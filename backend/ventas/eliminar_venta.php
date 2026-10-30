<?php
// backend/ventas/eliminar_venta.php
// Permite eliminar una venta SOLO durante el primer minuto despues de registrarla
// y solo al mismo usuario que la registro (vendedores, admin).
// El momento de la venta se guarda en la sesion al registrarla (registrar_venta.php),
// porque la tabla Ventas guarda la fecha pero no la hora.

// iniciamos la sesion y establecemos el protocolo en JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// Incluimos conexión a la db y funciones de encriptacion
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../seguridad/encriptar.php';

// segundos durante los que se puede eliminar una venta
const VENTANA_ELIMINAR = 60;

// Verificamos que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

// Verificamos que esté logueado y sea admin o vendedor (user)
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] !== 'admin' && $_SESSION['usuario_rol'] !== 'user')) {
    echo json_encode(["status" => "error", "message" => "Acceso denegado. Se requiere rol de vendedor o administrador."]);
    exit;
}

$idVenta = desencriptarId($_POST['id_venta'] ?? '');
if ($idVenta === false) {
    echo json_encode(["status" => "error", "message" => "ID de venta inválido."]);
    exit;
}

// tiene que ser una venta registrada en esta sesion hace menos de un minuto
$momento = $_SESSION['ventas_recientes'][$idVenta] ?? null;
if ($momento === null || time() - $momento >= VENTANA_ELIMINAR) {
    unset($_SESSION['ventas_recientes'][$idVenta]);
    echo json_encode(["status" => "error", "message" => "Solo quien registró la venta puede eliminarla, y únicamente durante el primer minuto."]);
    exit;
}

try {
    $con->beginTransaction();

    // verificamos que la venta exista y sea de este usuario
    $stmt = $con->prepare("SELECT veh.marca, veh.modelo
                           FROM Ventas v
                           INNER JOIN Vehiculo veh ON v.idVehiculo = veh.id
                           WHERE v.idVenta = :idVenta AND v.idFuncionario = :idFuncionario
                           FOR UPDATE");
    $stmt->execute([':idVenta' => $idVenta, ':idFuncionario' => $_SESSION['usuario_id']]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venta) {
        $con->rollBack();
        unset($_SESSION['ventas_recientes'][$idVenta]);
        echo json_encode(["status" => "error", "message" => "La venta no existe."]);
        exit;
    }

    $stmtDel = $con->prepare("DELETE FROM Ventas WHERE idVenta = :idVenta");
    $stmtDel->execute([':idVenta' => $idVenta]);

    $con->commit();
    unset($_SESSION['ventas_recientes'][$idVenta]);

    echo json_encode([
        "status" => "success",
        "message" => "Venta de " . $venta['marca'] . " " . $venta['modelo'] . " eliminada. El vehículo vuelve a estar disponible."
    ]);

} catch (PDOException $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Error al eliminar la venta: " . $e->getMessage()]);
}
