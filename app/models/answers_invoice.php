<?php
class AnswersInvoice extends AppModel
{	
	var $name = 'AnswersInvoice';
	var $belongsTo = array('Answer' => array('className' => 'Answer' ), 'Invoice' => array('className' => 'Invoice' ));
	var $useTable = 'answers_invoices';
	
	//Used for data validation purposes
	var $validate = array();
	
}
?>