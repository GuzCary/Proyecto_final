<?php


require_once __DIR__ . '/../config/conexion.php';

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
