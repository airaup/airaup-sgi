<?php
	ini_set("display_errors", 0);
	include("config.php");
	require_once("conexionDB.php");
	session_start(); //Se inicia la sesión
	$obj_con=new conectar;
	
	$conexion= new ConexionDB($obj_con->getServ(),$obj_con->getBase(),$obj_con->getUsr(),$obj_con->getPass());
	
	$idDistrito=$_GET['id'];
	$accion=$_GET['a'];
	
	if ($accion != "n"){
		$conexion->Ejecuto("select idClub from socio where idSocio=" . $accion);
		$idSeleccionado=$conexion->Siguiente();
	}
	
	$conexion->Ejecuto("select idClub, Nombre from club where idDistrito=" . $idDistrito . " and Activo=1 order by Nombre ASC");
	$cantClubes=$conexion->Tamano();
	
	$json = array();
	
	if ($cantClubes == 0){
		$json[0] = array(
			'total' => $cantClubes
		);
	} else {
		$x = 1;
	
		$json[0] = array(
			'total' => $cantClubes
		);
	
		while ($clubes=$conexion->Siguiente()){
			if ($idSeleccionado['idClub'] == $clubes['idClub']){
				$json[$x] = array(
					'idClub' => $clubes['idClub'],
					'nombre' => $clubes['Nombre'],
					'selected' => '1'
				);
			} else {
				$json[$x] = array(
					'idClub' => $clubes['idClub'],
					'nombre' => $clubes['Nombre'],
					'selected' => '0'
				);
			}
			
			$x++;
		}
	}
	
	$conexion->Libero(); //Se cierra la conexión a la base	
	echo json_encode($json); //Devuelvo la información
?>