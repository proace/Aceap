<?php
class IvCategorySettingsController extends AppController
{
	var $name = 'IvCategorySettings';
	var $components = array('HtmlAssist', 'Common', 'Lists');
	
	function edit($id) {
		$this->layout = 'blank';
		$db =& ConnectionManager::getDataSource('default');	
		
		$query = "
			SELECT name 
			FROM ace_iv_categories
			WHERE id = $id
		";
			
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result);
		$category = $row['name'];
		
		$query = "
			SELECT * 
			FROM ace_iv_category_settings
			WHERE iv_category_id = $id
		";
			
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result))
		{					
			$settings[$row['field']]['iv_category_id'] = $id;
			$settings[$row['field']]['field'] = $row['field'];
			$settings[$row['field']]['alias'] = $row['alias'];
			$settings[$row['field']]['order'] = $row['order'];
		}
		
		$this->set('iv_category_id', $id);
		$this->set('category', $category);
		$this->set('settings', $settings);
	}
	
	function save() {
		$db =& ConnectionManager::getDataSource('default');	
		$id = $this->data['Settings']['category_id'];
		
		if($this->data['Settings']['description1']['order'] == -1
			&& $this->data['Settings']['description2']['order'] == -1
			&& $this->data['Settings']['efficiency']['order'] == -1
			&& $this->data['Settings']['model']['order'] == -1
			&& $this->data['Settings']['efficiency']['order'] == -1
			&& $this->data['Settings']['supplier_price']['order'] == -1
			&& $this->data['Settings']['selling_price']['order'] == -1
			&& $this->data['Settings']['brand_id']['order'] == -1
			&& $this->data['Settings']['supplier_id']['order'] == -1
			&& $this->data['Settings']['active']['order'] == -1
			) {
			$this->data['Settings']['active']['order'] = 1;
		}
		
		$query = "
			DELETE FROM ace_iv_category_settings
			WHERE iv_category_id = $id
		";			
		$result = $db->_execute($query);
		
		$query = "
		INSERT INTO ace_iv_category_settings(`iv_category_id`, `field`, `alias`, `name`, `order`)
			VALUES($id, 'category_id', 'Category', 'category', 0);
		";			
		$result = $db->_execute($query);
		
		$query = "
			INSERT INTO ace_iv_category_settings(`iv_category_id`, `field`, `alias`, `name`, `order`)
			VALUES($id, 'description1', '".$this->data['Settings']['description1']['alias']."', '".$this->data['Settings']['description1']['name']."', ".$this->data['Settings']['description1']['order'].");
		";			
		$result = $db->_execute($query);
		
		$query = "
			INSERT INTO ace_iv_category_settings(`iv_category_id`, `field`, `alias`, `name`, `order`)
			VALUES($id, 'description2', '".$this->data['Settings']['description2']['alias']."', '".$this->data['Settings']['description2']['name']."', ".$this->data['Settings']['description2']['order'].");
		";			
		$result = $db->_execute($query);
		
		$query = "
			INSERT INTO ace_iv_category_settings(`iv_category_id`, `field`, `alias`, `name`, `order`)
			VALUES($id, 'efficiency', '".$this->data['Settings']['efficiency']['alias']."', '".$this->data['Settings']['efficiency']['name']."', ".$this->data['Settings']['efficiency']['order'].");	
		";			
		$result = $db->_execute($query);
		
		$query = "
			INSERT INTO ace_iv_category_settings(`iv_category_id`, `field`, `alias`, `name`, `order`)
			VALUES($id, 'model', '".$this->data['Settings']['model']['alias']."', '".$this->data['Settings']['model']['name']."', ".$this->data['Settings']['model']['order'].");
		";			
		$result = $db->_execute($query);
		
		$query = "
			INSERT INTO ace_iv_category_settings(`iv_category_id`, `field`, `alias`, `name`, `order`)
			VALUES($id, 'supplier_price', '".$this->data['Settings']['supplier_price']['alias']."', '".$this->data['Settings']['supplier_price']['name']."', ".$this->data['Settings']['supplier_price']['order'].");
		";			
		$result = $db->_execute($query);
		
		$query = "
			INSERT INTO ace_iv_category_settings(`iv_category_id`, `field`, `alias`, `name`, `order`)
			VALUES($id, 'selling_price', '".$this->data['Settings']['selling_price']['alias']."', '".$this->data['Settings']['selling_price']['name']."', ".$this->data['Settings']['selling_price']['order'].");				
		";			
		$result = $db->_execute($query);
		
		$query = "
			INSERT INTO ace_iv_category_settings(`iv_category_id`, `field`, `alias`, `name`, `order`)
			VALUES($id, 'brand_id', '".$this->data['Settings']['brand_id']['alias']."', '".$this->data['Settings']['brand_id']['name']."', ".$this->data['Settings']['brand_id']['order'].");				
		";			
		$result = $db->_execute($query);
		
		$query = "
			INSERT INTO ace_iv_category_settings(`iv_category_id`, `field`, `alias`, `name`, `order`)
			VALUES($id, 'supplier_id', '".$this->data['Settings']['supplier_id']['alias']."', '".$this->data['Settings']['supplier_id']['name']."', ".$this->data['Settings']['supplier_id']['order'].");				
		";			
		$result = $db->_execute($query);
		
		$query = "
			INSERT INTO ace_iv_category_settings(`iv_category_id`, `field`, `alias`, `name`, `order`)
			VALUES($id, 'active', '".$this->data['Settings']['active']['alias']."', '".$this->data['Settings']['active']['name']."', ".$this->data['Settings']['active']['order'].");
		";			
		$result = $db->_execute($query);
		$this->redirect("pages/close");
	}
	
}
?>
