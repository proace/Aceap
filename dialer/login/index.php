
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Dialer login</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://code.getmdl.io/1.3.0/material.indigo-red.min.css" />
    <script defer src="https://code.getmdl.io/1.3.0/material.min.js"></script>
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Roboto:300,400,500,700" type="text/css">    
    <link rel="stylesheet" href="../css/custom.css" type="text/css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    
</head>
<body class="mdl-color--grey-100">
    
    <div class="mdl-grid">
        <div class="mdl-cell mdl-cell--4-col"></div>
        <div class="mdl-cell mdl-cell--4-col">
            <div class="demo-card-square mdl-card mdl-shadow--4dp" >
                <div class="mdl-card__title mdl-card--expand" style="background: #3f51b5; color: #fff;">
                    <h2 class="mdl-card__title-text"><img src="../img/acelogo.png"</h2>
                </div>
                <div class="mdl-card__supporting-text">
                <form method="post" action="../databases/validate.php">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input class="mdl-textfield__input" type="text" id="usernameTxt" name="username" autocomplete="off">
                        <label class="mdl-textfield__label" for="usernameTxt">Username</label>
                    </div>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input class="mdl-textfield__input" type="text" id="passTxt" name="password" autocomplete="off">
                        <label class="mdl-textfield__label" for="passTxt">Password</label>
                    </div>                                       
                    <input type="submit" id="loginBtn" name="loginBtn" class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent">
                        
                    </input>
                </form>
                </div>
                
            </div>
        </div>
        <div class="mdl-cell mdl-cell--4-col"></div>
    </div>

    
    
</body>
</html>