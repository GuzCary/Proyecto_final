<?php
// backend/productos/productos.php
// Permite agregar un producto nuevo y sumar/restar stock a uno existente (admin, limpieza)

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

$idEncriptado = $_POST['id_encriptado'] ?? '';
$nombre = sanitizar($_POST['nombre']);
$cantidad = validarEntero($_POST['cantidad']);

try {
    if (empty($idEncriptado)) {
        // si no se mando nombre frenamos
        if (empty($nombre)) {
            echo json_encode(["status" => "error", "message" => "El nombre del producto es obligatorio."]);
            exit;
        }

        // Verificamos si ya existe un producto con ese mismo nombre
        $stmtCheck = $con->prepare("SELECT id FROM Productos WHERE LOWER(nombre) = LOWER(:nombre)");
        $stmtCheck->execute([':nombre' => $nombre]);

        if ($stmtCheck->fetch()) {
            echo json_encode(["status" => "error", "message" => "El producto ya existe en el sistema."]);
            exit;
        }

        // si todo sale bien lo insertamos con stock 0
        $stmtInsert = $con->prepare("INSERT INTO Productos (nombre, stock) VALUES (:nombre, 0)");
        $stmtInsert->execute([':nombre' => $nombre]);

        echo json_encode([
            "status" => "success",
            "message" => "Producto registrado correctamente con stock en 0."
        ]);
        exit;
    }



    $id = desencriptar($idEncriptado);
    if ($id === false || $id === '') {
        echo json_encode(["status" => "error", "message" => "ID de producto inválido."]);
        exit;
    }

    // La cantidad no puede ser nula ni cero
    if ($cantidad === null || $cantidad === 0) {
        echo json_encode(["status" => "error", "message" => "Debes indicar una cantidad válida a sumar o restar."]);
        exit;
    }

    // Consultamos el stock actual del producto
    $stmt = $con->prepare("SELECT stock, nombre FROM Productos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        echo json_encode(["status" => "error", "message" => "El producto no existe."]);
        exit;
    }

    // Calculamos el nuevo stock
    $nuevoStock = $producto['stock'] + $cantidad;

    // Evitamos que el stock quede en negativo
    if ($nuevoStock < 0) {
        echo json_encode([
            "status" => "error",
            "message" => "No hay suficiente stock. Stock actual: " . $producto['stock']
        ]);
        exit;
    }

    // Actualizamos el stock en la base de datos
    $stmtUpdate = $con->prepare("UPDATE Productos SET stock = :nuevoStock WHERE id = :id");
    $stmtUpdate->execute([
        ':nuevoStock' => $nuevoStock,
        ':id' => $id
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Stock actualizado correctamente.",
        "producto" => $producto['nombre'],
        "nuevo_stock" => $nuevoStock
    ]);
    exit;

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error en la base de datos: " . $e->getMessage()]);
    exit;
}
