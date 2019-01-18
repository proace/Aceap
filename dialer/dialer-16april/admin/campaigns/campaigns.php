<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign</title>
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
        <h2>Campaigns </h2></div>
    <div class="col-md-6">
        <div class="dropdown">
            <button class="btn btn-danger dropdown-toggle" data-toggle="dropdown" aria-expanded="false" type="button">Campaign <span class="caret"></span></button>
            <ul class="dropdown-menu" role="menu">
                <li role="presentation"><a href="#">All</a></li>
                <li role="presentation"><a href="#">Abbotsford</a></li>
                <li role="presentation"><a href="#">Aldergrove</a></li>
                <li role="presentation"><a href="#">Burnaby</a></li>
                <li role="presentation"><a href="#">Cloverdale</a></li>
                <li role="presentation"><a href="#">Coquitlam</a></li>
                <li role="presentation"><a href="#">Delta</a></li>
                <li role="presentation"><a href="#">Ladner</a></li>
                <li role="presentation"><a href="#">Langley</a></li>
                <li role="presentation"><a href="#">Maple Rdge</a></li>
                <li role="presentation"><a href="#">Pitt Meadows</a></li>
                <li role="presentation"><a href="#">Port Coquitlam</a></li>
                <li role="presentation"><a href="#">Port Moody</a></li>
                <li role="presentation"><a href="#">Richmond</a></li>
                <li role="presentation"><a href="#">Richmond and new west</a></li>
                <li role="presentation"><a href="#">Surrey</a></li>
                
            </ul>
        </div>
        <input type="search" placeholder="Search">
        <div>
            <h4>Users </h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Username </th>
                            <th>Option </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Agent3 </td>
                            <td>
                                <button class="btn btn-danger" type="button">Check </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <h4>Leads left: 0.</h4>
        <h4>Last reset: NULL.</h4></div>
    <button class="btn btn-danger" type="button">Reset </button>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
</body>

</html>