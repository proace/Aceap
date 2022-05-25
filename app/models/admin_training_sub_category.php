<?php

class AdminTrainingSubCategory extends AppModel

{	

	var $name = 'AdminTrainingSubCategory';

	// var $tablePrefix = 'ace_';

	//Used for data validation purposes

	var $useTable = 'admin_training_sub_categories';


	var $validate = array();

	var $belongsTo = array(	'AdminTrainingCategory' => array('className'	=> 'AdminTrainingCategory',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'    	=>  false,
							'foreignKey'   	=> 'cat_id'
							)
							);
	

}

?>