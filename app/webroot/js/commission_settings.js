// JavaScript Document
$(function() {
	var test_server = "http://acesys.ace1.ca/acetest/acesys-2.0/index.php/";
	var live_server = "http://acesys.ace1.ca/index.php/";
	
	var l = $(location).attr('href');

	$(".from").hide();

	if(l.indexOf("acesys-2.0") != -1) G_URL = test_server;
	else G_URL = live_server;
	
	$(".link_job_type").live("click", function(){
		loadJobType($(this).parents(".from").find(".item_box"));
	});
	
	$(".link_item_category").live("click", function(){
		loadItemCategory($(this).parents(".from").find(".item_box"));
	});
	
	$(".link_item").live("click", function(){
		loadItem($(this).parents(".from").find(".item_box"), "Labour");
	});
	
	$(".link_cancel").live("click", function(){
		$(this).parents(".from").find(".item_box").html("");
	});
	
	$(".delete_link").live("click", function(){		
		var answer = confirm("Are you sure you want to delete this role?")
		if(answer) {
			$(this).parents(".setting").remove();
		}		
	});	
	
	$(".delete").live("click", function(){
		$(this).parents("tr").remove();
	});
	
	$(".toggle_button").live("click", function(){
		$(this).parent("h3").next(".from").toggle();
		$(this).parent("h3").next(".from").next(".from").toggle();
	});
	
	$(".link_add").click(function(){
		var container = $(".all_tech_settings");
		var name = $(this).html();
		var id = $(this).next("input").val();
		addRole(container, name, id);			
	});
	
	$(".link_new").click(function(){
		var container = $(".all_tech_settings");
		var name = prompt("Enter the role name");
		if(name != "" && name != null) {
			addRole(container, name, 0);	
		} else {
			alert("Name cannot be blank");	
		}
		
	});
	
	$(".item_box a").live("click", function(){
		var container = $(this).parent(".item_box").parent(".from");
		var name = $(this).children(".name").val();
		var commission_role_id = parseInt(container.find(".commission_role_id").val());
		var commission_from_id = parseInt(container.find(".commission_from_id").val());
		var commission_type_id = parseInt($(this).children(".commission_type_id").val());
		var commission_item_id = parseInt($(this).children(".commission_item_id").val());
		var commission_person_type_id = parseInt($(this).children(".commission_person_type_id").val());
		addCommissionItem(container, name, commission_role_id, commission_from_id, commission_type_id, commission_item_id);
	});
});

function loadJobType(container) {
	$.get(G_URL + "commissions/jobTypeList",
		{},
		function(data){
		container.html(data);				
	});	
}

function loadItemCategory(container) {
	$.get(G_URL + "commissions/itemCategoryList",
		{},
		function(data){
		container.html(data);				
	});	
}

function loadItem(container, searchStr) {
	$.get(G_URL + "commissions/itemList",
		{"search":searchStr},
		function(data){
		container.html(data);				
	});	
}

function addCommissionItem(container, name, commission_role, commission_from_id, commission_type_id, commission_item_id){
	var a = commission_role;
	var b = commission_from_id;
	var c = commission_type_id;	
	var d = commission_item_id;
	var exists = false;
	
	switch(commission_type_id) {
		case 1:		
		break;
		case 2:
		container.find(".job_type").find(".commission_item_id").each(function(){		
			if($(this).val() == commission_item_id){				
				exists = true;
				return;
			}
		});
		break;
		case 3:
		container.find(".item_category").find(".commission_item_id").each(function(){			
			if($(this).val() == commission_item_id){
				exists = true;
				return;
			}
		});
		break;
		case 4:
		container.find(".item").find(".commission_item_id").each(function(){			
			if($(this).val() == commission_item_id){
				exists = true;
				return;
			}
		});
		break;
	}
	
	
	
	if(!exists) {
		var block = '<tr>';
		block += '<td>'+name+'<input type=\"hidden\" class=\"commission_item_id\" value=\"'+d+'\" /></td>';
		block += '<td><input type=\"text\" name=\"data[Settings]['+a+']['+b+']['+c+']['+d+'][1][value_fixed]\" value=\"0\" /></td>';
		block += '<td><input type=\"text\" name=\"data[Settings]['+a+']['+b+']['+c+']['+d+'][1][value_percent]\" value=\"0\" /></td>';
		block += '<td><input type=\"text\" name=\"data[Settings]['+a+']['+b+']['+c+']['+d+'][2][value_fixed]\" value=\"0\" /></td>';
		block += '<td><input type=\"text\" name=\"data[Settings]['+a+']['+b+']['+c+']['+d+'][2][value_percent]\" value=\"0\" /></td>';
		block += '<td><input type=\"button\" value=\"X\" class=\"delete\" /></td>';
		block += '</tr>';
		switch(commission_type_id) {
			case 1:
			container.find(".job").append(block);
			break;
			case 2:
			container.find(".job_type").append(block);
			break;
			case 3:
			container.find(".item_category").append(block);
			break;
			case 4:
			container.find(".item").append(block);
			break;
		}	
	}
}

function addRole(container, name, id) {
	var exists = false;
	$(".commission_role_id").each(function(){
		if($(this).val() == id){
			exists = true;
		}
	});
	
	if(!exists) {
		var block = '';
		block += '<div class=\"setting tech\">';
		block += '<input type=\"hidden\" class=\"commission_role_id\" value=\"'+id+'\" />';
		block += '<h3>' + name + '</h3>';
		block += '<div class=\"from office\">';
		block += '	<h4>Office</h4>';
		block += '		<ul class=\"nav\">';
		block += '			<li><a class=\"link_job_type\">Add Job Type</a></li>';
		block += '			<li><a class=\"link_item_category\">Add Item Category</a></li>';
		block += '			<li><a class=\"link_item\">Add Item</a></li>';
		block += '			<li><a class=\"link_cancel\">Close</a></li>';
		block += '			<li class=\"delete_link\"><a>Delete ' + name + '</a></li>';
		block += '			<div class=\"clear_this\"></div>';
		block += '		</ul>';
		block += '	<div class=\"item_box\"></div>';
		block += '	<table>';
		block += '		<tr>';
		block += '			<th rowspan=\"2\" class=\"label_header\"></th>';
		block += '			<th colspan=\"2\">Alone</th>';
		block += '			<th colspan=\"2\">w/ Tech</th>';
		block += '		</tr>';
		block += '		<tr>';      	
		block += '			<th title=\"fixed\">$</th>';
		block += '			<th title=\"percent\">%</th>';
		block += '			<th title=\"fixed\">$</th>';
		block += '			<th title=\"percent\">%</th>';
		block += '		</tr>';
		block += '		<tbody class=\"job\">';
		block += '		<tr>';
		block += '			<td>Per Job</td>';
		block += '			<td><input type=\"text\" name=\"data[Settings]['+id+'][1][1][1][1][value_fixed]\" value=\"0\" /></td>';
		block += '			<td><input type=\"text\" name=\"data[Settings]['+id+'][1][1][1][1][value_percent]\" value=\"0\" /></td>';
		block += '			<td><input type=\"text\" name=\"data[Settings]['+id+'][1][1][1][2][value_fixed]\" value=\"0\" /></td>';
		block += '			<td><input type=\"text\" name=\"data[Settings]['+id+'][1][1][1][2][value_percent]\" value=\"0\" /></td>';
		block += '			<td><input type=\"button\" value=\"X\" class=\"delete\" disabled=\"disabled\" /></td>';
		block += '		</tr>';
		block += '		</tbody>';
		block += '		<tbody class=\"job_type\">'; 	
		block += '		</tbody>';
		block += '		<tbody class=\"item_category\">';
		block += '		</tbody>';
		block += '		<tbody class=\"item\">';     	
		block += '		</tbody>';
		block += '	</table>';
		block += '</div><!--END .office-->';
		block += '<div class=\"from upsales\">';
		block += '	<h4>Upsales</h4>';
		block += '		<ul class=\"nav\">';
		block += '			<li><a class=\"link_job_type\">Add Job Type</a></li>';
		block += '			<li><a class=\"link_item_category\">Add Item Category</a></li>';
		block += '			<li><a class=\"link_item\">Add Item</a></li>';
		block += '			<li><a class=\"link_cancel\">Close</a></li>';
		block += '			<li class=\"delete_link\"><a>Delete ' + name + '</a></li>';
		block += '			<div class=\"clear_this\"></div>';
		block += '		</ul>';
		block += '	<div class=\"item_box\"></div>';
		block += '	<table>';
		block += '		<tr>';
		block += '			<th rowspan=\"2\" class=\"label_header\"></th>';
		block += '			<th colspan=\"2\">Alone</th>';
		block += '			<th colspan=\"2\">w/ Tech</th>';
		block += '		</tr>';
		block += '		<tr>';      	
		block += '			<th title=\"fixed\">$</th>';
		block += '			<th title=\"percent\">%</th>';
		block += '			<th title=\"fixed\">$</th>';
		block += '			<th title=\"percent\">%</th>';
		block += '		</tr>';
		block += '		<tbody class=\"job\">';
		block += '		<tr>';
		block += '			<td>Per Job</td>';
		block += '			<td><input type=\"text\" name=\"data[Settings]['+id+'][0][1][1][1][value_fixed]\" value=\"0\" /></td>';
		block += '			<td><input type=\"text\" name=\"data[Settings]['+id+'][0][1][1][1][value_percent]\" value=\"0\" /></td>';
		block += '			<td><input type=\"text\" name=\"data[Settings]['+id+'][0][1][1][2][value_fixed]\" value=\"0\" /></td>';
		block += '			<td><input type=\"text\" name=\"data[Settings]['+id+'][0][1][1][2][value_percent]\" value=\"0\" /></td>';
		block += '			<td><input type=\"button\" value=\"X\" class=\"delete\" disabled=\"disabled\" /></td>';
		block += '		</tr>';
		block += '		</tbody>';
		block += '		<tbody class=\"job_type\">'; 	
		block += '		</tbody>';
		block += '		<tbody class=\"item_category\">';
		block += '		</tbody>';
		block += '		<tbody class=\"item\">';     	
		block += '		</tbody>';
		block += '	</table>';
		block += '</div><!--END .upsales-->';
		block += '</div>';
		
		container.prepend(block);
	}
}