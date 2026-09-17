<?php
// backend/seguridad/intentos.php

//este archivo maneja el limite de intentos de incio de sesion

// esta funcion revisa si en la DB el usuario esta marcado como bloqueado en el ultimo minuto 
function estaBloqueado($con) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $con->prepare("SELECT COUNT(*) FROM intentos_login WHERE ip = ? AND fecha >= NOW() - INTERVAL 1 MINUTE");
    $stmt->execute([$ip]);
    return $stmt->fetchColumn() >= 5;
}

// esta funcion guarda un intento fallido para la IP actual
function registrarFallo($con) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $con->prepare("INSERT INTO intentos_login (ip) VALUES (?)");
    $stmt->execute([$ip]);
}

// esta funcion borra los intentos si el usuario inició sesión correctamente
function limpiarFallos($con) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $con->prepare("DELETE FROM intentos_login WHERE ip = ?");
    $stmt->execute([$ip]);
}
