<?php
// backend/licencias/registrar_licencia.php
// Este archivo registra o actualiza la licencia de conducir de un funcionario (admin)
// Si el funcionario no tiene licencia se crea una nueva y se le asigna.
// Si ya tiene una propia se actualiza. Si la comparte con otro funcionario
// (pasaba con los datos de prueba viejos) se le crea una nueva para no modificar la del otro.

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
$idEncriptado = $_POST['id_usuario'] ?? '';
$tipo = sanitizar($_POST['tipo'] ?? '');
$fechaVencimiento = validarFecha($_POST['fechaVencimiento'] ?? '');

if (empty($idEncriptado) || empty($tipo) || $fechaVencimiento === null) {
    echo json_encode(["status" => "error", "message" => "Completa todos los campos: funcionario, categoria y fecha de vencimiento valida."]);
    exit;
}

if (mb_strlen($tipo) > 100) {
    echo json_encode(["status" => "error", "message" => "La categoria no puede superar los 100 caracteres."]);
    exit;
}

$idUsuario = desencriptar($idEncriptado);
if ($idUsuario === false || $idUsuario === '') {
    echo json_encode(["status" => "error", "message" => "ID de funcionario invalido."]);
    exit;
}

try {
    $con->beginTransaction();

    // verificamos que sea un funcionario activo y traemos su licencia actual
    $stmt = $con->prepare("SELECT u.usuario, f.idLicenciaDeConducir
                           FROM Funcionario f
                           INNER JOIN Usuarios u ON f.id = u.id
                           LEFT JOIN Baja b ON u.id = b.idUsuario
                           WHERE f.id = :id AND b.id IS NULL AND u.rol IN ('admin', 'user')
                           FOR UPDATE");
    $stmt->execute([':id' => $idUsuario]);
    $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$funcionario) {
        $con->rollBack();
        echo json_encode(["status" => "error", "message" => "El funcionario no existe, no es admin/vendedor o esta dado de baja."]);
        exit;
    }

    $idLicencia = $funcionario['idLicenciaDeConducir'];

    // nos fijamos si la licencia actual la usa solo este funcionario
    $licenciaPropia = false;
    if ($idLicencia !== null) {
        $stmtUso = $con->prepare("SELECT COUNT(*) FROM Funcionario WHERE idLicenciaDeConducir = :idLicencia");
        $stmtUso->execute([':idLicencia' => $idLicencia]);
        $licenciaPropia = (int)$stmtUso->fetchColumn() === 1;
    }

    if ($licenciaPropia) {
        // actualizamos su licencia
        $stmtUpd = $con->prepare("UPDATE LicenciaDeConducir SET tipo = :tipo, fechaVencimiento = :fecha WHERE id = :id");
        $stmtUpd->execute([':tipo' => $tipo, ':fecha' => $fechaVencimiento, ':id' => $idLicencia]);
    } else {
        // creamos una licencia nueva y se la asignamos
        $stmtIns = $con->prepare("INSERT INTO LicenciaDeConducir (tipo, fechaVencimiento) VALUES (:tipo, :fecha)");
        $stmtIns->execute([':tipo' => $tipo, ':fecha' => $fechaVencimiento]);
        $nuevaLicencia = $con->lastInsertId();

        $stmtAsig = $con->prepare("UPDATE Funcionario SET idLicenciaDeConducir = :idLicencia WHERE id = :id");
        $stmtAsig->execute([':idLicencia' => $nuevaLicencia, ':id' => $idUsuario]);
    }

    $con->commit();

    $aviso = $fechaVencimiento < date('Y-m-d') ? ' Atencion: la licencia ya esta vencida.' : '';
    echo json_encode([
        "status" => "success",
        "message" => "Licencia de '{$funcionario['usuario']}' guardada correctamente." . $aviso
    ]);

} catch (PDOException $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Error al guardar la licencia: " . $e->getMessage()]);
}
