<?
error_reporting(E_PARSE  ^ E_ERROR );
class ItemsController extends AppController
{
	//To avoid possible PHP4 problemfss
	var $name = "ItemsController";

	var $uses = array('User', 'Item', 'ItemCategory', 'OrderType');

	var $helpers = array('Time','Javascript','Common');
	var $components = array('HtmlAssist','RequestHandler','Common', 'Lists');
	var $itemsToShow = 20;
	var $pagesToDisplay = 10;
	
	function index()
	{
		$this->layout="list";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		
		$job_type = $_GET['data']['job_type'][0];
		$item_class = $_GET['data']['item_class'][0];
		if (!$order) $order = 'id asc';
				
		$ShowInactive = $_GET['ShowInactive'];
		if ($ShowInactive) $conditions = "";
		else $conditions = "and i.flagactive=1";
		
		if ($job_type) $conditions = ' and i.related_order_type_id='.$job_type;
		//if ($item_class||($item_class=='0')) $conditions = ' and i.is_appliance='.$item_class;
		
		if ($item_class||($item_class=='0')) $conditions = ' and i.item_category_id='.$item_class;
		
		$query = "select i.id, i.name, c.name item_category, t.name job_type, i.price,
										 i.is_appliance item_class, i.price_purchase, i.flagactive,
										 i.part_number
								from ace_rp_items i
								left join ace_rp_item_categories c on c.id=i.item_category_id
								left join ace_rp_order_types t on t.id=i.related_order_type_id
							 where related_order_type_id not in (8) " .$conditions ." 
							 order by ".$order.' '.$sort;
		
		//$itemclasses = array(0=>'Service',1=>'Appliance',2=>'Parts',3=>'Other',5=>'Special Parts');
		
		
		
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $items[$row['id']][$k] = $v;
			$items[$row['id']]['item_class'] = $itemclasses[$row['item_class']];
		}		
		
		$this->set('ShowInactive', $ShowInactive);
		$this->set('items', $items);
		$this->set('jobtypes', $this->Lists->ListTable('ace_rp_order_types'));
		$this->set('itemclasses', $this->Lists->ItemCategories());
		$this->set('job_type', $job_type);
		$this->set('item_class', $item_class);
	}

	function editItem()
	{
		$item_id = $_GET['item_id'];
		$this->Item->id = $item_id;
		$this->data = $this->Item->read();
		
		$this->set('jobtypes', $this->Lists->ListTable('ace_rp_order_types'));
		$this->set('itemcategories',  $this->Lists->ItemCategories());
	}

	function saveItem()
	{
		$this->Item->id = $this->data['Item']['id'];
		$this->Item->save($this->data);
		//Forward user where they need to be - if this is a single action per view
		$this->redirect('/items/index');
	}

	//Method creates a price-list for all the items
	function items_price()
	{
		//Heating Services
		$heating_items = $this->Item->findAll(array('item_category_id'=> '3'), null, array("item_category_id ASC", "Item.id ASC"));
		$this->set('heating_items', $heating_items);
		
		//Parts
		$parts_items = $this->Item->findAll(array('item_category_id'=> '4','is_appliance'=>'2'), null, array("item_category_id ASC", "Item.id ASC"));
		$this->set('parts_items', $parts_items);
		
		//Appliances
		$appliances_items = $this->Item->findAll(array('item_category_id'=> '4','is_appliance'=>'1'), null, array("item_category_id ASC", "Item.id ASC"));
		$this->set('appliances_items', $appliances_items);
		
		//Carpet
		$carpet_items = $this->Item->findAll(array('item_category_id'=> '1'), null, array("item_category_id ASC", "Item.id ASC"));
		$this->set('carpet_items', $carpet_items);
		
		//Furniture
		//$furniture_items = $this->Item->findAll(array('item_category_id'=> '2'), null, array("item_category_id ASC", "Item.id ASC"));
		//$this->set('furniture_items', $furniture_items);
		
		//Other
		$other_items = $this->Item->findAll(array('is_appliance'=>'3'), null, array("item_category_id ASC", "Item.id ASC"));
		$this->set('other_items', $other_items);
	}
	
	// Method prints the price table for the selected items category
	function getPriceTable()
	{
		$category = $_GET['category'];
		$items_list = $_GET['items_list'];
		$header = $_GET['header'];
		
		echo '<table cellpadding="4" cellspacing="0" width="100%">';
		echo '<tr><td colspan="2" style="background-color:#9EE871;height:20px;"><b>' .$header .'</b></td></tr>';
		
		if ($items_list) $conditions = ' and id in (' .$items_list .')';
		if ($category) $conditions = ' and item_category_id=' .$category;
		
		$db =& ConnectionManager::getDataSource($this->Order->useDbConfig);
		$sql = 'select * from ace_rp_items where 1=1' .$conditions;

		$counter = 0;
		$result = $db->_execute($sql);
		while ($item = mysql_fetch_array($result)) {
			$counter++;
			$row_style = "";
			if(($counter % 2) == 0){
				$row_style = 'style="background-color:#F8F7F9;"';
			}
			echo '<tr '.$row_style.'><td>'.htmlspecialchars($item['name']).'</td><td width="50">$'.htmlspecialchars($item['price']).'</td></tr>';
		}
		echo '</table>';
		
		exit;
	}

  // Method draws the list of pricing items with a possibility to select
	// a row from it. Generates a call of a javascript function
	// 'addItem' from the local context when the row is selected.
	// Created: Anthony Chernikov, 06/14/2010
	function _ShowPrices($job_type_id, $item_type, $appl='', $order_id)
	{
		if ($item_type==0)
		{
			$bg = 'background:#EEFFEE;';
			$txt = 'color:#000000;';
			$txt2 = '';
		}
		else
		{
			$bg = 'background:#EEF4FF;';
			$txt = 'color:#440000;';
			$txt2 = 'tech';
		}
     
		$tabs = array(1 => array('name' => 'Heating', 'condition' => 'is_appliance in (0,2,4) and related_order_type_id not in (1,8)'),
									2 => array('name' => 'Appliances', 'condition' => 'is_appliance=1'),
									3 => array('name' => 'Carpet', 'condition' => 'is_appliance in (0,2) and related_order_type_id in (1,8)'),
									4 => array('name' => 'Other', 'condition' => 'is_appliance=3'),
									5 => array('name' => 'Special Parts', 'condition' => 'is_appliance=5'));
				
		$appliance = 'ChangePriceSubpage(2)';
									
		if(!isset($order_id) || $order_id != 0){
			if ($_SESSION['user']['role_id'] == 1) { // TECHNICIAN=1
				$appliance = "alert('Use new booking for new appliance')";
			} 
		}

    $h = '
			<style type="text/css" media="all">
		   @import "'.ROOT_URL.'/app/webroot/css/style.css";
			</style>
			<script language="JavaScript" src="'.ROOT_URL.'/app/webroot/js/jquery.js"></script>
      <script language="JavaScript">
        prcpgopen=1;
        function ChangePriceSubpage(pg){
					$(".tabOver").removeClass("tabOver").addClass("tabOff");
					$("#prctab'.$txt2.'"+pg).removeClass("tabOff").addClass("tabOver");
					$("#PricePage'.$txt2.'"+prcpgopen).hide();
					$("#PricePage'.$txt2.'"+pg).show();
					prcpgopen =pg;
        }
				function SearchFilter(element, class){
				  if (element.value.length==0){
						$(class).parent().show();
					}else{
						$(class).parent().hide();
						$(class+":contains("+element.value.toUpperCase()+")").parent().show();
					}
				}
				function highlightCurRow(element){
					$(".item_row").css("background","");
					$("#"+element).css("background","#a9f5fe");
				}
				$(document).ready(function(){$("#ItemSearchString").focus();});
      </script>
			<table>
				<tr>
					<td id="prctab'.$txt2.'1" class="tabOver" style="cursor:pointer;" onclick="ChangePriceSubpage(1)"><b>'.$tabs[1]['name'].'</b></td>
					<td id="prctab'.$txt2.'2" class="tabOff" style="cursor:pointer;" onclick="'.$appliance.'"><b>'.$tabs[2]['name'].'</b></td>
					<td id="prctab'.$txt2.'3" class="tabOff" style="cursor:pointer;" onclick="ChangePriceSubpage(3)"><b>'.$tabs[3]['name'].'</b></td>
					<td id="prctab'.$txt2.'4" class="tabOff" style="cursor:pointer;" onclick="ChangePriceSubpage(4)"><b>'.$tabs[4]['name'].'</b></td>
					<td id="prctab'.$txt2.'5" class="tabOff" style="cursor:pointer;" onclick="ChangePriceSubpage(5)"><b>'.$tabs[5]['name'].'</b></td>
					<td><b>Search:</b><input type="text" id="ItemSearchString" onkeyup="SearchFilter(this,\'.item_name_td\')"/></td>
					<td><b>Part #:</b><input type="text" id="ItemSearchString" onkeyup="SearchFilter(this,\'.part_number_td\')"/></td>
				</tr>
				</table>';
		    
		//Select all items related to the current job type
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		for ($item_class=1;$item_class<6;$item_class++)
		{
				if ($item_class==1) $display="block";
				else $display="none";

				$h .= '<div class="pricesubpage" id="PricePage'.$txt2.$item_class.'" style="display:'.$display.';'.$bg.';height:350px;overflow:auto">';
				$h .= '<table style="'.$bg.'" cellspacing=0 colspacing=0>';
				if ($item_class==5)
					$h .= '<tr><th style="'.$bg.'">Item</th><th style="'.$bg.'">Part #</th><th style="'.$bg.'">Manufacturer</th><th style="'.$bg.'">Application</th><th style="'.$bg.'">&nbsp;</th><th style="'.$bg.'">&nbsp;</th></tr>';
				else
					$h .= '<tr><th style="'.$bg.'">Item</th><th style="'.$bg.'">&nbsp;</th><th style="'.$bg.'">&nbsp;</th><th style="'.$bg.'">&nbsp;</th><th style="'.$bg.'">Final</th><th style="'.$bg.'">Reg.</th></tr>';

				$conditions = ' where i.flagactive=1 and '.$tabs[$item_class]['condition'];
				if ($appl) $conditions .= ' and (is_appliance='.$appl.' or is_appliance=5)';
				if ($_SESSION['user']['role_id'] == 1) { // TECHNICIAN=1
					$conditions .= ' and show_tech=1';
				}
				//old inventory
				$query = "select i.id, i.is_appliance class, i.name item_name, i.price, i.regular_price, i.item_category_id, i.price_purchase, i.part_number, i.*
										from ace_rp_items i 
										".$conditions."
									 order by class asc, item_name asc";

				$result = $db->_execute($query);
				while ($row = mysql_fetch_array($result,MYSQL_ASSOC))
				{					
						$h .= '<tr class="item_row" id="item_'.$row['id'].'" style="cursor:pointer;" onclick="addItem('.$row['id'].',\''.$row['item_name'].'\',\''.$row['price'].'\',\''.$item_type.'\',\''.$row['item_category_id'].'\',\''.$row['price_purchase'].'\',\''.$row['part_number'].'\')" onMouseOver="highlightCurRow(\'item_'.$row['id'].'\')">';
						$h .= '<td class="item_name_td" style="display:none">&nbsp;'.strtoupper($row['item_name']).'</td>';
						$h .= '<td class="part_number_td" style="display:none">&nbsp;'.strtoupper($row['part_number']).'</td>';
						$h .= '<td style='.$txt.'>&nbsp;'.$row['item_name'].'</td><td>'.$row['part_number'].'</td><td>'.$row['manufacturer'].'</td><td>'.$row['application'].'</td>';
						$h .= '<td style='.$txt.'>&nbsp;'.$row['price'].'</td>';
						$h .= '<td style="color:#550000">&nbsp;'.$row['regular_price'].'</td>';
						$h .= '</tr>';
				}
				
				$h .= '</table>';
				$h .= '</div>';
		}
		
		return $h;
	}
	
	
	
  // AJAX method for the list of pricing items 
	// Created: Anthony Chernikov, 06/14/2010
	function showPrices()
	{
		$job_type_id=$_GET['job_type'];
		$item_type=$_GET['item_type'];
		$appl = $_GET['appl'];
		$order_id = $_GET['order_id'];
		echo $this->_ShowPrices($job_type_id,$item_type,$appl, $order_id);
		exit;
	}

	// AJAX method for activation/deactivation of an item
	function changeActive()
	{
		$item_id = $_GET['item_id'];
		$is_active = $_GET['is_active'];
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$db->_execute("update ace_rp_items set flagactive='".$is_active."' where id=".$item_id);

		exit;
	}
}
?>
