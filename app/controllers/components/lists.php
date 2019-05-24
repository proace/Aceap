<?php
// Component contains or provide an access to all the lists that 
// are using inside the ACE System. The main purpose of it is
// to make these parts of code reusable and easy to manage.
// All these lists are very common and actually are references.
// Created: 05/31/2010, Anthony Chernikov
class ListsComponent extends Object
{
    var $controller = true;	// Link to the current controller
 
    function startup(&$controller_in)
    {
        // This method takes a reference to the controller which is loading it.
        // Perform controller initialization here.
        $this->controller =& $controller_in;
        $this->controller->set('Lists', $this);
    }

		// This is a list of all users that belong to one or several roles.
		// $RoleIDs should be a single number for the single role or
		// a string of comma-separated numbers for the plural roles
		function UsersByRoles($RoleIDs, $short=false, $active = true)
		{
				$db =& ConnectionManager::getDataSource($this->controller->User->useDbConfig);
				
				$name = "concat(a.first_name,' ',a.last_name)";
				if ($short) $name = "if(a.first_name!='',a.first_name,a.last_name)";
				$a = "";
				if ($active) $a = "a.is_active=1 and ";
				
				$result = $db->_execute("
				select a.id, $name as name 
				  from ace_rp_users as a, ace_rp_users_roles as b
				 where $a a.id=b.user_id and b.role_id in (" .$RoleIDs .")
				 order by name
				");
				
				$Ret = array();
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$Ret[$row['id']] = $row['name'];
				}
				
				return $Ret;
		}
		
		function UsersByGroup($groupID, $short=false)
		{
				$db =& ConnectionManager::getDataSource($this->controller->User->useDbConfig);
				
				$name = "concat(a.first_name,' ',a.last_name)";
				if ($short) $name = "if(a.first_name!='',a.first_name,a.last_name)";
				
				$result = $db->_execute("
				select a.id, $name as name 
				  from ace_rp_users as a, ace_rp_users_roles as b, ace_rp_roles r
				 where a.is_active=1 and a.id=b.user_id and b.role_id=r.id and r.group='$groupID'
				 order by name
				");
				
				$Ret = array();
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$Ret[$row['id']] = $row['name'];
				}
				
				return $Ret;
		}
		
		// This is a list of all technicians: the 'users' table
		// filtered by the 'technician' role id (1)
		function Technicians($short=false)
		{
				return $this->UsersByRoles(1,$short);
		}
		
		// This is a list of all helpers: the 'users' table
		// filtered by the 'helper' role id (2)
		function Helpers()
		{
				return $this->UsersByRoles(2);
		}
		
		// This is a list of all telemarketers: the 'users' table
		// filtered by the 'Telemarketer' and 'Limited Telemarketer' roles ids (3,9)
		function Telemarketers($active = true)
		{
				return $this->UsersByRoles('3,9', false, $active);
				//return $this->User->fincAll();
		}
		
		// This is a list of all admins: the 'users' table
		// filtered by the 'administrator' role id (6)
		function Admins()
		{
				return $this->UsersByRoles(6);
		}

		function Supervisors()
		{
				return $this->UsersByRoles('6,13');
		}
		
		// This is a list of all users which can be booking sources:
		// 'Technician', 'Telemarketer', 'Source', 'Limited Telemarketer'
		function BookingSources()
		{
				$db =& ConnectionManager::getDataSource($this->controller->User->useDbConfig);
				
				$result = $db->_execute("
				select b.role_id, a.id, CONCAT(a.first_name,' ',a.last_name) as name 
				  from ace_rp_users a, ace_rp_users_roles b
				 where a.is_active=1 and a.id=b.user_id
				   and b.role_id in (1,3,6,7,9,13)
				 order by b.role_id DESC, name
				");
				
				$Ret = array();
				$PrevRole = '';
				$nIdx = -1;
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
						if ($PrevRole != $row['role_id'])
						{
								$Ret[$nIdx]='---';
								$nIdx--;
						}
						$Ret[$row['id']] = $row['name'];
						$PrevRole = $row['role_id'];
				}
				
				return $Ret;
		}

		function CampaingList()
		{
				$db =& ConnectionManager::getDataSource($this->controller->User->useDbConfig);
				
				$result = $db->_execute("select a.id, a.campaign_name as name from ace_rp_reference_campaigns a");
				
				$Ret = array();

				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$Ret[$row['id']] = $row['name'];
				}
				
				return $Ret;
		}

		function AgentCampaingList($agent_id)
		{
				$db =& ConnectionManager::getDataSource($this->controller->User->useDbConfig);
				
				$result = $db->_execute("select a.id, a.campaign_name as name from ace_rp_reference_campaigns a where source_from = $agent_id");
				
				$Ret = array();

				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$Ret[$row['id']] = $row['name'];
				}
				
				return $Ret;
		}

		function AgentAllCampaingList($agent_id)
		{
				$db =& ConnectionManager::getDataSource($this->controller->User->useDbConfig);
				
				$result = $db->_execute("select a.id from ace_rp_reference_campaigns a where source_from = $agent_id");
				
				$Ret = array();

				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	
					$Ret[] = $row['id'];
				}
				return $Ret;
		}

		// This method creates a list for the drop-down menu from a given table.
		// This table should have 'id' and 'name' fields
		function ListTable($table_name, $conditions='', $fields=array('name'))
		{
				$db =& ConnectionManager::getDataSource($this->controller->User->useDbConfig);
				
				if ($conditions!='') $conditions = ' where '.$conditions;
				
				$result = $db->_execute("select * from ".$table_name.$conditions." order by name");
				
				$Ret = array();
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$sSep = '';
						$Ret[$row['id']] = '';
						foreach ($fields as $fld)
						{
								$Ret[$row['id']] .= $sSep .$row[$fld];
								$sSep = ' - ';
						}
				}
				
				return $Ret;
		}

		function paymenTable($table_name, $conditions='', $fields=array('name'))
		{
				$db =& ConnectionManager::getDataSource($this->controller->User->useDbConfig);
				
				if ($conditions!='') $conditions = ' where '.$conditions;
				
				$result = $db->_execute("select * from ".$table_name.$conditions." order by payment_order asc");
				
				$Ret = array();
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$sSep = '';
						$Ret[$row['id']] = '';
						foreach ($fields as $fld)
						{
								$Ret[$row['id']] .= $sSep .$row[$fld];
								$sSep = ' - ';
						}
				}
				
				return $Ret;
		}

		// This method returns an array containing the given row content.
		function GetTableRow($table_name, $conditions)
		{
				$db =& ConnectionManager::getDataSource($this->controller->User->useDbConfig);
				
				if ($conditions!='') $conditions = ' where '.$conditions;
				
				$result = $db->_execute("select * from ".$table_name.$conditions);
				
				$Ret = array();
				if ($row = mysql_fetch_array($result, MYSQL_ASSOC))
						foreach ($row as $k => $v)
							$Ret[$k] = $v;
				
				return $Ret;
		}

		// Here is a list of all order types.
		function OrderTypes()
		{
				$db =& ConnectionManager::getDataSource($this->controller->User->useDbConfig);
				
				$result = $db->_execute("select id, name from ace_rp_order_types where flagactive = 1");
				
				$Ret = array();
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$Ret[$row['id']] = $row['name'];
				}
				
				return $Ret;
		}

		// Here is a list of all item categories.
		function ItemCategories()
		{
				$db =& ConnectionManager::getDataSource($this->controller->User->useDbConfig);
				
				$result = $db->_execute("select id, name from ace_rp_item_categories order by name");
				
				$Ret = array();
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$Ret[$row['id']] = $row['name'];
				}
				
				return $Ret;
		}
		
		function PayPeriods($type)
		{
				$db =& ConnectionManager::getDataSource($this->controller->User->useDbConfig);
				
				$result = $db->_execute("select id, name from ace_rp_pay_periods where period_type=$type order by start_date desc");
				
				$Ret = array();
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$Ret[$row['id']] = $row['name'];
				}
				
				return $Ret;
		}
		
		function Groups()
		{
				$db =& ConnectionManager::getDataSource($this->controller->User->useDbConfig);
				
				$result = $db->_execute("SELECT id, name FROM ace_rp_groups WHERE id > 0");
				
				$Ret = array();
				
				while($row = mysql_fetch_array($result)) {
					$Ret[$row['id']]['id'] = $row['id'];
					$Ret[$row['id']]['name'] = $row['name'];
				}
				
				return $Ret;
		}
		
		function Routes($city_id, $job_date)
		{
				$db =& ConnectionManager::getDataSource($this->controller->User->useDbConfig);
				
				$result = $db->_execute("
					SELECT il.id, il.name 
					FROM ace_rp_inventory_locations il
					LEFT JOIN ace_rp_route_areas ra
					ON il.id = ra.route_id
					LEFT JOIN ace_rp_city_areas ca
					ON ra.area_id = ca.area_id
					WHERE (ra.route_id IS NULL
						AND ra.route_date IS NULL)
					OR ($city_id IN(SELECT c2.id 
							FROM ace_rp_city_areas ca2 
							LEFT JOIN ace_rp_cities c2
							ON c2.internal_id = ca2.city_id
							WHERE ca2.area_id = ca.area_id)
						AND ra.route_date = DATE('$job_date'))
				");
				
				$Ret = array();
				
				while($row = mysql_fetch_array($result)) {
					$Ret[$row['id']]['id'] = $row['id'];
					$Ret[$row['id']]['name'] = $row['name'];
				}
				
				return $Ret;
		}
		
		function CityAreas()
		{
				$db =& ConnectionManager::getDataSource($this->controller->User->useDbConfig);
				
				$result = $db->_execute("
					SELECT area_id, GROUP_CONCAT(city_id) cities
					FROM ace_rp_city_areas
					GROUP BY area_id
				");
				
				$Ret = array();
				
				while($row = mysql_fetch_array($result)) {
					$Ret[$row['area_id']]['area_id'] = $row['area_id'];
					$Ret[$row['area_id']]['cities'] = $row['cities'];
				}
				
				return $Ret;
		}
		
		
		
		function Cities()
		{
				$db =& ConnectionManager::getDataSource($this->controller->User->useDbConfig);
				
				$result = $db->_execute("
					SELECT *, CONCAT(c.name, '(', c.code, ')') modname
					FROM ace_rp_cities c
					LEFT JOIN ace_rp_route_cities rc
					ON c.internal_id = rc.city_id
					WHERE c.internal_id NOT IN(11,41,42,43,32,35,38,45,71,84,86,88,99,114,209,269)
					ORDER BY c.name
				");
				
				$Ret = array();
				
				while($row = mysql_fetch_array($result)) {
					$Ret[$row['id']]['id'] = $row['internal_id'];
					$Ret[$row['id']]['name'] = $row['modname'];
				}
				
				return $Ret;
		}
		
		function RouteCities()
		{
				$db =& ConnectionManager::getDataSource($this->controller->User->useDbConfig);
				
				$result = $db->_execute("
					SELECT internal_id, IFNULL(route_id, 0) route
					FROM ace_rp_cities c
					LEFT JOIN ace_rp_route_cities rc
					ON c.internal_id = rc.city_id
					WHERE c.internal_id NOT IN(11,41,42,43,32,35,38,45,71,84,86,88,99,114,209,269)
					ORDER BY c.name
				");
				
				$Ret = array();
				
				while($row = mysql_fetch_array($result)) {
					$Ret[$row['id']]['id'] = $row['internal_id'];					
					$Ret[$row['id']]['route'] = $row['route'];
				}
				
				return $Ret;
		}
		
		function CityCodes()
		{
				$db =& ConnectionManager::getDataSource($this->controller->User->useDbConfig);
				
				$result = $db->_execute("
					SELECT ca.area_id area_id,
					GROUP_CONCAT(c.code) codes
					FROM ace_rp_city_areas ca
					LEFT JOIN ace_rp_cities c
					ON c.internal_id = ca.city_id
					GROUP BY ca.area_id
				");
				
				$Ret = array();
				
				while($row = mysql_fetch_array($result)) {
					$Ret[$row['area_id']]['area_id'] = $row['area_id'];
					$Ret[$row['area_id']]['codes'] = $row['codes'];
				}
				
				return $Ret;
		}
		
		

		function VicidialStatuses()
		{
				$db =& ConnectionManager::getDataSource("vicidial");
				
				$result = $db->_execute("
					SELECT status, status_name 
					FROM vicidial_statuses 
					WHERE selectable = 'Y'
				");
				
				$Ret = array();
				
				while($row = mysql_fetch_array($result)) {
					$Ret[$row['status']]['status'] = $row['status'];
					$Ret[$row['status']]['status_name'] = $row['status_name'];
				}
				
				return $Ret;
		}
		
		function Titles()
		{
				$db =& ConnectionManager::getDataSource("default");
				
				$result = $db->_execute("
					SELECT *
					FROM ace_rp_titles
				");
				
				$Ret = array();
				
				while($row = mysql_fetch_array($result)) {
					$Ret[$row['id']]['id'] = $row['id'];
					$Ret[$row['id']]['name'] = $row['name'];
				}
				
				return $Ret;
		}
		
		function CancellationReasons()
		{
				$db =& ConnectionManager::getDataSource("default");
				
				$result = $db->_execute("
					SELECT cr.id, CONCAT(r.name,' - ', cr.name) name 
					FROM ace_rp_cancellation_reasons cr
					LEFT JOIN ace_rp_roles r 
					ON cr.role_id = r.id
					ORDER BY cr.role_id, cr.id
				");
				
				$Ret = array();
				
				while($row = mysql_fetch_array($result)) {
					$Ret[$row['id']] = $row['name'];					
				}
				
				return $Ret;
		}
		
		function PaymentMethods()
		{
				$db =& ConnectionManager::getDataSource("default");
				
				$result = $db->_execute("
					SELECT *
					FROM ace_rp_payment_methods					
				");
				
				$Ret = array();
				
				while($row = mysql_fetch_array($result)) {
					$Ret[$row['id']] = $row['name'];					
				}
				
				return $Ret;
		}
		
		function FeedbackRatings()
		{
				
				$Ret = array();
				
				$Ret[1] = 'BAD';
				$Ret[2] = 'GOOD';
				$Ret[3] = 'EXCELLENT';
				
				return $Ret;
		}
		
		function YesOrNo()
		{
				
				$Ret = array();
				
				$Ret[1] = 'YES';
				$Ret[0] = 'NO';				
				
				return $Ret;
		}
		
		function TrackemGPS()
		{
				
				$Ret = array();
				
				$Ret[0] = 'GPS not set';
				$Ret[1271] = 'Ace 1';
				$Ret[1270] = 'Ace 2';
				$Ret[1269] = 'Ace 3';
				$Ret[1266] = 'Ace 4';
				$Ret[1265] = 'Ace 5';
				$Ret[1267] = 'Ace 6';
				$Ret[1268] = 'Ace 7';			
				
				return $Ret;
		}
		
		function UserInterfaces()
		{
				$db =& ConnectionManager::getDataSource("default");
				
				$result = $db->_execute("
					SELECT *
					FROM ace_rp_interface			
				");
				
				$Ret = array();
				
				while($row = mysql_fetch_array($result)) {
					$Ret[$row['id']] = $row['name'];					
				}
				
				return $Ret;
		}
		
		function IvSuppliers()
		{
				$db =& ConnectionManager::getDataSource("default");
				
				$result = $db->_execute("
					SELECT *
					FROM ace_iv_suppliers			
				");
				
				$Ret = array();
				
				while($row = mysql_fetch_array($result)) {
					$Ret[$row['id']] = $row['name'];					
				}
				
				return $Ret;
		}
		
		function IvBrands()
		{
				$db =& ConnectionManager::getDataSource("default");
				
				$result = $db->_execute("
					SELECT *
					FROM ace_iv_brands	
				");
				
				$Ret = array();
				
				while($row = mysql_fetch_array($result)) {
					$Ret[$row['id']] = $row['name'];					
				}
				
				return $Ret;
		}
		
		function IvCategories()
		{
				$db =& ConnectionManager::getDataSource("default");
				
				$result = $db->_execute("
					SELECT *
					FROM ace_iv_categories		
				");
				
				$Ret = array();
				
				while($row = mysql_fetch_array($result)) {
					$Ret[$row['id']] = $row['name'];					
				}
				
				return $Ret;
		}
		// Loki: Get sub categories on basis of category Id
		function IvSubCategories($id)
		{
				$db =& ConnectionManager::getDataSource("default");
				
				$result = $db->_execute("
					SELECT *
					FROM  ace_iv_sub_categories where category_id =".$id);
				
				$Ret = array();
				
				while($row = mysql_fetch_array($result)) {
					$Ret[$row['id']] = $row['name'];					
				}
				
				return $Ret;
		}
		
		function EprintTerminals()
		{
				$db =& ConnectionManager::getDataSource("default");
				
				$result = $db->_execute("
					SELECT *
					FROM ace_rp_eprint			
				");
				
				$Ret = array();
				
				while($row = mysql_fetch_array($result)) {
					$Ret[$row['id']] = $row['name'];					
				}
				
				return $Ret;
		}
		
		function ActiveCities()
		{
				$db =& ConnectionManager::getDataSource("default");
				
				$result = $db->_execute("
					SELECT *
					FROM ace_rp_cities
					WHERE active = 1		
				");
				
				$Ret = array();
				
				while($row = mysql_fetch_array($result)) {
					$Ret[$row['id']] = $row['name'];					
				}
				
				return $Ret;
		}
		
		function ActiveCitiesWithId()
		{
				$db =& ConnectionManager::getDataSource("default");
				
				$result = $db->_execute("
					SELECT *
					FROM ace_rp_cities
					WHERE active = 1
					ORDER BY name		
				");
				
				$Ret = array();
				
				while($row = mysql_fetch_array($result)) {
					$Ret[$row['internal_id']] = $row['name'];					
				}
				
				return $Ret;
		}
		
		
}

?>
