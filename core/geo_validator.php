<?php
require_once __DIR__ . '/config.php';

/**
 * Calcula la distancia en metros entre dos puntos geográficos usando la fórmula de Haversine
 */
function calcularDistanciaMetros($lat1, $lng1, $lat2, $lng2): float {
    $R = 6371000; // Radio de la Tierra en metros
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng / 2) * sin($dLng / 2);
         
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($R * $c, 2);
}

/**
 * Valida si las coordenadas del usuario están dentro del radio permitido
 */
function validarUbicacion($lat, $lng): array {
    if (!is_numeric($lat) || !is_numeric($lng)) {
        return [
            'valido' => false,
            'distancia' => null,
            'mensaje' => 'Coordenadas de ubicación no válidas.'
        ];
    }
    
    $distancia = calcularDistanciaMetros(LONA_LAT, LONA_LNG, (float)$lat, (float)$lng);
    
    if ($distancia <= RADIO_METROS) {
        return [
            'valido' => true,
            'distancia' => (int)$distancia,
            'mensaje' => 'Ubicación verificada correctamente.'
        ];
    } else {
        return [
            'valido' => false,
            'distancia' => (int)$distancia,
            'mensaje' => 'Parece que no estás en el punto de activación (' . NOMBRE_LOCAL . '). Estás a ' . (int)$distancia . ' metros.'
        ];
    }
}
?>
