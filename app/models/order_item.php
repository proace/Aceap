<?php

class OrderItem extends AppModel
{
	var $name = 'OrderItem';

	var $validate = array();

	var $belongsTo = array(	'ItemCategory' => array('className'    	=> 'ItemCategory',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'		=> false,
							'foreignKey'   	=> 'item_category_id'
						)
				);
}

?>
