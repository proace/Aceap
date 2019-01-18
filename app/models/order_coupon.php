<?php

class OrderCoupon extends AppModel
{
	var $name = 'OrderCoupon';
	var $useTable = 'order_coupons';
	
	var $belongsTo = array('Order' => array('className' 	=> 'Order',
		                                     		'conditions'    => '',
		                                     		'order'    		=> '',
													'dependent'		=> false,
		                                     		'foreignKey'   	=> 'order_id'
		                  							)
		                  );
}

?>
