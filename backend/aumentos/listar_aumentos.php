<?php
// backend/aumentos/listar_aumentos.php
// Este archivo lista el historial de aumentos de sueldo (admin)

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
    // traemos el empleado, el admin que lo otorgo y el salario actual del empleado
    $sql = "SELECT
                a.fecha,
                a.tipo,
                a.valor,
                emp.usuario AS empleado,
                emp.salarioPorHora AS salarioActual,
                adm.usuario AS administrador
            FROM AumentosDeSueldo a
            INNER JOIN Usuarios emp ON a.idUsuario = emp.id
            INNER JOIN Usuarios adm ON a.idAdministrador = adm.id
            ORDER BY a.fecha DESC, a.id DESC";

    $stmt = $con->prepare($sql);
    $stmt->execute();
    $aumentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // convertimos los numeros para que el frontend no reciba texto
    foreach ($aumentos as &$aumento) {
        $aumento['valor'] = (float)$aumento['valor'];
        $aumento['salarioActual'] = $aumento['salarioActual'] !== null ? (float)$aumento['salarioActual'] : null;
    }
    unset($aumento);

    echo json_encode([
        "status" => "success",
        "aumentos" => $aumentos
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al obtener los aumentos: " . $e->getMessage()]);
}
