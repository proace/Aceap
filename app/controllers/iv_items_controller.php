<?php

class IvItemsController extends AppController

{

	var $name = 'IvItems';

	var $components = array('HtmlAssist', 'Common', 'Lists');

	var $uses = array('IvCategory','IvItem','CallResult');

	

	function index() {

		$this->layout = 'blank';

		$db =& ConnectionManager::getDataSource('default');	
		
		$this->set('mode', $_GET['mode']);

		$query = "
			SELECT * 
			FROM ace_iv_categories
			WHERE active = 1
		";

		$result = $db->_execute($query);		

		while($row = mysql_fetch_array($result))
		{
			$categories[] = $row;
		}
		$this->set('categories', $categories);

		//$this->set('categories', $this->IvCategory->findAll());

		$this->set('items', $this->IvItem->findAll());

		$this->set('message', $this->Session->read("message"));

		$this->Session->write("message", "");

		$this->set('inventoryAccess', $this->Session->read("Inventory"));

	}

	

	function branch() { 

		$this->layout = 'blank';

		$db =& ConnectionManager::getDataSource('default');		

		

		$category_id = $_GET['category_id'];

		//the default if the tree is not set

		$hierarchy[0]['id'] = 'category_id';

		$hierarchy[0]['name'] = 'category';

		$hierarchy[0]['alias'] = 'Category';

		

		$hierarchy[1]['id'] = 'active';

		$hierarchy[1]['name'] = 'active_name';

		$hierarchy[1]['alias'] = 'Active';

		

		$query = "

			SELECT * 

			FROM ace_iv_category_settings

			WHERE iv_category_id = $category_id

			AND `order` > -1

			ORDER BY `order` ASC

		";

		

		$result = $db->_execute($query);

		$i = 0;

		while($row = mysql_fetch_array($result))

		{					

			$hierarchy[$i]['id'] = trim($row['field'])==''?$row['name']:$row['field'];

			$hierarchy[$i]['name'] = $row['name'];

			$hierarchy[$i]['alias'] = trim($row['alias'])==''?$row['name']:$row['alias'];

			$i++;

		}		

		

		$criteria = $_GET;

		

		//hierarchy will be set by the user on a different table. 

		//if it is not set, the default will be category->brand->supplier->description1->item_list

		//name will be filled up in another table

		

		if($hierarchy[$criteria['level'] + 1]['id'] == '') {

			$this->items();

			exit;	

		}

		

		++$criteria['level']; //add 1 level

		

		$query = "SELECT ".$hierarchy[$criteria['level']]['name']." AS field";

		if($hierarchy[$criteria['level']]['id'] != '') {
			$query .= " , ".$hierarchy[$criteria['level']]['id']." AS id ";
		}

		

		$query .= "			

			FROM iv_items_labeled2

			WHERE 1 = 1			

		";

		$mode = $_GET['mode'];

		//the seed is used to prevent caching

		foreach($criteria as $field => $get) {

			if($field != 'url' && $field != 'level' && $field != 'seed' && $field != 'mode') {

				if(trim($get) != '') $query .= " AND $field LIKE '$get'";

			}

		}

		

		 $query .= " GROUP BY ".$hierarchy[$criteria['level']]['name'];

		

		unset($criteria['url']);

		unset($criteria['_']);

		

		$result = $db->_execute($query);

		$id = 0;

		while($row = mysql_fetch_array($result))

		{					

			$items[$id]['field'] = $row['field'];

			$row_criteria = $criteria;

			$row_criteria[$hierarchy[$criteria['level']]['id']] = $row['id'];

			$items[$id]['criteria'] = json_encode($row_criteria);

			$id++;

		}

		

		if($hierarchy[$criteria['level'] + 1] != '') $this->set('path', 'branch');

		else $this->set('path', 'item');

		
		

		$this->set('heading', $hierarchy[$criteria['level']]['alias']);

		$this->set('criteria', json_encode($criteria));

		$this->set('count', count($items));

		$this->set('items', $items);

		$this->set('query', $query);

		$this->set('mode', $mode);

	}

	

	function items() {

		$this->layout = 'blank';

		$db =& ConnectionManager::getDataSource('default');		

		

		$category_id = $_GET['category_id'];

		

		//the default if the tree is not set

		$hierarchy[0]['id'] = 'category_id';

		$hierarchy[0]['name'] = 'category';

		$hierarchy[0]['alias'] = 'Category';

		

		$hierarchy[1]['id'] = 'active';

		$hierarchy[1]['name'] = 'active';

		$hierarchy[1]['alias'] = 'Active';

		

		if(isset($category_id)) {		

			$query = "

				SELECT * 

				FROM ace_iv_category_settings

				WHERE iv_category_id = $category_id

				ORDER BY `order`

			";		

		} else {

			$query = "

				SELECT * 

				FROM ace_iv_category_settings

				ORDER BY `order`

			";	

		}

		

		$result = $db->_execute($query);		

		while($row = mysql_fetch_array($result))

		{

			$aliases[$row['name']]['alias'] = $row['alias'];

		}		

		

		$query = "

			SELECT *

			FROM iv_items_labeled2

			WHERE 1 = 1

		";

		

		$mode = $_GET['mode'];

		$criteria = $_GET;

		

		foreach($criteria as $field => $get) {

			if($field != 'url' && $field != 'level' && $field != '_' && $field != 'seed' && $field != 'mode') {

				if(trim($get) != '') $query .= " AND $field LIKE '$get'";

			}

		}

		

		if(!isset($_GET['active'])) $query .= " AND active = 1";

		

		unset($criteria['url']);

		unset($criteria['_']);

		
		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result))

		{		

			$items[$row['id']]['id'] = $row['id'];

			$items[$row['id']]['active'] = $row['active_name'];

			$items[$row['id']]['name'] = $row['name'];
			
			$items[$row['id']]['sku'] = $row['sku'];

			$items[$row['id']]['description1'] = $row['description1'];

			$items[$row['id']]['description2'] = $row['description2'];

			$items[$row['id']]['efficiency'] = $row['efficiency'];

			$items[$row['id']]['model'] = $row['model'];

			$items[$row['id']]['brand'] = $row['brand'];

			$items[$row['id']]['brand_id'] = $row['brand_id'];

			$items[$row['id']]['supplier'] = $row['supplier'];

			$items[$row['id']]['supplier_id'] = $row['supplier_id'];

			$items[$row['id']]['supplier_price'] = number_format($row['supplier_price'], 2, '.', '');

			$items[$row['id']]['selling_price'] = number_format($row['selling_price'], 2, '.', '');

			$items[$row['id']]['regular_price'] = number_format($row['regular_price'], 2, '.', '');

		}
		

		$this->set('criteria', json_encode($criteria));

		$this->set('items', $items);

		$this->set('count', count($items));

		$this->set('aliases', $aliases);

		$this->set('inventoryAccess', $this->Session->read("Inventory"));

		$this->set('mode', $mode);

		$this->set('query', $query);

	}

	

	function edit($id) {

		$this->layout = 'blank';	
		
		if (empty($this->data['IvItem'])) {    
			$this->IvItem->id = $id;    

			$this->data = $this->IvItem->read();	

			$this->set('categories', $this->Lists->IvCategories());

			$this->set('category_id', isset($_GET['category_id'])?$_GET['category_id']:'');

			$this->set('brands', $this->Lists->IvBrands());

			$this->set('brand_id', isset($_GET['brand_id'])?$_GET['brand_id']:'');

			$this->set('suppliers', $this->Lists->IvSuppliers());

			$this->set('supplier_id', isset($_GET['supplier_id'])?$_GET['supplier_id']:'');

			$this->set('efficiency', isset($_GET['efficiency'])?$_GET['efficiency']:'');

			$this->set('description1', isset($_GET['description1'])?$_GET['description1']:'');

			$this->set('description2', isset($_GET['description2'])?$_GET['description2']:'');

			$this->set('yesOrNo', $this->Lists->YesOrNo());

		}
		
	}

	

	function save() {
		if($this->data['IvItem']['active'] != 1) $this->data['IvItem']['active'] = 0;

		if($this->IvItem->save($this->data['IvItem'])) {
			
			$db =& ConnectionManager::getDataSource('default');	
			$brandName = $_POST['brandName'];
			$supplierName = $_POST['supplierName'];
			$categoryName = $_POST['categoryName'];
			if(!empty($this->data['IvItem']['id']))
			{
				
				$item_label2 = "UPDATE iv_items_labeled2 set sku='".$this->data['IvItem']['sku']."',name='".$this->data['IvItem']['name']."',regular_price='".$this->data['IvItem']['regular_price']."',selling_price='".$this->data['IvItem']['selling_price']."',supplier_price='".$this->data['IvItem']['supplier_price']."',description1='".$this->data['IvItem']['description1']."',description2='".$this->data['IvItem']['description2']."',efficiency='".$this->data['IvItem']['efficiency']."',model='".$this->data['IvItem']['model']."',category_id='".$this->data['IvItem']['iv_category_id']."',brand_id='".$this->data['IvItem']['iv_brand_id']."',supplier_id='".$this->data['IvItem']['iv_supplier_id']."',active='".$this->data['IvItem']['active']."', brand='".$brandName."', category='".$categoryName."', supplier='".$supplierName."' WHERE id = '".$this->data['IvItem']['id']."'";
			} else {
				$lastinsertID = $this->IvItem->getLastInsertId();
				
				$item_label2 = "INSERT INTO iv_items_labeled2 (sku,id,name, description1, description2,efficiency, model, brand, category, supplier,  category_id, brand_id, supplier_id, supplier_price, selling_price, regular_price, active) VALUES ('".$this->data['IvItem']['sku']."',".$lastinsertID.", '".$this->data['IvItem']['name']."', '".$this->data['IvItem']['description1']."','".$this->data['IvItem']['description2']."', '".$this->data['IvItem']['efficiency']."','".$this->data['IvItem']['model']."','".$brandName."' ,'".$categoryName."' ,'".$supplierName."' ,'".$this->data['IvItem']['iv_category_id']."','".$this->data['IvItem']['iv_brand_id']."','".$this->data['IvItem']['iv_supplier_id']."', '".$this->data['IvItem']['supplier_price']."','".$this->data['IvItem']['selling_price']."','".$this->data['IvItem']['regular_price']."',".$this->data['IvItem']['active'].")";
			}
			$db->_execute($item_label2);


			$this->Session->write("message", $this->data['IvItem']['name']." was saved.".mysql_error());
		
			$this->redirect("pages/close");			

		} else {

			$this->Session->write("message", $this->data['IvItem']['name']." was not saved.");
		
			$this->redirect("pages/close");

		}		

	}

	

	function storeTree() {

		$this->layout = 'blank';
		$db =& ConnectionManager::getDataSource('default');	
		
		$this->set('mode', $_GET['mode']);

		$query = "
			SELECT * 
			FROM ace_iv_categories
			WHERE active = 1
		";

		$result = $db->_execute($query);		

		while($row = mysql_fetch_array($result))
		{
			$categories[] = $row;
		}
		$this->set('categories', $categories);
		
		//$this->set('categories', $this->IvCategory->findAll());

		$this->set('items', $this->IvItem->findAll());

		$this->set('message', $this->Session->read("message"));

		$this->Session->write("message", "");

		$this->set('message', $this->Session->read("message"));

	}

	

	function storeTreeItems() {

		$this->layout = 'blank';

		$db =& ConnectionManager::getDataSource('default');		

		

		$category_id = $_GET['category_id'];

		

		//the default if the tree is not set

		$hierarchy[0]['id'] = 'category_id';

		$hierarchy[0]['name'] = 'category';

		$hierarchy[0]['alias'] = 'Category';

		

		$hierarchy[1]['id'] = 'active';

		$hierarchy[1]['name'] = 'active';

		$hierarchy[1]['alias'] = 'Active';

		

		$query = "

			SELECT * 

			FROM ace_iv_category_settings

			WHERE iv_category_id = $category_id

			ORDER BY `order`

		";

		

		$result = $db->_execute($query);		

		while($row = mysql_fetch_array($result))

		{

			$aliases[$row['name']]['alias'] = $row['alias'];

		}		

		

		$query = "

			SELECT *

			FROM iv_items_labeled2

			WHERE 1 = 1

		";

		

		$mode = $_GET['mode'];

		$criteria = $_GET;

		

		foreach($criteria as $field => $get) {

			if($field != 'url' && $field != 'level' && $field != '_' && $field != 'seed' && $field != 'mode') {

				if(trim($get) != '') $query .= " AND $field LIKE '$get'";

			}

		}

		

		if(!isset($_GET['active'])) $query .= " AND active = 1";

		

		unset($criteria['url']);

		unset($criteria['_']);

		

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result))

		{		

			$items[$row['id']]['id'] = $row['id'];

			$items[$row['id']]['active'] = $row['active_name'];
			
			$items[$row['id']]['sku'] = $row['sku'];
			
			$items[$row['id']]['name'] = $row['name'];

			$items[$row['id']]['description1'] = $row['description1'];

			$items[$row['id']]['description2'] = $row['description2'];

			$items[$row['id']]['efficiency'] = $row['efficiency'];

			$items[$row['id']]['model'] = $row['model'];

			$items[$row['id']]['brand'] = $row['brand'];

			$items[$row['id']]['brand_id'] = $row['brand_id'];

			$items[$row['id']]['category'] = $row['category'];

			$items[$row['id']]['category_id'] = $row['category_id'];

			$items[$row['id']]['supplier'] = $row['supplier'];

			$items[$row['id']]['supplier_id'] = $row['supplier_id'];

			$items[$row['id']]['supplier_price'] = number_format($row['supplier_price'], 2, '.', '');

			$items[$row['id']]['selling_price'] = number_format($row['selling_price'], 2, '.', '');

			$items[$row['id']]['regular_price'] = number_format($row['regular_price'], 2, '.', '');

		}

		

		$this->set('criteria', json_encode($criteria));

		$this->set('items', $items);

		$this->set('count', count($items));

		$this->set('aliases', $aliases);

		$this->set('mode', $mode);

	}

	

	function storeList() {

		$this->layout = 'blank';

		$db =& ConnectionManager::getDataSource('default');	

		$job_type = $_GET['job_type'];	

				

		$hierarchy[1]['id'] = 'active';

		$hierarchy[1]['name'] = 'active';

		$hierarchy[1]['alias'] = 'Active';

		

		$query = "

			SELECT * 
			FROM ace_iv_category_settings						
			ORDER BY `order`

		";

		

		$result = $db->_execute($query);		

		while($row = mysql_fetch_array($result))

		{

			$aliases[$row['name']]['alias'] = $row['alias'];

		}		

		

		if(isset($job_type)) {

			$query = "

				SELECT *

				FROM iv_items_labeled2		

				WHERE active = 1

				AND category_id IN(

					SELECT item_category_id 

					FROM ace_rp_item_job_categories 

					WHERE job_type_id = $job_type

					)

			";

		} else {

			$query = "

				SELECT *

				FROM iv_items_labeled2			

				WHERE active = 1				

			";

		}



		$mode = $_GET['mode'];

		$criteria = $_GET;		

		

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result))

		{		

			$items[$row['id']]['id'] = $row['id'];

			$items[$row['id']]['active'] = $row['active_name'];

			$items[$row['id']]['sku'] = $row['sku'];

			$items[$row['id']]['name'] = $row['name'];

			$items[$row['id']]['description1'] = $row['description1'];

			$items[$row['id']]['description2'] = $row['description2'];

			$items[$row['id']]['efficiency'] = $row['efficiency'];

			$items[$row['id']]['model'] = $row['model'];

			$items[$row['id']]['brand'] = $row['brand'];

			$items[$row['id']]['brand_id'] = $row['brand_id'];

			$items[$row['id']]['category'] = $row['category'];

			$items[$row['id']]['category_id'] = $row['category_id'];

			$items[$row['id']]['supplier'] = $row['supplier'];

			$items[$row['id']]['supplier_id'] = $row['supplier_id'];

			$items[$row['id']]['supplier_price'] = number_format($row['supplier_price'], 2, '.', '');

			$items[$row['id']]['selling_price'] = number_format($row['selling_price'], 2, '.', '');

			$items[$row['id']]['regular_price'] = number_format($row['regular_price'], 2, '.', '');

		}

		

		$this->set('criteria', json_encode($criteria));

		$this->set('items', $items);

		$this->set('count', count($items));

		$this->set('categories_old', $this->IvCategory->findAll());
		$this->set('categories', $aliases);

		$this->set('aliases', $aliases);

		$this->set('mode', $mode);

	}

	

	function close() {

		$this->layout = 'blank';

	}


}

?>