<?php
// backend/categorias/registrar_categoria.php
// Este archivo permite registrar categorias de vehiculos (admin)


// iniciamos la sesion y establecemos la respuesta como JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// incluimos la conexion a la db y las funciones de encriptacion
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../seguridad/encriptar.php';
require_once __DIR__ . '/../seguridad/sanitizar.php';


// Si la peticion es POST, verificamos que sea administrador
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
        echo json_encode(["status" => "error", "message" => "Acceso denegado. Se requiere rol de administrador."]);
        exit;
    }

    $nombre = sanitizar($_POST['nombre']);
    $idEncriptado = $_POST['id_encriptado'] ?? '';

    // nos fijamos que tenga nombre
    if (empty($nombre)) {
        echo json_encode(["status" => "error", "message" => "El nombre de la categoria es obligatorio."]);
        exit;
    }

    try {
        // si llega un id encriptado, actualizamos la categoria existente
        if (!empty($idEncriptado)) {
            $id = desencriptar($idEncriptado);
            if ($id === false || $id === '') {
                echo json_encode(["status" => "error", "message" => "ID de categoria invalido."]);
                exit;
            }

            $stmt = $con->prepare("UPDATE Categoria SET nombre = :nombre WHERE id = :id");
            $stmt->execute([':nombre' => $nombre, ':id' => $id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(["status" => "success", "message" => "Categoria actualizada correctamente."]);
            } else {
                echo json_encode(["status" => "success", "message" => "No se realizaron cambios."]);
            }
        } else {
            // si no hay id, creamos una nueva categoria
            $stmt = $con->prepare("INSERT INTO Categoria (nombre) VALUES (:nombre)");
            $stmt->execute([':nombre' => $nombre]);

            echo json_encode(["status" => "success", "message" => "Categoria agregada correctamente."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Error al guardar la categoria: " . $e->getMessage()]);
    }
    exit;
}

// si llega otro metodo, respondemos error
echo json_encode(["status" => "error", "message" => "Metodo no permitido."]);
