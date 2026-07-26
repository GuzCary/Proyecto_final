<?php
// backend/usuarios.php
// Este archivo permite gestionar los usuarios del sistema (listar y agregar)
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

// Si la peticion es GET, devolvemos todos los usuarios (sin contraseñas)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Version preliminar: solo mostramos id, usuario, rol y fechaDeContrato
        $stmt = $con->prepare("SELECT id, usuario, rol, fechaDeContrato FROM Usuarios ORDER BY usuario ASC");
        $stmt->execute();
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "status" => "success",
            "usuarios" => $usuarios
        ]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Error al obtener usuarios: " . $e->getMessage()]);
    }
    exit;
}

// Si la peticion es POST, agregamos un nuevo usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioInput = trim($_POST['usuario'] ?? '');
    $contraseniaInput = $_POST['contrasenia'] ?? '';
    $rolInput = trim($_POST['rol'] ?? '');

    // Validamos campos obligatorios
    if (empty($usuarioInput) || empty($contraseniaInput) || empty($rolInput)) {
        echo json_encode(["status" => "error", "message" => "Por favor, completa todos los campos (usuario, contraseña y rol)."]);
        exit;
    }

    // Validamos que el rol sea correcto
    if (!in_array($rolInput, ['admin', 'user', 'limp'])) {
        echo json_encode(["status" => "error", "message" => "Rol no valido. Debe ser admin, user o limp."]);
        exit;
    }

    try {
        // Verificamos si el nombre de usuario ya existe
        $stmtCheck = $con->prepare("SELECT id FROM Usuarios WHERE usuario = :usuario");
        $stmtCheck->execute([':usuario' => $usuarioInput]);
        if ($stmtCheck->fetch()) {
            echo json_encode(["status" => "error", "message" => "El nombre de usuario ya existe."]);
            exit;
        }

        // Hasheamos la contraseña
        $hash = password_hash($contraseniaInput, PASSWORD_DEFAULT);

        // Iniciamos una transaccion por si algo falla en las tablas secundarias
        $con->beginTransaction();

        //insertamos los datos
        $stmtUser = $con->prepare("INSERT INTO Usuarios (
            idSucursal, salarioPorHora, usuario, contraseña, diasDeLicencia,
            fechaDeContrato, diasDeTrabajo, horarioDeEntrada, horarioDeSalida, rol
        ) VALUES (
            1, NULL, :usuario, :contrasenia, NULL,
            :fechaDeContrato, NULL, NULL, NULL, :rol
        )");

        $stmtUser->execute([
            ':usuario' => $usuarioInput,
            ':contrasenia' => $hash,
            ':fechaDeContrato' => date('Y-m-d'),
            ':rol' => $rolInput
        ]);

        $userId = $con->lastInsertId();

        // Segun el rol, insertamos en las tablas correspondientes
        if ($rolInput === 'admin' || $rolInput === 'user') {
            // usamos la licencia de conducir con id = 1 por que es de prueba
            $idLicencia = 1;

            // Insertamos en Funcionario
            $stmtFunc = $con->prepare("INSERT INTO Funcionario (id, idLicenciaDeConducir) VALUES (:id, :idLicencia)");
            $stmtFunc->execute([
                ':id' => $userId,
                ':idLicencia' => $idLicencia
            ]);

            if ($rolInput === 'admin') {
                // Insertamos en Administrador
                $stmtAdmin = $con->prepare("INSERT INTO Administrador (id) VALUES (:id)");
                $stmtAdmin->execute([':id' => $userId]);
            } else {
                // Insertamos en Vendedor
                $stmtVend = $con->prepare("INSERT INTO Vendedor (id) VALUES (:id)");
                $stmtVend->execute([':id' => $userId]);
            }
        } elseif ($rolInput === 'limp') {
            // Insertamos en Limpieza
            $stmtLimp = $con->prepare("INSERT INTO Limpieza (id) VALUES (:id)");
            $stmtLimp->execute([':id' => $userId]);
        }

        // Confirmamos la transaccion
        $con->commit();

        echo json_encode(["status" => "success", "message" => "Usuario agregado correctamente."]);

    } catch (PDOException $e) {
        // Si algo falla, revertimos los cambios
        if ($con->inTransaction()) {
            $con->rollBack();
        }
        echo json_encode(["status" => "error", "message" => "Error al guardar el usuario: " . $e->getMessage()]);
    }
    exit;
}

// Si llega otro metodo, respondemos error
echo json_encode(["status" => "error", "message" => "Metodo no permitido."]);
