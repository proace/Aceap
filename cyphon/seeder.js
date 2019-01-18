// JavaScript Document
var postal_codes = new Array();
var index = 0;
$(function() {
	$("#saveSeeds").click(function(){
		$(this).attr("disabled", true);
		$("#city").attr("disabled", true);
		$("#state").attr("disabled", true);
		$("#postal_codes").attr("disabled", true);
		var temp = $("#postal_codes").val();
		temp = temp.replace(/ /g, "");		
		postal_codes = temp.split("\n");
		temp = $("#city").val();
		edited_city = temp.replace(/ /g, "");
		saveSeeds(postal_codes[index++], edited_city, $("#state").val());		
	});
});

function saveSeeds(postal_code, city, state) {
	$.post("save_seeds.php",
		{
		postal_code:postal_code,
		city:city,
		state:state
		},
		function(data){
			if(data == "Connection error: Can't connect to MySQL server on 'localhost' (10048)") {
				data = "Connection lost. Wait 10 seconds...";
				setTimeout("$('<div class=\"red\">'" + data + "'</div>').appendTo('.output');", 1000);
				$(".output").animate({scrollTop: 999999}, 500);	
				setTimeout("$('<div class=\"red\">'" + data + "'</div>').appendTo('.output');", 2000);
				$(".output").animate({scrollTop: 999999}, 500);	
				setTimeout("$('<div class=\"red\">'" + data + "'</div>').appendTo('.output');", 3000);
				$(".output").animate({scrollTop: 999999}, 500);	
				setTimeout("$('<div class=\"red\">'" + data + "'</div>').appendTo('.output');", 4000);
				$(".output").animate({scrollTop: 999999}, 500);	
				setTimeout("$('<div class=\"red\">'" + data + "'</div>').appendTo('.output');", 5000);
				$(".output").animate({scrollTop: 999999}, 500);	
				setTimeout("$('<div class=\"red\">'" + data + "'</div>').appendTo('.output');", 6000);
				$(".output").animate({scrollTop: 999999}, 500);	
				setTimeout("$('<div class=\"red\">'" + data + "'</div>').appendTo('.output');", 7000);
				$(".output").animate({scrollTop: 999999}, 500);	
				setTimeout("$('<div class=\"red\">'" + data + "'</div>').appendTo('.output');", 8000);
				$(".output").animate({scrollTop: 999999}, 500);	
				setTimeout("$('<div class=\"red\">'" + data + "'</div>').appendTo('.output');", 9000);
				$(".output").animate({scrollTop: 999999}, 500);
				setTimeout("saveSeeds('" + postal_codes[index] + "','" + city + "','" + state + "')", 10000);
			} else {
				if(index < postal_codes.length) {
					saveSeeds(postal_codes[index++], city, state);
					$('<div class=\"gray\">' + data + '</div>').appendTo('.output');
				} else {
					$('<div class=\"gray\">' + data + '</div>').appendTo('.output');				
					$('<div class=\"blue\">End of list</div>').appendTo('.output');					
				}
				$(".output").animate({scrollTop: 999999}, 500);
			}								
		}
	);
}