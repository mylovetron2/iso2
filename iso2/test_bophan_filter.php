<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/models/ThietBiHCKD.php';

// Capture error log
$logFile = tempnam(sys_get_temp_dir(), 'test_log_');
ini_set('error_log', $logFile);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Bộ Phận Filter</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        table { border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .log { background: #f4f4f4; padding: 10px; margin: 10px 0; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>Test Bộ Phận Filter</h1>
    
    <?php
    try {
        $model = new ThietBiHCKD();
        
        // Get all bộ phận
        $boPhanList = $model->getAllBoPhanSH();
        echo "<h2>Danh sách Bộ phận có sẵn:</h2>";
        echo "<ul>";
        foreach ($boPhanList as $bp) {
            echo "<li><a href='?bophan=" . urlencode($bp) . "'>" . htmlspecialchars($bp) . "</a></li>";
        }
        echo "</ul>";
        
        $testBoPhan = $_GET['bophan'] ?? (count($boPhanList) > 0 ? $boPhanList[0] : '');
        
        if ($testBoPhan) {
            echo "<hr><h2>Test với Bộ phận: " . htmlspecialchars($testBoPhan) . "</h2>";
            
            // Test 1: Without filter
            echo "<h3>Test 1: Chỉ lọc bộ phận (không có filter trạng thái)</h3>";
            $where = "WHERE bophansh = :bophansh";
            $params = ['bophansh' => $testBoPhan];
            $items = $model->getAllWithLatestHC($where, $params, 10, 0, '');
            
            echo "<p><strong>Kết quả:</strong> " . count($items) . " thiết bị</p>";
            if (count($items) > 0) {
                echo "<table><tr><th>Mã VT</th><th>Tên TB</th><th>Bộ phận</th><th>Days to expire</th></tr>";
                foreach ($items as $item) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($item['mavattu']) . "</td>";
                    echo "<td>" . htmlspecialchars($item['tenthietbi'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($item['bophansh'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($item['days_to_expire'] ?? 'NULL') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Test 2: With filter saphethan
            echo "<h3>Test 2: Lọc bộ phận + sắp hết hạn</h3>";
            $items2 = $model->getAllWithLatestHC($where, $params, 10, 0, 'saphethan');
            
            echo "<p><strong>Kết quả:</strong> " . count($items2) . " thiết bị sắp hết hạn trong bộ phận này</p>";
            if (count($items2) > 0) {
                echo "<table><tr><th>Mã VT</th><th>Tên TB</th><th>Bộ phận</th><th>Days to expire</th></tr>";
                foreach ($items2 as $item) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($item['mavattu']) . "</td>";
                    echo "<td>" . htmlspecialchars($item['tenthietbi'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($item['bophansh'] ?? '') . "</td>";
                    echo "<td><strong>" . htmlspecialchars($item['days_to_expire'] ?? 'NULL') . "</strong></td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Test 3: Count
            echo "<h3>Test 3: Count queries</h3>";
            $totalBoPhan = $model->count($where, $params);
            echo "<p>Tổng thiết bị trong bộ phận: $totalBoPhan</p>";
            
            $totalFiltered = $model->countWithFilter($where, $params, 'saphethan');
            echo "<p>Thiết bị sắp hết hạn trong bộ phận: $totalFiltered</p>";
        }
        
    } catch (Exception $e) {
        echo "<div style='color:red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    
    // Display log
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        if ($logContent) {
            echo "<hr><h2>Error Log Output:</h2>";
            echo "<div class='log'>" . htmlspecialchars($logContent) . "</div>";
        }
        unlink($logFile);
    }
    ?>
    
    <hr>
    <p><a href="thietbihckd.php">← Back to main page</a></p>
</body>
</html>
