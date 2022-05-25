<?php
class GroupsController extends AppController {
	var $name = 'GroupsController';
	var $uses = array('Order', 'OrderStatus', 'User', 'PaymentMethod');
	var $helpers = array('Time', 'Ajax', 'Javascript', 'Common');
	var $components = array('HtmlAssist', 'RequestHandler', 'Jpgraph', 'Common', 'Lists');
   //addition by Hennesy
   function index()
	{  
		$this->layout="list";
		$loggedUserId = 0;
	    $sqlConditions = '';
    
		//$sort = $_GET['sort'];
		//$order = $_GET['order'];
		//if (!$order) $order = 'u.first_name asc, u.last_name asc';
    
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
        SELECT g.id AS gid, g.name AS gname, g.leader_id AS gleaderid, u.id, 
		g.lead_color, g.lead_text, g.team_color, g.team_text,
		u.first_name, u.last_name
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

		$query = "
			SELECT * FROM ace_rp_groups
		";
		
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{		
			$groups[$row['id']]['id'] = $row['id'];	
			$groups[$row['id']]['name'] = $row['name'];	
			$groups[$row['id']]['leader_id'] = $row['leader_id'];	
			$groups[$row['id']]['perBooking'] = $row['perBooking'];
			$groups[$row['id']]['perBudget'] = $row['perBudget'];
			$groups[$row['id']]['minBooking'] = $row['minBooking'];
			$groups[$row['id']]['maxBooking'] = $row['maxBooking'];	
			$groups[$row['id']]['goalBooking'] = $row['goalBooking'];	
			$groups[$row['id']]['type'] = $row['type'];	
			$groups[$row['id']]['lead_color'] = $row['lead_color'];
			$groups[$row['id']]['lead_text'] = $row['lead_text'];
			$groups[$row['id']]['team_color'] = $row['team_color'];
			$groups[$row['id']]['team_text'] = $row['team_text'];
			
		}

		$this->set("groups", $groups);
		$this->set("grouplist", $this->Lists->Groups());

		$this->set("items", $records);
		$this->set("nogroups", $records);
		
		$this->set('allTelemarketers',$allTelemarketers);	

	}
	
	function add()    
	{    
		
		if (!empty($this->data['Group']))    
		{    
			if($this->Group->save($this->data['Group']))    
			{
				$this->redirect($this->referer());
			}
		} 
	}
	
	function saveLeader()    
	{    
		
		$d = explode('|', $_REQUEST['uid']);
		
		//$this->Group->read($d[0]);
		
		//$this->Group->leader_id = $d[1];
		
		
		$db =& ConnectionManager::getDataSource('default');
		$query = "update ace_rp_groups set leader_id = $d[1] where id = $d[0]";
		$db->_execute($query);
		
		//$this->redirect('http://69.31.184.162:81/acesys/index.php/reports/telem_groups');
		$this->redirect($this->referer());
	}
	
	function saveGroup()
	{		
		$d = explode('|', $_REQUEST['uid']);		
		$db =& ConnectionManager::getDataSource("default");
		$query = "delete from ace_rp_groups_users where user_id = $d[1]";
		$db->_execute($query);		
		if($d[0] != '0') {								
			$query = "insert into ace_rp_groups_users(group_id, user_id) values ($d[0],$d[1])";
			$db->_execute($query);
		}
		if($d[0] == '0') {								
			$query = "update ace_rp_groups set leader_id = 0 where leader_id = $d[1]";
			$db->_execute($query);
		}
				
		$this->redirect($this->referer());	
	}
	
	function addGroup()	
	{
		$d = $_REQUEST['name'];		
		$db =& ConnectionManager::getDataSource('default');
		$query = "insert into ace_rp_groups(name) values('$d')";
		$db->_execute($query);
		$this->redirect($this->referer());
	}
	
	function editGroup()	
	{
		$d = $_REQUEST['uid'];
		$val = $_REQUEST['name'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "update ace_rp_groups set name = '$val' where id = $d";
		$db->_execute($query);
		$this->redirect($this->referer());
	}
	
	function deleteGroup()	
	{
		$d = $_REQUEST['uid'];		
		$db =& ConnectionManager::getDataSource('default');
		$query = "delete from ace_rp_groups_users where group_id = $d";
		$db->_execute($query);
		$query = "delete from ace_rp_groups where id = $d";
		$db->_execute($query);
		$this->redirect($this->referer());
	}
	
	function saveGroupSettings()	
	{
		$id = $_REQUEST['uid'];
		$perBooking = $_REQUEST['perBooking'];
		$perBudget = $_REQUEST['perBudget'];
		$minBooking = $_REQUEST['minBooking'];
		$maxBooking = $_REQUEST['maxBooking'];
		$goalBooking = $_REQUEST['goalBooking'];
		$type = $_REQUEST['type'];
		$lead_color = $_REQUEST['lead_color'];
		$lead_text = $_REQUEST['lead_text'];
		$team_color = $_REQUEST['team_color'];
		$team_text = $_REQUEST['team_text'];
		$db =& ConnectionManager::getDataSource("default");
		$query = "
			UPDATE ace_rp_groups SET 
				perBooking = '$perBooking',
				perBudget = '$perBudget',
				minBooking = '$minBooking',
				maxBooking = '$maxBooking',
				goalBooking = '$goalBooking',
				lead_color = '$lead_color',
				lead_text = '$lead_text',
				team_color = '$team_color',
				team_text = '$team_text',
				type = '$type'				
			WHERE id = $id
		";

		$db->_execute($query);
		$this->redirect($this->referer());
	}
	
	function view()    
	{
		
		$this->Group->id = $_REQUEST['id'];
		$this->set('group', $this->Group->read());
		
		Controller::loadModel('User');
		
		$this->set('users', $this->User->find('all',
			array(
				"User.role_id" => array("3")
			)
		));
	}
   
   //END addition
   
   function saveTransaction()
	{
		$db =& ConnectionManager::getDataSource('default');
		$rowid = $_REQUEST['rowid'];
		$attr = array(
			'userid' => $_REQUEST['userid'],
			'date' => $_REQUEST['date'],
			'time_in' => $_REQUEST['time_in'],
			'time_out' => $_REQUEST['time_out'],
			'addition' => $_REQUEST['addition'],
			'deduction' => $_REQUEST['deduction'],
			'note' => $_REQUEST['note']
		);
		$this->calculateTime($attr);
		if ($rowid)
		{
			$query = "UPDATE whytecl_acesys.ace_rp_payroll_time_sheet 
						 SET user_id = {$attr['userid']}, date = '{$attr['date']}', time_in = '{$attr['time_in']}', time_out = '{$attr['time_out']}',
						     addition = '{$attr['addition']}', deduction = '{$attr['deduction']}', note = '{$attr['note']}',
							 breaks = '{$attr['breaks']}', gross = '{$attr['gross']}', net = '{$attr['net']}' 
					   WHERE id=$rowid";
		}
		else
		{
			$query = "INSERT INTO whytecl_acesys.ace_rp_payroll_time_sheet
					(user_id, date, time_in, time_out, addition, deduction, note, breaks, gross, net) 
					VALUES ({$attr['userid']}, '{$attr['date']}', '{$attr['time_in']}', '{$attr['time_out']}',
							'{$attr['addition']}', '{$attr['deduction']}', '{$attr['note']}', '{$attr['breaks']}',
							'{$attr['gross']}', '{$attr['net']}')";
		}
		$db->_execute($query);
		exit;
	}
}
?>