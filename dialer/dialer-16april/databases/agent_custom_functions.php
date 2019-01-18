<?php
error_reporting(E_ALL); 
require_once 'config.php';


$action=$_REQUEST['action'];
if($action == 'update_customer')update_customer();


/***************************************/
function update_customer(){
	
	global $pdo;
	
	$disposition=$_REQUEST['disposition'];
	$customer_id= $_REQUEST["customer_id"];
	$agent_id= $_SESSION["id"];
	
	$sql="update customer_list set 	customer_disposition='".$disposition."', agent_id='".$agent_id."'  where customer_id='".$customer_id."'";
	 $pdo->exec($sql);
    //echo "New record created successfully";
	  header('location:../'); 
		//echo   $customer_id;
	
}




?>
