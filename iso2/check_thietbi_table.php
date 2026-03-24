<?php
/**
 * CHECK THIETBI_ISO TABLE STRUCTURE
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
table { border-collapse: collapse; margin: 20px 0; background: white; }
td, th { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #4CAF50; color: white; }
</style></head><body>";

echo "<h1>🔍 CHECK thietbi_iso TABLE</h1>";

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<h2>📋 TABLE STRUCTURE</h2>";
    $stmt = $db->query("DESCRIBE thietbi_iso");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>📊 SAMPLE DATA (5 records)</h2>";
    $stmt = $db->query("SELECT * FROM thietbi_iso LIMIT 5");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($records)) {
        echo "<table>";
        echo "<tr>";
        foreach (array_keys($records[0]) as $key) {
            echo "<th>{$key}</th>";
        }
        echo "</tr>";
        foreach ($records as $rec) {
            echo "<tr>";
            foreach ($rec as $val) {
                echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>🔍 CHECK FOR STATUS-LIKE COLUMNS</h2>";
    echo "<p>Columns with 'tinh', 'trang', 'status' in name:</p>";
    echo "<ul>";
    foreach ($columns as $col) {
        if (stripos($col['Field'], 'tinh') !== false || 
            stripos($col['Field'], 'trang') !== false ||
            stripos($col['Field'], 'status') !== false ||
            stripos($col['Field'], 'state') !== false) {
            echo "<li><strong>{$col['Field']}</strong> ({$col['Type']})</li>";
        }
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>ERROR: {$e->getMessage()}</p>";
}

echo "</body></html>";
