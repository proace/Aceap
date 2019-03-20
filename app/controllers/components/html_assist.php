<?php

class HtmlAssistComponent extends Object
{
    var $controller = true;
 
    function startup(&$controller_in)
    {
        // This method takes a reference to the controller which is loading it.
        // Perform controller initialization here.
        $this->controller =& $controller_in;
        $this->controller->set('HtmlAssist', $this);
    }
 
    function table2array($table_array, $value_field, $title_field, $separator_array = "", $separator = "")
    {
		$sep = "";
		$si = -1;
		$arr = array();
		foreach ($table_array as $item)
		{

			if ($separator != "")
			{
				if (separator_array != "")
					$item_sep = $item[$separator_array];
				else
				{
					$item_sep = $item;
					foreach($item_sep as $subitem)
						$item_sep = $subitem;
				}

				if ($item_sep[$separator] != $sep)
				{
					$sep = $item_sep[$separator];
					$arr += array($si-- => "---");
				}
			}

			foreach($item as $subitem)
				$item = $subitem;

			$arr += array($item[$value_field] => $item[$title_field]);
		}

		return $arr;
    }
    
	function prPrice($val)
	{
		return '<nobr>$' . number_format($val, 2, '.', ' ') . '</nobr>';
	}
	function totalPrPrice($val)
	{
		return  number_format($val, 2, '.', ' ');
	}
	
	function getOrderGoogleMap($pCustomer) {
		$returnValue = '';
		
		if( $pCustomer['id'] > 0 ) {
			$returnValue = 'http://maps.google.ca/maps?f=q&hl=en&q='.urlencode($pCustomer['address_street_number'].' '.$pCustomer['address_street'].','.$pCustomer['city'].',BC');
		}
		
		return $returnValue;
	}
	
	
	function tableOrderItems($order, $type)
	{
		$h = '
			<tr>
				<th align=left>Item</th>
				<th align=left>Qty</th>
				<th>Price</th>
			</tr>';
		if ($type)
			$arr = $order[$type];
		else
			$arr = $order;
		
		foreach ($arr as $oi)
		{
			$h .= '<tr><td>'.$oi['name'].'</td>';
			$h .= '<td>x'.$oi['quantity'].'</td>';
			$h .= '<td>$'.$oi['price'].'</td></tr>';
		}
		
		return $h;
	}
	
	function tableOrderItemsPlain($order, $type)
	{
		$h = "Item\t\t\t Qty\t\t Price\n";

		if ($type)
			$arr = $order[$type];
		else
			$arr = $order;
		
		foreach ($arr as $oi)
		{
			$h .= $oi['name']."\t\t\t ";
			$h .= 'x'.$oi['quantity']."\t\t ";
			$h .= '$'.$oi['price']."\t\t\n";
		}
		
		return $h;
	}
	
	function tableOrderCoupons($order)
	{
		$h = '
			<tr>
				<th align=left>Name</th>
				<th align=left>Value</th>
			</tr>';
		if ($type)
			$arr = $order[$type];
		else
			$arr = $order;
		
		foreach ($arr as $oi)
		{
			$h .= '<tr><td>'.$oi['name'].'</td>';
			$h .= '<td>';
			if ($oi['percent'])
				$h .= $oi['percent']."%";
			if ($oi['percent'] && $oi['price'])
				$h .= " + ";
			if ($oi['price'])
				$h .= "$".$oi['price'];
			$h .='</td>';
		}
		
		return $h;
	}
	
	function orderTimeColor($order)
	{
		$color = '';
		if ($order['order_status_id'] == 5)		//done
			$color = '#41FB00';	//'green';
		else if (($order['order_status_id'] == 3) || ($order['order_status_id'] == 2))	//cancelled
			$color = '#FF9D9D';	//'red';
		else if ($order['order_status_id'] == 1)	
		{
			if ($order['order_substatus_id'] == 8)	//'Delayed' substatus should always be blue
				$color = '#c0eafe';
			elseif ($order['order_substatus_id'] == 7)	//'Done' substatus should always be light-green
				$color = '#cafec0';
			else{
				$setting = new Setting();
				$settings = $setting->find(array('title'=>'timezonecorrection'));
				$tdiff = strtotime($order['job_date'].' '.$order['job_time_end']) - strtotime(date('Y-m-d H:i:s', time() + $settings['Setting']['valuetxt']));
	
				$color = '#EAEAEA';	//'gray' - everything all right;
				if($order['tech_visible_agent'] == 1){
					$color = '#ffff00';
				}
				
				$minutes = $tdiff / 60; //minutes: time until deadline - negative number = past deadline
				/*if (($minutes < 0)&&($order['order_substatus_id'] != 7))	//deadline passed! job is late
					$color = '#FED6A1';	//orange';*/	//warning: customer must be notified
			}
		}
		
		return "background-color:".$color;
	}
}
?>
