<?php
if (file_exists(__DIR__ . '/database/conexion.php')) {
    require_once __DIR__ . '/database/conexion.php';
} else {
    require_once __DIR__ . '/conexion.php';
}
require_once __DIR__ . '/config.php';

/**
 * Genera un token aleatorio seguro
 */
function generarTokenSeguro(): string {
    return bin2hex(random_bytes(32));
}

/**
 * Crea una sesión QR en la base de datos tras verificar llave y ubicación
 */
function crearSesionToken($ip, $fingerprint, $lat, $lng, $distancia): ?string {
    $token = generarTokenSeguro();
    $minutos = EXPIRACION_SESION_MINUTOS;
    
    $sql = "INSERT INTO descubre_sesiones_qr 
            (token, ip_origen, device_fingerprint, geo_lat, geo_lng, distancia_metros, creado_en, expira_en, usado) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? MINUTE), 0)";
            
    $stmt = ejecutarConsulta($sql, [$token, $ip, $fingerprint, $lat, $lng, $distancia, $minutos]);
    return $stmt ? $token : null;
}

/**
 * Valida si un token existe, no ha expirado y no ha sido usado
 */
function validarSesionToken($token, $fingerprint = null): array {
    if (empty($token)) {
        return ['valido' => false, 'mensaje' => 'Token de sesión ausente.'];
    }
    
    $sql = "SELECT id, token, device_fingerprint, expira_en, usado, 
                   IF(expira_en > NOW(), 1, 0) AS no_expirado 
            FROM descubre_sesiones_qr 
            WHERE token = ? LIMIT 1";
            
    $stmt = ejecutarConsulta($sql, [$token]);
    if (!$stmt) {
        return ['valido' => false, 'mensaje' => 'Error al consultar la sesión.'];
    }
    
    $sesion = $stmt->fetch();
    if (!$sesion) {
        return ['valido' => false, 'mensaje' => 'Sesión no encontrada o inválida.'];
    }
    
    if ($sesion['usado'] == 1) {
        return ['valido' => false, 'mensaje' => 'Esta sesión ya fue utilizada para un registro.'];
    }
    
    if ($sesion['no_expirado'] != 1) {
        return ['valido' => false, 'mensaje' => 'La sesión ha expirado por inactividad.'];
    }
    
    // Si se pasa fingerprint y la sesión lo tenía, verificar coincidencia
    if (!empty($fingerprint) && !empty($sesion['device_fingerprint']) && $sesion['device_fingerprint'] !== $fingerprint) {
        return ['valido' => false, 'mensaje' => 'El dispositivo no coincide con la sesión inicial.'];
    }
    
    return ['valido' => true, 'sesion' => $sesion];
}

/**
 * Marca la sesión como usada tras completar el formulario
 */
function marcarSesionUsada($token): bool {
    $sql = "UPDATE descubre_sesiones_qr SET usado = 1 WHERE token = ?";
    $stmt = ejecutarConsulta($sql, [$token]);
    return $stmt !== false;
}
?>
