<?php
// backend/seguridad/encriptar.php
// este archivo maneja la encriptacion y desencriptacion de las ID

// traemos las variables de entorno
$env = require __DIR__ . '/../config/env_load.php';

// definimos la clave de encriptacion
if (!defined('CLAVE')) {
    define('CLAVE', $env['CLAVE']);
}

// esta funcion encripta un numero
 function encriptar($numero) {

    return openssl_encrypt((string)$numero, 'AES-128-ECB', CLAVE);
}

// esta funcion desencripta un codigo
function desencriptar($codigo) {
    // si llega algo que no es texto (por ejemplo un array) no se puede desencriptar
    if (!is_string($codigo) || $codigo === '') {
        return false;
    }
    return openssl_decrypt($codigo, 'AES-128-ECB', CLAVE);
}

// desencripta un ID y verifica que sea un numero entero positivo
// devuelve el numero o false si el codigo no es valido
// (todos los endpoints usan esta funcion para los IDs que vienen del navegador)
function desencriptarId($codigo) {
    $valor = desencriptar($codigo);
    if ($valor === false || !preg_match('/^[0-9]+$/', $valor)) {
        return false;
    }
    $id = (int)$valor;
    return $id > 0 ? $id : false;
}
