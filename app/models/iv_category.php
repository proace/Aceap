<?php

class IvCategory extends AppModel

{	

	var $name = 'IvCategory';

	var $tablePrefix = 'ace_';

	//Used for data validation purposes

	var $validate = array();

	var $belongsTo = array(	'Type' => array(	'className'    	=> 'OrderType',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'    	=>  false,
							'foreignKey'   	=> 'job_type'
							));
	

}

?>