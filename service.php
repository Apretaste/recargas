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
	public function _main(Request $request, Response $response)
	{
		// check if the user has a cellphone
		$phone = $request->person->cellphone;
		if(strlen($phone) != 10 || !substr($phone,0,2) === "53") {
			return $response->setTemplate("phone.ejs", ["phone"=>$phone]);
		}

		// get the price for the recharge for today
		$product = Connection::query("SELECT name, price FROM inventory WHERE code = 'CUBACEL_10'");
		$price = $product[0]->price;

		// get the recharge for today, or false
		$recharge = Connection::query(
			"SELECT A.inserted, B.username
			FROM _recargas A
			JOIN person B 
			ON A.person_id = B.id
			WHERE inserted >= DATE(NOW())
			UNION
			SELECT B.`inserted_date` AS inserted, A.username FROM person A 
			JOIN `_tienda_orders` B ON A.email=B.email 
			AND DATE(B.`inserted_date`) = CURRENT_DATE
			AND B.product='1806121252'");
		$recharge = empty($recharge) ? false : $recharge[0];

		$lastMonth = Connection::query(
			"SELECT id FROM _recargas 
			WHERE person_id='{$request->person->id}'
			AND MONTH(inserted)=MONTH(CURRENT_DATE)
			UNION
			SELECT id FROM _tienda_orders WHERE email=(SELECT email FROM person 
			WHERE id='{$request->person->id}')
			AND MONTH(inserted_date)=MONTH(CURRENT_DATE)");

		$lastMonth = !empty($lastMonth);

		// set the cache till the end of the day
		if($recharge) {
			$minsUntilDayEnds = ceil((strtotime("23:59:59") - time()) / 60);
			$response->setCache($minsUntilDayEnds);
		}

		$phoneIsBlocked = Connection::query("SELECT * FROM blocked_numbers WHERE cellphone='$phone'");
		$phoneIsBlocked = !empty($phoneIsBlocked);

		$isOldUser = date_diff(new DateTime(), new DateTime($request->person->insertion_date))->days > 60;

		// create the content array
		$content = [
			"price" => $price,
			"cellphone" => $request->person->cellphone,
			"recharge" => $recharge,
			"hasRechargeInLastMonth" => $lastMonth,
			"phoneIsBlocked" => $phoneIsBlocked,
			"isOldUser" => $isOldUser
		];

		// send data to the view
		$response->setTemplate("home.ejs", $content);
	}

	/**
	 * Check the last 20 previous recharges
	 *
	 * @author salvipascual
	 * @param Request $request
	 * @param Response $response	 
	 */
	public function _anteriores(Request $request, Response $response)
	{
		// get the recharge for today, or false
		$recharges = Connection::query(
			"SELECT * FROM (SELECT B.username, A.inserted
			FROM _recargas A
			JOIN person B 
			ON A.person_id = B.id
			UNION
			SELECT A.username, B.`inserted_date` AS inserted FROM person A 
			JOIN `_tienda_orders` B ON A.email=B.email 
			AND B.product='1806121252') A
			ORDER BY inserted DESC");

		// set the cache till the end of the day and send data to the view
		$minsUntilDayEnds = ceil((strtotime("23:59:59") - time()) / 60);
		$response->setCache($minsUntilDayEnds);
		$response->setTemplate("anteriores.ejs", ["recharges"=>$recharges]);
	}
}
