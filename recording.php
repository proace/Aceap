<?php
// echo "apach";die;
$putdata = fopen("php://input", "r");
 
   $from = $_GET["from"];
   $from = substr($from, 0, strpos($from, '@'));
   $to = $_GET["to"];
   $to = substr($to, 0, strpos($to, '@'));
   $ext = $_SERVER['REQUEST_URI'];
  $ext = substr($ext, strpos($ext, '?') - 3, 3);
   $r = $_GET["call_id"];
   $d = date("YmdHis");
  /* Open a file for writing */
  /* $fp = fopen("./$r.mp3", "w"); */
 
 $fp = fopen("call_recordings/$d-$from-$to.$ext", "w");
  /* Read the data 1 KB at a time and write to the file */
   while ($data = fread($putdata, 1024))
    fwrite($fp, $data);
 
  // /* Close the streams */
   fclose($fp);
   fclose($putdata);
?>