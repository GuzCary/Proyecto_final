<?php
// backend/transacciones/registrar_transaccion.php
// Este archivo permite a un administrador registrar movimientos de caja (admin)
// Un movimiento puede ser un Ingreso o un Egreso de dinero de una sucursal

// iniciamos la sesion y establecemos el protocolo en JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// incluimos la conexion a la db, las funciones de encriptacion y sanitizacion
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../seguridad/encriptar.php';
require_once __DIR__ . '/../seguridad/sanitizar.php';

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

// recibimos y validamos los datos
$idSucursalEncriptado = $_POST['id_sucursal'] ?? '';
$tipo = sanitizar($_POST['tipo'] ?? '');
$monto = validarFloat($_POST['monto'] ?? null);
$justificacion = sanitizar($_POST['justificacion'] ?? '');
$fechaInput = $_POST['fecha'] ?? '';
$fecha = validarFecha($fechaInput);

if (empty($idSucursalEncriptado) || empty($tipo) || $monto === null || empty($justificacion)) {
    echo json_encode(["status" => "error", "message" => "Completa todos los campos: sucursal, tipo, monto y justificacion."]);
    exit;
}

if (!in_array($tipo, ['Ingreso', 'Egreso'])) {
    echo json_encode(["status" => "error", "message" => "El tipo debe ser Ingreso o Egreso."]);
    exit;
}

// el monto siempre es positivo, el tipo indica si entra o sale
if ($monto <= 0) {
    echo json_encode(["status" => "error", "message" => "El monto tiene que ser mayor a 0."]);
    exit;
}

if (mb_strlen($justificacion) > 255) {
    echo json_encode(["status" => "error", "message" => "La justificacion no puede superar los 255 caracteres."]);
    exit;
}

// la fecha es opcional (por defecto hoy) pero no puede ser futura
if ($fechaInput !== '' && $fecha === null) {
    echo json_encode(["status" => "error", "message" => "La fecha no es valida."]);
    exit;
}
if ($fecha === null) {
    $fecha = date('Y-m-d');
}
if ($fecha > date('Y-m-d')) {
    echo json_encode(["status" => "error", "message" => "No se puede registrar una transaccion con fecha futura."]);
    exit;
}

$idSucursal = desencriptar($idSucursalEncriptado);
if ($idSucursal === false || $idSucursal === '') {
    echo json_encode(["status" => "error", "message" => "Sucursal invalida."]);
    exit;
}

try {
    // verificamos que la sucursal exista
    $stmtSuc = $con->prepare("SELECT nombre FROM Sucursal WHERE id = :id");
    $stmtSuc->execute([':id' => $idSucursal]);
    $sucursal = $stmtSuc->fetch(PDO::FETCH_ASSOC);

    if (!$sucursal) {
        echo json_encode(["status" => "error", "message" => "La sucursal no existe."]);
        exit;
    }

    // el funcionario que registra es el administrador con la sesion iniciada
    $stmt = $con->prepare("INSERT INTO Transacciones (idFuncionario, idSucursal, tipo, monto, justificacion, fecha)
                           VALUES (:idFuncionario, :idSucursal, :tipo, :monto, :justificacion, :fecha)");
    $stmt->execute([
        ':idFuncionario' => $_SESSION['usuario_id'],
        ':idSucursal'    => $idSucursal,
        ':tipo'          => $tipo,
        ':monto'         => $monto,
        ':justificacion' => $justificacion,
        ':fecha'         => $fecha
    ]);

    echo json_encode(["status" => "success", "message" => "$tipo de $$monto registrado en '{$sucursal['nombre']}'."]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al registrar la transaccion: " . $e->getMessage()]);
}
