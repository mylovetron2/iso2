<?php
/**
 * Script để đổi tên cột stt thành thietbi_id trong bảng ke_hoach_bao_duong_dinh_ky_iso
 * Migration này dành cho cơ sở dữ liệu đã có cột 'stt'
 */

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Bắt đầu đổi tên cột stt thành thietbi_id...\n";
    
    // Kiểm tra xem cột stt có tồn tại không
    $checkSttSql = "SHOW COLUMNS FROM ke_hoach_bao_duong_dinh_ky_iso LIKE 'stt'";
    $result = $db->query($checkSttSql);
    
    if ($result->rowCount() > 0) {
        echo "Tìm thấy cột 'stt'. Tiến hành đổi tên...\n";
        
        // Kiểm tra và xóa index cũ nếu tồn tại
        try {
            $dropIndexSql = "DROP INDEX idx_stt ON ke_hoach_bao_duong_dinh_ky_iso";
            $db->exec($dropIndexSql);
            echo "✓ Đã xóa index 'idx_stt'.\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "check that column/key exists") !== false) {
                echo "Index 'idx_stt' không tồn tại, bỏ qua.\n";
            } else {
                throw $e;
            }
        }
        
        // Đổi tên cột
        $renameSql = "ALTER TABLE `ke_hoach_bao_duong_dinh_ky_iso`
                      CHANGE COLUMN `stt` `thietbi_id` int(11) DEFAULT NULL 
                      COMMENT 'ID thiết bị (tham chiếu thietbi_iso.stt)'";
        $db->exec($renameSql);
        echo "✓ Đã đổi tên cột 'stt' thành 'thietbi_id'.\n";
        
        // Tạo index mới
        try {
            $createIndexSql = "CREATE INDEX idx_thietbi_id ON ke_hoach_bao_duong_dinh_ky_iso(`thietbi_id`)";
            $db->exec($createIndexSql);
            echo "✓ Đã tạo index 'idx_thietbi_id'.\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "Index 'idx_thietbi_id' đã tồn tại.\n";
            } else {
                throw $e;
            }
        }
        
        echo "\n✅ Migration hoàn tất thành công!\n";
        
    } else {
        // Kiểm tra xem cột thietbi_id đã tồn tại chưa
        $checkThietbiIdSql = "SHOW COLUMNS FROM ke_hoach_bao_duong_dinh_ky_iso LIKE 'thietbi_id'";
        $result = $db->query($checkThietbiIdSql);
        
        if ($result->rowCount() > 0) {
            echo "Cột 'thietbi_id' đã tồn tại. Không cần migration.\n";
        } else {
            echo "⚠️ Không tìm thấy cột 'stt' hoặc 'thietbi_id'.\n";
            echo "Có thể bảng chưa được tạo hoặc chưa có cột này.\n";
            echo "Chạy script execute_add_stt_kehoach_bd.php để thêm cột thietbi_id.\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
    exit(1);
}
