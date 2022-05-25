<?php
class Answer extends AppModel
{	
	var $name = 'Answer';
	var $hasAndBelongsToMany = 'Invoice';
	var $belongsTo = 'Question';
	var $hasMany = 'AnswersInvoice';
	
	//Used for data validation purposes
	var $validate = array();
	
}
?>