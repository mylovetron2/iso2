<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Mock session nếu chưa login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 5;
    $_SESSION['user_name'] = 'test_user';
}

echo "<h2>🔍 Debug Store Phiếu Đặt Hàng</h2><hr>";
echo "✅ Session: user_id = " . $_SESSION['user_id'] . "<br><br>";

// Include dependencies
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/ActivityLogger.php';

echo "<h3>Test 1: Database Connection</h3>";
try {
    $db = getDBConnection(true);
    echo "✅ Database connected<br><br>";
} catch (Exception $e) {
    die("❌ Connection failed: " . $e->getMessage());
}

echo "<h3>Test 2: Check Cart Items</h3>";
$stmt = $db->prepare("SELECT 
    c.*,
    v.mavattu,
    v.ten_tiengviet,
    v.dongia,
    v.dvt_tiengviet
FROM cart_vattu_thanh_ly c
LEFT JOIN vattu_thanh_ly_iso v ON c.vattu_stt = v.stt
WHERE c.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cartItems)) {
    die("❌ Giỏ hàng trống! Thêm vật tư vào giỏ trước khi test.<br><a href='test_add_giohang.php'>Thêm vào giỏ</a>");
}

echo "✅ Cart có " . count($cartItems) . " items:<br>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>STT</th><th>Mã VT</th><th>Tên</th><th>SL</th><th>Đơn giá</th></tr>";
foreach ($cartItems as $item) {
    echo "<tr>";
    echo "<td>{$item['vattu_stt']}</td>";
    echo "<td>{$item['mavattu']}</td>";
    echo "<td>{$item['ten_tiengviet']}</td>";
    echo "<td>{$item['so_luong']}</td>";
    echo "<td>" . number_format($item['dongia']) . "</td>";
    echo "</tr>";
}
echo "</table><br>";

echo "<h3>Test 3: Mock POST Data</h3>";
$_POST = [
    'nha_cung_cap' => 'NCC Test Debug',
    'so_hop_dong_ncc' => 'HD-2026-TEST-001',
    'ngay_giao_du_kien' => '2026-04-15',
    'ghi_chu' => 'Test tạo phiếu từ debug script'
];
echo "✅ POST data:<br>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

echo "<h3>Test 4: Include PhieuDatHangController</h3>";
try {
    require_once 'controllers/PhieuDatHangController.php';
    echo "✅ Controller loaded<br><br>";
} catch (Exception $e) {
    die("❌ Failed to load controller: " . $e->getMessage());
}

echo "<h3>Test 5: Create Controller Instance</h3>";
try {
    $controller = new PhieuDatHangController();
    echo "✅ Controller instance created<br><br>";
} catch (Exception $e) {
    die("❌ Failed to create controller: " . $e->getMessage() . "<br>Stack: " . $e->getTraceAsString());
}

echo "<h3>Test 6: Call store() Method</h3>";
echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0;'>";
echo "📝 Attempting to create phiếu đặt hàng...<br>";
echo "</div>";

try {
    // Capture output
    ob_start();
    $controller->store();
    $output = ob_get_clean();
    
    echo "<h4>✅ store() Executed Successfully!</h4>";
    
    // Check if redirect happened
    if (preg_match('/Location: (.+)/', $output, $matches)) {
        echo "<p style='color: green;'>✅ Redirect to: {$matches[1]}</p>";
    }
    
    echo "<h4>Output:</h4>";
    echo "<pre style='background: #d4edda; padding: 10px;'>" . htmlspecialchars($output) . "</pre>";
    
    // Verify phieu was created
    echo "<h4>Verify Database:</h4>";
    $stmt = $db->prepare("SELECT * FROM phieu_dat_hang ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $lastPhieu = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lastPhieu) {
        echo "✅ Last phieu created:<br>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Mã phiếu</th><th>NCC</th><th>Số HĐ</th><th>Status</th><th>Created</th></tr>";
        echo "<tr>";
        echo "<td>{$lastPhieu['id']}</td>";
        echo "<td>{$lastPhieu['ma_phieu']}</td>";
        echo "<td>{$lastPhieu['nha_cung_cap']}</td>";
        echo "<td>{$lastPhieu['so_hop_dong_ncc']}</td>";
        echo "<td>{$lastPhieu['trang_thai']}</td>";
        echo "<td>{$lastPhieu['ngay_tao']}</td>";
        echo "</tr>";
        echo "</table><br>";
        
        // Check chi tiết
        $stmt = $db->prepare("SELECT * FROM phieu_dat_hang_chi_tiet WHERE phieu_id = ?");
        $stmt->execute([$lastPhieu['id']]);
        $chiTiet = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✅ Chi tiết phiếu: " . count($chiTiet) . " items<br>";
        
        // Check cart cleared
        $stmt = $db->prepare("SELECT COUNT(*) FROM cart_vattu_thanh_ly WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $cartCount = (int)$stmt->fetchColumn();
        
        if ($cartCount === 0) {
            echo "✅ Cart đã được clear (count = 0)<br>";
        } else {
            echo "⚠️ Cart chưa clear (count = $cartCount)<br>";
        }
    } else {
        echo "⚠️ Không tìm thấy phiếu mới tạo<br>";
    }
    
} catch (TypeError $te) {
    echo "<h4 style='color: red;'>❌ TypeError in store():</h4>";
    echo "<pre style='background: #f8d7da; padding: 10px;'>";
    echo "Message: " . $te->getMessage() . "\n";
    echo "File: " . $te->getFile() . ":" . $te->getLine() . "\n\n";
    echo "Stack trace:\n" . $te->getTraceAsString();
    echo "</pre>";
} catch (Exception $e) {
    echo "<h4 style='color: red;'>❌ Exception in store():</h4>";
    echo "<pre style='background: #f8d7da; padding: 10px;'>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString();
    echo "</pre>";
} catch (Error $err) {
    echo "<h4 style='color: red;'>❌ Fatal Error in store():</h4>";
    echo "<pre style='background: #f8d7da; padding: 10px;'>";
    echo "Message: " . $err->getMessage() . "\n";
    echo "File: " . $err->getFile() . ":" . $err->getLine() . "\n\n";
    echo "Stack trace:\n" . $err->getTraceAsString();
    echo "</pre>";
}

echo "<hr>";
echo "<p><a href='phieudathang.php?action=create&step=1'>← Back to Step 1</a> | ";
echo "<a href='debug_direct_add_cart.php'>Test Add Cart</a></p>";
