<?php

//User Data class
//Table: ace_user_data

class Order extends AppModel
{
	//Model DB Information
	//we set the table to be used manually, since it's not just the plural of User

	//HARDCODE NOTE: There is 1 hardcoded entry on line 68 - SQL query for sources - based on fixed role IDs, table prefix

	var $useTable = 'orders';

	var $name = 'Order';

	var $sourcesFinderQuery = 'SELECT ';

	//Used for data validation purposes
	var $validate = array();

	var $belongsTo = array(	'Status' => array(	'className'    	=> 'OrderStatus',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'    	=>  false,
							'foreignKey'   	=> 'order_status_id'
							),
				'Substatus' => array(	'className'    	=> 'OrderSubstatus',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'    	=>  false,
							'foreignKey'   	=> 'order_substatus_id'
							),
				'Type' => array(	'className'    	=> 'OrderType',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'    	=>  false,
							'foreignKey'   	=> 'order_type_id'
							),
				'PaymentMethod' => array('className'   	=> 'PaymentMethod',
							'conditions'   	=> '',
							'order'        	=> '',
							'dependent'    	=>  false,
							'foreignKey'   	=> 'customer_payment_method_id'
							),
				'Customer' => array(	'className'    	=> 'Customer',
							'conditions'   	=> '',
							'order'		=> '',
							'foreignKey'	=> 'customer_id'
							 ),
				'CustomerUserRole' => array(	'className'    	=> 'Userrole',
							'conditions'   	=> '',
							'order'		=> '',
							'foreignKey'	=> 'customer_id'
							 ),
				'Technician1' => array(	'className'    	=> 'User',
							'conditions'    => '',
							'order'    	=> '',
							'foreignKey'   	=> 'job_technician1_id'
							),
				'Technician2' => array(	'className'    	=> 'User',
							'conditions'    => '',
							'order'    	=> '',
							'foreignKey'   	=> 'job_technician2_id'
							),
				'Telemarketer' => array('className'    	=> 'User',
							'conditions'    => '',
							'order'    	=> '',
							'foreignKey'   	=> 'booking_telemarketer_id'
							),
				'Source' 	=> array('className'   	=> 'User',
							'conditions'    => '',
							'order'    	=> '',
							'finderQuery'	=> 'SELECT ace_rp_users.id, ace_rp_users.first_name, ace_rp_users.last_name 
								FROM ace_rp_users, ace_rp_users_roles 
								WHERE ace_rp_users.id=ace_rp_users_roles.user_id AND 
									(ace_rp_users_roles.role_id=3 OR ace_rp_users_roles.role_id=7)',
							'foreignKey' 	=> 'booking_source_id'
							),
				'OrderPaymentMethod' => array('className'    	=> 'OrderPaymentMethod',
							'conditions'    => '',
							'order'    	=> '',
							'foreignKey'   	=> 'payment_method_type'
							),
				'Supplier' => array('className'    	=> 'Supplier',
							'conditions'    => '',
							'order'    	=> '',
							'foreignKey'   	=> 'app_ordered_supplier_id'
							)

				);

				//Comment by Metodi:
				//Tuk imame sledniqt problem: ako v works tablicata ima dva zapisa:
				// - ediniqt e za tech1 sys negoviqt arrival time
				// - vtoriqt e za source sys negoviqt commission no source_id-to e tech1, 
				// PROBLEM: moje da se slu4i da se vyrne za WorkRecord zapisyt za WorkRecordSource, sy6toto vaji i za Closer.Vaji i za tech2
				// RESOLVE: AND WorkRecord1.start_time > 0		start_time is mandatory when insert records
				//			AND WorkRecord2.start_time > 0		start_time is mandatory when insert records
				//			WorkRecordSource.start_time IS NULL
				//			WorkRecordCloser.start_time IS NULL
				
	var $hasOne = array(	'WorkRecord1' => array('className' 	=> 'WorkRecord',
		                                     	'conditions'    => 'WorkRecord1.user_id=Order.job_technician1_id AND WorkRecord1.start_time > 0',
		                                     	'order'    		=> '',
												'dependent'		=> true,
		                                     	'foreignKey'   	=> 'order_id'
		                               ),
							'WorkRecord2' => array('className' 	=> 'WorkRecord',
		                                     	'conditions'    => 'WorkRecord2.user_id=Order.job_technician2_id AND WorkRecord1.start_time > 0',
		                                     	'order'    	=> '',
												'dependent'		=> true,
		                                     	'foreignKey'   	=> 'order_id'
		                               ),
							'WorkRecordSource' => array('className' 	=> 'WorkRecord',
		                                     	'conditions'    => 'WorkRecordSource.user_id=Order.booking_source_id AND WorkRecordSource.start_time IS NULL',
		                                     	'order'    	=> '',
												'dependent'		=> true,
		                                     	'foreignKey'   	=> 'order_id'
		                               ),
							'WorkRecordCloser' => array('className' 	=> 'WorkRecord',
		                                     	'conditions'    => 'WorkRecordCloser.user_id=Order.booking_closer_id AND WorkRecordCloser.start_time IS NULL',
		                                     	'order'    	=> '',
												'dependent'		=> true,
		                                     	'foreignKey'   	=> 'order_id'
		                               ),

							'Payment' => array('className' 	=> 'Payment',
		                                     	'conditions'    => '',
		                                     	'order'    	=> '',
												'dependent'		=> false,
		                                     	'foreignKey'   	=> 'idOrder'
		                               ),

							'Invoice'
				);


	var $hasMany = array(	'BookingItem' => array(	'className' 	=> 'OrderItem',
													'dependent'		=> true,
													'order'    	=> 'id ASC',
													'foreignKey'	=> 'order_id'
						),
							'BookingCoupon' => array(	'className' 	=> 'OrderCoupon',
													'dependent'		=> true,
													'foreignKey'	=> 'order_id'
									),
							'OrdersQuestions' => array(	'className' 	=> 'OrdersQuestions',
													'conditions'	=> '',
													'dependent'		=> true,
													'foreignKey'	=> 'order_id'
									),
							'InstallationItem' => array('className' 	=> 'OrderInstallationItem',
													'dependent'		=> true,
													'order'    	=> 'id ASC',
													'foreignKey'	=> 'order_id'
							),
							'CreditcardPaymentDetails' => array('className'    	=> 'CreditcardPaymentDetails',
																'conditions'    => '',
																'order'    	=> '',
																'foreignKey'   	=> 'order_id'
							)
				);

	 

	
  	// Method creates a log record for the coming change of data
  	// and filled up some dates/times which content depends on the
  	// other field values
	// Created: Anthony Chernikov, 06/08/2010
	function beforeSave() {
		$prev = array();
		$changed_fields = '';
		$id = $this->data['Order']['id'];
		
		if ($this->data['Customer']['phone'])
			$this->data['Order']['customer_phone'] = $this->data['Customer']['phone'];
		
		if(!empty($id))
		{
			$db =& ConnectionManager::getDataSource($this->useDbConfig);
			
			// Search for the previous version of this record
			$query = "select * from ace_rp_orders where id=" .$id;
			$result = $db->_execute($query);
			if ($row = mysql_fetch_array($result, MYSQL_ASSOC)) 
			{
				if (0+$row['verified_by_id']!=$this->data['Order']['verified_by_id'])
					$this->data['Order']['verified_date']=date('Y/m/d H:i:s');
			}

			// Add a changes log record
			$query = "insert into ace_rp_orders_log
					(id, branch, order_status_id, order_type_id,
					 order_substatus_id, order_number,
					 
					 booking_date, booking_telemarketer_id, booking_closer_id,
					 booking_source_id, booking_source2_id,
					 verified_by_id, verified_date,
					 sSpokeTo, sCancelReason,  nRebooked,
					 notified_booking, adjustment_note, 
					 
					 job_date, job_time_beg, job_time_end, job_postal_code,
					 job_truck, job_route, job_technician1_id, job_technician2_id,
					 job_notes_technician, job_notes_office,
					 job_reference_id, job_estimate_id, 
					 
					 customer_id, customer_payment_method_id, 
					 customer_paid_amount, customer_deposit,
					 card_number, customer_desired_payment_method_id,
					 
					 feedback_callback_date, feedback_price, feedback_comment, feedback_suggestion, 
					 
					 app_ordered_date, app_ordered_by, app_ordered_supplier_id,
					 app_ordered_pickup_date, app_po_number, app_ordered_pickup_person,
					 
					 permit_applied_date, permit_applied_user,
					 permit_applied_method, permit_result, permit_number,
					 
					 change_user_id, change_date, change_time, opercode,estimator,payment_method_type) 
				select t.id, t.branch, t.order_status_id, t.order_substatus_id,
				   t.order_type_id, t.order_number,
				
				   t.booking_date, t.booking_telemarketer_id, t.booking_closer_id,
					 t.booking_source_id, t.booking_source2_id,
					 t.verified_by_id, t.verified_date,
					 t.sSpokeTo, t.sCancelReason, t.nRebooked, 
					 t.notified_booking, t.adjustment_note, 
						
					 t.job_date, t.job_time_beg, t.job_time_end, t.job_postal_code,
					 t.job_truck, t.job_route, t.job_technician1_id, t.job_technician2_id,
					 t.job_notes_technician, t.job_notes_office,
					 t.job_reference_id, t.job_estimate_id, 
					 
					 t.customer_id, t.customer_payment_method_id, 
					 t.customer_paid_amount, t.customer_deposit,
					 t.card_number, t.customer_desired_payment_method_id,
					 
					 t.feedback_callback_date, t.feedback_price, t.feedback_comment, t.feedback_suggestion, 
					
					 t.app_ordered_date, t.app_ordered_by, t.app_ordered_supplier_id,
					 t.app_ordered_pickup_date, t.app_po_number, t.app_ordered_pickup_person,
					 
					 t.permit_applied_date, t.permit_applied_user,
					 t.permit_applied_method, t.permit_result, t.permit_number,
					 
					 " .$_SESSION['user']['id'] .", now(), current_time(), 2,t.estimator, t.payment_method_type
				from ace_rp_orders t where t.id=".$id;
			$db->_execute($query);
		}
		return true;
	}
}

?>
