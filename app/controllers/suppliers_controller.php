<?

error_reporting(E_PARSE  ^ E_ERROR );

class SuppliersController extends AppController

{

	//To avoid possible PHP4 problemfss

	var $name = "SuppliersController";



	var $uses = array('User', 'Supplier');



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



	function saveItem()

	{

		$cur_id = $this->data['Supplier']['id'];

		$this->Supplier->id = $cur_id;

		$this->Supplier->save($this->data);

		

		if (!$cur_id) $cur_id=$this->Supplier->getLastInsertId();

		

		//Forward user where they need to be - if this is a single action per view

		$this->redirect('/suppliers/index');

	}



}

?>

