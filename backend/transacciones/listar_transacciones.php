<?php
// backend/transacciones/listar_transacciones.php
// Este archivo lista los movimientos de caja con sus totales (admin)
// Se puede filtrar opcionalmente por sucursal y por rango de fechas (GET)

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

// Verificamos que la peticion sea GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(["status" => "error", "message" => "Metodo no permitido."]);
    exit;
}

// filtros opcionales
$sucursalInput = $_GET['sucursal'] ?? '';
$desde = validarFecha($_GET['desde'] ?? '');
$hasta = validarFecha($_GET['hasta'] ?? '');

try {
    $sql = "SELECT
                t.fecha,
                t.tipo,
                t.monto,
                t.justificacion,
                s.nombre AS sucursal,
                u.usuario AS funcionario
            FROM Transacciones t
            INNER JOIN Sucursal s ON t.idSucursal = s.id
            INNER JOIN Usuarios u ON t.idFuncionario = u.id
            WHERE 1=1";
    $params = [];

    if ($sucursalInput !== '') {
        $idSucursal = desencriptarId($sucursalInput);
        if ($idSucursal === false || $idSucursal === '') {
            echo json_encode(["status" => "error", "message" => "Sucursal invalida."]);
            exit;
        }
        $sql .= " AND t.idSucursal = :idSucursal";
        $params[':idSucursal'] = $idSucursal;
    }

    if ($desde !== null) {
        $sql .= " AND t.fecha >= :desde";
        $params[':desde'] = $desde;
    }

    if ($hasta !== null) {
        $sql .= " AND t.fecha <= :hasta";
        $params[':hasta'] = $hasta;
    }

    $sql .= " ORDER BY t.fecha DESC, t.idTransaccion DESC";

    $stmt = $con->prepare($sql);
    $stmt->execute($params);
    $transacciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // calculamos los totales del listado filtrado
    $ingresos = 0;
    $egresos = 0;
    foreach ($transacciones as &$t) {
        $t['monto'] = (float)$t['monto'];
        if ($t['tipo'] === 'Ingreso') {
            $ingresos += $t['monto'];
        } else {
            $egresos += $t['monto'];
        }
    }
    unset($t);

    echo json_encode([
        "status" => "success",
        "transacciones" => $transacciones,
        "totales" => [
            "ingresos" => round($ingresos, 2),
            "egresos"  => round($egresos, 2),
            "balance"  => round($ingresos - $egresos, 2)
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al obtener las transacciones: " . $e->getMessage()]);
}
