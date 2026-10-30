<?php
// backend/sanciones/listar_sanciones.php
// Este archivo lista el historial de sanciones (admin)

// iniciamos la sesion y establecemos el protocolo en JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// incluimos la conexion a la db
require_once __DIR__ . '/../config/conexion.php';

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
    // traemos el nombre del empleado sancionado y del administrador que sanciono
    $sql = "SELECT
                s.fecha,
                s.tipo,
                s.motivo,
                s.consecuencia,
                emp.usuario AS empleado,
                adm.usuario AS administrador
            FROM Sanciona s
            INNER JOIN Usuarios emp ON s.idUsuario = emp.id
            INNER JOIN Usuarios adm ON s.idAdministrador = adm.id
            ORDER BY s.fecha DESC, s.idSancion DESC";

    $stmt = $con->prepare($sql);
    $stmt->execute();
    $sanciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "sanciones" => $sanciones
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al obtener las sanciones: " . $e->getMessage()]);
}
