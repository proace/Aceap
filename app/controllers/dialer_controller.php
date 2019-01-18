<?php
class DialerController extends AppController{

/**
 * Enter description here...
 *
 * @var unknown_type
 */
	 var $name = 'Dialer';

/**
 * Enter description here...
 *
 * @var unknown_type
 */				
	//var $helpers = array('Html');
	
	var $helpers = array('Common');
	var $components = array('HtmlAssist','Common','Lists');

/**
 * This controller does not use a model
 *
 * @var $uses
 */
	 var $uses = array('Order', 'CallRecord', 'User', 'Customer', 'OrderItem',
                    'Timeslot', 'OrderStatus', 'OrderType', 'Item',
                    'Zone','PaymentMethod','ItemCategory','InventoryLocation',
					'OrderSubstatus','Coupon','Setting','CallResult','Invoice', 'Question', 'Payment', 'Invoice');
					
					
	var $member_card1_item_id=1106; //Added by Maxim Kudryavtsev - for booking member cards
	var $member_card2_item_id=1107; //Added by Maxim Kudryavtsev - for booking member cards

	var $beforeFilter = array('checkAccess');
/**
 * Displays a view
 *
 */
	 function display() {

		  if (!func_num_args()) {
				$this->redirect('/');
		  }

		  $path=func_get_args();

		  if (!count($path)) {
				$this->redirect('/');
		  }

		  $count  =count($path);
		  $page   =null;
		  $subpage=null;
		  $title  =null;

		  if (!empty($path[0])) {
				$page = $path[0];
		  }

		  if (!empty($path[1])) {
				$subpage = $path[1];
		  }

		  if (!empty($path[$count - 1])) {
				$title = ucfirst($path[$count - 1]);
		  }

		  $this->set('page', $page);
		  $this->set('subpage', $subpage);
		  $this->set('title', $title);

		  // add this snippet before the last line
		  if (method_exists($this, $page)) {
		    $this->$page();
		  } 

		  $this->render(join('/', $path));
	 }

	


	
	function index(){
	error_reporting(E_ALL);
	    $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
if($_SERVER['REMOTE_ADDR']=='27.7.241.57'){
	//echo $agent_id =$_SESSION['user']['id'];
	//echo '<pre>';print_r($_SESSION);
}
	/*    
	  if(isset($_POST['submit_disposition'])){
			
			$first_name     = $_REQUEST['first_name'] ;
			$last_name      = $_REQUEST['last_name'] ;
			$postal_code    = $_REQUEST['postal_code'] ;
			$email 		    = $_REQUEST['email'] ;
			$address_street = $_REQUEST['address_street'] ;
			$city           = $_REQUEST['data']['Customer']['city'] ;
			$phone          = $_REQUEST['phone'] ;
			$disposition    = $_REQUEST['disposition'] ;
			$customer_id    = $_REQUEST['customer_id'] ;
			if($_REQUEST['call_back_date']!='')
					$callback_date    = date('Y-m-d',strtotime($_REQUEST['call_back_date'])) ;
				else $callback_date    = '';
			$callback_time    = $_REQUEST['call_back_time'] ;
	        
	        $agent_id =$_SESSION['user']['id'];

			//if($_SERVER['REMOTE_ADDR'] == '27.7.241.57'){
				//echo '<pre>';print_r($_REQUEST);echo $callback_date; die;
			//}
	   
	    if($disposition == 2 || $disposition == 4 || $disposition == 6 || $disposition == 8 ){
			$query="update customer_list set 	customer_disposition='".$disposition."', agent_id='".$agent_id."', status=0  where customer_id='".$customer_id."'";
			$results = $db->_execute($query);
			
		}
		else{
			
			$query = "delete from customer_list where customer_id ='".$customer_id."'";
			$results = $db->_execute($query);
			
			 $query = " INSERT INTO ace_rp_customers(first_name,last_name,postal_code,email,address_street,city,phone,disposition_id,lastcall_date,created,modified,callback_date,callback_time)
	           VALUES('".$first_name."','".$last_name."','".$postal_code."','".$email."','".$address_street."','".$city."','".$phone."','".$disposition."','".date('Y-m-d')."','".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."','".$callback_date."','".$callback_time."')";
			
			$results = $db->_execute($query);
	 	
		}	
			
 
	     }
		 */
	  
		
		$this->set('allCities',$this->Lists->ActiveCities());
		
		$query = "SELECT * FROM `customer_list` WHERE customer_disposition <=> NULL and status = 1 LIMIT 1";
		$result = $db->_execute($query);
		$customer=mysql_fetch_array($result, MYSQL_ASSOC);
		$this->set('customer',$customer);

		//$this->set('cities_with_id',$cities_with_id);
		
		  /****************************************************************/
		  
		  
		  //$this->layout='edit';
		if (!empty($this->data['Order']))
		{
			//If order information is submitted - save the order
			
			$this->saveOrder();
		}
		else
		{  
			// If no order data is submitted, we'll have one of the following situations:
			// 1. we are being asked to display an existing order's data ($order_id!='')
			// 2. we are being asked to create a new order for an existing customer ($order_id=='', $customer_id!='')
			// 3. we are being asked to create a completely new customer ($order_id=='', $customer_id=='')
			// Check submitted data for any special parameters to be set
			//$order_id = $this->params['url']['order_id'];
			//$customer_id = $this->params['url']['customer_id'];
			$customer_id='';
			$order_id=0;
			$num_items = 0;
			$show_app_order='display:none';
			$show_permits = 'display:none';

			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

			//Remove all reserved timeslots
			$query = "
				DELETE FROM ace_rp_pending_timeslots
				WHERE user_id = ".$_SESSION['user']['id']."
			";

			$result = $db->_execute($query);

			// If order ID is submitted, prepare order's data to be displayed
			if ($order_id)
			{
				// Read the order's data from database
				$this->Order->id = $order_id;
				$this->data = $this->Order->read();

				$h_booked='';
				$h_tech='';
				
				foreach ($this->data['BookingItem'] as $oi)
				{
					if ($oi['class']==0)
					{
						$h_booked .= '<tr id="order_'.$num_items.'" class="booked">';
						$h_booked .= $this->_itemHTML($num_items, $oi, true);
						$h_booked .= '</tr>';
					}
					else
					{
						$h_tech .= '<tr id="order_'.$num_items.'" class="extra">';
						$h_tech .= $this->_itemHTML($num_items, $oi, true);
						$h_tech .= '</tr>';
					}
					$num_items++;
				}
				foreach ($this->data['BookingCoupon'] as $oi)
				{
					$oi['price'] = 0-$oi['price'];
					$oi['quantity'] = 1;
					$oi['name'] = 'Discount';
					$h_booked .= '<tr id="order_'.$num_items.'" class="booked">';
					$h_booked .= $this->_itemHTML($num_items, $oi, true);
					$h_booked .= '</tr>';
					$num_items++;
				}
				$this->set('booked_items', $h_booked);
				$this->set('tech_items', $h_tech);

				//Check the job type category
				$query = "select category_id from ace_rp_order_types where id='".$this->data['Order']['order_type_id']."'";
				$result = $db->_execute($query);
				$row = mysql_fetch_array($result, MYSQL_ASSOC);
				if (($row['category_id']=='2')||($this->data['Order']['order_type_id']==10)||($this->data['Order']['order_type_id']==31)) $show_app_order='';

				if (!$this->data['Order']['app_ordered_pickup_date'])
					$this->data['Order']['app_ordered_pickup_date'] = date('d M Y');
				else
					$this->data['Order']['app_ordered_pickup_date'] = date('d M Y', strtotime($this->data['Order']['app_ordered_pickup_date']));

				if (!$this->data['Order']['app_ordered_date'])
					$this->data['Order']['app_ordered_date'] = date('d M Y');
				else
					$this->data['Order']['app_ordered_date'] = date('d M Y', strtotime($this->data['Order']['app_ordered_date']));

				//Load current questions
				$this->set('CurrentQuestionsTextOffice', $this->_showQuestions($order_id, 0));
				$this->set('CurrentQuestionsTextTech', $this->_showQuestions($order_id, 1));

				//Load Created By
				$created_by = $this->data['Order']['created_by'];
				if($this->data['Order']['created_date'] != '')
					$created_date = date('d M Y (H:i:s)', strtotime($this->data['Order']['created_date']));
				else
					$created_date = '';

				//Load Modified By
				$modified_by = $this->data['Order']['modified_by'];
				if($this->data['Order']['modified_date'] != '')
					$modified_date = date('d M Y (H:i:s)', strtotime($this->data['Order']['modified_date']));
				else
					$modified_date = '';

				//Permits and so on
				$this->OrderType->id = $this->data['Order']['order_type_id'];
				$aa = $this->OrderType->read();
				$this->data['OrderType'] = $aa['OrderType'];
				if ($this->data['OrderType']['category_id']==2)
					$show_permits = '';

				//Techs' commissions
				$comm = $this->requestAction('/commissions/getForOrder/'.$order_id);
				$tech1_comm = $comm[1]['total_comm'];
				$tech2_comm = $comm[2]['total_comm'];
				if ($this->data['Order']['booking_source_id']==$this->data['Order']['job_technician1_id'])
					$tech1_comm += $comm[3]['total_comm'];
				elseif ($this->data['Order']['booking_source_id']==$this->data['Order']['job_technician2_id'])
					$tech2_comm += $comm[3]['total_comm'];
				elseif ($this->data['Order']['booking_source2_id']==$this->data['Order']['job_technician1_id'])
					$tech1_comm += $comm[4]['total_comm'];
				elseif ($this->data['Order']['booking_source2_id']==$this->data['Order']['job_technician2_id'])
					$tech2_comm += $comm[4]['total_comm'];
				$this->set('tech1_comm', round($tech1_comm, 2));
				$this->set('tech2_comm', round($tech2_comm, 2));
				$this->set('tech1_comm_link', BASE_URL."/commissions/calculateCommissions?cur_ref=".$this->data['Order']['order_number']);
				$this->set('tech2_comm_link', BASE_URL."/commissions/calculateCommissions?cur_ref=".$this->data['Order']['order_number']);
			}
			else
			{
			// The 'new order' situation
			$created_by = $this->Common->getLoggedUserID();
			$created_date = date('Y-m-d H:i');
			$modified_by = $this->Common->getLoggedUserID();
			$modified_date = date('Y-m-d H:i');

			// Retrieve an additional information from the submitted parameters
			/*
			$this->data['Order']['job_date'] = $this->params['url']['job_date'];
			$this->data['Order']['job_time_beg'] = $this->params['url']['job_time_beg'];
			$this->data['Order']['job_technician1_id'] = $this->params['url']['job_technician1_id'];
			$this->data['Order']['job_technician2_id'] = $this->params['url']['job_technician2_id'];
			*/
       
			// Orders created by the 'new callback' action are callbacks
			if ( in_array($_GET['action_type'], array('callback','comeback','dnc') ) )//$_GET['action_type'] == 'callback')
				$this->data['Order']['order_status_id'] = 7;
			else
				$this->data['Order']['order_status_id'] = 1;

				// Default sub-status: Not confirmed (1)
				$this->data['Order']['order_substatus_id'] = 1;

				// Default source for the new order is:
				// 1. a currently logged telemarketer or
				// 2. empty - if the current user has another role
				if (($this->Common->getLoggedUserRoleID() == 3)
				  ||($this->Common->getLoggedUserRoleID() == 9))	//TELEMARKETER OR LIMITED TELEMARKETER
					$this->data['Order']['booking_source_id'] = $this->Common->getLoggedUserID();

				// If customer ID is submitted, read the customer's data
				if ($customer_id)
				{
					$this->Order->Customer->id = $customer_id;
					$aa = $this->Order->Customer->read();
					$this->data['Customer'] = $aa['Customer'];
				}

				// Generating new invoice number
				$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
				$query = "SELECT max(order_number) num FROM ace_rp_orders";
				$result = $db->_execute($query);
				$row = mysql_fetch_array($result);
				$this->data['Order']['order_number'] = 1+$row['num'];
			}
		}
		$this->set('num_items', $num_items);

		// New call history records are callbacks by default
		$this->data['CallRecord']['call_result_id'] = 2;
		$this->data['CallRecord']['call_date'] = date("d M Y");
		$this->data['CallRecord']['call_user_id'] = $this->Common->getLoggedUserID();
		$this->data['CallRecord']['callback_user_id'] = $this->Common->getLoggedUserID();

		if ( $_GET['action_type'] == 'dnc') $this->data['CallRecord']['call_result_id'] = 3;
		if ( $_GET['action_type'] == 'comeback') $this->data['CallRecord']['call_result_id'] = 10;

		// Currently open page
		if ( in_array($this->params['url']['action_type'], array('callback','comeback','dnc') ) )
		{
			$this->set('tab_num',3);
			$this->set('tab1','tabOff');
			$this->set('tab3','tabOver');
			$this->set('page1','none');
			$this->set('page3','block');
		}
		else
		{
			$this->set('tab_num',1);
			$this->set('tab1','tabOver');
			$this->set('tab3','tabOff');
			$this->set('page1','block');
			$this->set('page3','none');
		}

		// PREPARE DATA FOR UI
		// Get Associated Options
		if($_SERVER['REMOTE_ADDR']=='27.7.242.171'){
			
			//if (!$this->data['Order']['permit_applied_date'])echo 'yes';else echo 'no';
			//echo '<pre>';print_r($this->data);echo '</pre>';
		}
	/*	if (!$this->data['Order']['permit_applied_date'])
			$this->data['Order']['permit_applied_date'] = date('d M Y');
		else
			$this->data['Order']['permit_applied_date'] = date('d M Y', strtotime($this->data['Order']['permit_applied_date']));
*/
		$this->set('job_trucks', $this->HtmlAssist->table2array($this->InventoryLocation->findAll(array('type' => '2'), null, null, null, 1, 0), 'id', 'name'));
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query =  "SELECT id, CONCAT(name, REPLACE(REPLACE(flagactive, 0, ' [INACTIVE]'), 1, '')) AS truck FROM ace_rp_inventory_locations";

		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $items[$row['id']][$k] = $v;
		}

		$this->set('job_trucks2', $items);

		$this->set('job_statuses', $this->HtmlAssist->table2array($this->OrderStatus->findAll(), 'id', 'name'));
		$this->set('job_types', $this->HtmlAssist->table2array($this->OrderType->findAll(array("OrderType.flagactive",1)), 'id', 'name'));
		$this->set('call_results', $this->HtmlAssist->table2array($this->CallResult->findAll(), 'id', 'name'));
		$this->set('booking_sources', $this->Lists->BookingSources());
		$this->set('admins', $this->Lists->Admins());
		$this->set('verificators', $this->Lists->Supervisors());
		$this->set('payment_methods', $this->HtmlAssist->table2array($this->Order->PaymentMethod->findAll(), 'id', 'name'));
		$this->set('sub_status', $this->HtmlAssist->table2array($this->Order->OrderSubstatus->findAll(), 'id', 'name'));
		$this->set('allTechnician',$this->Lists->Technicians(true));
		$this->set('allSuppliers',$this->Lists->ListTable('ace_rp_suppliers','',array('name','phone')));
		$this->set('allPermitMethods',$this->Lists->ListTable('ace_rp_apply_methods'));
		$this->set('allPermitStates',$this->Lists->ListTable('ace_rp_permit_states'));
		//$this->set('allCities',$this->Lists->ListTable('ace_rp_cities'));
		$this->set('allCities',$this->Lists->ActiveCities());
		$this->set('txt_customer_note','');
		$this->set('show_app_order',  $show_app_order);
		$this->set('show_permits', $show_permits);
		$this->set('comm_roles',$this->Lists->ListTable('ace_rp_commissions_roles'));
		$this->set('cancellationReasons', $this->Lists->CancellationReasons());
		//$this->set('recordingFile', $recordingFile);

		// Past Order View Mode
	//	if ($this->data['Status']['name'] == 'Done') $this->set('ViewMode', 1);

	//	$this->data['Coupon'] = $this->Coupon->findAll();

		//Make Redo Orders List
		//$redo_orders = $this->_getPreviousJobs($this->data['Customer']['id']);
	/*	if ($this->data['Order']['job_estimate_id'])
		{
			$past_orders = $this->Order->findAll(array('Order.id'=> $this->data['Order']['job_estimate_id']), null, "job_date DESC", null, null, 1);
			foreach ($past_orders as $ord)
				$job_estimate_text = 'REF# '.$ord['Order']['order_number'].' - '.date('d M Y', strtotime($ord['Order']['job_date']));
		}
*/
		// Find customer's notes
	/*	if ($this->data['Customer']['id'])
		{
        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
        $query = "SELECT * FROM ace_rp_users_notes WHERE user_id=".$this->data['Customer']['id']." ORDER BY note_date DESC";
        $result = $db->_execute($query);
        while ($row = mysql_fetch_array($result))
            $customer_notes[$row['id']] = $row;
		}*/
	/*	$this->set('past_orders', $past_orders);
		$this->set('redo_orders', $redo_orders);
		$this->set('customer_notes',$customer_notes);
		$this->set('job_estimate_text',$job_estimate_text);
		$this->set('yesOrNo', $this->Lists->YesOrNo());
*/
		// Prepare dates for selector
	/*	if ((strlen($this->data['Order']['job_date']) > 0) && ($this->data['Order']['job_date'] != "0000-00-00"))
			$this->data['Order']['job_date'] = date("d M Y", strtotime($this->data['Order']['job_date']));
		if ((strlen($this->data['CallRecord']['callback_date']) > 0) && ($this->data['CallRecord']['callback_date'] != "0000-00-00"))
			$this->data['CallRecord']['callback_date'] = date("d M Y", strtotime($this->data['CallRecord']['callback_date']));
		if ((strlen($this->data['CallRecord']['call_date']) > 0) && ($this->data['CallRecord']['call_date'] != "0000-00-00"))
			$this->data['CallRecord']['call_date'] = date("d M Y", strtotime($this->data['CallRecord']['call_date']));
*/
		// Load created/modified Info
		$this->User->id = $created_by;
		$User_details = $this->User->read();
		$created_by = $User_details['User']['first_name'].' '.$User_details['User']['last_name'];

		$this->User->id = $modified_by;
		$User_details = $this->User->read();
		$modified_by = $User_details['User']['first_name'].' '.$User_details['User']['last_name'];

		$this->set('created_date',$created_date);
		$this->set('modified_by',$modified_by);
		$this->set('created_by',$created_by);
		$this->set('modified_date',$modified_date);

		$query = "
			SELECT REPLACE(name, ' ', '_') name, internal_id
			FROM ace_rp_cities
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$cities_with_id[$row['internal_id']]['name']= $row['name'];
		}

		if(isset($order_id)) {
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
				LEFT JOIN ace_rp_customers u
				ON n.user_id = u.id
				WHERE n.order_id = $order_id
				ORDER BY n.note_date ASC
			";

			$result = $db->_execute($query);
			$i = 0;$notes=array();
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$notes[$row['id']]['message'] = $row['message'];
				$notes[$row['id']]['note_type_id'] = $row['note_type_id'];
				$notes[$row['id']]['order_id'] = $row['order_id'];
				$notes[$row['id']]['user_id'] = $row['user_id'];
				$notes[$row['id']]['urgency_id'] = $row['urgency_id'];
				$notes[$row['id']]['note_date'] = $row['note_date'];
				$notes[$row['id']]['note_type_name'] = $row['note_type_name'];
				$notes[$row['id']]['urgency_name'] = $row['urgency_name'];
				$notes[$row['id']]['urgency_image'] = $row['image_file'];
				$notes[$row['id']]['author_name'] = $row['author_name'];
				$notes[$row['id']]['row_class'] = $i++%2==0?"even_row":"odd_row";
			}

			$this->set('notes',$notes);
		} //END retrieve notes

		$this->set('cities_with_id',$cities_with_id);

		$query = "
			SELECT *
			FROM ace_rp_settings
			WHERE id IN(21)
		";

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result)) {
			$use_template_questions = $row['valuetxt'];
		}

		$this->set('use_template_questions',$use_template_questions);

		if(isset($order_id)) {
			$query = "SELECT photo_1, photo_2 FROM ace_rp_orders WHERE id = ".$order_id;
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result)) {
					$this->data['Order']['photo_1'] = $this->getPhotoPath($row['photo_1']);
					$this->data['Order']['photo_2'] = $this->getPhotoPath($row['photo_2']);
			}
		}
		    
		
		
		
	}
	
/*****************************************************************************************/
/*****************************************************************************************/
/*****************************************************************************************/
	 function saveOrder($saveCustomer=1)
    {
		//Prepare the date for entry into the DB
		//Set nulls into the empty selects
		$this->Common->SetNull($this->data['Order']['booking_source_id']);
		$this->Common->SetNull($this->data['Order']['job_truck']);
		$this->Common->SetNull($this->data['Order']['order_type_id']);
		$this->Common->SetNull($this->data['Order']['customer_desired_payment_method_id']);
		$this->Common->SetNull($this->data['Order']['job_technician1_id']);
		$this->Common->SetNull($this->data['Order']['job_technician2_id']);
		$this->Common->SetNull($this->data['Order']['job_technician2_id']);
		$this->Common->SetNull($this->data['Order']['job_reference_id']);

		// Default booking date is today, telemarketer - current user
		$SendToDisp = false;
		if (!$this->data['Order']['id'])
		{
			$SendToDisp = true; //We'll be sending messages to the dispatcher for every new booking created
	        $this->data['Order']['booking_date'] = date('Y-m-d');
	        $this->data['Order']['booking_telemarketer_id'] = $this->Common->getLoggedUserID();

	        // Also we need to set the 'created..' fields here
			$this->data['Order']['created_by'] = $this->Common->getLoggedUserID();
			$this->data['Order']['created_date'] = date('Y-m-d H:i:s');
		}
		else
		{
	        // For the existing orders set the 'modyfied..' fields
			$this->data['Order']['modified_by'] = $this->Common->getLoggedUserID();
			$this->data['Order']['modified_date'] = date('Y-m-d H:i:s');
		}

		// Default Order Status: booked (1)
		if (!$this->data['Order']['order_status_id']) $this->data['Order']['order_status_id'] = 1;

		// Default sub-status: Not confirmed (1)
		if ($this->data['Order']['order_substatus_id'] == '')
			$this->data['Order']['order_substatus_id'] = 1;

		// Default Customer: Customer ID == -1   ->  new customer
		if ($this->data['Customer']['id'] == -1) $this->data['Customer']['id'] = '';

		//Added by Maxim Kudryavtsev - for booking member cards
		for ($i = 0; $i < count($this->data['Order']['BookingItem']); $i++)
		{
			$item_id=intval( $this->data['Order']['BookingItem'][$i]['item_id'] );
			if ($item_id==$this->member_card1_item_id || $item_id==$this->member_card2_item_id)
			{
				if ( $this->data['Customer']['card_number'] != $this->data['Order']['BookingItem'][$i]['name'] ) {
					$this->data['Customer']['card_number']=$this->data['Order']['BookingItem'][$i]['name'];
					$this->data['Order']['BookingItem'][$i]['part_number']=$this->data['Order']['BookingItem'][$i]['next_service'];
					$this->data['Customer']['card_exp']=(date('Y')+ ($item_id==$this->member_card1_item_id? 1:2) ).'-'.date('m-d');
					$this->data['Customer']['next_service']=$this->data['Order']['BookingItem'][$i]['next_service'];
				}
			}
		}
		// /Added by Maxim Kudryavtsev - for booking member cards

		// Save the customer
		if ($saveCustomer==1)
			if (!empty($this->data['Customer'])) $last_inserted_customer_id=$this->_SaveCustomer();

			// Cancelled jobs shouldn't have a time
		if ($this->data['Order']['order_status_id'] == 3)
		{
			$this->data['Order']['job_time_beg_hour'] = '';
			$this->data['Order']['job_time_beg'] = '';
			$this->data['Order']['job_time_end_hour'] = '';
			$this->data['Order']['job_time_end'] = '';
		}

		// Get some customer's information into this order
		$this->data['Order']['job_postal_code'] = $this->data['Customer']['postal_code'];
		$this->data['Order']['customer_id'] = $this->data['Customer']['id'];

		// Convert the dates into the appropriate format
		$this->data['Order']['job_date'] = date("Y-m-d", strtotime($this->data['Order']['job_date']));
		$this->data['Order']['app_ordered_date'] = date("Y-m-d", strtotime($this->data['Order']['app_ordered_date']));
		$this->data['Order']['app_ordered_pickup_date'] = date("Y-m-d", strtotime($this->data['Order']['app_ordered_pickup_date']));
		$this->data['Order']['permit_applied_date'] = date("Y-m-d", strtotime($this->data['Order']['permit_applied_date']));

		//set beginning and ending time
		if($this->data['Order']['job_time_beg_hour'] != '')
			$this->data['Order']['job_time_beg'] = $this->data['Order']['job_time_beg_hour'].':'.($this->data['Order']['job_time_beg_min'] ? $this->data['Order']['job_time_beg_min'] : '00');
		if($this->data['Order']['job_time_end_hour'] != '')
			$this->data['Order']['job_time_end'] = $this->data['Order']['job_time_end_hour'].':'.($this->data['Order']['job_time_end_min'] ? $this->data['Order']['job_time_end_min'] : '00');
		//set techs' time
		if($this->data['Order']['fact_job_beg_hour'] != '')
			$this->data['Order']['fact_job_beg'] = $this->data['Order']['fact_job_beg_hour'].':'.($this->data['Order']['fact_job_beg_min'] ? $this->data['Order']['fact_job_beg_min'] : '00');
		if($this->data['Order']['fact_job_end_hour'] != '')
			$this->data['Order']['fact_job_end'] = $this->data['Order']['fact_job_end_hour'].':'.($this->data['Order']['fact_job_end_min'] ? $this->data['Order']['fact_job_end_min'] : '00');

		//set approval to no if it is an approval
		if($this->data['Note']['urgency_id'] == 4) {
			$this->data['Order']['needs_approval'] = 0;
		}

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		if ($this->data['Order']['order_status_id']!=6)
		{
			if ($this->data['Order']['id'])
			{
				$query = "SELECT order_number num FROM ace_rp_orders where id='".$this->data['Order']['id']."'";
				$result = $db->_execute($query);
				$row = mysql_fetch_array($result);
				$this->data['Order']['order_number'] = $row['num'];
			}
			if (!$this->data['Order']['order_number'])
			{
				$query = "SELECT max(order_number) num FROM ace_rp_orders";
				$result = $db->_execute($query);
				$row = mysql_fetch_array($result);
				$this->data['Order']['order_number'] = 1+$row['num'];
			}
		}

		// Trying to save the order
		$old_status = 0;
		$this->Order->id = $this->data['Order']['id'];
		if ($this->Order->id)
		{
			$query = "SELECT order_status_id FROM ace_rp_orders where id=".$this->Order->id;
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result);
			$old_status = $row['order_status_id'];
		}
		if ($this->Order->save($this->data))
		{
			//Get Order ID
			if ($this->Order->id)
			  $order_id = $this->Order->id;
			else
			  $order_id = $this->Order->getLastInsertId();
		// 1. Items
		// Clear Previous Order Items of class 'booking'
		//$this->Order->BookingItem->execute("DELETE FROM " . $this->Order->BookingItem->tablePrefix . "order_items WHERE order_id = '".$order_id."' AND class=0;");
		$this->Order->BookingItem->execute("DELETE FROM " . $this->Order->BookingItem->tablePrefix . "order_items WHERE order_id = '".$order_id."'");
		$total = 0;

		/* Code for check Mail Bouncing */
		
		if(isset($_REQUEST['havetoprint']) && $_REQUEST['havetoprint']==1){

			$cEmail = $this->data['Customer']['email'];
			$response = exec("curl -G --user 'api:pubkey-02f5eddb05645b5c1135ee2f8c2e206f' -G \
			    https://api.mailgun.net/v3/address/validate \
			    --data-urlencode address='".$cEmail."'");
			$arr = json_decode($response);
			$isValid = $arr->is_valid;
			if($isValid==1){
				$this->data['Order']['emal_bounce_status'] = 0;
				$emal_bounce_status = 0;
			}else{
				$this->data['Order']['emal_bounce_status'] = 1;	
				$emal_bounce_status = 1;
			}
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$queryUpdate = "UPDATE ace_rp_orders set emal_bounce_status='".$emal_bounce_status."' WHERE id = '".$order_id."'";
			$result = $db->_execute($queryUpdate);

		}


		// Save booked items
		  for ($i = 0; $i < count($this->data['Order']['BookingItem']); $i++)
		{
			// Set ID of parent order
			$this->data['Order']['BookingItem'][$i]['order_id'] = $order_id;
			//$this->data['Order']['BookingItem'][$i]['class'] = 0;
			if (0+$this->data['Order']['BookingItem'][$i]['quantity']!=0)
			{
				$this->Order->BookingItem->create();
				$this->Order->BookingItem->save($this->data['Order']['BookingItem'][$i]);
				$total += $this->data['Order']['BookingItem'][$i]['quantity']*
						  $this->data['Order']['BookingItem'][$i]['price'] -
						  $this->data['Order']['BookingItem'][$i]['discount'] +
						  $this->data['Order']['BookingItem'][$i]['addition'];
			}
		}

		// 2. Questions
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			SELECT *
			FROM ace_rp_settings
			WHERE id IN(21)
		";

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result)) {
			$use_template_questions = $row['valuetxt'];
		}

		if($use_template_questions == 1) {
			$template = $this->data['Template'];

			foreach($template as $question_id => $row) {
				$response_text = isset($row['response_text'])&&$row['response_text']!=""?"'".$row['response_text']."'":"NULL";
				$response_id = isset($row['response_id'])&&$row['response_id']!=""?$row['response_id']:"NULL";
				$suggestion_id = isset($row['suggestion_id'])&&$row['suggestion_id']!=""?$row['suggestion_id']:"NULL";
				$decision_id = isset($row['decision_id'])&&$row['decision_id']!=""?$row['decision_id']:"NULL";
				$reminder = isset($row['reminder'])&&$row['reminder']!=""?$row['reminder']:"NULL";

				$query = "
					DELETE FROM ace_rp_orders_questions_working
					WHERE order_id = $order_id
					AND question_id = $question_id
				";

				$result = $db->_execute($query);

				$query = "
					INSERT INTO ace_rp_orders_questions_working(order_id, question_id, response_text, response_id, suggestion_id, decision_id,reminder)
					VALUES($order_id, $question_id, $response_text, $response_id, $suggestion_id, $decision_id,$reminder)
				";

				$result = $db->_execute($query);
				
				//if disposition == 4,6,8 then delete customer from customer_list table ,delete means status=0 (update that)
				//if disposition == 1,5,3,7 then save/update callback date and time in ace_rp_customer
				
				$disposition    = $_REQUEST['disposition'] ;
			    $customer_id    = $_REQUEST['customer_id'] ;//customer_id of customer_list table
			if($disposition == 2 || $disposition == 4 || $disposition == 6 || $disposition == 8 ){
				 $agent_id =$_SESSION['user']['id'];
					$query="update customer_list set 	customer_disposition='".$disposition."', agent_id='".$agent_id."', status=0  where customer_id='".$customer_id."'";
					$results = $db->_execute($query);
					
				}
				else{
					
					$query = "delete from customer_list where customer_id ='".$customer_id."'";
					$results = $db->_execute($query);
					
					 $query = " update ace_rp_customers set disposition_id='".$disposition."' where id='".$last_inserted_customer_id."'";
					
					$results = $db->_execute($query);
				
				}	
			
				
				
			
				
				
				
			}

			//save a final copy of the answers if done
			$this->_saveQuestionsAsFinal($order_id);
		} else {
			for ($i = 0; $i < count($this->data['Order']['OrdersQuestions']); $i++)
			{
				// Set ID of parent order
				$this->data['Order']['OrdersQuestions'][$i]['order_id'] = $order_id;
				$db->_execute("delete from ace_rp_orders_questions where order_id=$order_id and question_number='{$this->data['Order']['OrdersQuestions'][$i]['question_number']}'");
				$query="insert INTO ace_rp_orders_questions
					  (order_id,
						for_office,
						for_tech,
						for_print,
						question,
						suggestions,
						response,
						local_answer,
						question_number,
						question_id,
						answers)
					  VALUES
					  ('".$order_id."',
						'".$this->data['Order']['OrdersQuestions'][$i]['for_office']."',
						'".$this->data['Order']['OrdersQuestions'][$i]['for_tech']."',
						'".$this->data['Order']['OrdersQuestions'][$i]['for_print']."',
						'".$this->data['Order']['OrdersQuestions'][$i]['question']."',
						'".$this->data['Order']['OrdersQuestions'][$i]['suggestions']."',
						'".$this->data['Order']['OrdersQuestions'][$i]['response']."',
						'".str_replace("'","`",$this->data['Order']['OrdersQuestions'][$i]['local_answer'])."',
						'".$this->data['Order']['OrdersQuestions'][$i]['question_number']."',
						'".$this->data['Order']['OrdersQuestions'][$i]['question_id']."',
						'".$this->data['Order']['OrdersQuestions'][$i]['answers']."')";
				$db->_execute($query);
			}
		}
		//Now E-Mail the customer with what we just did


		if ($this->data['Order']['order_status_id'] != 5)
		{
		  	if($_POST['havetoprint'] == "1")
				{
					if(isset($_REQUEST['SendMailAgain']) && $_REQUEST['SendMailAgain']==1){
				  		$subject = $this->emailCustomerBooking($order_id);			  		
						$queryEmailDateUpdate = "UPDATE ace_rp_orders set email_send_date='".$emal_send_date."' WHERE id = '".$order_id."'";
						$db->_execute($queryEmailDateUpdate);
			
				  	}else if(isset($_REQUEST['SendMailAgain']) && $_REQUEST['SendMailAgain']==0){
				  		'';	
				  	}else{
				  		$queryEmailDateUpdate = "UPDATE ace_rp_orders set email_send_date='".$emal_send_date."' WHERE id = '".$order_id."'";
						$db->_execute($queryEmailDateUpdate);
				  		$subject = $this->emailCustomerBooking($order_id);
				  	}
				}
		}


		$this->emailCustomerBooking($order_id);
//die('Loadings...');

		// Trying to create a sale record for the call history
		// if there is no such record yet
		$sql = "SELECT count(*) cnt
			  FROM ace_rp_call_history h, ace_rp_orders o
			 WHERE h.customer_id='" .$this->data['Customer']['id'] ."'
				 AND o.id='" .$this->data['Order']['id'] ."'
					   AND h.call_date = o.booking_date";

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$result = $db->_execute($sql);
		$row = mysql_fetch_array($result);
		if (1*$row['cnt']==0)
			  $this->AddCallToHistory(
				  $this->data['Customer']['id'],
				  $this->data['Order']['booking_telemarketer_id'],
				  1,'','','','','web');
		}
		if ($SendToDisp)
		{
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$sql = "INSERT INTO ace_rp_messages
					(txt, state, from_user, from_date,
					 to_user, to_date, to_time, file_link)
			 VALUES ('A new job has been booked', 0, ".$this->Common->getLoggedUserID().", current_date(),
					 57499, current_date(), '00:00', ".$order_id.")";
			$db->_execute($sql);
			$sql = "INSERT INTO ace_rp_messages
					(txt, state, from_user, from_date,
					 to_user, to_date, to_time, file_link)
			 VALUES ('A new job has been booked', 0, ".$this->Common->getLoggedUserID().", current_date(),
					 223190, current_date(), '00:00', ".$order_id.")";
			$db->_execute($sql);
		}

		//Save summary
		if(isset($order_id)) {
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$query = "
				DELETE FROM ace_rp_order_summaries
				WHERE order_id = $order_id;
			";
			$db->_execute($query);

			$query = "
				INSERT INTO ace_rp_order_summaries(order_id, commission_from_id, commission_type_id, commission_item_id, subtotal)
				SELECT $order_id, 0 from_id, 1 type_id, 1 item_id, IFNULL(SUM((oi.price*oi.quantity)-oi.discount+oi.addition),0) amount
				FROM ace_rp_order_items oi
				WHERE oi.order_id = $order_id
				AND class = 0
				UNION
				SELECT $order_id, 1 from_id, 1 type_id, 1 item_id, IFNULL(SUM(((oi.price-oi.price_purchase)*oi.quantity)-oi.discount+oi.addition),0) amount
				FROM ace_rp_order_items oi
				WHERE oi.order_id = $order_id
				AND class = 1

				UNION
				SELECT $order_id, 0, 2, o.order_type_id, IFNULL(SUM((oi.price*oi.quantity)-oi.discount+oi.addition),0) amount
				FROM ace_rp_orders o
				LEFT JOIN ace_rp_order_items oi
				ON o.id = oi.order_id
				WHERE o.id = $order_id
				AND oi.class = 0
				UNION
				SELECT $order_id, 1, 2, o.order_type_id, IFNULL(SUM(((oi.price-oi.price_purchase)*oi.quantity)-oi.discount+oi.addition),0) amount
				FROM ace_rp_orders o
				LEFT JOIN ace_rp_order_items oi
				ON o.id = oi.order_id
				WHERE o.id = $order_id
				AND oi.class = 1

				UNION
				SELECT $order_id, 0, 3, oi.item_category_id, IFNULL(SUM((oi.price*oi.quantity)-oi.discount+oi.addition),0) amount
				FROM ace_rp_order_items oi
				WHERE oi.order_id = $order_id
				AND oi.class = 0
				GROUP BY oi.item_category_id
				UNION
				SELECT $order_id, 1, 3, oi.item_category_id, IFNULL(SUM(((oi.price-oi.price_purchase)*oi.quantity)-oi.discount+oi.addition),0) amount
				FROM ace_rp_order_items oi
				WHERE oi.order_id = $order_id
				AND oi.class = 1
				GROUP BY oi.item_category_id

				UNION
				SELECT $order_id, 0, 4, oi.item_id, IFNULL(SUM((oi.price*oi.quantity)-oi.discount+oi.addition),0) amount
				FROM ace_rp_order_items oi
				WHERE oi.order_id = $order_id
				AND oi.class = 0
				GROUP BY oi.item_id
				UNION
				SELECT $order_id, 1, 4, oi.item_id, IFNULL(SUM(((oi.price-oi.price_purchase)*oi.quantity)-oi.discount+oi.addition),0) amount
				FROM ace_rp_order_items oi
				WHERE oi.order_id = $order_id
				AND oi.class = 1
				GROUP BY oi.item_id

				UNION
				SELECT $order_id, 0 from_id, 1 type_id, 2 item_id,
					IFNULL(HOUR(o.fact_job_end) + MINUTE(o.fact_job_end)/60 -
					HOUR(o.fact_job_beg) - MINUTE(o.fact_job_beg)/60,0) amount
				FROM ace_rp_orders o
				WHERE o.id = $order_id
				;
			";
			$db->_execute($query);
			}
		//END save summary

		//Save Notes
			if(isset($this->data['Note']['message']) && trim($this->data['Note']['message']) != '') {
				$message = trim($this->data['Note']['message']);
				$user_id = $_SESSION['user']['id'];
				$urgency_id = $this->data['Note']['urgency_id'];
				if($_SESSION['user']['role_id'] == 1 || $_SESSION['user']['role_id'] == 2 || $_SESSION['user']['role_id'] == 12) {
					$note_type_id = 3;
				} else if($_SESSION['user']['role_id'] == 6 || $_SESSION['user']['role_id'] == 4) {
					$note_type_id = 2;
				}	else if($_SESSION['user']['role_id'] == 3) {
					$note_type_id = 4;
				}

				$query = "
					INSERT INTO ace_rp_notes(message, note_type_id, order_id, user_id, urgency_id, note_date)
					VALUES ('$message', $note_type_id, $order_id, $user_id, $urgency_id, NOW())
				";

				$db->_execute($query);
			}
			
						
			
		//END Save Notes
			//sleep(3);
		        /*$resp = $this->verifyEmailUsingMailgun($subject);
		        if(($resp=='failed') || ($resp=='tempfailed')){
				$emal_bounce_status = 1;
			}else{
				$emal_bounce_status = 0;
			}
			
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

			echo $queryUpdate = "UPDATE ace_rp_orders set emal_bounce_status='".$emal_bounce_status."' WHERE id = '".$order_id."'";
			$result = $db->_execute($queryUpdate);
			*/
			//Forward user where they need to be - if this is a single action per view
			if (($old_status == 1)&&($this->data['Order']['order_status_id'] == 2))
				$this->reschedule();
			elseif ($this->data['rurl'][0])
				$this->redirect($this->data['rurl'][0]);
			else
				$this->redirect('/orders/scheduleView');
    }

/************************************/
 function _SaveCustomer()
    {  error_reporting(E_ALL);
		$this->data['Customer']['phone'] = $this->data['Customer']['phone']!= '' ? $this->Common->preparePhone($this->data['Customer']['phone']):'';
		$this->data['Customer']['cell_phone'] = $this->data['Customer']['cell_phone'] != '' ? $this->Common->preparePhone($this->data['Customer']['cell_phone']) : '';
		$this->data['Customer']['postal_code'] = $this->data['Customer']['postal_code'] != '' ? $this->Common->prepareZip($this->data['Customer']['postal_code']) : '';

		if (strlen($this->data['Customer']['callback_date']) != 0)
			$this->data['Customer']['callback_date'] = date("Y-m-d", strtotime($this->data['Customer']['callback_date']));

		if($this->data['Customer']['callback_time_hour'] != '')
			$this->data['Customer']['callback_time'] = $this->data['Customer']['callback_time_hour'].':'.($this->data['Customer']['callback_time_min'] ? $this->data['Customer']['callback_time_min'] : '00');

		if (strlen($this->data['Customer']['lastcall_date']) != 0)
			$this->data['Customer']['lastcall_date'] = date("Y-m-d", strtotime($this->data['Customer']['lastcall_date']));

		//Added by Maxim Kudryavtsev - for booking member cards
		if (strlen($this->data['Customer']['card_exp']) != 0)
			$this->data['Customer']['card_exp']=date('Y-m-d',strtotime($this->data['Customer']['card_exp']));
		if (strlen($this->data['Customer']['next_service']) != 0)
			$this->data['Customer']['next_service']=date('Y-m-d',strtotime($this->data['Customer']['next_service']));
		// /Added by Maxim Kudryavtsev - for booking member cards

//var_dump($this->data['Customer']);
//die;
		//var_dump($this->data['Customer']);
		$this->Order->Customer->save($this->data['Customer']);
		$last_id = $this->Order->Customer->getLastInsertId();
		if( $this->data['Customer']['id'] == '')
		{
			$this->data['Customer']['id'] = $this->Order->Customer->getLastInsertId();
			// role
			/*$db =& ConnectionManager::getDataSource('default');
			$query = "replace into ace_rp_users_roles SET user_id = '".$this->data['Order']['customer_id']."',role_id = '8' ";
  			$db->_execute($query);*/
  		}

        //Save a common note - if we have one
		$note = $this->data['txt_customer_note'][0];
        if ($note!='')
        {
            $loggedUserId = $this->Common->getLoggedUserID();
            $this->User->id = $loggedUserId;
            $User_details = $this->User->read();
            $created_by = $User_details['User']['first_name'].' '.$User_details['User']['last_name'];
            $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
            $result = $db->_execute("INSERT INTO ace_rp_users_notes (user_id,note,note_date,created_by)
                                    VALUES(".$this->data['Customer']['id'].",
                                    '".str_replace("'","`",$note)."',now(),'".$created_by."')");
        }
     $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
      $query =" update ace_rp_customers set referred_by_existing_userid='".$this->data['Customer']['referred_by_existing_userid']."' 
                   where id= '".$last_id ."'";
       
   $result = $db->_execute($query);

		return $last_id;
    }
	/****************************************************************/
	function reschedule(){
		$order_id = $this->params['url']['order_id'];
		if ($order_id)
		{
			$this->Order->id = $order_id;
			$o = $this->Order->read();
			$o['Order']['order_status_id'] = 2; //reschedule
			$this->Order->save($o);

			// new order: a copy of the old one as all values are kept
			$o['Order']['id'] = 0;
			$o['Order']['order_status_id'] = 1; //booked
			$this->Order->save($o);
			$orig_order_id = $order_id;
			$order_id = $this->Order->getLastInsertId();

			//Now copy the order items
			$oi = $this->OrderItem->findAll(array('OrderItem.order_id' => $orig_order_id));
			for ($i=0; $i < count($oi); $i++)
			{
				$oi[$i]['OrderItem']['id'] = null;
				$oi[$i]['OrderItem']['order_id'] = $order_id;
				$this->OrderItem->save($oi[$i]['OrderItem']);
			}
		}

		//$this->redirect('/orders/editBooking?order_id=' . $order_id . '&reschedule=1');
		$this->redirect('/dialer/index?order_id=' . $order_id . '&reschedule=1');
	}
	
	/***********************************************************************************/
	
function _saveQuestionsAsFinal($order_id) {
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$date = new DateTime(date());
		$date_saved = $date->format('Y-m-d H:i:s');

		$query = "
			INSERT INTO ace_rp_orders_questions_final(question_number, order_id, question_text, response_text, suggestion_text, decision_text, date_saved)
			SELECT q.rank, qw.order_id,  q.value,
				IFNULL(r.value, IFNULL(qw.response_text, '')), IFNULL(s.value, ''), IFNULL(d.value, ''),
				'$date_saved'
			FROM ace_rp_orders_questions_working qw
			LEFT JOIN ace_rp_questions q
			ON qw.question_id = q.id
			LEFT JOIN ace_rp_responses r
			ON qw.response_id = r.id
			LEFT JOIN ace_rp_suggestions s
			ON qw.suggestion_id = s.id
			LEFT JOIN ace_rp_decisions d
			ON qw.decision_id = d.id
			LEFT JOIN ace_rp_orders o
			ON qw.order_id = o.id
			WHERE qw.order_id = $order_id
			AND o.order_status_id = 5
			ORDER BY q.rank
		";

		$result = $db->_execute($query);
	}
	
	/************************/
	function emailCustomerBooking($id)
	{

		//Get E-mail Settings
		$settings = $this->Setting->find(array('title'=>'email_fromaddress'));
		$from_address = $settings['Setting']['valuetxt'];

		$settings = $this->Setting->find(array('title'=>'email_fromname'));
		$from_name = $settings['Setting']['valuetxt'];

		$settings = $this->Setting->find(array('title'=>'email_template_bookingnotification'));
		$template = $settings['Setting']['valuetxt'];

		$settings = $this->Setting->find(array('title'=>'email_template_jobnotification_subject'));
		$template_subject = $settings['Setting']['valuetxt'];


		//define the headers we want passed. Note that they are separated with \r\n
		//$headers = "From: webmaster@example.com\r\nReply-To: webmaster@example.com";
		$headers = "From: info@acecare.ca\n";
		//add boundary string and mime type specification
		$headers .= "Content-Type: text/html; charset=iso-8859-1\n" ;
		$headers .= "Disposition-Notification-To: info@acecare.ca \r\n";
		$headers .= "X-Confirm-Reading-To: info@acecare.ca \r\n";

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "
			SELECT CONCAT(u2.first_name, \" \", u2.last_name) source,
				u.first_name, u.last_name, u.email, u.phone, u.cell_phone, CONCAT(u.address_unit,', ',u.address_street_number,', ',u.address_street) as address, u.city, u.email,
				o.order_number,
				DATE_FORMAT(o.job_date, '%M %D, %Y') job_date,
				DATE_FORMAT(o.job_time_beg, '%r') job_time_beg,
				DATE_FORMAT(o.job_time_end, '%r') job_time_end
			FROM ace_rp_orders o
			LEFT JOIN ace_rp_customers u
			ON o.customer_id = u.id
			LEFT JOIN ace_rp_users u2
			ON o.booking_source_id = u2.id
			WHERE o.id = '$id'
		";
		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result)) {
			$source = $row['source'];
			$refnumber = $row['order_number'];
			$jobdate = $row['job_date'];
			$jobtimeslot = $row['job_time_beg'] . " to " . $row['job_time_end'];
			$phone = $row['phone'];
			$cellphone = $row['cell_phone'];
			$address = $row['address'];
			$city = $row['city'];
			$firstname = $row['first_name'];
			$lastname = $row['last_name'];
			$email = $row['email'];
		}

		$query = "
			SELECT oi.order_id, oi.name, oi.quantity, oi.price, oi.discount, oi.price - oi.discount total
			FROM ace_rp_order_items oi
			WHERE oi.order_id = '$id'
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result)) {
			$summary .= "<tr>";
			$summary .= "<td>" . $row['name'] . "</td>";
			$summary .= "<td>" . $row['quantity'] . "</td>";
			$summary .= "<td>" . $row['price'] . "</td>";
			$summary .= "<td>" . $row['discount'] . "</td>";
			$summary .= "<td>" . $row['total'] . "</td>";
			$summary .= "</tr>";
		}

		$query = "
			SELECT SUM(price - discount) grandtotal
			FROM ace_rp_order_items
			WHERE order_id = '$id'
		";
		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result)) {
			$summary .= "<tr>";
			$summary .= "<td colspan='4' align='right'>Total Amount</td>";
			$summary .= "<td>" . $row['grandtotal'] . "</td>";
			$summary .= "</tr>";
		}

		$msg = $template;
		$msg = str_replace('{source}', $source, $msg);
		$msg = str_replace('{first_name}', $firstname, $msg);
		$msg = str_replace('{last_name}', $lastname, $msg);
		$msg = str_replace('{address}', $address, $msg);
		$msg = str_replace('{city}', $city, $msg);
		$msg = str_replace('{phone}', $phone, $msg);
		$msg = str_replace('{cell_phone}', $cellphone, $msg);
		$msg = str_replace('{ref_number}', $refnumber, $msg);
		$msg = str_replace('{job_date}', $jobdate, $msg);
		$msg = str_replace('{job_timeslot}', $jobtimeslot, $msg);
		$msg = str_replace('{booking_summary}', $summary, $msg);
		$email =$this->data['Customer']['email'];

		$res = $this->sendEmailUsingMailgun($email,$template_subject,$msg,$id);
		return $template_subject;
		//$res = mail($email, $template_subject, $msg, $headers);
	}
	function sendEmailUsingMailgun($to,$subject,$body,$order_id){
		

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://acecare.ca/acesystem2018/mailcheck.php");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"TO=".$to."&SUBJECT=".$subject."&BODY=".$body);
		// receive server response ...
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$msgid = curl_exec ($ch);//exit;
		curl_close ($ch);

		$this->manageMailgunEmailLogs($msgid, $subject, $order_id);

		//var_export($response);
		//$this->verifyEmailUsingMailgun($to,$subject,$order_id,$msgid);
	}

	function manageMailgunEmailLogs($msgid, $subject, $order_id){
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "SELECT COUNT(*) cnt
				FROM ace_mailgun_elog				
				WHERE order_id = '".$order_id."'";
		$resultR = $db->_execute($query);
		$rows = mysql_fetch_array($resultR);
		$tCount = $rows['cnt'];
		if($tCount>0){
			//$queryUpdate = "UPDATE ace_mailgun_elog set msgid='".$msgid."',msgsub='".$subject."' where order_id = '".$order_id."'";
		}else{
			//$queryUpdate = "INSERT INTO ace_mailgun_elog(order_id,msgid,msgsub) VALUES($order_id,'".$msgid."','".$subject."')";			
		}
		
		return true;//$result = $db->_execute($queryUpdate);
	}
	/**************************/
	// Method adds a new record to the client's calls history table
	function AddCallToHistory($customer_id, $call_user, $callresult, $call_note, $callback_date, $callback_time, $call_id='', $dialer_id='', $callback_user='', $record_id='')
	{
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		// If caller is not mentioned - set the current user as a caller
		if (!$call_user) $call_user = $this->Common->getLoggedUserID();
		if ($callback_user == '') $callback_user = $call_user;

		// Set the callback parameters: date, time and user
		$scheduled_date = "''";
		$scheduled_time = "''";
		$callback_reason = $call_note;
		// call result : not 'Not In Service', not 'Answering Machine'
		if (($callresult != 7)&&($callresult != 6))
		{
			if ($callresult == 1) // call result : SALE
			{
				// Move the call forward, 6 month from now
				$scheduled_date = 'now() + INTERVAL 6 MONTH';
				$scheduled_time = 'current_time()';
				$callback_reason = 'Sale. Callback in 6 months';
		     	$callback_user = 57145; //ACE
			}
			elseif ($callresult == 2) // call result : CALLBACK
			{
				$scheduled_date = "str_to_date('" .$callback_date ."', '%d %b %Y')";
				$scheduled_time = "'".$callback_time."'";
				$callback_reason = $call_note;
			}
			elseif ($callresult == 4) // call result : NOT INTERESTED (3 month)
			{
				// Move the call forward, 3 month from now
				$scheduled_date = 'now() + INTERVAL 3 MONTH';
				$scheduled_time = 'current_time()';
				$callback_reason = 'Not interested. Call back in 3 months';
		    	$callback_user = 57145; //ACE
			}
			elseif ($callresult == 8) // call result : NOT INTERESTED (6 month)
			{
				// Move the call forward, 3 month from now
				$scheduled_date = 'now() + INTERVAL 6 MONTH';
				$scheduled_time = 'current_time()';
				$callback_reason = 'Not interested. Call back in 6 months';
		    	$callback_user = 57145; //ACE
			}
			elseif ($callresult == 9) // call result : NOT INTERESTED (9 month)
			{
				// Move the call forward, 3 month from now
				$scheduled_date = 'now() + INTERVAL 9 MONTH';
				$scheduled_time = 'current_time()';
				$callback_reason = 'Not interested. Call back in 9 months';
		    	$callback_user = 57145; //ACE
			}
			elseif ($callresult == 3) // call result : DO NOT CALL
			{
				// Set the date to now
				//$callback_date
				$scheduled_date = "now()";
				$scheduled_time = 'current_time()';
				$callback_reason = 'Do not call';
		    	$callback_user = 57145; //ACE
			}
		}

		// We do not need to multiply calls that are already in the history.
    // So, we remove previous versions of them first
		if ($call_id)
		{
			$query = "delete from ace_rp_call_history
				  where customer_id=".$customer_id."
				  and call_id is not null and call_id=".$call_id;
			$db->_execute($query);
		}

    // If we intend to change the record that is already in history,
    // we need to delete this record first
		if ($record_id)
		{
			$query = "delete from ace_rp_call_history where id=".$record_id;
			$db->_execute($query);
		}

    $query = "select * from ace_rp_customers where id=" .$customer_id;
    $result = $db->_execute($query);
    if ($row = mysql_fetch_array($result, MYSQL_ASSOC))
    {
      $phone=$row['phone'];
      $cell_phone=$row['cell_phone'];
    }

		// Adds the history's record
		$query = "INSERT INTO ace_rp_call_history
			  (customer_id, call_id, dialer_id,
			   call_date, call_time, call_user_id,
			   call_result_id, call_note,
			   callback_date, callback_time, callback_user_id,
         phone, cell_phone)
		VALUES (".$customer_id.", '" .$call_id ."', '" .$dialer_id ."',
				current_date(), current_time(), '".$call_user."',
				'" .$callresult ."', '" .str_replace("\"","`",str_replace("'","`",$call_note)) ."',
				" .$scheduled_date .", " .$scheduled_time .", '".$callback_user."',
        '".$phone."', '".$cell_phone."')";

    	$db->_execute($query);

    	//remembering the last call result
		$query = "update ace_rp_customers
					set telemarketer_id = " .$call_user .",
						callback_date = " .$scheduled_date .",
						callback_time = " .$scheduled_time .",
						lastcall_date = current_date(),
						callresult = '" .$callresult ."',
						callback_note = '" .str_replace("\"","`",str_replace("'","`",$call_note)) ."'
				where id=".$customer_id;

    	$db->_execute($query);
	}

	
}
