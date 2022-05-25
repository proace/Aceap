<? ob_start();
//error_reporting(E_ALL);

// This class represents all the reports created under the ACE System
// Created: 05/31/2010, Anthony Chernikov
class ReportsController extends AppController
{
	//To avoid possible PHP4 problemfss
	var $name = "ReportsController";

	var $uses = array('Order', 'OrderStatus', 'User', 'PaymentMethod','OrderType','CallResult','Customer');

	var $helpers = array('Time', 'Ajax', 'Javascript', 'Common');
	var $components = array('HtmlAssist', 'RequestHandler', 'Jpgraph', 'Common', 'Lists');
	var $itemsToShow = 20;
	var $pagesToDisplay = 10;
	var $layout = 'reports';
	
	// The summary of the telemarketers' work.
	function telemarketers_summary()
	{
		$this->layout="list";
		$loggedUserId = 0;
		$sqlConditions = '';
		if (($this->Common->getLoggedUserRoleID() == 3)	|| ($this->Common->getLoggedUserRoleID() == 9)) //TELEMARKETER OR LIMITED TELEMARKETER ONLY
		{
			$loggedUserId = $this->Common->getLoggedUserID();
			$sqlConditions .= ' and u.id='.$loggedUserId;
		}
    
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		if (!$order) $order = 'u.first_name asc';
				
		//CONDITIONS
		//Convert date from date picker to SQL format
		if ($this->params['url']['ffromdate'] != '')
			$this->params['url']['ffromdate'] = date("Y-m-d", strtotime($this->params['url']['ffromdate']));

		if ($this->params['url']['ftodate'] != '')
			$this->params['url']['ftodate'] = date("Y-m-d", strtotime($this->params['url']['ftodate']));
		
		//Pick today's date if no date
		$fdate = ($this->params['url']['ffromdate'] != '' ? $this->params['url']['ffromdate']: date("Y-m-d") ) ;
		$tdate = ($this->params['url']['ftodate'] != '' ? $this->params['url']['ftodate']: date("Y-m-d") ) ;
		$telemid = $this->params['url']['ftelemid'];
		$fdetails = $this->params['url']['fdetails'];
    
		$allJobTypes = $this->Lists->ListTable('ace_rp_order_types');
		$allTelemarketers = $this->Lists->Telemarketers();
		
		$db =& ConnectionManager::getDataSource('default');
		$sqlConditions_job = '';
		$sqlConditions_job2 = '';
		$sqlConditions_job3 = '';
		if($fdate != '')
		{
			$sqlConditions_job .= " AND job_date >= '".$this->Common->getMysqlDate($fdate)."'"; 
			$sqlConditions_job2 .= " AND booking_date >= '".$this->Common->getMysqlDate($fdate)."'"; 
			$sqlConditions_job3 .= " AND cast(work_date as date) >= '".$this->Common->getMysqlDate($fdate)."'"; 
	    }
		if($tdate != '')
	    {
			$sqlConditions_job .= " AND job_date <= '".$this->Common->getMysqlDate($tdate)."'"; 
			$sqlConditions_job2 .= " AND booking_date <= '".$this->Common->getMysqlDate($tdate)."'"; 
			$sqlConditions_job3 .= " AND cast(work_date as date) <= '".$this->Common->getMysqlDate($tdate)."'"; 
	    }
		if($telemid != '')
	    {
			$sqlConditions .= " AND u.id=".$telemid; 
	    }

		$records = array();
		//booking_telemarketer_id;booking_source_id
		$query ="
        SELECT u.id,
               u.first_name,
               u.last_name,
               o.order_type_id,
               sum(if(o.order_status_id=1,1,0)) Booked,
               sum(if(o.order_status_id=3,1,0)) Canceled,
               sum(if(o.order_status_id=5,1,0)) Done
          FROM ace_rp_users u left join ace_rp_orders o on o.booking_source_id=u.id $sqlConditions_job
         WHERE exists (select * from ace_rp_users_roles r where r.user_id=u.id and r.role_id in (3,9))
           and u.is_active=1 $sqlConditions 
         group by u.id, o.order_type_id
         ORDER BY $order $sort";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{
			$records[$row['id']]['id'] = $row['id'];
			$records[$row['id']]['name'] = $row['first_name'].' '.$row['last_name'];
			$records[$row['id']]['still_booked'] += 1*$row['Booked'];
			$records[$row['id']]['canceled'] += 1*$row['Canceled'];
			$records[$row['id']]['done'] += 1*$row['Done'];
			$records[$row['id']]['rate'] = 1*$row['Rate'];
			// Details by job types
			if (($row['order_type_id'])&&($fdetails=='on'))
			{
				$records[$row['id']]['details'][$row['order_type_id']]['name'] = "&nbsp;&nbsp;&nbsp;&nbsp;".$allJobTypes[$row['order_type_id']];
				$records[$row['id']]['details'][$row['order_type_id']]['still_booked'] = $row['Booked'];
				$records[$row['id']]['details'][$row['order_type_id']]['canceled'] = $row['Canceled'];
				$records[$row['id']]['details'][$row['order_type_id']]['done'] = $row['Done'];
				$records[$row['id']]['details'][$row['order_type_id']]['rate'] = $row['Rate'];
			}
		}
    
		//booking_telemarketer_id;booking_source_id
		$query ="
        SELECT u.id,
               u.first_name,
               u.last_name,
               o.order_type_id,
               count(distinct o.id) Booked
          FROM ace_rp_users u left join ace_rp_orders o on o.booking_source_id=u.id $sqlConditions_job2
         WHERE exists (select * from ace_rp_users_roles r where r.user_id=u.id and r.role_id in (3,9))
           and u.is_active=1 $sqlConditions 
         group by u.id, o.order_type_id
         ORDER BY $order $sort";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{
			$records[$row['id']]['id'] = $row['id'];
			$records[$row['id']]['name'] = $row['first_name'].' '.$row['last_name'];
			$records[$row['id']]['booked'] += 1*$row['Booked'];
			// Details by job types
			if (($row['order_type_id'])&&($fdetails=='on'))
			{
				$records[$row['id']]['details'][$row['order_type_id']]['name'] = "&nbsp;&nbsp;&nbsp;&nbsp;".$allJobTypes[$row['order_type_id']];
				$records[$row['id']]['details'][$row['order_type_id']]['booked'] = $row['Booked'];
			}
		}
    
		// Get working time from the dialer log
		$query = "
		  select u.id, u.first_name, u.last_name,cast(work_date as date),
            hour(max(last_date))-hour(min(work_date))+(minute(max(last_date))-minute(min(work_date)))/60 hours_total  
        from ace_rp_login_log l, ace_rp_users u
       where login_type=1 and u.id=l.user_id
         $sqlConditions_job3 $sqlConditions 
       group by u.id, u.first_name, u.last_name, cast(work_date as date)";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) 
		{
			$records[$row['id']]['name'] = $row['first_name'].' '.$row['last_name'];
			$records[$row['id']]['hours_total'] += $row['hours_total'];
		}    
    
//    // Get the callbacks summary
//    $fd = $this->Common->getMysqlDate($fdate);
//    $td = $this->Common->getMysqlDate($tdate);
//		$query = "
//		  SELECT u.id, u.first_name, u.last_name, count(*) calls
//			FROM ace_rp_call_history c
//			left outer join ace_rp_users u on u.id = c.callback_user_id 
//		   where c.call_result_id = 2
//			 and c.callback_date >= '$fd'
//			 and c.callback_date <= '$td'
//        and not exists 
//        (select * from ace_rp_call_history e 
//          where e.customer_id=c.customer_id
//            and e.callback_date>c.callback_date  
//            and e.call_result_id>1)
//		   group by u.id, u.first_name, u.last_name";
//
//		$result = $db->_execute($query);
//		while($row = mysql_fetch_array($result)) 
//		{
//			$records[$row['id']]['name'] = $row['first_name'].' '.$row['last_name'];
//      $records[$row['id']]['callbacks'] = $row['calls'];
//		}    
		
		$this->set("previousPage",$previousPage);
		$this->set("nextPage",$nextPage);
		$this->set("items", $records);
		$this->set("telemid", $telemid);
		if($fdate!='')
			$this->set('fdate', date("d M Y", strtotime($fdate)));
		if($tdate!='')
			$this->set('tdate', date("d M Y", strtotime($tdate)));
		$this->set('allTelemarketers',$allTelemarketers);	
		$this->set('allJobTypes', $allJobTypes);
		$this->set('fdetails', ($fdetails=='on')?'checked':'');
	}
	function acacareBooking(){
		$this->layout="blank";
		$users=  array();
		$showArchive = isset($_GET['showArchive']) ? $_GET['showArchive'] : 0;

		// die($showArchive);
		$db =& ConnectionManager::getDataSource('default');
		if($showArchive < 1)
		{
			$query = "select * from rp_acacare_ca_booking where is_order=0 and is_archive= 0 order by id desc";
		} else {
			$query = "select * from rp_acacare_ca_booking where is_order=0 and is_archive= '$showArchive' order by id desc";
		}
		$totalRes = $db->_execute($query);
         
		  while($row = mysql_fetch_array($totalRes, MYSQL_ASSOC)){
			$user_phone = $row['phone'];
			$user_city = $row['city'];
			if($this->checkCustomerId($user_phone)){
				$row['customer'] = $this->checkCustomerId($user_phone);
				$row['city1'] = $this->getcitybyname($user_city);
			}
			$users[] = $row;	
		 }

		 $this->set('showArchive', $showArchive);
		 $this->set("users", $users); 
	}
	function getcitybyname($city){
		$db =& ConnectionManager::getDataSource('default');
		$city1 = strtoupper($city);
		$query="SELECT id from ace_rp_cities where name='$city' limit 1";
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result);
		if(!empty($row['id']))
		{
			return $row['id'];
            
		} else {
			return '';
            
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
	function deletebooking(){
		$db =& ConnectionManager::getDataSource('default');
      $id = $_REQUEST['id'];
		
		echo $query = "delete from rp_acacare_ca_booking where id='$id'";
	$db->_execute($query);
		
		exit;
	}
	function archiveBooking(){
			$db =& ConnectionManager::getDataSource('default');
	      $id = $_REQUEST['id'];
	      $archiveVal = $_REQUEST['archiveVal'];
			
			echo $query = "update rp_acacare_ca_booking set is_archive='$archiveVal' where id='$id'";
		$db->_execute($query);
			
			exit;
	}

	function addNote(){
		$db =& ConnectionManager::getDataSource('default');
	  $id = $_REQUEST['id'];
	  $notes = $_REQUEST['message'];
		
		echo $query = "update rp_acacare_ca_booking set notes='$notes' where id='$id'";
		$db->_execute($query);
		// $this->redirect("reports/acacareBooking");
		exit;
	}
	function getbookingcount(){
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
        $query = "SELECT count(*) as total from rp_acacare_ca_booking where is_order=0 and is_archive=0";
        
        $result = $db->_execute($query);

        $row = mysql_fetch_array($result);
        $total = $row['total'];     
        $data=array('total'=>$total);
        echo json_encode($data); 
        exit();
	}
	
	function telem_groups() {
		$this->layout="list";
		$loggedUserId = 0;
	    $sqlConditions = '';
    
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		if (!$order) $order = 'u.first_name asc, u.last_name asc';
    
		$allTelemarketers = $this->Lists->Telemarketers();
    
		$db =& ConnectionManager::getDataSource('default');
		$pay_period = $this->params['url']['pay_period'];
		if (!$pay_period)
		{
			$query = "select * from ace_rp_pay_periods where current_date() between start_date and end_date and period_type=2";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				$pay_period = $row['id'];
		}

		$query = "select * from ace_rp_pay_periods where id=$pay_period";
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$start_date = strtotime($row['start_date']);
		$end_date = strtotime($row['end_date']);
			$dates = array();
		$cur = $start_date;
		while ($cur<=$end_date)
		{
			if (date("w", $cur)!=0) 
			{
				$key = date("Ymd", $cur);
				$dates[$key] = date("d", $cur);
			}
			$cur = strtotime(date("m/d/Y", $cur)." +1 day");
		}
		
		ksort($dates);
						 
		$records = array();
		 
		$query ="
        SELECT g.id AS gid, g.name AS gname, g.leader_id AS gleaderid, u.id, u.first_name, u.last_name
        FROM ace_rp_users u 
		LEFT JOIN ace_rp_groups_users m
		ON m.user_id = u.id
		LEFT JOIN ace_rp_groups g
		ON g.id = m.group_id
        WHERE EXISTS (
			SELECT * 
			FROM ace_rp_users_roles r 
			WHERE r.user_id = u.id 
			AND r.role_id in (3,9)
			)
        AND u.is_active=1 	
		ORDER BY u.first_name, u.last_name
        ";
//ORDER BY $order $sort

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{		
			$records[$row['id']]['gid'] = $row['gid'];	
			$records[$row['id']]['gname'] = $row['gname'];	
			$records[$row['id']]['gleaderid'] = $row['gleaderid'];	
			$records[$row['id']]['id'] = $row['id'];
			$records[$row['id']]['first_name'] = $row['first_name'];
			$records[$row['id']]['last_name'] = $row['last_name'];		
		}

		$this->set("groups", $this->Lists->Groups());
		$this->set("grouplist", $this->Lists->Groups());

		$this->set("items", $records);
		$this->set("nogroups", $records);
		
		$this->set('allTelemarketers',$allTelemarketers);	

	}
	//orsers reports
	
	function orderReports(){
		$this->layout="list";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		$allTechnicians = $this->Lists->Technicians();

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
		
		if(isset($_GET['show_hidden'])){
			$sqlConditions .=' and o.is_hidden=1';
			
		}
		else {
			$sqlConditions .=' and o.is_hidden=0';
		}
		if(isset($_GET['ftechid']) && $_GET['ftechid']!=-1 ){
			$sqlConditions .=" and o.job_technician1_id=".$_GET['ftechid'];
			
		}
		if(isset($_GET['ref']) && $_GET['ref']!="" ){
			$sqlConditions .=" and o.order_number=".$_GET['ref'];
			
		}
	//	$sqlConditions .=' and o.is_checked=0';
		
		$query = "select o.id, o.order_number, o.job_date, o.order_status_id,o.job_technician1_id,o.job_technician2_id,
										o.booking_source_id, o.booking_source2_id,
										 s.name order_status, o.invoice_submitted,o.is_checked,
										 concat(t1.first_name,' ',t1.last_name) tech1_name,
										 concat(t2.first_name,' ',t2.last_name) tech2_name,
										 concat(c.first_name,' ',c.last_name) client_name,
										 concat(t3.first_name,' ',t3.last_name) confirmed_by,
										 c.address_street client_address, c.phone client_phone,
										 o.order_type_id, o.order_status_id, jt.category_id,o.customer_id,
										 jt.name job_type_name, o.customer_deposit,o.payment_image,o.call_to_book,o.send_estimate,o.order_part,o.office_use,o.is_hidden,o.job_tech_notes,o.job_notes,
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
		//$customer_id  = $result['customer_id'];
	
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			
            $customer_id = $row['customer_id'];
			$allImages = array();
		 if($customer_id)
            {
				$allImages = array();
                $this->Customer->id = $customer_id;
                $cus = $this->Customer->read();
	//			print_r($cus);
                //$this->set('campaingId',$cus['Customer']['campaign_id']);
                
                 foreach ($cus['PartImages'] as $key => $value) {
                     if(!empty($value['image_name']) && $value['image_name'] != null){
                        $allImages[$key]['id'] = $value['id'];
                    $allImages[$key]['image_name'] = $this->getPhotoPath($value['image_name']);
                        $allImages[$key]['extension'] = pathinfo($value['image_name'], PATHINFO_EXTENSION);
                    }
                }
				
				
				//print_r($allImages);
				$items[$row['id']]['pics'] = $allImages;
			}
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
		$this->set('allImages',$allImages);
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
		$this->set('allTechnicians',$allTechnicians);
		//$this->set('allImages',$allImages);
		
	}
	function getfollowcount(){
		$db =& ConnectionManager::getDataSource('default');
		$this->layout="list";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		$allTechnicians = $this->Lists->Technicians();

		if ($this->params['url']['ffromdate'] != '')
			$fdate = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
		else
			$fdate = date("Y-m-d", strtotime("-1 days"));

		if ($this->params['url']['ftodate'] != '')
			$tdate = date("Y-m-d", strtotime($this->params['url']['ftodate']));
		else
			$tdate = date("Y-m-d", strtotime("today"));

		$sqlConditions = "";
		if($fdate != '')
			$sqlConditions .= " and o.job_date >= '".$this->Common->getMysqlDate($fdate)."'"; 
		if($tdate != '')
			$sqlConditions .= " and o.job_date <= '".$this->Common->getMysqlDate($fdate)."'";
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
		
		if(isset($_GET['show_hidden'])){
			$sqlConditions .=' and o.is_hidden=1';
			
		}
		else {
			$sqlConditions .=' and o.is_hidden=0';
		}
		if(isset($_GET['ftechid']) && $_GET['ftechid']!=-1 ){
			$sqlConditions .=" and o.job_technician1_id=".$_GET['ftechid'];
			
		}
		if(isset($_GET['ref']) && $_GET['ref']!="" ){
			$sqlConditions .=" and o.order_number=".$_GET['ref'];
			
		}
		//$sqlConditions .=' and o.is_checked=0';
		
		$query = "select o.id, o.order_number, o.job_date, o.order_status_id,o.job_technician1_id,o.job_technician2_id,
										o.booking_source_id, o.booking_source2_id,
										 s.name order_status, o.invoice_submitted,o.is_checked,
										 concat(t1.first_name,' ',t1.last_name) tech1_name,
										 concat(t2.first_name,' ',t2.last_name) tech2_name,
										 concat(c.first_name,' ',c.last_name) client_name,
										 concat(t3.first_name,' ',t3.last_name) confirmed_by,
										 c.address_street client_address, c.phone client_phone,
										 o.order_type_id, o.order_status_id, jt.category_id,o.customer_id,
										 jt.name job_type_name, o.customer_deposit,o.payment_image,o.call_to_book,o.send_estimate,o.order_part,o.office_use,o.is_hidden,o.job_tech_notes,o.job_notes,
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
							 
							 $order=array();
							 
							 $result = $db->_execute($query);
							 
							 while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
								if($row['job_tech_notes']=="" && $row['job_notes']=="" && $row['call_to_book']==""
								   && $row['order_part']=="" && $row['send_estimate']==""
								   ){
									continue;
									
								}
								if($row['is_checked']==1){
									continue;

								}
								$order[]=$row;
							 }
							 echo count($order);
							 exit;
		
	}
	function getcallbookcount(){
		$db =& ConnectionManager::getDataSource("default");
		date_default_timezone_set("America/Vancouver");
		$today_date = date("Y-m-d");
		$sql="SELECT c.id, c.card_number, c.first_name, c.last_name, c.postal_code, c.email, c.address_unit,
c.address_street_number, c.address_street, c.city, c.phone, c.cell_phone,c.selected_customer_from_search
as customer_row_color,c.selected_customer_from_search_agent as customer_row_color_agent, h.call_user_id,
h.call_note, h.call_result_id, arc.order_type_id as job_type,arc.job_date as last_job_done_date, h.callback_date,
h.callback_time, if((h.callback_date=current_date())&&(TIME_TO_SEC(CAST(now() AS TIME))>=
TIME_TO_SEC(CAST(h.callback_time AS TIME))-300),1,0) reminder_flag, h.call_date, u.first_name as telemarketer_first_name,
u.last_name as telemarketer_last_name FROM ace_rp_customers AS c join ace_rp_call_history h left join ace_rp_users
u on u.id=h.call_user_id left join ace_rp_orders arc on c.id = arc.customer_id WHERE c.id=h.customer_id and
(h.call_result_id in (0,1,2,4,8,9) or h.call_result_id is null) AND h.callback_date LIKE '%$today_date%'
AND h.call_date <= h.callback_date and not exists (select * from ace_rp_call_history y where y.customer_id=h.customer_id
and (y.call_date>h.call_date or y.call_date=h.call_date and y.call_time>h.call_time)) GROUP by c.id";

 $order=array();
    $result = $db->_execute($sql);
	while ($row = mysql_fetch_array($result,MYSQL_ASSOC)){
		$order[]=$row;
	}
	echo count($order);
	exit;
	
	}
	function callbackcount(){
		$db =& ConnectionManager::getDataSource('default');
		date_default_timezone_set("America/Vancouver");
		$today_date = date("Y-m-d");
		
		$sql="SELECT o.id, concat(est.first_name, ' ', est.last_name)
		estimator_name,concat(u.first_name, ' ', u.last_name) agent_name,
		concat(c.first_name, ' ',c.last_name) customer_name, o.customer_id,
		t.name order_type, o.order_number, c.phone, c.cell_phone,o.flagactive,
		o.booking_date, o.sCancelReason, o.created_date , c.city FROM ace_rp_orders o
		left join ace_rp_customers c on o.customer_id=c.id left join ace_rp_users u on
		o.booking_source_id=u.id left join ace_rp_users est on o.estimator = est.id left
		join ace_rp_order_types t on o.order_type_id=t.id WHERE o.job_truck in (40) AND 1
		AND o.flagactive=1 AND o.estimate_callback_date >= '$today_date'
		AND o.estimate_callback_date <= '$today_date'";
		
		 $order=array();
    $result = $db->_execute($sql);
	while ($row = mysql_fetch_array($result,MYSQL_ASSOC)){
		$order[]=$row;
	}
	echo count($order);
	exit;
		
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
	//fecth the images for a customer
	function fetchImages(){
		$customer_id = $_POST['cusid'];
		 if($customer_id)
            {
                $this->Customer->id = $customer_id;
                $cus = $this->Customer->read();
	//			print_r($cus);
                //$this->set('campaingId',$cus['Customer']['campaign_id']);
                $allImages = array();
				if(empty($cus['PartImages'])){
					die('<h1>No images found</h1');
				}
                 foreach ($cus['PartImages'] as $key => $value) {
                     if(!empty($value['image_name']) && $value['image_name'] != null){
                        //$allImages[$key]['id'] = $value['id'];
                         $allImages[$key]['image_name'] = $this->getPhotoPath($value['image_name']);
						 echo "<a class='example1' href='".ROOT_URL."/upload_photos/".$this->getPhotoPath($value['image_name'])."'><img class='customer_image' src='".ROOT_URL."/upload_photos/".$this->getPhotoPath($value['image_name'])."' alt=''/></a>";;
                        //$allImages[$key]['extension'] = pathinfo($value['image_name'], PATHINFO_EXTENSION);
						
						
                    }
					else {
						die('<h1>No images found</h1>');
					}
					
                }
				exit;
				
				
				
				
			}
				exit();
	}
	
	function markHidden(){
		$db =& ConnectionManager::getDataSource('default');
	$id = $_POST['id'];
	$query = "update ace_rp_orders set is_hidden='1' where id='$id'";
	$db->_execute($query);
	exit;
	}
	function markChecked(){
		$db =& ConnectionManager::getDataSource('default');
	$id = $_POST['id'];
	$query = "update ace_rp_orders set is_checked='1' where id='$id'";
	$db->_execute($query);
	exit;
	}
	function unMarkHidden(){
		$db =& ConnectionManager::getDataSource('default');
	$id = $_POST['id'];
	$query = "update ace_rp_orders set is_hidden='0' where id='$id'";
	$db->_execute($query);
	exit;
	}
	
	// The telemarketers board
	
	function telem_board()
	{
		$this->layout="list";
		$loggedUserId = 0;
	    $sqlConditions = '';
    
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		if (!$order) $order = 'u.first_name asc, u.last_name asc';
    
		$allTelemarketers = $this->Lists->Telemarketers();
    
		$db =& ConnectionManager::getDataSource('default');
		$pay_period = $this->params['url']['pay_period'];
		if (!$pay_period)
		{
			$query = "select * from ace_rp_pay_periods where current_date() between start_date and end_date and period_type=2";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				$pay_period = $row['id'];
		}

		$query = "select * from ace_rp_pay_periods where id=$pay_period";
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$start_date = strtotime($row['start_date']);
		$end_date = strtotime($row['end_date']);
			$dates = array();
		$cur = $start_date;
		while ($cur<=$end_date)
		{
			if (date("w", $cur)!=0) 
			{
				$key = date("Ymd", $cur);
				$dates[$key] = date("d", $cur);
			}
			$cur = strtotime(date("m/d/Y", $cur)." +1 day");
		}
		
		ksort($dates);

		// GOALS
		$goals = array();
		$query = "select * from ace_rp_payroll_structure";
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			$goals[$row['user_id']] = $row['goal'];
		
		// QUOTA
		$quotas = array();
		$query = "select * from ace_rp_payroll_structure";
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$quotas[$row['user_id']] = $row['quota'];
		}
				
		//CONDITIONS
	   $sqlConditions = " and exists (select * from ace_rp_pay_periods p
        where ((o.job_date between p.start_date and p.end_date) or (o.booking_date between p.start_date and p.end_date))
        and p.id=$pay_period)";				
 
		$today_total = 0;
		$total = 0;
		$records = array();

		 //get the days difference allowed
		 
		$query = "
			SELECT * 
			FROM ace_rp_settings
			WHERE id = 19	
		";
		$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				$diff_limit = $row['valuetxt'];
		 
		 
		$query ="
        SELECT g.id AS gid, g.name AS gname, g.leader_id AS gleaderid, g.lead_color, g.lead_text, g.team_color, g.team_text,			
			u.id, 
			u.first_name, 
			IF(g.leader_id = u.id, 1, 0) is_leader,
			u.last_name, o.booking_date, o.job_date, o.order_status_id,
			o.recording_confirmed, DATEDIFF(o.`job_date`, o.`booking_date`) diff
        FROM ace_rp_users u 
		LEFT JOIN ace_rp_orders o 
		ON o.booking_source_id = u.id
		LEFT JOIN ace_rp_groups_users m
		ON m.user_id = u.id
		LEFT JOIN ace_rp_groups g
		ON g.id = m.group_id
        WHERE EXISTS (
			SELECT * 
			FROM ace_rp_users_roles r 
			WHERE r.user_id = u.id 
			AND r.role_id in (3,6,7,9,1)
			)
        AND u.is_active=1 
        AND u.show_board=1 
		AND EXISTS (
			SELECT * 
			FROM ace_rp_pay_periods p
        	WHERE (
				(o.job_date between p.start_date AND p.end_date) 
				OR 
				(o.booking_date BETWEEN p.start_date AND p.end_date)
				)
        	AND p.id=$pay_period
			)
		AND o.order_type_id IN(2,95,80,3,4,1113,17,18,19,21,39,43,59,31,28)
        AND o.order_status_id not in (3)
		GROUP BY o.id
		ORDER BY g.id, is_leader DESC, u.first_name
        ";
//ORDER BY $order $sort
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{		
			$records[$row['id']]['gid'] = $row['gid'];	
			$records[$row['id']]['gname'] = $row['gname'];	
			$records[$row['id']]['gleaderid'] = $row['gleaderid'];	
			$records[$row['id']]['lead_color'] = $row['lead_color'];
			$records[$row['id']]['lead_text'] = $row['lead_text'];
			$records[$row['id']]['team_color'] = $row['team_color'];
			$records[$row['id']]['team_text'] = $row['team_text'];
			$records[$row['id']]['id'] = $row['id'];
			$records[$row['id']]['name'] = strtoupper($row['first_name']?$row['first_name']:$row['last_name']);
			$records[$row['id']]['diff'] = $row['diff'];
			
			$key_book = date("Ymd", strtotime($row['booking_date']));
			
			
			$records[$row['id']][$key_book]['booked']+=1;
			if($row['diff'] <= $diff_limit) $records[$row['id']][$key_book]['valid_booking']+=1;
			
			if($row['recording_confirmed'] == 0 && $row['order_status_id'] == 1 && $row['diff'] <= $diff_limit)
				$records[$row['id']][$key_book]['recording_unconfirmed']+=1;
			
			$records[$row['id']]['booked']+=1;			
			$records[$row['id']]['recording_confirmed']+=$row['recording_confirmed'];
			
			$key_done = date("Ymd", strtotime($row['job_date']));
			
			if ($row['order_status_id']==3)
			{
				$records[$row['id']][$key_done]['cancelled']+=1;
				$records[$row['id']]['cancelled']+=1;
			}
			elseif ($row['order_status_id']==5)
			{
				$records[$row['id']][$key_done]['done']+=1;				
				$records[$row['id']]['done']+=1;
			}
			elseif ($row['order_status_id']==1)
			{
				$records[$row['id']][$key_done]['pending']+=1;
				$records[$row['id']]['pending']+=1;
			}
			elseif ($row['order_status_id']==2)
			{
				$records[$row['id']][$key_done]['rescheduled']+=1;
				$records[$row['id']]['rescheduled']+=1;
				/*if($records[$row['id']][$key_done]['booked'] > 0) {
					$records[$row['id']][$key_done]['booked']-=1;
					$records[$row['id']]['booked']-=1;	
				}*/
			}
				
			if (strtotime($row['booking_date'])<$start_date)
				$records[$row['id']]['previous']['booked']+=1;
			else
			{
				if ($key_book==date("Ymd"))
				{
					$today_total+=1;
					if($row['recording_confirmed'] == 0 && $row['order_status_id'] == 1 && $row['diff'] <= $diff_limit)
						$today_unconfirmed+=1;
					$records[$row['id']]['booked_today']+=1;
				}
				$records[$row['id']]['booked_this_period']+=1;
				$total++;
			}			
			
			//GOAL
			$records[$row['id']]['goal'] = (key_exists($row['id'],$goals))?$goals[$row['id']]:30;
			//QUOTA
			$records[$row['id']]['quota'] = (key_exists($row['id'],$quotas))?$quotas[$row['id']]:30;
		}
    
		// Get working time from the dialer log
		$query = "
		  select u.id, u.first_name, u.last_name, cast(work_date as date) work_date,
			hour(max(last_date))-hour(min(work_date))+(minute(max(last_date))-minute(min(work_date)))/60 hours_total  
			from ace_rp_login_log l, ace_rp_users u
			where login_type=1 and u.id=l.user_id and cast(l.work_date as date)=current_date()
			group by u.id, u.first_name, u.last_name, cast(work_date as date)";
		
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) 
		{
			$records[$row['id']]['id'] = $row['id'];
			$records[$row['id']]['name'] = strtoupper($row['first_name']?$row['first_name']:$row['last_name']);
			$records[$row['id']]['hours_total'] += $row['hours_total'];
			
			//GOAL
			$records[$row['id']]['goal'] = (key_exists($row['id'],$goals))?$goals[$row['id']]:30;
			//QUOTA
			$records[$row['id']]['quota'] = (key_exists($row['id'],$quotas))?$quotas[$row['id']]:30;
		}
	
		$query = "select * from ace_rp_board where date=current_date()";
		$result = $db->_execute($query);
		if ($row = mysql_fetch_array($result)) $msg = $row['text'];
		
		$disp = '0';
		$query = "select valuetxt from ace_rp_settings where id = 15";
		$result = $db->_execute($query);
		if ($row = mysql_fetch_array($result)) $disp = $row['valuetxt'];
		
		$this->set("msg", $msg);
		$this->set("total", $total);
		$this->set("today_total", $today_total);
		$this->set("today_unconfirmed", $today_unconfirmed);
		$this->set("items", $records);
		$this->set("dates", $dates);
		$this->set("pay_period", $pay_period);
		$this->set('allTelemarketers',$allTelemarketers);	
		$this->set('allPayPeriods', $this->Lists->PayPeriods(2));	
		$this->set("disp", $disp);
		$this->set('groups', $this->Lists->Groups());
		$this->set('diff_limit', $diff_limit);
	}
	
	function telem_board_by_group()
	{
		$this->layout="list";
		$loggedUserId = 0;
	    $sqlConditions = '';
    
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		if (!$order) $order = 'u.first_name asc, u.last_name asc';
    
		$allTelemarketers = $this->Lists->Telemarketers();
    
		$db =& ConnectionManager::getDataSource('default');
		$pay_period = $this->params['url']['pay_period'];
		if (!$pay_period)
		{
			$query = "select * from ace_rp_pay_periods where current_date() between start_date and end_date and period_type=2";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				$pay_period = $row['id'];
		}

		$query = "select * from ace_rp_pay_periods where id=$pay_period";
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$start_date = strtotime($row['start_date']);
		$end_date = strtotime($row['end_date']);
			$dates = array();
		$cur = $start_date;
		while ($cur<=$end_date)
		{
			if (date("w", $cur)!=0) 
			{
				$key = date("Ymd", $cur);
				$dates[$key] = date("d", $cur);
			}
			$cur = strtotime(date("m/d/Y", $cur)." +1 day");
		}
		
		ksort($dates);

		// GOALS
		$goals = array();
		$query = "select * from ace_rp_payroll_structure";
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$goals[$row['user_id']] = $row['goal'];
		}
		
		// QUOTA
		$quotas = array();
		$query = "select * from ace_rp_payroll_structure";
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$quotas[$row['user_id']] = $row['quota'];
		}
		
		//CONDITIONS
		$sqlConditions = " and exists (select * from ace_rp_pay_periods p
        where ((o.job_date between p.start_date and p.end_date) or (o.booking_date between p.start_date and p.end_date))
        and p.id=$pay_period)";				
 
		$today_total = 0;
		$total = 0;
		$records = array();		
		
		$query ="
        SELECT g.id AS gid, g.name AS gname, g.leader_id AS gleaderid, u.id, u.first_name, u.last_name, o.booking_date, o.job_date, o.order_status_id
        FROM ace_rp_users u
		LEFT JOIN ace_rp_users_roles ur
		ON ur.user_id = u.id
		LEFT JOIN ace_rp_groups_users m
		ON m.user_id = u.id
		LEFT JOIN ace_rp_groups g
		ON g.id = m.group_id
		LEFT JOIN ace_rp_orders o 
		ON o.booking_source_id = u.id
        WHERE ur.role_id in (3,9)
        AND u.is_active=1 
		AND (
			o.job_date BETWEEN 
				(SELECT start_date FROM ace_rp_pay_periods where id = $pay_period) 
				AND
				(SELECT end_date FROM ace_rp_pay_periods where id = $pay_period)
			OR
			o.booking_date BETWEEN
				(SELECT start_date FROM ace_rp_pay_periods where id = $pay_period) 
				AND
				(SELECT end_date FROM ace_rp_pay_periods where id = $pay_period)
			)
        GROUP BY o.id
		ORDER BY g.id, g.leader_id desc, u.first_name
        ";
//ORDER BY $order $sort

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{		
			$records[$row['id']]['gid'] = $row['gid'];	
			$records[$row['id']]['gname'] = $row['gname'];	
			$records[$row['id']]['gleaderid'] = $row['gleaderid'];	
			$records[$row['id']]['id'] = $row['id'];
			$records[$row['id']]['name'] = strtoupper($row['first_name']?$row['first_name']:$row['last_name']);
			
			$key_book = date("Ymd", strtotime($row['booking_date']));
			$records[$row['id']][$key_book]['booked']+=1;
			$records[$row['id']]['booked']+=1;
			
			$key_done = date("Ymd", strtotime($row['job_date']));
			
			if ($row['order_status_id']==3)
			{
				$records[$row['id']][$key_done]['cancelled']+=1;
				$records[$row['id']]['cancelled']+=1;
			}
			elseif ($row['order_status_id']==5)
			{
				$records[$row['id']][$key_done]['done']+=1;
				$records[$row['id']]['done']+=1;
			}
			elseif ($row['order_status_id']==1)
			{
				$records[$row['id']][$key_done]['pending']+=1;
				$records[$row['id']]['pending']+=1;
			}
			elseif ($row['order_status_id']==2)
			{				
				$records[$row['id']][$key_done]['rescheduled']+=1;
				$records[$row['id']]['rescheduled']+=1;			
			}
				
			if (strtotime($row['booking_date'])<$start_date)
				$records[$row['id']]['previous']['booked']+=1;
			else
			{
				if ($key_book==date("Ymd"))
				{
					$today_total+=1;
					$records[$row['id']]['booked_today']+=1;
				}
				$records[$row['id']]['booked_this_period']+=1;
				$total++;
			}
			
			//GOAL
			$records[$row['id']]['goal'] = (key_exists($row['id'],$goals))?$goals[$row['id']]:30;
			//QUOTA
			$records[$row['id']]['quota'] = (key_exists($row['id'],$quotas))?$quotas[$row['id']]:30;
		}
    
		// Get working time from the dialer log
		$query = "
		  select u.id, u.first_name, u.last_name, cast(work_date as date) work_date,
			hour(max(last_date))-hour(min(work_date))+(minute(max(last_date))-minute(min(work_date)))/60 hours_total  
			from ace_rp_login_log l, ace_rp_users u
			where login_type=1 and u.id=l.user_id and cast(l.work_date as date)=current_date()
			group by u.id, u.first_name, u.last_name, cast(work_date as date)";
		
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) 
		{
			$records[$row['id']]['id'] = $row['id'];
			$records[$row['id']]['name'] = strtoupper($row['first_name']?$row['first_name']:$row['last_name']);
			$records[$row['id']]['hours_total'] += $row['hours_total'];
			
			//GOAL
			$records[$row['id']]['goal'] = (key_exists($row['id'],$goals))?$goals[$row['id']]:30;
			//QUOTA
			$records[$row['id']]['quota'] = (key_exists($row['id'],$quotas))?$quotas[$row['id']]:30;
		}
	
		$query = "select * from ace_rp_board where date=current_date()";
		$result = $db->_execute($query);
		if ($row = mysql_fetch_array($result)) $msg = $row['text'];
				
		
		$this->set("msg", $msg);
		$this->set("total", $total);
		$this->set("today_total", $today_total);
		$this->set("items", $records);
		$this->set("dates", $dates);
		$this->set("pay_period", $pay_period);
		$this->set('allTelemarketers',$allTelemarketers);	
		$this->set('allPayPeriods', $this->Lists->PayPeriods(2));	
		$this->set('groups',$this->Lists->Groups());	
	}
	
	function setGoal()
	{
		$goal = $_POST['goal'];
//		$pay_period = $_POST['pay_period'];
		$userid = $_POST['userid'];
		/*$db =& ConnectionManager::getDataSource('default');
		$query = "update ace_rp_payroll_structure set goal='$goal' where user_id='$userid'";
		$result = $db->_execute($query);*/
		$db =& ConnectionManager::getDataSource('default');		
		$query = "select count(*) cnt from ace_rp_payroll_structure where user_id='$userid'";
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		if ($row['cnt']>0)
			$query = "update ace_rp_payroll_structure set goal='$goal' where user_id='$userid'";
		else
			$query = "insert into ace_rp_payroll_structure (user_id, goal) values ('$userid','$goal')";
		$result = $db->_execute($query);
		exit;
	}
	function timeslot(){
		
     $table = "ace_rp_route_types";
	 $db =& ConnectionManager::getDataSource('default');
	 $query = "select * from ace_rp_route_types";
	$totalRes = $db->_execute($query);
	  while($row = mysql_fetch_array($totalRes, MYSQL_ASSOC)){
			
			
			$users[] = $row;
			
			
		 }
		 $this->set("users", $users);
	}
	
	function setQuota()
	{
		$quota = $_POST['quota'];
//		$pay_period = $_POST['pay_period'];
		$userid = $_POST['userid'];
		/*$db =& ConnectionManager::getDataSource('default');
		$query = "update ace_rp_payroll_structure set quota=$quota where user_id='$userid'";
		$result = $db->_execute($query);*/
		$db =& ConnectionManager::getDataSource('default');		
		$query = "select count(*) cnt from ace_rp_payroll_structure where user_id='$userid'";
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		if ($row['cnt']>0)
			$query = "update ace_rp_payroll_structure set quota='$quota' where user_id='$userid'";
		else
			$query = "insert into ace_rp_payroll_structure (user_id, quota) values ('$userid','$quota')";
		$result = $db->_execute($query);
		exit;
	}
	
	// The summary of the techs' work.
	// Created: 11/30/2010, Anthony Chernikov
	function technicians_summary()
	{
		$this->layout="list";
		$loggedUserId = 0;
		$sqlConditions = '';
    
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		if (!$order) $order = 'u.first_name asc';
				
		$pay_period = $this->params['url']['pay_period'];
		
		$db =& ConnectionManager::getDataSource('default');
		
		if($pay_period > 0) {
			$result = $db->_execute("
				SELECT * 
				FROM ace_rp_pay_periods 
				WHERE id = $pay_period
				LIMIT 1
			");
			if($row = mysql_fetch_array($result)) {
				$fdate = $row['start_date'];
				$tdate = $row['end_date'];
			}
		} else {
				
			//CONDITIONS
			//Convert date from date picker to SQL format
			if ($this->params['url']['ffromdate'] != '')
				$this->params['url']['ffromdate'] = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
	
			if ($this->params['url']['ftodate'] != '')
				$this->params['url']['ftodate'] = date("Y-m-d", strtotime($this->params['url']['ftodate']));
			
			//Pick today's date if no date
			$fdate = ($this->params['url']['ffromdate'] != '' ? $this->params['url']['ffromdate']: date("Y-m-d") ) ;
			$tdate = ($this->params['url']['ftodate'] != '' ? $this->params['url']['ftodate']: date("Y-m-d") ) ;
			$techid = $this->params['url']['ftechid'];
    
		}
	
		$allJobTypes = $this->Lists->ListTable('ace_rp_order_types');
		$allTechnician = $this->Lists->Technicians();
		
		
    $sqlConditions_job = '';
    $sqlConditions_job2 = '';
		if($fdate != '')
    {
			$sqlConditions_job .= " AND job_date >= '".$this->Common->getMysqlDate($fdate)."'"; 
			$sqlConditions_job2 .= " AND booking_date >= '".$this->Common->getMysqlDate($fdate)."'"; 
    }
		if($tdate != '')
    {
			$sqlConditions_job .= " AND job_date <= '".$this->Common->getMysqlDate($tdate)."'"; 
			$sqlConditions_job2 .= " AND booking_date <= '".$this->Common->getMysqlDate($tdate)."'"; 
    }
		if($techid != '')
			$sqlConditions .= " and u.id=".$techid; 

		$records = array();
		// Jobs done
		$query ="
        SELECT u.id, u.first_name, u.last_name,
               sum(if(o.job_technician1_id>0 and o.job_technician2_id>0 and o.job_technician1_id is not null and o.job_technician2_id is not null,0.5,1)) jobs_done,
               sum((select sum(i.price*i.quantity-i.discount+i.addition) from ace_rp_order_items i where o.id=i.order_id and i.class=0)) booking,
               sum((select sum(i.price*i.quantity-i.discount+i.addition) from ace_rp_order_items i where o.id=i.order_id and i.class=1)) sales
          FROM ace_rp_users u, ace_rp_orders o 
         WHERE o.order_status_id=5 $sqlConditions $sqlConditions_job
           and (o.job_technician1_id=u.id or o.job_technician2_id=u.id)
         group by u.id
         ORDER BY $order $sort";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$records[$row['id']]['id'] = $row['id'];
			$records[$row['id']]['name'] = $row['first_name'].' '.$row['last_name'];
			$records[$row['id']]['jobs_done'] = $row['jobs_done'];
			$records[$row['id']]['booking'] = round($row['booking'],2);
			$records[$row['id']]['sales'] = round($row['sales'],2);
		}
    
		// Jobs booked
		$query ="
        SELECT u.id, u.first_name, u.last_name,
               sum(if((o.order_type_id!=9 and o.order_type_id!=10),if(o.booking_source_id>0 and o.booking_source2_id>0 and o.booking_source_id is not null and o.booking_source2_id is not null,0.5,1),0)) jobs_booked,
               sum(if(o.order_type_id=9,if(o.booking_source_id>0 and o.booking_source2_id>0 and o.booking_source_id is not null and o.booking_source2_id is not null,0.5,1),0)) complaints,
               sum(if(o.order_type_id=10,if(o.booking_source_id>0 and o.booking_source2_id>0 and o.booking_source_id is not null and o.booking_source2_id is not null,0.5,1),0)) followups,
               sum((select sum(i.quantity) from ace_rp_order_items i, ace_rp_items t where t.id=i.item_id and t.is_appliance=1 and o.id=i.order_id and i.class=0)) appliances_cnt,
               sum((select sum(i.price*i.quantity-i.discount+i.addition) from ace_rp_order_items i, ace_rp_items t where t.id=i.item_id and t.is_appliance=1 and o.id=i.order_id and i.class=0)) appliances,
               sum((select sum(i.price*i.quantity-i.discount+i.addition) from ace_rp_order_items i where o.id=i.order_id and i.class=0)) all_booking,
			   sum(if(o.feedback_quality = 'EXCELLENT',if(o.booking_source_id>0 and o.booking_source2_id>0 and o.booking_source_id is not null and o.booking_source2_id is not null,0.5,1),0)) excellent,
			   sum(if(o.feedback_quality = 'GOOD',if(o.booking_source_id>0 and o.booking_source2_id>0 and o.booking_source_id is not null and o.booking_source2_id is not null,0.5,1),0)) good,
			   sum(if(o.feedback_quality = 'BAD',if(o.booking_source_id>0 and o.booking_source2_id>0 and o.booking_source_id is not null and o.booking_source2_id is not null,0.5,1),0)) bad,
			   sum(o.is_door_hanger) door_hanger,
			   sum(o.insurable) insurable	   
          FROM ace_rp_users u, ace_rp_orders o 
         WHERE (o.booking_source_id=u.id or o.booking_source2_id=u.id) $sqlConditions $sqlConditions_job2
           and exists (select * from ace_rp_users_roles r where r.user_id=u.id and r.role_id=1)
         group by u.id
         ORDER BY $order $sort";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$records[$row['id']]['id'] = $row['id'];
			$records[$row['id']]['name'] = $row['first_name'].' '.$row['last_name'];
			$records[$row['id']]['jobs_booked'] = $row['jobs_booked'];
			$records[$row['id']]['complaints'] = $row['complaints'];
			$records[$row['id']]['followups'] = $row['followups'];
			$records[$row['id']]['jobs_booked'] = $row['jobs_booked'];
			$records[$row['id']]['door_hanger'] = $row['door_hanger'];
			$records[$row['id']]['insurable'] = $row['insurable'];
			$records[$row['id']]['extra_booking'] = round($row['all_booking'],2);
			$records[$row['id']]['extra_booking_appliances'] = round($row['appliances'],2);
			$records[$row['id']]['appliances_cnt'] = $row['appliances_cnt'];
			$records[$row['id']]['excellent'] = $row['excellent'];
			$records[$row['id']]['good'] = $row['good'];
			$records[$row['id']]['bad'] = $row['bad'];
		}
		
		$this->set("items", $records);
		$this->set("techid", $techid);
		if($fdate!='')
			$this->set('fdate', date("d M Y", strtotime($fdate)));
		if($tdate!='')
			$this->set('tdate', date("d M Y", strtotime($tdate)));
		$this->set('allTechnician',$allTechnician);	
		$this->set('allJobTypes', $allJobTypes);
		$this->set('allPayPeriods', $this->Lists->PayPeriods(1));
	}
  
	// Techs' summary detalization.
	// Created: 12/07/2010, Anthony Chernikov
	function technicians_summary_details()
	{
    $sqlConditions = '';

		//Pick today's date if no date
		$fdate = ($_GET['ffromdate'] != '' ? date("Y-m-d", strtotime($_GET['ffromdate'])): date("Y-m-d") ) ;
		$tdate = ($_GET['ftodate'] != '' ? date("Y-m-d", strtotime($_GET['ftodate'])): date("Y-m-d") ) ;
		$techid = $_GET['techid'];
    
		$allJobTypes = $this->Lists->ListTable('ace_rp_order_types');
		$allTechnician = $this->Lists->Technicians();
		
		$db =& ConnectionManager::getDataSource('default');
    $sqlConditions_job = '';
    $sqlConditions_job2 = '';
		if($fdate != '')
    {
			$sqlConditions_job .= " AND job_date >= '".$this->Common->getMysqlDate($fdate)."'"; 
			$sqlConditions_job2 .= " AND booking_date >= '".$this->Common->getMysqlDate($fdate)."'"; 
    }
		if($tdate != '')
    {
			$sqlConditions_job .= " AND job_date <= '".$this->Common->getMysqlDate($tdate)."'"; 
			$sqlConditions_job2 .= " AND booking_date <= '".$this->Common->getMysqlDate($tdate)."'"; 
    }

		$records = array();
		// Jobs done
		$query ="
        SELECT o.id, o.job_date, o.booking_date, o.order_number,
               sum(if(o.job_technician1_id>0 and o.job_technician2_id>0 and o.job_technician1_id is not null and o.job_technician2_id is not null,0.5,1)) jobs_done,
               sum((select sum(i.price*i.quantity-i.discount+i.addition) from ace_rp_order_items i where o.id=i.order_id and i.class=0)) booking,
               sum((select sum(i.price*i.quantity-i.discount+i.addition) from ace_rp_order_items i where o.id=i.order_id and i.class=1)) sales
          FROM ace_rp_orders o 
         WHERE o.order_status_id=5 $sqlConditions_job
           and (o.job_technician1_id=$techid or o.job_technician2_id=$techid)
         group by o.id, o.job_date, o.booking_date, o.order_number";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$records[$row['id']]['id'] = $row['id'];
			$records[$row['id']]['job_date'] = $row['job_date'];
			//$records[$row['id']]['booking_date'] = $row['booking_date'];
			$records[$row['id']]['order_number'] = $row['order_number'];
			$records[$row['id']]['jobs_done'] = $row['jobs_done'];
			$records[$row['id']]['booking'] = round($row['booking'],2);
			$records[$row['id']]['sales'] = round($row['sales'],2);
		}
    
		// Jobs booked
		$query ="
        SELECT o.id, o.job_date, o.booking_date, o.order_number,
               sum(if((o.order_type_id!=9 and o.order_type_id!=10),if(o.booking_source_id>0 and o.booking_source2_id>0 and o.booking_source_id is not null and o.booking_source2_id is not null,0.5,1),0)) jobs_booked,
               sum(if(o.order_type_id=9,if(o.booking_source_id>0 and o.booking_source2_id>0 and o.booking_source_id is not null and o.booking_source2_id is not null,0.5,1),0)) complaints,
               sum(if(o.order_type_id=10,if(o.booking_source_id>0 and o.booking_source2_id>0 and o.booking_source_id is not null and o.booking_source2_id is not null,0.5,1),0)) followups,
               sum((select sum(i.quantity) from ace_rp_order_items i, ace_rp_items t where t.id=i.item_id and t.is_appliance=1 and o.id=i.order_id and i.class=0)) appliances_cnt,
               sum((select sum(i.price*i.quantity-i.discount+i.addition) from ace_rp_order_items i, ace_rp_items t where t.id=i.item_id and t.is_appliance=1 and o.id=i.order_id and i.class=0)) appliances,
               sum((select sum(i.price*i.quantity-i.discount+i.addition) from ace_rp_order_items i where o.id=i.order_id and i.class=0)) all_booking,
			   o.feedback_quality
          FROM ace_rp_orders o 
         WHERE (o.booking_source_id=$techid or o.booking_source2_id=$techid) $sqlConditions $sqlConditions_job2
         group by o.id, o.job_date, o.booking_date, o.order_number";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$records[$row['id']]['id'] = $row['id'];
			$records[$row['id']]['job_date'] = $row['booking_date'];
			//$records[$row['id']]['booking_date'] = $row['booking_date'];
			$records[$row['id']]['order_number'] = $row['order_number'];
			$records[$row['id']]['jobs_booked'] = $row['jobs_booked'];
			$records[$row['id']]['complaints'] = $row['complaints'];
			$records[$row['id']]['followups'] = $row['followups'];
			$records[$row['id']]['jobs_booked'] = $row['jobs_booked'];
			$records[$row['id']]['extra_booking'] = round($row['all_booking'],2);
			$records[$row['id']]['extra_booking_appliances'] = round($row['appliances'],2);
			$records[$row['id']]['appliances_cnt'] = $row['appliances_cnt'];
			$records[$row['id']]['feedback_quality'] = $row['feedback_quality'];
		}

		$sRes = '<table>';
		
		$sRes .= '<tr>
		<th>Ref #</th>
		<th>Date</th>
		<th>Jobs done</th>
		<th>Jobs Booked by Tech</th>
		<th>Complaints</th>
		<th>Follow Up</th>
		<th>Booking Amount</th>
		<th>Sales</th>
		<th>Appliances Sold by Tech</th>
		<th>Feedback</th>
		</tr>';
		foreach($records as $cur)
		{
			$sRes .= '<tr>';
			$sRes .= '<td><a href="'.BASE_URL.'/orders/editBooking?order_id='.$cur['id'].'">'.$cur['order_number'].'</a></td>';
			$sRes .= '<td>'.$cur['job_date'].'</td>';
			$sRes .= '<td>'.$cur['jobs_done'].'</td>';
			$sRes .= '<td>'.$cur['jobs_booked'].'</td>';
			$sRes .= '<td>'.$cur['complaints'].'</td>';
			$sRes .= '<td>'.$cur['followups'].'</td>';
			$sRes .= '<td>'.$cur['booking'].'</td>';
			$sRes .= '<td>'.($cur['sales']+$cur['extra_booking']-$cur['extra_booking_appliances']).'</td>';
			$sRes .= '<td>'.$cur['appliances_cnt'].'</td>';
			$sRes .= '<td>'.$cur['feedback_quality'].'</td>';
			$sRes .= '</tr>';
		}
		
		$sRes .= '</table>';

    echo $sRes;
    exit;
	}
	
	// Sales summary
	// Created: Anthony Chernikov, 08/27/2010
	function sales()
	{
		$this->layout="list";
		if ($this->Common->getLoggedUserRoleID() != 6) return;
    
		$allJobTypes = $this->Lists->ListTable('ace_rp_order_types');

	    $groupTypes = $_REQUEST['groupTypes'];
	    $groupItems = $_REQUEST['groupItems'];
	    $job_type = $_REQUEST['job_type'];
		
		//CONDITIONS
		//Convert date from date picker to SQL format
		if ($this->params['url']['ffromdate'] != '')
			$fdate = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
    else
			$fdate = date("Y-m-d");

		if ($this->params['url']['ftodate'] != '')
			$tdate = date("Y-m-d", strtotime($this->params['url']['ftodate']));
    else
			$tdate = date("Y-m-d");

		$db =& ConnectionManager::getDataSource('default');
		$sqlConditions = "";
		if($fdate != '')
			$sqlConditions .= " AND o.job_date >= '".$this->Common->getMysqlDate($fdate)."'"; 
		if($tdate != '')
			$sqlConditions .= " AND o.job_date <= '".$this->Common->getMysqlDate($tdate)."'"; 
		if($job_type)
			$sqlConditions .= " AND o.order_type_id = $job_type"; 
    
    if (!$groupTypes&&!$groupItems) $groupTypes=1;

		$records = array();
		$recordsTotal = array();

    if ($groupTypes)
    {
        $query ="
            select t.name job_type, o.order_status_id,
                   count(distinct o.id) qty,
                   sum(i.price*i.quantity-i.discount+i.addition) money,
                   sum(i.price_purchase*i.quantity) purchase
              from ace_rp_order_items i, ace_rp_orders o, ace_rp_order_types t
             where o.id=i.order_id and t.id=o.order_type_id and o.order_status_id in (3,5)
               ".$sqlConditions."
          group by t.name, o.order_status_id";
        
        $result = $db->_execute($query);
        while($row = mysql_fetch_array($result))
        {
            $st = $row['order_status_id'];
            
            $records[$row['job_type']]['name'] = $row['job_type'];
            $records[$row['job_type']]['qty'][$st] += $row['qty'];
            $records[$row['job_type']]['money'][$st] += $row['money'];
            $records[$row['job_type']]['purchase'][$st] += $row['purchase'];
            
            $recordsTotal['qty'][$st] += $row['qty'];
            $recordsTotal['money'][$st] += $row['money'];
            $recordsTotal['purchase'][$st] += $row['purchase'];
        }
        
        // Techs payments
        $query ="
            select t.name job_type, o.order_status_id, sum(c.total_comm) tech
              from ace_rp_orders_comm c, ace_rp_orders o, ace_rp_order_types t
             where o.id=c.order_id and t.id=o.order_type_id and o.order_status_id=5
               ".$sqlConditions."
          group by t.name, o.order_status_id";
        
        $result = $db->_execute($query);
        while($row = mysql_fetch_array($result))
        {
            $st = $row['order_status_id'];
            $records[$row['job_type']]['tech'][$st] += $row['tech'];
            $recordsTotal['tech'][$st] += $row['tech'];
        }

        if ($groupItems)
        {
            $query ="
                select t.name job_type, i.name item, o.order_status_id,
                       sum(i.quantity) qty,
                       sum(i.price*i.quantity-i.discount+i.addition) money,
                       sum(i.price_purchase*i.quantity) purchase
                  from ace_rp_order_items i, ace_rp_orders o, ace_rp_order_types t
                 where o.id=i.order_id and t.id=o.order_type_id and o.order_status_id in (3,5)
                   ".$sqlConditions."
              group by t.name, i.name, o.order_status_id";
            
            $result = $db->_execute($query);
            while($row = mysql_fetch_array($result))
            {
                $st = $row['order_status_id'];
                
                if (!is_array($records[$row['job_type']][$row['order_status_id']]['items']))
                    $records[$row['job_type']][$row['order_status_id']]['items'] = array();
                    
                $records[$row['job_type']]['items'][$row['item']]['name'] = $row['item'];
                $records[$row['job_type']]['items'][$row['item']]['qty'][$st] += $row['qty'];
                $records[$row['job_type']]['items'][$row['item']]['money'][$st] += $row['money'];
                $records[$row['job_type']]['items'][$row['item']]['purchase'][$st] += $row['purchase'];
            }
        }
    }
    elseif ($groupItems)
    {
        $query ="
            select i.name item, o.order_status_id,
                   sum(i.quantity) qty,
                   sum(i.price*i.quantity-i.discount+i.addition) money,
                   sum(i.price_purchase*i.quantity) purchase
              from ace_rp_order_items i, ace_rp_orders o
             where o.id=i.order_id and o.order_status_id in (3,5)
               ".$sqlConditions."
          group by i.name, o.order_status_id";
        
        $result = $db->_execute($query);
        while($row = mysql_fetch_array($result))
        {
            $st = $row['order_status_id'];
            
            if (!is_array($records[0][$row['order_status_id']]['items']))
                $records[0][$row['order_status_id']]['items'] = array();
                
            $records[0]['items'][$row['item']]['name'] = $row['item'];
            $records[0]['items'][$row['item']]['qty'][$st] = $row['qty'];
            $records[0]['items'][$row['item']]['money'][$st] = $row['money'];
            $records[0]['items'][$row['item']]['purchase'][$st] = $row['purchase'];
            
            $recordsTotal['qty'][$st] += $row['qty'];
            $recordsTotal['money'][$st] += $row['money'];
            $recordsTotal['purchase'][$st] += $row['purchase'];
        }
    }
    
		$this->set("records", $records);
		$this->set("recordsTotal", $recordsTotal);
		$this->set("job_type", $job_type);    
		$this->set('allJobTypes', $allJobTypes);
		$this->set("groupTypes", ($groupTypes?'checked':''));
		$this->set("groupItems", ($groupItems?'checked':''));
		$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));
		$this->set('next_fdate', date("d M Y", strtotime($fdate) + 24*60*60));
		$this->set('prev_tdate', date("d M Y", strtotime($tdate) - 24*60*60));
		$this->set('next_tdate', date("d M Y", strtotime($tdate) + 24*60*60));
		$this->set('fdate', date("d M Y", strtotime($fdate)));
		$this->set('tdate', date("d M Y", strtotime($tdate)));
	}
  
	function sales_monthly()
	{
		$this->layout="list";
		if ($this->Common->getLoggedUserRoleID() != 6) return;
    
		$allJobTypes = $this->Lists->ListTable('ace_rp_order_types');

    $groupTypes = $_REQUEST['groupTypes'];
    $job_types = $_REQUEST['job_types'];
		$showDoneNumber = $_REQUEST['showDoneNumber']?1:0;
		$showDoneAvgNumber = $_REQUEST['showDoneAvgNumber']?1:0;
		$showCanceledNumber = $_REQUEST['showCanceledNumber']?1:0;
		$showCanceledPercent = $_REQUEST['showCanceledPercent']?1:0;
		$showDoneMoney = $_REQUEST['showDoneMoney']?1:0;
		$showDoneAvgMoney = $_REQUEST['showDoneAvgMoney']?1:0;
    
    $rowspan = $showDoneNumber+$showDoneAvgNumber+$showCanceledNumber+$showCanceledPercent+$showDoneMoney+$showDoneAvgMoney;
    
    if (!$rowspan)
    {
        $showDoneNumber = 1;
        $showDoneAvgNumber = 0;
        $showCanceledNumber = 1;
        $showCanceledPercent = 1;
        $showDoneMoney = 0;
        $showDoneAvgMoney = 0;
        $rowspan=3;
    }
		//CONDITIONS
		//Convert date from date picker to SQL format
		if ($this->params['url']['ffromdate'] != '')
			$fdate = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
    else
			$fdate = date("Y-m-d");

		if ($this->params['url']['ftodate'] != '')
			$tdate = date("Y-m-d", strtotime($this->params['url']['ftodate']));
    else
			$tdate = date("Y-m-d");

		$db =& ConnectionManager::getDataSource('default');
		$sqlConditions = "";
		if($fdate != '')
			$sqlConditions .= " AND o.job_date >= '".$this->Common->getMysqlDate($fdate)."'"; 
		if($tdate != '')
			$sqlConditions .= " AND o.job_date <= '".$this->Common->getMysqlDate($tdate)."'";
    if (is_array($job_types))
    {
        $lst = '(';
        $ddd = '';
        foreach ($job_types as $k => $v)
        {
            if ($v)
            {
                $lst .= $ddd.$k;
                $job_types[$k] = 'checked';
            }
            $ddd = ',';
        }
        $lst .= ')';
        $sqlConditions .= " AND o.order_type_id in $lst"; 
    }
    else
    {
        $job_types = array();
        foreach ($allJobTypes as $k => $v) $job_types[$k] = 'checked';
    }
    
		$records = array();
		$recordsTotal = array();
		$months = array();

    $query ="
        select DATE_FORMAT(job_date,'%b %Y') str, DATE_FORMAT(job_date,'%Y%m') num
          from ace_rp_orders o
         where 1=1 ".$sqlConditions."
      group by num";
    
    $result = $db->_execute($query);
    while($row = mysql_fetch_array($result))
    {
        $months[$row['num']] = $row['str'];
    }

    $query ="
        select DATE_FORMAT(o.job_date,'%b %Y') month, t.name job_type,
               o.order_status_id, count(distinct o.id) cnt,
               sum(i.price*i.quantity-i.discount+i.addition) money
          from ace_rp_order_items i, ace_rp_orders o, ace_rp_order_types t
         where o.id=i.order_id and t.id=o.order_type_id and o.order_status_id in (3,5)
           ".$sqlConditions."
      group by month, t.name, o.order_status_id";
    
    $result = $db->_execute($query);
    while($row = mysql_fetch_array($result))
    {
        $st = $row['order_status_id'];
        
        $records[$row['job_type']]['name'] = $row['job_type'];
            
        if ($st==5)
        {
            $records[$row['job_type']]['cnt_done'][$row['month']] = $row['cnt'];
            $records[$row['job_type']]['money_done'][$row['month']] = $row['money'];
            $recordsTotal['cnt_done'][$row['month']] += $row['cnt'];
            $recordsTotal['money_done'][$row['month']] += $row['money'];
        }
        elseif ($st==3)
        {
            $records[$row['job_type']]['cnt_canceled'][$row['month']] = $row['cnt'];
            $records[$row['job_type']]['money_canceled'][$row['month']] = $row['money'];
            $recordsTotal['cnt_canceled'][$row['month']] += $row['cnt'];
            $recordsTotal['money_canceled'][$row['month']] += $row['money'];
        }
    }   
   
		$this->set("rowspan", $rowspan);
		$this->set("records", $records);
		$this->set("months", $months);
		$this->set("recordsTotal", $recordsTotal);
		$this->set("job_types", $job_types);    
		$this->set('allJobTypes', $allJobTypes);
		$this->set("groupTypes", ($groupTypes?'checked':''));
		$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));
		$this->set('next_fdate', date("d M Y", strtotime($fdate) + 24*60*60));
		$this->set('prev_tdate', date("d M Y", strtotime($tdate) - 24*60*60));
		$this->set('next_tdate', date("d M Y", strtotime($tdate) + 24*60*60));
		$this->set('fdate', date("d M Y", strtotime($fdate)));
		$this->set('tdate', date("d M Y", strtotime($tdate)));
		$this->set("showDoneNumber", ($showDoneNumber?'checked':''));
		$this->set("showDoneAvgNumber", ($showDoneAvgNumber?'checked':''));
		$this->set("showCanceledNumber", ($showCanceledNumber?'checked':''));
		$this->set("showCanceledPercent", ($showCanceledPercent?'checked':''));
		$this->set("showDoneMoney", ($showDoneMoney?'checked':''));
		$this->set("showDoneAvgMoney", ($showDoneAvgMoney?'checked':''));
	}
	
	// Method returns a list of jobs, registered to this person
	function JobsRerPerson($PersonID, $BookingFromDate, $BookingToDate, $CheckField, $sqlConditions)
	{
		if ($BookingFromDate != '') $BookingFromDate = date("Y-m-d", strtotime($BookingFromDate));
		if ($BookingToDate != '') $BookingToDate = date("Y-m-d", strtotime($BookingToDate));

		// $sqlConditions = 'and (o.created_by='.$PersonID.' or o.booking_source_id='.$PersonID.')';
		if($BookingFromDate != '')
		{
			// $sqlConditions .= " AND o.booking_date >= '".$this->Common->getMysqlDate($BookingFromDate)."'";
			$sqlConditions .= " AND o.job_date >= '".$this->Common->getMysqlDate($BookingFromDate)."'";
		} 
		if($BookingToDate != '')
		{
			// $sqlConditions .= " AND o.booking_date <= '".$this->Common->getMysqlDate($BookingToDate)."'"; 
			$sqlConditions .= " AND o.job_date <= '".$this->Common->getMysqlDate($BookingToDate)."'"; 
		}
		
		$db =& ConnectionManager::getDataSource('default');
		// $query = "select o.job_date, o.created, t.name job_type, s.name job_status,
  //               o.id, c.address, concat(c.first_name,' ',c.last_name) cust_name,
  //               c.address, c.phone, c.city, 
  //               concat(vu.first_name,' ',vu.last_name) verified_by,
  //               o.verified_date
		// 	    from ace_rp_orders o
		// 	    left join ace_rp_users c on o.customer_id=c.id
		// 	    left join ace_rp_order_types t on o.order_type_id=t.id
		// 	    left join ace_rp_order_statuses s on o.order_status_id=s.id
		// 	    left join ace_rp_users vu on o.verified_by_id=vu.id
		// 	    where o.order_status_id in (1,3,4,5) ".$sqlConditions;

		$query = "select o.job_date, o.created, t.name job_type, s.name job_status,
                o.id, c.address, concat(c.first_name,' ',c.last_name) cust_name,
                c.address, c.phone, c.city, 
                concat(vu.first_name,' ',vu.last_name) verified_by,
                o.verified_date
			    from ace_rp_orders o
			    left join ace_rp_users c on o.customer_id=c.id
			    left join ace_rp_order_types t on o.order_type_id=t.id
			    left join ace_rp_order_statuses s on o.order_status_id=s.id
			    left join ace_rp_users vu on o.verified_by_id=vu.id
			    where ".$sqlConditions;


		$nIdx = 1;
		$aRes = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) 
		{
			$aRes[$nIdx]=array();
			$aRes[$nIdx]['id']=$row['id'];
			$aRes[$nIdx]['job_date']=$row['job_date'];
			$aRes[$nIdx]['job_type']=$row['job_type'];
			$aRes[$nIdx]['job_status']=$row['job_status'];
			$aRes[$nIdx]['cust_name']=$row['cust_name'];
			$aRes[$nIdx]['address']=$row['address'];
			$aRes[$nIdx]['created']=$row['created'];
			$aRes[$nIdx]['city']=$row['city'];
			$aRes[$nIdx]['phone']=$row['phone'];
			$aRes[$nIdx]['verified_by']=$row['verified_by'];
			$aRes[$nIdx]['verified_date']=$row['verified_date'];
			$nIdx++;
		}
				
		return $aRes;
	}
	
	// Method returns an HTML table for the jobs of the given person
	function JobsInTable()
	{
		$PersonID=$_GET['person_id'];
		$BookingFromDate=$_GET['from_date'];
		$BookingToDate=$_GET['to_date'];
		$bookingType =  $_GET['booking_type'];
		$sqlConditions = '';
		switch ($bookingType) {
			case 'book_user':
				$sqlConditions .= '(o.created_by='.$PersonID.' and o.order_status_id =1)';
				$createdBy = "User";
				break;
			case 'book_source':
				$createdBy = "Source";
				$sqlConditions .= ' (o.booking_source_id='.$PersonID.' and o.order_status_id =1)';
				break;
			case 'cancel_user':
				$createdBy = "User";
				$sqlConditions .= ' (o.created_by='.$PersonID.' and o.order_status_id =3)';
				break;
			case 'cancel_source':
				$createdBy = "Source";
				$sqlConditions .= ' (o.booking_source_id='.$PersonID.' and o.order_status_id =3)';
				break;
			case 'done_user':
				$createdBy = "User";
				$sqlConditions .= ' (o.created_by='.$PersonID.' and o.order_status_id =5)';
				break;
			case 'done_source':
				$createdBy = "Source";
				$sqlConditions .= ' (o.booking_source_id='.$PersonID.' and o.order_status_id =5)';
				break;
			
			default:
				# code...
				break;
		}
		$sRes = '<table>';
		
		$sRes .= '<tr><th rowspan=2>Job ID</th><th rowspan=2>Booking made</th><th rowspan=2>Job date</th><th rowspan=2>Job type</th><th rowspan=2>Job status</th><th rowspan=2>Created By</th><th colspan=3>Verifyed</th></tr>';
		$sRes .= '<tr><th>User</th><th>Date/Time</th></tr>';
		$aArray = $this->JobsRerPerson($PersonID, $BookingFromDate, $BookingToDate, 'created_by', $sqlConditions);
		for ($x=1; $x<=count($aArray); $x++)
		{
			$sRes .= '<tr>';
			$sRes .= '<td><a href="'.BASE_URL.'/orders/editBooking?order_id='.$aArray[$x]['id'].'">'.$aArray[$x]['id'].'</a></td>';
			$sRes .= '<td>'.$aArray[$x]['created'].'</td>';
			$sRes .= '<td>'.$aArray[$x]['job_date'].'</td>';
			$sRes .= '<td>'.$aArray[$x]['job_type'].'</td>';
			$sRes .= '<td>'.$aArray[$x]['job_status'].'</td>';
			$sRes .= '<td>'.$createdBy.'</td>';
			$sRes .= '<td>'.$aArray[$x]['verified_by'].'</td>';
			if (($aArray[$x]['verified_by']!='0')&&($aArray[$x]['verified_by']!=''))
			{
				$sRes .= '<td>'.$aArray[$x]['verified_date'].'</td>';
			}
			else
			{
				$sRes .= '<td></td>';
			}
			$sRes .= '</tr>';
		}
		
		$sRes .= '</table>';
		
		echo $sRes;
		exit();
	}

	// The dialing summary. Aggregates the calls' results and the bookings
	// by each telemarketer for the given period of dates
	function calls_summary()
	{


        $this->layout="list";
    
		//CONDITIONS
		//Convert date from date picker to SQL format
		if ($this->params['url']['ffromdate'] != '')
			$this->params['url']['ffromdate'] = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
		else
			$this->params['url']['ffromdate'] = date("Y-m-d");
 	
		if ($this->params['url']['ftodate'] != '')
			$this->params['url']['ftodate'] = date("Y-m-d", strtotime($this->params['url']['ftodate']));
		else
			$this->params['url']['ftodate'] = date("Y-m-d");
		
		//Pick today's date if no date
		$fdate = ($this->params['url']['ffromdate'] != '' ? $this->params['url']['ffromdate']: "" ) ;
		$tdate = ($this->params['url']['ftodate'] != '' ? $this->params['url']['ftodate']: "" ) ;
		
		$sqlConditions = '';
		$sqlConditions1 = '';
		if($fdate != '')
		{
			$sqlConditions .= " AND c.call_date >= '".$this->Common->getMysqlDate($fdate)."'";
			$sqlConditions1 .= " AND o.job_date >= '".$this->Common->getMysqlDate($fdate)."'";
		} 
		if($tdate != '')
		{
			$sqlConditions .= " AND c.call_date <= '".$this->Common->getMysqlDate($tdate)."'"; 
			$sqlConditions1.= " AND o.job_date <= '".$this->Common->getMysqlDate($tdate)."'";
		}
			
		$records = array();
		$records_order = array();
		
		// Calls history part
		$db =& ConnectionManager::getDataSource('default');
		$query = "SELECT u.id, u.first_name, u.last_name, count(*) calls, 
				0 sales,
				sum(if(c.call_result_id=2,1,0)) call_back ,
				sum(if(c.call_result_id=3,1,0)) dnc,
				sum(if(c.call_result_id=4,1,0)) not_interesting,
				sum(if(c.call_result_id=6,1,0)) answering_machine,
				sum(if(c.call_result_id=7,1,0)) not_in_service
			FROM ace_rp_call_history c
			left outer join ace_rp_users u on u.id = c.call_user_id 
			  where c.call_result_id!=1 and c.dialer_id!='web' and c.dialer_id!=''
				  ".$sqlConditions."
			  group by u.id, u.first_name, u.last_name";

		$result = $db->_execute($query);
		$records['total']['source_name']='total';
		$records['total']['calls'] = 0;
		$records['total']['answering_machine'] = 0;
		$records['total']['book_user'] = 0;
		$records['total']['book_source'] = 0;
		$records['total']['cancel_user'] = 0;
		$records['total']['cancel_source'] = 0;
		$records['total']['sources'] = 0;
		$records['total']['call_back'] = 0;
		$records['total']['not_interesting'] = 0;
		$records['total']['dnc'] = 0;
		$records['total']['not_in_service'] = 0;
		while($row = mysql_fetch_array($result)) {
			$source_name = $row['first_name'].' '.$row['last_name'];
			if ($source_name==' ') $source_name = 'DID NOT LOGON PROPERLY';
			$records[$source_name]['source_name'] = $source_name;
			$records[$source_name]['role_name'] = 'Telemarketer';
			$records[$source_name]['calls'] = 0+$row['calls'];
			$records[$source_name]['answering_machine'] = $row['answering_machine'];
			$records[$source_name]['call_back'] = $row['call_back'];
			$records[$source_name]['dnc'] = $row['dnc'];
			$records[$source_name]['not_interesting'] = $row['not_interesting'];
			$records[$source_name]['not_in_service'] = $row['not_in_service'];
			$records[$source_name]['ace'] = 1;
			$records[$source_name]['id'] = $row['id'];
			$records['total']['calls'] += $row['calls'];
			$records['total']['answering_machine'] += $row['answering_machine'];
			$records['total']['call_back'] += $row['call_back'];
			$records['total']['not_interesting'] += $row['not_interesting'];
			$records['total']['dnc'] += $row['dnc'];
			$records['total']['not_in_service'] += $row['not_in_service'];

			$records_order['Telemarketer']['calls'] += $row['calls'];
			$records_order['Telemarketer']['answering_machine'] += $row['answering_machine'];
			$records_order['Telemarketer']['call_back'] += $row['call_back'];
			$records_order['Telemarketer']['not_interesting'] += $row['not_interesting'];
			$records_order['Telemarketer']['dnc'] += $row['dnc'];
			$records_order['Telemarketer']['not_in_service'] += $row['not_in_service'];
			$records_order['Telemarketer']['users'][$source_name] = $source_name;
		}

		// Bookings by created user 
		$query = "
        select r.name role_name, u.id, u.first_name, u.last_name, sum(1) cnt,
              sum(if(o.order_status_id=1,1,0)) book_cnt,
              sum(if(o.order_status_id=3,1,0)) canc_cnt,
              sum(if(o.order_status_id=5,1,0)) done_cnt
			   FROM ace_rp_orders o, ace_rp_users u, ace_rp_users_roles ur, ace_rp_roles r
			  where u.id = o.created_by and o.order_status_id in (1,3,5)
				  and ur.role_id = r.id and ur.user_id=u.id
			    ".$sqlConditions1."
			  group by r.name, u.id, u.first_name, u.last_name";
		
		$result = $db->_execute($query);
		$records['total']['sales'] = 0;
		while($row = mysql_fetch_array($result))
        {
		    if ($row['role_name']=='Telemarketer'||$this->Common->getLoggedUserRoleID() == 6)
            {
                $source_name = $row['first_name'].' '.$row['last_name'];
                $records[$source_name]['source_name'] = $source_name;
                $records[$source_name]['role_name'] = $row['role_name'];
                $records[$source_name]['id'] = $row['id'];
                $records[$source_name]['book_user'] = $row['book_cnt'];
                $records[$source_name]['cancel_user'] = $row['canc_cnt'];
                $records[$source_name]['done_user'] = $row['done_cnt'];
                $records['total']['book_user'] += $row['book_cnt'];
                $records['total']['cancel_user'] += $row['canc_cnt'];
                $records['total']['done_user'] += $row['done_cnt'];
                if ($records[$source_name]['ace']==1)
                {
                    $records[$source_name]['calls'] = 0+$row['cnt']+$records[$source_name]['calls'];
                    $records['total']['calls'] += $row['cnt'];
                }
                
                $records_order[$row['role_name']]['book_user'] += $row['book_cnt'];
                $records_order[$row['role_name']]['cancel_user'] += $row['canc_cnt'];
                $records_order[$row['role_name']]['done_user'] += $row['done_cnt'];
                $records_order[$row['role_name']]['users'][$source_name] = $source_name;
            }
		}

		// Bookings by the source
		$query = "
        select r.name role_name, u.id, u.first_name, u.last_name, sum(1) cnt,
              sum(if(o.order_status_id=1,1,0)) book_cnt,
              sum(if(o.order_status_id=3,1,0)) canc_cnt,
              sum(if(o.order_status_id=5,1,0)) done_cnt
			   FROM ace_rp_orders o, ace_rp_users u, ace_rp_users_roles ur, ace_rp_roles r
			  where u.id = o.booking_source_id and o.order_status_id in (1,3,4,5)
				  and ur.role_id = r.id and ur.user_id=u.id
			    ".$sqlConditions1."
			  group by r.name, u.id, u.first_name, u.last_name";
		
		$result = $db->_execute($query);
		$records['total']['sales'] = 0;
		while($row = mysql_fetch_array($result))
        {
		    if ($row['role_name']=='Telemarketer'||$this->Common->getLoggedUserRoleID() == 6)
            {
                $source_name = $row['first_name'].' '.$row['last_name'];
                $records[$source_name]['role_name'] = $row['role_name'];
                $records[$source_name]['source_name'] = $source_name;
                $records[$source_name]['id'] = $row['id'];
                $records[$source_name]['book_source'] = $row['book_cnt'];
                $records[$source_name]['cancel_source'] = $row['canc_cnt'];
                $records[$source_name]['done_source'] = $row['done_cnt'];
                $records['total']['book_source'] += $row['book_cnt'];
                $records['total']['cancel_source'] += $row['canc_cnt'];
                $records['total']['done_source'] += $row['done_cnt'];
                
                $records_order[$row['role_name']]['book_source'] += $row['book_cnt'];
                $records_order[$row['role_name']]['cancel_source'] += $row['canc_cnt'];
                $records_order[$row['role_name']]['done_source'] += $row['done_cnt'];
                $records_order[$row['role_name']]['users'][$source_name] = $source_name;
            }
		}
		
		ksort($records_order);
		
		$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));
		$this->set('next_tdate', date("d M Y", strtotime($tdate) + 24*60*60));
		$this->set("previousPage",$previousPage);
		$this->set("nextPage",$nextPage);
		$this->set("items", $records);
		$this->set("records_order", $records_order);
		if($fdate!='')
			$this->set('fdate', date("d M Y", strtotime($fdate)));
		if($tdate!='')
			$this->set('tdate', date("d M Y", strtotime($tdate)));
	}

	function transferCallbacksrecords_oncall(){

		$condtion = " 1 ";
		$records1 = 0;

		// Dates		
		$fdate = isset($_REQUEST['fromdate'])?$_REQUEST['fromdate']:'';
		$tdate = isset($_REQUEST['ttodate'])?$_REQUEST['ttodate']:'';

		if(isset($_REQUEST['fromuser'])&& !empty($_REQUEST['fromuser'])){
			$condtion .= " AND arc.callback_user_id=".$_REQUEST['fromuser'];			
		}
		
		if(isset($_REQUEST['disposition'])&& !empty($_REQUEST['disposition'])){
			/*$condtion .= " AND arc.call_result_id='".$_REQUEST['disposition']."'";	*/
			$ex_data = explode(',', $_REQUEST['disposition']);
			$str3 = "'".implode("','", $ex_data)."'";
			$condtion .= " AND arc.call_result_id IN ($str3)";		
		}

		if(isset($_REQUEST['CustomerAddressStreet'])&& !empty($_REQUEST['CustomerAddressStreet'])){
			$condtion .= " AND c.address_street='".$_REQUEST['CustomerAddressStreet']."'";		
		}		

		/*if(isset($_REQUEST['status'])&& !empty($_REQUEST['status'])){
			$condtion .= "  AND arc.order_status_id = '".$_REQUEST['status']."'";
		}*/


		$callmodefrom = isset($_REQUEST['callmodefrom'])?$_REQUEST['callmodefrom']:'';
		$callmodeto = isset($_REQUEST['callmodeto'])?$_REQUEST['callmodeto']:'';

		$callmodefrom = date("Y-m-d", strtotime($callmodefrom));
		$callmodeto = date("Y-m-d", strtotime($callmodeto));

		$fdate = date("Y-m-d", strtotime($fdate));
		$tdate = date("Y-m-d", strtotime($tdate));

		if(!empty($fdate)&& !empty($tdate)){
			$condtion .= "  AND arc.callback_date BETWEEN '".$fdate."' AND '".$tdate."'";
		}

		if(isset($_REQUEST['city'])&& !empty($_REQUEST['city'])){
			$condtion .= " AND c.city='".$_REQUEST['city']."'";		
		}

		if(isset($_REQUEST['PostalCode'])&& !empty($_REQUEST['PostalCode'])){
			$condtion .= " AND c.postal_code='".$_REQUEST['PostalCode']."'";		
		}  
		
		$db =& ConnectionManager::getDataSource('default');

    // Get the callbacks summary
		$query = "
		  SELECT `arc`.`id` as `id`,`arc`.`customer_id`,`arc`.`call_result_id`
			FROM ace_rp_call_history arc
			left join ace_rp_customers c on c.id = arc.customer_id
		   where 
		   campaign_id IS NULL AND 
		   $condtion
		   ";

		$result = $db->_execute($query);
		
		$i=0;
		while($row = mysql_fetch_array($result)) 
		{
			// var_dump($row['id']);
			$records_cust[$i] = $row['customer_id'];
			$i++;
		}

		$str2 = "'".implode("','", $records_cust)."'";

		$query1 = "SELECT *,`arc`.`id` as `id` FROM ace_rp_call_history arc 
				left join ace_rp_customers c on c.id = arc.customer_id 
				WHERE arc.id IN (SELECT MAX(id) FROM ace_rp_call_history WHERE customer_id IN ($str2) GROUP BY customer_id) AND $condtion GROUP BY c.id";

		$result_new = $db->_execute($query1);

		$i = 1;
		$records_new = null;
		while($row = mysql_fetch_array($result_new)){
			$records_new[$i] = $row['id'];
			$i++;
		}

		// print_r($records1);exit;
		
		echo count($records_new); exit;
	}

	function transferCallbacksInBulk_callback(){
		//echo "<pre>"; print_r($_GET); die;
		$condtion = " 1 ";

		// Dates		
		$fdate = isset($_REQUEST['fromdate'])?$_REQUEST['fromdate']:'';
		$tdate = isset($_REQUEST['ttodate'])?$_REQUEST['ttodate']:'';

		if(isset($_REQUEST['fromuser'])&& !empty($_REQUEST['fromuser'])){
			$condtion .= " AND arc.callback_user_id=".$_REQUEST['fromuser'];			
		}
		
		if(isset($_REQUEST['disposition'])&& !empty($_REQUEST['disposition'])){
			/*$condtion .= " AND arc.call_result_id='".$_REQUEST['disposition']."'";	*/
			$ex_data = explode(',', $_REQUEST['disposition']);
			$str3 = "'".implode("','", $ex_data)."'";
			$condtion .= " AND arc.call_result_id IN ($str3)";		
		}
		if(isset($_REQUEST['CustomerAddressStreet'])&& !empty($_REQUEST['CustomerAddressStreet'])){
			$condtion .= " AND c.address_street='".$_REQUEST['CustomerAddressStreet']."'";		
		}		

		$callmodefrom = isset($_REQUEST['callmodefrom'])?$_REQUEST['callmodefrom']:'';
		$callmodeto = isset($_REQUEST['callmodeto'])?$_REQUEST['callmodeto']:'';

		$callmodefrom = date("Y-m-d", strtotime($callmodefrom));
		$callmodeto = date("Y-m-d", strtotime($callmodeto));

		$fdate = date("Y-m-d", strtotime($fdate));
		$tdate = date("Y-m-d", strtotime($tdate));

		if(!empty($fdate)&& !empty($tdate)){
			$condtion .= "  AND arc.callback_date BETWEEN '".$fdate."' AND '".$tdate."'";
		}

		if(isset($_REQUEST['city'])&& !empty($_REQUEST['city'])){
			$condtion .= " AND c.city='".$_REQUEST['city']."'";		
		}

		if(isset($_REQUEST['PostalCode'])&& !empty($_REQUEST['PostalCode'])){
			$condtion .= " AND c.postal_code='".$_REQUEST['PostalCode']."'";		
		}  
		
		$db =& ConnectionManager::getDataSource('default');

    // Get the callbacks summary
		$query = "
		  SELECT `arc`.`id` as `id`,`arc`.`customer_id`,`arc`.`call_result_id`
			FROM ace_rp_call_history arc
			left join ace_rp_customers c on c.id = arc.customer_id
		   where 
		   campaign_id IS NULL AND 
		   $condtion
		   ";

		/*print_r($query);
		print_r('-----');*/

		$result = $db->_execute($query);
		
		$i = 1;
		while($row = mysql_fetch_array($result)){
			$records_id[$i] = $row['id'];
			$records_cust[$i] = $row['customer_id'];
			$despo_id_for_camp[$i] = $row['call_result_id'];
			$i++;
		}

		$list = $records_id;

		if(count($list) == 0){
			echo false;
		}

		$str2 = "'".implode("','", $records_cust)."'";

		$query1 = "SELECT *,`arc`.`id` as `id`,`arc`.`customer_id` as `cus_id`,`arc`.`call_result_id` as `despo_id` FROM ace_rp_call_history arc 
				left join ace_rp_customers c on c.id = arc.customer_id 
				WHERE arc.id IN (SELECT MAX(id) FROM ace_rp_call_history WHERE customer_id IN ($str2) GROUP BY customer_id) AND $condtion GROUP BY c.id";

		/*print_r($query1);
		print_r('-----');*/

		$result_new = $db->_execute($query1);

		$i = 1;
		while($row = mysql_fetch_array($result_new)){
			$records1[$i] = $row['id'];
			$records2[$i] = $row['cus_id'];
			$despo_id_for_camp[$i] = $row['despo_id'];
			$i++;
		}

/*		print_r($records1);
		echo '---';
		print_r($records2);
		echo '---';
		print_r($despo_id_for_camp);exit;*/

		$update_condition = " 1 ";

		$str1 = "'".implode("','", $records1)."'";

		$toUser = $_REQUEST['ttouser'];

		if(isset($_REQUEST['campaing']) && !empty($_REQUEST['campaing'])){
			$current_date = date("Y-m-d");
			$camp = $_REQUEST['campaing'];
			$cus_count = count($records1);
			$whole_str = '';

			if($cus_count != 0){
				$query = "INSERT INTO ace_rp_reference_campaigns(campaign_name,camp_count,transfer_call_jobs_flag,source_from) VALUES ('$camp' , '$cus_count','2',$toUser)";	

				$result = $db->_execute($query);

				$sel_query = "SELECT MAX(id) AS LastID FROM ace_rp_reference_campaigns";

				$sel_result = $db->_execute($sel_query);

				while($row = mysql_fetch_array($sel_result)){
					$LastID = $row['LastID'];
				}

				for ($i=1; $i <= count($records1); $i++) { 
					$customer_id = $records2[$i];
					$dispos_id = $despo_id_for_camp[$i];
					if($i == count($records1)){
						$whole_str.="('$camp','$customer_id','2','$LastID','$dispos_id','$current_date')";
					}
					else{
						$whole_str.="('$camp','$customer_id','2','$LastID','$dispos_id','$current_date'),";
					}
				}

				$query_order_up = "UPDATE `ace_rp_call_history` as `arc` set `arc`.`callback_user_id` = $toUser,`arc`.`call_campaign_id` = $LastID WHERE id IN ($str1); ";

				$result_up_order = $db->_execute($query_order_up);

				$str3 = "'".implode("','", $records2)."'";

				$up_query = "UPDATE `ace_rp_customers` as `arc` set `arc`.`campaign_id` = $LastID WHERE id IN ($str3);";

				$up_result = $db->_execute($up_query);

				$query = "INSERT INTO ace_rp_all_campaigns(campaign_name,call_history_ids,transfer_call_jobs_flag,last_inserted_id,disposition_id,created_date) VALUES $whole_str";	
				$result = $db->_execute($query);
			}
			else{
				$result = "No Jobs Trasfer because jobs count is 0";
			}
		}
	
	    echo $result;
		exit;
	}

	function export_csv(){
        $condtion = "";
        $condtion2 = "";
        $records1 = 0;
        // Dates        
        $fdate = isset($_REQUEST['fromdate'])?$_REQUEST['fromdate']:'';
        $tdate = isset($_REQUEST['ttodate'])?$_REQUEST['ttodate']:'';
        $forText = isset($_REQUEST['for_text'])?$_REQUEST['for_text']:'';

        if(isset($_REQUEST['city'])&& !empty($_REQUEST['city'])){
            $condtion2 .= " AND c.city='".$_REQUEST['city']."'";
            $condtion .= " AND c.city='".$_REQUEST['city']."'";     
        }

        if(!empty($fdate)&& !empty($tdate)){
            $fdate = date("Y-m-d", strtotime($fdate));
            $tdate = date("Y-m-d", strtotime($tdate));
            // $condtion .= "  AND c.callback_date BETWEEN '".$fdate."' AND '".$tdate."'";
            $condtion .= "  AND arc.booking_date BETWEEN '".$fdate."' AND '".$tdate."'";
        }
        if(isset($_REQUEST['jobtype'])&& !empty($_REQUEST['jobtype'])){
            $ex_data = explode(',', $_REQUEST['jobtype']);
            $job_type = "'".implode("','", $ex_data)."'";
            $condtion .= " AND arc.order_type_id IN ($job_type)";   
        }

        if(isset($_REQUEST['status'])&& !empty($_REQUEST['status'])){
            $ex_data = explode(',', $_REQUEST['status']);
            $job_status = "'".implode("','", $ex_data)."'";
            $condtion .= " AND arc.order_status_id IN ($job_status)";
        }
        $db =& ConnectionManager::getDataSource('default');

            // if($_REQUEST['hidden_token'] == 'yes'){
            //  if(!empty($_REQUEST['callmodefrom'])&& !empty($_REQUEST['callmodeto'])){
            //      $callmodefrom = isset($_REQUEST['callmodefrom'])?$_REQUEST['callmodefrom']:'';
            //      $callmodeto = isset($_REQUEST['callmodeto'])?$_REQUEST['callmodeto']:'';

            //      $callmodefrom = date("Y-m-d", strtotime($callmodefrom));
            //      $callmodeto = date("Y-m-d", strtotime($callmodeto));

            //      $condtion2 .= "  AND ach.callback_date BETWEEN '".$callmodefrom."' AND '".$callmodeto."'";
            //  }
            //  if(isset($_REQUEST['disposition'])&& !empty($_REQUEST['disposition'])){
            //      $ex_data = explode(',', $_REQUEST['disposition']);
            //      $str3 = "'".implode("','", $ex_data)."'";
            //      $condtion2 .= " AND ach.call_result_id IN ($str3)"; 
            //  }
            //  if((!empty($_REQUEST['callmodefrom'])&& !empty($_REQUEST['callmodeto'])) || !empty($_REQUEST['disposition'])){
            //      $query1 = "SELECT *, `c`.`first_name`,`c`.`last_name`,`c`.`phone`,`c`.`city`,`c`.`email`,`c`.`address_street`,
            //      `c`.`address_street_number`, `ach`.`call_date` , `ach`.`call_result_id` as `disposition` FROM ace_rp_call_history ach INNER JOIN (SELECT customer_id, MAX( id ) 
            //      AS MaxId FROM ace_rp_call_history GROUP BY customer_id ) topscore 
            //      ON ach.customer_id = topscore.customer_id
            //      AND ach.id = topscore.MaxId INNER JOIN ace_rp_call_results cr 
            //      ON cr.id = ach.call_result_id INNER JOIN ace_rp_customers c ON c.id = ach.customer_id
            //      WHERE ach.call_result_id !='' ".$condtion2."";
            //      $result = $db->_execute($query1);
            //  }
            // } else {

            //  $query1 = "SELECT `c`.`id`,  `c`.`first_name`,`c`.`last_name`,`c`.`phone`,`c`.`city`,`c`.`email`,`c`.`address_street`,
            //      `c`.`address_street_number` FROM ace_rp_customers c where id IS NOT NULL".$condtion."";

            //      $result = $db->_execute($query1);   
            // }

            if($_REQUEST['hidden_token'] == 'yes'){
                if(!empty($_REQUEST['callmodefrom'])&& !empty($_REQUEST['callmodeto'])){
                    $callmodefrom = isset($_REQUEST['callmodefrom'])?$_REQUEST['callmodefrom']:'';
                    $callmodeto = isset($_REQUEST['callmodeto'])?$_REQUEST['callmodeto']:'';

                    $callmodefrom = date("Y-m-d", strtotime($callmodefrom));
                    $callmodeto = date("Y-m-d", strtotime($callmodeto));

                    $condtion2 .= "  AND ach.callback_date BETWEEN '".$callmodefrom."' AND '".$callmodeto."'";
                }
                if(isset($_REQUEST['disposition'])&& !empty($_REQUEST['disposition'])){
                    $ex_data = explode(',', $_REQUEST['disposition']);
                    $str3 = "'".implode("','", $ex_data)."'";
                    $condtion2 .= " AND ach.call_result_id IN ($str3)"; 
                }
                if((!empty($_REQUEST['callmodefrom'])&& !empty($_REQUEST['callmodeto'])) || !empty($_REQUEST['disposition'])){
                    if($forText == 1)
                    {
                        $query1 = "SELECT *, `c`.`first_name`,`c`.`last_name`,`c`.`phone` as phone_number FROM ace_rp_call_history ach INNER JOIN (SELECT customer_id, MAX( id ) 
                        AS MaxId FROM ace_rp_call_history GROUP BY customer_id ) topscore 
                        ON ach.customer_id = topscore.customer_id
                        AND ach.id = topscore.MaxId INNER JOIN ace_rp_call_results cr 
                        ON cr.id = ach.call_result_id INNER JOIN ace_rp_customers c ON c.id = ach.customer_id
                        WHERE ach.call_result_id !='' ".$condtion2." group by c.phone UNION  SELECT *, `c`.`first_name`,`c`.`last_name`,`c`.`cell_phone` as phone_number FROM ace_rp_call_history ach INNER JOIN (SELECT customer_id, MAX( id ) 
                        AS MaxId FROM ace_rp_call_history GROUP BY customer_id ) topscore 
                        ON ach.customer_id = topscore.customer_id
                        AND ach.id = topscore.MaxId INNER JOIN ace_rp_call_results cr 
                        ON cr.id = ach.call_result_id INNER JOIN ace_rp_customers c ON c.id = ach.customer_id
                        WHERE ach.call_result_id !='' ".$condtion2." group by c.cell_phone";
                    } else {
                        $query1 = "SELECT *, `c`.`first_name`,`c`.`last_name`,`c`.`phone`,`c`.`city`,`c`.`email`,`c`.`address_street`,
                        `c`.`address_street_number`, `ach`.`call_date` , `ach`.`call_result_id` as `disposition` FROM ace_rp_call_history ach INNER JOIN (SELECT customer_id, MAX( id ) 
                        AS MaxId FROM ace_rp_call_history GROUP BY customer_id ) topscore 
                        ON ach.customer_id = topscore.customer_id
                        AND ach.id = topscore.MaxId INNER JOIN ace_rp_call_results cr 
                        ON cr.id = ach.call_result_id INNER JOIN ace_rp_customers c ON c.id = ach.customer_id
                        WHERE ach.call_result_id !='' ".$condtion2."";
                        
                    }
                    $result = $db->_execute($query1);
                }
            } else {
                if($forText == 1)
                {
                    $query1 = "SELECT  `c`.`id`,`c`.`first_name`,`c`.`last_name`,`c`.`phone` as phone_number, c.city, c.postal_code FROM ace_rp_customers c INNER JOIN ace_rp_orders arc ON arc.customer_id = c.id where c.id IS NOT NULL AND arc.id = (select max(id) from ace_rp_orders where customer_id = c.id) ".$condtion." group by c.phone UNION SELECT `c`.`id`, `c`.`first_name`,`c`.`last_name`,`c`.`cell_phone` as phone_number,c.city,c.postal_code FROM ace_rp_customers c INNER JOIN ace_rp_orders arc ON arc.customer_id = c.id where c.id IS NOT NULL AND arc.id = (select max(id) from ace_rp_orders where customer_id = c.id) ".$condtion." group by c.cell_phone ";
                } else {
                    $query1 = "SELECT `c`.`id`,  `c`.`first_name`,`c`.`last_name`,`c`.`phone`,`c`.`city`,`c`.`email`,`c`.`address_street`,
                    `c`.`address_street_number` FROM ace_rp_customers c INNER JOIN ace_rp_orders arc ON arc.customer_id = c.id where c.id IS NOT NULL  AND arc.id = (select max(id) from ace_rp_orders where customer_id = c.id) ".$condtion."";
                }

                    $result = $db->_execute($query1);   
            }


            $total_records = array();

            $i=0;
            $reference = null;
            $job_type = null;
            $address_street = null;
            $first_name = null;
            $last_name = null;
            $city = null;
            $disposition = null;
            $last_job_date = null;
            $phone = null;
            $email = null;
            $call_date = null;
            $address_street_number = null;
            $postal_code = null;

            $CallResult_despo = $this->HtmlAssist->table2array($this->CallResult->findAll(), 'id', 'name');

            if($forText == 1) 
            {
                while($row1 = mysql_fetch_array($result)) 
                {
                    $total_records[$i] = $row1['id'];
                    $first_name[$i] = $row1['first_name'];
                    $last_name[$i] = $row1['last_name'];
                    $phone[$i] = $row1['phone_number'];
                    $city[$i] = $row1['city'];
                    $postal_code[$i] = $row1['postal_code'];
                    $i++;
                }
                if(count($total_records) != 0){
                    $j = 0;
                    for ($i=1; $i <= count($total_records); $i++) {
                        if($i == count($total_records)){
                            if($i == 1){
                                $whole_str[] = "First Name,Last Name,Phone Number,city,postal_code";
                            }
                            $whole_str[] ="$first_name[$j],$last_name[$j],$phone[$j], $city[$j], $postal_code[$j]";
                        }
                        else{
                            if($i == 1){
                                $whole_str[] = "First Name,Last Name, Phone Number,city,postal_code";
                            }
                            $whole_str[] ="$first_name[$j],$last_name[$j],$phone[$j], $city[$j], $postal_code[$j]";
                        }
                        $j++;
                    }
                }
            }
             else
             {
                while($row1 = mysql_fetch_array($result)) 
                {
                    $total_records[$i] = $row1['id'];
                    $first_name[$i] = $row1['first_name'];
                    $last_name[$i] = $row1['last_name'];
                    $city[$i] = $row1['city'];
                    $address_street[$i] = $row1['address_street'];
                    $phone[$i] = $row1['phone'];
                    $disposition[$i] = isset($CallResult_despo[$row1['disposition']]) ? $CallResult_despo[$row1['disposition']] :'';
                    $reference[$i] = $row1['reference'];
                    $email[$i] = $row1['email'];
                    $call_date[$i] = isset($row1['call_date']) ? $row1['call_date'] : '';
                    $address_street_number[$i] = $row1['address_street_number'];

                    $i++;
                }
                if(count($total_records) != 0){
                    $j = 0;
                    for ($i=1; $i <= count($total_records); $i++) {
                        if($i == count($total_records)){
                            if($i == 1){
                                $whole_str[] = "First Name,Last Name,House number,Phone,City,Email,Address,Disposition,Last call made date";
                            }
                            $whole_str[] ="$first_name[$j],$last_name[$j],$address_street_number[$j],$phone[$j],$city[$j],$email[$j],$address_street[$j],$disposition[$j],$call_date[$j]";
                        }
                        else{
                            if($i == 1){
                                $whole_str[] = "First Name,Last Name,House number,Phone,City,Email,Address,Disposition,Last call made date";
                            }
                            $whole_str[] ="$first_name[$j],$last_name[$j],$address_street_number[$j],$phone[$j],$city[$j],$email[$j],$address_street[$j],$disposition[$j],$call_date[$j]";
                        }
                        $j++;
                    }
                }
            }
            if($_REQUEST['export_data_val'] == 'export_data_val'){
                $filename = md5(date('Y-m-d H:i:s:u'));

                $filepath = $_SERVER['DOCUMENT_ROOT']."/acesys/csv_folder/$filename.csv";
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename=$filename.csv');               
                $fp = fopen($filepath, 'wb');
                foreach ( $whole_str as $line ) {
                    $val = explode(",", $line);
                    fputcsv($fp, $val);
                }
                fclose($fp);
                echo "$filename";
                die;
            }
            else{
                echo 'no';exit;
            }
    }

	function transferCallbacksrecords()
	{
		
		$condtion = "";
		$condtion2 = "";	
		$fdate = isset($_REQUEST['fromdate'])?$_REQUEST['fromdate']:'';
		$tdate = isset($_REQUEST['ttodate'])?$_REQUEST['ttodate']:'';
        $is_text = isset($_REQUEST['is_text'])?$_REQUEST['is_text']:'';
		if(!empty($fdate) && !empty($tdate))
		{
			$fdate = date("Y-m-d", strtotime($fdate));
			$tdate = date("Y-m-d", strtotime($tdate));
			// $condtion .= " AND c.callback_date BETWEEN '".$fdate."' AND '".$tdate."'";
			$condtion .= " AND arc.booking_date BETWEEN '".$fdate."' AND '".$tdate."'";
		}
		if(isset($_REQUEST['city']) && !empty($_REQUEST['city']))
		{
			$condtion2 .= " AND c.city='".$_REQUEST['city']."'";	
			$condtion .= " AND c.city='".$_REQUEST['city']."'";		
		}

		if(isset($_REQUEST['jobtype'])&& !empty($_REQUEST['jobtype'])){
			/*$condtion .= "  AND arc.order_type_id = '".$_REQUEST['jobtype']."'";*/	
			$ex_data = explode(',', $_REQUEST['jobtype']);
			$job_type = "'".implode("','", $ex_data)."'";
			$condtion .= " AND arc.order_type_id IN ($job_type)";	
			//$condtion2 .= " AND arc.order_type_id IN ($job_type)";	
		}

		if(isset($_REQUEST['status'])&& !empty($_REQUEST['status'])){
			/*$condtion .= "  AND arc.order_status_id = '".$_REQUEST['status']."'";*/
			$ex_data = explode(',', $_REQUEST['status']);
			$job_status = "'".implode("','", $ex_data)."'";
			$condtion .= " AND arc.order_status_id IN ($job_status)";
			//$condtion2 .= " AND arc.order_status_id IN ($job_status)";
		}

		$db =& ConnectionManager::getDataSource('default');
  		  // Get the callbacks summary
		
		
		if($_REQUEST['hidden_token'] == 'yes')
		{
			if(!empty($_REQUEST['callmodefrom'])&& !empty($_REQUEST['callmodeto']))
			{
				$callmodefrom = isset($_REQUEST['callmodefrom'])?$_REQUEST['callmodefrom']:'';
				$callmodeto = isset($_REQUEST['callmodeto'])?$_REQUEST['callmodeto']:'';

				$callmodefrom = date("Y-m-d", strtotime($callmodefrom));
				$callmodeto = date("Y-m-d", strtotime($callmodeto));

				$condtion2 .= "  AND ach.callback_date BETWEEN '".$callmodefrom."' AND '".$callmodeto."'";
			}
			if(isset($_REQUEST['disposition'])&& !empty($_REQUEST['disposition']))
			{
				$ex_data = explode(',', $_REQUEST['disposition']);
				$str3 = "'".implode("','", $ex_data)."'";
				$condtion2 .= " AND ach.call_result_id IN ($str3)";	
			}
				$query1 = "SELECT ach.id FROM ace_rp_call_history ach INNER JOIN (SELECT customer_id, MAX( id ) AS MaxId FROM ace_rp_call_history GROUP BY customer_id ) topscore ON ach.customer_id = topscore.customer_id
					AND ach.id = topscore.MaxId INNER JOIN ace_rp_call_results cr ON cr.id = ach.call_result_id INNER JOIN ace_rp_customers c ON c.id = ach.customer_id
					WHERE ach.call_result_id !='' ".$condtion2."";
				
				$result = $db->_execute($query1);
				$total_records = array();
				while($row1 = mysql_fetch_array($result)) 
				{
					$total_records[] = $row1['id'];
				}
		}
		else
		{
            if($is_text == 1) {
                 $query = "SELECT  `c`.`id`,`c`.`first_name`,`c`.`last_name`,`c`.`phone` as phone_number FROM ace_rp_customers c INNER JOIN ace_rp_orders arc ON arc.customer_id = c.id where c.id IS NOT NULL AND arc.id = (select max(id) from ace_rp_orders where customer_id = c.id) ".$condtion." group by c.phone UNION SELECT `c`.`id`, `c`.`first_name`,`c`.`last_name`,`c`.`cell_phone` as phone_number FROM ace_rp_customers c INNER JOIN ace_rp_orders arc ON arc.customer_id = c.id where c.id IS NOT NULL AND arc.id = (select max(id) from ace_rp_orders where customer_id = c.id) ".$condtion." group by c.cell_phone ";
            } else {
                    // $query = "SELECT `c`.`id` FROM ace_rp_customers c where id IS NOT NULL".$condtion."";
                $query = "SELECT `c`.`id`, arc.* FROM ace_rp_customers c left join ace_rp_orders arc on c.id = arc.customer_id where c.id IS NOT NULL AND arc.id = (select max(id) from ace_rp_orders where customer_id = c.id) ".$condtion."";
            }
		
            // print_r($query); die;
			$result = $db->_execute($query);
			
			$total_records = array();
			while($row = mysql_fetch_array($result)) 
			{
				$total_records[] = $row['id'];			
			}
		}

		echo count($total_records); 

		exit;
	}

/*
this function for trasfer jobs
*/

	function transferCallbacksInBulk()
	{
		$condtion = "";
		$condtion2 = "";
		
		$fdate = isset($_REQUEST['fromdate'])?$_REQUEST['fromdate']:'';
		$tdate = isset($_REQUEST['ttodate'])?$_REQUEST['ttodate']:'';

		if(isset($_REQUEST['jobtype'])&& !empty($_REQUEST['jobtype'])){
			/*$condtion .= "  AND arc.order_type_id = '".$_REQUEST['jobtype']."'";*/	
			$ex_data = explode(',', $_REQUEST['jobtype']);
			$job_type = "'".implode("','", $ex_data)."'";
			$condtion .= " AND arc.order_type_id IN ($job_type)";
		}

		if(isset($_REQUEST['city'])&& !empty($_REQUEST['city'])){
			$condtion2 .= " AND c.city='".$_REQUEST['city']."'";
			$condtion .= " AND c.city='".$_REQUEST['city']."'";		
		}

		if(!empty($fdate)&& !empty($tdate)){
			$fdate = date("Y-m-d", strtotime($fdate));
			$tdate = date("Y-m-d", strtotime($tdate));
			$condtion .= "  AND c.callback_date BETWEEN '".$fdate."' AND '".$tdate."'";
		}


		$db = & ConnectionManager::getDataSource('default');

		if($_REQUEST['hidden_token'] == 'yes'){
			if(!empty($_REQUEST['callmodefrom'])&& !empty($_REQUEST['callmodeto'])){
				$callmodefrom = isset($_REQUEST['callmodefrom'])?$_REQUEST['callmodefrom']:'';
				$callmodeto = isset($_REQUEST['callmodeto'])?$_REQUEST['callmodeto']:'';

				$callmodefrom = date("Y-m-d", strtotime($callmodefrom));
				$callmodeto = date("Y-m-d", strtotime($callmodeto));

				$condtion2 .= "  AND ach.callback_date BETWEEN '".$callmodefrom."' AND '".$callmodeto."'";
			}
			if(isset($_REQUEST['disposition'])&& !empty($_REQUEST['disposition'])){
				$ex_data = explode(',', $_REQUEST['disposition']);
				$str3 = "'".implode("','", $ex_data)."'";
				$condtion2 .= " AND ach.call_result_id IN ($str3)";	
			}
			if((!empty($_REQUEST['callmodefrom'])&& !empty($_REQUEST['callmodeto'])) || !empty($_REQUEST['disposition'])){	

				$query1 = "SELECT * FROM ace_rp_call_history ach INNER JOIN (SELECT customer_id, MAX( id ) AS MaxId FROM ace_rp_call_history GROUP BY customer_id ) topscore ON ach.customer_id = topscore.customer_id
					AND ach.id = topscore.MaxId INNER JOIN ace_rp_call_results cr ON cr.id = ach.call_result_id INNER JOIN ace_rp_customers c ON c.id = ach.customer_id
					WHERE ach.call_result_id !='' ".$condtion2."";
				
				$result = $db->_execute($query1);
				$total_records = array();

				$i=0;
				$records2 = null;
				$despo_id_for_camp = null;
				while($row1 = mysql_fetch_array($result)) 
				{
					$total_records[$i] = $row1['id'];
					$records2[$i] = $row1['id'];
					$despo_id_for_camp[$i] = $row1['call_result_id'];
					$i++;
				}
			}
		}
		else{
				$query = "SELECT `c`.`id` FROM ace_rp_customers c where id IS NOT NULL".$condtion."";

					$result = $db->_execute($query);
					$total_records = array();

					$i=0;
					$records2 = null;
					$despo_id_for_camp = null;
					while($row1 = mysql_fetch_array($result)) 
					{
						$total_records[$i] = $row1['id'];
						$records2[$i] = $row1['id'];
						$despo_id_for_camp[$i] = $row1['call_result_id'];
						$i++;
					}
		}

		$str1 = "";
		$str1 = "'".implode("','", $total_records)."'";
		$total_count_id = count($total_records);

		if(isset($_REQUEST['campaing']) && !empty($_REQUEST['campaing'])){
			$current_date = date("Y-m-d");
			$camp = $_REQUEST['campaing'];
			$whole_str = '';

			if($total_count_id != 0){
				$query = "INSERT INTO ace_rp_reference_campaigns(campaign_name,camp_count,transfer_call_jobs_flag,camp_city) VALUES ('$camp' , '$total_count_id','1','".$_REQUEST['city']."')";	

				$result = $db->_execute($query);

				$sel_query = "SELECT MAX(id) AS LastID FROM ace_rp_reference_campaigns";

				$sel_result = $db->_execute($sel_query);

				while($row = mysql_fetch_array($sel_result)){
					$LastID = $row['LastID'];
				}

				$j=0;
				for ($i=1; $i <= $total_count_id; $i++) { 
					$customer_id = $records2[$j];
					$dispos_id = $despo_id_for_camp[$j];

					if($i == $total_count_id){
						$whole_str.="('$camp','$customer_id','1','$LastID','$dispos_id','$current_date')";
					}
					else{
						$whole_str.="('$camp','$customer_id','1','$LastID','$dispos_id','$current_date'),";
					}
					$j++;
				}

				$query_order_up = "UPDATE `ace_rp_orders` as `arc` set `arc`.`o_campaign_id` = $LastID WHERE customer_id IN ($str1); ";

				$result_up_order = $db->_execute($query_order_up);

				$str3 = "'".implode("','", $records2)."'";

				$up_query = "UPDATE `ace_rp_customers` as `arc` set `arc`.`campaign_id` = $LastID WHERE id IN ($str3);";

				$up_result = $db->_execute($up_query);

				$query = "INSERT INTO ace_rp_all_campaigns(campaign_name, call_history_ids,transfer_call_jobs_flag,last_inserted_id,disposition_id,created_date) VALUES $whole_str";

				$result = $db->_execute($query);

			}
			else{
				$result = "No Jobs Trasfer because jobs count is 0";
			}
		}

	    echo $result; exit;
	}

	// Callback list. The list of the callbacks has to be made
	function callback_summary()
	{
		/*if ($_REQUEST['post']) {
			echo "string";exit();
		} */
		//echo "string";
		//print_r($_REQUEST());exit();
		// Dates
		$fdate = $this->params['url']['ffromdate'];
		$tdate = $this->params['url']['ftodate'];
		if ($fdate != '')
			$fdate = date("Y-m-d", strtotime($fdate));
		else
			$fdate = date("Y-m-d");

		if ($tdate != '')
			$tdate = date("Y-m-d", strtotime($tdate));
		else
			$tdate = date("Y-m-d");

		$db =& ConnectionManager::getDataSource('default');

    // Get the callbacks summary
		$query = "
		  SELECT u.id, u.first_name, u.last_name, count(*) calls
			FROM ace_rp_call_history c
			left outer join ace_rp_users u on u.id = c.callback_user_id 
		   where c.call_result_id in (2)
			and c.callback_date >= '".$fdate."'
			and c.callback_date <= '".$tdate."'
        and not exists 
        (select * from ace_rp_call_history e 
          where e.customer_id=c.customer_id
            and e.callback_date>c.callback_date)
		   group by u.id, u.first_name, u.last_name
		 ";

		  /*and c.callback_date >= '".$fdate."'
			 and c.callback_date <= '".$tdate."'
*/			 
		$result = $db->_execute($query);
		$records = array();
		while($row = mysql_fetch_array($result)) 
		{
			$source_name = $row['first_name'].' '.$row['last_name'];
			if ($source_name==' ') $source_name = 'UNKNOWN USER';
			$records[$source_name]['source_name'] = $source_name;
			$records[$source_name]['calls'] = 0+$row['calls'];
			$records[$source_name]['id'] = $row['id'];
		}    
		
		$this->set("callbacks", $records);
    
    // Get the 'not interested' summary
		$query = "
		  SELECT u.id, u.first_name, u.last_name, count(distinct c.phone) calls
			FROM ace_rp_call_history c
			left outer join ace_rp_users u on u.id = c.callback_user_id 
		   where c.call_result_id in (4,8,9)
			 and c.callback_date >= '".$fdate."'
			 and c.callback_date <= '".$tdate."'
             and not exists 
        (select * from ace_rp_call_history e 
          where e.customer_id=c.customer_id
            and e.call_date>c.call_date)
		   group by u.id, u.first_name, u.last_name";

		$result = $db->_execute($query);
		$records = array();
		while($row = mysql_fetch_array($result)) 
		{
			$source_name = $row['first_name'].' '.$row['last_name'];
			if ($source_name==' ') $source_name = 'UNKNOWN USER';
			$records[$source_name]['source_name'] = $source_name;
			$records[$source_name]['calls'] = 0+$row['calls'];
			$records[$source_name]['id'] = $row['id'];
		}
		$this->set("not_interested", $records);
		
		//get clients
		$query = "
    	SELECT COUNT(DISTINCT ch.phone) calls
		FROM ace_rp_users u
		LEFT JOIN ace_rp_call_history ch
		ON u.id = ch.customer_id
		LEFT JOIN ace_rp_users u2
		ON u2.id = ch.call_user_id
		WHERE ch.call_result_id!=6
		AND ch.callback_date >= '".$fdate."'
		AND ch.callback_date <= '".$tdate."'
		AND ch.callback_date = (SELECT MAX(ch3.callback_date) FROM ace_rp_call_history ch3 WHERE ch3.customer_id = u.id)
	";
		$result = $db->_execute($query);
		$records = array();
		while($row = mysql_fetch_array($result)) 
		{
			$source_count = $row['calls'];			
		}
		if( $_GET['forder_type_id'] != "" ) {
		  $conditions["Order.order_type_id"] =  $_GET['forder_type_id'];
		  $conditions_string .= " AND Order.order_type_id=".$_GET['forder_type_id'];
		}
		
		//Additions by Anton
		if (($this->Common->getLoggedUserRoleID() == "3") ||($this->Common->getLoggedUserRoleID() == "9"))
			$this->set('job_statuses', $this->HtmlAssist->table2array($this->OrderStatus->findAll(array("OrderStatus.id"=>array("1","5"))), 'id', 'name'));
		else
			$this->set('job_statuses', $this->HtmlAssist->table2array($this->OrderStatus->findAll(), 'id', 'name'));

		$this->set("clients", $source_count);

		$this->set('job_types', $this->HtmlAssist->table2array($this->OrderType->findAll(), 'id', 'name'));

		// Pagination
		$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));
		$this->set('next_tdate', date("d M Y", strtotime($tdate) + 24*60*60));
		$this->set("previousPage",$previousPage);
		$this->set("nextPage",$nextPage);
    
    // Other stuff
		$this->set('subtitle','Transfer Jobs');
		$this->set('fdate', date("d M Y", strtotime($fdate)));
		$this->set('tdate', date("d M Y", strtotime($tdate)));
		$this->set('telemarketers', $this->Lists->BookingSources());
		$this->set('booking_sources', $this->Lists->BookingSources());
		$this->set('job_types', $this->HtmlAssist->table2array($this->OrderType->findAll(array("OrderType.flagactive",1)), 'id', 'name'));
		$this->set('allCities',$this->Lists->ActiveCities());
		$this->set('call_results', $this->HtmlAssist->table2array($this->CallResult->findAll(), 'id', 'name'));

	}

  // Method transfers all callbacksthat have not been done yet
  // in the given period from one user to another
  function transferCallbacks()
  {	
  	
    $fromuser = $_GET["fromuser"];
    $touser = $_GET["touser"];
		$fdate = date("Y-m-d", strtotime($_GET["fdate"]));
		$tdate = date("Y-m-d", strtotime($_GET["tdate"]));
    
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "
		  update ace_rp_call_history c
         set callback_user_id='" .$touser ."'
		   where c.call_result_id in (1,2)
			 and c.callback_date >= '".$fdate."'
			 and c.callback_date <= '".$tdate."'
       and callback_user_id='" .$fromuser ."'";

		$db->_execute($query);
    echo 'Done';
		exit;
  }
	
  // Method creates a CSV list of all not interested for the given period
  function exportNIToCSV()
  {
		$fdate = date("Y-m-d", strtotime($_GET["fdate"]));
		$tdate = date("Y-m-d", strtotime($_GET["tdate"]));

    $date = date("Y.m.d");
    header("Content-Disposition: attachment; filename=not_interested_".$date.".csv");
    
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "
    select distinct u.phone, u.first_name, u.last_name, substr(u.address,1,50) address, u.city, u.postal_code
      from ace_rp_call_history c, ace_rp_users u
     where c.call_result_id in (4,8,9) and u.id=c.customer_id
			 and c.callback_date >= '".$fdate."'
			 and c.callback_date <= '".$tdate."'
       and not exists 
        (select * from ace_rp_call_history e 
          where e.customer_id=c.customer_id
            and e.call_date>c.call_date)
    order by u.city";

		$result = $db->_execute($query);
			
    while ($row = mysql_fetch_array($result, MYSQLI_ASSOC))
    {
        print $row['phone'] .",";
        print $row['first_name'] .",";
        print $row['last_name'] .",";
        print $row['address'] .",";
        print $row['city'] .",";
        print $row['postal_code'] ."\n";
    }
    
    exit;
  }
  
  function exportClientsToCSV()
  {
		$fdate = date("Y-m-d", strtotime($_GET["fdate"]));
		$tdate = date("Y-m-d", strtotime($_GET["tdate"]));
		if(isset($_GET["option"])) {				
				$temp = $_GET["option"];
				if($temp == 3)
					$options = " AND cr.id IN($temp) ";
				else
					$options = " AND cr.id IN($temp, 1) ";
		}

    $date = date("Y.m.d");
    header("Content-Disposition: attachment; filename=client_list_".$date.".csv");
    
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		/*$query = "
    select distinct u.phone, u.first_name, u.last_name, substr(u.address,1,50) address, u.city, u.postal_code
      from ace_rp_call_history c, ace_rp_users u
     where c.call_result_id in (4,8,9) and u.id=c.customer_id
			 and c.callback_date >= '".$fdate."'
			 and c.callback_date <= '".$tdate."'
       and not exists 
        (select * from ace_rp_call_history e 
          where e.customer_id=c.customer_id
            and e.call_date>c.call_date)
    order by u.city";*/

	if($temp == 1) {
		$query = "
			SELECT IFNULL(ch.phone, u.phone) phone, ch.cell_phone cell_phone, u.first_name first_name, u.last_name last_name
			,ch.callback_date callback_date, ch.call_note call_note, ch.call_date call_date
			,cr.name result,u.postal_code postal_code, REPLACE(u.address, ',', ' ') address, u.city city
			FROM ace_rp_users u
			LEFT JOIN ace_rp_call_history ch
			ON u.id = ch.customer_id
			LEFT JOIN ace_rp_call_results cr
			ON ch.call_result_id = cr.id
			WHERE ch.call_result_id!=6
			AND ch.call_date >= '$fdate'
			AND ch.call_date <= '$tdate'
			AND ch.call_date = (SELECT MAX(ch3.call_date) FROM ace_rp_call_history ch3 WHERE ch3.customer_id = u.id)
			AND NOT (u.phone = '' AND ch.phone = '')
			$options
			ORDER BY ch.phone
		";		
	} else {
		$query = "
			SELECT IFNULL(ch.phone, u.phone) phone, ch.cell_phone cell_phone, u.first_name first_name, u.last_name last_name
			,ch.callback_date callback_date, ch.call_note call_note, ch.call_date call_date
			,cr.name result,u.postal_code postal_code, REPLACE(u.address, ',', ' ') address, u.city city
			FROM ace_rp_users u
			LEFT JOIN ace_rp_call_history ch
			ON u.id = ch.customer_id
			LEFT JOIN ace_rp_call_results cr
			ON ch.call_result_id = cr.id
			WHERE ch.call_result_id!=6
			AND ch.callback_date >= '$fdate'
			AND ch.callback_date <= '$tdate'
			AND ch.callback_date = (SELECT MAX(ch3.callback_date) FROM ace_rp_call_history ch3 WHERE ch3.customer_id = u.id)
			AND NOT (u.phone = '' AND ch.phone = '')
			$options
			ORDER BY ch.phone
		";
	}
		$result = $db->_execute($query);
		
		print $row['phone'] ."Phone,";
		print $row['cell_phone'] ."Cellphone,";
        print $row['first_name'] ."First name,";
        print $row['last_name'] ."Last name,";
		print $row['callback_date'] ."Callback Date,";
		print $row['call_date'] ."Call Date,";
		//print $row['call_note'] .",";
		print $row['postal_code'] ."Postal Code,";
		print $row['address'] ."Address,";
		print $row['city'] ."City,";
		print $row['result'] ."Result\n";
    while ($row = mysql_fetch_array($result, MYSQLI_ASSOC))
    {
        print $row['phone'] .",";
		print $row['cell_phone'] .",";
        print $row['first_name'] .",";
        print $row['last_name'] .",";
		print $row['callback_date'] .",";
		print $row['call_date'] .",";
		//print $row['call_note'] .",";
		print $row['postal_code'] .",";
		print $row['address'] .",";
		print $row['city'] .",";
		print $row['result'] ."\n";
    }
    
    exit;
  }

    function ceoReport() {
		//TODO: unhardcode prefixes
		
		// set filters 
		$sqlFilter = '';
		if( $this->params['url']['fromdate'] != '' || $this->params['url']['todate'] != '') {
			if( $this->params['url']['fromdate'] != '' && $this->params['url']['todate'] != '' ) {
				$sqlFilter = "  AND ace_rp_orders.job_date >= '".$this->Common->getMysqlDate($_GET['fromdate'])."' AND job_date <= '".$this->Common->getMysqlDate($_GET['todate'])."'";
			} else {
				if( $this->params['url']['fromdate'] != '') {
					$sqlFilter = "  AND ace_rp_orders.job_date >= '".$this->Common->getMysqlDate($_GET['fromdate'])."'";
				}
				if( $this->params['url']['todate'] != '' ) {
					$sqlFilter = " AND ace_rp_orders.job_date <= '".$this->Common->getMysqlDate($_GET['todate'])."'";
				}
			}
		}
		
		//JOBS BY STATUS
		$statuses = $this->OrderStatus->findAll();

		$c = 0;
		$data_s = "";
		$legend_s = "";


		foreach ($statuses as $status)
		{
			$orders = $this->Order->query('SELECT SUM(booking_amount) as amount, COUNT(*) as jobs FROM ace_rp_orders WHERE order_status_id = '.$status['OrderStatus']['id'].$sqlFilter);

			$jbs_status[$c] = $status['OrderStatus']['name'];
			$jbs_amount[$c] = $orders[0][0]['amount'] | 0;
			$jbs_jobs[$c] = $orders[0][0]['jobs'];
			//$legend[$c] = $status[OrderStatus][name];

			$data_s .= $orders[0][0]['amount'];
			$data_s .= ",";

			$legend_s .= $status['OrderStatus']['name'];
			$legend_s .= ",";

			$c++;
		}

		$data_s = substr($data_s, 0, -1);
		$legend_s = substr($legend_s, 0, -1);

		$this->set('jbs_amount', $jbs_amount);
		$this->set('jbs_jobs', $jbs_jobs);
		$this->set('jbs_status', $jbs_status);

		$this->set('byStatusImg', 'pieGraph?x=500&y=200&theme=ace_statuses&title=Orders by Status&data=' . $data_s . '&legend=' . $legend_s);


		//JOBS BY SOURCE
		$orders_sources = $this->Order->query('SELECT DISTINCT ace_rp_orders.booking_source_id, ace_rp_users.first_name, ace_rp_users.last_name FROM ace_rp_orders INNER JOIN ace_rp_users ON ace_rp_orders.booking_source_id = ace_rp_users.id');

		$c = 0;
		$datay_s = "";
		$datax_s = "";

		foreach ($orders_sources as $source)
		{
			$orders = $this->Order->query('SELECT SUM(booking_amount) as amount, COUNT(*) as jobs FROM ace_rp_orders WHERE booking_source_id = '.$source['ace_rp_orders']['booking_source_id'].$sqlFilter);

			$jbr_source[$c] = $source['ace_rp_users']['first_name'].' '.$source['ace_rp_users']['last_name'];
			$jbr_amount[$c] = $orders[0][0]['amount'] | 0;
			$jbr_jobs[$c] = $orders[0][0]['jobs'];

			if ($datay_s != "")
				$datay_s .= ",";
			$datay_s .= $orders[0][0]['amount'];

			if ($datax_s != "")
				$datax_s .= ",";
			$datax_s .= $source['ace_rp_users']['first_name'].' '.$source['ace_rp_users']['last_name']; //." (".$orders[0][0][jobs]." jobs)";

			$c++;
		}

		$this->set('jbr_amount', $jbr_amount);
		$this->set('jbr_jobs', $jbr_jobs);
		$this->set('jbr_source', $jbr_source);

		//Determine right image size

		$width = count($orders_sources) * 110;
		if ($width < 500)
			$width = 500;

		$this->set('bySourceImg', 'barGraph?x='.$width.'&y=200&title=Orders by Source&xtitle=Source&ytitle=Amount&datay=' . $datay_s . '&datax=' . $datax_s);

		//JOBS BY PRIMARY TECHNICIAN (NET INCOME)
		$techs = $this->Order->query('SELECT DISTINCT ace_rp_orders.job_technician1_id, ace_rp_users.first_name, ace_rp_users.last_name FROM ace_rp_orders INNER JOIN ace_rp_users ON ace_rp_orders.job_technician1_id = ace_rp_users.id');


		$c = 0;
		$datay_s = "";
		$datax_s = "";

		foreach ($techs as $tech)
		{
			$orders = $this->Order->query('SELECT SUM(booking_amount) as amount, COUNT(*) as jobs FROM ace_rp_orders WHERE job_technician1_id = '.$tech['ace_rp_orders']['job_technician1_id'].$sqlFilter);

			$jbt_tech[$c] = $tech['ace_rp_users']['first_name'].' '.$tech['ace_rp_users']['last_name'];
			$jbt_amount[$c] = $orders[0][0]['amount'] | 0;
			$jbt_jobs[$c] = $orders[0][0]['jobs'];

			if ($datay_s != "")
				$datay_s .= ",";
			$datay_s .= $orders[0][0]['amount'];

			if ($datax_s != "")
				$datax_s .= ",";
			$datax_s .= $tech['ace_rp_users']['first_name'].' '.$tech['ace_rp_users']['last_name']; //." (".$orders[0][0][jobs]." jobs)";

			$c++;
		}

		$this->set('jbt_amount', $jbt_amount);
		$this->set('jbt_jobs', $jbt_jobs);
		$this->set('jbt_tech', $jbt_tech);

		$width = count($techs) * 110;
		if ($width < 500)
			$width = 500;

		$this->set('byTechImg', 'barGraph?x='.$width.'&y=200&title=Orders by Technician&xtitle=Technician&ytitle=Amount&datay=' . $datay_s . '&datax=' . $datax_s);


		//JOBS BY METHOD OF PAYMENT
		$methods = $this->PaymentMethod->findAll();

		$c = 0;
		$data_s = "";
		$legend_s = "";

		foreach ($methods as $method)
		{
			$orders = $this->Order->query('SELECT SUM(booking_amount) as amount, COUNT(*) as jobs FROM ace_rp_orders WHERE customer_payment_method_id = '.$method['PaymentMethod']['id'].$sqlFilter);

			$jbp_method[$c] = $method['PaymentMethod']['name'];
			$jbp_amount[$c] = $orders[0][0]['amount'] | 0;
			$jbp_jobs[$c] = $orders[0][0]['jobs'];

			$data_s .= $orders[0][0]['amount'];
			$data_s .= ",";

			$legend_s .= $method['PaymentMethod']['name'];
			$legend_s .= ",";

			$c++;
		}

		$data_s = substr($data_s, 0, -1);
		$legend_s = substr($legend_s, 0, -1);

		$this->set('jbp_amount', $jbp_amount);
		$this->set('jbp_jobs', $jbp_jobs);
		$this->set('jbp_method', $jbp_method);

		$this->set('byMethodImg', 'pieGraph?x=500&y=200&theme=ace_methods&title=Orders by Payment Methods&data=' . $data_s . '&legend=' . $legend_s);
	}

	function pieGraph() {
		//based on URL parameters
		//sample: cake/orders/pieGraph?x=500&y=200&title=Test&data=40,60&legend=good,bad

		$x = $this->params['url']['x'];
		$y = $this->params['url']['y'];
		$theme = $this->params['url']['theme'];
		$title = $this->params['url']['title'];
		$data = $this->params['url']['data'];
		$legend = $this->params['url']['legend'];

		$data = split(',', $data);
		$legend = split(',', $legend);

		$this->graph = $this->Jpgraph->pieGraph($x, $y, $title, $data, $legend, $theme);
	}

	function barGraph() {
		//based on URL parameters

		$x = $this->params['url']['x'];
		$y = $this->params['url']['y'];
		$title = $this->params['url']['title'];
		$xtitle = $this->params['url']['xtitle'];
		$ytitle = $this->params['url']['ytitle'];

		$datay = $this->params['url']['datay'];
		$datax = $this->params['url']['datax'];

		$datay = split(',', $datay);
		$datax = split(',', $datax);


		$this->graph = $this->Jpgraph->barGraph($x, $y, $title, $datay, $datax, $xtitle, $ytitle);
	} 
	
	// Payments summary
	// Created: Anthony Chernikov, 09/14/2010
	function payments()
	{
		$this->layout="list";
		if ($this->Common->getLoggedUserRoleID() != 6) return;
		
		//CONDITIONS
		//Convert date from date picker to SQL format
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
			$sqlConditions .= " AND o.job_date >= '".$this->Common->getMysqlDate($fdate)."'"; 
		if($tdate != '')
			$sqlConditions .= " AND o.job_date <= '".$this->Common->getMysqlDate($tdate)."'";
    
    $payment_type = $this->params['url']['payment_type'];
		if($payment_type != '')
			$sqlConditions .= " AND o.customer_payment_method_id = $payment_type"; 

    $job_type = $this->params['url']['job_type'];
		if($job_type != '')
			$sqlConditions .= " AND o.order_type_id = $job_type"; 

		$db =& ConnectionManager::getDataSource('default');
		$query ="
        select o.id, o.order_number, 
               c.first_name, c.last_name, c.phone, c.address, c.city,
               t.name job_type, p.name paid_by,
               sum(round(i.price*i.quantity-i.discount+i.addition,2)) sale,
               sum(round(1.12*(i.price*i.quantity-i.discount+i.addition),2)) total,
               o.customer_deposit deposit,
               o.customer_paid_amount paid
          from ace_rp_orders o
         inner join ace_rp_order_items i on o.id=i.order_id
         inner join ace_rp_users c on c.id=o.customer_id
          left outer join ace_rp_order_types t on t.id=o.order_type_id
          left outer join ace_rp_payment_methods p on p.id=o.customer_payment_method_id
         where o.order_status_id in (1,5) 
            ".$sqlConditions."
        group by o.id";
		
		//echo $query;
		$records = array();
    $num = 0;
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
        $num++;
				foreach ($row as $k => $v)
					$records[$num][$k] = $v;
		}
		
		$this->set("records", $records);
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
	}
		
	// The summary of all the cancellations
	function cancellations()
	{
		$this->layout="list";
    $sqlConditions = '';
    
    $ShowInactive = $_GET['ShowInactive'];
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		if (!$order) $order = 'o.booking_date asc';
				
		//CONDITIONS
		//Convert date from date picker to SQL format
		//Pick today's date if no date
		$fdate = ($this->params['url']['ffromdate'] != '' ? date("Y-m-d", strtotime($this->params['url']['ffromdate'])): date("Y-m-d") ) ;
		$tdate = ($this->params['url']['ftodate'] != '' ? date("Y-m-d", strtotime($this->params['url']['ftodate'])): date("Y-m-d") ) ;
		$telemid = $this->params['url']['ftelemid'];
		$officeid = $this->params['url']['fofficeid'];
		
		$db =& ConnectionManager::getDataSource('default');
    
    if ($ShowInactive) $sqlConditions_job = '';
    else $sqlConditions_job = ' and o.flagactive=1';
		if($fdate != '')
			$sqlConditions_job .= " AND o.job_date >= '".$this->Common->getMysqlDate($fdate)."'"; 
		if($tdate != '')
			$sqlConditions_job .= " AND o.job_date <= '".$this->Common->getMysqlDate($tdate)."'"; 
		if($telemid != '')
			$sqlConditions .= " AND u.id=".$telemid; 
		if($officeid != '')
			$sqlDerived .= "AND change_id = '$officeid' "; 
		if($this->Common->getLoggedUserRoleID() == 3)
			$sqlDerived .= "AND booking_source_id = '".$this->Common->getLoggedUserID()."'";

		$records = array();
		//booking_telemarketer_id;booking_source_id
		$query ="
        SELECT o.id, concat(u.first_name, ' ', u.last_name) agent_name,
               t.name order_type, o.order_number, c.phone, o.flagactive,
               o.booking_date, o.sCancelReason
          FROM ace_rp_orders o
          left join ace_rp_users c on o.customer_id=c.id 
          left join ace_rp_users u on o.booking_source_id=u.id
          left join ace_rp_order_types t on o.order_type_id=t.id
         WHERE o.order_status_id=3 and o.order_type_id not in (9,10,30) $sqlConditions_job $sqlConditions
         ORDER BY $order $sort";

		$query = "
			SELECT d.id, d.order_number, d.agent_name, d.order_type, d.phone, d.flagactive, d.booking_date, d.sCancelReason, CONCAT(u2.first_name,' ', u2.last_name) change_id, booking_source_id FROM (
				SELECT o.id, concat(u.first_name, ' ', u.last_name) agent_name,
					t.name order_type, o.order_number, c.phone, o.flagactive,
					o.booking_date, cr.name 'sCancelReason',
					(SELECT ol.change_user_id
					FROM ace_rp_orders_log ol
					WHERE ol.id = o.id
					AND ol.order_status_id != 3
					ORDER BY ol.change_date DESC
					LIMIT 1) change_id,
					o.booking_source_id booking_source_id
				FROM ace_rp_orders o
				LEFT JOIN ace_rp_users c 
				ON o.customer_id = c.id 
				LEFT JOIN ace_rp_users u
				 ON o.booking_source_id = u.id
				LEFT JOIN ace_rp_order_types t 
				ON o.order_type_id = t.id
				LEFT JOIN ace_rp_cancellation_reasons cr
				ON o.cancellation_reason = cr.id
				WHERE o.order_status_id = 3 
				AND o.order_type_id NOT IN(9,10,30) $sqlConditions_job $sqlConditions
				) d
			LEFT JOIN ace_rp_users u2
			ON change_id = u2.id
			WHERE 1 = 1
			$sqlDerived
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$records[$row['id']]['id'] = $row['id'];
			$records[$row['id']]['agent_name'] = $row['agent_name'];
			$records[$row['id']]['phone'] = $row['phone'];
			$records[$row['id']]['order_number'] = $row['order_number'];
			$records[$row['id']]['booking_date'] = $row['booking_date'];
			$records[$row['id']]['order_type'] = $row['order_type'];
			$records[$row['id']]['sCancelReason'] = $row['sCancelReason'];
			$records[$row['id']]['flagactive'] = $row['flagactive'];
			$records[$row['id']]['change_id'] = $row['change_id'];
		}
		
		$this->set("items", $records);
		$this->set("telemid", $telemid);
		if($fdate!='')
			$this->set('fdate', date("d M Y", strtotime($fdate)));
		if($tdate!='')
			$this->set('tdate', date("d M Y", strtotime($tdate)));
		$this->set('allTelemarketers', $this->Lists->Telemarketers(true));	
		$this->set('allOffice', $this->Lists->UsersByRoles(6));
		$this->set('ShowInactive', $ShowInactive);	
		$this->set('query', $query);
		$this->set('fofficeid', $fofficeid);
	}
  	
  	function getLastEstimateRecord($id)
  	{
  		$db =& ConnectionManager::getDataSource('default');
  		$query = "SELECT * FROM ace_rp_estimate_callback where order_id=".$id." order by id desc limit 1";	
  		$result = $db->_execute($query);
  		$row = mysql_fetch_array($result);
  		return $row;
  		exit();
  	}
  	function estimates(){
  		$fromTech = isset($_GET['fromTech']) ? $_GET['fromTech'] : 0;
  		$ShowInactive = $_GET['ShowInactive'];
  		$dateFilter = $_GET['dateFilter'];
  		$this->layout="list";
  		$loggedInUser = $this->Common->getLoggedUserID();
		$sqlConditions_job = ' 1 ';
		$sqlConditions_job1 = ' 1 ';
		$sqlConditions_job2 = ' 1 ';
    	$disabled = '';
		$estimate1 = array();
		$estimate2 = array();
		$records2= array();
    	//$sort_field = isset($_GET['sort_field']) ? $_GET['sort_field'] : 'estimator_name';
    	//$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] :'asc';
    	
    	if($fromTech == 1){
    		// die("here");
    		$sqlConditions_job .= " AND ".$loggedInUser." IN (o.estimator,o.booking_source_id)";
    		$sqlConditions_job1 .= " AND ".$loggedInUser." IN (o.estimator,o.booking_source_id)";
			$sqlConditions_job2 .= " AND ".$loggedInUser." IN (o.estimator,o.booking_source_id)";
			$estimator = $loggedInUser;
    		$disabled = 'disabled';
    	}
		
    	if ($ShowInactive == 'on') {
    		$sqlConditions_job .= " AND o.flagactive=0";
			$sqlConditions_job1 .= " AND o.flagactive=0";
			$sqlConditions_job2 .= " AND o.flagactive=0";
		}
    	else{ 
    		$sqlConditions_job .= " AND o.flagactive=1";
			$sqlConditions_job1 .= " AND o.flagactive=1";
			$sqlConditions_job2 .= " AND o.flagactive=1";

		}

    	$fdate = ($this->params['url']['ffromdate'] != '' ? date("Y-m-d", strtotime($this->params['url']['ffromdate'])): date("Y-m-d") ) ;
		$tdate = ($this->params['url']['ftodate'] != '' ? date("Y-m-d", strtotime($this->params['url']['ftodate'])): date("Y-m-d") ) ;
		$telemid = $this->params['url']['ftelemid'];
		$officeid = $this->params['url']['fofficeid'];
    	
    	//echo "<pre>"; print_r($_GET);
    	/* 1 = estimate date
			2=  callback date
    	*/
		if(isset($_REQUEST['dateFilter'])){
			$dateFilter	= $_REQUEST['dateFilter'];
			}
			else {
			$dateFilter=2;	
			}
				
				
				if($dateFilter==3){
					$dateFilter=2;
				}
			
		if($dateFilter != 2){
			$sqlConditions_job .= " AND DATE(o.job_date) >= '".$this->Common->getMysqlDate($fdate)."'"; 
			
			$sqlConditions_job .= " AND DATE(o.job_date) <= '".$this->Common->getMysqlDate($tdate)."'";

			$sqlConditions_job .= " AND o.estimate_callback_date IS NULL";
		}
			$sqlConditions_job1 .= " AND DATE(o.job_date) >= '".$this->Common->getMysqlDate($fdate)."'"; 
			
			$sqlConditions_job1 .= " AND DATE(o.job_date) <= '".$this->Common->getMysqlDate($tdate)."'";
			
			$sqlConditions_job1 .= " AND o.estimate_callback_date IS NULL";
	
		if($dateFilter == 2 || $dateFilter == 3 ){
	    	$sqlConditions_job .= " AND o.estimate_callback_date >= '".$fdate."'"; 
			
			$sqlConditions_job .= " AND o.estimate_callback_date <= '".$tdate."'";
		}
		$sqlConditions_job2 .= " AND o.estimate_callback_date >= '".$fdate."'"; 
			
			$sqlConditions_job2 .= " AND o.estimate_callback_date <= '".$tdate."'";
	
		if(!empty($_GET['job_type'])){
			$sqlConditions_job .= " AND o.order_type_id = ".$_GET['job_type']; 
		    $sqlConditions_job1 .= " AND o.order_type_id = ".$_GET['job_type'];
			$sqlConditions_job2 .= " AND o.order_type_id = ".$_GET['job_type'];
		}


		if(!empty($_GET['order_id'])){
			$sqlConditions_job .= " AND o.order_number = ".$_GET['order_id']; 
		   $sqlConditions_job1 .= " AND o.order_number = ".$_GET['order_id'];
			$sqlConditions_job2 .= " AND o.order_number = ".$_GET['order_id'];
		}

		if(!empty($_GET['phone'])){
			$sqlConditions_job .= " AND c.cell_phone = ".$_GET['phone']; 
		    $sqlConditions_job1 .= " AND c.cell_phone = ".$_GET['phone'];
			$sqlConditions_job2 .= " AND c.cell_phone = ".$_GET['phone'];
		}

		if(!empty($_GET['estimator'])){
			$sqlConditions_job .= " AND o.estimator = ".$_GET['estimator']; 
			$sqlConditions_job1 .= " AND o.estimator = ".$_GET['estimator'];
			$sqlConditions_job2 .= " AND o.estimator = ".$_GET['estimator'];
			$estimator = $_GET['estimator'];
		}

		if($this->Common->getShowEstimateId() == 1) {
			$sqlConditions_job .= " AND o.estimator = ".$this->Common->getLoggedUserID(); 
			$sqlConditions_job1 .= " AND o.estimator = ".$this->Common->getLoggedUserID(); 
			$sqlConditions_job2 .= " AND o.estimator = ".$this->Common->getLoggedUserID(); 
			$estimator = $this->Common->getLoggedUserID(); 
		}		

    $query = "SELECT o.id, o.modified,concat(est.first_name, ' ', est.last_name) estimator_name,concat(u.first_name, ' ', u.last_name) agent_name, concat(c.first_name, ' ',c.last_name) customer_name, o.customer_id,
               t.name order_type, o.order_number, c.phone, c.cell_phone,o.flagactive,
				o.booking_date, o.sCancelReason, o.created_date , c.city ,c.email as customer_email
          FROM ace_rp_orders o
          left join ace_rp_customers c on o.customer_id=c.id 
          left join ace_rp_users u on o.booking_source_id=u.id
          left join ace_rp_users est on o.estimator = est.id
          left join ace_rp_order_types t on o.order_type_id=t.id
             WHERE (o.job_truck in (40) OR o.payment_method_type in (11) ) AND ". $sqlConditions_job;
            $query1 = "SELECT o.id, o.modified,concat(est.first_name, ' ', est.last_name) estimator_name,concat(u.first_name, ' ', u.last_name) agent_name, concat(c.first_name, ' ',c.last_name) customer_name, o.customer_id,
               t.name order_type, o.order_number, c.phone, c.cell_phone,o.flagactive,
				o.booking_date, o.sCancelReason, o.created_date , c.city 
          FROM ace_rp_orders o
          left join ace_rp_customers c on o.customer_id=c.id 
          left join ace_rp_users u on o.booking_source_id=u.id
          left join ace_rp_users est on o.estimator = est.id
          left join ace_rp_order_types t on o.order_type_id=t.id
             WHERE (o.job_truck in (40) OR o.payment_method_type in (11) ) AND ". $sqlConditions_job1;
			 
			 $query2 = "SELECT o.id, o.modified,concat(est.first_name, ' ', est.last_name) estimator_name,concat(u.first_name, ' ', u.last_name) agent_name, concat(c.first_name, ' ',c.last_name) customer_name, o.customer_id,
               t.name order_type, o.order_number, c.phone, c.cell_phone,o.flagactive,
				o.booking_date, o.sCancelReason, o.created_date , c.city 
          FROM ace_rp_orders o
          left join ace_rp_customers c on o.customer_id=c.id 
          left join ace_rp_users u on o.booking_source_id=u.id
          left join ace_rp_users est on o.estimator = est.id
          left join ace_rp_order_types t on o.order_type_id=t.id
             WHERE (o.job_truck in (40) OR o.payment_method_type in (11) ) AND ". $sqlConditions_job2;
     	
		//  die($query);		 
		 if($fdate!='')
			$this->set('fdate', date("d M Y", strtotime($fdate)));
		if($tdate!='')
			$this->set('tdate', date("d M Y", strtotime($tdate)));

		if($telemid != '')
			$sqlConditions .= " AND u.id=".$telemid; 
		if($officeid != '')
			$sqlDerived .= "AND change_id = ".$officeid; 


		$db =& ConnectionManager::getDataSource('default');

		
		$result = $db->_execute($query);
				$result1 = $db->_execute($query1);
		$result2 = $db->_execute($query2);

		while($row1 = mysql_fetch_array($result1)) {
			
			
			$estimate1[]=$row1;
						
		}

		
		while($row2 = mysql_fetch_array($result2)) {
			$records2[$row2['id']]['modified'] = $row2['modified'];
			$records2[$row2['id']]['id'] = $row2['id'];
			$records2[$row2['id']]['estimator_name'] = $row2['estimator_name'];
			$records2[$row2['id']]['agent_name'] = $row2['agent_name'];
			$records2[$row2['id']]['customer_name'] = $row2['customer_name'];
			$records2[$row2['id']]['phone'] = $row2['phone'];
			$records2[$row2['id']]['cell_phone'] = $row2['cell_phone'];
			$records2[$row2['id']]['order_number'] = $row2['order_number'];
			$records2[$row2['id']]['booking_date'] = $row2['created_date'];
			$records2[$row2['id']]['order_type'] = $row2['order_type'];
			$records2[$row2['id']]['city'] = $row2['city'];
			$records2[$row2['id']]['sCancelReason'] = $row2['sCancelReason'];
			$records2[$row2['id']]['flagactive'] = $row2['flagactive'];
			$records2[$row2['id']]['last_call_history'] = $this->getLastCallHistory($row2['customer_id']);
			$records2[$row2['id']]['last_estimate_details'] = $this->getLastEstimateRecord($row2['order_number']);
			
		}

		while($row = mysql_fetch_array($result)) {
			$records[$row['id']]['modified'] = $row['modified'];
			$records[$row['id']]['id'] = $row['id'];
			$records[$row['id']]['estimator_name'] = $row['estimator_name'];
			$records[$row['id']]['agent_name'] = $row['agent_name'];
			$records[$row['id']]['customer_name'] = $row['customer_name'];
			$records[$row['id']]['customer_id'] = $row['customer_id'];
            $records[$row['id']]['customer_email'] = $row['customer_email'];
			$records[$row['id']]['phone'] = $row['phone'];
			$records[$row['id']]['cell_phone'] = $row['cell_phone'];
			$records[$row['id']]['order_number'] = $row['order_number'];
			$records[$row['id']]['booking_date'] = $row['created_date'];
			$records[$row['id']]['order_type'] = $row['order_type'];
			$records[$row['id']]['city'] = $row['city'];
			$records[$row['id']]['sCancelReason'] = $row['sCancelReason'];
			$records[$row['id']]['flagactive'] = $row['flagactive'];
			$records[$row['id']]['last_call_history'] = $this->getLastCallHistory($row['customer_id']);
			$records[$row['id']]['last_estimate_details'] = $this->getLastEstimateRecord($row['order_number']);
			
			/*$records[$row['id']]['change_id'] = $row['change_id'];*/
		}

		//echo "<pre>"; print_r($records); die;

		//$this->set('sort_field', $sort_field);
		//$this->set('sort_order', $sort_order);
		$this->set("items", $records);
		$this->set("records2", $records2);
		$this->set("estimate1", $estimate1);
		$this->set("estimate2", $estimate2);
		$this->set("disabled", $disabled);
		$this->set("fromTech", $fromTech);
		$this->set("dateFilter", $dateFilter);
		$this->set("telemid", $telemid);
		if($fdate!='')
			$this->set('fdate', date("d M Y", strtotime($fdate)));
		if($tdate!='')
			$this->set('tdate', date("d M Y", strtotime($tdate)));
		$this->set('allTelemarketers', $this->Lists->Telemarketers(true));	
		$this->set('allOffice', $this->Lists->UsersByRoles(6));
		$this->set('ShowInactive', $ShowInactive);	
		$this->set('query', $query);
		$this->set('fofficeid', $fofficeid);

		$this->set('job_types', $this->HtmlAssist->table2array($this->OrderType->findAll(array("OrderType.flagactive",1)), 'id', 'name'));
		$this->set('call_results', $this->HtmlAssist->table2array($this->CallResult->findAll(), 'id', 'name'));
		$this->set('allTechnician',$this->Lists->Technicians(true));

		
		// $this->set('booking_source_id', $_GET['estimator']);
		$this->set('phone', $_GET['phone']);
		$this->set('order_id', $_GET['order_id']);
		$this->set('booking_source_id', $estimator);

		$this->set('job_type_id', $_GET['job_type']);		
		$this->set('booking_sources', $this->Lists->BookingSources());
  	}

  	function getLastCallHistory($customer_id){
			
		//Telemarketers will not see 'Answering Machine' results
		$ans = '';
		if (($this->Common->getLoggedUserRoleID() == 3)
			||($this->Common->getLoggedUserRoleID() == 13)
			||($this->Common->getLoggedUserRoleID() == 9))
			$ans = 'call_result_id!=6 and';

		$users=$this->Lists->BookingSources();
		$call_results=$this->Lists->ListTable('ace_rp_call_results');

		if ($customer_id) $query = "select * from ace_rp_call_history where ".$ans." customer_id='".$customer_id."'";

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

		return $but_value;
		
	}

  	function estimates1()
	{
		$this->layout="list";
    $sqlConditions = '';
    
    $ShowInactive = $_GET['ShowInactive'];
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		if (!$order) $order = 'o.booking_date asc';
				
		//CONDITIONS
		//Convert date from date picker to SQL format
		//Pick today's date if no date
		$fdate = ($this->params['url']['ffromdate'] != '' ? date("Y-m-d", strtotime($this->params['url']['ffromdate'])): date("Y-m-d") ) ;
		$tdate = ($this->params['url']['ftodate'] != '' ? date("Y-m-d", strtotime($this->params['url']['ftodate'])): date("Y-m-d") ) ;
		$telemid = $this->params['url']['ftelemid'];
		$officeid = $this->params['url']['fofficeid'];
		
		$db =& ConnectionManager::getDataSource('default');
    
    if ($ShowInactive) $sqlConditions_job = '';
    else $sqlConditions_job = ' and o.flagactive=1';
		if($fdate != '')
			$sqlConditions_job .= " AND DATE(o.created_date) >= '".$this->Common->getMysqlDate($fdate)."'"; 
		if($tdate != '')
			$sqlConditions_job .= " AND DATE(o.created_date) <= '".$this->Common->getMysqlDate($tdate)."'"; 
		if($telemid != '')
			$sqlConditions .= " AND u.id=".$telemid; 
		if($officeid != '')
			$sqlDerived .= "AND change_id = '$officeid' "; 
		if($this->Common->getLoggedUserRoleID() == 3)
			$sqlDerived .= "AND booking_source_id = '".$this->Common->getLoggedUserID()."'";

		$records = array();
		//booking_telemarketer_id;booking_source_id
		$query ="
        SELECT o.id, concat(u.first_name, ' ', u.last_name) agent_name,
               t.name order_type, o.order_number, c.phone, o.flagactive,
               o.booking_date, o.sCancelReason
          FROM ace_rp_orders o
          left join ace_rp_users c on o.customer_id=c.id 
          left join ace_rp_users u on o.booking_source_id=u.id
          left join ace_rp_order_types t on o.order_type_id=t.id
         WHERE o.order_status_id=3 and o.order_type_id not in (9,10,30) $sqlConditions_job $sqlConditions
         ORDER BY $order $sort";

		$query = "
			SELECT d.id, d.order_number, d.agent_name, d.order_type, d.phone, d.flagactive, d.created_date, d.sCancelReason, CONCAT(u2.first_name,' ', u2.last_name) change_id, booking_source_id FROM (
				SELECT o.id, concat(u.first_name, ' ', u.last_name) agent_name,
					t.name order_type, o.order_number, c.phone, o.flagactive,
					o.created_date, cr.name 'sCancelReason',
					(SELECT ol.change_user_id
					FROM ace_rp_orders_log ol
					WHERE ol.id = o.id
					AND ol.order_status_id != 3
					ORDER BY ol.change_date DESC
					LIMIT 1) change_id,
					o.booking_source_id booking_source_id
				FROM ace_rp_orders o
				LEFT JOIN ace_rp_users c 
				ON o.customer_id = c.id 
				LEFT JOIN ace_rp_users u
				 ON o.booking_source_id = u.id
				LEFT JOIN ace_rp_order_types t 
				ON o.order_type_id = t.id
				LEFT JOIN ace_rp_cancellation_reasons cr
				ON o.cancellation_reason = cr.id
				WHERE o.order_status_id = 8 
				$sqlConditions_job $sqlConditions
				) d
			LEFT JOIN ace_rp_users u2
			ON change_id = u2.id
			WHERE 1 = 1
			$sqlDerived
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$records[$row['id']]['id'] = $row['id'];
			$records[$row['id']]['agent_name'] = $row['agent_name'];
			$records[$row['id']]['phone'] = $row['phone'];
			$records[$row['id']]['order_number'] = $row['order_number'];
			$records[$row['id']]['booking_date'] = $row['created_date'];
			$records[$row['id']]['order_type'] = $row['order_type'];
			$records[$row['id']]['sCancelReason'] = $row['sCancelReason'];
			$records[$row['id']]['flagactive'] = $row['flagactive'];
			$records[$row['id']]['change_id'] = $row['change_id'];
		}
		
		$this->set("items", $records);
		$this->set("telemid", $telemid);
		if($fdate!='')
			$this->set('fdate', date("d M Y", strtotime($fdate)));
		if($tdate!='')
			$this->set('tdate', date("d M Y", strtotime($tdate)));
		$this->set('allTelemarketers', $this->Lists->Telemarketers(true));	
		$this->set('allOffice', $this->Lists->UsersByRoles(6));
		$this->set('ShowInactive', $ShowInactive);	
		$this->set('query', $query);
		$this->set('fofficeid', $fofficeid);
	}
  
	function payments_summary()
	{
		$this->layout="list";
		if ($this->Common->getLoggedUserRoleID() != 6) return;
    
	    $allPaymentMethods = $this->Lists->paymenTable('ace_rp_payment_methods');
	    $allTechnicians = $this->Lists->Technicians();
    
		//CONDITIONS
		//Convert date from date picker to SQL format
		if ($this->params['url']['ffromdate'] != '')
			$fdate = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
	    else
				// $fdate = date("Y-m-d");
	    		$fdate = date('Y-m-d',strtotime("-1 days"));


			if ($this->params['url']['ftodate'] != '')
				$tdate = date("Y-m-d", strtotime($this->params['url']['ftodate']));
	    else
				//$tdate = date("Y-m-d");
	    	$tdate = date('Y-m-d',strtotime("-1 days"));

		$db =& ConnectionManager::getDataSource('default');
		$sqlConditions = "";
		if($fdate != '')
			$sqlConditions .= " AND o.job_date >= '".$this->Common->getMysqlDate($fdate)."'"; 
		if($tdate != '')
			$sqlConditions .= " AND o.job_date <= '".$this->Common->getMysqlDate($tdate)."'";
    
		$records = array();
		$recordsTotal = array();
		$orders = array();

    // $query ="
    //      select o.id as orderId, o.job_date, m.id payment_method_id, m.name payment_method,
    //             p.paid_amount as paid_amount,o.id, o.order_number, p.paid_amount,
				// 		 o.job_technician1_id, o.job_technician2_id
    //        from ace_rp_payments p
    //       right join ace_rp_orders o on p.idorder=o.id
    //        left outer join ace_rp_payment_methods m on m.id=p.payment_method
    //       where o.id IS NOT NULL $sqlConditions";
		$query ="
         select o.id as orderId, o.order_status_id, o.job_date, o.payment_method_type payment_method_id, o.confirm_payment,o.booking_source_id, o.job_technician1_id,o.job_technician2_id, o.booking_source2_id, o.order_number ,m.name payment_method, p.paid_amount as paid_amount,o.id, o.order_number, p.paid_amount, o.job_technician1_id, o.job_technician2_id , cu.first_name as user_first_name, cu.last_name as user_last_name from ace_rp_payments p right join ace_rp_orders o on p.idorder=o.id left outer join ace_rp_payment_methods m on m.id=o.payment_method_type LEFT JOIN	`ace_rp_users` as cu on cu.id = (SELECT user_id FROM  `ace_rp_orders_comm` WHERE  `order_id` = o.id AND id = (SELECT MAX( id ) FROM ace_rp_orders_comm WHERE order_id =o.id )) where o.id IS NOT NULL $sqlConditions";
          // print_r($query); die;
	    $result = $db->_execute($query);
	    $i = 0;
	    while($row = mysql_fetch_array($result))
	    {    	
	    	$orders[$i] = $row;
	    	$comm = $this->requestAction('/commissions/getForOrder/'.$orders[$i]['orderId']);
	    	
				$tech1_comm = $comm[0][1]['total_comm'];
				$tech2_comm = $comm[0][2]['total_comm'];
				if ($orders[$i]['booking_source_id']==$orders[$i]['job_technician1_id'])
					$tech1_comm += $comm[0][3]['total_comm'];
				elseif ($orders[$i]['booking_source_id']==$orders[$i]['job_technician2_id'])
					$tech2_comm += $comm[0][3]['total_comm'];
				elseif ($orders[$i]['booking_source2_id']==$orders[$i]['job_technician1_id'])
					$tech1_comm += $comm[0][4]['total_comm'];
				elseif ($orders[$i]['booking_source2_id']==$orders[$i]['job_technician2_id'])
					$tech2_comm += $comm[0][4]['total_comm'];
				$orders[$i]['tech_comm1'] = round($tech1_comm, 2);
				$orders[$i]['tech_comm2'] = round($tech2_comm, 2);		
				$orders[$i]['comm_link']  = BASE_URL."/commissions/calculateCommissions?cur_ref=".$orders[$i]['order_number'];
				$orders[$i]['user_name']  = $orders[$i]['user_first_name'].' '.$orders[$i]['user_last_name'];
	    	$i++;
	    }

        ksort($orders);
   		$this->set("orders", $orders);    
		$this->set("recordsTotal", $recordsTotal);
		$this->set("allPaymentMethods", $allPaymentMethods);
		$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));
		$this->set('next_fdate', date("d M Y", strtotime($fdate) + 24*60*60));
		$this->set('prev_tdate', date("d M Y", strtotime($tdate) - 24*60*60));
		$this->set('next_tdate', date("d M Y", strtotime($tdate) + 24*60*60));
		$this->set('fdate', date("d M Y", strtotime($fdate)));
		$this->set('tdate', date("d M Y", strtotime($tdate)));
		$this->set("allTechnicians", $allTechnicians);
	}
	
	function sendMsg()
	{
		$text = $_POST['text'];
		$db =& ConnectionManager::getDataSource('default');
		$db->_execute("replace INTO ace_rp_board (date,text) VALUES (current_date(),'$text')");
		exit;
	}
	
	function changeDisplayMode()
	{
			
		$d = $_REQUEST['disp'];		
		$db =& ConnectionManager::getDataSource('default');
		$query = "update ace_rp_settings set valuetxt = $d where id = 15";
		$db->_execute($query);
		//$this->redirect('http://192.168.2.150/acesys/acetest/acesys-2.0/index.php/reports/telem_board');	
	
	}

	function getcallhistory(){
		
		$db =& ConnectionManager::getDataSource('default');
		$orderId = $_REQUEST['orderId'];
		$query = "select customer_id,customer_phone from ace_rp_orders where id='$orderId'";
		$result = $db->_execute($query);
		
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$customer_id=$row['customer_id'];
			
			$phone=$row['customer_phone'];

		}
	
            $isDialer = 1;
			
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
//      else if ($phone) $query = "select * from ace_rp_call_history where ".$ans." customer_id in (select id from ace_rp_users where phone='".$phone."')";
        else if ($phone) $query = "select * from ace_rp_call_history where ".$ans." cell_phone='".$phone."'";

        if($isDialer == 1)
        {
            $query .= " order by call_date desc, call_time desc";
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
	
	function filters() {
		//Convert date from date picker to SQL format
		if ($this->params['url']['ffromdate'] != '')
			$this->params['url']['ffromdate'] = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
		if ($this->params['url']['ftodate'] != '')
			$this->params['url']['ftodate'] = date("Y-m-d", strtotime($this->params['url']['ftodate']));
		
		//Pick today's date if no date
		$fdate = ($this->params['url']['ffromdate'] != '' ? $this->params['url']['ffromdate']: date("Y-m-d") ) ;
		$tdate = ($this->params['url']['ftodate'] != '' ? $this->params['url']['ftodate']: date("Y-m-d") ) ;
		$weekday = date('w',strtotime($fdate));		
		
		$sql = "
			SELECT o.id, o.order_number, o.job_date, os.name status, i.name filter, 
			CONCAT(u.first_name, ' ', u.last_name) tech1, 
			CONCAT(u2.first_name, ' ', u2.last_name) tech2
			FROM ace_rp_order_items oi
			LEFT JOIN ace_rp_orders o
			ON oi.order_id = o.id
			LEFT JOIN ace_rp_items i
			ON oi.item_id = i.id
			LEFT JOIN ace_rp_users u
			ON o.job_technician1_id = u.id
			LEFT JOIN ace_rp_users u2
			ON o.job_technician2_id = u2.id
			LEFT JOIN ace_rp_order_statuses os
			ON o.order_status_id = os.id
			WHERE i.id IN(600,601,602,603,604)
			AND o.job_date BETWEEN '".$this->Common->getMysqlDate($fdate)."' AND '".$this->Common->getMysqlDate($tdate)."'
		";
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$result = $db->_execute($sql);		

		
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)			
			
			$cust_temp[$row['id']]['id']= $row['id'];
			$cust_temp[$row['id']]['order_number']= $row['order_number'];
			$cust_temp[$row['id']]['tech1']= $row['tech1'];
			$cust_temp[$row['id']]['tech2']= $row['tech2'];			
			$cust_temp[$row['id']]['job_date']= $row['job_date'];
			$cust_temp[$row['id']]['status']= $row['status'];
			$cust_temp[$row['id']]['filter']= $row['filter'];

		}
		
		$this->set('customers', $cust_temp);
		$this->set('fdate', date("d M Y", strtotime($fdate)));
		$this->set('tdate', date("d M Y", strtotime($tdate)));		
	}
	
	// function salesRecording()
	// {
	// 	$this->layout="list";
 //    	$sqlConditions = '';
    
 //    $ShowInactive = $_GET['ShowInactive'];
	// 	$sort = $_GET['sort'];
	// 	$order = $_GET['order'];
	// 	if (!$order) $order = 'o.booking_date asc';
				
	// 	//CONDITIONS
	// 	//Convert date from date picker to SQL format
	// 	//Pick today's date if no date
	// 	$fdate = ($this->params['url']['ffromdate'] != '' ? date("Y-m-d", strtotime($this->params['url']['ffromdate'])): date("Y-m-d") ) ;
	// 	$tdate = ($this->params['url']['ftodate'] != '' ? date("Y-m-d", strtotime($this->params['url']['ftodate'])): date("Y-m-d") ) ;
	// 	$telemid = $this->params['url']['ftelemid'];
	// 	$disposition = $this->params['url']['disposition'];
		
		
	// 	//vicidial connection
	// 	$db =& ConnectionManager::getDataSource('vicidial');
    

	// 	if($telemid != '')
	// 		$sqlConditions .= " AND u.user=".$telemid; 
			
	// 	if($disposition != '')
	// 		$sqlDisposition .= " AND al.status='".$disposition."'";

	// 	$records = array();
	// 	//booking_telemarketer_id;booking_source_id
	// 	$query ="
 //        	SELECT u.full_name, l.phone_number, rl.start_time event_time, al.status, rl.location 
	// 		FROM recording_log rl
	// 		LEFT JOIN vicidial_agent_log al
	// 		ON rl.lead_id = al.lead_id
	// 		LEFT JOIN vicidial_users u
	// 		ON u.user = rl.user
	// 		LEFT JOIN vicidial_list l
	// 		ON l.lead_id = al.lead_id
	// 		WHERE al.status IS NOT NULL
	// 		AND l.phone_number IS NOT NULL			
	// 		AND DATE(rl.start_time) >= '".$this->Common->getMysqlDate($fdate)."'
	// 		AND DATE(rl.start_time) <= '".$this->Common->getMysqlDate($tdate)."'
	// 		$sqlDisposition
	// 		$sqlConditions
	// 		ORDER BY rl.start_time DESC
	// 		";

	// 	$result = $db->_execute($query);
	// 	while($row = mysql_fetch_array($result)) {
	// 		$records[$row['location']]['full_name'] = $row['full_name'];
	// 		$records[$row['location']]['phone_number'] = $row['phone_number'];
	// 		$records[$row['location']]['event_time'] = $row['event_time'];    
	// 		$records[$row['location']]['status'] = $row['status'];   
	// 		$records[$row['location']]['location'] = $row['location'];  
	// 	}
		
	// 	$this->set("items", $records);
	// 	$this->set("telemid", $telemid);
	// 	if($fdate!='')
	// 		$this->set('fdate', date("d M Y", strtotime($fdate)));
	// 	if($tdate!='')
	// 		$this->set('tdate', date("d M Y", strtotime($tdate)));
	// 	$this->set('allTelemarketers', $this->Lists->Telemarketers(true));
	// 	$this->set('vicidialStatuses', $this->Lists->VicidialStatuses());
	// 	$this->set('ShowInactive', $ShowInactive);	
	// }
	
	function graphDone() {
		$this->layout="html5";
		
		$db =& ConnectionManager::getDataSource('default');
		
		$pay_period = $this->params['url']['pay_period'];
		if (!$pay_period)
		{
			$query = "select * from ace_rp_pay_periods where current_date() between start_date and end_date and period_type=2";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				$pay_period = $row['id'];
		}
		
		$query = "
			SELECT u.id, u.first_name, 
				u.last_name,
				SUM(IF(o.order_status_id = 3, 1, 0)) cancelled,
				SUM(IF(o.order_status_id = 5, 1, 0)) done,
				SUM(IF(o.order_status_id = 1, 1, 0)) booked
			FROM ace_rp_users u 
			LEFT JOIN ace_rp_orders o 
			ON o.booking_source_id = u.id
			LEFT JOIN ace_rp_groups_users m
			ON m.user_id = u.id
			LEFT JOIN ace_rp_groups g
			ON g.id = m.group_id
			WHERE EXISTS (
				SELECT * 
				FROM ace_rp_users_roles r 
				WHERE r.user_id = u.id 
				AND r.role_id in (3,9)
				)
			AND u.is_active=1 
			AND EXISTS (
				SELECT * 
				FROM ace_rp_pay_periods p
				WHERE (
					(o.job_date between p.start_date AND p.end_date) 
					OR 
					(o.booking_date BETWEEN p.start_date AND p.end_date)
					)
				AND p.id=$pay_period
				)
			AND o.order_type_id NOT IN(1, 15) 
			GROUP BY u.id
			ORDER BY u.first_name
		";		
		 
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) 
		{
			$graph[$row['id']]['id'] = $row['id'];
			$graph[$row['id']]['name'] = strtoupper($row['first_name']?$row['first_name']:$row['last_name']);
			$graph[$row['id']]['cancelled'] = $row['cancelled'];
			$graph[$row['id']]['done'] = $row['done'];
			$graph[$row['id']]['booked'] = $row['booked'];
		}
		
		$this->set('allPayPeriods', $this->Lists->PayPeriods(2));
		$this->set("pay_period", $pay_period);
		$this->set('graph', $graph);
	}
	
	function telemVsTech()
	{
		$this->layout="list";
    
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		if (!$order) $order = 'u.first_name asc';
				
		$pay_period = $this->params['url']['pay_period'];
		
		$db =& ConnectionManager::getDataSource('default');
		
		if($pay_period > 0) {
			$result = $db->_execute("
				SELECT * 
				FROM ace_rp_pay_periods 
				WHERE id = $pay_period
				LIMIT 1
			");
			if($row = mysql_fetch_array($result)) {
				$fdate = $row['start_date'];
				$tdate = $row['end_date'];
			}
		} else {
				
			//CONDITIONS
			//Convert date from date picker to SQL format
			if ($this->params['url']['ffromdate'] != '')
				$this->params['url']['ffromdate'] = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
	
			if ($this->params['url']['ftodate'] != '')
				$this->params['url']['ftodate'] = date("Y-m-d", strtotime($this->params['url']['ftodate']));
			
			//Pick today's date if no date
			$fdate = ($this->params['url']['ffromdate'] != '' ? $this->params['url']['ffromdate']: date("Y-m-d") ) ;
			$tdate = ($this->params['url']['ftodate'] != '' ? $this->params['url']['ftodate']: date("Y-m-d") ) ;
			$userid = $this->params['url']['fuserid'];
    
		}
	

		$records = array();
		// Jobs done
		
		$userid = $userid==""?"u.id":$userid;
		
		$query ="
			SELECT o.id, o.order_number, o.job_date, 
				u.first_name telem,
				us.first_name source,
				ut1.first_name tech1,
				ut2.first_name tech2,
				uo.first_name office,
				o.office_rating,
				CONCAT(o.job_time_beg,'-',o.job_time_end) job_time,
				uc.city,
				o.telem_rating, o.telem_comment
			FROM ace_rp_orders o
			LEFT JOIN ace_rp_users u
			ON o.booking_telemarketer_id = u.id 
			LEFT JOIN ace_rp_users us
			ON o.booking_source_id = us.id 
			LEFT JOIN ace_rp_users ut1
			ON o.job_technician1_id = ut1.id 
			LEFT JOIN ace_rp_users ut2
			ON o.job_technician2_id = ut2.id
			LEFT JOIN ace_rp_users uc
			ON o.customer_id = uc.id 
			LEFT JOIN ace_rp_users uo
			ON o.verified_by_id = uo.id 
			WHERE job_date BETWEEN '$fdate' AND '$tdate'
			AND o.order_status_id = 5
			AND us.id = $userid
			ORDER BY us.first_name, ut1.first_name
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$records[$row['id']]['id'] = $row['id'];
			$records[$row['id']]['job_date'] = $row['job_date'];
			$records[$row['id']]['order_number'] = $row['order_number'];
			$records[$row['id']]['telem'] = $row['telem'];
			$records[$row['id']]['source'] = $row['source'];
			$records[$row['id']]['tech1'] = $row['tech1'];
			$records[$row['id']]['tech2'] = $row['tech2'];
			$records[$row['id']]['office'] = $row['office'];
			$records[$row['id']]['job_time'] = $row['job_time'];
			$records[$row['id']]['city'] = $row['city'];
			$records[$row['id']]['office_rating'] = $row['telem_rating']==1?"BAD":$row['telem_rating']==2?"GOOD":$row['telem_rating']==3?"EXCELLENT":"NO RATING";
			$records[$row['id']]['telem_rating'] = $row['telem_rating']==1?"BAD":$row['telem_rating']==2?"GOOD":$row['telem_rating']==3?"EXCELLENT":"NO RATING";
			$records[$row['id']]['telem_comment'] = $row['telem_comment'];
		}
    
		
		$this->set("items", $records);
		$this->set("userid", $userid);
		if($fdate!='')
			$this->set('fdate', date("d M Y", strtotime($fdate)));
		if($tdate!='')
			$this->set('tdate', date("d M Y", strtotime($tdate)));
		$this->set('allTelemarketers',$this->Lists->Telemarketers());	
		$this->set('allJobTypes', $allJobTypes);
		$this->set('allPayPeriods', $this->Lists->PayPeriods(1));
	}
	
	function technicians_feedback()
	{
		$this->layout="list";
		$loggedUserId = 0;
		$sqlConditions = '';
    
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		if (!$order) $order = 'u.first_name asc';
				
		$pay_period = $this->params['url']['pay_period'];
		
		$db =& ConnectionManager::getDataSource('default');
		
		if($pay_period > 0) {
			$result = $db->_execute("
				SELECT * 
				FROM ace_rp_pay_periods 
				WHERE id = $pay_period
				LIMIT 1
			");
			if($row = mysql_fetch_array($result)) {
				$fdate = $row['start_date'];
				$tdate = $row['end_date'];
			}
		} else {
			$pay_period = $this->params['url']['pay_period'];

			$query = "select * from ace_rp_pay_periods where current_date() between start_date and end_date and period_type=1";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {				
				$fdate = $row['start_date'];
				$tdate = $row['end_date'];
			}
    
		}
	
		$allJobTypes = $this->Lists->ListTable('ace_rp_order_types');
		$allTechnician = $this->Lists->Technicians();
		
		
    $sqlConditions_job = '';
    $sqlConditions_job2 = '';
		if($fdate != '')
    {
			$sqlConditions_job .= " AND job_date >= '".$this->Common->getMysqlDate($fdate)."'"; 
			$sqlConditions_job2 .= " AND booking_date >= '".$this->Common->getMysqlDate($fdate)."'"; 
    }
		if($tdate != '')
    {
			$sqlConditions_job .= " AND job_date <= '".$this->Common->getMysqlDate($tdate)."'"; 
			$sqlConditions_job2 .= " AND booking_date <= '".$this->Common->getMysqlDate($tdate)."'"; 
    }
		if($techid != '')
			$sqlConditions .= " and u.id=".$techid; 

		$records = array();
		// Jobs done
		$query ="
        SELECT u.id, u.first_name, u.last_name,
               sum(if(o.job_technician1_id>0 and o.job_technician2_id>0 and o.job_technician1_id is not null and o.job_technician2_id is not null,0.5,1)) jobs_done,
               sum((select sum(i.price*i.quantity-i.discount+i.addition) from ace_rp_order_items i where o.id=i.order_id and i.class=0)) booking,
               sum((select sum(i.price*i.quantity-i.discount+i.addition) from ace_rp_order_items i where o.id=i.order_id and i.class=1)) sales
          FROM ace_rp_users u, ace_rp_orders o 
         WHERE o.order_status_id=5 $sqlConditions $sqlConditions_job
           and (o.job_technician1_id=u.id or o.job_technician2_id=u.id)
         group by u.id
         ORDER BY $order $sort";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$records[$row['id']]['id'] = $row['id'];
			$records[$row['id']]['name'] = $row['first_name'].' '.$row['last_name'];
			$records[$row['id']]['jobs_done'] = $row['jobs_done'];
			$records[$row['id']]['booking'] = round($row['booking'],2);
			$records[$row['id']]['sales'] = round($row['sales'],2);
		}
    
		// Jobs booked
		$query ="
        SELECT u.id, u.first_name, u.last_name,
               sum(if((o.order_type_id!=9 and o.order_type_id!=10),if(o.booking_source_id>0 and o.booking_source2_id>0 and o.booking_source_id is not null and o.booking_source2_id is not null,0.5,1),0)) jobs_booked,
               sum(if(o.order_type_id=9,if(o.booking_source_id>0 and o.booking_source2_id>0 and o.booking_source_id is not null and o.booking_source2_id is not null,0.5,1),0)) complaints,
               sum(if(o.order_type_id=10,if(o.booking_source_id>0 and o.booking_source2_id>0 and o.booking_source_id is not null and o.booking_source2_id is not null,0.5,1),0)) followups,
               sum((select sum(i.quantity) from ace_rp_order_items i, ace_rp_items t where t.id=i.item_id and t.is_appliance=1 and o.id=i.order_id and i.class=0)) appliances_cnt,
               sum((select sum(i.price*i.quantity-i.discount+i.addition) from ace_rp_order_items i, ace_rp_items t where t.id=i.item_id and t.is_appliance=1 and o.id=i.order_id and i.class=0)) appliances,
               sum((select sum(i.price*i.quantity-i.discount+i.addition) from ace_rp_order_items i where o.id=i.order_id and i.class=0)) all_booking,
			   sum(if(o.feedback_quality = 'EXCELLENT',if(o.booking_source_id>0 and o.booking_source2_id>0 and o.booking_source_id is not null and o.booking_source2_id is not null,0.5,1),0)) excellent,
			   sum(if(o.feedback_quality = 'GOOD',if(o.booking_source_id>0 and o.booking_source2_id>0 and o.booking_source_id is not null and o.booking_source2_id is not null,0.5,1),0)) good,
			   sum(if(o.feedback_quality = 'BAD',if(o.booking_source_id>0 and o.booking_source2_id>0 and o.booking_source_id is not null and o.booking_source2_id is not null,0.5,1),0)) bad,
			   sum(o.is_door_hanger) door_hanger,
			   sum(o.insurable) insurable	   
          FROM ace_rp_users u, ace_rp_orders o 
         WHERE (o.booking_source_id=u.id or o.booking_source2_id=u.id) $sqlConditions $sqlConditions_job2
           and exists (select * from ace_rp_users_roles r where r.user_id=u.id and r.role_id=1)
         group by u.id
         ORDER BY $order $sort";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$records[$row['id']]['id'] = $row['id'];
			$records[$row['id']]['name'] = $row['first_name'].' '.$row['last_name'];
			$records[$row['id']]['jobs_booked'] = $row['jobs_booked'];
			$records[$row['id']]['complaints'] = $row['complaints'];
			$records[$row['id']]['followups'] = $row['followups'];
			$records[$row['id']]['jobs_booked'] = $row['jobs_booked'];
			$records[$row['id']]['door_hanger'] = $row['door_hanger'];
			$records[$row['id']]['insurable'] = $row['insurable'];
			$records[$row['id']]['extra_booking'] = round($row['all_booking'],2);
			$records[$row['id']]['extra_booking_appliances'] = round($row['appliances'],2);
			$records[$row['id']]['appliances_cnt'] = $row['appliances_cnt'];
			$records[$row['id']]['excellent'] = $row['excellent'];
			$records[$row['id']]['good'] = $row['good'];
			$records[$row['id']]['bad'] = $row['bad'];
		}
		
		$this->set("items", $records);
		$this->set("techid", $techid);
		if($fdate!='')
			$this->set('fdate', date("d M Y", strtotime($fdate)));
		if($tdate!='')
			$this->set('tdate', date("d M Y", strtotime($tdate)));
		$this->set('allTechnician',$allTechnician);	
		$this->set('allJobTypes', $allJobTypes);
		$this->set('allPayPeriods', $this->Lists->PayPeriods(1));
	}
	
	// function unattendedCallbacks() {
	// 	$this->layout='edit';
	// 	$db =& ConnectionManager::getDataSource("vicidial");	
	// 	$query = "
	// 		SELECT l.phone_number, c.campaign_id, c.status, c.callback_time, c.comments
	// 		FROM vicidial_callbacks c
	// 		LEFT JOIN vicidial_list l
	// 		ON c.lead_id = l.lead_id
	// 		LEFT JOIN vicidial_users u
	// 		ON u.user = c.user		
	// 		WHERE l.lead_id IS NOT NULL
	// 		AND l.phone_number IS NOT NULL 
	// 		AND c.status IN('ACTIVE','LIVE')
	// 		AND l.status IN ('CBHOLD')
	// 		AND CURDATE() >= c.callback_time
	// 		AND u.active = 'N'
	// 		AND l.phone_number NOT IN(SELECT * FROM active_callbacks)
	// 		GROUP BY l.phone_number
	// 		ORDER BY c.status DESC
	// 	";
		
	// 	$result = $db->_execute($query);
		
	// 	while($row = mysql_fetch_array($result)) {
	// 		$records[$row['phone_number']]['phone_number'] = $row['phone_number'];
	// 		$records[$row['phone_number']]['campaign_id'] = $row['campaign_id'];
	// 		$records[$row['phone_number']]['status'] = $row['status'];
	// 		$records[$row['phone_number']]['callback_time'] = $row['callback_time'];
	// 		$records[$row['phone_number']]['comments'] = $row['comments'];			
	// 	}
		
	// 	$this->set('records', $records);
	// }
	
	function auditBookings() {
		$this->layout = 'blank';
		
		$db =& ConnectionManager::getDataSource("default");	
		$query = "
			SELECT o.id, o.order_number, o.created, o.job_date, os.name order_status, u1.first_name booked_by, u2.first_name source
			FROM ace_rp_orders o
			LEFT JOIN ace_rp_users u1
			ON o.booking_telemarketer_id = u1.id
			LEFT JOIN ace_rp_users u2
			ON o.booking_source_id = u2.id
			LEFT JOIN ace_rp_order_statuses os
			ON os.id = o.order_status_id
			WHERE o.booking_telemarketer_id != o.booking_source_id
			AND o.job_date BETWEEN '2011-10-13' AND '2011-10-28'
			AND o.booking_source_id = 65296
			AND o.order_status_id IN(1,5)
		";	
		
		$query = "
			SELECT o.id, o.order_number, o.created, o.job_date, os.name order_status, u1.first_name booked_by, u2.first_name source
			FROM ace_rp_orders o
			LEFT JOIN ace_rp_users u1
			ON o.booking_telemarketer_id = u1.id
			LEFT JOIN ace_rp_users u2
			ON o.booking_source_id = u2.id
			LEFT JOIN ace_rp_order_statuses os
			ON os.id = o.order_status_id
			WHERE o.booking_telemarketer_id != o.booking_source_id
			AND o.job_date BETWEEN '2011-10-13' AND '2011-10-28'			
			AND o.order_status_id IN(1,5)
		";	
		
		$result = $db->_execute($query);
		
		while($row = mysql_fetch_array($result)) {
			$bookings[$row['id']]['order_number'] = $row['order_number'];
			$bookings[$row['id']]['created'] = $row['created'];
			$bookings[$row['id']]['job_date'] = $row['job_date'];
			$bookings[$row['id']]['order_status'] = $row['order_status'];
			$bookings[$row['id']]['booked_by'] = $row['booked_by'];
			$bookings[$row['id']]['source'] = $row['source'];
		}
		
		$this->set('bookings', $bookings);
	}
	
	function updateDistanceInfo() {
		$this->layout = 'blank';
		
		$db =& ConnectionManager::getDataSource("default");	
		
		$query = "
			SELECT *
			FROM ace_rp_postal_codes
			ORDER BY postal_code
		";	
		
		$result = $db->_execute($query);
		
		$i = 0;
		while($row = mysql_fetch_array($result)) {
			$results[$i++] = $row['postal_code'];			
		}		
		
		$this->set('results', $results);		
	}
	
	function checkGoogleMaps() {
		$this->layout = 'blank';
		$from = $_GET['from'];
		$to = $_GET['to'];
		//$from = "v5c";
		//$to = "v5w";
		$json = file_get_contents("http://maps.google.com/maps/nav?q=from:$from%20to:$to");		
		//$json = file_get_contents('http://maps.google.com/maps/nav?q=from:London%20to:Dover');
		$this->set('json', $json);		
	}
	
	function saveDistanceInfo() {
		$this->layout = 'blank';
		$from = $_POST['from'];
		$to = $_POST['to'];
		$meters = $_POST['meters'];
		$seconds = $_POST['seconds'];
		
		/*$from = "qwe";
		$to = "ert";
		$meters = 12;
		$seconds = 14;*/
		
		$db =& ConnectionManager::getDataSource("default");	
		
		$query = "
			DELETE
			FROM ace_rp_postal_distances
			WHERE `from` = '$from'
			AND `to` = '$to'			
		";	
		
		$result = $db->_execute($query);
		
		$query = "
			INSERT INTO ace_rp_postal_distances(`from`, `to`, meters, seconds)
			VALUES('$from', '$to', $meters, $seconds)			
		";	
		
		$result = $db->_execute($query);
		
		echo "OK";	
	}
	
	function customerFeedbacks() {
		$this->layout="list";
		
		$fdate = ($this->params['url']['ffromdate'] != '' ? date("Y-m-d", strtotime($this->params['url']['ffromdate'])): date("Y-m-d") ) ;
		$tdate = ($this->params['url']['ftodate'] != '' ? date("Y-m-d", strtotime($this->params['url']['ftodate'])): date("Y-m-d") ) ;
		
		$db =& ConnectionManager::getDataSource("default");	
		
		$query = "
			SELECT f.*, CONCAT(u.first_name, ' ', u.last_name) customer_name, u.phone, 
				(SELECT id FROM ace_rp_orders WHERE order_status_id = 5 AND order_number = f.order_number LIMIT 1) order_id
			FROM ace_rp_feedbacks f 
			LEFT JOIN ace_rp_users u
			ON f.customer_id = u.id
			WHERE DATE(f.comment_date) BETWEEN '$fdate' AND '$tdate'			
		";	
		
		$result = $db->_execute($query);
		
		$i = 0;
		while($row = mysql_fetch_array($result)) {
			$results[$row['id']]['comment'] = $row['comment'];
			$results[$row['id']]['comment_type'] = $row['comment_type'];
			$results[$row['id']]['customer_id'] = $row['customer_id'];
			$results[$row['id']]['customer_name'] = $row['customer_name'];
			$results[$row['id']]['customer_phone'] = $row['phone'];
			$results[$row['id']]['order_id'] = $row['order_id'];
			$results[$row['id']]['order_number'] = $row['order_number'];
			$results[$row['id']]['rating'] = $row['rating'];
			$results[$row['id']]['comment_date'] = $row['comment_date'];
		}		
		
		$this->set('results', $results);
		if($fdate!='')
			$this->set('fdate', date("d M Y", strtotime($fdate)));
		if($tdate!='')
			$this->set('tdate', date("d M Y", strtotime($tdate)));
	}
	
	function dailyInvoices() {
				
		//Convert date from date picker to SQL format
		if ($this->params['url']['ffromdate'] != '')
			$this->params['url']['ffromdate'] = date("Y-m-d", strtotime($this->params['url']['ffromdate']));

		if ($this->params['url']['ftodate'] != '')
			$this->params['url']['ftodate'] = date("Y-m-d", strtotime($this->params['url']['ftodate']));
		
		//Pick today's date if no date
		$fdate = ($this->params['url']['ffromdate'] != '' ? $this->params['url']['ffromdate']: date("Y-m-d") ) ;
		$tdate = ($this->params['url']['ftodate'] != '' ? $this->params['url']['ftodate']: date("Y-m-d") ) ;
		$userid = $this->params['url']['fuserid'];
   		
		
		//do something
		
		$db =& ConnectionManager::getDataSource("default");	
		
		$query = "
			SELECT o.id, o.order_number, t1.first_name tech1, t2.first_name tech2, o.job_notes_technician, o.job_date
			FROM ace_rp_orders o
			LEFT JOIN ace_rp_users t1
			ON o.job_technician1_id = t1.id
			LEFT JOIN ace_rp_users t2
			ON o.job_technician2_id = t2.id
			WHERE o.job_date BETWEEN '$fdate' AND '$tdate'
			AND o.order_status_id = 5
			
		";
		//AND (o.job_technician1_id = $techid OR o.job_technician2_id = $techid)
		
		$result = $db->_execute($query);
		
		while($row = mysql_fetch_array($result)) {
			$results[$row['id']]['order_number'] = $row['order_number'];
			$results[$row['id']]['tech1'] = $row['tech1'];
			$results[$row['id']]['tech2'] = $row['tech2'];
			$results[$row['id']]['job_notes_technician'] = $row['job_notes_technician'];
			$results[$row['id']]['job_date'] = $row['job_date'];			
		}
		
		$this->set("results", $results);
				
		
		$this->set("techid", $techid);
		if($fdate!='')
			$this->set('fdate', date("d M Y", strtotime($fdate)));
		if($tdate!='')
			$this->set('tdate', date("d M Y", strtotime($tdate)));
		$this->set('allTechnician',$this->Lists->Technicians());	
	}
	
	/*function clientFilter()
	{
		$this->layout="list";
		$max_page = 100;
		//CONDITIONS
		//Convert date from date picker to SQL format
		//Pick today's date if no date
		$fdate = ($this->params['url']['ffromdate'] != '' ? date("Y-m-d", strtotime($this->params['url']['ffromdate'])): date("Y-m-d") ) ;
		$tdate = ($this->params['url']['ftodate'] != '' ? date("Y-m-d", strtotime($this->params['url']['ftodate'])): date("Y-m-d") ) ;		
		$city = $_GET['data']['Customer']['city'];
		
		
		
		if(isset($city) && trim($city) != '') $city_condition = "AND temp.city = '$city'"; 
		else $city_condition = " ";		
		
		
		$db2 =& ConnectionManager::getDataSource('vicidial');
		
		$query = "
			SELECT * FROM vicidial_dnc				
		";
		
		$result = $db2->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$systemdnc[$row['phone_number']] = 1;					
		}
		
		$query = "
			SELECT DISTINCT phone_number 
			FROM vicidial_list
			WHERE status LIKE '%DNC%'				
		";
		
		$result = $db2->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$vicidnc[$row['phone_number']] = 1;					
		}
		
		$db =& ConnectionManager::getDataSource('default');
		
		$query = "
			SELECT COUNT(*) cnt FROM
			(
			SELECT u.id, u.first_name, u.last_name, u.email, u.address, u.city, u.postal_code, u.phone,  
				EXISTS(SELECT ch.* FROM ace_rp_call_history ch WHERE ch.customer_id = u.id AND ch.call_result_id = 3) acednc,
				(SELECT MAX(o.job_date) FROM ace_rp_orders o WHERE o.customer_id = u.id) latest_job,
				(SELECT MAX(co.call_date) FROM ace_rp_call_history co WHERE co.customer_id = u.id) call_date
			FROM ace_rp_users u
			LEFT JOIN ace_rp_users_roles ur
			ON u.id = ur.user_id
			WHERE (ur.role_id IS NULL OR ur.role_id = 8)
			AND u.callresult != 3
			) temp
			WHERE temp.acednc = 0
			AND (temp.call_date BETWEEN '".$this->Common->getMysqlDate($fdate)."' AND '".$this->Common->getMysqlDate($tdate)."' 
				OR temp.latest_job BETWEEN '".$this->Common->getMysqlDate($fdate)."' AND '".$this->Common->getMysqlDate($tdate)."')
			$city_condition			
		";
		
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$count = $row['cnt'];
		}
		
		if(isset($this->params['url']['page']))	{
			$page = $this->params['url']['page'];
			$page_start = (intval($page) - 1) * $max_page;			
			
			if($count - $page_start <= $max_page) {				
				$has_next = "disabled";				
			} else {
				$has_next = "";	
			}
			
			if($page_start == 0) $has_prev = "disabled"; 
			else $has_prev = "";
			
		} else {			
			$page = 1;
			$page_start = 0;
			$has_prev = "disabled";		
		}
		
		$query = "
			SELECT * FROM
			(
			SELECT u.id, u.first_name, u.last_name, u.email, u.address, u.city, u.postal_code, u.phone,  
				EXISTS(SELECT ch.* FROM ace_rp_call_history ch WHERE ch.customer_id = u.id AND ch.call_result_id = 3) acednc,
				(SELECT MAX(o.job_date) FROM ace_rp_orders o WHERE o.customer_id = u.id) latest_job,
				(SELECT MAX(co.call_date) FROM ace_rp_call_history co WHERE co.customer_id = u.id) call_date
			FROM ace_rp_users u
			LEFT JOIN ace_rp_users_roles ur
			ON u.id = ur.user_id
			WHERE (ur.role_id IS NULL OR ur.role_id = 8)
			AND u.callresult != 3
			) temp
			WHERE temp.acednc = 0
			AND temp.latest_job BETWEEN '".$this->Common->getMysqlDate($fdate)."' AND '".$this->Common->getMysqlDate($tdate)."'
			$city_condition
			LIMIT $page_start, $max_page			
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$records[$row['id']]['first_name'] = $row['first_name'];
			$records[$row['id']]['last_name'] = $row['last_name'];			
			$records[$row['id']]['email'] = $row['email'];
			$records[$row['id']]['address'] = $row['address'];
			$records[$row['id']]['city'] = $row['city'];
			$records[$row['id']]['phone'] = $row['phone'];
			$records[$row['id']]['latest_job'] = $row['latest_job'];
			$records[$row['id']]['call_date'] = $row['call_date'];
			$records[$row['id']]['acednc'] = $row['acednc'];
			$records[$row['id']]['systemdnc'] = $systemdnc[$row['phone']]?1:0;
			$records[$row['id']]['vicidnc'] = $vicidnc[$row['phone']]?1:0;		
		}
		
		$this->set("items", $records);
		$this->set("query", $query);
		$this->set("city", $city);
		$this->set("count", $count);
		$this->set("page", $page);
		$this->set("has_next", $has_next);
		$this->set("has_prev", $has_prev);
		if($fdate!='')
			$this->set('fdate', date("d M Y", strtotime($fdate)));
		if($tdate!='')
			$this->set('tdate', date("d M Y", strtotime($tdate)));		
		$this->set('allCities',$this->Lists->ListTable('ace_rp_cities'));
	}*/
	
	function exportClientListToCsv()
  {
		$fdate = date("Y-m-d", strtotime($_GET["fdate"]));
		$tdate = date("Y-m-d", strtotime($_GET["tdate"]));
		$city = $_GET["city"];
		
		if(isset($city) && trim($city) != '') $city_condition = "AND temp.city = '$city'"; 
		else $city_condition = " ";	

    $date = date("Y.m.d");
    header("Content-Disposition: attachment; filename=client_list_".$date.".csv");
    
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "
			SELECT * FROM
			(
			SELECT u.id, u.first_name, u.last_name, u.email, u.address, u.city, u.postal_code, u.phone,  
				EXISTS(SELECT ch.* FROM ace_rp_call_history ch WHERE ch.customer_id = u.id AND ch.call_result_id = 3) acednc,
				(SELECT MAX(o.job_date) FROM ace_rp_orders o WHERE o.customer_id = u.id) latest_job,
				(SELECT MAX(co.call_date) FROM ace_rp_call_history co WHERE co.customer_id = u.id) call_date
			FROM ace_rp_users u
			LEFT JOIN ace_rp_users_roles ur
			ON u.id = ur.user_id
			WHERE (ur.role_id IS NULL OR ur.role_id = 8)
			AND u.callresult != 3
			) temp
			WHERE temp.acednc = 0
			AND temp.latest_job BETWEEN '$fdate' AND '$tdate)'
			$city_condition				
		";

		$result = $db->_execute($query);
	
	$result = $db->_execute($query);
	while ($row = mysql_fetch_array($result, MYSQLI_ASSOC)) {
		/*$records[$row['id']]['first_name'] = $row['first_name'];
		$records[$row['id']]['last_name'] = $row['last_name'];			
		$records[$row['id']]['email'] = $row['email'];
		$records[$row['id']]['address'] = $row['address'];
		$records[$row['id']]['city'] = $row['city'];
		$records[$row['id']]['phone'] = $row['phone'];
		$records[$row['id']]['latest_job'] = $row['latest_job'];
		$records[$row['id']]['acednc'] = $row['acednc'];*/
		
		
        print $row['first_name'] .",";
        print $row['last_name'] .",";
		print $row['email'] .",";
        print $row['address'] .",";
        print $row['city'] .",";
		print $row['postal_code'] .",";
		print $row['phone'] ."\n";		
	}    
    
    exit;
  }
	
	
	// function vicidialTimeStats() {
	// 	$this->layout="list";
				
	// 	//CONDITIONS
	// 	//Convert date from date picker to SQL format
	// 	//Pick today's date if no date
	// 	$fdate = ($this->params['url']['ffromdate'] != '' ? date("Y-m-d", strtotime($this->params['url']['ffromdate'])): date("Y-m-d") ) ;
	// 	$tdate = ($this->params['url']['ftodate'] != '' ? date("Y-m-d", strtotime($this->params['url']['ftodate'])): date("Y-m-d") ) ;
	// 	/*$telemid = $this->params['url']['ftelemid'];
	// 	$disposition = $this->params['url']['disposition'];*/
		
	// 	//vicidial connection
	// 	$db =& ConnectionManager::getDataSource('vicidial');

	// 	$query = "
	// 		select l.user, u.full_name, 
	// 		SEC_TO_TIME(SUM(talk_sec)) talk, SEC_TO_TIME(SUM(wait_sec)) wait, SEC_TO_TIME(SUM(dispo_sec)) dispo, 
	// 		SEC_TO_TIME(SUM(pause_sec)) pause, SEC_TO_TIME(SUM(dead_sec)) dead,
	// 		SEC_TO_TIME(SUM(wait_sec)+SUM(talk_sec)) total  
	// 		from vicidial_agent_log l
	// 		LEFT JOIN vicidial_users u
	// 		ON l.user = u.user
	// 		WHERE event_time >= '".$this->Common->getMysqlDate($fdate)."'
	// 		and event_time <= '".$this->Common->getMysqlDate($tdate)."' 
	// 		group by l.user
	// 	";

	// 	$result = $db->_execute($query);
	// 	while($row = mysql_fetch_array($result)) {
	// 		$records[$row['user']]['name'] = $row['full_name'];
	// 		$records[$row['user']]['talk'] = $row['talk'];
	// 		$records[$row['user']]['wait'] = $row['wait'];    
	// 		$records[$row['user']]['pause'] = $row['pause'];
	// 		$records[$row['user']]['dispo'] = $row['dispo'];   
	// 		$records[$row['user']]['dead'] = $row['dead'];
	// 		$records[$row['user']]['total'] = $row['total'];
	// 	}
		
	// 	$this->set("records", $records);
		
	// 	if($fdate!='')
	// 		$this->set('fdate', date("d M Y", strtotime($fdate)));
	// 	if($tdate!='')
	// 		$this->set('tdate', date("d M Y", strtotime($tdate)));	
	// }
	
	// Loki: Mark payment confirm:
  	function confirmPayment()
  	{
  		$orderId = $_POST['orderId'];
  		$checked = $_POST['checked'];
  		$db 	=& ConnectionManager::getDataSource('default');
  		$query 	= "UPDATE ace_rp_orders set confirm_payment=".$checked." where id=".$orderId;
  		$res 	=  $db->_execute($query);

  		if($res)
  		{
  			$response  = array("res" => "OK");
			echo json_encode($response);
			exit;
		}

  	}
  		//Loki: Get payment data 

	function monthlyPaymentReport()
	{
		$this->layout="list";
		if ($this->Common->getLoggedUserRoleID() != 6) return;
    
	    $allPaymentMethods = $this->Lists->ListTable('ace_rp_payment_methods');
	    
			//CONDITIONS
			//Convert date from date picker to SQL format
			if ($this->params['url']['ffromdate'] != '')
				$fdate = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
	    else
				$fdate = date("Y-m-d");

			if ($this->params['url']['ftodate'] != '')
				$tdate = date("Y-m-d", strtotime($this->params['url']['ftodate']));
	    else
				$tdate = date("Y-m-d");

			$db =& ConnectionManager::getDataSource('default');
			$sqlConditions = "";
			if($fdate != '')
				$sqlConditions .= " AND o.job_date >= '".$this->Common->getMysqlDate($fdate)."'"; 
			if($tdate != '')
				$sqlConditions .= " AND o.job_date <= '".$this->Common->getMysqlDate($tdate)."'";
	    
			$records = array();
			$recordsTotal = array();

	    $query ="
	         select o.job_date, m.id payment_method_id, m.name payment_method,
	                sum(p.paid_amount) paid_amount
	           from ace_rp_payments p
	           join ace_rp_orders o on p.idorder=o.id
	           left outer join ace_rp_payment_methods m on m.id=p.payment_method
	          where p.payment_type=1 $sqlConditions
	          group by o.job_date, m.id, m.name";
	    
	    $result = $db->_execute($query);
	    while($row = mysql_fetch_array($result))
	    {
	        $records[$row['job_date']]['date'] = date("d M Y", strtotime($row['job_date']));
	        $records[$row['job_date']]['payments'][$row['payment_method']] = $row['paid_amount'];
	        $records[$row['job_date']]['payments']['total'] += $row['paid_amount'];
	        $recordsTotal['payments'][$row['payment_method']] += $row['paid_amount'];
	        $recordsTotal['payments']['total'] += $row['paid_amount'];
	    }   
	    
	    ksort($records);
	   
			$this->set("records", $records);    
			$this->set("recordsTotal", $recordsTotal);
			$this->set("allPaymentMethods", $allPaymentMethods);
			$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));
			$this->set('next_fdate', date("d M Y", strtotime($fdate) + 24*60*60));
			$this->set('prev_tdate', date("d M Y", strtotime($tdate) - 24*60*60));
			$this->set('next_tdate', date("d M Y", strtotime($tdate) + 24*60*60));
			$this->set('fdate', date("d M Y", strtotime($fdate)));
			$this->set('tdate', date("d M Y", strtotime($tdate)));
	}

	function reminderSummary(){
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$limit = 50;
		$reminder = array();
		$pageNo = isset($_GET['currentPage']) ? $_GET['currentPage'] : 1;
		$filter = isset($_GET['filter']) ? $_GET['filter'] : -1;
		$search_param = isset($_GET['search_param']) ? $_GET['search_param']:'';
		if($pageNo == 0)
		{
			$pageNo	= 1;
		}
		$record_index= ($pageNo-1) * $limit;   


		if ($this->params['url']['ffromdate'] != '')
			$fdate = date("Y-m-d", strtotime($this->params['url']['ffromdate'])) . ' 00:00:00';
		else
			$fdate = date("Y-m-d".' 00:00:00');

		if ($this->params['url']['ftodate'] != '')
			$tdate = date("Y-m-d", strtotime($this->params['url']['ftodate'])) . ' 23:59:59';
		else
			$tdate = date("Y-m-d" .' 23:59:59');   

		$date_start = $this->Common->getMysqlDate($fdate);
		$date_end = $this->Common->getMysqlDate($tdate);
		$sqlConditions = "(s.sms_date BETWEEN '$date_start' AND '$date_end')";
		if($filter != -1){
			//filter with phone number
			if($filter == 1){
				$sqlConditions .= " AND u.cell_phone ='".$search_param."'";
			}

			//filter with failed email
			if($filter == 2){
				$sqlConditions .= " AND e.is_sent =0";
			}
			//filter with failed sms
			if($filter == 3){
				$sqlConditions .= " AND s.is_sent =0";
			}

			//filter with failed email
			if($filter == 4){
				$sqlConditions .= " AND s.is_sent =1";
			}
			//filter with failed sms
			if($filter == 5){
				$sqlConditions .= " AND e.is_sent =1";
			}

			//filter for reference number
			if($filter == 6){
				$sqlConditions .= " AND o.order_number =".$search_param."";
			}
		}

		$query = "SELECT s.order_id, s.sms_date, s.is_sent as sms_sent, e.is_sent as email_sent,o.order_number, o.address_unit, o.address_street_number, o.address_street,o.city,o.postal_code,s.text_type,e.email_type,u.cell_phone,u.phone,u.email,o.job_date FROM ace_rp_sms_log s
				LEFT JOIN ace_rp_reminder_email_log e ON s.order_id = e.order_id
				LEFT JOIN ace_rp_orders o ON s.order_id = o.id
				LEFT JOIN ace_rp_customers u ON s.customer_id = u.id
				WHERE s.text_type = 2 AND e.email_type = 2 AND $sqlConditions order by s.id asc  LIMIT $record_index, $limit
				";

			// print_r($query); die;
			$result = $db->_execute($query);
		
			while($row = mysql_fetch_array($result)){
				$reminder[] = $row;
			}
		$query1 = "SELECT count(*) FROM ace_rp_sms_log s
			LEFT JOIN ace_rp_reminder_email_log e ON s.order_id = e.order_id
			LEFT JOIN ace_rp_orders o ON s.order_id = o.id
			LEFT JOIN ace_rp_customers u ON s.customer_id = u.id
			WHERE s.text_type = 2 AND e.email_type = 2 AND $sqlConditions order by s.id asc
			";
		
		$result1 = $db->_execute($query1);
		$row1 = mysql_fetch_array($result1);  
		$total_records = $row1[0];
		$total_pages = ceil($total_records / $limit);  
		
		$this->set('totalPages', $total_pages);
		$this->set('currentPage', $pageNo);
		$this->set('search_param', $search_param);
		$this->set('filter', $filter);
		$this->set('reminder', $reminder);
		$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));
		$this->set('next_fdate', date("d M Y", strtotime($fdate) + 24*60*60));
		$this->set('prev_tdate', date("d M Y", strtotime($tdate) - 24*60*60));
		$this->set('next_tdate', date("d M Y", strtotime($tdate) + 24*60*60));
		$this->set('fdate', date("d M Y", strtotime($fdate)));
		$this->set('tdate', date("d M Y", strtotime($tdate)));
	}

	//Loki: set the reminder date for estimate
	function setReminderCallback()
	{
		$orderId = $_POST['orderId'];
        $remiderNote = $_POST['remiderNote'];
        $reminderDate = date('Y-m-d',strtotime($_POST['reminderDate']));
        $lastcallDate = date('Y-m-d');
        $createdBy = $this->Common->getLoggedUserID();
        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "INSERT INTO ace_rp_estimate_callback (order_id, callback_date,notes,created_by,lastcall_date) VALUES (".$orderId.", '".$reminderDate."','".$remiderNote."',".$createdBy.",'".$lastcallDate."')";
        $result = $db->_execute($query);
        if ($result) {
        	$db->_execute("UPDATE ace_rp_orders set estimate_callback_date = '".$reminderDate."' where order_number =".$orderId);
            $response  = array("res" => "OK");
            echo json_encode($response);
            exit;
        }
	}

	//Loki: get the  reminder history of estimate
	function viewEstimateReminder()
	{
		$orderId = $_POST['orderId'];
        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "SELECT ec.* , concat(u.first_name, ' ', u.last_name) agent_name FROM ace_rp_estimate_callback ec 
		LEFT JOIN ace_rp_users u ON u.id = ec.created_by
		where order_id=".$orderId." order by id desc";
        $result = $db->_execute($query);

        while ($row = mysql_fetch_array($result)) {
        	print_r($row);
        	$history[] = $row;
        }
        $this->set("history",$history);
	}
	/*Loki: get estimate callback details.*/
	function getEstimateReminder()
	{
		$Id = $_POST['Id'];
        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "SELECT * FROM ace_rp_estimate_callback where id=".$Id." order by id desc";
        $result = $db->_execute($query);
        $row = mysql_fetch_array($result);
        $row['callback_date'] = date("d M Y", strtotime($row['callback_date']));
        echo json_encode($row);
        exit();
	}

	/*Loki: Edit Callback notes*/
	function editReminderCallback()
	{
		$callbackId = $_POST['callbackId'];
		$remiderNote = $_POST['remiderNote'];

        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "UPDATE ace_rp_estimate_callback set notes = '".$remiderNote."' where id=".$callbackId;
        $result = $db->_execute($query);

        if ($result) {
            $response  = array("res" => "OK");
            echo json_encode($response);
            exit;
        }
	}

	function deleteCallback()
	{
		$callbackId = $_POST['callbackId'];

        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "DELETE FROM ace_rp_estimate_callback where id=".$callbackId;
        $result = $db->_execute($query);

        if ($result) {
            $response  = array("res" => "OK");
            echo json_encode($response);
            exit;
        }
	}

	function cardPaymentSummary()
	{
		$paymentType = isset($this->params['url']['payment_type']) ? $this->params['url']['payment_type'] : 0;
		$authCode = isset($this->params['url']['auth_search']) ? $this->params['url']['auth_search'] : '';
		$amount = isset($this->params['url']['amount_search']) ? $this->params['url']['amount_search'] : '';
		$this->layout="list";
		if ($this->Common->getLoggedUserRoleID() != 6) return;
    
	    $allPaymentMethods = $this->Lists->paymenTable('ace_rp_payment_methods');
	    $allTechnicians = $this->Lists->Technicians();
    
		//CONDITIONS
		//Convert date from date picker to SQL format
		if ($this->params['url']['ffromdate'] != '')
			$fdate = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
	    else
				// $fdate = date("Y-m-d");
	    		$fdate = date('Y-m-d',strtotime("-1 days"));


			if ($this->params['url']['ftodate'] != '')
				$tdate = date("Y-m-d", strtotime($this->params['url']['ftodate']));
	    else
				//$tdate = date("Y-m-d");
	    	$tdate = date('Y-m-d',strtotime("-1 days"));


		$db =& ConnectionManager::getDataSource('default');
		$sqlConditions = "";
		if($fdate != '')
			$sqlConditions .= " CAST(cp.payment_date AS DATE) >= '".$this->Common->getMysqlDate($fdate)."'"; 
		if($tdate != '')
			$sqlConditions .= " AND CAST(cp.payment_date AS DATE) <= '".$this->Common->getMysqlDate($tdate)."'";
    	
	    if($paymentType > 0){
	    	$sqlConditions .= " AND o.payment_method_type =".$paymentType;		
	    }

	    if($authCode != ''){
	    	$sqlConditions .= " AND cp.auth_code LIKE '%".$authCode."%'";			
	    }
	    if($amount != ''){
	    	$sqlConditions .= " AND cp.amount LIKE '%".$amount."%'";			
	    }

		$records = array();
		$recordsTotal = array();
		$orders = array();
		
		$query ="select cp.*,pm.name as payment_method,o.order_number, ot.name as job_type, CONCAT(tech1.first_name,' ', tech1.last_name) tech1_name, CONCAT(tech2.first_name,' ', tech2.last_name) tech2_name from ace_rp_creditcard_payment_details cp JOIN ace_rp_orders o ON 	 cp.order_id = o.id
				 LEFT JOIN ace_rp_payment_methods pm ON pm.id = o.payment_method_type
				 LEFT JOIN ace_rp_order_types ot ON ot.id = o.order_type_id
				 LEFT JOIN ace_rp_users tech1 ON tech1.id = o.job_technician1_id
				 LEFT JOIN ace_rp_users tech2 ON tech2.id = o.job_technician2_id
		 		where $sqlConditions AND cp.id = (SELECT MAX(id) FROM ace_rp_creditcard_payment_details cp1 WHERE cp.order_id = cp1.order_id)";
	    $result = $db->_execute($query);
	    $summary = array();
	    while($row = mysql_fetch_array($result))
	    {    	
	    	$summary[] = $row;
	    }

   		$this->set("summary", $summary);    
		$this->set("allPaymentMethods", $allPaymentMethods);
		$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));
		$this->set('next_fdate', date("d M Y", strtotime($fdate) + 24*60*60));
		$this->set('prev_tdate', date("d M Y", strtotime($tdate) - 24*60*60));
		$this->set('next_tdate', date("d M Y", strtotime($tdate) + 24*60*60));
		$this->set('fdate', date("d M Y", strtotime($fdate)));
		$this->set('tdate', date("d M Y", strtotime($tdate)));
		$this->set("allTechnicians", $allTechnicians);
		$this->set("paymentType", $paymentType);
		$this->set("authCode", $authCode);
		$this->set("amount", $amount);
	}

} //end of reports controller
?>
