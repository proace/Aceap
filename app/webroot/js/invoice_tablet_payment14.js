var G_URL;
var G_CONTAINER;
var G_NEW = false;
$(function() {
	var test_server = "http://acesys.ace1.ca/acetest/acesys-2.0/index.php/";
	var live_server = "/acesys/index.php/";
	
	var l = $(location).attr('href');
	
	//Loki payment API
    $("#cardTransaction").live("click", function(){
        var cardName = $("#creditName").val().trim();
        var cardNum = $("#creditCardNum").val().trim();
        var ExpMonth = $("#cardExpMonth").val();
        var ExpYear = $("#cardExpYear").val();
        var cvv = $("#creditCvv").val().trim();
        var amount = $("#saleAmount").val();
        var orderId = $('#InvoiceOrderId').val();
        var orderNum = $('#orderNum').val();
        var cusId = $("#customerId").val();
        var to_email = $("#toEmail").val();
        var status = $("#payment_status").val();
        var method = $("#PaymentPaymentMethodId").val();
        if(cardNum.indexOf('x') != -1 ){
           alert("Please Contact Admin.");
            return false;
        }
        if(method == 0){
            alert("Please select paid by method.");
            return false;
        }
        if (cardName.length <= 0){
            alert("Please enter Card Name");
            return false;
        }

        if (cardNum.length < 13){
            alert("Please enter valid Card Number");
            return false;
        }

        if (cvv.length < 3){
            alert("Please enter valid CVV");
            return false;
        }

        if(amount <= 0){
            alert("Please enter Amount");
            return false;   
        }
        $("#cardTransaction").val("In Progress");
    	$("#cardTransaction").attr("disabled", true);
         $.ajax({
            url: G_URL+'orders/bamboraPay',
            dataType: 'JSON',
            type: 'POST',
            cache: false,
            data: {order_id:orderId, cus_id:cusId,cardName:cardName,cardNum:cardNum,ExpMonth:ExpMonth,cvv:cvv,
            	amount:amount,ExpYear:ExpYear,to_email:to_email,order_num:orderNum,status:status},
            success: function(data) {
            	$("#cardTransaction").val("Charge");
    			$("#cardTransaction").attr("disabled", false);
                        if(data.res == 1)
                        {
                            $("#transaction_status").text("Approved");
                            $("#transaction_status").css('color', 'green');
                            $("#transaction_amount").text(data.amount);
                            $("#transaction_auth").text(data.auth_code);
                            $("#transaction_date").text(data.date);
                            $("#cardTransaction").attr("disabled", true);
                            var trailingCharsIntactCount = 4;
                            var str = $("#creditCardNum").val().trim();
                            if(str != ''){
                                str = new Array(str.length - trailingCharsIntactCount + 1).join('x')
                               + str.slice(-trailingCharsIntactCount);
                                $("#creditCardNum").val(str);
                            }
                           alert("Payment Done Successfully.");
                        } else {
                            $("#transaction_status").text("Declined");
                            $("#transaction_status").css('color', 'red');
                            $("#transaction_auth").text('');
                            $("#transaction_amount").text(data.amount);
                            $("#transaction_date").text(data.date);
                            alert(data.msg);
                        }
                    }           
            });   
    });
      $("#saveCardInfo").live("click", function(){
        var cardName = $("#creditName").val().trim();
        var cardNum = $("#creditCardNum").val().trim();
        var ExpMonth = $("#cardExpMonth").val();
        var ExpYear = $("#cardExpYear").val();
        var cvv = $("#creditCvv").val().trim();
        var amount = $("#saleAmount").val();
        var orderId = $('#InvoiceOrderId').val();
        var cusId = $("#customerId").val();

        if (cardName.length <= 0){
            alert("Please enter Card Name");
            return false;
        }

        if (cardNum.length < 13){
            alert("Please enter valid Card Number");
            return false;
        }

        if (cvv.length < 3){
            alert("Please enter valid CVV");
            return false;
        }
         $.ajax({
            url: G_URL+'orders/saveCardInfo',
            dataType: 'JSON',
            type: 'POST',
            cache: false,
            data: {order_id:orderId, cus_id:cusId,cardName:cardName,cardNum:cardNum,ExpMonth:ExpMonth,cvv:cvv,ExpYear:ExpYear},
            success: function(data) {
                        if(data.res == 1)
                        {
                           alert("Card Information saved Successfully.");
                        } else {
                            alert(data.msg);
                        }
                    }           
            });    
    });
	// Delete purchase images
	$(".delete-purchase-image").live("click", function(){
		var id = $(this).attr("image-id");
		var imgPath = $(this).attr("image-name");
		$.ajax({
		url: live_server+'payments/deletePartImage',
		dataType: 'html',
		type: 'POST',
		cache: false,
		data: {id:id, imgPath:imgPath},
		success: function(data) {
				res = JSON.parse(data);
				if(res.res == "OK")
				{
					location.reload(true);
				}
			}			
		});
	});

	//Loki: open pay via office box
	$("#officeBox").dialog({
            modal: true,
            autoOpen: false,
            title: "Pay Via Office",
            autoOpen: false,
            width: 700,
            height: 500,
        });
    $('#payViaOfc').live('click', function(){
    	$( "#officeBox" ).dialog("open");
    });
    //Loki: open tech recommendation box.
	$("#recommendBox").dialog({
            modal: true,
            autoOpen: false,
            title: "Tech Recommend",
            autoOpen: false,
            width: 700,
            height: 500,
        });
    $('#techRecommended').live('click', function(){
    	$( "#recommendBox" ).dialog("open");
    });

    $("#saveRecommend").live("click", function(){
    	var msg = $("#recommendMsg").val();
    	var orderNum = $("#orderNum").val();
    	var orderId = $("#InvoiceOrderId").val();
    	$.ajax({
			url: live_server + "orders/addTechRecommendation",
			type: "post",
			dataType:"json",
			data: {orderId:orderId, msg:msg, orderNum:orderNum},
			success: function(data)
			{
				if(data.res == 1){
					$("#recommendBox").dialog("close");
				}
			}
		});
    });

    $("#saveOfficePay").live("click", function(){
    	var msg = $("#officeMsg").val();
    	var orderNum = $("#orderNum").val();
    	var orderId = $("#InvoiceOrderId").val();
    	$.ajax({
			url: live_server + "orders/payViaOffice",
			type: "post",
			dataType:"json",
			data: {orderId:orderId, msg:msg, orderNum:orderNum},
			success: function(data)
			{
				if(data.res == 1){
					$("#officeBox").dialog("close");
				}
			}
		});
    });
	if(l.indexOf("acesys-2.0") != -1) G_URL = test_server;
	else G_URL = live_server;
	
	$("#job_list").hide();	
	
	// $("#jobs_link").click(function(){
	// 	$("#job_list").toggle();	
	// });
	
	//initializeItems();
	
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
	
	$(".items_button").live("click", function(){
		
		$("#items_container").toggle();
		if($(this).hasClass("service_tab")) $('#tabs').tabs("option", "selected", 0);
		if($(this).hasClass("furnace_tab")) $('#tabs').tabs("option", "selected", 1);
		if($(this).hasClass("boiler_tab")) $('#tabs').tabs("option", "selected", 2);
		if($(this).hasClass("hotwater_tab")) $('#tabs').tabs("option", "selected", 3);
		if($(this).hasClass("split_tab")) $('#tabs').tabs("option", "selected", 4);
		if($(this).hasClass("fireplace_tab")) $('#tabs').tabs("option", "selected", 5);
		if($(this).hasClass("accessories_tab")) $('#tabs').tabs("option", "selected", 6);
		if($(this).hasClass("parts_tab")) $('#tabs').tabs("option", "selected", 7);
		if($(this).hasClass("permit_tab")) $('#tabs').tabs("option", "selected", 8);
		if($(this).hasClass("heatpumps_tab")) $('#tabs').tabs("option", "selected", 9);
		
		G_CONTAINER = $("#item_details");
		G_NEW = false;
		
	});
	
	$(".new_booking_items").click(function(){
		$("#items_container").toggle();
		if($(this).hasClass("service_tab")) $('#tabs').tabs("option", "selected", 0);
		if($(this).hasClass("furnace_tab")) $('#tabs').tabs("option", "selected", 1);
		if($(this).hasClass("boiler_tab")) $('#tabs').tabs("option", "selected", 2);
		if($(this).hasClass("hotwater_tab")) $('#tabs').tabs("option", "selected", 3);
		if($(this).hasClass("split_tab")) $('#tabs').tabs("option", "selected", 4);
		if($(this).hasClass("fireplace_tab")) $('#tabs').tabs("option", "selected", 5);
		if($(this).hasClass("accessories_tab")) $('#tabs').tabs("option", "selected", 6);
		if($(this).hasClass("parts_tab")) $('#tabs').tabs("option", "selected", 7);
		if($(this).hasClass("permit_tab")) $('#tabs').tabs("option", "selected", 8);
		if($(this).hasClass("heatpumps_tab")) $('#tabs').tabs("option", "selected", 9);
		G_CONTAINER = $("#booking_item_details");
		G_NEW = true;		
	});
		
	
	computeValues();
	
	$(".discount_presets").live("change", function(){
		computeValues();	
	});
	
	$(".quantity_presets").live("change", function(){
		computeValues();	
	});
	$(".org_price").live("change", function(){
		price = $(this).val();
		$(this).parent().next().find(".base_price").val(price);
		computeValues();	
	});
	
	//hover effect	
	$('tbody tr').live('mouseover mouseout', function(event) {
	  if (event.type == 'mouseover') {				
		$(this).addClass('hovered');		
	  } else {
		$(this).removeClass('hovered');
	  }
	});
	
	//default each row to visible
	$('tbody tr').addClass('visible');
	
	initializeFilter('#filter_name');
	
	$('#link_filter_name').live('click', function(){
		$('#filter_name').keyup();
	});
	
	$(".delete_button").live("click", function() {
		$(this).parents("tr").remove();
	});
	
	$(".tabs tr.item").live("click", function(){		
		$(this).children(".item_id").val();
		$(this).children(".item_name").val();
		$(this).children(".item_model").val();
		$(this).children(".item_regular_price").val();
		$(this).children(".item_selling_price").val();
		$(this).children(".item_supplier_price").val();
		$(this).children(".item_efficiency").val();
		$(this).children(".item_category_id").val();
		$(this).children(".item_brand_id").val();
		$(this).children(".item_supplier_id").val();
		$(this).children(".item_description1").val();
		$(this).children(".item_description2").val();
		$(this).children(".item_mode").val();
		
		addItem(G_CONTAINER, $(this).children(".item_id").val(), $(this).children(".item_name").val(), $(this).children(".item_category_id").val(), $(this).children(".item_selling_price").val(),$(this).children(".item_supplier_price").val(), G_NEW);
		
		$("#items_container").hide();
		computeValues();
	});
	
	$("#test_required").click(function(){
		var current_balance = parseFloat($("#current_balance").val());		
		if((!checkInstalled() || current_balance < 0) && $("#saved_booking").val() == 0) {
			if($("#has_booking").val() == 1) {
				if($("#BookingOrderTypeId").val() != "") {
					$("#items_form").submit();
				} else {
					alert("Select a Job Type for the New Booking");
				}
			} else {
				var newBooking = confirm("Some parts are not installed or an extra deposit has been made. Do you want to place a New Booking?");	
				if(newBooking) {
					$("#has_booking").val(1);							
					$("#new_bookind_div").css("display", "block");
					$("#test_required").val("Go to Questions");						
				}
			}
		} else {
			if($("#Order2Id").val() != undefined) {;
				if($("#Order2JobDate").val() == "") {					
					alert("Please set the schedule");
				}
			} else {
				$("#items_form").submit();
			}
		}
	});
	
	$("#delete_attached").click(function(){
		var deleteAttached = confirm("Do you want to delete this attached booking?");
		if(deleteAttached) {
			$("#delete_this").val(1);		
			$("#items_form").submit();
		}
	});
	
	//timeslot feature
	
	
	$("#load_timeslots").click(function(){
		var url = G_URL + "orders/invoiceTabletTimeSlots";
		var city_id = $("#city_id").val();
		$(this).hide();
		$("#loading_timeslots").show();		
		$.post(url,
			{
				city_id:city_id
			},
			function(data){
				$("#timeslot_div").html(data);
				$('#timeslot_tabs').tabs();
				$("#loading_timeslots").hide();
				$("#close_timeslots").show();
		});
	});
	
	$("#loading_timeslots").hide();
	$("#close_timeslots").hide();
	
	$("#close_timeslots").click(function(){
		$("#timeslot_div").html("");
		$(this).hide();
		$("#load_timeslots").show();
		$("#slot_confirmations").html("");		
	});
	
	$(".slot a").live("click", function(){
		$("#slot_confirmations").html("");
		var job_date = $(this).children(".job_date").val();
		var week_number = $(this).children(".week_number").val();
		var job_time_beg = $(this).children(".job_time_beg").val();
		var job_time_end = $(this).children(".job_time_end").val();
		var job_time_name = $(this).children(".job_time_name").val();
		var city_id = $("#city_id").val();
		var route_type = $(this).children(".route_type").val();
		
		var url = G_URL + "orders/reserveTimeslot";
		
		$.post(url,
			{
				job_date:job_date,
				week_number:week_number,
				job_time_beg:job_time_beg,
				job_time_end:job_time_end,
				job_time_name:job_time_name,
				city_id:city_id,
				route_type:route_type
			},
			function(data){
				$("#slot_confirmations").html(data);
		});	
				
	});
	
	$("#slot_yes").live("click", function(){
		bookTimeslot();
		$("#close_timeslots").click();
	});
	
	$("#slot_no").live("click", function(){
		$("#slot_confirmations").html("");
	});
	
	$("#current_deposit").change(function(){
		computeValues();	
	});
	
	// $("#save_payment").click(function(){
	// 	SavePayment();	
	// });

	$("#saveJobPayment").click(function(){
		SavePayment();	
	});
	$("#save-payment-image").click(function(){
		SaveTechPaymentImg();	
	});
	showPayments();
	
	// check attach payment checkbox is checked

	$("#paymnetReceipt").live("change", function(){
        if($(this).is(":checked"))
        {
             $("#paymnetReceipt").val(1);
        } else {
        	$("#paymnetReceipt").val(0);
        }
     });

		$("#notSendInvoice").live("change", function(){
        if($(this).is(":checked"))
        {
             $("#notSendInvoice").val(1);
        } else {
        	$("#notSendInvoice").val(0);
        }
     });
	

	$(".installation_item_box").dialog({
	        modal: true,
	        autoOpen: false,
	        title: "Items",
	        autoOpen: false,
	        width: 800,
	        height: 786,
	    });

	$(".installation_items").live("click", function(){
		$('.installation_item_box').dialog('open');
	});

	$("#saveInstallationItem").live("click", function(e){
        e.preventDefault();
        var formdata = new FormData($("#installation_item_form")[0]);
        var xhr = new XMLHttpRequest();
        // var  = <?php echo BASE_URL; ?>;
        xhr.open('POST', live_server +"orders/saveTechInstallation", true);
        xhr.onload = function () {
            var response = JSON.parse(xhr.response);
            if(response.res == 1)
            {
                $('.installation_item_box').dialog('close');
            } 
        };
        xhr.send(formdata);
    }); 
});
//Loki:
function InstallationCalculation()
{
	subtotal = 0;
    CheckPrcCount = 0;
    invoiceSubTotal = 0;
    amount = 0;
    $('#installation_item_table').children('tbody').children('.booked').each(function(i, el)
    {
    	qty = $(el).children('.tech_quantity').children('input').val();  
    	if (qty!=null && qty != undefined)
		{
	    	prc = $(el).children('.price').children('input').val();
	    	amount = parseFloat(prc) * qty;
			subtotal += amount;
			$(el).children('.total').children('input').val(amount);
		}
    });
	$(".installationTotal").html('Total = $ '+subtotal);
}
function addItem(container, item_id, name, item_category_id, price, price_purchase, isNew) {
	var temp = '';
	var five = Math.round((parseFloat(price)*0.05)*10)/10;
	var ten = Math.round((parseFloat(price)*0.10)*10)/10;
	var hundred = 100;
	var fifty = 50;
	var bookingItem = "BookingItem";
	if(isNew) bookingItem = "BookingItem2";
	var bookingDetail = "item_detail";
	if(isNew) bookingDetail = "booking_item_detail";
		
	temp += '<tr class="' + bookingDetail + '">';
    
	if(item_id == 1024) {		
		temp += '	<td class="left">';
		temp += '	<input type="text" style="width:200px;text-align:left" value="-custom part-" name="data[' + bookingItem + '][' + item_id + '][name]">';	
	} else {
		temp += '	<td class="left">' + name;
		temp += '	<input type="hidden" value="' + name + '" name="data[' + bookingItem + '][' + item_id + '][name]">';
	}
	
	temp += '	<input type="hidden" value="' + price_purchase + '" name="data[' + bookingItem + '][' + item_id + '][price_purchase]">';
	temp += '	<input type="hidden" value="' + item_category_id + '" name="data[' + bookingItem + '][' + item_id + '][item_category_id]">';
	temp += '	<input type="hidden" value="0" name="data[' + bookingItem + '][' + item_id + '][addition]">';
	temp += '	</td>';
    temp += '   <td class="center">';
    temp += '		<select class="installed" name="data[' + bookingItem + '][' + item_id + '][installed]">';
    temp += '			<option value="0"></option>';
    temp += '			<option value="2">No</option>';
	temp += '			<option value="1">Yes</option>';
    temp += '		</select>';
    temp += '	</td>';
	temp += '   <td class="center">';
	
	//if appliance
	/*if(parseInt(item_category_id) == 2 || parseInt(item_category_id) == 3 || parseInt(item_category_id) == 4 || parseInt(item_category_id) == 5 || parseInt(item_category_id) == 6 || parseInt(item_category_id) == 10) {
	temp += '	<select class="discount_presets" name="data[' + bookingItem + '][' + item_id + '][discount]">';
	temp += '		<option value="0.00">0.00</option>';
    temp += '		<option value="' + fifty + '">' + fifty + '</option>';
	temp += '		<option value="' + hundred + '">' + hundred + '</option>';
	temp += '	</select>';
	} else { //not appliance
	temp += '	<select class="discount_presets" name="data[' + bookingItem + '][' + item_id + '][discount]">';
	temp += '		<option value="0.00">0.00</option>';
    temp += '		<option value="' + five + '">' + five + ' (5%)</option>';
	temp += '		<option value="' + ten + '">' + ten + ' (10%)</option>';
	temp += '	</select>';
	}*/
	
	temp += '<input type="text" class="discount_presets" name="data[' + bookingItem + '][' + item_id + '][discount]" value="0" />';
	
	temp += '	</td>';
	temp += '   <td class="center">';
	temp += '	<select class="quantity_presets" name="data[' + bookingItem + '][' + item_id + '][quantity]">';
	for(i=1; i<11; i++) {
		temp += '	<option value="' + i + '">' + i + '</option>';
	}
	temp += '	</select>';
	temp += '	</td>';    
	
	if(item_id == 1024) {
		temp += '	<td class="center"><input type="text" class="base_price" name="data[' + bookingItem + '][' + item_id + '][price]" value="' + price + '" />';
		temp += '	</td>';
	} else {
		temp += '	<td class="center"><input type="hidden" class="base_price" name="data[' + bookingItem + '][' + item_id + '][price]" value="' + price + '" />';
		temp += '	<input type="text" readonly="readonly" class="price_payable" value="0" /></td>';
	}
	
	temp += '	<td><input type="button" value=" X " class="delete_button" /></td>';
    temp += '</tr>';
	
	container.append(temp);
}

function initializeItems() {
	var url = G_URL + "iv_items/storeList?mode=0";		
	$.get(url,
		{},
		function(data){
			$('#items_container').html('<div class="center"><input type="button" value=" Close " class="items_button" /></div>' + data);
			$('#tabs').tabs();
			$('a[href|="#tabs-16"]').hide();
			$('#items_container').hide();
			
	});
}

function initializeFilter(selector) {
	$(selector).live('keyup', function(event) {
		//if esc is pressed or nothing is entered
		if (event.keyCode == 27 || $(this).val() == '') {
				//if esc is pressed we want to clear the value of search box
				$(this).val('');
				
				//we want each row to be visible because if nothing
				//is entered then all rows are matched.
		  $('tbody tr.item').removeClass('visible').show().addClass('visible');
		}
	
			//if there is text, lets filter
			else {
		  filter('tbody tr.item', $(this).val());
		}

		//reapply zebra rows
		//$('tr').filter(':even').addClass('even');
		zebraRows('tr.visible', 'even')
	});
}

function zebraRows(selector, className) {
	$(selector).removeClass(className);
	$(selector).filter(':even').addClass(className);
}
	
//filter results based on query
function filter(selector, query) {
	query	=	$.trim(query); //trim white space
	query = query.replace(/ /gi, '|'); //add OR for regex
	  
	$(selector).each(function() {
		($(this).text().search(new RegExp(query, "i")) < 0) ? $(this).hide().removeClass('visible') : $(this).show().addClass('visible');
	});
}		
//END filter and sort code

function computeValues() {
	var discount = 0;
	var quantity = 1;
	var price = 0;
	var payable = 0;
	var current_cost = 0;
	var current_tax = 0;
	var current_tax1 = 0;
	var current_balance = 0;
	var new_booking_cost = 0;
	
	$(".current_office_cost").each(function() {
		
		var tempval = 0;
		if(isNaN($(this).val())) tempval = 0;
		else tempval = parseFloat($(this).val());
		current_cost += tempval;
	});
	
	$(".item_detail").each(function(){
		discount = parseFloat($(this).find(".discount_presets").val());
		quantity = parseInt($(this).find(".quantity_presets").val());
		price = parseFloat($(this).find(".base_price").val());
		payable = Math.round(((price*quantity) - discount)*100)/100;
		if(isNaN(payable)) {					
			$(this).find(".price_payable").val(0);
			current_cost += 0;
		} else {			
			$(this).find(".price_payable").val(payable);
			current_cost += parseFloat(payable);
		}
	});
	
	$(".booking_item_detail").each(function(){
		discount = parseFloat($(this).find(".discount_presets").val());
		quantity = parseInt($(this).find(".quantity_presets").val());
		price = parseFloat($(this).find(".base_price").val());
		payable = Math.round(((price*quantity) - discount)*100)/100;		
		if(isNaN(payable)) {					
			$(this).find(".price_payable").val(0);
			new_booking_cost += 0;
		} else {			
			$(this).find(".price_payable").val(payable);
			new_booking_cost += parseFloat(payable);
		}
	});
	
	current_tax1 = current_cost*0.05;
	current_tax = current_tax1.toFixed(2);
	current_deposit = parseFloat($("#current_deposit").val());
	var id = $('#InvoiceOrderId').val();

	$.ajax({
		url: G_URL + "payments/updateDeposit",
		type: "post",
		data: {deposit:current_deposit, orderId:id},
		success: function(data)
		{

		}
	});
	
	$("#current_subtotal").val(current_cost);
	$("#current_tax").val(current_tax); 
	$("#current_total").val((+current_cost + +current_tax).toFixed(2));
	$("#current_balance").val((+current_cost + +current_tax + -current_deposit).toFixed(2));
	$("#saleAmount").val((+current_cost + +current_tax + -current_deposit).toFixed(2));
	var payableAmount = $("#paid_by_amount").val();
	if(payableAmount == 0)
	{
		$("#paid_by_amount").val(+current_cost + +current_tax + -current_deposit);	
	}
	
	
}

function checkInstalled() {
	var ok = true;
	$(".installed").each(function() {
		if($(this).val() != "1") {			
			ok = false;			
		} 
	});
	
	return ok;
}

function bookTimeslot() {
	var new_item=new Array();
	$("#Order2JobDate").val($("#job_date_ymd").val());
	$("#Order2JobTruck").val($("#job_truck").val());
	$("#Order2JobTimeBeg").val($("#job_time_beg").val());
	$("#Order2JobTimeEnd").val($("#job_time_end").val());
	$("#Order2JobTechnician1Id").val($("#tech1").val());
	$("#Order2JobTechnician2Id").val($("#tech2").val());
	$("#schedule_display").html($("#job_text_route").val());
}

function cancelTimeslot() {
		
}

function showPayments(){
  var id = $('#InvoiceOrderId').val();
	$.get(G_URL + "payments/techPaymentsForJob",
		{order_id:id}, 
		function(data){
			$('#payment_details').html(data);
		});
}

/*function SavePayment(){
	var userRole = $("#userRole").val();
	var imageName = $("#imageName").val();
    var id = $('#InvoiceOrderId').val();
	var method = $("#PaymentPaymentMethodId").val();	
	var element = $("#PaymentPaymentMethodId").find('option:selected'); 
	var option = element.attr("show-picture"); 
	var paymentOption = element.attr("show-payment"); 
	var MessageOption = element.attr("show-message"); 
	var amount = $("#paid_by_amount").val();	
	// var amount = $("#current_balance").val();	
	var orderId = $("#remOrderId").val();
	
	if (!method) {alert('A payment method should be selected!'); return;}
	if(amount == '' && paymentOption == 1 )
	{
		alert("Payment amount can't be blank"); return;
	}
	var formdata = new FormData();
	formdata.append("order_id",id);
	formdata.append("method",method);
	formdata.append("amount",amount);
	formdata.append("payment_type",1);
	formdata.append("show_message",MessageOption);
	if(!imageName)
	{
		if(option == 1)
	{
		var fileval = $('#FileinputImg')[0].files[0];
		if(fileval)
		{
			formdata.append('payment_image', fileval); 
		} else {
			alert('Payment Image is required!');
			return;
		}
		}else {
			var fileval = $('#FileinputImg')[0].files[0];
			if(fileval)
			{
				formdata.append('payment_image', fileval); 
			}
		}
	} 
	
	$.ajax({
		url: G_URL + "payments/savePayment",
		type: "post",
		data: formdata,
		contentType: false,
		processData: false,
		cache: false,
		success: function(data)
		{
			showPayments();
			$("#PaymentPaymentMethodId").removeAttr("readonly");
			$("#paid_by_amount").removeAttr("readonly");
			$("#auth_number").removeAttr("readonly");
			$("#save_payment").removeAttr("disabled");
			
			$("#PaymentPaymentMethodId").val(0);
			$("#paid_by_amount").val(0);
			 if(userRole == 6)
			 {
			 	location.reload(); 
			 } else {
			 	 showInvoiceReview();
			 }
		}
	});
}*/
function SavePayment(){
	var cell_phone = $("#cell_phone_no").val();
	var email = $("#receiptEmail").val();
	var jobNotes = $("#tech_job_notes").val();
	var sendReceipt = $("#paymnetReceipt").val();
	var sendReviewEmail = $("#notSendInvoice").val();
	var userRole = $("#userRole").val();
	var imageName = $("#imageName").val();
    var id = $('#InvoiceOrderId').val();
	var method = $("#PaymentPaymentMethodId").val();	
	var element = $("#PaymentPaymentMethodId").find('option:selected'); 
	var option = element.attr("show-picture"); 
	var paymentOption = element.attr("show-payment"); 
	var MessageOption = element.attr("show-message"); 
	var amount = $("#paid_by_amount").val();	
	// var amount = $("#current_balance").val();	
	var orderId = $("#remOrderId").val();
	var orderNum = $("#orderNum").val();
	var customerId = $("#customerId").val();
	if(method == 0){
        alert("Please select paid by method.");
        return false;
    }
	if (!method) {alert('A payment method should be selected!'); return;}
	if(amount == '' && paymentOption == 1 )
	{
		alert("Payment amount can't be blank"); return;
	}
	var formdata = new FormData($("#items_form")[0]);
	formdata.append("order_id",id);
	formdata.append("method",method);
	formdata.append("amount",amount);
	formdata.append("payment_type",1);
	formdata.append("show_message",MessageOption);
	formdata.append("sendReceipt",sendReceipt);
	formdata.append("email",email);
	formdata.append("orderNum",orderNum);
	formdata.append("customerId",customerId);
	formdata.append("sendReviewEmail",sendReviewEmail);
	formdata.append("jobNotes",jobNotes);
	if(!imageName)
	{
		if(option == 1)
		{
			var fileval = $('#FileinputImg')[0].files[0];
			if(fileval)
			{
				formdata.append('payment_image', fileval); 
			} else {
				if(sendReceipt == 1)
				{
					alert('Payment Image is required!');
					return;
				}
			}
		}else {
			var fileval = $('#FileinputImg')[0].files[0];
			if(fileval)
			{
				formdata.append('payment_image', fileval); 
			}
		}
	} 
	
	$.ajax({
		url: G_URL + "payments/savePayment",
		type: "post",
		data: formdata,
		contentType: false,
		processData: false,
		cache: false,
		success: function(data)
		{
			//showPayments();
			$("#PaymentPaymentMethodId").removeAttr("readonly");
			$("#paid_by_amount").removeAttr("readonly");
			$("#auth_number").removeAttr("readonly");
			$("#save_payment").removeAttr("disabled");
			
			// $("#PaymentPaymentMethodId").val(0);
			// $("#paid_by_amount").val(0);
			// location.reload(); 
			// if(sendReviewEmail == 0){
			// 	$.ajax({
			// 		url: G_URL + "orders/sendInvoiceWithReviewLink",
			// 		dataType: 'html',
			// 		type: 'POST',
			// 		cache: false,
			// 		data: {email:email, cus_id:customerId,cell_phone:cell_phone,order_id:orderId},
			// 		success: function(data) {
			// 			window.location.href = G_URL+"orders/invoiceTablet";
							
			// 			}			
			// 		});
			// }
			window.location.href = G_URL+"orders/invoiceTabletPrint?order_id="+id;		
		}
	});
}
function SaveTechPaymentImg()
{
	var id = $('#InvoiceOrderId').val();
	var formdata = new FormData();
	formdata.append("order_id",id);
	var fileval = $('#FileinputImg')[0].files[0];
	if(fileval)
	{
		formdata.append('payment_image', fileval); 
	} else {
		alert('Payment Image is required!');
	}
	$.ajax({
		url: G_URL + "payments/savePaymentImg",
		type: "post",
		data: formdata,
		contentType: false,
		processData: false,
		cache: false,
		success: function(data)
		{
			// showPayments();
		}
	});
}
function ErasePayment(payment_id){
  $.post(G_URL +"payments/deletePayment",{payment_id:payment_id},function(data){
    showPayments();
  });
}

function hideInvoiceReview(){
	// document.getElementById("myModalNew").style.display = "none";
	document.getElementById("reminderEmail").style.display = "none";
	window.location.reload();
}

function showInvoiceReview(){
	document.getElementById("reminderEmail").style.display = "block";
	// document.getElementById("reminderEmail").style.display = "block";
}

function sortTable(n) {
  var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
  table = document.getElementById("installation_item_table");
  switching = true;
  //Set the sorting direction to ascending:
  dir = "asc"; 
  /*Make a loop that will continue until
  no switching has been done:*/
  while (switching) {
    //start by saying: no switching is done:
    switching = false;
    rows = table.rows;
    /*Loop through all table rows (except the
    first, which contains table headers):*/
    for (i = 1; i < (rows.length - 1); i++) {
      //start by saying there should be no switching:
      shouldSwitch = false;
      /*Get the two elements you want to compare,
      one from current row and one from the next:*/
      x = rows[i].getElementsByTagName("TD")[n];
      y = rows[i + 1].getElementsByTagName("TD")[n];
      /*check if the two rows should switch place,
      based on the direction, asc or desc:*/
      if (dir == "asc") {
        if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
          //if so, mark as a switch and break the loop:
          shouldSwitch= true;
          break;
        }
      } else if (dir == "desc") {
        if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
          //if so, mark as a switch and break the loop:
          shouldSwitch = true;
          break;
        }
      }
    }
    if (shouldSwitch) {
      /*If a switch has been marked, make the switch
      and mark that a switch has been done:*/
      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
      switching = true;
      //Each time a switch is done, increase this count by 1:
      switchcount ++;      
    } else {
      /*If no switching has been done AND the direction is "asc",
      set the direction to "desc" and run the while loop again.*/
      if (switchcount == 0 && dir == "asc") {
        dir = "desc";
        switching = true;
      }
    }
  }
}
