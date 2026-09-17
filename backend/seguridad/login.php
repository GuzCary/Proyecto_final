<?php
// backend/seguridad/login.php
// Este archivo recibe las credenciales del usuario, las valida en la base de datos
// y, si son correctas y el usuario no está dado de baja, inicia una sesion de PHP

// iniciamos la sesion y establecemos el protocolo en JSON
session_start();

// establecemos el protocolo en JSON
header("Content-Type: application/json; charset=UTF-8");

// comprobamos que el usuario no tenga un bloqueo temporal
if (isset($_SESSION['bloqueado_hasta']) && time() < $_SESSION['bloqueado_hasta']) {

    $segundosRestantes = $_SESSION['bloqueado_hasta'] - time();

    echo json_encode([
        "status" => "error", 
        "message" => "Muchos intentos fallidos. Espera {$segundosRestantes} segundos."
    ]);
    exit;
}




// incluimos la conexion a la base de datos y las funciones de sanitizacion
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/sanitizar.php';

// Recibimos los datos enviados por POST
$usuarioInput = sanitizar($_POST['usuario'] ?? '');
$contraseniaInput = sanitizar($_POST['contrasenia'] ?? '');

// Validamos que no esten vacios
if (empty($usuarioInput) || empty($contraseniaInput)) {
    echo json_encode(["status" => "error", "message" => "Por favor, completa todos los campos."]);
    exit;
}

try {
    // Buscamos al usuario por su nombre de usuario
    // traemos el hash almacenado
    $stmt = $con->prepare("SELECT id, usuario, contraseña, rol FROM Usuarios WHERE usuario = :user");
    $stmt->execute([':user' => $usuarioInput]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si el usuario existe, verificamos la contraseña con password_verify()
    if ($usuario && password_verify($contraseniaInput, $usuario['contraseña'])) {
        $userId = $usuario['id'];

        
        // Verificamos si el usuario está registrado en la tabla Baja
        $stmtBaja = $con->prepare("SELECT tipo, fecha FROM Baja WHERE idUsuario = :id");
        $stmtBaja->execute([':id' => $userId]);
        $baja = $stmtBaja->fetch(PDO::FETCH_ASSOC);

        if ($baja) {
            echo json_encode([
                "status" => "error",
                "message" => "Esta cuenta fue dada de baja el " . $baja['fecha'] . " (Motivo: " . $baja['tipo'] . ")."
            ]);
            exit;
        }
       

        $rol = $usuario['rol'];
        $verificado = false;

        // Segun el rol, verificamos que el usuario exista en su tabla correspondiente
        if ($rol === 'admin') {
            $stmt = $con->prepare("SELECT id FROM Administrador WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            if ($stmt->fetch()) {
                $verificado = true;
            }
        } elseif ($rol === 'user') {
            $stmt = $con->prepare("SELECT id FROM Vendedor WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            if ($stmt->fetch()) {
                $verificado = true;
            }
        } elseif ($rol === 'limp') {
            $stmt = $con->prepare("SELECT id FROM Limpieza WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            if ($stmt->fetch()) {
                $verificado = true;
            }
        }

        // si todo sale bien iniciamos la sesion
        if ($verificado) {
            //limpiamos las variables de bloqueo
            unset($_SESSION['intentos_fallidos'], $_SESSION['bloqueado_hasta']);

            $_SESSION['usuario_id'] = $userId;
            $_SESSION['usuario_nombre'] = $usuario['usuario'];
            $_SESSION['usuario_rol'] = $rol;

            echo json_encode([
                "status" => "success",
                "message" => "el usuario " . $usuario['usuario'] . " ingreso",
                "rol" => $rol
            ]);
        } else {
            // si el rol de Usuarios no coincide con ninguna tabla secundaria damos error
            echo json_encode([
                "status" => "error",
                "message" => "el usuario tiene un rol invalido: " . $rol
            ]);
        }

    } else {
       

        // registramos el fallo 

        $_SESSION['intentos_fallidos'] = ($_SESSION['intentos_fallidos'] ?? 0) + 1;

        if ($_SESSION['intentos_fallidos'] >= 5) {

            $_SESSION['bloqueado_hasta'] = time() + 60; // Bloquea por 60 segundos

            unset($_SESSION['intentos_fallidos']);
            echo json_encode([
                "status" => "error", 
                "message" => "Has superado los 5 intentos. Bloqueado por 1 minuto."
            ]);
            exit;
        }


        // si las credenciales no existen o la contraseña no coincide, y no se llegaron a los 5 intentos, devolvemos error
        echo json_encode(["status" => "error", "message" => "Usuario o contraseña incorrectos."]);




    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error de servidor: " . $e->getMessage()]);
}


