<?php
// backend/seguridad/encriptar.php
// este archivo maneja la encriptacion y desencriptacion de las ID

// traemos las variables de entorno
$env = require __DIR__ . '/../config/env_load.php';

// definimos la clave de encriptacion
define('CLAVE', $env['CLAVE']);

// esta funcion encripta un numero
 function encriptar($numero) {

    return openssl_encrypt($numero, 'AES-128-ECB', CLAVE);
}

// esta funcion desencripta un codigo
function desencriptar($codigo) {

    return openssl_decrypt($codigo, 'AES-128-ECB', CLAVE);
}
