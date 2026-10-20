-- =========================================================================
-- Script SQL: Eliminar campo correo_electronico de descubre_registros
-- Proyecto: descubre.batidospitaya.com
-- =========================================================================

-- 1. Eliminar la columna de la tabla principal
ALTER TABLE `descubre_registros` DROP COLUMN `correo_electronico`;
