<?php
	ini_set("display_errors", 0);
	include("config.php");
	require_once("conexionDB.php");
	require 'class.phpmailer.php';
	require 'class.smtp.php';
	session_start(); //Se inicia la sesión
	$obj_con=new conectar;
	
	require_once("class.TemplatePower.inc.php"); //Usando Template Power
	
	$tpl=new TemplatePower("cuadrosocial.html");
 	$tpl->prepare();
	
	$conexion= new ConexionDB($obj_con->getServ(),$obj_con->getBase(),$obj_con->getUsr(),$obj_con->getPass());
	$conexion2= new ConexionDB($obj_con->getServ(),$obj_con->getBase(),$obj_con->getUsr(),$obj_con->getPass());
	
	$idSocio=$_GET['idS'];
	$accion=$_POST['a'];
	$idPeriodoActual = obtenerPeriodoActual($conexion);
	
	if ($accion == ""){
		$accion=$_GET['a'];
	}
	
	$logueado = $_SESSION['usuario'];
	
	if ($logueado == ""){
		header('Location: login.php');
	} else {
		if ($accion == "activarS"){
			$cantSocios=$_POST['cantSocios'];
			
			for ($i=1;$i<=$cantSocios;$i++){
				$idSocio=$_POST['activoI' . $i];
				
				$conexion->Ejecuto("select Activo from socio where idSocio=" . $idSocio);
				$activo=$conexion->Siguiente();
				
				if ($activo['Activo'] == 0){
					$conexion->Ejecuto("update socio set Activo=1 where idSocio=" . $idSocio);
				} else if ($activo['Activo'] == 1){
					$conexion->Ejecuto("update socio set Activo=0 where idSocio=" . $idSocio);
				}else if ($activo['Activo'] == 2){
					$conexion->Ejecuto("update socio set Activo=0 where idSocio=" . $idSocio);
				}
			}
			
			header('Location: cuadrosocial.php');
		} else if ($accion == "adm"){
			$idSocio=$_GET['idS'];
			
			$conexion->Ejecuto("select Admin, Email from socio where idSocio=" . $idSocio);
			$admin=$conexion->Siguiente();
			
			if ($admin['Admin'] == 0){
				$conexion->Ejecuto("update socio set Admin=1 where idSocio=" . $idSocio);
				$mensaje = "Fuiste designado como administrador distrital en el sistema.";
			} else if ($admin['Admin'] == 1){
				$conexion->Ejecuto("update socio set Admin=0 where idSocio=" . $idSocio);
				$mensaje = "Se te quitó la potestad de administración distrital en el sistema.";
			}else if ($admin['Admin'] == 2){
				$conexion->Ejecuto("update socio set Admin=0 where idSocio=" . $idSocio);
				$mensaje = "Se te quitó la potestad de administración distrital en el sistema.";
			}
			
			enviarCorreo($admin['Email'], utf8_encode("Potestad de administración"), utf8_encode($mensaje));
			
			header('Location: cuadrosocial.php');
		} else {
			$conexion->Ejecuto("select Admin from socio where idSocio=" . $logueado);
			$datosSocio=$conexion->Siguiente();
			
			$conexion->Ejecuto("select h.idSocio from historialcargoairaup h, cargoairaup c where h.idSocio=" . $logueado . " and c.Nombre='Presidente' and c.idCargoAIRAUP=h.idCargoAIRAUP and h.idPeriodo=" . $idPeriodoActual);
			$presidenteA=$conexion->Siguiente();
			
			$conexion->Ejecuto("select count(*) as 'Cantidad' from eventoadmin ea, evento e where ea.idSocio=" . $logueado . " and e.idEvento=ea.idEvento and e.Habilitado=1");
			$adminEventos=$conexion->Siguiente();
			
			$conexion->Ejecuto("select c.Nombre from historialcargoclub h, cargoclub c, periodo where h.idSocio=" . $logueado . " and c.idCargoClub=h.idCargoClub and h.idPeriodo=" . $idPeriodoActual);
			
			while($cargosClub=$conexion->Siguiente()){
				if ($cargosClub['Nombre'] == 'Presidente' || $cargosClub['Nombre'] == 'Secretario'){
					$presidente = true;
					$tpl->newBlock("menuAprobacion");
					$tpl->newBlock("menuCuadroSocial");
					$tpl->newBlock("menuStats");
					break;
				}
			}
			
			$conexion->Ejecuto("select c.Nombre from historialcargodistrito h, cargodistrito c where h.idSocio=" . $logueado . " and c.idCargoDistrito=h.idCargoDistrito and h.idPeriodo=" . $idPeriodoActual);
			
			while($cargosDistrito=$conexion->Siguiente()){
				if ($cargosDistrito['Nombre'] == 'Representante Distrital'){
					$representante = true;
					
					if (!$presidente){
						$tpl->newBlock("menuAprobacion");
						$tpl->newBlock("menuCuadroSocial");
						$tpl->newBlock("menuStats");
					}
					break;	
				} else if ($cargosDistrito['Nombre'] == 'Administrador'){
					$Administrador= true;
					
					if (!$presidente){
						$tpl->newBlock("menuAprobacion");
						$tpl->newBlock("menuCuadroSocial");
						$tpl->newBlock("menuStats");
					}
					break;	
				}
			}
			
			if ($presidenteA['idSocio'] == $logueado && !$presidente && !$representante){
				$tpl->newBlock("menuStats");
			}
			
			if ($datosSocio['Admin'] == 1 || $representante || $adminEventos['Cantidad'] > 0 || $presidenteA['idSocio'] == $logueado){
				$tpl->newBlock("menuEventos");
			}else if ($datosSocio['Admin'] == 2 || $representante || $adminEventos['Cantidad'] > 0 || $presidenteA['idSocio'] == $logueado){
				$tpl->newBlock("menuEventos");
			}
			
			if (!$presidente && !$representante){
				header('Location: perfil.php?a=p');
			}
			
			$conexion->Ejecuto("select s.idClub, c.Nombre as 'Club', d.idDistrito, d.Nombre as 'Distrito' from socio s, club c, distrito d where s.idSocio=" . $logueado . " and c.idClub=s.idClub and c.idDistrito=d.idDistrito");
			$idClub=$conexion->Siguiente();
			
			$descarga=$_POST['d'];
			
			if ($descarga == "d"){
				descargarListado($conexion, $conexion2, $presidente, $representante,$Administrador, $idClub);
			}
			
			if ($representante){
				$sentencia="select s.idSocio, s.Nombres, s.Apellidos, c.Nombre as 'Club', s.Activo, s.Admin, t.Nombre as 'Rueda' from socio s, tiporueda t, club c where c.idDistrito=" . $idClub['idDistrito'] . " and s.idClub=c.idClub and s.idTipoRueda=t.idTipoRueda";
			} else if ($Administrador){
				$sentencia="select s.idSocio, s.Nombres, s.Apellidos, c.Nombre as 'Club', s.Activo, s.Admin, t.Nombre as 'Rueda' from socio s, tiporueda t, club c where c.idDistrito=" . $idClub['idDistrito'] . " and s.idClub=c.idClub and s.idTipoRueda=t.idTipoRueda";
			} else if ($presidente){
				$sentencia="select s.idSocio, s.Nombres, s.Apellidos, c.Nombre as 'Club', s.Activo, s.Admin, t.Nombre as 'Rueda' from socio s, tiporueda t, club c where s.idClub=" . $idClub['idClub'] . " and s.idClub=c.idClub and s.idTipoRueda=t.idTipoRueda";			
			}
			
			$ordenamiento = $_GET['orden'];
		
			//Se añade el ordenamiento a la consulta
			if ($ordenamiento == 0 || $ordenamiento == ""){
				$sentencia .= " order by s.Nombres, s.Apellidos ASC";
			} else if ($ordenamiento == 1){
				$sentencia .= " order by Club ASC";
			} else if ($ordenamiento == 2){
				$sentencia .= " order by Rueda ASC";
			} else if ($ordenamiento == 3){
				$sentencia .= " order by Admin ASC";
			} else if ($ordenamiento == 4){
				$sentencia .= " order by Activo ASC";
			}
		
			//Ejecuto la consulta
			$conexion->Ejecuto($sentencia);
			$cantidad = $conexion->Tamano();
			
			$tpl->newBlock("cantidad");
			$tpl->assign("cantidad", $cantidad);
			
			$contarI = 0;
	
			while ($socios=$conexion->Siguiente()){
				$contarI++;
				$tpl->newBlock("lineaSocio");
				$tpl->assign("nombre", $socios['Nombres'] . " " . $socios['Apellidos']);
				$tpl->assign("club", $socios['Club']);
				$tpl->assign("rueda", $socios['Rueda']);
				
				if ($socios['Admin'] == 0){
					$tpl->assign("admin", "NO");
				} else if ($socios['Admin'] == 1) {
					$tpl->assign("admin", "SI");
				}else if ($socios['Admin'] == 2) {
					$tpl->assign("admin", "SI");
				}
				
				if ($socios['Activo'] == 0){
					$tpl->assign("activo", "NO");
				} else if ($socios['Activo'] == 1) {
					$tpl->assign("activo", "SI");
				}
				
				$tpl->assign("idSocio", $socios['idSocio']);
				$tpl->assign("contarI", $contarI);
				
				if ($representante){
					$tpl->newBlock("lineaSocioAdmin");
					
					if ($socios['Admin'] == 0){
						$tpl->assign("accion", "Habilitar ");
						$tpl->assign("idSocio", $socios['idSocio']);
					} else if ($socios['Admin'] == 1){
						$tpl->assign("accion", "Deshabilitar ");
						$tpl->assign("idSocio", $socios['idSocio']);
					}
				}
                                if ($Adminisrador){
					$tpl->newBlock("lineaSocioAdmin");
					
					if ($socios['Admin'] == 0){
						$tpl->assign("accion", "Habilitar ");
						$tpl->assign("idSocio", $socios['idSocio']);
					}else if ($socios['Admin'] == 2){
						$tpl->assign("accion", "Deshabilitar ");
						$tpl->assign("idSocio", $socios['idSocio']);
					}
				}
			}
			
			$tpl->newBlock("totalSocios");
			$tpl->assign("cantSocios", $contarI);
		}
	}
	
	$conexion->Libero(); //Se cierra la conexión a la base	
	$tpl->printToScreen(); //Se manda todo al HTML usando TPL
	
	function descargarListado($conexion, $conexion2, $presidente, $representante,$Adminisrador, $idClub){
		if ($representante){
			$nombreArchivo = "Distrito " . $idClub['Distrito'] . ".csv";
			$columnas = "Nombres;Apellidos;Documento;Dirección;Ciudad;Fecha de nac.;Sexo;Email;Facebook;Teléfono;Vive con;Puede hospedar;Club;Fecha de ing.;Rueda;Area de estudio;Trabajo;Nombre de contacto;Teléfono de contacto;Relación con contacto;Es admin;Está activo;Cargos en el Club;Cargos en el Distrito;Cargos en AIRAUP;Asambleas AIRAUP;ERAUP;Foro Distrital;Asamblea Distrital;Conferencia Distrital;Encuentro Zonal;Seminarios de Liderazgo;PETS;Seminarios de Instructores;Seminarios para Autoridades;Seminarios de Capacitación;Actividad de Servicio Distrital;Campamento Distrital;Reuniones de Presidentes";
			$sentencia = "select s.idSocio, s.Nombres, s.Apellidos, s.Documento, s.Direccion, s.Ciudad, s.FechaNac, s.Sexo, s.Email, s.Facebook, s.Telefono, s.ViveCon, s.Hospeda, c.Nombre as 'Club', s.FechaIngreso, t.Nombre as 'Rueda', s.AreaEstudio, s.Trabajo, s.NombreContacto, s.TelefonoContacto, s.RelacionContacto, s.Admin, s.Activo from socio s, club c, tiporueda t where s.idClub=c.idClub and t.idTipoRueda=s.idTipoRueda and c.idDistrito=" . $idClub['idDistrito'] . " order by s.Nombres, s.Apellidos DESC";
		} else if ($Administrador){
			$nombreArchivo = "Distrito " . $idClub['Distrito'] . ".csv";
			$columnas = "Nombres;Apellidos;Documento;Dirección;Ciudad;Fecha de nac.;Sexo;Email;Facebook;Teléfono;Vive con;Puede hospedar;Club;Fecha de ing.;Rueda;Area de estudio;Trabajo;Nombre de contacto;Teléfono de contacto;Relación con contacto;Es admin;Está activo;Cargos en el Club;Cargos en el Distrito;Cargos en AIRAUP;Asambleas AIRAUP;ERAUP;Foro Distrital;Asamblea Distrital;Conferencia Distrital;Encuentro Zonal;Seminarios de Liderazgo;PETS;Seminarios de Instructores;Seminarios para Autoridades;Seminarios de Capacitación;Actividad de Servicio Distrital;Campamento Distrital;Reuniones de Presidentes";
			$sentencia = "select s.idSocio, s.Nombres, s.Apellidos, s.Documento, s.Direccion, s.Ciudad, s.FechaNac, s.Sexo, s.Email, s.Facebook, s.Telefono, s.ViveCon, s.Hospeda, c.Nombre as 'Club', s.FechaIngreso, t.Nombre as 'Rueda', s.AreaEstudio, s.Trabajo, s.NombreContacto, s.TelefonoContacto, s.RelacionContacto, s.Admin, s.Activo from socio s, club c, tiporueda t where s.idClub=c.idClub and t.idTipoRueda=s.idTipoRueda and c.idDistrito=" . $idClub['idDistrito'] . " order by s.Nombres, s.Apellidos DESC";
		} else if ($presidente){
			$nombreArchivo = utf8_decode($idClub['Club']) . ".csv";
			$columnas = "Nombres;Apellidos;Documento;Dirección;Ciudad;Fecha de nac.;Sexo;Email;Facebook;Teléfono;Vive con;Puede hospedar;Fecha de ing.;Area de estudio;Trabajo;Nombre de contacto;Teléfono de contacto;Relación con contacto;Está activo;Cargos en el Club;Cargos en el Distrito;Cargos en AIRAUP;Asambleas AIRAUP;ERAUP;Foro Distrital;Asamblea Distrital;Conferencia Distrital;Encuentro Zonal;Seminarios de Liderazgo;PETS;Seminarios de Instructores;Seminarios para Autoridades;Seminarios de Capacitación;Actividad de Servicio Distrital;Campamento Distrital;Reuniones de Presidentes";
			$sentencia = "select s.idSocio, s.Nombres, s.Apellidos, s.Documento, s.Direccion, s.Ciudad, s.FechaNac, s.Sexo, s.Email, s.Facebook, s.Telefono, s.ViveCon, s.Hospeda, s.FechaIngreso, s.AreaEstudio, s.Trabajo, s.NombreContacto, s.TelefonoContacto, s.RelacionContacto,  s.Activo from socio s where s.idClub=" . $idClub['idClub'] . " order by s.Nombres, s.Apellidos DESC";
		}
		
		$fh = fopen($nombreArchivo, 'w');
		fwrite($fh, $columnas . "\n");

		$conexion->Ejecuto($sentencia);
			
		while($socios=$conexion->Siguiente()){
			$linea = $socios['Nombres'] . ";" . $socios['Apellidos'] . ";" . $socios['Documento'] . ";" . $socios['Direccion'] . ";" . $socios['Ciudad'] . ";";
				
			$fechaN = split("-", $socios['FechaNac']);
				
			$linea .= $fechaN[2] . "/" . $fechaN[1] . "/" . $fechaN[0] . ";";
			
			if ($socios['Sexo'] == "1"){
				$linea .= "Femenino" . ";";
			} else if ($socios['Sexo'] == "2"){
				$linea .= "Masculino" . ";";
			} else {
				$linea .= ";";
			}
			
			$linea .= $socios['Email'] . ";" . $socios['Facebook'] . ";" . $socios['Telefono'] . ";" . $socios['ViveCon'] . ";";
				
			if ($socios['Hospeda'] == "0"){
				$linea .= "No" . ";";
			} else if ($socios['Hospeda'] == "1"){
				$linea .= "Si" . ";";
			} else {
				$linea .= ";";
			}
			
			if ($representante){
				$linea .= $socios['Club'] . ";";
			}
			if ($Administrador){
				$linea .= $socios['Club'] . ";";
			}
	
			$fechaI = split("-", $socios['FechaIngreso']);
				
			$linea .= $fechaI[2] . "/" . $fechaI[1] . "/" . $fechaI[0] . ";";
			
			if ($representante){
				$linea .= $socios['Rueda'] . ";";
			}
			if ($Administrador){
				$linea .= $socios['Rueda'] . ";";
			}

			$linea .= $socios['AreaEstudio'] . ";" . $socios['Trabajo'] . ";" . $socios['NombreContacto'] . ";" . $socios['TelefonoContacto'] . ";" . $socios['RelacionContacto'] . ";";
				
			if ($representante){
				if ($socios['Admin'] == "0"){
					$linea .= "No" . ";";
				} else if ($socios['Admin'] == "1"){
					$linea .= "Si" . ";";
				}else if ($socios['Admin'] == "2"){
					$linea .= "Si" . ";";
				}
			}
			if ($Administrador){
				if ($socios['Admin'] == "0"){
					$linea .= "No" . ";";
				} else if ($socios['Admin'] == "1"){
					$linea .= "Si" . ";";
				}else if ($socios['Admin'] == "2"){
					$linea .= "Si" . ";";
				}
			}
				
			if ($socios['Activo'] == "0"){
				$linea .= "No;";
			} else if ($socios['Activo'] == "1"){
				$linea .= "Si;";
			}
			
			//Cargos en el club
			$conexion2->Ejecuto("select cc.Nombre, p.AnoInicio, p.AnoFin from cargoclub cc, historialcargoclub hc, periodo p
where hc.idSocio=" . $socios['idSocio'] . " and hc.idCargoClub=cc.idCargoClub and hc.idPeriodo=p.idPeriodo order by p.AnoInicio ASC");
			$cantidadCC=$conexion2->Tamano();
			$x = 0;
			
			if ($cantidadCC == 0){
				$linea .= ";";
			}
			
			while($cargosC=$conexion2->Siguiente()){
				if ($x == ($cantidadCC - 1)){
					$linea .= $cargosC['Nombre'] . " (" . $cargosC['AnoInicio'] . "-" . $cargosC['AnoFin'] . ");";
				} else {
					$linea .= $cargosC['Nombre'] . " (" . $cargosC['AnoInicio'] . "-" . $cargosC['AnoFin'] . "), ";
				}
				
				$x++;
			}
			
			//Cargos en el distrito
			$conexion2->Ejecuto("select cd.Nombre, p.AnoInicio, p.AnoFin from cargodistrito cd, historialcargodistrito hc, periodo p
where hc.idSocio=" . $socios['idSocio'] . " and hc.idCargoDistrito=cd.idCargoDistrito and hc.idPeriodo=p.idPeriodo order by p.AnoInicio ASC");
			$cantidadCD=$conexion2->Tamano();
			$x = 0;
		
			if ($cantidadCD == 0){
				$linea .= ";";
			}
			
			while($cargosD=$conexion2->Siguiente()){
				if ($x == ($cantidadCD - 1)){
					$linea .= $cargosD['Nombre'] . " (" . $cargosD['AnoInicio'] . "-" . $cargosD['AnoFin'] . ");";
				} else {
					$linea .= $cargosD['Nombre'] . " (" . $cargosD['AnoInicio'] . "-" . $cargosD['AnoFin'] . "), ";
				}
				
				$x++;
			}
			
			//Cargos en AIRAUP
			$conexion2->Ejecuto("select ca.Nombre, p.AnoInicio, p.AnoFin from cargoairaup ca, historialcargoairaup hc, periodo p
where hc.idSocio=" . $socios['idSocio'] . " and hc.idCargoAIRAUP=ca.idCargoAIRAUP and hc.idPeriodo=p.idPeriodo order by p.AnoInicio ASC");
			$cantidadCA=$conexion2->Tamano();
			$x = 0;
			
			if ($cantidadCA == 0){
				$linea .= ";";
			}
			
			while($cargosA=$conexion2->Siguiente()){
				if ($x == ($cantidadCA - 1)){
					$linea .= $cargosA['Nombre'] . " (" . $cargosA['AnoInicio'] . "-" . $cargosA['AnoFin'] . ");";
				} else {
					$linea .= $cargosA['Nombre'] . " (" . $cargosA['AnoInicio'] . "-" . $cargosA['AnoFin'] . "), ";
				}
				
				$x++;
			}
			
			//Asistencia a Asambleas AIRAUP
			$conexion2->Ejecuto("select h.CantidadAsistencias from historialevento h, tipoevento t where h.idSocio=" . $socios['idSocio'] . " and t.idTipoEvento=h.idTipoEvento and t.Tipo=1 and t.Nombre='Asamblea A.I.R.A.U.P.'");
			$cantAsambleas=$conexion2->Siguiente();
			
			$linea .= $cantAsambleas['CantidadAsistencias'] . ";";
			
			//Asistencias a ERAUP
			$conexion2->Ejecuto("select Mesa, idSocio from historialmesaeraup where idSocio=" . $socios['idSocio']);
			$cantidadE=$conexion2->Tamano();
			$x = 0;
			
			while($mesaERAUP=$conexion2->Siguiente()){
				if ($x == ($cantidadE - 1)){
					$linea .= $mesaERAUP['Mesa'] . ";";
				} else {
					$linea .= $mesaERAUP['Mesa'] . ", ";
				}
				
				$x++;
			}
			
			if ($x == 0){
				$linea .= ";";
			}
			
			//Asistencias a eventos distritales
			$conexion2->Ejecuto("select t.Nombre, h.CantidadAsistencias from historialevento h, tipoevento t where h.idSocio=" . $socios['idSocio'] . " and t.idTipoEvento=h.idTipoEvento and t.Tipo=0");
			$cantidadEV=$conexion2->Tamano();

			if ($cantidadEV == 0){
				$linea .= ";;;;;;;;;;;;";
			}
			
			while($eventosDistrito=$conexion2->Siguiente()){
				$linea .= $eventosDistrito['CantidadAsistencias'] . ";";
			}
			
			fwrite($fh, utf8_decode($linea) . "\n");
		}

		fclose($fh);

	    header('Content-Description: File Transfer');
	    header('Content-Type: application/octet-stream');
	    header('Content-Disposition: attachment; filename="'.basename($nombreArchivo).'"');
	    header('Expires: 0');
	    header('Cache-Control: must-revalidate');
	    header('Pragma: public');
	    header('Content-Length: ' . filesize($nombreArchivo));
	    readfile($nombreArchivo);
	
		unlink($nombreArchivo);
	}
	
	function obtenerPeriodoActual($conexion){
		$anoActual = date('Y');
		$mesActual = date('m');
		
		if ($mesActual > 6){
			$sentencia = "select idPeriodo from periodo where AnoInicio=" . $anoActual;
		} else if ($mesActual <= 6){
			$sentencia = "select idPeriodo from periodo where AnoInicio=" . ($anoActual - 1);
		}
		
		$conexion->Ejecuto($sentencia);
		$periodo = $conexion->Siguiente();
		return $periodo['idPeriodo'];
	}
	
	function enviarCorreo($direccion, $asunto, $texto){
		if ($direccion != ""){
			$mail							= new PHPMailer();
			$mail->CharSet = 'UTF-8';
			$mail->IsSMTP();
			$mail->Host				= "mail.airaup.org";
			$mail->SMTPAuth		= true;
			$mail->SMTPSecure = "tls";
			$mail->Host				= "smtp.gmail.com";
			$mail->Port				= 587; 
			$mail->Username		= "sgi@airaup.org";
			$mail->Password		= "Sistema2017";
			// $mail->SetFrom('sgi@airaup.org', utf8_encode("Sistema de Gestión Integral - AIRAUP"));
			$mail->FromName = utf8_encode("Sistema de Gestión Integral - AIRAUP");
			$mail->From = "sgi@airaup.org";
			$mail->Subject = $asunto;
			$mail->MsgHTML($texto . utf8_encode("<br><br>Por favor no respondas este mensaje.<br>Sistema de Gestión Integral<br>AIRAUP"));
			$mail->AddAddress($direccion);
			$mail->Send();
		}
	}
?>