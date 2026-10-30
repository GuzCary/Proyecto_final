<?php
// backend/seguridad/sanitizar.php
// Funciones de sanitizacion y validacion de datos de entrada
// Todo dato que llega del navegador ($_POST, $_GET, $_FILES) pasa por alguna de estas funciones

// esta funcion limpia cadenas de caracteres para evitar inyecciones JS
 function sanitizar($dato) {
     // si llega un array o un objeto donde se esperaba texto, lo descartamos
     if ($dato === null || is_array($dato)) {
         return '';
     }
     $dato = trim((string)$dato);
     $dato = strip_tags($dato);
     $dato = htmlspecialchars($dato, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
     return $dato;
 }

// devuelve la cantidad de caracteres de un texto
// (no dependemos de mb_strlen porque la extension mbstring no siempre esta activada en el servidor)
function largoTexto($texto) {
    $texto = (string)$texto;
    if (function_exists('mb_strlen')) {
        return mb_strlen($texto, 'UTF-8');
    }
    // cuenta caracteres UTF-8 con una expresion regular
    return preg_match_all('/./us', $texto);
}

// verifica que un texto ya sanitizado no supere el largo de su columna en la base de datos
// (se mide el texto con las entidades HTML, que es lo que realmente se guarda)
function largoValido($texto, $maximo) {
    return largoTexto($texto) <= $maximo;
}

// esta funcion valida que un dato sea un numero entero
function validarEntero($dato) {
    if ($dato === '' || $dato === null || is_array($dato)) {
        return null;
    }

    $limpio = filter_var($dato, FILTER_VALIDATE_INT);
    return ($limpio !== false) ? $limpio : null;
}

// esta funcion valida que un dato sea un numero de tipo FLOAT
function validarFloat($dato) {
    if ($dato === '' || $dato === null || is_array($dato)) {
        return null;
    }

    $limpio = filter_var($dato, FILTER_VALIDATE_FLOAT);
    // descartamos INF y NAN
    return ($limpio !== false && is_finite($limpio)) ? $limpio : null;
}

// valida un entero dentro de un rango. Devuelve el numero, o false si vino algo invalido.
// Si el campo vino vacio devuelve null (para campos opcionales).
function validarEnteroRango($dato, $min, $max) {
    if ($dato === '' || $dato === null) {
        return null;
    }
    $numero = validarEntero($dato);
    if ($numero === null || $numero < $min || $numero > $max) {
        return false;
    }
    return $numero;
}

// valida un numero decimal dentro de un rango. Devuelve el numero, false si es invalido o null si vino vacio.
function validarFloatRango($dato, $min, $max) {
    if ($dato === '' || $dato === null) {
        return null;
    }
    $numero = validarFloat($dato);
    if ($numero === null || $numero < $min || $numero > $max) {
        return false;
    }
    return $numero;
}

// esta funcion valida que un dato sea una fecha real con formato AAAA-MM-DD
// devuelve la fecha si es valida o null si no lo es
function validarFecha($dato) {
    if ($dato === '' || $dato === null || !is_string($dato)) {
        return null;
    }

    $fecha = DateTime::createFromFormat('Y-m-d', $dato);
    // comparamos con el formato para rechazar fechas como 2025-02-31
    if ($fecha === false || $fecha->format('Y-m-d') !== $dato) {
        return null;
    }
    return $dato;
}

// valida una hora con formato HH:MM o HH:MM:SS (como la manda un <input type="time">)
// devuelve la hora en formato HH:MM:SS o null si no es valida
function validarHora($dato) {
    if (!is_string($dato) || !preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])(:([0-5][0-9]))?$/', $dato, $m)) {
        return null;
    }
    return $m[1] . ':' . $m[2] . ':' . (isset($m[4]) ? $m[4] : '00');
}

// valida un enlace: tiene que ser una URL http o https (evita enlaces "javascript:...")
// devuelve la URL, '' si vino vacia o false si es invalida
function validarUrl($dato) {
    if ($dato === null || is_array($dato)) {
        return '';
    }
    $dato = trim((string)$dato);
    if ($dato === '') {
        return '';
    }
    if (filter_var($dato, FILTER_VALIDATE_URL) === false) {
        return false;
    }
    $esquema = strtolower((string)parse_url($dato, PHP_URL_SCHEME));
    if ($esquema !== 'http' && $esquema !== 'https') {
        return false;
    }
    // la guardamos escapada igual que el resto del texto
    return htmlspecialchars($dato, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// valida un nombre de usuario: letras, numeros, punto, guion y guion bajo (3 a 50 caracteres)
function validarNombreUsuario($dato) {
    if ($dato === null || is_array($dato)) {
        return false;
    }
    $dato = trim((string)$dato);
    return preg_match('/^[A-Za-z0-9._-]{3,50}$/', $dato) ? $dato : false;
}

// esta funcion valida que las imagenes subidas sean de los tipos de datos permitidos, si lo son los devuelve y sino devuelve false
function validarImagen($archivo) {

    if (!isset($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // limite de 5 MB por imagen
    if (!isset($archivo['size']) || $archivo['size'] > 5 * 1024 * 1024) {
        return false;
    }

    // tiene que ser un archivo subido por el formulario
    if (!is_uploaded_file($archivo['tmp_name'])) {
        return false;
    }

    // lista de permitidos
    $extensionesPermitidas = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp",
        "image/gif"  => "gif"
    ];

    $info = @getimagesize($archivo['tmp_name']);

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
