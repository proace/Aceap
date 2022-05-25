$(function(){
	var test_server = "http://acesys.ace1.ca/acetest/acesys-2.0/index.php/";
	// var live_server = "http://acesys.ace1.ca/index.php/";
	var live_server = "http://acesys.ace1.ca/index.php/";
	
	var l = $(location).attr('href');

	if(l.indexOf("acesys-2.0") != -1) G_URL = test_server;
	else G_URL = live_server;

	G_URL = "/acesys/index.php/"

	// Loki- Add/Remove item
	$(".add-dup-item").live("click", function(event){
		event.stopPropagation();
		var postdata = {};
		var cur 	= $(this).parent().parent();
		var cat_cur = $(this).parents(".list_item").parent();
		postdata.id 				= "";
		postdata.sku 				= cur.children(".item_sku").val();
		postdata.name 				= cur.children(".item_name").val();
		postdata.model 	 			= cur.children(".item_model").val();
		postdata.regular_price 		= cur.children(".item_regular_price").val();
		postdata.selling_price 		= cur.children(".item_selling_price").val();
		postdata.supplier_price 	= cur.children(".item_supplier_price").val();
		postdata.efficiency 	 	= cur.children(".item_efficiency").val();
		
		postdata.iv_brand_id 	 	= cur.children(".item_brand_id").val();
		postdata.iv_supplier_id 	= cur.children(".item_supplier_id").val();
		postdata.description1 		= cur.children(".item_description1").val();
		postdata.description2 		= cur.children(".item_description2").val();
		postdata.mode 	 			= cur.children(".item_mode").val();
		postdata.active 	 		= 1;
		postdata.iv_category_id 	= cur.children(".item_category_id").val(); 
		postdata.iv_sub_category_id = cur.children(".item_sub_category_id").val();
		var brand_name	 			= cur.children(".brand-name").val();
		var supplier_name			= cur.children(".supplier-name").val();
		var category_name			= cur.children(".category-name").val();
		var sub_category_name		= cur.children(".sub-category-name").val();
		var searchStr = $('#link_search_name').val();
		$.ajax({
		url: G_URL+'iv_items/save',
		dataType: 'html',
		type: 'POST',
		cache: false,
		data:{postdata: postdata,brandName:brand_name, supplierName:supplier_name, categoryName:category_name, subCategoryname:sub_category_name,is_duplicant:1},
		success: function(data) {
				res = JSON.parse(data);
				if(res.res == "OK")
				{
					if(searchStr == ''){
						$(".link_branch",cat_cur).trigger("click");
						$(".link_branch",cat_cur).trigger("click");
					} else {
						$('#link_search_name').keyup();
					}
					//var catId = $(".ui-state-active").attr("cat-act-id");
				}
			}
		});	
	});
	
	$(".remove-dup-item").live("click", function(event){
		event.stopPropagation();
		var itemId = $(this).attr('itemId');
		var cat_cur = $(this).parents(".list_item").parent();
		var inactiveid = $(this).attr('inactiveId');
		var searchStr = $('#link_search_name').val();

		$.ajax({
		url: G_URL+'iv_items/removeDuplicantItem',
		dataType: 'html',
		type: 'POST',
		cache: false,
		data:{item_id: itemId, inactiveId:inactiveid},
		success: function(data) {
				res = JSON.parse(data);
				if(res.res == "OK")
				{
					if (searchStr == '')
					{
						$(".link_branch",cat_cur).trigger("click");
						$(".link_branch",cat_cur).trigger("click");
					} else {
						$('#link_search_name').keyup();
					}
				}
			}
		});	

	});
	//tabs
	$('#tabs').tabs();
	
	$('#dialog_link, ul#icons li').hover(
		function() { $(this).addClass('ui-state-hover'); }, 
		function() { $(this).removeClass('ui-state-hover'); }
	);
	//end tabs
	
	//assign click events
	$(".tabs a.link_branch").live("click", function(){		
		var criteria = $.parseJSON($(this).children(".criteria").val());
		var path = $(this).children(".path").val();
		
		var url = '';
		var row_class = '';
		if(path == 'item') {
			url = G_URL + "iv_items/items?seed=" + Math.random();
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
	
	$(".all_node").click();
	//end assign click events
	
	$(".tabs tr.editable").live("click", function(){
		var container = $(this).parents(".list_item");
		//alert(container.find(".criteria").val());
		var cat_cur = $(this).parents(".list_item").parent();
		var criteria = $.parseJSON(container.find(".criteria").val());
		var url = G_URL + "iv_items/items";
		showItem($(this).children(".id").val(),cat_cur);
		$.get(url,
		criteria,
		function(data){
			container.html(data);
		});		
	});
	
	// showItem($(this).children(".id").val(),cat_cur);
	$(".editable_item").live("click", function(){
		
		id = $(this).children(".id").val();
		catId = $(this).children(".item_category_id").val();
		searchStr = $('#link_search_name').val();

		var url = G_URL + "iv_items/edit/" + id+"/"+catId;
					
			var option = "width=450,height=450,left=300,top=200,status=no,resize=none";
			
			var answer = window.open(url,'', option);

			if(answer)
			{
				var timer = setInterval(function() {   
				    if(answer.closed) {  
				    	clearInterval(timer); 
				    	// console.log('test=',searchStr);
				    	$('#link_search_name').keyup();
				    	// $('#link_search_name').val(searchStr); 
				  //      	$(".link_branch",cat_cur).trigger("click");
						// $(".link_branch",cat_cur).trigger("click");
				    }  
				}, 1000); 
			}
	});
	


	$(".tabs a.link_additem").live("click", function(){
		var container = $(this).parents(".list_item");
		//alert(container.find(".criteria").val());
		var cat_cur = $(this).parents(".list_item").parent();
		console.log("hiii");
		console.log(cat_cur);
		var criteria = $.parseJSON(container.find(".criteria").val());
		var url = G_URL + "iv_items/items";
		showItem('',cat_cur,criteria);
		$.get(url,
		criteria,
		function(data){
			container.html(data);
		});		
	});
	
	$(".tabs a.link_category_settings").click(function(){
		var id = $(this).children("input").val();
		var url = G_URL + "iv_category_settings/edit/" + id;
					
		var option = "width=450,height=450,left=300,top=200,status=no,resize=none";
			
		var answer = window.open(url,'', option);
		
		//click the "All" branch twice (close,then open again) to refresh
		$(".tab-" + id).click();
		$(".tab-" + id).click();
	});
	
	$('#link_filter_name').live('click', function(){
		$('#filter_name').keyup();
	});	
	
	$(".tab_link").click(function(){
		$("#list_search").show();
		// if($(this).attr("href") == '#tabs-0') {
		// 	$("#list_filter").hide();
		// 	$("#list_search").show();
		// } else {
		// 	$("#list_filter").show();
		// 	$("#list_search").hide();	
		// }
	});
	
	// $('#link_search_name').click(function(){
	// 	var criteria = $.parseJSON('{"' + $("#search_field").val() + '":"%' + $("#search_name").val() + '%"}');
	// 	$.get(G_URL + "iv_items/items?seed=" + Math.random(),
	// 		criteria,
	// 	function(data){
	// 		$("#search_results").html(data);				
	// 	});
	// });	

	$('#link_search_name').keyup(function(){
		var str = $(this).val();
		if(str.trim() != ''){
			$.get(G_URL + "iv_items/searchInventoryItems?str="+str
				,
			function(data){
				$("#search_results").html(data);				
				$("#search_results").show();				
			});
		} else{
			$("#search_results").hide();
		}
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
	
	$('#filter_name').keyup(function(event) {
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
	//END filter code
	
	function showItem(id, cat_cur, criteria) {

		var catId = $(".ui-state-active").attr("cat-act-id");
		if(criteria == null) {
			var url = G_URL + "iv_items/edit/" + id+"/"+catId;
					
			var option = "width=450,height=450,left=300,top=200,status=no,resize=none";
			
			var answer = window.open(url,'', option);

			if(answer)
			{
				var timer = setInterval(function() {   
				    if(answer.closed) {  
				    	clearInterval(timer);  
				       	$(".link_branch",cat_cur).trigger("click");
						$(".link_branch",cat_cur).trigger("click");
				    }  
				}, 1000); 
			}
		} else {
			var url = G_URL + "iv_items/edit?seed=" + Math.random();
			if(criteria.supplier_id) url += "&supplier_id=" + criteria.supplier_id;
			if(criteria.brand_id) url += "&brand_id=" + criteria.brand_id;
			if(criteria.category_id) url += "&category_id=" + criteria.category_id;
			if(criteria.description1) url += "&description1=" + criteria.description1;
			if(criteria.description2) url += "&description2=" + criteria.description2;
			if(criteria.efficiency) url += "&efficiency=" + criteria.efficiency;
			if(criteria.model) url += "&model=" + criteria.model;
					
			var option = "width=450,height=450,left=300,top=200,status=no,resize=none";
			
			var answer = window.open(url,'', option);	
			if(answer)
			{
				var timer = setInterval(function() {   
				    if(answer.closed) {  
				    	clearInterval(timer);  
				       	$(".link_branch",cat_cur).trigger("click");
						$(".link_branch",cat_cur).trigger("click");
				    }  
				}, 1000); 
			}
		}
	}
});