<?php ob_start();
//error_reporting(E_ALL);
//error_reporting(1);
//include 'adminchat/database_connection.php';
include 'adminchat/database_connection.php';
class UsersController extends AppController
{
	//To avoid possible PHP4 problems
	var $name = "UsersController";

	var $uses = array('User','Role','Userrole', 'Order', 'Commission', 'Setting','InventoryLocation','TruckMap');

	var $helpers = array('Time','Ajax','Common');
	var $components = array('HtmlAssist', 'RequestHandler','Common','Lists');

	var $itemsToShow = 40;
	var $pagesToDisplay = 20;
	var $beforeFilter = array('checkAccess');

	function checkAccess()
	{
		if( $this->action == 'index' || $this->action == 'edit' || $this->action == 'add' ) {
			$this->Common->checkRoles(array('6','3','13'));
		}
		if( $this->action == 'customer' ) {
			$this->Common->checkRoles(array('6','8'));
		}
	}
  
	function edit()
	{
		$id = $_REQUEST['id'];
		$roleID = $_REQUEST['roleId'];
		$this->set('sm', 10);
		$this->set('subtitle', 'Edit User');
		//$this->set('roleId', $roleID);
		//If we have no data, then we need to provide the data to the user for editing
		if (empty($this->data['User']))
		{
			$this->User->id = $id;
			$this->data = $this->User->read();
			$this->TruckMap->recursive=-1;
			$assignedTrucks=array();
			$assignedTrucksList=$this->TruckMap->findAllByUserId($id);
			
			foreach ($assignedTrucksList as $key => $eachAssignedTruck) {
				
				$assignedTrucks[$eachAssignedTruck['TruckMap']['id']]=$eachAssignedTruck['TruckMap']['truck_id'];
				$this->data['assigned_trucks'][]=$eachAssignedTruck['TruckMap']['truck_id'];
			}
			$this->set('roleId', $roleID);
			$this->set('data', $this->data);
			$this->set('interfaces', $this->Lists->UserInterfaces());
			$this->set('eprint', $this->Lists->EprintTerminals());
			
			$msg = $this->Setting->find(array('title'=>'portfolio_template'));
			$portfolio = $msg['Setting']['valuetxt'];
			
			$portfolio = str_replace("{first_name}", $this->data['User']['first_name'], $portfolio);
			$portfolio = str_replace("{last_name}", $this->data['User']['last_nam1'], $portfolio);			
			$portfolio = str_replace("{techphoto}", $this->data['User']['binary_picture'], $portfolio);
			$portfolio = str_replace("{qualifications}", $this->data['User']['qualifications'], $portfolio);
			$portfolio = str_replace("{experience}", $this->data['User']['experience'], $portfolio);
			$portfolio = str_replace("{skills}", $this->data['User']['skills'], $portfolio);
			$portfolio = str_replace("{about}", $this->data['User']['about'], $portfolio);
			$portfolio = str_replace("{goals}", $this->data['User']['goals'], $portfolio);
			$portfolio = str_replace("{hobbies}", $this->data['User']['hobbies'], $portfolio);
			// print_r($portfolio);die;
			$this->set('portfolio', $portfolio);

		}
		else if (!empty($this->data['User']))
		{	
			$userId = $this->data['User']['id'];
			$isValid = true;
			$dbObj = new DatabaseConnection();
		    $connectionObj = $dbObj->dbConnection();
		    $userName = $this->data['User']['username'];
		    $userEmail = $this->data['User']['email'];
			$userFirstName =  $this->data['User']['first_name'];
			$userLastName = (empty($this->data['User']['last_name'])) ? $this->data['User']['last_name'] :'';
			$hashed_password = crypt($this->data['User']['password']);
			
			if (!$connectionObj) {
			    echo "Error: Unable to connect to MySQL." . PHP_EOL;
			    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
			    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
			    exit;
			}
			// print_r($_SESSION['user']['username_chat']);
			// print_r($this->data['User']['username']);die;
		if(empty($userId)) {
			$sql = "SELECT * FROM lh_users where  username ='".$userName."'";
			$result = mysqli_query($connectionObj,$sql);
			$row = mysqli_fetch_array($result);
			if(empty($row )) {
				$sql = "INSERT INTO lh_users(username, password, email, name, surname, disabled, all_departments, exclude_autoasign, hide_online, invisible_mode, inactive_mode, rec_per_req, active_chats_counter, closed_chats_counter, pending_chats_counter, auto_accept, max_active_chats, pswd_updated, attr_int_1, attr_int_2, attr_int_3, time_zone, filepath,filename, job_title, departments_ids, chat_nickname, xmpp_username, session_id, operation_admin, skype)
					VALUES ('".$userName."','".$hashed_password."','".$userEmail."','".$userFirstName."','".$userLastName."',0,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,'','','','',0,'".$userFirstName."','', 0, '', '')";

			if(mysqli_query($connectionObj,$sql)) {
				$chatUserId = mysqli_insert_id($connectionObj);
				$sql= "INSERT INTO lh_groupuser (user_id, group_id) VALUES ('".$chatUserId."',2)";
				mysqli_query($connectionObj,$sql);	
				}
			}
		} else {
			$db =& ConnectionManager::getDataSource('default');
			$query = "select username from ace_rp_users where id=".$userId."";
			$result = $db->_execute($query);
			if($row = mysql_fetch_array($result)) {
			$oldUserName = $row['username'];
			$query = "UPDATE lh_users SET username ='".$userName."', password ='".$hashed_password."' WHERE username ='".$oldUserName."'";
			$result = mysqli_query($connectionObj,$query);

			// $query1 = "UPDATE Customers SET ContactName = 'Alfred Schmidt', City= 'Frankfurt' WHERE CustomerID = 1";
				
			}
					
		}
			// check if email is entered
			// edited by Maxim Kudryavtsev - 06.02.2013 - Ali decided that email here shouldn't be mandatory
			/*if($this->data['User']['email'] == ''){
				$this->User->invalidate('email_1'); $isValid=false;
			}
			else{
				
				if(!$this->check_email($this->data['User']['email'])){
					$this->User->invalidate('email_2'); $isValid=false;
				}
			}
			*/
			// check if email is valid
			
			// check if phone is entered
			if($this->data['User']['phone'] == ''){
				$this->User->invalidate('phone'); $isValid=false;
			}
			
			//$this->data['Role']['user_id'] = $this->data['User']['id'];
			//$this->User->Role->user_id = $this->data['User']['id'];

			//$this->User->Role->save($this->data);
			$this->data['User']['phone'] = $this->data['User']['phone'] ? $this->Common->preparePhone($this->data['User']['phone']) : '';
			$this->data['User']['cell_phone'] = $this->data['User']['cell_phone'] ? $this->Common->preparePhone($this->data['User']['cell_phone']) : '';
			$this->data['User']['postal_code'] = $this->data['User']['postal_code'] ? $this->Common->prepareZip($this->data['User']['postal_code']) : '';
			$this->data['User']['is_active'] = $_POST['is_active'];
			
			// by reason - Telemarketer: Access Limitation
			if( $this->data['User']['id'] == '' && $this->Common->getLoggedUserRoleID() == 3) {
				$this->data['User']['telemarketer_id'] = $this->Common->getLoggedUserID();
			}
			
			//Validate & Validate
			if($isValid)
			{
				if ($this->User->save($this->data['User']))
				{
					$this->Userrole->del($this->User->id);
					$this->data['Userrole']['user_id'] = $this->User->id;
					$this->data['Userrole']['role_id'] = $this->data['Role']['role_id'];
					$this->Userrole->save($this->data);
					
					if($this->data['Role']['role_id']==3){ //only for telemarketer
						$this->TruckMap->recursive=-1;
						$assignedTrucks=array();
						$assignedTrucksList=$this->TruckMap->findAllByUserId($this->User->id);
						
						foreach ($assignedTrucksList as $key => $eachAssignedTruck) {
							$assignedTrucks[$eachAssignedTruck['TruckMap']['id']]=$eachAssignedTruck['TruckMap']['truck_id'];
						}

						if(empty($this->data['assigned_trucks'])){
							$trucksToDel=$assignedTrucks;
						}else{
							$trucksToDel=array_diff($assignedTrucks, $this->data['assigned_trucks']);	
						}
						
						$trucksToCreate=array_diff($this->data['assigned_trucks'], $assignedTrucks);
						// echo ">>>>>>>>>>from database>>>>>>>";
						// pr($assignedTrucks);
						// echo ">>>>>>>>>>from Form>>>>>>>";
						// pr($this->data['assigned_trucks']);
						// echo ">>>>>>>>>>To Delete>>>>>>>";
						// pr($trucksToDel);
						// echo ">>>>>>>>>>To Create>>>>>>>";
						// pr($trucksToCreate);
						
						
						
						foreach ($trucksToDel as $mapKey => $truckTodel) {
							$this->TruckMap->del($mapKey);
						}

						foreach ($trucksToCreate as $eachKey => $truckToAssigned) {
							
							$this->TruckMap->create();
							$this->TruckMap->save(array('user_id'=>$this->User->id,'truck_id'=>$truckToAssigned));
						}
						
						//die();
					}
					$roleId = $_POST['roleId'];
					//Forward user where they need to be - if this is a single action per view
					if ($this->data['rurl'][0])
					{	
						$this->redirect($this->data['rurl'][0]);
					}
					else if(!empty($roleId)) {
						$this->redirect('/users?action=view&order=&sort=&currentPage=&data%5Brole%5D%5B%5D='.$roleId.'&submit2=Update');
					} else {
						$this->redirect('/users/');
					}
					exit();
				}
				else
				{
					//Generate the error messages for the appropriate fields
					//this is not really necessary as save already does this, but it is an example
					//call $this->User->validates($this->data['User']); if you are not doing a save
					//then use the method below to populate the tagErrorMsg() helper method
					$this->validateErrors($this->User);
	
					//And render the edit view code
					$this->render();
				}
			}
		}
		
		$items = $this->Role->findAll();
		/*$trucks=$this->Lists->ListTable('ace_rp_inventory_locations','type=2 and flagactive=1');*/
		$trucks=$this->Lists->ListTable('ace_rp_inventory_locations','type=2 and flagactive=0');
		foreach( $items as $item ) {
			$items4select[$item['Role']['id']] = $item['Role']['name'];
		}
	
		$this->set('Roles4Select',$items4select);
		$this->set('trucks',$trucks);
	}

	function editself()
	{
		//$id = $_REQUEST['id'];
		$id = $_SESSION['user']['id'];
		
		$this->set('sm', 10);
		$this->set('subtitle', 'Edit User');
		

		//If we have no data, then we need to provide the data to the user for editing
		if (empty($this->data['User']))
		{
			$this->User->id = $id;
			$this->data = $this->User->read();
			$this->set('data', $this->data);
		}
		else if (!empty($this->data['User']))
		{
			$isValid = true;
			
			// check if passwords match
			if($this->data['User']['password'] != $this->data['User']['password2']) {
				$this->User->invalidate('password');
				$isValid=false;
			} else {
				$newpassword = $this->data['User']['password'];
			}
			
			//Validate & Validate
			if($isValid)
			{
				$this->User->id = $id;
				$this->data = $this->User->read();
				$this->data['User']['password'] = $newpassword;
				$userName = $this->data['User']['username'];
				$hashed_password = crypt($newpassword);
				if ($this->User->save($this->data['User']))
				{		
					
					$dbObj = new DatabaseConnection();
		   			$connectionObj = $dbObj->dbConnection();
					$query = "UPDATE lh_users SET password ='".$hashed_password."' WHERE username ='".$userName."'";
					$result = mysqli_query($connectionObj,$query);
					//Forward user where they need to be - if this is a single action per view
					/*if ($this->data['rurl'][0])
						$this->redirect($this->data['rurl'][0]);
					else
						$this->redirect('/users/editself');*/
					$this->redirect('/orders/scheduleView');
					exit();
				}
				else
				{
					//Generate the error messages for the appropriate fields
					//this is not really necessary as save already does this, but it is an example
					//call $this->User->validates($this->data['User']); if you are not doing a save
					//then use the method below to populate the tagErrorMsg() helper method
					$this->validateErrors($this->User);
	
					//And render the edit view code
					$this->render();
				}
			}
		}
		
		$items = $this->Role->findAll();
		foreach( $items as $item ) {
			$items4select[$item['Role']['id']] = $item['Role']['name'];
		}
	
		$this->set('Roles4Select',$items4select);
	}

	function add()
	{
		$items = $this->Role->findAll();
		$trucks=$this->Lists->ListTable('ace_rp_inventory_locations','type=2 and flagactive=1');
		
		
	    foreach( $items as $item ) {
	    	$items4select[$item['Role']['id']] = $item['Role']['name'];
	    }
   
	    $this->set('Roles4Select',$items4select);

		//Users and Customers View
		$this->set('sm', 10);
		$this->set('subtitle', 'Add User');
		$this->set('show_roles', 1);
    	$this->set('trucks',$trucks);
		$this->render("edit");
	}
	
	//adds a VICIDial user
	function addDialerUser() {
		$ace_userid = $_GET['id'];
		
		$this->User->id = $ace_userid;
		$this->data = $this->User->read();
		
		$db = ConnectionManager::getDataSource('vicidial');
		
		$result = $db->_execute("
			SELECT COUNT(*) n 
			FROM vicidial_users WHERE
			user = '$ace_userid'
		");
		
		$row = mysql_fetch_array($result);
	    if($row['n'] == 0) {		
		$db->_execute("
			INSERT INTO vicidial_users (user, pass, full_name, user_level, user_group, phone_login, phone_pass, delete_users, delete_user_groups, delete_lists, delete_campaigns, delete_ingroups, delete_remote_agents, load_leads, campaign_detail, ast_admin_access, ast_delete_phones, delete_scripts, modify_leads, hotkeys_active, change_agent_campaign, agent_choose_ingroups, closer_campaigns, scheduled_callbacks, agentonly_callbacks, agentcall_manual, vicidial_recording, vicidial_transfers, delete_filters, alter_agent_interface_options, closer_default_blended, delete_call_times, modify_call_times, modify_users, modify_campaigns, modify_lists, modify_scripts, modify_filters, modify_ingroups, modify_usergroups, modify_remoteagents, modify_servers, view_reports, vicidial_recording_override, alter_custdata_override, qc_enabled, qc_user_level, qc_pass, qc_finish, qc_commit, add_timeclock_log, modify_timeclock_log, delete_timeclock_log, alter_custphone_override, vdc_agent_api_access, modify_inbound_dids, delete_inbound_dids, active, alert_enabled, download_lists, agent_shift_enforcement_override, manager_shift_enforcement_override, shift_override_flag, export_reports, delete_from_dnc, email, user_code, territory, allow_alerts, agent_choose_territories, custom_one, custom_two, custom_three, custom_four, custom_five, voicemail_id, agent_call_log_view_override, callcard_admin, agent_choose_blended, realtime_block_user_info, custom_fields_modify, force_change_password, agent_lead_search_override) 
			VALUES ('$ace_userid', '".$this->data['User']['password']."', \"".$this->data['User']['first_name']." ".$this->data['User']['last_name']."\", 3, 'Agent', '', '', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '1', '', '1', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', 'DISABLED', 'NOT_ACTIVE', '', 0, '', '', '', '0', '0', '0', 'NOT_ACTIVE', '0', '0', '0', 'Y', '0', '0', 'DISABLED', '0', '0', '0', '0', '', '', '', '0', '', '', '', '', '', '', '', 'DISABLED', '0', '1', '0', '0', 'N', 'NOT_ACTIVE')
		");
			echo "OK";
		} else {
			echo "Please check if the user is not on the dialer already.";
		}
		
		exit;
		//$this->redirect($this->referer());
	}
	
	function index()
	{
		
		if (($this->Common->getLoggedUserRoleID() != 6)&&($this->Common->getLoggedUserRoleID() != 13)) exit;
		$this->layout="list";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		$roleId = $_GET['roleId'];
		if (!$order) $order = 'username asc';
		
		$conditions = "and u.is_active=1";
		$ShowInactive = $_GET['ShowInactive'];
		if ($ShowInactive) $conditions = "";
		
		$disable_roles = array();
		$role = $_GET['data']['role'][0];
		if ($this->Common->getLoggedUserRoleID() == 13) {$role = 3; $disable_roles = array('disabled'=>'1');}
		if ($role) $conditions .= " and exists (select * from ace_rp_users_roles r where r.user_id=u.id and r.role_id=$role)";
		
		$query = "select *
		            from ace_rp_users u
				   where exists (select * from ace_rp_users_roles r where r.user_id=u.id and r.role_id!=8) $conditions  
				   order by $order $sort";
		
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $items[$row['id']][$k] = $v;
		}		
		
		$this->set('ShowInactive', $ShowInactive);
		$this->set('role', $role);
		$this->set('disable_roles', $disable_roles);
		$this->set('items', $items);
		$this->set('roleId', $roleId);
		$items = $this->Role->findAll();
		foreach( $items as $item ) {
			$items4select[$item['Role']['id']] = $item['Role']['name'];
		}
		$this->set('query',$query);
		$this->set('Roles4Select',$items4select);
	}

	// AJAX method for activation/deactivation of an item
	function changeActive()
	{
		$item_id = $_GET['item_id'];
		$is_active = $_GET['is_active'];
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$db->_execute("update ace_rp_users set is_active='".$is_active."' where id=".$item_id);

		exit;
	}
	
	function changeBoardActive()
	{
		$item_id = $_GET['item_id'];
		$is_active = $_GET['is_active'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$db->_execute("update ace_rp_users set show_board='".$is_active."' where id=".$item_id);
		exit;
	}
	
	function customer() {
		$db =& ConnectionManager::getDataSource("default");
		
		$this->set('user_id',$this->Common->getLoggedUserID());
		
		$this->User->id = $this->Common->getLoggedUserID();
		$this->data = $OrdersControllerthis->User->read();
		
		$query = "SELECT id,job_date FROM ace_rp_orders WHERE customer_id = '".$this->Common->getLoggedUserID()."' AND order_status_id = '6'";
	  	$result = $db->_execute($query);
	  	$row = mysql_fetch_array($result);
	  	$this->set('next_appointment',$row['job_date']);
	  	
	  	$pastJobs = array();
	  	$query = "SELECT id,job_date,sale_amount FROM ace_rp_orders WHERE customer_id = '".$this->Common->getLoggedUserID()."' AND order_status_id = '5'";
	  	$result = $db->_execute($query);
	  	while( $row = mysql_fetch_array($result) ) {
	  		$pastJobs[] = $row;
	  	}
	  	
	  	$this->set('pastJobs',$pastJobs);
	}
	
	function delUser($user_id)
	{
		if (!$user_id)
			$user_id = $this->params['url']['user_id'];
			
		//delete all Orders before deleting User - they are not linked up
		$orders = $this->Order->findAll(array("Order.customer_id" => $user_id));
		
		foreach( $orders as $order )
		{
			$this->Order->delete($order['Order']['id'], true);
		}
		
		//Delete All Commissions
		$commissions = $this->Commission->findAll(array("Commission.user_id" => $user_id));
		
		foreach( $commissions as $comm )
		{
			$this->Commission->delete($comm['Commission']['id'], true);
		}
		
		//Now delete User
		$this->User->delete($user_id, true);

		if ($this->params['url']['rurl'])
			$this->redirect($this->params['url']['rurl']);
		else
			$this->redirect('/users/');			
	}
	
	function delOrder($order_id)
	{
		
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

  // Method draws the list of clients to be chosen
	// Generates a call for a javascript function
	// 'addItem' from the local context when the row is selected.
	// Created: Anthony Chernikov, 08/13/2010
	function _GetClients($field, $value)
	{
		$bg = 'background:#EEFFEE;';
		$txt = 'color:#000000;';
        
		//Select all items related to the current job type
		$db =& ConnectionManager::getDataSource('default');
		$conditions = ' where i.is_active=1 ';
		if ($value)
			if ($field == 'name')
				$conditions .= ' and concat(upper(first_name),\' \',upper(last_name)) like \'%'.$value.'%\'';
			elseif ($field == 'address')
				$conditions .= ' and upper(address) like \'%'.$value.'%\'';
			elseif ($field == 'ref')
				$conditions .= ' and exists (select * from ace_rp_orders o where o.customer_id=i.id and order_number=\''.$value.'\')';
			elseif ($field == 'phone')
			{
				$value = preg_replace("/[- \.]/", "", $value);
				$value = preg_replace("/([?])*/", "[-]*", $value);
				$conditions .= ' and phone REGEXP \''.$value.'\'';
			}
		
		$query = "select i.id, i.first_name, i.last_name, i.address_unit, i.address_street_number, i.address_street, i.city,
										 i.phone, i.cell_phone, i.postal_code, i.email
								from ace_rp_customers i 
								".$conditions."
							 order by last_name asc, first_name asc limit 20";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result,MYSQL_ASSOC))
		{					
				$h .= '<tr class="item_row" id="item_'.$row['id'].'" style="cursor:pointer;"';
				$h .= 'onclick="addItem('.$row['id'].',\''.$row['first_name'].'\',\''.$row['last_name'].'\',\''.$row['address_unit'].'\',\''.$row['address_street_number'].'\',\''.$row['address_street'].'\'';
				$h .= ',\''.$row['city'].'\',\''.$row['phone'].'\',\''.$row['cell_phone'].'\',\''.$row['postal_code'].'\',\''.$row['email'].'\')"';
				$h .= 'onMouseOver="highlightCurRow(\'item_'.$row['id'].'\')">';
				$h .= '<td class="item_address_td" style="display:none">&nbsp;'.strtoupper($row['address']).'</td>';
				if ($this->Common->getLoggedUserRoleID() != 1)
				{
					$h .= '<td style='.$txt.'>&nbsp;'.$row['phone'].'</td>';
					$h .= '<td style='.$txt.'>&nbsp;'.$row['cell_phone'].'</td>';
				}
				$h .= '<td style='.$txt.'>&nbsp;'.$row['first_name'].'</td>';
				$h .= '<td style='.$txt.'>&nbsp;'.$row['last_name'].'</td>';
				$h .= '<td style='.$txt.'>&nbsp;'.$row['address_unit'].' '.$row['address_street_number'].' '.$row['address_street'].'</td>';
				$h .= '<td style='.$txt.'>&nbsp;'.$row['city'].'</td>';
				$h .= '</tr>';
		}
		
		return $h;
	}

  // Method draws a wrap for the list of clients
	// Created: Anthony Chernikov, 08/13/2010
	function _ShowClients($customerId = null, $cusPhone =null, $cusFirstName = null, $cusLastName = null, $cusEmail = null, $currentUrl=null)
	{
		$bg = 'background:#EEFFEE;';
		$txt = 'color:#000000;';

    $h = '
			<style type="text/css" media="all">
		   @import "'.ROOT_URL.'/app/webroot/css/style.css";
			</style>
			<script language="JavaScript" src="'.ROOT_URL.'/app/webroot/js/jquery.js"></script>
      <script language="JavaScript">
				function SearchFilter(element, search_type){
				  if (element.value.length>0){
						var val = element.value.toUpperCase();
						$("#Working").show();
						$.get("'.BASE_URL.'/users/getClients",{value:val,field:search_type},
									function(data){$("#Working").hide();$("#body_target").html(data);});
					}
				}
				function addItem(item_id, item_first_name, item_last_name, item_address_unit, item_address_street_number, item_address_street, item_city,
										 item_phone, item_cell_phone, item_postal_code, item_email)
				{
				  var new_item=new Array();
					new_item[0]=item_id;
					new_item[1]=item_first_name;
					new_item[2]=item_last_name;
					new_item[3]=item_address_unit;
					new_item[4]=item_address_street_number;
					new_item[5]=item_address_street;
					new_item[6]=item_city;
					new_item[7]=item_phone;
					new_item[8]=item_cell_phone;
					new_item[9]=item_postal_code;
					new_item[10]=item_email;
					window.returnValue=new_item;
					window.close();
				}
				function highlightCurRow(element){
					$(".item_row").css("background","");
					$("#"+element).css("background","#a9f5fe");
				}
				$(document).ready(function(){$("#ItemSearchString").focus();});
      </script>
      <form method="post" action= "'.BASE_URL.'/users/editCustomerInfo" name="csform">
      	<input type="hidden" name="cusId" value="'.$customerId.'">
      	<input type="hidden" name="currentUrl" value="'.$currentUrl.'">
      	

		<table>';
		// if ($this->Common->getLoggedUserRoleID() != 1)
		// {
			$h .= ' <tr>
								<td><b>Phone/Cell:</b></td>
								<td><input style="width:150px" type="text" name="phone" value="'.$cusPhone.'"/></td>
							</tr>';
		// }	
$h .= ' 		<tr>
					<td><b>First Name:</b></td>
					<td><input style="width:150px" type="text" name="firstName" value="'.$cusFirstName.'"/></td>
				</tr>
				<tr>
					<td><b>Last Name:</b></td>
					<td><input style="width:150px" type="text" name="lastName" value="'.$cusLastName.'"/></td>
				</tr>
				<tr>
					<td><b>Email:</b></td>
					<td><input style="width:150px" type="text" name="email" value="'.$cusEmail.'"/></td>
				</tr>
			</table>
			<input type="submit" value="submit"/>
			<img id="Working" style="display:none" src="'.ROOT_URL.'/app/webroot/img/wait30trans.gif"/>';
		return $h;
	}
	
  // AJAX methods for the list of all the clients
	// Created: Anthony Chernikov, 08/13/2010
	function getClients()
	{
		$field = $_GET['field'];
		$value = $_GET['value'];
		echo $this->_GetClients($field, $value);
		exit;
	}
	
	function showClients()
	{
		$customerId = $_GET['cusId'];
		$cusPhone = $_GET['cusPhone'];
		$cusFirstName = $_GET['cusFirstName'];
		$cusLastName = $_GET['cusLastName'];
		$cusEmail = $_GET['cusEmail'];
		$currentUrl = $_GET['currentUrl'];
		echo $this->_ShowClients($customerId, $cusPhone, $cusFirstName, $cusLastName, $cusEmail, $currentUrl);
		exit;
	}
	
	function GetList()
	{
		$aRet = array();		
		$sql = "select * from ace_rp_customers where phone like '%12345%'";
		$db =& ConnectionManager::getDataSource('default');
		$result = $db->_execute($sql);
		while ($aRet[] = mysql_fetch_array($result,MYSQL_ASSOC));

		return $aRet;
	}
	
	function testT()
	{
		$aRows = &$this->GetList();
		$sTempl = file_get_contents("test.html");
		foreach ($aRows as $row)
		{
			eval("\$sRes=\"$sTempl\";");
			echo $sRes;
		}
		exit;
	}

	// Export customers to csv file (only those customers that have jobs linked to them)
	// Created: Maxim Kudryavtsev, 29/01/2013
	function exportCustomers()
	{
		$userID = (integer)$_SESSION['user']['id'];
		if (($userID != 44851) && ($userID != 230964))
			die('Sorry, you have no acces to this page!');
		
		$user_fields = array(
			'id' => 'ID',
			'first_name' => 'First Name',
			'last_name' => 'Last Name',
			'city' => 'City',
			'address' => 'Address',
			'postal_code' => 'Postal Code',
			'phone' => 'Phone',
			'cell_phone' => 'Cell Phone',
			'callback_note' => 'Callback Note',
			'email' => 'Email',
			'call_result_id' => 'Call Result'
		);
		$job_fields = array(
			'job_date' => 'Job Date',
			'job_time_beg' => 'Job Time Start',
			'job_time_end' => 'Job Time End',
			'fact_job_beg' => 'Actual Job Start ',
			'fact_job_end' => 'Actual Job End',
			'order_type_id' => 'Order Type',
			'order_status_id' => 'Order Status',
			'order_substatus_id' => 'Order Substatus',
			'feedback_comment' => 'Feedback Comment',
			'feedback_suggestion' => 'Feedback Suggestion',
			'office_comment' => 'Office Comment',
			'telem_comment' => 'Telemarketer Comment',
			);
		$id_fields=array('order_type_id','order_status_id','order_substatus_id','call_result_id');
		//var_dump($_POST);
		if (isset($_POST['user_fields'])) {
			$query='select ';
			$where='';
			$delimiter=(!empty($_POST['delimiter']) ? $_POST['delimiter'] : ',');
			if (isset($_POST['dnc'])) {
				if ($_POST['dnc']=='dnc') $where=' and u.`callresult`=3 ';
				else
				if ($_POST['dnc']=='notdnc') $where=' and u.`callresult`!=3 ';
			}
			if (isset($_POST['fromdate']) && $d=strtotime($_POST['fromdate']))
				$fromdate=date('Y-m-d',$d);
			else 
				$fromdate='2007-12-31';

			if (isset($_POST['todate']) && $d=strtotime($_POST['todate']))
				$todate=date('Y-m-d',$d);
			else
				$todate=date('Y-m-d');

			$from='from 
				(select MAX(id) id from `ace_rp_orders` d where d.order_status_id>0 and d.`job_date`>=\''.$fromdate.'\' and d.`job_date`<=\''.$todate.'\' group by d.customer_id) a
				left join `ace_rp_orders` o on o.`id`=a.id
				left join ace_rp_customers u on o.`customer_id`=u.id ';

			$fields_csv=array();

			foreach ($_POST['user_fields'] as $f) {
				if (isset($user_fields[$f]) && !in_array($f,$id_fields)) {
					if ($f=='address')
						$query.='CONCAT(u.address_unit," ",u.address_street_number," ",u.address_street) as u.`'.$f.'`, ';
					else
						$query.='u.`'.$f.'`, ';
					$fields_csv[]=$user_fields[$f];
				}
			}
			foreach ($_POST['job_fields'] as $f) {
				if (isset($job_fields[$f]) && !in_array($f,$id_fields)) {
					$query.='o.`'.$f.'`, ';
					$fields_csv[]=$job_fields[$f];
				}
			}
			if (in_array('order_type_id', $_POST['job_fields'])) {
				$query.='ot.`name` as order_type, ';
				$from.='left join `ace_rp_order_types` ot on ot.`id`=o.`order_type_id` ';
				$fields_csv[]='Order Type';
			}
			if (in_array('order_status_id', $_POST['job_fields'])) {
				$query.='os.`name` as order_status, ';
				$from.='left join `ace_rp_order_statuses` os on os.`id`=o.`order_status_id` ';
				$fields_csv[]='Order Status';
			}
			if (in_array('order_substatus_id', $_POST['job_fields'])) {
				$query.='oss.`name` as order_substatus, ';
				$from.='left join `ace_rp_order_substatuses` oss on oss.`id`=o.`order_substatus_id` ';
				$fields_csv[]='Order Substatus';
			}
			if (in_array('call_result_id', $_POST['user_fields'])) {
				$query.='cr.`name` as call_result, ';
				$from.='left join `ace_rp_call_results` cr on cr.`id`=u.`callresult` ';
				$fields_csv[]='Call Result';
			}

			$query=substr($query,0,-2).' '.$from.' where u.`is_active`=1 '.$where.' order by o.`job_date` desc'; // grouping by u.`city` and u.`address` because of many duplicates, and some people have more than 1 house and the only difference in their records is address
			//var_dump($query);
			//die;
			$db =& ConnectionManager::getDataSource('default');

			$result = $db->_execute($query);
			$csv='';
			while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
				$flds=array();
				foreach ($row as $k=>$v) {
					if ( (strpos('"',$v)!==false) || (strpos(';',$v)!==false) )
						$flds[]='"'.str_replace(array('"',$delimiter),array('""','"'.$delimiter.'"'),$v).'"';
					else
						$flds[]=$v;
				}
				$csv.='"'.implode('"'.$delimiter.'"',$flds).'"'."\r\n";
			}
			if ($csv!='') {
				$csv='"'.implode('"'.$delimiter.'"',$fields_csv).'"'."\r\n".$csv;
				$l=strlen($csv);
				header('content-disposition: attachment; filename="customers.csv"');
				header('content-length: '.$l);
				header('content-type: application/vnd.ms-excel, text/plain');
				echo $csv;
				die;
			}
			else {
				die('There is no data matching your request!'); 
			}
		}
		$this->set('user_fields', $user_fields);
		$this->set('job_fields', $job_fields);
	}

	// Search customers by params
	// Created: Maxim Kudryavtsev, 31/01/2013
	function search($sort_field='name', $sort_order='asc', $page_number=1) {
		//var_dump($_POST,$_SESSION['users_search_fields']);
		/*$userID = (integer)$_SESSION['user']['id'];
		if (($userID != 44851) && ($userID != 230964))
			die('Sorry, you have no acces to this page!');*/

		if (!is_numeric($page_number) || $page_number<1) {
			$page_number=1;
		}

		$db =& ConnectionManager::getDataSource('default');

		$orderby='i.`last_name` '.addslashes($sort_order).', i.`first_name` '.addslashes($sort_order);
		if ($sort_field=='tech_name') {
			$orderby='u.`last_name` '.addslashes($sort_order).', u.`first_name` '.addslashes($sort_order);
		}
		else
		if ($sort_field!='name')
			$orderby='i.`'.addslashes($sort_field).'` '.addslashes($sort_order);

		if ( !isset($_POST['onlyactive']) ) {
			$onlyactive=$_POST['onlyactive']=0;
		}

		$session_fields=array('search_field','search_value','onlyactive','nsfrom','nsto','exfrom','exto','ppage');
		foreach ($session_fields as $f) {
			if (isset($_POST[$f])) {
				$_SESSION['users_search_fields'][$f]=$_POST[$f];
			}
			elseif (isset($_SESSION['users_search_fields'][$f])) {
				$_POST[$f]=$_SESSION['users_search_fields'][$f];
			}
		}

		$where='';
		if (!empty($_POST['search_field']) && !empty($_POST['search_value'])) {
			if ($_POST['search_field']=='phone') {
				$val=preg_replace('/[^0-9]+/','',$_POST['search_value']);
				$where.=' and (i.`phone` like "'.$val.'%" or i.`cell_phone` like "'.$val.'%")';
			}
			elseif ($_POST['search_field']=='card_number') {
				$val=preg_replace('/[^0-9]+/','',$_POST['search_value']);
				$where.=' and replace(i.`'.addslashes($_POST['search_field']).'`,\'-\',\'\') like "'.$val.'%"';
			}
			else {
				$where.=' and i.`'.addslashes($_POST['search_field']).'` like "'.addslashes($_POST['search_value']).'%"';
			}
			$this->set('search_field', $_POST['search_field']);
			$this->set('search_value', $_POST['search_value']);
		}

		if (isset($_POST['onlyactive']) && $_POST['onlyactive']==1) {
			$where.=' and i.is_deactive !=1 and i.is_active = 1 ';
			$onlyactive=1;
		}

		if ( !empty($_POST['nsfrom']) && $d=strtotime($_POST['nsfrom']) ) {
			$where.=' and i.`next_service` >= "'.date('Y-m-d',$d).'"';
			$this->set('nsfrom', $_POST['nsfrom']);
		}
		if ( !empty($_POST['nsto']) && $d=strtotime($_POST['nsto']) ) {
			$where.=' and i.`next_service` <= "'.date('Y-m-d',$d).'"';
			$this->set('nsto', $_POST['nsto']);
		}

		if ( !empty($_POST['exfrom']) && $d=strtotime($_POST['exfrom']) ) {
			$where.=' and i.`card_exp` >= "'.date('Y-m-d',$d).'"';
			$this->set('exfrom', $_POST['exfrom']);
		}
		if ( !empty($_POST['exto']) && $d=strtotime($_POST['exto']) ) {
			$where.=' and i.`card_exp` <= "'.date('Y-m-d',$d).'"';
			$this->set('exto', $_POST['exto']);
		}

		if (isset($_POST['ppage']) && intval($_POST['ppage'])>0) {
			$ppage=intval($_POST['ppage']);
		}
		else
			$ppage=20;

		$db =& ConnectionManager::getDataSource('default');

		// Prepare Active Members Count
		$r = $db->_execute('select count(*) from ace_rp_customers i 
					where card_number!="" and i.`card_exp`>NOW() and i.is_deactive !=1');
		$this->set('active_members_count', @mysql_result($r,0,0));

		// Prepare Expired Members Count
		$r = $db->_execute('select count(*) from ace_rp_customers i 
					where card_number!="" and i.`card_exp`<NOW()');
		$this->set('exp_members_count', @mysql_result($r,0,0));

		// Prepare Cities
		$r = $db->_execute('select distinct(i.`city`) as c from ace_rp_customers i 
					where card_number!="" order by i.`city`');
		$cities=array();
		while ($row = mysql_fetch_array($r,MYSQL_ASSOC)) {
			$cities[]=$row['c'];
		}
		$this->set('cities', $cities);

		// $query = 'from ace_rp_customers i
		// 			left join `ace_rp_order_items` oi on oi.`name`=i.card_number
		// 			left join `ace_rp_orders` o on o.id=oi.`order_id`
		// 			left join ace_rp_users u on u.`id`=o.`job_technician1_id`
		// 		where i.card_number!="" '.$where.'
		// 		order by '.$orderby;
	
		// $query='select i.id, i.first_name, i.last_name, i.email, i.phone, i.cell_phone, i.card_number, i.card_exp, i.next_service, CONCAT(i.address_unit," ",i.address_street_number," ",i.address_street) as address, i.city, i.callback_date, u.`first_name` as tname, u.`last_name` as tlname where i.card_number!="" '.$where.'
		// 	order by '.$orderby;

		// $r = $db->_execute('select count(*) '.$query);
		$r = $db->_execute('select count(*) from ace_rp_customers i where card_number!="" and  i.card_exp > CURRENT_DATE'.$where);
		$rows_count=@mysql_result($r,0,0);
		$pages_count=ceil($rows_count/$ppage);
		if ($page_number>1 && $page_number>$pages_count) $page_number=$pages_count;
		$limit=$ppage*($page_number-1).','.$ppage;

		$query='select i.id, i.first_name, i.last_name, i.email, i.phone, i.cell_phone, i.card_number, i.card_exp, i.next_service, CONCAT(i.address_unit," ",i.address_street_number," ",i.address_street) as address, i.city, i.callback_date, i.is_deactive from ace_rp_customers i where i.card_number!="" and i.card_exp > CURRENT_DATE '.$where.' group by (i.`id`)
			order by '.$orderby.' limit '.$limit;

		// print_r($query);die;
		$result = $db->_execute($query);
		$items=array();
		while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
			$items[]=$row;
		}

		$this->set('items', $items);
		$this->set('sort_field', $sort_field);
		$this->set('sort_order', $sort_order);
		$this->set('ppage', $ppage);
		$this->set('pages_count', $pages_count);
		$this->set('page_number', $page_number);
		$this->set('rows_count', $rows_count);

		$this->set('onlyactive', $onlyactive);
	}

	// Search customers by params and calculates optimal route (by postal codes distancies)
	// Created: Maxim Kudryavtsev, 12/02/2013
	function distances($page_number=1) {
		//var_dump($_POST,$_SESSION['users_search_fields']);
		/*$userID = (integer)$_SESSION['user']['id'];
		if (($userID != 44851) && ($userID != 230964))
			die('Sorry, you have no acces to this page!');*/

		$session_fields=array('city','postal_code','from','to','job_type');
		foreach ($session_fields as $f) {
			if (isset($_POST[$f])) {
				$_SESSION['users_distances_fields'][$f]=$_POST[$f];
			}
			elseif (isset($_SESSION['users_distances_fields'][$f])) {
				$_POST[$f]=$_SESSION['users_distances_fields'][$f];
			}
		}

		$where='';
		if (isset($_POST['city']) && $_POST['city']!='') {
			$where.= 'and u.city="'.addslashes($_POST['city']).'"';
		}
		if (isset($_POST['postal_code']) && $_POST['postal_code']!='') {
			$where.= ' and u.postal_code like "'.addslashes($_POST['postal_code']).'%"';
		}

		$db =& ConnectionManager::getDataSource('default');

		$orderby='u.postal_code, u.`last_name`, u.`first_name` ';

		$where_job='';

		if ( !empty($_POST['from']) && $d=strtotime($_POST['from']) ) {
			$where_job.=' and d.`job_date` >= "'.date('Y-m-d',$d).'"';
			$this->set('from', $_POST['from']);
		}
		if ( !empty($_POST['to']) && $d=strtotime($_POST['to']) ) {
			$where_job.=' and d.`job_date` <= "'.date('Y-m-d',$d).'"';
			$this->set('to', $_POST['to']);
		}
		if ( !empty($_POST['job_type']) &&  !empty($_POST['job_type']) ) {
			$where_job.=' and d.`order_type_id` = "'.intval($_POST['job_type']).'"';
			$this->set('job_type', $_POST['job_type']);
		}

		$db =& ConnectionManager::getDataSource('default');

		// Prepare Cities
		$r = $db->_execute('select DISTINCT(u.city) as c from ace_rp_customers u order by u.city');
		/*$r = $db->_execute('select DISTINCT(u.city) as c from 
						(select MAX(id) id from `ace_rp_orders` d where d.order_status_id>0 group by d.customer_id) a
						left join `ace_rp_orders` o on o.`id`=a.id
						left join ace_rp_users u on o.`customer_id`=u.id
						left join `ace_rp_order_types` ot on ot.`id`=o.`order_type_id`');*/
		$cities=array();
		while ($row = mysql_fetch_array($r,MYSQL_ASSOC)) {
			$cities[]=$row['c'];
		}
		$this->set('cities', $cities);

		// Prepare Job types
		$r = $db->_execute('select id, name from `ace_rp_order_types` t where t.`flagactive`=1 order by t.`id`');
		$job_types=array();
		while ($row = mysql_fetch_array($r,MYSQL_ASSOC)) {
			$job_types[]=$row;
		}
		$this->set('job_types', $job_types);

		if (isset($_POST['submit2'])) {
			// cleaning postal code
			$result = $db->_execute('select u.id, u.`postal_code` from `ace_rp_customers` u where u.`postal_code` not REGEXP "^[a-z0-9]*$"');
			while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
				$new_pc=preg_replace('/[^A-z0-9]/','',str_replace('\\','',$row['postal_code']));
				if ($new_pc==$row['postal_code'])$new_pc='';
				//echo $row['postal_code'].' -> '.$new_pc.'<br />';
				$db->_execute('update `ace_rp_customers` u set u.`postal_code` = "'.$new_pc.'" where u.id='.$row['id']);
			}
			// /cleaning postal code

			$query = 'select 
						CONCAT(u.address_unit," ",u.address_street_number," ",u.address_street) as address, u.id, u.`first_name`, u.`last_name`, u.`city`, u.`postal_code`, u.`phone`, u.`cell_phone`, o.`job_date`, o.`order_type_id`, ot.`name`
					from 
						(select MAX(id) id from `ace_rp_orders` d where d.order_status_id>0 '.$where_job.' group by d.customer_id) a
						left join `ace_rp_orders` o on o.`id`=a.id
						left join ace_rp_customers u on o.`customer_id`=u.id
						left join `ace_rp_order_types` ot on ot.`id`=o.`order_type_id`
					where u.`is_active`=1 '.$where.' order by '.$orderby;
	
			/*$r = $db->_execute($query);
			/*$rows_count=@mysql_result($r,0,0);
			$pages_count=ceil($rows_count/$ppage);
			if ($page_number>1 && $page_number>$pages_count) $page_number=$pages_count;
			$limit=$ppage*($page_number-1).','.$ppage;
	
			$query='select i.id, i.first_name, i.last_name, i.email, i.phone, i.cell_phone, i.card_number, i.card_exp, i.next_service, i.address, i.city, i.callback_date, u.`first_name` as tname, u.`last_name` as tlname '.$query.' limit '.$limit;*/
			$result = $db->_execute($query);
			$zones=array();
			$nozone=array();
			while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
				//var_dump($row);
				$postal_code=strtolower(trim($row['postal_code']));
				if (preg_match('/^(v[0-9][a-z]).*/',$postal_code,$m)) {
					$pc=$m[1];
	
					if (!isset($zones[$pc]))
						$zones[$pc]=array();
					$zones[$pc][]=$row;
				}
				else {
					$nozone[]=$row;
				}
	
			}
	
			$out=array();
			$next_zone='v5c';
			$dist=0;
			while (count($zones)>0) {
				if (isset($zones[$next_zone])) {
					$zones[$next_zone][0]['distance']=$dist;
					$out=array_merge($out,$zones[$next_zone]);
					//$out[]=array('rows'=>$zones[$next_zone],'distance'=>$dist);
					unset($zones[$next_zone]);
					if (count($zones)==0) break;
				}
				$to=implode('","',array_keys($zones));
				$r = $db->_execute('select * from `ace_rp_postal_distances` d where d.`from`="'.$next_zone.'" and d.`to` in ("'.$to.'") order by meters limit 1');
				if ($row = mysql_fetch_array($r,MYSQL_ASSOC)) {
					//echo '<br>select * from `ace_rp_postal_distances` d where d.`from`="'.$next_zone.'" and d.`to` in ("'.$to.'") order by meters limit 1<br>';
					$next_zone=$row['to'];
					//echo '$next_zone='.$next_zone.'<br>';
					$dist=$row['meters'];
					//echo '$dist='.$dist.'<br>';
				}
				else {
					$r = $db->_execute('select * from `ace_rp_postal_distances` d where d.`to`="'.$next_zone.'" and d.`from` in ("'.$to.'") order by meters limit 1');
					if ($row = mysql_fetch_array($r,MYSQL_ASSOC)) {
						$next_zone=$row['from'];
						$dist=$row['meters'];
					}
					else {
						foreach ($zones as $k=>$v)
							$nozone=array_merge($nozone,$v);
						unset($zones);
						break;
					}
				}
			}
			//var_dump($out);
			if (count($nozone)>0) {
				$nozone[0]['distance']='unknown';
				$out=array_merge($out,$nozone);
			}
			//$out[]=array('rows'=>$nozone,'distance'=>'unknown');
			unset($nozone);
			$_SESSION['users_distances_array']=$out;
			unset($out);
		}

		//$out=&$_SESSION['users_distances_array'];

		if (!is_numeric($page_number) || $page_number<1) {
			$page_number=1;
		}
		$rows_count=count($_SESSION['users_distances_array']);
		$ppage=20;
		$pages_count=ceil($rows_count/$ppage);
		//$last_row=max( ($ppage*$page_number), $rows_count);


		$this->set('items', &$_SESSION['users_distances_array']);
		//unset($out);
		$this->set('city', $_POST['city']);
		$this->set('postal_code', $_POST['postal_code']);
		/*$this->set('sort_order', $sort_order);

		$this->set('onlyactive', $onlyactive);*/
		$this->set('ppage', $ppage);
		$this->set('pages_count', $pages_count);
		$this->set('page_number', $page_number);
		$this->set('rows_count', $rows_count);
	}

	// #LOKI- User monitoring 
	function userMonitoring()
	{ 
	 	$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
	 	
 		$query = "SELECT u.id, u.first_name, u.extension_id, u.session_id, r.name from ace_rp_users u INNER JOIN ace_rp_users_roles ur on ur.user_id = u.id INNER JOIN ace_rp_roles r on r.id = ur.role_id where u.is_login=1 AND u.id !=".$_SESSION['user']['id'];
 		$result = $db->_execute($query);
 		$users = array();
 		while($row = mysql_fetch_array($result)) {
			$users[] = $row;
		}
		$this->set('users', $users);
 	}
 	// #LOKI- Set user deactive for members report customers
 	function setUserDeactive()
 	{
 		$userId = $_GET['userId'];
 		$isDeactive = $_GET['isDeactive'];
 		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
 		$query = "UPDATE ace_rp_customers set is_deactive=".$isDeactive." where id=".$userId;
 		$result = $db->_execute($query);
 		exit;
  	}

  	function editCustomerInfo()
  	{
  		$phone = $_POST['phone'];
		$email = $_POST['email'];
		$firstName = $_POST['firstName'];
		$lastName = $_POST['lastName'];  
		$customerId = $_POST['cusId'];
		$currentUrl = $_POST['currentUrl'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
 		$query = "UPDATE ace_rp_customers set phone='".$phone."', first_name='".$firstName."', last_name='".$lastName."', email= '".$email."' where id=".$customerId;
 		$result = $db->_execute($query);
 		if ($result) {

 			echo "<script>window.close();</script>";
 			//$this->redirect($currentUrl);
 			//header('Location: '.$_SERVER['REQUEST_URI']);	
 		}
		exit;
  	}
}
?>
