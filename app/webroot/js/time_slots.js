$(function(){
	var test_server = "http://acesys.ace1.ca/acetest/acesys-2.0/index.php/";
	var live_server = "http://acesys.ace1.ca/index.php/";
	
	var l = $(location).attr('href');

	if(l.indexOf("acesys-2.0") != -1) G_URL = test_server;
	else G_URL = live_server;
	
	//tabs
	$('#tabs').tabs();
	
	$("#city_list").change(function(){
		$("#thisform").submit();		
	});
	
	$("#city_list").val($("#current_city_id").val());
	
	$(".slot a").click(function(){
		var user_id = $("#user_id").val();
		var job_date = $(this).children(".job_date").val();
		var week_number = $(this).children(".week_number").val();
		var job_time_beg = $(this).children(".job_time_beg").val();
		var job_time_end = $(this).children(".job_time_end").val();
		var job_time_name = $(this).children(".job_time_name").val();
		var city_id = $("#city_list").val();
		var route_type = $(this).children(".route_type").val();
		
		var url = G_URL + "orders/reserveTimeslot";
		
		$.post(url,
			{
				user_id:user_id,
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
	});
	
	$("#slot_no").live("click", function(){
		
	});
	
}); 

function bookTimeslot() {
	var new_item=new Array();	
	new_item[0] = $("#job_truck").val();
	new_item[1] = $("#job_date").val();
	new_item[2] = $("#job_time_beg").val();
	new_item[3] = $("#tech1").val();
	new_item[4] = $("#tech2").val();
	new_item[5] = $("#job_time_end").val();;
	window.returnValue = new_item;
	window.close();
}

function cancelTimeslot() {
		
}