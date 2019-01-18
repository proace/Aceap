<?php
class CallsController extends AppController

{

	var $name = 'Calls';
	var $uses = array('Order', 'CallRecord', 'User', 'Customer', 'OrderItem',
                    'Timeslot', 'OrderStatus', 'OrderType', 'Item',
                    'Zone','PaymentMethod','ItemCategory','InventoryLocation',
					'OrderSubstatus','Coupon','Setting','CallResult','Invoice', 'Question', 'Payment', 'Invoice');
	var $helpers = array('Html','Common');
	var $components = array('HtmlAssist','Common','Lists');
	var $test_user_id = 231294;



	function index() {
		$this->layout = 'responsive2';
		$this->_setsidebar($_SESSION['user']['id']);
	}

	function _saveusermetric($user_id, $action, $number = "")
	{
		$db =& ConnectionManager::getDataSource('default');
		$query = "
			INSERT INTO n_user_metrics
			SET user_id = $user_id,
				metric_action = '$action',
				metric_number = '$number'
		";
		$db->_execute($query);
	}

	function phonelist() {
		//TESTING MODE
		//$test_phone = "6048415774";
		//$test_phone = "6043650065";
		//$user_id = $this->test_user_id;
		//end TEST MODE
		$user_id = $_SESSION['user']['id'];
		$db =& ConnectionManager::getDataSource('default');
		if(isset($_POST['disposition'])) {
			$this->_saveusermetric($user_id, "DISP CALL", $_POST['number']);

			$notes = $_POST['notes'];
			$lead_id = $_POST['lead'];
			$number = $_POST['number'];
			$return = $_POST['return'];
			$status = $_POST['disposition'];

			if(isset($return)) {
				$query = "
					INSERT INTO n_calls
					SET call_number = '$number',
						call_date = NOW(),
						call_status = $status,
						call_note = '$notes',
						call_return = '$return',
						user_id = $user_id
				";
			} else {
				$query = "
					INSERT INTO n_calls
					SET call_number = '$number',
						call_date = NOW(),
						call_status = $status,
						call_note = '$notes',
						user_id = $user_id
				";
			}
			$results = $db->_execute($query);
			$query = "
				DELETE FROM n_hopper
				WHERE lead_id = $lead_id
			";
			$results = $db->_execute($query);
			$query = "
				UPDATE n_leads
				SET lead_status = 3
				WHERE lead_id = $lead_id
			";
			$results = $db->_execute($query);

		}

		if(isset($_GET['l'])) {
			$list_id = $_GET['l'];
			$hopper = $this->_gethopper($user_id, $list_id);
			if(empty($hopper)) $this->_redirect("calls/nomoreleads");

			$query = "
				SELECT *
				FROM n_lists
				WHERE list_id = $list_id
			";
			$results = $db->_execute($query);

			while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
				foreach ($row as $field => $val) {
					$temp[$field] = $val;
				}
				$lists[] = $temp;
			}

			$this->set('spiel', $lists[0]['list_spiel']);
			$phone = $hopper[0]['lead_phone'];
			$customers = $this->_getcustomers($phone);
			$history = $this->_gethistory(trim($phone));
			$this->set('hopper', $hopper[0]);
			$this->set('customers', $customers);
			$this->set('test_phone', $test_phone);
			$this->set('history', $history);
			$this->_saveusermetric($user_id, "START CALL", $phone);
		} else {
			$this->_redirect('calls/index');
		}

		$this->layout = 'responsive2';
	}

	function _redirect($url)
	{
		echo "<script>window.location = '../".$url."'</script>"; exit;
	}

	function userstats() {
		$user_id = $_SESSION['user']['id'];
		$db =& ConnectionManager::getDataSource('default');

		$query = "
			SELECT *
			FROM n_user_metrics
			WHERE user_id = $user_id
			ORDER BY metric_datetime
		";
		$results = $db->_execute($query);

		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
		}

		$this->set('metrics', $metrics);
		$this->layout = 'responsive2';
	}

	function hotlist() { 
		//TESTING MODE
		//$test_phone = "6048415774";
		//$test_phone = "6043650065";
		//$user_id = $this->test_user_id;
		//end TEST MODE
		$test_phone='';
		 $user_id = $_SESSION['user']['id'];
		 
		$db =& ConnectionManager::getDataSource('default');
		if(isset($_POST['disposition'])) {
			$this->_saveusermetric($user_id, "DISP CALL", $_POST['number']);

			$notes = $_POST['notes'];
			$lead_id = $_POST['lead'];
			$number = $_POST['number'];
			$return = isset($_POST['return'])?$_POST['return']:"";
			$status = $_POST['disposition'];

			if($return == "") {
				switch($status."") {
			    	case "1":
			    	$call_return = "DATE_ADD(NOW(), INTERVAL 6 MONTH)";
			    	break;
			    	case "2":
			    	$call_return = "DATE_ADD(NOW(), INTERVAL 10 DAY)";
			    	break;
			    	case "3":
			    	$call_return = "DATE_ADD(NOW(), INTERVAL 10 DAY)";
			    	break;
			    	case "4":
			    	$call_return = "DATE_ADD(NOW(), INTERVAL 11 MONTH)";
			    	break;
			    	case "5":
			    	$call_return = "DATE_ADD(NOW(), INTERVAL 10 DAY)";
			    	break;
			    	case "6":
			    	$call_return = "'0000-00-00 00:00:00'";
			    	break;
			    	case "7":
			    	$call_return = "'0000-00-00 00:00:00'";
			    	break;
			    	case "8":
			    	$call_return = "'0000-00-00 00:00:00'";
			    	break;
			    	case "9":
			    	$call_return = "'0000-00-00 00:00:00'";
			    	break;
			    	case "10":
			    	$call_return = "DATE_ADD(NOW(), INTERVAL 10 DAY)";
			    	break;
			    	case "11":
			    	$call_return = "DATE_ADD(NOW(), INTERVAL 11 MONTH)";
			    	break;
			    	default:
			    	$call_return = "DATE_ADD(NOW(), INTERVAL 10 DAY)";
			    }
			    /*
			    UPDATE n_calls SET call_return = DATE_ADD(call_date, INTERVAL 6 MONTH) WHERE call_status = 1 AND call_return = '0000-00-00 00:00:00';
				UPDATE n_calls SET call_return = DATE_ADD(call_date, INTERVAL 10 DAY) WHERE call_status = 2 AND call_return = '0000-00-00 00:00:00';
				UPDATE n_calls SET call_return = DATE_ADD(call_date, INTERVAL 10 DAY) WHERE call_status = 3 AND call_return = '0000-00-00 00:00:00';
				UPDATE n_calls SET call_return = DATE_ADD(call_date, INTERVAL 11 MONTH) WHERE call_status = 4 AND call_return = '0000-00-00 00:00:00';
				UPDATE n_calls SET call_return = DATE_ADD(call_date, INTERVAL 10 DAY) WHERE call_status = 5 AND call_return = '0000-00-00 00:00:00';
				UPDATE n_calls SET call_return = DATE_ADD(call_date, INTERVAL 10 DAY) WHERE call_status = 10 AND call_return = '0000-00-00 00:00:00';
				UPDATE n_calls SET call_return = DATE_ADD(call_date, INTERVAL 11 MONTH) WHERE call_status = 11 AND call_return = '0000-00-00 00:00:00';

			    */
			} else {
				$call_return = "'".$return."'";
			}
			$query = "
				INSERT INTO n_calls
				SET call_number = '$number',
					call_date = NOW(),
					call_status = $status,
					call_note = '$notes',
					call_return = $call_return,
					user_id = $user_id
			";

			$results = $db->_execute($query);
			$query = "
				DELETE FROM n_hotlist
				WHERE lead_id = $lead_id
			";
			$results = $db->_execute($query);
			$query = "
				UPDATE n_leads SET
				lead_call_count = lead_call_count + 1,
				lead_last_called = NOW()
				WHERE lead_id = $lead_id
			";
			$results = $db->_execute($query);

		}

		if(isset($_GET['l'])) {
			$list_id = $_GET['l'];
			$type_id = $_GET['t'];
			$hotlist = $this->_gethotlist($user_id, $list_id, $type_id);
			//echo print_r($hotlist); exit;
			if(empty($hotlist)) $this->_redirect("calls/nomoreleads");

			 $query = "
				SELECT *
				FROM n_lists
				WHERE list_id = $list_id
			";
			$results = $db->_execute($query);

			while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
                                     
				foreach ($row as $field => $val) {
					$temp[$field] = $val;
				}
				$lists[] = $temp;
			}
                      
			$this->set('spiel', $lists[0]['list_spiel']);
			$phone = $hotlist[0]['lead_phone'];
			$customers = $this->_getcustomers($phone);
			$history = $this->_gethistory(trim($phone));
			$this->set('hotlist', $hotlist[0]);
			$this->set('customers', $customers);
			$this->set('test_phone', $test_phone);
			$this->set('history', $history);
			$this->_saveusermetric($user_id, "START CALL", $phone);
            /**Work History**/
            $workHistory=array();
            
            foreach($customers as $customer){
				$workHistory[]=$this->showCustomerJobs($customer['id'],$customer['last_order_id'],$customer['phone']);
				
			}
			
            $this->set('workHistory', $workHistory);
              
		} else {
			$this->_redirect('calls/index');
		}

		 $query = "
			SELECT COUNT(e.lead_id) call_total, cs.call_status_name, c.call_status
			FROM n_latest_call lc
			LEFT JOIN n_calls c
			ON lc.call_id = c.call_id
			LEFT JOIN n_leads e
			ON c.call_number = e.lead_phone
			LEFT JOIN n_call_status cs
			ON c.call_status = cs.call_status_id
			WHERE e.lead_id IS NOT NULL
			AND DATE(c.call_return) = CURDATE()
			GROUP BY c.call_status
			ORDER BY c.call_return ASC
		";
		$results = $db->_execute($query);
        $rows=array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {

			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[$temp['call_status']] = $temp;
		}

		$this->set('call_total', $rows);


		$this->layout = 'responsive2';
	}
/*************************************/
	/*function add_city(){ 
		$list_id=$_REQUEST['list_id'];
		$listname=$_REQUEST['listname'];
		if($list_id !='' && $listname !=''){ 
			$db =& ConnectionManager::getDataSource('default');
			$query = "insert into n_lists(list_name)values('".$listname."')" ;
			$results = $db->_execute($query);
			}
		echo 1;
		exit;
	}*/
/*************************************/
	function edit_city(){ 
		$list_id=$_REQUEST['list_id'];
		$listname=$_REQUEST['listname'];
		$user_id = $_SESSION['user']['id'];
		if($list_id !='' && $listname !=''){ 
			$db =& ConnectionManager::getDataSource('default');
			 $query = "update n_lists set list_name = '".$listname."' where list_id=".$list_id;
			$results = $db->_execute($query);
			unset($_SESSION['sidebar-menu']);
                  $this->_setsidebar($user_id);
			}
		echo 1;
		exit;
	}
/*************************************/
	function delete_city(){ 
		$list_id=$_REQUEST['list_id'];
		$user_id = $_SESSION['user']['id'];
		if($list_id !=''){ 
			$db =& ConnectionManager::getDataSource('default');
			$query = "delete from n_lists where list_id=".$list_id;
			$results = $db->_execute($query);
			
			$query = "delete from n_leads where list_id=".$list_id;
			$results = $db->_execute($query);
			
			unset($_SESSION['sidebar-menu']);
                  $this->_setsidebar($user_id);
			}
		echo 1;
		exit;
	}
	/*************************************/
	function delete_import_lead(){ 
		$id=$_REQUEST['id'];
		if($id !=''){ 
			$db =& ConnectionManager::getDataSource('default');
			$query = "delete from n_leads where lead_id=".$id;
			$results = $db->_execute($query);
			}
		echo 1;
		exit;
	}
	
	
	
	/*************************************/
	function checkRowExist($phone){
		$db =& ConnectionManager::getDataSource('default');
		$query = "select * from n_leads where lead_phone = '".$phone."' ";
		$results = $db->_execute($query);
		$rows =mysql_num_rows($results);
		return $rows;
	}
	/*function checkRowExist($phone,$firstname,$list_id){
		$db =& ConnectionManager::getDataSource('default');
		$query = "select * from n_leads where lead_phone = '".$phone."'  and lead_first_name='".$firstname."' and list_id='".$list_id."'";
		$results = $db->_execute($query);
		$rows =mysql_num_rows($results);
		return $rows;
	}*/
	
	
	function newcities(){ 
            $this->set('message', '');
            $user_id = $_SESSION['user']['id'];
           // $list_id=$_REQUEST['l']?$_REQUEST['l']:'';
	        $db =& ConnectionManager::getDataSource('default');
             
            if(isset($_POST) && isset($_REQUEST['submit'])){
				
			 $list_name=$_REQUEST['cityname'];	
	         $query = "insert into n_lists(list_name,new_city)values('".$list_name."',1)" ;
             $results = $db->_execute($query);
		
               $this->set('message', 'City Added successfully');
               //to add new city in session 
               unset($_SESSION['sidebar-menu']);
                  $this->_setsidebar($user_id);
            }
            
             $rows=array();
           
             $query = "SELECT * FROM n_lists  where  new_city=1 ORDER BY list_name DESC";
             
		$results = $db->_execute($query);
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
		}
             
		$this->set('rows', $rows);  
        $this->layout = 'responsive2';
		
	}
	
/*******************************************/
function get_fields_for_import($arr){
	
	$fields=array();
	
	 foreach($arr as $k=>$a){
		 $a=strtolower($a);
		 if (strpos($a, 'first') !== false) {
				$fields[0]=$k;
			}
		  else if (strpos($a, 'last') !== false) {
				$fields[1]=$k;
			}
		  else if (strpos($a, 'phone') !== false  && strpos($a, '1') !== false) {
				$fields[2]=$k;
			}
		  else if (strpos($a, 'phone') !== false && strpos($a, '2') !== false) {
				$fields[3]=$k;
			}
		  else if (strpos($a, 'address') !== false ) {
				$fields[4]=$k;
			}
		  else if (strpos($a, 'city') !== false ) {
				$fields[5]=$k;
			}
		 else if (strpos($a, 'postal') !== false ) {
				$fields[6]=$k;
			}
		 else if (strpos($a, 'email') !== false ) {
				$fields[7]=$k;
			}
		 else if (strpos($a, 'date') !== false ) {
				$fields[8]=$k;
			}
	 }
	 $arr=array($fields[0],$fields[1],$fields[2],$fields[3],$fields[4],$fields[5],$fields[6],$fields[7],$fields[8]);
	
	 return $arr;
	 
 }

	
/******************************************/
	function importleads(){  
            $this->set('message', '');
            $user_id = $_SESSION['user']['id'];
            $list_id=$_REQUEST['l'];
	        $db =& ConnectionManager::getDataSource('default');
             
            if(isset($_FILES) && $_FILES['import']['name']!='' && isset($_REQUEST['submit'])){
				
		     //$name=$_FILES['import']['name'];
	         $source=$_FILES['import']['tmp_name'];
	         //$destination="import/".$name;
	        // $a=move_uploaded_file($source,$destination);
                 //$url = '/public_html/development/import/'.$name;
                // $url = 'import/'.$name;
	         //$file = fopen($url,"r");
	         $file = fopen($source,"r");
               
              
                $i=0;$duplicates=array();$inserted_rows=0;
                
                  while(! feof($file))
                 {     if($i==0){
					      $a=fgetcsv($file);
					      $fields= $this->get_fields_for_import($a);
					      //echo '<pre>';print_r($fields);die;
				 }
                   else{
	               $b=fgetcsv($file); 
	              	               
                      if($b[1] !='' && $b[2] !='' && $b[3] !='' ){
						  $a0=$fields[0];$a1=$fields[1];$a2=$fields[2];$a3=$fields[3];$a4=$fields[4];$a5=$fields[5];$a6=$fields[6];$a7=$fields[7];$a8=$fields[8];
						  
						  // $return=$this->checkRowExist($b[2],$b[0],$list_id);
						     //$return=$this->checkRowExist($b[$a2],$b[$a0],$list_id);
						     $return=$this->checkRowExist($b[$a2]);
						   
						  if($return == 0){
					 
                         $query = "
				INSERT INTO n_leads
				SET lead_first_name = '$b[$a0]',
					lead_last_name = '$b[$a1]',
					lead_phone = '$b[$a2]',
					lead_address = '$b[$a4]',
					lead_city = '$b[$a5]',
					lead_postal_code = '$b[$a6]',
					lead_email = '$b[$a7]',                              
				                                
                    list_id=$list_id,
                    new_city=1
                   
				"; 
                         /*$query = "
				INSERT INTO n_import
				SET firstname = '$b[$a0]',
					lastname = '$b[$a1]',
					phone1 = '$b[$a2]',
					phone2 = '$b[$a3]',
					address = '$b[$a4]',
					city = '$b[$a5]',
					postal_code = '$b[$a6]',
					email = '$b[$a7]',                              
					date = '$b[$a8]',                              
					user_id = $user_id,                                
                    list_id=$list_id
                   
				"; */
                         /* $query = "
				INSERT INTO n_import
				SET firstname = '$b[0]',
					lastname = '$b[1]',
					phone1 = '$b[2]',
					phone2 = '$b[3]',
					address = '$b[4]',
					city = '$b[5]',
					postal_code = '$b[6]',
					email = '$b[7]',                                
					user_id = $user_id,                                
                    list_id=$list_id
                   
				";*/

			$db->_execute($query);
			   
			$inserted_rows++;
			
			
			
                  }else $duplicates[]=$b;
			  }
                }$i++;
                  }
                    fclose($file);
               //unlink($url);
              
               if($inserted_rows > 0) $this->set('message', 'Csv imported successfully');
                    else $this->set('message', '');
                    
                    if(!empty($duplicates)) $this->set('duplicates', $duplicates);
                    else $this->set('duplicates', '');
              
            }
            
             $rows=array();
             //$query = "SELECT * FROM n_import  where user_id=".$user_id."  and list_id=".$list_id." ORDER BY id DESC";
             $query = "SELECT * FROM n_leads  where  list_id=".$list_id." ORDER BY lead_id DESC";
             
		$results = $db->_execute($query);
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
		}
             
		$this->set('rows', $rows);  

                /*******Get city name ******/

        $query = "SELECT * FROM n_lists  where list_id=".$list_id;
		$results = $db->_execute($query);
		$row = mysql_fetch_array($results, MYSQL_ASSOC);
		$cityname=$row['list_name'];		
		             
		$this->set('cityname', $cityname);  

        $this->layout = 'responsive2';
		
	}
    
	


/***********************************************************/
	function hotlisteditor() {
		//TESTING MODE
		//$test_phone = "6048415774";
		//$test_phone = "6043650065";
		//$user_id = $this->test_user_id;
		//end TEST MODE
		$user_id = $_SESSION['user']['id'];
		$db =& ConnectionManager::getDataSource('default');
		if(isset($_POST['disposition'])) {
			$this->_saveusermetric($user_id, "DISP CALL", $_POST['number']);

			$notes = $_POST['notes'];
			$lead_id = $_POST['lead'];
			$number = $_POST['number'];
			$return = isset($_POST['return'])?$_POST['return']:"";
			$status = $_POST['disposition'];

			if($return == "") {
				switch($status."") {
			    	case "1":
			    	$call_return = "DATE_ADD(NOW(), INTERVAL 6 MONTH)";
			    	break;
			    	case "2":
			    	$call_return = "DATE_ADD(NOW(), INTERVAL 10 DAY)";
			    	break;
			    	case "3":
			    	$call_return = "DATE_ADD(NOW(), INTERVAL 10 DAY)";
			    	break;
			    	case "4":
			    	$call_return = "DATE_ADD(NOW(), INTERVAL 11 MONTH)";
			    	break;
			    	case "5":
			    	$call_return = "DATE_ADD(NOW(), INTERVAL 10 DAY)";
			    	break;
			    	case "6":
			    	$call_return = "'0000-00-00 00:00:00'";
			    	break;
			    	case "7":
			    	$call_return = "'0000-00-00 00:00:00'";
			    	break;
			    	case "8":
			    	$call_return = "'0000-00-00 00:00:00'";
			    	break;
			    	case "9":
			    	$call_return = "'0000-00-00 00:00:00'";
			    	break;
			    	case "10":
			    	$call_return = "DATE_ADD(NOW(), INTERVAL 10 DAY)";
			    	break;
			    	case "11":
			    	$call_return = "DATE_ADD(NOW(), INTERVAL 11 MONTH)";
			    	break;
			    	default:
			    	$call_return = "DATE_ADD(NOW(), INTERVAL 10 DAY)";
			    }
			    /*
			    UPDATE n_calls SET call_return = DATE_ADD(call_date, INTERVAL 6 MONTH) WHERE call_status = 1 AND call_return = '0000-00-00 00:00:00';
				UPDATE n_calls SET call_return = DATE_ADD(call_date, INTERVAL 10 DAY) WHERE call_status = 2 AND call_return = '0000-00-00 00:00:00';
				UPDATE n_calls SET call_return = DATE_ADD(call_date, INTERVAL 10 DAY) WHERE call_status = 3 AND call_return = '0000-00-00 00:00:00';
				UPDATE n_calls SET call_return = DATE_ADD(call_date, INTERVAL 11 MONTH) WHERE call_status = 4 AND call_return = '0000-00-00 00:00:00';
				UPDATE n_calls SET call_return = DATE_ADD(call_date, INTERVAL 10 DAY) WHERE call_status = 5 AND call_return = '0000-00-00 00:00:00';
				UPDATE n_calls SET call_return = DATE_ADD(call_date, INTERVAL 10 DAY) WHERE call_status = 10 AND call_return = '0000-00-00 00:00:00';
				UPDATE n_calls SET call_return = DATE_ADD(call_date, INTERVAL 11 MONTH) WHERE call_status = 11 AND call_return = '0000-00-00 00:00:00';

			    */
			} else {
				$call_return = "'".$return."'";
			}
			$query = "
				INSERT INTO n_calls
				SET call_number = '$number',
					call_date = NOW(),
					call_status = $status,
					call_note = '$notes',
					call_return = $call_return,
					user_id = $user_id
			";

			$results = $db->_execute($query);
			$query = "
				DELETE FROM n_hotlist
				WHERE lead_id = $lead_id
			";
			$results = $db->_execute($query);
			$query = "
				UPDATE n_leads SET
				lead_call_count = lead_call_count + 1,
				lead_last_called = NOW()
				WHERE lead_id = $lead_id
			";
			$results = $db->_execute($query);

		}

		$phone = isset($_REQUEST['p'])?trim($_REQUEST['p']):"0";

		if(true) {
			//$phone = trim($_REQUEST['p']);
			$phone = isset($_REQUEST['p'])?trim($_REQUEST['p']):"0";
			$query = "
				SELECT l.*
				FROM n_leads l
				WHERE l.lead_phone = '$phone'
				LIMIT 1
			";
			$leads = $db->_execute($query);

			$hotlist = array();
			while($row = mysql_fetch_array($leads, MYSQL_ASSOC)) {
				foreach ($row as $field => $val) {
					$temp[$field] = $val;
				}
				$hotlist[] = $temp;
			}


			$customers = $this->_getcustomers($phone);
			$history = $this->_gethistory(trim($phone));
			$this->set('hotlist', $hotlist[0]);
			$this->set('customers', $customers);
			$this->set('test_phone', $test_phone);
			$this->set('history', $history);
			$this->_saveusermetric($user_id, "START CALL", $phone);
		} else {
			$this->_redirect('calls/index');
		}

		$query = "
			SELECT COUNT(e.lead_id) call_total, cs.call_status_name, c.call_status
			FROM n_latest_call lc
			LEFT JOIN n_calls c
			ON lc.call_id = c.call_id
			LEFT JOIN n_leads e
			ON c.call_number = e.lead_phone
			LEFT JOIN n_call_status cs
			ON c.call_status = cs.call_status_id
			WHERE e.lead_id IS NOT NULL
			AND DATE(c.call_return) = CURDATE()
			GROUP BY c.call_status
			ORDER BY c.call_return ASC
		";
		$results = $db->_execute($query);

		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[$temp['call_status']] = $temp;
		}

		$this->set('call_total', $rows);


		$this->layout = 'responsive2';
	}


	function nomoreleads() {
		$this->layout = 'responsive2';
	}

	function dispositioned() {
		$this->layout = 'responsive2';
	}

	function calltrigger() {
		$this->_saveusermetric($_SESSION['user']['id'], "ON CALL", $_POST['phone']);
		$this->layout = 'blank';
		echo "OK";
	}

	function edithopper() {
		$this->layout = 'responsive2';
		$db =& ConnectionManager::getDataSource('default');

		$query = "
			SELECT *
			FROM n_hopper h
			LEFT JOIN n_leads l
			ON h.lead_id = l.lead_id
		";
		$results = $db->_execute($query);

		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[$row['user_id']][$row['lead_id']] = $temp;
		}

		$this->set('hopper', $rows);
	}
/****************Jack**********************/
function admin_ace_lists(){
	        $db =& ConnectionManager::getDataSource('default');
	        $query = "SELECT * from n_lists where new_city!=1 ORDER BY list_name ASC ";
			$results = $db->_execute($query);
			$admin_hot_sub = array();
			while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
				if($row['list_id'])
				    $icon = "circle";
				else  	$icon = "flash";
				$admin_hot_sub[] = array(
					"icon" => $icon,
					"text" => $row['list_name'],
					"link" => "/calls/hotlist?l=".$row['list_id']."&t=1",

				);
				
			}
           return $admin_hot_sub;
}

/******************************************/

function new_citylists_foragent(){
  $db =& ConnectionManager::getDataSource('default');
  $query = "SELECT  * from n_lists where new_city=1 order by list_name ASC";
  
  $results = $db->_execute($query);

		$newcity_list = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
				if($row['list_id'])	$icon = "circle";
				     else  $icon = "flash";
				
				$newcity_list[] = array(
					"icon" => $icon,
					"text" => $row['list_name'],
					"link" => "/calls/hotlist?l=".$row['list_id']."&t=1",

				);
				
			}
		
		return $newcity_list;
	
		
	}	 
/******************************************/

function new_citylists_foradmin(){
  $db =& ConnectionManager::getDataSource('default');
  $query = "SELECT  * from n_lists where new_city=1 order by list_name ASC";
  
  $results = $db->_execute($query);

		$newcity_list = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
				if($row['list_id'])	$icon = "circle";
				     else  $icon = "flash";
				
				$newcity_list[] = array(
					"icon" => $icon,
					"text" => $row['list_name'],
					"link" => "/calls/importleads?l=".$row['list_id'],

				);
				
			}
		
		return $newcity_list;
	
		
	}	 
/******************************************/

	function agent_lists(){ 
		$db =& ConnectionManager::getDataSource('default');
		
		$query = "select * from ace_rp_users u where exists (select * from ace_rp_users_roles r where r.user_id=u.id and r.role_id!=8) and u.is_active=1 and exists (select * from ace_rp_users_roles r where r.user_id=u.id and r.role_id=3) order by username asc";
		
		$results = $db->_execute($query);
		$agentlist=array();
		
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			
			$agentlist[] =array('id'=>$row['id'] , 'first_name' =>$row['first_name']);
		}

	  return $agentlist;
		
	}	 

/*************************************/
	function uploadcsv() {  

		$this->layout = 'responsive2';
		$db =& ConnectionManager::getDataSource('default');
		$user_id = $_SESSION['user']['id'];
		
		if(isset($_POST['list_access'])) {
			$this->set('list_access', $_POST['agent']);
			foreach($_POST['agent'] as $user_id => $agent) {
				$query = "DELETE FROM n_list_users WHERE user_id = $user_id AND list_id > 0 and new_city!=1";
				$db->_execute($query);
				foreach($agent as $list_id => $val) {
				$query = "INSERT INTO n_list_users SET user_id = $user_id, list_id = $list_id";
				$db->_execute($query);
				}
			}
		}
		
		$query = "SELECT * FROM n_lists WHERE list_id > 0 and new_city!=1";
		$results = $db->_execute($query);
		$rows = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
		}
		$this->set('lists', $rows);

      /***********New List******/
       $query = "SELECT * FROM n_lists WHERE new_city=1 order by list_name";
       $results = $db->_execute($query);
       $rows = array();
	   while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
		}
		$this->set('new_lists', $rows);
		if(isset($_POST['new_list_access'])) {
			if(isset($_POST['agent'])){
				$this->set('new_list_access', $_POST['agent']);
			  foreach($_POST['agent'] as $user_id => $agent) {
				$query = "DELETE FROM n_list_users WHERE user_id = $user_id AND list_id > 0 and new_city=1";
				$db->_execute($query);
				
				foreach($agent as $list_id => $val) {
				$query = "INSERT INTO n_list_users SET user_id = $user_id, list_id = $list_id ,new_city = 1";
				$db->_execute($query);
				
				
				//save in hotlist
				$query="select * from n_leads where list_id=".$list_id;
				$results=$db->_execute($query);
				while($row=mysql_fetch_array($results, MYSQL_ASSOC)){
				     $query1 = "INSERT IGNORE INTO n_hotlist SET user_id = $user_id, hotlist_type_id = 1 ,lead_id =".$row['lead_id'];
				    $db->_execute($query1);
				}
								
				}
			}
			}else{
				$query = "DELETE FROM n_list_users WHERE list_id > 0 and new_city=1";
					$db->_execute($query);
			}
	  }
      
      /*$query = "
			SELECT u.*, t.lead_status, t.lead_total
			FROM n_new_list_users u
			LEFT JOIN n_list_totals t
			ON u.list_id = t.list_id
			WHERE u.list_id > 0
		";
		$results = $db->_execute($query);

		$rows = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[$row['list_id']][$row['lead_status']] = $row['lead_total'];
		}
		$this->set('new_list_totals', $rows);*/
      
        $query = "SELECT * FROM n_list_users where new_city=1";
		$results = $db->_execute($query);
		$rows = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[$row['user_id']][$row['list_id']] = 1;
		}

		$this->set('new_list_users', $rows);
    
      /*************************/


		$query = "
			SELECT u.*, t.lead_status, t.lead_total
			FROM n_list_users u
			LEFT JOIN n_list_totals t
			ON u.list_id = t.list_id
			WHERE u.list_id > 0
		";
		$results = $db->_execute($query);
		$rows = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[$row['list_id']][$row['lead_status']] = $row['lead_total'];
		}
		$this->set('list_totals', $rows);

		//$this->set('date', $_POST['date']);
		if (isset($_FILES['upfile']) && $_FILES['upfile']['size'] > 0) {

			//get the csv file
			$file = $_FILES['upfile']['tmp_name'];
			$handle = fopen($file,"r");
			$lines = array();
			$data = array();
			$list_created = false;
			$name = $_POST['list_name'];
			$date = $_POST['date'];
			//loop through the csv file and insert into database

			while ($data = fgetcsv($handle,1000,",","'")) {
				$lines[] = $data;
				if ($data[0]) {
					if(!$list_created) {
						$query = "
							INSERT INTO n_lists
							SET list_name = '$name',
							list_date = '$date'
						";
						$db->_execute($query);
						$result = $db->_execute("SELECT LAST_INSERT_ID() id");
						$result_id = 0;

						while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
							foreach ($row as $k => $v)
								$result_id = $v;
						}

						$list_created = true;
					}
					$query = "
						INSERT IGNORE INTO n_leads (lead_phone, lead_first_name, lead_last_name, lead_postal_code, lead_address, lead_city, list_id) VALUES
						(
							'".addslashes($data[0])."',
							'".addslashes($data[1])."',
							'".addslashes($data[2])."',
							'".addslashes($data[3])."',
							'".addslashes($data[4])."',
							'".addslashes($data[5])."',
							'".$result_id."'
						)
					";
					$db->_execute($query);
				}
			}
			//


			//redirect
			//header('Location: import.php?success=1'); die;
			//$this->set('data', $_FILES['upfile']);
			$this->set('result_id', $result_id);
			$this->set('message', "List uploaded");
		} else {
			$this->set('message', "");
		}

		

		$query = "SELECT * FROM n_list_users";
		$results = $db->_execute($query);

		$rows = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$list_users[$row['user_id']][$row['list_id']] = 1;
		}

		$this->set('list_users', $list_users);
		
		$agent_lists=$this->agent_lists();
		$this->set('agent_lists', $agent_lists);
	}


	function leadmanagement() {
		$db =& ConnectionManager::getDataSource('default');

		if($_POST['btn-submit']) {
			$date_range = str_replace(" - ", " AND ", $_POST['date_range']);
			$date_range = str_replace("\\", "", $date_range);

			$status = trim($_POST['status_id'])==""?"c.call_status":$_POST['status_id'];
			$this->set('date_range', str_replace("\\", "", $_POST['date_range']));
			$this->set('status_select', $_POST['status_id']);
		} else {
			$date_range = "'".date("Y-m-d")."' AND '".date("Y-m-d")."'";
			$status = "c.call_status";
			$this->set('date_range', "'".date("Y-m-d")."' - '".date("Y-m-d")."'");
			$this->set('status_select', "");
		}

		$query = "
			SELECT l.*, c.user_id, c.call_status, c.call_number, cs.call_status_name, c.call_date, DATE_FORMAT(call_date, '%b %e %Y %H:%i:%s') AS call_formatdate, DATEDIFF(CURDATE(), c.call_date) AS call_last
			FROM n_calls c
			LEFT JOIN n_latest_call lc
			ON c.call_id = lc.call_id
			LEFT JOIN n_call_status cs
			ON cs.call_status_id = c.call_status
			LEFT JOIN n_leads l
			ON l.lead_phone = c.call_number
			WHERE DATE(c.call_date) BETWEEN $date_range
			AND c.call_status = $status
		";
		$results = $db->_execute($query);

		$leads = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$leads[$row['call_number']] = $temp;
		}

		$query = "
			SELECT * FROM n_call_status
		";
		$results = $db->_execute($query);

		$status = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$status[$row['call_status_id']] = $temp;
		}

		$this->set('status', $status);
		$this->set('leads', $leads);
		$this->layout = 'responsive2';
	}


	function adminstats() {
		$user_id = $_SESSION['user']['id'];
		$db =& ConnectionManager::getDataSource('default');

		if($_POST['btn-submit']) {
			$date_range = str_replace(" - ", " AND ", $_POST['date_range']);
			$date_range = str_replace("\\", "", $date_range);
			$date_range = substr_replace(str_replace("' AND ", " 00:00:00' AND ", $date_range), " 23:59:59'",-1);
			$this->set('date_range', str_replace("\\", "", $_POST['date_range']));
		} else {
			$date_range = "'".date("Y-m-d")."' AND '".date("Y-m-d")."'";
			$date_range = "'".date("Y-m-d 00:00:00")."' AND '".date("Y-m-d 23:59:59")."'";
			$this->set('date_range', "'".date("Y-m-d")."' - '".date("Y-m-d")."'");
		}

		$query = "
			SELECT *, DATE(metric_datetime) AS metric_date
			FROM n_user_metrics
			WHERE DATE(metric_datetime) BETWEEN $date_range
			ORDER BY metric_datetime
		";
		$this->set('query', $query);
		$results = $db->_execute($query);

		$rows = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[$row['user_id']][] = $temp;
		}

		$metrics = array();
		//$start_call_time;
		//$start_oncall_time;
		//$start_disp_time;
		foreach($rows as $user_id => $user) {
			$summary[$user_id]['calls'] = 0;
			$summary[$user_id]['on_calls'] = 0;
			$summary[$user_id]['on_disps'] = 0;
			$summary[$user_id]['idles_total'] = 0;
			$summary[$user_id]['calls_total'] = 0;
			foreach($user as $i => $row) {

				if($row['metric_action'] == "START CALL") {
					if(!isset($start_call_time)) {
						$start_call_time = $row['metric_datetime'];
						if(isset($start_disp_time)) {
							$metrics[$user_id][$row['metric_date']][$i]['start'] = $start_disp_time;
							$metrics[$user_id][$row['metric_date']][$i]['end'] = $start_call_time;
							$temp_dur = strtotime($start_call_time) - strtotime($start_disp_time);
							$metrics[$user_id][$row['metric_date']][$i]['diff_in_seconds'] = $temp_dur;
							$metrics[$user_id][$row['metric_date']][$i]['type'] = "load";
							if($temp_dur < 28800)
								$summary[$user_id]['loads_total'] += $temp_dur;
							unset($start_disp_time);
							unset($start_oncall_time);
						}
					}
				}
				if($row['metric_action'] == "ON CALL") {
					$start_oncall_time = $row['metric_datetime'];
					if(isset($start_call_time)) {
						$metrics[$user_id][$row['metric_date']][$i]['start'] = $start_call_time;
						$metrics[$user_id][$row['metric_date']][$i]['end'] = $start_oncall_time;
						$temp_dur = strtotime($start_oncall_time) - strtotime($start_call_time);
						$metrics[$user_id][$row['metric_date']][$i]['diff_in_seconds'] = $temp_dur;
						$metrics[$user_id][$row['metric_date']][$i]['type'] = "idle";
						if($temp_dur < 28800)
							$summary[$user_id]['idles_total'] += $temp_dur;
						unset($start_call_time);
					}
					$summary[$user_id]['on_calls'] += 1;
				}
				if($row['metric_action'] == "DISP CALL") {
					$start_disp_time = $row['metric_datetime'];
					if(isset($start_oncall_time)) {
						$metrics[$user_id][$row['metric_date']][$i]['start'] = $start_oncall_time;
						$metrics[$user_id][$row['metric_date']][$i]['end'] = $start_disp_time;
						$temp_dur = strtotime($start_disp_time) - strtotime($start_oncall_time);
						$metrics[$user_id][$row['metric_date']][$i]['diff_in_seconds'] = $temp_dur;
						$metrics[$user_id][$row['metric_date']][$i]['type'] = "call";
						if($temp_dur < 28800)
							$summary[$user_id]['calls_total'] += $temp_dur;
						unset($start_oncall_time);
						$summary[$user_id]['calls'] += 1;

					} else if(isset($start_call_time)) {
						$metrics[$user_id][$row['metric_date']][$i]['start'] = $start_call_time;
						$metrics[$user_id][$row['metric_date']][$i]['end'] = $start_disp_time;
						$temp_dur = strtotime($start_disp_time) - strtotime($start_call_time);
						$metrics[$user_id][$row['metric_date']][$i]['diff_in_seconds'] = $temp_dur;
						$metrics[$user_id][$row['metric_date']][$i]['type'] = "disp";
						if($temp_dur < 28800)
							$summary[$user_id]['disps_total'] += $temp_dur;
						unset($start_oncall_time);
						unset($start_call_time);
						$summary[$user_id]['calls'] += 1;
					}
					$summary[$user_id]['on_disps'] += 1;
				}
			}
			unset($start_call_time);
			unset($start_oncall_time);
			unset($start_disp_time);
		}

		$query = "
			SELECT c.user_id, s.call_status_name, c.call_status, COUNT(c.call_status) stat_count
			FROM n_calls c
			LEFT JOIN n_call_status s
			ON c.call_status = s.call_status_id
			WHERE c.call_date BETWEEN $date_range
			GROUP BY c.user_id, c.call_status
			ORDER BY s.call_status_name
		";

		$cresults = $db->_execute($query);

		$call_results = array();
		while($row = mysql_fetch_array($cresults, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$ctemp[$field] = $val;
			}
			$call_results[$row['call_status_name']][$row['user_id']] = $ctemp;
		}

		$query = "
			SELECT l.*, c.user_id, s.call_status_name, c.call_status, '' stat_count, cu.id customer_id
			FROM n_calls c
			LEFT JOIN n_call_status s
			ON c.call_status = s.call_status_id
			LEFT JOIN n_leads l
			ON l.lead_phone = c.call_number
			LEFT JOIN ace_rp_customers cu
			ON c.call_number = cu.phone
			WHERE c.call_date BETWEEN $date_range
			ORDER BY s.call_status_name
		";

		$cresults = $db->_execute($query);

		$call_result_details = array();
		while($row = mysql_fetch_array($cresults, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$ctemp[$field] = $val;
			}
			$call_result_details[$row['call_status_name']][$row['user_id']][] = $ctemp;
		}



		$this->set('call_result_details', $call_result_details);

		$this->set('call_results', $call_results);

		$this->set('metrics', $rows);
		$this->set('metrics_calc', $metrics);
		$this->set('metrics_summary', $summary);
		$this->layout = 'responsive2';
	}

	function agentstats() {
		$user_id = $_SESSION['user']['id'];
		$db =& ConnectionManager::getDataSource('default');

		if($_POST['btn-submit']) {
			$date_range = str_replace(" - ", " AND ", $_POST['date_range']);
			$date_range = str_replace("\\", "", $date_range);
			$date_range = substr_replace(str_replace("' AND ", " 00:00:00' AND ", $date_range), " 23:59:59'",-1);
			$this->set('date_range', str_replace("\\", "", $_POST['date_range']));
		} else {
			$date_range = "'".date("Y-m-d")."' AND '".date("Y-m-d")."'";
			$date_range = "'".date("Y-m-d 00:00:00")."' AND '".date("Y-m-d 23:59:59")."'";
			$this->set('date_range', "'".date("Y-m-d")."' - '".date("Y-m-d")."'");
		}

		$query = "
			SELECT *, DATE(metric_datetime) AS metric_date
			FROM n_user_metrics
			WHERE DATE(metric_datetime) BETWEEN $date_range
			AND user_id = $user_id
			ORDER BY metric_datetime
		";
		$this->set('query', $query);
		$results = $db->_execute($query);

		$rows = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[$row['user_id']][] = $temp;
		}

		$metrics = array();
		//$start_call_time;
		//$start_oncall_time;
		//$start_disp_time;
		foreach($rows as $user_id => $user) {
			$summary[$user_id]['calls'] = 0;
			$summary[$user_id]['idles_total'] = 0;
			$summary[$user_id]['calls_total'] = 0;
			foreach($user as $i => $row) {

				if($row['metric_action'] == "START CALL") {
					if(!isset($start_call_time)) {
						$start_call_time = $row['metric_datetime'];
						if(isset($start_disp_time)) {
							$metrics[$user_id][$row['metric_date']][$i]['start'] = $start_disp_time;
							$metrics[$user_id][$row['metric_date']][$i]['end'] = $start_call_time;
							$temp_dur = strtotime($start_call_time) - strtotime($start_disp_time);
							$metrics[$user_id][$row['metric_date']][$i]['diff_in_seconds'] = $temp_dur;
							$metrics[$user_id][$row['metric_date']][$i]['type'] = "load";
							if($temp_dur < 28800)
								$summary[$user_id]['loads_total'] += $temp_dur;
							unset($start_disp_time);
							unset($start_oncall_time);
						}
					}
				}
				if($row['metric_action'] == "ON CALL") {
					$start_oncall_time = $row['metric_datetime'];
					if(isset($start_call_time)) {
						$metrics[$user_id][$row['metric_date']][$i]['start'] = $start_call_time;
						$metrics[$user_id][$row['metric_date']][$i]['end'] = $start_oncall_time;
						$temp_dur = strtotime($start_oncall_time) - strtotime($start_call_time);
						$metrics[$user_id][$row['metric_date']][$i]['diff_in_seconds'] = $temp_dur;
						$metrics[$user_id][$row['metric_date']][$i]['type'] = "idle";
						if($temp_dur < 28800)
							$summary[$user_id]['idles_total'] += $temp_dur;
						unset($start_call_time);
					}
				}
				if($row['metric_action'] == "DISP CALL") {
					$start_disp_time = $row['metric_datetime'];
					if(isset($start_oncall_time)) {
						$metrics[$user_id][$row['metric_date']][$i]['start'] = $start_oncall_time;
						$metrics[$user_id][$row['metric_date']][$i]['end'] = $start_disp_time;
						$temp_dur = strtotime($start_disp_time) - strtotime($start_oncall_time);
						$metrics[$user_id][$row['metric_date']][$i]['diff_in_seconds'] = $temp_dur;
						$metrics[$user_id][$row['metric_date']][$i]['type'] = "call";
						if($temp_dur < 28800)
							$summary[$user_id]['calls_total'] += $temp_dur;
						unset($start_oncall_time);
						$summary[$user_id]['calls'] += 1;

					} else if(isset($start_call_time)) {
						$metrics[$user_id][$row['metric_date']][$i]['start'] = $start_call_time;
						$metrics[$user_id][$row['metric_date']][$i]['end'] = $start_disp_time;
						$temp_dur = strtotime($start_disp_time) - strtotime($start_call_time);
						$metrics[$user_id][$row['metric_date']][$i]['diff_in_seconds'] = $temp_dur;
						$metrics[$user_id][$row['metric_date']][$i]['type'] = "disp";
						if($temp_dur < 28800)
							$summary[$user_id]['disps_total'] += $temp_dur;
						unset($start_oncall_time);
						unset($start_call_time);
						$summary[$user_id]['calls'] += 1;
					}
				}
			}
			unset($start_call_time);
			unset($start_oncall_time);
			unset($start_disp_time);
		}

		$query = "
			SELECT c.user_id, s.call_status_name, c.call_status, COUNT(c.call_status) stat_count
			FROM n_calls c
			LEFT JOIN n_call_status s
			ON c.call_status = s.call_status_id
			WHERE c.call_date BETWEEN $date_range
			AND c.user_id = $user_id
			GROUP BY c.user_id, c.call_status
			ORDER BY s.call_status_name
		";

		$cresults = $db->_execute($query);

		$call_results = array();
		while($row = mysql_fetch_array($cresults, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$ctemp[$field] = $val;
			}
			$call_results[$row['call_status_name']][$row['user_id']] = $ctemp;
		}


		$this->set('call_results', $call_results);
		$this->set('metrics', $rows);
		$this->set('metrics_calc', $metrics);
		$this->set('metrics_summary', $summary);
		$this->layout = 'responsive2';
	}

	function _setsidebar($user_id) {
		if(!isset($_SESSION['sidebar-menu'])) {  
			$_SESSION['sidebar-menu'] = array();

			$db =& ConnectionManager::getDataSource('default');
            		  
			  $query = "
				SELECT l.*
				FROM n_list_users lu
				LEFT JOIN n_lists l
				ON lu.list_id = l.list_id
				WHERE lu.user_id = $user_id and l.new_city!=1
                                ORDER BY l.list_name ASC 
			";
			 $results = $db->_execute($query);
			$i = 0;
			$new_sub = array();
			$hot_sub = array();
			while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
				if($row['list_id'])
				$icon = "circle";
				else
				$icon = "flash";
				$new_sub[] = array(
					"icon" => $icon,
					"text" => $row['list_name'],
					"link" => "/calls/phonelist?l=".$row['list_id'],

				);
				$hot_sub[] = array(
					"icon" => $icon,
					"text" => $row['list_name'],
					"link" => "/calls/hotlist?l=".$row['list_id']."&t=1",

				);
				
				/*$hot_import_sub[] = array(
					"icon" => $icon,
					"text" => $row['list_name'],
					"link" => "/calls/importleads?l=".$row['list_id']."&t=1",

				);*/
				//foreach ($row as $field => $val) $rows[$field] = $val;
			}


			if($user_id == 206767 || $user_id == 44851 || $user_id == 226792 || $user_id == 231307) 
			{
				$admin_acelists = $this->admin_ace_lists();
				$newCityList = $this->new_citylists_foradmin();
				$_SESSION['sidebar-menu'] = array(
					/*"new" => array(
						"icon" => "phone",
						"text" => "Leads",
						"submenu" => $new_sub
					),
					"hotlist" => array(
						"icon" => "phone-square",
						"text" => "Ace List",
						"submenu" => $hot_sub
					),*/
					"admin" => array(
						"icon" => "cogs",
						"text" => "Admin",
						"submenu" => array(
							/*array(
								"icon" => "",
								"text" => "Hopper",
								"link" => "/calls/edithopper"
							),*/
							array(
								"icon" => "",
								"text" => "ACE Callbacks",
								"link" => "/calls/callbacksearch"
							),
							/*array(
								"icon" => "",
								"text" => "Callback Management",
								"link" => "/calls/admincallbacks"
							),*/
							array(
								"icon" => "",
								"text" => "List Management",
								"link" => "/calls/uploadcsv"
							),
							array(
								"icon" => "",
								"text" => "Lead Management",
								"link" => "/calls/leadmanagement"
							),
							array(
								"icon" => "",
								"text" => "Leads Generated",
								"link" => "/calls/adminhotlist"
							),
							array(
								"icon" => "",
								"text" => "Agent Stats",
								"link" => "/calls/adminstats"
							),
							array(
								"icon" => "",
								"text" => "Agent Spiels",
								"link" => "/calls/listspiels"
							),
							array(
								"icon" => "",
								"text" => "Year Lists",
								"link" => "/calls/yearlist"
							),
							array(
								"icon" => "",
								"text" => "Last Year Lists",
								"link" => "/calls/lastyearlist"
							),
							array(
								"icon" => "",
								"text" => "Email Notification",
								"link" => "/calls/emailnotifications"
							),
							array(
								"icon" => "external-link",
								"text" => "Back to ACE",
								"link" => "/pages/main"
							),
							array(
								"icon" => "",
								"text" => "Add New City",
								"link" => "/calls/newcities"
							)
							

						)
					),
					"adminacelist" =>array(
								"icon" => "phone-square",
								"text" => "Ace List",
								"submenu" => $admin_acelists
							),
					"aceimportlist" =>array(
								"icon" => "list",
								"text" => "New List",
								"submenu" => $newCityList
							),
					"inventory" => array(
						"icon" => "cubes",
						"text" => "inventory",
						"submenu" => array(
							/*array(
								"icon" => "",
								"text" => "Items",
								"link" => "/calls/inventoryitems"
							),*/
							/*array(
								"icon" => "",
								"text" => "Supplier Puchases",
								"link" => "/calls/inventorypurchases"
							),*/
							array(
								"icon" => "",
								"text" => "Invoice Puchases",
								"link" => "/calls/invoicepurchases"
							),
							array(
								"icon" => "",
								"text" => "Pay Purchases",
								"link" => "/calls/purchases"
							),
							array(
								"icon" => "",
								"text" => "Item Count",
								"link" => "/calls/inventorylocationscount"
							),
							array(
								"icon" => "",
								"text" => "Item Count Adj",
								"link" => "/calls/inventorylocationsadj"
							),
							array(
								"icon" => "",
								"text" => "Adjustment Report",
								"link" => "/calls/adjustmentreport"
							),

						)
					),
					/*
					"accounting" => array(
						"icon" => "pencil",
						"text" => "Accounting",
						"submenu" => array(
							array(
								"icon" => "",
								"text" => "Invoice Puchases",
								"link" => "/calls/invoicepurchase"
							),


						)
					)
					*/
				);
			} else {
				$newCityList_Agent = $this->new_citylists_foragent();
				$_SESSION['sidebar-menu'] = array(
					/*
					"new" => array(
						"icon" => "phone",
						"text" => "Leads",
						"submenu" => $new_sub
					),
					*/
					"hotlist" => array(
						"icon" => "phone-square",
						"text" => "Ace List",
						"submenu" => $hot_sub
					),
					"aceimportlist" => array(
						"icon" => "list",
						"text" => "New List",
						"submenu" => $newCityList_Agent
					),
					"reports" => array(
						"icon" => "bar-chart-o",
						"text" => "Reports",
						"submenu" => array(
							array(
								"icon" => "",
								"text" => "Stats",
								"link" => "/calls/agentstats"
							),
							array(
								"icon" => "calendar",
								"text" => "ACE Callbacks",
								"link" => "/calls/callbacksearch"
							),
						)
					),

				);
			}
			
			
		}
	}

	function listspiels() {
		$db =& ConnectionManager::getDataSource('default');

		if($_POST['list_access']) {
			foreach($_POST['spiel'] as $list_id => $spiel) {
				$query = "UPDATE n_lists SET list_spiel = '$spiel' WHERE list_id = $list_id";
				$db->_execute($query);
			}
		}

		$query = "
			SELECT *
			FROM n_lists
			WHERE list_id > 0
		";
		$results = $db->_execute($query);

		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
		}



		$this->set('lists', $rows);
		$this->layout = 'responsive2';
	}

	function _gethopper($user_id, $list_id) {
		$db =& ConnectionManager::getDataSource('default');

		$query = "
			SELECT *
			FROM n_hopper h
			LEFT JOIN n_leads l
			ON h.lead_id = l.lead_id
			WHERE h.user_id = $user_id
			AND l.list_id = $list_id
		";
		$leads = $db->_execute($query);

		if(mysql_num_rows($leads) == 0) {
			$query = "
				INSERT INTO n_hopper(lead_id, user_id)
				SELECT l.lead_id, $user_id
				FROM n_leads l
				LEFT JOIN n_list_users lu
				ON lu.list_id = l.list_id
				WHERE lu.user_id = $user_id
				AND l.lead_status = 1
				AND l.list_id = $list_id
				LIMIT 20
			";
			$results = $db->_execute($query);

			$query = "
				UPDATE n_leads
				SET lead_status = 2
				WHERE lead_id IN (SELECT lead_id FROM n_hopper)
				AND lead_status = 1
			";
			$db->_execute($query);
		}

		$query = "
			SELECT l.*
			FROM n_hopper h
			LEFT JOIN n_leads l
			ON h.lead_id = l.lead_id
			WHERE h.user_id = $user_id
			AND l.list_id = $list_id
			LIMIT 1
		";
		$leads = $db->_execute($query);

		$rows = array();
		while($row = mysql_fetch_array($leads, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
		}

		//return $this->_rows($leads, "lead_id");
		return $rows;

	}

	function _gethotlist($user_id, $list_id, $hotlist_type_id = 0) {
		$db =& ConnectionManager::getDataSource('default');

		$query = "
			SELECT COUNT(*) rows
			FROM n_hotlist h
			LEFT JOIN n_leads l
			ON h.lead_id = l.lead_id
			WHERE h.user_id = $user_id
			AND l.list_id = $list_id
			AND h.hotlist_type_id = $hotlist_type_id
		";
		$leads = $db->_execute($query);
		$count = 0;
		while($row = mysql_fetch_array($leads, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$count = $val;
			}
		}

		if($count == 0) { 
			 $query = "
				INSERT INTO n_hotlist(lead_id, user_id)
				SELECT e.lead_id, $user_id
				FROM n_latest_1year l
				LEFT JOIN n_calls c
				ON l.latest_id = c.call_id
				LEFT JOIN n_leads e
				ON c.call_number = e.lead_phone
				WHERE e.lead_id NOT IN (SELECT lead_id FROM n_hotlist)

				AND e.list_id = $list_id
				GROUP BY e.lead_id
				ORDER BY e.lead_last_called, e.lead_call_count

				LIMIT 10
			"; 
			if($hotlist_type_id == 1) {
				$query = "
					INSERT INTO n_hotlist(lead_id, user_id, hotlist_type_id)
					SELECT e.lead_id, $user_id, $hotlist_type_id
					FROM n_latest_call lc
					LEFT JOIN n_calls c
					ON lc.call_id = c.call_id
					LEFT JOIN n_leads e
					ON c.call_number = e.lead_phone
					LEFT JOIN n_call_status cs
					ON c.call_status = cs.call_status_id
					WHERE e.lead_id NOT IN (SELECT lead_id FROM n_hotlist)
					AND e.lead_id IS NOT NULL
					AND c.call_return <= NOW()
					AND c.call_return != '0000-00-00 00:00:00'
					AND e.list_id = $list_id
					ORDER BY c.call_return ASC
					LIMIT 10
				";
			}
			if($hotlist_type_id == 11) {
				$query = "
					INSERT INTO n_hotlist(lead_id, user_id, hotlist_type_id)
					SELECT e.lead_id, $user_id, $hotlist_type_id
					FROM n_latest_call lc
					LEFT JOIN n_calls c
					ON lc.call_id = c.call_id
					LEFT JOIN n_leads e
					ON c.call_number = e.lead_phone
					LEFT JOIN n_call_status cs
					ON c.call_status = cs.call_status_id
					WHERE e.lead_id NOT IN (SELECT lead_id FROM n_hotlist)
					AND e.lead_id IS NOT NULL
					AND c.call_return <= NOW()
					AND c.call_return != '0000-00-00 00:00:00'
					AND e.list_id = $list_id
					ORDER BY c.call_return ASC
					LIMIT 10
				";
			}
			if($hotlist_type_id == 2) {
				$query = "
					INSERT INTO n_hotlist(lead_id, user_id, hotlist_type_id)
					SELECT e.lead_id, $user_id, $hotlist_type_id
					FROM n_latest_call lc
					LEFT JOIN n_calls c
					ON lc.call_id = c.call_id
					LEFT JOIN n_leads e
					ON c.call_number = e.lead_phone
					LEFT JOIN n_call_status cs
					ON c.call_status = cs.call_status_id
					WHERE e.lead_id NOT IN (SELECT lead_id FROM n_hotlist)
					AND e.lead_id IS NOT NULL
					AND c.call_return <= NOW()
					AND c.call_return != '0000-00-00 00:00:00'
					AND e.list_id = $list_id
					ORDER BY c.call_return ASC
					LIMIT 10
				";

			}



			/*
			AND
				(
					(l.latest_status = 2 AND l.latest_count < 3 AND CURDATE() >= DATE_ADD(c.call_date, INTERVAL 2 DAY))
					OR
					(l.latest_status = 3 AND l.latest_count < 3 AND CURDATE() >= DATE_ADD(c.call_date, INTERVAL 2 DAY))
					OR
					(l.latest_status = 4 AND CURDATE() >= DATE_ADD(c.call_date, INTERVAL 11 MONTH))
				)
			*/
			/*
			$query = "
				INSERT INTO n_hotlist(lead_id, user_id)
				SELECT e.lead_id, $user_id
				FROM n_latest_1year l
				LEFT JOIN n_calls c
				ON l.latest_id = c.call_id
				LEFT JOIN n_leads e
				ON c.call_number = e.lead_phone
				WHERE e.lead_id NOT IN (SELECT lead_id FROM n_hotlist)
				AND l.latest_status = 10
				LIMIT 10
			";
			*/
			$results = $db->_execute($query);
		}


		 $query = "
			SELECT l.*
			FROM n_hotlist h
			LEFT JOIN n_leads l
			ON h.lead_id = l.lead_id
			WHERE h.user_id = $user_id
			AND l.list_id = $list_id
			LIMIT 1
		";
		$leads = $db->_execute($query);

		$rows = array();
		while($row = mysql_fetch_array($leads, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
		}

		//return $this->_rows($leads, "lead_id");
		return $rows;

	}

	function _gethoppers($user_id, $list_id, $type_id = 0) {
		$db =& ConnectionManager::getDataSource('default');

		$query = "
			SELECT COUNT(*) rows
			FROM n_hoppers h
			LEFT JOIN n_leads l
			ON h.lead_id = l.lead_id
			WHERE h.user_id = $user_id
			AND l.list_id = $list_id
		";
		$leads = $db->_execute($query);
		$count = 0;
		while($row = mysql_fetch_array($leads, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$count = $val;
			}
		}

		if($count == 0) {
			$query = "
				INSERT INTO n_hoppers(lead_id, user_id, hopper_type_id)
				SELECT e.lead_id, $user_id, $type_id
				FROM n_latest_1year l
				LEFT JOIN n_calls c
				ON l.latest_id = c.call_id
				LEFT JOIN n_leads e
				ON c.call_number = e.lead_phone
				WHERE e.lead_id NOT IN (SELECT lead_id FROM n_hoppers)
				AND e.list_id = $list_id
				GROUP BY e.lead_id
				ORDER BY e.lead_last_called, e.lead_call_count
				LIMIT 10
			";
			$query = "
				INSERT INTO n_hoppers(lead_id, user_id, hopper_type_id)
				SELECT e.lead_id, $user_id, $type_id
				FROM n_latest_call lc
				LEFT JOIN n_calls c
				ON lc.call_id = c.call_id
				LEFT JOIN n_leads e
				ON c.call_number = e.lead_phone
				LEFT JOIN n_call_status cs
				ON c.call_status = cs.call_status_id
				WHERE e.lead_id NOT IN (SELECT lead_id FROM n_hoppers)
				AND e.lead_id IS NOT NULL
				AND c.call_return <= NOW()
				AND c.call_return != '0000-00-00 00:00:00'
				ORDER BY c.call_return ASC
			";
			$results = $db->_execute($query);
		}

		$query = "
			SELECT l.*
			FROM n_hoppers h
			LEFT JOIN n_leads l
			ON h.lead_id = l.lead_id
			WHERE h.user_id = $user_id
			AND l.list_id = $list_id
			AND h.hopper_type_id = $type_id
			LIMIT 1
		";
		$leads = $db->_execute($query);

		$rows = array();
		while($row = mysql_fetch_array($leads, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
		}

		return $rows;

	}

	function adminhotlist()
	{
		//$date = isset($_POST['date'])?$_POST['date']:date("Y-m-d");

		if($_POST['btn-filter']) {
			$date_range = str_replace(" - ", " AND ", $_POST['date_range']);
			$date_range = str_replace("\\", "", $date_range);
			$date_range = substr_replace(str_replace("' AND ", " 00:00:00' AND ", $date_range), " 23:59:59'",-1);
			$supplier_name = $_POST['supplier_name'];
			$this->set('date_range', str_replace("\\", "", $_POST['date_range']));
			$this->set('supplier_name', $supplier_name);
		} else {
			$date_range = "'".date("Y-m-d")."' AND '".date("Y-m-d")."'";
			$date_range = "'".date("Y-m-d 00:00:00")."' AND '".date("Y-m-d 23:59:59")."'";
			$this->set('date_range', "'".date("Y-m-d")."' - '".date("Y-m-d")."'");
			$supplier_name = "";
		}

		$db =& ConnectionManager::getDataSource('default');

		$past_answering_machine = "
			SELECT e.*, cs.call_status_name, c.call_status, c.call_date, c.call_return, MONTHNAME(c.call_return) mname
			FROM n_latest_call lc
			LEFT JOIN n_calls c
			ON lc.call_id = c.call_id
			LEFT JOIN n_leads e
			ON c.call_number = e.lead_phone
			LEFT JOIN n_call_status cs
			ON c.call_status = cs.call_status_id
			WHERE e.lead_id IS NOT NULL
			AND c.call_status = 2
			AND c.call_return < DATE_SUB(NOW(), INTERVAL 6 DAY);
		";

		$past_not_interested = "
			SELECT e.*, cs.call_status_name, c.call_status, c.call_date, c.call_return, MONTHNAME(c.call_return) mname
			FROM n_latest_call lc
			LEFT JOIN n_calls c
			ON lc.call_id = c.call_id
			LEFT JOIN n_leads e
			ON c.call_number = e.lead_phone
			LEFT JOIN n_call_status cs
			ON c.call_status = cs.call_status_id
			WHERE e.lead_id IS NOT NULL
			AND c.call_status = 1
			AND c.call_return < DATE_SUB(NOW(), INTERVAL 6 MONTH);
		";

		$query = "
			SELECT e.*, cs.call_status_name, c.call_status, c.call_date, c.call_return, MONTHNAME(c.call_return) mname
			FROM n_latest_call lc
			LEFT JOIN n_calls c
			ON lc.call_id = c.call_id
			LEFT JOIN n_leads e
			ON c.call_number = e.lead_phone
			LEFT JOIN n_call_status cs
			ON c.call_status = cs.call_status_id
			WHERE e.lead_id IS NOT NULL
		";
		//$results = $db->_execute($query);

		$query = "
			SELECT e.*, cs.call_status_name, c.call_status, c.call_date, c.call_return, MONTHNAME(c.call_return) mname
			FROM n_latest_call lc
			LEFT JOIN n_calls c
			ON lc.call_id = c.call_id
			LEFT JOIN n_leads e
			ON c.call_number = e.lead_phone
			LEFT JOIN n_call_status cs
			ON c.call_status = cs.call_status_id
			WHERE e.lead_id IS NOT NULL
			AND c.call_return BETWEEN $date_range
			ORDER BY c.call_return ASC

		";
		$results = $db->_execute($query);
		$rows = array();
		$row_months = array();

		$not_interested = 0;
		$answering_machine = 0;
		$busy = 0;
		$booked = 0;
		$callback = 0;
		$dnc = 0;
		$nic = 0;
		$wrong = 0;
		$no_eng = 0;
		$done = 0;


		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;

			$row_months[$temp['mname']][] = $temp;
		}

		$query = "
			SELECT cs.call_status_name, COUNT(c.call_status) as call_total
			FROM n_latest_call lc
			LEFT JOIN n_calls c
			ON lc.call_id = c.call_id
			LEFT JOIN n_leads e
			ON c.call_number = e.lead_phone
			LEFT JOIN n_call_status cs
			ON c.call_status = cs.call_status_id
			WHERE e.lead_id IS NOT NULL
			GROUP BY c.call_status
		";
		$results = $db->_execute($query);

		$summary = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$summary[] = $temp;
		}

		$this->set('summary', $summary);
		$this->set('leads', $rows);
		$this->set('lead_months', $row_months);
		$this->set('date', $date);
		$this->layout = 'responsive2';
	}


	function callbacks() {
		$user_id = $_SESSION['user']['id'];
		$db =& ConnectionManager::getDataSource('default');

		$query = "
			SELECT l.lead_id, l.lead_first_name, l.lead_last_name, c.*, DATE_FORMAT(c.call_return, '%b %e %Y') AS call_return_text, IF(CURDATE()=DATE(c.call_return), 'Today', IF(CURDATE()<DATE(c.call_return), 'Future', IF(CURDATE()>DATE(c.call_return), 'Past', '-'))) AS call_tab
			FROM n_latest_call lc
			LEFT JOIN n_calls c
			ON lc.call_id = c.call_id
			LEFT JOIN n_leads l
			ON l.lead_phone = c.call_number
			WHERE c.call_status = 5
			AND c.user_id = $user_id
			ORDER BY c.call_return DESC
		";

		$results = $db->_execute($query);

		$rows = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[$temp['call_tab']][] = $temp;
		}


		$this->set('callbacks', $rows);
		$this->layout = 'responsive2';
	}

	/*function admincallbacks() {
		$user_id = $_SESSION['user']['id'];
		$db =& ConnectionManager::getDataSource('default');

		if($_POST['btn-filter']) {
			$date_range = str_replace(" - ", " AND ", $_POST['date_range']);
			$date_range = str_replace("\\", "", $date_range);
			$date_range = substr_replace(str_replace("' AND ", " 00:00:00' AND ", $date_range), " 23:59:59'",-1);
			$this->set('date_range', str_replace("\\", "", $_POST['date_range']));
		} else {
			$date_range = "'".date("Y-m-d")."' AND '".date("Y-m-d")."'";
			$date_range = "'".date("Y-m-d 00:00:00")."' AND '".date("Y-m-d 23:59:59")."'";
			$this->set('date_range', "'".date("Y-m-d")."' - '".date("Y-m-d")."'");
		}

		if($_POST['callback_access']) {

			$this->set('data', $_POST['agent']);
			foreach($_POST['agent'] as $phone => $agent) {
				$date = trim($agent['date'])==""?date("Y-m-d 15:00:00"):trim($agent['date']);
				//$user_id = $agent['user'];
				$user_id = $_POST['change_user'];
				$query = "
					UPDATE n_calls
					SET user_id = $user_id
					WHERE call_number = '$phone'
				";
				$results = $db->_execute($query);

			}
		}

		$query = "
			SELECT l.lead_first_name, l.lead_last_name, c.*, DATE_FORMAT(c.call_return, '%b %e %Y') AS call_return_text, IF(CURDATE()=DATE(c.call_return), 'Today', IF(CURDATE()<DATE(c.call_return), 'Future', IF(CURDATE()>DATE(c.call_return), 'Past', '-'))) AS call_tab
			FROM n_latest_call lc
			LEFT JOIN n_calls c
			ON lc.call_id = c.call_id
			LEFT JOIN n_leads l
			ON l.lead_phone = c.call_number
			WHERE c.call_status = 5
			AND c.call_return BETWEEN $date_range
		";
		$results = $db->_execute($query);

		$rows = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[$temp['call_tab']][] = $temp;
		}

		$this->set('callbacks', $rows);
		$this->layout = 'responsive2';
	}*/

	function callback() {
		$user_id = $_SESSION['user']['id'];
		$lead_id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');

		$query = "
			SELECT l.*, c.*
			FROM n_leads l
			LEFT JOIN n_calls c
			ON l.lead_phone = c.call_number
			WHERE c.call_status = 5
			AND c.user_id = $user_id
			AND l.lead_id = $lead_id
			LIMIT 1
		";
		$results = $db->_execute($query);

		$callback = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$callback = $temp;
		}

		if(isset($_POST['disposition'])) {
			$this->_saveusermetric($user_id, "DISP CALL", $_POST['number']);

			$notes = $_POST['notes'];
			$lead_id = $_POST['lead'];
			$number = $_POST['number'];
			$return = $_POST['return'];
			$status = $_POST['disposition'];

			if(isset($return)) {
				$query = "
					INSERT INTO n_calls
					SET call_number = '$number',
						call_date = NOW(),
						call_status = $status,
						call_note = '$notes',
						call_return = '$return',
						user_id = $user_id
				";
			} else {
				$query = "
					INSERT INTO n_calls
					SET call_number = '$number',
						call_date = NOW(),
						call_status = $status,
						call_note = '$notes',
						user_id = $user_id
				";
			}
			$results = $db->_execute($query);

			$this->_redirect('calls/callbacks');
		}




		if(empty($callback)) $this->_redirect("calls/nomoreleads");



		$this->set('spiel', "");
		$phone = $callback['lead_phone'];
		//$customers = $this->_getcustomers($phone);
		$history = $this->_gethistory(trim($phone));
		$customers = $this->_getcustomers(trim($phone));
		$this->set('customers', $customers);
		$this->set('test_phone', $test_phone);
		$this->set('history', $history);
		$this->_saveusermetric($user_id, "START CALL", $phone);




		$this->set('callback', $callback);
		$this->layout = 'responsive2';
	}

	function _rows($results, $id_field = NULL) {
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			if ($id_field === null) {
				$rows[] = $temp;
			} else {
				$rows[$row[$id_field]] = $temp;
			}
		}
		return $rows;
	}

	function _getcustomers($phone) {
		$db =& ConnectionManager::getDataSource('default');

		 $query = "
			SELECT *
			FROM ace_rp_customers
			WHERE phone = '$phone'
			OR cell_phone = '$phone'
		";
		$results = $db->_execute($query);
         $rows=array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
		}

		return $rows;
	}

	function _gethistory($phone) {
		$db =& ConnectionManager::getDataSource('default');

		$query = "
			SELECT * FROM
			(
			SELECT c.call_number AS call_number, DATE_FORMAT(c.call_date, '%b %e %Y') AS call_date, DATEDIFF(CURDATE(), c.call_date) AS call_last, c.call_note AS call_note, cs.call_status_name AS call_result, 'Call' AS call_type, 0 AS call_job, DATE_FORMAT(c.call_return, '%b %e %Y') call_return
			FROM n_calls c
			LEFT JOIN n_call_status cs
			ON c.call_status = cs.call_status_id
			WHERE c.call_number = '$phone'
			UNION
			SELECT o.customer_phone AS call_number , DATE_FORMAT(o.job_date, '%b %e %Y') AS call_date, DATEDIFF(CURDATE(), o.job_date) AS call_last, '' AS call_note, 'Done' AS call_result, 'Jobs' AS call_type, o.id AS call_job, NULL
			FROM ace_rp_orders o
			WHERE o.order_status_id = 5
			AND o.customer_phone = '$phone'
			UNION
			SELECT o.customer_phone, DATE_FORMAT(o.job_date, '%b %e %Y'), DATEDIFF(CURDATE(), o.job_date) AS call_last, '', 'Cancelled', 'Jobs', o.id, NULL
			FROM ace_rp_orders o
			WHERE o.order_status_id = 3
			AND o.customer_phone = '$phone'
			UNION
			SELECT h.phone, DATE_FORMAT(h.call_date, '%b %e %Y'), DATEDIFF(CURDATE(), h.call_date) AS call_last, h.call_note, r.name, 'Call', 0, NULL
			FROM ace_rp_call_history h
			LEFT JOIN ace_rp_call_results r
			ON h.call_result_id = r.id
			WHERE h.phone = '$phone'
			) history
			ORDER BY history.call_last ASC
			LIMIT 20
		";
		$results = $db->_execute($query);
       $rows=array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$temp['call_subitems'] = array();
			if(isset($row['call_job']) && $row['call_job'] > 0) {
				$query = "
					SELECT * FROM ace_rp_order_items WHERE order_id = ".$row['call_job']."
				";

				$subresults = $db->_execute($query);

				while($subrow = mysql_fetch_array($subresults, MYSQL_ASSOC)) {
					foreach ($subrow as $subfield => $subval) {
						$subtemp[$subfield] = $subval;
					}
					$temp['call_subitems'][] = $subtemp;
				}
			}

			$temp['call_notes'] = array();
			
			if(isset($row['call_job'])) {
				$query = "
					SELECT n.*, u.first_name
					FROM ace_rp_notes n
					LEFT JOIN ace_rp_users u
					ON u.id = n.user_id
					WHERE n.order_id = ".$row['call_job']." ORDER BY n.id DESC
				";

				$subresults = $db->_execute($query);
                
				while($subrow = mysql_fetch_array($subresults, MYSQL_ASSOC)) {
					foreach ($subrow as $subfield => $subval) {
						$subtemp[$subfield] = $subval;
					}
					$temp['call_notes'][] = $subtemp;
				}
			}

			$rows[] = $temp;
		}

		return array("history" => $rows);
	}

	function yearlist()
	{
		$db =& ConnectionManager::getDataSource('default');

		if(isset($_POST['load_year'])) {
			$this->set('message', $_POST['load_year']." has been loaded.");

			$query = "
				UPDATE n_leads
				SET lead_status = 1
				WHERE lead_status = ".trim($_POST['load_year'])."
			";
			$results = $db->_execute($query);
		}

		$query = "
			SELECT lead_status, COUNT(lead_status) lead_count
			FROM n_leads
			WHERE lead_status NOT IN (2, 3)
			GROUP BY lead_status
			ORDER BY lead_status DESC
		";
		$results = $db->_execute($query);

		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
		}

		$this->set('lists', $rows);
		$this->layout = 'responsive2';

	}

	function updatelead() {
		$db =& ConnectionManager::getDataSource('default');

		$id = $_POST['edit_id'];
		$fname = $_POST['edit_first_name'];
		$lname = $_POST['edit_last_name'];
		$postal = $_POST['edit_postal_code'];
		$email = $_POST['edit_email'];

		$query = "
			UPDATE n_leads SET
			lead_first_name = '$fname',
			lead_last_name = '$lname',
			lead_postal_code = '$postal',
			lead_email = '$email'
			WHERE lead_id = $id
		";
		$db->_execute($query);

		echo "OK";
	}

	function emailnotifications()
	{
		$db =& ConnectionManager::getDataSource('default');
		//Get E-mail Settings
		$from_address = "info@acecare.ca";
		$from_name = "Management";
		$template = "
Dear {first_name} {last_name},

We did service your Furnace last year this time
This is just a reminder, It is time to get your appliance serviced again
We are there in next 2 weeks we can come and service it while we are there
you will also receive $20 off

Thanks
Management
		";
		$template_subject = "Service due reminder";

		if($_POST['btn-submit']) {
			$date_range = str_replace(" - ", " AND ", $_POST['date_range']);
			$date_range = str_replace("\\", "", $date_range);
			$date_range = substr_replace(str_replace("' AND ", " 00:00:00' AND ", $date_range), " 23:59:59'",-1);
			$this->set('date_range', str_replace("\\", "", $_POST['date_range']));
		} else {
			$date_range = "'".date("Y-m-d")."' AND '".date("Y-m-d")."'";
			$date_range = "'".date("Y-m-d 00:00:00")."' AND '".date("Y-m-d 23:59:59")."'";
			$this->set('date_range', "'".date("Y-m-d")."' - '".date("Y-m-d")."'");
		}

		$query = "
			SELECT c.first_name, c.last_name, c.email, j.job_date, j.customer_phone
			FROM ace_latest_done_job j
			LEFT JOIN ace_rp_customers c
			ON j.customer_phone = c.phone
			WHERE c.email NOT LIKE 'ace%'
			AND c.email NOT LIKE '%acecare%'
			AND c.email != ''
			AND c.email NOT LIKE 'sousaroy%'
			AND c.email NOT LIKE '%@'
			AND c.email NOT LIKE '%ace1.ca'
			AND c.email NOT LIKE '%noemail%'
			AND c.email NOT LIKE '%ace'
			AND c.email NOT LIKE '%ace.ca'
			AND c.email NOT LIKE '123@%'
			AND c.email NOT LIKE 'test@%'
			AND LENGTH(c.email) > 7
			AND DATE_ADD(j.job_date, INTERVAL 1 YEAR) BETWEEN $date_range
			GROUP BY c.email
			ORDER BY j.job_date
		";
		$results = $db->_execute($query);

		$updates = array();

		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;

			$msg = $template;
			$msg = str_replace('{first_name}', $row['first_name'], $msg);
			$msg = str_replace('{last_name}', $row['last_name'], $msg);
			//$msg = str_replace('{job_date}', date("d M Y", strtotime($row['job_date'])), $msg);
			//$msg = str_replace('{job_timeslot}', date('ga', strtotime($row['job_time_beg'])).' - '.date('ga', strtotime($row['job_time_end'])), $msg);
			//$msg = str_replace('{url_confirm}', "http://".$_ENV['SERVER_NAME'].BASE_URL.'/orders/confirm?a='.$row['id'].'&b='.$row['customer_id'], $msg);

			//$res = mail($row['email'], $template_subject, $msg, "From: ".$from_address);
			//$res = mail("hsestacio13@gmail.com", $template_subject, $msg, "From: ".$from_address);
			$messages[] = $msg;
			$updates[count($updates)] = "UPDATE ace_rp_orders SET notified_booking = 1 WHERE id = ".$obj['id']."\n";
		}

		/*foreach ($orders as $obj)
		{
			//print "Order:".$obj['Order']['id']." - ".$obj['Order']['job_date']." - ".$obj['Customer']['email']." - "."http://".$_ENV['SERVER_NAME'].BASE_URL.'/orders/confirm?a='.$obj['Order']['id'].'&b='.$obj['Customer']['id']."<br/>";
			$msg = $template;
			$msg = str_replace('{first_name}', $obj['first_name'], $msg);
			$msg = str_replace('{last_name}', $obj['last_name'], $msg);
			$msg = str_replace('{job_date}', date("d M Y", strtotime($obj['job_date'])), $msg);
			$msg = str_replace('{job_timeslot}', date('ga', strtotime($obj['job_time_beg'])).' - '.date('ga', strtotime($obj['job_time_end'])), $msg);
			$msg = str_replace('{url_confirm}', "http://".$_ENV['SERVER_NAME'].BASE_URL.'/orders/confirm?a='.$obj['id'].'&b='.$obj['id'], $msg);

			//$res = mail($obj['Customer']['email'], $template_subject, $msg, "From: ".$from_address);	//\"".$from_name."\"
			$messages[] = $msg;
			$updates[count($updates)] = "UPDATE ace_rp_orders SET notified_booking = 1 WHERE id = ".$obj['id']."\n";
		}

		//Set orders as 'customer notified'
		foreach ($updates as $update)
		{

			//$db->_execute($update);
		}*/
		//print "<pre>".$updates."</pre>";
		$this->set('messages', $messages);
		$this->set('emails', $rows);
		$this->set('query', $query);
		$this->layout = 'responsive2';
	}

	function sendemail()
	{
		$db =& ConnectionManager::getDataSource('default');
		//Get E-mail Settings
		$first_name = $_POST['first_name'];
		$last_name = $_POST['last_name'];
		$email = $_POST['email'];
		$to_address = $email; //$email; //"hsestacio13@gmail.com";
		$from_address = "info@acecare.ca";
		$from_name = "Management";
		$template = "
Dear {first_name} {last_name},

We did service your Furnace last year this time
This is just a reminder, It is time to get your appliance serviced again
We are there in next 2 weeks we can come and service it while we are there
you will also receive $20 off

Thanks
Management
		";
		$template_subject = "Service Due Reminder";

		$msg = $template;
		$msg = str_replace('{first_name}', $first_name, $msg);
		$msg = str_replace('{last_name}', $last_name, $msg);

		$res = mail($to_address, $template_subject, $msg, "From: ".$from_address);
		//$query = "UPDATE ace_rp_orders SET notified_booking = 1 WHERE id = ".$obj['id']."\n";
		echo $msg;
	}

	function displayinvoice() {
		$this->layout = 'blank';
	}

	function invoicetabletprint() {
		$this->layout = "blank";

		$order_id = $_GET['order_id'];
		//$order_id = 105817;

		$db =& ConnectionManager::getDataSource("default");

		$query = "
			SELECT *
			FROM ace_rp_settings
			WHERE id IN(21)
		";

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result)) {
			$use_template_questions = $row['valuetxt'];
		}

		$conditions = array();

		$conditions += array('`Order`.`id`' => $order_id);

		$allQuestions = array();
		$allStatuses = $this->Lists->ListTable('ace_rp_order_statuses');
		$allJobTypes = $this->Lists->ListTable('ace_rp_order_types');

		// UNCOMMENT ON LIVE
		$conditions += array('order_status_id' => array(1, 5, 8));

		//$orders = $this->Order->findAll($conditions, null, "job_truck ASC", null, null, 1);
		$orders = $this->Order->findAll($conditions, null, array("job_truck ASC", "job_time_beg ASC"), null, null, 1);

		// Customer's history for followup or complaints
		$num = 0;

		$notes = array();

		foreach ($orders as $obj)
		{
			if (($obj['Type']['id']==9)||($obj['Type']['id']==10))
			{
				$sRes = '';
				$order_id = $obj['Order']['id'];
				$phone = $obj['Customer']['phone'];

				$sRes .= '<table width=100% class="history">';
				$sRes .= '<tr>';
				$sRes .= '<th>Date</th><th>Booking</th><th>Status</th><th>Tech</th>';
				$sRes .= '</tr>';

				if ($phone)
				{

					$sq_str = preg_replace("/[- \.]/", "", $phone);
					$sq_str = preg_replace("/([?])*/", "[-]*", $phone);
					$past_orders = array();
					$query = "select * from ace_rp_orders where customer_phone regexp '$sq_str' order by job_date DESC";

					$result = $db->_execute($query);
					while($row = mysql_fetch_array($result))
						$past_orders[$row['id']] = $row['id'];

					foreach ($past_orders as $cur)
					{
						$p_order = $this->Order->findAll(array('Order.id'=> $cur), null, "job_date DESC", null, null, 1);
						$p_order = $p_order[0];
						if ($p_order['Order']['id'] == $order_id) continue;

						$items_text='';
						$total_booked=0;
						$total_extra=0;

						foreach ($p_order['BookingItem'] as $oi)
						{
							$str_sum = round($oi['quantity']*$oi['price']-$oi['discount']+$oi['addition'],2);
							if ($oi['class']==0)
							{
								$text = 'booked';
								$total_booked += 0+$str_sum;
							}
							else
							{
								$text = 'provided by tech';
								$total_extra += 0+$str_sum;
							}

							$items_text .= '<tr>';
							$items_text .= '<td>'.$text.'</td>';
							$items_text .= '<td>'.$oi['name'].'</td>';
							$items_text .= '<td>'.$oi['quantity'].'</td>';
							$items_text .= '<td>'.$this->HtmlAssist->prPrice($oi['price']).'</td>';
							$items_text .= '<td>'.$this->HtmlAssist->prPrice($oi['addition']-$oi['discount']).'</td>';
							$items_text .= '</tr>';
						}

						$sRes .= "<tr class='orderline' valign='top' ".$add." >";
						$sRes .= "<td rowspan=1>".date('d-m-Y', strtotime($p_order['Order']['job_date']))."<br>REF#".$p_order['Order']['order_number']."</td>";
						$sRes .= "<td rowspan=1>".$this->HtmlAssist->prPrice($total_booked)."</td>";
						$status = $p_order['Order']['order_status_id'];
						$color="";
						$sRes .= "<td><b>".$allStatuses[$status]."</b><br/>";
						$sRes .= $allJobTypes[$p_order['Order']['order_type_id']]."</td>";
						$sRes .= "<td>".$p_order['Technician1']['first_name']."<br/>"
								  .$p_order['Technician2']['first_name']."</td>";
						$sRes .= "</tr>\n";
						$sRes .= "<tr valign='top'>";
						$sRes .= "<td colspan=4 style='border-bottom: 1px solid #AAAAAA;'>";
						$sRes .= '<table>';
						$sRes .= '<tr><th style="width:100px !important;">&nbsp;</th>';
						$sRes .= '<th style="text-align:left;width:250px !important;">Item</th>';
						$sRes .= '<th style="text-align:left;width:80px !important;">Qty</th>';
						$sRes .= '<th style="text-align:left;width:100px !important;">Price</th>';
						$sRes .= '<th style="text-align:left;">Adj</th></tr>';
						$sRes .= $items_text;
						$sRes .= '</table>';
						$sRes .= "</td>";
						$sRes .= "</tr>\n";
					}

					$sRes .= "</table>";
				}

				$orders[$num]['Order']['history']  = $sRes;
			}
			$num++;

			$order_id = $obj['Order']['id'];
			$order_type_id = $obj['Order']['order_type_id'];

			if(isset($order_id) || $order_id != 0) {
				$query = "
					SELECT n.*, nt.name note_type_name,
						ur.name urgency_name,
						CONCAT(u.first_name, ' ', u.last_name) author_name,
						ur.image_file
					FROM ace_rp_notes n
					LEFT JOIN ace_rp_note_types nt
					ON n.note_type_id = nt.id
					LEFT JOIN ace_rp_urgencies ur
					ON n.urgency_id = ur.id
					LEFT JOIN ace_rp_users u
					ON n.user_id = u.id
					WHERE n.order_id = $order_id
					AND ur.id = 1
					ORDER BY n.note_date DESC
					LIMIT 2
				";


				$result = $db->_execute($query);

				$temp = "";
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$temp .= $row['message']."&laquo;";
				}

				$notes[$order_id] = $temp;

			} //END retrieve notes

			$result = $db->_execute($query);

			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$values[$row['question_id']]['answer_id'] = $row['answer_id'];
				$values[$row['question_id']]['answer_text'] = $row['answer_text'];
				$values[$row['question_id']]['question_text'] = $row['question_text'];
			}

			$this->set('order_id', $order_id);
			//$this->set('questions', $questions);
			//$this->set('answers', $answers);
			$this->set('values', $values);

			//END questions
			}

		$query = "
			SELECT id, CONCAT(raw, b) hk
			FROM (SELECT id,
				RIGHT(CONCAT('ABCDEFGH', id), 8) raw,
				HEX(RIGHT(id, 1)) b
				FROM ace_rp_customers) u
			WHERE raw IS NOT NULL
			ORDER BY id
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$history_keys[$row['id']]= $row['hk'];
    	}

		$query = "
			SELECT qw.question_id, q.rank, q.value question, r.value response, qw.response_text, s.value suggestion, d.value decision
			FROM ace_rp_orders_questions_working qw
			LEFT JOIN ace_rp_questions q
			ON qw.question_id = q.id
			LEFT JOIN ace_rp_responses r
			ON qw.response_id = r.id
			LEFT JOIN ace_rp_suggestions s
			ON qw.suggestion_id = s.id
			LEFT JOIN ace_rp_decisions d
			ON qw.decision_id = d.id
			WHERE q.for_print = 1
			AND qw.order_id = $order_id
			ORDER BY q.rank
		";

		$result = $db->_execute($query);

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$working_answers[$row['question_id']]['question'] = $row['question'];
			$working_answers[$row['question_id']]['response'] = $row['response'];
			$working_answers[$row['question_id']]['response_text'] = $row['response_text'];
			$working_answers[$row['question_id']]['suggestion'] = $row['suggestion'];
			$working_answers[$row['question_id']]['decision'] = $row['decision'];

		}

		$this->set('working_answers', $working_answers);

		$this->set('order_id', $order_id);
		//$this->set('questions', $questions);
		//$this->set('answers', $answers);
		$this->set('values', $values);

		$this->set('history_keys',$history_keys);
		$this->set('job_truck', $this->params['url']['job_truck']);
		$this->set('orders', $orders);
		$this->set('allSources', $this->Lists->BookingSources());
		$this->set('suppliers', $this->Lists->ListTable('ace_rp_suppliers','',array('name','address')));
		$this->set('job_trucks', $this->HtmlAssist->table2array($this->InventoryLocation->findAll(array('type' => '2'), null, null, null, 1, 0), 'id', 'name'));
		$this->set('techs', $this->Lists->Technicians());
		$this->set('payment_methods', $this->HtmlAssist->table2array($this->PaymentMethod->findAll(array(), null, null, null, 1, 0), 'id', 'name'));
	}

	function invoiceprint() {
		$this->layout = "blank";

		$order_id = $_GET['order_id'];
		//$order_id = 105817;

		$db =& ConnectionManager::getDataSource("default");

		$query = "
			SELECT *
			FROM ace_rp_settings
			WHERE id IN(21)
		";

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result)) {
			$use_template_questions = $row['valuetxt'];
		}

		$conditions = array();

		$conditions += array('`Order`.`id`' => $order_id);

		$allQuestions = array();
		$allStatuses = $this->Lists->ListTable('ace_rp_order_statuses');
		$allJobTypes = $this->Lists->ListTable('ace_rp_order_types');

		// UNCOMMENT ON LIVE
		$conditions += array('order_status_id' => array(1, 5, 8));

		//$orders = $this->Order->findAll($conditions, null, "job_truck ASC", null, null, 1);
		$orders = $this->Order->findAll($conditions, null, array("job_truck ASC", "job_time_beg ASC"), null, null, 1);

		// Customer's history for followup or complaints
		$num = 0;

		$notes = array();

		foreach ($orders as $obj)
		{
			if (($obj['Type']['id']==9)||($obj['Type']['id']==10))
			{
				$sRes = '';
				$order_id = $obj['Order']['id'];
				$phone = $obj['Customer']['phone'];

				$sRes .= '<table width=100% class="history">';
				$sRes .= '<tr>';
				$sRes .= '<th>Date</th><th>Booking</th><th>Status</th><th>Tech</th>';
				$sRes .= '</tr>';

				if ($phone)
				{

					$sq_str = preg_replace("/[- \.]/", "", $phone);
					$sq_str = preg_replace("/([?])*/", "[-]*", $phone);
					$past_orders = array();
					$query = "select * from ace_rp_orders where customer_phone regexp '$sq_str' order by job_date DESC";

					$result = $db->_execute($query);
					while($row = mysql_fetch_array($result))
						$past_orders[$row['id']] = $row['id'];

					foreach ($past_orders as $cur)
					{
						$p_order = $this->Order->findAll(array('Order.id'=> $cur), null, "job_date DESC", null, null, 1);
						$p_order = $p_order[0];
						if ($p_order['Order']['id'] == $order_id) continue;

						$items_text='';
						$total_booked=0;
						$total_extra=0;

						foreach ($p_order['BookingItem'] as $oi)
						{
							$str_sum = round($oi['quantity']*$oi['price']-$oi['discount']+$oi['addition'],2);
							if ($oi['class']==0)
							{
								$text = 'booked';
								$total_booked += 0+$str_sum;
							}
							else
							{
								$text = 'provided by tech';
								$total_extra += 0+$str_sum;
							}

							$items_text .= '<tr>';
							$items_text .= '<td>'.$text.'</td>';
							$items_text .= '<td>'.$oi['name'].'</td>';
							$items_text .= '<td>'.$oi['quantity'].'</td>';
							$items_text .= '<td>'.$this->HtmlAssist->prPrice($oi['price']).'</td>';
							$items_text .= '<td>'.$this->HtmlAssist->prPrice($oi['addition']-$oi['discount']).'</td>';
							$items_text .= '</tr>';
						}

						$sRes .= "<tr class='orderline' valign='top' ".$add." >";
						$sRes .= "<td rowspan=1>".date('d-m-Y', strtotime($p_order['Order']['job_date']))."<br>REF#".$p_order['Order']['order_number']."</td>";
						$sRes .= "<td rowspan=1>".$this->HtmlAssist->prPrice($total_booked)."</td>";
						$status = $p_order['Order']['order_status_id'];
						$color="";
						$sRes .= "<td><b>".$allStatuses[$status]."</b><br/>";
						$sRes .= $allJobTypes[$p_order['Order']['order_type_id']]."</td>";
						$sRes .= "<td>".$p_order['Technician1']['first_name']."<br/>"
								  .$p_order['Technician2']['first_name']."</td>";
						$sRes .= "</tr>\n";
						$sRes .= "<tr valign='top'>";
						$sRes .= "<td colspan=4 style='border-bottom: 1px solid #AAAAAA;'>";
						$sRes .= '<table>';
						$sRes .= '<tr><th style="width:100px !important;">&nbsp;</th>';
						$sRes .= '<th style="text-align:left;width:250px !important;">Item</th>';
						$sRes .= '<th style="text-align:left;width:80px !important;">Qty</th>';
						$sRes .= '<th style="text-align:left;width:100px !important;">Price</th>';
						$sRes .= '<th style="text-align:left;">Adj</th></tr>';
						$sRes .= $items_text;
						$sRes .= '</table>';
						$sRes .= "</td>";
						$sRes .= "</tr>\n";
					}

					$sRes .= "</table>";
				}

				$orders[$num]['Order']['history']  = $sRes;
			}
			$num++;

			$order_id = $obj['Order']['id'];
			$order_type_id = $obj['Order']['order_type_id'];

			if(isset($order_id) || $order_id != 0) {
				$query = "
					select * from ace_rp_notes where order_id = $order_id
					LIMIT 2
				";


				$result = $db->_execute($query);

				$temp = "";
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$temp[] = $row['message'];
				}

				$notes = $temp;

			} //END retrieve notes

			$result = $db->_execute($query);

			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$values[$row['question_id']]['answer_id'] = $row['answer_id'];
				$values[$row['question_id']]['answer_text'] = $row['answer_text'];
				$values[$row['question_id']]['question_text'] = $row['question_text'];
			}

			$this->set('order_id', $order_id);
			$this->set('notes', $notes);
			//$this->set('questions', $questions);
			//$this->set('answers', $answers);
			$this->set('values', $values);

			//END questions
			}

		$query = "
			SELECT id, CONCAT(raw, b) hk
			FROM (SELECT id,
				RIGHT(CONCAT('ABCDEFGH', id), 8) raw,
				HEX(RIGHT(id, 1)) b
				FROM ace_rp_customers) u
			WHERE raw IS NOT NULL
			ORDER BY id
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$history_keys[$row['id']]= $row['hk'];
    	}

		$this->set('order_id', $order_id);
		//$this->set('questions', $questions);
		//$this->set('answers', $answers);
		$this->set('values', $values);

		$this->set('history_keys',$history_keys);
		$this->set('job_truck', $this->params['url']['job_truck']);
		$this->set('orders', $orders);
		$this->set('allSources', $this->Lists->BookingSources());
		$this->set('suppliers', $this->Lists->ListTable('ace_rp_suppliers','',array('name','address')));
		$this->set('job_trucks', $this->HtmlAssist->table2array($this->InventoryLocation->findAll(array('type' => '2'), null, null, null, 1, 0), 'id', 'name'));
		$this->set('techs', $this->Lists->Technicians());
		$this->set('payment_methods', $this->HtmlAssist->table2array($this->PaymentMethod->findAll(array(), null, null, null, 1, 0), 'id', 'name'));
	}

	function invoiceprintblank() {
		$this->layout = "blank";

		$order_id = $_GET['order_id'];
		//$order_id = 105817;

		$db =& ConnectionManager::getDataSource("default");

		$query = "
			SELECT *
			FROM ace_rp_settings
			WHERE id IN(21)
		";

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result)) {
			$use_template_questions = $row['valuetxt'];
		}

		$conditions = array();

		$conditions += array('`Order`.`id`' => $order_id);

		$allQuestions = array();
		$allStatuses = $this->Lists->ListTable('ace_rp_order_statuses');
		$allJobTypes = $this->Lists->ListTable('ace_rp_order_types');

		// UNCOMMENT ON LIVE
		$conditions += array('order_status_id' => array(1, 5, 8));

		//$orders = $this->Order->findAll($conditions, null, "job_truck ASC", null, null, 1);
		$orders = $this->Order->findAll($conditions, null, array("job_truck ASC", "job_time_beg ASC"), null, null, 1);

		// Customer's history for followup or complaints
		$num = 0;

		$notes = array();

		foreach ($orders as $obj)
		{
			if (($obj['Type']['id']==9)||($obj['Type']['id']==10))
			{
				$sRes = '';
				$order_id = $obj['Order']['id'];
				$phone = $obj['Customer']['phone'];

				$sRes .= '<table width=100% class="history">';
				$sRes .= '<tr>';
				$sRes .= '<th>Date</th><th>Booking</th><th>Status</th><th>Tech</th>';
				$sRes .= '</tr>';

				if ($phone)
				{

					$sq_str = preg_replace("/[- \.]/", "", $phone);
					$sq_str = preg_replace("/([?])*/", "[-]*", $phone);
					$past_orders = array();
					$query = "select * from ace_rp_orders where customer_phone regexp '$sq_str' order by job_date DESC";

					$result = $db->_execute($query);
					while($row = mysql_fetch_array($result))
						$past_orders[$row['id']] = $row['id'];

					foreach ($past_orders as $cur)
					{
						$p_order = $this->Order->findAll(array('Order.id'=> $cur), null, "job_date DESC", null, null, 1);
						$p_order = $p_order[0];
						if ($p_order['Order']['id'] == $order_id) continue;

						$items_text='';
						$total_booked=0;
						$total_extra=0;

						foreach ($p_order['BookingItem'] as $oi)
						{
							$str_sum = round($oi['quantity']*$oi['price']-$oi['discount']+$oi['addition'],2);
							if ($oi['class']==0)
							{
								$text = 'booked';
								$total_booked += 0+$str_sum;
							}
							else
							{
								$text = 'provided by tech';
								$total_extra += 0+$str_sum;
							}

							$items_text .= '<tr>';
							$items_text .= '<td>'.$text.'</td>';
							$items_text .= '<td>'.$oi['name'].'</td>';
							$items_text .= '<td>'.$oi['quantity'].'</td>';
							$items_text .= '<td>'.$this->HtmlAssist->prPrice($oi['price']).'</td>';
							$items_text .= '<td>'.$this->HtmlAssist->prPrice($oi['addition']-$oi['discount']).'</td>';
							$items_text .= '</tr>';
						}

						$sRes .= "<tr class='orderline' valign='top' ".$add." >";
						$sRes .= "<td rowspan=1>".date('d-m-Y', strtotime($p_order['Order']['job_date']))."<br>REF#".$p_order['Order']['order_number']."</td>";
						$sRes .= "<td rowspan=1>".$this->HtmlAssist->prPrice($total_booked)."</td>";
						$status = $p_order['Order']['order_status_id'];
						$color="";
						$sRes .= "<td><b>".$allStatuses[$status]."</b><br/>";
						$sRes .= $allJobTypes[$p_order['Order']['order_type_id']]."</td>";
						$sRes .= "<td>".$p_order['Technician1']['first_name']."<br/>"
								  .$p_order['Technician2']['first_name']."</td>";
						$sRes .= "</tr>\n";
						$sRes .= "<tr valign='top'>";
						$sRes .= "<td colspan=4 style='border-bottom: 1px solid #AAAAAA;'>";
						$sRes .= '<table>';
						$sRes .= '<tr><th style="width:100px !important;">&nbsp;</th>';
						$sRes .= '<th style="text-align:left;width:250px !important;">Item</th>';
						$sRes .= '<th style="text-align:left;width:80px !important;">Qty</th>';
						$sRes .= '<th style="text-align:left;width:100px !important;">Price</th>';
						$sRes .= '<th style="text-align:left;">Adj</th></tr>';
						$sRes .= $items_text;
						$sRes .= '</table>';
						$sRes .= "</td>";
						$sRes .= "</tr>\n";
					}

					$sRes .= "</table>";
				}

				$orders[$num]['Order']['history']  = $sRes;
			}
			$num++;

			$order_id = $obj['Order']['id'];
			$order_type_id = $obj['Order']['order_type_id'];

			if(isset($order_id) || $order_id != 0) {
				$query = "
					SELECT n.*, nt.name note_type_name,
						ur.name urgency_name,
						CONCAT(u.first_name, ' ', u.last_name) author_name,
						ur.image_file
					FROM ace_rp_notes n
					LEFT JOIN ace_rp_note_types nt
					ON n.note_type_id = nt.id
					LEFT JOIN ace_rp_urgencies ur
					ON n.urgency_id = ur.id
					LEFT JOIN ace_rp_users u
					ON n.user_id = u.id
					WHERE n.order_id = $order_id
					AND ur.id = 1
					ORDER BY n.note_date DESC
					LIMIT 2
				";


				$result = $db->_execute($query);

				$temp = "";
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$temp .= $row['message']."&laquo;";
				}

				$notes[$order_id] = $temp;

			} //END retrieve notes

			$result = $db->_execute($query);



			$this->set('order_id', $order_id);
			//$this->set('questions', $questions);
			//$this->set('answers', $answers);
			$this->set('values', array());

			//END questions
			}

		$query = "
			SELECT id, CONCAT(raw, b) hk
			FROM (SELECT id,
				RIGHT(CONCAT('ABCDEFGH', id), 8) raw,
				HEX(RIGHT(id, 1)) b
				FROM ace_rp_customers) u
			WHERE raw IS NOT NULL
			ORDER BY id
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$history_keys[$row['id']]= $row['hk'];
    	}

		$this->set('order_id', $order_id);
		//$this->set('questions', $questions);
		//$this->set('answers', $answers);
		$this->set('values', $values);

		$this->set('history_keys',$history_keys);
		$this->set('job_truck', $this->params['url']['job_truck']);
		$this->set('orders', $orders);
		$this->set('allSources', $this->Lists->BookingSources());
		$this->set('suppliers', $this->Lists->ListTable('ace_rp_suppliers','',array('name','address')));
		$this->set('job_trucks', $this->HtmlAssist->table2array($this->InventoryLocation->findAll(array('type' => '2'), null, null, null, 1, 0), 'id', 'name'));
		$this->set('techs', $this->Lists->Technicians());
		$this->set('payment_methods', $this->HtmlAssist->table2array($this->PaymentMethod->findAll(array(), null, null, null, 1, 0), 'id', 'name'));
	}

	function invoiceemail() {
		$this->layout = "blank";

		$order_id = $_GET['order_id'];
		//$order_id = 105817;

		$db =& ConnectionManager::getDataSource("default");

		$query = "
			SELECT *
			FROM ace_rp_settings
			WHERE id IN(21)
		";

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result)) {
			$use_template_questions = $row['valuetxt'];
		}

		$conditions = array();

		$conditions += array('`Order`.`id`' => $order_id);

		$allQuestions = array();
		$allStatuses = $this->Lists->ListTable('ace_rp_order_statuses');
		$allJobTypes = $this->Lists->ListTable('ace_rp_order_types');

		// UNCOMMENT ON LIVE
		$conditions += array('order_status_id' => array(1, 5, 8));

		//$orders = $this->Order->findAll($conditions, null, "job_truck ASC", null, null, 1);
		$orders = $this->Order->findAll($conditions, null, array("job_truck ASC", "job_time_beg ASC"), null, null, 1);

		// Customer's history for followup or complaints
		$num = 0;

		$notes = array();

		foreach ($orders as $obj)
		{
			if (($obj['Type']['id']==9)||($obj['Type']['id']==10))
			{
				$sRes = '';
				$order_id = $obj['Order']['id'];
				$phone = $obj['Customer']['phone'];

				$sRes .= '<table width=100% class="history">';
				$sRes .= '<tr>';
				$sRes .= '<th>Date</th><th>Booking</th><th>Status</th><th>Tech</th>';
				$sRes .= '</tr>';

				if ($phone)
				{

					$sq_str = preg_replace("/[- \.]/", "", $phone);
					$sq_str = preg_replace("/([?])*/", "[-]*", $phone);
					$past_orders = array();
					$query = "select * from ace_rp_orders where customer_phone regexp '$sq_str' order by job_date DESC";

					$result = $db->_execute($query);
					while($row = mysql_fetch_array($result))
						$past_orders[$row['id']] = $row['id'];

					foreach ($past_orders as $cur)
					{
						$p_order = $this->Order->findAll(array('Order.id'=> $cur), null, "job_date DESC", null, null, 1);
						$p_order = $p_order[0];
						if ($p_order['Order']['id'] == $order_id) continue;

						$items_text='';
						$total_booked=0;
						$total_extra=0;

						foreach ($p_order['BookingItem'] as $oi)
						{
							$str_sum = round($oi['quantity']*$oi['price']-$oi['discount']+$oi['addition'],2);
							if ($oi['class']==0)
							{
								$text = 'booked';
								$total_booked += 0+$str_sum;
							}
							else
							{
								$text = 'provided by tech';
								$total_extra += 0+$str_sum;
							}

							$items_text .= '<tr>';
							$items_text .= '<td>'.$text.'</td>';
							$items_text .= '<td>'.$oi['name'].'</td>';
							$items_text .= '<td>'.$oi['quantity'].'</td>';
							$items_text .= '<td>'.$this->HtmlAssist->prPrice($oi['price']).'</td>';
							$items_text .= '<td>'.$this->HtmlAssist->prPrice($oi['addition']-$oi['discount']).'</td>';
							$items_text .= '</tr>';
						}

						$sRes .= "<tr class='orderline' valign='top' ".$add." >";
						$sRes .= "<td rowspan=1>".date('d-m-Y', strtotime($p_order['Order']['job_date']))."<br>REF#".$p_order['Order']['order_number']."</td>";
						$sRes .= "<td rowspan=1>".$this->HtmlAssist->prPrice($total_booked)."</td>";
						$status = $p_order['Order']['order_status_id'];
						$color="";
						$sRes .= "<td><b>".$allStatuses[$status]."</b><br/>";
						$sRes .= $allJobTypes[$p_order['Order']['order_type_id']]."</td>";
						$sRes .= "<td>".$p_order['Technician1']['first_name']."<br/>"
								  .$p_order['Technician2']['first_name']."</td>";
						$sRes .= "</tr>\n";
						$sRes .= "<tr valign='top'>";
						$sRes .= "<td colspan=4 style='border-bottom: 1px solid #AAAAAA;'>";
						$sRes .= '<table>';
						$sRes .= '<tr><th style="width:100px !important;">&nbsp;</th>';
						$sRes .= '<th style="text-align:left;width:250px !important;">Item</th>';
						$sRes .= '<th style="text-align:left;width:80px !important;">Qty</th>';
						$sRes .= '<th style="text-align:left;width:100px !important;">Price</th>';
						$sRes .= '<th style="text-align:left;">Adj</th></tr>';
						$sRes .= $items_text;
						$sRes .= '</table>';
						$sRes .= "</td>";
						$sRes .= "</tr>\n";
					}

					$sRes .= "</table>";
				}

				$orders[$num]['Order']['history']  = $sRes;
			}
			$num++;

			$order_id = $obj['Order']['id'];
			$order_type_id = $obj['Order']['order_type_id'];

			if(isset($order_id) || $order_id != 0) {
				$query = "
					SELECT n.*, nt.name note_type_name,
						ur.name urgency_name,
						CONCAT(u.first_name, ' ', u.last_name) author_name,
						ur.image_file
					FROM ace_rp_notes n
					LEFT JOIN ace_rp_note_types nt
					ON n.note_type_id = nt.id
					LEFT JOIN ace_rp_urgencies ur
					ON n.urgency_id = ur.id
					LEFT JOIN ace_rp_users u
					ON n.user_id = u.id
					WHERE n.order_id = $order_id
					AND ur.id = 1
					ORDER BY n.note_date DESC
					LIMIT 2
				";


				$result = $db->_execute($query);

				$temp = "";
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$temp .= $row['message']."&laquo;";
				}

				$notes[$order_id] = $temp;

			} //END retrieve notes

			$result = $db->_execute($query);

			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$values[$row['question_id']]['answer_id'] = $row['answer_id'];
				$values[$row['question_id']]['answer_text'] = $row['answer_text'];
				$values[$row['question_id']]['question_text'] = $row['question_text'];
			}

			$this->set('order_id', $order_id);
			//$this->set('questions', $questions);
			//$this->set('answers', $answers);
			$this->set('values', $values);

			//END questions
			}

		$query = "
			SELECT id, CONCAT(raw, b) hk
			FROM (SELECT id,
				RIGHT(CONCAT('ABCDEFGH', id), 8) raw,
				HEX(RIGHT(id, 1)) b
				FROM ace_rp_customers) u
			WHERE raw IS NOT NULL
			ORDER BY id
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$history_keys[$row['id']]= $row['hk'];
    	}

		$this->set('order_id', $order_id);
		//$this->set('questions', $questions);
		//$this->set('answers', $answers);
		$this->set('values', $values);

		$this->set('history_keys',$history_keys);
		$this->set('job_truck', $this->params['url']['job_truck']);
		$this->set('orders', $orders);
		$this->set('allSources', $this->Lists->BookingSources());
		$this->set('suppliers', $this->Lists->ListTable('ace_rp_suppliers','',array('name','address')));
		$this->set('job_trucks', $this->HtmlAssist->table2array($this->InventoryLocation->findAll(array('type' => '2'), null, null, null, 1, 0), 'id', 'name'));
		$this->set('techs', $this->Lists->Technicians());
		$this->set('payment_methods', $this->HtmlAssist->table2array($this->PaymentMethod->findAll(array(), null, null, null, 1, 0), 'id', 'name'));
	}

	function lastyearlist()
	{
		$db =& ConnectionManager::getDataSource('default');

		$year = $_POST['load_year'];
		$month = $_POST['load_month'];
		if(isset($_POST['load_year'])) {
			$this->set('message', $_POST['load_year']." has been loaded.");

			$query = "
				INSERT INTO n_calls(call_number, call_date, call_status, user_id)
				SELECT customer_phone, job_date, 10, booking_telemarketer_id
				FROM ace_rp_orders o
				WHERE o.order_status_id = 5
				AND DATE_FORMAT(o.job_date, '%Y') = $year
				AND DATE_FORMAT(o.job_date, '%c') = $month
				AND o.customer_phone NOT IN(
					SELECT c.customer_phone
					FROM ace_rp_orders c
					WHERE c.order_status_id = 5
					AND $year = ".date("Y")."
				)
			";
			$results = $db->_execute($query);
		}

		$query = "
			SELECT
				DATE_FORMAT(job_date,'%b %Y') month_year,
				COUNT(customer_id) jobs,
				DATE_FORMAT(job_date,'%c') month,
				DATE_FORMAT(job_date,'%Y') year
			FROM ace_rp_orders
			WHERE order_status_id = 5
			AND job_date > DATE_SUB(CURDATE(),INTERVAL 17 MONTH)
			AND job_date < DATE_SUB(CURDATE(),INTERVAL 11 MONTH)
			GROUP BY DATE_FORMAT(job_date,'%b')
			ORDER BY job_date
		";
		$results = $db->_execute($query);

		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
		}

		$this->set('lists', $rows);
		$this->layout = 'responsive2';

	}

	function invoicepurchase() {

		if($_POST['btn-filter']) {
			$date_range = str_replace(" - ", " AND ", $_POST['date_range']);
			$date_range = str_replace("\\", "", $date_range);
			$date_range = substr_replace(str_replace("' AND ", " 00:00:00' AND ", $date_range), " 23:59:59'",-1);
			$this->set('date_range', str_replace("\\", "", $_POST['date_range']));
		} else {
			$date_range = "'".date("Y-m-d")."' AND '".date("Y-m-d")."'";
			$date_range = "'".date("Y-m-d 00:00:00")."' AND '".date("Y-m-d 23:59:59")."'";
			$this->set('date_range', "'".date("Y-m-d")."' - '".date("Y-m-d")."'");
		}

		$db =& ConnectionManager::getDataSource('default');
		$query = "
			SELECT oi.invoice, SUM(oi.price) AS price, SUM(oi.price_purchase) AS price_purchase, o.order_number, o.id AS order_id
			FROM ace_rp_order_items oi
			LEFT JOIN ace_rp_orders o
			ON oi.order_id = o.id
			WHERE oi.item_id = 1227
			AND o.job_date BETWEEN $date_range
			GROUP BY oi.invoice
		";
		$query = "
			SELECT oi.invoice, SUM(oi.price) AS price, SUM(oi.price_purchase) AS price_purchase, o.order_number, o.id AS order_id
			FROM ace_rp_order_items oi
			LEFT JOIN ace_rp_orders o
			ON oi.order_id = o.id
			WHERE oi.item_id = 1227
			GROUP BY oi.invoice
		";

		$results = $db->_execute($query);

		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
		}

		$this->set('query', $query);
		$this->set('invoices', $rows);
		$this->layout = 'responsive2';
	}

	function invoicepurchaseform2() {
		$invoice = $_GET['i'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "
			SELECT *
			FROM ace_rp_order_items
			WHERE invoice = '$invoice'
		";
		$results = $db->_execute($query);

		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
		}

		$this->set('invoices', $rows);
		$this->layout = 'responsive2';
	}

	function inventorypurchases() {
		if($_POST['btn-filter']) {
			$date_range = str_replace(" - ", " AND ", $_POST['date_range']);
			$date_range = str_replace("\\", "", $date_range);
			$date_range = substr_replace(str_replace("' AND ", " 00:00:00' AND ", $date_range), " 23:59:59'",-1);
			$supplier_name = $_POST['supplier_name'];
			$this->set('date_range', str_replace("\\", "", $_POST['date_range']));
			$this->set('supplier_name', $supplier_name);
		} else {
			$date_range = "'".date("Y-m-d")."' AND '".date("Y-m-d")."'";
			$date_range = "'".date("Y-m-d 00:00:00")."' AND '".date("Y-m-d 23:59:59")."'";
			$this->set('date_range', "'".date("Y-m-d")."' - '".date("Y-m-d")."'");
			$supplier_name = "";
		}

		$invoice = 123;
		$db =& ConnectionManager::getDataSource('default');
		if(trim($supplier_name) != "") {
			$query = "
				SELECT p.*, IFNULL(s.name, '') supplier_name
				FROM n_purchases p
				LEFT JOIN ace_rp_suppliers s
				ON p.supplier_id = s.id
				WHERE p.purchase_date BETWEEN $date_range
				AND s.name LIKE '%$supplier_name%'
			";
		} else {
			$query = "
				SELECT p.*, IFNULL(s.name, '') supplier_name
				FROM n_purchases p
				LEFT JOIN ace_rp_suppliers s
				ON p.supplier_id = s.id
				WHERE p.purchase_date BETWEEN $date_range
			";
		}
		$results = $db->_execute($query);

		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
			$rows_types[$temp['purchase_type']][] = $temp;
		}
		$this->set('purchases', $rows);
		$this->set('purchases_types', $rows_types);

		if(trim($supplier_name) != "") {
			$query = "
				SELECT p.purchase_type, pt.purchase_type_name, SUM(IFNULL(p.purchase_cost, 0)) purchase_sum, pt.purchase_debit
				FROM n_purchases p
				LEFT JOIN ace_rp_suppliers s
				ON p.supplier_id = s.id
				LEFT JOIN n_purchase_types pt
				ON p.purchase_type = pt.purchase_type_id
				WHERE p.purchase_date BETWEEN $date_range
				AND s.name LIKE '%$supplier_name%'
				GROUP BY p.purchase_type
			";
		} else {
			$query = "
				SELECT p.purchase_type, pt.purchase_type_name, SUM(IFNULL(p.purchase_cost, 0)) purchase_sum, pt.purchase_debit
				FROM n_purchases p
				LEFT JOIN ace_rp_suppliers s
				ON p.supplier_id = s.id
				LEFT JOIN n_purchase_types pt
				ON p.purchase_type = pt.purchase_type_id
				WHERE p.purchase_date BETWEEN $date_range
				GROUP BY p.purchase_type
			";
		}
		$results = $db->_execute($query);

		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$summaries[] = $temp;
		}
		$this->set('summaries', $summaries);

		$this->layout = 'responsive2';
	}

	function inventoryitems() {
		$sku = "LIMIT 10";
		if(isset($_POST['sku_search']) && trim($_POST['sku_search']) != "") {
			$sku_search = $_POST['sku_search'];
			if(strpos($sku_search,'-')) {
				$sku = "WHERE item_sku BETWEEN ". str_replace("-"," AND ",$sku_search);
			}
			$sku = "WHERE item_sku = '". $_POST['sku_search']."'";
		}
		$db =& ConnectionManager::getDataSource('default');
		$query = "
			SELECT *
			FROM n_items
			$sku
		";
		$results = $db->_execute($query);

		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
		}

		$this->set('sku_search', $_POST['sku_search']);
		$this->set('items', $rows);
		$this->set('query', $query);
		$this->layout = 'responsive2';
	}

	function inventoryitem() {
		$message = "";
		$id = $_GET['i'];
		$db =& ConnectionManager::getDataSource('default');
		if(isset($_POST['btn-save']) && $_POST['btn-save'] == 1) {

			if($id > 0) {
				$query = "
					UPDATE n_items SET
					item_sku = '".$_POST['item_sku']."',
					item_name = '".$_POST['item_name']."',
					supplier_id = '".$_POST['supplier_id']."',
					item_price = '".$_POST['item_price']."',
					item_cost = '".$_POST['item_cost']."'
					WHERE item_id = $id
				";
				$db->_execute($query);
				$message = "Item Updated";
			} else {
				$query = "
					INSERT INTO n_items SET
					item_sku = '".$_POST['item_sku']."',
					item_name = '".$_POST['item_name']."',
					supplier_id = '".$_POST['supplier_id']."',
					item_price = '".$_POST['item_price']."',
					item_cost = '".$_POST['item_cost']."'
				";
				$db->_execute($query);

				$result = $db->_execute("SELECT LAST_INSERT_ID() AS last_id");
				$id = 0;

				while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					foreach ($row as $k => $v)
						$id = $v;
				}

				$message = "Item Saved";
			}
		}

		$rows = array();


		$query = "
			SELECT * FROM n_items
			WHERE item_id = $id
		";
		$results = $db->_execute($query);
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
		}

		$query = "
			SELECT * FROM ace_rp_suppliers
			WHERE  flagactive = 1
		";
		$results = $db->_execute($query);
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$suppliers[] = $temp;
		}


		$this->set('message', $message);
		$this->set('suppliers', $suppliers);
		$this->set('item', $rows[0]);
		$this->set('query', $query);
		$this->layout = 'responsive2';
	}

	function invoicepurchaseform() {
		$message = "";
		$id = $_GET['i'];
		$db =& ConnectionManager::getDataSource('default');
		if(isset($_POST['btn-save']) && $_POST['btn-save'] == 1) {
			if($id > 0) {
				$query = "
					UPDATE n_purchases SET
					purchase_invoice = '".$_POST['purchase_invoice']."',
					purchase_date = '".$_POST['purchase_date']."',
					purchase_type = '".$_POST['purchase_type']."',
					purchase_cost = '".$_POST['purchase_cost']."',
					purchase_tax = '".$_POST['purchase_tax']."',
					purchase_cheque = '".$_POST['purchase_cheque']."',
					supplier_id = '".$_POST['supplier_id']."',
					related_invoice = '".$_POST['purchase_invoice']."'
					WHERE purchase_id = $id
				";
				$db->_execute($query);

				$db->_execute("
					DELETE FROM n_purchase_items WHERE purchase_id = $id;
				");

				foreach($_POST['items'] as $item) {
					$query = "
						INSERT INTO n_purchase_items SET
						purchase_id = 		$id,
						item_sku = 			'".$item['item_sku']."',

						item_description = 	'".$item['item_description']."',
						item_cost = 		'".$item['item_cost']."',
						item_price = 		'".$item['item_price']."',
						item_quantity = 	'".$item['item_quantity']."',
                                                item_location = 	'".$item['item_location']."'
					";
				$db->_execute($query);
				}

				$message = "Item Updated";
			} else {
				$query = "
					INSERT INTO n_purchases SET
					purchase_invoice = '".$_POST['purchase_invoice']."',
					purchase_date = '".$_POST['purchase_date']."',
					purchase_type = '".$_POST['purchase_type']."',
					purchase_cost = '".$_POST['purchase_cost']."',
					purchase_tax = '".$_POST['purchase_tax']."',
					purchase_cheque = '".$_POST['purchase_cheque']."',
					related_invoice = '".$_POST['purchase_invoice']."',
					supplier_id = '".$_POST['supplier_id']."'
				";
				$db->_execute($query);

				$result = $db->_execute("SELECT LAST_INSERT_ID() AS last_id");
				$id = 0;
				while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$id = $row['last_id'];
				}

				foreach($_POST['items'] as $item) {
					$query = "
						INSERT INTO n_purchase_items SET
						purchase_id = 		$id,
						item_sku = 			'".$item['item_sku']."',

						item_description = 	'".$item['item_description']."',
						item_cost = 		'".$item['item_cost']."',
						item_price = 		'".$item['item_price']."',
						item_quantity = 	'".$item['item_quantity']."',
                                                item_location = 	'".$item['item_location']."'
					";
				//location_id = 		'".$item['location_id']."',
				$db->_execute($query);
				}

				$message = "Saved";
			}
			$this->_redirect('calls/invoicepurchaseform?i='.$id);

		}

		$rows = array();



		$query = "
			SELECT * FROM ace_rp_suppliers
			WHERE  flagactive = 1
		";
		$results = $db->_execute($query);
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$suppliers[] = $temp;
		}

		$query = "
			SELECT i.*, s.name AS supplier_name, c.name AS category_name
			FROM ace_iv_items i
			LEFT JOIN ace_iv_suppliers s
			ON i.iv_supplier_id = s.id
			LEFT JOIN ace_iv_categories c
			ON i.iv_category_id = c.id
			WHERE i.active = 1
		";
		$results = $db->_execute($query);
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$items[] = $temp;
		}

		if($id > 0) {
			$query = "
				SELECT * FROM n_purchases
				WHERE purchase_id = $id
			";
			$results = $db->_execute($query);
			while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
				foreach ($row as $field => $val) {
					$temp[$field] = $val;
				}
				$purchase = $temp;
			}

			$query = "
				SELECT * FROM n_purchase_items
				WHERE purchase_id = $id
			";
			$results = $db->_execute($query);
			while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
				foreach ($row as $field => $val) {
					$temp[$field] = $val;
				}
				$purchase_items[] = $temp;
			}
		}
                
                $allTechnicians = $this->Lists->Technicians();
                $this->set('allTechnician', $allTechnicians);                
                
		$this->set('items', $items);
		$this->set('message', $message);
		$this->set('suppliers', $suppliers);
		$this->set('purchase', $purchase);
		$this->set('purchase_items', $purchase_items);
		$this->set('query', $query);
		$this->layout = 'responsive2';
	}

	function invoicetransferform() {
		$message = "";
		$id = $_GET['i'];
		$db =& ConnectionManager::getDataSource('default');
		if(isset($_POST['btn-save']) && $_POST['btn-save'] == 1) {
			if($id > 0) {
				$query = "
					UPDATE n_purchases SET
					purchase_invoice = '".$_POST['purchase_invoice']."',
					purchase_date = '".$_POST['purchase_date']."',
					purchase_type = '".$_POST['purchase_type']."',
					purchase_cost = '".$_POST['purchase_cost']."',
					supplier_id = '0'
					WHERE purchase_id = $id
				";
				$db->_execute($query);

				$db->_execute("
					DELETE FROM n_purchase_items WHERE purchase_id = $id;
				");

				foreach($_POST['items'] as $item) {
					$query = "
						INSERT INTO n_purchase_items SET
						purchase_id = 		$id,
						item_sku = 			'".$item['item_sku']."',
						location_id = 		'".$item['from_location_id']."',
						item_description = 	'".$item['item_description']."',
						item_cost = 		'".$item['item_cost']."',
						item_price = 		'".$item['item_price']."',
						item_quantity = 	'".($item['item_quantity']*-1)."'
					";
					$db->_execute($query);
					$query = "
						INSERT INTO n_purchase_items SET
						purchase_id = 		$id,
						item_sku = 			'".$item['item_sku']."',
						location_id = 		'".$item['to_location_id']."',
						item_description = 	'".$item['item_description']."',
						item_cost = 		'".$item['item_cost']."',
						item_price = 		'".$item['item_price']."',
						item_quantity = 	'".$item['item_quantity']."'
					";
					$db->_execute($query);
				}

				$message = "Item Transfered";
			} else {
				$query = "
					INSERT INTO n_purchases SET
					purchase_invoice = '".$_POST['purchase_invoice']."',
					purchase_date = '".$_POST['purchase_date']."',
					purchase_type = '".$_POST['purchase_type']."',
					purchase_cost = '".$_POST['purchase_cost']."',
					supplier_id = '".$_POST['supplier_id']."'
				";
				$db->_execute($query);

				$result = $db->_execute("SELECT LAST_INSERT_ID() AS last_id");
				$id = 0;
				while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$id = $row['last_id'];
				}

				foreach($_POST['items'] as $item) {
					$query = "
						INSERT INTO n_purchase_items SET
						purchase_id = 		$id,
						item_sku = 			'".$item['item_sku']."',
						location_id = 		'".$item['from_location_id']."',
						item_description = 	'".$item['item_description']."',
						item_cost = 		'".$item['item_cost']."',
						item_price = 		'".$item['item_price']."',
						item_quantity = 	'".($item['item_quantity']*-1)."'
					";
					$db->_execute($query);
					$query = "
						INSERT INTO n_purchase_items SET
						purchase_id = 		$id,
						item_sku = 			'".$item['item_sku']."',
						location_id = 		'".$item['to_location_id']."',
						item_description = 	'".$item['item_description']."',
						item_cost = 		'".$item['item_cost']."',
						item_price = 		'".$item['item_price']."',
						item_quantity = 	'".$item['item_quantity']."'
					";
					$db->_execute($query);
				}

				$message = "Saved";
			}
			$this->_redirect('calls/invoicetransferform?i='.$id);

		}

		$rows = array();



		$query = "
			SELECT * FROM ace_rp_suppliers
			WHERE  flagactive = 1
		";
		$results = $db->_execute($query);
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$suppliers[] = $temp;
		}

		$query = "
			SELECT i.*, s.name AS supplier_name, c.name AS category_name
			FROM ace_iv_items i
			LEFT JOIN ace_iv_suppliers s
			ON i.iv_supplier_id = s.id
			LEFT JOIN ace_iv_categories c
			ON i.iv_category_id = c.id
			WHERE i.active = 1
		";
		$results = $db->_execute($query);
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$items[] = $temp;
		}

		if($id > 0) {
			$query = "
				SELECT * FROM n_purchases
				WHERE purchase_id = $id
			";
			$results = $db->_execute($query);
			while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
				foreach ($row as $field => $val) {
					$temp[$field] = $val;
				}
				$purchase = $temp;
			}

			$query = "
				SELECT p2.*, IF(p1.location_id=0,'Warehouse', il1.name) AS location_from, IF(p2.location_id=0,'Warehouse', il2.name) AS location_to
				FROM (SELECT pf.* FROM n_purchase_items pf WHERE item_quantity < 0 AND pf.purchase_id = $id) p1
				LEFT JOIN (SELECT pf.* FROM n_purchase_items pf WHERE item_quantity >= 0 AND pf.purchase_id = $id) p2
				ON p1.item_sku = p2.item_sku
				LEFT JOIN ace_rp_inventory_locations il1
				ON p1.location_id = il1.id
				LEFT JOIN ace_rp_inventory_locations il2
				ON p2.location_id = il2.id
				WHERE p1.purchase_id = $id
			";
			$results = $db->_execute($query);
			while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
				foreach ($row as $field => $val) {
					$temp[$field] = $val;
				}
				$purchase_items[] = $temp;
			}
		}

		$this->set('items', $items);
		$this->set('message', $message);
		$this->set('suppliers', $suppliers);
		$this->set('purchase', $purchase);
		$this->set('purchase_items', $purchase_items);
		$this->set('query', $query);
		$this->layout = 'responsive2';
	}

	function invoicepaymentform() {
		$message = "";
		$id = $_GET['i'];
		$db =& ConnectionManager::getDataSource('default');
		if(isset($_POST['btn-save']) && $_POST['btn-save'] == 1) {
			if($id > 0) {
				$query = "
					UPDATE n_purchases SET
					purchase_invoice = '".$_POST['purchase_invoice']."',
					purchase_date = '".$_POST['purchase_date']."',
					purchase_type = '".$_POST['purchase_type']."',
					purchase_cost = '".$_POST['purchase_cost']."',
					supplier_id = '".$_POST['supplier_id']."'
					WHERE purchase_id = $id
				";
				$db->_execute($query);

				$message = "Item Updated";
			} else {
				$query = "
					INSERT INTO n_purchases SET
					purchase_invoice = '".$_POST['purchase_invoice']."',
					purchase_date = '".$_POST['purchase_date']."',
					purchase_type = '".$_POST['purchase_type']."',
					purchase_cost = '".$_POST['purchase_cost']."',
					supplier_id = '".$_POST['supplier_id']."'
				";
				$db->_execute($query);

				$result = $db->_execute("SELECT LAST_INSERT_ID() AS last_id");
				$id = 0;
				while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$id = $row['last_id'];
				}

				$message = "Saved";
			}
			$this->_redirect('calls/invoicepurchaseform?i='.$id);
		}

		$this->set('message', $message);
		$this->set('form', $purchase);
		$this->set('query', $query);
		$this->layout = 'responsive2';
	}

	function callbacksearch() {
		$user_id = $_SESSION['user']['id'];
		$db =& ConnectionManager::getDataSource('default');

		if($user_id == 206767 || $user_id == 44851 || $user_id == 226792) {
			$telem = "";
		} else {
			$telem = "AND h.callback_user_id = $user_id";
		}

		if($_POST['btn-filter']) {
			$date_range = str_replace(" - ", " AND ", $_POST['date_range']);
			$date_range = str_replace("\\", "", $date_range);
			$date_range = substr_replace(str_replace("' AND ", " 00:00:00' AND ", $date_range), " 23:59:59'",-1);
			$this->set('date_range', str_replace("\\", "", $_POST['date_range']));
		} else {
			$date_range = "'".date("Y-m-d")."' AND '".date("Y-m-d")."'";
			$date_range = "'".date("Y-m-d 00:00:00")."' AND '".date("Y-m-d 23:59:59")."'";
			$this->set('date_range', "'".date("Y-m-d")."' - '".date("Y-m-d")."'");
		}

		$query = "
			SELECT distinct
				c.id, c.card_number, c.first_name, c.last_name,
				c.postal_code, c.email, c.address_unit, c.address_street_number, c.address_street, c.city,
				c.phone, c.cell_phone,
				h.call_user_id, h.call_note, h.call_result_id,
				h.callback_date, h.callback_time,
				if((h.callback_date=current_date())&&(TIME_TO_SEC(CAST(now() AS TIME))>= TIME_TO_SEC(CAST(h.callback_time AS TIME))-300),1,0) reminder_flag,
				h.call_date, u.first_name as telemarketer_first_name, u.last_name as telemarketer_last_name
			FROM ace_rp_customers AS c
				join ace_rp_call_history h
				left join ace_rp_users u on u.id=h.call_user_id
			WHERE
				c.id=h.customer_id and
				(h.call_result_id in (0,1,2,4) or h.call_result_id is null)
				AND h.callback_date BETWEEN $date_range
				AND h.call_date <= h.callback_date
				$telem
				and not exists
					(
					select * from ace_rp_call_history y
					where y.customer_id=h.customer_id
					and (y.call_date>h.call_date
					or y.call_date=h.call_date and y.call_time>h.call_time)
					)
			order by reminder_flag desc, h.callback_date, h.callback_time asc
		";

		$results = $db->_execute($query);

		$rows = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[$temp['call_tab']][] = $temp;
		}


		$this->set('callbacks', $rows);
		$this->layout = 'responsive2';
	}

	function search() {
		$user_id = $_SESSION['user']['id'];
		$db =& ConnectionManager::getDataSource('default');
		if($_POST['btn-filter']) {
			unset($_SESSION['filter']);
			$date_range = str_replace(" - ", " AND ", $_POST['date_range']);
			$date_range = str_replace("\\", "", $date_range);
			$date_range = substr_replace(str_replace("' AND ", " 00:00:00' AND ", $date_range), " 23:59:59'",-1);
			$this->set('date_range', str_replace("\\", "", $_POST['date_range']));
			$_SESSION['filter']['date_range'] = str_replace("\\", "", $_POST['date_range']);

			$search_term = trim($_POST['search_term']);
			$this->set('search_term', $search_term);
			$_SESSION['filter']['search_term'] = $search_term;

			if($_POST['order_status_id'] > 0) $order_status_id = "AND order_status_id = ".$_POST['order_status_id']; else $order_status_id = "";
			$this->set('order_status_id', $_POST['order_status_id']);
			$_SESSION['filter']['order_status_id'] = $_POST['order_status_id'];

			if($_POST['job_type'] > 0) $job_type = "AND order_type_id = ".$_POST['job_type']; else $job_type = "";
			$this->set('job_type', $_POST['job_type']);
			$_SESSION['filter']['job_type'] = $_POST['job_type'];
		} else {
			if(isset($_SESSION['filter']['search_term'])) {
				$search_term = trim($_SESSION['filter']['search_term']);
				$this->set('search_term', $search_term);
			} else {
				$search_term = "";
			}

			if($_SESSION['filter']['order_status_id'] > 0) {
				$order_status_id = "AND order_status_id = ".$_SESSION['filter']['order_status_id'];
				$this->set('order_status_id', $_SESSION['filter']['order_status_id']);
			} else {
				$order_status_id = "";
			}

			if($_SESSION['filter']['job_type'] > 0) {
				$job_type = "AND order_type_id = ".$_SESSION['filter']['job_type'];
				$this->set('job_type', $_SESSION['filter']['job_type']);
			} else {
				$job_type = "";
			}


			if($_SESSION['filter']['date_range'] == "") {
				$date_range = "'".date((date("Y")-1)."-m-01 00:00:00")."' AND '".date((date("Y")-1)."-m-31 23:59:59")."'";
				$this->set('date_range', "'".date((date("Y")-1)."-m-01")."' - '".date((date("Y")-1)."-m-31")."'");
				$_SESSION['filter']['date_range'] =  "'".date((date("Y")-1)."-m-01")."' - '".date((date("Y")-1)."-m-31")."'";
			} else {
				$date_range = str_replace(" - ", " AND ", $_SESSION['filter']['date_range']);
				$date_range = str_replace("\\", "", $date_range);
				$date_range = substr_replace(str_replace("' AND ", " 00:00:00' AND ", $date_range), " 23:59:59'",-1);
				$this->set('date_range', str_replace("\\", "", $_SESSION['filter']['date_range']));
			}

			$_SESSION['filter']['search_term'] = $search_term;
		}

		$query = "
			SELECT *
			FROM ace_rp_order_types
			WHERE flagactive = 1;
		";

		$results = $db->_execute($query);

		$types = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$types[$row['id']] = $temp;
		}

		$this->set('types', $types);

		$query = "
			SELECT  o.*, l.*, ca.*, c.first_name, c.last_name, c.city, c.address_street_number, c.address_street, c.email, c.postal_code, IFNULL(cl.list_id, 0) AS city_list
			FROM ace_rp_orders o
			LEFT JOIN n_leads l
			ON o.customer_phone = l.lead_phone
			LEFT JOIN ace_rp_customers c
			ON o.customer_id = c.id
			LEFT JOIN n_latest_call lc
			ON lc.call_number = l.lead_phone
			LEFT JOIN n_calls ca
			ON ca.call_id = lc.call_id
			LEFT JOIN n_city_lists cl
			ON cl.lead_city = ucase(c.city)
			WHERE job_date BETWEEN $date_range
			AND (o.booking_source_id = $user_id OR o.booking_telemarketer_id = $user_id)
			AND (
			c.address_street LIKE '%".$search_term."%'
			OR
			c.city LIKE '%".$search_term."%'
			OR
			o.order_number LIKE '%".$search_term."%'
			)
			$order_status_id
			$job_type
			ORDER BY ca.call_date
			LIMIT 200
		";

		$results = $db->_execute($query);

		$rows = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
		}

		$dates = array();

		$last_year = date("Y") - 1;
		$month = date("m");
		/*
		for($i=0;$i < 12;$i++) {
			$m = ($month+$i+2)%12;
			$monthName = date('F', mktime(0, 0, 0, $m, 10));
			if($m == 0) $m = 12;
			$dates[] = array(
				"range" => "'$last_year-$m-01' - '$last_year-$m-31'",
				"name" => $monthName." ".$last_year
			);
			if($m == 12) $last_year = $last_year+1;
		}*/

		for($i=0;$i < 12;$i++) {
			$m = $i+1;
			$monthName = date('F', mktime(0, 0, 0, $i+2, 0));
			//if($m == 0) $m = 12;
			$dates[] = array(
				"range" => "'$last_year-$m-01' - '$last_year-$m-31'",
				"name" => $monthName." ".$last_year
			);
			//if($m == 12) $last_year = $last_year+1;
		}

		//$this->set('jobs', $jobs);
		$this->set('dates', $dates);
		$this->set('query', $query);
		$this->set('results', $rows);
		$this->layout = 'responsive2';
	}

	function lead() {
		$user_id = $_SESSION['user']['id'];
		$lead_id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');

		$query = "
			SELECT l.*, c.*
			FROM n_leads l
			LEFT JOIN n_calls c
			ON l.lead_phone = c.call_number
			WHERE l.lead_id = $lead_id
			LIMIT 1
		";
		$results = $db->_execute($query);

		$callback = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$callback = $temp;
		}

		if(isset($_POST['disposition'])) {
			$this->_saveusermetric($user_id, "DISP CALL", $_POST['number']);

			$notes = $_POST['notes'];
			$lead_id = $_POST['lead'];
			$number = $_POST['number'];
			$return = $_POST['return'];
			$status = $_POST['disposition'];

			if(isset($return)) {
				$query = "
					INSERT INTO n_calls
					SET call_number = '$number',
						call_date = NOW(),
						call_status = $status,
						call_note = '$notes',
						call_return = '$return',
						user_id = $user_id
				";
			} else {
				$query = "
					INSERT INTO n_calls
					SET call_number = '$number',
						call_date = NOW(),
						call_status = $status,
						call_note = '$notes',
						user_id = $user_id
				";
			}
			$results = $db->_execute($query);
			$query = "
				UPDATE n_leads
				SET lead_status = 3
				WHERE lead_id = $lead_id
			";
			$results = $db->_execute($query);
			unset($_POST['disposition']);
			$this->_redirect('calls/search');
		}


		//$this->_redirect('calls/nomoreleads');



		$this->set('spiel', "");
		$phone = $callback['lead_phone'];
		//$customers = $this->_getcustomers($phone);
		$history = $this->_gethistory(trim($phone));
		$customers = $this->_getcustomers(trim($phone));
		$this->set('customers', $customers);
		$this->set('test_phone', $test_phone);
		$this->set('history', $history);
		$this->_saveusermetric($user_id, "START CALL", $phone);


		$this->set('callback', $callback);
		$this->layout = 'responsive2';
	}

	function addlead() {
		$db =& ConnectionManager::getDataSource('default');

		$query = "
			INSERT INTO n_leads
			SET lead_phone = '".$_POST['lead_phone']."',
			lead_first_name = '".$_POST['lead_first_name']."',
			lead_last_name = '".$_POST['lead_last_name']."',
			lead_postal_code = '".$_POST['lead_postal_code']."',
			lead_address = '".$_POST['lead_address']."',
			lead_city = '".$_POST['lead_city']."',
			lead_email = '".$_POST['lead_email']."',
			lead_status = '".$_POST['lead_status']."',
			list_id = '".$_POST['list_id']."'
		";
		$results = $db->_execute($query);

		$query = "
			select last_insert_id() last_id
		";
		$results = $db->_execute($query);

		$rows = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			$last_id = $row['last_id'];
		}

		$this->_redirect('calls/lead?id='.$last_id);
	}

	function inventorylocationscount() {
		$search_type = isset($_POST['search_type'])?$_POST['search_type']:1;
		$this->set('search_type', $search_type);


		$db =& ConnectionManager::getDataSource('default');
		switch($search_type) {
			case "1":
				$query = "
					SELECT item_sku, MAX(item_cost) AS item_cost, item_description, sum(item_quantity) AS item_quantity, '' AS location, '' AS supplier
					FROM n_purchase_items p
					GROUP BY item_sku
				";
			break;
			case "2":
				$query = "
					SELECT item_sku, MAX(item_cost) AS item_cost, item_description, sum(item_quantity) AS item_quantity, IF(p.location_id=0,'Warehouse', il.name) AS location, '' AS supplier
					FROM n_purchase_items p
					LEFT JOIN ace_rp_inventory_locations il
					ON p.location_id = il.id
					GROUP BY item_sku, location_id
				";
			break;
			case "3":
				$query = "
					SELECT item_sku, item_cost, item_description, sum(item_quantity) AS item_quantity, '' AS location, IFNULL(s.name, '') AS supplier
					FROM n_purchase_items p
					LEFT JOIN n_purchases ps
					ON ps.purchase_id = p.purchase_id
					LEFT JOIN ace_rp_suppliers s
					ON ps.supplier_id = s.id
					GROUP BY ps.supplier_id, item_sku
				";
			break;
			case "4":
				$query = "
					SELECT item_sku, item_description, sum(item_quantity) AS item_quantity, IF(p.location_id=0,'Warehouse', il.name) AS location, IFNULL(s.name, '') AS supplier
					FROM n_purchase_items p
					LEFT JOIN ace_rp_inventory_locations il
					ON p.location_id = il.id
					LEFT JOIN n_purchases ps
					ON ps.purchase_id = p.purchase_id
					LEFT JOIN ace_rp_suppliers s
					ON ps.supplier_id = s.id
					GROUP BY ps.supplier_id, item_sku, location_id
				";
			break;
		}

		$results = $db->_execute($query);
		$items = array();
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$items[] = $temp;
		}

		$this->set('items', $items);
		$this->layout = 'responsive2';
	}



	function purchases() {
		if($_POST['btn-filter']) {
			$date_range = str_replace(" - ", " AND ", $_POST['date_range']);
			$date_range = str_replace("\\", "", $date_range);
			$date_range = substr_replace(str_replace("' AND ", " 00:00:00' AND ", $date_range), " 23:59:59'",-1);
			$supplier_name = $_POST['supplier_name'];
			$this->set('date_range', str_replace("\\", "", $_POST['date_range']));
			$this->set('supplier_name', $supplier_name);
		} else {
			$date_range = "'".date("Y-m-d")."' AND '".date("Y-m-d")."'";
			$date_range = "'".date("Y-m-d 00:00:00")."' AND '".date("Y-m-d 23:59:59")."'";
			$this->set('date_range', "'".date("Y-m-d")."' - '".date("Y-m-d")."'");
			$supplier_name = "";
		}

		$invoice = 123;
		$db =& ConnectionManager::getDataSource('default');

		$query = "
			SELECT oi.order_id, o.order_number, o.job_date AS purchase_date, oi.supplier AS supplier_name, oi.invoice AS purchase_invoice, oi.name, oi.price_purchase as purchase_cost, '1' as tt, 'J' AS purchase_type
			FROM ace_rp_order_items oi
			LEFT JOIN ace_rp_orders o
			ON o.id = oi.order_id
			WHERE oi.item_id = 1227 AND o.job_date BETWEEN $date_range
			AND TRIM(IFNULL(oi.supplier, '')) LIKE '%$supplier_name%'
			UNION
			SELECT p.purchase_id, p.purchase_id, p.purchase_date, s.name, p.purchase_invoice, '[Multiple Items]', p.purchase_cost + p.purchase_tax, '2', p.purchase_type
			FROM n_purchases p
			LEFT JOIN ace_rp_suppliers s
			ON p.supplier_id = s.id
			WHERE p.purchase_date BETWEEN $date_range
			AND s.name LIKE '%$supplier_name%'
		";

		/*
		if(trim($supplier_name) != "") {
			$query = "
				SELECT p.*, IFNULL(s.name, '') supplier_name
				FROM n_purchases p
				LEFT JOIN ace_rp_suppliers s
				ON p.supplier_id = s.id
				WHERE p.purchase_date BETWEEN $date_range
				AND p.purchase_type = 1
				AND s.name LIKE '%$supplier_name%'
			";
		} else {
			$query = "
				SELECT p.*, IFNULL(s.name, '') supplier_name
				FROM n_purchases p
				LEFT JOIN ace_rp_suppliers s
				ON p.supplier_id = s.id
				WHERE p.purchase_date BETWEEN $date_range
				AND p.purchase_type = 1
			";
		}
		*/


		$results = $db->_execute($query);

		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
			$rows_types[$temp['purchase_type']][] = $temp;
		}
		$this->set('purchases', $rows);
		$this->set('purchases_types', $rows_types);

		if(trim($supplier_name) != "") {
			$query = "
				SELECT p.purchase_type, pt.purchase_type_name, SUM(IFNULL(p.purchase_cost, 0)) purchase_sum, pt.purchase_debit
				FROM n_purchases p
				LEFT JOIN ace_rp_suppliers s
				ON p.supplier_id = s.id
				LEFT JOIN n_purchase_types pt
				ON p.purchase_type = pt.purchase_type_id
				WHERE p.purchase_date BETWEEN $date_range
				AND s.name LIKE '%$supplier_name%'
				GROUP BY p.purchase_type
			";
		} else {
			$query = "
				SELECT p.purchase_type, pt.purchase_type_name, SUM(IFNULL(p.purchase_cost, 0)) purchase_sum, pt.purchase_debit
				FROM n_purchases p
				LEFT JOIN ace_rp_suppliers s
				ON p.supplier_id = s.id
				LEFT JOIN n_purchase_types pt
				ON p.purchase_type = pt.purchase_type_id
				WHERE p.purchase_date BETWEEN $date_range
				GROUP BY p.purchase_type
			";
		}
		$results = $db->_execute($query);

		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$summaries[] = $temp;
		}
		$this->set('summaries', $summaries);

		$this->layout = 'responsive2';
	}

	function inventorylocationsadj() {
		$db =& ConnectionManager::getDataSource('default');
		$query = "
			SELECT i.*, s.name AS supplier_name, c.name AS category_name
			FROM ace_iv_items i
			LEFT JOIN ace_iv_suppliers s
			ON i.iv_supplier_id = s.id
			LEFT JOIN ace_iv_categories c
			ON i.iv_category_id = c.id
			WHERE i.active = 1
		";
		$results = $db->_execute($query);
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$items[] = $temp;
		}

		$query = "
			SELECT il.*, u.first_name
			FROM ace_rp_inventory_locations il
			LEFT JOIN ace_rp_users u
			ON il.tech1_day".date("w")." = u.id
		";
		$results = $db->_execute($query);
		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$locations[] = $temp;
		}


		$this->set('items', $items);
		$this->set('locations', $locations);
		$this->layout = 'responsive2';
	}

	function invoicepurchases() {
		if($_POST['btn-filter']) {
			$date_range = str_replace(" - ", " AND ", $_POST['date_range']);
			$date_range = str_replace("\\", "", $date_range);
			$date_range = substr_replace(str_replace("' AND ", " 00:00:00' AND ", $date_range), " 23:59:59'",-1);
			$supplier_name = $_POST['supplier_name'];
			$this->set('date_range', str_replace("\\", "", $_POST['date_range']));
			$this->set('supplier_name', $supplier_name);
		} else {
			$date_range = "'".date("Y-m-d")."' AND '".date("Y-m-d")."'";
			$date_range = "'".date("Y-m-d 00:00:00")."' AND '".date("Y-m-d 23:59:59")."'";
			$this->set('date_range', "'".date("Y-m-d")."' - '".date("Y-m-d")."'");
			$supplier_name = "";
		}

		$invoice = 123;
		$db =& ConnectionManager::getDataSource('default');

		$query = "
			SELECT oi.order_id, o.order_number, o.job_date, oi.supplier, oi.invoice, oi.name, oi.price_purchase as price, oi.price_payment as payment, oi.price_refund as refund, '1' as tt, 'J' AS purchase_type
			FROM ace_rp_order_items oi
			LEFT JOIN ace_rp_orders o
			ON o.id = oi.order_id
			WHERE oi.item_id = 1227 AND o.job_date BETWEEN $date_range
			AND TRIM(IFNULL(oi.supplier, '')) LIKE '%$supplier_name%'
			UNION
			SELECT p.purchase_id, p.purchase_id, p.purchase_date, s.name, p.purchase_invoice, '[Multiple Items]', p.purchase_cost, p.purchase_payment, p.purchase_refund, '2', p.purchase_type
			FROM n_purchases p
			LEFT JOIN ace_rp_suppliers s
			ON p.supplier_id = s.id
			WHERE p.purchase_date BETWEEN $date_range
			AND s.name LIKE '%$supplier_name%'
		";

		$results = $db->_execute($query);

		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
			//$rows_types[$temp['purchase_type']][] = $temp;
		}
		$this->set('purchases', $rows);//echo $query."<pre>";print_r($rows);
		//$this->set('purchases_types', $rows_types);
		$this->layout = 'responsive2';
	}

	function adjuststock() {
		$db =& ConnectionManager::getDataSource('default');
		$val = $_POST['val'];
		$location = $_POST['location'];
		$item_id = $_POST['item_id'];
		$user_id = $_SESSION['user']['id'];
		$query = "
			UPDATE ace_iv_items
			SET location".$location." = $val
			WHERE id = $item_id
		";
		$db->_execute($query);

		$query = "
			INSERT INTO n_inventory_adjustments SET
			inventory_adjustment_user_id = $user_id,
			inventory_adjustment_location = $location,
			inventory_adjustment_amount = $val,
			inventory_adjustment_item = $item_id
		";
		$db->_execute($query);


		echo "Done";
	}

	function adjustmentreport() {
		if(isset($_POST['btn-filter']) && $_POST['btn-filter']) {
			$date_range = str_replace(" - ", " AND ", $_POST['date_range']);
			$date_range = str_replace("\\", "", $date_range);
			$date_range = substr_replace(str_replace("' AND ", " 00:00:00' AND ", $date_range), " 23:59:59'",-1);
			$supplier_name = $_POST['supplier_name'];
			$this->set('date_range', str_replace("\\", "", $_POST['date_range']));
			$this->set('supplier_name', $supplier_name);
		} else {
			$date_range = "'".date("Y-m-d")."' AND '".date("Y-m-d")."'";
			$date_range = "'".date("Y-m-d 00:00:00")."' AND '".date("Y-m-d 23:59:59")."'";
			$this->set('date_range', "'".date("Y-m-d")."' - '".date("Y-m-d")."'");
			$supplier_name = "";
		}


		$db =& ConnectionManager::getDataSource('default');

		$query = "
			SELECT ia.*, u.first_name, i.name AS item_name
			FROM n_inventory_adjustments ia
			LEFT JOIN ace_rp_users u
			ON ia.inventory_adjustment_user_id = u.id
			LEFT JOIN ace_iv_items i
			ON ia.inventory_adjustment_item = i.id
			WHERE ia.inventory_adjustment_datetime BETWEEN $date_range
		";

		$results = $db->_execute($query);

		while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			foreach ($row as $field => $val) {
				$temp[$field] = $val;
			}
			$rows[] = $temp;
			//$rows_types[$temp['purchase_type']][] = $temp;
		}
		$this->set('purchases', $rows);
		//$this->set('purchases_types', $rows_types);
		$this->layout = 'responsive2';
	}
/*******************************Jack Hutson ****************/
	function showCustomerJobs($customer_id,$order_id,$phone)
	{
		//$customer_id = $_GET['customer_id'];
		//$order_id = $_GET['order_id'];
		//$phone = $_GET['phone'];
		
		//$customer_id = $_GET['customer_id'];
		//$order_id = $_GET['order_id'];
		//$phone = $_GET['phone'];
		$class="trshow";
  $content='';


		if ($this->Common->getLoggedUserRoleID() != "1") $method = "editBooking"; else $method = "techBooking";
		$allStatuses = $this->Lists->ListTable('ace_rp_order_statuses');
		$allJobTypes = $this->Lists->ListTable('ace_rp_order_types');

		$content.= '<table class="historytable">';
		$content.= '<tr class="'.$class.'" cellpadding="10">';
		$content.= '<th>Date</th><th>Booking</th><th>Status</th><th>Tech</th>';
		if ($this->Common->getLoggedUserRoleID() == 6) $content.= '<th>Feedback</th>';
		$content.= '</tr>';
		$content.= "<tr class='".$class."'><td colspan=8 style=\"background: #AAAAAA; height: 5px;\"></td></tr>\n";

		if ($phone)
		{
			$sq_str = preg_replace("/[- \.]/", "", $phone);
			$sq_str = preg_replace("/([?])*/", "[-]*", $phone);
//	    $past_orders = $this->Order->findAll(array('Order.customer_id'=> $customer_id), null, "job_date DESC", null, null, 1);
//			$past_orders = $this->Order->findAll(array('Customer.phone'=> $phone), null, "job_date DESC", null, null, 1);
//	      $past_orders = $this->Order->findAll(array('Order.customer_phone'=> $phone), null, "job_date DESC", null, null, 1);
			$past_orders = array();
			$db =& ConnectionManager::getDataSource('default');
			$query = "select * from ace_rp_orders where customer_phone regexp '$sq_str' order by job_date DESC";

			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result))
				$past_orders[$row['id']] = $row['id'];

			foreach ($past_orders as $cur)
			{
				$p_order = $this->Order->findAll(array('Order.id'=> $cur), null, "job_date DESC", null, null, 1);
				$p_order = $p_order[0];
				if ($p_order['Order']['id'] == $order_id)
					$add = "style=\"background: #FFFF99;\"";
				else
				{
					if ((($this->Common->getLoggedUserRoleID() != 3)
					   &&($this->Common->getLoggedUserRoleID() != 9)
					   &&($this->Common->getLoggedUserRoleID() != 1))
						||($this->Common->getLoggedUserID()==$p_order['Order']['booking_telemarketer_id']))
						$add = " style=\"cursor: hand; cursor: pointer;\" onclick=\"location.href='./".$method."?order_id=".$p_order['Order']['id']."';\"";
					else
						$add = "";
				}

				$items_text='';
				$total_booked=0;
				$total_extra=0;
				foreach ($p_order['BookingItem'] as $oi)
				{
					$str_sum = round($oi['quantity']*$oi['price'],2);
					if ($oi['class']==0)
					{
						$text = 'booked';
						$total_booked += 0+$str_sum-$oi['discount']+$oi['addition'];
					}
					else
					{
						$text = 'provided by tech';
						$total_extra += 0+$str_sum-$oi['discount']+$oi['addition'];
					}

					if ((($this->Common->getLoggedUserRoleID() != 3)
					  &&($this->Common->getLoggedUserRoleID() != 9))
					  ||($oi['class']==0))
					{
						$items_text .= '<tr>';
						$items_text .= '<td>'.$text.'</td>';
						$items_text .= '<td style="width:200px">'.$oi['name'].'</td>';
						$items_text .= '<td>'.$oi['quantity'].'</td>';
						$items_text .= '<td>'.$this->HtmlAssist->prPrice($oi['price']).'</td>';
						$items_text .= '<td>'.$this->HtmlAssist->prPrice($oi['addition']-$oi['discount']).'</td>';
						//$items_text .= '<td>'.$this->HtmlAssist->prPrice($str_sum).'</td>';
						$items_text .= '</tr>';
					}
				}
				foreach ($p_order['BookingCoupon'] as $oi)
				{
					$str_sum = 0-$oi['price'];
					if ($oi['class']==0)
					{
					  $text = 'booked';
					  $total_booked += 0+$str_sum;
					}
					else
					{
					  $text = 'provided by tech';
					  $total_extra += 0+$str_sum;
					}

					if ((($this->Common->getLoggedUserRoleID() != 3)
						&&($this->Common->getLoggedUserRoleID() != 9))
						||($oi['class']==0))
					{
					  $items_text .= '<tr>';
					  $items_text .= '<td>'.$text.'</td>';
					  $items_text .= '<td style="width:200px">'.$oi['name'].'</td>';
					  $items_text .= '<td>&nbsp;</td>';
					  $items_text .= '<td>'.$this->HtmlAssist->prPrice($str_sum).'</td>';
					  $items_text .= '<td>&nbsp;</td>';
					  //$items_text .= '<td>'.$this->HtmlAssist->prPrice($str_sum).'</td>';
					  $items_text .= '</tr>';
					}
				}

	          $content.= "<tr class='orderline ".$class."' valign='top' ".$add." >";
	          $content.= "<td rowspan=1>".date('d-m-Y', strtotime($p_order['Order']['job_date']))."<br>REF#".$p_order['Order']['order_number']."</td>";
	          $content.= "<td rowspan=1>".$this->HtmlAssist->prPrice($total_booked)."</td>";
	          //echo "<td rowspan=1>".$this->HtmlAssist->prPrice($p_order['Order']['customer_paid_amount'])."</td>";
            $status = $p_order['Order']['order_status_id'];
            $color="";
            if (($status == 3)||($status == 2)) $color="color:red";
            if ($status == 5) $color="color:green";
	          $content.= "<td><b style='".$color."'>".$allStatuses[$status]."</b><br/>";
	          $content.= $allJobTypes[$p_order['Order']['order_type_id']]."</td>";
	          $content.= "<td>".$p_order['Technician1']['first_name']."<br/>"
	                    .$p_order['Technician2']['first_name']."</td>";
						if ($this->Common->getLoggedUserRoleID() == 6)
							$content.= "<td rowspan=2><a style='text-decoration:none;color:black;' href='".BASE_URL."/orders/feedbacks_add?id=". $p_order['Order']['id']."'><b>".$p_order['Order']['feedback_quality']."</b><br/>".
												"<b>Notes</b>: ".$p_order['Order']['feedback_comment']."<br/>".
												"<b>Solution</b>: ".$p_order['Order']['feedback_suggestion']."</a></td>";
	          $content.= "</tr>\n";
	          $content.= "<tr class='".$class."' valign='top' ".$add." >";
	          $content.= "<td colspan=4>";
						$content.= '<table cellspacing=0 colspacing=5>';
						//echo '<tr><th>&nbsp;</th><th align=left style="width:200px">Item</th><th>Qty</th><th>Price</th><th>Sum</th></tr>';
						$content.= '<tr><th>&nbsp;</th><th align=left style="width:200px">Item</th><th>Qty</th><th>Price</th><th>Adj</th></tr>';
            $content.= $items_text;
	          $content.= '</table>';
	          $content.= "</td>";
	          $content.= "</tr>\n";
	          $content.= "<tr class='".$class."'><td colspan=8 style=\"background: #AAAAAA; height: 5px;\"></td></tr>\n";
	        $class="trhide";
	        }
	    }

	    $content.= "</table>";
	    return $content;
	    //exit;
	}
	
	


}
?>
