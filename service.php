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
			SELECT B.username, A.inserted
			FROM _recargas A
			JOIN person B 
			ON A.person_id = B.id
			WHERE inserted >= DATE(NOW())");
		$recharge = empty($recharge) ? false : $recharge[0];

		// set the cache till the end of the day
		if($recharge) {
			$minsUntilDayEnds = ceil((strtotime("23:59:59") - time()) / 60);
			$response->setCache($minsUntilDayEnds);
		}

		// send data to the view
		$response->setTemplate("home.ejs", ["price"=>$price, "recharge"=>$recharge]);
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
		$recharges = Connection::query("
			SELECT B.username, A.inserted
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
