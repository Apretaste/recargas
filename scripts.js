$(document).ready(function(){
	// start tabs
	$('.tabs').tabs();

	// start modal
	$('.modal').modal({
		onOpenEnd: function(){
			$('#result').focus();
		}
	});

	// start counting ...
	if($('#time').length) {
		setInterval(counting, 1000);
	}
});

// start counting 
function counting() {
	// get time in the view
	var time = $('#time').html();

	// get hour and minutes
	var parts = time.split(':');
	var hour = parseInt(parts[0]);
	var minutes = parseInt(parts[1]);
	var seconds = parseInt(parts[2]);

	// add one second
	seconds++;

	// reset seconds
	if(seconds >= 60) {
		seconds = 0;

		// reset minutes
		minutes++;
		if(minutes >= 60) {
			minutes = 0;

			// reset hour
			if(hour >= 12) hour = 1;
			else hour++;
		}
	}

	// save back to the view
	if(seconds < 10) seconds = '0' + seconds; 
	if(minutes < 10) minutes = '0' + minutes; 
	var timeback = (hour + ':' + minutes + ':' + seconds);
	$('#time').html(timeback);
}

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
	if (cell.length !== 10 || !cell.startsWith('53')) {
		M.toast({html: 'Su número de celular debe empezar con 53 y tener 10 dígitos'});
		return false;
	}

	// submit the number
	apretaste.send({
		"command": "PERFIL UPDATE",
		"data": {"cellphone": cell},
		'redirect': false,
		'callback': {'name': 'updateRedirect'}
	});
}

// redirect to home
function updateRedirect(){
	apretaste.send({"command": "RECARGAS"});
}

// start a payment process
function pay() {
	// get the math result
	var result = $('#result').val();

	// do not submit empty results
	if(!result) return false;

	// try to get the recharge
	apretaste.send({
		command: 'RECARGAS PAY',
		data: {'captcha': result}
	});
}

// pollyfill startsWith for Android 4.4
if (!String.prototype.startsWith) {
	String.prototype.startsWith = function(searchString, position){
		position = position || 0;
		return this.substr(position, searchString.length) === searchString;
	};
}