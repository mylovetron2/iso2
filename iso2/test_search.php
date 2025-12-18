<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/KeHoachISO.php';

try {
    $model = new KeHoachISO();
    
    // Test without search
    echo "<h2>Test 1: Không có tìm kiếm</h2>";
    $result1 = $model->countByMonthYear(12, 2025, '', 'all');
    echo "Count: $result1<br>";
    
    $data1 = $model->getWithHCStatus(12, 2025, 10, 0, '', 'all');
    echo "Records: " . count($data1) . "<br><br>";
    
    // Test with search
    echo "<h2>Test 2: Có tìm kiếm</h2>";
    $result2 = $model->countByMonthYear(12, 2025, 'CNC', 'all');
    echo "Count: $result2<br>";
    
    $data2 = $model->getWithHCStatus(12, 2025, 10, 0, 'CNC', 'all');
    echo "Records: " . count($data2) . "<br><br>";
    
    // Test with empty string from GET (simulating URL)
    echo "<h2>Test 3: Empty string từ GET</h2>";
    $_GET['search'] = '';
    $_GET['search_type'] = 'all';
    
    $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
    $searchType = isset($_GET['search_type']) ? $_GET['search_type'] : 'all';
    
    echo "Search Term: '" . $searchTerm . "' (length: " . strlen($searchTerm) . ")<br>";
    echo "Search Type: $searchType<br>";
    echo "Empty check: " . ($searchTerm === '' ? 'TRUE' : 'FALSE') . "<br>";
    
    $result3 = $model->countByMonthYear(12, 2025, $searchTerm, $searchType);
    echo "Count: $result3<br>";
    
    $data3 = $model->getWithHCStatus(12, 2025, 10, 0, $searchTerm, $searchType);
    echo "Records: " . count($data3) . "<br><br>";
    
    echo "<h2 style='color:green'>✓ Tất cả test thành công!</h2>";
    
} catch (Exception $e) {
    echo "<h2 style='color:red'>✗ Lỗi: " . htmlspecialchars($e->getMessage()) . "</h2>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
