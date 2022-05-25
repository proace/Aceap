<? ob_start();

class PaymentsController extends AppController
{
	//To avoid possible PHP4 problemfss
	var $name = "PaymentsController";
	var $G_URL  = "http://hvacproz.ca";
	// var $G_URL  = "localhost";
	var $uses = array('User');

	var $helpers = array('Time','Javascript','Common');
	var $components = array('HtmlAssist','RequestHandler','Common', 'Lists');
	var $itemsToShow = 20;
	var $pagesToDisplay = 10;
	
	function index()
	{
		$this->layout="list";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$payment_type = $_GET['payment_type'];
		$auth_search = $_GET['auth_search'];
		$amount_search = $_GET['amount_search'];
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		$payStatus = $_GET['payment_status'];
		if ($this->params['url']['ffromdate'] != '')
			$fdate = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
		else
			$fdate = date("Y-m-d", strtotime("-1 days"));

		if ($this->params['url']['ftodate'] != '')
			$tdate = date("Y-m-d", strtotime($this->params['url']['ftodate']));
		else
			$tdate = date("Y-m-d", strtotime("-1 days"));

		$sqlConditions = "";
		if($fdate != '')
			$sqlConditions .= " and o.job_date >= '".$this->Common->getMysqlDate($fdate)."'"; 
		if($tdate != '')
			$sqlConditions .= " and o.job_date <= '".$this->Common->getMysqlDate($tdate)."'";
		if ($payment_type != 1000 && $payment_type !=''){
			$sqlConditions .= " and exists (select * from ace_rp_payments p where p.idorder=o.id and p.payment_method='$payment_type')";
		}else if ($payment_type == 1000){
			$sqlConditions .= " and NOT EXISTS (select * from ace_rp_payments p where p.idorder=o.id)";
		}
		if($payStatus > 0){
			$sqlConditions .= " and cp.payment_status =".$payStatus;
		}
		if ($auth_search)
			$sqlConditions .= " and exists (select * from ace_rp_payments p where p.idorder=o.id and p.auth_number='$auth_search')";	
				
		if (!$order) $order = 'order_number asc';
		
		$query = "select o.id, o.order_number, o.job_date, o.order_status_id,o.job_technician1_id,o.job_technician2_id,
										o.booking_source_id, o.booking_source2_id,
										 s.name order_status, o.invoice_submitted,
										 concat(t1.first_name,' ',t1.last_name) tech1_name,
										 concat(t2.first_name,' ',t2.last_name) tech2_name,
										 concat(c.first_name,' ',c.last_name) client_name,
										 concat(t3.first_name,' ',t3.last_name) confirmed_by,
										 c.address_street client_address, c.phone client_phone,
										 o.order_type_id, o.order_status_id, jt.category_id,
										 jt.name job_type_name, o.customer_deposit,o.payment_image,
										 (SELECT SUM(p.paid_amount) 
										 FROM ace_rp_payments p 
										 WHERE p.idorder = o.id) payment,
										 (SELECT GROUP_CONCAT(DISTINCT pm.name, '') 
										 FROM ace_rp_payments p
										 LEFT JOIN ace_rp_payment_methods pm
										 ON p.payment_method = pm.id
										 WHERE p.idorder = o.id) method,
										 cp.payment_status,cp.card_num
							  from ace_rp_orders o
								left outer join ace_rp_order_types as jt on ( o.order_type_id = jt.id ) 
							  left outer join ace_rp_customers c on o.customer_id=c.id
							  left outer join ace_rp_users t1 on o.job_technician1_id=t1.id
							  left outer join ace_rp_users t2 on o.job_technician2_id=t2.id
							  left outer join ace_rp_users t3 on o.verified_by_id=t3.id
							  left outer join ace_rp_order_statuses s on s.id=o.order_status_id
							  LEFT JOIN ace_rp_creditcard_payment_details cp ON cp.order_id = o.id AND cp.id = (SELECT MAX(id) FROM ace_rp_creditcard_payment_details where order_id = o.id)
							 where order_status_id in (1,2,3,4,5) $sqlConditions 
							 order by $order $sort";
		// print_r($query); die;
		$query2 = "select id, name, price, quantity from ace_rp_order_items where 1=1 $sqlConditions order by $order $sort";
		
		$redo = array();
		$followup = array();
		$install = array();
		$other = array();
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			// $this->Common->printData($row);
			$totals = $this->Common->getOrderTotal($row['id']);
			
			$comm = $this->requestAction('/commissions/getForOrder/'.$row['id']);
            $tech1_comm = $comm[0][1]['total_comm'];
            $tech2_comm = $comm[0][2]['total_comm'];
     
            if ($row['booking_source_id'] == $row['job_technician1_id'])
                $tech1_comm += $comm[0][3]['total_comm'];
            elseif ($row['booking_source_id']==$row['job_technician2_id'])
                $tech2_comm += $comm[0][3]['total_comm'];
            elseif ($row['booking_source2_id']==$row['job_technician1_id'])
                $tech1_comm += $comm[0][4]['total_comm'];
            elseif ($row['booking_source2_id']==$row['job_technician2_id'])
                $tech2_comm += $comm[0][4]['total_comm'];
           
           $items[$row['id']]['tech1_comm'] = round($tech1_comm, 2);
           $items[$row['id']]['tech2_comm'] = round($tech2_comm, 2);
            // $this->set('tech2_comm', round($tech2_comm, 2));
            // $this->set('tech1_comm', round($tech1_comm, 2));


			if (($amount_search)&&($amount_search!=$totals['sum_total'])) continue;
			
			//$items[$row['id']]['total'] = $totals['sum_total'] - $row['customer_deposit'];
			$items[$row['id']]['total'] = $row['payment'] - $row['customer_deposit'];

			foreach ($row as $k => $v)
			  $items[$row['id']][$k] = $v;

			if ($row['order_type_id'] == 9) $redo[$row['order_status_id']][$row['id']] = 1;
			elseif ($row['order_type_id'] == 10) $followup[$row['order_status_id']][$row['id']] = 1;
			elseif ($row['category_id'] == 2) $install[$row['order_status_id']][$row['id']] = 1;
			else $other[$row['order_status_id']][$row['id']] = 1;

			$payments = array();
			$total_p = 0;

			$query = "select p.id, m.id payment_method_id, m.name payment_method,
											 p.paid_amount, p.payment_date, p.notes
									from ace_rp_payments p
									left outer join ace_rp_payment_methods m on m.id=p.payment_method
								 where payment_type=2 and p.idorder='{$row['id']}'";
			$result_p = $db->_execute($query);
			while($row_p = mysql_fetch_array($result_p, MYSQL_ASSOC))
			{
				foreach ($row_p as $k => $v)
					$payments[$row_p['id']][$k] = $v;
					
				$total_p += 1*$row_p['paid_amount'];
			}
			
			$items[$row['id']]['payments'] = $payments;
			$items[$row['id']]['total_payments'] = $total_p;

			$payments = '';
			$tech_auth = '';
			$div = '';
			$query = "select p.id, m.id payment_method_id, m.name payment_method,
											 p.paid_amount, p.auth_number, p.payment_date
									from ace_rp_payments p
									left outer join ace_rp_payment_methods m on m.id=p.payment_method
								 where payment_type=1 and p.idorder='{$row['id']}'";
			$result_p = $db->_execute($query);
			while($row_p = mysql_fetch_array($result_p, MYSQL_ASSOC))
			{
				$payments .= $div.$row_p['payment_method'];
				$tech_auth .= $div.$row_p['auth_number'];
				$div = '<br/>';
			}
			$items[$row['id']]['tech_payments'] = $payments;
			$items[$row['id']]['tech_auth'] = $tech_auth;
		}
		
		$this->set('items', $items);
		$this->set('sort', $sort);
		$this->set('order', $order);
		$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));
		$this->set('next_fdate', date("d M Y", strtotime($fdate) + 24*60*60));
		$this->set('prev_tdate', date("d M Y", strtotime($tdate) - 24*60*60));
		$this->set('next_tdate', date("d M Y", strtotime($tdate) + 24*60*60));
		$this->set('fdate', date("d M Y", strtotime($fdate)));
		$this->set('tdate', date("d M Y", strtotime($tdate)));
		$this->set('payment_type', $payment_type);
		$this->set('job_type', $job_type);
		$this->set('payStatus', $payStatus);
		$this->set('allPaymentTypes', $this->Lists->ListTable('ace_rp_payment_methods'));
		$this->set('allJobTypes', $this->Lists->ListTable('ace_rp_order_types'));
	
		$redo = array('booked' => count($redo[1]),'done' => count($redo[5]),'canceled' => count($redo[3]));
		$followup = array('booked' => count($followup[1]),'done' => count($followup[5]),'canceled' => count($followup[3]));
		$install = array('booked' => count($install[1]),'done' => count($install[5]),'canceled' => count($install[3]));
		$other = array('booked' => count($other[1]),'done' => count($other[5]),'canceled' => count($other[3]));

		$this->set('redo', $redo);
		$this->set('followup', $followup);
		$this->set('install', $install);
		$this->set('other', $other);
	}

	function deletePayment()
	{
		$id = $_REQUEST['payment_id'];
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "delete from ace_rp_payments where id=$id";
		$db->_execute($query);
		
		echo 'ok';
		exit;
	}

	function savePayment()
	{
		$jobNotes = isset($_REQUEST['jobNotes']) ? $_REQUEST['jobNotes'] : '';
		$email = $_REQUEST['email'];
		$send_receipt = $_REQUEST['sendReceipt'];
		$sendReviewEmail = $_REQUEST['sendReviewEmail'];
		$order_id = $_REQUEST['order_id'];
		$orderNum = $_REQUEST['orderNum'];
		$customerId = $_REQUEST['customerId'];
		$method = $_REQUEST['method'];
		$amount = $_REQUEST['amount'];
		$payment_type = $_REQUEST['payment_type'];
		$show_message = $_REQUEST['show_message'];
		$userRole = $this->Common->getLoggedUserRoleID();
		$note = $_REQUEST['notes'];
		$file 	= isset($_FILES['payment_image'])? $_FILES['payment_image'] : null;
		$loggedUserId 	= $this->Common->getLoggedUserID();
		$anchor = '<a class="open-payment-page" orderId='.$order_id.' data-url="'.BASE_URL.'/orders/editBooking?order_id='.$order_id.'&rurl=orders%2FscheduleView%3F" style="cursor: pointer;color: blue;">'.$orderNum.'</a>';
		$message = 'Please collect payment for '.$anchor;
		$toDate = date('Y-m-d');
		$fromDate = date('Y-m-d H:i:s');
		$photoImage1 = $_FILES['sortpic1'];
		if($file !== null)
		{
			
            $this->User->id = $loggedUserId;
			$imageResult 	= $this->Common->commonSavePaymentImage($file, $order_id , $config = $this->User->useDbConfig);
		}
		 if(!empty($photoImage1))
        {    
            foreach ($photoImage1['name'] as $key => $value) {
                if(!empty($value)){
                    $imageResult = $this->Common->uploadPhoto($value,$photoImage1['tmp_name'][$key] , $order_id , $config = $this->User->useDbConfig, 1, $customerId,1);
                }
             }  
        }
		//$date = date("Y-m-d", strtotime($_REQUEST['date']));
		$date = date("Y-m-d");
		$creator = $this->Common->getLoggedUserID();
		
		$isDialer 	= isset($_POST['from_dialer'])?$_POST['from_dialer'] :0;

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		//$payment_date = date("Y-m-d", strtotime($dat['payment_date']));
		$query_order_up = "UPDATE `ace_rp_orders` as `arc` set `arc`.`paid_by` =".$method."  WHERE arc.id=".$order_id."";
		$up_order = $db->_execute($query_order_up);

		$query="select * from ace_rp_payments where idorder='".$order_id."'";
		$result = $db->_execute($query);
        $row =mysql_num_rows($result);
      if($row == 0 || $row ==''){
		$query = "INSERT INTO ace_rp_payments
								(idorder, creator, payment_method, payment_date, paid_amount, payment_type, notes) 
							VALUES ($order_id, '$creator', '$method', '$date', '$amount', '$payment_type', '$note')";
		}
		else{ 
			$query = "UPDATE  ace_rp_payments set creator ='".$creator."',payment_method='".$method."',payment_date='".$date."' ,paid_amount='".$amount."',payment_type='".$payment_type."', notes='".$note."' where idorder='".$order_id."'";	
		}				
		$res = $db->_execute($query);
		/*if($res == 1 && $show_message == 1) 
		{
			$query = "SELECT id from ace_rp_users where role_id = 6";
			$res = $db->_execute($query);
			$query = "INSERT INTO ace_rp_messages (txt,state,to_user,from_user,to_date,from_date)";
			$i=1;
			while($row = mysql_fetch_array($res, MYSQL_ASSOC))
			{
				// // Send message to all office role user's.
				if($i == 1){
					$values .= " VALUES ('".$message."',0,".$row['id'].", ".$loggedUserId.", '".$toDate."','".$fromDate."')"; 	
				} else {
					$values .= ", ('".$message."',0,".$row['id'].", ".$loggedUserId.", '".$toDate."','".$fromDate."')"; 
				}
				$i++;
				
			}
			
			$query = $query.$values;
			$db->_execute($query);

		}*/
		if($send_receipt == 1){
			$orgFile = null;
			$currentDate = date('Y-m-d');
			$subject = "Payment Receipt";
			$msg = '<p>Hi!</p><p>Please find attached payment receipt.</p>';
			$orderDetails = $this->Common->getOrderDetails($order_id, $this->User->useDbConfig);
			if(!empty($orderDetails['payment_image']) || $orderDetails['payment_image'] != '')
			{
				
				$orgFile = $this->G_URL."/acesys/app/webroot/payment-images/".$orderDetails['payment_image'];
				// $orgFile = $this->G_URL."/acesys/app/webroot/payment-images/1570119825_image.jpg";
				$res = $this->Common->sendEmailMailgun($email,$subject,$msg,null,$orgFile);
				if (strpos($res, '@acecare') !== false) 
				{
					$is_sent = 1;
				} else 
				{
					$is_sent = 0;
				}
				$query = "INSERT INTO ace_rp_reminder_email_log (order_id, customer_id, job_type, sent_date, is_sent, message, message_id,subject) values (".$order_id.",'','','".$currentDate."',".$is_sent.",'".$msg."', '".$res."','".$subject."')";
				$result = $db->_execute($query);	
			}
		}
	
		echo 'ok';
		exit;
	}
	function savePaymentImg()
	{
		$order_id = $_REQUEST['order_id'];
		$file 	= isset($_FILES['payment_image'])? $_FILES['payment_image'] : null;
		if($file !== null)
		{
			$loggedUserId 	= $this->Common->getLoggedUserID();
            $this->User->id = $loggedUserId;
			$imageResult 	= $this->Common->commonSavePaymentImage($file, $order_id , $config = $this->User->useDbConfig);
		}
		echo 'ok';
		exit;
	}

	function invoiceSubmitted()
	{
		$order_id = $_REQUEST['order_id'];
		$flag = $_REQUEST['flag'];

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "update ace_rp_orders set invoice_submitted=$flag where id=$order_id";
		$db->_execute($query);
		
		echo 'ok';
		exit;
	}
	
	function ManagerView()
	{
		$this->layout="list";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		if ($this->params['url']['ffromdate'] != '')
			$fdate = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
		else
			$fdate = date("Y-m-d");

		if ($this->params['url']['ftodate'] != '')
			$tdate = date("Y-m-d", strtotime($this->params['url']['ftodate']));
		else
			$tdate = date("Y-m-d");

		$sqlConditions = "";
		if($fdate != '')
			$sqlConditions .= " and o.job_date >= '".$this->Common->getMysqlDate($fdate)."'"; 
		if($tdate != '')
			$sqlConditions .= " and o.job_date <= '".$this->Common->getMysqlDate($tdate)."'";

		if (!$order) $order = 'order_number asc';
		
		$query = "select o.id, o.order_type_id, o.order_status_id, jt.category_id
							  from ace_rp_orders o
								left outer join ace_rp_order_types as jt on ( o.order_type_id = jt.id ) 
							 where 1=1 $sqlConditions";
		
		$redo = array();
		$followup = array();
		$install = array();
		$other = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			if ($row['order_type_id'] == 9) $redo[$row['order_status_id']][$row['id']] = 1;
			elseif ($row['order_type_id'] == 10) $followup[$row['order_status_id']][$row['id']] = 1;
			elseif ($row['category_id'] == 2) $install[$row['order_status_id']][$row['id']] = 1;
			else $other[$row['order_status_id']][$row['id']] = 1;
		}
	
		$redo = array('booked' => count($redo[1]),'done' => count($redo[5]),'canceled' => count($redo[3]));
		$followup = array('booked' => count($followup[1]),'done' => count($followup[5]),'canceled' => count($followup[3]));
		$install = array('booked' => count($install[1]),'done' => count($install[5]),'canceled' => count($install[3]));
		$other = array('booked' => count($other[1]),'done' => count($other[5]),'canceled' => count($other[3]));
		
		$query = "select m.id payment_method_id, m.name payment_method, 
										 sum(if (p.payment_type=1,1,0)) count_tech,
										 sum(if (p.payment_type=1,p.paid_amount,0)) paid_amount_tech,
										 sum(if (p.payment_type=2,p.paid_amount,0)) paid_amount_acc,
										 sum(if (p.payment_type=2,1,0)) count_acc
							  from ace_rp_payments p
							  left outer join ace_rp_payment_methods m on m.id=p.payment_method
						 	  left outer join ace_rp_orders o on p.idorder=o.id
							 where 1=1 $sqlConditions 
							 group by m.id, m.name
							 order by $order $sort";
		
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $items[$row['payment_method_id']][$k] = $v;
		}
		
		$this->set('items', $items);
		$this->set('sort', $sort);
		$this->set('order', $order);
		$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));
		$this->set('next_fdate', date("d M Y", strtotime($fdate) + 24*60*60));
		$this->set('prev_tdate', date("d M Y", strtotime($tdate) - 24*60*60));
		$this->set('next_tdate', date("d M Y", strtotime($tdate) + 24*60*60));
		$this->set('fdate', date("d M Y", strtotime($fdate)));
		$this->set('tdate', date("d M Y", strtotime($tdate)));
		$this->set('payment_type', $payment_type);
		$this->set('allPaymentTypes', $this->Lists->ListTable('ace_rp_payment_methods'));

		$this->set('redo', $redo);
		$this->set('followup', $followup);
		$this->set('install', $install);
		$this->set('other', $other);
	}
	
	function techPaymentsForJob(){
		$order_id = $_REQUEST['order_id'];
		if (isset($_REQUEST['actions'])) $actions = $_REQUEST['actions'];
		else $actions = 1;
		
		$res = '<table>';
		$total_p = 0;
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "select p.id, m.id payment_method_id, m.name payment_method,
										 p.paid_amount, p.payment_date, p.auth_number, m.color
								from ace_rp_payments p
								left outer join ace_rp_payment_methods m on m.id=p.payment_method
							 where p.idorder='$order_id'";
		$result_p = $db->_execute($query);
		while($row_p = mysql_fetch_array($result_p, MYSQL_ASSOC))
		{
			$total_p += 1*$row_p['paid_amount'];
			
			$res .= "<tr>\n";
			$res .= "<td style='color:#{$row_p['color']}'>&nbsp;{$row_p['payment_method']}</td>";
			$res .= "<td>".$this->HtmlAssist->prPrice($row_p['paid_amount'])."</td>";
			// $res .= "<td>Auth.#;{$row_p['auth_number']}</td>";
			if ($actions) {
				//$res .= "<td style='width:40px'><a href='#' style='color:blue' onclick='ErasePayment({$row_p['id']});'>Delete</a></td>";
				$res .= "<td style='width:40px'><input type='button' value='X' title='Delete this payment' class='delete_payment' onclick='ErasePayment({$row_p['id']});' /></td>";
			}
			$res .= "</tr>\n";
		}
		$res .= "<tr><td><b>Total paid:&nbsp;</b></td><td><input type='hidden' id='total_paid' value='$total_p'/>".$this->HtmlAssist->prPrice($total_p)."</td><td>&nbsp;</td></tr>";
		
		$res .= "<table>";
		echo $res;
		exit;
	}
	
	function paymentTransactions(){
		$payment_method_id = $_REQUEST['payment_method_id'];
		$payment_type_id = $_REQUEST['payment_type_id'];
		$job_fdate = $_REQUEST['job_fdate'];
		$job_tdate = $_REQUEST['job_tdate'];
		$job_date_conditions = "";
		// if ($job_fdate) $job_date_conditions = " and o.job_date='".date("Y-m-d",strtotime($job_date))."'";
		if ($job_fdate) $job_date_conditions = " and (o.job_date BETWEEN '". date('Y-m-d',strtotime($job_fdate))."' AND '". date('Y-m-d',strtotime($job_tdate))."')";
		
		$res = '<table>';
		$total_p = 0;
		$allTechnicians = $this->Lists->Technicians();
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "select o.id, o.order_number, p.paid_amount,
						 o.job_technician1_id, o.job_technician2_id
		            from ace_rp_payments p, ace_rp_orders o
				   where o.id=p.idorder and p.payment_method='$payment_method_id'
					 and p.payment_type=$payment_type_id and o.order_status_id=5 $job_date_conditions";
		$result_p = $db->_execute($query);
		while($row_p = mysql_fetch_array($result_p, MYSQL_ASSOC))
		{
			$res .= "<tr>\n";
			$res .= "<td><a href='".BASE_URL."/orders/editBooking?order_id=".$row_p['id']."'><b>REF #:</b>&nbsp;{$row_p['order_number']}</a></td>";
			$res .= "<td><b>tech:</b>&nbsp;{$allTechnicians[$row_p['job_technician1_id']]},</td>";
			$res .= "<td>&nbsp;{$allTechnicians[$row_p['job_technician2_id']]}</td>";
			$res .= "<td><b>amount paid:</b>&nbsp;{$row_p['paid_amount']}</td>";
			$res .= "</tr>\n";
		}
		
		$res .= "<table>";
		echo $res;
		exit;
	}

	// Loki Show payment methods
	function showPaymentTypes() 
	{
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "SELECT * from ace_rp_payment_methods";
		
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $items[$row['id']][$k] = $v;
		}
		$this->set('paymentMethods', $items);
	}

	function deletePaymentType()
	{

		$data = $_POST['typeIds'];
		$ids = implode(',', $data);
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "DELETE from  ace_rp_payment_methods WHERE id IN (".$ids.")";
		$result = $db->_execute($query);
		exit();
	}

	function changePictureActive()
	{
		$jobTypeId = $_GET['jobtype_id'];
		$isActive = $_GET['is_active'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "UPDATE ace_rp_payment_methods set show_picture = ".$isActive." WHERE id=".$jobTypeId."";
		$result = $db->_execute($query);
		exit();
	}
	function changePaymentActive()
	{
		$jobTypeId = $_GET['jobtype_id'];
		$isActive = $_GET['is_active'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "UPDATE ace_rp_payment_methods set show_payment = ".$isActive." WHERE id=".$jobTypeId."";
		$result = $db->_execute($query);
		exit();
	}
	function changeMessageActive()
	{
		$jobTypeId = $_GET['jobtype_id'];
		$isActive = $_GET['is_active'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "UPDATE ace_rp_payment_methods set message_to_office = ".$isActive." WHERE id=".$jobTypeId."";
		$result = $db->_execute($query);
		exit();
	}
	function changePaymentTypeActive()
	{
		$jobTypeId = $_GET['jobtype_id'];
		$isActive = $_GET['is_active'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "UPDATE ace_rp_payment_methods set show_method = ".$isActive." WHERE id=".$jobTypeId."";
		$result = $db->_execute($query);
		exit();
	}
	function changeTechPaymentType()
	{
		$jobTypeId = $_GET['jobtype_id'];
		$isActive = $_GET['is_active'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "UPDATE ace_rp_payment_methods set show_tech_method = ".$isActive." WHERE id=".$jobTypeId."";
		$result = $db->_execute($query);
		exit();
	}
	// Loki : show the add payment page. Don't remov this function
	function showAddPaymentTypePage()
	{

	}
	function addPaymentType()
	{
		$paymentTypeName = $_POST['typeName'];
		$isActive = $_POST['isActive'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "INSERT INTO ace_rp_payment_methods (name,show_picture) values ('".$paymentTypeName."',".$isActive.")";
		$result = $db->_execute($query);
		$this->redirect('payments/showPaymentTypes');
		exit();
	}

	function updateDeposit()
	{
		$deposit = $_POST['deposit'];
		$orderId = $_POST['orderId'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "UPDATE ace_rp_orders set customer_deposit = ".$deposit." where id = ".$orderId;
		$result = $db->_execute($query);
		exit();
	}

	//Loki: Get payment methods for purchase items invoice.
	function ShowPaymentMethod()
	{
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "SELECT * from ace_rp_purchase_payment_method";

		$methods = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $methods[$row['id']][$k] = $v;
		}
		$this->set('paymentMethods', $methods);
	}

	function editPaymentMethod($id)
	{	
		$db =& ConnectionManager::getDataSource('default');	
		$category = array();
		if($id) {
			$query = "SELECT * from ace_rp_purchase_payment_method where id=".$id;
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
		} else {
			$row = array("id"=>"", "name"=>"");
		}
		$this->set('methods', $row);
	}
	
	function editPaymentType($id)
	{	
		$db =& ConnectionManager::getDataSource('default');	
		$category = array();
		if($id) {
			$query = "SELECT * from ace_rp_payment_methods where id=".$id;
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
		} else {
			$row = array("id"=>"", "name"=>"");
		}
		$this->set('methods', $row);
	}

	function updatePaymentMethod()
	{
		$id = $_POST['methodId'];
		$name = $_POST['methodName'];
		$db =& ConnectionManager::getDataSource('default');
		if(!empty($id))
		{
			$query = "UPDATE ace_rp_purchase_payment_method set name ='".$name."' WHERE id=".$id;
			$result = $db->_execute($query);			
		} else {
			$query = "INSERT INTO ace_rp_purchase_payment_method (name) VALUES ('".$name."')";
			$result = $db->_execute($query);		
		}
		exit();
	}

	function updatePaymentType(){
		$id = $_POST['methodId'];
		$name = $_POST['methodName'];
		$color = !empty($_POST['color']) ? $_POST['color'] : NULL;
		$db =& ConnectionManager::getDataSource('default');
		if(!empty($id))
		{
			$query = "UPDATE ace_rp_payment_methods set name ='".$name."', color = '".$color."' WHERE id=".$id;
			$result = $db->_execute($query);			
		} 
		// else {
		// 	$query = "INSERT INTO ace_rp_payment_methods (name) VALUES ('".$name."')";
		// 	$result = $db->_execute($query);		
		// }
		exit();
	}

	function deletePaymentMethod()
	{
			$data = $_POST['typeIds'];
			$ids = implode(',', $data);
			$db =& ConnectionManager::getDataSource('default');
			$query = "DELETE from  ace_rp_purchase_payment_method WHERE id IN (".$ids.")";
			$result = $db->_execute($query);
			exit();
	}
	//Loki: Save invoice paid details.
	function addInvoicePayment()
	{		
		$jobRefnum 	= ($_POST['refNum'] !='undefined') ? $_POST['refNum'] : 0 ;
		$invoiceNum = $_POST['invoiceNum'];
		$invoiceIds = explode (",", $_POST['invoiceIds']);
		$methodId 	= $_POST['methodId'];
		$payDate 	= $_POST['payDate'];
		$notes 		= $_POST['notes'];
		$agentId 	= $_POST['agentId'];
		$paidAmount = $_POST['paidAmount'];
		$statusId 	= $_POST['statusId'];
		$remainingAmount = $_POST['remainingAmount'];
		$totalRemainingAmount = ($remainingAmount - $paidAmount);
		$photoImage = isset($_FILES['fileval'])? $_FILES['fileval'] : null;
		$imageName = '';
		
		if ($payDate != '' || !empty($payDate))
		{
			$payDate = date("Y-m-d", strtotime($payDate));
		}
		else{
			$payDate = date("Y-m-d");
		}
		if(!empty($photoImage['name']))
        {       
            $path = $photoImage['name'];
			$ext = pathinfo($path, PATHINFO_EXTENSION);
			$imageName = date('Ymdhis', time()).'_'.$path.'.'.$ext;
			if ( 0 < $file['error'] ) {
        		echo 'Error: ' . $_FILES['image']['error'] . '<br>'; 
		    } else {
		        move_uploaded_file($photoImage['tmp_name'], ROOT."/app/webroot/purchase-invoice-images/".$imageName);
		    }
        }
		$db =& ConnectionManager::getDataSource('default');

		foreach ($invoiceIds as $invoiceId) {
			$query = "INSERT INTO ace_iv_invoice_history (invoice_id, status_id, pay_date, payment_method, paid_amount,paid_by,notes, invoice_image,job_ref_num,invoice_num) VALUES (".$invoiceId.",".$statusId.",'".$payDate."',".$methodId.", ".$paidAmount.",".$agentId.",'".$notes."', '".$imageName."','".$jobRefnum."','".$invoiceNum."')";
			$result = $db->_execute($query);
			if($result){
				$db->_execute("UPDATE ace_iv_invoice set remaining_amount = ".$totalRemainingAmount.", status_id=".$statusId." where invoice_id =".$invoiceId);
			}
		}
		$response  = array("res" => "1");
	    echo json_encode($response);
		exit();
	}

	//Loki: Save invoice refund details.
	function addRefundInvoiceDetails()
	{
		$invoiceIds = $_POST['invoiceIds'];
		$methodId = $_POST['methodId'];
		$payDate = $_POST['payDate'];
		$notes = $_POST['notes'];
		$agentId = $_POST['agentId'];
		$refundInvoiceNo = $_POST['refundInvoiceNo'];
		$refundTime = $_POST['refundTime'];
		$supplierRep = $_POST['supplierRep'];
		$statusId 	= $_POST['statusId'];
		$RefundAmount = $_POST['RefundAmount'];
		$items 		= json_decode(stripslashes($_POST['items']),true);
		$remainingAmount = $_POST['remainingAmount'];
		$refundTaxAmount = $RefundAmount * .12;
		$totalRemainingAmount = $remainingAmount - ($RefundAmount + $refundTaxAmount);
		$photoImage = isset($_FILES['fileval'])? $_FILES['fileval'] : null;
		$imageName = '';
		
		if ($payDate != '' || !empty($payDate))
		{
			$payDate = date("Y-m-d", strtotime($payDate));
		}
		else{
			$payDate = date("Y-m-d");
		}

		if(!empty($photoImage['name']))
        {       
            $path = $photoImage['name'];
			$ext = pathinfo($path, PATHINFO_EXTENSION);
			$imageName = date('Ymdhis', time()).'_'.$path.'.'.$ext;
			if ( 0 < $file['error'] ) {
        		echo 'Error: ' . $_FILES['image']['error'] . '<br>'; 
		    } else {
		        move_uploaded_file($photoImage['tmp_name'], ROOT."/app/webroot/purchase-invoice-images/".$imageName);
		    }
        }

		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO ace_iv_invoice_history (invoice_id, status_id, pay_date, payment_method,returned_by,notes, refund_time, supplier_rep, refund_invoice_id, refund_amount , invoice_image) VALUES (".$invoiceIds.",".$statusId.",'".$payDate."',".$methodId.",".$agentId.",'".$notes."', '".$refundTime."', '".$supplierRep."', '".$refundInvoiceNo."',".$RefundAmount." , '".$imageName."')";
		$result = $db->_execute($query);
		
		$invoiceHistoryId = $db->lastInsertId();
	
		if(!empty($items))
		{ 
			foreach ($items as $key => $value) {
				$db->_execute("INSERT INTO ace_iv_invoice_refund_items (Invoice_id, invoice_history_id,item_id,quantity) VALUES (".$invoiceIds.",".$invoiceHistoryId.", ".$key.",".$value.")");
			}
		}
		if($result){
			$db->_execute("UPDATE ace_iv_invoice set remaining_amount = ".$totalRemainingAmount." where invoice_id =".$invoiceIds);
			 $response  = array("res" => "1");
             echo json_encode($response);
		}
		exit();
	}

	//Loki: Save invoice refund and pay details.
	function addRefundPayInvoiceDetails()
	{
		$invoiceIds = $_POST['invoiceIds'];
		$methodId = $_POST['methodId'];
		$payDate = $_POST['payDate'];
		$notes = $_POST['notes'];
		$refPayRefundBy = $_POST['refPayRefundBy'];
		$refundInvoiceNo = $_POST['refundInvoiceNo'];
		$refundTime = $_POST['refundTime'];
		$supplierRep = $_POST['supplierRep'];
		$refPayAmount = $_POST['refPayAmount'];
		$refPayAgent = $_POST['refPayAgent'];
		$statusId 	= $_POST['statusId'];
		$items 		= json_decode(stripslashes($_POST['items']),true);
		$RefundAmount = $_POST['RefundAmount'];
		$remainingAmount = $_POST['remainingAmount'];
		$refundTaxAmount = $RefundAmount * .12;
		$totalRemainingAmount = ($remainingAmount - (($RefundAmount +$refundTaxAmount) + $refPayAmount));
		$photoImage = isset($_FILES['fileval'])? $_FILES['fileval'] : null;
		$imageName = '';

		if ($payDate != '' || !empty($payDate))
		{
			$payDate = date("Y-m-d", strtotime($payDate));
		}
		else{
			$payDate = date("Y-m-d");
		}
		if(!empty($photoImage['name']))
        {       
            $path = $photoImage['name'];
			$ext = pathinfo($path, PATHINFO_EXTENSION);
			$imageName = date('Ymdhis', time()).'_'.$path.'.'.$ext;
			if ( 0 < $file['error'] ) {
        		echo 'Error: ' . $_FILES['image']['error'] . '<br>'; 
		    } else {
		        move_uploaded_file($photoImage['tmp_name'], ROOT."/app/webroot/purchase-invoice-images/".$imageName);
		    }
        }

		$db =& ConnectionManager::getDataSource('default');
		
		$query = "INSERT INTO ace_iv_invoice_history (invoice_id, status_id, pay_date, payment_method, paid_amount,returned_by,notes, refund_time, supplier_rep, refund_invoice_id, paid_by,refund_amount , invoice_image) VALUES (".$invoiceIds.",".$statusId.",'".$payDate."',".$methodId.", ".$refPayAmount.",".$refPayRefundBy.",'".$notes."', '".$refundTime."', '".$supplierRep."', ".$refundInvoiceNo.",".$refPayAgent.", ".$RefundAmount." , '".$imageName."')";
		$result = $db->_execute($query);

		$invoiceHistoryId = $db->lastInsertId();
	
		if(!empty($items))
		{
			foreach ($items as $key => $value) {
				$db->_execute("INSERT INTO ace_iv_invoice_refund_items (Invoice_id, invoice_history_id,item_id,quantity) VALUES (".$invoiceIds.",".$invoiceHistoryId.", ".$key.",".$value.")");
			}
		}
		if($result){
			$db->_execute("UPDATE ace_iv_invoice set remaining_amount = ".$totalRemainingAmount." where invoice_id =".$invoiceIds);
			 $response  = array("res" => "1");
             echo json_encode($response);
		}
		exit();
	}
	//Loki: Save invoice credit amount details.
	function addCreditPayment()
	{
		$invoiceIds = $_POST['invoiceIds'];
		$creditMethod = $_POST['creditMethod'];
		$creditDate = $_POST['creditDate'];
		$note = $_POST['note'];
		$creditAmount = $_POST['creditAmount'];
		$statusId 	= $_POST['statusId'];
		$remainingAmount = $_POST['remainingAmount'];
		$totalRemainingAmount = ($remainingAmount + $creditAmount);
		$photoImage = isset($_FILES['fileval'])? $_FILES['fileval'] : null;
		$imageName = '';

		if ($payDate != '' || !empty($payDate))
		{
			$payDate = date("Y-m-d", strtotime($payDate));
		}
		else{
			$payDate = date("Y-m-d");
		}
		if(!empty($photoImage['name']))
        {       
            $path = $photoImage['name'];
			$ext = pathinfo($path, PATHINFO_EXTENSION);
			$imageName = date('Ymdhis', time()).'_'.$path.'.'.$ext;
			if ( 0 < $file['error'] ) {
        		echo 'Error: ' . $_FILES['image']['error'] . '<br>'; 
		    } else {
		        move_uploaded_file($photoImage['tmp_name'], ROOT."/app/webroot/purchase-invoice-images/".$imageName);
		    }
        }

		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO ace_iv_invoice_history (invoice_id, status_id, pay_date, credit_method, paid_amount,notes , invoice_image) VALUES (".$invoiceIds.",".$statusId.",'".$creditDate."','".$creditMethod."', ".$creditAmount.",'".$note."' , '".$imageName."' )";
		$result = $db->_execute($query);
	
		if($result){
			$db->_execute("UPDATE ace_iv_invoice set remaining_amount = ".$totalRemainingAmount." where invoice_id =".$invoiceIds);
			 $response  = array("res" => "1");
             echo json_encode($response);
		}
		exit();
	}

	function deletePartImage()
	{
		$id = $_POST['id'];
        $imgPath = $_POST['imgPath'];

		$res = $this->Common->deletePurchasePartImage($id,$imgPath);
		 if ($res) {
            $response  = array("res" => "OK");
            echo json_encode($response);
            exit();
        }
        exit();
	}

}
?>
