<?php
// backend/productos/modificar_producto.php
// Permite cambiar el nombre de un producto de limpieza (admin, limpieza)
// El stock no se edita aca: se suma o resta desde productos.php para que quede registrado el gasto

// iniciamos la sesion y establecemos el protocolo en JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// Incluimos la conexión, encriptación y sanitización
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../seguridad/encriptar.php';
require_once __DIR__ . '/../seguridad/sanitizar.php';

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
$nombre = sanitizar($_POST['nombre'] ?? '');

if ($id === false) {
    echo json_encode(["status" => "error", "message" => "ID de producto inválido."]);
    exit;
}

if (empty($nombre)) {
    echo json_encode(["status" => "error", "message" => "El nombre del producto es obligatorio."]);
    exit;
}

if (!largoValido($nombre, 100)) {
    echo json_encode(["status" => "error", "message" => "El nombre no puede superar los 100 caracteres."]);
    exit;
}

try {
    // el producto tiene que existir
    $stmt = $con->prepare("SELECT id FROM Productos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        echo json_encode(["status" => "error", "message" => "El producto no existe."]);
        exit;
    }

    // no puede haber otro producto con el mismo nombre
    $stmtCheck = $con->prepare("SELECT id FROM Productos WHERE LOWER(nombre) = LOWER(:nombre) AND id != :id");
    $stmtCheck->execute([':nombre' => $nombre, ':id' => $id]);
    if ($stmtCheck->fetch()) {
        echo json_encode(["status" => "error", "message" => "Ya existe un producto con ese nombre."]);
        exit;
    }

    $stmtUpd = $con->prepare("UPDATE Productos SET nombre = :nombre WHERE id = :id");
    $stmtUpd->execute([':nombre' => $nombre, ':id' => $id]);

    echo json_encode(["status" => "success", "message" => "Producto actualizado correctamente."]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al modificar el producto: " . $e->getMessage()]);
}
