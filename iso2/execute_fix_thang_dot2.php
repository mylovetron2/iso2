<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection();
    
    // Sửa cột thang_dot2 cho phép NULL
    $sql = "ALTER TABLE `kehoach_kiemdinh_2026_iso` 
            MODIFY COLUMN `thang_dot2` INT(11) NULL DEFAULT NULL";
    
    $db->exec($sql);
    
    echo "✓ Đã sửa cột thang_dot2 cho phép NULL thành công!\n";
    
} catch (Exception $e) {
    echo "✗ Lỗi: " . $e->getMessage() . "\n";
}
