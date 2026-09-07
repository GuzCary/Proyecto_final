<?php
// backend/sanitizar.php
// Funciones de sanitizacion y validacion de datos de entrada

// esta funcion limpia cadenas de caracteres para evitar inyecciones JS
 function sanitizar($dato) {
     if ($dato === null) {
         return '';
     }
     $dato = trim($dato);
     $dato = strip_tags($dato);
     $dato = htmlspecialchars($dato);
     return $dato;
 }

// esta funcion valida que un dato sea un numero entero
function validarEntero($dato) {
    if ($dato === '' || $dato === null) {
        return null;
    }

    $limpio = filter_var($dato, FILTER_VALIDATE_INT);
    return ($limpio !== false) ? $limpio : null;
}

// esta funcion valida que un dato sea un numero de tipo FLOAT
function validarFloat($dato) {
    if ($dato === '' || $dato === null) {
        return null;
    }

    $limpio = filter_var($dato, FILTER_VALIDATE_FLOAT);
    return ($limpio !== false) ? $limpio : null;
}

// esta funcion valida que las imagenes subidas sean de los tipos de datos permitidos, si lo son los devuelve y sino devuelve false
function validarImagen($archivo) {

    if (!isset($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // lista de permitidos
    $extensionesPermitidas = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp",
        "image/gif"  => "gif"
    ];


    $info = getimagesize($archivo['tmp_name']);


    if ($info === false || !isset($extensionesPermitidas[$info["mime"]])) {
        return false;
    }


    return $extensionesPermitidas[$info["mime"]];
}


// esta funcion sanitiza arrays, incluso con arrays dentro
function sanitizarArray($datos) {
    // Si no es un array lo sanitiza como dato normal
    if (!is_array($datos)) {
        return sanitizar($datos);
    }

    $limpio = [];
    foreach ($datos as $clave => $valor) {
        if (is_array($valor)) {
            // Si el valor es otro array, lo vuelve a pasar por la misma función
            $limpio[$clave] = sanitizarArray($valor);
        } else {
            // sino lo sanitiza como valor normal como sanitizar()
            $limpio[$clave] = sanitizar($valor);
        }
    }

    return $limpio;
}
