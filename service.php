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
		$recharge = Connection::query("
			SELECT A.inserted, B.username
			FROM _recargas A
			JOIN person B 
			ON A.person_id = B.id
			WHERE inserted >= DATE(NOW())");
		$recharge = empty($recharge) ? false : $recharge[0];

		// check if the user has bough a recharge the current month
		$lastMonth = !empty(Connection::query("
			SELECT id FROM _recargas 
			WHERE person_id = '{$request->person->id}'
			AND MONTH(inserted) = MONTH(CURRENT_DATE)"));

		// set the cache till the end of the day
		if($recharge) {
			$minsUntilDayEnds = ceil((strtotime("23:59:59") - time()) / 60);
			$response->setCache($minsUntilDayEnds);
		}

		// check if the phone is blocked
		$phoneIsBlocked = !empty(Connection::query("SELECT * FROM blocked_numbers WHERE cellphone='$phone'"));

		// check if the user is a month old
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
		// show a list of previous recharges
		$recharges = Connection::query("
			SELECT B.username, DATE_FORMAT(A.inserted, '%e/%c/%Y %r') AS inserted
			FROM _recargas A
			JOIN person B 
			ON A.person_id = B.id
			ORDER BY A.inserted DESC
			LIMIT 20");

		// set the cache till the end of the day and send data to the view
		$minsUntilDayEnds = ceil((strtotime("23:59:59") - time()) / 60);
		$response->setCache($minsUntilDayEnds);
		$response->setTemplate("anteriores.ejs", ["recharges"=>$recharges]);
	}
}