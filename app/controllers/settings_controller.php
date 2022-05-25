<? ob_start();
// error_reporting(E_ALL);

class SettingsController extends AppController
{
	var $name = "SettingsController";
	var $uses = array('Setting','User','TrainingCategory','TrainingSubCategory','TrainingSecondSubCategory','AdminTrainingCategory','AdminTrainingSubCategory','AdminTrainingSecondSubCategory', 'TechsTrainingCategory','TechsTrainingSecondSubCategory','TechsTrainingSubCategory','AgentTrainingCategory','AgentTrainingSubCategory','AgentTrainingSecondSubCategory');
	// var $components = array('HtmlAssist', 'RequestHandler', 'Jpgraph', 'Common', 'Lists');

	var $helpers = array('Common');
	function edit()
	{
		//If we have no data, then we need to provide the data to the user for editing
		if (empty($this->data['Setting']))
		{
			$this->data = $this->Setting->find(array("title" => $_GET['title']));
		}
		else if (!empty($this->data['Setting']))
		{
			//Validate & Validate
			if ($this->Setting->save($this->data['Setting']))
			{
				//Forward user where they need to be - if this is a single action per view
				 if ($this->data['rurl'][0]){
				 	$this->redirect($this->data['rurl'][0]);
				 }
				 else{
					$this->redirect('/orders/scheduleView');
				 }
				exit();
			}
		}
	}
//apoorv add the save time option
	function timeOption()
	{
		$this->layout = 'blank';
		$db =& ConnectionManager::getDataSource('default');
		$user_query1 = "select * from ace_rp_time_setting";
        $result12 = $db->_execute($user_query1);
        
        while($row1 = mysql_fetch_array($result12)) {
         
           
            $this->set($row1['name'], $row1['value']);
                      
        }
			
			
			}

			public function savetime(){
		
				$db =& ConnectionManager::getDataSource('default');
				$repair_time 		=  $_REQUEST['repair_time'];
				$repair_km 			=  $_REQUEST['repair_km'];
				$airduct_time  	 	=  $_REQUEST['airduct_time'];
				$airduct_km 		= $_REQUEST['airduct_km'];
				$service_time 		=  $_REQUEST['service_time'];
				$service_km 			=  $_REQUEST['service_km'];
				$installation_time  =  $_REQUEST['installation_time'];
				$installation_km  =  $_REQUEST['installation_km'];
				
				$query = "UPDATE ace_rp_time_setting set value='".$repair_time."' where name='repair_time'";
				$query1 = "UPDATE ace_rp_time_setting set value='".$repair_km."' where name='repair_km'";
				$query2 = "UPDATE ace_rp_time_setting set value='".$airduct_time."' where name='airduct_time'";
				$query3 = "UPDATE ace_rp_time_setting set value='".$airduct_km."' where name='airduct_km'";
				$query4 = "UPDATE ace_rp_time_setting set value='".$service_time."' where name='service_time'";
				$query5 = "UPDATE ace_rp_time_setting set value='".$service_km."' where name='service_km'";
				$query6 = "UPDATE ace_rp_time_setting set value='".$installation_time."' where name='installation_time'";
				$query7 = "UPDATE ace_rp_time_setting set value='".$installation_km."' where name='installation_km'";
			$result = $db->_execute($query);
			$result1 = $db->_execute($query1);
			$result2 = $db->_execute($query2);
			$result3 = $db->_execute($query3);
			$result4 = $db->_execute($query4);
			$result5 = $db->_execute($query5);
			$result6 = $db->_execute($query6);
			$result7 = $db->_execute($query7);
		
		
				
				

		exit();
	}
	
	function editNewsletter(){
		if (empty($this->data['Setting']))
		{
			$this->data = $this->Setting->find(array("title" => $_GET['title']));
		}
		else if (!empty($this->data['Setting']))
		{
			//Validate & Validate
			if ($this->Setting->save($this->data['Setting']))
			{
				//ToDo: Send email to all users or to specific email

				//Get E-mail Settings
				$settings = $this->Setting->find(array('title'=>'email_fromaddress'));
				$from_address = $settings['Setting']['valuetxt'];
				
				$settings = $this->Setting->find(array('title'=>'email_fromname'));
				$from_name = $settings['Setting']['valuetxt'];
		
				//$settings = $this->Setting->find(array('title'=>'email_template_custom'));
				$settings = $this->Setting->find(array('title'=> $_GET['title']));
				
				$template = $settings['Setting']['valuetxt'];
		
				$template_subject = 'Ace Services Ltd';

				//define the headers we want passed. Note that they are separated with \r\n
				//$headers = "From: webmaster@example.com\r\nReply-To: webmaster@example.com";
				$headers = "From: ".$from_address."\n";
				//add boundary string and mime type specification
				$headers .= "Content-Type: text/html; charset=iso-8859-1\n" ;				
				
				$msg = $template;
				if($_POST['sent_to'] == 1){
					//echo $_POST['email'];
					if( ($_POST['email']!='') && $this->check_email($_POST['email']) ){
						$msg = str_replace('{first_name}', 'Dear', $msg);
						$msg = str_replace('{last_name}', '', $msg);
						$res = mail($_POST['email'], $template_subject, $msg, $headers);
						// print_r($res); die;
					}
				}
				elseif($_POST['sent_to'] == 0)
				{
					set_time_limit(36000);
					//sent to all users
					//load all active users
					$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

					$query = "
						SELECT u.id, u.first_name, u.last_name, u.email FROM ace_rp_customers u
						WHERE u.is_active=1 and u.callresult!=4 and u.email is not null
						and u.email not in ('','ace_123@live.ca','ace-123@live.ca','NO EMAIL','N/A','cb@acecare.ca','NONE',
						'ace_123@livenation.ca','N A','a','ACE_123@LIVE.COM','ace_123@live.com','ACE_!23@LIVE.CA','NA','ali@acecare.ca')
						and exists (select * from ace_rp_orders o where o.customer_id=u.id)
					";
					
					$query = "
						SELECT u.id, u.first_name, u.last_name, u.email
						FROM ace_rp_customers u
						WHERE email != ''";
					
					$result = $db->_execute($query);
					
					$st = array();
					while ($row = mysql_fetch_array($result))
					{
						if($this->check_email($row['email']))
						{
							$msg_temp = $msg;
							$msg_temp = str_replace('{first_name}', $row['first_name'], $msg);							
							$msg_temp = str_replace('{last_name}', $row['last_name'], $msg_temp);
							$msg_temp = str_replace('{ace_logo}', "http://69.31.184.162:81/acesys/print_ace2.png", $msg_temp);						
							$st[] = array('email'=>$row['email'],'body'=>$msg_temp);
						}
					}
					
					$cnt = 0;
					foreach ($st as $cur)
					{
						$res = mail($cur['email'], $template_subject, $cur['body'], $headers);
						$cnt++;
					}

					set_time_limit(30);
					$this->flash($cnt.' emails have been sent.','/settings/editNewsletter?title={\'$_GET["title"]\'}');
				}
				
			}
		}
	}

	function check_email($email) {
	    if( (preg_match('/(@.*@)|(\.\.)|(@\.)|(\.@)|(^\.)/', $email)) ||
	        (preg_match('/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,3}|[0-9]{1,3})(\]?)$/',$email)) ) {
				
	    	return true;
			}
	    else{
	    	return false;
		}
	}
		
	function generalSettings() {
		$this->layout = "blank";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$query = "
			SELECT * FROM ace_rp_settings WHERE id IN(19,20,21)		
		";
		
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$settings[$row['id']]['title'] = $row['title'];	
			$settings[$row['id']]['name'] = $row['name'];	
			$settings[$row['id']]['valuetxt'] = $row['valuetxt'];			
		}
		
		$this->set("settings", $settings);
		
	}
	
	function saveGeneralSettings() {
		$this->layout = "blank";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		print_r($this->data['Setting']);
		
		foreach($this->data['Setting'] as $key => $val) {
			$query = "
			UPDATE ace_rp_settings
			SET valuetxt = '$val'
			WHERE id = $key
		";		
		$result = $db->_execute($query);
		}		
		
		$this->redirect("settings/generalSettings");
	}
	// Loki- Show settings options
	function showSetting()
	{

	}

	function showPurchasePrice()
	{
		$db =& ConnectionManager::getDataSource('default');
		$query = "SELECT active from ace_rp_show_purchase_price WHERE id =1";
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result);
		$this->set('active', $row['active']);
	}

	function changePurchaseActive()
	{
		$active = $_GET['is_active'];
		$id = $_GET['purchase_id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE ace_rp_show_purchase_price SET Active =".$active." WHERE id =".$id;
		$result = $db->_execute($query);
		exit();
	}
	// Loki: Show the bulk email template
	function editBulkEmail()
	{
		//If we have no data, then we need to provide the data to the user for editing
		if (empty($this->data['Setting']))
		{
			$this->data = $this->Setting->find(array("title" => $_GET['title']));
		}
		else if (!empty($this->data['Setting']))
		{
			//Validate & Validate
			if ($this->Setting->save($this->data['Setting']))
			{
				//Forward user where they need to be - if this is a single action per view
				if ($this->data['rurl'][0]){
					$this->redirect($this->data['rurl'][0]);
				}
				else{
					$this->redirect('/orders/scheduleView');
				}
				exit();
			}
		}
	}

	//Loki: Get the bulk email template content
	function getBulkMailContent()
	{
		$settings = $this->Setting->find(array('title'=>'bulk_email'));
		$message = $settings['Setting']['valuetxt'];
		$response  = array("msgBody" => $message);
		echo json_encode($response);
		exit();
	}
	//Loki: Save the bulk email template content
	function saveBulkEmailContent()
	{
		$title = 'bulk_email';
		$content = $_POST['content'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$update = "UPDATE  ace_rp_settings set valuetxt = '".$content."' where title = '".$title."'";
		$result = $db->_execute($update);
		if($result)
		{			
 			$response  = array("res" => "OK");
 			echo json_encode($response);
 			exit();
		}
		exit();
	}

	//Loki: Save the bulk SMS template content
	function saveBulkSmsContent()
	{
		$title = 'bulk_sms';
		$content = $_POST['content'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$update = "UPDATE  ace_rp_settings set valuetxt = '".$content."' where title = '".$title."'";
		$result = $db->_execute($update);
		if($result)
		{			
 			$response  = array("res" => "OK");
 			echo json_encode($response);
 			exit();
		}
		exit();
	}

	//Loki: Get the bulk Sms template content
	function getBulkSmsContent()
	{
		$settings = $this->Setting->find(array('title'=>'bulk_sms'));
		$message = $settings['Setting']['valuetxt'];
		$response  = array("msgBody" => $message);
		echo json_encode($response);
		exit();
	}
	//Loki: Get the Review text template content
	function getReviewTextContent()
	{
		$settings = $this->Setting->find(array('title'=>'review_text'));
		$message = $settings['Setting']['valuetxt'];
		$response  = array("msgBody" => $message);
		echo json_encode($response);
		exit();
	}

	function techInvoiceTemplate()
	{

	}


	function showTrainingCategory()
	{			
		$allCat = $this->TrainingCategory->findAll();
		$this->set('trainingCategories', $allCat);
	}

	function showTrainingSubCategory()
	{			
		$allSubCat = $this->TrainingSubCategory->findAll();		
		$this->set('trainingSubCategories', $allSubCat);
	}
	
	function showTrainingSecondSubCategory()
	{			
		$allSecondSubCat = $this->TrainingSecondSubCategory->findAll();		
		$this->set('trainingSecondSubCategories', $allSecondSubCat);
	}

	function editTrainingCategory($fromAdd = 0,$id=0) {
		// error_reporting(E_ALL);
		$this->layout = 'blank';	

		if (empty($this->data['TrainingCategory'])) {    

			$this->TrainingCategory->id = $id;    

			$this->data = $this->TrainingCategory->read();	

			$this->set("fromAdd", $fromAdd);
		}

	}

	function save() {
		$fromAdd = $_POST['fromAdd'];
		//if($this->data['IvCategory']['active'] != 1) $this->data['IvCategory']['active'] = 0;
		if($this->TrainingCategory->save($this->data['TrainingCategory'])) {				
			if($fromAdd == 1)
			{
				echo "<script>opener.location.reload();
				window.close();</script>";
		 			exit;
			}
		}	
	}

	function editCategory($id = null) {
		$this->layout = 'blank';
		if (empty($this->data['TrainingCategory'])) {    

			$this->TrainingCategory->id = $id;    

			$cat = $this->TrainingCategory->read();	
			
			$this->set("cat", $cat['TrainingCategory']);
		}
	}


	function updateCategory()
	{
		$id = $_POST['catId'];
		$name = $_POST['catName'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE ace_rp_training_category set name ='".$name."' WHERE id=".$id;
		$result = $db->_execute($query);
		exit();
	}

	// function deleteCategory()
	// {
	// 		$data = $_POST['typeIds'];
	// 		$ids = implode(',', $data);
	// 		$db =& ConnectionManager::getDataSource('default');
	// 		$query = "DELETE FROM ace_rp_training_category WHERE id IN (".$ids.")";
	// 		$result = $db->_execute($query);
	// 		exit();
	// }

	function editSubCategory($catId=0,$subCat=0)
	{	
		$db =& ConnectionManager::getDataSource('default');	
		$category = array();
		if($subCat) {
			$query = "SELECT * from ace_rp_training_sub_categories where id=".$subCat;
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
		} else {
			$row = array("id"=>"", "name"=>"", "cat_id"=>$catId);
		}
		$query1 = "SELECT * from ace_rp_training_category";
			$result1 = $db->_execute($query1);
			$category = array();		
			while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
			{
				foreach($row1 as $k => $v)
				{
				  $category[$row1['id']][$k] = $v;
				}	
			}
		
		$this->set('catId', $catId);
		$this->set('subCategory', $row);
		$this->set('categories', $category);
	}

	function addSubCategory($id)
	{	
		$allCatIds = $_POST['allCatId'];
		$catId = $_POST['catId'];
		$name = $_POST['catName'];
		$subCatId = $_POST['subCatId'];
		$db =& ConnectionManager::getDataSource('default');	
		if(!empty($subCatId) || $subCatId != '')
		{
			$query = "UPDATE ace_rp_training_sub_categories set name='".$name[0]."',cat_id=".$allCatIds[0]." where id=".$subCatId;
			$result = $db->_execute($query);
		} else {
			foreach($name as $key => $val)
			{
				$query = "INSERT INTO  ace_rp_training_sub_categories (name,cat_id) VALUES ('".$val."', ".$allCatIds[$key].")";	
				$result = $db->_execute($query);
			}
			
		}
		if($result)
			{
				echo "<script>opener.location.reload();
				window.close();</script>";
		 			exit();
			}
		exit();
	}

	// function deleteSubCategory()
	// {
	// 		$data = $_POST['typeIds'];
	// 		$ids = implode(',', $data);
	// 		$db =& ConnectionManager::getDataSource('default');
	// 		$query = "DELETE FROM  ace_rp_training_sub_categories WHERE id IN (".$ids.")";
	// 		$result = $db->_execute($query);
	// 		exit();
	// }

	function getSubCategory()
	{	
		$this->layout='blank';
		$catId = $_GET['catId'];
		$allSubCat = $this->TrainingSubCategory->findAll(array('cat_id' => $catId));		
		$this->set('trainingSubCategories', $allSubCat);
	}


	function addEvent(){
	$db =& ConnectionManager::getDataSource('default');
	
	$title = isset($_POST['title']) ? $_POST['title'] : "";
$start = isset($_POST['start']) ? $_POST['start'] : "";
$end = isset($_POST['end']) ? $_POST['end'] : "";
$owner = isset($_POST['owner']) ? $_POST['owner'] : "";

$reminder = isset($_POST['reminder']) ? $_POST['reminder'] : "";

$sqlInsert = "INSERT INTO ace_rp_orgniser_events (title,start,end,owner,reminder) VALUES ('".$title."','".$start."','".$end ."','".$owner ."','".$reminder."')";

$result1 = $db->_execute($sqlInsert);

exit;

	}
	
	function editEvent(){
	
	$db =& ConnectionManager::getDataSource('default');
	
	$title = $_REQUEST['title'];
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];
	$id    = $_REQUEST['id'];
	$owner = $_REQUEST['owner'];
	$reminder = $_REQUEST['reminder'];

	$query1 = "UPDATE ace_rp_orgniser_events set title='".$title."', start ='".$start."', end = '".$end."', owner = '".$owner."', reminder = '".$reminder."'   where id =".$id;
		$result1 = $db->_execute($query1);
		echo $query1;
		die($result1);
		exit;
		
	}

	function closeReminder(){
	
	$db =& ConnectionManager::getDataSource('default');

	$id = $_REQUEST['id'];

	$query1 = "UPDATE ace_rp_orgniser_events set reminder=0 where id =".$id;
		$result1 = $db->_execute($query1);
		exit;
	}
	
	function fetchEvent(){
	$db =& ConnectionManager::getDataSource('default');
	
    $json = array();
	$user = $_SESSION['user']['id'];
    $sqlQuery = "SELECT * FROM ace_rp_orgniser_events where owner in (0,$user) ORDER BY id";
	
	    $result = $db->_execute($sqlQuery);
		
		    $eventArray = array();
    while ($row = mysql_fetch_array($result)) {
        array_push($eventArray, $row);
    }

    echo json_encode($eventArray);


exit;

	}

	function todayEvent(){
	$db =& ConnectionManager::getDataSource('default');
	
    $json = array();
	$user = $_SESSION['user']['id'];
	$date = date('Y-m-d H:i:s', time());
    $sqlQuery = "SELECT * FROM ace_rp_orgniser_events where owner in (0,$user) AND start <= '".$date."' AND `end` >= '".$date."'  AND reminder = 1 ORDER BY id";
    
	    $result = $db->_execute($sqlQuery);
		
		    $eventArray = array();
    while ($row = mysql_fetch_array($result)) {
        array_push($eventArray, $row);
    }

    echo json_encode($eventArray);


exit;

	}
	
	function saveOrgniser(){
		
		$db =& ConnectionManager::getDataSource('default');
		$id = $_REQUEST['order_id'];
		$href = $_REQUEST['href'];
		$query1 = "select * from rp_acacare_ca_booking where id={$id}";
        $result12 = $db->_execute($query1);
		//$data=array();
		        while($row1 = mysql_fetch_array($result12)) {
					
					$data = $row1;
					
				}
				
				if($data['job_type']=="Estimation"){
				$start = $data['created_at']." 00:00:00";
				$end = $data['created_at']." 00:00:00";	
				}
				else {
					$str = $data['job_date'];
					if ($str == trim($str) && strpos($str, ' ') !== false) {
						$start1 = date('Y-m-d',strtotime($data['job_date']));
						$start = $start1." 00:00:00";
				        $end = $start1." 00:00:00";
    
					}
					else {
						$start = $data['job_date']." 00:00:00";
				        $end = $data['job_date']." 00:00:00";
						
					}
					
				}
				
				$customer = $this->checkCustomerId($data['phone']);
				
				
				if($customer){
            $customer = "&customer_id=".$customer;
          }
          else {
            $customer="";
          }
		  if($this->checkbookingexist($id)){
			$db->_execute("delete from ace_rp_orgniser_events where booking_id=".$id
						  
						  
						  
						  );
		  }
		  
				
				// $url = $data['link']."&from_booking=1&booking_id=".$data['id']."&first_name=".$data['first_name']."&last_name=".$data['last_name']."&email=".$data['email']."&phone=".$data['phone']."&street=".$data['street_number']."&route=".$data['route']."&message=".$data['message']."&job_type=".$data['job_type']."&coupon=".$data['coupon']."&city=".$data['city1'].$customer;
		  		$url = $href;
				
				$title = $data['first_name']." ".$data['last_name'];
				
				
				$sqlInsert = "INSERT INTO ace_rp_orgniser_events (title,start,end,url,booking_id) VALUES ('".$title."','".$start."','".$end ."','".$url ."','".$id ."')";

				$result = $db->_execute($sqlInsert);
		
		exit;
	}
	
	function checkbookingexist($id){
		$db =& ConnectionManager::getDataSource('default');
		
		$result = $db->_execute("SELECT id from ace_rp_orgniser_events where booking_id = $id order by id desc limit 1 ;");
		$row = mysql_fetch_array($result);
		if(!empty($row['id']))
		{
			return $row['id'];
            
		} else {
			return false;
            
		}
	}
	
	function checkCustomerId($phone_num)
	{
		$db =& ConnectionManager::getDataSource('default');
		
		
		$result = $db->_execute("SELECT id from ace_rp_customers where (cell_phone='$phone_num' or phone='phone' ) order by id desc limit 1 ;");
		$row = mysql_fetch_array($result);
		if(!empty($row['id']))
		{
			return $row['id'];
            
		} else {
			return false;
            
		}
		exit();

	}
	
	function deleteEvent(){
	
		$db =& ConnectionManager::getDataSource('default');
		$id = $_POST['id'];
        $sqlDelete = "DELETE from ace_rp_orgniser_events WHERE id=".$id;
		$db->_execute($sqlDelete);
		exit;
	}

	function orgniser(){
		$this->layout = 'blank';
		$db =& ConnectionManager::getDataSource('default');
			
			
			}

	function addTechSupport()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_training_material (sub_cat_id,tech_support,type,tech_support_name) VALUES (".$id.",'".$note."',1,'".$name."')";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getTechSupport()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type FROM ace_rp_training_material WHERE sub_cat_id =".$id." and type=1";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<input type="button" value="View" onclick="getTechSupport('.$row1['id'].');" class="getTechSupport" type-id="'.$row1['type'].'" row-id="'.$row1['id'].'">';
		}	
		echo $res;
		exit();
	}

	/*function getTechSupportVal()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,tech_support FROM ace_rp_training_material WHERE id =".$id."";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res = $row1;
		}	
		echo json_encode($res);
		exit();
	}*/

	function getTechSupportVal()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,tech_support,tech_support_name  FROM ace_rp_training_material WHERE sub_cat_id =".$id." and type=1";
		$res = '<table><tr><th></th><th><b>Name</b></th><th><b>Tech Support</b></th></tr>';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<tr><td><i class="fa fa-pencil-square-o editSubCatTech" row-id="'.$row1['id'].'"></i>&nbsp<i class="fa fa-trash-o deleteSubCatTech" row-id="'.$row1['id'].'"></i></td><td>'.$row1['tech_support_name'].'</td><td><span class="callSubCatNum" number="'.$row1['tech_support'].'">'.$row1['tech_support'].'</span></td></tr>';
			// $res .= $row1['tech_support'].'  ';
		}
		$res .= '<tr><td><button type="button" name="close" value="Close" onclick=$("#show_techsupport_val").dialog("close")>Close</button></td>
    </tr></table>';
		echo json_encode($res);
		exit();
	}

	function editSecondSubCategory($subCatId=0,$subCatType=0,$secondSubCatId=0)
	{	
		$db =& ConnectionManager::getDataSource('default');	
		$subCategory = array();
		if($secondSubCatId) {
			$query = "SELECT * from ace_rp_training_second_sub_categories where id=".$secondSubCatId;
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
		} else {
			$row = array("id"=>"", "name"=>"", "sub_cat"=>"","sub_cat_type"=>"");
		}
		$query1 = "SELECT * from ace_rp_training_sub_categories";
			$result1 = $db->_execute($query1);
			$subCategory = array();		
			while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
			{
				foreach($row1 as $k => $v)
				{
				  $subCategory[$row1['id']][$k] = $v;
				}	
			}
		
		$this->set('subCatId', $subCatId);
		$this->set('secondSubCategory', $row);
		$this->set('secondSubCategoryType', $subCatType);
		$this->set('subCategories', $subCategory);
		
	}

	function addSecondSubCategory($id)
	{	
		$allSubCatIds = $_POST['allSubCatId'];
		$name = $_POST['secondSubCatName'];
		$subCatId = $_POST['subCatId'];
		$secondSubCatId = $_POST['secondSubCatId'];
		$secondSubCatType = $_POST['secondSubCatType'];
		$db =& ConnectionManager::getDataSource('default');	
		if(!empty($secondSubCatId) || $secondSubCatId != '')
		{
			$query = "UPDATE ace_rp_training_second_sub_categories set name='".$name[0]."',sub_cat=".$allSubCatIds[0]." where id=".$secondSubCatId;
			$result = $db->_execute($query);
		} else {
			foreach($name as $key => $val)
			{
				$query = "INSERT INTO  ace_rp_training_second_sub_categories (name,sub_cat,sub_cat_type) VALUES ('".$val."', ".$allSubCatIds[$key].",".$secondSubCatType[$key].")";	
				$result = $db->_execute($query);
			}
			
		}
		if($result)
			{
				echo "<script>window.close();</script>";
		 			exit();
			}
		exit();
	}

	function getSecondSubCategory()
	{	
		$this->layout='blank';
		$subCatId = $_GET['subCatId'];
		$subCatType = $_GET['subCatType'];
		$allSecondSubCat = $this->TrainingSecondSubCategory->findAll(array('sub_cat' => $subCatId,'sub_cat_type' => $subCatType));
		$this->set('trainingSecondSubCategories', $allSecondSubCat);
	}

	function addSubCatNote()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_training_material (sub_cat_id,notes,type) VALUES (".$id.",'".$note."',2)";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getSubCatNotes()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type FROM ace_rp_training_material WHERE sub_cat_id =".$id." and type=2";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<input type="button" value="View" onclick="getCatNotes('.$row1['id'].');" class="getTechSupport" type-id="'.$row1['type'].'" row-id="'.$row1['id'].'">&nbsp';
		}	
		$res .='</br><div style="margin-top:5px;"><button type="button" name="close" value="Close" onclick=$("#show_subcat_notes_box").dialog("close");>Close</button></td></tr></div>';
		echo $res;
		exit();
	}

	function getSubCatNotesVal()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,notes FROM ace_rp_training_material WHERE id =".$id."";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res = $row1;
		}	
		echo json_encode($res);
		exit();
	}

	function addSubCatVideo()
	{
		$id = $_POST['id'];
		$videoLink = mysql_real_escape_string($_POST['videoLink']);
		$videoLinkName = mysql_real_escape_string($_POST['videoLinkName']);
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_training_material (sub_cat_id,video_link,video_link_name,type) VALUES (".$id.",'".$videoLink."','".$videoLinkName."',8)";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getSubCatVideos()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,video_link,video_link_name FROM ace_rp_training_material WHERE sub_cat_id =".$id." and type=8";
		$res = '<table>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<tr><td><i class="fa fa-trash-o deleteSubCatVideo" row-id="'.$row1['id'].'"></i></td><td><a href="'.$row1['video_link'].'" target="_blank">'.$row1['video_link_name'].'</a></td></tr>
			<tr><td><button type="button" name="close" value="Close" onclick=$("#show_subcat_videos_box").dialog("close");>Close</button></td></tr>
			';
			$i++;
		}	

		$res .= '</table>';
		echo $res;
		exit();
	}

	function subCatImageUpload(){
		$subCatId = $_POST['subcatId'];
		$images = $_FILES['file'];
		$db =& ConnectionManager::getDataSource('default');
		if(!empty($images)){
			foreach ($images['name'] as $key => $value) {
				$fileName = time()."_".$value;
				$fileTmpName = $images['tmp_name'][$key];
				$orgFileName = $value;
				if($images['error'][$key] == 0)
				{
					//$move = $this->saveImages($file, $orgFileName, 90);
					$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/training-images/".$fileName);
					$query = "INSERT INTO ace_rp_training_material (sub_cat_id,document_name,org_document_name,type) VALUES (".$subCatId.",'".$fileName."','".$orgFileName."',4)";

					$result = $db->_execute($query);
				}
			}
		}
		if($result){
			 $output = array(
                        'status' => 'success'
                    );
			}else{
				 $output = array(
                        'status' => 'error'
                    );
			}

		echo json_encode($output);
                exit();
	}

	function subCatDocumentUpload(){
		$subCatId = $_POST['subcatId'];
		$images = $_FILES['file'];
		$db =& ConnectionManager::getDataSource('default');
		if(!empty($images)){
			foreach ($images['name'] as $key => $value) {
				$fileName = time()."_".$value;
				$fileTmpName = $images['tmp_name'][$key];
				$orgFileName = $value;
				if($images['error'][$key] == 0)
				{
					//$move = $this->saveImages($file, $orgFileName, 90);
					$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/training-images/".$fileName);
					$query = "INSERT INTO ace_rp_training_material (sub_cat_id,document_name,org_document_name,type) VALUES (".$subCatId.",'".$fileName."','".$orgFileName."',5)";

					$result = $db->_execute($query);
				}
			}
		}
		if($result){
			 $output = array(
                        'status' => 'success'
                    );
			}else{
				 $output = array(
                        'status' => 'error'
                    );
			}

		echo json_encode($output);
                exit();
	}

	function getSubCatImages()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,document_name FROM ace_rp_training_material WHERE sub_cat_id =".$id." and type=4";
		$res = '<table><tr>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{			
			$res .= '<td><span style="position:absolute;"><img style="height: 15px;width: 12px;" class="delete-subcat-image" src="'.ROOT_URL.'/app/webroot/img/cross-icon.jpeg" image-name="'.$row1["document_name"].'" image-id="'.$row1["id"].'"></span>
                        <img id="" class="invoice-openImg order-images" src="'.ROOT_URL.'/app/webroot/training-images/'.$row1["document_name"].'"></td>';
		}	
		$res .= '</tr><tr><td><button type="button" name="close" value="Close" onclick=$("#show_subcat_images_box").dialog("close");>Close</button></td></tr>
		</table>';
		echo $res;
		exit();
	}

	function getSubCatDocuments()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,document_name FROM ace_rp_training_material WHERE sub_cat_id =".$id." and type=5";
		$res = '<table><tr>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{	

			$res .='<td><span style="position:absolute;"><img style="height: 15px;width: 12px;" class="delete-subcat-doc" src="'.ROOT_URL.'/app/webroot/img/cross-icon.jpeg" image-name="'.$row1['document_name'].'" image-id="'.$row1["id"].'"></span>
                  <img class="show-pdf-image" src="'.ROOT_URL.'/app/webroot/img/doc_pdf.png" data-doc_lnk="/acesys/app/webroot/training-images/'.$row1['document_name'].'" style="height: 70px;width: 70px;"></td>';

			// $res .='<td><a href="/acesys/app/webroot/training-images/'.$row1['document_name'].'"">example</a></td>';
		}	
		$res .= '</tr><tr><td><button type="button" name="close" value="Close" onclick=$("#show_subcat_document_box").dialog("close");>Close</button></td></tr></table>';
		echo $res;
		exit();
	}

	function secondSubCatDocumentUpload()
	{
		error_reporting(E_ALL);
		$secondSubCatId = $_POST['secondSubcatId'];
		$images = $_FILES['file'];
		$db =& ConnectionManager::getDataSource('default');
		if(!empty($images)){
			foreach ($images['name'] as $key => $value) {
				$fileName = time()."_".$value;
				$fileTmpName = $images['tmp_name'][$key];
				$orgFileName = $value;
				if($images['error'][$key] == 0)
				{
					//$move = $this->saveImages($file, $orgFileName, 90);
					$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/training-images/".$fileName);
					$query = "INSERT INTO ace_rp_second_cat_training_material
						 (second_sub_cat,document_name,org_document_name,type) VALUES (".$secondSubCatId.",'".$fileName."','".$orgFileName."',3)";

					$result = $db->_execute($query);
				}
			}
		}
		if($result){
			 $output = array(
                        'status' => 'success'
                    );
			}else{
				 $output = array(
                        'status' => 'error'
                    );
			}
		echo json_encode($output);
                exit();
	}

	function getSecondSubCatDocuments()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,document_name FROM ace_rp_second_cat_training_material WHERE second_sub_cat =".$id." and type=3";
		$res = '<table><tr>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{	

			$res .='<td><span style="position:absolute;"><img style="height: 15px;width: 12px;" class="delete-secondsubcat-doc" src="'.ROOT_URL.'/app/webroot/img/cross-icon.jpeg" image-name="'.$row1['document_name'].'" image-id="'.$row1["id"].'"></span>
                  <img class="show-pdf-image" src="'.ROOT_URL.'/app/webroot/img/doc_pdf.png" data-doc_lnk="/acesys/app/webroot/training-images/'.$row1['document_name'].'" style="height: 70px;width: 70px;"></td>';
		}	
		$res .= '</tr><tr><td><button type="button" name="close" value="Close" onclick=$("#show_second_subcat_document_box").dialog("close");>Close</button></td></tr></table>';
		echo $res;
		exit();
	}

	function secondSubCatImageUpload()
	{
		$secondSubCatId = $_POST['secondSubcatId'];
		$images = $_FILES['file'];
		$db =& ConnectionManager::getDataSource('default');
		if(!empty($images)){
			foreach ($images['name'] as $key => $value) {
				$fileName = time()."_".$value;
				$fileTmpName = $images['tmp_name'][$key];
				$orgFileName = $value;
				if($images['error'][$key] == 0)
				{
					//$move = $this->saveImages($file, $orgFileName, 90);
					$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/training-images/".$fileName);
					$query = "INSERT INTO ace_rp_second_cat_training_material (second_sub_cat,document_name,org_document_name,type) VALUES (".$secondSubCatId.",'".$fileName."','".$orgFileName."',2)";

					$result = $db->_execute($query);
				}
			}
		}
		if($result){
			 $output = array(
                        'status' => 'success'
                    );
			}else{
				 $output = array(
                        'status' => 'error'
                    );
			}

		echo json_encode($output);
                exit();
	}

	function getSecondSubCatImages()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,document_name FROM ace_rp_second_cat_training_material WHERE  second_sub_cat =".$id." and type=2";
		$res = '<table><tr>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{			
			$res .= '<td><span style="position:absolute;"><img style="height: 15px;width: 12px;" class="delete-secondsubcat-image" src="'.ROOT_URL.'/app/webroot/img/cross-icon.jpeg" image-name="'.$row1["document_name"].'" image-id="'.$row1["id"].'"	></span>
                        <img id="" class="invoice-openImg order-images" src="'.ROOT_URL.'/app/webroot/training-images/'.$row1["document_name"].'"></td>';
		}	
		$res .= '</tr><tr><td><button type="button" name="close" value="Close" onclick=$("#show_second_subcat_images_box").dialog("close");>Close</button></td></tr>
		</table>';
		echo $res;
		exit();
	}

	function addSecondSubCatNote()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_second_cat_training_material (second_sub_cat,notes,type) VALUES (".$id.",'".$note."',1)";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getSecondSubCatNotes()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type FROM ace_rp_second_cat_training_material WHERE second_sub_cat =".$id." and type=1";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<input type="button" value="View" onclick="getSecondCatNotes('.$row1['id'].');" class="getTechSupport" type-id="'.$row1['type'].'" row-id="'.$row1['id'].'">&nbsp';
		}	

		$res .='</br><div style="margin-top:5px;"><button type="button" name="close" value="Close" onclick=$("#show_second_subcat_notes_box").dialog("close");>Close</button></td></tr></div>';
		echo $res;
		exit();
	}

	function getSecondSubCatNotesVal()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,notes FROM ace_rp_second_cat_training_material WHERE id =".$id."";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res = $row1;
		}	
		echo json_encode($res);
		exit();
	}

	function getSecondSubCatVideos()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,video_link,video_link_name FROM ace_rp_second_cat_training_material WHERE second_sub_cat =".$id." and type=4";
		$res = '<table>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<tr><td><i class="fa fa-trash-o deleteSecondSubCatVideo" row-id="'.$row1['id'].'"></i></td><td><a href="'.$row1['video_link'].'" target="_blank">'.$row1['video_link_name'].'</a></td></tr>
				<tr><td><button type="button" name="close" value="Close" onclick=$("#show_second_subcat_videos_box").dialog("close");>Close</button></td></tr>';
			$i++;
		}	

		$res .= '</table>';
		echo $res;
		exit();
	}

	function addSecondSubCatVideo()
	{
		$id = $_POST['id'];
		$videoLink = mysql_real_escape_string($_POST['videoLink']);
		$videoLinkName = mysql_real_escape_string($_POST['videoLinkName']);
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_second_cat_training_material (second_sub_cat,video_link,video_link_name,type) VALUES (".$id.",'".$videoLink."','".$videoLinkName."',4)";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteCategory()
	{
		$id = $_POST['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM ace_rp_training_category WHERE id IN (".$id.")";
		$result = $db->_execute($query);

		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	function deleteSubCategory()
	{
		$id = $_POST['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM  ace_rp_training_sub_categories WHERE id IN (".$id.")";
		$result = $db->_execute($query);
		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	function updateSubCatNote()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE  ace_rp_training_material set notes='".$note."' WHERE id=".$id;
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteSubcatImage()
	{
		$id = $_POST['id'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');

		$filename = ROOT.'/app/webroot/training-images/'.$name;
        $query = "DELETE FROM ace_rp_training_material  WHERE id =".$id."";
        $result = $db->_execute($query);
        if (file_exists($filename)) 
        {
            unlink($filename);
        } 

        if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteSecondSubcatImage()
	{
		$id = $_POST['id'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');

		$filename = ROOT.'/app/webroot/training-images/'.$name;
        $query = "DELETE FROM ace_rp_second_cat_training_material  WHERE id =".$id."";
        $result = $db->_execute($query);
        if (file_exists($filename)) 
        {
            unlink($filename);
        } 

        if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteSubcatDoc()
	{
		$id = $_POST['id'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');

		$filename = ROOT.'/app/webroot/training-images/'.$name;
        $query = "DELETE FROM ace_rp_training_material  WHERE id =".$id."";
        $result = $db->_execute($query);
        if (file_exists($filename)) 
        {
            unlink($filename);
        } 

        if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteSecondSubcatDoc()
	{
		$id = $_POST['id'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');

		$filename = ROOT.'/app/webroot/training-images/'.$name;
        $query = "DELETE FROM ace_rp_second_cat_training_material  WHERE id =".$id."";
        $result = $db->_execute($query);
        if (file_exists($filename)) 
        {
            unlink($filename);
        } 

        if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function updateSecondSubCatNote()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE ace_rp_second_cat_training_material set notes='".$note."' WHERE id=".$id;
		$res = $db->_execute($query);
		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getTechSupportDetails()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,tech_support,tech_support_name,sub_cat_id  FROM ace_rp_training_material WHERE id =".$id." and type=1";
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res = $row1;
		}	
		echo json_encode($res);
		exit();
	}

	function editTechSupport()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE ace_rp_training_material set tech_support='".$note."', tech_support_name ='".$name."' WHERE id=".$id;
		$res = $db->_execute($query);
		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteTechSupportDetails()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM ace_rp_training_material WHERE id =".$id."";	
		$result = $db->_execute($query);
		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	function deleteSubCatVideo()
	{
		$id = $_POST['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM  ace_rp_training_material WHERE id =".$id;
		$result = $db->_execute($query);
		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	function deleteSecondSubCatVideo()
	{
		$id = $_POST['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM  ace_rp_second_cat_training_material WHERE id =".$id;
		$result = $db->_execute($query);
		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	function showCities()
	{
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT *  FROM ace_rp_cities order by name";
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res[] = $row1;
		}	

		
		$this->set('cities',$res);
	}
	
	function editCity($id)
	{
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT *  FROM ace_rp_cities where internal_id =".$id;
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res = $row1;
		}	
		$this->set('city',$res);
	}

function updateCity()
	{
		$name = trim($_POST['cityName']);
		$id = $_POST['cityId'];
		$link = $_POST['link'];
		$link1 = $_POST['link1'];
		$link2 = $_POST['link2'];
		$postalCode =  trim($_POST['postalCode']);
		// $color =  trim($_POST['color']);
		$color = str_replace('#', '', trim($_POST['color']));

		$db =& ConnectionManager::getDataSource('default');
		$query1 = "UPDATE ace_rp_cities set name ='".$name."', postal_code = '".$postalCode."',color = '".$color."',link = '".$link."',link1 = '".$link1."',link2 = '".$link2."'   where internal_id =".$id;
		$result1 = $db->_execute($query1);

		if($result1){
			$this->redirect('/settings/showCities');
		}
	}

	function addCity()
	{

	}

	function createCity()
	{
		$name = trim($_POST['cityName']);
		$postalCode =  trim($_POST['postalCode']);
		$color = str_replace('#', '', trim($_POST['color']));
		// $code =  trim($_POST['cityCode']);
		
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "INSERT INTO ace_rp_cities (name,id,active,postal_code,color) VALUES ('".$name."','".$name."',1,'".$postalCode."','".$color."')";
		$result1 = $db->_execute($query1);

		if($result1){
			echo "<script>
			window.opener.location.reload(false);
			window.close();</script>";
			exit();
		}
		exit();
	}

	function deleteCity($id)
	{
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM  ace_rp_cities WHERE internal_id =".$id;
		$result = $db->_execute($query);
		if($result)
		{
			$this->redirect('/settings/showCities');
		}
		exit();
	}

	//-----------------------------------

	function showAdminTrainingCategory(){	
		$allCat = $this->AdminTrainingCategory->findAll();
		$this->set('trainingCategories', $allCat);
	}

	function showAdminTrainingSubCategory()
	{			
		$allSubCat = $this->AdminTrainingSubCategory->findAll();		
		$this->set('trainingSubCategories', $allSubCat);
	}
	
	function showAdminTrainingSecondSubCategory()
	{			
		$allSecondSubCat = $this->AdminTrainingSecondSubCategory->findAll();		
		$this->set('trainingSecondSubCategories', $allSecondSubCat);
	}

	function editAdminTrainingCategory($fromAdd = 0,$id=0) {
		// error_reporting(E_ALL);
		$this->layout = 'blank';	

		if (empty($this->data['TrainingCategory'])) {    

			$this->AdminTrainingCategory->id = $id;    

			$this->data = $this->AdminTrainingCategory->read();	

			$this->set("fromAdd", $fromAdd);
		}

	}

	function adminSave() {
		$fromAdd = $_POST['fromAdd'];
		//if($this->data['IvCategory']['active'] != 1) $this->data['IvCategory']['active'] = 0;
		if($this->AdminTrainingCategory->save($this->data['TrainingCategory'])) {				
			if($fromAdd == 1)
			{
				echo "<script>opener.location.reload();
				window.close();</script>";
		 			exit;
			}
		}	
	}

	function editAdminCategory($id = null) {
		$this->layout = 'blank';
		if (empty($this->data['TrainingCategory'])) {    

			$this->AdminTrainingCategory->id = $id;    

			$cat = $this->AdminTrainingCategory->read();	
			
			$this->set("cat", $cat['TrainingCategory']);
		}
	}


	function updateAdminCategory()
	{
		$id = $_POST['catId'];
		$name = $_POST['catName'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE ace_rp_admin_training_category set name ='".$name."' WHERE id=".$id;
		$result = $db->_execute($query);
		exit();
	}


	function editAdminSubCategory($catId=0,$subCat=0)
	{	
		$db =& ConnectionManager::getDataSource('default');	
		$category = array();
		if($subCat) {
			$query = "SELECT * from ace_rp_admin_training_sub_categories where id=".$subCat;
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
		} else {
			$row = array("id"=>"", "name"=>"", "cat_id"=>$catId);
		}
		$query1 = "SELECT * from ace_rp_admin_training_category";
			$result1 = $db->_execute($query1);
			$category = array();		
			while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
			{
				foreach($row1 as $k => $v)
				{
				  $category[$row1['id']][$k] = $v;
				}	
			}
		
		$this->set('catId', $catId);
		$this->set('subCategory', $row);
		$this->set('categories', $category);
	}

	function addAdminSubCategory($id)
	{	
		$allCatIds = $_POST['allCatId'];
		$catId = $_POST['catId'];
		$name = $_POST['catName'];
		$subCatId = $_POST['subCatId'];
		$db =& ConnectionManager::getDataSource('default');	
		if(!empty($subCatId) || $subCatId != '')
		{
			$query = "UPDATE ace_rp_admin_training_sub_categories set name='".$name[0]."',cat_id=".$allCatIds[0]." where id=".$subCatId;
			$result = $db->_execute($query);
		} else {
			foreach($name as $key => $val)
			{
				$query = "INSERT INTO  ace_rp_admin_training_sub_categories (name,cat_id) VALUES ('".$val."', ".$allCatIds[$key].")";	
				$result = $db->_execute($query);
			}
			
		}
		if($result)
			{
				echo "<script>opener.location.reload();
				window.close();</script>";
		 			exit();
			}
		exit();
	}


	function getAdminSubCategory()
	{	
		$this->layout='blank';
		$catId = $_GET['catId'];
		$allSubCat = $this->AdminTrainingSubCategory->findAll(array('cat_id' => $catId));		
		$this->set('trainingSubCategories', $allSubCat);
	}

	function addAdminTechSupport()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_admin_training_material (sub_cat_id,tech_support,type,tech_support_name) VALUES (".$id.",'".$note."',1,'".$name."')";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getAdminTechSupport()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type FROM ace_rp_admin_training_material WHERE sub_cat_id =".$id." and type=1";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<input type="button" value="View" onclick="getTechSupport('.$row1['id'].');" class="getTechSupport" type-id="'.$row1['type'].'" row-id="'.$row1['id'].'">';
		}	
		echo $res;
		exit();
	}

	function getAdminTechSupportVal()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,tech_support,tech_support_name  FROM ace_rp_admin_training_material WHERE sub_cat_id =".$id." and type=1";
		$res = '<table><tr><th></th><th><b>Name</b></th><th><b>Tech Support</b></th></tr>';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<tr><td><i class="fa fa-pencil-square-o editSubCatTech" row-id="'.$row1['id'].'"></i>&nbsp<i class="fa fa-trash-o deleteSubCatTech" row-id="'.$row1['id'].'"></i></td><td>'.$row1['tech_support_name'].'</td><td><span class="callSubCatNum" number="'.$row1['tech_support'].'">'.$row1['tech_support'].'</span></td></tr>';
			// $res .= $row1['tech_support'].'  ';
		}
		$res .= '<tr><td><button type="button" name="close" value="Close" onclick=$("#show_techsupport_val").dialog("close")>Close</button></td>
    </tr></table>';
		echo json_encode($res);
		exit();
	}

	function editAdminSecondSubCategory($subCatId=0,$subCatType=0,$secondSubCatId=0)
	{	
		$db =& ConnectionManager::getDataSource('default');	
		$subCategory = array();
		if($secondSubCatId) {
			$query = "SELECT * from ace_rp_admin_training_second_sub_categories where id=".$secondSubCatId;
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
		} else {
			$row = array("id"=>"", "name"=>"", "sub_cat"=>"","sub_cat_type"=>"");
		}
		$query1 = "SELECT * from ace_rp_admin_training_sub_categories";
			$result1 = $db->_execute($query1);
			$subCategory = array();		
			while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
			{
				foreach($row1 as $k => $v)
				{
				  $subCategory[$row1['id']][$k] = $v;
				}	
			}
		
		$this->set('subCatId', $subCatId);
		$this->set('secondSubCategory', $row);
		$this->set('secondSubCategoryType', $subCatType);
		$this->set('subCategories', $subCategory);
		
	}

	function addAdminSecondSubCategory($id)
	{	
		$allSubCatIds = $_POST['allSubCatId'];
		$name = $_POST['secondSubCatName'];
		$subCatId = $_POST['subCatId'];
		$secondSubCatId = $_POST['secondSubCatId'];
		$secondSubCatType = $_POST['secondSubCatType'];
		$db =& ConnectionManager::getDataSource('default');	
		if(!empty($secondSubCatId) || $secondSubCatId != '')
		{
			$query = "UPDATE ace_rp_admin_training_second_sub_categories set name='".$name[0]."',sub_cat=".$allSubCatIds[0]." where id=".$secondSubCatId;
			$result = $db->_execute($query);
		} else {
			foreach($name as $key => $val)
			{
				$query = "INSERT INTO  ace_rp_admin_training_second_sub_categories (name,sub_cat,sub_cat_type) VALUES ('".$val."', ".$allSubCatIds[$key].",".$secondSubCatType[$key].")";	
				$result = $db->_execute($query);
			}
			
		}
		if($result)
			{
				echo "<script>window.close();</script>";
		 			exit();
			}
		exit();
	}

	function getAdminSecondSubCategory()
	{	
		$this->layout='blank';
		$subCatId = $_GET['subCatId'];
		$subCatType = $_GET['subCatType'];
		$allSecondSubCat = $this->AdminTrainingSecondSubCategory->findAll(array('sub_cat' => $subCatId,'sub_cat_type' => $subCatType));
		$this->set('trainingSecondSubCategories', $allSecondSubCat);
	}

	function addAdminSubCatNote()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_admin_training_material (sub_cat_id,notes,type) VALUES (".$id.",'".$note."',2)";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getAdminSubCatNotes()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type FROM ace_rp_admin_training_material WHERE sub_cat_id =".$id." and type=2";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<input type="button" value="View" onclick="getCatNotes('.$row1['id'].');" class="getTechSupport" type-id="'.$row1['type'].'" row-id="'.$row1['id'].'">&nbsp';
		}	
		$res .='</br><div style="margin-top:5px;"><button type="button" name="close" value="Close" onclick=$("#show_subcat_notes_box").dialog("close");>Close</button></td></tr></div>';
		echo $res;
		exit();
	}

	function getAdminSubCatNotesVal()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,notes FROM ace_rp_admin_training_material WHERE id =".$id."";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res = $row1;
		}	
		echo json_encode($res);
		exit();
	}

	function addAdminSubCatVideo()
	{
		$id = $_POST['id'];
		$videoLink = mysql_real_escape_string($_POST['videoLink']);
		$videoLinkName = mysql_real_escape_string($_POST['videoLinkName']);
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_admin_training_material (sub_cat_id,video_link,video_link_name,type) VALUES (".$id.",'".$videoLink."','".$videoLinkName."',8)";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getAdminSubCatVideos()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,video_link,video_link_name FROM ace_rp_admin_training_material WHERE sub_cat_id =".$id." and type=8";
		$res = '<table>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<tr><td><i class="fa fa-trash-o deleteSubCatVideo" row-id="'.$row1['id'].'"></i></td><td><a href="'.$row1['video_link'].'" target="_blank">'.$row1['video_link_name'].'</a></td></tr>
			<tr><td><button type="button" name="close" value="Close" onclick=$("#show_subcat_videos_box").dialog("close");>Close</button></td></tr>
			';
			$i++;
		}	

		$res .= '</table>';
		echo $res;
		exit();
	}

	function adminSubCatImageUpload(){
		$subCatId = $_POST['subcatId'];
		$images = $_FILES['file'];
		$db =& ConnectionManager::getDataSource('default');
		if(!empty($images)){
			foreach ($images['name'] as $key => $value) {
				$fileName = time()."_".$value;
				$fileTmpName = $images['tmp_name'][$key];
				$orgFileName = $value;
				if($images['error'][$key] == 0)
				{
					//$move = $this->saveImages($file, $orgFileName, 90);
					$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/training-images/".$fileName);
					$query = "INSERT INTO ace_rp_admin_training_material (sub_cat_id,document_name,org_document_name,type) VALUES (".$subCatId.",'".$fileName."','".$orgFileName."',4)";

					$result = $db->_execute($query);
				}
			}
		}
		if($result){
			 $output = array(
                        'status' => 'success'
                    );
			}else{
				 $output = array(
                        'status' => 'error'
                    );
			}

		echo json_encode($output);
                exit();
	}

	function adminSubCatDocumentUpload(){
		$subCatId = $_POST['subcatId'];
		$images = $_FILES['file'];
		$db =& ConnectionManager::getDataSource('default');
		if(!empty($images)){
			foreach ($images['name'] as $key => $value) {
				$fileName = time()."_".$value;
				$fileTmpName = $images['tmp_name'][$key];
				$orgFileName = $value;
				if($images['error'][$key] == 0)
				{
					//$move = $this->saveImages($file, $orgFileName, 90);
					$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/training-images/".$fileName);
					$query = "INSERT INTO ace_rp_admin_training_material (sub_cat_id,document_name,org_document_name,type) VALUES (".$subCatId.",'".$fileName."','".$orgFileName."',5)";

					$result = $db->_execute($query);
				}
			}
		}
		if($result){
			 $output = array(
                        'status' => 'success'
                    );
			}else{
				 $output = array(
                        'status' => 'error'
                    );
			}

		echo json_encode($output);
                exit();
	}

	function getAdminSubCatImages()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,document_name FROM ace_rp_admin_training_material WHERE sub_cat_id =".$id." and type=4";
		$res = '<table><tr>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{			
			$res .= '<td><span style="position:absolute;"><img style="height: 15px;width: 12px;" class="delete-subcat-image" src="'.ROOT_URL.'/app/webroot/img/cross-icon.jpeg" image-name="'.$row1["document_name"].'" image-id="'.$row1["id"].'"></span>
                        <img id="" class="invoice-openImg order-images" src="'.ROOT_URL.'/app/webroot/training-images/'.$row1["document_name"].'"></td>';
		}	
		$res .= '</tr><tr><td><button type="button" name="close" value="Close" onclick=$("#show_subcat_images_box").dialog("close");>Close</button></td></tr>
		</table>';
		echo $res;
		exit();
	}

	function getAdminSubCatDocuments()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,document_name FROM ace_rp_admin_training_material WHERE sub_cat_id =".$id." and type=5";
		$res = '<table><tr>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{	

			$res .='<td><span style="position:absolute;"><img style="height: 15px;width: 12px;" class="delete-subcat-doc" src="'.ROOT_URL.'/app/webroot/img/cross-icon.jpeg" image-name="'.$row1['document_name'].'" image-id="'.$row1["id"].'"></span>
                  <img class="show-pdf-image" src="'.ROOT_URL.'/app/webroot/img/doc_pdf.png" data-doc_lnk="/acesys/app/webroot/training-images/'.$row1['document_name'].'" style="height: 70px;width: 70px;"></td>';

			// $res .='<td><a href="/acesys/app/webroot/training-images/'.$row1['document_name'].'"">example</a></td>';
		}	
		$res .= '</tr><tr><td><button type="button" name="close" value="Close" onclick=$("#show_subcat_document_box").dialog("close");>Close</button></td></tr></table>';
		echo $res;
		exit();
	}

	function adminSecondSubCatDocumentUpload()
	{
		$secondSubCatId = $_POST['secondSubcatId'];
		$images = $_FILES['file'];
		$db =& ConnectionManager::getDataSource('default');
		if(!empty($images)){
			foreach ($images['name'] as $key => $value) {
				$fileName = time()."_".$value;
				$fileTmpName = $images['tmp_name'][$key];
				$orgFileName = $value;
				if($images['error'][$key] == 0)
				{
					//$move = $this->saveImages($file, $orgFileName, 90);
					$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/training-images/".$fileName);
					$query = "INSERT INTO ace_rp_admin_second_cat_training_material
						 (second_sub_cat,document_name,org_document_name,type) VALUES (".$secondSubCatId.",'".$fileName."','".$orgFileName."',3)";

					$result = $db->_execute($query);
				}
			}
		}
		if($result){
			 $output = array(
                        'status' => 'success'
                    );
			}else{
				 $output = array(
                        'status' => 'error'
                    );
			}
		echo json_encode($output);
                exit();
	}

	function getAdminSecondSubCatDocuments()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,document_name FROM ace_rp_admin_second_cat_training_material WHERE second_sub_cat =".$id." and type=3";
		$res = '<table><tr>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{	

			$res .='<td><span style="position:absolute;"><img style="height: 15px;width: 12px;" class="delete-secondsubcat-doc" src="'.ROOT_URL.'/app/webroot/img/cross-icon.jpeg" image-name="'.$row1['document_name'].'" image-id="'.$row1["id"].'"></span>
                  <img class="show-pdf-image" src="'.ROOT_URL.'/app/webroot/img/doc_pdf.png" data-doc_lnk="/acesys/app/webroot/training-images/'.$row1['document_name'].'" style="height: 70px;width: 70px;"></td>';
		}	
		$res .= '</tr><tr><td><button type="button" name="close" value="Close" onclick=$("#show_second_subcat_document_box").dialog("close");>Close</button></td></tr></table>';
		echo $res;
		exit();
	}

	function adminSecondSubCatImageUpload()
	{
		$secondSubCatId = $_POST['secondSubcatId'];
		$images = $_FILES['file'];
		$db =& ConnectionManager::getDataSource('default');
		if(!empty($images)){
			foreach ($images['name'] as $key => $value) {
				$fileName = time()."_".$value;
				$fileTmpName = $images['tmp_name'][$key];
				$orgFileName = $value;
				if($images['error'][$key] == 0)
				{
					//$move = $this->saveImages($file, $orgFileName, 90);
					$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/training-images/".$fileName);
					$query = "INSERT INTO ace_rp_admin_second_cat_training_material (second_sub_cat,document_name,org_document_name,type) VALUES (".$secondSubCatId.",'".$fileName."','".$orgFileName."',2)";

					$result = $db->_execute($query);
				}
			}
		}
		if($result){
			 $output = array(
                        'status' => 'success'
                    );
			}else{
				 $output = array(
                        'status' => 'error'
                    );
			}

		echo json_encode($output);
                exit();
	}

	function getAdminSecondSubCatImages()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,document_name FROM ace_rp_admin_second_cat_training_material WHERE  second_sub_cat =".$id." and type=2";
		$res = '<table><tr>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{			
			$res .= '<td><span style="position:absolute;"><img style="height: 15px;width: 12px;" class="delete-secondsubcat-image" src="'.ROOT_URL.'/app/webroot/img/cross-icon.jpeg" image-name="'.$row1["document_name"].'" image-id="'.$row1["id"].'"	></span>
                        <img id="" class="invoice-openImg order-images" src="'.ROOT_URL.'/app/webroot/training-images/'.$row1["document_name"].'"></td>';
		}	
		$res .= '</tr><tr><td><button type="button" name="close" value="Close" onclick=$("#show_second_subcat_images_box").dialog("close");>Close</button></td></tr>
		</table>';
		echo $res;
		exit();
	}

	function addAdminSecondSubCatNote()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_admin_second_cat_training_material (second_sub_cat,notes,type) VALUES (".$id.",'".$note."',1)";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getAdminSecondSubCatNotes()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type FROM ace_rp_admin_second_cat_training_material WHERE second_sub_cat =".$id." and type=1";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<input type="button" value="View" onclick="getSecondCatNotes('.$row1['id'].');" class="getTechSupport" type-id="'.$row1['type'].'" row-id="'.$row1['id'].'">&nbsp';
		}	

		$res .='</br><div style="margin-top:5px;"><button type="button" name="close" value="Close" onclick=$("#show_second_subcat_notes_box").dialog("close");>Close</button></td></tr></div>';
		echo $res;
		exit();
	}

	function getAdminSecondSubCatNotesVal()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,notes FROM ace_rp_admin_second_cat_training_material WHERE id =".$id."";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res = $row1;
		}	
		echo json_encode($res);
		exit();
	}

	function getAdminSecondSubCatVideos()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,video_link,video_link_name FROM ace_rp_admin_second_cat_training_material WHERE second_sub_cat =".$id." and type=4";
		$res = '<table>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<tr><td><i class="fa fa-trash-o deleteSecondSubCatVideo" row-id="'.$row1['id'].'"></i></td><td><a href="'.$row1['video_link'].'" target="_blank">'.$row1['video_link_name'].'</a></td></tr>
				<tr><td><button type="button" name="close" value="Close" onclick=$("#show_second_subcat_videos_box").dialog("close");>Close</button></td></tr>';
			$i++;
		}	

		$res .= '</table>';
		echo $res;
		exit();
	}

	function addAdminSecondSubCatVideo()
	{
		$id = $_POST['id'];
		$videoLink = mysql_real_escape_string($_POST['videoLink']);
		$videoLinkName = mysql_real_escape_string($_POST['videoLinkName']);
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_admin_second_cat_training_material (second_sub_cat,video_link,video_link_name,type) VALUES (".$id.",'".$videoLink."','".$videoLinkName."',4)";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteAdminCategory()
	{
		$id = $_POST['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM ace_rp_admin_training_category WHERE id IN (".$id.")";
		$result = $db->_execute($query);

		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	function deleteAdminSubCategory()
	{
		$id = $_POST['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM  ace_rp_admin_training_sub_categories WHERE id IN (".$id.")";
		$result = $db->_execute($query);
		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	function updateAdminSubCatNote()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE  ace_rp_admin_training_material set notes='".$note."' WHERE id=".$id;
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteAdminSubcatImage()
	{
		$id = $_POST['id'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');

		$filename = ROOT.'/app/webroot/training-images/'.$name;
        $query = "DELETE FROM ace_rp_admin_training_material  WHERE id =".$id."";
        $result = $db->_execute($query);
        if (file_exists($filename)) 
        {
            unlink($filename);
        } 

        if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteAdminSecondSubcatImage()
	{
		$id = $_POST['id'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');

		$filename = ROOT.'/app/webroot/training-images/'.$name;
        $query = "DELETE FROM ace_rp_admin_second_cat_training_material  WHERE id =".$id."";
        $result = $db->_execute($query);
        if (file_exists($filename)) 
        {
            unlink($filename);
        } 

        if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteAdminSubcatDoc()
	{
		$id = $_POST['id'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');

		$filename = ROOT.'/app/webroot/training-images/'.$name;
        $query = "DELETE FROM ace_rp_admin_training_material  WHERE id =".$id."";
        $result = $db->_execute($query);
        if (file_exists($filename)) 
        {
            unlink($filename);
        } 

        if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteAdminSecondSubcatDoc()
	{
		$id = $_POST['id'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');

		$filename = ROOT.'/app/webroot/training-images/'.$name;
        $query = "DELETE FROM ace_rp_admin_second_cat_training_material  WHERE id =".$id."";
        $result = $db->_execute($query);
        if (file_exists($filename)) 
        {
            unlink($filename);
        } 

        if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function updateAdminSecondSubCatNote()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE ace_rp_admin_second_cat_training_material set notes='".$note."' WHERE id=".$id;
		$res = $db->_execute($query);
		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getAdminTechSupportDetails()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,tech_support,tech_support_name,sub_cat_id  FROM ace_rp_admin_training_material WHERE id =".$id." and type=1";
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res = $row1;
		}	
		echo json_encode($res);
		exit();
	}

	function editAdminTechSupport()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE ace_rp_admin_training_material set tech_support='".$note."', tech_support_name ='".$name."' WHERE id=".$id;
		$res = $db->_execute($query);
		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteAdminTechSupportDetails()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM ace_rp_admin_training_material WHERE id =".$id."";	
		$result = $db->_execute($query);
		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	function deleteAdminSubCatVideo()
	{
		$id = $_POST['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM  ace_rp_admin_training_material WHERE id =".$id;
		$result = $db->_execute($query);
		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	function deleteAdminSecondSubCatVideo()
	{
		$id = $_POST['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM  ace_rp_admin_second_cat_training_material WHERE id =".$id;
		$result = $db->_execute($query);
		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	function showTechTrainingCategory()
	{			
		$allCat = $this->TechsTrainingCategory->findAll();
		$this->set('trainingCategories', $allCat);
	}

	function showTechTrainingSubCategory()
	{			
		$allSubCat = $this->TechsTrainingSubCategory->findAll();		
		$this->set('trainingSubCategories', $allSubCat);
	}
	
	function showTechTrainingSecondSubCategory()
	{			
		$allSecondSubCat = $this->TechsTrainingSecondSubCategory->findAll();		
		$this->set('trainingSecondSubCategories', $allSecondSubCat);
	}

	function editTechsTrainingCategory($fromAdd = 0,$id=0) {
		// error_reporting(E_ALL);
		$this->layout = 'blank';	

		if (empty($this->data['TrainingCategory'])) {    

			$this->TechsTrainingCategory->id = $id;    

			$this->data = $this->TechsTrainingCategory->read();	

			$this->set("fromAdd", $fromAdd);
		}

	}

	function techSave() {
		$fromAdd = $_POST['fromAdd'];
		//if($this->data['IvCategory']['active'] != 1) $this->data['IvCategory']['active'] = 0;
		if($this->TechsTrainingCategory->save($this->data['TechsTrainingCategory'])) {				
			if($fromAdd == 1)
			{
				echo "<script>opener.location.reload();
				window.close();</script>";
		 			exit;
			}
		}	
	}

	function editTechsCategory($id = null) {
		$this->layout = 'blank';
		if (empty($this->data['TrainingCategory'])) {    

			$this->TechsTrainingCategory->id = $id;    

			$cat = $this->TechsTrainingCategory->read();	
			
			$this->set("cat", $cat['TrainingCategory']);
		}
	}


	function updateTechsCategory()
	{
		$id = $_POST['catId'];
		$name = $_POST['catName'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE ace_rp_techs_training_category set name ='".$name."' WHERE id=".$id;
		$result = $db->_execute($query);
		exit();
	}


	function editTechsSubCategory($catId=0,$subCat=0)
	{	
		$db =& ConnectionManager::getDataSource('default');	
		$category = array();
		if($subCat) {
			$query = "SELECT * from ace_rp_techs_training_sub_categories where id=".$subCat;
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
		} else {
			$row = array("id"=>"", "name"=>"", "cat_id"=>$catId);
		}
		$query1 = "SELECT * from ace_rp_techs_training_category";
			$result1 = $db->_execute($query1);
			$category = array();		
			while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
			{
				foreach($row1 as $k => $v)
				{
				  $category[$row1['id']][$k] = $v;
				}	
			}
		
		$this->set('catId', $catId);
		$this->set('subCategory', $row);
		$this->set('categories', $category);
	}

	function addTechsSubCategory($id)
	{	
		$allCatIds = $_POST['allCatId'];
		$catId = $_POST['catId'];
		$name = $_POST['catName'];
		$subCatId = $_POST['subCatId'];
		$db =& ConnectionManager::getDataSource('default');	
		if(!empty($subCatId) || $subCatId != '')
		{
			$query = "UPDATE ace_rp_techs_training_sub_categories set name='".$name[0]."',cat_id=".$allCatIds[0]." where id=".$subCatId;
			$result = $db->_execute($query);
		} else {
			foreach($name as $key => $val)
			{
				$query = "INSERT INTO  ace_rp_techs_training_sub_categories (name,cat_id) VALUES ('".$val."', ".$allCatIds[$key].")";	
				$result = $db->_execute($query);
			}
			
		}
		if($result)
			{
				echo "<script>opener.location.reload();
				window.close();</script>";
		 			exit();
			}
		exit();
	}


	function getTechsSubCategory()
	{	
		$this->layout='blank';
		$catId = $_GET['catId'];
		$allSubCat = $this->TechsTrainingSubCategory->findAll(array('cat_id' => $catId));		
		$this->set('trainingSubCategories', $allSubCat);
	}

	function addTechsTechSupport()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_techs_training_material (sub_cat_id,tech_support,type,tech_support_name) VALUES (".$id.",'".$note."',1,'".$name."')";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getTechsTechSupport()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type FROM ace_rp_techs_training_material WHERE sub_cat_id =".$id." and type=1";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<input type="button" value="View" onclick="getTechSupport('.$row1['id'].');" class="getTechSupport" type-id="'.$row1['type'].'" row-id="'.$row1['id'].'">';
		}	
		echo $res;
		exit();
	}

	function getTechsTechSupportVal()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,tech_support,tech_support_name  FROM ace_rp_techs_training_material WHERE sub_cat_id =".$id." and type=1";
		$res = '<table><tr><th></th><th><b>Name</b></th><th><b>Tech Support</b></th></tr>';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<tr><td><i class="fa fa-pencil-square-o editSubCatTech" row-id="'.$row1['id'].'"></i>&nbsp<i class="fa fa-trash-o deleteSubCatTech" row-id="'.$row1['id'].'"></i></td><td>'.$row1['tech_support_name'].'</td><td><span class="callSubCatNum" number="'.$row1['tech_support'].'">'.$row1['tech_support'].'</span></td></tr>';
			// $res .= $row1['tech_support'].'  ';
		}
		$res .= '<tr><td><button type="button" name="close" value="Close" onclick=$("#show_techsupport_val").dialog("close")>Close</button></td>
    </tr></table>';
		echo json_encode($res);
		exit();
	}

	function editTechsSecondSubCategory($subCatId=0,$subCatType=0,$secondSubCatId=0)
	{	
		$db =& ConnectionManager::getDataSource('default');	
		$subCategory = array();
		if($secondSubCatId) {
			$query = "SELECT * from ace_rp_techs_training_second_sub_categories where id=".$secondSubCatId;
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
		} else {
			$row = array("id"=>"", "name"=>"", "sub_cat"=>"","sub_cat_type"=>"");
		}
		$query1 = "SELECT * from ace_rp_techs_training_sub_categories";
			$result1 = $db->_execute($query1);
			$subCategory = array();		
			while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
			{
				foreach($row1 as $k => $v)
				{
				  $subCategory[$row1['id']][$k] = $v;
				}	
			}
		
		$this->set('subCatId', $subCatId);
		$this->set('secondSubCategory', $row);
		$this->set('secondSubCategoryType', $subCatType);
		$this->set('subCategories', $subCategory);
		
	}

	function addTechsSecondSubCategory($id)
	{	
		$allSubCatIds = $_POST['allSubCatId'];
		$name = $_POST['secondSubCatName'];
		$subCatId = $_POST['subCatId'];
		$secondSubCatId = $_POST['secondSubCatId'];
		$secondSubCatType = $_POST['secondSubCatType'];
		$db =& ConnectionManager::getDataSource('default');	
		if(!empty($secondSubCatId) || $secondSubCatId != '')
		{
			$query = "UPDATE ace_rp_techs_training_second_sub_categories set name='".$name[0]."',sub_cat=".$allSubCatIds[0]." where id=".$secondSubCatId;
			$result = $db->_execute($query);
		} else {
			foreach($name as $key => $val)
			{
				$query = "INSERT INTO  ace_rp_techs_training_second_sub_categories (name,sub_cat,sub_cat_type) VALUES ('".$val."', ".$allSubCatIds[$key].",".$secondSubCatType[$key].")";	
				$result = $db->_execute($query);
			}
			
		}
		if($result)
			{
				echo "<script>window.close();</script>";
		 			exit();
			}
		exit();
	}

	function getTechsSecondSubCategory()
	{	
		$this->layout='blank';
		$subCatId = $_GET['subCatId'];
		$subCatType = $_GET['subCatType'];
		$allSecondSubCat = $this->TechsTrainingSecondSubCategory->findAll(array('sub_cat' => $subCatId,'sub_cat_type' => $subCatType));
		$this->set('trainingSecondSubCategories', $allSecondSubCat);
	}

	function addTechsSubCatNote()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_techs_training_material (sub_cat_id,notes,type) VALUES (".$id.",'".$note."',2)";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getTechsSubCatNotes()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type FROM ace_rp_techs_training_material WHERE sub_cat_id =".$id." and type=2";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<input type="button" value="View" onclick="getCatNotes('.$row1['id'].');" class="getTechSupport" type-id="'.$row1['type'].'" row-id="'.$row1['id'].'">&nbsp';
		}	
		$res .='</br><div style="margin-top:5px;"><button type="button" name="close" value="Close" onclick=$("#show_subcat_notes_box").dialog("close");>Close</button></td></tr></div>';
		echo $res;
		exit();
	}

	function getTechsSubCatNotesVal()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,notes FROM ace_rp_techs_training_material WHERE id =".$id."";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res = $row1;
		}	
		echo json_encode($res);
		exit();
	}

	function addTechsSubCatVideo()
	{
		$id = $_POST['id'];
		$videoLink = mysql_real_escape_string($_POST['videoLink']);
		$videoLinkName = mysql_real_escape_string($_POST['videoLinkName']);
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_techs_training_material (sub_cat_id,video_link,video_link_name,type) VALUES (".$id.",'".$videoLink."','".$videoLinkName."',8)";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getTechsSubCatVideos()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,video_link,video_link_name FROM ace_rp_techs_training_material WHERE sub_cat_id =".$id." and type=8";
		$res = '<table>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<tr><td><i class="fa fa-trash-o deleteSubCatVideo" row-id="'.$row1['id'].'"></i></td><td><a href="'.$row1['video_link'].'" target="_blank">'.$row1['video_link_name'].'</a></td></tr>
			<tr><td><button type="button" name="close" value="Close" onclick=$("#show_subcat_videos_box").dialog("close");>Close</button></td></tr>
			';
			$i++;
		}	

		$res .= '</table>';
		echo $res;
		exit();
	}

	function techsSubCatImageUpload(){
		$subCatId = $_POST['subcatId'];
		$images = $_FILES['file'];
		$db =& ConnectionManager::getDataSource('default');
		if(!empty($images)){
			foreach ($images['name'] as $key => $value) {
				$fileName = time().'_'.rand()."_".$value;
				$fileTmpName = $images['tmp_name'][$key];
				$orgFileName = $value;
				if($images['error'][$key] == 0)
				{
					//$move = $this->saveImages($file, $orgFileName, 90);
					$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/training-images/".$fileName);
					$query = "INSERT INTO ace_rp_techs_training_material (sub_cat_id,document_name,org_document_name,type) VALUES (".$subCatId.",'".$fileName."','".$orgFileName."',4)";

					$result = $db->_execute($query);
				}
			}
		}
		if($result){
			 $output = array(
                        'status' => 'success'
                    );
			}else{
				 $output = array(
                        'status' => 'error'
                    );
			}

		echo json_encode($output);
                exit();
	}

	function techsSubCatDocumentUpload(){
		$subCatId = $_POST['subcatId'];
		$images = $_FILES['file'];
		$db =& ConnectionManager::getDataSource('default');
		if(!empty($images)){
			foreach ($images['name'] as $key => $value) {
				$fileName = time()."_".$value;
				$fileTmpName = $images['tmp_name'][$key];
				$orgFileName = $value;
				if($images['error'][$key] == 0)
				{
					//$move = $this->saveImages($file, $orgFileName, 90);
					$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/training-images/".$fileName);
					$query = "INSERT INTO ace_rp_techs_training_material (sub_cat_id,document_name,org_document_name,type) VALUES (".$subCatId.",'".$fileName."','".$orgFileName."',5)";

					$result = $db->_execute($query);
				}
			}
		}
		if($result){
			 $output = array(
                        'status' => 'success'
                    );
			}else{
				 $output = array(
                        'status' => 'error'
                    );
			}

		echo json_encode($output);
                exit();
	}

	function getTechsSubCatImages()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,document_name FROM ace_rp_techs_training_material WHERE sub_cat_id =".$id." and type=4";
		$res = '<table><tr>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{			
			$res .= '<td><span style="position:absolute;"><img style="height: 15px;width: 12px;" class="delete-subcat-image" src="'.ROOT_URL.'/app/webroot/img/cross-icon.jpeg" image-name="'.$row1["document_name"].'" image-id="'.$row1["id"].'"></span>
                        <img id="" class="invoice-openImg order-images" src="'.ROOT_URL.'/app/webroot/training-images/'.$row1["document_name"].'"></td>';
		}	
		$res .= '</tr><tr><td><button type="button" name="close" value="Close" onclick=$("#show_subcat_images_box").dialog("close");>Close</button></td></tr>
		</table>';
		echo $res;
		exit();
	}

	function getTechsSubCatDocuments()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,document_name FROM ace_rp_techs_training_material WHERE sub_cat_id =".$id." and type=5";
		$res = '<table><tr>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{	

			$res .='<td><span style="position:absolute;"><img style="height: 15px;width: 12px;" class="delete-subcat-doc" src="'.ROOT_URL.'/app/webroot/img/cross-icon.jpeg" image-name="'.$row1['document_name'].'" image-id="'.$row1["id"].'"></span>
                  <img class="show-pdf-image" src="'.ROOT_URL.'/app/webroot/img/doc_pdf.png" data-doc_lnk="/acesys/app/webroot/training-images/'.$row1['document_name'].'" style="height: 70px;width: 70px;"></td>';

			// $res .='<td><a href="/acesys/app/webroot/training-images/'.$row1['document_name'].'"">example</a></td>';
		}	
		$res .= '</tr><tr><td><button type="button" name="close" value="Close" onclick=$("#show_subcat_document_box").dialog("close");>Close</button></td></tr></table>';
		echo $res;
		exit();
	}

	function techsSecondSubCatDocumentUpload()
	{
		$secondSubCatId = $_POST['secondSubcatId'];
		$images = $_FILES['file'];
		$db =& ConnectionManager::getDataSource('default');
		if(!empty($images)){
			foreach ($images['name'] as $key => $value) {
				$fileName = time()."_".$value;
				$fileTmpName = $images['tmp_name'][$key];
				$orgFileName = $value;
				if($images['error'][$key] == 0)
				{
					//$move = $this->saveImages($file, $orgFileName, 90);
					$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/training-images/".$fileName);
					$query = "INSERT INTO ace_rp_techs_second_cat_training_material
						 (second_sub_cat,document_name,org_document_name,type) VALUES (".$secondSubCatId.",'".$fileName."','".$orgFileName."',3)";

					$result = $db->_execute($query);
				}
			}
		}
		if($result){
			 $output = array(
                        'status' => 'success'
                    );
			}else{
				 $output = array(
                        'status' => 'error'
                    );
			}
		echo json_encode($output);
                exit();
	}

	function getTechsSecondSubCatDocuments()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,document_name FROM ace_rp_techs_second_cat_training_material WHERE second_sub_cat =".$id." and type=3";
		$res = '<table><tr>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{	

			$res .='<td><span style="position:absolute;"><img style="height: 15px;width: 12px;" class="delete-secondsubcat-doc" src="'.ROOT_URL.'/app/webroot/img/cross-icon.jpeg" image-name="'.$row1['document_name'].'" image-id="'.$row1["id"].'"></span>
                  <img class="show-pdf-image" src="'.ROOT_URL.'/app/webroot/img/doc_pdf.png" data-doc_lnk="/acesys/app/webroot/training-images/'.$row1['document_name'].'" style="height: 70px;width: 70px;"></td>';
		}	
		$res .= '</tr><tr><td><button type="button" name="close" value="Close" onclick=$("#show_second_subcat_document_box").dialog("close");>Close</button></td></tr></table>';
		echo $res;
		exit();
	}

	function techsSecondSubCatImageUpload()
	{
		$secondSubCatId = $_POST['secondSubcatId'];
		$images = $_FILES['file'];
		$db =& ConnectionManager::getDataSource('default');
		if(!empty($images)){
			foreach ($images['name'] as $key => $value) {
				$fileName = time()."_".$value;
				$fileTmpName = $images['tmp_name'][$key];
				$orgFileName = $value;
				if($images['error'][$key] == 0)
				{
					//$move = $this->saveImages($file, $orgFileName, 90);
					$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/training-images/".$fileName);
					$query = "INSERT INTO ace_rp_techs_second_cat_training_material (second_sub_cat,document_name,org_document_name,type) VALUES (".$secondSubCatId.",'".$fileName."','".$orgFileName."',2)";

					$result = $db->_execute($query);
				}
			}
		}
		if($result){
			 $output = array(
                        'status' => 'success'
                    );
			}else{
				 $output = array(
                        'status' => 'error'
                    );
			}

		echo json_encode($output);
                exit();
	}

	function getTechsSecondSubCatImages()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,document_name FROM ace_rp_techs_second_cat_training_material WHERE  second_sub_cat =".$id." and type=2";
		$res = '<table><tr>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{			
			$res .= '<td><span style="position:absolute;"><img style="height: 15px;width: 12px;" class="delete-secondsubcat-image" src="'.ROOT_URL.'/app/webroot/img/cross-icon.jpeg" image-name="'.$row1["document_name"].'" image-id="'.$row1["id"].'"	></span>
                        <img id="" class="invoice-openImg order-images" src="'.ROOT_URL.'/app/webroot/training-images/'.$row1["document_name"].'"></td>';
		}	
		$res .= '</tr><tr><td><button type="button" name="close" value="Close" onclick=$("#show_second_subcat_images_box").dialog("close");>Close</button></td></tr>
		</table>';
		echo $res;
		exit();
	}

	function addTechsSecondSubCatNote()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_techs_second_cat_training_material (second_sub_cat,notes,type) VALUES (".$id.",'".$note."',1)";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getTechsSecondSubCatNotes()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type FROM ace_rp_techs_second_cat_training_material WHERE second_sub_cat =".$id." and type=1";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<input type="button" value="View" onclick="getSecondCatNotes('.$row1['id'].');" class="getTechSupport" type-id="'.$row1['type'].'" row-id="'.$row1['id'].'">&nbsp';
		}	

		$res .='</br><div style="margin-top:5px;"><button type="button" name="close" value="Close" onclick=$("#show_second_subcat_notes_box").dialog("close");>Close</button></td></tr></div>';
		echo $res;
		exit();
	}

	function getTechsSecondSubCatNotesVal()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,notes FROM ace_rp_techs_second_cat_training_material WHERE id =".$id."";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res = $row1;
		}	
		echo json_encode($res);
		exit();
	}

	function getTechsSecondSubCatVideos()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,video_link,video_link_name FROM ace_rp_techs_second_cat_training_material WHERE second_sub_cat =".$id." and type=4";
		$res = '<table>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<tr><td><i class="fa fa-trash-o deleteSecondSubCatVideo" row-id="'.$row1['id'].'"></i></td><td><a href="'.$row1['video_link'].'" target="_blank">'.$row1['video_link_name'].'</a></td></tr>
				<tr><td><button type="button" name="close" value="Close" onclick=$("#show_second_subcat_videos_box").dialog("close");>Close</button></td></tr>';
			$i++;
		}	

		$res .= '</table>';
		echo $res;
		exit();
	}

	function addTechsSecondSubCatVideo()
	{
		$id = $_POST['id'];
		$videoLink = mysql_real_escape_string($_POST['videoLink']);
		$videoLinkName = mysql_real_escape_string($_POST['videoLinkName']);
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_techs_second_cat_training_material (second_sub_cat,video_link,video_link_name,type) VALUES (".$id.",'".$videoLink."','".$videoLinkName."',4)";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteTechsCategory()
	{
		$id = $_POST['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM ace_rp_techs_training_category WHERE id IN (".$id.")";
		$result = $db->_execute($query);

		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	function deleteTechsSubCategory()
	{
		$id = $_POST['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM  ace_rp_techs_training_sub_categories WHERE id IN (".$id.")";
		$result = $db->_execute($query);
		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	function updateTechsSubCatNote()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE  ace_rp_techs_training_material set notes='".$note."' WHERE id=".$id;
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteTechsSubcatImage()
	{
		$id = $_POST['id'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');

		$filename = ROOT.'/app/webroot/training-images/'.$name;
        $query = "DELETE FROM ace_rp_techs_training_material  WHERE id =".$id."";
        $result = $db->_execute($query);
        if (file_exists($filename)) 
        {
            unlink($filename);
        } 

        if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteTechsSecondSubcatImage()
	{
		$id = $_POST['id'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');

		$filename = ROOT.'/app/webroot/training-images/'.$name;
        $query = "DELETE FROM ace_rp_techs_second_cat_training_material  WHERE id =".$id."";
        $result = $db->_execute($query);
        if (file_exists($filename)) 
        {
            unlink($filename);
        } 

        if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteTechsSubcatDoc()
	{
		$id = $_POST['id'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');

		$filename = ROOT.'/app/webroot/training-images/'.$name;
        $query = "DELETE FROM ace_rp_techs_training_material  WHERE id =".$id."";
        $result = $db->_execute($query);
        if (file_exists($filename)) 
        {
            unlink($filename);
        } 

        if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteTechsSecondSubcatDoc()
	{
		$id = $_POST['id'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');

		$filename = ROOT.'/app/webroot/training-images/'.$name;
        $query = "DELETE FROM ace_rp_techs_second_cat_training_material  WHERE id =".$id."";
        $result = $db->_execute($query);
        if (file_exists($filename)) 
        {
            unlink($filename);
        } 

        if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function updateTechsSecondSubCatNote()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE ace_rp_techs_second_cat_training_material set notes='".$note."' WHERE id=".$id;
		$res = $db->_execute($query);
		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getTechsTechSupportDetails()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,tech_support,tech_support_name,sub_cat_id  FROM ace_rp_techs_training_material WHERE id =".$id." and type=1";
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res = $row1;
		}	
		echo json_encode($res);
		exit();
	}

	function editTechsTechSupport()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE ace_rp_techs_training_material set tech_support='".$note."', tech_support_name ='".$name."' WHERE id=".$id;
		$res = $db->_execute($query);
		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteTechsTechSupportDetails()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM ace_rp_techs_training_material WHERE id =".$id."";	
		$result = $db->_execute($query);
		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	function deleteTechsSubCatVideo()
	{
		$id = $_POST['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM  ace_rp_techs_training_material WHERE id =".$id;
		$result = $db->_execute($query);
		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	function deleteTechsSecondSubCatVideo()
	{
		$id = $_POST['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM  ace_rp_techs_second_cat_training_material WHERE id =".$id;
		$result = $db->_execute($query);
		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	/*Agent trainig start*/


	function showAgentTrainingCategory()
	{			
		$allCat = $this->AgentTrainingCategory->findAll();
		$this->set('trainingCategories', $allCat);
	}

	function showAgentTrainingSubCategory()
	{			
		$allSubCat = $this->AgentTrainingSubCategory->findAll();		
		$this->set('trainingSubCategories', $allSubCat);
	}
	
	function showAgentTrainingSecondSubCategory()
	{			
		$allSecondSubCat = $this->AgentTrainingSecondSubCategory->findAll();		
		$this->set('trainingSecondSubCategories', $allSecondSubCat);
	}

	function editAgentTrainingCategory($fromAdd = 0,$id=0) {
		// error_reporting(E_ALL);
		$this->layout = 'blank';	
		
		if (empty($this->data['TrainingCategory'])) {    

			$this->AgentTrainingCategory->id = $id;    

			$this->data = $this->AgentTrainingCategory->read();	

			$this->set("fromAdd", $fromAdd);
		}

	}

	function agentSave() {
		$fromAdd = $_POST['fromAdd'];
		//if($this->data['IvCategory']['active'] != 1) $this->data['IvCategory']['active'] = 0;
		// print_r($this->data['TrainingCategory']);
		// die('hello');
		if($this->AgentTrainingCategory->save($this->data['AgentTrainingCategory'])) {				
			if($fromAdd == 1)
			{
				echo "<script>opener.location.reload();
				window.close();</script>";
		 			exit;
			}
		}	
	}

	function editAgentCategory($id = null) {
		$this->layout = 'blank';
		if (empty($this->data['TrainingCategory'])) {    

			$this->AgentTrainingCategory->id = $id;    

			$cat = $this->AgentTrainingCategory->read();	
			
			$this->set("cat", $cat['TrainingCategory']);
		}
	}


	function updateAgentCategory()
	{
		$id = $_POST['catId'];
		$name = $_POST['catName'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE ace_rp_agent_training_category set name ='".$name."' WHERE id=".$id;
		$result = $db->_execute($query);
		exit();
	}


	function editAgentSubCategory($catId=0,$subCat=0)
	{	
		$db =& ConnectionManager::getDataSource('default');	
		$category = array();
		if($subCat) {
			$query = "SELECT * from ace_rp_agent_training_sub_categories where id=".$subCat;
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
		} else {
			$row = array("id"=>"", "name"=>"", "cat_id"=>$catId);
		}
		$query1 = "SELECT * from ace_rp_agent_training_category";
			$result1 = $db->_execute($query1);
			$category = array();		
			while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
			{
				foreach($row1 as $k => $v)
				{
				  $category[$row1['id']][$k] = $v;
				}	
			}
		
		$this->set('catId', $catId);
		$this->set('subCategory', $row);
		$this->set('categories', $category);
	}

	function addAgentSubCategory($id)
	{	
		$allCatIds = $_POST['allCatId'];
		$catId = $_POST['catId'];
		$name = $_POST['catName'];
		$subCatId = $_POST['subCatId'];
		$db =& ConnectionManager::getDataSource('default');	
		if(!empty($subCatId) || $subCatId != '')
		{
			$query = "UPDATE ace_rp_agent_training_sub_categories set name='".$name[0]."',cat_id=".$allCatIds[0]." where id=".$subCatId;
			$result = $db->_execute($query);
		} else {
			foreach($name as $key => $val)
			{
				$query = "INSERT INTO  ace_rp_agent_training_sub_categories (name,cat_id) VALUES ('".$val."', ".$allCatIds[$key].")";	
				$result = $db->_execute($query);
			}
			
		}
		if($result)
			{
				echo "<script>opener.location.reload();
				window.close();</script>";
		 			exit();
			}
		exit();
	}


	function getAgentSubCategory()
	{	
		$db =& ConnectionManager::getDataSource('default');
		$this->layout='blank';
		$catId = $_GET['catId'];
		$allSubCat = $this->AgentTrainingSubCategory->findAll(array('cat_id' => $catId));
		//echo "<pre>";
		//print_r($allSubCat);
		//die();
		$get_data = array();
		foreach ($allSubCat as $obj) {
			$cat_id=$obj['AgentTrainingSubCategory']['id'];
			$query = "select * from ace_rp_agent_training_material where sub_cat_id='$cat_id'";
			$result1 = $db->_execute($query);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$get_data[]=$row1;
			
		}	
			
			
			$this->set('get_data', $get_data);
	}
		$this->set('trainingSubCategories', $allSubCat);
	}

	function addAgentTechSupport()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_agent_training_material (sub_cat_id,tech_support,type,tech_support_name) VALUES (".$id.",'".$note."',1,'".$name."')";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getAgentTechSupport()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type FROM ace_rp_agent_training_material WHERE sub_cat_id =".$id." and type=1";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<input type="button" value="View" onclick="getTechSupport('.$row1['id'].');" class="getTechSupport" type-id="'.$row1['type'].'" row-id="'.$row1['id'].'">';
		}	
		echo $res;
		exit();
	}

	function getAgentTechSupportVal()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,tech_support,tech_support_name  FROM ace_rp_agent_training_material WHERE sub_cat_id =".$id." and type=1";
		$res = '<table><tr><th></th><th><b>Name</b></th><th><b>Tech Support</b></th></tr>';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<tr><td><i class="fa fa-pencil-square-o editSubCatTech" row-id="'.$row1['id'].'"></i>&nbsp<i class="fa fa-trash-o deleteSubCatTech" row-id="'.$row1['id'].'"></i></td><td>'.$row1['tech_support_name'].'</td><td><span class="callSubCatNum" number="'.$row1['tech_support'].'">'.$row1['tech_support'].'</span></td></tr>';
			// $res .= $row1['tech_support'].'  ';
		}
		$res .= '<tr><td><button type="button" name="close" value="Close" onclick=$("#show_techsupport_val").dialog("close")>Close</button></td>
    </tr></table>';
		echo json_encode($res);
		exit();
	}
	function upload_picture(){
		
		if(isset($_FILES['upload']['name']))
{
 $file = $_FILES['upload']['tmp_name'];
 date_default_timezone_set('America/Los_Angeles');

		$year = date('Y', time());
		if (!file_exists($year)) {
			mkdir('upload_photos/'.$year, 0755);
		}
		$month = date('Y/m', time());
		if (!file_exists($month)) {
			mkdir('upload_photos/'.$month, 0755);
		}

		$day = date('Y/m/d', time());
		if (!file_exists($day)) {
			mkdir('upload_photos/'.$day, 0755);
		}
		$path = $file_name;
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		if($order_id != 0)
		{
			$name = date('Ymdhis', time()).$order_id.$i.'_'.rand().$path;
		} 
		else {
			$name = date('Ymdhis', time()).'_'.rand().$path;
		}
			
		if ( 0 < $file['error'] ) {
	        echo 'Error: ' . $_FILES['image']['error'] . '<br>'; 
	    } else {
			$extensions=array('jpg','jpe','jpeg','jfif','png','bmp','dib','gif');
if(in_array($ext,$extensions)){     
$maxDimW = 800;
$maxDimH = 500;
list($width, $height, $type, $attr) = getimagesize( $file_tmpname );
if ( $maxDimW > $maxDimW || $height > $maxDimH ) {
    $target_filename = $file_tmpname;
    $fn = $file_tmpname;
    $size = getimagesize( $fn );
    $ratio = $size[0]/$size[1]; // width/height
    
       $width =$width/4;
       $height=$height/4;
    
    $src = imagecreatefromstring(file_get_contents($fn));
    $dst = imagecreatetruecolor( $width, $height );
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $size[0], $size[1] );

    imagejpeg($dst, $target_filename); // adjust format as needed
	
    


}
}
			
	        move_uploaded_file($file, 'upload_photos/'.$day.'/'.$name);
  $function_number = $_GET['CKEditorFuncNum'];
  $url = ROOT_URL.'/upload_photos/'.$day.'/'.$name;
  $message = '';
  echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($function_number, '$url', '$message');</script>";
 
}
		
		exit;
	}
	
	}

	function get_html(){
		
		$db =& ConnectionManager::getDataSource('default');
		
		$subcat_id = $_REQUEST['subcat_id'];
		
		$secondsubcattype = $_REQUEST['secondsubcattype'];
		
		$query = "select html from ace_rp_agent_training_html where sub_cat=$subcat_id and sub_cat_type=$secondsubcattype";
		
		$result1 = $db->_execute($query);
		
			while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC)){
				
				$html = $row1['html'];
				
			}
			
			print_r($html);
			
			exit;
		
	}
	function get_html1(){
		
		$db =& ConnectionManager::getDataSource('default');
		
		$subcat_id = $_REQUEST['subcat_id'];
		
		$secondsubcattype = $_REQUEST['secondsubcattype'];
		
		$query = "select html from ace_rp_techs_training_html where sub_cat=$subcat_id and sub_cat_type=$secondsubcattype";
		
		$result1 = $db->_execute($query);
		
			while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC)){
				
				$html = $row1['html'];
				
			}
			
			print_r($html);
			
			exit;
		
	}
	function get_html2(){
		
		$db =& ConnectionManager::getDataSource('default');
		
		$subcat_id = $_REQUEST['subcat_id'];
		
		$secondsubcattype = $_REQUEST['secondsubcattype'];
		
		$query = "select html from ace_rp_admin_training_html where sub_cat=$subcat_id and sub_cat_type=$secondsubcattype";
		
		$result1 = $db->_execute($query);
		
			while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC)){
				
				$html = $row1['html'];
				
			}
			
			print_r($html);
			
			exit;
		
	}
	function get_html3(){
		
		$db =& ConnectionManager::getDataSource('default');
		
		$subcat_id = $_REQUEST['subcat_id'];
		
		$secondsubcattype = $_REQUEST['secondsubcattype'];
		
		$query = "select html from ace_rp_tech_training_html where sub_cat=$subcat_id and sub_cat_type=$secondsubcattype";
		
		$result1 = $db->_execute($query);
		
			while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC)){
				
				$html = $row1['html'];
				
			}
			
			print_r($html);
			
			exit;
		
	}
		function aasort (&$array, $key) {
    $sorter=array();
    $ret=array();
    reset($array);
    foreach ($array as $ii => $va) {
        $sorter[$ii]=$va[$key];
    }
    asort($sorter);
    foreach ($sorter as $ii => $va) {
        $ret[$ii]=$array[$ii];
    }
    $array=$ret;
}
function get_all_notes_and_images(){
		$db =& ConnectionManager::getDataSource('default');
		$id = $_REQUEST['id'];
		$query = "select * from ace_rp_agent_second_cat_training_material where second_sub_cat='$id'";
		$result = $db->_execute($query);
		$get_data =  array();
				while($row1 = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$get_data[]=$row1;
			
		}
		
		$this->aasort($get_data,"type");
		
		$counter=0;
		
		$document=1;
		
		$video=1;
		
		foreach ($get_data as $get_datas){
			if($get_datas['type']==1){
				?>
			
			<span class="notes_123"><?= nl2br($get_datas['notes']) ?></span>
				
		<?php
		}
			if($get_datas['type']==2){ ?>
			
			<img class="invoice-openImg order-images images123" src="/acesys/app/webroot/training-images/<?= $get_datas['document_name'] ?>" class="image_123">
				
		<?php	}
			if($get_datas['type']==3){ ?>
			
			<span class="document123"><a href="/acesys/app/webroot/training-images/<?= $get_datas['document_name'] ?>" target="_blank">Document <?=$document ?></span>
				
		<?php
		$document++;
		}
			if($get_datas['type']==4){ ?>
			<span class="video123"><a class="video123" style="padding:5px" href="<?= $get_datas['video_link'] ?>" target="_blank"> <?=$get_datas['video_link_name'] ?></span>
			
			
				
		<?php
		$video++;
		}
			$counter++;
			
		}
		?>
		<script>
			$('span.notes_123').last().append('<br><br>');
			$('img.images123').last().after('<br><br>');
			$('span.document123').last().append('<br><br>');
			
			
		</script>
		
		<?php
		
		exit;
		
	}
		function get_agent_search_result(){
		
		$db =& ConnectionManager::getDataSource('default');
		
		$value = $_REQUEST['get_value'];
		
		$result_ids = array();
		
		$query  = "select id, sub_cat, sub_cat_type from ace_rp_agent_training_second_sub_categories where name LIKE '%$value%' GROUP BY sub_cat, sub_cat_type";
		
		$result1 = $db->_execute($query);
		
		while($row = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$sub_cat = $row['sub_cat'];
			$sub_cat_type=$row['sub_cat_type'];
			$result_ids[] = array(
								  'id'=>$row['sub_cat'],
								  'type'=>$row['sub_cat_type'],
								  //'html'=>$this->getAgentSecondSubCategory11($sub_cat,$sub_cat_type),
								  'html'=>file_get_contents("http://hvacproz.ca/acesys/index.php/settings/getAgentSecondSubCategory11?1=$sub_cat&2=$sub_cat_type"),
								  'url'=>"http://hvacproz.ca/acesys/index.php/settings/getAgentSecondSubCategory11?1=$sub_cat&2=$sub_cat_type",
								  );
			
			
			
		}
		
		print_r(json_encode($result_ids));
		
		
		
		exit;
	}

	function get_all_the_text(){
		$db =& ConnectionManager::getDataSource('default');
		$id = $_REQUEST['get_value'];
		
		$query="select id from ace_rp_agent_training_sub_categories where cat_id='$id'";
		$result1 = $db->_execute($query);
		$id1 = array();
		$id3 = array();
		$get_data = array();
		$get_data1 = array();
		while($row = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$id1[] = $row['id'];
			
		}
		
				$id11 = implode(',',$id1);
          if(empty($id1)){
            exit;  
		  }
		
		$query4="select id from ace_rp_agent_training_second_sub_categories where sub_cat in ($id11)";
		
		$result4 = $db->_execute($query4);
		
		while($row4 = mysql_fetch_array($result4, MYSQL_ASSOC))
		{
			$id1[] =$row4['id'];
			
		}
		
		$id2 = implode(',',$id1);

		
		
		
		$query2="select * from ace_rp_agent_training_material where sub_cat_id in ($id2)";
		
		$result2 = $db->_execute($query2);
		
		while($row2 = mysql_fetch_array($result2, MYSQL_ASSOC))
		{
			$get_data1[] =$row2;
			
		}
		
		
		
		$this->aasort($get_data1,"type");
		
		
		
		$counter=0;
		
		$document=1;
		
		$video=1;
		
		foreach ($get_data1 as $get_datas){
			if($get_datas['type']==2 || $get_datas['type']==6){
				?>
			
			<span class="notes_123"><?= $get_datas['notes'] ?><?= $get_datas['general_notes'] ?></span>
				
		<?php
		}
			if($get_datas['type']==4){ ?>
			
			<img class="invoice-openImg order-images images123" src="/acesys/app/webroot/training-images/<?= $get_datas['document_name'] ?>" class="image_123">
				
		<?php	}
			if($get_datas['type']==3 || $get_datas['type']==5){ ?>
			
			<span class="document123"><a href="/acesys/app/webroot/training-images/<?= $get_datas['document_name'] ?>" target="_blank">Document <?=$document ?></span>
				
		<?php
		$document++;
		}
			if($get_datas['type']==8){ ?>
			<span class="video123"><a class="video123" style="padding:5px" href="<?= $get_datas['video_link'] ?>" target="_blank"> <?=$get_datas['video_link_name'] ?></span>
			
			
				
		<?php
		$video++;
		}
			$counter++;
			
		}
		
		?>
		<script>
			$('span.notes_123').last().append('<br><br>');
			$('img.images123').last().after('<br><br>');
			$('span.document123').last().append('<br><br>');
			
			
		</script>
		
		<?php
		
		$query10="select * from ace_rp_agent_second_cat_training_material where second_sub_cat in ($id2)";
		
		$result10 = $db->_execute($query10);
		
		while($row10 = mysql_fetch_array($result10, MYSQL_ASSOC))
		{
			$get_data[] =$row10;
			
		}
		
		
		
		
		
		$this->aasort($get_data,"type");
		
		$counter=0;
		
		$document=1;
		
		$video=1;
		
		foreach ($get_data as $get_datas){
			if($get_datas['type']==1){
				?>
			
			<span class="notes_12345"><?= nl2br($get_datas['notes']) ?></span>
				
		<?php
		}
			if($get_datas['type']==2){ ?>
			
			<img class="invoice-openImg order-images images123" src="/acesys/app/webroot/training-images/<?= $get_datas['document_name'] ?>" class="image_123">
				
		<?php	}
			if($get_datas['type']==3){ ?>
			
			<span class="document123"><a href="/acesys/app/webroot/training-images/<?= $get_datas['document_name'] ?>" target="_blank">Document <?=$document ?></span>
				
		<?php
		$document++;
		}
			if($get_datas['type']==4){ ?>
			<span class="video123"><a class="video123" style="padding:5px" href="<?= $get_datas['video_link'] ?>" target="_blank"> <?=$get_datas['video_link_name'] ?></span>
			
			
				
		<?php
		$video++;
		}
			$counter++;
			
		}
		?>
		<script>
			$('span.notes_12345').first().before('<br><br>');
			$('span.notes_12345').last().append('<br><br>');
			$('img.images123').last().after('<br><br>');
			$('span.document123').last().append('<br><br>');
			
			
		</script>
		
		<?php
		
	
		exit;
		
	}
	function save_html(){
		$db =& ConnectionManager::getDataSource('default');
		
		$data = $_REQUEST['data1'];
		$data_subcat_id = $_REQUEST['data_subcat_id'];
		$data_secondsubcattype = $_REQUEST['data_secondsubcattype'];
		$query1 = "select id from ace_rp_agent_training_html where sub_cat=$data_subcat_id and sub_cat_type=$data_secondsubcattype"; 
		$result1 = $db->_execute($query1);
        $num = mysql_num_rows($result1);
	
		if($num>0) {
			//update
			
			$query2 = "UPDATE ace_rp_agent_training_html set html='".$data."' where sub_cat=$data_subcat_id and sub_cat_type=$data_secondsubcattype";
			$result2 = $db->_execute($query2);
		}
		else {
			//insert
			
			$query = "INSERT INTO  ace_rp_agent_training_html (sub_cat,sub_cat_type,html) VALUES ('".$data_subcat_id."','".$data_secondsubcattype."','".$data."')";
		$res = $db->_execute($query);
			
			
		}
		exit;
	}
	function save_html1(){
		$db =& ConnectionManager::getDataSource('default');
		
		$data = $_REQUEST['data1'];
		$data_subcat_id = $_REQUEST['data_subcat_id'];
		$data_secondsubcattype = $_REQUEST['data_secondsubcattype'];
		$query1 = "select id from ace_rp_techs_training_html where sub_cat=$data_subcat_id and sub_cat_type=$data_secondsubcattype"; 
		$result1 = $db->_execute($query1);
        $num = mysql_num_rows($result1);
	
		if($num>0) {
			//update
			
			$query2 = "UPDATE ace_rp_techs_training_html set html='".$data."' where sub_cat=$data_subcat_id and sub_cat_type=$data_secondsubcattype";
			$result2 = $db->_execute($query2);
		}
		else {
			//insert
			
			$query = "INSERT INTO  ace_rp_techs_training_html (sub_cat,sub_cat_type,html) VALUES ('".$data_subcat_id."','".$data_secondsubcattype."','".$data."')";
		$res = $db->_execute($query);
			
			
		}
		exit;
	}
	function save_html2(){
		$db =& ConnectionManager::getDataSource('default');
		
		$data = $_REQUEST['data1'];
		$data_subcat_id = $_REQUEST['data_subcat_id'];
		$data_secondsubcattype = $_REQUEST['data_secondsubcattype'];
		$query1 = "select id from ace_rp_admin_training_html where sub_cat=$data_subcat_id and sub_cat_type=$data_secondsubcattype"; 
		$result1 = $db->_execute($query1);
        $num = mysql_num_rows($result1);
	
		if($num>0) {
			//update
			
			$query2 = "UPDATE ace_rp_admin_training_html set html='".$data."' where sub_cat=$data_subcat_id and sub_cat_type=$data_secondsubcattype";
			$result2 = $db->_execute($query2);
		}
		else {
			//insert
			
			$query = "INSERT INTO  ace_rp_admin_training_html (sub_cat,sub_cat_type,html) VALUES ('".$data_subcat_id."','".$data_secondsubcattype."','".$data."')";
		$res = $db->_execute($query);
			
			
		}
		exit;
	}
	function save_html3(){
		$db =& ConnectionManager::getDataSource('default');
		
		$data = $_REQUEST['data1'];
		$data_subcat_id = $_REQUEST['data_subcat_id'];
		$data_secondsubcattype = $_REQUEST['data_secondsubcattype'];
		$query1 = "select id from ace_rp_tech_training_html where sub_cat=$data_subcat_id and sub_cat_type=$data_secondsubcattype"; 
		$result1 = $db->_execute($query1);
        $num = mysql_num_rows($result1);
	
		if($num>0) {
			//update
			
			$query2 = "UPDATE ace_rp_tech_training_html set html='".$data."' where sub_cat=$data_subcat_id and sub_cat_type=$data_secondsubcattype";
			$result2 = $db->_execute($query2);
		}
		else {
			//insert
			
			$query = "INSERT INTO  ace_rp_tech_training_html (sub_cat,sub_cat_type,html) VALUES ('".$data_subcat_id."','".$data_secondsubcattype."','".$data."')";
		$res = $db->_execute($query);
			
			
		}
		exit;
	}

	function editAgentSecondSubCategory($subCatId=0,$subCatType=0,$secondSubCatId=0)
	{	
		$db =& ConnectionManager::getDataSource('default');	
		$subCategory = array();
		if($secondSubCatId) {
			$query = "SELECT * from ace_rp_agent_training_second_sub_categories where id=".$secondSubCatId;
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
		} else {
			$row = array("id"=>"", "name"=>"", "sub_cat"=>"","sub_cat_type"=>"");
		}
		$query1 = "SELECT * from ace_rp_agent_training_sub_categories";
			$result1 = $db->_execute($query1);
			$subCategory = array();		
			while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
			{
				foreach($row1 as $k => $v)
				{
				  $subCategory[$row1['id']][$k] = $v;
				}	
			}
		
		$this->set('subCatId', $subCatId);
		$this->set('secondSubCategory', $row);
		$this->set('secondSubCategoryType', $subCatType);
		$this->set('subCategories', $subCategory);
		
	}

	function addAgentSecondSubCategory($id)
	{	
		$allSubCatIds = $_POST['allSubCatId'];
		$name = $_POST['secondSubCatName'];
		$subCatId = $_POST['subCatId'];
		$secondSubCatId = $_POST['secondSubCatId'];
		$secondSubCatType = $_POST['secondSubCatType'];
		$db =& ConnectionManager::getDataSource('default');	
		if(!empty($secondSubCatId) || $secondSubCatId != '')
		{
			$query = "UPDATE ace_rp_agent_training_second_sub_categories set name='".$name[0]."',sub_cat=".$allSubCatIds[0]." where id=".$secondSubCatId;
			$result = $db->_execute($query);
		} else {
			foreach($name as $key => $val)
			{
				$query = "INSERT INTO  ace_rp_agent_training_second_sub_categories (name,sub_cat,sub_cat_type) VALUES ('".$val."', ".$allSubCatIds[$key].",".$secondSubCatType[$key].")";	
				$result = $db->_execute($query);
			}
			
		}
		if($result)
			{
				echo "<script>window.close();</script>";
		 			exit();
			}
		exit();
	}
		function search_revisions($dataArray, $search_value, $key_to_search, $other_matching_value = null, $other_matching_key = null) {
    // This function will search the revisions for a certain value
    // related to the associative key you are looking for.
    $keys = array();
    foreach ($dataArray as $key => $cur_value) {
        if ($cur_value[$key_to_search] == $search_value) {
            if (isset($other_matching_key) && isset($other_matching_value)) {
                if ($cur_value[$other_matching_key] == $other_matching_value) {
                    $keys[] = $key;
                }
            } else {
                // I must keep in mind that some searches may have multiple
                // matches and others would not, so leave it open with no continues.
                $keys[] = $key;
            }
        }
    }
    return $keys;
}

	function getAgentSecondSubCategory11($subCatId1="",$subCatType1="")
	{
		
		$db =& ConnectionManager::getDataSource('default');
		//$this->layout='blank';
		$subCatId = $_REQUEST['1'];
		$subCatType = $_REQUEST['2'];
		
		//$subCatId= $subCatId1;
		
		//$subCatType = $subCatType1;
		
		$allSecondSubCat = $this->AgentTrainingSecondSubCategory->findAll(array('sub_cat' => $subCatId,'sub_cat_type' => $subCatType));
		
		$get_data  = array();
		foreach ($allSecondSubCat as $obj) {
			
			$cat_id=$obj['AgentTrainingSecondSubCategory']['id'];
			$query = "select * from ace_rp_agent_second_cat_training_material where second_sub_cat='$cat_id'";
			$result1 = $db->_execute($query);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$get_data[]=$row1;
			
		}	
			
			
			
	}

	
		?>
		
		 <ul class="nested active 123">
      <?php foreach ($allSecondSubCat as $obj) {
       $data = $this->search_revisions($get_data,$obj['AgentTrainingSecondSubCategory']['id'],'second_sub_cat');
       $video=0;
       $image=0;
       $notes=0;
       $document=0;
       foreach($data as $datas){
        if($get_data[$datas]['type']==4){
         $video++;
        }
        if($get_data[$datas]['type']==2){
         $image++;
        }
        if($get_data[$datas]['type']==1){
         $notes++;
        }
        
        if($get_data[$datas]['type']==3){
         $document++;
        }
        
       }
       if($video>0 || $image>0 || $notes>0 || $document>0){
        $class="active";
       }
       ?>
             
             <li><span class="caret secondSubCat" ><?=$obj['AgentTrainingSecondSubCategory']['name']?>
             <i style="cursor:pointer" class="fa fa-trash-o deletesecond_category" data-id="<?= $obj['AgentTrainingSecondSubCategory']['id'] ?>"></i>
             <i style="cursor:pointer" class="fa fa-file get_all_notes_images" data-id="<?= $obj['AgentTrainingSecondSubCategory']['id'] ?>"></i>
      
             </span>
                 <ul class="nested active">
                <?php
                $allow =$obj['AgentTrainingSecondSubCategory']['allow'];
                $check = explode(',',$allow); ?>
                
                
                    <li>Documents (<?= $document ?>) <i class="fa fa-plus addSecondCatDoc" secondSubcat-id="<?php echo $obj['AgentTrainingSecondSubCategory']['id']; ?>" aria-hidden="true"></i>&nbsp<i class="fa fa fa-folder showSecondCatDoc" secondSubcat-id="<?php echo $obj['AgentTrainingSecondSubCategory']['id']; ?>"></i></li>
                  <li>Note (<?= $notes ?>) <i class="fa fa-plus addSecondCatNote" secondSubcat-id="<?php echo $obj['AgentTrainingSecondSubCategory']['id']; ?>" aria-hidden="true"></i>&nbsp<i class="fa fa fa-folder showSecondCatNote" secondSubcat-id="<?php echo $obj['AgentTrainingSecondSubCategory']['id']; ?>"></i></li>
                  <li>Images (<?= $image ?>) <i class="fa fa-plus addSecondCatImage" secondSubcat-id="<?php echo $obj['AgentTrainingSecondSubCategory']['id']; ?>" aria-hidden="true"></i>&nbsp<i class="fa fa fa-folder showSecondCatImage" secondSubcat-id="<?php echo $obj['AgentTrainingSecondSubCategory']['id']; ?>"></i></li>
                  <li>Video (<?= $video ?>) <i class="fa fa-plus addSecondCatVideo" secondSubcat-id="<?php echo $obj['AgentTrainingSecondSubCategory']['id']; ?>" aria-hidden="true"></i>&nbsp<i class="fa fa fa-folder showSecondCatVideo" secondSubcat-id="<?php echo $obj['AgentTrainingSecondSubCategory']['id']; ?>"></i></li>
                 
              
                </ul>
              </li>
      <? } ?>
    </ul>
		
		<?php
		
		exit;
		
	}

	function getAgentSecondSubCategory()
	{	
		$db =& ConnectionManager::getDataSource('default');
		$this->layout='blank';
		$subCatId = $_GET['subCatId'];
		$subCatType = $_GET['subCatType'];
		$allSecondSubCat = $this->AgentTrainingSecondSubCategory->findAll(array('sub_cat' => $subCatId,'sub_cat_type' => $subCatType));
		
		$get_data  = array();
		foreach ($allSecondSubCat as $obj) {
			$cat_id=$obj['AgentTrainingSecondSubCategory']['id'];
			$query = "select * from ace_rp_agent_second_cat_training_material where second_sub_cat='$cat_id'";
			$result1 = $db->_execute($query);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$get_data[]=$row1;
			
		}	
			
			
			$this->set('get_data', $get_data);
	}
		
		$this->set('trainingSecondSubCategories', $allSecondSubCat);
	}

	function addAgentSubCatNote()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_agent_training_material (sub_cat_id,notes,type) VALUES (".$id.",'".$note."',2)";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getAgentSubCatNotes()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type FROM ace_rp_agent_training_material WHERE sub_cat_id =".$id." and type=2";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<input type="button" value="View" onclick="getCatNotes('.$row1['id'].');" class="getTechSupport" type-id="'.$row1['type'].'" row-id="'.$row1['id'].'">&nbsp';
		}	
		$res .='</br><div style="margin-top:5px;"><button type="button" name="close" value="Close" onclick=$("#show_subcat_notes_box").dialog("close");>Close</button></td></tr></div>';
		echo $res;
		exit();
	}

	function getAgentSubCatNotesVal()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,notes FROM ace_rp_agent_training_material WHERE id =".$id."";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res = $row1;
		}	
		echo json_encode($res);
		exit();
	}

	function addAgentSubCatVideo()
	{
		$id = $_POST['id'];
		$videoLink = mysql_real_escape_string($_POST['videoLink']);
		$videoLinkName = mysql_real_escape_string($_POST['videoLinkName']);
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_agent_training_material (sub_cat_id,video_link,video_link_name,type) VALUES (".$id.",'".$videoLink."','".$videoLinkName."',8)";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getAgentSubCatVideos()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,video_link,video_link_name FROM ace_rp_agent_training_material WHERE sub_cat_id =".$id." and type=8";
		$res = '<table>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<tr><td><i class="fa fa-trash-o deleteSubCatVideo" row-id="'.$row1['id'].'"></i></td><td><a href="'.$row1['video_link'].'" target="_blank">'.$row1['video_link_name'].'</a></td></tr>
			<tr><td><button type="button" name="close" value="Close" onclick=$("#show_subcat_videos_box").dialog("close");>Close</button></td></tr>
			';
			$i++;
		}	

		$res .= '</table>';
		echo $res;
		exit();
	}

	function agentSubCatImageUpload(){
		$subCatId = $_POST['subcatId'];
		$images = $_FILES['file'];
		$db =& ConnectionManager::getDataSource('default');
		if(!empty($images)){
			foreach ($images['name'] as $key => $value) {
				$fileName = time().'_'.rand()."_".$value;
				$fileTmpName = $images['tmp_name'][$key];
				$orgFileName = $value;
				if($images['error'][$key] == 0)
				{
					//$move = $this->saveImages($file, $orgFileName, 90);
					$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/training-images/".$fileName);
					$query = "INSERT INTO ace_rp_agent_training_material (sub_cat_id,document_name,org_document_name,type) VALUES (".$subCatId.",'".$fileName."','".$orgFileName."',4)";

					$result = $db->_execute($query);
				}
			}
		}
		if($result){
			 $output = array(
                        'status' => 'success'
                    );
			}else{
				 $output = array(
                        'status' => 'error'
                    );
			}

		echo json_encode($output);
                exit();
	}

	function agentSubCatDocumentUpload(){
		$subCatId = $_POST['subcatId'];
		$images = $_FILES['file'];
		$db =& ConnectionManager::getDataSource('default');
		if(!empty($images)){
			foreach ($images['name'] as $key => $value) {
				$fileName = time()."_".$value;
				$fileTmpName = $images['tmp_name'][$key];
				$orgFileName = $value;
				if($images['error'][$key] == 0)
				{
					//$move = $this->saveImages($file, $orgFileName, 90);
					$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/training-images/".$fileName);
					$query = "INSERT INTO ace_rp_agent_training_material (sub_cat_id,document_name,org_document_name,type) VALUES (".$subCatId.",'".$fileName."','".$orgFileName."',5)";

					$result = $db->_execute($query);
				}
			}
		}
		if($result){
			 $output = array(
                        'status' => 'success'
                    );
			}else{
				 $output = array(
                        'status' => 'error'
                    );
			}

		echo json_encode($output);
                exit();
	}

	function getAgentSubCatImages()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,document_name FROM ace_rp_agent_training_material WHERE sub_cat_id =".$id." and type=4";
		$res = '<table><tr>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{			
			$res .= '<td><span style="position:absolute;"><img style="height: 15px;width: 12px;" class="delete-subcat-image" src="'.ROOT_URL.'/app/webroot/img/cross-icon.jpeg" image-name="'.$row1["document_name"].'" image-id="'.$row1["id"].'"></span>
                        <img id="" class="invoice-openImg order-images" src="'.ROOT_URL.'/app/webroot/training-images/'.$row1["document_name"].'"></td>';
		}	
		$res .= '</tr><tr><td><button type="button" name="close" value="Close" onclick=$("#show_subcat_images_box").dialog("close");>Close</button></td></tr>
		</table>';
		echo $res;
		exit();
	}

	function getAgentSubCatDocuments()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,document_name FROM ace_rp_agent_training_material WHERE sub_cat_id =".$id." and type=5";
		$res = '<table><tr>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{	

			$res .='<td><span style="position:absolute;"><img style="height: 15px;width: 12px;" class="delete-subcat-doc" src="'.ROOT_URL.'/app/webroot/img/cross-icon.jpeg" image-name="'.$row1['document_name'].'" image-id="'.$row1["id"].'"></span>
                  <img class="show-pdf-image" src="'.ROOT_URL.'/app/webroot/img/doc_pdf.png" data-doc_lnk="/acesys/app/webroot/training-images/'.$row1['document_name'].'" style="height: 70px;width: 70px;"></td>';

			// $res .='<td><a href="/acesys/app/webroot/training-images/'.$row1['document_name'].'"">example</a></td>';
		}	
		$res .= '</tr><tr><td><button type="button" name="close" value="Close" onclick=$("#show_subcat_document_box").dialog("close");>Close</button></td></tr></table>';
		echo $res;
		exit();
	}

	function agentSecondSubCatDocumentUpload()
	{
		$secondSubCatId = $_POST['secondSubcatId'];
		$images = $_FILES['file'];
		$db =& ConnectionManager::getDataSource('default');
		if(!empty($images)){
			foreach ($images['name'] as $key => $value) {
				$fileName = time()."_".$value;
				$fileTmpName = $images['tmp_name'][$key];
				$orgFileName = $value;
				if($images['error'][$key] == 0)
				{
					//$move = $this->saveImages($file, $orgFileName, 90);
					$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/training-images/".$fileName);
					$query = "INSERT INTO ace_rp_agent_second_cat_training_material
						 (second_sub_cat,document_name,org_document_name,type) VALUES (".$secondSubCatId.",'".$fileName."','".$orgFileName."',3)";

					$result = $db->_execute($query);
				}
			}
		}
		if($result){
			 $output = array(
                        'status' => 'success'
                    );
			}else{
				 $output = array(
                        'status' => 'error'
                    );
			}
		echo json_encode($output);
                exit();
	}

	function getAgentSecondSubCatDocuments()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,document_name FROM ace_rp_agent_second_cat_training_material WHERE second_sub_cat =".$id." and type=3";
		$res = '<table><tr>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{	

			$res .='<td><span style="position:absolute;"><img style="height: 15px;width: 12px;" class="delete-secondsubcat-doc" src="'.ROOT_URL.'/app/webroot/img/cross-icon.jpeg" image-name="'.$row1['document_name'].'" image-id="'.$row1["id"].'"></span>
                  <img class="show-pdf-image" src="'.ROOT_URL.'/app/webroot/img/doc_pdf.png" data-doc_lnk="/acesys/app/webroot/training-images/'.$row1['document_name'].'" style="height: 70px;width: 70px;"></td>';
		}	
		$res .= '</tr><tr><td><button type="button" name="close" value="Close" onclick=$("#show_second_subcat_document_box").dialog("close");>Close</button></td></tr></table>';
		echo $res;
		exit();
	}

	function agentSecondSubCatImageUpload()
	{
		$secondSubCatId = $_POST['secondSubcatId'];
		$images = $_FILES['file'];
		$db =& ConnectionManager::getDataSource('default');
		if(!empty($images)){
			foreach ($images['name'] as $key => $value) {
				$fileName = time()."_".$value;
				$fileTmpName = $images['tmp_name'][$key];
				$orgFileName = $value;
				if($images['error'][$key] == 0)
				{
					//$move = $this->saveImages($file, $orgFileName, 90);
					$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/training-images/".$fileName);
					$query = "INSERT INTO ace_rp_agent_second_cat_training_material (second_sub_cat,document_name,org_document_name,type) VALUES (".$secondSubCatId.",'".$fileName."','".$orgFileName."',2)";

					$result = $db->_execute($query);
				}
			}
		}
		if($result){
			 $output = array(
                        'status' => 'success'
                    );
			}else{
				 $output = array(
                        'status' => 'error'
                    );
			}

		echo json_encode($output);
                exit();
	}

	function getAgentSecondSubCatImages()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,document_name FROM ace_rp_agent_second_cat_training_material WHERE  second_sub_cat =".$id." and type=2";
		$res = '<table><tr>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{			
			$res .= '<td><span style="position:absolute;"><img style="height: 15px;width: 12px;" class="delete-secondsubcat-image" src="'.ROOT_URL.'/app/webroot/img/cross-icon.jpeg" image-name="'.$row1["document_name"].'" image-id="'.$row1["id"].'"	></span>
                        <img id="" class="invoice-openImg order-images" src="'.ROOT_URL.'/app/webroot/training-images/'.$row1["document_name"].'"></td>';
		}	
		$res .= '</tr><tr><td><button type="button" name="close" value="Close" onclick=$("#show_second_subcat_images_box").dialog("close");>Close</button></td></tr>
		</table>';
		echo $res;
		exit();
	}

	function addAgentSecondSubCatNote()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_agent_second_cat_training_material (second_sub_cat,notes,type) VALUES (".$id.",'".$note."',1)";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getAgentSecondSubCatNotes()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type FROM ace_rp_agent_second_cat_training_material WHERE second_sub_cat =".$id." and type=1";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<input type="button" value="View" onclick="getSecondCatNotes('.$row1['id'].');" class="getTechSupport" type-id="'.$row1['type'].'" row-id="'.$row1['id'].'">&nbsp';
		}	

		$res .='</br><div style="margin-top:5px;"><button type="button" name="close" value="Close" onclick=$("#show_second_subcat_notes_box").dialog("close");>Close</button></td></tr></div>';
		echo $res;
		exit();
	}

	function getAgentSecondSubCatNotesVal()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,notes FROM ace_rp_agent_second_cat_training_material WHERE id =".$id."";
		$res = '';
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res = $row1;
		}	
		echo json_encode($res);
		exit();
	}

	function getAgentSecondSubCatVideos()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,video_link,video_link_name FROM ace_rp_agent_second_cat_training_material WHERE second_sub_cat =".$id." and type=4";
		$res = '<table>';
		$result1 = $db->_execute($query1);
		$i = 1;
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res .= '<tr><td><i class="fa fa-trash-o deleteSecondSubCatVideo" row-id="'.$row1['id'].'"></i></td><td><a href="'.$row1['video_link'].'" target="_blank">'.$row1['video_link_name'].'</a></td></tr>
				<tr><td><button type="button" name="close" value="Close" onclick=$("#show_second_subcat_videos_box").dialog("close");>Close</button></td></tr>';
			$i++;
		}	

		$res .= '</table>';
		echo $res;
		exit();
	}

	function addAgentSecondSubCatVideo()
	{
		$id = $_POST['id'];
		$videoLink = mysql_real_escape_string($_POST['videoLink']);
		$videoLinkName = mysql_real_escape_string($_POST['videoLinkName']);
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO  ace_rp_agent_second_cat_training_material (second_sub_cat,video_link,video_link_name,type) VALUES (".$id.",'".$videoLink."','".$videoLinkName."',4)";
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteAgentCategory()
	{
		$id = $_POST['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM ace_rp_agent_training_category WHERE id IN (".$id.")";
		$result = $db->_execute($query);

		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	function deleteAgentSubCategory()
	{
		$id = $_POST['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM  ace_rp_agent_training_sub_categories WHERE id IN (".$id.")";
		$result = $db->_execute($query);
		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	function updateAgentSubCatNote()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE  ace_rp_agent_training_material set notes='".$note."' WHERE id=".$id;
		$res = $db->_execute($query);

		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteAgentSubcatImage()
	{
		$id = $_POST['id'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');

		$filename = ROOT.'/app/webroot/training-images/'.$name;
        $query = "DELETE FROM ace_rp_agent_training_material  WHERE id =".$id."";
        $result = $db->_execute($query);
        if (file_exists($filename)) 
        {
            unlink($filename);
        } 

        if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteAgentSecondSubcatImage()
	{
		$id = $_POST['id'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');

		$filename = ROOT.'/app/webroot/training-images/'.$name;
        $query = "DELETE FROM ace_rp_agent_second_cat_training_material  WHERE id =".$id."";
        $result = $db->_execute($query);
        if (file_exists($filename)) 
        {
            unlink($filename);
        } 

        if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteAgentSubcatDoc()
	{
		$id = $_POST['id'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');

		$filename = ROOT.'/app/webroot/training-images/'.$name;
        $query = "DELETE FROM ace_rp_agent_training_material  WHERE id =".$id."";
        $result = $db->_execute($query);
        if (file_exists($filename)) 
        {
            unlink($filename);
        } 

        if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteAgentSecondSubcatDoc()
	{
		$id = $_POST['id'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');

		$filename = ROOT.'/app/webroot/training-images/'.$name;
        $query = "DELETE FROM ace_rp_agent_second_cat_training_material  WHERE id =".$id."";
        $result = $db->_execute($query);
        if (file_exists($filename)) 
        {
            unlink($filename);
        } 

        if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function updateAgentSecondSubCatNote()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE ace_rp_agent_second_cat_training_material set notes='".$note."' WHERE id=".$id;
		$res = $db->_execute($query);
		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function getAgentTechSupportDetails()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query1 = "SELECT id,type,tech_support,tech_support_name,sub_cat_id  FROM ace_rp_agent_training_material WHERE id =".$id." and type=1";
		$result1 = $db->_execute($query1);
		while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
		{
			$res = $row1;
		}	
		echo json_encode($res);
		exit();
	}

	function editAgentTechSupport()
	{
		$id = $_POST['id'];
		$note = $_POST['note'];
		$name = $_POST['name'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE ace_rp_agent_training_material set tech_support='".$note."', tech_support_name ='".$name."' WHERE id=".$id;
		$res = $db->_execute($query);
		if($res)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
	}

	function deleteAgentTechSupportDetails()
	{
		$id = $_GET['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM ace_rp_agent_training_material WHERE id =".$id."";	
		$result = $db->_execute($query);
		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	function deleteAgentSubCatVideo()
	{
		$id = $_POST['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM  ace_rp_agent_training_material WHERE id =".$id;
		$result = $db->_execute($query);
		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}

	function deleteAgentSecondSubCatVideo()
	{
		$id = $_POST['id'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE FROM  ace_rp_agent_second_cat_training_material WHERE id =".$id;
		$result = $db->_execute($query);
		if($result)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit();	
		}
		exit();
	}
}
?>
