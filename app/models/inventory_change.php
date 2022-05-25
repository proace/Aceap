<?php

class InventoryChange extends AppModel
{
	var $useTable = 'inventory_changes';
	var $name = 'InventoryChange';

	//Used for data validation purposes
	var $validate = array('item_id' => VALID_NOT_EMPTY);

    var $belongsTo = array('Item' =>
                           array('className'  => 'Item',
                                 'conditions' => '',
                                 'order'      => 'Item.name ASC',
                                 'foreignKey' => 'item_id'
                           )
                           ,    
    						'InventoryLocationSource' =>
                           array('className'  => 'InventoryLocation',
                                 'conditions' => '',
                                 'order'      => 'InventoryLocationSource.name ASC',
                                 'foreignKey' => 'source_id'
                           ),
							'InventoryLocationDestination' =>
                           array('className'  => 'InventoryLocation',
                                 'conditions' => '',
                                 'order'      => 'InventoryLocationDestination.name ASC',
                                 'foreignKey' => 'destination_id'
                           )
                     );

}

?>
