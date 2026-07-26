<?php
// backend/logout.php
// Este archivo cierra la sesion del usuario


session_start();

// eliminamos todas las variables de sesion
$_SESSION = [];

// Destruimos la sesion en el servidor
session_destroy();

//respondemos que fue cerrada
header("Content-Type: application/json; charset=UTF-8");
echo json_encode(["status" => "success", "message" => "Sesion cerrada correctamente."]);
