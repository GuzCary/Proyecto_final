<?php
// backend/modificar_vehiculo.php
// Este archivo permite modificar un vehiculo ya existente (admin)


// iniciamos la sesion y establecemos el protocolo en JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// incluimos la conexion a la db, las funciones de encriptacion y sanitizacion
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

// recibimos y desencriptamos el id del vehiclo, no se puede sanitizar
$idEncriptado = $_POST['id'] ?? '';
$id = desencriptar($idEncriptado);

// Si no se pudo desencriptar o vino vacio, cortamos
if (!$id) {
    echo json_encode(["status" => "error", "message" => "El identificador del vehiculo no es valido."]);
    exit;
}

// Recibimos y validamos los datos del formulario
$idSucursal = validarEntero($_POST['idSucursal']);
$marca = sanitizar($_POST['marca']);
$descripcion = sanitizar($_POST['descripcion']);
$modelo = sanitizar($_POST['modelo']);
$potencia = validarEntero($_POST['potencia']);
$estado = validarEntero($_POST['estado']);
$enlaceDocOficial = sanitizar($_POST['enlaceDocOficial']);
$consumo = validarFloat($_POST['consumo']);
$patente = validarFloat($_POST['patente']);
$seguroSOA = validarFloat($_POST['seguroSOA']);
$seguroTerceros = validarFloat($_POST['seguroTerceros']);
$seguroTotal = validarFloat($_POST['seguroTotal']);
$anio = validarEntero($_POST['anio']);
$km = validarEntero($_POST['km']);
$precioMinimo = validarFloat($_POST['precioMinimo']);
$precio = validarFloat($_POST['precio']);
$categorias = sanitizarArray($_POST['categorias']);

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

    // Si se mandaron categorias, actualizamos la tabla 'Tiene'
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

    
    // --- Manejo de imagenes ---
    $imgDir = __DIR__ . '/../../img/'; 
    
    // eliminamos las imágenes marcadas
    if (!empty($_POST['eliminarImagenes']) && is_array($_POST['eliminarImagenes'])) {
        foreach ($_POST['eliminarImagenes'] as $nombre) {
            // basename evita acceso a otros directorios
            $ruta = $imgDir . basename($nombre);
            if (file_exists($ruta)) {
                unlink($ruta);
            }
        }
    }
    
    // renumeramos las imagenes
    $imagenesVehiculo = [];
    $archivos = scandir($imgDir);
    
    foreach ($archivos as $archivo) {
        if (preg_match('/^' . $id . '_(\d+)\.[a-zA-Z]+$/', $archivo)) {
            $imagenesVehiculo[] = $archivo;
        }
    }
    // ordenamos las imagenes 
    sort($imagenesVehiculo);
    
    foreach ($imagenesVehiculo as $index => $archivoViejo) {
        $nuevoNombre = $id . '_' . ($index + 1) . '.' . pathinfo($archivoViejo, PATHINFO_EXTENSION);
        if ($archivoViejo !== $nuevoNombre) {
            rename($imgDir . $archivoViejo, $imgDir . $nuevoNombre);
        }
    }
    
    // agregamos las nuevas imagenes subidas verificandolas con validarImagen
    if (isset($_FILES["files"]) && is_array($_FILES["files"]["name"])) {

    
        $siguienteNumero = count($imagenesVehiculo);
        $cantidad = count($_FILES["files"]["name"]);
    
        for ($i = 0; $i < $cantidad; $i++) {
            $archivoActual = [
                'tmp_name' => $_FILES["files"]["tmp_name"][$i],
                'error'    => $_FILES["files"]["error"][$i]
            ];
    
            
            $extension = validarImagen($archivoActual);
    
            // Si la imagen no es válida o falló, la salteamos
            if ($extension === false) {
                continue;
            }
    
            // Incrementamos el contador solo si la imagen es válida (así evitamos huecos)
            $siguienteNumero++;
            $to = $imgDir . $id . "_" . $siguienteNumero . "." . $extension;
            move_uploaded_file($archivoActual['tmp_name'], $to);
        }
    }

    $con->commit();

    echo json_encode(["status" => "success", "message" => "Vehiculo modificado con exito."]);

} catch (PDOException $e) {
    $con->rollBack();
    echo json_encode(["status" => "error", "message" => "Error: " . $e->getMessage()]);
}
