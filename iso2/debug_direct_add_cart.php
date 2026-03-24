<?php
/**
 * Debug direct - Bypass tất cả để tìm lỗi chính xác
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h2>🔍 Direct Debug - Tìm lỗi 500</h2>";
echo "<hr>";

// Fake login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
}

echo "✅ Session: user_id = {$_SESSION['user_id']}<br><br>";

try {
    // Test 1: Database connection
    echo "<h3>Test 1: Database Connection</h3>";
    require_once __DIR__ . '/config/database.php';
    $db = getDBConnection();
    echo "✅ Database connected<br><br>";
    
    // Test 2: Check tables
    echo "<h3>Test 2: Check Tables</h3>";
    $tables = ['cart_vattu_thanh_ly', 'vattu_thanh_ly_iso', 'activity_logs'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();
        if ($exists) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' NOT found<br>";
        }
    }
    echo "<br>";
    
    // Test 3: ActivityLogger class
    echo "<h3>Test 3: ActivityLogger Class</h3>";
    try {
        require_once __DIR__ . '/includes/ActivityLogger.php';
        $logger = new ActivityLogger($db);
        echo "✅ ActivityLogger loaded<br><br>";
    } catch (Exception $e) {
        echo "❌ ActivityLogger error: " . $e->getMessage() . "<br><br>";
    }
    
    // Test 4: Lấy 1 vật tư
    echo "<h3>Test 4: Get Test Item</h3>";
    $stmt = $db->query("SELECT stt, mavattu, ten_tiengviet FROM vattu_thanh_ly_iso LIMIT 1");
    $vattu = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($vattu) {
        echo "✅ Vật tư: STT={$vattu['stt']}, Mã={$vattu['mavattu']}<br><br>";
        
        // Test 5: Simulate add to cart (manual SQL)
        echo "<h3>Test 5: Manual INSERT vào giỏ</h3>";
        
        $user_id = $_SESSION['user_id'];
        $vattu_stt = $vattu['stt'];
        $so_luong = 2;
        
        // Check existing
        $stmt = $db->prepare("SELECT id, so_luong FROM cart_vattu_thanh_ly WHERE user_id = ? AND vattu_stt = ?");
        $stmt->execute([$user_id, $vattu_stt]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            echo "⚠️ Đã tồn tại (ID={$existing['id']}, SL={$existing['so_luong']}), sẽ UPDATE<br>";
            $new_qty = $existing['so_luong'] + $so_luong;
            $stmt = $db->prepare("UPDATE cart_vattu_thanh_ly SET so_luong = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_qty, $existing['id']]);
            echo "✅ UPDATE thành công: {$existing['so_luong']} → {$new_qty}<br><br>";
        } else {
            echo "→ Chưa tồn tại, sẽ INSERT<br>";
            $stmt = $db->prepare("INSERT INTO cart_vattu_thanh_ly (user_id, vattu_stt, so_luong, ghi_chu) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $vattu_stt, $so_luong, 'Test manual']);
            $inserted_id = $db->lastInsertId();
            echo "✅ INSERT thành công: ID = {$inserted_id}<br><br>";
        }
        
        // Test 6: Try GioHangController::add() với error handling chi tiết
        echo "<h3>Test 6: GioHangController::add() với Error Details</h3>";
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['vattu_stt'] = $vattu['stt'];
        $_POST['so_luong'] = 1;
        $_POST['ghi_chu'] = 'Test controller';
        
        ob_start();
        
        try {
            require_once __DIR__ . '/controllers/GioHangController.php';
            
            $controller = new GioHangController();
            $controller->add();
            
            $output = ob_get_clean();
            
            echo "<h4>✅ Controller Response:</h4>";
            echo "<pre style='background: #d4edda; padding: 10px;'>";
            echo htmlspecialchars($output);
            echo "</pre>";
            
            $json = json_decode($output, true);
            if ($json) {
                if ($json['success']) {
                    echo "<p style='color: green; font-weight: bold;'>✅ SUCCESS! Cart count: {$json['cart_count']}</p>";
                } else {
                    echo "<p style='color: red; font-weight: bold;'>❌ FAILED: {$json['message']}</p>";
                }
            }
            
        } catch (TypeError $e) {
            ob_end_clean();
            echo "<div style='background: #f8d7da; padding: 10px;'>";
            echo "<strong>❌ TypeError:</strong><br>";
            echo "Message: " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        } catch (Exception $e) {
            ob_end_clean();
            echo "<div style='background: #f8d7da; padding: 10px;'>";
            echo "<strong>❌ Exception:</strong><br>";
            echo "Message: " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        } catch (Error $e) {
            ob_end_clean();
            echo "<div style='background: #f8d7da; padding: 10px;'>";
            echo "<strong>❌ Fatal Error:</strong><br>";
            echo "Message: " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        }
        
    } else {
        echo "❌ Không có vật tư nào<br>";
    }
    
    // Test 7: Check current cart
    echo "<h3>Test 7: Current Cart Data</h3>";
    $stmt = $db->prepare("
        SELECT 
            c.*,
            v.mavattu,
            v.ten_tiengviet
        FROM cart_vattu_thanh_ly c
        LEFT JOIN vattu_thanh_ly_iso v ON c.vattu_stt = v.stt
        WHERE c.user_id = ?
        ORDER BY c.ngay_them DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($items) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Mã VT</th><th>Tên</th><th>SL</th><th>Ghi chú</th><th>Ngày thêm</th>";
        echo "</tr>";
        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>{$item['id']}</td>";
            echo "<td>{$item['mavattu']}</td>";
            echo "<td>{$item['ten_tiengviet']}</td>";
            echo "<td><strong>{$item['so_luong']}</strong></td>";
            echo "<td>{$item['ghi_chu']}</td>";
            echo "<td>{$item['ngay_them']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Giỏ hàng trống";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0;'>";
    echo "<strong>❌ GLOBAL ERROR:</strong><br>";
    echo "Message: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='test_add_giohang.php'>← Test chính</a> | <a href='check_table_name.php'>Check tables</a></p>";
?>
