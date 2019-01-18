<?php

class DatabaseConnection 
{
    function dbConnection()
    {
        return $con = mysqli_connect('localhost','acecare7_support','P?HRg9H11=V}','acecare7_live_chat','3306');
    }
}

