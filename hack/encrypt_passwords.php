<?php
include("/var/www/html/config.php");
require_once("/var/www/html/conexionDB.php");
session_start();

// Constants
$LENGTH_PASSWORD_HASHED = 34;
$REGEX_PASSWORD_HASHED = '/^\$\d+\$.*/';

echo "Start hashing passwords";

// Get DB instances to start to operate.
$connectionRead = getInstanceDB();
$connectionWrite = getInstanceDB();

$connectionRead -> Ejecuto("select idSocio, Password from socio;");
while ($socio = $connectionRead->Siguiente()) {
    if (!passwordIsHashed($socio["Password"])) {
        $connectionWrite -> Ejecuto(
            "update socio set Password='" . crypt($socio["Password"]) . "' where idSocio=" . $socio["idSocio"]
        );
    }
}

$connectionRead -> Ejecuto("select idPreRegistro, Password from preregistro;");
while ($preregistro = $connectionRead->Siguiente()) {
    if (!passwordIsHashed($preregistro["Password"])) {
        $connectionWrite -> Ejecuto(
            "update preregistro set Password='" . crypt($preregistro["Password"]) . "' where idPreRegistro=" . $preregistro["idPreRegistro"]
        );
    }
}

echo "All passwords were hashed.";

function passwordIsHashed($password)
{
    return strlen($password) == $LENGTH_PASSWORD_HASHED && preg_match($REGEX_PASSWORD_HASHED, $password);
}

function getInstanceDB()
{
    $obj_con = new conectar;
    return new ConexionDB(
        $obj_con -> getServ(),
        $obj_con -> getBase(),
        $obj_con -> getUsr(),
        $obj_con->  getPass()
    );
}
