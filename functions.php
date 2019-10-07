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
	// check if a recharge was already done today
	$recharge = Connection::query("
		SELECT COUNT(id) AS total
		FROM _recargas 
		WHERE inserted >= DATE(NOW())");

	// do not continue if a purchase was already made today
	if($recharge[0]->total > 0) return false;

	// get the buyer Person object
	$buyer = Utils::getPerson($payment->buyer);

	// do not continue if the phone number is blocked for scams
	$blocked = Connection::query("SELECT * FROM blocked_numbers WHERE cellphone='{$buyer->cellphone}'");
	if($blocked) return false;

	// check the buyer is at least one month old
	$isOldUser = date_diff(new DateTime(), new DateTime($buyer->insertion_date))->days > 60;
	if(!$isOldUser) return false;

	// add the recharge to the table
	// TODO: stage = 2, se mantiene mientras exista la regla de negocio "una recarga por fecha"
	Connection::query("
		INSERT IGNORE INTO _recargas (person_id, product_code, cellphone, stage)
		VALUES ({$buyer->id}, '{$payment->code}', '{$buyer->cellphone}', 2)");

	// return false if no row was created (time collisions)
	if (Connection::lastAffectedRows() < 1) return false;

	// return OK
	return true;
}