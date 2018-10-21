<?php
	ini_set("display_errors", 0);
	include("config.php");
	require_once("conexionDB.php");
	session_start(); //Se inicia la sesión
	$obj_con=new conectar;
	
	$conexion= new ConexionDB($obj_con->getServ(),$obj_con->getBase(),$obj_con->getUsr(),$obj_con->getPass());
	
	$distritos=$_GET['distritos'];
	
	$conexion->Ejecuto("select s.idSocio, s.Nombres, s.Apellidos from socio s, club c, distrito d where c.idClub=s.idClub and c.idDistrito=d.idDistrito and d.idDistrito in (" . $distritos . ") order by s.Nombres, s.Apellidos ASC");
	$total=$conexion->Tamano();
	
	$json = array();
	$x = 1;
	
	$json[0] = array(
		'total' => $total
	);
	
	while ($admins=$conexion->Siguiente()){
		$json[$x] = array(
			'idsocio' => $admins['idSocio'],
			'nombre' => $admins['Nombres'] . " " . $admins['Apellidos']
		);

		$x++;
	}
	
	$conexion->Libero(); //Se cierra la conexión a la base	
	echo json_encode($json); //Devuelvo la información
?>