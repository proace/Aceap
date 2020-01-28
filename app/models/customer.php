<?php
//Customer Data class
class Customer extends AppModel
{
	//Model DB Information
	//we set the table to be used manually, since it's not just the plural of User
	var $useTable = 'customers';

	var $name = 'Customer';
	var $validate = array();
	var $hasMany = array(
							'PartImages' => array(	'className' 	=> 'UserPartImages',
													'conditions'	=> '',
													'dependent'		=> true,
													'foreignKey'	=> 'customer_id'
							),
							'CustomerAddresses' => array(	'className' 	=> 'CustomerAddresses',
													'conditions'	=> '',
													'dependent'		=> true,
													'foreignKey'	=> 'customer_id'
							)
				);

  	// Method creates a log record for the coming change of data
	// Created: Anthony Chernikov, 06/08/2010
	/*function beforeSave() {
		$id = $this->data['Customer']['id'];
		if(!empty($id))
		{
			$db =& ConnectionManager::getDataSource($this->useDbConfig);
			$query = "insert into ace_rp_customers_log
					(id, card_number, first_name, last_name, postal_code, email, address,
					 city, state, phone, cell_phone, credit, username, password,
					 telemarketer_id, callback_date, callback_time,
					 lastcall_date, callback_note, callresult, is_active, role_id,
					 change_user_id, change_date, change_time, opercode) 
				select t.id, t.card_number, t.first_name, t.last_name, t.postal_code, t.email, t.address,
					 t.city, t.state, t.phone, t.cell_phone, t.credit, t.username, t.password,
					 t.telemarketer_id, t.callback_date, t.callback_time,
					 t.lastcall_date, t.callback_note, t.callresult, t.is_active, t.role_id,
					 " .$_SESSION['user']['id'] .", now(), current_time(), 2
				from ace_rp_users t where t.id=".$id;
			$db->_execute($query);
		}
		return true;
	}*/
}
?>
