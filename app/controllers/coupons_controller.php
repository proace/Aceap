<?
error_reporting(0);
class CouponsController extends AppController
{
	var $name = "Coupons";
	var $uses = array('Coupon','Item');
	var $helpers = array('Common');
	var $components = array('Common');
	
	var $itemsToShow = 20;
	var $pagesToDisplay = 10;
	var $beforeFilter = array('checkAccess');

  function checkAccess()
  {
  	//if( $this->action == 'index' ) {
      $this->Common->checkRoles(array('6'));
    //}
  }
  
	function index()
	{
		$sqlOrder .= 'Coupon.id ';
		
		$conditions = array();
		if( $_GET['fsearch'] != "" ) {
			$conditions["Coupon.name"] = "LIKE %". $_GET['fsearch']."%";
		}
			
		$this->Common->pagination($this->Coupon->findCount($conditions),$_GET['currentPage'],$this->itemsToShow,$this->pagesToDisplay);
    
    $this->set('coupons', $this->Coupon->findAll($conditions,'',$sqlOrder,$this->itemsToShow,$_GET['currentPage']+1));
	}
	
	function addedit()
  {
      if (!empty($this->data))
      {
          $this->Coupon->id = $this->data['id'];
          
          $this->data['Coupon']['start_date'] =  $this->data['Coupon']['start_date_year'].'-'. $this->data['Coupon']['start_date_month'].'-'. $this->data['Coupon']['start_date_day'];
          $this->data['Coupon']['end_date'] =  $this->data['Coupon']['end_date_year'].'-'. $this->data['Coupon']['end_date_month'].'-'. $this->data['Coupon']['end_date_day'];
         
          if ($this->Coupon->save($this->data['Coupon']))
          {
            $this->redirect("/coupons/index");
						exit();
          }
      }
      
      if( $this->params['url']['id'] > 0) {
      	$this->Coupon->id = $this->params['url']['id'];
        $this->data = $this->Coupon->read();
      }
      
     
      $items = $this->Item->findAll();
      foreach( $items as $item ) {
      	$items4select[$item['Item']['id']] = $item['Item']['name'];
      }
     
      $this->set('Item',$items4select);
  }
	
}
?>
