<?php
ini_set("display_errors", 0);
include("config.php");
require_once("conexionDB.php");
require 'class.phpmailer.php';
require 'class.smtp.php';
session_start(); //Se inicia la sesi�n
$obj_con=new conectar;

require_once("class.TemplatePower.inc.php"); //Usando Template Power

date_default_timezone_set('America/Argentina/Buenos_Aires');

$tpl=new TemplatePower("preregistro.html");
$tpl->prepare();

$conexion= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());
$conexion2= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());

$accion=$_POST['accion'];

if ($accion == "") {
    $accion=$_GET['a'];
}

if ($accion == "aceptar") {
    $idSocio = $_SESSION['usuario'];

    if ($idSocio != "") {
        header('Location: perfil.php?a=p');
    } else {
        //Proceso datos recibidos
        $nombres=quitarCaracteres($_POST['nombres']);
        $apellidos=quitarCaracteres($_POST['apellidos']);
        $documento=$_POST['documento'];
        $direccionF=quitarCaracteres($_POST['direccion']);
        $ciudad=quitarCaracteres($_POST['ciudad']);
        $viveCon=quitarCaracteres($_POST['viveCon']);
        $hospeda=$_POST['hospeda'];

        if ($hospeda == 'undefined' || $hospeda == '') {
            $hospeda = 'NULL';
        }

        $fechaNac=$_POST['fechaNac'];
        $sexo=$_POST['sexo'];

        if ($sexo == 'undefined' || $sexo == '') {
            $sexo = 'NULL';
        }

        $facebook=$_POST['facebook'];
        $direccion=$_POST['mail'];
        $telefono=$_POST['telefono'];
        $ocupacion=quitarCaracteres($_POST['ocupacion']);
        $trabajo=quitarCaracteres($_POST['trabajo']);
        $pwd=$_POST['pwd'];

        $idNoExiste=false;

        while (!$idNoExiste) {
            $idTransaccion=rand(1, 20000);

            $conexion->Ejecuto("select idTransaccion from preregistro where idTransaccion=" . $idTransaccion);
            $buscar=$conexion->Siguiente();

            if ($buscar['idTransaccion'] == "") {
                $idNoExiste = true;
            }
        }

        $fechaN = split("/", $fechaNac);

        //insertar usuario inactivo hasta confirmar mail
        $conexion->Ejecuto("insert into preregistro (Nombres, Apellidos, Documento, Direccion, Ciudad, ViveCon, Hospeda, FechaNac, Sexo, Email, Password, Telefono, AreaEstudio, Trabajo, Facebook, FechaRegistro,idTransaccion) values ('" . $nombres . "','" . $apellidos . "'," . $documento . ",'" . $direccionF . "','" . $ciudad . "','" . $viveCon . "'," . $hospeda . ",'" . $fechaN[2] . $fechaN[1] . $fechaN[0] . "'," . $sexo . ",'" . $direccion . "','" . $pwd . "','" . $telefono . "','" . $ocupacion . "','" . $trabajo . "','" . $facebook . "','" . date("Y-m-d H:i:s") . "'," . $idTransaccion . ")");

        //Env�o correo de confirmaci�n
        $cuerpo = "Para continuar tu registro en SGI, hace click <a href='http://sgi.airaup.org/confirmacion.php?id=" . $idTransaccion . "'>aqu�</a>.<br><br>Por favor no respondas este mensaje.<br>Sistema de Gesti�n Integral<br>AIRAUP";
        try {
            enviarCorreo($direccion, $cuerpo);

            header('Location: preregistro.php?a=fc');
        } catch (Exception $e) {
            header('Location: preregistro.php?a=fe');
        }
    }
} elseif ($accion == "fc") {
    $tpl->newBlock("mensaje");
    $tpl->assign("mensaje", utf8_encode("Te enviamos un mail para confirmar tu direcci�n de correo y continuar el registro. Por favor revisa tu carpeta de spam en caso de no recibir el mensaje en tu bandeja de entrada."));
} elseif ($accion == "fe") {
    $tpl->newBlock("mensaje");
    $tpl->assign("mensaje", utf8_encode("Quedaste registrado pero ocurri� un error al enviar el correo de confirmaci�n. Por favor comunicate con tu Presidente."));
} else {
    $tpl->newBlock("preregistro");
}

$conexion->Libero(); //Se cierra la conexi�n a la base
$tpl->printToScreen(); //Se manda todo al HTML usando TPL

function enviarCorreo($direccion, $cuerpo)
{
    $mail							= new PHPMailer();
    $mail->IsSMTP();
    $mail->Host				= "mail.airaup.org";
    $mail->SMTPAuth		= true;
    $mail->SMTPSecure = "tls";
    $mail->Host				= "smtp.gmail.com";
    $mail->Port				= 587;
    $mail->Username		= "sgi@airaup.org";
    $mail->Password		= "Sistema2017";
    $mail->SetFrom('sgi@airaup.org', 'SGI - Registro');
    $mail->Subject		= utf8_encode('Registro en SGI');
    $mail->MsgHTML(utf8_encode($cuerpo));
    $mail->AddAddress($direccion);
    $mail->Send();
}

function quitarCaracteres($string)
{
    $string = trim($string);

    $string = str_replace(
            array("\\", "�", "�", "-", "~",
                         "#", "@", "|", "!", "\"",
                         "�", "$", "%", "&", "/",
                         "(", ")", "?", "'", "�",
                         "�", "[", "^", "`", "]",
                         "+", "}", "{", "�", "�",
                         ">", "< ", ";", ",", ":",
                         ".", "*", "_"),
                '',
                $string
        );


    return $string;
}
