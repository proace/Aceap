<? ob_start();

error_reporting(E_PARSE  ^ E_ERROR );

class SuppliersController extends AppController

{

	//To avoid possible PHP4 problemfss

	var $name = "SuppliersController";



	var $uses = array('User', 'Supplier','MainSupplier');



	var $helpers = array('Time','Javascript','Common');

	var $components = array('HtmlAssist','RequestHandler','Common','Jpgraph', 'Lists');

	var $itemsToShow = 20;

	var $pagesToDisplay = 10;

	

	function index_old()

	{

		$this->layout="list";

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		

		$sort = $_GET['sort'];

		$order = $_GET['order'];

		$search = $_GET['search'];

		if (!$order) $order = 'id asc';

		$search_str = '';

		if ($search) $search_str = "and (brand_part like '%{$search}%' or

        brand_furnace like '%{$search}%' or

        brand_boiler like '%{$search}%' or

        brand_hwt like '%{$search}%' or

        brand_tankless like '%{$search}%' or

        brand_heatpump like '%{$search}%')";     

		

		$query = "select * from ace_rp_suppliers i where i.flagactive=1 $search_str order by ".$order.' '.$sort;

		

		$items = array();

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result, MYSQL_ASSOC))

		{

			foreach ($row as $k => $v)

				$items[$row['id']][$k] = $v;

		}

		

		$this->set('items', $items);

		$this->set('search', $search);

		$this->set("ismobile", $this->Session->read("ismobile"));

		//$this->set('jobcategories', $this->Lists->ListTable('ace_rp_order_type_categories'));

	}

	
	function index()
	{
		$this->layout="list";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		$search = $_GET['search'];
		if (!$order) $order = 'id asc';
		$search_str = '';
		if ($search) $search_str = "where (equipment like '%{$search}%'";     
		
		$query = "select * from ace_rp_main_suppliers $search_str order by ".$order.' '.$sort;
		
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
				$items[$row['id']][$k] = $v;
		}
		
		$this->set('items', $items);
		$this->set('search', $search);
		$this->set("ismobile", $this->Session->read("ismobile"));
		//$this->set('jobcategories', $this->Lists->ListTable('ace_rp_order_type_categories'));
	}
	

	function editItem()

	{

		$item_id = $_GET['item_id'];

		$this->Supplier->id = $item_id;

		$this->data = $this->Supplier->read();

		

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		

//		$this->set('questions', $questions);

//		$this->set('jobtypes', $this->OrderType->findAll());

//		$this->set('jobcategories', $this->Lists->ListTable('ace_rp_order_type_categories'));

	}

	//Loki: edit the main supplier details.
	function editMainSupplier()
	{
		$this->layout="list";
		$supId = $_GET['sup_id'];

		$this->MainSupplier->id = $supId;

		$this->data = $this->MainSupplier->read();

		// $this->data = $details['MainSupplier'];
	}

	function editSupplierBranch()
	{
		$this->layout="list";
		$branchId = $_GET['branch_id'];

		$this->Supplier->id = $branchId;

		$this->data = $this->Supplier->read();		
	}


	function saveSupplierBranch()

	{

		$cur_id = $this->data['Supplier']['id'];

		$this->Supplier->id = $cur_id;

		$this->Supplier->save($this->data);

		

		if (!$cur_id) $cur_id=$this->Supplier->getLastInsertId();

		

		//Forward user where they need to be - if this is a single action per view

		$this->redirect('/suppliers/index');

	}
	//Loki: Get all supplier branches.
	function getSupplierBranch()
	{
		$supplierId = $_GET['sup_id'];
		$branches = $this->Supplier->findAll(array('condition' => array('Supplier.main_supplier_id' =>  $supplierId)));
		$sRes = '<table class="sup_branch">';
		
		$sRes .= '<tr>
		<th>Id</th>
		<th>Name</th>
		<th>Phone</th>
		<th>Address</th>
		<th>City</th>
		<th>Notes</th>
		<th width="25" style="color: black"><i class="fa fa-trash" aria-hidden="true"></i>
		</tr>';
		foreach ($branches as $key => $value) {
			$sRes .= '<tr onclick="ClickRow(this)" style="cursor:pointer;">';
			$sRes .= '<td><a href="'.BASE_URL.'/suppliers/editSupplierBranch?branch_id='.$value['Supplier']['id'].'">'.$value['Supplier']['id'].'</a></td>';
			$sRes .= '<td>'.$value['Supplier']['name'].'</td>';
			$sRes .= '<td> <a href="#" id="call_phone" call_cell_phone="'.$value["Supplier"]["phone"].'">'.$value['Supplier']['phone'].'</a></td>';
			$sRes .= '<td>'.$value['Supplier']['address'].'</td>';
			$sRes .= '<td>'.$value['Supplier']['city'].'</td>';
			$sRes .= '<td>'.$value['Supplier']['notes'].'</td>';
			$sRes .= '<td><a href="'.BASE_URL.'/suppliers/deleteSupplierBranch?branch_id='.$value['Supplier']['id'].'"><i class="fa fa-trash" aria-hidden="true"></a></td>';
			$sRes .= '</tr>';
		}
		$sRes .= '</table>';

	   echo $sRes;
	    exit;
	}

	/*Loki: save main supplier data.*/
	function saveMainSupplier()
	{
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		// $this->Common->printData($_POST);
		$supplierDetails = $_POST['data']['MainSupplier'];
		if(!empty($supplierDetails['id']))
		{
			$this->MainSupplier->id = $supplierDetails['id'];
			$this->MainSupplier->save($supplierDetails);
			$this->redirect('/suppliers/index');
			if(!empty($_POST['name'][1]))
			{
				foreach ($_POST['name'] as $key => $value) {
					if(!empty($value))
					{
						$db->_execute("INSERT INTO ace_rp_suppliers (name,phone, address, city, notes,main_supplier_id) VALUES  ('".$value."', '".$_POST['phone'][$key]."', '".$_POST['address'][$key]."', '".$_POST['city'][$key]."', '".$_POST['notes'][$key]."', ".$supplierDetails['id'].")");
					}
				}
			}


		} else {
			$this->MainSupplier->save($supplierDetails);
			if ($this->MainSupplier->id)
            {
                $supplierId = $this->MainSupplier->id;
            }   
            else {
              $supplierId = $this->MainSupplier->getLastInsertId();
            }
            if(!empty($_POST['name'][1]))
			{
				foreach ($_POST['name'] as $key => $value) {
					if(!empty($value))
					{
						$db->_execute("INSERT INTO ace_rp_suppliers (name,phone, address, city, notes,main_supplier_id) VALUES  ('".$value."', '".$_POST['phone'][$key]."', '".$_POST['address'][$key]."', '".$_POST['city'][$key]."', '".$_POST['notes'][$key]."', ".$supplierId.")");
					}
				}
			}
            $this->redirect('/suppliers/index');
		}

		exit();

	}

	/*Loki: delete main suplier*/

	function deleteMainSupplier()
	{
		$supplierId = $_GET['sup_id'];
		$this->MainSupplier->id = $supplierId;
		$this->MainSupplier->delete();
		$this->redirect('/suppliers/index');
	}


	/*Loki: delete main suplier*/

	function deleteSupplierBranch()
	{
		$supplierId = $_GET['branch_id'];
		$this->Supplier->id = $supplierId;
		$this->Supplier->delete();
		$this->redirect('/suppliers/index');
	}
	
}

?>

