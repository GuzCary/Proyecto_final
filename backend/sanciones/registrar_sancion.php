<?php
// backend/sanciones/registrar_sancion.php
// Este archivo permite a un administrador registrar una sancion a un empleado (admin)
// Las sanciones son un registro historico: no se editan ni se borran

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

// tipos de sancion permitidos
$tiposPermitidos = ['Observacion', 'Advertencia', 'Suspension', 'Descuento'];

// recibimos y validamos los datos
$idEncriptado = $_POST['id_usuario'] ?? '';
$tipo = sanitizar($_POST['tipo'] ?? '');
$motivo = sanitizar($_POST['motivo'] ?? '');
$consecuencia = sanitizar($_POST['consecuencia'] ?? '');
$fechaInput = $_POST['fecha'] ?? '';
$fecha = validarFecha($fechaInput);

if (empty($idEncriptado) || empty($tipo) || empty($motivo)) {
    echo json_encode(["status" => "error", "message" => "Completa los campos obligatorios: empleado, tipo y motivo."]);
    exit;
}

if (!in_array($tipo, $tiposPermitidos)) {
    echo json_encode(["status" => "error", "message" => "Tipo de sancion no valido."]);
    exit;
}

if (!largoValido($motivo, 255) || !largoValido($consecuencia, 255)) {
    echo json_encode(["status" => "error", "message" => "El motivo y la consecuencia no pueden superar los 255 caracteres."]);
    exit;
}

// si mandaron una fecha tiene que ser valida y no puede ser futura
if ($fechaInput !== '' && $fecha === null) {
    echo json_encode(["status" => "error", "message" => "La fecha no es valida."]);
    exit;
}
if ($fecha === null) {
    $fecha = date('Y-m-d');
}
if ($fecha > date('Y-m-d')) {
    echo json_encode(["status" => "error", "message" => "No se puede registrar una sancion con fecha futura."]);
    exit;
}

$idUsuario = desencriptarId($idEncriptado);
if ($idUsuario === false || $idUsuario === '') {
    echo json_encode(["status" => "error", "message" => "ID de empleado invalido."]);
    exit;
}

// un administrador no se puede sancionar a si mismo
if ($idUsuario == $_SESSION['usuario_id']) {
    echo json_encode(["status" => "error", "message" => "No podes sancionarte a vos mismo."]);
    exit;
}

try {
    // verificamos que el empleado exista y este activo
    $stmt = $con->prepare("SELECT u.usuario
                           FROM Usuarios u
                           LEFT JOIN Baja b ON u.id = b.idUsuario
                           WHERE u.id = :id AND b.id IS NULL");
    $stmt->execute([':id' => $idUsuario]);
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$empleado) {
        echo json_encode(["status" => "error", "message" => "El empleado no existe o esta dado de baja."]);
        exit;
    }

    // insertamos la sancion, el administrador es el que tiene la sesion iniciada
    $stmtIns = $con->prepare("INSERT INTO Sanciona (idAdministrador, idUsuario, fecha, tipo, motivo, consecuencia)
                              VALUES (:idAdmin, :idUsuario, :fecha, :tipo, :motivo, :consecuencia)");
    $stmtIns->execute([
        ':idAdmin'      => $_SESSION['usuario_id'],
        ':idUsuario'    => $idUsuario,
        ':fecha'        => $fecha,
        ':tipo'         => $tipo,
        ':motivo'       => $motivo,
        ':consecuencia' => $consecuencia !== '' ? $consecuencia : null
    ]);

    echo json_encode(["status" => "success", "message" => "Sancion registrada para '{$empleado['usuario']}'."]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al registrar la sancion: " . $e->getMessage()]);
}
