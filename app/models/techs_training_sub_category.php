<?php

class TechsTrainingSubCategory extends AppModel

{   

    var $name = 'TechsTrainingSubCategory';

    // var $tablePrefix = 'ace_';

    //Used for data validation purposes

    var $useTable = 'techs_training_sub_categories';


    var $validate = array();

    var $belongsTo = array( 'TechsTrainingCategory' => array('className'    => 'TechsTrainingCategory',
                            'conditions'    => '',
                            'order'         => '',
                            'dependent'     =>  false,
                            'foreignKey'    => 'cat_id'
                            )
                            );
    

}

?>