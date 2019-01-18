//starts the API, load the parameters and click events for the buttons

webphone_api.onLoaded(function ()
        {
            webphone_api.setparameter('serveraddress', 'ace.metrocentrex.net'); // yoursipdomain.com your VoIP server IP address or domain name
            webphone_api.setparameter('username', '100');      // SIP account username
            webphone_api.setparameter('password', 'Metro!100');      // SIP account password (see the "Parameters encryption" in the documentation)
            webphone_api.start();
            clickToCall.addEventListener("click", click2Call)  // destination number to call
            callInco.addEventListener("click", showModal); //shows the incoming call if hidden
            btnCall.addEventListener("click", Call); 
            btnHang.addEventListener("click", hangUp);
            btnMute.addEventListener("click", Mute);
            btnHold.addEventListener("click", Hold);
            acceptBtn.addEventListener("click", Accept);
            rejectBtn.addEventListener("click", Rejects);
            
                 
        });


 
function Call()
{
     if (webphone_api.isincall() === true)
     {
         
     }
    else
        {
            var destNumber = document.getElementById("callTo"); //takes the number from the textbox
            
            webphone_api.call(destNumber.value); 
            
        }
    
    
}

//to make calls from clickables buttons or links

function click2Call ()
{
    if (webphone_api.isincall() === true)
     {
         
     }
    else
        {
            var clicknumber = clickToCall.value;         
            webphone_api.call(clicknumber); 
            
        }
}

//hangs up the call if in any



function hangUp()
{
   webphone_api.hangup();  
    
}


//mute function

var mutestate = false;
function Mute()
{
            if (mutestate === true)
            {
                webphone_api.mute(false, 0);
                mutestate = false;
                iconMute.className = Unmuted;
            }else
            {
                webphone_api.mute(true, 0);
                mutestate = true;
                iconMute.className = Muted;
            }
            
}

//Place the cal

var holdstate = false;

function Hold()
{
    if (holdstate === true)
            {
        webphone_api.hold(false);
        holdstate = false;
                
    }else
    {
        webphone_api.hold(true);
        holdstate = true;
                
    }
            
}

//shwo the events in real time

webphone_api.onEvents(function (evt)
        {
            if (evt) { evt = evt.toString(); if (evt.indexOf('[') > 0) { evt = evt.substring(0, evt.indexOf('[')); } }

            textsts.innerHTML = evt;
            console.log('abhilasha======'+evt);  
            
            // always display the last relevant status
            evtReceivedTick = GetTickCount();
            if (evt.indexOf('STATUS') >= 0) { lastStatus = evt; }
            if (eventTimer === null)
            {
                // We receive EVENT and STATUS type messages here. We display EVENT messages only for a few seconds and after which we put back the last STATUS message (so always the relevant status is displayed)
                eventTimer = setInterval(function ()
                {
                    if (GetTickCount() - evtReceivedTick > 3000 && lastStatus.length > 0)
                    {
                        evtReceivedTick = GetTickCount();
                        if (lastStatus.indexOf('Registered') > 0) { lastStatus = lastStatus.substring(0, lastStatus.indexOf('Registered') + 10); }
                        vars.textsts.innerHTML = lastStatus.replace("STATUS,-1", "");
                       
                    }
                }, 300);
            }
});
var eventTimer = null;
var lastStatus = '';

var evtReceivedTick = 0;

function GetTickCount() // returns the current time in milliseconds
{
    var currDate = new Date();
    return currDate.getTime();
}

 webphone_api.onCallStateChange(function (event, direction, peername, peerdisplayname, line)
        {
            if (event === 'callSetup')
            {
                if (direction == 1)
                {
                    
                }
                else if (direction == 2)
                {
                    // means it's incoming call
                    
                    $(divCallID).modal('show');// display Accept, Reject buttons
                    idCaller.innerHTML = peerdisplayname + ' - ' + peername;                    
                    callInco.style.visibility = "visible";// do somethingâ€¦
            
                    
                }
            }
            
            
            // end of a call, even if it wasn't successfull
            if (event === 'callDisconnected')
            {
                $(divCallID).modal('hide') // hide Accept, Reject buttons
                idCaller.innerHTML = '';
                callInco.style.visibility = "hidden";
            }
     
            $(divCallID).on('hidden', function () {
                callInco.style.visibility = "visible";
                })
            
        });

function Accept()
        {
            $(divCallID).modal('hide');
            
            webphone_api.accept();
        }

function Reject()
{
    $(divCallID).modal('hide');
    webphone_api.reject();
}

//shows incoming call with accept - reject button
function showModal ()
{
    $(divCallID).modal('show');
}