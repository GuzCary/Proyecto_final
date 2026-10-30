<?php
// backend/vehiculos/eliminar_vehiculo.php
// Este archivo permite eliminar un vehiculo del inventario (admin)

// iniciamos la sesion y establecemos el protocolo JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// incluimos la conexion a la db y las funciones de encriptacion
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../seguridad/encriptar.php';

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

// Recibimos el ID encriptado del vehiculo a eliminar
$idEncriptado = $_POST['id'] ?? '';

// Nos fijamos que no este vacia
if (empty($idEncriptado)) {
    echo json_encode(["status" => "error", "message" => "ID de vehiculo no proporcionado."]);
    exit;
}

// Desencriptamos el id
$id = desencriptarId($idEncriptado);
if ($id === false || $id === '') {
    echo json_encode(["status" => "error", "message" => "ID de vehiculo invalido."]);
    exit;
}

try {
    // Iniciamos la transaccion para seguridad de datos
    $con->beginTransaction();

    // 1. Un vehiculo vendido no se puede eliminar (se perderia el historial de ventas)
    $stmtVendido = $con->prepare("SELECT idVenta FROM Ventas WHERE idVehiculo = :id");
    $stmtVendido->execute([':id' => $id]);
    if ($stmtVendido->fetch()) {
        $con->rollBack();
        echo json_encode(["status" => "error", "message" => "No se puede eliminar un vehiculo vendido."]);
        exit;
    }

    // 2. Eliminamos las relaciones en "Tiene" (categorias)
    $stmtTiene = $con->prepare("DELETE FROM Tiene WHERE idVehiculo = :id");
    $stmtTiene->execute([':id' => $id]);

    // 3. Eliminamos el vehiculo de la tabla principal
    $stmt = $con->prepare("DELETE FROM Vehiculo WHERE id = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        // 4. Eliminamos las imagenes del vehiculo guardadas en disco
        $imgDir = __DIR__ . '/../../img/';
        if (is_dir($imgDir)) {
            $archivos = scandir($imgDir);
            foreach ($archivos as $archivo) {
                if (preg_match('/^' . (int)$id . '_(\d+)\.(jpg|png|webp|gif)$/', $archivo)) {
                    @unlink($imgDir . $archivo);
                }
            }
        }

        // Confirmamos los cambios en la base de datos
        $con->commit();
        echo json_encode(["status" => "success", "message" => "Vehiculo eliminado correctamente."]);
    } else {
        $con->rollBack();
        echo json_encode(["status" => "error", "message" => "No se encontro el vehiculo."]);
    }

} catch (PDOException $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Error al eliminar el vehiculo: " . $e->getMessage()]);
}
