<?php

use Framework\Alert;
use Framework\Database;
use Apretaste\Money;
use Apretaste\Level;
use Apretaste\Request;
use Apretaste\Response;

class Service
{
	private $inventoryCode = 'CUBACEL_10';

	/**
	 * Display the steps to buy recharges
	 *
	 * @param  Request $request
	 * @param  Response $response
	 * @author salvipascual
	 */
	public function _main(Request $request, Response $response)
	{
		// get the price for the recharge
		$rechargePrice = Database::queryCache("SELECT price FROM inventory WHERE code = '{$this->inventoryCode}'", Database::CACHE_DAY)[0]->price;

		// get the recharges calendar for today
		$schedule = Database::query('SELECT scheduled FROM _recharges WHERE scheduled >= CURRENT_DATE ORDER BY scheduled ASC');

		// create the content array
		$content = [
			'phone' => $request->person->phone,
			'credit' => $request->person->credit,
			'price' => $rechargePrice,
			'recharges' => $schedule
		];

		// send data to the view
		$response->setTemplate('home.ejs', $content);
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
			SELECT B.username, B.avatar, B.avatarColor, B.gender, A.bought
			FROM _recharges A
			JOIN person B 
			ON A.person_id = B.id
			ORDER BY bought DESC
			LIMIT 20");

		// set the cache till the end of the day and send data to the view
		$response->setCache('hour');
		$response->setTemplate('anteriores.ejs', ['recharges' => $recharges]);
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
		$response->setTemplate('phone.ejs', ['phone' => $request->person->phone]);
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
		// get the price for the recharge
		$rechargePrice = Database::queryCache("SELECT price FROM inventory WHERE code = '{$this->inventoryCode}'", Database::CACHE_DAY)[0]->price;

		$response->setCache('year');
		$response->setTemplate('help.ejs', ['price' =>$rechargePrice]);
	}

	/**
	 * Pay for an item and add the items to the database
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function _pay(Request $request, Response $response)
	{
		// check if the phone number is blocked for scams
		$isPhoneBlocked = Database::query("SELECT COUNT(*) AS cnt FROM blocked_numbers WHERE cellphone = '{$request->person->phone}'")[0]->cnt > 0;
		if ($isPhoneBlocked) {
			return $this->displayError('Su usuario esté bloqueado y no tiene permisos para recargar. Por favor consulte el soporte si tiene alguna duda.', $response);
		}

		// check if user is not yet level Topacio
		if ($request->person->levelCode == Level::TOPACIO) {
			return $this->displayError('Su usuario aún no sea nivel Topacio o superior. Siga usando la app para ganar experiencia y subir de nivel.', $response);
		}

		// check if your phone number is valid 
		if (!$this->checkNumber($request->person->phone)) {
			return $this->displayError('El número que insertastes "{$request->person->phone}" no parece un teléfono válido de Cuba. Por favor modifica tu teléfono e intenta nuevamente.', $response);
		}

		// check if user has enough credit
		$rechargePrice = Database::queryCache("SELECT price FROM inventory WHERE code = '{$this->inventoryCode}'", Database::CACHE_DAY)[0]->price;
		if ($request->person->credit < $rechargePrice) {
			return $this->displayError('Usted no tiene suficiente crédito para conseguir este recarga. Siga usando la app y ganando crédito e intente nuevamente.', $response);
		}

		// POSITIVE check if there is a recharge available for the user
		$isARechargeAvailable = Database::query("SELECT COUNT(id) AS cnt FROM _recharges WHERE scheduled >= CURRENT_DATE AND scheduled < CURRENT_TIMESTAMP AND person_id IS NULL")[0]->cnt > 0;
		if($isARechargeAvailable) {
			// prepare a unique ID to ensure only one recharge is made
			$securityCode = uniqid('', true);

			// take the first unused slot and place the recharge
			Database::query("
				UPDATE _recharges SET
					person_id = {$request->person->id},
					product_code = '{$this->inventoryCode}',
					phone = '{$request->person->phone}',
					security_code = '$securityCode',
					bought = CURRENT_TIMESTAMP
				WHERE scheduled >= CURRENT_DATE 
				AND scheduled < CURRENT_TIMESTAMP 
				AND person_id IS NULL
				ORDER BY scheduled ASC
				LIMIT 1");

			// check if was me who won the recharge
			$userWon = Database::query("SELECT COUNT(id) AS cnt FROM _recharges WHERE security_code = '$securityCode'")[0]->cnt > 0;

			// if I was the one who got the recharge, process the payment
			if ($userWon) {
				try {
					Money::purchase($request->person->id, $this->inventoryCode);
				} catch (Exception $e) {
					// let the developers know what went wrong
					$alert = new Alert($e->getCode(), $e->getMessage());
					$alert->post();
		
					// rollback the transaction and display error message
					Database::query("
						UPDATE _recharges 
						SET person_id=NULL, product_code=NULL, phone=NULL, security_code=NULL, bought=NULL
						WHERE security_code = '$securityCode'");

					// show error message for unknown issues
					return $this->displayError('Hemos encontrado un error inesperado. Ya avisamos al equipo técnico. Por favor intente nuevamente, si el problema persiste, escríbanos al soporte.', $response);
				}

				// possitive response
				return $response->setTemplate('message.ejs', [
					'header' => 'Canje realizado',
					'icon' => 'sentiment_very_satisfied',
					'text' => 'Su canje se ha realizado satisfactoriamente, y su teléfono recibirá una recarga en menos de tres días. Si tiene cualquier pregunta, por favor no dude en escribirnos al soporte.',
					'button' => ['href' => 'RECARGAS ANTERIORES', 'caption' => 'Ver recargas']
				]);
			}
		}

		// check is there is a next recharge available
		$nextRechargeAvailable = Database::query('SELECT scheduled FROM _recharges WHERE scheduled >= CURRENT_TIMESTAMP AND person_id IS NULL ORDER BY scheduled ASC LIMIT 1');
		if (!empty($nextRechargeAvailable)) {
			$nextRechargeDate = date('d/m/Y \a \l\a\s h:m:s A', strtotime($nextRechargeAvailable[0]->scheduled));
			return $this->displayError("Por ahora no hay recargas a realizar. Si estabas cazando una, alguien se te adelantó. La próxima recarga estará disponible el $nextRechargeDate", $response);
		}

		// if there are no recharges available
		return $this->displayError('Lamentablemente, alguien fue un poco más rápido que tú y canjeo la recarga disponible. No te desanimes, espera a la hora de la próxima recarga y sé más rápido esta vez.', $response);
	}

	/**
	 * Return an error message to be displayed
	 *
	 * @param String $errorMessage 
	 * @param Response $response
	 * @return Response
	 */
	private function displayError(String $errorMessage, Response $response): Response
	{
		return $response->setTemplate('message.ejs', [
			'header' => 'Canje rechazado',
			'icon' => 'sentiment_very_dissatisfied',
			'text' => $errorMessage,
			'button' => ['href' => 'RECARGAS', 'caption' => 'Reintentar']
		]);
	}

	/**
	 * Check and format a cellphone number from Cuba
	 *
	 * @param String $number
	 * @return Bool
	 */
	private function checkNumber($number)
	{
		$number = trim(str_replace(['-', ' ', '+', '(', ')'], '', $number));
		if (strlen($number) === 8) $number = "53$number";
		if (strlen($number) !== 10) return false;
		if (strpos($number, '53')!==0) return false;
		return true;
	}
}