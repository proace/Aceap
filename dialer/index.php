<?php
  session_start();
  include 'databases/config.php';
  include 'databases/showCustomer.php';
 //echo '<pre>';print_r($_SESSION);echo '</pre>';
 
  if(!isset($_SESSION['user']))
  {
    
    echo '<script> window.location="login/index.php"; </script>';    
    exit();
  } 
  else
  {
    //echo '<script> window.location="index.php"; </script>'; 
  }

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Dialer</title>
    <link rel="stylesheet" href="css/material.min.css">
    <script src="js/material.min.js"></script>
    
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
      rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" href="https://code.getmdl.io/1.3.0/material.indigo-red.min.css" />
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Roboto:300,400,500,700" type="text/css">
    <link rel="stylesheet" href="css/custom.css" type="text/css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script>
      var extension = '<?php echo $_SESSION["extension"];?>';
      var destNumber = '<?php echo $_COOKIE["cust_cell"];?>';
      var altHome = <?php echo $_COOKIE["cust_home"];?>;
   
    </script>
     
<!--
      
    <script src= "../../DB/dialer/webphone_api.js?jscodeversion=<?php echo time();?>"></script>
    <script src="../../DB/dialer/js/functions.js?js=<?php echo time();?>"></script>
-->

    
</head>
<body class="mdl-color--grey-100">

    <div class="modal fade" id="icoming_call_layout" role="dialog">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <div class="modal-header">          
          <h4 class="modal-title" align="center">Incoming Call</h4>
        </div>
        <div class="modal-body" align="center">
          <p id="callerIDText"></p>
        </div>
        <div class="modal-body" align="center">
          <button type="button" class="btn btn-success" id="btnAccept">Accept</button>
          <button type="button" class="btn btn-danger" id="btnReject">Reject</button>
        </div>
      </div>
    </div>
  </div>

  <div class="mdl-layout mdl-js-layout mdl-layout--fixed-header">
    <header class="mdl-layout__header">
      <div class="mdl-textfield mdl-js-textfield" style="position:absolute; right: 250px; top:-15px;">
        <input class="mdl-textfield__input" type="text" id="callTo" pattern="-?[0-9]*(\.[0-9]+)?">
          <label class="mdl-textfield__label" style="color:gray;" for="fname">Phone number</label>
          <span class="mdl-textfield__error">Input is not a number!</span>
      </div>
      <button id="startDial" class="mdl-button mdl-js-button mdl-button--icon" style="position:absolute; right: 200px;">            
        <i class="material-icons">call</i>
      </button> 
      <button id="muteBtn" class="mdl-button mdl-js-button mdl-button--icon" style="position:absolute; right: 160px;">            
        <i  id="iconMute" class="material-icons">mic_none</i>
      </button> 
      <button class="mdl-button mdl-js-button mdl-button--icon" style="position:absolute; right: 120px;">            
        <i id="holdBnt" class="material-icons">phone_paused</i>
      </button>           
        <label id="agentTag" style="position:absolute; right: 50px; top: 4px; padding:0px 8px 3px">Options</label>          
      <button id="demo-menu-lower-right" class="mdl-button mdl-js-button mdl-button--icon" style="position:absolute; right: 25px;">            
        <i class="material-icons">keyboard_arrow_down</i>
      </button>  
      <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect" for="demo-menu-lower-right">
          <li class="mdl-menu__item">Go break</li>
          <li class="mdl-menu__item"><a href="databases/logout.php" style="text-decoration: none">Sign out</a></li>
          </ul>       
      <div class="mdl-layout__header-row ">
                    <!-- Title -->
        <img src="img/acelogo.png"></img>                    
      </div>
                        <!-- Tabs -->
      <div class="mdl-layout__tab-bar mdl-js-ripple-effect">
        <a href="#scroll-tab-1" class="mdl-layout__tab is-active">Ace list</a>
        <a href="#scroll-tab-2" class="mdl-layout__tab">Call Backs</a>
        <a href="#scroll-tab-3" class="mdl-layout__tab"></a>
                    
      </div>
      <button class="mdl-button mdl-js-button mdl-button--fab mdl-js-ripple-effect mdl-button--colored mdl-shadow--6dp mdl-color--accent" id="add">
        <i class="material-icons">pause</i>
        <span class="visuallyhidden">Add</span>
      </button>
    </header>
    
    <main class="mdl-layout__content">
      <section class="mdl-layout__tab-panel is-active" id="scroll-tab-1">
        <div class="page-content">
                          <!-- Your content goes here -->
          <div class="center-content mdl-layout">
            <i class="material-icons">record_voice_over</i>
            <p class="section-grid pitch">Hi My Name is (agentName) and I am calling from pro ace Heating. we just want to remind you that is time to service your Furnace . Are you still at .............. when would you like it done ?</p>                      
          </div>
          <div class="section-grid mdl-grid">
            <div class="mdl-cell mdl-cell--4-col">
              <div class="mdl-card mdl-shadow--4dp">
                <div class="demo-card-wide mdl-card">
                  <div class="mdl-card__title" id="customerName">
                    <h2 class="mdl-card__title-text"><?php echo $_COOKIE["cust_name"];?></h2>
                  </div>
                  <div class="mdl-card__supporting-text" id="custInfo">
                    <p><b>City:</b><?php echo $_COOKIE["cust_city"];?></p>
                    <p><b>Zipcode:</b><?php echo $_COOKIE["cust_zip"];?></p>
                    <p><b>Cellphone:</b><?php echo $_COOKIE["cust_cell"];?></p>
                    <p><b>Homephone:</b><?php echo $_COOKIE["cust_home"];?></p>
                    <p><b>Email:</b><?php echo $_COOKIE["cust_email"];?></p>
                  </div>
                  <div class="mdl-card__actions mdl-card--border">
                    <a class="mdl-button mdl-button--colored mdl-js-button mdl-js-ripple-effect" value='<?php echo $_COOKIE["cust_cell"];?>' id='callcellphone'>
                      Call Cellphone
                    </a>
                    <a class="mdl-button mdl-button--colored mdl-js-button mdl-js-ripple-effect"  value='<?php echo $_COOKIE["cust_home"];?>'  id='callhomephone'>
                      Call Homephone
                      </a>
                  </div>                  
                </div>
              </div>
            </div>
            <div class="mdl-cell mdl-cell--4-col">
              <div class="mdl-card  mdl-cell--12-col mdl-shadow--4dp">
                <div class="mdl-card__tittle">
                  <h2 class="mdl-card__title-text" >Disposition</h2>
                </div>
                <form method="POST" action="databases/agent_custom_functions.php" id="disposition_form" >
                <div class="card--supporting-text mdl-cell--12-col">                                                     
                  <div class="mdl-grid">
                    <div class="mdl-cell mdl-cell--6-col">
					  <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" for="Not interested">
                        <input  class="mdl-radio__button" id="Not interested" name="disposition" type="radio" value="1">
                        <span class="mdl-radio__label">Not interested</span>
                      </label>
                      <br>
                      <br>
                      <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" for="Ans machine">
                        <input class="mdl-radio__button" id="Ans machine" name="disposition" type="radio" value="3">
                        <span class="mdl-radio__label">Ans machine</span>
                      </label>
                      <br>
                      <br>
                      <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" for="Busy">
                        <input class="mdl-radio__button" id="Busy" name="disposition" type="radio" value="5">
                        <span class="mdl-radio__label">Busy</span>
                      </label>
                      <br>
                      <br>
                      <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" for="Call back">
                        <input class="mdl-radio__button" id="Call back" name="disposition" type="radio" value="7">
                        <span class="mdl-radio__label">Call back</span>
                      </label>
                      <br>
                      <br>
                      <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" for="Booked">
                        <input class="mdl-radio__button" id="Booked" name="disposition" type="radio" value="9">
                        <span class="mdl-radio__label">Booked</span>
                      </label>                      
                    </div>
                    <div class="mdl-cell mdl-cell--6-col">
                      <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" for="Do not call">
                        <input class="mdl-radio__button" id="Do not call" name="disposition" type="radio" value="2">
                        <span class="mdl-radio__label">Do not call</span>
                      </label>
                      <br>
                      <br>
                      <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" for="Wrg number">
                        <input class="mdl-radio__button" id="Wrg number" name="disposition" type="radio" value="4">
                          <span class="mdl-radio__label">Wrg number</span>
                      </label>
                      <br>
                      <br>
                      <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" for="Not in service">
                        <input class="mdl-radio__button" id="Not in service" name="disposition" type="radio" value="6">
                          <span class="mdl-radio__label">Not in service</span>
                      </label>
                      <br>
                      <br>
                      <label class="mdl-radio mdl-js-radio mdl-js-ripple-effect" for="Not english">
                        <input class="mdl-radio__button" id="No english" name="disposition" type="radio" value="8">
                        <span class="mdl-radio__label">No english</span>
                      </label>
                    </div>                                                    
                  </div>                                                                                                   
                </div>  
                <input type="hidden" name="customer_id"  value="<?php echo $_COOKIE["cust_id"];?>" >
                <input type="hidden" name="action"  value="update_customer" >
                <div class="mdl-card__actions mdl-card--border">
                  <a target="_blank" href="http://acecare.ca/acesys/index.php/pages/main" class="mdl-button mdl-button--colored mdl-js-button mdl-js-ripple-effect">Book on ACE</a>
                  <a id="disposeBtn" name="disposeBtn" class="mdl-button mdl-button--colored mdl-js-button mdl-js-ripple-effect">Next Call</a>
                </div>  
                </form>                                         
              </div>                                                  
            </div>                  
            <div class="mdl-cell mdl-cell--4-col">
              <div class="demo-card-event mdl-card mdl-shadow--2dp" style="background: #3E4EB8;">
                <div class="mdl-card__title mdl-card--expand" style="color: #fff" id="lastcalled">                  
                  <h4>Last called:</b><br><?php echo  $_COOKIE["cust_last"];?> <br>
                  <br>
                  Last work:<br>
                  
                  </h4>
                </div>
                <div class="mdl-card__actions mdl-card--border">
                  <a class="mdl-button mdl-button--colored mdl-js-button mdl-js-ripple-effect" style="color: #fff">
                    Show more
                  </a>
                
                </div>
              </div>
          </div>  
        </div> 
      </section>
      <section class="mdl-layout__tab-panel" id="scroll-tab-2">
        <div class="page-content"><!-- Your content goes here -->  
          <div class="mdl-grid">
            <div class="mdl-cell mdl-cell--6-col">
            From: <input type="date" name="from">To: </input><input type="date" name="from"></input>
              <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  Options
                </button>
              <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <a class="dropdown-item" href="#">Today</a>
                <a class="dropdown-item" href="#">Due</a>
                <a class="dropdown-item" href="#">Missed</a>
                <a class="dropdown-item" href="#">Future</a>
                <a class="dropdown-item" href="#">Cancelation</a>
                <a class="dropdown-item" href="#">Reminders</a>
              </div>
            </div> 
            
            <table class="mdl-data-table mdl-js-data-table mdl-shadow--2dp">
              <thead>
                <tr>
                  <th class="mdl-data-table__cell--non-numeric">First Name</th>
                  <th>Last Name</th>
                  <th>City</th>
                  <th>Last Called</th>
                  <th>Last Job Done</th>
                  <th>Last Job Type</th>
                  <th>Cellphone</th>
                  <th>Homephone</th>
                  <th>Disposition</th>
                  <th>Option</th>
                </tr>
              </thead>
              
            </table>     
          </div>
          <div class="mdl-cell mdl-cell--4-col"></div>
          <div class="mdl-cell mdl-cell--2-col"></div>
        </div>      
        
        </div>
      </section>
      <section class="mdl-layout__tab-panel" id="scroll-tab-3">
        <div class="page-content"><!-- Your content goes here --></div>
      </section>
    </main>
 
 <div id='textStatus' ></div>
  </div>         
  
  <a id="view-source" class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-color--accent mdl-color-text--accent-contrast">HUNG UP</a>
  <!-- scripts -->  
  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>   
    
</body>
</html>

<script>

$(document).ready(function(){
	$('#disposeBtn').click(function(){
	 var disposition=	$('input[name="disposition"]:checked').val();
		
	if(disposition !=''){
		$('#disposition_form').submit();
	
  }
	
 });
});

</script>
