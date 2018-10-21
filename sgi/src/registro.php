<?php
	ini_set("display_errors", 0);
	include("config.php");
	require_once("conexionDB.php");
	require 'class.phpmailer.php';
	require 'class.smtp.php';
	session_start(); //Se inicia la sesión
	$obj_con=new conectar;
	
	require_once("class.TemplatePower.inc.php"); //Usando Template Power
	
	date_default_timezone_set('America/Argentina/Buenos_Aires');
	
	$tpl=new TemplatePower("registro.html");
 	$tpl->prepare();
	
	$conexion= new ConexionDB($obj_con->getServ(),$obj_con->getBase(),$obj_con->getUsr(),$obj_con->getPass());
	$conexion2= new ConexionDB($obj_con->getServ(),$obj_con->getBase(),$obj_con->getUsr(),$obj_con->getPass());
	$conexion3= new ConexionDB($obj_con->getServ(),$obj_con->getBase(),$obj_con->getUsr(),$obj_con->getPass());
	
	$accion=$_POST['accion'];
	
	if ($accion == ""){
		$accion=$_GET['a'];
	}
	
	$idSocio = $_SESSION['usuario'];
	
	if ($accion == "e"){ //Editar datos personales
		if ($idSocio != ""){
			$conexion->Ejecuto("select Nombres, Apellidos, Documento, Direccion, Ciudad, Sexo, Facebook, ViveCon, Hospeda, FechaNac, Email, Telefono, AreaEstudio, Trabajo, NombreContacto, TelefonoContacto, RelacionContacto from socio where idSocio=" . $idSocio);
			$datosSocio=$conexion->Siguiente();
		
			//Bloque datos personales
			$tpl->newBlock("personales");
			$tpl->assign("nombres", $datosSocio['Nombres']);
			$tpl->assign("apellidos", $datosSocio['Apellidos']);
			$tpl->assign("documento", $datosSocio['Documento']);
			$tpl->assign("direccion", $datosSocio['Direccion']);
			$tpl->assign("ciudad", $datosSocio['Ciudad']);
			$tpl->assign("viveCon", $datosSocio['ViveCon']);
			
			if ($datosSocio['Hospeda'] == "1"){
				$tpl->assign("checkedhospeda1", "checked='checked'"); 
			} else {
				$tpl->assign("checkedhospeda2", "checked='checked'"); 
			}

			$fechaNac = split("-", $datosSocio['FechaNac']);
			
			$tpl->assign("fechaNac", $fechaNac[2] . "/" . $fechaNac[1] . "/" . $fechaNac[0]);
			
			if ($datosSocio['Sexo'] == "1"){
				$tpl->assign("checkedsexo1", "checked='checked'"); 
			} else if ($datosSocio['Sexo'] == "2"){
				$tpl->assign("checkedsexo2", "checked='checked'"); 
			}
			
			$tpl->assign("mail", $datosSocio['Email']);
			$tpl->assign("telefono", $datosSocio['Telefono']);
			$tpl->assign("ocupacion", $datosSocio['AreaEstudio']);
			$tpl->assign("trabajo", $datosSocio['Trabajo']);
			$tpl->assign("facebook", $datosSocio['Facebook']);
		
			//Bloque datos médicos
			$conexion->Ejecuto("select ObraSocial, NumeroSocio, GrupoSangre, Factor, EnfermedadCronica, EnfermedadCronicaE, Internacion3anos, Internacion3anosE, EnfermedadInfecciosa, EnfermedadInfecciosaE, IntervencionQuirurjica, IntervencionQuirurjicaE, Alergia, AlergiaE, Vegetariano, Dieta, Fuma, Lateralidad, Lentes, Audifonos, LimitacionFisica, LimitacionFisicaE, DonanteOrganos, DonanteMedula, NombreMedicamento, Droga, CantidadMedicamento from datosmedicos where idSocio=" . $idSocio);
			$datosMedicos=$conexion->Siguiente();
			
			$tpl->newBlock("medicos");
			$tpl->assign("obraSocial", $datosMedicos['ObraSocial']);
			$tpl->assign("numSocio", $datosMedicos['NumeroSocio']);
			
			if ($datosMedicos['GrupoSangre'] == "A"){
				$tpl->assign("seleccionadoA", "selected='selected'"); 
			} else if ($datosMedicos['GrupoSangre'] == "B"){
				$tpl->assign("seleccionadoB", "selected='selected'"); 
			} else if ($datosMedicos['GrupoSangre'] == "O"){
				$tpl->assign("seleccionadoO", "selected='selected'"); 
			} else if ($datosMedicos['GrupoSangre'] == "AB"){
				$tpl->assign("seleccionadoAB", "selected='selected'"); 
			}
			
			if ($datosMedicos['Factor'] == "1"){
				$tpl->assign("checkedfactor1", "checked='checked'"); 
			} else {
				$tpl->assign("checkedfactor2", "checked='checked'"); 
			}
			
			if ($datosMedicos['EnfermedadCronica'] == "1"){
				$tpl->assign("checkedenfermedadC1", "checked='checked'"); 
			} else {
				$tpl->assign("checkedenfermedadC2", "checked='checked'"); 
			}
			
			$tpl->assign("enfermedadCE", $datosMedicos['EnfermedadCronicaE']);
			
			if ($datosMedicos['Internacion3anos'] == "1"){
				$tpl->assign("checkedinternacion1", "checked='checked'"); 
			} else {
				$tpl->assign("checkedinternacion2", "checked='checked'"); 
			}
			
			$tpl->assign("internacionE", $datosMedicos['Internacion3anosE']);
			
			if ($datosMedicos['EnfermedadInfecciosa'] == "1"){
				$tpl->assign("checkedenfermedadI1", "checked='checked'"); 
			} else {
				$tpl->assign("checkedenfermedadI2", "checked='checked'"); 
			}
			
			$tpl->assign("enfermedadIE", $datosMedicos['EnfermedadInfecciosaE']);
			
			if ($datosMedicos['IntervencionQuirurjica'] == "1"){
				$tpl->assign("checkedintervencion1", "checked='checked'"); 
			} else {
				$tpl->assign("checkedintervencion2", "checked='checked'"); 
			}
			
			$tpl->assign("intervencionE", $datosMedicos['IntervencionQuirurjicaE']);
			
			if ($datosMedicos['Alergia'] == "1"){
				$tpl->assign("checkedalergia1", "checked='checked'"); 
			} else {
				$tpl->assign("checkedalergia2", "checked='checked'"); 
			}
			
			$tpl->assign("alergiaE", $datosMedicos['AlergiaE']);
			
			if ($datosMedicos['Vegetariano'] == "1"){
				$tpl->assign("checkedvegetariano1", "checked='checked'"); 
			} else {
				$tpl->assign("checkedvegetariano2", "checked='checked'"); 
			}
			
			if ($datosMedicos['Fuma'] == "1"){
				$tpl->assign("checkedfumador1", "checked='checked'"); 
			} else {
				$tpl->assign("checkedfumador2", "checked='checked'"); 
			}
			
			if ($datosMedicos['Lateralidad'] == "1"){
				$tpl->assign("checkedlateralidad1", "checked='checked'"); 
			} else {
				$tpl->assign("checkedlateralidad2", "checked='checked'"); 
			}
			
			if ($datosMedicos['Lentes'] == "1"){
				$tpl->assign("checkedlentes1", "checked='checked'"); 
			} else {
				$tpl->assign("checkedlentes2", "checked='checked'"); 
			}
			
			if ($datosMedicos['Audifonos'] == "1"){
				$tpl->assign("checkedaudifonos1", "checked='checked'"); 
			} else {
				$tpl->assign("checkedaudifonos2", "checked='checked'"); 
			}
			
			if ($datosMedicos['LimitacionFisica'] == "1"){
				$tpl->assign("checkedlimitacion1", "checked='checked'"); 
			} else {
				$tpl->assign("checkedlimitacion2", "checked='checked'"); 
			}
			
			$tpl->assign("limitacionE", $datosMedicos['LimitacionFisicaE']);
			
			if ($datosMedicos['DonanteOrganos'] == "1"){
				$tpl->assign("checkeddonanteorganos1", "checked='checked'"); 
			} else {
				$tpl->assign("checkeddonanteorganos2", "checked='checked'"); 
			}
			
			if ($datosMedicos['DonanteMedula'] == "1"){
				$tpl->assign("checkeddonantemedula1", "checked='checked'"); 
			} else {
				$tpl->assign("checkeddonantemedula2", "checked='checked'"); 
			}
			
			$tpl->assign("dieta", $datosMedicos['Dieta']);

			$tpl->assign("nombreMed", $datosMedicos['NombreMedicamento']);
			$tpl->assign("monodroga", $datosMedicos['Droga']);
			$tpl->assign("cantidadMed", $datosMedicos['CantidadMedicamento']);
		
			$tpl->assign("nomContacto", $datosSocio['NombreContacto']);
			$tpl->assign("telContacto", $datosSocio['TelefonoContacto']);
			$tpl->assign("relContacto", $datosSocio['RelacionContacto']);
		
			$tpl->newBlock("accion");
			$tpl->assign("tipo", "editar");
		} else {
			header('Location: login.php');
		}
	} else if ($accion == "editarCargos"){
		$idSocio = $_GET['idS'];
		
		$tpl->newBlock("cargaClubes");
		$tpl->assign("idSocio", $idSocio);
		
		$conexion->Ejecuto("select s.Nombres, s.Apellidos, s.Email, s.idClub, s.FechaIngreso, t.Nombre as 'TipoRueda', d.idDistrito from socio s, distrito d, club c, tiporueda t where s.idSocio=" . $idSocio . " and d.idDistrito=c.idDistrito and s.idClub=c.idClub and t.idTipoRueda=s.idTipoRueda");
		$socio=$conexion->Siguiente();
		
		$tpl->newBlock("club");
		
		//Cargo fecha de ingreso y rueda
		$fechaIng = split("-", $socio['FechaIngreso']);
		$tpl->assign("fechaIng", $fechaIng[2] . "/" . $fechaIng[1] . "/" . $fechaIng[0]);
		
		switch ($socio['TipoRueda']) {
		    case "Interact":
        		$tpl->assign("checkedInteract", "checked='checked'");
	        	break;
    		case "Rotaract":
		        $tpl->assign("checkedRotaract", "checked='checked'");
        		break;
		    case "Rotary":
		        $tpl->assign("checkedRotary", "checked='checked'");
		        break;
		}
		
		$tpl->newBlock("correoEditarCargos");
		$tpl->assign("correo", $socio['Email']);
		$tpl->assign("nombreCompleto", $socio['Nombres'] . " " . $socio['Apellidos']);
		
		//Cargo Distritos
		$conexion->Ejecuto("select idDistrito, Nombre from distrito order by Nombre ASC");
			
		while($distritos=$conexion->Siguiente()){
			$tpl->newBlock("comboDistrito");
			$tpl->assign("valor", $distritos['idDistrito']);
			$tpl->assign("opcion", $distritos['Nombre']);
			
			if ($distritos['idDistrito'] == $socio['idDistrito']){
				$tpl->assign("seleccionado", "selected='selected'");
			}
		}
		
		//Cargo los cargos de Club
		$conexion->Ejecuto("select idCargoClub, Nombre from cargoclub");
		
		while($cargoClub=$conexion->Siguiente()){
			$conexion2->Ejecuto("select idCargoClub, idPeriodo from historialcargoclub where idSocio=" . $idSocio);
			
			$tpl->newBlock("checkCargoClub");
			$tpl->assign("idCargo", $cargoClub['idCargoClub']);
			$tpl->assign("cargo", $cargoClub['Nombre']);
			
			while($histCargoClub=$conexion2->Siguiente()){
				if ($cargoClub['idCargoClub'] == $histCargoClub['idCargoClub']){
					$tpl->assign("checkedCargoClub", "checked='checked'");
					break;
				}
			}
		
			cargarPeriodos($conexion2, $conexion3, $tpl, "comboPeriodoClub", $idSocio, $histCargoClub['idCargoClub'], "historialcargoclub", "idCargoClub");
		}
		
		$tpl->newBlock("distrito");
		
		//Cargo los cargos de Distrito
		$conexion->Ejecuto("select idCargoDistrito, Nombre from cargodistrito");
		
		while($cargoDistrito=$conexion->Siguiente()){
			$conexion2->Ejecuto("select idCargoDistrito, idPeriodo from historialcargodistrito where idSocio=" . $idSocio);
			
			$tpl->newBlock("checkCargoDistrito");
			$tpl->assign("idCargo", $cargoDistrito['idCargoDistrito']);
			$tpl->assign("cargo", $cargoDistrito['Nombre']);
			
			while($histCargoDistrito=$conexion2->Siguiente()){
				if ($cargoDistrito['idCargoDistrito'] == $histCargoDistrito['idCargoDistrito']){
					$tpl->assign("checkedCargoDistrito", "checked='checked'");
					break;
				}
			}
				
			cargarPeriodos($conexion2, $conexion3, $tpl, "comboPeriodoDistrito", $idSocio, $histCargoDistrito['idCargoDistrito'], "historialcargodistrito", "idCargoDistrito");
		}
	
		//Cargo los eventos de Distrito
		$conexion->Ejecuto("select idTipoEvento, Nombre from tipoevento where Tipo=0 order by Nombre ASC");
		$cantTipoEvento=0;
		
		while($eventoDistrito=$conexion->Siguiente()){
			$cantTipoEvento++;
			$tpl->newBlock("tipoEvento");
			$tpl->assign("numTipoEvento", $cantTipoEvento);
			$tpl->assign("tipoEvento", $eventoDistrito['Nombre']);
			
			$conexion2->Ejecuto("select CantidadAsistencias, VecesInstructor from historialevento where idSocio=" . $idSocio . " and idTipoEvento=" . $eventoDistrito['idTipoEvento']);
			$histTipoEvento=$conexion2->Siguiente();
			
			$tpl->assign("vecesTipoEvento", $histTipoEvento['CantidadAsistencias']);
			$tpl->assign("vecesInstTipoEvento", $histTipoEvento['VecesInstructor']);
		}
		
		$tpl->newBlock("airaup");
		
		//Cargo ERAUPs
		$conexion->Ejecuto("select h.CantidadAsistencias, h.VecesInstructor from historialevento h, tipoevento t where h.idSocio=" . $idSocio . " and t.idTipoEvento=h.idTipoEvento and t.Tipo=1 and t.Nombre='E.R.A.U.P.'");
		$cantEraups=$conexion->Siguiente();
		
		$tpl->assign("asistERAUP", $cantEraups['CantidadAsistencias']);
		$tpl->assign("asistInstERAUP", $cantEraups['VecesInstructor']);
		
		//Cargo Asambleas
		$conexion->Ejecuto("select h.CantidadAsistencias from historialevento h, tipoevento t where h.idSocio=" . $idSocio . " and t.idTipoEvento=h.idTipoEvento and t.Tipo=1 and t.Nombre='Asamblea A.I.R.A.U.P.'");
		$cantAsambleas=$conexion->Siguiente();
		
		$tpl->assign("vecesAsamblea", $cantAsambleas['CantidadAsistencias']);
		
		//Cargo los cargos de AIRAUP
		$conexion->Ejecuto("select idCargoAIRAUP, Nombre from cargoairaup");
		
		while($cargoAIRAUP=$conexion->Siguiente()){
			$conexion2->Ejecuto("select idCargoAIRAUP, idPeriodo from historialcargoairaup where idSocio=" . $idSocio);
			
			$tpl->newBlock("checkCargoAIRAUP");
			$tpl->assign("idCargo", $cargoAIRAUP['idCargoAIRAUP']);
			$tpl->assign("cargo", $cargoAIRAUP['Nombre']);
			
			while($histCargoAIRAUP=$conexion2->Siguiente()){
				if ($cargoAIRAUP['idCargoAIRAUP'] == $histCargoAIRAUP['idCargoAIRAUP']){
					$tpl->assign("checkedCargoAIRAUP", "checked='checked'");
					break;
				}
			}
			
			cargarPeriodos($conexion2, $conexion3, $tpl, "comboPeriodoAIRAUP", $idSocio, $histCargoAIRAUP['idCargoAIRAUP'], "historialcargoairaup", "idCargoAIRAUP");
		}
	
		//Cargo mesas de ERAUP
		$conexion->Ejecuto("select idCalidadAsistencia, Nombre from calidadasistenciaevento where Nombre not in ('Sin definir')");
		
		while($calidadAsistencia=$conexion->Siguiente()){
			$asistio = false;
			$instruc = false;
			
			$conexion2->Ejecuto("select Mesa from historialmesaeraup where idSocio=" . $idSocio . " and Instructor=0");
			
			while($histMesaERAUP=$conexion2->Siguiente()){
				if ($calidadAsistencia['Nombre'] == $histMesaERAUP['Mesa']){
					$asistio = true;
					break;
				}
			}
			
			if ($calidadAsistencia['Nombre'] != "Instructor"){
				$tpl->newBlock("checkMesasERAUP");
				$tpl->assign("idCalidad", $calidadAsistencia['idCalidadAsistencia']);
				$tpl->assign("calidadAsistente", $calidadAsistencia['Nombre']);
				
				if ($asistio){
					$tpl->assign("checkedMesaERAUP", "checked='checked'");	
				}
			}
			
			$conexion2->Ejecuto("select Mesa from historialmesaeraup where idSocio=" . $idSocio . " and Instructor=1");
			
			while($histMesaERAUP=$conexion2->Siguiente()){
				if ($calidadAsistencia['Nombre'] == $histMesaERAUP['Mesa']){
					$instruc = true;
					break;
				}
			}
		
			if ($calidadAsistencia['Nombre'] != "Instructor" && $calidadAsistencia['Nombre'] != "Equipo AIRAUP" && $calidadAsistencia['Nombre'] != "Organizador"){
				$tpl->newBlock("checkMesasInsERAUP");
				$tpl->assign("idCalidad", $calidadAsistencia['idCalidadAsistencia']);
				$tpl->assign("calidadInstructor", $calidadAsistencia['Nombre']);
				
				if ($instruc){
					$tpl->assign("checkedInstMesaERAUP", "checked='checked'");
				}
			}
		}
		
		$tpl->newBlock("finalizarECargos");
		$tpl->assign("tipo", "editarCargos");
		$tpl->assign("cantTipoEvento", $cantTipoEvento);
		$tpl->assign("idSocio", $idSocio);
		
		$tpl->newBlock("medicos");
		
		$tpl->newBlock("accion");
		$tpl->assign("tipo", "editarCargos");
		$tpl->assign("cantTipoEvento", $cantTipoEvento);
	} else if ($accion == "aceptar"){
		$editarCargos = $_POST['accionE'];
		
		if ($editarCargos == "editarCargos"){
			$idSocio = $_POST['idSocioE'];
		} else {
			$idSocio = $_SESSION['usuario'];
		}
		
		if ($editarCargos != "editarCargos"){
			//Proceso datos recibidos
			$nombres=quitarCaracteres($_POST['nombres']);
			$apellidos=quitarCaracteres($_POST['apellidos']);
			$documento=$_POST['documento'];
			$direccionF=quitarCaracteres($_POST['direccion']);
			$ciudad=quitarCaracteres($_POST['ciudad']);
			$viveCon=quitarCaracteres($_POST['viveCon']);
			$hospeda=$_POST['hospeda'];
		
			if ($hospeda == 'undefined' || $hospeda == ''){
				$hospeda = 'NULL';
			}
		
			$fechaNac=$_POST['fechaNac'];
			$sexo=$_POST['sexo'];
		
			if ($sexo == 'undefined' || $sexo == ''){
				$sexo = 'NULL';
			}
		
			$direccion=$_POST['mail'];
			$telefono=$_POST['telefono'];
			$ocupacion=quitarCaracteres($_POST['ocupacion']);
			$trabajo=quitarCaracteres($_POST['trabajo']);
			$facebook=$_POST['facebook'];
			
			$obraSocial=quitarCaracteres($_POST['obraSocial']);
			$numSocio=quitarCaracteres($_POST['numSocio']);
			$grupoS=$_POST['grupoS'];
			$factor=$_POST['factor'];
			$enfermedadC=$_POST['enfermedadC'];
			$enfermedadCE=quitarCaracteres($_POST['enfermedadCE']);
			$internacion=$_POST['internacion'];
			$internacionE=quitarCaracteres($_POST['internacionE']);
			$enfermedadI=$_POST['enfermedadI'];
			$enfermedadIE=quitarCaracteres($_POST['enfermedadIE']);
			$intervencion=$_POST['intervencion'];
			$intervencionE=quitarCaracteres($_POST['intervencionE']);
			$alergia=$_POST['alergia'];
			$alergiaE=quitarCaracteres($_POST['alergiaE']);
			$vegetariano=$_POST['vegetariano'];
			$dieta=quitarCaracteres($_POST['dieta']);
			$fumador=$_POST['fumador'];
			$lateralidad=$_POST['lateralidad'];
			$lentes=$_POST['lentes'];
			$audifonos=$_POST['audifonos'];
			$limitacion=$_POST['limitacion'];
			$limitacionE=quitarCaracteres($_POST['limitacionE']);
			$donanteOrganos=$_POST['donanteOrganos'];
			
			if ($donanteOrganos == 'undefined' || $donanteOrganos == ''){
				$donanteOrganos = 'NULL';
			}
			
			$donanteMedula=$_POST['donanteMedula'];
			
			if ($donanteMedula == 'undefined' || $donanteMedula == ''){
				$donanteMedula = 'NULL';
			}
			
			$nombreMed=quitarCaracteres($_POST['nombreMed']);
			$monodroga=quitarCaracteres($_POST['monodroga']);
			$cantidadMed=quitarCaracteres($_POST['cantidadMed']);
			
			$nomContacto=quitarCaracteres($_POST['nomContacto']);
			$telContacto=$_POST['telContacto'];
			$relContacto=quitarCaracteres($_POST['relContacto']);
		} else {
			$correo=$_POST['correo'];
		}
		
		if ($idSocio == ""){ //Es nuevo, hay datos que se piden solo en un registro nuevo
			$idTransaccion=$_POST['transaccion'];
			$distrito=$_POST['distrito'];
			$club=$_POST['club'];
			$fechaIng=$_POST['fechaIng'];
			$tipoRueda=$_POST['tipoRueda'];
			$eraups=$_POST['eraups'];
			$instructorERAUP=$_POST['instructorERAUP'];
			$asambleasA=$_POST['asambleasA'];
			
			$conexion->Ejecuto("select idTipoRueda from tiporueda where Nombre='" . $tipoRueda . "'");
			$rueda=$conexion->Siguiente();
			
			$fechaI = split("/", $fechaIng);
			$fechaN = split("/", $fechaNac);
			
			$conexion->Ejecuto("select * from preregistro where idTransaccion=" . $idTransaccion);
			$preregistro=$conexion->Siguiente();
			
			if ($preregistro['idPreRegistro'] != ""){
				$fechaN = str_replace("-", "", $preregistro['FechaNac']);
				
				if ($preregistro['Hospeda'] == 'NULL' || $preregistro['Hospeda'] == ""){
					$hospeda = 'NULL';
				} else {
					$hospeda = $preregistro['Hospeda'];
				}
				
				if ($preregistro['Sexo'] == 'NULL' || $preregistro['Sexo'] == ""){
					$sexo = 'NULL';
				} else {
					$sexo = $preregistro['Sexo'];
				}
				
				$conexion->Ejecuto("insert into socio (Nombres,Apellidos,Documento,Direccion,Ciudad,ViveCon,Hospeda,FechaNac,Sexo,Email,Password,Telefono,idClub,FechaIngreso,idTipoRueda,AreaEstudio,Trabajo,Facebook,NombreContacto,TelefonoContacto,RelacionContacto,Admin,Activo,FechaRegistro) values ('" . $preregistro['Nombres'] . "','" . $preregistro['Apellidos'] . "'," . $preregistro['Documento'] . ",'" . $preregistro['Direccion'] . "','" . $preregistro['Ciudad'] . "','" . $preregistro['ViveCon'] . "'," . $hospeda . ",'" . $fechaN . "'," . $sexo . ",'" . $preregistro['Email'] . "','" . $preregistro['Password'] . "','" . $preregistro['Telefono'] . "'," . $club . ",'" . $fechaI[2] . $fechaI[1] . $fechaI[0] . "'," . $rueda['idTipoRueda'] . ",'" . $preregistro['AreaEstudio'] . "','" . $preregistro['Trabajo'] . "','" . $preregistro['Facebook'] . "','" . $nomContacto . "','" . $telContacto . "','" . $relContacto . "',0,1,'" . date("Y-m-d H:i:s") . "')");
				
				//Obtener ID
				$conexion->Ejecuto("select idSocio from socio where Email='" . $preregistro['Email'] . "'");
				$obtenerID=$conexion->Siguiente();
				$idSocio=$obtenerID['idSocio'];
				
				if ($idSocio != ""){
					//Inserto datos medicos
					$conexion->Ejecuto("insert into datosmedicos (idSocio, ObraSocial,NumeroSocio,GrupoSangre,Factor,EnfermedadCronica,EnfermedadCronicaE,Internacion3anos,Internacion3anosE,EnfermedadInfecciosa,EnfermedadInfecciosaE,IntervencionQuirurjica,IntervencionQuirurjicaE,Alergia,AlergiaE,Vegetariano,Dieta,Fuma,Lateralidad,Lentes,Audifonos,LimitacionFisica,LimitacionFisicaE,DonanteOrganos,DonanteMedula,NombreMedicamento,Droga,CantidadMedicamento) values (" . $idSocio . ",'" . $obraSocial . "','" .$numSocio . "','" . $grupoS . "','" . $factor . "'," . $enfermedadC . ",'" . $enfermedadCE . "'," . $internacion . ",'" . $internacionE . "'," . $enfermedadI . ",'" . $enfermedadIE . "'," . $intervencion . ",'" . $intervencionE . "'," . $alergia . ",'" . $alergiaE . "'," . $vegetariano . ",'" . $dieta . "'," . $fumador . "," . $lateralidad . "," . $lentes . "," . $audifonos . "," . $limitacion . ",'" . $limitacionE .  "'," . $donanteOrganos . "," . $donanteMedula . ",'" . $nombreMed . "','" . $monodroga . "','" . $cantidadMed . "')");
					
					//Inserto cargos club historico
					foreach($_POST['cargosPasadosClub'] as $check) {
						foreach($_POST['periodoClub' . $check] as $check2) {
							$conexion->Ejecuto("insert into historialcargoclub (idSocio, idCargoClub, idPeriodo) values (" . $idSocio . "," . $check . "," . $check2 . ")");
						}
					}
					
					//Inserto cargos distrito historico
					foreach($_POST['cargosPasadosDistrito'] as $check) {
						foreach($_POST['periodoDist' . $check] as $check2) {
							$conexion->Ejecuto("insert into historialcargodistrito (idSocio, idCargoDistrito, idPeriodo) values (" . $idSocio . "," . $check . "," . $check2 . ")");
						}
					}
					
					//Inserto cargos airaup historico
					foreach($_POST['cargosPasadosAIRAUP'] as $check) {
						foreach($_POST['periodoAIRAUP' . $check] as $check2) {
							$conexion->Ejecuto("insert into historialcargoairaup (idSocio, idCargoAIRAUP, idPeriodo) values (" . $idSocio . "," . $check . "," . $check2 . ")");
						}
					}
			
					//Inserto mesas asistidas ERAUP historico
					foreach($_POST['mesasPasadasERAUP'] as $check) {
						$conexion->Ejecuto("insert into historialmesaeraup (Mesa, idSocio, Instructor) values ('" . $check . "'," . $idSocio . ",0)");
					}
			
					//Inserto mesas asistidas ERAUP historico en las que fue instructor
					foreach($_POST['mesasPasadasInsERAUP'] as $check) {
						$conexion->Ejecuto("insert into historialmesaeraup (Mesa, idSocio, Instructor) values ('" . $check . "'," . $idSocio . ",1)");
					}
			
					$conexion->Ejecuto("select idTipoEvento from tipoevento where Nombre='E.R.A.U.P.'");
					$idERAUP=$conexion->Siguiente();
				
					//Inserto cantidad de asistencias a ERAUP y veces que fue instructor
					$conexion->Ejecuto("insert into historialevento (idSocio, idTipoEvento, CantidadAsistencias, VecesInstructor) values (" . $idSocio . "," . $idERAUP['idTipoEvento'] . "," . $eraups . "," . $instructorERAUP . ")");
			
					$conexion->Ejecuto("select idTipoEvento from tipoevento where Nombre='Asamblea A.I.R.A.U.P.'");
					$idAsamblea=$conexion->Siguiente();
			
					//Inserto cantidad de asambleas y conferencias de AIRAUP a las que asistió
					$conexion->Ejecuto("insert into historialevento (idSocio, idTipoEvento, CantidadAsistencias, VecesInstructor) values (" . $idSocio . "," . $idAsamblea['idTipoEvento'] . "," . $asambleasA . ",0)");
			
					//Inserto registro historico de asistencias a cada evento distrital
					$conexion->Ejecuto("select idTipoEvento from tipoevento where Tipo=0 order by Nombre ASC");
					$contar = 1;
				
					while($tipoEventos=$conexion->Siguiente()){
						$conexion2->Ejecuto("insert into historialevento (idSocio, idTipoEvento, CantidadAsistencias, VecesInstructor) values (" . $idSocio . "," . $tipoEventos['idTipoEvento'] . "," . $_POST['tipoEvento' . $contar] . "," . $_POST['instructorD' . $contar] . ")");
				
						$contar++;
					}
					
					$conexion->Ejecuto("delete from preregistro where idTransaccion=" . $idTransaccion);
					
					$_SESSION['usuario'] = $idSocio;
					header('Location: perfil.php?a=p');
				} else {
					$tpl->newBlock("mensaje");
					$tpl->assign("mensaje", utf8_encode("Ocurrió un error al procesar tu registro, por favor contactate con un administrador del sistema."));
				}
			} else {
				$tpl->newBlock("mensaje");
				$tpl->assign("mensaje", utf8_encode("No se pudo procesar tu registro dado que la transacción de preregistro no es válida. Por favor contactate con un administrador del sistema."));
			}
		} else {
			if ($editarCargos == "editarCargos"){
				$club=$_POST['club'];
				$fechaIng=$_POST['fechaIng'];
				$tipoRueda=$_POST['tipoRueda'];
				$eraups=$_POST['eraups'];
				$instructorERAUP=$_POST['instructorERAUP'];
				$asambleasA=$_POST['asambleasA'];
				
				$conexion->Ejecuto("select idTipoRueda from tiporueda where Nombre='" . $tipoRueda . "'");
				$rueda=$conexion->Siguiente();
			
				$fechaI = split("/", $fechaIng);
				
				//Actualizo datos varios del Socio
				$conexion->Ejecuto("update socio set Email='" . $correo . "', idClub=" . $club . ", FechaIngreso='" . $fechaI[2] . $fechaI[1] . $fechaI[0] . "', idTipoRueda=" . $rueda['idTipoRueda'] . " where idSocio=" . $idSocio);
				
				//Elimino registros de cargos y asistencias a mesa previo a insertar la información recibida
				$conexion->Ejecuto("delete from historialcargoclub where idSocio=" . $idSocio);
				$conexion->Ejecuto("delete from historialcargodistrito where idSocio=" . $idSocio);
				$conexion->Ejecuto("delete from historialcargoairaup where idSocio=" . $idSocio);
				$conexion->Ejecuto("delete from historialmesaeraup where idSocio=" . $idSocio);
				$conexion->Ejecuto("delete from historialevento where idSocio=" . $idSocio);
				
				//Inserto cargos club historico
				foreach($_POST['cargosPasadosClub'] as $check) {
					foreach($_POST['periodoClub' . $check] as $check2) {
						$conexion->Ejecuto("insert into historialcargoclub (idSocio, idCargoClub, idPeriodo) values (" . $idSocio . "," . $check . "," . $check2 . ")");
					}
				}
					
				//Inserto cargos distrito historico
				foreach($_POST['cargosPasadosDistrito'] as $check) {
					foreach($_POST['periodoDist' . $check] as $check2) {
						$conexion->Ejecuto("insert into historialcargodistrito (idSocio, idCargoDistrito, idPeriodo) values (" . $idSocio . "," . $check . "," . $check2 . ")");
					}
				}
					
				//Inserto cargos airaup historico
				foreach($_POST['cargosPasadosAIRAUP'] as $check) {
					foreach($_POST['periodoAIRAUP' . $check] as $check2) {
						$conexion->Ejecuto("insert into historialcargoairaup (idSocio, idCargoAIRAUP, idPeriodo) values (" . $idSocio . "," . $check . "," . $check2 . ")");
					}
				}
			
				//Inserto mesas asistidas ERAUP historico
				foreach($_POST['mesasPasadasERAUP'] as $check) {
					$conexion->Ejecuto("insert into historialmesaeraup (Mesa, idSocio, Instructor) values ('" . $check . "'," . $idSocio . ",0)");
				}
			
				//Inserto mesas asistidas ERAUP historico en las que fue instructor
				foreach($_POST['mesasPasadasInsERAUP'] as $check) {
					$conexion->Ejecuto("insert into historialmesaeraup (Mesa, idSocio, Instructor) values ('" . $check . "'," . $idSocio . ",1)");
				}
			
				$conexion->Ejecuto("select idTipoEvento from tipoevento where Nombre='E.R.A.U.P.'");
				$idERAUP=$conexion->Siguiente();
			
				//Inserto cantidad de asistencias a ERAUP y veces que fue instructor
				$conexion->Ejecuto("insert into historialevento (idSocio, idTipoEvento, CantidadAsistencias, VecesInstructor) values (" . $idSocio . "," . $idERAUP['idTipoEvento'] . "," . $eraups . "," . $instructorERAUP . ")");
		
				$conexion->Ejecuto("select idTipoEvento from tipoevento where Nombre='Asamblea A.I.R.A.U.P.'");
				$idAsamblea=$conexion->Siguiente();
			
				//Inserto cantidad de asambleas y conferencias de AIRAUP a las que asistió
				$conexion->Ejecuto("insert into historialevento (idSocio, idTipoEvento, CantidadAsistencias, VecesInstructor) values (" . $idSocio . "," . $idAsamblea['idTipoEvento'] . "," . $asambleasA . ",0)");
		
				//Inserto registro historico de asistencias a cada evento distrital
				$conexion->Ejecuto("select idTipoEvento from tipoevento where Tipo=0 order by Nombre ASC");
				$contar = 1;
			
				while($tipoEventos=$conexion->Siguiente()){
					$conexion2->Ejecuto("insert into historialevento (idSocio, idTipoEvento, CantidadAsistencias, VecesInstructor) values (" . $idSocio . "," . $tipoEventos['idTipoEvento'] . "," . $_POST['tipoEvento' . $contar] . "," . $_POST['instructorD' . $contar] . ")");			
					$contar++;
				}
				
				header('Location: cuadrosocial.php');
			} else {
				$fechaN = split("/", $fechaNac);
			
				//Editando perfil, actualizo datos personales
				$conexion->Ejecuto("update socio set Nombres='" . $nombres . "',Apellidos='" . $apellidos ."',Documento=" . $documento . ",Direccion='" . $direccionF . "',Ciudad='" . $ciudad . "',ViveCon='" . $viveCon . "',Hospeda=" . $hospeda . ",FechaNac='" . $fechaN[2] . $fechaN[1] . $fechaN[0] . "',Sexo=" . $sexo . ",Email='" . $direccion . "',Telefono='" . $telefono . "',AreaEstudio='" . $ocupacion . "',Trabajo='" . $trabajo ."',Facebook='" . $facebook . "',NombreContacto='" . $nomContacto . "',TelefonoContacto='" . $telContacto . "',RelacionContacto='" . $relContacto . "' where idSocio=" . $idSocio);
				
				//Actualizo datos médicos
				$conexion->Ejecuto("update datosmedicos set ObraSocial='" . $obraSocial . "',NumeroSocio='" . $numSocio ."',GrupoSangre='" . $grupoS ."',Factor='" . $factor . "',EnfermedadCronica=" . $enfermedadC . ",EnfermedadCronicaE='" . $enfermedadCE . "',Internacion3anos=" . $internacion . ",Internacion3anosE='" . $internacionE . "',EnfermedadInfecciosa=" . $enfermedadI . ",EnfermedadInfecciosaE='" . $enfermedadIE . "',IntervencionQuirurjica=" . $intervencion . ",IntervencionQuirurjicaE='" . $intervencionE . "',Alergia=" . $alergia . ",AlergiaE='" . $alergiaE . "',Vegetariano=" . $vegetariano . ",Dieta='" . $dieta . "',Fuma=" . $fumador . ",Lateralidad=" . $lateralidad . ",Lentes=" . $lentes . ",Audifonos=" . $audifonos . ",LimitacionFisica=" . $limitacion . ",LimitacionFisicaE='" . $limitacionE . "',DonanteOrganos=" . $donanteOrganos . ",DonanteMedula=" . $donanteMedula . ",NombreMedicamento='" . $nombreMed . "',Droga='" . $monodroga . "',CantidadMedicamento='" . $cantidadMed . "' where idSocio=" . $idSocio);
			
				header('Location: perfil.php?a=p');
			}
		}
	} else if ($idSocio != ""){
		header('Location: perfil.php?a=p');
	} else if ($idSocio == ""){
		header('Location: preregistro.php');
	}
	
	$conexion->Libero(); //Se cierra la conexión a la base	
	$conexion2->Libero(); //Se cierra la conexión a la base	
	$conexion3->Libero(); //Se cierra la conexión a la base	
	$tpl->printToScreen(); //Se manda todo al HTML usando TPL
	
	function cargarPeriodos($conexion2, $conexion3, $tpl, $nombreCombo, $idSocio, $idCargo, $tabla, $campo){
		if (date('m') < 6){ //Si no empezó Junio
			$anoInicio = date('Y') - 13;
			$anoFin = date('Y') + 1;
		} else {
			$anoInicio = date('Y') - 12;
			$anoFin = date('Y') + 2;
		}
		
		$conexion2->Ejecuto("select idPeriodo, AnoInicio, AnoFin from periodo where AnoInicio>=" . $anoInicio . " and AnoFin<=" . $anoFin . " order by idPeriodo DESC");
		
		while($periodo=$conexion2->Siguiente()){
			$tpl->newBlock($nombreCombo);
			$tpl->assign("valor", $periodo['idPeriodo']);
			$tpl->assign("opcion", $periodo['AnoInicio'] . "-" . $periodo['AnoFin']);
			
			if ($idSocio != ""){
				$conexion3->Ejecuto("select idPeriodo from " . $tabla . " where idSocio=" . $idSocio . " and " . $campo . "=" . $idCargo);
				while($periodosHist=$conexion3->Siguiente()){
					if ($periodosHist['idPeriodo'] == $periodo['idPeriodo']){
						$tpl->assign("seleccionado", "selected='selected'");
						break;
					}
				}
			}
		}
	}
	
	function quitarCaracteres($string)
	{
	    $string = trim($string);
		
		$string = utf8_decode($string);
		
	    $string = str_replace(
		    array("\\", "¨", "º", "-", "~",
	             "#", "@", "|", "!", "\"",
        	     "·", "$", "%", "&", "/",
	             "(", ")", "?", "'", "¡",
	             "¿", "[", "^", "`", "]",
	             "+", "}", "{", "¨", "´",
	             ">", "< ", ";", ",", ":",
	             ".", "*", "_"),
	        '',
	        $string
	    );
		
	    return utf8_encode($string);
	}
?>