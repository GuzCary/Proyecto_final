<?php
// backend/modificar_vehiculo.php
// Este archivo permite modificar un vehiculo ya existente
// Solo puede ser usado por un usuario administrador

session_start();
header("Content-Type: application/json; charset=UTF-8");

// incluimos la conexion a la db
require_once __DIR__ . '/../config/conexion.php';

// incluimos el archivo de encriptacion
require_once __DIR__ . '/encriptar.php'; 

// Verificamos que el usuario este logueado y sea administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'user') {
    echo json_encode(["status" => "error", "message" => "Acceso denegado. Se requiere rol de vendedor."]);
    exit;
}

// Verificamos que la peticion sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Metodo no permitido."]);
    exit;
}



// recibimos y desencriptamos el id del vehiclo
$idEncriptado = $_POST['id'] ?? '';
$id = desencriptar($idEncriptado);

// Si no se pudo desencriptar o vino vacío, cortamos
if (!$id) {
    echo json_encode(["status" => "error", "message" => "El identificador del vehículo no es válido."]);
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











try {
    // Actualizamos usando el id desencriptado
    $stmt = $con->prepare("
        UPDATE Vehiculo SET
            idSucursal = :idSucursal,
            marca = :marca,
            descripcion = :descripcion,
            modelo = :modelo,
            potencia = :potencia,
            estado = :estado,
            enlaceDocOficial = :enlaceDocOficial,
            consumo = :consumo,
            patente = :patente,
            seguroSOA = :seguroSOA,
            seguroTerceros = :seguroTerceros,
            seguroTotal = :seguroTotal,
            anio = :anio,
            km = :km,
            precioMinimo = :precioMinimo,
            precio = :precio
        WHERE id = :id
    ");

    //iniciamos una transaccion por la seguridad de los datos
    $con->beginTransaction();

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
        ':precio' => $precio,
        ':id' => $id
    ]);

    // Si se mandaron categorías, actualizamos la tabla 'Tiene'
    if (!empty($categorias) && is_array($categorias)) {
        // Borramos las relaciones viejas
        $stmtDel = $con->prepare("DELETE FROM Tiene WHERE idVehiculo = :idVehiculo");
        $stmtDel->execute([':idVehiculo' => $id]);

        // Insertamos las nuevas
        $stmtIns = $con->prepare("INSERT INTO Tiene (idVehiculo, idCategoria) VALUES (:idVehiculo, :idCategoria)");
        foreach ($categorias as $idCat) {
            $stmtIns->execute([
                ':idVehiculo' => $id,
                ':idCategoria' => $idCat
            ]);
        }
    }

    echo json_encode([
        "status" => "success",
        "message" => "Vehículo modificado con éxito."
    ]);

    $con->commit(); 

  

} catch (PDOException $e) {
    $con->rollBack(); // Si hay fallos volvemos a los datos anteriores
    
    echo json_encode(["status" => "error", "message" => "Error: " . $e->getMessage()]);
}