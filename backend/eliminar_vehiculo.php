<?php
// backend/eliminar_vehiculo.php
// Este archivo permite eliminar un vehiculo del inventario.
// Solo puede ser usado por un usuario administrador.

session_start();
header("Content-Type: application/json; charset=UTF-8");

//incuimos la conexion a la db
require_once __DIR__ . '/../config/conexion.php';

// Verificamos que el usuario este logueado y sea administrador.
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Acceso denegado. Se requiere rol de administrador."]);
    exit;
}

// Verificamos que la peticion sea POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Metodo no permitido."]);
    exit;
}

// Recibimos el ID del vehiculo a eliminar.
$id = $_POST['id'] ?? '';

// nos fijamos que no este vacia
if (empty($id)) {
    echo json_encode(["status" => "error", "message" => "ID de vehiculo no proporcionado."]);
    exit;
}

try {
    // Eliminamos primero las relaciones en "Tiene"
    $stmt = $con->prepare("DELETE FROM Tiene WHERE idVehiculo = :id");
    $stmt->execute([':id' => $id]);

    // Eliminamos el vehiculo
    $stmt = $con->prepare("DELETE FROM Vehiculo WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // nos fijamos si cambio algo en la db, y si no deciemos error
    if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => "success", "message" => "Vehiculo eliminado correctamente."]);
    } else {
        echo json_encode(["status" => "error", "message" => "No se encontro el vehiculo."]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al eliminar el vehiculo: " . $e->getMessage()]);
}
