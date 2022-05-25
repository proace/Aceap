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
	$(".next-btn").click(function(){
		var orderId = $("#orderId").val();
		var orderTypeId = $("#orderTypeId").val();

		window.location.href = G_URL+"orders/techLastPageNew?order_id="+orderId+"&order_type_id="+orderTypeId;
	})
	$("#sendReviewEmail").click(function(){
		var month = ["Jan", "Feb", "Mar", "Apr", "May", "Jun",
"jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
		
		var email = $("#cusEmail").val();
		var cellPhone = $("#cusCellPhone").val();
		var orderId = $("#orderId").val();
		var cusId = $("#cusId").val();
		var orderTypeId = $("#orderTypeId").val();
		var loggedUserId = $("#loggedUserId").val();
		var date_order = $('.order_date').text();
		var get_date = date_order.split('-');
		var get_index = get_date[1]-1;
		var month_job = month[get_index];
		var url_date = get_date[2]+"+"+month_job+"+"+get_date[0];
		 $.ajax({
	        url: G_URL+'orders/sendInvoiceWithReviewLink',
	        dataType: 'html',
	        type: 'POST',
	        cache: false,
	        data: {order_id:orderId, cus_id:cusId, cell_phone:cellPhone, email:email},
	        success: function(data) {
	        		res = JSON.parse(data);
	        		console.log(res);
						if(res.res == "OK")
						{
							if(loggedUserId == 6)
			        		{
			        			target = window.parent.frames["main_view"];
								window.location.href = G_URL+"orders/scheduleView?ffromdate="+url_date;
			        			// window.location.href = G_URL+"pages/main";
			        		} else {
			        			// window.location.href = G_URL+"orders/invoiceTabletPayment?order_id="+orderId+"&sendText=0";
			        			// window.location.href = G_URL+"orders/invoiceTablet";
			        			window.location.href = G_URL+"orders/techLastPageNew?order_id="+orderId+"&order_type_id="+orderTypeId;
			        		}
						}
	            	}           
        	});    
		});
	$("#next_page").click(function(){
		var loggedUserId = $("#loggedUserId").val();
		var orderId = $("#orderId").val();
		window.location.href = G_URL+"orders/invoiceTabletPayment?order_id="+orderId+"&sendText=1";
	})

	$("#book_next_page").click(function(){
		var orderId = $("#orderId").val();
		window.location.href = G_URL+"orders/editBooking?order_id="+orderId;
	})
});

