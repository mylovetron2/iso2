<?php
/**
 * Debug AJAX Add to Cart - Simulate giống như frontend
 */

// Bắt đầu session
session_start();

// Fake login nếu chưa đăng nhập (để test)
if (!isset($_SESSION['user_id'])) {
    // Lấy user thật từ database
    require_once __DIR__ . '/config/database.php';
    $db = getDBConnection();
    $stmt = $db->query("SELECT id, username FROM users WHERE id = 1 LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        echo "ℹ️ Auto-login as: {$user['username']} (ID: {$user['id']})<br><br>";
    } else {
        die("❌ Không tìm thấy user trong database");
    }
}

echo "<h2>🧪 Test AJAX Add to Cart (Simulate Frontend)</h2>";
echo "<hr>";

// Lấy 1 vật tư để test
require_once __DIR__ . '/config/database.php';
$db = getDBConnection();
$stmt = $db->query("SELECT stt, mavattu, ten_tiengviet FROM vattu_thanh_ly_iso LIMIT 1");
$vattu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vattu) {
    die("❌ Không có vật tư nào trong database");
}

echo "<h3>Vật tư test:</h3>";
echo "STT: {$vattu['stt']}<br>";
echo "Mã: {$vattu['mavattu']}<br>";
echo "Tên: {$vattu['ten_tiengviet']}<br><br>";

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['vattu_stt'] = $vattu['stt'];
$_POST['so_luong'] = 3;
$_POST['ghi_chu'] = 'Test AJAX';

echo "<h3>Simulate POST data:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h3>Gọi GioHangController::add():</h3>";

// Capture output
ob_start();

try {
    require_once __DIR__ . '/includes/auth_check.php';
    require_once __DIR__ . '/includes/permission_check.php';
    require_once __DIR__ . '/controllers/GioHangController.php';
    
    // Override permission check để test
    if (!function_exists('checkPermission')) {
        function checkPermission($perm) {
            // Skip permission check for testing
            return true;
        }
    }
    
    $controller = new GioHangController();
    $controller->add();
    
    $output = ob_get_clean();
    
    echo "<h4>✅ Response từ controller:</h4>";
    echo "<pre style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    // Parse JSON
    $json = json_decode($output, true);
    if ($json) {
        echo "<h4>Parsed JSON:</h4>";
        echo "<pre>";
        print_r($json);
        echo "</pre>";
        
        if ($json['success']) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "✅ <strong>THÀNH CÔNG!</strong><br>";
            echo "Message: {$json['message']}<br>";
            echo "Cart count: {$json['cart_count']}<br>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "❌ <strong>THẤT BẠI!</strong><br>";
            echo "Message: {$json['message']}<br>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>EXCEPTION:</strong><br>";
    echo "Message: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>📋 Check dữ liệu trong database:</h3>";

try {
    $stmt = $db->prepare("
        SELECT 
            c.id,
            c.user_id,
            c.vattu_stt,
            c.so_luong,
            c.ghi_chu,
            c.ngay_them,
            v.mavattu,
            v.ten_tiengviet
        FROM cart_vattu_thanh_ly c
        LEFT JOIN vattu_thanh_ly_iso v ON c.vattu_stt = v.stt
        WHERE c.user_id = ?
        ORDER BY c.ngay_them DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Tổng items: <strong>" . count($items) . "</strong><br><br>";
    
    if ($items) {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>User ID</th><th>Mã VT</th><th>Tên vật tư</th><th>SL</th><th>Ghi chú</th><th>Ngày thêm</th>";
        echo "</tr>";
        
        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>{$item['id']}</td>";
            echo "<td>{$item['user_id']}</td>";
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
    echo "❌ Lỗi query: " . $e->getMessage();
}

echo "<hr>";
echo "<p><a href='test_add_giohang.php'>← Quay lại test chính</a></p>";
?>
