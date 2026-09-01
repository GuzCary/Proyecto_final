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

// Si no se pudo desencriptar o vino vacio, cortamos
if (!$id) {
    echo json_encode(["status" => "error", "message" => "El identificador del vehiculo no es valido."]);
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
    $imgDir = __DIR__ . '/../img/';

    // Log temporal para diagnosticar
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    $log = date('H:i:s') . " - id=$id - files=" . (isset($_FILES['files']) ? count($_FILES['files']['name']) : 'NO') . "\n";

    // Creamos el directorio si no existe
    if (!is_dir($imgDir)) {
        mkdir($imgDir, 0755, true);
    }

    // Eliminamos las imagenes marcadas
    if (!empty($_POST['eliminarImagenes']) && is_array($_POST['eliminarImagenes'])) {
        $log .= "eliminar=" . implode(',', $_POST['eliminarImagenes']) . "\n";
        foreach ($_POST['eliminarImagenes'] as $nombre) {
            $ruta = $imgDir . basename($nombre);
            if (file_exists($ruta)) {
                unlink($ruta);
            }
        }
    } else {
        $log .= "eliminar=NINGUNA\n";
    }

    // Reenumeramos las imagenes restantes para evitar huecos
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
        $archivos = scandir($imgDir);
        $maxNum = 0;
        foreach ($archivos as $archivo) {
            if (preg_match('/^' . $id . '_(\d+)\.jpg$/', $archivo, $matches)) {
                $maxNum = max($maxNum, (int)$matches[1]);
            }
        }

        $cantidad = count($_FILES["files"]["name"]);
        for ($i = 0; $i < $cantidad; $i++) {
            $err = $_FILES["files"]["error"][$i];
            $log .= "img[$i] error=$err";
            if ($err === UPLOAD_ERR_OK) {
                $tmpName = $_FILES["files"]["tmp_name"][$i];
                $numero = $maxNum + $i + 1;
                $to = $imgDir . $id . "_" . $numero . ".jpg";
                $ok = move_uploaded_file($tmpName, $to);
                $log .= " destino=$to ok=" . ($ok ? 'SI' : 'NO');
            }
            $log .= "\n";
        }
    } else {
        $log .= "NO HAY FILES\n";
    }

    file_put_contents($logDir . 'debug_img.log', $log . "---\n", FILE_APPEND);

    echo json_encode(["status" => "success", "message" => "Vehiculo modificado con exito."]);

    $con->commit();

} catch (PDOException $e) {
    $con->rollBack();
    echo json_encode(["status" => "error", "message" => "Error: " . $e->getMessage()]);
}
