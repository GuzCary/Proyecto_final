<?php
// backend/vehiculos/vehiculo.php
// Este archivo permite agregar vehiculos al inventario (admin)

// iniciamos la sesion y establecemos el protocolo en JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// incluimos la conexion a la db y las funciones compartidas de vehiculos
// (datos_vehiculo.php incluye encriptacion y sanitizacion)
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/datos_vehiculo.php';

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

try {
    // Recibimos los datos del formulario y los validamos
    $resultado = leerDatosVehiculo($con);
    if (isset($resultado['error'])) {
        echo json_encode(["status" => "error", "message" => $resultado['error']]);
        exit;
    }

    // iniciamos una transaccion: si falla alguna categoria no queda el vehiculo a medias
    $con->beginTransaction();

    // insertamos el vehiculo en la tabla Vehiculo
    $stmt = $con->prepare("INSERT INTO Vehiculo (
        idSucursal, marca, descripcion, modelo, potencia, estado, enlaceDocOficial,
        consumo, patente, seguroSOA, seguroTerceros, seguroTotal, anio, km,
        precioMinimo, precio
    ) VALUES (
        :idSucursal, :marca, :descripcion, :modelo, :potencia, :estado, :enlaceDocOficial,
        :consumo, :patente, :seguroSOA, :seguroTerceros, :seguroTotal, :anio, :km,
        :precioMinimo, :precio
    )");
    $stmt->execute($resultado['datos']);

    // Obtenemos el ID del vehiculo recien insertado
    $idVehiculo = $con->lastInsertId();

    // Asociamos las categorias elegidas en "Tiene"
    guardarCategoriasVehiculo($con, $idVehiculo, $resultado['categorias']);

    // confirmamos los datos antes de mover las imagenes al disco
    $con->commit();

    // Si se subieron imagenes, las guardamos con el formato id_numero.ext
    guardarImagenesSubidas($idVehiculo, 0);

    echo json_encode(["status" => "success", "message" => "Vehiculo agregado correctamente."]);

} catch (PDOException $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Error al guardar el vehiculo: " . $e->getMessage()]);
}
