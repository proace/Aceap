<?php
class IvSuppliersController extends AppController
{
	var $name = 'IvSuppliers';
	var $tablePrefix = 'ace_iv_'; 
	var $components = array('HtmlAssist', 'Common', 'Lists');
	
	function edit($id = null) {
		$this->layout = 'blank';	
		if (empty($this->data['IvSupplier'])) {    
			$this->IvSupplier->id = $id;    
			$this->data = $this->IvSupplier->read();			
		}
	}
	
	function save() {
		if($this->data['IvSupplier']['active'] != 1) $this->data['IvSupplier']['active'] = 0;
		if($this->IvSupplier->save($this->data['IvSupplier'])) {				
			$this->Session->write("message", $this->data['IvSupplier']['name']." was saved.".mysql_error());
			$this->redirect("pages/close");
		} else {
			$this->Session->write("message", $this->data['IvSupplier']['name']." was not saved.");
			$this->redirect("pages/close");
		}		
	}
	
	function dropdownAjax($id) {
		$this->layout = 'blank';
		$suppliers = $this->Lists->IvSuppliers();
		$this->set('suppliers', $suppliers);
		$this->set('value', count($suppliers));
	}		
		
}
?>