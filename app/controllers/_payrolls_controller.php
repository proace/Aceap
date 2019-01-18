<?
error_reporting(1);

class PayrollsController extends AppController
{
	//To avoid possible PHP4 problems
	var $name = "Payrolls";

	var $uses = array('Payroll','User','Role','Userrole','WorkRecord','PayPeriods');

	var $helpers = array('Time','Ajax','Common');
	var $components = array('HtmlAssist', 'RequestHandler','Common','Lists');

	var $itemsToShow=20;
	var $pagesToDisplay=10;
	
	function getAllTypes()
	{
		return array(1 => 'Technicians', 2 => 'Office and Telemarketers');
	}
	
	function pay_periods()
	{
		$this->layout="list";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		if (!$order) $order = 'start_date asc';
		
		$query = "select * from ace_rp_pay_periods i order by ".$order.' '.$sort;
		
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $items[$row['id']][$k] = $v;
		}
		
		$this->set('items', $items);
		$this->set('allTypes', $this->getAllTypes());
	}

	function editItem()
	{
		$item_id = $_GET['item_id'];
		if ($item_id)
		{
			$this->PayPeriods->id = $item_id;
			$this->data = $this->PayPeriods->read();
			$this->data['PayPeriods']['start_date'] = date('d M Y',strtotime($this->data['PayPeriods']['start_date']));
			$this->data['PayPeriods']['end_date'] = date('d M Y',strtotime($this->data['PayPeriods']['end_date']));
		}
		else
		{
			$this->data['PayPeriods']['start_date'] = date('d M Y');
			$this->data['PayPeriods']['end_date'] = date('d M Y');
		}
		$this->set('allTypes', $this->getAllTypes());
	}

	function saveItem()
	{
		$this->data['PayPeriods']['start_date'] = date('Y-m-d', strtotime($this->data['PayPeriods']['start_date']));
		$this->data['PayPeriods']['end_date'] = date('Y-m-d', strtotime($this->data['PayPeriods']['end_date']));
		$this->PayPeriods->id = $this->data['PayPeriods']['id'];		
		$this->PayPeriods->save($this->data);
		if ($this->PayPeriods->id)
			$cur_id = $this->PayPeriods->id;
		else
			$cur_id = $this->PayPeriods->getLastInsertId();

		//Forward user where they need to be - if this is a single action per view
		$this->redirect('/payrolls/pay_periods');
	}

	function view_payroll()
	{
		$this->layout="list";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$disableEdit = false;
		$useridCondition = "";
		$useridCondition2 = "";
		if (($_SESSION['user']['role_id'] == 3)||($_SESSION['user']['role_id'] == 9)||($_SESSION['user']['role_id'] == 13)) { 
			$useridCondition = " and u.id=".$this->Common->getLoggedUserID();
			$useridCondition2 = " and user_id=".$this->Common->getLoggedUserID();
			$disableEdit = true;
		}
		
		$allUsers = $this->Lists->UsersByGroup('office');
		
		// Check for new data from TimeQPlus device
		$query = "select max(date) date from ace_rp_time_log";
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		if ($row['date'])
			$maxDate = date("Ymd",strtotime($row['date']." -1 day"));
		else
			$maxDate = date("Ymd",strtotime("2000-01-01"));
		$dir = "c:/Program Files/Acroprint/Attendance Rx/TQLogs/";
		if ($dh = opendir($dir))
		{
			while (($file = readdir($dh)) !== false)
			{
				if (((integer)substr($file,0,strpos($file,".")))>=((integer)$maxDate))
				{
					$ct = file_get_contents($dir.$file);
					$arr = explode("\n",$ct);
					foreach ($arr as $cur)
					{
						$a = explode(",",$cur);
						if (count($a)==3)
						{
							$pin = (integer)$a[1];
							$date = date("Y-m-d",strtotime($a[2]));
							$time = date("H:i",strtotime($a[2]));
							$query = "insert into ace_rp_time_log(user_pin, date, time) values ($pin,'$date','$time')";
							$db->_execute($query);
						}
					}
				}
			}
			closedir($dh);
		}

		$sort = $_GET['sort'];
		$order = $_GET['order'];
		if (!$order) $order = 'employee asc';
		
		$pay_period = $this->params['url']['pay_period'];
		if (!$pay_period)
		{
			$pay_period = 1;
			$query = "select * from ace_rp_pay_periods where now() between start_date and end_date and period_type=2";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				$pay_period = $row['id'];
		}
		
		if ($pay_period<0)
		{
			if ($this->params['url']['ffromdate'] != '')
				$this->params['url']['ffromdate'] = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
			
			if ($this->params['url']['ftodate'] != '')
				$this->params['url']['ftodate'] = date("Y-m-d", strtotime($this->params['url']['ftodate']));
			
			//Pick today's date if no date
			$fdate = ($this->params['url']['ffromdate'] != '' ? $this->params['url']['ffromdate']: date("Y-m-d") );
			$tdate = ($this->params['url']['ftodate'] != '' ? $this->params['url']['ftodate']: date("Y-m-d") );
			
			$sqlConditions2="";
			if(($fdate != '')&&(!$cur_ref))
			{
				$sqlConditions .= " AND a.job_date >= '".$this->Common->getMysqlDate($fdate)."'"; 
				$sqlConditions2 .= " and i.date >= '".$this->Common->getMysqlDate($fdate)."'"; 
			}
			if(($tdate != '')&&(!$cur_ref))
			{
				$sqlConditions .= " AND a.job_date <= '".$this->Common->getMysqlDate($tdate)."'";
				$sqlConditions2 .= " and i.date <= '".$this->Common->getMysqlDate($tdate)."'";
			}
				
			$query = "select user_id, sum(bonus) bonus, sum(adj) adj from ace_rp_payrolls where pay_period in
						(select id from ace_rp_pay_periods where start_date>='".$this->Common->getMysqlDate($fdate)."'
						and end_date<='".$this->Common->getMysqlDate($tdate)."' and period_type=2) $useridCondition2
						group by user_id";
		}
		else
		{
			$fdate = '';
			$tdate = '';
			$sqlConditions .= " and exists (select * from ace_rp_pay_periods p where a.job_date between p.start_date and p.end_date and p.id=$pay_period)";
			$sqlConditions2 .= " and exists (select * from ace_rp_pay_periods p where i.date between p.start_date and p.end_date and p.id=$pay_period)";
			$query = "select * from ace_rp_pay_periods where id=$pay_period";
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$fdate = $row['start_date'];
			$tdate = $row['end_date'];
			$query = "select user_id, sum(bonus) bonus, sum(adj) adj from ace_rp_payrolls where pay_period=$pay_period $useridCondition2 group by user_id";
		}
		
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
				$items[$allUsers[$row['user_id']]][$k] = $v;
		}
		
		$query = "select u.id, concat(u.first_name,' ',u.last_name) employee, 
						z.rate, count(*) jobs_done, z.goal,
						sum(if(a.order_type_id=2 or a.order_type_id=4,1,0)) furnace,
						sum(if(a.order_type_id=3,1,0)) airduct,
						sum(if(a.order_type_id=2 or a.order_type_id=4,1,0)*z.commission_furnace) +
						sum(if(a.order_type_id=3,1,0)*z.commission_airduct) commission,
						sum(i.price*i.quantity-i.discount+i.addition) jobs_amount						
				   from ace_rp_orders a, ace_rp_users u, ace_rp_payroll_structure z, ace_rp_order_items i
				  where u.id=a.booking_source_id and z.user_id=u.id and a.order_status_id=5
					and i.order_id=a.id and i.class=0 $sqlConditions $useridCondition
				  group by u.id order by ".$order.' '.$sort;
		
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
				$items[$row['employee']][$k] = $v;
			if (($row['goal']-$row['furnace']-$row['airduct']>0)&&($row['jobs_amount']<1800))
				$items[$row['employee']]['commission'] = 0;
			$items[$row['employee']]['others'] =
				$items[$row['employee']]['jobs_done'] -
				$items[$row['employee']]['airduct'] -
				$items[$row['employee']]['furnace'];
			$items[$row['employee']]['gross'] =
				$items[$row['employee']]['commission']+
				$items[$row['employee']]['bonus']+
				$items[$row['employee']]['adj'];
		}
		
		$query = "select u.id, z.rate, concat(u.first_name,' ',u.last_name) employee,
						 SUM(TIME_TO_SEC(i.net))/3600 net
					from ace_rp_users u, ace_rp_payroll_structure z, ace_rp_payroll_time_sheet i
				    where i.user_id=u.id and z.user_id=u.id $sqlConditions2
				   group by u.id order by ".$order.' '.$sort;
		
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$items[$row['employee']]['employee'] = $row['employee'];
			$items[$row['employee']]['hours'] = round($row['net'],2);
			$items[$row['employee']]['rate'] = $row['rate'];
			$items[$row['employee']]['wage'] = round(round($row['net'],2)*$row['rate'],2);
			$items[$row['employee']]['gross'] += $items[$row['employee']]['wage'];
		}
		
		ksort($items);
		
		$this->set('items', $items);
		$this->set('disableEdit', $disableEdit);
		$this->set('pay_period', $pay_period);
		if ($pay_period<0)
		{
			$this->set("disabled","");
			$this->set("disabled2","display:none;");
		}
		else
		{
			$this->set("disabled","display:none;");
			$this->set("disabled2","");
		}
		$this->set('allPayPeriods', $this->Lists->PayPeriods(2));			
		if($fdate!='') $this->set('fdate', date("d M Y", strtotime($fdate)));
		if($tdate!='') $this->set('tdate', date("d M Y", strtotime($tdate)));
	}
	
	function editEmployees()
	{
		$this->layout="list";
		$db = &ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$items = array();
		foreach ($this->Lists->UsersByGroup('office') as $id => $nm)
		{
			$items[$id] = array(
				"id" => $id,
				"name" => $nm,
				"rate" => 0,
				"commission_furnace" => 0,
				"commission_airduct" => 0,
				"goal" => 30,
				"breaks" => 0,
				"start_time" => '08:00',
				"end_time" => '17:00'
			);
		}

		$query = "select * from ace_rp_payroll_structure";
		
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
				$items[$row['user_id']][$k] = $v;
			$items[$row['user_id']]['start_time'] = date("H:i",strtotime($row['start_time']));
			$items[$row['user_id']]['end_time'] = date("H:i",strtotime($row['end_time']));
		}
		
		$this->set('items', $items);
	}
	
	function changeEmployeeField()
	{
		//$period_id = $_GET['period_id'];
		$userid = $_REQUEST['userid'];
		$field = $_REQUEST['field'];
		$value = $_REQUEST['value'];

		// Saving data
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$result = $db->_execute("select count(*) cnt from ace_rp_payroll_structure where user_id='$userid'");
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		if ($row['cnt']>0)
			$db->_execute("update ace_rp_payroll_structure set $field='$value' where user_id='$userid'");
		else
			$db->_execute("insert into ace_rp_payroll_structure (user_id, $field) values ('$userid','$value')");
		
		exit;
	}
	
	function changePayrollField()
	{
		$payperiod = $_REQUEST['payperiod'];
		$userid = $_REQUEST['userid'];
		$field = $_REQUEST['field'];
		$value = $_REQUEST['value'];

		// Saving data
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$result = $db->_execute("select count(*) cnt from ace_rp_payrolls where user_id='$userid' and pay_period='$payperiod'");
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		if ($row['cnt']>0)
			$db->_execute("update ace_rp_payrolls set $field='$value' where user_id='$userid' and pay_period='$payperiod'");
		else
			$db->_execute("insert into ace_rp_payrolls (user_id, pay_period, $field) values ('$userid','$payperiod','$value')");
		
		exit;
	}
	
	function time_sheet()
	{
		$this->layout="list";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		//$sort = $_GET['sort'];
		//$order = $_GET['order'];
		//if (!$order) $order = 'employee asc';
		
		$allUsers = $this->Lists->UsersByGroup('office');
		$userid = $this->params['url']['userid'];
		//if (!$userid) {$a = array_keys($allUsers); $userid = $a[0];};
		
		$pay_period = $this->params['url']['pay_period'];
		if (!$pay_period)
		{
			$pay_period = 1;
			$query = "select * from ace_rp_pay_periods where now() between start_date and end_date and period_type=2";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				$pay_period = $row['id'];
		}
		
		if ($pay_period<0)
		{
			if ($this->params['url']['ffromdate'] != '')
				$this->params['url']['ffromdate'] = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
			
			if ($this->params['url']['ftodate'] != '')
				$this->params['url']['ftodate'] = date("Y-m-d", strtotime($this->params['url']['ftodate']));
			
			//Pick today's date if no date
			$fdate = ($this->params['url']['ffromdate'] != '' ? $this->params['url']['ffromdate']: date("Y-m-d") );
			$tdate = ($this->params['url']['ftodate'] != '' ? $this->params['url']['ftodate']: date("Y-m-d") );
		}
		else
		{
			$fdate = '';
			$tdate = '';
			$query = "select * from ace_rp_pay_periods where id=$pay_period";
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$fdate = $row['start_date'];
			$tdate = $row['end_date'];
		}
		
		if ($fdate != '')
			$sqlConditions .= " and t.date >= '".$this->Common->getMysqlDate($fdate)."'"; 
		if ($tdate != '')
			$sqlConditions .= " and t.date <= '".$this->Common->getMysqlDate($tdate)."'";
		
		$query = "select t.*
					 from ace_rp_payroll_time_sheet t
				    where user_id='$userid' $sqlConditions
				    order by date asc";
		
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$item = array();
			foreach ($row as $k => $v)
				$item[$k] = $v;
			$item['date'] = date("d M Y",strtotime($row['date']));
			$item['time_in'] = date("H:i",strtotime($row['time_in']));
			$item['time_out'] = date("H:i",strtotime($row['time_out']));
			$item['gross'] = date("H:i",strtotime($row['gross']));
			$item['addition'] = date("H:i",strtotime($row['addition']));
			$item['deduction'] = date("H:i",strtotime($row['deduction']));
			$item['net'] = date("H:i",strtotime($row['net']));
			$items[] = $item;
		}
		
		$this->set('items', $items);
		
		$this->set('items', $items);
		$this->set('disableEdit', $disableEdit);
		$this->set('pay_period', $pay_period);
		$this->set('userid', $userid);
		if ($pay_period<0)
		{
			$this->set("disabled","");
			$this->set("disabled2","display:none;");
		}
		else
		{
			$this->set("disabled","display:none;");
			$this->set("disabled2","");
		}
		$this->set('allPayPeriods', $this->Lists->PayPeriods(2));
		$this->set('allUsers', $allUsers);
		if($fdate!='') $this->set('fdate', date("d M Y", strtotime($fdate)));
		if($tdate!='') $this->set('tdate', date("d M Y", strtotime($tdate)));
	}
	
	function deleteTransaction()
	{
		$rowid = $_REQUEST['rowid'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$db->_execute("delete from ace_rp_payroll_time_sheet where id=$rowid");
		exit;
	}
	
	function saveTransaction()
	{
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$rowid = $_REQUEST['rowid'];
		$userid = $_REQUEST['userid'];
		$date = date("Y-m-d",strtotime($_REQUEST['date']));
		$time_in = $_REQUEST['time_in']?date("H:i",strtotime($_REQUEST['time_in'])):"00:00";
		$time_out = $_REQUEST['time_out']?date("H:i",strtotime($_REQUEST['time_out'])):"00:00";
		$breaks = $_REQUEST['breaks']?date("H:i",strtotime($_REQUEST['breaks'])):"00:00";
		$addition = $_REQUEST['addition']?date("H:i",strtotime($_REQUEST['addition'])):"00:00";
		$deduction = $_REQUEST['deduction']?date("H:i",strtotime($_REQUEST['deduction'])):"00:00";
		$note = $_REQUEST['note'];
		$h_in = 1*date("H",strtotime($_REQUEST['time_in']))+date("i",strtotime($_REQUEST['time_in']))/60;
		$h_out = 1*date("H",strtotime($_REQUEST['time_out']))+date("i",strtotime($_REQUEST['time_out']))/60;
		
		$query = "select *
				   from ace_rp_payroll_structure z
				  where z.user_id=$userid";
		
		$deduct_breaks = 1;
		$start_time = "08:00";
		$end_time = "17:00";
		$result = $db->_execute($query);
		if ($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$deduct_breaks = $row['breaks'];
			$start_time = date("H:i",strtotime($row['start_time']));
			$end_time = date("H:i",strtotime($row['end_time']));
			$start_h = 1*date("H",strtotime($row['start_time']))+date("i",strtotime($row['start_time']))/60;
			$end_h = 1*date("H",strtotime($row['end_time']))+date("i",strtotime($row['end_time']))/60;
		}
		
		if ($start_h>$h_in)
		{
			$h_in = $start_h;
			$time_in = $start_time;
		}
		
		if ($end_h<$h_out)
		{
			$h_out = $end_h;
			$time_out = $end_time;
		}
		
		$gross = date("H:i",strtotime($time_out." -".date("H",strtotime($time_in))." hours -".date("i",strtotime($time_in))." min"));
		$breaks_min = 0;
		$breaks = "00:00";
		if ($deduct_breaks)
		{
			if (($h_in<11)&&($h_out>11)) $breaks_min += min(($h_out-11)*60,15);
			if (($h_in<12.5)&&($h_out>12.5)) $breaks_min += min(($h_out-12.5)*60,30);
			if (($h_in<15)&&($h_out>15)) $breaks_min += min(($h_out-15)*60,15);
			if (date("H",strtotime($gross))>=7)
				$breaks = date("H:i",strtotime("00:00 +".($breaks_min-15)." min"));
			else
				$breaks = date("H:i",strtotime("00:00 +".$breaks_min." min"));
		}
		$net = date("H:i",strtotime($gross." -".date("H",strtotime($breaks))." hours -".date("i",strtotime($breaks))." min"));
		$net = date("H:i",strtotime($net." +".date("H",strtotime($addition))." hours +".date("i",strtotime($addition))." min"));
		$net = date("H:i",strtotime($net." -".date("H",strtotime($deduction))." hours -".date("i",strtotime($deduction))." min"));

		if ($rowid)
		{
			$query = "UPDATE whytecl_acesys.ace_rp_payroll_time_sheet 
						 SET user_id = $userid , date = '$date', time_in = '$time_in', time_out = '$time_out',
						     addition = '$addition', deduction = '$deduction', note = '$note', breaks = '$breaks',
							 gross = '$gross', net = '$net' 
					   WHERE id=$rowid";
		}
		else
		{
			$query = "INSERT INTO whytecl_acesys.ace_rp_payroll_time_sheet
					(user_id, date, time_in, time_out, addition, deduction, note, breaks, gross, net) 
					VALUES ($userid, '$date', '$time_in', '$time_out', '$addition', '$deduction', '$note', '$breaks', '$gross', '$net')";
		}
		$db->_execute($query);
		exit;
	}
	
	function time_daily()
	{
		$this->layout="list";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$allUsers = $this->Lists->UsersByGroup('office');

		$pay_period = $this->params['url']['pay_period'];
		if (!$pay_period)
		{
			$pay_period = 1;
			$query = "select * from ace_rp_pay_periods where now() between start_date and end_date and period_type=2";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				$pay_period = $row['id'];
		}
		$sqlConditions = "and exists (select * from ace_rp_pay_periods p where i.date between p.start_date and p.end_date and p.id=$pay_period)";

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
				$dates[$key] = $cur;
			}
			$cur = strtotime(date("m/d/Y", $cur)." +1 day");
		}
		
		ksort($dates);
		
		$query = "select user_id, date, SEC_TO_TIME(SUM(TIME_TO_SEC(net))) net
					from ace_rp_payroll_time_sheet i
				    where 1=1 $sqlConditions
				   group by user_id, date";
//				    order by ".$order.' '.$sort;
		
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$k = date("Ymd", strtotime($row['date']));
			$items[$allUsers[$row['user_id']]][$k] = date("H:i",strtotime($row['net']));
			$items[$allUsers[$row['user_id']]]['id'] = $row['user_id'];
			$items[$allUsers[$row['user_id']]]['name'] = $allUsers[$row['user_id']];
		}
		
		ksort($items);
		
		$this->set('dates', $dates);
		$this->set('items', $items);
		$this->set('pay_period', $pay_period);
		$this->set('allPayPeriods', $this->Lists->PayPeriods(2));
	}
	
	function special_bonuses()
	{
		$this->layout="list";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$allUsers = $this->Lists->UsersByGroup('office');

		$pay_period = $this->params['url']['pay_period'];
		if (!$pay_period)
		{
			$pay_period = 1;
			$query = "select * from ace_rp_pay_periods where now() between start_date and end_date and period_type=2";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				$pay_period = $row['id'];
		}
		$sqlConditions = "and exists (select * from ace_rp_pay_periods p where a.booking_date between p.start_date and p.end_date and p.id=$pay_period)";

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
				$dates[$key] = $cur;
			}
			$cur = strtotime(date("m/d/Y", $cur)." +1 day");
		}
		
		ksort($dates);
		
		$query = "select u.id, concat(u.first_name,' ',u.last_name) employee,
						 a.booking_date, a.order_number, a.id job_id, c.phone
				   from ace_rp_orders a, ace_rp_users u, ace_rp_users c
				  where u.id=a.booking_source_id and a.order_status_id not in (2,3)
				    and job_date-booking_date<=7 and c.id=a.customer_id
					and exists (select * from ace_rp_users_roles r where r.user_id=u.id and r.role_id in (3,9,13))
				    $sqlConditions
				 order by concat(u.first_name,' ',u.last_name) asc";
		
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$key = date("Ymd", strtotime($row['booking_date']));
			if (!is_array($items[$row['id']][$key])) $items[$row['id']][$key] = array();
			$items[$row['id']][$key][$row['job_id']] = "Phone: {$row['phone']}, REF #: {$row['order_number']}";
			$items[$row['id']]['id'] = $row['id'];
			$items[$row['id']]['name'] = $row['employee'];
		}
		
		$this->set('dates', $dates);
		$this->set('items', $items);
		$this->set('pay_period', $pay_period);
		$this->set('allPayPeriods', $this->Lists->PayPeriods(2));
	}
}
?>