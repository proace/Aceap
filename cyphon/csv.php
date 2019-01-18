<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Cyphon Harvest</title>
<link href="all.css" media="all" rel="stylesheet" />
</head>
<body>
<h1>Harvested Leads</h1>
<ul>
<?php
    // Define the full path to your folder from root
    $path = $_SERVER['DOCUMENT_ROOT']."/cyphon/harvest";

    // Open the folder
    $dir_handle = @opendir($path) or die("Unable to open $path");

    // Loop through the files
    while ($file = readdir($dir_handle)) {

    if($file == "." || $file == ".." || $file == "index.php" )
	
		continue;
		echo "<li><a href=\"download.php?download_file=$file\">".str_replace(".csv", "", $file)."</a></li>";

    }
    // Close
    closedir($dir_handle);
?>
</ul>
<?php include("navi.php") ?>
</body>
</html>