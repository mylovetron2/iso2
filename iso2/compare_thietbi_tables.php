<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection(true);
    
    echo "<h2>SO SÁNH 2 BẢNG: thietbi_iso vs thietbihckd_iso</h2>";
    
    echo "<h3>1. Cấu trúc bảng thietbi_iso:</h3>";
    $stmt = $db->query("DESCRIBE thietbi_iso");
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #ddd;'><th>Field</th><th>Type</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
    
    echo "<h3>2. Cấu trúc bảng thietbihckd_iso:</h3>";
    $stmt = $db->query("DESCRIBE thietbihckd_iso");
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #ddd;'><th>Field</th><th>Type</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
    
    echo "<h3>3. Số lượng dữ liệu:</h3>";
    $count1 = $db->query("SELECT COUNT(*) FROM thietbi_iso")->fetchColumn();
    $count2 = $db->query("SELECT COUNT(*) FROM thietbihckd_iso")->fetchColumn();
    echo "<ul>";
    echo "<li><strong>thietbi_iso:</strong> $count1 thiết bị</li>";
    echo "<li><strong>thietbihckd_iso:</strong> $count2 thiết bị</li>";
    echo "</ul>";
    
    echo "<h3>4. Kiểm tra thiết bị trong phiếu 2037:</h3>";
    
    // JOIN với thietbi_iso (hiện tại)
    echo "<h4>4a. JOIN với thietbi_iso (ĐANG DÙNG):</h4>";
    $sql1 = "SELECT h.stt, h.mavt, h.somay, t.stt as tb_stt, t.mavt as tb_mavt
             FROM hososcbd_iso h
             LEFT JOIN thietbi_iso t ON h.mavt = t.mavt AND h.somay = t.somay
             WHERE h.phieu = '2037'
             LIMIT 5";
    $stmt = $db->query($sql1);
    $results1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($results1);
    echo "</pre>";
    echo "<p><strong>Kết quả:</strong> " . count($results1) . " thiết bị match với thietbi_iso</p>";
    
    // JOIN với thietbihckd_iso (đề xuất)
    echo "<h4>4b. JOIN với thietbihckd_iso (ĐỀ XUẤT DÙNG):</h4>";
    $sql2 = "SELECT h.stt, h.mavt, h.somay, t.stt as tb_stt, t.mavattu as tb_mavattu
             FROM hososcbd_iso h
             LEFT JOIN thietbihckd_iso t ON h.mavt = t.mavattu AND h.somay = t.somay
             WHERE h.phieu = '2037'
             LIMIT 5";
    $stmt = $db->query($sql2);
    $results2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($results2);
    echo "</pre>";
    echo "<p><strong>Kết quả:</strong> " . count($results2) . " thiết bị match với thietbihckd_iso</p>";
    
    echo "<h3>5. JOIN đầy đủ với HC/KĐ (OPTION 3 - qua thietbi_iso → thietbihckd_iso):</h3>";
    $sql3 = "SELECT h.stt, h.mavt, h.somay, 
                    t.stt as tb_stt, t.mavt as tb_mavt,
                    thckd.stt as thckd_stt, thckd.mavattu, thckd.tenthietbi,
                    kd.thang_thuchien, kd.thang_dot2,
                    GROUP_CONCAT(DISTINCT MONTH(hc.ngayhc)) as inspected_months
             FROM hososcbd_iso h
             LEFT JOIN thietbi_iso t ON h.mavt = t.mavt AND h.somay = t.somay
             LEFT JOIN thietbihckd_iso thckd ON t.mavt = thckd.mavattu AND t.somay = thckd.somay
             LEFT JOIN kehoach_kiemdinh_2026_iso kd ON thckd.stt = kd.stt AND kd.nam_kehoach = 2026
             LEFT JOIN hosohckd_iso hc ON (thckd.mavattu = hc.tenmay OR thckd.somay = hc.tenmay) AND YEAR(hc.ngayhc) = 2026
             WHERE h.phieu = '2037'
             GROUP BY h.stt
             LIMIT 10";
    $stmt = $db->query($sql3);
    $results3 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #ddd;'>";
    echo "<th>H_STT</th><th>Mavt</th><th>Somay</th><th>TB_STT</th><th>THCKD_STT</th><th>Tên TB</th><th>KH_T1</th><th>KH_T2</th><th>Inspected</th>";
    echo "</tr>";
    foreach ($results3 as $row) {
        echo "<tr>";
        echo "<td>{$row['stt']}</td>";
        echo "<td>{$row['mavt']}</td>";
        echo "<td>{$row['somay']}</td>";
        echo "<td>" . ($row['tb_stt'] ?? 'NULL') . "</td>";
        echo "<td style='background: " . (empty($row['thckd_stt']) ? '#ffcccc' : '#ccffcc') . "'>" . ($row['thckd_stt'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['tenthietbi'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['thang_thuchien'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['thang_dot2'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['inspected_months'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Ghi chú:</strong></p>";
    echo "<ul>";
    echo "<li>TB_STT: ID trong thietbi_iso</li>";
    echo "<li>THCKD_STT: ID trong thietbihckd_iso (màu xanh = có mapping, đỏ = không có)</li>";
    echo "<li>Chỉ thiết bị có THCKD_STT mới có thông tin HC/KĐ</li>";
    echo "</ul>";
    
    echo "<h3>6. KẾT LUẬN:</h3>";
    echo "<div style='background: #ffffcc; padding: 15px; border: 2px solid #ffcc00;'>";
    echo "<p><strong>Để lấy thông tin HC/KĐ ĐÚNG, cần:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Dùng bảng <code>thietbihckd_iso</code> (bảng thiết bị HC/KĐ chuyên dụng)</li>";
    echo "<li>✅ JOIN: <code>h.mavt = t.mavattu</code> (lưu ý: thietbihckd_iso dùng cột 'mavattu', không phải 'mavt')</li>";
    echo "<li>✅ JOIN với hosohckd_iso: <code>t.mavattu = hc.tenmay OR t.somay = hc.tenmay</code></li>";
    echo "<li>❌ KHÔNG dùng bảng <code>thietbi_iso</code> (bảng thiết bị tổng quát, không liên quan HC/KĐ)</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
