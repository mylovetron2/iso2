<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    // Read SQL file
    $sql = file_get_contents('update_vattu_structure.sql');
    
    // Execute migration
    $pdo->exec($sql);
    
    echo "✅ Migration completed successfully!\n";
    echo "Database structure updated with multilingual fields.\n";
    
    // Verify new columns
    $stmt = $pdo->query("DESCRIBE vattu_thanh_ly_iso");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\nColumns in vattu_thanh_ly_iso:\n";
    foreach ($columns as $col) {
        echo "  - $col\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
