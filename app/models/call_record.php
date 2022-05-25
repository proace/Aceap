<?php

//User Data class
//Table: ace_user_data

class CallRecord extends AppModel
{
	//Model DB Information
	//we set the table to be used manually, since it's not just the plural of User
	var $useTable = 'call_history';

	var $name = 'CallRecord';

	var $sourcesFinderQuery = 'SELECT ';

	//Used for data validation purposes
	var $validate = array();
	
	var $belongsTo = array(	
				'Customer' => array(	
							'className'   	=> 'User',
							'conditions'  	=> '',
							'order'					=> '',
							'foreignKey'		=> 'customer_id'
							),
				'CallResult' => array(
							'className'    	=> 'CallResult',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'    	=>  false,
							'foreignKey'   	=> 'call_result_id'
							),							
				'CallUser' => array(	
							'className'    	=> 'User',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'    	=>  false,
							'foreignKey'   	=> 'call_user_id'
							),
				'CallbackUser' => array(
							'className'    	=> 'User',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'    	=>  false,
							'foreignKey'   	=> 'callback_user_id'
							)
				);
}
?>
