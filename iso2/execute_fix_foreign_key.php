<?php
set_time_limit(300); // 5 phút timeout
ini_set('max_execution_time', 300);

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Đang sửa foreign key constraint...\n";
    
    // Tắt foreign key checks tạm thời
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "✓ Đã tắt foreign key checks\n";
    
    // Xóa constraint cũ
    try {
        $db->exec("ALTER TABLE vattu_thanh_ly_iso DROP FOREIGN KEY fk_vattu_phanloai");
        echo "✓ Đã xóa constraint cũ\n";
    } catch (PDOException $e) {
        echo "⚠ Không tìm thấy constraint cũ (có thể đã xóa trước đó)\n";
    }
    
    // Tạo constraint mới
    $db->exec("ALTER TABLE vattu_thanh_ly_iso 
               ADD CONSTRAINT fk_vattu_phanloai 
               FOREIGN KEY (phanloai_id) 
               REFERENCES phanloai_vattu_thanh_ly_iso(id)");
    echo "✓ Đã tạo constraint mới trỏ đúng bảng phanloai_vattu_thanh_ly_iso\n";
    
    // Bật lại foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "✓ Đã bật lại foreign key checks\n";
    
    echo "\n✅ Hoàn tất! Giờ có thể import Excel được rồi.\n";
    
} catch (PDOException $e) {
    echo "✗ Lỗi: " . $e->getMessage() . "\n";
    // Đảm bảo bật lại foreign key checks
    try {
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $e2) {}
}
