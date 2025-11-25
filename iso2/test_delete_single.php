<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/PhieuBanGiaoController.php';

requireAuth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    die("❌ Cần ID phiếu. <a href='test_phieubangiao_full.php'>← Quay lại</a>");
}

echo "<h2>Test xóa phiếu bàn giao #$id</h2>";

// Giả lập POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['id'] = $id;

// Gọi controller delete
$controller = new PhieuBanGiaoController();

echo "<p>Đang thực hiện xóa...</p>";
try {
    $controller->delete();
    // Nếu không redirect (lỗi), hiển thị message
    echo "<h3>Kết quả:</h3>";
    if (isset($_SESSION['success'])) {
        echo "<p style='color:green;'>✅ " . $_SESSION['success'] . "</p>";
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo "<p style='color:red;'>❌ " . $_SESSION['error'] . "</p>";
        unset($_SESSION['error']);
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Exception: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<br><a href='test_phieubangiao_full.php'>← Quay lại kiểm tra</a>";
echo " | <a href='phieubangiao.php'>📋 Danh sách phiếu</a>";
?>
