<?php
// backend/productos/listar_productos.php
// Devuelve la lista de productos con su id encriptado, nombre y stock (limpieza o admin)

// iniciamos la sesion y establecemos el protocolo en JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// Incluimos la conexión y encriptación
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../seguridad/encriptar.php';

// Verificamos que la petición sea GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

// Verificamos que esté logueado y sea de limpieza (o admin)
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] !== 'limp' && $_SESSION['usuario_rol'] !== 'admin')) {
    echo json_encode(["status" => "error", "message" => "Acceso denegado. Se requiere rol de limpieza o administrador."]);
    exit;
}

try {
    // Obtenemos los productos ordenados alfabéticamente
    $stmt = $con->prepare("SELECT id, nombre, stock FROM Productos ORDER BY nombre ASC");
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Encriptamos los IDs
    $resultado = [];
    foreach ($productos as $producto) {
        $resultado[] = [
            "id" => encriptar($producto['id']),
            "nombre"        => $producto['nombre'],
            "stock"         => (int)$producto['stock']
        ];
    }

    echo json_encode([
        "status" => "success",
        "productos" => $resultado
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al obtener productos: " . $e->getMessage()]);
}
exit;
