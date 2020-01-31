var G_URL;
$(function() {
	var test_server = "http://acesys.ace1.ca/acetest/acesys-2.0/index.php/";
	// var live_server = "http://acesys.ace1.ca/index.php/";
	var live_server = "/acesys/index.php/"
	
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

	$("#sendReviewEmail").click(function(){
		var email = $("#cusEmail").val();
		var cellPhone = $("#cusCellPhone").val();
		var orderId = $("#orderId").val();
		var cusId = $("#cusId").val();
		var loggedUserId = $("#loggedUserId").val();
		 $.ajax({
	        url: G_URL+'orders/sendInvoiceWithReviewLink',
	        dataType: 'html',
	        type: 'POST',
	        cache: false,
	        data: {order_id:orderId, cus_id:cusId, cell_phone:cellPhone, email:email},
	        success: function(data) {
	        		res = JSON.parse(data);
						if(res.res == "OK")
						{
							if(loggedUserId == 6)
			        		{
			        			target = window.parent.frames["main_view"];
								target.location = G_URL+"orders/scheduleView";
			        			// window.location.href = G_URL+"pages/main";
			        		} else {
			        			window.location.href = G_URL+"orders/invoiceTabletPayment?order_id="+orderId;
			        		}
						}
	            	}           
        	});    
		});
	$("#next_page").click(function(){
		var loggedUserId = $("#loggedUserId").val();
		var orderId = $("#orderId").val();
		window.location.href = G_URL+"orders/invoiceTabletPayment?order_id="+orderId;
	})
});

