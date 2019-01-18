<?php

class WorkRecord extends AppModel
{
	var $name = 'WorkRecord';

	var $validate = array();

	var $belongsTo = array(	'User' 	=> array(	'className'    	=> 'User',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'    	=>  false,
							'foreignKey'   	=> 'user_id'
						),
				'Order' => array(	'className'    	=> 'Order',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'    	=>  false,
							'foreignKey'   	=> 'order_id'
						)
				);
	
	function UpdateStatus($id=null,$status=null)
	{
	  if ($id && $status) 
    {
        $this->id = $id;
        $this->saveField('paid', $status);
    }
	}

}

?>
