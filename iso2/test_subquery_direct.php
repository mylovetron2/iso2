<?php
require_once __DIR__ . '/config/database.php';

echo "<h2>Test Subquery Direct Execution</h2>";

try {
    $db = getDBConnection();
    
    // Test 1: Inner query only (should return all equipment with HC data)
    echo "<h3>Test 1: Inner Query (no filter)</h3>";
    $innerSql = "SELECT t.*, 
                   h.ngayhc as ngayhc_latest,
                   h.ngayhctt as ngayhctt_latest,
                   h.ttkt as ttkt_latest,
                   COALESCE(h.ngayhc, t.ngayktnghiemthu) as ngayhc_calc,
                   CASE 
                       WHEN COALESCE(h.ngayhc, t.ngayktnghiemthu) IS NOT NULL AND t.thoihankd IS NOT NULL THEN
                           DATEDIFF(DATE_ADD(COALESCE(h.ngayhc, t.ngayktnghiemthu), INTERVAL CAST(t.thoihankd AS SIGNED) MONTH), CURDATE())
                       ELSE NULL
                   END as days_to_expire
            FROM thietbihckd_iso t
            LEFT JOIN (
                SELECT tenmay, 
                       MAX(stt) as max_stt
                FROM hosohckd_iso
                WHERE ngayhc IS NOT NULL
                GROUP BY tenmay
            ) latest ON t.mavattu = latest.tenmay
            LEFT JOIN hosohckd_iso h ON h.stt = latest.max_stt
            ORDER BY t.stt DESC
            LIMIT 5";
    
    $stmt = $db->query($innerSql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Records from inner query: " . count($results) . "</p>";
    
    if (count($results) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Mã vật tư</th><th>Tên thiết bị</th><th>Ngày HC</th><th>Thời hạn (tháng)</th><th>Days to Expire</th></tr>";
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['mavattu']) . "</td>";
            echo "<td>" . htmlspecialchars($row['tenthietbi'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['ngayhc_latest'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['thoihankd'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['days_to_expire'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 2: Subquery with filter (saphethan)
    echo "<h3>Test 2: Subquery with Filter (sắp hết hạn: 0-30 days)</h3>";
    $fullSql = "SELECT * FROM (
        SELECT t.*, 
               h.ngayhc as ngayhc_latest,
               h.ngayhctt as ngayhctt_latest,
               h.ttkt as ttkt_latest,
               COALESCE(h.ngayhc, t.ngayktnghiemthu) as ngayhc_calc,
               CASE 
                   WHEN COALESCE(h.ngayhc, t.ngayktnghiemthu) IS NOT NULL AND t.thoihankd IS NOT NULL THEN
                       DATEDIFF(DATE_ADD(COALESCE(h.ngayhc, t.ngayktnghiemthu), INTERVAL CAST(t.thoihankd AS SIGNED) MONTH), CURDATE())
                   ELSE NULL
               END as days_to_expire
        FROM thietbihckd_iso t
        LEFT JOIN (
            SELECT tenmay, 
                   MAX(stt) as max_stt
            FROM hosohckd_iso
            WHERE ngayhc IS NOT NULL
            GROUP BY tenmay
        ) latest ON t.mavattu = latest.tenmay
        LEFT JOIN hosohckd_iso h ON h.stt = latest.max_stt
    ) subq
    WHERE days_to_expire IS NOT NULL AND days_to_expire <= 30 AND days_to_expire >= 0
    ORDER BY stt DESC
    LIMIT 5";
    
    $stmt = $db->query($fullSql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Records matching filter: " . count($results) . "</p>";
    
    if (count($results) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Mã vật tư</th><th>Tên thiết bị</th><th>Ngày HC</th><th>Thời hạn (tháng)</th><th>Days to Expire</th></tr>";
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['mavattu']) . "</td>";
            echo "<td>" . htmlspecialchars($row['tenthietbi'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['ngayhc_latest'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['thoihankd'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['days_to_expire'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 3: Count total records
    echo "<h3>Test 3: Count Total Records</h3>";
    $countSql = "SELECT COUNT(*) as total FROM thietbihckd_iso";
    $stmt = $db->query($countSql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total equipment in thietbihckd_iso: " . $result['total'] . "</p>";
    
    // Test 4: Count records with HC data
    echo "<h3>Test 4: Count Records with HC Data</h3>";
    $countWithHC = "SELECT COUNT(DISTINCT t.mavattu) as total 
                    FROM thietbihckd_iso t
                    LEFT JOIN (
                        SELECT tenmay, MAX(stt) as max_stt
                        FROM hosohckd_iso
                        WHERE ngayhc IS NOT NULL
                        GROUP BY tenmay
                    ) latest ON t.mavattu = latest.tenmay
                    WHERE latest.max_stt IS NOT NULL";
    $stmt = $db->query($countWithHC);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Equipment with HC records: " . $result['total'] . "</p>";
    
    // Test 5: Check if filter produces count
    echo "<h3>Test 5: Count Filtered Records (sắp hết hạn)</h3>";
    $countFiltered = "SELECT COUNT(*) as total FROM (
        SELECT t.*, 
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
    WHERE days_to_expire IS NOT NULL AND days_to_expire <= 30 AND days_to_expire >= 0";
    
    $stmt = $db->query($countFiltered);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Filtered count (sắp hết hạn): " . $result['total'] . "</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
