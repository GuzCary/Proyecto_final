<?php
// backend/categorias.php
// Este archivo permite gestionar las categorias de vehiculos
// - GET: devuelve todas las categorias (publico)
// - POST: crea una nueva categoria (administradores)

session_start();
header("Content-Type: application/json; charset=UTF-8");

// incluimos la conexion a la db
require_once __DIR__ . '/../config/conexion.php';

// Si la peticion es GET, devolvemos todas las categorias
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // devolvemos en orden alfabetico
        $stmt = $con->prepare("SELECT id, nombre FROM Categoria ORDER BY nombre ASC");
        $stmt->execute();
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "status" => "success",
            "categorias" => $categorias
        ]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Error al obtener categorias: " . $e->getMessage()]);
    }
    exit;
}

// Si la peticion es POST, verificamos que sea administrador
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
        echo json_encode(["status" => "error", "message" => "Acceso denegado. Se requiere rol de administrador."]);
        exit;
    }

    $nombre = trim($_POST['nombre'] ?? '');
     // nos fijamos que tenga nombre
    if (empty($nombre)) {
        echo json_encode(["status" => "error", "message" => "El nombre de la categoria es obligatorio."]);
        exit;
    }

    try {
        $stmt = $con->prepare("INSERT INTO Categoria (nombre) VALUES (:nombre)");
        $stmt->execute([':nombre' => $nombre]);

        echo json_encode(["status" => "success", "message" => "Categoria agregada correctamente."]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Error al guardar la categoria: " . $e->getMessage()]);
    }
    exit;
}

// si llega otro metodo, respondemos error
echo json_encode(["status" => "error", "message" => "Metodo no permitido."]);
