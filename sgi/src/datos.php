<?php
	ini_set("display_errors", 0);
	include("config.php");
	require_once("conexionDB.php");
	session_start(); //Se inicia la sesión
	$obj_con=new conectar;
	
	require_once("class.TemplatePower.inc.php"); //Usando Template Power
	
	date_default_timezone_set('America/Argentina/Buenos_Aires');
	
	$tpl=new TemplatePower("datos.html");
 	$tpl->prepare();
	
	$conexion= new ConexionDB($obj_con->getServ(),$obj_con->getBase(),$obj_con->getUsr(),$obj_con->getPass());
	$conexion2= new ConexionDB($obj_con->getServ(),$obj_con->getBase(),$obj_con->getUsr(),$obj_con->getPass());
	$conexion3= new ConexionDB($obj_con->getServ(),$obj_con->getBase(),$obj_con->getUsr(),$obj_con->getPass());
	
	$conexion3->Ejecuto("select i.idSocio, c.Nombre as 'Calidad' from inscripcionevento i, calidadasistenciaevento c, socio s where i.idEvento=88 and i.Aprobado<>2 and i.Eliminado=0 and i.idSocio=s.idSocio and i.idCalidadAsistencia=c.idCalidadAsistencia order by s.Nombres ASC, s.Apellidos ASC");
	$tpl->newBlock("inscripto");
	
	while($inscriptos=$conexion3->Siguiente()){		
		$conexion->Ejecuto("select s.Nombres, s.Apellidos, c.Nombre as 'Club', d.Nombre as 'Distrito', s.FechaIngreso, r.Nombre as 'Rueda', s.AreaEstudio, s.Trabajo from socio s, tiporueda r, club c, distrito d where s.idSocio=" . $inscriptos['idSocio'] . " and s.idClub=c.idClub and c.idDistrito=d.idDistrito and r.idTipoRueda=s.idTipoRueda");
		$datosSocio=$conexion->Siguiente();
		
		//Bloque datos personales
		$tpl->newBlock("personales");
		$tpl->assign("nombre", $datosSocio['Nombres'] . " " . $datosSocio['Apellidos']);
		$tpl->assign("estudio", $datosSocio['AreaEstudio'] . " / " . $datosSocio['Trabajo']);
		$tpl->assign("distrito", $datosSocio['Distrito']);
		$tpl->assign("club", $datosSocio['Club']);
		$fechaIng = split("-", $datosSocio['FechaIngreso']);
		$tpl->assign("fechaI", $fechaIng[2] . "/" . $fechaIng[1] . "/" . $fechaIng[0]);
		$tpl->assign("rueda", $datosSocio['Rueda']);
		$tpl->assign("calidad", $inscriptos['Calidad']);
			
		//Cargos en el Club		
		$conexion->Ejecuto("select cc.Nombre as 'Cargo', p.AnoInicio, p.AnoFin from historialcargoclub h, periodo p, cargoclub cc where h.idSocio=" . $inscriptos['idSocio'] . " and p.idPeriodo=h.idPeriodo and cc.idCargoClub=h.idCargoClub order by p.idPeriodo DESC");
		$cantCargoClub=$conexion->Tamano();
		
		$tpl->assign("cargosC", $cantCargoClub);
		
		//Cargos en el Distrito		
		$conexion->Ejecuto("select cd.Nombre as 'Cargo', p.AnoInicio, p.AnoFin from historialcargodistrito h, periodo p, cargodistrito cd where h.idSocio=" . $inscriptos['idSocio'] . " and p.idPeriodo=h.idPeriodo and cd.idCargoDistrito=h.idCargoDistrito order by p.idPeriodo DESC");
		$cantCargoDistrito=$conexion->Tamano();

		$tpl->assign("cargosD", $cantCargoDistrito);
		
		//Cargar cargos anteriores en AIRAUP		
		$conexion->Ejecuto("select ca.Nombre as 'Cargo', p.AnoInicio, p.AnoFin from historialcargoairaup h, periodo p, cargoairaup ca where h.idSocio=" . $inscriptos['idSocio'] . " and p.idPeriodo=h.idPeriodo and ca.idCargoAIRAUP=h.idCargoAIRAUP order by p.idPeriodo DESC");
		$cantCargoAIRAUP=$conexion->Tamano();
		
		$tpl->assign("cargosA", $cantCargoAIRAUP);
		
		$cantidadD = 0;
		$cantidadDI = 0;
		
		//Cargar asistencia a eventos del Distrito
		$conexion->Ejecuto("select t.Nombre, h.CantidadAsistencias, h.VecesInstructor from historialevento h, tipoevento t where h.idSocio=" . $inscriptos['idSocio'] . " and t.idTipoEvento=h.idTipoEvento and t.Tipo=0 order by t.Nombre ASC");
		
		while($eventosDistrito=$conexion->Siguiente()){
			$cantidadD += $eventosDistrito['CantidadAsistencias'];
			$cantidadDI += $eventosDistrito['VecesInstructor'];
		}
		
		$tpl->assign("eventosD", $cantidadD);
		$tpl->assign("instructorD", $cantidadDI);
		
		$cantidadAI = 0;
		
		//Cargar asistencia a ERAUP
		$conexion->Ejecuto("select h.CantidadAsistencias, h.VecesInstructor from historialevento h, tipoevento t where h.idSocio=" . $inscriptos['idSocio'] . " and t.idTipoEvento=h.idTipoEvento and t.Tipo=1 and t.Nombre='E.R.A.U.P.'");
		$cantERAUP=$conexion->Siguiente();
		
		$cantidadAI += $cantERAUP['VecesInstructor'];
		
		//Cargar asistencia a Asambleas de AIRAUP
		$conexion->Ejecuto("select h.CantidadAsistencias, h.VecesInstructor from historialevento h, tipoevento t where h.idSocio=" . $inscriptos['idSocio'] . " and t.idTipoEvento=h.idTipoEvento and t.Tipo=1 and t.Nombre='Asamblea A.I.R.A.U.P.'");
		$cantAsambleas=$conexion->Siguiente();
		
		$cantidadAI += $cantAsambleas['VecesInstructor'];
		
		$tpl->assign("eventosA", $cantERAUP['CantidadAsistencias'] + $cantAsambleas['CantidadAsistencias']);
		$tpl->assign("instructorA", $cantidadAI);
	}
	
	$conexion->Libero();
	$conexion2->Libero(); //Se cierra la conexión a la base	
	$conexion3->Libero();
	$tpl->printToScreen(); //Se manda todo al HTML usando TPL
	
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
	
	function convertirBit($dato){
		if ($dato == 0){
			return "No";	
		} else if ($dato == 1){
			return "Si";
		}
	}
?>