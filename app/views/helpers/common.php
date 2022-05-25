<?
class CommonHelper extends Helper {

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
}
?>