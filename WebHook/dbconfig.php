<?php
$debugfunc = false;
$serverName = "__MYSQL_SERVER__";
$userName = "__MYSQL_USERNAME__";
$password = "__MYSQL_PASSWORD__";
$dbName = "__MYSQL_DB_NAME__";

$pharmacyUrl = "http://daten.buergernetz.bz.it/services/pharmacy/v1/json?";
$museumUrl = "http://daten.buergernetz.bz.it/services/musport/v1/geojson";
$newsUrl = "https://www.comune.merano.bz.it/it/rss";
$newsUrlDe = "https://www.comune.merano.bz.it/de/rss";

$con = new mysqli($serverName, $userName, $password, $dbName); 
$con->query("set names 'utf8'");

if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}
$con2 = new mysqli($serverName, $userName, $password, $dbName); 

if ($con2->connect_error) {
    die("Connection failed: " . $con->connect_error);
}
?>