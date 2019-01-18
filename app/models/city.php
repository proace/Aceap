<?php
class City extends AppModel
{	
	var $useTable = 'cities';
	var $primaryKey = 'internal_id';
	var $name = 'City';

	//Used for data validation purposes
	var $validate = array();
	
}
?>
