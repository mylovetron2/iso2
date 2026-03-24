<?php
/**
 * Script để thêm cột stt vào bảng ke_hoach_bao_duong_dinh_ky_iso
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection();
    
    echo "Bắt đầu thêm cột thietbi_id vào bảng ke_hoach_bao_duong_dinh_ky_iso...\n";
    
    // Kiểm tra xem cột đã tồn tại chưa
    $checkSql = "SHOW COLUMNS FROM ke_hoach_bao_duong_dinh_ky_iso LIKE 'thietbi_id'";
    $result = $db->query($checkSql);
    
    if ($result->rowCount() > 0) {
        echo "Cột 'thietbi_id' đã tồn tại trong bảng.\n";
    } else {
        // Thêm cột thietbi_id
        $sql = "ALTER TABLE `ke_hoach_bao_duong_dinh_ky_iso`
                ADD COLUMN `thietbi_id` int(11) DEFAULT NULL COMMENT 'ID thiết bị (tham chiếu thietbi_iso.stt)' AFTER `id`";
        
        $db->exec($sql);
        echo "✓ Đã thêm cột 'thietbi_id' thành công.\n";
        
        // Tạo index
        $indexSql = "CREATE INDEX idx_thietbi_id ON ke_hoach_bao_duong_dinh_ky_iso(`thietbi_id`)";
        try {
            $db->exec($indexSql);
            echo "✓ Đã tạo index cho cột 'thietbi_id' thành công.\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "Index 'idx_thietbi_id' đã tồn tại.\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "\nHoàn thành!\n";
    
} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
    exit(1);
}
