<?php
$target = ""; 
$target = $target . basename($_FILES['uploaded']['name']) ; 
$ok=1; 
if(move_uploaded_file($_FILES['uploaded']['tmp_name'], $target)) {
	
	echo "The file ". basename( $_FILES['uploadedfile']['name']). " has been uploaded";
	
	$con = file_get_contents(basename($_FILES['uploaded']['name']));
	$en = base64_encode($con);
	$mime='image/jpg';
	$binary_data='data:' . $mime . ';base64,' . $en ; 
	$url = "http://69.31.184.162:81/acesys/".$_FILES['uploaded']['name'];

} else {
	echo "There was a problem uploading your file.";
}

?>
<html>
<head><title></title>
<script type="text/javascript">
function terminate()
{
	window.returnValue = "<?php echo $url; ?>"; 
	window.close();
}

</script>
</head>
<body onLoad="terminate();">

</body>
</html>