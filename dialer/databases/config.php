<?php
$host = "localhost";
$name = "acecare7_acetest";
$user = "acecare7_acesys";
$password = "Iw+&Sm]=otV7";
$pdo = "";

try
{
    $pdo = new PDO("mysql:host=$host;dbname=$name", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(Exception $e)
{
  //echo $e->getMessage();
}
  
?>
