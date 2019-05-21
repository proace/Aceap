//$(function(){
//	storeTreeInitialize();
//});
	function storeTreeInitialize() {
		
	/*var test_server = "http://acesys.ace1.ca/acesys/acesys-2.0/index.php/";
	var live_server = "http://acesys.ace1.ca/index.php/";
	
	var l = $(location).attr('href');

	if(l.indexOf("acesys-2.0") != -1) G_URL = test_server;
	else G_URL = live_server;*/

	//tabs
	//$('#tabs').tabs();
	
	G_URL = "/acesys/index.php/";
	
	// $('#dialog_link, ul#icons li').hover(
	// 	function() { $(this).addClass('ui-state-hover'); }, 
	// 	function() { $(this).removeClass('ui-state-hover'); }
	// );
	//end tabs
	$(".changeTab").live("onchange", function(){
		console.log($(this));
	});
	// $("#triggerTree").live("click", function(){
	// 	alert('ff');
	// 	console.log("jbh");
	// 	var id = $(this).attr('treetabId');
	// 	console.log("id=", id);
	// 	$('.activeClick'+id).trigger();
	// });

	//assign click events
	$(".tabs a.link_branch").live("click", function(){		
		var criteria = $.parseJSON($(this).children(".criteria").val());
		var path = $(this).children(".path").val();
		var url = '';
		var row_class = '';
		if(path == 'item') {
			url = G_URL + "iv_items/storeTreeItems?seed=" + Math.random() + "&mode=store";
			row_class = 'list_item';
		} else {
			url = G_URL + "iv_items/branch?seed=" + Math.random();
			row_class = 'list_branch';
		}
		
		var temp = $(this).next("ul");
		if($(this).attr("title") == "More") {
			$.get(url,
			criteria,
			function(data){
				temp.addClass(row_class);
				temp.html(data);				
			});
			$(this).attr("title", "Less");
		} else {
			temp.html("");
			$(this).attr("title", "More");
		}
	});
	
	//$(".all_node").click();
	//end assign click events
	
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

	// 	// addItem($(this).children(".item_id").val(), $(this).children(".item_name").val(), $(this).children(".item_selling_price").val(), $(this).children(".item_mode").val(), $(this).children(".item_category_id").val(), $(this).children(".item_supplier_price").val(), $(this).children(".item_model").val());
		
	// 	$('#closeBookedItems').click();
	// 	$('#closeTechnicianSales').click();
	// });

	//Loki: Add multiple items
	$("#multipleItems").live("click", function(){
		var items= [];	
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
			itema.item_mode 	 		= cur.children(".item_mode").val();
			items.push(itema);
		});

		$(items).each(function( index ) {
  			addItem(items[index].item_id, items[index].item_name, items[index].selling_price, items[index].item_mode, items[index].item_category_id, items[index].supplier_price, items[index].item_model);
		});
		
		$('#closeBookedItems').click();
		$('#closeTechnicianSales').click();
	});
	
	//filter code from http://net.tutsplus.com/tutorials/javascript-ajax/using-jquery-to-manipulate-and-filter-data/

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
	
	} //END storeTreeInitialize()

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
