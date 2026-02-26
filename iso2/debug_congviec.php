<?php
// Debug script - xóa sau khi fix xong
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug CongViec System</h2>";

try {
    session_start();
    
    require_once __DIR__ . '/config/constants.php';
    echo "✅ config/constants.php loaded<br>";
    
    require_once __DIR__ . '/includes/auth.php';
    echo "✅ includes/auth.php loaded<br>";
    
    // Skip auth for debugging
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test';
    
    require_once __DIR__ . '/controllers/CongViecSuaChuaController.php';
    echo "✅ Controller loaded<br>";
    
    $controller = new CongViecSuaChuaController();
    echo "✅ Controller instantiated<br>";
    
    $formData = $controller->getFormData();
    echo "✅ getFormData() OK - " . count($formData['nhanviens']) . " nhân viên<br>";
    echo "✅ getFormData() OK - " . count($formData['capdos']) . " cấp độ<br>";
    
    $viewData = $controller->index();
    echo "✅ index() OK<br>";
    
    echo "<br><strong>SUCCESS - Không có lỗi!</strong>";
    
} catch (Throwable $e) {
    echo "<br><strong style='color:red'>LỖI:</strong><br>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
