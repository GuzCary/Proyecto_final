<?php
// backend/ventas/listar_ventas.php
// Lista el historial de ventas realizadas (vendedores, admin)

// iniciamos la sesion y establecemos el protocolo en JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// Incluimos conexión y las funciones de  encriptación
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../seguridad/encriptar.php';

// Verificamos que sea GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

// Verificamos rol
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] !== 'admin' && $_SESSION['usuario_rol'] !== 'user')) {
    echo json_encode(["status" => "error", "message" => "Acceso denegado."]);
    exit;
}

try {
    // Obtenemos las ventas junto con los datos del vehículo y el nombre del vendedor
    $sql = "SELECT
                v.idVenta,
                v.fecha,
                veh.id AS idVehiculo,
                veh.marca,
                veh.modelo,
                veh.patente,
                veh.precio,
                u.usuario AS vendedor
            FROM Ventas v
            INNER JOIN Vehiculo veh ON v.idVehiculo = veh.id
            INNER JOIN Usuarios u ON v.idFuncionario = u.id
            ORDER BY v.fecha DESC, v.idVenta DESC";

    $stmt = $con->prepare($sql);
    $stmt->execute();
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ventas que este usuario registro hace menos de 60 segundos (se pueden eliminar)
    // y de paso limpiamos de la sesion las que ya vencieron
    $recientes = $_SESSION['ventas_recientes'] ?? [];
    foreach ($recientes as $idV => $momento) {
        if (time() - $momento >= 60) {
            unset($_SESSION['ventas_recientes'][$idV]);
        }
    }

    // Encriptamos los IDs antes de enviarlos al frontend
    $resultado = [];
    foreach ($ventas as $fila) {
        $segundos = 0;
        if (isset($_SESSION['ventas_recientes'][$fila['idVenta']])) {
            $segundos = 60 - (time() - $_SESSION['ventas_recientes'][$fila['idVenta']]);
        }

        $resultado[] = [
            "id_venta"    => encriptar($fila['idVenta']),
            "fecha"       => $fila['fecha'],
            "marca"       => $fila['marca'],
            "modelo"      => $fila['modelo'],
            "patente"     => $fila['patente'],
            "precio"      => (float)$fila['precio'],
            "vendedor"    => $fila['vendedor'],
            // segundos que quedan para poder eliminarla (0 = ya no se puede)
            "segundosParaEliminar" => max(0, $segundos)
        ];
    }

    echo json_encode([
        "status" => "success",
        "ventas" => $resultado
    ]);
    exit;

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al obtener ventas: " . $e->getMessage()]);
    exit;
}
