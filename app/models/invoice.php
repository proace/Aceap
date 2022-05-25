<?php
class Invoice extends AppModel
{	
	var $name = 'Invoice';
	var $hasAndBelongsToMany = array('Question' => array('className' => 'Question' ), 'Answer' => array('className' => 'Answer' ));
	var $hasMany = 'AnswersInvoice';
	
	//Used for data validation purposes
	var $validate = array();
	
}
?>