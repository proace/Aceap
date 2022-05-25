<?php

class TechScheduleController extends AppController

{

	//To avoid possible PHP4 problemfss

	var $name = "TechScheduleController";

  var $uses = array('User');

	var $helpers = array('Common');

	var $components = array('HtmlAssist', 'RequestHandler', 'Common', 'Lists');

	var $itemsToShow = 20;

	var $pagesToDisplay = 10;



  function index()

  {

		$this->layout='list';

    $db =& ConnectionManager::getDataSource('default');



    $AllRoutes = array();

    

		$query = "select * from ace_rp_inventory_locations order by id asc";

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result)) {

			$AllRoutes[$row['id']] = array(

        'id' => $row['id'],

        'name' => $row['name'],

        'truck_number' => $row['truck_number'],

        'tech1' => array(

            $row['tech1_day1'],

            $row['tech1_day2'],

            $row['tech1_day3'],

            $row['tech1_day4'],

            $row['tech1_day5'],

            $row['tech1_day6']

        ),

        'tech2' => array(

            $row['tech2_day1'],

            $row['tech2_day2'],

            $row['tech2_day3'],

            $row['tech2_day4'],

            $row['tech2_day5'],

            $row['tech2_day6']

        )

      );

		}

    

		$this->set('AllRoutes',$AllRoutes);

		$this->set('AllTechnicians',$this->Lists->Technicians());

  }


  function night_shift()

  {
		

				$this->layout='list';

    $db =& ConnectionManager::getDataSource('default');



    $AllRoutes = array();

    

		$query = "select * from ace_rp_inventory_locations_night order by id asc";

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result)) {

			$AllRoutes[$row['id']] = array(

        'id' => $row['id'],
				
				

        'name' => $row['name'],

        'truck_number' => $row['truck_number'],

        'tech1' => array(

            $row['tech1_day1'],

            $row['tech1_day2'],

            $row['tech1_day3'],

            $row['tech1_day4'],

            $row['tech1_day5'],

            $row['tech1_day6'],
			$row['tech1_day7']

        ),
				
				'cell_phone' => array(

            $row['cell_phone_1'],

            $row['cell_phone_2'],

            $row['cell_phone_3'],

            $row['cell_phone_4'],

            $row['cell_phone_5'],

            $row['cell_phone_6'],
			$row['cell_phone_7']

        ),
				'time_from' => array(

            $row['time_from_1'],

            $row['time_from_2'],

            $row['time_from_3'],

            $row['time_from_4'],

            $row['time_from_5'],

            $row['time_from_6'],

			$row['time_from_7']

        ),
				'time_to' => array(

            $row['time_to_1'],

            $row['time_to_2'],

            $row['time_to_3'],

            $row['time_to_4'],

            $row['time_to_5'],

            $row['time_to_6'],

			$row['time_to_7']

        ),

        'email' => array(

            $row['email_1'],

            $row['email_2'],

            $row['email_3'],

            $row['email_4'],

            $row['email_5'],

            $row['email_6'],

			$row['email_7']

        )

      );

		}

    

		$this->set('AllRoutes',$AllRoutes);

		$this->set('AllTechnicians',$this->Lists->Technicians());


  }
	
	

	

	function setTech()

	{

		$tech_id = $_GET['tech_id'];

		$day = $_GET['day'];

		$route_id = $_GET['route_id'];

		$tech_num = $_GET['tech_num'];

		

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$db->_execute("update ace_rp_inventory_locations set tech{$tech_num}_day{$day}={$tech_id} WHERE id=$route_id");

 		exit;

	}
	
	function get_value()

	{

		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		date_default_timezone_set('America/Vancouver');
		
		$day = date('N');
		
		$time = date('H');
		
		if($time=="00"){
			$time=24;
		}
		if($time=="01"){
			$time=25;
		}
		if($time=="02"){
			$time=26;
		}
		if($time=="03"){
			$time=27;
		}
		if($time=="04"){
			$time=28;
		}
		if($time=="05"){
			$time=29;
		}
		if($time=="06"){
			$time=30;
		}
		if($time=="07"){
			$time=31;
		}
		if($time=="08"){
			$time=32;
		}
		if($time=="09"){
			$time=33;
		}
		if($time=="10"){
			$time=34;
		}
		if($time=="11"){
			$time=35;
		}
		if($time=="12"){
			$time=36;
		}
		if($time=="13"){
			$time=37;
		}
		if($time=="14"){
			$time=38;
		}
		if($time=="15"){
			$time=39;
		}
		if($time=="16"){
			$time=40;
		}
		if($time=="17"){
			$time=41;
		}
		if($time=="18"){
			$time=42;
		}
		if($time=="19"){
			$time=43;
		}
    
		
		
	$query = "select cell_phone_{$day},email_{$day} from ace_rp_inventory_locations_night WHERE time_from_{$day} <= '$time' AND time_to_{$day} >= '$time'";
		
		$result = $db->_execute($query);
		
		$result_id  = array();
		
		$cell_phone  = array();
		
		$email  = array();
		
		$cell_phone1="cell_phone_".$day;
		
		$email1="email_".$day;
		
	$reservations = array();  // makes sure the array exists in case result set is empty

		while($row = mysql_fetch_array($result)) {
		
				$reservations[] =   array (
                                      cell_phone => $row[$cell_phone1],
                                      email => $row[$email1],
                                    );
		
		
		}
		
		$cell = array_combine($cell_phone, $email);
		
		print_r($reservations);
		
		//print_r($email);
		
		
		
		exit;

	}

  function get_value2(){
   $url="http://hvacproz.ca/acesys/index.php/tech_schedule/get_value";
   $data=file_get_contents($url);
   $cell_phone=array();
   $email=array();

	//print_r($email);
	foreach($data as $datas){
   print_r($datas[cell_phone]);
	}
    exit;
  }
	
	function setTech2()

	{

		$tech_id = $_GET['tech_id'];

		$day = $_GET['day'];

		$route_id = $_GET['route_id'];

		$tech_num = $_GET['tech_num'];
		
		$cell_phone = $_GET['cell_phone'];
		
		$email = $_GET['email'];
		
		$time_from = $_GET['time_from'];
		
		$time_to = $_GET['time_to'];

		

		$tech_id = $_GET['tech_id'];

		$day = $_GET['day'];

		$route_id = $_GET['route_id'];

		$tech_num = $_GET['tech_num'];

		

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$db->_execute("update ace_rp_inventory_locations_night set tech{$tech_num}_day{$day}={$tech_id} WHERE id=$route_id");

 		exit;

	}
	
	function setTech3()

	{
		if($_REQUEST['cell_phone']==""){
	$cell_phone = "";
		
		}
		else {
		$cell_phone = $_REQUEST['cell_phone'];
			
		}
		if($_REQUEST['email']==""){
			$email ="";
				
		
		}
		else {
		$email = $_REQUEST['email'];

		}
		
		
	
			
		
		$day = $_REQUEST['day'];
		
		$id = $_REQUEST['id'];
		
		if($_REQUEST['time_from']==""){
				
		$time_from ="NULL";
		}
		else {
			
		$time_from = $_REQUEST['time_from'];

		}
		
		if($_REQUEST['time_to']==""){
				
			$time_to ="NULL";
		
		}
		else {
$time_to = $_REQUEST['time_to'];
		
		}
	
		
		
		

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		echo $db->_execute("update ace_rp_inventory_locations_night set email_{$day}='{$email}',cell_phone_{$day}='$cell_phone',time_from_{$day}=$time_from,time_to_{$day}=$time_to WHERE id=$id");

 		exit;

	}

}

?>