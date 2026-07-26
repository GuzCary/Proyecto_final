<?php

$env = require __DIR__ . '/env_load.php';

//tomamos los datos devueltos por env_load y creamos la conexion a la db
try {
    $con = new PDO(
        "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4",$env['DB_USER'],$env['DB_PASS']
    );
    
 

} catch (PDOException $e) {
    die("error de conexión: " . $e->getMessage());
}





