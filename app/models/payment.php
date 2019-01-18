<?php

class Payment extends AppModel
{
	var $name = 'Payment';
	var $useTable = 'payments';
	
	var $belongsTo = array('User' => array('className' 	=> 'User',
		'conditions'    => '',
		'order'    	=> '',
		'dependent' => true,
		'foreignKey'   	=> 'creator'
	));
}

?>
