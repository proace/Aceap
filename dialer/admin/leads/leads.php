<?php include('../header.php'); ?>


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
   <?php include('../footer.php'); ?>


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


