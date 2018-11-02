<?php
	ini_set("display_errors", 0);
	include("config.php");
	require_once("conexionDB.php");
	session_start(); //Se inicia la sesi�n
	$obj_con=new conectar;

	require_once("class.TemplatePower.inc.php"); //Usando Template Power

	$tpl=new TemplatePower("login.html");
 	$tpl->prepare();

	$accion=$_POST['accion'];
	$idSocio = $_SESSION['usuario'];

	$conexion= new ConexionDB($obj_con->getServ(),$obj_con->getBase(),$obj_con->getUsr(),$obj_con->getPass());

	if ($accion == "login"){
		$direccion=$_POST['mail'];
		$pwd=$_POST['pwd'];
		$conexion->Ejecuto("select idSocio, Activo, Password from socio where Email='" . $direccion . "'");
		$socio=$conexion->Siguiente();
		$isPasswordValid=password_verify($pwd, $socio["Password"]);

		if ($isPasswordValid){
			if ($socio['Activo'] == 0){
				$tpl->NewBlock("mensaje");
				$tpl->Assign("mensaje", "Tu cuenta est� desactivada");
			} else {
				$_SESSION['usuario'] = $socio['idSocio'];
				header('Location: perfil.php?a=p');
			}
		} else {
			$tpl->NewBlock("mensaje");
			$tpl->Assign("mensaje", utf8_encode("Los datos ingresados no son v�lidos"));
		}
	} else if ($idSocio != ""){
		header('Location: perfil.php?a=p');
	}

	$conexion->Libero(); //Se cierra la conexi�n a la base
	$tpl->printToScreen(); //Se manda todo al HTML usando TPL
?>
