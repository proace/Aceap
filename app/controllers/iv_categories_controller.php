<?php
class IvCategoriesController extends AppController
{
	var $name = 'IvCategories';
	var $tablePrefix = 'ace_iv_'; 
	var $components = array('HtmlAssist', 'Common', 'Lists');
	
	function edit($id = null) {
		$this->layout = 'blank';	
		if (empty($this->data['IvCategory'])) {    
			$this->IvCategory->id = $id;    
			$this->data = $this->IvCategory->read();			
		}
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