<?php 
    require 'lib/nusoap.php';
    $cliente = new nusoap_client("http://localhost/wsSSP/wsSSP.php?wsdl");
?>
<html>
    <head>        
    </head>
    <body>
<!--        <h1>Consultar</h1>
        <form action="" method="post">
            <input type="text" name="userid" />
            <input type="submit" value="Buscar" />
        </form>-->
     
     
        <h1>Crear usuario</h1>
        <form action="" method="post">
                 <input type="text" name="username" />
                 <input type="password" name="userpass" />
                 <input type="submit" value="Crear" />
        </form>
        <?php
            if(isset($_POST['username'], $_POST['userpass']))
            {
                $username = $_POST["username"];
                $userpass = $_POST["userpass"];
            }
            else
            {
                $username = "";
                $userpass = "";
            }
//            echo $query = "INSERT INTO t_users(username, password) VALUES('$username', '$userpass')";
            $resp =  $cliente->call('registraUsuario', array("username"=>'', 
                "password"=>'',
                "numDispositivos"=>'',
                "sistema"=>'',
                "rol"=>'',
                "idPersona"=>''));
           echo $resp;
        ?>
    </body>
</html> 