<?
error_reporting(E_PARSE  ^ E_ERROR );
class RoutesController extends AppController
{
	//To avoid possible PHP4 problemfss
	var $name = "RoutesController";

	var $uses = array('User', 'InventoryLocation');

	var $helpers = array('Time','Javascript','Common');
	var $components = array('HtmlAssist','RequestHandler','Common', 'Lists');
	var $itemsToShow = 20;
	var $pagesToDisplay = 10;
	
	function index()
	{
		$this->layout="list";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		if (!$order) $order = 'id asc';
		
		$query =  "select i.id, i.name, i.color, i.truck_number, i.flagactive,i.order_id,i.route_number,t.name route_type
								 from ace_rp_inventory_locations i
								 left outer join ace_rp_users t1 on i.tech1_id=t1.id
								 left outer join ace_rp_users t2 on i.tech2_id=t2.id
								 left outer join ace_rp_route_types t on i.route_type=t.id
								where i.type=2
								order by ".$order.' '.$sort;
		
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $items[$row['id']][$k] = $v;
		}
		
		$this->set('items', $items);
		$this->set('allTypes', $this->Lists->ListTable('ace_rp_route_types'));
		
		//$this->set('allTechnicians', $this->Lists->Technicians());
	}

	function editItem()
	{
		$item_id = $_GET['item_id'];
		$this->InventoryLocation->id = $item_id;
		$this->data = $this->InventoryLocation->read();
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$this->set('allTechnicians', $this->Lists->Technicians());
		$this->set('allTypes', $this->Lists->ListTable('ace_rp_route_types'));
		$this->set('trackemGPS', $this->Lists->TrackemGPS());
	}

	function saveItem()
	{
		$this->InventoryLocation->id = $this->data['InventoryLocation']['id'];
		$this->data['InventoryLocation']['color'] = '#' . str_replace('#','', $this->data['InventoryLocation']['color']);
		$this->InventoryLocation->save($this->data);
		if ($this->InventoryLocation->id)
			$cur_id = $this->InventoryLocation->id;
		else
			$cur_id = $this->InventoryLocation->getLastInsertId();

		//Forward user where they need to be - if this is a single action per view
		$this->redirect('/routes/index');
	}
	
	function delete($id = null)
	{
		//$this-InventoryLocation->id = $id;
		$item_id = $_GET['item_id'];
		$this->InventoryLocation->del($item_id);
		$this->redirect('/routes/index');
	}
	
	function deactivate($id = null)
	{
		//$this-InventoryLocation->id = $id;
		$item_id = $_GET['item_id'];
		$this->InventoryLocation->id = $item_id;
		$this->data = $this->InventoryLocation->read();
		$this->data[InventoryLocation][flagactive] = 0;
		$this->InventoryLocation->save($this->data);
		$this->redirect('/routes/index');
	}
	
	function activate($id = null)
	{
		//$this-InventoryLocation->id = $id;
		$item_id = $_GET['item_id'];
		$this->InventoryLocation->id = $item_id;
		$this->data = $this->InventoryLocation->read();
		$this->data[InventoryLocation][flagactive] = 1;
		$this->InventoryLocation->save($this->data);
		$this->redirect('/routes/index');
	}
}
?>
