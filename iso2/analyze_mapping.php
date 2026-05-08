<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection(true);
    
    echo "<h2>PHÂN TÍCH MAPPING giữa thietbi_iso và thietbihckd_iso</h2>";
    
    // Lấy thiết bị từ phiếu 2037
    echo "<h3>1. Thiết bị trong phiếu 2037:</h3>";
    $sql = "SELECT h.stt, h.mavt, h.somay, t.stt as tb_stt, t.mavt as tb_mavt, t.somay as tb_somay
            FROM hososcbd_iso h
            LEFT JOIN thietbi_iso t ON h.mavt = t.mavt AND h.somay = t.somay
            WHERE h.phieu = '2037'
            LIMIT 5";
    $results = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #ddd;'><th>H_Mavt</th><th>H_Somay</th><th>T_Mavt</th><th>T_Somay</th></tr>";
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>{$row['mavt']}</td>";
        echo "<td>{$row['somay']}</td>";
        echo "<td>" . ($row['tb_mavt'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['tb_somay'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Thử tìm mapping trong thietbihckd_iso
    echo "<h3>2. Tìm kiếm trong thietbihckd_iso:</h3>";
    foreach ($results as $row) {
        $mavt = $row['mavt'];
        $somay = $row['somay'];
        
        echo "<h4>Thiết bị: {$mavt} - {$somay}</h4>";
        
        // Tìm theo mavattu = mavt
        $sql1 = "SELECT stt, mavattu, somay, tenthietbi FROM thietbihckd_iso WHERE mavattu = :mavt LIMIT 3";
        $stmt = $db->prepare($sql1);
        $stmt->execute([':mavt' => $mavt]);
        $match1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Tìm theo <code>mavattu = '{$mavt}'</code>: <strong>" . count($match1) . " kết quả</strong></p>";
        if (!empty($match1)) {
            echo "<pre>" . print_r($match1, true) . "</pre>";
        }
        
        // Tìm theo somay = somay
        $sql2 = "SELECT stt, mavattu, somay, tenthietbi FROM thietbihckd_iso WHERE somay = :somay LIMIT 3";
        $stmt = $db->prepare($sql2);
        $stmt->execute([':somay' => $somay]);
        $match2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Tìm theo <code>somay = '{$somay}'</code>: <strong>" . count($match2) . " kết quả</strong></p>";
        if (!empty($match2)) {
            echo "<pre>" . print_r($match2, true) . "</pre>";
        }
        
        // Tìm LIKE
        $sql3 = "SELECT stt, mavattu, somay, tenthietbi FROM thietbihckd_iso WHERE mavattu LIKE :mavt OR somay LIKE :somay LIMIT 3";
        $stmt = $db->prepare($sql3);
        $stmt->execute([':mavt' => "%{$mavt}%", ':somay' => "%{$somay}%"]);
        $match3 = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Tìm theo <code>LIKE '%{$mavt}%' hoặc LIKE '%{$somay}%'</code>: <strong>" . count($match3) . " kết quả</strong></p>";
        if (!empty($match3)) {
            echo "<pre>" . print_r($match3, true) . "</pre>";
        }
        
        echo "<hr>";
    }
    
    echo "<h3>3. KẾT LUẬN:</h3>";
    echo "<div style='background: #ffffcc; padding: 15px; border: 2px solid #ffcc00;'>";
    echo "<p><strong>Nếu KHÔNG tìm thấy mapping:</strong></p>";
    echo "<ul>";
    echo "<li>Thiết bị trong phiếu yêu cầu (hososcbd_iso) KHÔNG nằm trong danh mục HC/KĐ (thietbihckd_iso)</li>";
    echo "<li>→ Cột HC/KĐ sẽ để TRỐNG (không có thông tin)</li>";
    echo "<li>→ Đây là HÀNH VI ĐÚNG vì không phải thiết bị nào cũng cần HC/KĐ</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
