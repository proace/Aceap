<?php

class DatabaseConnection 
{
    function dbConnection()
    {
        return $con = mysqli_connect('localhost','hvacproz_acesys','Iw+&Sm]=otV7','hvacproz_livechat','3306');
        // return $con = mysqli_connect('localhost','acecare7_support','P?HRg9H11=V}','acecare7_live_chat','3306');
    }
}

