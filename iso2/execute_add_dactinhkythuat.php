<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $sql = file_get_contents(__DIR__ . '/add_dactinhkythuat_columns.sql');
    
    $db->exec($sql);
    
    echo "✓ Đã thêm trường đặc tính kỹ thuật thành công!\n";
    echo "Database đã sẵn sàng cho cấu trúc Excel mới.\n";
    
} catch (PDOException $e) {
    echo "✗ Lỗi: " . $e->getMessage() . "\n";
}
