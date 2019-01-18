 <?php include('../header.php'); ?>




    <div class="container">
        <h2>Monitoring </h2>
        <input type="search" placeholder="Search">
    </div>
    <div class="container">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Agent </th>
                        <th>Seconds of the call</th>
                        <th>Status </th>
                        <th>Bardge </th>
                        <th>Listen </th>
                        <th>Agent Only</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Agent3 </td>
                        <td>0 </td>
                        <td>Paused </td>
                        <td>
                            <button class="btn btn-danger" type="button">Bardge in</button>
                        </td>
                        <td>
                            <button class="btn btn-danger" type="button">Listen </button>
                        </td>
                        <td>
                            <button class="btn btn-danger" type="button">Agent </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="container">
<div class="row">
          <nav class="navbar navbar-fixed-bottom" id="navbarCaller" style="position: absolute; bottom:0px; margin-bottom: 0px; background-color: #dd4b39; border-color: #dd4b39;">
         <ul class="nav navbar-nav" style="padding-top: 0px; padding-bottom: 0px; line-height: 20px;">
            <li class="navbar-item">
              <p class="navbar-text" id="textStatus" style="color: whitesmoke;">
                  
              </p>
             </li>
        </ul>        
        <div class="collapse navbar-collapse" id="collapsibleNavbar">
            <form class="navbar-form form-inline navbar-right">  
            
             <ul class="nav navbar-nav" style="200px;">
                <li class="navbar-item">
                      <img src="img/phone_callin.gif" id="toggleCall" alt="Show incoming call" style="width: 50px; padding-right:10px; visibility: hidden;">       
                      <input type="text" class="form-control" id="callTo" placeholder="Type number">
                      <button class="btn btn-danger btn-call" type="button" id="startDial" title="Call" style="border-color: #dd4b39;"><i class="fa fa-phone" ></i></button>
                      <button class="btn btn-danger" type="button" id="hangUp" title="Hang up" style="border-color: #dd4b39;"><i class="fa fa-times" ></i></button>
                      <button class="btn btn-danger" type="button" id="holdBnt" title="Hold" style="border-color: #dd4b39;"><i class="fa fa-phone-square" ></i></button>
                      <button class="btn btn-danger" type="button" id="muteBtn" title="Mute" style="border-color: #dd4b39;"><i class="fa fa-microphone" id="iconMute"></i></button>
                    
            </form>
                </li>
              </ul>        
        </div>
    </nav>
    </div>  </div>
     <?php include('../footer.php'); ?>