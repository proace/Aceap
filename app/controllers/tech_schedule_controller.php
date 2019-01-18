<?php
error_reporting(E_PARSE ^ E_ERROR );
//error_reporting(2047);

class TechScheduleController extends AppController
{
	//To avoid possible PHP4 problemfss
	var $name = "TechScheduleController";
  var $uses = array('User');
	var $helpers = array('Common');
	var $components = array('HtmlAssist', 'RequestHandler', 'Common', 'Lists');
	var $itemsToShow = 20;
	var $pagesToDisplay = 10;

  function index()
  {
		$this->layout='list';
    $db =& ConnectionManager::getDataSource('default');

    $AllRoutes = array();
    
		$query = "select * from ace_rp_inventory_locations order by id asc";
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result)) {
			$AllRoutes[$row['id']] = array(
        'id' => $row['id'],
        'name' => $row['name'],
        'truck_number' => $row['truck_number'],
        'tech1' => array(
            $row['tech1_day1'],
            $row['tech1_day2'],
            $row['tech1_day3'],
            $row['tech1_day4'],
            $row['tech1_day5'],
            $row['tech1_day6']
        ),
        'tech2' => array(
            $row['tech2_day1'],
            $row['tech2_day2'],
            $row['tech2_day3'],
            $row['tech2_day4'],
            $row['tech2_day5'],
            $row['tech2_day6']
        )
      );
		}
    
		$this->set('AllRoutes',$AllRoutes);
		$this->set('AllTechnicians',$this->Lists->Technicians());
  }
	
	function setTech()
	{
		$tech_id = $_GET['tech_id'];
		$day = $_GET['day'];
		$route_id = $_GET['route_id'];
		$tech_num = $_GET['tech_num'];
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$db->_execute("update ace_rp_inventory_locations set tech{$tech_num}_day{$day}={$tech_id} WHERE id=$route_id");
 		exit;
	}
}
?>