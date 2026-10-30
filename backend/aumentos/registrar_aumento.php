<?php
// backend/aumentos/registrar_aumento.php
// Este archivo permite a un administrador registrar un aumento de sueldo (admin)
// Ademas de guardar el aumento en AumentosDeSueldo, actualiza el salarioPorHora del usuario

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

// recibimos y validamos los datos
// tipo: 'Porcentaje' (ej: 10 = +10%) o 'Monto fijo' (ej: 50 = +$50 por hora)
$idEncriptado = $_POST['id_usuario'] ?? '';
$tipo = sanitizar($_POST['tipo'] ?? '');
$valor = validarFloat($_POST['valor'] ?? null);
// un monto fijo no puede superar $100.000 por hora (evita errores de tipeo)
if ($valor !== null && $valor > 100000) {
    echo json_encode(["status" => "error", "message" => "El valor del aumento es demasiado alto."]);
    exit;
}

if (empty($idEncriptado) || empty($tipo) || $valor === null) {
    echo json_encode(["status" => "error", "message" => "Completa todos los campos: empleado, tipo y valor."]);
    exit;
}

if (!in_array($tipo, ['Porcentaje', 'Monto fijo'])) {
    echo json_encode(["status" => "error", "message" => "Tipo de aumento no valido."]);
    exit;
}

if ($valor <= 0) {
    echo json_encode(["status" => "error", "message" => "El valor del aumento tiene que ser mayor a 0."]);
    exit;
}

if ($tipo === 'Porcentaje' && $valor > 100) {
    echo json_encode(["status" => "error", "message" => "El porcentaje no puede superar el 100%."]);
    exit;
}

$idUsuario = desencriptarId($idEncriptado);
if ($idUsuario === false || $idUsuario === '') {
    echo json_encode(["status" => "error", "message" => "ID de empleado invalido."]);
    exit;
}

// un administrador no se puede dar un aumento a si mismo
if ($idUsuario == $_SESSION['usuario_id']) {
    echo json_encode(["status" => "error", "message" => "No podes otorgarte un aumento a vos mismo."]);
    exit;
}

try {
    $con->beginTransaction();

    // buscamos el empleado activo y bloqueamos su fila mientras calculamos el nuevo salario
    $stmt = $con->prepare("SELECT u.usuario, u.salarioPorHora
                           FROM Usuarios u
                           LEFT JOIN Baja b ON u.id = b.idUsuario
                           WHERE u.id = :id AND b.id IS NULL
                           FOR UPDATE");
    $stmt->execute([':id' => $idUsuario]);
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$empleado) {
        $con->rollBack();
        echo json_encode(["status" => "error", "message" => "El empleado no existe o esta dado de baja."]);
        exit;
    }

    if ($empleado['salarioPorHora'] === null) {
        $con->rollBack();
        echo json_encode([
            "status" => "error",
            "message" => "'{$empleado['usuario']}' no tiene salario por hora cargado. Asignalo primero desde Usuarios."
        ]);
        exit;
    }

    // calculamos el nuevo salario
    $salarioAnterior = (float)$empleado['salarioPorHora'];
    if ($tipo === 'Porcentaje') {
        $salarioNuevo = $salarioAnterior * (1 + $valor / 100);
    } else {
        $salarioNuevo = $salarioAnterior + $valor;
    }
    $salarioNuevo = round($salarioNuevo, 2);

    // guardamos el aumento, el administrador es el que tiene la sesion iniciada
    $stmtIns = $con->prepare("INSERT INTO AumentosDeSueldo (idUsuario, idAdministrador, tipo, valor, fecha)
                              VALUES (:idUsuario, :idAdmin, :tipo, :valor, CURDATE())");
    $stmtIns->execute([
        ':idUsuario' => $idUsuario,
        ':idAdmin'   => $_SESSION['usuario_id'],
        ':tipo'      => $tipo,
        ':valor'     => $valor
    ]);

    // actualizamos el salario del usuario
    $stmtUpd = $con->prepare("UPDATE Usuarios SET salarioPorHora = :salario WHERE id = :id");
    $stmtUpd->execute([':salario' => $salarioNuevo, ':id' => $idUsuario]);

    $con->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Aumento registrado. Salario de '{$empleado['usuario']}': $" . $salarioAnterior . " -> $" . $salarioNuevo . " por hora."
    ]);

} catch (PDOException $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Error al registrar el aumento: " . $e->getMessage()]);
}
