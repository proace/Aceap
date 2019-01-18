<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>
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
        <h2>Reports </h2></div>
    <div class="container">
        From: <input type="date">
        To: <input type="date">
    </div>
    <div class="container">
        <div class="dropdown">
            <button class="btn btn-danger dropdown-toggle" data-toggle="dropdown" aria-expanded="false" type="button" id="agentBtn">Agent<span class="caret"></span></button>
            <ul class="dropdown-menu" role="menu">
                <li role="presentation"><a href="#">Agent3</a></li>
                <li role="presentation"><a href="#">All</a></li>
                
            </ul>
        </div>
        <input type="search" placeholder="Search">
    </div>
    <div class="container">
        <div class="table-responsive" id="reportTb">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date </th>
                        <th>Agent </th>
                        <th>Hours dialed</th>
                        <th>Bookings </th>
                        <th>Call Backs</th>
                        <th>Number of calls</th>
                        <th>Cancellations </th>
                        <th>Ratio </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>2018-03-05 </td>
                        <td>Agent3 </td>
                        <td>0 </td>
                        <td>0 </td>
                        <td>0 </td>
                        <td>0 </td>
                        <td>0 </td>
                        <td>0 </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="container">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Total </th>
                        
                        <th>Hours dialed</th>
                        <th>Bookings </th>
                        <th>Call Backs</th>
                        <th>Number of calls</th>
                        <th>Cancellations </th>
                        <th>Ratio </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td> </td>
                        
                        <td>0 </td>
                        <td>0 </td>
                        <td>0 </td>
                        <td>0 </td>
                        <td>0 </td>
                        <td>0 </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
</body>

</html>