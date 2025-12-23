<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Form Submit - Nhập Hồ Sơ HC/KĐ</h2>";

// Simulate POST data
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'tenmay' => '001.02.0005',  // Thay bằng mã vật tư thực tế
    'sohs' => '',  // Để trống để test auto-generate
    'ngayhc' => '2025-12-19',
    'ngayhctt' => '2026-12-19',
    'nhanvien' => 'Nguyễn Văn A',  // Test với tên nhập tự do
    'noithuchien' => 'Xưởng',
    'ttkt' => 'Tốt',
    'danchuan' => 'on',
    'thietbidc1' => 'TB001'
];

require_once __DIR__ . '/controllers/BangCanhBaoController.php';

try {
    $controller = new BangCanhBaoController();
    
    echo "<h3>POST Data:</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    echo "<h3>Executing saveHoSo()...</h3>";
    
    // Capture output
    ob_start();
    $controller->saveHoSo();
    $output = ob_get_clean();
    
    echo "<p style='color: green;'>✓ Execution completed</p>";
    
    if ($output) {
        echo "<h3>Output:</h3>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><a href='bangcanhbao.php?action=formhoso'>← Back to form</a></p>";
