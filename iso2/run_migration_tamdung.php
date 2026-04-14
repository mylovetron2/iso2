<?php
/**
 * Script chạy migration: Tạo bảng hososcbd_tamdung
 * Chạy file này một lần để tạo bảng và thêm cột is_tamdung
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';

// Require admin permission
requireAuth();
if (!hasPermission('hososcbd.edit')) {
    die('Chỉ admin mới có quyền chạy migration');
}

$db = getDbConnection();

try {
    echo "<h2>Đang chạy migration: Tạo bảng hososcbd_tamdung</h2>";
    echo "<hr>";
    
    // Đọc file SQL
    $sqlFile = __DIR__ . '/migrations/create_hososcbd_tamdung_table.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("File migration không tồn tại: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Tách các câu lệnh SQL
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        echo "<p>Đang thực thi: <code>" . htmlspecialchars(substr($statement, 0, 100)) . "...</code></p>";
        
        try {
            $db->exec($statement);
            echo "<p style='color: green;'>✓ Thành công</p>";
        } catch (PDOException $e) {
            // Bỏ qua lỗi nếu bảng/cột đã tồn tại
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "<p style='color: orange;'>⚠ Đã tồn tại, bỏ qua</p>";
            } else {
                throw $e;
            }
        }
    }
    
    echo "<hr>";
    echo "<h3 style='color: green;'>✓ Migration hoàn tất!</h3>";
    echo "<p>Bảng <strong>hososcbd_tamdung</strong> đã được tạo thành công.</p>";
    echo "<p>Cột <strong>is_tamdung</strong> đã được thêm vào bảng <strong>hososcbd_iso</strong>.</p>";
    echo "<br>";
    echo "<a href='hososcbd.php'>← Quay lại Hồ sơ SCBĐ</a> | ";
    echo "<a href='baocao_hososcbd_tamdung.php?trangthai=dang_tam_dung'>Xem danh sách tạm dừng →</a> | ";
    echo "<a href='baocao_hososcbd_tamdung.php'>Xem báo cáo lịch sử →</a>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>✗ Lỗi khi chạy migration:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
