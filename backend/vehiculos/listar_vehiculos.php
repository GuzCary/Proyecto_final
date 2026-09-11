<?php
// backend/vehiculos/listar_vehiculos.php
// Este endpoint devuelve la informacion de todos los vehiculos del inventario (publico)



// establecemos el protocolo en JSON
header("Content-Type: application/json; charset=UTF-8");

// incuimos la conexion a la db y las funciones de encriptacion
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../seguridad/encriptar.php';

try {
    // Consultamos todos los vehiculos con el nombre de la sucursal
    $stmt = $con->prepare("
    SELECT
        Vehiculo.*,
        Sucursal.nombre AS sucursal,
        IF(Ventas.idVenta IS NOT NULL, 1, 0) AS vendido
    FROM Vehiculo
    INNER JOIN Sucursal ON Vehiculo.idSucursal = Sucursal.id
    LEFT JOIN Ventas ON Vehiculo.id = Ventas.idVehiculo
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

        // Buscamos las imagenes del vehiculo con formato id_numero.jpg (lo hace la expresion REGEX)
        $vehiculo['imagenes'] = [];
        $imgDir = __DIR__ . '/../../img/';
        if (is_dir($imgDir)) {
            $archivos = scandir($imgDir);
            foreach ($archivos as $archivo) {
                if (preg_match('/^' . $vehiculo['id'] . '_(\d+)\.[a-zA-Z]+$/', $archivo)) {
                    $vehiculo['imagenes'][] = $archivo;
                }
            }
            sort($vehiculo['imagenes']);
        }

        $vehiculo['id'] = encriptar($vehiculo['id']);
    }
    unset($vehiculo);
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
