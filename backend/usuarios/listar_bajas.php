<?php
// backend/usuarios/listar_bajas.php
// Este archivo lista el historial de usuarios dados de baja (admin)

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

// Verificamos que la peticion sea GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(["status" => "error", "message" => "Metodo no permitido."]);
    exit;
}

try {
    // traemos las bajas junto con los datos del usuario
    // ademas avisamos si el nombre ya lo usa otro usuario activo (para saber si se puede reactivar)
    $sql = "SELECT
                b.id AS idBaja,
                b.tipo,
                b.fecha AS fechaBaja,
                u.id AS idUsuario,
                u.usuario,
                u.rol,
                u.fechaDeContrato,
                s.nombre AS sucursal,
                (SELECT COUNT(*)
                   FROM Usuarios u2
                   LEFT JOIN Baja b2 ON u2.id = b2.idUsuario
                  WHERE u2.usuario = u.usuario AND u2.id != u.id AND b2.id IS NULL) AS nombreEnUso
            FROM Baja b
            INNER JOIN Usuarios u ON b.idUsuario = u.id
            LEFT JOIN Sucursal s ON u.idSucursal = s.id
            ORDER BY b.fecha DESC, b.id DESC";

    $stmt = $con->prepare($sql);
    $stmt->execute();
    $bajas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // encriptamos los ids antes de enviarlos al frontend
    $resultado = [];
    foreach ($bajas as $baja) {
        $resultado[] = [
            "id_usuario"      => encriptar($baja['idUsuario']),
            "usuario"         => $baja['usuario'],
            "rol"             => $baja['rol'],
            "sucursal"        => $baja['sucursal'],
            "fechaDeContrato" => $baja['fechaDeContrato'],
            "fechaBaja"       => $baja['fechaBaja'],
            "motivo"          => $baja['tipo'],
            "nombreEnUso"     => (int)$baja['nombreEnUso'] > 0
        ];
    }

    echo json_encode([
        "status" => "success",
        "bajas" => $resultado
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al obtener las bajas: " . $e->getMessage()]);
}
