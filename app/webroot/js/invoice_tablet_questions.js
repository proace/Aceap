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
	
	//$(".text_response").val("");
	//$(".responses").val("");
	//$(".suggestions").val("");
	//$(".decisions").val("");
	
	//start question logic
	
		//--------dropdown response-------
	
	$(".responses").change(function(){
		var question_id = $(this).prev(".question_id").val();
		var response_id = $(this).val();
		$(".q" + question_id).addClass("hide");	
		$(".q" + question_id).attr("disabled", "disabled");		
		$("#r" + response_id).removeClass("hide");	
		$("#r" + response_id).removeAttr("disabled", "disabled");	
	});
	
	$(".suggestions").change(function(){
		var response_id = $(this).prev(".response_id").val();
		var suggestion_id = $(this).val();
		$(".r" + response_id).addClass("hide");
		$(".r" + response_id).attr("disabled", "disabled");
		$("#s" + suggestion_id).removeClass("hide");
		$("#s" + suggestion_id).removeAttr("disabled", "disabled");
	});
	
	//--------text response-----------
	
	$(".text_response").change(function(){
		
		var question_id = $(this).prev(".question_id").val();
		var text_response = $(this).val();		
		var response_id = 0;
		
		$(".q" + question_id).addClass("hide");
		$(".q" + question_id).attr("disabled", "disabled");	
				
		
		$(this).next(".response_codes").children(".code").each(function(){
			var id = $(this).children(".id").val();
			var operation_id = $(this).children(".operation_id").val();
			var which_id = $(this).children(".which_id").val();
			var value;
			
			if(which_id == 1) {
				value = $(this).children(".value").val();
			} else if(which_id == 2) {
				var input = $(".rank" + $(this).children(".value").val());
				if(input.is("select")) value = input.find('option:selected').text();
				else value = input.val();
			}			
			
			if(!isNaN(value)) value = parseInt(value);						
			
			switch(operation_id){
				case '1':
					if(text_response == value) {
						
						response_id = id;				
						return false;
					}
				break;	
				case '2':
					if(text_response > value){						
						response_id = id;
						return false;
					}
				break;
				case '3':
					if(text_response >= value) {						
						response_id = id;
						return false;
					}
				break;
				case '4':
					if(text_response < value) {	
										
						response_id = id;
						return false;
					}
				break;
				case '5':
					if(text_response <= value) {	
											
						response_id = id;
						return false;
					}
				break;
				case '6':
					if(text_response != value) {
						
						response_id = id;
						return false;
					}
				break;				
			}
			
			response_id = 0;
			
		});
		
		$("#r" + response_id).removeClass("hide");
		$("#r" + response_id).removeAttr("disabled", "disabled");
	});	
	
	checkText();
	
	$("#skip_to_comments").click(function(){
		$(".next_submit").attr("disabled", "disabled");
		window.open(G_URL + "orders/invoiceTabletFeedback?order_id=" + $("#InvoiceOrderId").val(), "_self");
	});
	
});

function checkText() {
	$(".text_response").each(function(){		
		if($(this).val() == '') return;
		var question_id = $(this).prev(".question_id").val();
		var text_response = $(this).val();		
		var response_id = 0;
		
		$(".q" + question_id).addClass("hide");
		$(".q" + question_id).attr("disabled", "disabled");	
				
		
		$(this).next(".response_codes").children(".code").each(function(){
			var id = $(this).children(".id").val();
			var operation_id = $(this).children(".operation_id").val();
			var which_id = $(this).children(".which_id").val();
			var value;
			
			if(which_id == 1) {
				value = $(this).children(".value").val();
			} else if(which_id == 2) {
				var input = $(".rank" + $(this).children(".value").val());
				if(input.is("select")) value = input.find('option:selected').text();
				else value = input.val();
			}			
			
			if(!isNaN(value)) value = parseInt(value);						
			
			switch(operation_id){
				case '1':
					if(text_response == value) {
						
						response_id = id;				
						return false;
					}
				break;	
				case '2':
					if(text_response > value){						
						response_id = id;
						return false;
					}
				break;
				case '3':
					if(text_response >= value) {						
						response_id = id;
						return false;
					}
				break;
				case '4':
					if(text_response < value) {	
										
						response_id = id;
						return false;
					}
				break;
				case '5':
					if(text_response <= value) {	
											
						response_id = id;
						return false;
					}
				break;
				case '6':
					if(text_response != value) {
						
						response_id = id;
						return false;
					}
				break;				
			}
			
			response_id = 0;
			
		});
		
		$("#r" + response_id).removeClass("hide");
		$("#r" + response_id).removeAttr("disabled", "disabled");
	});	
	
	$(".suggestions").each(function(){
		var response_id = $(this).prev(".response_id").val();
		var suggestion_id = $(this).val();
		$(".r" + response_id).addClass("hide");
		$(".r" + response_id).attr("disabled", "disabled");
		$("#s" + suggestion_id).removeClass("hide");
		$("#s" + suggestion_id).removeAttr("disabled", "disabled");
	});
	
	$("#open_questions").click(function(){
		$("#questions_form").show();
		$("#question_opener").hide();		
		$(this).hide();
	});
}

function checkFields() {
	var ok = true;
	$(".required:not(.hide)").each(function(){
		if($(this).val() == "") {
			$(this).parents("tr").find(".error_indicator").addClass("error_icon");
			ok = false;
		} else {
			$(this).parents("tr").find(".error_indicator").removeClass("error_icon");
		}
	});
	
	return ok;
}