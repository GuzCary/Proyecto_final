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
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Acceso denegado. Se requiere rol de administrador."]);
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

    // --- Manejo de imagenes ---
    $imgDir = __DIR__ . '/../img/';
    $erroresImg = [];

    // Creamos el directorio si no existe
    if (!is_dir($imgDir)) {
        if (!mkdir($imgDir, 0755, true)) {
            $erroresImg[] = "No se pudo crear el directorio de imagenes.";
        }
    }

    // Eliminamos las imagenes marcadas para borrar
    if (!empty($_POST['eliminarImagenes']) && is_array($_POST['eliminarImagenes']) && is_dir($imgDir)) {
        foreach ($_POST['eliminarImagenes'] as $nombre) {
            // Validamos que el nombre tenga el formato idVehiculo_numero.jpg
            if (!preg_match('/^' . $id . '_\d+\.jpg$/', $nombre)) {
                continue;
            }
            $ruta = $imgDir . $nombre;
            $rutaReal = realpath($ruta);
            // Nos aseguramos de que el archivo este dentro del directorio de imagenes
            if ($rutaReal && strpos($rutaReal, realpath($imgDir)) === 0 && file_exists($rutaReal)) {
                if (!unlink($rutaReal)) {
                    $erroresImg[] = "No se pudo eliminar $nombre";
                }
            }
        }
    }

    // Reenumeramos las imagenes restantes para evitar huecos en la numeracion
    if (is_dir($imgDir)) {
        $archivos = scandir($imgDir);
        $imagenesVehiculo = [];
        foreach ($archivos as $archivo) {
            if (preg_match('/^' . $id . '_(\d+)\.jpg$/', $archivo)) {
                $imagenesVehiculo[] = $archivo;
            }
        }
        sort($imagenesVehiculo);
        foreach ($imagenesVehiculo as $index => $archivoViejo) {
            $nuevoNombre = $id . '_' . ($index + 1) . '.jpg';
            if ($archivoViejo !== $nuevoNombre) {
                rename($imgDir . $archivoViejo, $imgDir . $nuevoNombre);
            }
        }
    }

    // Agregamos las nuevas imagenes subidas
    if (isset($_FILES["files"]) && is_array($_FILES["files"]["name"]) && is_dir($imgDir)) {
        // Buscamos el numero mas alto actual para este vehiculo
        $archivos = scandir($imgDir);
        $maxNum = 0;
        foreach ($archivos as $archivo) {
            if (preg_match('/^' . $id . '_(\d+)\.jpg$/', $archivo, $matches)) {
                $maxNum = max($maxNum, (int)$matches[1]);
            }
        }

        $cantidad = count($_FILES["files"]["name"]);
        for ($i = 0; $i < $cantidad; $i++) {
            if ($_FILES["files"]["error"][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES["files"]["tmp_name"][$i];
                $numero = $maxNum + $i + 1;
                $to = $imgDir . $id . "_" . $numero . ".jpg";
                if (!move_uploaded_file($tmpName, $to)) {
                    $erroresImg[] = "No se pudo guardar la imagen " . ($i + 1);
                }
            } elseif ($_FILES["files"]["error"][$i] !== UPLOAD_ERR_NO_FILE) {
                $erroresImg[] = "Error al subir imagen " . ($i + 1) . ": codigo " . $_FILES["files"]["error"][$i];
            }
        }
    }

    echo json_encode([
        "status" => "success",
        "message" => "Vehículo modificado con éxito.",
        "errores_imagenes" => $erroresImg
    ]);

    $con->commit(); 

  

} catch (PDOException $e) {
    $con->rollBack(); // Si hay fallos volvemos a los datos anteriores
    
    echo json_encode(["status" => "error", "message" => "Error: " . $e->getMessage()]);
}