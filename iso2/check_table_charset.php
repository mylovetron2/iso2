<?php
/**
 * CHECK TABLE CHARSET
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
.error { color: red; font-weight: bold; }
.success { color: green; font-weight: bold; }
</style></head><body>";

echo "<h1>🔍 CHECK TABLE CHARSET</h1>";

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Check table charset
    echo "<h2>📋 TABLE giao_nhan_thietbi_iso</h2>";
    $stmt = $db->query("
        SELECT 
            TABLE_COLLATION,
            ENGINE
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = 'diavatly_db' 
          AND TABLE_NAME = 'giao_nhan_thietbi_iso'
    ");
    $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Property</th><th>Value</th></tr>";
    echo "<tr><td>Collation</td><td><strong>{$tableInfo['TABLE_COLLATION']}</strong></td></tr>";
    echo "<tr><td>Engine</td><td>{$tableInfo['ENGINE']}</td></tr>";
    echo "</table>";
    
    $isUtf8mb4 = stripos($tableInfo['TABLE_COLLATION'], 'utf8mb4') !== false;
    if ($isUtf8mb4) {
        echo "<p class='success'>✅ Table uses utf8mb4 (GOOD)</p>";
    } else {
        echo "<p class='error'>❌ Table NOT using utf8mb4 (BAD for Vietnamese)</p>";
        echo "<p class='error'>Current: {$tableInfo['TABLE_COLLATION']}</p>";
    }
    
    // Check specific columns
    echo "<h2>📋 COLUMN CHARSETS</h2>";
    $stmt = $db->query("
        SELECT 
            COLUMN_NAME,
            CHARACTER_SET_NAME,
            COLLATION_NAME,
            COLUMN_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = 'diavatly_db' 
          AND TABLE_NAME = 'giao_nhan_thietbi_iso'
          AND DATA_TYPE IN ('varchar', 'text', 'char')
        ORDER BY ORDINAL_POSITION
    ");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Charset</th><th>Collation</th><th>Status</th></tr>";
    foreach ($columns as $col) {
        $isUtf8 = stripos($col['COLLATION_NAME'], 'utf8') !== false;
        $status = $isUtf8 ? "<span class='success'>✅ OK</span>" : "<span class='error'>❌ BAD</span>";
        
        echo "<tr>";
        echo "<td><strong>{$col['COLUMN_NAME']}</strong></td>";
        echo "<td>{$col['COLUMN_TYPE']}</td>";
        echo "<td>{$col['CHARACTER_SET_NAME']}</td>";
        echo "<td>{$col['COLLATION_NAME']}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check chitiet table
    echo "<h2>📋 TABLE giao_nhan_thietbi_chitiet</h2>";
    $stmt = $db->query("
        SELECT 
            TABLE_COLLATION,
            ENGINE
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = 'diavatly_db' 
          AND TABLE_NAME = 'giao_nhan_thietbi_chitiet'
    ");
    $tableInfo2 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Property</th><th>Value</th></tr>";
    echo "<tr><td>Collation</td><td><strong>{$tableInfo2['TABLE_COLLATION']}</strong></td></tr>";
    echo "<tr><td>Engine</td><td>{$tableInfo2['ENGINE']}</td></tr>";
    echo "</table>";
    
    $isUtf8mb4_2 = stripos($tableInfo2['TABLE_COLLATION'], 'utf8mb4') !== false;
    if ($isUtf8mb4_2) {
        echo "<p class='success'>✅ Chi tiết table uses utf8mb4 (GOOD)</p>";
    } else {
        echo "<p class='error'>❌ Chi tiết table NOT using utf8mb4</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>ERROR: {$e->getMessage()}</p>";
}

echo "</body></html>";
