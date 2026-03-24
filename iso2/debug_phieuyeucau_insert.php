<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config/database.php';

echo "<h2>Debug Phiếu Yêu Cầu Insert</h2>";

try {
    $db = getDBConnection();
    echo "<p>✓ Kết nối database thành công</p>";
    
    // Test connection character set
    $stmt = $db->query("SHOW VARIABLES LIKE 'character_set%'");
    echo "<h3>Character Sets:</h3>";
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Variable_name'] . ": " . $row['Value'] . "\n";
    }
    echo "</pre>";
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'hososcbd_iso'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "<p>✓ Bảng hososcbd_iso tồn tại</p>";
        
        // Check table structure
        $stmt = $db->query("DESCRIBE hososcbd_iso");
        echo "<h3>Cấu trúc bảng hososcbd_iso:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test insert with minimal data
        echo "<h3>Test INSERT:</h3>";
        
        $testData = [
            'phieu' => '9999',
            'ngayyc' => date('Y-m-d'),
            'madv' => 'TEST',
            'maql' => 'TEST-9999',
            'hoso' => '9999-1',
            'mavt' => 'TEST001',
            'somay' => 'TEST001',
            'bg' => 0
        ];
        
        echo "<p>Dữ liệu test:</p>";
        echo "<pre>" . print_r($testData, true) . "</pre>";
        
        try {
            $columns = implode(', ', array_keys($testData));
            $placeholders = ':' . implode(', :', array_keys($testData));
            $sql = "INSERT INTO hososcbd_iso ({$columns}) VALUES ({$placeholders})";
            
            echo "<p>SQL: <code>$sql</code></p>";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($testData);
            
            if ($result) {
                $insertId = $db->lastInsertId();
                echo "<p style='color: green;'>✓ INSERT thành công! Insert ID: $insertId</p>";
                
                // Clean up test data
                $db->exec("DELETE FROM hososcbd_iso WHERE phieu = '9999'");
                echo "<p>Test data đã được xóa</p>";
            } else {
                echo "<p style='color: red;'>✗ INSERT thất bại</p>";
                echo "<pre>" . print_r($stmt->errorInfo(), true) . "</pre>";
            }
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ Lỗi: " . $e->getMessage() . "</p>";
            echo "<p>SQL State: " . $e->getCode() . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Bảng hososcbd_iso KHÔNG tồn tại!</p>";
    }
    
    // Check for any existing triggers or constraints
    echo "<h3>Triggers:</h3>";
    $stmt = $db->query("SHOW TRIGGERS LIKE 'hososcbd_iso'");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($triggers)) {
        echo "<p>Không có trigger nào</p>";
    } else {
        echo "<pre>" . print_r($triggers, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
