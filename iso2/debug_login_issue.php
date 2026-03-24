<?php
// Debug script - check what's wrong
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 KIỂM TRA LỖI</h2>";
echo "<hr>";

// Test 1: Check database connection
echo "<h3>1. Kết nối database:</h3>";
try {
    require_once 'config/database.php';
    $db = getDBConnection();
    echo "✅ Kết nối database OK<br>";
} catch (Exception $e) {
    echo "❌ Lỗi database: " . $e->getMessage() . "<br>";
    die();
}

// Test 2: Check tables
echo "<h3>2. Kiểm tra bảng roles:</h3>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'roles'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Bảng roles tồn tại<br>";
        
        // Check roles structure
        $stmt = $db->query("DESCRIBE roles");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<ul>";
        foreach ($cols as $col) {
            echo "<li>{$col['Field']} ({$col['Type']})</li>";
        }
        echo "</ul>";
    } else {
        echo "❌ Bảng roles KHÔNG tồn tại<br>";
    }
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "<br>";
}

// Test 3: Check User model
echo "<h3>3. Kiểm tra User model:</h3>";
try {
    require_once 'models/User.php';
    $userModel = new User();
    echo "✅ User model load OK<br>";
} catch (Exception $e) {
    echo "❌ Lỗi User model: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 4: Check hasPermission function
echo "<h3>4. Kiểm tra hasPermission():</h3>";
try {
    require_once 'includes/permissions.php';
    echo "✅ permissions.php load OK<br>";
    
    // Test với user_id giả
    if (function_exists('hasPermission')) {
        echo "✅ Function hasPermission() tồn tại<br>";
    } else {
        echo "❌ Function hasPermission() KHÔNG tồn tại<br>";
    }
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 5: Check auth.php
echo "<h3>5. Kiểm tra auth.php:</h3>";
try {
    require_once 'includes/auth.php';
    echo "✅ auth.php load OK<br>";
    
    if (function_exists('isLoggedIn')) {
        echo "✅ Function isLoggedIn() tồn tại<br>";
        $loggedIn = isLoggedIn();
        echo "Trạng thái: " . ($loggedIn ? "Đã đăng nhập" : "Chưa đăng nhập") . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 6: Check header.php
echo "<h3>6. Kiểm tra header.php:</h3>";
try {
    ob_start();
    include 'views/layouts/header.php';
    $headerOutput = ob_get_clean();
    echo "✅ header.php load OK<br>";
    echo "<small>Header length: " . strlen($headerOutput) . " bytes</small><br>";
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Lỗi header.php: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 7: Test giohang.php router
echo "<h3>7. Kiểm tra giohang.php:</h3>";
if (file_exists('giohang.php')) {
    echo "✅ File giohang.php tồn tại<br>";
    try {
        $syntax = exec("php -l giohang.php 2>&1", $output, $returnCode);
        if ($returnCode === 0) {
            echo "✅ Syntax giohang.php OK<br>";
        } else {
            echo "❌ Lỗi syntax: <pre>" . implode("\n", $output) . "</pre>";
        }
    } catch (Exception $e) {
        echo "⚠️ Không check được syntax (exec disabled)<br>";
    }
} else {
    echo "❌ File giohang.php KHÔNG tồn tại<br>";
}

// Test 8: Check PHP error log location
echo "<h3>8. PHP Error Log:</h3>";
echo "Error log location: " . ini_get('error_log') . "<br>";
echo "Display errors: " . ini_get('display_errors') . "<br>";

echo "<hr>";
echo "<h3>✅ HÀNH ĐỘNG TIẾP THEO:</h3>";
echo "<ol>";
echo "<li>Nếu có lỗi ở trên → Kiểm tra file đó</li>";
echo "<li>Nếu không có lỗi → Thử truy cập: <a href='/iso2/index.php'>index.php</a></li>";
echo "<li>Xem PHP error log để biết lỗi chi tiết</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='/iso2/views/auth/login.php'>→ Thử truy cập login.php trực tiếp</a></p>";
?>
