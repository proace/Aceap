<?php

class OrderTypeItems extends AppModel
{
	var $name = 'OrderTypeItems';
	var $useTable = 'order_types_items';
	var $validate = array();
	var $belongsTo = array('OrderType' => array('className' 	=> 'OrderType',
		                                     		'conditions'    => '',
		                                     		'order'    		=> '',
													'dependent'		=> false,
		                                     		'foreignKey'   	=> 'order_type_id'
		                  							)
		                  );
}?>
