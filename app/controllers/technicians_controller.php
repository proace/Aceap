<? ob_start();


class TechniciansController extends AppController

{

	//To avoid possible PHP4 problemfss

	var $name = "TechniciansController";



	var $uses = array('Order', 'CallRecord', 'User', 'Customer', 'OrderItem',
                    'Timeslot', 'OrderStatus', 'OrderType', 'Item',
                    'Zone','PaymentMethod','ItemCategory','InventoryLocation',
					'OrderSubstatus','Coupon','Setting','CallResult','Invoice', 'Question', 'Payment', 'Invoice');



	var $helpers = array('Time','Javascript','Common');

	var $components = array('HtmlAssist','RequestHandler','Common','Jpgraph', 'Lists');

	var $itemsToShow = 20;

    var $pagesToDisplay = 10;
    
    function index()
	{
		
		
		$this->layout="list";
        $query = " SELECT * from ace_rp_payment_methods where show_method = 1";
        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
        $result1 = $db->_execute($query);
        while($row = mysql_fetch_array($result1))
        {
            $methods[$row['id']] = $row;
		}
		
		$fromdate = $_REQUEST['fromdate'];
		$orderinput = $_REQUEST['orderinput'];
		$ordertype = "";
		$ordertype = $_REQUEST['ordertype'];
		$payment_order = "";
		//  print_r($ordertype);die;
		$payment_order = $_REQUEST['payment_order'];
		$todate = $_REQUEST['todate'];
		$original_todate =  $_REQUEST['todate'];
		$original_fromdate =  $_REQUEST['fromdate'];
        $reffrom = $_REQUEST['reffrom'];
        $refto = $_REQUEST['refto'];
        $printtype = $_REQUEST['printtype'];

		if($fromdate != "" || $orderinput != "" || $ordertype != "" || $todate != "" || $reffrom != "" || $refto != "" || $printtype != ""){
		$paymenttype = $_REQUEST['paymenttype'];
        $fromdate =  date("Y-m-d", strtotime($fromdate));
        $todate   =  date("Y-m-d", strtotime($todate));
		$db =& ConnectionManager::getDataSource("default");
		$allQuestions = array();
		$allStatuses = $this->Lists->ListTable('ace_rp_order_statuses');
		$allJobTypes = $this->Lists->ListTable('ace_rp_order_types');
		
        $reffrom1 = "";
        $refto1 = "";
		$refbetween ="";
		$isdate = 0;
		$ispayment = 0;
		$date = "";
		if($fromdate !=  "1969-12-31" && $todate ==  "1969-12-31"){
			$isdate = 1;
			$date = "orders.job_date >= '$fromdate' ";
			
		}
		if($todate !=  "1969-12-31" && $fromdate == "1969-12-31"){
			$isdate = 1;
			$date = "orders.job_date <= '$fromdate' ";	
		}
		if($fromdate != "1969-12-31" && $todate != "1969-12-31"){
			$isdate = 1;
			$date = "(orders.job_date BETWEEN  '$fromdate' AND '$todate' )";
        }
		if($paymenttype){
			$ispayment = 1;
			if($isdate == 1){
				$paymenttype = "and payment.payment_method = $paymenttype";
			}else{
				$paymenttype = "payment.payment_method = $paymenttype";
			}
		}else{
			$paymenttype = "";
		}
		if($reffrom && $refto == ""){
			if($isdate == 1 || $ispayment == 1){
			$reffrom1 = "and orders.order_number >= $reffrom";
			}else{
			$reffrom1 = " orders.order_number >= $reffrom";
			}
		}else{
			$reffrom1 = "";
		}
		if($refto && $reffrom == ""){
			if($isdate == 1 || $ispayment == 1){
			$refto1 = "and orders.order_number <= $refto";
			}else{
				$refto1 = "orders.order_number <= $refto";	
			}
		}else{
			$refto1 = "";
		}
		if($refto != "" && $reffrom != ""){
			if($isdate == 1 || $ispayment == 1){
			$refbetween = "and (orders.order_number BETWEEN  '$reffrom' AND '$refto' )";
			}else{
				$refbetween = "(orders.order_number BETWEEN  '$reffrom' AND '$refto' )";	
			}
			$$reffrom1 = "";
			$refto1 = "";
		}
		
		if($orderinput){
			$rr = implode(",",$orderinput);
			if(empty($rr)){
				die('please select atlease ');
			}
			$query = "SELECT orders.*,payment.paid_amount,methods.name as payment_method_name,methods.id as paid_by_id  FROM `ace_rp_orders` as orders left join ace_rp_payments as payment on payment.idorder = orders.id left join ace_rp_payment_methods as methods on methods.id = payment.payment_method   WHERE orders.id in ($rr)  ";
			$printtype = 1;
		 }else{
			$query = "SELECT orders.*,payment.paid_amount,methods.name as payment_method_name,methods.id as paid_by_id FROM `ace_rp_orders` as orders left join ace_rp_payments as payment on payment.idorder = orders.id left join ace_rp_payment_methods as methods on methods.id = payment.payment_method  WHERE $date $paymenttype  $refbetween $reffrom1 $refto1";	
		 }
	
		 if($payment_order == 1){
			$query .= "order by payment.paid_amount asc";
			$payment_order = 2;	
			$ordertype = "";
		}else if($payment_order == 2){
			$query .= "order by payment.paid_amount desc";
			$payment_order = 1;	
			$ordertype = "";
		}

		if($ordertype == 1){
			$query .= "order by orders.order_number asc";
			$ordertype = 2;	
			$payment_order = "";
		}else if($ordertype == 2){
		    $query .= "order by orders.order_number desc";
			$ordertype = 1;	
			$payment_order = "";
		}
		$result = $db->_execute($query);
		$num=0;
		$orders = array();
		// print_r($result);die;
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$orders[$num]['Order'] = $row;
			$orders[$num]['BookingItem'] = $this->get_items_by_order_id($row['id']);
            
			$orders[$num]['Customer'] = $this->get_customers_by_customer_id($row['customer_id']);

			$orders[$num]['BookingCoupon'] = $this->get_coupon_by_order_id($row['id']);
			// $orders[$num]['orderpayment'] = $this->getorderpayment($row['id']);
			$orders[$num]['technician'] = $this->get_technician_by_tech_id($row['job_technician1_id']);
			
			$num++;   
		}
		// print_r($orders);die;
	   // $this->set('job_truck', $this->params['url']['job_truck']);
		$this->set('orders', $orders);

		$this->set('fromdate', $_REQUEST['fromdate']);
		$this->set('todate', $_REQUEST['todate']);
		$this->set('reffrom', $_REQUEST['reffrom']);
		$this->set('original_fromdate', $original_fromdate);
		$this->set('original_todate', $original_todate);
		// $this->set('printtype', $_REQUEST['printtype']);
		$this->set('refto', $_REQUEST['refto']);
		$this->set('paymenttype', $_REQUEST['paymenttype']);

		$this->set('printtype', $printtype);
		$this->set('payment_order', $payment_order);
		$this->set('ordertype', $ordertype);
		$this->set('allSources', $this->Lists->BookingSources());
		$this->set('suppliers', $this->Lists->ListTable('ace_rp_suppliers','',array('name','address')));
		$this->set('job_trucks', $this->HtmlAssist->table2array($this->InventoryLocation->findAll(array('type' => '2'), null, null, null, 1, 0), 'id', 'name'));
		$this->set('techs', $this->Lists->Technicians());
		$this->set('payment_methods', $this->HtmlAssist->table2array($this->PaymentMethod->findAll(array(), null, null, null, 1, 0), 'id', 'name'));
	}
    
        $this->set("payment_types", $methods);
		//$this->set('jobcategories', $this->Lists->ListTable('ace_rp_order_type_categories'));
    }
    
	

    function invoiceprint() {
        // ini_set('display_errors', 1);
		// ini_set('display_startup_errors', 1);
		// error_reporting(E_ALL);
		$this->layout = "blank";
		$fromdate = $_REQUEST['fromdate'];
		$orderinput = $_REQUEST['orderinput'];
		$ordertype = "";
		$ordertype = $_REQUEST['ordertype'];
		$payment_order = "";
		//  print_r($ordertype);die;
		$payment_order = $_REQUEST['payment_order'];
		$filer_ordertype = $_REQUEST['filer_ordertype'];
		$filter_payment_order = $_REQUEST['filter_payment_order'];
		
		$todate = $_REQUEST['todate'];
        $reffrom = $_REQUEST['reffrom'];
        $refto = $_REQUEST['refto'];
        $printtype = $_REQUEST['printtype'];
		
        $paymenttype = $_REQUEST['paymenttype'];
        $fromdate =  date("Y-m-d", strtotime($fromdate));
        $todate   =  date("Y-m-d", strtotime($todate));
		$db =& ConnectionManager::getDataSource("default");
		$allQuestions = array();
		$allStatuses = $this->Lists->ListTable('ace_rp_order_statuses');
		$allJobTypes = $this->Lists->ListTable('ace_rp_order_types');
		
        $reffrom1 = "";
        $refto1 = "";
		$refbetween ="";
		$isdate = 0;
		$ispayment = 0;
		$date = "";
		if($fromdate !=  "1969-12-31" && $todate ==  "1969-12-31"){
			$isdate = 1;
			$date = "orders.job_date >= '$fromdate' ";
			
		}
		if($todate !=  "1969-12-31" && $fromdate == "1969-12-31"){
			$isdate = 1;
			$date = "orders.job_date <= '$fromdate' ";	
		}
		if($fromdate != "1969-12-31" && $todate != "1969-12-31"){
			$isdate = 1;
			$date = "(orders.job_date BETWEEN  '$fromdate' AND '$todate' )";
        }
		if($paymenttype){
			$ispayment = 1;
			if($isdate == 1){
				$paymenttype = "and payment.payment_method = $paymenttype";
			}else{
				$paymenttype = "payment.payment_method = $paymenttype";
			}
		}else{
			$paymenttype = "";
		}
		if($reffrom && $refto == ""){
			if($isdate == 1 || $ispayment == 1){
			$reffrom1 = "and orders.order_number >= $reffrom";
			}else{
			$reffrom1 = " orders.order_number >= $reffrom";
			}
		}else{
			$reffrom1 = "";
		}
		if($refto && $reffrom == ""){
			if($isdate == 1 || $ispayment == 1){
			$refto1 = "and orders.order_number <= $refto";
			}else{
				$refto1 = "orders.order_number <= $refto";	
			}
		}else{
			$refto1 = "";
		}
		if($refto != "" && $reffrom != ""){
			if($isdate == 1 || $ispayment == 1){
			$refbetween = "and (orders.order_number BETWEEN  '$reffrom' AND '$refto' )";
			}else{
				$refbetween = "(orders.order_number BETWEEN  '$reffrom' AND '$refto' )";	
			}
			$$reffrom1 = "";
			$refto1 = "";
		}
		
         if($orderinput){
			$rr = implode(",",$orderinput);
			if(empty($rr)){
				die('please select atlease ');
			}
			$query = "SELECT orders.*,payment.paid_amount,methods.name as payment_method_name FROM `ace_rp_orders` as orders left join ace_rp_payments as payment on payment.idorder = orders.id left join ace_rp_payment_methods as methods on methods.id = payment.payment_method  WHERE orders.id in ($rr)  ";
			$printtype = 1;
		 }else{
			$query = "SELECT orders.*,payment.paid_amount,methods.name as payment_method_name FROM `ace_rp_orders` as orders left join ace_rp_payments as payment on payment.idorder = orders.id left join ace_rp_payment_methods as methods on methods.id = payment.payment_method WHERE $date $paymenttype  $refbetween $reffrom1 $refto1";	
		 }
	
		 if($payment_order == 1){
			$query .= "order by payment.paid_amount asc";
			$payment_order = 2;
			$ordertype ="";	
		}else if($payment_order == 2){
			$query .= "order by payment.paid_amount desc";
			$payment_order = 1;
			$ordertype ="";		
		}

		if($ordertype == 1){
			$query .= "order by orders.order_number asc";
			$ordertype = 2;
			$payment_order = "";	
		}else if($ordertype == 2){
			
			$query .= "order by orders.order_number desc";
			$ordertype = 1;	
			$payment_order ="";
		}

		if($filer_ordertype == 1){
			$query .= "order by orders.order_number desc";
			$ordertype = 2;
			$payment_order ="";	
		}else if($filer_ordertype == 2){
			$query .= "order by orders.order_number asc";
			$ordertype = 1;
			$payment_order ="";		
		}

		if($filter_payment_order == 1){
			$query .= "order by payment.paid_amount desc";
			$payment_order = 2;
			$ordertype = "";	
		}else if($filter_payment_order == 2){
			
			$query .= "order by payment.paid_amount asc";
			$payment_order = 1;	
			$ordertype ="";
		}
		// print_r($query);die;


		
	 
		$result = $db->_execute($query);
		$num=0;
		$orders = array();
		// print_r($result);die;
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$orders[$num]['Order'] = $row;
			$orders[$num]['BookingItem'] = $this->get_items_by_order_id($row['id']);
            
			$orders[$num]['Customer'] = $this->get_customers_by_customer_id($row['customer_id']);

			$orders[$num]['BookingCoupon'] = $this->get_coupon_by_order_id($row['id']);
			// $orders[$num]['orderpayment'] = $this->getorderpayment($row['id']);
			$orders[$num]['technician'] = $this->get_technician_by_tech_id($row['job_technician1_id']);
			
			$num++;   
		}
		
	   // $this->set('job_truck', $this->params['url']['job_truck']);
		$this->set('orders', $orders);

		$this->set('fromdate', $_REQUEST['fromdate']);
		$this->set('todate', $_REQUEST['todate']);
		$this->set('reffrom', $_REQUEST['reffrom']);
		// $this->set('printtype', $_REQUEST['printtype']);
		$this->set('refto', $_REQUEST['refto']);
		$this->set('paymenttype', $_REQUEST['paymenttype']);
	

		$this->set('printtype', $printtype);
		$this->set('payment_order', $payment_order);
		$this->set('ordertype', $ordertype);
		$this->set('allSources', $this->Lists->BookingSources());
		$this->set('suppliers', $this->Lists->ListTable('ace_rp_suppliers','',array('name','address')));
		$this->set('job_trucks', $this->HtmlAssist->table2array($this->InventoryLocation->findAll(array('type' => '2'), null, null, null, 1, 0), 'id', 'name'));
		$this->set('techs', $this->Lists->Technicians());
		$this->set('payment_methods', $this->HtmlAssist->table2array($this->PaymentMethod->findAll(array(), null, null, null, 1, 0), 'id', 'name'));


		}
// public function getorderpayment($order_id){
		
// 			$db =& ConnectionManager::getDataSource("default");
// 			$item_name = array();
// 			if($order_id){
// 			$query="select paid_amount from ace_rp_payments  where idorder = $order_id ";
			 
// 				$result = $db->_execute($query);
// 				// $row = mysql_fetch_array($result, MYSQL_ASSOC);
			
// 				$num = 0;
// 				while($row = mysql_fetch_array($result, MYSQL_ASSOC))
// 				{
				
// 					$item_name[$num]['payment'] = $row['paid_amount'];
// 					$num++;  
// 				}
// 			}
// 		    	// print_r($item_name);die;
// 				return $item_name; 
// 		}

		public function get_items_by_order_id($order_id){
		
			$db =& ConnectionManager::getDataSource("default");
			$query="select name,quantity,price from ace_rp_order_items where order_id = $order_id ";
				$result = $db->_execute($query);
				// $row = mysql_fetch_array($result, MYSQL_ASSOC);
				$item_name = array();
				$num = 0;
				while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				{
					$item_name[$num]['name'] = $row['name'];
					$item_name[$num]['quantity'] = $row['quantity'];
					$item_name[$num]['price'] = $row['price'];
					$num++;   
				}
				return $item_name; 
		}

		public function get_technician_by_tech_id($job_technician1_id){
		
			$db =& ConnectionManager::getDataSource("default");
			$item_name = array();
			if($job_technician1_id){
			$query="select first_name from ace_rp_users  where id = $job_technician1_id ";
			 
				$result = $db->_execute($query);
				// $row = mysql_fetch_array($result, MYSQL_ASSOC);
			
				$num = 0;
				while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				{
					$item_name[$num]['first_name'] = $row['first_name'];
					$num++;   
				}
			}
		    	// print_r($item_name);die;
				return $item_name; 
		}

		public function get_customers_by_customer_id($customer_id){
		
			$db =& ConnectionManager::getDataSource("default");
			   $item_name = array();
            if($customer_id){
			   $query="select first_name,last_name,address_unit,address_street_number,city,postal_code from ace_rp_customers where id = $customer_id";
			  
			   $result = $db->_execute($query);
				// $row = mysql_fetch_array($result, MYSQL_ASSOC);
				
				$num = 0;
				while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				{
					 $item_name[$num]['first_name'] = $row['first_name'];
					 $item_name[$num]['last_name'] = $row['last_name'];
					 $item_name[$num]['address_unit'] = $row['address_unit'];
					 $item_name[$num]['address_street_number'] = $row['address_street_number'];
					 $item_name[$num]['city'] = $row['city'];
					 $item_name[$num]['postal_code'] = $row['postal_code'];
					 $num++;   
				}
			}

				return $item_name; 

		}

		public function updateorder(){
		$db =& ConnectionManager::getDataSource("default");
		$order_id = $_POST['val'];
		$paying_by=$_POST['val1'];
		$payment_method = $_POST['val2'];
		
		
		$db->_execute("update ace_rp_orders set payment_method_type=$paying_by WHERE id=$order_id");
		
		$db->_execute("update ace_rp_payments set payment_method=$payment_method WHERE idorder=$order_id");
		
		exit();
			
			
			
			
		}
		public function get_coupon_by_order_id($order_id){ 
		
			$db =& ConnectionManager::getDataSource("default");
			$item_name = array();
			if($order_id){
			$query="select name,price  from ace_rp_order_coupons where order_id = $order_id ";
				$result = $db->_execute($query);
				// $row = mysql_fetch_array($result, MYSQL_ASSOC);
				
				$num = 0;
				while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				{
					 $item_name[$num]['name'] = $row['name'];
					 $item_name[$num]['price'] = $row['price'];
					 
					 $num++;   
				}
			}

				return $item_name; 

		}
		
		 function createDateRangeArray($strDateFrom,$strDateTo)
{
    //create an array of dates provided so we can use the loop over them in our function
    

    $aryRange = array();

    $iDateFrom = mktime(1, 0, 0, substr($strDateFrom, 5, 2), substr($strDateFrom, 8, 2), substr($strDateFrom, 0, 4));
    $iDateTo = mktime(1, 0, 0, substr($strDateTo, 5, 2), substr($strDateTo, 8, 2), substr($strDateTo, 0, 4));

    if ($iDateTo >= $iDateFrom) {
        array_push($aryRange, date('Y-m-d', $iDateFrom)); // first entry
        while ($iDateFrom<$iDateTo) {
            $iDateFrom += 86400; // add 24 hours
            array_push($aryRange, date('Y-m-d', $iDateFrom));
        }
    }
    return $aryRange;
exit();
}
		
		public function scheduleview12(){
			//echo "<pre>";
			//print_r($_POST);
			//die();
			
			//get the schedule view according to the date and the post codes
			$exclude_id= array();
			$db =& ConnectionManager::getDataSource("default");
			$truck_ids = array();
			$data = array();
			$trucks = $_POST['trucks'];
			$fromDate = $_POST['fromDate'];
			$fromDate_sc = $_POST['fromDate_sc'];
			$toDate_sc = $_POST['toDate'];
			$street = $_POST['street'];
			$route = $_POST['route'];
			$period = $_POST['period'];
			$period_query="";
			if($period=='am'){
			$period_query="and job_time_beg <= '12:00:00'";	
			}
			else if($period=='pm'){
				$period_query="and job_time_end >= '12:00:00'";
			}
			if(empty($fromDate_sc) && empty($toDate_sc) ){
			$date_array[] =	date('Y-m-d',strtotime($_POST['fromDate']));
				
			}
			else {
			
			$date_array=$this->createDateRangeArray($fromDate_sc,$toDate_sc);	
			}
			
			
			
			
			$get_route_type = $_POST['service'];
			
			if(!empty($trucks) && $trucks!="" ){
				
				$truck_ids[] = $trucks;
			}
			else {
				if($get_route_type=='123'){
					$route_query = "select id from ace_rp_inventory_locations and flagactive=0";
					
				}
				else {
					$route_query = "select id from ace_rp_inventory_locations where route_type=$get_route_type and flagactive=0";
					
				}
		
				
			$result_route = $db->_execute($route_query);
			while($row_route = mysql_fetch_array($result_route, MYSQL_ASSOC)){
				$truck_ids[]=$row_route['id'];
				
			}
			}
			
			
			$get_trucks = implode(',',$truck_ids);
			$fromDate = $_POST['fromDate'];
			$get_date=date('Y-m-d',strtotime($fromDate));
			$postalCode = $_POST['postalCode'];
			$result_ids = array();
			$result_arr=array();
			if($period!='pm'){
				
			
			foreach($date_array as $date_arrays){
				
			
			
			foreach($truck_ids as $val => $truck_id){
				
				 $query="select s.id,s.job_time_beg,s.job_postal_code,s.customer_id,s.job_date,
				 s.job_truck,l.name from ace_rp_orders s join ace_rp_inventory_locations
				 l on (s.job_truck=l.id) where job_date='{$date_arrays}' $period_query
				 and job_truck ='$truck_id' and s.job_time_beg!='00:00:00' and s.job_time_end!='00:00:00'
				 and job_truck!=40
				 ORDER BY job_time_end ASC LIMIT 1";
			  
			
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				{
					if($row['job_time_beg']!='08:00:00'){
					
					$exclude_id[] = $row['job_truck'];
					$beg_time  = $row['job_time_beg'];
					$ex_time_beg = explode(':',$beg_time);
					$new_start_time = $ex_time_beg[0]-1;
					$new_end_time = $ex_time_beg[0];
					$postal1=trim($row['job_postal_code']);
					$customer=$row['customer_id'];
					if(empty($row['job_postal_code'])){
						$get_postal="select postal_code from ace_rp_customers where id='$customer'";
						$result111 = $db->_execute($get_postal);
						$row11 = mysql_fetch_array($result111, MYSQL_ASSOC);
						$postal1=$row11['postal_code'];
						
					}
					
			 $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$postal1."&destinations=".$postalCode."&mode=driving&language=en-EN&sensor=false&key=AIzaSyDUC73wk4-yrBlIKZOy7j1ya2_dv9MFiGw";
            
			$ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"http://hvacproz.ca/acesystem2018/distance_calculation.php");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,"URL=".urlencode($url));
            // receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec ($ch);//exit;
            
            
            $url2 = json_decode($response, true);
			
			

				$new_start_time11 = date("h A", strtotime("$new_start_time:00"));
				
				$new_end_time11 = date("h A", strtotime("$new_end_time:00"));
                       
                       $duration = ($url2['rows'][0]['elements'][0]['distance']['text']);
                       
                       $time = ($url2['rows'][0]['elements'][0]['duration']['text']);
					   
					   $time1 = ($url2['rows'][0]['elements'][0]['duration']['value']);
					   
					   $result_arr['html']= "<span>$new_start_time $am-$new_end_time $am1</span> <span>$time</span> <span>$duration</span> <span>route 2</span>";
					   
					   $result_arr['id'] = $row['id'];
					   $truck = $row['job_truck'];
					   $truck_name = $row['name'];
					  
					  $date  = $row['job_date'];	
                      $date1 = date('d M Y',strtotime($date));
					  $date2 = date('M d',strtotime($date));
					   
					   $href=BASE_URL."/orders/editBooking?customer_id=&job_truck=$truck&street=$street&route=$route&postal_code=$postalCode&job_time_beg=$new_start_time:00&job_time_end=$new_end_time:00&job_date=$date1&job_technician1_id=&job_technician2_id=";
					   $result_ids[] = array(
					   'html' => "<a href='$href'><span>$new_start_time11 - $new_end_time11</span><span>$date2</span><span>$time</span><span>$duration</span><span>$truck_name</span></a>",
					   'id' => $row['id'],
					   'duration'=>$duration*1000,
					   'time' => $time1,
					   'date'=>$row['job_date']
    );
					    
				}
				}
				
			}
		}
			}
		
		    if($period!='am'){
				
				
		
			foreach($date_array as $date_arrays){
			
			foreach($truck_ids as $val => $truck_id){
				
				  $exclude_id_get = implode(',',$exclude_id);
				  
				  
				 $query="select s.id,s.job_time_end,s.job_postal_code,s.customer_id,s.job_date,s.job_truck,l.name
				 from ace_rp_orders s join ace_rp_inventory_locations l on (s.job_truck=l.id)
				 where job_date='{$date_arrays}' $period_query and s.job_time_beg!='00:00:00' and s.job_time_end!='00:00:00' and job_truck ='$truck_id' $will_truck_ids
				 ORDER BY job_time_end DESC LIMIT 5";
			   
			
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				{
					if($row['job_time_end']!='20:00:00'){
						
					
					$end_time  = $row['job_time_end'];
					$ex_time_end = explode(':',$end_time);
					$new_start_time = $ex_time_end[0];
					$new_end_time = $ex_time_end[0]+1;
					$postal1=trim($row['job_postal_code']);
					$customer=$row['customer_id'];
					if(empty($row['job_postal_code'])){
						$get_postal="select postal_code from ace_rp_customers where id='$customer'";
						$result111 = $db->_execute($get_postal);
						$row11 = mysql_fetch_array($result111, MYSQL_ASSOC);
						$postal1=$row11['postal_code'];
						
					}
					
			 $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$postal1."&destinations=".$postalCode."&mode=driving&language=en-EN&sensor=false&key=AIzaSyDUC73wk4-yrBlIKZOy7j1ya2_dv9MFiGw";
            
			$ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"http://hvacproz.ca/acesystem2018/distance_calculation.php");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,"URL=".urlencode($url));
            // receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec ($ch);//exit;
            
            
            $url2 = json_decode($response, true);
			
			


						$new_start_time11 = date("h A", strtotime("$new_start_time:00"));
						
						$new_end_time11 = date("h A", strtotime("$new_end_time:00"));
                       
                       $duration = ($url2['rows'][0]['elements'][0]['distance']['text']);
                       
                       $time = ($url2['rows'][0]['elements'][0]['duration']['text']);
					   
					   $time1 = ($url2['rows'][0]['elements'][0]['duration']['value']);
					   
					   $result_arr['html']= "<span>$new_start_time $am-$new_end_time $am1</span> <span>$time</span> <span>$duration</span> <span>route 2</span>";
					   
					   $result_arr['id'] = $row['id'];
					   $truck = $row['job_truck'];
					   $truck_name = $row['name'];
					  
					  $date  = $row['job_date'];	
                      $date1 = date('d M Y',strtotime($date));
					  $date2 = date('M d',strtotime($date));
					   
					   $href=BASE_URL."/orders/editBooking?customer_id=&job_truck=$truck&street=$street&route=$route&postal_code=$postalCode&job_time_beg=$new_start_time:00&job_time_end=$new_end_time:00&job_date=$date1&job_technician1_id=&job_technician2_id=";
					   $result_ids[] = array(
					   'html' => "<a href='$href'><span>$new_start_time11 - $new_end_time11</span><span>$date2</span><span>$time</span><span>$duration</span><span>$truck_name</span></a>",
					   'id' => $row['id'],
					   'duration'=>$duration*1000,
					   'time' => $time1,
					   'date'=>$row['job_date']
    );
					    
				}
				
			}
			}
			}
			}
			
			if(empty($result_arr)){
				$result_arr['id']='0';
				$result_arr['html']= "no booking found";
			}
			
		function cmp($a, $b) {
        return $a["time"] - $b["time"];
		}
		//sort according to the time taken
		usort($result_ids, "cmp");
		
		if($_POST['choose_km']!="" && $_POST['choose_time']!="" ){
			
			foreach ($result_ids as $key => $item) 
				{
				   if ($item['duration'] <= $_POST['choose_km']*1000 && $item['time'] <= $_POST['choose_time']*60  )
				   {
					   $data[] = array(
									  'html' => $item['html'],
									  'id' => $item['id'],
									  'time' => $item['time'],
									  'date' => $item['date'],
			   );
				   }
				}
		print_r(json_encode($data));
			exit();

			
		}
		
		if(isset($_POST['choose_km']) && $_POST['choose_km']!="" ){
			foreach ($result_ids as $key => $item) 
				{
				   if ($item['duration'] < $_POST['choose_km']*1000 )
				   {
					   $data[] = array(
									  'html' => $item['html'],
									  'id' => $item['id'],
									  'time' => $item['time'],
									  'date' => $item['date'],
			   );
				   }
				}
				   }
				   else if($_POST['choose_km']!="") {
					
					   
					   $data = $result_ids;
					   
				   }
		
		if($_POST['choose_km']=="" || $_POST['choose_time']!="" ){
			
		
		
	if(isset($_POST['choose_time']) && $_POST['choose_time']!="" ){
	
			foreach ($result_ids as $key => $item) 
 {
    if ($item['time'] <= $_POST['choose_time']*60 )
    {
        $data[] = array(
					   'html' => $item['html'],
					   'id' => $item['id'],
					   'time' => $item['time'],
					   'date' => $item['date'],
    );
    }
 }
	}
	else {
		
		$data = $result_ids;
		
	}
	
		}
		
		$data = array_slice($data, 0, 10);

		
			
			print_r(json_encode($data));
			exit();
			
			
		}
		//save the label and the date from the images
		public function uploadimages(){
			
		
			
			$db =& ConnectionManager::getDataSource("default");
			
			$customer = $_POST['customer'];
			$date = $_POST['date'];
			$label = $_POST['label'];
			$newdate = $_POST['newdate'];
			if(empty($date)){
				$date='IS NULL';
			}
			else {
				$date="='$date'";
			}
			if(empty($newdate)){
				$newdate='=NULL';
			}
			else {
				$newdate="='$newdate'";
			}
			
		  //echo "update ace_rp_user_part_images set label='$label',date_created $newdate WHERE customer_id='$customer' and date_created $date";
		  //die();
			
			$db->_execute("update ace_rp_user_part_images set label='$label',date_created $newdate WHERE customer_id='$customer' and date_created $date");
			exit();
		}
       
	   	//create a new page where we will show the calander
		public function calender2(){
			//die('11');
			// echo "<pre>";
			// print_r($_REQUEST);
			// die();
			if($_REQUEST['first_page']==1){
				$get_url=(parse_url($_SERVER['REQUEST_URI']));
				$get_url2 = $get_url['path']."?".$get_url['query']."&first_page=0"; 
				echo '<div style="margin: 0 auto;
    display: table;
    margin-top: 10%"><img src="http://i.imgur.com/KUJoe.gif"><br>please wait loading</div>';
	header( "refresh:1;url=$get_url2" );
				exit;
			}
			else {
				$this->set('first_page', 0);
				
			}
			
		$this->layout = "blank";
		$exclude_id= array();
		$exclude_id11= array();
		$exclude_date= array();
		$exclude_truck= array();
		$exclude_truck2= array();
		$exclude_time_start= array();
		$exclude_time_end= array();
		$sunday_count=0;
		$booked_arr=0;
			$db =& ConnectionManager::getDataSource("default");
			$truck_ids = array();
			$data = array();
			$trucks = $_REQUEST['trucks'];
			$fromDate = $_REQUEST['fromDate'];
			$fromDate_sc = $_REQUEST['sc_from'];
			$toDate_sc = $_REQUEST['sc_to'];
			$street = $_REQUEST['street_number'];
			$route = $_REQUEST['route'];
			$city = $_REQUEST['city'];
			$period_am = $_REQUEST['sc_period'];
			$choose_km=$_REQUEST['choose_km'];
			$choose_time=$_REQUEST['choose_time'];
			$customer_id=$_REQUEST['customer_id'];
			$period = "";
			$period_query="";
			if($period=='am'){
			$period_query="";	
			}
			else if($period=='pm'){
				$period_query="";
			}
			if(empty($fromDate_sc) && empty($toDate_sc) ){
			$date_array[] =	date('Y-m-d',strtotime($_REQUEST['fromDate']));
				
			}
			else {
			
			$date_array=$this->createDateRangeArray($fromDate_sc,$toDate_sc);	
			}
			

			
			
			$get_route_type = $_REQUEST['map_services'];
			
			if(!empty($trucks) && $trucks!="" ){
				
				$truck_ids[] = $trucks;
			}
			else {
				if($get_route_type=='123'){
					$route_query = "select id from ace_rp_inventory_locations where flagactive=0 and id!=40 order by id desc";
					
				}
				else {
					$route_query = "select id from ace_rp_inventory_locations where route_type=$get_route_type and id!=40 and flagactive=0 order by id desc";
					
				}
				
		      
				
			$result_route = $db->_execute($route_query);
			while($row_route = mysql_fetch_array($result_route, MYSQL_ASSOC)){
				$truck_ids[]=$row_route['id'];
				
			}
			}
			
		  
			
			$get_trucks = implode(',',$truck_ids);
			$fromDate = $_REQUEST['fromDate'];
			$get_date=date('Y-m-d',strtotime($fromDate));
			$postalCode = $_REQUEST['postal_code'];
			$result_ids = array();
			$result_arr=array();
			if($period!='pm'){
				
			
			foreach($date_array as $date_arrays){
				$get_st_time1 = array();
			$get_end_time1 = array();
				
			$duration123456=0;
			
			foreach($truck_ids as $val=> $truck_id){
				

				
				
			
				
			 $query="select s.id,s.job_time_beg,s.job_postal_code,s.customer_id,s.job_date,
				 s.job_truck,l.name from ace_rp_orders s join ace_rp_inventory_locations
				 l on (s.job_truck=l.id) where job_date='{$date_arrays}' $period_query
				 and job_truck ='$truck_id' and s.job_time_beg!='00:00:00' and s.job_time_end!='00:00:00'
				 and job_truck!=40 and l.flagactive=0 and s.order_status_id in (1,5)
				 ORDER BY job_time_end";
			    
	
			$result = $db->_execute($query);
			
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				{
					if($row['job_time_beg']!='08:00:00' && $row['job_time_beg']!='09:00:00'){
				
					
					$exclude_id[] = $row['job_truck'];
					$beg_time  = $row['job_time_beg'];
					$ex_time_beg = explode(':',$beg_time);
					if($ex_time_beg[0]=="13"){
						$ex_time_beg[0]="12";
					}
					if($ex_time_beg[0]=="15"){
						$ex_time_beg[0]="14";
					}
					if($ex_time_beg[0]=="11"){
						$ex_time_beg[0]="10";
					}
					$new_start_time = $ex_time_beg[0]-2;
					$new_end_time = $ex_time_beg[0];
					$postal1=trim($row['job_postal_code']);
					$customer=$row['customer_id'];
					if(empty($row['job_postal_code'])){
						$get_postal="select postal_code from ace_rp_customers where id='$customer'";
						$result111 = $db->_execute($get_postal);
						$row11 = mysql_fetch_array($result111, MYSQL_ASSOC);
						$postal1=$row11['postal_code'];
						
					}
					
			 $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$postal1."&destinations=".$postalCode."&mode=driving&language=en-EN&sensor=false&key=AIzaSyDUC73wk4-yrBlIKZOy7j1ya2_dv9MFiGw";
            
			$ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"http://hvacproz.ca/acesystem2018/distance_calculation.php");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,"URL=".urlencode($url));
            // receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec ($ch);//exit;
            
            
            $url2 = json_decode($response, true);
			
			

				$new_start_time11 = date("h", strtotime("$new_start_time:00"));
				
				$new_end_time11 = date("h", strtotime("$new_end_time:00"));
                       
                       $duration = ($url2['rows'][0]['elements'][0]['distance']['text']);
                       
                       $time = ($url2['rows'][0]['elements'][0]['duration']['text']);
					   
					   $time1 = ($url2['rows'][0]['elements'][0]['duration']['value']);
					   
					   $result_arr['html']= "<span>$new_start_time $am-$new_end_time $am1</span> <span>$time</span> <span>$duration</span> <span>route 2</span>";
					   
					   $result_arr['id'] = $row['id'];
					   $truck = $row['job_truck'];
					   $truck_name = $row['name'];
					  
					  $date  = $row['job_date'];	
                      $date1 = date('d M Y',strtotime($date));
					  $date2 = date('M d',strtotime($date));
					  
					  if($this->checkBooking($row['job_date'],$truck,$new_start_time,$new_end_time)){
						
						continue;
					  }
					  
					  
					  
					  if($this->checkSchedule($row['job_date'],$truck,$new_start_time,$new_end_time)){
						
						continue;
					  }
					  
					  	if($_REQUEST['choose_km']!="" && $_REQUEST['choose_time']!="" ){
			
			
			
			
				   if ($duration1 <= $_REQUEST['choose_km']*1000 && $time1 <= $_REQUEST['choose_time']*60  )
				   {
					 
				   }
				   else {
					   
					continue;
					
					
					
				   }
				
				 
	

			
		}
					  
					  
					  
					  if($new_start_time==8){
						$new_start_time="08";
					  }
					   
					   $href=BASE_URL."/orders/editBooking?customer_id=&job_truck=$truck&street=$street&route=$route&postal_code=$postalCode&job_time_beg=$new_start_time:00&job_time_end=$new_end_time:00&job_date=$date1&job_technician1_id=&job_technician2_id=&from_calender=1&map_service=$get_route_type&city1=$city&choose_km=$choose_km&$choose_time=&$choose_time&choose_time=$choose_time&preiod=$period_am&customer_id=$customer_id";
					   $result_ids[] = array(
					   //'html' => "<a href='$href'><span>$new_start_time11 - $new_end_time11</span><span>$date2</span><span>$time</span><span>$duration</span><span>$truck_name</span></a>",
					   'title'=>"Available  \n $new_start_time11 and $new_end_time11",
					   'textColor'=>'green',
					   'sortBy'=>1,
					   'start' => $row['job_date']."T".$new_start_time.":00",
					   'end'=>$row['job_date']."T".$new_end_time.":00",
					   'duration'=>$duration*1000,
					   'time' => $time1,
					   'url'=>$href,
					   'date'=>$row['job_date']
    );
					   $exclude_date[]=$row['job_date'];
					   $exclude_id11[]=$row['job_date'];
						 $get_st_time1[]=$new_start_time;
						 $get_end_time1[]=$new_end_time;
						 
					    
				}
				}
			
	
		
			$sunday_count++;
				
			}
			$web_co=10;
			
			
					for ($i = 0; $i < 4; $i++){
					$start=$web_co-=2;
				    $end=$web_co+=2;
					if($start==8){
						$start="08";
				}
				
				$start1=$date_arrays."T$start:00:00";
					$end1=$date_arrays."T$end:00:00";
					
					$new_start_time11 = date("h", strtotime("$start:00"));
				
				    $new_end_time11 = date("h", strtotime("$end:00"));
					
					
					
					
					
					
						$result_ids[] = array(
					   
					   'title' =>"Not Available",
					   'sortBy'=>12,
					   'start'=>$start1,
					  
					   'end'=>$end1,
					   'textColor'=>'#B0B0B0',
					   'time'=>1,
					   'duration'=>1,
					   
    );
				
				
				
				$web_co+=2;
					}
			 			
			
		}
			} 
							
 
		
		    if($period!='am'){
				
			
			foreach($date_array as $date_arrays){
			
			foreach($truck_ids as $val => $truck_id){
				
				
			
				 $query="select s.id,s.job_time_end,s.job_postal_code,s.customer_id,s.job_date,
				 s.job_truck,l.name from ace_rp_orders s join ace_rp_inventory_locations
				 l on (s.job_truck=l.id) where job_date='{$date_arrays}' $period_query
				 and s.job_time_beg!='00:00:00' and s.job_time_end!='00:00:00' and s.order_status_id in (1,5)
				 and l.flagactive=0 and job_truck ='$truck_id' ORDER BY job_time_end";
				 
				 	
			$result = $db->_execute($query);
			$no_repeat_counter=0;
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				{
                    					
					if($row['job_time_end']!='20:00:00' && ($row['job_time_end']!='19:00:00') && ($row['job_time_end']!='16:00:00') && ($row['job_time_end']!='15:00:00') ){
						
			  
					$end_time  = $row['job_time_end'];
					$ex_time_end = explode(':',$end_time);
					$new_start_time = $ex_time_end[0];
					if($new_start_time==9){
						$new_start_time=10;
					}
					if($new_start_time==11){
						$new_start_time=12;
					}
					if($new_start_time==13){
						$new_start_time=14;
					}
					$new_end_time = $new_start_time+2;
					$postal1=trim($row['job_postal_code']);
					$customer=$row['customer_id'];
					if(empty($row['job_postal_code'])){
						$get_postal="select postal_code from ace_rp_customers where id='$customer'";
						$result111 = $db->_execute($get_postal);
						$row11 = mysql_fetch_array($result111, MYSQL_ASSOC);
						$postal1=$row11['postal_code'];
						
					}
					$query11 = "select count(*) cnt from ace_rp_orders
          where job_date = '".date("Y-m-d", strtotime($date_arrays))."'
          and job_truck  = '".$truck_id."'
          and ((job_time_beg >= '".$new_start_time.":00' and job_time_beg < '".$new_end_time.":00')
               or (job_time_end > '".(1+$new_start_time).":00' and job_time_end <= '".$new_end_time.":00'))
            AND order_status_id in (1,5)";
		
		$result11 = $db->_execute($query11);
      $row11 = mysql_fetch_array($result11);
 $bookedTrucks11 = $row11['cnt'];
	 if ($bookedTrucks11 > 0) {
        continue;
      }
		
            $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$postal1."&destinations=".$postalCode."&mode=driving&language=en-EN&sensor=false&key=AIzaSyDUC73wk4-yrBlIKZOy7j1ya2_dv9MFiGw";
            
			$ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"http://hvacproz.ca/acesystem2018/distance_calculation.php");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,"URL=".urlencode($url));
            // receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec ($ch);//exit;
            
            
            $url2 = json_decode($response, true);
			
			
						$duration = ($url2['rows'][0]['elements'][0]['distance']['text']);
                       
                       $time = ($url2['rows'][0]['elements'][0]['duration']['text']);
					   
					   $time1 = ($url2['rows'][0]['elements'][0]['duration']['value']);

						$new_start_time11 = date("h", strtotime("$new_start_time:00"));
						
						$new_end_time11 = date("h", strtotime("$new_end_time:00"));
                       
                       $duration = ($url2['rows'][0]['elements'][0]['distance']['text']);
					   
					   $duration1 = ($url2['rows'][0]['elements'][0]['distance']['value']);
                       
                       $time = ($url2['rows'][0]['elements'][0]['duration']['text']);
					   
					   $time1 = ($url2['rows'][0]['elements'][0]['duration']['value']);
					   
					   $result_arr['html']= "<span>$new_start_time $am-$new_end_time $am1</span> <span>$time</span> <span>$duration</span> <span>route 2</span>";
					   
					   $result_arr['id'] = $row['id'];
					   $truck = $row['job_truck'];
					   $truck_name = $row['name'];
					  
					  $date  = $row['job_date'];	
                      $date1 = date('d M Y',strtotime($date));
					  $date2 = date('M d',strtotime($date));
					  $start = $row['job_date']."T".$new_start_time.":00:00";
					  $end = $row['job_date']."T".$new_end_time.":00:00";
					  
					  if($this->checkBooking($row['job_date'],$truck,$new_start_time,$new_end_time)){
						continue;
					}
					
					if($this->checkSchedule($row['job_date'],$truck,$new_start_time,$new_end_time)){
		
					continue;
					
					}
					if($_REQUEST['choose_km']!="" && $_REQUEST['choose_time']!="" ){
			
			
			
			
				   if ($duration1 <= $_REQUEST['choose_km']*1000 && $time1 <= $_REQUEST['choose_time']*60  )
				   {
					  
				   }
				   else {
					continue;
				   }
				
	

			
		}
					   
					   $href=BASE_URL."/orders/editBooking?customer_id=&job_truck=$truck&street=$street&route=$route&postal_code=$postalCode&job_time_beg=$new_start_time:00&job_time_end=$new_end_time:00&job_date=$date1&job_technician1_id=&job_technician2_id=&from_calender=1&map_service=$get_route_type&city1=$city&choose_km=$choose_km&$choose_time=&$choose_time&choose_time=$choose_time&preiod=$period_am&customer_id=$customer_id";
					   $result_ids[] = array(
					   //'html' => "<a href='$href'><span>$new_start_time11 - $new_end_time11</span><span>$date2</span><span>$time</span><span>$duration</span><span>$truck_name</span></a>",
					   'title' =>"Available \n $new_start_time11 and $new_end_time11",
					   'sortBy'=>1,
					   'start'=>$start,
					   'end'=>$end,
					   'url'=>$href,
					   'textColor'=>'green',
					   'time'=>$time1,
					   'duration'=>$duration1,
					   
    );
					   $exclude_id11[]=$row['job_date'];
						 $exclude_time_start[]=$new_start_time;
						 $exclude_time_end[]=$new_end_time;
					    
				}
				
				$counter = $new_end_time;
				
			
				$no_repeat_counter++;
				
			}
			
			
					$check_empty = "SELECT id,job_time_end,job_time_beg,job_postal_code,customer_id,job_date,
				 job_truck FROM `ace_rp_orders`
WHERE `job_date` = '$date_arrays' and job_truck=$truck_id and order_status_id in (1,5) order by id desc LIMIT 1";
		     $check=0;
			 $result_empty = $db->_execute($check_empty);
			 while($row = mysql_fetch_array($result_empty, MYSQL_ASSOC)){
				
				$check++;
				$web_co=10;
			/*time slots alailable for the day where there is no booking
			 *right now we are only showing first available i.e.8 to 10
			 *change it to 4 if you want 8 to 10 and 10 to 12 and 12 to 2
			 *and 2 to 4
			 */
			
					for ($i = 0; $i < 4; $i++){
					$start=$web_co-=2;
				    $end=$web_co+=2;
					if($start==8){
						$start="08";
				}
				
				$postal1=trim($row['job_postal_code']);
					$customer=$row['customer_id'];
					if(empty($row['job_postal_code'])){
						$get_postal="select postal_code from ace_rp_customers where id='$customer'";
						$result111 = $db->_execute($get_postal);
						$row11 = mysql_fetch_array($result111, MYSQL_ASSOC);
						$postal1=$row11['postal_code'];
						
					}
					$url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$postal1."&destinations=".$postalCode."&mode=driving&language=en-EN&sensor=false&key=AIzaSyDUC73wk4-yrBlIKZOy7j1ya2_dv9MFiGw";
            
			$ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"http://hvacproz.ca/acesystem2018/distance_calculation.php");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,"URL=".urlencode($url));
            // receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec ($ch);//exit;
            
            
            $url2 = json_decode($response, true);
			
			
						$duration = ($url2['rows'][0]['elements'][0]['distance']['text']);
                       
                       $time = ($url2['rows'][0]['elements'][0]['duration']['text']);
					   
					   $time1 = ($url2['rows'][0]['elements'][0]['duration']['value']);

						$new_start_time11 = date("h", strtotime("$new_start_time:00"));
						
						$new_end_time11 = date("h", strtotime("$new_end_time:00"));
                       
                       $duration = ($url2['rows'][0]['elements'][0]['distance']['text']);
					   
					   $duration1 = ($url2['rows'][0]['elements'][0]['distance']['value']);
                       
                       $time = ($url2['rows'][0]['elements'][0]['duration']['text']);
					   
					   $time1 = ($url2['rows'][0]['elements'][0]['duration']['value']);
					
					
if ($duration1 <= $_REQUEST['choose_km']*1000 && $time1 <= $_REQUEST['choose_time']*60  )
				   {
					  
				   }
				   else {
					continue;
				   }
					
					   
					
					
				$href=BASE_URL."/orders/editBooking?customer_id=&job_truck=$truck_id&street=$street&route=$route&postal_code=$postalCode&job_time_beg=$start:00&job_time_end=$end:00&job_date=$date_arrays&job_technician1_id=&job_technician2_id=&from_calender=1&map_service=$get_route_type&city1=$city&choose_km=$choose_km&$choose_time=&$choose_time&choose_time=$choose_time&preiod=$period_am&customer_id=$customer_id";
					$start1=$date_arrays."T$start:00:00";
					$end1=$date_arrays."T$end:00:00";
					
					$new_start_time11 = date("h", strtotime("$start:00"));
				
				    $new_end_time11 = date("h", strtotime("$end:00"));
					
					if($this->checkBooking($date_arrays,$truck_id,$start,$end)){
						 $web_co+=2;
						continue;
					}
					
					if($this->checkSchedule($date_arrays,$truck_id,$start,$end)){
		              $web_co+=2;
					continue;
					
					}
					
					
					
					
					/*	$result_ids[] = array(
					   
					   'title' =>"Available   \n $new_start_time11 and $new_end_time11",
					   'sortBy'=>3,
					   'start'=>$start1,
					   'url'=>$href,
					   'end'=>$end1,
					   'textColor'=>'#007bff',
					   'time'=>1,
					   'duration'=>1,
					   
    );*/
						
						
					
					 
					 $web_co+=2;
					}

			 }
			 
			 if($check>0){
				
				
			 }
			 else{
				 
				
				$sunday=0;
				if($this->checkSunday($date_arrays)){

					//it is a sunday so ignore
                        $web_co1=10;
						for ($i = 0; $i < 4; $i++){
					$start=$web_co1-=2;
				    $end=$web_co1+=2;
					if($start==8){
						$start="08";
				}
						$result_ids[] = array(
					   
					   'title' =>"Not Available",
					   'sortBy'=>2,
					   'start'=>$date_arrays."T$start:00:00",
					   
					   'end'=>$date_arrays."T$end:00:00",
					   'textColor'=>'#B0B0B0',
					   'time'=>1,
					   'duration'=>1,
					   
    );


				
$web_co1+=2;


						}
					
	
					
	$sunday++;
				}
				else {

    
			$web_co=10;
			/*time slots alailable for the day where there is no booking
			 *right now we are only showing first available i.e.8 to 10
			 *change it to 4 if you want 8 to 10 and 10 to 12 and 12 to 2
			 *and 2 to 4
			 */
			
					for ($i = 0; $i < 4; $i++){
					$start=$web_co-=2;
				    $end=$web_co+=2;
					if($start==8){
						$start="08";
				}
				
				$postal1=trim($row['job_postal_code']);
					$customer=$row['customer_id'];
					if(empty($row['job_postal_code'])){
						$get_postal="select postal_code from ace_rp_customers where id='$customer'";
						$result111 = $db->_execute($get_postal);
						$row11 = mysql_fetch_array($result111, MYSQL_ASSOC);
						$postal1=$row11['postal_code'];
						
					}
					
					

					
					   
					
					
				$href=BASE_URL."/orders/editBooking?customer_id=&job_truck=$truck_id&street=$street&route=$route&postal_code=$postalCode&job_time_beg=$start:00&job_time_end=$end:00&job_date=$date_arrays&job_technician1_id=&job_technician2_id=&from_calender=1&map_service=$get_route_type&city1=$city&choose_km=$choose_km&$choose_time=&$choose_time&choose_time=$choose_time&preiod=$period_am&customer_id=$customer_id";
					$start1=$date_arrays."T$start:00:00";
					$end1=$date_arrays."T$end:00:00";
					
					$new_start_time11 = date("h", strtotime("$start:00"));
				
				    $new_end_time11 = date("h", strtotime("$end:00"));
					
					if($this->checkBooking($date_arrays,$truck_id,$start,$end)){
						 $web_co+=2;
						continue;
					}
					
					if($this->checkSchedule($date_arrays,$truck_id,$start,$end)){
		           $web_co+=2;
					continue;
					
					}
					
					
					
					
						$result_ids[] = array(
					   
					   'title' =>"Available \n $new_start_time11 and $new_end_time11",
					   'sortBy'=>3,
					   'start'=>$start1,
					   'url'=>$href,
					   'end'=>$end1,
					   'textColor'=>'#007bff',
					   'time'=>1,
					   'duration'=>1,
					   
    );
						
						
					
					 
					 $web_co+=2;
					}
	
					

						
				}
				
			 }
			 
			
				 
				 
				 
				  $query_all="select s.id,s.job_time_beg,s.job_time_end,s.job_postal_code,s.customer_id,s.job_date,s.job_truck,l.name
				 from ace_rp_orders s join ace_rp_inventory_locations l on (s.job_truck=l.id)
				 where job_date='{$date_arrays}' and s.job_time_beg!='00:00:00'
				 and s.job_time_end!='00:00:00' and l.flagactive=0 and job_truck ='$truck_id'
				 and order_status_id in (1,5) ORDER BY job_time_end DESC";
				 
				 $result_all = $db->_execute($query_all);
				 
				 while($row = mysql_fetch_array($result_all, MYSQL_ASSOC))
				{
		
					
					$start_time=  $row['job_time_beg'];
					$ex_start_time = explode(':',$start_time);
					$end_time  = $row['job_time_end'];
					$ex_time_end = explode(':',$end_time);
					$new_start_time = $ex_start_time[0];
					$new_end_time = $ex_time_end[0];
					$postal1=trim($row['job_postal_code']);
					$customer=$row['customer_id'];
					if(empty($row['job_postal_code'])){
						$get_postal="select postal_code from ace_rp_customers where id='$customer'";
						$result111 = $db->_execute($get_postal);
						$row11 = mysql_fetch_array($result111, MYSQL_ASSOC);
						$postal1=$row11['postal_code'];
						
					}
		
            
            
            $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$postal1."&destinations=".$postalCode."&mode=driving&language=en-EN&sensor=false&key=AIzaSyDUC73wk4-yrBlIKZOy7j1ya2_dv9MFiGw";
            
			$ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"http://hvacproz.ca/acesystem2018/distance_calculation.php");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,"URL=".urlencode($url));
            // receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec ($ch);//exit;
            
            
            $url2 = json_decode($response, true);

						$new_start_time11 = date("h", strtotime("$new_start_time:00"));
						
						$new_end_time11 = date("h", strtotime("$new_end_time:00"));
                       
                       $duration = ($url2['rows'][0]['elements'][0]['distance']['text']);
					   
					    $duration1 = ($url2['rows'][0]['elements'][0]['distance']['value']);
                       
                       $time = ($url2['rows'][0]['elements'][0]['duration']['text']);
					   
					   $time1 = ($url2['rows'][0]['elements'][0]['duration']['value']);
					   
					   $result_arr['html']= "<span>$new_start_time $am-$new_end_time $am1</span> <span>$time</span> <span>$duration</span> <span>route 2</span>";
					   
					   $result_arr['id'] = $row['id'];
					   $truck = $row['job_truck'];
					   $truck_name = $row['name'];
					  
					  $date  = $row['job_date'];	
                      $date1 = date('d M Y',strtotime($date));
					  $date2 = date('M d',strtotime($date));
					  $start = $row['job_date']."T".$new_start_time.":00:00";
					  $end = $row['job_date']."T".$new_end_time.":00:00";
					  
				
					
					
					   
					 
					   $result_ids[] = array(
					   'title' =>"Booked",
					   'sortBy'=>10,
					   'start'=>$start,
					   'end'=>$end,
					   
					   'color'=>'#B0B0B0',
					   'textColor'=>'#B0B0B0',
					   'time'=>1,
					   'duration'=>1,
    );
					  
					   
					  
					  
				
					    
				
				
			}
			
			   
		
			}
			$web_co=10;
			
			
					for ($i = 0; $i < 4; $i++){
					$start=$web_co-=2;
				    $end=$web_co+=2;
					if($start==8){
						$start="08";
				}
				
				$start1=$date_arrays."T$start:00:00";
					$end1=$date_arrays."T$end:00:00";
					
					$new_start_time11 = date("h", strtotime("$start:00"));
				
				    $new_end_time11 = date("h", strtotime("$end:00"));
					
					
					
					
					
					
						$result_ids[] = array(
					   
					   'title' =>"Not Available",
					   'sortBy'=>12,
					   'start'=>$start1,
					   
					   'end'=>$end1,
					   'textColor'=>'#B0B0B0',
					   'time'=>1,
					   'duration'=>1,
					   
    );
				
				
				
				$web_co+=2;
					}
			
			}
			} 
			
			
			if(empty($result_arr)){
				$result_arr['id']='0';
				$result_arr['html']= "no booking found";
			}
			
			
			
		function cmp($a, $b) {
        return $a["time"] - $b["time"];
		}
		//sort according to the time taken
		usort($result_ids, "cmp");
		
		
	$data=$result_ids;
		if(isset($_REQUEST['choose_km']) && $_REQUEST['choose_km']!="" ){
			
			foreach ($result_ids as $key => $item) 
				{
				   if ($item['duration'] <= $_REQUEST['choose_km']*1000 )
				   { 
					   $data[] = array(
									    'title' => $item['title'],
									 
									   'duration'=>$item['duration'],
									   'sortBy'=>$item['sortBy'],
									  'start' => $item['start'],
									  'time'=>$item['time'],
									  'end'=>$item['end'],
									  'url'=>$item['url'],
									  'textColor'=>$item['textColor'],
			   );
				   }
				}
				
				
				
				
				   }
				   else if($_REQUEST['choose_km']!="") {
					
					   
					   $data = $result_ids;
					   
				   }
		
		if($_REQUEST['choose_km']=="" || $_REQUEST['choose_time']!="" ){
			
		
		
	if(isset($_REQUEST['choose_time']) && $_REQUEST['choose_time']!="" ){
	
			foreach ($result_ids as $key => $item) 
 {
    if ($item['time'] <= $_REQUEST['choose_time']*60 )
    {
        $data[] = array(
									  'title' => $item['title'],
									   'duration'=>$item['duration'],
									   'sortBy'=>$item['sortBy'],
									  'start' => $item['start'],
									  'time'=>$item['time'],
									  'end'=>$item['end'],
									  'url'=>$item['url'],
									  'textColor'=>$item['textColor'],
    );
    }
 }
	}
	else {
		
		$data = $result_ids;
		
	}
	
		}
	
		
		
		
		
		
		//echo "<pre>";
		//print_r($data);
		//die();
		

		$this->set('data', $data);
		
		$this->set('start_date', $_REQUEST['sc_from']);
		
		$this->set('end_date', $_REQUEST['sc_to']);
		
		$this->set('allTypes', $this->Lists->ListTable('ace_rp_route_types'));
			
			
			
		}

		//create a new page where we will show the calander
		public function calender(){
			//die('11');
			//echo "<pre>";
			//print_r($_REQUEST);
			//die();
			
				$textcolor="green";
			
			
			if($_REQUEST['first_page']==1){
				$get_url=(parse_url($_SERVER['REQUEST_URI']));
				$get_url2 = $get_url['path']."?".$get_url['query']."&first_page=0"; 
				echo '<div style="margin: 0 auto;
    display: table;
    margin-top: 10%"><img src="http://i.imgur.com/KUJoe.gif"><br>please wait loading</div>';
	header( "refresh:1;url=$get_url2" );
				exit;
			}
			else {
				$this->set('first_page', 0);
				
			}
			
		$this->layout = "blank";
		$exclude_id= array();
		$exclude_id11= array();
		$exclude_date= array();
		$exclude_truck= array();
		$exclude_truck2= array();
		$exclude_time_start= array();
		$exclude_time_end= array();
		$sunday_count=0;
		$booked_arr=0;
			$db =& ConnectionManager::getDataSource("default");
			$truck_ids = array();
			$data = array();
			$trucks = $_REQUEST['trucks'];
			$fromDate = $_REQUEST['fromDate'];
			$fromDate_sc = $_REQUEST['sc_from'];
			$toDate_sc = $_REQUEST['sc_to'];
			$street = $_REQUEST['street_number'];
			$route = $_REQUEST['route'];
			$city = $_REQUEST['city'];
			$period_am = $_REQUEST['sc_period'];
			$choose_km=$_REQUEST['choose_km'];
			$choose_time=$_REQUEST['choose_time'];
			$customer_id=$_REQUEST['customer_id'];
			$period = "";
			$period_query="";
			if($period=='am'){
			$period_query="";	
			}
			else if($period=='pm'){
				$period_query="";
			}
			if(empty($fromDate_sc) && empty($toDate_sc) ){
			$date_array[] =	date('Y-m-d',strtotime($_REQUEST['fromDate']));
				
			}
			else {
			
			$date_array=$this->createDateRangeArray($fromDate_sc,$toDate_sc);	
			}
			

			
			
			$get_route_type = $_REQUEST['map_services'];
			
			if(!empty($trucks) && $trucks!="" ){
				
				$truck_ids[] = $trucks;
			}
			else {
				if($get_route_type=='123'){
					$route_query = "select id from ace_rp_inventory_locations where flagactive=0 and id!=40 order by id desc";
					
				}
				else {
					$route_query = "select id from ace_rp_inventory_locations where route_type=$get_route_type and id!=40 and flagactive=0 order by id desc";
					
				}
				
		      
				
			$result_route = $db->_execute($route_query);
			while($row_route = mysql_fetch_array($result_route, MYSQL_ASSOC)){
				$truck_ids[]=$row_route['id'];
				
			}
			}
			
		  
			
			$get_trucks = implode(',',$truck_ids);
			$fromDate = $_REQUEST['fromDate'];
			$get_date=date('Y-m-d',strtotime($fromDate));
			$postalCode = $_REQUEST['postal_code'];
			$result_ids = array();
			$result_arr=array();
			if($period!='pm'){
				
			
			foreach($date_array as $date_arrays){
				$get_st_time1 = array();
			$get_end_time1 = array();
				
			$duration123456=0;
			
			foreach($truck_ids as $val=> $truck_id){
				

				
				
			
				
			 $query="select s.id,s.job_time_beg,s.job_postal_code,s.customer_id,s.job_date,
				 s.job_truck,l.name from ace_rp_orders s join ace_rp_inventory_locations
				 l on (s.job_truck=l.id) where job_date='{$date_arrays}' $period_query
				 and job_truck ='$truck_id' and s.job_time_beg!='00:00:00' and s.job_time_end!='00:00:00'
				 and job_truck!=40 and l.flagactive=0 and s.order_status_id in (1,5)
				 ORDER BY job_time_end";
			    
	
			$result = $db->_execute($query);
			
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				{
					if($row['job_time_beg']!='08:00:00' && $row['job_time_beg']!='09:00:00'){
				
					
					$exclude_id[] = $row['job_truck'];
					$beg_time  = $row['job_time_beg'];
					$ex_time_beg = explode(':',$beg_time);
					if($ex_time_beg[0]=="13"){
						$ex_time_beg[0]="12";
					}
					if($ex_time_beg[0]=="15"){
						$ex_time_beg[0]="14";
					}
					if($ex_time_beg[0]=="11"){
						$ex_time_beg[0]="10";
					}
					$new_start_time = $ex_time_beg[0]-2;
					$new_end_time = $ex_time_beg[0];
					$postal1=trim($row['job_postal_code']);
					$customer=$row['customer_id'];
					if(empty($row['job_postal_code'])){
						$get_postal="select postal_code from ace_rp_customers where id='$customer'";
						$result111 = $db->_execute($get_postal);
						$row11 = mysql_fetch_array($result111, MYSQL_ASSOC);
						$postal1=$row11['postal_code'];
						
					}
					
			 $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$postal1."&destinations=".$postalCode."&mode=driving&language=en-EN&sensor=false&key=AIzaSyDUC73wk4-yrBlIKZOy7j1ya2_dv9MFiGw";
            
			$ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"http://hvacproz.ca/acesystem2018/distance_calculation.php");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,"URL=".urlencode($url));
            // receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec ($ch);//exit;
            
            
            $url2 = json_decode($response, true);
			
			

				$new_start_time11 = date("h", strtotime("$new_start_time:00"));
				
				$new_end_time11 = date("h", strtotime("$new_end_time:00"));
                       
                       $duration = ($url2['rows'][0]['elements'][0]['distance']['text']);
                       
                       $time = ($url2['rows'][0]['elements'][0]['duration']['text']);
					   
					   $time1 = ($url2['rows'][0]['elements'][0]['duration']['value']);
					   
					   $result_arr['html']= "<span>$new_start_time $am-$new_end_time $am1</span> <span>$time</span> <span>$duration</span> <span>route 2</span>";
					   
					   $result_arr['id'] = $row['id'];
					   $truck = $row['job_truck'];
					   $truck_name = $row['name'];
					  
					  $date  = $row['job_date'];	
                      $date1 = date('d M Y',strtotime($date));
					  $date2 = date('M d',strtotime($date));
					  
					  if($this->checkBooking($row['job_date'],$truck,$new_start_time,$new_end_time)){
						
						continue;
					  }
					  
					  
					  
					  if($this->checkSchedule($row['job_date'],$truck,$new_start_time,$new_end_time)){
						
						continue;
					  }
					  
					  	if($_REQUEST['choose_km']!="" && $_REQUEST['choose_time']!="" ){
			
			
			
			
				   if ($duration1 <= $_REQUEST['choose_km']*1000 && $time1 <= $_REQUEST['choose_time']*60  )
				   {
					 
				   }
				   else {
					   
					continue;
					
					
					
				   }
				
				 
	

			
		}
					  
					  
					  
					  if($new_start_time==8){
						$new_start_time="08";
					  }
					   
					   $href=BASE_URL."/orders/editBooking?customer_id=&job_truck=$truck&street=$street&route=$route&postal_code=$postalCode&job_time_beg=$new_start_time:00&job_time_end=$new_end_time:00&job_date=$date1&job_technician1_id=&job_technician2_id=&from_calender=1&map_service=$get_route_type&city1=$city&choose_km=$choose_km&$choose_time=&$choose_time&choose_time=$choose_time&preiod=$period_am&customer_id=$customer_id";
					   $result_ids[] = array(
					   //'html' => "<a href='$href'><span>$new_start_time11 - $new_end_time11</span><span>$date2</span><span>$time</span><span>$duration</span><span>$truck_name</span></a>",
					   'title'=>"Available  \n $new_start_time11 and $new_end_time11",
					   'textColor'=>$textcolor,
					   'sortBy'=>1,
					   'start' => $row['job_date']."T".$new_start_time.":00",
					   'end'=>$row['job_date']."T".$new_end_time.":00",
					   'duration'=>$duration*1000,
					   'time' => $time1,
					   'url'=>$href,
					   'date'=>$row['job_date']
    );
					   $exclude_date[]=$row['job_date'];
					   $exclude_id11[]=$row['job_date'];
						 $get_st_time1[]=$new_start_time;
						 $get_end_time1[]=$new_end_time;
						 
					    
				}
				}
			
	
		
			$sunday_count++;
				
			}
			$web_co=10;
			
			
					for ($i = 0; $i < 4; $i++){
					$start=$web_co-=2;
				    $end=$web_co+=2;
					if($start==8){
						$start="08";
				}
				
				$start1=$date_arrays."T$start:00:00";
					$end1=$date_arrays."T$end:00:00";
					
					$new_start_time11 = date("h", strtotime("$start:00"));
				
				    $new_end_time11 = date("h", strtotime("$end:00"));
					
					
					
					
					
					
						$result_ids[] = array(
					   
					   'title' =>"Not Available",
					   'sortBy'=>12,
					   'start'=>$start1,
					  
					   'end'=>$end1,
					   'textColor'=>'#B0B0B0',
					   'time'=>1,
					   'duration'=>1,
					   
    );
				
				
				
				$web_co+=2;
					}
			 			
			
		}
			} 
							
 
		
		    if($period!='am'){
				
			
			foreach($date_array as $date_arrays){
			
			foreach($truck_ids as $val => $truck_id){
				
				
			
				 $query="select s.id,s.job_time_end,s.job_postal_code,s.customer_id,s.job_date,
				 s.job_truck,l.name from ace_rp_orders s join ace_rp_inventory_locations
				 l on (s.job_truck=l.id) where job_date='{$date_arrays}' $period_query
				 and s.job_time_beg!='00:00:00' and s.job_time_end!='00:00:00' and s.order_status_id in (1,5)
				 and l.flagactive=0 and job_truck ='$truck_id' ORDER BY job_time_end";
				 
				 	
			$result = $db->_execute($query);
			$no_repeat_counter=0;
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				{
                    					
					if($row['job_time_end']!='20:00:00' && ($row['job_time_end']!='19:00:00') && ($row['job_time_end']!='16:00:00') && ($row['job_time_end']!='15:00:00') ){
						
			  
					$end_time  = $row['job_time_end'];
					$ex_time_end = explode(':',$end_time);
					$new_start_time = $ex_time_end[0];
					if($new_start_time==9){
						$new_start_time=10;
					}
					if($new_start_time==11){
						$new_start_time=12;
					}
					if($new_start_time==13){
						$new_start_time=14;
					}
					$new_end_time = $new_start_time+2;
					$postal1=trim($row['job_postal_code']);
					$customer=$row['customer_id'];
					if(empty($row['job_postal_code'])){
						$get_postal="select postal_code from ace_rp_customers where id='$customer'";
						$result111 = $db->_execute($get_postal);
						$row11 = mysql_fetch_array($result111, MYSQL_ASSOC);
						$postal1=$row11['postal_code'];
						
					}
					$query11 = "select count(*) cnt from ace_rp_orders
          where job_date = '".date("Y-m-d", strtotime($date_arrays))."'
          and job_truck  = '".$truck_id."'
          and ((job_time_beg >= '".$new_start_time.":00' and job_time_beg < '".$new_end_time.":00')
               or (job_time_end > '".(1+$new_start_time).":00' and job_time_end <= '".$new_end_time.":00'))
            AND order_status_id in (1,5)";
		
		$result11 = $db->_execute($query11);
      $row11 = mysql_fetch_array($result11);
 $bookedTrucks11 = $row11['cnt'];
	 if ($bookedTrucks11 > 0) {
        continue;
      }
		
            $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$postal1."&destinations=".$postalCode."&mode=driving&language=en-EN&sensor=false&key=AIzaSyDUC73wk4-yrBlIKZOy7j1ya2_dv9MFiGw";
            
			$ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"http://hvacproz.ca/acesystem2018/distance_calculation.php");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,"URL=".urlencode($url));
            // receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec ($ch);//exit;
            
            
            $url2 = json_decode($response, true);
			
			
						$duration = ($url2['rows'][0]['elements'][0]['distance']['text']);
                       
                       $time = ($url2['rows'][0]['elements'][0]['duration']['text']);
					   
					   $time1 = ($url2['rows'][0]['elements'][0]['duration']['value']);

						$new_start_time11 = date("h", strtotime("$new_start_time:00"));
						
						$new_end_time11 = date("h", strtotime("$new_end_time:00"));
                       
                       $duration = ($url2['rows'][0]['elements'][0]['distance']['text']);
					   
					   $duration1 = ($url2['rows'][0]['elements'][0]['distance']['value']);
                       
                       $time = ($url2['rows'][0]['elements'][0]['duration']['text']);
					   
					   $time1 = ($url2['rows'][0]['elements'][0]['duration']['value']);
					   
					   $result_arr['html']= "<span>$new_start_time $am-$new_end_time $am1</span> <span>$time</span> <span>$duration</span> <span>route 2</span>";
					   
					   $result_arr['id'] = $row['id'];
					   $truck = $row['job_truck'];
					   $truck_name = $row['name'];
					  
					  $date  = $row['job_date'];	
                      $date1 = date('d M Y',strtotime($date));
					  $date2 = date('M d',strtotime($date));
					  $start = $row['job_date']."T".$new_start_time.":00:00";
					  $end = $row['job_date']."T".$new_end_time.":00:00";
					  
					  if($this->checkBooking($row['job_date'],$truck,$new_start_time,$new_end_time)){
						continue;
					}
					
					if($this->checkSchedule($row['job_date'],$truck,$new_start_time,$new_end_time)){
		
					continue;
					
					}
					if($_REQUEST['choose_km']!="" && $_REQUEST['choose_time']!="" ){
			
			
			
			
				   if ($duration1 <= $_REQUEST['choose_km']*1000 && $time1 <= $_REQUEST['choose_time']*60  )
				   {
					  
				   }
				   else {
					continue;
				   }
				
	

			
		}
					   
					   $href=BASE_URL."/orders/editBooking?customer_id=&job_truck=$truck&street=$street&route=$route&postal_code=$postalCode&job_time_beg=$new_start_time:00&job_time_end=$new_end_time:00&job_date=$date1&job_technician1_id=&job_technician2_id=&from_calender=1&map_service=$get_route_type&city1=$city&choose_km=$choose_km&$choose_time=&$choose_time&choose_time=$choose_time&preiod=$period_am&customer_id=$customer_id";
					   $result_ids[] = array(
					   //'html' => "<a href='$href'><span>$new_start_time11 - $new_end_time11</span><span>$date2</span><span>$time</span><span>$duration</span><span>$truck_name</span></a>",
					   'title' =>"Available \n $new_start_time11 and $new_end_time11",
					   'sortBy'=>1,
					   'start'=>$start,
					   'end'=>$end,
					   'url'=>$href,
					   'textColor'=>$textcolor,
					   'time'=>$time1,
					   'duration'=>$duration1,
					   
    );
					   $exclude_id11[]=$row['job_date'];
						 $exclude_time_start[]=$new_start_time;
						 $exclude_time_end[]=$new_end_time;
					    
				}
				
				$counter = $new_end_time;
				
			
				$no_repeat_counter++;
				
			}
			
			
					$check_empty = "SELECT id,job_time_end,job_time_beg,job_postal_code,customer_id,job_date,
				 job_truck FROM `ace_rp_orders`
WHERE `job_date` = '$date_arrays' and job_truck=$truck_id and order_status_id in (1,5) order by id desc LIMIT 1";
		     $check=0;
			 $result_empty = $db->_execute($check_empty);
			 while($row = mysql_fetch_array($result_empty, MYSQL_ASSOC)){
				
				$check++;
				$web_co=10;
			/*time slots alailable for the day where there is no booking
			 *right now we are only showing first available i.e.8 to 10
			 *change it to 4 if you want 8 to 10 and 10 to 12 and 12 to 2
			 *and 2 to 4
			 */
			
					for ($i = 0; $i < 4; $i++){
					$start=$web_co-=2;
				    $end=$web_co+=2;
					if($start==8){
						$start="08";
				}
				
				$postal1=trim($row['job_postal_code']);
					$customer=$row['customer_id'];
					if(empty($row['job_postal_code'])){
						$get_postal="select postal_code from ace_rp_customers where id='$customer'";
						$result111 = $db->_execute($get_postal);
						$row11 = mysql_fetch_array($result111, MYSQL_ASSOC);
						$postal1=$row11['postal_code'];
						
					}
					$url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$postal1."&destinations=".$postalCode."&mode=driving&language=en-EN&sensor=false&key=AIzaSyDUC73wk4-yrBlIKZOy7j1ya2_dv9MFiGw";
            
			$ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"http://hvacproz.ca/acesystem2018/distance_calculation.php");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,"URL=".urlencode($url));
            // receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec ($ch);//exit;
            
            
            $url2 = json_decode($response, true);
			
			
						$duration = ($url2['rows'][0]['elements'][0]['distance']['text']);
                       
                       $time = ($url2['rows'][0]['elements'][0]['duration']['text']);
					   
					   $time1 = ($url2['rows'][0]['elements'][0]['duration']['value']);

						$new_start_time11 = date("h", strtotime("$new_start_time:00"));
						
						$new_end_time11 = date("h", strtotime("$new_end_time:00"));
                       
                       $duration = ($url2['rows'][0]['elements'][0]['distance']['text']);
					   
					   $duration1 = ($url2['rows'][0]['elements'][0]['distance']['value']);
                       
                       $time = ($url2['rows'][0]['elements'][0]['duration']['text']);
					   
					   $time1 = ($url2['rows'][0]['elements'][0]['duration']['value']);
					
					
if ($duration1 <= $_REQUEST['choose_km']*1000 && $time1 <= $_REQUEST['choose_time']*60  )
				   {
					  
				   }
				   else {
					continue;
				   }
					
					   
					
					
				$href=BASE_URL."/orders/editBooking?customer_id=&job_truck=$truck_id&street=$street&route=$route&postal_code=$postalCode&job_time_beg=$start:00&job_time_end=$end:00&job_date=$date_arrays&job_technician1_id=&job_technician2_id=&from_calender=1&map_service=$get_route_type&city1=$city&choose_km=$choose_km&$choose_time=&$choose_time&choose_time=$choose_time&preiod=$period_am&customer_id=$customer_id";
					$start1=$date_arrays."T$start:00:00";
					$end1=$date_arrays."T$end:00:00";
					
					$new_start_time11 = date("h", strtotime("$start:00"));
				
				    $new_end_time11 = date("h", strtotime("$end:00"));
					
					if($this->checkBooking($date_arrays,$truck_id,$start,$end)){
						 $web_co+=2;
						continue;
					}
					
					if($this->checkSchedule($date_arrays,$truck_id,$start,$end)){
		              $web_co+=2;
					continue;
					
					}
					
					
					
					
					/*	$result_ids[] = array(
					   
					   'title' =>"Available   \n $new_start_time11 and $new_end_time11",
					   'sortBy'=>3,
					   'start'=>$start1,
					   'url'=>$href,
					   'end'=>$end1,
					   'textColor'=>'#007bff',
					   'time'=>1,
					   'duration'=>1,
					   
    );*/
						
						
					
					 
					 $web_co+=2;
					}

			 }
			 
			 if($check>0){
				
				
			 }
			 else{
				 
				
				$sunday=0;
				if($this->checkSunday($date_arrays)){

					//it is a sunday so ignore
                        $web_co1=10;
						for ($i = 0; $i < 4; $i++){
					$start=$web_co1-=2;
				    $end=$web_co1+=2;
					if($start==8){
						$start="08";
				}
						$result_ids[] = array(
					   
					   'title' =>"Not Available",
					   'sortBy'=>2,
					   'start'=>$date_arrays."T$start:00:00",
					   
					   'end'=>$date_arrays."T$end:00:00",
					   'textColor'=>'#B0B0B0',
					   'time'=>1,
					   'duration'=>1,
					   
    );


				
$web_co1+=2;


						}
					
	
					
	$sunday++;
				}
				else {

    
			$web_co=10;
			/*time slots alailable for the day where there is no booking
			 *right now we are only showing first available i.e.8 to 10
			 *change it to 4 if you want 8 to 10 and 10 to 12 and 12 to 2
			 *and 2 to 4
			 */
			
					for ($i = 0; $i < 4; $i++){
					$start=$web_co-=2;
				    $end=$web_co+=2;
					if($start==8){
						$start="08";
				}
				
				$postal1=trim($row['job_postal_code']);
					$customer=$row['customer_id'];
					if(empty($row['job_postal_code'])){
						$get_postal="select postal_code from ace_rp_customers where id='$customer'";
						$result111 = $db->_execute($get_postal);
						$row11 = mysql_fetch_array($result111, MYSQL_ASSOC);
						$postal1=$row11['postal_code'];
						
					}
					
					

					
					   
					
					
				$href=BASE_URL."/orders/editBooking?customer_id=&job_truck=$truck_id&street=$street&route=$route&postal_code=$postalCode&job_time_beg=$start:00&job_time_end=$end:00&job_date=$date_arrays&job_technician1_id=&job_technician2_id=&from_calender=1&map_service=$get_route_type&city1=$city&choose_km=$choose_km&$choose_time=&$choose_time&choose_time=$choose_time&preiod=$period_am&customer_id=$customer_id";
					$start1=$date_arrays."T$start:00:00";
					$end1=$date_arrays."T$end:00:00";
					
					$new_start_time11 = date("h", strtotime("$start:00"));
				
				    $new_end_time11 = date("h", strtotime("$end:00"));
					
					if($this->checkBooking($date_arrays,$truck_id,$start,$end)){
						 $web_co+=2;
						continue;
					}
					
					if($this->checkSchedule($date_arrays,$truck_id,$start,$end)){
		           $web_co+=2;
					continue;
					
					}
					
					
					
					
						$result_ids[] = array(
					   
					   'title' =>"Available \n $new_start_time11 and $new_end_time11",
					   'sortBy'=>3,
					   'start'=>$start1,
					   'url'=>$href,
					   'end'=>$end1,
					   'textColor'=>'#007bff',
					   'time'=>1,
					   'duration'=>1,
					   
    );
						
						
					
					 
					 $web_co+=2;
					}
	
					

						
				}
				
			 }
			 
			
				 
				 
				 
				  $query_all="select s.id,s.job_time_beg,s.job_time_end,s.job_postal_code,s.customer_id,s.job_date,s.job_truck,l.name
				 from ace_rp_orders s join ace_rp_inventory_locations l on (s.job_truck=l.id)
				 where job_date='{$date_arrays}' and s.job_time_beg!='00:00:00'
				 and s.job_time_end!='00:00:00' and l.flagactive=0 and job_truck ='$truck_id'
				 and order_status_id in (1,5) ORDER BY job_time_end DESC";
				 
				 $result_all = $db->_execute($query_all);
				 
				 while($row = mysql_fetch_array($result_all, MYSQL_ASSOC))
				{
		
					
					$start_time=  $row['job_time_beg'];
					$ex_start_time = explode(':',$start_time);
					$end_time  = $row['job_time_end'];
					$ex_time_end = explode(':',$end_time);
					$new_start_time = $ex_start_time[0];
					$new_end_time = $ex_time_end[0];
					$postal1=trim($row['job_postal_code']);
					$customer=$row['customer_id'];
					if(empty($row['job_postal_code'])){
						$get_postal="select postal_code from ace_rp_customers where id='$customer'";
						$result111 = $db->_execute($get_postal);
						$row11 = mysql_fetch_array($result111, MYSQL_ASSOC);
						$postal1=$row11['postal_code'];
						
					}
		
            
            
            $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$postal1."&destinations=".$postalCode."&mode=driving&language=en-EN&sensor=false&key=AIzaSyDUC73wk4-yrBlIKZOy7j1ya2_dv9MFiGw";
            
			$ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"http://hvacproz.ca/acesystem2018/distance_calculation.php");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,"URL=".urlencode($url));
            // receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec ($ch);//exit;
            
            
            $url2 = json_decode($response, true);

						$new_start_time11 = date("h", strtotime("$new_start_time:00"));
						
						$new_end_time11 = date("h", strtotime("$new_end_time:00"));
                       
                       $duration = ($url2['rows'][0]['elements'][0]['distance']['text']);
					   
					    $duration1 = ($url2['rows'][0]['elements'][0]['distance']['value']);
                       
                       $time = ($url2['rows'][0]['elements'][0]['duration']['text']);
					   
					   $time1 = ($url2['rows'][0]['elements'][0]['duration']['value']);
					   
					   $result_arr['html']= "<span>$new_start_time $am-$new_end_time $am1</span> <span>$time</span> <span>$duration</span> <span>route 2</span>";
					   
					   $result_arr['id'] = $row['id'];
					   $truck = $row['job_truck'];
					   $truck_name = $row['name'];
					  
					  $date  = $row['job_date'];	
                      $date1 = date('d M Y',strtotime($date));
					  $date2 = date('M d',strtotime($date));
					  $start = $row['job_date']."T".$new_start_time.":00:00";
					  $end = $row['job_date']."T".$new_end_time.":00:00";
					  
				
					
					
					   
					 
					   $result_ids[] = array(
					   'title' =>"Booked",
					   'sortBy'=>10,
					   'start'=>$start,
					   'end'=>$end,
					   
					   'color'=>'#B0B0B0',
					   'textColor'=>'#B0B0B0',
					   'time'=>1,
					   'duration'=>1,
    );
					  
					   
					  
					  
				
					    
				
				
			}
			
			   
		
			}
			$web_co=10;
			
			
					for ($i = 0; $i < 4; $i++){
					$start=$web_co-=2;
				    $end=$web_co+=2;
					if($start==8){
						$start="08";
				}
				
				$start1=$date_arrays."T$start:00:00";
					$end1=$date_arrays."T$end:00:00";
					
					$new_start_time11 = date("h", strtotime("$start:00"));
				
				    $new_end_time11 = date("h", strtotime("$end:00"));
					
					
					
					
					
					
						$result_ids[] = array(
					   
					   'title' =>"Not Available",
					   'sortBy'=>12,
					   'start'=>$start1,
					   
					   'end'=>$end1,
					   'textColor'=>'#B0B0B0',
					   'time'=>1,
					   'duration'=>1,
					   
    );
				
				
				
				$web_co+=2;
					}
			
			}
			} 
			
			
			if(empty($result_arr)){
				$result_arr['id']='0';
				$result_arr['html']= "no booking found";
			}
			
			
			
		function cmp($a, $b) {
        return $a["time"] - $b["time"];
		}
		//sort according to the time taken
		usort($result_ids, "cmp");
		
		
	$data=$result_ids;
		if(isset($_REQUEST['choose_km']) && $_REQUEST['choose_km']!="" ){
			
			foreach ($result_ids as $key => $item) 
				{
				   if ($item['duration'] <= $_REQUEST['choose_km']*1000 )
				   { 
					   $data[] = array(
									    'title' => $item['title'],
									 
									   'duration'=>$item['duration'],
									   'sortBy'=>$item['sortBy'],
									  'start' => $item['start'],
									  'time'=>$item['time'],
									  'end'=>$item['end'],
									  'url'=>$item['url'],
									  'textColor'=>$item['textColor'],
			   );
				   }
				}
				
				
				
				
				   }
				   else if($_REQUEST['choose_km']!="") {
					
					   
					   $data = $result_ids;
					   
				   }
		
		if($_REQUEST['choose_km']=="" || $_REQUEST['choose_time']!="" ){
			
		
		
	if(isset($_REQUEST['choose_time']) && $_REQUEST['choose_time']!="" ){
	
			foreach ($result_ids as $key => $item) 
 {
    if ($item['time'] <= $_REQUEST['choose_time']*60 )
    {
        $data[] = array(
									  'title' => $item['title'],
									   'duration'=>$item['duration'],
									   'sortBy'=>$item['sortBy'],
									  'start' => $item['start'],
									  'time'=>$item['time'],
									  'end'=>$item['end'],
									  'url'=>$item['url'],
									  'textColor'=>$item['textColor'],
    );
    }
 }
	}
	else {
		
		$data = $result_ids;
		
	}
	
		}
	
		
		
		
		
		
		//echo "<pre>";
		//print_r($data);
		//die();
		

		$this->set('data', $data);
		
		$this->set('start_date', $_REQUEST['sc_from']);
		
		$this->set('end_date', $_REQUEST['sc_to']);
		
		$this->set('allTypes', $this->Lists->ListTable('ace_rp_route_types'));
			
			
			
		}
		
		public function checkSunday($date){
			//check if the given date is a sunday
			$MyGivenDateIn = strtotime($date);
        $ConverDate = date("l", $MyGivenDateIn);
        $ConverDateTomatch = strtolower($ConverDate);
        
        if($ConverDateTomatch == "sunday"){
			return true;
            
        } else {
           return false; 
        }
		}
		
		public function checkTheBooking($date,$truck){
			$db =& ConnectionManager::getDataSource("default");
			$query="select count(*) cnt from ace_rp_orders where job_date={$date} and  job_truck={$truck} and order_status_id in (1,5)";
			
		}
		
		public function checkBooking($date,$truck,$start,$end){
			$db =& ConnectionManager::getDataSource("default");
			/*$query11 = "select count(*) cnt from ace_rp_orders
          where job_date = '".date("Y-m-d", strtotime($date))."'
          and job_truck  = '".$truck."'
          and ((job_time_beg >= '".$start.":00' and job_time_beg < '".$end.":00')
               or (job_time_end > '".(1+$start).":00' and job_time_end <= '".$end.":00'))
            AND order_status_id in (1,5)";*/
					
					$query11 = "
			SELECT count(*) as cnt
FROM ace_rp_orders
WHERE
     job_date = '".date("Y-m-d", strtotime($date))."'
          and job_truck  = '".$truck."' and
    (
       (job_time_end > '$start:00' and job_time_beg < '$end:00')
	   
	      )
            AND order_status_id in (1,5)";
					
				
		
		$result11 = $db->_execute($query11);
      $row11 = mysql_fetch_array($result11);
 $bookedTrucks11 = $row11['cnt'];
	 if ($bookedTrucks11 > 0) {
       return true;
      }
	  else {
		return false;
	  }
			
		}
		
		public function checkSchedule($date,$truck,$start,$end){
			
			$db =& ConnectionManager::getDataSource("default");
			 $get_tech = "select tech1_day1 from ace_rp_inventory_locations where id=$truck and flagactive=0";
			$result = $db->_execute($get_tech);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$tech = $row['tech1_day1'];
			$get_date=explode('-',$date);
			$year=$get_date[0];
			$month=$get_date[1];
			$day=$get_date[2];
			$get_sc = "select * from ace_rp_tech_schedule where tech_id=$tech and year=$year and month=$month and day=$day";
			$result_sc = $db->_execute($get_sc);
			while($row = mysql_fetch_array($result_sc, MYSQL_ASSOC))
				{
					$start_time = $row['start_time'];
					$end_time = $row['end_time'];
					if($end_time > $start && $end > $start_time){
						return true;
					}
					else {
						return false;
					    
					}
				}
		}
		public function calender3(){
			echo '<div style="margin: 0 auto;
    display: table;
    margin-top: 10%"><img src="http://i.imgur.com/KUJoe.gif"><br>please wait loading</div>';
			
		}
		public function save_acecare_booking(){
			$db =& ConnectionManager::getDataSource("default");
			$currentDate = date('Y-m-d');
			$href = $_REQUEST['href'];
			parse_str($_REQUEST['query_string'], $get_array);
			parse_str($href, $get_array1);
			$job_date=$get_array1['job_date'];
			$job_time_beg=$get_array1['job_time_beg'];
			$street_number=$get_array1['street_number'];
			$route=$get_array1['route'];
			$city=$get_array1['route'];
			$job_time_end=$get_array1['job_time_end'];
			$job_time = $job_time_beg." to ".$job_time_end;
			if($get_array['map_services']==1){
             $job_type="Service";
			}
			if($get_array['map_services']==2){
             $job_type="Airduct";
			}
			if($get_array['map_services']==3){
             $job_type="Repair";
			}
			if($get_array['map_services']==4){
              $job_type="Installation";
			}
			if($get_array['map_services']==5){
              $job_type="Estimation";
			}
			$query = "INSERT INTO rp_acacare_ca_booking (first_name,last_name, phone, email, address, job_type,
			job_date,job_time,
        coupon, post_code,link, message,street_number,route,city,created_at) 
			VALUES ('".$get_array['first_name']."','".$get_array['last_name']."', '".$get_array['phone']."',
      '".$get_array['email']."','".$get_array['p_code']."','".$job_type."','".$job_date."','".$job_time."', '".$get_array['coupon']."',
      '".$get_array['postal_code']."','".$href."','".$get_array['subject']."','".$get_array['street_number']."','".$get_array['route']."','".$get_array['city']."','$currentDate')";
            $result = $db->_execute($query);
			$to="acecare88@gmail.com";
			$subject="new ace care booking";
			$body="Hi Admin, <br> Please find the customers feedback. <br><br>
			<label>Name:</label> ".$get_array['first_name']." ".$get_array['last_name']."<br><br>
			<label>Email:</label> ".$get_array['email']." <br><br>
			<label> Cell Phone:</label> ".$get_array['phone']." <br><br> 
			<label> Address:</label>".$get_array['p_code'] ."<br><br> 
			<label> Coupon:</label>".$get_array['coupon'] ."<br><br>
			<label> Message:</label>".$get_array['subject'] ."<br><br>
						";
			
			$ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"http://hvacproz.ca/acesystem2018/mailcheck.php");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,"TO=".$to."&SUBJECT=".$subject."&BODY=".urlencode($body));
            // receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $msgid = curl_exec ($ch);//exit;
            curl_close ($ch);
            
			exit;
		}
		
		public function getdistance(){
			
			$db =& ConnectionManager::getDataSource("default");
			$query = "select job_postal_code from ace_rp_orders where job_technician1_id='231355' and job_date='2020-11-25'";
			$result = $db->_execute($query);
			$post_codes= array();
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				{
					$post_codes[] =  $row['job_postal_code'];
					    
				}
				array_unshift($post_codes , '452001');
				//echo "<pre>";
				//print_r($post_codes);
				//die();
				?>
				<?php $previousWeight = 0;
				$check=0;
				?>
		
						<?php foreach($post_codes as $post_code) {
							
							?>
							<tr>
							<td>
								<?php
								if(!empty($post_codes[$previousWeight+1])){
                        //echo $post_codes[$previousWeight]." and ".$post_codes[$previousWeight+1]."<br>";									
								}

									
										
									
									
								?>
							</td>
							</tr>
						<?php
						$previousWeight++;
						$check++;
						} ?>
           <?php
		   
		   $this->set('post_codes', $post_codes);
		   $this->set('date', '2021-05-05');
				
		}
	
}

?>