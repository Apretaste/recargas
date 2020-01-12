<?php

class Service
{
	/**
	 * Main
	 *
	 * @author salvipascual
	 * @param Request $request
	 * @param Response $response
	 */
	public function _main (Request $request, Response $response)
	{
		// check if the user has a cellphone
		$phone = $request->person->cellphone;
		if(strlen($phone) != 10 || !substr($phone,0,2) ==='53') {
			return $response->setTemplate('phone.ejs', ['phone' =>$phone]);
		}

		// get the price for the recharge for today
		$product = Connection::query("SELECT name, price FROM inventory WHERE code = 'CUBACEL_10'");
		$price = $product[0]->price;

		// get the recharge for today, or false
		$recharge = Connection::query('
			SELECT A.inserted, B.username, B.avatar, B.avatarColor
			FROM _recargas A
			JOIN person B
			ON A.person_id = B.id
			WHERE inserted >= DATE(NOW())');
		$recharge = empty($recharge) ? false : $recharge[0];

		// check if the user has bough a recharge the current month
		$lastMonth = !empty(Connection::query("
			SELECT id FROM _recargas
			WHERE person_id = '{$request->person->id}'
			AND MONTH(inserted) = MONTH(CURRENT_DATE) AND YEAR(inserted) = YEAR(CURRENT_DATE)"));

		// set the cache till the end of the day
		if($recharge) {
			$minsUntilDayEnds = ceil((strtotime('23:59:59') - time()) / 60);
			$response->setCache($minsUntilDayEnds);
		}

		// check if the phone is blocked
		$phoneIsBlocked = !empty(Connection::query("SELECT * FROM blocked_numbers WHERE cellphone='$phone'"));

		// check if the user is a month old
		$isOldUser = date_diff(new DateTime(), new DateTime($request->person->insertion_date))->days > 60;

		// create the content array
		$content = [
				'price'                  => $price,
				'cellphone'              => $request->person->cellphone,
				'recharge'               => $recharge,
				'hasRechargeInLastMonth' => $lastMonth,
				'phoneIsBlocked'         => $phoneIsBlocked,
				'isOldUser'              => $isOldUser
		];

		// send data to the view
		$response->setTemplate('home.ejs', $content);
	}

	/**
	 * Check the last 20 previous recharges
	 *
	 * @author salvipascual
	 * @param Request $request
	 * @param Response $response
	 */
	public function _anteriores (Request $request, Response $response)
	{
		// show a list of previous recharges
		$recharges = Connection::query("
			SELECT B.username, DATE_FORMAT(A.inserted, '%e/%c/%Y %r') AS inserted, B.avatar, B.avatarColor
			FROM _recargas A
			JOIN person B
			ON A.person_id = B.id
			ORDER BY A.inserted DESC
			LIMIT 20");

		// set the cache till the end of the day and send data to the view
		$minsUntilDayEnds = ceil((strtotime('23:59:59') - time()) / 60);
		$response->setCache($minsUntilDayEnds);
		$response->setTemplate('anteriores.ejs', ['recharges' =>$recharges]);
	}

	/**
	 * Pay for an item and add the items to the database
	 *
	 * @param Request
	 * @param Response
	 *
	 * @throws Exception
	 */
	public function _pay (Request $request, Response $response)
	{
		// get buyer and code
		$buyer = $request->person;
		$code = $request->input->data->code;

		// check if a recharge was already done today
		$isMaxReached = Connection::query('SELECT COUNT(id) AS cnt FROM _recargas WHERE inserted >= DATE(NOW())')[0]->cnt > 0;

		// do not continue if recharges max was reached today
		if($isMaxReached) {
			$response->setTemplate('message.ejs', [
					'header' => '¡Sigue intentando!',
					'icon'   => 'sentiment_very_dissatisfied',
					'text'   => 'Lamentablemente, alguien más fue un poco más rápido que tú y canjeo la recarga del día. No te desanimes, mañana tendrás otra oportunidad para tratar de canjearla.',
					'button' => ['href' => 'RECARGAS ANTERIORES', 'caption' => 'Ver recargas']]);
			return;
		}

		// do not continue if the phone number is blocked for scams
		$isUserBlocked = Connection::query("SELECT COUNT(*) AS cnt FROM blocked_numbers WHERE cellphone='{$buyer->cellphone}'")[0]->cnt > 0;

		// check the buyer is at least one month old
		$isNewUser = date_diff(new DateTime(), new DateTime($buyer->insertion_date))->days < 60;

		// do not continue if a rule is broken
		if($isUserBlocked || $isNewUser) {
			$response->setTemplate('message.ejs', [
					'header' => 'Canje rechazado',
					'icon'   => 'sentiment_very_dissatisfied',
					'text'   => 'Puede que su usuario esté bloqueado o que aún no tenga permisos para comprar recargas. Por favor consulte el soporte si tiene alguna duda.',
					'button' => ['href' => 'RECARGAS ANTERIORES', 'caption' => 'Ver recargas']]);
			return;
		}

		$security_code = uniqid('',true);

		// add the recharge to the table
		// TODO: stage = 2, se mantiene mientras exista la regla de negocio "una recarga por fecha"
		Connection::query("
			INSERT IGNORE INTO _recargas (person_id, product_code, cellphone, stage, inserted_date, security_code)
			VALUES ({$buyer->id}, '$code', '{$buyer->cellphone}', 2, CURRENT_DATE, '$security_code')");

		$r = Connection::query("SELECT * FROM _recargas where security_code = '$security_code'");
		if (isset($r[0]))
			if ((int) $r[0]->person_id === $buyer->id)
			{
				// process the payment
				try {
					MoneyNew::buy($buyer->id, $code);
				} catch (Exception $e) {
					echo $e->getMessage();
					$response->setTemplate('message.ejs', [
							'header' => 'Error inesperado',
							'icon'   => 'sentiment_very_dissatisfied',
							'text'   => 'Hemos encontrado un error procesando su canje. Por favor intente nuevamente, si el problema persiste, escríbanos al soporte.',
							'button' => ['href' => 'RECARGAS', 'caption' => 'Reintentar']]);
					return;
				}

				// possitive response
				 $response->setTemplate('message.ejs', [
						 'header' => 'Canje realizado',
						 'icon'   => 'sentiment_very_satisfied',
						 'text'   => 'Su canje se ha realizado satisfactoriamente, y su teléfono recibirá una recarga en menos de tres días. Si tiene cualquier pregunta, por favor no dude en escribirnos al soporte.',
						 'button' => ['href' => 'RECARGAS ANTERIORES', 'caption' => 'Ver recargas']]);
				return;
			}

		 $response->setTemplate('message.ejs', [
				 'header' => '¡Sigue intentando!',
				 'icon'   => 'sentiment_very_dissatisfied',
				 'text'   => 'Lamentablemente, alguien más fue un poco más rápido que tú y canjeo la recarga del día. No te desanimes, mañana tendrás otra oportunidad para tratar de canjearla.',
				 'button' => ['href' => 'RECARGAS ANTERIORES', 'caption' => 'Ver recargas']]);
	}
}
