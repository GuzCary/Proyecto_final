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
$idSucursal = $_POST['idSucursal'] ?? '';
$marca = $_POST['marca'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$modelo = $_POST['modelo'] ?? '';
$potencia = $_POST['potencia'] ?? '';
$estado = $_POST['estado'] ?? '';
$enlaceDocOficial = $_POST['enlaceDocOficial'] ?? '';
$consumo = $_POST['consumo'] ?? '';
$patente = $_POST['patente'] ?? '';
$seguroSOA = $_POST['seguroSOA'] ?? '';
$seguroTerceros = $_POST['seguroTerceros'] ?? '';
$seguroTotal = $_POST['seguroTotal'] ?? '';
$anio = $_POST['anio'] ?? '';
$km = $_POST['km'] ?? '';
$precioMinimo = $_POST['precioMinimo'] ?? '';
$precio = $_POST['precio'] ?? '';
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
    if (!empty($categorias)) {
        $stmtTiene = $con->prepare("INSERT INTO Tiene (idVehiculo, idCategoria) VALUES (:idVehiculo, :idCategoria)");

        foreach ($categorias as $idCategoria) {
            $stmtTiene->execute([
                ':idVehiculo' => $idVehiculo,
                ':idCategoria' => $idCategoria
            ]);
        }
    }

    echo json_encode(["status" => "success", "message" => "Vehiculo agregado correctamente."]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al guardar el vehiculo: " . $e->getMessage()]);
}
