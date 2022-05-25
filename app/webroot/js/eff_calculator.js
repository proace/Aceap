
$(function() {
	
	$("#calc_dialog").dialog({
		modal: true,
		autoOpen: false,
		width: 500
	});
	
	$("#calc_link").click(function(){
		$("#calc_dialog").dialog("open");
	});
		
	$(".calc_digits").click(function(){
		var field = $("#cost").html();
		if(field.length < 8) {				
			field = parseFloat(field);
			$("#cost").html((field*10) + parseFloat($(this).val()));
			drawEffScale();
		}
	});
	
	$(".calc_clear").click(function(){
		$("#cost").html(0);
		drawEffScale();
	});
	
	$(".calc_percent").click(function(){
		$("#old_eff").html(parseFloat($(this).val()));
		drawEffScale();

	});
		
	
});

function drawEffScale() {
	var field = $("#cost").html();			
	field = parseFloat(field);
	
	if(field > 500)  adjusted = (field%300) + 100; else adjusted = field;

	switch($("#old_eff").html()) {
		case "60" :				
			$("#old_scale").css("height", adjusted + "px");
			$("#old_cost").html("$" + field);
			$("#new1_scale").css("height", (adjusted - Math.ceil((0.32*adjusted))) + "px");
			$("#new1_cost").html("$" + (field - Math.round(0.32*field)));
			$("#new2_scale").css("height", (adjusted - Math.ceil((0.35*adjusted))) + "px");
			$("#new2_cost").html("$" + (field - Math.round(0.35*field)));
		break;
		case "70" :				
			$("#old_scale").css("height", adjusted + "px");
			$("#old_cost").html("$" + field);
			$("#new1_scale").css("height", (adjusted - Math.ceil((0.22*adjusted))) + "px");
			$("#new1_cost").html("$" + (field - Math.round(0.22*field)));
			$("#new2_scale").css("height", (adjusted - Math.ceil((0.25*adjusted))) + "px");
			$("#new2_cost").html("$" + (field - Math.round(0.25*field)));			
		break;
		case "80" :				
			$("#old_scale").css("height", adjusted + "px");
			$("#old_cost").html("$" + field);
			$("#new1_scale").css("height", (adjusted - Math.ceil((0.12*adjusted))) + "px");
			$("#new1_cost").html("$" + (field - Math.round(0.12*field)));
			$("#new2_scale").css("height", (adjusted - Math.ceil((0.15*adjusted))) + "px");
			$("#new2_cost").html("$" + (field - Math.round(0.15*field)));	
		break;
	}
	
	

	
	
	
	
}