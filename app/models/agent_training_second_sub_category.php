<?php

class AgentTrainingSecondSubCategory extends AppModel

{   

    var $name = 'AgentTrainingSecondSubCategory';

    // var $tablePrefix = 'ace_';

    //Used for data validation purposes

    var $useTable = 'agent_training_second_sub_categories';


    var $validate = array();

    var $belongsTo = array( 'AgentTrainingSubCategory' => array('className' => 'AgentTrainingSubCategory',
                            'conditions'    => '',
                            'order'         => '',
                            'dependent'     =>  false,
                            'foreignKey'    => 'sub_cat'
                            )
                            );
}

?>