var G_URL;
var G_CONTAINER;
var G_NEW = false;
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
	
	if($("#has_booking").val() != 1) {
		$("#new_bookind_div").hide();
	}
	
	//$("#has_booking").val(0);
	
	//set the higlight for this page
	$("#items_link").addClass("active");
	
	$("#overview_link").click(function(){
		
	});
	
	$("#questions_link").click(function(){
		
	});
	
	$("#notes_link").click(function(){
		
	});	
	
	computeValues();
	/*$("#compute_values").click(function(){
		computeValues();	
	});*/	
	
	$("#test_required").click(function(){
		//if($("#customer_initial").val() != "") {
		//	$("#feedback_form").submit();
		//} else {
		//	alert("Please put your initials");	
		//}
		if($("#FeedbackJobNotesTech").val() == "") {
			alert("Fill in the notes");
		} else $("#feedback_form").submit();
	});	


});



function computeValues() {
	var discount = 0;
	var quantity = 1;
	var price = 0;
	var payable = 0;
	$(".item_detail").each(function(){
		discount = parseFloat($(this).find(".discount_presets").val());
		quantity = parseInt($(this).find(".quantity_presets").val());
		price = parseFloat($(this).find(".base_price").val());
		payable = Math.round(((price*quantity) - discount)*10)/10 ;
		$(this).find(".price_payable").val(payable);
	});
	
	$("#booking_item_detail").each(function(){
		alert("hello");
		discount = parseFloat($(this).find(".discount_presets").val());
		quantity = parseInt($(this).find(".quantity_presets").val());
		price = parseFloat($(this).find(".base_price").val());
		payable = Math.round(((price*quantity) - discount)*10)/10 ;
		$(this).find(".price_payable").val(payable);
	});
}

