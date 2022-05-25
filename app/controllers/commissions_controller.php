<? ob_start();
// error_reporting(E_ALL);
// error_reporting(1);

class CommissionsController extends AppController

{

	var $name = "CommissionsController";

	var $uses = array('Commission', 'User', 'Order','TechQualification','Setting');

	var $helpers = array('Common');

	var $components = array('HtmlAssist','Common','Lists','Tcpdf','Mpdf');

	var $itemsToShow = 20;

	var $pagesToDisplay = 10;

	var $layout="edit";

	var $G_URL  = "http://hvacproz.ca";
	
	function editTech($user_id = null)

	{
		$this->layout = "edit";
		$loggedUser = $this->Common->getLoggedUserRoleID();

		// echo "<pre>";
		// print_r($_POST); die;
		// print_r($this->data); die;
		if (!empty($this->data))

		{
			// $this->Common->printData($_POST['pending_tech']);

			$user_id = $_POST['userid'];

			$categories = $this->Lists->ListTable('ace_rp_order_types','category_id=2 and show_tech_commission = 1');

			$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

			//Delete previous commissions' settings

			$db->_execute("DELETE FROM ace_rp_commissions WHERE user_id=".$user_id);

			//Save new data

			foreach($this->data['Rates'] as $com_type => $val1)

				foreach($val1 as $partner_type => $val2)

					foreach($val2 as $category => $rate)

						$db->_execute("INSERT INTO ace_rp_commissions (user_id, commission_type_id, partner_role_id, category_id, commission)

											VALUES ('".$user_id."','".$com_type."','".$partner_type."','".$category."','".$rate."')");

			
			foreach($this->data['comm'] as $com_type => $val1)
			{
				foreach($val1 as $partner_type => $val2)
				{
					foreach ($categories as $key => $value) {
						$db->_execute("INSERT INTO ace_rp_commissions (user_id, commission_type_id, partner_role_id, category_id, commission) VALUES ('".$user_id."','".$com_type."','".$partner_type."','".$key."','".$val2[39]."')");
					}
				}
			}

			if ($user_id>=0)
			{
				if ($user_id>0) $this->User->id = $user_id;

				$this->data['User']['start_date'] = date('Y-m-d',strtotime($this->data['User']['start_date']));
				$this->data['User']['end_date'] = date('Y-m-d',strtotime($this->data['User']['end_date']));
				
				$this->User->save($this->data);

				
				if ($user_id==0 && $loggedUser == 6)

				{
					$user_id = $this->User->getLastInsertId();
					
					$db->_execute("INSERT INTO ace_iv_locations (type,`number`) VALUES ('tech',".$user_id.")");
					if($_POST['pending_tech'] == 1) 
					{
						$db->_execute("update ace_rp_users set is_active='2' where id=".$user_id);
						$url = $this->G_URL.BASE_URL."/login";
	        			$link = '<a href='\.$url.\' target="_blank">Click Here</a>'; 
						
						$subject = 'Pro Ace Heating & Air Conditioning Ltd';
						$msg = '<p>Dear {first_name} {last_name},</p>
								<p>Please find the login&nbsp;details to fill the form:</p>
								<p>&nbsp;</p>
								<p><strong>URl&nbsp;</strong>:&nbsp;{link}</p>
								<p>Login Id : {login_id}</p>
								<p>Password: {password}</p>
								<p>&nbsp;</p>
								<p>Thank you,</p>
								<p>Pro Ace Heating &amp; Air Conditioning Ltd<br />Tel: 604-293-3770<br />&nbsp;<a href="https://www.acecare.ca"><img src="https://www.acecare.ca/wp-content/uploads/2018/08/newacelogooptimized.png" alt="" width="461" height="81" /></a></p>
								<p>&nbsp;</p>
								<p>&nbsp;</p>';
						$msg = str_replace('{first_name}', $this->data['User']['first_name'], $msg);
						$msg = str_replace('{link}', $link, $msg);
		                $msg = str_replace('{last_name}', $this->data['User']['last_name'], $msg);
		                $msg = str_replace('{login_id}','<b>'.$this->data['User']['username'].'</b>', $msg);
		                $msg = str_replace('{password}','<b>'.$this->data['User']['password'].'</b>', $msg);
						
						$this->Common->sendEmailMailgun($this->data['User']['email'],$subject,$msg);
				}

					$db->_execute("insert into ace_rp_users_roles(user_id, role_id) values(".$user_id.",1)");

				}

			}

 			if(!empty($_POST['qa']))
 			{
 				foreach ($_POST['qa'] as $key => $value) {
 					$value['tech_id'] = $user_id;
 					$value['qualification_id'] = $value['id'];
 					$this->TechQualification->save($value);
 				}
 			}
			if ($this->data['rurl'][0])

				$this->redirect($this->data['rurl'][0]);

			else 

				$this->redirect('/commissions/index');

		}

		if($user_id > 0)

		{
			$this->User->id = $user_id;

			$user_details = $this->User->read();

		 if (empty($user_details['User']['end_date']) || $user_details['User']['end_date'] == "1969-12-31")
                $user_details['User']['end_date'] = date('d M Y');
            else
                $user_details['User']['end_date'] = date('d M Y', strtotime($user_details['User']['end_date']));

            if (empty($user_details['User']['start_date']) || $user_details['User']['start_date'] == "1969-12-31")
                $user_details['User']['start_date'] = date('d M Y');
            else
                $user_details['User']['start_date'] = date('d M Y', strtotime($user_details['User']['start_date']));
		}

		
		// $this->Common->printData($user_details);
		//Default user - view only

		$access_method = 'disabled';

		//Ali, Sanaz, Maria Flor and Anthony - full access

		if (
		($this->Common->getLoggedUserID()==44851)
		  ||($this->Common->getLoggedUserID()==58613)
		  ||($this->Common->getLoggedUserID()==52249)
		  ||($this->Common->getLoggedUserID()==68476)
		  ||($this->Common->getLoggedUserID()==231307)
		  )

			$access_method = '';

		//Set variables for the page

		$this->set('access_method',$access_method);

		$this->set('comm_records',$this->getCommissionSettings($user_id));

		$this->set('job_types',$this->Lists->ListTable('ace_rp_order_types','category_id=2 and show_tech_commission = 1'));

		$this->set('comm_roles',$this->Lists->ListTable('ace_rp_commissions_roles'));

		$this->set('comm_types',$this->Lists->ListTable('ace_rp_commissions_types'));

		$this->set('comm_statuses',array(0 => 'Tech', 1=>'Installer'));

		$this->set('user_id', $user_id);

		$this->set('user_details', $user_details);

		$this->set("ismobile", $this->Session->read("ismobile"));

		

		if ($this->data['rurl'][0])

			$this->redirect($this->data['rurl'][0]);

	}	

	

	// Method returns commission percentages for the given user

	// Created: Anthony Chernikov, 07.07.2010

	function getCommissionSettings($user_id)

	{		

		$comm_types = $this->Lists->ListTable('ace_rp_commissions_types');

		

		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		$result = $db->_execute("SELECT * FROM ace_rp_commissions WHERE user_id=".$user_id);

		

		//For the selected technitian we have to apply default first

		if ($user_id)

			$user_commissions = $this->getCommissionSettings(0);

		else

			$user_commissions = array();
	
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC))

		{

			//Normally commission depends on the partner

			if ($row['partner_role_id'])

				$partner = 'w_tech';

			else

				$partner = 'alone';

			//Appliances sales and Fixed rate installer's commissions are also divided by job type

			if (($row['commission_type_id']==3)||($row['commission_type_id']==6))
			{
				$user_commissions[$comm_types[$row['commission_type_id']]][$partner][$row['category_id']] = floatval($row['commission']);
			}
			else{
				$user_commissions[$comm_types[$row['commission_type_id']]][$partner] = floatval($row['commission']);
			}


		}

		// $this->Common->printData($user_commissions);
		return $user_commissions;

	}



	// Commission calculation engine

	function _calculate(&$order, &$comm_settings)

	{
		
		//Set up persons' rows

		$rows_persons = array(); //1,2 - technitians; 3,4 - sources

		$tech_comm_confirm = array();


		// Techs' commission methods

		if (!$order['t1_method']) $order['t1_method']=$order['t1_commission_type'];

		if (!$order['t2_method']) $order['t2_method']=$order['t2_commission_type'];


		// For the job commission: if both technitians were set for this job   

		// and noone was set as a helper - use 'w_tech' percent for each of techs.

		// Otherwise use 'alone' percent.

		if (($order['job_technician1_id'])&&($order['job_technician2_id'])

				&&($order['t2_method']<4)&&($order['t1_method']<4))

			$prc_var='w_tech';

		else

			$prc_var='alone';

		

		$rows_persons[1] = array(

			'tech_num' => 1,

			'sale_class' => 1,

			'id' => $order['job_technician1_id'],

			'commission_type' => $order['t1_method'],

			'src_type' => $prc_var,

			'prc_type' => $prc_var,

			'total_comm' => 0,

			'verified' => ''
			

		);
		$tech_comm_confirm[1] = array(
			'tech_verified' => '',
			'tech_unverified' => ''
		);

		$rows_persons[2] = array(

			'tech_num' => 2,

			'sale_class' => 1,

			'id' => $order['job_technician2_id'],

			'commission_type' => $order['t2_method'],

			'src_type' => $prc_var,

			'prc_type' => $prc_var,

			'total_comm' => 0,

			'verified' => ''

		);
		$tech_comm_confirm[2] = array(
			'tech_verified' => '',
			'tech_unverified' => ''
		);


		// If both sources were set for this job - use 'w_tech' percent for each of them.

		// Otherwise use 'alone' percent for the defined person.

		if (($order['booking_source_id'])&&($order['booking_source2_id']))

			$prc_var='w_tech';

		else

			$prc_var='alone';

				
		// Loki: if method will be 
		$rows_persons[3] = array(

			'tech_num' => 3,

			'sale_class' => 0,

			'id' => $order['booking_source_id'],

			'commission_type' => ($order['t1_method'] == 2) ? 2 :0,

			'src_type' => $prc_var,

			'prc_type' => $prc_var,

			'total_comm' => 0,

			'verified' => ''

		);
		$tech_comm_confirm[3] = array(
			'tech_verified' => '',
			'tech_unverified' => ''
		);

		$rows_persons[4] = array(

			'tech_num' => 4,

			'sale_class' => 0,

			'id' => $order['booking_source2_id'],

			'commission_type' => ($order['t1_method'] == 2) ? 2 :0,

			'src_type' => $prc_var,

			'prc_type' => $prc_var,

			'total_comm' => 0,

			'verified' => ''

		);

		$tech_comm_confirm[4] = array(
			'tech_verified' => '',
			'tech_unverified' => ''
		);

		// Set up order totals for calculation

		$order['total'] = array('0' => 0, '1' => 0);

		$order['job'] = array('0' => 0, '1' => 0);

		$order['parts'] = array('0' => 0, '1' => 0);

		$order['appl'] = array('0' => 0, '1' => 0);

		// $this->Common->printData($rows_persons);

		//Calculate technitians' commissions for the EXTRA SALES (in the current job order)

		//1. Commission for the servises and parts sold is based on a single percent

		//2. Commission for the appliances sold based on different percents.

		//   The applied percent depends on the job type ('order_type_id').

		//3. Technicians will be given this kind of comission only for the 'extra sale'

		//	 items (class=1). Sources - only for the 'extra booking' items (class=0)

		// Loki- Show commission only for the Done jobs and not have estimate paymnet type.
	    // if($order['estimate_sent'] == 1 && $order['order_status'] ==  5) {
		
 		if($order['order_status_id'] ==  5 && $order['payment_method_type'] != 11 ) {
			// Live part_req = 81, local = 70
			if($order['order_type_id'] == 81)
			{
				$query_items =  "select sum(if(i.is_appliance!=1,(oi.price-oi.tech_purchase_price)*oi.quantity-oi.discount+oi.addition+oi.tech-oi.tech_minus,0)) sell_service,

					sum(if(i.is_appliance=1,oi.price*oi.quantity-oi.discount+oi.addition,0)) sell_appl,

					oi.class sale_class

				from ace_rp_order_items oi, ace_rp_items i

			   where i.id=oi.item_id and i.is_appliance!=3 

				   and oi.order_id=" .$order['id'] ."

			   group by oi.class";

			   $query_items = "

				select sum(if(i.iv_type_id != 1,(if(i.iv_category_id = 59,tech_purchase_price, oi.price-oi.price_purchase))*oi.quantity-oi.discount+oi.addition,0)) sell_service,

					sum(if(i.iv_type_id = 1,(if(i.iv_category_id = 59,tech_purchase_price, oi.price))*oi.quantity-oi.discount+oi.addition,0)) sell_appl,

					sum(if(i.iv_category_id = 59,tech_purchase_price,0)) parts_total,
					oi.class sale_class	,
					sum(if(i.iv_category_id = 58,(oi.price)*oi.quantity-oi.discount+oi.addition,0)) labour_install,

					oi.class sale_class

				from ace_rp_order_items oi

				left join ace_iv_items i

				on oi.item_id = i.id

				where oi.order_id=" .$order['id'] ." and oi.item_id not in (1106,1107)

				group by oi.class;";

			} else {

			// $query_items = "select sum(if(i.is_appliance!=1,(oi.price-oi.price_purchase)*oi.quantity-oi.discount+oi.addition+oi.tech-oi.tech_minus,0)) sell_service,

			// 		sum(if(i.is_appliance=1,oi.price*oi.quantity-oi.discount+oi.addition,0)) sell_appl,

			// 		oi.class sale_class

			// 	from ace_rp_order_items oi, ace_rp_items i

			//    where i.id=oi.item_id and i.is_appliance!=3 

			// 	   and oi.order_id=" .$order['id'] ."

			//    group by oi.class";
		// Edited by Maxim Kudryavtsev - exclude membership cards from commisions	

		/*local labour category = 38, live = 37*/
		$query_items = "select sum(if(i.iv_type_id != 1 && i.iv_category_id != 59 , (oi.price-oi.price_purchase)*oi.quantity-oi.discount+oi.addition,0)) sell_service,
			
			sum(if(i.iv_type_id = 1 && i.iv_category_id != 59, (oi.price)*oi.quantity-oi.discount+oi.addition,0)) sell_appl,	
			sum(if(i.iv_category_id = 58,(oi.price)*oi.quantity-oi.discount+oi.addition,0)) labour_install,
			sum(if(i.iv_category_id = 59,tech_purchase_price,0)) parts_total,
			oi.class sale_class	
			from ace_rp_order_items oi left join ace_iv_items i on oi.item_id = i.id 
			where oi.order_id=" .$order['id'] ." 
			group by oi.class;";
		}

		
		// /Edited by Maxim Kudryavtsev - exclude membership cards from commisions



		$db =& ConnectionManager::getDataSource('default');

		$result_items = $db->_execute($query_items);

		while ($row_items = mysql_fetch_array($result_items, MYSQL_ASSOC))

		{

			$order['job'][$row_items['sale_class']]= $row_items['sell_service'];

			$order['appl'][$row_items['sale_class']]= $row_items['sell_appl'];
			
			$order['parts'][$row_items['sale_class']]= $row_items['parts_total'];
			
			$order['install_labour'][$row_items['sale_class']]= $row_items['labour_install'];

			$order['total'][$row_items['sale_class']]=$row_items['sell_service']+$row_items['sell_appl'];

		}		
	
		// $this->Common->printData($order); die();
		$num_of_sources = 1;

		if ($rows_persons[3]['id']&&$rows_persons[4]['id']) $num_of_sources = 2;

		$num_of_techs = 1;

		if ($rows_persons[1]['id']&&$rows_persons[2]['id']) $num_of_techs = 2;


		// $this->Common->printData($rows_persons);

		//Calculate technicians' commissions for doing this job 

		//Applicable only for rows 1 and 2 - technicians, not sources

		for ($x=1;$x<=4;$x++)

		{

			//$rows_persons[$x]['time_comm'] = 0;

			$rows_persons[$x]['redo_penalty'] = 0;

			$rows_persons[$x]['booking_comm'] = 0;

			$rows_persons[$x]['time_comm'] = 0;

			$rows_persons[$x]['driving_comm'] = 0;

			$rows_persons[$x]['helper_ded'] = 0;

			$helper_deduction[$x] = 0;

			

			$src_type = $rows_persons[$x]['src_type'];

			$sale_class = $rows_persons[$x]['sale_class'];

			//Upsales 

			// For the helper 10+10 the percent should be always 10

			if (($rows_persons[$x]['commission_type']==4)

				||($rows_persons[$x]['commission_type']==7)) $prc = 10;

			else

			{

				$prc = $comm_settings[$rows_persons[$x]['id']]['Sales'][$src_type];



				//Sources will be given less percent for extra sales, than technicians.

				if ($sale_class==0)
					//For redo jobs sources are not applicable

					if ($order['order_type_id']==9) $prc = 0;

					else $prc = $comm_settings[$rows_persons[$x]['id']]['Source'][$src_type];

			}
			

			if($x ==1 || $x ==2)
			{
				$rows_persons[$x]['sales_job_comm'] = $order['job'][$sale_class] * ($prc/100);			
			} else {
				if(($rows_persons[1]['id'] != $rows_persons[$x]['id']) && ($rows_persons[2]['id'] != $rows_persons[$x]['id']))
				{
					$rows_persons[$x]['source_job_comm'] = $order['job'][$sale_class] * ($prc/100);	
				}
			}
			$rows_persons[$x]['sales_job_prc'] = $prc;
			

			
			if($x == 1 || $x == 2)
			{
				$prcnt = $comm_settings[$rows_persons[$x]['id']]['Parts'][$src_type];
				$rows_persons[$x]['part_comm'] = ($order['parts'][0] + $order['parts'][1]) * ($prcnt/100);			
			}



			// For the helper 10+10 the percent for appliences should be always 2

			if (($rows_persons[$x]['commission_type']==4)

				||($rows_persons[$x]['commission_type']==7)) $prc = 2;

			else

				$prc = $comm_settings[$rows_persons[$x]['id']]['Appliance'][$src_type][$order['order_type_id']];

			

			$rows_persons[$x]['sales_appl_comm'] = $order['appl'][$sale_class] * ($prc/100);			

			$rows_persons[$x]['sales_appl_prc'] = $prc;



			// Here we have different cases for each of commission calculation methods

			// 1. Tech On Commission 

			if (($order['order_type_id']==9)&&($x<=2))

			{				

				//if ($rows_persons[$x]['id']) $rows_persons[$x]['booking_comm'] = 20/$num_of_techs;

				if ($rows_persons[$x]['id']) $rows_persons[$x]['booking_comm'] = $comm_settings[$rows_persons[$x]['id']]['Redo'][$rows_persons[$x]['prc_type']];

				

			}

			else

			{

				if ($rows_persons[$x]['commission_type']==1)

				{

					$prc = $comm_settings[$rows_persons[$x]['id']]['Booking'][$rows_persons[$x]['prc_type']];

					$rows_persons[$x]['booking_comm'] = $order['total'][0] * ($prc/100);

					//$rows_persons[$x]['sales_job_comm'] = $order['total'][1] * ($prc/100);

				}

				// 2. Fixed rate Installer

				elseif ($rows_persons[$x]['commission_type']==2) 

				{
					// $prc = $comm_settings[$rows_persons[$x]['id']]['Fixed'][$rows_persons[$x]['prc_type']][$order['order_type_id']];

					// // $rows_persons[$x]['booking_comm'] = $prc;
					// $rows_persons[$x]['booking_comm'] = ($order['install_labour'][0] + $order['install_labour'][1]) * ($prc/100);
					if($x == 1 || $x == 2){
						//print_r($comm_settings[$rows_persons[$x]['id']]['Fixed'][$rows_persons[$x]['prc_type']]);
						$prc = $comm_settings[$rows_persons[$x]['id']]['Fixed'][$rows_persons[$x]['prc_type']][$order['order_type_id']];
						$rows_persons[$x]['labour_comm'] = ($order['install_labour'][0] + $order['install_labour'][1]) * ($prc/100);
						
					} else {
						$prc = $comm_settings[$rows_persons[$x]['id']]['Appliance'][$rows_persons[$x]['prc_type']][$order['order_type_id']];
						$rows_persons[$x]['sales_appl_comm'] = $order['install_labour'][0]  * ($prc/100);
						$rows_persons[$x]['sales_job_comm'] = 0;
					}
					$rows_persons[$x]['sales_job_comm'] = 0;
					$order['total'][0] =  $order['install_labour'][0];
				}

				// 3. Tech paid per job

				elseif ($rows_persons[$x]['commission_type']==3)

				{

					$rows_persons[$x]['booking_comm'] = $comm_settings[$rows_persons[$x]['id']]['PerJob'][$rows_persons[$x]['prc_type']];

					$rows_persons[$x]['driving_comm'] = $comm_settings[$rows_persons[$x]['id']]['Driving'][$rows_persons[$x]['prc_type']];

				}

				// 4. Helper $10+10%

				elseif ($rows_persons[$x]['commission_type']==4)

				{

					$rows_persons[$x]['booking_comm'] = 10;

					$helper_deduction[$x] = 0-($rows_persons[$x]['booking_comm']

																	 +$rows_persons[$x]['sales_job_comm'] + $rows_persons[$x]['sales_appl_comm'])/2;

				}

				// 5. Hourly paid Helper, 50% deduction

				elseif ($rows_persons[$x]['commission_type']==5)

				{

					$prc = $comm_settings[$rows_persons[$x]['id']]['Time'][$rows_persons[$x]['prc_type']];

					$rows_persons[$x]['booking_comm'] = $prc*$order['job_time_payable'];

					$helper_deduction[$x] = 0-$rows_persons[$x]['booking_comm']/2;

				}

				// 6. Hourly paid Helper, 100% deduction

				elseif ($rows_persons[$x]['commission_type']==6)

				{

					$prc = $comm_settings[$rows_persons[$x]['id']]['Time'][$rows_persons[$x]['prc_type']];

					$rows_persons[$x]['booking_comm'] = $prc*$order['job_time_payable'];

					$helper_deduction[$x] = 0-$rows_persons[$x]['driving_comm'];

				}

				// 7. Helper $20+10%

				elseif ($rows_persons[$x]['commission_type']==7)

				{

					$rows_persons[$x]['booking_comm'] = 20;

					$helper_deduction[$x] = 0-($rows_persons[$x]['booking_comm']

											+$rows_persons[$x]['sales_job_comm'] + $rows_persons[$x]['sales_appl_comm'])/2;

				}

				// 8. Hourly paid Tech

				elseif ($rows_persons[$x]['commission_type']==8)

				{

					$prc = $comm_settings[$rows_persons[$x]['id']]['Time'][$rows_persons[$x]['prc_type']];

					$rows_persons[$x]['time_comm'] = $prc*$order['job_time_payable'];

					$rows_persons[$x]['booking_comm'] = $rows_persons[$x]['time_comm'];

				}

			}


			$rows_persons[$x]['total_comm'] +=$rows_persons[$x]['booking_comm']

											+ $rows_persons[$x]['driving_comm']

											+ $rows_persons[$x]['sales_job_comm']

											+ $rows_persons[$x]['sales_appl_comm']

											+ $rows_persons[$x]['redo_penalty'] + $rows_persons[$x]['part_comm']+$rows_persons[$x]['source_job_comm'] + $rows_persons[$x]['labour_comm'];
										}
		
			

		// Apply the redo penalty for the sources if the job type is 'redo'

		if ($order['order_type_id']==9)

		{

			//$rows_persons[3]['redo_penalty'] = 0-$comm_settings[$rows_persons[3]['id']]['Booking'][$rows_persons[3]['prc_type']]*$order['total'][0]/100;

			if ($rows_persons[3]['id'])

			{

				//$rows_persons[3]['redo_penalty'] = 0-20/$num_of_sources;

				$rows_persons[3]['redo_penalty'] = $comm_settings[$rows_persons[3]['id']]['RedoPenalty'][$rows_persons[3]['prc_type']];

				//$rows_persons[3]['redo_penalty'] = -500;

				$rows_persons[3]['total_comm'] += $rows_persons[3]['redo_penalty'];

			}

			//$rows_persons[4]['redo_penalty'] = $comm_settings[$rows_persons[4]['id']]['Booking'][$rows_persons[4]['prc_type']]*$order['total'][0]/100;

			if ($rows_persons[4]['id'])

			{				

				//$rows_persons[4]['redo_penalty'] = 0-20/$num_of_sources;

				$rows_persons[4]['redo_penalty'] = $comm_settings[$rows_persons[4]['id']]['RedoPenalty'][$rows_persons[4]['prc_type']];				

				$rows_persons[4]['total_comm'] += $rows_persons[4]['redo_penalty'];

			}

		}

		

		//Apply helper deductions 

		if ($rows_persons[1]['commission_type']>=4)

		{

			$rows_persons[2]['helper_ded'] = $helper_deduction[1];

			$rows_persons[2]['total_comm'] += $rows_persons[2]['helper_ded'];

		}

		elseif ($rows_persons[2]['commission_type']>=4)

		{

			$rows_persons[1]['helper_ded'] = $helper_deduction[2];

			$rows_persons[1]['total_comm'] += $rows_persons[1]['helper_ded'];

		}



		// If the job has been verified, we have to override all the calculated values.

		// May be we should've done this before, but in fact it doesn't really matter.

		$rows_persons[$x]['adjustment'] = 0;

		$query_ver = "select * from ace_rp_orders_comm where order_id=".$order['id'];

		$result_ver = $db->_execute($query_ver);

		while ($row_ver= mysql_fetch_array($result_ver, MYSQL_ASSOC))

		{

			 if($rows_persons[$row_ver['tech_num']]['commission_type'] != 2)
            {
                $rows_persons[$row_ver['tech_num']]['booking_comm']=$row_ver['booking_comm'];

                $rows_persons[$row_ver['tech_num']]['sales_job_comm']=$row_ver['sales_job_comm'];

                $rows_persons[$row_ver['tech_num']]['sales_appl_comm']=$row_ver['sales_appl_comm'];

                // $rows_persons[$row_ver['tech_num']]['driving_comm']=$row_ver['driving_comm'];

                // $rows_persons[$row_ver['tech_num']]['redo_penalty']=$row_ver['redo_penalty'];

                // $rows_persons[$row_ver['tech_num']]['helper_ded']=$row_ver['helper_ded'];

                // $rows_persons[$row_ver['tech_num']]['total_comm']=$row_ver['total_comm'];

                // $rows_persons[$row_ver['tech_num']]['adjustment']=$row_ver['adjustment'];
            }
            	$rows_persons[$row_ver['tech_num']]['driving_comm']=$row_ver['driving_comm'];

                $rows_persons[$row_ver['tech_num']]['redo_penalty']=$row_ver['redo_penalty'];

                $rows_persons[$row_ver['tech_num']]['helper_ded']=$row_ver['helper_ded'];

                $rows_persons[$row_ver['tech_num']]['total_comm']= $row_ver['driving_comm'] + $row_ver['redo_penalty'] + $row_ver['helper_ded']+$row_ver['adjustment']+$rows_persons[$row_ver['tech_num']]['booking_comm']+ $rows_persons[$row_ver['tech_num']]['driving_comm']	+ $rows_persons[$row_ver['tech_num']]['sales_job_comm'] + $rows_persons[$row_ver['tech_num']]['sales_appl_comm']
					+ $rows_persons[$row_ver['tech_num']]['redo_penalty'] + $rows_persons[$row_ver['tech_num']]['part_comm']+$rows_persons[$row_ver['tech_num']]['source_job_comm'] + $rows_persons[$row_ver['tech_num']]['labour_comm'];

                $rows_persons[$row_ver['tech_num']]['adjustment']= $row_ver['adjustment'];
            	$rows_persons[$row_ver['tech_num']]['verified'] = 'checked';
			// if(!empty($row_ver['tech_confirm']) || $row_ver['tech_confirm'] != '')
			// {
			// 	if($row_ver['tech_confirm'] != NULL && $row_ver['tech_confirm'] == 1)
			// 	{
			// 		$rows_persons[$row_ver['tech_num']]['tech_verified'] = 'checked';
			// 	}else if( $row_ver['tech_confirm'] != NULL && $row_ver['tech_confirm'] == 0)
			// 	{
			// 		$rows_persons[$row_ver['tech_num']]['tech_unverified'] = 'checked';
			// 	}	
			// }
		}

		// $this->Common->printData($rows_persons);

		$query_comm = "select * from ace_rp_tech_comm_confirm where order_id=".$order['id'];

		$result_comm = $db->_execute($query_comm);
		while ($row_comm= mysql_fetch_array($result_comm, MYSQL_ASSOC))
		{
			if($row_comm['tech_confirm'] != NULL && $row_comm['tech_confirm'] == 1)
				{
					$tech_comm_confirm[$row_comm['tech_num']]['tech_verified'] = 'checked';
				}else if( $row_comm['tech_confirm'] != NULL && $row_comm['tech_confirm'] == 0)
				{
					$tech_comm_confirm[$row_comm['tech_num']]['tech_unverified'] = 'checked';
				}
		}
	}
		return array($rows_persons, $tech_comm_confirm);
	}



	// Commission calculation page

	function calculateCommissions()

	{
		if(empty($_SESSION['user']))
		{
			$redirectUrl = $_SERVER['REQUEST_URI'];
			$this->Session->write('redirect_url', $redirectUrl);
			$this->redirect("/login/index");
		}
		$this->layout="list";

		if (($_SESSION['user']['role_id'] == 1) || ($_SESSION['user']['role_id'] == 4) || (($_SESSION['user']['role_id'] == 6) && ($_SESSION['user']['active_commission'] != 0))) 

		{ // TECHNICIAN=1, ACCOuntant=4, administrator=6

			

			//CUSTOM PAGING

			//*************s

			$itemsCount = 20;

			$currentPage = 0;

			$previousPage = 0;

			$nextPage = 1;

			$fromTech = isset($_GET['fromTech']) ? $_GET['fromTech'] : 0 ;

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

	

			if ($this->params['url']['ftodate'] != '')

				$this->params['url']['ftodate'] = date("Y-m-d", strtotime($this->params['url']['ftodate']));

	

			//Pick today's date if no date
			if($_SESSION['user']['role_id'] == 6) {
				$fdate = ($this->params['url']['ffromdate'] != '' ? $this->params['url']['ffromdate']: date("Y-m-d", strtotime('-1 days')) );
			} else {
				$fdate = ($this->params['url']['ffromdate'] != '' ? $this->params['url']['ffromdate']: date("Y-m-d"));
			}
			
			if($this->Common->getLoggedUserRoleID() == 1)
			{
				$today = date("Y-m-d");
				$fromDate = $_GET['ffromdate'];
				if($fromDate != ''){
					$fromDate = date("Y-m-d",strtotime($_GET['ffromdate']));
				}
				// echo "<pre>";
				// print_r($_GET);
				// print_r($fromDate); die;
				if($fromDate > $today)
				{
					echo "<script>
					alert('You can not see future jobs.');
					window.history.back();
					</script>";
					exit();
				}
			}

			$tdate = ($this->params['url']['ftodate'] != '' ? $this->params['url']['ftodate']: date("Y-m-d") );

			$cur_ref = ($this->params['url']['cur_ref'] != '' ? $this->params['url']['cur_ref'] : '');

			$single = ($this->params['url']['single'] != '' ? $this->params['url']['single'] : 0);

			$techid = $this->params['url']['ftechid'];

			$job_option = $_REQUEST['job_option'];


			if (!$job_option) $job_option = 1;


			//If user is Technician - role id=1

			//then show only orders that belongs to him

			if (($_SESSION['user']['role_id'] == 1) ) { // TECHNICIAN=1

				//show data only for current technician

				$techid = $this->Common->getLoggedUserID();

			}

						

			//The list of all technicians in the system. We'll need it anyway

			$allTechnicians = $this->Lists->Technicians();

			

			//Addintional lists

			$allSources = $this->Lists->BookingSources();

			$allJobStatuses = $this->Lists->ListTable('ace_rp_order_statuses');

			

			//This array is being used for the commission settings.

			$comm_settings = array();

			

			//Fill out commissions settings for the technicians.

			//If we've logged in as a technician - we just need our own settings

			foreach ($allTechnicians as $cur_id => $cur_value)

				$comm_settings[$cur_id] = $this->getCommissionSettings($cur_id);

			

			//CONDITIONS

			//**********	

			$db =& ConnectionManager::getDataSource('default');

			if(($fdate != '')&&(!$cur_ref))

				if ($job_option==1)

					$sqlConditions .= " AND order_status_id IN (1,5) AND a.job_date = '".$this->Common->getMysqlDate($fdate)."'"; 			

				elseif ($job_option==3)

					$sqlConditions .= " AND order_status_id=3 AND a.job_date = '".$this->Common->getMysqlDate($fdate)."'"; 			

				elseif ($job_option==2)

					$sqlConditions .= " AND order_status_id IN (1,5) AND a.booking_date = '".$this->Common->getMysqlDate($fdate)."'"; 			
			

			if(($techid > 0)&&(!$cur_ref))

				if (($job_option==1)||($job_option==3))

					$sqlConditions .= " AND (a.booking_source_id=".$techid." OR a.booking_source2_id=".$techid." OR a.job_technician1_id=".$techid." OR a.job_technician2_id=".$techid.") ";

				elseif ($job_option==2)

					$sqlConditions .= " AND (a.booking_source_id=".$techid." OR a.booking_source2_id=".$techid.") ";

			

			if($cur_ref)

				$sqlConditions .= " AND a.order_number = '".$cur_ref."'"; 

			if($fromTech == 1 && $_SESSION['user']['role_id'] == 1){
				$sqlConditions .= " AND a.payment_method_type != 11"; 
			}
			//The route visibility was added on 2011-04-17. All jobs before this date are excluded.

			if($this->Common->getMysqlDate($fdate) > $this->Common->getMysqlDate("2011-04-17") && $this->Common->getLoggedUserRoleID()==1) {				

				$routeVisibilityConstraint =  "

				AND (

					rv.route_id IS NOT NULL 

					OR a.job_technician1_id = a.booking_source_id

					OR a.job_technician2_id = a.booking_source_id

					OR $techid = a.booking_source_id

					OR $techid = a.booking_source2_id

					OR $techid = a.job_technician1_id

					OR $techid = a.job_technician2_id

					)

				";

				

				$routeVisibilityConstraint =  "

				AND (

					a.job_technician1_id = a.booking_source_id

					OR a.job_technician2_id = a.booking_source_id

					OR $techid = a.booking_source_id

					OR $techid = a.booking_source2_id

					OR $techid = a.job_technician1_id

					OR $techid = a.job_technician2_id

					)

				";

				

			} else {

				$routeVisibilityConstraint = "";	

			}
			if (isset($this->params['url']['orderId']))
			{
				$orderId = $this->params['url']['orderId'];
				$sqlConditions .= " AND a.id='".$orderId."'";
			}
			

			$orders = array();

			$query = "

				SELECT  a.id, a.job_date, a.order_number, a.order_type_id, a.order_status_id,
						at.name as order_type, at.category_id as job_type_category,
						at.show_commission,a.admin_commission_reply,
						a.fact_job_beg, a.fact_job_end,
						a.booking_source_id,
						a.booking_source2_id,
						a.job_technician1_id,
						a.job_technician2_id,
						a.t1_method,
						a.t2_method,
						a.payment_image,
						a.photo_1,
						a.photo_2,
						a.tech_notes,
						a.admin_notes,
						a.estimate_sent,
					   	a.order_type_id,
					   	a.payment_method_type,
						t1.first_name as tech1_first_name,
						t1.last_name as tech1_last_name,
						t1.commission_type as t1_commission_type,
						t2.first_name as tech2_first_name,
						t2.last_name as tech2_last_name,
						t2.commission_type as t2_commission_type,
						pm.name as payment_type,
						pm.show_picture

				FROM `ace_rp_orders` as a

				INNER JOIN	`ace_rp_order_types` as at ON (a.order_type_id = at.id)

				LEFT JOIN	`ace_rp_users` as t1 on ( a.job_technician1_id = t1.id )

				LEFT JOIN	`ace_rp_users` as t2 on ( a.job_technician2_id = t2.id )
				LEFT JOIN ace_rp_route_visibility rv ON a.job_truck = rv.route_id AND a.job_date = rv.job_date
				LEFT JOIN ace_rp_payment_methods pm ON a.payment_method_type = pm.id 
				WHERE 1=1
				AND tech_visible = 1
				$routeVisibilityConstraint
				$sqlConditions
				ORDER BY a.job_date desc 
				$sqlPaging";
				
			$result = $db->_execute($query);
			$tech_comm_conf = array();
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))

			{

				//Transfer all fields from the query result

				foreach ($row as $k => $v)

				$orders[$row['id']][$k] = $v;

				if(!empty($cur_ref) && $techid == -1)
				{
					$techid = $row['job_technician1_id'];
				}

				//Calculate/set special fields

				$orders[$row['id']]['job_time_payable'] =

					(strtotime($row['job_date'].' '.$row['fact_job_end'])-

					strtotime($row['job_date'].' '.$row['fact_job_beg']))/3600;

				
				$orders[$row['id']]['orderNumber_image_path'] = $row['payment_image'];
				$orders[$row['id']]['order_purchase_image1'] = $this->getPhotoPath($row['photo_1']);
				$orders[$row['id']]['order_purchase_image2'] = $this->getPhotoPath($row['photo_2']);	
				$orders[$row['id']]['tech_confirm_total'] = $row['tech_confirm_total'];	
				$orders[$row['id']]['tech_not_confirm_total'] = $row['tech_not_confirm_total'];		
				$orders[$row['id']]['tech_notes'] = $row['tech_notes'];	
				$orders[$row['id']]['admin_notes'] = $row['admin_notes'];			
				$orders[$row['id']]['estimate_sent'] = $row['estimate_sent'];	
				$orders[$row['id']]['admin_commission_reply'] = $row['admin_commission_reply'];		
				$orders[$row['id']]['order_status'] = $allJobStatuses[$row['order_status_id']];

				$orders[$row['id']]['order_type'] = $row['order_type'];
				$orders[$row['id']]['order_type_id'] = $row['order_type_id'];

							

				$orders[$row['id']]['source_name'] = $allTechnicians[$row['booking_source_id']];

				$orders[$row['id']]['source2_name'] = $allTechnicians[$row['booking_source2_id']];

				$orders[$row['id']]['tech1_name'] = $row['tech1_first_name'].' '.$row['tech1_last_name'];

				$orders[$row['id']]['tech2_name'] = $row['tech2_first_name'].' '.$row['tech2_last_name'];
				$orders[$row['id']]['tech1_id'] = $row['job_technician1_id'];

				$orders[$row['id']]['tech2_id'] = $row['job_technician2_id'];


				//COMMISSIONS CALCULATION


				$all_data = $this->_calculate(&$orders[$row['id']], &$comm_settings);

				$rows_persons = $all_data[0];
				$tech_comm_conf[$row['id']] = $all_data[1];
				$getInvoices = $this->getOrderInvoices($row['id']);
				$orders[$row['id']]['comm'] = $rows_persons;
				$orders[$row['id']]['invoices'] = $getInvoices;

			}	
			$adminCommonNotes = '';
			if(!empty($techid))
			{
				$query = "SELECT admin_common_notes from ace_rp_tech_done_comm where comm_date='".$fdate."' AND tech_id=".$techid;
				$result = $db->_execute($query);
				$row = mysql_fetch_array($result, MYSQL_ASSOC);
				$adminCommonNotes = $row['admin_common_notes'];
			}
			$this->set('adminCommonNotes', $adminCommonNotes);
			//SET PAGE OPTIONS			

			//$techid = 2; $this->set("loggedUserIsTech",1);

			if (($_SESSION['user']['role_id'] == 1) )

			{

				$this->set("loggedUserIsTech",1);

			}
			$query = "SELECT email FROM ace_rp_commission_email where id=1";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$defaultEmail = $row['email'];
			}	

			$this->set("defaultEmail", $defaultEmail);
			$this->set("tech_comm_conf",$tech_comm_conf);
			$this->set("previousPage",$previousPage);

			$this->set("nextPage",$nextPage);

			$this->set("orders", $orders);

			$this->set("techid", $techid);

			$this->set("cur_ref", $cur_ref);

			$this->set('allTechnician', $allTechnicians);

			$this->set('comm_roles',$this->Lists->ListTable('ace_rp_commissions_roles'));



			$this->set("comm_oper", $comm_oper);

			$this->set("selected_job", $selected_job);

			$this->set("job_option", $job_option);

			$this->set("selected_commission_type", $selected_commission_type);

			$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));

			$this->set('next_tdate', date("d M Y", strtotime($fdate) + 24*60*60));

			$this->set("routeVisibilityConstraint", $routeVisibilityConstraint);

			$this->set("query", $query);

			$this->set("ismobile", $this->Session->read("ismobile"));

			

			if($fdate!='')

				$this->set('fdate', date("d M Y", strtotime($fdate)));

			if($tdate!='')

				$this->set('tdate', date("d M Y", strtotime($tdate)));

				

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

	function allCommissions()

	{

		$this->layout="list";

		if (($_SESSION['user']['role_id'] == 1) || ($_SESSION['user']['role_id'] == 4) || ($_SESSION['user']['role_id'] == 6)) 

		{ // TECHNICIAN=1, ACCOuntant=4, administrator=6

			

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

	

			if ($this->params['url']['ftodate'] != '')

				$this->params['url']['ftodate'] = date("Y-m-d", strtotime($this->params['url']['ftodate']));

	

			//Pick today's date if no date

			$fdate = ($this->params['url']['ffromdate'] != '' ? $this->params['url']['ffromdate']: date("Y-m-d") );

			$tdate = ($this->params['url']['ftodate'] != '' ? $this->params['url']['ftodate']: date("Y-m-d") );

			$cur_ref = ($this->params['url']['cur_ref'] != '' ? $this->params['url']['cur_ref'] : '');

			$single = ($this->params['url']['single'] != '' ? $this->params['url']['single'] : 0);

			$techid = $this->params['url']['ftechid'];

			$job_option = $_REQUEST['job_option'];

			if (!$job_option) $job_option = 1;



			//If user is Technician - role id=1

			//then show only orders that belongs to him

			if (($_SESSION['user']['role_id'] == 1) ) { // TECHNICIAN=1

				//show data only for current technician

				$techid = $this->Common->getLoggedUserID();

			}

						

			//The list of all technicians in the system. We'll need it anyway

			$allTechnicians = $this->Lists->Technicians();

			

			//Addintional lists

			$allSources = $this->Lists->BookingSources();

			$allJobStatuses = $this->Lists->ListTable('ace_rp_order_statuses');

			

			//This array is being used for the commission settings.

			$comm_settings = array();

			

			//Fill out commissions settings for the technicians.

			//If we've logged in as a technician - we just need our own settings

			foreach ($allTechnicians as $cur_id => $cur_value)

				$comm_settings[$cur_id] = $this->getCommissionSettings($cur_id);

			

			//CONDITIONS

			//**********	

			$db =& ConnectionManager::getDataSource('default');

			if(($fdate != '')&&(!$cur_ref))

				if ($job_option==1)

					$sqlConditions .= " AND order_status_id IN (1,5) AND a.job_date = '".$this->Common->getMysqlDate($fdate)."'"; 			

				elseif ($job_option==3)

					$sqlConditions .= " AND order_status_id=3 AND a.job_date = '".$this->Common->getMysqlDate($fdate)."'"; 			

				elseif ($job_option==2)

					$sqlConditions .= " AND order_status_id IN (1,5) AND a.booking_date = '".$this->Common->getMysqlDate($fdate)."'"; 			

			

			if(($techid > 0)&&(!$cur_ref))

				if (($job_option==1)||($job_option==3))

					$sqlConditions .= " AND (a.booking_source_id=".$techid." OR a.booking_source2_id=".$techid." OR a.job_technician1_id=".$techid." OR a.job_technician2_id=".$techid.") ";

				elseif ($job_option==2)

					$sqlConditions .= " AND (a.booking_source_id=".$techid." OR a.booking_source2_id=".$techid.") ";

			

			if($cur_ref)

				$sqlConditions .= " AND a.order_number = '".$cur_ref."'"; 



			//The route visibility was added on 2011-04-17. All jobs before this date are excluded.

			if($this->Common->getMysqlDate($fdate) > $this->Common->getMysqlDate("2011-04-17") && $this->Common->getLoggedUserRoleID()==1) {				

				$routeVisibilityConstraint =  "

				AND (

					rv.route_id IS NOT NULL 

					OR a.job_technician1_id = a.booking_source_id

					OR a.job_technician2_id = a.booking_source_id

					OR $techid = a.booking_source_id

					OR $techid = a.booking_source2_id

					)

				";

			} else {

				$routeVisibilityConstraint = "";	

			}



			$orders = array();

			$query = "

				SELECT  a.id, a.job_date, a.order_number, a.order_type_id, a.order_status_id,

						at.name as order_type, at.category_id as job_type_category,

					   

						a.fact_job_beg, a.fact_job_end,

						

						a.booking_source_id,

						a.booking_source2_id,

						a.job_technician1_id,

						a.job_technician2_id,

						a.t1_method,

						a.t2_method,

					   

						t1.first_name as tech1_first_name,

						t1.last_name as tech1_last_name,

						t1.commission_type as t1_commission_type,

					   

						t2.first_name as tech2_first_name,

						t2.last_name as tech2_last_name,

						t2.commission_type as t2_commission_type



				FROM 				`ace_rp_orders` as a

				INNER JOIN	`ace_rp_order_types` as at ON (a.order_type_id = at.id)

				LEFT JOIN		`ace_rp_users` as t1 on ( a.job_technician1_id = t1.id )

				LEFT JOIN		`ace_rp_users` as t2 on ( a.job_technician2_id = t2.id )

				LEFT JOIN ace_rp_route_visibility rv ON a.job_truck = rv.route_id AND a.job_date = rv.job_date

				WHERE 1=1

				$routeVisibilityConstraint

				$sqlConditions

				ORDER BY a.job_date desc 

				$sqlPaging

				";

	

			$result = $db->_execute($query);

			while($row = mysql_fetch_array($result, MYSQL_ASSOC))

			{

				//Transfer all fields from the query result

				foreach ($row as $k => $v)

					$orders[$row['id']][$k] = $v;

				

				//Calculate/set special fields

				$orders[$row['id']]['job_time_payable'] =

					(strtotime($row['job_date'].' '.$row['fact_job_end'])-

					strtotime($row['job_date'].' '.$row['fact_job_beg']))/3600;

				

				$orders[$row['id']]['order_status'] = $allJobStatuses[$row['order_status_id']];

				$orders[$row['id']]['order_type'] = $row['order_type'];

							

				$orders[$row['id']]['source_name'] = $allTechnicians[$row['booking_source_id']];

				$orders[$row['id']]['source2_name'] = $allTechnicians[$row['booking_source2_id']];

				$orders[$row['id']]['tech1_name'] = $row['tech1_first_name'].' '.$row['tech1_last_name'];

				$orders[$row['id']]['tech2_name'] = $row['tech2_first_name'].' '.$row['tech2_last_name'];



				//COMMISSIONS CALCULATION

				$rows_persons = $this->_calculate(&$orders[$row['id']], &$comm_settings);

				$orders[$row['id']]['comm'] = $rows_persons;

			}				



			//SET PAGE OPTIONS			

			//$techid = 2; $this->set("loggedUserIsTech",1);

			if (($_SESSION['user']['role_id'] == 1) )

			{

				$this->set("loggedUserIsTech",1);

			}

			

			$this->set("previousPage",$previousPage);

			$this->set("nextPage",$nextPage);

			$this->set("orders", $orders);

			$this->set("techid", $techid);

			$this->set("cur_ref", $cur_ref);

			$this->set('allTechnician', $allTechnicians);

			$this->set('comm_roles',$this->Lists->ListTable('ace_rp_commissions_roles'));



			$this->set("comm_oper", $comm_oper);

			$this->set("selected_job", $selected_job);

			$this->set("job_option", $job_option);

			$this->set("selected_commission_type", $selected_commission_type);

			$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));

			$this->set('next_tdate', date("d M Y", strtotime($fdate) + 24*60*60));

			$this->set("routeVisibilityConstraint", $routeVisibilityConstraint);

			$this->set("query", $query);

			$this->set("ismobile", $this->Session->read("ismobile"));

			

			if($fdate!='')

				$this->set('fdate', date("d M Y", strtotime($fdate)));

			if($tdate!='')

				$this->set('tdate', date("d M Y", strtotime($tdate)));

				

		}

	}



	// Commission summary for one tech page

	function techSummary()

	{
		$this->layout="list";
		$db =& ConnectionManager::getDataSource('default');

		// echo "<pre>";
		// print_r($this->params['url']); die;

		if (($_SESSION['user']['role_id'] == 1) || ($_SESSION['user']['role_id'] == 4) || ($_SESSION['user']['role_id'] == 6)) 

		{ // TECHNICIAN=1, ACCOuntant=4, administrator=6

			$job_option = $_REQUEST['job_option'];

			if (!$job_option) $job_option = 1;

			$pay_period = isset($this->params['url']['pay_period_id']) ? $this->params['url']['pay_period_id'] : 0;
			
			$check_pay = isset($this->params['url']['check_pay']) ? $this->params['url']['check_pay'] : 0;


			//**********

			//CONDITIONS

			//Convert date from date picker to SQL format

		if($check_pay == 1){

				if($pay_period > 0)
				{
					$query = "select * from ace_rp_pay_periods where id=".$pay_period;

					$result = $db->_execute($query);

					$row = mysql_fetch_array($result, MYSQL_ASSOC);

					$fdate = date("Y-m-d", strtotime($row['start_date']));
					$tdate = date("Y-m-d", strtotime($row['end_date']));
				} else {
					$fdate = date("Y-m-d");
					$tdate = date("Y-m-d");
				}

		} else {
			if ($this->params['url']['ffromdate'] != '')

				$fdate = date("Y-m-d", strtotime($this->params['url']['ffromdate']));

			else

				$fdate = date("Y-m-d");

	

			if ($this->params['url']['ftodate'] != '')

				$tdate = date("Y-m-d", strtotime($this->params['url']['ftodate']));

			else

				$tdate = date("Y-m-d");
		}
			
	

			//Pick today's date if no date

			$joboption = ($this->params['url']['fjoboption'] != '' ? $this->params['url']['fjoboption'] : '1');

			$cur_ref = ($this->params['url']['cur_ref'] != '' ? $this->params['url']['cur_ref'] : '');

			$techid = $this->params['url']['ftechid'];



			//If user is Technician - role id=1

			//then show only orders that belongs to him

			if (($_SESSION['user']['role_id'] == 1) ) { // TECHNICIAN=1

				//show data only for current technician

				$techid = $this->Common->getLoggedUserID();

			}

			$this->User->id = $techid;

			$tech_details = $this->User->read();	

			//The list of all technicians in the system. We'll need it anyway

			$allTechnicians = $this->Lists->Technicians();

			

			//Addintional lists

			$allSources = $this->Lists->BookingSources();

			$allJobStatuses = $this->Lists->ListTable('ace_rp_order_statuses');

			

			//This array is being used for the commission settings.

			$comm_settings = array();

			

			//Fill out commissions settings for the technicians.

			//If we've logged in as a technician - we just need our own settings

			foreach ($allTechnicians as $cur_id => $cur_value)

				$comm_settings[$cur_id] = $this->getCommissionSettings($cur_id);

			

			//CONDITIONS

			//**********	

			

			if ($job_option==1)

			{

				if(($fdate != '')&&(!$cur_ref))

					$sqlConditions .= " AND a.job_date >= '".$this->Common->getMysqlDate($fdate)."'"; 

				if(($tdate != '')&&(!$cur_ref))

					$sqlConditions .= " AND a.job_date <= '".$this->Common->getMysqlDate($tdate)."'";

			}

			elseif ($job_option==2)

			{

				if(($fdate != '')&&(!$cur_ref))

					$sqlConditions .= " AND a.booking_date >= '".$this->Common->getMysqlDate($fdate)."'"; 

				if(($tdate != '')&&(!$cur_ref))

					$sqlConditions .= " AND a.booking_date <= '".$this->Common->getMysqlDate($tdate)."'";

			}

			

			if (($job_option==1)&&($techid))

				$sqlConditions .= " AND (a.booking_source_id=".$techid." OR a.booking_source2_id=".$techid." OR a.job_technician1_id=".$techid." OR a.job_technician2_id=".$techid.") ";

			elseif (($job_option==2)&&($techid))

				$sqlConditions .= " AND (a.booking_source_id=".$techid." OR a.booking_source2_id=".$techid.") ";

				$sqlConditions .= " AND a.payment_method_type != 110000000";

			$orders = array();

			$query = "

				SELECT a.id, a.job_date, a.order_number, a.order_type_id, a.order_status_id,

							 at.name as order_type, at.category_id as job_type_category,							

							 a.fact_job_beg, a.fact_job_end,

							 

							 a.booking_source_id,

							 a.booking_source2_id,

							 a.job_technician1_id,

							 a.job_technician2_id,

							 a.t1_method,

							 a.t2_method,
							 
							 a.payment_method_type,
							 
							 a.payment_status,

							

							 t1.first_name as tech1_first_name,

							 t1.last_name as tech1_last_name,

							 t1.commission_type as t1_commission_type,

							

							 t2.first_name as tech2_first_name,

							 t2.last_name as tech2_last_name,

							 t2.commission_type as t2_commission_type

	

				FROM 				`ace_rp_orders` as a

				INNER JOIN	`ace_rp_order_types` as at ON (a.order_type_id = at.id)

				LEFT JOIN		`ace_rp_users` as t1 on ( a.job_technician1_id = t1.id )

				LEFT JOIN		`ace_rp_users` as t2 on ( a.job_technician2_id = t2.id )

				WHERE 	order_status_id IN (1,5) ".$sqlConditions." order by a.job_date desc";

	

			$summary = array();

			$result = $db->_execute($query);

			while($row = mysql_fetch_array($result, MYSQL_ASSOC))

			{
				if($row['payment_method_type']==11 && $row['payment_status']!=1 ){
					continue;
				}

				//Transfer all the fields from the query result

				$order = array();

				foreach ($row as $k => $v)

					$order[$k] = $v;

				

				//Calculate/set special fields

				$order['job_time_payable'] =

					(strtotime($row['job_date'].' '.$row['fact_job_end'])-

					strtotime($row['job_date'].' '.$row['fact_job_beg']))/3600;

				

				$order['order_status'] = $allJobStatuses[$row['order_status_id']];

				$order['order_type'] = $row['order_type'];

							

				$order['source_name'] = $allTechnicians[$row['booking_source_id']];

				$order['source2_name'] = $allTechnicians[$row['booking_source2_id']];

				$order['tech1_name'] = $row['tech1_first_name'].' '.$row['tech1_last_name'];

				$order['tech2_name'] = $row['tech2_first_name'].' '.$row['tech2_last_name'];



				//COMMISSIONS CALCULATION

				//$rows_persons = $this->_calculate(&$order, &$comm_settings);

				$data = $this->_calculate(&$order, &$comm_settings);

				$rows_persons = $data[0];

				$temp = array();

				for ($x=1; $x<=4; $x++)

				{

					$id = $rows_persons[$x]['id'];

					if ($techid == $id)

					{

						$summary[$row['job_date']]['id'] = $id;

						$summary[$row['job_date']]['job_date'] = $row['job_date'];

						$temp['total'][0] = 0 + $order['total'][0];

						$temp['total'][1] = 0 + $order['total'][1];

						$summary[$row['job_date']]['booking_comm'] += 0+$rows_persons[$x]['booking_comm'];

						$summary[$row['job_date']]['sales_job_comm'] += 0+$rows_persons[$x]['sales_job_comm'];

						$summary[$row['job_date']]['sales_appl_comm'] += 0+$rows_persons[$x]['sales_appl_comm'];

						$summary[$row['job_date']]['driving_comm'] += 0+$rows_persons[$x]['driving_comm'];

						$summary[$row['job_date']]['redo_penalty'] += 0+$rows_persons[$x]['redo_penalty'];

						$summary[$row['job_date']]['helper_ded'] += 0+$rows_persons[$x]['helper_ded'];

						$summary[$row['job_date']]['adjustment'] += 0+$rows_persons[$x]['adjustment'];

						$summary[$row['job_date']]['total_comm'] += 0+$rows_persons[$x]['total_comm'];

						if ($rows_persons[$x]['verified'])

							$summary[$row['job_date']]['approuved'] += 0+$rows_persons[$x]['total_comm'];

					}

				}

				if ($temp['total'][0]!=0)

					$summary[$row['job_date']]['total'][0] += $temp['total'][0];

				if ($temp['total'][1]!=0)

					$summary[$row['job_date']]['total'][1] += $temp['total'][1];

			}				



			//SET PAGE OPTIONS			

			//$techid = 2; $this->set("loggedUserIsTech",1);

			if (($_SESSION['user']['role_id'] == 1) ){

				$this->set("loggedUserIsTech",1);

			}

			
			ksort($summary);			

			$this->set("summary", $summary);
			
			$this->set("tech_details", $tech_details);

			$this->set("job_option", $job_option);

			$this->set("techid", $techid);
			
			$this->set("pay_period", $pay_period);
			
			$this->set("check_pay", $check_pay);

			$this->set('allTechnician', $allTechnicians);

			$this->set('comm_roles',$this->Lists->ListTable('ace_rp_commissions_roles'));

			$this->set("ismobile", $this->Session->read("ismobile"));
			$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));

			$this->set('next_tdate', date("d M Y", strtotime($fdate) + 24*60*60));

			$this->set('allPayPeriods', $this->Lists->PayPeriods(1));


			if($fdate!='')

				$this->set('fdate', date("d M Y", strtotime($fdate)));

			if($tdate!='')

				$this->set('tdate', date("d M Y", strtotime($tdate)));

		}

	}



	// Commission summary page

	// Created: Anthony Chernikov, 08/26/2010

	function summary()

	{

		$this->layout="list";

		if (($_SESSION['user']['role_id'] == 4) || ($_SESSION['user']['role_id'] == 6)) 

		{ // ACCOuntant=4, administrator=6

			//**********

			//CONDITIONS

			//Convert date from date picker to SQL format

			

			$db =& ConnectionManager::getDataSource('default');

			$pay_period = $this->params['url']['pay_period'];

			if (!$pay_period)

			{

				$pay_period = 1;

				$query = "select * from ace_rp_pay_periods where now() between start_date and end_date and period_type=1";

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

				

				if(($fdate != '')&&(!$cur_ref))

					$sqlConditions .= " AND a.job_date >= '".$this->Common->getMysqlDate($fdate)."'"; 

				if(($tdate != '')&&(!$cur_ref))

					$sqlConditions .= " AND a.job_date <= '".$this->Common->getMysqlDate($tdate)."'";

			}

			else

			{

				$fdate = '';

				$tdate = '';

				$sqlConditions .= " and exists (select * from ace_rp_pay_periods p where a.job_date between p.start_date and p.end_date and p.period_type=1 and p.id=$pay_period)";				

				$query = "select * from ace_rp_pay_periods where id=$pay_period";

				$result = $db->_execute($query);

				$row = mysql_fetch_array($result, MYSQL_ASSOC);

				$fdate = $row['start_date'];

				$tdate = $row['end_date'];

			}

	

			//The list of all technicians in the system. We'll need it anyway

			$allTechnicians = $this->Lists->Technicians();

	

			//This array is being used for the commission settings.

			$comm_settings = array();

			

			//Fill out commissions settings for the technicians.

			//If we've logged in as a technician - we just need our own settings

			foreach ($allTechnicians as $cur_id => $cur_value)

				$comm_settings[$cur_id] = $this->getCommissionSettings($cur_id);


			$sqlConditions .= " AND a.payment_method_type != 11";
			
			$query = "

				SELECT a.id, a.job_date, a.order_number, a.order_type_id, a.order_status_id,

							 at.name as order_type, at.category_id as job_type_category,

							

							 a.fact_job_beg, a.fact_job_end,

							 

							 a.booking_source_id,

							 a.booking_source2_id,

							 a.job_technician1_id,

							 a.job_technician2_id,

							 a.t1_method,

							 a.t2_method,

							

							 t1.first_name as tech1_first_name,

							 t1.last_name as tech1_last_name,

							 t1.commission_type as t1_commission_type,

							

							 t2.first_name as tech2_first_name,

							 t2.last_name as tech2_last_name,

							 t2.commission_type as t2_commission_type

	

				FROM 				`ace_rp_orders` as a

				INNER JOIN	`ace_rp_order_types` as at ON (a.order_type_id = at.id)

				LEFT JOIN		`ace_rp_users` as t1 on ( a.job_technician1_id = t1.id )

				LEFT JOIN		`ace_rp_users` as t2 on ( a.job_technician2_id = t2.id )

				WHERE 	order_status_id IN (1,5) $sqlConditions";

	

			$orders = array();

			$summary = array();

			$result = $db->_execute($query);

			while($row = mysql_fetch_array($result, MYSQL_ASSOC))

			{

				//Transfer all fields from the query result

				foreach ($row as $k => $v)

					$orders[$row['id']][$k] = $v;

				

				//Calculate/set special fields

				$orders[$row['id']]['job_time_payable'] =

					(strtotime($row['job_date'].' '.$row['fact_job_end'])-

					strtotime($row['job_date'].' '.$row['fact_job_beg']))/3600;



				$orders[$row['id']]['order_type'] = $row['order_type'];



				//COMMISSIONS CALCULATION

				$data = $this->_calculate(&$orders[$row['id']], &$comm_settings);

				$orders[$row['id']]['comm'] = $data[0];

				$rows_persons = $data[0];

				//Summary calculation

				for ($x=1; $x<=4; $x++)

				{

					$id =$allTechnicians[$rows_persons[$x]['id']];

					if ($id)

					{

						$summary[$id]['id'] = $rows_persons[$x]['id'];

						$summary[$id]['name'] = $id;

						$summary[$id]['booking_comm'] += 0+$rows_persons[$x]['booking_comm'];

						$summary[$id]['sales_job_comm'] += 0+$rows_persons[$x]['sales_job_comm'];

						$summary[$id]['sales_appl_comm'] += 0+$rows_persons[$x]['sales_appl_comm'];

						$summary[$id]['driving_comm'] += 0+$rows_persons[$x]['driving_comm'];

						$summary[$id]['redo_penalty'] += 0+$rows_persons[$x]['redo_penalty'];

						$summary[$id]['helper_ded'] += 0+$rows_persons[$x]['helper_ded'];

						$summary[$id]['adjustment'] += 0+$rows_persons[$x]['adjustment'];

						$summary[$id]['total_comm'] += 0+$rows_persons[$x]['total_comm'];

						if ($rows_persons[$x]['verified'])

							$summary[$id]['approuved'] += 0+$rows_persons[$x]['total_comm'];

					}

				}

			}

			

			$query = "select * from ace_rp_tech_comm where period=$pay_period";

			$result = $db->_execute($query);

			while($row = mysql_fetch_array($result, MYSQL_ASSOC))

			{

				$id = $allTechnicians[$row['tech_id']];

				$summary[$id]['final_adj'] = $row['extra'];

			}

			

			ksort($summary);

			

			//SET PAGE OPTIONS			

			//$techid = 2; $this->set("loggedUserIsTech",1);

			if (($_SESSION['user']['role_id'] == 1) ){

				$this->set("loggedUserIsTech",1);

			}

			

			$this->set("orders", $orders);

			$this->set("summary", $summary);

			$this->set("pay_period", $pay_period);

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

			$this->set('allTechnician', $allTechnicians);

			$this->set('allPayPeriods', $this->Lists->PayPeriods(1));			

			if($fdate!='') $this->set('fdate', date("d M Y", strtotime($fdate)));

			if($tdate!='') $this->set('tdate', date("d M Y", strtotime($tdate)));

		}

	}



	// Commission calculation for the given order only

	function getForOrder($order_id)

	{

		$db =& ConnectionManager::getDataSource('default');

		

		//The list of all technicians in the system. We'll need it anyway

		$allTechnicians = $this->Lists->Technicians();

		

		//Addintional lists

		$allSources = $this->Lists->BookingSources();

		$allJobStatuses = $this->Lists->ListTable('ace_rp_order_statuses');

		

		//This array is being used for the commission settings.

		$comm_settings = array();

		

		//Fill out commissions settings for the technicians.

		//If we've logged in as a technician - we just need our own settings

		foreach ($allTechnicians as $cur_id => $cur_value)

			$comm_settings[$cur_id] = $this->getCommissionSettings($cur_id);

		

		$orders = array();

		$rows_persons = array();

		$query = "

			SELECT a.id, a.job_date, a.order_number, a.order_type_id, a.order_status_id,

						 at.name as order_type, at.category_id as job_type_category,

						

						 a.fact_job_beg, a.fact_job_end,

						 

						 a.booking_source_id,

						 a.booking_source2_id,

						 a.job_technician1_id,

						 a.job_technician2_id,

						 a.t1_method,

						 a.t2_method,
						 a.estimate_sent,

						

						 t1.first_name as tech1_first_name,

						 t1.last_name as tech1_last_name,

						 t1.commission_type as t1_commission_type,

						

						 t2.first_name as tech2_first_name,

						 t2.last_name as tech2_last_name,

						 t2.commission_type as t2_commission_type



			FROM 				`ace_rp_orders` as a

			INNER JOIN	`ace_rp_order_types` as at ON (a.order_type_id = at.id)

			LEFT JOIN		`ace_rp_users` as t1 on ( a.job_technician1_id = t1.id )

			LEFT JOIN		`ace_rp_users` as t2 on ( a.job_technician2_id = t2.id )

			WHERE 	a.id = ".$order_id;



		$result = $db->_execute($query);

		if ($row = mysql_fetch_array($result, MYSQL_ASSOC))

		{

			//Transfer all fields from the query result

			foreach ($row as $k => $v) $orders[$k] = $v;

			

			//Calculate/set special fields

			$orders['job_time_payable'] =

				(strtotime($row['job_date'].' '.$row['fact_job_end'])-

				strtotime($row['job_date'].' '.$row['fact_job_beg']))/3600;

			

			$orders['order_status'] = $allJobStatuses[$row['order_status_id']];

			$orders['order_type'] = $row['order_type'];

						

			$orders['source_name'] = $allTechnicians[$row['booking_source_id']];

			$orders['source2_name'] = $allTechnicians[$row['booking_source2_id']];

			$orders['tech1_name'] = $row['tech1_first_name'].' '.$row['tech1_last_name'];

			$orders['tech2_name'] = $row['tech2_first_name'].' '.$row['tech2_last_name'];



			//COMMISSIONS CALCULATION

			$rows_persons = $this->_calculate(&$orders, &$comm_settings);

		}

		

		return $rows_persons;

	}



	// AJAX method for commission verification.

	// Inserts a record into the 'ace_rp_orders_comm' table for the given job and tech ids

	function setVerified()

	{

		$order_id = $_GET['order_id'];

		$tech_num = $_GET['tech_num'];

		$booking_comm = $_GET['booking_comm'];

		$sales_job_comm = $_GET['sales_job_comm'];

		$sales_appl_comm = $_GET['sales_appl_comm'];

		$driving_comm = $_GET['driving_comm'];

		$redo_penalty = $_GET['redo_penalty'];

		$helper_ded = $_GET['helper_ded'];

		$adjustment = $_GET['adjustment'];
		
		$part_comm = $_GET['part_comm'];
		
		$labour_comm = $_GET['labour_comm'];
		
		$source_job_comm = $_GET['source_job_comm'];

		$total_comm = 0+$booking_comm+$sales_job_comm+$sales_appl_comm+

										$driving_comm+$redo_penalty+$helper_ded+$adjustment+$part_comm+ $source_job_comm + $labour_comm;

		$user_id = $this->Common->getLoggedUserID();

		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		//Delete previous records

		$db->_execute("DELETE FROM ace_rp_orders_comm WHERE order_id=".$order_id." and tech_num=".$tech_num);

		//Save new data
	  $db->_execute("INSERT INTO ace_rp_orders_comm (order_id, tech_num, booking_comm,

			sales_job_comm, sales_appl_comm, driving_comm,redo_penalty, total_comm, helper_ded, adjustment, part_comm,source_job_comm,labour_comm,user_id)

	 VALUES ('".$order_id."','".$tech_num."','".$booking_comm."',

				'".$sales_job_comm."','".$sales_appl_comm."','".$driving_comm."',

				'".$redo_penalty."','".$total_comm."','".$helper_ded."','".$adjustment."','".$part_comm."','".$source_job_comm."','".$labour_comm."','".$user_id."')");

		exit;

	}

	

	// AJAX method for commission verification rollback.

	// Deletes a record into the 'ace_rp_orders_comm' table for the given job and tech ids

	function setUnVerified()

	{

		$order_id = $_GET['order_id'];

		$tech_num = $_GET['tech_num'];

		

		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		$db->_execute("DELETE FROM ace_rp_orders_comm WHERE order_id=".$order_id." and tech_num=".$tech_num);

		exit;

	}



	// AJAX method for setting the helper deduction percent in the job order

	function saveTechMethod()

	{

		$order_id = $_GET['order_id'];

		$method = $_GET['method'];

		$tech_num = $_GET['tech_num'];

		

		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		$db->_execute("update ace_rp_orders set t".$tech_num."_method='".$method."' where id=".$order_id);



		exit;

	}

	

	// Methods shows all the technicians

	function index()

	{

		$this->layout="list";

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$sort = $_GET['sort'];

		$order = $_GET['order'];

		if (!$order) $order = 'first_name asc';

		

		$conditions = "where is_active=1";

		$ShowInactive = $_GET['ShowInactive'];
		
		if ($ShowInactive) $conditions = "where is_active=0";
		
		$ShowNewTech = $_GET['ShowNewTech'];

		

		$query = "select u.id, u.first_name, u.last_name, c.name commission_type, u.is_active, u.phone

								,u.tech_inventory from ace_rp_users u

								inner join ace_rp_users_roles r on u.id=r.user_id and r.role_id=1

								left outer join ace_rp_commissions_roles c on u.commission_type=c.id

							".$conditions."

							 order by ".$order.' '.$sort;

		

		$items = array();

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result, MYSQL_ASSOC))

		{

			foreach ($row as $k => $v)

			  $items[$row['id']][$k] = $v;

		}

		$condition1 = "where is_active=2";
		
		$query1 = "select u.id, u.first_name, u.last_name, c.name commission_type, u.is_active, u.phone

								from ace_rp_users u

								inner join ace_rp_users_roles r on u.id=r.user_id and r.role_id=1

								left outer join ace_rp_commissions_roles c on u.commission_type=c.id

							".$condition1."

							 order by ".$order.' '.$sort;

		

		$items1 = array();

		$result = $db->_execute($query1);

		while($row = mysql_fetch_array($result, MYSQL_ASSOC))

		{

			foreach ($row as $k => $v)

			  $items1[$row['id']][$k] = $v;

		}

		$this->set('ShowInactive', $ShowInactive);
		
		$this->set('ShowNewTech', $ShowNewTech);

		$this->set('items', $items);
		
		$this->set('items1', $items1);

		$this->set('comm_roles', $this->Lists->ListTable('ace_rp_commissions_roles'));

	}



	// AJAX method for activation/deactivation of an Technician

	function changeActive()

	{
		$item_id = $_GET['item_id'];

		$is_active = $_GET['is_active'];

		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		$db->_execute("update ace_rp_users set is_active='".$is_active."' where id=".$item_id);

		exit;
	}

	// AJAX method for activation/deactivation of technician for inventory.

	function changeActiveInventory()

	{
		$tech_id = $_GET['item_id'];

		$is_active = $_GET['is_active'];

		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		$db->_execute("update ace_rp_users set tech_inventory='".$is_active."' where id=".$tech_id);

		exit;

	}

	// AJAX. Method generates HTML code for the calendar

	function getCalendar()

	{

		$CurTech = $_GET['cur_tech'];

		$CurMonth = $_GET['cur_month'];

		$CurYear = $_GET['cur_year'];

		echo $this->_getCalendar($CurTech, $CurMonth, $CurYear);

		exit;

	}

	

	// Method generates HTML code for the calendar

	function _getCalendar($CurTech, $CurMonth, $CurYear)

	{

		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		$query = "select month, day, weekday, state, start_time, end_time from ace_rp_tech_schedule

							 where tech_id='".$CurTech."' and year='".$CurYear."'

								 and month between '".($CurMonth-1)."' and '".($CurMonth+1)."'";

		$days = array();

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$days[$row['month'].'_'.$row['day']]['state'] = $row['state'];
			$days[$row['month'].'_'.$row['day']]['start'] = $row['start_time'];
			$days[$row['month'].'_'.$row['day']]['end'] = $row['end_time'];


		}
			
		$DaysOfWeek = array(0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat');

		$strTime=mktime(1,1,1,$CurMonth,1,date("Y"));

		
		// function markDay(monnum,daynum,state){

		// 	if (state==2) state=1; else state=2;

		// 	$.get("'.BASE_URL.'/commissions/markDay",

		// 		{cur_tech:"'.$CurTech.'",cur_year:'.$CurYear.',cur_month:monnum,cur_day:daynum,daytype:state},

		// 		function(data){$("#tech_schedule").html(data);});

		// }

		$sRet = '<script language="JavaScript">

		function MoveMonth(newmonth){

			var newyear='.$CurYear.';

			if (newmonth==13) {newyear++;newmonth=1;}

			else if (newmonth==0) {newyear--;newmonth=12;}

			$.get("'.BASE_URL.'/commissions/getCalendar",

				{cur_tech:"'.$CurTech.'",cur_year:newyear,cur_month:newmonth},

				function(data){$("#tech_schedule").html(data);});

		}

		</script>';

		$sRet .= '<table width=100%><tr>';

		$sRet .= '<td align=left><input type="button" value="&lt;&lt;" onclick="MoveMonth('.($CurMonth-1).')" style="font-size: 12pt;">&nbsp;</td>';

		$sRet .= '<td align=center><b style="font-size:14pt">'.date("F",$strTime).'&nbsp;'.$CurYear.'</b>&nbsp;</td>';

		$sRet .= '<td align=right><input type="button" value="&gt;&gt;" onclick="MoveMonth('.($CurMonth+1).')" style="font-size: 12pt;"></td>';

		$sRet .= '</tr><table width=100%>';

		$sRet .= '<table class="results" >';

		$sRet .= '<tr>';

		foreach ($DaysOfWeek as $Name) $sRet .= '<th>'.$Name.'</th>';

		$sRet .= '</tr>';

		

		if ($CurMonth>1) 

			$DaysInPrev = cal_days_in_month(CAL_GREGORIAN, $CurMonth-1, $CurYear);

		else

			$DaysInPrev = cal_days_in_month(CAL_GREGORIAN, 12, $CurYear-1);

		$DaysInCur = cal_days_in_month(CAL_GREGORIAN, $CurMonth, $CurYear);

		$FirstDayInCur = jddayofweek ( cal_to_jd(CAL_GREGORIAN, $CurMonth, 1, $CurYear));

		

		// The first week can contain days from the previous month

		$sRet .= '<tr class="cell0">';

		for ($x=$FirstDayInCur; $x>0; $x--)

		{

			$CurDay = ($DaysInPrev-$x+1);

			$state = $days[($CurMonth-1).'_'.$CurDay]['state'];

			$text = ""; $color = "";

			if ($state==2) {$text = 'OFF'; $color="background-color:#AAAAAA;";}

			if ($x==$FirstDayInCur) {$text = 'OFF'; $color="background-color:#AAAAAA;";}

			$sRet .= '<td class="calendar inactive" style="'.$color.'">

								<div class="calendar_off"><b>'.$text.'</b></div><br/>'.$CurDay.'</td>';

		}

		for ($x=$FirstDayInCur; $x<7; $x++)

		{

			$CurDay = (1+$x-$FirstDayInCur);

			$state = $days[$CurMonth.'_'.$CurDay]['state'];

			$text = ""; $color = "";
			if(!empty($days[$CurMonth.'_'.$CurDay]['start']))
			{
				$text = 'OFF ('.date("g:i a", strtotime($days[$CurMonth.'_'.$CurDay]['start'].':00')).' to '.date("g:i a", strtotime($days[$CurMonth.'_'.$CurDay]['end'].':00')).')';
			}
			if ($state==2) {$text = 'OFF'; $color="background-color:#AAAAAA;";}

			if ($x==0) {$text = 'OFF'; $color="background-color:#AAAAAA;";}

			// $sRet .= '<td class="calendar" style="'.$color.'" onclick="markDay('.$CurMonth.','.$CurDay.",'".$state."'".')">

			// 					<div class="calendar_off"><b>'.$text.'</b></div><br/>'.$CurDay.'</td>';
			$sRet .= '<td class="calendar openTimeBox" style="'.$color.'" current-day="'.$CurDay.'" current-month="'.$CurMonth.'" current-tech="'.$CurTech.'" current-year="'.$CurYear.'" start-time="'.$days[$CurMonth.'_'.$CurDay]['start'].'" end-time="'.$days[$CurMonth.'_'.$CurDay]['end'].'">

								<div class="calendar_off"><b>'.$text.'</b></div><br/>'.$CurDay.'</td>';

		}

		$sRet .= '</tr>';

		

		for ($y=0; $y<5; $y++)

		{

			$sRet .= '<tr class="cell0">';

			for ($x=0; $x<7; $x++)

			{

				$CurDay = $y*7+$x+8-$FirstDayInCur;

				if ($CurDay<=$DaysInCur)

				{

					$state = $days[$CurMonth.'_'.$CurDay]['state'];

					$text = ""; $color = "";

					if(!empty($days[$CurMonth.'_'.$CurDay]['start']))
					{
						$text = 'OFF ('.date("g:i a", strtotime($days[$CurMonth.'_'.$CurDay]['start'].':00')).' to '.date("g:i a", strtotime($days[$CurMonth.'_'.$CurDay]['end'].':00')).')';
					}
					if ($state==2) {$text = 'OFF'; $color="background-color:#AAAAAA;";}

					if ($x==0) {$text = 'OFF'; $color="background-color:#AAAAAA;";}

					// $sRet .= '<td class="calendar" style="'.$color.'" onclick="markDay('.$CurMonth.','.$CurDay.",'".$state."'".')">

					// 					<div class="calendar_off"><b>'.$text.'</b></div>'.$CurDay.'</td>';
					$sRet .= '<td class="calendar openTimeBox" style="'.$color.'" current-day="'.$CurDay.'" current-month="'.$CurMonth.'" current-tech="'.$CurTech.'" current-year="'.$CurYear.'" start-time="'.$days[$CurMonth.'_'.$CurDay]['start'].'" end-time="'.$days[$CurMonth.'_'.$CurDay]['end'].'">

										<div class="calendar_off">'.$text.'</div>'.$CurDay.'</td>';

				}

				else

				{

					$CurDay = $CurDay-$DaysInCur;

					$state = $days[($CurMonth+1).'_'.$CurDay];

					$text = ""; $color = "";

					if ($state==2) {$text = 'OFF'; $color="background-color:#AAAAAA;";}

					if ($x==0) {$text = 'OFF'; $color="background-color:#AAAAAA;";}

					$sRet .= '<td class="calendar inactive" style="'.$color.'">

										<div class="calendar_off"><b>'.$text.'</b></div>'.$CurDay.'</td>';

				}

			}

			$sRet .= '</tr>';

		}

		$sRet .= '</table>';

		

		return $sRet;

	}



	// AJAX. Method changes the state of the given day in tech's schedule

	function markDay()

	{

		$CurTech = $_GET['cur_tech'];

		$CurYear = $_GET['cur_year'];

		$CurMonth = $_GET['cur_month'];

		$CurDay = $_GET['cur_day'];

		$DayType = $_GET['daytype'];

		$StartTime = $_GET['start_time'];

		$EndTime = $_GET['end_time'];

		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		$db->_execute("replace into ace_rp_tech_schedule (tech_id, year, month, day, state,start_time, end_time)

									 values ('".$CurTech."','".$CurYear."','".$CurMonth."','".$CurDay."','".$DayType."','".$StartTime."', '".$EndTime."')");



		// Sending messages to admins if the tech's taking a day off

		if (($DayType==2)&&($this->Common->getLoggedUserRoleID()==1))

		{

			$allTechnicians = $this->Lists->Technicians();

			$txt = "{$allTechnicians[$CurTech]} is taking a day off on $CurMonth/$CurDay/$CurYear";

			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

			$sql = "INSERT INTO ace_rp_messages

							(txt, state, from_user, from_date, 

							 to_user, to_date, to_time)

			 VALUES ('$txt', 0, ".$this->Common->getLoggedUserID().", current_date(),

							 44851, current_date(), '00:00')";

			$db->_execute($sql);

			$sql = "INSERT INTO ace_rp_messages

							(txt, state, from_user, from_date, 

							 to_user, to_date, to_time)

			 VALUES ('$txt', 0, ".$this->Common->getLoggedUserID().", current_date(),

							 57499, current_date(), '00:00')";

			$db->_execute($sql);

		}



		echo $this->_getCalendar($CurTech, $CurMonth, $CurYear);

		exit;

	}

	

	function finalAdjust()

	{

		$period_id = $_GET['period_id'];

		$tech_id = $_GET['tech_id'];

		$adjustment = $_GET['adjustment'];

		

		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		//Save new data

		$db->_execute("replace INTO ace_rp_tech_comm (period, tech_id, extra)

			 VALUES ('$period_id','$tech_id','$adjustment')");



		exit;

	}

	

	function roulette()

	{

		

	}

	function chartreport(){
		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);
		$this->layout = "blank";
		$query = "select a.*
				  from ace_rp_users as a, ace_rp_users_roles as b
				 where a.id=b.user_id and a.is_active=1 and b.role_id in (1)
				 order by a.first_name";
		$result = $db->_execute($query);
		$allTechnicians= array();
		$date1 =$_REQUEST['date1'];
		$date2 =$_REQUEST['date2'];
		
		$result_ids = array();
		if(isset($_REQUEST['date'])){
			$date = $_REQUEST['date'];
		}
		else {
			$date = date('Y-m-d',strtotime("-1 days"));
		}
		
		if(isset($_REQUEST['date1']) && isset($_REQUEST['date2']) ){
			$date_sql="(job_date BETWEEN '$date1' AND '$date2')";
		}
		else {
			$date_sql="job_date='$date'";
		}
		
		
		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$allTechnicians[]=$row;
			$id1=$row['id'];
			$query1 = "select id from ace_rp_orders where job_technician1_id=$id1 and $date_sql";
			
			$result1 = $db->_execute($query1);
			
			while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC)) {
				
				$order_id = $row1['id'];
				
				$query2="select price,quantity,class from ace_rp_order_items where order_id=$order_id";
				
				$result2 = $db->_execute($query2);
				
					while($row2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
				
				
			
							$result_ids[] = array(
					   
					   'id' =>$row['id'],
					   'price'=>($row2['price']*$row2['quantity'])+$row2['addition'],
					   'class'=>$row2['class'],
					   
					   );
					   
			}
			
			}
					   
					   
    
			
			
		}
		$this->set('allTechnicians', $allTechnicians);
		
		$this->set('result_ids', $result_ids);
		
		
	}

	function commissionSettings($id) {

		$this->layout = "blank";

		

		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		

		//BEGIN navigation

		$query = "

			SELECT *

			FROM ace_rp_commission_roles

			WHERE commission_person_type_id = 2

			AND name != 'Default'

		";

		

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

			$commission_tech_roles[$row['id']]['id'] = $row['id'];

			$commission_tech_roles[$row['id']]['name'] = $row['name'];

			$commission_tech_roles[$row['id']]['commission_person_type_id'] = $row['commission_person_type_id'];			

		}



		$query = "

			SELECT cs.user_id, u.first_name, u.last_name

			FROM (SELECT DISTINCT user_id FROM ace_rp_commission_settings) cs

			LEFT JOIN ace_rp_users u

			ON cs.user_id = u.id

		";

		

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

			$commission_techs[$row['user_id']]['id'] = $row['id'];

			$commission_techs[$row['user_id']]['first_name'] = $row['first_name'];

			$commission_techs[$row['user_id']]['last_name'] = $row['last_name'];

		}

		

		//END Navigation

		

		$query = "

			SELECT *

			FROM ace_rp_commission_settings

			WHERE user_id = $id

		";

		

		$result = $db->_execute($query);

		

		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

			$commission_settings[$row['commission_role_id']][$row['commission_from_id']][$row['commission_type_id']][$row['commission_item_id']][$row['commission_division_id']]['value_percent'] = $row['value_percent'];

			$commission_settings[$row['commission_role_id']][$row['commission_from_id']][$row['commission_type_id']][$row['commission_item_id']][$row['commission_division_id']]['value_fixed'] = $row['value_fixed'];

		}		

		

		$query = "

			SELECT cr.id, cr.name, cr.commission_person_type_id

			FROM (SELECT DISTINCT commission_role_id FROM ace_rp_commission_settings WHERE user_id = $id) cs

			LEFT JOIN ace_rp_commission_roles cr

			ON cs.commission_role_id = cr.id

		";

		

		$result = $db->_execute($query);

		

		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {			

			$commission_existing_roles[$row['commission_person_type_id']][$row['id']] = $row['name'];

		}

		

		$query = "

			SELECT *

			FROM ace_rp_commission_types

		";

		

		$result = $db->_execute($query);

		

		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {			

			$commission_types[$row['id']]['name'] = $row['name'];

			$commission_types[$row['id']]['class'] = $row['class'];

		}

		

		$query = "

			SELECT *

			FROM ace_rp_order_types

		";

		

		$result = $db->_execute($query);

		

		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {			

			$job_types[$row['id']]['name'] = $row['name'];

		}

		

		$query = "

			SELECT *

			FROM ace_iv_categories

		";

		

		$result = $db->_execute($query);

		

		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {			

			$item_categories[$row['id']]['name'] = $row['name'];

		}

		

		$query = "

			SELECT *

			FROM ace_iv_items

		";

		

		$result = $db->_execute($query);

		

		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {			

			$items[$row['id']]['name'] = $row['name'];

		}

		

		$this->set('commission_tech_roles', $commission_tech_roles);		

		$this->set('commission_techs', $commission_techs);

		$this->set('commission_existing_roles', $commission_existing_roles);

		$this->set('commission_types', $commission_types);

		$this->set('commission_settings', $commission_settings);

		$this->set('job_types', $job_types);

		$this->set('item_categories', $item_categories);

		$this->set('items', $items);

		$this->set('techs', $this->Lists->Technicians());

		$this->set('user_id', $id);

		

	}

	

	function saveCommissionSettings($id) {

		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);		

		

		$query = "

			DELETE FROM ace_rp_commission_settings

			WHERE user_id = $id

		";			

		$result = $db->_execute($query);

		

		foreach($this->data['Settings'] as $commission_role_id => $commission_role) {

			foreach($commission_role as $commission_from_id => $commission_from) {

				foreach($commission_from as $commission_type_id => $commission_type) {

					foreach($commission_type as $commission_item_id => $commission_item) {

						foreach($commission_item as $commission_division_id => $value) {

							//test script

							echo "$commission_role_id $commission_from_id $commission_type_id $commission_item_id $commission_division_id $id ". $value['value_fixed'] ." ".$value['value_percent']."<br />";

							

							$query = "

							INSERT INTO ace_rp_commission_settings(`commission_role_id`, `commission_from_id`, `commission_type_id`, `commission_item_id`, `commission_division_id`, `user_id`, `value_percent`, `value_fixed`)

								VALUES($commission_role_id, $commission_from_id, $commission_type_id, $commission_item_id, $commission_division_id, $id, ".$value['value_percent'].",".$value['value_fixed'].");

							";			

							$result = $db->_execute($query);					

											

						}//END commission_item

					}//END commission_type

				}//END commission_from

			}//END commission_role

		}//END data[Settings]

						

		$this->redirect($this->referer());

	}



	function techList() {

		$this->layout = "blank";

		

		$this->Lists->Technicians();

		

		$this->set('techs', $this->Lists->Technicians());

	}



	function jobTypeList() {

		$this->layout = "blank";

		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		

		$query = "

			SELECT * FROM ace_rp_order_types

			WHERE flagactive = 1

		";

		

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

			$jobTypeList[$row['id']]['name'] = $row['name'];			

		}

		

		$this->set('jobTypeList', $jobTypeList);

	}



	function itemCategoryList() {

		$this->layout = "blank";

		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		

		$query = "

			SELECT * FROM ace_iv_categories

		";

		

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

			$itemCategoryList[$row['id']]['name'] = $row['name'];			

		}

		

		$this->set('itemCategoryList', $itemCategoryList);

	}

	

	function itemList() {

		$this->layout = "blank";

		$search = $_GET['search'];

		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		

		$query = "

			SELECT * 

			FROM ace_iv_items

			WHERE name LIKE '%$search%'

			OR model LIKE '%$search%'

		";

		

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

			$itemList[$row['id']]['name'] = $row['name'];

			$itemList[$row['id']]['model'] = $row['model'];		

		}

		

		$this->set('itemList', $itemList);

	}
	public function sendCommReviewMailToTech() {
		$data = $_POST;
		$fromDate = $data['fromDate'];
		$sort = $data['sort'];
		$jobOption = $data['jobOption'];
		$currentRef = $data['currentRef'];
		$currentPage = $data['currentPage'];
		$orderId = $data['orderId'];
		$adminNotes = isset($data['adminNotes']) ? $data['adminNotes'] : '';
		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);
		if(!empty($adminNotes))
		{
			$query = "UPDATE ace_rp_orders set admin_notes='".$adminNotes."' where id=".$orderId;
			$result = $db->_execute($query);
		}
		$url = urlencode('action=view&order=&sort='.$sort.'&currentPage='.$currentPage.'&comm_oper=&ftechid='.$techId.'&selected_job=&selected_commission_type=&job_option='.$jobOption.'&ffromdate='.$fromDate.'&cur_ref=&orderId='.$orderId);
		foreach($data['techIds'] as $tech)
		{
			$query = "SELECT first_name, email from ace_rp_users where id=".$tech."";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$techName = $row['first_name'];			
				$techEmail = $row['email'];
			}
			$query = "SELECT email FROM ace_rp_commission_email where id=1";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$defaultEmail = $row['email'];
			}	
			$body = 'Hi '.$techName.',<br><br> Please find the URL for Todays Commission Confirmation.<br><br>';
			 $body .= '<a href="http://hvacproz.ca/acesys/index.php/commissions/calculateCommissions?'.$url.'">Click Here</a>';
			$from = $defaultEmail;
			$to = $techEmail;
			 // $to = "lokendra.k@cisinlabs.com";
			 $subject = "Commission";
			$this->sendEmailUsingMailgun($to,$from,$subject, $body, $header);
		}
		exit();
	}
	
	public function saveTechCommission()
	{
		$data = $_POST;
		$techId = $data['techId'];
		$fromDate = $data['fromDate'];
		$sort = $data['sort'];
		$jobOption = $data['jobOption'];
		$currentRef = $data['currentRef'];
		$currentPage = $data['currentPage'];
		$isAdmin = $data['isAdmin'];
		$adminEmail = isset($data['adminEmail']) ? $data['adminEmail'] : '';
		
		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		foreach ($data["techCommArr"] as $key => $value) {
			if(!empty($_FILES['techCommArr']['name'][$key]['uploadFile']))
			{
			    $imageName = $_FILES['techCommArr']['name'][$key]['uploadFile'];
				$imageTempName = $_FILES['techCommArr']['tmp_name'][$key]['uploadFile'];
				$imageResult = $this->Common->TechCommonSavePaymentImage($imageName, $imageTempName, $key, $config = $this->User->useDbConfig);
			}	
			if(!empty($_FILES['techCommArr']['name'][$key]['sortpic2']))
			{
				$imageName = $_FILES['techCommArr']['name'][$key]['sortpic2'];
				$imageTempName = $_FILES['techCommArr']['tmp_name'][$key]['sortpic2'];
				$imageResult = $this->Common->techUploadPhoto($imageName, $imageTempName ,$key , $config = $this->User->useDbConfig, 2);
			}
			if(!empty($_FILES['techCommArr']['name'][$key]['sortpic1']))
			{   
				$imageName = $_FILES['techCommArr']['name'][$key]['sortpic1'];
				$imageTempName = $_FILES['techCommArr']['tmp_name'][$key]['sortpic1'];    
				$imageResult = $this->Common->techUploadPhoto($imageName, $imageTempName, $key, $config = $this->User->useDbConfig, 1);
			}

			if(!empty($value['tech-notes']))
			{
				$query = "UPDATE ace_rp_orders set tech_notes='".$value['tech-notes']."' where id=".$key."";
				$result = $db->_execute($query);
			}
			if(!empty($value['admin-notes']))
			{
				$query = "UPDATE ace_rp_orders set admin_notes='".$value['admin-notes']."' where id=".$key."";
				$result = $db->_execute($query);
			}
		}

		$techCommDate = date("Y-m-d", strtotime($fromDate));

		 $query = "SELECT comm_date from ace_rp_tech_done_comm where comm_date='".$techCommDate."' AND tech_id=".$techId;
		 $result = $db->_execute($query);
		 $row = mysql_fetch_array($result, MYSQL_ASSOC);		 
		 $commDate = $row['comm_date'];
		 if(empty($commDate) || $commDate == '')
		 {   
			$inserData = "INSERT INTO ace_rp_tech_done_comm (tech_id,comm_date, status) values (".$techId.",'".$techCommDate."', 1)";
			$result = $db->_execute($inserData);
		 }				
		 $url = urlencode('action=view&order=&sort='.$sort.'&currentPage='.$currentPage.'&comm_oper=&ftechid='.$techId.'&selected_job=&selected_commission_type=&job_option='.$jobOption.'&ffromdate='.$fromDate.'&cur_ref=');

		if($isAdmin == 0)
		{
			$query = "SELECT first_name, email from ace_rp_users where id=".$techId."";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$techName = $row['first_name'];			
				$techEmail = $row['email'];
			}				
			 $body = 'Hi Admin,<br><br> Please find the URL for Todays Commission Confirmation.<br><br>';
			 $body .= '<a href="http://hvacproz.ca/acesys/index.php/commissions/calculateCommissions?'.$url.'">Click Here</a>';
			// $body .= '<a href="http://localhost/acesys/index.php/commissions/calculateCommissions?'.$url.'">Click Here</a>';
			
			$to = $adminEmail ;
			$subject = "Commission";
			$from = $techEmail;
		} else {
			$query = "SELECT first_name, email from ace_rp_users where id=".$techId."";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$techName = $row['first_name'];			
				$techEmail = $row['email'];
			}
			$query = "SELECT email FROM ace_rp_commission_email where id=1";
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$defaultEmail = $row['email'];
			}	
			$body = 'Hi '.$techName.',<br><br> Please find the URL for Todays Commission Confirmation.<br><br>';
			 $body .= '<a href="http://hvacproz.ca/acesys/index.php/commissions/calculateCommissions?'.$url.'">Click Here</a>';
			$from = $defaultEmail;
			$to = $techEmail;
			 // $to = "lokendra.k@cisinlabs.com";
			 $subject = "Commission";
		}
		 
		// $header = "Content-Type: text/html; charset=iso-8859-1\n" ;
		$this->sendEmailUsingMailgun($to,$from,$subject, $body, $header);
		// $this->redirect('/commissions/calculateCommissions?action=view&order=&sort='.$sort.'&currentPage='.$currentPage.'&comm_oper=&ftechid='.$techId.'&selected_job=&selected_commission_type=&job_option='.$jobOption.'&ffromdate='.$fromDate.'&cur_ref=');
		$this->redirect('/orders/invoiceTablet');
		exit();
	}

	function sendEmailUsingMailgun($to,$from,$subject,$body, $header){
	
		// error_reporting(E_ALL);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://hvacproz.ca/acesystem2018/mailcheck.php");
		// curl_setopt($ch, CURLOPT_URL,"http://acecare.ca/acesystem2018/mailcheck.php");
		// curl_setopt($ch, CURLOPT_URL,"http://35.209.147.55/acesystem2018/mailcheck.php");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"TO=".$to."&SUBJECT=".$subject."&BODY=".$body."&FROM=".$from);
		
		// receive server response ...
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$msgid = curl_exec ($ch);//exit;
		curl_close ($ch);
	}
	# LOKI- This function is used to set the technician commision confirmation.
	public function setTechVerified()
	{
		$order_id = $_GET['order_id'];
		$tech_num = $_GET['tech_num'];
		$tech_status_val = $_GET['tech_status_val'];
		$total_comm = $_GET['total_comm'];	
		$old_total_comm = $_GET['old_total_comm'];
		//$new_total_comm = 0;
		$new_total_comm = $total_comm + $old_total_comm;
		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);
		$query = "INSERT INTO ace_rp_tech_comm_confirm (order_id, tech_num, tech_confirm) VALUES (".$order_id.", ".$tech_num.", ".$tech_status_val.")";

		$result = $db->_execute($query);
		exit();
	}
	# LOKI- This function is used to unset the technician commision confirmation.
	public function setTechUnVerified()
	{
		$order_id = $_GET['order_id'];
		$tech_num = $_GET['tech_num'];
		$total_comm = $_GET['total_comm'];	
		$old_total_comm = $_GET['old_total_comm'];
		$tech_status_val = $_GET['tech_status_val'];
		$new_total_comm = 0;
		$total_field = 'tech_not_confirm_total';
		if($tech_status_val == 1)
		{
			$total_field = 'tech_confirm_total';
		}
		
		$new_total_comm = $old_total_comm - $total_comm;
		
		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);
		$query = "DELETE from  ace_rp_tech_comm_confirm where order_id= ".$order_id." AND tech_num=".$tech_num."";
		$result = $db->_execute($query);
		exit();
	}

	function showDefaultCommissionEmail()
	{
		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);
		$query = "SELECT email FROM ace_rp_commission_email where id=1";
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$email = $row['email'];
		}
		$this->set('email', $email);
	}

	public function saveDefaultEmail()
	{
		$email = $_POST['email'];
		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);
		$query = "UPDATE ace_rp_commission_email set email = '".$email."' where id=1";
		$result = $db->_execute($query);
		$this->redirect('/commissions/showDefaultCommissionEmail');
		exit();
	}
	 //Loki: get the purchase item invoices
	function getOrderInvoices($orderId)
	{
		if(!empty($orderId))
		{
			$db =& ConnectionManager::getDataSource('default');
			$query = "SELECT invoice_image FROM ace_rp_order_items where order_id=".$orderId." AND invoice_image IS NOT NULL";
			$result = $db->_execute($query);
			$invoiceArray = array();
			while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				if(!empty($row['invoice_image']) || ($row['invoice_image'] != '')) {
					$invoiceArray[] = $row['invoice_image'];
				}
			}
			return $invoiceArray;
			exit();
		}
		exit();
	}

	function deleteTech()
	{
		$tech_id = $_GET['tech_id'];

		$is_active = $_GET['is_active'];



		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		$db->_execute("DELETE FROM ace_rp_users  where id=".$tech_id);

		exit;

	}

	function sendTechinvoice()
	{
		$wcb_amount = $_POST['wcb_amount'];
		$subtotal_amount = $_POST['subtotal_amount'];
		$gst_amount = $_POST['gst_amount'];
		$total_amount = $_POST['total_amount'];
		$techId = $_POST['techId'];
		$to_email = $_POST['to_email'];
		$pay_period = $_POST['pay_period'];
		$ffromdate = $_POST['ffromdate'];
		$ftodate = $_POST['ftodate'];
		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		$this->User->id = $techId;
		$tech_details = $this->User->read();	
		$settings = $this->Setting->find(array('title'=>'tech_invoice'));
        $template = $settings['Setting']['valuetxt'];
        $subject = $settings['Setting']['subject'];

        $msg = $template;

        // print_r($msg); die;
        $msg = str_replace('{company_name}',$tech_details['User']['company_name'] , $msg);
        $msg = str_replace('{tech_name}', $tech_details['User']['first_name'].' '.$tech_details['User']['last_name'], $msg);
        $msg = str_replace('{address}',$tech_details['User']['address'] , $msg);
        $msg = str_replace('{city}',$tech_details['User']['city'] , $msg);
        $msg = str_replace('{post_code}',$tech_details['User']['postal_code'] , $msg);
        $msg = str_replace('{phone_number}',$tech_details['User']['phone'], $msg);
        $msg = str_replace('{email}',$tech_details['User']['email'] , $msg);
        $msg = str_replace('{gst_num}',$tech_details['User']['gst_number'] , $msg);
        $msg = str_replace('{wcb_num}',$tech_details['User']['wcb_number'], $msg);
        $msg = str_replace('{isssue_date}', date("d/m/Y"), $msg);
        $msg = str_replace('{invoice_num}',$tech_details['User']['invoice_num'] , $msg);
        $msg = str_replace('{desription}', '', $msg);
        $msg = str_replace('{from}', date("d/m/Y",strtotime($ffromdate)), $msg);
        $msg = str_replace('{to}', date("d/m/Y",strtotime($ftodate)), $msg);
        $msg = str_replace('{sub_total}', $subtotal_amount, $msg);
        $msg = str_replace('{gst_amt}',$gst_amount , $msg);
        $msg = str_replace('{total_amount}',$total_amount, $msg);
        // $res = $this->Tcpdf->createPdf($msg);
        $result = $this->Mpdf->createPdf($msg);
        $orgFile = $this->G_URL."/acesys/app/webroot/tech-invoice/".$result;
        $msgBody = '<p>Hi,</p>
                                <p>Please find the attached Invoice.</p>
                                <p>&nbsp;</p>
                                <p>Thank you,</p>
                                <p>Pro Ace Heating &amp; Air Conditioning Ltd<br />Tel: 604-293-3770<br />&nbsp;<a href="https://www.acecare.ca"><img src="https://www.acecare.ca/wp-content/uploads/2018/08/newacelogooptimized.png" alt="" width="461" height="81" /></a></p>
                                <p>&nbsp;</p>
                                <p>&nbsp;</p>';
        $res = $this->Common->sendEmailMailgun($to_email,$subject,$msgBody,null,$orgFile);
        
        if (strpos($res, '@acecare') !== false) 
        {
        	$db->_execute("UPDATE ace_rp_users set invoice_num = invoice_num+1 WHERE id=".$techId);
         	$response  = array("res" => "1");
            echo json_encode($response);   
        } else 
        {
           $response  = array("res" => "2");
           echo json_encode($response);
        }
        exit();
	}

	function printTechInvoice()
	{
		$wcb_amount = $_POST['wcb_amount'];
		$subtotal_amount = $_POST['subtotal_amount'];
		$gst_amount = $_POST['gst_amount'];
		$total_amount = $_POST['total_amount'];
		$techId = $_POST['techId'];
		$to_email = $_POST['to_email'];
		$pay_period = $_POST['pay_period'];
		$ffromdate = $_POST['ffromdate'];
		$ftodate = $_POST['ftodate'];
		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		$this->User->id = $techId;
		$tech_details = $this->User->read();	
		$settings = $this->Setting->find(array('title'=>'tech_invoice'));
        $template = $settings['Setting']['valuetxt'];
        $subject = $settings['Setting']['subject'];

        $msg = $template;
        $msg = str_replace('{company_name}',$tech_details['User']['company_name'] , $msg);
        $msg = str_replace('{tech_name}', $tech_details['User']['first_name'].' '.$tech_details['User']['last_name'], $msg);
        $msg = str_replace('{address}',$tech_details['User']['address'] , $msg);
        $msg = str_replace('{city}',$tech_details['User']['city'] , $msg);
        $msg = str_replace('{post_code}',$tech_details['User']['postal_code'] , $msg);
        $msg = str_replace('{phone_number}',$tech_details['User']['phone'], $msg);
        $msg = str_replace('{email}',$tech_details['User']['email'] , $msg);
        $msg = str_replace('{gst_num}',$tech_details['User']['gst_number'] , $msg);
        $msg = str_replace('{wcb_num}',$tech_details['User']['wcb_number'], $msg);
        $msg = str_replace('{isssue_date}', date("d/m/Y"), $msg);
        $msg = str_replace('{invoice_num}',$tech_details['User']['invoice_num'] , $msg);
        $msg = str_replace('{desription}', '', $msg);
        $msg = str_replace('{from}', date("d/m/Y",strtotime($ffromdate)), $msg);
        $msg = str_replace('{to}', date("d/m/Y",strtotime($ftodate)), $msg);
        $msg = str_replace('{sub_total}', $subtotal_amount, $msg);
        $msg = str_replace('{gst_amt}',$gst_amount , $msg);
        $msg = str_replace('{total_amount}',$total_amount, $msg);
       
       echo $msg;
        exit();
	}

	function techPurchase()
	{
		$this->layout="list";
		$this->set("loggedUserIsTech",0);
		if (($_SESSION['user']['role_id'] == 1) ) { // TECHNICIAN=1

			//show data only for current technician

			$techid = $this->Common->getLoggedUserID();
			$this->set("loggedUserIsTech",1);

		}
		if($check_pay == 1){

				if($pay_period > 0)
				{
					$query = "select * from ace_rp_pay_periods where id=".$pay_period;

					$result = $db->_execute($query);

					$row = mysql_fetch_array($result, MYSQL_ASSOC);

					$fdate = date("Y-m-d", strtotime($row['start_date']));
					$tdate = date("Y-m-d", strtotime($row['end_date']));
				} else {
					$fdate = date("Y-m-d");
					$tdate = date("Y-m-d");
				}

		} else {
			if ($this->params['url']['ffromdate'] != '')

				$fdate = date("Y-m-d", strtotime($this->params['url']['ffromdate']));

			else

				$fdate = date("Y-m-d");

	

			if ($this->params['url']['ftodate'] != '')

				$tdate = date("Y-m-d", strtotime($this->params['url']['ftodate']));

			else

				$tdate = date("Y-m-d");
		}

		$allTechnicians = $this->Lists->Technicians();
		$this->set('allTechnician', $allTechnicians);
		$this->set('techid', $techid);
		$this->set('allPayPeriods', $this->Lists->PayPeriods(1));
		$this->set('fdate', date("d M Y", strtotime($fdate)));
		$this->set('tdate', date("d M Y", strtotime($tdate)));	
		$this->set('purchaseDate', date("d M Y"));	
		$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));
		$this->set('next_tdate', date("d M Y", strtotime($fdate) + 24*60*60));
	}

	function saveTechPurchaseItem()
	{	
		$data = $_POST;
		$items = $_POST['itemName'];
		$itemImages = $_FILES['sortpic1'];
		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);
		$techid = $data['ftechid'];
		if($this->Common->getLoggedUserRoleID() == 1)
		{
			$techid = $this->Common->getLoggedUserID();
		}
		foreach ($items as $key => $value) {
			if(empty($data['itemStatus'][$key])){
				$status = 2;
			} else {
				$status = $data['itemStatus'][$key];
			}
			$imageName = '';
			if(!empty($itemImages['name'][$key]))
			{
				$imageName = $this->Common->uploadTechPurchaseImage($itemImages['name'][$key],$itemImages['tmp_name'][$key]);
			}
			$purchaseDate = date("Y-m-d", strtotime($data['purchaseDate'][$key]));
			$query = "INSERT INTO ace_rp_tech_purchase_item (tech_id,item_name,quantity,price,paid_by,purchase_date,image,status,invoice_num) VALUES (".$techid.",'".$value."',".$data['itemQuantity'][$key].",".$data['itemPrice'][$key].",'".$data['itemPaidBy'][$key]."','".$purchaseDate."','".$imageName."',".$status.",'".$data['invoiceNum'][$key]."')";

			$res = $db->_execute($query);
		}
		if($res)
		{
			$response  = array("res" => "1");
           	echo json_encode($response);	
		}
		exit();
	}

	function getTechPurchaseItems()
	{	
		$this->layout="blank";  
		$techid = $_GET['ftechid'];
		$to = date("Y-m-d",strtotime($_GET['to']));
		$from = date("Y-m-d",strtotime($_GET['from']));
		$status = $_GET['status'];
		$itemInvoiceNum = $_GET['itemInvoiceNum'];
		$condition = "";
		$this->set("loggedUserIsTech",0);
		if (($_SESSION['user']['role_id'] == 1) ) { // TECHNICIAN=1
			//show data only for current technician
			$this->set("loggedUserIsTech",1);

		}
		if($status > 0){
			$condition = "and status=".$status;
		}

		if(!empty($itemInvoiceNum)){
			$condition .= "and invoice_num='".$itemInvoiceNum."'";
		}

		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		$query = "SELECT * FROM ace_rp_tech_purchase_item where tech_id = ".$techid." and purchase_date BETWEEN '$from' and '$to' $condition";

		$res = $db->_execute($query);
		$items = array();
		while ($row = mysql_fetch_array($res)) {
			
			$items[] = $row;
		}

		$this->set('items',$items);
	}

	function sendTechPurchaseInvoice()
	{
		$pst_amount = 0;
		$subtotal_amount = 0;
		$gst_amount = 0;
		$total_amount =0;
		$techId = $_POST['techId'];
		$to_email =  $_POST['to_email'];
		$ffromdate = date("Y-m-d", strtotime($_POST['ffromdate']));
		$ftodate = date("Y-m-d", strtotime($_POST['ftodate']));
		$status =  $_POST['status'];
		$itemInvoiceNum = $_POST['invoiceNum'];
		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		$this->User->id = $techId;
		$tech_details = $this->User->read();	
		$settings = $this->Setting->find(array('title'=>'tech_purchase_invoice'));
        $template = $settings['Setting']['valuetxt'];
        $subject = $settings['Setting']['subject'];
        if($status > 0){
			$condition = "  status=".$status;
		}

		if(!empty($itemInvoiceNum)){
			$condition .= " and invoice_num='".$itemInvoiceNum."'";
		}
        $msg = $template;
        $msg = str_replace('{company_name}',$tech_details['User']['company_name'] , $msg);
        $msg = str_replace('{tech_name}', $tech_details['User']['first_name'].' '.$tech_details['User']['last_name'], $msg);
        $msg = str_replace('{Purchased_by}', $tech_details['User']['first_name'].' '.$tech_details['User']['last_name'], $msg);
        $msg = str_replace('{address}',$tech_details['User']['address'] , $msg);
        $msg = str_replace('{city}',$tech_details['User']['city'] , $msg);
        $msg = str_replace('{post_code}',$tech_details['User']['postal_code'] , $msg);
        $msg = str_replace('{phone_number}',$tech_details['User']['phone'], $msg);
        $msg = str_replace('{email}',$tech_details['User']['email'] , $msg);
        $msg = str_replace('{isssue_date}', date("d/m/Y"), $msg);
        $msg = str_replace('{invoice_num}',($tech_details['User']['invoice_num'] + 1) , $msg);
        // $msg = str_replace('{from}', date("d/m/Y",strtotime($ffromdate)), $msg);
        // $msg = str_replace('{to}', date("d/m/Y",strtotime($ftodate)), $msg);
        

        $query = "SELECT * FROM ace_rp_tech_purchase_item where tech_id = ".$techId." and purchase_date BETWEEN '$ffromdate' and '$ftodate'".$condition;

		$res = $db->_execute($query);
		$itemRows = '';
		$grandTotal = 0;
		while ($row = mysql_fetch_array($res)) {
			$pst_amount = 0;
			$subtotal_amount = 0;
			$gst_amount = 0;
			$total_amount =0;

			$subtotal_amount = number_format ($row['price'] *$row['quantity'],2);
			$gst_amount = number_format ((($subtotal_amount * 5)/100),2);
			$pst_amount = number_format ((($subtotal_amount * 7)/100),2);
			$total_amount = number_format ($subtotal_amount + $gst_amount + $pst_amount,2);
			$grandTotal = number_format ($grandTotal + $total_amount,2);
			$itemRows .= '<tr>';	
			$itemRows .= '<td style="border:1px solid black; text-align: center;">'.$row['item_name'].'</td>';	
			$itemRows .= '<td style="border:1px solid black; text-align: center;">'.$row['quantity'].'</td>';	
			$itemRows .= '<td style="border:1px solid black; text-align: center;">'.$row['price'].'</td>';	
			$itemRows .= '<td style="border:1px solid black; text-align: center;">'.$subtotal_amount.'</td>';	
			$itemRows .= '<td style="border:1px solid black; text-align: center;">'.$gst_amount.'</td>';	
			$itemRows .= '<td style="border:1px solid black; text-align: center;">'.$pst_amount.'</td>';	
			$itemRows .= '<td style="border:1px solid black; text-align: center;">'.$total_amount.'</td>';	
			$itemRows .= '</tr>';	

		}  
		
		$msg = str_replace('{items_data}',$itemRows, $msg);
		$msg = str_replace('{total_amount}',$grandTotal, $msg);

		$msgBody = '<p>Hi,</p>
                                <p>Please find the attached Invoice.</p>
                                <p>&nbsp;</p>
                                <p>Thank you,</p>
                                <p>Pro Ace Heating &amp; Air Conditioning Ltd<br />Tel: 604-293-3770<br />&nbsp;<a href="https://www.acecare.ca"><img src="https://www.acecare.ca/wp-content/uploads/2018/08/newacelogooptimized.png" alt="" width="461" height="81" /></a></p>
                                <p>&nbsp;</p>
                                <p>&nbsp;</p>';
		$result = $this->Mpdf->createPdf($msg);
        $orgFile = $this->G_URL."/acesys/app/webroot/tech-invoice/".$result;
    	// $orgFile = $this->G_URL."/acesys/app/webroot/contract/".$res;

        $res = $this->Common->sendEmailMailgun($to_email,$subject,$msgBody,null,$orgFile);
        
        if (strpos($res, '@acecare') !== false) 
        {
        	$db->_execute("UPDATE ace_rp_users set invoice_num = invoice_num+1 WHERE id=".$techId);
         	$response  = array("res" => "1");
            echo json_encode($response);   
        } else 
        {
           $response  = array("res" => "2");
           echo json_encode($response);
        }
        exit();
	}

	function updateTechPurchaseItem()
	{
		// echo  "<pre>";
		// print_r($_POST); die;
		$data = $_POST;
		$itemIds = $_POST['itemId'];
		$itemImages = $_FILES['sortpic1'];
		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);
		$techid = $data['ftechid'];
		foreach ($itemIds as $key => $value) {
			
			if(!empty($data['itemName'][$key])){			
				$purchaseDate = date("Y-m-d", strtotime($data['purchaseDate'][$key]));
				
				$res = $db->_execute("UPDATE ace_rp_tech_purchase_item set item_name = '".$data['itemName'][$key]."', quantity=".$data['itemQuantity'][$key].", price=".$data['itemPrice'][$key].",paid_by='".$data['itemPaidBy'][$key]."', purchase_date = '".$purchaseDate."',status = ".$data['itemStatus'][$key].", invoice_num = '".$data['invoiceNum'][$key]."' where id=".$key);
			}
		}
		if($res)
		{
			$response  = array("res" => "1");
           	echo json_encode($response);	
		}
		exit();

	}

	function deleteItemImage()
	{	$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);
		$id = $_GET['id'];
		$imageName = $_GET['imageName'];
		$filename = ROOT.'/app/webroot/tech-purchase-image/'.$imageName;
        $query = "UPDATE ace_rp_tech_purchase_item set image = '' where id =".$id."";
        $result = $db->_execute($query);
        if (file_exists($filename)) 
        {
            unlink($filename);
        } 

        if($result)	
        {
        	$response  = array("res" => "1");
           	echo json_encode($response);
        }

        exit();
	}

	function printTechItemInvoice()
	{
		$pst_amount = 0;
		$subtotal_amount = 0;
		$gst_amount = 0;
		$total_amount =0;
		$techId = $_POST['techId'];
		$to_email =  $_POST['to_email'];
		$status =  $_POST['status'];
		$itemInvoiceNum = $_POST['itemInvoiceNum'];
		$ffromdate = date("Y-m-d", strtotime($_POST['ffromdate']));
		$ftodate = date("Y-m-d", strtotime($_POST['ftodate']));
		$db =& ConnectionManager::getDataSource($this->Commission->useDbConfig);

		if($status > 0){
			$condition = " and status=".$status;
		}
		if(!empty($itemInvoiceNum)){
			$condition .= " and invoice_num='".$itemInvoiceNum."'";
		}
		$this->User->id = $techId;
		$tech_details = $this->User->read();	
		$settings = $this->Setting->find(array('title'=>'tech_purchase_invoice'));
        $template = $settings['Setting']['valuetxt'];
        $subject = $settings['Setting']['subject'];

        $msg = $template;
        $msg = str_replace('{company_name}',$tech_details['User']['company_name'] , $msg);
        $msg = str_replace('{tech_name}', $tech_details['User']['first_name'].' '.$tech_details['User']['last_name'], $msg);
        $msg = str_replace('{Purchased_by}', $tech_details['User']['first_name'].' '.$tech_details['User']['last_name'], $msg);
        $msg = str_replace('{address}',$tech_details['User']['address'] , $msg);
        $msg = str_replace('{city}',$tech_details['User']['city'] , $msg);
        $msg = str_replace('{post_code}',$tech_details['User']['postal_code'] , $msg);
        $msg = str_replace('{phone_number}',$tech_details['User']['phone'], $msg);
        $msg = str_replace('{email}',$tech_details['User']['email'] , $msg);
        $msg = str_replace('{isssue_date}', date("d/m/Y"), $msg);
        $msg = str_replace('{invoice_num}',($tech_details['User']['invoice_num'] + 1) , $msg);
        // $msg = str_replace('{from}', date("d/m/Y",strtotime($ffromdate)), $msg);
        // $msg = str_replace('{to}', date("d/m/Y",strtotime($ftodate)), $msg);
        

        $query = "SELECT * FROM ace_rp_tech_purchase_item where tech_id = ".$techId." and purchase_date BETWEEN '$ffromdate' and '$ftodate'".$condition;
		$res = $db->_execute($query);
		$itemRows = '';
		$grandTotal = 0;
		while ($row = mysql_fetch_array($res)) {
			$pst_amount = 0;
			$subtotal_amount = 0;
			$gst_amount = 0;
			$total_amount =0;

			$subtotal_amount = number_format ($row['price'] *$row['quantity'],2);
			$gst_amount = number_format ((($subtotal_amount * 5)/100),2);
			$pst_amount = number_format ((($subtotal_amount * 7)/100),2);
			$total_amount = number_format ($subtotal_amount + $gst_amount + $pst_amount,2);
			$grandTotal = number_format ($grandTotal + $total_amount,2);
			$itemRows .= '<tr>';	
			$itemRows .= '<td style="border:1px solid black; text-align: center;">'.$row['item_name'].'</td>';	
			$itemRows .= '<td style="border:1px solid black; text-align: center;">'.$row['quantity'].'</td>';	
			$itemRows .= '<td style="border:1px solid black; text-align: center;">'.$row['price'].'</td>';	
			$itemRows .= '<td style="border:1px solid black; text-align: center;">'.$subtotal_amount.'</td>';	
			$itemRows .= '<td style="border:1px solid black; text-align: center;">'.$gst_amount.'</td>';	
			$itemRows .= '<td style="border:1px solid black; text-align: center;">'.$pst_amount.'</td>';	
			$itemRows .= '<td style="border:1px solid black; text-align: center;">'.$total_amount.'</td>';	
			$itemRows .= '</tr>';	

		}  
		
		$msg = str_replace('{items_data}',$itemRows, $msg);
		$msg = str_replace('{total_amount}',$grandTotal, $msg);

		echo $msg;
		exit();
	}
}

?>

