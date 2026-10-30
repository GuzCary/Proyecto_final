<?php
// backend/sucursales/listar_sucursales.php
// Este archivo permite listar las sucursales de la empresa (admin)
// Ademas de los datos de la sucursal devuelve cuantos vehiculos, usuarios y
// transacciones tiene asociados, asi el admin sabe si la puede eliminar

// iniciamos la sesion y establecemos el protocolo en JSON
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

// Verificamos que la peticion sea GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(["status" => "error", "message" => "Metodo no permitido."]);
    exit;
}

try {
    // usamos subconsultas para contar los registros dependientes de cada sucursal
    $sql = "SELECT
                s.id,
                s.nombre,
                (SELECT COUNT(*) FROM Vehiculo v WHERE v.idSucursal = s.id) AS vehiculos,
                (SELECT COUNT(*) FROM Usuarios u WHERE u.idSucursal = s.id) AS usuarios,
                (SELECT COUNT(*) FROM Transacciones t WHERE t.idSucursal = s.id) AS transacciones
            FROM Sucursal s
            ORDER BY s.nombre ASC";

    $stmt = $con->prepare($sql);
    $stmt->execute();
    $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // encriptamos los ids antes de enviarlos al frontend
    $resultado = [];
    foreach ($sucursales as $sucursal) {
        $resultado[] = [
            "id"            => encriptar($sucursal['id']),
            "nombre"        => $sucursal['nombre'],
            "vehiculos"     => (int)$sucursal['vehiculos'],
            "usuarios"      => (int)$sucursal['usuarios'],
            "transacciones" => (int)$sucursal['transacciones']
        ];
    }

    echo json_encode([
        "status" => "success",
        "sucursales" => $resultado
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al obtener sucursales: " . $e->getMessage()]);
}
