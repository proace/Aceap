<?php

class IvCategoriesController extends AppController

{

	var $name = 'IvCategories';

	var $tablePrefix = 'ace_iv_'; 
	
	var $uses = array('OrderType','IvCategory', 'IvSubCategory', 'IvMidCategory');

	var $components = array('HtmlAssist', 'Common', 'Lists');

	#Loki: show all item categories
	function showItemCategory()
	{				
		$allCat = $this->IvCategory->findAll();
		$this->set('itemCategories', $allCat);
	}
	function showItemSubCategory()
	{			
		if(isset($_GET['showArchive']) && $_GET['showArchive'] == 1)
		{
			$archive = 1;
			$allSubCat = $this->IvSubCategory->findAll(array("IvSubCategory.is_hide" => 1));	
		} else {
			$archive = 0;
			$allSubCat = $this->IvSubCategory->findAll(array("IvSubCategory.is_hide" => 0));	
		}

		$this->set('itemSubCategories', $allSubCat);
		$this->set('showArchive', $archive);
	}
	function showItemMidCategory()
	{			
		$allMidCat = $this->IvMidCategory->findAll();		
		$this->set('itemMidCategories', $allMidCat);
	}
	function editSubCategory($id)
	{	
		$db =& ConnectionManager::getDataSource('default');	
		$category = array();
		if($id) {
			$query = "SELECT * from ace_iv_sub_categories where id=".$id;
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
		} else {
			$row = array("id"=>"", "name"=>"", "category_id"=>"");
		}
		$query1 = "SELECT * from ace_iv_mid_categories";
			$result1 = $db->_execute($query1);
			$category = array();		
			while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
			{
				foreach($row1 as $k => $v)
				{
				  $category[$row1['id']][$k] = $v;
				}	
			}
		$this->set('job_types', $this->HtmlAssist->table2array($this->OrderType->findAll(array("OrderType.flagactive",1)), 'id', 'name'));
		$this->set('subCategory', $row);
		$this->set('categories', $category);
	}

	function editMidCategory($id)
	{	
		$db =& ConnectionManager::getDataSource('default');	
		$category = array();
		if($id) {
			$query = "SELECT * from ace_iv_mid_categories where id=".$id;
			$result = $db->_execute($query);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
		} else {
			$row = array("id"=>"", "name"=>"", "category_id"=>"");
		}
		$query1 = "SELECT * from ace_iv_categories";
			$result1 = $db->_execute($query1);
			$category = array();		
			while($row1 = mysql_fetch_array($result1, MYSQL_ASSOC))
			{
				foreach($row1 as $k => $v)
				{
				  $category[$row1['id']][$k] = $v;
				}	
			}
		$this->set('midCategory', $row);
		$this->set('categories', $category);
	}

	function addSubCategory($id)
	{	
		$jobType = !empty($this->data['job_type'][0]) ? $this->data['job_type'][0] : 0 ;
		$allCatIds = $_POST['allCatId'];
		$catId = $_POST['catId'];
		$name = $_POST['catName'];
		$subCatId = $_POST['subCatId'];
		$db =& ConnectionManager::getDataSource('default');	
		if(!empty($subCatId) || $subCatId != '')
		{
			$query = "UPDATE ace_iv_sub_categories set name='".$name[0]."',category_id=".$allCatIds[0].", job_type = '".$jobType."' where id=".$subCatId;
			$result = $db->_execute($query);
		} else {
			foreach($name as $key => $val)
			{
				$sort = 0;
				if($val == 'Inactive' || $val == 'inactive')
				{
					$sort = 999;
				}
				$query = "INSERT into ace_iv_sub_categories (name,category_id,sort,job_type) VALUES ('".$val."', ".$allCatIds[$key].",".$sort.", ".$jobType.")";	
				$result = $db->_execute($query);
			}
			
		}
		if($result)
			{
				echo "<script>opener.location.reload();
				window.close();</script>";
		 			exit();
			}
		exit();
	}

	function addMidCategory($id)
	{	
		$allCatIds = $_POST['allCatId'];
		$catId = $_POST['catId'];
		$name = $_POST['catName'];
		$midCatId = $_POST['midCatId'];
		$db =& ConnectionManager::getDataSource('default');	
		if(!empty($midCatId) || $midCatId != '')
		{
			$query = "UPDATE ace_iv_mid_categories set name='".$name[0]."',category_id=".$allCatIds[0]." where id=".$midCatId;
			$result = $db->_execute($query);
		} else {
			foreach($name as $key => $val)
			{
				$sort = 0;
				if($val == 'Inactive' || $val == 'inactive')
				{
					$sort = 999;
				}
				$query = "INSERT into ace_iv_mid_categories (name,category_id,sort) VALUES ('".$val."', ".$allCatIds[$key].",".$sort.")";	
				$result = $db->_execute($query);
			}
			
		}
		if($result)
			{
				echo "<script>opener.location.reload();
				window.close();</script>";
		 			exit();
			}
		exit();
	}

	function deleteSubCategory()
	{
		$data = $_POST['typeIds'];
		$ids = implode(',', $data);
		$db =& ConnectionManager::getDataSource('default');
		$query = "DELETE from  ace_iv_sub_categories WHERE id IN (".$ids.")";
		$result = $db->_execute($query);
		exit();
	}

	function hideSubCategory()
	{
		$data = $_POST['typeIds'];
		$ids = implode(',', $data);
		$db =& ConnectionManager::getDataSource('default');
		$query = "update  ace_iv_sub_categories set is_hide=1 WHERE id IN (".$ids.")";
		$result = $db->_execute($query);
		exit();
	}
	function hideShowSubCategory()
	{
		$id = $_POST['typeId'];
		$show = $_POST['isHide'];
		
		$db =& ConnectionManager::getDataSource('default');
		$query = "update  ace_iv_sub_categories set is_hide=".$show." WHERE id =".$id;
		$result = $db->_execute($query);
		exit();
	}

	function deleteMidCategory()
	{
			$data = $_POST['typeIds'];
			$ids = implode(',', $data);
			$db =& ConnectionManager::getDataSource('default');
			$query = "DELETE from  ace_iv_mid_categories WHERE id IN (".$ids.")";
			$result = $db->_execute($query);
			exit();
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
			$db =& ConnectionManager::getDataSource('default');
			$query = "DELETE from  ace_iv_categories WHERE id IN (".$ids.")";
			$result = $db->_execute($query);
			exit();
	}
	function edit($fromAdd = 0) {
		// error_reporting(E_ALL);
		$this->layout = 'blank';	

		if (empty($this->data['IvCategory'])) {    

			$this->IvCategory->id = $id;    

			$this->data = $this->IvCategory->read();	

			$this->set("fromAdd", $fromAdd);
			$this->set('job_types', $this->HtmlAssist->table2array($this->OrderType->findAll(array("OrderType.flagactive",1)), 'id', 'name'));
		}

	}

	function editCategory($id = null) {
		$this->layout = 'blank';
		if (empty($this->data['IvCategory'])) {    

			$this->IvCategory->id = $id;    

			$cat = $this->IvCategory->read();	
			$this->set('job_types', $this->HtmlAssist->table2array($this->OrderType->findAll(array("OrderType.flagactive",1)), 'id', 'name'));
			$this->set("cat", $cat['IvCategory']);
		}

	}

	function updateCategory()
	{
		$id = $_POST['catId'];
		$name = $_POST['catName'];
		$db =& ConnectionManager::getDataSource('default');
		$query = "UPDATE ace_iv_categories set name ='".$name."' WHERE id=".$id;
		$result = $db->_execute($query);
		exit();
	}

	function save() {
		$fromAdd = $_POST['fromAdd'];
		//if($this->data['IvCategory']['active'] != 1) $this->data['IvCategory']['active'] = 0;
		if($this->IvCategory->save($this->data['IvCategory'])) {				

			if($fromAdd == 1)
			{
				echo "<script>opener.location.reload();
				window.close();</script>";
		 			exit;
			}
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