<?php
/**
 * Debug script để test SQL query thực tế
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

echo "<h1>🔍 Debug SQL Query Filter</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
    .error { color: red; }
    .success { color: green; }
</style>";

try {
    $db = getDBConnection();
    echo "<p class='success'>✓ Connected to database</p>";
    
    // Test 1: Query without filter
    echo "<h2>Test 1: Query cơ bản (no filter)</h2>";
    $sql1 = "SELECT t.stt, t.mavattu, t.tenviettat, 
                   h.ngayhc as ngayhc_latest,
                   COALESCE(h.ngayhc, t.ngayktnghiemthu) as ngayhc_calc,
                   t.thoihankd,
                   CASE 
                       WHEN COALESCE(h.ngayhc, t.ngayktnghiemthu) IS NOT NULL AND t.thoihankd IS NOT NULL THEN
                           DATEDIFF(DATE_ADD(COALESCE(h.ngayhc, t.ngayktnghiemthu), INTERVAL CAST(t.thoihankd AS SIGNED) MONTH), CURDATE())
                       ELSE NULL
                   END as days_to_expire
            FROM thietbihckd_iso t
            LEFT JOIN (
                SELECT tenmay, MAX(stt) as max_stt
                FROM hosohckd_iso
                WHERE ngayhc IS NOT NULL
                GROUP BY tenmay
            ) latest ON t.mavattu = latest.tenmay
            LEFT JOIN hosohckd_iso h ON h.stt = latest.max_stt
            ORDER BY t.stt DESC
            LIMIT 5";
    
    echo "<pre>" . htmlspecialchars($sql1) . "</pre>";
    
    $stmt1 = $db->query($sql1);
    $results1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Kết quả: " . count($results1) . " records</p>";
    if (!empty($results1)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>STT</th><th>Mã VT</th><th>Tên VT</th><th>Ngày HC</th><th>Thời hạn</th><th>Days to expire</th></tr>";
        foreach ($results1 as $row) {
            echo "<tr>";
            echo "<td>{$row['stt']}</td>";
            echo "<td>{$row['mavattu']}</td>";
            echo "<td>{$row['tenviettat']}</td>";
            echo "<td>" . ($row['ngayhc_latest'] ?? 'NULL') . "</td>";
            echo "<td>" . ($row['thoihankd'] ?? 'NULL') . "</td>";
            echo "<td>" . ($row['days_to_expire'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 2: Query with HAVING filter
    echo "<h2>Test 2: Query với filter 'saphethan'</h2>";
    $sql2 = "SELECT t.stt, t.mavattu, t.tenviettat, 
                   h.ngayhc as ngayhc_latest,
                   COALESCE(h.ngayhc, t.ngayktnghiemthu) as ngayhc_calc,
                   t.thoihankd,
                   CASE 
                       WHEN COALESCE(h.ngayhc, t.ngayktnghiemthu) IS NOT NULL AND t.thoihankd IS NOT NULL THEN
                           DATEDIFF(DATE_ADD(COALESCE(h.ngayhc, t.ngayktnghiemthu), INTERVAL CAST(t.thoihankd AS SIGNED) MONTH), CURDATE())
                       ELSE NULL
                   END as days_to_expire
            FROM thietbihckd_iso t
            LEFT JOIN (
                SELECT tenmay, MAX(stt) as max_stt
                FROM hosohckd_iso
                WHERE ngayhc IS NOT NULL
                GROUP BY tenmay
            ) latest ON t.mavattu = latest.tenmay
            LEFT JOIN hosohckd_iso h ON h.stt = latest.max_stt
            HAVING days_to_expire IS NOT NULL AND days_to_expire <= 30 AND days_to_expire >= 0
            ORDER BY t.stt DESC
            LIMIT 5";
    
    echo "<pre>" . htmlspecialchars($sql2) . "</pre>";
    
    try {
        $stmt2 = $db->query($sql2);
        $results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='success'>✓ Query thành công: " . count($results2) . " records</p>";
        if (!empty($results2)) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>STT</th><th>Mã VT</th><th>Tên VT</th><th>Ngày HC</th><th>Thời hạn</th><th>Days to expire</th></tr>";
            foreach ($results2 as $row) {
                echo "<tr>";
                echo "<td>{$row['stt']}</td>";
                echo "<td>{$row['mavattu']}</td>";
                echo "<td>{$row['tenviettat']}</td>";
                echo "<td>" . ($row['ngayhc_latest'] ?? 'NULL') . "</td>";
                echo "<td>" . ($row['thoihankd'] ?? 'NULL') . "</td>";
                echo "<td><strong>" . ($row['days_to_expire'] ?? 'NULL') . "</strong></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Lỗi: " . $e->getMessage() . "</p>";
    }
    
    // Test 3: Check if HAVING + ORDER BY works
    echo "<h2>Test 3: HAVING + ORDER BY (potential issue)</h2>";
    echo "<p>MySQL có thể yêu cầu ORDER BY sau HAVING hoặc cần subquery</p>";
    
    $sql3 = "SELECT * FROM (
                SELECT t.stt, t.mavattu, t.tenviettat, 
                       h.ngayhc as ngayhc_latest,
                       t.thoihankd,
                       CASE 
                           WHEN COALESCE(h.ngayhc, t.ngayktnghiemthu) IS NOT NULL AND t.thoihankd IS NOT NULL THEN
                               DATEDIFF(DATE_ADD(COALESCE(h.ngayhc, t.ngayktnghiemthu), INTERVAL CAST(t.thoihankd AS SIGNED) MONTH), CURDATE())
                           ELSE NULL
                       END as days_to_expire
                FROM thietbihckd_iso t
                LEFT JOIN (
                    SELECT tenmay, MAX(stt) as max_stt
                    FROM hosohckd_iso
                    WHERE ngayhc IS NOT NULL
                    GROUP BY tenmay
                ) latest ON t.mavattu = latest.tenmay
                LEFT JOIN hosohckd_iso h ON h.stt = latest.max_stt
            ) subq
            WHERE days_to_expire IS NOT NULL AND days_to_expire <= 30 AND days_to_expire >= 0
            ORDER BY stt DESC
            LIMIT 5";
    
    echo "<pre>" . htmlspecialchars($sql3) . "</pre>";
    
    try {
        $stmt3 = $db->query($sql3);
        $results3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='success'>✓ Subquery approach thành công: " . count($results3) . " records</p>";
        if (!empty($results3)) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>STT</th><th>Mã VT</th><th>Tên VT</th><th>Ngày HC</th><th>Thời hạn</th><th>Days to expire</th></tr>";
            foreach ($results3 as $row) {
                echo "<tr>";
                echo "<td>{$row['stt']}</td>";
                echo "<td>{$row['mavattu']}</td>";
                echo "<td>{$row['tenviettat']}</td>";
                echo "<td>" . ($row['ngayhc_latest'] ?? 'NULL') . "</td>";
                echo "<td>" . ($row['thoihankd'] ?? 'NULL') . "</td>";
                echo "<td><strong>" . ($row['days_to_expire'] ?? 'NULL') . "</strong></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Lỗi: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Lỗi kết nối: " . $e->getMessage() . "</p>";
}
?>
