<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection();
    
    echo "Kiểm tra cột nhomsc trong bảng ke_hoach_bao_duong_dinh_ky_iso...\n\n";
    
    $sql = "SHOW COLUMNS FROM ke_hoach_bao_duong_dinh_ky_iso LIKE 'nhomsc'";
    $result = $db->query($sql);
    
    if ($result->rowCount() > 0) {
        echo "✓ Cột nhomsc đã tồn tại!\n\n";
        $column = $result->fetch(PDO::FETCH_ASSOC);
        echo "Chi tiết:\n";
        foreach ($column as $key => $value) {
            echo "  $key: $value\n";
        }
    } else {
        echo "✗ Cột nhomsc CHƯA tồn tại!\n";
        echo "Đang thêm cột...\n\n";
        
        $alterSql = "ALTER TABLE ke_hoach_bao_duong_dinh_ky_iso 
                     ADD COLUMN nhomsc VARCHAR(100) DEFAULT NULL COMMENT 'Nhóm sửa chữa'";
        $db->exec($alterSql);
        
        echo "✓ Đã thêm cột nhomsc thành công!\n";
    }
    
} catch (Exception $e) {
    echo "LỖI: " . $e->getMessage() . "\n";
}
