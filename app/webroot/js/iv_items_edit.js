var G_URL;
$(function() {
	var test_server = "http://acesys.ace1.ca/acetest/acesys-2.0/index.php/";
	var live_server = "http://acesys.ace1.ca/index.php/";
	
	var l = $(location).attr('href');

	if(l.indexOf("acesys-2.0") != -1) G_URL = test_server;
	else G_URL = live_server;
	
	$('a[title="New Category"]').live('click', function(){
		showCategoryField();
		$.get(G_URL + "iv_categories/dropdownAjax",
		{seed:Math.random()},
		function(data){
			$("#selection_category").html(data);			
		});	
	});
	
	$('a[title="New Brand"]').live('click', function(){
		showBrandField();
		$.get(G_URL + "iv_brands/dropdownAjax",
		{seed:Math.random()},
		function(data){
			$("#selection_brand").html(data);			
		});	
	});
	
	$('a[title="New Supplier"]').live('click', function(){
		showSupplierField();
		$.get(G_URL + "iv_suppliers/dropdownAjax",
		{seed:Math.random()},
		function(data){
			$("#selection_supplier").html(data);			
		});	
	});
	
	$('.button_cancel').click(function(){
		window.close();
	});
	
});

function showCategoryField() {
	var url = G_URL + "iv_categories/edit";
		
	var option = "dialogWidth:450px;dialogHeight:135px;dialogLeft:300px;dialogTop:200px;status:off;scroll:off;resizable:off;";
	
	var answer = window.showModalDialog(url,'', option);
}

function showBrandField() {
	var url = G_URL + "iv_brands/edit";
		
	var option = "dialogWidth:450px;dialogHeight:135px;dialogLeft:300px;dialogTop:200px;status:off;scroll:off;resizable:off;";
	
	var answer = window.showModalDialog(url,'', option);
}

function showSupplierField() {
	var url = G_URL + "iv_suppliers/edit";
		
	var option = "dialogWidth:450px;dialogHeight:135px;dialogLeft:300px;dialogTop:200px;status:off;scroll:off;resizable:off;";
	
	var answer = window.showModalDialog(url,'', option);
}