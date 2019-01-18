<?php 
include_once("dbconnect.php");

$city = $_GET['city'];

$query = "
	SELECT * 
	FROM ace_rp_cyphon_seed
	WHERE city = '$city'
	AND is_active = 1
	";

$result = mysql_query($query);

$cyphon = array();

while($row = mysql_fetch_array($result)) {
    $cyphon[$row['id']] = array();
    $cyphon[$row['id']]['id'] = $row['id'];
    $cyphon[$row['id']]['postal_code'] = $row['postal_code'];
	$cyphon[$row['id']]['city'] = $row['city'];
	$cyphon[$row['id']]['state'] = $row['state'];
}

echo json_encode($cyphon);
?>