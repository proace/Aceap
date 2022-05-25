<?php
class IvItem extends AppModel
{	
	var $name = 'IvItem';
	var $belongsTo = array('IvBrand', 'IvCategory', 'IvSupplier');
	var $tablePrefix = 'ace_';		
	//Used for data validation purposes
	var $validate = array();
	
}
?>