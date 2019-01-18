<?php
    session_start();

    include "config.php";
    if(isset($_POST["loginBtn"]))
    {
        $user = $_POST["username"];
        $pass = $_POST["password"];
        $queryResult = $pdo->query("SELECT * FROM users_login WHERE login_id='$user' AND login_password= md5('$pass')");
        if($row = $queryResult->fetch())
        {
            $data["data"][] = $row;
            $_SESSION["user"] = $row["login_id"];
            $_SESSION["campaign"] = $row["login_extension"];
            $_SESSION["extension"] = $row["login_extension"];
            $_SESSION["rol"] = $row["login_rol"];
            $_SESSION["id"] = $row["id"];
            if($_SESSION["rol"] == "agent")
            {
                echo '<script> window.location="../"; </script>';
            }
            else
            {
                echo '<script> window.location="../admin/monitoring/monitoring.php"; </script>';
            }
                                   
        }
        else
        {
                       
            echo '<script> window.location="../login"; </script>';
            
        }
        
    }
?>
