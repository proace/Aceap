<?
error_reporting(E_PARSE  ^ E_ERROR );
class ChatController extends AppController
{
	//To avoid possible PHP4 problemfss
	var $name = "ChatController";

	var $uses = array('User', 'Message');

	var $helpers = array('Time','Javascript','Common');
	var $components = array('HtmlAssist','RequestHandler','Common','Lists');
	var $itemsToShow = 20;
	var $pagesToDisplay = 10;
	var $layout='plain';
	
	function index()
	{
		$this->layout='edit';
	}
	
	//Returns current chat content
	function _GetCurrent($user_name, $user_email, $show_past)
	{
		$conditions = 'date(date)=current_date()';
		if ($show_past==1) $conditions = '1=1';
		
		// A chat with the selected user
		$db =& ConnectionManager::getDataSource('default');
		if (!$_SESSION['user']['id'])
			$query = "select * from ace_rp_chat where $conditions and user_name!='' and user_email!='' and user_name='$user_name' and user_email='$user_email' order by date";
		else
			$query = "select * from ace_rp_chat where $conditions and user_name='$user_name' and user_email='$user_email' order by date";
		$messages = "<b></b>";
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			if (!$row['answer_by_id'])
				$messages .= "&nbsp;<b class='user_chat'>".$row['user_name'].":</b> ".$row['message']."<br/>";
			else
				$messages .= "&nbsp;<b class='ace_chat'>".$row['answer_by_name'].":</b> ".$row['message']."<br/>";
		}		
		
		return $messages;
	}
	
	function CheckForChat()
	{
		$db =& ConnectionManager::getDataSource('default');
		$query = "select count(*) cnt from ace_rp_chat where status=0 and user_name!='' and user_email!=''";
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		echo $row['cnt'];
		exit;
	}
	
	//AJAX method. Sends an answer to the web-chat
	function AdminMessage()
	{
		$user_name=addslashes($_GET['user_name']);
		$user_email=addslashes($_GET['user_email']);
		$message_text=addslashes($_GET['message_text']);
		$sender_id=addslashes($_SESSION['user']['id']);
		$sender_name=addslashes($_SESSION['user']['name']);
		
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO whytecl_acesys.ace_rp_chat
								(user_name, user_email, message, date, answer_by_id, answer_by_name) 
							VALUES ('$user_name', '$user_email', '$message_text', now(), $sender_id, '$sender_name')";
		$result = $db->_execute($query);

		exit;
	}
	
	//AJAX method. Catches chat messages from the web-site
	function UserMessage()
	{
		$user_name=addslashes($_GET['user_name']);
		$user_email=addslashes($_GET['user_email']);
		$message_text=addslashes($_GET['message_text']);
		
		$db =& ConnectionManager::getDataSource('default');
		$query = "INSERT INTO whytecl_acesys.ace_rp_chat
							(user_name, user_email, message, date) 
							VALUES ('$user_name', '$user_email', '$message_text', now())";
		$result = $db->_execute($query);

		exit;
	}
	
	//AJAX method. Returns current chat content
	function GetCurrent()
	{
		$user_name = addslashes($_GET['user_name']);
		$user_email = addslashes($_GET['user_email']);
		$show_past = $_REQUEST['show_past'];

		$messages = $this->_GetCurrent($user_name, $user_email, $show_past);
		
		echo $messages;
		exit;
	}
	
	//AJAX method. Stops a conversation and marks
	function StopChat()
	{
		$user_name=addslashes($_GET['user_name']);
		$user_email=addslashes($_GET['user_email']);
		
		$db =& ConnectionManager::getDataSource('default');
		$query = "update whytecl_acesys.ace_rp_chat set status=1 where user_name='$user_name' and user_email='$user_email'";
		$result = $db->_execute($query);

		exit;
	}
	
	function ShowMessages()
	{
		$this->layout='list';
		$db =& ConnectionManager::getDataSource('default');

		$user_name = addslashes($_GET['user_name']);
		if ($user_name=='Internal chat') $user_name = '';
		$user_email = addslashes($_GET['user_email']);

		$show_past = $_REQUEST['show_past'];
		$conditions = 'where status=0';
		if ($show_past==1) $conditions = '';
		
		// The list of active users
		$query = "select user_name, user_email from ace_rp_chat $conditions group by user_name, user_email";
		$id = 0;
		$all_users = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$id++;
			if (!$row['user_name']) $row['user_name'] = 'Internal chat';
			foreach ($row as $k => $v)
			  $all_users[$id][$k] = $v;
		}		

		// A chat with the selected user
		$this->set('all_users', $all_users);
		$this->set('messages', $this->_GetCurrent($user_name, $user_email));
		$this->set('user_name', $user_name);
		$this->set('user_email', $user_email);
		$this->set('show_past', $show_past);
	}
         
       function ace_wordpress_cake_chat(){
         if($_SESSION['user']['id'] =='') header('location:acecare.ca/acesys');

        } 
}
?>
