<?php
// Script thực thi tạo bảng kehoach_kiemdinh_2026
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config/database.php';

echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h2 { color: #333; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    .info { background: #e7f3fe; padding: 10px; border-left: 4px solid #2196F3; margin: 10px 0; }
</style>";

echo "<h2>Tạo bảng kehoach_kiemdinh_2026_iso</h2>";

try {
    $db = getDBConnection(true);
    
    // Đọc file SQL
    $sqlFile = __DIR__ . '/create_table_kehoach_kiemdinh_2026.sql';
    $sql = file_get_contents($sqlFile);
    
    // Thực thi SQL
    $db->exec($sql);
    echo "<p class='success'>✓ Bảng 'kehoach_kiemdinh_2026_iso' đã được tạo thành công!</p>";
    
    // Hiển thị cấu trúc bảng
    echo "<h3>Cấu trúc bảng kehoach_kiemdinh_2026_iso:</h3>";
    $stmt = $db->query("DESCRIBE kehoach_kiemdinh_2026_iso");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr>
            <th>Field</th>
            <th>Type</th>
            <th>Null</th>
            <th>Key</th>
            <th>Default</th>
            <th>Comment</th>
          </tr>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Comment'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='info'>";
    echo "<h3>📌 Thông tin bảng:</h3>";
    echo "<ul>";
    echo "<li><b>Tên bảng:</b> kehoach_kiemdinh_2026_iso</li>";
    echo "<li><b>Mục đích:</b> Lưu trữ kế hoạch chuẩn chỉnh, kiểm định thiết bị ĐVLTH năm 2026</li>";
    echo "<li><b>Số cột:</b> " . count($columns) . "</li>";
    echo "<li><b>Primary Key:</b> id (AUTO_INCREMENT)</li>";
    echo "<li><b>Charset:</b> utf8mb4_unicode_ci</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>📝 Các cột chính:</h3>";
    echo "<ul>";
    echo "<li><b>ten_thietbi:</b> Tên thiết bị cần kiểm định</li>";
    echo "<li><b>ky_hieu:</b> Ký hiệu thiết bị</li>";
    echo "<li><b>hang_sanxuat:</b> Hãng sản xuất</li>";
    echo "<li><b>so_may:</b> Số máy/Serial Number</li>";
    echo "<li><b>donvi:</b> Đơn vị quản lý</li>";
    echo "<li><b>vitri:</b> Vị trí lắp đặt</li>";
    echo "<li><b>phuongphap_kiemdinh:</b> HC (Hiệu chuẩn) hoặc KD (Kiểm định)</li>";
    echo "<li><b>thang_thuchien:</b> Tháng dự kiến thực hiện trong năm 2026</li>";
    echo "<li><b>tinhtrang:</b> Trạng thái thực hiện (Chưa/Đang/Đã thực hiện)</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔍 Indexes đã tạo:</h3>";
    $stmt = $db->query("SHOW INDEX FROM kehoach_kiemdinh_2026_iso");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Key Name</th><th>Column Name</th><th>Index Type</th></tr>";
    foreach ($indexes as $idx) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($idx['Key_name']) . "</td>";
        echo "<td>" . htmlspecialchars($idx['Column_name']) . "</td>";
        echo "<td>" . htmlspecialchars($idx['Index_type']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    echo "<h3>✅ Hoàn tất!</h3>";
    echo "<p>Bạn có thể bắt đầu nhập dữ liệu từ file PDF/HTML vào bảng này.</p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
}
