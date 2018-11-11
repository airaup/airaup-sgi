<?php
ini_set("display_errors", 0);
include("../../config/config.php");
include("../mailer/mailer.php");
include("../../helpers/conexionDB.php");
session_start(); //Se inicia la sesi�n
$obj_con=new conectar;

date_default_timezone_set('America/Argentina/Buenos_Aires');

include("../../lib/class.TemplatePower.inc.php"); //Usando Template Power

$tpl=new TemplatePower("views/inscriptos.html");
    $tpl->prepare();

$conexion= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());
$conexion2= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());

$idEvento=$_GET['id'];
$idSocio=$_GET['idS'];
$accion=$_POST['accion'];
$idPeriodoActual = obtenerPeriodoActual($conexion);

if ($accion == "") {
    $accion=$_GET['a'];
}

$logueado = $_SESSION['usuario'];

if ($logueado == "") {
    header('Location: modules/auth/login.php');
} else {
    if ($accion == "eliminarS") { //Si se elimina la inscripci�n
        $cantBorrar=$_POST['cantBorrar'];
        $idEvento=$_POST['idEvento'];
        $accion2=$_GET['a2'];

        if ($accion2 == 1) {
            $cantBorrar=1;
            $idEvento=$_GET['id'];
        }

        $conexion->Ejecuto("select count(i.idInscripcion) as 'Cantidad', e.CupoMaximo, e.FechaInicioInscripcion, e.FechaFinInscripcion, e.FechaInicioInscripcion2, e.FechaFinInscripcion2, e.PorcentajeRotarios1, e.PorcentajeRotarios2, e.PorcentajeExtranjeros1, e.PorcentajeExtranjeros2, e.Reserva, t.Nombre as 'TipoEvento' from inscripcionevento i, evento e, tipoevento t where i.idEvento=" . $idEvento . " and i.idEvento=e.idEvento and i.Aprobado in (0,1,3,4) and i.Eliminado=0 and e.idTipoEvento=t.idTipoEvento");
        $inscriptos=$conexion->Siguiente();

        $controlar = false;
        $eraup = false;

        if ($inscriptos['TipoEvento'] == "E.R.A.U.P.") {
            $eraup = true;
        }

        if ($inscriptos['Cantidad'] > $inscriptos ['CupoMaximo']) {
            $controlar = true;
        }

        $cuposAbiertos = 0;
        $idsEliminados = "";

        for ($i=1;$i<=$cantBorrar;$i++) {
            if ($accion2 == 1) {
                $idInscripcion=$_GET['idI'];
            } else {
                $idInscripcion=$_POST['eliminarI' . $i];
            }

            $conexion->Ejecuto("select i.Aprobado, i.idEvento, i.idSocio, c.Nombre as 'Calidad', d.idDistrito, d.Nombre as 'Distrito', s.idTipoRueda as 'Rueda' from inscripcionevento i, calidadasistenciaevento c, socio s, club cl, distrito d where s.idSocio=i.idSocio and s.idClub=cl.idClub and cl.idDistrito=d.idDistrito and c.idCalidadAsistencia=i.idCalidadAsistencia and i.idInscripcion=" . $idInscripcion);
            $aprobado=$conexion->Siguiente();

            //Se borra el registro de inscripcionevento
            $conexion->Ejecuto("update inscripcionevento set Eliminado=1 where idInscripcion=" . $idInscripcion);

            if ($inscriptos['TipoEvento'] == "E.R.A.U.P.") {
                //Restar uno de los inscriptos del distrito que corresponda si es Rotaractiano de AIRAUP
                if ($aprobado['Distrito'] != "Otro" && $aprobado['Rueda'] == 2) {
                    $conexion->Ejecuto("update asistenciaeraup set Inscriptos=Inscriptos-1 where idDistrito=" . $aprobado['idDistrito'] . " and idEvento=" . $idEvento);
                }
            }

            //Si estaba aprobado previamente, se resta una asistencia al tipo de evento y se elimina el registro hist�rico del socio
            if ($aprobado['Aprobado'] == 1 || $aprobado['Aprobado'] == 4) {
                $conexion->Ejecuto("select idTipoEvento from evento where idEvento=" . $aprobado['idEvento']);
                $tipoEvento=$conexion->Siguiente();

                if ($aprobado['Calidad'] != "Instructor") {
                    $conexion->Ejecuto("update historialevento set CantidadAsistencias=CantidadAsistencias-1 where idSocio=" . $aprobado['idSocio'] . " and idTipoEvento=" . $tipoEvento['idTipoEvento']);
                } else {
                    $conexion->Ejecuto("update historialevento set CantidadAsistencias=CantidadAsistencias-1, VecesInstructor=VecesInstructor-1 where idSocio=" . $aprobado['idSocio'] . " and idTipoEvento=" . $tipoEvento['idTipoEvento']);
                }

                $conexion->Ejecuto("delete from historialinscripcion where idSocio=" . $aprobado['idSocio'] . " and idEvento=" . $aprobado['idEvento']);

                if ($inscriptos['TipoEvento'] == "E.R.A.U.P.") {
                    //Se elimina la asistencia hist�rica a la mesa
                    $conexion->Ejecuto("select idHistorialMesaERAUP as 'id' from historialmesaeraup where idSocio=" . $aprobado['idSocio'] . " order by idHistorialMesaERAUP DESC limit 1");
                    $idEliminar=$conexion->Siguiente();

                    $conexion->Ejecuto("delete from historialmesaeraup where idHistorialMesaERAUP=" . $idEliminar['id']);

                    $conexion->Ejecuto("delete from inscripcionservicioeraup where idEvento=" . $aprobado['idEvento'] . " and idSocio=" . $aprobado['idSocio']);
                }
            }

            if ($aprobado['Aprobado'] != 2) {
                $conexion->Ejecuto("select Email from socio where idSocio=" . $aprobado['idSocio']);
                $direccion=$conexion->Siguiente();

                $conexion->Ejecuto("select Nombre from evento where idEvento=" . $aprobado['idEvento']);
                $evento=$conexion->Siguiente();

                $errores = 0;

                //Se env�a correo al inscripto para notificarle la baja
                try {
                    enviarCorreo($direccion['Email'], utf8_encode("Baja de inscripci�n"), utf8_encode("Tu inscripci�n a ") . $evento['Nombre'] . utf8_encode(" fue dada de baja."));
                } catch (Exception $e) {
                    $errores++;
                }
            }

            if ($aprobado['Aprobado'] == 0 || $aprobado['Aprobado'] == 1) {
                $cuposAbiertos++;

                if ($inscriptos['TipoEvento'] == "E.R.A.U.P.") {
                    if ($i == $cantBorrar) {
                        $idsEliminados .= $idInscripcion;
                    } else {
                        $idsEliminados .= $idInscripcion . ",";
                    }
                }
            }
        }

        if (($controlar && $cuposAbiertos > 0) || ($eraup && $cuposAbiertos > 0) || $accion2 != 1) {
            if ($eraup) {
                movimientosERAUP($conexion, $conexion2, $idEvento, $idsEliminados, $idPeriodoActual);
            } else {
                movimientoDeCupos($conexion, $conexion2, $idEvento, $cuposAbiertos, $idPeriodoActual);
            }
        }

        $tpl->newBlock("mensaje");

        if ($errores == 0) {
            if ($accion2 == 1) {
                $mensaje = utf8_encode("El cambio fue realizado con �xito, ambos socios fueron notificados por correo.");
            } else {
                $mensaje = utf8_encode("Todos los inscriptos seleccionados fueron eliminados y notificados por correo. ");

                if ($controlar && $cuposAbiertos > 0) {
                    $mensaje .= $cuposAbiertos . utf8_encode(" inscriptos dejaron de estar en lista de espera y fueron notificados, as� como tambi�n los responsables de su aprobaci�n.");
                }
            }

            $tpl->assign("mensaje", $mensaje);
        } else {
            if ($accion2 == 1) {
                $mensaje = utf8_encode("El cambio fue realizado con �xito pero ocurrieron errores al enviar las notificaciones por correo.");
            } else {
                $mensaje = utf8_encode("Todos los inscriptos seleccionados fueron eliminados y pero ocurrieron errores al enviar las notificaciones por correo. ");

                if ($controlar && $cuposAbiertos > 0) {
                    $mensaje .= $cuposAbiertos . utf8_encode(" inscriptos dejaron de estar en lista de espera.");
                }
            }

            $tpl->assign("mensaje", $mensaje);
        }

        $conexion->Ejecuto("select Admin from socio where idSocio=" . $logueado);
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

        if ($presidenteA['idSocio'] == $logueado && !$presidente && !$representante) {
            $tpl->newBlock("menuStats");
        }

        if ($datosSocio['Admin'] == 1 || $representante || $adminEventos['Cantidad'] > 0 || $presidenteA['idSocio'] == $logueado) {
            $tpl->newBlock("menuEventos");
        }
    } else {
        $conexion->Ejecuto("select s.idClub, d.idDistrito, s.Admin from socio s, distrito d, club c where s.idSocio=" . $logueado . " and c.idClub=s.idClub and d.idDistrito=c.idDistrito");
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

        if ($presidenteA['idSocio'] == $logueado && !$presidente && !$representante) {
            $tpl->newBlock("menuStats");
        }

        if ($datosSocio['Admin'] == 1 || $representante || $adminEventos['Cantidad'] > 0 || $presidenteA['idSocio'] == $logueado) {
            $tpl->newBlock("menuEventos");
        }

        if ($idEvento == "") {
            $idEvento=$_POST['idEvento'];
        }

        $conexion->Ejecuto("select e.Nombre, e.idTipoEvento, e.FechaFinInscripcion, e.FechaInicioInscripcion2, t.Nombre as 'Evento' from evento e, tipoevento t where e.idEvento=" . $idEvento . " and e.idTipoEvento=t.idTipoEvento");
        $evento=$conexion->Siguiente();

        $organiza = obtenerOrganizadores($conexion, $idEvento, "", false);

        $tpl->newBlock("datosEvento");
        $tpl->assign("evento", $evento['Nombre'] . " - " . $organiza);
        $tpl->assign("idEventoD", $idEvento);

        if ($accion == "v") {
            $tpl->assign("accionD", "v");
        }

        $descarga=$_POST['d'];
        $accionD=$_POST['accionD'];

        if ($descarga == "d") {
            descargarListado($conexion, $conexion2, $presidente, $representante, $accionD, $datosSocio, $idEvento, $evento['Nombre']);
        }

        $ordenamiento = $_GET['orden'];

        if ($accion == "v") {
            if ($representante) {
                $sentencia = "select s.idSocio, s.Nombres, s.Apellidos, s.Documento, s.Sexo, ti.Nombre as 'Rueda', d.Vegetariano, d.Dieta, c.Nombre as 'Club', ca.Nombre as 'Calidad', i.idInscripcion, i.FechaInscripcion, i.Aprobado, i.idTransporteEvento, i.Eliminado, di.Nombre as 'Distrito', i.Reserva, case when i.Monto>0 then 1 else 0 end as 'Pago' from socio s, club c, calidadasistenciaevento ca, inscripcionevento i, datosmedicos d, tiporueda ti, distrito di where idEvento=" . $idEvento . " and c.idClub=s.idClub and c.idDistrito=di.idDistrito and c.idDistrito=" . $datosSocio['idDistrito'] . " and s.idSocio=i.idSocio and d.idSocio=s.idSocio and ti.idTipoRueda=s.idTipoRueda and ca.idCalidadAsistencia=i.idCalidadAsistencia";
            } elseif ($presidente) {
                $sentencia = "select s.idSocio, s.Nombres, s.Apellidos, s.Documento, s.Sexo, ti.Nombre as 'Rueda', d.Vegetariano, d.Dieta, c.Nombre as 'Club', ca.Nombre as 'Calidad', i.idInscripcion, i.FechaInscripcion, i.Aprobado, i.idTransporteEvento, i.Eliminado, di.Nombre as 'Distrito', i.Reserva, case when i.Monto>0 then 1 else 0 end as 'Pago' from socio s, club c, calidadasistenciaevento ca, inscripcionevento i, datosmedicos d, tiporueda ti, distrito di where idEvento=" . $idEvento . " and c.idClub=s.idClub and s.idClub=" . $datosSocio['idClub'] . " and c.idDistrito=di.idDistrito and s.idSocio=i.idSocio and d.idSocio=s.idSocio and ti.idTipoRueda=s.idTipoRueda and ca.idCalidadAsistencia=i.idCalidadAsistencia";
            }
        } else {
            //No lo dejo pasar si no pertencece a alg�n distrito organizador y es admin del mismo y/o RDR
            controlarAcceso($conexion, $datosSocio, $idEvento, $representante, $logueado, $presidenteA);

            //Consulta de datos por evento
            $sentencia = "select s.idSocio, s.Nombres, s.Apellidos, s.Documento, s.Sexo, ti.Nombre as 'Rueda', d.Vegetariano, d.Dieta, c.Nombre as 'Club', ca.Nombre as 'Calidad', i.idInscripcion, i.FechaInscripcion, i.Aprobado, i.idTransporteEvento, i.Eliminado, di.Nombre as 'Distrito', i.Reserva, case when i.Monto>0 then 1 else 0 end as 'Pago' from socio s, club c, calidadasistenciaevento ca, inscripcionevento i, datosmedicos d, tiporueda ti, distrito di where idEvento=" . $idEvento . " and c.idClub=s.idClub and c.idDistrito=di.idDistrito and s.idSocio=i.idSocio and d.idSocio=s.idSocio and ti.idTipoRueda=s.idTipoRueda and ca.idCalidadAsistencia=i.idCalidadAsistencia";
        }

        //Se a�ade el ordenamiento a la consulta
        if ($ordenamiento == 0 || $ordenamiento == "") {
            $sentencia .= " order by s.Nombres, s.Apellidos ASC";
        } elseif ($ordenamiento == 1) {
            $sentencia .= " order by Club ASC, s.Nombres, s.Apellidos ASC";
        } elseif ($ordenamiento == 2) {
            $sentencia .= " order by Calidad ASC, s.Nombres, s.Apellidos ASC";
        } elseif ($ordenamiento == 3) {
            $sentencia .= " order by idTransporteEvento ASC, s.Nombres, s.Apellidos ASC";
        } elseif ($ordenamiento == 4) {
            $sentencia .= " order by i.FechaInscripcion ASC";
        } elseif ($ordenamiento == 5) {
            $sentencia .= " order by i.Aprobado ASC, i.Eliminado, s.Nombres, s.Apellidos ASC";
        } elseif ($ordenamiento == 6) {
            $sentencia .= " order by Rueda ASC, s.Nombres, s.Apellidos ASC";
        } elseif ($ordenamiento == 7) {
            $sentencia .= " order by d.Vegetariano, d.Dieta ASC, s.Nombres, s.Apellidos ASC";
        } elseif ($ordenamiento == 8) {
            $sentencia .= " order by s.Sexo ASC, s.Nombres, s.Apellidos ASC";
        } elseif ($ordenamiento == 9) {
            $sentencia .= " order by Distrito ASC, Club ASC, s.Nombres, s.Apellidos ASC";
        } elseif ($ordenamiento == 10) {
            $sentencia .= " order by Reserva ASC";
        } elseif ($ordenamiento == 11) {
            $sentencia .= " order by Pago ASC";
        }

        $conexion->Ejecuto("select idSocio from eventoadmin where idSocio=" . $logueado . " and idEvento=" . $idEvento);
        $adminE=$conexion->Siguiente();

        $conexion->Ejecuto("select idTipoEvento from tipoevento where Nombre='E.R.A.U.P.'");
        $eraup=$conexion->Siguiente();

        if ($accion == "v") {
            if ($representante) {
                if ($evento['idTipoEvento'] == $eraup['idTipoEvento']) {
                    $sentenciaCant = "select count(s.idSocio) as 'Cantidad' from socio s, inscripcionevento i, club c, distrito d where i.idEvento=" . $idEvento . " and s.idSocio=i.idSocio and i.Eliminado=0 and s.idTipoRueda=2 and (i.Reserva is NULL or i.Reserva=0) and i.Aprobado <> 2 and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito=" . $datosSocio['idDistrito'];
                } else {
                    $sentenciaCant = "select count(s.idSocio) as 'Cantidad' from socio s, inscripcionevento i, club c, distrito d where i.idEvento=" . $idEvento . " and s.idSocio=i.idSocio and i.Eliminado=0 and i.Aprobado <> 2 and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito=" . $datosSocio['idDistrito'];
                }
            } elseif ($presidente) {
                $sentenciaCant = "select count(s.idSocio) as 'Cantidad' from socio s, inscripcionevento i where i.idEvento=" . $idEvento . " and s.idSocio=i.idSocio and i.Eliminado=0 and (i.Reserva is NULL or i.Reserva=0) and i.Aprobado <> 2 and s.idClub=" . $datosSocio['idClub'];
            }
        } else {
            if ($evento['idTipoEvento'] == $eraup['idTipoEvento']) {
                $esOrganizador = obtenerOrganizadores($conexion, $idEvento, $datosSocio['idDistrito'], false);

                if ($esOrganizador == "SI") {
                    $distritosO = obtenerOrganizadores($conexion, $idEvento, "", true);

                    $sentenciaCant = "select count(s.idSocio) as 'Cantidad' from socio s, inscripcionevento i, club c, distrito d where i.idEvento=" . $idEvento . " and s.idSocio=i.idSocio and s.idClub=c.idClub and c.idDistrito=d.idDistrito and i.Reserva<>1 and d.idDistrito not in (" . $distritosO . ") and i.Eliminado=0 and i.Aprobado <> 2";
                } else {
                    $sentenciaCant = "select count(s.idSocio) as 'Cantidad' from socio s, inscripcionevento i where i.idEvento=" . $idEvento . " and s.idSocio=i.idSocio and i.Reserva<>1 and i.Eliminado=0 and i.Aprobado <> 2";
                }
            } else {
                $sentenciaCant = "select count(s.idSocio) as 'Cantidad' from socio s, inscripcionevento i where i.idEvento=" . $idEvento . " and s.idSocio=i.idSocio and i.Eliminado=0 and i.Aprobado <> 2";
            }
        }

        $conexion->Ejecuto($sentenciaCant);
        $cantidadTotal = $conexion->Siguiente();

        $hoy = date("Y-m-d H:i:s");

        if ($evento['idTipoEvento'] == $eraup['idTipoEvento'] && $hoy < $evento['FechaInicioInscripcion2'] && $representante) {
            $esOrganizador = obtenerOrganizadores($conexion, $idEvento, $datosSocio['idDistrito'], false);

            if ($esOrganizador == "SI" && $accion != "v") {
                $tpl->assign("inscriptos", $cantidadTotal['Cantidad'] . " registros");
                $tpl->assign("eraup", "organizadores, reservas, ");
            } elseif ($esOrganizador == "SI" && $accion == "v") {
                $tpl->assign("inscriptos", $cantidadTotal['Cantidad'] . " registros");
            } else {
                $conexion->Ejecuto("select CupoReservado as 'Cupo' from asistenciaeraup where idEvento=" . $idEvento . " and idDistrito=" . $datosSocio['idDistrito']);
                $cupoDistrito=$conexion->Siguiente();

                $tpl->assign("inscriptos", $cantidadTotal['Cantidad'] . " inscriptos / " . $cupoDistrito['Cupo'] . " cupos reservados");
                $tpl->assign("eraup", "reservas, Rotarios, ");
            }
        } else {
            $esOrganizador = obtenerOrganizadores($conexion, $idEvento, $datosSocio['idDistrito'], false);

            if ($evento['idTipoEvento'] == $eraup['idTipoEvento']) {
                if ($esOrganizador == "SI" && $accion != "v") {
                    $tpl->assign("eraup", "organizadores, reservas, ");
                } else {
                    $tpl->assign("eraup", "reservas, ");
                }
            }

            $tpl->assign("inscriptos", $cantidadTotal['Cantidad'] . " registros");
        }

        if ($accion == "v") {
            $tpl->assign("idEvento", $idEvento . "&a=v");
            $tpl->newBlock("botonVolver");
            $tpl->assign("urlVolver", "modules/users/perfil.php?a=i");
        } else {
            $tpl->assign("idEvento", $idEvento);
            $tpl->newBlock("botonVolver");
            $tpl->assign("urlVolver", "modules/events/eventos.php");
        }

        //Ejecuto la consulta
        $conexion->Ejecuto($sentencia);
        $contarI = 0;

        if ($evento['Evento'] == "E.R.A.U.P.") {
            $tpl->newBlock("tituloERAUP");
            if ($accion == "v") {
                $tpl->assign("idEvento", $idEvento . "&a=v");
            } else {
                $tpl->assign("idEvento", $idEvento);
            }
        }

        while ($inscriptos=$conexion->Siguiente()) {
            $contarI++;
            $tpl->newBlock("lineaInscripto");
            $tpl->assign("nombre", $inscriptos['Nombres'] . " " . $inscriptos['Apellidos']);
            $tpl->assign("documento", $inscriptos['Documento']);

            if ($inscriptos['Sexo'] == 1) {
                $tpl->assign("sexo", "Femenino");
            } elseif ($inscriptos['Sexo'] == 2) {
                $tpl->assign("sexo", "Masculino");
            }

            $tpl->assign("distrito", $inscriptos['Distrito']);
            $tpl->assign("club", $inscriptos['Club']);
            $tpl->assign("rueda", $inscriptos['Rueda']);
            $tpl->assign("calidad", $inscriptos['Calidad']);

            if ($inscriptos['idTransporteEvento'] == 0) {
                $tpl->assign("transporte", "Particular");
            } else {
                $conexion2->Ejecuto("select Nombre from transporteevento where idEvento=" . $idEvento . " and idTransporteEvento=" . $inscriptos['idTransporteEvento']);
                $transporte=$conexion2->Siguiente();

                $tpl->assign("transporte", $transporte['Nombre']);
            }

            if ($inscriptos['Vegetariano'] == 0) {
                if ($inscriptos['Dieta'] != "") {
                    $tpl->assign("dieta", "No, " . $inscriptos['Dieta']);
                } else {
                    $tpl->assign("dieta", "No");
                }
            } elseif ($inscriptos['Vegetariano'] == 1) {
                if ($inscriptos['Dieta'] != "") {
                    $tpl->assign("dieta", "Si, " . $inscriptos['Dieta']);
                } else {
                    $tpl->assign("dieta", "Si");
                }
            }

            $fecha = split(" ", $inscriptos['FechaInscripcion']);
            $fecha2 = split("-", $fecha[0]);

            $tpl->assign("fecha", $fecha2[2] . "/" . $fecha2[1] . "/" . $fecha2[0] . " " . $fecha[1]);

            if ($inscriptos['Eliminado'] == 0) {
                if ($inscriptos['Aprobado'] == 0) {
                    $tpl->assign("aprobado", "Pendiente de aprobacion");
                } elseif ($inscriptos['Aprobado'] == 1) {
                    $tpl->assign("aprobado", "Aprobado");
                } elseif ($inscriptos['Aprobado'] == 2) {
                    $tpl->assign("aprobado", "Reprobado");
                } elseif ($inscriptos['Aprobado'] == 3) {
                    $tpl->assign("aprobado", "En lista de espera");
                } elseif ($inscriptos['Aprobado'] == 4) {
                    $tpl->assign("aprobado", "Aprobado, en lista de espera");
                }
            } elseif ($inscriptos['Eliminado'] == 1) {
                if ($inscriptos['Aprobado'] == 0) {
                    $tpl->assign("aprobado", "ELIMINADO: Pendiente de aprobacion");
                } elseif ($inscriptos['Aprobado'] == 1) {
                    $tpl->assign("aprobado", "ELIMINADO: Aprobado");
                } elseif ($inscriptos['Aprobado'] == 2) {
                    $tpl->assign("aprobado", "ELIMINADO: Reprobado");
                } elseif ($inscriptos['Aprobado'] == 3) {
                    $tpl->assign("aprobado", "ELIMINADO: En lista de espera");
                } elseif ($inscriptos['Aprobado'] == 4) {
                    $tpl->assign("aprobado", "ELIMINADO: Aprobado, en lista de espera");
                }
            }

            $tpl->assign("idSocio", $inscriptos['idSocio']);

            if ($accion == "v") {
                $tpl->assign("idEvento", $idEvento . "&a2=v");
            } else {
                $tpl->assign("idEvento", $idEvento);
            }

            if ($accion != "v") {
                $entro = false;
                $eraup = false;

                if ($evento['Evento'] == "E.R.A.U.P." && $adminE['idSocio'] == $logueado) {
                    $entro = true;
                    $eraup = true;
                } elseif ($evento['Evento'] != "E.R.A.U.P." && ($representante || $adminE['idSocio'] == $logueado)) {
                    $entro = true;
                }

                if ($entro) {
                    $tpl->newBlock("botonesAdmin");
                    $tpl->assign("idSocio", $inscriptos['idSocio']);
                    $tpl->assign("idEvento", $idEvento);
                    $tpl->assign("contarI", $contarI);
                    $tpl->assign("idInscripcion", $inscriptos['idInscripcion']);

                    if ($inscriptos['Eliminado'] == 1) {
                        $tpl->assign("deshabilitado", "disabled='disabled'");
                    }

                    if ($eraup) {
                        if ($hoy >= $evento['FechaInicioInscripcion2'] && $inscriptos['Rueda'] == "Rotaract" && $inscriptos['Distrito'] != "Otro" && ($inscriptos['Aprobado'] == 0 || $inscriptos['Aprobado'] == 1) && $inscriptos['Eliminado'] == 0) {
                            $tpl->newBlock("cambioNombre");
                            $tpl->assign("idSocio", $inscriptos['idSocio']);
                            $tpl->assign("idEvento", $idEvento);
                        }
                    }
                }
            }

            if ($evento['Evento'] == "E.R.A.U.P.") {
                $tpl->newBlock("lineaERAUP");

                if ($inscriptos['Reserva'] == 1) {
                    $tpl->assign("reserva", "SI");
                } else {
                    $tpl->assign("reserva", "NO");
                }

                if ($inscriptos['Pago'] == 1) {
                    $tpl->assign("pago", "SI");
                } else {
                    $tpl->assign("pago", "NO");
                }
            }
        }

        if ($accion != "v") {
            if ($representante || $adminE['idSocio'] == $logueado) {
                $tpl->newBlock("elimS");
                $tpl->assign("idEvento", $idEvento);
            }
        }

        $tpl->newBlock("totalInscriptos");
        $tpl->assign("cantInscriptos", $contarI);
    }
}

$conexion->Libero(); //Se cierra la conexi�n a la base
$tpl->printToScreen(); //Se manda todo al HTML usando TPL

function controlarAcceso($conexion, $datosSocio, $idEvento, $representante, $logueado, $presidenteA)
{
    $conexion->Ejecuto("select count(idSocio) as 'Admin' from eventoadmin where idEvento=" . $idEvento . " and idSocio=" . $logueado);
    $esAdminE=$conexion->Siguiente();

    if ($esAdminE['Admin'] == 1) {
        return;
    }

    $conexion->Ejecuto("select t.Nombre as 'Evento' from evento e, tipoevento t where t.idTipoEvento=e.idTipoEvento and e.idEvento=" . $idEvento);
    $eraup=$conexion->Siguiente();

    if ($eraup['Evento'] == "E.R.A.U.P." && $presidenteA['idSocio'] == $logueado) {
        return;
    }

    $conexion->Ejecuto("select ed.idDistrito as 'Distrito' from evento e, eventodistrito ed where e.idEvento=" . $idEvento . " and e.idEvento=ed.idEvento");
    $cantDistritosO=$conexion->Tamano();

    if ($cantDistritos0 == 1) {
        $evento=$conexion->Siguiente();
        if ($datosSocio['idDistrito'] == $evento['Distrito'] && ($representante || $datosSocio['Admin'] == 1)) {
            return;
        } else {
            header('Location: modules/auth/login.php');
        }
    } else {
        for ($x=1;$x<=$cantDistritosO;$x++) {
            $evento=$conexion->Siguiente();

            if ($datosSocio['idDistrito'] == $evento['Distrito'] && ($representante || $datosSocio['Admin'] == 1)) {
                return;
            }
        }

        header('Location: modules/auth/login.php');
    }
}

function convertirBit($dato)
{
    if ($dato == 0) {
        return "No";
    } elseif ($dato == 1) {
        return "Si";
    }
}

function descargarListado($conexion, $conexion2, $presidente, $representante, $accion, $datosSocio, $idEvento, $nombreE)
{
    $nombreArchivo = quitarCaracteres($nombreE);
    $nombreArchivo = utf8_decode($nombreArchivo) . ".csv";
    $columnas = "Nombres;Apellidos;Documento;Fecha de Nac.;Email;Distrito;Club;Sexo;Rueda;Calidad de asistencia;Transporte;Es vegetariano;Consideraciones en la dieta;Fecha de inscripci�n;Estado de aprobaci�n;Monto pagado;Cotizaci�n;Fecha de pago;Moneda;Observaciones;Persona de contacto;Tel�fono de contacto;Relaci�n;Obra social;N�mero de socio;Grupo sangu�neo;Factor;Enfermedades cr�nicas;Internaci�n ult. 3 a�os;Enfermedades infecciosas;Intervenciones quir�rjicas;Alergias;Fuma;Lateralidad;Lentes;Aud�fonos;Limitaciones f�sicas;Donante de �rganos;Donante de m�dula;Medicamentos;Nombre de la droga;Cantidad suministrada";

    if ($accion == "v") {
        if ($representante) {
            $sentencia = "select s.idSocio, s.Nombres, s.Apellidos, s.Documento, s.FechaNac, s.Email, s.Sexo, ti.Nombre as 'Rueda', d.Vegetariano, d.Dieta, c.Nombre as 'Club', ca.Nombre as 'Calidad', i.FechaInscripcion, i.Aprobado, tr.Nombre as 'Transporte', i.Eliminado, di.Nombre as 'Distrito', i.Observaciones, i.Monto, i.idMoneda, i.Cotizacion, i.FechaPago, s.NombreContacto, s.TelefonoContacto, s.RelacionContacto from socio s, club c, calidadasistenciaevento ca, inscripcionevento i, datosmedicos d, tiporueda ti, distrito di, transporteevento tr where i.idEvento=" . $idEvento . " and c.idClub=s.idClub and c.idDistrito=di.idDistrito and c.idDistrito=" . $datosSocio['idDistrito'] . " and s.idSocio=i.idSocio and d.idSocio=s.idSocio and ti.idTipoRueda=s.idTipoRueda and ca.idCalidadAsistencia=i.idCalidadAsistencia and i.idTransporteEvento=tr.idTransporteEvento order by Distrito ASC, Club ASC, s.Nombres, s.Apellidos ASC";
        } elseif ($presidente) {
            $sentencia = "select s.idSocio, s.Nombres, s.Apellidos, s.Documento, s.FechaNac, s.Email, s.Sexo, ti.Nombre as 'Rueda', d.Vegetariano, d.Dieta, c.Nombre as 'Club', ca.Nombre as 'Calidad', i.FechaInscripcion, i.Aprobado, tr.Nombre as 'Transporte', i.Eliminado, di.Nombre as 'Distrito', i.Observaciones, i.Monto, i.idMoneda, i.Cotizacion, i.FechaPago, s.NombreContacto, s.TelefonoContacto, s.RelacionContacto from socio s, club c, calidadasistenciaevento ca, inscripcionevento i, datosmedicos d, tiporueda ti, distrito di, transporteevento tr where i.idEvento=" . $idEvento . " and c.idClub=s.idClub and s.idClub=" . $datosSocio['idClub'] . " and c.idDistrito=di.idDistrito and s.idSocio=i.idSocio and d.idSocio=s.idSocio and ti.idTipoRueda=s.idTipoRueda and ca.idCalidadAsistencia=i.idCalidadAsistencia and i.idTransporteEvento=tr.idTransporteEvento order by Distrito ASC, Club ASC, s.Nombres, s.Apellidos ASC";
        }
    } else {
        $sentencia = "select s.idSocio, s.Nombres, s.Apellidos, s.Documento, s.FechaNac, s.Email, s.Sexo, ti.Nombre as 'Rueda', d.Vegetariano, d.Dieta, c.Nombre as 'Club', ca.Nombre as 'Calidad', i.FechaInscripcion, i.Aprobado, tr.Nombre as 'Transporte', i.Eliminado, di.Nombre as 'Distrito', i.Observaciones, i.Monto, i.idMoneda, i.Cotizacion, i.FechaPago, s.NombreContacto, s.TelefonoContacto, s.RelacionContacto from socio s, club c, calidadasistenciaevento ca, inscripcionevento i, datosmedicos d, tiporueda ti, distrito di, transporteevento tr where i.idEvento=" . $idEvento . " and c.idClub=s.idClub and c.idDistrito=di.idDistrito and s.idSocio=i.idSocio and d.idSocio=s.idSocio and ti.idTipoRueda=s.idTipoRueda and ca.idCalidadAsistencia=i.idCalidadAsistencia and i.idTransporteEvento=tr.idTransporteEvento order by Distrito ASC, Club ASC, s.Nombres, s.Apellidos ASC";
    }

    $fh = fopen($nombreArchivo, 'w');
    fwrite($fh, $columnas . "\n");

    $conexion->Ejecuto($sentencia);

    while ($socios=$conexion->Siguiente()) {
        $linea = $socios['Nombres'] . ";" . $socios['Apellidos'] . ";" . $socios['Documento'] . ";" . $socios['FechaNac'] . ";" . $socios['Email'] . ";" . $socios['Distrito'] . ";" . $socios['Club'] . ";";

        if ($socios['Sexo'] == "1") {
            $linea .= "Femenino" . ";";
        } elseif ($socios['Sexo'] == "2") {
            $linea .= "Masculino" . ";";
        } else {
            $linea .= ";";
        }

        $linea .= $socios['Rueda'] . ";" . $socios['Calidad'] . ";" . $socios['Transporte'] . ";";

        if ($socios['Vegetariano'] == "0") {
            $linea .= "No" . ";";
        } elseif ($socios['Vegetariano'] == "1") {
            $linea .= "Si" . ";";
        } else {
            $linea .= ";";
        }

        $linea .= $socios['Dieta'] . ";";

        $fechaHora = split(" ", $socios['FechaInscripcion']);
        $fecha = split("-", $fechaHora[0]);

        $linea .= $fecha[2] . "/" . $fecha[1] . "/" . $fecha[0] . " " . $fechaHora[1] . ";";

        if ($socios['Eliminado'] == 0) {
            if ($socios['Aprobado'] == 0) {
                $linea .= "Pendiente de aprobacion";
            } elseif ($socios['Aprobado'] == 1) {
                $linea .= "Aprobado";
            } elseif ($socios['Aprobado'] == 2) {
                $linea .= "Reprobado";
            } elseif ($socios['Aprobado'] == 3) {
                $linea .= "En lista de espera";
            } elseif ($socios['Aprobado'] == 4) {
                $linea .= "Aprobado, en lista de espera";
            }
        } elseif ($socios['Eliminado'] == 1) {
            if ($socios['Aprobado'] == 0) {
                $linea .= "ELIMINADO: Pendiente de aprobacion";
            } elseif ($socios['Aprobado'] == 1) {
                $linea .= "ELIMINADO: Aprobado";
            } elseif ($socios['Aprobado'] == 2) {
                $linea .= "ELIMINADO: Reprobado";
            } elseif ($socios['Aprobado'] == 3) {
                $linea .= "ELIMINADO: En lista de espera";
            } elseif ($socios['Aprobado'] == 4) {
                $linea .= "ELIMINADO: Aprobado, en lista de espera";
            }
        }

        $fechaPago = split("-", $socios['FechaPago']);
        $fechaP = $fechaPago[2] . "/" . $fechaPago[1] . "/" . $fechaPago[0];

        $conexion2->Ejecuto("select Nombre from moneda where idMoneda=" . $socios['idMoneda']);
        $moneda = $conexion2->Siguiente();

        $linea .= ";" . $socios['Monto'] . ";" . $socios['Cotizacion'] . ";" . $fechaP . ";" . $moneda['Nombre'] . ";" . $socios['Observaciones'] . ";" . $socios['NombreContacto'] . ";" . $socios['TelefonoContacto'] . ";" . $socios['RelacionContacto'] . ";";

        $conexion2->Ejecuto("select * from datosmedicos where idSocio=" . $socios['idSocio']);
        $datosMedicos = $conexion2->Siguiente();

        $linea .= $datosMedicos['ObraSocial'] . ";" . $datosMedicos['NumeroSocio'] . ";" . $datosMedicos['GrupoSangre'] . ";" . $datosMedicos['Factor'] . ";" . convertirBit($datosMedicos['EnfermedadCronica']) . " - " . $datosMedicos['EnfermedadCronicaE'] . ";" . convertirBit($datosMedicos['Internacion3anos']) . " - " . $datosMedicos['Internacion3anosE'] . ";" . convertirBit($datosMedicos['EnfermedadInfecciosa']) . " - " . $datosMedicos['EnfermedadInfecciosaE'] . ";" . convertirBit($datosMedicos['IntervencionQuirurjica']) . " - " . $datosMedicos['IntervencionQuirurjicaE'] . ";" . convertirBit($datosMedicos['Alergia']) . " - " . $datosMedicos['AlergiaE'] . ";" . convertirBit($datosMedicos['Fuma']) . ";" . convertirBit($datosMedicos['Lateralidad']) . ";" . convertirBit($datosMedicos['Lentes']) . ";" . convertirBit($datosMedicos['Audifonos']) . ";" . convertirBit($datosMedicos['LimitacionFisica']) . " - " . $datosMedicos['LimitacionFisicaE'] . ";" . convertirBit($datosMedicos['DonanteOrganos']) . ";" . convertirBit($datosMedicos['DonanteMedula']) . ";" . $datosMedicos['NombreMedicamento'] . ";" . $datosMedicos['Droga'] . ";" . $datosMedicos['CantidadMedicamento'];

        fwrite($fh, utf8_decode($linea) . "\n");
    }

    fclose($fh);

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($nombreArchivo).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($nombreArchivo));
    readfile($nombreArchivo);

    unlink($nombreArchivo);
}

function movimientosERAUP($conexion, $conexion2, $idEvento, $idsEliminados, $idPeriodoActual)
{
    $conexion->Ejecuto("select * from evento where idEvento=" . $idEvento);
    $infoEvento=$conexion->Siguiente();

    $hoy = date("Y-m-d H:i:s");
    $arrayIDs=split(",", $idsEliminados);

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
                enviarCorreo($responsables[$x], "Cupos liberados en " . $espera['Evento'], $mensajeR);
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

function quitarCaracteres($string)
{
    $string = trim($string);

    $string = str_replace(
        array("|", "\"", "/", "?", ">", "< ", ":", "\\", "*"),
        '-',
        $string
    );

    return $string;
}
