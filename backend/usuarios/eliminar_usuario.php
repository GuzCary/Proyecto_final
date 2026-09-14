<?php
// backend/usuarios/eliminar_usuario.php 
// este archivo permite dar de baja a un usuario (admin)

// iniciamos la sesion y establecemos el protocolo en JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// incluimos la conexion y las funciones de encriptacion y sanitizacion
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../seguridad/encriptar.php';
require_once __DIR__ . '/../seguridad/sanitizar.php';

// Verificar permisos de administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Acceso denegado. Se requiere rol de administrador."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

$idEncriptado = sanitizar($_POST['id'] ?? '');
$tipoBaja = sanitizar($_POST['tipo'] ?? '' ); 

if (empty($idEncriptado)) {
    echo json_encode(["status" => "error", "message" => "ID de usuario no proporcionado."]);
    exit;
}

$id = desencriptar($idEncriptado);

if (empty($id)) {
    echo json_encode(["status" => "error", "message" => "ID de usuario inválido."]);
    exit;
}

// Evitar que el administrador se dé de baja a sí mismo
if ($id == $_SESSION['usuario_id']) {
    echo json_encode(["status" => "error", "message" => "No podés dar de baja tu propio usuario."]);
    exit;
}

try {
    // Verificar si el usuario existe
    $stmtUser = $con->prepare("SELECT id, usuario FROM Usuarios WHERE id = :id");
    $stmtUser->execute([':id' => $id]);
    $usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        echo json_encode(["status" => "error", "message" => "El usuario no existe."]);
        exit;
    }

    // Verificar si ya fue dado de baja previamente
    $stmtCheckBaja = $con->prepare("SELECT id FROM Baja WHERE idUsuario = :id");
    $stmtCheckBaja->execute([':id' => $id]);
    if ($stmtCheckBaja->fetch()) {
        echo json_encode(["status" => "error", "message" => "Este usuario ya se encuentra dado de baja."]);
        exit;
    }

    // Registrar la baja
    $stmtBaja = $con->prepare("INSERT INTO Baja (idUsuario, tipo, fecha) VALUES (:idUsuario, :tipo, CURDATE())");
    $stmtBaja->execute([
        ':idUsuario' => $id,
        ':tipo' => !empty($tipoBaja) ? $tipoBaja : 'Baja general'
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "El usuario '{$usuario['usuario']}' fue dado de baja correctamente."
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al registrar la baja: " . $e->getMessage()]);
}