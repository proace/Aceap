// JavaScript Document
$(function() {
	$("#start").click(function(){
		temp = $("#filename").val();
		temp = temp.replace(/ /g, "_");
		startCyphon(temp, $("#city").val())
	});	
});

function startCyphon(filename, city)
{
	
	var url = "cyphon.php";
	url += "?filename=" + filename;
	url += "&city=" + city;
	
	if(filename == '' || city == '') {
		alert("Choose a filename and a city");
		return;
	}
	
	var option = "dialogWidth:450px;dialogHeight:130px;dialogLeft:300px;dialogTop:200px;status:off;scroll:off;resizable:off;";
	
	var answer = window.showModalDialog(url,'', option);
}