<?php
ini_set("display_errors", 0);
include("config.php");
require_once("conexionDB.php");
session_start(); //Se inicia la sesi�n
$obj_con=new conectar;

require_once("class.TemplatePower.inc.php"); //Usando Template Power

$tpl=new TemplatePower("stats.html");
    $tpl->prepare();

$conexion= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());
$conexion2= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());
$conexion3= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());

$idPeriodoActual = obtenerPeriodoActual($conexion);
$logueado = $_SESSION['usuario'];

if ($logueado == "") {
    header('Location: login.php');
} else {
    $conexion->Ejecuto("select s.Admin, d.Nombre as 'NomDistrito', d.idDistrito, c.Nombre as 'NomClub', s.idClub from socio s, distrito d, club c where s.idSocio=" . $logueado . " and c.idDistrito=d.idDistrito and c.idClub=s.idClub");
    $datosSocio=$conexion->Siguiente();

    $conexion->Ejecuto("select h.idSocio from historialcargoairaup h, cargoairaup c where h.idSocio=" . $logueado . " and c.Nombre='Presidente' and c.idCargoAIRAUP=h.idCargoAIRAUP and h.idPeriodo=" . $idPeriodoActual);
    $presidenteA=$conexion->Siguiente();

    $conexion->Ejecuto("select count(*) as 'Cantidad' from eventoadmin ea, evento e where ea.idSocio=" . $logueado . " and e.idEvento=ea.idEvento and e.Habilitado=1");
    $adminEventos=$conexion->Siguiente();

    $conexion->Ejecuto("select c.Nombre from historialcargoclub h, cargoclub c, periodo where h.idSocio=" . $logueado . " and c.idCargoClub=h.idCargoClub and h.idPeriodo=" . $idPeriodoActual);

    while ($cargosClub=$conexion->Siguiente()) {
        if ($cargosClub['Nombre'] == 'Presidente' || $cargosClub['Nombre'] == 'Secretario') {
            $presidente = true;
            $tpl->newBlock("menuAprobacion");
            $tpl->newBlock("menuCuadroSocial");
            $tpl->newBlock("menuStats");
            break;
        }
    }

    $conexion->Ejecuto("select c.Nombre from historialcargodistrito h, cargodistrito c where h.idSocio=" . $logueado . " and c.idCargoDistrito=h.idCargoDistrito and h.idPeriodo=" . $idPeriodoActual);

    while ($cargosDistrito=$conexion->Siguiente()) {
        if ($cargosDistrito['Nombre'] == 'Representante Distrital') {
            $representante = true;

            if (!$presidente) {
                $tpl->newBlock("menuAprobacion");
                $tpl->newBlock("menuCuadroSocial");
                $tpl->newBlock("menuStats");
            }
            break;
        }
    }

    if ($datosSocio['Admin'] == 1 || $representante || $adminEventos['Cantidad'] > 0 || $presidenteA['idSocio'] == $logueado) {
        $tpl->newBlock("menuEventos");
    }

    if (!$presidente && !$representante && $presidenteA['idSocio'] != $logueado) {
        header('Location: perfil.php?a=p');
    }

    if ($presidenteA['idSocio'] == $logueado) {
        if (!$presidente && !$representante) {
            $tpl->newBlock("menuStats");
        }

        $tpl->newBlock("titulo");
        $tpl->assign("segmento", "A.I.R.A.U.P.");

        $sentenciaG = "select d.Nombre, count(s.idSocio) as 'CantidadS', sum(case when s.Sexo=1 then 1 else 0 end) as 'CantidadF', sum(case when s.Sexo=2 then 1 else 0 end) as 'CantidadM', sum(case when s.Sexo is NULL then 1 else 0 end) as 'CantidadSE', AVG(TIMESTAMPDIFF(YEAR,s.FechaNac,CURDATE())) as 'Promedio' from distrito d, socio s, club c where s.idClub=c.idClub and c.idDistrito=d.idDistrito and s.Activo=1 and s.idTipoRueda=2 group by d.Nombre order by CantidadS DESC";

        $conexion->Ejecuto("select count(idSocio) as 'Total' from socio where Activo=1 and idTipoRueda=2");
        $totalSocios=$conexion->Siguiente();
    } elseif ($representante) {
        $tpl->newBlock("titulo");
        $tpl->assign("segmento", "Distrito " . $datosSocio['NomDistrito']);

        $sentenciaG = "select c.Nombre, count(s.idSocio) as 'CantidadS', sum(case when s.Sexo=1 then 1 else 0 end) as 'CantidadF', sum(case when s.Sexo=2 then 1 else 0 end) as 'CantidadM', sum(case when s.Sexo is NULL then 1 else 0 end) as 'CantidadSE', AVG(TIMESTAMPDIFF(YEAR,s.FechaNac,CURDATE())) as 'Promedio' from distrito d, socio s, club c where s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito=" . $datosSocio['idDistrito'] . " and s.Activo=1 and s.idTipoRueda=2 group by c.Nombre order by CantidadS DESC";

        $conexion->Ejecuto("select count(s.idSocio) as 'Total' from socio s, club c, distrito d where s.Activo=1 and s.idClub=c.idClub and c.idDistrito=d.idDistrito and s.idTipoRueda=2 and d.idDistrito=" . $datosSocio['idDistrito']);
        $totalSocios=$conexion->Siguiente();
    } elseif ($presidente) {
        $tpl->newBlock("titulo");
        $tpl->assign("segmento", $datosSocio['NomClub']);

        $sentenciaG = "select c.Nombre, count(s.idSocio) as 'CantidadS', sum(case when s.Sexo=1 then 1 else 0 end) as 'CantidadF', sum(case when s.Sexo=2 then 1 else 0 end) as 'CantidadM', sum(case when s.Sexo is NULL then 1 else 0 end) as 'CantidadSE', AVG(TIMESTAMPDIFF(YEAR,s.FechaNac,CURDATE())) as 'Promedio' from socio s, club c where s.idClub=c.idClub and c.idClub=" . $datosSocio['idClub'] . " and s.Activo=1 and s.idTipoRueda=2 order by CantidadS DESC";
    }

    $conexion->Ejecuto($sentenciaG);

    $totalH = 0;
    $totalM = 0;
    $totalSE = 0;
    $totalGeneral = 0;

    while ($general=$conexion->Siguiente()) {
        $tpl->newBlock("lineaGeneral");

        if ($presidenteA['idSocio'] == $logueado) {
            $tpl->assign("lineaSegmento", "Distrito " . $general['Nombre']);
        } else {
            $tpl->assign("lineaSegmento", $general['Nombre']);
        }

        if ($presidente) {
            $tpl->assign("cantidad", $general['CantidadS']);
        } else {
            $tpl->assign("cantidad", $general['CantidadS'] . " (" . round($general['CantidadS'] * 100 / $totalSocios['Total'], 1) . "%)");
        }

        $tpl->assign("cantH", $general['CantidadM'] . " (" . round($general['CantidadM'] * 100 / $general['CantidadS'], 1) . "%)");
        $tpl->assign("cantM", $general['CantidadF'] . " (" . round($general['CantidadF'] * 100 / $general['CantidadS'], 1) . "%)");
        $tpl->assign("cantSE", $general['CantidadSE'] . " (" . round($general['CantidadSE'] * 100 / $general['CantidadS'], 1) . "%)");
        $tpl->assign("edad", round($general['Promedio'], 1));

        $totalH += $general['CantidadM'];
        $totalM += $general['CantidadF'];
        $totalSE += $general['CantidadSE'];
        $totalGeneral += $general['CantidadS'];
    }

    if ($representante) { // CLUBES SIN SOCIOS REGISTRADOS
        $conexion->Ejecuto("select c.Nombre from club c, distrito d where c.idClub not in (select s.idClub from socio s, club c, distrito d where s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito=" . $datosSocio['idDistrito'] . " group by s.idClub) and c.idDistrito=d.idDistrito and d.idDistrito=" . $datosSocio['idDistrito'] . " order by c.Nombre ASC");

        while ($general=$conexion->Siguiente()) {
            $tpl->newBlock("lineaGeneral");
            $tpl->assign("lineaSegmento", $general['Nombre']);

            $tpl->assign("cantidad", "0");
            $tpl->assign("cantH", "0");
            $tpl->assign("cantM", "0");
            $tpl->assign("cantSE", "0");
            $tpl->assign("edad", "0");
        }
    }

    if ($presidenteA['idSocio'] == $logueado || $representante) {
        $tpl->newBlock("lineaGeneral");
        $tpl->assign("lineaSegmento", "TOTAL");
        $tpl->assign("cantidad", $totalSocios['Total']);
        $tpl->assign("cantH", $totalH . " (" . round($totalH * 100 / $totalGeneral, 1) . "%)");
        $tpl->assign("cantM", $totalM . " (" . round($totalM * 100 / $totalGeneral, 1) . "%)");
        $tpl->assign("cantSE", $totalSE . " (" . round($totalSE * 100 / $totalGeneral, 1) . "%)");

        if ($presidenteA['idSocio'] == $logueado) {
            $global = "select AVG(TIMESTAMPDIFF(YEAR,s.FechaNac,CURDATE())) as 'Global' from distrito d, socio s, club c where s.idClub=c.idClub and c.idDistrito=d.idDistrito and s.Activo=1";
        } elseif ($representante) {
            $global = "select AVG(TIMESTAMPDIFF(YEAR,s.FechaNac,CURDATE())) as 'Global' from distrito d, socio s, club c where s.idClub=c.idClub and c.idDistrito=d.idDistrito and s.Activo=1 and d.idDistrito=" . $datosSocio['idDistrito'];
        }

        $conexion->Ejecuto($global);
        $total = $conexion->Siguiente();

        $tpl->assign("edad", round($total['Global'], 1));

        $tpl->newBlock("bloqueEventos");

        if ($presidenteA['idSocio'] == $logueado) {
            $sentenciaEA = "select t.Nombre, count(e.idEvento) as 'Cantidad' from evento e, tipoevento t where e.idTipoEvento=t.idTipoEvento and t.Tipo=1 group by t.Nombre";
            $sentenciaED = "select d.Nombre, count(e.idEvento) as 'Cantidad' from evento e, tipoevento t, distrito d, eventodistrito ed where e.idTipoEvento=t.idTipoEvento and ed.idEvento=e.idEvento and ed.idDistrito=d.idDistrito and t.Tipo=0 group by d.Nombre order by d.Nombre ASC";
        } elseif ($representante) {
            $sentenciaEA = "select t.Nombre, count(e.idEvento) as 'Cantidad' from evento e, tipoevento t, distrito d, eventodistrito ed where e.idTipoEvento=t.idTipoEvento and ed.idEvento=e.idEvento and ed.idDistrito=d.idDistrito and ed.idDistrito=" . $datosSocio['idDistrito'] . " and t.Tipo=1 group by t.Nombre order by t.Nombre ASC";
            $sentenciaED = "select t.Nombre, count(e.idEvento) as 'Cantidad' from evento e, tipoevento t, distrito d, eventodistrito ed where e.idTipoEvento=t.idTipoEvento and ed.idEvento=e.idEvento and ed.idDistrito=d.idDistrito and ed.idDistrito=" . $datosSocio['idDistrito'] . " and t.Tipo=0 group by t.Nombre order by t.Nombre ASC";
        }

        $total = 0;

        $conexion->Ejecuto($sentenciaEA);

        while ($eventosA=$conexion->Siguiente()) {
            $tpl->newBlock("lineaEventos");
            $tpl->assign("lineaSegmento", $eventosA['Nombre']);
            $tpl->assign("cantE", $eventosA['Cantidad']);
            $total += $eventosA['Cantidad'];
        }

        $tpl->newBlock("lineaEventos");

        $conexion->Ejecuto($sentenciaED);

        while ($eventosD=$conexion->Siguiente()) {
            $tpl->newBlock("lineaEventos");

            if ($presidenteA['idSocio'] == $logueado) {
                $tpl->assign("lineaSegmento", "Distrito " . $eventosD['Nombre']);
            } else {
                $tpl->assign("lineaSegmento", $eventosD['Nombre']);
            }

            $tpl->assign("cantE", $eventosD['Cantidad']);
            $total += $eventosD['Cantidad'];
        }

        $tpl->newBlock("lineaEventos");

        $tpl->newBlock("lineaEventos");
        $tpl->assign("lineaSegmento", "TOTAL");
        $tpl->assign("cantE", $total);

        if ($presidenteA['idSocio'] == $logueado) {
            $tpl->newBlock("promAsistencia");
            $conexion->Ejecuto("select idDistrito, Nombre from distrito order by Nombre ASC");
            $primerDistrito=true;

            while ($distrito=$conexion->Siguiente()) {
                $conexion2->Ejecuto("select idTipoEvento, Nombre from tipoevento where Tipo=1 order by Nombre ASC");
                $cantTipoEvento=$conexion2->Tamano();
                $primeraFila=true;
                $x = 0;

                while ($eventos=$conexion2->Siguiente()) {
                    if ($primerDistrito && $x < $cantTipoEvento) {
                        $tpl->newBlock("columnaEvento");
                        $tpl->assign("segmento", $eventos['Nombre']);
                        $x++;

                        if ($x == $cantTipoEvento) {
                            $primerDistrito = false;
                        }
                    }

                    if ($primeraFila) {
                        $tpl->newBlock("filaDistrito");
                        $tpl->assign("lineaDistrito", $distrito['Nombre']);
                        $primeraFila = false;
                    }

                    $conexion3->Ejecuto("SELECT AVG(a.cantidad) as 'CAltas', AVG(a.bajas) as 'CBajas' FROM (select sum(case when i.Eliminado=0 then 1 else 0 end) as cantidad, sum(case when i.Eliminado=1 then 1 else 0 end) as bajas FROM inscripcionevento i, evento e, socio s, club c, tipoevento t where i.idSocio=s.idSocio and i.Aprobado=1 and i.idEvento=e.idEvento and e.idTipoEvento=t.idTipoEvento and t.idTipoEvento=" . $eventos['idTipoEvento'] . " and s.idClub=c.idClub and c.idDistrito=" . $distrito['idDistrito'] . " GROUP BY i.idEvento) a");
                    while ($promedio=$conexion3->Siguiente()) {
                        $tpl->newBlock("columnaPromTipoEvento");

                        if ($promedio['CAltas'] != "") {
                            $tpl->assign("lineaPromedio", round($promedio['CAltas'], 1) . "/" . round($promedio['CBajas'], 1));
                        } else {
                            $tpl->assign("lineaPromedio", "0/0");
                        }
                    }
                }
            }
        }
    }
}

$conexion->Libero(); //Se cierra la conexi�n a la base
$conexion2->Libero(); //Se cierra la conexi�n a la base
$tpl->printToScreen(); //Se manda todo al HTML usando TPL

function obtenerPeriodoActual($conexion)
{
    $anoActual = date('Y');
    $mesActual = date('m');

    if ($mesActual > 6) {
        $sentencia = "select idPeriodo from periodo where AnoInicio=" . $anoActual;
    } elseif ($mesActual <= 6) {
        $sentencia = "select idPeriodo from periodo where AnoInicio=" . ($anoActual - 1);
    }

    $conexion->Ejecuto($sentencia);
    $periodo = $conexion->Siguiente();
    return $periodo['idPeriodo'];
}
