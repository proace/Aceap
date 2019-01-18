 <?php include('../header.php'); ?>
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
    <?php include('../footer.php'); ?>