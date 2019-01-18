<?php

		ini_set('max_execution_time',0);
		ini_set('memory_limit', '-1');

		error_reporting(1);
		ini_set("display_errors",1);

		$servername = "37.60.227.80";
		$username = "acecare7_acesys";
		$password = "Iw+&Sm]=otV7";
		$dbname = "acecare7_acetest";


		$conn = new mysqli($servername, $username, $password, $dbname);
		// Check connection
		if ($conn->connect_error) {
		    die("Connection failed: " . $conn->connect_error);
		}
		$arr_phone = array();
		$arr_cell = array();
		$filePath = 'Pro_ace_phone.csv';

		//How many rows to process in each batch
		$limit = 60;

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
		    	if($i>0){
			        $sql = "SELECT id,phone,cell_phone FROM ace_rp_customers WHERE phone = $currRow[0] OR cell_phone = $currRow[0]";
		       		$result = $conn->query($sql);
		       		
		       		$row = mysqli_fetch_assoc($result);
		       		$id = $row['id'];

		       		if($id != ''){
		       			/*print_r($id);
		       			print_r('<br>');
		       			print_r('--'.$currRow[0].'--');*/
		       			$phone = $row['phone'];
						$cell_phone = $row['cell_phone']; 

						if($phone == $currRow[0]){
							$ph = $currRow[0];
							$ce = 0;
						}
						else{
							$ph = 0;
							$ce = $currRow[0];
						}

						$whole_date_time = date("Y-m-d h:i:s");
						$date_time =  explode(' ', $whole_date_time);
						$curr_date = $date_time[0];
						$curr_time = $date_time[1];

						$sql = "INSERT INTO ace_rp_call_history(customer_id, call_date,call_time,call_user_id,call_result_id,call_note,callback_user_id,phone,cell_phone)
							VALUES($id, '$curr_date', '$curr_time', 44851,7,'Not in service row inserted',57145, $ph,$ce)";
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
		}
?>



