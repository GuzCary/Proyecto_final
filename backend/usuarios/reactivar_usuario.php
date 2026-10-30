<?php
// backend/usuarios/reactivar_usuario.php
// Este archivo permite reincorporar a un usuario que fue dado de baja (admin)
// Se borra su registro de la tabla Baja, asi vuelve a poder iniciar sesion

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

// Verificamos que la peticion sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Metodo no permitido."]);
    exit;
}

$idEncriptado = $_POST['id'] ?? '';
if (empty($idEncriptado)) {
    echo json_encode(["status" => "error", "message" => "ID de usuario no proporcionado."]);
    exit;
}

$id = desencriptar($idEncriptado);
if ($id === false || $id === '') {
    echo json_encode(["status" => "error", "message" => "ID de usuario invalido."]);
    exit;
}

try {
    $con->beginTransaction();

    // buscamos el usuario y nos aseguramos de que este dado de baja
    $stmt = $con->prepare("SELECT u.usuario, b.id AS idBaja
                           FROM Usuarios u
                           INNER JOIN Baja b ON b.idUsuario = u.id
                           WHERE u.id = :id");
    $stmt->execute([':id' => $id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        $con->rollBack();
        echo json_encode(["status" => "error", "message" => "El usuario no existe o no esta dado de baja."]);
        exit;
    }

    // si mientras tanto alguien activo tomo el mismo nombre, no lo podemos reactivar
    $stmtCheck = $con->prepare("SELECT u.id
                                FROM Usuarios u
                                LEFT JOIN Baja b ON u.id = b.idUsuario
                                WHERE u.usuario = :usuario AND u.id != :id AND b.id IS NULL");
    $stmtCheck->execute([':usuario' => $usuario['usuario'], ':id' => $id]);
    if ($stmtCheck->fetch()) {
        $con->rollBack();
        echo json_encode([
            "status" => "error",
            "message" => "No se puede reactivar: el nombre '{$usuario['usuario']}' ya lo usa otro usuario activo."
        ]);
        exit;
    }

    // borramos el registro de baja
    $stmtDel = $con->prepare("DELETE FROM Baja WHERE idUsuario = :id");
    $stmtDel->execute([':id' => $id]);

    $con->commit();
    echo json_encode(["status" => "success", "message" => "El usuario '{$usuario['usuario']}' fue reactivado."]);

} catch (PDOException $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Error al reactivar el usuario: " . $e->getMessage()]);
}
