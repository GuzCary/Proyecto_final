<?php
// backend/licencias/listar_licencias.php
// Este archivo lista los funcionarios activos (admins y vendedores) con su licencia de conducir (admin)

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
    // solo los usuarios con rol admin o user son funcionarios (los de limpieza no manejan)
    $sql = "SELECT
                u.id,
                u.usuario,
                u.rol,
                l.tipo,
                l.fechaVencimiento
            FROM Funcionario f
            INNER JOIN Usuarios u ON f.id = u.id
            LEFT JOIN LicenciaDeConducir l ON f.idLicenciaDeConducir = l.id
            LEFT JOIN Baja b ON u.id = b.idUsuario
            WHERE b.id IS NULL AND u.rol IN ('admin', 'user')
            ORDER BY u.usuario ASC";

    $stmt = $con->prepare($sql);
    $stmt->execute();
    $funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // encriptamos los ids y calculamos el estado de la licencia
    $hoy = date('Y-m-d');
    $resultado = [];
    foreach ($funcionarios as $f) {
        if ($f['fechaVencimiento'] === null) {
            $estado = 'sin_licencia';
        } elseif ($f['fechaVencimiento'] < $hoy) {
            $estado = 'vencida';
        } elseif ($f['fechaVencimiento'] <= date('Y-m-d', strtotime('+30 days'))) {
            $estado = 'por_vencer';
        } else {
            $estado = 'vigente';
        }

        $resultado[] = [
            "id"               => encriptar($f['id']),
            "usuario"          => $f['usuario'],
            "rol"              => $f['rol'],
            "tipo"             => $f['tipo'],
            "fechaVencimiento" => $f['fechaVencimiento'],
            "estado"           => $estado
        ];
    }

    echo json_encode([
        "status" => "success",
        "funcionarios" => $resultado
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al obtener las licencias: " . $e->getMessage()]);
}
