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

		/*$query = "
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
		$issue_count = $row['cnt'];*/
		$issue_count=0;
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
	function getMessages()
	{
		$toUser = $this->Common->getLoggedUserID();
		$currentDate = date('Y-m-d');
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "SELECT count(*) as total from ace_rp_messages where to_user=".$toUser." AND to_date='".$currentDate."' AND state= 0";
		
		$result = $db->_execute($query);

		$row = mysql_fetch_array($result);
		$total = $row['total'];    	
		$data=array('total'=>$total);
		echo json_encode($data);
    	
    	
		exit();
	}
	function markAsRead()
	{
		$message_id=$_GET['message_id'];
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "update ace_rp_messages set state=1 where id='".$message_id."'";
		$result = $db->_execute($query);
		exit;
	}
	function ShowMessages()
	{
		$fromMessage = isset($this->params['url']['fromMessage']) ? $this->params['url']['fromMessage'] :0;
		$toUser = $this->Common->getLoggedUserID();
		$currentDate = date('Y-m-d');
		$this->layout='list';
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$sort = $_GET['sort'];
		$order = $_GET['order'];
		$job_id = $_GET['job_id'];
		 if (!$order) $order = 'from_date desc';
		$condition = "";
		if ($job_id) $condition = " and m.file_link=$job_id ";

		if($fromMessage)
		{
			
			// $messages = "select m.id, m.from_date,m.state,
			// 							 m.from_user, concat(fu.first_name, ' ', fu.last_name) from_name,
			// 							 m.to_user, concat(tu.first_name, ' ', tu.last_name) to_name,
			// 							 m.txt, m.file_link, m.customer_link, m.state
			// 					from ace_rp_messages m
			// 					left outer join ace_rp_users tu on tu.id=m.to_user
			// 					left outer join ace_rp_users fu on fu.id=m.from_user
			// 				 where m.state = 0 and m.to_user='".$this->Common->getLoggedUserID()."' and m.to_date='".$currentDate."'
			// 				 order by to_date desc";		
			$messages = "select m.id, m.from_date,m.state,
										 m.from_user, concat(fu.first_name, ' ', fu.last_name) from_name,
										 m.to_user, concat(tu.first_name, ' ', tu.last_name) to_name,
										 m.txt, m.file_link, m.customer_link, m.state
								from ace_rp_messages m
								left outer join ace_rp_users tu on tu.id=m.to_user
								left outer join ace_rp_users fu on fu.id=m.from_user
							 where m.state = 0 and m.to_user='".$this->Common->getLoggedUserID()."' and m.to_date='".$currentDate."'
							 order by m.id desc limit 1";	
		} else {
			$messages = "select m.id, m.from_date,m.state,
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
		}
		
		$newMessage = array();
		$messageIds = array();
		$result = $db->_execute($messages);
		$allSources = array("0" => "---");

		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$newMessage = $row;
			$messageIds[] = $row['id'] ;

		}

		$this->set('newMessage', $newMessage);
		$this->set('allSources', $allSources); 
		if($fromMessage) 
		{
			$ids = implode(', ', $messageIds);
			if(!empty($ids))
			{
				$query = "UPDATE ace_rp_messages set state=1 where id IN (".$ids.")";
				$result = $db->_execute($query);
			}
				
		}
		
		
	}

	function saveMessage()
	{

		$is_reply = $_POST['is_reply'];
		$this->data['Message']['from_date']= date("Y-m-d H:i:s");
		$this->data['Message']['from_user']= $this->Common->getLoggedUserID();
		$this->data['Message']['to_date']= date("Y-m-d", strtotime($this->data['Message']['to_date']));
		$db =& ConnectionManager::getDataSource('default');
		$toUserList = implode(', ', $this->data['Message']['to_user']); 
		// Send reply to user
		if($is_reply == 1){
			
			$this->data['Message']['to_user'] = $_POST['to_user'];
			$db->_execute("INSERT INTO `ace_rp_messages` (`to_date`,`to_user`,`txt`,`from_date`,`from_user`) VALUES ('".$this->data['Message']['to_date']."',".$this->data['Message']['to_user'].", '".$this->data['Message']['txt']."','".$this->data['Message']['from_date']."',".$this->data['Message']['from_user'].")");
			echo "<script>
				 window.opener.close();
				window.close();
			</script>";
		} 
		else {
			if($this->data['Message']['to_user'][0] != 0){
				if($this->data['Message']['to_time_hour'] != '')
					$this->data['Message']['to_time'] = $this->data['Message']['to_time_hour'].':'.($this->data['Message']['to_time_min'] ? $this->data['Message']['to_time_min'] : '00');
				
				if($this->data['Message']['to_time_hour'] != '')
					$this->data['Message']['to_time'] = $this->data['Message']['to_time_hour'].':'.($this->data['Message']['to_time_min'] ? $this->data['Message']['to_time_min'] : '00');
				$this->Message->id = $this->data['Message']['id'];
				
				foreach ($this->data['Message']['to_user'] as $key => $value) {						
						$this->data['Message']['to_user'] = $value;
						$result = $db->_execute("INSERT INTO `ace_rp_messages` (`to_date`,`to_user`,`txt`,`from_date`,`from_user`) VALUES ('".$this->data['Message']['to_date']."',".$value.", '".$this->data['Message']['txt']."','".$this->data['Message']['from_date']."',".$this->data['Message']['from_user'].")");
					}

				if ($this->Message->id)
					$cur_id = $this->Message->id;
				else
					$cur_id = $this->Message->getLastInsertId();
				
				echo "<script>
				window.opener.close();
				window.close();
				</script>";
				exit();
			} 
		else if($this->data['Message']['to_user'][0] == 0 && $this->data['Message']['search_user'][0] != 0) {
				
				if($this->data['Message']['to_time_hour'] != '')
				{
					$this->data['Message']['to_time'] = $this->data['Message']['to_time_hour'].':'.($this->data['Message']['to_time_min'] ? $this->data['Message']['to_time_min'] : '00');
				}

				$this->Message->id = $this->data['Message']['id'];
				foreach ($this->data['Message']['search_user'] as $key => $value) {
						$db->_execute("INSERT INTO `ace_rp_messages` (`to_date`,`to_user`,`txt`,`from_date`,`from_user`) VALUES ('".$this->data['Message']['to_date']."',".$value.", '".$this->data['Message']['txt']."','".$this->data['Message']['from_date']."',".$this->data['Message']['from_user'].")");
				}
				if ($this->Message->id)
					$cur_id = $this->Message->id;
				else
					$cur_id = $this->Message->getLastInsertId();
				
				echo "<script>
				window.opener.close();
				window.close();
				</script>";
				exit();
			}
			else {
					echo "<script>
					alert('Please select atleast one receiver.');
					window.history.back();
					</script>";
					exit();
			}
		}
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
		// If we are sending reply
		$is_reply = isset($_GET['is_reply']) ? $_GET['is_reply'] : 0;
		
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
		
		// $allSources = $this->Lists->BookingSources();
		$allSources = array("0" => "---");
		if (!$this->data['Message']['from_user']) $this->data['Message']['from_user']=$this->Common->getLoggedUserID();
		$from_user = $allSources[$this->data['Message']['from_user']];

		if (!$this->data['Message']['from_date']) $this->data['Message']['from_date']=date('d M Y H:i');
		$created_date = date('d M Y (H:i)', strtotime($this->data['Message']['from_date']));
		
		if (!$this->data['Message']['to_date']) $this->data['Message']['to_date']=date('d M Y');

		$db =& ConnectionManager::getDataSource('default');
		$result = $db->_execute("select b.role_id, a.id, CONCAT(a.first_name,' ',a.last_name) as name 
				  from ace_rp_users a, ace_rp_users_roles b
				 where a.is_active=1 and a.id=b.user_id
				   and b.role_id in (1,6,3) order by b.role_id DESC, name");
		$searchUsers = array();
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$searchUsers[$row['id']] = $row['name'];
		}
		// print_r($searchUsers);
		$this->set('searchUsers', $searchUsers);
		$this->set('to_user', $to_user_id);
		$this->set('is_reply', $is_reply);
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
	
	//Loki: get incoming internal message
	function getInternalMessage()
	{
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$currentDate = date("Y-m-d");
		$messageData = "select m.id, m.from_date,m.state,m.txt as message,
					 m.from_user, concat(fu.first_name, ' ', fu.last_name) from_name,
					 m.to_user, concat(tu.first_name, ' ', tu.last_name) to_name,
					 m.txt, m.file_link, m.customer_link, m.state
					from ace_rp_messages m left outer join ace_rp_users tu on tu.id=m.to_user
					left outer join ace_rp_users fu on fu.id=m.from_user
				 where m.state = 0 and m.to_user='".$this->Common->getLoggedUserID()."' and m.to_date='".$currentDate."' order by to_date desc";		
		$messages = array();
		$messageIds = array();
		$result = $db->_execute($messageData);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $messages[$row['id']][$k] = $v;
			  $messageIds[] = $row['id'] ;

		}
		$this->set('messages', $messages);
		$this->layout = false;
		$this->render('pages/message');
		exit();
	}

	// Loki: Set message frame
	function sendTextMessage() 
	{	
		// error_reporting(E_ALL);
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$phone_number = isset($_POST['phone_num']) ? $_POST['phone_num']: '';
		$message = isset($_POST['message']) ? mysql_real_escape_string($_POST['message']) : '';
		$today = gmdate("Y-m-d\TH:i:s\Z");
		$sender_id = $this->Common->getLoggedUserID();
		if(!empty($phone_number))
		{
			$response = $this->Common->sendTextMessage($phone_number, $message); 
			if(!empty($response))
			{
				$query = "INSERT INTO ace_rp_sms_log (order_id, customer_id, log_id, message, sms_date, phone_number, sms_type, sender_id) VALUES ('','', ".$response->id.",'".$message."','".$today."', '".$phone_number."',1, ".$sender_id.")";
				$result = $db->_execute($query);	
				if($result)
				{
					$data  = array("res" => "OK");
					echo json_encode($data);
					exit();

				}
			}
		}

	}

	
	 // <ul class="icon-set">
	       // <li><i class="fa fa-meh-o" aria-hidden="true"></i></li>
	       // <li><i class="fa fa-calendar" aria-hidden="true"></i></li>
	       // <li><i class="fa fa-file-text" aria-hidden="true"></i></li>
	//  </ul>
	// Loki: get the message history
	function getMessageHistory() 
	{	
		$this->layout = false;
		// error_reporting(E_ALL);
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$phone_number = $_POST['phone_number'];
		$messageId = $_POST['messageId'];
		$updateMessageRead = $db->_execute("UPDATE ace_rp_sms_log set is_read = 1 where id=".$messageId);
		$textData = "SELECT concat(cu.first_name, ' ', cu.last_name) from_name, sl . * FROM 			ace_rp_sms_log sl LEFT JOIN ace_rp_users cu ON  cu.id = sl.sender_id
					WHERE sl.phone_number = '".$phone_number."' GROUP BY sl.id ORDER BY sms_date DESC";
		$result = $db->_execute($textData);
		$msg = '<div class="chat-footer">
            <div class="chat-form">
                <form>
                    <div class="form-grp">
                        <div class="form-input"> <input id="text_message" type="text" placeholder="Enter text" ></div>
                        <div class="form-submit"><input id="send_text_message_to_user" type="submit" class="submit"></div>
                    </div>
                </form>
            </div>
            <div class="footer-bottom-nav">
				<p class="chat-counter">0/420</p>
            </div>
        </div>
		<div class="chat-header">
		<div class="chat-h-option">
                <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
            </div>
		<div class="contact-user">
				<input type="hidden" id="phone_number" value="'.$phone_number.'">
                <p class="c-user-name"><i class="fa fa-mobile" aria-hidden="true"> ('.$phone_number.')</i></p>
            </div>
            <div class="user-status">
                <span class="" id="close_chat_popup"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            </div>
        </div>
        <div class="chat-body">
            <ul class="chat-msgs">';
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			//receive-msg sent-msg
			$orgDate = $this->Common->convertTimeZone($row['sms_date']);

			if($row["sms_type"]== 1) 
			{ 
				$class =  'sent-msg';
				$userName = $row['from_name'].',';
			} else if($row["sms_type"]== 2){
			  	$class =  'receive-msg';
			  	$userName = '';
			}
			 $msg .= '<li class="'.$class.'">
	                    <p class="user-name-date"><span class="user-name-span"><b>'.$userName.'</b></span> <span class="date-span">'.$orgDate.'</span></p> 
	                <span class="msg-text">'.$row['message'].'</span>
	                 </li>';
		}
		$msg .='</ul>
        </div>';

        echo $msg;
        exit();
		
	}

	//Loki: get the count of the new message
	function getNewMessage()
	{
		$this->layout = false;
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		// $textData = "SELECT id from ace_rp_sms_log where sms_type = 2 AND is_read = 0 order by sms_date desc limit 1";
		$textData = "SELECT sl.message, sl.phone_number, sl.sms_type, sl.id from ace_rp_sms_log sl where sl.sms_type = 2 AND sl.is_read = 0 order by sms_date desc limit 1";

		$result = $db->_execute($textData);
		$messages = array();
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		if(!empty($row['id']))
		{
			$res .= '<div class="textus-ConversationListItem-link textus-ConversationListItem-preview" onclick="showMessageHistory('.$row["phone_number"].')" get_text_id="'.$row["id"].'">
			<input type="hidden" id="message_id" value="'.$row['id'].'"?>
            		<h4 class="textus-ConversationListItem-contactName">'.$row["phone_number"].'</h4>
            		<div class="textus-ConversationListItem-previewDetails"><span class="textus-ConversationListItem-previewMessage">'. $row["message"].'
            		</div>
       			 </div>';
       			 echo $res;
       			 exit();
		}
		exit();
	}

	function showUnreadMesssages()
	{
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$textData = "SELECT sl.message, sl.phone_number, sl.sms_type, sl.id from ace_rp_sms_log sl where sl.sms_type = 2 AND sl.is_read = 0 order by sms_date desc group by sl.phone_number";

		$result = $db->_execute($textData);
		$messages = array();
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$res = '';
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$res .= '<div class=" textus-ConversationListItem-link textus-ConversationListItem-preview" onclick="showMessageHistory('.$row["phone_number"].')">
			<input type="hidden" id="message_id" value="'.$row['id'].'"?>
            		<h4 class="textus-ConversationListItem-contactName">'.$row["phone_number"].'</h4>
            		<div class="textus-ConversationListItem-previewDetails"><span class="textus-ConversationListItem-previewMessage">'. $row["message"].'
            		</div>
       			 </div>';
       			 echo $res;
		}	
		die();

		exit();
	}

	//Loki: Get the sources for messaging according to role id.
		function getMessageSourceList()
		{
				error_reporting(E_ALL);
				$roleId = $_GET['role'];
				$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
				
				$result = $db->_execute("
				select b.role_id, a.id, CONCAT(a.first_name,' ',a.last_name) as name 
				  from ace_rp_users a, ace_rp_users_roles b
				 where a.is_active=1 and a.id=b.user_id
				   and b.role_id =".$roleId." order by b.role_id DESC, name");
				$Ret = array();
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$Ret[$row['id']] = $row['name'];
				}
				echo(json_encode($Ret));
				exit();
		}
}
?>
