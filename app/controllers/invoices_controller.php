<?php
class InvoicesController extends AppController
{
	var $name = 'Invoices';
	var $components = array('HtmlAssist', 'Common', 'Lists');
	var $uses = array('Order', 'Invoice', 'Question', 'Payment', 'Item');
		
	function invoiceSummary() {
		$this->layout = "h_invoice";
		$this->pageTitle = 'Handheld Invoice - Jobs';
				
		$this->set('orders', $this->Order->findAll(array(
			"Order.job_date" => date("Y-m-d"),  
			"OR" => array("Order.booking_source_id" => $this->Common->getLoggedUserID(), 
				"Order.booking_source2_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician1_id" => $this->Common->getLoggedUserID(),
				"Order.job_technician2_id" => $this->Common->getLoggedUserID()
			))
		, null, "Order.job_time_beg ASC"));
	}
		
	function invoiceDetails() {
		$orderid = $_GET['orderid'];
		
		$this->layout = "h_invoice";
		$this->pageTitle = 'Handheld Invoice - Details';
		
		$this->set('order', $this->Order->findById($orderid));
		$this->set('invoice', $this->Invoice->findByOrderId($orderid));
	}
	
	function invoiceQuestions() {
		$orderid = $_GET['orderid'];
		
		$this->layout = "h_invoice";
		$this->pageTitle = 'Handheld Invoice - Questions';
		
		$this->set('questions', $this->Question->findAll());
		$this->set('order', $this->Order->findById($orderid));
		$this->set('invoice', $this->Invoice->findByOrderId($orderid));
	}
	
	function invoiceItems() {
		$orderid = $_GET['orderid'];
		
		$this->layout = "h_invoice";
		$this->pageTitle = 'Handheld Invoice - Items';
		
		$this->set('order', $this->Order->findById($orderid));
		
		//Heating Services
		$heating_items = $this->Item->findAll(array('item_category_id'=> '3'), null, array("Item.name ASC"));
		$this->set('heating_items', $heating_items);
		
		//Parts
		$parts_items = $this->Item->findAll(array('item_category_id'=> '4','is_appliance'=>'2'), null, array("Item.name ASC"));
		$this->set('parts_items', $parts_items);
		
		//Appliances
		$appliances_items = $this->Item->findAll(array('item_category_id'=> '4','is_appliance'=>'1'), null, array("Item.name ASC"));
		$this->set('appliances_items', $appliances_items);
		
		//Carpet
		$carpet_items = $this->Item->findAll(array('item_category_id'=> '1'), null, array("Item.name ASC"));
		$this->set('carpet_items', $carpet_items);
		
		//Furniture
		//$furniture_items = $this->Item->findAll(array('item_category_id'=> '2'), null, array("item_category_id ASC", "Item.id ASC"));
		//$this->set('furniture_items', $furniture_items);
		
		//Other
		$other_items = $this->Item->findAll(array('is_appliance'=>'3'), null, array("Item.name ASC"));
		$this->set('other_items', $other_items);
	}
	
	function invoicePayment() {
		$orderid = $_GET['orderid'];
		
		$this->layout = "h_invoice";
		$this->pageTitle = 'Handheld Invoice - Payment';
		
		$this->set('order', $this->Order->findById($orderid));
		$this->set('payments', $this->Payment->findByIdorder($orderid));
		$this->set('paymentMethods', $this->Lists->PaymentMethods());
	}
	
	function invoiceFeedback() {
		$orderid = $_GET['orderid'];
		
		$this->layout = "h_invoice";
		$this->pageTitle = 'Handheld Invoice - Feedback';
		
		$this->set('order', $this->Order->findById($orderid));
		$this->set('invoice', $this->Invoice->findByOrderId($orderid));
		
		$this->set('feedbackRatings', $this->Lists->FeedbackRatings());
		$this->set('yesOrNo', $this->Lists->YesOrNo());
	}
	
	function invoiceTimeout() {
		$orderid = $_GET['orderid'];
		
		$this->layout = "h_invoice";
		$this->pageTitle = 'Handheld Invoice - Timeout';
		
		$this->set('order', $this->Order->findById($orderid));
	}			
	
	function saveInvoiceDetails(){
		$invoice_id = $this->data['Invoice']['id'];	
		$order_id = $this->data['Invoice']['order_id'];	
		
		if($this->Invoice->save($this->data['Invoice'])) {
			//save time in
			$this->Order->id = $order_id;					
			$this->Order->saveField('fact_job_beg', $this->data['Order']['fact_job_beg_hour'].':'.$this->data['Order']['fact_job_beg_min']);			
		}
		
		$this->redirect("invoices/invoiceQuestions?orderid=$order_id");
	}
	
	function saveInvoiceQuestions(){
		$invoice_id = $this->data['Invoice']['id'];		
		$order_id = $this->data['Invoice']['order_id'];
		
		$db =& ConnectionManager::getDataSource('default');
		if($invoice_id == '') $invoice_id = $this->Invoice->getLastInsertId();
			$db->_execute("
				DELETE FROM ace_rp_answers_invoices
				WHERE invoice_id = $invoice_id
			");
		foreach($this->data['Answer'] as $key => $ans) {
			$db->_execute("
				INSERT INTO ace_rp_answers_invoices(answer_id, invoice_id, user_answer)
				VALUES($key, $invoice_id,'$ans')					
			");
		}								
		
		$this->redirect("invoices/invoiceItems?orderid=$order_id");
	}
	
	function saveInvoiceItems(){
		$invoice_id = $this->data['Invoice']['id'];		
		$order_id = $this->data['Invoice']['order_id'];		
		
		$db =& ConnectionManager::getDataSource('default');
		
		//clear all items for the techs
		$this->Order->BookingItem->execute("DELETE FROM ace_rp_order_items WHERE order_id = $order_id AND class = 1");

		foreach($this->data['BookingItem'] as $item) {
			$item['order_id'] = $order_id;
			$item['class'] = 1;

			$this->Order->BookingItem->create();
			if (0 + $item['quantity']!=0) {
				$this->Order->BookingItem->create();
				$this->Order->BookingItem->save($item);
				$total += $item['quantity'] * $item['price'] - $item['discount'] + $item['addition'];
			}
			//print_r($item);			
		}
						
		$this->redirect("invoices/invoicePayment?orderid=$order_id");
	}
	
	function saveInvoicePayment(){
		$invoice_id = $this->data['Invoice']['id'];		
		$order_id = $this->data['Invoice']['order_id'];

		$db =& ConnectionManager::getDataSource('default');

		//save payment
		$order_id = $this->data['Invoice']['order_id'];
		$creator = $this->Common->getLoggedUserID();
		$payment_method = $this->data['Payment']['payment_method'];
		$payment_date = date("Y-m-d", strtotime($this->data['Payment']['payment_date']));
		$paid_amount = $this->data['Payment']['paid_amount'];
		$payment_type = $this->data['Payment']['payment_type'];
		$auth_number = $this->data['Payment']['auth_number'];
		$notes = $this->data['Payment']['notes'];
		
		//remove previous payments
		$db->_execute("
			DELETE FROM ace_rp_payments
			WHERE idorder = $order_id
		");
		
		//add the new payment
		$db->_execute("
			INSERT INTO ace_rp_payments(idorder, creator, payment_method, payment_date, paid_amount, payment_type, auth_number, notes) 
			VALUES ($order_id, '$creator', '$payment_method', '$payment_date', '$paid_amount', '$payment_type', '$auth_number', '$notes')
		");
		
		$this->redirect("invoices/invoiceFeedback?orderid=$order_id");
	}
	
	function invoicePrint() {
		$orderid = $_GET['orderid'];
		
		$this->layout = "blank";
		$this->pageTitle = 'Handheld Invoice - Print';
	}
	
	function saveInvoiceFeedback(){
		$invoice_id = $this->data['Invoice']['id'];
		$order_id = $this->data['Invoice']['order_id'];
		
		$this->Order->id = $order_id;
		$this->Order->saveField('feedback_service', $this->data['Invoice']['feedback_service']);
		$this->Order->saveField('feedback_price', $this->data['Invoice']['feedback_price']);
		$this->Order->saveField('feedback_knowledge', $this->data['Invoice']['feedback_knowledge']);
	
		$this->redirect("invoices/invoiceTimeout?orderid=$order_id");
	}
	
	function saveInvoiceTimeout(){
		$invoice_id = $this->data['Invoice']['id'];	
		$order_id = $this->data['Invoice']['order_id'];	
		
		//save time out
		$this->Order->id = $order_id;								
		$this->Order->saveField('fact_job_end', $this->data['Order']['fact_job_end_hour'].':'.$this->data['Order']['fact_job_end_min']);
		
		$this->redirect("invoices/invoiceSummary");
	}
	
}
?>