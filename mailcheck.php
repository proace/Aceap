<?php
require 'vendor/autoload.php';
use Mailgun\Mailgun;
if(isset($_REQUEST['TO']) && !empty($_REQUEST['SUBJECT'])){
	$mail = $_REQUEST['TO'];
	$subject = $_REQUEST['SUBJECT'];
	$body = $_REQUEST['BODY'];
	$from = 'info@acecare.ca';	
	# Instantiate the client.
	$mgClient = new Mailgun('key-d0b892f98309d183705bb3e633eaff45');
	$domain = "aceno1.ca";
	# Make the call to the client.
	$queryString = array('from'    => $from,
	'to'      => $mail,
	'subject' => $subject,
	'text'    => 'Your mail do not support HTML',
	'html'    => $body);
	$result = $mgClient->sendMessage($domain,$queryString);
	//echo '<BR>Send Query Strinf<pre>';print_r($queryString);exit;
	$messageId = $result->http_response_body->id;
	$messageId = str_replace("<","",$messageId);
	echo $messageId = str_replace(">","",$messageId);
}else{
	echo 2;
}	
