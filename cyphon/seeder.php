<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Seeder</title>
<script src="jquery-1.5.2.min.js" type="text/javascript"></script>
<script src="seeder.js" type="text/javascript"></script>
<link href="all.css" media="all" rel="stylesheet" />
<link href="seeder.css" media="all" rel="stylesheet" />
</head>
<body>
<h1>Add Seeds</h1>
<div class="section">
    <div class="input">
        <input type="hidden" name="id" value="0" />
        <label>City</label><br />
        <input type="text" id="city" name="city" /><br />
        <label>State/Province</label><br />
        <input type="text" id="state" name="state" /><br />
        <textarea wrap="physical" id="postal_codes" name="postal_codes" rows="15" cols="16"></textarea>
        <br />
        <input type="submit" value="Save" id="saveSeeds" />    
    </div>
    <div class="output">
    </div>
</div>
<?php include("navi.php") ?>
</body>
</html>