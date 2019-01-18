<?php

ini_set('max_execution_time',0);
ini_set('memory_limit', '-1');

error_reporting(1);
ini_set("display_errors",1);

		$servername = "37.60.227.80";
		$username = "acecare7_acesys";
		$password = "Iw+&Sm]=otV7";
		$dbname = "acecare7_acetest";


		// Create connection
		$conn = new mysqli($servername, $username, $password, $dbname);
		// Check connection
		if ($conn->connect_error) {
		    die("Connection failed: " . $conn->connect_error);
		}

		       		
   		$sql = "UPDATE ace_rp_customers SET phone_valid = 1 AND cell_valid = 1 WHERE created >= '2018-03-01'";
   		$result = $conn->query($sql);



		exit;
		
		
?>



