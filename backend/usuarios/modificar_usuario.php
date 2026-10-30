<?php
// backend/usuarios/modificar_usuario.php
// Este archivo permite modificar un usuario existente (admin)

// iniciamos la sesion y establecemos el protocolo en JSON
session_start();
header("Content-Type: application/json; charset=UTF-8");

// incluimos la conexion a la db y las funciones de sanitizacion y encriptacion
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../seguridad/encriptar.php';
require_once __DIR__ . '/../seguridad/sanitizar.php';
require_once __DIR__ . '/datos_usuario.php';

// Verificamos que el usuario este logueado y sea administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Acceso denegado. Se requiere rol de administrador."]);
    exit;
}

// Verificamos que la peticion sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Metodo no permitido."]);
    exit;
}

// Recibimos y sanitizamos los datos (los ids encriptados no se sanitizan)
$idEncriptado = $_POST['id_encriptado'] ?? '';
$usuarioInput = sanitizar($_POST['usuario'] ?? '');
$contraseniaInput = sanitizar($_POST['contrasenia'] ?? '');
$rolInput = sanitizar($_POST['rol'] ?? '');
$sucursalEncriptada = $_POST['id_sucursal'] ?? '';
$salarioInput = $_POST['salarioPorHora'] ?? '';
$salario = validarFloat($salarioInput);

// Validamos campos obligatorios
if (empty($idEncriptado) || empty($usuarioInput) || empty($rolInput) || empty($sucursalEncriptada)) {
    echo json_encode(["status" => "error", "message" => "Por favor, completa los campos obligatorios."]);
    exit;
}

// Desencriptamos el id
$id = desencriptarId($idEncriptado);
if ($id === false || $id === '') {
    echo json_encode(["status" => "error", "message" => "ID de usuario invalido."]);
    exit;
}

// Desencriptamos la sucursal
$idSucursal = desencriptarId($sucursalEncriptada);
if ($idSucursal === false || $idSucursal === '') {
    echo json_encode(["status" => "error", "message" => "Sucursal invalida."]);
    exit;
}

// Validamos que el rol sea correcto
if (!in_array($rolInput, ['admin', 'user', 'limp'])) {
    echo json_encode(["status" => "error", "message" => "Rol no valido. Debe ser admin, user o limp."]);
    exit;
}

// Validamos formato y rangos (la contraseña y el salario son opcionales: vacio = no se cambian)
$errorDatos = validarDatosUsuario($usuarioInput, $contraseniaInput, false, $salarioInput);
if ($errorDatos !== null) {
    echo json_encode(["status" => "error", "message" => $errorDatos]);
    exit;
}

// Datos laborales (opcionales: si se dejan vacios quedan sin cargar)
$laborales = leerDatosLaborales();
if (isset($laborales['error'])) {
    echo json_encode(["status" => "error", "message" => $laborales['error']]);
    exit;
}

// Un administrador no se puede quitar el rol a si mismo (se quedaria sin acceso al panel)
if ($id == $_SESSION['usuario_id'] && $rolInput !== 'admin') {
    echo json_encode(["status" => "error", "message" => "No podes quitarte el rol de administrador a vos mismo."]);
    exit;
}

try {
    // Verificamos que la sucursal exista
    $stmtSuc = $con->prepare("SELECT id FROM Sucursal WHERE id = :id");
    $stmtSuc->execute([':id' => $idSucursal]);
    if (!$stmtSuc->fetch()) {
        echo json_encode(["status" => "error", "message" => "La sucursal no existe."]);
        exit;
    }

    // Verificamos si el nombre de usuario ya lo usa otra cuenta activa
    $stmtCheck = $con->prepare("SELECT u.id
                                FROM Usuarios u
                                LEFT JOIN Baja b ON u.id = b.idUsuario
                                WHERE u.usuario = :usuario AND u.id != :id AND b.id IS NULL");
    $stmtCheck->execute([
        ':usuario' => $usuarioInput,
        ':id' => $id
    ]);

    if ($stmtCheck->fetch()) {
        echo json_encode(["status" => "error", "message" => "El nombre de usuario ya existe."]);
        exit;
    }

    // Consultamos el rol actual para ver si cambio (y que el usuario este activo)
    $stmtCur = $con->prepare("SELECT u.rol
                              FROM Usuarios u
                              LEFT JOIN Baja b ON u.id = b.idUsuario
                              WHERE u.id = :id AND b.id IS NULL");
    $stmtCur->execute([':id' => $id]);
    $usuarioActual = $stmtCur->fetch(PDO::FETCH_ASSOC);

    if (!$usuarioActual) {
        echo json_encode(["status" => "error", "message" => "El usuario no existe o esta dado de baja."]);
        exit;
    }

    $rolActual = $usuarioActual['rol'];

    // Iniciamos una transaccion
    $con->beginTransaction();

    // Armamos el UPDATE segun los campos que vinieron
    $campos = "usuario = :usuario, rol = :rol, idSucursal = :idSucursal,
               diasDeLicencia = :diasDeLicencia, diasDeTrabajo = :diasDeTrabajo,
               horarioDeEntrada = :horarioDeEntrada, horarioDeSalida = :horarioDeSalida";
    $params = [
        ':usuario' => $usuarioInput,
        ':rol' => $rolInput,
        ':idSucursal' => $idSucursal,
        ':diasDeLicencia' => $laborales['diasDeLicencia'],
        ':diasDeTrabajo' => $laborales['diasDeTrabajo'],
        ':horarioDeEntrada' => $laborales['horarioDeEntrada'],
        ':horarioDeSalida' => $laborales['horarioDeSalida'],
        ':id' => $id
    ];

    // la fecha de contrato solo se cambia si vino una
    if ($laborales['fechaDeContrato'] !== null) {
        $campos .= ", fechaDeContrato = :fechaDeContrato";
        $params[':fechaDeContrato'] = $laborales['fechaDeContrato'];
    }

    // contraseña solo si vino una nueva
    if (!empty($contraseniaInput)) {
        $campos .= ", contraseña = :contrasenia";
        $params[':contrasenia'] = password_hash($contraseniaInput, PASSWORD_DEFAULT);
    }

    // salario solo si vino
    if ($salarioInput !== '') {
        $campos .= ", salarioPorHora = :salario";
        $params[':salario'] = $salario;
    }

    $stmtUser = $con->prepare("UPDATE Usuarios SET $campos WHERE id = :id");
    $stmtUser->execute($params);

    // Si cambio el rol, actualizamos las tablas secundarias correspondientes
    if ($rolActual !== $rolInput) {
        // Quitamos al usuario de la tabla del rol anterior, SOLO si no tiene historial asociado.
        // Si lo tiene (sanciones/aumentos que otorgo como admin, gastos de limpieza) dejamos la fila
        // para no romper las claves foraneas; el login usa la columna Usuarios.rol, asi que no afecta.
        if ($rolActual === 'admin') {
            $stmtHist = $con->prepare("SELECT
                (SELECT COUNT(*) FROM Sanciona WHERE idAdministrador = :id1) +
                (SELECT COUNT(*) FROM AumentosDeSueldo WHERE idAdministrador = :id2)");
            $stmtHist->execute([':id1' => $id, ':id2' => $id]);
            if ((int)$stmtHist->fetchColumn() === 0) {
                $stmtDel = $con->prepare("DELETE FROM Administrador WHERE id = :id");
                $stmtDel->execute([':id' => $id]);
            }
        } elseif ($rolActual === 'user') {
            // Vendedor no tiene tablas que dependan de el (las ventas apuntan a Funcionario)
            $stmtDel = $con->prepare("DELETE FROM Vendedor WHERE id = :id");
            $stmtDel->execute([':id' => $id]);
        } elseif ($rolActual === 'limp') {
            $stmtHist = $con->prepare("SELECT COUNT(*) FROM Gasta WHERE idLimpieza = :id");
            $stmtHist->execute([':id' => $id]);
            if ((int)$stmtHist->fetchColumn() === 0) {
                $stmtDel = $con->prepare("DELETE FROM Limpieza WHERE id = :id");
                $stmtDel->execute([':id' => $id]);
            }
        }

        // Insertamos en la tabla del rol nuevo (si ya estaba de antes no la duplicamos)
        if ($rolInput === 'limp') {
            $stmtExiste = $con->prepare("SELECT id FROM Limpieza WHERE id = :id");
            $stmtExiste->execute([':id' => $id]);
            if (!$stmtExiste->fetch()) {
                $stmtIns = $con->prepare("INSERT INTO Limpieza (id) VALUES (:id)");
                $stmtIns->execute([':id' => $id]);
            }
        } else {
            // Si pasa a admin o user, aseguramos que este en Funcionario (sin licencia si es nuevo)
            $stmtFuncCheck = $con->prepare("SELECT id FROM Funcionario WHERE id = :id");
            $stmtFuncCheck->execute([':id' => $id]);

            if (!$stmtFuncCheck->fetch()) {
                $stmtFuncIns = $con->prepare("INSERT INTO Funcionario (id, idLicenciaDeConducir) VALUES (:id, NULL)");
                $stmtFuncIns->execute([':id' => $id]);
            }

            $tablaRol = $rolInput === 'admin' ? 'Administrador' : 'Vendedor';
            $stmtExiste = $con->prepare("SELECT id FROM $tablaRol WHERE id = :id");
            $stmtExiste->execute([':id' => $id]);
            if (!$stmtExiste->fetch()) {
                $stmtIns = $con->prepare("INSERT INTO $tablaRol (id) VALUES (:id)");
                $stmtIns->execute([':id' => $id]);
            }
        }
    }

    // Confirmamos la transaccion
    $con->commit();

    echo json_encode(["status" => "success", "message" => "Usuario actualizado correctamente."]);

} catch (PDOException $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Error al actualizar el usuario: " . $e->getMessage()]);
}
