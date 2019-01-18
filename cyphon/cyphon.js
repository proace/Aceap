// JavaScript Document
var g_postal_codes = [];
var g_cities = [];
var g_states = [];
var g_index = 0;
var g_url = '';
var g_filename = '';
var g_url = '';

function setUrl(url) {
	g_url = url;	
}

function setCityScope(city) {
	g_city_scope = city;	
}

function setFilename(filename) {
	g_filename = filename;	
}

function startMiner(postal_code, city, state, filename, url) {
	$.post("harvest/",
		{
		postal_code:postal_code,
		city:city,
		state:state,
		file_name:filename,
		url:url
		},
		function(data){
			if(g_index < g_postal_codes.length) {
				//$('<div class=\"gray\">' + data + '</div>').appendTo('.results');
				$('.results').html('<div class=\"gray\">' + data + '</div>');
				//setTimeout("startMiner('" + g_postal_codes[g_index] + "','" + g_cities[g_index] + "','" + g_states[g_index++] + "',filename,url);", 1000);
				startMiner(g_postal_codes[g_index], g_cities[g_index], g_states[g_index++], filename, url);			
			} else {
				//$('<div class=\"gray\">' + data + '</div>').html('.results');
				//$('<div class=\"blue\">Done</div>').appendTo('.results');
				$('.results').html('<div class=\"blue\">Done</div>');
			}

			$( "#progressbar" ).progressbar({
				value: Math.ceil((g_index/g_postal_codes.length)*100)
			});

			//$(".results").animate({scrollTop: 999999}, 500);
		}
	);
}

function populateSeeds(city) {
	$.getJSON("json_seeds.php",
	{
		city:city
	},
	function(data) {
		$.each(data, function(key, val){
			g_postal_codes.push(val.postal_code);
			g_cities.push(val.city);
			g_states.push(val.state);
		});
		startMiner(g_postal_codes[g_index], g_cities[g_index], g_states[g_index++], g_filename, g_url);
	});
}