<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>listStatus</title>
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
        <h2>List Status</h2></div>
    <div class="col-md-3">
        <div class="dropdown">
			<div class="form-group">
			    <select name="city" class=" form-control"></select>
			 </div>   
          <!--  <button class="btn btn-danger dropdown-toggle" data-toggle="dropdown" aria-expanded="false" type="button">Campaign <span class="caret"></span></button>
          
           
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
                
            </ul>-->
        </div>
        <div>
            <h4>Stats </h4></div>
        <p><strong>Not interested:</strong>		<span class="disposition" dataid="1"></span></p>
        <p><strong>Answering machine:</strong>  <span class="disposition" dataid="3"></span></p>
        <p><strong>Busy:</strong>				<span class="disposition" dataid="5"></span></p>
        <p><strong>Call backs:</strong> 		<span class="disposition" dataid="7"></span></p>
        <p><strong>Booked:</strong> 			<span class="disposition" dataid="9"></span></p>
        <p><strong>Do not call:</strong> 		<span class="disposition" dataid="2"></span></p>
        <p><strong>Wrong number:</strong> 		<span class="disposition" dataid="4"></span></p>
        <p><strong>Not in service:</strong> 	<span class="disposition" dataid="6"></span></p>
        <p><strong>No english:</strong>			<span class="disposition" dataid="8"></span></p>
        <p><strong>Last reset:</strong> NULL.</p>
    </div>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
</body>

</html>
<script>
$(document).ready(function(){
	$.ajax({
		//async:false,
		url:'../../databases/custom_functions.php',
		type:'POST',
		data:{action :'get_city_list'},
		success:function(result){
			
			$('select[name="city"]').html(result);
			
		}
		
	});
	
});

$('select[name="city"]').change(function(){
	$('.disposition').html('');
	var city = $('select[name="city"] option:selected').val();
	$.ajax({
		//async:false,
		url:'../../databases/custom_functions.php',
		type:'POST',
		data:{city:city,action :'get_disposition_count'},
		success:function(result){
			 res=JSON.parse(result);
			
			//$('.disposition[dataid="0"]').html(res[0]);
			$('.disposition[dataid="1"]').html(res[1]);
			$('.disposition[dataid="2"]').html(res[2]);
			$('.disposition[dataid="3"]').html(res[3]);
			$('.disposition[dataid="4"]').html(res[4]);
			$('.disposition[dataid="5"]').html(res[5]);
			$('.disposition[dataid="6"]').html(res[6]);
			$('.disposition[dataid="7"]').html(res[7]);
			$('.disposition[dataid="8"]').html(res[8]);
			$('.disposition[dataid="9"]').html(res[9]);
			//$('select[name="city"]').html(result);
			
		}
		
	});
});

</script>
