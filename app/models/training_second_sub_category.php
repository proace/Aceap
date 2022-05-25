<?php

class TrainingSecondSubCategory extends AppModel

{	

	var $name = 'TrainingSecondSubCategory';

	// var $tablePrefix = 'ace_';

	//Used for data validation purposes

	var $useTable = 'training_second_sub_categories';


	var $validate = array();

	var $belongsTo = array(	'TrainingSubCategory' => array('className'	=> 'TrainingSubCategory',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'    	=>  false,
							'foreignKey'   	=> 'sub_cat'
							)
							);
}

?>