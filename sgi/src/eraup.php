<?php
ini_set("display_errors", 0);
include("config.php");
require_once("conexionDB.php");
require 'class.phpmailer.php';
require 'class.smtp.php';
session_start(); //Se inicia la sesi�n
$obj_con=new conectar;

date_default_timezone_set('America/Argentina/Buenos_Aires');

require_once("class.TemplatePower.inc.php"); //Usando Template Power

$tpl=new TemplatePower("eraup.html");
    $tpl->prepare();

$conexion= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());
$conexion2= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());

$idSocio = $_SESSION['usuario'];
$idPeriodoActual = obtenerPeriodoActual($conexion);

if ($idSocio == "") {
    header('Location: login.php');
} else {
    $accion=$_GET['id'];

    if ($accion == "") {
        $accion=$_POST['accion'];
    }

    $entra = false;

    if (is_numeric($accion)) {
        $conexion->Ejecuto("select idSocio from eventoadmin where idSocio=" . $idSocio . " and idEvento=" . $accion);
        $adminE=$conexion->Siguiente();
    } elseif ($accion == "aceptar") {
        $entra = true;
    }

    $conexion->Ejecuto("select h.idSocio from historialcargoairaup h, cargoairaup c where h.idSocio=" . $idSocio . " and c.Nombre='Presidente' and c.idCargoAIRAUP=h.idCargoAIRAUP and h.idPeriodo=" . $idPeriodoActual);
    $presidenteA=$conexion->Siguiente();

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

            $tpl->newBlock("menuAprobacion");
            $tpl->newBlock("menuCuadroSocial");
            $tpl->newBlock("menuStats");
            break;
        }
    }

    if ($presidenteA['idSocio'] == $idSocio && !$presidente && !$representante) {
        $tpl->newBlock("menuStats");
    }

    if ($representante || $adminE['idSocio'] != "" || $entra || $presidenteA['idSocio'] == $idSocio) {
        $tpl->newBlock("menuEventos");

        if ($accion == "n") {
            $tpl->newBlock("evento");
            $tpl->assign("accion", "Nuevo");

            $conexion->Ejecuto("select idDistrito, Nombre from distrito where Nombre not in ('Otro') order by Nombre ASC");

            while ($distrito=$conexion->Siguiente()) {
                $tpl->newBlock("comboDistrito");
                $tpl->assign("valor", $distrito['idDistrito']);
                $tpl->assign("opcion", $distrito['Nombre']);
            }

            $conexion->Ejecuto("select idMoneda, Nombre from moneda order by Nombre ASC");

            while ($moneda=$conexion->Siguiente()) {
                $tpl->newBlock("comboMonedaTicket");
                $tpl->assign("valor", $moneda['idMoneda']);
                $tpl->assign("opcion", $moneda['Nombre']);
            }

            if ($presidenteA['idSocio'] == $idSocio) {
                $tpl->newBlock("seleccionarAdmins");
            }

            $tpl->newBlock("actividad1");
            $tpl->newBlock("actividad2");
            $tpl->newBlock("actividad3");
            $tpl->newBlock("actividad4");
            $tpl->newBlock("actividad5");
        } elseif (is_numeric($accion)) {
            $conexion->Ejecuto("select e.Nombre, e.FechaInicio, e.FechaFin, e.FechaInicioInscripcion, e.FechaFinInscripcion, e.FechaInicioInscripcion2, e.FechaFinInscripcion2, e.PorcentajeRotarios1, e.PorcentajeExtranjeros1, e.PorcentajeRotarios2, e.PorcentajeExtranjeros2, e.Reserva, e.Ubicacion, e.Costo, e.idMoneda, e.CupoMaximo, ed.idDistrito as 'Distrito' from evento e, eventodistrito ed where e.idEvento=" . $accion . " and e.idEvento=ed.idEvento");
            $cantDistritosO=$conexion->Tamano();

            if ($cantDistritosO>1) {
                for ($x=1;$x<=$cantDistritosO;$x++) {
                    $evento=$conexion->Siguiente();
                    $distritos[$x]= $evento['Distrito'];
                }
            } else {
                $evento=$conexion->Siguiente();
                $distritos[1]= $evento['Distrito'];
            }

            $hoy = date("Y-m-d H:i:s");

            $tpl->newBlock("evento");
            $tpl->assign("accion", "Editar");
            $tpl->assign("idEvento", $accion);
            $tpl->assign("reservaActual", $evento['Reserva']);
            $tpl->assign("cupoActual", $evento['CupoMaximo']);

            $tpl->assign("nombre", $evento['Nombre']);

            $fecha = split(" ", $evento['FechaInicio']);
            $fecha2 = split("-", $fecha[0]);

            $tpl->assign("fechaI", $fecha2[2] . "/" . $fecha2[1] . "/" . $fecha2[0]);

            $horaI = split(":", $fecha[1]);
            $tpl->assign("horaIH", $horaI[0] . ":" . $horaI[1]);

            $fecha = split(" ", $evento['FechaFin']);
            $fecha2 = split("-", $fecha[0]);

            $tpl->assign("fechaF", $fecha2[2] . "/" . $fecha2[1] . "/" . $fecha2[0]);

            $horaF = split(":", $fecha[1]);
            $tpl->assign("horaFH", $horaF[0] . ":" . $horaF[1]);

            $fecha = split(" ", $evento['FechaInicioInscripcion']);
            $fecha2 = split("-", $fecha[0]);

            $tpl->assign("fechaII", $fecha2[2] . "/" . $fecha2[1] . "/" . $fecha2[0]);

            $horaII = split(":", $fecha[1]);
            $tpl->assign("horaIIH", $horaII[0] . ":" . $horaII[1]);

            $fecha = split(" ", $evento['FechaFinInscripcion']);
            $fecha2 = split("-", $fecha[0]);

            $tpl->assign("fechaIF", $fecha2[2] . "/" . $fecha2[1] . "/" . $fecha2[0]);

            $horaFI = split(":", $fecha[1]);
            $tpl->assign("horaIFH", $horaFI[0] . ":" . $horaFI[1]);

            $fecha = split(" ", $evento['FechaInicioInscripcion2']);
            $fecha2 = split("-", $fecha[0]);

            $tpl->assign("fechaSI", $fecha2[2] . "/" . $fecha2[1] . "/" . $fecha2[0]);

            $horaII = split(":", $fecha[1]);
            $tpl->assign("horaSIH", $horaII[0] . ":" . $horaII[1]);

            $fecha = split(" ", $evento['FechaFinInscripcion2']);
            $fecha2 = split("-", $fecha[0]);

            $tpl->assign("fechaSF", $fecha2[2] . "/" . $fecha2[1] . "/" . $fecha2[0]);

            $horaFI = split(":", $fecha[1]);
            $tpl->assign("horaSFH", $horaFI[0] . ":" . $horaFI[1]);

            $tpl->assign("porcentajeR1", $evento['PorcentajeRotarios1']);
            $tpl->assign("porcentajeR2", $evento['PorcentajeRotarios2']);
            $tpl->assign("porcentajeE1", $evento['PorcentajeExtranjeros1']);
            $tpl->assign("porcentajeE2", $evento['PorcentajeExtranjeros2']);

            $tpl->assign("ubicacion", $evento['Ubicacion']);
            $tpl->assign("costo", $evento['Costo']);
            $tpl->assign("reserva", $evento['Reserva']);
            $tpl->assign("cupo", $evento['CupoMaximo']);

            if ($hoy <= $evento['FechaFinInscripcion']) {
                $tpl->assign("deshabilitado", "disabled='disabled'");
            }

            $conexion->Ejecuto("select idMoneda, Nombre from moneda order by Nombre ASC");

            while ($moneda=$conexion->Siguiente()) {
                $tpl->newBlock("comboMonedaTicket");
                $tpl->assign("valor", $moneda['idMoneda']);
                $tpl->assign("opcion", $moneda['Nombre']);

                if ($moneda['idMoneda'] == $evento['idMoneda']) {
                    $tpl->assign("seleccionado", "selected='selected'");
                }
            }

            $conexion->Ejecuto("select idDistrito, Nombre from distrito where Nombre not in ('Otro') order by Nombre ASC");

            $distritosAdmin = "";

            while ($distrito=$conexion->Siguiente()) {
                $tpl->newBlock("comboDistrito");
                $tpl->assign("valor", $distrito['idDistrito']);
                $tpl->assign("opcion", $distrito['Nombre']);

                for ($x=1;$x<=$cantDistritosO;$x++) {
                    if ($distrito['idDistrito'] == $distritos[$x]) {
                        $tpl->assign("seleccionado", "selected='selected'");
                        $distritosAdmin .= $distrito['idDistrito'] . ",";
                    }
                }
            }

            if ($presidenteA['idSocio'] == $idSocio) {
                $tpl->newBlock("seleccionarAdmins");

                $distritosA = substr($distritosAdmin, 0, -1);

                $conexion->Ejecuto("select s.idSocio, s.Nombres, s.Apellidos from socio s, club c, distrito d where s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito in (" . $distritosA . ") order by Nombres, Apellidos ASC");

                while ($admins=$conexion->Siguiente()) {
                    $tpl->newBlock("comboAdmins");
                    $tpl->assign("valor", $admins['idSocio']);
                    $tpl->assign("opcion", $admins['Nombres'] . " " . $admins['Apellidos']);

                    $conexion2->Ejecuto("select idSocio from eventoadmin where idEvento=" . $accion);

                    while ($selec=$conexion2->Siguiente()) {
                        if ($admins['idSocio'] == $selec['idSocio']) {
                            $tpl->assign("seleccionado", "selected='selected'");
                            break;
                        }
                    }
                }
            }

            $conexion->Ejecuto("select * from servicioeraup where idEvento=" . $accion);
            $x = 0;

            while ($servicio=$conexion->Siguiente()) {
                $x += 1;
                $tpl->newBlock("actividad" . $x);
                $tpl->assign("actividad" . $x, $servicio['Nombre']);
            }

            if ($x < 5) {
                while ($x <= 5) {
                    $x += 1;
                    $tpl->newBlock("actividad" . $x);
                }
            }
        } elseif ($accion == "aceptar") { //Aceptar formulario
            $idEvento=$_POST['idEvento'];
            $nombre=$_POST['nombre'];
            $fechaI=$_POST['fechaI'];
            $fechaF=$_POST['fechaF'];
            $horaI=$_POST['horaIH'];
            $horaF=$_POST['horaFH'];
            $fechaInicioInsc=$_POST['fechaII'];
            $fechaFinInsc=$_POST['fechaIF'];
            $horaIIH=$_POST['horaIIH'];
            $horaIFH=$_POST['horaIFH'];
            $fechaSI=$_POST['fechaSI'];
            $fechaSF=$_POST['fechaSF'];
            $horaSIH=$_POST['horaSIH'];
            $horaSFH=$_POST['horaSFH'];
            $porR1=$_POST['porcentajeR1'];
            $porR2=$_POST['porcentajeR2'];
            $porE1=$_POST['porcentajeE1'];
            $porE2=$_POST['porcentajeE2'];
            $costo=$_POST['costo'];
            $monedaTicket=$_POST['monedaTicket'];
            $reserva=$_POST['reserva'];
            $cupo=$_POST['cupo'];
            $ubicacion=$_POST['ubicacion'];

            $fechaInicio = split("/", $fechaI);
            $fechaFin = split("/", $fechaF);
            $fechaInsI = split("/", $fechaInicioInsc);
            $fechaInsF = split("/", $fechaFinInsc);
            $fechaSelI = split("/", $fechaSI);
            $fechaSelF = split("/", $fechaSF);

            if ($idEvento == "") { //Si no llega el id es un evento nuevo
                $conexion->Ejecuto("select idTipoEvento from tipoevento where Nombre='E.R.A.U.P.'");
                $tipoEvento=$conexion->Siguiente();

                //Inserto evento
                $conexion->Ejecuto("insert into evento (Nombre, FechaInicio, FechaFin, FechaInicioInscripcion, FechaFinInscripcion, FechaInicioInscripcion2, FechaFinInscripcion2, PorcentajeRotarios1, PorcentajeRotarios2, PorcentajeExtranjeros1, PorcentajeExtranjeros2, Reserva, Ubicacion, Costo, idMoneda, idTipoEvento, CupoMaximo, Habilitado) values ('" . $nombre . "','" . $fechaInicio[2] . "-" . $fechaInicio[1] . "-" . $fechaInicio[0] . " " . $horaI . ":00','" . $fechaFin[2] . "-" . $fechaFin[1] . "-" . $fechaFin[0] . " " . $horaF . ":00','" . $fechaInsI[2] . "-" . $fechaInsI[1] . "-" . $fechaInsI[0] . " " . $horaIIH . ":00','" . $fechaInsF[2] . "-" . $fechaInsF[1] . "-" . $fechaInsF[0] . " " . $horaIFH . ":00','" . $fechaSelI[2] . "-" . $fechaSelI[1] . "-" . $fechaSelI[0] . " " . $horaSIH . ":00','" . $fechaSelF[2] . "-" . $fechaSelF[1] . "-" . $fechaSelF[0] . " " . $horaSFH . ":00'," . $porR1 . "," . $porR2 . "," . $porE1 . "," . $porE2 . "," . $reserva . ",'" . $ubicacion . "'," . $costo . "," . $monedaTicket . "," . $tipoEvento['idTipoEvento'] . "," . $cupo . ",1)");

                //Obtengo el ID del evento insertado
                $conexion->Ejecuto("select idEvento from evento where Habilitado=1 order by idEvento DESC");
                $ultimoID=$conexion->Siguiente();

                $conexion->Ejecuto("insert into transporteevento (Nombre, Costo, idMoneda, idEvento) values ('Auto particular',0,1," . $ultimoID['idEvento'] . ")");
                $conexion->Ejecuto("insert into transporteevento (Nombre, Costo, idMoneda, idEvento) values ('Bus',0,1," . $ultimoID['idEvento'] . ")");
                $conexion->Ejecuto("insert into transporteevento (Nombre, Costo, idMoneda, idEvento) values ('Sin definir',0,1," . $ultimoID['idEvento'] . ")");

                foreach ($_POST['distrito'] as $check) {
                    $conexion->Ejecuto("insert into eventodistrito (idEvento, idDistrito) values (" . $ultimoID['idEvento'] . "," . $check . ")");
                }

                $distritosOrg = obtenerOrganizadores($conexion, $ultimoID['idEvento'], "", false);

                foreach ($_POST['admins'] as $check) {
                    $conexion->Ejecuto("insert into eventoadmin (idEvento, idSocio) values (" . $ultimoID['idEvento'] . "," . $check . ")");
                    $conexion->Ejecuto("select Email from socio where idSocio=" . $check);
                    $correoA = $conexion->Siguiente();

                    enviarCorreo($correoA['Email'], utf8_encode("Potestad de administraci�n"), utf8_encode("Fuiste designado como administrador del evento " . $nombre . " - " . $distritosOrg . "."));
                }

                $x = 0;

                while ($x <= 5) {
                    $x += 1;

                    if ($_POST['actividad' . $x] != "") {
                        $conexion->Ejecuto("insert into servicioeraup (idEvento, Nombre) values (" . $ultimoID['idEvento'] . ",'" . $_POST['actividad' . $x] . "')");
                    }
                }

                $idOrganizador = obtenerOrganizadores($conexion, $ultimoID['idEvento'], "", true);

                calcularCuposDistrito($conexion, $conexion2, $ultimoID['idEvento'], $idOrganizador);
            } else { //De lo contrario se actualiza
                //Obtengo informaci�n actual del evento para comparar
                $conexion->Ejecuto("select e.Nombre, e.FechaInicio, e.FechaFin, e.FechaInicioInscripcion, e.FechaFinInscripcion, e.FechaInicioInscripcion2, e.FechaFinInscripcion2, e.PorcentajeRotarios1, e.PorcentajeRotarios2, e.PorcentajeExtranjeros1, e.PorcentajeExtranjeros2, e.Reserva, e.Ubicacion, e.Costo, m.Nombre as 'MonedaTicket', t.Nombre as 'TipoEvento', e.idTipoEvento, e.CupoMaximo from evento e, tipoevento t, moneda m where e.idEvento=" . $idEvento . " and e.idTipoEvento=t.idTipoEvento and e.idMoneda=m.idMoneda");
                $datosAnteriores=$conexion->Siguiente();

                $conexion->Ejecuto("select idTipoEvento from tipoevento where Nombre='E.R.A.U.P.'");
                $tipoEvento=$conexion->Siguiente();

                //Actualizo evento
                $conexion->Ejecuto("update evento set Nombre='" . $nombre . "', FechaInicio='" . $fechaInicio[2] . "-" . $fechaInicio[1] . "-" . $fechaInicio[0] . " " . $horaI . ":00', FechaFin='" . $fechaFin[2] . "-" . $fechaFin[1] . "-" . $fechaFin[0] . " " . $horaF . ":00',FechaInicioInscripcion='" . $fechaInsI[2] . "-" . $fechaInsI[1] . "-" . $fechaInsI[0] . " " . $horaIIH . ":00',FechaFinInscripcion='" . $fechaInsF[2] . "-" . $fechaInsF[1] . "-" . $fechaInsF[0] . " " . $horaIFH . ":00',FechaInicioInscripcion2='" . $fechaSelI[2] . "-" . $fechaSelI[1] . "-" . $fechaSelI[0] . " " . $horaSIH . ":00',FechaFinInscripcion2='" . $fechaSelF[2] . "-" . $fechaSelF[1] . "-" . $fechaSelF[0] . " " . $horaSFH . ":00', PorcentajeRotarios1=" . $porR1 . ", PorcentajeRotarios2=" . $porR2 . ", PorcentajeExtranjeros1=" . $porE1 . ", PorcentajeExtranjeros2=" . $porE2 . ", Reserva=" . $reserva . ", Ubicacion='" . $ubicacion . "', Costo=" . $costo . ",idMoneda=" . $monedaTicket . ", idTipoEvento=" . $tipoEvento['idTipoEvento'] . ", CupoMaximo=" . $cupo . " where idEvento=" . $idEvento);

                $conexion->Ejecuto("delete from eventodistrito where idEvento=" . $idEvento);

                foreach ($_POST['distrito'] as $check) {
                    $conexion->Ejecuto("insert into eventodistrito (idEvento, idDistrito) values (" . $idEvento . "," . $check . ")");
                }

                /*$conexion->Ejecuto("delete from servicioeraup where idEvento=" . $idEvento);

                $x = 0;

                while ($x <= 5){
                    $x += 1;

                    if ($_POST['actividad' . $x] != ""){
                        $conexion->Ejecuto("insert into servicioeraup (idEvento, Nombre) values (" . $idEvento . ",'" . $_POST['actividad' . $x] . "')");
                    }
                }*/

                if ($presidenteA['idSocio'] == $idSocio) {
                    $distritosOrg = obtenerOrganizadores($conexion, $idEvento, "", false);

                    $conexion->Ejecuto("select idSocio from eventoadmin where idEvento=" . $idEvento); //Obtengo admins actuales
                    $contar = 0;

                    while ($adminA=$conexion->Siguiente()) {
                        $arrayAdmin[$contar]['idSocio'] = $adminA['idSocio'];
                        $arrayAdmin[$contar]['Existe'] = 0;
                        $contar++;
                    }

                    $conexion->Ejecuto("delete from eventoadmin where idEvento=" . $idEvento); //Elimino admins actuales

                    foreach ($_POST['admins'] as $check) {
                        $enviar = true;

                        for ($x=0;$x<$contar;$x++) {
                            if ($arrayAdmin[$x]['idSocio'] == $check) {
                                $arrayAdmin[$x]['Existe'] = 1;
                                $enviar = false;
                                break;
                            }
                        }

                        $conexion->Ejecuto("insert into eventoadmin (idEvento, idSocio) values (" . $idEvento . "," . $check . ")");

                        if ($enviar) {
                            $conexion->Ejecuto("select Email from socio where idSocio=" . $check);
                            $correoA=$conexion->Siguiente();

                            enviarCorreo($correoA['Email'], utf8_encode("Potestad de administraci�n"), utf8_encode("Fuiste designado como administrador del evento " . $nombre . " - " . $distritosOrg . "."));
                        }
                    }

                    for ($x=0;$x<$contar;$x++) {
                        if ($arrayAdmin[$x]['Existe'] == 0) {
                            $conexion->Ejecuto("select Email from socio where idSocio=" . $arrayAdmin[$x]['idSocio']);
                            $correoA=$conexion->Siguiente();

                            enviarCorreo($correoA['Email'], utf8_encode("Potestad de administraci�n"), utf8_encode("Se te quit� la potestad de administraci�n sobre el evento " . $nombre . " - (" . $distritosOrg . ")."));
                        }
                    }
                }

                $conexion->Ejecuto("select count(idInscripcion) as 'Cantidad' from inscripcionevento where idEvento=" . $idEvento . " and Aprobado<>2 and Eliminado=0");
                $inscriptosTotal=$conexion->Siguiente();

                if ($cupo > $datosAnteriores['CupoMaximo']) {
                    $cantidad = $cupo - $datosAnteriores['CupoMaximo'];
                    movimientosERAUP($conexion, $conexion2, $idEvento, $cantidad, $idPeriodoActual);
                }
            }

            header('Location: eventos.php');
        }
    } else {
        header('Location: login.php');
    }
}

$conexion->Libero(); //Se cierra la conexi�n a la base
$conexion2->Libero(); //Se cierra la conexi�n a la base
$tpl->printToScreen(); //Se manda todo al HTML usando TPL

function calcularCuposDistrito($conexion, $conexion2, $idEvento, $idOrganizador)
{
    $conexion->Ejecuto("select PorcentajeRotarios1, PorcentajeExtranjeros1, Reserva, CupoMaximo from evento where idEvento=" . $idEvento);
    $valores=$conexion->Siguiente();

    $cupoRotario = ($valores['PorcentajeRotarios1'] * $valores['CupoMaximo']) / 100;
    $cupoExtranjeros = ($valores['PorcentajeExtranjeros1'] * $valores['CupoMaximo']) / 100;
    $disponible = $valores['CupoMaximo'] - $valores['Reserva'] - $cupoRotario - $cupoExtranjeros;

    $conexion->Ejecuto("select sum(Inscriptos) as 'Inscriptos' from asistenciaeraup where idDistrito not in (" . $idOrganizador . ") and idEvento<" . $idEvento . " group by idEvento order by idAsistenciaERAUP DESC LIMIT 3");

    //Asistencia global a los �ltimos 3 ERAUP
    $totalEraup3 = 0; //Antepentultimo ERAUP
    $totalEraup2 = 0; //Penultimo ERAUP
    $totalEraup1 = 0; //Ultimo ERAUP

    $x = 1;

    while ($asistenciaGlobal=$conexion->Siguiente()) {
        if ($x == 1) {
            $totalEraup1 = $asistenciaGlobal['Inscriptos'];
        } elseif ($x == 2) {
            $totalEraup2 = $asistenciaGlobal['Inscriptos'];
        } elseif ($x == 3) {
            $totalEraup3 = $asistenciaGlobal['Inscriptos'];
        }

        $x++;
    }

    $conexion->Ejecuto("select count(s.idSocio) as 'Registro' from socio s, club c, distrito d where s.idTipoRueda=2 and s.Activo=1 and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito not in (" . $idOrganizador . ") and d.Nombre<>'Otro'");
    $totalSGI=$conexion->Siguiente();

    $conexion->Ejecuto("select idDistrito, Nombre from distrito where Nombre<>'Otro' and idDistrito not in (" . $idOrganizador . ")");

    $totalG = 0;

    while ($distrito=$conexion->Siguiente()) {
        $conexion2->Ejecuto("select Inscriptos from asistenciaeraup where idEvento<" . $idEvento . " and idDistrito=" . $distrito['idDistrito'] . " order by idAsistenciaERAUP DESC LIMIT 3");

        //Asistencia del distrito en cuestion
        $eraup3 = 0; //Antepentultimo ERAUP
        $eraup2 = 0; //Penultimo ERAUP
        $eraup1 = 0; //Ultimo ERAUP

        $x = 1;

        while ($asistencias=$conexion2->Siguiente()) {
            if ($x == 1) {
                $eraup1 = $asistencias['Inscriptos'];
            } elseif ($x == 2) {
                $eraup2 = $asistencias['Inscriptos'];
            } elseif ($x == 3) {
                $eraup3 = $asistencias['Inscriptos'];
            }

            $x++;
        }

        //Cantidad de Rotaractianos registrados del distrito en cuesti�n
        $conexion2->Ejecuto("select count(s.idSocio) as 'Membresia' from socio s, club c, distrito d where s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito=" . $distrito['idDistrito'] . " and s.idTipoRueda=2");
        $membresia=$conexion2->Siguiente();

        $porcEraup3 = ($eraup3 * 100) / $totalEraup3;
        $porcEraup2 = ($eraup2 * 100) / $totalEraup2;
        $porcEraup1 = ($eraup1 * 100) / $totalEraup1;
        $porcMembresia = ($membresia['Membresia'] * 100) / $totalSGI['Registro'];

        $calculo = (0.2 * $porcEraup3 / 100) + (0.2 * $porcEraup2 / 100) + (0.3 * $porcEraup1 / 100) + (0.3 * $porcMembresia / 100);
        $cuposReservados = round($disponible * $calculo);

        $conexion2->Ejecuto("insert into asistenciaeraup (idDistrito, idEvento, CupoReservado, Inscriptos) values (" . $distrito['idDistrito'] . "," . $idEvento . "," . $cuposReservados . ",0)");
    }

    //INSERTAR FILAS PARA LOS ORGANIZADORES CON CUPORESERVADO=0
    $organizadoresSep = split(",", $idOrganizador);

    for ($x=0;$x<count($organizadoresSep);$x++) {
        $conexion2->Ejecuto("insert into asistenciaeraup (idDistrito, idEvento, CupoReservado, Inscriptos) values (" . $organizadoresSep[$x] . "," . $idEvento . ",0,0)");
    }
}

function movimientosERAUP($conexion, $conexion2, $idEvento, $cantidad, $idPeriodoActual)
{
    $conexion->Ejecuto("select * from evento where idEvento=" . $idEvento);
    $infoEvento=$conexion->Siguiente();

    $hoy = date("Y-m-d H:i:s");

    $conexion->Ejecuto("select i.*, e.Nombre as 'NomEvento' from inscripcionevento i, evento e where i.idEvento=" . $idEvento . " and i.Eliminado=0 and i.Aprobado in (3,4) and i.idEvento=e.idEvento order by i.FechaInscripcion ASC");

    $x = 0;

    while ($x < $cantidad) { //Recorro todos los que est�n en lista de espera dentro del l�mite
        $entra = false;
        $ingreso=$conexion->Siguiente();

        $conexion2->Ejecuto("select PorcentajeRotarios2, PorcentajeExtranjeros2, CupoMaximo, Reserva from evento where idEvento=" . $idEvento);
        $valores=$conexion2->Siguiente();

        $cupoRotario = ($valores['PorcentajeRotarios2'] * $valores['CupoMaximo']) / 100;
        $cupoExtranjeros = ($valores['PorcentajeExtranjeros2'] * $valores['CupoMaximo']) / 100;
        $organizadores = obtenerOrganizadores($conexion2, $idEvento, "", true);

        $conexion2->Ejecuto("select s.idSocio, s.Nombres, s.Apellidos, s.Email, t.Nombre as 'Rueda', d.Nombre as 'Distrito', d.idDistrito, s.idClub from socio s, club c, distrito d, tiporueda t where s.idTipoRueda=t.idTipoRueda and s.idClub=c.idClub and c.idDistrito=d.idDistrito and s.idSocio=" . $ingreso['idSocio']);
        $datosSocio=$conexion2->Siguiente();

        $esOrganizador = obtenerOrganizadores($conexion2, $idEvento, $datosSocio['idDistrito'], false);

        if ($datosSocio['Rueda'] == "Rotaract" && $datosSocio['Distrito'] != "Otro") { //Rotaractiano de AIRAUP
            $entra = true;
        } elseif ($datosSocio['Rueda'] == "Rotary" && $datosSocio['Distrito'] != "Otro") { //Rotario de AIRAUP
            //Se chequea si hay l�mite para Rotarios
            $conexion2->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s where s.idTipoRueda=3 and s.idSocio=i.idSocio and i.Eliminado=0 and i.Aprobado<>2 and i.idEvento=" . $idEvento);
            $cantidadR=$conexion2->Siguiente();

            if ($cupoRotario > 0 && $cantidadR['Cantidad'] < $cupoRotario) { //Existe l�mite y queda lugar Rotarios
                $entra = true;
            } elseif ($cupoRotario == 0) { //No existe l�mite
                $entra = true;
            }
        } elseif ($datosSocio['Distrito'] == "Otro") { //Extranjero
            //Se chequea si hay l�mite para extranjeros
            $conexion2->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre='Otro' and s.idSocio=i.idSocio and i.Eliminado=0 and i.Aprobado<>2 and i.idEvento=" . $idEvento);
            $cantidadE=$conexion2->Siguiente();

            if ($cupoExtranjeros > 0 && $cantidadE['Cantidad'] < $cupoExtranjeros) { //Existe l�mite y queda lugar extranjeros
                $entra = true;
            } elseif ($cupoExtranjeros == 0) { //No existe l�mite
                $entra = true;
            }
        }

        if ($entra) {
            $x++;
            notificarERAUP($conexion2, $ingreso['Aprobado'], $ingreso['idInscripcion'], $idEvento, $idPeriodoActual, $ingreso['NomEvento'], $datosSocio['Email'], $datosSocio['Nombres'] . " " . $datosSocio['Apellidos'], $datosSocio['Distrito'], $datosSocio['idClub']);
        }
    }
}

function notificarERAUP($conexion2, $aprobado, $idInscripcion, $idEvento, $idPeriodoActual, $nomEvento, $correo, $nombreC, $nomDistrito, $idClub)
{
    if ($aprobado == "3") {
        $aprobado = 0;
    } elseif ($aprobado == "4") {
        $aprobado = 1;
    }

    //Update de inscripcion
    $conexion2->Ejecuto("update inscripcionevento set Aprobado=" . $aprobado . " where idInscripcion=" . $idInscripcion);

    if ($aprobado == 0) {
        $mensajeI = utf8_encode("Se liberaron cupos en ") . $nomEvento . utf8_encode(" y saliste de la lista de espera. Tu inscripci�n sigue pendiente de aprobaci�n.");
        $mensajeR = utf8_encode("Se liberaron cupos en "). $nomEvento . " y " . $nombreC . utf8_encode(" sali� de la lista de espera. A�n debes aprobar su inscripci�n.");
    } elseif ($aprobado == 1) {
        $mensajeI = utf8_encode("Se liberaron cupos en ") . $nomEvento . utf8_encode(" y saliste de la lista de espera. Tu inscripci�n fue aprobada previamente por lo que no tenes que realizar ninguna acci�n en el sistema.");
        $mensajeR = utf8_encode("Se liberaron cupos en ") . $nomEvento . " y " . $nombreC . utf8_encode(" sali� de la lista de espera. Su inscripci�n fue aprobada previamente por lo que no tenes que realizar ninguna acci�n en el sistema.");
    }

    //MAIL AL INSCRIPTO
    enviarCorreo($correo, utf8_encode("Saliste de la lista de espera!"), $mensajeI);

    //MAIL AL/LOS RESPONSABLE/S
    $conexion2->Ejecuto("select e.Nombre, ed.idDistrito as 'Distrito', t.Tipo from evento e, tipoevento t, eventodistrito ed where e.idEvento=" . $idEvento . " and e.idTipoEvento=t.idTipoEvento and e.idEvento=ed.idEvento");
    $cantDistritosO=$conexion2->Tamano();

    if ($cantDistritos0 == 1) {
        $evento=$conexion2->Siguiente();
        $distritos=$evento['Distrito'];
    } else {
        for ($x=1;$x<=$cantDistritosO;$x++) {
            $evento=$conexion2->Siguiente();

            if ($x<$cantDistritosO) {
                $distritos.= "'" . $evento['Distrito'] . "',";
            } else {
                $distritos.= "'" . $evento['Distrito'] . "'";
            }
        }
    }

    if ($nomDistrito != "Otro") {
        $sqlResponsable = "select s.Email from socio s, distrito d, club c, cargodistrito ca, historialcargodistrito h where s.idClub=c.idClub and s.idSocio=h.idSocio and ca.Nombre='Representante Distrital' and ca.idCargoDistrito=h.idCargoDistrito and d.Nombre not in ('Otro') and c.idDistrito=(select d1.idDistrito from distrito d1, club c1 where c1.idClub=" . $idClub . " and c1.idDistrito=d1.idDistrito) and h.idPeriodo=" . $idPeriodoActual . " group by s.Email";
    } else {
        $sqlResponsable = "select s.Email from socio s, distrito d, club c, cargodistrito ca, historialcargodistrito h where s.idClub=c.idClub and s.idSocio=h.idSocio and ca.Nombre='Representante Distrital' and ca.idCargoDistrito=h.idCargoDistrito and c.idDistrito in (" . $distritos . ") and h.idPeriodo=" . $idPeriodoActual . " group by s.Email";
    }

    $conexion2->Ejecuto($sqlResponsable);
    $cantResponsables=$conexion2->Tamano();

    if ($cantResponsables > 1) {
        for ($x=1;$x<=$cantResponsables;$x++) {
            $responsable=$conexion2->Siguiente();
            $responsables[$x]= $responsable['Email'];
        }
    } else {
        $responsable=$conexion2->Siguiente();
        $responsables[1] = $responsable['Email'];
    }

    for ($x=1;$x<=$cantResponsables;$x++) {
        if ($responsables[$x] != "") {
            enviarCorreo($responsables[$x], "Cupos liberados en " . $nomEvento, $mensajeR);
        }
    }
}

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

function enviarCorreo($direccion, $asunto, $texto)
{
    if ($direccion != "") {
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
        // $mail->SetFrom('sgi@airaup.org', utf8_encode("Sistema de Gesti�n Integral - AIRAUP"));
        $mail->FromName = utf8_encode("Sistema de Gesti�n Integral - AIRAUP");
        $mail->From = "sgi@airaup.org";
        $mail->Subject = $asunto;
        $mail->MsgHTML($texto . utf8_encode("<br><br>Por favor no respondas este mensaje.<br>Sistema de Gesti�n Integral<br>AIRAUP"));
        $mail->AddAddress($direccion);
        $mail->Send();
    }
}

function obtenerOrganizadores($conexion, $idEvento, $idBuscado, $soloID)
{
    $conexion->Ejecuto("select d.Nombre, d.idDistrito from distrito d, evento e, eventodistrito ed where ed.idEvento=" . $idEvento . " and ed.idDistrito=d.idDistrito and e.idEvento="  . $idEvento . " order by Nombre ASC");
    $cantDistritos=$conexion->Tamano();

    if ($cantDistritos>1) {
        $contar=1;

        if ($soloID) {
            $nombreDistrito = "";
        } else {
            $nombreDistrito = "Distritos ";
        }

        while ($distritos=$conexion->Siguiente()) {
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
        $distritoO=$conexion->Siguiente();

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
