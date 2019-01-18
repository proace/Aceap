<?php 
include_once("dbconnect.php");

$query = "SELECT url FROM ace_rp_cyphon WHERE id = 1";
$result = mysql_query($query);
if($row = mysql_fetch_array($result)) {
	$url = $row['url'];
}

$filename = $_GET['filename'];
$city = $_GET['city'];
//$url = "http://www.canada411.ca/search/ad/1/-/cZQQstZQQciZQQpvZQQpcZ{postal_code}?pglen=100";
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Cyphon Miner</title>
<script src="jquery-1.5.2.min.js" type="text/javascript"></script>
<script src="cyphon.js" type="text/javascript"></script>
<script src="jquery-ui-1.8.12.custom.min.js" type="text/javascript"></script>
<script type="text/javascript">
$(function() {
	setUrl('<?php echo $url ?>');
	setCityScope('<?php echo $city ?>');
	setFilename('<?php echo $filename ?>');
	populateSeeds("<?php echo $city ?>");
	
	$( "#progressbar" ).progressbar({
		value: 0
	});
});
</script>
<link href="all.css" media="all" rel="stylesheet" />
<link href="cyphon.css" media="all" rel="stylesheet" />
<link href="jquery-ui-1.8.12.custom.css" media="all" rel="stylesheet" />
</head>
<body>
<div class="results">
	<div class="gray">Mining has started...</div>
</div>
<div id="progressbar"></div>
</body>
</html>