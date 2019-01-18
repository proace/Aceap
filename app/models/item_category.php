<?php

class ItemCategory extends AppModel
{
	var $name = 'ItemCategory';

	var $validate = array();
	var $hasMany = array('Item' =>
                         array('className'     => 'Item',
                               'conditions'    => '',
                               'order'         => 'Item.id ASC',
                               'foreignKey'    => 'item_category_id',
                               'dependent'     => false
                         )
                  );

}

?>
