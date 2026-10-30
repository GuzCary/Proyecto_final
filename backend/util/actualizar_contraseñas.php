<?php
// backend/util/actualizar_contraseñas.php
// Este script actualiza las contraseñas de los usuarios de prueba con hashes
// generados por password_hash() de PHP
// es para uso de desarrollo
// No tiene interfaz grafica ni botones, se accede directamente por URL

// usamos directamente el usuario de gestion de la DB, porque sin sesion iniciada
// conexion.php conecta con usr_publico, que no tiene permiso de UPDATE en Usuarios
$env = require __DIR__ . '/../config/env_load.php';
$con = new PDO(
    "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4",
    $env['DB_USER_GESTION'],
    $env['DB_PASS_GESTION']
);

$usuarios = [
    'admin' => 'admin123',
    'vendedor' => 'vendedor123',
    'limpieza' => 'limpieza123'
];

foreach ($usuarios as $usuario => $pass) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $con->prepare("UPDATE Usuarios SET contraseña = :hash WHERE usuario = :usuario");
    $stmt->execute([':hash' => $hash, ':usuario' => $usuario]);
    echo "Actualizado: $usuario<br>";
}

echo "<br>Contraseñas actualizadas correctamente.";
