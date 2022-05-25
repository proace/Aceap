<?php

class InventoryState extends AppModel
{
	var $useTable = 'inventory_states';
	var $name = 'InventoryState';

	//Used for data validation purposes
	var $validate = array('location_id' => VALID_NOT_EMPTY, 'item_id' => VALID_NOT_EMPTY,
	'location_id' => VALID_NUMBER, 'item_id' => VALID_NUMBER);

    var $belongsTo = array('InventoryLocation' =>
                           array('className'  => 'InventoryLocation',
                                 'conditions' => '',
                                 'order'      => 'InventoryLocation.name ASC',
                                 'foreignKey' => 'location_id'
                           ),
                           'Item' =>
                           array('className'  => 'Item',
                                 'conditions' => '',
                                 'order'      => 'Item.name ASC',
                                 'foreignKey' => 'item_id'
                           )
                     );

}

?>
