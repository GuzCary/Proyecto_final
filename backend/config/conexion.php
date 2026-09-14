<?php
// backend/config/conexion.php

$env = require __DIR__ . '/env_load.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbUser = $env['DB_USER_PUBLIC'];
$dbPass = $env['DB_PASS_PUBLIC'];

if (isset($_SESSION['usuario_id'])) {
    $dbUser = $env['DB_USER_GESTION'];
    $dbPass = $env['DB_PASS_GESTION'];
}




//tomamos los datos devueltos por env_load y creamos la conexion a la db
try {
    $con = new PDO(
        "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4",
        $dbUser,
        $dbPass
    );
} catch (PDOException $e) {
    die("error de conexión: " . $e->getMessage());
}












