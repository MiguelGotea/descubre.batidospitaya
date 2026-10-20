<?php
/**
 * Script de Migración: Eliminar columna correo_electronico
 * Ejecutable desde el navegador o consola en el servidor.
 */
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../core/database/conexion.php';

echo "<h2>🔧 Migración: Eliminación de columna correo_electronico</h2>";

try {
    // 1. Verificar si la columna existe en la tabla
    $stmt = $conn->query("SHOW COLUMNS FROM `descubre_registros` LIKE 'correo_electronico'");
    $columna = $stmt->fetch();

    if ($columna) {
        echo "<p>⏳ La columna <strong>correo_electronico</strong> existe en <code>descubre_registros</code>. Procediendo a eliminar...</p>";
        
        // 2. Ejecutar ALTER TABLE
        $conn->exec("ALTER TABLE `descubre_registros` DROP COLUMN `correo_electronico`");
        
        echo "<p style='color: green; font-weight: bold;'>✅ ¡Columna eliminada con éxito de la base de datos!</p>";
    } else {
        echo "<p style='color: #0A3D36; font-weight: bold;'>ℹ️ La columna <strong>correo_electronico</strong> ya no existe en la tabla (ya fue eliminada previamente).</p>";
    }

    // 3. Mostrar estructura actual de campos
    echo "<h3>Estructura actual de descubre_registros:</h3><ul>";
    $stmtCols = $conn->query("SHOW COLUMNS FROM `descubre_registros`");
    while ($row = $stmtCols->fetch()) {
        echo "<li><code>{$row['Field']}</code> ({$row['Type']})</li>";
    }
    echo "</ul>";

} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Error al ejecutar la migración: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
