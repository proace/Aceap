<?
error_reporting(E_PARSE ^ E_ERROR );
//error_reporting(2047);

class RejectController extends AppController
{
	//To avoid possible PHP4 problemfss
	var $name = "OrdersController";

	var $uses = array('Order', 'CallRecord', 'User', 'Customer', 'OrderItem',
                    'Timeslot', 'OrderStatus', 'OrderType', 'Item',
                    'Zone','PaymentMethod','ItemCategory','InventoryLocation',
					'OrderSubstatus','Coupon','Setting','CallResult','Invoice', 'Question', 'Payment', 'Invoice');

	var $helpers = array('Common');
	var $components = array('HtmlAssist', 'Common', 'Lists');
	var $itemsToShow = 20;
	var $pagesToDisplay = 10;

	var $member_card1_item_id=1106; //Added by Maxim Kudryavtsev - for booking member cards
	var $member_card2_item_id=1107; //Added by Maxim Kudryavtsev - for booking member cards

	var $beforeFilter = array('checkAccess');


function reject()
	{ 
		$customer_id = 14052;
		$order_id = 69874;
		$phone = 6046124905;

		if ($this->Common->getLoggedUserRoleID() != "1") $method = "editBooking"; else $method = "techBooking";
		$allStatuses = $this->Lists->ListTable('ace_rp_order_statuses');
		$allJobTypes = $this->Lists->ListTable('ace_rp_order_types');

		echo '<table class="historytable">';
		echo '<tr cellpadding="10">';
		echo '<th>Date</th><th>Booking</th><th>Status</th><th>Tech</th>';
		if ($this->Common->getLoggedUserRoleID() == 6) echo '<th>Feedback</th>';
		echo '</tr>';
		echo "<tr><td colspan=8 style=\"background: #AAAAAA; height: 5px;\"></td></tr>\n";

		if ($phone)
		{
			$sq_str = preg_replace("/[- \.]/", "", $phone);
			$sq_str = preg_replace("/([?])*/", "[-]*", $phone);
//	    $past_orders = $this->Order->findAll(array('Order.customer_id'=> $customer_id), null, "job_date DESC", null, null, 1);
//			$past_orders = $this->Order->findAll(array('Customer.phone'=> $phone), null, "job_date DESC", null, null, 1);
//	      $past_orders = $this->Order->findAll(array('Order.customer_phone'=> $phone), null, "job_date DESC", null, null, 1);
			$past_orders = array();
			$db =& ConnectionManager::getDataSource('default');
			$query = "select * from ace_rp_orders where customer_phone regexp '$sq_str' order by job_date DESC";

			$result = $db->_execute($query);
			
			
			
			
			while($row = mysql_fetch_array($result))
				$past_orders[$row['id']] = $row['id'];
          
			foreach ($past_orders as $cur)
			{   
				$p_order = $this->Order->findAll(array('Order.id'=> $cur), null, "job_date DESC", null, null, 1);

				$p_order = $p_order[0];
				
				echo '<pre>';print_r($p_order);
				
				
			
				
				if ($p_order['Order']['id'] == $order_id)
					$add = "style=\"background: #FFFF99;\"";
				else
				{
					if ((($this->Common->getLoggedUserRoleID() != 3)
					   &&($this->Common->getLoggedUserRoleID() != 9)
					   &&($this->Common->getLoggedUserRoleID() != 1))
						||($this->Common->getLoggedUserID()==$p_order['Order']['booking_telemarketer_id']))
						$add = " style=\"cursor: hand; cursor: pointer;\" onclick=\"location.href='./".$method."?order_id=".$p_order['Order']['id']."';\"";
					else
						$add = "";
				}

				$items_text='';
				$total_booked=0;
				$total_extra=0;
				foreach ($p_order['BookingItem'] as $oi)
				{
					$str_sum = round($oi['quantity']*$oi['price'],2);
					if ($oi['class']==0)
					{
						$text = 'booked';
						$total_booked += 0+$str_sum-$oi['discount']+$oi['addition'];
					}
					else
					{
						$text = 'provided by tech';
						$total_extra += 0+$str_sum-$oi['discount']+$oi['addition'];
					}

					if ((($this->Common->getLoggedUserRoleID() != 3)
					  &&($this->Common->getLoggedUserRoleID() != 9))
					  ||($oi['class']==0))
					{
						$items_text .= '<tr>';
						$items_text .= '<td>'.$text.'</td>';
						$items_text .= '<td style="width:200px">'.$oi['name'].'</td>';
						$items_text .= '<td>'.$oi['quantity'].'</td>';
						$items_text .= '<td>'.$this->HtmlAssist->prPrice($oi['price']).'</td>';
						$items_text .= '<td>'.$this->HtmlAssist->prPrice($oi['addition']-$oi['discount']).'</td>';
						//$items_text .= '<td>'.$this->HtmlAssist->prPrice($str_sum).'</td>';
						$items_text .= '</tr>';
					}
				}
				foreach ($p_order['BookingCoupon'] as $oi)
				{
					$str_sum = 0-$oi['price'];
					if ($oi['class']==0)
					{
					  $text = 'booked';
					  $total_booked += 0+$str_sum;
					}
					else
					{
					  $text = 'provided by tech';
					  $total_extra += 0+$str_sum;
					}

					if ((($this->Common->getLoggedUserRoleID() != 3)
						&&($this->Common->getLoggedUserRoleID() != 9))
						||($oi['class']==0))
					{
					  $items_text .= '<tr>';
					  $items_text .= '<td>'.$text.'</td>';
					  $items_text .= '<td style="width:200px">'.$oi['name'].'</td>';
					  $items_text .= '<td>&nbsp;</td>';
					  $items_text .= '<td>'.$this->HtmlAssist->prPrice($str_sum).'</td>';
					  $items_text .= '<td>&nbsp;</td>';
					  //$items_text .= '<td>'.$this->HtmlAssist->prPrice($str_sum).'</td>';
					  $items_text .= '</tr>';
					}
				}

	          echo "<tr class='orderline' valign='top' ".$add." >";
	          echo "<td rowspan=1>".date('d-m-Y', strtotime($p_order['Order']['job_date']))."<br>REF#".$p_order['Order']['order_number']."</td>";
	          echo "<td rowspan=1>".$this->HtmlAssist->prPrice($total_booked)."</td>";
	          //echo "<td rowspan=1>".$this->HtmlAssist->prPrice($p_order['Order']['customer_paid_amount'])."</td>";
            $status = $p_order['Order']['order_status_id'];
            $color="";
            if (($status == 3)||($status == 2)) $color="color:red";
            if ($status == 5) $color="color:green";
	          echo "<td><b style='".$color."'>".$allStatuses[$status]."</b><br/>";
	          echo $allJobTypes[$p_order['Order']['order_type_id']]."</td>";
	          echo "<td>".$p_order['Technician1']['first_name']."<br/>"
	                    .$p_order['Technician2']['first_name']."</td>";
						if ($this->Common->getLoggedUserRoleID() == 6)
							echo "<td rowspan=2><a style='text-decoration:none;color:black;' href='".BASE_URL."/orders/feedbacks_add?id=". $p_order['Order']['id']."'><b>".$p_order['Order']['feedback_quality']."</b><br/>".
												"<b>Notes</b>: ".$p_order['Order']['feedback_comment']."<br/>".
												"<b>Solution</b>: ".$p_order['Order']['feedback_suggestion']."</a></td>";
	          echo "</tr>\n";
	          echo "<tr valign='top' ".$add." >";
	          echo "<td colspan=4>";
						echo '<table cellspacing=0 colspacing=5>';
						//echo '<tr><th>&nbsp;</th><th align=left style="width:200px">Item</th><th>Qty</th><th>Price</th><th>Sum</th></tr>';
						echo '<tr><th>&nbsp;</th><th align=left style="width:200px">Item</th><th>Qty</th><th>Price</th><th>Adj</th></tr>';
            echo $items_text;
	          echo '</table>';
	          echo "</td>";
	          echo "</tr>\n";
	          echo "<tr><td colspan=8 style=\"background: #AAAAAA; height: 5px;\"></td></tr>\n";
	        }
	    }

	    echo "</table>";
          
	}
    
   function invoiceTabletPrint() {
   //acesys/index.php/orders/invoiceTabletPrint?order_id=110689&amp;type=office

		$this->layout = "blank";

		//$order_id = $_GET['order_id'];
         $_GET['order_id']=118504;
         $_GET['type']='office';
        $order_id = $_GET['order_id'];
		

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		if(isset($_GET['type']) && $_GET['type'] == 'office') {
			//do nothing
		} else {
			if($this->_needsApproval($order_id))
				$this->redirect("orders/invoiceTabletStandby?order_id=$order_id");

			$result = $db->_execute("
				UPDATE ace_rp_orders
				SET order_status_id = 5
				WHERE id = $order_id
				AND order_status_id != 8
			");

			$result = $db->_execute("
				UPDATE ace_rp_orders
				SET order_status_id = 1
				WHERE id = $order_id
				AND order_status_id = 8
				AND job_date IS NOT NULL
			");

			$this->_saveQuestionsAsFinal($order_id);
		}

		$query = "
			SELECT *
			FROM ace_rp_settings
			WHERE id IN(21)
		";

		$result = $db->_execute($query);

		while($row = mysql_fetch_array($result)) {
			$use_template_questions = $row['valuetxt'];
		}

		if(isset($use_template_questions) && $use_template_questions == 0) {
			if($this->Common->getLoggedUserRoleID() != 1)
			$this->redirect("orders/invoiceTabletPrintOld?order_id=$order_id&type=office");
			else
			$this->redirect("orders/invoiceTabletPrintOld?order_id=$order_id");
		}

		$conditions = array();

		$conditions += array('`Order`.`id`' => $order_id);

		$allQuestions = array();

		$allStatuses = $this->Lists->ListTable('ace_rp_order_statuses');
		$allJobTypes = $this->Lists->ListTable('ace_rp_order_types');




		// UNCOMMENT ON LIVE
		$conditions += array('order_status_id' => array(1, 5, 8));

		//$orders = $this->Order->findAll($conditions, null, "job_truck ASC", null, null, 1);
		$orders = $this->Order->findAll($conditions, null, array("job_truck ASC", "job_time_beg ASC"), null, null, 1);

echo '<pre>';print_r($orders);


		// Customer's history for followup or complaints
		$num = 0;

		$notes = array();

		foreach ($orders as $obj)
		{
			if (($obj['Type']['id']==9)||($obj['Type']['id']==10))
			{
				$sRes = '';
				$order_id = $obj['Order']['id'];
				$phone = $obj['Customer']['phone'];

				$sRes .= '<table width=100% class="history">';
				$sRes .= '<tr>';
				$sRes .= '<th>Date</th><th>Booking</th><th>Status</th><th>Tech</th>';
				$sRes .= '</tr>';

				if ($phone)
				{

					$sq_str = preg_replace("/[- \.]/", "", $phone);
					$sq_str = preg_replace("/([?])*/", "[-]*", $phone);
					$past_orders = array();
					$query = "select * from ace_rp_orders where customer_phone regexp '$sq_str' order by job_date DESC";

					$result = $db->_execute($query);
					while($row = mysql_fetch_array($result))
						$past_orders[$row['id']] = $row['id'];

					foreach ($past_orders as $cur)
					{
						$p_order = $this->Order->findAll(array('Order.id'=> $cur), null, "job_date DESC", null, null, 1);
						$p_order = $p_order[0];
						if ($p_order['Order']['id'] == $order_id) continue;

						$items_text='';
						$total_booked=0;
						$total_extra=0;

						foreach ($p_order['BookingItem'] as $oi)
						{
							$str_sum = round($oi['quantity']*$oi['price']-$oi['discount']+$oi['addition'],2);
							if ($oi['class']==0)
							{
								$text = 'booked';
								$total_booked += 0+$str_sum;
							}
							else
							{
								$text = 'provided by tech';
								$total_extra += 0+$str_sum;
							}

							$items_text .= '<tr>';
							$items_text .= '<td>'.$text.'</td>';
							$items_text .= '<td>'.$oi['name'].'</td>';
							$items_text .= '<td>'.$oi['quantity'].'</td>';
							$items_text .= '<td>'.$this->HtmlAssist->prPrice($oi['price']).'</td>';
							$items_text .= '<td>'.$this->HtmlAssist->prPrice($oi['addition']-$oi['discount']).'</td>';
							$items_text .= '</tr>';
						}

						$sRes .= "<tr class='orderline' valign='top' ".$add." >";
						$sRes .= "<td rowspan=1>".date('d-m-Y', strtotime($p_order['Order']['job_date']))."<br>REF#".$p_order['Order']['order_number']."</td>";
						$sRes .= "<td rowspan=1>".$this->HtmlAssist->prPrice($total_booked)."</td>";
						$status = $p_order['Order']['order_status_id'];
						$color="";
						$sRes .= "<td><b>".$allStatuses[$status]."</b><br/>";
						$sRes .= $allJobTypes[$p_order['Order']['order_type_id']]."</td>";
						$sRes .= "<td>".$p_order['Technician1']['first_name']."<br/>"
								  .$p_order['Technician2']['first_name']."</td>";
						$sRes .= "</tr>\n";
						$sRes .= "<tr valign='top'>";
						$sRes .= "<td colspan=4 style='border-bottom: 1px solid #AAAAAA;'>";
						$sRes .= '<table>';
						$sRes .= '<tr><th style="width:100px !important;">&nbsp;</th>';
						$sRes .= '<th style="text-align:left;width:250px !important;">Item</th>';
						$sRes .= '<th style="text-align:left;width:80px !important;">Qty</th>';
						$sRes .= '<th style="text-align:left;width:100px !important;">Price</th>';
						$sRes .= '<th style="text-align:left;">Adj</th></tr>';
						$sRes .= $items_text;
						$sRes .= '</table>';
						$sRes .= "</td>";
						$sRes .= "</tr>\n";
					}

					$sRes .= "</table>";
				}

				$orders[$num]['Order']['history']  = $sRes;
			}
			$num++;

			$order_id = $obj['Order']['id'];
			$order_type_id = $obj['Order']['order_type_id'];

			if(isset($order_id) || $order_id != 0) {
				$query = "
					SELECT n.*, nt.name note_type_name,
						ur.name urgency_name,
						CONCAT(u.first_name, ' ', u.last_name) author_name,
						ur.image_file
					FROM ace_rp_notes n
					LEFT JOIN ace_rp_note_types nt
					ON n.note_type_id = nt.id
					LEFT JOIN ace_rp_urgencies ur
					ON n.urgency_id = ur.id
					LEFT JOIN ace_rp_users u
					ON n.user_id = u.id
					WHERE n.order_id = $order_id
					AND ur.id = 1
					ORDER BY n.note_date DESC
					LIMIT 2
				";


				$result = $db->_execute($query);

				$temp = "";
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$temp .= $row['message']."&laquo;";
				}

				$notes[$order_id] = $temp;

			} //END retrieve notes

			$result = $db->_execute($query);

			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$values[$row['question_id']]['answer_id'] = $row['answer_id'];
				$values[$row['question_id']]['answer_text'] = $row['answer_text'];
				$values[$row['question_id']]['question_text'] = $row['question_text'];
			}

			$this->set('order_id', $order_id);
			//$this->set('questions', $questions);
			//$this->set('answers', $answers);
			$this->set('values', $values);

			//END questions
			}

		$query = "
			SELECT id, CONCAT(raw, b) hk
			FROM (SELECT id,
				RIGHT(CONCAT('ABCDEFGH', id), 8) raw,
				HEX(RIGHT(id, 1)) b
				FROM ace_rp_customers) u
			WHERE raw IS NOT NULL
			ORDER BY id
		";

		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$history_keys[$row['id']]= $row['hk'];
    	}

		$query = "
			SELECT qw.question_id, q.rank, q.value question, r.value response, qw.response_text, s.value suggestion, d.value decision
			FROM ace_rp_orders_questions_working qw
			LEFT JOIN ace_rp_questions q
			ON qw.question_id = q.id
			LEFT JOIN ace_rp_responses r
			ON qw.response_id = r.id
			LEFT JOIN ace_rp_suggestions s
			ON qw.suggestion_id = s.id
			LEFT JOIN ace_rp_decisions d
			ON qw.decision_id = d.id
			WHERE q.for_print = 1
			AND qw.order_id = $order_id
			ORDER BY q.rank
		";

		$result = $db->_execute($query);

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$working_answers[$row['question_id']]['question'] = $row['question'];
			$working_answers[$row['question_id']]['response'] = $row['response'];
			$working_answers[$row['question_id']]['response_text'] = $row['response_text'];
			$working_answers[$row['question_id']]['suggestion'] = $row['suggestion'];
			$working_answers[$row['question_id']]['decision'] = $row['decision'];

		}

		$this->set('working_answers', $working_answers);



		$this->set('order_id', $order_id);
		//$this->set('questions', $questions);
		//$this->set('answers', $answers);
		$this->set('values', $values);



		$this->set('history_keys',$history_keys);
		$this->set('job_truck', $this->params['url']['job_truck']);
		$this->set('orders', $orders);
		$this->set('allSources', $this->Lists->BookingSources());
		$this->set('suppliers', $this->Lists->ListTable('ace_rp_suppliers','',array('name','address')));
		$this->set('job_trucks', $this->HtmlAssist->table2array($this->InventoryLocation->findAll(array('type' => '2'), null, null, null, 1, 0), 'id', 'name'));
		$this->set('techs', $this->Lists->Technicians());
		$this->set('payment_methods', $this->HtmlAssist->table2array($this->PaymentMethod->findAll(array(), null, null, null, 1, 0), 'id', 'name'));
	}
    
}
