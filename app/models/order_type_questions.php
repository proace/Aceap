<?php

class OrderTypeQuestions extends AppModel
{
	var $name = 'OrderTypeQuestions';
	var $useTable = 'order_types_questions';
	var $validate = array();
	var $belongsTo = array('OrderType' => array('className' 	=> 'OrderType',
		                                     		'conditions'    => '',
		                                     		'order'    		=> '',
													'dependent'		=> false,
		                                     		'foreignKey'   	=> 'order_type_id'
		                  							)
		                  );
}?>