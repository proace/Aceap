<?php

class TrainingSubCategory extends AppModel

{	

	var $name = 'TrainingSubCategory';

	// var $tablePrefix = 'ace_';

	//Used for data validation purposes

	var $useTable = 'training_sub_categories';


	var $validate = array();

	var $belongsTo = array(	'TrainingCategory' => array('className'	=> 'TrainingCategory',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'    	=>  false,
							'foreignKey'   	=> 'cat_id'
							)
							);
	

}

?>