<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>1. Cấu trúc bảng thietbihckd_iso</h2>";
    $stmt = $db->query("DESCRIBE thietbihckd_iso");
    echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
    
    echo "<h2>2. Cấu trúc bảng hosohckd_iso</h2>";
    $stmt = $db->query("DESCRIBE hosohckd_iso");
    echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
    
    echo "<h2>3. Sample data from hosohckd_iso (10 records)</h2>";
    $stmt = $db->query("SELECT stt, sohs, tenmay, ngayhc, nhanvien FROM hosohckd_iso LIMIT 10");
    echo "<table border='1'><tr><th>STT</th><th>Số HS</th><th>Tên máy</th><th>Ngày HC</th><th>Nhân viên</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>{$row['stt']}</td><td>{$row['sohs']}</td><td>{$row['tenmay']}</td><td>{$row['ngayhc']}</td><td>{$row['nhanvien']}</td></tr>";
    }
    echo "</table>";
    
    echo "<h2>4. Test join query</h2>";
    $nam = 2024;
    $sql = "
        SELECT 
            t.mavattu,
            t.somay,
            t.tenthietbi,
            t.model,
            t.hangsx,
            h.ngayhc,
            MONTH(h.ngayhc) as thangkd,
            YEAR(h.ngayhc) as namkd
        FROM thietbihckd_iso t
        LEFT JOIN hosohckd_iso h ON t.mavattu = h.tenmay 
            AND YEAR(h.ngayhc) = :nam
        LIMIT 10
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(['nam' => $nam]);
    
    echo "<table border='1'><tr><th>Mã vật tư</th><th>Số máy</th><th>Tên TB</th><th>Model</th><th>Hãng SX</th><th>Ngày HC</th><th>Tháng</th><th>Năm</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['mavattu']}</td>";
        echo "<td>{$row['somay']}</td>";
        echo "<td>{$row['tenthietbi']}</td>";
        echo "<td>{$row['model']}</td>";
        echo "<td>{$row['hangsx']}</td>";
        echo "<td>{$row['ngayhc']}</td>";
        echo "<td>{$row['thangkd']}</td>";
        echo "<td>{$row['namkd']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
