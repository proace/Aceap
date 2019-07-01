<?
error_reporting(E_PARSE  ^ E_ERROR );
class PaymentsController extends AppController
{
	//To avoid possible PHP4 problemfss
	var $name = "PaymentsController";

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
		if ($payment_type)
			$sqlConditions .= " and exists (select * from ace_rp_payments p where p.idorder=o.id and p.payment_method='$payment_type')";
		if ($auth_search)
			$sqlConditions .= " and exists (select * from ace_rp_payments p where p.idorder=o.id and p.auth_number='$auth_search')";	
				
		if (!$order) $order = 'order_number asc';
		
		$query = "select o.id, o.order_number, o.job_date, o.order_status_id,
										 s.name order_status, o.invoice_submitted,
										 concat(t1.first_name,' ',t1.last_name) tech1_name,
										 concat(t2.first_name,' ',t2.last_name) tech2_name,
										 concat(c.first_name,' ',c.last_name) client_name,
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
										 WHERE p.idorder = o.id) method
							  from ace_rp_orders o
								left outer join ace_rp_order_types as jt on ( o.order_type_id = jt.id ) 
							  left outer join ace_rp_customers c on o.customer_id=c.id
							  left outer join ace_rp_users t1 on o.job_technician1_id=t1.id
							  left outer join ace_rp_users t2 on o.job_technician2_id=t2.id
							  left outer join ace_rp_order_statuses s on s.id=o.order_status_id
							 where order_status_id in (1,5) $sqlConditions 
							 order by $order $sort";
		$query2 = "select id, name, price, quantity from ace_rp_order_items where 1=1 $sqlConditions order by $order $sort";
		
		$redo = array();
		$followup = array();
		$install = array();
		$other = array();
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$totals = $this->Common->getOrderTotal($row['id']);
			
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
		$order_id = $_REQUEST['order_id'];
		$method = $_REQUEST['method'];
		$amount = $_REQUEST['amount'];
		$payment_type = $_REQUEST['payment_type'];
		$show_message = $_REQUEST['show_message'];
		$userRole = $this->Common->getLoggedUserRoleID();
		$note = $_REQUEST['notes'];
		$file 	= isset($_FILES['payment_image'])? $_FILES['payment_image'] : null;
		$loggedUserId 	= $this->Common->getLoggedUserID();
		$anchor = '<a href="'.BASE_URL.'/orders/editBooking?order_id='.$order_id.'&rurl=orders%2FscheduleView%3F">'.$order_id.'</a>';
		$message = 'Please collect payment for '.$anchor;
		$toDate = date('Y-m-d');
		$fromDate = date('Y-m-d H:i:s');
		if($file !== null)
		{
			
            $this->User->id = $loggedUserId;
			$imageResult 	= $this->Common->commonSavePaymentImage($file, $order_id , $config = $this->User->useDbConfig);
		}
		//$date = date("Y-m-d", strtotime($_REQUEST['date']));
		$date = date("Y-m-d");
		$creator = $this->Common->getLoggedUserID();
		
		$isDialer 	= isset($_POST['from_dialer'])?$_POST['from_dialer'] :0;

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		//$payment_date = date("Y-m-d", strtotime($dat['payment_date']));
		$query_order_up = "UPDATE `ace_rp_orders` as `arc` set `arc`.`payment_method_type` =".$method." WHERE arc.id=".$order_id."";
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
		if($res == 1 && $show_message == 1) 
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
										 p.paid_amount, p.payment_date, p.auth_number
								from ace_rp_payments p
								left outer join ace_rp_payment_methods m on m.id=p.payment_method
							 where payment_type=1 and p.idorder='$order_id'";
		$result_p = $db->_execute($query);
		while($row_p = mysql_fetch_array($result_p, MYSQL_ASSOC))
		{
			$total_p += 1*$row_p['paid_amount'];
			
			$res .= "<tr>\n";
			$res .= "<td>&nbsp;{$row_p['payment_method']}</td>";
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
}
?>
