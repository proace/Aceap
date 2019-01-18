var G_URL;
$(function() {
	var test_server = "http://acesys.ace1.ca/acetest/acesys-2.0/index.php/";
	var live_server = "http://acesys.ace1.ca/index.php/";
	
	var l = $(location).attr('href');

	if(l.indexOf("acesys-2.0") != -1) G_URL = test_server;
	else G_URL = live_server;
	
	$("#job_list").hide();	
	
	$("#jobs_link").click(function(){
		$("#job_list").toggle();	
	});
	
	//set the higlight for this page
	$("#preview_link").addClass("active");
	
	$("#overview_link").click(function(){
		
	});
	
	$("#questions_link").click(function(){
		
	});
	
	$("#notes_link").click(function(){
		
	});
	
});