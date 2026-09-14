<?php
// backend/config/env_load.php
// Carga las variables del archivo .env y las devuelve como arreglo.
//
// Formato esperado del .env (sin comentarios):
//   DB_HOST=
//   DB_NAME=
//   CLAVE=
//   DB_USER_PUBLIC=
//   DB_PASS_PUBLIC=
//   DB_USER_GESTION=
//   DB_PASS_GESTION=




return [
    'DB_HOST'         => $_SERVER['DB_HOST'],
    'DB_NAME'         => $_SERVER['DB_NAME'],
    'CLAVE'           => $_SERVER['CLAVE'],
    'DB_USER_PUBLIC'  => $_SERVER['DB_USER_PUBLIC'],
    'DB_PASS_PUBLIC'  => $_SERVER['DB_PASS_PUBLIC'],
    'DB_USER_GESTION' => $_SERVER['DB_USER_GESTION'],
    'DB_PASS_GESTION' => $_SERVER['DB_PASS_GESTION'],
];
