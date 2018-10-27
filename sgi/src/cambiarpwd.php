<?php
ini_set("display_errors", 0);
include("config.php");
require_once("conexionDB.php");
session_start(); //Se inicia la sesi�n
$obj_con=new conectar;

require_once("class.TemplatePower.inc.php"); //Usando Template Power

$tpl=new TemplatePower("cambiarpwd.html");
$tpl->prepare();

$idSocio = $_SESSION['usuario'];

if ($idSocio == "") {
    header('Location: login.php');
} else {
    $accion=$_POST['accion'];

    $conexion= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());
    $idPeriodoActual = obtenerPeriodoActual($conexion);

    $conexion->Ejecuto("select Admin from socio where idSocio=" . $idSocio);
    $datosSocio=$conexion->Siguiente();

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

    if ($datosSocio['Admin'] == 1 || $representante || $adminEventos['Cantidad'] > 0 || $presidenteA['idSocio'] == $idSocio) {
        $tpl->newBlock("menuEventos");
    }

    if ($accion == "change") {
        $pwdActual=$_POST['pwdActual'];
        $pwdNueva=$_POST['pwdNueva'];

        $conexion->Ejecuto("select Password from socio where idSocio=" . $idSocio);
        $socio=$conexion->Siguiente();

        if ($socio['Password'] == $pwdActual) { //SI COINCIDEN, SE CAMBIA EL PWD
            $conexion->Ejecuto("update socio set Password='" . $pwdNueva . "' where idSocio=" . $idSocio);

            $tpl->NewBlock("mensaje");
            $tpl->Assign("mensaje", utf8_encode("La contrase�a se modific� correctamente."));
        } elseif ($socio['Password'] != $pwdActual) { //SI NO COINCIDEN, SE MUESTRA MENSAJE
            $tpl->NewBlock("mensaje");
            $tpl->Assign("mensaje", utf8_encode("La contrase�a actual ingresada no coincide con la almacenada en la base de datos"));
        }
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
