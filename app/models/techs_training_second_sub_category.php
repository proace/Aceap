<?php

class TechsTrainingSecondSubCategory extends AppModel

{   

    var $name = 'TechsTrainingSecondSubCategory';

    // var $tablePrefix = 'ace_';

    //Used for data validation purposes

    var $useTable = 'techs_training_second_sub_categories';


    var $validate = array();

    var $belongsTo = array( 'TechsTrainingSubCategory' => array('className' => 'TechsTrainingSubCategory',
                            'conditions'    => '',
                            'order'         => '',
                            'dependent'     =>  false,
                            'foreignKey'    => 'sub_cat'
                            )
                            );
}

?>