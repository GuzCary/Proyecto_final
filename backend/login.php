<?php
// backend/login.php
// Este archivo recibe las credenciales del usuario, las valida en la base de datos
// y, si son correctas, inicia una sesion de PHP 


session_start();


header("Content-Type: application/json; charset=UTF-8");

// incluimos la conexion a la base de datos
require_once __DIR__ . '/../config/conexion.php';

// Recibimos los datos enviados por POST
$usuarioInput = $_POST['usuario'] ?? '';
$contraseniaInput = $_POST['contrasenia'] ?? '';

// Validamos que no esten vacios
if (empty($usuarioInput) || empty($contraseniaInput)) {
    echo json_encode(["status" => "error", "message" => "Por favor, completa todos los campos."]);
    exit;
}

try {
    // Buscamos al usuario por su nombre de usuario
    //traemos el hash almacenado
    $stmt = $con->prepare("SELECT id, usuario, contraseña, rol FROM Usuarios WHERE usuario = :user");
    $stmt->execute([':user' => $usuarioInput]);
    $usuario = $stmt->fetch();

    // Si el usuario existe, verificamos la contraseña con password_verify()
    if ($usuario && password_verify($contraseniaInput, $usuario['contraseña'])) {
        $userId = $usuario['id'];
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
                "message" => "el usuario tiene un rol invalido" . $rol
            ]);
        }

    } else {
        
        echo json_encode(["status" => "error", "message" => "Usuario o contraseña incorrectos."]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error de servidor: " . $e->getMessage()]);
}
