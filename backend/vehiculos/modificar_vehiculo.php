<?php
// backend/vehiculos/modificar_vehiculo.php
// Este archivo permite modificar un vehiculo ya existente (admin)

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

// recibimos y desencriptamos el id del vehiculo
$id = desencriptarId($_POST['id'] ?? '');
if ($id === false) {
    echo json_encode(["status" => "error", "message" => "El identificador del vehiculo no es valido."]);
    exit;
}

try {
    // verificamos que el vehiculo exista
    $stmtExiste = $con->prepare("SELECT id FROM Vehiculo WHERE id = :id");
    $stmtExiste->execute([':id' => $id]);
    if (!$stmtExiste->fetch()) {
        echo json_encode(["status" => "error", "message" => "El vehiculo no existe."]);
        exit;
    }

    // Recibimos los datos del formulario y los validamos
    $resultado = leerDatosVehiculo($con);
    if (isset($resultado['error'])) {
        echo json_encode(["status" => "error", "message" => $resultado['error']]);
        exit;
    }

    //iniciamos una transaccion por la seguridad de los datos
    $con->beginTransaction();

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
    $stmt->execute($resultado['datos'] + [':id' => $id]);

    // Reemplazamos las categorias (si se desmarcaron todas, queda sin categoria)
    guardarCategoriasVehiculo($con, $id, $resultado['categorias']);

    $con->commit();

    // --- Manejo de imagenes ---

    // eliminamos las imagenes marcadas, pero SOLO si pertenecen a este vehiculo
    $imagenesActuales = imagenesDeVehiculo($id);
    $eliminar = $_POST['eliminarImagenes'] ?? [];
    if (is_array($eliminar)) {
        foreach ($eliminar as $nombre) {
            if (is_string($nombre) && in_array($nombre, $imagenesActuales, true)) {
                @unlink(DIR_IMAGENES . $nombre);
            }
        }
    }

    // renumeramos las imagenes que quedaron para que no haya huecos (1, 2, 3...)
    $restantes = imagenesDeVehiculo($id);
    foreach ($restantes as $index => $archivoViejo) {
        $nuevoNombre = $id . '_' . ($index + 1) . '.' . pathinfo($archivoViejo, PATHINFO_EXTENSION);
        if ($archivoViejo !== $nuevoNombre) {
            rename(DIR_IMAGENES . $archivoViejo, DIR_IMAGENES . $nuevoNombre);
        }
    }

    // agregamos las nuevas imagenes a continuacion de las existentes
    guardarImagenesSubidas($id, count($restantes));

    echo json_encode(["status" => "success", "message" => "Vehiculo modificado con exito."]);

} catch (PDOException $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Error al modificar el vehiculo: " . $e->getMessage()]);
}
