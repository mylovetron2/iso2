<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/PhieuBanGiao.php';
require_once __DIR__ . '/models/PhieuBanGiaoThietBi.php';
require_once __DIR__ . '/models/HoSoSCBD.php';

requireAuth();

echo "<h2>Kiểm tra tổng thể chức năng Phiếu Bàn Giao</h2>";

$pbgModel = new PhieuBanGiao();
$pbtbModel = new PhieuBanGiaoThietBi();
$hosoModel = new HoSoSCBD();

// 1. Kiểm tra session
echo "<h3>1. Session & Quyền</h3>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'N/A') . "<br>";
echo "Username: " . ($_SESSION['username'] ?? 'N/A') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'N/A') . "<br><br>";

$perms = ['view', 'create', 'edit', 'delete'];
foreach ($perms as $p) {
    $check = hasPermission("phieubangiao.$p");
    echo ($check ? '✅' : '❌') . " phieubangiao.$p<br>";
}

// 2. Kiểm tra bảng trong database
echo "<h3>2. Kiểm tra cấu trúc database</h3>";
try {
    $tables = ['phieubangiao_iso', 'phieubangiao_thietbi_iso', 'hososcbd_iso'];
    foreach ($tables as $table) {
        $sql = "SHOW TABLES LIKE '$table'";
        $stmt = $pbgModel->query($sql);
        $exists = $stmt->rowCount() > 0;
        echo ($exists ? '✅' : '❌') . " Bảng $table " . ($exists ? 'tồn tại' : 'không tồn tại') . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Lỗi kiểm tra bảng: " . $e->getMessage() . "<br>";
}

// 3. Lấy danh sách phiếu nháp
echo "<h3>3. Danh sách phiếu nháp (có thể sửa/xóa)</h3>";
try {
    $sql = "SELECT stt, sophieu, phieuyc, ngaybg, nguoigiao, nguoinhan, trangthai 
            FROM phieubangiao_iso 
            WHERE trangthai = 0 
            ORDER BY stt DESC 
            LIMIT 5";
    $stmt = $pbgModel->query($sql);
    $nhaps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($nhaps)) {
        echo "<p>❌ Không có phiếu nháp nào trong database</p>";
        echo "<p><a href='create_test_phieu.php' style='background:green;color:white;padding:10px;text-decoration:none;'>Tạo phiếu test</a></p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>ID</th><th>Số phiếu</th><th>Phiếu YC</th><th>Ngày BG</th><th>Người giao</th><th>Trạng thái</th><th>Test</th></tr>";
        foreach ($nhaps as $p) {
            echo "<tr>";
            echo "<td>{$p['stt']}</td>";
            echo "<td>{$p['sophieu']}</td>";
            echo "<td>{$p['phieuyc']}</td>";
            echo "<td>{$p['ngaybg']}</td>";
            echo "<td>{$p['nguoigiao']}</td>";
            echo "<td><span style='color:orange;'>Nháp</span></td>";
            echo "<td>
                    <a href='phieubangiao.php?action=view&id={$p['stt']}' target='_blank'>👁️ Xem</a> | 
                    <a href='phieubangiao.php?action=edit&id={$p['stt']}' target='_blank'>✏️ Sửa</a> |
                    <a href='test_delete_single.php?id={$p['stt']}' style='color:red;'>🗑️ Test xóa</a>
                  </td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Lỗi truy vấn: " . $e->getMessage() . "<br>";
}

// 4. Test các method trong controller
echo "<h3>4. Test các method quan trọng</h3>";

// Test findById
if (!empty($nhaps)) {
    $testId = $nhaps[0]['stt'];
    echo "<strong>Test findById($testId):</strong> ";
    try {
        $phieu = $pbgModel->findById($testId);
        echo $phieu ? "✅ Tìm thấy<br>" : "❌ Không tìm thấy<br>";
    } catch (Exception $e) {
        echo "❌ Lỗi: " . $e->getMessage() . "<br>";
    }
    
    // Test getBySoPhieu
    $sophieu = $nhaps[0]['sophieu'];
    echo "<strong>Test getBySoPhieu('$sophieu'):</strong> ";
    try {
        $thietbi = $pbtbModel->getBySoPhieu($sophieu);
        echo "✅ Tìm thấy " . count($thietbi) . " thiết bị<br>";
    } catch (Exception $e) {
        echo "❌ Lỗi: " . $e->getMessage() . "<br>";
    }
    
    // Test delete method (dry-run - không thực sự xóa)
    echo "<strong>Test khả năng xóa:</strong> ";
    if ($phieu['trangthai'] == 0) {
        echo "✅ Phiếu nháp, có thể xóa<br>";
    } else {
        echo "❌ Phiếu đã duyệt, không thể xóa<br>";
    }
}

echo "<h3>5. Các link chức năng</h3>";
echo "<a href='phieubangiao.php' target='_blank'>📋 Danh sách phiếu</a><br>";
echo "<a href='phieubangiao.php?action=select' target='_blank'>➕ Tạo phiếu mới</a><br>";
echo "<a href='debug_phieubangiao.php' target='_blank'>🔍 Debug permissions</a><br>";
echo "<a href='grant_admin.php' target='_blank'>🔐 Grant admin quyền</a><br>";

echo "<br><hr><p><em>Nếu tất cả ✅ mà vẫn không sửa/xóa được, hãy:</em></p>";
echo "<ol>
        <li>Đảm bảo đã pull code mới nhất từ GitHub</li>
        <li>Đăng xuất và đăng nhập lại</li>
        <li>Xóa cache trình duyệt (Ctrl+Shift+Delete)</li>
        <li>Thử trên trình duyệt ẩn danh (Incognito)</li>
      </ol>";
?>
