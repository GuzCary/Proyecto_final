<?php
// backend/listar_vehiculos.php
// Este endpoint devuelve la informacion de todos los vehiculos del inventario
// Es publico



header("Content-Type: application/json; charset=UTF-8");
// incuimos la conexion a la db
require_once __DIR__ . '/../config/conexion.php';

// incluimos el archivo de encriptacion
require_once __DIR__ . '/encriptar.php'; 

try {
    // Consultamos todos los vehiculos con el nombre de la sucursal
    $stmt = $con->prepare("
        SELECT
            Vehiculo.id,
            Vehiculo.marca,
            Vehiculo.modelo,
            Vehiculo.descripcion,
            Vehiculo.patente,
            Vehiculo.seguroSOA,
            Vehiculo.seguroTerceros,
            Vehiculo.seguroTotal,
            Vehiculo.anio,
            Vehiculo.km,
            Vehiculo.precio,
            Vehiculo.precioMinimo,
            Vehiculo.potencia,
            Vehiculo.consumo,
            Vehiculo.estado,
            Vehiculo.enlaceDocOficial,
            Sucursal.nombre AS sucursal
        FROM Vehiculo
        INNER JOIN Sucursal ON Vehiculo.idSucursal = Sucursal.id
        ORDER BY Vehiculo.id DESC
    ");
    $stmt->execute();
    $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Para cada vehiculo, obtenemos sus categorias asociadas, y las guardamos en el arreglo de vehiculos
    foreach ($vehiculos as &$vehiculo) {
        $stmtCat = $con->prepare("
            SELECT Categoria.id, Categoria.nombre
            FROM Tiene
            INNER JOIN Categoria ON Tiene.idCategoria = Categoria.id
            WHERE Tiene.idVehiculo = :idVehiculo
        ");
        $stmtCat->execute([':idVehiculo' => $vehiculo['id']]);
        $vehiculo['categorias'] = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
        $vehiculo['id'] = encriptar($vehiculo['id']);
    }
    
    //devolvemos los datos obtenidos
    echo json_encode([
        "status" => "success",
        "cantidad" => count($vehiculos),
        "vehiculos" => $vehiculos
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error al obtener vehiculos: " . $e->getMessage()
    ]);
}
