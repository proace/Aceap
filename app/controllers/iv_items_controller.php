<?php

class IvItemsController extends AppController

{

	var $name = 'IvItems';

	var $components = array('HtmlAssist', 'Common', 'Lists');

	var $uses = array('IvCategory','IvItem','CallResult','TechInventoryItem','User');

	

	function index() {

		$this->layout = 'blank';

		$db =& ConnectionManager::getDataSource('default');	
		
		$this->set('mode', $_GET['mode']);

		$query = "
			SELECT * 
			FROM ace_iv_categories
			WHERE active = 1 order by cat_order 
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

	

	/*function branch() { 

		$this->layout = 'blank';

		$db =& ConnectionManager::getDataSource('default');		

		

		$category_id = $_GET['category_id'];

		//the default if the tree is not set

		$hierarchy[0]['id'] = 'category_id';

		$hierarchy[0]['name'] = 'category';

		$hierarchy[0]['alias'] = 'Category';

		

		// $hierarchy[1]['id'] = 'active';

		// $hierarchy[1]['name'] = 'active_name';

		// $hierarchy[1]['alias'] = 'Active';

		$hierarchy[1]['id'] = 'sub_category_id';

		$hierarchy[1]['name'] = 'sub_category_name';

		$hierarchy[1]['alias'] = 'Sub Category';

		

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

		
		// print_r($_GET); die;
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
		// print_r($query); die;

		 $query1 = "SELECT sc.name AS field , ivl.sub_category_id AS id FROM iv_items_labeled2 ivl JOIN ace_iv_sub_categories sc ON ivl.category_id  = sc.category_id WHERE 1 = 1 AND ivl.category_id LIKE $category_id GROUP BY sc.name";

		unset($criteria['url']);

		unset($criteria['_']);

		$result = $db->_execute($query);

		$id = 0;
		// print_r($criteria); die;
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

	}*/
	
	// LOKI : Show sub category list
	function midBranch()
	{
		$mode = $_GET['mode'];
		$db 	=& ConnectionManager::getDataSource('default');	
		$category_id = $_GET['category_id'];
		$query = "SELECT * from ace_iv_mid_categories where category_id=".$category_id." order by sort";
		$result = $db->_execute($query);
		$items = array();
		$id = 0;
		while($row = mysql_fetch_array($result))
		{
			$items[$id]['field'] = $row['name'];
			$row_criteria = array('seed' => '', 'level' => 1, 'category_id' =>  $row['id'], 'sub_category_id' => $row['id'], 'mode' => $mode );
			$items[$id]['criteria'] = json_encode($row_criteria);
			$id++;
		}
		$this->layout = 'blank';
		$this->set("items", $items);
		$this->set('mode', $mode);
		
	}


	// LOKI : Show sub category list
	function branch()
	{
		$mode = $_GET['mode'];
		$db 	=& ConnectionManager::getDataSource('default');	
		$category_id = $_GET['category_id'];
		$query = "SELECT * from ace_iv_sub_categories where category_id=".$category_id." order by sort";
		$result = $db->_execute($query);
		$items = array();
		$id = 0;
		while($row = mysql_fetch_array($result))
		{
			$items[$id]['field'] = $row['name'];
			$row_criteria = array('seed' => '', 'level' => 1, 'category_id' => $category_id, 'sub_category_id' => $row['id'], 'mode' => $mode );
			$items[$id]['criteria'] = json_encode($row_criteria);
			$id++;
		}
		$this->layout = 'blank';
		$this->set("items", $items);
		$this->set('mode', $mode);
		
	}
	

	function items() {

		$this->layout = 'blank';

		$db =& ConnectionManager::getDataSource('default');		

		/*live pkg= 39, local= 41*/
		$subCategories = $this->Lists->IvSubCategories(39);
		
		// $subCategoryList = $this->Lists->IvSubCategories();
		$midCategoryList = $this->Lists->IvMidCategories();

		$category_id = $_GET['category_id'];

		//the default if the tree is not set

		$hierarchy[0]['id'] = 'category_id';

		$hierarchy[0]['name'] = 'category';

		$hierarchy[0]['alias'] = 'Category';

		

		$hierarchy[1]['id'] = 'active';

		$hierarchy[1]['name'] = 'active';

		$hierarchy[1]['alias'] = 'Active';

		// Get Inactive subcategory Id
		$query = "SELECT id FROM ace_iv_sub_categories WHERE category_id = $category_id AND name='Inactive'";
		$res = 	$db->_execute($query);
		$inactiveId = mysql_fetch_array($res);

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

				if(trim($get) != '' && $field != 'category_id') $query .= " AND $field LIKE '$get'";

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

			$items[$row['id']]['image'] = $row['image'];
			
			$items[$row['id']]['default_quantity'] = $row['default_quantity'];

			$items[$row['id']]['description1'] = $row['description1'];

			$items[$row['id']]['description2'] = $row['description2'];

			$items[$row['id']]['efficiency'] = $row['efficiency'];

			$items[$row['id']]['dba'] = $row['dba'];

			$items[$row['id']]['tonnage'] = $row['tonnage'];

			$items[$row['id']]['seer'] = $row['seer'];

			$items[$row['id']]['position'] = $row['position'];

			$items[$row['id']]['voltage'] = $row['voltage'];

			$items[$row['id']]['amprage'] = $row['amprage'];
			
			$items[$row['id']]['btu'] = $row['btu'];

			$items[$row['id']]['model'] = $row['model'];

			$items[$row['id']]['brand'] = $row['brand'];
			
			$items[$row['id']]['is_import'] = $row['is_import'];

			$items[$row['id']]['brand_id'] = $row['brand_id'];
			$items[$row['id']]['warranty'] = $row['warranty'];
			$items[$row['id']]['warranty'] = $row['warranty'];
			$items[$row['id']]['mocp'] = $row['mocp'];

			$items[$row['id']]['supplier'] = $row['supplier'];
			$items[$row['id']]['category_id'] = $row['category_id'];
			$items[$row['id']]['supplier_id'] = $row['supplier_id'];
			$items[$row['id']]['brand_name'] = $row['brand'];
			$items[$row['id']]['supplier_name'] = $row['supplier'];
			$items[$row['id']]['category_name'] = $row['category'];
			$items[$row['id']]['sub_category_id'] = $row['sub_category_id'];
			$items[$row['id']]['sub_category_name'] = $row['sub_category_name'];
			$items[$row['id']]['markup_percent'] = $row['markup_percent'];
			$items[$row['id']]['tech_percent'] = $row['tech_percent'];
			
			$items[$row['id']]['supplier_price'] = number_format($row['supplier_price'], 2, '.', '');

			$items[$row['id']]['selling_price'] = number_format($row['selling_price'], 2, '.', '');

			$items[$row['id']]['regular_price'] = number_format($row['regular_price'], 2, '.', '');
			$items[$row['id']]['tech_data'] = $this->getInventoryTechQty($row['id']);

		}

		
		
		$this->set('subCategories',$subCategories);

		$this->set('midCategoryList',$midCategoryList);
		
		$this->set('category_id',$category_id);
		
		$this->set('inventoryTechnician',$this->Lists->inventoryTech());

		$this->set('criteria', json_encode($criteria));

		$this->set('items', $items);

		$this->set('count', count($items));

		$this->set('aliases', $aliases);

		$this->set('inventoryAccess', $this->Session->read("Inventory"));

		$this->set('mode', $mode);

		$this->set('query', $query);

		$this->set('InactiveId', $inactiveId['id']);	

	}

	/*Loki: search inventory items.*/
	function searchInventoryItems()
	{
		$this->layout = 'blank';

		$needle = $_GET['str'];
		
		$db =& ConnectionManager::getDataSource('default');
		
		$query = "SELECT i.*
			FROM iv_items_labeled2 i
			JOIN ace_iv_sub_categories sub ON i.sub_category_id  = sub.id
			WHERE active = 1 and sub.name != 'Inactive' and i.name LIKE \"%$needle%\"";
		
		$result = $db->_execute($query);
		
		while($row = mysql_fetch_array($result))
		{		

			$items[$row['id']]['id'] = $row['id'];

			$items[$row['id']]['image'] = $row['image'];

			$items[$row['id']]['active'] = $row['active_name'];

			$items[$row['id']]['name'] = $row['name'];
			
			$items[$row['id']]['sku'] = $row['sku'];
			
			$items[$row['id']]['default_quantity'] = $row['default_quantity'];

			$items[$row['id']]['description1'] = $row['description1'];

			$items[$row['id']]['description2'] = $row['description2'];

			$items[$row['id']]['efficiency'] = $row['efficiency'];

			$items[$row['id']]['model'] = $row['model'];

			$items[$row['id']]['brand'] = $row['brand'];

			$items[$row['id']]['brand_id'] = $row['brand_id'];

			$items[$row['id']]['supplier'] = $row['supplier'];
			$items[$row['id']]['category_id'] = $row['category_id'];
			$items[$row['id']]['supplier_id'] = $row['supplier_id'];
			$items[$row['id']]['brand_name'] = $row['brand'];
			$items[$row['id']]['supplier_name'] = $row['supplier'];
			$items[$row['id']]['category_name'] = $row['category'];
			$items[$row['id']]['sub_category_id'] = $row['sub_category_id'];
			$items[$row['id']]['sub_category_name'] = $row['sub_category_name'];
			$items[$row['id']]['markup_percent'] = $row['markup_percent'];
			$items[$row['id']]['tech_percent'] = $row['tech_percent'];
			
			$items[$row['id']]['supplier_price'] = number_format($row['supplier_price'], 2, '.', '');

			$items[$row['id']]['selling_price'] = number_format($row['selling_price'], 2, '.', '');

			$items[$row['id']]['regular_price'] = number_format($row['regular_price'], 2, '.', '');

			$query1 = "SELECT id FROM ace_iv_sub_categories WHERE category_id =".$row['category_id']." AND name='Inactive'";
			$res1 = 	$db->_execute($query1);
			$inactive = mysql_fetch_assoc($res1);
			$items[$row['id']]['inactiveId'] = $inactive['id'];
			$items[$row['id']]['tech_data'] = $this->getInventoryTechQty($row['id']);

		}
		$this->set('inventoryTechnician',$this->Lists->inventoryTech());

		$this->set('items', $items);
	}

	function edit($id, $catId=0,$fromInventory=0) {
		$db =& ConnectionManager::getDataSource('default');	

		if(isset($_GET['category_id']))
		{
			$catId = $_GET['category_id'];
		}
		$this->layout = 'blank';	
		// print_r($_GET);die();
		if (empty($this->data['IvItem'])) {
			// die('here');
			$this->IvItem->id = $id;    
	
			$this->data = $this->IvItem->read();

			$this->set('catId', $catId);
			
			$this->set('fromInventory', $fromInventory);

			$this->set('categories', $this->Lists->IvMidCategories());

			$this->set('subCategories', $this->Lists->IvSubCategories($catId));

			$this->set('category_id', $catId);

			$this->set('brands', $this->Lists->IvBrands());

			$this->set('brand_id', isset($_GET['brand_id'])?$_GET['brand_id']:'');
			
			$this->set('sub_category_id', isset($_GET['sub_category_id'])?$_GET['sub_category_id']:'');

			$this->set('suppliers', $this->Lists->IvSuppliers());

			$this->set('supplier_id', isset($_GET['supplier_id'])?$_GET['supplier_id']:'');

			$this->set('efficiency', isset($_GET['efficiency'])?$_GET['efficiency']:'');
			
			$this->set('dba', isset($_GET['dba'])?$_GET['dba']:'');
			
			$this->set('tonnage', isset($_GET['tonnage'])?$_GET['tonnage']:'');
			
			$this->set('seer', isset($_GET['seer'])?$_GET['seer']:'');
			
			$this->set('position', isset($_GET['position'])?$_GET['position']:'');
			
			$this->set('voltage', isset($_GET['voltage'])?$_GET['voltage']:'');
			
			$this->set('amprage', isset($_GET['amprage'])?$_GET['amprage']:'');

			$this->set('btu', isset($_GET['btu'])?$_GET['btu']:'');

			$this->set('mocp', isset($_GET['mocp'])?$_GET['mocp']:'');

			$this->set('description1', isset($_GET['description1'])?$_GET['description1']:'');

			$this->set('description2', isset($_GET['description2'])?$_GET['description2']:'');

			$this->set('yesOrNo', $this->Lists->YesOrNo());

		}

		if(!empty($id)) {
			$get_image = "select * from iv_items_labeled2 where id=$id";
			$result_image = $db->_execute($get_image);		

			$row_image = mysql_fetch_array($result_image);

		
		
		
			$this->set('images1', $row_image['image']);

			$this->set('warranty', $row_image['warranty']);

			$this->set('link', $row_image['link']);
			
			$this->set('member_price', $row_image['member_price']);

			$this->set('isImport', $row_image['is_import']);

			$this->set('subCategories', $this->Lists->IvSubCategories($row_image['category_id']));
			
			$this->set('sub_category_id', $row_image['sub_category_id']);

		}
		
	}

	

	function save() {
		$db =& ConnectionManager::getDataSource('default');	
		$fromInventory = $_POST['fromInventory'];
		$is_duplicant = isset($_POST['is_duplicant']) ? $_POST['is_duplicant']  : 0;
		
		$get_item_id = $_REQUEST['id'];
		
		
		$member_price = $_POST['member_price'];
		if(empty($member_price)){
			$member_price="0";
		}
		
		if(!empty($_FILES['upload_image']['name'])){
			
			$file_name = $_FILES['upload_image']['name'];
			
			$file_tmpname = $_FILES['upload_image']['tmp_name'];
		
		date_default_timezone_set('America/Los_Angeles');

		$year = date('Y', time());
		if (!file_exists($year)) {
			mkdir('upload_photos/'.$year, 0755);
		}
		$month = date('Y/m', time());
		if (!file_exists($month)) {
			mkdir('upload_photos/'.$month, 0755);
		}

		$day = date('Y/m/d', time());
		if (!file_exists($day)) {
			mkdir('upload_photos/'.$day, 0755);
		}
		$path = $file_name;
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		

			$name = date('Ymdhis', time()).'_'.rand().$path;
		
			
		if ( 0 < $file['error'] ) {
	        echo 'Error: ' . $_FILES['image']['error'] . '<br>'; 
	    } else {
			$maxDimW = 800;
			$maxDimH = 500;
			list($width, $height, $type, $attr) = getimagesize( $file_tmpname );
			if ( $maxDimW > $maxDimW || $height > $maxDimH ) {
			$target_filename = $file_tmpname;
			$fn = $file_tmpname;
			$size = getimagesize( $fn );
			$ratio = $size[0]/$size[1]; // width/height
			
			$width =$width/4;
			$height=$height/4;
			
			$src = imagecreatefromstring(file_get_contents($fn));
			$dst = imagecreatetruecolor( $width, $height );
			imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $size[0], $size[1] );

			imagejpeg($dst, $target_filename); // adjust format as needed
			
			


			}
	        move_uploaded_file($file_tmpname, 'upload_photos/'.$day.'/'.$name);
			$db->_execute("UPDATE iv_items_labeled2 set image = '$name', is_import = 0 WHERE id = ".$this->data['IvItem']['id']."");
	    }
		}
		
		
		if($is_duplicant)
		{
			$this->data['IvItem'] = $_POST['postdata'];
			$this->data['IvItem']['name'] = stripslashes($_POST['postdata']['name']);
			$this->data['IvItem']['sku'] = stripslashes($_POST['postdata']['sku']);
			if($this->data['IvItem']['iv_sub_category_id'] == NULL || $this->data['IvItem']['iv_sub_category_id'] == '')
			{
				$this->data['IvItem']['iv_sub_category_id'] = 0;
			}

			if($this->data['IvItem']['tech_default'] == NULL || $this->data['IvItem']['tech_default'] == '')
			{
				$this->data['IvItem']['tech_default'] = 0;
			}
		}
		if($this->data['IvItem']['active'] != 1) $this->data['IvItem']['active'] = 0;
		
		if($this->IvItem->save($this->data['IvItem'])) {
			$brandName = $_POST['brandName'];
			$supplierName = $_POST['supplierName'];
			$categoryName = $_POST['categoryName'];
			$subCategoryName = $_POST['subCategoryName'];
			$dba = isset($this->data['IvItem']['dba']) ? $this->data['IvItem']['dba'] : '';
			$tonnage = isset($this->data['IvItem']['tonnage']) ? $this->data['IvItem']['tonnage'] : '';
			$seer = isset($this->data['IvItem']['seer']) ? $this->data['IvItem']['seer'] : '';
			$position = isset($this->data['IvItem']['position']) ? $this->data['IvItem']['position'] : '';
			$voltage = isset($this->data['IvItem']['voltage']) ? $this->data['IvItem']['voltage'] : '';
			$amprage = isset($this->data['IvItem']['amprage']) ? $this->data['IvItem']['amprage'] : '';
			$btu = isset($this->data['IvItem']['btu']) ? $this->data['IvItem']['btu'] : '';
			$mocp = isset($this->data['IvItem']['mocp']) ? $this->data['IvItem']['mocp'] : '';
			if(!empty($this->data['IvItem']['id']))
			{
				if(empty($this->data['IvItem']['warranty'])){
					$warranty='NULL';
				}
				else {
					$warranty=$this->data['IvItem']['warranty'];
				}
				
				if(empty($this->data['IvItem']['link']))
				{
					$link='NULL';
				}
				else {
					$link=$this->data['IvItem']['link'];
				}
				$item_label2 = "UPDATE iv_items_labeled2 set sku='".$this->data['IvItem']['sku']."',name='".mysql_real_escape_string($this->data['IvItem']['name'])."',regular_price='".$this->data['IvItem']['regular_price']."',selling_price='".$this->data['IvItem']['selling_price']."',supplier_price='".$this->data['IvItem']['supplier_price']."',description1='".mysql_real_escape_string($this->data['IvItem']['description1'])."',description2='".mysql_real_escape_string($this->data['IvItem']['description2'])."',efficiency='".$this->data['IvItem']['efficiency']."',model='".$this->data['IvItem']['model']."',category_id='".$this->data['IvItem']['iv_category_id']."',brand_id='".$this->data['IvItem']['iv_brand_id']."',supplier_id='".$this->data['IvItem']['iv_supplier_id']."',active='".$this->data['IvItem']['active']."', brand='".$brandName."', category='".$categoryName."', supplier='".$supplierName."', sub_category_id='".$this->data['IvItem']['iv_sub_category_id']."', sub_category_name ='".$subCategoryName."', default_quantity = '".$this->data['IvItem']['default_quantity']."', tech_default = ".$this->data['IvItem']['tech_default']."
				,markup_percent = ".$this->data['IvItem']['markup_percent'].",tech_percent = ".$this->data['IvItem']['tech_percent']." ,warranty = '".$warranty."' ,
				link = '".$link."' ,member_price = ".$member_price.", dba = '".$dba."', tonnage='".$tonnage."', seer='".$seer."',position = '".$position."',
				voltage='".$voltage."', amprage='".$amprage."', btu='".$btu."'   WHERE id = '".$this->data['IvItem']['id']."'";
			} else {
				$lastinsertID = $this->IvItem->getLastInsertId();
				
				$item_label2 = "INSERT INTO iv_items_labeled2 (sku,id,name, description1, description2,efficiency, model, 
				brand, category, supplier,  category_id, brand_id, supplier_id, supplier_price, selling_price, regular_price, 
				active, sub_category_id, sub_category_name,default_quantity,tech_default,markup_percent,tech_percent,member_price,
				 dba, tonnage, seer, position, voltage, amprage,mocp)
				VALUES ('".mysql_real_escape_string($this->data['IvItem']['sku'])."',".$lastinsertID.", 
					'".mysql_real_escape_string($this->data['IvItem']['name'])."', '".mysql_real_escape_string($this->data['IvItem']['description1'])."',
					'".mysql_real_escape_string($this->data['IvItem']['description2'])."', '".$this->data['IvItem']['efficiency']."',
					'".$this->data['IvItem']['model']."','".$brandName."','".$categoryName."','".$supplierName."',
					'".$this->data['IvItem']['iv_category_id']."','".$this->data['IvItem']['iv_brand_id']."',
					'".$this->data['IvItem']['iv_supplier_id']."', '".$this->data['IvItem']['supplier_price']."',
					'".$this->data['IvItem']['selling_price']."','".$this->data['IvItem']['regular_price']."',
					".$this->data['IvItem']['active'].", '".$this->data['IvItem']['iv_sub_category_id']."', 
					'".$subCategoryName."', '".$this->data['IvItem']['default_quantity']."',".$this->data['IvItem']['tech_default'].",
					".$this->data['IvItem']['markup_percent'].",".$this->data['IvItem']['tech_percent'].",".$member_price."
					,'".$dba."', '".$tonnage."','".$seer."','".$position."','".$voltage."','".$amprage."', '".$mocp."'
					)";
			}
			$result = $db->_execute($item_label2);
			if($is_duplicant)
			{
				if ($result) {
					$this->Session->write("message", $this->data['IvItem']['name']." was saved.".mysql_error());
		 			$response  = array("res" => "OK");
		 			echo json_encode($response);
		 			exit;
 				}
			} else {
				$this->Session->write("message", $this->data['IvItem']['name']." was saved.".mysql_error());
				$image_src="/acesys/upload_photos/$day/$name";
				// echo "<script>opener.location.reload();
				//echo '<script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>';
			
				$img_src = '<img class="hover_image" style="height:30px;width:30px;" src="'.$image_src.'"><span class="hover_span"><img class="hover_image" style="height:30px;width:30px;" src="'.$image_src.'"></span>';
				if(!empty($_FILES['upload_image']['name'])){
				echo "<script>window.opener.document.getElementById(".$get_item_id.").innerHTML='".$img_src."';</script>";	
					
				}
				echo "<script>
				
				//window.opener.find($('.img-".$get_item_id."')).html('1111');
				window.returnValueId= '".$lastinsertID.";".$this->data['IvItem']['name'].";".$this->data['IvItem']['supplier_price'].";".$this->data['IvItem']['sku'].";".$this->data['IvItem']['iv_category_id']."; ".$this->data['IvItem']['selling_price']."; ".$this->data['IvItem']['default_quantity'].";<img src>';
				
				window.close();
				console.log(window.opener);
				</script>";
		 			exit;
				//$this->redirect("pages/close");			
			}

		} else {

			$this->Session->write("message", $this->data['IvItem']['name']." was not saved.");
		
			$this->redirect("pages/close");

		}		

	}
	//Apoorv delete items image
	function deleteImage(){
		$db=& ConnectionManager::getDataSource('default');
		$id = $_REQUEST['id'];
		$path = $_REQUEST['path'];
		$name = $_REQUEST['name'];
		
		$db->_execute("UPDATE iv_items_labeled2 set image = '' WHERE id = ".$id."");
		$path1= getcwd().$path;
		echo unlink($path1);
		exit();
	}

	

	function storeTree() {

		$this->layout = 'blank';
		$db =& ConnectionManager::getDataSource('default');	
		
		$this->set('mode', $_GET['mode']);

		$query = "
			SELECT * 
			FROM ace_iv_categories
			WHERE active = 1 order by cat_order
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

			SELECT i.*, techItem.quantity as warehouse_qty

			FROM iv_items_labeled2 i
			LEFT JOIN ace_rp_tech_inventory_item techItem ON i.id  = techItem.item_id AND techItem.tech_id = 0
			WHERE 1 = 1

		";

		

		$mode = $_GET['mode'];

		$criteria = $_GET;

		

		foreach($criteria as $field => $get) {

			if($field != 'url' && $field != 'level' && $field != '_' && $field != 'seed' && $field != 'mode') {

				if(trim($get) != '' && $field != 'category_id') $query .= " AND $field LIKE '$get'";

			}

		}

		

		if(!isset($_GET['active'])) $query .= " AND active = 1";

		

		unset($criteria['url']);

		unset($criteria['_']);

		

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result))

		{		

			$items[$row['id']]['id'] = $row['id'];

			$items[$row['id']]['image'] = $row['image'];

			$items[$row['id']]['active'] = $row['active_name'];
			
			$items[$row['id']]['sku'] = $row['sku'];
			
			$items[$row['id']]['name'] = $row['name'];

			$items[$row['id']]['description1'] = $row['description1'];

			$items[$row['id']]['description2'] = $row['description2'];

			$items[$row['id']]['efficiency'] = $row['efficiency'];

			$items[$row['id']]['model'] = $row['model'];

			$items[$row['id']]['brand'] = $row['brand'];

			$items[$row['id']]['dba'] = $row['dba'];
			$items[$row['id']]['tonnage'] = $row['tonnage'];
			$items[$row['id']]['seer'] = $row['seer'];
			$items[$row['id']]['position'] = $row['position'];
			$items[$row['id']]['warranty'] = $row['warranty'];
			$items[$row['id']]['btu'] = $row['btu'];
			
			$items[$row['id']]['efficiency'] = $row['efficiency'];

			$items[$row['id']]['brand_id'] = $row['brand_id'];

			$items[$row['id']]['category'] = $row['category'];

			$items[$row['id']]['category_id'] = $row['category_id'];

			$items[$row['id']]['supplier'] = $row['supplier'];

			$items[$row['id']]['supplier_id'] = $row['supplier_id'];

			$items[$row['id']]['supplier_price'] = number_format($row['supplier_price'], 2, '.', '');

			$items[$row['id']]['selling_price'] = number_format($row['selling_price'], 2, '.', '');

			$items[$row['id']]['regular_price'] = number_format($row['regular_price'], 2, '.', '');
			
			$items[$row['id']]['sub_category_id'] = $row['sub_category_id'];
			
			$items[$row['id']]['warehouse_qty'] = $row['warehouse_qty'];
			$items[$row['id']]['markup_percent'] = $row['markup_percent'];
			$items[$row['id']]['tech_percent'] = $row['tech_percent'];
			

			$items[$row['id']]['tech_data'] = $this->getInventoryTechQty($row['id']);

		}
		
		
		 $this->set('inventoryTechnician',$this->Lists->inventoryTech());

		$this->set('criteria', json_encode($criteria));

		$this->set('items', $items);

		$this->set('count', count($items));

		$this->set('aliases', $aliases);

		$this->set('mode', $mode);

	}

	/*Loki: get technician item quantity*/
	function getInventoryTechQty($item_id)
		{
			$data = $this->TechInventoryItem->findAll(array('TechInventoryItem.item_id' => $item_id),array('tech_id','quantity'));
			return $data;
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

	// Loki: Delete the item
	function removeDuplicantItem()
	{
		$db 			=& ConnectionManager::getDataSource('default');	
		$id 			= $_POST['item_id'];
		$inactiveId 	= $_POST['inactiveId'];
		$query = "Select * from ace_rp_order_items where item_id =".$id;
		$result 		= $db->_execute($query);
		$itemDetails = mysql_fetch_array($result);
		if(!empty($itemDetails)) {
			$response  = array("res" => "0");
			echo json_encode($response);
			exit;
		} else {
			$query = "Delete from  iv_items_labeled2 where id=".$id;
			$result = $db->_execute($query);

			$response  = array("res" => "OK");
 			echo json_encode($response);
 			exit;
		}
		// $query 			= "UPDATE  ace_iv_items set iv_sub_category_id =".$inactiveId." where id=".$id;
		// $result 		= $db->_execute($query);
		// if($result)
		// {
		// 	$query1 = "UPDATE  iv_items_labeled2 set sub_category_id =".$inactiveId." where id=".$id;
		// 	$result1 = $db->_execute($query1);
		// }
		// if($result1)
		// {
		// 	$response  = array("res" => "OK");
 		// 	echo json_encode($response);
 		// 	exit;
		// }
	}

	// LOki: Get sub Categories

	function getSubCategories()
	{
		$catId =  $_POST['categoryId'];
		$subCategories = $this->Lists->IvSubCategories($catId);
		$res = "<option value='0'>Choose Here</option>";
		foreach ($subCategories as $key => $value) {
			$res .= "<option value='".$key."'>".$value."</option>";
		}
		echo $res;
		exit();
	}

	function getMidCategories()
	{
		$catId =  $_POST['categoryId'];
		$midCategories = $this->Lists->IvMidCategories($catId);
		$res = "<option value='0'>Choose Here</option>";
		foreach ($midCategories as $key => $value) {
			$res .= "<option value='".$key."'>".$value."</option>";
		}
		echo $res;
		exit();
	}

		/*Loki: copy items to new category*/
		function copyItems()
		{
			$now = date('Y-m-d');
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$bookItems = json_decode(stripslashes($_POST['items']), true);
			$catId = $_POST['catId'];
			$subCatId = ($_POST['subCatId']);
			// $packages = json_decode(stripslashes($_POST['packages']), true);
			
				foreach ($bookItems as $itemKey => $itemValue) {
					$itemRes = $db->_execute("Select * from iv_items_labeled2 where id =".$itemValue['item_id']);
					$itemDetails = mysql_fetch_array($itemRes);
					$db->_execute("INSERT INTO ace_iv_items  (sku,name, description1, description2,efficiency, model, 
					iv_category_id, iv_brand_id, iv_supplier_id, supplier_price, selling_price, regular_price, 
					active, iv_sub_category_id,default_quantity,tech_default,markup_percent,tech_percent,dba, tonnage, seer, 
					position, voltage, amprage)
					VALUES ('".$itemDetails['sku']."', '".mysql_real_escape_string($itemDetails['name'])."', '".$itemDetails['description1']."',
					'".$itemDetails['description2']."', '".$itemDetails['efficiency']."','".$itemDetails['model']."',
					".$catId.",".$itemDetails['brand_id'].", ".$itemDetails['supplier_id'].", '".$itemDetails['supplier_price']."',
					'".$itemDetails['selling_price']."', '".$itemDetails['regular_price']."',  ".$itemDetails['active'].",
					".$subCatId.", '0', '0', '".$itemDetails['markup_percent']."',
					'".$itemDetails['tech_percent']."', '".$itemDetails['dba']."','".$itemDetails['tonnage']."', 
					'".$itemDetails['seer']."', '".$itemDetails['position']."','".$itemDetails['voltage']."', '".$itemDetails['amprage']."')"); 
					
					$lastinsertID = $db->lastInsertId();
					$item_label2 = "INSERT INTO iv_items_labeled2 (sku,id,name, description1, description2,efficiency, model, 
					brand, category, supplier,  category_id, brand_id, supplier_id, supplier_price, selling_price, regular_price, 
					active, sub_category_id, sub_category_name,default_quantity,tech_default,markup_percent,tech_percent,member_price,
				 	dba, tonnage, seer, position, voltage, amprage)
					 VALUES ('".$itemDetails['sku']."',".$lastinsertID.", '".mysql_real_escape_string($itemDetails['name'])."', '".$itemDetails['description1']."',
					'".$itemDetails['description2']."', '".$itemDetails['efficiency']."','".$itemDetails['model']."',
					'".$itemDetails['brand']."', '".$itemDetails['category']."', '".$itemDetails['supplier']."',".$catId.",
					".$itemDetails['brand_id'].", ".$itemDetails['supplier_id'].", '".$itemDetails['supplier_price']."',
					'".$itemDetails['selling_price']."', '".$itemDetails['regular_price']."',  ".$itemDetails['active'].",
					".$subCatId.", '".$itemDetails['sub_category_name']."', '0', '0', '".$itemDetails['markup_percent']."',
					'".$itemDetails['tech_percent']."', '".$itemDetails['member_price']."', '".$itemDetails['dba']."',
					'".$itemDetails['tonnage']."', '".$itemDetails['seer']."', '".$itemDetails['position']."',
					'".$itemDetails['voltage']."', '".$itemDetails['amprage']."')"; 
					
					$res = $db->_execute($item_label2);  
				}	
			if($res)
			{
				 $response  = array("res" => "1");
				 echo json_encode($response);
				exit();
			}
		}
		 
		function transferItems()
		{
			$now = date('Y-m-d');
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$bookItems = json_decode(stripslashes($_POST['items']), true);
			$catId = $_POST['catId'];
			$subCatId = ($_POST['subCatId']);

			foreach ($bookItems as $itemKey => $itemValue) {
				$db->_execute("Update ace_iv_items  set iv_sub_category_id =".$subCatId.", iv_category_id=".$catId." where id=".$itemValue['item_id']);
				
				$res = $db->_execute("Update iv_items_labeled2  set sub_category_id =".$subCatId.", category_id=".$catId." where id=".$itemValue['item_id']);
			}

			if($res)
			{
				 $response  = array("res" => "1");
				 echo json_encode($response);
				exit();
			}
		}

		public function deleteMultipleItems()
		{
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$bookItems = json_decode(stripslashes($_POST['items']), true);
			$res = 1;
			foreach ($bookItems as $itemKey => $itemValue) {
				$query = "Select * from ace_rp_order_items where item_id =".$itemValue['item_id'];
				$result 		= $db->_execute($query);
				$itemDetails = mysql_fetch_array($result);
				if(empty($itemDetails)) {
					$db->_execute("Delete from ace_iv_items where id=".$itemValue['item_id']);
					
					$res = $db->_execute("Delete from iv_items_labeled2 where id=".$itemValue['item_id']);
				}
			}
			if($res)
			{
				 $response  = array("res" => "1");
				 echo json_encode($response);
				exit();
			}
		}
	function importItem() {

		$this->layout = 'blank';

		$db =& ConnectionManager::getDataSource('default');		

		$query = "
		SELECT * 
		FROM ace_iv_categories
		WHERE active = 1 order by cat_order 
		";

		$result = $db->_execute($query);		
		while($row = mysql_fetch_array($result))
		{
			$categories[] = $row;
		}
		$this->set('brands', $this->Lists->IvBrands());
		$this->set('suppliers', $this->Lists->IvSuppliers());
		$this->set('mainCategories', $categories);
	}

	function import() {
		$db =& ConnectionManager::getDataSource('default');	
		if(isset($_POST["Import"])){
			$filename=$_FILES["file"]["tmp_name"];    
			$brandName = $_POST['brandName'];
			$supplierName = $_POST['supplierName'];
			$categoryName = $_POST['categoryName'];
			$subCategoryName = $_POST['subCategoryName'];
			$categoryId = $_POST['category_id'];
			$subCategoryId = $_POST['sub_category_id'];
			$brandId = $_POST['data']['IvItem']['brand_id'];
			$supplierId = $_POST['data']['IvItem']['supplier_id'];
			 if($_FILES["file"]["size"] > 0)
			 {
				$file = fopen($filename, "r");
				  while (($getData = fgetcsv($file, 10000, ",")) !== FALSE)
				   {
					   $data = array(
							'id' => '',
							'sku' => $getData[0],
							'default_quantity' => 1,
							'name' => $getData[1],
							'markup_percent' => 0,
							'tech_percent' => 0,
							'supplier_price' => $getData[6],
							'warranty' => '',
							'link' => '',
							'selling_price' => $getData[7],
							'description1' => $getData[2],
							'description2' => $getData[3],
							'efficiency' => $getData[4],
							'model' => $getData[5],
							'iv_category_id' => $categoryId,
							'iv_sub_category_id' => $subCategoryId,
							'iv_brand_id' => $brandId,
							'iv_supplier_id' => $supplierId,
							'active' => 1,
							'notify_admin' => 1,
							'tech_default' => 0,
							'dba' => $getData[8],
							'tonnage' => $getData[9],
							'seer' => $getData[10],
							'position' => $getData[11],
							'voltage' => $getData[12],
							'mocp' => $getData[13],
							'amprage' => $getData[14],
							'btu' => $getData[15],
							'warranty' => $getData[16]
					   );
					$this->IvItem->save($data);
					$lastinsertID = $this->IvItem->getLastInsertId();
					$query = "INSERT INTO iv_items_labeled2 (sku,id,name, description1, description2,efficiency, model, brand, category, 
					supplier,  category_id, brand_id, supplier_id, supplier_price, selling_price, regular_price, active, sub_category_id, 
					sub_category_name,default_quantity,tech_default, dba, tonnage, seer, position, voltage,mocp,amprage,btu,warranty,image,is_import)
					VALUES ('".mysql_real_escape_string($getData[0])."',".$lastinsertID.", '".mysql_real_escape_string($getData[1])."', 
					'".mysql_real_escape_string($getData[2])."','".mysql_real_escape_string($getData[3])."', '".$getData[4]."','".$getData[5]."',
					'".$brandName."' ,'".$categoryName."' ,'".$supplierName."' ,'".$categoryId."','".$brandId."','".$supplierId."', 
					'".$getData[6]."','".$getData[7]."','','1', '".$subCategoryId."', '".$subCategoryName."','0','0','".$getData[8]."','".$getData[9]."'
					,'".$getData[10]."','".$getData[11]."','".$getData[12]."','".$getData[13]."','".$getData[14]."','".$getData[15]."','".$getData[16]."','".$getData[17]."',1)";
					
					$result = $db->_execute($query);	
						if(!isset($result))
						{
						echo "<script type=\"text/javascript\">
							alert(\"Invalid File:Please Upload CSV File.\");
							window.location.reload()
							</script>";    
						}
						else {
							echo "<script type=\"text/javascript\">
							alert(\"CSV File has been successfully Imported.\");
							location.href = 'importItem';
						</script>";
						}
				   }
				   fclose($file);  
			}
		  }   
	}

	function getItemDetails()
	{
		$itemId =  $_GET['item_id'];
		$db =& ConnectionManager::getDataSource('default');
		$itemdeatils = "select * from iv_items_labeled2 where id=$itemId";
		$result = $db->_execute($itemdeatils);		

		$response = mysql_fetch_array($result);
		echo json_encode($response);
		exit();
	}


}

?>