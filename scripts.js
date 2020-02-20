$(document).ready(function(){
	$('.tabs').tabs();
	$('.modal').modal();
});

// shorten a name to fit in the box
function short(username) {
	if (username.length > 9) {
		return username.substring(0, 6) + '...';
	}
	return username;
}

// open the person's profile
function profile(username) {
	apretaste.send({
		'command': 'PERFIL',
		'data': {'username': username}
	});
}

// change the phone number
function sendPhone() {
	// get the coupon
	var cell = $('#cell').val().trim();

	// check the coupon is not empty
	if (cell.length !== 10 || !cell.startsWith("53")) {
		M.toast({html: 'Su número de celular debe empezar con 53 y tener 10 dígitos'});
		return false;
	}

	// submit the number
	apretaste.send({
		"command": "PERFIL UPDATE",
		"data": {"cellphone": cell}
	});
}