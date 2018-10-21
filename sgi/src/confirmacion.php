<?php
	ini_set("display_errors", 0);
	include("config.php");
	require_once("conexionDB.php");
	session_start(); //Se inicia la sesión
	$obj_con=new conectar;
	
	require_once("class.TemplatePower.inc.php"); //Usando Template Power
	
	$tpl=new TemplatePower("registro.html");
 	$tpl->prepare();
	
	$id=$_GET['id'];
	
	if ($id != ""){
		$conexion= new ConexionDB($obj_con->getServ(),$obj_con->getBase(),$obj_con->getUsr(),$obj_con->getPass());
		$conexion2= new ConexionDB($obj_con->getServ(),$obj_con->getBase(),$obj_con->getUsr(),$obj_con->getPass());
		
		$conexion->Ejecuto("select idTransaccion from preregistro where idTransaccion=" . $id);
		$buscar=$conexion->Siguiente();
		
		if ($buscar['idTransaccion'] != ""){
			$tpl->newBlock("club");
			$tpl->assign("idTransaccion", $buscar['idTransaccion']);
			//Cargo Distritos
			$conexion->Ejecuto("select idDistrito, Nombre from distrito order by Nombre ASC");
			
			while($distritos=$conexion->Siguiente()){
				$tpl->newBlock("comboDistrito");
				$tpl->assign("valor", $distritos['idDistrito']);
				$tpl->assign("opcion", $distritos['Nombre']);
			}
		
			//Cargo los cargos de Club
			$conexion->Ejecuto("select idCargoClub, Nombre from cargoclub");
		
			while($cargoClub=$conexion->Siguiente()){
				$tpl->newBlock("checkCargoClub");
				$tpl->assign("idCargo", $cargoClub['idCargoClub']);
				$tpl->assign("cargo", $cargoClub['Nombre']);
				
				if ($cargoClub['Nombre'] == "Presidente" || $cargoClub['Nombre'] == "Secretario"){
					cargarPeriodos($conexion2, $tpl, "comboPeriodoClub", 1);
				} else {
					cargarPeriodos($conexion2, $tpl, "comboPeriodoClub", 0);
				}
			}
		
			$tpl->newBlock("distrito");
			//Cargo los cargos de Distrito
			$conexion->Ejecuto("select idCargoDistrito, Nombre from cargodistrito");
		
			while($cargoDistrito=$conexion->Siguiente()){
				$tpl->newBlock("checkCargoDistrito");
				$tpl->assign("idCargo", $cargoDistrito['idCargoDistrito']);
				$tpl->assign("cargo", $cargoDistrito['Nombre']);
				
				if ($cargoDistrito['Nombre'] == "Representante Distrital"){
					cargarPeriodos($conexion2, $tpl, "comboPeriodoDistrito", 1);
				} else {
					cargarPeriodos($conexion2, $tpl, "comboPeriodoDistrito", 0);
				}
			}
		
			//Cargo los eventos de Distrito
			$conexion->Ejecuto("select Nombre from tipoevento where Tipo=0 order by Nombre ASC");
			$cantTipoEvento=0;
			
			while($eventoDistrito=$conexion->Siguiente()){
				$cantTipoEvento++;
				$tpl->newBlock("tipoEvento");
				$tpl->assign("numTipoEvento", $cantTipoEvento);
				$tpl->assign("tipoEvento", $eventoDistrito['Nombre']);
			}
		
			$tpl->newBlock("airaup");
			//Cargo los cargos de AIRAUP
			$conexion->Ejecuto("select idCargoAIRAUP, Nombre from cargoairaup");
		
			while($cargoAIRAUP=$conexion->Siguiente()){
				$tpl->newBlock("checkCargoAIRAUP");
				$tpl->assign("idCargo", $cargoAIRAUP['idCargoAIRAUP']);
				$tpl->assign("cargo", $cargoAIRAUP['Nombre']);
				
				if ($cargoAIRAUP['Nombre'] == "Presidente"){
					cargarPeriodos($conexion2, $tpl, "comboPeriodoAIRAUP", 1);
				} else {
					cargarPeriodos($conexion2, $tpl, "comboPeriodoAIRAUP", 0);
				}
			}
		
			//Cargo mesas de ERAUP
			$conexion->Ejecuto("select idCalidadAsistencia, Nombre from calidadasistenciaevento where Nombre not in ('Sin definir')");
			
			while($calidadAsistencia=$conexion->Siguiente()){		
				if ($calidadAsistencia['Nombre'] != "Instructor"){
					$tpl->newBlock("checkMesasERAUP");
					$tpl->assign("idCalidad", $calidadAsistencia['idCalidadAsistencia']);
					$tpl->assign("calidadAsistente", $calidadAsistencia['Nombre']);
				}
			
				if ($calidadAsistencia['Nombre'] != "Instructor" && $calidadAsistencia['Nombre'] != "Equipo AIRAUP" && $calidadAsistencia['Nombre'] != "Organizador"){
					$tpl->newBlock("checkMesasInsERAUP");
					$tpl->assign("idCalidad", $calidadAsistencia['idCalidadAsistencia']);
					$tpl->assign("calidadInstructor", $calidadAsistencia['Nombre']);
				}
			}
		
			$tpl->newBlock("medicos");
		
			$tpl->newBlock("accion");
			$tpl->assign("tipo", "nuevo");
			$tpl->assign("cantTipoEvento", $cantTipoEvento);	
		} else {
			$tpl->newBlock("mensaje");
			$tpl->Assign("mensaje", utf8_encode("Ocurrió un error en la transacción, por favor intentalo nuevamente o comunicate con un administrador del sistema."));
		}
		
		$conexion->Libero(); //Se cierra la conexión a la base
	}
	
	$tpl->printToScreen(); //Se manda todo al HTML usando TPL
	
	function cargarPeriodos($conexion2, $tpl, $nombreCombo, $cargoEspecial){
		if ($cargoEspecial == 1){
			if (date('m') < 6){ //Si no empezó Junio
				$anoInicio = date('Y') - 13;
				$anoFin = date('Y') - 1;
			} else {
				$anoInicio = date('Y') - 12;
				$anoFin = date('Y');
			}
		} else if ($cargoEspecial == 0){
			if (date('m') < 6){ //Si no empezó Junio
				$anoInicio = date('Y') - 13;
				$anoFin = date('Y') + 1;
			} else {
				$anoInicio = date('Y') - 12;
				$anoFin = date('Y') + 2;
			}
		}
		
		$conexion2->Ejecuto("select idPeriodo, AnoInicio, AnoFin from periodo where AnoInicio>=" . $anoInicio . " and AnoFin<=" . $anoFin . " order by idPeriodo DESC");
		
		while($periodo=$conexion2->Siguiente()){
			$tpl->newBlock($nombreCombo);
			$tpl->assign("valor", $periodo['idPeriodo']);
			$tpl->assign("opcion", $periodo['AnoInicio'] . "-" . $periodo['AnoFin']);
		}
	}
?>