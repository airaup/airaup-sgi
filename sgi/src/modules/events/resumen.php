<?php
ini_set("display_errors", 0);
include("../../config/config.php");
include("../../helpers/conexionDB.php");
session_start(); //Se inicia la sesi�n
$obj_con=new conectar;

date_default_timezone_set('America/Argentina/Buenos_Aires');

include("../../lib/class.TemplatePower.inc.php"); //Usando Template Power

$tpl=new TemplatePower("views/resumen.html");
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

        $idEvento = $_GET['id'];

        $conexion->Ejecuto("select Nombre, FechaInicioInscripcion, FechaFinInscripcion, FechaInicioInscripcion2, FechaFinInscripcion2 from evento where idEvento=" . $idEvento);
        $eraup=$conexion->Siguiente();

        $tpl->newBlock("titulo");
        $tpl->assign("evento", $eraup['Nombre']);

        $hoy = date("Y-m-d H:i:s");

        if ($hoy >= $eraup['FechaInicioInscripcion'] && $hoy <= $eraup['FechaFinInscripcion']) {
            $tpl->assign("fase", "I");
        } elseif ($hoy > $eraup['FechaFinInscripcion'] && $hoy < $eraup['FechaInicioInscripcion2']) {
            $tpl->assign("fase", "stand by entre fases");
        } elseif ($hoy >= $eraup['FechaInicioInscripcion2'] && $hoy <= $eraup['FechaFinInscripcion2']) {
            $tpl->assign("fase", "II");
        } elseif ($hoy > $eraup['FechaFinInscripcion2']) {
            $tpl->assign("fase", "inscripci�n cerrada");
        } else {
            $tpl->assign("fase", "pendiente de apertura");
        }

        // Cantidad por distrito
        $conexion->Ejecuto("select d.idDistrito, d.Nombre as 'Distrito', a.CupoReservado, a.Inscriptos from distrito d, asistenciaeraup a where a.idEvento="  . $idEvento . " and a.idDistrito=d.idDistrito order by d.Nombre ASC");

        while ($cantID=$conexion->Siguiente()) {
            $conexion2->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where i.idSocio=s.idSocio and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito=" . $cantID['idDistrito'] . " and i.Reserva=1 and i.aprobado<>2 and i.Eliminado=0 and i.idEvento=" . $idEvento);
            $cantReserva=$conexion2->Siguiente();

            if ($cantReserva['Cantidad'] > 0) {
                $totalI = $cantID['Inscriptos'] - $cantReserva['Cantidad'];

                if ($totalI < 0) {
                    $totalI = 0;
                }
            } else {
                $totalI = $cantID['Inscriptos'];
            }

            $tpl->newBlock("bloqueInscripciones");
            $tpl->assign("distrito", $cantID['Distrito']);
            $tpl->assign("inscripciones", $totalI);
            $tpl->assign("reserva", $cantID['CupoReservado']);
        }

        // Cantidad de Rotarios y Extranjeros
        $conexion->Ejecuto("select sum(case when t.Nombre='Rotary' and d.Nombre<>'Otro' and i.Reserva=0 then 1 else 0 end) as 'CantidadR', sum(case when d.Nombre='Otro' and i.Reserva=0 then 1 else 0 end) as 'CantidadE', sum(case when i.Reserva=1 then 1 else 0 end) as 'CantidadRes' from inscripcionevento i, tiporueda t, distrito d, socio s, club c where s.idClub=c.idClub and c.idDistrito=d.idDistrito and i.idSocio=s.idSocio and s.idTipoRueda=t.idTipoRueda and i.Aprobado<>2 and i.Eliminado=0 and i.idEvento=" . $idEvento);
        $cantRE=$conexion->Siguiente();

        $tpl->newBlock("bloqueRE");

        if ($cantRE['CantidadR'] == null) {
            $tpl->assign("rotarios", "0");
        } else {
            $tpl->assign("rotarios", $cantRE['CantidadR']);
        }

        if ($cantRE['CantidadE'] == null) {
            $tpl->assign("extranjeros", "0");
        } else {
            $tpl->assign("extranjeros", $cantRE['CantidadE']);
        }

        if ($cantRE['CantidadRes'] == null) {
            $tpl->assign("reserva", "0");
        } else {
            $tpl->assign("reserva", $cantRE['CantidadRes']);
        }

        // Resumen financiero
        $conexion->Ejecuto("select sum(i.Monto) as 'Total', m.Nombre as 'Moneda' from inscripcionevento i, moneda m where i.idMoneda=m.idMoneda and i.idEvento=" . $idEvento . " and i.Aprobado<>2 and i.Eliminado=0 group by m.Nombre");
        $cant=$conexion->Tamano();

        if ($cant > 0) {
            $tpl->newBlock("tablaFinanzas");

            while ($cantMoneda=$conexion->Siguiente()) {
                $tpl->newBlock("bloqueMoneda");
                $tpl->assign("moneda", $cantMoneda['Moneda']);
                $tpl->assign("monto", $cantMoneda['Total']);
            }
        }

        // Cantidad por distrito sin pagar
        $conexion->Ejecuto("select d.Nombre as 'Distrito', sum(e.Costo - i.Monto) as 'Deuda' from inscripcionevento i, distrito d, socio s, club c, evento e where s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre<>'Otro' and i.idSocio=s.idSocio and i.idEvento=e.idEvento and s.idTipoRueda=2 and i.idEvento=" . $idEvento . " and i.Aprobado<>2 and i.Eliminado=0 group by d.Nombre ASC");
        $cant=$conexion->Tamano();

        if ($cant > 0) {
            $tpl->newBlock("tablaFaltantes");

            while ($cantFaltantes=$conexion->Siguiente()) {
                $tpl->newBlock("bloqueFaltantes");
                $tpl->assign("distrito", $cantFaltantes['Distrito']);
                $tpl->assign("inscripciones", $cantFaltantes['Deuda']);
            }
        }

        // Cantidad de Rotarios y Extranjeros sin pagar
        $conexion->Ejecuto("select sum(e.Costo - i.Monto) as 'Rotarios' from inscripcionevento i, tiporueda t, socio s, evento e, club c, distrito d where s.idTipoRueda=t.idTipoRueda and e.idEvento=i.idEvento and t.Nombre='Rotary' and i.idSocio=s.idSocio and i.Aprobado<>2 and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre<>'Otro' and i.Eliminado=0 and i.idEvento=" . $idEvento);
        $cantRotarios=$conexion->Siguiente();

        if ($cantRotarios['Rotarios'] > 0) {
            if ($cant == 0) {
                $tpl->newBlock("tablaFaltantes");
            }

            $tpl->newBlock("bloqueFaltantes");
            $tpl->assign("distrito", "Rotarios");
            $tpl->assign("inscripciones", $cantRotarios['Rotarios']);
        }

        $conexion->Ejecuto("select sum(e.Costo - i.Monto) as 'Extranjeros' from inscripcionevento i, socio s, club c, distrito d, evento e where c.idClub=s.idClub and c.idDistrito=d.idDistrito and i.idSocio=s.idSocio and e.idEvento=i.idEvento and i.Aprobado<>2 and i.Eliminado=0 and d.Nombre='Otro' and i.idEvento=" . $idEvento);
        $cantExtranjeros=$conexion->Siguiente();

        if ($cantExtranjeros['Extranjeros'] > 0) {
            if ($cant == 0 && $cantRotarios['Rotarios'] == 0) {
                $tpl->newBlock("tablaFaltantes");
            }

            $tpl->newBlock("bloqueFaltantes");
            $tpl->assign("distrito", "Extranjeros");
            $tpl->assign("inscripciones", $cantExtranjeros['Extranjeros']);
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
