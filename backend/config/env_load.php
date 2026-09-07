<?php
// config/env_load.php
// Carga las variables del archivo .env y las devuelve como arreglo.
//
// Formato esperado del .env (sin comentarios):
//   DB_HOST=
//   DB_NAME=
//   DB_USER=
//   DB_PASS=
//   CLAVE=  

return parse_ini_file(__DIR__ . '/../.env');
