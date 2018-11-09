<?php
ini_set("display_errors", 0);
session_start(); //Se inicia la sesión
$_SESSION['usuario'] = "";
session_destroy();
header('Location: login.php');
