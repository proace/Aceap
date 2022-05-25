<?php

class InventoryLocation extends AppModel
{
	var $useTable = 'inventory_locations';
	var $name = 'InventoryLocation';

	//Used for data validation purposes
	var $validate = array('name' => VALID_NOT_EMPTY);
			
	var $hasMany = array('InventoryState' =>
                    array('className'    => 'InventoryState',
                          'conditions'   => '',
                          'order'        => '',
                          'dependent'    =>  true,
                          'foreignKey'   => 'location_id'
                    ),
					'InventoryChange' =>
                    array('className'    => 'InventoryChange',
                          'conditions'   => '',
                          'order'        => '',
                          'dependent'    =>  true,
                          'foreignKey'   => 'source_id'
                    ),
                    'InventoryChange' =>
                    array('className'    => 'InventoryChange',
                          'conditions'   => '',
                          'order'        => '',
                          'dependent'    =>  true,
                          'foreignKey'   => 'destination_id'
                    )                               
              );
}

?>
