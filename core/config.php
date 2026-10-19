<?php
/**
 * Configuración de Seguridad y Negocio - descubre.batidospitaya.com
 */

// Llave secreta del QR físico (Capa 1)
define('QR_ACCESS_KEY', 'bP9x2Qm7vN4kRj3sT8wLf6Yh');

// Coordenadas del punto físico (Multicentro Las Américas)
define('LONA_LAT', 12.139557);
define('LONA_LNG', -86.228827);

// Radio permitido en metros
define('RADIO_METROS', 300);

// Tiempo de expiración del token de sesión en minutos (Capa 4)
define('EXPIRACION_SESION_MINUTOS', 30);

// ID de la activación
define('ACTIVACION_ID', 1);

// Nombre del local
define('NOMBRE_LOCAL', 'Kiosko Batidos Pitaya Multicentro Las Américas');
?>
