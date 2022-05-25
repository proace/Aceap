<?php

class Coupon extends AppModel
{
	var $name = 'Coupon';
	var $useTable = 'coupons';
	
	var $belongsTo = array('Item' => array('className' 	=> 'Item',
		                                     		'conditions'    => '',
		                                     		'order'    	=> '',
		                                     		'foreignKey'   	=> 'order_item_id'
		                  											)
		                  );
}

?>
