<?
error_reporting(1);

class ExpensesController extends AppController
{
	//To avoid possible PHP4 problems
	var $name = "Expenses";

	var $uses = array('Item', 'InventoryLocation', 'InventoryState', 'InventoryChange', 'Expense');
	var $helpers = array('Time');
	var $components = array('HtmlAssist', 'RequestHandler','Common');

	var $itemsToShow=20;
	var $pagesToDisplay=20;
	var $beforeFilter = array('checkAccess');

  function checkAccess()
  {
  	//if( $this->action == 'index' ) {
      $this->Common->checkRoles(array('6','4','5'));
    //}
  }
  
  	function index()
  	{
		$sort = $_GET['sort'];
		$order = $_GET['order'];

		$SORT_ASC = '&nbsp;<span class="sortarrow">&Uacute;</span>';
		$SORT_DESC = '&nbsp;<span class="sortarrow">&Ugrave;</span>';

		$sqlOrder = '';
		$sqlSort = $sort;
		switch ( $order ) {
			case 'name' :
			$sqlOrder = 'Item.name';
			$this->set('name',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'amount' :
			$sqlOrder = 'Expense.amount';
			$this->set('amount',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'date' :
			$sqlOrder = 'Expense.date';
			$this->set('date',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'supplier' :
			$sqlOrder = 'Expense.supplier';
			$this->set('supplier',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;	
			case 'qty' :
			$sqlOrder = 'Expense.qty';
			$this->set('qty',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'invoice' :
			$sqlOrder = 'Expense.invoiceNo';
			$this->set('invoice',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;
			case 'location' :
			$sqlOrder = 'InventoryChange.destination_id';
			$this->set('location',( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC ));
			break;					
			default : 
			$sqlOrder = 'Expense.date ASC,Expense.id';
			$sqlSort = 'ASC';
			break;
		}
		$sqlOrder .= ' '.$sqlSort; 	

		$conditions=array(); 
		if(($_GET['fromdate'] != "") && ($_GET['todate'] != ""))
		{
		  //$conditions["Expense.date"] = '>='.$this->Common->getMysqlDate($_GET['fromdate']).' AND <= '.$this->Common->getMysqlDate($_GET['todate']);
		  $conditions["Expense.date"] = '>='.$this->Common->getMysqlDate($_GET['fromdate']);
		  $conditions['and'] = array('Expense.date'=>'<= '.$this->Common->getMysqlDate($_GET['todate']));
		}
		else 
		{
			if( $_GET['fromdate'] != "" ) {
			  $conditions["Expense.date"] = '>='.$this->Common->getMysqlDate($_GET['fromdate']);
			}
			if( $_GET['todate'] != "" ) {
			  $conditions["Expense.date"] = '<='.$this->Common->getMysqlDate($_GET['todate']);
			}
		}

		if( $_GET['item_id'] != "" && $_GET['item_id'] != 0) {
		  $conditions["InventoryChange.item_id"] = '='.$_GET['item_id'];
		}

		if( $_GET['supplier'] != "" ) {
		  $conditions["Expense.supplier"] = 'LIKE %'.$_GET['supplier'].'%';
		}
		
		if( $_GET['locationID'] != "" && $_GET['locationID'] != "0" ) {
		  $conditions["InventoryChange.destination_id"] = '='.$_GET['locationID'];
		}
		// set type
		if( $this->params['url']['type_id'] > 0  ) {
			 $conditions["Expense.type_id"] = $this->params['url']['type_id'];
		}
		// set truck
		
		if( $this->params['url']['truck_id'] > 0  ) {
			 $conditions["InventoryChange.destination_id"] = $this->params['url']['truck_id'];
		}
		//pr($conditions);

		$this->Common->pagination($this->Expense->findCount($conditions),$_GET['paginationList1'],$this->itemsToShow,$this->pagesToDisplay,'paginationList1');
	 	$this->set('expenses', $this->Expense->findAll($conditions,"",$sqlOrder,$this->itemsToShow,$_GET['paginationList1']+1,2));
		
		if( $this->params['url']['type_id'] == 1)
			$this->set('sm', '3');
		else if( $this->params['url']['type_id'] == 2)
			$this->set('sm', '5');

	  	$this->set('items', $this->Item->findAll(array('ItemCategory.physical' => '1'),array('Item.id','Item.name','ItemCategory.id','ItemCategory.name'),'item_category_id',null,null,0));
  }

 	function new_expense()
	{
		$this->set('sm', '3');
		
		if (!empty($this->data))
	  {
		  	$isValid = true;
			
		  	if($this->data['Expense']['type_id'] <= 0)
			{
				$this->Expense->invalidate('type_id'); $isValid=false;
			}
			
			if($this->data['Expense']['type_id'] == 1)
			{
				$this->set('sm', '3');
				if($this->data['Expense']['item_id'] <= 0)
				{
					//$this->Expense->invalidate('item_id'); 
						
					$isValid=false;
				}
			}
							
			if($this->data['Expense']['type_id'] == 2)
			{
				$this->set('sm', '5');
				if($this->data['Expense']['note'] == "")
				{
					$this->Expense->invalidate('note'); $isValid=false;
				}
			}
				
			if($this->data['Expense']['date_day'] == "" || $this->data['Expense']['date_month'] == ""
				|| $this->data['Expense']['date_year'] == "")
			{
				$this->Expense->invalidate('date'); $isValid=false;
			}
			if($this->data['Expense']['qty'] <= 0)
			{
				$this->Expense->invalidate('qty'); $isValid=false;
			}	
				
			//Invalide Location only expense is inventory
			if($this->data['Expense']['type_id'] == 1)
			{
				if($this->data['Expense']['location_id'] <= 0)
				{
					$this->Expense->invalidate('location_id'); $isValid=false;
				}	
			}   			
			if($this->data['Expense']['amount'] <= 0)
			{
				$this->Expense->invalidate('amount'); $isValid=false;
			}	
				
			//pr($this->data);	
			if($isValid)
			{
				//Set data inf correct format
				$this->data['Expense']['date'] = $this->data['Expense']['date_year'].'-'.$this->data['Expense']['date_month'].'-'.$this->data['Expense']['date_day'];
					
				if($this->Expense->save($this->data['Expense']))
	        {
	        	$expenseID = $this->Expense->getInsertID();
	        	if($this->data['Expense']['type_id'] == 1)
	        	{
		        	//Add record to ace_inventory_changes
		        	//***********************************
							$InventoryChangesArr = array(
							"id"=>0,
			    		"item_id"=>$this->data['Expense']['item_id'],
			    		"date"=>$this->data['Expense']['date'],
			    		"qty"=>$this->data['Expense']['qty'],
			    		"source_id"=>0,
			    		"destination_id"=>$this->data['Expense']['location_id']);
							
			    		$this->InventoryChange->save($InventoryChangesArr);	        	
							
			    		//Get last insert id and update current Expense record
							$changeID = $this->InventoryChange->getInsertID();
				
							//Update Chnage_ID columh with inserted change id
							$this->Expense->UpdateField($expenseID,'change_id',$changeID);
							
			    		//Change ace_inventory_state table
		        	//********************************
		        	$conditions = array();
			        $conditions["InventoryState.item_id"] = '='.$this->data['Expense']['item_id'];
			        $conditions["InventoryState.location_id"] = '='.$this->data['Expense']['location_id'];
				    	$InventoryStates = $this->InventoryState->findAll($conditions);

		        	if(count($InventoryStates) == 0)
				    	{//Add new record
								$InventoryStatesArr = array(
								"id"=>0,
				    		"location_id"=>$this->data['Expense']['location_id'],
				    		"item_id"=>$this->data['Expense']['item_id'],
				    		"qty"=>$this->data['Expense']['qty']);
				    		$this->InventoryState->save($InventoryStatesArr);
				    	}
				    	else {
				    	
		        	$InventoryStatesArr = array(
							"id"=>$InventoryStates[0]['InventoryState']['id'],
			    		"location_id"=>$InventoryStates[0]['InventoryState']['location_id'],
			    		"item_id"=>$InventoryStates[0]['InventoryState']['item_id'],
			    		"qty"=>($InventoryStates[0]['InventoryState']['qty']+$this->data['Expense']['qty']));
							
			    		//pr($StateDestinationArr);

			    		$this->InventoryState->save($InventoryStatesArr);
						if ($this->data['rurl'][0])
							$this->redirect($this->data['rurl'][0]);
						else
							$this->redirect('/inventories/');
						exit();
				    	}     	
	        	}
		  			//Flash a message and redirect.
					//$this->flash('Your information has been saved.','/inventories/index');
					}
       	else
       	{
          $this->set('errorMessage', 'Please correct errors below.');
          $this->render();
       	}
			}
	  	
	  }
	  else  //!empty($this->data
	  {
	  		$this->Expense->id = 0;
	   		$this->data = $this->Expense->read();
	   		
	   		$this->data['Expense']['type_id'] = 1;
	   		//Set current date
	   		$this->data['Expense']['date_day'] = date("d");
	   		$this->data['Expense']['date_month'] = date("m");
	   		$this->data['Expense']['date_year'] = date("Y");
	   		

	  }

		
		$this->set('expenses', $this->Expense->findAll());
   	$this->set('inventoryLocations', $this->InventoryLocation->findAll());
  	$this->set('items', $this->Item->findAll(array('ItemCategory.physical' => '1'),array('Item.id','Item.name','ItemCategory.id','ItemCategory.name'),'item_category_id',null,null,0));
	}
}


?>
