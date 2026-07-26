<?php
// backend/eliminar_usuario.php
// Este archivo permite eliminar un usuario del sistema
// Solo puede ser usado por un usuario autenticado con rol de administrador

session_start();
header("Content-Type: application/json; charset=UTF-8");

// Incluimos la conexion a la db
require_once __DIR__ . '/../config/conexion.php';

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

// Recibimos el ID del usuario a eliminar
$id = $_POST['id'] ?? '';

// Validamos que el id exista
if (empty($id)) {
    echo json_encode(["status" => "error", "message" => "ID de usuario no proporcionado."]);
    exit;
}

// Evitamos que el administrador se elimine a si mismo
if ($id == $_SESSION['usuario_id']) {
    echo json_encode(["status" => "error", "message" => "No podés eliminar tu propio usuario."]);
    exit;
}

try {
    // Iniciamos una transaccion para eliminar en cascada de manera segura
    $con->beginTransaction();

    // Eliminamos de Gasta (relacionado con Limpieza)
    $stmt = $con->prepare("DELETE FROM Gasta WHERE idLimpieza = :id");
    $stmt->execute([':id' => $id]);

    // Eliminamos de Limpieza
    $stmt = $con->prepare("DELETE FROM Limpieza WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // Eliminamos de Sanciona (como sancionado o sancionador)
    $stmt = $con->prepare("DELETE FROM Sanciona WHERE idUsuario = :id OR idAdministrador = :id");
    $stmt->execute([':id' => $id]);

    // Eliminamos de AumentosDeSueldo (como beneficiario o administrador)
    $stmt = $con->prepare("DELETE FROM AumentosDeSueldo WHERE idUsuario = :id OR idAdministrador = :id");
    $stmt->execute([':id' => $id]);

    // Eliminamos de Ventas (vendedor/funcionario)
    $stmt = $con->prepare("DELETE FROM Ventas WHERE idFuncionario = :id");
    $stmt->execute([':id' => $id]);

    // Eliminamos de Transacciones (funcionario)
    $stmt = $con->prepare("DELETE FROM Transacciones WHERE idFuncionario = :id");
    $stmt->execute([':id' => $id]);

    // Eliminamos de Vendedor
    $stmt = $con->prepare("DELETE FROM Vendedor WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // Eliminamos de Administrador
    $stmt = $con->prepare("DELETE FROM Administrador WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // Eliminamos de Funcionario
    $stmt = $con->prepare("DELETE FROM Funcionario WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // Eliminamos de RegistroMarca
    $stmt = $con->prepare("DELETE FROM RegistroMarca WHERE idUsuario = :id");
    $stmt->execute([':id' => $id]);

    // Eliminamos de Baja
    $stmt = $con->prepare("DELETE FROM Baja WHERE idUsuario = :id");
    $stmt->execute([':id' => $id]);

    // Eliminamos de la tabla principal Usuarios
    $stmt = $con->prepare("DELETE FROM Usuarios WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // Verificamos si se elimino el registro
    if ($stmt->rowCount() > 0) {
        $con->commit();
        echo json_encode(["status" => "success", "message" => "Usuario eliminado correctamente."]);
    } else {
        $con->rollBack();
        echo json_encode(["status" => "error", "message" => "No se encontro el usuario."]);
    }

} catch (PDOException $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Error al eliminar el usuario: " . $e->getMessage()]);
}
