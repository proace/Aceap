<?php

require_once 'config.php';

$queryResult = $pdo->query("SELECT * FROM `customer_list` WHERE customer_disposition <=> NULL LIMIT 1");
if($row = $queryResult->fetch())
{
    $data["data"][] = $row;
    $_COOKIE["cust_id"] = $row["customer_id"];
    $_COOKIE["cust_name"] = $row["customer_first_name"] . " " . $row["customer_last_name"];
    $_COOKIE["cust_city"] = $row["customer_city"];
    $_COOKIE["cust_cell"] = $row["customer_cellphone"];
    $_COOKIE["cust_home"] = $row["customer_homephone"];
    $_COOKIE["cust_zip"] = $row["customer_zipcode"];
    $_COOKIE["cust_email"] = $row["customer_email"];  
    $_COOKIE["cust_last"] =$row["customer_lastcall"] ;    
}

?>