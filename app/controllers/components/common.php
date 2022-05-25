<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
class CommonComponent extends Object
{
	function startup(&$controller){
      $this->controller =& $controller;
    }
    /**
		common function to save images
    */
    function saveImages($file, $filename, $degree)
    {
    	// error_reporting(E_ALL);
    	$fileExt = @strtolower(@end(explode('.',$file['name'])));
    	$src = $file['tmp_name'];
		if($fileExt == "png")
		{
		    $img_r =  imagecreatefrompng($src); 
		} 
		else if($fileExt == "gif")
		{
		    $img_r =  imagecreatefromgif($src); 
		} 
		else if($fileExt == "jpeg" || $fileExt == 'jpg')
		{
		    $img_r = imagecreatefromjpeg($src); 
		}

		if(isset($degree))
		{
		    $img_r = imagerotate($img_r, $degree, 0);
		}


		if($fileExt == "png")
		{
		    imagepng($img_r, $filename);
		} 
		else if($fileExt == "gif")
		{
		    imagegif($img_r, $filename);
		} 
		else if($fileExt == "jpeg" || $fileExt == 'jpg')
		{
		    imagejpeg($img_r, $filename);
		} 

    }
     /** common function for save image into ace_rp_orders field payment_image
	  *  Need to pass three parameter $file, $order_id, $config in the component 
      */
    function commonSavePaymentImage($file, $order_id, $config)
    {    	
    	$fileName = time()."_".$file['name'];
		$fileTmpName = $file['tmp_name'];
		$orgFileName = ROOT."/app/webroot/payment-images/".$fileName;
		if($file['error'] == 0)
		{
			//$move = $this->saveImages($file, $orgFileName, 90);
			$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/payment-images/".$fileName);
			$query = "UPDATE ace_rp_orders SET payment_image ='".$fileName."' WHERE id=".$order_id;
			$db =& ConnectionManager::getDataSource($config);

			$result = $db->_execute($query);
			return $result; 
		}
    }
     function uploadTechPurchaseImage($fileName,$temName)
     {
     	$fileName = time()."_".rand().$fileName;
		$fileTmpName = $temName;
		$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/tech-purchase-image/".$fileName);
		return $fileName; 	
     }

    function getOrderDetails($order_id, $config)
    {    	
    	$db =& ConnectionManager::getDataSource($config);
		$query = "SELECT * from  ace_rp_orders WHERE id=".$order_id;
		

		$result = $db->_execute($query);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		return $row; 
    }
    
    //function uploadPhoto($file, $order_id, $config, $i, $customer_id = null)
    	function uploadPhoto($file_name,$file_tmpname, $order_id, $config, $i, $customer_id = null, $fromTech=0, $date='', $label='')
	{
		date_default_timezone_set('America/Los_Angeles');

		$year = date('Y', time());
		if (!file_exists($year)) {
			mkdir('upload_photos/'.$year, 0755);
		}
		$month = date('Y/m', time());
		if (!file_exists($month)) {
			mkdir('upload_photos/'.$month, 0755);
		}

		$day = date('Y/m/d', time());
		if (!file_exists($day)) {
			mkdir('upload_photos/'.$day, 0755);
		}
		$path = $file_name;
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		if($order_id != 0)
		{
			$name = date('Ymdhis', time()).$order_id.$i.'_'.rand().$path;
		} 
		else {
			$name = date('Ymdhis', time()).'_'.rand().$path;
		}
			
		if ( 0 < $file['error'] ) {
	        echo 'Error: ' . $_FILES['image']['error'] . '<br>'; 
	    } else {
			$extensions=array('jpg','jpe','jpeg','jfif','png','bmp','dib','gif');
if(in_array($ext,$extensions)){     
$maxDimW = 1600;
$maxDimH = 1000;
list($width, $height, $type, $attr) = getimagesize( $file_tmpname );
if ( $maxDimW > $maxDimW || $height > $maxDimH ) {
    $target_filename = $file_tmpname;
    $fn = $file_tmpname;
    $size = getimagesize( $fn );
    $ratio = $size[0]/$size[1]; // width/height
    
       $width =$width/2;
       $height=$height/2;
    
    $src = imagecreatefromstring(file_get_contents($fn));
    $dst = imagecreatetruecolor( $width, $height );
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $size[0], $size[1] );

    imagejpeg($dst, $target_filename); // adjust format as needed
	
    


}
}
	        move_uploaded_file($file_tmpname, 'upload_photos/'.$day.'/'.$name);
	    }
				

		

		//$sql = "UPDATE ace_rp_orders SET photo_".$i." = '".$name."' WHERE id = ".$order_id;
		
		$db =& ConnectionManager::getDataSource($config);
		//$sql = "INSERT INTO ace_rp_user_part_images (customer_id, image_name, from_tech,order_id,created_at,type) VALUES (".$customer_id.", '".$name."', ".$fromTech.", ".$order_no.", ".time().", ".$type.")";
		//$result = $db->_execute($sql);
		if(empty($date) || $date==""){
			$date=null;
		}

		$sql = "INSERT INTO ace_rp_user_part_images (customer_id, image_name, from_tech,order_id,date_created,label) VALUES (".$customer_id.", '".$name."', ".$fromTech.", ".$order_id.", '".$date."', '".$label."')";
	    
		$result = $db->_execute($sql);
		$result1 = $db->_execute("update ace_rp_user_part_images set label='$label' WHERE customer_id=$customer_id and date_created ='$date'");
		return $result;
	}
	//# LOKI- Techinician upload pictures from commission page.
	function techUploadPhoto($imageName, $imageTempName, $order_id, $config, $i)
	{
		date_default_timezone_set('America/Los_Angeles');

		$year = date('Y', time());
		if (!file_exists($year)) {
			mkdir('upload_photos/'.$year, 0755);
		}
		$month = date('Y/m', time());
		if (!file_exists($month)) {
			mkdir('upload_photos/'.$month, 0755);
		}

		$day = date('Y/m/d', time());
		if (!file_exists($day)) {
			mkdir('upload_photos/'.$day, 0755);
		}
		$path = $imageName;
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		$name = date('Ymdhis', time()).$order_id.$i.'.'.$ext;

		if ( 0 < $file['error'] ) {
	        // echo 'Error: ' . $_FILES['image']['error'] . '<br>'; 
	    } else {
	        move_uploaded_file($imageTempName, 'upload_photos/'.$day.'/'.$name);
	    }

		$sql = "UPDATE ace_rp_orders SET photo_".$i." = '".$name."' WHERE id = ".$order_id;
		$db =& ConnectionManager::getDataSource($config);
		$result = $db->_execute($sql);

		return $result;
	}
	// Upload payment image from technician commision page.
	function TechCommonSavePaymentImage($imageName, $imageTempName, $order_id, $config)
    {    	
    	$fileName = time()."_".$imageName;
		$fileTmpName = $imageTempName;
		$orgFileName = ROOT."/app/webroot/payment-images/".$fileName;
		
			//$move = $this->saveImages($file, $orgFileName, 90);
		$move = move_uploaded_file($fileTmpName ,ROOT."/app/webroot/payment-images/".$fileName);
		$query = "UPDATE ace_rp_orders SET payment_image ='".$fileName."' WHERE id=".$order_id;
		$db =& ConnectionManager::getDataSource($config);

		$result = $db->_execute($query);
		return $result; 										
    }
    /** end common function for save image into ace_rp_orders field payment_image*/
	function getMenuItems()
	{
		$roleID = (integer)$this->getLoggedUserRoleID();
		$userID = (integer)$this->getLoggedUserID();
		$menu = array();	
		if ($roleID == 6)	
		// FULL ADMINISTRATORS: ALI, SANAZ
		// Tab = 'Referrals'
		{

			$menu = array(

				'tabs' => array('Bookings','Inventory','Reports','Admin','Techs','Payroll','Call Center','Financial', 'Template','Support'),

				'content' =>

				array(

					array(

						array(

							'name' => 'New Client',

							'url' => BASE_URL.'/orders/editBooking?customer_id=&order_id=0',

							'img' => 'icon-lg-customers.png'

						),

						array(

							'name' => 'Schedule',

							'url' => BASE_URL.'/orders/scheduleView',

							'img' => 'icon-lg-schedule.png'

						),
						array(

							'name' => 'Call To Book',

							'url' => '#',

							'onclick' => 'opencb();return false;',

							'img' => 'icon-lg-callback.png',

							'target' => '_self'

						),
						array(

							'name' => 'Estimates',

							'url' => BASE_URL.'/reports/estimates',

							'img' => 'icon-lg-all-bookings.png'

						),
						array(

							'name' => 'Chat1',
							// 'url' => 'http://support.acecare.ca/lhc_web/index.php/site_admin/',
							'url' => 'http://support.hvacproz.ca/lhc_web/index.php/site_admin/',
							'img' => 'comments.png'
						),

						array(
							'name' => 'Organiser',
							'url' => BASE_URL.'/settings/orgniser',
							'img' => 'icon-lg-jobs.png'
						),

						array(

							'name' => 'Monitoring',

							'url' => BASE_URL.'/users/userMonitoring',

							'img' => 'investigation12.png'

						),
						array(

							'name' => 'Failed Email',

							'url' => BASE_URL.'/orders/showFailedEmail',

							'img' => 'icon-lg-all-bookings.png'

						),
						array(

							'name' => 'Booking Requests',

							'url' => BASE_URL.'/orders/showUserResponse',

							'img' => 'icon-lg-all-bookings.png'

						),
						// array(

						// 	'name' => 'Received Email',

						// 	'url' => BASE_URL.'/users/showUserEmailResponse',

						// 	'img' => 'icon-lg-all-bookings.png'

						// ),
						
						array(

							'name' => 'Follow up',

							'url' => BASE_URL.'/reports/orderReports',

							'img' => 'icon-lg-all-bookings.png'
						),
						
						array(
							'name' => 'GPS',
							// 'url' => 'http://support.acecare.ca/lhc_web/index.php/site_admin/',
							'url' => 'https://locate.positrace.com/en/?share_token=7d634e0bc7392c8a1403a330c10e4353',
							'img' => 'location1.png',
							'target' => '_blank'
						),
						array(

							'name' => 'Suppliers',

							'url' => BASE_URL.'/suppliers',

							'img' => 'icon-lg-technician.png'

						)
					),

					array(

						// array(

						// 	'name' => 'Warehouse',

						// 	'url' => BASE_URL.'/inventories/index',

						// 	'img' => 'icon-lg-inventory.png'

						// ),

						// array(

						// 	'name' => 'Transactions',

						// 	'url' => BASE_URL.'/inventories/AllDocuments',

						// 	'img' => 'icon-lg-inventory-moves.png'

						// ),

						array(

							'name' => 'Purchase',

							'url' => BASE_URL.'/inventories/supplies',

							'img' => 'icon-lg-inventory-moves.png'

						),

						array(

							'name' => 'Transactions',

							'url' => BASE_URL.'/inventories/techs',

							'img' => 'icon-lg-inventory-moves.png'

						),					

						array(

							'name' => 'Truck Default Items',

							'url' => BASE_URL.'/inventories/editDefault',

							'img' => 'icon-lg-jobs.png'

						),
						array(

							'name' => 'Move Items',

							'url' => BASE_URL.'/inventories/moveTechItem',

							'img' => 'icon-lg-jobs.png'

						),
						array(

							'name' => 'Suppliers',

							'url' => BASE_URL.'/suppliers',

							'img' => 'icon-lg-technician.png'

						),

						array(

							'name' => 'Item Inventory',

							'url' => BASE_URL.'/iv_items/',

							'img' => 'icon-lg-price.png'

						),

						// array(
						// 	'name' => 'Item Count',
						// 	'url' => BASE_URL.'/calls/inventorylocationscount',
						// 	'img' => 'icon-lg-price.png',
						// 	'target' => '_top'
						// ),
					
						array(
							'name' => 'Main Category',
							'url' => BASE_URL.'/iv_categories/showItemCategory',
							'img' => 'icon-lg-price.png',
						),
						array(
							'name' => 'Category',
							'url' => BASE_URL.'/iv_categories/showItemMidCategory',
							'img' => 'icon-lg-price.png',
						),
						array(
							'name' => 'Sub Category',
							'url' => BASE_URL.'/iv_categories/showItemSubCategory',
							'img' => 'icon-lg-price.png',
						),
						array(
							'name' => 'Import Item',
							'url' => BASE_URL.'/iv_items/importItem',
							'img' => 'icon-lg-price.png',
						),
						array(

							'name' => 'Part Requests',

							'url' => BASE_URL.'/inventories/requests',

							'img' => 'icon-lg-inventory-moves.png'

						),
						//,
						// array(
						// 	'name' => 'Dialer',
						// 	'url' => BASE_URL.'/calls',
						// 	'img' => 'icon-lg-callback.png',
						// 	'target' => '_top'
						// )

					),

					array(

						array(

							'name' => 'Booking Report',

							'url' => BASE_URL.'/orders/?action=view&newSearch=1&ffromdate='.date('Y-m-d').'&fsource_id='.$userID,

							'img' => 'icon-lg-jobs.png'

						),

						array(

							'name' => 'Telem Rating',

							'url' => BASE_URL.'/reports/telemVsTech',

							'img' => 'icon-lg-all-bookings.png'

						),
						array(

							'name' => 'Telem Summary',

							'url' => BASE_URL.'/reports/telemarketers_summary',

							'img' => 'icon-lg-all-bookings.png'

						),
						array(

							'name' => 'Monthly Summary',

							'url' => BASE_URL.'/reports/sales_monthly',

							'img' => 'icon-lg-report.png'

						),

						array(

							'name' => 'Profit',

							'url' => BASE_URL.'/reports/sales',

							'img' => 'icon-lg-report.png'

						),

						array(

							'name' => 'Tech Work',

							'url' => BASE_URL.'/reports/technicians_summary',

							'img' => 'icon-lg-all-bookings.png'

						),
						array(

							'name' => 'Members Report',

							'url' => BASE_URL.'/users/search',

							'img' => 'icon-lg-customers.png'

						),

						array(

							'name' => 'Customers',

							'url' => BASE_URL.'/customers/canvassers',

							'img' => 'icon-lg-customers.png',

						),
						array(

							'name' => 'Feedbacks',

							'url' => BASE_URL.'/orders/feedbacks_list',

							'img' => 'icon-lg-feedbacks.png'

						),

						array(

							'name' => 'Feedback Display',

							'url' => BASE_URL.'/orders/feedbackView',

							'img' => 'icon-lg-feedbacks.png'

						),
						array(

							'name' => 'Reminder Summary',

							'url' => BASE_URL.'/reports/reminderSummary',

							'img' => 'icon-lg-report.png'

						),
						array(

							'name' => 'Print summery',

							'url' => BASE_URL.'/technicians/',

							'img' => 'icon-lg-report.png'
						),

					),
					array(

						array(

							'name' => 'Users',

							'url' => BASE_URL.'/users?view_mode=users',

							'img' => 'icon-lg-technician.png'

						),

						array(

							'name' => 'Job Types',

							'url' => BASE_URL.'/jobs',

							'img' => 'icon-lg-jobs.png'

						),
						array(

							'name' => 'Item Categ.',

							'url' => BASE_URL.'/tableeditor?table=ace_rp_item_categories',

							'img' => 'icon-lg-jobs.png'

						),

						array(

							'name' => 'Invoice Questions',

							'url' => BASE_URL.'/orders/editInvoiceQuestions',

							'img' => 'icon-lg-jobs.png'

						),

						
						array(

							'name' => 'Maintenance Tables',

							'url' => BASE_URL.'/maintenance/index',

							'img' => 'icon-lg-jobs.png'

						),

						array(

							'name' => 'City',

							'url' => BASE_URL.'/settings/showCities',

							'img' => 'icon-lg-jobs.png'

						),

						array(

							'name' => 'Time option',

							'url' => BASE_URL.'/settings/timeOption',

							'img' => 'icon-lg-jobs.png'

						),


						array(

							'name' => 'Gallery',

							'url' => 'http://hvacproz.ca/acesys/filemanager/dialog.php',

							'img' => 'edit_18.png'

						)
					),

					array(

						array(

							'name' => 'Edit Routes',

							'url' => BASE_URL.'/routes/index',

							'img' => 'icon-lg-jobs.png'

						),

						array(

							'name' => 'Techs',

							'url' => BASE_URL.'/commissions/index',

							'img' => 'icon-lg-customers.png'

						),

						array(

							'name' => 'Commissions',

							'url' => BASE_URL.'/commissions/calculateCommissions',

							'img' => 'icon-lg-price.png'

						),

						array(

							'name' => 'Daily Summary',

							'url' => BASE_URL.'/commissions/techSummary',

							'img' => 'icon-lg-report.png'

						),

						array(

							'name' => 'Commission Summary',

							'url' => BASE_URL.'/commissions/summary',

							'img' => 'icon-lg-report.png'

						),

						array(

							'name' => 'Techs Schedule',

							'url' => BASE_URL.'/tech_schedule/index',

							'img' => 'icon-lg-customers.png'

						),

						array(

							'name' => 'Daily Invoice Summary',

							'url' => BASE_URL.'/reports/dailyInvoices',

							'img' => 'icon-lg-report.png'

						),
						array(

							'name' => 'Payment-Image',
							'url' => BASE_URL.'/orders/showPaymentImages',
							
							'img' => 'images1.png'
						)
						,
						array(

							'name' => 'Email Setup',
							'url' => BASE_URL.'/commissions/showDefaultCommissionEmail',
							'img' => 'email1.png'
							
						)
						,
						array(

							'name' => 'Tech Report',
							'url' => BASE_URL.'/commissions/chartreport',
							'img' => 'icon-lg-report.png',
						),
						array(

							'name' => 'Night Shift',
							'url' => BASE_URL.'/tech_schedule/night_shift',
							'img' => 'icon-lg-report.png',
						),

						array(

							'name' => 'Tech Purchase',
							'url' => BASE_URL.'/commissions/techPurchase',
							'img' => 'icon-lg-customers.png',
						)

					),

					array(

						array(

							'name' => 'Pay Periods',

							'url' => BASE_URL.'/payrolls/pay_periods',

							'img' => 'icon-lg-jobs.png'

						),

						array(

							'name' => 'Settings',

							'url' => BASE_URL.'/payrolls/editEmployees',

							'img' => 'icon-lg-technician.png'

						),

						array(

							'name' => 'Time Sheet',

							'url' => BASE_URL.'/payrolls/time_sheet',

							'img' => 'icon-lg-enter-work-done.png'

						),

						array(

							'name' => 'Daily&nbsp;Hours',

							'url' => BASE_URL.'/payrolls/time_daily',

							'img' => 'icon-lg-enter-work-done.png'

						),

						array(

							'name' => 'Booking Bonus',

							'url' => BASE_URL.'/payrolls/special_bonuses',

							'img' => 'icon-lg-price.png'

						),

						array(

							'name' => 'Payroll',

							'url' => BASE_URL.'/payrolls/view_payroll',

							'img' => 'icon-lg-price.png'

						),

						array(

							'name' => 'Payment Type',

							'url' => BASE_URL.'/payments/showPaymentTypes',

							'img' => 'icon-lg-price.png'

						)


					),

					array(

						array(

							'name' => 'Board',

							'url' => BASE_URL.'/reports/telem_board',

							'img' => 'icon-lg-report.png'

						),

						array(

							'name' => 'Board with Groups',

							'url' => BASE_URL.'/reports/telem_board_by_group',

							'img' => 'icon-lg-report.png'

						),

						array(

							'name' => 'Group Setting',

							'url' => BASE_URL.'/groups/',

							'img' => 'icon-lg-technician.png'

						),

						array(

							'name' => 'Calls',

							'url' => BASE_URL.'/reports/calls_summary',

							'img' => 'icon-lg-all-bookings.png'

						),

						array(

							'name' => 'Cancellations',

							'url' => BASE_URL.'/reports/cancellations',

							'img' => 'icon-lg-all-bookings.png'

						),



						array(

							'name' => 'User Feedbacks',

							'url' => BASE_URL.'/reports/customerFeedbacks',

							'img' => 'icon-lg-all-bookings.png'

						),

						array(

							'name' => 'Settings',

							'url' => BASE_URL.'/settings/generalSettings',

							'img' => 'icon-lg-all-bookings.png'

						),
						array(

							'name' => 'Pitch',

							'url' => BASE_URL.'/orders/editPitch',

							'img' => 'voice.jpg'

						),

						array(

							'name' => 'New Campaing',

							'url' => BASE_URL.'/reports/callback_summary',

							'img' => 'icon-lg-all-bookings.png'

						),
						array(

							'name' => 'Campaing',

							'url' => BASE_URL.'/orders/campaing',

							'img' => 'icon-lg-callback.png'

						),

						array(

							'name' => 'Campaing List',

							'url' => BASE_URL.'/orders/campaing_list',

							'img' => 'icon-lg-callback.png'

						)
						,
						array(

							'name' => 'Campaing Report',

							'url' => BASE_URL.'/customers/campaingReport',

							'img' => 'icon-lg-customers.png',

						)

					),
					array(
						array(

								'name' => 'Payment Summary',

								'url' => BASE_URL.'/reports/payments_summary',

								'img' => 'icon-lg-all-bookings.png'
							),
							array(

								'name' => 'Payments',

								'url' => BASE_URL.'/payments/index',

								'img' => 'icon-lg-price.png'
							),
							array(
								'name' => 'Payment Report',

								'url' => BASE_URL.'/reports/monthlyPaymentReport',

								'img' => 'icon-lg-all-bookings.png'
							),
							array(
								'name' => 'Bank Reconciliation',

								'url' => BASE_URL.'/reports/cardPaymentSummary',

								'img' => 'icon-lg-all-bookings.png'
							),array(

							'name' => 'Edit Inventory Payment',

							'url' => BASE_URL.'/payments/ShowPaymentMethod',

							'img' => 'icon-lg-all-bookings.png'
						)
							
                      ),
					array (
						array(

							'name' => 'Booking Template',

							'url' => BASE_URL.'/settings/edit?title=email_template_bookingnotification',

							'img' => 'icon-lg-mail.png'

						),

						array(

							'name' => 'Email Invoice/Review Template',

							'url' => BASE_URL.'/settings/editNewsletter?title=email_template_custom',

							'img' => 'icon-lg-mail.png'

						),

						array(

							'name' => 'Coupon Template',

							'url' => BASE_URL.'/settings/editNewsletter?title=coupon_template',

							'img' => 'icon-lg-mail.png'

						),

						array(

							'name' => 'Portfolio Template',

							'url' => BASE_URL.'/settings/editNewsletter?title=portfolio_template',

							'img' => 'icon-lg-mail.png'

						),

						array(

							'name' => 'Reminder 7-Days',

							'url' => BASE_URL.'/settings/edit?title=email_template_jobnotification',

							'img' => 'icon-lg-mail.png'

						),

						array(

							'name' => 'Blast Email Template',

							'url' => BASE_URL.'/settings/editBulkEmail?title=bulk_email',

							'img' => 'icon-lg-mail.png'

						),
						array(

							'name' => 'GLS Invoice Temp',

							'url' => BASE_URL.'/settings/edit?title=gls_invoice_temp',

							'img' => 'icon-lg-mail.png'

						),
						array(

							'name' => 'Membership Template',

							'url' => BASE_URL.'/settings/editBulkEmail?title=membership',

							'img' => 'icon-lg-mail.png'

						),

						array(

							'name' => 'Text Booking Template',

							'url' => BASE_URL.'/settings/edit?title=booking_sms',

							'img' => 'icon-lg-mail.png'

						),
						array(

							'name' => 'Blast Text Template',

							'url' => BASE_URL.'/settings/edit?title=bulk_sms',

							'img' => 'icon-lg-mail.png'

						),
						array(

							'name' => 'Cancelled Text Template',

							'url' => BASE_URL.'/settings/edit?title=cancel_booking_sms',

							'img' => 'icon-lg-mail.png'

						),
						array(

							'name' => 'Reminder Text Template',

							'url' => BASE_URL.'/settings/edit?title=reminder_text',

							'img' => 'icon-lg-mail.png'

						),
						array(

							'name' => 'Review Template',

							'url' => BASE_URL.'/settings/edit?title=review_text',

							'img' => 'icon-lg-mail.png'

						),

						array(

							'name' => 'Time Confirmation',

							'url' => BASE_URL.'/settings/edit?title=email_template_booking_time_cofirmation',

							'img' => 'icon-lg-mail.png'

						),

						array(

							'name' => 'Contract Pdf',

							'url' => BASE_URL.'/settings/edit?title=pdf_template',

							'img' => 'icon-lg-mail.png'

						),

						array(

							'name' => 'Tech Invoice',

							'url' => BASE_URL.'/settings/edit?title=tech_invoice',

							'img' => 'icon-lg-mail.png'

						),

						array(

							'name' => 'Tech Purchase Invoice',

							'url' => BASE_URL.'/settings/edit?title=tech_purchase_invoice',

							'img' => 'icon-lg-mail.png'

						),


						array(

							'name' => 'New Permit',

							'url' => BASE_URL.'/settings/edit?title=permit_template',

							'img' => 'icon-lg-mail.png'
						),

						array(

							'name' => 'Failed Permit',

							'url' => BASE_URL.'/settings/edit?title=failed_permit_template',

							'img' => 'icon-lg-mail.png'
						)

					),
					array(		
						array(
							'name' => 'Tech Support',
							'url' => BASE_URL.'/settings/showTrainingCategory',
							'img' => 'icon-lg-jobs.png',
						)
						,
						array(
							'name' => 'Admin Support',
							'url' => BASE_URL.'/settings/showAdminTrainingCategory',
							'img' => 'icon-lg-jobs.png',
						),
						array(
							'name' => 'Techs Training',
							'url' => BASE_URL.'/settings/showTechTrainingCategory',
							'img' => 'icon-lg-jobs.png',
						),
						array(
								'name' => 'Agent Training',
								'url' => BASE_URL.'/settings/showAgentTrainingCategory',
								'img' => 'icon-lg-jobs.png',
							),
						// array(
						// 	'name' => 'Admin Training',
						// 	'url' => BASE_URL.'/settings/showAdminTraining',
						// 	'img' => 'icon-lg-jobs.png',
						// ),
						// array(
						// 	'name' => 'Agents Training',
						// 	'url' => BASE_URL.'/settings/showAgentsTraining',
						// 	'img' => 'icon-lg-jobs.png',
						// )
						
						// ,
						// array(
						// 	'name' => 'Sub Category',
						// 	'url' => BASE_URL.'/settings/showTrainingSubCategory',
						// 	'img' => 'icon-lg-jobs.png',
						// ),
						// array(
						// 	'name' => 'Second Sub Category',
						// 	'url' => BASE_URL.'/settings/showTrainingSecondSubCategory',
						// 	'img' => 'icon-lg-jobs.png',
						// )
					),
					array(
						array(

								'name' => 'Referrals',

								'url' => BASE_URL.'/orders/referrals',

								'img' => 'icon-lg-all-bookings.png'

							)
                      )
				)

			);

		}
		elseif (($userID == 52249)||($userID == 146936) || $userID == 231307)	// ACCOUNTANT: MARIA FLOR
		{
			$menu = array(
				'tabs' => array('Bookings','Inventory','Reports','Admin','Techs','Payroll'),
				'content' =>
				array(
					array(
						array(
							'name' => 'New Client',
							'url' => BASE_URL.'/orders/editBooking?action_type=callback',
							'img' => 'icon-lg-customers.png'
						),
						array(
							'name' => 'Schedule',
							'url' => BASE_URL.'/orders/scheduleView',
							'img' => 'icon-lg-schedule.png'
						),
						array(
							'name' => 'Feedbacks',
							'url' => BASE_URL.'/orders/feedbacks_list',
							'img' => 'icon-lg-feedbacks.png'
						),
						array(
							'name' => 'Feedback Display',
							'url' => BASE_URL.'/orders/feedbackView',
							'img' => 'icon-lg-feedbacks.png'
						),
						array(
							'name' => 'Installations',
							'url' => BASE_URL.'/orders/installations',
							'img' => 'icon-lg-all-bookings.png'
						),
						array(
							'name' => 'Payments',
							'url' => BASE_URL.'/payments/index',
							'img' => 'icon-lg-price.png'
						),
						array(
							'name' => 'ACE Web Site',
							'url' => 'http://www.acecare.ca/',
							'img' => 'icon-ace.gif',
							'target' => '_blank'
						),
						array(
							'name' => 'Payment Summary',
							'url' => BASE_URL.'/reports/payments_summary',
							'img' => 'icon-lg-all-bookings.png'
						)
					),
					array(
						array(
							'name' => 'Status',
							'url' => BASE_URL.'/inventories/index',
							'img' => 'icon-lg-inventory.png'
						),
						array(
							'name' => 'Transactions',
							'url' => BASE_URL.'/inventories/AllDocuments',
							'img' => 'icon-lg-inventory-moves.png'
						),
						array(
							'name' => 'Purchase',
							'url' => BASE_URL.'/inventories/supplies',
							'img' => 'icon-lg-inventory-moves.png'
						),
						array(
							'name' => 'Techs',
							'url' => BASE_URL.'/inventories/techs',
							'img' => 'icon-lg-inventory-moves.png'
						),
						array(
							'name' => 'Part Requests',
							'url' => BASE_URL.'/inventories/requests',
							'img' => 'icon-lg-inventory-moves.png'
						),
						array(
							'name' => 'Edit Defaults',
							'url' => BASE_URL.'/inventories/editDefault',
							'img' => 'icon-lg-jobs.png'
						),
						array(
							'name' => 'Suppliers',
							'url' => BASE_URL.'/suppliers',
							'img' => 'icon-lg-technician.png'
						),
						/*array(
							'name' => 'Items',
							'url' => BASE_URL.'/items',
							'img' => 'icon-lg-price.png'
						),*/
						array(
							'name' => 'Item Inventory',
							'url' => BASE_URL.'/iv_items/',
							'img' => 'icon-lg-price.png'
						),
						array(
							'name' => 'Item Count',
							'url' => BASE_URL.'/calls/inventorylocationscount',
							'img' => 'icon-lg-price.png',
							'target' => '_top'
						),
						array(
							'name' => 'Main Category',
							'url' => BASE_URL.'/iv_categories/showItemCategory',		
							'img' => 'icon-lg-price.png',
						),
						array(
							'name' => 'Category',
							'url' => BASE_URL.'/iv_categories/showItemMidCategory',
							'img' => 'icon-lg-price.png',
						),
						array(
							'name' => 'Sub Category',
							'url' => BASE_URL.'/iv_categories/showItemSubCategory',
							'img' => 'icon-lg-price.png',
						),
						array(
							'name' => 'Import Item',
							'url' => BASE_URL.'/iv_items/importItem',
							'img' => 'icon-lg-price.png',
						),
						array(
							'name' => 'Dialer',
							'url' => BASE_URL.'/calls',
							'img' => 'icon-lg-callback.png',
							'target' => '_top'
						)
						
					),
					array(
						array(
							'name' => 'Monthly Summary',
							'url' => BASE_URL.'/reports/sales_monthly',
							'img' => 'icon-lg-report.png'
						),
						array(
							'name' => 'Profit',
							'url' => BASE_URL.'/reports/sales',
							'img' => 'icon-lg-report.png'
						),
						
						array(
							'name' => 'Booking Report',
							'url' => BASE_URL.'/orders/?action=view&newSearch=1&ffromdate='.date('Y-m-d').'&fsource_id='.$userID,
							'img' => 'icon-lg-jobs.png'
						),
						array(
							'name' => 'Tech Work',
							'url' => BASE_URL.'/reports/technicians_summary',
							'img' => 'icon-lg-all-bookings.png'
						),
						array(
							'name' => 'Telem Work',
							'url' => BASE_URL.'/orders/?action=view&newSearch=1&ffromdate='.date('Y-m-d'),
							'img' => 'icon-lg-all-bookings.png'
						),
						array(
							'name' => 'Board',
							'url' => BASE_URL.'/reports/telem_board',
							'img' => 'icon-lg-report.png'
						),
						array(
							'name' => 'Board with Groups',
							'url' => BASE_URL.'/reports/telem_board_by_group',
							'img' => 'icon-lg-report.png'
						),
						array(
							'name' => 'Calls',
							'url' => BASE_URL.'/reports/calls_summary',
							'img' => 'icon-lg-all-bookings.png'
						),
						array(
							'name' => 'Cancellations',
							'url' => BASE_URL.'/reports/cancellations',
							'img' => 'icon-lg-all-bookings.png'
						),
						array(
							'name' => 'Estimates',
							'url' => BASE_URL.'/reports/estimates',
							'img' => 'icon-lg-all-bookings.png'
						),
						array(
							'name' => 'Telem Summary',
							'url' => BASE_URL.'/reports/telemarketers_summary',
							'img' => 'icon-lg-all-bookings.png'
						)
					),
					array(
						array(
							'name' => 'Users',
							'url' => BASE_URL.'/users?view_mode=users',
							'img' => 'icon-lg-technician.png'
						),
						array(
							'name' => 'Job Types',
							'url' => BASE_URL.'/jobs',
							'img' => 'icon-lg-jobs.png'
						),
						array(
							'name' => 'Item Categ.',
							'url' => BASE_URL.'/tableeditor?table=ace_rp_item_categories',
							'img' => 'icon-lg-jobs.png'
						),
						array(
							'name' => 'Maintenance Tables',
							'url' => BASE_URL.'/maintenance/index',
							'img' => 'icon-lg-jobs.png'
						),
						array(
							'name' => 'Settings',
							'url' => BASE_URL.'/settings/generalSettings',
							'img' => 'icon-lg-all-bookings.png'
						)
					),
					array(
						array(
							'name' => 'Edit Routes',
							'url' => BASE_URL.'/routes/index',
							'img' => 'icon-lg-jobs.png'
						),
						array(
							'name' => 'Techs',
							'url' => BASE_URL.'/commissions/index',
							'img' => 'icon-lg-customers.png'
						),
						array(
							'name' => 'Commissions',
							'url' => BASE_URL.'/commissions/calculateCommissions',
							'img' => 'icon-lg-price.png'
						),
						array(
							'name' => 'Test Commissions',
							'url' => BASE_URL.'/commissions/allCommissions',
							'img' => 'icon-lg-price.png'
						),
						array(
							'name' => 'Daily Summary',
							'url' => BASE_URL.'/commissions/techSummary',
							'img' => 'icon-lg-report.png'
						),
						array(
							'name' => 'Commission Summary',
							'url' => BASE_URL.'/commissions/summary',
							'img' => 'icon-lg-report.png'
						),
						array(
							'name' => 'Techs Schedule',
							'url' => BASE_URL.'/tech_schedule/index',
							'img' => 'icon-lg-customers.png'
						)
					),
					array(
						array(
							'name' => 'Pay Periods',
							'url' => BASE_URL.'/payrolls/pay_periods',
							'img' => 'icon-lg-jobs.png'
						),
						array(
							'name' => 'Settings',
							'url' => BASE_URL.'/payrolls/editEmployees',
							'img' => 'icon-lg-technician.png'
						),
						array(
							'name' => 'Time Sheet',
							'url' => BASE_URL.'/payrolls/time_sheet',
							'img' => 'icon-lg-enter-work-done.png'
						),
						array(
							'name' => 'Daily&nbsp;Hours',
							'url' => BASE_URL.'/payrolls/time_daily',
							'img' => 'icon-lg-enter-work-done.png'
						),
						array(
							'name' => 'Booking Bonus',
							'url' => BASE_URL.'/payrolls/special_bonuses',
							'img' => 'icon-lg-price.png'
						),
						array(
							'name' => 'Payroll',
							'url' => BASE_URL.'/payrolls/view_payroll',
							'img' => 'icon-lg-price.png'
						),
						array(
							'name' => 'Custom Payroll',
							'url' => BASE_URL.'/payrolls/customPayroll',
							'img' => 'icon-lg-price.png'
						)

					),
					array(
						array(
							'name' => 'Boards',
							'url' => BASE_URL.'/payrolls/pay_periods',
							'img' => 'icon-lg-jobs.png'
						),
						array(
							'name' => 'Boards with Groupings',
							'url' => BASE_URL.'/payrolls/editEmployees',
							'img' => 'icon-lg-technician.png'
						)
					)
				)
			);
		//	$menu['10']['name'] = 'Discounts',//
		//	$menu['10']['url'] = BASE_URL.'/tableeditor?table=ace_rp_coupons',
		//	$menu['10']['img'] = 'icon-lg-back.png',

			/*$menu['10']['name'] = 'Adv. Cards',
			$menu['10']['url'] = BASE_URL.'/cards',
			$menu['10']['img'] = 'icon-lg-card.png',*/
				}
		
		elseif (($roleID == 3)||($roleID == 9)) // TELEMARKETERS and OUTSOURCE AGENTS
		{
			$db =& ConnectionManager::getDataSource('default');
		$userID = (integer)$this->getLoggedUserID();
		$showEstimateId = (integer)$this->getShowEstimateId();
		$query = "select truck_id from ace_rp_truck_maps where user_id=$userID";
		
		$result = $db->_execute($query);
		$trucks = array();
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
        {
		  $trucks[] = $row['truck_id'];	
		}
		//$menu="";
		// if(in_array(40,$trucks) && ()){
		if($roleID == 9 || $showEstimateId == 1 || $showEstimateId == 2 ) {
			$menu123=array(
							'name' => 'Estimates',

							'url' => BASE_URL.'/reports/estimates',

							'img' => 'icon-lg-all-bookings.png'
						);
		}
		else {
		$menu123="";
		}
			$menu = array(
				'tabs' => array(),
				'content' => array(
					array(
						array(
							'name' => 'New Client', //new customer
							'url' => BASE_URL.'/orders/editBooking?customer_id=&order_id=0',
							'img' => 'icon-lg-customers.png'
						),
						array(
							'name' => 'Board',
							'url' => BASE_URL.'/reports/telem_board',
							'img' => 'icon-lg-report.png'
						),
						/*array(
							'name' => 'Board with Groups',
							'url' => BASE_URL.'/reports/telem_board_by_group',
							'img' => 'icon-lg-report.png'
						),*/
						array(
							'name' => 'Schedule',
							'url' => BASE_URL.'/orders/scheduleView',
							'img' => 'icon-lg-schedule.png'
						),
						array(
							'name' => 'Call To Book',
							'url' => '#',
							'onclick' => 'opencb();return false;',
							//'onclick' => 'opencb()',
							'img' => 'icon-lg-callback.png',
							'target' => '_self'
						),
						array(
							'name' => 'Telem Summary ',
							'url' => BASE_URL.'/reports/telemarketers_summary',
							'img' => 'icon-lg-all-bookings.png'
						)
						,
						$menu123,
						array(
							'name' => 'Map',
							'url' => '#',
							'onclick' => 'openMap();return false;',
							'img' => 'icon-ace.gif',
							'target' => '_blank'
						),
						

						array(

							'name' => 'Gallery',

							'url' => 'http://hvacproz.ca/acesys/filemanager/dialog.php',

							'img' => 'edit_18.png'

						),
						array(
							'name' => 'Cancellations',
							'url' => BASE_URL.'/reports/cancellations',
							'img' => 'icon-lg-all-bookings.png'
						),
						array(

							'name' => 'Pitch',

							'url' => BASE_URL.'/orders/editPitch',

							'img' => 'voice.jpg'

						),
						array(
								'name' => 'Agent Training',
								'url' => BASE_URL.'/settings/showAgentTrainingCategory',
								'img' => 'icon-lg-jobs.png',
							),
						// ,
						// array(

						// 	'name' => 'Dialer',

						// 	'url' => BASE_URL.'/calls',

						// 	'img' => 'icon-lg-callback.png',
						// 	'target' => '_top'

						// )
					)
				)
			);
			//$menu['11']['name'] = 'Logout',
			//$menu['11']['url'] = BASE_URL.'/login/logout',
			//$menu['11']['img'] = 'icon-lg-logout.png',
			//$menu['11']['target'] = '_top',
		}
		elseif ($roleID == 5) // INVENTORY ADMIN
		{
			$menu = array(
				'tabs' => array(),
				'content' => array(
					array(
						array(
							'name' => 'Status',
							'url' => BASE_URL.'/inventories/index',
							'img' => 'icon-lg-inventory.png'
						),
						array(
							'name' => 'Transactions',
							'url' => BASE_URL.'/inventories/AllDocuments',
							'img' => 'icon-lg-inventory-moves.png'
						),
						array(
							'name' => 'Purchase',
							'url' => BASE_URL.'/inventories/supplies',
							'img' => 'icon-lg-inventory-moves.png'
						),
						array(
							'name' => 'Techs',
							'url' => BASE_URL.'/inventories/techs',
							'img' => 'icon-lg-inventory-moves.png'
						),
						array(
							'name' => 'Part Requests',
							'url' => BASE_URL.'/inventories/requests',
							'img' => 'icon-lg-inventory-moves.png'
						),
						array(
							'name' => 'Edit Defaults',
							'url' => BASE_URL.'/inventories/editDefault',
							'img' => 'icon-lg-jobs.png'
						),
						array(
							'name' => 'Suppliers',
							'url' => BASE_URL.'/suppliers',
							'img' => 'icon-lg-technician.png'
						),
						array(
							'name' => 'Logout',
							'url' => BASE_URL.'/login/logout',
							'img' => 'icon-lg-logout.png',
							'target' => '_top'
						)
					)
				)
			);
		}
		elseif ($roleID <=2 || $roleID ==15 || $roleID ==12) // TECH
		{
			$menu = array(
				'tabs' => array('Jobs','Inventory'),
				'content' => array(
					array(
						array(
							'name' => 'Your Jobs',
							'url' => BASE_URL.'/commissions/calculateCommissions',
							'img' => 'icon-lg-trucks.png'
						),
						array(
							'name' => 'Part Purchase',
							'url' => BASE_URL.'/inventories/supplies',
							'img' => 'icon-lg-inventory-moves.png'
						),
						array(

							'name' => 'Tech Purchase',
							'url' => BASE_URL.'/commissions/techPurchase',
							'img' => 'icon-lg-customers.png',
						),
						array(
							'name' => 'Days off',
							'url' => BASE_URL.'/commissions/editTech/'.$userID.'?view=2',
							'img' => 'icon-lg-technician.png'
						),
						array(
							'name' => 'Commission',
							'url' => BASE_URL.'/commissions/editTech/'.$userID,
							'img' => 'icon-lg-price.png'
						),
						// array(
						// 	'name' => 'Summary',
						// 	'url' => BASE_URL.'/commissions/techSummary',
						// 	'img' => 'icon-lg-all-bookings.png'
						// ),
						array(

							'name' => 'Daily Summary',

							'url' => BASE_URL.'/commissions/techSummary',

							'img' => 'icon-lg-report.png'

						),
						// array(
						// 	'name' => 'Schedule',
						// 	'url' => BASE_URL.'/orders/scheduleView',
						// 	'img' => 'icon-lg-schedule.png'
						// ),
						array(
							'name' => 'Feedbacks',
							'url' => BASE_URL.'/orders/feedbacks_list',
							'img' => 'icon-lg-feedbacks.png'
						),
						
						array(
							'name' => 'Send Message',
							'url' => BASE_URL.'/messages/EditMessage',
							'img' => 'icon-lg-enter-work-done.png',
							'target' => '_top'
						),
						
						array(
							'name' => 'Feedback Display',
							'url' => BASE_URL.'/orders/feedbackView',
							'img' => 'icon-lg-feedbacks.png'
						),
						array(
							'name' => 'Home',
							'url' => BASE_URL.'/orders/invoiceTablet',
							'img' => 'invoice48x48.png',
							'target' => '_top'
						),
						array(
							'name' => 'Logout',
							'url' => BASE_URL.'/login/logout',
							'img' => 'icon-lg-logout.png',
							'target' => '_top'
						)

					),
					array(
						array(
							'name' => 'Inventory',
							'url' => BASE_URL.'/inventories/techs',
							'img' => 'icon-lg-inventory-moves.png'
						),
						array(
							'name' => 'Part Requests',
							'url' => BASE_URL.'/inventories/requests',
							'img' => 'icon-lg-inventory-moves.png'
						),
						array(
							'name' => 'Suppliers',
							'url' => BASE_URL.'/suppliers',
							'img' => 'icon-lg-technician.png'
						)
					)
				)
			);
		}
		elseif ($roleID == 13) // SUPERVISOR
		{
			$menu = array(
				'tabs' => array('Functions','Reports'),
				'content' => array(
					array(
						array(
							'name' => 'New Client', //new customer
							'url' => BASE_URL.'/orders/editBooking?action_type=callback',
							'img' => 'icon-lg-customers.png'
						),
						array(
							'name' => 'Schedule',
							'url' => BASE_URL.'/orders/scheduleView',
							'img' => 'icon-lg-schedule.png'
						),
						array(
							'name' => 'Board',
							'url' => BASE_URL.'/reports/telem_board',
							'img' => 'icon-lg-report.png'
						),
						array(
							'name' => 'Board with Groups',
							'url' => BASE_URL.'/reports/telem_board_by_group',
							'img' => 'icon-lg-report.png'
						),
						array(
							'name' => 'Call To Book',
							'url' => '#',
							'onclick' => 'opencb()',
							'img' => 'icon-lg-callback.png',
							'target' => '_self'
						),
						array(
							'name' => 'Bookings',
							'url' => BASE_URL.'/orders/?action=view&newSearch=1&ffromdate='.date('Y-m-d').'&fsource_id='.$userID,
							'img' => 'icon-lg-jobs.png'
						),
						array(
							'name' => 'Users',
							'url' => BASE_URL.'/users',
							'img' => 'icon-lg-technician.png'
						),
						array(
							'name' => 'Logout',
							'url' => BASE_URL.'/login/logout',
							'img' => 'icon-lg-logout.png',
							'target' => '_top'
						),
						array(
							'name' => 'Map',
							'url' => '#',
							'onclick' => 'openMap();return false;',
							'img' => 'icon-ace.gif',
							'target' => '_blank'
						),
						array(

							'name' => 'Chat',
							'url' => 'http://support.hvacproz.ca/lhc_web/index.php/site_admin/',
							'img' => 'comments.png'
						)
					),
					array(
						array(
							'name' => 'Call Summary',
							'url' => BASE_URL.'/reports/calls_summary',
							'img' => 'icon-lg-all-bookings.png'
						),
						array(
							'name' => 'Hot List Summary',
							'url' => BASE_URL.'/reports/callback_summary',
							'img' => 'icon-lg-all-bookings.png'
						),
						array(
							'name' => 'Telem Summary',
							'url' => BASE_URL.'/reports/telemarketers_summary',
							'img' => 'icon-lg-all-bookings.png'
						),
						array(
							'name' => 'Cancellations',
							'url' => BASE_URL.'/reports/cancellations',
							'img' => 'icon-lg-all-bookings.png'
						)
					)
				)
			);
		}

		return $menu;
	}

function pagination($allPage, $currentPage, $itemsToShow='', $pagesToDisplay='',$param_name='currentPage',$pagination_name='pagination')
{
    global $_GET;

    $showastable=true;

    $otherGetVars = '';
    foreach( $_GET as $k => $v ) {
    	if( $k != $param_name && $k != 'url' ) $otherGetVars .= '&'.$k.'='.$v;
    }

    $listString = "";
    $allRows = $allPage;
    $allPages = ceil($allRows / $itemsToShow);
    if($currentPage >=$allPages)
         $currentPage = $allPages-1;
    $start = 0;
    $end = $allPages;

    if($pagesToDisplay > 0)
    {
        $end = $currentPage + (ceil($pagesToDisplay/2));
        $start = $end - $pagesToDisplay;
        if($start < 0)
        {
            $start = 0;
            $end = $pagesToDisplay;
        }
        if($end > $allPages)
            $end = $allPages;
    }

    $listString = ( $showastable ? '<table border="0" cellpadding="0" cellspacing="5" ><tr><td align="center" width="60" >&nbsp;' : '' );
    if($currentPage > 0)
    {
        $targetpage = $currentPage - 1;
        $listString .= "<a href=\"?".$param_name."=".$targetpage.$otherGetVars."\"  class=\"dings3\" style=\"\">&lt;&lt;</a>";

    }
    $listString .= ( $showastable ? '</td>' : '' );
    if((($start+1)<$end)&&($pagesToDisplay>0))
    {
        for ($i=$start; $i<$end; $i++)
        {
            if($i == $currentPage)
                $listString .= ( $showastable ? "<td class=\"menuactive\">" : '' )."<a>" . ($i + 1) . "</a>".( $showastable ? "</td>" : "");
            else
             $listString .= ( $showastable ? "<td class=\"menubottom\">" : "" )."<a href=\"?".$param_name."=".$i.$otherGetVars."\" >" . ($i + 1) . "</a>".( $showastable ? "</td>" : "" );
        }
    }
    $listString .= ( $showastable ? '<td align="center" width="60">' : '' );
    if($currentPage < ($allPages-1))
        $listString .= "<a href=\"?".$param_name."=".($currentPage + 1).$otherGetVars. "\" class=\"dings3\"  style=\"\">&gt;&gt;</a>";
    $listString .= ( $showastable ? '&nbsp;</td></tr></table>' : '' );

   $this->controller->set($pagination_name,$listString);
}

// Converts format of $datetime from 'dd mm yyyy' to 'yyyy-mm-dd'(delimiter in $datetime can be any non digit character)
	function getMysqlDate($datetime){
	  return $datetime;
	  $timestamp = '';
	  preg_match_all('/[\D]*(\d*)/',$datetime,$matches);
	  $timestamp = $matches[1][2];
	  $timestamp .= '-';
	  $timestamp .= strlen($matches[1][1])<2?'0'.$matches[1][1]:$matches[1][1];
	  $timestamp .= '-';
	  $timestamp .= strlen($matches[1][0])<2?'0'.$matches[1][0]:$matches[1][0];
	  return $timestamp;
	}

	function calculeteItemDiscount($arr,$sum=0) {
		$returnValue = $sum;

		if( $arr['percent'] > 0 && $arr['price'] > 0 ) {
			$returnValue  = $sum - ((($arr['percent']*$sum)/100)-$arr['price']);
		} else {
			if( $arr['percent'] > 0 ) {
				$returnValue  = $sum - ($arr['percent']*$sum)/100;
			}

			if( $arr['price'] > 0) {
				$returnValue  = $sum - ($arr['price']);
			}
		}

		return $returnValue;
	}

	function hasRole($roleIDs)
	{
		global $_SESSION;
		// if($_COOKIE['acecare'])
		// {	
		// 	$id = session_regenerate_id();
		// 	$new_sessionid = session_id();
		// 	$db =& ConnectionManager::getDataSource('default');
		//     $query1 = "UPDATE ace_rp_users set session_id = '".$new_sessionid."' where id =".$_COOKIE['acecare'];     
		//     $result = $db->_execute($query1);
		//     echo "<pre>";
		//     print_r($_SESSION); die;
		// 	// $db =& ConnectionManager::getDataSource('default');
  //   		$query2 = "SELECT role_id  from ace_rp_users where id =".$_COOKIE['acecare'];     
  //   		$result2 = $db->_execute($query2);
  //   		$roleId = mysql_fetch_array($result2);
		// 	return in_array($roleId['role_id'], $roleIDs);
		// } else {

		// 	return in_array($_SESSION['user']['role_id'], $roleIDs);
		// }
		return in_array($_SESSION['user']['role_id'], $roleIDs);
	}

	function checkRoles($roles){

		 if( !$this->hasRole($roles) ) {
		 	die('<h3 style="text-align:center">You have not permision to access this action2!<br /><a href="javascript:history.go(-1)" style="text-align:center">back</a></h3>');
		 }
	}

	function getLoggedUserID()
	{
		global $_SESSION;
		return ( $_SESSION['user']['id'] > 0  ? $_SESSION['user']['id'] : 0);
	}

	function getLoggedUserRoleID() {
		global $_SESSION;
		return ( $_SESSION['user']['role_id'] > 0  ? $_SESSION['user']['role_id'] : 0);
	}

	function getShowEstimateId()
	{
		global $_SESSION;
		return ( $_SESSION['user']['show_estimate'] > 0  ? $_SESSION['user']['show_estimate'] : 0);
	}

	function getJobPayPeriodConditions($pDay=0)
	{
		$returnValue = array();
		$day = ($pDay > 0 ? $pDay : date("j"));

		if( $day <= 15 ) {
			$returnValue["Order.job_date"] = '>= '.date("Y-m-").'01';
			$returnValue["and"] = array("Order.job_date" => '<= '.date("Y-m-").'15');
		} else {
			$returnValue["Order.job_date"] = '> '.date("Y-m-").'15';
			$returnValue["and"] = array("Order.job_date" => '<= '.date("Y-m-").'31');
		}

		return $returnValue;
	}
  function getJobPayPeriodDate($pDay=0) {
  	$returnValue = array();
  	$day = ($pDay > 0 ? $pDay : date("j"));
  	if( $day <= 15 ) {
  		$returnValue["from"] = date("Y-m-").'01';
    	$returnValue["to"] = date("Y-m-").'15';
  	} else {
  		$returnValue["from"] = date("Y-m-").'15';
    	$returnValue["to"] = date("Y-m-").'31';
  	}

  	return $returnValue;
  }
  function getJobPayPeriodDateAsString($pDay=0) {
  	$returnValue = '';
  	$day = ($pDay > 0 ? $pDay : date("j"));
  	$aa = $this->getJobPayPeriodDate($day);
  	if( !empty($aa) ) {
  		$returnValue = "(".$aa['from']."/".$aa['to'].")";
  	}

  	return $returnValue;
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
	  if ( trim($str) != '' ) {
		if (strlen($str) == 10)
			{
			  $str1 = substr($str, 0, 3);
				$str2 = substr($str, 3, 3);
				return $str1.'-'.$str2.'-'.substr($str, 6, strlen($str));
			}
			else
			{
				return $str;
			}

		}
  }

  function displayZip($str){
  	if( trim($str) != '' ) {
	  	return substr($str, 0, 3).' '.substr($str, 3, strlen($str));
	  }
  }

	// Method replaces empty string by null as a value of a given variable
	function SetNull(&$check_var)
	{
		if ($check_var == '') $check_var = null;
	}

	function getTeamType($role_id, $partner_role_id)
	{
		if (($role_id = 1) && ($partner_role_id = 1))
			return "tech & tech";
		else if (($role_id = 1) && ($partner_role_id == null))
			return "tech alone";
		else if (($role_id = 1) && ($partner_role_id == 2))
			return "tech & helper";
	}

	function getFriendlyName($table, $field = null)
	{
		$db =& ConnectionManager::getDataSource('default');
		$table = str_replace($db->config['prefix'], '', $table);
		$query = "SELECT * FROM ace_rp_descriptor WHERE".($table ? " q_table='".$table."'" : "").($table && $field ? " AND " : "").($field ? " q_field='".$field."'" : "");
    	$result = $db->_execute($query);
    	if ($row = mysql_fetch_array($result))
			return array("title" => $row['d_title'], "desc" => $row['d_desc'], "format" => $row['d_format']);
		else
			return array("title" => ($field ? $field : $table), "desc" => "", "format" => "");
	}

	function makeCSV($elements, $filter = null, $hdr = null)
	{
		//Header
		foreach ($elements[0] as $k => $v)
		{
			if (is_array($v))
			{
				foreach ($v as $kk => $vv)
				{
					//ADDITION
					if (in_array($k.".".$kk, $filter))
					{
						if ($header != '') $header .= ",";
						$header .= $k.".".$kk;
					}
				}
			}
			else
			{
				//ADDITION
				if ($header != '') $header .= ",";
				$header .= $k;
			}
		}

		//Data
		foreach ($elements as $el)
		{
			foreach ($el as $k => $piece)
			{
				if (is_array($piece))
				{
					foreach ($piece as $kk => $v)
					{
						//ADDITION
						if (in_array($k.".".$kk, $filter))
						{
							if (!$newLine && ($data != '')) $data .= ",";
							$data .= $v;
							$newLine = 0;
						}
					}
				}
				else
				{
					if (in_array($k, $filter))
					{
						if (!$newLine && ($data != '')) $data .= ",";
						$data .= $piece;
						$newLine = 0;
					}
				}
			}
			$data .= "\n";
			$newLine = 1;
		}
		$date = date("Y.m.d");
		header("Content-Disposition: attachment; filename=export-".$date.".csv");
		print $header."\n".$data;
	}

	//Added By Metodi :: TODO
	//************************************
	function PrintTestComment($msg){
		echo '<div style="background-color:red;color:white;font-weight:bold;"><p>';
		echo $msg;
		echo '</p></div>';
	}

	function PrintTestCommentObj($obj){
		echo '<div style="background-color:red;color:white;font-weight:bold;"><p>';
		var_dump($obj);
		echo '</p></div>';
	}

    // Method calculates order's total
    function getOrderTotal($order_id)
    {
        // Set up order totals for calculation
        $ret['total'] = array('0' => 0, '1' => 0);
        $ret['job'] = array('0' => 0, '1' => 0);
        $ret['parts'] = array('0' => 0, '1' => 0);
        $ret['appl'] = array('0' => 0, '1' => 0);

        $query_items = "select sum(if(i.is_appliance!=1,oi.price*oi.quantity-oi.discount+oi.addition,0)) sell_service,
								sum(if(i.is_appliance=1,oi.price*oi.quantity-oi.discount+oi.addition,0)) sell_appl,
								sum(oi.price*oi.quantity-oi.discount+oi.addition) sell_all,
                               oi.class sale_class
                         from ace_rp_order_items oi, ace_rp_items i
                        where i.id=oi.item_id
                          and oi.order_id=" .$order_id ."
                        group by oi.class";

        $db =& ConnectionManager::getDataSource('default');
        $result_items = $db->_execute($query_items);
        while ($row_items = mysql_fetch_array($result_items, MYSQL_ASSOC))
        {
          $ret['job'][$row_items['sale_class']]=$row_items['sell_service'];
          $ret['appl'][$row_items['sale_class']]=$row_items['sell_appl'];
          $ret['total'][$row_items['sale_class']]=$row_items['sell_all'];
        }

        $ret['sum_subtotal'] = 1*$ret['total'][0] + 1*$ret['total'][1];
        $ret['sum_hst'] = round($ret['sum_subtotal']*0.12,2);
        $ret['sum_total'] = $ret['sum_subtotal'] + $ret['sum_hst'];

        return $ret;
    }

		// Creates an HTML 'select' drop-down on the basis of a given array
		function getSelector($aSrc, $sControlName, $mCurrentItem)
		{
			$sRet = "<select id='$sControlName' name='data[$sControlName]'onchange =  'ChangeSupplier(this)'>";
			$selected = "";
			if (!$mCurrentItem)
				$selected = "selected='selected'";
			$sRet.= "<option value='' $selected>&nbsp;</option>";

			foreach ($aSrc as $k => $v)
			{
				$selected = "";
				if ($k == $mCurrentItem)
					$selected = "selected='selected'";
				$sRet.= "<option value='$k' $selected>$v</option>";
			}

			$sRet.= "</select>";

			return $sRet;
		}

	// added by Maxim Kudryavtsev - for displaying distances between jobs (postal codes)
	function getPostalDistances($from, $to)
	{
		$db =& ConnectionManager::getDataSource('default');
		$query = 'SELECT meters, seocnds FROM ace_rp_postal_distances WHERE from="'.$from.'" and to="'.$to.'"';
		$result = $db->_execute($query);
		if ($row = mysql_fetch_array($result))
			return array("distance" => $row['meters'], "time" => $row['seocnds']);
		else
			return false;
	}

	function split_addr($a) {
		$incorrect_adr=false;

		$original_addr=trim($a);
		$a=str_replace(array('-','_',',',';','/','\\','|'),' ',$original_addr);

		$out=array('unit'=>'','street_number'=>'','street'=>'');
		if (preg_match('/^(su|ph|ap|u|\#)[^0-9\s\#]*.*?([0-9]+|[A-z])/i',$a,$m))
			$out['unit']=$m[0];
		elseif (preg_match('/^[A-z][0-9]*\s/i',$a,$m))
			$out['unit']=trim($m[0]);
		if ($out['unit']!='') $a=trim(substr($a,strlen($out['unit'])));

		$p=0;
		if (preg_match('/([0-9]+)\s*(a|b|c|d|e)?(th)?(nd)?\s*(rd|road|av|st|dr|bl|hw|pl|vl)([^\s]*)$/i',$a,$m)) {
			$p=strpos($a,$m[0]);
		}
		if (preg_match('/([A-z]).*$/i',$a,$m)) {
			if ($p>0) $p=min($p,strpos($a,$m[0]));
			else $p=strpos($a,$m[0]);
		}
		if ($p>0) {
			$out['street']=substr($a,$p);
			$a=trim(substr($a,0,$p));
		}

		if (is_numeric($a))
			$out['street_number']=$a;
		elseif ($out['unit']!='')
			$incorrect_adr=true;
		elseif (preg_match('/^([0-9]+)\s+([0-9]+)$/i',$a,$m)) {
			$out['unit']=$m[1];
			$out['street_number']=$m[2];
		}
		else $incorrect_adr=true;

		if ($out['street']=='')
			$incorrect_adr=true;

		if ($incorrect_adr)
			$out=array('unit'=>'','street_number'=>'','street'=>$original_addr); // if we couldn't recognise an address - return original in `street` field

		return $out;
	}

	function itemTransaction(
		$doc_id, $item_id, $item_name, $item_qty,
		$item_selling_price, $item_purchase_price,
		$item_model_number, $item_location, $move_date) {

		$db =& ConnectionManager::getDataSource("default");

		$query = "
			INSERT INTO ace_iv_transactions SET
				doc_id = $doc_id,
				item_id = '$item_id',
				item_name = '".mysql_real_escape_string($item_name)."',
				item_qty = '$item_qty',
				item_selling_price = '$item_selling_price',
				item_purchase_price = '$item_purchase_price',
				item_model_number = '$item_model_number',
				user_id = ".$this->getLoggedUserID().",
				item_location = '$item_location',
				move_date = '$move_date'
		";
		$db->_execute($query);
	}

	function itemAssetTransaction ($itemId, $purchasePrice, $regularPrice,$qty,$paidBy)
	{
		$db =& ConnectionManager::getDataSource("default");

		$query = "
			INSERT INTO ace_iv_assets SET
				item_id = '$itemId',
				regular_price = '$regularPrice',
				purchase_price = '$purchasePrice',
				qty = '$qty'
				
		";
		$db->_execute($query);
		return $id = $db->lastInsertId();
	}
	function itemMove($doc_id, $item_id, $item_name, $item_qty,
			$item_selling_price, $item_purchase_price,
			$item_model_number, $item_location_from, $item_location_to, $move_date) {

		$this->itemTransaction($doc_id, $item_id, $item_name, $item_qty*-1,
			$item_selling_price, $item_purchase_price,
			$item_model_number, $item_location_from, $move_date);

		$this->itemTransaction($doc_id, $item_id, $item_name, $item_qty,
			$item_selling_price, $item_purchase_price,
			$item_model_number, $item_location_to, $move_date);
	}


	function getUserDetails($id)
	{
		$db =& ConnectionManager::getDataSource('default');
		$query = "SELECT * from ace_rp_customers where id =".$id;
		$result = $db->_execute($query);

		$row = mysql_fetch_array($result);

		return $row;
	}

	function formateDate($date)
	{
		$date = explode("/", $date);
		$new_date = $date[2]."-".$date[0]."-".$date[1];
		return $new_date;
	}


	// Loki: send sms
	function sendTextMessage($phone = null, $message = null)
	{
		$phone = "+1".str_replace ('-','', $phone);
		// $phone = str_replace ('-','', $phone);
		$msg = strip_tags($message);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://hvacproz.ca/acesystem2018/send_text_sms.php");
		// curl_setopt($ch, CURLOPT_URL,"http://acecare.ca/acesystem2018/send_text_sms.php");
		// curl_setopt($ch, CURLOPT_URL,"http://35.209.147.55/acesystem2018/send_text_sms.php");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"phone=".urlencode($phone)."&body=".urlencode($msg));
		// receive server response ...
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$err = curl_error($ch);
		curl_close($ch);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  return $response = json_decode($result);
		}
		exit();
	}

	function getSmsStatus($id)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://hvacproz.ca/acesystem2018/get_sms_status.php");
		// curl_setopt($ch, CURLOPT_URL,"http://acecare.ca/acesystem2018/get_sms_status.php");
		// curl_setopt($ch, CURLOPT_URL,"http://35.209.147.55/acesystem2018/get_sms_status.php");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"id=".$id);
		// receive server response ...
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$err = curl_error($ch);
		curl_close($ch);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  return $response = json_decode($result);
		}
		exit();
	}

	function removeSlash($str)
	{
		return $newStr = stripcslashes($str);
	}

	// Loki : Convert UTC time to GMT-7
	function convertTimeZone($date)
	{
		$timezone  = -7; //(GMT -5:00) EST (U.S. & Canada) 
		$orgTime = strtotime($date);
		$convertedTime =  gmdate("Y-m-d h:i:s a", $orgTime + 3600*($timezone+date("I"))); 
		return $convertedTime;
		exit();
	}

	function sendEmailMailgun($to,$subject,$body,$order_id = null, $imagePath =null){
		try
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,"http://hvacproz.ca.ca/acesystem2018/mailcheck.php");
			// curl_setopt($ch, CURLOPT_URL,"http://acecare.ca/acesystem2018/mailcheck.php");
			// curl_setopt($ch, CURLOPT_URL,"http://35.209.147.55/acesystem2018/mailcheck.php");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,"TO=".$to."&SUBJECT=".$subject."&BODY=".urlencode($body)."&imagePath=".$imagePath);
			// receive server response ...
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$msgid = curl_exec ($ch);//exit;
			curl_close ($ch);
			return $msgid;
		}
		 catch(Exception $e)
		{
			file_put_contents("mail_error_log.txt", "mailgun=".$e->getMessage(), FILE_APPEND);
		}
	}

	// Loki: Get distance between two postal code.
	function getPostaCodeDistance($source, $destination)
	{
		$url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$source."&destinations=".$destination."&mode=driving&language=en-EN&sensor=false&key=AIzaSyDUC73wk4-yrBlIKZOy7j1ya2_dv9MFiGw";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"http://hvacproz.ca/acesystem2018/distance_calculation.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,"URL=".urlencode($url));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_URL, $url);
        // curl_setopt($ch, CURLOPT_POST, 1);
        // curl_setopt($ch, CURLOPT_HEADER, 0);
        // curl_setopt($ch, CURLOPT_POSTFIELDS,"");
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);

        // print_r($response); die;
        $result = json_decode($response, true);
        $err = curl_error($ch);
        curl_close($ch);

        return $result;
	}

	function printData($data)
	{
		echo "<pre>";
		print_r($data); 
		die;
	}

	//Loki: delete the part images.
	function deletePurchasePartImage($id, $imgPath)
	{
        $db =& ConnectionManager::getDataSource('default');
		$rootPath = getcwd();
        unlink($rootPath.'/upload_photos/'.$imgPath);
        $query = "DELETE from ace_rp_user_part_images  where id =".$id;
        $res = $db->_execute($query);
        if ($res) {
           return true;
        } else {
        	return false ;
        }
        exit();
	}
}
?>
