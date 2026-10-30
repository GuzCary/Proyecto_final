<?php
// backend/config/env_load.php
// Carga las variables de entorno y las devuelve como arreglo.
//
// Primero busca las variables en $_SERVER (por si el servidor las define con SetEnv).
// Si no estan, las lee directamente del archivo .env de la raiz del proyecto.
//
// Formato esperado del .env (una variable por linea, se permiten comentarios con #):
//   DB_HOST=
//   DB_NAME=
//   CLAVE=
//   DB_USER_PUBLIC=
//   DB_PASS_PUBLIC=
//   DB_USER_GESTION=
//   DB_PASS_GESTION=

$claves = ['DB_HOST', 'DB_NAME', 'CLAVE', 'DB_USER_PUBLIC', 'DB_PASS_PUBLIC', 'DB_USER_GESTION', 'DB_PASS_GESTION'];

// leemos el archivo .env (dos carpetas arriba de backend/config)
$archivoEnv = [];
$rutaEnv = __DIR__ . '/../../.env';
if (is_readable($rutaEnv)) {
    foreach (file($rutaEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        // salteamos comentarios y lineas sin "="
        if ($linea === '' || $linea[0] === '#' || strpos($linea, '=') === false) {
            continue;
        }
        [$nombre, $valor] = explode('=', $linea, 2);
        // sacamos espacios y comillas opcionales
        $archivoEnv[trim($nombre)] = trim(trim($valor), "\"'");
    }
}

$resultadoEnv = [];
foreach ($claves as $clave) {
    $resultadoEnv[$clave] = $_SERVER[$clave] ?? $archivoEnv[$clave] ?? '';
}

return $resultadoEnv;
