<?php
class TableeditorController extends AppController
{
    var $name = 'Tableeditor';

    var $components = array('Common');
	
		var $itemsToShow = 300;
		var $pagesToDisplay = 10;
		
		var $beforeFilter = array('checkAccess');
		//var $layout = "tableeditor";
		
	  function checkAccess()
	  {
	  	//$this->Common->checkRoles(array('6'));
	  }
	  
    function index()
    {
    	//Added by Metodi: 09-12-2009
    	$isAppliance = 0;
    	if(isset($this->params['url']['is_appliance'])){
    		$isAppliance = intval($this->params['url']['is_appliance']);
    	}

    	
    	$this->set('sm', 10);
    	
    	$db =& ConnectionManager::getDataSource('default');
    	 
    	$tableName = '';
		
		if (isset($this->params['url']['table'])) {
			$tableName = $this->params['url']['table'];
		} else {
			$this->flash('Table is no set!.','/tableeditor');
			exit();
		}

		if (isset($this->params['url']['filter_tbl']))
			$filter_tbl = $this->params['url']['filter_tbl'];
		
		if (isset($this->params['url']['filter_fld']))
			$filter_fld = $this->params['url']['filter_fld'];
			
		if (isset($this->params['url']['filter_val']))
			$filter_val = $this->params['url']['filter_val'];
			
		if (isset($this->params['url']['filter_fld2']))
			$filter_fld2 = $this->params['url']['filter_fld2'];
			
		if (isset($this->params['url']['filter_val2']))
			$filter_val2 = $this->params['url']['filter_val2'];
						
		//add clause for field name here as well
		
		$tableFields = array();
		$query = "DESCRIBE ".$tableName;
    	$result = $db->_execute($query);
    	while($row = mysql_fetch_array($result)) {
			if($tableName == 'ace_rp_items'){
				if($row['Field'] != 'is_appliance'){
    		$tableFields[$row['Field']] = $row['Type'];
    	}
			}
			else{
				$tableFields[$row['Field']] = $row['Type'];
			}
    	}
    	
    	$sort = $this->data['sort'] = $this->params['url']['sort'];
			$order = $this->data['order'] = $this->params['url']['order'];
			$currentPage = $this->data['currentPage'] = $this->params['url']['currentPage'];
			$this->data['table'] = $tableName;
			
			$SORT_ASC = '&nbsp;<span class="sortarrow">&Uacute;</span>';
			$SORT_DESC = '&nbsp;<span class="sortarrow">&Ugrave;</span>';

			$sqlOrder = '';
			$sqlSort = $sort;
			if( in_array( $order,array_keys($tableFields)) ) {
				$sqlOrder = $order;
				$this->data['s'.$order.''] =( $sort == 'DESC' ? $SORT_DESC : $SORT_ASC );
			} else {
				$sqlOrder = 'id';
				$sqlSort = 'DESC';
			}
			
			$sqlOrder .= ' '.$sqlSort;
			
			$query = "select * FROM ".$tableName;
			$query .= " WHERE 1=1 ";
			if ($filter_fld)
				$query .= " AND ".$filter_fld."='".$filter_val."'";
			if ($filter_fld2)
				$query .= " AND ".$filter_fld2."='".$filter_val2."'";
			
			$quert .= " order by ".$sqlOrder;
			
		  $listString = "";
		  $allRows = mysql_num_rows($db->_execute($query));
		  $allPages = ceil($allRows / $this->itemsToShow);
		  if($currentPage >=$allPages)
		       $currentPage = $allPages-1;
		  $newQuery = $query . " LIMIT " . ($currentPage * $this->itemsToShow) . ", ".$this->itemsToShow;
		  $resultToList = $db->_execute($newQuery);
		  
			$tableValues = array();
			while($row = mysql_fetch_array($resultToList)) {
    		foreach( $tableFields as $k => $v ) {
    			$tableValues[$row['id']][$k] = $row[$k];
    		}
    	}
    	//pr($tableValues);die();
			
		$this->Common->pagination($allRows,$currentPage,$this->itemsToShow,$this->pagesToDisplay);
		
		//Now Get Filter name
		if (($filter_tbl) && ($filter_fld))
		{
			$query = "select * FROM ".$filter_tbl;
			$query .= " WHERE id='".$filter_val."'";

			$result = $db->_execute($query);
		  
			if ($row = mysql_fetch_array($result))
				$this->set("filter_title", $row['name']);
		}
			
    	$this->set("tableFields",$tableFields);
    	$this->set("tableValues",$tableValues);
		$this->set("tableName",$tableName);
		$this->set("common", $this->Common);
		$this->set("filter_fld", $filter_fld);
		$this->set("filter_val", $filter_val);
		$this->set("filter_fld2", $filter_fld2);
		$this->set("filter_val2", $filter_val2);
		$this->set("filter_tbl", $filter_tbl);
    }
		
	function addedit()
	{
		$this->set('sm', 10);
		
	  	error_reporting(1);
		
		($this->params['url']['filter_tbl'] ? $filter_tbl = $this->params['url']['filter_tbl'] : $filter_tbl = $_POST['filter_tbl']);
		($this->params['url']['filter_fld'] ? $filter_fld = $this->params['url']['filter_fld'] : $filter_fld = $_POST['filter_fld']);
		($this->params['url']['filter_val'] ? $filter_val = $this->params['url']['filter_val'] : $filter_val = $_POST['filter_val']);
		($this->params['url']['filter_fld2'] ? $filter_fld2 = $this->params['url']['filter_fld2'] : $filter_fld2 = $_POST['filter_fld2']);
		($this->params['url']['filter_val2'] ? $filter_val2 = $this->params['url']['filter_val2'] : $filter_val2 = $_POST['filter_val2']);
	  	
	  	$db =& ConnectionManager::getDataSource('default');
	  	
	  	$tableName  = $this->params['url']['table'] != '' ? $this->params['url']['table'] : $this->params['form']['table'];
		if (trim($tableName) == '' ) {
			$this->flash('Table is no set!.','/tableeditor');
			exit();
		}
		$tableFields = array();
		$query = "DESCRIBE ".$tableName;
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{
	    	$tableFields[$row['Field']] = $row['Type'];
			if ($row['Key'] == 'PRI')
				$pri_key = $row['Field'];
    	}
		
		if (!empty($this->data))
	    {
			//save data		
			if (empty($this->data[$pri_key]))
				$add = 1;
			else
			{
				$sql = "SELECT * FROM ".$tableName." WHERE id=".$this->data[$pri_key];
				$result = $db->_execute($query);
				if ($row = mysql_fetch_array($result))
					$add = 0;
				else
					$add = 1;
			}
			
			if ($add == 1)
				$query = "insert into ";
			else
				$query = "replace into ";
			$query .= $tableName." set ";
			foreach( $tableFields as $k => $v )
			{
				if ($this->data[$k] == '')
					continue;
				
				$query .= " ".$k."='".$this->data[$k]."',";
			}
			$query = substr($query,0,-1);
			$db->_execute($query);
			//echo $query;
			$this->data['sort'] = ($this->params['url']['sort'] != '') ? $this->params['url']['sort'] : $this->params['form']['sort'];
			$this->data['order'] = $this->params['url']['order'] != '' ? $this->params['url']['order'] : $this->params['form']['order'];
			$this->data['currentPage'] = $this->params['url']['currentPage'] != '' ? $this->params['url']['currentPage'] : $this->params['form']['currentPage'];
			
			//echo "/tableeditor?table=".$tableName."&currentPage=".$this->data['currentPage']."&sort=".$this->data['sort']."&order=".$this->data['order'].($filter_val ? "&filter_fld=".$filter_fld."&filter_val=".$filter_val."&filter_tbl=".$filter_tbl : "").($filter_val2 ? "&filter_fld2=".$filter_fld2."&filter_val2=".$filter_val2 : "");
			$this->redirect("/tableeditor?table=".$tableName."&currentPage=".$this->data['currentPage']."&sort=".$this->data['sort']."&order=".$this->data['order'].($filter_val ? "&filter_fld=".$filter_fld."&filter_val=".$filter_val."&filter_tbl=".$filter_tbl : "").($filter_val2 ? "&filter_fld2=".$filter_fld2."&filter_val2=".$filter_val2 : ""));
			
			exit();
	    }
	      
	    $this->data['table'] = $tableName;
	    $this->data['sort'] = $this->params['url']['sort'] != '' ? $this->params['url']['sort'] : $this->params['form']['sort'];
			$this->data['order'] = $this->params['url']['order'] != '' ? $this->params['url']['order'] : $this->params['form']['order'];
			$this->data['currentPage'] = $this->params['url']['currentPage'] != '' ? $this->params['url']['currentPage'] : $this->params['form']['currentPage'];
				
	    if( $this->params['url']['id'] > 0) {
	      	$this->data['id'] = $this->params['url']['id'];
	      	// load data
	      	$query = "select * from ".$tableName." where id = ".$this->data['id'];
		    	$result = $db->_execute($query);
		    	$this->data['values'] = mysql_fetch_array($result);
	    }
	    elseif($tableName == 'ace_rp_items' && $this->params['url']['filter_fld2'] =='is_appliance'){
	    	$this->data['values']['is_appliance'] = $this->params['url']['filter_val2'];
	    }
	      
	    $this->set("tableFields", $tableFields);
		$this->set("tableName", $tableName);
		$this->set("filter_fld", $filter_fld);
		$this->set("filter_val", $filter_val);
		$this->set("filter_fld2", $filter_fld2);
		$this->set("filter_val2", $filter_val2);
		$this->set("filter_tbl", $filter_tbl);
		$this->set('common', $this->Common);
	}
	function saveDonebytech($order_id=""){
	  	$db =& ConnectionManager::getDataSource('default');
        $order_id = $_GET['orderId'];
        $query = "
            UPDATE ace_rp_orders
            SET order_status_id = 5
            WHERE id = $order_id";
        $db->_execute($query);
        exit();
            
        }
	  
	  function del()
	  {
		$this->set('sm', 10);

		($this->params['url']['filter_tbl'] ? $filter_tbl = $this->params['url']['filter_tbl'] : $filter_tbl = $_POST['filter_tbl']);
		($this->params['url']['filter_fld'] ? $filter_fld = $this->params['url']['filter_fld'] : $filter_fld = $_POST['filter_fld']);
		($this->params['url']['filter_val'] ? $filter_val = $this->params['url']['filter_val'] : $filter_val = $_POST['filter_val']);

		
	  	$db =& ConnectionManager::getDataSource('default');
	  	
	  	$tableName  = $this->params['url']['table'] != '' ? $this->params['url']['table'] : $this->params['form']['table'];
			if (trim($tableName) == '' ) {
				$this->flash('Table is no set!.','/tableeditor');
				exit();
			}
			
			$this->data['table'] = $tableName;
      $this->data['sort'] = $this->params['url']['sort'];
			$this->data['order'] = $this->params['url']['order'];
			$this->data['currentPage'] = $this->params['url']['currentPage'];
			
			$query = "delete from ".$tableName ." where id ='".$this->params['url']['id']."'";
    	$db->_execute($query);
    	
    	$this->redirect("/tableeditor?table=".$tableName."&currentPage=".$this->data['currentPage']."&sort=".$this->data['sort']."&order=".$this->data['order'].($filter_val ? "&filter_fld=".$filter_fld."&filter_val=".$filter_val."&filter_tbl=".$filter_tbl : ""));
			exit();
		}
}
?>
