<?php
// backend/usuarios/datos_usuario.php
// Funciones compartidas por agregar_usuario.php y modificar_usuario.php
// para validar los datos del formulario de usuarios.
// Este archivo no se llama directamente desde el navegador.

if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../seguridad/sanitizar.php';

// Reglas de los datos de acceso del usuario:
// - nombre: 3 a 50 caracteres, solo letras, numeros, punto, guion y guion bajo
// - contraseña: entre 6 y 72 caracteres (72 es el maximo que usa password_hash)
// - salario por hora: numero entre 0 y 1.000.000
function validarDatosUsuario($usuarioInput, $contraseniaInput, $contraseniaObligatoria, $salarioInput) {
    if (validarNombreUsuario($usuarioInput) === false) {
        return "El nombre de usuario debe tener entre 3 y 50 caracteres y solo letras, numeros, punto, guion o guion bajo.";
    }
    if ($contraseniaInput !== '' || $contraseniaObligatoria) {
        $largo = strlen($contraseniaInput);
        if ($largo < 6 || $largo > 72) {
            return "La contraseña debe tener entre 6 y 72 caracteres.";
        }
    }
    if ($salarioInput !== '' && validarFloatRango($salarioInput, 0, 1000000) === false) {
        return "El salario por hora tiene que ser un numero positivo.";
    }
    return null;
}

// Lee los datos laborales del formulario (todos opcionales):
// dias de licencia, dias de trabajo por semana, horario de entrada y salida, fecha de contrato.
// Devuelve ['error' => mensaje] o un array con los valores (null = campo vacio).
function leerDatosLaborales() {
    $diasLicenciaInput = $_POST['diasDeLicencia'] ?? '';
    $diasTrabajoInput  = $_POST['diasDeTrabajo'] ?? '';
    $entradaInput      = $_POST['horarioDeEntrada'] ?? '';
    $salidaInput       = $_POST['horarioDeSalida'] ?? '';
    $fechaInput        = $_POST['fechaDeContrato'] ?? '';

    // dias de licencia por año: 0 a 365
    $diasLicencia = validarEnteroRango($diasLicenciaInput, 0, 365);
    if ($diasLicencia === false) {
        return ['error' => "Los dias de licencia tienen que ser un numero entero entre 0 y 365."];
    }

    // dias de trabajo por semana: 1 a 7
    $diasTrabajo = validarEnteroRango($diasTrabajoInput, 1, 7);
    if ($diasTrabajo === false) {
        return ['error' => "Los dias de trabajo por semana tienen que ser un numero entre 1 y 7."];
    }

    // horarios: vacios o con formato de hora valido
    $entrada = null;
    if ($entradaInput !== '') {
        $entrada = validarHora($entradaInput);
        if ($entrada === null) {
            return ['error' => "El horario de entrada no es valido."];
        }
    }
    $salida = null;
    if ($salidaInput !== '') {
        $salida = validarHora($salidaInput);
        if ($salida === null) {
            return ['error' => "El horario de salida no es valido."];
        }
    }
    if ($entrada !== null && $salida !== null && $entrada === $salida) {
        return ['error' => "El horario de entrada y el de salida no pueden ser iguales."];
    }

    // fecha de contrato: fecha real, desde 1950 y como maximo un año hacia adelante
    $fecha = null;
    if ($fechaInput !== '') {
        $fecha = validarFecha($fechaInput);
        $maxima = date('Y-m-d', strtotime('+1 year'));
        if ($fecha === null || $fecha < '1950-01-01' || $fecha > $maxima) {
            return ['error' => "La fecha de contrato no es valida."];
        }
    }

    return [
        'diasDeLicencia'   => $diasLicencia,
        'diasDeTrabajo'    => $diasTrabajo,
        'horarioDeEntrada' => $entrada,
        'horarioDeSalida'  => $salida,
        'fechaDeContrato'  => $fecha
    ];
}
