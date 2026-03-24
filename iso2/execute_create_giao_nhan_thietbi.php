<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection();
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/create_table_giao_nhan_thietbi.sql');
    
    if ($sql === false) {
        throw new Exception('Không thể đọc file SQL');
    }
    
    // Execute SQL
    $db->exec($sql);
    
    echo "✅ Tạo bảng giao_nhan_thietbi_iso thành công!\n";
    
    // Check table structure
    $stmt = $db->query("DESCRIBE giao_nhan_thietbi_iso");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📋 Cấu trúc bảng:\n";
    echo str_pad("Field", 30) . str_pad("Type", 30) . str_pad("Null", 10) . "Key\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach ($columns as $column) {
        echo str_pad($column['Field'], 30) . 
             str_pad($column['Type'], 30) . 
             str_pad($column['Null'], 10) . 
             $column['Key'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
    exit(1);
}
