<?php
// backend/marcas/registrar_marca.php
// este archivo registra las marcas de los usuarios (publico)

// establecemos el potocolo en JSON
header("Content-Type: application/json; charset=UTF-8");

// Incluimos la conexión a la DB y los archivos de sanitizacion
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../seguridad/sanitizar.php';

try {
    // si la peticion es POST registramos una nueva marca
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $usuarioInput = sanitizar($_POST['usuario']);
        $direccionInput = sanitizar($_POST['direccion']);

        // Validamos que no estén vacíos
        if (empty($usuarioInput) || empty($direccionInput)) {
            echo json_encode(["status" => "error", "message" => "Por favor, completa todos los campos."]);
            exit;
        }

        // Solo se aceptan Entrada o Salida
        if (!in_array($direccionInput, ['Entrada', 'Salida'])) {
            echo json_encode(["status" => "error", "message" => "Tipo de marca no valido."]);
            exit;
        }

        // Buscamos si el usuario existe y esta activo (los dados de baja no pueden marcar)
        $stmt = $con->prepare("SELECT u.id
                               FROM Usuarios u
                               LEFT JOIN Baja b ON u.id = b.idUsuario
                               WHERE u.usuario = :user AND b.id IS NULL");
        $stmt->execute([':user' => $usuarioInput]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $userId = $usuario['id'];
            $fechaActual = date('Y-m-d');
            $horaActual = date('H:i:s');

            // Insertamos la marca con fecha y hora
            $stmtInsert = $con->prepare("INSERT INTO RegistroMarca (idUsuario, fecha, hora, direccion) VALUES (:idUsuario, :fecha, :hora, :direccion)");
            $stmtInsert->execute([
                ':idUsuario' => $userId,
                ':fecha' => $fechaActual,
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
