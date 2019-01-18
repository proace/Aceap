<?php
require 'vendor/autoload.php';
use Mailgun\Mailgun;
if(isset($_REQUEST['subject']) && $_REQUEST['msgid']!=''){
    $sub = trim($_REQUEST['subject']);
    $mgClient = new Mailgun('key-d0b892f98309d183705bb3e633eaff45');
    $domain = "aceno1.ca";
    $queryString = array(
        'begin'        => 'Fri, 1 Mar 2018 09:00:00 -0000',
        'ascending'    => 'yes',
        'limit'        =>  1,
        'pretty'       => 'yes',
        'subject' => $sub
    );
    //echo '<BR>Response Query Strinf<pre>';print_r($queryString);//exit;
    # Make the call to the client.
    //$result = $mgClient->get("$domain/events", $queryString);
    $result = $mgClient->get("$domain/log",array( 'skip' => 0));        
    $http_response_body=$result->http_response_body;//
    $items=$http_response_body->items;
    //echo "<pre>";
    //print_r($items);
    //echo "</pre>";//exit;
    $msgid = trim($_REQUEST['msgid']);
    foreach($items as $v){        
        //echo '<br>Mid=>'.$v->message_id.' == Cmid->'. $msgid;        
        if($v->message_id==$msgid){
            echo $v->hap;exit;
        }
        //return $v->hap;
        //echo $v->recipient." ".$v->timestamp." ".date("d-m-Y",round($v->timestamp))." ".$v->event;
        //$string = $v->message;
        //$words = explode('Server response:', $string);
        //echo '<BR>Respons:<BR>'.$words[0];
        //$last_space = strrpos($string, 'Server response:');
        //echo $v->hap;//." = ".$v->type;
        //die;
    }
}
?>