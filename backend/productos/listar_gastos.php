<?php
// backend/productos/listar_gastos.php
// Devuelve el historial de gastos de productos de limpieza: quién usó qué producto y cuánto (admin)
// (la tabla Gasta no guarda fecha, por eso se ordena del más nuevo al más viejo por su id)

// iniciamos la sesion y establecemos el protocolo en JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// Incluimos la conexión
require_once __DIR__ . '/../config/conexion.php';

// Verificamos que la petición sea GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

// Solo el administrador puede ver el historial de gastos
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Acceso denegado. Se requiere rol de administrador."]);
    exit;
}

try {
    $sql = "SELECT
                g.cantidad,
                u.usuario AS empleado,
                p.nombre AS producto
            FROM Gasta g
            INNER JOIN Usuarios u ON g.idLimpieza = u.id
            INNER JOIN Productos p ON g.idProducto = p.id
            ORDER BY g.idGasto DESC";

    $stmt = $con->prepare($sql);
    $stmt->execute();
    $gastos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($gastos as &$gasto) {
        $gasto['cantidad'] = (int)$gasto['cantidad'];
    }
    unset($gasto);

    echo json_encode([
        "status" => "success",
        "gastos" => $gastos
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al obtener los gastos: " . $e->getMessage()]);
}
