<?php
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    echo "Checking mo_iso table...\n\n";
    
    // Check if table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'mo_iso'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✓ Table mo_iso already exists\n\n";
        
        // Show structure
        $stmt = $conn->query("DESCRIBE mo_iso");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Table structure:\n";
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
        
        // Show count
        $stmt = $conn->query("SELECT COUNT(*) as count FROM mo_iso");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\nTotal records: {$count['count']}\n";
        
    } else {
        echo "❌ Table mo_iso does not exist\n\n";
        echo "Creating table...\n";
        
        $sql = "CREATE TABLE IF NOT EXISTS `mo_iso` (
            `stt` INT(11) AUTO_INCREMENT PRIMARY KEY,
            `mamo` VARCHAR(200) NOT NULL COMMENT 'Mã mỏ',
            `tenmo` TEXT NOT NULL COMMENT 'Tên mỏ',
            INDEX `idx_mamo` (`mamo`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Quản lý mỏ dầu khí'";
        
        $conn->exec($sql);
        
        echo "✅ Table mo_iso created successfully!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
