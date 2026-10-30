<?php
// backend/vehiculos/datos_vehiculo.php
// Funciones compartidas por vehiculo.php (alta) y modificar_vehiculo.php (edicion):
// leen y validan los datos del formulario y manejan las imagenes en disco.
// Este archivo no se llama directamente desde el navegador.

if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../seguridad/encriptar.php';
require_once __DIR__ . '/../seguridad/sanitizar.php';

// carpeta donde se guardan las imagenes de los vehiculos
define('DIR_IMAGENES', __DIR__ . '/../../img/');

// Lee y valida todos los campos del formulario de vehiculos.
// Devuelve ['error' => mensaje] si algo esta mal, o ['datos' => [...], 'categorias' => [ids]]
function leerDatosVehiculo($con) {
    $anioMaximo = (int)date('Y') + 1;

    // la sucursal llega encriptada desde el select (cargado con listar_sucursales.php)
    $idSucursal = desencriptarId($_POST['idSucursal'] ?? '');

    $marca = sanitizar($_POST['marca'] ?? '');
    $modelo = sanitizar($_POST['modelo'] ?? '');
    $descripcion = sanitizar($_POST['descripcion'] ?? '');
    $enlace = validarUrl($_POST['enlaceDocOficial'] ?? '');

    // campos numericos con su rango permitido (false = invalido, null = vacio)
    $precio         = validarFloatRango($_POST['precio'] ?? '', 0.01, 1000000000);
    $precioMinimo   = validarFloatRango($_POST['precioMinimo'] ?? '', 0, 1000000000);
    $estado         = validarEnteroRango($_POST['estado'] ?? '', 1, 10);
    $anio           = validarEnteroRango($_POST['anio'] ?? '', 1900, $anioMaximo);
    $km             = validarEnteroRango($_POST['km'] ?? '', 0, 5000000);
    $potencia       = validarEnteroRango($_POST['potencia'] ?? '', 0, 5000);
    $consumo        = validarFloatRango($_POST['consumo'] ?? '', 0, 100);
    $patente        = validarFloatRango($_POST['patente'] ?? '', 0, 100000000);
    $seguroSOA      = validarFloatRango($_POST['seguroSOA'] ?? '', 0, 100000000);
    $seguroTerceros = validarFloatRango($_POST['seguroTerceros'] ?? '', 0, 100000000);
    $seguroTotal    = validarFloatRango($_POST['seguroTotal'] ?? '', 0, 100000000);

    // obligatorios
    if ($idSucursal === false || $marca === '' || $modelo === '' || $precio === null) {
        return ['error' => "Completa los campos obligatorios: sucursal, marca, modelo y precio."];
    }

    // largos de texto segun las columnas de la base
    if (!largoValido($marca, 100) || !largoValido($modelo, 100)) {
        return ['error' => "La marca y el modelo no pueden superar los 100 caracteres."];
    }
    if (!largoValido($descripcion, 255)) {
        return ['error' => "La descripcion no puede superar los 255 caracteres."];
    }
    if ($enlace === false || !largoValido($enlace, 255)) {
        return ['error' => "El enlace al documento oficial tiene que ser una direccion http:// o https:// valida."];
    }

    // numeros fuera de rango (por ejemplo negativos)
    $rangos = [
        'precio'            => [$precio, 'mayor a 0'],
        'precio minimo'     => [$precioMinimo, 'un numero positivo'],
        'estado'            => [$estado, 'un numero del 1 al 10'],
        'año'               => [$anio, "un año entre 1900 y $anioMaximo"],
        'kilometraje'       => [$km, 'un numero entero positivo'],
        'potencia'          => [$potencia, 'un numero entero positivo'],
        'consumo'           => [$consumo, 'un numero entre 0 y 100'],
        'patente'           => [$patente, 'un numero positivo'],
        'seguro SOA'        => [$seguroSOA, 'un numero positivo'],
        'seguro a terceros' => [$seguroTerceros, 'un numero positivo'],
        'seguro total'      => [$seguroTotal, 'un numero positivo'],
    ];
    foreach ($rangos as $campo => [$valor, $regla]) {
        if ($valor === false) {
            return ['error' => "El campo $campo tiene que ser $regla."];
        }
    }

    if ($precioMinimo !== null && $precioMinimo > $precio) {
        return ['error' => "El precio minimo no puede ser mayor al precio."];
    }

    // la sucursal tiene que existir
    $stmt = $con->prepare("SELECT id FROM Sucursal WHERE id = :id");
    $stmt->execute([':id' => $idSucursal]);
    if (!$stmt->fetch()) {
        return ['error' => "La sucursal elegida no existe."];
    }

    // categorias: llegan encriptadas, cada una tiene que existir
    $categoriasInput = $_POST['categorias'] ?? [];
    if (!is_array($categoriasInput)) {
        return ['error' => "Categorias invalidas."];
    }
    $categorias = [];
    $stmtCat = $con->prepare("SELECT id FROM Categoria WHERE id = :id");
    foreach ($categoriasInput as $codigo) {
        $idCategoria = desencriptarId($codigo);
        if ($idCategoria === false) {
            return ['error' => "Una de las categorias no es valida."];
        }
        $stmtCat->execute([':id' => $idCategoria]);
        if (!$stmtCat->fetch()) {
            return ['error' => "Una de las categorias ya no existe. Recarga la pagina."];
        }
        $categorias[$idCategoria] = $idCategoria; // la clave evita repetidas
    }

    return [
        'datos' => [
            ':idSucursal'       => $idSucursal,
            ':marca'            => $marca,
            ':descripcion'      => $descripcion,
            ':modelo'           => $modelo,
            ':potencia'         => $potencia,
            ':estado'           => $estado,
            ':enlaceDocOficial' => $enlace,
            ':consumo'          => $consumo,
            ':patente'          => $patente,
            ':seguroSOA'        => $seguroSOA,
            ':seguroTerceros'   => $seguroTerceros,
            ':seguroTotal'      => $seguroTotal,
            ':anio'             => $anio,
            ':km'               => $km,
            ':precioMinimo'     => $precioMinimo,
            ':precio'           => $precio,
        ],
        'categorias' => array_values($categorias)
    ];
}

// Guarda las categorias del vehiculo en la tabla Tiene (reemplaza las anteriores)
function guardarCategoriasVehiculo($con, $idVehiculo, $categorias) {
    $stmtDel = $con->prepare("DELETE FROM Tiene WHERE idVehiculo = :idVehiculo");
    $stmtDel->execute([':idVehiculo' => $idVehiculo]);

    $stmtIns = $con->prepare("INSERT INTO Tiene (idVehiculo, idCategoria) VALUES (:idVehiculo, :idCategoria)");
    foreach ($categorias as $idCategoria) {
        $stmtIns->execute([':idVehiculo' => $idVehiculo, ':idCategoria' => $idCategoria]);
    }
}

// Devuelve las imagenes de un vehiculo (formato id_numero.ext) ordenadas por numero
function imagenesDeVehiculo($idVehiculo) {
    $imagenes = [];
    if (!is_dir(DIR_IMAGENES)) {
        return $imagenes;
    }
    foreach (scandir(DIR_IMAGENES) as $archivo) {
        if (preg_match('/^' . (int)$idVehiculo . '_(\d+)\.(jpg|png|webp|gif)$/', $archivo, $m)) {
            $imagenes[(int)$m[1]] = $archivo;
        }
    }
    ksort($imagenes); // orden numerico: 1, 2, ..., 10 (y no 1, 10, 2)
    return array_values($imagenes);
}

// Guarda las imagenes subidas en el formulario, numerandolas a partir de $desde + 1
function guardarImagenesSubidas($idVehiculo, $desde) {
    if (!isset($_FILES['files']) || !is_array($_FILES['files']['name'])) {
        return;
    }
    if (!is_dir(DIR_IMAGENES)) {
        mkdir(DIR_IMAGENES, 0755, true);
    }

    $numero = $desde;
    // como maximo 20 imagenes por envio
    $cantidad = min(count($_FILES['files']['name']), 20);

    for ($i = 0; $i < $cantidad; $i++) {
        $archivo = [
            'tmp_name' => $_FILES['files']['tmp_name'][$i],
            'error'    => $_FILES['files']['error'][$i],
            'size'     => $_FILES['files']['size'][$i]
        ];

        // validamos tipo y tamaño real del archivo; si no es una imagen valida la salteamos
        $extension = validarImagen($archivo);
        if ($extension === false) {
            continue;
        }

        $numero++;
        move_uploaded_file($archivo['tmp_name'], DIR_IMAGENES . (int)$idVehiculo . '_' . $numero . '.' . $extension);
    }
}
