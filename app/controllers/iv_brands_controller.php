<?php
class IvBrandsController extends AppController
{
	var $name = 'IvBrands';
	var $tablePrefix = 'ace_iv_'; 
	var $components = array('HtmlAssist', 'Common', 'Lists');
	
	function edit($id = null) {
		$this->layout = 'blank';	
		if (empty($this->data['IvBrand'])) {    
			$this->IvBrand->id = $id;    
			$this->data = $this->IvBrand->read();			
		}
	}
	
	function save() {
		if($this->data['IvBrand']['active'] != 1) $this->data['IvBrand']['active'] = 0;
		if($this->IvBrand->save($this->data['IvBrand'])) {				
			$this->Session->write("message", $this->data['IvBrand']['name']." was saved.".mysql_error());
			$this->redirect("pages/close");
		} else {
			$this->Session->write("message", $this->data['IvBrand']['name']." was not saved.");
			$this->redirect("pages/close");
		}		
	}
	
	function dropdownAjax($id) {
		$this->layout = 'blank';
		$brands = $this->Lists->IvBrands();
		$this->set('brands', $brands);
		$this->set('value', count($brands));
	}		
}
?>