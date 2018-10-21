// JavaScript Document
	function verificarRueda(){
		var club = document.getElementById('club');
		nombreClub = club.options[club.selectedIndex].text;
		
		if (nombreClub != 'Otro'){
			document.getElementById('rotaract').checked = true;
			
			var radios = document.getElementsByName('tipoRueda');
			for (var i = 0; i< radios.length;  i++){
		    	radios[i].disabled = true;
			}
			
			
			chequearRueda();
		} else {
			document.getElementById('rotaract').checked = false;
			
			var radios = document.getElementsByName('tipoRueda');
			for (var i = 0; i< radios.length;  i++){
		    	radios[i].disabled = false;
			}
			
			chequearRueda();
		}
	}
	
	function chequearRueda(){
		if ($('input[name=tipoRueda]:checked').val() == 'Interact'){
			var ocultar = document.getElementsByClassName('ocultar');
			for (var x=0; x < ocultar.length; x++) {
				ocultar[x].hidden = true;
			}
			
			var ocultarInteract = document.getElementsByClassName('ocultarInteract');
			for (var x=0; x < ocultarInteract.length; x++) {
				ocultarInteract[x].hidden = true;
			}
			
			var editar = getUrlVars()["a"];
			
			if (editar == "editarCargos"){
				document.getElementById("botonInteract").innerHTML = "Finalizar";	
			}
		} else if ($('input[name=tipoRueda]:checked').val() == 'Rotaract'){
			var ocultar = document.getElementsByClassName('ocultar');
			for (var x=0; x < ocultar.length; x++) {
				ocultar[x].hidden = false;
			}
			
			var ocultarInteract = document.getElementsByClassName('ocultarInteract');
			for (var x=0; x < ocultarInteract.length; x++) {
				ocultarInteract[x].hidden = false;
			}
			
			document.getElementById("botonInteract").innerHTML = "Siguiente";
			
			var editar = getUrlVars()["a"];
			
			if (editar == "editarCargos"){
				var club = document.getElementById('club');
				nombreClub = club.options[club.selectedIndex].text;
		
				if (nombreClub != 'Otro'){
					document.getElementById('rotaract').checked = true;
			
					var radios = document.getElementsByName('tipoRueda');
					for (var i = 0; i< radios.length;  i++){
				    	radios[i].disabled = true;
					}
				} else {
					document.getElementById('rotaract').checked = false;
			
					var radios = document.getElementsByName('tipoRueda');
					for (var i = 0; i< radios.length;  i++){
				    	radios[i].disabled = false;
					}
				}
			}
		} else if ($('input[name=tipoRueda]:checked').val() == 'Rotary'){
			var ocultar = document.getElementsByClassName('ocultar');
			for (var x=0; x < ocultar.length; x++) {
				ocultar[x].hidden = true;
			}
			
			var ocultarInteract = document.getElementsByClassName('ocultarInteract');
			for (var x=0; x < ocultarInteract.length; x++) {
				ocultarInteract[x].hidden = false;
			}
			
			document.getElementById("botonInteract").innerHTML = "Siguiente";
		}
	}
	
	function validarDatos(tipo, cantTipoE, paso){
		if (tipo == "editar"){
			paso = "p1";
		}
		
		switch (paso) {
    		case 'p1':
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
				}
				
				if (tipo == "editar"){
					validarDatos('fin', '0', 'p5');
				} else {
					return 1;
				}
		    case 'p2':
				if (tipo == "nuevo" || tipo == "editarCargos"){
					if ($("#distrito").attr("value")=="vacio"){
						alert("Tenés que seleccionar el distrito al que perteneces");
						return;
					} else if ($("#club").attr("value")=="vacio"){
						alert("Tenés que seleccionar el club al que perteneces");
						return;
					} else if ($("#fechaIng").attr("value").replace(/^\s*|\s*$/g,"")=="__/__/____"){
						alert("Tenés que ingresar tu fecha de ingreso al Club");
						return;
					} else if (!isValidDate($("#fechaIng").attr("value").replace(/^\s*|\s*$/g,""))){
						alert("La fecha de ingreso al Club ingresada no es válida");
						return;
					} else if ($('input[name=tipoRueda]:checked').val() == null){
						alert("Tenés que seleccionar la rueda a la que perteneces");
						return;
					} 
			
					if ($('input[name=tipoRueda]:checked').val() == 'Rotaract'){
						//Cargos pasados club
						var cargosPasadosC = document.getElementsByName("cargosPasadosClub[]");
		
						for (var x=0; x < cargosPasadosC.length; x++) {
							if (cargosPasadosC[x].checked) {
								var periodoCargosPasadosClub = document.getElementById("periodoClub" + cargosPasadosC[x].id);
						
								if (periodoCargosPasadosClub.selectedOptions.length == 0){
									alert("Tenés que seleccionar el período asociado a cada cargo por el que pasaste en tu Club");
									return;
								}		
							}
						}
					}
				}
				
				return 1;
		    case 'p3':
				if (tipo == "nuevo" || tipo == "editarCargos"){
					if ($('input[name=tipoRueda]:checked').val() == 'Rotaract'){
						//Cargos pasados distrito
					
						var cargosPasadosD = document.getElementsByName("cargosPasadosDistrito[]");
				
						for (var x=0; x < cargosPasadosD.length; x++) {
							if (cargosPasadosD[x].checked) {
								var periodoCargosPasadosDistrito = document.getElementById("periodoDist" + cargosPasadosD[x].id);
						
								if (periodoCargosPasadosDistrito.selectedOptions.length == 0){
									alert("Tenés que seleccionar el período asociado a cada cargo por el que pasaste en el Distrito");
									return;
								}
							}
						}
			
						//Eventos asistidos distrito y veces como instructor en cada uno
	
						for (var x=1; x <= cantTipoE; x++) {
							if (isNaN($("#tipoEvento" + x).attr("value").replace(/^\s*|\s*$/g,"")) || $("#tipoEvento" + x).attr("value").replace(/^\s*|\s*$/g,"")<0){
								alert("El valor de las casillas de asistencias a cada evento distrital deben ser numéricos");
								return;
							} else {
								if ($("#tipoEvento" + x).attr("value").replace(/^\s*|\s*$/g,"")=="") {
									alert("Tenés que ingresar la cantidad de veces que asististe a cada evento distrital");
									return;
								} else {
									if (isNaN($("#instructorD" + x).attr("value").replace(/^\s*|\s*$/g,"")) || $("#instructorD" + x).attr("value").replace(/^\s*|\s*$/g,"")<0){
										alert("El valor de las casillas de veces que fuiste instructor/orador en cada evento distrital deben ser numéricos");
										return;
									} else {
										if ($("#instructorD" + x).attr("value").replace(/^\s*|\s*$/g,"")=="") {
											alert("Tenés que ingresar la cantidad de veces que fuiste instructor/orador en cada evento distrital");
											return;
										} else if (parseInt($("#instructorD" + x).attr("value")) > parseInt($("#tipoEvento" + x).attr("value"))) {
											alert("La cantidad de asistencias a un evento no puede ser menor que la cantidad de veces que fuiste instructor/orador en un evento del mismo tipo");
											return;
										}
									}
								}
							}
						}
					}
				}
				
				return 1;
		    case 'p4':
				if (tipo == "nuevo" || tipo == "editarCargos"){
					if ($('input[name=tipoRueda]:checked').val() == 'Rotaract'){
						//Cargos pasados AIRAUP
						var cargosPasadosA = document.getElementsByName("cargosPasadosAIRAUP[]");
						var marcado = false; 
				
						for (var x=0; x < cargosPasadosA.length; x++) {
							if (cargosPasadosA[x].checked) {
								var periodoCargosPasadosAIRAUP = document.getElementById("periodoAIRAUP" + cargosPasadosA[x].id);
						
								if (periodoCargosPasadosAIRAUP.selectedOptions.length == 0){
									alert("Tenés que seleccionar el período asociado a cada cargo por el que pasaste en AIRAUP");
									return;
								}
							}
						}
			
						if ($("#eraups").attr("value").replace(/^\s*|\s*$/g,"")==""){
							alert("Tenés que ingresar la cantidad de asistencias a ERAUP");
							return;
						} 
					}
			
					if ($('input[name=tipoRueda]:checked').val() != 'Interact'){
						//Mesas pasadas ERAUP
				
						if (isNaN($("#eraups").attr("value").replace(/^\s*|\s*$/g,"")) || $("#eraups").attr("value").replace(/^\s*|\s*$/g,"")<0){
							alert("El valor de la casilla de asistencias al ERAUP debe ser numérico");
							return;
						} else {
							if ($("#eraups").attr("value").replace(/^\s*|\s*$/g,"")>0){
								var mesasPasadasE = document.getElementsByName("mesasPasadasERAUP[]");
								var marcadoMesa = false;
								contar = 0;
					
								for (var x=0; x < mesasPasadasE.length; x++) {
									if (mesasPasadasE[x].checked) {
										marcadoMesa = true;
										contar++;
									}
								}
								
								if (!marcadoMesa){
									alert("Tenés que marcar las mesas a las que asististe en el ERAUP");
									return;	
								} else if (contar > $("#eraups").attr("value").replace(/^\s*|\s*$/g,"")){
									alert("La cantidad de mesas a las que asististe en el ERAUP no puede ser mayor que la cantidad de asistencias");
									return;
								}
							}
						}
		
						if ($("#instructorERAUP").attr("value").replace(/^\s*|\s*$/g,"")==""){
							alert("Tenés que ingresar la cantidad de veces que fuiste instructor en un ERAUP");
							return;
						}
			
						//Mesas pasadas ERAUP como Instructor
		
						if (isNaN($("#instructorERAUP").attr("value").replace(/^\s*|\s*$/g,"")) || $("#instructorERAUP").attr("value").replace(/^\s*|\s*$/g,"")<0){
							alert("El valor de la casilla de asistencias como instructor al ERAUP debe ser numérico");
							return;
						} else {
							if ($("#instructorERAUP").attr("value").replace(/^\s*|\s*$/g,"")>0){
								var mesasPasadasI = document.getElementsByName("mesasPasadasInsERAUP[]");
								var marcadoMesaI = false;
								contar = 0;
							
								for (var x=0; x < mesasPasadasI.length; x++) {
									if (mesasPasadasI[x].checked) {
										marcadoMesaI = true;
										contar++;
									}
								}
						
								if (!marcadoMesaI){
									alert("Tenés que marcar las mesas a las que asististe como instructor en el ERAUP");
									return;	
								} else if (contar > $("#instructorERAUP").attr("value").replace(/^\s*|\s*$/g,"")){
									alert("La cantidad de mesas en las que fuiste instructor en el ERAUP no puede ser mayor que la cantidad de asistencias como instructor");
									return;
								}
							}
						}
					}
			
					if ($('input[name=tipoRueda]:checked').val() == 'Rotaract'){
						if ($("#asambleasA").attr("value").replace(/^\s*|\s*$/g,"")==""){
							alert("Tenés que ingresar la cantidad de Asambleas de AIRAUP a las que fuiste");
							return;
						} else if (isNaN($("#asambleasA").attr("value").replace(/^\s*|\s*$/g,"")) || $("#asambleasA").attr("value").replace(/^\s*|\s*$/g,"")<0){
							alert("El valor de la casilla de asistencias a Asambleas de AIRAUP debe ser numérico");
							return;
						}
					}
				}
				
				if (tipo == "editarCargos"){
					document.getElementById("club").disabled = false;
					document.getElementById("fechaIng").disabled = false;
			
					var radios = document.getElementsByName('tipoRueda');
					for (var i = 0; i< radios.length;  i++){
		    			radios[i].disabled = false;
					}
					
					document.registro.submit();
					break;
				} else {
					return 1;
				}
		    case 'p5':
				if ($("#obraSocial").attr("value").replace(/^\s*|\s*$/g,"")==""){
					alert("Tenés que ingresar tu obra social / sociedad médica");
					return;
				} else if ($("#grupoS").attr("value")=="vacio"){
					alert("Tenés que ingresar tu grupo sanguíneo");
					return;
				} else if ($('input[name=factor]:checked').val() == null){
					alert("Tenés que seleccionar tu factor sanguíneo");
					return;
				} else if ($('input[name=enfermedadC]:checked').val() == null){
					alert("Tenés que marcar si padeces de alguna enfermedad crónica o no");
					return;
				} else if ($('input[name=enfermedadC]:checked').val() == 1 && $("#enfermedadCE").attr("value").replace(/^\s*|\s*$/g,"")==""){
					alert("Tenés que especificar cuál es la enfermedad crónica que padeces");
					return;
				} else if ($('input[name=internacion]:checked').val() == null){
					alert("Tenés que marcar si fuiste internado alguna vez en los últimos 3 años o no");
					return;
				} else if ($('input[name=internacion]:checked').val() == 1 && $("#internacionE").attr("value").replace(/^\s*|\s*$/g,"")==""){
					alert("Tenés que especificar cuál fue el motivo de tu internación");
					return;
				} else if ($('input[name=enfermedadI]:checked').val() == null){
					alert("Tenés que marcar si padeces de alguna enfermedad infecto-contagiosa o no");
					return;
				} else if ($('input[name=enfermedadI]:checked').val() == 1 && $("#enfermedadIE").attr("value").replace(/^\s*|\s*$/g,"")==""){
					alert("Tenés que especificar cuál es la enfermedad infecto-contagiosa que padeces");
					return;
				} else if ($('input[name=intervencion]:checked').val() == null){
					alert("Tenés que marcar si te realizaron alguna intervención quirúrjica o no");
					return;
				} else if ($('input[name=intervencion]:checked').val() == 1 && $("#intervencionE").attr("value").replace(/^\s*|\s*$/g,"")==""){
					alert("Tenés que especificar cuál fue el motivo de la intervención");
					return;
				} else if ($('input[name=alergia]:checked').val() == null){
					alert("Tenés que marcar si tenes alguna alergia");
					return;
				} else if ($('input[name=alergia]:checked').val() == 1 && $("#alergiaE").attr("value").replace(/^\s*|\s*$/g,"")==""){
					alert("Tenés que especificar que alergias padeces");
					return;
				} else if ($('input[name=vegetariano]:checked').val() == null){
					alert("Tenés que marcar si sos vegetariano o no");
					return;
				} else if ($('input[name=fumador]:checked').val() == null){
					alert("Tenés que marcar si sos fumador o no");
					return;
				} else if ($('input[name=lateralidad]:checked').val() == null){
					alert("Tenés que indicar tu lateralidad");
					return;
				} else if ($('input[name=lentes]:checked').val() == null){
					alert("Tenés que marcar si utilizas lentes o no");
					return;
				} else if ($('input[name=audifonos]:checked').val() == null){
					alert("Tenés que marcar si utilizas audífonos o no");
					return;
				} else if ($('input[name=limitacion]:checked').val() == null){
					alert("Tenés que marcar si sufris de alguna limitación física o no");
					return;
				} else if ($('input[name=limitacion]:checked').val() == 1 && $("#limitacionE").attr("value").replace(/^\s*|\s*$/g,"")==""){
					alert("Tenés que especificar que limitación física padeces");
					return;
				} else if ($("#nomContacto").attr("value").replace(/^\s*|\s*$/g,"")==""){
					alert("Tenés que ingresar el nombre de la persona de contacto");
					return;
				} else if ($("#telContacto").attr("value").replace(/^\s*|\s*$/g,"")==""){
					alert("Tenés que ingresar el teléfono de la persona de contacto");
					return;
				} else if (buscarLetras($("#telContacto").attr("value").replace(/^\s*|\s*$/g,""))){
					alert("El valor de teléfono de tu contacto no puede contener letras");
					return;
				} else if ($("#relContacto").attr("value").replace(/^\s*|\s*$/g,"")==""){
					alert("Tenés que ingresar tu relación con la persona de contacto");
					return;
				}
		
				if (tipo == "nuevo"){
					document.getElementById("club").disabled = false;
					document.getElementById("fechaIng").disabled = false;
			
					var radios = document.getElementsByName('tipoRueda');
					for (var i = 0; i< radios.length;  i++){
		    			radios[i].disabled = false;
					}
				}

				document.registro.submit();
		}
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

    	// Check the ranges of month and year
	    if(year < 1000 || year > 3000 || month == 0 || month > 12)
    	    return false;

	    var monthLength = [ 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 ];

	    // Adjust for leap years
    	if(year % 400 == 0 || (year % 100 != 0 && year % 4 == 0))
	        monthLength[1] = 29;
	
	    // Check the range of the day
    	return day > 0 && day <= monthLength[month - 1];
	};