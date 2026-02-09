<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection();
    
    echo "<h2>Thêm cột phân loại vào bảng vật tư thanh lý</h2>";
    
    // Đọc file SQL
    $sqlFile = __DIR__ . '/add_phanloai_vattu_thanh_ly.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Không tìm thấy file SQL: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        throw new Exception("Không thể đọc file SQL");
    }
    
    // Tách các câu lệnh SQL
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    $db->beginTransaction();
    
    foreach ($statements as $statement) {
        if (trim($statement)) {
            echo "<p>Đang thực thi: <pre>" . htmlspecialchars(substr($statement, 0, 200)) . "...</pre></p>";
            $db->exec($statement);
            echo "<p style='color: green;'>✓ Thành công</p>";
        }
    }
    
    $db->commit();
    
    echo "<h3 style='color: green;'>✓ Hoàn thành! Đã thêm cột phân loại vào bảng vật tư thanh lý</h3>";
    echo "<p><a href='vattuthanhly.php'>← Quay lại trang quản lý vật tư</a></p>";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "<h3 style='color: red;'>✗ Lỗi:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
