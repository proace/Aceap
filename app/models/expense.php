<?php

class Expense extends AppModel
{
	var $useTable = 'expenses';
	var $name = 'Expense';

	//Used for data validation purposes
	var $validate = array('item_id' => VALID_NOT_EMPTY);

    var $belongsTo = array('Item' =>
                           array('className'  => 'Item',
                                 'conditions' => '',
                                 'order'      => 'Item.name ASC',
                                 'foreignKey' => 'item_id'
                           ),    
    						'InventoryChange' =>
                           array('className'  => 'InventoryChange',
                                 'conditions' => '',
                                 'order'      => 'InventoryChange.id',
                                 'foreignKey' => 'change_id'
                           )
                 );

	function UpdateField($id, $fieldName, $fieldValue)
	{
		if($id && $fieldName && $fieldValue)
		{
			$this->id = $id;
      $this->saveField($fieldName, $fieldValue);
		}
	}
}

?>
