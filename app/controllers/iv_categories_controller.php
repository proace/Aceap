<?php

class IvCategoriesController extends AppController

{

	var $name = 'IvCategories';

	var $tablePrefix = 'ace_iv_'; 

	var $components = array('HtmlAssist', 'Common', 'Lists');

	#Loki: show all item categories
	 function showItemCategory()
	{				
		$db =& ConnectionManager::getDataSource('default');	
		$query = "SELECT * from ace_iv_categories";

		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $items[$row['id']][$k] = $v;
		}
		
		$this->set('itemCategories', $items);
	}
	function changeActive()
	{
		$jobTypeId = $_GET['jobtype_id'];
		$isActive = $_GET['is_active'];
		$db =& ConnectionManager::getDataSource('default');	
		$query = "UPDATE ace_iv_categories set active = ".$isActive." WHERE id=".$jobTypeId."";
		$result = $db->_execute($query);
		exit();
	}
	function deleteCategory()
	{
			$data = $_POST['typeIds'];
			$ids = implode(',', $data);
			$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
			$query = "DELETE from  ace_iv_categories WHERE id IN (".$ids.")";
			$result = $db->_execute($query);
			exit();
	}
	function edit($id = null) {

		$this->layout = 'blank';	

		if (empty($this->data['IvCategory'])) {    

			$this->IvCategory->id = $id;    

			$this->data = $this->IvCategory->read();			

		}

	}

	function editCategory($id = null) {
		$this->layout = 'blank';
		if (empty($this->data['IvCategory'])) {    

			$this->IvCategory->id = $id;    

			$cat = $this->IvCategory->read();	
			// print_r($cat['IvCategory']['id']); die;
			$this->set("cat", $cat['IvCategory']);
		}

	}

	function updateCategory()
	{
		error_reporting(E_ALL);
		$id = $_POST['catId'];
		$name = $_POST['catName'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE ace_iv_categories set name ='".$name."' WHERE id=".$id;
		$result = $db->_execute($query);
		$this->redirect("pages/close");
	}

	function save() {

		if($this->data['IvCategory']['active'] != 1) $this->data['IvCategory']['active'] = 0;

		if($this->IvCategory->save($this->data['IvCategory'])) {				

			$this->Session->write("message", $this->data['IvCategory']['name']." was saved.".mysql_error());

			$this->redirect("pages/close");

		} else {

			$this->Session->write("message", $this->data['IvCategory']['name']." was not saved.");

			$this->redirect("pages/close");

		}		

	}

	

	function dropdownAjax($id) {

		$this->layout = 'blank';

		$categories = $this->Lists->IvCategories();

		$this->set('categories', $categories);

		$this->set('value', count($categories));

	}

	

}

?>