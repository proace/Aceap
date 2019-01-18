<?php 
$link = mysql_connect('localhost', 'whytecl_ace', 'ace88');
if (!$link) die('Connection error: ' . mysql_error());

$db_selected = mysql_select_db('whytecl_acesys', $link);
if (!$db_selected) die ('Database error: ' . mysql_error());
?>