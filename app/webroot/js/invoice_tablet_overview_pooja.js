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
		// $(this).hide();
		$("#hide_history").show();
		$("#show_history").hide();
		OpenJobsHistory();	
		$("#all_images").hide();
		$("#show_images").show();
		$("#hide_images").hide();
	});

	$("#hide_history").click(function(){
		// $(this).hide();
		$("#hide_history").hide();
		$("#show_history").show();
		$("#JobHistory").hide();
		$("#all_images").hide();
		$("#show_images").show();
	});


	$(".invoice-img-enlarge").dialog({
	        modal: true,
	        autoOpen: false,
	        title: "Photo",
	        autoOpen: false,
	        width: 1024,
	        height: 786,
	    });
    $(".invoice-openImg").live("click",function () {
    	var imgPath = $(this).attr('src');
    	$('.invoice-img-enlarge img').attr('src', imgPath);
        $('.invoice-img-enlarge a').attr('href', imgPath);
        $('.invoice-img-enlarge').dialog('open');
    });
	$("#show_images").live("click", function(){
		$("#all_images").show();
		$("#hide_images").show();
		$("#show_images").hide();
		$("#JobHistory").hide();
		$("#show_history").show();
		$("#hide_history").hide();
		
	});
	$("#hide_images").live("click", function(){
		$("#all_images").hide();
		$("#hide_images").hide();
		$("#show_images").show();
		$("#JobHistory").hide();
		$("#show_history").show();
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
        	$("#JobHistory").show();
			$("#JobHistoryWorking").hide();
		});
}