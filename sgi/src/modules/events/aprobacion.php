<?php
ini_set("display_errors", 0);
include("config.php");
include("mailer.php");
require_once("conexionDB.php");
session_start(); //Se inicia la sesi�n
$obj_con=new conectar;

require_once("class.TemplatePower.inc.php"); //Usando Template Power

date_default_timezone_set('America/Argentina/Buenos_Aires');

$tpl=new TemplatePower("aprobacion.html");
$tpl->prepare();

$conexion= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());
$conexion2= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());

$accion=$_POST['accion'];
$idSocio = $_SESSION['usuario'];
$idPeriodoActual = obtenerPeriodoActual($conexion);

if ($idSocio == "") {
    header('Location: login.php');
} else {
    $conexion->Ejecuto("select idClub, Admin from socio where idSocio=" . $idSocio);
    $logueado=$conexion->Siguiente();

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

    if ($logueado['Admin'] == 1 || $representante || $adminEventos['Cantidad'] > 0 || $presidenteA['idSocio'] == $idSocio) {
        $tpl->newBlock("menuEventos");
    }

    if ($accion == "aprobacion") { //Si confirm� la inscripci�n, se env�an los correos correspondientes
        $idEvento = $_POST['idEvento'];
        $conexion->Ejecuto("select e.Nombre, e.FechaFin, t.Tipo, t.Nombre as 'NomEvento', t.idTipoEvento from evento e, tipoevento t where e.idEvento=" . $idEvento . " and e.idTipoEvento=t.idTipoEvento");
        $evento=$conexion->Siguiente();

        $conexion->Ejecuto("select count(i.idInscripcion) as 'Cantidad', e.CupoMaximo from inscripcionevento i, evento e where i.idEvento=" . $idEvento . " and i.idEvento=e.idEvento and i.Aprobado in (0,1,3,4) and i.Eliminado=0");
        $inscriptosTotal=$conexion->Siguiente();

        $controlar = false;
        $eraup = false;

        if ($evento['NomEvento'] == "E.R.A.U.P.") {
            $eraup = true;
        }

        if ($inscriptosTotal['Cantidad'] >= $inscriptosTotal['CupoMaximo']) {
            $controlar = true;
        }

        $organiza = obtenerOrganizadores($conexion, $idEvento);

        $totalInscriptos = $_POST['cantidadInscriptos'];
        $reprobados = 0;
        $idsReprobados = "";

        for ($x=1;$x<=$totalInscriptos;$x++) {
            $idInscripcion = $_POST['idAprobacion' . $x];
            $aprobacion = $_POST['aprobacion' . $x];

            if ($aprobacion != "" || $aprobacion != "undefined") {
                $conexion->Ejecuto("select s.Email, s.idSocio, c.Nombre as 'Calidad', i.Aprobado as 'Estado', d.Nombre as 'Distrito', d.idDistrito, s.idTipoRueda as 'Rueda' from socio s, inscripcionevento i, calidadasistenciaevento c, distrito d, club cl where s.idSocio=i.idSocio and s.idClub=cl.idClub and d.idDistrito=cl.idDistrito and i.idCalidadAsistencia=c.idCalidadAsistencia and i.idInscripcion=" . $idInscripcion);
                $socio=$conexion->Siguiente();

                //Actualizo el campo "Aprobado" en la tabla inscripcionevento
                if ($aprobacion == 1 && $socio['Estado'] == "3") {
                    $conexion2->Ejecuto("update inscripcionevento set Aprobado=4 where idInscripcion=" . $idInscripcion);
                } else {
                    $conexion2->Ejecuto("update inscripcionevento set Aprobado=" . $aprobacion . " where idInscripcion=" . $idInscripcion);
                    if ($aprobacion == 2) {
                        $reprobados++;

                        if ($evento['NomEvento'] == "E.R.A.U.P.") {
                            if ($socio['Estado'] == 0) {
                                if ($x == $totalInscriptos) {
                                    $idsReprobados .= $idInscripcion;
                                } else {
                                    $idsReprobados .= $idInscripcion . ",";
                                }
                            }
                        }
                    }
                }

                $hoy = date("Y-m-d H:i:s");

                if ($aprobacion == 1) { //Si fue aprobado
                    //Inserto inscripcion en historialinscripcion
                    $conexion2->Ejecuto("insert into historialinscripcion (idSocio, idEvento, CalidadAsistencia) values (" . $socio['idSocio'] . "," . $idEvento . ",'" . $socio['Calidad'] . "')");

                    if ($socio['Calidad'] == "Instructor") {
                        //Actualizo el historial del socio en historialevento
                        $conexion2->Ejecuto("update historialevento set VecesInstructor=VecesInstructor+1 where idSocio=" . $socio['idSocio'] . " and idTipoEvento=" . $evento['idTipoEvento']);
                    }

                    //Actualizo el historial del socio en historialevento
                    $conexion2->Ejecuto("update historialevento set CantidadAsistencias=CantidadAsistencias+1 where idSocio=" . $socio['idSocio'] . " and idTipoEvento=" . $evento['idTipoEvento']);

                    //SI ES ERAUP, INSERTO REGISTRO DE HISTORIAL DE MESA
                    if ($evento['NomEvento'] == "E.R.A.U.P.") {
                        if ($socio['Calidad'] == "Instructor") {
                            $conexion2->Ejecuto("insert into historialmesaeraup (Mesa,idSocio,Instructor) values ('" . $socio['Calidad'] . "'," . $socio['idSocio'] . ",1)");
                        } else {
                            $conexion2->Ejecuto("insert into historialmesaeraup (Mesa,idSocio,Instructor) values ('" . $socio['Calidad'] . "'," . $socio['idSocio'] . ",0)");
                        }
                    }

                    if ($hoy <= $evento['FechaFin']) {
                        //Se env�a correo de confirmaci�n al inscripto, solo si el evento no finaliz�, despu�s no tiene sentido avisar
                        if ($socio['Estado'] == "3") {
                            enviarCorreo($socio['Email'], utf8_encode("Inscripci�n aprobada"), utf8_encode("Tu inscripci�n a ") . $evento['Nombre'] . " (" . $organiza . ")" . utf8_encode(" fue aprobada, pero segu�s en lista de espera."));
                        } else {
                            enviarCorreo($socio['Email'], utf8_encode("Inscripci�n aprobada"), utf8_encode("Tu inscripci�n a ") . $evento['Nombre'] . " (" . $organiza . ")" . " fue aprobada.");
                        }
                    }
                } else { //Si no fue aprobado
                    if ($evento['NomEvento'] == "E.R.A.U.P.") {
                        //Restar uno de los inscriptos del distrito que corresponda si es Rotaractiano de AIRAUP
                        if ($socio['Distrito'] != "Otro" && $socio['Rueda'] == 2) {
                            $conexion->Ejecuto("update asistenciaeraup set Inscriptos=Inscriptos-1 where idDistrito=" . $socio['idDistrito'] . " and idEvento=" . $idEvento);
                        }
                    }

                    if ($hoy <= $evento['FechaFin']) {
                        //Se env�a correo de confirmaci�n al inscripto, solo si el evento no finaliz�, despu�s no tiene sentido avisar
                        if ($evento['Tipo'] == 0) {
                            enviarCorreo($socio['Email'], utf8_encode("Inscripci�n reprobada"), utf8_encode("Tu inscripci�n a ") . $evento['Nombre'] . " (" . $organiza . ")" . " fue reprobada. Comunicate con tu Presidente.");
                        } elseif ($evento['Tipo'] == 1) {
                            enviarCorreo($socio['Email'], utf8_encode("Inscripci�n reprobada"), utf8_encode("Tu inscripci�n a ") . $evento['Nombre'] . " (" . $organiza . ")" . " fue reprobada. Comunicate con tu Representante Distrital.");
                        }
                    }
                }
            }
        }

        if (($controlar && $reprobados > 0) || ($eraup && $reprobados > 0)) {
            if ($eraup) {
                movimientosERAUP($conexion, $conexion2, $idEvento, $idsReprobados, $idPeriodoActual);
            } else {
                movimientoDeCupos($conexion, $conexion2, $idEvento, $reprobados, $idPeriodoActual);
            }
        }

        header('Location: perfil.php?a=a');
    } else {
        $idEvento=$_POST['idEvento'];

        if ($idEvento == "") {
            header('Location: perfil.php?a=p');
        }

        $conexion->Ejecuto("select e.Nombre, t.Tipo from evento e, tipoevento t where e.idEvento=" . $idEvento . " and e.idTipoEvento=t.idTipoEvento");
        $evento=$conexion->Siguiente();

        $organiza = obtenerOrganizadores($conexion, $idEvento);

        $tpl->NewBlock("datosEvento");
        $tpl->Assign("idEvento", $idEvento);
        $tpl->Assign("evento", $evento['Nombre'] . " - " . $organiza);

        if ($presidente) {
            if ($evento['Tipo'] == 0) {
                $conexion->Ejecuto("select idClub from socio where idSocio=" . $idSocio);
                $logueado=$conexion->Siguiente();

                $sentenciaSql="select i.idInscripcion, s.idSocio, s.Nombres, s.Apellidos, c.Nombre as 'Calidad', t.Nombre as 'Transporte', d.Nombre as 'Distrito', cl.Nombre as 'Club', r.Nombre as 'Rueda' from inscripcionevento i, socio s, calidadasistenciaevento c, transporteevento t, tiporueda r, club cl, distrito d where i.idEvento=" . $idEvento . " and s.idSocio=i.idSocio and i.idCalidadAsistencia=c.idCalidadAsistencia and i.idTransporteEvento=t.idTransporteEvento and (i.Aprobado=0 or i.Aprobado=3) and i.Eliminado=0 and s.idClub=cl.idClub and cl.idDistrito=d.idDistrito and r.idTipoRueda=s.idTipoRueda and s.idClub=" . $logueado['idClub'] . " order by s.idSocio ASC";
            }
        }

        $conexion->Ejecuto("select ed.idDistrito as 'Distrito' from evento e, eventodistrito ed where e.idEvento=" . $idEvento . " and e.idEvento=ed.idEvento");
        $cantDistritosO=$conexion->Tamano();

        if ($cantDistritos0 == 1) {
            $eventoO=$conexion->Siguiente();
            $distritos=$eventoO['Distrito'];
        } else {
            for ($x=1;$x<=$cantDistritosO;$x++) {
                $eventoO=$conexion->Siguiente();

                if ($x<$cantDistritosO) {
                    $distritos.= "'" . $eventoO['Distrito'] . "',";
                } else {
                    $distritos.= "'" . $eventoO['Distrito'] . "'";
                }
            }
        }

        if ($representante) {
            $conexion->Ejecuto("select d.idDistrito from socio s, club c, distrito d where s.idSocio=" . $idSocio . " and c.idClub=s.idClub and d.idDistrito=c.idDistrito");
            $logueado=$conexion->Siguiente();

            if ($evento['Tipo'] == 0) {
                $sentenciaSql="select i.idInscripcion, s.idSocio, s.Nombres, s.Apellidos, ca.Nombre as 'Calidad', tr.Nombre as 'Transporte' from inscripcionevento i, socio s, club c, distrito d, calidadasistenciaevento ca, transporteevento tr where i.idEvento=" . $idEvento . " and i.idSocio=s.idSocio and i.idCalidadAsistencia=ca.idCalidadAsistencia and i.idTransporteEvento=tr.idTransporteEvento and (i.Aprobado=0 or i.Aprobado=3) and i.Eliminado=0 and ((s.idClub=c.idClub and c.Nombre='Otro' and c.idDistrito=" . $logueado['idDistrito'] . ") or (s.idClub=c.idClub and c.idDistrito not in (" . $distritos . ")) or  (s.idTipoRueda=1 or s.idTipoRueda=3)) group by s.idSocio order by s.idSocio ASC";
            } elseif ($evento['Tipo'] == 1) {
                if (strpos($distritos, $logueado['idDistrito'])) {
                    $sentenciaSql="select i.idInscripcion, s.idSocio, s.Nombres, s.Apellidos, ca.Nombre as 'Calidad', tr.Nombre as 'Transporte' from inscripcionevento i, socio s, club c, distrito d, calidadasistenciaevento ca, transporteevento tr where i.idEvento=" . $idEvento . " and i.idSocio=s.idSocio and i.idCalidadAsistencia=ca.idCalidadAsistencia and i.idTransporteEvento=tr.idTransporteEvento and (i.Aprobado=0 or i.Aprobado=3) and i.Eliminado=0 and ((s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre='Otro') or (s.idClub=c.idClub and c.idDistrito=d.idDistrito and c.idDistrito=" . $logueado['idDistrito'] . ")) group by s.idSocio order by s.idSocio ASC";
                } else {
                    $sentenciaSql="select i.idInscripcion, s.idSocio, s.Nombres, s.Apellidos, ca.Nombre as 'Calidad', tr.Nombre as 'Transporte' from inscripcionevento i, socio s, club c, distrito d, calidadasistenciaevento ca, transporteevento tr where i.idEvento=" . $idEvento . " and i.idSocio=s.idSocio and i.idCalidadAsistencia=ca.idCalidadAsistencia and i.idTransporteEvento=tr.idTransporteEvento and (i.Aprobado=0 or i.Aprobado=3) and i.Eliminado=0 and (s.idClub=c.idClub and c.idDistrito=d.idDistrito and c.idDistrito=" . $logueado['idDistrito'] . ") group by s.idSocio order by s.idSocio ASC";
                }
            }
        }

        $conexion->Ejecuto($sentenciaSql);
        $totalInscriptos = $conexion->Tamano();

        if ($totalInscriptos > 0) {
            $tpl->Assign("cantidad", $totalInscriptos);
            $contar = 1;

            while ($rowInscripto=$conexion->Siguiente()) {
                $conexion2->Ejecuto("select d.Nombre as 'Distrito', c.Nombre as 'Club', r.Nombre as 'Rueda' from socio s, club c, distrito d, tiporueda r where s.idSocio=" . $rowInscripto['idSocio'] . " and s.idClub=c.idClub and d.idDistrito=c.idDistrito and s.idTipoRueda=r.idTipoRueda");
                $datos=$conexion2->Siguiente();

                $tpl->newBlock("inscripto");
                $tpl->assign("idInscripcion", $rowInscripto['idInscripcion']);
                $tpl->assign("socio", $rowInscripto['Nombres'] . " " . $rowInscripto['Apellidos']);
                $tpl->assign("distrito", $datos['Distrito']);
                $tpl->assign("club", $datos['Club']);
                $tpl->assign("rueda", $datos['Rueda']);
                $tpl->assign("calidad", $rowInscripto['Calidad']);
                $tpl->assign("transporte", $rowInscripto['Transporte']);
                $tpl->assign("contar", $contar);
                $contar++;
            }
        }
    }
}

$conexion->Libero(); //Se cierra la conexi�n a la base
$conexion2->Libero(); //Se cierra la conexi�n a la base
$tpl->printToScreen(); //Se manda todo al HTML usando TPL

function movimientosERAUP($conexion, $conexion2, $idEvento, $idsReprobados, $idPeriodoActual)
{
    $conexion->Ejecuto("select * from evento where idEvento=" . $idEvento);
    $infoEvento=$conexion->Siguiente();

    $hoy = date("Y-m-d H:i:s");
    $arrayIDs=split(",", $idsReprobados);

    for ($x=0;$x<sizeof($arrayIDs);$x++) {
        $conexion->Ejecuto("select s.idSocio, t.Nombre as 'Rueda', d.Nombre as 'Distrito', d.idDistrito, i.Reserva from socio s, inscripcionevento i, club c, distrito d, tiporueda t where s.idTipoRueda=t.idTipoRueda and s.idClub=c.idClub and c.idDistrito=d.idDistrito and i.idSocio=s.idSocio and i.idInscripcion=" . $arrayIDs[$x]);
        $reprobado=$conexion->Siguiente();

        $esOrganizador = obtenerOrganizadores($conexion, $idEvento, $reprobado['idDistrito'], false);

        if ($reprobado['Reserva'] == 0) {
            //Busco al siguiente en espera dependiendo de si es Rotaractiano de AIRAUP, Rotario o Extranjero y de la fase de inscripci�n actual
            if ($hoy < $infoEvento['FechaInicioInscripcion2']) { //FASE I
                if ($reprobado['Rueda'] == "Rotaract" && $reprobado['Distrito'] != "Otro") { //Rotaractiano de AIRAUP
                    $conexion->Ejecuto("select CupoReservado as 'Cupo', Inscriptos from asistenciaeraup where idEvento=" . $idEvento . " and idDistrito=" . $reprobado['idDistrito']);
                    $reserva=$conexion->Siguiente();

                    if ($reserva['Inscriptos'] > $reserva['Cupo'] && $esOrganizador != "SI") {
                        //Sube un Rotaractiano del mismo distrito
                        $conexion->Ejecuto("select i.idInscripcion, i.Aprobado, s.Email, s.Nombres, s.Apellidos, s.idClub, e.Nombre as 'NomEvento' from evento e, inscripcionevento i, socio s, club c, distrito d where i.idEvento=" . $idEvento . " and i.idEvento=e.idEvento and i.idSocio=s.idSocio and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito=" . $reprobado['idDistrito'] . " and s.idTipoRueda=2 and i.Aprobado in (3,4) and i.Eliminado=0 order by FechaInscripcion ASC limit 1");
                        $ingreso=$conexion->Siguiente();
                        $cantidad=$conexion->Tamano();

                        if ($cantidad > 0) {
                            notificarERAUP($conexion, $ingreso['Aprobado'], $ingreso['idInscripcion'], $idEvento, $idPeriodoActual, $ingreso['NomEvento'], $ingreso['Email'], $ingreso['Nombres'] . " " . $ingreso['Apellidos'], $reprobado['Distrito'], $ingreso['idClub']);
                        }
                    }
                } elseif ($reprobado['Rueda'] == "Rotary" && $reprobado['Distrito'] != "Otro") { //Rotario de AIRAUP
                    $conexion->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where s.idTipoRueda=3 and s.idSocio=i.idSocio and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre<>'Otro' and i.Eliminado=0 and i.Reserva=0 and i.Aprobado not in (2,3,4) and i.idEvento=" . $idEvento);
                    $cantidadR=$conexion->Siguiente();

                    $conexion->Ejecuto("select PorcentajeRotarios1, PorcentajeExtranjeros1, CupoMaximo, Reserva from evento where idEvento=" . $idEvento);
                    $valores=$conexion->Siguiente();

                    $cupoRotario = ($valores['PorcentajeRotarios1'] * $valores['CupoMaximo']) / 100;

                    if ($cupoRotario > 0 && $cantidadR['Cantidad'] < $cupoRotario && $esOrganizador != "SI") {
                        //Sube un Rotario de AIRAUP
                        $conexion->Ejecuto("select i.idInscripcion, i.Aprobado, s.Email, s.Nombres, s.Apellidos, s.idClub, e.Nombre as 'NomEvento' from evento e, inscripcionevento i, socio s, club c, distrito d where i.idEvento=" . $idEvento . " and i.idEvento=e.idEvento and i.idSocio=s.idSocio and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre<>'Otro' and s.idTipoRueda=3 and i.Aprobado in (3,4) and i.Eliminado=0 order by FechaInscripcion ASC limit 1");
                        $ingreso=$conexion->Siguiente();
                        $cantidad=$conexion->Tamano();

                        if ($cantidad > 0) {
                            notificarERAUP($conexion, $ingreso['Aprobado'], $ingreso['idInscripcion'], $idEvento, $idPeriodoActual, $ingreso['NomEvento'], $ingreso['Email'], $ingreso['Nombres'] . " " . $ingreso['Apellidos'], "Otro", $ingreso['idClub']);
                        }
                    } elseif ($cupoRotario == 0 && $esOrganizador != "SI") { //No hay l�mite de Rotarios
                        //Se chequea cupo global
                        $cupoExtranjeros = ($valores['PorcentajeExtranjeros2'] * $valores['CupoMaximo']) / 100;
                        $cupoTotal = $valores['CupoMaximo'] - $valores['Reserva'] - $cupoRotario - $cupoExtranjeros;
                        $organizadores = obtenerOrganizadores($conexion, $idEvento, "", true);

                        if ($cupoExtranjeros > 0) {
                            $conexion->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d, tiporueda t where i.idEvento=" . $idEvento . " and s.idSocio=i.idSocio and i.Eliminado=0 and i.Reserva=0 and i.Aprobado not in (2,3,4) and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre<>'Otro' and d.idDistrito not in (" . $organizadores . ")");
                            $inscriptos=$conexion->Siguiente();
                        } else {
                            $conexion->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d, tiporueda t where i.idEvento=" . $idEvento . " and s.idSocio=i.idSocio and i.Eliminado=0 and i.Reserva=0 and i.Aprobado not in (2,3,4) and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito not in (" . $organizadores . ")");
                            $inscriptos=$conexion->Siguiente();
                        }

                        $disponible = $cupoTotal - $inscriptos['Cantidad'];

                        if ($disponible > 0) {
                            //Sube un Rotario de AIRAUP
                            $conexion->Ejecuto("select i.idInscripcion, i.Aprobado, s.Email, s.Nombres, s.Apellidos, s.idClub, e.Nombre as 'NomEvento' from evento e, inscripcionevento i, socio s, club c, distrito d where i.idEvento=" . $idEvento . " and i.idEvento=e.idEvento and i.idSocio=s.idSocio and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre<>'Otro' and s.idTipoRueda=3 and i.Aprobado in (3,4) and i.Eliminado=0 order by FechaInscripcion ASC limit 1");
                            $ingreso=$conexion->Siguiente();
                            $cantidad=$conexion->Tamano();

                            if ($cantidad > 0) {
                                notificarERAUP($conexion, $ingreso['Aprobado'], $ingreso['idInscripcion'], $idEvento, $idPeriodoActual, $ingreso['NomEvento'], $ingreso['Email'], $ingreso['Nombres'] . " " . $ingreso['Apellidos'], "Otro", $ingreso['idClub']);
                            }
                        }
                    }
                } elseif ($reprobado['Distrito'] == "Otro") { //Extranjero
                    $conexion->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre='Otro' and s.idSocio=i.idSocio and i.Eliminado=0 and i.Reserva=0 and i.Aprobado not in (2,3,4) and i.idEvento=" . $idEvento);
                    $cantidadE=$conexion->Siguiente();

                    $conexion->Ejecuto("select PorcentajeRotarios1, PorcentajeExtranjeros1, CupoMaximo, Reserva from evento where idEvento=" . $idEvento);
                    $valores=$conexion->Siguiente();

                    $cupoExtranjeros = ($valores['PorcentajeExtranjeros1'] * $valores['CupoMaximo']) / 100;

                    if ($cupoExtranjeros > 0 && $cantidadE['Cantidad'] < $cupoExtranjeros) {
                        //Sube un extranjero
                        $conexion->Ejecuto("select i.idInscripcion, i.Aprobado, s.Email, s.Nombres, s.Apellidos, s.idClub, e.Nombre as 'NomEvento' from evento e, inscripcionevento i, socio s, club c, distrito d where i.idEvento=" . $idEvento . " and i.idEvento=e.idEvento and i.idSocio=s.idSocio and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre='Otro' and i.Aprobado in (3,4) and i.Eliminado=0 order by FechaInscripcion ASC limit 1");
                        $ingreso=$conexion->Siguiente();
                        $cantidad=$conexion->Tamano();

                        if ($cantidad > 0) {
                            notificarERAUP($conexion, $ingreso['Aprobado'], $ingreso['idInscripcion'], $idEvento, $idPeriodoActual, $ingreso['NomEvento'], $ingreso['Email'], $ingreso['Nombres'] . " " . $ingreso['Apellidos'], "Otro", $ingreso['idClub']);
                        }
                    } elseif ($cupoExtranjeros == 0) { //No hay l�mite de extranjeros
                        //Se chequea cupo global
                        $cupoRotario = ($valores['PorcentajeRotarios1'] * $valores['CupoMaximo']) / 100;
                        $cupoTotal = $valores['CupoMaximo'] - $valores['Reserva'] - $cupoRotario - $cupoExtranjeros;
                        $organizadores = obtenerOrganizadores($conexion, $idEvento, "", true);

                        if ($cupoRotario > 0) {
                            $conexion->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d, tiporueda t where i.idEvento=" . $idEvento . " and s.idSocio=i.idSocio and s.idTipoRueda=t.idTipoRueda and t.Nombre='Rotaract' and i.Eliminado=0 and i.Reserva=0 and i.Aprobado<>2 and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito not in (" . $organizadores . ")");
                            $inscriptos=$conexion->Siguiente();
                        } else {
                            $conexion->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d, tiporueda t where i.idEvento=" . $idEvento . " and s.idSocio=i.idSocio and i.Eliminado=0 and i.Reserva=0 and i.Aprobado<>2 and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito not in (" . $organizadores . ")");
                            $inscriptos=$conexion->Siguiente();
                        }

                        $disponible = $cupoTotal - $inscriptos['Cantidad'];

                        if ($disponible > 0) {
                            //Sube un extranjero
                            $conexion->Ejecuto("select i.idInscripcion, i.Aprobado, s.Email, s.Nombres, s.Apellidos, s.idClub, e.Nombre as 'NomEvento' from evento e, inscripcionevento i, socio s, club c, distrito d where i.idEvento=" . $idEvento . " and i.idEvento=e.idEvento and i.idSocio=s.idSocio and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre='Otro' and i.Aprobado in (3,4) and i.Eliminado=0 order by FechaInscripcion ASC limit 1");
                            $ingreso=$conexion->Siguiente();
                            $cantidad=$conexion->Tamano();

                            if ($cantidad > 0) {
                                notificarERAUP($conexion, $ingreso['Aprobado'], $ingreso['idInscripcion'], $idEvento, $idPeriodoActual, $ingreso['NomEvento'], $ingreso['Email'], $ingreso['Nombres'] . " " . $ingreso['Apellidos'], "Otro", $ingreso['idClub']);
                            }
                        }
                    }
                }
            } elseif ($hoy >= $infoEvento['FechaInicioInscripcion2']) { //FASE II
                $conexion->Ejecuto("select PorcentajeRotarios2, PorcentajeExtranjeros2, CupoMaximo, Reserva from evento where idEvento=" . $idEvento);
                $valores=$conexion->Siguiente();

                $cupoRotario = ($valores['PorcentajeRotarios2'] * $valores['CupoMaximo']) / 100;
                $cupoExtranjeros = ($valores['PorcentajeExtranjeros2'] * $valores['CupoMaximo']) / 100;

                $conexion->Ejecuto("select i.idInscripcion, i.Aprobado, s.Email, s.Nombres, s.Apellidos, s.idClub, t.Nombre as 'Rueda', d.Nombre as 'Distrito', e.Nombre as 'NomEvento' from evento e, inscripcionevento i, socio s, club c, distrito d, tiporueda t where i.idEvento=" . $idEvento . " and i.idEvento=e.idEvento and i.idSocio=s.idSocio and s.idTipoRueda=t.idTipoRueda and s.idClub=c.idClub and c.idDistrito=d.idDistrito and i.Aprobado in (3,4) and i.Eliminado=0 order by FechaInscripcion ASC");
                $cantidad=$conexion->Tamano();

                if ($cantidad > 0) {
                    //Variable para determinar si hay que buscar otro inscripto para subir
                    $entra = false;

                    while ($ingreso=$conexion->Siguiente()) {
                        if ($ingreso['Rueda'] == "Rotaract" && $ingreso['Distrito'] != "Otro") { //Es Rotaractiano de AIRAUP el que sube
                            $entra = true;
                            break;
                        } elseif ($ingreso['Rueda'] == "Rotary" && $ingreso['Distrito'] != "Otro") { //Es Rotario de AIRAUP el que sube
                            //Se chequea si hay l�mite para Rotarios
                            $conexion2->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s where s.idTipoRueda=3 and s.idSocio=i.idSocio and i.Eliminado=0 and i.Aprobado<>2 and i.idEvento=" . $idEvento);
                            $cantidadR=$conexion2->Siguiente();

                            if ($cupoRotario > 0 && $cantidadR['Cantidad'] < $cupoRotario) { //Existe l�mite y queda lugar
                                $entra = true;
                                break;
                            } elseif ($cupoRotario == 0) { //No existe l�mite y queda lugar en el general
                                $entra = true;
                                break;
                            }
                        } elseif ($ingreso['Distrito'] == "Otro") { //Es extranjero el que sube
                            //Se chequea si hay l�mite para extranjeros
                            $conexion2->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre='Otro' and s.idSocio=i.idSocio and i.Eliminado=0 and i.Aprobado<>2 and i.idEvento=" . $idEvento);
                            $cantidadE=$conexion2->Siguiente();

                            if ($cupoExtranjeros > 0 && $cantidadE['Cantidad'] < $cupoExtranjeros) { //Existe l�mite y queda lugar
                                $entra = true;
                                break;
                            } elseif ($cupoExtranjeros == 0) { //No existe l�mite y queda lugar en el general
                                $entra = true;
                                break;
                            }
                        }
                    }

                    if ($entra) {
                        notificarERAUP($conexion, $ingreso['Aprobado'], $ingreso['idInscripcion'], $idEvento, $idPeriodoActual, $ingreso['NomEvento'], $ingreso['Email'], $ingreso['Nombres'] . " " . $ingreso['Apellidos'], $ingreso['Distrito'], $ingreso['idClub']);
                    }
                }
            }
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

function movimientoDeCupos($conexion, $conexion2, $idEvento, $cantBorrar, $idPeriodoActual)
{
    $conexion->Ejecuto("select i.idInscripcion, i.Aprobado, s.idSocio, s.Nombres, s.Apellidos, s.Email, s.idClub, d.idDistrito, d.Nombre as 'NombreDistrito', c.Nombre as 'NombreClub', e.Nombre as 'Evento' from inscripcionevento i, socio s, evento e, distrito d, club c where i.idEvento=" . $idEvento . " and i.Aprobado in (3,4) and i.Eliminado=0 and i.idSocio=s.idSocio and i.idEvento=e.idEvento and s.idClub=c.idClub and c.idDistrito=d.idDistrito order by FechaInscripcion ASC limit " . $cantBorrar);

    while ($espera=$conexion->Siguiente()) {
        if ($espera['Aprobado'] == "3") {
            $aprobado = 0;
        } elseif ($espera['Aprobado'] == "4") {
            $aprobado = 1;
        }

        //Update de inscripcion
        $conexion2->Ejecuto("update inscripcionevento set Aprobado=" . $aprobado . " where idInscripcion=" . $espera['idInscripcion']);

        if ($aprobado == 0) {
            $mensajeI = utf8_encode("Se liberaron cupos en ") . $espera['Evento'] . utf8_encode(" y saliste de la lista de espera. Tu inscripci�n sigue pendiente de aprobaci�n.");
            $mensajeR = utf8_encode("Se liberaron cupos en "). $espera['Evento'] . " y " . $espera['Nombres'] . " " . $espera['Apellidos'] . utf8_encode(" sali� de la lista de espera. A�n debes aprobar su inscripci�n.");
        } elseif ($aprobado == 1) {
            $mensajeI = utf8_encode("Se liberaron cupos en ") . $espera['Evento'] . utf8_encode(" y saliste de la lista de espera. Tu inscripci�n fue aprobada previamente por lo que no tenes que realizar ninguna acci�n en el sistema.");
            $mensajeR = utf8_encode("Se liberaron cupos en ") . $espera['Evento'] . " y " . $espera['Nombres'] . " " . $espera['Apellidos'] . utf8_encode(" sali� de la lista de espera. Su inscripci�n fue aprobada previamente por lo que no tenes que realizar ninguna acci�n en el sistema.");
        }

        //MAIL AL INSCRIPTO
        enviarCorreo($espera['Email'], utf8_encode("Saliste de la lista de espera!"), $mensajeI);

        //MAIL AL/LOS RESPONSABLE/S
        $conexion->Ejecuto("select e.Nombre, ed.idDistrito as 'Distrito', t.Tipo from evento e, tipoevento t, eventodistrito ed where e.idEvento=" . $idEvento . " and e.idTipoEvento=t.idTipoEvento and e.idEvento=ed.idEvento");
        $cantDistritosO=$conexion->Tamano();

        //Variable para determinar si el socio pertecene a uno de los distritos organizadores cuando el evento es organizado por 2 distritos o m�s
        $esLocal = 0;

        if ($cantDistritos0 == 1) {
            $evento=$conexion->Siguiente();
            $distritos=$evento['Distrito'];
        } else {
            for ($x=1;$x<=$cantDistritosO;$x++) {
                $evento=$conexion->Siguiente();

                if ($espera['idDistrito'] == $evento['Distrito']) {
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

        $conexion->Ejecuto($sqlResponsable);
        $cantResponsables=$conexion->Tamano();

        if ($cantResponsables > 1) {
            for ($x=1;$x<=$cantResponsables;$x++) {
                $responsable=$conexion->Siguiente();
                $responsables[$x]= $responsable['Email'];
            }
        } else {
            $responsable=$conexion->Siguiente();
            $responsables[1] = $responsable['Email'];
        }

        for ($x=1;$x<=$cantResponsables;$x++) {
            if ($responsables[$x] != "") {
                enviarCorreo($responsables[$x], "Cupos liberados en " . $espera['Evento'], $mensajeR);
            }
        }
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
