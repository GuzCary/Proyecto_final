<?php
// backend/vehiculos.php
// Este archivo permite agregar vehiculos al inventario
// Solo puede ser usado por un usuario autenticado con rol de administrador

session_start();
header("Content-Type: application/json; charset=UTF-8");

// incuimos la conexion a la db
require_once __DIR__ . '/../config/conexion.php';

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

// Recibimos los datos del formulario
$idSucursal = $_POST['idSucursal'] ?? 1;
$marca = $_POST['marca'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$modelo = $_POST['modelo'] ?? '';
$potencia = $_POST['potencia'] ?? 0;
$estado = $_POST['estado'] ?? 0;
$enlaceDocOficial = $_POST['enlaceDocOficial'] ?? '';
$consumo = $_POST['consumo'] ?? 0;
$patente = $_POST['patente'] ?? 0;
$seguroSOA = $_POST['seguroSOA'] ?? 0;
$seguroTerceros = $_POST['seguroTerceros'] ?? 0;
$seguroTotal = $_POST['seguroTotal'] ?? 0;
$anio = $_POST['anio'] ?? 0;
$km = $_POST['km'] ?? 0;
$precioMinimo = $_POST['precioMinimo'] ?? 0;
$precio = $_POST['precio'] ?? 0;
$categorias = $_POST['categorias'] ?? [];

// Validamos campos obligatorios
if (empty($idSucursal) || empty($marca) || empty($modelo) || empty($precio)) {
    echo json_encode(["status" => "error", "message" => "Completa los campos obligatorios: sucursal, modelo y precio."]);
    exit;
}

try {
    // insertamos el vehiculo en la tabla Vehiculo
    // anio esta asi porque no me lo aceptaba
    $stmt = $con->prepare("INSERT INTO Vehiculo (
        idSucursal, marca, descripcion, modelo, potencia, estado, enlaceDocOficial,
        consumo, patente, seguroSOA, seguroTerceros, seguroTotal, anio, km,
        precioMinimo, precio
    ) VALUES (
        :idSucursal, :marca, :descripcion, :modelo, :potencia, :estado, :enlaceDocOficial,
        :consumo, :patente, :seguroSOA, :seguroTerceros, :seguroTotal, :anio, :km,
        :precioMinimo, :precio
    )");

    $stmt->execute([
        ':idSucursal' => $idSucursal,
        ':marca' => $marca,
        ':descripcion' => $descripcion,
        ':modelo' => $modelo,
        ':potencia' => $potencia,
        ':estado' => $estado,
        ':enlaceDocOficial' => $enlaceDocOficial,
        ':consumo' => $consumo,
        ':patente' => $patente,
        ':seguroSOA' => $seguroSOA,
        ':seguroTerceros' => $seguroTerceros,
        ':seguroTotal' => $seguroTotal,
        ':anio' => $anio,
        ':km' => $km,
        ':precioMinimo' => $precioMinimo,
        ':precio' => $precio
    ]);

    // Obtenemos el ID del vehiculo recien insertado
    $idVehiculo = $con->lastInsertId();

    // Si se seleccionaron categorias, las asociamos al vehiculo en "Tiene"
    if (!empty($categorias) && is_array($categorias)) {
        $stmtTiene = $con->prepare("INSERT INTO Tiene (idVehiculo, idCategoria) VALUES (:idVehiculo, :idCategoria)");

        foreach ($categorias as $idCategoria) {
            $stmtTiene->execute([
                ':idVehiculo' => $idVehiculo,
                ':idCategoria' => $idCategoria
            ]);
        }
    }

    // Si se subieron imagenes, las guardamos con formato idVehiculo_numero.ext
    if (isset($_FILES["files"]) && is_array($_FILES["files"]["name"])) {
        $imgDir = __DIR__ . "/../img/";
        if (!is_dir($imgDir)) {
            mkdir($imgDir, 0755, true);
        }

        $extensionesPermitidas = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp",
            "image/gif" => "gif"
        ];

        $cantidad = count($_FILES["files"]["name"]);
        for ($i = 0; $i < $cantidad; $i++) {
            if ($_FILES["files"]["error"][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $tmpName = $_FILES["files"]["tmp_name"][$i];
            $info = getimagesize($tmpName);

            if ($info === false || !isset($extensionesPermitidas[$info["mime"]])) {
                continue;
            }

            $extension = $extensionesPermitidas[$info["mime"]];
            $numero = $i + 1;
            $to = $imgDir . $idVehiculo . "_" . $numero . "." . $extension;
            move_uploaded_file($tmpName, $to);
        }
    }

    echo json_encode(["status" => "success", "message" => "Vehiculo agregado correctamente."]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al guardar el vehiculo: " . $e->getMessage()]);
}
