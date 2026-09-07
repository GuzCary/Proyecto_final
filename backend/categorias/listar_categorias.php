<?php
// backend/categorias/listar_categorias.php
// este archivo permite listar las categorias (publico)

// establecemos el protocolo en JSON
header("Content-Type: application/json; charset=UTF-8");

// incluimos la conexion a la DB y las funciones de encriptacion
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../seguridad/encriptar.php';

// si la peticion es GET devolvemos las categorias
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // devolvemos en orden alfabetico
        $stmt = $con->prepare("SELECT id, nombre FROM Categoria ORDER BY nombre ASC");
        $stmt->execute();
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // encriptamos los ids antes de enviarlos al frontend
        foreach ($categorias as &$categoria) {
            $categoria['id_encriptado'] = encriptar($categoria['id']);
        }
        unset($categoria);

        echo json_encode([
            "status" => "success",
            "categorias" => $categorias
        ]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Error al obtener categorias: " . $e->getMessage()]);
    }
    exit;
}


// si llega otro metodo, respondemos error
echo json_encode(["status" => "error", "message" => "Metodo no permitido."]);
