<?php
/* SVN FILE: $Id: vendors.php 5317 2007-06-20 08:28:35Z phpnut $ */
/**
 * Short description for file.
 *
 * This file includes js vendor-files from /vendor/ directory if they need to
 * be accessible to the public.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.app.webroot.js
 * @since			CakePHP(tm) v 0.2.9
 * @version			$Revision: 5317 $
 * @modifiedby		$LastChangedBy: phpnut $
 * @lastmodified	$Date: 2007-06-20 03:28:35 -0500 (Wed, 20 Jun 2007) $
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Enter description here...
 */
$file = $_GET['file'];
$pos = strpos($file, '..');
if ($pos === false) {
	if (is_file('../../vendors/javascript/'.$file) && (preg_match('/(\/.+)\\.js/', $file)))
	{
		readfile('../../vendors/javascript/'.$file);
	}
} else {
	header('HTTP/1.1 404 Not Found');
}
?>