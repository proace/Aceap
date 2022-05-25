<?php

class AdminTrainingSecondSubCategory extends AppModel

{	

	var $name = 'AdminTrainingSecondSubCategory';

	// var $tablePrefix = 'ace_';

	//Used for data validation purposes

	var $useTable = 'admin_training_second_sub_categories';


	var $validate = array();

	var $belongsTo = array(	'AdminTrainingSubCategory' => array('className'	=> 'AdminTrainingSubCategory',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'    	=>  false,
							'foreignKey'   	=> 'sub_cat'
							)
							);
}

?>