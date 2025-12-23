<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/models/ThietBiHCKD.php';

echo "<h2>Debug Query Execution</h2>";

try {
    $model = new ThietBiHCKD();
    
    // Test 1: Without filter
    echo "<h3>Test 1: No Filter</h3>";
    $items = $model->getAllWithLatestHC('', [], 5, 0, '');
    echo "<p>Results: " . count($items) . " records</p>";
    if (count($items) > 0) {
        echo "<table border='1'><tr><th>Mã vật tư</th><th>Tên TB</th><th>Ngày HC</th><th>Days to expire</th></tr>";
        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['mavattu']) . "</td>";
            echo "<td>" . htmlspecialchars($item['tenthietbi'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($item['ngayhc_latest'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($item['days_to_expire'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 2: With saphethan filter
    echo "<h3>Test 2: Filter = saphethan</h3>";
    $items = $model->getAllWithLatestHC('', [], 5, 0, 'saphethan');
    echo "<p>Results: " . count($items) . " records</p>";
    if (count($items) > 0) {
        echo "<table border='1'><tr><th>Mã vật tư</th><th>Tên TB</th><th>Ngày HC</th><th>Days to expire</th></tr>";
        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['mavattu']) . "</td>";
            echo "<td>" . htmlspecialchars($item['tenthietbi'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($item['ngayhc_latest'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($item['days_to_expire'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 3: Count with filter
    echo "<h3>Test 3: Count with Filter</h3>";
    $total = $model->countWithFilter('', [], 'saphethan');
    echo "<p>Total matching filter: $total</p>";
    
    // Test 4: Regular count
    $totalAll = $model->count('', []);
    echo "<p>Total all equipment: $totalAll</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr><h3>Check Error Log Output</h3>";
echo "<p>Check terminal for error_log output with SQL queries</p>";
