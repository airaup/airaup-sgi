<?php
ini_set("display_errors", 0);
include("config.php");
include("mailer.php");
require_once("conexionDB.php");
session_start(); //Se inicia la sesi�n
$obj_con=new conectar;

require_once("class.TemplatePower.inc.php"); //Usando Template Power

$tpl=new TemplatePower("recoverpwd.html");
    $tpl->prepare();

$accion=$_POST['accion'];

$conexion= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());

if ($accion == "recover") {
    $direccion=$_POST['mail'];

    $conexion->Ejecuto("select idSocio, Password, Activo from socio where Email='" . $direccion . "'");
    $socio=$conexion->Siguiente();

    if ($socio['idSocio'] != "") { //SI EXISTE
        if ($socio['Activo'] == 0) { //SI EST� DESACTIVADA LA CUENTA
            $tpl->NewBlock("mensaje");
            $tpl->Assign("mensaje", utf8_encode("Tu cuenta est� desactivada, comunicate con tu Presidente."));
        } else {
            try {
                $subject = utf8_encode('Recuperar contrase�a - SGI');
                $body = utf8_encode('Tu contrase�a actual es ') . $socio['Password'] . utf8_encode('<br><br>Por favor no respondas este mensaje.<br>Sistema de Gesti�n Integral<br>AIRAUP');
                enviarCorreo($direccion, $subject, $body);

                $tpl->NewBlock("mensaje");
                $tpl->Assign("mensaje", utf8_encode("Te mandamos tu contrase�a por mail, revisa tu casilla."));
            } catch (Exception $e) {
                $tpl->NewBlock("mensaje");
                $tpl->Assign("mensaje", utf8_encode("Ocurri� un error al intentar enviar tu contrase�a. Por favor comunicate con tu Presidente."));
            }
        }
    } else { //SI NO EXISTE
        $tpl->NewBlock("mensaje");
        $tpl->Assign("mensaje", utf8_encode("La direcci�n de correo ingresada no existe en la base de datos."));
    }
}

$conexion->Libero(); //Se cierra la conexi�n a la base
$tpl->printToScreen(); //Se manda todo al HTML usando TPL
