<?php

//mkdir('/home/aceno191/public_html/ace786/sanju', 0777);
$conn = mysqli_connect("localhost","aceno191_acesys","acesys123user","aceno191_acesys");
// Check connection
if (mysqli_connect_errno())
{
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
}else{
	echo 'Done';
}

$sql = "SELECT msgid,msgsub,order_id from ace_mailgun_elog";
$result = $conn->query($sql);
$result = mysqli_query($conn, $sql);
if (mysqli_num_rows($result) > 0) {
	while($row = mysqli_fetch_assoc($result)) {
	    $msgid = $row["msgid"];
        $msgsub = $row["msgsub"];
        $order_id = $row["order_id"];

        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://aceno1.ca/acesystem2018/mailgun_response.php");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"subject=".$msgsub."&msgid=".$msgid);
		// receive server response ...
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		echo '<BR> OID='.$order_id.'  RESPONSE = '.$resp = curl_exec ($ch);//exit;
		if(($resp=='failed') || ($resp=='tempfailed') || ($resp=='accepted') || ($resp=='')){
			$emal_bounce_status = 1;
		}else{
			$emal_bounce_status = 0;
		}
        $queryUpdate = "UPDATE ace_rp_orders set emal_bounce_status='".$emal_bounce_status."' WHERE id = '".$order_id."'";
		
		mysqli_query($conn, $queryUpdate);//$result = $conn->query($queryUpdate);
	}
} else {
	echo "0 results";
}

function callCURL($msgsub, $msgid){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,"http://aceno1.ca/acesystem2018/mailgun_response.php");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,"subject=".$msgsub."&msgid=".$msgid);
	// receive server response ...
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	echo 'RESPO =>'.$resp = curl_exec ($ch);exit;
	if(($resp=='failed') || ($resp=='tempfailed')){
		$emal_bounce_status = 1;
	}else{
		$emal_bounce_status = 0;
	}
	return $emal_bounce_status;
}

/*
mysqli_close($conn);

if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
        
        $msgid = $row["msgid"];
        $msgsub = $row["msgsub"];
        $order_id = $row["order_id"];

        
		
		$queryUpdate = "UPDATE ace_rp_orders set emal_bounce_status='".$emal_bounce_status."' WHERE id = '".$order_id."'";
		$result = $conn->query($queryUpdate);
		
		curl_close ($ch);
    }
} else {
    echo "0 results";
}

/*
$resp = $this->verifyEmailUsingMailgun($subject);
if(($resp=='failed') || ($resp=='tempfailed')){
	$emal_bounce_status = 1;
}else{
	$emal_bounce_status = 0;
}

$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

echo $queryUpdate = "UPDATE ace_rp_orders set emal_bounce_status='".$emal_bounce_status."' WHERE id = '".$order_id."'";
$result = $db->_execute($queryUpdate);*/

