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

// Recibimos los filtros opcionales enviados por GET y los validamos
$busqueda = sanitizar($_GET['busqueda'] ?? '');
$precioMaxInput = $_GET['precio_max'] ?? '';
$categoriaInput = $_GET['categoria'] ?? '';

// el texto de busqueda no puede ser enorme
if (!largoValido($busqueda, 100)) {
    echo json_encode(["status" => "error", "message" => "La busqueda no puede superar los 100 caracteres."]);
    exit;
}

// el precio maximo, si viene, tiene que ser un numero positivo (no se aceptan negativos)
$precioMax = null;
if ($precioMaxInput !== '') {
    $precioMax = validarFloatRango($precioMaxInput, 0, 1000000000);
    if ($precioMax === false) {
        echo json_encode(["status" => "error", "message" => "El precio maximo tiene que ser un numero positivo."]);
        exit;
    }
}

// la categoria llega encriptada
$idCategoria = null;
if ($categoriaInput !== '') {
    $idCategoria = desencriptarId($categoriaInput);
    if ($idCategoria === false) {
        echo json_encode(["status" => "error", "message" => "Categoria invalida."]);
        exit;
    }
}

try {
    // Armamos la consulta base
    // los vehiculos vendidos solo los recibe el administrador (para verlos en su panel);
    // el catalogo publico y el panel de ventas solo reciben los disponibles
    $esAdmin = isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';

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

    if (!$esAdmin) {
        $sql .= " AND Ventas.idVenta IS NULL";
    }

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

    // primero los disponibles y al final los vendidos
    $sql .= " ORDER BY vendido ASC, Vehiculo.id DESC";

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
        // los ids de las categorias tambien viajan encriptados
        foreach ($vehiculo['categorias'] as &$cat) {
            $cat['id'] = encriptar($cat['id']);
        }
        unset($cat);

        // Buscamos las imagenes del vehiculo con formato id_numero.jpg
        $vehiculo['imagenes'] = [];
        $imgDir = __DIR__ . '/../../img/';
        if (is_dir($imgDir)) {
            $archivos = scandir($imgDir);
            foreach ($archivos as $archivo) {
                if (preg_match('/^' . (int)$vehiculo['id'] . '_(\d+)\.(jpg|png|webp|gif)$/', $archivo)) {
                    $vehiculo['imagenes'][] = $archivo;
                }
            }
            natsort($vehiculo['imagenes']); // orden 1, 2, ..., 10
            $vehiculo['imagenes'] = array_values($vehiculo['imagenes']);
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
