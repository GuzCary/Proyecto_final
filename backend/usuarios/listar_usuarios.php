<?php
// backend/usuarios/listar_usuarios.php
// Este archivo permite listar los usuarios del sistema (admin)

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

// Si la peticion es GET, devolvemos todos los usuarios (sin contraseñas)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // solo mostramos id, usuario, rol y fechaDeContrato
        $stmt = $con->prepare("SELECT id, usuario, rol, fechaDeContrato FROM Usuarios ORDER BY usuario ASC");
        $stmt->execute();
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Encriptamos los IDs antes de enviarlos al frontend
        foreach ($usuarios as &$usuario) {
            $usuario['id'] = encriptar($usuario['id']);
        }
        unset($usuario); 

        echo json_encode([
            "status" => "success",
            "usuarios" => $usuarios
        ]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Error al obtener usuarios: " . $e->getMessage()]);
    }
    exit;
}

// Si llega otro metodo, respondemos error
echo json_encode(["status" => "error", "message" => "Metodo no permitido."]);