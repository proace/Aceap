<?
error_reporting(1);

class CustomersController extends AppController
{
	//To avoid possible PHP4 problems
	var $name = "CustomersController";

	var $uses = array('Customer', 'Order', 'Setting','User');

	var $helpers = array('Time','Ajax','Common');
	var $components = array('HtmlAssist', 'RequestHandler','Common','Lists');

	var $itemsToShow = 40;
	var $pagesToDisplay = 20;
	//var $beforeFilter = array('checkAccess');


// !!!! Below are old functions from users sontroller !!!!

	function check_email($email) {
		if( (preg_match('/(@.*@)|(\.\.)|(@\.)|(\.@)|(^\.)/', $email)) ||
			(preg_match('/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,3}|[0-9]{1,3})(\]?)$/',$email)) ) {
			
			return true;
		}
		else{
			return false;
		}
	}

	// Method draws the list of clients to be chosen
	// Generates a call for a javascript function
	// 'addItem' from the local context when the row is selected.
	// Created: Anthony Chernikov, 08/13/2010
	function _GetClients($field, $value)
	{
		$bg = 'background:#EEFFEE;';
		$txt = 'color:#000000;';
        
		//Select all items related to the current job type
		$db =& ConnectionManager::getDataSource('default');
		$conditions = ' where i.is_active=1 ';
		if ($value)
			if ($field == 'name')
				$conditions .= ' and concat(upper(first_name),\' \',upper(last_name)) like \'%'.$value.'%\'';
			elseif ($field == 'address')
				$conditions .= ' and upper(address) like \'%'.$value.'%\'';
			elseif ($field == 'ref')
				$conditions .= ' and exists (select * from ace_rp_orders o where o.customer_id=i.id and order_number=\''.$value.'\')';
			elseif ($field == 'phone')
			{
				$value = preg_replace("/[- \.]/", "", $value);
				$value = preg_replace("/([?])*/", "[-]*", $value);
				$conditions .= ' and phone REGEXP \''.$value.'\'';
			}
		
		$query = "select i.id, i.first_name, i.last_name, i.address, i.city,
										 i.phone, i.cell_phone, i.postal_code, i.email
								from ace_rp_customers i 
								".$conditions."
							 order by last_name asc, first_name asc limit 20";

		$result = $db->_execute($query);
		while ($row = mysql_fetch_array($result,MYSQL_ASSOC))
		{					
				$h .= '<tr class="item_row" id="item_'.$row['id'].'" style="cursor:pointer;"';
				$h .= 'onclick="addItem('.$row['id'].',\''.$row['first_name'].'\',\''.$row['last_name'].'\',\''.$row['address'].'\'';
				$h .= ',\''.$row['city'].'\',\''.$row['phone'].'\',\''.$row['cell_phone'].'\',\''.$row['postal_code'].'\',\''.$row['email'].'\')"';
				$h .= 'onMouseOver="highlightCurRow(\'item_'.$row['id'].'\')">';
				$h .= '<td class="item_address_td" style="display:none">&nbsp;'.strtoupper($row['address']).'</td>';
				if ($this->Common->getLoggedUserRoleID() != 1)
				{
					$h .= '<td style='.$txt.'>&nbsp;'.$row['phone'].'</td>';
					$h .= '<td style='.$txt.'>&nbsp;'.$row['cell_phone'].'</td>';
				}
				$h .= '<td style='.$txt.'>&nbsp;'.$row['first_name'].'</td>';
				$h .= '<td style='.$txt.'>&nbsp;'.$row['last_name'].'</td>';
				$h .= '<td style='.$txt.'>&nbsp;'.$row['address'].'</td>';
				$h .= '<td style='.$txt.'>&nbsp;'.$row['city'].'</td>';
				$h .= '</tr>';
		}
		
		return $h;
	}

  // Method draws a wrap for the list of clients
	// Created: Anthony Chernikov, 08/13/2010
	function _ShowClients()
	{
		$bg = 'background:#EEFFEE;';
		$txt = 'color:#000000;';

    $h = '
			<style type="text/css" media="all">
		   @import "'.ROOT_URL.'/app/webroot/css/style.css";
			</style>
			<script language="JavaScript" src="'.ROOT_URL.'/app/webroot/js/jquery.js"></script>
      <script language="JavaScript">
				function SearchFilter(element, search_type){
				  if (element.value.length>0){
						var val = element.value.toUpperCase();
						$("#Working").show();
						$.get("'.BASE_URL.'/users/getClients",{value:val,field:search_type},
									function(data){$("#Working").hide();$("#body_target").html(data);});
					}
				}
				function addItem(item_id, item_first_name, item_last_name, item_address, item_city,
										 item_phone, item_cell_phone, item_postal_code, item_email)
				{
				  var new_item=new Array();
					new_item[0]=item_id;
					new_item[1]=item_first_name;
					new_item[2]=item_last_name;
					new_item[3]=item_address;
					new_item[4]=item_city;
					new_item[5]=item_phone;
					new_item[6]=item_cell_phone;
					new_item[7]=item_postal_code;
					new_item[8]=item_email;
					window.returnValue=new_item;
					window.close();
				}
				function highlightCurRow(element){
					$(".item_row").css("background","");
					$("#"+element).css("background","#a9f5fe");
				}
				$(document).ready(function(){$("#ItemSearchString").focus();});
      </script>
			<table>';
		if ($this->Common->getLoggedUserRoleID() != 1)
		{
			$h .= ' <tr>
								<td><b>Phone/Cell:</b></td>
								<td><input style="width:150px" type="text" id="ItemSearchString" onchange="SearchFilter(this,\'phone\')"/></td>
								<td><input type="button" value="GO"/></td>
							</tr>';
		}	
$h .= ' <tr>
					<td><b>Name:</b></td>
					<td><input style="width:150px" type="text" id="ItemSearchString" onchange="SearchFilter(this,\'name\')"/></td>
					<td><input type="button" value="GO"/></td>
				</tr>
				<tr>
					<td><b>Address:</b></td>
					<td><input style="width:150px" type="text" id="ItemSearchString" onchange="SearchFilter(this,\'address\')"/></td>
					<td><input type="button" value="GO"/></td>
				</tr>
				<tr>
					<td><b>REF #:</b></td>
					<td><input style="width:50px" type="text" id="ItemSearchString" onchange="SearchFilter(this,\'ref\')"/></td>
					<td><input type="button" value="GO"/></td>
				</tr>
			</table>
			<img id="Working" style="display:none" src="'.ROOT_URL.'/app/webroot/img/wait30trans.gif"/>';
        
		//Select all items related to the current job type
		$db =& ConnectionManager::getDataSource('default');
		$h .= '<table style="'.$bg.'" cellspacing=0 colspacing=0>';
		$h .= '<tr>';
		if ($this->Common->getLoggedUserRoleID() != 1)
		{
			$h .= ' <th width="100px" style="'.$bg.'">Phone</th>';
			$h .= ' <th width="100px" style="'.$bg.'">Cell</th>';
		}
		$h .= ' <th width="100px" style="'.$bg.'">First Name</th>
						<th width="100px" style="'.$bg.'">Last Name</th
						<th width="150px" style="'.$bg.'">Address</th
						<th width="60px" style="'.$bg.'">City</th
					 </tr><tbody id="body_target"></tbody>';

		$h .= '</table>';
		
		return $h;
	}
	
  // AJAX methods for the list of all the clients
	// Created: Anthony Chernikov, 08/13/2010
	function getClients()
	{
		$field = $_GET['field'];
		$value = $_GET['value'];
		echo $this->_GetClients($field, $value);
		exit;
	}
	
	function showClients()
	{
		echo $this->_ShowClients();
		exit;
	}
	
	function GetList()
	{
		$aRet = array();		
		$sql = "select * from ace_rp_customers where phone like '%12345%'";
		$db =& ConnectionManager::getDataSource('default');
		$result = $db->_execute($sql);
		while ($aRet[] = mysql_fetch_array($result,MYSQL_ASSOC));

		return $aRet;
	}
	
	function testT()
	{
		$aRows = &$this->GetList();
		$sTempl = file_get_contents("test.html");
		foreach ($aRows as $row)
		{
			eval("\$sRes=\"$sTempl\";");
			echo $sRes;
		}
		exit;
	}

	// Export customers to csv file (only those customers that have jobs linked to them)
	// Created: Maxim Kudryavtsev, 29/01/2013
	function exportCustomers()
	{
		$userID = (integer)$_SESSION['user']['id'];
		if (($userID != 44851) && ($userID != 230964))
			die('Sorry, you have no acces to this page!');
		
		$user_fields = array(
			'id' => 'ID',
			'first_name' => 'First Name',
			'last_name' => 'Last Name',
			'city' => 'City',
			'address' => 'Address',
			'postal_code' => 'Postal Code',
			'phone' => 'Phone',
			'cell_phone' => 'Cell Phone',
			'callback_note' => 'Callback Note',
			'email' => 'Email',
			'call_result_id' => 'Call Result'
		);
		$job_fields = array(
			'job_date' => 'Job Date',
			'job_time_beg' => 'Job Time Start',
			'job_time_end' => 'Job Time End',
			'fact_job_beg' => 'Actual Job Start ',
			'fact_job_end' => 'Actual Job End',
			'order_type_id' => 'Order Type',
			'order_status_id' => 'Order Status',
			'order_substatus_id' => 'Order Substatus',
			'feedback_comment' => 'Feedback Comment',
			'feedback_suggestion' => 'Feedback Suggestion',
			'office_comment' => 'Office Comment',
			'telem_comment' => 'Telemarketer Comment',
			);
		$id_fields=array('order_type_id','order_status_id','order_substatus_id','call_result_id');
		//var_dump($_POST);
		if (isset($_POST['user_fields'])) {
			$query='select ';
			$where='';
			$delimiter=(!empty($_POST['delimiter']) ? $_POST['delimiter'] : ',');
			if (isset($_POST['dnc'])) {
				if ($_POST['dnc']=='dnc') $where=' and u.`callresult`=3 ';
				else
				if ($_POST['dnc']=='notdnc') $where=' and u.`callresult`!=3 ';
			}
			if (isset($_POST['fromdate']) && $d=strtotime($_POST['fromdate']))
				$fromdate=date('Y-m-d',$d);
			else 
				$fromdate='2007-12-31';

			if (isset($_POST['todate']) && $d=strtotime($_POST['todate']))
				$todate=date('Y-m-d',$d);
			else
				$todate=date('Y-m-d');

			$from='from 
				(select MAX(id) id from `ace_rp_orders` d where d.order_status_id>0 and d.`job_date`>=\''.$fromdate.'\' and d.`job_date`<=\''.$todate.'\' group by d.customer_id) a
				left join `ace_rp_orders` o on o.`id`=a.id
				left join ace_rp_customers u on o.`customer_id`=u.id ';

			$fields_csv=array();

			foreach ($_POST['user_fields'] as $f) {
				if (isset($user_fields[$f]) && !in_array($f,$id_fields)) {
					$query.='u.`'.$f.'`, ';
					$fields_csv[]=$user_fields[$f];
				}
			}
			foreach ($_POST['job_fields'] as $f) {
				if (isset($job_fields[$f]) && !in_array($f,$id_fields)) {
					$query.='o.`'.$f.'`, ';
					$fields_csv[]=$job_fields[$f];
				}
			}
			if (in_array('order_type_id', $_POST['job_fields'])) {
				$query.='ot.`name` as order_type, ';
				$from.='left join `ace_rp_order_types` ot on ot.`id`=o.`order_type_id` ';
				$fields_csv[]='Order Type';
			}
			if (in_array('order_status_id', $_POST['job_fields'])) {
				$query.='os.`name` as order_status, ';
				$from.='left join `ace_rp_order_statuses` os on os.`id`=o.`order_status_id` ';
				$fields_csv[]='Order Status';
			}
			if (in_array('order_substatus_id', $_POST['job_fields'])) {
				$query.='oss.`name` as order_substatus, ';
				$from.='left join `ace_rp_order_substatuses` oss on oss.`id`=o.`order_substatus_id` ';
				$fields_csv[]='Order Substatus';
			}
			if (in_array('call_result_id', $_POST['user_fields'])) {
				$query.='cr.`name` as call_result, ';
				$from.='left join `ace_rp_call_results` cr on cr.`id`=u.`callresult` ';
				$fields_csv[]='Call Result';
			}

			$query=substr($query,0,-2).' '.$from.' where u.`is_active`=1 '.$where.' group by u.`city`,u.`address` order by o.`job_date` desc'; // grouping by u.`city` and u.`address` because of many duplicates, and some people have more than 1 house and the only difference in their records is address
			//var_dump($query);
			//die;
			$db =& ConnectionManager::getDataSource('default');

			$result = $db->_execute($query);
			$csv='';
			while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
				$flds=array();
				foreach ($row as $k=>$v) {
					if ( (strpos('"',$v)!==false) || (strpos(';',$v)!==false) )
						$flds[]='"'.str_replace(array('"',$delimiter),array('""','"'.$delimiter.'"'),$v).'"';
					else
						$flds[]=$v;
				}
				$csv.='"'.implode('"'.$delimiter.'"',$flds).'"'."\r\n";
			}
			if ($csv!='') {
				$csv='"'.implode('"'.$delimiter.'"',$fields_csv).'"'."\r\n".$csv;
				$l=strlen($csv);
				header('content-disposition: attachment; filename="customers.csv"');
				header('content-length: '.$l);
				header('content-type: application/vnd.ms-excel, text/plain');
				echo $csv;
				die;
			}
			else {
				die('There is no data matching your request!'); 
			}
		}
		$this->set('user_fields', $user_fields);
		$this->set('job_fields', $job_fields);
	}

	// Search customers by params
	// Created: Maxim Kudryavtsev, 31/01/2013
	function search($sort_field='name', $sort_order='asc', $page_number=1) {
		//var_dump($_POST,$_SESSION['users_search_fields']);
		/*$userID = (integer)$_SESSION['user']['id'];
		if (($userID != 44851) && ($userID != 230964))
			die('Sorry, you have no acces to this page!');*/

		if (!is_numeric($page_number) || $page_number<1) {
			$page_number=1;
		}

		$db =& ConnectionManager::getDataSource('default');

		$orderby='i.`last_name` '.addslashes($sort_order).', i.`first_name` '.addslashes($sort_order);
		if ($sort_field=='tech_name') {
			$orderby='u.`last_name` '.addslashes($sort_order).', u.`first_name` '.addslashes($sort_order);
		}
		else
		if ($sort_field!='name')
			$orderby='i.`'.addslashes($sort_field).'` '.addslashes($sort_order);

		if ( !isset($_POST['onlyactive']) ) {
			$onlyactive=$_POST['onlyactive']=0;
		}

		$session_fields=array('search_field','search_value','onlyactive','nsfrom','nsto','exfrom','exto','ppage');
		foreach ($session_fields as $f) {
			if (isset($_POST[$f])) {
				$_SESSION['users_search_fields'][$f]=$_POST[$f];
			}
			elseif (isset($_SESSION['users_search_fields'][$f])) {
				$_POST[$f]=$_SESSION['users_search_fields'][$f];
			}
		}

		$where='';
		if (!empty($_POST['search_field']) && !empty($_POST['search_value'])) {
			if ($_POST['search_field']=='phone') {
				$val=preg_replace('/[^0-9]+/','',$_POST['search_value']);
				$where.=' and (i.`phone` like "'.$val.'%" or i.`cell_phone` like "'.$val.'%")';
			}
			elseif ($_POST['search_field']=='card_number') {
				$val=preg_replace('/[^0-9]+/','',$_POST['search_value']);
				$where.=' and replace(i.`'.addslashes($_POST['search_field']).'`,\'-\',\'\') like "'.$val.'%"';
			}
			else {
				$where.=' and i.`'.addslashes($_POST['search_field']).'` like "'.addslashes($_POST['search_value']).'%"';
			}
			$this->set('search_field', $_POST['search_field']);
			$this->set('search_value', $_POST['search_value']);
		}

		if (isset($_POST['onlyactive']) && $_POST['onlyactive']==1) {
			$where.=' and i.is_active=1 ';
			$onlyactive=1;
		}

		if ( !empty($_POST['nsfrom']) && $d=strtotime($_POST['nsfrom']) ) {
			$where.=' and i.`next_service` >= "'.date('Y-m-d',$d).'"';
			$this->set('nsfrom', $_POST['nsfrom']);
		}
		if ( !empty($_POST['nsto']) && $d=strtotime($_POST['nsto']) ) {
			$where.=' and i.`next_service` <= "'.date('Y-m-d',$d).'"';
			$this->set('nsto', $_POST['nsto']);
		}

		if ( !empty($_POST['exfrom']) && $d=strtotime($_POST['exfrom']) ) {
			$where.=' and i.`card_exp` >= "'.date('Y-m-d',$d).'"';
			$this->set('exfrom', $_POST['exfrom']);
		}
		if ( !empty($_POST['exto']) && $d=strtotime($_POST['exto']) ) {
			$where.=' and i.`card_exp` <= "'.date('Y-m-d',$d).'"';
			$this->set('exto', $_POST['exto']);
		}

		if (isset($_POST['ppage']) && intval($_POST['ppage'])>0) {
			$ppage=intval($_POST['ppage']);
		}
		else
			$ppage=20;

		$db =& ConnectionManager::getDataSource('default');

		// Prepare Active Members Count
		$r = $db->_execute('select count(*) from ace_rp_customers i 
					where card_number!="" and i.`card_exp`>NOW()');
		$this->set('active_members_count', @mysql_result($r,0,0));

		// Prepare Expired Members Count
		$r = $db->_execute('select count(*) from ace_rp_customers i 
					where card_number!="" and i.`card_exp`<NOW()');
		$this->set('exp_members_count', @mysql_result($r,0,0));

		// Prepare Cities
		$r = $db->_execute('select distinct(i.`city`) as c from ace_rp_customers i 
					where card_number!="" order by i.`city`');
		$cities=array();
		while ($row = mysql_fetch_array($r,MYSQL_ASSOC)) {
			$cities[]=$row['c'];
		}
		$this->set('cities', $cities);

		$query = 'from ace_rp_customers i
					left join `ace_rp_order_items` oi on oi.`name`=i.card_number
					left join `ace_rp_orders` o on o.id=oi.`order_id`
					left join ace_rp_users u on u.`id`=o.`job_technician1_id`
				where i.card_number!="" '.$where.'
				order by '.$orderby;

		$r = $db->_execute('select count(*) '.$query);
		$rows_count=@mysql_result($r,0,0);
		$pages_count=ceil($rows_count/$ppage);
		if ($page_number>1 && $page_number>$pages_count) $page_number=$pages_count;
		$limit=$ppage*($page_number-1).','.$ppage;

		$query='select i.id, i.first_name, i.last_name, i.email, i.phone, i.cell_phone, i.card_number, i.card_exp, i.next_service, i.address, i.city, i.callback_date, u.`first_name` as tname, u.`last_name` as tlname '.$query.' limit '.$limit;
		$result = $db->_execute($query);
		$items=array();
		while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
			$items[]=$row;
		}

		$this->set('items', $items);
		$this->set('sort_field', $sort_field);
		$this->set('sort_order', $sort_order);
		$this->set('ppage', $ppage);
		$this->set('pages_count', $pages_count);
		$this->set('page_number', $page_number);
		$this->set('rows_count', $rows_count);

		$this->set('onlyactive', $onlyactive);
	}

	// Search customers by params and calculates optimal route (by postal codes distancies)
	// Created: Maxim Kudryavtsev, 12/02/2013
	function distances($page_number=1) {
		//var_dump($_POST,$_SESSION['users_search_fields']);
		/*$userID = (integer)$_SESSION['user']['id'];
		if (($userID != 44851) && ($userID != 230964))
			die('Sorry, you have no acces to this page!');*/

		$session_fields=array('city','postal_code','from','to','job_type');
		foreach ($session_fields as $f) {
			if (isset($_POST[$f])) {
				$_SESSION['users_distances_fields'][$f]=$_POST[$f];
			}
			elseif (isset($_SESSION['users_distances_fields'][$f])) {
				$_POST[$f]=$_SESSION['users_distances_fields'][$f];
			}
		}

		$where='';
		if (isset($_POST['city']) && $_POST['city']!='') {
			$where.= 'and u.city="'.addslashes($_POST['city']).'"';
		}
		if (isset($_POST['postal_code']) && $_POST['postal_code']!='') {
			$where.= ' and u.postal_code like "'.addslashes($_POST['postal_code']).'%"';
		}

		$db =& ConnectionManager::getDataSource('default');

		$orderby='u.postal_code, u.`last_name`, u.`first_name` ';

		$where_job='';

		if ( !empty($_POST['from']) && $d=strtotime($_POST['from']) ) {
			$where_job.=' and d.`job_date` >= "'.date('Y-m-d',$d).'"';
			$this->set('from', $_POST['from']);
		}
		if ( !empty($_POST['to']) && $d=strtotime($_POST['to']) ) {
			$where_job.=' and d.`job_date` <= "'.date('Y-m-d',$d).'"';
			$this->set('to', $_POST['to']);
		}
		if ( !empty($_POST['job_type']) &&  !empty($_POST['job_type']) ) {
			$where_job.=' and d.`order_type_id` = "'.intval($_POST['job_type']).'"';
			$this->set('job_type', $_POST['job_type']);
		}

		$db =& ConnectionManager::getDataSource('default');

		// Prepare Cities
		$r = $db->_execute('select DISTINCT(u.city) as c from ace_rp_customers u order by u.city');
		/*$r = $db->_execute('select DISTINCT(u.city) as c from 
						(select MAX(id) id from `ace_rp_orders` d where d.order_status_id>0 group by d.customer_id) a
						left join `ace_rp_orders` o on o.`id`=a.id
						left join ace_rp_users u on o.`customer_id`=u.id
						left join `ace_rp_order_types` ot on ot.`id`=o.`order_type_id`');*/
		$cities=array();
		while ($row = mysql_fetch_array($r,MYSQL_ASSOC)) {
			$cities[]=$row['c'];
		}
		$this->set('cities', $cities);

		// Prepare Job types
		$r = $db->_execute('select id, name from `ace_rp_order_types` t where t.`flagactive`=1 order by t.`id`');
		$job_types=array();
		while ($row = mysql_fetch_array($r,MYSQL_ASSOC)) {
			$job_types[]=$row;
		}
		$this->set('job_types', $job_types);

		if (isset($_POST['submit2'])) {
			// cleaning postal code
			$result = $db->_execute('select u.id, u.`postal_code` from `ace_rp_customers` u where u.`postal_code` not REGEXP "^[a-z0-9]*$"');
			while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
				$new_pc=preg_replace('/[^A-z0-9]/','',str_replace('\\','',$row['postal_code']));
				if ($new_pc==$row['postal_code'])$new_pc='';
				//echo $row['postal_code'].' -> '.$new_pc.'<br />';
				$db->_execute('update `ace_rp_customers` u set u.`postal_code` = "'.$new_pc.'" where u.id='.$row['id']);
			}
			// /cleaning postal code

			$query = 'select 
						u.`address`, u.id, u.`first_name`, u.`last_name`, u.`city`, u.`postal_code`, u.`phone`, u.`cell_phone`, o.`job_date`, o.`order_type_id`, ot.`name`
					from 
						(select MAX(id) id from `ace_rp_orders` d where d.order_status_id>0 '.$where_job.' group by d.customer_id) a
						left join `ace_rp_orders` o on o.`id`=a.id
						left join ace_rp_customers u on o.`customer_id`=u.id
						left join `ace_rp_order_types` ot on ot.`id`=o.`order_type_id`
					where u.`is_active`=1 '.$where.' group by u.`address` order by '.$orderby;
	
			/*$r = $db->_execute($query);
			/*$rows_count=@mysql_result($r,0,0);
			$pages_count=ceil($rows_count/$ppage);
			if ($page_number>1 && $page_number>$pages_count) $page_number=$pages_count;
			$limit=$ppage*($page_number-1).','.$ppage;
	
			$query='select i.id, i.first_name, i.last_name, i.email, i.phone, i.cell_phone, i.card_number, i.card_exp, i.next_service, i.address, i.city, i.callback_date, u.`first_name` as tname, u.`last_name` as tlname '.$query.' limit '.$limit;*/
			$result = $db->_execute($query);
			$zones=array();
			$nozone=array();
			while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
				//var_dump($row);
				$postal_code=strtolower(trim($row['postal_code']));
				if (preg_match('/^(v[0-9][a-z]).*/',$postal_code,$m)) {
					$pc=$m[1];
	
					if (!isset($zones[$pc]))
						$zones[$pc]=array();
					$zones[$pc][]=$row;
				}
				else {
					$nozone[]=$row;
				}
	
			}
	
			$out=array();
			$next_zone='v5c';
			$dist=0;
			while (count($zones)>0) {
				if (isset($zones[$next_zone])) {
					$zones[$next_zone][0]['distance']=$dist;
					$out=array_merge($out,$zones[$next_zone]);
					//$out[]=array('rows'=>$zones[$next_zone],'distance'=>$dist);
					unset($zones[$next_zone]);
					if (count($zones)==0) break;
				}
				$to=implode('","',array_keys($zones));
				$r = $db->_execute('select * from `ace_rp_postal_distances` d where d.`from`="'.$next_zone.'" and d.`to` in ("'.$to.'") order by meters limit 1');
				if ($row = mysql_fetch_array($r,MYSQL_ASSOC)) {
					//echo '<br>select * from `ace_rp_postal_distances` d where d.`from`="'.$next_zone.'" and d.`to` in ("'.$to.'") order by meters limit 1<br>';
					$next_zone=$row['to'];
					//echo '$next_zone='.$next_zone.'<br>';
					$dist=$row['meters'];
					//echo '$dist='.$dist.'<br>';
				}
				else {
					$r = $db->_execute('select * from `ace_rp_postal_distances` d where d.`to`="'.$next_zone.'" and d.`from` in ("'.$to.'") order by meters limit 1');
					if ($row = mysql_fetch_array($r,MYSQL_ASSOC)) {
						$next_zone=$row['from'];
						$dist=$row['meters'];
					}
					else {
						foreach ($zones as $k=>$v)
							$nozone=array_merge($nozone,$v);
						unset($zones);
						break;
					}
				}
			}
			//var_dump($out);
			if (count($nozone)>0) {
				$nozone[0]['distance']='unknown';
				$out=array_merge($out,$nozone);
			}
			//$out[]=array('rows'=>$nozone,'distance'=>'unknown');
			unset($nozone);
			$_SESSION['users_distances_array']=$out;
			unset($out);
		}

		//$out=&$_SESSION['users_distances_array'];

		if (!is_numeric($page_number) || $page_number<1) {
			$page_number=1;
		}
		$rows_count=count($_SESSION['users_distances_array']);
		$ppage=20;
		$pages_count=ceil($rows_count/$ppage);
		//$last_row=max( ($ppage*$page_number), $rows_count);


		$this->set('items', &$_SESSION['users_distances_array']);
		//unset($out);
		$this->set('city', $_POST['city']);
		$this->set('postal_code', $_POST['postal_code']);
		/*$this->set('sort_order', $sort_order);

		$this->set('onlyactive', $onlyactive);*/
		$this->set('ppage', $ppage);
		$this->set('pages_count', $pages_count);
		$this->set('page_number', $page_number);
		$this->set('rows_count', $rows_count);
	}

	// Created: Maxim Kudryavtsev, 12/02/2013

	function canvassers($page_number=1) {

		//var_dump($_POST,$_SESSION['users_search_fields']);

		/*$userID = (integer)$_SESSION['user']['id'];

		if (($userID != 44851) && ($userID != 230964))

			die('Sorry, you have no acces to this page!');*/

		if ($this->Common->getLoggedUserRoleID()<1)

			die('Sorry, you have no acces to this page!');



		$session_fields=array('city','postal_code','from','to','job_type', 'address_street', 'address_street_number','dnc','nfrom','nto');

		foreach ($session_fields as $f) {

			if (isset($_POST[$f])) {

				$_SESSION['canvassers_fields'][$f]=$_POST[$f];

			}

			elseif (isset($_SESSION['canvassers_fields'][$f])) {

				$_POST[$f]=$_SESSION['canvassers_fields'][$f];

			}

		}



		$where='';

		if (isset($_POST['city']) && $_POST['city']!='') {

			$where.= 'and u.city="'.addslashes($_POST['city']).'"';

		}

		if (isset($_POST['postal_code']) && $_POST['postal_code']!='') {

			$where.= ' and u.postal_code like "'.addslashes($_POST['postal_code']).'%"';

		}

		if (isset($_POST['address_street']) && $_POST['address_street']!='') {

			$where.= ' and u.address_street like "'.addslashes($_POST['address_street']).'%"';

		}

		if (isset($_POST['address_street_number']) && $_POST['address_street_number']!='') {

			$where.= ' and u.address_street_number like "%'.addslashes($_POST['address_street_number']).'%"';

		}



		$db =& ConnectionManager::getDataSource('default');



		$orderby='u.address_street, u.`address_street_number`, u.`address_unit` ';



		//$where_job='';



		if ( !empty($_POST['from']) && $d=strtotime($_POST['from']) ) {

			$where.=' and o.`job_date` >= "'.date('Y-m-d',$d).'"';

			$this->set('from', $_POST['from']);

		}

		if ( !empty($_POST['to']) && $d=strtotime($_POST['to']) ) {

			$where.=' and o.`job_date` <= "'.date('Y-m-d',$d).'"';

			$this->set('to', $_POST['to']);

		}

		if ( !empty($_POST['job_type']) &&  !empty($_POST['job_type']) ) {

			$where.=' and o.`order_type_id` = "'.intval($_POST['job_type']).'"';

			$this->set('job_type', $_POST['job_type']);

		}



		$select='';

		$is_admin=$is_telemerketer=false;

		//echo $this->Common->getLoggedUserRoleID();

		if ($this->Common->getLoggedUserRoleID() == 3) {

			$where.=' and u.callresult!= 3'; // do not show DNCs to telemerketers

			$is_telemerketer=true;

		}

		if ( in_array($this->Common->getLoggedUserRoleID(), array(5,6,11,14) ) ) { // show next event for admins and office users

			$select.=' , if (u.next_service>=now(), concat("Next service date:<br>", u.next_service), 

							if (o.`job_date`>=now(), concat("Next job date:<br>", o.`job_date`), 

							(	SELECT concat( if (h.call_result_id=10, "Come back on", "Call back on"), "<br>", h.callback_date)

								FROM

								  ace_rp_call_history h

								WHERE

								  h.customer_id = u.id

								  AND h.callback_date > now()

								ORDER BY

								  h.callback_date

								LIMIT

								  1) 

							)

						) as next_event';

			if (isset($_POST['dnc'])) {
				
				if ($_POST['dnc']=='both') $where.=' and u.`callresult`!=1 ';

				else 
				
				if ($_POST['dnc']=='dnc') $where.=' and u.`callresult`=3 ';
				
				else 
				
				if ($_POST['dnc']=='crtc') $where.=' and u.`callresult`=99 ';

				else

				if ($_POST['dnc']=='notdnc') $where.=' and u.`callresult`!=3 ';
				
				else

				if ($_POST['dnc']=='booked') $where.=' and u.`callresult`=1 and u.`callresult`!=3 ';
				
				else

				if ($_POST['dnc']=='notbooked') $where.=' and u.`callresult`!=1 and u.`callresult`!=3 and u.`callresult`!=2 and u.`callresult`!=8  and u.`callresult`!=4 and u.`callresult`!=9 and u.`callresult`!=99' ;

				
				else
				// temp code for export of csv list, should be standardized later (By Lovedeep, 17-feb-2015)
				if ($_POST['dnc']=='export')
				{
				$csv_export = '';
				$csv_filename = 'Ace_Customers_'.$db_record.'_'.date('Y-m-d').'.csv';
				// query to get data from database
				$query = mysql_query("SELECT * FROM ace_rp_customers");
				$field = mysql_num_fields($query);
				// create line with field names
				for($i = 0; $i < $field; $i++) {
				$csv_export.= mysql_field_name($query,$i).',';
				}
				// newline (seems to work both on Linux & Windows servers)
				$csv_export.= '
				';
				// loop through database query and fill export variable
				while($row = mysql_fetch_array($query)) {
				// create line with field values
				for($i = 0; $i < $field; $i++) {
				$csv_export.= '"'.$row[mysql_field_name($query,$i)].'",';
				}
				$csv_export.= '
				';
				}
				// Export the data and prompt a csv file for download
				header("Content-type: text/x-csv");
				header("Content-Disposition: attachment; filename=".$csv_filename."");
				echo($csv_export); 
				
				
				}
			}

			$jwhere=$nswhere='';

			if ( !empty($_POST['nfrom']) && $d=strtotime($_POST['nfrom']) ) {

				$d=date('Y-m-d',$d);

				$jwhere.=' and h2.`callback_date` >= "'.$d.'"';

				$nswhere.=' and u.next_service >= "'.$d.'"';

				$this->set('nfrom', $_POST['nfrom']);

			}

			if ( !empty($_POST['nto']) && $d=strtotime($_POST['nto']) ) {

				$d=date('Y-m-d',$d);

				$jwhere.=' and h2.`callback_date` <= "'.$d.'"';

				$nswhere.=' and u.next_service <= "'.$d.'"';

				$this->set('nto', $_POST['nto']);

			}

			if ($jwhere!='')

				$where.=' and ( (1 '.$nswhere.') or

						exists(SELECT h2.call_result_id FROM ace_rp_call_history h2 WHERE h2.customer_id = u.id '.$jwhere.') )';



			$is_admin=true;

		}

		else { // for any other user hide customers who has scheduled job, callback, next service etc. in future

			$where.=' and u.next_service<now() and o.`job_date`<now() and not exists (SELECT h.call_result_id FROM ace_rp_call_history h

								WHERE h.customer_id = u.id AND h.callback_date > now() )';

		}





		$db =& ConnectionManager::getDataSource('default');



		// Prepare Cities

		$r = $db->_execute('select DISTINCT(u.city) as c from ace_rp_customers u order by u.city');

		/*$r = $db->_execute('select DISTINCT(u.city) as c from 

						(select MAX(id) id from `ace_rp_orders` d where d.order_status_id>0 group by d.customer_id) a

						left join `ace_rp_orders` o on o.`id`=a.id

						left join ace_rp_users u on o.`customer_id`=u.id

						left join `ace_rp_order_types` ot on ot.`id`=o.`order_type_id`');*/

		$cities=array();

		while ($row = mysql_fetch_array($r,MYSQL_ASSOC)) {

			$cities[]=$row['c'];

		}

		$this->set('cities', $cities);



		// Prepare Job types

		$r = $db->_execute('select id, name from `ace_rp_order_types` t where t.`flagactive`=1 order by t.`id`');

		$job_types=array();

		while ($row = mysql_fetch_array($r,MYSQL_ASSOC)) {

			$job_types[]=$row;

		}

		$this->set('job_types', $job_types);



		if (isset($_POST['submit2']) || isset($_GET['refresh'])) {

			unset($_SESSION['canvassers_array']);

			// cleaning postal code

			/*$result = $db->_execute('select u.id, u.`postal_code` from `ace_rp_customers` u where u.`postal_code` not REGEXP "^[a-z0-9]*$"');

			while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {

				$new_pc=preg_replace('/[^A-z0-9]/','',str_replace('\\','',$row['postal_code']));

				if ($new_pc==$row['postal_code'])$new_pc='';

				//echo $row['postal_code'].' -> '.$new_pc.'<br />';

				$db->_execute('update `ace_rp_customers` u set u.`postal_code` = "'.$new_pc.'" where u.id='.$row['id']);

			}*/

			// /cleaning postal code



			//echo

			$query = 'SELECT 

							CONCAT(u.address_unit," ",u.address_street_number," ",u.address_street) as address,

							u.`address_unit`,

							u.`address_street_number`,

							u.`address_street`

							 , u.id

							 , u.`first_name`

							 , u.`last_name`

							 , u.`city`

							 , u.`postal_code`

							 , u.`phone`

							 , u.`cell_phone`

							 , o.`job_date`

							 , o.`order_type_id`

							 , ot.`name`

							 , r.name as call_result

							'.$select.'

						FROM

						ace_rp_customers u

						LEFT JOIN `ace_rp_orders` o

						ON o.`id` = u.last_order_id

						LEFT JOIN `ace_rp_order_types` ot

						ON ot.`id` = o.`order_type_id`

						left join ace_rp_call_results r on r.id=u.callresult

						WHERE

							u.`is_active` = 1 '.$where.'

						ORDER BY '.$orderby;



			/*$r = $db->_execute($query);

			/*$rows_count=@mysql_result($r,0,0);

			$pages_count=ceil($rows_count/$ppage);

			if ($page_number>1 && $page_number>$pages_count) $page_number=$pages_count;

			$limit=$ppage*($page_number-1).','.$ppage;

	

			$query='select i.id, i.first_name, i.last_name, i.email, i.phone, i.cell_phone, i.card_number, i.card_exp, i.next_service, i.address, i.city, i.callback_date, u.`first_name` as tname, u.`last_name` as tlname '.$query.' limit '.$limit;*/

			$result = $db->_execute($query);

			$out=array();

			while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {

				$out[]=$row;

			}



			$_SESSION['canvassers_array']=$out;

			unset($out);

		}



		//$out=&$_SESSION['users_distances_array'];



		if (!is_numeric($page_number) || $page_number<1) {

			$page_number=1;

		}

		$rows_count=count($_SESSION['canvassers_array']);

		$ppage=20;

		$pages_count=ceil($rows_count/$ppage);

		//$last_row=max( ($ppage*$page_number), $rows_count);





		$this->set('items', &$_SESSION['canvassers_array']);

		//unset($out);

		$this->set('city', $_POST['city']);

		$this->set('postal_code', $_POST['postal_code']);

		$this->set('address_street', $_POST['address_street']);

		$this->set('address_street_number', $_POST['address_street_number']);

		$this->set('dnc', $_POST['dnc']);

		/*$this->set('sort_order', $sort_order);



		$this->set('onlyactive', $onlyactive);*/

		$this->set('ppage', $ppage);

		$this->set('pages_count', $pages_count);

		$this->set('page_number', $page_number);

		$this->set('rows_count', $rows_count);

		

		$this->set('is_telemerketer', $is_telemerketer);

		$this->set('is_admin', $is_admin);

	}

	function campaingReport()
	{
		$where = '';
		$callResultWhere = '';
		if(!empty($_POST['call_action'])) 
		{
			$callResultWhere.=' AND ch.call_result_id ="'.$_POST['call_action'].'"';
			$this->set('call_action', $_POST['call_action']);
		}
		if(!empty($_POST['city'])) 
		{
			$callResultWhere.= ' AND c.city ="'.$_POST['city'].'"';
			$where.=' AND c.city ="'.$_POST['city'].'"';
			$this->set('city', $_POST['city']);
		}
		if(!empty($_POST['job_type']))
		{
			$where.=' AND o.order_type_id ="'.$_POST['job_type'].'"';
			$this->set('job_type', $_POST['job_type']);
		}
		if(!empty($_POST['from']) && !empty($_POST['to']))
		{

			$to = date('Y-m-d',strtotime($_POST['to']));
			$from = date('Y-m-d',strtotime($_POST['from']));
			$callResultWhere.= ' AND ch.callback_date BETWEEN "'.$from.'" AND "'.$to.'"';
			$where.=' AND o.booking_date BETWEEN "'.$from.'" AND "'.$to.'"';
			$this->set('from', $_POST['from']);
			$this->set('to', $_POST['to']);

		}

		$db =& ConnectionManager::getDataSource('default');
		$r = $db->_execute('select DISTINCT(u.city) as c from ace_rp_customers u order by u.city');
		$cities=array();

		while ($row = mysql_fetch_array($r,MYSQL_ASSOC)) {

			$cities[]=$row['c'];

		}

		$this->set('cities', $cities);

		$r = $db->_execute('select id, name from `ace_rp_order_types` t where t.`flagactive`=1 order by t.`id`');

		$job_types = array();

		while ($row = mysql_fetch_array($r,MYSQL_ASSOC)) {

			$job_types[]=$row;

		}
		$this->set('job_types', $job_types);
		$dataCount = array();
		$total = 0;
		if (isset($_POST['submit2']) || isset($_GET['refresh'])) {
			
				// $query = 'SELECT COUNT( o.order_status_id ) as count , os.name FROM ace_rp_orders o LEFT JOIN ace_rp_order_statuses os ON os.id = o.order_status_id LEFT JOIN ace_rp_customers c ON c.id = o.customer_id where o.order_status_id != ""'.$where.' GROUP BY o.order_status_id';
				$query = 'SELECT COUNT( o.order_status_id ) AS count, os.name FROM ace_rp_orders o INNER JOIN (SELECT customer_id, MAX( id ) AS MaxId
					FROM ace_rp_orders GROUP BY customer_id)topscore ON o.customer_id = topscore.customer_id AND o.id = topscore.MaxId LEFT JOIN ace_rp_order_statuses os ON os.id = o.order_status_id LEFT JOIN ace_rp_customers c ON c.id = o.customer_id WHERE o.order_status_id !=""'.$where.' GROUP BY o.order_status_id';
				$result = $db->_execute($query);
			
				while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
					$total = $total+$row["count"];
					$dataCount[$row["name"]] = $row["count"];
					
				}
				$this->set('dataCount', $dataCount);

			$callResultCount = array();	
					// $query1 = 	'SELECT COUNT(call_result_id) as crd, name FROM (SELECT ch.call_result_id,cr.name FROM `ace_rp_call_history` ch INNER JOIN ace_rp_call_results cr ON cr.id = ch.call_result_id INNER JOIN ace_rp_customers c ON c.id = ch.customer_id WHERE ch.call_result_id != ""'.$callResultWhere.' GROUP By call_result_id,customer_id) as res GROUP BY call_result_id';
				$query1 = 'SELECT COUNT( ch.call_result_id ) AS crd, cr.name FROM ace_rp_call_history ch INNER JOIN (SELECT customer_id, MAX( id ) AS MaxId
					FROM ace_rp_call_history GROUP BY customer_id) topscore ON ch.customer_id =topscore.customer_id AND ch.id = topscore.MaxId INNER JOIN ace_rp_call_results cr ON cr.id = ch.call_result_id INNER JOIN ace_rp_customers c ON c.id = ch.customer_id
						WHERE ch.call_result_id != ""'.$callResultWhere.' GROUP BY call_result_id';
			$result1 = $db->_execute($query1);
			
			while ($row1 = mysql_fetch_array($result1,MYSQL_ASSOC)) {
				$callResultCount[$row1["name"]] = $row1["crd"];	
			}
			$this->set('callResultCount', $callResultCount);
		}
		
	}

	/*	Loki: Check customer exist
		num_type  = 2 (home phone)
		num_type  = 1 (cell phone)
	*/
	
	function checkCustomerExist()
	{
		$db =& ConnectionManager::getDataSource('default');
		$phone_num 		= str_replace('-','', trim($_POST['phoneNum']));
		$number_type 	= $_POST['numType'];
		if($number_type == 1){
			$sqlCond = "cell_phone =".$phone_num;
		} else {
			$sqlCond = "phone =".$phone_num;
		}
		$result = $db->_execute("SELECT id from ace_rp_customers where ".$sqlCond." order by id desc limit 1 ;");
		$row = mysql_fetch_array($result);
		if(!empty($row['id']))
		{
			$response  = array("res" => $row['id']);
            echo json_encode($response);
		} else {
			$response  = array("res" => "0");
            echo json_encode($response);
		}
		exit();

	}

	//Loki: save images 
	function saveImages()
	{
		$customerId = $_POST['data']['Customer']['id'];
		$images = $_FILES['sortpic1'];

		if(!empty($customerId))
		{
			foreach ($images['name'] as $key => $value) {
	                if(!empty($value)){
	                    $imageResult = $this->Common->uploadPhoto($value,$images['tmp_name'][$key] ,0 , $config = $this->User->useDbConfig, 1, $customerId);
	                }
	             }  

	          echo $imageResult;
	          exit();
		}

	}

}
?>
