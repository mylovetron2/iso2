<?php
/**
 * DEBUG: Test create công việc trực tiếp
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

$db = getDBConnection();

// Auto-find valid nhanvien_stt
$stmt = $db->query("SELECT stt, hoten FROM resume ORDER BY stt LIMIT 1");
$nhanvien = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$nhanvien) {
    die("<h1 style='color: red'>❌ Bảng resume trống! Không có nhân viên để test.</h1>");
}

// Auto-find valid capdo_stt
$stmt = $db->query("SELECT stt, ten_capdo FROM capdo_baocuong_iso ORDER BY stt LIMIT 1");
$capdo = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$capdo) {
    die("<h1 style='color: red'>❌ Bảng capdo_baocuong_iso trống! Chạy setup_test_data.php trước.</h1>");
}

echo "<h1>DEBUG: Test Create Công Việc</h1>";
echo "<p><strong>Sử dụng nhân viên:</strong> {$nhanvien['stt']} - {$nhanvien['hoten']}</p>";
echo "<p><strong>Sử dụng cấp độ:</strong> {$capdo['stt']} - {$capdo['ten_capdo']}</p>";
echo "<hr>";

session_start();

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

// Simulate POST data với STT hợp lệ
$_POST = [
    'action' => 'save',
    'nhanvien_stt' => (string)$nhanvien['stt'],
    'ngay_lam' => date('Y-m-d'),
    'mavt' => 'TP7',
    'somay' => '452',
    'capdo_stt' => (string)$capdo['stt'],
    'noi_dung' => 'Test công việc từ debug script',
    'so_gio_lam' => '2',
    'gio_bat_dau' => '08:00',
    'gio_ket_thuc' => '10:00',
    'ghi_chu' => 'Test note',
    'hososcbd_stt' => '1'
];

$_GET['action'] = 'save';

echo "<h1>DEBUG: Test Create Công Việc</h1>";
echo "<h2>POST Data:</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";
echo "<hr>";

echo "<h2>Response:</h2>";

// Include the main script
try {
    include __DIR__ . '/congviec_suachua.php';
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px; border: 2px solid red;'>";
    echo "<strong>Exception:</strong> " . $e->getMessage();
    echo "<br><strong>File:</strong> " . $e->getFile();
    echo "<br><strong>Line:</strong> " . $e->getLine();
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
