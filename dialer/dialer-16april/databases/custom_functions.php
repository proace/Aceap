<?php
error_reporting(E_ALL); 
require_once 'config.php';


$action=$_REQUEST['action'];
if($action == 'add_campaign')add_campaign();
if($action == 'get_city_list')get_city_list();
if($action == 'get_campaign_and_city_list')get_campaign_and_city_list();
if($action == 'import_leads')import_leads();
if($action == 'export_leads')export_leads();
if($action == 'get_disposition_count')get_disposition_count();
if($action == 'get_telemarketer_agent_list')get_telemarketer_agent_list();
if($action == 'assign_campaign_to_agent')assign_campaign_to_agent();

/***************************************/
function add_campaign(){
	
	global $pdo;
	
	$campaign_name=$_REQUEST['campaign_name'];
	$script_for_agent=$_REQUEST['script_for_agent'];
	$sql="insert into dialer_campaign(campaign_name,script_for_agent)values('".$campaign_name."','".$script_for_agent."')";
	 $pdo->exec($sql);
    //echo "New record created successfully";
	echo '1';
	
}
/***************************************/
function city_list(){
	global $pdo;
	$text='<option>Select City.....</option>';
	$queryResult = $pdo->query("SELECT * FROM ace_rp_cities where name !='' ");
	while($row = $queryResult->fetch()){
      $text.='<option value="'.strtoupper($row['name']).'" >'.strtoupper($row['name']).'</option>';
  }
  return $text;
} 
/*****************************************/
function get_city_list(){
    $text= city_list();
  echo $text;
}
/***************************************/
function campaign_list(){
	global $pdo;
	$text='<option>Select Campaign.....</option>';
	$queryResult = $pdo->query("SELECT * FROM dialer_campaign  ");
	while($row = $queryResult->fetch()){
      $text.='<option value="'.strtoupper($row['id']).'" >'.strtoupper($row['campaign_name']).'</option>';
  }
  return $text;
} 
/***************************************/
function get_campaign_and_city_list(){
	global $pdo;
	$city_list=city_list();
	$campaign_list=campaign_list();
	$data=array('city_list'=>$city_list,'campaign_list'=>$campaign_list);
	echo json_encode($data);
}
/***************************************/
function import_leads(){
	global $pdo;
	 $campaign_id = $_REQUEST['campaign_id'];
	$city = $_REQUEST['city'];
	
    if(isset($_FILES) && $_FILES['import']['name']!='' ){
		 $source=$_FILES['import']['tmp_name'];
		 $file = fopen($source,"r");
		 
		$i=0;
                
                  while(! feof($file))
                 {     if($i!=0){
							  $a=fgetcsv($file);
					     
					     $customer_homephone=$a[0];
					     $customer_first_name=$a[1];
					     $customer_last_name=$a[2];
					     $customer_address=$a[3];
					     $customer_city=$a[4];
					     $customer_zipcode=$a[5];
					
					if($customer_city ==   $city){  
					$sql="insert into  customer_list(customer_homephone,customer_first_name,customer_last_name,
								customer_address,customer_city,customer_zipcode,campaign_id)
					        values('".$customer_homephone."','".$customer_first_name."','".$customer_last_name."','".
								$customer_address."','".$customer_city."','".$customer_zipcode."','".$campaign_id."')";
	                    $pdo->exec($sql);
					     
					     }
					 }
					     $i++;
					     //if($i==6)break;
					     
				 }
                 header('location:../admin/leads/leads.php'); 
		 
	}
	//echo '<pre>';print_r($_REQUEST);
}
/***************************************/
/*function export_leads(){
	header('Content-Type: application/excel');
	header('Content-Disposition: attachment; filename="export.csv"');
	// do not cache the file
	header('Pragma: no-cache');
	header('Expires: 0');
 
// create a file pointer connected to the output stream
$file = fopen('php://output', 'w');
	global $pdo;
	$campaign_id = $_REQUEST['campaign_id'];
	$city = $_REQUEST['city'];
	$i=0;
	$data[]='Phone1;Firstname;Lastname;Address;City;Postalcode;Campaining Name';
	$queryResult = $pdo->query("SELECT a.*,b.campaign_name FROM customer_list as a left join dialer_campaign as b on a.campaign_id=b.id where a.campaign_id='".$campaign_id."' and a.customer_city='".$city."'");
	while($row = $queryResult->fetch()){
     $data[]="'".$row['customer_homephone'].";".$row['customer_first_name'].";".$row['customer_last_name'].";".$row['customer_address'].";".$row['customer_city'].";".$row['customer_zipcode'].";".$row['campaign_id']."'";
     if($i==5)break;$i++;
  }
	
	//echo '<pre>';print_r($data);echo '</pre>';
	

$fp = fopen('php://output', 'w');
foreach ( $data as $line ) {
    $val = explode(".",".", $line);
    fputcsv($fp, $val);
}
fclose($fp);
	
	
	
	
}
*/
/***************************************/
function get_disposition_count(){
	global $pdo;
	
	$data=array();
	$city=$_REQUEST['city'];
	
	
	$queryResult = $pdo->query("SELECT customer_disposition,count(customer_disposition) as total FROM customer_list  
	                where customer_city='".$city."' group by(customer_disposition) ");
	while($row = $queryResult->fetch()){
        $data[$row['customer_disposition']]=$row['total'];
    }
  echo json_encode($data);
} 

function get_telemarketer_agent_list(){
	global $pdo;
	
	$sql="select * from ace_rp_users u where exists (select * from ace_rp_users_roles r where r.user_id=u.id and r.role_id!=8) and u.is_active=1 and exists (select * from ace_rp_users_roles r where r.user_id=u.id and r.role_id=3) order by username asc";
	
	$queryResult = $pdo->query($sql);
	$text='';
	while($row = $queryResult->fetch()){
        $text.='<tr rowid="'.$row['id'].'">
				<td>'.$row['id'].'</td>
				<td>'.$row['username'].'</td>
				<td class="firstname">'.$row['first_name'].'</td>
				<td class="lastname">'.$row['last_name'].'</td>
				<td>'.$row['phone'].'</td>
				<td><a href="javascript:void(0)" onclick=assign_campaign("'.$row['id'].'") >Assign Campaign</a></td>
			</tr>';
    }
    //echo json_encode($data);
    echo $text;
}



function assign_campaign_to_agent(){
	
	
	
	
}
?>
