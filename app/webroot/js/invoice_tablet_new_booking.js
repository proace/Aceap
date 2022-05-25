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
		if($("#Order2JobDate").val() == "") {					
			alert("Please set the schedule");
		} else {
			$("#items_form").submit();
		}
	});
	
	$("#BookingOrderTypeId").change(function(){
		var url = G_URL + "orders/invoiceTabletQuestions";
		var order_type_id = $(this).val();		
		$.post(url,
			{
				order_type_id:order_type_id
			},
			function(data){
				$("#questions_div").html(data);				
		});
	});
	
	//timeslot feature
	
	
	$("#load_timeslots").click(function(){
		var url = G_URL + "orders/invoiceTabletTimeSlots";
		var city = $('#CustomerCity').val();
		console.log(city);
		
		var job_type_id = $('#job_type_id').val();
		if(city != "") {
			city = city.replace(' ', '_');
			var city_id = $('#' + city).val()
			$(this).hide();
			$("#loading_timeslots").show();		
			$.post(url,
				{
					city_id:city_id,
					job_type_id:job_type_id
				},
				function(data){
					$("#timeslot_div").html(data);
					$('#timeslot_tabs').tabs();
					$("#loading_timeslots").hide();
					$("#close_timeslots").show();
			});			
		} else {
			alert("City must not be blank");	
		}		
		
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
		
		var city = $('#CustomerCity').val();
		if(city != "") {
			city = city.replace(' ', '_');
			var city_id = $('#' + city).val()					
		} else {
			
			alert("City must not be blank");	
			return;
		}
		
		
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
		$(".next_submit").attr("disabled", "disabled");
		$("#newdate_form").submit();
	});
	
	$("#slot_no").live("click", function(){
		$("#slot_confirmations").html("");
	});
	
	$("#current_deposit").change(function(){
		computeValues();	
	});
	
	$("#skip_to_questions").click(function(){
		$(".next_submit").attr("disabled", "disabled");
		window.open(G_URL + "orders/invoiceTabletQuestions?order_id=" + $("#InvoiceOrderId").val(), "_self");
	});
});

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
    temp += '	<td class="left">' + name;
	temp += '	<input type="hidden" value="' + name + '" name="data[' + bookingItem + '][' + item_id + '][name]">';
	temp += '	<input type="hidden" value="' + price_purchase + '" name="data[' + bookingItem + '][' + item_id + '][price_purchase]">';
	temp += '	<input type="hidden" value="' + item_category_id + '" name="data[' + bookingItem + '][' + item_id + '][item_category_id]">';
	temp += '	<input type="hidden" value="0" name="data[' + bookingItem + '][' + item_id + '][addition]">';
	temp += '	</td>';
//    temp += '   <td class="center">';
//    temp += '		<select class="installed" name="data[' + bookingItem + '][' + item_id + '][installed]">';
//    temp += '			<option value="0"></option>';
//    temp += '			<option value="2">No</option>';
//	temp += '			<option value="1">Yes</option>';
//    temp += '		</select>';
//    temp += '	</td>';
	temp += '   <td class="center">';
	
	//if appliance
	if(parseInt(item_category_id) == 2 || parseInt(item_category_id) == 3 || parseInt(item_category_id) == 4 || parseInt(item_category_id) == 5 || parseInt(item_category_id) == 6 || parseInt(item_category_id) == 10) {
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
	}
	
	temp += '	</td>';
	temp += '   <td class="center">';
	temp += '	<select class="quantity_presets" name="data[' + bookingItem + '][' + item_id + '][quantity]">';
	for(i=1; i<11; i++) {
		temp += '	<option value="' + i + '">' + i + '</option>';
	}
	temp += '	</select>';
	temp += '	</td>';    
    temp += '	<td class="center"><input type="hidden" class="base_price" name="data[' + bookingItem + '][' + item_id + '][price]" value="' + price + '" />';
	temp += '	<input type="text" readonly="readonly" class="price_payable" value="0" /></td>';
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
	var current_balance = 0;
	var new_booking_cost = 0;
	
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
	
	current_tax = current_cost*0.12;
	current_deposit = parseFloat($("#current_deposit").val());
	
	$("#current_subtotal").val(current_cost);
	$("#current_tax").val(current_tax); 
	$("#current_total").val(current_cost + current_tax);
	$("#current_balance").val(current_cost + current_tax - current_deposit);
	
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