<?php
// backend/marcas/listar_marcas.php
// este archivo lista las marcas de los usuarios (admin)

// iniciamos la sesion y establecemos el protocolo en JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// incluimos la conexion a la DB
require_once __DIR__ . '/../config/conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Acceso denegado. Se requiere rol de administrador."]);
    exit;
}

// si la peticion es GET enviamos todas las marcas 
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT rm.id, u.usuario, rm.hora, rm.direccion 
            FROM RegistroMarca rm
            INNER JOIN Usuarios u ON rm.idUsuario = u.id
            ORDER BY rm.hora DESC";
            
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $marcas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "marcas" => $marcas
    ]);
    exit;
}



