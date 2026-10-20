<?php
require_once __DIR__ . '/conexion.php';

/**
 * Genera un código único alfanumérico en mayúsculas de 6 caracteres máximo
 * Ejemplo: P7X9K2, D10M4B
 */
function generarCodigoDescuentoUnico(): string {
    $caracteres = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // Excluimos 0, O, 1, I para evitar confusiones de lectura
    $longitud = 6;
    $maxIntentos = 10;
    
    for ($intento = 0; $intento < $maxIntentos; $intento++) {
        $codigo = '';
        $maxIndex = strlen($caracteres) - 1;
        for ($i = 0; $i < $longitud; $i++) {
            $codigo .= $caracteres[random_int(0, $maxIndex)];
        }
        
        // Verificar unicidad en la base de datos
        $sql = "SELECT id FROM descubre_registros WHERE codigo_descuento = ? LIMIT 1";
        $stmt = ejecutarConsulta($sql, [$codigo]);
        if ($stmt && !$stmt->fetch()) {
            return $codigo;
        }
    }
    
    // Fallback con timestamp hash si se agotan intentos
    return strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));
}
?>
