<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
</head>

<body>
<nav class="navbar navbar-fixed-bottom" id="navbarCaller" style="position: absolute; bottom:0px; margin-bottom: 0px; background-color: #dd4b39; border-color: #dd4b39;">
         <ul class="nav navbar-nav" style="padding-top: 0px; padding-bottom: 0px; line-height: 20px;">
            <li class="navbar-item">
              <p class="navbar-text" id="textStatus" style="color: whitesmoke;">
                  
              </p>
             </li>
        </ul>        
        <div class="collapse navbar-collapse" id="collapsibleNavbar">
            <form class="navbar-form form-inline navbar-right">  
            
             <ul class="nav navbar-nav">
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
    <nav class="navbar navbar-inverse">
        <div class="container">
            <div class="navbar-header"><a class="navbar-brand navbar-link" href="../monitoring/monitoring.php"><strong>Admin </strong></a>
                <button class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navcol-1"><span class="sr-only">Toggle navigation</span><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></button>
            </div>
            <div class="collapse navbar-collapse" id="navcol-1">
                <ul class="nav navbar-nav">
                    <li role="presentation"><a href="../monitoring/monitoring.php">Monitoring </a></li>
                    <li role="presentation"><a href="../leads/leads.php">New Leads</a></li>
                    <li role="presentation"><a href="../status/status.php">List Status</a></li>
                    <li role="presentation"><a href="../campaigns/campaigns.php">Campaign </a></li>
                    <li role="presentation"><a href="../users/users.php">Users </a></li>
                    <li role="presentation"><a href="../reports/reports.php">Reports </a></li>
                    <li role="presentation"><a href="../transfers/transfers.php">Transfer </a></li>
                    <li role="presentation"><a href=""><strong>Log out</strong></a></li>
                </ul>
            </div>
        </div>
    </nav>
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
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
</body>

</html>