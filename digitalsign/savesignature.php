<?php
/**
 *	Signature to Image: A supplemental script for Signature Pad that
 *	generates an image of the signature’s JSON output server-side using PHP.
 *	
 *	@project	ca.thomasjbradley.applications.signaturetoimage
 *	@author		Thomas J Bradley <hey@thomasjbradley.ca>
 *	@link		http://thomasjbradley.ca/lab/signature-to-image
 *	@link		http://github.com/thomasjbradley/signature-to-image
 *	@copyright Copyright MMXI–, Thomas J Bradley
 *	@license	New BSD License
 *	@version	1.0.1
 */

require_once 'signature-to-image.php';

//$img = sigJsonToImage(file_get_contents('sig-output.json'));

$img = sigJsonToImage($_POST['output']);

$order_id = $_POST['order_id'];
$email = $_POST['email'];

// Save to file
imagepng($img, "../app/webroot/img/order_signatures/$order_id.png");

// Output to browser
//header('Content-Type: image/png');
//imagepng($img);

// Destroy the image in memory when complete
imagedestroy($img);
?>
<img src="http://69.31.184.162:81/acesys/app/webroot/img/order_signatures/<?php echo $order_id ?>.png"  />
<br />
<input type="text" id="email" value="<?php echo $email ?>" />
<input type="button" value="Email Invoice" onclick="window.open('http://69.31.184.162:81/acesys/index.php/orders/invoiceTabletEprint?order_id=<?php echo $order_id ?>&email='+document.getElementById('email').value, '_self')" />
<input type="button" value="Print Invoice" onclick="window.open('http://69.31.184.162:81/acesys/index.php/orders/invoiceTabletPrint?order_id=<?php echo $order_id ?>', '_self')" />



