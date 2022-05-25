<?php
class AcUser extends AppModel
{	
	var $name = 'AcUser';
	var $tablePrefix = 'ace_';
	var $hasOne = array(
		'User' => array(
		'className' => 'User',
		'foreignKey' => 'id'
		)
	);
	
	//Used for data validation purposes
	var $validate = array();

}
?>