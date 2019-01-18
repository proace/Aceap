<?php 
$host='localhost';
$username='acecare7_acesys';
$password='Iw+&Sm]=otV7';
$database='acecare7_acetest';

$con = mysqli_connect($host,$username,$password,$database);


error_reporting(E_ALL);

//1125-75

   //$sql= "select * from ace_iv_transaction_summary_view limit 15 offset 1619";
   
   
   $sql= "select * from ace_latest_done_job   ";
  
  
	 $result=mysqli_query($con,$sql);

	
	 echo "insert into ace_latest_done_job(job_date, customer_phone)values<br>"; 
	 
	 
	 
	 while($row=mysqli_fetch_object($result)){
	
/*	$a[]= '("'.$row->id.'","'.$row->order_id.'","'.$row->question_number.'","'.$row->question_text.'","'.$row->response_text.'","'.$row->suggestion_text.'","'.$row->decision_text.'","'.$row->date_saved.'")';

 
$a[]= "('".$row->sku."','".$row->id."','".$row->auto_sku."','".$row->name."','".$row->description1."','".$row->description2."','".$row->efficiency."','".$row->model."','".$row->category."', '".$row->category_id."','".$row->brand."','".$row->brand_id."','".$row->supplier."','".$row->supplier_id."','".$row->supplier_price."','".$row->selling_price."','".$row->regular_price."','".$row->active_name."','".$row->active."')";
  
*/
  
  
	$a[]= '("'.$row->job_date.'","'.$row->customer_phone.'")';
	     
	 }
	echo implode(',<br>',$a);  























/*
//start 370000

	     $sql= "select * from ace_rp_orders_questions_final limit 10000 OFFSET 370000";
	  
	 $result=mysqli_query($con,$sql);

	
	 echo "insert into ace_rp_orders_questions_final(id,order_id,question_number,question_text,response_text,suggestion_text,decision_text,date_saved)values<br>"; 
	 
	 
	 
	 while($row=mysqli_fetch_object($result)){
	
//	$a[]= '("'.$row->id.'","'.$row->order_id.'","'.$row->question_number.'","'.$row->question_text.'","'.$row->response_text.'","'.$row->suggestion_text.'","'.$row->decision_text.'","'.$row->date_saved.'")';
  $a[]= "('".$row->id."','".$row->order_id."','".$row->question_number."','".$row->question_text."','".$row->response_text."','".$row->suggestion_text."','".$row->decision_text."','".$row->date_saved."')";
	     
	 }
	echo implode(',<br>',$a);  */
?>
