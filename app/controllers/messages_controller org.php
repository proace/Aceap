<?
error_reporting(E_PARSE  ^ E_ERROR );
class MessagesController extends AppController
{
	//To avoid possible PHP4 problemfss
	var $name = "MessagesController";

	var $uses = array('User', 'Message');

	var $helpers = array('Time','Javascript','Common');
	var $components = array('HtmlAssist','RequestHandler','Common','Lists');
	var $itemsToShow = 20;
	var $pagesToDisplay = 10;
	
	function index()
	{
		$this->layout='edit';
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query = "
			SELECT u.first_name, u.last_name, o.id, o.order_number, o.booking_date, o.customer_phone, o.booking_telemarketer_id
			FROM ace_rp_orders o
			LEFT JOIN ace_rp_users u
			ON o.booking_telemarketer_id = u.id
			LEFT JOIN ace_rp_users_roles ur
			ON ur.user_id = u.id
			WHERE o.order_status_id = 1			
			AND o.recording_confirmed_by_id IS NULL
			AND ur.role_id = 3		
			ORDER BY o.booking_date
			LIMIT 9
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{
			$recordings[$row['id']] = array();
			$recordings[$row['id']]['id'] = $row['id'];
			$recordings[$row['id']]['first_name'] = $row['first_name'];
			$recordings[$row['id']]['last_name'] = $row['last_name'];
			$recordings[$row['id']]['order_number'] = $row['order_number'];
			$recordings[$row['id']]['date'] = $row['booking_date'];
			$recordings[$row['id']]['phone'] = $row['customer_phone'];
			$recordings[$row['id']]['agent_id'] = $row['booking_telemarketer_id'];
		}
		
		$query = "
			SELECT u.first_name, u.last_name, o.id, o.order_number, o.booking_date, o.customer_phone, o.booking_telemarketer_id
			FROM ace_rp_orders o
			LEFT JOIN ace_rp_users u
			ON o.booking_telemarketer_id = u.id
			LEFT JOIN ace_rp_users_roles ur
			ON ur.user_id = u.id
			WHERE o.order_status_id = 1 
			AND o.order_substatus_id = 5
			AND o.job_date IS NULL
			LIMIT 9
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{
			$recordings[$row['id']] = array();
			$recordings[$row['id']]['id'] = $row['id'];
			$recordings[$row['id']]['first_name'] = $row['first_name'];
			$recordings[$row['id']]['last_name'] = $row['last_name'];
			$recordings[$row['id']]['order_number'] = $row['order_number'];
			$recordings[$row['id']]['date'] = $row['booking_date'];
			$recordings[$row['id']]['phone'] = $row['customer_phone'];
			$recordings[$row['id']]['agent_id'] = $row['booking_telemarketer_id'];
		}

		 $query = "
			SELECT COUNT(*) cnt FROM
				(SELECT *, 
					(SELECT COUNT(*) 
					FROM ace_rp_notes n1 
					WHERE n.order_id = n1.order_id 
					AND (n1.urgency_id = 3 OR n1.urgency_id = 4) 
					AND n1.note_date > n.note_date) solutions
				FROM ace_rp_notes n
				WHERE n.urgency_id = 2) d
				LEFT JOIN ace_rp_users u
				ON d.user_id = u.id	
				LEFT JOIN ace_rp_orders o
				ON d.order_id = o.id	
			WHERE solutions = 0
			AND (o.needs_approval = 1)
		";
		
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result);
		$issue_count = $row['cnt']; 
		//$issue_count=0;
		$this->set('issue_count', $issue_count);
		$this->set('recordings', $recordings);
		$this->set('routes', $routes);
	}
	
	function player() {
		$this->layout='edit';
		$phone = $_GET['phone'];

		$db =& ConnectionManager::getDataSource("vicidial");
		
		$query ="
			SELECT `user`, location, filename
			FROM recording_log rl
			WHERE rl.location LIKE '%".$phone."%'
			ORDER BY rl.`start_time` DESC
			LIMIT 10
			";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$records[$row['location']]['user'] = $row['user'];
			$records[$row['location']]['filename'] = $row['filename'];  
			$records[$row['location']]['location'] = $row['location'];  
		}
		
		$this->set('records', $records);
	}
	
	function ShowMessages()
	{
		$this->layout='list';
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$sort = $_GET['sort'];
		$order = $_GET['order'];
		$job_id = $_GET['job_id'];
		if (!$order) $order = 'from_date desc';
		$condition = "";
		if ($job_id) $condition = " and m.file_link=$job_id ";

		$query = "select m.id, m.from_date,
										 m.from_user, concat(fu.first_name, ' ', fu.last_name) from_name,
										 m.to_user, concat(tu.first_name, ' ', tu.last_name) to_name,
										 m.txt, m.file_link, m.customer_link, m.state
								from ace_rp_messages m
								left outer join ace_rp_users tu on tu.id=m.to_user
								left outer join ace_rp_users fu on fu.id=m.from_user
							 where m.state<2 $condition
							   and (m.to_user='".$this->Common->getLoggedUserID()."'
									 or m.from_user='".$this->Common->getLoggedUserID()."'
									 or m.to_role='".$this->Common->getLoggedUserRoleID()."')
							 order by ".$order.' '.$sort;
		
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $items[$row['id']][$k] = $v;
		}		
		
		$this->set('items', $items);
	}

	function saveMessage()
	{
		$this->data['Message']['from_date']=date("Y-m-d H:i:s");
		$this->data['Message']['from_user']=$this->Common->getLoggedUserID();
		$this->data['Message']['to_date']=date("Y-m-d", strtotime($this->data['Message']['to_date']));

		if($this->data['Message']['to_time_hour'] != '')
			$this->data['Message']['to_time'] = $this->data['Message']['to_time_hour'].':'.($this->data['Message']['to_time_min'] ? $this->data['Message']['to_time_min'] : '00');
		
		if($this->data['Message']['to_time_hour'] != '')
			$this->data['Message']['to_time'] = $this->data['Message']['to_time_hour'].':'.($this->data['Message']['to_time_min'] ? $this->data['Message']['to_time_min'] : '00');
		
		$this->Message->id = $this->data['Message']['id'];
		$this->Message->save($this->data);
		
		if ($this->Message->id)
			$cur_id = $this->Message->id;
		else
			$cur_id = $this->Message->getLastInsertId();
		
		echo "<script>window.close();</script>";
		exit;
	}

	// Message editing form 
	function EditMessage()
	{
		$message_id=$_GET['message_id'];
		$file_id=$_GET['order_id'];
		$customer_id=$_GET['customer_id'];
		$to_user_id=$_GET['to_user_id'];
		$text=$_GET['text'];
		
		if (!$file_id) $file_id=$customer_id;
		
		if ($message_id)
		{
			$this->Message->id = $message_id;
			$this->data = $this->Message->read();
		}
		
		if ($text)
		{
			$this->data['Message']['txt']=$text;
		}
	
		$this->data['Message']['file_link']=$file_id;
		$this->data['Message']['to_user']=$to_user_id;
		
		$allSources = $this->Lists->BookingSources();
		if (!$this->data['Message']['from_user']) $this->data['Message']['from_user']=$this->Common->getLoggedUserID();
		$from_user = $allSources[$this->data['Message']['from_user']];

		if (!$this->data['Message']['from_date']) $this->data['Message']['from_date']=date('d M Y H:i');
		$created_date = date('d M Y (H:i)', strtotime($this->data['Message']['from_date']));
		
		if (!$this->data['Message']['to_date']) $this->data['Message']['to_date']=date('d M Y');

		$this->set('from_user', $from_user);
		$this->set('created_date', $created_date);
		$this->set('allSources', $allSources);
	}
	
	//AJAX method. Checks for pending messages for the current user
	function CheckForMessages()
	{
		$allSources = $this->Lists->BookingSources();
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$cur_user = $this->Common->getLoggedUserID();
		$query = "select * from ace_rp_messages where to_user='".$cur_user."'
								 and to_date<=current_date() and state=0
								 and (to_time<=current_time() or to_time is null)
							 order by from_date desc";
		
		$items = array();
		$result = $db->_execute($query);
		$index=0;
		if($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $items[$k] = $v;
			$index++;
			
			$items['from'] = $allSources[$items['from_user']];
		
			echo json_encode($items);
		}
		else
		{
			echo '';
		}
		
		exit;
	}
	
	//AJAX method. Mark the message as read
	function ReadMessage()
	{
		$message_id=$_GET['message_id'];
				
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "update ace_rp_messages set state=1 where id='".$message_id."'";
		$result = $db->_execute($query);

		exit;
	}
	
	//AJAX method. Mark the message as deleted
	function DeleteMessage()
	{
		$message_id=$_GET['message_id'];
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "update ace_rp_messages set state=2 where id='".$message_id."'";
		$result = $db->_execute($query);

		exit;
	}
	
	//AJAX method. Mark all the messages as deleted
	function DeleteAll()
	{
		$cur_user = $this->Common->getLoggedUserID();
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "update ace_rp_messages set state=2 where to_user='".$cur_user."' or from_user='".$cur_user."'";
		$result = $db->_execute($query);

		exit;
	}
	
	function confirmation()
	{
		$order_id = $_GET['order_id'];
		$value = $_GET['value'];		
		$cur_user = $this->Common->getLoggedUserID();
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "
			UPDATE ace_rp_orders SET 
			recording_confirmed_by_id = $cur_user,
			recording_confirmed = $value
			WHERE id = $order_id
		";
		$result = $db->_execute($query);

		exit;
	}
	
	function trackemSettings() {
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$query = "
			SELECT id, name, trackem_id 
			FROM ace_rp_inventory_locations
		";
		
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{		
			$routes[$row['id']] = array();	
			$routes[$row['id']]['id'] = $row['id'];
			$routes[$row['id']]['name'] = $row['name'];	
			$routes[$row['id']]['trackem_id'] = $row['trackem_id'];				
		}
		
		$this->set('routes', $routes);
		$this->set('trackemGPS', $this->Lists->TrackemGPS());
	}
	
	function saveTrackemSettings() {
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);	
				
		foreach($this->data['Route'] as $k => $r) {
		$result = $db->_execute("
			UPDATE ace_rp_inventory_locations 
			SET trackem_id = $r
			WHERE id = $k
		");
			echo $k."=>".$r."<br />";
		}
		
		$this->redirect($this->referer()."?closethis=1");
	}
	
	function trackemAjax() {
		$this->layout='blank';
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$query = "
			SELECT il.id, il.name, il.trackem_id
			FROM ace_rp_orders o
			LEFT JOIN ace_rp_inventory_locations il
			ON il.id = o.job_truck
			WHERE o.job_date = CURDATE()
			AND o.order_status_id = 1
			GROUP BY o.job_truck
		";
		
		$url = "http://live.trackem.com/rss.aspx?id=";
		
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{		
			$routes[$row['id']] = array();	
			$routes[$row['id']]['id'] = $row['id'];
			$routes[$row['id']]['name'] = trim(substr($row['name'], 0, 8));	
			$routes[$row['id']]['trackem_id'] = $row['trackem_id'];

			$trackem_id = $row['trackem_id'];

			if($row['trackem_id'] > 0) {
				$xmlDoc = new DOMDocument();
				$xmlDoc->load($url.$trackem_id);
				
				//get and output "<item>" elements
				$x = $xmlDoc->getElementsByTagName('item')->item(0);

				if($x) { //print only if available
					$description = $x->getElementsByTagName('description')->item(0)->childNodes->item(0)->nodeValue;
					$datetime = $x->getElementsByTagName('pubDate')->item(0)->childNodes->item(0)->nodeValue;
				
					$routes[$row['id']]['where'] = "in ".$description;	
					$routes[$row['id']]['when'] = date('h:i:s A', strtotime($datetime));
				} else {
					$routes[$row['id']]['where'] = "Location unavailable";
					$routes[$row['id']]['when'] = "";	
				}
			} else {
				$routes[$row['id']]['where'] = "GPS not set";
				$routes[$row['id']]['when'] = "";
			}
		}
		$this->set('routes', $routes);
	}

	function messagesAjax() {
		$this->layout='blank';

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$sort = $_GET['sort'];
		$order = $_GET['order'];
		$job_id = $_GET['job_id'];
		if (!$order) $order = 'from_date desc';
		$condition = "";
		if ($job_id) $condition = " and m.file_link=$job_id ";
		
		$query = "select m.id, m.from_date,
										 m.from_user, concat(fu.first_name, ' ', fu.last_name) from_name,
										 m.to_user, concat(tu.first_name, ' ', tu.last_name) to_name,
										 m.txt, m.file_link, m.customer_link, m.state
								from ace_rp_messages m
								left outer join ace_rp_users tu on tu.id=m.to_user
								left outer join ace_rp_users fu on fu.id=m.from_user
							 where m.state = 0	
							and m.to_user='".$this->Common->getLoggedUserID()."'
							 order by ".$order.' '.$sort;
		
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $items[$row['id']][$k] = $v;
		}

		$this->set('items', $items);
	}

	function recordingsAjax() {
		$this->layout='blank';
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$query = "
			SELECT u.first_name, u.last_name, o.id, o.order_number, o.booking_date, o.customer_phone, o.booking_telemarketer_id
			FROM ace_rp_orders o
			LEFT JOIN ace_rp_users u
			ON o.booking_telemarketer_id = u.id
			LEFT JOIN ace_rp_users_roles ur
			ON ur.user_id = u.id
			WHERE o.order_status_id = 1
			AND o.booking_date >= DATE_SUB(CURDATE(), INTERVAL 2 DAY)
			AND o.recording_confirmed_by_id IS NULL
			AND ur.role_id = 3
			ORDER BY o.booking_date
			LIMIT 9
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{		
			$recordings[$row['id']] = array();	
			$recordings[$row['id']]['id'] = $row['id'];
			$recordings[$row['id']]['first_name'] = $row['first_name'];
			$recordings[$row['id']]['last_name'] = $row['last_name'];
			$recordings[$row['id']]['order_number'] = $row['order_number'];
			$recordings[$row['id']]['date'] = $row['booking_date'];
			$recordings[$row['id']]['phone'] = $row['customer_phone'];
			$recordings[$row['id']]['agent_id'] = $row['booking_telemarketer_id'];
		}		
		
		$this->set('recordings', $recordings);
	}
		
	function issueGatherer() {
		$this->layout='blank';
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$query = "
			SELECT d.*, CONCAT(u.first_name, ' ', u.last_name) noted_by, o.order_number 
			FROM (SELECT *, 
					(SELECT COUNT(*) FROM ace_rp_notes n1 WHERE n.order_id = n1.order_id AND (n1.urgency_id = 3 OR n1.urgency_id = 4) AND n1.note_date > n.note_date) solutions
				FROM ace_rp_notes n
				WHERE n.urgency_id = 2) d
				LEFT JOIN ace_rp_users u
				ON d.user_id = u.id	
				LEFT JOIN ace_rp_orders o
				ON d.order_id = o.id
			WHERE solutions = 0
			AND (o.needs_approval = 1)
		";
		
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{		
			$issues[$row['id']]['message'] = $row['message'];
			$issues[$row['id']]['order_id'] = $row['order_id'];
			$issues[$row['id']]['order_number'] = $row['order_number'];
			$issues[$row['id']]['noted_by'] = $row['noted_by'];
			
		}	
		
		$this->set('issues', $issues);
	}
	
	function noteGatherer() {
		$this->layout='blank';
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$query = "
			SELECT d.*, CONCAT(u.first_name, ' ', u.last_name) noted_by, o.order_number 
			FROM (SELECT *, 
					(SELECT COUNT(*) FROM ace_rp_notes n1 WHERE n.order_id = n1.order_id AND (n1.urgency_id = 3 OR n1.urgency_id = 4) AND n1.note_date > n.note_date) solutions
				FROM ace_rp_notes n
				WHERE n.urgency_id = 2) d
				LEFT JOIN ace_rp_users u
				ON d.user_id = u.id	
				LEFT JOIN ace_rp_orders o
				ON d.order_id = o.id
			WHERE solutions = 0
			AND (o.needs_approval = 1)
		";
		
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{		
			$issues[$row['id']]['message'] = $row['message'];
			$issues[$row['id']]['order_id'] = $row['order_id'];
			$issues[$row['id']]['order_number'] = $row['order_number'];
			$issues[$row['id']]['noted_by'] = $row['noted_by'];
			
		}	
		
		$this->set('issues', $issues);	
	}
	
	function getNotes() {
		$this->layout='blank';
		
		$order_id = $_POST['order_id'];
		$last_id = isset($_POST['last_id'])?$_POST['last_id']:0;
		
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
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $notes[$row['id']][$k] = $v;
		}
		
		$this->set('notes', $notes);	
	}
	
}
?>
