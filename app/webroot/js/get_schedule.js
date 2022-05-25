$('.search_schedule').click(function(event){
  
  var trucks = $('select[name="trucks"]').val();
  
  var get_service = $('.map_services').val();
  
  var street = $('#street_number').val();
  
  var route = $('#route').val();
  
  var period = $('#sc_period').val();
  
  if (trucks=="" && get_service=="") {
    alert('please choose either truck or service');
    return false;
  }
  
   var modal = document.getElementById("myModal");
    modal.style.display = "block";
    $('.modal-body').html('Please wait Fetching results');
    event.preventDefault();
    let fromDate = $('#ffromdate').val();
    let fromDate_sc = $('#sc_from').val();
    let toDate = $('#sc_to').val();
    let postalCode = $('#postal_code').val();
    let service = $('.map_services').val();
    let choose_time = $('#choose_time').val();
    let choose_km = $('#choose_km').val();
    
//    alert(fromDate+" "+postalCode+" "+base_url);
    
     $.ajax({
      type: 'POST',
      url: base_url+"/index.php/technicians/scheduleview12",
      data: {fromDate:fromDate,postalCode:postalCode,service:service,trucks:trucks,choose_time:choose_time,choose_km:choose_km,fromDate_sc:fromDate_sc,toDate:toDate,street:street,route:route,period:period},
      dataType:"json",
      success: function(data) {
							console.log(data);
                            var modal = document.getElementById("myModal");
                             modal.style.display = "block";
                             var btn = document.getElementById("myBtn");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];
span.onclick = function(e) {
    e.preventDefault();
  modal.style.display = "none";
};
//$('.modal-body').html(data.html);

var get_data="";

$.each (data, function (bb) {
    
    get_data+= ("<div class='dis_result'>"+data[bb].html+"</div>");
});
if ($.isEmptyObject(data)) {
    $('.modal-body').html('No Booking Found');
}
else {
$('.modal-body').html(get_data);
  
}


$( "#myModal" ).draggable();
//$('.draggable').removeClass('blink');
//$('#divjob'+data.id).addClass('blink');
						
						}
});
    
    });



// Get the modal
var modal = document.getElementById("myModal");

// Get the button that opens the modal
var btn = document.getElementById("myBtn");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks the button, open the modal 
btn.onclick = function(e) {
    e.preventDefault();
  modal.style.display = "block";
  
};

// When the user clicks on <span> (x), close the modal
span.onclick = function(e) {
    e.preventDefault();
  modal.style.display = "none";
};