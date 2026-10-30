<?php
// backend/config/conexion.php

// --- Manejo de errores ---
// Los avisos de PHP no se muestran en la respuesta (romperian el JSON y el frontend no
// podria leerla); quedan en el log de errores del servidor.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Si ocurre un error grave que corta el script (por ejemplo una funcion que no existe),
// igual respondemos un JSON con el problema en vez de una pagina de error en HTML.
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            header("Content-Type: application/json; charset=UTF-8");
        }
        echo json_encode([
            "status" => "error",
            "message" => "Error interno del servidor: " . $error['message'] . " (" . basename($error['file']) . ":" . $error['line'] . ")"
        ]);
    }
});

// Lo mismo para cualquier excepcion que no haya sido atrapada por un try/catch
set_exception_handler(function ($e) {
    if (!headers_sent()) {
        header("Content-Type: application/json; charset=UTF-8");
    }
    echo json_encode(["status" => "error", "message" => "Error interno del servidor: " . $e->getMessage()]);
});

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
