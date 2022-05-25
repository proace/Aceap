// JavaScript Document
$(function() {
	$("#Appliances").hide();
	$("#Carpet").hide();
	$("#Other").hide();
	$("#Parts").hide();
	$(".new_items").hide();	
	
	$("#item_type").change(function(){		
		switch(parseInt($(this).val())){
		case 1:
			$("#Heating").show();
			$("#Appliances").hide();
			$("#Carpet").hide();
			$("#Other").hide();
			$("#Parts").hide();		  
		  	break;
		case 2:
			$("#Heating").hide();
			$("#Appliances").show();
			$("#Carpet").hide();
			$("#Other").hide();
			$("#Parts").hide();		  
		  	break;
		case 3:
			$("#Heating").hide();
			$("#Appliances").hide();
			$("#Carpet").show();
			$("#Other").hide();
			$("#Parts").hide();		  
		  	break;
		case 4:
			$("#Heating").hide();
			$("#Appliances").hide();
			$("#Carpet").hide();
			$("#Other").show();
			$("#Parts").hide();		  
		  	break;
		case 5:
			$("#Heating").hide();
			$("#Appliances").hide();
			$("#Carpet").hide();
			$("#Other").hide();
			$("#Parts").show();		  
		  	break;
		}
	});
	
	$("append_test").click(function(){
		$("<input />")
		  .attr("type","hidden")
		  .attr("id","test_append")
		  .val("Something")
		  .appendTo("#button");
		alert($("#test_append").val());
	});
	
	$(".item_add").click(function(){
		name = $(this).parents(".item").find(".item_name").val();
		price = $(this).parents(".item").find(".item_price").val();
		discount = 0.00;
		addition = 0.00;
		quantity = 1;
		dealer = $(this).parents(".item").find(".dealer").val();
		item_category_id = $(this).parents(".item").find(".item_category_id").val();
		id = $(this).parents(".item").find(".item_id").val();
		price_purchase = $(this).parents(".item").find(".item_price_purchase").val();
		$("#tech_items").append(addItem(name, price, discount, addition, quantity, id, price_purchase, item_category_id, dealer));
		$(".new_items").hide();
		$(".section").show();
		$(".bottom_nav").show();
	});
		
	$(".new_item_add").click(function(){
		$(".section").hide();
		$(".bottom_nav").hide();
		$(".new_items").show();
		$('html, body').animate( { scrollTop: 0 }, 0 );
	});
	
	$(".new_item_close").click(function(){
		$(".section").show();
		$(".bottom_nav").show();
		$(".new_items").hide();		
	});
	
	$("#details").click(function(){
		
		window.open("invoiceSummary", "_self");
	});
	
	$("#questions").click(function(){
		window.open("invoiceDetails?orderid=" + $("#InvoiceOrderId").val(), "_self");
	});
	
	$("#items").click(function(){
		window.open("invoiceQuestions?orderid=" + $("#InvoiceOrderId").val(), "_self");
	});
	
	$("#payment").click(function(){
		window.open("invoiceItems?orderid=" + $("#InvoiceOrderId").val(), "_self");
	});
	
	$("#feedback").click(function(){
		window.open("invoicePayment?orderid=" + $("#InvoiceOrderId").val(), "_self");
	});
	
	function calculateTotal(price, quantity, discount, addition) {
		var sum = price*quantity - discount + addition;
		return sum.toFixed(2);
	}
	
	function addItem(name, price, discount, addition, quantity, id, price_purchase, item_category_id, dealer) {
		var tbody = "";
		if(price_purchase <= 0) price_purchase = 0;
		var sum = price - discount + addition;
				
		tbody += "<tbody class=\"item\">";
        tbody += "	<tr class=\"summary\">";
        tbody += "		<td>" + name + "</td>";
        tbody += "		<td class=\"sum value\">" + sum + "</td>";        
        tbody += "	</tr>";
        tbody += "	<tr class=\"detail\">";
        tbody += "	<td>Quantity";
        tbody += "		<input name=\"data[BookingItem][" + id + "][quantity]\"  type=\"text\" value=\"" + quantity + "\" class=\"quantity\" id=\"BookingItem" + id + "Quantity\" />";
        tbody += "		<input name=\"data[BookingItem][" + id + "][name]\"  type=\"text\" value=\"" + name + "\" class=\"name\" id=\"BookingItem" + id + "Name\" />";
        tbody += "		<input name=\"data[BookingItem][" + id + "][item_id]\"  type=\"text\" value=\"" + id + "\" class=\"item_id\" id=\"BookingItem" + id + "ItemId\" />";                
        tbody += "		<input name=\"data[BookingItem][" + id + "][item_category_id]\"  type=\"text\" value=\"" + item_category_id + "\" class=\"item_category_id\" id=\"BookingItem" + id + "ItemCategoryId\" />";
        tbody += "		<input name=\"data[BookingItem][" + id + "][dealer]\"  type=\"text\" value=\"" + dealer + "\" class=\"dealer\" id=\"BookingItem" + id + "Dealer\" />";
        tbody += "	</td>";
        tbody += "	<td class=\"quantity value editable\">1</td>";		        
        tbody += "	</tr>";
        tbody += "	<tr class=\"detail\">";
        tbody += "    	<td>Price<input name=\"data[BookingItem][" + id + "][price]\"  type=\"text\" value=\"" + price + "\" class=\"price\" id=\"BookingItem" + id + "Price\" /></td>";
        tbody += "		<td class=\"price value\">" + price + "</td>";                        
        tbody += "	</tr>";
        tbody += "	<tr class=\"detail\">";
        tbody += "    	<td>Discount<input name=\"data[BookingItem][" + id + "][discount]\"  type=\"text\" value=\"" + discount + "\" class=\"discount\" id=\"BookingItem" + id + "Discount\" /></td>";
        tbody += "		<td class=\"discount value editable\">" + discount + "</td>";                
        tbody += "	</tr>";
        tbody += "	<tr class=\"detail\">";
        tbody += "    	<td>Additional<input name=\"data[BookingItem][" + id + "][addition]\"  type=\"text\" value=\"" + addition + "\" class=\"addition\" id=\"BookingItem" + id + "Addition\" /></td>";
        tbody += "		<td class=\"addition value editable\">" + addition + "</td>";                       
        tbody += "	</tr>";
        tbody += "	<tr class=\"detail\">";
        tbody += "    	<td>Purchase Price<input name=\"data[BookingItem][" + id + "][price_purchase]\"  type=\"text\" value=\"" + price_purchase + "\" class=\"price_purchase\" id=\"BookingItem" + id + "PricePurchase\" /></td>";
        tbody += "		<td class=\"price_purchase value\">" + price_purchase + "</td>";        
        tbody += "	</tr>";
        tbody += "	<tr class=\"detail\">";
        tbody += "    	<td>Installed<input name=\"data[BookingItem][" + id + "][installed]\"  type=\"text\" value=\"0\" class=\"installed\" id=\"BookingItem" + id + "Installed\" /></td>";
        tbody += "		<td class=\"installed editable\">Not Installed</td>";       
        tbody += "	</tr>";
        tbody += "</tbody>";
		
		
		return tbody;
	}

	
	$(".quantity").live("click", function(){
		var temp = prompt("Quantity",$(this).html());

		if(temp != null && temp != "" && !isNaN(parseInt(temp))) {
			temp = parseInt(temp);
			//$(this).next("input.quantity").val(temp);
			$(this).parents("tbody").find("input.quantity").val(temp);
			$(this).html(temp);
			
			var price = parseFloat($(this).parents("tbody").find("input.price").val());		
			var quantity = parseInt($(this).parents("tbody").find("input.quantity").val());
			var discount = parseFloat($(this).parents("tbody").find("input.discount").val());
			var addition = parseFloat($(this).parents("tbody").find("input.addition").val());
			
			$(this).parents("tbody").find(".sum").html(calculateTotal(price, quantity, discount, addition));
		}
	});
	
	$(".discount").live("click", function(){
		var temp = prompt("Discount",$(this).html());

		if(temp != null && temp != "" && !isNaN(parseFloat(temp))) {
			temp = parseFloat(temp);
			temp = temp.toFixed(2);
			//$(this).next("input.discount").val(temp);
			$(this).parents("tbody").find("input.discount").val(temp);
			$(this).html(temp);
			
			var price = parseFloat($(this).parents("tbody").find("input.price").val());		
			var quantity = parseInt($(this).parents("tbody").find("input.quantity").val());
			var discount = parseFloat($(this).parents("tbody").find("input.discount").val());
			var addition = parseFloat($(this).parents("tbody").find("input.addition").val());
			
			$(this).parents("tbody").find(".sum").html(calculateTotal(price, quantity, discount, addition));
		}
	});
	
	$(".addition").live("click", function(){
		var temp = prompt("Addition",$(this).html());		
		if(temp != null && temp != "" && !isNaN(parseFloat(temp))) {
			temp = parseFloat(temp);
			temp = temp.toFixed(2);
			//$(this).next("input.addition").val(temp);
			$(this).parents("tbody").find("input.addition").val(temp);
			$(this).html(temp);
			
			var price = parseFloat($(this).parents("tbody").find("input.price").val());		
			var quantity = parseInt($(this).parents("tbody").find("input.quantity").val());
			var discount = parseFloat($(this).parents("tbody").find("input.discount").val());
			var addition = parseFloat($(this).parents("tbody").find("input.addition").val());
			
			$(this).parents("tbody").find(".sum").html(calculateTotal(price, quantity, discount, addition));
		}				
	});
	
	$(".installed").live("click", function(){
		var temp = confirm("Hit OK for Installed");	
		if (temp == true) {
			//$(this).next("input.installed").val(1);
			$(this).parents("tbody").find("input.installed").val(1);
  			$(this).parents("tbody").find(".installed").html("Installed");					
  		} else {
			//$(this).next("input.installed").val(0);		
			$(this).parents("tbody").find("input.installed").val(0);		
			$(this).parents("tbody").find(".installed").html("Not Installed");
  		}						
	});
	
	
	
});
