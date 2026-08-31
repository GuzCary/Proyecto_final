<?php
// backend/eliminar_categoria.php
// Este archivo permite eliminar una categoria
// Solo puede ser usado por un usuario administrador

session_start();
header("Content-Type: application/json; charset=UTF-8");

// incluimos la conexion a la db y las funciones de encriptacion
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/encriptar.php';

// Verificamos que el usuario este logueado y sea administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Acceso denegado. Se requiere rol de administrador."]);
    exit;
}

// Verificamos que la peticion sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Metodo no permitido."]);
    exit;
}

// Recibimos el ID encriptado de la categoria a eliminar
$idEncriptado = $_POST['id_encriptado'] ?? '';

// nos fijamos que el id exista
if (empty($idEncriptado)) {
    echo json_encode(["status" => "error", "message" => "ID de categoria no proporcionado."]);
    exit;
}

$id = desencriptar($idEncriptado);
if ($id === false || $id === '') {
    echo json_encode(["status" => "error", "message" => "ID de categoria invalido."]);
    exit;
}

try {
    // Eliminamos primero las relaciones en "Tiene"
    $stmt = $con->prepare("DELETE FROM Tiene WHERE idCategoria = :id");
    $stmt->execute([':id' => $id]);

    // Eliminamos la categoria.
    $stmt = $con->prepare("DELETE FROM Categoria WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // nos fijamos si cambio algo en la db, y si no deciemos error
    if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => "success", "message" => "Categoria eliminada correctamente."]);
    } else {
        echo json_encode(["status" => "error", "message" => "No se encontro la categoria."]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al eliminar la categoria: " . $e->getMessage()]);
}
