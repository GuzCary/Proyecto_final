<?php
// backend/registrar_marca.php

header("Content-Type: application/json; charset=UTF-8");

// Incluimos la conexión
require_once __DIR__ . '/../config/conexion.php';

$metodo = $_SERVER['REQUEST_METHOD'];

try {
    // PETICIÓN GET: Obtener todas las marcas ordenadas por hora
    if ($metodo === 'GET') {
        $sql = "SELECT rm.id, u.usuario, rm.hora, rm.direccion 
                FROM RegistroMarca rm
                INNER JOIN Usuarios u ON rm.idUsuario = u.id
                ORDER BY rm.hora DESC";
                
        $stmt = $con->prepare($sql);
        $stmt->execute();
        $marcas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "status" => "success",
            "marcas" => $marcas
        ]);
        exit;
    }

    // PETICIÓN POST: Registrar una nueva marca
    if ($metodo === 'POST') {
        $usuarioInput = $_POST['usuario'] ?? '';
        $direccionInput = $_POST['direccion'] ?? '';

        // Validamos que no estén vacíos
        if (empty($usuarioInput) || empty($direccionInput)) {
            echo json_encode(["status" => "error", "message" => "Por favor, completa todos los campos."]);
            exit;
        }

        // 1. Buscamos si el usuario existe para obtener su ID
        $stmt = $con->prepare("SELECT id FROM Usuarios WHERE usuario = :user");
        $stmt->execute([':user' => $usuarioInput]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $userId = $usuario['id'];
            $horaActual = date('H:i:s');

            // 2. Insertamos la marca
            $stmtInsert = $con->prepare("INSERT INTO RegistroMarca (idUsuario, hora, direccion) VALUES (:idUsuario, :hora, :direccion)");
            $stmtInsert->execute([
                ':idUsuario' => $userId,
                ':hora' => $horaActual,
                ':direccion' => $direccionInput
            ]);

            echo json_encode([
                "status" => "success",
                "message" => "Marca registrada correctamente."
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "El usuario no existe."]);
        }
        exit;
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error de servidor: " . $e->getMessage()]);
}