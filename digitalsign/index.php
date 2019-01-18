<!DOCTYPE html><head lang="en-ca"><meta charset="utf-8">
<title>Digital Sign</title>
<link rel="stylesheet" href="jquery.signaturepad.css">
<!--[if lt IE 9]><script src="http://static.thomasjbradley.ca/lab/signature-pad/flashcanvas.js"></script><![endif]-->
<script src="jquery.min.js"></script>
<script src="jquery.signaturepad.min.js"></script>
<script src="json2.min.js"></script>
<script>
		$(document).ready(function(){
			$('.sigPad').signaturePad();
					
		});		
</script>

<?php 

$order_id = $_GET['order_id'];
$email = $_GET['email'];

?>

</head>
<body>
<div id="content">
<h1>Digital Signature</h1>
<form method="post" action="savesignature.php" class="sigPad">
<label for="name">Print your name</label>
<input type="hidden" name="order_id" value="<?php echo $order_id ?>">
<input type="hidden" name="email" value="<?php echo $email ?>">
<input type="text" name="name" id="name" class="name" value="">

<p class="typeItDesc">Review your signature</p>
<p class="drawItDesc">Draw your signature</p><ul class="sigNav">
<li class="typeIt"><a href="#type-it">Name</a></li>
<li class="drawIt"><a href="#draw-it" class="current">Signature</a></li>
<li class="clearButton"><a href="#clear">Clear</a></li></ul>
<div class="sig sigWrapper"><div class="typed"></div>
<canvas class="pad" width="198" height="55"></canvas>
<input type="hidden" name="output" class="output">
</div>
<br />
<br />
<input type="submit" value="Sign Invoice">

</form></div>
<script>
		$('#draw-it').click();		
</script>

</body>
<html>