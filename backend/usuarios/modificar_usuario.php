<?php
// backend/usuarios/modificar_usuario.php
// Este archivo permite modificar un usuario existente 

// iniciamos la sesion y establecemos el protocolo en JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// incluimos la conexion a la db y las funciones de sanitizacion y encriptacion
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

// Recibimos y sanitizamos los datos
$idEncriptado = sanitizar($_POST['id_encriptado'] ?? '');
$usuarioInput = sanitizar($_POST['usuario'] ?? '');
$contraseniaInput = sanitizar($_POST['contrasenia'] ?? '');
$rolInput = sanitizar($_POST['rol'] ?? '');

// Validamos campos obligatorios
if (empty($idEncriptado) || empty($usuarioInput) || empty($rolInput)) {
    echo json_encode(["status" => "error", "message" => "Por favor, completa los campos obligatorios."]);
    exit;
}

// Desencriptamos el id
$id = desencriptar($idEncriptado);
if ($id === false || $id === '') {
    echo json_encode(["status" => "error", "message" => "ID de usuario invalido."]);
    exit;
}

// Validamos que el rol sea correcto
if (!in_array($rolInput, ['admin', 'user', 'limp'])) {
    echo json_encode(["status" => "error", "message" => "Rol no valido. Debe ser admin, user o limp."]);
    exit;
}

try {
    // Verificamos si el nombre de usuario ya existe en otra cuenta
    $stmtCheck = $con->prepare("SELECT id FROM Usuarios WHERE usuario = :usuario AND id != :id");
    $stmtCheck->execute([
        ':usuario' => $usuarioInput,
        ':id' => $id
    ]);

    if ($stmtCheck->fetch()) {
        echo json_encode(["status" => "error", "message" => "El nombre de usuario ya existe."]);
        exit;
    }

    // Consultamos el rol actual para ver si cambio
    $stmtCur = $con->prepare("SELECT rol FROM Usuarios WHERE id = :id");
    $stmtCur->execute([':id' => $id]);
    $usuarioActual = $stmtCur->fetch(PDO::FETCH_ASSOC);

    if (!$usuarioActual) {
        echo json_encode(["status" => "error", "message" => "El usuario no existe."]);
        exit;
    }

    $rolActual = $usuarioActual['rol'];

    // Iniciamos una transaccion
    $con->beginTransaction();

    // Actualizamos usuario (con contraseña si vino una nueva, o sin contraseña si vino vacia)
    if (!empty($contraseniaInput)) {
        $hash = password_hash($contraseniaInput, PASSWORD_DEFAULT);
        $stmtUser = $con->prepare("UPDATE Usuarios SET usuario = :usuario, contraseña = :contrasenia, rol = :rol WHERE id = :id");
        $stmtUser->execute([
            ':usuario' => $usuarioInput,
            ':contrasenia' => $hash,
            ':rol' => $rolInput,
            ':id' => $id
        ]);
    } else {
        $stmtUser = $con->prepare("UPDATE Usuarios SET usuario = :usuario, rol = :rol WHERE id = :id");
        $stmtUser->execute([
            ':usuario' => $usuarioInput,
            ':rol' => $rolInput,
            ':id' => $id
        ]);
    }

    // Si cambio el rol, actualizamos las tablas secundarias correspondientes
    if ($rolActual !== $rolInput) {
        // Eliminamos de la tabla del rol anterior
        if ($rolActual === 'admin') {
            $stmtDel = $con->prepare("DELETE FROM Administrador WHERE id = :id");
            $stmtDel->execute([':id' => $id]);
        } elseif ($rolActual === 'user') {
            $stmtDel = $con->prepare("DELETE FROM Vendedor WHERE id = :id");
            $stmtDel->execute([':id' => $id]);
        } elseif ($rolActual === 'limp') {
            $stmtDel = $con->prepare("DELETE FROM Limpieza WHERE id = :id");
            $stmtDel->execute([':id' => $id]);
        }

        // Insertamos en la tabla del rol nuevo
        if ($rolInput === 'limp') {
            $stmtIns = $con->prepare("INSERT INTO Limpieza (id) VALUES (:id)");
            $stmtIns->execute([':id' => $id]);
        } else {
            // Si pasa a admin o user, aseguramos que este en Funcionario
            $stmtFuncCheck = $con->prepare("SELECT id FROM Funcionario WHERE id = :id");
            $stmtFuncCheck->execute([':id' => $id]);

            if (!$stmtFuncCheck->fetch()) {
                $stmtFuncIns = $con->prepare("INSERT INTO Funcionario (id, idLicenciaDeConducir) VALUES (:id, :idLicencia)");
                $stmtFuncIns->execute([
                    ':id' => $id,
                    ':idLicencia' => 1
                ]);
            }

            if ($rolInput === 'admin') {
                $stmtAdmin = $con->prepare("INSERT INTO Administrador (id) VALUES (:id)");
                $stmtAdmin->execute([':id' => $id]);
            } else {
                $stmtVend = $con->prepare("INSERT INTO Vendedor (id) VALUES (:id)");
                $stmtVend->execute([':id' => $id]);
            }
        }
    }

    // Confirmamos la transaccion
    $con->commit();

    echo json_encode(["status" => "success", "message" => "Usuario actualizado correctamente."]);

} catch (PDOException $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Error al actualizar el usuario: " . $e->getMessage()]);
}