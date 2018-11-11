<?php
ini_set("display_errors", 0);
include("../../config/config.php");
include("../mailer/mailer.php");
include("../../helpers/conexionDB.php");
session_start(); //Se inicia la sesi�n
$obj_con=new conectar;

include("../../lib/class.TemplatePower.inc.php"); //Usando Template Power

$tpl=new TemplatePower("views/abmeventos.html");
$tpl->prepare();

$conexion= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());
$conexion2= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());

$idSocio = $_SESSION['usuario'];
$idPeriodoActual = obtenerPeriodoActual($conexion);

if ($idSocio == "") {
    header('Location: modules/auth/login.php');
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

    if ($representante || $adminE['idSocio'] != "" || $entra) {
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

            $conexion->Ejecuto("select idTipoEvento, Nombre from tipoevento where Nombre not in ('E.R.A.U.P.') order by Nombre ASC");

            while ($tipoEvento=$conexion->Siguiente()) {
                $tpl->newBlock("comboTipoEvento");
                $tpl->assign("valor", $tipoEvento['idTipoEvento']);
                $tpl->assign("opcion", $tipoEvento['Nombre']);
            }

            $conexion->Ejecuto("select idMoneda, Nombre from moneda order by Nombre ASC");

            while ($moneda=$conexion->Siguiente()) {
                $tpl->newBlock("comboMonedaTicket");
                $tpl->assign("valor", $moneda['idMoneda']);
                $tpl->assign("opcion", $moneda['Nombre']);

                $tpl->newBlock("comboMonedaCosto1");
                $tpl->assign("valor", $moneda['idMoneda']);
                $tpl->assign("opcion", $moneda['Nombre']);

                $tpl->newBlock("comboMonedaCosto2");
                $tpl->assign("valor", $moneda['idMoneda']);
                $tpl->assign("opcion", $moneda['Nombre']);

                $tpl->newBlock("comboMonedaCosto3");
                $tpl->assign("valor", $moneda['idMoneda']);
                $tpl->assign("opcion", $moneda['Nombre']);

                $tpl->newBlock("comboMonedaCosto4");
                $tpl->assign("valor", $moneda['idMoneda']);
                $tpl->assign("opcion", $moneda['Nombre']);

                $tpl->newBlock("comboMonedaCosto5");
                $tpl->assign("valor", $moneda['idMoneda']);
                $tpl->assign("opcion", $moneda['Nombre']);
            }

            if ($representante) {
                $tpl->newBlock("seleccionarAdmins");
            }
        } elseif (is_numeric($accion)) {
            $conexion->Ejecuto("select e.Nombre, e.FechaInicio, e.FechaFin, e.FechaInicioInscripcion, e.FechaFinInscripcion, e.Ubicacion, e.Costo, e.idMoneda, e.idTipoEvento, e.CupoMaximo, ed.idDistrito as 'Distrito' from evento e, eventodistrito ed where e.idEvento=" . $accion . " and e.idEvento=ed.idEvento");
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

            $tpl->newBlock("evento");
            $tpl->assign("accion", "Editar");
            $tpl->assign("idEvento", $accion);

            $tpl->assign("nombre", $evento['Nombre']);

            $fecha = split(" ", $evento['FechaInicio']);
            $fecha2 = split("-", $fecha[0]);

            $tpl->assign("fechaI", $fecha2[2] . "/" . $fecha2[1] . "/" . $fecha2[0]);

            $horaI = split(":", $fecha[1]);
            $tpl->assign("horaI", $horaI[0] . ":" . $horaI[1]);

            $fecha = split(" ", $evento['FechaFin']);
            $fecha2 = split("-", $fecha[0]);

            $tpl->assign("fechaF", $fecha2[2] . "/" . $fecha2[1] . "/" . $fecha2[0]);

            $horaF = split(":", $fecha[1]);
            $tpl->assign("horaF", $horaF[0] . ":" . $horaF[1]);

            $fecha = split(" ", $evento['FechaInicioInscripcion']);
            $fecha2 = split("-", $fecha[0]);

            $tpl->assign("fechaII", $fecha2[2] . "/" . $fecha2[1] . "/" . $fecha2[0]);

            $horaII = split(":", $fecha[1]);
            $tpl->assign("horaII", $horaII[0] . ":" . $horaII[1]);

            $fecha = split(" ", $evento['FechaFinInscripcion']);
            $fecha2 = split("-", $fecha[0]);

            $tpl->assign("fechaFI", $fecha2[2] . "/" . $fecha2[1] . "/" . $fecha2[0]);

            $horaFI = split(":", $fecha[1]);
            $tpl->assign("horaFI", $horaFI[0] . ":" . $horaFI[1]);

            $tpl->assign("ubicacion", $evento['Ubicacion']);
            $tpl->assign("costo", $evento['Costo']);
            $tpl->assign("cupo", $evento['CupoMaximo']);

            $transporteEvento="";

            $conexion->Ejecuto("select idTransporteEvento, Nombre, Costo, idMoneda from transporteevento where idEvento=" . $accion);
            $x = 1;
            while ($transporte=$conexion->Siguiente()) {
                if ($transporte['Nombre'] != "Particular") {
                    $tpl->assign("transporte" . $x, $transporte['Nombre']);
                    $tpl->assign("costo" . $x, $transporte['Costo']);
                    $tpl->assign("idTransporte" . $x, $transporte['idTransporteEvento']);

                    $conexion2->Ejecuto("select idMoneda, Nombre from moneda order by Nombre ASC");

                    while ($moneda=$conexion2->Siguiente()) {
                        $tpl->newBlock("comboMonedaCosto" . $x);
                        $tpl->assign("valor", $moneda['idMoneda']);
                        $tpl->assign("opcion", $moneda['Nombre']);

                        if ($transporte['Nombre'] != "Particular") {
                            if ($moneda['idMoneda'] == $transporte['idMoneda']) {
                                $tpl->assign("seleccionado", "selected='selected'");
                            }
                        }
                    }

                    $x++;
                }
            }

            if ($x < 5) {
                for ($x;$x<=5;$x++) {
                    $conexion2->Ejecuto("select idMoneda, Nombre from moneda order by Nombre ASC");

                    while ($moneda=$conexion2->Siguiente()) {
                        $tpl->newBlock("comboMonedaCosto" . $x);
                        $tpl->assign("valor", $moneda['idMoneda']);
                        $tpl->assign("opcion", $moneda['Nombre']);
                    }
                }
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

            $conexion->Ejecuto("select idTipoEvento, Nombre from tipoevento where Nombre not in ('E.R.A.U.P.') order by Nombre ASC");
            while ($tipoEvento=$conexion->Siguiente()) {
                $tpl->newBlock("comboTipoEvento");
                $tpl->assign("valor", $tipoEvento['idTipoEvento']);
                $tpl->assign("opcion", $tipoEvento['Nombre']);

                if ($tipoEvento['idTipoEvento'] == $evento['idTipoEvento']) {
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

            if ($representante) {
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
        } elseif ($accion == "aceptar") { //Aceptar formulario
            $idEvento=$_POST['idEvento'];
            $nombre=$_POST['nombre'];
            $fechaI=$_POST['fechaI'];
            $fechaF=$_POST['fechaF'];
            $horaI=$_POST['horaIH'];
            $horaF=$_POST['horaFH'];
            $fechaInicioInsc=$_POST['fechaInscripcionesI'];
            $fechaFinInsc=$_POST['fechaInscripcionesF'];
            $horaII=$_POST['horaIIH'];
            $horaFI=$_POST['horaFIH'];
            $costo=$_POST['costo'];
            $monedaTicket=$_POST['monedaTicket'];
            $cupo=$_POST['cupo'];
            $tipoEvento=$_POST['tipoEvento'];
            $ubicacion=$_POST['ubicacion'];

            $fechaInicio = split("/", $fechaI);
            $fechaFin = split("/", $fechaF);
            $fechaInicioInscripcion = split("/", $fechaInicioInsc);
            $fechaFinInscripcion = split("/", $fechaFinInsc);

            if ($idEvento == "") { //Si no llega el id es un evento nuevo
                //Inserto evento
                $conexion->Ejecuto("insert into evento (Nombre, FechaInicio, FechaFin, FechaInicioInscripcion, FechaFinInscripcion,  Ubicacion, Costo, idMoneda, idTipoEvento, CupoMaximo, Habilitado) values ('" . $nombre . "','" . $fechaInicio[2] . "-" . $fechaInicio[1] . "-" . $fechaInicio[0] . " " . $horaI . ":00','" . $fechaFin[2] . "-" . $fechaFin[1] . "-" . $fechaFin[0] . " " . $horaF . ":00','" . $fechaInicioInscripcion[2] . "-" . $fechaInicioInscripcion[1] . "-" . $fechaInicioInscripcion[0] . " " . $horaII . ":00','" . $fechaFinInscripcion[2] . "-" . $fechaFinInscripcion[1] . "-" . $fechaFinInscripcion[0] . " " . $horaFI . ":00','" . $ubicacion . "'," . $costo . "," . $monedaTicket . "," . $tipoEvento . "," . $cupo . ",1)");

                //Obtengo el ID del evento insertado
                $conexion->Ejecuto("select idEvento from evento where Habilitado=1 order by idEvento DESC");
                $ultimoID=$conexion->Siguiente();

                foreach ($_POST['distrito'] as $check) {
                    $conexion->Ejecuto("insert into eventodistrito (idEvento, idDistrito) values (" . $ultimoID['idEvento'] . "," . $check . ")");
                }

                //Obtengo las opciones de transporte
                for ($i=1;$i<=5;$i++) {
                    $transporte=$_POST['transporte' . $i];
                    $costoT=$_POST['costo' . $i];
                    $monedaT=$_POST['monedaCosto' . $i];

                    if ($transporte != "" || $transporte != "undefined") {
                        //Inserto la opci�n en la base
                        $conexion->Ejecuto("insert into transporteevento (Nombre, Costo, idMoneda, idEvento) values ('" . $transporte . "'," . $costoT . "," . $monedaT . "," . $ultimoID['idEvento'] . ")");
                    }
                }

                $conexion->Ejecuto("insert into transporteevento (Nombre, Costo, idMoneda, idEvento) values ('Particular',0,1," . $ultimoID['idEvento'] . ")");

                $distritosOrg = obtenerOrganizadores($conexion, $ultimoID['idEvento']);

                foreach ($_POST['admins'] as $check) {
                    $conexion->Ejecuto("insert into eventoadmin (idEvento, idSocio) values (" . $ultimoID['idEvento'] . "," . $check . ")");
                    $conexion->Ejecuto("select Email from socio where idSocio=" . $check);
                    $correoA = $conexion->Siguiente();

                    enviarCorreo($correoA['Email'], utf8_encode("Potestad de administraci�n"), utf8_encode("Fuiste designado como admnistrador del evento " . $nombre . " - " . $distritosOrg . "."));
                }
            } else { //De lo contrario se actualiza
                //Obtengo informaci�n actual del evento para comparar
                $conexion->Ejecuto("select e.Nombre, e.FechaInicio, e.FechaFin, e.FechaInicioInscripcion, e.FechaFinInscripcion, e.Costo, m.Nombre as 'MonedaTicket', t.Nombre as 'TipoEvento', e.idTipoEvento, e.CupoMaximo from evento e, tipoevento t, moneda m where e.idEvento=" . $idEvento . " and e.idTipoEvento=t.idTipoEvento and e.idMoneda=m.idMoneda");
                $datosAnteriores=$conexion->Siguiente();

                //Actualizo evento
                $conexion->Ejecuto("update evento set Nombre='" . $nombre . "', FechaInicio='" . $fechaInicio[2] . "-" . $fechaInicio[1] . "-" . $fechaInicio[0] . " " . $horaI . ":00', FechaFin='" . $fechaFin[2] . "-" . $fechaFin[1] . "-" . $fechaFin[0] . " " . $horaF . ":00',FechaInicioInscripcion='" . $fechaInicioInscripcion[2] . "-" . $fechaInicioInscripcion[1] . "-" . $fechaInicioInscripcion[0] . " " . $horaII . ":00',FechaFinInscripcion='" . $fechaFinInscripcion[2] . "-" . $fechaFinInscripcion[1] . "-" . $fechaFinInscripcion[0] . " " . $horaFI . ":00', Costo=" . $costo . ",idMoneda=" . $monedaTicket . ", idTipoEvento=" . $tipoEvento . ", CupoMaximo=" . $cupo . " where idEvento=" . $idEvento);

                //Obtengo las opciones de transporte
                for ($i=1;$i<=5;$i++) {
                    $idTransporte=$_POST['idTransporte' . $i];
                    $transporte=$_POST['transporte' . $i];
                    $costoT=$_POST['costo' . $i];
                    $monedaT=$_POST['monedaCosto' . $i];

                    //Actualizo o inserto la opci�n en la base
                    if ($transporte != "" && $costoT != "") {
                        if ($idTransporte == "") {
                            $conexion->Ejecuto("insert into transporteevento (Nombre, Costo, idMoneda, idEvento) values ('" . $transporte . "'," . $costoT . "," . $monedaT . "," . $idEvento . ")");
                        } else {
                            $conexion->Ejecuto("update transporteevento set Nombre='" . $transporte . "', Costo=" . $costoT . " where idEvento=" . $idEvento . " and idTransporteEvento=" . $idTransporte);
                        }
                    } else {
                        if ($idTransporte != "") {
                            $conexion->Ejecuto("delete from transporteevento where idEvento=" . $idEvento . " and idTransporteEvento=" . $idTransporte);
                        }
                    }
                }

                $conexion->Ejecuto("delete from eventodistrito where idEvento=" . $idEvento);

                foreach ($_POST['distrito'] as $check) {
                    $conexion->Ejecuto("insert into eventodistrito (idEvento, idDistrito) values (" . $idEvento . "," . $check . ")");
                }

                if ($representante) {
                    $distritosOrg = obtenerOrganizadores($conexion, $idEvento);

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

                $conexion->Ejecuto("select count(idInscripcion) as 'Cantidad' from inscripcionevento where idEvento=" . $idEvento . " and Aprobado in (0,1,3,4) and Eliminado=0");
                $inscriptosTotal=$conexion->Siguiente();

                if (($cupo < $datosAnteriores['CupoMaximo']) && ($inscriptosTotal['Cantidad'] > $cupo)) {
                    if ($inscriptosTotal['Cantidad'] > $datosAnteriores['CupoMaximo']) {
                        $cantidad = $datosAnteriores['CupoMaximo'] - $cupo;
                    } else {
                        $cantidad = $inscriptosTotal['Cantidad'] - $cupo;
                    }

                    movimientoDeCupos($conexion, $conexion2, $idEvento, $cantidad, $idPeriodoActual, "negativo");
                } elseif (($cupo > $datosAnteriores['CupoMaximo']) && ($datosAnteriores['CupoMaximo'] < $inscriptosTotal['Cantidad'])) {
                    $cantidad = $cupo - $datosAnteriores['CupoMaximo'];
                    movimientoDeCupos($conexion, $conexion2, $idEvento, $cantidad, $idPeriodoActual, "positivo");
                }
            }

            header('Location: modules/events/eventos.php');
        }
    } else {
        header('Location: modules/auth/login.php');
    }
}

$conexion->Libero(); //Se cierra la conexi�n a la base
$conexion2->Libero(); //Se cierra la conexi�n a la base
$tpl->printToScreen(); //Se manda todo al HTML usando TPL

function movimientoDeCupos($conexion, $conexion2, $idEvento, $cantBorrar, $idPeriodoActual, $tipo)
{
    if ($tipo == "positivo") {
        $sentencia = "select i.idInscripcion, i.Aprobado, s.idSocio, s.Nombres, s.Apellidos, s.Email, s.idClub, d.idDistrito, d.Nombre as 'NombreDistrito', c.Nombre as 'NombreClub', e.Nombre as 'Evento' from inscripcionevento i, socio s, evento e, distrito d, club c where i.idEvento=" . $idEvento . " and i.Aprobado in (3,4) and i.Eliminado=0 and i.idSocio=s.idSocio and i.idEvento=e.idEvento and s.idClub=c.idClub and c.idDistrito=d.idDistrito order by FechaInscripcion ASC limit " . $cantBorrar;
    } elseif ($tipo == "negativo") {
        $sentencia = "select i.idInscripcion, i.Aprobado, s.idSocio, s.Nombres, s.Apellidos, s.Email, s.idClub, d.idDistrito, d.Nombre as 'NombreDistrito', c.Nombre as 'NombreClub', e.Nombre as 'Evento' from inscripcionevento i, socio s, evento e, distrito d, club c where i.idEvento=" . $idEvento . " and i.Aprobado in (0,1) and i.Eliminado=0 and i.idSocio=s.idSocio and i.idEvento=e.idEvento and s.idClub=c.idClub and c.idDistrito=d.idDistrito order by FechaInscripcion DESC limit " . $cantBorrar;
    }

    $conexion->Ejecuto($sentencia);

    while ($espera=$conexion->Siguiente()) {
        if ($tipo == "positivo") {
            if ($espera['Aprobado'] == "3") {
                $aprobado = 0;
            } elseif ($espera['Aprobado'] == "4") {
                $aprobado = 1;
            }
        } elseif ($tipo == "negativo") {
            if ($espera['Aprobado'] == "0") {
                $aprobado = 3;
            } elseif ($espera['Aprobado'] == "1") {
                $aprobado = 4;
            }
        }

        //Update de inscripcion
        $conexion2->Ejecuto("update inscripcionevento set Aprobado=" . $aprobado . " where idInscripcion=" . $espera['idInscripcion']);

        if ($tipo == "positivo") {
            if ($aprobado == 0) {
                $mensajeI = utf8_encode("Se liberaron cupos en ") . $espera['Evento'] . utf8_encode(" y saliste de la lista de espera. Tu inscripci�n sigue pendiente de aprobaci�n.");
                $mensajeR = utf8_encode("Se liberaron cupos en ") . $espera['Evento'] . " y " . $espera['Nombres'] . " " . $espera['Apellidos'] . utf8_encode(" sali� de la lista de espera. A�n debes aprobar su inscripci�n.");
            } elseif ($aprobado == 1) {
                $mensajeI = utf8_encode("Se liberaron cupos en ") . $espera['Evento'] . utf8_encode(" y saliste de la lista de espera. Tu inscripci�n fue aprobada previamente por lo que no tenes que realizar ninguna acci�n en el sistema.");
                $mensajeR = utf8_encode("Se liberaron cupos en ") . $espera['Evento'] . " y " . $espera['Nombres'] . " " . $espera['Apellidos'] . utf8_encode(" sali� de la lista de espera. Su inscripci�n fue aprobada previamente por lo que no tenes que realizar ninguna acci�n en el sistema.");
            }
        } elseif ($tipo == "negativo") {
            if ($aprobado == 3) {
                $mensajeI = utf8_encode("Los cupos del evento ") . $espera['Evento'] . utf8_encode(" se redujeron y quedaste en lista de espera. Tu inscripci�n sigue pendiente de aprobaci�n.");
                $mensajeR = utf8_encode("Los cupos del evento "). $espera['Evento'] . " se redujeron y " . $espera['Nombres'] . " " . $espera['Apellidos'] . utf8_encode(" qued� en lista de espera. A�n debes aprobar su inscripci�n.");
            } elseif ($aprobado == 4) {
                $mensajeI = utf8_encode("Los cupos del evento ") . $espera['Evento'] . utf8_encode(" se redujeron y quedaste en lista de espera. Tu inscripci�n fue aprobada previamente, en cuanto se liberen cupos te notificaremos.");
                $mensajeR = utf8_encode("Los cupos del evento ") . $espera['Evento'] . " se redujeron y " . $espera['Nombres'] . " " . $espera['Apellidos'] . utf8_encode(" qued� en lista de espera. Su inscripci�n fue aprobada previamente, en cuanto se liberen cupos te notificaremos.");
            }
        }

        if ($tipo == "positivo") {
            $asunto = utf8_encode("Saliste de la lista de espera!");
        } elseif ($tipo == "negativo") {
            $asunto = utf8_encode("Tu inscripci�n pas� a estar en lista de espera");
        }

        //MAIL AL INSCRIPTO
        enviarCorreo($espera['Email'], $asunto, $mensajeI);

        //MAIL AL/LOS RESPONSABLE/S
        $conexion2->Ejecuto("select e.Nombre, ed.idDistrito as 'Distrito', t.Tipo from evento e, tipoevento t, eventodistrito ed where e.idEvento=" . $idEvento . " and e.idTipoEvento=t.idTipoEvento and e.idEvento=ed.idEvento");
        $cantDistritosO=$conexion2->Tamano();

        //Variable para determinar si el socio pertecene a uno de los distritos organizadores cuando el evento es organizado por 2 distritos o m�s
        $esLocal = 0;

        if ($cantDistritos0 == 1) {
            $evento=$conexion2->Siguiente();
            $distritos=$evento['Distrito'];
        } else {
            for ($x=1;$x<=$cantDistritosO;$x++) {
                $evento=$conexion2->Siguiente();

                if ($socio['idDistrito'] == $evento['Distrito']) {
                    $esLocal = 1;
                }

                if ($x<$cantDistritosO) {
                    $distritos.= "'" . $evento['Distrito'] . "',";
                } else {
                    $distritos.= "'" . $evento['Distrito'] . "'";
                }
            }
        }

        if ($evento['Tipo'] == 0) {
            if (($cantDistritosO == 1 && $espera['idDistrito'] == $evento['Distrito'] && $espera['NombreClub'] != "Otro") || ($cantDistritosO > 1 && $esLocal && $espera['NombreClub'] != "Otro")) {
                $sqlResponsable = "select s1.Email from socio s1, socio s2, cargoclub c, historialcargoclub h where c.Nombre='Presidente' and c.idCargoClub=h.idCargoClub and s1.idClub=s2.idClub and h.idSocio=s1.idSocio and s2.idSocio=" . $espera['idSocio'] . " and h.idPeriodo=" . $idPeriodoActual;
            } else {
                $sqlResponsable = "select s.Email from socio s, distrito d, club c, cargodistrito ca, historialcargodistrito h where s.idSocio=h.idSocio and c.idClub=s.idClub and ca.Nombre='Representante Distrital' and ca.idCargoDistrito=h.idCargoDistrito and c.idDistrito in (" . $distritos . ") and h.idPeriodo=" . $idPeriodoActual . " group by s.Email";
            }
        } elseif ($evento['Tipo'] == 1) {
            if ($espera['NombreDistrito'] != "Otro") {
                $sqlResponsable = "select s.Email from socio s, distrito d, club c, cargodistrito ca, historialcargodistrito h where s.idClub=c.idClub and s.idSocio=h.idSocio and ca.Nombre='Representante Distrital' and ca.idCargoDistrito=h.idCargoDistrito and d.Nombre not in ('Otro') and c.idDistrito=(select d1.idDistrito from distrito d1, club c1 where c1.idClub=" . $espera['idClub'] . " and c1.idDistrito=d1.idDistrito) and h.idPeriodo=" . $idPeriodoActual . " group by s.Email";
            } else {
                $sqlResponsable = "select s.Email from socio s, distrito d, club c, cargodistrito ca, historialcargodistrito h where s.idClub=c.idClub and s.idSocio=h.idSocio and ca.Nombre='Representante Distrital' and ca.idCargoDistrito=h.idCargoDistrito and c.idDistrito in (" . $distritos . ") and h.idPeriodo=" . $idPeriodoActual . " group by s.Email";
            }
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
                if ($tipo == "positivo") {
                    $asunto = "Cupos liberados en " . $espera['Evento'];
                } elseif ($tipo == "negativo") {
                    $asunto = "Se redujeron los cupos en " . $espera['Evento'];
                }

                enviarCorreo($responsables[$x], $asunto, $mensajeR);
            }
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

function obtenerOrganizadores($conex, $evento)
{
    $conex->Ejecuto("select d.Nombre from distrito d, evento e, eventodistrito ed where ed.idEvento=" . $evento . " and ed.idDistrito=d.idDistrito and e.idEvento="  . $evento . " order by Nombre ASC");
    $cantDistritos=$conex->Tamano();

    if ($cantDistritos>1) {
        $contar=1;
        $nombreDistrito = "Distritos ";
        while ($distritos=$conex->Siguiente()) {
            if ($contar<$cantDistritos) {
                $nombreDistrito .= $distritos['Nombre'] . " / ";
            } else {
                $nombreDistrito .= $distritos['Nombre'];
            }
            $contar++;
        }
    } else {
        $distritoO=$conex->Siguiente();
        $nombreDistrito = "Distrito " . $distritoO['Nombre'];
    }

    return $nombreDistrito;
}
