$(function(){
	var test_server = "http://acesys.ace1.ca/acetest/acesys-2.0/index.php/";
	// var live_server = "http://acesys.ace1.ca/index.php/";
	var live_server = "http://acesys.ace1.ca/index.php/";
	
	var l = $(location).attr('href');

	if(l.indexOf("acesys-2.0") != -1) G_URL = test_server;
	else G_URL = live_server;

	G_URL = "/acesys/index.php/"



	//Loki:
		
	  $('.allSelect').live('click',function(){
        if(this.checked){
            $('.transferSelect').each(function(){
                this.checked = true;
            });
        }else{
             $('.transferSelect').each(function(){
                this.checked = false;
            });
        }
    });
	 $(".transferSelect").live("click", function(e){	 	
    	 e.stopPropagation();
    	 // this.checked = true;
    	 if(this.checked){        
              this.checked = true;        
        }else{     
            this.checked = false;
        }
	 });

	$(".transferQty").live("click", function(){
		var items= [];
		var cat_cur = $(this).parents(".list_item").parent();
		$('.transferSelect:checkbox:checked').each(function () {
			var cur = $(this).parent().parent();
			var itema = {}; 
       		itema.item_id 		 		= $(".item_id",cur).val();
			itema.default_qty 	 		= $(".default_qty",cur).val();
			itema.item_category_id 	 	= $(".item_category_id" ,cur).val();
            itema.item_sub_category_id  = $('.item_sub_category_id',cur).val();
			items.push(itema);
		});
		
		var itemArr   = JSON.stringify(items);
			
			$.ajax({
				url:G_URL+"inventories/transferItemQty",
				type: 'POST',
				dataType: 'json',
				data: {items:itemArr},
				cache: false,
				success: function(data) {
					if(data.res == "1")
					{
	             		$(".link_branch",cat_cur).trigger("click");
						$(".link_branch",cat_cur).trigger("click");
					}
				}			
			});
	});

	$(".transferPkg").live("click", function(){
		var items= [];
		var cat_cur = $(this).parents(".list_item").parent();
		$('.transferSelect:checkbox:checked').each(function () {
			var cur = $(this).parent().parent();
			var itema = {}; 
       		itema.item_id 		 		= $(".item_id",cur).val();
			itema.default_qty 	 		= $(".default_qty",cur).val();
			itema.item_category_id 	 	= $(".item_category_id" ,cur).val();
            itema.item_sub_category_id  = $('.item_sub_category_id',cur).val();
            itema.name  = $('.item_name',cur).val();
            itema.sku  = $('.item_sku',cur).val();
            itema.selling_price  = $('.item_selling_price',cur).val();
            itema.supplier_price  = $('.item_supplier_price',cur).val();
            itema.supplier_id  = $('.item_supplier_id',cur).val();
			items.push(itema);
		});
		
		var itemArr   = JSON.stringify(items);

		var packages = [];
		$('#IvItemIvSubCategoryId option:selected').each(function() {
    		packages.push($(this).val());
		});
		var packageArr     = JSON.stringify(packages);
		if(packages.length > 0)
		{	
			$.ajax({
				url:G_URL+"inventories/transferPackage",
				type: 'POST',
				dataType: 'json',
				data: {items:itemArr,packages:packageArr},
				cache: false,
				success: function(data) {
					if(data.res == "1")
					{
						// $('.transferSelect').attr('selected',false);
						$(".showPackages").dialog('close');
	             		$(".link_branch",cat_cur).trigger("click");
						$(".link_branch",cat_cur).trigger("click");
					}
				}			
			});

		} else {
				alert("Please select atleast one package.")
			}
	});
	
	$(".copyItems").live("click", function(){
		var items= [];
		var cat_cur = $(this).parents(".list_item").parent();
		$('.transferSelect:checkbox:checked').each(function () {
			var cur = $(this).parent().parent();
			var itema = {}; 
       		itema.item_id 		 		= $(".item_id",cur).val();
			itema.default_qty 	 		= $(".default_qty",cur).val();
			itema.item_category_id 	 	= $(".item_category_id" ,cur).val();
            itema.item_sub_category_id  = $('.item_sub_category_id',cur).val();
            itema.name  = $('.item_name',cur).val();
            itema.sku  = $('.item_sku',cur).val();
            itema.selling_price  = $('.item_selling_price',cur).val();
            itema.supplier_price  = $('.item_supplier_price',cur).val();
            itema.supplier_id  = $('.item_supplier_id',cur).val();
			items.push(itema);
		});
		
		var itemArr   = JSON.stringify(items);
		var subCatId = $('#sub_category_id').val();
		var catId = $('#IvItemIvCategoryId').val();

		if(itemArr.length > 0)
		{	
			$.ajax({
				url:G_URL+"iv_items/copyItems",
				type: 'POST',
				dataType: 'json',
				data: {items:itemArr,subCatId :subCatId, catId:catId },
				cache: false,
				success: function(data) {
					if(data.res == "1")
					{
						// $('.transferSelect').attr('selected',false);
						$(".showSubCategory").dialog('close');
	             				alert("Items copied successfully");
					}
				}			
			});

		} else {
				alert("Please select atleast one item.")
			}
	});
	// loki delete multiple items

	$(".deleteItems").live("click", function(){
		var items= [];
		var cat_cur = $(this).parents(".list_item").parent();
		$('.transferSelect:checkbox:checked').each(function () {
			var cur = $(this).parent().parent();
			var itema = {}; 
       		itema.item_id 		 		= $(".item_id",cur).val();
			itema.default_qty 	 		= $(".default_qty",cur).val();
			itema.item_category_id 	 	= $(".item_category_id" ,cur).val();
            itema.item_sub_category_id  = $('.item_sub_category_id',cur).val();
            itema.name  = $('.item_name',cur).val();
            itema.sku  = $('.item_sku',cur).val();
            itema.selling_price  = $('.item_selling_price',cur).val();
            itema.supplier_price  = $('.item_supplier_price',cur).val();
            itema.supplier_id  = $('.item_supplier_id',cur).val();
			items.push(itema);
		});
		
		var itemArr   = JSON.stringify(items);
		var subCatId = $('#sub_category_id').val();
		var catId = $('#IvItemIvCategoryId').val();

		if(itemArr.length > 0)
		{	
			$.ajax({
				url:G_URL+"iv_items/deleteMultipleItems",
				type: 'POST',
				dataType: 'json',
				data: {items:itemArr,subCatId :subCatId, catId:catId },
				cache: false,
				success: function(data) {
					if(data.res == "1")
					{	
						$(".link_branch",cat_cur).trigger("click");
						$(".link_branch",cat_cur).trigger("click");
					}
				}			
			});

		} else {
				alert("Please select atleast one item.")
			}
	});

	// Loki transfer items:

	$(".TransferItems").live("click", function(){
		var items= [];
		var cat_cur = $(this).parents(".list_item").parent();
		$('.transferSelect:checkbox:checked').each(function () {

			var cur = $(this).parent().parent();
			var itema = {}; 
       		itema.item_id 		 		= $(".item_id",cur).val();
			itema.default_qty 	 		= $(".default_qty",cur).val();
			itema.item_category_id 	 	= $(".item_category_id" ,cur).val();
            itema.item_sub_category_id  = $('.item_sub_category_id',cur).val();
            itema.name  = $('.item_name',cur).val();
            itema.sku  = $('.item_sku',cur).val();
            itema.selling_price  = $('.item_selling_price',cur).val();
            itema.supplier_price  = $('.item_supplier_price',cur).val();
            itema.supplier_id  = $('.item_supplier_id',cur).val();
			items.push(itema);
		});
		
		var itemArr   = JSON.stringify(items);
		var subCatId = $('#sub_category_id_transfer').val();
		var catId = $('#IvItemTransferCategoryId').val();

		if(itemArr.length > 0)
		{	
			$.ajax({
				url:G_URL+"iv_items/transferItems",
				type: 'POST',
				dataType: 'json',
				data: {items:itemArr,subCatId :subCatId, catId:catId },
				cache: false,
				success: function(data) {
					if(data.res == "1")
					{
						// $('.transferSelect').attr('selected',false);
                                               $(".showTransferItem").dialog('close');
						
	             				alert("Items transfered successfully");
					}
				}			
			});

		} else {
				alert("Please select atleast one item.")
			}
	});

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
		postdata.markup_percent		= cur.children(".markup_percent").val();
		postdata.tech_percent		= cur.children(".tech_percent").val();
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
				console.log('res=',res);
				if(res.res == "OK")
				{
					if(searchStr == ''){
						console.log('res1');
						$(".link_branch",cat_cur).trigger("click");
						$(".link_branch",cat_cur).trigger("click");
					} else {
						console.log('res2');
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
				} else {
					alert("Item already used in Booking");	
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
		var catId = $(this).attr('cat_id');
		var url = '';
		var row_class = '';
		if(path == 'item') {
			url = G_URL + "iv_items/items?seed=" + Math.random();
			row_class = 'list_item';
		} else {
			url = G_URL + "iv_items/midBranch?seed=" + Math.random();
			row_class = 'list_mid_branch';
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
	
 	$(".tabs a.link_mid_branch").live("click", function(){	
		var criteria = $.parseJSON($(this).children(".criteria").val());
		var path = $(this).children(".path").val();
		var midcatId = $(this).attr('mid_id');
		var url = '';
		var row_class = '';
		if(path == 'item') {
			url = G_URL + "iv_items/items?seed=" + Math.random()+"&category_id="+midcatId;
			row_class = 'list_item';
		} else {
			url = G_URL + "iv_items/branch?seed=" + Math.random()+"&category_id="+midcatId;
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
		var row_order = $(this).attr("row_order");
		//alert(container.find(".criteria").val());
		var cat_cur = $(this).parents(".list_item").parent();
		var criteria = $.parseJSON(container.find(".criteria").val());
		var url = G_URL + "iv_items/items";
		showItem($(this).children(".id").val(),cat_cur,'',row_order);
		// $.get(url,
		// criteria,
		// function(data){
		// 	container.html(data);
		// });		
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
		var str = $(this).val().trim();
		if(str != '' && str.length >= 3){
			$.get(G_URL + "iv_items/searchInventoryItems?str="+str,
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
	
	function showItem(id, cat_cur, criteria,rowId='') {
		var catId = $(".ui-state-active").attr("cat-act-id");
		if(criteria == '') {
			var url = G_URL + "iv_items/edit/" + id+"/"+catId+"/1";
					
			var option = "width=450,height=450,left=300,top=200,status=no,resize=none";
			
			var answer = window.open(url,'', option);
			
			if(answer)
			{
				var timer = setInterval(function() {   
				    if(answer.closed) {  
				    	clearInterval(timer);  
				    	var returnId = answer.returnValueId;
	            		var itemArr = returnId.split(';');
	            		$(".rowNum"+rowId).find(".item_sku").val(itemArr[3]);
	            		$(".rowNum"+rowId).find(".item_name").val(itemArr[1]);
	            		$(".rowNum"+rowId).find(".item_selling_price").val(itemArr[5]);
	            		$(".rowNum"+rowId).find(".item_supplier_price").val(itemArr[2]);
	            		$(".rowNum"+rowId).find(".default_qty").val(itemArr[6]);
	            		$(".rowNum"+rowId).find(".name").html(itemArr[1]);
	            		$(".rowNum"+rowId).find(".sku").html(itemArr[3]);
	            		$(".rowNum"+rowId).find(".quantity").html(itemArr[6]);
	            		$(".rowNum"+rowId).find(".supplier_price").html(itemArr[2]);
	            		$(".rowNum"+rowId).find(".selling_price").html(itemArr[5]);
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