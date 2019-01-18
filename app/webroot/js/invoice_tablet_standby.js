var G_URL;
var G_TIMER;
$(function() {
	var test_server = "http://acesys.ace1.ca/acetest/acesys-2.0/index.php/";
	var live_server = "http://acesys.ace1.ca/index.php/";
	
	var l = $(location).attr('href');

	if(l.indexOf("acesys-2.0") != -1) G_URL = test_server;
	else G_URL = live_server;
	
	startFetch();
	
	$("#proceed_to_feedback").css("display", "none");
	
	$("#proceed").click(function(){
		window.open(G_URL + "orders/invoiceTabletItems?order_id=" + $("#order_id").val(), "_self");
	});
	
	$("#proceed_to_items").click(function(){
		window.open(G_URL + "orders/invoiceTabletItems?order_id=" + $("#order_id").val(), "_self");
	});
	
	$("#proceed_to_feedback").click(function(){
		window.open(G_URL + "orders/invoiceTabletFeedback?order_id=" + $("#order_id").val(), "_self");
	});
	
	$(".button_send").click(function(){
		stopFetch();
		var button = $(this);
		var order_id = button.next().val();
		button.attr("disabled","disabled");		
		$.post(G_URL + "orders/addInvoiceTabletNotes", 
			{
				"order_id":order_id,
				"message":$("#message").val(),
				"urgency_id":1,
				"note_type_id":3,
			},
			function(data) {
				$("#message").val("");
				button.removeAttr("disabled","disabled");
				fetchNotes();
		});		
	});
	
});

function startFetch() {
	G_TIMER = setTimeout("fetchNotes()", 10000);	
}

function stopFetch() {
	clearTimeout(G_TIMER);	
}

function fetchNotes() {
	$("#button_send").attr("disabled", "disabled");
	$.post(G_URL + "orders/invoiceTabletNotes", 
		{"order_id":$("#order_id").val()},
		function(data) {
			$("#notes").html(data);
	});	
	
	if($("#needs_approval").val() == 0 && $("#needs_job_approval").val() == 0) toggleProceedToFeedback();
	
	$("#button_send").removeAttr("disabled");
	startFetch();
}

function toggleProceedToItems() {
	$("#proceed_to_items").css("display", "inline");
}

function toggleProceedToFeedback() {
	$("#proceed_to_feedback").css("display", "inline");
}