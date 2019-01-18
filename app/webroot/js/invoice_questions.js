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
	$("#questions_link").addClass("active");

	$("#test_required").click(function(){
		if(!checkFields()) {
			alert("Some fields are missing.");				
		} else {
			$("#questions_form").submit();	
		}
	});
	
});

function checkFields() {
	var ok = true;
	$(".required").each(function(){
		if($(this).val() == "") {
			$(this).parents("tr").find(".error_indicator").addClass("error_icon");
			ok = false;
		} else {
			$(this).parents("tr").find(".error_indicator").removeClass("error_icon");
		}
	});
	
	return ok;
}