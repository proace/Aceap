<?php

class TruckMap extends AppModel
{
	var $useTable = 'truck_maps';
	var $name = 'TruckMap';

	//Used for data validation purposes
	var $validate = array('truck_id' => VALID_NOT_EMPTY);

    var $belongsTo = array('User' =>
                           array('className'  => 'User',
                                 'conditions' => '',
                                 'foreignKey' => 'user_id'
                           )
                           ,    
    						'InventoryLocation' =>
                           array('className'  => 'InventoryLocation',
                                 'conditions' => '',
                                 'foreignKey' => 'truck_id'
                           ),
							
                     );

}

?>
