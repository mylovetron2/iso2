<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/models/HoSoHCKD.php';

echo "<h2>Xóa Record Test</h2>";

try {
    $model = new HoSoHCKD();
    
    // Find test records
    $db = getDBConnection();
    $stmt = $db->query("SELECT * FROM hosohckd_iso WHERE sohs = 'TEST-001' OR nhanvien = 'Test User'");
    $testRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($testRecords)) {
        echo "<p style='color:orange;'>Không tìm thấy record test nào</p>";
    } else {
        echo "<h3>Tìm thấy " . count($testRecords) . " record(s) test:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>STT</th><th>Số HS</th><th>Tên máy</th><th>Nhân viên</th><th>Ngày HC</th></tr>";
        foreach ($testRecords as $record) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($record['stt']) . "</td>";
            echo "<td>" . htmlspecialchars($record['sohs']) . "</td>";
            echo "<td>" . htmlspecialchars($record['tenmay']) . "</td>";
            echo "<td>" . htmlspecialchars($record['nhanvien']) . "</td>";
            echo "<td>" . htmlspecialchars($record['ngayhc']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Delete them
        echo "<h3>Đang xóa...</h3>";
        $deleteStmt = $db->prepare("DELETE FROM hosohckd_iso WHERE sohs = 'TEST-001' OR nhanvien = 'Test User'");
        $deleteStmt->execute();
        
        $deletedCount = $deleteStmt->rowCount();
        echo "<p style='color:green;font-size:18px;'><strong>✓ Đã xóa " . $deletedCount . " record(s)</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><a href='test_save_direct.php'>← Back to test page</a></p>";
echo "<p><a href='bangcanhbao.php'>← Back to Bảng cảnh báo</a></p>";
