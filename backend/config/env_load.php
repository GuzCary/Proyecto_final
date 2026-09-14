<?php
// backend/config/env_load.php
// Carga las variables del archivo .env y las devuelve como arreglo.
//
// Formato esperado del .env (sin comentarios):
//   DB_HOST=
//   DB_NAME=
//   DB_USER=
//   DB_PASS=
//   CLAVE=  



return [
    'DB_HOST' => $_SERVER['DB_HOST'],
    'DB_NAME' => $_SERVER['DB_NAME'],
    'DB_USER' => $_SERVER['DB_USER'],
    'DB_PASS' => $_SERVER['DB_PASS'],
    'CLAVE'   => $_SERVER['CLAVE'],
];


