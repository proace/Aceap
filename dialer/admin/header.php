<!DOCTYPE html>
<html>
<?php error_reporting(E_ALL); ?>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads</title>
    <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css">
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
    .error{
		border:2px solid red;
	}
    .alert{
		margin-top:10px;
	}
    </style>
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