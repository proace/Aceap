<?php
class Group extends AppModel
{
	var $name = 'Group';
	
	var $hasAndBelongsToMany = array(
        'User' =>
            array(
                'className'              => 'User',
                'joinTable'              => 'groups_users',
                'foreignKey'             => 'user_id',
                'associationForeignKey'  => 'group_id'
            )
    );
}
?>