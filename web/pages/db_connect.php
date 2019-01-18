<?php

/* Database connection start */
$servername = "localhost";
$username = "aceno191_acesys";
$password = "acesys123user";
$dbname = "aceno191_acesys";
$conn = mysqli_connect($servername, $username, $password, $dbname) or die("Connection failed: " . mysqli_connect_error());
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}
echo '<script type="text/javascript">alert("hello!");</script>';
?>

