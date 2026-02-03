<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/VatTuThanhLy.php';

try {
    echo "<h2>Test Search VatTuThanhLy</h2>";
    
    $model = new VatTuThanhLy();
    
    // Test 1: Search without keyword
    echo "<h3>Test 1: Tất cả vật tư (không có từ khóa)</h3>";
    $items = $model->getAllWithStats('', [], 5, 0);
    echo "Số lượng: " . count($items) . "<br>";
    if (count($items) > 0) {
        echo "<pre>" . print_r($items[0], true) . "</pre>";
    }
    
    // Test 2: Search with keyword
    $search = 'điện';
    echo "<h3>Test 2: Tìm kiếm với từ khóa '{$search}' (không phân biệt hoa thường)</h3>";
    $searchLower = mb_strtolower($search, 'UTF-8');
    $searchCap = mb_strtoupper(mb_substr($search, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($searchLower, 1);
    
    $where = "(
        v.mavattu LIKE :search1a OR v.mavattu LIKE :search1b OR
        v.ten_tienganh LIKE :search2a OR v.ten_tienganh LIKE :search2b OR
        v.ten_tiengnga LIKE :search3a OR v.ten_tiengnga LIKE :search3b OR
        v.ten_tiengviet LIKE :search4a OR v.ten_tiengviet LIKE :search4b OR
        v.nguoiquanly LIKE :search5a OR v.nguoiquanly LIKE :search5b
    )";
    
    $params = [
        ':search1a' => "%$searchLower%",
        ':search1b' => "%$searchCap%",
        ':search2a' => "%$searchLower%",
        ':search2b' => "%$searchCap%",
        ':search3a' => "%$searchLower%",
        ':search3b' => "%$searchCap%",
        ':search4a' => "%$searchLower%",
        ':search4b' => "%$searchCap%",
        ':search5a' => "%$searchLower%",
        ':search5b' => "%$searchCap%"
    ];
    $where = "WHERE " . $where;
    
    $items = $model->getAllWithStats($where, $params, 10, 0);
    echo "Số lượng kết quả: " . count($items) . " (phải có cả 'Điện trở' và 'Tụ điện')<br>";
    if (count($items) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>STT</th><th>Mã VT</th><th>Tên TA</th><th>Tên Việt</th></tr>";
        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['stt']) . "</td>";
            echo "<td>" . htmlspecialchars($item['mavattu']) . "</td>";
            echo "<td>" . htmlspecialchars($item['ten_tienganh'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($item['ten_tiengviet'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Không tìm thấy kết quả nào!<br>";
    }
    
    // Test 2b: Test với từ khóa HOA
    $search2 = 'ĐIỆN';
    echo "<h3>Test 2b: Tìm kiếm với từ khóa '{$search2}' (CHỮ HOA)</h3>";
    $searchLower2 = mb_strtolower($search2, 'UTF-8');
    $searchCap2 = mb_strtoupper(mb_substr($search2, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($searchLower2, 1);
    
    $params2 = [
        ':search1a' => "%$searchLower2%",
        ':search1b' => "%$searchCap2%",
        ':search2a' => "%$searchLower2%",
        ':search2b' => "%$searchCap2%",
        ':search3a' => "%$searchLower2%",
        ':search3b' => "%$searchCap2%",
        ':search4a' => "%$searchLower2%",
        ':search4b' => "%$searchCap2%",
        ':search5a' => "%$searchLower2%",
        ':search5b' => "%$searchCap2%"
    ];
    
    $items2 = $model->getAllWithStats($where, $params2, 10, 0);
    echo "Số lượng kết quả: " . count($items2) . " (phải giống Test 2)<br>";
    
    // Test 3: Count function
    echo "<h3>Test 3: Kiểm tra count function</h3>";
    $total = $model->count($where, $params);
    echo "Tổng số kết quả: " . $total . "<br>";
    
    echo "<br><div style='color:green; font-weight:bold;'>✓ Tất cả test đều hoạt động!</div>";
    
} catch (Exception $e) {
    echo "<div style='color:red; border:2px solid red; padding:10px; margin:10px;'>";
    echo "<h3>ERROR:</h3>";
    echo "<strong>Message:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<strong>Trace:</strong><pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
