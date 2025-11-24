<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/PhieuBanGiao.php';
require_once __DIR__ . '/models/PhieuBanGiaoThietBi.php';
require_once __DIR__ . '/models/HoSoSCBD.php';

requireAuth();

// Test xóa phiếu
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    die("Cần ID phiếu");
}

echo "<h2>Debug xóa phiếu bàn giao #$id</h2>";

$phieuModel = new PhieuBanGiao();
$thietBiModel = new PhieuBanGiaoThietBi();
$hosoModel = new HoSoSCBD();

// 1. Kiểm tra phiếu tồn tại
echo "<h3>1. Kiểm tra phiếu</h3>";
$phieu = $phieuModel->findById($id);
if (!$phieu) {
    die("❌ Không tìm thấy phiếu");
}
echo "✅ Tìm thấy phiếu: {$phieu['sophieu']}<br>";
echo "Trạng thái: " . ($phieu['trangthai'] == 0 ? 'Nháp' : 'Đã duyệt') . "<br>";

if ($phieu['trangthai'] == 1) {
    die("❌ Không thể xóa phiếu đã duyệt");
}

// 2. Lấy danh sách thiết bị
echo "<h3>2. Danh sách thiết bị</h3>";
$thietBiList = $thietBiModel->getBySoPhieu($phieu['sophieu']);
echo "Số thiết bị: " . count($thietBiList) . "<br>";
foreach ($thietBiList as $tb) {
    echo "- STT: {$tb['stt']}, HoSo STT: {$tb['hososcbd_stt']}, Mã VT: {$tb['mavt']}<br>";
}

// 3. Thử xóa chi tiết
echo "<h3>3. Xóa chi tiết thiết bị</h3>";
try {
    $deleted = $thietBiModel->deleteBySoPhieu($phieu['sophieu']);
    echo "✅ Đã xóa $deleted chi tiết<br>";
} catch (Exception $e) {
    echo "❌ Lỗi xóa chi tiết: " . $e->getMessage() . "<br>";
    die();
}

// 4. Thử xóa phiếu chính
echo "<h3>4. Xóa phiếu chính</h3>";
try {
    $result = $phieuModel->delete($id);
    if ($result > 0) {
        echo "✅ Đã xóa phiếu chính (rows affected: $result)<br>";
    } else {
        echo "❌ Không xóa được phiếu (rows affected: $result)<br>";
        die();
    }
} catch (Exception $e) {
    echo "❌ Lỗi xóa phiếu: " . $e->getMessage() . "<br>";
    die();
}

// 5. Cập nhật trạng thái bg=0
echo "<h3>5. Cập nhật trạng thái thiết bị</h3>";
foreach ($thietBiList as $tb) {
    try {
        $hosoModel->update($tb['hososcbd_stt'], ['bg' => 0]);
        echo "✅ Đã cập nhật HoSo #{$tb['hososcbd_stt']} → bg=0<br>";
    } catch (Exception $e) {
        echo "❌ Lỗi cập nhật HoSo #{$tb['hososcbd_stt']}: " . $e->getMessage() . "<br>";
    }
}

echo "<h3>✅ HOÀN THÀNH</h3>";
echo "<a href='phieubangiao.php'>← Quay lại danh sách</a>";
?>
