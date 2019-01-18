$(function(){
	var test_server = "http://acesys.ace1.ca/acetest/acesys-2.0/index.php/";
	var live_server = "http://acesys.ace1.ca/index.php/";
	
	var l = $(location).attr('href');

	if(l.indexOf("acesys-2.0") != -1) G_URL = test_server;
	else G_URL = live_server;
	
	//tabs
	$('#tabs').tabs();
	
	//date picker
	$("#date_picker").datepicker({
			showOtherMonths: true,
			selectOtherMonths: true,
			dateFormat: 'dd M yy'
	});
	
	//when date is changed
	$("#date_picker").change(function(){
		window.open(G_URL + "orders/scheduleByDistance?date_picker=" + $("#date_picker").val(), "_self");
	});
	
	//previous date button
	$(".previous_date").click(function(){
		window.open(G_URL + "orders/scheduleByDistance?date_picker=" + $("#prevdate").val(), "_self");
	});
	
	//next date button
	$(".next_date").click(function(){
		window.open(G_URL + "orders/scheduleByDistance?date_picker=" + $("#nextdate").val(), "_self");
	});
	
}); 

function bookTimeslot() {
	var new_item=new Array();	
	new_item[0] = $("#job_truck").val();
	new_item[1] = $("#job_date").val();
	new_item[2] = $("#job_time_beg").val();
	new_item[3] = $("#tech1").val();
	new_item[4] = $("#tech2").val();
	new_item[5] = $("#job_time_end").val();
	window.returnValue = new_item;
	window.close();
}
