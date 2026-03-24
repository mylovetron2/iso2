<?php
/**
 * Check tên bảng phân loại trong database
 */

require_once __DIR__ . '/config/database.php';

echo "<h2>🔍 Check Tên Bảng Phân Loại</h2>";
echo "<hr>";

try {
    $db = getDBConnection();
    
    echo "<h3>Kiểm tra các bảng phanloai_*:</h3>";
    
    $tables = [
        'phanloai_vattu_thanh_ly',
        'phanloai_vattu_thanh_ly_iso'
    ];
    
    $existingTable = null;
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();
        
        if ($exists) {
            echo "✅ Bảng <strong>$table</strong> TỒN TẠI<br>";
            $existingTable = $table;
            
            // Get structure
            echo "<details><summary>Xem cấu trúc bảng</summary>";
            echo "<pre>";
            $stmt = $db->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                echo "{$col['Field']} - {$col['Type']}\n";
            }
            echo "</pre>";
            echo "</details>";
            
            // Count records
            $stmt = $db->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "Số records: <strong>$count</strong><br><br>";
            
        } else {
            echo "❌ Bảng <strong>$table</strong> KHÔNG TỒN TẠI<br>";
        }
    }
    
    if ($existingTable) {
        echo "<hr>";
        echo "<h3>🔧 Action Required:</h3>";
        echo "<p>Tên bảng đúng là: <strong style='color: green;'>$existingTable</strong></p>";
        echo "<p>Cần sửa trong file: <strong>controllers/GioHangController.php</strong></p>";
        
        if ($existingTable === 'phanloai_vattu_thanh_ly') {
            echo "<p>Thay đổi dòng 340:</p>";
            echo "<pre style='background: #f8d7da; padding: 10px;'>";
            echo "LEFT JOIN phanloai_vattu_thanh_ly_iso p ON v.phanloai_id = p.id";
            echo "</pre>";
            echo "<p>Thành:</p>";
            echo "<pre style='background: #d4edda; padding: 10px;'>";
            echo "LEFT JOIN phanloai_vattu_thanh_ly p ON v.phanloai_id = p.id";
            echo "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage();
}
?>
