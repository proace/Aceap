<?php
class CitiesController extends AppController
{
	var $name = 'Cities';
	var $components = array('HtmlAssist', 'Common', 'Lists');
	
	function index() {
		$this->set('cities', $this->City->findAll());
		$this->set('message', $this->Session->read("message"));
		$this->Session->write("message", "");
	}

	function save() {		
		if($this->data['City']['delete'] == 1) {
			if($this->City->delete($this->data['City']['internal_id'], false)) {
				$this->Session->write("message", $this->data['City']['name']." was deleted.");
				$this->redirect('/cities/');
			} else {
				$this->Session->write("message", $this->data['City']['name']." was not deleted.");
				$this->redirect('/cities/');
			}
		} else {
			if($this->data['City']['active'] != 1) $this->data['City']['active'] = 0;
			if($this->City->save($this->data['City'])) {				
				$this->Session->write("message", $this->data['City']['name']." was saved.".mysql_error());
				$this->redirect('/cities/');			
			} else {
				$this->Session->write("message", $this->data['City']['name']." was not saved.");
				$this->redirect('/cities/');
			}
		}
	}
}
?>