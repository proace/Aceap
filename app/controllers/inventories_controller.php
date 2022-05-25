<? ob_start();
//error_reporting(1);
#define("DEBUG",2);
class Queries {
	function __construct($controller) {
		$this->controller = $controller;
		$this->connection = &ConnectionManager::getDataSource($controller->User->useDbConfig); }
	function listStartingLocations() {
	}
	function listAllDocuments($from='',$to='') {
		// return an array of all info about documents from $from to $to
		$dateConditions = "WHERE 1=1 ";
		if($from != '')
			$dateConditions .= " AND movements.date >= '".$this->controller->Common->getMysqlDate($from)." 00:00:00' "; 
		if($to != '')
			$dateConditions .= " AND movements.date <= '".$this->controller->Common->getMysqlDate($to)." 23:59:59' ";
		
		$query = "SELECT movements.id, movements.date, movements.asset_id,
					tolocation.id AS to_location_id, tolocation.type AS to_location_type, tolocation.number AS to_location_number,
					fromlocation.id AS from_location_id, fromlocation.type AS from_location_type, fromlocation.number AS from_location_number,
					invoice.invoice AS invoice_number
					
					FROM ace_iv_movements movements
					
					LEFT JOIN ace_iv_locations tolocation ON movements.to_location_id = tolocation.id
					LEFT JOIN ace_iv_locations fromlocation ON movements.from_location_id = fromlocation.id
					LEFT JOIN ace_iv_invoice invoice ON movements.id = invoice.invoice_id
					
					{$dateConditions} 
					
					GROUP BY movements.id order by movements.date desc";
		$response = array();
		$results = $this->connection->_execute($query);
		while($row = mysql_fetch_assoc($results)) {
			$response[] = $row; }
		return $response; }
	function singleInvoiceFromID($id) {
		$query = "SELECT invoice FROM ace_iv_invoice WHERE invoice_id = '$id'";
		$results = $this->connection->_execute($query);
		$row = mysql_fetch_assoc($results);
		if ($row) return $row["invoice"];
		return False; }
	function SearchParts($needle) {
		$limit = 15;
		$query = "SELECT i.id id, i.name name, i.selling_price price
			FROM ace_iv_items i
			LEFT JOIN ace_iv_suppliers s 
			ON i.iv_supplier_id = s.id
			WHERE 
				(i.name LIKE \"%$needle%\"
				OR i.model LIKE \"%$needle%\")
			LIMIT $limit";
		$results = $this->connection->_execute($query);
		$response = array();
		while ($row = mysql_fetch_assoc($results)) {
			$response[] = $row; }
		return $response;
	}
	// MZ:
	// Function same as SearchParts, with modification to only return active items.
	/*function SearchActiveParts($needle) {
		$limit = 15;
	

		// $query = "SELECT i.id id,i.brand,i.model, i.name name, i.selling_price price, i.supplier_price purchase_price, i.category_id, i.sku, i.sub_category_id
		// 	FROM iv_items_labeled2 i
		// 	JOIN ace_iv_sub_categories sub ON i.sub_category_id  = sub.id
		// 	WHERE active = 1 and sub.name != 'Inactive' and i.name LIKE \"%$needle%\"";

		$query = "SELECT i.id id,i.brand,i.model, i.name name, i.selling_price price, i.supplier_price purchase_price, i.category_id, i.sku, i.sub_category_id, techItem.quantity
			FROM iv_items_labeled2 i
			JOIN ace_iv_sub_categories sub ON i.sub_category_id  = sub.id  
			LEFT JOIN ace_rp_tech_inventory_item techItem ON i.id  = techItem.item_id AND techItem.tech_id = 0
			WHERE active = 1 and sub.name != 'Inactive' and i.name LIKE \"%$needle%\"";

		$results = $this->connection->_execute($query);
		$techArr = $this->Lists->inventoryTech();
		$response = array();
		while ($row = mysql_fetch_assoc($results)) {
				$response[] = $row;
			 }
		echo "<pre>";
		print_r($response); die;
		return $response;
	}*/
	function SearchPartsFromSuppliers($needle,$supplier_id) {
		$limit = 15;
		$query = "SELECT *, i.id id, i.name name, i.selling_price price, sk.sku sku
			FROM ace_iv_items i
			LEFT JOIN ace_iv_sku sk ON i.id = sk.item_id
			LEFT JOIN ace_iv_locations l ON sk.supplier_id = l.id
			LEFT JOIN ace_rp_suppliers s ON l.number = s.id
			WHERE 
			l.type = \"Supplier\"
			AND s.id = \"$supplier_id\"
			AND
			(
			    i.name LIKE \"%$needle%\"
			    OR i.model LIKE \"%$needle%\"
			)
			ORDER BY i.id DESC
			LIMIT $limit";
		$results = $this->connection->_execute($query);
		$response = array();
		while ($row = mysql_fetch_assoc($results)) {
			$response[] = $row; }
		return $response;
	}
		
	
}

class InventoriesController extends AppController
{
	//To avoid possible PHP4 problems
	var $name = "Inventories";

	var $uses = array('Item', 'User', 'InventoryLocation', 'InventoryState', 'InventoryChange', 'ItemCategory', 'Inventory', 'IvItem','TechInventoryItem');

	var $helpers = array('Time','Ajax','Common');
	var $components = array('HtmlAssist', 'RequestHandler','Common','Lists');

	var $itemsToShow=10;
	var $pagesToDisplay=10;
	var $beforeFilter = array('checkAccess');


	function getInventoryTechQty($item_id)
	{
		$data = $this->TechInventoryItem->findAll(array('TechInventoryItem.item_id' => $item_id),array('tech_id','quantity'));
		return $data;
	}

	function SearchActiveParts($needle) {
		$limit = 15;
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "SELECT i.id id,i.brand,i.model, i.name name, i.selling_price price, i.supplier_price purchase_price, i.category_id, i.sku, i.sub_category_id
			FROM iv_items_labeled2 i
			JOIN ace_iv_sub_categories sub ON i.sub_category_id  = sub.id
			WHERE active = 1 and sub.name != 'Inactive' and i.name LIKE \"%$needle%\"";

		// $query = "SELECT i.id id,i.brand,i.model, i.name name, i.selling_price price, i.supplier_price purchase_price, i.category_id, i.sku, i.sub_category_id, techItem.quantity
		// 	FROM iv_items_labeled2 i
		// 	JOIN ace_iv_sub_categories sub ON i.sub_category_id  = sub.id  
		// 	LEFT JOIN ace_rp_tech_inventory_item techItem ON i.id  = techItem.item_id AND techItem.tech_id = 0
		// 	WHERE active = 1 and sub.name != 'Inactive' and i.name LIKE \"%$needle%\"";

		// $query = "SELECT i.id id,i.brand,i.model, i.name name, i.selling_price price, i.supplier_price purchase_price, i.category_id, i.sku, i.sub_category_id, techItem.quantity, group_concat(techItem.tech_id,'-',techItem.quantity) as tech_id
		// 	FROM iv_items_labeled2 i
		// 	JOIN ace_iv_sub_categories sub ON i.sub_category_id  = sub.id  
		// 	LEFT JOIN ace_rp_tech_inventory_item techItem ON i.id  = techItem.item_id 
		// 	WHERE active = 1 and sub.name != 'Inactive' and i.name LIKE \"%$needle%\" group by i.id"; 

		$results = $db->_execute($query);
		$techArr = $this->Lists->inventoryTech();
		$response = array();
		while ($row = mysql_fetch_assoc($results)) {
				$row['tech_data'] = $this->getInventoryTechQty($row['id']);	
				$response[] = $row;
			 }
		$response['tech'] = $techArr;
		return $response;
	}
	function checkAccess() {

		// We must authenticate the user manually,
		// Current implementation does not use CakePHP's authentication framework.. <- boo!
		// If user is not logged in, redirect them to login page.
		$user_id = $this->Common->getLoggedUserID();
		if (!$user_id || $user_id == '') {
			$this->redirect('login');
    	}
    	// Get the user's role.
    	/*$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$sql = "SELECT r.role_id FROM ace_rp_users_roles r, ace_rp_users u WHERE r.user_id=u.id AND u.id=$user_id;";
		$result = $db->_execute($sql);
		if (mysql_num_rows($result) > 1) {
			header('Status: 500 Server Error');
			exit;
		}
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$this->role_id = $row['role_id'];*/

		// See wiki under 'User Roles' for mappings..
		$this->Common->checkRoles(array(1,2,3,4,5,6,10,11,13,14));
	}
  
	function index_old()
	{
		$this->layout="list";
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		//if (!$order) $order = 'd.docdate desc';
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);	
		
		$allDocTypes = $this->GetDocTypes();
		$allSuppliers = $this->Lists->ListTable('ace_rp_suppliers');
		$allLocations = $this->GetLocations();
		
		// Defaults
		$query = "select * from ace_rp_inventory_default d";
		
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$items[$row['name']]['name'] = $row['name'];
		  	$items[$row['name']]['default'] += 1*$row['qty'];
		}

		// Inventory tracking setup
		$query = "select * from ace_rp_inventory_start";
		
		$result = $db->_execute($query);
		$num=0;
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$items[$row['name']]['item_id'] = $row['item_id'];
			$items[$row['name']]['part_number'] = $row['part_number'];
			$items[$row['name']]['name'] = $row['name'];
		  	$items[$row['name']][0] = 1*$row['qty'];
		  	$items[$row['name']]['show'] = $row['show'];
			$start_date = $row['date_doc'];
		}
		
		// Income
		$query = "select d.tech_id location, i.name part, i.part_number, sum(if (d.opertype=5,-i.qty,i.qty)) qty
								from ace_rp_inventory_docitems i, ace_rp_inventory_documents d
							 	where d.doc_id=i.doc_id and d.docdate>'$start_date'
							 	group by d.tech_id, i.name";
		
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			if (!$items[$row['part']]['part_number'])
					$items[$row['part']]['part_number'] = $row['part_number'];
					
			$items[$row['part']]['name'] = $row['part'];
		  	$items[$row['part']][$row['location']] += 1*$row['qty'];
		}
		// Expences
		$query = "select d.supplier_id location, it.name part, it.part_number, sum(i.qty) qty
								from ace_rp_inventory_docitems i, ace_rp_inventory_documents d, ace_rp_items it
							 where d.doc_id=i.doc_id and d.opertype!=1 and d.opertype!=4 and d.opertype!=5
								and it.id=i.item_id and d.docdate>'$start_date'
							 group by d.tech_id, it.name, it.part_number";
		
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			if (!$items[$row['part']]['part_number'])
				$items[$row['part']]['part_number'] = $row['part_number'];
				
			$items[$row['part']]['name'] = $row['part'];
		 	$items[$row['part']][$row['location']] -= 1*$row['qty'];
		}
		
		// Sales
		$query = "select d.job_technician1_id location, it.name part, it.part_number, sum(i.quantity) qty
								from ace_rp_order_items i, ace_rp_orders d, ace_rp_items it
							 where d.id=i.order_id and it.is_appliance=2 and it.id=i.item_id and d.job_date>'$start_date'
							 group by d.job_technician1_id, it.name, it.part_number";
		
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			if (!$items[$row['part']]['part_number'])
				$items[$row['part']]['part_number'] = $row['part_number'];
			$items[$row['part']]['name'] = $row['part'];
		  	$items[$row['part']][$row['location']] -= 1*$row['qty'];
		}
		
		$this->set('items', $items);
		$this->set('locations', $allLocations);
	}
	
	function index()
	{
		$this->layout= 'blank';
		$allTechnicians = $this->Lists->Technicians();
		$items = $this->Inventory->getTechInventory();
		$stock = $this->Inventory->getWarehouseInventory('wh_');
		$items['Warehouse'] = $stock['Warehouse'];
		$this->set('items', $items);
		$this->set('allTechnicians', $allTechnicians);
	}
	
	function GetLocations(){
		$aRet = $this->Lists->Technicians(true);
		
		//In case we have to deal with extra warehouses later
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);	
		$query = "SELECT id, type, number FROM ace_iv_locations WHERE type = 'Warehouse'";
		$result = $db->_execute($query);
		$count = mysql_num_rows($result);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			if ($count == 1)
				$aRet[$row['type'] . ':' . $row['number']] = $row['type'];
			else
				$aRet[$row['type'] . ':' . $row['number']] = $row['type'] . ' ' . ($row['number']+1);
		}
		//---
		$aRet["Transit:1"] = "Transit";
		ksort($aRet);
		return $aRet;
	}
	function GetTechs(){
		$aRet = $this->Lists->Technicians(true);
		ksort($aRet);
		return $aRet;
	}
	
	function GetDocTypes()
	{
		$aRet = array(1 => 'Supply', 2 => 'Movement', 3 => 'Expences', 4 => 'Adjustment', 5 => 'Refund');
		return $aRet;
	}
	
	function GetAllStatuses()
	{
		$aRet = array(0 => 'Deleted', 1 => 'Requested', 2 => 'Ordered');
		return $aRet;
	}
	
	function AllDocuments()
	{
		$this->layout="list";
		$queries = new Queries($this);
		
		if ($this->params['url']['ffromdate'] != '')
			$fdate = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
		else
			$fdate = date("Y-m-d");

		if ($this->params['url']['ftodate'] != '')
			$tdate = date("Y-m-d", strtotime($this->params['url']['ftodate']));
		else
			$tdate = date("Y-m-d");
		$allDocs = $queries->listAllDocuments($fdate,$tdate);
		
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		if (!$order) $order = 'movements.date desc';
		
		$allDocTypes = $this->GetDocTypes();
		$allSuppliers = $this->Lists->ListTable('ace_rp_suppliers');
		$allLocations = $this->GetLocations();
		
		$items = array();
		if (DEBUG) {
			echo "<pre>";print_r($allDocs);echo "</pre>"; }
		foreach($allDocs as $row) {
			$item = array();
			
			
			
			// determine where items are coming from
			switch ($row['from_location_type']) {
				case 'Supplier':
					$item['supplier_name'] = $allSuppliers[$row['from_location_number']];
					break;
				case 'User':
					$item['supplier_name'] = $allLocations[$row['from_location_number']];
					break;
				case 'Warehouse':
					$item['supplier_name'] = $allLocations['Warehouse:' . $row['from_location_number']];
					break;
				case 'Transit':
					$item['supplier_name'] = "In Transit";
					break;
				default:
					$item['supplier_name'] = $row['from_location_type'];
					break; }
			
			// determine type of transaction
			if ($row['from_location_type'] == 'Supplier'){
				$item['opertype_name'] = 'Purchase From Supplier';
			}elseif ($row['to_location_type'] == 'Customer'){
				$item['opertype_name'] = 'Sold to Customer';
			}elseif ($row['to_location_type'] == 'Supplier'){
				$item['opertype_name'] = 'Refund to Supplier';
			}elseif ($row['to_location_type'] == 'User'){
				$item['opertype_name'] = 'Internal Transfer';
			}elseif ($row['to_location_type'] == 'Warehouse'){
				$item['opertype_name'] = 'Internal Transfer';
			}else{
				$item['opertype_name'] = 'Misc';
			}
			
			$item['docdate'] = date('Y-m-d', strtotime($row['date']));
			$item['doc_id'] = $row['id'];
			
			// get name of tech / warehouse / supplier
			if ($row['to_location_type'] == 'User'){ // techs
				$item['tech_name'] = $allLocations[$row['to_location_number']];
			}
			else if ($row['to_location_type'] == 'Warehouse'){ // warehouse
				$item['tech_name'] = $allLocations['Warehouse:' . $row['to_location_number']];
			}
			else if ($row['to_location_type'] == 'Supplier'){ // suppliers
				$item['tech_name'] = $allSuppliers[$row['to_location_number']];
			}
			else if ($row['to_location_type'] == 'Transit'){
				$item['tech_name'] = "Transit";
			}
			else if ($row['to_location_type'] == 'Customer'){
				
				$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
				$query = "SELECT * FROM ace_iv_movements movements
						  LEFT JOIN ace_iv_locations locations ON movements.to_location_id = locations.id
						  LEFT JOIN ace_rp_customers customers ON locations.number = customers.id
						  WHERE movements.id = {$item['doc_id']}
						  AND locations.type = 'Customer'
						  LIMIT 0,1";
				$result = $db->_execute($query);
				if (mysql_num_rows($result) > 0){
					$row = mysql_fetch_array($result, MYSQL_ASSOC);
					$item['tech_name'] = "Customer - {$row['first_name']} {$row['last_name']}";
				}
				else {
					$item['tech_name'] = "Customer - Error";
				}
				
			}
			else {
				$item['tech_name'] = "Unknown";
			}
			
			//------------
			//Invoice
			$item['invoice_number'] = $queries->singleInvoiceFromID($row['id']);
			$items[] = $item;
		}
		
		$this->set('documents', $items);
		$this->set('locations', $allLocations);
		$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));
		$this->set('next_fdate', date("d M Y", strtotime($fdate) + 24*60*60));
		$this->set('prev_tdate', date("d M Y", strtotime($tdate) - 24*60*60));
		$this->set('next_tdate', date("d M Y", strtotime($tdate) + 24*60*60));
		$this->set('fdate', date("d M Y", strtotime($fdate)));
		$this->set('tdate', date("d M Y", strtotime($tdate)));
	}
	
	function supplies(){
		$this->layout="list";
		$userRole = $this->Common->getLoggedUserRoleID();
		$userID = $this->Common->getLoggedUserID();

		if ($this->params['url']['ffromdate'] != '')
			$fdate = date("Y-m-d", strtotime($this->params['url']['ffromdate']));
		else
			$fdate = date("Y-m-d");

		if ($this->params['url']['ftodate'] != '')
			$tdate = date("Y-m-d", strtotime($this->params['url']['ftodate']));
		else
			$tdate = date("Y-m-d");
		$paidDate = date("Y-m-d");
		$search_supplier = $this->params['url']['search_supplier'];
		$search_invoice_number = $this->params['url']['search_invoice_number'];
		// $search_refunds = $this->params['url']['search_refunds'];
		// $search_paid = $this->params['url']['search_paid'];
		$search_invoice = !empty($this->params['url']['search_invoice']) ? $this->params['url']['search_invoice']:0;
		$search_active = $this->params['url']['search'];
		
		$price_id = $this->params['url']['id'];
		$status_id = $this->params['url']['status_id'];
		
		
		$sqlConditions = "";
		if($userRole == 1){
			$sqlConditions .= " AND invoice.created_by = ".$userID."";
		}
		if ($search_supplier && strlen($search_supplier) > 0){
			$sqlConditions .= " AND suppliers.name LIKE '%{$search_supplier}%' ";
		}
		
		if ($search_invoice_number && strlen($search_invoice_number) > 0){
			$sqlConditions .= " AND invoice.invoice LIKE '%{$search_invoice_number}%' ";
		}		
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		//Paid
		if ($status_id == 5 && $price_id > 0){
			$query = "
				UPDATE ace_iv_invoice SET status_id = {$status_id} WHERE invoice_id = '{$price_id}'
			";
			$db->_execute($query);
		}
		
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		if (!$order) $order = 'd.docdate desc';
		
		$allDocTypes = $this->GetDocTypes();
		$allSuppliers = $this->Lists->ListTable('ace_rp_suppliers');
		$allLocations = $this->GetLocations();
		
		$query = "select *
								from ace_rp_inventory_documents d, ace_rp_inventory_docitems i
							 where d.flagactive=1 and i.doc_id=d.doc_id
							   and (d.opertype=1 or d.opertype=5) $sqlConditions
							 order by ".$order.' '.$sort;
							 
			
		$dateConditions = "";				 
		if($fdate != '')
			$dateConditions .= " AND movements.date >= '".$this->Common->getMysqlDate($fdate)." 00:00:00'"; 
		if($tdate != '')
			$dateConditions .= " AND movements.date <= '".$this->Common->getMysqlDate($tdate)." 23:59:59'";
		
		if($search_invoice == 0){
			$searchCondition = '';
		} else {
			$searchCondition = 'AND invoice.status_id ='.$search_invoice;
		}
		// if(empty($search_invoice) || $search_invoice == 'Invoices')
		// {

			// FIND INVOICES
			// query assumes an asset can only come from a supplier once
			// select info for each invoice, as well as SUM for all items in that invoice.
			// we use GROUP BY which selects all items in an invoice then groups them together in a single row, which allows us to add
			// the price for all those items into total_price for that invoice
			
			/*$query = "SELECT ord.id as org_ord_id,ord.invoice_id as invid,invoice.po_number,invoice.order_id, invoice.remaining_amount, invoice.doc_type, invoice.invoice AS invoice_number, movements.id AS invoice_id, invoice.status_id AS status_id,
			  suppliers.name AS supplier_name, movements.date, (SUM(assets.regular_price) - (SELECT  SUM( s1.price )
                                    FROM (
                                            SELECT (
                                            regular_price * refund_quantity
                                            ) AS price,movement_id
                                            FROM  `ace_iv_assets` 
                                            GROUP BY movement_id,item_id
                                        ) AS s1 WHERE s1.movement_id = invoice_id)) as total_price, invoice.pay_date,
		 	  invoice.reference_no, invoice.paid_amount, payment_method.name as payment_type, CONCAT(users.first_name, ' ',users.last_name ) as tech_name, ins.status as invoice_status, CONCAT(tech_users.first_name, ' ',tech_users.last_name ) as location_name, CONCAT(purch_user.first_name, ' ',purch_user.last_name ) as order_by
			  FROM ace_iv_movements movements
			  LEFT JOIN ace_iv_assets assets ON movements.asset_id = assets.asset_id
			  LEFT JOIN ace_rp_suppliers suppliers ON movements.from_location_id = suppliers.id
			  LEFT JOIN ace_iv_invoice invoice ON movements.id = invoice.invoice_id
			  LEFT JOIN ace_rp_purchase_payment_method payment_method ON invoice.payment_method = payment_method.id
			  LEFT JOIN ace_rp_users users ON invoice.agent_id = users.id
			  LEFT JOIN ace_rp_users tech_users ON movements.to_location_id = tech_users.id
			  LEFT JOIN ace_rp_users purch_user ON invoice.created_by = purch_user.id
			  LEFT JOIN ace_iv_invoice_status  ins ON invoice.status_id = ins.id
			  LEFT JOIN ace_rp_orders ord ON invoice.order_id = ord.order_number
			  WHERE 1=1 {$searchCondition}
			  {$dateConditions} 
			  {$sqlConditions}
			  GROUP BY movements.id
			";*/

			$query = "SELECT invoice.po_number,invoice.order_id,ord.id as order_no, invoice.remaining_amount, invoice.doc_type, invoice.invoice AS invoice_number, movements.id AS invoice_id, invoice.status_id AS status_id,
			  suppliers.name AS supplier_name, movements.date, (SUM(assets.regular_price) - (SELECT  SUM( s1.price )
                                    FROM (
                                            SELECT (
                                            regular_price * refund_quantity
                                            ) AS price,movement_id
                                            FROM  `ace_iv_assets` 
                                            GROUP BY movement_id,item_id
                                        ) AS s1 WHERE s1.movement_id =invoice.invoice_id)) as total_price, invoice.pay_date,
		 	  invoice.reference_no, invoice.paid_amount, payment_method.name as payment_type, CONCAT(users.first_name, ' ',users.last_name ) as tech_name, ins.status as invoice_status, CONCAT(tech_users.first_name, ' ',tech_users.last_name ) as location_name, CONCAT(purch_user.first_name, ' ',purch_user.last_name ) as order_by
			  FROM ace_iv_movements movements
			  LEFT JOIN ace_iv_assets assets ON movements.asset_id = assets.asset_id
			  LEFT JOIN ace_rp_suppliers suppliers ON movements.from_location_id = suppliers.id
			  LEFT JOIN ace_iv_invoice invoice ON movements.id = invoice.invoice_id
			  LEFT JOIN ace_rp_purchase_payment_method payment_method ON invoice.payment_method = payment_method.id
			  LEFT JOIN ace_rp_users users ON invoice.agent_id = users.id
			  LEFT JOIN ace_rp_users tech_users ON movements.to_location_id = tech_users.id
			  LEFT JOIN ace_rp_users purch_user ON invoice.created_by = purch_user.id
			  LEFT JOIN ace_iv_invoice_status  ins ON invoice.status_id = ins.id
			  LEFT JOIN ace_rp_orders ord ON invoice.order_id = ord.order_number
			  
			  WHERE 1=1 {$searchCondition}
			  {$dateConditions} 
			  {$sqlConditions}
			  GROUP BY movements.id
			";
			// print_r($query); die;
			$items = array();
			$result = $db->_execute($query);
			$num=0;
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				foreach ($row as $k => $v){
				  $data[$k] = $v;
				}
				if($row['doc_type'] == 7){
					$data['invoice_type'] = 'Refund';	
				} else {
					$data['invoice_type'] = 'Invoice';	
				}
				if(empty($row['location_name'])){
					$data['location_name'] = 'Warehouse';	
				}
				$items[] = $data;
			}
		//} 
		// else if($search_invoice == 'Refunds'){

		// 	// FIND REFUNDS
		// 	// query assumes an asset can only come from a supplier once
		// 	// select info for each invoice, as well as SUM for all items in that invoice.
		// 	// we use GROUP BY which selects all items in an invoice then groups them together in a single row, which allows us to add
		// 	// the price for all those items into total_price for that invoice
		// 	$query = "SELECT invoice.invoice AS invoice_number, movements.id AS invoice_id,
		// 		  suppliers.name AS supplier_name, movements.date, SUM(refunds.refund_regular_price) as total_price,
		// 		  invoice.status_id
		// 		  FROM ace_iv_movements movements
		// 		  LEFT JOIN ace_iv_assets assets ON movements.asset_id = assets.asset_id
		// 		  LEFT JOIN ace_iv_locations locations ON movements.to_location_id = locations.id
		// 		  LEFT JOIN ace_rp_suppliers suppliers ON locations.number = suppliers.id
		// 		  LEFT JOIN ace_iv_invoice invoice ON movements.id = invoice.invoice_id
		// 		  LEFT JOIN ace_iv_refund_price refunds ON assets.asset_id = refunds.asset_id
		// 		  WHERE locations.type = 'Supplier'
		// 		  {$dateConditions} 
		// 		  {$sqlConditions}
		// 		  GROUP BY movements.id
		// 	";
			
		// 	$result = $db->_execute($query);
		// 	$num=0;
		// 	while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		// 	{
		// 		foreach ($row as $k => $v){
		// 		  $data[$k] = $v;
		// 		}
		// 		$data['invoice_type'] = "Refund - Type Unknown";
		// 		if ($data['status_id'] == 1) {
		// 			$data['invoice_type'] = 'Refund - Returned';
		// 			$data['invoice_status'] = 'Pending';
		// 		}
		// 		else if ($data['status_id'] == 2) {
		// 			$data['invoice_type'] = 'Refund - Refused';
		// 			$data['invoice_status'] = 'Not Payable';
		// 		}
		// 		else if ($data['status_id'] == 3 || $data['status_id'] == 4) {
		// 			$data['invoice_type'] = 'Refund - Credited';
		// 			$data['invoice_status'] = 'Credited';
		// 		}
				
		// 		$items[] = $data;
		// 	}
		// } else if($search_invoice == 'Paid')
		// {
		// 	$query = "SELECT invoice.invoice AS invoice_number, movements.id AS invoice_id, invoice.status_id AS status_id,
		// 	  suppliers.name AS supplier_name, movements.date, (SUM(assets.regular_price) - (SELECT  SUM( s1.price )
	  //                                   FROM (
	  //                                           SELECT (
	  //                                           regular_price * refund_quantity
		  //                                           ) AS price,movement_id
	  //                                           FROM  `ace_iv_assets` 
	  //                                           GROUP BY movement_id,item_id
	  //                                       ) AS s1 WHERE s1.movement_id = invoice_id)) as total_price, invoice.pay_date,
		// 	  invoice.reference_no, invoice.paid_amount, payment_method.name as payment_type, CONCAT(users.first_name, ' ',users.last_name ) as tech_name
		// 	  FROM ace_iv_movements movements
		// 	  LEFT JOIN ace_iv_assets assets ON movements.asset_id = assets.asset_id
		// 	  LEFT JOIN ace_iv_locations locations ON movements.from_location_id = locations.id
		// 	  LEFT JOIN ace_rp_suppliers suppliers ON locations.number = suppliers.id
		// 	  LEFT JOIN ace_iv_invoice invoice ON movements.id = invoice.invoice_id
		// 	  LEFT JOIN ace_rp_purchase_payment_method payment_method ON invoice.payment_method = payment_method.id
		// 	  LEFT JOIN ace_rp_users users ON invoice.agent_id = users.id
		// 	  WHERE locations.type = 'Supplier' and invoice.status_id = 5
		// 	  {$dateConditions} 
		// 	  {$sqlConditions}
		// 	  GROUP BY movements.id
		// 	";
		
		// 	$items = array();
		// 	$result = $db->_execute($query);
		// 	$num=0;
		// 	while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		// 	{
		// 		foreach ($row as $k => $v){
		// 		  $data[$k] = $v;
		// 		}
		// 		$data['invoice_type'] = 'Paid';
		// 		$items[] = $data;
		// 	}
		// }
		
		
		
		
		
		
		// foreach ($items as $key => $item){
		// 	//Remove refunds
		// 	if (isset($search_active) && $search_refunds != 'Refunds'){
		// 		if (strpos($item['invoice_type'], 'Refund') !== false)
		// 			unset($items[$key]);
		// 	}
			
		// 	//Remove invoices
		// 	if (isset($search_active) && $search_invoice != 'Invoices'){
		// 		if ($item['invoice_type'] == 'Invoice')
		// 			unset($items[$key]);
		// 	}
			
		// 	//Remove Paid
		// 	if (isset($search_active) && $search_paid != 'Paid'){
		// 		if ($item['status_id'] == 5)
		// 			unset($items[$key]);
		// 		$data['invoice_status'] = 'Paid';
		// 	}

		// 	//Remove Unpaid
		// 	if (isset($search_active) && $search_paid == 'Paid'){
		// 		if ($item['status_id'] == 6)
		// 			unset($items[$key]);
		// 		$data['invoice_status'] = 'Invoice';
		// 	}
		// }
		
		// sort results by date
		foreach ($items as $key => $row) {
			$date[$key]  = $row['date'];
		}
		array_multisort($date, SORT_ASC, $items);
		
		//echo "<pre>";print_r($items);echo "</pre>";
		$query1 = "SELECT * from ace_rp_purchase_payment_method";
			$result1 = $db->_execute($query1);
			$category = array();		
			while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
			{
				foreach($row1 as $k => $v)
				{
				  $methods[$row1['id']][$k] = $v;
				}	
			}
		$this->set("status", $search_invoice);
		$this->set('booking_sources', $this->Lists->BookingSources());
		$this->set("paidDate", date("d M Y", strtotime($paidDate)));
		$this->set('methods', $methods);
		$this->set('search_invoice', $search_invoice);
		$this->set('documents', $items);
		//$this->set('refunds', $refunds);
		$this->set('locations', $allLocations);
		$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));
		$this->set('next_fdate', date("d M Y", strtotime($fdate) + 24*60*60));
		$this->set('prev_tdate', date("d M Y", strtotime($tdate) - 24*60*60));
		$this->set('next_tdate', date("d M Y", strtotime($tdate) + 24*60*60));
		$this->set('fdate', date("d M Y", strtotime($fdate)));
		$this->set('tdate', date("d M Y", strtotime($tdate)));
	}
	
	/**
	* Output the data for the Inventory > Techs table
	* @return: None
	**/
	function techs(){
		/*
		*	INIT
		*/
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$allDocTypes = $this->GetDocTypes();
		$allSuppliers = $this->Lists->ListTable('ace_rp_suppliers');
		$allLocations = $this->GetLocations();
		
		$allTechs = $this->GetTechs();
		
		$items = array();
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		$techid = isset($_GET['ftechid']) ? $_GET['ftechid'] : -1;
		$booking = isset($_GET['booking']) ? $_GET['booking'] : '';
		$part_name = isset($_GET['part_name']) ? $_GET['part_name'] : '';
		$status = isset($_GET['status']) ? $_GET['status'] : -1;
		$limit = 50;
		// $total_pages = 0;
		$pageNo = isset($_GET['currentPage']) ? $_GET['currentPage'] : 1;
		if($pageNo == 0)
		{
			$pageNo	= 1;
		}
		$record_index= ($pageNo-1) * $limit;      
		
		if (!$order) $order = 'movements.date desc';
	
		$this->layout="list";
		
		/*
		*	SEARCH PARAMETERS
		*/
		
		if ($this->params['url']['ffromdate'] != '')
			$fdate = date("Y-m-d", strtotime($this->params['url']['ffromdate'])) . ' 00:00:00';
		else
			$fdate = date("Y-m-d");

		if ($this->params['url']['ftodate'] != '')
			$tdate = date("Y-m-d", strtotime($this->params['url']['ftodate'])) . ' 23:59:59';
		else
			$tdate = date("Y-m-d");

		$date_start = $this->Common->getMysqlDate($fdate);
		$date_end = $this->Common->getMysqlDate($tdate);
		$sqlConditions = "(item_history.item_date BETWEEN '$date_start' AND '$date_end')"; 

		if ($_GET['part_name']){
			$sqlConditions .= " AND item.name LIKE '%{$_GET['part_name']}%'";
		}
		if ($_GET['status'] && $_GET['status'] != -1){
			$sqlConditions .= " AND item_history.status = {$_GET['status']}";
		}
		if ($_GET['booking']){
			$sqlConditions .= " AND item_history.job_ref_num={$_GET['booking']}";
		}
		
		if ($_GET['ftechid'] && $_GET['ftechid'] != -1){
			$sqlConditions .= " AND {$_GET['ftechid']} IN(item_history.paid_by,item_history.assigned_by,item_history.sold_by,item_history.received_by)";
		}
		
		$query = "SELECT item_history.*,sold_user.first_name as sold_by,
		received_user.first_name as received_by,paid_user.first_name as paid_by,assigned_user.first_name as assigned_by, supplier.name as supplier_name, item_status.name as status_name, item.name as item_name, item.auto_sku,order_d.id as order_id
			FROM ace_iv_invoice_item_history item_history
			LEFT JOIN ace_rp_users sold_user ON item_history.sold_by = sold_user.id
			LEFT JOIN ace_rp_users received_user ON item_history.received_by = received_user.id
			LEFT JOIN ace_rp_users assigned_user ON item_history.assigned_by = assigned_user.id
			LEFT JOIN ace_rp_users paid_user ON item_history.paid_by = paid_user.id
			LEFT JOIN ace_rp_suppliers supplier ON item_history.supplier = supplier.id
			LEFT JOIN ace_rp_item_status item_status ON item_history.status = item_status.id
			LEFT JOIN iv_items_labeled2 item ON item_history.item_id = item.id
			LEFT JOIN ace_rp_orders order_d ON item_history.job_ref_num = order_d.order_number
			WHERE $sqlConditions group by item_history.item_id,item_history.status,order_d.id order by item_history.id asc  LIMIT $record_index, $limit";

		/*
		* BUILD ITEMS
		*/

		// print_r($query);; die;
		$result = $db->_execute($query);
		
		while($row = mysql_fetch_array($result)){
			$items[] = $row;
		}
		$query1 = "SELECT count(*)	FROM ace_iv_invoice_item_history item_history
			LEFT JOIN ace_rp_users sold_user ON item_history.sold_by = sold_user.id
			LEFT JOIN ace_rp_users received_user ON item_history.received_by = received_user.id
			LEFT JOIN ace_rp_users assigned_user ON item_history.assigned_by = assigned_user.id
			LEFT JOIN ace_rp_users paid_user ON item_history.paid_by = paid_user.id
			LEFT JOIN ace_rp_suppliers supplier ON item_history.supplier = supplier.id
			LEFT JOIN ace_rp_item_status item_status ON item_history.status = item_status.id
			LEFT JOIN iv_items_labeled2 item ON item_history.item_id = item.id
			LEFT JOIN ace_rp_orders order_d ON item_history.job_ref_num = order_d.order_number
			WHERE $sqlConditions group by item_history.item_id,item_history.status,order_d.id";
		
		$result1 = $db->_execute($query1);
		$row1 = mysql_fetch_array($result1);  
		$total_records = $row1[0];

		// print_r($row1); die;
		// print_r($total_records); die;
    	//  echo $total_records;
		$total_pages = ceil($total_records / $limit);  

		// echo "<pre>";
		// print_r($items); die;
		/*
		* STUFF TO RETURN TO THE PAGE VIEW (techs.thtml)
		*/
		$this->set('totalPages', $total_pages);
		$this->set('currentPage', $pageNo);
		$this->set('booking', $booking);
		$this->set('part_name', $part_name);
		$this->set('status', $status);
		$this->set('items', $items);
		$this->set('locations', $allTechs);
		$this->set('techid', $techid);
		$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));
		$this->set('next_fdate', date("d M Y", strtotime($fdate) + 24*60*60));
		$this->set('prev_tdate', date("d M Y", strtotime($tdate) - 24*60*60));
		$this->set('next_tdate', date("d M Y", strtotime($tdate) + 24*60*60));
		$this->set('fdate', date("d M Y", strtotime($fdate)));
		$this->set('tdate', date("d M Y", strtotime($tdate)));
		
		
		$pending = $_REQUEST['pending'];
		if ($pending == 'on') $pending = 'checked'; else $pending = '';
		$this->set('pending', $pending);
		
		$this->set("ismobile", $this->Session->read("ismobile"));

	}
	
	function editDoc()
	{
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$this->layout="list";
		
		if ($this->params['url']['doc_id']) {
			$doc_id = $this->params['url']['doc_id'];
			$view = 1;
		}
		else if ($this->params['url']['invoice_id']) {
			$doc_id = $this->params['url']['invoice_id'];
			$refund = 1;
		}
		$fromBooking = isset($_GET['fromBooking']) ? $_GET['fromBooking'] :0;
		$fromTech = isset($_GET['fromTech']) ? $_GET['fromTech'] :0;
		$docdate = isset($_GET['doc_date']) ? $_GET['doc_date'] : date('d M Y');
		$doc_type = isset($_GET['type']) ? $_GET['type'] : $_REQUEST['type'];
		$item_id = $_REQUEST['item_id'];
		$invoice_number = isset($_GET['invoiceId']) ? $_GET['invoiceId'] :'';
		$order_number = $_GET['orderId'];
		// $order_number = isset($_GET['orderId']) ? $_GET['orderId'] :'';
		$supplier_id = isset($_GET['supplierId']) ? $_GET['supplierId'] :'';
		$supplierInfo = isset($_GET['supplierInfo']) ? $_GET['supplierInfo'] :'';
		$tech_id = isset($_GET['techId']) ? $_GET['techId'] :'';
		$bookItems = isset($_GET['orderItems']) ? json_decode(stripslashes($_GET['orderItems']),true) :array();
		$items = array();
		$paidDate = date("Y-m-d");
		$allDocTypes = $this->GetDocTypes();
		// $allSuppliers = $this->Lists->ListTable('ace_rp_suppliers');
		$allSuppliers = $this->Lists->ListTable('ace_rp_suppliers','',array('name','city'));
		$allLocations = $this->GetLocations();
		

		// $methods = $this->HtmlAssist->table2array($this->PurchasePaymentMethod->findAll(), 'id', 'name');

		// echo "<pre>";
		// print_r($methods);

		$trucks = $this->Lists->Technicians(true);		
		if(empty($doc_id)){
			
			$getPo = $db->_execute("SELECT * FROM ace_rp_invoice_po_number WHERE  `invoice_id` = ( SELECT MAX(  `invoice_id` ) FROM ace_rp_invoice_po_number where po_number > 2020100);");
			$getPoResult = mysql_fetch_assoc($getPo);
			$last_po_number = $getPoResult['po_number'];
			$new_po_number = $getPoResult['po_number']+1;
			$this->set("last_po_number", $last_po_number);
			$this->set("new_po_number", $new_po_number);
		} 
		if ($view)
		{
			$getPo = $db->_execute("SELECT * FROM ace_rp_invoice_po_number WHERE  `invoice_id` =".$doc_id."");
			$getPoResult = mysql_fetch_assoc($getPo);
			$this->set("new_po_number", $getPoResult['po_number']);
			$query = "SELECT * FROM ace_iv_invoice 
					  LEFT JOIN ace_iv_invoice_status ON ace_iv_invoice.status_id = ace_iv_invoice_status.id
					  WHERE invoice_id = '{$doc_id}'";
			
			$result = $db->_execute($query);
			if($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				$invoice_number = $row['invoice'];
				$original_invoice_id = $row['original_invoice_id'];
				// $status = $row['status'];
				$status = $row['status_id'];
			}
			
			if ($original_invoice_id) {
				// refunds (price per item for refunds is in a different table)
				$is_view_refund = 1;
				$query = "
					SELECT movements.*, assets.*, items.model, items.name, refunds.refund_regular_price, sku.sku
					FROM ace_iv_movements movements
					LEFT JOIN ace_iv_assets assets ON movements.asset_id = assets.asset_id
					LEFT JOIN ace_iv_items items ON assets.item_id = items.id
					LEFT JOIN ace_iv_refund_price refunds ON assets.asset_id = refunds.asset_id
					LEFT JOIN ace_iv_sku sku ON assets.item_id = sku.item_id AND movements.from_location_id = sku.supplier_id
					WHERE movements.id = $doc_id
				";
			}
			else {
				// invoices
				$query = "
					SELECT  movements.*, assets.*, items.model, items.name,  sku.sku
					FROM ace_iv_movements movements
					LEFT JOIN ace_iv_assets assets ON movements.asset_id = assets.asset_id
					LEFT JOIN ace_iv_items items ON assets.item_id = items.id
					LEFT JOIN ace_iv_sku sku ON assets.item_id = sku.item_id AND movements.from_location_id = sku.supplier_id
					WHERE movements.id = $doc_id
				";
				if (!isset($_GET['view_transaction'])){
					$this->set('can_save_purchase', 1);
					$query2 = "SELECT inv.*,suppliers.name as supplier_name, CONCAT(users.first_name, ' ',users.last_name ) as agent_name, CONCAT(return_user.first_name, ' ',return_user.last_name ) as return_by , payment_method.name as payment_type ,
					tech_pay.name as tech_method1
					FROM ace_iv_invoice inv 
					LEFT JOIN ace_iv_movements mv ON mv.id = inv.invoice_id
			  		LEFT JOIN ace_rp_suppliers suppliers ON mv.from_location_id = suppliers.id
			  		LEFT JOIN ace_rp_users users ON inv.agent_id = users.id
					LEFT JOIN ace_rp_users return_user ON inv.returned_by = return_user.id
					LEFT JOIN ace_rp_purchase_payment_method tech_pay ON inv.tech_pay_method = tech_pay.id
					LEFT JOIN ace_rp_purchase_payment_method payment_method ON inv.payment_method = payment_method.id
					WHERE invoice_id = '{$doc_id}'";
					$invoiceDetails = array();
					$invoiceImageArr = array();

					$result = $db->_execute($query2);
					if($row = mysql_fetch_array($result, MYSQL_ASSOC)){
						$invoice_number = $row['invoice'];
						$supplierName = $row['supplier_name'];
						$supplierInfo = $row['supplier_info'];
						$invoiceDetails = $row;
						if(!empty($row['invoice_image'])){
							$invoiceImageArr[] = $row['invoice_image'];				
						}
						if(!empty($row['tech_pay_image']))
						{
							$invoiceImageArr[] = $row['tech_pay_image'];	
						}
					}

					$query1 = "SELECT ih.*, invs.status as status_name,
					ppm.name as payment_method_name, CONCAT(return_user.first_name, ' ',return_user.last_name) as return_user,
					CONCAT(paid_by.first_name, ' ',paid_by.last_name) as paid_user
					from ace_iv_invoice_history ih 
					LEFT JOIN ace_iv_invoice_status invs ON invs.id = ih.status_id
					LEFT JOIN ace_rp_purchase_payment_method ppm ON ppm.id = ih.payment_method
					LEFT JOIN ace_rp_users return_user ON return_user.id = ih.returned_by
					LEFT JOIN ace_rp_users paid_by ON paid_by.id = ih.paid_by 
				 	where ih.invoice_id=".$doc_id." AND ih.status_id = 5 order by ih.id desc";
					$result1 = $db->_execute($query1);
					$row = mysql_fetch_array($result1, MYSQL_ASSOC);
					$this->set("response",$row);
				}
			}

			// echo "<pre>";
			// print_r($row); die;

			
			$result = $db->_execute($query);
			$num=0;
			while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
				// if ($row['type'] == "User") $row['type'] = 'tech';
				// if ($row['type'] == "Warehouse") $row['type'] = 'warehouse';
				
				$row['item_location'] = 'tech' . ':' . $row['to_location_id'];
				unset($row['type']);
				unset($row['number']);
				if ($row['refund_regular_price']) {
					$row['regular_price'] = $row['refund_regular_price'];
				}
				
				$items[] = $row;
			}
			

			// $new_items = array();
			// $temp_items = $items;

			// Get All Invoices
			//Loki: Get the refund items quantity:
			$invoiceQuery = "SELECT invoice_image FROM  `ace_iv_invoice_history` WHERE  `invoice_id` =".$doc_id;
			$invoiceResult = $db->_execute($invoiceQuery);
			while($row = mysql_fetch_array($invoiceResult, MYSQL_ASSOC)){
				if(!empty($row['invoice_image'])){
					$invoiceImageArr[] = $row['invoice_image'];
				}
			}
			//Merge existing results
			
			// foreach($items as $key => $item){
			// 	$qty = 1;
			// 	$match = false;
			// 	$add = true;
				
			// 	foreach($temp_items as $key2 => $temp_item){
			// 		//Check other items for non-unique fields to find the grouping ones
			// 		if ($temp_item['item_id'] == $item['item_id']
			// 			&& $temp_item['model'] == $item['model']
			// 			&& $temp_item['purchase_price'] == $item['purchase_price']
			// 			&& $temp_item['regular_price'] == $item['regular_price']
			// 			&& $temp_item['item_location'] == $item['item_location']
			// 			&& $key2 != $key){
						
			// 			if (!$temp_items[$key2]['ignore']){
			// 				//echo $item['item_id'] . '['. $key .'] match with ' . $key2 . '<br/>';
			// 				$qty++;
			// 				$temp_items[$key2]['ignore'] = true;
			// 			}else{
			// 				$add = false;
			// 				break;
			// 			}
			// 		}else if ($key2 == $key){
			// 			$temp_items[$key2]['ignore'] = true;
			// 		}
			// 	}
				
			// 	if ($add){
			// 		$item['qty'] = $qty;
			// 		$new_items[] = $item;
			// 	}
			// }
			
			// $items = $new_items;
			//Loki: Get the refund items quantity:
			$query = "SELECT SUM(quantity) as refunded_quantity , item_id FROM  `ace_iv_invoice_refund_items` WHERE  `Invoice_id` =".$doc_id." GROUP BY item_id";
			$quantityData = array();
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
				$quantityData[$row['item_id']] = $row['refunded_quantity'];
			}
			
			if ($is_view_refund) {
				// get refund invoice details
				$query = "SELECT * FROM ace_iv_invoice invoice
						  WHERE invoice_id = $doc_id";
				$result = $db->_execute($query);
				$row = mysql_fetch_array($result, MYSQL_ASSOC);
				$refund_invoice = $row['invoice'];
				
				$query = "SELECT * FROM ace_iv_invoice invoice
						  WHERE invoice_id = {$row['original_invoice_id']}";
				$result = $db->_execute($query);
				$row = mysql_fetch_array($result, MYSQL_ASSOC);
				$original_invoice = $row['invoice'];
			}
				
		}
		
		/* REFUNDS */
		if ($refund) {
			$query = "SELECT invoice FROM ace_iv_invoice WHERE invoice_id = '{$doc_id}'";
			
			$result = $db->_execute($query);
			if($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				
				$invoice_number = $row['invoice'];
			}
			
			// query assumes $doc_id is the transaction ID of the movement from the supplier (ie. bought from the supplier)
			// query also joins determines the current location of an asset and returns as current_location_id (this is to determine
			// whether the item has already been refunded or not)
			$query = "
				SELECT movements.*, assets.*, items.model, items.name, locations.number, locations.type, suppliers.name AS supplier_name,
				current_movement.to_location_id AS current_location_id, refunds.refund_regular_price
				FROM ace_iv_movements movements
				LEFT JOIN ace_iv_assets assets ON movements.asset_id = assets.asset_id
				LEFT JOIN ace_iv_items items ON assets.item_id = items.id
				LEFT JOIN ace_iv_locations locations ON movements.from_location_id = locations.id
				LEFT JOIN ace_iv_movements current_movement ON assets.movement_id = current_movement.id AND assets.asset_id = current_movement.asset_id
				LEFT JOIN ace_rp_suppliers suppliers ON locations.number = suppliers.id
				LEFT JOIN ace_iv_refund_price refunds ON assets.asset_id = refunds.asset_id
				WHERE movements.id = {$doc_id} AND locations.type = 'Supplier'
			";
			// locations.type = 'Supplier' is redundant since every $doc_id passed into here must be a Supplier,
			// but this will tell us that something is wrong if no results get returned
			
			$result = $db->_execute($query);
			$num=0;
			while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
				if ($row['type'] == "User") $row['type'] = 'tech';
				if ($row['type'] == "Warehouse") $row['type'] = 'warehouse';
				
				$row['item_location'] = $row['type'] . ':' . $row['number'];
				
				$supplier_location_id = $row['number'];
				
				unset($row['type']);
				unset($row['number']);
				
				$items[] = $row;
			}
			
			$new_items = array();
			$temp_items = $items;
			
			//Merge existing results
			foreach($items as $key => $item){
				$qty = 1;
				$match = false;
				$add = true;
				
				foreach($temp_items as $key2 => $temp_item){
					// Check other items for non-unique fields to find the grouping ones
					// ie. combine items together if they're the same model / price / location
					if ($temp_item['item_id'] == $item['item_id']
						&& $temp_item['model'] == $item['model']
						&& $temp_item['purchase_price'] == $item['purchase_price']
						&& $temp_item['regular_price'] == $item['regular_price']
						&& $temp_item['item_location'] == $item['item_location']
						&& $key2 != $key){
						
						if (!$temp_items[$key2]['ignore']){
							//echo $item['item_id'] . '['. $key .'] match with ' . $key2 . '<br/>';
							$qty++;
							$temp_items[$key2]['ignore'] = true;
						}else{
							$add = false;
							break;
						}
					}else if ($key2 == $key){
						$temp_items[$key2]['ignore'] = true;
					}
					
					// FOR REFUNDS - don't count this item as part of the quantity if it's already been refunded
					// Comparison is for determining whether the ID it was purchased from matches the ID of the current location
					// if it matches, that means the current location is the supplier, meaning it's already been refunded
					if ($temp_item['from_location_id'] == $temp_item['current_location_id']) {
						$qty--;
					}
					
				}
				
				if ($add){
					$item['qty'] = $qty;
					$new_items[] = $item;
				}
			}
			
			$items = $new_items;
		}
		/* MOVEMENT */
		if ($doc_type == 2){
		
			if ($doc_type == 2){
				$query = "
					SELECT assets.*, items.model, items.name, locations.number, locations.type, movements.to_location_id
					FROM ace_iv_assets assets
					LEFT JOIN ace_iv_items items ON assets.item_id = items.id
					LEFT JOIN ace_iv_movements movements ON assets.movement_id = movements.id AND assets.asset_id = movements.asset_id
					LEFT JOIN ace_iv_locations locations ON movements.to_location_id = locations.id
					WHERE assets.item_id = '$item_id'
					AND locations.type != 'Customer'
					AND locations.type != 'Supplier'
				";
			}elseif($doc_type == 5){
				$location_id = $_REQUEST['location'];
				
				$query = "
					SELECT COUNT(item_id) AS qty, item_id, locations.id, suppliers.name AS supplier_name,
							items.name AS name, items.model, movements.date, assets.regular_price, 
							movements.id AS movement_id, movements.to_location_id AS to_location, movements.from_location_id AS from_location
					
				    FROM ace_iv_movements movements
				    LEFT JOIN ace_iv_assets assets ON movements.asset_id = assets.asset_id
				    LEFT JOIN ace_iv_locations locations ON movements.from_location_id = locations.id
				    LEFT JOIN ace_rp_suppliers suppliers ON locations.number = suppliers.id
				    LEFT JOIN ace_iv_items items ON assets.item_id = items.id
				    WHERE locations.type = 'Supplier' AND locations.id = '$location_id' AND item_id = '$item_id' AND movements.from_location_id <> movements.to_location_id
				    GROUP BY item_id, locations.id
				";
			}
			
			$result = $db->_execute($query);
			$num=0;
			//echo $query;

			while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
				if ($row['type'] == "User") $row['type'] = 'tech';
				if ($row['type'] == "Warehouse") $row['type'] = 'warehouse';
				
				$row['item_location'] = $row['type'] . ':' . $row['number'];
				unset($row['type']);
				unset($row['number']);
				
				$items[] = $row;
			}
			
			if ($doc_type == 2){
				$new_items = array();
				$temp_items = $items;
				
				//Merge existing results
				
				foreach($items as $key => $item){
					$qty = 1;
					$match = false;
					$add = true;
					
					foreach($temp_items as $key2 => $temp_item){
						//Check other items for non-unique fields to find the grouping ones
						if ($temp_item['item_id'] == $item['item_id']
							&& $temp_item['model'] == $item['model']
							&& $temp_item['purchase_price'] == $item['purchase_price']
							&& $temp_item['regular_price'] == $item['regular_price']
							&& $temp_item['item_location'] == $item['item_location']
							&& $key2 != $key){
							
							if (!$temp_items[$key2]['ignore']){
								//echo $item['item_id'] . '['. $key .'] match with ' . $key2 . '<br/>';
								$qty++;
								$temp_items[$key2]['ignore'] = true;
							}else{
								$add = false;
								break;
							}
						}else if ($key2 == $key){
							$temp_items[$key2]['ignore'] = true;
						}
					}
					
					if ($add){
						$item['qty'] = $qty;
						$new_items[] = $item;
					}
				}
				
				$items = $new_items;
			}
			$tech_id = 0;
			$supplier_id = 1;
		}
		
		if ($is_view_refund) {
			$this->set('is_view_refund', $is_view_refund);
			$this->set('refund_invoice', $refund_invoice);
			$this->set('original_invoice', 	$original_invoice);
		}
		
		$query1 = "SELECT * from ace_rp_purchase_payment_method";
			$result1 = $db->_execute($query1);
			$category = array();		
			while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
			{
				foreach($row1 as $k => $v)
				{
				  $methods[$row1['id']][$k] = $v;
				}	
			}

		$this->set('categories', $this->Lists->IvMidCategories());
		$this->set('subCategories', $this->Lists->IvSubCategories(62));
		$this->set('booking_sources', $this->Lists->BookingSources());		
		$this->set('trucks', $trucks);
		$this->set('methods', $methods);
		$this->set('paidDate', date("d M Y", strtotime($paidDate)));
		$this->set('supplierName', $supplierName);
		$this->set('supplierInfo', $supplierInfo);
		$this->set('items', $items);		
		$this->set('bookItems',json_encode($bookItems));		
		$this->set('fromBooking', $fromBooking);		
		$this->set('fromTech', $fromTech);		
		$this->set('docdate', date('d M Y', strtotime($docdate)));
		$this->set('invoice_number', $invoice_number);
		$this->set('status', $status);	
		$this->set('doc_id', $doc_id);		
		$this->set('supplier_id', $supplier_id);		
		$this->set('note', $note);		
		$this->set('doc_type', $doc_type);
		$this->set('order_number', $order_number);
		$this->set('invoiceDetails', $invoiceDetails);
		$this->set('invoiceImageArr', $invoiceImageArr);
		$this->set('doctype', $allDocTypes[$doc_type]);
		$this->set('doctypes', $this->Common->getSelector($allDocTypes,'doctype',$doc_type));
		$this->set('inventoryTechnician',$this->Lists->inventoryTech());

		if (($doc_type==1)||($doc_type==5) || ($doc_type==7))
			$this->set('suppliers', $this->Common->getSelector($allSuppliers,'supplier',$supplier_id));
		 // $this->set('allSuppliers',$this->Lists->ListTable('ace_rp_suppliers','',array('name','city')));
		else
			$this->set('suppliers', $this->Common->getSelector($allLocations,'supplier',$supplier_id));
		$this->set('locations', $this->Common->getSelector($allLocations,'location',$tech_id));
		if ($supplier_location_id) {
			$this->set('supplier_location_id', $supplier_location_id);
		}
	}

	/*Loki:get inventory items with tech quantity*/
	
function searchItems()
	{
		$this->layout = 'blank';

		$needle = $_GET['query'];
		$classId = $_GET['classId'];
		
		$db =& ConnectionManager::getDataSource('default');
		
		$query = "SELECT i.*, techItem.quantity as stock
			FROM iv_items_labeled2 i
			JOIN ace_iv_sub_categories sub ON i.sub_category_id  = sub.id
			LEFT JOIN ace_rp_tech_inventory_item techItem ON i.id  = techItem.item_id AND techItem.tech_id = 231433
			WHERE active = 1 and i.category_id != 39 and sub.name != 'Inactive' and i.name LIKE \"%$needle%\"";
		
		$result = $db->_execute($query);
		$inventoryTechnician = $this->Lists->inventoryTech();

		while($row = mysql_fetch_array($result))
		{
			$images1 =  $row['image'];
			if(!empty($images1)){
				
			
		$year = substr($images1, 0, 4);
        $mon = substr($images1, 4, 2);
        $day = substr($images1, 6, 2);
        $name = $year.'/'.$mon.'/'.$day.'/'.$images1;
		$dir = str_replace('index.php/inventories','',dirname($_SERVER['REQUEST_URI']));
		$src = $dir."/upload_photos/$name";
			$image="<img id='' style='height:30px;width:30px' class='invoice-openImg order-images' src='$src'>";
			}
			else {
				$image="";
				
			}
			
			$row['tech_data'] = $this->getInventoryTechQty($row['id']);
			$tableRows .= "<tr id='ajax_item_result_template_".$classId." style='display:none'>
				<input type='hidden' class='stock_".$classId."' val='".$row['stock']."'>
				 <td class='markup_percent_".$classId."' style='display:none'>". $row['markup_percent']."</td>
				  <td class='tech_percent_".$classId."' style='display:none'>". $row['tech_percent']."</td>
			    <td class='name_".$classId."'>".htmlentities(stripslashes($row['name']), ENT_QUOTES)."</td>
			    <td class='model_".$classId."'>". $row['model']."</td>
			    <td class='sku_".$classId."'>". $row['sku']."</td>
			    <td class='price_".$classId."' >". $row['selling_price']."</td>
			    <td style='display:none;'' class='item_id_".$classId."' >".$row['id']."</td>
			    <td class='purchase_price_".$classId."'>".$row['supplier_price']."</td>
				<td class='purchase_price_".$classId."'>".$image."</td>
			    <td style='display:none;' class='category_id_".$classId."'>".$row['category_id']."</td>";

			foreach ($inventoryTechnician as $tech1 => $value1){
			$tableRows .= "<td class='warehouse_".$value1['id']."_".$classId."'>";
		     if(!empty($row['tech_data'])){
		     foreach ($row['tech_data'] as $tech2 => $value2){
		        if($value1['id'] == $value2['TechInventoryItem']['tech_id'] ) {
		         	$tableRows .= $value2['TechInventoryItem']['quantity'];
		         } 
			 	}
			 } 
			 $tableRows .= "</td>";
			}
			 
			$tableRows .= "<td class='remove_item_".$classId."'><input type='checkbox' name='delete_item' class='delete_items' item-id='".$row['id']."' item-class='".$classId."'/></td>
    		<td class='brand_".$classId."' style='display:none'>". $row['brand_name']."</td>
    		<td class='sub_category_id_".$classId."' style='display:none'>".$row['sub_category_id']."</td>";
			 $tableRows .= "</tr>";			

		}
		
		print json_encode($tableRows);
		exit();
		// $this->set('inventoryTechnician',$this->Lists->inventoryTech());

		// $this->set('classId', $classId);
		// $this->set('items', $items);
	}
	
	function ajaxItems() {
		$queries = new Queries($this);
		$active = isset($_GET['active']) ? $_GET['active'] : null;
		if ($active) {
			if(!empty($_GET['query'])){
				print json_encode($this->SearchActiveParts($_GET['query']));
			} else {
				$response = array();
				print json_encode($response);
			}
		}
		else if ($_GET['supplier']) {
			//print json_encode($queries->SearchPartsFromSuppliers($_GET['query'],$_GET['supplier']));
			print json_encode($queries->SearchParts($_GET['query']));
		}
		else {
			print json_encode($queries->SearchParts($_GET['query']));
		}
	}

	/*Loki: Assign purchase items to technician.*/
	function saveTechItems($items){
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$now = date('Y-m-d');
		$item_tech = explode(':', $items['location']);
		$checkExisting = "SELECT * FROM ace_rp_tech_inventory_item where tech_id = ".$item_tech[1]." AND item_id =".$items['item_id']."";
			$executeRes = $db->_execute($checkExisting);
			$row = mysql_fetch_array($executeRes, MYSQL_ASSOC);
			if(!empty($row)) {
				$newQuantity = $items['quantity'] + $row['quantity'];
				$res = $db->_execute("UPDATE ace_rp_tech_inventory_item set quantity = ".$newQuantity." where id = ".$row['id']."");
			} else{
				$query = "INSERT INTO ace_rp_tech_inventory_item (tech_id, item_id, quantity, updated_date) VALUES (".$item_tech[1].",".$items['item_id'].", ".$items['quantity'].", '".$now."')";
				$res = $db->_execute($query);
			}
	}
	/*Loki: add items into history table
		status: 1 = purchase, 2 = sold, 3 = received
	*/
	function invoiceItemHistory($item,$invoice_number,$doc_id,$supplier_id,$ref_num,$paid_by){
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$now = date('Y-m-d');
		$item_tech = explode(':', $item['location']);

		$job_ref_num = !empty($ref_num) ? $ref_num : 0;

		$db->_execute("INSERT INTO ace_iv_invoice_item_history (quantity,item_id,purchase_price,job_ref_num,Invoice_num,received_by, supplier,item_date,invoice_id,status,paid_by) VALUES (".$item['quantity'].",".$item['item_id'].",".$item['price'].",'".$job_ref_num."','".$invoice_number."',".$item_tech[1].",".$supplier_id.",'".$now."',".$doc_id.",1,".$paid_by.")");
	}

	function saveDoc(){
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$assets = array();
		$doc_id = $this->data['doc_id'];
		$original_invoice_id = $this->data['doc_id']; // for refund
		$doc_type = $this->data['doc_type'];
		$supplier_id = $this->data['supplier'];
		$supplier_info = $this->data['supplier_info'];
		$invoice_number = $this->data['invoice_number'];
		$do_save = (isset($_POST['enable_save']))? $_POST['enable_save'] : null;
		$fromBooking = isset($_POST['fromBooking']) ? $_POST['fromBooking'] : 0;
		$fromTech = isset($_POST['fromTech']) ? $_POST['fromTech'] : 0;
		$techPayMethod = isset($_POST['techPayMethod']) ? $_POST['techPayMethod'] : 0;
		$orderId = $this->data['order_id'];
		$order_number = $this->data['order_number'];
		$location = $this->data['location'];
		$note = $this->data['note'];
		$doc_date = date("Y-m-d", strtotime($this->data['doc_date']));
		$moveDate = date("Y-m-d");
		$status_id = $this->data['status_id'];
		$refundDate = date("Y-m-d", strtotime($this->data['refund_date']));
		$total_purchase_amount = (isset($_POST['totalPurchaseAmount']))? $_POST['totalPurchaseAmount'] : null;
		$photoImage = isset($_FILES['sortpic1'])? $_FILES['sortpic1'] : null;
		$photoImage5 = isset($_FILES['sortpic5'])? $_FILES['sortpic5'] : null;
		$imageName = '';
		$po_number = isset($this->data['po_number']) ? $this->data['po_number'] : null ;
		$purchasePaidBy = isset($this->data['purchase_paid_by']) ? $this->data['purchase_paid_by'] : null ;
		$loggedUser = $this->Common->getLoggedUserID();

		if ($do_save == 1){
			//echo 'Perform Update';
			$items = $_POST['data']['items'];

			if (isset($_POST['invoice'])){
				$invoice = $_POST['invoice'];
			}
			if($status_id == 9){
				$status_id = 5;
			}
			
			foreach($items as $item){		
				$newItems[] = $item;
				$techId = explode(':', $item['location']);
				$oldTech = explode(':', $item['old_location']);

				if($techId[1] == $oldTech[1]){					
					if($item['quantity'] > $item['old_qty'])
					{
						$addQty = $item['quantity'] - $item['old_qty'];
						$qry = "UPDATE ace_rp_tech_inventory_item set quantity = quantity + ".$addQty." where item_id = ".$item['item_id']." AND tech_id=".$techId[1];
						$res = $db->_execute("UPDATE ace_rp_tech_inventory_item set quantity = quantity + ".$addQty." where item_id = ".$item['item_id']." AND tech_id=".$techId[1]);
					} else if($item['quantity'] < $item['old_qty']){
						$removeQty = $item['old_qty'] - $item['quantity'];
						$res = $db->_execute("UPDATE ace_rp_tech_inventory_item set quantity = quantity - ".$removeQty." where item_id = ".$item['item_id']." AND tech_id=".$techId[1]);
					}
				}
				
				if($techId[1] != $oldTech[1]){
					$now = date('Y-m-d');
					$checkExisting = $db->_execute("SELECT * FROM ace_rp_tech_inventory_item where tech_id = ".$techId[1]." AND item_id =".$item['item_id']."");
					$row = mysql_fetch_array($checkExisting, MYSQL_ASSOC);
					if(!empty($row))
					{
						$res = $db->_execute("UPDATE ace_rp_tech_inventory_item set quantity = quantity + ".$item['quantity']." where item_id = ".$item['item_id']." AND tech_id=".$techId[1]);
					} else {
						$db->_execute("INSERT INTO ace_rp_tech_inventory_item (tech_id, item_id, quantity, updated_date) VALUES (".$techId[1].",".$item['item_id'].", ".$item['quantity'].", '".$now."')");
					}
					
					$res = $db->_execute("UPDATE ace_rp_tech_inventory_item set quantity = quantity - ".$item['quantity']." where item_id = ".$item['item_id']." AND tech_id=".$oldTech[1]);

					 $db->_execute("UPDATE ace_iv_movements set to_location_id = ".$techId[1]." where asset_id = ".$item['asset_id']."");
				}
				// Update Location

				//Update Item Info
				
				// Update SKU
				if (isset($item['item_id'])){
					$query = "
						UPDATE ace_iv_sku SET sku = '{$item['part_number']}'
						WHERE item_id = '{$item['item_id']}' AND supplier_id = '{$item['from_location_id']}'
					";
					$db->_execute($query);
				}
				
				//Update price
					$query = "
						UPDATE ace_iv_assets SET regular_price = '{$item['price']}', refund_quantity = '{$item['refund_quantity']}', qty = {$item['quantity']}, purchase_price = {$item['price']}
						WHERE item_id = '{$item['item_id']}' AND movement_id = '{$item['movement_id']}'
					";
					$db->_execute($query);
				
				//Update Invoice
				if (isset($invoice)){
					$query = "
						UPDATE ace_iv_invoice SET invoice = '{$invoice}', status_id = '{$status_id}', purchase_paid_by = '{$purchasePaidBy}'
						WHERE invoice_id = '{$doc_id}'
					";
					$db->_execute($query);
				}

				$db->_execute("UPDATE ace_iv_invoice_item_history set quantity = '".$item['quantity']."', purchase_price=".$item['price']."  where item_id = ".$item['item_id']." and invoice_id=".$doc_id);
				 $db->_execute("UPDATE ace_iv_items set name = '".$item['name']."', sku='".$item['part_number']."',supplier_price = ".$item['price']."  where id = ".$item['item_id']."");
				 $db->_execute("UPDATE iv_items_labeled2 set name = '".$item['name']."', sku='".$item['part_number']."',supplier_price = ".$item['price']."  where id = ".$item['item_id']."");
			}
			if($fromBooking == 2){
				$newArray = json_encode($newItems);
				echo "<script>
				window.returnPurchaseItem=".$newArray.";
				window.close();
				</script>";
			} else {
				$this->redirect('inventories/'.$_REQUEST['rurl']);
			}
		}
		
		// For everything that isn't a movement/refund type
		// Which should only be for purchasing items??
		if ($doc_type != 2 && $doc_type != 5 && !$do_save) {

			$query = "SELECT MAX(id) as last_id FROM ace_iv_movements";
			$result = $db->_execute($query);
			$results = mysql_fetch_array($result, MYSQL_ASSOC);
			if ($results['last_id'] == NULL) {
				$doc_id = 1;
			}
			else {
				$doc_id = $results['last_id'] + 1;
			}
			
			$from_location_id = $supplier_id;
			if($doc_type == 1 || $doc_type == 7) {
				foreach ($this->data['items'] as $cur){	
				if($cur['sub_cat_id'] == 16)
				// if($cur['sub_cat_id'] == 23)
             	{
             		// Local catId = 37, sub cat = 26, cat=8,sub=34
             		//Live catId = 36, sub cat = 101
             		$db->_execute("INSERT INTO ace_iv_items (sku,name, description1, description2,efficiency, model,  iv_category_id, iv_brand_id, iv_supplier_id, supplier_price, selling_price, regular_price, active, iv_sub_category_id) VALUES ('".$cur['sku']."', '".mysql_real_escape_string($cur['name'])."', '','', '','',".$cur['new_cat_id'].",'','".$supplierId."', '".$cur['price']."','".$cur['price']."','','1', ".$cur['new_sub_cat_id'].")"); 
             		$lastinsertID = $db->lastInsertId();
             		 $item_label2 = "INSERT INTO iv_items_labeled2 (sku,id,name, description1, description2,efficiency, model, brand, category, supplier,  category_id, brand_id, supplier_id, supplier_price, selling_price, regular_price, active, sub_category_id, sub_category_name) VALUES ('".$cur['sku']."',".$lastinsertID.", '".mysql_real_escape_string($cur['name'])."', '','', '','','' ,'One Time Purchase' ,'' ,".$cur['new_cat_id'].",'','".$supplier_id."', '".$cur['price']."','".$cur['price']."','','1', ".$cur['new_sub_cat_id'].", 'Inactive')";
               		$res = $db->_execute($item_label2);   
               		$cur['item_id'] = $lastinsertID;   
               		$cur['sub_cat_id'] = $cur['new_sub_cat_id'];   
               		$cur['cat_id'] = $cur['new_cat_id'];   
             	}
             			$cur['invoiceId'] = $doc_id;
						$this->saveTechItems($cur);
						
						$this->invoiceItemHistory($cur,$invoice_number,$doc_id,$supplier_id,$order_number,$purchasePaidBy);
						// $doc_type 1 = purchase
						//Add the items to the inventory
							$location_data = $this->GetLocationIDFromString($cur['location']);
							$fromLocation = explode(':', $cur['location']);
							// store $assets array for insertion into movements table
							$asset = array();
							// store into assets table
							$this->Common->itemTransaction($doc_id, $cur['item_id'],$cur['name'],$cur['quantity'],$cur['price'],$cur['purchase_price'],'',$location_data["id"],$moveDate);
							$asset['id'] = $this->Common->itemAssetTransaction($cur['item_id'],$cur['purchase_price'], $cur['price'],$cur['quantity']);
							$asset['location_id'] = $fromLocation[1];
							$assets[] = $asset;
							
							$this->_store_sku($cur['item_id'], $supplier_id, $cur['sku']);

							$newItems[] = $cur;
					}
			}
			
			//Add the assets as a movement
			//-----------------------------
			
			foreach ($assets as $asset){
				//Insert
				$date = date("Y-m-d G:i:s");
				$userID = (integer)$this->Common->getLoggedUserID();
				$query = "INSERT INTO ace_iv_movements (id, asset_id, from_location_id, to_location_id, date, user_id)
							VALUES ('$doc_id', '{$asset[id]}', '$from_location_id', '{$asset['location_id']}', '$date', '$userID')";
				$db->_execute($query);
				
				// update movement_id in ace_iv_assets for this asset
				$query = "UPDATE ace_iv_assets SET movement_id = {$doc_id} WHERE asset_id = '{$asset[id]}'";
				$db->_execute($query);
			}

			// Forward user where they need to be - if this is a single action per view
			
				// die("here");
			
			//Loki: For new refund Invoice.
			if(!empty($photoImage['name']))
	        {       
	            $path = $photoImage['name'];
				$ext = pathinfo($path, PATHINFO_EXTENSION);
				$imageName = date('Ymdhis', time()).'_'.$path.'.'.$ext;
				if ( 0 < $file['error'] ) {
	        		// echo 'Error: ' . $_FILES['image']['error'] . '<br>'; 
			    } else {
			        move_uploaded_file($photoImage['tmp_name'], ROOT."/app/webroot/purchase-invoice-images/".$imageName);
			    }
	        }
	        //Loki: For technician payment image.
			if(!empty($photoImage5['name']))
	        {       
	            $path = $photoImage5['name'];
				$ext = pathinfo($path, PATHINFO_EXTENSION);
				$imageName1 = date('Ymdhis', time()).'_'.$path.'.'.$ext;
				if ( 0 < $file['error'] ) {
	        		// echo 'Error: ' . $_FILES['image']['error'] . '<br>'; 
			    } else {
			        move_uploaded_file($photoImage5['tmp_name'], ROOT."/app/webroot/purchase-invoice-images/".$imageName1);
			    }
	        }
	        
			if($doc_type == 7)	
			{
				$query = "INSERT INTO ace_iv_invoice (invoice_id, invoice, status_id, pay_date, reference_no, refund_invoice_id, returned_by, supplier_rep, refund_time	, payment_method,doc_type, total_purchase_amount,remaining_amount,invoice_image, po_number,supplier_info,created_by) VALUES ('{$doc_id}', '{$this->data['invoice_number']}','{$status_id}', '{$refundDate}','{$this->data['ref_no']}', '{$this->data['returned_invoice']}', '{$this->data['Returned_by']}', '{$this->data['supplier_rep']}', '{$this->data['refund_time']}', '{$this->data['payment_method']}', '{$doc_type}', '{$total_purchase_amount}','{$total_purchase_amount}', '{$imageName}', '{$po_number}' ,'{$supplier_info}','{$loggedUser}')";
				$res = $db->_execute($query);
			} else {
				$query = "INSERT INTO ace_iv_invoice (invoice_id, invoice, status_id, doc_type, total_purchase_amount,remaining_amount,invoice_image, po_number,supplier_info,tech_pay_method, tech_pay_image, order_id,purchase_paid_by,created_by) VALUES ('{$doc_id}', '{$this->data['invoice_number']}','6', '{$doc_type}', '{$total_purchase_amount}','{$total_purchase_amount}', '{$imageName}', '{$po_number}','{$supplier_info}','{$techPayMethod}','{$imageName1}','{$order_number}','{$purchasePaidBy}','{$loggedUser}')";
				$db->_execute($query);

			}
			$db->_execute("INSERT INTO ace_rp_invoice_po_number (invoice_id, po_number) VALUES ('{$doc_id}', '{$po_number}')");
			if($fromBooking == 1 || $fromBooking == 2){
				$newArray = json_encode($newItems);
				echo "<script>
				window.returnPurchaseItem=".$newArray.";
				window.purchase='".$supplier_id.",".$invoice_number."';
				window.close();
				</script>";
			}else if($fromTech == 1){
				$this->redirect('orders/invoiceTablet');
			}
			else {
				$this->redirect('inventories/'.$_REQUEST['rurl']);
			}
			// $this->redirect('inventories/supplies');
		} 
		//-----------
		
		
		
		// moving or refunding items
		if (($doc_type == 2 || $doc_type == 5) && !$do_save) {
			// $doc_type 2 = moving items
			// $doc_type = 5 = refund

			$query = "SELECT MAX(id) as last_id FROM ace_iv_movements";
			$result = $db->_execute($query);
			$results = mysql_fetch_array($result, MYSQL_ASSOC);
			if ($results['last_id'] == NULL) {
				$doc_id = 1;
			}
			else {
				$doc_id = $results['last_id'] + 1;
			}
			
			
			$movements_count = 0;
			
			//echo "<pre>";print_r($this->data);echo "</pre>";die();
			
			foreach($this->data['moveto']['items'] as $item){
				
				//echo "<pre>";print_r($item);echo "</pre>";die();
				
				// $item['current_location'] = location_id
				// $item['tolocation'] = type:id - warehouse:0 or tech:xxxx
				
				$quant = $item['quantity'];
				
				$from_location_id = $item['current_location'];
				
				$tolocation = explode(':', $item['tolocation']);
				
				// currently can only move to warehouse or user, or refund to supplier. TO DO: transit
				if ($tolocation[0] == 'warehouse') {
					$location_type = 'Warehouse';
				}
				else if ($tolocation[0] == 'supplier') {
					$location_type = 'Supplier';
				}
				else {
					$location_type = 'User';
				}
				$location_number = $tolocation[1];
				
				$query = "SELECT * FROM ace_iv_locations WHERE type='{$location_type}' AND number='{$location_number}'";
				$result = $db->_execute($query);
				$results = mysql_fetch_array($result, MYSQL_ASSOC);
				$to_location_id = $results['id'];
				
				if ($from_location_id != $to_location_id) {
					// not moving to the same location.
					// If we're moving to the same location, we do nothing
					
					// retrieve items that match this item_id and is in the location we are moving from, but limit to the
					// quantity being moved
					$query = "
						SELECT *
						FROM ace_iv_assets assets
						LEFT JOIN ace_iv_movements movements ON assets.movement_id = movements.id AND assets.asset_id = movements.asset_id
						WHERE assets.item_id = '{$item['item_id']}' AND movements.to_location_id = '{$item['current_location']}'
						LIMIT 0, {$quant}
					";
					$result = $db->_execute($query);
					
					while ($results = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$date = date("Y-m-d G:i:s");
						$userID = (integer)$this->Common->getLoggedUserID();
						if (!$to_location_id) {
							$to_location_id = 1;
						}
						$query = "INSERT INTO ace_iv_movements (id, asset_id, from_location_id, to_location_id, date, user_id)
							  VALUES ('$doc_id', '{$results[asset_id]}', '{$from_location_id}', '{$to_location_id}', '$date', '$userID')";
						$db->_execute($query);
						
						// set price per item for refunds
						if ($doc_type == 5) {
							$query = "INSERT INTO ace_iv_refund_price (asset_id, refund_regular_price)
								  VALUES ('{$results[asset_id]}', '{$item['refund_regular_price']}')";
							$db->_execute($query);
						}
						
						// update current movement_id for this asset
						$query = "UPDATE ace_iv_assets SET movement_id = '{$doc_id}' WHERE asset_id = '{$results[asset_id]}'";
						$db->_execute($query);
						
						// We should probably design the DB structure better.  We need to make sure here that there are actually movements inserted
						// into the DB.  If for any reason there are none, then no movements with $doc_id will be created, but below a record will
						// still be inserted for the invoice.  This currently leads to problems because we use the ace_iv_movements table to determine
						// the next $doc_id.  The $doc_id would become inconsistent if this scenario were to happen ($doc_id inserted into invoice table but
						// not into movements table)
						$movements_count++;
					}
					
				}
			}
			
			// insert invoice # for refund
			if ($doc_type == 5 && $movements_count && !$do_save) {
				// $movements count is just a check to make sure there were actually movements inserted into the ace_iv_movements table, to avoid
				// inconsistencies with doc_id across tables
				//Insert Invoice
				$query = "INSERT INTO ace_iv_invoice (invoice_id, invoice, original_invoice_id, status_id, credited_date, note) VALUES
				('{$doc_id}',
				'{$this->data['invoice_number']}',
				'{$original_invoice_id}',
				'{$this->data['status_id']}',
				'{$this->data['credited_date']}',
				'{$this->data['note']}')";
				$db->_execute($query);
			}
			
			$this->redirect('inventories/'.$_REQUEST['rurl']);
		}
		
		// refunding items	
		//if ($doc_type == 5){
		//	// $doc_type 5 = refund
		//	//Handle update of fields (for moving items);			
		//	$item_id = $this->data['moveto']['items'][1]['item_id'];
		//	$location_id = $this->data['moveto']['items'][1]['tolocation'];
		//	$quantity = $this->data['moveto']['items'][1]['quantity'];
		//	
		//	// retrieve items that match this item_id and is in the location we are refunding from, but limit to the
		//	// quantity being moved
		//	$query = "
		//			SELECT item_id, locations.id, suppliers.name AS supplier_name,
		//						items.name AS name, items.model, movements.date, assets.regular_price, movements.id AS movement_id,
		//						movements.asset_id AS asset_id, movements.to_location_id
		//				
		//		  FROM ace_iv_movements movements
		//		  LEFT JOIN ace_iv_assets assets ON movements.asset_id = assets.asset_id
		//		  LEFT JOIN ace_iv_locations locations ON movements.from_location_id = locations.id
		//		  LEFT JOIN ace_rp_suppliers suppliers ON locations.number = suppliers.id
		//		  LEFT JOIN ace_iv_items items ON assets.item_id = items.id
		//		  WHERE locations.type = 'Supplier' AND locations.id = '{$location_id}' AND item_id = '{$item_id}' AND movements.from_location_id <> movements.to_location_id
		//		  LIMIT {$quantity}
		//		";
		//	$result = $db->_execute($query);
		//	while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
		//		$row_loc_id = $row['to_location_id'];
		//		$row_id = $row['movement_id'];
		//		$row_asset_id = $row['asset_id'];
		//		
		//		$query = "UPDATE ace_iv_movements SET to_location_id = {$location_id} WHERE id = '{$row_id}' AND asset_id = '{$row_asset_id}'";
		//		$db->_execute($query);
		//	}
		//	$this->redirect('inventories/'.$_REQUEST['rurl']);
		//}
	}
	
	/**
	* Convert an old location to the new location data
	**/
	function _convert_old_location_to_new($location){
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$location_data = explode(":", $location);
		if ($location_data[0] == 'tech') $location_data[0] = 'User';
		if ($location_data[0] == 'warehouse') $location_data[0] = 'Warehouse';
		
		$query = "SELECT id, type, number FROM ace_iv_locations WHERE type = '{$location_data['0']}' AND number = '{$location_data['1']}'";
		$result = $db->_execute($query);
		$results = mysql_fetch_array($result, MYSQL_ASSOC);
		return $results;
	}
	
	/**
	* Convert a new location to the old location data
	**/
	function _convert_new_location_to_old($location){
		
		$output = str_replace('User', 'tech', $location);
		$output = str_replace('Warehouse', 'warehouse', $location);
		
		
		return $output;
	}
	
	function _store_sku($item_id, $supplier_id, $sku) {
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$query = "SELECT * FROM ace_iv_sku
				  WHERE item_id = '{$item_id}' AND supplier_id = '{$supplier_id}'";
		$result = $db->_execute($query);
		if (mysql_num_rows($result) > 0) {
			// SKU exists, update with new SKU
			$query = "UPDATE ace_iv_sku SET sku = '{$sku}' WHERE item_id = '{$item_id}' AND supplier_id = '{$supplier_id}'";
			$result = $db->_execute($query);
		}
		else {
			// SKU doesn't exist, add to SKU table
			$query = "INSERT INTO ace_iv_sku (item_id, supplier_id, sku) VALUES ({$item_id}, {$supplier_id}, '{$sku}')";
			$result = $db->_execute($query);
		}
	}
	
	//transaction log
	function _transaction(
			$doc_id, $item_id, $item_name, $item_qty, 
			$item_selling_price, $item_purchase_price, 
			$item_model_number, $item_location, $move_date) {
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$query = "
			INSERT INTO ace_iv_transactions SET
				doc_id = $doc_id,
				item_id = '$item_id',
				item_name = '$item_name',
				item_qty = '$item_qty',
				item_selling_price = '$item_selling_price',
				item_purchase_price = '$item_purchase_price',
				item_model_number = '$item_model_number',
				user_id = ".$this->Common->getLoggedUserID().",
				item_location = $item_location,
				move_date = $move_date
		";
		$db->_execute($query);
	}
	
	//Loki: Commented
	/*function editDefault()
	{
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$this->layout="list";
		
		$items = array();
		
		$query = "select * from ace_rp_inventory_default d";
		
		$result = $db->_execute($query);
		$num=0;
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$num++;
			foreach ($row as $k => $v)
				$items[$num][$k] = $v;
		}
		
		$this->set('items', $items);		
	}*/

	//reuseable item = 26, parts=8
	function editDefault()
	{
		// error_reporting(E_ALL);
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$fromUser = isset($_GET['from_user']) ? $_GET['from_user'] : 0;
		$this->layout="list";
		$allTechnicians = $this->Lists->Technicians();
		$now = date("d M Y");
		$items = array();
		if($fromUser > 0)
		{		
			// $query = "SELECT it.quantity,il.* FROM  ace_rp_tech_inventory_item it 
			// 		JOIN iv_items_labeled2 il ON it.item_id = il.id WHERE il.tech_default = 1 AND il.active = 1 AND it.tech_id =".$fromUser." order by il.name";
			$query = "SELECT it.quantity,il.* FROM  ace_rp_tech_inventory_item it 
					JOIN iv_items_labeled2 il ON it.item_id = il.id WHERE it.tech_id =".$fromUser." order by il.name";

			$result  = $db->_execute($query);
			while($row = mysql_fetch_array($result)) {	
				if($row['quantity'] > 0)
				{
					$items[] = $row;
				}
			}
			$techArr = $this->Lists->inventoryTech($fromUser);
			
		} else {		
			$query = "SELECT * FROM iv_items_labeled2 WHERE tech_default = 1 AND active = 1";
			$result  = $db->_execute($query);
			while($row = mysql_fetch_array($result)) {
				$row['tech_data'] = $this->getInventoryTechQty($row['id']);	
				$items[] = $row;
			}
			$techArr = $this->Lists->inventoryTech();
		}

		$this->set('inventoryTechnician', $techArr);		
		$this->set('fdate', $now);		
		$this->set('fromUser', $fromUser);		
		$this->set('items', $items);		
		$this->set('allTechnicians', $allTechnicians);		
	}
	/*Loki: move item to tech.
		Category: reuseable item = 26, parts=8, One time item: 37
	*/
	function moveTechItem()
		{
			// $categoryId = isset($_GET['category_id']) ? $_GET['category_id'] : 8;
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$this->layout="list";
			$techId = isset($_GET['techId']) ? $_GET['techId'] :0;
			$fromBooking = isset($_GET['fromBooking']) ? $_GET['fromBooking'] :0;
			$bookingItems = $_GET['bookingItems'];
			$search = isset($_GET['search']) ? $_GET['search'] :'';
			$allTechnicians = $this->Lists->Technicians();
			$now = date("d M Y");
			$limit = 50;
			// $total_pages = 0;
			// $pageNo = isset($_GET['currentPage']) ? $_GET['currentPage'] : 1;
			// if($pageNo == 0)
			// {
			// 	$pageNo	= 1;
			// }
			// $record_index = ($pageNo-1) * $limit;      
			
			if ($_GET['search']){
					$sqlConditions .= " AND i.name LIKE '%{$_GET['search']}%'";
			}

			if($fromBooking == 1){
				
				$query = "SELECT i.* FROM iv_items_labeled2 i
				JOIN ace_iv_sub_categories sub ON i.sub_category_id  = sub.id
				WHERE i.id IN (".$bookingItems.")".$sqlConditions;
			} else {
				$query = "SELECT i.* FROM iv_items_labeled2 i
				JOIN ace_iv_sub_categories sub ON i.sub_category_id  = sub.id
				WHERE i.category_id NOT IN (1,37) AND sub.name != 'Inactive' AND i.active = 1".$sqlConditions;

				// $query = "SELECT i.* FROM iv_items_labeled2 i
				// JOIN ace_iv_sub_categories sub ON i.sub_category_id  = sub.id
				// WHERE i.category_id IN (8,26,36) AND sub.name != 'Inactive' AND i.active = 1";
			}

			$result  = $db->_execute($query);
			$items = array();
			while($row = mysql_fetch_array($result)) {
				$row['tech_data'] = $this->getInventoryTechQty($row['id']);	
				$items[] = $row;
			}
			$techArr = $this->Lists->inventoryTech();
			
			$this->set('techId', $techId);		
			$this->set('fromBooking', $fromBooking);		
			$this->set('search', $search);		
			$this->set('inventoryTechnician', $techArr);		
			$this->set('fdate', $now);		
			$this->set('items', $items);		
			$this->set('allTechnicians', $allTechnicians);		
		}
	function saveDefault()
	{
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$query = "delete from ace_rp_inventory_default";
		$db->_execute($query);
		
		foreach ($this->data['items'] as $cur)
		{
			$query = "INSERT INTO ace_rp_inventory_default
								(item_id, qty, name) 
								VALUES ('{$cur['item_id']}', '{$cur['quantity']}', '{$cur['name']}')";
			$db->_execute($query);
		}

		// Forward user where they need to be - if this is a single action per view
		$this->redirect('/inventories/AllDocuments');
	}
	
	function requests()
	{
		$this->layout="list";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
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
			$sqlConditions .= " AND docdate >= '".$this->Common->getMysqlDate($fdate)."'"; 
		if($tdate != '')
			$sqlConditions .= " AND docdate <= '".$this->Common->getMysqlDate($tdate)."'"; 
		
		$techid = $_GET['ftechid'];
		if ($_SESSION['user']['role_id'] == 1) { // TECHNICIAN=1
			//show data only for current technician
			$techid = $this->Common->getLoggedUserID();
		}
		$sqlConditions1 = '';
		if (($techid>=0)&&($techid!=''))	$sqlConditions1 = " and d.tech_id=$techid"; 		
		
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		if (!$order) $order = 'd.docdate desc';
		
		$allTechnicians = $this->Lists->Technicians();
		$allStatuses = $this->GetAllStatuses();
		
		$query = "select *
								from ace_rp_inventory_requests d, ace_rp_inventory_request_items i
							 where d.flagactive=1 $sqlConditions $sqlConditions1 and d.id=i.doc_id
							 order by ".$order.' '.$sort;
		
		$num=0;
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$num++;
			foreach ($row as $k => $v)
			  $items[$num][$k] = $v;
			$items[$num]['tech_name'] = $allTechnicians[$row['tech_id']];
			$items[$num]['status_name'] = $allStatuses[$row['status']];
		}
		
		$this->set('documents', $items);
		$this->set('allTechnicians', $allTechnicians);
		$this->set('allStatuses', $allStatuses);
		$this->set('techid', $techid);
		$this->set('prev_fdate', date("d M Y", strtotime($fdate) - 24*60*60));
		$this->set('next_fdate', date("d M Y", strtotime($fdate) + 24*60*60));
		$this->set('prev_tdate', date("d M Y", strtotime($tdate) - 24*60*60));
		$this->set('next_tdate', date("d M Y", strtotime($tdate) + 24*60*60));
		$this->set('fdate', date("d M Y", strtotime($fdate)));
		$this->set('tdate', date("d M Y", strtotime($tdate)));
		$this->set("ismobile", $this->Session->read("ismobile"));
	}
	
	function editRequest()
	{
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$this->layout="list";
		
		$id = $_REQUEST['id'];
		$docdate = date('d M Y');
		$ref_number = '';
		$customer_id = '';
		$supplier_id = '';
		$tech_id = '';
		$po_number = '';
		$status = 1;
		$items = array();
		if ($_SESSION['user']['role_id'] == 1) { // TECHNICIAN=1
			//show data only for current technician
			$tech_id = $this->Common->getLoggedUserID();
		}
		
		$allTechnicians = $this->Lists->Technicians();
		$allSuppliers = $this->Lists->ListTable('ace_rp_suppliers');

		if ($id)
		{
			$query = "select *
									from ace_rp_inventory_requests d
								 where d.id=$id";
			
			$result = $db->_execute($query);
			if($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				$docdate = $row['docdate'];
				$tech_id = $row['tech_id'];
				$ref_number = $row['ref_number'];
				$customer = $row['customer'];
				$unit_make = $row['unit_make'];
				$unit_model = $row['unit_model'];
				$unit_serial = $row['unit_serial'];
				$notes = $row['notes'];
				$customer_id = $row['customer_id'];
				$order_id = $row['order_id'];
				$status = $row['status'];
				$supplier_id = $row['supplier_id'];
				$po_number = $row['po_number'];
				$item_image1 = $row['item_image1'];
				$item_image2 = $row['item_image2'];
			}

			$query = "select *
									from ace_rp_inventory_request_items d
								 where d.doc_id=$id";
			
			$result = $db->_execute($query);
			$num=0;
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				$num++;
				foreach ($row as $k => $v)
					$items[$num][$k] = $v;
			}
		}
		
		$this->set('items', $items);		
		$this->set('docdate', date('d M Y', strtotime($docdate)));
		$this->set('ref_number', $ref_number);		
		$this->set('id', $id);		
		$this->set('notes', $notes);		
		$this->set('tech_id', $tech_id);		
		$this->set('customer', $customer);		
		$this->set('status', $status);		
		$this->set('customer_id', $customer_id);		
		$this->set('order_id', $order_id);		
		$this->set('unit_make', $unit_make);		
		$this->set('unit_model', $unit_model);		
		$this->set('unit_serial', $unit_serial);		
		$this->set('po_number', $po_number);		
		$this->set('item_image1', $item_image1);		
		$this->set('item_image2', $item_image2);		
		$this->set('allTechnicians', $this->Common->getSelector($allTechnicians,'tech_id',$tech_id));
		$this->set('allStatuses', $this->Common->getSelector($this->GetAllStatuses(),'status',$status));
		$this->set('suppliers', $this->Common->getSelector($allSuppliers,'supplier',$supplier_id));
	}

	function saveRequest()
	{
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$id = $this->data['id'];
		$doc_date = date("Y-m-d",strtotime($this->data['doc_date']));
		$ref_number = $this->data['ref_number'];
		$tech_id = $this->data['tech_id'];
		$customer = $this->data['customer'];
		$unit_make = $this->data['unit_make'];
		$unit_model = $this->data['unit_model'];
		$unit_serial = $this->data['unit_serial'];
		$notes = $this->data['notes'];
		$customer_id = $this->data['customer_id'];
		$order_id = $this->data['order_id'];
		$status = $this->data['status'];
		$po_number = $this->data['po_number'];
		$supplier = $this->data['supplier'];
		
		if ($id)
		{
			$query = "UPDATE ace_rp_inventory_requests 
									 SET tech_id='$tech_id', unit_make='$unit_make', unit_model='$unit_model', unit_serial='$unit_serial',
									     notes='$notes', docdate='$doc_date', ref_number='$ref_number', customer='$customer',
											 customer_id='$customer_id', order_id='$order_id', status='$status', po_number='$po_number',
											 supplier_id='$supplier'
								 WHERE id=$id";
			$db->_execute($query);
			if ($status==2)
			{
				$sql = "INSERT INTO ace_rp_messages
								(txt, state, from_user, from_date, 
								 to_user, to_date, to_time)
				 VALUES ('Parts have been ordered', 0, ".$this->Common->getLoggedUserID().", current_date(),
								 $tech_id, current_date(), '00:00')";
				$db->_execute($sql);
			}
		}
		else
		{
			$query = "INSERT INTO ace_rp_inventory_requests
								(tech_id, unit_make, unit_model, unit_serial, notes,
								 docdate, flagactive, ref_number, customer, customer_id, order_id, status, po_number, supplier_id) 
								VALUES ($tech_id, '$unit_make', '$unit_model', '$unit_serial', '$notes',
								'$doc_date', 1, '$ref_number', '$customer', '$customer_id', '$order_id', '$status', '$po_number', '$supplier')";
			$db->_execute($query);
			$id = $db->lastInsertId();
			$sql = "INSERT INTO ace_rp_messages
							(txt, state, from_user, from_date, 
							 to_user, to_date, to_time)
			 VALUES ('<a target=\"main_view\" href=\"/acebeta/index.php/inventories/editRequest?id=$id&rurl=requests%3F\">Parts requested by tech</a>: $unit_make $unit_model ($unit_serial) $notes', 0, ".$this->Common->getLoggedUserID().", current_date(),
							 57499, current_date(), '00:00')";
			$db->_execute($sql);
			$sql = "INSERT INTO ace_rp_messages
							(txt, state, from_user, from_date, 
							 to_user, to_date, to_time)
			 VALUES ('<a target=\"main_view\" href=\"/acebeta/index.php/inventories/editRequest?id=$id&rurl=requests%3F\">Parts requested by tech</a>: $unit_make $unit_model ($unit_serial) $notes', 0, ".$this->Common->getLoggedUserID().", current_date(),
							 44851, current_date(), '00:00')";
			$db->_execute($sql);
		}
		
		$query = "delete from ace_rp_inventory_request_items where doc_id=$id";
		$db->_execute($query);
		
		foreach ($this->data['items'] as $cur)
		{
			$query = "INSERT INTO ace_rp_inventory_request_items
								(doc_id, item_id, qty, name, part_number) 
								VALUES ($id, '{$cur['item_id']}', '{$cur['quantity']}',
								'{$cur['name']}', '{$cur['part_number']}')";
			$db->_execute($query);
		}

		// Forward user where they need to be - if this is a single action per view
		$this->redirect('inventories/'.$_REQUEST['rurl']);
	}

	// AJAX method to get the 
	function getOrderInfo()
	{
		$ref_number = $_GET['ref_number'];
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "select c.first_name, c.last_name, o.id, o.customer_id
		from ace_rp_orders o, ace_rp_customers c
		where o.customer_id=c.id and o.order_number='$ref_number'";
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$aRet = array('order_id' => $row['id'],
									'customer_id' => $row['customer_id'],
									'customer_name' => $row['first_name']." ".$row['last_name']
									);
		
		echo json_encode($aRet);
		exit;
	}

	function giveToTech()
	{
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$tech_id = $_REQUEST['tech'];
		$item_name = $_REQUEST['item'];
		$part_number = $_REQUEST['part_number'];
		$qty = $_REQUEST['qty'];
		$doc_type = 2;
		$doc_date = date("Y-m-d", strtotime($_REQUEST['date']));
		if (!$_REQUEST['date']) $doc_date = date("Y-m-d");
		
		$query = "INSERT INTO ace_rp_inventory_documents
							(opertype, docdate, supplier_id, tech_id, flagactive) 
							VALUES ($doc_type, '$doc_date', '0', '$tech_id', 1)";
		$db->_execute($query);
		$doc_id = $db->lastInsertId();
		
		$query = "INSERT INTO ace_rp_inventory_docitems
							(doc_id, item_id, qty, price, name, purchase_price, part_number) 
							VALUES ($doc_id, '1001', '$qty', '0', '$item_name', '0',	'$part_number')";
		$db->_execute($query);
		
		exit;
	}
	
	function hideItemStatus()
	{
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$item_id = $_GET['item_id'];
		
		$query = "
			UPDATE ace_rp_inventory_start SET
				`show` = 0
			WHERE item_id = $item_id
		";
		$db->_execute($query);
		
		echo $query;
		exit;
	}
	
	function edit_setup()
	{
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$this->layout="list";
		
		$items = array();
		
		$query = "select * from ace_rp_inventory_start d";
		
		$result = $db->_execute($query);
		$num=0;
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$num++;
			foreach ($row as $k => $v)
				$items[$num][$k] = $v;
		}
		
		if ($num==0) $date_doc=date('d M Y');
		else $date_doc=date('d M Y', strtotime($items[1]['date_doc']));
		
		$this->set('items', $items);		
		$this->set('date_doc', $date_doc);		
	}

	function save_setup()
	{
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$date_doc=date('Y-m-d', strtotime($this->data['date_doc']));
		
		$query = "delete from ace_rp_inventory_start";
		$db->_execute($query);
		
		foreach ($this->data['items'] as $cur)
		{
			$query = "INSERT INTO ace_rp_inventory_start
								(item_id, qty, name, part_number, date_doc) 
								VALUES ('{$cur['item_id']}', '{$cur['quantity']}', '{$cur['name']}', '{$cur['part_number']}', '{$date_doc}')";
			$db->_execute($query);
		}

		$this->redirect('/inventories/index');
	}
	
	function StoreTechColumns() {
		
		$checked = $this->data['checked'];
		$tech_id = $this->data['tech_id'];
		$cur_user = $this->Common->getLoggedUserID();
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		if ($cur_user) {
			if ($checked == 'true') {
				$query = "INSERT INTO ace_iv_tech_columns VALUES({$tech_id}, {$cur_user})";
				$result = $db->_execute($query);
			}
			else if ($checked == 'false') {
				$query = "DELETE FROM ace_iv_tech_columns WHERE checked_id = {$tech_id} AND user_id = {$cur_user}";
				$result = $db->_execute($query);
			}
		}
		
		echo '';
		exit;
	}
	
	function BestLocationType($type) {
		if ($type == 'tech') {
			$type = 'User';
		}
		$opts = explode(" ", "Transit Warehouse Truck Supplier Customer User");
		$nearest = $opts[0];
		$diff = similar_text($type,$opts[0]);
		foreach($opts as $opt) {
			$new_diff = similar_text( $type, $opt );
			if ($new_diff>$diff) {
				$nearest = $opt;
				$diff = $new_diff; } }
		return $nearest; }
		
	function GetLocationIDFromString($str) {
		// should return the id in ace_iv_locations that match $str
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$parts = explode(":",$str); // format should be "type:number"
		if (count($parts)==1) {
			$parts = explode(" ",$str); } // might be "type number"
		if (count($parts)==1) {
			$parts[1] = "0"; } // assume 0 if no num can be determined
		//$type = $this->BestLocationType($parts[0]);
		$type = $parts[0];
		$number = (int)$parts[1];
		$query = "SELECT id FROM ace_iv_locations WHERE type = '$type' AND number = $number";
		$result = $db -> _execute( $query );
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		if (!$row) {
			return False; }
		return array("type"=>$type,"number"=>$number,"id"=>$row['id']); 
	}

	function searchSuppliers()
	{
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$needle = $_GET['query'];
		if(!empty($needle)){
			$query = "SELECT * FROM ace_rp_suppliers WHERE name LIKE \"%$needle%\"";
			$results = $db->_execute($query);
			$response = array();
			while ($row = mysql_fetch_assoc($results)) {
				$response[] = $row; 
			}	
			print json_encode($response);
			exit();
		}
	}
	//This function get all the invoice history and show.
	function showInvoiceHistory()
	{
		$invoiceId = $_GET['invoiceId'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "SELECT ih.*, invs.status as status_name,
		ppm.name as payment_method_name, CONCAT(return_user.first_name, ' ',return_user.last_name) as return_user,
			CONCAT(paid_by.first_name, ' ',paid_by.last_name) as paid_user
			from ace_iv_invoice_history ih 
			LEFT JOIN ace_iv_invoice_status invs ON invs.id = ih.status_id
			LEFT JOIN ace_rp_purchase_payment_method ppm ON ppm.id = ih.payment_method
			LEFT JOIN ace_rp_users return_user ON return_user.id = ih.returned_by
			LEFT JOIN ace_rp_users paid_by ON paid_by.id = ih.paid_by 
		 	where ih.invoice_id=".$invoiceId." order by ih.id desc";

		 	// print_r($query); die;
		$result = $db->_execute($query);
		$response = array();
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$response[] = $row; 
		}
		$this->set("response",$response);
		//print json_encode($response);
		
	}

	// Loki: Show the full history details 
	function ShowInvoiceHistoryById()
	{
		$this->layout="list";
		$historyId = $this->params['url']['historyId'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "SELECT ih. * , inri.item_id, inri.quantity, ias.purchase_price, ins.status AS status_name, inl.name AS item_name, ppm.name as payment_method_name, CONCAT(return_user.first_name, ' ',return_user.last_name) as return_user,
			CONCAT(paid_by.first_name, ' ',paid_by.last_name) as paid_user
			FROM ace_iv_invoice_history ih
			LEFT JOIN ace_iv_invoice_refund_items inri ON inri.invoice_history_id = ih.id
			LEFT JOIN ace_iv_assets ias ON ( ias.movement_id = inri.Invoice_id
			AND ias.item_id = inri.item_id ) 
			INNER JOIN ace_iv_invoice_status ins ON ins.id = ih.status_id
			LEFT JOIN iv_items_labeled2 inl ON inl.id = inri.item_id
			LEFT JOIN ace_rp_purchase_payment_method ppm ON ppm.id = ih.payment_method
			LEFT JOIN ace_rp_users return_user ON return_user.id =ih.returned_by
			LEFT JOIN ace_rp_users paid_by ON paid_by.id = ih.paid_by 
			WHERE ih.id = ".$historyId." GROUP BY ias.item_id";
			$response = array();
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				$response[] = $row; 
			}
		$this->set("response",$response);
	}

	
	function addTransferItem()
    {
        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
        $techId = !empty($_POST['techId']) ? $_POST['techId'] : 0;
        $orderItems = $_POST['data']['Order']['BookingItem'];
        $items = array();
 
        foreach ($orderItems as $key => $value) {
             if($value['order_status'] == 1){ 
	                $items[]       = $value['item_id'];
             }
         }
          $response  = array("res" => "1", "poData" => $_POST, "itemArray" => $items );
          echo json_encode($response);
         exit();
     }

    function createPurchaseInvoice()
    {
    	// error_reporting(E_ALL);
        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
        $orderInvoiceExist = $_POST['orderInvoiceExist'];
        $supplierName = $_POST['supplier_name'];
        $supplierId = $_POST['data']['Order']['app_ordered_supplier_id'];
        $invoiceId = $_POST['data']['Order']['invoice_id'];
        $techId = !empty($_POST['techId']) ? $_POST['techId'] : 0;
        $orderId = $_POST['data']['Order']['id'];
        $orderNum = $_POST['poNumber'];
        $imageName = $_POST['orderInvoiceImage'];
        $orderItems = $_POST['data']['Order']['BookingItem'];
        $items = array();
        $moveDate = date("Y-m-d");
        $doc_type = 1;
        $i = 0;
        if($techId == 0){
        	$locationData = 'warehouse:'.$techId.'';
        } else {
        	$locationData = 'tech:'.$techId.'';
        }
        
        foreach ($orderItems as $key => $value) {
             if($value['order_status'] == 1){
                	$items[$i]['class']           = $value['class'];
                	$items[$i]['name']            = stripcslashes($value['name']);
	                $items[$i]['sku']             = $value['part_number'];
	                $items[$i]['qty']        	  = $value['quantity'];
	                $items[$i]['price_purchase']  = $value['price_purchase'];
	                $items[$i]['selling_price']   = $value['price'];
	                $items[$i]['supplier_id']     = $supplierId;
	                $items[$i]['item_id']         = $value['item_id'];
	                $items[$i]['item_location']   = $locationData;
	                $items[$i]['catId']        	  = $value['item_category_id'];
	                $items[$i]['subCatId']        = $value['sub_category_id'];
	                $items[$i]['item_markup_percent']        = $value['item_markup_percent'];
	                $items[$i]['item_tech_percent']        = $value['item_tech_percent'];
	                $i++;
                //$db->_execute("UPDATE ace_rp_order_items SET order_status = 1 WHERE id =".$value['order_item_id']."");
             }
         }
         
          $response  = array("res" => "1", "poData" => $_POST, "itemArray" => $items);
          echo json_encode($response);
         exit();
     }

     /*Loki: add new purchase item for booking.*/
     function addNewPurchaseItem()
     {
     	$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
        $orderInvoiceExist = $_POST['orderInvoiceExist'];
        $supplierName = $_POST['supplier_name'];
        $supplierId = $_POST['data']['Order']['app_ordered_supplier_id'];
        $invoiceId = $_POST['data']['Order']['invoice_id'];
        $techId = !empty($_POST['techId']) ? $_POST['techId'] : 0;
        $orderId = $_POST['data']['Order']['id'];
        $orderNum = $_POST['poNumber'];
        $imageName = $_POST['orderInvoiceImage'];
        $orderItems = $_POST['data']['Order']['BookingItem'];
        $items = array();
        $moveDate = date("Y-m-d");
        $doc_type = 1;
        $i = 0;
        if($techId == 0){
        	$locationData = 'warehouse:'.$techId.'';
        } else {
        	$locationData = 'tech:'.$techId.'';
        }

         $response  = array("res" => "1", "poData" => $_POST, "itemArray" => $items );
          echo json_encode($response);
         exit();
     }
    /*Loki: Get tech inventory data*/
    function techInventory(){
    	$this->layout = "blank";
    	$techId = $this->Common->getLoggedUserID();
        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
        $query = "SELECT ti.quantity, lb.*,(select tin.quantity from ace_rp_tech_inventory_item tin where tech_id = 0 and item_id = lb.id) as warehouse_count FROM ace_rp_tech_inventory_item ti INNER JOIN iv_items_labeled2 lb on ti.item_id = lb.id  where ti.tech_id = ". $techId."";
        $result = $db->_execute($query);
        $items = array();

        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $items[] = $row;
        }
        $this->set("items", $items);
        exit();
    }

  /*  function techItemRequest (){
    	$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
    	$itemPart = json_decode(stripslashes($_POST['itemPartNum']), true);
    	$itemName = json_decode(stripslashes($_POST['itemName']), true);
    	$itemQuantity = json_decode(stripslashes($_POST['itemQuantity']), true);
    	$doc_date = date("Y-m-d", strtotime($_POST['date']));
    	$ref_number = $_POST['orderRef'];
    	$status = 1;
    	$notes = $_POST['notes'];
    	$image1 = $_FILES['fileval1'];
    	$image2 = $_FILES['fileval2'];
    	$tech_id = $this->Common->getLoggedUserID();
    	$fileName1 = '';
    	$fileName2 = '';
    	if(!empty($image1)){
                $fileName1 = time()."_".$image1['name'];
                $fileTmpName1 = $image1['tmp_name'];

                if($image1['error'] == 0)
                {
                    $move = move_uploaded_file($fileTmpName1 ,ROOT."/app/webroot/request-item-image/".$fileName1);
                } 
    	}
    	if(!empty($image2)){

                $fileName2 = time()."_".$image2['name'];
                $fileTmpName2 = $image2['tmp_name'];

                if($image2['error'] == 0)
                {
                    $move = move_uploaded_file($fileTmpName2 ,ROOT."/app/webroot/request-item-image/".$fileName2);
                }
    	}
    	$query = "INSERT INTO ace_rp_inventory_requests	(tech_id, notes, docdate, flagactive, ref_number, status, item_image1, item_image2) VALUES (".$tech_id.", '".$notes."','".$doc_date."', 1, '".$ref_number."', ".$status.", '".$fileName1."', '".$fileName2."')";
		$db->_execute($query);
		$id = $db->lastInsertId();
		// $sql = "INSERT INTO ace_rp_messages
		// 				(txt, state, from_user, from_date, 
		// 				 to_user, to_date, to_time)
		//  VALUES ('<a target=\"main_view\" href=\"/acebeta/index.php/inventories/editRequest?id=$id&rurl=requests%3F\">Parts requested by tech</a>: $unit_make $unit_model ($unit_serial) $notes', 0, ".$this->Common->getLoggedUserID().", current_date(),
		// 				 57499, current_date(), '00:00')";
		// $db->_execute($sql);
		$sql = "INSERT INTO ace_rp_messages
						(txt, state, from_user, from_date, 
						 to_user, to_date, to_time)
		 VALUES ('Part request generated by technician.', 0, ".$tech_id.", current_date(),
						 44851, current_date(), '00:00')";
		$db->_execute($sql);


		foreach ($itemName as $key => $value)
		{
			if(!empty($value['value'])){
				$query = "INSERT INTO ace_rp_inventory_request_items
									(doc_id, qty, name, part_number) 
									VALUES ($id, ".$itemQuantity[$key]['value'].",
									'".$value['value']."', ".$itemPart[$key]['value'].")";
				$result = $db->_execute($query);
			}
		}
		if($result){
			$response  = array("res" => "OK");
            echo json_encode($response);
		}
    	exit();
    }
*/
    //Loki: Add tech default inventory items
    function saveTechDefaultInventory()
    {	
    	//$now = date("Y-m-d");
		$techArray = json_decode(stripslashes($_POST['toTech']), true);
		$itemArray = json_decode(stripslashes($_POST['items']), true);		
		$fromTech = $_POST['fromTech'];		
		$tdate = date("Y-m-d", strtotime($_POST['tdate']));		

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		foreach ($techArray as $key => $value) 
		{
			foreach ($itemArray as $itemKey => $itemValue) {
					/*Loki: Check item exist in the inventory and update the quantity.*/
					$checkExisting = "SELECT * FROM ace_rp_tech_inventory_item where tech_id = ".$value." AND item_id =".$itemValue['item_id']."";
					$executeRes = $db->_execute($checkExisting);
					$row = mysql_fetch_array($executeRes, MYSQL_ASSOC);
					if(!empty($row)) {
						$newQuantity = $itemValue['item_quantity'] + $row['quantity'];
						$res = $db->_execute("UPDATE ace_rp_tech_inventory_item set quantity = ".$newQuantity.",show_default = 1 where id = ".$row['id']."");
					} else{
						$query = "INSERT INTO ace_rp_tech_inventory_item (tech_id, item_id, quantity, updated_date,show_default) VALUES (".$value.",".$itemValue['item_id'].", ".$itemValue['item_quantity'].", '".$tdate."',1)";
						$res = $db->_execute($query);
					}
					
					$res = $db->_execute("UPDATE ace_rp_tech_inventory_item set quantity = quantity - ".$itemValue['item_quantity']." where item_id = ".$itemValue['item_id']." AND tech_id=".$fromTech);

					$history = $db->_execute("INSERT INTO ace_iv_invoice_item_history (quantity,item_id,purchase_price,received_by,item_date,assigned_by,status) VALUES (".$itemValue['item_quantity'].",".$itemValue['item_id'].",".$itemValue['purchase_price'].",".$value.",'".$tdate."',".$fromTech.",3)");
				}	
		}
		if($history){
			$response  = array("res" => "OK");
            echo json_encode($response);
		}
		exit();
    }
    /*Loki: Move items to tech inventory*/
      function moveItemToTechInventory()
    {	
    	error_reporting(E_ALL);
    	//$now = date("Y-m-d");
		$techArray = json_decode(stripslashes($_POST['toTech']), true);
		$itemArray = json_decode(stripslashes($_POST['items']), true);		
		$fromTech = $_POST['fromTech'];		
		$fromBooking = $_POST['fromBooking'];		
		$tdate = date("Y-m-d", strtotime($_POST['tdate']));		

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		foreach ($techArray as $key => $value) 
		{
			foreach ($itemArray as $itemKey => $itemValue) {
					/*Loki: Check item exist in the inventory and update the quantity.*/
					$checkExisting = "SELECT * FROM ace_rp_tech_inventory_item where tech_id = ".$value." AND item_id =".$itemValue['item_id']."";
					$executeRes = $db->_execute($checkExisting);
					$row = mysql_fetch_array($executeRes, MYSQL_ASSOC);
					if(!empty($row)) {
						$newQuantity = $itemValue['item_quantity'] + $row['quantity'];
						$res = $db->_execute("UPDATE ace_rp_tech_inventory_item set quantity = ".$newQuantity." where id = ".$row['id']."");
					} else{
						$query = "INSERT INTO ace_rp_tech_inventory_item (tech_id, item_id, quantity, updated_date) VALUES (".$value.",".$itemValue['item_id'].", ".$itemValue['item_quantity'].", '".$tdate."')";
						$res = $db->_execute($query);
					}
					
					$res = $db->_execute("UPDATE ace_rp_tech_inventory_item set quantity = quantity - ".$itemValue['item_quantity']." where item_id = ".$itemValue['item_id']." AND tech_id=".$fromTech);

					$history = $db->_execute("INSERT INTO ace_iv_invoice_item_history (quantity,item_id,purchase_price,received_by,item_date,assigned_by,status) VALUES (".$itemValue['item_quantity'].",".$itemValue['item_id'].",".$itemValue['purchase_price'].",".$value.",'".$tdate."',".$fromTech.",3)");
				}	
		}
		if($history){
			if($fromBooking == 1)
			{
				$response  = array("res" => "KO");
	            echo json_encode($response);
			}else {
				$response  = array("res" => "OK");
	            echo json_encode($response);
			}
		}
		exit();
    }
    // Loki: Delete items using purchase invoice page.
    function deleteItems(){
    	$itemIds = $_POST['Id'];
    	// print_r($itemIds); die;
    	$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$query 		= "UPDATE  ace_iv_items set active = 0 where id IN (".$itemIds.")";
		$result 	= $db->_execute($query);
		if($result)
		{
			$query1 = "UPDATE  iv_items_labeled2 set active = 0 where id IN (".$itemIds.")";
			$result1 = $db->_execute($query1);
		}
		if($result1)
		{
			$response  = array("res" => "1");
 			echo json_encode($response);
 			exit;
		}
    	exit();
    }

     //Loki: remove purhcse invoice image
     function removeInvoiceImage()
    {
        $id = $_POST['oid'];
        $imgPath = $_POST['imgPath'];
        $rootPath = getcwd();
        unlink($rootPath.'/app/webroot/purchase-invoice-images/'.$imgPath);
        $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
        $query = "UPDATE ace_iv_invoice set invoice_image = '' where invoice_id =".$id;
        $res = $db->_execute($query);
        if ($res) {
            $response  = array("res" => "OK");
            echo json_encode($response);
            exit();
        }
        exit();     
    }

    /*Loki: get all the items of technician.*/
    function getTechItems()
    {
    	$loggedUser = $this->Common->getLoggedUserID();
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$this->layout="list";
		$now = date("d M Y");
		$query = "SELECT li.sku, li.name, li.model,li.brand,li.supplier_price,li.selling_price,ti.item_id, ti.quantity FROM ace_rp_tech_inventory_item ti
		JOIN iv_items_labeled2 li ON ti.item_id = li.id
		WHERE tech_id=".$loggedUser;
		$result  = $db->_execute($query);
		$items = array();
		while($row = mysql_fetch_array($result)) {
			$row['tech_data'] = $this->getInventoryTechQty($row['item_id']); 
			$items[] = $row;
		}
		$techArr = $this->Lists->inventoryTech();
        $this->set('inventoryTechnician', $techArr);    
		$this->set('items', $items);		
	
    }

    function deleteInvoice()
	{
		$invoiceId = $_POST['invoiceId'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$deleteInvoice = $db->_execute("DELETE FROM ace_iv_invoice WHERE invoice_id=".$invoiceId);
		$deleteMovement = $db->_execute("DELETE FROM ace_iv_movements WHERE id=".$invoiceId);
		$deleteItems = $db->_execute("DELETE FROM ace_iv_invoice_item_history  WHERE invoice_id=".$invoiceId);
		if ($deleteItems) {
            $response  = array("res" => "1");
            echo json_encode($response);
            exit();
        }		
		exit();
	}

	/*Loki: Delete transaction history*/
	function deleteItemTransaction()
	{
		$transactionId = $_GET['transactionId'];
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$deleteItems = $db->_execute("DELETE FROM ace_iv_invoice_item_history WHERE id=".$transactionId);
		
		if ($deleteItems) {
            $response  = array("res" => "1");
            echo json_encode($response);
            exit();
        }		
		exit();
	}

	/*Loki: Transfer item default quantity */
	function transferItemQty()
	{
		$now = date('Y-m-d');
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$bookItems = json_decode(stripslashes($_POST['items']), true);
		foreach ($bookItems as $key => $value) {
			$checkExisting = "SELECT * FROM ace_rp_tech_inventory_item where tech_id = 231433 AND item_id =".$value['item_id']."";
			$executeRes = $db->_execute($checkExisting);
			$row = mysql_fetch_array($executeRes, MYSQL_ASSOC);
			if(!empty($row)) {
				$newQuantity = $value['default_qty'] + $row['quantity'];
				$res = $db->_execute("UPDATE ace_rp_tech_inventory_item set quantity = ".$newQuantity." where id = ".$row['id']."");
			} else{
				$query = "INSERT INTO ace_rp_tech_inventory_item (tech_id, item_id, quantity, updated_date) VALUES (231433,".$value['item_id'].", ".$value['default_qty'].", '".$now."')";
				$res = $db->_execute($query);
			}
		}
		if($res)
		{
			 $response  = array("res" => "1");
             echo json_encode($response);
            exit();
		}
	}

	/*Loki: Transfer itemss to packes*/
	function transferPackage()
	{
		$now = date('Y-m-d');
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$bookItems = json_decode(stripslashes($_POST['items']), true);
		$packages = json_decode(stripslashes($_POST['packages']), true);
		
		foreach ($packages as $key => $value) 
		{
			foreach ($bookItems as $itemKey => $itemValue) {
				$db->_execute("INSERT INTO ace_iv_items (sku,name, description1, description2,efficiency, model,  iv_category_id, iv_brand_id, iv_supplier_id, supplier_price, selling_price, regular_price, active, iv_sub_category_id,ref_item_id,default_quantity) VALUES ('".$itemValue['sku']."', '".mysql_real_escape_string($itemValue['name'])."', '','', '','',39,'','".$itemValue['supplier_id']."', '".$itemValue['supplier_price']."','".$itemValue['selling_price']."','','1', ".$value.",".$itemValue['item_id'].",".$itemValue['default_qty'].")"); 
             		$lastinsertID = $db->lastInsertId();
             		 $item_label2 = "INSERT INTO iv_items_labeled2 (sku,id,name, description1, description2,efficiency, model, brand, category, supplier,  category_id, brand_id, supplier_id, supplier_price, selling_price, regular_price, active, sub_category_id,ref_item_id,default_quantity) VALUES ('".$itemValue['sku']."',".$lastinsertID.", '".mysql_real_escape_string($itemValue['name'])."', '','', '','','' ,'Package' ,'' ,39,'','".$itemValue['supplier_id']."', '".$itemValue['supplier_price']."','".$itemValue['selling_price']."','','1', ".$value.",".$itemValue['item_id'].",".$itemValue['default_qty'].")";
               		$res = $db->_execute($item_label2);   					
			}	
		}
		if($res)
		{
			 $response  = array("res" => "1");
             echo json_encode($response);
            exit();
		}
	}

	function getStockQty()
	{
		$techId = explode(':', $_GET['techId']);
		$orgTechId = $techId[1];
		$itemId = $_GET['itemId'];
		$techItem = $this->TechInventoryItem->query("SELECT quantity FROM ace_rp_tech_inventory_item Where item_id = ".$itemId." and tech_id =".$orgTechId);
            
        $item['count'] = $techItem[0]['ace_rp_tech_inventory_item']['quantity'];

        $response  = array("res" => $item['count']);
		echo json_encode($response);
		exit;
	}
	
}
?>
