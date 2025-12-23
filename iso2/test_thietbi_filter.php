<?php
/**
 * Test script để debug lỗi filter trong thietbihckd
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

echo "<h1>🔍 Test Filter Thiết Bị HC/KĐ</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
    .success { color: green; }
    .error { color: red; }
</style>";

try {
    $db = getDBConnection();
    echo "<p class='success'>✓ Kết nối database thành công</p>";
    
    // Test 1: Query đơn giản
    echo "<h2>Test 1: Query cơ bản</h2>";
    $sql = "SELECT COUNT(*) FROM thietbihckd_iso";
    $stmt = $db->query($sql);
    $count = $stmt->fetchColumn();
    echo "<p>Tổng số thiết bị: <strong>$count</strong></p>";
    
    // Test 2: Query với WHERE đơn giản
    echo "<h2>Test 2: WHERE đơn giản</h2>";
    $sql = "SELECT COUNT(*) FROM thietbihckd_iso WHERE loaitb = :loaitb";
    $stmt = $db->prepare($sql);
    $params = ['loaitb' => '1'];
    $stmt->execute($params);
    $count = $stmt->fetchColumn();
    echo "<pre>SQL: $sql\nParams: " . print_r($params, true) . "</pre>";
    echo "<p>Kết quả: <strong>$count</strong> thiết bị</p>";
    
    // Test 3: Query với LIKE
    echo "<h2>Test 3: WHERE với LIKE</h2>";
    $search = 'PM';
    $sql = "SELECT COUNT(*) FROM thietbihckd_iso WHERE mavattu LIKE :search";
    $stmt = $db->prepare($sql);
    $params = ['search' => "%$search%"];
    $stmt->execute($params);
    $count = $stmt->fetchColumn();
    echo "<pre>SQL: $sql\nParams: " . print_r($params, true) . "</pre>";
    echo "<p>Kết quả: <strong>$count</strong> thiết bị</p>";
    
    // Test 4: Query phức tạp (giống controller)
    echo "<h2>Test 4: Query phức tạp (Multi OR + AND)</h2>";
    $search = 'PM';
    $loaitb = '1';
    
    $conditions = [];
    $params = [];
    
    if ($search) {
        $conditions[] = "(mavattu LIKE :search OR tenviettat LIKE :search OR tenthietbi LIKE :search OR somay LIKE :search OR hangsx LIKE :search)";
        $params['search'] = "%$search%";
    }
    
    if ($loaitb !== '') {
        $conditions[] = "loaitb = :loaitb";
        $params['loaitb'] = $loaitb;
    }
    
    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $sql = "SELECT COUNT(*) FROM thietbihckd_iso $where";
    
    echo "<pre>SQL: $sql\n\nParams: " . print_r($params, true) . "</pre>";
    
    $stmt = $db->prepare($sql);
    
    try {
        $stmt->execute($params);
        $count = $stmt->fetchColumn();
        echo "<p class='success'>✓ Query thành công! Kết quả: <strong>$count</strong> thiết bị</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Lỗi execute: " . $e->getMessage() . "</p>";
        echo "<pre>Error Code: " . $e->getCode() . "</pre>";
        
        // Debug parameter count
        echo "<h3>Debug Info:</h3>";
        echo "<p>Số placeholders trong SQL: " . substr_count($sql, ':') . "</p>";
        echo "<p>Số parameters trong array: " . count($params) . "</p>";
        
        // Count unique placeholders
        preg_match_all('/:(\w+)/', $sql, $matches);
        echo "<p>Placeholders tìm thấy: " . implode(', ', array_unique($matches[1])) . "</p>";
        echo "<p>Số placeholders unique: " . count(array_unique($matches[1])) . "</p>";
    }
    
    // Test 5: Filter sắp hết hạn
    echo "<h2>Test 5: Filter Sắp Hết Hạn</h2>";
    $conditions = [];
    $params = [];
    $conditions[] = "ngayktnghiemthu IS NOT NULL AND DATEDIFF(DATE_ADD(ngayktnghiemthu, INTERVAL CAST(thoihankd AS SIGNED) MONTH), CURDATE()) <= 30 AND DATEDIFF(DATE_ADD(ngayktnghiemthu, INTERVAL CAST(thoihankd AS SIGNED) MONTH), CURDATE()) >= 0";
    
    $where = 'WHERE ' . implode(' AND ', $conditions);
    $sql = "SELECT COUNT(*) FROM thietbihckd_iso $where";
    
    echo "<pre>SQL: $sql</pre>";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $count = $stmt->fetchColumn();
        echo "<p class='success'>✓ Query thành công! Kết quả: <strong>$count</strong> thiết bị sắp hết hạn</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Lỗi: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Lỗi: " . $e->getMessage() . "</p>";
}
?>
