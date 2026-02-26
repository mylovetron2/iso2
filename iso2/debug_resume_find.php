<?php
require_once __DIR__ . '/config/database.php';

$db = getDBConnection();

echo "<h1>Debug Resume.find()</h1>";

// Test find method directly
require_once __DIR__ . '/models/Resume.php';
$resumeModel = new Resume();

echo "<h2>Test Resume::find(1):</h2>";
$result = $resumeModel->find(1);
echo "<pre>";
var_dump($result);
echo "</pre>";

echo "<hr>";

// Query resume table directly
echo "<h2>Query resume table trực tiếp:</h2>";
try {
    $stmt = $db->query("SELECT * FROM resume ORDER BY stt LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Tìm thấy " . count($rows) . " records</p>";
    
    if (count($rows) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>STT</th><th>Họ tên</th><th>Chức danh</th><th>Đơn vị</th></tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            echo "<td>{$row['stt']}</td>";
            echo "<td>" . htmlspecialchars($row['hoten'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['chucdanh'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['donvi'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<hr>";
        echo "<h2>✅ Sử dụng STT này trong test:</h2>";
        $firstStt = $rows[0]['stt'];
        echo "<pre>'nhanvien_stt' => '$firstStt',  // " . htmlspecialchars($rows[0]['hoten'] ?? '') . "</pre>";
        
        // Test find with first STT
        echo "<h3>Test Resume::find($firstStt):</h3>";
        $testResult = $resumeModel->find((int)$firstStt);
        echo "<pre>";
        var_dump($testResult);
        echo "</pre>";
    } else {
        echo "<p style='color: red'>❌ Bảng resume RỖNG!</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 2px solid red;'>";
    echo "Lỗi: " . $e->getMessage();
    echo "</div>";
}
