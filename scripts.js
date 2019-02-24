// change the phone number
function sendPhone() {
	// get the coupon
	var cell = $('#cell').val().trim();

	// check the coupon is not empty
	if(cell.length != 10 || !cell.startsWith("53")) {
		M.toast({html:'Su número de celular debe empezar con 53 y tener una longitus de 10 dígitos'});
		return false;
	}

	apretaste.send({
		"command": "PERFIL UPDATE",
		"data": {"cellphone": cell},
		"redirect": false,
		"callback":{"name":"callbackReloadHome","data":""}
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
	var minutes = String(date.getMinutes()).padStart(2, "0");
	var amOrPm = (date.getHours() < 12) ? "am" : "pm";
	return hour + ':' + minutes + amOrPm;
}

// formats a date and time
function formatDateTime(dateStr) {
	var months = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
	var date = new Date(dateStr);
	var month = date.getMonth();
	var day = date.getDate().toString().padStart(2, '0');
	var hour = (date.getHours() < 12) ? date.getHours() : date.getHours() - 12;
	var minutes = String(date.getMinutes()).padStart(2, "0");
	var amOrPm = (date.getHours() < 12) ? "am" : "pm";
	return day + ' de ' + months[month] + ', ' + hour + ':' + minutes + amOrPm;
}

