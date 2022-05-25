<?php

class AgentTrainingSubCategory extends AppModel

{   

    var $name = 'AgentTrainingSubCategory';

    // var $tablePrefix = 'ace_';

    //Used for data validation purposes

    var $useTable = 'agent_training_sub_categories';


    var $validate = array();

    var $belongsTo = array( 'AgentTrainingCategory' => array('className'    => 'AgentTrainingCategory',
                            'conditions'    => '',
                            'order'         => '',
                            'dependent'     =>  false,
                            'foreignKey'    => 'cat_id'
                            )
                            );
    

}

?>