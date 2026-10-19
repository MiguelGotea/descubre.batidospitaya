-- ==========================================================
-- SCRIPT DE CREACIÓN DE TABLAS - DESCUBRE.BATIDOSPITAYA.COM
-- Base de datos: u839374897_erp (o la configurada en conexion.php)
-- ==========================================================

-- 1. TABLA DE SESIONES TEMPORALES QR (Capa 4 de Seguridad)
CREATE TABLE IF NOT EXISTS `descubre_sesiones_qr` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `token` VARCHAR(64) NOT NULL UNIQUE COMMENT 'Token UUID de sesión temporal',
    `ip_origen` VARCHAR(45) DEFAULT NULL COMMENT 'IP pública del cliente',
    `device_fingerprint` VARCHAR(64) DEFAULT NULL COMMENT 'Hash SHA-256 del dispositivo',
    `geo_lat` DECIMAL(10, 8) DEFAULT NULL COMMENT 'Latitud validada',
    `geo_lng` DECIMAL(11, 8) DEFAULT NULL COMMENT 'Longitud validada',
    `distancia_metros` INT DEFAULT NULL COMMENT 'Distancia al punto central en metros',
    `creado_en` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `expira_en` DATETIME NOT NULL COMMENT 'Expira en 30 minutos',
    `usado` TINYINT(1) DEFAULT 0 COMMENT '1 si ya completó el registro',
    INDEX `idx_token` (`token`),
    INDEX `idx_device` (`device_fingerprint`),
    INDEX `idx_expira` (`expira_en`, `usado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sesiones temporales generadas tras escanear QR y validar geolocalización';

-- 2. TABLA PRINCIPAL DE REGISTROS Y DESCUENTOS (Capa 5 y Auditoría Forense)
CREATE TABLE IF NOT EXISTS `descubre_registros` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Datos del participante (del formulario)
    `nombre_completo` VARCHAR(200) NOT NULL COMMENT 'Nombre y apellido del participante',
    `numero_contacto` VARCHAR(20) NOT NULL COMMENT 'Número telefónico (8 dígitos en Nicaragua)',
    `numero_cedula` VARCHAR(30) NOT NULL UNIQUE COMMENT 'Número de cédula único normalizado sin guiones',
    `correo_electronico` VARCHAR(150) DEFAULT NULL COMMENT 'Correo electrónico si aplica',
    
    -- Código de descuento generado
    `codigo_descuento` VARCHAR(10) NOT NULL UNIQUE COMMENT 'Código único alfanumérico mayúsculas máx 6 caracteres',
    
    -- Datos forenses del dispositivo (Capa 3)
    `ip_publica` VARCHAR(45) DEFAULT NULL COMMENT 'Dirección IP del participante',
    `user_agent` TEXT DEFAULT NULL COMMENT 'Navegador y sistema operativo',
    `device_fingerprint` VARCHAR(64) DEFAULT NULL COMMENT 'Hash SHA-256 único del dispositivo',
    `canvas_fingerprint` VARCHAR(64) DEFAULT NULL COMMENT 'Hash de canvas rendering',
    
    -- Geolocalización validada (Capa 2)
    `geo_lat` DECIMAL(10, 8) DEFAULT NULL COMMENT 'Latitud GPS reportada',
    `geo_lng` DECIMAL(11, 8) DEFAULT NULL COMMENT 'Longitud GPS reportada',
    `geo_precision_metros` INT DEFAULT NULL COMMENT 'Margen de precisión GPS',
    `distancia_metros` INT DEFAULT NULL COMMENT 'Distancia calculada al punto del Kiosko',
    `geo_metodo` ENUM('gps','ip','manual') DEFAULT 'gps',
    
    -- Sesión y trazabilidad
    `session_token` VARCHAR(64) DEFAULT NULL COMMENT 'Token de sesión con el que se registró',
    `activacion_id` INT DEFAULT 1 COMMENT 'ID de la activación o punto comercial',
    `estado` ENUM('emitido','canjeado','anulado') DEFAULT 'emitido' COMMENT 'Estado del cupón',
    `fecha_canje` DATETIME DEFAULT NULL COMMENT 'Fecha y hora en que se canjeó en el kiosko',
    `fecha_registro` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de registro',
    
    INDEX `idx_cedula` (`numero_cedula`),
    INDEX `idx_contacto` (`numero_contacto`),
    INDEX `idx_codigo` (`codigo_descuento`),
    INDEX `idx_fingerprint` (`device_fingerprint`),
    INDEX `idx_ip` (`ip_publica`),
    INDEX `idx_fecha` (`fecha_registro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registros de participantes y cupones emitidos para activación descubre';
