<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';

requireAuth();

echo "<h2>Kiểm tra quyền và chức năng</h2>";

echo "<h3>1. Thông tin user:</h3>";
echo "Username: " . ($_SESSION['username'] ?? 'N/A') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'N/A') . "<br>";
echo "User STT: " . ($_SESSION['user_stt'] ?? 'N/A') . "<br>";

echo "<h3>2. Quyền phiếu bàn giao:</h3>";
$perms = ['view', 'create', 'edit', 'delete'];
foreach ($perms as $p) {
    $check = hasPermission("phieubangiao.$p");
    echo ($check ? '✅' : '❌') . " phieubangiao.$p<br>";
}

echo "<h3>3. Test các action:</h3>";

// Test view
echo "<strong>View:</strong> <a href='phieubangiao.php' target='_blank'>Mở trang danh sách</a><br>";

// Test create
if (hasPermission('phieubangiao.create')) {
    echo "<strong>Create:</strong> <a href='phieubangiao.php?action=select' target='_blank'>Tạo phiếu mới</a><br>";
} else {
    echo "❌ Không có quyền tạo<br>";
}

// Lấy 1 phiếu nháp để test
require_once __DIR__ . '/models/PhieuBanGiao.php';
$model = new PhieuBanGiao();
$sql = "SELECT stt, sophieu, trangthai FROM phieubangiao_iso WHERE trangthai = 0 LIMIT 1";
$stmt = $model->query($sql);
$testPhieu = $stmt->fetch(PDO::FETCH_ASSOC);

if ($testPhieu) {
    echo "<h3>4. Phiếu test (Nháp): {$testPhieu['sophieu']}</h3>";
    
    // Test view detail
    echo "<strong>Xem chi tiết:</strong> <a href='phieubangiao.php?action=view&id={$testPhieu['stt']}' target='_blank'>Xem phiếu #{$testPhieu['stt']}</a><br>";
    
    // Test edit
    if (hasPermission('phieubangiao.edit')) {
        echo "<strong>Sửa:</strong> <a href='phieubangiao.php?action=edit&id={$testPhieu['stt']}' target='_blank'>Sửa phiếu #{$testPhieu['stt']}</a><br>";
    } else {
        echo "❌ Không có quyền sửa<br>";
    }
    
    // Test delete form
    if (hasPermission('phieubangiao.delete')) {
        echo "<strong>Xóa:</strong>";
        echo "<form method='POST' action='phieubangiao.php?action=delete' style='display:inline;'>
                <input type='hidden' name='id' value='{$testPhieu['stt']}'>
                <button type='submit' onclick='return confirm(\"Xóa phiếu test?\")' style='background:red;color:white;padding:5px 10px;border:none;cursor:pointer;'>
                    🗑️ Xóa phiếu #{$testPhieu['stt']}
                </button>
              </form><br>";
    } else {
        echo "❌ Không có quyền xóa<br>";
    }
} else {
    echo "<h3>4. Không có phiếu nháp để test</h3>";
    echo "<a href='create_test_phieu.php'>→ Tạo phiếu test</a><br>";
}

echo "<br><hr><br>";
echo "<a href='phieubangiao.php'>← Quay lại danh sách</a>";
?>
