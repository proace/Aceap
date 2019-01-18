<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfers</title>
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
        <h2>Transfer List</h2></div>
    <div class="container">
        <div class="col-md-6">
            From: <input type="date">
            To: <input type="date">
        </div>
    </div>
    <div class="container">
        <h4>Check the lists</h4>
        <div class="checkbox">
            <label>
                <input type="checkbox">Reminders</label>
        </div>
        <div class="checkbox">
            <label>
                <input type="checkbox">Call backs</label>
        </div>
        <div class="checkbox">
            <label>
                <input type="checkbox">Past call backs</label>
        </div>
        <div class="checkbox">
            <label>
                <input type="checkbox">Future call backs</label>
        </div>
        <div class="col-md-3">
            <p>From </p>
            <div class="dropdown">
                <button class="btn btn-danger dropdown-toggle" data-toggle="dropdown" aria-expanded="false" type="button">Select Agent<span class="caret"></span></button>
                <ul class="dropdown-menu" role="menu">
                    <li role="presentation"><a href="#">Agent3</a></li>
                    
                </ul>
            </div>
            <p>To </p>
            <div class="dropdown">
                <button class="btn btn-danger dropdown-toggle" data-toggle="dropdown" aria-expanded="false" type="button">Select Agent<span class="caret"></span></button>
                <ul class="dropdown-menu" role="menu">
                    <li role="presentation"><a href="#">Agent3</a></li>
                    
                </ul>
            </div>
            <button class="btn btn-danger" type="button">Transfer </button>
        </div>
    </div>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
</body>

</html>