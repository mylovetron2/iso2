<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/PhieuBanGiao.php';
require_once __DIR__ . '/models/PhieuBanGiaoThietBi.php';
require_once __DIR__ . '/models/HoSoSCBD.php';

requireAuth();

$phieuModel = new PhieuBanGiao();
$thietBiModel = new PhieuBanGiaoThietBi();
$hosoModel = new HoSoSCBD();

echo "<h2>Tạo phiếu bàn giao nháp để test</h2>";

// Lấy 1 thiết bị bất kỳ đã hoàn thành sửa chữa
$sql = "SELECT stt, mavt, somay, maql, phieu, madv 
        FROM hososcbd_iso 
        WHERE ngaykt IS NOT NULL AND ngaykt != '0000-00-00' 
        LIMIT 1";
$stmt = $hosoModel->query($sql);
$device = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$device) {
    die("❌ Không có thiết bị nào để tạo phiếu test");
}

echo "Thiết bị test: {$device['mavt']} - {$device['somay']}<br>";

// Tạo phiếu test
try {
    $sophieu = $phieuModel->getNextSoPhieu();
    echo "Số phiếu mới: $sophieu<br>";
    
    $phieuData = [
        'sophieu' => $sophieu,
        'phieuyc' => $device['phieu'],
        'ngaybg' => date('Y-m-d'),
        'nguoigiao' => 'Test User',
        'nguoinhan' => 'Người nhận test',
        'donvigiao' => $device['madv'],
        'donvinhan' => $device['madv'],
        'ghichu' => 'PHIẾU TEST - CÓ THỂ XÓA',
        'trangthai' => 0, // Nháp
        'nguoitao' => $_SESSION['username'] ?? 'test'
    ];
    
    $phieuId = $phieuModel->create($phieuData);
    
    if ($phieuId) {
        echo "✅ Đã tạo phiếu ID: $phieuId<br>";
        
        // Thêm thiết bị vào phiếu
        $thietBiData = [[
            'hososcbd_stt' => $device['stt'],
            'tinhtrang' => 'Test',
            'ghichu' => 'Test'
        ]];
        
        if ($thietBiModel->createMultiple($sophieu, $thietBiData)) {
            echo "✅ Đã thêm thiết bị vào phiếu<br>";
        }
        
        echo "<br><h3>✅ HOÀN THÀNH</h3>";
        echo "<p>Phiếu nháp đã tạo: <strong>$sophieu</strong> (ID: $phieuId)</p>";
        echo "<a href='test_delete_phieu.php?id=$phieuId' style='background: red; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🗑️ Test xóa phiếu này</a><br><br>";
        echo "<a href='phieubangiao.php?action=view&id=$phieuId'>👁️ Xem phiếu</a> | ";
        echo "<a href='list_phieu_nhap.php'>📋 Danh sách phiếu nháp</a> | ";
        echo "<a href='phieubangiao.php'>← Quay lại</a>";
    } else {
        echo "❌ Không tạo được phiếu";
    }
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage();
}
?>
