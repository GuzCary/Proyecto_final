<?php
// backend/config/conexion.php

$env = require __DIR__ . '/env_load.php';

// zona horaria de Uruguay para date(), asi las fechas y horas coinciden con las de la empresa
date_default_timezone_set('America/Montevideo');

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
    // si algo falla PDO lanza una excepcion (asi funcionan los try/catch de todo el backend)
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // usamos la hora de Uruguay tanto en PHP como en MySQL (CURDATE, NOW, etc.)
    $con->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    // respondemos en JSON para que el frontend pueda mostrar el error
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(["status" => "error", "message" => "Error de conexion con la base de datos: " . $e->getMessage()]);
    exit;
}
