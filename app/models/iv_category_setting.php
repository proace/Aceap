<?php
class IvCategorySetting extends AppModel
{	
	var $name = 'IvCategorySetting';
	var $belongsTo = array('IvCategory');
	var $tablePrefix = 'ace_';		
	//Used for data validation purposes
	var $validate = array();
	
}
?>