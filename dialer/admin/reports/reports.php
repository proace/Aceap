 <?php include('../header.php'); ?>
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
    <?php include('../footer.php'); ?>