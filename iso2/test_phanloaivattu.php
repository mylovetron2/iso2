<?php
// File test đơn giản để debug lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test Phanloaivattu.php</h1>";

try {
    echo "<p>1. Loading config/constants.php...</p>";
    require_once __DIR__ . '/config/constants.php';
    echo "<p style='color: green;'>✓ OK</p>";
    
    echo "<p>2. Loading includes/auth.php...</p>";
    require_once __DIR__ . '/includes/auth.php';
    echo "<p style='color: green;'>✓ OK</p>";
    
    echo "<p>3. Loading includes/permissions.php...</p>";
    require_once __DIR__ . '/includes/permissions.php';
    echo "<p style='color: green;'>✓ OK</p>";
    
    echo "<p>4. Loading models/BaseModel.php...</p>";
    require_once __DIR__ . '/models/BaseModel.php';
    echo "<p style='color: green;'>✓ OK</p>";
    
    echo "<p>5. Loading models/PhanLoaiVatTu.php...</p>";
    require_once __DIR__ . '/models/PhanLoaiVatTu.php';
    echo "<p style='color: green;'>✓ OK</p>";
    
    echo "<p>6. Loading controllers/PhanLoaiVatTuController.php...</p>";
    require_once __DIR__ . '/controllers/PhanLoaiVatTuController.php';
    echo "<p style='color: green;'>✓ OK</p>";
    
    echo "<p>7. Creating PhanLoaiVatTu model instance...</p>";
    $model = new PhanLoaiVatTu();
    echo "<p style='color: green;'>✓ OK</p>";
    
    echo "<p>8. Checking if table exists...</p>";
    require_once __DIR__ . '/config/database.php';
    $db = getDBConnection();
    $stmt = $db->query("SHOW TABLES LIKE 'phanloai_vattu_thanh_ly_iso'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Bảng phanloai_vattu_thanh_ly_iso tồn tại</p>";
        
        echo "<p>9. Counting records...</p>";
        $stmt = $db->query("SELECT COUNT(*) as count FROM phanloai_vattu_thanh_ly_iso");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✓ Có " . $result['count'] . " bản ghi</p>";
        
        echo "<p>10. Testing getAllOrdered()...</p>";
        $items = $model->getAllOrdered();
        echo "<p style='color: green;'>✓ Lấy được " . count($items) . " phân loại</p>";
        
        if (!empty($items)) {
            echo "<h3>Danh sách phân loại:</h3>";
            echo "<ul>";
            foreach ($items as $item) {
                echo "<li>" . htmlspecialchars($item['ten_phanloai']) . " (" . htmlspecialchars($item['ma_phanloai']) . ")</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>✗ Bảng phanloai_vattu_thanh_ly_iso CHƯA TỒN TẠI!</p>";
        echo "<p><strong>Giải pháp:</strong> Chạy file <a href='setup_phanloai_vattu.php'>setup_phanloai_vattu.php</a> để tạo bảng</p>";
    }
    
    echo "<p>11. Creating controller...</p>";
    $controller = new PhanLoaiVatTuController();
    echo "<p style='color: green;'>✓ OK</p>";
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✓ TẤT CẢ OK! Không có lỗi</h2>";
    echo "<p><a href='phanloaivattu.php'>Thử truy cập phanloaivattu.php</a></p>";
    
} catch (Exception $e) {
    echo "<hr>";
    echo "<h2 style='color: red;'>✗ LỖI XẢY RA:</h2>";
    echo "<pre style='background: #fee; padding: 10px; border: 2px solid red;'>";
    echo htmlspecialchars($e->getMessage());
    echo "\n\n";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
}
