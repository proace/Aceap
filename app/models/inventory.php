<?php
class Inventory extends AppModel
{	
	var $name = 'Inventory';
	var $useTable = false;

	// Prefix should be alphabet and underscore characters only.
	// This is to allow 'item_id' to be parsed correctly.
	function getWarehouseInventory($prefix=null) {

		$sql = "
			SELECT locations.id as tech_id,
			items.id as item_id,
			items.name as item_name,
			movements.date as date,
			assets.regular_price as quote_price,
			COUNT(items.name) as quantity

			FROM ace_iv_locations locations,
			ace_iv_movements movements,
			ace_iv_assets assets,
			ace_iv_items items

			WHERE locations.number=0
			AND locations.id=movements.to_location_id
			AND movements.asset_id=assets.asset_id
			AND assets.item_id=items.id

			GROUP BY items.name
		";

		$items = array();
		$result = $this->execute($sql);
		foreach ($result as $row) {
			$items[ 'Warehouse' ][] = array(
				'tech_id' => '0',
				'item_name' => $row['items']['item_name'],
				'item_id' => $prefix . $row['items']['item_id'],
				'date' => $row['movements']['date'],
				'quote_price' => $row['assets']['quote_price'],
				'quantity' => $row['0']['quantity']
			);
		}
		return $items;

	}

	function getTechInventory() {

		$sql = "
			SELECT locations.id as tech_id,
			users.first_name as tech_name,
			items.id as item_id,
			items.name as item_name,
			movements.date as date,
			assets.regular_price as quote_price,
			COUNT(items.name) as quantity

			FROM ace_iv_locations locations,
			ace_iv_movements movements,
			ace_rp_users users,
			ace_iv_assets assets,
			ace_iv_items items

			WHERE locations.type LIKE 'User'
			AND locations.id=movements.to_location_id
			AND locations.number=users.id
			AND movements.asset_id=assets.asset_id
			AND assets.item_id=items.id

			GROUP BY items.name
		";

		$items = array();
		$result = $this->execute($sql);
		foreach ($result as $row) {
			$items[ $row['users']['tech_name'] ][] = array(
				'tech_id' => $row['locations']['tech_id'],
				'item_name' => $row['items']['item_name'],
				'item_id' => $row['items']['item_id'],
				'date' => $row['movements']['date'],
				'quote_price' => $row['assets']['quote_price'],
				'quantity' => $row['0']['quantity']
			);
		}
		return $items;
	}
}

?>