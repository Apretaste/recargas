<?php

/**
 * Function executed when a payment is finalized
 * Substract the credit from the user and 
 *
 * @author salvipascual
 * @param Payment $payment
 * @return boolean
 */
function payment(Payment $payment)
{
	// check if a recharge was done already today
	$recharge = Connection::query(
		"SELECT * 
		FROM _recargas 
		WHERE inserted >= DATE(NOW())
		UNION
		SELECT A.* FROM person A 
		JOIN `_tienda_orders` B ON A.email=B.email 
		AND CONVERT(B.`inserted_date`,DATE) = CONVERT(CURRENT_TIMESTAMP,DATE) 
		AND B.product='1806121252'");

	// do not continue if a purchase was already made today
	if($recharge) return false;

	// add the recharge to the table
	Connection::query("
		INSERT INTO _recargas (person_id, product_code, cellphone) 
		VALUES ({$payment->buyer->id}, '{$payment->code}', '{$payment->buyer->cellphone}')");

	return true;
}