<?php
/* vars for export */
// database record to be exported
$db_record = 'ace_rp_customers';
// optional where query

// filename for export
$csv_filename = 'db_export_'.$db_record.'_'.date('Y-m-d').'.csv';
// database variables
$hostname = "localhost";
$user = "aceno191_acesys";
$password = "acesys123user";
$database = "aceno191_acesys";
// Database connecten voor alle services
mysql_connect($hostname, $user, $password)
or die('Could not connect: ' . mysql_error());
					
mysql_select_db($database)
or die ('Could not select database ' . mysql_error());
// create empty variable to be filled with export data
$csv_export = '';
// query to get data from database
$query = mysql_query("SELECT phone, last_name, city, state, callback_date FROM ".$db_record." ");
$field = mysql_num_fields($query);
// create line with field names
for($i = 0; $i < $field; $i++) {
  $csv_export.= mysql_field_name($query,$i).';';
}
// newline (seems to work both on Linux & Windows servers)
$csv_export.= '
';
// loop through database query and fill export variable
while($row = mysql_fetch_array($query)) {
  // create line with field values
  for($i = 0; $i < $field; $i++) {
    $csv_export.= '"'.$row[mysql_field_name($query,$i)].'";';
  }	
  $csv_export.= '
';	
}
// Export the data and prompt a csv file for download
header("Content-type: text/x-csv");
header("Content-Disposition: attachment; filename=".$csv_filename."");
echo($csv_export);
?>