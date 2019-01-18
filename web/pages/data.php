<?php
include_once("db_connect.php");
// initilize all variables
$params = $columns = $totalRecords = $data = array();
$params = $_REQUEST;
//define index of columns
$columns = array( 
	0 =>'phone',
	1 =>'last_name', 
	2 => 'city'	
);
$where = $sqlTot = $sqlRec = "";
// getting total number records from table without any search
$sql = "SELECT phone, last_name, city, state, callback_date  FROM ace_rp_customers";
$sqlTot .= $sql;
$sqlRec .= $sql;
$sqlRec .=  " ORDER BY last_name";
$queryTot = mysqli_query($conn, $sqlTot) or die("database error:". mysqli_error($conn));
$totalRecords = mysqli_num_rows($queryTot);
$queryRecords = mysqli_query($conn, $sqlRec) or die("error to fetch employees data");
// iterate on results row and create new index array of data
while( $row = mysqli_fetch_row($queryRecords) ) { 
	$data[] = $row;
}	
$json_data = array(
		"draw"            => 1,   
		"recordsTotal"    => intval( $totalRecords ),  
		"recordsFiltered" => intval($totalRecords),
		"data"            => $data
		);
// send data as json format
echo json_encode($json_data);  
?>