<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core/database/conexion.php';
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/session_manager.php';
require_once __DIR__ . '/core/device_blocker.php';
require_once __DIR__ . '/core/codigo_generator.php';

$response = [
    'success' => false,
    'message' => '',
    'codigo'  => null
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido.';
    echo json_encode($response);
    exit;
}

$token         = trim($_POST['session_token'] ?? '');
$nombre        = trim($_POST['nombre_completo'] ?? '');
$contacto      = trim($_POST['numero_contacto'] ?? '');
$cedula        = trim($_POST['numero_cedula'] ?? '');
$correo        = trim($_POST['correo_electronico'] ?? '');
$fingerprint   = trim($_POST['device_fingerprint'] ?? '');
$canvasFp      = trim($_POST['canvas_fingerprint'] ?? '');

// 1. Validar sesión
$checkSesion = validarSesionToken($token, $fingerprint);
if (!$checkSesion['valido']) {
    $response['message'] = $checkSesion['mensaje'];
    echo json_encode($response);
    exit;
}
$sesionData = $checkSesion['sesion'];

// 2. Validar Nombre Completo (al menos 2 palabras)
$palabras = preg_split('/\s+/', $nombre);
if (count($palabras) < 2 || mb_strlen($nombre) < 5) {
    $response['message'] = 'Por favor ingresa tu nombre y apellido completo.';
    echo json_encode($response);
    exit;
}

// 3. Validar Teléfono (8 dígitos para Nicaragua)
$contactoLimpio = normalizarTelefono($contacto);
if (strlen($contactoLimpio) !== 8) {
    $response['message'] = 'El número de celular debe contener exactamente 8 dígitos.';
    echo json_encode($response);
    exit;
}

// 4. Validar Cédula Nicaragüense
$cedulaNorm = normalizarCedula($cedula);
if (strlen($cedulaNorm) < 14) {
    $response['message'] = 'Por favor ingresa un formato de cédula válido (ej: 001-201090-0012A).';
    echo json_encode($response);
    exit;
}

// 5. Verificar si la cédula ya participó
if (cedulaYaRegistrada($cedulaNorm)) {
    $response['message'] = 'Esta cédula ya fue registrada en la activación. Solo se permite una participación por persona.';
    echo json_encode($response);
    exit;
}

// 6. Verificar si el dispositivo ya participó
if (!empty($fingerprint) && dispositivoYaRegistrado($fingerprint)) {
    $response['message'] = 'Tu dispositivo ya completó un registro en esta activación.';
    echo json_encode($response);
    exit;
}

// 7. Generar Código Único de 6 caracteres
$codigoDescuento = generarCodigoDescuentoUnico();

// 8. Capturar datos de IP y User Agent
$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// 9. Guardar Registro en Base de Datos
$sql = "INSERT INTO descubre_registros 
        (nombre_completo, numero_contacto, numero_cedula, correo_electronico, 
         codigo_descuento, ip_publica, user_agent, device_fingerprint, 
         canvas_fingerprint, geo_lat, geo_lng, distancia_metros, geo_metodo, 
         session_token, activacion_id, estado, fecha_registro)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'gps', ?, ?, 'emitido', NOW())";

$params = [
    $nombre,
    $contactoLimpio,
    $cedulaNorm,
    empty($correo) ? null : $correo,
    $codigoDescuento,
    $ip,
    $userAgent,
    $fingerprint,
    $canvasFp,
    $sesionData['geo_lat'] ?? null,
    $sesionData['geo_lng'] ?? null,
    $sesionData['distancia_metros'] ?? null,
    $token,
    ACTIVACION_ID
];

$stmt = ejecutarConsulta($sql, $params);

if (!$stmt) {
    $response['message'] = 'Ocurrió un error al guardar tu registro. Por favor intenta nuevamente.';
    echo json_encode($response);
    exit;
}

// 10. Marcar sesión como usada
marcarSesionUsada($token);

// Respuesta exitosa
$response['success'] = true;
$response['message'] = '¡Felicidades! Tu descuento del 10% ha sido generado exitosamente.';
$response['codigo']  = $codigoDescuento;
$response['nombre']  = $nombre;
echo json_encode($response);
?>
