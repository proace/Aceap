<!DOCTYPE html>
<html>
<?php error_reporting(E_ALL); ?>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
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
    <div class="container">
        <h2>New Leads</h2>
      <div class="col-md-12">   
    <div class="col-md-6">
        <h4>New Campaign</h4>
        <form method="POST" action="../../databases/custom_functions.php" id="campaign_form">
       <!-- <form method="POST" action="http://acecare.ca/acesys/dialer/dialer/databases/custom_functions.php">-->
            <input class="form-control" type="text" placeholder="Campaign name" name="campaign_name">
            <textarea class="form-control" rows="4" placeholder="Script for agent" name="script_for_agent"></textarea>
            
            <button class="btn btn-danger" type="button" onclick="add_campaign()">Create </button>
        </form>
        <div id="campaign_msg"></div>
    </div>
    <div class="col-md-6">
        <div class="container2">
            <h4>Import leads</h4>
            <form method="POST" action="../../databases/custom_functions.php" id="import_leads_form" enctype="multipart/form-data">
				
				<div class="form-group">
					<label for="email">Campaign :</label>
					<select name="campaign_id" class="form-control"></select>
			   </div>
				<div class="form-group">
					<label for="email">City :</label>
					<select name="city" class="form-control"></select>
			   </div>
				<div class="form-group">
					<label for="email">Import :</label>
					<input type="file" class="form-control" name="import">
				 </div>
				
                <input type="hidden" name="action"  value="import_leads" >
                <button class="btn btn-danger" type="submit">Import </button>
               
               
            </form>
        </div>
        <hr>
        <div class="container2">
            <h4>Export leads</h4>
            <form method="POST" action="../../databases/custom_functions.php" id="export_leads_form" enctype="multipart/form-data">
				<div class="form-group">
					<label for="email">Campaign :</label>
					<select name="campaign_id" class="form-control"></select>
			   </div>
				<div class="form-group">
					<label for="email">City :</label>
					<select name="city" class="form-control"></select>
			   </div>
			 <input type="hidden" name="action"  value="export_leads" >  
            <button class="btn btn-danger" type="submit">Export</button>
            </form>
        </div>
    </div>
     </div>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
</body>

</html>


<script>
/*************************************Add Campaign *************************/
function add_campaign(){ 
 var campaign_name = $('input[name="campaign_name"]').val();
 var script_for_agent = $('textarea[name="script_for_agent"]').val();

if(campaign_name ==''){
	$('input[name="campaign_name"]').addClass('error');
	  $('#campaign_msg').html('<div class="alert alert-danger">Campaign Name is required.</div>');
	}else{
		$('input[name="campaign_name"]').removeClass('error');
		 $('#campaign_msg').html('');
     }
if(campaign_name !=''){
	$.ajax({
		//async:false,
		url:'../../databases/custom_functions.php',
		type:'POST',
		data:{campaign_name:campaign_name,script_for_agent:script_for_agent,action:'add_campaign'},
		success:function(result){
			if(result == 1)$('#campaign_msg').html('<div class="alert alert-success">Campaign Added Successfully.....</div>');
			$('#campaign_form input,#campaign_form textarea').val('');
			
			setTimeout(function(){
				$('#campaign_msg').html('');
			},4000);
		}
		
	});
	}
}

$(document).ready(function(){
	$.ajax({
		//async:false,
		url:'../../databases/custom_functions.php',
		type:'POST',
		data:{action :'get_campaign_and_city_list'},
		success:function(result){
			var res=JSON.parse(result); 
			$('select[name="city"]').html(res['city_list']);
			$('select[name="campaign_id"]').html(res['campaign_list']);
		}
		
	});
	
});

</script>


