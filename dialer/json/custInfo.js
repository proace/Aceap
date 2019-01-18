//LOAD THE MAIN FUNCTIONS


$(function(){
    
    autoCall(); //LOAD INFO AND START DIALING IF AUTO ON
    $("#disposeBtn").bind("click", function(){
        disposeCall(); 
        i++;
        autoCall();  
          
        
      });


      //hang up button
    $("#view-source").bind("click", hangUp);
 
 });

//LOOPER VAR
var i = 0;
var autoON = true;

//SHOWS CUSTOMER INFO, ONE BY ONE AFTER EVERY DISPOSAL AND AUTO DIALS IF AUTO ON
function autoCall()
{
     __ajax("databases/showCustomer.php", "")
        .done( function( info ){
        var customer = JSON.parse(info);
        console.log(customer);
        var name = ""
        var info = ""; 
        var last = "";
        
               
        if(customer.data[i])
        {
            //LOADS CUSTOMER INFORMACION
            var ID =+ `${customer.data[i].CUSTOMERS_ID}`
            name+= `<h2 class="mdl-card__title-text">${customer.data[i].CUSTOMERS_FIRSTNAME + " " + customer.data[i].CUSTOMERS_LASTNAME}</h2>`
            info+=`
            <b id="custID">${ID}</b><br>
            <b>Address: </b>${customer.data[i].CUSTOMERS_ADDRESS}<br>
            <b>City: </b>${customer.data[i].CUSTOMERS_CITY}<br>
            <b>Zipcode: </b>${customer.data[i].CUSTOMERS_ZIPCODE}<br>
            <b>Cellphone: </b>${customer.data[i].CUSTOMERS_CELLPHONE}<br>
            <b>Homephone: </b>${customer.data[i].CUSTOMERS_HOMEPHONE} <br>
            <b>Email: </b>${customer.data[i].CUSTOMERS_EMAIL}<br>
            `

            last+= `<h4>Last Called: <br>
            ${customer.data[i].BOOKING_DATE}</h4>`

            //
            if(autoON)
            {
                 
                
                /*if (webphone_api.isincall() === true)
                {
                     
                }
                else
                   {
                      var destNumber = `${customer.data[i].CUSTOMERS_CELLPHONE}`
                       
                       webphone_api.call(destNumber); 
                       
                   } */ 
                           
            }    
                               
        }
        else
        {
            name= `<h2 class="mdl-card__title-text">${"NO CUSTOMER"}</h>`
        }
        if(last == null)
        {
            $("#lastcalled").html("last");
        }
        else
        {
            $("#lastcalled").html(last);
        }

        $("#custInfo").html( info);
        $("#customerName").html(name);
        
        
        
        
          });        
}

function disposeCall()
{
     __ajax("databases/disposition.php", "")
        .done( function(){
        
            
            alert("disposed");
        
        
          });        
}

//INSERT INFO INTO THE DATABASE


//PHP SERVER PETITION
function __ajax(url, data)
{
    var ajax = $.ajax({
              "method": "POST",
              "url": url,
              "data": data
          })
          return ajax;
}

//BUTTON ACTIONS

//sign out


//PHONE FUNCTIONS

function manualCall()
{

}

function hangUp()
{
   //webphone_api.hangup(); 
     
    
}

function muteMic()
{

}


