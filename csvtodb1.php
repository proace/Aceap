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
		$arr_phone = array();
		$arr_cell = array();
		$filePath = 'customer_phone.csv';

		//How many rows to process in each batch
		$limit = 100;

		$fileHandle = fopen($filePath, "r");
		if ($fileHandle === FALSE)
		{
		    die('Error opening '.$filePath);
		}
		

		//Set up a variable to hold our current position in the file
		$offset = 0;
		while(!feof($fileHandle))
		{
		    //Go to where we were when we ended the last batch
		    fseek($fileHandle, $offset);

		    $i = 0;
		    while (($currRow = fgetcsv($fileHandle)) !== FALSE)
		    {
		        $i++;

		        //Do something with the current row
		       	if($i != 1){
		       		$sql = "UPDATE ace_rp_customers SET cell_valid = 1 WHERE cell_phone = $currRow[1] AND created < '2018-03-01'";
		       		$result = $conn->query($sql);
		       	}


		        //If we hit our limit or are at the end of the file
		        if($i >= $limit)
		        {
		            //Update our current position in the file
		            $offset = ftell($fileHandle);
		            //Break out of the row processing loop
		            break;
		        }
		    }
		}	
?>



