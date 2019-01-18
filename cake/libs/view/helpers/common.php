<?
class CommonHelper extends Helper {
	
	// Converts format of $datetime from 'dd mm yyyy' to 'yyyy-mm-dd'(delimiter in $datetime can be any non digit character)
	function getMysqlDate($datetime){
	  $timestamp = '';
	  preg_match_all('/[\D]*(\d*)/',$datetime,$matches);
	  $timestamp = $matches[1][2];
	  $timestamp .= '-';
	  $timestamp .= strlen($matches[1][1])<2?'0'.$matches[1][1]:$matches[1][1];
	  $timestamp .= '-';
	  $timestamp .= strlen($matches[1][0])<2?'0'.$matches[1][0]:$matches[1][0];
	  return $timestamp;
	}
	
	// level 1 - jobTeamType
	// 1 - tech: alone
	// 2 - tech: & tech
	// 3 - tech: & helper
	// 4 - helper
	
	// level 2 - jobUserType
	// 1 - new (technician)
	// 2 - old (technician)
	// 3 - gas fitter
		
	function getCarpetCommission() {
		return array(
		"1" =>array(
			"1" => array("booking"=>16,"sale"=>21),
			"2" => array("booking"=>18,"sale"=>28),
			"3" => array("booking"=>18,"sale"=>28),
			),
		"2" =>array(
			"1" => array("booking"=>8,"sale"=>10.5),
			"2" => array("booking"=>9,"sale"=>16),
			"3" => array("booking"=>9,"sale"=>16),
			),
		"3" =>array(
			"1" => array("booking"=>16,"sale"=>16),
			"2" => array("booking"=>18,"sale"=>25),
			"3" => array("booking"=>18,"sale"=>25),
			),
		"4" =>array(
			"1" => array("sale"=>10),
			"2" => array("sale"=>10),
			"3" => array("sale"=>10),
			)
		);
	}
	
	function getFurnaceCommission() {
		return array(
		"1" =>array(
			"1" => array("booking"=>16,"sale"=>21),
			"2" => array("booking"=>18,"sale"=>28),
			"3" => array("booking"=>18,"sale"=>28),
			),
		"2" =>array(
			"1" => array("booking"=>8,"sale"=>10.5),
			"2" => array("booking"=>9,"sale"=>16),
			"3" => array("booking"=>9,"sale"=>16),
			),
		"3" =>array(
			"1" => array("booking"=>16,"sale"=>16),
			"2" => array("booking"=>18,"sale"=>25),
			"3" => array("booking"=>18,"sale"=>28),
			),
		"4" =>array(
			"1" => array("sale"=>10),
			"2" => array("sale"=>10),
			"3" => array("sale"=>10),
			)
		);
	}
	// $jobTeamType - 1 - tech: alone,tech: & tech,tech: & helper,helper
	// $userType -1 - new (technician),2- old (technician), 3 - gas fitter
	// $commissionType - carpet,furnace
	function calculeteCarpetCommission($booking_amount,$sale_amount,$jobTechType=0,$jobUserType=0,$commissionType="carpet") {
		if( $jobTechType==0 || $jobUserType == 0 ) return "invalid jobTechType or userType";
		
		$returnValue = array("booking"=>0,"sale"=>0,"total"=>0,"formula"=>"");
		$percent = 0;
		
		if( $commissionType == "carpet" ) {
			$tmp = $this->getCarpetCommission();
			$percent = $tmp[$jobTechType][$jobUserType];
		} else {
			$tmp = $this->getFurnaceCommission();
			$percent = $tmp[$jobTechType][$jobUserType];
		}
		$tmp = $this->getCarpetCommission();
		
		if( $percent['booking'] > 0 ) {
			$returnValue['booking'] = (($booking_amount*$percent['booking'])/100);
			$returnValue['formula'] = $percent['booking']."% b";
		}
		
		if( $percent['sale'] > 0 ) {
			$returnValue['sale'] = (($sale_amount*$percent['sale'])/100);
			$returnValue['formula'] .= " + ".$percent['sale']."% s";
		}
		//echo '<pre>';print_r($returnValue);die();
		$returnValue['total'] = ($returnValue['booking']+$returnValue['sale']);
		
		if( $jobTechType == 3 ){
			$returnValue['total'] -= 10;
			$returnValue['formula'] .= " -10";
		} 
		
		if( $jobTechType == 4 ){
			$returnValue['total'] += 10;
			$returnValue['formula'] .= " +10";
		}
		
		return $returnValue;
	}
	
	
	 function getLoggedUserID() {
  	global $_SESSION;
  	return ( $_SESSION['user']['id'] > 0  ? $_SESSION['user']['id'] : 0);
  }
  function getLoggedUserRoleID() {
  	global $_SESSION;
  	return ( $_SESSION['user']['role_id'] > 0  ? $_SESSION['user']['role_id'] : 0);
  }
  
  function preparePhone($str)
  {
  	preg_match_all( "/(\d+)/i", $str, $matches );
  	return join('',$matches[0]) ;
  }
  function prepareZip($str)
  {
  	return strtoupper(str_replace(" ","",$str));
  }
  function displayPhone($str){
	  if( trim($str) != '' ) { 
		  $str1 = substr($str, 0, 3);
			$str2 = substr($str, 3, 3);
			return $str1.'-'.$str2.'-'.substr($str, 6, strlen($str));
		}
  }
  
  function displayZip($str){
  	if( trim($str) != '' ) {
	  	return substr($str, 0, 3).' '.substr($str, 3, strlen($str));
	  }
  }
  
  function getOrderItemCategories($t,$categoryNames){
		$returnValue = '';
		//$i = 0;
		$without = array();
		foreach ($t['BookingItem'] as $ti) {
			if( !in_array($ti['item_category_id'], $without) ) {
				$without[] = $ti['item_category_id'];
				$name = $categoryNames[$ti['item_category_id']];
				$name = substr($name, 0, stripos($name, ' '));
				$returnValue .= (strlen($returnValue)  > 0 ? '<br>' : '' ).$name;
			}
		}
		
		return $returnValue;
	}
}
?>