<?php

class ItemStatus extends AppModel
{
	var $name = 'ItemStatus';
	var $useTable = 'item_status';
	

	var $belongsTo = array(	'Technician' => array('className' 	=> 'User',
		                                    'conditions'    => '',
		                                    'order'    		=> '',
							 'dependent'		=> false,
		                                    'foreignKey'   	=> 'technician_id'
		                               ),
				'Truck' => array(	'className' 	=> 'InventoryLocation',
		                                  	'conditions'    => '',
		                                   'order'    	=> '',
							'dependent'		=> false,
		                                   'foreignKey'   	=> 'truck_id'
		                               )
		    			                    
				);
}

?>
