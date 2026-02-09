<?php
/**
 * Script thực thi thêm cột số serial vào bảng vật tư thanh lý
 */

require_once __DIR__ . '/config/database.php';

try {
    echo "<h2>Thêm cột số serial vào bảng vật tư thanh lý</h2>";
    
    // Đọc file SQL
    $sql = file_get_contents(__DIR__ . '/add_serial_column.sql');
    
    if ($sql === false) {
        throw new Exception("Không thể đọc file add_serial_column.sql");
    }
    
    // Tách các câu lệnh SQL
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    echo "<p>Tìm thấy " . count($statements) . " câu lệnh SQL</p>";
    
    $conn = getDbConnection();
    $conn->beginTransaction();
    
    $executed = 0;
    foreach ($statements as $statement) {
        if (!empty(trim($statement))) {
            echo "<pre style='background: #f0f0f0; padding: 10px; margin: 10px 0;'>" . htmlspecialchars($statement) . "</pre>";
            $conn->exec($statement);
            $executed++;
            echo "<p style='color: green;'>✓ Thực thi thành công</p>";
        }
    }
    
    $conn->commit();
    
    echo "<h3 style='color: green;'>✓ Hoàn thành! Đã thêm cột số serial vào bảng vattu_thanh_ly_iso</h3>";
    echo "<p>Tổng số câu lệnh đã thực thi: $executed</p>";
    
    // Kiểm tra cấu trúc bảng sau khi thêm
    echo "<h3>Cấu trúc bảng sau khi thêm:</h3>";
    $stmt = $conn->query("DESCRIBE vattu_thanh_ly_iso");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><a href='vattuthanhly.php'>→ Quay lại quản lý vật tư thanh lý</a></p>";
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    echo "<p style='color: red;'>❌ Lỗi: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
