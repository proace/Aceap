<?php
include_once("dbconnect.php");

$city = str_replace(" ", "", $_POST['city']);
$state = $_POST['state'];
$postal_code = $_POST['postal_code'];

$query = "
	SELECT COUNT(*) c
	FROM ace_rp_cyphon_seed 
	WHERE postal_code = '$postal_code'
	LIMIT 1
	";
$result = mysql_query($query);
if($row = mysql_fetch_array($result)) $count = $row['c'];
else $count = 0;
if($count > 0) {
	echo "$postal_code is already in the database.";
} else {
	$query = "
		INSERT INTO ace_rp_cyphon_seed(postal_code, city, state)
		VALUES('$postal_code', '$city', '$state')
		";
	mysql_query($query);
	echo "$postal_code has been saved.";
}
?>