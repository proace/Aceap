<?php
require 'vendor/autoload.php';
use Mailgun\Mailgun;

	$mail = "niks.pawale@gmail.com"; //$_REQUEST['TO'];
	$subject = "Test Subject"; //$_REQUEST['SUBJECT'];
	$body = "Test Body"; //$_REQUEST['BODY'];
	$from = 'Test<info@acecare.ca>';
	# Instantiate the client.
	$mgClient = new Mailgun('key-d0b892f98309d183705bb3e633eaff45');
	$domain = "aceno1.ca";
	# Make the call to the client.
	$result = $mgClient->sendMessage($domain,
	array('from'    => $from,
	'to'      => $mail,
	'subject' => $subject,
	'text'    => 'Your mail do not support HTML',
	'html'    => $body));
	
	
	$messageId = $result->http_response_body->id;
	$messageId = str_replace("<","",$messageId);
	echo $messageId = str_replace(">","",$messageId);
	echo "<br/>";

	//if($result!=0){
	  //  echo 1;
//	}else{
	//    echo 0;
//	}