<?php
// backend/usuarios/listar_usuarios.php
// este archivo permite listtar a todos los usuarios (admin)

// iniciamos la sesion y establecemos el protocolo en JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// incluimos la conexion a la db y las funciones de encriptacion
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../seguridad/encriptar.php';

// verificamos que el usuario sea admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Acceso denegado. Se requiere rol de administrador."]);
    exit;
}

// si la peticion en GET devolvemos los usuarios
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Obtenemos usuarios junto con los datos de baja si existen
        $sql = "SELECT 
                    u.id, 
                    u.usuario, 
                    u.rol, 
                    u.fechaDeContrato,
                    b.fecha AS fecha_baja,
                    b.tipo AS tipo_baja,
                    IF(b.id IS NOT NULL, 1, 0) AS dado_de_baja
                FROM Usuarios u
                LEFT JOIN Baja b ON u.id = b.idUsuario
                ORDER BY dado_de_baja ASC, u.usuario ASC";

        $stmt = $con->prepare($sql);
        $stmt->execute();
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

echo json_encode(["status" => "error", "message" => "Método no permitido."]);