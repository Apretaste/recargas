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
	// TODO: stage = 2, se mantiene mientras exista la regla de negocio "una recarga por fecha"

	Connection::query("
		INSERT IGNORE INTO _recargas (person_id, product_code, cellphone, stage, inserted_date) 
		VALUES ({$payment->buyer->id}, '{$payment->code}', '{$payment->buyer->cellphone}', 2, CURRENT_DATE)");

	if (Connection::lastAffectedRows() < 1) return false;

	return true;
}