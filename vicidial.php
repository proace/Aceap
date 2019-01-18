<?php 
	function get_data($url)
		{
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}

	/*						version api									
	version - shows version and build of the API, along with the date/time
	*/
	echo "<h4># version api</h4>";
	$version = 'http://96.53.56.234/vicidial/non_agent_api.php?function=version';
	$result = get_data($version);
	echo "<h4>output:</h4>";
	echo $result.'<br><br>';

	/*                      Phone_number_log
	NOTE: api user for this function must have user_level set to 7 or higher and "view reports" enabled

	REQUIRED FIELDS-
	phone_number -		the phone number(s) that you want to pull logs for. allows more than one, separated by commas

	SETTINGS FIELDS-
	stage -			the format of the exported data: csv, tab, pipe(default)
	header -		include a header(YES) or not(NO). This is optional, default is not to include a header
	detail -		(ALL) calls or only (LAST) call. default is (ALL)
	type -			(IN)inbound, (OUT)outbound or (ALL) calls. defauls is (OUT) calls

	NOTES- 
	There is a hard limit of 100000 results
	*/
	echo "<h4># phone_number_log api</h4>";
	$phone_number_log = 'http://96.53.56.234/vicidial/non_agent_api.php?source=test&function=phone_number_log&stage=pipe&user=ALIACE&pass=Dguk7vKS9wQX&phone_number=3477080107,9998887112&type=ALL';
	$result = get_data($phone_number_log);
	echo "<h4>output:</h4>";
	echo $result.'<br><br>';

	/*echo "<h4>external_status api</h4>";
	$external_status = 'http://96.53.56.234/agc/api.php?source=test&user=ALIACE&pass=Dguk7vKS9wQX&agent_user=ACE1&function=external_status&value=CALLBK&callback_datetime=2012-01-25+12:00:00&callback_type=USERONLY&callback_comments=callback+comments+go+here&qm_dispo_code=1234';
	$result = get_data($external_status);
	echo "<h4>Output:</h4>";
	echo $result.'<br><br>';*/

	/* 							Call_dispo_report
	call_dispo_report - call disposition breakdown report

	NOTE: api user for this function must have user_level set to 9 and "view reports" enabled

	REQUIRED FIELDS(one of the following)-
	campaigns -		Campaigns to return stats on.  Use single dash delimiters if requesting more than one specific campaign
	ingroups -		In-Groups to return stats on.  Use single dash delimiters if requesting more than one specific inbound group
	dids -			DIDs to return stats on.  Use single dash delimiters if requesting more than one specific DID

	OPTIONAL FIELDS-
	query_date -	Date to report on, leave blank to default to today's date.  Must be in YYYY-MM-DD format
	end_date -	Date to report on, leave blank to default to today's date.  Must be in YYYY-MM-DD format
	statuses -	List of specific statuses to report on.  Leave blank for all, or use dash delimiters if requesting more than one status.
	categories -	List of specific status categories to report on.  Leave blank for all, or use dash delimiters if requesting more than one category.
	users -		List of specific users to report on.  Use single dash delimiters if requesting more than one specific user
	status_breakdown -	[0,1] Breakdown of all statuses within selected elements, default 0
	show_percentages -	[0,1] (Only works if status_breakdown above is enabled), will show percentages of statuses, default 0
	file_download -		[0,1] Download as a CSV file, default 0

	CALL DISPOSITION
	A - Answering Machine 
	B - Busy 
	CALLBK - Call Back *
	DC - Disconnected Number 
	DEC - Declined Sale 
	DNC - DO NOT CALL 
	N - No Answer 
	NI - Not Interested 
	NP - No Pitch No Price 
	SALE - Sale Made 
	WRNBR - Wrong Number 
	XFER - Call Transferred 
	*/
	echo "<h4># call_dispo_report api</h4>";
	$call_dispo_report = 'http://96.53.56.234/vicidial/non_agent_api.php?source=test&user=ALIACE&pass=Dguk7vKS9wQX&function=call_dispo_report&campaigns=ACECARE&ingroups=AGENTDIRECT&query_date=2018-04-05&end_date=2018-04-29&status_breakdown=1';
	$result = get_data($call_dispo_report);
	echo "<h4>output:</h4>";
	echo $result.'<br><br>';

	
	/*								Call_status_stats
	call_status_stats - report on number of calls made by campaign and ingroup, with hourly and status breakdowns

	NOTE: api user for this function must have user_level set to 8 or higher and "view reports" enabled

	REQUIRED FIELDS-
	campaigns -		Campaigns to return stats on.  Use "---ALL---" or "ALLCAMPAIGNS" for all campaigns, or use single dash delimiters if 
	                        requesting more than one specific campaign

	OPTIONAL FIELDS-
	query_date -	Date to report on, leave blank to default to today's date.  Must be in YYYY-MM-DD format
	ingroups -	List of ingroups to report on.  Leave blank for all ingroups belonging to the campaigns specified in the "campaigns" variable.
			Use dash delimiters if requesting more than one ingroup.
	statuses -	List of specific statuses to report on.  Leave blank for all, or use dash delimiters if requesting more than one status.
	*/
	echo "<h4># call_status_stats api</h4>";
	$call_status_stats = 'http://96.53.56.234/vicidial/non_agent_api.php?source=test&user=ALIACE&pass=Dguk7vKS9wQX&function=call_status_stats&campaigns=ACECARE&query_date=2018-04-18';
	$result = get_data($call_status_stats);
	echo "<h4>output:</h4>";
	echo $result.'<br><br>';


	/*                         Add Lead
	NOTE: api user for this function must have modify_leads set to 1 and user_level
      must be set to 8 or higher

	REQUIRED FIELDS-
	phone_number -		must be all numbers, 6-16 digits
	phone_code -		must be all numbers, 1-4 digits, defaults to 1 if not set
	list_id -		must be all numbers, 3-12 digits, defaults to 999 if not set
	source -		description of what originated the API call (maximum 20 characters)

	SETTINGS FIELDS-
	dnc_check -		Y, N or AREACODE, default is N
	campaign_dnc_check -	Y, N or AREACODE, default is N
	campaign_id -		2-8 Character campaign ID, required if using campaign_dnc_check or callbacks
	add_to_hopper -		Y or N, default is N
	hopper_priority -	99 to -99, the higher number the higher priority, default is 0
	hopper_local_call_time_check - Y or N, default is N. Validate the local call time and/or state call time before inserting lead in the hopper
	duplicate_check -	Check for duplicate records in the system, can select more than one (duplicate_check=DUPLIST-DUPTITLEALTPHONELIST)
				If duplicate is found, will return error, the duplicate data and lead_id and list_id of existing record
				Here are the duplicate_check options:
					DUPLIST - check for duplicate phone_number in same list
					DUPCAMP - check for duplicate phone_number in all lists for this list's campaign
					DUPSYS - check for duplicate phone_number in entire system
					DUPTITLEALTPHONELIST - check for duplicate title and alt_phone in same list
					DUPTITLEALTPHONECAMP - check for duplicate title and alt_phone in all lists for this list's campaign
					DUPTITLEALTPHONESYS - check for duplicate title and alt_phone in entire system
					DUPNAMEPHONELIST - check for duplicate first_name, last_name and phone_number in same list
					DUPNAMEPHONECAMP - check for duplicate first_name, last_name and phone_number in all lists for this list's campaign
					DUPNAMEPHONESYS - check for duplicate first_name, last_name and phone_number in entire system
					 "  90DAY - Added to one of the above duplicate checks(i.e. "DUPSYS90DAY"), only checks leads loaded in last 90 days
	usacan_prefix_check -	Y or N, default is N. Check for a valid 4th digit for USA and Canada phone numbers (cannot be 0 or 1)
	usacan_areacode_check -	Y or N, default is N. Check for a valid areacode for USA and Canada phone numbers(also checks for 10-digit length)
	nanpa_ac_prefix_check -	Y or N, default is N. Check for a valid NANPA areacode and prefix, if optional NANPA data is on the system
	custom_fields -		Y or N, default is N. Defines whether the API will accept custom field data when inserting leads into the vicidial_list table
				For custom fields to be inserted, just add the field label as a variable to the URL string
				For example, if the field_label is "favorite_color" you would add "&favorite_color=blue"
	tz_method -		<empty>, POSTAL, TZCODE or NANPA, default is <empty> which will use the country code and areacode for time zone lookups
					POSTAL relies on the postal_code field
					TZCODE relies on the owner field being populated with a proper time zone code
					NANPA relies on the optional NANPA areacode prefix data being loaded on your system
	callback -		Y or N, default is N. Set this lead as a scheduled callback. campaign_id field is REQUIRED for callbacks
	callback_status -	1-6 Character, callback status to use, default is CALLBK (vicidial_list status will be set to CBHOLD to lock)
	callback_datetime -	YYYY-MM-DD+HH:MM:SS, date and time of scheduled callback. REQUIRED if callback is set. NOW can be used for current datetime.
	callback_type -		USERONLY or ANYONE, default is ANYONE
	callback_user -		User ID the USERONLY callback is assigned to
	callback_comments -	Optional comments to appear when the callback is called back
	lookup_state -		Y or N, default is N. Looks up state field from areacode list. Only works if the 'state' field is not populated.

	(for fields with spaces in the values, you can replace the space with a plus + sign[address, city, first_name, etc...])
	OPTIONAL FIELDS- 
	vendor_lead_code -	1-20 characters
	source_id  -		1-50 characters
	gmt_offset_now -	overridden by auto-lookup of phone_code and area_code portion of phone number if applicable
	title -			1-4 characters
	first_name -		1-30 characters
	middle_initial -	1 character
	last_name -		1-30 characters
	address1 -		1-100 characters
	address2 -		1-100 characters
	address3 -		1-100 characters
	city -			1-50 characters
	state -			2 characters
	province -		1-50 characters
	postal_code -		1-10 characters
	country_code -		3 characters
	gender -		U, M, F (Undefined, Male, Female) - defaults to 'U'
	date_of_birth -		YYYY-MM-DD
	alt_phone -		1-12 characters
	email -			1-70 characters
	security_phrase -	1-100 characters
	comments -		1-255 characters
	multi_alt_phones -	5-1024 characters (see examples for more info)
	rank -			1-5 digits
	owner -			1-20 characters (user ID, Territory or user group)
	entry_list_id -		WARNING! ONLY USE IF YOU KNOW WHAT YOU ARE DOING, CAN BREAK CUSTOM FIELDS! (must be all numbers, 3-12 digits, will not work if custom_fields is set to Y)
	*/

	echo "<h4># add_lead - adds a new lead to the vicidial_list table with several fields and options</h4>";
	$add_lead = 'http://96.53.56.234/vicidial/non_agent_api.php?source=test&user=ALIACE&pass=Dguk7vKS9wQX&function=add_lead&phone_number=3477180107&phone_code=91&list_id=10003&campaign_id=ACECARE&callback=Y&callback_status=CALLBK&callback_datetime=2018-04-25+11:00:00&callback_type=USERONLY&callback_user=ACE1&callback_comments=Comments+go+here&duplicate_check=DUPLIST';
	$result = get_data($add_lead);
	echo "<h4>output:</h4>";
	echo $result.'<br><br>';

	
	/* 							Update Lead
	REQUIRED FIELDS-
	lead_id -		must be all numbers, 1-9 digits, not required if using vendor_lead_code or phone_number
	vendor_lead_code -	can be used instead of lead_id to match leads
	phone_number -		can be used instead of lead_id or vendor_lead_code to match leads
	source -		description of what originated the API call (maximum 20 characters)

	SETTINGS FIELDS-
	search_method -		You can combine the following 3 options in this field to search the parameters you desire:
					LEAD_ID, will attempt to find a match with the lead_id
					VENDOR_LEAD_CODE, will attempt to find a match with the vendor_lead_code
					PHONE_NUMBER, will attempt to find a match with the phone_number
				  For example to search lead_id and vendor_lead_code: "&search_method=LEAD_ID_VENDOR_LEAD_CODE"
				  The search order is NOT preserved, Lead ID is always first, Vendor Lead Code is second
				  and Phone number is last. Default is "LEAD_ID"
	search_location -	Where to check for records in the system, can select only one(default is SYSTEM):
					LIST - check for lead in same list
					CAMPAIGN - check for lead in all lists for this list's campaign
					SYSTEM - check for lead in entire system
				  If no list_id is defined, the the search_location will be assumed as SYSTEM
	insert_if_not_found -	Y or N, will attempt to insert as a NEW lead if no match is found, default is N.
				Insertion will require phone_code, phone_number and list_id. lead_id will be ignored.
				Most of the add_lead options that are not available if you use this setting in this function
	records -		number of records to update if more than 1 found (defaults to '1'[most recently loaded lead])
	custom_fields -		Y or N, default is N. Defines whether the API will accept custom field data when updating leads in the vicidial_list table
				For custom fields to be updated, just add the field label as a variable to the URL string
				For example, if the field_label is "favorite_color" you would add "&favorite_color=blue"
	no_update -		Y or N, Setting this to Y will not perform any updates, but will instead only tell
				you if a lead exists that matches the search criteria, default is N.
	delete_lead -		Y or N, Setting this to Y will delete the lead from the vicidial_list table, default is N.
	reset_lead -		Y or N, Setting this to Y will reset the called-since-last-reset flag of the lead, default is N.
	callback -		Y, N or REMOVE, default is N. Set this lead as a scheduled callback. REMOVE will delete the scheduled callback entry
	callback_status -	1-6 Character, callback status to use, default is CALLBK (vicidial_list status will be set to CBHOLD to lock)
	callback_datetime -	YYYY-MM-DD+HH:MM:SS, date and time of scheduled callback. REQUIRED if callback is set. NOW can be used for current datetime.
	callback_type -		USERONLY or ANYONE, default is ANYONE
	callback_user -		User ID the USERONLY callback is assigned to
	callback_comments -	Optional comments to appear when the callback is called back
	update_phone_number -	Y or N, Optional setting to update the phone_number field, default is N.
	add_to_hopper -		Y or N, default is N
	hopper_priority -	99 to -99, the higher number the higher priority, default is 0
	hopper_local_call_time_check - Y or N, default is N. Validate the local call time and/or state call time before inserting lead in the hopper

	EDITABLE FIELDS- 
	user_field -		1-20 characters, this updates the 'user' field in the vicidial_list table
	list_id_field -		3-12 digits, this updates the 'list_id' field in the vicidial_list table
	status -		1-6 characters, not punctuation or spaces
	vendor_lead_code -	1-20 characters
	source_id  -		1-50 characters
	gmt_offset_now -	overridden by auto-lookup of phone_code and area_code portion of phone number if applicable
	title -			1-4 characters
	first_name -		1-30 characters
	middle_initial -	1 character
	last_name -		1-30 characters
	address1 -		1-100 characters
	address2 -		1-100 characters
	address3 -		1-100 characters
	city -			1-50 characters
	state -			2 characters
	province -		1-50 characters
	postal_code -		1-10 characters
	country_code -		3 characters
	gender -		U, M, F (Undefined, Male, Female) - defaults to 'U'
	date_of_birth -		YYYY-MM-DD
	alt_phone -		1-12 characters
	email -			1-70 characters
	security_phrase -	1-100 characters
	comments -		1-255 characters
	rank -			1-5 digits
	owner -			1-20 characters (user ID, Territory or user group)
	called_count -		digits only, the number of attempts dialing the lead
	entry_list_id -		WARNING! ONLY USE IF YOU KNOW WHAT YOU ARE DOING, CAN BREAK CUSTOM FIELDS! (must be all numbers, 3-12 digits, will not work if custom_fields is set to Y)
	force_entry_list_id -	WARNING! ONLY USE IF YOU KNOW WHAT YOU ARE DOING, CAN BREAK CUSTOM FIELDS! (must be all numbers, 3-12 digits, will override entry_list_id to this value in all custom fields queries executed by this command)
	NOTES: 
	 - in order to set a field to empty('') set it equal to --BLANK--, i.e. "&province=--BLANK--"
	 - please use no special characters like apostrophes, double-quotes or amphersands
	*/
	echo "<h4># update_lead - updates lead information in vicidial_list and associated custom table</h4>";
	$update_lead = 'http://96.53.56.234/vicidial/non_agent_api.php?source=test&user=ALIACE&pass=Dguk7vKS9wQX&function=update_lead&lead_id=47717&vendor_lead_code=10003&phone_number=3477080107&first_name=BRAIN&middle_initial=J&last_name=SMITH&campaign_id=ACECARE&callback=Y&callback_status=CALLBK&callback_datetime=2018-04-22+23:23:04&callback_type=USERONLY&callback_user=ACE1&callback_comments=Comments+go+here+again';
	$result = get_data($update_lead);
	echo "<h4>output:</h4>";
	echo $result.'<br><br>';




        echo "<h4># did_log_export api</h4>";
	$did_log_export = 'http://96.53.56.234/vicidial/non_agent_api.php?source=test&user=ALIACE&pass=Dguk7vKS9wQX&function=did_log_export&phone_number=3477180107&date=2018-04-23&header=YES&sage=pipe';
	$result = get_data($did_log_export);
	echo "<h4>output:</h4>";
	echo $result.'<br><br>';

	
	echo "<h4># agent_status api</h4>";
	$agent_status = 'http://96.53.56.234/vicidial/non_agent_api.php?source=test&user=ALIACE&pass=Dguk7vKS9wQX&function=agent_status&agent_user=ACE1&stage=pipe&header=YES';
	$result = get_data($agent_status);
	echo "<h4>output:</h4>";
	echo $result.'<br><br>';
	/*
	status|callerid|lead_id|campaign_id|calls_today|full_name|user_group|user_level 
	READY |        |0      |ACECARE    |3          |Testing  |ADMIN     |3         |||||8600051 
	*/

	echo "<h4># lead_field_info api</h4>";
	$lead_field_info = 'http://96.53.56.234/vicidial/non_agent_api.php?source=test&user=ALIACE&pass=Dguk7vKS9wQX&function=lead_field_info&field_name=first_name&lead_id=47718&lead_id=Y&list_id=10003';
	$result = get_data($lead_field_info);
	echo "<h4>output:</h4>";
	echo $result.'<br><br>';

        echo "<h4># recording_lookup api</h4>";
	$recording_lookup = 'http://96.53.56.234/vicidial/non_agent_api.php?source=test&user=ALIACE&pass=Dguk7vKS9wQX&function=recording_lookup&stage=pipe&agent_user=ACE1&date=2018-04-26&header=YES';
	$result = get_data($recording_lookup);
	echo "<h4>Output:</h4>";
	echo $result.'<br><br>';
	

	/** AGENT API's **/

	echo "<h4># st_get_agent_active_lead api</h4>";
	$st_get_agent_active_lead = 'http://96.53.56.234/agc/api.php?source=test&user=ALIACE&pass=Dguk7vKS9wQX&agent_user=ACE1&function=st_get_agent_active_lead&value=ACE1&vendor_id=207';
	$result = get_data($st_get_agent_active_lead);
	echo "<h4>output:</h4>";
	echo $result.'<br><br>';
?>
<?php 
  /*$curl_handle=curl_init();
  curl_setopt($curl_handle,CURLOPT_URL,'http://96.53.56.234/vicidial/non_agent_api.php?source=test&user=ALIACE&pass=Dguk7vKS9wQX&function=update_lead&lead_id=999&last_name=SMITH');
  curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,2);
  curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
  $buffer = curl_exec($curl_handle);
  curl_close($curl_handle);
  if (empty($buffer)){
      print "Nothing returned from url.<p>";
  }
  else{
      print $buffer;
  }*/
?>