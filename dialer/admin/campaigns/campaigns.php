 <?php include('../header.php'); ?>
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
     <?php include('../footer.php'); ?>