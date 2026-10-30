<?php
// backend/sucursales/registrar_sucursal.php
// Este archivo permite agregar una sucursal nueva o editar una existente (admin)
// Si llega un id_encriptado se actualiza, si no se inserta una nueva

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

// el id encriptado no se sanitiza porque htmlspecialchars puede romper el codigo
$idEncriptado = $_POST['id_encriptado'] ?? '';
$nombre = sanitizar($_POST['nombre'] ?? '');

// nos fijamos que tenga nombre
if (empty($nombre)) {
    echo json_encode(["status" => "error", "message" => "El nombre de la sucursal es obligatorio."]);
    exit;
}

// el nombre no puede superar el largo de la columna
if (mb_strlen($nombre) > 100) {
    echo json_encode(["status" => "error", "message" => "El nombre no puede superar los 100 caracteres."]);
    exit;
}

try {
    // si llega un id lo desencriptamos
    $id = null;
    if (!empty($idEncriptado)) {
        $id = desencriptar($idEncriptado);
        if ($id === false || $id === '') {
            echo json_encode(["status" => "error", "message" => "ID de sucursal invalido."]);
            exit;
        }
    }

    // verificamos que no exista otra sucursal con el mismo nombre
    $stmtCheck = $con->prepare("SELECT id FROM Sucursal WHERE LOWER(nombre) = LOWER(:nombre) AND id != :id");
    $stmtCheck->execute([':nombre' => $nombre, ':id' => $id ?? 0]);
    if ($stmtCheck->fetch()) {
        echo json_encode(["status" => "error", "message" => "Ya existe una sucursal con ese nombre."]);
        exit;
    }

    if ($id !== null) {
        // si hay id actualizamos la sucursal existente
        $stmt = $con->prepare("UPDATE Sucursal SET nombre = :nombre WHERE id = :id");
        $stmt->execute([':nombre' => $nombre, ':id' => $id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => "success", "message" => "Sucursal actualizada correctamente."]);
        } else {
            echo json_encode(["status" => "success", "message" => "No se realizaron cambios."]);
        }
    } else {
        // si no hay id creamos una nueva sucursal
        $stmt = $con->prepare("INSERT INTO Sucursal (nombre) VALUES (:nombre)");
        $stmt->execute([':nombre' => $nombre]);

        echo json_encode(["status" => "success", "message" => "Sucursal agregada correctamente."]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al guardar la sucursal: " . $e->getMessage()]);
}
