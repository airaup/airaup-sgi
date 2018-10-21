// JavaScript Document
	function validarDatos(){
		if ($("#nombres").attr("value").replace(/^\s*|\s*$/g,"")==""){
			alert("Tenés que ingresar tus nombres");
			return;
		} else if ($("#apellidos").attr("value").replace(/^\s*|\s*$/g,"")==""){
			alert("Tenés que ingresar tus apellidos");
			return;
		} else if ($("#documento").attr("value").replace(/^\s*|\s*$/g,"")==""){
			alert("Tenés que ingresar tu número de documento");
			return;
		} else if (isNaN($("#documento").attr("value").replace(/^\s*|\s*$/g,""))){
			alert("El documento debe ser númerico");
			return;
		} else if ($("#fechaNac").attr("value").replace(/^\s*|\s*$/g,"")=="__/__/____" || $("#fechaNac").attr("value").replace(/^\s*|\s*$/g,"")==""){
			alert("Tenés que ingresar tu fecha de nacimiento");
			return;
		} else if (!isValidDate($("#fechaNac").attr("value").replace(/^\s*|\s*$/g,""))){
			alert("La fecha de nacimiento ingresada no es válida");
			return;
		} else if ($("#telefono").attr("value").replace(/^\s*|\s*$/g,"")==""){
			alert("Tenés que ingresar tu número de teléfono");
			return;
		} else if (buscarLetras($("#telefono").attr("value").replace(/^\s*|\s*$/g,""))){
			alert("El valor de teléfono no puede contener letras");
			return;
		} else if ($("#ocupacion").attr("value").replace(/^\s*|\s*$/g,"")==""){
			alert("Tenés que ingresar tu ocupación");
			return;
		} else if ($("#mail").attr("value").replace(/^\s*|\s*$/g,"")==""){
			alert("Tenés que ingresar tu dirección de correo electrónico");
			return;
		} else if ($("#pwd").attr("value").replace(/^\s*|\s*$/g,"")==""){
			alert("Tenés que ingresar tu contraseña");
			return;
		} else if ($("#pwd2").attr("value").replace(/^\s*|\s*$/g,"")==""){
			alert("Tenés que repetir tu contraseña");
			return;
		} else if ($("#pwd").attr("value").replace(/^\s*|\s*$/g,"")!=$("#pwd2").attr("value").replace(/^\s*|\s*$/g,"")){
			alert("Las contraseñas ingresadas no coinciden");
			return;				
		}
		
		document.getElementById("fechaNac").disabled = false;
		document.preregistro.submit();
	}
	
	function buscarLetras(numero){
		if (numero.match(/[a-z]/i)) {
			return true;
		} else {
			return false;	
		}
	}
	
	function isValidDate(dateString){
	    // First check for the pattern
    	if(!/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(dateString))
        	return false;

	    // Parse the date parts to integers
    	var parts = dateString.split("/");
	    var month = parseInt(parts[1], 10);
    	var day = parseInt(parts[0], 10);
	    var year = parseInt(parts[2], 10);
		
		var d = new Date();
		var n = d.getFullYear();
		
		var maxYear = n - 10;
		var minYear = n - 80;

    	// Check the ranges of month and year
	    if(year < minYear || year > maxYear || month == 0 || month > 12)
    	    return false;

	    var monthLength = [ 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 ];

	    // Adjust for leap years
    	if(year % 400 == 0 || (year % 100 != 0 && year % 4 == 0))
	        monthLength[1] = 29;
	
	    // Check the range of the day
    	return day > 0 && day <= monthLength[month - 1];
	};