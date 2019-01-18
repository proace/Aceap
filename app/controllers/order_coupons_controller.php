<?
error_reporting(0);
class OrderCouponsController extends AppController
{
	var $name = "OrderCoupons";
	var $uses = array('OrderCoupon','Order');
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
		$sqlOrder .= 'OrderCoupon.id ';
		
		$conditions = array();
		if( $_GET['fsearch'] != "" ) {
			$conditions["OrderCoupon.name"] = "LIKE %". $_GET['fsearch']."%";
		}
			
		$this->Common->pagination($this->OrderCoupon->findCount($conditions),$_GET['currentPage'],$this->itemsToShow,$this->pagesToDisplay);
    
    $this->set('ordercoupons', $this->OrderCoupon->findAll($conditions,'',$sqlOrder,$this->itemsToShow,$_GET['currentPage']+1));
	}
	
	function addedit()
  {
      if (!empty($this->data))
      {
          $this->OrderCoupon->id = $this->data['id'];
          
          $this->data['OrderCoupon']['start_date'] =  $this->data['OrderCoupon']['start_date_year'].'-'. $this->data['OrderCoupon']['start_date_month'].'-'. $this->data['OrderCoupon']['start_date_day'];
          $this->data['OrderCoupon']['end_date'] =  $this->data['OrderCoupon']['end_date_year'].'-'. $this->data['OrderCoupon']['end_date_month'].'-'. $this->data['OrderCoupon']['end_date_day'];
         
          if ($this->OrderCoupon->save($this->data['OrderCoupon']))
          {
            $this->redirect("order_coupons/index");
						exit();
          }
      }
      
      if( $this->params['url']['id'] > 0) {
      	$this->OrderCoupon->id = $this->params['url']['id'];
        $this->data = $this->OrderCoupon->read();
      }
      
     $items = $this->Order->findAll();
      foreach( $items as $item ) {
      	$items4select[$item['Order']['id']] = $item['Order']['id'];
      }
     
      $this->set('Order',$items4select);
  }
	
}
?>
