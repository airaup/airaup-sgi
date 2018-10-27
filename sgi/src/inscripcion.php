<?php
ini_set("display_errors", 0);
include("config.php");
include("mailer.php");
require_once("conexionDB.php");
session_start(); //Se inicia la sesión
$obj_con=new conectar;

date_default_timezone_set('America/Argentina/Buenos_Aires');

require_once("class.TemplatePower.inc.php"); //Usando Template Power

$tpl=new TemplatePower("inscripcion.html");
    $tpl->prepare();

$conexion= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());
$conexion2= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());

$logueado = $_SESSION['usuario'];
$idPeriodoActual = obtenerPeriodoActual($conexion);

if ($logueado == "") {
    header('Location: login.php');
} else {
    $conexion->Ejecuto("select Admin from socio where idSocio=" . $logueado);
    $admin=$conexion->Siguiente();

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

    if ($presidenteA['idSocio'] == $logueado && !$presidente && !$representante) {
        $tpl->newBlock("menuStats");
    }

    if ($admin['Admin'] == 1 || $representante || $adminEventos['Cantidad'] > 0 || $presidenteA['idSocio'] == $logueado) {
        $tpl->newBlock("menuEventos");
    }

    $accion=$_POST['accion'];

    if ($accion == "") {
        $accion = $_GET['a'];
    }

    if ($accion == "inscripcion" || $accion == "inscripcionCN") { //Si confirmó la inscripción, se envían los correos correspondientes
        $idSocio = $_SESSION['usuario'];
        $idEvento = $_POST['idEvento'];
        $calidad = $_POST['calidad'];
        $transporte = $_POST['transporte'];

        if ($accion == "inscripcionCN") {
            $idSocio = $_GET['idS'];
            $idAnt = $_GET['idA'];
            $idEvento = $_GET['id'];
            $idEliminar = $_GET['idI'];
            $idInscNueva = $_GET['idN'];
        }

        $conexion->Ejecuto("select s.Nombres, s.Apellidos, s.Email, s.idClub, c.Nombre as 'NombreClub', d.idDistrito, d.Nombre as 'NombreDistrito', tr.Nombre as 'Rueda' from socio s, club c, distrito d, tiporueda tr where s.idSocio=" . $idSocio . " and s.idClub=c.idClub and c.idDistrito=d.idDistrito and s.idTipoRueda=tr.idTipoRueda");
        $socio=$conexion->Siguiente();

        $conexion->Ejecuto("select e.Nombre, ed.idDistrito as 'Distrito', t.Tipo, t.Nombre as 'TipoEvento', t.idTipoEvento, e.FechaInicioInscripcion, e.FechaFinInscripcion, e.FechaInicioInscripcion2, e.FechaFinInscripcion2 from evento e, tipoevento t, eventodistrito ed where e.idEvento=" . $idEvento . " and e.idTipoEvento=t.idTipoEvento and e.idEvento=ed.idEvento");
        $cantDistritosO=$conexion->Tamano();

        //Variable para determinar si el socio pertecene a uno de los distritos organizadores cuando el evento es organizado por 2 distritos o más
        $esLocal = 0;
        $tipoEvento = "";

        if ($cantDistritos0 == 1) {
            $evento=$conexion->Siguiente();
            $distritos=$evento['Distrito'];
            $tipoEvento = $evento['TipoEvento'];
            $tipo = $evento['Tipo'];
            $idtipoevento = $evento['idTipoEvento'];
        } else {
            for ($x=1;$x<=$cantDistritosO;$x++) {
                $evento=$conexion->Siguiente();

                if ($socio['idDistrito'] == $evento['Distrito']) {
                    $esLocal = 1; //En los eventos organizados por mas de un distrito, se manda notificacion solo al RDR del socio
                }

                if ($x<$cantDistritosO) {
                    $distritos.= "'" . $evento['Distrito'] . "',";
                } else {
                    $distritos.= "'" . $evento['Distrito'] . "'";
                    $tipoEvento = $evento['TipoEvento'];
                    $tipo = $evento['Tipo'];
                    $idtipoevento = $evento['idTipoEvento'];
                }
            }
        }

        $organiza = obtenerOrganizadores($conexion, $idEvento, "", false);

        $ahora = getDate();
        $fechaInsert = $ahora['year'] . "-" . $ahora['mon'] . "-" . $ahora['mday'] . " " . $ahora['hours'] . ":" . $ahora['minutes'] . ":" . $ahora['seconds'];

        $hoy = date("Y-m-d H:i:s");

        $espera = false;
        $esOrganizador = obtenerOrganizadores($conexion, $idEvento, $socio['idDistrito'], false);


        if ($tipoEvento == "E.R.A.U.P.") {


                       // PARCHE J.TIERNO (El combo no esta enviando bien el codigo de que tipo de asistente es, esto se debe a que el combo box queda fijo como organizador y no esta cargando ene l value del html)
            //*
            //*
            //*
            if ($esOrganizador == "SI") {
                $calidad = 23;
            }
            //*
            //*
            //*
            //------------------------------------------------------------

            $conexion->Ejecuto("select FechaInicioInscripcion, FechaFinInscripcion, FechaInicioInscripcion2, FechaFinInscripcion2 from evento where idEvento=" . $idEvento);
            $datosEvento=$conexion->Siguiente();

            //FASE I
            if ($datosEvento['FechaInicioInscripcion'] < $hoy && $datosEvento['FechaFinInscripcion'] > $hoy) {
                if ($socio['Rueda'] == "Rotaract" && $socio['NombreDistrito'] != "Otro") {
                    $conexion->Ejecuto("select CupoReservado as 'Cupo', Inscriptos from asistenciaeraup where idEvento=" . $idEvento . " and idDistrito=" . $socio['idDistrito']);
                    $reserva=$conexion->Siguiente();

                    //De inscriptos restar los que estén marcados como reserva
                    $conexion->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where s.idTipoRueda=2 and s.idSocio=i.idSocio and i.Eliminado=0 and i.Aprobado<>2 and i.Reserva=1 and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito=" . $socio['idDistrito'] . " and i.idEvento=" . $idEvento);
                    $marcados=$conexion->Siguiente();

                    // PARCHE J.TIERNO
                    //&& $esOrganizador != "SI"

                    if (($reserva['Inscriptos'] - $marcados['Cantidad']) >= $reserva['Cupo']) {
                        $espera = true;
                    }
                } elseif ($socio['Rueda'] == "Rotary" && $socio['NombreDistrito'] != "Otro") {
                    $conexion->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where c.idClub=s.idClub and c.idDistrito=d.idDistrito and s.idTipoRueda=3 and d.Nombre<>'Otro' and s.idSocio=i.idSocio and i.Eliminado=0 and i.Aprobado<>2 and i.Reserva=0 and i.idEvento=" . $idEvento);
                    $cantidadR=$conexion->Siguiente(); //Rotarios inscriptos que no ocupan reserva

                    $conexion->Ejecuto("select PorcentajeRotarios1, CupoMaximo from evento where idEvento=" . $idEvento);
                    $valores=$conexion->Siguiente();

                    $cupoRotario = ($valores['PorcentajeRotarios1'] * $valores['CupoMaximo']) / 100;

                    // PARCHE J.TIERNO
                    //&& $esOrganizador != "SI"

                    if ($cupoRotario > 0 && $cantidadR['Cantidad'] >= $cupoRotario) {
                        $espera = true;
                    }
                } elseif ($socio['NombreDistrito'] == "Otro") {
                    $conexion->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre='Otro' and s.idSocio=i.idSocio and i.Eliminado=0 and i.Aprobado<>2 and i.idEvento=" . $idEvento);
                    $cantidadE=$conexion->Siguiente();

                    $conexion->Ejecuto("select PorcentajeExtranjeros1, CupoMaximo from evento where idEvento=" . $idEvento);
                    $valores=$conexion->Siguiente();

                    $cupoExtranjeros = ($valores['PorcentajeExtranjeros1'] * $valores['CupoMaximo']) / 100;

                    if ($cupoExtranjeros > 0 && $cantidadE['Cantidad'] >= $cupoExtranjeros) {
                        $espera = true;
                    }
                }
            } elseif ($datosEvento['FechaInicioInscripcion2'] < $hoy && $datosEvento['FechaFinInscripcion2'] > $hoy) {
                //FASE II
                //Chequeo si es una inscripción eliminada en la Fase I
                $actualizar = false;

                if ($accion == "inscripcionCN") { //Cambio de nombre
                    $conexion->Ejecuto("select * from inscripcionevento where idInscripcion=" . $idInscNueva);
                    $inscExistente=$conexion->Siguiente();
                } else {
                    $conexion->Ejecuto("select idInscripcion from inscripcionevento where idEvento=" . $idEvento . " and idSocio=" . $logueado);
                    $inscExistente=$conexion->Siguiente();
                }

                if ($inscExistente['idInscripcion'] != "") {
                    $actualizar = true;
                }

                if ($accion != "inscripcionCN") { //No es cambio de nombre
                    $conexion->Ejecuto("select PorcentajeRotarios2, PorcentajeExtranjeros2, CupoMaximo, Reserva from evento where idEvento=" . $idEvento);
                    $valores=$conexion->Siguiente();

                    $cupoRotario = ($valores['PorcentajeRotarios2'] * $valores['CupoMaximo']) / 100;
                    $cupoExtranjeros = ($valores['PorcentajeExtranjeros2'] * $valores['CupoMaximo']) / 100;
                    $cupoTotal = $valores['CupoMaximo'] - $valores['Reserva'];

                    $distritosOrg = obtenerOrganizadores($conexion, $idEvento, "", true);

                    $conexion->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where i.idEvento=" . $idEvento . " and s.idSocio=i.idSocio and i.Eliminado=0 and i.Reserva=0 and i.Aprobado<>2 and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito not in (" . $distritosOrg . ")");
                    $inscriptos=$conexion->Siguiente();

                    $disponible = $cupoTotal - $inscriptos['Cantidad'];

                    if ($socio['Rueda'] == "Rotary" && $socio['NombreDistrito'] != "Otro") {
                        $conexion->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre<>'Otro' and s.idTipoRueda=3 and s.idSocio=i.idSocio and i.Eliminado=0 and i.Reserva=0 and i.Aprobado<>2 and i.idEvento=" . $idEvento);
                        $cantidadR=$conexion->Siguiente();

                        if ($disponible <= 0 && $esOrganizador != "SI") {
                            $espera = true;
                        } else {
                            if ($cupoRotario > 0 && $cantidadR['Cantidad'] >= $cupoRotario && $esOrganizador != "SI") {
                                $espera = true;
                            }
                        }
                    } elseif ($socio['NombreDistrito'] == "Otro") {
                        $conexion->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre='Otro' and s.idSocio=i.idSocio and i.Eliminado=0 and i.Aprobado<>2 and i.idEvento=" . $idEvento);
                        $cantidadE=$conexion->Siguiente();

                        if ($disponible <= 0) {
                            $espera = true;
                        } else {
                            if ($cupoExtranjeros > 0 && $cantidadE['Cantidad'] >= $cupoExtranjeros) {
                                $espera = true;
                            }
                        }
                    } elseif ($disponible <= 0 && $esOrganizador != "SI") {
                        $espera = true;
                    }
                }
            }
        } else {
            $conexion->Ejecuto("select count(idInscripcion) as 'Inscriptos' from inscripcionevento where Eliminado=0 and Aprobado<>2 and idEvento=" . $idEvento);
            $cantInscriptos=$conexion->Siguiente();
            $conexion->Ejecuto("select CupoMaximo from evento where idEvento=" . $idEvento);
            $cupoTotal=$conexion->Siguiente();

            if ($cantInscriptos >= $cupoTotal) {
                $espera = true;
            }
        }

        if ($espera) {
            if ($tipoEvento == "E.R.A.U.P.") {
                if ($representante) {
                    if ($datosEvento['FechaInicioInscripcion'] < $hoy && $datosEvento['FechaFinInscripcion'] > $hoy) {
                        $cuerpo = utf8_encode("Estás inscripto/a a ") . $evento['Nombre'] . " (" . $organiza . ")" . utf8_encode(". El cupo reservado de tu distrito para el evento fue superado, por el momento estás en lista de espera.");
                    } elseif ($datosEvento['FechaInicioInscripcion2'] < $hoy && $datosEvento['FechaFinInscripcion2'] > $hoy) {
                        $cuerpo = utf8_encode("Estás inscripto/a a ") . $evento['Nombre'] . " (" . $organiza . ")" . utf8_encode(". El cupo del evento fue superado, por el momento estás en lista de espera.");
                    }

                    $aprobado = 4;
                } else {
                    if ($datosEvento['FechaInicioInscripcion'] < $hoy && $datosEvento['FechaFinInscripcion'] > $hoy) {
                        $cuerpo = utf8_encode("Está pendiente de aprobación tu inscripción a ") . $evento['Nombre'] . " (" . $organiza . ")" . utf8_encode(". El cupo reservado de tu distrito para el evento fue superado, por el momento estás en lista de espera.");
                    } elseif ($datosEvento['FechaInicioInscripcion2'] < $hoy && $datosEvento['FechaFinInscripcion2'] > $hoy) {
                        $cuerpo = utf8_encode("Está pendiente de aprobación tu inscripción a ") . $evento['Nombre'] . " (" . $organiza . ")" . utf8_encode(". El cupo del evento fue superado, por el momento estás en lista de espera.");
                    }

                    $aprobado = 3;
                }
            } else {
                if (($tipo == 0 && $presidente && $esOrganizador == "SI") || ($tipo == 0 && $representante && $esOrganizador == "SI") || ($tipo == 1 && $representante)) {
                    $cuerpo = utf8_encode("Estás inscripto/a a ") . $evento['Nombre'] . " (" . $organiza . ")" . utf8_encode(". El cupo del evento fue superado, por el momento estás en lista de espera.");
                    $aprobado = 4;
                } else {
                    $cuerpo = utf8_encode("Está pendiente de aprobación tu inscripción a ") . $evento['Nombre'] . " (" . $organiza . ")" . utf8_encode(". El cupo del evento fue superado, por el momento estás en lista de espera.");
                    $aprobado = 3;
                }
            }

            //Se guarda la inscripción
            if ($actualizar) {
                $conexion->Ejecuto("update inscripcionevento set idCalidadAsistencia=" . $calidad . ",idTransporteEvento=" . $transporte . ",FechaInscripcion='" . $fechaInsert . "',Aprobado=" . $aprobado . ", Observaciones='', Monto=0, idMoneda=NULL, Cotizacion=NULL, FechaPago='NULL',Reserva=0,Eliminado=0 where idInscripcion=" . $inscExistente['idInscripcion']);
            } else {
                $conexion->Ejecuto("insert into inscripcionevento (idSocio, idCalidadAsistencia, idEvento, idTransporteEvento, FechaInscripcion, Aprobado) values (" . $idSocio . "," . $calidad . "," . $idEvento . "," . $transporte . ",'" . $fechaInsert . "'," . $aprobado . ")");
            }
        } else {
            if (($tipo == 0 && $presidente && $esOrganizador == "SI") || ($tipo == 0 && $representante && $esOrganizador == "SI") || ($tipo == 1 && $representante)) {
                $cuerpo = utf8_encode("Estás inscripto/a a ") . $evento['Nombre'] . " (" . $organiza . ")";
                $aprobado = 1;

                //Inserto inscripcion en historialinscripcion
                $conexion->Ejecuto("insert into historialinscripcion (idSocio, idEvento, CalidadAsistencia) values (" . $idSocio . "," . $idEvento . ",'" . $calidad . "')");

                if ($socio['Calidad'] == "Instructor") {
                    //Actualizo el historial del socio en historialevento
                    $conexion->Ejecuto("update historialevento set VecesInstructor=VecesInstructor+1 where idSocio=" . $idSocio . " and idTipoEvento=" . $idtipoevento);
                }

                //Actualizo el historial del socio en historialevento
                $conexion->Ejecuto("update historialevento set CantidadAsistencias=CantidadAsistencias+1 where idSocio=" . $idSocio . " and idTipoEvento=" . $idtipoevento);

                //SI ES ERAUP, INSERTO REGISTRO DE HISTORIAL DE MESA
                if ($tipoEvento == "E.R.A.U.P.") {
                    if ($calidad == "Instructor") {
                        $conexion->Ejecuto("insert into historialmesaeraup (Mesa,idSocio,Instructor) values ('" . $calidad . "'," . $idSocio . ",1)");
                    } else {
                        $conexion->Ejecuto("insert into historialmesaeraup (Mesa,idSocio,Instructor) values ('" . $calidad . "'," . $idSocio . ",0)");
                    }
                }
            } else {
                if ($accion == "inscripcionCN") {
                    if ($inscExistente['Aprobado'] != 1 || $inscExistente['Aprobado'] != 3) {
                        $cuerpo = utf8_encode("Está pendiente de aprobación tu inscripción a ") . $evento['Nombre'] . " (" . $organiza . ")";
                        $aprobado = 0;
                    } else {
                        $cuerpo = utf8_encode("Tu inscripción a ") . $evento['Nombre'] . " (" . $organiza . ")" . utf8_encode(" está confirmada.");
                        $aprobado = 1;
                    }
                } else {
                    $cuerpo = utf8_encode("Está pendiente de aprobación tu inscripción a ") . $evento['Nombre'] . " (" . $organiza . ")";
                    $aprobado = 0;
                }
            }

            //Se guarda la inscripción
            if ($actualizar) {
                if ($accion == "inscripcionCN") {
                    $conexion->Ejecuto("update inscripcionevento set idCalidadAsistencia=" . $inscExistente['idCalidadAsistencia'] . ",idTransporteEvento=" . $inscExistente['idTransporteEvento'] . ",FechaInscripcion='" . $fechaInsert . "',Aprobado=" . $aprobado .", Observaciones='" . $inscExistente['Observaciones'] . "', Monto=" . $inscExistente['Monto'] . ", idMoneda=" . $inscExistente['idMoneda'] . ", Cotizacion=" . $inscExistente['Cotizacion'] . ", FechaPago='" . $inscExistente['FechaPago'] . "',Reserva=0,Eliminado=0 where idInscripcion=" . $inscExistente['idInscripcion']);
                } else {
                    $conexion->Ejecuto("update inscripcionevento set idCalidadAsistencia=" . $calidad . ",idTransporteEvento=" . $transporte . ",FechaInscripcion='" . $fechaInsert . "',Aprobado=" . $aprobado .", Observaciones='', Monto=0, idMoneda=NULL, Cotizacion=NULL, FechaPago='NULL',Reserva=0,Eliminado=0 where idInscripcion=" . $inscExistente['idInscripcion']);
                }
            } else {
                $conexion->Ejecuto("insert into inscripcionevento (idSocio, idCalidadAsistencia, idEvento, idTransporteEvento, FechaInscripcion, Aprobado) values (" . $idSocio . "," . $calidad . "," . $idEvento . "," . $transporte . ",'" . $fechaInsert . "'," . $aprobado . ")");
            }
        }

        if ($tipoEvento == "E.R.A.U.P.") {
            if ($socio['NombreDistrito'] != "Otro") {
                $conexion->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where i.idSocio=s.idSocio and i.idEvento=" . $idEvento . " and i.Eliminado=0 and i.Aprobado<>2 and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito=" . $socio['idDistrito']);
                $cantidad=$conexion->Siguiente();

                $conexion->Ejecuto("update asistenciaeraup set Inscriptos=" . ($cantidad['Cantidad'] + 1) . " where idEvento=" . $idEvento . " and idDistrito=" . $socio['idDistrito']);
            }

            $conexion->Ejecuto("select * from servicioeraup where idEvento=" . $idEvento . " order by Nombre ASC");
            $x = 1;

            while ($rowServicio=$conexion->Siguiente()) {
                $prioridad = $_POST['prioridadS' . $x];

                $conexion2->Ejecuto("insert into inscripcionservicioeraup (idEvento,idSocio,idServicioERAUP,Prioridad) values (" . $idEvento . "," . $idSocio . "," . $rowServicio['idServicioERAUP'] . "," . $prioridad . ")");

                $x += 1;
            }
        }

        try {
            if (($tipo == 0 && $presidente && $esOrganizador == "SI") || ($tipo == 0 && $representante && $esOrganizador == "SI") || ($tipo == 1 && $representante)) {
                enviarCorreo($socio['Email'], "Inscripción confirmada", $cuerpo);

                if ($espera) {
                    $mensaje = "Tu inscripción está confirmada, quedaste en lista de espera. Te enviamos un correo para tu registro personal.";
                } else {
                    $mensaje = "Tu inscripción está confirmada, te enviamos un correo para tu registro personal.";
                }
            } else {
                enviarCorreo($socio['Email'], "Inscripción pendiente de aprobación", $cuerpo);

                if ($espera) {
                    $mensaje = "Tu inscripción está pendiente de aprobación, quedaste en lista de espera. Te enviamos un correo para tu registro personal.";
                } else {
                    $mensaje = "Tu inscripción está pendiente de aprobación, te enviamos un correo para tu registro personal.";
                }
            }
        } catch (Exception $e) {
            if (($tipo == 0 && $presidente && $esOrganizador == "SI") || ($tipo == 0 && $representante && $esOrganizador == "SI") || ($tipo == 1 && $representante)) {
                if ($espera) {
                    $mensaje = "Tu inscripción está confirmada, quedaste en lista de espera. Ocurrió un error al intentar enviarte un correo para tu registro personal.";
                } else {
                    $mensaje = "Tu inscripción está confirmada, ocurrió un error al intentar enviarte un correo para tu registro personal.";
                }
            } else {
                if ($espera) {
                    $mensaje = "Tu inscripción está pendiente de aprobación, quedaste en lista de espera. Ocurrió un error al intentar enviarte un correo para tu registro personal.";
                } else {
                    $mensaje = "Tu inscripción está pendiente de aprobación, ocurrió un error al intentar enviarte un correo para tu registro personal.";
                }
            }
        }

        if (!($tipo == 0 && $presidente && $esOrganizador == "SI") && !($tipo == 0 && $representante && $esOrganizador == "SI") && !($tipo == 1 && $representante)) {
            //Se envía un correo al responsable de aprobar la inscripción
            if ($tipoEvento == "E.R.A.U.P.") {
                if ($socio['NombreDistrito'] != "Otro") {
                    $sqlResponsable = "select s.Email from socio s, distrito d, club c, cargodistrito ca, historialcargodistrito h where s.idClub=c.idClub and s.idSocio=h.idSocio and ca.Nombre='Representante Distrital' and ca.idCargoDistrito=h.idCargoDistrito and d.idDistrito=" . $socio['idDistrito'] . " and c.idDistrito=d.idDistrito and h.idPeriodo=" . $idPeriodoActual . " group by s.Email";
                } elseif ($socio['NombreDistrito'] == "Otro") {
                    $distritosOrg = obtenerOrganizadores($conexion, $idEvento, "", true);

                    $sqlResponsable = "select s.Email from socio s, distrito d, club c, cargodistrito ca, historialcargodistrito h where s.idClub=c.idClub and s.idSocio=h.idSocio and ca.Nombre='Representante Distrital' and ca.idCargoDistrito=h.idCargoDistrito and d.idDistrito in (" . $distritosOrg . ") and c.idDistrito=d.idDistrito and h.idPeriodo=" . $idPeriodoActual . " group by s.Email";
                }
            } else {
                if ($evento['Tipo'] == 0) {
                    if (($cantDistritosO == 1 && $socio['idDistrito'] == $evento['Distrito'] && $socio['NombreClub'] != "Otro") || ($cantDistritosO > 1 && $esLocal && $socio['NombreClub'] != "Otro")) {
                        $sqlResponsable = "select s1.Email from socio s1, socio s2, cargoclub c, historialcargoclub h where c.Nombre='Presidente' and c.idCargoClub=h.idCargoClub and s1.idClub=s2.idClub and h.idSocio=s1.idSocio and s2.idSocio=" . $idSocio . " and h.idPeriodo=" . $idPeriodoActual;
                    } else {
                        $sqlResponsable = "select s.Email from socio s, distrito d, club c, cargodistrito ca, historialcargodistrito h where s.idSocio=h.idSocio and c.idClub=s.idClub and ca.Nombre='Representante Distrital' and ca.idCargoDistrito=h.idCargoDistrito and c.idDistrito in (" . $distritos . ") and h.idPeriodo=" . $idPeriodoActual . " group by s.Email";
                        $sqlResponsable2 = "select s.Email from socio s, distrito d, club c, cargodistrito ca, historialcargodistrito h where s.idClub=c.idClub and s.idSocio=h.idSocio and ca.Nombre='Representante Distrital' and ca.idCargoDistrito=h.idCargoDistrito and d.Nombre not in ('Otro') and c.idDistrito=(select d1.idDistrito from distrito d1, club c1 where c1.idClub=" . $socio['idClub'] . " and c1.idDistrito=d1.idDistrito) and h.idPeriodo=" . $idPeriodoActual . " group by s.Email";
                    }
                } elseif ($evento['Tipo'] == 1) {
                    if ($socio['NombreDistrito'] != "Otro") {
                        $sqlResponsable = "select s.Email from socio s, distrito d, club c, cargodistrito ca, historialcargodistrito h where s.idClub=c.idClub and s.idSocio=h.idSocio and ca.Nombre='Representante Distrital' and ca.idCargoDistrito=h.idCargoDistrito and d.Nombre not in ('Otro') and c.idDistrito=(select d1.idDistrito from distrito d1, club c1 where c1.idClub=" . $socio['idClub'] . " and c1.idDistrito=d1.idDistrito) and h.idPeriodo=" . $idPeriodoActual . " group by s.Email";
                    } else {
                        $sqlResponsable = "select s.Email from socio s, distrito d, club c, cargodistrito ca, historialcargodistrito h where s.idClub=c.idClub and s.idSocio=h.idSocio and ca.Nombre='Representante Distrital' and ca.idCargoDistrito=h.idCargoDistrito and c.idDistrito in (" . $distritos . ") and h.idPeriodo=" . $idPeriodoActual . " group by s.Email";
                    }
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

            if ($cantInscriptos <= $cupoTotal) {
                $cuerpo = utf8_encode("Está pendiente de aprobación la inscripción de ") . $socio['Nombres'] . " " . $socio['Apellidos'] . " a " . $evento['Nombre'] . " (" . $organiza . ")";
            } else {
                $cuerpo = utf8_encode("Está en lista de espera la inscripción de ") . $socio['Nombres'] . " " . $socio['Apellidos'] . " a " . $evento['Nombre'] . " (" . $organiza . ")";
            }

            if (!($tipo == 0 && $representante && $esOrganizador != "SI")) {
                if ($sqlResponsable2 != "") {
                    $conexion->Ejecuto($sqlResponsable2);
                    $responsable2=$conexion->Siguiente();

                    $cuerpo2 = "El socio " . $socio['Nombres'] . " " . $socio['Apellidos'] . " de tu distrito se ha inscripto a " . $evento['Nombre'] . " (" . $organiza . ")" . utf8_encode(". Este mensaje es solamente un aviso para que estés al tanto, no tenes que realizar ninguna acción en el sistema.");

                    try {
                        enviarCorreo($responsable2['Email'], "Notificación de inscripción", $cuerpo2);
                    } catch (Exception $e) {
                    }
                }
            }

            try {
                $envio = 0;

                for ($x=1;$x<=$cantResponsables;$x++) {
                    if ($responsables[$x] != "") {
                        enviarCorreo($responsables[$x], "Inscripción pendiente de aprobación", $cuerpo2);
                        $envio++;
                    }
                }

                if ($envio > 0) {
                    $mensaje .= " Le enviamos un correo al responsable de aprobar tu inscripción para notificarlo.";
                } else {
                    $mensaje .= " Ocurrió un error al intentar enviar el correo al responsable de aprobar tu inscripción para notificarlo.";
                }
            } catch (Exception $e) {
                $mensaje .= " Ocurrió un error al intentar enviar el correo al responsable de aprobar tu inscripción para notificarlo.";
            }
        }

        if ($accion == "inscripcionCN") {
            header('Location: inscriptos.php?id=' . $idEvento . '&idS=' . $idSocio . '&a2=1&a=eliminarS&idI=' . $idEliminar);
        }

        //Se muestra mensaje en pantalla
        $tpl->NewBlock("mensaje");
        $tpl->Assign("mensaje", utf8_encode($mensaje));
    } elseif ($accion == "cambio") {
        $idSocio = $_POST['idS'];
        $idEvento = $_POST['idE'];

        $conexion->Ejecuto("select d.idDistrito from distrito d, club c, socio s where s.idSocio=" . $idSocio . " and s.idClub=c.idClub and c.idDistrito=d.idDistrito");
        $distrito=$conexion->Siguiente();

        $conexion->Ejecuto("select e.Nombre, t.Nombre as 'Evento' from evento e, tipoevento t where e.idEvento=" . $idEvento . " and e.idTipoEvento=t.idTipoEvento");
        $evento=$conexion->Siguiente();

        $tpl->NewBlock("editarInscripto");
        $tpl->Assign("accion", "inscripcionC");
        $tpl->Assign("onclick", "C");

        if ($evento['Evento'] == "E.R.A.U.P.") {
            $esOrganizador = obtenerOrganizadores($conexion, $idEvento, $distrito['idDistrito'], false);

            if ($esOrganizador == "SI") {
                $tpl->Assign("deshabilitado", "disabled='disabled'");
                $tpl->Assign("marcado", "disabled='disabled'");
            }
        } else {
            $tpl->Assign("marcado", "disabled='disabled'");
        }

        $tpl->Assign("evento", $evento['Nombre'] . " - Cambio de nombre");
        $tpl->Assign("idEvento", $idEvento);

        $conexion->Ejecuto("select s.Nombres, s.Apellidos, i.idTransporteEvento, i.idCalidadAsistencia, i.Observaciones, i.Monto, i.FechaPago, i.Cotizacion, i.idMoneda, i.Reserva, d.idDistrito from inscripcionevento i, socio s, club c, distrito d where i.idEvento=" . $idEvento . " and i.idSocio=" . $idSocio . " and i.idSocio=s.idSocio and s.idClub=c.idClub and c.idDistrito=d.idDistrito");
        $socio=$conexion->Siguiente();

        $tpl->Assign("nombre", $socio['Nombres'] . " " . $socio['Apellidos']);
        $tpl->Assign("idSocio", $idSocio);

        $conexion->Ejecuto("select s.idSocio, s.Nombres, s.Apellidos from socio s, club c, distrito d where s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito=" . $socio['idDistrito'] . " and s.Activo=1 and s.idTipoRueda=2 and s.idSocio in (select i.idSocio from inscripcionevento i, socio s, club c, distrito d where i.idSocio=s.idSocio and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito=" . $socio['idDistrito'] . " and ((i.Aprobado not in (0,1) and i.Eliminado=0) or (i.Eliminado=1)) and i.idEvento=" . $idEvento . ") order by s.Nombres ASC, s.Apellidos ASC");

        $tpl->NewBlock("listaSocios");

        while ($rowSocios=$conexion->Siguiente()) {
            $tpl->newBlock("comboSocios");
            $tpl->assign("valor", $rowSocios['idSocio']);
            $tpl->assign("opcion", $rowSocios['Nombres'] . " " . $rowSocios['Apellidos']);
        }
    } elseif ($accion == "e") {
        $idSocio = $_POST['idS'];
        $idEvento = $_POST['idE'];

        $conexion->Ejecuto("select d.idDistrito from distrito d, club c, socio s where s.idSocio=" . $idSocio . " and s.idClub=c.idClub and c.idDistrito=d.idDistrito");
        $distrito=$conexion->Siguiente();

        $conexion->Ejecuto("select e.Nombre, t.Nombre as 'Evento' from evento e, tipoevento t where e.idEvento=" . $idEvento . " and e.idTipoEvento=t.idTipoEvento");
        $evento=$conexion->Siguiente();

        $tpl->NewBlock("editarInscripto");
        $tpl->Assign("accion", "inscripcionE");
        $tpl->Assign("onclick", "E");

        if ($evento['Evento'] == "E.R.A.U.P.") {
            $esOrganizador = obtenerOrganizadores($conexion, $idEvento, $distrito['idDistrito'], false);

            if ($esOrganizador == "SI") {
                $tpl->Assign("deshabilitado", "disabled='disabled'");
                $tpl->Assign("marcado", "disabled='disabled'");
            }
        } else {
            $tpl->Assign("marcado", "disabled='disabled'");
        }

        $tpl->Assign("evento", $evento['Nombre']);
        $tpl->Assign("idEvento", $idEvento);

        $conexion->Ejecuto("select s.Nombres, s.Apellidos, i.idTransporteEvento, i.idCalidadAsistencia, i.Observaciones, i.Monto, i.FechaPago, i.Cotizacion, i.idMoneda, i.Reserva, d.idDistrito from inscripcionevento i, socio s, club c, distrito d where i.idEvento=" . $idEvento . " and i.idSocio=" . $idSocio . " and i.idSocio=s.idSocio and s.idClub=c.idClub and c.idDistrito=d.idDistrito");
        $socio=$conexion->Siguiente();

        $tpl->Assign("nombre", $socio['Nombres'] . " " . $socio['Apellidos']);
        $tpl->Assign("idSocio", $idSocio);
        $tpl->NewBlock("noCN");
        $tpl->Assign("observaciones", $socio['Observaciones']);

        if ($socio['Monto'] == null || $socio['Monto'] == "" || $socio['Monto'] == 0) {
            $tpl->Assign("monto", "");
        } else {
            $tpl->Assign("monto", $socio['Monto']);
        }

        if ($socio['Cotizacion'] == null || $socio['Cotizacion'] == "" || $socio['Cotizacion'] == 0) {
            $tpl->Assign("cotizacion", "");
        } else {
            $tpl->Assign("cotizacion", $socio['Cotizacion']);
        }

        if ($socio['FechaPago'] == "0000-00-00" || $socio['FechaPago'] == "" || $socio['FechaPago'] == null) {
            $tpl->Assign("fechaP", "");
        } else {
            $fechaP = split("-", $socio['FechaPago']);
            $tpl->Assign("fechaP", $fechaP[2] . "/" . $fechaP[1] . "/" . $fechaP[0]);
        }

        if ($socio['Reserva'] == 1) {
            $tpl->Assign("marcado", "checked=checked");
        }

        $conexion->Ejecuto("select idMoneda, Nombre from moneda");
        while ($rowMoneda=$conexion->Siguiente()) {
            $tpl->newBlock("comboMoneda");
            $tpl->assign("valor", $rowMoneda['idMoneda']);
            $tpl->assign("opcion", $rowMoneda['Nombre']);

            if ($socio['idMoneda'] == $rowMoneda['idMoneda']) {
                $tpl->assign("seleccionado", "selected='selected'");
            }
        }

        $conexion->Ejecuto("select idCalidadAsistencia, Nombre from calidadasistenciaevento order by Nombre ASC");

        while ($rowCalidad=$conexion->Siguiente()) {
            if ($rowCalidad['Nombre'] == "Organizador") {
                if ($esOrganizador == "SI") {
                    $tpl->newBlock("comboCalidadE");
                    $tpl->assign("valor", $rowCalidad['idCalidadAsistencia']);
                    $tpl->assign("opcion", $rowCalidad['Nombre']);
                    $tpl->assign("seleccionado", "selected='selected'");
                }
            } else {
                $tpl->newBlock("comboCalidadE");
                $tpl->assign("valor", $rowCalidad['idCalidadAsistencia']);
                $tpl->assign("opcion", $rowCalidad['Nombre']);

                if ($socio['idCalidadAsistencia'] == $rowCalidad['idCalidadAsistencia']) {
                    $tpl->assign("seleccionado", "selected='selected'");
                }
            }
        }

        $conexion->Ejecuto("select idTransporteEvento, Nombre, Costo from transporteevento where idEvento=" . $idEvento);
        while ($rowTransporte=$conexion->Siguiente()) {
            $tpl->newBlock("comboTransporteE");
            $tpl->assign("valor", $rowTransporte['idTransporteEvento']);
            if ($rowTransporte['Costo'] > 0) {
                $tpl->assign("opcion", $rowTransporte['Nombre'] . " - $" . $rowTransporte['Costo']);
            } else {
                $tpl->assign("opcion", $rowTransporte['Nombre']);
            }

            if ($socio['idTransporteEvento'] == $rowTransporte['idTransporteEvento']) {
                $tpl->assign("seleccionado", "selected='selected'");
            }
        }
    } elseif ($accion == "inscripcionE" || $accion == "inscripcionC") { //Acepta edición de inscripción o cambio de nombre
        $idEvento=$_POST['idEvento'];
        $idSocio=$_POST['idSocio'];
        $calidad=$_POST['calidadE'];
        $transporte=$_POST['transporteE'];
        $observaciones=$_POST['observaciones'];
        $monto=$_POST['monto'];
        $cotizacion=$_POST['cotizacion'];
        $fechaP=$_POST['fechaPago'];
        $moneda=$_POST['moneda'];
        $reserva=$_POST['reserva'];

        if ($accion == "inscripcionC") {
            $nuevoIdSocio=$_POST['socios'];

            // Se verifica si la nueva inscripción ya existía y fue eliminada
            $conexion->Ejecuto("select idInscripcion from inscripcionevento where idEvento=" . $idEvento . " and idSocio=" . $nuevoIdSocio);
            $chequeo=$conexion->Siguiente();

            $conexion->Ejecuto("select idInscripcion from inscripcionevento where idEvento=" . $idEvento . " and idSocio=" . $idSocio);
            $eliminar=$conexion->Siguiente();

            header('Location: inscripcion.php?id=' . $idEvento . '&idA=' . $idSocio . '&idS=' . $nuevoIdSocio . '&a=inscripcionCN&idI=' . $eliminar['idInscripcion'] . '&idN=' . $chequeo['idInscripcion']);
        }

        $conexion->Ejecuto("select i.idInscripcion, i.Aprobado, i.Reserva, c.Nombre as 'Calidad' from inscripcionevento i, calidadasistenciaevento c where i.idCalidadAsistencia=c.idCalidadAsistencia and i.idEvento=" . $idEvento . " and i.idSocio=" . $idSocio);
        $aprobado=$conexion->Siguiente();

        if ($fechaP != "") {
            $fecha = split("/", $fechaP);
            $fechaPago = $fecha[2] . "-" . $fecha[1] . "-" . $fecha[0];
        } else {
            $fechaPago = "NULL";
        }

        if ($monto == "") {
            $monto = "NULL";
            $cotizacion = "NULL";
            $fechaPago = null;
            $moneda = "NULL";
        }

        if ($cotizacion == "") {
            $cotizacion = "NULL";
        }

        if ($moneda == "vacio") {
            $moneda = "NULL";
        }

        if ($reserva != "") {
            $reserva = 1;
        } else {
            $reserva = 0;
        }

        //Actualizo inscripción del socio
        if ($reserva == 1) {	//SI SE MARCÓ COMO RESERVA Y ESTABA EN ESPERA
            //Chequeo cantidad de cupos de reserva disponibles
            $conexion->Ejecuto("select count(i.idSocio) as 'Reservas', e.Reserva as 'Disponible' from inscripcionevento i, evento e where i.Reserva=1 and i.Eliminado=0 and i.idEvento=" . $idEvento . " and i.idEvento=e.idEvento");
            $reservas=$conexion->Siguiente();

            $sinLugar=false;

            if ($reservas['Reservas'] < $reservas['Disponible']) {
                if ($aprobado['Aprobado'] == 3) {
                    $conexion->Ejecuto("update inscripcionevento set idCalidadAsistencia=" . $calidad . ", idTransporteEvento=" . $transporte . ", Observaciones='" . $observaciones . "', Monto=" . $monto . ", Cotizacion=" . $cotizacion . ", FechaPago='" . $fechaPago . "', idMoneda=" . $moneda . ", Reserva=" . $reserva . ", Aprobado=0 where idSocio=" . $idSocio . " and idEvento=" . $idEvento);
                } elseif ($aprobado['Aprobado'] == 4) {
                    $conexion->Ejecuto("update inscripcionevento set idCalidadAsistencia=" . $calidad . ", idTransporteEvento=" . $transporte . ", Observaciones='" . $observaciones . "', Monto=" . $monto . ", Cotizacion=" . $cotizacion . ", FechaPago='" . $fechaPago . "', idMoneda=" . $moneda . ", Reserva=" . $reserva . ", Aprobado=1 where idSocio=" . $idSocio . " and idEvento=" . $idEvento);
                } else {
                    $conexion->Ejecuto("update inscripcionevento set idCalidadAsistencia=" . $calidad . ", idTransporteEvento=" . $transporte . ", Observaciones='" . $observaciones . "', Monto=" . $monto . ", Cotizacion=" . $cotizacion . ", FechaPago='" . $fechaPago . "', idMoneda=" . $moneda . ", Reserva=" . $reserva . " where idSocio=" . $idSocio . " and idEvento=" . $idEvento);

                    if ($aprobado['Reserva'] == 0) {
                        //Se ejecutan los movimientos que corresponda
                        movimientosERAUP($conexion, $conexion2, $idEvento, $aprobado['idInscripcion'], $idPeriodoActual);
                    }
                }
            } else {
                $sinLugar=true;
            }
        } else {
            $conexion->Ejecuto("update inscripcionevento set idCalidadAsistencia=" . $calidad . ", idTransporteEvento=" . $transporte . ", Observaciones='" . $observaciones . "', Monto=" . $monto . ", Cotizacion=" . $cotizacion . ", FechaPago='" . $fechaPago . "', idMoneda=" . $moneda . ", Reserva=" . $reserva . " where idSocio=" . $idSocio . " and idEvento=" . $idEvento);
        }

        if (!$sinLugar) {
            $conexion->Ejecuto("select Nombre from calidadasistenciaevento where idCalidadAsistencia=" . $calidad);
            $calidadActual=$conexion->Siguiente();

            $conexion->Ejecuto("select t.Nombre as 'Evento', e.idTipoEvento from evento e, tipoevento t where t.idTipoEvento=e.idTipoEvento and e.idEvento=" . $idEvento);
            $tipoEvento=$conexion->Siguiente();

            //Si ya fue aprobado, actualizo el historial de inscripciones del socio
            if ($aprobado['Aprobado'] == 1) {
                $conexion->Ejecuto("update historialinscripcion set CalidadAsistencia='" . $calidadActual['Nombre'] . "' where idSocio=" . $idSocio . " and idEvento=" . $idEvento);

                if ($aprobado['Calidad'] == "Instructor" && $calidadActual['Nombre'] != "Instructor") {
                    $conexion->Ejecuto("update historialevento set VecesInstructor=VecesInstructor-1 where idSocio=" . $idSocio . " and idTipoEvento=" . $tipoEvento['idTipoEvento']);
                } elseif ($aprobado['Calidad'] != "Instructor" && $calidadActual['Nombre'] == "Instructor") {
                    $conexion->Ejecuto("update historialevento set VecesInstructor=VecesInstructor+1 where idSocio=" . $idSocio . " and idTipoEvento=" . $tipoEvento['idTipoEvento']);
                }

                if ($tipoEvento['Evento'] == "E.R.A.U.P.") {
                    $conexion->Ejecuto("select * from historialmesaeraup where idSocio=" . $idSocio . " and Mesa='" . $aprobado['Calidad'] . "' order by idHistorialMesaEraup DESC limit 1");
                    $historico=$conexion->Siguiente();

                    if ($aprobado['Calidad'] == "Instructor" && $calidadActual['Nombre'] != "Instructor") {
                        $conexion->Ejecuto("update historialmesaeraup set Mesa='" . $calidadActual['Nombre'] . "', Instructor=0 where idHistorialMesaERAUP=" . $historico['idHistorialMesaEraup']);
                    } elseif ($aprobado['Calidad'] != "Instructor" && $calidadActual['Nombre'] == "Instructor") {
                        $conexion->Ejecuto("update historialmesaeraup set Mesa='" . $calidadActual['Nombre'] . "', Instructor=1 where idHistorialMesaERAUP=" . $historico['idHistorialMesaEraup']);
                    }
                }
            }

            if ($accion == "inscripcionC") {
                //header('Location: inscriptos.php?id=' . $idEvento . '&idS=' . $nuevoIdSocio . '&a2=1&a=eliminarS&idI=' . $chequeo['idInscripcion']);
            } else {
                //header('Location: inscriptos.php?id=' . $idEvento);
            }
        } else {
            //No hay reservas disponibles
            $tpl->NewBlock("mensaje");
            $tpl->Assign("mensaje", utf8_encode("No hay cupos de reserva disponibles, no se actualizó la información de la inscripción editada."));
        }
    } else {
        $idEvento=$_GET['idEvento'];

        $conexion->Ejecuto("select e.Nombre, e.FechaInicio, e.FechaFin, e.Ubicacion, e.Costo, e.idTipoEvento, t.Nombre as 'Evento', m.Nombre as 'Moneda' from evento e, moneda m, tipoevento t where e.idEvento=" . $idEvento . " and m.idMoneda=e.idMoneda and e.idTipoEvento=t.idTipoEvento");
        $evento=$conexion->Siguiente();

        $conexion->Ejecuto("select d.idDistrito from socio s, club c, distrito d where s.idSocio=" . $logueado . " and s.idClub=c.idClub and c.idDistrito=d.idDistrito");
        $socio=$conexion->Siguiente();

        $organiza = obtenerOrganizadores($conexion, $idEvento, "", false);

        if ($evento['Nombre'] != "") {
            $tpl->NewBlock("datosEvento");

            if ($evento['Evento'] == "E.R.A.U.P.") {
                $esOrganizador = obtenerOrganizadores($conexion, $idEvento, $socio['idDistrito'], false);

                if ($esOrganizador == "SI") {
                    $tpl->Assign("deshabilitado", "disabled='disabled'");
                }
            }

            $tpl->Assign("idEvento", $idEvento);
            $tpl->Assign("evento", $evento['Nombre'] . " - " . $organiza);

            $fecha = split(" ", $evento['FechaInicio']);
            $fechaI = split("-", $fecha[0]);
            $horaI = split(":", $fecha[1]);
            $tpl->Assign("fechaI", $fechaI[2] . "/" . $fechaI[1] . "/" . $fechaI[0] . " " . $horaI[0] . ":" . $horaI[1]);

            $fecha = split(" ", $evento['FechaFin']);
            $fechaF = split("-", $fecha[0]);
            $horaF = split(":", $fecha[1]);
            $tpl->Assign("fechaF", $fechaF[2] . "/" . $fechaF[1] . "/" . $fechaF[0] . " " . $horaF[0] . ":" . $horaF[1]);

            $tpl->Assign("ubicacion", $evento['Ubicacion']);
            $tpl->Assign("costo", $evento['Costo'] . " " . $evento['Moneda']);

            $conexion->Ejecuto("select idTransporteEvento, Nombre, Costo from transporteevento where idEvento=" . $idEvento);

            while ($rowTransporte=$conexion->Siguiente()) {
                $tpl->newBlock("comboTransporte");
                $tpl->assign("valor", $rowTransporte['idTransporteEvento']);
                if ($rowTransporte['Costo'] > 0) {
                    $tpl->assign("opcion", $rowTransporte['Nombre'] . " - $" . $rowTransporte['Costo']);
                } else {
                    $tpl->assign("opcion", $rowTransporte['Nombre']);
                }
            }

            $conexion->Ejecuto("select idCalidadAsistencia, Nombre from calidadasistenciaevento order by Nombre ASC");

            while ($rowCalidad=$conexion->Siguiente()) {
                if ($rowCalidad['Nombre'] == "Organizador") {
                    if ($esOrganizador == "SI") {
                        $tpl->newBlock("comboCalidad");
                        $tpl->assign("valor", $rowCalidad['idCalidadAsistencia']);
                        $tpl->assign("opcion", $rowCalidad['Nombre']);
                        $tpl->assign("seleccionado", "selected='selected'");
                    }
                } else {
                    $tpl->newBlock("comboCalidad");
                    $tpl->assign("valor", $rowCalidad['idCalidadAsistencia']);
                    $tpl->assign("opcion", $rowCalidad['Nombre']);
                }
            }

            if ($evento['Evento'] == "E.R.A.U.P.") {
                $conexion->Ejecuto("select idServicioERAUP, Nombre from servicioeraup where idEvento=" . $idEvento . " order by Nombre ASC");
                $tpl->newBlock("servicio");
                $cantAct=$conexion->Tamano();

                $y = 1;

                while ($rowServicio=$conexion->Siguiente()) {
                    $tpl->newBlock("actividad");
                    $tpl->assign("actividad", $rowServicio['Nombre']);
                    $tpl->assign("opcion", $y);

                    $x = 1;

                    while ($x <= $cantAct) {
                        $tpl->newBlock("comboPrioridad");
                        $tpl->assign("opcion", $x);
                        $x += 1;
                    }

                    $y += 1;
                }

                $tpl->newBlock("eraup");
                $tpl->assign("cantAct", $cantAct);
                $tpl->newBlock("caseERAUP");
            }
        }
    }
}

$conexion->Libero(); //Se cierra la conexión a la base
$conexion2->Libero(); //Se cierra la conexión a la base
$tpl->printToScreen(); //Se manda todo al HTML usando TPL

function movimientosERAUP($conexion, $conexion2, $idEvento, $idsReservados, $idPeriodoActual)
{
    $conexion->Ejecuto("select * from evento where idEvento=" . $idEvento);
    $infoEvento=$conexion->Siguiente();

    $hoy = date("Y-m-d H:i:s");
    $arrayIDs=split(",", $idsReservados);

    for ($x=0;$x<sizeof($arrayIDs);$x++) {
        $conexion->Ejecuto("select s.idSocio, t.Nombre as 'Rueda', d.Nombre as 'Distrito', d.idDistrito from socio s, inscripcionevento i, club c, distrito d, tiporueda t where s.idTipoRueda=t.idTipoRueda and s.idClub=c.idClub and c.idDistrito=d.idDistrito and i.idSocio=s.idSocio and i.idInscripcion=" . $arrayIDs[$x]);
        $reprobado=$conexion->Siguiente();

        $esOrganizador = obtenerOrganizadores($conexion, $idEvento, $reprobado['idDistrito'], false);

        //Busco al siguiente en espera dependiendo de si es Rotaractiano de AIRAUP, Rotario o Extranjero y de la fase de inscripción actual
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
                } elseif ($cupoRotario == 0 && $esOrganizador != "SI") { //No hay límite de Rotarios
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
                $conexion->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre='Otro' and s.idSocio=i.idSocio and i.Reserva=0 and i.Eliminado=0 and i.Aprobado not in (2,3,4) and i.idEvento=" . $idEvento);
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
                } elseif ($cupoExtranjeros == 0) { //No hay límite de extranjeros
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
            $cupoTotal = $valores['CupoMaximo'] - $valores['Reserva'];
            $organizadores = obtenerOrganizadores($conexion, $idEvento, "", true);

            $conexion->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where i.idEvento=" . $idEvento . " and s.idSocio=i.idSocio and i.Eliminado=0 and i.Reserva=0 and i.Aprobado<>2 and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito not in (" . $organizadores . ")");
            $inscriptos=$conexion->Siguiente();

            $disponible = $cupoTotal - $inscriptos['Cantidad'];

            $conexion->Ejecuto("select i.idInscripcion, i.Aprobado, s.Email, s.Nombres, s.Apellidos, s.idClub, t.Nombre as 'Rueda', d.Nombre as 'Distrito', e.Nombre as 'NomEvento' from evento e, inscripcionevento i, socio s, club c, distrito d, tiporueda t where i.idEvento=" . $idEvento . " and i.idEvento=e.idEvento and i.idSocio=s.idSocio and s.idTipoRueda=t.idTipoRueda and s.idClub=c.idClub and c.idDistrito=d.idDistrito and i.Aprobado in (3,4) and i.Eliminado=0 order by FechaInscripcion ASC");
            $cantidad=$conexion->Tamano();

            if ($cantidad > 0) {
                //Variable para determinar si hay que buscar otro inscripto para subir
                $entra = false;

                while (!$entra) {
                    $ingreso=$conexion->Siguiente();

                    if ($ingreso['Rueda'] == "Rotaract" && $ingreso['Distrito'] != "Otro") { //Es Rotaractiano de AIRAUP el que sube
                        $entra = true;
                    } elseif ($ingreso['Rueda'] == "Rotary" && $ingreso['Distrito'] != "Otro") { //Es Rotario de AIRAUP el que sube
                        //Se chequea si hay límite para Rotarios
                        $conexion2->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s where s.idTipoRueda=3 and s.idSocio=i.idSocio and i.Eliminado=0 and i.Aprobado<>2 and i.idEvento=" . $idEvento);
                        $cantidadR=$conexion2->Siguiente();

                        if ($cupoRotario > 0 && $cantidadR['Cantidad'] < $cupoRotario) { //Existe límite y queda lugar Rotarios
                            $entra = true;
                        } elseif ($cupoRotario == 0) { //No existe límite
                            $entra = true;
                        }
                    } elseif ($ingreso['Distrito'] == "Otro") { //Es extranjero el que sube
                        //Se chequea si hay límite para extranjeros
                        $conexion2->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre='Otro' and s.idSocio=i.idSocio and i.Eliminado=0 and i.Aprobado<>2 and i.idEvento=" . $idEvento);
                        $cantidadE=$conexion2->Siguiente();

                        if ($cupoExtranjeros > 0 && $cantidadE['Cantidad'] < $cupoExtranjeros) { //Existe límite y queda lugar extranjeros
                            $entra = true;
                        } elseif ($cupoExtranjeros == 0) { //No existe límite
                            $entra = true;
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
        $mensajeI = utf8_encode("Se liberaron cupos en ") . $nomEvento . utf8_encode(" y saliste de la lista de espera. Tu inscripción sigue pendiente de aprobación.");
        $mensajeR = utf8_encode("Se liberaron cupos en "). $nomEvento . " y " . $nombreC . utf8_encode(" salió de la lista de espera. Aún debes aprobar su inscripción.");
    } elseif ($aprobado == 1) {
        $mensajeI = utf8_encode("Se liberaron cupos en ") . $nomEvento . utf8_encode(" y saliste de la lista de espera. Tu inscripción fue aprobada previamente por lo que no tenes que realizar ninguna acción en el sistema.");
        $mensajeR = utf8_encode("Se liberaron cupos en ") . $nomEvento . " y " . $nombreC . utf8_encode(" salió de la lista de espera. Su inscripción fue aprobada previamente por lo que no tenes que realizar ninguna acción en el sistema.");
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
