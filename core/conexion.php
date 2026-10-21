<?php
date_default_timezone_set('America/Managua');

$servername = "localhost";
$username   = "u839374897_erp";
$password   = "ERpPitHay2025$";
$dbname     = "u839374897_erp";

try {
    $conn = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    // Sincronizar zona horaria de MySQL con Nicaragua (UTC-6)
    $conn->exec("SET time_zone = '-06:00'");
} catch (PDOException $e) {
    error_log("Error de conexión BD Descubre: " . $e->getMessage());
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'validate_access.php' || 
        basename($_SERVER['SCRIPT_NAME'] ?? '') === 'save_registration.php') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar con la base de datos: ' . $e->getMessage()
        ]);
        exit;
    }
    if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
        die("Error de conexión: " . $e->getMessage());
    } else {
        die("Error al conectar con el servidor. Por favor intente más tarde.");
    }
}

// Función auxiliar para consultas preparadas
function ejecutarConsulta($sql, $params = []) {
    global $conn;
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Error en consulta Descubre: " . $e->getMessage() . " - SQL: " . $sql);
        return false;
    }
}
?>
