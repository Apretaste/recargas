<?php

use Framework\Database;
use Apretaste\Money;
use Apretaste\Level;
use Apretaste\Request;
use Apretaste\Response;

class Service
{
	/**
	 * Display the steps to buy recharges
	 *
	 * @param Request $request
	 * @param Response $response
	 * @author salvipascual
	 */
	public function _main(Request $request, Response $response)
	{
		// get the price for the recharge
		$product = Database::queryCache("SELECT price FROM inventory WHERE code = 'CUBACEL_10'", Database::CACHE_DAY);
		$price = $product[0]->price;

		// get the recharges calendar for today
		$schedule = Database::query("SELECT open FROM _recargas_schedule WHERE open >= CURRENT_DATE");

		// create the content array
		$content = [
			"phone" => $request->person->phone,
			"credit" => $request->person->credit,
			"price" => $price,
			"recharges" => $schedule
		];

		// send data to the view
		$response->setTemplate("home.ejs", $content);
	}

	/**
	 * Check the last 20 previous recharges
	 *
	 * @param Request $request
	 * @param Response $response
	 * @author salvipascual
	 */
	public function _anteriores(Request $request, Response $response)
	{
		// show a list of previous recharges
		$recharges = Database::query("
			SELECT 
				B.username, B.avatar, B.avatarColor, B.gender,
				DATE_FORMAT(A.inserted, '%e/%c/%Y %r') AS inserted,
				(A.inserted >= CURRENT_DATE) AS today
			FROM _recargas A
			JOIN person B 
			ON A.person_id = B.id
			ORDER BY A.inserted DESC
			LIMIT 20");

		// set the cache till the end of the day and send data to the view
		$response->setCache('hour');
		$response->setTemplate("anteriores.ejs", ["recharges" => $recharges]);
	}

	/**
	 * Updates the phone number
	 *
	 * @param Request $request
	 * @param Response $response
	 * @author salvipascual
	 */
	public function _telefono(Request $request, Response $response)
	{
		$response->setTemplate("phone.ejs", ["phone" => $request->person->phone]);
	}

	/**
	 * Display the help document
	 *
	 * @param Request $request
	 * @param Response $response
	 * @author salvipascual
	 */
	public function _ayuda(Request $request, Response $response)
	{
		$response->setCache('year');
		$response->setTemplate("help.ejs");
	}

	/**
	 * Pay for an item and add the items to the database
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function _pay(Request $request, Response $response)
	{
		// get buyer and code
		$buyer = $request->person;

		// get the next rechgarge to claim
		$nextRechargeScheduled = Database::query("
			SELECT open 
			FROM _recargas_schedule 
			WHERE open >= CURRENT_TIMESTAMP 
			ORDER BY open 
			LIMIT 1");

		// stop if no rechgarges were scheduled
		if (empty($nextRechargeScheduled)) {
			return $response->setTemplate('message.ejs', [
				"header" => "No hay recargas",
				"icon" => "sentiment_very_dissatisfied",
				"text" => "Por ahora no tenemos recargas disponibles para canjear. Es posible que el tiempo de expiración se halla alcanzado. Intente nuevamente más tarde.",
				"button" => ["href" => "RECARGAS ANTERIORES", "caption" => "Ver recargas"]
			]);
		}

print_r($nextRechargeScheduled); exit;

		// check if a recharge was already done today
		$isMaxReached = Database::query("
			SELECT COUNT(id) AS cnt 
			FROM _recargas 
			WHERE inserted >= CURRENT_DATE")[0]->cnt > 0;

		// do not continue if recharges max was reached today
		if ($isMaxReached) {
			return $response->setTemplate('message.ejs', [
				"header" => "¡Sigue intentando!",
				"icon" => "sentiment_very_dissatisfied",
				"text" => "Lamentablemente, alguien fue un poco más rápido que tú y canjeo la recarga del día. No te desanimes, mañana tendrás otra oportunidad para tratar de canjearla.",
				"button" => ["href" => "RECARGAS ANTERIORES", "caption" => "Ver recargas"]
			]);
		}

		// check if the phone number is blocked for scams
		$isPhoneBlocked = Database::query("SELECT COUNT(*) AS cnt FROM blocked_numbers WHERE cellphone = '{$buyer->phone}'")[0]->cnt > 0;

		// check the buyer is Topacio or higer
		$isLevelTopacio = $request->person->levelCode == Level::TOPACIO;

		// do not continue if a rule is broken
		if ($isPhoneBlocked || $isLevelTopacio) {
			return $response->setTemplate('message.ejs', [
				"header" => "Canje rechazado",
				"icon" => "sentiment_very_dissatisfied",
				"text" => "Puede que su usuario esté bloqueado o que aún no sea nivel Topacio o superior. Por favor consulte el soporte si tiene alguna duda.",
				"button" => ["href" => "RECARGAS ANTERIORES", "caption" => "Ver recargas"]
			]);
		}

		$securityCode = uniqid('',true);

		// add the recharge to the table
		Database::query("
			INSERT IGNORE INTO _recargas (person_id, product_code, cellphone, securityCode)
			VALUES ({$buyer->id}, 'CUBACEL_10', '{$buyer->cellphone}', '$securityCode')");

		// check if there is a security code
		$res = Database::query("SELECT * FROM _recargas WHERE securityCode = '$securityCode'");

		// process the payment
		if (isset($res[0])) {
			try {
				Money::purchase($buyer->id, 'CUBACEL_10');
			} catch (Exception $e) {
				// show user alert
				$alert = new Alert($e->getCode(), $e->getMessage());
				$alert->post();

				// rollback the transaction
				Database::query("DELETE FROM _recargas WHERE securityCode = '$securityCode'");

				// show error message to the user
				return $response->setTemplate('message.ejs', [
					"header" => "Error inesperado",
					"icon" => "sentiment_very_dissatisfied",
					"text" => "Hemos encontrado un error procesando su canje. Por favor intente nuevamente, si el problema persiste, escríbanos al soporte.",
					"button" => ["href" => "RECARGAS", "caption" => "Reintentar"]
				]);
			}
		}

		// possitive response
		$response->setTemplate('message.ejs', [
			"header" => "Canje realizado",
			"icon" => "sentiment_very_satisfied",
			"text" => "Su canje se ha realizado satisfactoriamente, y su teléfono recibirá una recarga en menos de tres días. Si tiene cualquier pregunta, por favor no dude en escribirnos al soporte.",
			"button" => ["href" => "RECARGAS ANTERIORES", "caption" => "Ver recargas"]
		]);
	}

	/**
	 * Check and format a cellphone number from Cuba
	 *
	 * @param String $number
	 * @return Bool
	 */
	private function checkNumber(&$number)
	{
		$number = trim(str_replace(['-', ' ', '+', '(', ')'], '', $number));
		if (strlen($number) === 8) $number = "53$number";
		if (strlen($number) !== 10) return false;
		if (strpos($number, '53')!==0) return false;
		return true;
	}
}