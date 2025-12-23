<?php
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    // Check if table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'vitri_iso'");
    $tableExists = $stmt->rowCount() > 0;
    
    echo "Table vitri_iso exists: " . ($tableExists ? "YES" : "NO") . "\n\n";
    
    if ($tableExists) {
        // Get count
        $stmt = $conn->query("SELECT COUNT(*) as total FROM vitri_iso");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Total records: " . $count['total'] . "\n\n";
        
        // Get sample data
        $stmt = $conn->query("SELECT mavitri, tenvitri, mota FROM vitri_iso LIMIT 10");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Sample data:\n";
        print_r($data);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
