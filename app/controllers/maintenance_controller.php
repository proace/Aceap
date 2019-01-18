<?php
class MaintenanceController extends AppController
{
	var $name = 'Maintenance';
	var $components = array('HtmlAssist', 'Common', 'Lists');
	
	function index() {
		$this->set('maintenance', $this->Maintenance->findAll());
		$this->set('message', $this->Session->read("message"));
		$this->Session->write("message", "");
	}
	
	function save() {		
		if($this->data['Maintenance']['delete'] == 1) {
			if($this->Maintenance->delete($this->data['Maintenance']['id'], false)) {
				$this->Session->write("message", $this->data['Maintenance']['name']." was deleted.");
				$this->redirect('/maintenance/');
			} else {
				$this->Session->write("message", $this->data['Maintenance']['name']." was not deleted.");
				$this->redirect('/maintenance/');
			}
		} else {
			if($this->data['Maintenance']['active'] != 1) $this->data['Maintenance']['active'] = 0;
			if($this->Maintenance->save($this->data['Maintenance'])) {				
				$this->Session->write("message", $this->data['Maintenance']['name']." was saved.".mysql_error());
				$this->redirect('/maintenance/');			
			} else {
				$this->Session->write("message", $this->data['Maintenance']['name']." was not saved.");
				$this->redirect('/maintenance/');
			}
		}
	}
	
	function grid($id) {
		$db =& ConnectionManager::getDataSource("default");
				
		$query = "
			SELECT *
			FROM ace_rp_maintenance
			WHERE id = $id
			LIMIT 1
		";
		
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result);		
		
		$name = $row['name'];
		$table = $row['table'];		
		
		
		$query = "
			SELECT * 
			FROM information_schema.columns
			WHERE table_name = '$table'
			AND table_schema = 'whytecl_acesys'
		";
		
		$result = $db->_execute($query);
		
		while($row = mysql_fetch_array($result))
		{			
			$comment['COLUMN_NAME'] = $row['COLUMN_COMMENT'];			
		}
		
		$query = "
			DESCRIBE $table
		";
		
		$result = $db->_execute($query);
		
		$column_line = "";
		
		while($row = mysql_fetch_array($result))
		{			
			$arrTemp['field'] = $row['Field'];
			$arrTemp['type'] = $row['Type'];
			$arrTemp['null'] = $row['Null'];
			$arrTemp['key'] = $row['Key'];
			$arrTemp['default'] = $row['Default'];
			$arrTemp['extra'] = $row['Extra'];
			$arrTemp['comment'] = $comment[$row['Field']];
			
			$columns[] = $arrTemp;
			
			$coltemp = $row['Field'];
			$typetemp = $row['Type'];
			$keytemp = $row['Key'];
			$defaulttemp = $row['Default'];
			$extratemp = $row['Extra'];
			
			$column_line .= "`$coltemp`, '$typetemp' AS '".$coltemp."_type',";
			if($keytemp != "") $column_line .=  "'$keytemp' AS '".$coltemp."_key',";
			if($defaulttemp != "") $column_line .=  "'$defaulttemp' AS '".$coltemp."_default',";
			if($extratemp != "") $column_line .=  "'$extratemp' AS '".$coltemp."_extra',";
		}
		
		if($column_line != '') {			
			$column_line = substr($column_line, 0, -1);
			$query = "
				SELECT $column_line
				FROM $table
			";
			
			$result = $db->_execute($query);
			$index = 0;
			while($row = mysql_fetch_array($result))
			{
				foreach($columns as $column) {
					$rows[$index]['index'] = $index;
					$rows[$index][$column['field']] = $row[$column['field']];
					$rows[$index][$column['field'].'_type'] = $row[$column['field'].'_type'];
					$rows[$index][$column['field'].'_key'] = $row[$column['field'].'_key'];
				}
				$index++;
			}			
		}
		
		$this->set('columns', $columns);
		$this->set('rows', $rows);
		$this->set('name', $name);
		$this->set('id', $id);
	}
	
	function saveGrid($id) {
		
		$db =& ConnectionManager::getDataSource("default");
				
		$query = "
			SELECT *
			FROM ace_rp_maintenance
			WHERE id = $id
			LIMIT 1
		";
		
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result);		
		
		$name = $row['name'];
		$table = $row['table'];		
		
		
		
		
		$query = "
			DESCRIBE $table
		";
		
		$result = $db->_execute($query);
		
		$column_line = "";
		
		while($row = mysql_fetch_array($result))
		{			
			$arrTemp['field'] = $row['Field'];
			$arrTemp['type'] = $row['Type'];
			$arrTemp['null'] = $row['Null'];
			$arrTemp['key'] = $row['Key'];
			$arrTemp['default'] = $row['Default'];
			$arrTemp['extra'] = $row['Extra'];			
			
			$columns[] = $arrTemp;

		}
		
		$mass_query = "";
		if($this->data['newrow']['is_edited']) {
			$query = "
				INSERT INTO $table (				
			";
			
			foreach($columns as $column) {
				$query .= "`".$column['field']."`,";					
			}
			
			$query = substr($query, 0, -1) . ") VALUES(";
			
			foreach($columns as $column) {
				$query .= "'".$this->data['newrow'][$column['field']]."',";				
			}
			
			$query = substr($query, 0, -1) . ");"; 
			
			//$mass_query .= $query;
			$db->_execute($query);		
		}
		
		foreach($this->data['ThisTable'] as $row) {							
			if($row['is_edited']) {
				$query = "
					UPDATE $table SET 				
				";
				
				foreach($columns as $column) {
					if($column['key'] == "PRI") {
						$condition_line = " WHERE ".$column['field']."='".$row[$column['field']]."'";
					} else {
						$query .= "`".$column['field']."`='".$row[$column['field']]."',";
					}					
				}
				$query = substr($query, 0, -1);
				//$mass_query .= $query . $condition_line . ";";
				$db->_execute($query . $condition_line);
			}
		}
		
		//echo $mass_query; //writes the query to sql for debugging purposes
		//$db->_execute($mass_query);
		$this->redirect($this->referer());
	}
}
?>