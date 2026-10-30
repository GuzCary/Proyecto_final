<?php
// backend/vehiculos/listar_vehiculos.php
// Este endpoint devuelve la informacion de todos los vehiculos del inventario (publico)

// iniciamos la sesion y verificamos si es un usuario logueado
session_start();
$UsuarioLog = isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['admin', 'user']);

// establecemos el protocolo en JSON
header("Content-Type: application/json; charset=UTF-8");

// incluimos la conexion a la db, funciones de encriptacion y sanitizacion
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../seguridad/encriptar.php';
require_once __DIR__ . '/../seguridad/sanitizar.php';

// Recibimos los filtros opcionales enviados por GET
$busqueda = sanitizar($_GET['busqueda'] ?? '');
$precioMax = validarFloat($_GET['precio_max'] ?? null);
$categoriaInput = sanitizar($_GET['categoria'] ?? '');

$idCategoria = null;
if (!empty($categoriaInput)) {
    // Si viene encriptada la desencriptamos, si viene numerica la validamos
    $idCategoria = desencriptar($categoriaInput);
    if ($idCategoria === false || $idCategoria === '') {
        $idCategoria = validarEntero($categoriaInput);
    }
}

try {
    // Armamos la consulta base
    $sql = "
    SELECT
        Vehiculo.*,
        Sucursal.nombre AS sucursal,
        IF(Ventas.idVenta IS NOT NULL, 1, 0) AS vendido
    FROM Vehiculo
    INNER JOIN Sucursal ON Vehiculo.idSucursal = Sucursal.id
    LEFT JOIN Ventas ON Vehiculo.id = Ventas.idVehiculo
    WHERE 1=1
    ";

    $params = [];

    // Filtro por texto en marca o modelo
    if (!empty($busqueda)) {
        $sql .= " AND (Vehiculo.marca LIKE :busquedaMarca OR Vehiculo.modelo LIKE :busquedaModelo)";
        $params[':busquedaMarca'] = '%' . $busqueda . '%';
        $params[':busquedaModelo'] = '%' . $busqueda . '%';
    }

    // Filtro por precio maximo
    if ($precioMax !== null && $precioMax > 0) {
        $sql .= " AND Vehiculo.precio <= :precioMax";
        $params[':precioMax'] = $precioMax;
    }

    // Filtro por categoria (usando subconsulta en Tiene para no duplicar filas)
    if ($idCategoria !== null && $idCategoria > 0) {
        $sql .= " AND Vehiculo.id IN (SELECT idVehiculo FROM Tiene WHERE idCategoria = :idCategoria)";
        $params[':idCategoria'] = $idCategoria;
    }

    $sql .= " ORDER BY Vehiculo.id DESC";

    $stmt = $con->prepare($sql);
    $stmt->execute($params);
    $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Para cada vehiculo, obtenemos sus categorias asociadas y sus imagenes
    foreach ($vehiculos as &$vehiculo) {
        if (!$UsuarioLog) {
            unset($vehiculo['precioMinimo']);
        }

        $stmtCat = $con->prepare("
            SELECT Categoria.id, Categoria.nombre
            FROM Tiene
            INNER JOIN Categoria ON Tiene.idCategoria = Categoria.id
            WHERE Tiene.idVehiculo = :idVehiculo
        ");
        $stmtCat->execute([':idVehiculo' => $vehiculo['id']]);
        $vehiculo['categorias'] = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

        // Buscamos las imagenes del vehiculo con formato id_numero.jpg
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
        // la sucursal tambien viaja encriptada (el panel admin la usa para el select)
        $vehiculo['idSucursal'] = encriptar($vehiculo['idSucursal']);
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
