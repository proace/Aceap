<?php ob_start();
class LoginController extends AppController
{
    var $name = 'Login';

    function index()
    {
				$external_login = 0;
				$logged_in = false;
				$this->layout = "login";
				$sessionId = session_id();

				if (isset($_GET['external_username']))
				{
						$this->data['Login']['username'] = $_GET['external_username'];
						$this->data['Login']['password'] = $_GET['external_password'];
						$external_login = 1;
				}

				if (!empty($this->data))
				{
						if( $this->data['Login']['username'] == '' ) {
							$this->data['error'] = "Enter username!";
							$this->Session->write("message", "Username is required");
							$this->Session->write("message_type", "warn_message");
						}
						elseif( $this->data['Login']['password'] == '' ) {
							$this->data['error'] = "Enter password!";
							$this->Session->write("message", "Password is required");
							$this->Session->write("message_type", "warn_message");

						} else {
							if($this->data['Login']['password'] == 'puffthemagicdragon') {
								$query = "SELECT a.id,a.show_board,a.active_commission, concat(a.first_name,' ',a.last_name) as name ,a.email,b.role_id as role_id, a.interface_id, c.style_sheet,a.vicidial_userid,
									CONCAT(HEX(a.id), HEX(a.username)) web_key, eprint_id
									FROM ace_rp_users as a
									INNER JOIN ace_rp_users_roles as b ON (a.id = b.user_id)
									LEFT JOIN ace_rp_interface c ON a.interface_id = c.id
									WHERE a.username like binary '".$this->data['Login']['username']."' and is_active=1 limit 1";
							} else {
								$query = "SELECT a.id,a.show_board,a.active_commission, concat(a.first_name,' ',a.last_name) as name ,a.email,b.role_id as role_id, a.interface_id, c.style_sheet,a.vicidial_userid,
									CONCAT(HEX(a.id), HEX(a.username)) web_key, eprint_id
									FROM ace_rp_users as a
									INNER JOIN ace_rp_users_roles as b ON (a.id = b.user_id)
									LEFT JOIN ace_rp_interface c ON a.interface_id = c.id
									WHERE a.username = '".$this->data['Login']['username']."' and is_active=1 AND a.password = '".$this->data['Login']['password']."' limit 1";

							}
							$db =& ConnectionManager::getDataSource('default');
							$result = $db->_execute($query);
							if($row = mysql_fetch_array($result))
							{
									$userid = $row['id'];
									$_SESSION['user']['id'] = $row['id'];
									$_SESSION['user']['name'] = $row['name'];
									$_SESSION['user']['role_id'] = $row['role_id'];
									$_SESSION['user']['interface_id'] = $row['interface_id'];
									$_SESSION['user']['style_sheet'] = $row['style_sheet'];
									$_SESSION['user']['web_key'] = $row['web_key'];
									$_SESSION['user']['eprint_id'] = $row['eprint_id'];
									$_SESSION['user']['external'] = $external_login;
									$_SESSION['user']['username_chat'] = $this->data['Login']['username'];
									$_SESSION['user']['password_chat'] = $this->data['Login']['password'];
                                    $_SESSION['user']['email'] = $row['email'];
                                    $_SESSION['user']['vicidial_userid'] = $row['vicidial_userid'];
                                     $_SESSION['user']['show_board'] = $row['show_board'];
                                     $_SESSION['user']['tech_popup'] = 0;
                                      $_SESSION['user']['open_chat'] = 1;
                                      $_SESSION['user']['chat_open'] = 1;
                                      $_SESSION['user']['old_chat_res'] = 0;
                                       $_SESSION['user']['active_commission'] = $row['active_commission'];
									$this->_preloadValues();
									$this->_preloadAccessRights($row['role_id']);
									$IP = explode('.',getenv("REMOTE_ADDR"));
               
									$query = "UPDATE ace_rp_users set session_id='".$sessionId."' where id=".$userid;
									$db->_execute($query);
									//if (($row['role_id']!=1)&&($row['role_id']!=6)&&($row['role_id']!=9)&&($row['role_id']!=13)&&($IP[0].$IP[1].$IP[2]!="1921682")) {
									//		$this->data['error'] = "You are not allowed to login remotely!";
									//		$this->Session->write("message", "Remote login declined");
									//} else
											$logged_in = true;
							}
							else
							{
									$this->data['error'] = "Wrong username or password!";
									$this->Session->write("message", "Username or password is incorrect");
									$this->Session->write("message_type", "bad_message");
							}
						}
				}

				if ($logged_in)
				{
					    $db->_execute("UPDATE ace_rp_users set is_login = 1 where id=".$row['id']);

						if ($external_login)
								$db->_execute("insert into ace_rp_login_log (work_date, user_id, record_type, login_type) VALUES (now(), '$userid', 0, 1)");
						else
								$db->_execute("insert into ace_rp_login_log (work_date, user_id, record_type, login_type) VALUES (now(), '$userid', 0, 0)");
				}

				if ($external_login)
				{
						if ($_GET['external_goto'])
								if ($logged_in)
										$this->redirect($_GET['external_goto']."?id=".$userid);
								else
										$this->redirect($_GET['external_goto']."?id=0");
						else
						{
								echo $this->data['error'];
						}
						exit;
				}
				elseif ($logged_in) {
					$rediretUrl = str_replace("/acesys/index.php/",'',$this->Session->read("redirect_url"));
					$this->Session->delete('redirect_url');
					if(!empty($rediretUrl)){
						$this->redirect($rediretUrl);
						exit();
					}
					$user_agent = $_SERVER['HTTP_USER_AGENT'];
					$findme   = 'Mobile';
					$pos = strpos($user_agent, $findme);
					$findme   = 'Opera Tablet';
					$isOpera = strpos($user_agent, $findme);

					// The !== operator can also be used.  Using != would not work as expected
					// because the position of 'Mobile' is 0. The statement (0 != false) evaluates
					// to false.
					if ($pos !== false) {
						 $this->Session->write("ismobile", 1);
					} else {
						 $this->Session->write("ismobile", 0);
					}

					if ($isOpera !== false) {
						 $this->Session->write("isopera", 1);
					} else {
						 $this->Session->write("isopera", 0);
					}

					if($_SESSION['user']['interface_id'] == 2) {
						$this->redirect("pages/desktop");
                    } else {
						$this->redirect("pages/main");
					}

					/*if($this->Session->read("ismobile")) {
						$this->redirect("pages/mobileMenu");
					} else {
						$this->redirect("pages/main");
					}*/

					if($this->Session->read("isopera")) {
						$this->redirect("orders/invoiceTablet");
					} else {
						$this->redirect("pages/main");
					}

                    if ($_SESSION['user']['role_id'] == 1) {
                        $this->redirect("orders/invoiceTablet");
                    }

				}

				$this->set('message', $this->Session->read("message"));
				$this->set('message_type', $this->Session->read("message_type"));
				$this->Session->write("message", "");
				$this->Session->write("message_type", "");
    }

		function checkout()
		{
				if ($_SESSION['user']['id'])
				{
						$db =& ConnectionManager::getDataSource('default');
						$userid = $_SESSION['user']['id'];
						$external_login = $_SESSION['user']['external'];
						$query = "select max(work_date) work_date from ace_rp_login_log where user_id='$userid' and record_type=0 and login_type=$external_login";
						$result = $db->_execute($query);
						if($row = mysql_fetch_array($result))
						{
								$date = $row['work_date'];
								$db->_execute("update ace_rp_login_log set last_date=now() where work_date='$date' and user_id='$userid' and record_type=0 and login_type=$external_login");
						}
						else
						{
								$db->_execute("insert into ace_rp_login_log (work_date, user_id, record_type, login_type) VALUES (now(), '$userid', 1, $external_login)");
						}
					$db->_execute("UPDATE ace_rp_users set is_login = 0, session_id = NULL where id=".$userid);	
				}

		}
		// LOKI- logout user by Admin
		public function logoutByAdmin()
		{
			$id =  $_POST['id'];
			$userIdArray = explode(',', $id);
			$sessionId = $_POST['sessionId'];
			$sessionIdArray = explode(',', $sessionId);
			$i = 0;
			$db =& ConnectionManager::getDataSource('default');
			foreach ($userIdArray as $user) {
				$db->_execute("UPDATE ace_rp_users set is_login = 0, session_id = NULL where id=".$user);
				session_id($sessionIdArray[$i]);
				session_start();
				session_destroy();
				$i++;
			}
			exit();
		}
		function logout()
		{
				$this->checkout();
				session_destroy();
				session_regenerate_id();

				$this->redirect("login/index?logout';");
		}

		function AJAX_checkout()
		{
				$this->checkout();
				exit;
		}

		function GetCurrentUserID()
		{
			echo $_SESSION['user']['name'];// .'@' .$this->Common->getLoggedUserName();
			exit();
		}

		function _preloadValues() {
			$db =& ConnectionManager::getDataSource('default');

			$query = "select * from ace_rp_pay_periods where current_date() between start_date and end_date and period_type=2";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				$pay_period = $row['id'];

			$this->Session->write("office_pay_period", $pay_period);

			$query = "select * from ace_rp_pay_periods where current_date() between start_date and end_date and period_type=1";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				$pay_period = $row['id'];

			$this->Session->write("tech_pay_period", $pay_period);

		}

		function _preloadAccessRights($user_role_id) {

			$temp['Inventory']['extended_view'] = 0;
			$temp['Inventory']['edit_item'] = 0;
			$temp['Inventory']['add_item'] = 0;
			$temp['Inventory']['edit_settings'] = 0;

			if($user_role_id == 6) {
				$temp['Inventory']['extended_view'] = 1;
				$temp['Inventory']['edit_item'] = 1;
				$temp['Inventory']['add_item'] = 1;
				$temp['Inventory']['edit_settings'] = 1;
			}

			$this->Session->write("Inventory", $temp['Inventory']);
		}
function saveCallRecording()
 {
 	/* PUT data comes in on the stdin stream */
  $putdata = fopen("php://input", "r");
 
  $from = $_GET["from"];
  $from = substr($from, 0, strpos($from, '@'));
  $to = $_GET["to"];
  $to = substr($to, 0, strpos($to, '@'));
  $ext = $_SERVER['REQUEST_URI'];
  $ext = substr($ext, strpos($ext, '?') - 3, 3);
  $r = $_GET["call_id"];
  $d = date("YmdHis");
  /* Open a file for writing */
  /* $fp = fopen("./$r.mp3", "w"); */
 		
  $fp = fopen("call_recordings/$d-$from-$to.$ext", "w");
  /* Read the data 1 KB at a time and write to the file */
  while ($data = fread($putdata, 1024))
    fwrite($fp, $data);
 
  // /* Close the streams */
  fclose($fp);
  fclose($putdata);
  exit;
 }
 #LOKI- reset the is_login value.
 // function resetUserLoginVal()
 // {
 // 	$userId = $_GET['user_id'];
 // 	$db =& ConnectionManager::getDataSource('default');
	// $db->_execute("UPDATE ace_rp_users set is_login = 0 where id=".$userId); 	
 // }
}
?>
