<?php

class Payroll extends AppModel
{
	var $name = 'Payroll';

	//Used for data validation purposes
	var $validate = array('user_id' => VALID_NOT_EMPTY,
		'commision' => VALID_NOT_EMPTY,
		'hours' => VALID_NOT_EMPTY,
		'rate' => VALID_NOT_EMPTY,
		'bonus' => VALID_NOT_EMPTY,
		'total' => VALID_NOT_EMPTY
		);

	var $belongsTo = array(	'User' 	=> array(	'User' => 'User',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'    	=>  false,
							'foreignKey'   	=> 'user_id')
							);
						
/*  var $hasAndBelongsToMany = array('Role' =>
                             array('className'    => 'Role',
                                   'joinTable'    => 'users_roles',
                                   'foreignKey'   => 'User.user_id',
                                   'associationForeignKey'=> 'role_id',
                                   'conditions'   => '',
                                   'order'        => '',
                                   'limit'        => '',
                                   'unique'       => true,
                                   'finderQuery'  => '',
                                   'deleteQuery'  => '',
                             )
                             );  */       
}

?>
