<?php
/**
 * Inicializador Central del Core - descubre.batidospitaya.com
 * Carga ordenada de configuración, base de datos y módulos de validación.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/geo_validator.php';
require_once __DIR__ . '/device_blocker.php';
require_once __DIR__ . '/session_manager.php';
require_once __DIR__ . '/codigo_generator.php';
