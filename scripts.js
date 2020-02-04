$(document).ready(function(){
	$('.tabs').tabs();
	$('.modal').modal();
});

function pad(n, width, z) {
	z = z || '0';
	n = n + '';
	return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
}

// change the phone number
function sendPhone() {
	// get the coupon
	var cell = $('#cell').val().trim();

	// check the coupon is not empty
	if (cell.length !== 10 || !cell.startsWith("53")) {
		M.toast({html: 'Su número de celular debe empezar con 53 y tener una longitus de 10 dígitos'});
		return false;
	}

	apretaste.send({
		"command": "PERFIL UPDATE",
		"data": {"cellphone": cell},
		"redirect": false,
		"callback": {"name": "callbackReloadHome", "data": ""}
	});
}

// callback to go Home
function callbackReloadHome() {
	apretaste.send({"command": "RECARGAS"});
}

// formats a time
function formatTime(dateStr) {
	var date = new Date(dateStr);
	var hour = (date.getHours() < 12) ? date.getHours() : date.getHours() - 12;
	var minutes = pad(date.getMinutes(), 2);
	var amOrPm = (date.getHours() < 12) ? "am" : "pm";
	return hour + ':' + minutes + amOrPm;
}

// show the modal popup
function openModal(code) {
	$('#modal').modal('open');
}

// create a new purchase
function pay() {
	apretaste.send({
		command: "RECARGAS PAY",
		data: {'code': 'CUBACEL_10'},
		redirect: true
	});
}