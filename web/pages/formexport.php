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


$params = $columns = $totalRecords = $data = array();
$params = $_REQUEST;
//define index of columns
$columns = array( 
  0 =>'phone',
  1 =>'last_name', 
  2 => 'city',
  3=> 'state' ,
  4=> 'callback_date' 
);
$where = $sqlTot = $sqlRec = "";
// getting total number records from table without any search
$sql = "SELECT phone, last_name, city, state, callback_date  FROM ace_rp_customers";
$sqlTot .= $sql;
$queryTot = mysqli_query($conn, $sqlTot) or die("database error:". mysqli_error($conn));
$totalRecords = mysqli_num_rows($queryTot);
// iterate on results row and create new index array of data
while( $row = mysqli_fetch_row($queryRecords) ) { 
  $data[] = $row;
} 
?>
<!DOCTYPE html>
<html>
<head>
<title></title>
</head>
<body>

 <form method="post" action="export.php">
                            <button type="submit" class="btn btn-primary" name="export1" value="export1">Export</button>


                             </form>
                             </body>
                            </html>