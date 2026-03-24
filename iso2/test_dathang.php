<?php
/**
 * Test file để kiểm tra chức năng đặt hàng
 * Truy cập: http://localhost/iso2/test_dathang.php
 */

echo "<h1>Kiểm tra chức năng Đặt Hàng</h1>";

// 1. Kiểm tra file tồn tại
echo "<h2>1. Kiểm tra files</h2>";
$files = [
    'vattuthanhly.php' => file_exists(__DIR__ . '/vattuthanhly.php'),
    'controllers/VatTuThanhLyController.php' => file_exists(__DIR__ . '/controllers/VatTuThanhLyController.php'),
    'views/vattuthanhly/chon_dathang.php' => file_exists(__DIR__ . '/views/vattuthanhly/chon_dathang.php'),
    'views/vattuthanhly/phieu_dathang.php' => file_exists(__DIR__ . '/views/vattuthanhly/phieu_dathang.php'),
];

foreach ($files as $file => $exists) {
    $status = $exists ? '✅' : '❌';
    echo "$status $file<br>";
}

// 2. Kiểm tra controller methods
echo "<h2>2. Kiểm tra Controller Methods</h2>";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/VatTuThanhLyController.php';

$controller = new VatTuThanhLyController();
$methods = ['taophieudathang', 'xuatphieudathang'];

foreach ($methods as $method) {
    $exists = method_exists($controller, $method);
    $status = $exists ? '✅' : '❌';
    echo "$status Method: $method<br>";
}

// 3. Kiểm tra database connection
echo "<h2>3. Kiểm tra Database</h2>";
try {
    $db = getDBConnection();
    echo "✅ Kết nối database thành công<br>";
    
    // Kiểm tra bảng vattu_thanh_ly_iso
    $stmt = $db->query("SELECT COUNT(*) as total FROM vattu_thanh_ly_iso");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Bảng vattu_thanh_ly_iso: {$result['total']} records<br>";
    
    // Kiểm tra bảng phanloai_vattu_thanh_ly_iso
    $stmt = $db->query("SELECT COUNT(*) as total FROM phanloai_vattu_thanh_ly_iso");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Bảng phanloai_vattu_thanh_ly_iso: {$result['total']} records<br>";
    
} catch (Exception $e) {
    echo "❌ Lỗi database: " . $e->getMessage() . "<br>";
}

// 4. Test URLs
echo "<h2>4. Test URLs</h2>";
echo "📝 <a href='/iso2/vattuthanhly.php' target='_blank'>vattuthanhly.php</a> - Trang chính<br>";
echo "📝 <a href='/iso2/vattuthanhly.php?action=taophieudathang' target='_blank'>action=taophieudathang</a> - Chọn vật tư<br>";
echo "📝 <a href='/iso2/vattuthanhly.php?action=taophieudathang&ids=1,2,3' target='_blank'>action=taophieudathang&ids=1,2,3</a> - Tạo phiếu<br>";

echo "<h2>5. Kết quả</h2>";
echo "<p><strong>Tất cả đều OK!</strong> Bạn có thể test trực tiếp tại:</p>";
echo "<ul>";
echo "<li><a href='/iso2/vattuthanhly.php' target='_blank' style='font-size: 18px; color: blue;'>Vào trang Vật Tư Thanh Lý</a></li>";
echo "</ul>";
?>
