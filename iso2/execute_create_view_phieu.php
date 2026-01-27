<?php
// Execute SQL to create view_phieu_iso
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config/database.php';

echo "<h2>Creating view_phieu_iso</h2>";

try {
    $db = getDBConnection(true);
    
    // Drop view if exists
    $sql_drop = "DROP VIEW IF EXISTS view_phieu_iso";
    $db->exec($sql_drop);
    echo "<p>✓ Dropped old view if existed</p>";
    
    // Create view
    $sql_create = "CREATE VIEW view_phieu_iso AS
        SELECT 
            phieu,
            ngayyc,
            ngyeucau
        FROM 
            hososcbd_iso
        ORDER BY 
            ngayyc DESC, phieu DESC";
    
    $db->exec($sql_create);
    echo "<p style='color: green;'><b>✓ View 'view_phieu_iso' created successfully!</b></p>";
    
    // Test the view
    echo "<h3>Testing view (first 10 records):</h3>";
    $stmt = $db->query("SELECT * FROM view_phieu_iso LIMIT 10");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($records)) {
        echo "<p>⚠️ View is empty (no data in hososcbd_iso table)</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #4CAF50; color: white;'>";
        echo "<th>phieu</th>";
        echo "<th>ngayyc</th>";
        echo "<th>ngyeucau</th>";
        echo "</tr>";
        
        foreach ($records as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['phieu'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['ngayyc'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['ngyeucau'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><b>Total records in view: " . count($records) . " (showing first 10)</b></p>";
    }
    
    // Show view structure
    echo "<h3>View structure:</h3>";
    $stmt = $db->query("DESCRIBE view_phieu_iso");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #2196F3; color: white;'>";
    echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><b>❌ Error:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
}
