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

        // Buscamos si el usuario existe para obtener su ID
        $stmt = $con->prepare("SELECT id FROM Usuarios WHERE usuario = :user");
        $stmt->execute([':user' => $usuarioInput]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $userId = $usuario['id'];
            $horaActual = date('H:i:s');

            // Insertamos la marca
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
