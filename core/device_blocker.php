<?php
require_once __DIR__ . '/conexion.php';

/**
 * Normaliza la cédula: quita espacios, guiones y convierte a mayúsculas
 */
function normalizarCedula(string $cedula): string {
    return strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', trim($cedula)));
}

/**
 * Normaliza el teléfono: solo dígitos
 */
function normalizarTelefono(string $telefono): string {
    return preg_replace('/[^0-9]/', '', trim($telefono));
}

/**
 * Verifica si el dispositivo ya fue registrado previamente en la promoción
 */
function dispositivoYaRegistrado(string $fingerprint): bool {
    if (empty($fingerprint)) {
        return false;
    }
    
    $sql = "SELECT id FROM descubre_registros WHERE device_fingerprint = ? LIMIT 1";
    $stmt = ejecutarConsulta($sql, [$fingerprint]);
    return ($stmt && $stmt->fetch()) ? true : false;
}

/**
 * Verifica si la cédula ya fue registrada previamente
 */
function cedulaYaRegistrada(string $cedula): bool {
    $cedulaNorm = normalizarCedula($cedula);
    if (empty($cedulaNorm)) {
        return false;
    }
    
    $sql = "SELECT id FROM descubre_registros WHERE numero_cedula = ? LIMIT 1";
    $stmt = ejecutarConsulta($sql, [$cedulaNorm]);
    return ($stmt && $stmt->fetch()) ? true : false;
}

/**
 * Verifica si el teléfono ya fue registrado previamente (opcional para evitar multicuentas)
 */
function telefonoYaRegistrado(string $telefono): bool {
    $telNorm = normalizarTelefono($telefono);
    if (empty($telNorm)) {
        return false;
    }
    
    $sql = "SELECT id FROM descubre_registros WHERE numero_contacto = ? LIMIT 1";
    $stmt = ejecutarConsulta($sql, [$telNorm]);
    return ($stmt && $stmt->fetch()) ? true : false;
}
?>
