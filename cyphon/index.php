<?php 
include_once("dbconnect.php");

$query = "
	SELECT DISTINCT city 
	FROM ace_rp_cyphon_seed
	";
$result = mysql_query($query);
$cyphon = array();
$index = 1;
while($row = mysql_fetch_array($result)) {
    $cyphon[$index] = array();
    $cyphon[$index]['city'] = $row['city'];
    $index++;
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Cyphon Lead Miner</title>
<script src="jquery-1.5.2.min.js" type="text/javascript"></script>
<script src="index.js" type="text/javascript"></script>
<link href="all.css" media="all" rel="stylesheet" />
</head>
<body>
<h1>Cyphon</h1>
<table>
	<tr>
    	<td>Filename:</td>
        <td><input type="text" name="filename" id="filename" /></td>
    </tr>
    <tr>
    	<td>Seed (City):</td>
        <td>
        <select name="city" id="city">
        	<option value=""></option>
        	<?php foreach($cyphon as $c) { ?>
        	<option value="<?php echo $c['city'] ?>"><?php echo $c['city'] ?></option>
            <?php } ?>
        </select>
        <a href="seeder.php">Add more seeds</a>
        </td>
    </tr>
    <tr>
    	<td colspan="2" align="center"><input type="button" name="start" id="start" value="start" /></td>
    </tr>
</table>
<?php include("navi.php") ?>
</body>
</html>