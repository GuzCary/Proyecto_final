<?php
// backend/productos/eliminar_producto.php
// Permite eliminar un producto de limpieza (admin, limpieza)
//
// La tabla Gasta (usos registrados) tiene clave foranea hacia Productos, asi que:
// - Si el producto nunca se uso, se borra directamente.
// - Si tiene usos registrados, la primera vez respondemos "requiereConfirmacion" con la cantidad
//   de usos. Solo si el frontend vuelve a enviar con confirmar_historial=1 se borran esos usos y el producto.

// iniciamos la sesion y establecemos el protocolo en JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// Incluimos la conexión y encriptación
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../seguridad/encriptar.php';

// Verificamos que sea por método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

// Verificamos que esté logueado y sea de limpieza (o admin)
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] !== 'limp' && $_SESSION['usuario_rol'] !== 'admin')) {
    echo json_encode(["status" => "error", "message" => "Acceso denegado. Se requiere rol de limpieza o administrador."]);
    exit;
}

$id = desencriptarId($_POST['id_encriptado'] ?? '');
if ($id === false) {
    echo json_encode(["status" => "error", "message" => "ID de producto inválido."]);
    exit;
}

// solo se acepta exactamente "1" como confirmacion
$confirmado = ($_POST['confirmar_historial'] ?? '') === '1';

try {
    $con->beginTransaction();

    $stmt = $con->prepare("SELECT nombre FROM Productos WHERE id = :id FOR UPDATE");
    $stmt->execute([':id' => $id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        $con->rollBack();
        echo json_encode(["status" => "error", "message" => "El producto no existe."]);
        exit;
    }

    // contamos los usos registrados del producto
    $stmtUso = $con->prepare("SELECT COUNT(*) FROM Gasta WHERE idProducto = :id");
    $stmtUso->execute([':id' => $id]);
    $usos = (int)$stmtUso->fetchColumn();

    // si tiene historial y todavia no se confirmo, no borramos nada y pedimos confirmacion
    if ($usos > 0 && !$confirmado) {
        $con->rollBack();
        echo json_encode([
            "status" => "confirmar",
            "requiereConfirmacion" => true,
            "message" => "'{$producto['nombre']}' tiene $usos uso(s) registrado(s) por el personal de limpieza. Si lo eliminás, ese historial también se borra. ¿Eliminar igual?"
        ]);
        exit;
    }

    if ($usos > 0) {
        $stmtGasta = $con->prepare("DELETE FROM Gasta WHERE idProducto = :id");
        $stmtGasta->execute([':id' => $id]);
    }

    $stmtDel = $con->prepare("DELETE FROM Productos WHERE id = :id");
    $stmtDel->execute([':id' => $id]);

    $con->commit();
    echo json_encode(["status" => "success", "message" => "Producto '{$producto['nombre']}' eliminado."]);

} catch (PDOException $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Error al eliminar el producto: " . $e->getMessage()]);
}
