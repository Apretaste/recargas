$(document).ready(function(){
	$('.tabs').tabs();
	$('.modal').modal();
});

var colors = {
	'Azul': '#99F9FF',
	'Verde': '#9ADB05',
	'Rojo': '#FF415B',
	'Morado': '#58235E',
	'Naranja': '#F38200',
	'Amarillo': '#FFE600'
};
var avatars = {
	'Rockera': 'F',
	'Tablista': 'F',
	'Rapero': 'M',
	'Guapo': 'M',
	'Bandido': 'M',
	'Encapuchado': 'M',
	'Rapear': 'M',
	'Inconformista': 'M',
	'Coqueta': 'F',
	'Punk': 'M',
	'Metalero': 'M',
	'Rudo': 'M',
	'Señor': 'M',
	'Nerd': 'M',
	'Hombre': 'M',
	'Cresta': 'M',
	'Emo': 'M',
	'Fabulosa': 'F',
	'Mago': 'M',
	'Jefe': 'M',
	'Sensei': 'M',
	'Rubia': 'F',
	'Dulce': 'F',
	'Belleza': 'F',
	'Músico': 'M',
	'Rap': 'M',
	'Artista': 'M',
	'Fuerte': 'M',
	'Punkie': 'M',
	'Vaquera': 'F',
	'Modelo': 'F',
	'Independiente': 'F',
	'Extraña': 'F',
	'Hippie': 'M',
	'Chica Emo': 'F',
	'Jugadora': 'F',
	'Sencilla': 'F',
	'Geek': 'F',
	'Deportiva': 'F',
	'Moderna': 'F',
	'Surfista': 'M',
	'Señorita': 'F',
	'Rock': 'F',
	'Genia': 'F',
	'Gótica': 'F',
	'Sencillo': 'M',
	'Hawaiano': 'M',
	'Ganadero': 'M',
	'Gótico': 'M'
};

function pad(n, width, z) {
	z = z || '0';
	n = n + '';
	return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
}

function getAvatar(avatar, serviceImgPath, size) {
	var index = Object.keys(avatars).indexOf(avatar);
	var fullsize = size * 7;
	var x = index % 7 * size;
	var y = Math.floor(index / 7) * size;
	return "background-image: url(" + serviceImgPath + "/avatars.png);" + "background-size: " + fullsize + "px " + fullsize + "px;" + "background-position: -" + x + "px -" + y + "px;";
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

function _typeof(obj) {
	if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
		_typeof = function _typeof(obj) {
			return typeof obj;
		};
	} else {
		_typeof = function _typeof(obj) {
			return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
		};
	}
	return _typeof(obj);
}

if (!Object.keys) {
	Object.keys = function () {
		'use strict';

		var hasOwnProperty = Object.prototype.hasOwnProperty,
			hasDontEnumBug = !{
				toString: null
			}.propertyIsEnumerable('toString'),
			dontEnums = ['toString', 'toLocaleString', 'valueOf', 'hasOwnProperty', 'isPrototypeOf', 'propertyIsEnumerable', 'constructor'],
			dontEnumsLength = dontEnums.length;
		return function (obj) {
			if (_typeof(obj) !== 'object' && (typeof obj !== 'function' || obj === null)) {
				throw new TypeError('Object.keys called on non-object');
			}

			var result = [],
				prop,
				i;

			for (prop in obj) {
				if (hasOwnProperty.call(obj, prop)) {
					result.push(prop);
				}
			}

			if (hasDontEnumBug) {
				for (i = 0; i < dontEnumsLength; i++) {
					if (hasOwnProperty.call(obj, dontEnums[i])) {
						result.push(dontEnums[i]);
					}
				}
			}

			return result;
		};
	}();
}
