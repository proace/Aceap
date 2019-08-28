<? //error_reporting(E_ALL);

class SettingsController extends AppController
{
	var $name = "SettingsController";
	var $uses = array('Setting','User');

		
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
}
?>
