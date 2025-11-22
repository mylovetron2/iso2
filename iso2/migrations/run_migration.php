<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

echo "=== Running Migration: Create phieubangiao tables ===\n\n";

try {
    $pdo = getDBConnection();
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/20251122_create_phieubangiao_tables.sql');
    
    // Remove comments and split by semicolon
    $statements = array_filter(
        array_map('trim', preg_split('/;[\r\n]+/', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    echo "Found " . count($statements) . " SQL statements\n\n";
    
    $success = 0;
    $failed = 0;
    
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            echo "Executing statement " . ($index + 1) . "...\n";
            $pdo->exec($statement);
            $success++;
            echo "✓ Success\n\n";
        } catch (PDOException $e) {
            $failed++;
            echo "✗ Failed: " . $e->getMessage() . "\n\n";
        }
    }
    
    echo "=== Migration Complete ===\n";
    echo "Success: $success\n";
    echo "Failed: $failed\n";
    
    // Verify tables exist
    echo "\n=== Verifying tables ===\n";
    $tables = ['phieubangiao_iso', 'phieubangiao_thietbi_iso'];
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
        if ($result) {
            echo "✓ Table $table exists\n";
            
            // Show table structure
            $columns = $pdo->query("DESCRIBE $table")->fetchAll();
            echo "  Columns: " . count($columns) . "\n";
        } else {
            echo "✗ Table $table NOT found\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✓ Migration completed successfully!\n";
