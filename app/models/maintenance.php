<?php
class Maintenance extends AppModel
{	
	var $useTable = 'maintenance';
	var $name = 'Maintenance';
	var $components = array('HtmlAssist', 'Common', 'Lists');

	//Used for data validation purposes
	var $validate = array();
	
}
?>