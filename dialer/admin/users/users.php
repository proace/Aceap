 <?php include('../header.php'); ?>
    <div class="container">
        <h2>Users </h2></div>
    <div class="col-md-6">
        <div>
            <h4>List </h4>
            <input type="search" placeholder="Search....." id="search_agents" class="form-control"><br>
             <div class="table-responsive" >
                <table class="table table-bordered" id="agent_list">
					<thead>
						<tr>
						<th>Id</th>
						<th>Username</th>
						<th>Firstname</th>
						<th>Lastname</th>
						<th>Phone</th>
						<th>Action</th>
						</tr>
                   </thead>
                 <tbody></tbody>
                </table>
            </div>
            <br>
            
            
          <!--  <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Username </th>
                            <th>Reset Password</th>
                            <th>Delete User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Agent3</td>
                            <td>
                                <button class="btn btn-danger" type="button">Reset </button>
                            </td>
                            <td>
                                <button class="btn btn-danger" type="button">Delete </button>
                            </td>
                        </tr>
                        
                    </tbody>
                </table>
            </div>-->
        </div>
    </div>
    <div class="col-md-6">
        <h4>Create new user</h4>
         <form method="POST" action="../../databases/custom_functions.php" id="assign_campaign_form" >
           <!-- <input class="form-control" type="text" required="" placeholder="User name">
            <input class="form-control" type="text" placeholder="Password" minlength="8">
            <input class="form-control" type="text" required="" placeholder="Extension" maxlength="3" minlength="3" inputmode="numeric">-->
            <div class="dropdown">
			<div class="form-group">
					<label for="email">Agent Name :<span id="agentname"></span></label>
					
					<input type="hidden" name="agent_id" value="" >
 			   </div>
			  <div class="form-group">
					<label for="email">Campaign :</label>
					<select name="campaign_id" class="form-control"></select>
			   </div>
				<div class="form-group">
					<label for="email">City :</label>
					<select name="city" class="form-control"></select>
			   </div>
			
			 <input type="hidden" name="action"  value="assign_campaign_to_agent" >  
			 
               <!-- <button class="btn btn-danger dropdown-toggle" data-toggle="dropdown" aria-expanded="false" type="button" id="campaignBtn">Campaign <span class="caret"></span></button>
                <ul class="dropdown-menu" role="menu">
                
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
             <button class="btn btn-danger" type="button" id="add_campaign" >Add </button>
        </form>
       
    </div>
     <?php include('../footer.php'); ?>
<script>
$(document).ready(function(){
  $("#search_agents").on("keyup", function() {
    var value = $(this).val().toLowerCase();
    $("#agent_list tbody tr").filter(function() {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
  });
});	
$(document).ready(function(){
	$.ajax({
		//async:false,
		url:'../../databases/custom_functions.php',
		type:'POST',
		data:{action :'get_telemarketer_agent_list'},
		success:function(result){
			$('#agent_list tbody').html(result);
		}
		
	});
	
});
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
	/***************************/
	$('#add_campaign').click(function(){
	     var campaign_id = $('select[name="campaign_id"] option:selected').val();
	     var city = $('select[name="city"] option:selected').val();
	     var agent_id = $('#agent_id').val();
	     if(campaign_id !='' && city !='' && agent_id !='')$('#assign_campaign_form').submit();
   });
	
	
});
function assign_campaign(rowid){
	var firstname = $('tr[rowid="'+rowid+'"] .firstname').html();
	var lastname = $('tr[rowid="'+rowid+'"] .lastname').html();
	$('#agentname').html(firstname+' '+lastname);
	$('#agent_id').val(id);
}


</script>
