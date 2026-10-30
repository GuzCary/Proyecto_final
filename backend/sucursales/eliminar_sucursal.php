<?php
// backend/sucursales/eliminar_sucursal.php
// Este archivo permite eliminar una sucursal (admin)
// Solo se puede eliminar si no tiene vehiculos, usuarios ni transacciones asociadas,
// asi evitamos errores de clave foranea en MySQL y no perdemos informacion

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

// Verificamos que la peticion sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Metodo no permitido."]);
    exit;
}

// Recibimos el ID encriptado de la sucursal a eliminar
$idEncriptado = $_POST['id_encriptado'] ?? '';

if (empty($idEncriptado)) {
    echo json_encode(["status" => "error", "message" => "ID de sucursal no proporcionado."]);
    exit;
}

$id = desencriptarId($idEncriptado);
if ($id === false || $id === '') {
    echo json_encode(["status" => "error", "message" => "ID de sucursal invalido."]);
    exit;
}

try {
    // iniciamos la transaccion para que nadie agregue registros mientras verificamos
    $con->beginTransaction();

    // bloqueamos la fila de la sucursal hasta terminar
    $stmtSuc = $con->prepare("SELECT nombre FROM Sucursal WHERE id = :id FOR UPDATE");
    $stmtSuc->execute([':id' => $id]);
    $sucursal = $stmtSuc->fetch(PDO::FETCH_ASSOC);

    if (!$sucursal) {
        $con->rollBack();
        echo json_encode(["status" => "error", "message" => "No se encontro la sucursal."]);
        exit;
    }

    // contamos los registros que dependen de esta sucursal
    $dependencias = [
        'vehiculo(s)'     => "SELECT COUNT(*) FROM Vehiculo WHERE idSucursal = :id",
        'usuario(s)'      => "SELECT COUNT(*) FROM Usuarios WHERE idSucursal = :id",
        'transaccion(es)' => "SELECT COUNT(*) FROM Transacciones WHERE idSucursal = :id"
    ];

    $motivos = [];
    foreach ($dependencias as $nombre => $sql) {
        $stmt = $con->prepare($sql);
        $stmt->execute([':id' => $id]);
        $cantidad = (int)$stmt->fetchColumn();
        if ($cantidad > 0) {
            $motivos[] = $cantidad . ' ' . $nombre;
        }
    }

    // si tiene registros asociados no la borramos y explicamos por que
    if (!empty($motivos)) {
        $con->rollBack();
        echo json_encode([
            "status" => "error",
            "message" => "No se puede eliminar '{$sucursal['nombre']}' porque tiene asociados: " . implode(', ', $motivos) . ". Reasignalos a otra sucursal primero."
        ]);
        exit;
    }

    // si no tiene dependencias la eliminamos
    $stmtDel = $con->prepare("DELETE FROM Sucursal WHERE id = :id");
    $stmtDel->execute([':id' => $id]);

    $con->commit();
    echo json_encode(["status" => "success", "message" => "Sucursal eliminada correctamente."]);

} catch (PDOException $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Error al eliminar la sucursal: " . $e->getMessage()]);
}
