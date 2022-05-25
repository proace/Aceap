var G_URL;
var G_CONTAINER;
var G_NEW = false;

$(function() {
	var test_server = "http://acesys.ace1.ca/acesys/acesys-2.0/index.php/";
	var live_server = "/acesys/index.php/";
	
	var l = $(location).attr('href'); 
	$("#ajax_item_results_0").hide();

	$(".add_selected_items").live("click", function(){
		var Id = '';
		table = $('#ajax_item_results_0');
		table.hide();
		G_CONTAINER = $("#item_details");
		G_NEW = false;
		$('#ajax_item_name').val('');
		$(".delete_items:checkbox:checked").each(function(){
			var cur = $(this ).parent().parent();
			 var itemId = $('.item_id_0',cur).text();
			 var name = $('.name_0',cur).text();
			 var price = $('.price_0',cur).text();
			 var sku = $('.sku_0',cur).text();
			 var purchasePrice = $('.purchase_price_0',cur).text();
			 var catId = $('.category_id_0',cur).text();
			 var subCatId = $('.sub_category_id_0',cur).text();
			 var markupPercent = $('.markup_percent_0',cur).text();
			 var techPercent = $('.tech_percent_0',cur).text();
				 // addItem(dat.children(".item_id").text(), dat.children(".name").text(), dat.children(".price").text(), dat.children(".sku").text(), "", "", "", "", dat.children(".purchase_price").text(),dat.children(".category_id").text());
				 addItem(G_CONTAINER,itemId, name,catId,price, purchasePrice,G_NEW,markupPercent,techPercent);
				 computeValues();
		});
	});  

	$("#ajax_item_name").live("keyup",ajax);
	$(".items_button_close").live('click', function(){
		$('#items_container').hide();
	});
	$(".disply_preview").live("change",function(e){
	    var img_ct = $(this).attr("data-ct").trim();
	    var cur = $(this);
	   upload_photo(cur[0],img_ct);
	});
	 $(function () {
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
	        $('.invoice-img-enlarge').dialog('open');
	    });
    });

	if(l.indexOf("acesys-2.0") != -1) G_URL = test_server;
	else G_URL = live_server;
	
	$("#job_list").hide();	
	
	$("#jobs_link").click(function(){
		$("#job_list").toggle();	
	});
	
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
	
	$("#add_supplier_item").click(function(){
		addSupplierItem($("#item_details"));
	});	
	
	$(".items_button").live("click", function(){
		
		//$("#items_container").toggle();
		/*if($(this).hasClass("service_tab")) $('#tabs').tabs("option", "selected", 0);
		if($(this).hasClass("furnace_tab")) $('#tabs').tabs("option", "selected", 1);
		if($(this).hasClass("boiler_tab")) $('#tabs').tabs("option", "selected", 2);
		if($(this).hasClass("hotwater_tab")) $('#tabs').tabs("option", "selected", 3);
		if($(this).hasClass("split_tab")) $('#tabs').tabs("option", "selected", 4);
		if($(this).hasClass("fireplace_tab")) $('#tabs').tabs("option", "selected", 5);
		if($(this).hasClass("accessories_tab")) $('#tabs').tabs("option", "selected", 6);
		if($(this).hasClass("parts_tab")) $('#tabs').tabs("option", "selected", 7);
		if($(this).hasClass("permit_tab")) $('#tabs').tabs("option", "selected", 8);
		if($(this).hasClass("heatpumps_tab")) $('#tabs').tabs("option", "selected", 9);
		*/
		
		// if($(this).hasClass("tab1")) $('#tabs').tabs("option", "selected", 0);
		// if($(this).hasClass("tab2")) $('#tabs').tabs("option", "selected", 1);
		// if($(this).hasClass("tab3")) $('#tabs').tabs("option", "selected", 2);
		// if($(this).hasClass("tab4")) $('#tabs').tabs("option", "selected", 3);
		// if($(this).hasClass("tab5")) $('#tabs').tabs("option", "selected", 4);
		// if($(this).hasClass("tab6")) $('#tabs').tabs("option", "selected", 5);
		// if($(this).hasClass("tab7")) $('#tabs').tabs("option", "selected", 6);
		// if($(this).hasClass("tab8")) $('#tabs').tabs("option", "selected", 7);
		// if($(this).hasClass("tab9")) $('#tabs').tabs("option", "selected", 8);
		// if($(this).hasClass("tab10")) $('#tabs').tabs("option", "selected", 9);
		
		 G_CONTAINER = $("#item_details");
		 G_NEW = false;
		var url = G_URL + "iv_items/storeTree?mode=0";
		// var url = G_URL + "iv_items/storeList?mode=0";		
		$.get(url,
			{},
			function(data){
				$('#items_container').show();
				$('#items_container').html('<div class="center"><input type="button" value=" Close " class="items_button_close" /></div>'+data);
				$('#tabs').tabs();
				$(".all_node").click();
				//$('a[href|="#tabs-16"]').hide();	
		});
		
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
		
	$(".customName").live("change", function(){
		var name = $(this).val();
		$(this).parent().find(".customPartName").val(name);
	});

	$(".customService").live("change", function(){
		var name = $(this).val();
		$(this).parent().find(".customServiceName").val(name);
	});
	computeValues();
	
	// $(".discount_presets").live("change", function(){
	// 	computeValues();	
	// });

	// $(".org_price").live("change", function(){
	// 	price = $(this).val();
	// 	$(this).parent().parent().find(".base_price").val(price);
	// 	computeValues();	
	// });
	
	// $(".purch_price").live("change", function(){
	// 	var catId = $(this).parent().parent().find('.catId').val();
	// 	var qty = $(this).parent().parent().find('.quantity_presets').val();
	// 	purch = $(this).val();
	// 	if(catId == 8)
	// 	{
	// 		var percentVal = $("#tech_percentage").val();
 //            var percentType = $("#tech_percentage_type").val();
 //            var markupPercentVal = $("#markup_percentage").val();
 //            var markupPercentType = $("#markup_percentage_type").val();
 //           	var newMarkupAmount = 0;
 //           	var newPrchAmount = 0;
 //            if(percentType == 1)
 //            {
 //                var newPrchAmount = (parseFloat(purch) * parseInt(percentVal))/ 100;
 //            } else if(percentType == 2) {
 //                var newPrchAmount = parseFloat(percentVal);
 //            }
 //            if(markupPercentType == 1)
 //            {
 //            	var newMarkupAmount = newPrchAmount + (parseFloat(purch) * parseInt(markupPercentVal))/ 100;
 //            } else if(markupPercentType == 2)
 //            {
 //            	var newMarkupAmount = newPrchAmount + parseFloat(markupPercentVal);
 //            }
 //            var totalMarkupAmount = (parseFloat(purch) + parseFloat(newMarkupAmount)).toFixed(2);
	// 	}
		
	// 	$(this).parent().parent().find(".base_price").val(totalMarkupAmount);
	// 	$(this).parent().parent().find(".tech_purchase_price").val(newPrchAmount * qty);
	// 	$(this).parent().parent().find(".org_price").val(totalMarkupAmount);
	// 	computeValues();	
	// });
	// $(".quantity_presets").live("change", function(){
	// 	var catId = $(this).parent().parent().find('.catId').val();
	// 	var qty = $(this).parent().parent().find('.quantity_presets').val();
	// 	purch = $(this).val();
	// 	if(catId == 8)
	// 	{
	// 		var percentVal = $("#tech_percentage").val();
 //            var percentType = $("#tech_percentage_type").val();
 //            var markupPercentVal = $("#markup_percentage").val();
 //            var markupPercentType = $("#markup_percentage_type").val();
 //           	var newMarkupAmount = 0;
 //           	var newPrchAmount = 0;
 //            if(percentType == 1)
 //            {
 //                var newPrchAmount = (parseFloat(purch) * parseInt(percentVal))/ 100;
 //            } else if(percentType == 2) {
 //                var newPrchAmount = parseFloat(percentVal);
 //            }
 //            if(markupPercentType == 1)
 //            {
 //            	var newMarkupAmount = newPrchAmount + (parseFloat(purch) * parseInt(markupPercentVal))/ 100;
 //            } else if(markupPercentType == 2)
 //            {
 //            	var newMarkupAmount = newPrchAmount + parseFloat(markupPercentVal);
 //            }
 //            var totalMarkupAmount = (parseFloat(purch) + parseFloat(newMarkupAmount)).toFixed(2);
	// 	}
		
	// 	$(this).parent().parent().find(".base_price").val(totalMarkupAmount);
	// 	$(this).parent().parent().find(".tech_purchase_price").val(newPrchAmount * qty);
	// 	$(this).parent().parent().find(".org_price").val(totalMarkupAmount);
		
	// 	computeValues();	
	// });
	
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
	
	$(".multipleItems").live("click", function(){
		var items= [];	
		var show_purchase = $("#show-purchase").val();
		$('.addItems:checkbox:checked').each(function () {
			var cur = $(this).parent().parent();
			var itema = {}; 
       		itema.item_id 		 		= cur.children(".item_id").val();
			itema.item_name 	 		= cur.children(".item_name").val();
			itema.item_model 	 		= cur.children(".item_model").val();
			itema.item_regular_price 	= cur.children(".item_regular_price").val();
			itema.item_selling_price 	= cur.children(".item_selling_price").val();
			itema.item_supplier_price 	= cur.children(".item_supplier_price").val();
			itema.item_efficiency 	 	= cur.children(".item_efficiency").val();
			itema.item_category_id 	 	= cur.children(".item_category_id").val();
			itema.item_brand_id 	 	= cur.children(".item_brand_id").val();
			itema.item_supplier_id 	 	= cur.children(".item_supplier_id").val();
			itema.item_description1 	= cur.children(".item_description1").val();
			itema.item_description2 	= cur.children(".item_description2").val();
			itema.item_markup_percent 	= cur.children(".item_markup_percent").val();
			itema.item_tech_percent 	= cur.children(".item_tech_percent").val();
			itema.item_mode 	 		= cur.children(".item_mode").val();
			items.push(itema);
			$('input:checkbox').removeAttr('checked');
		});
		
		$.each(items, function( key, value ) {
  			addItem(G_CONTAINER, value.item_id, value.item_name, value.item_category_id, value.item_selling_price ,value.item_supplier_price, G_NEW,value.item_markup_percent,value.item_tech_percent);
		});
		
		$("#items_container").hide();
		computeValues();
	});
	
	// $(".tabs tr.item").live("click", function(){		
	// 	$(this).children(".item_id").val();
	// 	$(this).children(".item_name").val();
	// 	$(this).children(".item_model").val();
	// 	$(this).children(".item_regular_price").val();
	// 	$(this).children(".item_selling_price").val();
	// 	$(this).children(".item_supplier_price").val();
	// 	$(this).children(".item_efficiency").val();
	// 	$(this).children(".item_category_id").val();
	// 	$(this).children(".item_brand_id").val();
	// 	$(this).children(".item_supplier_id").val();
	// 	$(this).children(".item_description1").val();
	// 	$(this).children(".item_description2").val();
	// 	$(this).children(".item_mode").val();
		
	// 	addItem(G_CONTAINER, $(this).children(".item_id").val(), $(this).children(".item_name").val(), $(this).children(".item_category_id").val(), $(this).children(".item_selling_price").val(),$(this).children(".item_supplier_price").val(), G_NEW);
		
	// 	$("#items_container").hide();
	// 	computeValues();
	// });
	
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
				var submit = true ; 
				$(".item_detail").each(function(){
					catId = $(this).children().find(".catId").val();
					price = parseFloat($(this).find(".purch_price").val());
					
					if(catId == 8 && price <= 0 )
					{
						alert("Please enter purchase price for part.");
						$(this).find(".purch_price").css("background-color", "red");
						submit = false;
						return false;
					} 
				});
				if(submit)
					{
						$("#items_form").submit();
					}
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
	
	$("#save_payment").click(function(){
		SavePayment();	
	});

	showPayments();

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

function ajax(event) {
	var val = $('#ajax_item_name').val().trim();
	// console.log("Ajaxing",val);
	var supp = $('#supplier').val();
	// Return all items if:
	// 1) allItems is not passed as an argument.
	// 2) allItems is not false
	var src = event.currentTarget.id;
	
	if (val != '' && val != null && val.length >= 3){
		if (src == "ajax_item_name") {
			// Return only active items.
			$.get(
				"../inventories/searchItems",
				// {query:val,supplier:supp,active:true},
				{query:val,classId:0},
				ajax_finished,
				"json");
		}
	} else{
		$('#ajax_item_results_0').hide();
	}
}

function ajax_failed( why ) {
	template = $('#ajax_item_result_template');
	table = $('#ajax_item_results tbody');
	table.empty();
	$('#ajax_item_results thead').hide();

	if (why=="no supplier") {
		table.html("Please choose a supplier."); }
	if (why=="no results") {
		table.html("No items match your search. <a href='javascript:add_unknown_item();'>Add it?</a>"); }
	table.show();
 }
function ajax_finished( data ) {
	accessData = data;
	if (data.length===0) {
		ajax_failed("no results");
		return false;
	}
	template = $('#ajax_item_result_template');
	table = $('#ajax_item_results_0 tbody');
	table.empty();
	table.append(data);
	table.append('<tr><td colspan="11"></td><td><input type="button" class="add_selected_items" name="add_item" value="Add"></td></tr>');
	table.parent().show();
}
//Loki: calculate the amount of installation items.
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
function upload_photo(cur,i) {	
	//var file = $('input[id="sortpicture'+i+'"]')[0];
	if (cur.files && cur.files[0]) {
    var reader = new FileReader();
    reader.onload = function (e) {
      $('.pre-image_'+i+'')
        .attr('src', e.target.result)
        .width(86)
        .height(50);
        $('.pre-image_'+i+'').css("display","block");
    };
    reader.readAsDataURL(cur.files[0]);
  }
	// formData.append('image', $('input[id="sortpicture'+i+'"]')[0].files[0]);
	// $('#uploading_' + i).show();
}

function addItem(container, item_id, name, item_category_id, price, price_purchase, isNew,markupPercent=52,techPercent=30) {
	var temp = '';
	var five = Math.round((parseFloat(price)*0.05)*10)/10;
	var ten = Math.round((parseFloat(price)*0.10)*10)/10;
	var hundred = 100;
	var fifty = 50;
	var bookingItem = "BookingItem";
	if(isNew) bookingItem = "BookingItem2";
	var bookingDetail = "item_detail";
	if(isNew) bookingDetail = "booking_item_detail";
	var index = 0;
	var techPurch = 0;
	$("." + bookingDetail).each(function(){
		if(parseInt($(this).attr("item_index")) > index) index = parseInt($(this).attr("item_index"));
	});
	index++;
	if(item_category_id == 8)
	{
		if(price_purchase > 0)
		{
			// var percentVal = $("#tech_percentage").val();
	  //       var percentType = $("#tech_percentage_type").val();
	  //       var markupPercentVal = $("#markup_percentage").val();
	  //       var markupPercentType = $("#markup_percentage_type").val();
	  		var percentVal = techPercent;
	        var percentType = 1;
	        var markupPercentVal = markupPercent;
	        var markupPercentType = 1;
	       	var newMarkupAmount = 0;
	       	var newPrchAmount = 0;
	        if(percentType == 1)
	        {
	        	if((percentVal > 0) && (percentVal != '')){
	            	var newPrchAmount = (parseFloat(price_purchase) * parseInt(percentVal))/ 100;
	        	}
	        } else if(percentType == 2) {
	        	if((percentVal > 0) && (percentVal != '')){
	            	var newPrchAmount = parseFloat(percentVal);
	        	}
	        }
	        if(markupPercentType == 1)
	        {
	        	if((markupPercentVal > 0) && (markupPercentVal != '')){
	        		var newMarkupAmount = (parseFloat(price_purchase) * parseInt(markupPercentVal))/ 100;
	        	}
	        } else if(markupPercentType == 2)
	        {
	        	if((markupPercentVal > 0) && (markupPercentVal != '')){
	        		var newMarkupAmount =  parseFloat(markupPercentVal);
	        	}
	        }
	        var totalMarkupAmount = (parseFloat(price_purchase) + parseFloat(newMarkupAmount)+parseFloat(newPrchAmount)).toFixed(2);

	        techPurch = newPrchAmount;
	        price = totalMarkupAmount;
		}
	}

	temp += '<tr class="' + bookingDetail + '" item_index="'+index+'">';

	if(name == "-custom part-")
	{
		temp += '	<td class="left">' + '<input type="text" style="width: 145px;text-align: left;" class="customName" value="'+name+'">' ;
		temp += '	<input type="hidden" class="customPartName" value="' + name + '" name="data[' + bookingItem + '][' + index + '][name]">';
	} else if(name == "-custom service-"){
		temp += '	<td class="left">' + '<input type="text" style="width: 145px;text-align: left;" class="customService" value="'+name+'">' ;
		temp += '	<input type="hidden" class="customServiceName" value="' + name + '" name="data[' + bookingItem + '][' + index + '][name]">';
	}
	else {
		temp += '	<td class="left">' + name;
		temp += '	<input type="hidden" value="' + name + '" name="data[' + bookingItem + '][' + index + '][name]">';
	}

	temp += '	<input type="hidden" class="item_tech_percent" value="'+techPercent+'" name="data[' + bookingItem + '][' + index + '][item_tech_percent]">';
	temp += '	<input type="hidden" class="item_tech_percent_type" value="1" name="data[' + bookingItem + '][' + index + '][item_tech_percent_type]">';
	temp += '	<input type="hidden" class="item_markup_percent" value="'+markupPercent+'" name="data[' + bookingItem + '][' + index + '][item_markup_percent]">';
	temp += '	<input type="hidden" class="item_markup_percent_type" value="1" name="data[' + bookingItem + '][' + index + '][item_markup_percent_type]">';
	
	temp += '	<input type="hidden" value="'+item_id+'" name="data[' + bookingItem + '][' + index + '][item_id]">';
	temp += '	<input type="hidden" value="' + price_purchase + '" name="data[' + bookingItem + '][' + index + '][price_purchase]">';
	temp += '	<input type="hidden" class="catId" value="' + item_category_id + '" name="data[' + bookingItem + '][' + index + '][item_category_id]">';
	temp += '	<input type="hidden" value="0" name="data[' + bookingItem + '][' + index + '][addition]">';
	temp += '	</td>';
    temp += '   <td class="center">';
    temp += '		<select class="installed" name="data[' + bookingItem + '][' + index + '][installed]">';    
    temp += '			<option value="1">Yes</option>';
	temp += '			<option value="2">No</option>';
    temp += '		</select>';
	//temp += '	<input type="hidden" name="data[' + bookingItem + '][' + item_id + '][installed]" value="1" />Yes';
    temp += '	</td>';
	temp += '   <td class="center">';
	
	
	temp += '<input type="text" class="discount_presets" onchange="computeValues();" name="data[' + bookingItem + '][' + index + '][discount]" value="0" />';
	
	temp += '	</td>';
	temp += '   <td class="center">';
	temp += '<input type="text" class="quantity_presets" onchange="computeValues();" name="data[' + bookingItem + '][' + index + '][quantity]" value="1" />';
	temp += '	</td>';    

	temp += ' <td><input type="text" class="org_price" onchange="computeValues();" value="'+ price +'" /></td>';
	if(item_category_id == 8)
	{
		temp += ' <td class="center"><input type="text" onchange="computeValues();" class="purch_price" name="data[' + bookingItem + '][' + index + '][price_purchase]" value="' + price_purchase + '" />';
		temp += ' <td class="center"><input type="text" class="tech_purchase_price" name="data[' + bookingItem + '][' + index + '][tech_purchase_price]" value="'+ techPurch +'" />';
	} else {
		temp += '<td></td>';
		temp += '<td></td>';
	}
	temp += ' <td class="center"><input type="hidden" class="base_price" name="data[' + bookingItem + '][' + index + '][price]" value="' + price + '" />';		
	temp += ' <input type="text" class="price_payable" value="'+price+'" /></td>';
	
	temp += '	<td><input type="button" value=" X " class="delete_button" /></td>';
    temp += '</tr>';
	
	container.append(temp);
}
// function addItem(container, item_id, name, item_category_id, price, price_purchase, isNew) {
// 	var temp = '';
// 	var five = Math.round((parseFloat(price)*0.05)*10)/10;
// 	var ten = Math.round((parseFloat(price)*0.10)*10)/10;
// 	var hundred = 100;
// 	var fifty = 50;
// 	var bookingItem = "BookingItem";
// 	if(isNew) bookingItem = "BookingItem2";
// 	var bookingDetail = "item_detail";
// 	if(isNew) bookingDetail = "booking_item_detail";
// 	var index = 0;
// 	$("." + bookingDetail).each(function(){
// 		if(parseInt($(this).attr("item_index")) > index) index = parseInt($(this).attr("item_index"));
// 	});
// 	index++;
		
// 	temp += '<tr class="' + bookingDetail + '" item_index="'+index+'">';
// 	// if(item_id == 1024) {		
// 	// 	temp += '	<td class="left">';
// 	// 	temp += '	<input type="text" style="width:200px;text-align:left" value="-custom part-" name="data[' + bookingItem + '][' + index + '][name]">';	
// 	// } else {
// 	// 	if(name == "-custom part-")
// 	// 	{
// 	// 		temp += '	<td class="left">' + '<input type="text" style="width: 145px;text-align: left;" class="customName" value="'+name+'">' ;
// 	// 		temp += '	<input type="hidden" class="customPartName" value="' + name + '" name="data[' + bookingItem + '][' + index + '][name]">';
// 	// 	} else {
// 	// 		temp += '	<td class="left">' + name;
// 	// 		temp += '	<input type="hidden" value="' + name + '" name="data[' + bookingItem + '][' + index + '][name]">';
// 	// 	}
		
// 	// }
// 	console.log("name1=",name);
// 	if(name == "-custom part-")
// 	{
// 		temp += '	<td class="left">' + '<input type="text" style="width: 145px;text-align: left;" class="customName" value="'+name+'">' ;
// 		temp += '	<input type="hidden" class="customPartName" value="' + name + '" name="data[' + bookingItem + '][' + index + '][name]">';
// 	} else {
// 		temp += '	<td class="left">' + name;
// 		temp += '	<input type="hidden" value="' + name + '" name="data[' + bookingItem + '][' + index + '][name]">';
// 	}

// 	temp += '	<input type="hidden" value="'+item_id+'" name="data[' + bookingItem + '][' + index + '][item_id]">';
// 	temp += '	<input type="hidden" value="' + price_purchase + '" name="data[' + bookingItem + '][' + index + '][price_purchase]">';
// 	temp += '	<input type="hidden" value="' + item_category_id + '" name="data[' + bookingItem + '][' + index + '][item_category_id]">';
// 	temp += '	<input type="hidden" value="0" name="data[' + bookingItem + '][' + index + '][addition]">';
// 	temp += '	</td>';
//     temp += '   <td class="center">';
//     temp += '		<select class="installed" name="data[' + bookingItem + '][' + index + '][installed]">';    
//     temp += '			<option value="1">Yes</option>';
// 	temp += '			<option value="2">No</option>';
//     temp += '		</select>';
// 	//temp += '	<input type="hidden" name="data[' + bookingItem + '][' + item_id + '][installed]" value="1" />Yes';
//     temp += '	</td>';
// 	temp += '   <td class="center">';
	
// 	//if appliance
// 	/*if(parseInt(item_category_id) == 2 || parseInt(item_category_id) == 3 || parseInt(item_category_id) == 4 || parseInt(item_category_id) == 5 || parseInt(item_category_id) == 6 || parseInt(item_category_id) == 10) {
// 	temp += '	<select class="discount_presets" name="data[' + bookingItem + '][' + item_id + '][discount]">';
// 	temp += '		<option value="0.00">0.00</option>';
//     temp += '		<option value="' + fifty + '">' + fifty + '</option>';
// 	temp += '		<option value="' + hundred + '">' + hundred + '</option>';
// 	temp += '	</select>';
// 	} else { //not appliance
// 	temp += '	<select class="discount_presets" name="data[' + bookingItem + '][' + item_id + '][discount]">';
// 	temp += '		<option value="0.00">0.00</option>';
//     temp += '		<option value="' + five + '">' + five + ' (5%)</option>';
// 	temp += '		<option value="' + ten + '">' + ten + ' (10%)</option>';
// 	temp += '	</select>';
// 	}*/
	
// 	temp += '<input type="text" class="discount_presets" name="data[' + bookingItem + '][' + index + '][discount]" value="0" />';
	
// 	temp += '	</td>';
// 	temp += '   <td class="center">';
// 	temp += '<input type="text" class="quantity_presets" name="data[' + bookingItem + '][' + index + '][quantity]" value="0" />';
// 	temp += '	</select>';
// 	temp += '	</td>';    
	
// 	/*if(item_id == 1024) {
// 		temp += '	<td class="center"><input type="text" class="base_price" name="data[' + bookingItem + '][' + index + '][price]" value="' + price + '" />';
// 		temp += '	</td>';
// 	} else {

// 		temp += '	<td class="center"><input type="hidden" class="base_price" name="data[' + bookingItem + '][' + index + '][price]" value="' + price + '" />';
// 		// temp += '	<input type="text" readonly="readonly" class="price_payable" value="0" /></td>';
// 		temp += '	<input type="text" class="price_payable" value="0" /></td>';
// 	}*/

// 		temp += ' <td><input type="text" class="org_price" value="'+ price +'" /></td>';
// 		temp += ' <td class="center"><input type="hidden" class="base_price" name="data[' + bookingItem + '][' + index + '][price]" value="' + price + '" />';		
// 		temp += ' <input type="text" class="price_payable" value="0" /></td>';

// 	// if(item_id == 1024) {
// 	// 	temp += '	<td class="center"><input type="text" class="base_price" name="data[' + bookingItem + '][' + index + '][price]" value="' + price + '" />';
// 	// 	temp += '	</td>';
// 	// } else {
// 	// 	temp += ' <td><input type="text" class="org_price" value="'+ price +'" /></td>';
// 	// 	temp += ' <td class="center"><input type="hidden" class="base_price" name="data[' + bookingItem + '][' + index + '][price]" value="' + price + '" />';		
// 	// 	temp += '	<input type="text" class="price_payable" value="0" /></td>';
// 	// }
	
// 	temp += '	<td><input type="button" value=" X " class="delete_button" /></td>';
//     temp += '</tr>';
	
// 	container.append(temp);
// }

function addSupplierItem(container) {
	var item_id = 1218;
	var temp = '';
	var five = 0;
	var ten = 0;
	var hundred = 100;
	var fifty = 50;
	var bookingItem = "BookingItem";	
	var bookingDetail = "item_detail";
	var index = 0;
	$("." + bookingDetail).each(function(){
		if(parseInt($(this).attr("item_index")) > index) index = parseInt($(this).attr("item_index"));
	});	
	index++;
	temp += '<tr class="' + bookingDetail + ' supplier_item" item_index="'+index+'">';
    		
	temp += '	<td class="left" style="padding:5px">';

	temp += '	<div style="background-color:#FFFFFF;padding:5px;border-radius:5px;width:210px;">';
	temp += '<img id="pre-image_photo1" class="pre-image_'+index+' invoice-openImg" src="#" alt="your image" />';

	temp +=	'<div class="cls-acecare-td-adjust"><label for="Fileinput1" >Upload Invoice</label>';
	temp +=	'<input type="file" name="uploadInvoice1[' + index + ']" id="Fileinput1" class="disply_preview" data-ct="'+index+'"></div>';
	temp += '		<div style="width:200px;text-align:left;font-size:8px;">Part Name</div>';
	temp += '		<div><input type="text" style="width:200px;text-align:left" value="" name="data[' + bookingItem + '][' + index + '][name]"></div>';	
	temp += '		<div style="width:200px;text-align:left;font-size:8px;">Model</div>';
	temp += '		<div><input type="text" style="width:200px;text-align:left" value="" name="data[' + bookingItem + '][' + index + '][part_model]"></div>';	
	temp += '		<div style="width:200px;text-align:left;font-size:8px;">Brand</div>';
	temp += '		<div><input type="text" style="width:200px;text-align:left" value="" name="data[' + bookingItem + '][' + index + '][part_brand]"></div>';
	temp += '		<div style="width:200px;text-align:left;font-size:8px;">Supplier</div>';
	temp += '		<div><input type="text" style="width:200px;text-align:left" value="" name="data[' + bookingItem + '][' + index + '][part_supplier]"></div>';	
	temp += '	</div>';
	
	temp += '	<input type="hidden" value="'+item_id+'" name="data[' + bookingItem + '][' + index + '][item_id]">';
	temp += '	<input type="hidden" value="0" name="data[' + bookingItem + '][' + index + '][price_purchase]">';
	temp += '	<input type="hidden" value="1" name="data[' + bookingItem + '][' + index + '][item_category_id]">';
	temp += '	<input type="hidden" value="0" name="data[' + bookingItem + '][' + index + '][addition]">';
	temp += '	</td>';
    temp += '   <td class="center">';    
	temp += '	<input type="hidden" name="data[' + bookingItem + '][' + index + '][installed]" value="2" />No';
    temp += '	</td>';
	temp += '   <td class="center">';
	
	temp += '<input type="text" class="discount_presets" name="data[' + bookingItem + '][' + index + '][discount]" value="0" />';
	
	temp += '	</td>';
	temp += '   <td class="center">';
	temp += '	<select class="quantity_presets" name="data[' + bookingItem + '][' + index + '][quantity]">';
	for(i=1; i<11; i++) {
		temp += '	<option value="' + i + '">' + i + '</option>';
	}
	temp += '	</select>';
	temp += '	</td>';    
	
	temp += '<td><input type="text" class="org_price" value="0" /></td>';
	temp += '	<td class="center"><input type="hidden" class="base_price" name="data[' + bookingItem + '][' + index + '][price]" value="0" />';
	// temp += '	<input type="text" readonly="readonly" class="price_payable" value="0" /></td>';
	
	temp += '	<input type="text" class="price_payable" value="0" /></td>';
	
	
	temp += '	<td><input type="button" value=" X " class="delete_button" /></td>';
    temp += '</tr>';
	
	container.append(temp);
}

function initializeItems() {
	// var url = G_URL + "iv_items/storeList?mode=0&job_type=" + $("#job_type").val();		
	// var url = G_URL + "iv_items/storeList?mode=0";	
	var url = G_URL + "iv_items/storeTree?mode=0";		
	$.get(url,
		{},
		function(data){
			$('#items_container').html('<div class="center"><input type="button" value=" Close " class="items_button" /></div>' + data);
			$('#tabs').tabs();
			//$('a[href|="#tabs-16"]').hide();
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

function computeValues(is_custom=0) {
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
		var catId = $(this).find('.catId').val();		
		discount = parseFloat($(this).find(".discount_presets").val());
		quantity = parseInt($(this).find(".quantity_presets").val());
		// price = parseFloat($(this).find(".base_price").val());
		price = parseFloat($(this).find(".org_price").val());
		if( catId != 8){
			payable = Math.round(((price*quantity) - discount)*100)/100;
			$(this).find(".base_price").val(price);
		}
		purch = $(this).find(".purch_price").val()
		if(catId == 8)
		{
			// var percentVal = $("#tech_percentage").val();
   //          var percentType = $("#tech_percentage_type").val();
   //          var markupPercentVal = $("#markup_percentage").val();
   //          var markupPercentType = $("#markup_percentage_type").val();
   			var percentVal = $(this).find(".item_tech_percent").val();
            var percentType = $(this).find(".item_tech_percent_type").val();
            var markupPercentVal = $(this).find(".item_markup_percent").val();
            var markupPercentType = $(this).find(".item_markup_percent_type").val();
           	var newMarkupAmount = 0;
           	var newPrchAmount = 0;
            if(percentType == 1)
            {
            	if((percentVal > 0) && (percentVal != '')){
                	var newPrchAmount = (parseFloat(purch) * parseInt(percentVal))/ 100;
            	}
            } else if(percentType == 2) {
            	if((percentVal > 0) && (percentVal != '')){
                	var newPrchAmount = parseFloat(percentVal);
            	}
            }
            if(markupPercentType == 1)
            {
            	if((markupPercentVal > 0) && (markupPercentVal !='')){
            		var newMarkupAmount =  (parseFloat(purch) * parseInt(markupPercentVal))/ 100;
            	}
            } else if(markupPercentType == 2)
            {
            	if((markupPercentVal > 0) && (markupPercentVal !='')){
            		var newMarkupAmount = parseFloat(markupPercentVal);
            	}
            }
            var totalMarkupAmount = (parseFloat(purch) + parseFloat(newMarkupAmount) + parseFloat(newPrchAmount)).toFixed(2);
			$(this).find(".base_price").val(totalMarkupAmount);
			$(this).find(".tech_purchase_price").val(newPrchAmount * quantity);
			$(this).find(".org_price").val(totalMarkupAmount);
			payable = Math.round(((totalMarkupAmount * quantity) - discount)*100)/100;
		}		
		if(isNaN(payable)) {	
			console.log("here",payable);
			$(this).find(".price_payable").val(0);
			current_cost += 0;
		} else {	
			console.log("here1",payable);
			$(this).find(".price_payable").val(payable);
			current_cost += parseFloat(payable);
		}
		// $(this).find(".tech_purchase_price").val( quantity * price);
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
		// $(this).find(".tech_purchase_price").val( quantity * price);
	});
	
	current_tax1 = current_cost*0.05;
	current_tax = parseFloat(current_tax1.toFixed(2));
	current_deposit = parseFloat($("#current_deposit").val());
	
	$("#current_subtotal").val(current_cost);
	$("#current_tax").val(current_tax); 
	$("#current_total").val((current_cost + current_tax).toFixed(2));
	$("#current_balance").val((current_cost + current_tax - current_deposit).toFixed(2));
	
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

function SavePayment(){
    var id = $('#InvoiceOrderId').val();
	var method = $("#PaymentPaymentMethodId").val();	
	var amount = $("#paid_by_amount").val();	
	var auth_number = $("#auth_number").val();
	
	$("#PaymentPaymentMethodId").attr("readonly","readonly");
	$("#paid_by_amount").attr("readonly","readonly");
	$("#auth_number").attr("readonly","readonly");
	$("#save_payment").attr("disabled","disabled");
	
	
	if (!method) {alert('A payment method should be selected!'); return;}
	if ((method>2)&&(method<6)&&(!auth_number)) {alert('An authorization number is required!'); return;} 
	$.post(G_URL + "payments/savePayment",
		{
			order_id:id, 
			method:method, 
			amount:amount, 
			payment_type:1, 
			auth_number:auth_number
		},
		function(data){
		showPayments();
		$("#PaymentPaymentMethodId").removeAttr("readonly");
		$("#paid_by_amount").removeAttr("readonly");
		$("#auth_number").removeAttr("readonly");
		$("#save_payment").removeAttr("disabled");
		
		$("#PaymentPaymentMethodId").val(0);
		$("#paid_by_amount").val(0);
		$("#auth_number").val("");
  });
}

function ErasePayment(payment_id){
  $.post(G_URL +"payments/deletePayment",{payment_id:payment_id},function(data){
    showPayments();
  });
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