<?php
/**
 * Check cấp độ bảo dưỡng data
 */
require_once __DIR__ . '/config/database.php';

$db = getDBConnection();

echo "<h1>Kiểm tra dữ liệu Cấp độ bảo dưỡng</h1>";

// Check capdo_baocuong_iso table
try {
    $stmt = $db->query("SELECT * FROM capdo_baocuong_iso ORDER BY stt");
    $capdos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Bảng capdo_baocuong_iso (" . count($capdos) . " records):</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>STT</th><th>Mã</th><th>Tên</th><th>KPI Giờ</th><th>Màu</th><th>Trạng thái</th></tr>";
    foreach ($capdos as $cd) {
        echo "<tr>";
        echo "<td>{$cd['stt']}</td>";
        echo "<td>{$cd['ma_capdo']}</td>";
        echo "<td>{$cd['ten_capdo']}</td>";
        echo "<td>{$cd['kpi_gio_chuan']}</td>";
        echo "<td style='background-color: {$cd['mau_sac']}'>{$cd['mau_sac']}</td>";
        echo "<td>{$cd['trang_thai']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='color: red'>Lỗi: " . $e->getMessage() . "</div>";
}

echo "<hr>";

// Check resume table
try {
    $stmt = $db->query("SELECT stt, hoten FROM resume ORDER BY stt LIMIT 10");
    $nhanviens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Bảng resume (10 records đầu):</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>STT</th><th>Họ tên</th></tr>";
    foreach ($nhanviens as $nv) {
        echo "<tr>";
        echo "<td>{$nv['stt']}</td>";
        echo "<td>{$nv['hoten']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='color: red'>Lỗi: " . $e->getMessage() . "</div>";
}
