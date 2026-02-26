<?php
/**
 * Insert test data for congviec testing
 */
require_once __DIR__ . '/config/database.php';

$db = getDBConnection();

echo "<h1>Tạo dữ liệu test</h1>";

try {
    // Check if capdo_baocuong_iso has data
    $stmt = $db->query("SELECT COUNT(*) FROM capdo_baocuong_iso");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo "<p>Đang tạo cấp độ bảo dưỡng mẫu...</p>";
        
        $db->exec("
            INSERT INTO capdo_baocuong_iso (ma_capdo, ten_capdo, kpi_gio_chuan, mau_sac, mo_ta, trang_thai, thu_tu) VALUES
            ('CAP1', 'Cấp 1 - Đơn giản', 2, '#4CAF50', 'Công việc đơn giản, ít phức tạp', 1, 1),
            ('CAP2', 'Cấp 2 - Trung bình', 4, '#FF9800', 'Công việc trung bình, độ phức tạp vừa phải', 1, 2),
            ('CAP3', 'Cấp 3 - Phức tạp', 8, '#F44336', 'Công việc phức tạp, yêu cầu kỹ năng cao', 1, 3)
        ");
        
        echo "<p style='color: green'>✓ Đã tạo 3 cấp độ bảo dưỡng</p>";
    } else {
        echo "<p>Cấp độ bảo dưỡng đã có $count records</p>";
    }
    
    // Show current data
    $stmt = $db->query("SELECT * FROM capdo_baocuong_iso ORDER BY stt");
    $capdos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Cấp độ bảo dưỡng hiện có:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>STT</th><th>Mã</th><th>Tên</th><th>KPI Giờ</th></tr>";
    foreach ($capdos as $cd) {
        echo "<tr><td>{$cd['stt']}</td><td>{$cd['ma_capdo']}</td><td>{$cd['ten_capdo']}</td><td>{$cd['kpi_gio_chuan']}</td></tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    
    // Show resume data
    $stmt = $db->query("SELECT stt, hoten FROM resume ORDER BY stt LIMIT 10");
    $nhanviens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Nhân viên (10 records đầu):</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>STT</th><th>Họ tên</th></tr>";
    foreach ($nhanviens as $nv) {
        echo "<tr><td>{$nv['stt']}</td><td>{$nv['hoten']}</td></tr>";
    }
    echo "</table>";
    
    if (count($nhanviens) > 0 && count($capdos) > 0) {
        $testNhanvienStt = $nhanviens[0]['stt'];
        $testCapdoStt = $capdos[0]['stt'];
        
        echo "<hr>";
        echo "<h2>📝 Cập nhật test script:</h2>";
        echo "<p>Sử dụng giá trị sau trong <code>test_congviec_create.php</code>:</p>";
        echo "<pre>";
        echo "'nhanvien_stt' => '$testNhanvienStt',  // {$nhanviens[0]['hoten']}\n";
        echo "'capdo_stt' => '$testCapdoStt',  // {$capdos[0]['ten_capdo']}\n";
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 2px solid red;'>";
    echo "Lỗi: " . $e->getMessage();
    echo "</div>";
}
