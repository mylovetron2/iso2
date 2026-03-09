<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection();
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/create_table_phieu_kiem_soat_vattu.sql');
    
    // Execute SQL
    $db->exec($sql);
    
    echo "✓ Đã tạo bảng phieu_kiem_soat_vattu_iso và phieu_kiem_soat_vattu_chitiet_iso thành công!\n";
    
} catch (PDOException $e) {
    echo "✗ Lỗi: " . $e->getMessage() . "\n";
    exit(1);
}
