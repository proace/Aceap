<?php
class AcPage extends AppModel
{	
	var $name = 'AcPage';
	var $tablePrefix = 'ace_';
	var $hasMany = 'AcSwitch';
	//Used for data validation purposes
	var $validate = array();
	
}
?>