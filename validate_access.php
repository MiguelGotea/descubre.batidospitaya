<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/core/config.php';
    require_once __DIR__ . '/core/geo_validator.php';
    require_once __DIR__ . '/core/session_manager.php';
    require_once __DIR__ . '/core/device_blocker.php';

    $response = [
        'success' => false,
        'message' => '',
        'token'   => null
    ];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Método no permitido.';
        echo json_encode($response);
        exit;
    }

    $k = trim($_POST['k'] ?? '');
    $lat = $_POST['lat'] ?? null;
    $lng = $_POST['lng'] ?? null;
    $accuracy = $_POST['accuracy'] ?? null;
    $fingerprint = trim($_POST['fingerprint'] ?? '');

    // 1. Capa 1: Validación de Llave Secreta
    if (empty($k) || $k !== QR_ACCESS_KEY) {
        $response['message'] = 'Para participar debés escanear el código QR en nuestro punto de activación.';
        $response['error_type'] = 'INVALID_KEY';
        echo json_encode($response);
        exit;
    }

    // 2. Capa 3: Validación de Dispositivo (Previo a registrar sesión)
    if (!empty($fingerprint) && dispositivoYaRegistrado($fingerprint)) {
        $response['message'] = 'Tu dispositivo ya participó en esta activación. ¡Solo se permite un descuento por participante!';
        $response['error_type'] = 'DEVICE_ALREADY_REGISTERED';
        echo json_encode($response);
        exit;
    }

    // 3. Capa 2: Validación de Geolocalización
    if ($lat === null || $lng === null) {
        $response['message'] = 'Es necesario permitir el acceso a tu ubicación para validar que estás en el punto de activación.';
        $response['error_type'] = 'GEO_REQUIRED';
        echo json_encode($response);
        exit;
    }

    $geoCheck = validarUbicacion($lat, $lng);
    if (!$geoCheck['valido']) {
        $response['message'] = $geoCheck['mensaje'];
        $response['error_type'] = 'OUT_OF_RANGE';
        $response['distancia'] = $geoCheck['distancia'];
        echo json_encode($response);
        exit;
    }

    // 4. Capa 4: Creación de Token de Sesión Temporal
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $token = crearSesionToken($ip, $fingerprint, $lat, $lng, $geoCheck['distancia']);

    if (!$token) {
        $response['message'] = 'No se pudo iniciar la sesión de registro. Por favor intenta nuevamente.';
        echo json_encode($response);
        exit;
    }

    $response['success'] = true;
    $response['message'] = 'Verificación exitosa. Acceso concedido.';
    $response['token'] = $token;
    echo json_encode($response);

} catch (Throwable $e) {
    error_log("Error crítico en validate_access: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
?>
