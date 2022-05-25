var G_URL;
$(function() {
	var test_server = "http://acesys.ace1.ca/acetest/acesys-2.0/index.php/";
	var live_server = "http://acesys.ace1.ca/index.php/";
	
	var l = $(location).attr('href');

	if(l.indexOf("acesys-2.0") != -1) G_URL = test_server;
	else G_URL = live_server;
	
	$(".sub").hide();
	$("#bubble_search").hide();	
	$("#bubble_bookings").hide();
	$("#bubble_routes").hide();
	$("#bubble_messages").hide();
	$(".notice").hide();
	//$(".stat_messages").show();
	
	$("#trackem").click(function(){
		//not available
	});
	
	$("#schedule").click(function(){
		$(".sub").slideUp(300);
	});
	
	$("#links").click(function(){
		$(".sub").slideToggle(300);
	});	
	
	$(".sub_link").click(function(){
		$(".sub").slideUp(300);
	});	
	
	$(window).resize(function(){		
		var h = $(this).height() - 40;
		$(".main_view").css({height: h});
	});
	
	$(window).resize();
	
	$("#side_search").click(function(){		
		$("#bubble_search").toggle();
		$("#bubble_bookings").hide();
		$("#bubble_routes").hide();
		$("#bubble_messages").hide();
		$("#field_search").select();
	});	
	
	$("#side_bookings").click(function(){
		$("#bubble_search").hide();
		$("#bubble_bookings").toggle();
		$("#bubble_routes").hide();
		$("#bubble_messages").hide();
	});
	
	$("#side_routes").click(function(){
		$("#bubble_search").hide();
		$("#bubble_bookings").hide();
		$("#bubble_routes").toggle();
		$("#bubble_messages").hide();
	});
	
	$("#side_messages").click(function(){
		$("#bubble_search").hide();
		$("#bubble_bookings").hide();
		$("#bubble_routes").hide();
		$("#bubble_messages").toggle();
	});
	
	$(".stat_messages").click(function(){
		$("#side_messages").click();
	});
	
	$("#button_search").click(function(){
		$(this).attr("disabled","disabled");

		var sq_crit = $('#select_search').val();
		var sq_str = $('#field_search').val();
		
		$.get(G_URL + "orders/searchAjax",
    	{
			sq_crit:sq_crit,
			sq_str:sq_str,
			limit:0
		},
		function(data){
			$("#search_results").html(data);
			$("#button_search").removeAttr("disabled");		      
		});
	});
	
	//hover effect	
	$('tr.item').live('mouseover mouseout', function(event) {
	  if (event.type == 'mouseover') {				
		$(this).addClass('hovered');		
	  } else {
		$(this).removeClass('hovered');
	  }
	});
	
	$('tr.item').live('click', function() {
		window.open($(this).find('a').attr('href'), 'main_view');  
	});	
	
	$("a[title='Trackem Settings']").click(function(){
		showTrackemSettings();
	});
	
	$("a[title='History']").click(function(){
		showMessages();
	});
	
	$("a[title='New Message']").click(function(){
		createMessage();
	});
	
	$("a.mark_as_read").live('click', function(){
		markAsRead($(this).children('input').val());
		$(this).parent().parent().fadeOut();

		if($('#message_count').val() == 0) $("#bubble_messages").hide();	
	});
	
	$("a.reply").live('click', function(){
		replyToMessage($(this).children('input.to_user').val(), $(this).children('input.order_id').val());	
	});
	
	$("a.forward").live('click', function(){
		forwardMessage($(this).children('input.text_message').val(), $(this).children('input.order_id').val());		
	});
	
	$("a[title='Close Search']").click(function(){
		$("#bubble_search").hide();
	});
	
	$("a[title='Close Routes']").click(function(){
		$("#bubble_routes").hide();
	});
	
	$("a[title='Close Messages']").click(function(){
		$("#bubble_messages").hide();
	});
	
	loadUnreadMessages();
	loadTrackemMessages();
	
	
	setInterval('loadUnreadMessages()', 20000);
	setInterval('loadTrackemMessages()', 50000);
});

function loadUnreadMessages() {
	$.get(G_URL + "messages/messagesAjax",
	{},
	function(data){
		$("#message_results").html(data);
		var count = $('#message_count').val();
		if(count > 0) {
			$('.stat_messages').html(count);
			$('.stat_messages').show();
		} else {
			$('.stat_messages').hide();
		}
	});	
}

function markAsRead(id) {
	var count = $('#message_count').val();
	if(count - 1 > 0) {
		count--;
		$('.stat_messages').html(count);
		$('#message_count').val(count);
	} else {
		$('.stat_messages').html(0);
		$('#message_count').val(0);
		$('.stat_messages').hide();		
	}
	$.get(G_URL + "messages/ReadMessage",
	{message_id:id},
	function(data){
		//do nothing	      
	});	
}

function replyToMessage(to_user_id, order_id)
{
  var answer = window.showModalDialog(G_URL + "messages/EditMessage?order_id="+order_id+"&to_user_id="+to_user_id,'', 
  "dialogWidth:450px; dialogHeight:350px; dialogLeft:300px; dialogTop:200px;");
}
function forwardMessage(text, order_id)
{
  var answer = window.showModalDialog(G_URL + "messages/EditMessage?order_id="+order_id+"&text="+text,'', 
  "dialogWidth:450px; dialogHeight:350px; dialogLeft:300px; dialogTop:200px;");
}

function loadTrackemMessages() {
	$.get(G_URL + "messages/trackemAjax",
	{},
	function(data){
		$("#route_results").html(data);	      
	});	
}

function showMessages()
{
  var answer = window.showModalDialog(G_URL + "messages/ShowMessages",'', 
  "dialogWidth:600px; dialogHeight:350px; dialogLeft:300px; dialogTop:200px;");
}
function createMessage()
{
  var answer = window.showModalDialog(G_URL + "messages/EditMessage",'', 
  "dialogWidth:450px; dialogHeight:350px; dialogLeft:300px; dialogTop:200px;");
}

function showPlayer(order_id, agent_id, date, phone)
{
	var url = G_URL + "messages/player";
	url += "?order_id=" + order_id;
	url += "&agent_id=" + agent_id;
	url += "&phone=" + phone;
	url += "&date=" + date;
		
	var option = "dialogWidth:450px;dialogHeight:130px;dialogLeft:300px;dialogTop:200px;status:off;scroll:off;resizable:off;";
	
	var answer = window.showModalDialog(url,'', option);
}

function showTrackemSettings() {
	var url = G_URL + "messages/trackemSettings";
	
	var option = "dialogWidth:300px;dialogHeight:450px;dialogLeft:300px;dialogTop:200px;status:off;scroll:off;resizable:off;";
	
	var answer = window.showModalDialog(url,'', option);
}