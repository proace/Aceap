<? ob_start();
//error_reporting(E_ALL );
//error_reporting(2047);

class OrdersController extends AppController
{
	//To avoid possible PHP4 problemfss
	var $name = "OrdersController";
	var $G_URL  = "http://hvacproz.ca";

	var $uses = array('OrderEstimate','Order', 'CallRecord', 'User', 'Customer', 'OrderItem',
                    'Timeslot', 'OrderStatus', 'OrderType', 'Item',
                    'Zone','PaymentMethod','ItemCategory','InventoryLocation',
					'OrderSubstatus','Coupon','Setting','CallResult','Invoice', 'Question', 'Payment', 'Invoice');

	var $helpers = array('Common');
	var $components = array('HtmlAssist', 'Common', 'Lists');
	var $itemsToShow = 20;
	var $pagesToDisplay = 10;

	var $member_card1_item_id=1106; //Added by Maxim Kudryavtsev - for booking member cards
	var $member_card2_item_id=1107; //Added by Maxim Kudryavtsev - for booking member cards

	var $beforeFilter = array('checkAccess');

	function checkMailSend(){
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		if (isset($_REQUEST['ordid']))
		{			
			$query = "SELECT mail_sent,booking_date FROM ace_rp_orders where id='".(int) $_REQUEST['ordid']."'";
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result);
			echo $row['mail_sent'].'@@'.$row['booking_date'];exit;	
		}else{
			echo 2;exit;
		}
		//echo '<pre>';print_r($_REQUEST['ordid']);exit;
	}

	function checkAccess()
	{
	  //if( $this->action == 'index' ) {
	  //	$this->Common->checkRoles(array('6','3','4','1','9','13'));
	  //}
	  if( $this->action == 'editBooking' || $this->action == 'reschedule' || $this->action == 'cancel' ) {
		$this->Common->checkRoles(array('6','3','4','8','1','9','13','15'));
	  }
	  if( $this->action == 'jobCheckout' ) {
		$this->Common->checkRoles(array('6','3','1','13'));
	  }
	  if( $this->action == 'dayendCheckout' ) {
		$this->Common->checkRoles(array('6','3','1','13'));
	  }
	  if( $this->action == 'scheduleView' ) {
		$this->Common->checkRoles(array('6','3','1','9','13','15'));
	  }
	  if( $this->action == 'ceoReport' ) {
		$this->Common->checkRoles(array('6'));
	  }
	  if( $this->action == 'followup' ) {
		$this->Common->checkRoles(array('1'));
	  }
		if($this->action == 'invoiceTablet'
			|| $this->action == 'invoiceTabletOverview'
			|| $this->action == 'invoiceQuestions'
			|| $this->action == 'invoiceTabletItems'
			|| $this->action == 'invoiceTabletFeedback'
			|| $this->action == 'invoiceTabletNewBooking') {
		$this->Common->checkRoles(array('1','12','6'));
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

    // Method saves current customer's data
    // NOTE: for now this method is invisible from the page. May be it should stay invisible forever
    // Created: 06/02/2010, Anthony Chernikov
    function _SaveCustomer()
    {  
    	if(!empty($this->data['Customer']['campaign_id'])) 
    	{
    		$this->data['Customer']['campaign_id'] = $this->data['Customer']['campaign_id'];
    	}
    	// #LOKI- Set the filter 
    	$this->data['Customer']['show_default'] = $this->data['Customer']['selected_button'];
		$this->data['Customer']['phone'] = $this->data['Customer']['phone']!= '' ? $this->Common->preparePhone($this->data['Customer']['phone']):'';
		$this->data['Customer']['cell_phone'] = $this->data['Customer']['cell_phone'] != '' ? $this->Common->preparePhone($this->data['Customer']['cell_phone']) : '';
		$this->data['Customer']['postal_code'] = $this->data['Customer']['postal_code'] != '' ? $this->Common->prepareZip($this->data['Customer']['postal_code']) : '';

		if (isset($this->data['Customer']['callback_date']))
			$this->data['Customer']['callback_date'] = date("Y-m-d", strtotime($this->data['Customer']['callback_date']));

		if(isset($this->data['Customer']['callback_time_hour']))
			$this->data['Customer']['callback_time'] = $this->data['Customer']['callback_time_hour'].':'.($this->data['Customer']['callback_time_min'] ? $this->data['Customer']['callback_time_min'] : '00');

		if (isset($this->data['Customer']['lastcall_date']))
			$this->data['Customer']['lastcall_date'] = date("Y-m-d", strtotime($this->data['Customer']['lastcall_date']));

		//Added by Maxim Kudryavtsev - for booking member cards
		if (isset($this->data['Customer']['card_exp']))
			$this->data['Customer']['card_exp']=date('Y-m-d',strtotime($this->data['Customer']['card_exp']));
		if (isset($this->data['Customer']['next_service']))
			$this->data['Customer']['next_service']=date('Y-m-d',strtotime($this->data['Customer']['next_service']));
		// /Added by Maxim Kudryavtsev - for booking member cards
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

    // Method saves order's data that was recieved from the order's page.
    // Created: 06/02/2010, Anthony Chernikov
    function saveOrder($saveCustomer=1, $isDialer=0, $file=null, $invoiceImages=null, $photoImage1=null, $photoImage2=null, $fromTech=null, $techOrderId=null, $send_cancalled_email = null, $showDefault=null, $jobDate = null)
    {
    	if($_POST['preViewEstimate'] == 1 && $_SESSION['user']['role_id']==6){
			$this->preViewEstimate($_POST);
		}else{
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
		$paidById = $this->data['Order']['payment_method_type'];

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
					//$this->data['Customer']['card_exp']=(date('Y')+ ($item_id==$this->member_card1_item_id? 1:2) ).'-'.date('m-d');
					$this->data['Customer']['card_exp']=(date('Y')+ (1)).'-'.date('m-d');
					$this->data['Customer']['next_service']=$this->data['Order']['BookingItem'][$i]['next_service'];
				}
			}
		}
		// /Added by Maxim Kudryavtsev - for booking member cards

		// Save the customer
		if ($saveCustomer==1)
			if (!empty($this->data['Customer'])) $this->_SaveCustomer();

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
		if(isset($this->data['Order']['job_time_beg_hour'])){
			$this->data['Order']['job_time_beg'] = $this->data['Order']['job_time_beg_hour'].':'.($this->data['Order']['job_time_beg_min'] ? $this->data['Order']['job_time_beg_min'] : '00');
		}
		if(isset($this->data['Order']['job_time_end_hour'])){
			$this->data['Order']['job_time_end'] = $this->data['Order']['job_time_end_hour'].':'.($this->data['Order']['job_time_end_min'] ? $this->data['Order']['job_time_end_min'] : '00');
		}
		//set techs' time
		if(isset($this->data['Order']['fact_job_beg_hour'])){
			$this->data['Order']['fact_job_beg'] = $this->data['Order']['fact_job_beg_hour'].':'.($this->data['Order']['fact_job_beg_min'] ? $this->data['Order']['fact_job_beg_min'] : '00');
		}
		if(isset($this->data['Order']['fact_job_end_hour']
			)){
			$this->data['Order']['fact_job_end'] = $this->data['Order']['fact_job_end_hour'].':'.($this->data['Order']['fact_job_end_min'] ? $this->data['Order']['fact_job_end_min'] : '00');
}
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
			{
				$order_id = $this->Order->id;
				if(empty($this->data['Order']['id']))
				{
				  $itemTotal = $this->data['Order']['current_item_total'];
				  $creator = $this->Common->getLoggedUserID();
				  $date = date("Y-m-d");
				  $savePayment = "INSERT INTO ace_rp_payments (idorder, creator, payment_method, payment_date, paid_amount, payment_type) VALUES (".$order_id.", ".$creator.", ".$paidById.", '$date', '$itemTotal', 1)";
				  $db->_execute($savePayment);
				}
			}	
			else {

			  $order_id = $this->Order->getLastInsertId();
			}
		// 1. Items
		// Clear Previous Order Items of class 'booking'
		//$this->Order->BookingItem->execute("DELETE FROM " . $this->Order->BookingItem->tablePrefix . "order_items WHERE order_id = '".$order_id."' AND class=0;");
		// Save payment image
		if($file !== null)
		{
			$loggedUserId = $this->Common->getLoggedUserID();
            $this->User->id = $loggedUserId;
             
			$imageResult = $this->Common->commonSavePaymentImage($file, $order_id , $config = $this->User->useDbConfig);
		}	
		if(!empty($photoImage2['name'] ))
		{
			$imageResult = $this->Common->uploadPhoto($photoImage2, $order_id , $config = $this->User->useDbConfig, 2);
		}
		if(!empty($photoImage1['name']))
		{       
			$imageResult = $this->Common->uploadPhoto($photoImage1, $order_id , $config = $this->User->useDbConfig, 1);
		}
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
				if(!empty($invoiceImages['name'][$i])) 
				{
					$fileName = time()."_".$invoiceImages['name'][$i];
					$fileTmpName = $invoiceImages['tmp_name'][$i];
				
					if($invoiceImages['error'][$i] == 0)
					{
						$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/purchase-invoice-images/".$fileName);
						$this->data['Order']['BookingItem'][$i]['invoice_image'] = 	$fileName;
					}
					
				}	
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
		
	
		if($_POST['sendMailOnEstimate']){
			$mail_sent = false;
			if ($this->data['Order']['order_status_id'] != 5)
			{
			  	if($_POST['havetoprint'] == "1")
					{

						if(isset($_REQUEST['SendMailAgain']) && $_REQUEST['SendMailAgain']==1){
					  		if($paidById != 11) {
						  		$subject = $this->emailCustomerBooking($order_id);			  		$mail_sent = true;
								$queryEmailDateUpdate = "UPDATE ace_rp_orders set email_send_date='".$emal_send_date."' WHERE id = '".$order_id."'";
								$db->_execute($queryEmailDateUpdate);
							}
					  	}else if(isset($_REQUEST['SendMailAgain']) && $_REQUEST['SendMailAgain']==0){
					  		'';	
					  	}else{
						  		$queryEmailDateUpdate = "UPDATE ace_rp_orders set email_send_date='".$emal_send_date."' WHERE id = '".$order_id."'";
								$db->_execute($queryEmailDateUpdate);
						  		$subject = $this->emailCustomerBooking($order_id);
						  		$mail_sent = true;
					  	}
					}
			}
		if($paidById != 11) {
			if(isset($_REQUEST['SendMailAgain']) && $_REQUEST['SendMailAgain']==1 && $mail_sent== false ){
				$this->emailCustomerBooking($order_id, $send_cancalled_email);
			}
		}
	}
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
				GROUP BY o.order_type_id
				UNION
				SELECT $order_id, 1, 2, o.order_type_id, IFNULL(SUM(((oi.price-oi.price_purchase)*oi.quantity)-oi.discount+oi.addition),0) amount
				FROM ace_rp_orders o
				LEFT JOIN ace_rp_order_items oi
				ON o.id = oi.order_id
				WHERE o.id = $order_id
				AND oi.class = 1
				GROUP BY o.order_type_id
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
		// LOKI= update payment method type
		
		if($paidById)
		{
			$query_order_up = "UPDATE `ace_rp_orders` as `arc` set `arc`.`payment_method_type` =".$paidById." WHERE arc.id=".$order_id.";";
			$up_order = $db->_execute($query_order_up);
		}
		$campId = $this->data['Order']['order_campaing_id'];
		$cusId = $this->data['Order']['customer_id'];
		if(!empty($campId))
		{
			$up_query = "UPDATE `ace_rp_customers` as `arc` set `arc`.`campaign_id` =".$campId."  WHERE id=".$cusId.";";
			$up_result = $db->_execute($up_query);

			$query_order_up = "UPDATE `ace_rp_orders` as `arc` set `arc`.`o_campaign_id` =".$campId." WHERE customer_id=".$cusId.";";
			$up_order = $db->_execute($query_order_up);

			$get_user = "SELECT id from ace_rp_all_campaigns where call_history_ids=".$cusId;
			$user_result = $db->_execute($get_user);
			$row = mysql_fetch_array($user_result);
			if(empty($row['id']))
			{

				$get_camp = "SELECT campaign_name from ace_rp_reference_campaigns where id=".$campId;
				$camp_result = $db->_execute($get_camp);
				$row = mysql_fetch_array($camp_result);
				$up_camp_data = "INSERT INTO ace_rp_all_campaigns(campaign_name, call_history_ids, transfer_call_jobs_flag, last_inserted_id, disposition_id, created_date) VALUES ('".$row['campaign_name']."',".$cusId.", 1,".$campId.", 1,CURDATE())";
				$up_camp_res = $db->_execute($up_camp_data);
			}
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
			if($_POST['havetoprint'] == 0 && ($_SESSION['user']['role_id']==6 || $_SESSION['user']['role_id']==1)){
				//REDIRECT FOR SEND ESTIMATE TEMPLATE
				$this->orderEstimate($order_id, $this->data['Order']['BookingItem'], $fromTech);
			}else{
				
				if($isDialer) {

					$custId = $this->data['Customer']['id'];
					$orderNumber = $this->data['Order']['order_number'];
					$query = "UPDATE ace_rp_all_campaigns set show_default =1 where call_history_ids = ".$custId;
       				$db->_execute($query);
					$this->redirect('orders/editBooking?hotlist=1&customer_id='.$custId.'&is_booking=1&orderNo='.$orderNumber);
				} else if($fromTech == 1)
				{
					$this->redirect('/orders/invoiceTabletNewBooking');
				} else if($fromTech == 2 )
				{
					
					$this->redirect('orders/invoiceTabletPayment?order_id='.$this->data['Order']['id']);
				} else if($fromTech == 3) {
					$techId = $_SESSION['user']['id'];
					$jobDate = date("dMY", strtotime($jobDate));
					$url = 'action=view&order=&sort=&currentPage=1&comm_oper=&ftechid='.$techId.'&selected_job=&selected_commission_type=&job_option=1&ffromdate='.urlencode($jobDate).'&cur_ref=';
					// $this->redirect('orders/invoiceTabletPayment?order_id='.$this->data['Order']['id']);
					$this->redirect('commissions/calculateCommissions?'.$url);
				}
				else {
				if($_POST['havetoprint'] == 3 && $_SESSION['user']['role_id']==6)
					$this->redirect('/orders/scheduleView');
				elseif (($old_status == 1)&&($this->data['Order']['order_status_id'] == 2))
					$this->reschedule();
				elseif ($this->data['rurl'][0])

					$this->redirect($this->data['rurl'][0]);
				else
					$this->redirect('/orders/scheduleView');
				}

			}
		}
    }

    function preViewEstimate($postData){
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		// Read the order's data from database

		

		if(!empty($postData['data']['Order']['order_type_id'])){
			$order_type = $postData['data']['Order']['order_type_id'];
		}else{
			$order_type = 0;
		}

		$templateQuery = "select * from ace_rp_estimation_template where job_type_id =".$order_type; 
		$result = $db->_execute($templateQuery);
		$template = mysql_fetch_array($result);

		//Load current questions 
		//$officeQuery =  $this->_showQuestions(0, 0,$orderData['Order']['order_type_id'],'');
		$condition = "for_office=1 and";
		$query = " SELECT * FROM ace_rp_questions WHERE ".$condition." order_type_id = ".$order_type." order by rank, value ";
		/*ace_rp_orders_questions_working*/
		$result = $db->_execute($query);
		$i=0;
		$temp = array();
		while ($row = mysql_fetch_assoc($result)){
			$temp[] = $row;
		}

		foreach ($temp as $key => $value) {

			if(isset($postData['data']['Template'])){
				$qan['response'] ="";
				$qan['response_text'] ="";
				if($value['type']=='text'){
					$qan['response'] =$postData['data']['Template'][$value['id']]['response_text'];
					$qan['response_text'] = $postData['data']['Template'][$value['id']]['response_text'];
				}
				if($value['type']=='dropdown'){
					if($postData['data']['Template'][$value['id']]['response_id']!=""){
						$query = "SELECT * FROM ace_rp_responses WHERE id=".$postData['data']['Template'][$value['id']]['response_id'];
						$result = $db->_execute($query);
						$response = mysql_fetch_assoc($result);
						$qan['response'] = $response['value'];
						$qan['response_text'] = $response['value'];	
					}
				}
				$temp[$key]['qan'] = $qan;	
			}
		}

		$typeQuery = "SELECT id, name FROM ace_rp_order_types where flagactive=1" ;
		$typeResult = $db->_execute($typeQuery);
		$orderType = array();
		
		
		
		

		//$temp as officeQuery
		$officeQueryHTML = '<table style="border-collapse: collapse; width: 35%;" border="1">
			<tbody>
			<tr style="height: 18px;">
				<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">#</strong></td>
				<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Questions</strong></td>
				<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Responses</strong></td>
			</tr>';
		
		foreach ($temp as $key => $value) {
				
			$officeQueryHTML .='<tr style="height: 18px;">
					<td style="width: 16.6667%; height: 18px;text-align: center;">&nbsp;'. $value['rank'].'</td>
					<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['value'].'</td>
					<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['qan']['response_text']. '</td>
				</tr>';
		}
		$officeQueryHTML .= '</table>';


		$option1 = '<table style="border-collapse: collapse; width: 35%;" border="1">
			<tbody>
			<tr style="height: 18px;">
				<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Item</strong></td>
				<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Qty</strong></td>
				<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Price</strong></td>
				<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Disc</strong></td>
			</tr>';


		$option2 = '<table style="border-collapse: collapse; width: 35%;" border="1">
			<tbody>
			<tr style="height: 18px;">
				<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Item</strong></td>
				<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Qty</strong></td>
				<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Price</strong></td>
				<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Disc</strong></td>
			</tr>';

			$subTotalPrice1 = 0;
			$subTotalPrice2 = 0;
			$classZeroExist = 0;
			$classOneExist = 0;

		foreach ($postData['data']['Order']['BookingItem'] as $key => $value) {
			if($value['class']==0){
				$classZeroExist = 1;
				$option1 .='<tr style="height: 18px;">
					<td style="width: 16.6667%; height: 18px;text-align: center;">&nbsp;'. $value['name'].'</td>
					<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['quantity'].'</td>
					<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['price']. '</td>
					<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['discount']. '</td>
				</tr>';
				$subTotalPrice1 = $subTotalPrice1+$value['price'];
			}


			if($value['class']==1){
				$classOneExist = 1;
				$option2 .='<tr style="height: 18px;">
					<td style="width: 16.6667%; height: 18px;text-align: center;">&nbsp;'. $value['name'].'</td>
					<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['quantity'].'</td>
					<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['price']. '</td>
					<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['discount']. '</td>
				</tr>';
				$subTotalPrice2 = $subTotalPrice2+$value['price'];
			}
		}

		
		$gst1 = $subTotalPrice1*0.05;
		$total1 = $subTotalPrice1+$gst1;

		$gst2 = $subTotalPrice2*0.05;
		$total2 = $subTotalPrice2+$gst2;		


		$subTotal1 = '<table style="border-collapse: collapse; width: 15.593%; height: 100px;" border="1">
		<tbody>
		<tr>
		<td style="width: 50%; text-align: right;">SubTotal:</td>
		<td style="width: 50%; text-align: right;">'.$subTotalPrice1.'</td>
		</tr>
		<tr>
		<td style="width: 50%; text-align: right;">GST:</td>
		<td style="width: 50%; text-align: right;">'.$gst1.'</td>
		</tr>
		<tr>
		<td style="width: 50%; text-align: right;">Total</td>
		<td style="width: 50%; text-align: right;">'.$total1.'</td>
		</tr>
		</tbody>
		</table>';

		$subTotal2 = '<table style="border-collapse: collapse; width: 15.593%; height: 100px;" border="1">
		<tbody>
		<tr>
		<td style="width: 50%; text-align: right;">SubTotal:</td>
		<td style="width: 50%; text-align: right;">'.$subTotalPrice2.'</td>
		</tr>
		<tr>
		<td style="width: 50%; text-align: right;">GST:</td>
		<td style="width: 50%; text-align: right;">'.$gst2.'</td>
		</tr>
		<tr>
		<td style="width: 50%; text-align: right;">Total</td>
		<td style="width: 50%; text-align: right;">'.$total2.'</td>
		</tr>
		</tbody>
		</table>';

		$option1 .= '</tbody></table>';
		$option2 .= '</tbody></table>';

		

		

		$optionHTML = '<select name="jobType">';
		while ($row = mysql_fetch_assoc($typeResult)){
			$temp = '';
			if($postData['data']['Order']['order_type_id']==$row['id']){
				$temp = 'selected="selected"';
				$jobetype = $row['name'];
			}
			$optionHTML .='<option  '.$temp.'  value="'.$row['id'].'" > '.$row['name'].' </option>';
		}
		$optionHTML .= '</select>';
		
		
		

		$name = $postData['data']['Customer']['first_name'].' '.$postData['data']['Customer']['last_name'];
		$address = $postData['data']['Customer']['address_unit'].' '.$postData['data']['Customer']['address_street_number'].' '.$postData['data']['Customer']['address_street'];
		$city = $postData['data']['Customer']['city'];
		$postal = $postData['data']['Customer']['postal_code'];
		$email= $postData['data']['Customer']['email']; 
		$phone= $postData['data']['Customer']['phone'];
		$cellphone= $postData['data']['Customer']['cell_phone'];
		$note = $postData['data']['Note']['message'];	
		$officeQuery=$officeQueryHTML;
		$date = $postData['data']['Order']['permit_applied_date'];
		$ref = $postData['data']['Order']['order_number'];

		if($postData['data']['Order']['id']==''){
			$order_id = 0;
			$flag = 'true';
		}else{
			$order_id = $postData['data']['Order']['id'];
			$flag = 'false';
		}


		$template['template'] = str_replace("{name}",$name ,$template['template']);
		$template['template'] = str_replace("{address}",$address ,$template['template']);
		$template['template'] = str_replace("{city}",$city ,$template['template']);
		$template['template'] = str_replace("{postal}",$postal ,$template['template']);
		$template['template'] = str_replace("{email}",$email ,$template['template']);
		$template['template'] = str_replace("{phone}",$phone ,$template['template']);
		$template['template'] = str_replace("{cellphone}",$cellphone ,$template['template']);
		$template['template'] = str_replace("{officequestion}",$officeQuery ,$template['template']);
		$template['template'] = str_replace("{date}",$date ,$template['template']);
		$template['template'] = str_replace("{reference}",$ref ,$template['template']);
		$template['template'] = str_replace("{note}",$note ,$template['template']);

		$template['template'] = str_replace("{jobetype}",$jobetype ,$template['template']);
		
		if($classZeroExist){
			$template['template'] = str_replace("{option1}",$option1 ,$template['template']);	
			$template['template'] = str_replace("{subtotal1}",$subTotal1 ,$template['template']);	
		}else{
			$template['template'] = str_replace("{option1}",'' ,$template['template']);	
			$template['template'] = str_replace("{subtotal1}",'' ,$template['template']);	
		}
		
		if($classOneExist){
			$template['template'] = str_replace("{option2}",$option2 ,$template['template']);	
			$template['template'] = str_replace("{subtotal2}",$subTotal2 ,$template['template']);	
		}else{
			$template['template'] = str_replace("{option2}",'' ,$template['template']);	
			$template['template'] = str_replace("{subtotal2}",'' ,$template['template']);	
		}
		
		$emptytemplate ='';

		$this->set( array(
			'emptytemplate'=>$emptytemplate,
			'template'=>$template,
			'orderData'=>$postData['data'],
			'estimateFor'=>$orderType,
			'note_message'=>$note,
			'order_id' => $order_id,
			'email'=>'',
			'preViewEstimate' => $flag
		));

		$this->render('orderestimate');

	}

    function orderEstimate($order_id , $bookingItem, $fromTech=null ){

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		// Read the order's data from database

		$first_Temp = "";
		$query = "SELECT * FROM ace_rp_order_estimation WHERE order_id=".$order_id;
		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result)){
				$template = $row;
		}

		if(1){

			$this->Order->id = $order_id;
			$orderData = $this->Order->read();
			
			if(empty($template)){
				$templateQuery = "select * from ace_rp_estimation_template where job_type_id =".$this->data['Order']['order_type_id']; 
				$result = $db->_execute($templateQuery);
				$template = mysql_fetch_array($result);
				$first_Temp = "true";
			}
			else{
				$templateQuery = "SELECT * FROM ace_rp_order_estimation WHERE order_id=".$order_id; 
				$result = $db->_execute($templateQuery);
				$template = mysql_fetch_array($result);
			}

		//Load current questions 
		//$officeQuery =  $this->_showQuestions(0, 0,$orderData['Order']['order_type_id'],'');
		$condition = "for_office=1 and";
		$query = " SELECT * FROM ace_rp_questions WHERE ".$condition." order_type_id = ".$orderData['Order']['order_type_id']." order by rank, value ";
		/*ace_rp_orders_questions_working*/
		$result = $db->_execute($query);
		$i=0;
		$temp = array();
		while ($row = mysql_fetch_assoc($result)){
			$temp[] = $row;
		}

		foreach ($temp as $key => $value) {
			$query = "SELECT * FROM ace_rp_orders_questions_working WHERE question_id=".$value['id']." AND order_id=".$order_id ;
			$result = $db->_execute($query);
			$qan = mysql_fetch_assoc($result);

			if(!empty($qan['response_id'])){
				/*ace_rp_responses*/
				$query = "SELECT * FROM ace_rp_responses WHERE id=".$qan['response_id'];
				$result = $db->_execute($query);
				$response = mysql_fetch_assoc($result);
				$qan['response'] = $response['value'];
				$qan['response_text'] = $response['value'];
			}
			$temp[$key]['qan'] = $qan;
		}

		$typeQuery = "SELECT id, name FROM ace_rp_order_types where flagactive=1" ;
		$typeResult = $db->_execute($typeQuery);
		$orderType = array();
		
		
		$query = "SELECT message FROM ace_rp_notes WHERE order_id=".$order_id ;
		$result = $db->_execute($query);
		$note = mysql_fetch_assoc($result);		

		//$temp as officeQuery
		if($first_Temp == "true"){
			$officeQueryHTML = '<table class="officeQueryHTML" style="border-collapse: collapse; width: 35%;" border="1">
				<tbody>
				<tr style="height: 18px;">
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">#</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Questions</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Responses</strong></td>
				</tr>';
			
			foreach ($temp as $key => $value) {
					
				$officeQueryHTML .='<tr style="height: 18px;">
						<td style="width: 16.6667%; height: 18px;text-align: center;">&nbsp;'. $value['rank'].'</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['value'].'</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['qan']['response_text']. '</td>
					</tr>';
			}
			$officeQueryHTML .= '</table>';


			$option1 = '<table class="option1_new" style="border-collapse: collapse; width: 35%;" border="1">
				<tbody>
				<tr style="height: 18px;">
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Item</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Brand</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Model</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Qty</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Price</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Disc</strong></td>
				</tr>';


			$option2 = '<table class="option2_new" style="border-collapse: collapse; width: 35%;" border="1">
				<tbody>
				<tr style="height: 18px;">
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Item</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Brand</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Model</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Qty</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Price</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Disc</strong></td>
				</tr>';

				$subTotalPrice1 = 0;
				$subTotalPrice2 = 0;
				$classZeroExist = 0;
				$classOneExist = 0;
			foreach ($bookingItem as $key => $value) {
				if($value['class']==0){
					$classZeroExist = 1;
					$option1 .='<tr style="height: 18px;">
						<td style="width: 16.6667%; height: 18px;text-align: center;">&nbsp;'. $value['name'].'</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['brand'].'</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['model_number'].'</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['quantity'].'</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['price']. '</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['discount']. '</td>
					</tr>';
					$subTotalPrice1 = $subTotalPrice1+($value['price']*$value['quantity']-$value['discount']);
				}


				if($value['class']==1){
					$classOneExist = 1;
					$option2 .='<tr style="height: 18px;">
						<td style="width: 16.6667%; height: 18px;text-align: center;">&nbsp;'. $value['name'].'</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['brand'].'</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['model_number'].'</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['quantity'].'</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['price']. '</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['discount']. '</td>
					</tr>';
					$subTotalPrice2 = $subTotalPrice2+($value['price']*$value['quantity']-$value['discount']);
				}
			}

			
			$gst1 = $subTotalPrice1*0.05;
			$total1 = $subTotalPrice1+$gst1;

			$gst2 = $subTotalPrice2*0.05;
			$total2 = $subTotalPrice2+$gst2;		


			$subTotal1 = '<table class="subTotal1" style="border-collapse: collapse; width: 15.593%; height: 100px;" border="1">
			<tbody>
			<tr>
			<td style="width: 50%; text-align: right;">SubTotal:</td>
			<td style="width: 50%; text-align: right;">'.$subTotalPrice1.'</td>
			</tr>
			<tr>
			<td style="width: 50%; text-align: right;">GST:</td>
			<td style="width: 50%; text-align: right;">'.$gst1.'</td>
			</tr>
			<tr>
			<td style="width: 50%; text-align: right;">Total</td>
			<td style="width: 50%; text-align: right;">'.$total1.'</td>
			</tr>
			</tbody>
			</table>';

			$subTotal2 = '<table class="subTotal2" style="border-collapse: collapse; width: 15.593%; height: 100px;" border="1">
			<tbody>
			<tr>
			<td style="width: 50%; text-align: right;">SubTotal:</td>
			<td style="width: 50%; text-align: right;">'.$subTotalPrice2.'</td>
			</tr>
			<tr>
			<td style="width: 50%; text-align: right;">GST:</td>
			<td style="width: 50%; text-align: right;">'.$gst2.'</td>
			</tr>
			<tr>
			<td style="width: 50%; text-align: right;">Total</td>
			<td style="width: 50%; text-align: right;">'.$total2.'</td>
			</tr>
			</tbody>
			</table>';

			$option1 .= '</tbody></table>';
			$option2 .= '</tbody></table>';

			$optionHTML = '<select name="jobType">';
			while ($row = mysql_fetch_assoc($typeResult)){
				$temp = '';
				if($orderData['Order']['order_type_id']==$row['id']){
					$temp = 'selected="selected"';
					$jobetype = "<span class='cus_job_type'>".$row['name']."</span>";
				}
				$optionHTML .='<option  '.$temp.'  value="'.$row['id'].'" > '.$row['name'].' </option>';
			}
			$optionHTML .= '</select>';
			
			/*echo $jobetype; die;*/


			$name = "<span class='cus_name'>".$orderData['Customer']['first_name'].' '.$orderData['Customer']['last_name']."</span>";
			$address = "<span class='cus_address'>".$orderData['Customer']['address_unit'].' '.$orderData['Customer']['address_street_number'].' '.$orderData['Customer']['address_street']."</span>";
			$city = "<span class='cus_city'>".$orderData['Customer']['city']."</span>";
			$postal = "<span class='cus_postal'>".$orderData['Customer']['postal_code']."</span>";
			$email= "<span class='cus_email'>".$orderData['Customer']['email']."</span>"; 
			$phone= "<span class='cus_phone'>".$orderData['Customer']['phone']."</span>";
			$cellphone= "<span class='cus_cellphone'>".$orderData['Customer']['cell_phone']."</span>";
			$note = "<span class='cus_note'>".$note['message']."</span>";
			$officeQuery = $officeQueryHTML;
			$date = "<span class='cus_date'>".$orderData['Order']['booking_date']."</span>";
			$reference = "<span class='cus_ref'>".$orderData['Order']['id']."</span>";
			$ref = $orderData['Order']['id'];
		
			$template['template'] = str_replace("{name}",$name ,$template['template']);
			$template['template'] = str_replace("{address}",$address ,$template['template']);
			$template['template'] = str_replace("{city}",$city ,$template['template']);
			$template['template'] = str_replace("{postal}",$postal ,$template['template']);
			$template['template'] = str_replace("{email}",$email ,$template['template']);
			$template['template'] = str_replace("{phone}",$phone ,$template['template']);
			$template['template'] = str_replace("{cellphone}",$cellphone ,$template['template']);
			$template['template'] = str_replace("{officequestion}",$officeQuery ,$template['template']);
			$template['template'] = str_replace("{date}",$date ,$template['template']);
			$template['template'] = str_replace("{reference}",$reference ,$template['template']);
			$template['template'] = str_replace("{note}",$note ,$template['template']);

			$template['template'] = str_replace("{jobetype}",$jobetype ,$template['template']);
			
			if($classZeroExist){
				$template['template'] = str_replace("{option1}",$option1 ,$template['template']);	
				$template['template'] = str_replace("{subtotal1}",$subTotal1 ,$template['template']);	
			}else{
				$template['template'] = str_replace("{option1}",'' ,$template['template']);	
				$template['template'] = str_replace("{subtotal1}",'' ,$template['template']);	
			}
			
			// if($classOneExist){
			$template['template'] = str_replace("{option2}",$option2 ,$template['template']);	
			$template['template'] = str_replace("{subtotal2}",$subTotal2 ,$template['template']);	
			// }else{
			// 	$template['template'] = str_replace("{option2}",'' ,$template['template']);	
			// 	$template['template'] = str_replace("{subtotal2}",'' ,$template['template']);	
			// }
		}
		else{
			$officeQueryHTML = '<table class="officeQueryHTML" style="border-collapse: collapse; width: 35%;" border="1">
				<tbody>
				<tr style="height: 18px;">
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">#</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Questions</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Responses</strong></td>
				</tr>';
			
			foreach ($temp as $key => $value) {
					
				$officeQueryHTML .='<tr style="height: 18px;">
						<td style="width: 16.6667%; height: 18px;text-align: center;">&nbsp;'. $value['rank'].'</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['value'].'</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['qan']['response_text']. '</td>
					</tr>';
			}
			$officeQueryHTML .= '</table>';

			$option1 = '<table class="option1_new" style="border-collapse: collapse; width: 35%;" border="1">
				<tbody>
				<tr style="height: 18px;">
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Item</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Brand</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Model</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Qty</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Price</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Disc</strong></td>
				</tr>';


			$option2 = '<table class="option2_new" style="border-collapse: collapse; width: 35%;" border="1">
				<tbody>
				<tr style="height: 18px;">
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Item</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Brand</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Model</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Qty</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Price</strong></td>
					<td style="width: 16.6667%; height: 18px; text-align: center;"><strong contenteditable="false">Disc</strong></td>
				</tr>';

				$subTotalPrice1 = 0;
				$subTotalPrice2 = 0;
				$classZeroExist = 0;
				$classOneExist = 0;
			foreach ($bookingItem as $key => $value) {
				if($value['class']==0){
					$classZeroExist = 1;
					$option1 .='<tr style="height: 18px;">
						<td style="width: 16.6667%; height: 18px;text-align: center;">&nbsp;'. $value['name'].'</td>
						<td style="width: 16.6667%; height: 18px;text-align: center;">&nbsp;'. $value['brand'].'</td>
						<td style="width: 16.6667%; height: 18px;text-align: center;">&nbsp;'. $value['model_number'].'</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['quantity'].'</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['price']. '</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['discount']. '</td>
					</tr>';
					$subTotalPrice1 = $subTotalPrice1+($value['price']*$value['quantity']-$value['discount']);
				}


				if($value['class']==1){
					$classOneExist = 1;
					$option2 .='<tr style="height: 18px;">
						<td style="width: 16.6667%; height: 18px;text-align: center;">&nbsp;'. $value['name'].'</td>
						<td style="width: 16.6667%; height: 18px;text-align: center;">&nbsp;'. $value['brand'].'</td>
						<td style="width: 16.6667%; height: 18px;text-align: center;">&nbsp;'. $value['model_number'].'</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['quantity'].'</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['price']. '</td>
						<td style="width: 16.6667%; height: 18px;">&nbsp;'. $value['discount']. '</td>
					</tr>';
					$subTotalPrice2 = $subTotalPrice2+($value['price']*$value['quantity']-$value['discount']);
				}
			}
			
			$gst1 = $subTotalPrice1*0.05;
			$total1 = $subTotalPrice1+$gst1;

			$gst2 = $subTotalPrice2*0.05;
			$total2 = $subTotalPrice2+$gst2;		


			$subTotal1 = '<table class="subTotal1" style="border-collapse: collapse; width: 15.593%; height: 100px;" border="1">
			<tbody>
			<tr>
			<td style="width: 50%; text-align: right;">SubTotal:</td>
			<td style="width: 50%; text-align: right;">'.$subTotalPrice1.'</td>
			</tr>
			<tr>
			<td style="width: 50%; text-align: right;">GST:</td>
			<td style="width: 50%; text-align: right;">'.$gst1.'</td>
			</tr>
			<tr>
			<td style="width: 50%; text-align: right;">Total</td>
			<td style="width: 50%; text-align: right;">'.$total1.'</td>
			</tr>
			</tbody>
			</table>';

			$subTotal2 = '<table class="subTotal2" style="border-collapse: collapse; width: 15.593%; height: 100px;" border="1">
			<tbody>
			<tr>
			<td style="width: 50%; text-align: right;">SubTotal:</td>
			<td style="width: 50%; text-align: right;">'.$subTotalPrice2.'</td>
			</tr>
			<tr>
			<td style="width: 50%; text-align: right;">GST:</td>
			<td style="width: 50%; text-align: right;">'.$gst2.'</td>
			</tr>
			<tr>
			<td style="width: 50%; text-align: right;">Total</td>
			<td style="width: 50%; text-align: right;">'.$total2.'</td>
			</tr>
			</tbody>
			</table>';

			$option1 .= '</tbody></table>';
			$option2 .= '</tbody></table>';

			$optionHTML = '<select name="jobType">';
			while ($row = mysql_fetch_assoc($typeResult)){
				$temp = '';
				if($orderData['Order']['order_type_id']==$row['id']){
					$temp = 'selected="selected"';
					$jobetype = $row['name'];
				}
				$optionHTML .='<option  '.$temp.'  value="'.$row['id'].'" > '.$row['name'].' </option>';
			}
			$optionHTML .= '</select>';
			
			/*echo $jobetype; die;*/

			$name = "<span class='cus_name'>".$orderData['Customer']['first_name'].' '.$orderData['Customer']['last_name']."</span>";
			$address = "<span class='cus_address'>".$orderData['Customer']['address_unit'].' '.$orderData['Customer']['address_street_number'].' '.$orderData['Customer']['address_street']."</span>";
			$city = "<span class='cus_city'>".$orderData['Customer']['city']."</span>";
			$postal = "<span class='cus_postal'>".$orderData['Customer']['postal_code']."</span>";
			$email= "<span class='cus_email'>".$orderData['Customer']['email']."</span>"; 
			$phone= "<span class='cus_phone'>".$orderData['Customer']['phone']."</span>";
			$cellphone= "<span class='cus_cellphone'>".$orderData['Customer']['cell_phone']."</span>";
			$note = "<span class='cus_note'>".$note['message']."</span>";
			$date = "<span class='cus_date'>".$orderData['Order']['booking_date']."</span>";
			$reference = "<span class='cus_ref'>".$orderData['Order']['id']."</span>";
			$ref = $orderData['Order']['id'];

			$template['estimate'] = preg_replace('/<span class=\"cus_name\">.*<\/span>/',$name,$template['estimate']);
			$template['estimate'] = preg_replace('/<span class=\"cus_address\">.*<\/span>/',$address,$template['estimate']);
			$template['estimate'] = preg_replace('/<span class=\"cus_city\">.*<\/span>/',$city,$template['estimate']);
			$template['estimate'] = preg_replace('/<span class=\"cus_postal\">.*<\/span>/',$postal,$template['estimate']);
			$template['estimate'] = preg_replace('/<span class=\"cus_email\">.*<\/span>/',$email,$template['estimate']);
			$template['estimate'] = preg_replace('/<span class=\"cus_phone\">.*<\/span>/',$phone,$template['estimate']);
			$template['estimate'] = preg_replace('/<span class=\"cus_cellphone\">.*<\/span>/',$cellphone,$template['estimate']);
			$template['estimate'] = preg_replace('/<span class=\"cus_note\">.*<\/span>/',$note,$template['estimate']);
			$template['estimate'] = preg_replace('/<span class=\"cus_off_query\">.*<\/span>/',$officeQuery,$template['estimate']);
			$template['estimate'] = preg_replace('/<span class=\"cus_date\">.*<\/span>/',$date,$template['estimate']);
			$template['estimate'] = preg_replace('/<span class=\"cus_ref\">.*<\/span>/',$reference,$template['estimate']);

			$template['estimate'] =preg_replace('/<table class="officeQueryHTML" [^>]*>.*?<\/table>/si',$officeQueryHTML,$template['estimate']);

			$template['estimate'] = preg_replace('/<span class=\"cus_job_type\">.*<\/span>/',$jobetype,$template['estimate']);
			
			if($classZeroExist){ 
				$template['estimate'] =preg_replace('/<table class="option1_new" [^>]*>.*?<\/table>/si',$option1,$template['estimate']);
				$template['estimate'] =preg_replace('/<table class="subtotal1" [^>]*>.*?<\/table>/si',$subTotal1,$template['estimate']);
			}else{
				$template['estimate'] =preg_replace('/<table class="option1_new" [^>]*>.*?<\/table>/si','',$template['estimate']);
				$template['estimate'] =preg_replace('/<table class="subtotal1" [^>]*>.*?<\/table>/si','',$template['estimate']);
			}
			
			if($classOneExist){

				$template['estimate'] =preg_replace('/<table class="option2_new" [^>]*>.*?<\/table>/si',$option2,$template['estimate']);
				$template['estimate'] =preg_replace('/<table class="subtotal2" [^>]*>.*?<\/table>/si',$subTotal2,$template['estimate']);
			}else{	
				$template['estimate'] =preg_replace('/<table class="option2_new" [^>]*>.*?<\/table>/si','',$template['estimate']);
				$template['estimate'] =preg_replace('/<table class="subtotal2" [^>]*>.*?<\/table>/si','',$template['estimate']);
			}

			$template['template']=$template['estimate'];
		}
		
		$emptytemplate ='';

		$this->set( array(
			'emptytemplate'=>$emptytemplate,
			'template'=>$template,
			'orderData'=>$orderData,
			'estimateFor'=>$orderType,
			'note_message'=>$note,
			'order_id' => $ref,
			'email'=>'',
			'preViewEstimate' => "false",
			'fromTech' => $fromTech 
		));

		}else{

			$template['template']=$template['estimate'];
			$emptytemplate ='';
			$this->set( array(
				'emptytemplate'=>$emptytemplate,
				'template'=>$template,
				'order_id'=> $order_id,
				'email'=>'',
				'preViewEstimate' => "false",
				'fromTech' => $fromTech
			));
		}
		$this->render('orderestimate');

	}

	function saveAndSendEstimateForOrder(){

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		if(isset($_POST['print'])){

			$query = "SELECT * FROM ace_rp_order_estimation WHERE order_id=".$_POST['order_id'];
			$result = $db->_execute($query);

			while ($row = mysql_fetch_array($result)){
				$template = $row;
			}

			if($template){
				$query = "UPDATE ace_rp_order_estimation set estimate='".$_POST['editor']."' where order_id = ".$_POST['order_id'];

			}
			else{
			
				$query = "
						INSERT INTO ace_rp_order_estimation(order_id, estimate)
						VALUES(".$_POST['order_id'].",'".$_POST['editor']."')";
			}
			
			$result = $db->_execute($query);
			$this->estimateTabletPrint($_POST['order_id']);

		}else{
			
			$query = "SELECT * FROM ace_rp_order_estimation WHERE order_id=".$_POST['order_id'];
			$result = $db->_execute($query);

			while ($row = mysql_fetch_array($result)){
				$template = $row;
			}

			if($template){

				$query = "UPDATE ace_rp_order_estimation set estimate='".$_POST['editor']."' where order_id = ".$_POST['order_id'];

			}
			else{
				$query = "
						INSERT INTO ace_rp_order_estimation(order_id, estimate)
						VALUES(".$_POST['order_id'].",'".$_POST['editor']."')";
			}
			$result = $db->_execute($query);
			/*$subject = 'Ace Services Ltd';
			$headers = "From: info@acecare.ca\n";
			$headers .="MIME-Version: 1.0";
    		$headers .="Content-Type: text/html;charset=utf-8";
			$msg = '<html><body>'.$_POST['editor'].'</body></html>';
			$msg = str_replace("\\", '' , $msg);*/
			// echo $_POST['email']."".$subject,$msg."".$headers;die;
			/*$res = mail($_POST['email'], $subject,$msg, $headers);
			$this->redirect('/orders/scheduleView');*/
			exit;
		}
	}

	function sendMailEstimate(){
		$orderId = $_POST['order_id'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "SELECT oe.*, o.* FROM ace_rp_order_estimation oe INNER JOIN ace_rp_orders o ON oe.order_id = o.id   WHERE oe.order_id=".$orderId; 
		$result = $db->_execute($query);
		$currentDate = date("Y-m-d");
		while ($row = mysql_fetch_array($result)){
			$template = $row;
		}
		$subject = 'Ace Services : Order Estimate';
		$new = str_replace('&nbsp;', ' ', $template['estimate']);
		$message = html_entity_decode($new);
		$email = $_POST['email'];
		$res1 = $this->sendEmailUsingMailgun($email,$subject,$message);		
		if (strpos($res1, '@acecare') !== false) 
		{
	    	$is_sent = 1;
		} else 
		{
			$is_sent = 0;
		}
		
		if($is_sent) {
			$db->_execute("UPDATE ace_rp_orders set estimate_sent =".$is_sent." where id=".$orderId);
			$insertLog = "INSERT INTO ace_rp_reminder_email_log (order_id, customer_id, job_type, sent_date, is_sent, message, message_id) values (".$orderId.",".$template['customer_id'].",".$template['order_type_id'].",'".$currentDate."',".$is_sent.",'".$message."', '".$res1."')";
			$insetRes = $db->_execute($insertLog);

			$response  = array("res" => "OK");
	 		echo json_encode($response);
	 		exit();
		}
		exit;
	}


	function sendemailTmp(){
		$tmp  = $_POST['editor'];
		
		$subject = 'Ace Services : Order Estimate';
		$headers = "From: info@acecare.ca\n";
		/*$headers .="MIME-Version: 1.0";*/
    	$headers .="Content-Type: text/html;charset=utf-8";
    	/*$header .= "Content-Type: text/html; charset=iso-8859-1\n" ;*/
		$msg = '<html><body>'.$tmp.'</body></html>';
		/*$msg = 'djhfdhg fgjfdg fhbgdfgjfd gdfjkgfd gfjdgf <br> <h2>fgkjhfdgjfd</h2>';*/
		$msg = str_replace("\\", '' , $msg);
		$res = mail($_POST['email'], $subject,$msg, $headers);
		exit;
	}


	function estimateTabletPrint($order_id){

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "SELECT * FROM ace_rp_order_estimation WHERE order_id=".$order_id;
		$result = $db->_execute($query);

		while ($row = mysql_fetch_array($result)){
			$template = $row;
		}
		echo $template['estimate']; exit;
	}


	function showEstimateForOrder(){

		if($_GET['order_id'] != 'undefined'){
			$emptytemplate = '';

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "SELECT * FROM ace_rp_order_estimation WHERE order_id=".$_GET['order_id'];
		$result = $db->_execute($query);

		while ($row = mysql_fetch_array($result)){
			$template = $row;
		}
		if(!empty($template[2])){

			$this->set( array(			
			'template'=>$template[2],
			'emptytemplate'=>$emptytemplate,		
			'order_id' => $_GET['order_id'],
			'email' => $_GET['email']
		));

		$this->render('orderestimate');

		}
		else{

		$emptytemplate = "Not yet";

			$this->set( array(			
			'emptytemplate'=>$emptytemplate
		));
		$this->render('orderestimate');
		}
		}
		else{			
		
		$emptytemplate = "Not yet";
		$this->set( array(			
			'emptytemplate'=>$emptytemplate
		));
		$this->render('orderestimate');
		}

	}
 
	function saveOrderFromVici($bookingTelemarketerId, $saveCustomer=1)
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
	        $this->data['Order']['booking_telemarketer_id'] = $bookingTelemarketerId;
			$this->data['Order']['booking_source_id'] = $bookingTelemarketerId;

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

		// Save the customer
		if ($saveCustomer==1)
			if (!empty($this->data['Customer'])) $this->_SaveCustomer();

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
		/*$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		for ($i = 0; $i < count($this->data['Order']['OrdersQuestions']); $i++)
		{
			// Set ID of parent order
			$this->data['Order']['OrdersQuestions'][$i]['order_id'] = $order_id;
			$db->_execute("delete from ace_rp_orders_questions where order_id=$order_id and question_number='{$this->data['Order']['OrdersQuestions'][$i]['question_number']}'");
			$query="insert INTO ace_rp_orders_questions
				  (order_id, for_office, for_tech, question, local_answer,
				   question_number, question_id, answers)
				  VALUES
				  ('".$order_id."', '".$this->data['Order']['OrdersQuestions'][$i]['for_office']."',
				   '".$this->data['Order']['OrdersQuestions'][$i]['for_tech']."',
				   '".$this->data['Order']['OrdersQuestions'][$i]['question']."',
				   '".str_replace("'","`",$this->data['Order']['OrdersQuestions'][$i]['local_answer'])."',
				   '".$this->data['Order']['OrdersQuestions'][$i]['question_number']."',
				   '".$this->data['Order']['OrdersQuestions'][$i]['question_id']."',
				   '".$this->data['Order']['OrdersQuestions'][$i]['answers']."')";
			$db->_execute($query);
		}*/

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

				$query = "
					DELETE FROM ace_rp_orders_questions_working
					WHERE order_id = $order_id
					AND question_id = $question_id
				";

				$result = $db->_execute($query);

				$query = "
					INSERT INTO ace_rp_orders_questions_working(order_id, question_id, response_text, response_id, suggestion_id, decision_id)
					VALUES($order_id, $question_id, $response_text, $response_id, $suggestion_id, $decision_id)
				";

				$result = $db->_execute($query);
			}
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
						$this->emailCustomerBooking($order_id);
		}

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

		//Forward user where they need to be - if this is a single action per view
		if (($old_status == 1)&&($this->data['Order']['order_status_id'] == 2))
			$this->reschedule();
		elseif ($this->data['rurl'][0])
			$this->redirect($this->data['rurl'][0]);
		else
			$this->redirect('/orders/scheduleView');
    }

    // Function for add/edit pitch

     function editPitch()
    {
    	$id = $_SESSION['user']['id'];
    	$pitch1 = isset($this->data['Pitch']['pitch1']) ? $this->data['Pitch']['pitch1']: '' ;
		$pitch2 = isset($this->data['Pitch']['pitch2']) ? $this->data['Pitch']['pitch2']: '' ;
		$pitchType = $_POST['pitch_type'];

		if (empty($this->data['User']))
		{
			$this->User->id = $id;
			$db =& ConnectionManager::getDataSource('default');
			$query = "SELECT * from ace_rp_users_pitch where user_id = $id";
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result);
			$this->data = $this->User->read();
			$this->data['Pitch']['pitch1'] = isset($row['pitch1'])? $row['pitch1'] : '';
			$this->data['Pitch']['pitch2'] = isset($row['pitch2'])? $row['pitch2'] : '';
			$this->data['Pitch']['pitch_type'] = isset($row['pitch_type'])? $row['pitch_type'] : '';
			$this->set('data', $this->data);
		}
		else if (!empty($this->data['User']))
		{
		if(!empty($pitch1) || !empty($pitch2)) 
			{
				$db =& ConnectionManager::getDataSource('default');
				$query = "SELECT * from ace_rp_users_pitch where user_id = $id";
				$result = $db->_execute($query);
				if($row = mysql_fetch_array($result))
				{ 
					$query = "UPDATE ace_rp_users_pitch SET pitch1='".$pitch1."', pitch2='".$pitch2."', pitch_type=".$pitchType."  WHERE user_id=$id";
					$result = $db->_execute($query);
				} else {
					$query = "INSERT INTO ace_rp_users_pitch (user_id, pitch1, pitch2, pitch_type)
					VALUES ($id,'".$pitch1."','".$pitch2."' ,$pitchType)";
					$result = $db->_execute($query);
				}

				$this->redirect('/orders/scheduleView');
				exit();
			}
		}
    }
    
	// Function Name: Edit Booking
	// Hardcoded:	* User Roles
	function editBooking()
	{   
		// error_reporting(E_ALL);
		// print_r("jbvjb"); die;
		$this->layout='edit';
		$fromTech = isset($this->params['url']['from_tech_page']) ? $this->params['url']['from_tech_page'] :0;
		$techOrderId = isset($this->params['url']['techOrderId']) ? $this->params['url']['techOrderId'] :0;

		$hotlist = isset($this->params['url']['hotlist'])?$this->params['url']['hotlist']:0;
		;
		$isDialer = isset($this->params['url']['is_dialer'])?$this->params['url']['is_dialer']:0;

		$is_booking = isset($this->params['url']['is_booking'])?$this->params['url']['is_booking'] : "";
		$orderNo = isset($this->params['url']['orderNo'])?$this->params['url']['orderNo'] : '';
		$fromEstimate = isset($this->params['url']['fromEstimate']) ? $this->params['url']['fromEstimate'] :0;

		if (!empty($this->data['Order']))
		{
			$showDefault = isset($_POST['showDefault'])?$_POST['showDefault']:0;
			//If order information is submitted - save the order
			$send_cancalled_email = isset($_POST['send_cancalled_email'])?$_POST['send_cancalled_email']
			:0;
			$isDialer = isset($_POST['from_dialer'])?$_POST['from_dialer']
			:0;
			$fromTech = isset($_POST['from_tech']) ? $_POST['from_tech'] :0;
			$techOrderId = isset($_POST['techOrderId']) ? $_POST['techOrderId'] :0;
			$file = isset($_FILES['uploadFile'])? $_FILES['uploadFile'] : null;
			$invoiceImages = isset($_FILES['uploadInvoice'])? $_FILES['uploadInvoice'] : null;
			$photoImage1 = isset($_FILES['sortpic1'])? $_FILES['sortpic1'] : null;
			$photoImage2 = isset($_FILES['sortpic2'])? $_FILES['sortpic2'] : null;
			$this->saveOrder(1, $isDialer, $file, $invoiceImages, $photoImage1, $photoImage2, $fromTech, $techOrderId, $send_cancalled_email, $showDefault);
		}
		else
		{
		
			$this->set('is_booking',$is_booking);
			$this->set('orderNo',$orderNo);
			$this->set('currentUrl', $_SERVER['REQUEST_URI']);

			// If no order data is submitted, we'll have one of the following situations:
			// 1. we are being asked to display an existing order's data ($order_id!='')
			// 2. we are being asked to create a new order for an existing customer ($order_id=='', $customer_id!='')
			// 3. we are being asked to create a completely new customer ($order_id=='', $customer_id=='')
			// Check submitted data for any special parameters to be set
			$order_id = $this->params['url']['order_id'];
			$customer_id = $this->params['url']['customer_id'];
			
			if($customer_id)
			{
				$this->Customer->id = $customer_id;
				$cus = $this->Customer->read(); 	
				$this->set('campaingId',$cus['Customer']['campaign_id']);
			}
			
			
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

			if($_SESSION['user']['role_id'] == 6 && $_REQUEST['admin_select'] == 1){
				$query = "UPDATE ace_rp_customers SET selected_customer_from_search = NULL WHERE selected_customer_from_search = ".$_SESSION['user']['id'];

				$result = $db->_execute($query);

				$query = "UPDATE ace_rp_customers SET selected_customer_from_search = ".$_SESSION['user']['id']." WHERE id = $customer_id";

				$result = $db->_execute($query);
			}
			elseif ($_SESSION['user']['role_id'] == 3 && $_REQUEST['agent_select'] == 1) {
				$query = "UPDATE ace_rp_customers SET selected_customer_from_search_agent = NULL WHERE selected_customer_from_search_agent = ".$_SESSION['user']['id'];

				$result = $db->_execute($query);

				$query = "UPDATE ace_rp_customers SET selected_customer_from_search_agent = ".$_SESSION['user']['id']." WHERE id = $customer_id";

				$result = $db->_execute($query);
			}
			//# Loki Retrieve all payment types
			$query = " SELECT * from ace_rp_payment_methods";
			$result1 = $db->_execute($query);
			while($row = mysql_fetch_array($result1))
			{
				$methods[$row['id']] = $row;
			}
		
			$this->set("payment_types", $methods);
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

				/** Fetch order payment image using order id*/
				// echo 'Order id : '.$this->data['Order']['id']; 
				$queryPayment 	= "select payment_image from ace_rp_orders where id='".$this->data['Order']['id']."'";
				$resultPayment 	= $db->_execute($queryPayment);
				$rowPayment 	= mysql_fetch_array($resultPayment, MYSQL_ASSOC);
				if($rowPayment){
					$this->set('invoice_image_path', $rowPayment['payment_image']);
				}
				/* closed */

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
				$tech1_comm = $comm[0][1]['total_comm'];
				$tech2_comm = $comm[0][2]['total_comm'];
				if ($this->data['Order']['booking_source_id']==$this->data['Order']['job_technician1_id'])
					$tech1_comm += $comm[0][3]['total_comm'];
				elseif ($this->data['Order']['booking_source_id']==$this->data['Order']['job_technician2_id'])
					$tech2_comm += $comm[0][3]['total_comm'];
				elseif ($this->data['Order']['booking_source2_id']==$this->data['Order']['job_technician1_id'])
					$tech1_comm += $comm[0][4]['total_comm'];
				elseif ($this->data['Order']['booking_source2_id']==$this->data['Order']['job_technician2_id'])
					$tech2_comm += $comm[0][4]['total_comm'];
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
			$this->data['Order']['job_date'] = $this->params['url']['job_date'];
			$this->data['Order']['job_time_beg'] = $this->params['url']['job_time_beg'];
			$this->data['Order']['job_technician1_id'] = $this->params['url']['job_technician1_id'];
			$this->data['Order']['job_technician2_id'] = $this->params['url']['job_technician2_id'];
			// $this->data['Order']['order_campaing_id'] = 1;
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
		$this->set('from_dialer',$isDialer);
		$this->set('from_tech',$fromTech);
		$this->set('techOrderId', $techOrderId);
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
			if($this->Common->getLoggedUserRoleID() == 3 || $this->Common->getLoggedUserRoleID() == 6) 
			{
				$this->set('tab_num',1);
				$this->set('tab1','tabOver ');
				$this->set('tab7','tabOff');
				$this->set('tab3','tabOff');
				$this->set('tab10','tabOff');
				$this->set('tab11','tabOff');
				$this->set('page1','block');
				$this->set('page3','none');
				$this->set('page7','none');
				$this->set('page10','none');
				$this->set('page11','none');
			} else {
				$this->set('tab_num',3);
				$this->set('tab1','tabOff');
				$this->set('tab7','tabOff');
				$this->set('tab10','tabOff');
				$this->set('tab11','tabOff');
				$this->set('tab3','tabOver');
				$this->set('page1','none');
				$this->set('page3','block');
				$this->set('page10','none');
				$this->set('page7','none');
				$this->set('page11','none');
			}
					
		}else if($hotlist){
			$this->set('tab_num',7);
			$this->set('tab1','tabOff ');
			$this->set('tab7','tabOver');
			$this->set('tab3','tabOff');
			$this->set('tab10','tabOff');
			$this->set('tab11','tabOff');
			$this->set('page1','none');
			$this->set('page3','none');
			$this->set('page10','none');
			$this->set('page7','block');
			$this->set('page11','none');
		} 
		else
		{
			$this->set('tab_num',1);
			$this->set('tab1','tabOver ');
			$this->set('tab7','tabOff');
			$this->set('tab3','tabOff');
			$this->set('tab10','tabOff');
			$this->set('tab11','tabOff');
			$this->set('page1','block');
			$this->set('page3','none');
			$this->set('page7','none');
			$this->set('page10','none');
			$this->set('page11','none');

		}
		// Get call recordings 1 800 394 1980
		$recordings = array();
		if(!empty($this->data['Customer']['phone']))
		{
			$query =  "SELECT * FROM ace_rp_call_recordings where phone_no='".$this->data['Customer']['phone']."' order by id desc";
			// $query =  "SELECT * FROM ace_rp_call_recordings where phone_no='1 800 394 1980' order by id desc";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				array_push($recordings, $row);
			}
		}
		$emailLogs = array();
		$cusId = $customer_id;
		if(empty($cusId))
		{
			$cusId = $this->data['Order']['customer_id'];
		}
		$query =  "SELECT el.*, ot.name as job_type_name FROM ace_rp_reminder_email_log el LEFT JOIN ace_rp_order_types ot ON el.job_type = ot.id where el.customer_id='".$cusId."' order by id desc";
			// $query =  "SELECT * FROM ace_rp_call_recordings where phone_no='1 800 394 1980' order by id desc";
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			array_push($emailLogs, $row);
		}
		$receivedLogs = array();
		//$receivedEmails =  "SELECT * FROM ace_rp_customer_mail_response where customer_id = ".$cusId." order by id desc";
		$receivedEmails =  "SELECT * FROM ace_rp_customer_mail_response where email = '".$this->data['Customer']['email']."' order by id desc";
		$emailResult = $db->_execute($receivedEmails);
		while($row = mysql_fetch_array($emailResult, MYSQL_ASSOC))
		{
			array_push($receivedLogs, $row);
		}
		$this->set('receivedLogs', $receivedLogs);
		$this->set('emailLogs',$emailLogs);
		$this->set('callRecordings',$recordings);
		// PREPARE DATA FOR UI
		// Get Associated Options
		if (!$this->data['Order']['permit_applied_date'])
			$this->data['Order']['permit_applied_date'] = date('d M Y');
		else
			$this->data['Order']['permit_applied_date'] = date('d M Y', strtotime($this->data['Order']['permit_applied_date']));

		$this->set('job_trucks', $this->HtmlAssist->table2array($this->InventoryLocation->findAll(array('type' => '2','flagactive' => '0'), null,"order by order_id", null, 1, 0), 'id', 'name'));
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query =  "SELECT id, CONCAT(name, REPLACE(REPLACE(flagactive, 0, ' [INACTIVE]'), 1, '')) AS truck FROM ace_rp_inventory_locations order by order_id";

		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $items[$row['id']][$k] = $v;
		}

		$query =  "SELECT id, campaign_name, camp_city FROM ace_rp_reference_campaigns";
		$campListArray = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
		  $cla['id'] = $row['id'];
		  $cla['camp_name'] = $row['campaign_name'];
		  $cla['camp_city'] = $row['camp_city'];
		  $campListArray[] = $cla;
		}
		$this->set('job_trucks2', $items);
		$this->set('from_estimate', $fromEstimate);
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
		$this->set('campaing_list',$campListArray);
		//$this->set('recordingFile', $recordingFile);

		// Past Order View Mode
		if ($this->data['Status']['name'] == 'Done') $this->set('ViewMode', 1);

		$this->data['Coupon'] = $this->Coupon->findAll();

		//Make Redo Orders List
		//$redo_orders = $this->_getPreviousJobs($this->data['Customer']['id']);
		if ($this->data['Order']['job_estimate_id'])
		{
			$past_orders = $this->Order->findAll(array('Order.id'=> $this->data['Order']['job_estimate_id']), null, "job_date DESC", null, null, 1);
			foreach ($past_orders as $ord)
				$job_estimate_text = 'REF# '.$ord['Order']['order_number'].' - '.date('d M Y', strtotime($ord['Order']['job_date']));
		}
		// Find customer's notes
		if ($this->data['Customer']['id'])
		{
        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
        $query = "SELECT * FROM ace_rp_users_notes WHERE user_id=".$this->data['Customer']['id']." ORDER BY note_date DESC";
        $result = $db->_execute($query);
        while ($row = mysql_fetch_array($result))
            $customer_notes[$row['id']] = $row;
		}
		$this->set('past_orders', $past_orders);
		$this->set('redo_orders', $redo_orders);
		$this->set('customer_notes',$customer_notes);
		$this->set('job_estimate_text',$job_estimate_text);
		$this->set('yesOrNo', $this->Lists->YesOrNo());

		//Set pitch for dialer page
		$user_id = $_SESSION['user']['id'];
		$query = "select * from ace_rp_users_pitch where user_id = $user_id ";
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		if($row['pitch_type'] == 1)
		{
			$this->set('pitch', $row['pitch1']);
		} else {
			$this->set('pitch', $row['pitch2']);
		}

		// Prepare dates for selector
		if ((strlen($this->data['Order']['job_date']) > 0) && ($this->data['Order']['job_date'] != "0000-00-00"))
			$this->data['Order']['job_date'] = date("d M Y", strtotime($this->data['Order']['job_date']));
		if ((strlen($this->data['CallRecord']['callback_date']) > 0) && ($this->data['CallRecord']['callback_date'] != "0000-00-00"))
			$this->data['CallRecord']['callback_date'] = date("d M Y", strtotime($this->data['CallRecord']['callback_date']));
		if ((strlen($this->data['CallRecord']['call_date']) > 0) && ($this->data['CallRecord']['call_date'] != "0000-00-00"))
			$this->data['CallRecord']['call_date'] = date("d M Y", strtotime($this->data['CallRecord']['call_date']));

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

		if(!empty($order_id)) {
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
			$i = 0;
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

		if(!empty($order_id)) {
			$query = "SELECT photo_1, photo_2 FROM ace_rp_orders WHERE id = ".$order_id;
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result)) {
					$this->data['Order']['photo_1'] = $this->getPhotoPath($row['photo_1']);
					$this->data['Order']['photo_2'] = $this->getPhotoPath($row['photo_2']);
			}
		}
	}

	function getPhotoPath($name)
	{
		if (!$name)
			return "";
		$year = substr($name, 0, 4);
		$mon = substr($name, 4, 2);
		$day = substr($name, 6, 2);
		$name = $year.'/'.$mon.'/'.$day.'/'.$name;
		return $name;
	}

	// Function Name: Tech Booking
	// Hardcoded:	* User Roles
	function techBooking()
	{
		$this->layout='edit';
		if (!empty($this->data['Order']))
		{
			$file = isset($_FILES['uploadFile1'])? $_FILES['uploadFile1'] : null;
			$invoiceImages	=	isset($_FILES['uploadInvoice']) ? $_FILES['uploadInvoice']:null;
			$photoImage1 = isset($_FILES['sortpic1'])? $_FILES['sortpic1'] : null;
			$photoImage2 = isset($_FILES['sortpic2'])? $_FILES['sortpic2'] : null;
			$fromTech = !empty($_POST['fromTech']) ? $_POST['fromTech'] : 3;
			$jobDate = !empty($_POST['jobDate']) ? $_POST['jobDate'] : '';
			//If order information is submitted - save the order
			$this->saveOrder(0,'',$file, $invoiceImages, $photoImage1, $photoImage2, $fromTech,'','','',$jobDate);
		}
		else
		{
			// If no order data is submitted, we'll have one of the following situations:
			// 1. we are being asked to display an existing order's data ($order_id!='')
			// 2. we are being asked to create a new order for an existing customer ($order_id=='', $customer_id!='')
			// 3. we are being asked to create a completely new customer ($order_id=='', $customer_id=='')
			// Check submitted data for any special parameters to be set
			$order_id = $this->params['url']['order_id'];
			$customer_id = $this->params['url']['customer_id'];
			$fromTech = $_GET['fromTech'];
			$jobDate = isset($_GET['job_date']) ? $_GET['job_date'] : '';
			$this->set('jobDate', $jobDate);
			$this->set('fromTech', $fromTech);
		    $num_items = 0;
		    $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			// If order ID is submitted, prepare order's data to be displayed
			if ($order_id)
			{
				// Read the order's data from database
				$this->Order->id = $order_id;
				$this->data = $this->Order->read();

		        $h_booked='';
		        $h_tech='';
            	$b_actions = false;
            	$s_actions = false;
						if (($this->Common->getLoggedUserID()==$this->data['Order']['booking_source_id'])
								||($this->Common->getLoggedUserID()==$this->data['Order']['booking_source2_id']))
                $b_actions = true;
						if (($this->Common->getLoggedUserID()==$this->data['Order']['job_technician1_id'])
								||($this->Common->getLoggedUserID()==$this->data['Order']['job_technician2_id']))
                $s_actions = true;
		        foreach ($this->data['BookingItem'] as $oi)
		        {
		          if ($oi['class']==0)
		          {
		            $h_booked .= '<tr id="order_'.$num_items.'" class="booked">';
		            $h_booked .= $this->_itemHTML($num_items, $oi, $b_actions);
		            $h_booked .= '</tr>';
		          }
		          else
		          {
		            $h_tech .= '<tr id="order_'.$num_items.'" class="extra">';
		            $h_tech .= $this->_itemHTML($num_items, $oi, $s_actions);
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
		          $h_booked .= $this->_itemHTML($num_items, $oi, false);
		          $h_booked .= '</tr>';
		          $num_items++;
		        }
		        $this->set('booked_items', $h_booked);
		        $this->set('tech_items', $h_tech);
		        $queryPayment 	= "select payment_image from ace_rp_orders where id='".$order_id."'";
				$resultPayment 	= $db->_execute($queryPayment);
				$rowPayment 	= mysql_fetch_array($resultPayment, MYSQL_ASSOC);
				if($rowPayment){
					$this->set('invoice_image_path', $rowPayment['payment_image']);
				}
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
			}
			else
			{
		        // Retrieve an additional information from the submitted parameters
				$this->data['Order']['job_date'] = $this->params['url']['job_date'];
				$this->data['Order']['job_time_beg'] = $this->params['url']['job_time_beg'];
				$this->data['Order']['job_technician1_id'] = $this->params['url']['job_technician1_id'];
				$this->data['Order']['job_technician2_id'] = $this->params['url']['job_technician2_id'];

				$this->data['Order']['order_status_id'] = 1;

		        // Default sub-status: Not confirmed (1)
		        $this->data['Order']['order_substatus_id'] = 1;

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

		// PREPARE DATA FOR UI
		// Get Associated Options
		$this->set('job_trucks', $this->HtmlAssist->table2array($this->InventoryLocation->findAll(array('type' => '2'), null, null, null, 1, 0), 'id', 'name'));
		$this->set('job_statuses', $this->HtmlAssist->table2array($this->OrderStatus->findAll(), 'id', 'name'));
		$this->set('job_types', $this->HtmlAssist->table2array($this->OrderType->findAll(array("OrderType.flagactive",1)), 'id', 'name'));
		$this->set('call_results', $this->HtmlAssist->table2array($this->CallResult->findAll(), 'id', 'name'));
		$this->set('booking_sources', $this->Lists->BookingSources());
		$this->set('admins', $this->Lists->Admins());
		$this->set('verificators', $this->Lists->Supervisors());
		$this->set('payment_methods', $this->HtmlAssist->table2array($this->Order->PaymentMethod->findAll(), 'id', 'name'));
		$this->set('sub_status', $this->HtmlAssist->table2array($this->Order->OrderSubstatus->findAll(), 'id', 'name'));
		$this->set('allTechnician',$this->Lists->Technicians(true));
		$this->set('txt_customer_note','');

		// Past Order View Mode
		if ($this->data['Status']['name'] == 'Done') $this->set('ViewMode', 1);

		// Find customer's history and notes
		if ($this->data['Customer']['id'])
		{
	        // 1. History
	        //Make Redo Orders List
	        //$redo_orders = $this->_getPreviousJobs($this->data['Customer']['id']);
        if ($this->data['Order']['job_estimate_id'])
        {
            $past_orders = $this->Order->findAll(array('Order.id'=> $this->data['Order']['job_estimate_id']), null, "job_date DESC", null, null, 1);
            foreach ($past_orders as $ord)
                $job_estimate_text = 'REF# '.$ord['Order']['order_number'].' - '.date('d M Y', strtotime($ord['Order']['job_date']));
        }

	        // 2. Customer Notes
	        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
	        $query = "SELECT * FROM ace_rp_users_notes WHERE user_id=".$this->data['Customer']['id']." ORDER BY note_date DESC";
	        $result = $db->_execute($query);
	        while ($row = mysql_fetch_array($result))
	            $customer_notes[$row['id']] = $row;
		}
	    $this->set('past_orders', $past_orders);
	    $this->set('redo_orders', $redo_orders);
		$this->set('customer_notes',$customer_notes);
		$this->set('job_estimate_text',$job_estimate_text);
		$this->set('yesOrNo', $this->Lists->YesOrNo());
		$this->set('feedbackRatings', $this->Lists->FeedbackRatings());

		// Prepare dates for selector
		if ((strlen($this->data['Order']['job_date']) > 0) && ($this->data['Order']['job_date'] != "0000-00-00"))
			$this->data['Order']['job_date'] = date("d M Y", strtotime($this->data['Order']['job_date']));

		$query = "
			SELECT REPLACE(name, ' ', '_') name, internal_id
			FROM ace_rp_cities
		";

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
				LEFT JOIN ace_rp_users u
				ON n.user_id = u.id
				WHERE n.order_id = $order_id
				ORDER BY n.note_date ASC
			";

			$result = $db->_execute($query);
			$i = 0;
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

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$cities_with_id[$row['internal_id']]['name']= $row['name'];
		}

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
		if(!empty($order_id)) {
			$query = "SELECT photo_1, photo_2 FROM ace_rp_orders WHERE id = ".$order_id;
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result)) {
					$this->data['Order']['photo_1'] = $this->getPhotoPath($row['photo_1']);
					$this->data['Order']['photo_2'] = $this->getPhotoPath($row['photo_2']);
			}
		}
	}

	function techCustomerInterest()
	{
		$this->layout='edit';
		if (!empty($this->data['Order']))
		{
			//If order information is submitted - save the order
			$this->saveOrder(0);
		}
		else
		{
			// If no order data is submitted, we'll have one of the following situations:
			// 1. we are being asked to display an existing order's data ($order_id!='')
			// 2. we are being asked to create a new order for an existing customer ($order_id=='', $customer_id!='')
			// 3. we are being asked to create a completely new customer ($order_id=='', $customer_id=='')
			// Check submitted data for any special parameters to be set
			$order_id = $this->params['url']['order_id'];
			$customer_id = $this->params['url']['customer_id'];
		    $num_items = 0;

			// If order ID is submitted, prepare order's data to be displayed
			if ($order_id)
			{
				// Read the order's data from database
				$this->Order->id = $order_id;
				$this->data = $this->Order->read();

		        $h_booked='';
		        $h_tech='';
            $b_actions = false;
            $s_actions = false;
						if (($this->Common->getLoggedUserID()==$this->data['Order']['booking_source_id'])
								||($this->Common->getLoggedUserID()==$this->data['Order']['booking_source2_id']))
                $b_actions = true;
						if (($this->Common->getLoggedUserID()==$this->data['Order']['job_technician1_id'])
								||($this->Common->getLoggedUserID()==$this->data['Order']['job_technician2_id']))
                $s_actions = true;
		        foreach ($this->data['BookingItem'] as $oi)
		        {
		          if ($oi['class']==0)
		          {
		            $h_booked .= '<tr id="order_'.$num_items.'" class="booked">';
		            $h_booked .= $this->_itemHTML($num_items, $oi, $b_actions);
		            $h_booked .= '</tr>';
		          }
		          else
		          {
		            $h_tech .= '<tr id="order_'.$num_items.'" class="extra">';
		            $h_tech .= $this->_itemHTML($num_items, $oi, $s_actions);
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
		          $h_booked .= $this->_itemHTML($num_items, $oi, false);
		          $h_booked .= '</tr>';
		          $num_items++;
		        }
		        $this->set('booked_items', $h_booked);
		        $this->set('tech_items', $h_tech);

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
			}
			else
			{
		        // Retrieve an additional information from the submitted parameters
				$this->data['Order']['job_date'] = $this->params['url']['job_date'];
				$this->data['Order']['job_time_beg'] = $this->params['url']['job_time_beg'];
				$this->data['Order']['job_technician1_id'] = $this->params['url']['job_technician1_id'];
				$this->data['Order']['job_technician2_id'] = $this->params['url']['job_technician2_id'];

				$this->data['Order']['order_status_id'] = 7;

		        // Default sub-status: Not confirmed (1)
		        $this->data['Order']['order_substatus_id'] = 1;

		        
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

		// PREPARE DATA FOR UI
		// Get Associated Options
		$this->set('job_trucks', $this->HtmlAssist->table2array($this->InventoryLocation->findAll(array('type' => '2'), null, null, null, 1, 0), 'id', 'name'));
		$this->set('job_statuses', $this->HtmlAssist->table2array($this->OrderStatus->findAll(), 'id', 'name'));
		$this->set('job_types', $this->HtmlAssist->table2array($this->OrderType->findAll(array("OrderType.flagactive",1)), 'id', 'name'));
		$this->set('call_results', $this->HtmlAssist->table2array($this->CallResult->findAll(), 'id', 'name'));
		$this->set('booking_sources', $this->Lists->BookingSources());
		$this->set('admins', $this->Lists->Admins());
		$this->set('verificators', $this->Lists->Supervisors());
		$this->set('payment_methods', $this->HtmlAssist->table2array($this->Order->PaymentMethod->findAll(), 'id', 'name'));
		$this->set('sub_status', $this->HtmlAssist->table2array($this->Order->OrderSubstatus->findAll(), 'id', 'name'));
		$this->set('allTechnician',$this->Lists->Technicians(true));
		$this->set('txt_customer_note','');
		

		// Past Order View Mode
		if ($this->data['Status']['name'] == 'Done') $this->set('ViewMode', 1);

		// Find customer's history and notes
		if ($this->data['Customer']['id'])
		{
	        // 1. History
	        //Make Redo Orders List
	        //$redo_orders = $this->_getPreviousJobs($this->data['Customer']['id']);
        if ($this->data['Order']['job_estimate_id'])
        {
            $past_orders = $this->Order->findAll(array('Order.id'=> $this->data['Order']['job_estimate_id']), null, "job_date DESC", null, null, 1);
            foreach ($past_orders as $ord)
                $job_estimate_text = 'REF# '.$ord['Order']['order_number'].' - '.date('d M Y', strtotime($ord['Order']['job_date']));
        }

	        // 2. Customer Notes
	        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
	        $query = "SELECT * FROM ace_rp_users_notes WHERE user_id=".$this->data['Customer']['id']." ORDER BY note_date DESC";
	        $result = $db->_execute($query);
	        while ($row = mysql_fetch_array($result))
	            $customer_notes[$row['id']] = $row;
		}
	    $this->set('past_orders', $past_orders);
	    $this->set('redo_orders', $redo_orders);
		$this->set('customer_notes',$customer_notes);
		$this->set('job_estimate_text',$job_estimate_text);

		// Prepare dates for selector
		if ((strlen($this->data['Order']['job_date']) > 0) && ($this->data['Order']['job_date'] != "0000-00-00"))
			$this->data['Order']['job_date'] = date("d M Y", strtotime($this->data['Order']['job_date']));
	}

	// Method renders last call history to jobform
	function getLastCallHistory(){
		$customer_id = $_GET['customer_id'];
		$phone = $_GET['phone'];
		if ((!$customer_id)&&(!$phone)) exit;

		$phone = preg_replace("/[- \.]/", "", $phone);

		//Telemarketers will not see 'Answering Machine' results
		$ans = '';
		if (($this->Common->getLoggedUserRoleID() == 3)
			||($this->Common->getLoggedUserRoleID() == 13)
			||($this->Common->getLoggedUserRoleID() == 9))
			$ans = 'call_result_id!=6 and';

		$users=$this->Lists->BookingSources();
		$call_results=$this->Lists->ListTable('ace_rp_call_results');

		if ($customer_id) $query = "select * from ace_rp_call_history where ".$ans." customer_id='".$customer_id."'";
//		else if ($phone) $query = "select * from ace_rp_call_history where ".$ans." customer_id in (select id from ace_rp_users where phone='".$phone."')";
		else if ($phone) $query = "select * from ace_rp_call_history where ".$ans." phone='".$phone."'";
 
		$query .= " order by call_date desc, call_time desc";

		$r = 1;
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$result = $db->_execute($query);
		$loopstart=1;
		$cb_date = "";
		while ($row = mysql_fetch_array($result,MYSQL_ASSOC))
		{	
			if(!empty($row['call_result_id'])){
				$but_value = $call_results[$row['call_result_id']];	
			}else{
				$but_value = 'Call Back';
			}
			
			$call_sttaus = $call_results[$row['call_result_id']];
			$cb_date = date('d-m-Y',strtotime($row['callback_date'])); 
			break;
		}

		$html = "";

		if($call_sttaus=='Do Not Call'){
			$html = '<input type="button" value="DND" disabled > <a href="javascript:void(0)" onclick="openTab(3);">See Call History</a>';
		}else{
			$html = '<input type="button" value="'.$but_value.'" onclick="openTab(3);">  '.$cb_date;
		}
		echo $html;
		exit;
	}

    // Method renders call history into the table
    function getCallHistory()
    {   
        session_write_close(); // It added to remove delay in ajax response
        $customer_id = $_GET['customer_id'];
        $phone = $_GET['phone'];
        $isDialer = $_GET['is_dialer'];
        if ((!$customer_id)&&(!$phone)) exit;

            $phone = preg_replace("/[- \.]/", "", $phone);

        //Telemarketers will not see 'Answering Machine' results
        $ans = '';
        if (($this->Common->getLoggedUserRoleID() == 13)
         	||($this->Common->getLoggedUserRoleID() == 9))
            $ans = 'call_result_id!=6 and';

	    $users=$this->Lists->BookingSources();
	    $call_results=$this->Lists->ListTable('ace_rp_call_results');

        echo "<script>";
        echo "function DeleteCallRecord(rec_id, cust_id){";
        echo "$.get('".BASE_URL."/orders/deleteCallRecord', {record_id:rec_id}, function(data){";
				echo "$.get('".BASE_URL."/orders/getCallHistory', {phone:".$phone."}, function(data){";
				echo "showhist=1;$('#CallHistory').html(data);});});";
        echo "}";
        echo "</script>";
        echo "<table style='background-color:white'>";
        echo "<tr class='results'><th colspan=3>Call</th><th rowspan=2>Note</th><th colspan=2>Callback</th><th rowspan=2>Action</th>";
		echo "</tr><tr class='results'><th>Date</th><th>User</th><th>Result</th><th>Date</th><th>User</th></tr>";

		if ($customer_id) $query = "select * from ace_rp_call_history where ".$ans." customer_id='".$customer_id."'";
//		else if ($phone) $query = "select * from ace_rp_call_history where ".$ans." customer_id in (select id from ace_rp_users where phone='".$phone."')";
		else if ($phone) $query = "select * from ace_rp_call_history where ".$ans." phone='".$phone."'";

 		if($isDialer == 1)
 		{
 			$query .= " order by call_date desc, call_time desc limit 1";
 		} else {
 			$query .= " order by call_date desc, call_time desc";
 		}
        
		$r = 1;
        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
        $result = $db->_execute($query);
        $loopstart=1;
				while ($row = mysql_fetch_array($result,MYSQL_ASSOC))
		{
			if($loopstart ==1)$class='';else $class="showcallhistory";
			
            echo "<tr id='" .$row['id'] ."' class='" ."cell".(++$r%2) ." ".$class."'>";
            echo "<td>" .date('d-m-Y',strtotime($row['call_date'])) ."</td>";
            echo "<td>" .$users[$row['call_user_id']] ."</td>";
            echo "<td>" .$call_results[$row['call_result_id']] ."</td>";

            $action = 'return false;';

            $cb_note = '';
            $cb_date = '';
            if ((($this->Common->getLoggedUserRoleID() != 3)
         	&& ($this->Common->getLoggedUserRoleID() != 9))
         	|| (1*$this->Common->getLoggedUserID() == 1*$row['call_user_id']))
	        {
	        	$cb_note = $row['call_note'];
	          $cb_date = date('d-m-Y',strtotime($row['callback_date']));
            if (($row['dialer_id']!='web')&&($row['dialer_id']!=''))
                $action = "alert('This call was made from DIALER and can not be removed.');";
            else
                $action = "DeleteCallRecord(" .$row['id'] ."," .$row['customer_id'] .");";
	        }

            echo "<td><div style='width:150px'>" .$cb_note ."</div></td>";
            echo "<td>" .$cb_date ."</td>";
            echo "<td>" .$users[$row['callback_user_id']] ."</td>";
			echo "<td></td>";
            //echo "<td><img src='" .ROOT_URL . "/app/webroot/img/icon-vsm-delete.png' onclick=\"" .$action ."\"></td>";
            echo "</tr>";
            $loopstart++;
			}
		echo "</table>";
        exit;
    }

    // Method deletes specified call record
    function deleteCallRecord()
    {
        $rec_id = $_GET['record_id'];
        $this->CallRecord->del($rec_id);
        echo 'Record deleted';
        exit;
    }

    //AJAX. Method shows the list of previous orders with a posibility to choose one
	  function getPreviousJobs()
    {  
        $customer_id = $_GET['customer_id'];
        $phone = $_GET['phone'];
        $phone = preg_replace("/[- \.]/", "", $phone);

        $txt_head = 'style="border-bottom:1px solid black;border-right:1px solid black;background-color:#999999"';
        $txt = 'style="border-bottom:1px solid black;border-right:1px solid black;"';

        $h = '
			<style type="text/css" media="all">
		   @import "'.ROOT_URL.'/app/webroot/css/style.css";
			</style>
			<script language="JavaScript" src="'.ROOT_URL.'/app/webroot/js/jquery.js"></script>
      <script language="JavaScript">
				function addItem(item_id,item_txt,item_tech1,item_tech2){
				  var new_item=new Array();
					new_item[0]=item_id;
					new_item[1]=item_tech1;
					new_item[2]=item_tech2;
					new_item[3]=item_txt;
					window.returnValue=new_item;
					window.close();
				}
				function highlightCurRow(element){
					$(".item_row").css("background","");
					$("#"+element).css("background","#a9f5fe");
				}
      </script>
      <div class="pricesubpage">
      <h3>Choose a previous job</h3>
      <table style="booked" cellspacing=0 colspacing=0>
      <tr>
        <th '.$txt_head.' width=100px>Date</th>
        <th '.$txt_head.' width=60px>REF #</th>
        <th '.$txt_head.' style="border-bottom:1px solid black" width=100px>Tech 1</th>
        <th '.$txt_head.' width=100px>Tech 2</th>
      </tr>';

        //Make Redo Orders List
        if ($customer_id)
            $past_orders = $this->Order->findAll(array('Order.customer_id'=> $customer_id), null, "job_date DESC", null, null, 1);
        else
            $past_orders = $this->Order->findAll(array('Customer.phone'=> $phone), null, "Order.job_date DESC", null, null, 1);

        foreach ($past_orders as $ord)
        {
          $tech1 = $ord['Technician1']['first_name'];
          if (!$tech1) $tech1 = $ord['Technician1']['last_name'];

          $tech2 = $ord['Technician2']['first_name'];
          if (!$tech2) $tech2 = $ord['Technician2']['last_name'];

				  $h .= '<tr class="item_row" id="item_'.$ord['Order']['id'].'" style="cursor:pointer;" onclick="addItem('.$ord['Order']['id'].',\'REF# '.$ord['Order']['order_number'].' - '.date('d M Y', strtotime($ord['Order']['job_date'])).'\',\''.$ord['Order']['job_technician1_id'].'\',\''.$ord['Order']['job_technician2_id'].'\')" onMouseOver="highlightCurRow(\'item_'.$ord['Order']['id'].'\')">';
					$h .= '<td '.$txt.'>&nbsp;'.date('d M Y', strtotime($ord['Order']['job_date'])).'</td>';
					$h .= '<td '.$txt.'>&nbsp;'.$ord['Order']['order_number'].'</td>';
					$h .= '<td '.$txt.'>&nbsp;'.$tech1.'</td>';
					$h .= '<td '.$txt.'>&nbsp;'.$tech2.'</td>';
					$h .= '</tr>';
        }
				$h .= '</table>
        </div>';

        echo $h;
        exit;
    }

    //AJAX. Method returns the last order done for the customer
	  function getLastJob()
    {
        $customer_id = $_GET['customer_id'];
        $phone = $_GET['phone'];
        $phone = preg_replace("/[- \.]/", "", $phone);

        //Make Redo Orders List
        if ($customer_id)
            $past_orders = $this->Order->findAll(array('Order.customer_id'=> $customer_id), null, "job_date DESC", null, null, 1);
        else
            $past_orders = $this->Order->findAll(array('Customer.phone'=> $phone), null, "Order.job_date DESC", null, null, 1);

        $ord = $past_orders[0];

				$aRet = array();
				$aRet['job_id'] = $ord['Order']['id'];
				$aRet['source1_id'] = $ord['Order']['job_technician1_id'];
				$aRet['source2_id'] = $ord['Order']['job_technician2_id'];
				$aRet['job_text'] = "REF# {$ord['Order']['order_number']} - ".date('d M Y', strtotime($ord['Order']['job_date']));

        echo json_encode($aRet);
        exit;
    }

    // Method renders the changes history into the table
    function getChangesHistory()
    {
        $customer_id = $_GET['customer_id'];
        $order_id = $_GET['order_id'];
 		echo "<table style='background-color:white'>";
		echo "<tr class='results'><th width=80px>Date</th><th width=60px>Time</th><th width=60px>User</th><th width=380px>Operation</th></tr>";

		$sql =  "select l.change_date, l.change_time, u.id,
							concat(u.first_name,' ',u.last_name) name,
							'changed' operation
					from ace_rp_customers_log l, ace_rp_users u
					where l.change_user_id=u.id and l.id='" .$customer_id ."'
					group by l.change_date, l.change_time, u.id, concat(u.first_name,' ',u.last_name)
				union
				select l.change_date, l.change_time, u.id,
						concat(u.first_name,' ',u.last_name) name,
						'changed' operation
					from ace_rp_orders_log l, ace_rp_users u
					where l.change_user_id=u.id and l.id='" .$order_id ."'
					group by l.change_date, l.change_time, u.id, concat(u.first_name,' ',u.last_name)
					order by 1 desc,2 desc";

		$r = 1;
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$result = $db->_execute($sql);
		while ($row = mysql_fetch_array($result))
		{
            echo "<tr class='" ."cell".(++$r%2) ."' style='height:25px'";
//            if ($row['operation']=='client changed')
            echo "onclick='ShowChangeDetails(\"CH_DT_".$r."\",".$customer_id.",\"".$order_id."\",".$row['id'].",\"".$row['change_date']."\",\"".$row['change_time']."\")'>";
//            elseif ($row['operation']=='job changed')
//                  echo "onclick='ShowJobChangeDetails(\"CH_DT_".$r."\",".$order_id.",".$row['id'].",\"".$row['change_date']."\",\"".$row['change_time']."\")'>";
            echo "<td align=center>" .date('d-m-Y',strtotime($row['change_date'])) ."</td>";
            echo "<td align=center>" .date('H:i', strtotime($row['change_time'])) ."</td>";
            echo "<td>" .$row['name'] ."</td>";
            echo "<td>" .$row['operation'] ."</td>";
            echo "</tr>";
            echo "<tr class='" ."cell".($r%2) ."'><td style='display:none' class='change_details' colspan=4 id='CH_DT_".$r."'></td></tr>";
		}
		echo "</table>";
        exit;
    }

    // Method renders the current changes details into the table
    function getChangesDetails()
    {
        $customer_id = $_GET['customer_id'];
        $order_id = $_GET['order_id'];
        $change_date = $_GET['change_date'];
        $change_time = $_GET['change_time'];
        $change_user_id = $_GET['change_user_id'];

		echo "<table style='background-color:white'>";
		echo "<tr class='results'><th width=80px>Field</th><th width=150px>Was</th><th width=150px>Is</th></tr>";

		$sql =  "select * from ace_rp_customers_log
                  where id='" .$customer_id ."'
                    and change_user_id='".$change_user_id."'
                    and change_date='".$change_date."'
                    and change_time='".$change_time."'";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$result = $db->_execute($sql);
		$prev_row = mysql_fetch_array($result, MYSQL_ASSOC);

    if (!empty($prev_row))
    {
        $sql =  "select * from ace_rp_customers_log
                      where id='" .$customer_id ."'
                        and change_user_id='".$change_user_id."'
                        and (change_date>'".$change_date."'
                             or (change_date='".$change_date."'
                               and change_time>'".$change_time."'))
                order by change_date asc, change_time asc limit 1";

        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
        $result = $db->_execute($sql);
        if (!$row = mysql_fetch_array($result, MYSQL_ASSOC))
        {
            $sql =  "select * from ace_rp_customers where id='" .$customer_id ."'";
            $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
            $result = $db->_execute($sql);
            $row = mysql_fetch_array($result, MYSQL_ASSOC);
        }

        foreach ($row as $f_nam => $f_val)
        {
            if (($f_nam!='role_id')&&($f_nam!='change_user_id')&&($f_nam!='change_date')
              &&($f_nam!='change_time')&&($f_nam!='opercode')&&($f_nam!='created')&&($f_nam!='modified'))
                if ($prev_row[$f_nam]!=$f_val)
                {
                echo "<tr class='cell0'>";
                echo "<td>" .$f_nam ."</td>";
                echo "<td>" .$prev_row[$f_nam] ."</td>";
                echo "<td>" .$f_val ."</td>";
                echo "</tr>";
                }
        }
    }

		$sql =  "select * from ace_rp_orders_log
                  where id='" .$order_id ."'
                    and change_user_id='".$change_user_id."'
                    and change_date='".$change_date."'
                    and change_time='".$change_time."'";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$result = $db->_execute($sql);
		$prev_row = mysql_fetch_array($result, MYSQL_ASSOC);

    if (!empty($prev_row))
    {
        $sql =  "select * from ace_rp_orders_log
                      where id='" .$order_id ."'
                        and change_user_id='".$change_user_id."'
                        and (change_date>'".$change_date."'
                             or (change_date='".$change_date."'
                               and change_time>'".$change_time."'))
                order by change_date asc, change_time asc limit 1";

        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
        $result = $db->_execute($sql);
        if (!$row = mysql_fetch_array($result, MYSQL_ASSOC))
        {
            $sql =  "select * from ace_rp_orders where id='" .$order_id ."'";
            $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
            $result = $db->_execute($sql);
            $row = mysql_fetch_array($result, MYSQL_ASSOC);
        }

        foreach ($row as $f_nam => $f_val)
        {
            if (($f_nam!='created')&&($f_nam!='modified')&&($f_nam!='created_by')
              &&($f_nam!='created_date')&&($f_nam!='modified_by')&&($f_nam!='modified_date')
              &&($f_nam!='change_user_id')&&($f_nam!='change_date')
              &&($f_nam!='change_time')&&($f_nam!='opercode'))
                if ($prev_row[$f_nam]!=$f_val)
                {
                echo "<tr class='cell0'>";
                echo "<td>" .$f_nam ."</td>";
                echo "<td>" .$prev_row[$f_nam] ."</td>";
                echo "<td>" .$f_val ."</td>";
                echo "</tr>";
                }
        }
    }
		echo "</table>";
        exit;
    }

	//Finds all future-dated orders that are of the same zone as the supplied postal code
	function ordersByArea()
	{
		$layout = 'inline';

		if ($this->params['url']['postal_code'] != '')
		{
			//Find Zone Number
			$zones = $this->Zone->findAll(array('postal_code' => substr($this->params['url']['postal_code'], 0, 3)));

			if (count($zones))
			{
				//Store the current zone data
				$this->set('cur_zone_name', $zones[0]['Zone']['zone_name']);
				$this->set('cur_zone_postal_code', $zones[0]['Zone']['postal_code']);
				$this->set('cur_zone_city', $zones[0]['Zone']['city']);

				//Find all Zone elements (postal codes) for the same number
				$zones = $this->Zone->findAll(array('zone_num' => $zones[0]['Zone']['zone_num']));
			}
		}

		for ($i = 0; $i < count($zones); $i++)
		{
			if ($query != "")
				$query .= " OR ";

			$query .=  "Customer.postal_code LIKE '" . $zones[$i]['Zone']['postal_code'] . "%'";
		}

		if ($query)
			$query = "(".$query.")";

		if (isset($this->params['url']['customer_id']))
			$query .= ' AND ace_rp_customers.id != '.$this->params['url']['customer_id'];


		if( $query != '' ) {
			if ($query)
				$query .= " AND ";
			$query .= "(Order.job_date >= '".date('Y-m-d')."')";
			//print $query;
			$this->data['Zone'] = $zones;
			$this->data['Order'] = $this->Order->findAll($query,null,"Order.job_date DESC,Order.job_timeslot_id,Order.job_truck");	//, null, null, null, 1, 2);
			$this->set('postal_code', $this->params['url']['postal_code']);

			$withoutTimeslot = array();
			foreach( $this->data['Order'] as $o){
				if( $o['Timeslot']['id']  && $o['Order']['job_truck'] ) {
					$withoutTimeslot[$o['Order']['job_truck']] = $o['Timeslot']['id'];
				}
			}
			//pr($withoutTimeslot);die();

			$this->set('timeslots', $this->HtmlAssist->table2array($this->Timeslot->findAll(), 'id', 'name'));
		}
	}

	// Method performs a look up for the customer by given phone number.
	// Is called from the 'edit_booking' page, when a user enters the phone number.
	// Returns an array of all found clients in JSON.
	function phoneLookup(){
    $phone = $_GET['phone'];
    $phone = preg_replace("/[- \.]/", "", $phone);

		$sql = "SELECT *
              FROM ace_rp_customers u
					   WHERE u.phone like '%" .$phone ."%'";

    $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$result = $db->_execute($sql);
    $index = 1;
		while ($row = mysql_fetch_array($result))
		{
        $res[$index]['id'] = $row['id'];
        $res[$index]['first_name'] = $row['first_name'];
        $res[$index]['last_name'] = $row['last_name'];
        $res[$index]['phone'] = $row['phone'];
        $res[$index]['cell_phone'] = $row['cell_phone'];
        $res[$index]['city'] = $row['city'];
        $res[$index]['postal_code'] = $row['postal_code'];
        $res[$index]['email'] = $row['email'];
        $res[$index]['address'] = $row['address'];
		}

		print json_encode($res);
    exit;
  }

	// Method performs a look up for the supplier's info by given id.
	// Returns found data as an array in JSON.
	function getSupplierInfo()
	{
		$id = $_GET['id'];
		$res = $this->Lists->GetTableRow('ace_rp_suppliers','id='.$id);
			print json_encode($res);
		exit;
	}

  	function confirmations() {
		//Convert date from date picker to SQL format
		if ($this->params['url']['ffromdate'] != '')
			$this->params['url']['ffromdate'] = date("Y-m-d", strtotime($this->params['url']['ffromdate']));

		//Pick today's date if no date
		$fdate = ($this->params['url']['ffromdate'] != '' ? $this->params['url']['ffromdate']: date("Y-m-d") ) ;
		$weekday = date('w',strtotime($fdate));

		$sql = "
			SELECT o.id, o.order_number, u.id user_id, u.first_name, u.last_name
				,o.booking_date, u2.first_name booking_telemarketer_id, o.job_date
				,u3.first_name verified_by_id, o.verified_date, u.city
				,os.name substatus, ot.name order_type, o.order_status_id
			FROM ace_rp_customers u
			LEFT JOIN ace_rp_orders o
			ON u.id = o.customer_id
			LEFT JOIN ace_rp_order_substatuses os
			ON o.order_substatus_id = os.id
			LEFT JOIN ace_rp_order_types ot
			ON o.order_type_id = ot.id
			LEFT JOIN ace_rp_users u2
			ON o.booking_telemarketer_id = u2.id
			LEFT JOIN ace_rp_users u3
			ON o.verified_by_id = u3.id
			WHERE o.order_number IS NOT NULL
			AND o.booking_date = '".$this->Common->getMysqlDate($fdate)."'
			ORDER BY o.verified_by_id, u.city, o.job_date
		";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$result = $db->_execute($sql);

		$bookings = 0;
		$notConfirmed = 0;
		$confirmed = 0;
		$changed = 0;
		$answeringMachine = 0;
		$cancelled = 0;
		$done = 0;

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)

			$cust_temp[$row['id']]['id']= $row['id'];
			$cust_temp[$row['id']]['order_number']= $row['order_number'];
			$cust_temp[$row['id']]['user_id']= $row['user_id'];
			$cust_temp[$row['id']]['first_name']= $row['first_name'];
			$cust_temp[$row['id']]['last_name']= $row['last_name'];
			$cust_temp[$row['id']]['booking_date']= $row['booking_date'];
			$cust_temp[$row['id']]['booking_telemarketer_id']= $row['booking_telemarketer_id'];
			$cust_temp[$row['id']]['job_date']= $row['job_date'];
			$cust_temp[$row['id']]['verified_by_id']= $row['verified_by_id'];
			$cust_temp[$row['id']]['verified_date']= $row['verified_date']==''?'':date("d M Y h:m:s", strtotime($row['verified_date']));
			$cust_temp[$row['id']]['city']= $row['city'];
			$cust_temp[$row['id']]['substatus']= $row['substatus'];
			$cust_temp[$row['id']]['order_type']= $row['order_type'];

			//add statistics
			$bookings++;
			if($row['substatus'] == 'Not Confirmed') $notConfirmed++;
			if($row['substatus'] == 'Answering Machine') $answeringMachine++;
			if($row['substatus'] == 'Confirmed') $confirmed++;
			if($row['substatus'] == 'Changed') $changed++;

		}

		$this->set('customers', $cust_temp);
		$this->set('fdate', date("d M Y", strtotime($fdate)));
		$this->set('ydate', date("d M Y", strtotime($fdate) - 24*60*60));
		$this->set('tdate', date("d M Y", strtotime($fdate) + 24*60*60));
		$this->set('bookings', $bookings);
		$this->set('notConfirmed', $notConfirmed);
		$this->set('answeringMachine', $answeringMachine);
		$this->set('confirmed', $confirmed);
		$this->set('changed', $changed);
	}

	function uploadPhoto($order_id, $i)
	{
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
		$path = $_FILES['image']['name'];
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		$name = date('Ymdhis', time()).$order_id.'.'.$ext;

		if ( 0 < $_FILES['image']['error'] ) {
	        // echo 'Error: ' . $_FILES['image']['error'] . '<br>'; 
	    } else {
	        move_uploaded_file($_FILES['image']['tmp_name'], 'upload_photos/'.$day.'/'.$name);
	    }

		$sql = "UPDATE ace_rp_orders SET photo_".$i." = '".$name."' WHERE id = ".$order_id;
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$result = $db->_execute($sql);

		$data = ROOT_URL.'/upload_photos/'.$day.'/'.$name;
		echo $data;

		$this->autoRender = false;
	}

	function searchList()
	{
		// error_reporting(E_ALL);
		$conditions = array();
		$is_campaing = 0;
		$campaignId = 0;
		if ($_GET['sq_crit'] == 'phone')
		{
			$sq_str = preg_replace("/[- \.]/", "", $_GET['sq_str']);
			$sq_str = preg_replace("/([?])*/", "[-]*", $sq_str);
			//$conditions[$_GET['sq_crit']] = "REGEX '".$sq_str."'";
			$conditions['phone REGEXP '] = $sq_str;
		}
		else
			$conditions[$_GET['sq_crit']] = "LIKE %".$_GET['sq_str']."%";


		if (($_GET['limit']=="undefined") || ($_GET['limit'] == ""))
			$limit = "0,100";
		else
			$limit = $_GET['limit'];

		if (($_GET['add']=="undefined") || ($_GET['add'] == ""))
			$add = 0;
		else
			$add = $_GET['add'];

		$sort = null;
		$callback_search_head = "";

		if ($_GET['sq_crit'] == 'phone')
		{
			if($this->Common->getLoggedUserRoleID() != 6) {
				$allCampList = $this->Lists->AgentAllCampaingList($_SESSION['user']['id']);
				$arrayString = implode(',', $allCampList);
				$telem_clause = " AND c.campaign_id IN ('".$arrayString."') AND EXISTS(SELECT * FROM ace_rp_orders WHERE customer_id = c.id AND order_status_id IN(1,3,5))";
			}
			$sql = "SELECT c.id, c.card_number, `c`.`first_name`, c.last_name,
						c.postal_code, c.email, c.address_unit, c.address_street_number, c.address_street, c.city,
						c.phone, c.cell_phone, c.created, c.modified,
						c.telemarketer_id, '' callback_note, c.callresult,
						c.callback_date, CAST(c.callback_time AS TIME) callback_time,
						c.lastcall_date, u.first_name as telemarketer_first_name, u.last_name as telemarketer_last_name
					FROM ace_rp_customers as c
						left join ace_rp_users u on u.id=c.telemarketer_id
					WHERE (c.phone REGEXP '".$sq_str."'
						OR
						c.cell_phone REGEXP '".$sq_str."')
					$telem_clause
					LIMIT ".$limit;
			$cust = $this->User->query($sql);
		}
		else if ($_GET['sq_crit'] == 'Campaign_data')
		{
			$sortBy = $_GET['sortBy'];
			$sortType = $_GET['sortType'];
			$seletedStr = $_GET['seletedStr'];
			$fromDate = $_GET['fromDate'];
			$toDate = $_GET['toDate'];
			$isCampaing = 1;
			$callResult = $_GET['is_call_result'];
			$is_search = $_GET['is_search'];
			$this->set('is_search', $is_search);
			$this->set('is_campaing', $isCampaing);
			$data = explode('-', $_GET['sq_str']);
			if($_GET['sq_str'] == 0)
			{
				$this->set('campId', 0);
				if($this->Common->getLoggedUserRoleID() == 6) 
				{
					$allCampList = $this->Lists->allCampaingList();
					$arrayString = implode(',', $allCampList);
					// $telem_clause = ' AND o.booking_source_id='.$this->Common->getLoggedUserID();
					$telem_clause = ' AND c.campaign_id IN ('.$arrayString.')';
				}
			}
			else{
				if($_GET['sq_str'] != ''){
					$id = $_GET['sq_str'];
 					
					$this->set('campId', $id);
					$callWhere = ' AND ec.last_inserted_id ='.$id.''; 
				}	
			}
			if(!empty($callResult))
			{
				// $callWhere .= " AND ec.show_default > 0";
				if($callResult == 1) 
				{
					$callWhere .= " AND u2.callresult IN (1,2)";
				} else if($callResult == 2) {
					$callWhere .= " AND u2.callresult IN (6,4,8,9,11)";
				}
			} else {
				$callWhere .= " AND ec.show_default = 0";
			}
			if(!empty($seletedStr)) 
			{
				if($seletedStr == 'today')
				{
					$callWhere .= " AND u2.callback_date = CURDATE()"; 
				} else if ($seletedStr == 'missed-call-date') {
					 $fdate1 = date_create($fromDate);
				   $fromDate = date_format($fdate1,"Y-m-d");
				   $fdate2 = date_create($toDate);
				   $toDate =  date_format($fdate2,"Y-m-d");
					$callWhere .= " AND u2.callback_date BETWEEN'".$fromDate."' AND '". $toDate."'";
				} else {
					$callWhere .= '';
				}
			}
			$orderBy ='';
			if(!empty($sortBy))
			{
				$this->set('sort_by', $sortBy);

				if($sortBy == "call-result")
				{
					$orderBy = " ORDER BY u2.callresult ".$sortType;
				} else if($sortBy == "call-back") {
					$orderBy = " ORDER BY u2.callback_date ".$sortType;
				} else {
					$orderBy = " ORDER BY u2.lastcall_date ".$sortType;
				}
			}
			if(!empty($sortType))
			{
				if($sortType == 'desc')
				{
					$this->set('sortTypeImg', 'v');
				} else {
					$this->set('sortTypeImg', '^');
				}
			}
			$callWhere .= " AND u2.callresult NOT IN (7, 3)";
			$countSql = "SELECT count(DISTINCT u2.id) as total FROM ace_rp_reference_campaigns o LEFT JOIN ace_rp_all_campaigns ec ON o.id = ec.last_inserted_id LEFT JOIN ace_rp_customers u2 ON ec.call_history_ids = u2.id LEFT JOIN ace_rp_reminder_email_log rel ON rel.customer_id = ec.call_history_ids WHERE u2.campaign_id IS NOT NULL ".$callWhere;
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
					$result = $db->_execute($countSql);

			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$totalCus = $row ['total']; 
			$this->set('totalCus', $totalCus);
			$totalPages = ceil($totalCus / 500);
			$this->set('totalPages', $totalPages);
			$sql = "SELECT o . * , ec . * , u2 . * , u2.id AS uid, (SELECT delivery_status FROM ace_rp_reminder_email_log rel WHERE customer_id = uid ORDER BY id DESC 
				LIMIT 0 , 1) AS is_sent, ord.reminder_date FROM ace_rp_reference_campaigns o LEFT JOIN ace_rp_all_campaigns ec ON o.id = ec.last_inserted_id LEFT JOIN ace_rp_customers u2 ON ec.call_history_ids = u2.id LEFT JOIN ace_rp_reminder_email_log rel ON rel.customer_id = ec.call_history_ids INNER JOIN ace_rp_orders ord ON ord.customer_id = u2.id WHERE u2.campaign_id IS NOT NULL ".$callWhere.$orderBy." group by uid limit ".$limit;
					$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
					$result = $db->_execute($sql);

					while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
					{
						foreach ($row as $k => $v)
						$cust_temp['User'][$k] = $v;

						$cust_temp['User']['telemarketer_id']= $row['telemarketer_id'];
						$cust_temp['User']['callback_time']= date("H:i", strtotime($row['callback_time']));
						/*$cust_temp['Order']['job_date']= $row['job_date'];
						$cust_temp['Order']['id']= $row['order_id'];*/

						$cust[$row['id']] = $cust_temp;
					}
			}

		else if ($_GET['sq_crit'] == 'REF')
		{
			if($this->Common->getLoggedUserRoleID() != 6) {
				$allCampList = $this->Lists->AgentAllCampaingList($_SESSION['user']['id']);
				$arrayString = implode(',', $allCampList);
				// $telem_clause = ' AND o.booking_source_id='.$this->Common->getLoggedUserID();
				$telem_clause = ' AND c.campaign_id IN ('.$arrayString.')';
			}

			$sql = "SELECT o.id order_id, o.job_date,
						c.id, c.card_number, c.`first_name`, c.last_name,
						c.postal_code, c.email, c.address_unit, c.address_street_number, c.address_street, c.city,
						c.phone, c.cell_phone, c.created, c.modified,
						c.telemarketer_id, '' callback_note,c.callresult,
						c.callback_date, CAST(c.callback_time AS TIME) callback_time,
						c.lastcall_date, u.first_name as telemarketer_first_name, u.last_name as telemarketer_last_name
					FROM ace_rp_customers as c
						join ace_rp_orders o on c.id=o.customer_id
						left join ace_rp_users u on u.id=c.telemarketer_id
					WHERE o.order_number='".$_GET['sq_str']."'
						$telem_clause
			 ";
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$result = $db->_execute($sql);
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				foreach ($row as $k => $v)
					$cust_temp['User'][$k] = $v;

				$cust_temp['User']['telemarketer_id']= $row['call_user_id'];
				$cust_temp['User']['callback_time']= date("H:i", strtotime($row['callback_time']));
				$cust_temp['Order']['job_date']= $row['job_date'];
				$cust_temp['Order']['id']= $row['order_id'];

				$cust[$row['id']] = $cust_temp;
			}
		}
		else if($_GET['sq_crit'] == 'callback_date_dropdown')
		{
			$telem_clause = '';
			$telem_clause1 = '';
			$callback_search_head = 'yes';
			$today_date = date("Y-m-d");
			
			if ($_GET['sq_str'] == 'all_missed_callback' || $_GET['sq_str'] == 'btw_missed_callback'){

				if($this->Common->getLoggedUserRoleID() != 6) {
					$telem_clause = ' AND h.callback_user_id='.$this->Common->getLoggedUserID();
					$telem_clause1 = ' AND y.call_user_id='.$this->Common->getLoggedUserID();
				}

				if($_GET['sq_str'] == 'all_missed_callback'){
					$today_date = date("Y-m-d");
					$total_dates = " AND h.callback_date < $today_date ";
				}
				else{
					$dates = explode('_', $_GET['sq_dates']);
					$today_date = date("Y-m-d");

					if($dates[0] == '' && $dates[1] == ''){
						$total_dates = " AND h.callback_date < $today_date ";
					}
					elseif ($dates[0] != '' && $dates[1] == '') {
						$date1 = strtotime($dates[0]); 
	        			$fdates = date("Y-m-d", $date1); 
						$total_dates = " AND h.callback_date BETWEEN $fdates AND $today_date ";
					}
					elseif ($dates[0] == '' && $dates[1] != '') {
						$date2 = strtotime($dates[1]); 
	        			$tdates = date("Y-m-d", $date2); 
						$total_dates = " AND h.callback_date < $tdates ";
					}
					else{
						$date1 = strtotime($dates[0]); 
	        			$fdates = date("Y-m-d", $date1); 
	        			$date2 = strtotime($dates[1]); 
	        			$tdates = date("Y-m-d", $date2); 
						$total_dates = " AND h.callback_date BETWEEN $fdates AND $tdates ";
					}
				}

				$sql = "SELECT distinct
						c.id, c.card_number, c.first_name, c.last_name,
						c.postal_code, c.email, c.address_unit, c.address_street_number, c.address_street, c.city,
						c.phone, c.cell_phone,c.selected_customer_from_search as customer_row_color,c.selected_customer_from_search_agent as customer_row_color_agent,
						h.call_user_id, h.call_note, h.call_result_id, arc.order_type_id as job_type,arc.job_date as last_job_done_date,
						h.callback_date, h.callback_time,
						if((h.callback_date=current_date())&&(TIME_TO_SEC(CAST(now() AS TIME))>= TIME_TO_SEC(CAST(h.callback_time AS TIME))-300),1,0) reminder_flag,
						h.call_date, u.first_name as telemarketer_first_name, u.last_name as telemarketer_last_name
					FROM ace_rp_customers AS c
						join ace_rp_call_history h
						left join ace_rp_users u on u.id=h.call_user_id
						left join ace_rp_orders arc on c.id = arc.customer_id
					WHERE
						c.id=h.customer_id and
						(h.call_result_id in (0,1,2,4,8,9)) 
						".$total_dates."
						AND h.call_date <= h.callback_date
						".$telem_clause."
						and not exists
							(select * from ace_rp_call_history y
								where y.customer_id=h.customer_id ".$telem_clause1."
									and (y.call_date>h.call_date
									or y.call_date=h.call_date and y.call_time>h.call_time))
					order by reminder_flag desc, h.callback_date, h.callback_time asc
					LIMIT ".$limit;
			}
			else
			{
				if($this->Common->getLoggedUserRoleID() != 6) {
					$telem_clause = ' AND h.callback_user_id='.$this->Common->getLoggedUserID();
					$telem_clause1 = ' AND y.call_user_id='.$this->Common->getLoggedUserID();
				}

				$sent_date = $_GET['sq_str'];

				$sql = "SELECT distinct
						c.id, c.card_number, c.first_name, c.last_name,
						c.postal_code, c.email, c.address_unit, c.address_street_number, c.address_street, c.city,
						c.phone, c.cell_phone,c.selected_customer_from_search as customer_row_color,c.selected_customer_from_search_agent as customer_row_color_agent,
						h.call_user_id, h.call_note, h.call_result_id, arc.order_type_id as job_type,arc.job_date as last_job_done_date,
						h.callback_date, h.callback_time,
						if((h.callback_date=current_date())&&(TIME_TO_SEC(CAST(now() AS TIME))>= TIME_TO_SEC(CAST(h.callback_time AS TIME))-300),1,0) reminder_flag,
						h.call_date, u.first_name as telemarketer_first_name, u.last_name as telemarketer_last_name
					FROM ace_rp_customers AS c
						join ace_rp_call_history h
						left join ace_rp_users u on u.id=h.call_user_id
						left join ace_rp_orders arc on c.id = arc.customer_id
					WHERE
						c.id=h.customer_id and
						(h.call_result_id in (0,1,2,4,8,9) or h.call_result_id is null)
						AND h.callback_date LIKE '%".$sent_date."%'
						AND h.call_date <= h.callback_date
						".$telem_clause."
						and not exists
							(select * from ace_rp_call_history y
								where y.customer_id=h.customer_id ".$telem_clause1."
									and (y.call_date>h.call_date
									or y.call_date=h.call_date and y.call_time>h.call_time))
					order by reminder_flag desc, h.callback_date, h.callback_time asc
					LIMIT ".$limit;
			}
			
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$result = $db->_execute($sql);

			$jType = $this->HtmlAssist->table2array($this->OrderType->findAll(), 'id', 'name');

			while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{	//var_dump($row);
				foreach ($row as $k => $v)
					$cust_temp['User'][$k] = $v;

				$cust_temp['User']['telemarketer_id']= $row['call_user_id'];
				$cust_temp['User']['job_type']= $jType[$row['job_type']];
				$cust_temp['User']['last_job_done_date']= $row['last_job_done_date'];
				$cust_temp['User']['customer_row_color']= $row['customer_row_color'];
				$cust_temp['User']['customer_row_color_agent']= $row['customer_row_color_agent'];
				$cust_temp['User']['callback_note']= str_replace("'","`",str_replace("\"","`",$row['call_note']));
				$cust_temp['User']['callresult']= $row['call_result_id'];
				$cust_temp['u']['telemarketer_first_name']= $row['telemarketer_first_name'];
				$cust_temp['u']['telemarketer_last_name']= $row['telemarketer_last_name'];

				$cust_temp['User']['callback_time']= date("H:i", strtotime($row['callback_time']));
				$cust_temp['User']['callback_time']= date("H:i", strtotime($row['callback_time']));

				$cust_temp['check_callback'] = 'yes';

				$cust[$row['id']] = $cust_temp;
			}
		}
		else if($_GET['sq_crit'] == 'callback_date')
		{
			$telem_clause = '';
			$telem_clause1 = '';
			$callback_search_head = 'yes';

			if($this->Common->getLoggedUserRoleID() != 6) {
				$telem_clause = ' AND h.callback_user_id='.$this->Common->getLoggedUserID();
				$telem_clause1 = ' AND y.call_user_id='.$this->Common->getLoggedUserID();
			}

			$sql = "SELECT distinct
					c.id, c.card_number, c.first_name, c.last_name,
					c.postal_code, c.email, c.address_unit, c.address_street_number, c.address_street, c.city,
					c.phone, c.cell_phone,c.selected_customer_from_search as customer_row_color,c.selected_customer_from_search_agent as customer_row_color_agent,
					h.call_user_id, h.call_note, h.call_result_id, arc.order_type_id as job_type,arc.job_date as last_job_done_date,
					h.callback_date, h.callback_time,
					if((h.callback_date=current_date())&&(TIME_TO_SEC(CAST(now() AS TIME))>= TIME_TO_SEC(CAST(h.callback_time AS TIME))-300),1,0) reminder_flag,
					h.call_date, u.first_name as telemarketer_first_name, u.last_name as telemarketer_last_name
				FROM ace_rp_customers AS c
					join ace_rp_call_history h
					left join ace_rp_users u on u.id=h.call_user_id
					left join ace_rp_orders arc on c.id = arc.customer_id
				WHERE
					c.id=h.customer_id and
					(h.call_result_id in (0,1,2,4,8,9) or h.call_result_id is null)
					AND h.callback_date LIKE '%".$_GET['sq_str']."%'
					AND h.call_date <= h.callback_date
					".$telem_clause."
					and not exists
						(select * from ace_rp_call_history y
							where y.customer_id=h.customer_id ".$telem_clause1."
								and (y.call_date>h.call_date
								or y.call_date=h.call_date and y.call_time>h.call_time))
				order by reminder_flag desc, h.callback_date, h.callback_time asc
				LIMIT ".$limit;

			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$result = $db->_execute($sql);

			$jType = $this->HtmlAssist->table2array($this->OrderType->findAll(), 'id', 'name');

			while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{	//var_dump($row);
				foreach ($row as $k => $v)
					$cust_temp['User'][$k] = $v;

				$cust_temp['User']['telemarketer_id']= $row['call_user_id'];
				$cust_temp['User']['job_type']= $jType[$row['job_type']];
				$cust_temp['User']['last_job_done_date']= $row['last_job_done_date'];
				$cust_temp['User']['customer_row_color']= $row['customer_row_color'];
				$cust_temp['User']['customer_row_color_agent']= $row['customer_row_color_agent'];
				$cust_temp['User']['callback_note']= str_replace("'","`",str_replace("\"","`",$row['call_note']));
				$cust_temp['User']['callresult']= $row['call_result_id'];
				$cust_temp['u']['telemarketer_first_name']= $row['telemarketer_first_name'];
				$cust_temp['u']['telemarketer_last_name']= $row['telemarketer_last_name'];

				$cust_temp['User']['callback_time']= date("H:i", strtotime($row['callback_time']));
				$cust_temp['User']['callback_time']= date("H:i", strtotime($row['callback_time']));

				$cust_temp['check_callback'] = 'yes';

				$cust[$row['id']] = $cust_temp;
			}
		}
		elseif($_GET['sq_crit'] == 'servicesselect') {
			$telem_clause = ' AND d.questions_id='.$_GET['curpage'];
	      $telem_clause1 = ' AND y.call_user_id='.$this->Common->getLoggedUserID();

 $sql = "SELECT distinct d.id,c.id, c.card_number, c.first_name, c.last_name, c.postal_code, c.email, c.address_unit, c.address_street_number, c.address_street, c.city, c.phone, c.cell_phone, h.call_user_id, h.call_note, h.call_result_id, h.callback_date, h.callback_time,h.call_date FROM ace_rp_customers AS c left join ace_rp_call_history as h on c.id=h.customer_id left join ace_rp_questions As d on d.id=h.questions_id WHERE c.id=h.customer_id AND h.callback_date LIKE '%".$_GET['sq_str']."%' AND d.id='".$_GET['curpage']."'";

        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$result = $db->_execute($sql);
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{	//var_dump($row);
				foreach ($row as $k => $v)
					$cust_temp['User'][$k] = $v;

				$cust_temp['User']['telemarketer_id']= $row['call_user_id'];
				$cust_temp['User']['callback_note']= str_replace("'","`",str_replace("\"","`",$row['call_note']));
				$cust_temp['User']['callresult']= $row['call_result_id'];
				$cust_temp['u']['telemarketer_first_name']= $row['telemarketer_first_name'];
				$cust_temp['u']['telemarketer_last_name']= $row['telemarketer_last_name'];

				$cust_temp['User']['callback_time']= date("H:i", strtotime($row['callback_time']));
				$cust_temp['User']['callback_time']= date("H:i", strtotime($row['callback_time']));

				$cust[$row['id']] = $cust_temp;
			 
			
		}
	}
		
		else if ($_GET['sq_crit'] == 'address_street')
		{
			//$cust = $this->User->findAll($conditions, null, $sort, $limit);

			if($this->Common->getLoggedUserRoleID() != 6) {
				$telem_clause = " AND EXISTS(SELECT * FROM ace_rp_orders WHERE customer_id = u.id AND order_status_id IN(1,3,5) AND booking_source_id = ".$this->Common->getLoggedUserID().")";
			}

			$criteria = 'c.'.$_GET['sq_crit'];
			$sq_str = $_GET['sq_str'];

			$sql = "SELECT c.id, c.card_number, c.first_name, c.last_name,
						c.postal_code, c.email, c.address_unit, c.address_street_number, c.address_street, c.city,
						c.phone, c.cell_phone, c.created, c.modified,
						c.telemarketer_id, '' callback_note, c.callresult,
						c.callback_date, CAST(c.callback_time AS TIME) callback_time,
						c.lastcall_date, u.first_name as telemarketer_first_name, u.last_name as telemarketer_last_name
					FROM ace_rp_customers as c
						left join ace_rp_users u on u.id=c.telemarketer_id
					WHERE CONCAT_WS(' ', c.address_street_number, c.address_street) LIKE '%$sq_str%'
						$telem_clause
					LIMIT ".$limit;
			$cust = $this->User->query($sql);
		}
		else if (($_GET['sq_crit'] != 'booking_source_id') && ($_GET['sq_crit'] != 'order_type_id') && ($_GET['sq_crit'] != 'callback_date'))
		{
			//$cust = $this->User->findAll($conditions, null, $sort, $limit);
			if($this->Common->getLoggedUserRoleID() != 6) {
				$allCampList = $this->Lists->AgentAllCampaingList($_SESSION['user']['id']);
				$arrayString = implode(',', $allCampList);
				// $telem_clause = " AND c.campaign_id IN ('".$arrayString."') AND EXISTS(SELECT * FROM ace_rp_orders WHERE customer_id = u.id AND order_status_id IN(1,3,5) AND booking_source_id = ".$this->Common->getLoggedUserID().")";
				
				$telem_clause = " AND c.campaign_id IN (".$arrayString.") AND EXISTS(SELECT * FROM ace_rp_orders WHERE customer_id = u.id AND order_status_id IN(1,3,5))";
			}

			$criteria = 'c.'.$_GET['sq_crit'];
			$sq_str = $_GET['sq_str'];

			$sql = "SELECT c.id, c.card_number, c.first_name, c.last_name,
						c.postal_code, c.email, c.address_unit, c.address_street_number, c.address_street, c.city,
						c.phone, c.cell_phone, c.created, c.modified,c.selected_customer_from_search as customer_row_color,c.selected_customer_from_search_agent as customer_row_color_agent,
						c.telemarketer_id, '' callback_note, c.callresult, arc.order_type_id as job_type,
						c.callback_date, CAST(c.callback_time AS TIME) callback_time,
						c.lastcall_date, u.first_name as telemarketer_first_name, u.last_name as telemarketer_last_name
					FROM ace_rp_customers as c
						left join ace_rp_users u on u.id=c.telemarketer_id
						left join ace_rp_orders arc on c.id = arc.customer_id
					WHERE $criteria LIKE '%$sq_str%'
						$telem_clause
					LIMIT ".$limit;
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$result = $db->_execute($sql);

			$jType = $this->HtmlAssist->table2array($this->OrderType->findAll(), 'id', 'name');

			while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{	//var_dump($row);
				foreach ($row as $k => $v)
					$cust_temp['User'][$k] = $v;

				$cust_temp['User']['job_type']= $jType[$row['job_type']];
				$cust_temp['User']['customer_row_color']= $row['customer_row_color'];
				$cust_temp['User']['customer_row_color_agent']= $row['customer_row_color_agent'];

				$cust[$row['id']] = $cust_temp;
			}
		}
		else	//by source, order type
		{
			$cust = $this->Order->findAll($conditions, null, $sort, $limit);
			$i = 0;
			foreach ($ord as $order)
			{
				$cust[$i]['c'] = $order['Customer'];
				$cust[$i]['Order'] = $order['Order'];
				$i++;
			}
		}
		//var_dump($cust);

		foreach ($cust as $cnt => $cur)
		{
			if(isset($cur['c'])) {
				foreach ($cur['c'] as $k => $v)
				$cust[$cnt]['User'][$k] = str_replace("'","`",str_replace("\"","`",$v));
			}
		}

		$vicidial_fields=array('phone'=>'phone_number','postal_code'=>'postal_code','city'=>'city','address_street'=>'address1','last_name'=>'last_name','first_name'=>'first_name');
		if (isset($vicidial_fields[$_GET['sq_crit']])) {
			$this->set('vicidial', 1);
			$r=$this->get_list_from_vicidial($vicidial_fields[$_GET['sq_crit']], $_GET['sq_str']);
			var_dump($r);
			$this->set('vicidial_results', $r);
		}
		else
			$this->set('vicidial', 0);

		$this->set('cust', $cust);
		$this->set('callback_search_head',$callback_search_head);
		$this->set('add', $add);
		$this->set('curpage', $_GET['curpage']);
		$this->set('sq_crit', $_GET['sq_crit']);
		$this->set('sq_str', $_GET['sq_str']);
		$this->set('call_results', $this->HtmlAssist->table2array($this->CallResult->findAll(), 'id', 'name'));
		$this->set('Common', $this->Common);
	}

	function get_list_from_vicidial($field,$value) {
		return false;
		if (empty($field) || empty($value)) return false;
		$db =& ConnectionManager::getDataSource("vicidial");
		if ($field=='address1') $value='%'.$value;
		echo $query='select * from vicidial_list v where v.`'.addslashes($field).'` like "'.addslashes($value).'%" group by v.`city`,v.`address1`';
		$r = $db->_execute($query);

		$ret=array();
		while ($row = mysql_fetch_array($r,MYSQL_ASSOC)) {
			$ret[]=$row;
		}
		return $ret;
	}

	function index()
	{
		// error_reporting(E_ALL);
		//Get a list of all technicians
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

        $this->set('allTechnician', $this->Lists->Technicians());
        $this->set('booking_sources', $this->Lists->BookingSources());
        $this->set('job_types', $this->HtmlAssist->table2array($this->OrderType->findAll(), 'id', 'name'));

		$sort = $_GET['sort'];
		$order = $_GET['order'];

		if ($this->params['url']['data[tech_id][]'] != '')
			$this->params['url']['tech_id'] = $this->params['url']['data[tech_id][]'];

		$SORT_ASC = '&nbsp;<span class="sortarrow">&Uacute;</span>';
		$SORT_DESC = '&nbsp;<span class="sortarrow">&Ugrave;</span>';

		$sqlOrder = '';
		$sqlSort = $sort;
		switch ( $order ) {
			case 'sdate' :
			$sqlOrder = 'Order.job_date';
			$this->set('sdate',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'sid' :
			$sqlOrder = 'Order.id';
			$this->set('sid',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'scity' :
			$sqlOrder = 'Customer.city';
			$this->set('scity',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'scrating' :
			$sqlOrder = 'Order.customer_rating';
			$this->set('scrating',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'sbookingby' :
			$sqlOrder = 'Telemarketer.last_name';
			$this->set('sbookingby',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'stech1' :
			$sqlOrder = 'Technician1.last_name';
			$this->set('stech1',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'stech2' :
			$sqlOrder = 'Technician2.last_name';
			$this->set('stech2',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'scommission' :
			$sqlOrder = '(WorkRecord1.commission+WorkRecord2.commission)';
			$this->set('scommission',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'sjobamount' :
			$sqlOrder = 'Order.customer_paid_amount';
			$this->set('sjobamount',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'sstatus' :
			$sqlOrder = 'Status.name';
			$this->set('sstatus',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			//Additions by Anton
			case 'scustomerlast' :
			$sqlOrder = 'Customer.last_name';
			$this->set('scustomerlast',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'scustomerfirst' :
			$sqlOrder = 'Customer.first_name';
			$this->set('scustomerfirst',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			default :
			$sqlOrder = 'Order.job_date DESC, Order.job_time_beg';
			$sqlSort = 'ASC';
			break;
		}
		$sqlOrder .= ' '.$sqlSort;

		$conditions=array();
		$conditions_string = " WHERE 1=1";

		if( $_GET['ffromdate'] != "" || $_GET['ftodate'] != ""  ) {
		  if( $_GET['ffromdate'] != "" && $_GET['ftodate'] != ""  ) {
			$conditions["Order.job_date"] = '>='.$this->Common->getMysqlDate($_GET['ffromdate']);
			$conditions["and"] = array("Order.job_date" => '<='.$this->Common->getMysqlDate($_GET['ftodate']));

			$conditions_string .= " AND (Order.job_date >='".$this->Common->getMysqlDate($_GET['ffromdate'])."' AND Order.job_date<='".$this->Common->getMysqlDate($_GET['ftodate'])."') ";
		  } else {
			  if( $_GET['ffromdate'] != "" ) {
				$conditions["Order.job_date"] = '>='.$this->Common->getMysqlDate($_GET['ffromdate']);
				$conditions_string .= " AND Order.job_date >='".$this->Common->getMysqlDate($_GET['ffromdate'])."' ";
				}
				if( $_GET['ftodate'] != "" ) {
				$conditions["Order.job_date"] = '<='.$this->Common->getMysqlDate($_GET['ftodate']);
				$conditions_string .= " AND Order.job_date <='".$this->Common->getMysqlDate($_GET['ftodate'])."' ";
				}
			}
		}

		if( $_GET['ffromdatebooking'] != "" || $_GET['ftodatebooking'] != ""  ) {
		  if( $_GET['ffromdatebooking'] != "" && $_GET['ftodatebooking'] != ""  ) {
			$conditions["Order.booking_date"] = '>='.$this->Common->getMysqlDate($_GET['ffromdatebooking']);
			$conditions["and"] = array("Order.booking_date" => '<='.$this->Common->getMysqlDate($_GET['ftodatebooking']));

			$conditions_string .= " AND (Order.booking_date >='".$this->Common->getMysqlDate($_GET['ffromdatebooking'])."' AND Order.booking_date<='".$this->Common->getMysqlDate($_GET['ftodatebooking'])."') ";
		  } else {
			  if( $_GET['ffromdatebooking'] != "" ) {
				$conditions["Order.booking_date"] = '>='.$this->Common->getMysqlDate($_GET['ffromdatebooking']);
				$conditions_string .= " AND Order.booking_date >='".$this->Common->getMysqlDate($_GET['ffromdatebooking'])."' ";
				}
				if( $_GET['ftodatebooking'] != "" ) {
				$conditions["Order.booking_date"] = '<='.$this->Common->getMysqlDate($_GET['ftodatebooking']);
				$conditions_string .= " AND Order.booking_date <='".$this->Common->getMysqlDate($_GET['ftodatebooking'])."' ";
				}
			}
		}

		if( $_GET['fname'] != "" ) {
			$conditions["CONCAT(Customer.first_name,' ',Customer.last_name)"] = "LIKE %". $_GET['fname']."%";
		  //$conditions["or"] = array("Customer.last_name" => "LIKE %". $_GET['fname']."%");
			$conditions_string .= " AND CONCAT(Customer.first_name,' ',Customer.last_name) LIKE'%".$_GET['fname']."%'";
		}
		if( $_GET['fphone'] != "" ) {
		  $conditions["Customer.phone"] = "LIKE %". $this->Common->preparePhone($_GET['fphone'])."%";
		  $conditions_string .= " AND Customer.phone LIKE'%".$this->Common->preparePhone($_GET['fphone'])."%'";
		}

		if( $_GET['faddress'] != "" ) {
		  $conditions["Customer.address"] =  "LIKE %". $_GET['faddress']."%";
		  $conditions_string .= " AND Customer.address LIKE'%".$_GET['faddress']."%'";
		}
		if( is_array($_GET['data']) && $_GET['data']['Order']['order_status_id'] != "" ) {
		  $conditions["Order.order_status_id"] =  "=". $_GET['data']['Order']['order_status_id'];
		  $conditions_string .= " AND Order.order_status_id=".$_GET['data']['Order']['order_status_id'];
		}
		elseif (($this->Common->getLoggedUserRoleID() == "3") ||($this->Common->getLoggedUserRoleID() == "9"))
		{
		  $conditions["Order.order_status_id"] =  array("1","5");
		  $conditions_string .= " AND Order.order_status_id in (1,5)";
		}
		if( $_GET['fsource_id'] != "" ) {
		  $conditions["Order.booking_source_id"] = "=". $_GET['fsource_id'];
		  $conditions_string .= " AND Order.booking_source_id=".$_GET['fsource_id'];
		}
		if( $_GET['forder_type_id'] != "" ) {
		  $conditions["Order.order_type_id"] =  $_GET['forder_type_id'];
		  $conditions_string .= " AND Order.order_type_id=".$_GET['forder_type_id'];
		}
		if( $_GET['teleuser_id'] != "" ) {
			$conditions["Order.booking_source_id"] =  $_GET['teleuser_id'];
			$conditions_string .= " AND Order.booking_source_id=".$_GET['teleuser_id'];
		}

		if( $_GET['booker_id'] != "" ) {
		  $conditions["Order.booking_telemarketer_id"] =  $_GET['booker_id'];
			$conditions_string .= " AND Order.booking_telemarketer_id=".$_GET['booker_id'];
		}
		if( $_GET['fordernumber'] != "" ) {
		  $conditions["Order.order_number"] = "LIKE %". $_GET['fordernumber']."%";
		  $conditions_string .= " AND Order.order_number='".$_GET['fordernumber']."'";
		}

		if (is_array($_GET['data']))
			$this->set('forder_status_id', $_GET['data']['Order']['order_status_id']);


		//Additions by Anton
		if (($this->Common->getLoggedUserRoleID() == "3") ||($this->Common->getLoggedUserRoleID() == "9"))
			$this->set('job_statuses', $this->HtmlAssist->table2array($this->OrderStatus->findAll(array("OrderStatus.id"=>array("1","5"))), 'id', 'name'));
		else
			$this->set('job_statuses', $this->HtmlAssist->table2array($this->OrderStatus->findAll(), 'id', 'name'));

		//Derived View - Show/Hide Fields
		if ($this->Common->getLoggedUserRoleID() == 3 || $this->Common->getLoggedUserRoleID() == 9
		          || $this->Common->getLoggedUserRoleID() == 13)
			$show_delete = 0;
		else
			$show_delete = 1;
		$this->set('show_delete', $show_delete);

		if ($this->Common->getLoggedUserRoleID() == 3 || $this->Common->getLoggedUserRoleID() == 9
		          || $this->Common->getLoggedUserRoleID() == 13)
			$show_edit = 0;
		else
			$show_edit = 1;
		$this->set('show_edit', $show_edit);

		$view_mode = $_GET['view_mode'];
		if ($view_mode == '')
			$view_mode = $_POST['view_mode'];
		$pp_view = $_GET['pp_view'];
		$pp_user_id = $_GET['pp_user_id'];

		if (!$view_mode)
			$view_mode = 'all';

		if ($view_mode == 'all')
		{
			$this->set('sm', '1');
			$this->set('subtitle', 'All Jobs & Bookings');
			//Filter Options to show
			$this->set('show_op_date', 1);
			$this->set('show_op_booking_date', 1);
			$this->set('show_op_payperiod', 0);
			$this->set('show_op_name', 1);
			$this->set('show_op_phoneaddress', 1);
			$this->set('show_op_status', 1);
			$this->set('op_action', 'edit');
			$this->set('show_op_source', 1);
			$this->set('show_op_booker', 1);
			$this->set('show_op_order_type', 1);
			$this->set('show_op_tech', 0);
			//Data to show
			$this->set('show_id', 1);
			$this->set('show_job_type', 1);
			$this->set('show_customer_last', 1);
			$this->set('show_customer_first', 1);
			$this->set('show_customer_address', 1);
			$this->set('show_customer_phone', 1);
			$this->set('show_customer_rating', 1);
			$this->set('show_bookedby', 1);
			$this->set('show_techs', 1);
			$this->set('show_hours_done', 0);
			$this->set('show_commission_1', 0);
			$this->set('show_commission_2', 0);
			$this->set('show_booking_amount', 1);
			if ($this->Common->getLoggedUserRoleID() != 3)
				$this->set('show_sales_amount', 1);
			$this->set('show_cancellation', 1);
			$this->set('show_office_note', 1);
			$this->set('show_status', 1);
			$this->set('show_actions', 1);
			$this->set('show_paid', 0);
			$this->set('show_source', 1);
			$this->set('show_total_amounts', 1);
		}
		else if ($view_mode == 'current_bookings')
		{
			$this->set('sm', '1');
			$this->set('subtitle', 'Current Bookings');
			//Conditions
			//status=booked
			$conditions["Order.order_status_id"] = '=1';
			$conditions_string .= " AND Order.order_status_id=1 ";
			//date = future
			$conditions["Order.job_date"] = '>='.date("Y-m-d"); //  H-m-s
			$conditions_string .= " AND Order.job_date>='".date("Y-m-d")."'";
			//Filter Options to show
			$this->set('show_op_date', 1);
			$this->set('show_op_payperiod', 0);
			$this->set('show_op_name', 1);
			$this->set('show_op_phoneaddress', 1);
			$this->set('show_op_status', 0);
			$this->set('op_action', 'edit');
			$this->set('show_op_tech', 0);
			//Data to show
			$this->set('show_id', 1);
			$this->set('show_customer_last', 1);
			$this->set('show_customer_first', 1);
			$this->set('show_customer_address', 1);
			$this->set('show_customer_phone', 1);
			$this->set('show_customer_rating', 0);
			$this->set('show_bookedby', 1);
			$this->set('show_techs', 1);
			$this->set('show_hours_done', 0);
			$this->set('show_commission_1', 0);
			$this->set('show_commission_2', 0);
			$this->set('show_booking_amount', 1);
			$this->set('show_sales_amount', 1);
			$this->set('show_cancellation', 0);
			$this->set('show_office_note', 1);
			$this->set('show_status', 1);
			$this->set('show_actions', 1);
			$this->set('show_paid', 0);
		}
		else if ($view_mode == 'requested_bookings')
		{
			$this->set('sm', '1');
			$this->set('subtitle', 'Incoming Bookings');
			//Conditions
			//status=6 customer request
			$conditions["Order.order_status_id"] = '=6';
			$conditions_string .= " AND Order.order_status_id=6 ";
			//Filter Options to show
			$this->set('show_op_date', 1);
			$this->set('show_op_payperiod', 0);
			$this->set('show_op_name', 1);
			$this->set('show_op_phoneaddress', 1);
			$this->set('show_op_status', 0);
			$this->set('op_action', 'edit');
			$this->set('show_op_tech', 0);
			//Data to show
			$this->set('show_id', 1);
			$this->set('show_customer_last', 1);
			$this->set('show_customer_first', 1);
			$this->set('show_customer_address', 0);
			$this->set('show_customer_phone', 1);
			$this->set('show_customer_rating', 0);
			$this->set('show_bookedby', 0);
			$this->set('show_techs', 0);
			$this->set('show_hours_done', 0);
			$this->set('show_commission_1', 0);
			$this->set('show_commission_2', 0);
			$this->set('show_booking_amount', 0);
			$this->set('show_sale_amount', 0);
			$this->set('show_cancellation', 0);
			$this->set('show_office_note', 0);
			$this->set('show_status', 0);
			$this->set('show_actions', 1);
			$this->set('show_paid', 0);
		}
		else if ($view_mode == 'current_day')
		{
			$this->set('sm', '1');
			$this->set('subtitle', 'Today\'s Jobs');
			//Conditions
			//status=booked
			//$conditions["Order.order_status_id"] = '=1';
			$conditions["or"] = array("Order.order_status_id" => '=1', "Order.order_status_id" => '=5');
			$conditions_string .= " AND (Order.order_status_id=1 OR Order.order_status_id=5 )";
			//date=today
			$conditions["Order.job_date"] = '='.date("Y-m-d");
			$conditions_string .= " AND Order.job_date>='".date("Y-m-d")."'";
			//technician is the one logged in
			$conditions["or"] = array("Order.job_technician1_id" => '='.$_SESSION['user']['id'], "Order.job_technician2_id" => '='.$_SESSION['user']['id']);
			$conditions_string .= " AND (Order.job_technician1_id=".$_SESSION['user']['id']." OR Order.job_technician2_id=".$_SESSION['user']['id'].") ";
			//Filter Options to show
			$this->set('show_op_date', 0);
			$this->set('show_op_payperiod', 0);
			$this->set('show_op_name', 0);
			$this->set('show_op_phoneaddress', 0);
			$this->set('show_op_status', 0);
			$this->set('op_action', 'checkout');
			$this->set('show_op_tech', 0);
			//Data to show
			$this->set('show_id', 1);
			$this->set('show_customer_last', 1);
			$this->set('show_customer_first', 0);
			$this->set('show_customer_address', 1);
			$this->set('show_customer_phone', 0);
			$this->set('show_customer_rating', 0);
			$this->set('show_bookedby', 0);
			$this->set('show_techs', 0);
			$this->set('show_hours_done', 0);
			$this->set('show_commission_1', 0);
			$this->set('show_commission_2', 0);
			$this->set('show_booking_amount', 1);
			$this->set('show_sale_amount', 1);
			$this->set('show_cancellation', 0);
			$this->set('show_office_note', 1);
			$this->set('show_status', 1);
			$this->set('show_paid', 0);
			$this->set('show_timeslots', 1);
			$this->set('show_itemcategories', 1);
			$this->set('show_googlemap', 1);
			$this->set('show_substatus', 1);
			$this->set('show_actions', 1);

			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$result = $db->_execute("SELECT id,name FROM `ace_rp_item_categories`");
			while ($row = mysql_fetch_array($result)) {
				$item_categories[$row['id']] = $row['name'];
			}
			$this->set('item_categories', $item_categories);
		}
		else if ($view_mode == 'pay_period')
		{
			if ($pp_user_id == "")
				if (($this->Common->getLoggedUserRoleID() == 1) || ($this->Common->getLoggedUserRoleID() == 2))
				{
					$_GET['tech_id'] = $_SESSION['user']['id'];
					$pp_user_id = $_SESSION['user']['id'];
				}
				elseif ( ($this->Common->getLoggedUserRoleID() == 6) && (isset($this->params['url']['tech_id'])) )
					$pp_user_id = $this->params['url']['tech_id'];
				elseif ( ($this->Common->getLoggedUserRoleID() == 6) && (isset($this->params['url']['closer_id'])) )
					$pp_user_id = $this->params['url']['closer_id'];

			$this->set('subtitle', 'Jobs Done To Date');

			if ($pp_view == 'technicians')
			{
				$this->set('sm', '8');
				$this->set('show_closer_commission', 1);
				$this->set('show_total_commission', 1);
				$this->set('show_total_amounts', 1);

				if (isset($pp_user_id) && ($pp_user_id != "")){
					$conditions["or"] = array("Order.job_technician1_id" => '='.$pp_user_id, "Order.job_technician2_id" => '='.$pp_user_id, "Order.booking_source_id" => '='.$pp_user_id, "Order.booking_closer_id" => '='.$pp_user_id);
					$conditions_string .= " AND (Order.job_technician1_id=".$pp_user_id." OR Order.job_technician2_id=".$pp_user_id." OR Order.booking_source_id=".$pp_user_id." OR Order.booking_closer_id=".$pp_user_id.") ";
				}
			}
			else if ($pp_view == 'telemarketers')
			{
				$this->set('sm', '9');
				$this->set('show_total_amounts', 1);
				$pp_user_id = $this->Common->getLoggedUserID();
				$conditions["Order.booking_source_id"] = '='.$pp_user_id;
				$conditions_string .= " AND Order.booking_source_id=".$pp_user_id;
			}
			else if ($pp_view == 'closers')
			{
				$this->set('sm', '9');
				$this->set('show_op_closer', 1);
				$this->set('show_total_commission', 1);
				$this->set('show_total_amounts', 1);

				if($pp_user_id == "") {$pp_user_id=0;}
				if (isset($pp_user_id)){
					$conditions["Order.booking_closer_id"] = '='.$pp_user_id;
					$conditions_string .= " AND Order.booking_closer_id=".$pp_user_id;
				}
			}

			//Filter Options to show
			$this->set('show_op_date', 1);
			$this->set('show_op_payperiod', 0);
			$this->set('show_op_name', 0);
			$this->set('show_op_phoneaddress', 0);
			$this->set('show_op_status', 1);
			$this->set('op_action', 'view');

			//Data to show
			$this->set('show_id', 1);
			$this->set('show_customer_last', 1);
			$this->set('show_customer_first', 0);
			$this->set('show_customer_address', 1);
			$this->set('show_customer_rating', 0);
			if ($pp_view == 'technicians')
			{
				if ($this->Common->getLoggedUserRoleID() == 6)
					$this->set('show_op_tech', 1);
				else
					$this->set('show_op_tech', 0);

				$this->set('show_techs', 1);
				$this->set('show_commission_1', 1);
				$this->set('show_commission_2', 1);
				$this->set('show_commission_booking', 1);
				$this->set('show_op_source', 0);
				$this->set('show_source', 0);
				$this->set('show_bookedby', 0);
				$this->set('show_sale_amount', 1);
			}
			else if ($pp_view == 'telemarketers')
			{
				$this->set('show_op_tech', 0);

				$this->set('show_techs', 0);
				$this->set('show_op_source', 1);
				$this->set('show_source', 1);
				//before we used bookedby, now we use source to get to telemarketer $this->set('show_bookedby', 1);
			}
			else if ($pp_view == 'closers')
			{
				$this->set('show_op_tech', 0);

				$this->set('show_techs', 0);
				$this->set('show_commission_closer', 1);
			}
			$this->set('show_hours_done', 0);
			$this->set('show_booking_amount', 1);
			$this->set('show_cancellation', 0);
			$this->set('show_status', 1);
			$this->set('show_actions', 0);
			$this->set('show_office_note', 1);
			//if (($_SESSION['user']['role_id'] == 4) || ($_SESSION['user']['role_id'] == 6))
			//	$this->set('show_paid', 1);
			//else
				$this->set('show_paid', 0);
		}else if ($view_mode == 'call_back')
		{
			$this->set('sm', '1');
			$this->set('subtitle', 'Call Back');
			$conditions["Order.order_status_id"] = '7';
			$conditions_string .= " AND Order.order_status_id=7";
			//Filter Options to show
			$this->set('show_op_date', 0);
			$this->set('show_op_payperiod', 0);
			$this->set('show_op_name', 1);
			$this->set('show_op_phoneaddress', 1);
			$this->set('show_op_status', 0);
			$this->set('op_action', 'edit');
			$this->set('show_op_tech', 0);
			//Data to show
			$this->set('show_id', 1);
			$this->set('show_customer_last', 1);
			$this->set('show_customer_first', 1);
			$this->set('show_customer_address', 1);
			$this->set('show_customer_rating', 0);
			$this->set('show_bookedby', 1);
			$this->set('show_techs', 1);
			$this->set('show_hours_done', 0);
			$this->set('show_commission_1', 0);
			$this->set('show_commission_2', 0);
			$this->set('show_booking_amount', 1);
			$this->set('show_sale_amount', 1);
			$this->set('show_cancellation', 0);
			$this->set('show_status', 1);
			$this->set('show_actions', 1);
			$this->set('show_paid', 0);
			$this->set('show_callback', 1);
		}

		if ($_GET['print'] == 1)
			$this->itemsToShow = 2000;

		//don't count old pages when doing a new search
		if ($_GET['newSearch'])
		{
			$_GET['newSearch']=0;
			$_GET['currentPage'] = 0;
			$_GET['paginationList1'] = '';
		}

		$this->Common->pagination($this->Order->findCount($conditions),$_GET['currentPage'],$this->itemsToShow,$this->pagesToDisplay);

		$pre_o =  $this->Order->findAll($conditions,'',$sqlOrder,$this->itemsToShow,$_GET['currentPage']+1,0);

		//Calculate totals for: current tech (if selected), booking amount, (sale amount)
		$total_comm = 0;
		$total_booking = 0;
		$total_sales = 0;

		//ADDED BY METODI
		//echo $conditions_string;
		if (($pp_view == "technicians") or ($pp_view == "telemarketers"))
		{
			//Booking  Commission
			$conditions_string_temp = $conditions_string;
			if ($pp_user_id != "")
			{
				$conditions_string = $conditions_string.' and `Order`.booking_source_id='.$pp_user_id;
			}
			$sql ='SELECT
					SUM(`WorkRecordSource`.commission),
					`Order`.id
				FROM ace_rp_orders AS `Order`
				LEFT JOIN ace_rp_work_records AS `WorkRecordSource` ON (`WorkRecordSource`.user_id=`Order`.booking_source_id AND `WorkRecordSource`.order_id = `Order`.id)
					'.$conditions_string_temp.
				' GROUP BY `Order`.id';

			$result = $db->_execute($sql);
			while ($row = mysql_fetch_array($result)) {
				$total_comm += $row[0];
			}
		}
		//*********************************

		$db =& ConnectionManager::getDataSource($this->Order->useDbConfig);
		$sql = "SELECT
				      SUM(if(i.class=0,i.quantity*i.price-i.discount+i.addition,0)) as booking,
				      SUM(if(i.class=1,i.quantity*i.price-i.discount+i.addition,0)) as sales
				FROM `ace_rp_orders` AS `Order`
        LEFT JOIN ace_rp_order_items i on (`Order`.`id` = i.order_id)
				LEFT JOIN `ace_rp_customers` AS `Customer` ON (`Order`.`customer_id` = `Customer`.`id`)
				".$conditions_string;

		//echo $sql;
		$result = $db->_execute($sql);
		while ($row = mysql_fetch_array($result)) {
			$total_booking = $row['booking'];
			$total_sales = $row['sales'];
		}

		//Added By Metodi :: 14.04.2010
		//Get Total Rows
		$db =& ConnectionManager::getDataSource($this->Order->useDbConfig);
		$sql = "SELECT
				      Count(*) as total_rows
				FROM `ace_rp_orders` AS `Order` ".$conditions_string;

		//echo '<br/>'.$sql;
		$result = $db->_execute($sql);
		while ($row = mysql_fetch_array($result)) {
			$total_rows =$row['total_rows'];
		}

		$query = "
			SELECT u.id, u.first_name, u.last_name,
				(SELECT COUNT(*)
				FROM ace_rp_orders
				WHERE customer_id = u.id
				AND job_date BETWEEN '2009-01-01' AND '2009-12-31') 2009_jobs,
				(SELECT COUNT(*)
				FROM ace_rp_orders
				WHERE customer_id = u.id
				AND job_date BETWEEN '2010-01-01' AND '2010-12-31') 2010_jobs,
				(SELECT COUNT(*)
				FROM ace_rp_orders
				WHERE customer_id = u.id
				AND job_date BETWEEN '2011-01-01' AND '2011-12-31') 2011_jobs
			FROM ace_rp_customers u
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$job_count[$row['id']]['2009_jobs'] = $row['2009_jobs'];
			$job_count[$row['id']]['2010_jobs'] = $row['2010_jobs'];
			$job_count[$row['id']]['2011_jobs'] = $row['2011_jobs'];
		}

		$query = "
			SELECT oi.order_id, SUM(if(oi.class=0,oi.quantity*oi.price-oi.discount+oi.addition,0)) as booking,
				SUM(if(oi.class=1,oi.quantity*oi.price-oi.discount+oi.addition,0)) as sales
			FROM ace_rp_order_items oi
			LEFT JOIN ace_rp_orders o
			ON oi.order_id = o.id
			WHERE oi.order_id IS NOT NULL
			GROUP BY oi.order_id
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$subtotal[$row['order_id']]['booking'] = $row['booking'];
			$subtotal[$row['order_id']]['sales'] = $row['sales'];
		}

		$this->set('total_comm', $total_comm);
		$this->set('total_booking', $total_booking);
		$this->set('total_sales', $total_sales);
		$this->set('total_rows', $total_rows);
		$this->set('orders', $pre_o);
		$this->set('subtotal', $subtotal);
		$this->set('job_count', $job_count);
		$this->set('Common', $this->Common);
		$this->Order->id = 14;
	}

	// This is a method for the on-line booking proceeding.
	// We do not use it still
	function requestAppointment()
	{
		//save
		$this->data=$_REQUEST['data'];
		if( !empty($this->data) )
		{
			//pr($this->data);die();

			$db =& ConnectionManager::getDataSource('default');

			$this->data['pcode'] = trim($this->data['pcode']) != '' ? $this->Common->prepareZip(trim($this->data['pcode'])):'';
			$this->data['phone'] = trim($this->data['phone']) != '' ? $this->Common->preparePhone(trim($this->data['phone'])) : '';

			// check customer exists
			$customerID = 0;
			$query = "select a.id
						from ace_rp_customers as a
					   where a.phone = '".trim($this->data['phone'])."'";
			$result = $db->_execute($query);
			if( $row = mysql_fetch_array($result) ) {
				// exist => update phone
				$customerID = $row['id'];
				$query = "update ace_rp_customers
                     set first_name = '".($this->data['fname'])."',
                         last_name = '".($this->data['lname'])."',
                         address = '".($this->data['address'])."',
                         city = '".($this->data['city'])."',
                         postal_code = '".($this->data['pcode'])."'
                    where id = '".$customerID."'";
				$db->_execute($query);
			} else {
				// add new customer
				$query = "insert into ace_rp_customers (first_name,last_name,postal_code,phone,address,city)
									values ('".trim($this->data['fname'])."','".trim($this->data['lname'])."',
                          '".trim($this->data['pcode'])."','".trim($this->data['phone'])."',
                          '".trim($this->data['address'])."','".trim($this->data['city'])."')";
				$db->_execute($query);
				/*$customerID = mysql_insert_id();
				$query = "insert into ace_rp_users_roles (user_id,role_id)
									values ('".$customerID."','8')";*/
				$db->_execute($query);
			}

			// save order
			$query = "insert into ace_rp_orders (`order_status_id`,`customer_id`,`job_notes_office`,job_date,booking_date, booking_source_id)
								values ('6','".$customerID."','".str_replace("'","`",$this->data['comment'])."','".date('Y-m-d',strtotime($this->data['date']))."',now(), 96043)";
			$db->_execute($query);
			$orderID = mysql_insert_id();

			//Send a message to Admins
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$sql = "INSERT INTO ace_rp_messages
					  (txt, state, from_user, from_date,
					   to_user, to_date, to_time, file_link)
			   VALUES ('A new request from the web-site has been received', 0, 96043,
					  current_date(), 44885, current_date(), '00:00', ".$orderID.")";
			$db->_execute($sql);
			$sql = "INSERT INTO ace_rp_messages
					  (txt, state, from_user, from_date,
					   to_user, to_date, to_time, file_link)
			   VALUES ('A new request from the web-site has been received', 0, 96043,
					  current_date(), 44851, current_date(), '00:00', ".$orderID.")";
			$db->_execute($sql);
			$sql = "INSERT INTO ace_rp_messages
					  (txt, state, from_user, from_date,
					   to_user, to_date, to_time, file_link)
			   VALUES ('A new request from the web-site has been received', 0, 96043,
					  current_date(), 57499, current_date(), '00:00', ".$orderID.")";
			$db->_execute($sql);

			$this->flash('Your request has been processed. Thank you!', '/orders/requestAppointment');
			exit();
		}
	}

	function scheduleView()
	{
		//$this->layout = 'frameless';
		$db =& ConnectionManager::getDataSource('default');
		/*
		if($this->Common->getLoggedUserRoleID() == 6) {
			$query = "
				SELECT COUNT(*) cnt FROM
					(SELECT *,
						(SELECT COUNT(*) FROM ace_rp_notes n1 WHERE n.order_id = n1.order_id AND (n1.urgency_id = 3 OR n1.urgency_id = 4 OR n1.urgency_id = 5) AND n1.note_date > n.note_date) solutions
					FROM ace_rp_notes n
					WHERE n.urgency_id = 2) d
					LEFT JOIN ace_rp_users u
					ON d.user_id = u.id
					LEFT JOIN ace_rp_orders o
					ON d.order_id = o.id
				WHERE solutions = 0
				AND (o.needs_approval = 1 OR o.sale_approval = 1)
			";

			$result = $db->_execute($query);
			$row = mysql_fetch_array($result);
			$issue_count = $row['cnt'];

			$this->set('issue_count', $issue_count);

			if($issue_count > 0) {
				$this->redirect("messages/noteGatherer");
				exit;
			}
		}*/

		if((isset($_REQUEST['p_code']) && !empty($_REQUEST['p_code'])) || (isset($_REQUEST['city']) && !empty($_REQUEST['city']))){

			$route_type = isset($_REQUEST['route_type'])?$_REQUEST['route_type']:"";

			header("Location: http://hvacproz.ca/acesys/index.php/orders/mapSchedule?p_code=".$_REQUEST['p_code']."&city=".$_REQUEST['city']."&route_type=".$route_type);
		}
		$this->layout='edit';
		$p_code = strtoupper(substr($_REQUEST['p_code'],0,3));
		$city = $_REQUEST['city'];
		$neighbours = array();



		// Get neighbouring areas
		if ($p_code)
		{
			$neighbours[] = $p_code;
			$result = $db->_execute("select * from ace_rp_map where p_code=".$p_code);
			while($row = mysql_fetch_array($result))
			{
				$neighbours[] = $row['neighbour'];
				$city = $row['city'];
			}
		}
		elseif ($city)
		{
			$neighbours[] = $city;
			$result = $db->_execute("select * from ace_rp_map where city='$city'");
			while($row = mysql_fetch_array($result))
				$neighbours[] = $row['p_code'];
		}

		//Prepare Truck Names
		$map_reverse = array();
		$map_all = array();
		$trucks = array();
		$truck_colors = array();
		$truck_numbers = array();

		$route_type = $_REQUEST['route_type'];
		if (!$route_type)
			if (($this->Common->getLoggedUserRoleID()==1))
				$route_type = '2';
			elseif (($this->Common->getLoggedUserRoleID()!=6))
				$route_type = '1';

		$cond = '';
		if ($route_type) $cond = 'and route_type='.$route_type;
		$userId=$this->Common->getLoggedUserID();
		if($this->Common->getLoggedUserRoleID()==3){
			$query = "select ace_rp_inventory_locations.* from ace_rp_inventory_locations inner join ace_rp_truck_maps as artm on ace_rp_inventory_locations.id=artm.truck_id where flagactive = 0 and type=2 and artm.user_id=".$userId." $cond order by ace_rp_inventory_locations.order_id asc";	
		}else{
			$query = "select * from ace_rp_inventory_locations where flagactive = 0 and type=2 $cond order by ace_rp_inventory_locations.order_id asc";
		}
		
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$trucks[$row['id']] = $row['name'];
			$truck_colors[$row['id']] = $row['color'];
			$truck_numbers[$row['id']] = $row['truck_number'];
			$map_all[$row['id']] = 'ALL';
			for ($i=8; $i<18; $i++)
				$map_reverse[$row['id']][$i][] = 'ALL';
		}

		//Prepare Technician Names
		if ($this->Common->getLoggedUserRoleID() != "1") $method = "editBooking"; else $method = "techBooking";
		$this->set('method',$method);
		$this->set('allTechnician',$this->Lists->Technicians(true));

		//Prepare substatus/confirmation names
		$substatuses = $this->Lists->ListTable('ace_rp_order_substatuses');

		//Prepare job types
		$jobtypes = $this->Lists->ListTable('ace_rp_order_types');

		//Convert date from date picker to SQL format
		if ($this->params['url']['ffromdate'] != '')
			$this->params['url']['ffromdate'] = date("Y-m-d", strtotime($this->params['url']['ffromdate']));

		//Pick today's date if no date
		$fdate = ($this->params['url']['ffromdate'] != '' ? $this->params['url']['ffromdate']: date("Y-m-d") ) ;
		$weekday = date('w',strtotime($fdate));

		//Prepare default techs' names
		$default_techs = array();
		$query = "select i.id, i.tech1_day{$weekday}, t1.state t1_state, i.tech2_day{$weekday}, t2.state t2_state
                from ace_rp_inventory_locations i
                left outer join ace_rp_tech_schedule t1 on i.tech1_day{$weekday}=t1.tech_id
                    and CAST(concat(t1.year,'-',t1.month,'-',t1.day) AS DATE)='".$fdate."'
                left outer join ace_rp_tech_schedule t2 on i.tech2_day{$weekday}=t2.tech_id
                    and CAST(concat(t2.year,'-',t2.month,'-',t2.day) AS DATE)='".$fdate."'
               where i.flagactive = 0 and i.type=2 order by i.order_id asc";
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$default_techs[$row['id']] = array();
			$default_techs[$row['id']]['tech1_id'] = $row['tech1_day'.$weekday];
			$default_techs[$row['id']]['tech2_id'] = $row['tech2_day'.$weekday];
			$default_techs[$row['id']]['tech1_state'] = $row['t1_state'];
			$default_techs[$row['id']]['tech2_state'] = $row['t2_state'];
		}

		$sqlConditions = " AND a.job_date = '".$this->Common->getMysqlDate($fdate)."'"; //$this->params['url']['ffromdate']
		if ($route_type)
			$sqlConditions .= ' and a.job_truck in (select id from ace_rp_inventory_locations where route_type='.$route_type.') ';

		$orders = array();

		$status_condition = '';
		if ($this->Common->getLoggedUserRoleID() == "3"||$this->Common->getLoggedUserRoleID() == "9")
			$status_condition = ' and order_status_id!=3 ';

		$query = "SELECT a.id, a.order_status_id,a.emal_bounce_status, a.order_substatus_id, a.job_truck,
						 a.sale_amount, a.job_timeslot_id, a.job_time_beg, a.job_time_end,
						 a.job_date, a.job_technician1_id, a.job_technician2_id, a.order_type_id,
						 a.sCancelReason, c.city as zone_city, c.postal_code as postal_code1,
						 c.color as color, a.booking_source_id as booking_source_id, s.first_name as booking_source_fn,
						 s.last_name as booking_source_ln, c.zone_name as zone_name, u.postal_code as postal_code,
						 CONCAT(u.address_unit,', ',u.address_street_number,', ',u.address_street) as address,
						 'BC' as state, u.phone as customer_phone,
						 concat(u.first_name,' ',u.last_name) as customer_name, u.city as user_city,
						 jt.name job_type_name, a.verified_by_id, rr.role_id, jt.category_id,
						 a.app_ordered_by, a.permit_result, a.order_number, CONCAT(ur.name, ' - ', cr.name) dCancelReason,
						 a.tech_visible,a.tech_visible_agent
					FROM `ace_rp_orders` as a
					LEFT JOIN `ace_rp_order_types` as jt on ( a.order_type_id = jt.id )
					LEFT JOIN `ace_rp_customers` as u on ( a.customer_id = u.id )
					LEFT JOIN `ace_rp_users` as s on ( a.booking_source_id = s.id )
					LEFT JOIN `ace_rp_users` as t on ( a.booking_telemarketer_id = t.id )
					LEFT JOIN `ace_rp_users_roles` as rr on ( rr.user_id = t.id )
					LEFT JOIN `ace_rp_zones` as c on ( (LCASE(LEFT(a.job_postal_code,3)) = LCASE(LEFT(c.postal_code,3))) or (LCASE(c.city) LIKE LCASE(u.city)) )
					LEFT JOIN ace_rp_cancellation_reasons cr ON a.cancellation_reason = cr.id
					LEFT JOIN ace_rp_roles ur ON ur.id = cr.role_id
				   WHERE order_status_id < 6 $status_condition $sqlConditions order by a.id asc";

		$redo = array();
		$followup = array();
		$install = array();
		$other = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{
			foreach ($row as $k => $v)
			  $orders[$row['id']][$k] = $v;

			if ($row['order_type_id'] == 9) $redo[$row['order_status_id']][$row['id']] = 1;
			elseif ($row['order_type_id'] == 10) $followup[$row['order_status_id']][$row['id']] = 1;
			elseif ($row['category_id'] == 2) $install[$row['order_status_id']][$row['id']] = 1;
			else $other[$row['order_status_id']][$row['id']] = 1;

			$orders[$row['id']]['tech_visible'] = $row['tech_visible'];
			$orders[$row['id']]['truck'] = $row['job_truck'];
			$orders[$row['id']]['city'] = (($row['user_city'] != "") ? $row['user_city'] : $row['zone_city']);

			for ($i = date('G', strtotime($row['job_time_beg'])); $i<date('G', strtotime($row['job_time_end'])); $i++)
			{
				unset($map_reverse[$row['job_truck']][$i]);
				unset($map_all[$row['job_truck']]);
				if (isset($map_reverse[$row['job_truck']][$i-1]))
				{
					$map_reverse[$row['job_truck']][$i-1][] = substr($row['postal_code'],0,3);
					$map_reverse[$row['job_truck']][$i-1][] = $orders[$row['id']]['city'];
				}
				if (isset($map_reverse[$row['job_truck']][$i+1]))
				{
					$map_reverse[$row['job_truck']][$i+1][] = substr($row['postal_code'],0,3);
					$map_reverse[$row['job_truck']][$i+1][] = $orders[$row['id']]['city'];
				}
			}

			//Check for the special marks
			if (($row['app_ordered_by']>0)||($row['category_id']!=2))
				$orders[$row['id']]['appliance_ordered'] = true;
			else
				$orders[$row['id']]['appliance_ordered'] = false;

			if (($row['order_status_id']!=5)||$row['permit_result']||($row['category_id']!=2))
				$orders[$row['id']]['permit_ordered'] = true;
			else
				$orders[$row['id']]['permit_ordered'] = false;
		}

		//Determine if all trucks use same techs
		foreach ($trucks as $truck_k => $truck_v)
		{
			$trucktech[$truck_k][0] = '';
			$trucktech[$truck_k][1] = '';
		}

		$flag1 = array();
		$flag2 = array();
		foreach ($orders as $order)
		{
			$truck_k = $order['truck'];

			if ($order['job_technician1_id']!=$trucktech[$truck_k][0])
			{
				if ($trucktech[$truck_k][0]||$flag1[$truck_k]) $trucktech[$truck_k][0] = '';
				else $trucktech[$truck_k][0] = $order['job_technician1_id'];
				$flag1[$truck_k] = true;
			}

				if ($order['job_technician2_id']!=$trucktech[$truck_k][1])
			{
				if ($trucktech[$truck_k][1]||$flag2[$truck_k]) $trucktech[$truck_k][1] = '';
				else $trucktech[$truck_k][1] = $order['job_technician2_id'];
				$flag2[$truck_k] = true;
			}
		}

		// Reverce the map
		$map = array();
		if ($city||$p_code)
			foreach ($map_reverse as $truck_k => $time_v)
			{
				foreach ($time_v as $time_k => $map_val)
				{
					foreach ($map_val as $val)
					{
						if (in_array($val, $neighbours))
							$map[$time_k][] = $truck_k;
					}
				}
			}

		$query = "
			SELECT IFNULL(rc.route_id, 0) route_id,
				rc.route_date,
				c.internal_id city_id,
			    c.code city_code
			FROM ace_rp_cities c
			LEFT JOIN ace_rp_route_cities rc
			ON rc.city_id = c.internal_id
			WHERE rc.route_date = '".$this->Common->getMysqlDate($fdate)."'
			ORDER BY c.name
		";

		$result = $db->_execute($query);
		$i = 0;

		while($row = mysql_fetch_array($result)) {
			$routeCodes[$row['route_id']][] = array(
				"route_id" => $row['route_id'],
				"route_date" => $row['route_date'],
				"city_id" => $row['city_id'],
				"city_code" => $row['city_code']
			);
		}

		$query = "
			SELECT GROUP_CONCAT(' ', order_number) dups
			FROM ace_rp_orders
			WHERE job_date = '".$this->Common->getMysqlDate($fdate)."'
			AND order_status_id != 3
			GROUP BY customer_phone
			HAVING COUNT(order_number) > 1
		";

		$result = $db->_execute($query);
		$row = mysql_fetch_array($result);
		$duplicateOrders = $row['dups'];

		$query = "
			SELECT *
			FROM ace_rp_route_visibility
			WHERE job_date = '".$this->Common->getMysqlDate($fdate)."'
		";

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result)) {
			$routeVisibility[$row['route_id']]['route_id'] = $row['route_id'];
			$routeVisibility[$row['route_id']]['job_date'] = $row['job_date'];
			$routeVisibility[$row['route_id']]['show'] = 1;
		}

		$pay_period = $this->Session->read("office_pay_period");

		if (!$pay_period)
		{
			$query = "select * from ace_rp_pay_periods where current_date() between start_date and end_date and period_type=2";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				$pay_period = $row['id'];
		}

		// $query = "
		// 	SELECT ru.route_id, ru.user_id , u.first_name name, o.booking_count
		// 	FROM ace_rp_route_users ru
		// 	LEFT JOIN ace_rp_users u
		// 	ON ru.user_id = u.id
		// 	LEFT JOIN (SELECT o.booking_source_id, COUNT(*) booking_count
		// 		FROM ace_rp_orders o
		// 		WHERE job_date = '".$this->Common->getMysqlDate($fdate)."'
		// 		GROUP BY booking_source_id) o
		// 	ON u.id = o.booking_source_id
		// 	WHERE ru.pay_period_id = ".$this->Session->read("office_pay_period")."
		// ";
		$pay_period = isset($pay_period)?$pay_period: 0;
		$query = "
			SELECT ru.route_id, ru.user_id , u.first_name name, o.booking_count
			FROM ace_rp_route_users ru
			LEFT JOIN ace_rp_users u
			ON ru.user_id = u.id
			LEFT JOIN (SELECT o.booking_source_id, COUNT(*) booking_count
				FROM ace_rp_orders o
				WHERE job_date = '".$this->Common->getMysqlDate($fdate)."'
				GROUP BY booking_source_id) o
			ON u.id = o.booking_source_id
			WHERE ru.pay_period_id = $pay_period
		";

		$result = $db->_execute($query);
		$i = 0;
		while($row = mysql_fetch_array($result)) {
			$routeUsers[$i]['user_id'] = $row['user_id'];
			$routeUsers[$i]['route_id'] = $row['route_id'];
			$routeUsers[$i]['name'] = $row['name'];
			$routeUsers[$i]['booking_count'] = $row['booking_count'];
			$i++;
		}

		$query = "
			SELECT u.id id, u.first_name name
			FROM ace_rp_users u
			LEFT JOIN ace_rp_users_roles ur
			ON u.id = ur.user_id
			WHERE ur.role_id IN(3,9)
			AND u.is_active = 1
		";

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result)) {
			$telemarketers[$row['id']]['id'] = $row['id'];
			$telemarketers[$row['id']]['name'] = $row['name'];
		}

		$this->set('norm_date', date("Y-m-d", strtotime($fdate)));
		$this->set('fdate', date("d M Y", strtotime($fdate)));
		$this->set('ydate', date("d M Y", strtotime($fdate) - 24*60*60));
		$this->set('tdate', date("d M Y", strtotime($fdate) + 24*60*60));
		$this->set('trucks', $trucks);
		$this->set('default_techs', $default_techs);
		$this->set('truck_colors', $truck_colors);
		$this->set('truck_numbers', $truck_numbers);
		$this->set('substatuses', $substatuses);
		$this->set('jobtypes', $jobtypes);
		$this->set('orders', $orders);
		$this->set('trucktech', $trucktech);
		$this->set('allTypes', $this->Lists->ListTable('ace_rp_route_types'));
		$this->set('map', $map);
		$this->set('map_all', $map_all);
		$this->set('p_code', $p_code);
		$this->set('city', $city);
		//$this->set('allCities', $this->Lists->ListTable('ace_rp_cities'));
		$this->set('allCities',$this->Lists->ActiveCities());
		$this->set('cityWithId', $this->Lists->Cities());
		$this->set('routeCities', $this->Lists->RouteCities());
		$this->set('cityAreas', $this->Lists->CityAreas());
		$this->set('cityCodes', $this->Lists->CityCodes());
		$this->set('routeCodes', $routeCodes);
		$this->set('duplicateOrders', $duplicateOrders);
		$this->set('routeVisibility', $routeVisibility);
		$this->set('routeUsers', $routeUsers);
		$this->set('telemarketers', $telemarketers);

		$redo = array('booked' => count($redo[1]),'done' => count($redo[5]),'canceled' => count($redo[3]));
		$followup = array('booked' => count($followup[1]),'done' => count($followup[5]),'canceled' => count($followup[3]));
		$install = array('booked' => count($install[1]),'done' => count($install[5]),'canceled' => count($install[3]));
		$other = array('booked' => count($other[1]),'done' => count($other[5]),'canceled' => count($other[3]));

		$this->set('redo', $redo);
		$this->set('followup', $followup);
		$this->set('install', $install);
		$this->set('other', $other);

		//Find Max and Min time
		$query = "SELECT MAX(`to`) as end, MIN(`from`) as beg FROM ace_rp_timeslots";
		$result = $db->_execute($query);
		if ($row = mysql_fetch_array($result))
		{
			$this->set('time_beg', $row['beg']);
			$this->set('time_end', $row['end']);
		}

		$this->set("ismobile", $this->Session->read("ismobile"));

	}

	function printEstimate()
	{
		$conditions = array();

		if ($this->params['url']['id'])
			$conditions += array('`Order`.`id`' => $this->params['url']['id']);

		if ($this->params['url']['job_date'])
			$conditions += array('job_date' => $this->params['url']['job_date']);

		if ($this->params['url']['job_truck']){
			$conditions += array('job_truck' => $this->params['url']['job_truck']);

			//load truck and technicians
			//$inventoryLocations = $this->InventoryLocation->find(array('id' => $this->params['url']['job_truck']), null, null, null, null, 1);
			//$technicians = $this->User->findAll(array('is_active' => 1), null, null, null, null, 1);
		}

		$allStatuses = $this->Lists->ListTable('ace_rp_order_statuses');
		$allJobTypes = $this->Lists->ListTable('ace_rp_order_types');

		// UNCOMMENT ON LIVE
		$conditions += array('order_status_id' => array(1, 2, 5));

		//$orders = $this->Order->findAll($conditions, null, "job_truck ASC", null, null, 1);
		$orders = $this->Order->findAll($conditions, null, array("job_truck ASC", "job_time_beg ASC"), null, null, 1);

		$job_type = $orders[0]['Type']['id'];
		$questions = array();
		$db =& ConnectionManager::getDataSource('default');
		$query = "select * from ace_rp_order_types_questions
				where order_type_id=".$job_type." order by question_number asc";
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{
			$questions[$row['question_number']]['question_number'] = $row['question_number'];
			$questions[$row['question_number']]['question'] = $row['question'];
			$questions[$row['question_number']]['answers'] = $row['answers'];
		}
		$job_types = $this->Lists->ListTable('ace_rp_order_types');

		$this->set('job_truck', $this->params['url']['job_truck']);
		$this->set('job_type',$job_types[$job_type]);
		$this->set('job_type_id',$job_type);
		$this->set('questions',$questions);
		$this->set('orders', $orders);
		$this->set('obj', $orders[0]);
		$this->set('allSources', $this->Lists->BookingSources());
		$this->set('job_trucks', $this->HtmlAssist->table2array($this->InventoryLocation->findAll(array('type' => '2'), null, null, null, 1, 0), 'id', 'name'));
		$this->set('payment_methods', $this->HtmlAssist->table2array($this->PaymentMethod->findAll(array(), null, null, null, 1, 0), 'id', 'name'));
	}


	function emailCustomerThankYou()
	{
		$id = $this->params['url']['id'];
		$to = array($this->params['url']['email']);
		$res = true;

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			/*if ($_POST['save'] == true) {
				$this->saveEmail();
			}*/
			$to = $_POST['to'];
			$subject = $_POST['subject'];
			$body = $_POST['body'];
			$header = "From: info@acecare.ca\n";
			$header .= "Content-Type: text/html; charset=iso-8859-1\n" ;
			$res = mail($to, $subject, $body, $header);
			$this->autoRender = false;
			return true;
 		}
 		else {

			$setting = $this->Setting->find(array('title'=>'email_fromaddress'));
			$from['addr'] = $setting['Setting']['valuetxt'];

			$setting = $this->Setting->find(array('title'=>'email_fromname'));
			$from['name'] = $setting['Setting']['valuetxt'];

			$setting = $this->Setting->find(array('title'=>'email_template_custom'));
			$body = $setting['Setting']['valuetxt'];

			$subject = "Thank You";

			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

			$sql = "
				SELECT DISTINCT(oi.name) AS name, job_date, job_notes_office, first_name, last_name, email
				FROM ace_rp_order_items oi, ace_rp_orders_log ol, ace_rp_customers c
				WHERE oi.order_id=ol.order_number
				AND ol.customer_id=c.id
				AND oi.order_id=$id
			";

			$result = $db->_execute($sql);
			while($row = mysql_fetch_array($result)) {
				$date = $row['job_date'];
				$firstname = $row['first_name'];
				$lastname = $row['last_name'];
				$names[] = $row['name'];
				$notes[] = $row['job_notes_office'];
				$to[] = $row['email'];
			}
		}

		// Passing $to in GET request. If failed try and recover from
		// db.
		if ( count($to) < 1 ) {
			$to = array_filter($to,'strlen'); // Remove empty strings.
			$to = array_values($to);
		}

		$body = str_replace('{firstname}', $firstname, $body);
		$body = str_replace('{lastname}', $lastname, $body);
		$body = str_replace('{date}', $date, $body);

		$this->set( array(
			'target'=>$this->here,
			'job_date'=>$date,
			'first_name'=>$firstname,
			'last_name'=>$lastname,
			'names'=>$names,
			'notes'=>$notes,
			'to'=>$to,
			'subject'=>$subject,
			'body'=>$body
		));

		$this->render('email_customer');
	}


	function emailCustomerBooking($id, $send_cancalled_email = 0)
	{

		//Get E-mail Settings
		$settings = $this->Setting->find(array('title'=>'email_fromaddress'));
		$from_address = $settings['Setting']['valuetxt'];

		$settings = $this->Setting->find(array('title'=>'email_fromname'));
		$from_name = $settings['Setting']['valuetxt'];

		if($send_cancalled_email)
		{
			$settings = $this->Setting->find(array('title'=>'email_template_cancelbookingnotification'));
			$template = $settings['Setting']['valuetxt'];

			$settings = $this->Setting->find(array('title'=>'email_template_canceljobnotification_subject'));	
		} else {
			$settings = $this->Setting->find(array('title'=>'email_template_bookingnotification'));
			$template = $settings['Setting']['valuetxt'];

			$settings = $this->Setting->find(array('title'=>'email_template_jobnotification_subject'));
		}
		
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
	function sendEmailUsingMailgun($to,$subject,$body,$order_id = null){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://acecare.ca/acesystem2018/mailcheck.php");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"TO=".$to."&SUBJECT=".$subject."&BODY=".$body);
		// receive server response ...
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$msgid = curl_exec ($ch);//exit;
		curl_close ($ch);
		$this->manageMailgunEmailLogs($msgid, $subject, $order_id);
		return $msgid;
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

	/*  Check email bounce code here */
	function verifyEmailUsingMailgun($subject){
		//echo '<br>MSGID ='.$msgid = trim($msgid);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://acecare.ca/acesystem2018/mailgun_response.php");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"subject=".$subject);
		// receive server response ...
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		return $server_output = curl_exec ($ch);
		curl_close ($ch);
		//var_export($response);
	}

	function emailNotifications()
  {
		//Get E-mail Settings
		$settings = $this->Setting->find(array('title'=>'email_fromaddress'));
		$from_address = $settings['Setting']['valuetxt'];

		$settings = $this->Setting->find(array('title'=>'email_fromname'));
		$from_name = $settings['Setting']['valuetxt'];

		$settings = $this->Setting->find(array('title'=>'email_template_jobnotification'));
		$template = $settings['Setting']['valuetxt'];

		$settings = $this->Setting->find(array('title'=>'email_template_jobnotification_subject'));
		$template_subject = $settings['Setting']['valuetxt'];

		//Search for customers that need to be notified
		$conditions = array();

		$conditions += array('LENGTH(Customer.email)' => '> 0');
		$conditions += array('notified_booking' => '<> 1');
		$conditions += array('order_status_id' => 1);
		$conditions += array('order_substatus_id' => 1);
		$conditions += array('job_date' => '<'.date("Y-m-d", time() + 2*24*60*60));	//make it check for e-mails coming in 2 days or sooner with no notifications sent

		$orders = $this->Order->findAll($conditions, null, null, null, null, 1);


		$updates = array();
		foreach ($orders as $obj)
		{
			//print "Order:".$obj['Order']['id']." - ".$obj['Order']['job_date']." - ".$obj['Customer']['email']." - "."http://".$_ENV['SERVER_NAME'].BASE_URL.'/orders/confirm?a='.$obj['Order']['id'].'&b='.$obj['Customer']['id']."<br/>";
			$msg = $template;
			$msg = str_replace('{first_name}', $obj['Customer']['first_name'], $msg);
			$msg = str_replace('{last_name}', $obj['Customer']['last_name'], $msg);
			$msg = str_replace('{job_date}', date("d M Y", strtotime($obj['Order']['job_date'])), $msg);
			$msg = str_replace('{job_timeslot}', date('ga', strtotime($obj['Order']['job_time_beg'])).' - '.date('ga', strtotime($obj['Order']['job_time_end'])), $msg);
			$msg = str_replace('{url_confirm}', "http://".$_ENV['SERVER_NAME'].BASE_URL.'/orders/confirm?a='.$obj['Order']['id'].'&b='.$obj['Customer']['id'], $msg);

			$res = mail($obj['Customer']['email'], $template_subject, $msg, "From: ".$from_address);	//\"".$from_name."\"

			$updates[count($updates)] = "UPDATE ace_rp_orders SET notified_booking = 1 WHERE id = ".$obj['Order']['id']."\n";
		}

		//Set orders as 'customer notified'
		foreach ($updates as $update)
		{
			$db =& ConnectionManager::getDataSource($this->Order->useDbConfig);
			$db->_execute($update);
		}
		//print "<pre>".$updates."</pre>";;
	}

	function setTechnicians()
	{
    $tech_num = $_GET['tech_num'];
    $tech_id = $_GET['tech_id'];
    $Truck = $_GET['job_truck'];

    if ($tech_id)
    {
      $sql = "update ace_rp_orders set job_technician1_id=0
            WHERE order_status_id!=3 and job_truck is not null
              and job_technician1_id=".$tech_id."
              and job_truck!=".$Truck." and job_date='".$_GET['job_date']."'";
      $this->Order->query($sql);
      $sql = "update ace_rp_orders set job_technician2_id=0
            WHERE order_status_id!=3 and job_truck is not null
              and job_technician2_id=".$tech_id."
              and job_truck!=".$Truck." and job_date='".$_GET['job_date']."'";
      $this->Order->query($sql);
    }

    $sql = "UPDATE ace_rp_orders SET job_technician{$tech_num}_id=".($tech_id ? $tech_id : "NULL")
        ." WHERE job_truck=".$Truck." AND job_date='".$_GET['job_date']."'";
    $res = $this->Order->query($sql);

		$this->redirect($_GET['rurl']);
	}

	function confirm()
  {
		if (($_GET['a'] == '') || ($_GET['b'] == ''))
			return;

		$conditions = array();
		$conditions += array('Order.id' => $_GET['a']);
		$fnd = 0;
		$orders = $this->Order->findAll($conditions, null, null, null, null, 1);
		foreach ($orders as $obj)
		{
			if ($obj['Customer']['id'] == $_GET['b'])
				$fnd = 1;
		}

		if ($fnd)
		{
			$updates = "UPDATE ace_rp_orders SET order_substatus_id = 2 WHERE id = ".$_GET['a']."\n";
			$db =& ConnectionManager::getDataSource($this->Order->useDbConfig);
			$res = $db->_execute($updates);

			if ($res)
				print "Thank you. Your request has been processed.";
			else
				print "Thank you. However, there has been a problem processing your request.";
		}
	}

	function pickTechnician()
  {
		if (($this->params['url']['order_date'] != '') && ($this->params['url']['data']['technician_id'][0] != ''))
		{
			//Convert date from date picker to SQL format
			$this->params['url']['order_date'] = date("Y-m-d", strtotime($this->params['url']['order_date']));

			$this->redirect('/orders/dayendCheckout?order_date=' . $this->params['url']['order_date'] . '&technician_id=' . $this->params['url']['data']['technician_id'][0]);
		}

		//Pick today's date if no date
		$order_date = ($this->params['url']['order_date'] != '' ? $this->params['url']['order_date']: date("Y-m-d"));
		$this->set('order_date', date("d M Y", strtotime($order_date)));

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$result = $db->_execute("
		SELECT a.id,CONCAT(a.first_name,' ',a.last_name) as name
		FROM ace_rp_users as a,ace_rp_users_roles as b
		WHERE a.is_active=1 AND a.id = b.user_id and b.role_id in (1)
		ORDER BY name");
		while ($row = mysql_fetch_array($result)) {
			$techs[$row['id']] = $row['name'];
		}
		$this->set('allTechnician',$techs);
	}

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

		$this->redirect('/orders/editBooking?order_id=' . $order_id . '&reschedule=1');
	}

	function cancel() {
		$order_id = $this->params['url']['order_id'];

		if ($order_id)
		{
			$this->Order->id = $order_id;
			$o = $this->Order->read();
			$o['Order']['order_status_id'] = 3; //cancel
			$this->Order->save($o);
		}

		$this->redirect('/orders/editBooking?order_id=' . $order_id);
	}

	function followup() {
		$order_id =  $this->params['url']['order_id'] != '' ? $this->params['url']['order_id'] : $this->params['form']['order_id'];
		$this->set('order_id', $order_id);

		if( $this->params['form']['action'] == 'save' ) {
			if( $this->params['form']['dosave'] == '1' ) {
				//Acquire old order
				$this->Order->id = $order_id;
				$p = $this->Order->read();

				$this->Order->id = 0;
				$o = $this->Order->read();
				$o['Order']['job_date'] = $this->params['form']['date'];
				$o['Order']['job_notes_technician'] = $this->params['form']['note'];
				$o['Order']['order_type_id'] = 2;
				$o['Order']['order_status_id'] = 1;
				$o['Order']['job_reference_id'] = $order_id;

				//Carried over values
				$o['Order']['job_technician1_id'] = $p['Order']['job_technician1_id'];
				$o['Order']['job_technician2_id'] = $p['Order']['job_technician2_id'];
				$o['Order']['customer_id'] = $p['Order']['customer_id'];
				$this->Order->save($o);
			}
			if ($this->data['rurl'][0])
				$this->redirect($this->data['rurl'][0]);
			else
				$this->redirect('/orders/jobCheckout?order_id=' . $order_id);
			exit();
		}
	}

	function delOrder($order_id, $nr = false)
	{
		if (!$order_id)
			$order_id = $this->params['url']['order_id'];

		$this->Order->delete($order_id, true);

		//if NR=no redirect then quit here
		if ($nr)
			return;

		if ($this->data['rurl'][0])
			$this->redirect($this->params['url']['rurl']);
		else
			$this->redirect('/orders/');

	}

	function sendMail()
	{
		if (isset($this->params['url']['message']))
		{
			//Get E-mail Settings
			$settings = $this->Setting->find(array('title'=>'email_fromaddress'));
			$from_address = $settings['Setting']['valuetxt'];

			$settings = $this->Setting->find(array('title'=>'email_fromname'));
			$from_name = $settings['Setting']['valuetxt'];

			$res = mail($this->params['url']['email'], $this->params['url']['subject'], $this->params['url']['message'], "From: ".$from_address);

			$this->set('hide_form', "display: none;");
		}
		else
			$this->set('hide_conf', "display: none;");

		$this->set('email', $this->params['url']['email']);
		$this->set('text', $this->params['url']['text']);
	}

	//Method checks for orders in the same timeslot
	function conflictCheck()
	{

		$job_date = $_GET['job_date'];
		$job_truck = $_GET['job_truck'];
		$job_from = $_GET['job_from'];
		$job_to = $_GET['job_to'];
		$order_id = $_GET['order_id'];
		$customer_city = $_GET['customer_city'];
		$job_status = $_GET['jobstatus'];
		$user_role_id = $_GET['user_role_id'];
		//13,3,9

		//if the job is cancelled or done, we don't have to check for anything
		if($job_status == 3 || $job_status == 5) {
			echo "OK";
			exit;
		}

		$db =& ConnectionManager::getDataSource('default');

		//see if it is closed

		$query = "
			SELECT COUNT(*) cnt
			FROM ace_rp_route_cities rc
			LEFT JOIN ace_rp_cities c
			ON rc.city_id = c.internal_id
			WHERE rc.route_id = $job_truck
			AND c.name = 'BLOCKED'
			AND rc.route_date = '".date("Y-m-d", strtotime($job_date))."'
		";

		$result = $db->_execute($query);
		$row = mysql_fetch_array($result);
	    $blockCount = $row['cnt'];

		if($blockCount >= 1) {
			echo "The truck for $job_date is closed. Please check with admin.";
			exit;
		}

		//count the cities for the route

		$query = "
			SELECT COUNT(*) cnt
			FROM ace_rp_route_cities rc
			LEFT JOIN ace_rp_cities c
			ON rc.city_id = c.internal_id
			WHERE rc.route_id = $job_truck
			AND rc.route_date = '".date("Y-m-d", strtotime($job_date))."'
		";

		$result = $db->_execute($query);
		$row = mysql_fetch_array($result);
	    $cityCount = $row['cnt'];

		if($cityCount > 0) {
			//if a city/zone is in that route, check if the city of the job is allowed

			$query = "
				SELECT COUNT(*) cnt
				FROM ace_rp_route_cities rc
				LEFT JOIN ace_rp_cities c
				ON rc.city_id = c.internal_id
				WHERE rc.route_id = $job_truck
				AND c.id = '$customer_city'
				AND rc.route_date = '".date("Y-m-d", strtotime($job_date))."'
			";

			$result = $db->_execute($query);
			$row = mysql_fetch_array($result);
			$areaCount = $row['cnt'];

			if($areaCount == 0 && $user_role_id != 4 && $user_role_id != 6) {
			//if($areaCount == 0) {
				//echo "areaCount:$areaCount job_date:$job_date customerCity:$customer_city job_truck:$job_truck";
				echo "The customer city $customer_city is not allowed on this route. Please check with admin. [$user_role_id]";
				exit;
			}
		} else {
			//if there is no city/zone assigned to the route, assign one to it depending on the $customer_city
			if($customer_city != 'BURNABY') {
				$query = "
					SELECT ca.area_id area_id
					FROM ace_rp_city_areas ca
					LEFT JOIN ace_rp_cities c
					ON c.internal_id = ca.city_id
					WHERE c.id = '$customer_city'
				";
				$result = $db->_execute($query);
				$row = mysql_fetch_array($result);
				$zoneId = $row['area_id'];

				if($zoneId) {
					$query = "
						INSERT INTO ace_rp_route_cities(route_id, route_date, city_id)
						SELECT $job_truck, '".date("Y-m-d", strtotime($job_date))."', city_id
						FROM ace_rp_city_areas WHERE area_id = $zoneId
					";
					$db->_execute($query);
				}
			}
		}

		//count the number of schedules on the current date and timeslot; if it is 0, then it is valid

	    $query = "select count(*) cnt from ace_rp_orders
	    	where job_date = '".date("Y-m-d", strtotime($job_date))."'
	    	and job_truck  = '".$job_truck."'
	    	and ((job_time_beg >= '".$job_from.":00' and job_time_beg < '".$job_to.":00')
	    		or (job_time_end > '".(1+$job_from).":00' and job_time_end <= '".$job_to.":00'))
	    	AND order_status_id in (1,5) and id != '".$order_id."'";

		//$query = "select count(*) cnt from ace_rp_orders
//	    	where job_date = '".date("Y-m-d", strtotime($job_date))."'
//	    	and job_truck  = '".$job_truck."'
//	    	and job_time_beg BETWEEN '".$job_from.":00' AND '".$job_to.":00'
//	    	AND order_status_id in (1,5) and id != '".$order_id."'";

		$result = $db->_execute($query);
		$row = mysql_fetch_array($result);
	    $bookedTrucks = $row['cnt'];

		if ($bookedTrucks > 0) {
			echo "There is another job for this truck for this time.";
			exit;
		}
		//echo "prevJobTruck:$prevJobTruck jobsOnArea:$jobsOnArea customerCity:$customer_city";
		echo 'OK';
		exit;
	}

	function nothingChangeJobTruckAndHour()
	{
		exit;
	}

    //Method is used for drag'n'drop mechanism in the schedule view
	//Added By Metodi
	function changeJobTruckAndHour()
	{
		//1. Get all parrams
		//2. Load Order
		//2.1 Get Job Time duration in hours
		//3. Update Object

		if (($_GET['order_id'] != "") && ($_GET['job_truck'] != "") && ($_GET['beg_hour'] != ""))
		{
			$this->Order->id = $_GET['order_id'];
			$o = $this->Order->read();
			$job_date = $o['Order']['job_date'];
			$hoursRange = date("H", strtotime($o['Order']['job_time_end'])) - date("H", strtotime($o['Order']['job_time_beg']));
			if(date("H", strtotime($_GET['beg_hour'])) == 18)
			{
				$end_hour = date("H", strtotime($_GET['beg_hour'])).":00:00";
			}
			elseif (date("H", strtotime($_GET['beg_hour'])) + $hoursRange > 18)
			{
				$end_hour = "18:00:00";
			}
			else
			{
				$end_hour = date("H", strtotime($_GET['beg_hour'])) + $hoursRange.":00:00";
			}

			// If the truck has been changed we should empty technicians' fields
			if ($o['Order']['job_truck'] != $_GET['job_truck'])
			{
				$o['Order']['job_technician1_id'] = 0;
				$o['Order']['job_technician2_id'] = 0;
				$sql = "select job_technician1_id, job_technician2_id
					  from ace_rp_orders
					WHERE order_status_id!=3 and job_truck=".$_GET['job_truck']."
					  and job_date='".$job_date."'";

				$db =& ConnectionManager::getDataSource('default');
				$result = $db->_execute($sql);
				while($row = mysql_fetch_array($result))
				{
					$o['Order']['job_technician1_id'] = $row['job_technician1_id'];
					$o['Order']['job_technician2_id'] = $row['job_technician2_id'];
				}
			}

			// if the time has been changed, we should set this job to the 'changed' substatus
			if ((date("H", strtotime($o['Order']['job_time_beg'])) != date("H", strtotime($_GET['beg_hour'])))
				&&($o['Order']['order_substatus_id']!=8))
				$o['Order']['order_substatus_id'] = 6;

			$o['Order']['job_truck'] = $_GET['job_truck'];
			$o['Order']['job_time_beg'] = $_GET['beg_hour'];
			$o['Order']['job_time_end'] = $end_hour;

			$this->Order->save($o);

			/*
			//check if there is nothing on the truck
			$check_order_id = $_GET['order_id'];
			$query = "
				SELECT *
				FROM ace_rp_orders
				WHERE id = $check_order_id
			";
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result);
			$check_job_date = $row['job_date'];
			$check_job_truck = $row['job_truck'];

			$query = "
				SELECT COUNT(*) job_count
				FROM ace_rp_orders
				WHERE job_truck = $check_job_truck
				AND job_date = '$check_job_date'
				AND order_id != $check_order_id
			";
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result);
			$check_job_count = $row['job_count'];

			//if there is nothing, remove all city restrictions
			if($check_job_count == 0) {
				$query = "
					DELETE ace_rp_route_cities
					WHERE route_id = $check_job_truck
					AND route_date = '$check_job_date'
				";
				$db->_execute($query);
			}
			*/
			//then save it



		}

		print json_encode(array('order_id' => $_GET['order_id']));
		exit;
	}

	function saveAllowedCities()
	{
		$cities = explode(",", $_GET['city_list']);
		$job_truck = $_GET['job_truck'];
		$job_date = $_GET['job_date'];

		$db =& ConnectionManager::getDataSource('default');

		$query = "
			DELETE FROM ace_rp_route_cities
			WHERE route_id = $job_truck
			AND route_date = '".date("Y-m-d", strtotime($job_date))."'
		";
		$db->_execute($query);

		foreach($cities as $city) {
			$query = "
				INSERT INTO ace_rp_route_cities(route_id, route_date, city_id)
				VALUES($job_truck, '".date("Y-m-d", strtotime($job_date))."', $city)
			";
			$db->_execute($query);
		}

		exit;
	}

	function hideRouteVisibility()
	{
		$job_truck = explode(",", $_GET['job_truck']);
		//$job_truck = $_GET['job_truck'];
		$job_date = $_GET['job_date'];

		$db =& ConnectionManager::getDataSource('default');

		foreach($job_truck as $truck) {
			$query = "
				DELETE FROM ace_rp_route_visibility
				WHERE route_id = $truck
				AND job_date = '".date("Y-m-d", strtotime($job_date))."'
			";
			$db->_execute($query);
		}
		exit;
	}

	function showRouteVisibility()
	{
		$job_truck = explode(",", $_GET['job_truck']);
		//$job_truck = $_GET['job_truck'];
		$job_date = $_GET['job_date'];

		$db =& ConnectionManager::getDataSource('default');

		foreach($job_truck as $truck) {
			$query = "
				DELETE FROM ace_rp_route_visibility
				WHERE route_id = $truck
				AND job_date = '".date("Y-m-d", strtotime($job_date))."'
			";
			$db->_execute($query);

			$query = "
				INSERT INTO ace_rp_route_visibility(route_id, job_date)
				VALUES($truck, '".date("Y-m-d", strtotime($job_date))."')
			";
			$db->_execute($query);

		}
		exit;
	}

	function addRouteUser()
	{
		$job_truck = $_GET['job_truck'];
		$user_id = $_GET['user_id'];

		$db =& ConnectionManager::getDataSource('default');

		$query = "
			INSERT INTO ace_rp_route_users(route_id, user_id, pay_period_id)
			VALUES($job_truck, $user_id, ".$this->Session->read("office_pay_period").")
		";
		$db->_execute($query);

		exit;
	}

	function deleteRouteUser()
	{
		$job_truck = $_GET['job_truck'];
		$user_id = $_GET['user_id'];

		$db =& ConnectionManager::getDataSource('default');

		$query = "
			DELETE FROM ace_rp_route_users
			WHERE route_id = $job_truck
			AND user_id = $user_id
			AND pay_period_id = ".$this->Session->read("office_pay_period")."
		";
		$db->_execute($query);

		exit;
	}

	function feedbacks_list()
	{
		$this->layout="list";

		//CUSTOM PAGING
		//*************s
		$itemsCount = 30;
		$currentPage = 0;
		$previousPage = 0;
		$nextPage = 1;

		if(isset($_GET['page'])){
			if(is_numeric($_GET['page'])){
				$currentPage = $_GET['page'];
			}
		}
		$sqlPaging = " LIMIT 0,".$itemsCount;
		if($currentPage > 0){
			$firstItem = ($currentPage*$itemsCount)+1;
			$sqlPaging = " LIMIT ".$firstItem.",".$itemsCount;

			$previousPage = $currentPage -1;
			$nextPage = $currentPage +1;
		}
		//********************
		//END OF CUSTOM PAGING

		//**********
		//CONDITIONS
		//Convert date from date picker to SQL format
		if ($this->params['url']['ffromdate'] != '')
			$this->params['url']['ffromdate'] = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
    else
      $this->params['url']['ffromdate'] = date("Y-m-d", strtotime(date("d M Y")) - 24*60*60);

		if ($this->params['url']['ftodate'] != '')
			$this->params['url']['ftodate'] = date("Y-m-d", strtotime($this->params['url']['ftodate']));
    else
      $this->params['url']['ftodate'] = date("Y-m-d", strtotime(date("d M Y")) - 24*60*60);

		//Pick today's date if no date
		$fdate = ($this->params['url']['ffromdate'] != '' ? $this->params['url']['ffromdate']: "" ) ;
		$tdate = ($this->params['url']['ftodate'] != '' ? $this->params['url']['ftodate']: "" ) ;
		$phone = $this->params['url']['fphone'];

		$allTechnicians = $this->Lists->Technicians();
    $ftechid = $this->params['url']['ftechid'];
		if ($this->Common->getLoggedUserRoleID()==1) $ftechid = $this->Common->getLoggedUserID();
		$allQuality = array('BAD'=>'BAD','OK'=>'OK','GOOD'=>'GOOD','EXCELLENT'=>'EXCELLENT');
    $fquality = $this->params['url']['fquality'];
		//CONDITIONS
		//**********

    $allJobTypes = $this->Lists->ListTable('ace_rp_order_types');

		$db =& ConnectionManager::getDataSource('default');
		if($fdate != '')
			$sqlConditions .= " AND a.job_date >= '".$this->Common->getMysqlDate($fdate)."'";
		if($tdate != '')
			$sqlConditions .= " AND a.job_date <= '".$this->Common->getMysqlDate($tdate)."'";
		if($ftechid)
			$sqlConditions .= " AND (a.job_technician1_id=$ftechid or a.job_technician2_id=$ftechid)";
		if($fquality)
			$sqlConditions .= " AND a.feedback_quality='$fquality'";
		if($phone != '')
			$sqlConditions .= " AND u.phone LIKE '%".$phone."%' ";

		//If user is Limited Telemarketer - role id=9
		//then show only orders that belongs to him
		if (($_SESSION['user']['role_id'] == 3) || ($_SESSION['user']['role_id'] == 9)) { // TELEMARKETER=3 or LIMITED TELEMARKETE9 ($_SESSION['user']['role_id'] == 3) ||
			//$sqlConditions.= " AND a.booking_source_id=".$this->Common->getLoggedUserID();
		}

		$orders = array();
		$query = "SELECT 		a.id, a.order_number,
						a.job_date,
						a.order_type_id,
						a.customer_id,
						a.job_technician1_id,
						a.job_technician2_id,
						a.feedback_callback_date,
						a.feedback_price,
						a.feedback_comment,
						if (a.feedback_sticker=1,'Yes',if (a.feedback_sticker=0,'No','')) feedback_sticker,
						if (a.feedback_number=1,'Yes',if (a.feedback_number=0,'No','')) feedback_number,
						a.feedback_suggestion,
						a.feedback_quality,

						u.first_name,
						u.last_name,
						u.phone as customer_phone,
						u.callback_date,
						o.office_note

			FROM 			`ace_rp_orders` as a
			INNER JOIN		`ace_rp_customers` as u on ( a.customer_id = u.id )
			LEFT JOIN
				(SELECT user_id, CONCAT(GROUP_CONCAT('". "<strong>" . "', created_by, ':</strong> ', note, '<br />' SEPARATOR ''),'...') office_note
				FROM ace_rp_users_notes
				WHERE user_id != ''
				AND user_id IS NOT NULL
				AND note IS NOT NULL
				GROUP BY user_id) o
			ON o.user_id = u.id
			WHERE 	order_status_id in (1,5) ".$sqlConditions." order by u.callback_date desc ".$sqlPaging;

		//echo $query;
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
				//Transfer all fields from the query result
				foreach ($row as $k => $v)
					$orders[$row['id']][$k] = $v;

			$orders[$row['id']]['customer_name'] = $row['first_name'].' '.$row['last_name'];
			$orders[$row['id']]['tech1_name'] = $allTechnicians[$row['job_technician1_id']];
			$orders[$row['id']]['tech2_name'] = $allTechnicians[$row['job_technician2_id']];
      $orders[$row['id']]['job_type'] = $allJobTypes[$row['order_type_id']];

      $totals = $this->Common->getOrderTotal($row['id']);
			$orders[$row['id']]['total'] = $totals['sum_total'];
		}

		$this->set("previousPage",$previousPage);
		$this->set("nextPage",$nextPage);
		$this->set("orders", $orders);
		$this->set("phone", $phone);
		$this->set("ftechid", $ftechid);
		$this->set("fquality", $fquality);
		$this->set('allTechnician', $allTechnicians);
		$this->set('allQuality', $allQuality);
    $this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));
    $this->set('next_tdate', date("d M Y", strtotime($tdate) + 24*60*60));
		if($fdate!='')
			$this->set('fdate', date("d M Y", strtotime($fdate)));
		if($tdate!='')
			$this->set('tdate', date("d M Y", strtotime($tdate)));

		$this->set("ismobile", $this->Session->read("ismobile"));
	}

	function feedbacks_add()
	{
		$this->layout="edit";

		if (!empty($this->data))
		{
			$errorExist = 0;
			if($this->data['Order']['feedback_price'] =='')
			{
				$this->set('errorMessage', 'Please enter price.');
				$errorExist = 1;
			}
			else{
				if(!is_numeric($this->data['Order']['feedback_price']))
				{
					$this->set('errorMessage', 'Please enter correct price - price is invalid!');
					$errorExist = 1;
				}
				else
					$price = $this->data['Order']['feedback_price'];
			}

			$feedback_comment = $this->data['Order']['feedback_comment'];
			$feedback_price = $this->data['Order']['feedback_price'];
			$call_back_date = $_POST['callback_date'];
			$feedback_sticker = $this->data['Order']['feedback_sticker'];
			$feedback_number = $this->data['Order']['feedback_number'];
			$feedback_suggestion = $this->data['Order']['feedback_suggestion'];
      		$call_result_id = 2;
			if($_POST['callback_date'] =='') $call_result_id = 3;

			if($errorExist == 1)
			{
				if( $this->params['url']['id'] > 0)
        {
					$this->Order->id = $this->params['url']['id'];
					$this->data = $this->Order->read();
					$this->set('callback_date', $call_back_date);
					$this->data['Order']['feedback_suggestion'] = $feedback_suggestion;
					$this->data['Order']['feedback_number'] = $feedback_number;
					$this->data['Order']['feedback_sticker'] = $feedback_sticker;
					$this->data['Order']['feedback_price'] = $feedback_price;
          $this->data['Order']['feedback_comment'] = $feedback_comment;

					//Set Query string back to hidden field (Search fields form list page)
					$this->set('search_query',$_POST["search_query"]);
				}

				$this->render();
				exit;
			}

			$this->data['Order']['feedback_callback_date'] = date("Y-m-d", strtotime($_POST['callback_date'] ));
			if ($this->data['Order']['feedback_professional']=='on')
				$this->data['Order']['feedback_professional']=1;
			else
				$this->data['Order']['feedback_professional']=0;

			if ($this->data['Order']['feedback_knowledgeable']=='on')
				$this->data['Order']['feedback_knowledgeable']=1;
			else
				$this->data['Order']['feedback_knowledgeable']=0;

			if ($this->data['Order']['feedback_skilled']=='on')
				$this->data['Order']['feedback_skilled']=1;
			else
				$this->data['Order']['feedback_skilled']=0;

			if ($this->data['Order']['feedback_clear']=='on')
				$this->data['Order']['feedback_clear']=1;
			else
				$this->data['Order']['feedback_clear']=0;

			if ($this->data['Order']['feedback_timing']=='on')
				$this->data['Order']['feedback_timing']=1;
			else
				$this->data['Order']['feedback_timing']=0;

			if ($this->data['Order']['feedback_not_professional']=='on')
				$this->data['Order']['feedback_not_professional']=1;
			else
				$this->data['Order']['feedback_not_professional']=0;

			if ($this->data['Order']['feedback_not_knowledgeable']=='on')
				$this->data['Order']['feedback_not_knowledgeable']=1;
			else
				$this->data['Order']['feedback_not_knowledgeable']=0;

			if ($this->data['Order']['feedback_not_skilled']=='on')
				$this->data['Order']['feedback_not_skilled']=1;
			else
				$this->data['Order']['feedback_not_skilled']=0;

			if ($this->data['Order']['feedback_not_clear']=='on')
				$this->data['Order']['feedback_not_clear']=1;
			else
				$this->data['Order']['feedback_not_clear']=0;

			if ($this->data['Order']['feedback_not_timing']=='on')
				$this->data['Order']['feedback_not_timing']=1;
			else
				$this->data['Order']['feedback_not_timing']=0;

			if (($this->data['Order']['feedback_sticker']!='0')&&($this->data['Order']['feedback_sticker']!='1'))
				$this->data['Order']['feedback_sticker'] = 3;
			if (($this->data['Order']['feedback_number']!='0')&&($this->data['Order']['feedback_number']!='1'))
				$this->data['Order']['feedback_number'] = 3;

			$this->Order->id = $this->data['id'];



			if ($this->Order->save($this->data['Order']))
			{
				$this->AddCallToHistory($_REQUEST['customer_id'], $this->Common->getLoggedUserID(), $call_result_id, 'Feedback', $call_back_date, '', '', '', 57145, '');
        		$this->redirect('orders/feedbacks_list?'.$_POST["search_query"]);
				//$this->redirect('orders/feedbackView');
        		exit();
			}
		}
		else{
			$this->set('search_query',$_SERVER['QUERY_STRING']);
			$allTechnicians = $this->Lists->Technicians();



			if( $this->params['url']['id'] > 0) {
				$this->Order->id = $this->params['url']['id'];
				$this->data = $this->Order->read();

				if($this->data['Order']['id'] == ''){ //invalid order id
					$this->redirect('orders/feedbacks_list?'.$_SERVER['QUERY_STRING']);
					exit();
				}

				$order_id = $this->params['url']['id'];

				$db =& ConnectionManager::getDataSource('default');

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
					ORDER BY n.note_date ASC
				";

				$result = $db->_execute($query);

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

				$query = "
					SELECT job_notes_technician
					FROM ace_rp_orders
					WHERE id = $order_id
				";

				$result = $db->_execute($query);

				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$tech_notes = $row['job_notes_technician'];
				}

				$this->set('tech_notes',$tech_notes);

				$this->data['Tech1'] = $allTechnicians[$this->data['Order']['job_technician1_id']];
				$this->data['Tech2'] = $allTechnicians[$this->data['Order']['job_technician2_id']];

				if($this->data['Customer']['callback_date'] !='')
					$this->set('callback_date', date("d M Y", strtotime($this->data['Customer']['callback_date'])));

				$totals = $this->Common->getOrderTotal($this->data['Order']['id']);
				$this->set('total',$totals['sum_total']);

				$job_date = new DateTime($this->data['Order']['job_date']);
				$job_date->modify('+6 month');
				$this->set('callback_date_6',$job_date->format("d M Y"));
				$job_date = new DateTime($this->data['Order']['job_date']);
				$job_date->modify('+1 year');
				$this->set('callback_date_12',$job_date->format("d M Y"));
				$this->set('callback_date_dnc','');


			}

			else {
				$this->redirect('orders/feedbacks_list?'.$_SERVER['QUERY_STRING']);
				exit();
			}
		}
	}

	// Method creates an HTML table with customer jobs' history
	function showCustomerJobs()
	{
		session_write_close(); // It added to remove delay in ajax response
		$customer_id = $_GET['customer_id'];
		$order_id = $_GET['order_id'];
		$phone = $_GET['phone'];
		$fromDialer = isset($_GET['fromDialer']) ? $_GET['fromDialer'] : 0;
		if ($this->Common->getLoggedUserRoleID() != "1") $method = "editBooking"; else $method = "techBooking";
		$allStatuses = $this->Lists->ListTable('ace_rp_order_statuses');
		$allJobTypes = $this->Lists->ListTable('ace_rp_order_types');

		echo '<table class="historytable">';
		echo '<tr cellpadding="10">';
		echo '<th>Date</th><th>Booking</th><th>Status</th><th>Tech</th>';
		if ($this->Common->getLoggedUserRoleID() == 6) echo '<th>Feedback</th>';
		echo '</tr>';
		echo "<tr><td colspan=8 style=\"background: #AAAAAA; height: 5px;\"></td></tr>\n";

		if ($phone)
		{
			$sq_str = preg_replace("/[- \.]/", "", $phone);
			$sq_str = preg_replace("/([?])*/", "[-]*", $phone);
//	    $past_orders = $this->Order->findAll(array('Order.customer_id'=> $customer_id), null, "job_date DESC", null, null, 1);
//			$past_orders = $this->Order->findAll(array('Customer.phone'=> $phone), null, "job_date DESC", null, null, 1);
//	      $past_orders = $this->Order->findAll(array('Order.customer_phone'=> $phone), null, "job_date DESC", null, null, 1);
			$past_orders = array();
			$db =& ConnectionManager::getDataSource('default');
			if($fromDialer > 0)
			{
				$query = "select * from ace_rp_orders where customer_phone regexp '$sq_str' order by job_date DESC limit 1";
			} else {
				$query = "select * from ace_rp_orders where customer_phone regexp '$sq_str' order by job_date DESC";
			}
			

			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result))
				$past_orders[$row['id']] = $row['id'];
 $loopstart=1;
			foreach ($past_orders as $cur)
			{
				$p_order = $this->Order->findAll(array('Order.id'=> $cur), null, "job_date DESC", null, null, 1);
				$p_order = $p_order[0];
				if ($p_order['Order']['id'] == $order_id)
					$add = "style=\"background: #FFFF99;\"";
				else
				{
					if ((($this->Common->getLoggedUserRoleID() != 9)
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
					  $items_text .= '<tr >';
					  $items_text .= '<td>'.$text.'</td>';
					  $items_text .= '<td style="width:200px">'.$oi['name'].'</td>';
					  $items_text .= '<td>&nbsp;</td>';
					  $items_text .= '<td>'.$this->HtmlAssist->prPrice($str_sum).'</td>';
					  $items_text .= '<td>&nbsp;</td>';
					  //$items_text .= '<td>'.$this->HtmlAssist->prPrice($str_sum).'</td>';
					  $items_text .= '</tr>';
					}
				}

    if($loopstart==1)$class='';else $class = 'showjobhistory';
    
	          echo "<tr class='orderline  ".$class."' valign='top' ".$add."  loopstart='".$loopstart."'>";
	          echo "<td rowspan=1>".date('d-m-Y', strtotime($p_order['Order']['job_date']))."<br>REF#".$p_order['Order']['order_number']."</td>";
	          echo "<td rowspan=1>".$this->HtmlAssist->prPrice($total_booked)."</td>";
	          //echo "<td rowspan=1>".$this->HtmlAssist->prPrice($p_order['Order']['customer_paid_amount'])."</td>";
            $status = $p_order['Order']['order_status_id'];
            $color="";
            if (($status == 3)||($status == 2)) $color="color:red";
            if ($status == 5) $color="color:green";
	          echo "<td><b style='".$color."'>".$allStatuses[$status]."</b><br/>";
	          echo $allJobTypes[$p_order['Order']['order_type_id']]."</td>";
	          echo "<td>".$p_order['Technician1']['first_name']."<br/>"
	                    .$p_order['Technician2']['first_name']."</td>";
						if ($this->Common->getLoggedUserRoleID() == 6)
							echo "<td rowspan=2><a style='text-decoration:none;color:black;' href='".BASE_URL."/orders/feedbacks_add?id=". $p_order['Order']['id']."'><b>".$p_order['Order']['feedback_quality']."</b><br/>".
												"<b>Notes</b>: ".$p_order['Order']['feedback_comment']."<br/>".
												"<b>Solution</b>: ".$p_order['Order']['feedback_suggestion']."</a></td>";
	          echo "</tr>\n";
	          echo "<tr valign='top' ".$add."  class='".$class."' loopstart='".$loopstart."'>";
	          echo "<td colspan=4>";
						echo '<table cellspacing=0 colspacing=5>';
						//echo '<tr><th>&nbsp;</th><th align=left style="width:200px">Item</th><th>Qty</th><th>Price</th><th>Sum</th></tr>';
						echo '<tr><th>&nbsp;</th><th align=left style="width:200px">Item</th><th>Qty</th><th>Price</th><th>Adj</th></tr>';
            echo $items_text;
	          echo '</table>';
	          echo "</td>";
	          echo "</tr>\n";
	          echo "<tr class='tr_break ".$class."' loopstart='".$loopstart."'><td colspan=8 style=\"background: #AAAAAA; height: 5px;\" ></td></tr>\n";
	          $loopstart++;
	        }
	    }

	    echo "</table>";
	    exit;
	}

  // Method returns an HTML code for the given order's item
  function _itemHTML($index, $item, $actions)
  {
  	$db =& ConnectionManager::getDataSource('default');
		$query = "SELECT active from ace_rp_show_purchase_price WHERE id =1";
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result);
    $h = '';

    //Class==0 means that this item is from the original booking
    //Class==1 - the item was sold by technitian (extra sale)

    $h .= '<td><input type="hidden" id="data[Order][BookingItem]['.$index.'][class]" name="data[Order][BookingItem]['.$index.'][class]" value="'.$item['class'].'"/>';
    $h .= '<input type="hidden" id="data[Order][BookingItem]['.$index.'][item_id]" name="data[Order][BookingItem]['.$index.'][item_id]" value="'.$item['item_id'].'"/>';
    $h .='<input type="hidden" id="data[Order][BookingItem]['.$index.'][model_number]" name="data[Order][BookingItem]['.$index.'][model_number]" value="'.$item['model_number'].'"/>';
    $h .='<input type="hidden" id="data[Order][BookingItem]['.$index.'][brand]" name="data[Order][BookingItem]['.$index.'][brand]" value="'.$item['brand'].'"/>';
    $h .= '<input type="hidden" id="data[Order][BookingItem]['.$index.'][item_category_id]" name="data[Order][BookingItem]['.$index.'][item_category_id]" value="'.$item['item_category_id'].'"/>';


	if ($item['name']=='-custom part-')
		$h .= '<input type="text" id="data[Order][BookingItem]['.$index.'][name]" name="data[Order][BookingItem]['.$index.'][name]" value=""/>';
	elseif ($item['name']=='-custom item-') {
		$h .= '<input type="text" id="data[Order][BookingItem]['.$index.'][name]" name="data[Order][BookingItem]['.$index.'][name]" value=""/>';
	} elseif ($item['name']=='Coupon Promotion')
		$h .= '<input type="text" id="data[Order][BookingItem]['.$index.'][name]" name="data[Order][BookingItem]['.$index.'][name]" value="Coupon REF"/>';

	elseif ($item['item_id']==$this->member_card1_item_id || $item['item_id']==$this->member_card2_item_id) { // Added by Maxim Kudryavtsev - member card booking
		if (strpos($item['name'],'Ace Member')===0) $item['name']='enter number';
		$h .= 'Member Card <input type="text" id="data[Order][BookingItem]['.$index.'][name]" name="data[Order][BookingItem]['.$index.'][name]" value="'.$item['name'].'" onfocus="if (value==\'enter number\') value=\'\';"/><br />';
		$h .= 'Next Service Date <input type="text" id="data[Order][BookingItem]['.$index.'][next_service]" name="data[Order][BookingItem]['.$index.'][next_service]" class="emboss" style="width: 85px; cursor: hand; cursor: pointer;" readonly="1" onclick="scwShow(this,event);" onkeydown="return false;" value="'.( $item['part_number'] ? $item['part_number'] : date('d M Y', strtotime('+11 months') ) ).'">';
	}

	elseif($item['item_id']==1218) {
		if(!empty($item['invoice_image']))
		{
			$imgPath =  '/acesys/app/webroot/purchase-invoice-images/'.$item['invoice_image']; 
			$h.='<img class="invoice-openImg" src="'.$imgPath.'" style="max-height: 100px; max-width: 100%; height: 50px; width: 50px;">';
			$h .= '<input type="hidden" id="data[Order][BookingItem]['.$index.'][invoice_image]" name="data[Order][BookingItem]['.$index.'][invoice_image]" value="'.$item['invoice_image'].'"/>';
		} else
		{
			$h.='<img  class="pre-image_'.$index.' invoice-openImg" src="#" alt="your image" />';
			$h.='<div class="cls-acecare-td-adjust">
			<label for="Fileinput" >Upload Invoice</label>
			<input type="file" name="uploadInvoice['.$index.']" id="Fileinput1" class="disply_preview" data-ct="'.$index.'"></div>';
		}
		$h .= '<div>Description</div><input type="text" id="data[Order][BookingItem]['.$index.'][name]" name="data[Order][BookingItem]['.$index.'][name]" value="'.$item['name'].'"/>';
		$h .= '<div>Model</div><input type="text" id="data[Order][BookingItem]['.$index.'][model_number]" name="data[Order][BookingItem]['.$index.'][model_number]" value="'.$item['model_number'].'"/>';
		$h .= '<div>Serial</div><input type="text" id="data[Order][BookingItem]['.$index.'][serial_number]" name="data[Order][BookingItem]['.$index.'][serial_number]" value="'.$item['serial_number'].'"/>';
		$h .= '<div>Brand</div><input type="text" id="data[Order][BookingItem]['.$index.'][brand]" name="data[Order][BookingItem]['.$index.'][brand]" value="'.$item['brand'].'"/>';
		$h .= '<div>Supplier</div><input type="text" id="data[Order][BookingItem]['.$index.'][supplier]" name="data[Order][BookingItem]['.$index.'][supplier]" value="'.$item['supplier'].'"/>';
	}
	elseif($item['item_id']==1227) {
		if(!empty($item['invoice_image']))
		{
			$imgPath =  '/acesys/app/webroot/purchase-invoice-images/'.$item['invoice_image']; 
			$h.='<img class="invoice-openImg" src="'.$imgPath.'" style="max-height: 100px; max-width: 100%; height: 50px; width: 50px;">';
			$h .= '<input type="hidden" id="data[Order][BookingItem]['.$index.'][invoice_image]" name="data[Order][BookingItem]['.$index.'][invoice_image]" value="'.$item['invoice_image'].'"/>';
		} else
		{
			$h.='<img  class="pre-image_'.$index.' invoice-openImg" src="#" alt="your image" />';
			$h.='<div class="cls-acecare-td-adjust">
			<label for="Fileinput" >Upload Invoice</label>
			<input type="file" name="uploadInvoice['.$index.']" id="Fileinput1" class="disply_preview" data-ct="'.$index.'"></div>';
		}
		$h .= '<div>Description</div><input type="text" id="data[Order][BookingItem]['.$index.'][name]" name="data[Order][BookingItem]['.$index.'][name]" value="'.$item['name'].'"/>';
		$h .= '<div>SKU</div><input type="text" id="data[Order][BookingItem]['.$index.'][serial_number]" name="data[Order][BookingItem]['.$index.'][serial_number]" value="'.$item['serial_number'].'"/>';
		$h .= '<div>Supplier</div><input type="text" id="data[Order][BookingItem]['.$index.'][supplier]" name="data[Order][BookingItem]['.$index.'][supplier]" value="'.$item['supplier'].'"/>';
		$h .= '<div>Supplier Invoice#</div><input type="text" id="data[Order][BookingItem]['.$index.'][invoice]" name="data[Order][BookingItem]['.$index.'][invoice]" value="'.$item['invoice'].'"/>';

	}
	else
		$h .= '<input type="hidden" id="data[Order][BookingItem]['.$index.'][name]" name="data[Order][BookingItem]['.$index.'][name]" value="'.$item['name'].'"/>'.$item['name'];



 //    if (($item['item_category_id']==2 || $item['item_category_id']==3 || $item['item_category_id']==4 || $item['item_category_id']==5 || $item['item_category_id']==6 || $item['item_category_id']==7 || $item['item_category_id']==8 || $item['item_category_id']==10 ) &&(($this->Common->getLoggedUserRoleID() == 6)||($this->Common->getLoggedUserRoleID() == 1))) 
	// {
	// 	$h .= '&nbsp;<select id="data[Order][BookingItem]['.$index.'][installed]" class="is_installed" name="data[Order][BookingItem]['.$index.'][installed]">';
	// 	$h .= '<option value=0 '.($item['installed']==0?'selected':'').'>-Choose-</option>';
	// 	$h .= '<option value=2 '.($item['installed']==2?'selected':'').'>Not installed</option>';
	// 	$h .= '<option value=1 '.($item['installed']==1?'selected':'').'>Installed</option>';

	// 	$h .= '</select>';
	// }

    $h .= '</td>';
	if (($item['item_id']=='0'))
        $h .= '<td class="quantity"><input style="width:50px" type="hidden" id="data[Order][BookingItem]['.$index.'][quantity]" name="data[Order][BookingItem]['.$index.'][quantity]" value="'.$item['quantity'].'"/>'.$item['quantity'].'</td>';
    elseif (($item['name']=='Discount')||(!$actions))
        $h .= '<td class="quantity"><input style="width:50px" type="hidden" id="data[Order][BookingItem]['.$index.'][quantity]" name="data[Order][BookingItem]['.$index.'][quantity]" value="'.$item['quantity'].'"/>'.$item['quantity'].'</td>';
    else
        $h .= '<td class="quantity"><input style="width:50px" type="text" id="data[Order][BookingItem]['.$index.'][quantity]" name="data[Order][BookingItem]['.$index.'][quantity]" value="'.$item['quantity'].'" onkeyup="TotalCalculation()"/></td>';



    if ((($item['item_id']=='1000')||($item['item_id']=='1024')||($item['item_id']=='1218')||($item['item_id']=='1227'))&&$actions)
        $h .= '<td class="price"><input style="width:50px" type="text" id="data[Order][BookingItem]['.$index.'][price]" name="data[Order][BookingItem]['.$index.'][price]" value="'.$item['price'].'" onkeyup="TotalCalculation()"/></td>';
    else if($item['name']=='-custom part-')
    	$h .= '<td class="price"><input style="width:50px" type="text" id="data[Order][BookingItem]['.$index.'][price]" name="data[Order][BookingItem]['.$index.'][price]" value="'.$item['price'].'" onkeyup="TotalCalculation()"/></td>';
    else
        $h .= '<td class="price"><input style="width:50px" type="hidden" id="data[Order][BookingItem]['.$index.'][price]" name="data[Order][BookingItem]['.$index.'][price]" value="'.$item['price'].'"/><span>'.$item['price'].'</span></td>';

    if ($actions&&($item['item_id']!='1000')&&($item['item_id']!='0'))
        $h .= '<td class="discount"><input style="width:50px;color:red;" type="text" id="data[Order][BookingItem]['.$index.'][discount]" name="data[Order][BookingItem]['.$index.'][discount]" value="'.$item['discount'].'" onkeyup="TotalCalculation()"/></td>';
    else
        $h .= '<td class="discount"><input type="hidden" id="data[Order][BookingItem]['.$index.'][discount]" name="data[Order][BookingItem]['.$index.'][discount]" value="'.$item['discount'].'"/><span style="color:red;">'.$item['discount'].'&nbsp;</span></td>';

    if ($actions&&($item['item_id']!='1000')&&($item['item_id']!='0'))
        $h .= '<td class="addition"><input style="width:50px" type="text" id="data[Order][BookingItem]['.$index.'][addition]" name="data[Order][BookingItem]['.$index.'][addition]" value="'.$item['addition'].'" onkeyup="TotalCalculation()"/></td>';
    else
        $h .= '<td class="addition"><input style="width:50px" type="hidden" id="data[Order][BookingItem]['.$index.'][addition]" name="data[Order][BookingItem]['.$index.'][addition]" value="'.$item['addition'].'"/>'.$item['addition'].'&nbsp;</td>';

    //For the parts we have to open the purchase price box
    if (($item['item_id']=='1024' || $item['item_id']=='1227')&&$actions) {
    	if($item['show_purchase'] == 1 || $item['show_purchase'] == '' || $item['show_purchase'] == NULL)
    	{
			$h .= '<td class="purchase"><input style="width:50px" type="text" id="data[Order][BookingItem]['.$index.'][price_purchase]" name="data[Order][BookingItem]['.$index.'][price_purchase]" value="'.$item['price_purchase'].'"/></td>';
		} else {
			$h .='<td></td>';
		}
	} else if($item['name']=='-custom part-'){
			if($item['show_purchase'] == 1 || $item['show_purchase'] == '' || $item['show_purchase'] == NULL)
	    	{
				$h .= '<td class="purchase"><input style="width:50px" type="text" id="data[Order][BookingItem]['.$index.'][price_purchase]" name="data[Order][BookingItem]['.$index.'][price_purchase]" value="'.$item['price_purchase'].'" onkeyup="TotalCalculation()"/></td>';
			} else {
				$h .='<td></td>';
			}
		} 
	else {
		if(($this->Common->getLoggedUserRoleID() == 6)) {
			if($item['show_purchase'] == 1 || $item['show_purchase'] == '' || $item['show_purchase'] == NULL){
				$h .= '<td class="purchase"><input style="width:50px" type="hidden" id="data[Order][BookingItem]['.$index.'][price_purchase]" name="data[Order][BookingItem]['.$index.'][price_purchase]" value="'.$item['price_purchase'].'"/><span>'.$item['price_purchase'].'</span></td>';
			} else {
				$h .='<td></td>';
			}
		}
		else {
			if($item['show_purchase'] == 1 || $item['show_purchase'] == '' || $item['show_purchase'] == NULL) {
				$h .= '<td class="purchase"><input style="width:50px" type="hidden" id="data[Order][BookingItem]['.$index.'][price_purchase]" name="data[Order][BookingItem]['.$index.'][price_purchase]" value="'.$item['price_purchase'].'"/>&nbsp;</td>';
			} else {
				$h .='<td></td>';
			}
		}
	}
    $h .= '<td class="amount" id="data[Order][BookingItem]['.$index.'][amount]">'.$this->HtmlAssist->prPrice(round($item['quantity']*$item['price']-$item['discount']+$item['addition'],2)).'</td>';

 //    if ($this->Common->getLoggedUserRoleID() == 6)
	// {
	// 	$h .= '<td style="background:#00bb00;"><input style="width:50px;color:red;background:#c4ffc4;" type="text" id="data[Order][BookingItem]['.$index.'][tech_minus]" name="data[Order][BookingItem]['.$index.'][tech_minus]" value="'.$item['tech_minus'].'"/></td>';
	// 	$h .= '<td style="background:#00bb00;"><input style="width:50px;background:#c4ffc4;" type="text" id="data[Order][BookingItem]['.$index.'][tech]" name="data[Order][BookingItem]['.$index.'][tech]" value="'.$item['tech'].'"/></td>';
	// }
 //    elseif ($this->Common->getLoggedUserRoleID() == 1)
	// {
	// 	$h .= '<td style="background:#55bb55;"><input style="width:50px;color:red;background:#c4ffc4;" type="hidden" id="data[Order][BookingItem]['.$index.'][tech_minus]" name="data[Order][BookingItem]['.$index.'][tech_minus]" value="'.$item['tech_minus'].'"/>'.$item['tech_minus'].'</td>';
	// 	$h .= '<td style="background:#55bb55;"><input style="width:50px;background:#c4ffc4;" type="hidden" id="data[Order][BookingItem]['.$index.'][tech]" name="data[Order][BookingItem]['.$index.'][tech]" value="'.$item['tech'].'"/>'.$item['tech'].'</td>';
	// }

	// if ($actions)
	// {
	// 	$checked = '';
	// 	if ($item['print_it']=='on') $checked = '"checked"';
	// 	$h .= '<td><input type="checkbox" id="data[Order][BookingItem]['.$index.'][print_it]" name="data[Order][BookingItem]['.$index.'][print_it]" '.$checked.'/></td>';
	// 	$h .= '<td><img onclick="removeItem('.$index.')" src="'.ROOT_URL.'/app/webroot/img/icon-vsm-delete.png"/></td>';
	// }
    $h .= '<td style="width:22px"><img onclick="removeItem('.$index.')" src="'.ROOT_URL.'/app/webroot/img/icon-vsm-delete.png"/></td>';
    return $h;
  }

  // Method returns an HTML code for a new item for the given order.
  // AJAX version
  function newItemHTML()
  {
    $index=$_GET['index'];
    $actions=$_GET['actions'];
    $item['item_id']=$_GET['item_id'];
    $item['name']=$_GET['name'];
    $item['price']=$_GET['price'];
    $item['quantity']=$_GET['quantity'];
    $item['class']=$_GET['item_class'];
    $item['item_category_id']=$_GET['category'];
    $item['price_purchase']=$_GET['price_purchase'];
    $item['print_it']='on';
    $item['show_purchase']= $_GET['show_purchase'];
    $item['model_number']= $_GET['model_number'];
    $item['brand']= $_GET['brand'];
    echo $this->_itemHTML($index, $item, $actions);
    exit;
  }

  // AJAX. Method returns the list of the given order's items in an HTML format.
  // Created: Anthony Chernikov, 08/25/2010
  function getItems()
  {
    $order_id = $_REQUEST['order_id'];
    $items_type = $_REQUEST['items_type'];
    $enable_actions = $_REQUEST['enable_actions'];

    $this->Order->id = $order_id;
    $orderData = $this->Order->read();

    echo $this->_getItems($orderData, $items_type, $enable_actions);
    exit;
  }

  // Method returns the list of the given order's items in an HTML format.
  // Created: Anthony Chernikov, 08/25/2010
  function _getItems(&$orderData, $items_type, $enable_actions)
  {
    $num_items = 0;
    $h_booked='';
    $h_tech='';
    foreach ($orderData['BookingItem'] as $oi)
    {
      if ($oi['class']==0)
      {
        $h_booked .= '<tr id="order_'.$num_items.'" class="booked">';
        $h_booked .= $this->_itemHTML($num_items, $oi, $enable_actions);
        $h_booked .= '</tr>';
      }
      else
      {
        $h_tech .= '<tr id="order_'.$num_items.'" class="extra">';
        $h_tech .= $this->_itemHTML($num_items, $oi, $enable_actions);
        $h_tech .= '</tr>';
      }
      $num_items++;
    }
    foreach ($orderData['BookingCoupon'] as $oi)
    {
      $oi['price'] = 0-$oi['price'];
      $oi['quantity'] = 1;
      $oi['name'] = 'Discount';
      $h_booked .= '<tr id="order_'.$num_items.'" class="booked">';
      $h_booked .= $this->_itemHTML($num_items, $oi, $enable_actions);
      $h_booked .= '</tr>';
      $num_items++;
    }

    if ($items_type == 0)
        $Ret = $h_booked;
    elseif ($items_type == 1)
        $Ret = $h_tech;
    else
        $Ret = array('h_booked' => $h_booked,
                     'h_tech' => $h_tech,
                     'num_items' => $num_items);

    return $Ret;
  }

 	// Method draws the table for this booking detalizaion
	// Created: Anthony Chernikov, 06/2010
	function _showQuestions($order_id,$question_type,$job_type,$strStyle)
	{
		if (!$strStyle) $strStyle='class="inResultsBooking"';
    $h .= '
    <table id="DetailsTable" cellspacing=0 colspacing=0 ' .$strStyle .'>
    <tr>
      <th>#</th>
      <th>Question / Hint</th>
      <th>Answer</th>
      <th>&nbsp</th>
		</tr>';

    $db =& ConnectionManager::getDataSource($this->User->useDbConfig);

    if ($question_type==0) $condition = "for_office=1 and";
    else $condition = "for_office=0 and";

		// If $order_id is set - ignore $job_type.
    $are_here=false;
    if ($order_id)
    {
        $query="select count(*) cnt from ace_rp_orders_questions where $condition order_id='$order_id'";
        $result = $db->_execute($query);
        $row = mysql_fetch_array($result,MYSQL_ASSOC);
        if ($row['cnt']!=0) $are_here=true;
    }

    $for_tech = true;
    if (($this->Common->getLoggedUserRoleID() == "3")
      ||($this->Common->getLoggedUserRoleID() == "9")
	  ||($this->Common->getLoggedUserRoleID() == "13"))
    {
    	$for_tech = false;
    }

    if ($are_here)
        $query="select * from ace_rp_orders_questions where $condition order_id=$order_id order by question_number";
    elseif ($job_type)
        $query="select * from ace_rp_order_types_questions where $condition order_type_id=$job_type order by question_number";
    else return '';

    $index = 0;
    $result = $db->_execute($query);
    while ($row = mysql_fetch_array($result,MYSQL_ASSOC))
    {
        if (0+$row['for_office']==0)
        {
            if ($row['local_answer']=='') $row['local_answer']=' ';
            if ($for_tech)
                $ttt = '';
            else
                $ttt = 'style="display:none"';
        }

        $h .= '<tr '.$ttt.' id="question_'.$index.'">';
        $h .= '<td style="color:#000000" rowspan=2><input type="hidden" name="data[Order][OrdersQuestions]['.$index.'][id]" id="data[Order][OrdersQuestions]['.$index.'][id]" value="'.$row['id'].'"/>';
        $h .= '<input type="hidden" name="data[Order][OrdersQuestions]['.$index.'][for_office]" id="data[Order][OrdersQuestions]['.$index.'][for_office]" value="'.$row['for_office'].'"/>';
        $h .= '<input type="hidden" name="data[Order][OrdersQuestions]['.$index.'][for_tech]" id="data[Order][OrdersQuestions]['.$index.'][for_tech]" value="'.$row['for_tech'].'"/>';
        $h .= '<input type="hidden" name="data[Order][OrdersQuestions]['.$index.'][question_id]" id="data[Order][OrdersQuestions]['.$index.'][question_id]" value="'.$row['question_id'].'"/>';
        $h .= '<input type="hidden" name="data[Order][OrdersQuestions]['.$index.'][question_number]" id="data[Order][OrdersQuestions]['.$index.'][question_number]" value="'.$row['question_number'].'"/>'.$row['question_number'].'</td>';
        $h .= '<td style="color:#000000"><input type="hidden" name="data[Order][OrdersQuestions]['.$index.'][question]" id="data[Order][OrdersQuestions]['.$index.'][question]" value="'.$row['question'].'"/>&nbsp;'.$row['question'].'</td>';
        $h .= '<td style="color:#000000" rowspan=2 colspan=2><input name="data[Order][OrdersQuestions]['.$index.'][local_answer]" id="data[Order][OrdersQuestions]['.$index.'][local_answer]" value="'.$row['local_answer'].'"/></td>';
        $h .= '</tr>';
        $h .= '<tr '.$ttt.' id="question_'.$index.'">';
        $h .= '<td style="color:#550000"><input type="hidden" name="data[Order][OrdersQuestions]['.$index.'][answers]" id="data[Order][OrdersQuestions]['.$index.'][answers]" value="'.$row['answers'].'"/>&nbsp;'.$row['answers'].'</td>';
        $h .= '</tr>';
        $index++;
    }

		$h .= '</table>';

		return $h;
	}



	// Method draws the table for this booking detalizaion (version for AJAX)
	// Created: Anthony Chernikov, 06/2010
	function showQuestions()
	{
    $order_id=$_GET['order_id'];
    $question_type=$_GET['question_type'];
    $job_type=$_GET['job_type'];
    $strStyle=$_GET['strStyle'];

		$h = $this->_showQuestions($order_id,$question_type,$job_type,$strStyle);

		echo $h;
    exit;
	}

	function showTabletQuestions() {
		$this->layout = "blank";

		$order_id = $_GET['order_id'];
    	$question_type = $_GET['question_type'];
		$job_type = $_GET['job_type'];
		$strStyle = $_GET['strStyle'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);



		if ($order_id) {
			$query="select count(*) cnt from ace_rp_orders_questions where $condition order_id='$order_id'";
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result,MYSQL_ASSOC);
			if ($row['cnt']!=0) $are_here=true;

			$query = "
				SELECT *
				FROM ace_rp_orders
				WHERE id = $order_id
			";

			$result = $db->_execute($query);
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$temp_order_type_id = $row['order_type_id'];
			}

			$query = "
				SELECT *
				FROM ace_rp_orders_questions
				WHERE order_id = $order_id
			";

			$result = $db->_execute($query);

			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$order_answers[$row['question_id']]['local_answer'] = $row['local_answer'];
				$order_answers[$row['question_id']]['answers'] = $row['answers'];
				$order_answers[$row['question_id']]['suggestions'] = $row['suggestions'];
				$order_answers[$row['question_id']]['response'] = $row['response'];

				$order_answers[$row['question']]['local_answer'] = $row['local_answer'];
				$order_answers[$row['question']]['answers'] = $row['answers'];
				$order_answers[$row['question']]['suggestions'] = $row['suggestions'];
				$order_answers[$row['question']]['response'] = $row['response'];
			}

			$this->set('order_answers',$order_answers);

		} else {
			$temp_order_type_id = $job_type;
		}

		$query = "
			SELECT *
			FROM ace_rp_order_types_questions
			WHERE order_type_id = $temp_order_type_id
		";

		$result = $db->_execute($query);

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if($question_type) {
				if($row['for_tech'] == 1) $questions[$row['id']]['display'] = '';
				else $questions[$row['id']]['display'] = 'style="display:none;"';
			} else {
				if($row['for_office'] == 1) $questions[$row['id']]['display'] = '';
				else $questions[$row['id']]['display'] = 'style="display:none;"';
			}

			$questions[$row['id']]['order_type_id'] = $row['order_type_id'];
			$questions[$row['id']]['for_office'] = $row['for_office'];
			$questions[$row['id']]['for_tech'] = $row['for_tech'];
			$questions[$row['id']]['for_print'] = $row['for_print'];
			$questions[$row['id']]['question'] = $row['question'];
			$questions[$row['id']]['suggestions'] = $row['suggestions'];
			$questions[$row['id']]['response'] = $row['response'];
			$questions[$row['id']]['question_number'] = $row['question_number'];
			if(trim($row['answers']) != "") {
				$questions[$row['id']]['answers'] = explode(",", $row['answers']);
			} else {
				$questions[$row['id']]['answers'] = "text";
			}
			if(trim($row['suggestions']) != "") {
				$questions[$row['id']]['suggestions'] = explode(",", $row['suggestions']);
			} else {
				$questions[$row['id']]['suggestions'] = "text";
			}
			if(trim($row['response']) != "") {
				$questions[$row['id']]['response'] = explode(",", $row['response']);
			} else {
				$questions[$row['id']]['response'] = "text";
			}
		}


		$this->set('questions',$questions);
	}

	function showTemplateQuestions() {
		$this->layout = "blank";
		$order_id = $_GET['order_id'];
		$question_type = $_GET['question_type'];
		$customer_id = $_GET['customer_id'];
		if($question_type == 2) {
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

			$query = "
				SELECT *
				FROM ace_rp_orders_questions_final fn
				LEFT JOIN ace_rp_questions q
				ON fn.question_number = q.id
				WHERE fn.order_id = $order_id
				ORDER BY fn.id, fn.question_number, fn.date_saved
			";

			$questions = array();
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				foreach ($row as $k => $v)
				  $questions[$row['id']][$k] = $v;
			}

			$this->set('questions', $questions);
			$this->set('mode', 1);

		} else {
			if($question_type == 0) {
				$search_in_questions = "AND q.for_office = 1";
			} else {
				$search_in_questions = "AND q.for_tech = 1";
			}
			$order_type_id = $_GET['job_type'];
			$strStyle = $_GET['strStyle'];

			$this->set('order_id', $order_id);

			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

			$item_id = $order_type_id;

			$query = "
				SELECT *
				FROM ace_rp_questions q
				WHERE order_type_id = $item_id
				AND q.id IS NOT NULL
				$search_in_questions
				order by rank, value
			";

			$questions = array();
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				foreach ($row as $k => $v)
				  $questions[$row['id']][$k] = $v;
			}

			$query = "
				SELECT r.*
				FROM ace_rp_questions q
				LEFT JOIN ace_rp_responses r
				ON q.id = r.question_id
				WHERE q.order_type_id = $item_id
				AND r.id IS NOT NULL
				$search_in_questions
			";

			$responses = array();
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				foreach ($row as $k => $v)
				  $responses[$row['question_id']][$row['id']][$k] = $v;
			}

			$query = "
				SELECT s.*
				FROM ace_rp_questions q
				LEFT JOIN ace_rp_responses r
				ON q.id = r.question_id
				LEFT JOIN ace_rp_suggestions s
				ON r.id = s.response_id
				WHERE q.order_type_id = $item_id
				AND s.id IS NOT NULL
				$search_in_questions
			";
			$suggestions = array();
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				foreach ($row as $k => $v)
				  $suggestions[$row['response_id']][$row['id']][$k] = $v;
			}

			$query = "
				SELECT d.*
				FROM ace_rp_questions q
				LEFT JOIN ace_rp_responses r
				ON q.id = r.question_id
				LEFT JOIN ace_rp_suggestions s
				ON r.id = s.response_id
				LEFT JOIN ace_rp_decisions d
				ON s.id = d.suggestion_id
				WHERE q.order_type_id = $item_id
				AND d.id IS NOT NULL
				$search_in_questions
			";

			$decisions = array();
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				foreach ($row as $k => $v)
				  $decisions[$row['suggestion_id']][$row['id']][$k] = $v;
			}

			//set answers
			if(isset($order_id) && trim($order_id) !="" ) {

				$query = "
					SELECT *
					FROM ace_rp_orders_questions_working
					WHERE order_id = $order_id
				";

				$working_answers = array();

				$result = $db->_execute($query);
				//$row = mysql_fetch_array($result, MYSQL_ASSOC);
				while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				{
					foreach ($row as $k => $v)
						$working_answers[$row['question_id']][$k] = $v;
				}

				$this->set('working_answers', $working_answers);

			}

			//set carried over answers
			if(isset($customer_id) && trim($customer_id) !="" ) {
				$query = "
					SELECT qw.*
					FROM ace_rp_orders_questions_working qw
					LEFT JOIN ace_rp_questions q
					ON qw.question_id = q.id
					WHERE qw.order_id = (SELECT id
						FROM ace_rp_orders
						WHERE customer_id = $customer_id
						AND order_status_id IN (5,3,1)
						ORDER BY job_date DESC, order_status_id DESC
						LIMIT 1)
					AND q.is_permanent = 1
				";

				$result = $db->_execute($query);

				while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					foreach ($row as $k => $v)
					  $carried_answers[$row['question_id']][$k] = $v;
				}

				$this->set('carried_answers', $carried_answers);
			}
           
           $query = "
			SELECT m.* 
			FROM ace_rp_questions q
			LEFT JOIN ace_rp_responses r
			ON q.id = r.question_id
			LEFT JOIN ace_rp_suggestions s
			ON r.id = s.response_id
			LEFT JOIN ace_rp_decisions d
			ON s.id = d.suggestion_id
            LEFT JOIN ace_rp_reminders m
			ON d.id = m.decision_id
			WHERE q.order_type_id = $item_id
		";
		
		$reminders = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			$reminders[$row['decision_id']][$k] = $v;
			 //$reminders[$row['id']][$k] = $v;
			  //$reminders[$row['decision_id']]=$row['value'];
		}

			$this->set('questions', $questions);
			$this->set('responses', $responses);
			$this->set('suggestions', $suggestions);
			$this->set('decisions', $decisions);
			$this->set('reminders', $reminders);
			$this->set('mode', 0);
		}
	}

    //Method loads customer notes
	function customer_notes(){
		$customer_id = 0;
		$customer_notes = array();
		$customer_name = '';

		if($_GET['customer_id'] != '' && $_GET['customer_id'] != 0){
			$customer_id = $_GET['customer_id'];
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$result = $db->_execute("
			SELECT * FROM ace_rp_users_notes WHERE user_id=".$customer_id." ORDER BY note_date DESC");
			while ($row = mysql_fetch_array($result)) {
				$customer_notes[$row['id']] = $row;
			}

			$this->Customer->id = $customer_id;
			$customer_details = $this->User->read();
			$customer_name = $customer_details['User']['first_name'].' '.$customer_details['User']['last_name'];
		}

		$this->set('customer_id',$customer_id);
		$this->set('customer_name',$customer_name);
		$this->set('customer_notes',$customer_notes);
	}

	function customer_notes_add_ajax(){
		//Get Parameters
		$return_arr = array();

		if(isset($_GET['customer_id'])){
			$customer_id = $_GET['customer_id'];
			if($customer_id > 0) {
				$note = $_GET['note'];

				$loggedUserId = $this->Common->getLoggedUserID();
				$this->User->id = $loggedUserId;
				$loggedUser_details = $this->User->read();

				//Insert Note
				$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
				$result = $db->_execute("INSERT INTO ace_rp_users_notes (user_id,note,note_date,created_by)
                                VALUES(".$customer_id.",'".str_replace("'","`",$note)."',now(),'".$loggedUser_details["User"]["first_name"].' '.$loggedUser_details["User"]["last_name"]."')");

				$return_arr += array('date'=>date("d-M-y H:i:s"));
				$return_arr += array('note'=>$note);
				$return_arr += array('created_by'=>$loggedUser_details["User"]["first_name"].' '.$loggedUser_details["User"]["last_name"]);
			}
		}

		print json_encode($return_arr);
		exit;
	}

	function clients(){

		$recordsCount = 0;
		$number_of_call_made = 0;
		$number_of_sales = 0;
		$number_of_call_back = 0;
		$number_of_dnc = 0;

		//CUSTOM PAGING
		//*************s
		$itemsCount = 20;
		$currentPage = 0;
		$previousPage = 0;
		$nextPage = 1;

		if(isset($_GET['page'])){
			if(is_numeric($_GET['page'])){
				$currentPage = $_GET['page'];
			}
		}
		$sqlPaging = " LIMIT 0,".$itemsCount;
		if($currentPage > 0){
			$firstItem = ($currentPage*$itemsCount); //($currentPage*$itemsCount)+1;
			$sqlPaging = " LIMIT ".$firstItem.",".$itemsCount;

			$previousPage = $currentPage -1;
			$nextPage = $currentPage +1;
		}
		//********************
		//END OF CUSTOM PAGING

		//**********
		//CONDITIONS
		//Convert date from date picker to SQL format
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		$SORT_ASC = '&darr;';//'&nbsp;<span class="sortarrow">&Uacute;</span>';
		$SORT_DESC = '&uarr;'; //'&nbsp;<span class="sortarrow">&Ugrave;</span>';

		$sqlOrder = '';
		$sqlSort = $sort;
		switch ( $order ) {
			case 'slastcall_date' :
			$sqlOrder = 't_u.lastcall_date';
			$this->set('slastcall_date',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'scallback_date' :
			$sqlOrder = 't_u.callback_date';
			$this->set('scallback_date',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'sjob_date' :
			$sqlOrder = 't_o.job_date';
			$this->set('sjob_date',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'scity' :
			$sqlOrder = 't_u.city';
			$this->set('scity',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'sjob_type' :
			$sqlOrder = 't_ot.name';
			$this->set('sjob_type',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;

			default :
			$sqlOrder = 't_o.job_date';
			$sqlSort = 'ASC';
			$this->set('sjob_date',$SORT_DESC);
			break;
		}
		$sqlOrder .= ' '.$sqlSort;

		if ($this->params['url']['ffromdate'] != '')
			$this->params['url']['ffromdate'] = date("Y-m-d", strtotime($this->params['url']['ffromdate']));

		if ($this->params['url']['ftodate'] != '')
			$this->params['url']['ftodate'] = date("Y-m-d", strtotime($this->params['url']['ftodate']));

		//Pick today's date if no date
		$fdate = ($this->params['url']['ffromdate'] != '' ? $this->params['url']['ffromdate']: "" ) ;
		$tdate = ($this->params['url']['ftodate'] != '' ? $this->params['url']['ftodate']: "" ) ;
		$city = $this->params['url']['fcity'];
		$jobtype = $this->params['url']['fjobtype'];
		$telem_id = $this->params['url']['ftelem'];
		$filteritems = $this->params['url']['ffilteritems'];//0-all;2-Callback;3-DNC(do not call);4-Not interested;
		//CONDITIONS
		//**********

		$sqlConditions = '';
		$db =& ConnectionManager::getDataSource('default');
		if($fdate != '')
			$sqlConditions .= " AND t_o.job_date >= '".$this->Common->getMysqlDate($fdate)."'";
		if($tdate != '')
			$sqlConditions .= " AND t_o.job_date <= '".$this->Common->getMysqlDate($tdate)."'";
		if($city != ''){
			$sqlConditions .= " AND t_u.city LIKE '%".$city."%' ";
		}
		if($telem_id > 0){
			$sqlConditions .= " AND s.booking_source_id=".$telem_id;
		}
		//If user is Limited Telemarketer - role id=9
		//then show only orders that belongs to him
		if (($this->Common->getLoggedUserRoleID() == 9) || ($this->Common->getLoggedUserRoleID() == 3)
			|| ($this->Common->getLoggedUserRoleID() == 13))
		{
			$sqlConditions.= " AND t_o.booking_source_id=".$this->Common->getLoggedUserID();
		}

		$Sign = " != ";
		if($filteritems == 2){// Callback - show all records that have lastcall_date=current date, because when we click on callback date option then we update last call date to current
			$sqlConditions .= " AND t_u.callback_date='".date("Y-m-d")."'";
		}
		else if($filteritems == 3){ //Do not Call
			$Sign = " = ";
		}
		else if($filteritems == 4){// Not Interested
			$sqlConditions .= " AND t_u.callresult=1 ";
		}
		$sqlConditions .= " AND t_u.callresult".$Sign."2 ";

//		if($filteritems != 3){ //Do not Call - do not show DNC records in main query
//			$sqlConditions .= " AND t_u.callresult<>2 ";
//		}

		//GET TOTAL RECORDS
		$query = "SELECT DISTINCT
				t_u.id, t_u.first_name as u_fn, t_u.last_name as u_ln,
				t_u.phone as customer_phone, t_u.callback_date,
				t_u.lastcall_date,t_u.callresult, t_o.order_status_id,
				CONCAT(t_u.address_unit,', ',t_u.address_street_number,', ',t_u.address_street) as customer_address,
				t_u.city as customer_city
			    FROM
				ace_rp_customers t_u
			    INNER JOIN ace_rp_orders t_o on t_u.id = t_o.customer_id
			    WHERE (t_o.job_date > DATE('1900-01-01'))
			      AND (t_o.order_status_id IN (1,5)) ".$sqlConditions;

		//echo $query."<br/><br/>";
		$recordsCount = 0;
		$number_of_call_made = 0;
		$number_of_sales = 0;
		$number_of_call_back = 0;
		$number_of_dnc = 0;
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$recordsCount++;
			if($row['callresult'] == "0")
			{
				//If callback_date e > current date, togava trqbva da selektiram callback option-a
				$_callback_date = $row['callback_date'];
				$_current_date = date("Y-m-d");
				if(strtotime($_callback_date) > $_current_date)
				{
					$number_of_call_back++;
				}
				else
				{
					$number_of_sales++;
				}
			}
			else if ($row['callresult'] == "2") $number_of_dnc++;
		}

		//otnosno
		//shte trqbva new table v koqto da se pazaqt broi za vsqko - poneje moje za edin zapis (user) da se napravqt nqkolko call_made, etc.
		//pri update da vnimavam za concurency

		//******************************

		//GET RECORDS PER LIST
		$orders = array();
		//SELECT ONLY BOOKED(1) and DONE(5) orders:
		$query = "SELECT DISTINCT
				t_u.id, t_u.first_name as u_fn, t_u.last_name as u_ln,
				t_u.phone as customer_phone, t_u.callback_date,
				t_u.lastcall_date,t_u.callresult, t_o.order_status_id,
				CONCAT(t_u.address_unit,', ',t_u.address_street_number,', ',t_u.address_street) as as customer_address,
				t_u.city as customer_city,
				t_tu.first_name as tu_fn, t_tu.last_name as tu_ln
			    FROM
				ace_rp_customers t_u
			    LEFT JOIN ace_rp_users t_tu ON (t_u.telemarketer_id=t_tu.id)
			    INNER JOIN ace_rp_orders t_o on t_u.id = t_o.customer_id
			    LEFT JOIN ace_rp_order_types t_ot on (t_o.order_type_id = t_ot.id)
			    WHERE (t_o.job_date > DATE('1900-01-01')) AND (t_o.order_status_id IN (1,5)) ".$sqlConditions."
			    ORDER BY ".$sqlOrder.' '.$sqlPaging;

		//echo $query;
		//exit;
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$orders[$row['id']]['customer_id'] = $row['id'];
			$orders[$row['id']]['customer_name'] = $row['u_fn'].' '.$row['u_ln'];
			$orders[$row['id']]['customer_callback_date'] = $row['callback_date'];
			$orders[$row['id']]['customer_lastcall_date'] = $row['lastcall_date'];
			$orders[$row['id']]['order_status_id'] = $row['order_status_id'];

			if($row['callresult'] == "0")
			{
				//If callback_date e > current date, togava trqbva da selektiram callback option-a
				$_callback_date = $row['callback_date'];
				$_current_date = date("Y-m-d");
				if(strtotime($_callback_date) > $_current_date){
					$orders[$row['id']]['customer_callback_result'] = 'cb';
				}
			}
			else
			{
				$orders[$row['id']]['customer_callback_result'] = $row['callresult'];
			}

			$orders[$row['id']]['customer_phone'] = $row['customer_phone'];
			$orders[$row['id']]['customer_address'] = $row['customer_address'];
			$orders[$row['id']]['customer_city'] = $row['customer_city'];
			$orders[$row['id']]['customer_telemarketer'] = $row['tu_fn'].' '.$row['tu_ln'];

			//2. Get order details
			/*$orders[$row['id']]['id'] = $row['id'];
			//$orders[$row['id']]['order_type_id'] = $row['order_type_id'];
			$orders[$row['id']]['order_type_name'] = $row['order_type_name'];
			$orders[$row['id']]['job_date'] = $row['job_date'];
			$orders[$row['id']]['customer_paid_amount'] = $row['customer_paid_amount'];*/

			$orders[$row['id']]['has_feedback'] = $row_order['feedback_callback_date'] != '' ? 1 : 0;
			$query_order = "SELECT t_o.id,t_o.job_date,t_ot.name as order_type_name,t_o.customer_paid_amount,
						t_o.feedback_callback_date
					FROM
					    ace_rp_orders t_o
					LEFT JOIN ace_rp_order_types t_ot on (t_o.order_type_id = t_ot.id)

					WHERE
					     (t_o.job_date > DATE('1900-01-01'))
					      AND
					      t_o.customer_id=".$row['id']."
					ORDER BY t_o.job_date desc
					LIMIT 0,1";
			$result_order = $db->_execute($query_order);
			while($row_order = mysql_fetch_array($result_order)) {
				$orders[$row['id']]['id'] = $row_order['id'];
				//$orders[$row['id']]['order_type_id'] = $row_order['order_type_id'];
				$orders[$row['id']]['order_type_name'] = $row_order['order_type_name'];
				$orders[$row['id']]['job_date'] = $row_order['job_date'];
				$orders[$row['id']]['customer_paid_amount'] = $row_order['customer_paid_amount'];

				$orders[$row['id']]['has_feedback'] = $row_order['feedback_callback_date'] != '' ? 1 : 0;

			}

		}

		//LOAD MAIN DATA
		$this->set("previousPage",$previousPage);
		$this->set("nextPage",$nextPage);
		$this->set("orders", $orders);

		if($fdate!='')
			$this->set('fdate', date("d M Y", strtotime($fdate)));
		if($tdate!='')
			$this->set('tdate', date("d M Y", strtotime($tdate)));
		$this->set("city", $city);
		$this->set("jobtype", $jobtype);
		$this->set("telem_id", $telem_id);
		$this->set("filteritems", $filteritems);

		$this->set('recordsCount',$recordsCount);
		$this->set('number_of_call_made',$number_of_call_made);
		$this->set('number_of_sales',$number_of_sales);
		$this->set('number_of_call_back',$number_of_call_back);
		$this->set('number_of_dnc',$number_of_dnc);
		//Load Job Types
		//**************
		$jobtypes = $this->OrderType->findAll(null, null, array("name ASC"));
		$this->set('jobtypes', $jobtypes);
		//List all Telemarketers
		//**********************
		$telems = array();
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$result = $db->_execute("
		SELECT a.id,CONCAT(a.first_name,' ',a.last_name) as name
		FROM ace_rp_users as a,ace_rp_users_roles as b
		WHERE a.is_active=1 AND a.id = b.user_id and b.role_id in (3,9)
		ORDER BY name");
		while ($row = mysql_fetch_array($result)) {
			$telems[] = $row;
		}
		$this->set('allTelems',$telems);

		//**************************
		$this->set('call_results', $this->HtmlAssist->table2array($this->CallResult->findAll(), 'id', 'name'));
		$this->set('booking_sources', $this->HtmlAssist->table2array($this->Order->Source->execute('SELECT ace_rp_users_roles.role_id as role_id, ace_rp_users.id as id, ace_rp_users.first_name as first_name, ace_rp_users.last_name as last_name FROM ace_rp_users, ace_rp_users_roles WHERE ace_rp_users.is_active=1 AND ace_rp_users.id=ace_rp_users_roles.user_id AND (ace_rp_users_roles.role_id=1 OR ace_rp_users_roles.role_id=3 OR ace_rp_users_roles.role_id=7 OR ace_rp_users_roles.role_id=9) ORDER BY ace_rp_users_roles.role_id DESC, ace_rp_users.first_name'), 'id', 'first_name', 'ace_rp_users_roles', 'role_id'));

	}

    function saveCallRecord()
    {
    	$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
        $customer_id = $_GET['customer_id'];
        if ($customer_id)
        {
            $this->data['Customer']['id'] = $customer_id;
        }
   		$selected_button    = isset($_GET['selected_button']) ? $_GET['selected_button'] : 0;

        $this->data['Customer']['campaign_id']    			= isset($_GET['customer_campaing_id']) ? $_GET['customer_campaing_id'] : '';
        $this->data['Customer']['first_name']    			= $_GET['customer_first_name'];
        $this->data['Customer']['last_name']     			= $_GET['customer_last_name'];
        $this->data['Customer']['email']         			= $_GET['customer_email'];
        $this->data['Customer']['address_unit']  			= $_GET['customer_address_unit'];
        $this->data['Customer']['address_street_number'] = $_GET['customer_address_street_number'];
        $this->data['Customer']['address_street'] 			= $_GET['customer_address_street'];
        $this->data['Customer']['city']           			= $_GET['customer_city'];
        $this->data['Customer']['postal_code']    			= $_GET['customer_postcode'];
        $this->data['Customer']['phone'] 		  			   = $_GET['customer_phone'];
        $this->data['Customer']['cell_phone'] 	  			= $_GET['customer_cell_phone'];
        $this->data['Customer']['card_number'] 	  			= $_GET['customer_card_number'];
        $this->data['Customer']['card_exp'] 	  			   = $_GET['customer_card_exp'];
        $this->data['Customer']['callback_date']   		= $_GET['callback_date'];
        $this->data['Customer']['callback_time_hour']   	= $_GET['callback_time_hour'];
        $this->data['Customer']['call_user']  				= $_GET['call_user'];
        $this->data['Customer']['callback_user']  			= $_GET['callback_user'];
        $this->data['Customer']['referred_by_existing_userid'] = $_GET['call_user'];
        $this->data['Customer']['lastcall_date']   				= $_GET['call_date'];
        $this->data['Customer']['next_service']   					= $_GET['customer_next_service'];
        
        $this->data['txt_customer_note'][]   						= $_GET['txt_customer_note'];
        // echo json_encode($this->data['Customer']);die;
        $this->_SaveCustomer();
        if (!$customer_id)
        {
            $customer_id = $this->data['Customer']['id'];
        }
        $query = "UPDATE ace_rp_all_campaigns set show_default =".$selected_button." where call_history_ids = ".$customer_id;
       	$db->_execute($query);
  //       $query = "select * from ace_rp_call_history ach WHERE ach.id IN (SELECT MAX(id) FROM ace_rp_call_history WHERE customer_id = $customer_id)";
		// $result = $db->_execute($query);

		// while($row = mysql_fetch_assoc($result))
		// {
		// 	$his_customer_id = $row['customer_id'];
		//     if($row['call_result_id'] != $_GET['callresult'] || $row['callresult'] != 6){
		// 	  	$query = "update ace_rp_customers set campaign_id = NULL WHERE id = $his_customer_id";
		// 		$db->_execute($query);
		// 		$up_query = "update ace_rp_orders set o_campaign_id = NULL WHERE customer_id = $his_customer_id";
		// 		$db->_execute($up_query);
		// 		$query = "update ace_rp_call_history as arc set arc.call_campaign_id = NULL WHERE customer_id = $his_customer_id";
		// 		$db->_execute($query);
		//     }
		// }
       
        if($_GET['savedata']==0) {
        	  $this->AddCallToHistory(
            $customer_id,
            $_GET['call_user'],
            $_GET['callresult'],
            $_GET['callback_reason'],
            $_GET['callback_date'],
            $_GET['callback_time'],
            $_GET['call_id'],
            $_GET['dialer_id'],
            $_GET['callback_user']
        );
        
        }
      
        if($_GET['savedata']==1) {
        $this->Addquestions(
            $customer_id,
            $_GET['call_user'],
            $_GET['callresult'],
            $_GET['callback_reason'],
            $_GET['callback_date'],
            $_GET['callback_questionsids'],
            $_GET['call_id'],
            $_GET['dialer_id'],
            $_GET['callback_user']
            
        );
      }
        echo $customer_id;
        exit;
    }

    //AJAX method for the saving of customer's data only
    function saveCustomerData()
    {
        $customer_id = $_GET['customer_id'];
        if ($customer_id)
        {
            $this->data['Customer']['id'] = $customer_id;
        }
        $this->data['Customer']['first_name'] = $_GET['customer_first_name'];
        $this->data['Customer']['last_name'] = $_GET['customer_last_name'];
        $this->data['Customer']['email'] = $_GET['customer_email'];
        $this->data['Customer']['address_unit'] = $_GET['customer_address_unit'];
        $this->data['Customer']['address_street_number'] = $_GET['customer_address_street_number'];
        $this->data['Customer']['address_street'] = $_GET['customer_address_street'];
        $this->data['Customer']['city'] = $_GET['customer_city'];
        $this->data['Customer']['postal_code'] = $_GET['customer_postcode'];
        $this->data['Customer']['phone'] = $_GET['customer_phone'];
        $this->data['Customer']['cell_phone'] = $_GET['customer_cell_phone'];
        $this->data['Customer']['card_number'] = $_GET['customer_card_number'];
        $this->data['Customer']['card_exp'] = $_GET['customer_card_exp'];
        $this->data['Customer']['next_service'] = $_GET['customer_next_service'];

        $this->_SaveCustomer();
        if (!$customer_id)
        {
            $customer_id = $this->data['Customer']['id'];
        }

        echo $customer_id;
        exit;
    }

  function Addquestions($customer_id,$call_user,$callresult,$call_note,$callback_date,$questionsids,$callback_time='', $call_id='',$dialer_id='',$callback_user='',$record_id=''){
  	
    		if (!$call_user) $call_user = $this->Common->getLoggedUserID();
     	
        
         $getcallbackdate = count($callback_date);
        // echo $customer_id;
        //echo $call_user;
        //echo $callresult;
        //echo $callback_time;
          //print_r($call_note);
          //print_r($callback_date);
          $callback_user =57145;
        	$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
              $datestore =array();
             foreach($callback_date as $date){
             	  //echo $date;
             	  $converttotime = strtotime($date);
             	  $datestore[] = date( 'Y-m-d', $converttotime );
             
             }             
        
     for ($i=0; $i < $getcallbackdate; $i++) { 
     
        
        
    $query = "INSERT INTO ace_rp_call_history (customer_id,call_id, dialer_id,
			   call_date, call_time, call_user_id,call_result_id,call_note,
			   callback_date,questions_id,callback_time,callback_user_id,phone,cell_phone)
            VALUES (".$customer_id.",'" .$call_id ."','web',current_date(),current_time(), '".$call_user."','2', '" .$call_note[$i]."','".$datestore[$i]."','".$questionsids[$i]."', " .$callback_time .", '".$callback_user."','".$phone."', '".$cell_phone."')";
            
            	$db->_execute($query);
             	  }
    
      }


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
		//if (($callresult != 7)&&($callresult != 6))
		if (($callresult != 7)&&($callresult != 6) && ($callresult != 5) && ($callresult != 11))
		{
			if ($callresult == 1) // call result : SALE
			{
				// Move the call forward, 6 month from now
				$scheduled_date = 'now() + INTERVAL 11 MONTH';
				$scheduled_time = 'current_time()';
				$callback_reason = 'Sale. Callback in 11 months';
				if($this->Common->getLoggedUserRoleID() != 3){
					$callback_user = 57145; //ACE
				}
			}
			elseif ($callresult == 2) // call result : CALLBACK
			{
				$scheduled_date = "str_to_date('" .$callback_date ."', '%d %b %Y')";
				$scheduled_time = "'".$callback_time."'";
				$callback_reason = $call_note;
			}
			elseif ($callresult == 4) // call result : NOT INTERESTED (3 month)
			{
				if($_GET['callback_reason']!="") {
				// Move the call forward, 3 month from now
				$scheduled_date = 'now() + INTERVAL 3 MONTH';
				$scheduled_time = 'current_time()';
				$callback_reason = $_GET['callback_reason'];
		    	if($this->Common->getLoggedUserRoleID() != 3){
					$callback_user = 57145; //ACE
				}
			    }else {
	             $scheduled_date = 'now() + INTERVAL 3 MONTH';
					 $scheduled_time = 'current_time()';
					 $callback_reason = 'Not interested. Call back in 3 months';
			    	 if($this->Common->getLoggedUserRoleID() != 3){
						$callback_user = 57145; //ACE
					}
			    
			    }
			}
			elseif ($callresult == 8) // call result : NOT INTERESTED (6 month)
			{
				if($_GET['callback_reason']!="") {
				// Move the call forward, 3 month from now
				$scheduled_date = 'now() + INTERVAL 6 MONTH';
				$scheduled_time = 'current_time()';
				$callback_reason = $_GET['callback_reason'];
		    	if($this->Common->getLoggedUserRoleID() != 3){
					$callback_user = 57145; //ACE
				}
			    }else {
			    	$scheduled_date = 'now() + INTERVAL 6 MONTH';
					$scheduled_time = 'current_time()';
					$callback_reason = 'Not interested. Call back in 6 months';
			    	if($this->Common->getLoggedUserRoleID() != 3){
						$callback_user = 57145; //ACE
					}
			    
			    }
			}
			elseif ($callresult == 9) // call result : NOT INTERESTED (9 month)
			{
				if($_GET['callback_reason']!="") {
				// Move the call forward, 3 month from now
				$scheduled_date = 'now() + INTERVAL 9 MONTH';
				$scheduled_time = 'current_time()';
				$callback_reason = $_GET['callback_reason'];
		    	if($this->Common->getLoggedUserRoleID() != 3){
					$callback_user = 57145; //ACE
				}
		    	}else { 
		    	$scheduled_date = 'now() + INTERVAL 9 MONTH';
				$scheduled_time = 'current_time()';
				$callback_reason = 'Not interested. Call back in 9 months';
		    	if($this->Common->getLoggedUserRoleID() != 3){
					$callback_user = 57145; //ACE
				}
            } 			
            
            }
			elseif ($callresult == 3) // call result : DO NOT CALL
			{
				// Set the date to now
				//$callback_date
				$scheduled_date = 'now() + INTERVAL 20 YEAR';
				$scheduled_time = 'current_time()';
				$callback_reason = 'Do not call';
		    	$callback_user = 57145; //ACE
			}
			/****** jack hutson ****/
			elseif ($callresult == 10) // call result : DO NOT CALL 12 months
			{
				// Set the date to now
				//$callback_date
				$scheduled_date = 'now() + INTERVAL 6 MONTH';
				$scheduled_time = 'current_time()';
				$callback_reason = 'Not interested. Call back in 6 months';
		    	$callback_user = 57145; //ACE
			}
			elseif ($callresult == 12) // call result : Busy 1 DAY
			{
				// Set the date to now
				//$callback_date
				$scheduled_date = 'now() + INTERVAL 1 DAY';
				$scheduled_time = 'current_time()';
				$callback_reason = 'Busy. Call back in 1 day';
		    	$callback_user = 57145; //ACE
			}
			
		}elseif($callresult == 5 || $callresult == 31 || $callresult == 32 || $callresult == 33 || $callresult == 34) // call result : SALE
			{
			  if($_GET['callback_reason']!="") {
				// Move the call forward, 6 month from now
				$scheduled_date = 'now() + INTERVAL 20 YEAR';
				$scheduled_time = 'current_time()';
				$callback_reason = $_GET['callback_reason'];
		     	$callback_user = 57145; //ACE
		      }else {
		      $scheduled_date = 'now() + INTERVAL 20 YEAR';
				$scheduled_time = 'current_time()';
				$callback_reason = 'Sale. Callback in 6 months';
		     	$callback_user = 57145; //ACE
		      
		      }	
			}elseif ($callresult == 6) // call result : Busy 1 DAY
			{
				// Set the date to now
				//$callback_date
				$scheduled_date = 'now() + INTERVAL 1 MONTH';
				$scheduled_time = 'current_time()';
				$callback_reason = 'Answering machine';
		    	$callback_user = 57145; //ACE
			}
			elseif($callresult == 7){
				if($_GET['callback_reason']!="") {
					// Move the call forward, 6 month from now
					$scheduled_date = 'now() + INTERVAL 20 YEAR';
					$scheduled_time = 'current_time()';
					$callback_reason = $_GET['callback_reason'];
			     	$callback_user = 57145; //ACE
		        }else {
			        $scheduled_date = 'now() + INTERVAL 20 YEAR';
					$scheduled_time = 'current_time()';
					$callback_reason = 'Not In Service';
			     	$callback_user = 57145; //ACE
		        }	
			}
			elseif($callresult == 11){
				if($_GET['callback_reason']!="") {
					// Move the call forward, 6 month from now
					$scheduled_date = 'now() + INTERVAL 20 YEAR';
					$scheduled_time = 'current_time()';
					$callback_reason = $_GET['callback_reason'];
			     	$callback_user = 57145; //ACE
		        }else {
			        $scheduled_date = 'now() + INTERVAL 20 YEAR';
					$scheduled_time = 'current_time()';
					$callback_reason = 'Busy';
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
       //echo $callback_reason."sachin";
		// Adds the history's record
		$query = "INSERT INTO ace_rp_call_history
			  (customer_id, call_id, dialer_id,
			   call_date, call_time, call_user_id,
			   call_result_id, call_note,
			   callback_date, callback_time, callback_user_id,
         phone, cell_phone)
		VALUES (".$customer_id.", '" .$call_id ."', '" .$dialer_id ."',
				current_date(), current_time(), '".$call_user."',
				'" .$callresult ."', '" .$callback_reason."',
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
						callback_note = '" .$callback_reason."'
				where id=".$customer_id;

    	$db->_execute($query);
	}

	// Method adds a string to the calls history table.
	// This is supposed to be called from the outside of the ACE System
	function AddDialerCall()
	{
		$customer_id = $_GET['customer_id'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		// When we have a new customer here, we need to save his information first
		if (!$customer_id)
		{
			// Check if there is really no this customer.
			// If we do not do this check we would duplicate the customers,
			// because dialer can send the same information more then once.
		    $cond = '';
        	$query = "select count(*) cnt from ace_rp_customers
					   where phone='".$_GET['phone']."'";
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result);

			if ($row['cnt']==0)
			{
				// If we are sure that there are no such customers - create one
				$this->data['Customer']['first_name'] = $_GET['first_name'];
				$this->data['Customer']['last_name'] = $_GET['last_name'];
				$this->data['Customer']['address'] = $_GET['address'];
				$this->data['Customer']['city'] = $_GET['city'];
				$this->data['Customer']['phone'] = $_GET['phone'];
				$this->Order->Customer->save($this->data['Customer']);
				$customer_id = $this->Order->Customer->getLastInsertId();
			}
		}

		$this->AddCallToHistory($customer_id,
					$_GET['call_user'],
					$_GET['callresult'],
					$_GET['callback_reason'],
					$_GET['callback_date'],
					$_GET['callback_time'],
					$_GET['call_id'],
					$_GET['dialer_id']);

		echo $customer_id;
		exit;
	}

	function installations()
  {
		$db =& ConnectionManager::getDataSource('default');

		//CUSTOM PAGING
		//*************s
		$itemsCount = 20;
		$currentPage = 0;
		$previousPage = 0;
		$nextPage = 1;

		if(isset($_GET['page'])){
			if(is_numeric($_GET['page'])){
				$currentPage = $_GET['page'];
			}
		}
		$sqlPaging = " LIMIT 0,".$itemsCount;
		if($currentPage > 0){
			$firstItem = ($currentPage*$itemsCount); //($currentPage*$itemsCount)+1;
			$sqlPaging = " LIMIT ".$firstItem.",".$itemsCount;

			$previousPage = $currentPage -1;
			$nextPage = $currentPage +1;
		}
		//********************
		//END OF CUSTOM PAGING

		//**********
		//SORTING
		$order = $_GET['order'].' '.$_GET['sort'];
		if ($order==' ') $order = ' o.job_date asc';

		//**********
		//CONDITIONS
		//Pick today's date if no date
		if ($this->params['url']['ffromdate'] != '')
			$this->params['url']['ffromdate'] = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
		else
			$this->params['url']['ffromdate'] = date("Y-m-d");

		if ($this->params['url']['ftodate'] != '')
			$this->params['url']['ftodate'] = date("Y-m-d", strtotime($this->params['url']['ftodate']));
		else
			$this->params['url']['ftodate'] = date("Y-m-d");

		$fdate = ($this->params['url']['ffromdate'] != '' ? $this->params['url']['ffromdate']: "" ) ;
		$tdate = ($this->params['url']['ftodate'] != '' ? $this->params['url']['ftodate']: "" ) ;
		$jobtype = $this->params['url']['fjobtype'];
		$tech = $this->params['url']['ftech'];
		$fsource = $this->params['url']['fsource'];
		$fitem = $this->params['url']['fitem'];

		//CONDITIONS
		//**********
		$sqlConditions = '';
		if($fdate != '')
			$sqlConditions .= " AND o.job_date >= '".$this->Common->getMysqlDate($fdate)."'";
		if($tdate != '')
			$sqlConditions .= " AND o.job_date <= '".$this->Common->getMysqlDate($tdate)."'";
		if($jobtype)
			$sqlConditions .= " AND o.order_type_id = '".$jobtype."' ";
		if($tech)
			$sqlConditions .= " AND (o.job_technician1_id = '".$tech."' or o.job_technician2_id = '".$tech."')";
		if($fsource)
			$sqlConditions .= " AND (o.booking_source_id = '".$fsource."' or o.booking_source2_id = '".$fsource."')";
		if($fitem)
			$sqlConditions .= " AND exists (select * from ace_rp_order_items i where i.order_id=o.id and i.item_id = '$fitem')";

		//GET RECORDS PER LIST
		$orders = array();
		//SELECT ONLY BOOKED(1) and DONE(5) orders:
		$query = "select concat(u.first_name, ' ', u.last_name) customer_name,
                    CONCAT(u.address_unit,', ',u.address_street_number,', ',u.address_street) as address,
					u.city, u.phone, s.name job_status, s.id job_status_id,
                    o.id job_id, o.job_date, t.name job_type, o.order_number,
                    o.job_technician1_id, o.job_technician2_id,
                    o.permit_applied_date, o.permit_applied_method,
                    o.permit_applied_user, o.permit_result, o.permit_number,
                    o.app_ordered_by, o.app_ordered_date,
                    o.app_ordered_pickup_date, o.app_ordered_supplier_id,
										o.labor_warranty, o.parts_warranty,
										(select sum(if(s.is_appliance=1,i.price*i.quantity-i.discount+i.addition,0)) from ace_rp_order_items i, ace_rp_items s
											where o.id=i.order_id and i.item_id=s.id) total_appl,
										(select sum(if(s.is_appliance!=1,i.price*i.quantity-i.discount+i.addition,0)) from ace_rp_order_items i, ace_rp_items s
											where o.id=i.order_id and i.item_id=s.id) total_other,
										(select sum(if(s.is_appliance!=1,i.quantity,0)) from ace_rp_order_items i, ace_rp_items s
											where o.id=i.order_id and i.item_id=s.id) cnt_other,
										(SELECT COUNT(*)
										FROM ace_rp_orders ocount
										WHERE ocount.job_date BETWEEN '2009-01-01' AND '2009-12-31'
										AND ocount.customer_id = o.customer_id) 2009_jobs,
										(SELECT COUNT(*)
										FROM ace_rp_orders ocount
										WHERE ocount.job_date BETWEEN '2010-01-01' AND '2010-12-31'
										AND ocount.customer_id = o.customer_id) 2010_jobs,
										(SELECT COUNT(*)
										FROM ace_rp_orders ocount
										WHERE ocount.job_date BETWEEN '2011-01-01' AND '2011-12-31'
										AND ocount.customer_id = o.customer_id) 2011_jobs
               from ace_rp_orders o, ace_rp_order_types t,
                    ace_rp_customers u, ace_rp_order_statuses s
              where o.order_type_id=t.id and t.category_id=2
                and o.customer_id=u.id and o.order_status_id=s.id
              ".$sqlConditions."
              order by ".$order.' '.$sqlPaging;

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{
			foreach ($row as $k => $v)
			  $orders[$row['job_id']][$k] = $v;
		}

		//LOAD MAIN DATA
		$this->set("previousPage",$previousPage);
		$this->set("nextPage",$nextPage);
		$this->set("orders", $orders);

		if($fdate!='')
			$this->set('fdate', date("d M Y", strtotime($fdate)));
		if($tdate!='')
			$this->set('tdate', date("d M Y", strtotime($tdate)));
		$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));
		$this->set('next_tdate', date("d M Y", strtotime($tdate) + 24*60*60));

		$this->set('jobtype', $jobtype);
		$this->set('tech', $tech);
		$this->set('fsource', $fsource);
		$this->set('fitem', $fitem);

		$this->set('allItems', $this->Lists->ListTable('ace_rp_items', 'is_appliance=1'));
		$this->set('allUsers', $this->Lists->BookingSources());
		$this->set('allJobTypes', $this->Lists->ListTable('ace_rp_order_types'));
		$this->set('allSuppliers',$this->Lists->ListTable('ace_rp_suppliers'));
		$this->set('allPermitMethods',$this->Lists->ListTable('ace_rp_apply_methods'));
		$this->set('allPermitStates',$this->Lists->ListTable('ace_rp_permit_states'));
	}

  function calc()
  {
		$prc = $_REQUEST['calc_price'];
		$per = $_REQUEST['calc_period'];

    $res['calc_monthly'] = 0;
    $res['calc_total'] = 0;

    if ($per==1)
    {
        $res['calc_monthly'] = round($prc*0.04942,2);
        $res['calc_total'] = round($res['calc_monthly']*24,2);
    }
    elseif ($per==2)
    {
        $res['calc_monthly'] = round($prc*0.03563,2);
        $res['calc_total'] = round($res['calc_monthly']*36,2);
    }
    elseif ($per==3)
    {
        $res['calc_monthly'] = round($prc*0.02883,2);
        $res['calc_total'] = round($res['calc_monthly']*48,2);
    }
    elseif ($per==4)
    {
        $res['calc_monthly'] = round($prc*0.02482,2);
        $res['calc_total'] = round($res['calc_monthly']*60,2);
    }

		print json_encode($res);
    exit;
  }

	function permit()
	{
		$city = $_REQUEST['city'];
		$file_name = 'bc safety.pdf';
		if (($city=='VANCOUVER')||($city=='VANCOUVER (EAST)')||($city=='VANCOUVER (WEST)'))
			$file_name = 'gas permit city of vancouver.pdf';
		elseif ($city=='BURNABY')
			$file_name = 'burnaby permit.pdf';
		elseif ($city=='NORTH VANCOUVER')
			$file_name = 'city of nvan permit.pdf';
		elseif ($city=='MAPLE RDGE')
			$file_name = 'gas_permitapplication maple ridge.pdf';
		elseif ($city=='RICHMOND')
			$file_name = 'richmond permit.pdf';

		$this->set('file_name',$file_name);
	}

	function mapSchedule()
	{
		$this->layout='edit';
    	$p_code = strtoupper(substr($_REQUEST['p_code'],0,3));
		$city = strtoupper($_REQUEST['city']);

		if ($this->params['url']['date_from'] != '')
			$date_from = date("Y-m-d", strtotime($this->params['url']['date_from']));
    else
			$date_from = date("Y-m-d");

		$date_to = date("Y-m-d", strtotime("$date_from +2 week"));

    $db =& ConnectionManager::getDataSource('default');

		// Get neighbouring areas
		$neighbours = array();
		if ($p_code)
		{
			$neighbours[] = $p_code;
			$result = $db->_execute("select * from ace_rp_map where p_code='$p_code'");
			while($row = mysql_fetch_array($result))
			{
				$neighbours[] = $row['neighbour'];
				$city = $row['city'];
			}
		}
		elseif ($city)
		{
			$neighbours[] = $city;
			$result = $db->_execute("select * from ace_rp_map where city='$city'");
			while($row = mysql_fetch_array($result))
				$neighbours[] = $row['p_code'];
		}

		//Prepare Truck Names
		$map_reverse = array();
		$map_all = array();
		$trucks = array();

		$route_type = $_REQUEST['route_type'];
		if (!$route_type)
			if (($this->Common->getLoggedUserRoleID()==1))
				$route_type = '2';
			elseif (($this->Common->getLoggedUserRoleID()!=6))
				$route_type = '1';

		$cond = '';
		if ($route_type) $cond = 'and route_type='.$route_type;

		$userId=$this->Common->getLoggedUserID();
		if($this->Common->getLoggedUserRoleID()==3){
			$query = "select ace_rp_inventory_locations.* from ace_rp_inventory_locations inner join ace_rp_truck_maps as artm on ace_rp_inventory_locations.id=artm.truck_id where flagactive = 0 and type=2 and artm.user_id=".$userId." $cond order by order_id asc";	
		}else{
			$query = "select * from ace_rp_inventory_locations where flagactive = 0 and type=2 $cond order by order_id asc";
		}

		//$query = "select * from ace_rp_inventory_locations where type=2 $cond order by id asc";
		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result)) {
			$trucks[$row['id']]['id'] = $row['id'];
			$trucks[$row['id']]['name'] = $row['name'];
			$trucks[$row['id']]['color'] = $row['color'];
			$trucks[$row['id']]['truck_number'] = $row['truck_number'];
			$trucks[$row['id']]['route_number'] = $row['route_number'];
			for ($j=2; $j<8; $j++)
			{
				$map_all[$j][$row['id']] = 'ALL';
				for ($i=8; $i<18; $i++)
					$map_reverse[$row['id']][$j][$i][] = 'ALL';
			}
		}

		$query = "select o.job_date, o.job_truck, DAYOFWEEK(o.job_date) job_day, o.order_number,
										 hour(o.job_time_beg) hour_beg, hour(o.job_time_end) hour_end,
										 upper(substr(u.postal_code,1,3)) p_code, upper(u.city) city
								from ace_rp_orders o, ace_rp_customers u
							 where o.job_date between '$date_from' and '$date_to'
								 and o.order_status_id in (1,5) and u.id=o.customer_id
							";
		$map_busy = array();
		$dates = array();
		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result))
		{
			$day = $row['job_day'];
			$date = array();
			$date['weekday_name'] = date("l", strtotime($row['job_date']));
			$date['weekday'] = $day;
			$date['name'] = date("d M Y", strtotime($row['job_date']));
			$date['date'] = date("Y-m-d", strtotime($row['job_date']));
			$dates[strtotime($row['job_date'])] = $date;
			for ($i = $row['hour_beg']; $i < $row['hour_end']; $i++)
			{
				$map_busy[$row['job_truck']][$day][$i] = $row['order_number'];
				unset($map_reverse[$row['job_truck']][$day][$i]);
				unset($map_all[$day][$row['job_truck']]);
				if (isset($map_reverse[$row['job_truck']][$day][$i-1]))
				{
					$map_reverse[$row['job_truck']][$day][$i-1][] = substr($row['p_code'],0,3);
					$map_reverse[$row['job_truck']][$day][$i-1][] = $row['city'];
				}
				if (isset($map_reverse[$row['job_truck']][$day][$i+1]))
				{
					$map_reverse[$row['job_truck']][$day][$i+1][] = substr($row['p_code'],0,3);
					$map_reverse[$row['job_truck']][$day][$i+1][] = $row['city'];
				}
			}
		}

		// Reverce the map
		$map = array();
		if ($city||$p_code)
			foreach ($map_reverse as $truck_k => $time_v)
			{
				foreach ($time_v as $day_k => $day_val)
				{
					foreach ($day_val as $time_k => $map_val)
					{
						foreach ($map_val as $val)
						{
							if (in_array($val, $neighbours))
								$map[$day_k][$time_k][] = $truck_k;
						}
					}
				}
			}

		$this->set('neighbours', $neighbours);
		$this->set('trucks', $trucks);
		$this->set('map', $map);
		$this->set('map_all', $map_all);
		$this->set('map_busy', $map_busy);
		$this->set('p_code', $p_code);
		$this->set('city', $city);
		$this->set('dates', $dates);
		//$this->set('allCities', $this->Lists->ListTable('ace_rp_cities'));
		$this->set('allCities',$this->Lists->ActiveCities());
		$this->set('allTypes', $this->Lists->ListTable('ace_rp_route_types'));
		$this->set('date_from', date("d M Y", strtotime($date_from)));
		$this->set('ydate', date("d M Y", strtotime("$date_from - 2 week")));
		$this->set('tdate', date("d M Y", strtotime("$date_from + 2 week")));
	}

	// AJAX method for activation/deactivation of an item
	function changeActive()
	{
		$item_id = $_GET['item_id'];
		$is_active = $_GET['is_active'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$db->_execute("update ace_rp_orders set flagactive='".$is_active."' where id=".$item_id);

		exit;
	}

	// AJAX method for changing a job's substatus
	function setSubstatus()
	{
		$order_id = $_REQUEST['order_id'];
		$substatus = $_REQUEST['substatus'];
		$userid = $this->Common->getLoggedUserID();

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		if ($substatus==4)
			$db->_execute("update ace_rp_orders set order_substatus_id=$substatus, notified_by_id=$userid, notified_when=current_time() where id=$order_id");
		else
			$db->_execute("update ace_rp_orders set order_substatus_id=$substatus, notified_by_id=0 where id=$order_id");

		exit;
	}

	// AJAX method for changing a job's end time
	function setEndTime()
	{
		$order_id = $_REQUEST['order_id'];
		$diff = $_REQUEST['time_diff'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "select t.job_time_end from ace_rp_orders t where t.id=$order_id";
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result);
		if ($diff>0)
			$endTime = date("H:i", strtotime($row['job_time_end']." +$diff hour"));
		else
			$endTime = date("H:i", strtotime($row['job_time_end']." $diff hour"));

		if ($diff!=0)
		{
			$prev = array();
			$query = "select t.id, t.job_time_beg, t.job_time_end from ace_rp_orders t, ace_rp_orders o
						where o.id=$order_id and t.job_truck=o.job_truck and t.job_time_beg>=o.job_time_end and o.job_date=t.job_date";
			$result = $db->_execute($query);
			while ($row = mysql_fetch_array($result)) $prev[] = $row;
			foreach ($prev as $row)
			{
				if ($diff>0)
				{
					$tNewB = date("H:i", strtotime($row['job_time_beg']." +$diff hour"));
					$tNewE = date("H:i", strtotime($row['job_time_end']." +$diff hour"));
				}
				else
				{
					$tNewB = date("H:i", strtotime($row['job_time_beg']." $diff hour"));
					$tNewE = date("H:i", strtotime($row['job_time_end']." $diff hour"));
				}
				$db->_execute("update ace_rp_orders set job_time_end='$tNewE', job_time_beg='$tNewB', order_substatus_id=8, notified_by_id=0
								where id={$row['id']}");
			}

			$db->_execute("update ace_rp_orders set job_time_end='$endTime' where id=$order_id");
		}

		exit;
	}

	function toggleVisible() {
		$order_id = $_REQUEST['order_id'];
		$visibility = $_REQUEST['visibility'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "select t.job_time_end from ace_rp_orders t where t.id=$order_id";
		$db->_execute("
			UPDATE ace_rp_orders
			SET tech_visible = $visibility
			WHERE id = $order_id
		");
		exit;
	}

	function sendInvoiceToPrinter() {
		$subject = 'Ace Services Ltd';
		$headers = "From: info@acecare.ca\n";
		$headers .= "Content-Type: text/html; charset=iso-8859-1\n";
		$order_id = $_GET['order_id'];
		//$msg = file_get_contents('http://acesys.ace1.ca/acetest/acesys-2.0/index.php/orders/printView?id='.$order_id);
		$msg = file_get_contents('http://acecare.ca/acesys/index.php/orders/printView?id='.$order_id);
		$res = mail('proacetruck01@hpeprint.com', $subject, $msg, $headers);
		//$res = mail('hsestacio13@gmail.com', $subject, $msg, $headers);
		echo "Printing...";
		exit;
	}

	function sendConfirmationEmail() {
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);


		if(isset($_REQUEST['for_date'])) {
			$settings = $this->Setting->find(array('title'=>'email_fromaddress'));
			$from_address = $settings['Setting']['valuetxt'];

			$subject = 'Ace Services Ltd';

			//define the headers we want passed. Note that they are separated with \r\n
			//$headers = "From: webmaster@example.com\r\nReply-To: webmaster@example.com";
			$headers = "From: info@acecare.ca\n";
			//add boundary string and mime type specification
			$headers .= "Content-Type: text/html; charset=iso-8859-1\n" ;

			$job_date = $_REQUEST['for_date'];

			$query = "
				SELECT u.first_name, u.last_name, u.email, o.job_date,
					DATE_FORMAT(o.job_date, '%M %D, %Y') text_date,
					DATE_FORMAT(o.job_time_beg, '%r') job_time_beg,
					DATE_FORMAT(o.job_time_end, '%r') job_time_end
				FROM ace_rp_orders o
				LEFT JOIN ace_rp_customers u
				ON o.customer_id = u.id
				WHERE o.job_date = date('$job_date')
				AND o.order_status_id IN(1,2)
				AND u.email
				NOT IN ('',
					'ace_123@live.ca',
					'ace-123@live.ca',
					'NO EMAIL',
					'N/A',
					'cb@acecare.ca',
					'NONE',
					'ace_123@livenation.ca',
					'N A',
					'a',
					'ACE_123@LIVE.COM',
					'ace_123@live.com',
					'ACE_!23@LIVE.CA',
					'NA')
			";
			$result = $db->_execute($query);
			while ($row = mysql_fetch_array($result))
			{
				$msg = "
					Dear ".$row['first_name']." ".$row['last_name'].",<br /><br />

					As booked, ACE Services Ltd. will be coming by your home on ".$row['text_date']." between ".$row['job_time_beg']." to ".$row['job_time_end'].".<br /><br />

					If you have any questions regarding your booking please give us a call.<br /><br />

					Have a nice day!<br /><br />

					ACE Services Ltd<br />
					phone: 604-293-3770<br />
					email: info@acecare.ca
				";
				$res = mail($row['email'], $subject, $msg, $headers);
				//$res = mail('hsestacio13@gmail.com', $subject, $msg, $headers);

			}
			$db->_execute("INSERT INTO ace_rp_email_confirmations(job_date, is_sent) VALUES('".$job_date."', 1) ");
		}//end isset($_REQUEST['for_date'])

		$query = "
			SELECT DISTINCT o.job_date,
				DATE_FORMAT(o.job_date, '%M %D, %Y') textdate,
				o.job_date - CURDATE() 'days',
				IFNULL(ec.is_sent, 0) is_sent
			FROM ace_rp_orders o
			LEFT JOIN ace_rp_email_confirmations ec
			ON o.job_date = ec.job_date
			WHERE o.job_date >= CURDATE()
			AND o.job_date - CURDATE() <= 7
			ORDER BY o.job_date
		";
		$result = $db->_execute($query);
		$jobs = '';
		while ($row = mysql_fetch_array($result))
		{
			$jobs[$row['job_date']]['job_date'] = $row['job_date'];
			$jobs[$row['job_date']]['textdate'] = $row['textdate'];
			$jobs[$row['job_date']]['days'] = $row['days'];
			$jobs[$row['job_date']]['is_sent'] = $row['is_sent'];
		}

		$this->set('jobs', $jobs);
	}

	function campaing() {
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "SELECT * FROM ace_rp_reference_campaigns";
		$result = $db->_execute($query);
		$campaign = array();
		$camp_id = array();
		$flag = array();
		$ori_count = array();

		while ($row = mysql_fetch_assoc($result)){
			$campaign[$row['campaign_name']] = $row['camp_count'];
			$id = $row['id'];
			$camp_id[] = $row['id'];
			$flag[] = $row['transfer_call_jobs_flag'];
			$query_n = "SELECT count(id) as count FROM ace_rp_customers WHERE campaign_id = $id";
			$result_n = $db->_execute($query_n);
			$row_n = mysql_fetch_assoc($result_n);
			$ori_count[] = $row_n['count'];
		}

		$this->set('original_count', $ori_count);
		$this->set('campaign', $campaign);
		$this->set('camp_id', $camp_id);
		$this->set('flag', $flag);
	}

	function campaingSearch() {

		$id = $_REQUEST['id'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "SELECT * FROM ace_rp_reference_campaigns o LEFT JOIN ace_rp_all_campaigns ec ON o.id = ec.last_inserted_id LEFT JOIN ace_rp_customers u2 ON ec.call_history_ids = u2.id WHERE u2.campaign_id IS NOT NULL AND ec.last_inserted_id = $id";

		$result = $db->_execute($query);
		$cust = array();

		while ($row = mysql_fetch_assoc($result)){
			$cust[] = $row;
		}
		$this->set('cust', $cust);
		$this->set('campId', $id);
	}

	function campaing_list() {
		$this->set('campaing_list', $this->Lists->CampaingList());
		$this->set('booking_sources',$this->Lists->BookingSources());
	}

	function remaining_leads_of_campain() {
		$camp_id = $_REQUEST['id'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$source_Data = $this->Lists->BookingSources();

		$query = "SELECT `arc`.`id` as `id` , `ec`.`source_from` as `pre_agent_id`
				FROM ace_rp_customers arc 
				LEFT JOIN ace_rp_reference_campaigns ec ON arc.campaign_id = ec.id  
				WHERE arc.campaign_id = $camp_id";
		$result = $db->_execute($query);

		while ($row = mysql_fetch_assoc($result)){
			$cus_id[] = $row['id'];
			$pre_agent_id = $row['pre_agent_id'];
		}

		$remaining_count = count($cus_id);

		foreach ($source_Data as $key => $value) {
			if($pre_agent_id == $key){
				$pre_agent_name = $value ;
			}
		}

		$total = $remaining_count.','.$pre_agent_name;
		print_r($total);

		exit;
	}

	function transferCampaign(){
		$toUser = $_REQUEST['source_id'];
		$campaign_id = $_REQUEST['campaign_id'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "SELECT `arc`.`transfer_call_jobs_flag` as `flag` , `u1`.`call_history_ids` as `cust_id` FROM ace_rp_reference_campaigns arc 
				LEFT JOIN ace_rp_all_campaigns u1 ON arc.id = u1.last_inserted_id
				WHERE arc.id = $campaign_id";

		$result = $db->_execute($query);

		while ($row = mysql_fetch_assoc($result)){
			$cust_id[] = $row['cust_id'];
			$flag = $row['flag'];
		}

		$str1 = "'".implode("','", $cust_id)."'";

		if($flag == 1){
			$query = "UPDATE `ace_rp_orders` as `arc` set `arc`.`booking_source_id` = $toUser WHERE o_campaign_id = $campaign_id";
			$result = $db->_execute($query);
			$trans = 'job';
		}
		elseif($flag == 2){
			$query = "UPDATE `ace_rp_call_history` as `arc` set `arc`.`callback_user_id` = $toUser WHERE call_campaign_id = $campaign_id";
			$result = $db->_execute($query);
			$trans = 'call';
		}

		$query = "UPDATE `ace_rp_reference_campaigns` as `arc` set `arc`.`source_from` = $toUser WHERE id = $campaign_id";
		$result = $db->_execute($query);

		$source_Data = $this->Lists->BookingSources();

		foreach ($source_Data as $key => $value) {
			if($toUser == $key){
				$agent_name = $value ;
			}
		}

		print_r($trans.','.$agent_name);
		exit;
	}

	function delete_campaign(){
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$camp_id = $_REQUEST['id'];

		$query = "SELECT * FROM ace_rp_reference_campaigns WHERE id = $camp_id";
		$result = $db->_execute($query);

		$row = mysql_fetch_array($result);

		if($row['transfer_call_jobs_flag'] == 1){
			$query = "UPDATE `ace_rp_orders` as `arc` set `arc`.`o_campaign_id` = NULL WHERE o_campaign_id = $camp_id";
			$result = $db->_execute($query);
		}
		else{
			$query = "UPDATE `ace_rp_call_history` as `arc` set `arc`.`call_campaign_id` = NULL WHERE call_campaign_id = $camp_id";
			$result = $db->_execute($query);
		}

		$query = "DELETE FROM ace_rp_reference_campaigns WHERE id = $camp_id";
		$result = $db->_execute($query);

		$query = "DELETE FROM ace_rp_all_campaigns WHERE last_inserted_id = $camp_id";
		$result = $db->_execute($query);

		$query = "UPDATE `ace_rp_customers` as `arc` set `arc`.`campaign_id` = NULL WHERE campaign_id = $camp_id";
		$result = $db->_execute($query);

		exit;
	}

	function sendPortfolioEmail() {
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);


		if(isset($_REQUEST['for_date'])) {
			$settings = $this->Setting->find(array('title'=>'email_fromaddress'));
			$from_address = $settings['Setting']['valuetxt'];

			$subject = 'Ace Services Ltd';

			//define the headers we want passed. Note that they are separated with \r\n
			//$headers = "From: webmaster@example.com\r\nReply-To: webmaster@example.com";
			$headers = "From: info@acecare.ca\n";
			//add boundary string and mime type specification
			$headers .= "Content-Type: text/html; charset=iso-8859-1\n" ;

			$job_date = $_REQUEST['for_date'];

			$query = "
				SELECT u1.first_name first_name1,
					u1.last_name last_name1,
					u1.qualifications qualifications1,
					u1.experience experience1,
					u1.skills skills1,
					u1.about about1,
					u1.goals goals1,
					u1.hobbies hobbies1,
					u1.binary_picture binary_picture1,
					o.job_technician1_id,
					u2.first_name first_name2,
					u2.last_name last_name2,
					u2.qualifications qualifications2,
					u2.experience experience2,
					u2.skills skills2,
					u2.about about2,
					u2.goals goals2,
					u2.hobbies hobbies2,
					u2.binary_picture binary_picture2,
					o.job_technician2_id,
					u.email,
					DATE_FORMAT(o.job_date, '%W, %M %d, %Y') job_date,
					TIME_FORMAT(o.job_time_beg, '%r') job_time_begin,
					TIME_FORMAT(o.job_time_end, '%r') job_time_end,
					ot.name job_type
				FROM ace_rp_orders o
				LEFT JOIN ace_rp_users u1
				ON o.job_technician1_id = u1.id
				LEFT JOIN ace_rp_users u2
				ON o.job_technician2_id = u2.id
				LEFT JOIN ace_rp_users u
				ON o.customer_id = u.id
				LEFT JOIN ace_rp_order_types ot
				ON o.order_type_id = ot.id
				WHERE o.job_date = date('$job_date')
				AND o.order_status_id IN(1,2)
				AND u.email
				NOT IN ('',
					'ace_123@live.ca',
					'ace-123@live.ca',
					'NO EMAIL',
					'N/A',
					'cb@acecare.ca',
					'NONE',
					'ace_123@livenation.ca',
					'N A',
					'a',
					'ACE_123@LIVE.COM',
					'ace_123@live.com',
					'ACE_!23@LIVE.CA',
					'NA',
					'ace_@ACECARE.CA',
					'ali@acecare.ca',
					'www.NoEMAIl@ace.cOM',
					'ACE123@LIVE.CA'
					)
			";

			$msg = $this->Setting->find(array('title'=>'portfolio_template'));
			$tech1_porfolio = $msg['Setting']['valuetxt'];
			$tech2_porfolio = $msg['Setting']['valuetxt'];

			$result = $db->_execute($query);
			while ($row = mysql_fetch_array($result))
			{
				if($row['job_technician1_id'] != null) {

					$tech1_porfolio = str_replace("{first_name}", $row['first_name1'], $tech1_porfolio);
					$tech1_porfolio = str_replace("{last_name}", $row['last_name1'], $tech1_porfolio);
					$tech1_porfolio = str_replace("{jobdate}", $row['job_date'], $tech1_porfolio);
					$tech1_porfolio = str_replace("{jobtimebegin}", $row['job_time_begin'], $tech1_porfolio);
					$tech1_porfolio = str_replace("{jobtimeend}", $row['job_time_end'], $tech1_porfolio);
					$tech1_porfolio = str_replace("{jobtype}", $row['job_type'], $tech1_porfolio);
					$tech1_porfolio = str_replace("{techphoto}", $row['binary_picture1'], $tech1_porfolio);
					$tech1_porfolio = str_replace("{qualifications}", $row['qualifications1'], $tech1_porfolio);
					$tech1_porfolio = str_replace("{experience}", $row['experience1'], $tech1_porfolio);
					$tech1_porfolio = str_replace("{skills}", $row['skills1'], $tech1_porfolio);
					$tech1_porfolio = str_replace("{about}", $row['about1'], $tech1_porfolio);
					$tech1_porfolio = str_replace("{goals}", $row['goals1'], $tech1_porfolio);
					$tech1_porfolio = str_replace("{hobbies}", $row['hobbies1'], $tech1_porfolio);

					$res = mail($row['email'], $subject, $tech1_porfolio, $headers);
					//$res = mail('hsestacio13@gmail.com', $subject, $tech1_porfolio, $headers);
				}

				if($row['job_technician2_id'] != null) {
					$tech2_porfolio = str_replace("{first_name}", $row['first_name2'], $tech2_porfolio);
					$tech2_porfolio = str_replace("{last_name}", $row['last_name2'], $tech2_porfolio);
					$tech2_porfolio = str_replace("{jobdate}", $row['job_date'], $tech2_porfolio);
					$tech2_porfolio = str_replace("{jobtimebegin}", $row['job_time_begin'], $tech2_porfolio);
					$tech2_porfolio = str_replace("{jobtimeend}", $row['job_time_end'], $tech2_porfolio);
					$tech2_porfolio = str_replace("{jobtype}", $row['job_type'], $tech2_porfolio);
					$tech2_porfolio = str_replace("{techphoto}", $row['binary_picture2'], $tech2_porfolio);
					$tech2_porfolio = str_replace("{qualifications}", $row['qualifications2'], $tech2_porfolio);
					$tech2_porfolio = str_replace("{experience}", $row['experience2'], $tech2_porfolio);
					$tech2_porfolio = str_replace("{skills}", $row['skills2'], $tech2_porfolio);
					$tech2_porfolio = str_replace("{about}", $row['about2'], $tech2_porfolio);
					$tech2_porfolio = str_replace("{goals}", $row['goals2'], $tech2_porfolio);
					$tech2_porfolio = str_replace("{hobbies}", $row['hobbies2'], $tech2_porfolio);

					$res = mail($row['email'], $subject, $tech2_porfolio, $headers);
					//$res = mail('hsestacio13@gmail.com', $subject, $tech2_porfolio, $headers);
				}
			}

			$db->_execute("INSERT INTO ace_rp_email_portfolios(job_date, is_sent) VALUES('".$job_date."', 1) ");
		}//end isset($_REQUEST['for_date'])

		$query = "
			SELECT DISTINCT o.job_date,
				DATE_FORMAT(o.job_date, '%M %D, %Y') textdate,
				o.job_date - CURDATE() 'days',
				IFNULL(ec.is_sent, 0) is_sent
			FROM ace_rp_orders o
			LEFT JOIN ace_rp_email_portfolios ec
			ON o.job_date = ec.job_date
			WHERE o.job_date >= CURDATE()
			AND o.job_date - CURDATE() <= 7
			ORDER BY o.job_date
		";
		$result = $db->_execute($query);
		$jobs = '';
		while ($row = mysql_fetch_array($result))
		{
			$jobs[$row['job_date']]['job_date'] = $row['job_date'];
			$jobs[$row['job_date']]['textdate'] = $row['textdate'];
			$jobs[$row['job_date']]['days'] = $row['days'];
			$jobs[$row['job_date']]['is_sent'] = $row['is_sent'];
		}

		$this->set('jobs', $jobs);
		$this->set('tech2', $tech2_porfolio);
	}

	function agentBooking()
	{
		//check for log in
		$vici_user = $_GET['user'];
		$vici_phone_number = $_GET['phone_number'];
		$vici_first_name = $_GET['first_name'];
		$vici_last_name = $_GET['last_name'];
		$vici_address1 = $_GET['address1'];
		$vici_address2 = $_GET['address2'];
		$vici_address3 = $_GET['address3'];
		$vici_city = $_GET['city'];
		$vici_postal_code = $_GET['postal_code'];
		$vici_alt_phone = $_GET['alt_phone'];
		$vici_email = $_GET['email'];

		if($_SESSION['user']['id'] != $vici_user) {
			$query = "SELECT a.id,concat(a.first_name,' ',a.last_name) as name ,b.role_id as role_id
				FROM ace_rp_users as a
				INNER JOIN ace_rp_users_roles as b ON (a.id = b.user_id)
				WHERE a.id = $vici_user limit 1";

			$db =& ConnectionManager::getDataSource('default');
			$result = $db->_execute($query);
			if($row = mysql_fetch_array($result))
			{
					$_SESSION['user']['id'] = $row['id'];
					$_SESSION['user']['name'] = $row['name'];
					$_SESSION['user']['role_id'] = $row['role_id'];
					$_SESSION['user']['external'] = 0;
			}
		}

		$this->layout='edit';
		if (!empty($this->data['Order']))
		{
			//If order information is submitted - save the order
			//$this->saveOrder();
			$this->saveOrderFromVici($vici_user);
		}
		else
		{
			// If no order data is submitted, we'll have one of the following situations:
			// 1. we are being asked to display an existing order's data ($order_id!='')
			// 2. we are being asked to create a new order for an existing customer ($order_id=='', $customer_id!='')
			// 3. we are being asked to create a completely new customer ($order_id=='', $customer_id=='')

			if($vici_phone_number != '') {
				$query = "
					SELECT *
					FROM ace_rp_customers
					WHERE phone = $vici_phone_number
					ORDER BY id DESC
					LIMIT 1
				";

				$db =& ConnectionManager::getDataSource('default');
				$result = $db->_execute($query);
				if($row = mysql_fetch_array($result))
				{
					$vici_customer_id = $row['id'];
				} else {
					$vici_customer_id = '';
				}
			}
			// Check submitted data for any special parameters to be set
			$order_id = $this->params['url']['order_id'];
			$customer_id = $vici_customer_id;
			$num_items = 0;
			$show_app_order='display:none';
			$show_permits = 'display:none';

			//Remove all reserved timeslots
			$query = "
				DELETE FROM ace_rp_pending_timeslots
				WHERE user_id = ".$vici_user."
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
				$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
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
            $this->set('tech1_comm', $tech1_comm);
            $this->set('tech2_comm', $tech2_comm);
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
			$this->data['Order']['job_date'] = $this->params['url']['job_date'];
			$this->data['Order']['job_time_beg'] = $this->params['url']['job_time_beg'];
			$this->data['Order']['job_technician1_id'] = $this->params['url']['job_technician1_id'];
			$this->data['Order']['job_technician2_id'] = $this->params['url']['job_technician2_id'];

			// Orders created by the 'new callback' action are callbacks
			if ( in_array($_GET['action_type'], array('callback','comeback','dnc') ) ) //$_GET['action_type'] == 'callback')
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
				else
				{
					$this->data['Customer']['first_name'] = $vici_first_name;
					$this->data['Customer']['last_name'] = $vici_last_name;
					$this->data['Customer']['address'] = $vici_address1;
					$this->data['Customer']['city'] = $vici_city;
					$this->data['Customer']['postal_code'] = $vici_postal_code;
					$this->data['Customer']['phone'] = $vici_phone_number;
					$this->data['Customer']['cell_phone'] = $vici_alt_phone;
					$this->data['Customer']['email'] = $vici_email;
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
		if (!$this->data['Order']['permit_applied_date'])
			$this->data['Order']['permit_applied_date'] = date('d M Y');
		else
			$this->data['Order']['permit_applied_date'] = date('d M Y', strtotime($this->data['Order']['permit_applied_date']));

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

		//$this->set("job_trucks_area", $this->Lists->Routes());
		//$this->set('job_trucks2', $this->HtmlAssist->table2array($this->InventoryLocation->findAll($query), 'id', 'truck'));

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

		// Past Order View Mode
		if ($this->data['Status']['name'] == 'Done') $this->set('ViewMode', 1);

		$this->data['Coupon'] = $this->Coupon->findAll();

		//Make Redo Orders List
		//$redo_orders = $this->_getPreviousJobs($this->data['Customer']['id']);
		if ($this->data['Order']['job_estimate_id'])
		{
			$past_orders = $this->Order->findAll(array('Order.id'=> $this->data['Order']['job_estimate_id']), null, "job_date DESC", null, null, 1);
			foreach ($past_orders as $ord)
				$job_estimate_text = 'REF# '.$ord['Order']['order_number'].' - '.date('d M Y', strtotime($ord['Order']['job_date']));
		}

		// Find customer's notes
		if ($this->data['Customer']['id'])
		{
        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
        $query = "SELECT * FROM ace_rp_users_notes WHERE user_id=".$this->data['Customer']['id']." ORDER BY note_date DESC";
        $result = $db->_execute($query);
        while ($row = mysql_fetch_array($result))
            $customer_notes[$row['id']] = $row;
		}
		$this->set('past_orders', $past_orders);
		$this->set('redo_orders', $redo_orders);
		$this->set('customer_notes',$customer_notes);
		$this->set('job_estimate_text',$job_estimate_text);

		// Prepare dates for selector
		if ((strlen($this->data['Order']['job_date']) > 0) && ($this->data['Order']['job_date'] != "0000-00-00"))
			$this->data['Order']['job_date'] = date("d M Y", strtotime($this->data['Order']['job_date']));
		if ((strlen($this->data['CallRecord']['callback_date']) > 0) && ($this->data['CallRecord']['callback_date'] != "0000-00-00"))
			$this->data['CallRecord']['callback_date'] = date("d M Y", strtotime($this->data['CallRecord']['callback_date']));
		if ((strlen($this->data['CallRecord']['call_date']) > 0) && ($this->data['CallRecord']['call_date'] != "0000-00-00"))
			$this->data['CallRecord']['call_date'] = date("d M Y", strtotime($this->data['CallRecord']['call_date']));

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
	}

// Method renders vicidial call history into the table
    function getViciCallHistory()
    {
        $customer_id = $_GET['customer_id'];
        $phone = $_GET['phone'];

        if ((!$customer_id)&&(!$phone)) exit;

        //$phone = preg_replace("/[- \.]/", "", $phone);

        echo "<script>";
        echo "function DeleteCallRecord(rec_id){";
        echo "$.get('".BASE_URL."/orders/deleteCallRecord', {record_id:rec_id}, function(data){";
				echo "$.get('".BASE_URL."/orders/getViciCallHistory', {phone:".$phone."}, function(data){";
				echo "showhist=1;$('#CallHistory').html(data);});});";
        echo "}";
        echo "</script>";
        echo "<table style='background-color:white'>";
        echo "<tr class='results'><th colspan=3>Call</th><th rowspan=2>Note</th><th colspan=2>Callback</th><th rowspan=2>Source</th>";
		echo "</tr><tr class='results'><th>Date</th><th>User</th><th>Result</th><th>Date</th><th>User</th></tr>";

		if(1) {
			$acefilter = "AND ch.call_result_id NOT IN(6)";
			$vicifilter = "AND al.status IN ('CALLBK', 'CBHOLD', 'NI3', 'NI6', 'NI9')";
		}

		$r = 1;

		$query = "
			SELECT ch.id,
				DATE_FORMAT(CONCAT(ch.call_date, ' ', ch.call_time), '%b. %d, %Y %r') event_time,
				ch.phone phone_number,
				CONCAT(u.first_name, ' ', u.last_name) full_name,
				cr.name status,
				ch.call_note call_notes,
				DATE_FORMAT(CONCAT(ch.callback_date, ' ', ch.callback_time), '%b. %d, %Y %r') callback_date,
				'ACE' source
			FROM ace_rp_call_history ch
			LEFT JOIN ace_rp_users u
			ON u.id = ch.callback_user_id
			LEFT JOIN ace_rp_call_results cr
			ON ch.call_result_id = cr.id
			WHERE ch.phone = REPLACE('$phone', '-', '')
			$acefilter
		";

        $db =& ConnectionManager::getDataSource("default");
        $result = $db->_execute($query);

		while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
			$call_history[$row['event_time']]['id'] = $row['id'];
			$call_history[$row['event_time']]['event_time'] = $row['event_time'];
			$call_history[$row['event_time']]['phone_number'] = $row['phone_number'];
			$call_history[$row['event_time']]['full_name'] = $row['full_name'];
			$call_history[$row['event_time']]['status'] = $row['status'];
			$call_history[$row['event_time']]['call_notes'] = $row['call_notes'];
			$call_history[$row['event_time']]['callback_date'] = $row['callback_date'];
			$call_history[$row['event_time']]['source'] = $row['source'];
		}

		/**$query = "
			SELECT '0' id,
				DATE_FORMAT(al.event_time, '%b. %d, %Y %r') event_time,
				l.phone_number,
				u.full_name,
				IF(al.status = 'CALLBK' OR al.status = 'CBHOLD', 'Call Back',
					IF(al.status = 'SALE', 'Sale',
						IF(al.status = 'A', 'Answering Machine',
							IF(al.status = 'NI3', 'Not Interested (3 months)',
								IF(al.status = 'NI6', 'Not Interested (6 months)',
									IF(al.status = 'NI9', 'Not Interested (9 months)',
										IF(al.status = 'DNC' OR al.status = 'DNCL' OR al.status = 'DNCC', 'Do Not Call',
											IF(al.status = 'B', 'Busy',
												IF(al.status = 'N', 'No Answer',
												al.status
												)
											)
										)
									)
								)
							)
						)
					)
				) status,
				cn.call_notes,
				DATE_FORMAT(
				IF(al.status = 'NI3', DATE_ADD(al.event_time, INTERVAL 3 MONTH),
					IF(al.status = 'NI6', DATE_ADD(al.event_time, INTERVAL 6 MONTH),
						IF(al.status = 'NI3', DATE_ADD(al.event_time, INTERVAL 9 MONTH),
							IF(al.status = 'CALLBK', cb.callback_time,
								IF(al.status = 'CBHOLD', cb.callback_time,
								al.event_time
								)
							)
						)
					)
				), '%b. %d, %Y %r') callback_date,
				'VICI' source
			FROM vicidial_agent_log al
			LEFT JOIN vicidial_list l
			ON al.lead_id = l.lead_id
			LEFT JOIN vicidial_users u
			ON al.user = u.user
			LEFT JOIN vicidial_call_notes cn
			ON al.lead_id = cn.lead_id AND DATE_FORMAT(al.event_time, '%Y-%j') = DATE_FORMAT(cn.call_date, '%Y-%j')
			LEFT JOIN vicidial_callbacks cb
			ON al.lead_id = cb.lead_id AND DATE_FORMAT(al.event_time, '%Y-%j') = DATE_FORMAT(cb.entry_time, '%Y-%j')
			WHERE l.phone_number IS NOT NULL
			AND al.status IS NOT NULL
			AND l.phone_number = REPLACE('$phone', '-', '')
			$vicifilter
		";

		$db =& ConnectionManager::getDataSource("vicidial");
        $result = $db->_execute($query);

		while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
			$call_history[$row['event_time']]['id'] = $row['id'];
			$call_history[$row['event_time']]['event_time'] = $row['event_time'];
			$call_history[$row['event_time']]['phone_number'] = $row['phone_number'];
			$call_history[$row['event_time']]['full_name'] = $row['full_name'];
			$call_history[$row['event_time']]['status'] = $row['status'];
			$call_history[$row['event_time']]['call_notes'] = $row['call_notes'];
			$call_history[$row['event_time']]['callback_date'] = $row['callback_date'];
			$call_history[$row['event_time']]['source'] = $row['source'];
		}**/

		krsort($call_history);

		foreach($call_history as $call){
			echo "<tr id='" .$call['id'] ."' class='" ."cell".(++$r%2) ."'>";
            echo "<td>" .$call['event_time']."</td>";
            echo "<td>" .$call['full_name'] ."</td>";
            echo "<td>" .$call['status'] ."</td>";

            $action = 'return false;';

            $cb_note = '';
            $cb_date = '';


            echo "<td><div style='width:150px'>" .$call['call_notes']."</div></td>";
            echo "<td>" .$call['callback_date']."</td>";
			if($call['status'] == 'Call Back')
	            echo "<td>" .$call['full_name']."</td>";
			else
				echo "<td>ACE</td>";
			echo "</td>";
			echo "<td>".$call['source']."</td>";
            echo "</tr>";
		}

		echo "</table>";
        exit;
    }


	function feedbackView()
	{
		$this->layout="list";

		//**********
		//CONDITIONS
		//Convert date from date picker to SQL format
		if ($this->params['url']['ffromdate'] != '')
			$this->params['url']['ffromdate'] = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
    else
      $this->params['url']['ffromdate'] = date("Y-m-d", strtotime(date("d M Y")) - 48*60*60);

		if ($this->params['url']['ftodate'] != '')
			$this->params['url']['ftodate'] = date("Y-m-d", strtotime($this->params['url']['ftodate']));
    else
      $this->params['url']['ftodate'] = date("Y-m-d", strtotime(date("M d Y")) - 24*60*60);

		//Pick today's date if no date
		$fdate = ($this->params['url']['ffromdate'] != '' ? $this->params['url']['ffromdate']: "" ) ;
		$tdate = ($this->params['url']['ftodate'] != '' ? $this->params['url']['ftodate']: "" ) ;
		$phone = $this->params['url']['fphone'];

		$allTechnicians = $this->Lists->Technicians();
    $ftechid = $this->params['url']['ftechid'];
		if ($this->Common->getLoggedUserRoleID()==1) $ftechid = $this->Common->getLoggedUserID();
		$allQuality = array('BAD'=>'BAD','OK'=>'OK','GOOD'=>'GOOD','EXCELLENT'=>'EXCELLENT');
    $fquality = $this->params['url']['fquality'];
		//CONDITIONS
		//**********

    $allJobTypes = $this->Lists->ListTable('ace_rp_order_types');

		$db =& ConnectionManager::getDataSource('default');
		if($fdate != '')
			$sqlConditions .= " AND a.job_date >= '".$this->Common->getMysqlDate($fdate)."'";
		if($tdate != '')
			$sqlConditions .= " AND a.job_date <= '".$this->Common->getMysqlDate($tdate)."'";
		if($ftechid)
			$sqlConditions .= " AND (a.job_technician1_id=$ftechid or a.job_technician2_id=$ftechid)";
		if($fquality)
			$sqlConditions .= " AND a.feedback_quality='$fquality'";
		if($phone != '')
			$sqlConditions .= " AND u.phone LIKE '%".$phone."%' ";

		//If user is Limited Telemarketer - role id=9
		//then show only orders that belongs to him
		if (($_SESSION['user']['role_id'] == 3) || ($_SESSION['user']['role_id'] == 9)) { // TELEMARKETER=3 or LIMITED TELEMARKETE9 ($_SESSION['user']['role_id'] == 3) ||
			$sqlConditions.= " AND a.booking_source_id=".$this->Common->getLoggedUserID();
		}

		$orders = array();
		$query = "SELECT 		a.id, a.order_number,
						a.job_date,
						a.order_type_id,
						a.customer_id,
						a.job_technician1_id,
						a.job_technician2_id,
						a.feedback_callback_date,
						a.feedback_price,
						a.feedback_comment,
						if (a.feedback_sticker=1,'Yes',if (a.feedback_sticker=0,'No','')) feedback_sticker,
						if (a.feedback_number=1,'Yes',if (a.feedback_number=0,'No','')) feedback_number,
						a.feedback_suggestion,
						a.feedback_quality,

						u.first_name,
						u.last_name,
						u.phone as customer_phone,
						u.callback_date

			FROM 			`ace_rp_orders` as a
			INNER JOIN		`ace_rp_customers` as u on ( a.customer_id = u.id )
			LEFT JOIN ace_rp_users u2
			ON u2.id = a.job_technician1_id
			WHERE 	order_status_id in (1,5) ".$sqlConditions."
			AND a.feedback_comment != ''
			ORDER BY u2.first_name
			";

		//echo $query;
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
				//Transfer all fields from the query result
				foreach ($row as $k => $v)
					$orders[$row['id']][$k] = $v;

			$orders[$row['id']]['customer_name'] = $row['first_name'].' '.$row['last_name'];
			$orders[$row['id']]['tech1_name'] = $allTechnicians[$row['job_technician1_id']];
			$orders[$row['id']]['tech2_name'] = $allTechnicians[$row['job_technician2_id']];
      		$orders[$row['id']]['job_type'] = $allJobTypes[$row['order_type_id']];

			$totals = $this->Common->getOrderTotal($row['id']);
			$orders[$row['id']]['total'] = $totals['sum_total'];
		}

		$this->set("previousPage",$previousPage);
		$this->set("nextPage",$nextPage);
		$this->set("orders", $orders);
		$this->set("phone", $phone);
		$this->set("ftechid", $ftechid);
		$this->set("fquality", $fquality);
		$this->set('allTechnician', $allTechnicians);
		$this->set('allQuality', $allQuality);
    $this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));
    $this->set('next_tdate', date("d M Y", strtotime($tdate) + 24*60*60));
		if($fdate!='')
			$this->set('fdate', date("d M Y", strtotime($fdate)));
		if($tdate!='')
			$this->set('tdate', date("d M Y", strtotime($tdate)));

	}

	function cancellationReason() {
		$this->set('cancellationReasons', $this->Lists->CancellationReasons());
	}

	function hInvoice() {
		$orderid = $_GET['orderid'];

		$this->layout = "h_invoice";
		$this->pageTitle = 'Handheld Invoice';

		$this->set('questions', $this->Question->findAll());
		$this->set('order', $this->Order->findById($orderid));
		$this->set('invoice', $this->Invoice->findByOrderId($orderid));
		$this->set('payments', $this->Payment->findByIdorder($orderid));
		$this->set('paymentMethods', $this->Lists->PaymentMethods());
		//$this->set('answersInvoice', $this->AnswersInvoice->findAll());
		//$invoice = $this->Invoice->findByOrderId($orderid);
	}

	function saveInvoice(){
		$invoice_id = $this->data['Invoice']['id'];
		if($this->Invoice->save($this->data['Invoice'])) {
			$db =& ConnectionManager::getDataSource('default');
			if($invoice_id == '') $invoice_id = $this->Invoice->getLastInsertId();
			$db->_execute("
					DELETE FROM ace_rp_answers_invoices
					WHERE invoice_id = $invoice_id
				");
			foreach($this->data['Answer'] as $key => $ans) {
				$db->_execute("
					INSERT INTO ace_rp_answers_invoices(answer_id, invoice_id, user_answer)
					VALUES($key, $invoice_id,'$ans')
				");
			}

			//save time in/out
			$this->Order->id = $this->data['Invoice']['order_id'];
			$this->Order->saveField('fact_job_beg', $this->data['Order']['fact_job_beg_hour'].':'.$this->data['Order']['fact_job_beg_min']);
			$this->Order->saveField('fact_job_end', $this->data['Order']['fact_job_end_hour'].':'.$this->data['Order']['fact_job_end_min']);

			//save payment
			$order_id = $this->data['Invoice']['order_id'];
			$creator = $this->Common->getLoggedUserID();
			$payment_method = $this->data['Payment']['payment_method'];
			$payment_date = date("Y-m-d", strtotime($this->data['Payment']['payment_date']));
			$paid_amount = $this->data['Payment']['paid_amount'];
			$payment_type = $this->data['Payment']['payment_type'];
			$auth_number = $this->data['Payment']['auth_number'];
			$notes = $this->data['Payment']['notes'];

			//remove previous payments
			$db->_execute("
				DELETE FROM ace_rp_payments
				WHERE idorder = $order_id
			");

			//add the new payment
			$db->_execute("
				INSERT INTO ace_rp_payments(idorder, creator, payment_method, payment_date, paid_amount, payment_type, auth_number, notes)
				VALUES ($order_id, '$creator', '$payment_method', '$payment_date', '$paid_amount', '$payment_type', '$auth_number', '$notes')
			");
		}

		$this->redirect($this->referer());
	}

	function searchAjax()
	{
		$conditions = array();
		$this->layout = "blank";
		if ($_GET['sq_crit'] == 'phone')
		{
			$sq_str = preg_replace("/[- \.]/", "", $_GET['sq_str']);
			$sq_str = preg_replace("/([?])*/", "[-]*", $sq_str);
			//$conditions[$_GET['sq_crit']] = "REGEX '".$sq_str."'";
			$conditions['phone REGEXP '] = $sq_str;
		}
		else
			$conditions[$_GET['sq_crit']] = "LIKE %".$_GET['sq_str']."%";


		if (($_GET['limit']=="undefined") || ($_GET['limit'] == ""))
			$limit = "0,100";
		else
			$limit = $_GET['limit'];

		if (($_GET['add']=="undefined") || ($_GET['add'] == ""))
			$add = 0;
		else
			$add = $_GET['add'];

		$sort = null;

		if ($_GET['sq_crit'] == 'phone')
		{
			$sql = "SELECT User.id, User.card_number, `User`.`first_name`, User.last_name,
				  User.postal_code, User.email,
				  CONCAT(User.address_unit,', ',User.address_street_number,', ',User.address_street) as address,
				  User.city, 'BC' as state,
				  User.phone, User.cell_phone, User.created, User.modified,
				  User.telemarketer_id, '' callback_note, User.callresult,
				  User.callback_date, CAST(User.callback_time AS TIME) callback_time,
				  User.lastcall_date
			FROM ace_rp_customers as User WHERE phone REGEXP '".$sq_str."' or cell_phone REGEXP '".$sq_str."' LIMIT ".$limit;
			$cust = $this->User->query($sql);
		}
		else if ($_GET['sq_crit'] == 'REF')
		{
			$sql = "SELECT o.id order_id, o.job_date,
				User.id, User.card_number, `User`.`first_name`, User.last_name,
				User.postal_code, User.email,
				CONCAT(User.address_unit,', ',User.address_street_number,', ',User.address_street) as address,
				User.city, 'BC' as state,
				User.phone, User.cell_phone, User.created, User.modified,
				User.telemarketer_id,  '' callback_note,User.callresult,
				User.callback_date, CAST(User.callback_time AS TIME) callback_time,
				User.lastcall_date
			FROM ace_rp_customers as User, ace_rp_orders o
			 WHERE User.id=o.customer_id and o.order_number='".$_GET['sq_str']."'";
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$result = $db->_execute($sql);
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				foreach ($row as $k => $v)
					$cust_temp['User'][$k] = $v;

				$cust_temp['User']['telemarketer_id']= $row['call_user_id'];
				$cust_temp['User']['callback_time']= date("H:i", strtotime($row['callback_time']));
				$cust_temp['Order']['job_date']= $row['job_date'];
				$cust_temp['Order']['id']= $row['order_id'];

				$cust[$row['id']] = $cust_temp;
			}
		}
		else if($_GET['sq_crit'] == 'callback_date')
		{
			$telem_clause = '';
			$telem_clause1 = '';
			if($this->Common->getLoggedUserRoleID() != 6) {
				$telem_clause = ' AND h.callback_user_id='.$this->Common->getLoggedUserID();
				$telem_clause1 = ' AND y.call_user_id='.$this->Common->getLoggedUserID();
			}

			$sql = "SELECT distinct
					 User.id, User.card_number, User.first_name, User.last_name,
					 User.postal_code, User.email,
					 CONCAT(User.address_unit,', ',User.address_street_number,', ',User.address_street) as address,
					 User.city,
					 User.phone, User.cell_phone,
					 h.call_user_id, h.call_note, h.call_result_id,
					 h.callback_date, h.callback_time,
				  if((h.callback_date=current_date())&&(TIME_TO_SEC(CAST(now() AS TIME))>= TIME_TO_SEC(CAST(h.callback_time AS TIME))-300),1,0) reminder_flag,
					 h.call_date
				FROM ace_rp_customers AS User, ace_rp_call_history h
				WHERE  User.id=h.customer_id and
					 (h.call_result_id in (0,1,2,4) or h.call_result_id is null)
				  AND h.callback_date LIKE '%".$_GET['sq_str']."%'
				  AND h.call_date <= h.callback_date
					 ".$telem_clause."
					and not exists
					(select * from ace_rp_call_history y
					  where y.customer_id=h.customer_id ".$telem_clause1."
						and (y.call_date>h.call_date
						 or y.call_date=h.call_date and y.call_time>h.call_time))
						  order by reminder_flag desc, h.callback_date, h.callback_time asc
								LIMIT ".$limit;

			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$result = $db->_execute($sql);
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				foreach ($row as $k => $v)
					$cust_temp['User'][$k] = $v;

				$cust_temp['User']['telemarketer_id']= $row['call_user_id'];
				$cust_temp['User']['callback_note']= str_replace("'","`",str_replace("\"","`",$row['call_note']));
				$cust_temp['User']['callresult']= $row['call_result_id'];
				$cust_temp['User']['lastcall_date']= $row['call_date'];
				$cust_temp['User']['callback_time']= date("H:i", strtotime($row['callback_time']));

				$cust[$row['id']] = $cust_temp;
			}
		}
		else if (($_GET['sq_crit'] != 'booking_source_id') && ($_GET['sq_crit'] != 'order_type_id') && ($_GET['sq_crit'] != 'callback_date'))
		{
			$cust = $this->Customer->findAll($conditions, null, $sort, $limit);
		}
		else	//by source, order type
		{
			$cust = $this->Order->findAll($conditions, null, $sort, $limit);
			$i = 0;
			foreach ($ord as $order)
			{
				$cust[$i]['User'] = $order['Customer'];
				$cust[$i]['Order'] = $order['Order'];
				$i++;
			}
		}

		foreach ($cust as $cnt => $cur)
		{
			foreach ($cur['User'] as $k => $v)
				$cust[$cnt]['User'][$k] = str_replace("'","`",str_replace("\"","`",$v));
		}

		$this->set('cust', $cust);
		$this->set('add', $add);
		$this->set('curpage', $_GET['curpage']);
		$this->set('sq_crit', $_GET['sq_crit']);
		$this->set('sq_str', $_GET['sq_str']);
		$this->set('call_results', $this->HtmlAssist->table2array($this->CallResult->findAll(), 'id', 'name'));
		$this->set('Common', $this->Common);
	}

	function truckSlotsAjax() {
		$this->layout = "blank";
		$city = $_GET['city'];
		$has_airduct = $_GET['has_airduct'];
		$date = date("Y-m-d", strtotime($_GET['date']));
		$date = $this->Common->getMysqlDate($date);
		if($has_airduct == 'true') {
			$query = "
				SELECT * FROM
					(SELECT ts.id, ts.name, o.job_time_beg, ts.truck,
						(SELECT COUNT(*) cnt
						FROM ace_rp_route_cities rc
						LEFT JOIN ace_rp_cities c
						ON rc.city_id = c.internal_id
						WHERE rc.route_id = ts.truck
						AND c.id = '$city'
						AND rc.route_date = '$date') is_valid
					FROM (SELECT *
					FROM ace_rp_timeslots,
					(SELECT 6 AS truck FROM DUAL) du
					WHERE ace_rp_timeslots.id != 6) ts
					LEFT JOIN (
						SELECT * FROM ace_rp_orders
						WHERE job_date = '$date'
						AND order_status_id NOT IN (3)
						AND job_truck IN(6)
					) o
					ON ts.from = o.job_time_beg AND ts.truck = o.job_truck
					ORDER BY ts.id) truck_slots
				WHERE job_time_beg IS NULL
				AND is_valid = 1
				AND DATE_ADD(CURDATE(), INTERVAL 2 DAY) = '$date'
				GROUP BY id
				ORDER BY id, truck
			";
		} else {
			$query = "
				SELECT * FROM
					(SELECT ts.id, ts.name, o.job_time_beg, ts.truck,
						(SELECT COUNT(*) cnt
						FROM ace_rp_route_cities rc
						LEFT JOIN ace_rp_cities c
						ON rc.city_id = c.internal_id
						WHERE rc.route_id = ts.truck
						AND c.id = '$city'
						AND rc.route_date = '$date') is_valid
					FROM (SELECT *
					FROM ace_rp_timeslots,
					(SELECT 2 AS truck FROM DUAL
					UNION ALL
					SELECT 3 FROM DUAL
					UNION ALL
					SELECT 4 FROM DUAL
					UNION ALL
					SELECT 5 FROM DUAL
					UNION ALL
					SELECT 6 FROM DUAL) du
					WHERE ace_rp_timeslots.id != 6) ts
					LEFT JOIN (
						SELECT * FROM ace_rp_orders
						WHERE job_date = '$date'
						AND order_status_id NOT IN (3)
						AND job_truck IN(2,3,4,5,6)
					) o
					ON ts.from = o.job_time_beg AND ts.truck = o.job_truck
					ORDER BY ts.id) truck_slots
				WHERE job_time_beg IS NULL
				AND is_valid = 1
				AND DATE_ADD(CURDATE(), INTERVAL 2 DAY) = '$date'
				GROUP BY id
				ORDER BY id, truck
			";
		}

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$timeslots[$row['id']]['id']= $row['id'];
			$timeslots[$row['id']]['name']= $row['name'];
			$timeslots[$row['id']]['job_time_beg']= $row['job_time_beg'];
			$timeslots[$row['id']]['truck']= $row['truck'];
			$timeslots[$row['id']]['is_valid']= $row['is_valid'];
		}

		$this->set('timeslots', $timeslots);
		$this->set('date', $_GET['date']);
		$this->set('city', $_GET['city']);
	}

	function onlineItemsAjax($n) {
		$this->layout = "blank";

		$temp[0]['class'] = '0';
		$temp[0]['item_id'] = '18';
		$temp[0]['item_category_id'] = '1';
		$temp[0]['name'] = 'Furnace Service (Basic)';
		$temp[0]['quantity'] = '1';
		$temp[0]['price'] = '109';
		$temp[0]['discount'] = '20';
		$temp[0]['addition'] = '0';

		$temp[1]['class'] = '0';
		$temp[1]['item_id'] = '8';
		$temp[1]['item_category_id'] = '1';
		$temp[1]['name'] = 'Air Duct Cleaning(15 Vents) - SILVER Package #2';
		$temp[1]['quantity'] = '1';
		$temp[1]['price'] = '229';
		$temp[1]['discount'] = '0';
		$temp[1]['addition'] = '0';

		$temp[2]['class'] = '0';
		$temp[2]['item_id'] = '14';
		$temp[2]['item_category_id'] = '1';
		$temp[2]['name'] = 'Boiler Service';
		$temp[2]['quantity'] = '1';
		$temp[2]['price'] = '139';
		$temp[2]['discount'] = '0';
		$temp[2]['addition'] = '0';

		$temp[3]['class'] = '0';
		$temp[3]['item_id'] = '1031';
		$temp[3]['item_category_id'] = '1';
		$temp[3]['name'] = 'Others - Online Inquiry';
		$temp[3]['quantity'] = '1';
		$temp[3]['price'] = '0';
		$temp[3]['discount'] = '0';
		$temp[3]['addition'] = '0';

		$i = 0;
		foreach(explode("-",$n) as $temp_index) {
			$items[$i++] = $temp[$temp_index];
		}

		$this->set('items', $items);
	}

	function onlineHistoryAjax() {
		$this->layout = "blank";

		$hk = $_POST['history_key'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			SELECT *
			FROM ace_rp_customers
			WHERE CONCAT(RIGHT(CONCAT('ABCDEFGH', id), 8), HEX(RIGHT(id, 1))) = '$hk'
			LIMIT 1;
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$customer['id']= $row['id'];
			$customer['first_name']= $row['first_name'];
			$customer['last_name']= $row['last_name'];
			$customer['address']= $row['address'];
			$customer['city']= $row['city'];
			$customer['phone']= $row['phone'];
			$phone = $row['phone'];
		}

		$query = "
			SELECT o.*, DATE_FORMAT(o.job_date, '%W, %M %D, %Y') job_date_name, ot.name order_type_name
			FROM ace_rp_orders o
			LEFT JOIN ace_rp_order_types ot
			ON o.order_type_id = ot.id
			WHERE o.customer_phone = REPLACE('$phone', '-', '')
			AND o.order_status_id = 5
			AND o.order_type_id IS NOT NULL
			ORDER BY o.job_date DESC
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$orders[$row['id']]['id']= $row['id'];
			$orders[$row['id']]['order_number']= $row['order_number'];
			$orders[$row['id']]['job_date']= $row['job_date'];
			$orders[$row['id']]['job_date_name']= $row['job_date_name'];
			$orders[$row['id']]['order_type_name']= $row['order_type_name'];
		}

		$query = "
			SELECT oi.*
			FROM ace_rp_orders o
			LEFT JOIN ace_rp_order_items oi
			ON o.id = oi.order_id
			WHERE o.customer_phone = REPLACE('$phone', '-', '')
			AND oi.order_id IS NOT NULL
			AND o.order_status_id = 5
		";

		$total = 0;

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$items[$row['id']]['id']= $row['id'];
			$items[$row['id']]['order_id']= $row['order_id'];
			$items[$row['id']]['name']= $row['name'];
			$items[$row['id']]['price']= ($row['price'] * $row['quantity']) - $row['discount'] + $row['addition'];
		}

		$this->set('customer', $customer);
		$this->set('orders', $orders);
		$this->set('items', $items);
		$this->set('phone', $phone);

	}

	function maintenancePackage() {
		$this->layout = "blank";

		$customer_id = $_POST['customer_id'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			SELECT COUNT(*) has_package,
				IF(CURDATE() > DATE_ADD(o.job_date, INTERVAL 3 YEAR), 'Expired', 'Active') AS package_status,
				o.job_date purchase_date,
				DATE_ADD(o.job_date, INTERVAL 3 YEAR) expiry_date
			FROM ace_rp_order_items oi
			LEFT JOIN ace_rp_orders o
			ON o.id = oi.order_id
			WHERE oi.item_id IN (5,1025)
			AND o.job_date >= '2011-07-08'
			AND o.customer_id = $customer_id
			ORDER BY o.job_date DESC
			LIMIT 1
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$details['has_package']= $row['has_package'];
			$details['package_status']= $row['package_status'];
			$details['purchase_date']= $row['purchase_date'];
			$details['expiry_date']= $row['expiry_date'];
		}

		$this->set('details', $details);
	}

	function confirmedBy() {
		$this->layout = "blank";

		$order_id = $_POST['order_id'];

		if(isset($order_id)) {
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

			$query = "
				SELECT u.first_name
				FROM ace_rp_orders_log ol
				LEFT JOIN ace_rp_users u
				ON ol.change_user_id = u.id
				WHERE ol.id = $order_id
				AND ol.order_substatus_id = 2
				ORDER BY ol.change_date
				LIMIT 1
			";

			$result = $db->_execute($query);
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$first_name = $row['first_name'];
			}

			$this->set('first_name', $first_name);
		}
	}

	function timeSlots() {
		$this->layout = "blank";

		$days_ahead = 31;

		$city_id = $_GET['city_id'];
		$user_id = $_GET['user_id'];
		if(!isset($city_id)) $city_id = 0;

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			SELECT *
			FROM ace_rp_cities
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$cities[$row['internal_id']]['name']= $row['name'];
		}


		$query = "
			SELECT DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 0 DAY), '%d') 'dates',
				SUBSTR(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 0 DAY), '%W'), 1, 2) 'weeks',
				SUBSTR(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 0 DAY), '%w'), 1, 2) 'week_number',
				SUBSTR(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 0 DAY), '%b'), 1, 2) 'months',
				DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 0 DAY), '%Y-%m-%d') full_date
		";

		for($i = 1; $i < $days_ahead; $i++) {
			$query .= "
				UNION
				SELECT DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL $i DAY), '%d'),
					SUBSTR(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL $i DAY), '%W'), 1, 2),
					SUBSTR(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL $i DAY), '%w'), 1, 2),
					SUBSTR(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL $i DAY), '%b'), 1, 2),
					DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL $i DAY), '%Y-%m-%d')
			";
		}

		$result = $db->_execute($query);
		$i = 0;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$dates[$i]['dates']= $row['dates'];
			$dates[$i]['weeks']= $row['weeks'];
			$dates[$i]['week_number']= $row['week_number'];
			$dates[$i]['months']= $row['months'];
			$dates[$i++]['full_date']= $row['full_date'];
		}

		$query = "
			SELECT ts.id, ts.name, ts.from, ts.to,
		";

		for($i = 0; $i < $days_ahead; $i++) {
			$query .= "
				(SELECT
					SUM(
						IF(
							(SELECT IF(COUNT(*) = 0,1,0)
							FROM ace_rp_orders
							WHERE job_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND order_status_id NOT IN(3,2)
							AND (
								(HOUR(ts.from) >= HOUR(job_time_beg) AND HOUR(ts.from) < HOUR(job_time_end))
								OR
								(HOUR(ts.to) > HOUR(job_time_beg) AND HOUR(ts.to) <= HOUR(job_time_end))
							)
							AND job_truck = il.id)
						AND
							(SELECT COUNT(route_id)
							FROM ace_rp_route_cities
							WHERE route_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND city_id = $city_id
							AND route_id = il.id)
							+
							(SELECT IF(COUNT(route_id) = 0, 1, 0)
							FROM ace_rp_route_cities
							WHERE route_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND route_id = il.id)
						AND
							(SELECT IF(COUNT(*) = 0,1,0)
							FROM ace_rp_pending_timeslots
							WHERE route_id = il.id
							AND job_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND ((HOUR(ts.from) >= HOUR(job_time_beg) AND HOUR(ts.from) < HOUR(job_time_end))
								OR
								(HOUR(ts.to) > HOUR(job_time_beg) AND HOUR(ts.to) <= HOUR(job_time_end)))
							AND user_id != $user_id)
							,
						1,0)
					) 'slots'
				FROM ace_rp_inventory_locations il
				WHERE il.route_type = 1) '$i',";
		}

		$query = substr($query, 0, -1);

		$query .= "
			FROM ace_rp_timeslots ts
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$furnaceslots[$row['id']]['name']= $row['name'];
			$furnaceslots[$row['id']]['from']= $row['from'];
			$furnaceslots[$row['id']]['to']= $row['to'];
			for($i=0;$i < $days_ahead; $i++) $furnaceslots[$row['id']][$i]= $row[$i];
		}

		$query = "
			SELECT ts.id, ts.name, ts.from, ts.to,
		";

		for($i = 0; $i < $days_ahead; $i++) {
			$query .= "
				(SELECT
					SUM(
						IF(
							(SELECT IF(COUNT(*) = 0,1,0)
							FROM ace_rp_orders
							WHERE job_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND order_status_id NOT IN(3,2)
							AND (
								(HOUR(ts.from) >= HOUR(job_time_beg) AND HOUR(ts.from) < HOUR(job_time_end))
								OR
								(HOUR(ts.to) > HOUR(job_time_beg) AND HOUR(ts.to) <= HOUR(job_time_end))
							)
							AND job_truck = il.id)
						AND
							(SELECT COUNT(route_id)
							FROM ace_rp_route_cities
							WHERE route_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND city_id = $city_id
							AND route_id = il.id)
							+
							(SELECT IF(COUNT(route_id) = 0, 1, 0)
							FROM ace_rp_route_cities
							WHERE route_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND route_id = il.id)
						AND
							(SELECT IF(COUNT(*) = 0,1,0)
							FROM ace_rp_pending_timeslots
							WHERE route_id = il.id
							AND job_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND ((HOUR(ts.from) >= HOUR(job_time_beg) AND HOUR(ts.from) < HOUR(job_time_end))
								OR
								(HOUR(ts.to) > HOUR(job_time_beg) AND HOUR(ts.to) <= HOUR(job_time_end)))
							AND user_id != $user_id)
							,
						1,0)
					) 'slots'
				FROM ace_rp_inventory_locations il
				WHERE il.route_type = 2) '$i',";
		}

		$query = substr($query, 0, -1);

		$query .= "
			FROM ace_rp_timeslots ts
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$airductslots[$row['id']]['name']= $row['name'];
			$airductslots[$row['id']]['from']= $row['from'];
			$airductslots[$row['id']]['to']= $row['to'];
			for($i=0;$i < $days_ahead; $i++) $airductslots[$row['id']][$i]= $row[$i];
		}

		$query = "
			SELECT ts.id, ts.name, ts.from, ts.to,
		";

		for($i = 0; $i < $days_ahead; $i++) {
			$query .= "
				(SELECT
					SUM(
						IF(
							(SELECT IF(COUNT(*) = 0,1,0)
							FROM ace_rp_orders
							WHERE job_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND order_status_id NOT IN(3,2)
							AND (
								(HOUR(ts.from) >= HOUR(job_time_beg) AND HOUR(ts.from) < HOUR(job_time_end))
								OR
								(HOUR(ts.to) > HOUR(job_time_beg) AND HOUR(ts.to) <= HOUR(job_time_end))
							)
							AND job_truck = il.id)
						AND
							(SELECT COUNT(route_id)
							FROM ace_rp_route_cities
							WHERE route_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND city_id = $city_id
							AND route_id = il.id)
							+
							(SELECT IF(COUNT(route_id) = 0, 1, 0)
							FROM ace_rp_route_cities
							WHERE route_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND route_id = il.id)
						AND
							(SELECT IF(COUNT(*) = 0,1,0)
							FROM ace_rp_pending_timeslots
							WHERE route_id = il.id
							AND job_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND ((HOUR(ts.from) >= HOUR(job_time_beg) AND HOUR(ts.from) < HOUR(job_time_end))
								OR
								(HOUR(ts.to) > HOUR(job_time_beg) AND HOUR(ts.to) <= HOUR(job_time_end)))
							AND user_id != $user_id)
							,
						1,0)
					) 'slots'
				FROM ace_rp_inventory_locations il
				WHERE il.route_type = 4) '$i',";
		}

		$query = substr($query, 0, -1);

		$query .= "
			FROM ace_rp_timeslots ts
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$installationslots[$row['id']]['name']= $row['name'];
			$installationslots[$row['id']]['from']= $row['from'];
			$installationslots[$row['id']]['to']= $row['to'];
			for($i=0;$i < $days_ahead; $i++) $installationslots[$row['id']][$i]= $row[$i];
		}

		$this->set('cities', $cities);
		$this->set('city_id', $city_id);
		$this->set('furnaceslots', $furnaceslots);
		$this->set('airductslots', $airductslots);
		$this->set('installationslots', $installationslots);
		$this->set('dates', $dates);
		$this->set('days_ahead', $days_ahead);
	}

	function reserveTimeslot() {
		$this->layout = "blank";

		$user_id = $_POST['user_id'];

		if(!isset($user_id)) $user_id = $this->Common->getLoggedUserID();

		$job_date = $_POST['job_date'];
		$week_number = $_POST['week_number'];
		$job_time_beg = $_POST['job_time_beg'];
		$job_time_end = $_POST['job_time_end'];
		$job_time_name = $_POST['job_time_name'];
		$city_id = $_POST['city_id'];
		$route_type = $_POST['route_type'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			DELETE FROM ace_rp_pending_timeslots
			WHERE user_id = $user_id
		";

		$result = $db->_execute($query);

		$query = "
			SELECT *
			FROM (
				SELECT il.id, il.name, il.tech1_day$week_number tech1, il.tech2_day$week_number tech2,
					  IF(
						  (SELECT IF(COUNT(*) = 0,1,0)
						  FROM ace_rp_orders
						  WHERE job_date = '$job_date'
						  AND order_status_id NOT IN(3,2)
						  AND (
							  (HOUR('$job_time_beg') >= HOUR(job_time_beg) AND HOUR('$job_time_beg') < HOUR(job_time_end))
							  OR
							  (HOUR('$job_time_end') > HOUR(job_time_beg) AND HOUR('$job_time_end') <= HOUR(job_time_end))
						  )
						  AND job_truck = il.id)
					  AND
						  (SELECT COUNT(route_id)
						  FROM ace_rp_route_cities
						  WHERE route_date = '$job_date'
						  AND city_id = $city_id
						  AND route_id = il.id)
						  +
						  (SELECT IF(COUNT(route_id) = 0, 1, 0)
						  FROM ace_rp_route_cities
						  WHERE route_date = '$job_date'
						  AND route_id = il.id)
						  ,
					  1,0) slots,
					  (SELECT name FROM ace_rp_cities WHERE internal_id = $city_id) city_name,
					  RIGHT(CONCAT('0', HOUR('$job_time_beg')), 2) job_time_beg,
					  RIGHT(CONCAT('0', HOUR('$job_time_end')), 2) job_time_end,
					  (SELECT COUNT(*) FROM ace_rp_pending_timeslots
					  WHERE route_id = il.id
					  AND job_date = '$job_date'
					  AND ((HOUR('$job_time_beg') >= HOUR(job_time_beg) AND HOUR('$job_time_beg') < HOUR(job_time_end))
					  	OR
						(HOUR('$job_time_beg') > HOUR(job_time_beg) AND HOUR('$job_time_beg') <= HOUR(job_time_end)))
					  AND user_id != $user_id
					  ) reserved
			  	FROM ace_rp_inventory_locations il
			  	WHERE il.route_type = $route_type) available
			WHERE slots > 0
			AND reserved = 0
		  	LIMIT 1
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$slot['id'] = $row['id'];
			$slot['name'] = $row['name'];
			$slot['slot'] = $row['slots'];
			$slot['job_time_beg'] = $row['job_time_beg'];
			$slot['job_time_end'] = $row['job_time_end'];
			$slot['city_name'] = $row['city_name'];
			$slot['tech1'] = $row['tech1'];
			$slot['tech2'] = $row['tech2'];
		}

		if(count($slot) > 0) {
			$this->set('availability', "Available");
			$this->set('availability_class', "available");
			$this->set('city_area', $slot['city_name']);
			$this->set('job_date', $job_date);
			$this->set('job_date_name', date("j M Y", strtotime($job_date)));
			$this->set('job_truck', $slot['id']);
			$this->set('job_truck_name', $slot['name']);
			$this->set('job_time_beg', $slot['job_time_beg']);
			$this->set('job_time_end', $slot['job_time_end']);
			$this->set('job_time_name', $job_time_name);
			$this->set('tech1', $slot['tech1']);
			$this->set('tech2', $slot['tech2']);
			$this->set('slot', $slot);

			$query = "
				INSERT INTO ace_rp_pending_timeslots
				VALUES ($user_id,".$slot['id'].",'$job_date','$job_time_beg','$job_time_end',NOW())
			";

			$result = $db->_execute($query);
		} else {
			$this->set('availability', "Unavailable");
			$this->set('availability_class', "unavailable");
			$this->set('city_area', "");
			$this->set('job_date', $job_date);
			$this->set('job_date_name', date("j M Y", strtotime($job_date)));
			$this->set('job_truck', 0);
			$this->set('job_truck_name', "");
			$this->set('job_time_beg', "");
			$this->set('job_time_end', "");
			$this->set('job_time_name', $job_time_name);
		}


	}

	function cancelTimeslot() {
		$this->layout = "blank";

		$user_id = $_POST['user_id'];
		$job_date = $_POST['job_date'];
		$week_number = $_POST['week_number'];
		$job_time_beg = $_POST['job_time_beg'];
		$job_time_end = $_POST['job_time_end'];
		$job_time_name = $_POST['job_time_name'];
		$city_id = $_POST['city_id'];
		$route_type = $_POST['route_type'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			DELETE FROM ace_rp_pending_timeslots
			WHERE user_id = $user_id
		";

		$result = $db->_execute($query);
	}

	function onlineCustomerFeedback() {
		$this->layout = "blank";

		$hk = $_POST['history_key'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			SELECT *
			FROM ace_rp_customers
			WHERE CONCAT(RIGHT(CONCAT('ABCDEFGH', id), 8), HEX(RIGHT(id, 1))) = '$hk'
			LIMIT 1;
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$customer['id']= $row['id'];
			$customer['first_name']= $row['first_name'];
			$customer['last_name']= $row['last_name'];
			$customer['address']= $row['address'];
			$customer['city']= $row['city'];
			$customer['phone']= $row['phone'];
			$phone = $row['phone'];
		}
		$this->set('history_key', $_POST['history_key']);
		$this->set('customer', $customer);
	}

	function onlineSaveCustomerFeedback() {
		$this->layout = "blank";

		$comment_type = $_POST['comment_type'];
		$customer_id = $_POST['customer_id'];

		if(isset($_POST['order_number'])) {
		$order_number = $_POST['order_number'];
		} else {
		$order_number = 0;
		}

		$text_comment = $_POST['text_comment'];
		$rating = $_POST['rating'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			INSERT INTO ace_rp_feedbacks(comment, comment_type, customer_id, order_number, rating, comment_date)
			VALUES ('$text_comment', $comment_type, $customer_id, $order_number, $rating, NOW());
		";

		$result = $db->_execute($query);
	}

	function invoiceTablet() {
		if($this->Common->getLoggedUserRoleID() == 6){
             $this->redirect(BASE_PATH."pages/main");exit;
        }
        else{
         $todayDate = date('Y-m-d')	;
		 $db =& ConnectionManager::getDataSource($this->User->useDbConfig);        	
         $techId = $_SESSION['user']['id'];
         $query = "SELECT MAX(job_date) as max_job_date from ace_rp_orders where (booking_source_id=".$techId." OR booking_source2_id=".$techId." OR job_technician1_id=".$techId." OR job_technician2_id=".$techId.") AND tech_visible = 1";
         
         $result = $db->_execute($query);
		 $getCommDate = mysql_fetch_array($result, MYSQL_ASSOC);

		 $commDate1 = $getCommDate['max_job_date'];
		 // #LOKI - If the max job date is equal to tady's date get the previous job date.
		if(($commDate1 == $todayDate) || ($commDate1 > $todayDate)) {
		 	$commDate1 = $this->checkJobAssigned($techId, $todayDate);
		 	//'2019-03-29'
		 } 

		 if(!empty($commDate1) || $commDate1 != '' )
		 {
			 $query = "SELECT comm_date from ace_rp_tech_done_comm where comm_date='".$commDate1."' AND tech_id=".$techId;
			 $result = $db->_execute($query);
			 $row = mysql_fetch_array($result, MYSQL_ASSOC);		 
			 $commDate = $row['comm_date'];
			 // if((empty($commDate) || $commDate == '') && ($todayDate != $commDate1))
			 if((empty($commDate) || $commDate == ''))
			 {
			 	$urlEncode = 'action=view&order=&sort=&currentPage=&comm_oper=&ftechid='.$techId.'&selected_job=&selected_commission_type=&job_option=1&ffromdate='.date('d M Y',strtotime($commDate1)).'&cur_ref=';
			 	$this->set('isShow','1');

			 	$this->set('URL', $urlEncode);
			 }
		 }

			$this->layout = "blank";

			$this->set('jobs', $this->Order->findAll(array(
				"Order.job_date" => date("Y-m-d"),  
				"OR" => array("Order.booking_source_id" => $this->Common->getLoggedUserID(),
					"Order.booking_source2_id" => $this->Common->getLoggedUserID(),
					"Order.job_technician1_id" => $this->Common->getLoggedUserID(),
					"Order.job_technician2_id" => $this->Common->getLoggedUserID()
				))
			, null, "Order.job_time_beg ASC"));

			$order_id = $_GET['id'];

			if(isset($order_id)) {
				//save time out
				$this->Order->id = $order_id;
				$this->Order->saveField('fact_job_end', date("H:i:s"));
			}
		}
	}

	function invoiceTabletOverview() {
		$this->layout = "blank";

		$order_id = $_GET['order_id'];
		$orderDetails = $this->Order->findById($order_id);
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "SELECT recording_name from ace_rp_call_recordings where order_number=".$orderDetails['Order']['order_number'];
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$this->set('recordingName', $row['recording_name']);
		$this->set('order', $orderDetails);
		$this->set('invoice', $this->Invoice->findByOrderId($order_id));

		$this->set('jobs', $this->Order->findAll(array(
			"Order.job_date" => date("Y-m-d"),  "Order.tech_visible" => 1,
			"OR" => array("Order.booking_source_id" => $this->Common->getLoggedUserID(),
				"Order.booking_source2_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician1_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician2_id" => $this->Common->getLoggedUserID()
			))
		, null, "Order.job_time_beg ASC"));

		$this->set('order_id', $order_id);


		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
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
				ORDER BY n.note_date ASC
			";

			$result = $db->_execute($query);
			$i = 0;
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
	}

	function invoiceTabletOverviewSave() {
		$order_id = $this->data['Invoice']['order_id'];
		$order_type_id = $this->data['Invoice']['order_type_id'];

		//save time in
		$this->Order->id = $order_id;
		if($this->data['Order']['fact_job_beg'] == '00:00:00')
			$this->Order->saveField('fact_job_beg', date("H:i:s"));

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
			$this->redirect("orders/invoiceTabletQuestions?order_id=$order_id&order_type_id=$order_type_id");
		} else {
			$this->redirect("orders/invoiceQuestions?order_id=$order_id&order_type_id=$order_type_id");
		}

	}

	function invoiceTabletEmail() {
		$this->layout = "blank";

		$order_id = $_GET['order_id'];

		$this->set('order', $this->Order->findById($order_id));
		$this->set('invoice', $this->Invoice->findByOrderId($order_id));

		$this->set('jobs', $this->Order->findAll(array(
			"Order.job_date" => date("Y-m-d"),  "Order.tech_visible" => 1,
			"OR" => array("Order.booking_source_id" => $this->Common->getLoggedUserID(),
				"Order.booking_source2_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician1_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician2_id" => $this->Common->getLoggedUserID()
			))
		, null, "Order.job_time_beg ASC"));

		$this->set('order_id', $order_id);
	}

	function saveInvoiceTabletEmail() {
		$order_id = $this->data['Invoice']['order_id'];
		$email = $this->data['Invoice']['email'];

		$this->set('order_id', $order_id);

		//$user_id = $this->Common->getLoggedUserID();

		$db =& ConnectionManager::getDataSource('default');

		$subject = 'Ace Services Ltd';
		$headers = "From: info@acecare.ca\n";
		$headers .= "Content-Type: text/html; charset=iso-8859-1\n";

		$msg = file_get_contents("http://hvacproz.ca/acesys/index.php/orders/invoiceTabletPrint?order_id=$order_id&type=office");
		$res = mail($email, $subject, $msg, $headers);

		$this->redirect("orders/invoiceTabletPrint?order_id=$order_id");
	}

	function invoiceQuestions() {
		$this->layout = "blank";

		$order_id = $_GET['order_id'];
		$last_order_id = $_GET['last_order_id'];

		$job = $this->Order->findById($order_id);
		$last_job = $this->Order->findById($last_order_id);

		$this->set('order', $job);
		$this->set('last_order', $last_job);
		$this->set('invoice', $this->Invoice->findByOrderId($order_id));

		$order_type_id = $job['Order']['order_type_id'];

		$jobs = $this->Order->findAll(array(
			"Order.job_date" => date("Y-m-d"),
			"OR" => array("Order.booking_source_id" => $this->Common->getLoggedUserID(),
				"Order.booking_source2_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician1_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician2_id" => $this->Common->getLoggedUserID()
			))
		, null, "Order.job_time_beg ASC");

		$this->set('jobs', $jobs);

		$this->set('order_id', $order_id);

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		if(isset($last_order_id) && $last_order_id != "") {
			$temp_order_type_id = $last_job['Order']['order_type_id'];
			$temp_order_id = $last_order_id;
		} else {
			$temp_order_type_id = $order_type_id;
			$temp_order_id = $order_id;
		}

		$query = "
			SELECT *
			FROM ace_rp_order_types_questions
			WHERE order_type_id = $temp_order_type_id
			AND for_tech = 1
		";

		$result = $db->_execute($query);

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$questions[$row['id']]['order_type_id'] = $row['order_type_id'];
			$questions[$row['id']]['for_office'] = $row['for_office'];
			$questions[$row['id']]['for_tech'] = $row['for_tech'];
			$questions[$row['id']]['for_print'] = $row['for_print'];
			$questions[$row['id']]['question'] = $row['question'];
			$questions[$row['id']]['suggestions'] = $row['suggestions'];
			$questions[$row['id']]['response'] = $row['response'];
			$questions[$row['id']]['question_number'] = $row['question_number'];
			if(trim($row['answers']) != "") {
				$questions[$row['id']]['answers'] = explode(",", $row['answers']);
			} else {
				$questions[$row['id']]['answers'] = "text";
			}
			if(trim($row['suggestions']) != "") {
				$questions[$row['id']]['suggestions'] = explode(",", $row['suggestions']);
			} else {
				$questions[$row['id']]['suggestions'] = "text";
			}
			if(trim($row['response']) != "") {
				$questions[$row['id']]['response'] = explode(",", $row['response']);
			} else {
				$questions[$row['id']]['response'] = "text";
			}
		}

		$query = "
			SELECT *
			FROM ace_rp_orders_questions
			WHERE order_id = $temp_order_id
		";

		$result = $db->_execute($query);

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$order_answers[$row['question_id']]['local_answer'] = $row['local_answer'];
			$order_answers[$row['question_id']]['answers'] = $row['answers'];
			$order_answers[$row['question_id']]['suggestions'] = $row['suggestions'];
			$order_answers[$row['question_id']]['response'] = $row['response'];
			$order_answers[$row['question']]['local_answer'] = $row['local_answer'];
			$order_answers[$row['question']]['answers'] = $row['answers'];
			$order_answers[$row['question']]['suggestions'] = $row['suggestions'];
			$order_answers[$row['question']]['response'] = $row['response'];
		}

		$this->set('order_id', $order_id);
		$this->set('last_order_id', $last_order_id);
		$this->set('questions', $questions);
		$this->set('order_answers', $order_answers);
		$this->set('answers', $answers);
		$this->set('values', $values);
	}

	function saveInvoiceQuestions() {
		$order_id = $this->data['Invoice']['order_id'];
		$order_type_id = $this->data['Invoice']['order_type_id'];
		$last_order_id = $this->data['Invoice']['last_order_id'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		if(isset($last_order_id) && $last_order_id != "") {
			$temp_order_id = $last_order_id;
		} else {
			$temp_order_id = $order_id;
		}

		$query = "
			DELETE
			FROM ace_rp_orders_questions
			WHERE for_tech = 1
			AND order_id = $temp_order_id
		";
		$result = $db->_execute($query);

		foreach($this->data['Question'] as $question_id => $answer) {
			$answers = $answer['answers'];
			$local_answer = $answer['local_answer'];
			$for_office = $answer['for_office'];
			$for_tech = $answer['for_tech'];
			$question = $answer['question'];
			$suggestions = $answer['suggestions'];
			$response = $answer['response'];
			$question_number = $answer['question_number'];

			echo "$temp_order_id, $for_office, $for_tech, '$question', '$local_answer', $question_number, $question_id, '$answers' <br />";

			$query = "
				INSERT INTO ace_rp_orders_questions (order_id, for_office, for_tech, question, local_answer, question_number, question_id, answers, suggestions, response)
				VALUES ($temp_order_id, $for_office, $for_tech, '$question', '$local_answer', $question_number, $question_id, '$answers', '$suggestions', '$response');
			";
			$result = $db->_execute($query);
		}

		if(isset($last_order_id) && $last_order_id != "") {
			$query = "
				UPDATE ace_rp_orders
				SET order_status_id = 1
				WHERE id = $temp_order_id
			";
			$result = $db->_execute($query);
		}

		$this->redirect("orders/invoiceTabletItems?order_id=$order_id");
	}

	function invoiceTabletItems() {
		$this->layout = "blank";

		$order_id = $_GET['order_id'];
		if(isset($_GET['new_entry'])){
			$new_entry = $_GET['new_entry'];
		}
		
		$this->set('this_job', $this->Order->findById($order_id));

		/*if($this->_needsApproval($order_id))
			$this->redirect("orders/invoiceTabletStandby?order_id=$order_id");*/

		$delete_attached = $_GET['q'];

		if($delete_attached == 1)  {
			$this->Order->delete($order_id);
		}

		$order = $this->Order->findById($order_id);
	
		$city = $order['Customer']['city'];
		// $h_booked='';
		// $h_tech='';
		// $num_items = 0;
		// foreach ($order['BookingItem'] as $oi)
		// {
		// 	if ($oi['class']==0)
		// 	{
		// 		$h_booked .= '<tr id="order_'.$num_items.'" class="booked">';
		// 		$h_booked .= $this->_itemHTML($num_items, $oi, true);
		// 		$h_booked .= '</tr>';
		// 	}
		// 	else
		// 	{
		// 		$h_tech .= '<tr id="order_'.$num_items.'" class="extra">';
		// 		$h_tech .= $this->_itemHTML($num_items, $oi, true);
		// 		$h_tech .= '</tr>';
		// 	}
		// 	$num_items++;
		// }
		// foreach ($this->data['BookingCoupon'] as $oi)
		// {
		// 	$oi['price'] = 0-$oi['price'];
		// 	$oi['quantity'] = 1;
		// 	$oi['name'] = 'Discount';
		// 	$h_booked .= '<tr id="order_'.$num_items.'" class="booked">';
		// 	$h_booked .= $this->_itemHTML($num_items, $oi, true);
		// 	$h_booked .= '</tr>';
		// 	$num_items++;
		// }
		// $this->set('booked_items', $h_booked);
		// $this->set('tech_items', $h_tech);
		// $this->set('num_items', $num_items);
		
		$this->set('order', $order);
		$this->set('last_order', $this->Order->findByJobEstimateId($order_id));
		$this->set('invoice', $this->Invoice->findByOrderId($order_id));

		$this->set('jobs', $this->Order->findAll(array(
			"Order.job_date" => date("Y-m-d"),  "Order.tech_visible" => 1,
			"OR" => array("Order.booking_source_id" => $this->Common->getLoggedUserID(),
				"Order.booking_source2_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician1_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician2_id" => $this->Common->getLoggedUserID()
			))
		, null, "Order.job_time_beg ASC"));

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "
			DELETE
			FROM ace_rp_orders
			WHERE order_status_id = 0
		";
		$result = $db->_execute($query);

		$query = "
			SELECT internal_id
			FROM ace_rp_cities
			WHERE id = '$city'
		";
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{
			$city_id = $row['internal_id'];
		}

		// $query = "
		// 	SELECT c.*
		// 	FROM ace_rp_item_job_categories ic
		// 	LEFT JOIN ace_iv_categories c
		// 	ON c.id = ic.item_category_id
		// 	WHERE ic.job_type_id = (SELECT order_type_id FROM ace_rp_orders WHERE id = $order_id)
		// ";
		$query = "
			SELECT *
			FROM  ace_iv_categories where active=1";
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{
			$job_type_id[$row['id']] = $row['name'];
		}

		if($new_entry == 'tech_agent'){
			$query = "UPDATE ace_rp_orders SET tech_visible_agent = 1 WHERE id = $order_id";
			$db->_execute($query);
		}
		
		$this->set('job_type_id', $job_type_id);
		$this->set('city_id', $city_id);
		$this->set('order_id', $order_id);
		$this->set('job_types',$this->Lists->OrderTypes());
		$this->set('payment_methods', $this->HtmlAssist->table2array($this->Order->PaymentMethod->findAll(), 'id', 'name'));
	}

	function getCustomerEmailAdd($order_id){
		if($order_id!=''){
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$query = "SELECT email
				FROM ace_rp_customers			
				WHERE id = $order_id";
			$result = $db->_execute($query);			
			$row = mysql_fetch_array($result);
			//echo 'Email =><pre>';print_r($row);exit;
			return $row['email'];
		}			
	}
	function saveInvoiceTabletItems() {
		//echo '<BR>REQUEST<BR><pre>';print_r($_REQUEST);exit;
		
		$fromTech = isset($_GET['fromTech']) ? $_GET['fromTech'] : 0;
		if(isset($_REQUEST['order_id'])){
			$order_id = $_REQUEST['order_id'];	
		}else{
			$order_id = $this->data['Invoice']['order_id'];
		}	
		//echo '<pre>';print_r($this->data);exit;
		$order_type_id = $this->data['Invoice']['order_type_id'];
		$order_status_id = $this->data['Invoice']['order_status_id'];
		$has_booking = $this->data['Invoice']['has_booking'];
		$delete_this = $this->data['delete_this'];
		$saved_booking = $this->data['saved_booking'];
		$customer_deposit = $this->data['Order']['deposit'];
		$invoiceImages	=	isset($_FILES['uploadInvoice1']) ? $_FILES['uploadInvoice1']:null;
		
		$newEmail 	= isset($_POST['newEmail']) && !empty($_POST['newEmail']) ? filter_var($_POST['newEmail'], FILTER_SANITIZE_EMAIL) : null;		

		$cemail 	= $newEmail ? $newEmail : $this->getCustomerEmailAdd($this->data['Invoice']['customer_id']);

		$current_customer_id = $this->data['Invoice']['customer_id'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$isValid = $this->validateEmailAddress($cemail);
		if($isValid==1){			
			$invoice_mail_status = 1;
		}else{
			$invoice_mail_status = 0;
		}

		if(!empty($cemail)){
			$query = "UPDATE ace_rp_customers SET email = '".$cemail."'	WHERE id = ".$current_customer_id."";
			$result = $db->_execute($query);			
		}

		if(!empty($order_id)){
			$query = "UPDATE ace_rp_orders SET customer_deposit = $customer_deposit	WHERE id = $order_id";
			$result = $db->_execute($query);
			
		
			$query = "
				DELETE FROM ace_rp_order_items
				WHERE order_id = $order_id
				AND class = 1
			";
			$result = $db->_execute($query);
		}
		$supplier_index = 0;
		$supplier_item = array();
		foreach($this->data['BookingItem'] as $item_index => $item) {
			$class = 1;
			$item_id = $item['item_id'];
			$name = $item['name'];
			$price = $item['price'];
			$quantity = $item['quantity'];
			$item_category_id = $item['item_category_id'];
			$price_purchase = $item['price_purchase'];
			$discount = $item['discount'];
			$addition = $item['addition'];
			$installed = 1;
			$fileName = isset($item['invoice_image']) ? $item['invoice_image'] : null;
			if(!empty($invoiceImages['name'][$item_index])) 
			{
				$fileName = time()."_".$invoiceImages['name'][$item_index];
				$fileTmpName = $invoiceImages['tmp_name'][$item_index];

				if($invoiceImages['error'][$item_index] == 0)
				{
					$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/purchase-invoice-images/".$fileName);
				}
			}
			//echo "<div>$order_id, $item_id, $class, $name, $price, $quantity, $item_category_id, $price_purchase, $discount, $addition, $installed</div>";

			if($item_id == 1218) {
				$supplier_item[$supplier_index]['name'] = $item['name'];
				$supplier_item[$supplier_index]['quantity'] = $item['quantity'];
				$supplier_item[$supplier_index]['part_model'] = $item['part_model'];
				$supplier_item[$supplier_index]['part_brand'] = $item['part_brand'];
				$supplier_item[$supplier_index]['part_supplier'] = $item['part_supplier'];
				$supplier_index++;
			}

			$query = "
				INSERT INTO ace_rp_order_items (order_id, item_id, class, name, price, quantity, item_category_id, price_purchase, discount, addition, installed, print_it, model_number, brand, supplier, invoice_image)
				VALUES ($order_id, '$item_id', $class, '$name', $price, $quantity, $item_category_id, $price_purchase, $discount, $addition, $installed, 'on', '".$item['part_model']."', '".$item['part_brand']."', '".$item['part_supplier']."', '".$fileName."')
			";

			$result = $db->_execute($query);
		}

		if($supplier_index > 0) { //create a new unconfirmed booking
			$customer_id = $this->data['Invoice']['customer_id'];
			$customer_phone = $this->data['Invoice']['phone'];

			$query = "
				SELECT MAX(order_number) + 1 max_number
				FROM ace_rp_orders
			";

			$result = $db->_execute($query);

			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$order_number = $row['max_number'];
			}

			$booking_source_id = $this->Common->getLoggedUserID();
			$order_status_id = 1;
			$job_technician1_id = $this->Common->getLoggedUserID();;

			$query = "
				INSERT INTO ace_rp_orders (customer_id, booking_source_id, order_status_id, order_number, created_by, created_date, customer_phone, job_technician1_id, order_substatus_id)
				VALUES($customer_id, $job_technician1_id, $order_status_id, $order_number, $job_technician1_id, NOW(), $customer_phone, $job_technician1_id, 5);
			";
			//echo $query;
			$result = $db->_execute($query);

			$query = "
				SELECT LAST_INSERT_ID() order_id
			";

			$result = $db->_execute($query);

			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$new_order_id = $row['order_id'];
			}

			foreach($supplier_item as $item) {
				$query = "
					INSERT INTO ace_rp_order_items (order_id, item_id, class, name, price, quantity, item_category_id, invoice, dealer, price_purchase, discount, price_purchase_real, tech, addition, tech_minus, installed, part_number, print_it, model_number, brand, supplier)
					VALUES ($new_order_id, '1218', 0, '".$item['name']."', 0, ".$item['quantity'].", 1, '', '', 0.00, 0.00, NULL, 0.00, 0.00, 0.00, 2, NULL, 'on', '".$item['part_model']."', '".$item['part_brand']."', '".$item['part_supplier']."');";

				$result = $db->_execute($query);
				//echo $query;
			}

			//echo "</pre>";
		}

		//echo "<div>inserted items</div>";

		$query = "
			SELECT COUNT(*) sales_count
			FROM ace_rp_order_items oi
			LEFT JOIN ace_rp_orders o
			ON oi.order_id = o.id
			WHERE o.id = $order_id
			AND o.needs_approval = 1
			AND oi.class = 1
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{
			$sales_count = $row['sales_count'];
		}

		//$needs_approval = $this->_needsApproval($order_id);

		//exit;

		if($sales_count > 0) {
			$query = "
				INSERT INTO ace_rp_notes(message, note_type_id, order_id, user_id, urgency_id, note_date)
				SELECT CONCAT(name, ' for CAD ', (price*quantity)-discount),
					3, $order_id, ".$this->Common->getLoggedUserID().", 1, NOW()
				FROM ace_rp_order_items
				WHERE order_id = $order_id
			";
			//echo $query;
			$db->_execute($query);
		}
		// #LOKI- Check payment is null or not for send Invoice
		if($fromTech)
		{
			$query = "select rp.idorder, rp.payment_method, o.payment_image from ace_rp_payments rp  INNER JOIN ace_rp_orders o ON rp.idorder = o.id where rp.idorder='".$order_id."'";
			$result = $db->_execute($query);
        	$row = mysql_fetch_array($result, MYSQL_ASSOC);
        	if(!empty($row))
        	{
	        	$paymentMethod = $row['payment_method'];
	        	$paymentImage = $row['payment_image'];
	        	$paymentMethodArray = array(2,3,4,5);
	        	if(in_array($paymentMethod, $paymentMethodArray))
	        	{
	        		if(empty($paymentImage) || $paymentImage == '')
	        		{
	  	    			 $this->redirect('orders/invoiceTabletPayment?order_id='.$order_id.'&payment_image_error=Please add payment image!');
		    			 exit;
	        		}
	        	}
        		
        	} else {
        		$this->redirect('orders/invoiceTabletPayment?order_id='.$order_id.'&payment_image_error=Please add payment!');
		    			 exit;
        	}
		} 
		if(isset($_REQUEST['review'])){
			//echo 'OID='.$order_id.' == ='.$cemail;exit;
			$return = $this->emailInvoiceReviewLinks($order_id,$cemail);
                        if($this->Common->getLoggedUserRoleID() == 6){
                             //$this->redirect("orders/invoiceTabletPrint?order_id=$order_id&type=$type");exit;
                             $this->redirect(BASE_PATH."pages/main");exit;
                        }
			$this->redirect("orders/invoiceTablet?order_id=$order_id");exit;
		}
		
		if(isset($_POST['newEmail'])){
			if(isset($_REQUEST['invoice'])){
				//die('M IN Invoice');
				$return = $this->invoiceTabletEprint($order_id,$cemail);	
				$this->redirect("orders/invoiceTablet?order_id=$order_id");exit;
			}
		}
		//echo 'DATA<pre>';prnt_r($_REQUEST);exit;
		if($this->_needsApproval($order_id)) {//die('IMINN');
			$query = "INSERT INTO ace_rp_notes(message, note_type_id, order_id, user_id, urgency_id, note_date)
				VALUES('The job questions have been answered and the items have been listed', 1, $order_id, 1, 2, NOW())
			";
			//echo $query;
			$db->_execute($query);

			$this->redirect("orders/invoiceTabletStandby?order_id=$order_id");

		} else {
			//echo $order_status_id;die('IIMM');
			if($order_status_id == 8) {
				$this->redirect("orders/invoiceTabletNewDate?order_id=$order_id");
			} else {
				$this->redirect("orders/invoiceTabletFeedback?order_id=$order_id");
			}
		}
	}

	function invoiceTabletFeedback() {
		$this->layout = "blank";
		$order_id = $_GET['order_id'];

		if($this->_needsApproval($order_id))
			$this->redirect("orders/invoiceTabletStandby?order_id=$order_id");

		$order = $this->Order->findById($order_id);
		$this->set('this_job', $this->Order->findById($order_id));

		if(isset($order_id)) {
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$query = "SELECT photo_1, photo_2 FROM ace_rp_orders WHERE id = ".$order_id;
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result)) {
					$order['Order']['photo_1'] = $this->getPhotoPath($row['photo_1']);
					$order['Order']['photo_2'] = $this->getPhotoPath($row['photo_2']);
			}
		}
		$this->set('order', $order);
		$this->set('last_order', $this->Order->findByJobEstimateId($order_id));
		$this->set('invoice', $this->Invoice->findByOrderId($order_id));

		$this->set('jobs', $this->Order->findAll(array(
			"Order.job_date" => date("Y-m-d"),  "Order.tech_visible" => 1,
			"OR" => array("Order.booking_source_id" => $this->Common->getLoggedUserID(),
				"Order.booking_source2_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician1_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician2_id" => $this->Common->getLoggedUserID()
			))
		, null, "Order.job_time_beg ASC"));

		$this->set('order_id', $order_id);
	}

	 function saveInvoiceTabletFeedback() {
		$order_id = $this->data['Invoice']['order_id'];
		$order_type_id = $this->data['Invoice']['order_type_id'];
		$email = $this->data['Invoice']['email'];
		$fname = $this->data['Invoice']['fname'];
		$office_rating = $this->data['Feedback']['office_rating'];
		$tech_rating = $this->data['Feedback']['tech_rating'];
		$customer_initial = $this->data['Feedback']['customer_initial'];
		$job_notes_tech = $this->data['Feedback']['job_notes_tech'];
		$follow_up = $this->data['Feedback']['follow_up'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			UPDATE ace_rp_orders
			SET office_rating = $office_rating,
			tech_rating = $tech_rating,
			customer_initial = '$customer_initial',
			job_notes_technician = '$job_notes_tech'
			WHERE id = $order_id
		";
		$result = $db->_execute($query);

		$user_id = $this->Common->getLoggedUserID();
		$query = "
			INSERT INTO ace_rp_notes(message, note_type_id, order_id, user_id, urgency_id, note_date)
			VALUES ('$job_notes_tech', 3, $order_id, $user_id, 1, NOW())
		";
		$result = $db->_execute($query);

		//Save Notes
		if(isset($follow_up) && $follow_up == 1) {

				$message = "Please call this customer back after the job is done";
				$user_id = $this->Common->getLoggedUserID();
				$urgency_id = 2;
				if($this->Common->getLoggedUserRoleID() == 1 || $this->Common->getLoggedUserRoleID() == 2 || $this->Common->getLoggedUserRoleID() == 12) {
					$note_type_id = 3;
				} else if($this->Common->getLoggedUserRoleID() == 6 || $this->Common->getLoggedUserRoleID() == 4) {
					$note_type_id = 2;
				}	else if($this->Common->getLoggedUserRoleID() == 3) {
					$note_type_id = 4;
				}

				$query = "
					INSERT INTO ace_rp_notes(message, note_type_id, order_id, user_id, urgency_id, note_date)
					VALUES ('$message', $note_type_id, $order_id, $user_id, $urgency_id, NOW())
				";

				$db->_execute($query);
		}
		//END Save Notes

		set_time_limit(300);
		$subject = 'Ace Services Ltd';

		//$msg = "Hi $fname,<br><br>Thanks for using our company. The invoice is attached. ";
		//$msg.= "Can you please write a review about your experience with our company.<br>";
		//$msg.= "Choose one of following links to give us your review on our service.<br>";


		$msg = "<div style='padding-top:25px;'><a href='http://acecare.ca/acesys/index.php/calls/invoiceprint?order_id=".$order_id."' target='_blank' style='color:black;text-decoration:underline;font-size:20px;'>Please open attachment to see your invoice</a></div>";
 
                $msg .= "<div style='padding-top:25px;'><p><b>We would a take moment to thank you for choosing our company.</b></p></div>";
                $msg .= "<div style='padding-top:25px;'><p><b>Can you please write a review about your experience with our company.</b></p></div>";
                $msg .= "<div style='padding-top:25px;'><br><p><b>Choose one of following links to give us your review on our service.</b></p></div>";
		//http://aceno1.ca/acetest/index.php/calls/invoiceprint?order_id=118281

		$msg.= "<div style='padding-top:25px;'>1.<a href='http://acecare.ca/pro-ace-google-plus-city-pages/' style='background:green;padding:10px;margin:20px;color:white;text-decoration:none;font-size:20px;'>Write a review on Google</a></div>";
		$msg.= "<div style='padding-top:25px;'>2.<a href='http://homestars.com/companies/2805505-pro-ace-heating-and-plumbing' style='background:blue;padding:10px;margin:20px;color:white;text-decoration:none;font-size:20px;'>Write a review on Homestars</a></div>";
		$msg.= "<div style='padding-top:25px;'>3.<a href='http://www.yelp.ca/biz/pro-ace-heating-burnaby' style='background:red;padding:10px;margin:20px;color:white;text-decoration:none;font-size:20px;'>Write a review on Yelp</a></div>";
		$msg.= "<div style='padding-top:25px;'>4.<a href='http://acecare.ca/write-a-review/' style='background:purple;padding:10px;margin:20px;color:white;text-decoration:none;font-size:20px;'>Write a review on Ace Pro</a></div>";

		$msg.= "<br><br>Thanks for your business!<br><br>Sincerely,<br><br>";
		$msg.= "ACE Clients<br>Pro Ace Heating & Air Conditioning Ltd<br>";
		$msg.= "Tel: 604-293-3770<br>www.acecare.ca";

		$invoice = file_get_contents("http://hvacproz.ca/acesys/index.php/orders/invoiceTabletPrint?order_id=$order_id&type=office");

		$boundary = md5(time());
		$header = "From: info@acecare.ca \r\n";
		$header .= "MIME-Version: 1.0\r\n";
		$header .= "Content-Type: multipart/mixed;boundary=\"" . $boundary . "\"\r\n";

		$output = "--".$boundary."\r\n";
		$output .= "Content-Type: text/csv; name=\"invoice.html\";\r\n";
		$output .= "Content-Disposition: attachment;\r\n\r\n";
		$output .= $invoice."\r\n\r\n";
		$output .= "--".$boundary."\r\n";
		$output .= "Content-type: text/html; charset=\"utf-8\"\r\n";
		$output .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
		$output .= $msg."\r\n\r\n";
		$output .= "--".$boundary."--\r\n\r\n";

		// $res = mail($email, $subject, $output, $header);

		/*$this->redirect("http://acesys.ace1.ca/digitalsign?order_id=$order_id&email=$email");
		*/
		$this->redirect("orders/invoiceTabletPrint?order_id=$order_id");
	}

	function invoiceTabletSignature() {
		$this->layout = "blank";
	}

	function invoiceTabletPrint() {

		$this->layout = "blank";

		$order_id = $_GET['order_id'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		if(isset($_GET['type']) && $_GET['type'] == 'office') {
			//do nothing
		} else {
			if($this->_needsApproval($order_id))
				$this->redirect("orders/invoiceTabletStandby?order_id=$order_id");

			$result = $db->_execute("
				UPDATE ace_rp_orders
				SET order_status_id = 5
				WHERE id = $order_id
				AND order_status_id != 8
			");

			$result = $db->_execute("
				UPDATE ace_rp_orders
				SET order_status_id = 1
				WHERE id = $order_id
				AND order_status_id = 8
				AND job_date IS NOT NULL
			");

			$this->_saveQuestionsAsFinal($order_id);
		}

		$query = "
			SELECT *
			FROM ace_rp_settings
			WHERE id IN(21)
		";

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result)) {
			$use_template_questions = $row['valuetxt'];
		}

		if(isset($use_template_questions) && $use_template_questions == 0) {
			if($this->Common->getLoggedUserRoleID() != 1)
			$this->redirect("orders/invoiceTabletPrintOld?order_id=$order_id&type=office");
			else
			$this->redirect("orders/invoiceTabletPrintOld?order_id=$order_id");
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
			WHERE 
			qw.order_id = $order_id
			ORDER BY q.rank
		";
		//q.for_print = 1 AND 

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

	function invoiceTabletPrintOld() {
		$this->layout = "blank";

		$order_id = $_GET['order_id'];

		$conditions = array();

		$conditions += array('`Order`.`id`' => $order_id);

		$allQuestions = array();
		$db =& ConnectionManager::getDataSource('default');
		$result = $db->_execute("select * from ace_rp_order_types_questions where for_print = 1");
		while($row = mysql_fetch_array($result))
		{
			$allQuestions[$row['order_type_id']][$row['question_number']]['question_number'] = $row['question_number'];
			$allQuestions[$row['order_type_id']][$row['question_number']]['question'] = $row['question'];
			$allQuestions[$row['order_type_id']][$row['question_number']]['question'] = $row['suggestions'];
			$allQuestions[$row['order_type_id']][$row['question_number']]['question'] = $row['response'];
			$allQuestions[$row['order_type_id']][$row['question_number']]['local_answer'] = $row['local_answer'];
		}

		$allStatuses = $this->Lists->ListTable('ace_rp_order_statuses');
		$allJobTypes = $this->Lists->ListTable('ace_rp_order_types');

		// UNCOMMENT ON LIVE
		$conditions += array('order_status_id' => array(1, 5));

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

			$order_type_id = 2;

			//questions
			$query = "
				SELECT *, (SELECT COUNT(*) FROM ace_rp_answers a WHERE a.question_id = q.id) answer_count
				FROM ace_rp_questions q
				WHERE q.type = 2
				ORDER BY q.rank
			";

			$result = $db->_execute($query);

			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$questions[$row['id']]['question']= $row['question'];
				$questions[$row['id']]['answer_count']= $row['answer_count'];
			}

			$query = "
				SELECT *
				FROM ace_rp_answers
			";

			$result = $db->_execute($query);

			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$answers[$row['question_id']][$row['id']]['answer'] = $row['answer'];
			}

			$query = "
				SELECT *
				FROM ace_rp_answers_orders
				WHERE order_id = $order_id
			";

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

		$db =& ConnectionManager::getDataSource('default');
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$history_keys[$row['id']]= $row['hk'];
    	}

				$query = "
			SELECT *, (SELECT COUNT(*) FROM ace_rp_answers a WHERE a.question_id = q.id) answer_count
			FROM ace_rp_questions q
			WHERE q.type = $order_type_id
			ORDER BY q.rank
		";

		$result = $db->_execute($query);

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$questions[$row['id']]['question']= $row['question'];
			$questions[$row['id']]['answer_count']= $row['answer_count'];
		}

		$query = "
			SELECT *
			FROM ace_rp_answers
		";

		$result = $db->_execute($query);

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$answers[$row['question_id']][$row['id']]['answer'] = $row['answer'];
		}

		$query = "
			SELECT *
			FROM ace_rp_answers_orders
			WHERE order_id = $order_id

		";

		$result = $db->_execute($query);

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$values[$row['question_id']]['answer_id'] = $row['answer_id'];
			$values[$row['question_id']]['answer_text'] = $row['answer_text'];
		}


		$result = $db->_execute("
			UPDATE ace_rp_orders
			SET order_status_id = 5
			WHERE id = $order_id
		");

		$this->set('order_id', $order_id);
		//$this->set('questions', $questions);
		//$this->set('answers', $answers);
		$this->set('values', $values);



		$this->set('notes', $notes);
		$this->set('history_keys',$history_keys);
		$this->set('job_truck', $this->params['url']['job_truck']);
		//$this->set('inventoryLocations',$inventoryLocations);
		$this->set('orders', $orders);
		$this->set('allSources', $this->Lists->BookingSources());
		$this->set('suppliers', $this->Lists->ListTable('ace_rp_suppliers','',array('name','address')));
		$this->set('job_trucks', $this->HtmlAssist->table2array($this->InventoryLocation->findAll(array('type' => '2'), null, null, null, 1, 0), 'id', 'name'));
		$this->set('techs', $this->Lists->Technicians());
		$this->set('payment_methods', $this->HtmlAssist->table2array($this->PaymentMethod->findAll(array(), null, null, null, 1, 0), 'id', 'name'));
		$this->set('allQuestions', $allQuestions);
	}

	function invoiceTabletPayment() {
		$this->layout = "blank";

		$order_id = $_GET['order_id'];

		if($this->_needsApproval($order_id))
			$this->redirect("orders/invoiceTabletStandby?order_id=$order_id");

		$delete_attached = $_GET['q'];

		if($delete_attached == 1)  {
			$this->Order->delete($order_id);
		}

		$order = $this->Order->findById($order_id);
		$city = $order['Customer']['city'];
		$this->set('order', $order);
		$this->set('last_order', $this->Order->findByJobEstimateId($order_id));
		$this->set('invoice', $this->Invoice->findByOrderId($order_id));

		/** Fetch order payment image using order id*/
		$db =& ConnectionManager::getDataSource('default');
		$queryPayment 	= "select payment_image from ace_rp_orders where id='".$order_id."'";
		$resultPayment 	= $db->_execute($queryPayment);
		$rowPayment 	= mysql_fetch_array($resultPayment, MYSQL_ASSOC);
		if($rowPayment){
			$this->set('invoice_payment_photo', $rowPayment['payment_image']);
		}
		/* closed */
		
		$this->set('jobs', $this->Order->findAll(array(
			"Order.job_date" => date("Y-m-d"),  "Order.tech_visible" => 1,
			"OR" => array("Order.booking_source_id" => $this->Common->getLoggedUserID(),
				"Order.booking_source2_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician1_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician2_id" => $this->Common->getLoggedUserID()
			))
		, null, "Order.job_time_beg ASC"));

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "
			DELETE
			FROM ace_rp_orders
			WHERE order_status_id = 0
		";
		$result = $db->_execute($query);

		$query = "
			SELECT internal_id
			FROM ace_rp_cities
			WHERE id = '$city'
		";
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{
			$city_id = $row['internal_id'];
		}
		$methods = array();
		$query = " SELECT * from ace_rp_payment_methods";
		$result1 = $db->_execute($query);
		while($row = mysql_fetch_array($result1))
		{
			$methods[$row['id']] = $row;
		}
		$this->set('city_id', $city_id);
		$this->set('order_id', $order_id);
		$this->set('job_types',$this->Lists->OrderTypes());
		// $this->set('payment_method', $this->HtmlAssist->table2array($this->Order->PaymentMethod->findAll(), 'id', 'name', 'show_picture'));
		$this->set('payment_methods', $methods);
	}

	function saveInvoiceTabletPayment() {
		$order_id = $this->data['Invoice']['order_id'];
		$order_type_id = $this->data['Invoice']['order_type_id'];
		$has_booking = $this->data['Invoice']['has_booking'];
		$delete_this = $this->data['delete_this'];
		$saved_booking = $this->data['saved_booking'];
		$customer_deposit = $this->data['Order']['deposit'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			UPDATE ace_rp_orders
			SET customer_deposit = $customer_deposit
			WHERE id = $order_id
		";
		$result = $db->_execute($query);

		$query = "
			DELETE FROM ace_rp_order_items
			WHERE order_id = $order_id
			AND class = 1
		";
		$result = $db->_execute($query);

		$this->redirect("orders/invoiceTabletFeedback?order_id=$order_id");
	}

	function invoiceTabletStandby() {
		$this->layout = "blank";
		$order_id = $_GET['order_id'];

		if(!($this->_needsApproval($order_id))) {
			$this->redirect("orders/invoiceTabletFeedback?order_id=$order_id");
		}

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			SELECT n.id, n.message, nt.name note_type, u2.first_name name
			FROM ace_rp_notes n
			LEFT JOIN ace_rp_note_types nt
			ON n.note_type_id = nt.id
			LEFT JOIN ace_rp_urgencies u
			ON n.urgency_id = u.id
			LEFT JOIN ace_rp_users u2
			ON n.user_id = u2.id
			WHERE n.order_id = $order_id
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			foreach ($row as $k => $v)
			  $notes[$row['id']][$k] = $v;
		}

		$this->set('notes', $notes);
		$this->set('order_id', $order_id);
	}

	function invoiceTabletNotes() {
		$this->layout = "blank";

		$order_id = $_POST['order_id'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			SELECT n.id, n.message, nt.name note_type, u2.first_name name
			FROM ace_rp_notes n
			LEFT JOIN ace_rp_note_types nt
			ON n.note_type_id = nt.id
			LEFT JOIN ace_rp_urgencies u
			ON n.urgency_id = u.id
			LEFT JOIN ace_rp_users u2
			ON n.user_id = u2.id
			WHERE n.order_id = $order_id
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			foreach ($row as $k => $v)
			  $notes[$row['id']][$k] = $v;
		}

		$query = "
			SELECT *
			FROM ace_rp_orders
			WHERE id = $order_id
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$needs_approval = $row['needs_approval'];
			$sale_approval = $row['sale_approval'];
		}

		$query = "
			SELECT COUNT(*) sales_count
			FROM ace_rp_order_items oi
			WHERE oi.order_id = $order_id
			AND oi.class = 1
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$sales_count = $row['sales_count'];
		}

		$need1 = $this->_needsApproval($order_id);
		$need2 = $sale_approval && $sales_count;

		$this->set('notes', $notes);
		$this->set('needs_approval',$need1);
		$this->set('needs_job_approval',$need2);

	}

	function addInvoiceTabletNotes() {
		$message = $_POST['message'];
		$note_type_id = $_POST['note_type_id'];
		$order_id = $_POST['order_id'];
		$user_id = $this->Common->getLoggedUserID();
		$urgency_id = $_POST['urgency_id'];

		$db =& ConnectionManager::getDataSource('default');


		$query = "
			INSERT INTO ace_rp_notes(message, note_type_id, order_id, user_id, urgency_id, note_date)
			VALUES ('$message', $note_type_id, $order_id, $user_id, $urgency_id, NOW())
		";

		$db->_execute($query);

		if($urgency_id == 4) {
			$query = "
				UPDATE ace_rp_orders
				SET needs_approval = 0
				WHERE id = $order_id
			";

			$db->_execute($query);
		}

		if($urgency_id == 5) {
			$query = "
				UPDATE ace_rp_orders
				SET sale_approval = 0
				WHERE id = $order_id
			";

			$db->_execute($query);
		}

		echo "OK";
		exit;
	}

	function _needsApproval($order_id) {
		$db =& ConnectionManager::getDataSource('default');

		$query = "
			SELECT *
			FROM ace_rp_orders
			WHERE id = $order_id
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$needs_approval = $row['needs_approval'];
		}

		$query = "
			SELECT COUNT(*) sales_count
			FROM ace_rp_order_items oi
			LEFT JOIN ace_rp_orders o
			ON oi.order_id = o.id
			WHERE o.id = $order_id
			AND oi.class = 1
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$sales_count = $row['sales_count'];
		}

		$query = "
			SELECT COUNT(*) for_approval
			FROM ace_rp_orders_questions_working qw
			LEFT JOIN ace_rp_decisions d
			ON d.id = qw.decision_id
			WHERE qw.order_id = $order_id
			AND d.notify = 1
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$for_approval = $row['for_approval'];
		}

		if($needs_approval == 1) {
			if($for_approval > 0 || $sales_count > 0)
				return true;
			else return false;
		} else return false;

		//return $needs_approval.$for_approval.$sales_count;
	}

	function invoiceTabletEprint__old() {
		$this->layout = "blank";
		$order_id = $_GET['order_id'];
		$email = $_GET['email'];
		$type = $_GET['type'];

		$this->set('order_id', $order_id);

		$user_id = $this->Common->getLoggedUserID();

		$db =& ConnectionManager::getDataSource('default');

		$eprint_id = $_SESSION['user']['eprint_id'];

		$subject = 'Ace Services Ltd';
		$headers = "From: info@acecare.ca\n";
		$headers .= "Content-Type: text/html; charset=iso-8859-1\n";

		//$msg = file_get_contents("http://acesys.ace1.ca/index.php/orders/invoiceTabletPrint?order_id=$order_id&type=$type");
		$msg = file_get_contents("http://acecare.ca/acesys/index.php/calls/invoiceprint?order_id=$order_id");

		$res = mail($email, $subject, $msg, $headers);
		//$res = mail('proaceprinter01@hpeprint.com', $subject, $msg, $headers);
		//$res = mail('hsestacio13@gmail.com', $subject, $msg, $headers);

		$this->set('printerNumber', $printerNumber);

		$this->redirect("calls/invoiceprint?order_id=$order_id");
	}

	function invoiceTabletTimeSlots() {
		$this->layout = "blank";

		$days_ahead = 16;

		$job_type_id = $_POST['job_type_id'];
		$city_id = $_POST['city_id'];
		$user_id = $this->Common->getLoggedUserID();
		if(!isset($city_id)) $city_id = 0;

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			SELECT *
			FROM ace_rp_orders_schedule_display s
			WHERE job_type_id = $job_type_id
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$schedules[$row['schedule_id']] = "1";
		}

		if(isset($schedules)) {
			$this->set('furnace_on', isset($schedules[1])?1:0);
			$this->set('airducts_on', isset($schedules[2])?1:0);
			$this->set('installation_on', isset($schedules[3])?1:0);
		} else {
			$this->set('furnace_on', 1);
			$this->set('airducts_on', 1);
			$this->set('installation_on', 1);
		}

		$this->set('schedules', $schedules);

		$query = "
			SELECT *
			FROM ace_rp_cities
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$cities[$row['internal_id']]['name']= $row['name'];
		}


		$query = "
			SELECT DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 0 DAY), '%d') 'dates',
				SUBSTR(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 0 DAY), '%W'), 1, 2) 'weeks',
				SUBSTR(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 0 DAY), '%w'), 1, 2) 'week_number',
				SUBSTR(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 0 DAY), '%b'), 1, 2) 'months',
				DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 0 DAY), '%Y-%m-%d') full_date
		";

		for($i = 1; $i < $days_ahead; $i++) {
			$query .= "
				UNION
				SELECT DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL $i DAY), '%d'),
					SUBSTR(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL $i DAY), '%W'), 1, 2),
					SUBSTR(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL $i DAY), '%w'), 1, 2),
					SUBSTR(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL $i DAY), '%b'), 1, 2),
					DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL $i DAY), '%Y-%m-%d')
			";
		}

		$result = $db->_execute($query);
		$i = 0;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$dates[$i]['dates']= $row['dates'];
			$dates[$i]['weeks']= $row['weeks'];
			$dates[$i]['week_number']= $row['week_number'];
			$dates[$i]['months']= $row['months'];
			$dates[$i++]['full_date']= $row['full_date'];
		}

		$query = "
			SELECT ts.id, ts.name, ts.from, ts.to,
		";

		for($i = 0; $i < $days_ahead; $i++) {
			$query .= "
				(SELECT
					SUM(
						IF(
							(SELECT IF(COUNT(*) = 0,1,0)
							FROM ace_rp_orders
							WHERE job_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND order_status_id NOT IN(3,2)
							AND (
								(HOUR(ts.from) >= HOUR(job_time_beg) AND HOUR(ts.from) < HOUR(job_time_end))
								OR
								(HOUR(ts.to) > HOUR(job_time_beg) AND HOUR(ts.to) <= HOUR(job_time_end))
							)
							AND job_truck = il.id)
						AND
							(SELECT COUNT(route_id)
							FROM ace_rp_route_cities
							WHERE route_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND city_id = $city_id
							AND route_id = il.id)
							+
							(SELECT IF(COUNT(route_id) = 0, 1, 0)
							FROM ace_rp_route_cities
							WHERE route_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND route_id = il.id)
						AND
							(SELECT IF(COUNT(*) = 0,1,0)
							FROM ace_rp_pending_timeslots
							WHERE route_id = il.id
							AND job_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND ((HOUR(ts.from) >= HOUR(job_time_beg) AND HOUR(ts.from) < HOUR(job_time_end))
								OR
								(HOUR(ts.to) > HOUR(job_time_beg) AND HOUR(ts.to) <= HOUR(job_time_end)))
							AND user_id != $user_id)
							,
						1,0)
					) 'slots'
				FROM ace_rp_inventory_locations il
				WHERE il.route_type = 1) '$i',";
		}

		$query = substr($query, 0, -1);

		$query .= "
			FROM ace_rp_timeslots ts
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$furnaceslots[$row['id']]['name']= $row['name'];
			$furnaceslots[$row['id']]['from']= $row['from'];
			$furnaceslots[$row['id']]['to']= $row['to'];
			for($i=0;$i < $days_ahead; $i++) $furnaceslots[$row['id']][$i]= $row[$i];
		}

		$query = "
			SELECT ts.id, ts.name, ts.from, ts.to,
		";

		for($i = 0; $i < $days_ahead; $i++) {
			$query .= "
				(SELECT
					SUM(
						IF(
							(SELECT IF(COUNT(*) = 0,1,0)
							FROM ace_rp_orders
							WHERE job_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND order_status_id NOT IN(3,2)
							AND (
								(HOUR(ts.from) >= HOUR(job_time_beg) AND HOUR(ts.from) < HOUR(job_time_end))
								OR
								(HOUR(ts.to) > HOUR(job_time_beg) AND HOUR(ts.to) <= HOUR(job_time_end))
							)
							AND job_truck = il.id)
						AND
							(SELECT COUNT(route_id)
							FROM ace_rp_route_cities
							WHERE route_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND city_id = $city_id
							AND route_id = il.id)
							+
							(SELECT IF(COUNT(route_id) = 0, 1, 0)
							FROM ace_rp_route_cities
							WHERE route_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND route_id = il.id)
						AND
							(SELECT IF(COUNT(*) = 0,1,0)
							FROM ace_rp_pending_timeslots
							WHERE route_id = il.id
							AND job_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND ((HOUR(ts.from) >= HOUR(job_time_beg) AND HOUR(ts.from) < HOUR(job_time_end))
								OR
								(HOUR(ts.to) > HOUR(job_time_beg) AND HOUR(ts.to) <= HOUR(job_time_end)))
							AND user_id != $user_id)
							,
						1,0)
					) 'slots'
				FROM ace_rp_inventory_locations il
				WHERE il.route_type = 2) '$i',";
		}

		$query = substr($query, 0, -1);

		$query .= "
			FROM ace_rp_timeslots ts
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$airductslots[$row['id']]['name']= $row['name'];
			$airductslots[$row['id']]['from']= $row['from'];
			$airductslots[$row['id']]['to']= $row['to'];
			for($i=0;$i < $days_ahead; $i++) $airductslots[$row['id']][$i]= $row[$i];
		}

		$query = "
			SELECT 0 'id', '8am - 6pm' 'name', '08:00:00' 'from', '18:00:00' 'to',
		";

		for($i = 0; $i < $days_ahead; $i++) {
			$query .= "
				(SELECT
					SUM(
						IF(
							(SELECT IF(COUNT(*) = 0,1,0)
							FROM ace_rp_orders
							WHERE job_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND order_status_id NOT IN(3,2)
							AND (
								(HOUR('08:00:00') >= HOUR(job_time_beg) AND HOUR('08:00:00') < HOUR(job_time_end))
								OR
								(HOUR('18:00:00') > HOUR(job_time_beg) AND HOUR('18:00:00') <= HOUR(job_time_end))
							)
							AND job_truck = il.id)
						AND
							(SELECT COUNT(route_id)
							FROM ace_rp_route_cities
							WHERE route_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND city_id = $city_id
							AND route_id = il.id)
							+
							(SELECT IF(COUNT(route_id) = 0, 1, 0)
							FROM ace_rp_route_cities
							WHERE route_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND route_id = il.id)
						AND
							(SELECT IF(COUNT(*) = 0,1,0)
							FROM ace_rp_pending_timeslots
							WHERE route_id = il.id
							AND job_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND ((HOUR('08:00:00') >= HOUR(job_time_beg) AND HOUR('08:00:00') < HOUR(job_time_end))
								OR
								(HOUR('18:00:00') > HOUR(job_time_beg) AND HOUR('18:00:00') <= HOUR(job_time_end)))
							AND user_id != $user_id)
							,
						1,0)
					) 'slots'
				FROM ace_rp_inventory_locations il
				WHERE il.route_type = 4) '$i',";
		}

		$query = substr($query, 0, -1);

		$query .= "

		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$installationdayslots[$row['id']]['name']= '8am - 6pm';
			$installationdayslots[$row['id']]['from']= '08:00:00';
			$installationdayslots[$row['id']]['to']= '18:00:00';
			for($i=0;$i < $days_ahead; $i++) $installationdayslots[$row['id']][$i]= $row[$i];
		}


		$this->set('cities', $cities);
		$this->set('city_id', $city_id);
		$this->set('furnaceslots', $furnaceslots);
		$this->set('airductslots', $airductslots);
		$this->set('installationdayslots', $installationdayslots);
		$this->set('dates', $dates);
		$this->set('days_ahead', $days_ahead);
	}

	function invoiceTabletNewQuestions() {
		$this->layout = "blank";


	}

	function saveInvoiceTabletNewQuestions() {

	}

	function invoiceTabletNewBooking() {
		$this->layout = "blank";

		$this->set('jobs', $this->Order->findAll(array(
			"Order.job_date" => date("Y-m-d"),  "Order.tech_visible" => 1,
			"OR" => array("Order.booking_source_id" => $this->Common->getLoggedUserID(),
				"Order.booking_source2_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician1_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician2_id" => $this->Common->getLoggedUserID()
			))
		, null, "Order.job_time_beg ASC"));

		$db =& ConnectionManager::getDataSource('default');

		$query = "
			SELECT REPLACE(name, ' ', '_') name, internal_id
			FROM ace_rp_cities
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$cities_with_id[$row['internal_id']]['name']= $row['name'];
		}

		$this->set('cities_with_id',$cities_with_id);
		//$this->set('allCities',$this->Lists->ListTable('ace_rp_cities'));
		$this->set('allCities',$this->Lists->ActiveCities());
		$this->set('job_types',$this->Lists->OrderTypes());
	}

	function invoiceTabletNewSlot() {
		$this->layout = "blank";
		$order_id = $_GET['order_id'];

		$this->set('jobs', $this->Order->findAll(array(
			"Order.job_date" => date("Y-m-d"), "Order.tech_visible" => 1,
			"OR" => array("Order.booking_source_id" => $this->Common->getLoggedUserID(),
				"Order.booking_source2_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician1_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician2_id" => $this->Common->getLoggedUserID()
			))
		, null, "Order.job_time_beg ASC"));

		$db =& ConnectionManager::getDataSource('default');
		$tech = $this->Common->getLoggedUserID();

		$query = "
			SELECT u.first_name, u.last_name, CONCAT(u.address_unit,', ',u.address_street_number,', ',u.address_street) as address,
				u.city, u.postal_code,
				o.customer_phone, ot.name order_type_name, o.*
			FROM ace_rp_orders o
			LEFT JOIN ace_rp_customers u
			ON o.customer_id = u.id
			LEFT JOIN ace_rp_order_types ot
			ON o.order_type_id = ot.id
			WHERE DATE(o.created_date) = CURDATE()
			AND o.order_status_id = 8
			AND (o.job_technician1_id = $tech OR o.job_technician2_id = $tech)
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			foreach ($row as $k => $v)
			  $estimate_jobs[$row['id']][$k] = $v;
		}
		$this->set('estimate_jobs',$estimate_jobs);

		$query = "
			SELECT REPLACE(name, ' ', '_') name, internal_id
			FROM ace_rp_cities
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$cities_with_id[$row['internal_id']]['name']= $row['name'];
		}

		$this->set('cities_with_id',$cities_with_id);
		$this->set('allCities',$this->Lists->ActiveCities());
		$this->set('job_types',$this->Lists->OrderTypes());
		$this->set('order_id',$order_id);
	}

	function saveInvoiceTabletNewSlot() {
		$db =& ConnectionManager::getDataSource('default');
		$tech = $this->Common->getLoggedUserID();
		$order_id = $_POST['order_id'];
		$order_type_id = $this->data['Booking']['order_type_id'];
		$job_date = $_POST['job_date'];
		$job_truck = $_POST['job_truck'];
		$job_time_beg = $_POST['job_time_beg'];
		$job_time_end = $_POST['job_time_end'];
		$job_tech_1 = $_POST['job_tech_1'];
		$job_tech_2 = isset($_POST['job_tech_2'])&&$_POST['job_tech_2']!=""?"":",job_technician2_id = $job_tech_2";

		$query = "
			UPDATE ace_rp_orders
			SET order_type_id = $order_type_id
			WHERE id = 	$order_id
		";

		$result = $db->_execute($query);

		//$this->redirect("orders/invoiceTabletQuestions?order_id=$order_id&order_type_id=$order_type_id");
		$this->redirect("orders/invoiceTabletItems?order_id=$order_id");
	}

	function invoiceTabletNewDate() {
		$this->layout = "blank";
		$order_id = $_GET['order_id'];

		$this->set('jobs', $this->Order->findAll(array(
			"Order.job_date" => date("Y-m-d"), "Order.tech_visible" => 1,
			"OR" => array("Order.booking_source_id" => $this->Common->getLoggedUserID(),
				"Order.booking_source2_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician1_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician2_id" => $this->Common->getLoggedUserID()
			))
		, null, "Order.job_time_beg ASC"));

		$db =& ConnectionManager::getDataSource('default');
		$tech = $this->Common->getLoggedUserID();

		$query = "
			SELECT u.first_name, u.last_name, CONCAT(u.address_unit,', ',u.address_street_number,', ',u.address_street) as address,
				u.city, u.postal_code,
				o.customer_phone, ot.name order_type_name, ot.id job_type_id, o.*
			FROM ace_rp_orders o
			LEFT JOIN ace_rp_customers u
			ON o.customer_id = u.id
			LEFT JOIN ace_rp_order_types ot
			ON o.order_type_id = ot.id
			WHERE DATE(o.created_date) = CURDATE()
			AND o.order_status_id = 8
			AND o.created_by = $tech
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			foreach ($row as $k => $v)
			  $estimate_jobs[$row['id']][$k] = $v;
		}
		$this->set('estimate_jobs',$estimate_jobs);

		$query = "
			SELECT REPLACE(name, ' ', '_') name, internal_id
			FROM ace_rp_cities
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$cities_with_id[$row['internal_id']]['name']= $row['name'];
		}

		$this->set('cities_with_id',$cities_with_id);
		$this->set('allCities',$this->Lists->ActiveCities());
		$this->set('job_types',$this->Lists->OrderTypes());
		$this->set('order_id',$order_id);
	}

	function saveInvoiceTabletNewDate() {
		$db =& ConnectionManager::getDataSource('default');
		$tech = $this->Common->getLoggedUserID();
		$order_id = $_POST['order_id'];
		$order_type_id = $this->data['Booking']['order_type_id'];
		$job_date = $_POST['job_date'];
		$job_truck = $_POST['job_truck'];
		$job_time_beg = $_POST['job_time_beg'];
		$job_time_end = $_POST['job_time_end'];
		$job_tech_1 = $_POST['job_tech_1'];

		if(isset($job_date) && $job_date != "") {
			$query = "
				UPDATE ace_rp_orders
				SET job_date = '$job_date',
				job_truck = $job_truck,
				job_time_beg = '$job_time_beg:00:00',
				job_time_end = '$job_time_end:00:00',
				job_technician1_id = created_by
				WHERE id = $order_id
			";
			$result = $db->_execute($query);
		}

		$this->redirect("orders/invoiceTabletQuestions?order_id=$order_id");
	}

	function saveInvoiceTabletNewBooking() {
		$db =& ConnectionManager::getDataSource('default');

		$customer_id = $_GET['customer_id'];
		$mode = $_GET['mode'];
		$customer_phone = $_GET['phone'];

		$query = "
			SELECT MAX(order_number) + 1 max_number
			FROM ace_rp_orders
		";

		$result = $db->_execute($query);

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$order_number = $row['max_number'];
		}

		$booking_source_id = $this->Common->getLoggedUserID();
		$order_status_id = $mode==0?8:1;
		$job_technician1_id = $this->Common->getLoggedUserID();;

		$query = "
			INSERT INTO ace_rp_orders (customer_id, booking_source_id, order_status_id, order_number, created_by, created_date, customer_phone, job_technician1_id)
			VALUES($customer_id, $job_technician1_id, $order_status_id, $order_number, $job_technician1_id, NOW(), $customer_phone, $job_technician1_id);
		";

		$result = $db->_execute($query);

		$query = "
			SELECT LAST_INSERT_ID() order_id
		";

		$result = $db->_execute($query);

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$order_id = $row['order_id'];
		}

		$query = "
			INSERT INTO ace_rp_order_items (order_id, item_id, class, name, price, quantity, item_category_id, invoice, dealer, price_purchase, discount, price_purchase_real, tech, addition, tech_minus, installed, part_number, print_it)
			VALUES ($order_id, '1096', 0, 'Estimate', 0, 1, 1, '', '', 0.00, 0.00, NULL, 0.00, 0.00, 0.00, 2, NULL, 'on');";

		$result = $db->_execute($query);

		$this->redirect("orders/invoiceTabletNewSlot?order_id=$order_id");
	}

	function invoiceTabletNewCustomer() {
		$this->layout = "blank";

		$this->set('jobs', $this->Order->findAll(array(
			"Order.job_date" => date("Y-m-d"),  "Order.tech_visible" => 1,
			"OR" => array("Order.booking_source_id" => $this->Common->getLoggedUserID(),
				"Order.booking_source2_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician1_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician2_id" => $this->Common->getLoggedUserID()
			))
		, null, "Order.job_time_beg ASC"));

		$db =& ConnectionManager::getDataSource('default');

		$query = "
			SELECT REPLACE(name, ' ', '_') name, internal_id
			FROM ace_rp_cities
		";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$cities_with_id[$row['internal_id']]['name']= $row['name'];
		}

		$this->set('cities_with_id',$cities_with_id);
		//$this->set('allCities',$this->Lists->ListTable('ace_rp_cities'));
		$this->set('allCities',$this->Lists->ActiveCities());
		$this->set('job_types',$this->Lists->OrderTypes());
	}

	function saveInvoiceTabletNewCustomer() {
		$mode = $_GET['mode'];

		$customer_id = $this->_SaveCustomer();
		$customer_phone = $this->data['Customer']['phone'];
		$order_status_id = $mode==0?8:1;
		$booking_order_type_id = $this->data['Booking']['order_type_id'];

		$this->redirect("orders/saveInvoiceTabletNewBooking?customer_id=$customer_id&phone=$customer_phone&mode=$mode");
	}

	function invoiceTabletQuestions() {
		$this->layout = "blank";

		$order_id = $_GET['order_id'];
		$last_order_id = $_GET['last_order_id'];

		$job = $this->Order->findById($order_id);
		$last_job = $this->Order->findById($last_order_id);

		$this->set('this_job', $this->Order->findById($order_id));
		$this->set('order', $job);
		$this->set('last_order', $last_job);
		$this->set('invoice', $this->Invoice->findByOrderId($order_id));

		$order_type_id = $job['Order']['order_type_id'];
		$customer_id = $job['Order']['customer_id'];

		$jobs = $this->Order->findAll(array(
			"Order.job_date" => date("Y-m-d"),
			"OR" => array("Order.booking_source_id" => $this->Common->getLoggedUserID(),
				"Order.booking_source2_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician1_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician2_id" => $this->Common->getLoggedUserID()
			))
		, null, "Order.job_time_beg ASC");

		$this->set('jobs', $jobs);

		$this->set('order_id', $order_id);

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$item_id = $order_type_id;

		$query = "
			SELECT *
			FROM ace_rp_questions
			WHERE order_type_id = $item_id
			order by rank, value
		";

		$questions = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $questions[$row['id']][$k] = $v;
		}

		$query = "
			SELECT r.*
			FROM ace_rp_questions q
			LEFT JOIN ace_rp_responses r
			ON q.id = r.question_id
			WHERE q.order_type_id = $item_id
		";

		$responses = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $responses[$row['question_id']][$row['id']][$k] = $v;
		}

		$query = "
			SELECT s.*
			FROM ace_rp_questions q
			LEFT JOIN ace_rp_responses r
			ON q.id = r.question_id
			LEFT JOIN ace_rp_suggestions s
			ON r.id = s.response_id
			WHERE q.order_type_id = $item_id
		";
		$suggestions = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $suggestions[$row['response_id']][$row['id']][$k] = $v;
		}

		$query = "
			SELECT d.*
			FROM ace_rp_questions q
			LEFT JOIN ace_rp_responses r
			ON q.id = r.question_id
			LEFT JOIN ace_rp_suggestions s
			ON r.id = s.response_id
			LEFT JOIN ace_rp_decisions d
			ON s.id = d.suggestion_id
			WHERE q.order_type_id = $item_id
		";

		$decisions = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $decisions[$row['suggestion_id']][$row['id']][$k] = $v;
		}

		$query = "
			SELECT *
			FROM ace_rp_suggestion_operations
		";

		$operations = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $operations[$row['id']][$k] = $v;
		}

		$query = "
			SELECT *
			FROM ace_rp_suggestion_which
		";

		$which = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $which[$row['id']][$k] = $v;
		}


		$query = "
			SELECT *
			FROM ace_rp_orders_questions_working
			WHERE order_id = $order_id
		";

		$working_answers = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $working_answers[$row['question_id']][$k] = $v;
		}

		//set carried over answers

		$query = "
			SELECT qw.*
			FROM ace_rp_orders_questions_working qw
			LEFT JOIN ace_rp_questions q
			ON qw.question_id = q.id
			WHERE qw.order_id = (SELECT id
				FROM ace_rp_orders
				WHERE customer_id = $customer_id
				AND order_status_id IN (5,3,1)
				ORDER BY job_date DESC, order_status_id DESC
				LIMIT 1)
		";

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			foreach ($row as $k => $v)
			  $carried_answers[$row['question_id']][$k] = $v;
		}

		$this->set('carried_answers', $carried_answers);


		$this->set('questions', $questions);
		$this->set('responses', $responses);
		$this->set('suggestions', $suggestions);
		$this->set('decisions', $decisions);
		$this->set('item_id', $item_id);
		$this->set('operations', $operations);
		$this->set('which', $which);
		$this->set('working_answers', $working_answers);

		$this->set('jobtypes', $this->OrderType->findAll());
		//$this->set('jobcategories', $this->Lists->ListTable('ace_rp_order_type_categories'));

		//if(isset($this->data)) $this->set('template', $this->data);
	}

	function saveInvoiceTabletQuestions() {
		$order_id = $this->data['Invoice']['order_id'];
		$order_status_id = $this->data['Invoice']['order_status_id'];
		$template = $this->data['Template'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		foreach($template as $question_id => $row) {
			$response_text = isset($row['response_text'])?"'".$row['response_text']."'":"NULL";
			$response_id = isset($row['response_id'])?$row['response_id']:"NULL";
			$suggestion_id = isset($row['suggestion_id'])?$row['suggestion_id']:"NULL";
			$decision_id = isset($row['decision_id'])?$row['decision_id']:"NULL";

			$query = "
				DELETE FROM ace_rp_orders_questions_working
				WHERE order_id = $order_id
				AND question_id = $question_id
			";

			$result = $db->_execute($query);

			$query = "
				INSERT INTO ace_rp_orders_questions_working(order_id, question_id, response_text, response_id, suggestion_id, decision_id)
				VALUES($order_id, $question_id, $response_text, $response_id, $suggestion_id, $decision_id)
			";

			$result = $db->_execute($query);
		}

		$query = "
			INSERT INTO ace_rp_notes(message, note_type_id, order_id, user_id, urgency_id, note_date)
			SELECT CONCAT(
				'<strong>',q.value,'</strong><br>',
				IFNULL(r.value, qw.response_text),'<br>',
				s.value,'<br>',
				d.value,'<br>'
				),
				3, $order_id, ".$this->Common->getLoggedUserID().", 1, NOW()
			FROM ace_rp_orders_questions_working qw
			LEFT JOIN ace_rp_questions q
			ON q.id = qw.question_id
			LEFT JOIN ace_rp_responses r
			ON r.id = qw.response_id
			LEFT JOIN ace_rp_suggestions s
			ON s.id = qw.suggestion_id
			LEFT JOIN ace_rp_decisions d
			ON d.id = qw.decision_id
			WHERE d.notify = 1
			AND qw.order_id = $order_id
		";
		$db->_execute($query);

		$new_entry = 'tech_agent';

		if($order_status_id == 8) {
			$this->redirect("orders/invoiceTabletPrint?order_id=$order_id");
		} else {
			$this->redirect("orders/invoiceTabletItems?order_id=$order_id&new_entry=$new_entry");
		}

	}

	function setJobApproval() {
		$this->layout = "blank";

		$date = $_GET['date'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			SELECT o.id, o.order_number, o.job_date, o.job_truck, t1.first_name tech1, t2.first_name tech2, o.needs_approval, o.sale_approval
			FROM ace_rp_orders o
			LEFT JOIN ace_rp_users t1
			ON o.job_technician1_id = t1.id
			LEFT JOIN ace_rp_users t2
			ON o.job_technician2_id = t2.id
			WHERE o.job_date = '".date("Y-m-d", strtotime($date))."'
			AND o.order_status_id NOT IN(3,5)
			ORDER BY o.job_date, o.job_truck
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $jobs[$row['id']][$k] = $v;
		}

		$this->set('jobs', $jobs);
	}

	function saveJobApproval() {
		$approval = $this->data['Approval'];
		$saleapproval = $this->data['SaleApproval'];
		$noapproval = $this->data['NoApproval'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		foreach($noapproval as $id => $i) {
			$a = isset($approval[$id][$i])==1?1:0;
			$b = isset($saleapproval[$id][$i])==1?1:0;
			$query = "
				UPDATE ace_rp_orders
				SET needs_approval = $a,
				sale_approval = $b
				WHERE id = $id
			";
			$db->_execute($query);
		}
		echo "<script>window.close()</script>";
		//print_r($approval);
		//print_r($noapproval);
	}

	function _sigJsonToImage($json, $options = array())
	{
		$defaultOptions = array(
			'imageSize' => array(198, 55)
			,'bgColour' => array(0xff, 0xff, 0xff)
			,'penWidth' => 2
			,'penColour' => array(0x14, 0x53, 0x94)
			,'drawMultiplier'=> 12
		);

		$options = array_merge($defaultOptions, $options);

		$img = imagecreatetruecolor($options['imageSize'][0] * $options['drawMultiplier'], $options['imageSize'][1] * $options['drawMultiplier']);
		$bg = imagecolorallocate($img, $options['bgColour'][0], $options['bgColour'][1], $options['bgColour'][2]);
		$pen = imagecolorallocate($img, $options['penColour'][0], $options['penColour'][1], $options['penColour'][2]);
		imagefill($img, 0, 0, $bg);

		if(is_string($json))
			$json = json_decode(stripslashes($json));

		foreach($json as $v)
			$this->_drawThickLine($img, $v->lx * $options['drawMultiplier'], $v->ly * $options['drawMultiplier'], $v->mx * $options['drawMultiplier'], $v->my * $options['drawMultiplier'], $pen, $options['penWidth'] * ($options['drawMultiplier'] / 2));

		$imgDest = imagecreatetruecolor($options['imageSize'][0], $options['imageSize'][1]);
		imagecopyresampled($imgDest, $img, 0, 0, 0, 0, $options['imageSize'][0], $options['imageSize'][0], $options['imageSize'][0] * $options['drawMultiplier'], $options['imageSize'][0] * $options['drawMultiplier']);

		imagedestroy($img);

		return $imgDest;
	}

	/**
	 *	Draws a thick line
	 *	Changing the thickness of a line using imagesetthickness doesn't produce as nice of result
	 *
	 *	@param	object	$img
	 *	@param	int		$startX
	 *	@param	int		$startY
	 *	@param	int		$endX
	 *	@param	int		$endY
	 *	@param	object	$colour
	 *	@param	int		$thickness
	 *
	 *	@return	void
	 */
	function _drawThickLine($img, $startX, $startY, $endX, $endY, $colour, $thickness)
	{
		$angle = (atan2(($startY - $endY), ($endX - $startX)));

		$dist_x = $thickness * (sin($angle));
		$dist_y = $thickness * (cos($angle));

		$p1x = ceil(($startX + $dist_x));
		$p1y = ceil(($startY + $dist_y));
		$p2x = ceil(($endX + $dist_x));
		$p2y = ceil(($endY + $dist_y));
		$p3x = ceil(($endX - $dist_x));
		$p3y = ceil(($endY - $dist_y));
		$p4x = ceil(($startX - $dist_x));
		$p4y = ceil(($startY - $dist_y));

		$array = array(0=>$p1x, $p1y, $p2x, $p2y, $p3x, $p3y, $p4x, $p4y);
		imagefilledpolygon($img, $array, (count($array)/2), $colour);
	}

	function samOrders()
	{
		$this->layout = "blank";
		$sql = "SELECT id, branch, order_status_id, order_type_id, booking_date, booking_amount, sale_amount, booking_telemarketer_id, booking_source_id, job_date
              FROM ace_rp_orders orders LIMIT 100";

    $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$result = $db->_execute($sql);
    $index = 1;
		while ($row = mysql_fetch_array($result))
		{
        $res[$row['id']]['id'] = $row['id'];
        $res[$row['id']]['branch'] = $row['branch'];
        $res[$row['id']]['order_status_id'] = $row['order_status_id'];
        $res[$row['id']]['order_type_id'] = $row['order_type_id'];
        $res[$row['id']]['booking_date'] = $row['booking_date'];
        $res[$row['id']]['booking_amount'] = $row['booking_amount'];
        $res[$row['id']]['sale_amount'] = $row['sale_amount'];
        $res[$row['id']]['booking_telemarketer_id'] = $row['booking_telemarketer_id'];
        $res[$row['id']]['booking_source_id'] = $row['booking_source_id'];
		$res[$row['id']]['job_date'] = $row['job_date'];


		}

		$this->set('view_sam', $res);

	}

	function effCalculator() {
		$this->layout = "blank";
	}

	function scheduleByDistance() {
		$this->layout = "blank";

		$days_ahead = 7;
		$days_ahead1 = 7;
		$days_ahead2 = 7;
		$postal_code = $_GET['postal_code'];
		$weeks = isset($_GET['weeks'])?$_GET['weeks']:1;
		$hour_interval = isset($_GET['hour'])?$_GET['hour']:"";

		if($_GET['date_picker'] != '') $date_picker = date("Y-m-d", strtotime($_GET['date_picker']));
		else $date_picker = date("Y-m-d");

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			SELECT *
			FROM ace_rp_hourslots$hour_interval hs
		";

		$result = $db->_execute($query);

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$hourslots[$row['id']]['name']= $row['name'];
			$hourslots[$row['id']]['hour']= $row['hour'];
		}

		$query = "
			SELECT *
			FROM ace_rp_inventory_locations il
		";

		$result = $db->_execute($query);

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$allslots[$row['id']]['name']= $row['name'];
		}

		$query = "
			SELECT *
			FROM ace_rp_inventory_locations il
			WHERE il.route_type = 1
		";

		$result = $db->_execute($query);

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$furnaceslots[$row['id']]['name']= $row['name'];
			$furnaceslots[$row['id']]['abbreviation']= $row['abbreviation'];
		}

		$query = "
			SELECT *
			FROM ace_rp_inventory_locations il
			WHERE il.route_type = 2
		";

		$result = $db->_execute($query);

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$airductslots[$row['id']]['name']= $row['name'];
			$airductslots[$row['id']]['abbreviation']= $row['abbreviation'];
		}

		$query = "
			SELECT *
			FROM ace_rp_inventory_locations il
			WHERE il.route_type = 4
		";

		$result = $db->_execute($query);

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$installationslots[$row['id']]['name']= $row['name'];
			$installationslots[$row['id']]['abbreviation']= $row['abbreviation'];
		}


		//get the postal codes
		$home_base = "v5c";
		for($i = 1; $i <= $days_ahead1; $i++) {
			$dates1[$i]["date"] = date("Y-m-d", strtotime(date("Y-m-d"). " + $i days"));
			$dates1[$i]["week"] = date("D", strtotime(date("Y-m-d"). " + $i days"));
			$dates1[$i]["day"] = date("d", strtotime(date("Y-m-d"). " + $i days"));
			$query = "SELECT hs.id, hs.name, hs.hour,";
				foreach($allslots as $id => $slot) {
					$query .= "
						(SELECT (SELECT SUBSTRING(postal_code, 1, 3) postal_code FROM ace_rp_customers WHERE id = o.customer_id)
						FROM ace_rp_orders o
						WHERE job_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
						AND order_status_id NOT IN(3,2,8)
						AND (
							(HOUR(hs.hour_start) >= HOUR(job_time_beg) AND HOUR(hs.hour_end) < HOUR(job_time_end))
							OR
							HOUR(hs.hour_start) = HOUR(job_time_beg)
							OR
							HOUR(hs.hour_end) = HOUR(job_time_beg)
							)
						AND job_truck = $id LIMIT 1) '$id',
						(
						SELECT seconds
						FROM ace_rp_postal_distances
						WHERE `from` =
							IF(HOUR(hs.hour_start) = 8, '$home_base',
							(SELECT (SELECT SUBSTRING(postal_code, 1, 3) postal_code FROM ace_rp_customers WHERE id = o.customer_id)
							FROM ace_rp_orders o
							WHERE job_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND order_status_id NOT IN(3,2,8)
							AND (
								(HOUR(hs.hour_start)-2 >= HOUR(job_time_beg) AND HOUR(hs.hour_end)-2 < HOUR(job_time_end))
								OR
								HOUR(hs.hour_start)-2 = HOUR(job_time_beg)
								OR
								HOUR(hs.hour_end)-2 = HOUR(job_time_beg)
								)
							AND job_truck = $id LIMIT 1))
						AND `to` =
							(SELECT (SELECT SUBSTRING(postal_code, 1, 3) postal_code FROM ace_rp_customers WHERE id = o.customer_id)
							FROM ace_rp_orders o
							WHERE job_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND order_status_id NOT IN(3,2,8)
							AND (
								(HOUR(hs.hour_start) >= HOUR(job_time_beg) AND HOUR(hs.hour_end) < HOUR(job_time_end))
								OR
								HOUR(hs.hour_start) = HOUR(job_time_beg)
								OR
								HOUR(hs.hour_end) = HOUR(job_time_beg)
								)
							AND job_truck = $id LIMIT 1)
						) 'd$id',";
				}

			$query = substr($query, 0, -1);

			$query .= "
				FROM ace_rp_hourslots$hour_interval hs
			";

			$result = $db->_execute($query);

			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$scheduleslots1[$i][$row['id']]['name']= $row['name'];
				$scheduleslots1[$i][$row['id']]['hour']= $row['hour'];
				$scheduleslots1[$i][$row['id']]['open']= 1;
				foreach($allslots as $id => $slot) {
					$scheduleslots1[$i][$row['id']][$id]= $row[$id];
				}
				foreach($allslots as $id => $slot) {
					$scheduleslots1[$i][$row['id']]["d".$id]= $row["d".$id];
				}
			}
		}

		//get the distance

		if($weeks > 1) {
			for($i = $days_ahead1 + 1; $i <= $days_ahead1 + $days_ahead2; $i++) {
				$dates2[$i]["date"] = date("Y-m-d", strtotime(date("Y-m-d"). " + $i days"));
				$dates2[$i]["week"] = date("D", strtotime(date("Y-m-d"). " + $i days"));
				$dates2[$i]["day"] = date("d", strtotime(date("Y-m-d"). " + $i days"));
				$query = "SELECT hs.id, hs.name, hs.hour,";
					foreach($allslots as $id => $slot) {
						$query .= "
						(SELECT (SELECT SUBSTRING(postal_code, 1, 3) postal_code FROM ace_rp_customers WHERE id = o.customer_id)
						FROM ace_rp_orders o
						WHERE job_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
						AND order_status_id NOT IN(3,2,8)
						AND (
							(HOUR(hs.hour_start) >= HOUR(job_time_beg) AND HOUR(hs.hour_end) < HOUR(job_time_end))
							OR
							HOUR(hs.hour_start) = HOUR(job_time_beg)
							OR
							HOUR(hs.hour_end) = HOUR(job_time_beg)
							)
						AND job_truck = $id LIMIT 1) '$id',
						(
						SELECT seconds
						FROM ace_rp_postal_distances
						WHERE `from` =
							IF(HOUR(hs.hour_start) = 8, '$home_base',
							(SELECT (SELECT SUBSTRING(postal_code, 1, 3) postal_code FROM ace_rp_customers WHERE id = o.customer_id)
							FROM ace_rp_orders o
							WHERE job_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND order_status_id NOT IN(3,2,8)
							AND (
								(HOUR(hs.hour_start)-2 >= HOUR(job_time_beg) AND HOUR(hs.hour_end)-2 < HOUR(job_time_end))
								OR
								HOUR(hs.hour_start)-2 = HOUR(job_time_beg)
								OR
								HOUR(hs.hour_end)-2 = HOUR(job_time_beg)
								)
							AND job_truck = $id LIMIT 1))
						AND `to` =
							(SELECT (SELECT SUBSTRING(postal_code, 1, 3) postal_code FROM ace_rp_customers WHERE id = o.customer_id)
							FROM ace_rp_orders o
							WHERE job_date = DATE_ADD(CURDATE(), INTERVAL $i DAY)
							AND order_status_id NOT IN(3,2,8)
							AND (
								(HOUR(hs.hour_start) >= HOUR(job_time_beg) AND HOUR(hs.hour_end) < HOUR(job_time_end))
								OR
								HOUR(hs.hour_start) = HOUR(job_time_beg)
								OR
								HOUR(hs.hour_end) = HOUR(job_time_beg)
								)
							AND job_truck = $id LIMIT 1)
						) 'd$id',";
					}

				$query = substr($query, 0, -1);

				$query .= "
					FROM ace_rp_hourslots$hour_interval hs
				";

				$result = $db->_execute($query);

				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$scheduleslots2[$i][$row['id']]['name']= $row['name'];
					$scheduleslots2[$i][$row['id']]['hour']= $row['hour'];
					$scheduleslots2[$i][$row['id']]['open']= 1;
					foreach($allslots as $id => $slot) {
						$scheduleslots2[$i][$row['id']][$id]= $row[$id];
					}
					foreach($allslots as $id => $slot) {
						$scheduleslots2[$i][$row['id']]["d".$id]= $row["d".$id];
					}
				}
			}
		} //END if($weeks > 1) {

		$this->set('prevdate', date("d M Y", strtotime('-1 day', strtotime($date_picker))));
		$this->set('date_picker', date("d M Y", strtotime($date_picker)));
		$this->set('nextdate', date("d M Y", strtotime('+1 day', strtotime($date_picker))));
		$this->set('hourslots', $hourslots);
		$this->set('furnaceslots', $furnaceslots);
		$this->set('airductslots', $airductslots);
		$this->set('installationslots', $installationslots);
		$this->set('scheduleslots', $scheduleslots);
		$this->set('scheduleslots1', $scheduleslots1);
		$this->set('scheduleslots2', $scheduleslots2);
		$this->set('days_ahead', $days_ahead);
		$this->set('days_ahead1', $days_ahead1);
		$this->set('days_ahead2', $days_ahead2);
		$this->set('breaker', $breaker);
		$this->set('dates', $dates);
		$this->set('dates1', $dates1);
		$this->set('dates2', $dates2);
		$this->set('weeks', $weeks);
	}

	function _saveQuestionsAsFinal($order_id) {
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$date = new DateTime(date());
		// $date = new DateTime();
		$date_saved = $date->format('Y-m-d H:i:s');

		$query = "
					DELETE FROM ace_rp_orders_questions_final
					WHERE order_id = ".$order_id;

			$result = $db->_execute($query);

		$query = "
			INSERT INTO ace_rp_orders_questions_final(question_number, order_id, question_text, response_text, suggestion_text, decision_text, date_saved)
			SELECT q.id, qw.order_id,  q.value,
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

	function carryAnswers() {
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$date = new DateTime(date());
		$date_saved = $date->format('Y-m-d H:i:s');
		$customer_id = 52747;

		$query = "
			SELECT qw.*
			FROM ace_rp_orders_questions_working qw
			LEFT JOIN ace_rp_questions q
			ON qw.question_id = q.id
			WHERE qw.order_id = (SELECT id
				FROM ace_rp_orders
				WHERE customer_id = $customer_id
				AND order_status_id IN (5,3,1)
				ORDER BY job_date DESC, order_status_id DESC
				LIMIT 1)
			AND q.is_permanent = 0
		";

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			foreach ($row as $k => $v)
			  $carried_answers[$row['question_id']][$k] = $v;
		}

		$this->set('carried_answers', $carried_answers);
	}


	function uploadImage() {
		$this->layout = "blank";
	}

	function invoiceTabletEprint($orderid,$email){
		//END Save Notes
		if(isset($orderid) && $orderid!=''){
			$order_id = $orderid;
		}else{
			$order_id = $_GET['order_id'];
		}

		if(isset($email) && $email!=''){
			$email = $email;
		}else{
			$email = $_GET['email'];
		}
		//echo ''.$order_id.'=='.$email;
		$this->layout = "blank";


		$fileUrl ="http://hvacproz.ca/acesys/index.php/orders/invoiceTabletPrint?order_id=".$order_id."&type=office";

		// $fileUrl =BASE_PATH."orders/invoiceTabletPrint?order_id=".$order_id."&type=office";
		
		set_time_limit(300);
		$subject = 'Ace Services Ltd';
		$settings = $this->Setting->find(array('title'=>'email_template_custom1'));
		$template = $settings['Setting']['valuetxt'];

		$msg = $template;
	
		$msg = str_replace('{file_url}', $fileUrl, $msg);
		// $invoice = file_get_contents(BASE_PATH."orders/invoiceTabletPrint?order_id=$order_id&type=office");
		$invoice = file_get_contents("http://hvacproz.ca/acesys/index.php/orders/invoiceTabletPrint?order_id=$order_id&type=office");
		$boundary = md5(time());
		$header = "From: info@acecare.ca \r\n";
		$header .= "MIME-Version: 1.0\r\n";
		$header .= "Content-Type: multipart/mixed;boundary=\"" . $boundary . "\"\r\n";

		$output = "--".$boundary."\r\n";
		//$output .= "Content-Type: text/csv; name=\"invoice.html\";\r\n";
		//$output .= "Content-Disposition: attachment;\r\n\r\n";
		$output .= $invoice."\r\n\r\n";
		$output .= "--".$boundary."\r\n";
		$output .= "Content-type: text/html; charset=\"utf-8\"\r\n";
		$output .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
		$output .= $msg."\r\n\r\n";
		$output .= "--".$boundary."--\r\n\r\n";

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			UPDATE ace_rp_orders
			SET order_status_id = 5
			WHERE id = $order_id
		";
		$db->_execute($query);
		$res = mail($email, $subject, $output, $header);
		$this->set('printerNumber', $printerNumber);

		$this->redirect("orders/invoiceTabletPrint?order_id=$order_id&type=$type");


		//$order_id = $_GET['order_id'];
		//$email = $_GET['email'];
		/*$type = 'office';

		$this->set('order_id', $order_id);

		$user_id = $this->Common->getLoggedUserID();

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			UPDATE ace_rp_orders
			SET order_status_id = 5
			WHERE id = $order_id
		";
		$db->_execute($query);

		$eprint_id = $_SESSION['user']['eprint_id'];

		$subject = 'Ace Services Ltd';
		$headers = "From: info@acecare.ca\n";
		$headers .= "Content-Type: text/html; charset=iso-8859-1\n";

		//$msg = file_get_contents("http://acecare.ca/acesys/index.php/orders/invoiceTabletPrint?order_id=$order_id&type=$type");
		$msg = "<p>Click below link to view your invoice. </p>";
		$msg .= "http://aceno1.ca/development/index.php/orders/invoiceTabletPrint?order_id=$order_id&type=$type";*/
		//$email = "niks.pawale@gmail.com";
		/*$res = mail($email, $subject, $msg, $headers);
		//$res = mail('proaceprinter01@hpeprint.com', $subject, $msg, $headers);
		//$res = mail('hsestacio13@gmail.com', $subject, $msg, $headers);

		$this->set('printerNumber', $printerNumber);

		$this->redirect("orders/invoiceTabletPrint?order_id=$order_id&type=$type");*/
	}



	function emailInvoiceReviewLinks($orderid,$email){
		//END Save Notes
		if(isset($orderid) && $orderid!=''){
			$order_id = $orderid;
		}else{
			$order_id = $_GET['order_id'];
		}

		if(isset($email) && $email!=''){
			$email = $email;
		}else{
			$email = $_GET['email'];
		}
			
		$fileUrl = "http://hvacproz.ca/acesys/index.php/orders/invoiceTabletPrint?order_id=".$order_id."&type=office";
		set_time_limit(300);
		$subject = 'Ace Services Ltd';
		$settings = $this->Setting->find(array('title'=>'email_template_custom'));
		$template = $settings['Setting']['valuetxt'];
		$msg = $template;
		$msg = str_replace('{file_url}', $fileUrl, $msg);
		$invoice = file_get_contents("http://hvacproz.ca/acesys/index.php/orders/invoiceTabletPrint?order_id=$order_id&type=office");

		$boundary = md5(time());
		$header = "From: info@acecare.ca \r\n";
		$header .= "MIME-Version: 1.0\r\n";
		$header .= "Content-Type: multipart/mixed;boundary=\"" . $boundary . "\"\r\n";

		$output = "--".$boundary."\r\n";
		//$output .= "Content-Type: text/csv; name=\"invoice.html\";\r\n";
		//$output .= "Content-Disposition: attachment;\r\n\r\n";
		$output .= $invoice."\r\n\r\n";
		$output .= "--".$boundary."\r\n";
		$output .= "Content-type: text/html; charset=\"utf-8\"\r\n";
		$output .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
		$output .= $msg."\r\n\r\n";
		$output .= "--".$boundary."--\r\n\r\n";

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			UPDATE ace_rp_orders
			SET order_status_id = 5
			WHERE id = $order_id
		";
		$db->_execute($query);
		$res = mail($email, $subject, $output, $header);
		$this->redirect("orders/invoiceTabletPrint?order_id=$order_id&type=$type");
	}

	function noInvoiceReviewLinks(){
		//END Save Notes
		$order_id = $_GET['order_id'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			UPDATE ace_rp_orders
			SET order_status_id = 5
			WHERE id = $order_id
		";

		$db->_execute($query);
		exit();
	}

	function validateEmailAddress($cEmail){
    	/*$response = exec("curl -G --user 'api:pubkey-02f5eddb05645b5c1135ee2f8c2e206f' -G \
			    https://api.mailgun.net/v3/address/validate \
		    --data-urlencode address='".$cEmail."'");
		$arr = json_decode($response);
		return $arr->is_valid;*/
		return true;
    }
    
    
    function referrals(){
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		//$this->layout='referrals';
		$query = "SELECT a.*,concat(b.first_name,' ',b.last_name) as referred_by_name,b.id as user_id, b.existinguser_id ,b.card_number as ref_card_number FROM ace_us_referral as a left join ace_us_users as b on a.user_id=b.id";

		$result = $db->_execute($query);


		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$arr=array();
			foreach ($row as $k => $v){
				$arr[$k]=$v;
			}
			$ref[]=$arr;
		}
       //echo '<pre>';print_r($ref);echo 'ppppppp';
		$this->set('referral', $ref);
	}
    
/*************************jackhutson *************/
function phone_calltype(){
     $calltype = $_REQUEST['calltype'];
     $_SESSION['calltype']=$calltype;
     echo $_SESSION['calltype'];
     exit;
}
  
 
/******************************savequestionget************************************/

	function showTemplateQuestionsDefault() {
		$this->layout = "blank";
		$order_id = $_GET['order_id'];
		$question_type = $_GET['question_type'];
		$customer_id = $_GET['customer_id'];
		if($question_type == 2) {
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

			$query = "
				SELECT *
				FROM ace_rp_orders_questions_final
				WHERE order_id = $order_id
				ORDER BY id, question_number, date_saved
			";

			$questions = array();
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				foreach ($row as $k => $v)
				  $questions[$row['id']][$k] = $v;
			}

			$this->set('questions', $questions);
			$this->set('mode', 1);

		} else {
			if($question_type == 0) {
				$search_in_questions = "AND q.for_office = 1";
			} else {
				$search_in_questions = "AND q.for_tech = 1";
			}
			//$order_type_id = $_GET['job_type'];
			$strStyle = $_GET['strStyle'];

			$this->set('order_id', $order_id);

			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

			//$item_id = $order_type_id;
           $query = "
				SELECT *
				FROM ace_rp_questions q
				WHERE q.for_service = 1
				AND q.id IS NOT NULL
				$search_in_questions
				order by rank, value
			";
			

			$questions = array();
			$result = $db->_execute($query);
			
			
			 
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				foreach ($row as $k => $v)
				  $questions[$row['id']][$k] = $v;
			}

			$query = "
				SELECT r.*
				FROM ace_rp_questions q
				LEFT JOIN ace_rp_responses r
				ON q.id = r.question_id
				WHERE q.for_service = 1
				AND r.id IS NOT NULL
				$search_in_questions
			";

			$responses = array();
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				foreach ($row as $k => $v)
				  $responses[$row['question_id']][$row['id']][$k] = $v;
			}

			$query = "
				SELECT s.*
				FROM ace_rp_questions q
				LEFT JOIN ace_rp_responses r
				ON q.id = r.question_id
				LEFT JOIN ace_rp_suggestions s
				ON r.id = s.response_id
				WHERE q.for_service = 1
				AND s.id IS NOT NULL
				$search_in_questions
			";
			$suggestions = array();
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				foreach ($row as $k => $v)
				  $suggestions[$row['response_id']][$row['id']][$k] = $v;
			}

			$query = "
				SELECT d.*
				FROM ace_rp_questions q
				LEFT JOIN ace_rp_responses r
				ON q.id = r.question_id
				LEFT JOIN ace_rp_suggestions s
				ON r.id = s.response_id
				LEFT JOIN ace_rp_decisions d
				ON s.id = d.suggestion_id
				WHERE q.for_service = 1
				AND d.id IS NOT NULL
				$search_in_questions
			";

			$decisions = array();
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				foreach ($row as $k => $v)
				  $decisions[$row['suggestion_id']][$row['id']][$k] = $v;
			}

			//set answers
			if(isset($order_id) && trim($order_id) !="" ) {

				$query = "
					SELECT *
					FROM ace_rp_orders_questions_working
					WHERE order_id = $order_id
				";

				$working_answers = array();

				$result = $db->_execute($query);
				//$row = mysql_fetch_array($result, MYSQL_ASSOC);
				while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				{
					foreach ($row as $k => $v)
						$working_answers[$row['question_id']][$k] = $v;
				}

				$this->set('working_answers', $working_answers);

			}

			//set carried over answers
			if(isset($customer_id) && trim($customer_id) !="" ) {
				$query = "
					SELECT qw.*
					FROM ace_rp_orders_questions_working qw
					LEFT JOIN ace_rp_questions q
					ON qw.question_id = q.id
					WHERE qw.order_id = (SELECT id
						FROM ace_rp_orders
						WHERE customer_id = $customer_id
						AND order_status_id IN (5,3,1)
						ORDER BY job_date DESC, order_status_id DESC
						LIMIT 1)
					AND q.is_permanent = 1
				";

				$result = $db->_execute($query);

				while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					foreach ($row as $k => $v)
					  $carried_answers[$row['question_id']][$k] = $v;
				}

				$this->set('carried_answers', $carried_answers);
			}
           
           $query = "
			SELECT m.* 
			FROM ace_rp_questions q
			LEFT JOIN ace_rp_responses r
			ON q.id = r.question_id
			LEFT JOIN ace_rp_suggestions s
			ON r.id = s.response_id
			LEFT JOIN ace_rp_decisions d
			ON s.id = d.suggestion_id
            LEFT JOIN ace_rp_reminders m
			ON d.id = m.decision_id
			WHERE q.for_service = 1
		";
		
		$reminders = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			$reminders[$row['decision_id']][$k] = $v;
			 //$reminders[$row['id']][$k] = $v;
			  //$reminders[$row['decision_id']]=$row['value'];
		}

			$this->set('questions', $questions);
			$this->set('responses', $responses);
			$this->set('suggestions', $suggestions);
			$this->set('decisions', $decisions);
			$this->set('reminders', $reminders);
			$this->set('mode', 0);
		}
	} 

function SearchQuestions(){
$this->layout = "blank";
$query = "SELECT * FROM ace_rp_questions WHERE for_service = 1";
$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
 $result = $db->_execute($query);
 $sers = array();
 while($row = mysql_fetch_array($result, MYSQL_ASSOC))
  {
	/*foreach ($row as $val){ */
         $sers[] = $row;
   /*  }*/
    
 }
   $this->set('sers', $sers);
}

function weeklySchedule(){
		$db =& ConnectionManager::getDataSource('default');
		$this->layout='edit';
		$p_code = strtoupper(substr($_REQUEST['p_code'],0,3));
		$city = strtoupper($_REQUEST['city']);

		if ($this->params['url']['date_from'] != '')
			$date_from = date("Y-m-d", strtotime($this->params['url']['date_from']));
    	else
			$date_from = date("Y-m-d");

		$date_to = date("Y-m-d", strtotime("$date_from +2 week"));


		//get users trucks 
		
		$map_reverse = array();
		$map_all = array();
		$trucks = array();

		$route_type = $_REQUEST['route_type'];
		if (!$route_type)
			if (($this->Common->getLoggedUserRoleID()==1))
				$route_type = '2';
			elseif (($this->Common->getLoggedUserRoleID()!=6))
				$route_type = '1';

		$cond = '';
		if ($route_type) $cond = 'and route_type='.$route_type;

		$userId=$this->Common->getLoggedUserID();
		if($this->Common->getLoggedUserRoleID()==3){
			$query = "select ace_rp_inventory_locations.* from ace_rp_inventory_locations inner join ace_rp_truck_maps as artm on ace_rp_inventory_locations.id=artm.truck_id where type=2 and artm.user_id=".$userId." $cond order by id asc";	
		}else{
			$query = "select * from ace_rp_inventory_locations where type=2 $cond order by id asc";
		}

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result)) {
			$trucks[$row['id']]=array(
						'id'=>$row['id'],
						'name'=>$row['name'],
						'color'=>$row['color'],
						'truck_number'=>$row['truck_number'],
						'route_number'=>$row['route_number']
					);
			for ($j=2; $j<8; $j++)
			{
				$map_all[$j][$row['id']] = 'ALL';
				for ($i=8; $i<18; $i++)
					$map_reverse[$row['id']][$j][$i][] = 'ALL';
			}
			
		}

		$dates = array();
		$days=0;
		while($days<14){

			$dateStr=strtotime("$date_from +$days day");
			$dates[$dateStr]['weekday_name'] = date("l", $dateStr);
			$dates[$dateStr]['weekday'] = date("w", $dateStr);;
			$dates[$dateStr]['name'] = date("d M Y", $dateStr);
			$dates[$dateStr]['date'] = date("Y-m-d", $dateStr);
			$days++;
		}
		
		


		$sqlConditions = " AND a.job_date between '$date_from' and '$date_to'"; //$this->params['url']['ffromdate']
		if ($route_type)
			$sqlConditions .= ' and a.job_truck in (select id from ace_rp_inventory_locations where route_type='.$route_type.') ';

		$orders = array();

		$status_condition = '';
		if ($this->Common->getLoggedUserRoleID() == "3"||$this->Common->getLoggedUserRoleID() == "9")
			$status_condition = ' and order_status_id!=3 ';

		$query = "SELECT a.id, a.order_status_id,a.emal_bounce_status, a.order_substatus_id, a.job_truck,
						 a.sale_amount, a.job_timeslot_id, a.job_time_beg, a.job_time_end,
						 a.job_date, a.job_technician1_id, a.job_technician2_id, a.order_type_id,
						 a.sCancelReason, c.city as zone_city, c.postal_code as postal_code1,
						 c.color as color, a.booking_source_id as booking_source_id, s.first_name as booking_source_fn,
						 s.last_name as booking_source_ln, c.zone_name as zone_name, u.postal_code as postal_code,
						 CONCAT(u.address_unit,', ',u.address_street_number,', ',u.address_street) as address,
						 'BC' as state, u.phone as customer_phone,
						 concat(u.first_name,' ',u.last_name) as customer_name, u.city as user_city,
						 jt.name job_type_name, a.verified_by_id, rr.role_id, jt.category_id,
						 a.app_ordered_by, a.permit_result, a.order_number, CONCAT(ur.name, ' - ', cr.name) dCancelReason,
						 a.tech_visible
					FROM `ace_rp_orders` as a
					LEFT JOIN `ace_rp_order_types` as jt on ( a.order_type_id = jt.id )
					LEFT JOIN `ace_rp_customers` as u on ( a.customer_id = u.id )
					LEFT JOIN `ace_rp_users` as s on ( a.booking_source_id = s.id )
					LEFT JOIN `ace_rp_users` as t on ( a.booking_telemarketer_id = t.id )
					LEFT JOIN `ace_rp_users_roles` as rr on ( rr.user_id = t.id )
					LEFT JOIN `ace_rp_zones` as c on ( (LCASE(LEFT(a.job_postal_code,3)) = LCASE(LEFT(c.postal_code,3))) or (LCASE(c.city) LIKE LCASE(u.city)) )
					LEFT JOIN ace_rp_cancellation_reasons cr ON a.cancellation_reason = cr.id
					LEFT JOIN ace_rp_roles ur ON ur.id = cr.role_id
				   WHERE order_status_id < 6 $status_condition $sqlConditions order by a.id asc";

		$redo = array();
		$followup = array();
		$install = array();
		$other = array();
		$result = $db->_execute($query);

		
		while($row = mysql_fetch_array($result))
		{
			foreach ($row as $k => $v)
			  $orders[$row['id']][$k] = $v;

			if ($row['order_type_id'] == 9) $redo[$row['order_status_id']][$row['id']] = 1;
			elseif ($row['order_type_id'] == 10) $followup[$row['order_status_id']][$row['id']] = 1;
			elseif ($row['category_id'] == 2) $install[$row['order_status_id']][$row['id']] = 1;
			else $other[$row['order_status_id']][$row['id']] = 1;

			$orders[$row['id']]['tech_visible'] = $row['tech_visible'];
			$orders[$row['id']]['truck'] = $row['job_truck'];
			$orders[$row['id']]['city'] = (($row['user_city'] != "") ? $row['user_city'] : $row['zone_city']);

			for ($i = date('G', strtotime($row['job_time_beg'])); $i<date('G', strtotime($row['job_time_end'])); $i++)
			{
				unset($map_reverse[$row['job_truck']][$i]);
				unset($map_all[$row['job_truck']]);
				if (isset($map_reverse[$row['job_truck']][$i-1]))
				{
					$map_reverse[$row['job_truck']][$i-1][] = substr($row['postal_code'],0,3);
					$map_reverse[$row['job_truck']][$i-1][] = $orders[$row['id']]['city'];
				}
				if (isset($map_reverse[$row['job_truck']][$i+1]))
				{
					$map_reverse[$row['job_truck']][$i+1][] = substr($row['postal_code'],0,3);
					$map_reverse[$row['job_truck']][$i+1][] = $orders[$row['id']]['city'];
				}
			}

			//Check for the special marks
			if (($row['app_ordered_by']>0)||($row['category_id']!=2))
				$orders[$row['id']]['appliance_ordered'] = true;
			else
				$orders[$row['id']]['appliance_ordered'] = false;

			if (($row['order_status_id']!=5)||$row['permit_result']||($row['category_id']!=2))
				$orders[$row['id']]['permit_ordered'] = true;
			else
				$orders[$row['id']]['permit_ordered'] = false;
		}

		//Determine if all trucks use same techs
		foreach ($trucks as $truck_k => $truck_v)
		{
			$trucktech[$truck_k][0] = '';
			$trucktech[$truck_k][1] = '';
		}

		$flag1 = array();
		$flag2 = array();
		foreach ($orders as $order)
		{
			$truck_k = $order['truck'];

			if ($order['job_technician1_id']!=$trucktech[$truck_k][0])
			{
				if ($trucktech[$truck_k][0]||$flag1[$truck_k]) $trucktech[$truck_k][0] = '';
				else $trucktech[$truck_k][0] = $order['job_technician1_id'];
				$flag1[$truck_k] = true;
			}

				if ($order['job_technician2_id']!=$trucktech[$truck_k][1])
			{
				if ($trucktech[$truck_k][1]||$flag2[$truck_k]) $trucktech[$truck_k][1] = '';
				else $trucktech[$truck_k][1] = $order['job_technician2_id'];
				$flag2[$truck_k] = true;
			}
		}


		// Reverce the map
		$map = array();
		if ($city||$p_code)
			foreach ($map_reverse as $truck_k => $time_v)
			{
				foreach ($time_v as $time_k => $map_val)
				{
					foreach ($map_val as $val)
					{
						if (in_array($val, $neighbours))
							$map[$time_k][] = $truck_k;
					}
				}
			}



		$query = "SELECT * FROM ace_rp_route_visibility WHERE job_date between '$date_from' and '$date_to'";

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result)) {
			$routeVisibility[$row['route_id']]['route_id'] = $row['route_id'];
			$routeVisibility[$row['route_id']]['job_date'] = $row['job_date'];
			$routeVisibility[$row['route_id']]['show'] = 1;
		}

		
		//Find Max and Min time
		$query = "SELECT MAX(`to`) as end, MIN(`from`) as beg FROM ace_rp_timeslots";
		$result = $db->_execute($query);
		if ($row = mysql_fetch_array($result))
		{
			$this->set('time_beg', $row['beg']);
			$this->set('time_end', $row['end']);
		}
		$substatuses = $this->Lists->ListTable('ace_rp_order_substatuses');

		if ($this->Common->getLoggedUserRoleID() != "1") $method = "editBooking"; else $method = "techBooking";
		

		
		//Prepare job types
		$jobtypes = $this->Lists->ListTable('ace_rp_order_types');
		$date_from=date("d M Y", strtotime($date_from));
		$ydate=date("d M Y", strtotime("$date_from - 2 week"));
		$tdate=date("d M Y", strtotime("$date_from + 2 week"));
		$this->set(compact('date_from','ydate','tdate','city','p_code','trucks','dates','routeVisibility','orders','map','substatuses','jobtypes','method'));
		$this->set('allCities',$this->Lists->ActiveCities());

		$this->set('allTypes', $this->Lists->ListTable('ace_rp_route_types'));
		
		
	} 

function deleteUserFromCampaign() 
{
	$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
	$customer_id = $_POST['customer_id'];
	$query = "DELETE from ace_rp_all_campaigns where call_history_ids = $customer_id";
 		$result = $db->_execute($query);
 	if($result)
 	{
 		$query = "UPDATE ace_rp_customers set campaign_id = '' where id = $customer_id";
 		$result = $db->_execute($query);		
 	}
 	echo "hi";
 	exit();
}

// #LOKI- save callRecordings
 // function saveCallRecording()
 // {
 //         // print_r("fsjkf");die;
 // 	// $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
 // 	// $recordingName = $_POST['recording_name'];
 // 	// $phoneNumber = $_POST['phone_number'];
 // 	// $orderNumber = !empty($_POST['order_num']) ? $_POST['order_num'] : 'null';
 // 	// $date = date("Y-m-d h:i:s");

 // 	// $query = "INSERT into ace_rp_call_recordings (phone_no, recording_name, record_date, order_number) values ('".$phoneNumber."', '".$recordingName."', '".$date."',".$orderNumber.")";
 // 	// 	$result = $db->_execute($query);
 // 	// if($result)
 // 	// {
 // 	// 	echo "OK";
 // 	// 	exit();
 // 	// }

	// // $putdata = fopen("php://input", "r");

	// // print_r($putdata);die;
	// // $from = $_GET["from"];
	// // $from = substr($from, 0, strpos($from, '@'));
	// // $to = $_GET["to"];
	// // $to = substr($to, 0, strpos($to, '@'));
	// // $ext = $_SERVER['REQUEST_URI'];
	// // $ext = substr($ext, strpos($ext, '?') - 3, 3);
	// // $r = $_GET["call_id"];
	//  // $d = date("YmdHis");
	//  // $fp = fopen("$d.JPG", "w");
	//  //  while ($data = fread($putdata, 1024))
 //  //   fwrite($fp, $data);
 
 //  // /* Close the streams */
 //  // fclose($fp);
 //  // fclose($putdata);
 // }

 function showPaymentImages()
 {
 	$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
 	$search = isset($_GET['search-image']) ? $_GET['search-image'] : '';
 	if(!empty($search))
 	{
 		$query = "SELECT id, order_number, payment_image from ace_rp_orders where order_number=".$search;
 	} else {
 		$query = "SELECT id, order_number, payment_image from ace_rp_orders where payment_image != '' OR payment_image != NULL";
 	 }
 		$result = $db->_execute($query);
 		$payment_image = array();
 		while($row = mysql_fetch_array($result)) {
			$payment_image[] = $row;
		}
		$this->set('paymentImages', $payment_image);
 }
 function deletePaymentImages()
 {
 	$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
  	foreach($_POST['paymentImages'] as $orderId)
 	{
		$query = "SELECT payment_image from ace_rp_orders where order_number=".$orderId."";
 		$result = $db->_execute($query);

 		while($row = mysql_fetch_array($result))
 		 {
			$filename = ROOT.'/app/webroot/payment-images/'.$row['payment_image'];
			$query = "UPDATE ace_rp_orders set payment_image = '' where order_number =".$orderId."";
			$result = $db->_execute($query);
				if (file_exists($filename)) 
				{
				    unlink($filename);
				    echo 'File '.$filename.' has been deleted';
				} 
		}
	}
 	$this->redirect('/orders/showPaymentImages');
 	exit();
 }

 function checkJobAssigned($techId, $commDate ) {
 	$i = 0;
 	$compareDate = date("Y-m-d", strtotime("-30 days"));
	$commDate = date("Y-m-d", strtotime("-1 days", strtotime($commDate)));
	$techId = $techId;
 	$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
    $query = "SELECT job_date as max_job_date from ace_rp_orders where job_date ='".$commDate."' AND ( booking_source_id=".$techId." OR booking_source2_id=".$techId." OR job_technician1_id=".$techId." OR job_technician2_id=".$techId.") AND tech_visible = 1 limit 1"; 	
    $result = $db->_execute($query);
    $getCommDate = mysql_fetch_array($result, MYSQL_ASSOC);
	 $commDate1 = $getCommDate['max_job_date'];
	  if (!empty($commDate1) || $commDate1 != '') {
	    // end the recursion
	    return $commDate1;
	  } else {
	  	$i++;
	  	if($commDate == $compareDate)
	  	{
	  		 $commDate1 = '';
	  		return $commDate1;
	  	}
	    // continue the recursion
	   $commDate1 = $this->checkJobAssigned( $techId,$commDate);
	   return $commDate1;
		}
	}

	public function setChatResSession()
	{
		$res = $_POST['chatRes'];
		$_SESSION['user']['old_chat_res'] = $res;
		exit();
	}

	#Loki: Set reminder date
	function setReminderEmail()
	{
		$orderId = $this->params['url']['ordid'];
		$reminderMonths = $_POST['reminderMonths'];
		$reminderDate = $_POST['reminderDate'];
		$reminderType = !empty($_POST['reminderType']) ? $_POST['reminderType'] : 0;
		$remiderNote = !empty($_POST['remiderNote']) ? $_POST['remiderNote'] : NULL;
		$reminderEmailSend = $_POST['reminderEmailSend'];
		$reminderSmsSend = $_POST['reminderSmsSend'];
		$emailId = $_POST['emailId'];
		$smsNum = $_POST['smsNum'];
		$callbackNum = $_POST['callbackNum'];
		$reminderCallback = $_POST['reminderCallback'];
		$customerId = $_POST['customerId'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		if($reminderMonths == 1) {
			$reminderDate = date('Y-m-d', strtotime($reminderDate));
		} else {
			 $reminderDate = date('Y-m-d', strtotime("+".$reminderMonths."months", strtotime($reminderDate)));
		}
		if($reminderCallback == 1)
		{
			$db->execute("UPDATE ace_rp_customers set callback_date = NULL where id=".$customerId);
		}
		$query = "UPDATE ace_rp_orders set reminder_date='".$reminderDate."', reminder_type =".$reminderType.", reminder_note='".$remiderNote."',reminder_email=".$reminderEmailSend.", reminder_sms=".$reminderSmsSend.", reminder_month=".$reminderMonths." where id = ".$orderId;
		$result = $db->_execute($query);

		$updateUserInfo = "UPDATE ace_rp_customers set email='".$emailId."', cell_phone='".$smsNum."', callback_num ='".$callbackNum."' where id =".$customerId;
		$userResult = $db->_execute($updateUserInfo);
		if ($result) {
 			$response  = array("res" => "OK");
 			echo json_encode($response);
 			exit;
 		}
		exit();
	}

	/* Loki- Send reminder email to customers before 7 days
		Reminder types: 1 = always send mail
						2 = only one time
						3 = don't send mail
	*/
	function sendReminderEmail()
	{
		error_reporting(E_ALL);
		$maildate = date('Y-m-d', strtotime("+7 days"));
		$db 	  =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query 	  = "SELECT DISTINCT o.id, o.job_time_beg,o.job_date, o.job_time_end ,o.customer_id, o.order_type_id, o.reminder_type , o.reminder_date, o.job_date, o.reminder_month, o.order_number,c.email, c.first_name, c.last_name, ot.name as job_type, rel.is_sent from ace_rp_orders o LEFT JOIN ace_rp_customers c ON c.id = o.customer_id LEFT JOIN ace_rp_order_types ot ON ot.id= o.order_type_id LEFT JOIN ace_rp_reminder_email_log rel ON rel.order_id = o.id  WHERE o.reminder_date='".$maildate."'";
		$result = $db->_execute($query);
		$settings = $this->Setting->find(array('title'=>'email_template_jobnotification'));
		$template = $settings['Setting']['valuetxt'];
		//$template_subject = "Acecare Reminder Email for Job";
		$settings = $this->Setting->find(array('title'=>'email_template_jobnotification_subject'));
		$template_subject = $settings['Setting']['subject'];
		$currentDate = date('Y-m-d');

		while($row = mysql_fetch_array($result, MYSQL_BOTH)) {
				$url = $this->G_URL.BASE_URL."/pages/showReminderBookingPage?oid=".$row['id']."&cid=".$row['customer_id']."&otype=".$row['order_type_id']."&rdate=".$row['reminder_date']."&onum=".$row['order_number'];

				$link = '<a href='\.urlencode($url).\'>Book Now</a>';
				$msg = $template;
				$msg = str_replace('{first_name}', $row['first_name'], $msg);
				$msg = str_replace('{last_name}', $row['last_name'], $msg);
				$msg = str_replace('{job_type}','<b>'. $row['job_type'].'</b>', $msg);
				$msg = str_replace('{last_job_date}', date("d-M-Y",strtotime($row['job_date'])), $msg);
				$msg = str_replace('{url_confirm}', $link, $msg);
				$msg = str_replace("&nbsp;", nl2br("\n"), $msg);

			if($row['reminder_type'] != 3) 
			{
				$res = $this->sendEmailUsingMailgun($row['email'],$template_subject,$msg);
				if (strpos($res, '@acecare') !== false) {
	    			$is_sent = 1;
				} else {
					$is_sent = 0;
				}
				$message = mysql_real_escape_string($msg);
				$query1 = "INSERT INTO ace_rp_reminder_email_log (order_id, customer_id, job_type, sent_date, is_sent, message, message_id) values (".$row['id'].",". $row['customer_id'].",".$row['order_type_id'].",'".$currentDate."',".$is_sent.", '".$message."', '".$res."')";
				$result1 = $db->_execute($query1);
				// if($row['reminder_type'] == 1)
				// {
				// 	$reminderDate = date('Y-m-d', strtotime("+".$row['reminder_month']."months", strtotime($maildate)));
				// 	$setReminderDate = "UPDATE ace_rp_orders set reminder_date='".$reminderDate."' where id=".$row['id'];
				// 	$reminderRes = $db->_execute($setReminderDate);
				// }
			}
		}
		
		exit();
	}

	function reminderHotList()
	{
		// error_reporting(E_ALL);
		$limit = isset($_GET['limit']) ?$_GET['limit'] : 500;
		$currentDate = date('Y-m-d');
		$currentSelected = $_GET['currentSelected'];
		$selectedStr = $_GET['selectedStr'];
		$is_search = $_GET['is_search'];
		$this->set('is_search', $is_search);
		$campId = !empty($_GET['sq_str']) ? $_GET['sq_str'] : 0 ;
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		if($campId > 0)
		{
			$this->set('campId', $campId);
			$callWhere = ' AND ec.last_inserted_id ='.$campId.''; 
			$emptyEmailWhere = ' AND ec.last_inserted_id ='.$campId.'';
		} else {
			if($this->Common->getLoggedUserRoleID() == 6) 
			{
				$allCampList = $this->Lists->allCampaingList();
				$arrayString = implode(',', $allCampList);
				$callWhere = ' AND ec.last_inserted_id IN ('.$arrayString.')';
				$emptyEmailWhere = ' AND ec.last_inserted_id IN ('.$arrayString.')';
				//$telem_clause = ' AND c.campaign_id IN ('.$arrayString.')';
			} else {
				$allCampList = $this->Lists->AgentAllCampaingList($_SESSION['user']['id']);

				if(!empty($allCampList)) {
					$arrayString = implode(',', $allCampList);
				
					$callWhere = ' AND ec.last_inserted_id IN ('.$arrayString.')';
					$emptyEmailWhere = ' AND ec.last_inserted_id IN ('.$arrayString.')';
				}
			}
		}
		if($currentSelected == 1 || $currentSelected == 2)
		{
			if(!empty($selectedStr)) 
			{
				if($selectedStr == 'today')
				{
					$callWhere .= " AND o.reminder_date = CURDATE()"; 
				} else {
					$callWhere .= '';
				}
			} else {
				$callWhere .= ' AND o.reminder_date IS NOT NULL ';
			}
			if($currentSelected == 1) {
				$compare = 1;
			} elseif($currentSelected == 2) {
				$compare = 0;
			}
			$sql = "SELECT o.*, c.*, c.id AS cid,  (SELECT delivery_status FROM ace_rp_reminder_email_log WHERE customer_id = c.id ORDER BY id DESC 
				LIMIT 0 , 1) as is_sent FROM ace_rp_orders o LEFT JOIN ace_rp_customers c ON c.id = o.customer_id LEFT JOIN ace_rp_all_campaigns ec ON o.customer_id = ec.call_history_ids WHERE (SELECT delivery_status FROM ace_rp_reminder_email_log WHERE customer_id = c.id ORDER BY id DESC 
				LIMIT 0 , 1) =".$compare." ".$callWhere." limit ".$limit;
			$result = $db->_execute($sql);
			
			$countSql = "SELECT count(*) as total FROM ace_rp_orders o LEFT JOIN ace_rp_customers c ON c.id = o.customer_id LEFT JOIN ace_rp_all_campaigns ec ON o.customer_id = ec.call_history_ids WHERE o.reminder_date IS NOT NULL AND (SELECT delivery_status FROM ace_rp_reminder_email_log WHERE customer_id = c.id ORDER BY id DESC LIMIT 0 , 1) =".$compare." ".$callWhere;
			$resultTotal = $db->_execute($countSql);
			$rowTotal = mysql_fetch_array($resultTotal);
		} else if($currentSelected == 3 ) {
			$countSql = "SELECT count(DISTINCT o.id) as total FROM ace_rp_orders o LEFT JOIN ace_rp_customers c ON c.id = o.customer_id LEFT JOIN ace_rp_all_campaigns ec ON o.customer_id = ec.call_history_ids WHERE o.reminder_date IS NOT NULL ".$callWhere;
			$resultTotal = $db->_execute($countSql);
			$rowTotal = mysql_fetch_array($resultTotal);
			
			$sql = "SELECT o.*, c.*, c.id AS cid,  (SELECT delivery_status FROM ace_rp_reminder_email_log WHERE customer_id = cid ORDER BY id DESC 
				LIMIT 0 , 1) as is_sent FROM ace_rp_orders o LEFT JOIN ace_rp_customers c ON c.id = o.customer_id LEFT JOIN ace_rp_all_campaigns ec ON o.customer_id = ec.call_history_ids WHERE o.reminder_date IS NOT NULL ".$callWhere." group by o.id limit ".$limit;
			$result = $db->_execute($sql);
				//print_r($sql); die;
		} else {
			$countSql = "SELECT count(*) as total FROM ace_rp_orders o LEFT JOIN ace_rp_customers c ON c.id = o.customer_id LEFT JOIN ace_rp_all_campaigns ec ON o.customer_id = ec.call_history_ids WHERE c.email= ''".$emptyEmailWhere;
			$resultTotal = $db->_execute($countSql);
			$rowTotal = mysql_fetch_array($resultTotal, MYSQL_ASSOC);
			$sql = "SELECT o.*, o.id as order_id , c.* FROM ace_rp_orders o LEFT JOIN ace_rp_customers c ON c.id = o.customer_id LEFT JOIN ace_rp_all_campaigns ec ON o.customer_id = ec.call_history_ids WHERE c.email= ''".$emptyEmailWhere." limit ".$limit;	
			$result = $db->_execute($sql);
		}
		
		$totalCus = $rowTotal['total']; 
		$this->set('totalCus', $totalCus);
		$totalPages = ceil($totalCus / 500);
		$this->set('totalPages', $totalPages);
		$cust = array();
		$i=0;
		$cust_temp = array();
		while ($row = mysql_fetch_array($result, MYSQL_BOTH))
		{
			
			foreach ($row as $k => $v)
			$cust_temp['User'][$k] = $v;
			$cust_temp['User']['telemarketer_id']= $row['telemarketer_id'];
			$cust_temp['User']['callback_time']= date("H:i", strtotime($row['callback_time']));
			array_push($cust, $cust_temp);
			$i++;
		}
		$this->set('cust', $cust);
		$this->set('call_results', $this->HtmlAssist->table2array($this->CallResult->findAll(), 'id', 'name'));
		$this->render('search_list');
	}

	//LOki: Remove payment image

	function removePaymentImage()
	{
		$orderId = $_POST['oid'];
		$imgPath = $_POST['imgPath'];
		$rootPath = getcwd();
		unlink($rootPath.'/app/webroot/payment-images/'.$imgPath);
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "UPDATE ace_rp_orders set payment_image = NULL where id=".$orderId;
		$res = $db->_execute($query);
		if ($res) {
 			$response  = array("res" => "OK");
 			echo json_encode($response);
 			exit();
 		}
		exit();		
	}

	function removePurchaseImage()
	{
		$orderId = $_POST['oid'];
		$imgPath = $_POST['imgPath'];
		$imgNu = $_POST['imgNo'];
		$rootPath = getcwd();
		unlink($rootPath.'/upload_photos/'.$imgPath);
		if($imgNu == 1)
		{
			$pic = 'photo_1';
		}else{
			$pic = 'photo_2';
		}
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "UPDATE ace_rp_orders set ".$pic." = NULL where id=".$orderId;
		$res = $db->_execute($query);
		if ($res) {
 			$response  = array("res" => "OK");
 			echo json_encode($response);
 			exit();
 		}
		exit();		
	}
	function SendSms()
	{
		$phone_number = $_POST['phone'];
		$cusId = $_POST['cusId'];
		$message = $_POST['message'];
		exit();
	}

	function sendSeparateEmail()
	{
		$email   = $_POST['email'];
		$cusId 	 = $_POST['cusId'];
		$message = $_POST['message'];
		$subject = 'Pro Ace  Heating and AIR Conditioning';
		$db 	 =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$res = $this->sendEmailUsingMailgun($email,$subject,$message);
		$currentDate = date('Y-m-d');
		if (strpos($res, '@acecare') !== false) 
		{
	    	$is_sent = 1;
		} else 
		{
			$is_sent = 0;
		}
		$query = "INSERT INTO ace_rp_reminder_email_log (order_id, customer_id, job_type, sent_date, is_sent, message, message_id) values ('',".$cusId.",'','".$currentDate."',".$is_sent.",'".$message."', '".$res."')";
		$result = $db->_execute($query);
		if ($result) {
 			$response  = array("res" => "OK");
 			echo json_encode($response);
 			exit();
 		}
		exit();
	}
	// Loki: Set campId for sending emails
	function sendBulkEmails()
	{
		$campId  = $_POST['campId'];
		$db 	 =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query   = "INSERT INTO ace_rp_camp_email (camp_id,status) VALUES (".$campId.", 0)";
		$res 	 =	$db->_execute($query);
		if ($res) {
	 			$response  = array("res" => "OK");
	 			echo json_encode($response);
	 			exit();
	 		}
		exit();	
	}

	/* LOki: send mail to all campaign users
	 status 0 = not complete, 1 = queue, 2 = complete 
	 */ 	
	function sendMailToAll()
	{
		$db 	 		=& ConnectionManager::getDataSource($this->User->useDbConfig);
		$getCampIdSql 	= "SELECT camp_id,id from ace_rp_camp_email where status=0 limit 1";
		$result 		= $db->_execute($getCampIdSql);
		$campId 		= mysql_fetch_array($result, MYSQL_ASSOC);
		if(!empty($campId))
		{


			$updateStatusRunning = $db->_execute("UPDATE ace_rp_camp_email set status=1 where id=".$campId['id']."");
			$currentDate = date('Y-m-d');
			$settings = $this->Setting->find(array('title'=>'bulk_email'));
			$message = $settings['Setting']['valuetxt'];
			$subject = $settings['Setting']['subject'];
			$sql = "SELECT u2.email, u2.id AS cid, (SELECT id FROM ace_rp_orders WHERE customer_id = ec.call_history_ids 		ORDER BY id DESC LIMIT 0 , 1 ) AS order_Id FROM ace_rp_reference_campaigns o LEFT JOIN 						ace_rp_all_campaigns ec ON o.id = ec.last_inserted_id INNER JOIN ace_rp_customers u2 ON ec.call_history_ids = u2.id INNER JOIN ace_rp_orders ord ON ord.customer_id = ec.call_history_ids WHERE 
				u2.campaign_id IS NOT NULL AND ec.last_inserted_id = ".$campId['camp_id']." AND ec.show_default =0 AND u2.callresult NOT IN ( 7, 3 ) GROUP BY ord.customer_id";
			$res = $db->_execute($sql);

			while ($row = mysql_fetch_array($res, MYSQL_ASSOC))
			{
				$res1 = $this->sendEmailUsingMailgun($row['email'],$subject,$message);
				
				if (strpos($res1, '@acecare') !== false) 
				{
			    	$is_sent = 1;
				} else 
				{
					$is_sent = 0;
				}
				$query = "INSERT INTO ace_rp_reminder_email_log (order_id, customer_id, job_type, sent_date, is_sent, message, message_id) values ('',".$row['cid'].",'','".$currentDate."',".$is_sent.",'".$message."', '".$res1."')";
				$result = $db->_execute($query);
			}
			$updateStatusRunning = $db->_execute("UPDATE ace_rp_camp_email set status=2 where id=".$campId['id']."");
			echo "done";
		}
		exit();
	}

	function getStatusMailgun()
	{
		$logDate = date("Y-m-d");
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$getLink = "SELECT link from ace_rp_mail_link where log_date ='".$logDate."'";
		$linkRes = $db->_execute($getLink);
		$row = mysql_fetch_array($linkRes);
		$link = "";
		if(!empty($row['link']) || $row['link'] != '')
		{
			$link = $row['link'];
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://acecare.ca/acesystem2018/mailgun_status.php");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"link=".$link);
		// receive server response ...
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res = 	curl_exec ($ch);//exit;
		curl_close ($ch);		
		$data = json_decode($res);
		$nextLink = $data->paging->next;
		$explodeLink = explode("events/",$nextLink);
		
		foreach ($data->items  as $key => $value) {
			$msgConstant = 'message-id';
			$toEmail = $value->message->headers->to;
			$subject = $value->message->headers->subject;
			$messageId = $value->message->headers->$msgConstant;
			$delivery_status = 'delivery-status';
			$message = mysql_real_escape_string($value->$delivery_status->message);
			if(empty($message))
			{
				$message = mysql_real_escape_string($value->$delivery_status->description);
			}
			$query = "INSERT INTO ace_rp_failed_email (email, subject, reason,message_id) VALUES ('".$toEmail."', '".$subject."', '".$message."', '".$messageId."')";
			$res = $db->_execute($query);
		}
		
		if(!empty($link) || $link != '') 	
		{
			$insertLink = "UPDATE ace_rp_mail_link set link='".$explodeLink[1]."' where log_date='".$logDate."'";	
		} else {
			$insertLink = "INSERT INTO ace_rp_mail_link (log_date,link) VALUES ('".$logDate."', '".$explodeLink[1]."')";
		}
		$response = $db->_execute($insertLink);
		exit();
	}

	function showFailedEmail()
	{
		$no_of_records_per_page = 25;
		$pageNo = isset($this->params['url']['page_no']) ?$this->params['url']['page_no']: 1;
		$offset = ($pageNo-1) * $no_of_records_per_page;
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		// $query = "SELECT fe.*, cus.first_name, cus.last_name from ace_rp_failed_email fe LEFT JOIN ace_rp_customers cus ON fe.email = cus.email where fe.status = 0 LIMIT ".$offset.", ". $no_of_records_per_page."";
		$query = "SELECT fe.*, cus.first_name, cus.last_name from ace_rp_failed_email fe LEFT JOIN ace_rp_reminder_email_log rel ON rel.message_id = fe.message_id LEFT JOIN ace_rp_customers cus ON rel.customer_id = cus.id where fe.status = 0 LIMIT ".$offset.", ". $no_of_records_per_page."";
		$res = $db->_execute($query);
		$emails = array();
		// $totalQuery = "SELECT count(*) as total from ace_rp_failed_email fe LEFT JOIN ace_rp_customers cus ON fe.email = cus.email where fe.status = 0";
		$totalQuery = "SELECT count(*) as total from ace_rp_failed_email fe LEFT JOIN ace_rp_reminder_email_log rel ON rel.message_id = fe.message_id LEFT JOIN ace_rp_customers cus ON rel.customer_id = cus.id where fe.status = 0 ";
		$totalRes = $db->_execute($totalQuery);
		$row1 = mysql_fetch_array($totalRes, MYSQL_ASSOC);
		while ($row = mysql_fetch_array($res, MYSQL_ASSOC))
		{
			$emails[] = $row;
		}
		$totalPages = ($row1['total'] / $no_of_records_per_page);
		$this->set("emails", $emails);
		$this->set("totalPages", ceil($totalPages));
		$this->set("pageNo", $pageNo);
	}
	// Loki: Delete the email log
	function deleteEmailEntry()
	{
		$ids = $_POST['id'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$deleteQuery = "DELETE from ace_rp_failed_email where id IN (".$ids.")";
		$res = $db->_execute($deleteQuery);
		if ($res) {
	 			$response  = array("res" => "OK");
	 			echo json_encode($response);
	 			exit();
	 		}
	 	exit();
	}
	// Loki: Save the user mail response for booking.
	function saveUserResponse()
	{
		$orderId 		= $_POST['orderId'];
		$customerId 	= $_POST['customerId'];
		$order_num 		= $_POST['orderNum'];
		$workDate 		= $_POST['workDate'];
		$workTime 		= $_POST['workTime'];
		$Notes  		= $_POST["Notes"];
		$contactInfo	= $_POST['contactInfo'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "INSERT INTO ace_rp_user_booking_response (order_id, customer_id, order_num, work_date, work_time, notes, contactInfo) VALUES (".$orderId.", ".$customerId.", ".$order_num.", '".$workDate."','".$workTime."','".$Notes."',".$contactInfo.")";
		$result = $db->_execute($query);
		$this->redirect('/pages/thankYouPage');
	}

	function showUserResponse()
	{
		$no_of_records_per_page = 25;
		$pageNo = isset($this->params['url']['page_no']) ?$this->params['url']['page_no']: 1;
		$offset = ($pageNo-1) * $no_of_records_per_page;
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "SELECT ur.*, cus.first_name, cus.last_name, cus.email from ace_rp_user_booking_response ur LEFT JOIN ace_rp_customers cus ON ur.customer_id = cus.id group by ur.order_id LIMIT ".$offset.", ". $no_of_records_per_page." ";
		$res = $db->_execute($query);
		$users = array();
		$totalQuery = "SELECT count(DISTINCT ur.order_id) as total from ace_rp_user_booking_response ur LEFT JOIN ace_rp_customers cus ON ur.customer_id = cus.id ";
		$totalRes = $db->_execute($totalQuery);
		$row1 = mysql_fetch_array($totalRes, MYSQL_ASSOC);
		while ($row = mysql_fetch_array($res, MYSQL_ASSOC))
		{
			
			switch ($row['contactInfo']) {
				case '1':
					$row['contactInfo'] = 'Email';
					break;
				case '2':
					$row['contactInfo'] = 'Call';
					break;
				case '3':
					$row['contactInfo'] = 'Text';
					break;
			}
			$users[] = $row;
		}
		
		$totalPages = ( $row1['total'] / $no_of_records_per_page);
		$this->set("users", $users);
		$this->set("totalPages", ceil($totalPages));
		$this->set("pageNo", $pageNo);
	}

	// Loki: Delete the User booking response
	function deleteUserResponse()
	{
		$ids = $_POST['id'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$deleteQuery = "DELETE from  ace_rp_user_booking_response where id IN (".$ids.")";
		$res = $db->_execute($deleteQuery);
		if ($res) {
	 			$response  = array("res" => "OK");
	 			echo json_encode($response);
	 			exit();
	 		}
	 	exit();
	}

	function getMailEventMailgun()
	{
		// error_reporting(E_ALL);
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$getMessageId = "SELECT * from ace_rp_reminder_email_log where is_done =0 limit 50";
		$result = $db->_execute($getMessageId);
		
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$messageId = $row['message_id'];
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,"http://acecare.ca/acesystem2018/mailgun_message_data.php");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,"message_id=".$messageId);
			// receive server response ...
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$res = 	curl_exec ($ch);//exit;
			curl_close ($ch);		
			$data = json_decode($res);
			if(!empty($data))
			{
				$eventStatus = $data->items[0]->event;
				if($eventStatus == 'failed')
				{
					$deliveryStatus = 0;
				} else {
					$deliveryStatus = 1;
				}
				$db->_execute("UPDATE ace_rp_reminder_email_log set delivery_status = ".$deliveryStatus.", is_done=1 where id=".$row['id']."");
			}
		}
		exit();
	}

	// Get incoming mail data and save with their users.
	function getAllMailFromMailgun()
	{
		$body = mysql_real_escape_string($_POST['body-html']);
		$fromEmail = $_POST['sender'];
		$subject = $_POST['subject'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$mailData = "INSERT INTO ace_rp_customer_mail_response (email, subject, body, flag) VALUES ('".$fromEmail."', '".$subject."', '".$body."', 0)";
		$result = $db->_execute($mailData);
		exit();
	}
}
?>