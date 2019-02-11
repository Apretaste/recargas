<?php

/**
 *
 * Reacargas Service
 */
class Service
{

	/**
	 * Default service response
	 *
	 * @param Response $response
	 * @param $template
	 * @param array $data
	 */
	public function setDefaultResponse(Response &$response, $template, &$data = [])
	{

		$today = Connection::query("SELECT A.username AS user, DATE_FORMAT(B.`inserted_date`, '%h:%i %p') AS hora 
						 FROM person A JOIN `_tienda_orders` B 
						  	ON A.email = B.email 
						  	AND CONVERT(B.`inserted_date`,DATE) = CONVERT(CURRENT_TIMESTAMP, DATE) 
						  	AND B.product='1806121252'");

		$images = ['logo' => $this->pathToService . '/recargas.png'];

		$data['images'] = $images;
		$data['disponible'] = ! isset($today[0]);
		$data['today'] = $today;
		$response->setTemplate("$template.ejs", [
			'images' => $images,
			'disponible' => ! isset($today[0]),
			'today' => $today
		], $images);
	}

	/**
	 * Main
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function _main(Request $request, Response &$response)
	{
		$this->setDefaultResponse($response, 'main');
	}

	/**
	 * RECARGAR subservice
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function _recargar(Request $request, Response &$response)
	{
		$this->setDefaultResponse($response, 'confirmar', $data);

		$number = (strlen($request->data->query) == 10) ? $request->data->query : "";
		$number = (substr($number, 0, 2) == "53") ? $number : "";

		if( ! empty($number))
		{
			$user = Utils::getPerson($request->email);

			if(empty($user->phone)) Connection::query("UPDATE person SET phone='$number' WHERE email='$request->email'");

			if( ! $data['disponible'])
			{
				$response->setTemplate("nodisponible.ejs", ['today' => $data['today']]);

				return;
			}

			if($user->credit < 40)
			{
				$response->setTemplate("text.ejs", [
					"title" => "Error en su recarga",
					"body" => "Usted tiene &sect;{$user->credit} de credito, lo cual no es suficiente para comprar la recarga con un valor de &sect;40"
				]);

				return;
			}

			$confirmationHash = Utils::generateRandomHash();
			Connection::query("START TRANSACTION;
			        UPDATE person SET credit=credit-40 WHERE email='{$request->person->email}';
			        UPDATE person SET credit=credit+40 WHERE email='alex@apretaste.com';
			        INSERT INTO _tienda_orders(product,email,phone) VALUES('1806121252','{$request->person->email}','+$number');
			        INSERT INTO transfer(sender,receiver,amount,confirmation_hash,inventory_code,transfered) VALUES ('{$request->person->email}', 'alex@apretaste.com', 40, '$confirmationHash', 'RECARGA',1);
			        COMMIT;");
		}
		else
		{
			$response->setTemplate("text.ejs", [
				"title" => "Error en su recarga",
				"body" => "El número que ingreso es invalido. El numero debe iniciar con 53 y tener una longitud total de 10 numeros"
			]);
		}

	}

	/**
	 * ANTERIORES subservice
	 *
	 * @param Request $request
	 * @param Response $response
	 *
	 */
	public function _anteriores(Request $request, Response &$response)
	{
		$data['compradores'] = Connection::query("SELECT A.username AS user, 
					DATE_FORMAT(B.`inserted_date`, '%d/%m/%Y %h:%i %p') AS fecha 
				FROM person A 
				JOIN _tienda_orders B 
      				ON A.email=B.email AND B.product='1806121252' 
      			ORDER BY fecha DESC 
      			LIMIT 30");

		$this->setDefaultResponse($response, 'anteriores', $data);
	}

	/**
	 * MIAS subservice
	 *
	 * @param Request $request
	 * @param Response $response
	 *
	 */
	public function _mias(Request $request, Response &$response)
	{
		$data['recargas'] = Connection::query("SELECT DATE_FORMAT(B.`inserted_date`, '%d/%m/%Y %h:%i %p') AS fecha, 
						A.amount AS amount, B.phone AS phone
      			FROM `transfer` A JOIN _tienda_orders B 
      			ON A.sender='$request->email' 
      				AND B.email='$request->email' 
      				AND A.inventory_code='RECARGA'
      				AND CONVERT(B.`inserted_date`,DATE) = CONVERT(A.transfer_time,DATE) 
      				AND B.product='1806121252'
      			ORDER BY fecha DESC");

		$this->setDefaultResponse($response, 'misrecargas', $data);
	}
}
