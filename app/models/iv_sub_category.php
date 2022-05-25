<?php

class IvSubCategory extends AppModel

{	

	var $name = 'IvSubCategory';

	var $tablePrefix = 'ace_';

	//Used for data validation purposes

	var $validate = array();

	var $belongsTo = array(	'Type' => array('className'=> 'OrderType',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'    	=>  false,
							'foreignKey'   	=> 'job_type'
							),

							'IvMidCategory' => array('className'	=> 'IvMidCategory',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'    	=>  false,
							'foreignKey'   	=> 'category_id'
						),
					);
}

?>