<?php
// Script nhập dữ liệu vào bảng kehoach_kiemdinh_2026
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config/database.php';

echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h2 { color: #333; }
    .success { color: green; }
    .error { color: red; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    tr:nth-child(even) { background-color: #f2f2f2; }
</style>";

echo "<h2>Nhập dữ liệu vào bảng kehoach_kiemdinh_2026_iso</h2>";

// Dữ liệu cần nhập
$data = [
    ['Bảng bắn mìn LEE', 'LEE', '411136', '2005', 'USA', 'KĐ'],
    ['Bảng bắn mìn LEE', 'LEE', '411143', '2005', 'USA', 'KĐ'],
    ['Bảng bắn mìn LEE', 'LEE', '411140', '2005', 'USA', 'KĐ'],
    ['Bảng bắn mìn LEE', 'LEE', '506238', '2005', 'USA', 'KĐ'],
    ['Bảng bắn mìn LEE', 'LEE', '506237', '2005', 'USA', 'KĐ'],
    ['Bảng bắn mìn FS17', 'FS17', '1', '2005', 'USA', 'KĐ'],
    ['Bảng bắn mìn FS17', 'FS17', '2', '2005', 'USA', 'KĐ'],
    ['Bảng bắn mìn FS17', 'FS17', '3', '2005', 'USA', 'KĐ'],
    ['Bảng bắn mìn FS17', 'FS17', '4', '2005', 'USA', 'KĐ'],
    ['Bảng bắn mìn FS17', 'FS17', '5', '2005', 'USA', 'KĐ'],
];

try {
    $db = getDBConnection(true);
    
    // Chuẩn bị câu lệnh INSERT
    $sql = "INSERT INTO kehoach_kiemdinh_2026_iso 
            (ten_thietbi, ky_hieu, so_may, hang_sanxuat, ghichu, nam_kehoach) 
            VALUES (:ten_thietbi, :ky_hieu, :so_may, :hang_sanxuat, :ghichu, 2026)";
    
    $stmt = $db->prepare($sql);
    
    $success = 0;
    $failed = 0;
    $errors = [];
    
    foreach ($data as $index => $row) {
        try {
            $stmt->execute([
                ':ten_thietbi' => $row[0],
                ':ky_hieu' => $row[1],
                ':so_may' => $row[2],
                ':hang_sanxuat' => $row[4],
                ':ghichu' => 'Năm sản xuất: ' . $row[3] . ' - Phương pháp: ' . $row[5]
            ]);
            $success++;
        } catch (PDOException $e) {
            $failed++;
            $errors[] = "Dòng " . ($index + 1) . ": " . $e->getMessage();
        }
    }
    
    echo "<div class='success'>";
    echo "<h3>✓ Kết quả nhập dữ liệu:</h3>";
    echo "<p><b>Thành công:</b> $success bản ghi</p>";
    echo "<p><b>Thất bại:</b> $failed bản ghi</p>";
    echo "</div>";
    
    if (!empty($errors)) {
        echo "<div class='error'>";
        echo "<h3>Chi tiết lỗi:</h3>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    // Hiển thị dữ liệu đã nhập
    echo "<h3>Dữ liệu đã nhập:</h3>";
    $stmt = $db->query("SELECT * FROM kehoach_kiemdinh_2026_iso ORDER BY id DESC LIMIT 10");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($records)) {
        echo "<table>";
        echo "<tr>
                <th>ID</th>
                <th>STT</th>
                <th>Tên thiết bị</th>
                <th>Ký hiệu</th>
                <th>Số máy</th>
                <th>Hãng SX</th>
                <th>Tháng TH</th>
                <th>Đơn vị TH</th>
                <th>Ghi chú</th>
                <th>Năm KH</th>
              </tr>";
        
        foreach ($records as $rec) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($rec['id']) . "</td>";
            echo "<td>" . htmlspecialchars($rec['stt'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($rec['ten_thietbi']) . "</td>";
            echo "<td>" . htmlspecialchars($rec['ky_hieu'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($rec['so_may']) . "</td>";
            echo "<td>" . htmlspecialchars($rec['hang_sanxuat'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($rec['thang_thuchien'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($rec['donvi_thuchien'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($rec['ghichu'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($rec['nam_kehoach']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Thống kê
    $stmt = $db->query("SELECT COUNT(*) as total FROM kehoach_kiemdinh_2026_iso");
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><b>Tổng số bản ghi trong bảng:</b> " . $total['total'] . "</p>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Lỗi kết nối database:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
