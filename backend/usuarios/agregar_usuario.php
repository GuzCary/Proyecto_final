<?php
// backend/usuarios/agregar_usuario.php
// Este archivo permite agregar los usuarios al sistema (admin)

// iniciamos la sesion y establecemos el protocolo en JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// Incluimos la conexion a la db y las funciones de sanitizacion y encriptacion
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../seguridad/encriptar.php';
require_once __DIR__ . '/../seguridad/sanitizar.php';

// Reglas de los datos del usuario:
// - nombre: 3 a 50 caracteres, solo letras, numeros, punto, guion y guion bajo
// - contraseña: entre 6 y 72 caracteres (72 es el maximo que usa password_hash)
// - salario por hora: numero entre 0 y 1.000.000
function validarDatosUsuario($usuarioInput, $contraseniaInput, $contraseniaObligatoria, $salarioInput) {
    if (validarNombreUsuario($usuarioInput) === false) {
        return "El nombre de usuario debe tener entre 3 y 50 caracteres y solo letras, numeros, punto, guion o guion bajo.";
    }
    if ($contraseniaInput !== '' || $contraseniaObligatoria) {
        $largo = strlen($contraseniaInput);
        if ($largo < 6 || $largo > 72) {
            return "La contraseña debe tener entre 6 y 72 caracteres.";
        }
    }
    if ($salarioInput !== '' && validarFloatRango($salarioInput, 0, 1000000) === false) {
        return "El salario por hora tiene que ser un numero positivo.";
    }
    return null;
}

// Verificamos que el usuario este logueado y sea administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Acceso denegado. Se requiere rol de administrador."]);
    exit;
}

// Si la peticion es POST, agregamos un nuevo usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioInput = sanitizar($_POST['usuario'] ?? '');
    $contraseniaInput = sanitizar($_POST['contrasenia'] ?? '');
    $rolInput = sanitizar($_POST['rol'] ?? '');
    $sucursalEncriptada = $_POST['id_sucursal'] ?? '';
    $salarioInput = $_POST['salarioPorHora'] ?? '';
    $salario = $salarioInput !== '' ? validarFloat($salarioInput) : null;

    // Validamos campos obligatorios
    if (empty($usuarioInput) || empty($contraseniaInput) || empty($rolInput) || empty($sucursalEncriptada)) {
        echo json_encode(["status" => "error", "message" => "Por favor, completa todos los campos (usuario, contraseña, rol y sucursal)."]);
        exit;
    }

    // Validamos que el rol sea correcto
    if (!in_array($rolInput, ['admin', 'user', 'limp'])) {
        echo json_encode(["status" => "error", "message" => "Rol no valido. Debe ser admin, user o limp."]);
        exit;
    }

    // Validamos formato y rangos (nombre, contraseña y salario)
    $errorDatos = validarDatosUsuario($usuarioInput, $contraseniaInput, true, $salarioInput);
    if ($errorDatos !== null) {
        echo json_encode(["status" => "error", "message" => $errorDatos]);
        exit;
    }

    // Desencriptamos la sucursal
    $idSucursal = desencriptarId($sucursalEncriptada);
    if ($idSucursal === false || $idSucursal === '') {
        echo json_encode(["status" => "error", "message" => "Sucursal invalida."]);
        exit;
    }

    try {
        // Verificamos que la sucursal exista
        $stmtSuc = $con->prepare("SELECT id FROM Sucursal WHERE id = :id");
        $stmtSuc->execute([':id' => $idSucursal]);
        if (!$stmtSuc->fetch()) {
            echo json_encode(["status" => "error", "message" => "La sucursal no existe."]);
            exit;
        }

        // Verificamos si el nombre de usuario ya lo usa un usuario ACTIVO
        // (los dados de baja se ignoran, asi su nombre se puede volver a usar)
        $stmtCheck = $con->prepare("SELECT u.id
                                    FROM Usuarios u
                                    LEFT JOIN Baja b ON u.id = b.idUsuario
                                    WHERE u.usuario = :usuario AND b.id IS NULL");
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
            :idSucursal, :salario, :usuario, :contrasenia, NULL,
            :fechaDeContrato, NULL, NULL, NULL, :rol
        )");

        $stmtUser->execute([
            ':idSucursal' => $idSucursal,
            ':salario' => $salario,
            ':usuario' => $usuarioInput,
            ':contrasenia' => $hash,
            ':fechaDeContrato' => date('Y-m-d'),
            ':rol' => $rolInput
        ]);

        $userId = $con->lastInsertId();

        // Segun el rol, insertamos en las tablas correspondientes
        if ($rolInput === 'admin' || $rolInput === 'user') {
            // Insertamos en Funcionario sin licencia: se carga despues desde Config > Licencias
            $stmtFunc = $con->prepare("INSERT INTO Funcionario (id, idLicenciaDeConducir) VALUES (:id, NULL)");
            $stmtFunc->execute([':id' => $userId]);

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
