<?php
ini_set("display_errors", 0);
include("../../config/config.php");
include("../../helpers/conexionDB.php");
session_start(); //Se inicia la sesi�n
$obj_con=new conectar;

include("../../lib/class.TemplatePower.inc.php"); //Usando Template Power

$tpl=new TemplatePower("views/eventos.html");
    $tpl->prepare();

$conexion= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());
$conexion2= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());

$idSocio = $_SESSION['usuario'];
$idPeriodoActual = obtenerPeriodoActual($conexion);

if ($idSocio == "") {
    header('Location: modules/auth/login.php');
} else {
    $conexion->Ejecuto("select Admin, idClub from socio where idSocio=" . $idSocio);
    $admin=$conexion->Siguiente();

    $conexion->Ejecuto("select h.idSocio from historialcargoairaup h, cargoairaup c where h.idSocio=" . $idSocio . " and c.Nombre='Presidente' and c.idCargoAIRAUP=h.idCargoAIRAUP and h.idPeriodo=" . $idPeriodoActual);
    $presidenteA=$conexion->Siguiente();

    $conexion->Ejecuto("select count(*) as 'Cantidad' from eventoadmin ea, evento e where ea.idSocio=" . $idSocio . " and e.idEvento=ea.idEvento and e.Habilitado=1");
    $adminEventos=$conexion->Siguiente();

    $conexion->Ejecuto("select c.Nombre from historialcargoclub h, cargoclub c, periodo where h.idSocio=" . $idSocio . " and c.idCargoClub=h.idCargoClub and h.idPeriodo=" . $idPeriodoActual);

    while ($cargosClub=$conexion->Siguiente()) {
        if ($cargosClub['Nombre'] == 'Presidente' || $cargosClub['Nombre'] == 'Secretario') {
            $presidente = true;
            $tpl->newBlock("menuAprobacion");
            $tpl->newBlock("menuCuadroSocial");
            $tpl->newBlock("menuStats");
            break;
        }
    }

    $conexion->Ejecuto("select c.Nombre from historialcargodistrito h, cargodistrito c where h.idSocio=" . $idSocio . " and c.idCargoDistrito=h.idCargoDistrito and h.idPeriodo=" . $idPeriodoActual);

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

    if ($presidenteA['idSocio'] == $idSocio && !$presidente && !$representante) {
        $tpl->newBlock("menuStats");
    }

    if ($admin['Admin'] == 1 || $representante || $adminEventos['Cantidad'] > 0 || $presidenteA['idSocio'] == $idSocio) {
        $tpl->newBlock("menuEventos");

        $accion=$_GET['a'];

        if ($accion == 'd') {
            $idE=$_GET['id'];
            $conexion->Ejecuto("update evento set Habilitado=0 where idEvento=" . $idE);
        }

        $conexion->Ejecuto("select idTipoEvento from tipoevento where Nombre='E.R.A.U.P.'");
        $eraup=$conexion->Siguiente();

        if ($presidenteA['idSocio'] == $idSocio) {
            if ($admin['Admin'] == 1 || $representante) {
                $sentencia = "select e.idEvento, e.Nombre, e.FechaInicio, t.Nombre as 'Tipo', e.CupoMaximo from evento e, tipoevento t, eventodistrito ed where e.idTipoEvento=t.idTipoEvento and Habilitado=1 and e.idEvento=ed.idEvento and ed.idDistrito=(select d.idDistrito from distrito d, club c where c.idClub=" . $admin['idClub'] . " and c.idDistrito=d.idDistrito) group by e.idEvento order by e.FechaInicio DESC limit 10";
            } elseif ($adminEventos['Cantidad'] > 0) {
                $sentencia = "select e.idEvento, e.Nombre, e.FechaInicio, t.Nombre as 'Tipo', e.CupoMaximo from evento e, tipoevento t,  eventoadmin ea where e.idEvento=ea.idEvento and ea.idSocio=" . $idSocio . " and e.idTipoEvento=t.idTipoEvento and Habilitado=1 group by e.idEvento order by e.FechaInicio DESC limit 10";
            } else {
                $sentencia = "select e.idEvento, e.Nombre, e.FechaInicio, t.Nombre as 'Tipo', e.CupoMaximo from evento e, tipoevento t, eventodistrito ed where e.idTipoEvento=t.idTipoEvento and e.idTipoEvento=" . $eraup['idTipoEvento'] . " and Habilitado=1 and e.idEvento=ed.idEvento group by e.idEvento order by e.FechaInicio DESC limit 10";
            }
        } elseif ($admin['Admin'] == 1 || $representante) {
            $sentencia = "select e.idEvento, e.Nombre, e.FechaInicio, t.Nombre as 'Tipo', e.CupoMaximo from evento e, tipoevento t, eventodistrito ed where e.idTipoEvento=t.idTipoEvento and Habilitado=1 and e.idEvento=ed.idEvento and ed.idDistrito=(select d.idDistrito from distrito d, club c where c.idClub=" . $admin['idClub'] . " and c.idDistrito=d.idDistrito) group by e.idEvento order by e.FechaInicio DESC limit 10";
        } elseif ($adminEventos['Cantidad'] > 0) {
            $sentencia = "select e.idEvento, e.Nombre, e.FechaInicio, t.Nombre as 'Tipo', e.CupoMaximo from evento e, tipoevento t,  eventoadmin ea where e.idEvento=ea.idEvento and ea.idSocio=" . $idSocio . " and e.idTipoEvento=t.idTipoEvento and Habilitado=1 group by e.idEvento order by e.FechaInicio DESC limit 10";
        }

        $conexion->Ejecuto($sentencia);
        //Obtengo los eventos correspondientes al nivel de administraci�n del usuario logueado

        while ($eventosDistrito=$conexion->Siguiente()) {
            $tpl->newBlock("lineaEvento");
            $tpl->assign("idEvento", $eventosDistrito['idEvento']);
            $tpl->assign("nombre", $eventosDistrito['Nombre']);

            $fecha = split(" ", $eventosDistrito['FechaInicio']);
            $fecha2 = split("-", $fecha[0]);

            $tpl->assign("fecha", $fecha2[2] . "/" . $fecha2[1] . "/" . $fecha2[0]);
            $tpl->assign("tipoEvento", $eventosDistrito['Tipo']);

            //Obtengo cantidad de inscriptos
            if ($eventosDistrito['Tipo'] == "E.R.A.U.P.") {
                $distritosO = obtenerOrganizadores($conexion2, $eventosDistrito['idEvento'], "", true);

                $conexion2->Ejecuto("select count(i.idInscripcion) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where i.idEvento=" . $eventosDistrito['idEvento'] . " and i.Aprobado in (0,1,3,4) and i.Eliminado=0 and s.idSocio=i.idSocio and s.idClub=c.idClub and c.idDistrito=d.idDistrito and (i.Reserva is NULL or i.Reserva=0) and d.idDistrito not in (" . $distritosO . ")");
            } else {
                $conexion2->Ejecuto("select count(idInscripcion) as 'Cantidad' from inscripcionevento where idEvento=" . $eventosDistrito['idEvento'] . " and Aprobado in (0,1,3,4) and Eliminado=0");
            }

            $cantInscriptos=$conexion2->Siguiente();
            $tpl->assign("cantInscriptos", $cantInscriptos['Cantidad']);
            $tpl->assign("cupo", $eventosDistrito['CupoMaximo']);

            if (($presidenteA['idSocio'] == $idSocio || $representante) && $eventosDistrito['Tipo'] == "E.R.A.U.P.") {
                $tpl->newBlock("accionesERAUP");
                $tpl->assign("idEvento", $eventosDistrito['idEvento']);
                $tpl->newBlock("accionesAdmin");
                $tpl->assign("idEvento", $eventosDistrito['idEvento']);
                $tpl->assign("url", "eraup");
            } else {
                if ($representante && $eventosDistrito['Tipo'] != "E.R.A.U.P.") {
                    $tpl->newBlock("accionesAdmin");
                    $tpl->assign("idEvento", $eventosDistrito['idEvento']);
                    $tpl->assign("url", "abmeventos");
                } elseif ($adminEventos['Cantidad'] > 0) {
                    $conexion2->Ejecuto("select idSocio from eventoadmin where idSocio=" . $idSocio . " and idEvento=" . $eventosDistrito['idEvento']);
                    $adminE=$conexion2->Siguiente();

                    if ($adminE['idSocio'] == $idSocio) {
                        $tpl->newBlock("accionesAdmin");
                        $tpl->assign("idEvento", $eventosDistrito['idEvento']);

                        if ($eventosDistrito['Tipo'] == "E.R.A.U.P.") {
                            $tpl->assign("url", "eraup");
                            $tpl->newBlock("accionesERAUP");
                            $tpl->assign("idEvento", $eventosDistrito['idEvento']);
                        } else {
                            $tpl->assign("url", "abmeventos");
                        }
                    }
                }
            }
        }

        if ($representante) {
            $tpl->newBlock("nuevoEvento");
        }

        if ($presidenteA['idSocio'] == $idSocio) {
            $tpl->newBlock("nuevoERAUP");
        }
    } else {
        header('Location: modules/auth/login.php');
    }
}

$conexion->Libero(); //Se cierra la conexi�n a la base
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

function obtenerOrganizadores($conex, $idEvento, $idBuscado, $soloID)
{
    $conex->Ejecuto("select d.Nombre, d.idDistrito from distrito d, evento e, eventodistrito ed where ed.idEvento=" . $idEvento . " and ed.idDistrito=d.idDistrito and e.idEvento="  . $idEvento . " order by Nombre ASC");
    $cantDistritos=$conex->Tamano();

    if ($cantDistritos>1) {
        $contar=1;

        if ($soloID) {
            $nombreDistrito = "";
        } else {
            $nombreDistrito = "Distritos ";
        }

        while ($distritos=$conex->Siguiente()) {
            if ($idBuscado != "" && $distritos['idDistrito'] == $idBuscado) {
                return "SI";
            } else {
                if ($contar<$cantDistritos) {
                    if ($soloID) {
                        $nombreDistrito .= $distritos['idDistrito'] . ",";
                    } else {
                        $nombreDistrito .= $distritos['Nombre'] . " / ";
                    }
                } else {
                    if ($soloID) {
                        $nombreDistrito .= $distritos['idDistrito'];
                    } else {
                        $nombreDistrito .= $distritos['Nombre'];
                    }
                }
            }

            $contar++;
        }
    } else {
        $distritoO=$conex->Siguiente();

        if ($idBuscado != "" && $distritoO['idDistrito'] == $idBuscado) {
            return "SI";
        } else {
            if ($soloID) {
                $nombreDistrito = $distritoO['idDistrito'];
            } else {
                $nombreDistrito = "Distrito " . $distritoO['Nombre'];
            }
        }
    }

    return $nombreDistrito;
}
