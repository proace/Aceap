<?php
class Question extends AppModel
{	
	var $name = 'Question';
	var $hasAndBelongsToMany = 'Invoice';
	var $hasMany = 'Answer';
	
	//Used for data validation purposes
	var $validate = array();
	
}
?>