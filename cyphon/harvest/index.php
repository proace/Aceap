<?php 
include_once('../simple_html_dom.php');

$url = $_POST['url'];

$postal_code = $_POST['postal_code'];
$city = $_POST['city'];
$state = $_POST['state'];
$file_name = $_POST['file_name'];
$page_index = 1;
$continue = true;

//put the seed (postal code) into the url
$url = str_replace("{postal_code}", $postal_code, $url);
//while($continue) {
	//set the page
	$url = str_replace("{page_index}", $page_index++, $url);
	
	$html = file_get_html($url);
	
	$listing_info = $html->find('div[class=c411ListingInfo]');
	
	if(count($listing_info) > 0) {
	
		$lead_file = "$file_name.csv";
		$fh = fopen($lead_file, 'a') or die("error");
		
		foreach($listing_info as $l) {
			//set last word as last name, the rest as first name
			$name = preg_replace('/\s+/xms', ' ', trim($l->find('a[class=elpBold]', 0)->innertext));
			$name_array = explode(" ", $name);
			$last_name = $name_array[count($name_array) - 1];
			$first_name = trim(str_replace($last_name, "", $name));
			
			//remove city and state/provice, then remove unnecessary mark ups
			$remove = array("<br />", "<br/>", $city.", ".$state);
			$address = str_replace($remove, " ", substr(preg_replace('/\s+/xms', ' ', trim($l->find('span[class=address]', 0)->innertext)), 0, -12));	
			//remove the commas
			$address = str_replace(",", " ", $address);		
			
			//remove address and name, then format phone number
			$remove = array($name, $address);
			$number = str_replace($remove,"", preg_replace('/\s+/xms', ' ', trim($l->plaintext)));	
			$numberpost = strrpos($number, "(", 0);
			$remove = array(")", "-", " ");
			$number = str_replace($remove,"", substr(trim($number), $numberpost, 14));
			
			//echo $first_name.",".$last_name.",".$address.",".$number.",".$city.",".$state.",".$postal_code."<br />";
			
			fwrite($fh, $first_name.",".$last_name.",".$address.",".$number.",".$city.",".$state.",".$postal_code."\n");
							
		} //end of foreach($listing_info as $l)
		fclose($fh);
	} else { 
		$continue = false;
	}//end of if(count($listing_info) > 0)
//} //end of while($continue)

echo $postal_code.": ".count($listing_info)." leads";
//echo $url;
?>
