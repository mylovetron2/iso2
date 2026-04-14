<?php
/**
 * Script sửa cấu trúc bảng hososcbd_tamdung
 * Nguyên nhân: Bảng có thể đã tồn tại với schema cũ hoặc thiếu cột
 * Truy cập: /iso2/fix_hososcbd_tamdung_schema.php
 */

require_once __DIR__ . '/config/database.php';

$db = getDBConnection();

echo "<h2>Sửa cấu trúc bảng hososcbd_tamdung</h2>";
echo "<hr>";

try {
    // Kiểm tra bảng có tồn tại không
    $checkTable = $db->query("SHOW TABLES LIKE 'hososcbd_tamdung'");
    if ($checkTable->rowCount() === 0) {
        echo "<p style='color: red;'>✗ Bảng hososcbd_tamdung chưa tồn tại. Vui lòng chạy migration chính trước.</p>";
        echo "<p><a href='run_migration_tamdung.php'>→ Chạy migration chính</a></p>";
        exit;
    }
    
    echo "<p style='color: green;'>✓ Bảng hososcbd_tamdung đã tồn tại</p>";
    
    // Lấy danh sách cột hiện có
    $columns = $db->query("SHOW COLUMNS FROM hososcbd_tamdung")->fetchAll(PDO::FETCH_ASSOC);
    $existingColumns = array_column($columns, 'Field');
    
    echo "<h3>Cột hiện có:</h3>";
    echo "<ul>";
    foreach ($existingColumns as $col) {
        echo "<li>$col</li>";
    }
    echo "</ul>";
    echo "<hr>";
    
    // Danh sách cột cần có
    $requiredColumns = [
        'id' => [
            'type' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'sql' => null // Không thể ALTER PRIMARY KEY dễ dàng
        ],
        'hoso' => [
            'type' => "VARCHAR(50) NOT NULL COMMENT 'Mã hồ sơ'",
            'sql' => "ALTER TABLE hososcbd_tamdung ADD COLUMN hoso VARCHAR(50) NOT NULL COMMENT 'Mã hồ sơ' AFTER id"
        ],
        'trangthai' => [
            'type' => "ENUM('tamdung', 'tieptuc') NOT NULL COMMENT 'Trạng thái'",
            'sql' => "ALTER TABLE hososcbd_tamdung ADD COLUMN trangthai ENUM('tamdung', 'tieptuc') NOT NULL COMMENT 'Trạng thái' AFTER hoso"
        ],
        'nguoi_thuchien' => [
            'type' => "VARCHAR(100) NOT NULL COMMENT 'Người thực hiện'",
            'sql' => "ALTER TABLE hososcbd_tamdung ADD COLUMN nguoi_thuchien VARCHAR(100) NOT NULL COMMENT 'Người thực hiện' AFTER trangthai"
        ],
        'ngay_thuchien' => [
            'type' => "DATETIME NOT NULL COMMENT 'Ngày thực hiện'",
            'sql' => "ALTER TABLE hososcbd_tamdung ADD COLUMN ngay_thuchien DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Ngày thực hiện' AFTER nguoi_thuchien"
        ],
        'lydo_tamdung' => [
            'type' => "TEXT COMMENT 'Lý do tạm dừng'",
            'sql' => "ALTER TABLE hososcbd_tamdung ADD COLUMN lydo_tamdung TEXT COMMENT 'Lý do tạm dừng' AFTER ngay_thuchien"
        ],
        'ghichu_tieptuc' => [
            'type' => "TEXT COMMENT 'Ghi chú tiếp tục'",
            'sql' => "ALTER TABLE hososcbd_tamdung ADD COLUMN ghichu_tieptuc TEXT COMMENT 'Ghi chú tiếp tục' AFTER lydo_tamdung"
        ],
        'created_at' => [
            'type' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp tạo'",
            'sql' => "ALTER TABLE hososcbd_tamdung ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp tạo' AFTER ghichu_tieptuc"
        ]
    ];
    
    echo "<h3>Kiểm tra và thêm cột thiếu:</h3>";
    $modified = false;
    
    foreach ($requiredColumns as $colName => $colInfo) {
        if (!in_array($colName, $existingColumns)) {
            if ($colInfo['sql'] === null) {
                echo "<p style='color: orange;'>⚠ Cột <strong>$colName</strong> thiếu nhưng không thể tự động thêm (PRIMARY KEY)</p>";
                continue;
            }
            
            echo "<p style='color: blue;'>→ Đang thêm cột <strong>$colName</strong>...</p>";
            
            try {
                $db->exec($colInfo['sql']);
                echo "<p style='color: green;'>✓ Đã thêm cột <strong>$colName</strong> thành công</p>";
                $modified = true;
            } catch (PDOException $e) {
                echo "<p style='color: red;'>✗ Lỗi khi thêm cột <strong>$colName</strong>: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p style='color: gray;'>○ Cột <strong>$colName</strong> đã tồn tại</p>";
        }
    }
    
    if (!$modified) {
        echo "<p style='color: green;'><strong>✓ Tất cả cột đã đầy đủ, không cần sửa gì!</strong></p>";
    }
    
    echo "<hr>";
    
    // Kiểm tra indexes
    echo "<h3>Kiểm tra indexes:</h3>";
    $indexes = $db->query("SHOW INDEX FROM hososcbd_tamdung")->fetchAll(PDO::FETCH_ASSOC);
    $existingIndexes = array_unique(array_column($indexes, 'Key_name'));
    
    echo "<p>Indexes hiện có: <strong>" . implode(', ', $existingIndexes) . "</strong></p>";
    
    $requiredIndexes = [
        'idx_hoso' => "ALTER TABLE hososcbd_tamdung ADD INDEX idx_hoso (hoso)",
        'idx_trangthai' => "ALTER TABLE hososcbd_tamdung ADD INDEX idx_trangthai (trangthai)",
        'idx_ngay' => "ALTER TABLE hososcbd_tamdung ADD INDEX idx_ngay (ngay_thuchien)",
        'idx_hoso_trangthai' => "ALTER TABLE hososcbd_tamdung ADD INDEX idx_hoso_trangthai (hoso, trangthai)"
    ];
    
    foreach ($requiredIndexes as $idxName => $sql) {
        if (!in_array($idxName, $existingIndexes)) {
            echo "<p style='color: blue;'>→ Đang thêm index <strong>$idxName</strong>...</p>";
            try {
                $db->exec($sql);
                echo "<p style='color: green;'>✓ Đã thêm index <strong>$idxName</strong></p>";
            } catch (PDOException $e) {
                // Bỏ qua nếu index đã tồn tại với tên khác
                if (strpos($e->getMessage(), 'Duplicate key') === false) {
                    echo "<p style='color: orange;'>⚠ Không thể thêm index <strong>$idxName</strong>: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
        } else {
            echo "<p style='color: gray;'>○ Index <strong>$idxName</strong> đã tồn tại</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Kết quả:</h3>";
    
    // Hiển thị cấu trúc cuối cùng
    $finalColumns = $db->query("SHOW COLUMNS FROM hososcbd_tamdung")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($finalColumns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p style='color: green; font-size: 18px;'><strong>✓ Hoàn tất! Bảng đã sẵn sàng sử dụng.</strong></p>";
    echo "<p><a href='check_tamdung_migration.php' style='color: blue;'>→ Kiểm tra lại migration</a></p>";
    echo "<p><a href='hososcbd.php' style='color: blue;'>→ Về trang Hồ sơ SCBĐ</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>✗ Lỗi:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
