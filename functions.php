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
	$recharge = Connection::query("
		SELECT * 
		FROM _recargas 
		WHERE inserted >= DATE(NOW())");

	// do not continue if a purchase was already made today
	if($recharge) return false;

	// add the recharge to the table
	Connection::query("
		INSERT INTO _recargas (person_id, product_code) 
		VALUES ({$payment->buyer->id}, '{$payment->code}')");

	return true;
}