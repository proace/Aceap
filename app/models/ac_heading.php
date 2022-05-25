<?php
class AcHeading extends AppModel
{	
	var $name = 'AcHeading';
	var $tablePrefix = 'ace_';
	var $hasMany = 'AcPage';
	
	//Used for data validation purposes
	var $validate = array();

}
?>