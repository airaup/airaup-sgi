<?php
# Fill our vars and run on cli
# $ php -f db-connect-test.php
$dbname = 'c0310458_sgi';
$dbuser = 'c0310458_sgi';
$dbpass = 'Rotaract2016';
$dbhost = 'mysql';
$link = mysqli_connect($dbhost, $dbuser, $dbpass) or die("Unable to Connect to '$dbhost'");
mysqli_select_db($link, $dbname) or die("Could not open the db '$dbname'");

$sql = "SELECT idSocio, Activo from `socio` where Email='mrtnbcrr@gmail.com'";
$sql = "SHOW TABLES FROM c0310458_sgi";
echo $sql;
echo "";
$result = mysqli_query($link, $sql) or die(mysql_error());
$tbl = mysqli_fetch_array($result);
echo "There are $tbl tables<br />\n";
// $row = $result->fetch_object() or die(mysql_error());
// echo mysqli_num_rows($result) or die(mysql_error());
// echo $row;

if (!$result) {
    echo 'Error: ', $mysqli->error;
}
