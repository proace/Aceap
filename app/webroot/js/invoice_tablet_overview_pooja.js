var G_URL;
$(function() {
	var test_server = "http://acecare.ca/acesys/index.php/";
	var live_server = "/acesys/index.php/";
	
	var l = $(location).attr('href');

	if(l.indexOf("acesys-2.0") != -1) G_URL = test_server;
	else G_URL = live_server;
	
	$("#job_list").hide();	
	
	$("#jobs_link").click(function(){
		$("#job_list").toggle();	
	});
	
	//set the higlight for this page
	$("#overview_link").addClass("active");	
	
	
	$("#JobHistoryWorking").hide();
	$("#show_history").click(function(){
		$(this).hide();
		OpenJobsHistory();	
	});
	
});

function OpenJobsHistory(){
	$("#JobHistoryWorking").show();
	$.get(G_URL + "orders/showCustomerJobs",
		{
			customer_id:$('#InvoiceCustomerId').val(),
			order_id:$('#InvoiceOrderId').val(),
			phone:$('#InvoicePhone').val()
		},
		function(data){
        	$("#JobHistory").html(data);
			$("#JobHistoryWorking").hide();
		});
}