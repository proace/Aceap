<?php
class CyphonController extends AppController
{
	var $name = 'Cyphon';
	var $components = array('HtmlAssist', 'Common', 'Lists');
	
	function index() {
		$this->set('cyphon', $this->Cyphon->findAll());
		$this->set('message', $this->Session->read("message"));
		$this->Session->write("message", "");
	}	
	
	function save() {		
		if($this->data['Cyphon']['delete'] == 1) {
			if($this->Cyphon->delete($this->data['Cyphon']['id'], false)) {
				$this->Session->write("message", $this->data['Cyphon']['name']." was deleted.");
				$this->redirect('/Cyphon/');
			} else {
				$this->Session->write("message", $this->data['Cyphon']['name']." was not deleted.");
				$this->redirect('/Cyphon/');
			}
		} else {
			if($this->data['Cyphon']['active'] != 1) $this->data['Cyphon']['active'] = 0;
			if($this->Cyphon->save($this->data['Cyphon'])) {				
				$this->Session->write("message", $this->data['Cyphon']['name']." was saved.".mysql_error());
				$this->redirect('/Cyphon/');			
			} else {
				$this->Session->write("message", $this->data['Cyphon']['name']." was not saved.");
				$this->redirect('/Cyphon/');
			}
		}
	}
	//$this->redirect($this->referer());
}
?>