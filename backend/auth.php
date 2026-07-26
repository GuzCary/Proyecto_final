<?php
// backend/auth.php
// Este archivo devuelve el estado de la sesion actual


//Iniciamos la sesion 
session_start();

//Respuesta en formato JSON
header("Content-Type: application/json; charset=UTF-8");


// Si existe, devolvemos los datos del usuario logueado
if (isset($_SESSION['usuario_id'])) {
    echo json_encode([
        "status" => "success",
        "logueado" => true,
        "usuario_id" => $_SESSION['usuario_id'],
        "usuario_nombre" => $_SESSION['usuario_nombre'],
        "usuario_rol" => $_SESSION['usuario_rol']
    ]);
} else {
    // Si no hay sesion, devolvemos logueado = false
    echo json_encode([
        "status" => "success",
        "logueado" => false
    ]);
}
