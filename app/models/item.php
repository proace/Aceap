<?php

class Item extends AppModel
{
	var $name = 'Item';

	//Used for data validation purposes
	var $validate = array(
		'name' => VALID_NOT_EMPTY
		);

	var $hasMany = array('InventoryState' =>
                    array('className'    => 'InventoryState',
                          'conditions'   => '',
                          'order'        => '',
                          'dependent'    =>  true,
                          'foreignKey'   => 'item_id'
                    ),
                    'InventoryChange' =>
                    array('className'    => 'InventoryChange',
                          'conditions'   => '',
                          'order'        => '',
                          'dependent'    =>  true,
                          'foreignKey'   => 'item_id')
              );
			  
	var $belongsTo = array(
		'ItemCategory' => array('className'    	=> 'ItemCategory',
								'conditions'   	=> '',
								'order'        	=> '',
								'dependent'    	=>  false,
								'foreignKey'   	=> 'item_category_id'
		),
		'OrderType' => 	  array('className'    	=> 'OrderType',
								'conditions'   	=> '',
								'order'        	=> '',
								'dependent'    	=>  false,
								'foreignKey'   	=> 'related_order_type_id'
		)
	);
}

?>
