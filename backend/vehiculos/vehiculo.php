<?php
// backend/vehiculos/vehiculo.php
// Este archivo permite agregar vehiculos al inventario (admin)

// iniciamos la sesion y establecemos el protocolo en JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// incuimos la conexion a la db y las funciones de sanitizacion
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

// Recibimos los datos del formulario y los verificamos
// la sucursal llega encriptada desde el select (cargado con listar_sucursales.php)
$idSucursal = desencriptar($_POST['idSucursal'] ?? '');
if ($idSucursal === false || $idSucursal === '') {
    $idSucursal = null;
}
$marca = sanitizar($_POST['marca'] ?? null);
$descripcion = sanitizar($_POST['descripcion'] ?? null);
$modelo = sanitizar($_POST['modelo'] ?? null);
$potencia = validarEntero($_POST['potencia'] ?? null);
$estado = validarEntero($_POST['estado'] ?? null);
$enlaceDocOficial = sanitizar($_POST['enlaceDocOficial'] ?? null);
$consumo = validarFloat($_POST['consumo'] ?? null);
$patente = validarFloat($_POST['patente'] ?? null);
$seguroSOA = validarFloat($_POST['seguroSOA'] ?? null);
$seguroTerceros = validarFloat($_POST['seguroTerceros'] ?? null);
$seguroTotal = validarFloat($_POST['seguroTotal'] ?? null);
$anio = validarEntero($_POST['anio'] ?? null);
$km = validarEntero($_POST['km'] ?? null);
$precioMinimo = validarFloat($_POST['precioMinimo'] ?? null);
$precio = validarFloat($_POST['precio'] ?? null);
$categorias = sanitizarArray($_POST['categorias'] ?? []);

// Validamos campos obligatorios
if (empty($idSucursal) || empty($marca) || empty($modelo) || empty($precio)) {
    echo json_encode(["status" => "error", "message" => "Completa los campos obligatorios: sucursal, marca, modelo y precio."]);
    exit;
}

try {
    // iniciamos una transaccion: si falla alguna categoria no queda el vehiculo a medias
    $con->beginTransaction();

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

    // confirmamos los datos antes de mover las imagenes al disco
    $con->commit();

    // Si se subieron imagenes, las guardamos con formato
    if (isset($_FILES["files"]) && is_array($_FILES["files"]["name"])) {
        $imgDir = __DIR__ . "/../../img/";
        $cantidad = count($_FILES["files"]["name"]);

        for ($i = 0; $i < $cantidad; $i++) {

            $archivoActual = [
                'tmp_name' => $_FILES["files"]["tmp_name"][$i],
                'error'    => $_FILES["files"]["error"][$i]
            ];

            // validamos la imagen y obtenemos su extension
            $extension = validarImagen($archivoActual);

           // si hay algun error salteamos esa imagen
            if ($extension === false) {
                continue;
            }

            // si todo sale bien armamos el nombre y guardamos la imagen
            $numero = $i + 1;
            $to = $imgDir . $idVehiculo . "_" . $numero . "." . $extension;
            move_uploaded_file($archivoActual['tmp_name'], $to);
        }
    }

    echo json_encode(["status" => "success", "message" => "Vehiculo agregado correctamente."]);

} catch (PDOException $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Error al guardar el vehiculo: " . $e->getMessage()]);
}
