<?php

class IvMidCategory extends AppModel

{	

	var $name = 'IvMidCategory';

	var $tablePrefix = 'ace_';

	//Used for data validation purposes

	var $validate = array();

	var $belongsTo = array(	'IvCategory' => array('className'	=> 'IvCategory',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'    	=>  false,
							'foreignKey'   	=> 'category_id'
							)

							);
	

}

?>