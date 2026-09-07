<?php
// backend/ventas/registrar_venta.php
// Registra la venta de un vehículo (vendedores, admin)

// iniciamos la sesion y establecemos el protocolo en JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// Incluimos conexión a la db y funciones de encriptacion
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../seguridad/encriptar.php';


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

$idVehiculoEncriptado = $_POST['id_vehiculo'] ?? '';

if (empty($idVehiculoEncriptado)) {
    echo json_encode(["status" => "error", "message" => "El ID del vehículo es obligatorio."]);
    exit;
}

// Desencriptamos el ID del vehículo
$idVehiculo = desencriptar($idVehiculoEncriptado);
if ($idVehiculo === false || $idVehiculo === '') {
    echo json_encode(["status" => "error", "message" => "ID de vehículo inválido."]);
    exit;
}

$idFuncionario = $_SESSION['usuario_id']; // El usuario logueado

try {
    // 1. Verificamos que el vehículo exista y no esté vendido
    $stmtCheck = $con->prepare("SELECT id, marca, modelo, precio FROM Vehiculo WHERE id = :id");
    $stmtCheck->execute([':id' => $idVehiculo]);
    $vehiculo = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$vehiculo) {
        echo json_encode(["status" => "error", "message" => "El vehículo seleccionado no existe."]);
        exit;
    }

    // 2. Registramos la venta con la fecha actual (CURDATE())
    $stmtVenta = $con->prepare("INSERT INTO Ventas (idVehiculo, idFuncionario, fecha) VALUES (:idVehiculo, :idFuncionario, CURDATE())");
    $stmtVenta->execute([
        ':idVehiculo'    => $idVehiculo,
        ':idFuncionario' => $idFuncionario
    ]);

    // 3. Cambiamos el estado del vehículo a 0 (inactivo/vendido) para que no se venda dos veces
    $stmtVehiculo = $con->prepare("UPDATE Vehiculo SET estado = 0 WHERE id = :id");
    $stmtVehiculo->execute([':id' => $idVehiculo]);

    echo json_encode([
        "status"  => "success",
        "message" => "Venta registrada con éxito para " . $vehiculo['marca'] . " " . $vehiculo['modelo'] . "."
    ]);
    exit;

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al registrar la venta: " . $e->getMessage()]);
    exit;
}