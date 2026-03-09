<?php
declare(strict_types=1);

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

echo "<h2>🔍 Debug Phiếu Kiểm Soát Vật Tư</h2>";
echo "<pre>";

// 1. Check session
echo "1. Checking session...\n";
try {
    session_start();
    echo "✓ Session started\n";
    echo "  - Session ID: " . session_id() . "\n";
    echo "  - User logged in: " . (isset($_SESSION['user_id']) ? 'Yes (ID: ' . $_SESSION['user_id'] . ')' : 'No') . "\n\n";
} catch (Exception $e) {
    echo "✗ Session Error: " . $e->getMessage() . "\n\n";
}

// 2. Check auth & permissions
echo "2. Checking auth & permissions...\n";
try {
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/includes/auth.php';
    require_once __DIR__ . '/includes/permissions.php';
    echo "✓ Auth & permissions loaded\n";
    echo "  - requireAuth exists: " . (function_exists('requireAuth') ? 'Yes' : 'No') . "\n";
    echo "  - hasPermission exists: " . (function_exists('hasPermission') ? 'Yes' : 'No') . "\n";
    echo "  - isLoggedIn exists: " . (function_exists('isLoggedIn') ? 'Yes' : 'No') . "\n\n";
} catch (Exception $e) {
    echo "✗ Auth Error: " . $e->getMessage() . "\n\n";
}

// 3. Check database connection
echo "3. Checking database connection...\n";
try {
    require_once __DIR__ . '/config/database.php';
    $db = getDBConnection();
    echo "✓ Database connected\n\n";
} catch (Exception $e) {
    echo "✗ Database Error: " . $e->getMessage() . "\n\n";
    exit;
}

// 4. Check if tables exist
echo "4. Checking tables...\n";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'phieu_kiem_soat_vattu_iso'");
    $tableExists = $stmt->rowCount() > 0;
    echo "  - phieu_kiem_soat_vattu_iso: " . ($tableExists ? "✓ Exists" : "✗ NOT FOUND") . "\n";
    
    $stmt = $db->query("SHOW TABLES LIKE 'phieu_kiem_soat_vattu_chitiet_iso'");
    $tableExists2 = $stmt->rowCount() > 0;
    echo "  - phieu_kiem_soat_vattu_chitiet_iso: " . ($tableExists2 ? "✓ Exists" : "✗ NOT FOUND") . "\n\n";
    
    if (!$tableExists || !$tableExists2) {
        echo "⚠ TABLES MISSING! Run: php execute_create_phieu_kiem_soat_vattu.php\n";
        echo "Or manually run: create_table_phieu_kiem_soat_vattu.sql\n\n";
    }
} catch (Exception $e) {
    echo "✗ Table check error: " . $e->getMessage() . "\n\n";
}

// 5. Check vattu_thanh_ly_iso table
echo "5. Checking vattu_thanh_ly_iso table...\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM vattu_thanh_ly_iso");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ vattu_thanh_ly_iso exists with " . $result['total'] . " records\n\n";
} catch (Exception $e) {
    echo "✗ vattu_thanh_ly_iso error: " . $e->getMessage() . "\n\n";
}

// 6. Check controller file
echo "6. Checking controller file...\n";
$controllerPath = __DIR__ . '/controllers/PhieuKiemSoatVatTuController.php';
if (file_exists($controllerPath)) {
    echo "✓ Controller file exists\n";
    try {
        require_once $controllerPath;
        echo "✓ Controller loaded successfully\n";
        echo "  - Class exists: " . (class_exists('PhieuKiemSoatVatTuController') ? 'Yes' : 'No') . "\n\n";
    } catch (Exception $e) {
        echo "✗ Controller load error: " . $e->getMessage() . "\n\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
    }
} else {
    echo "✗ Controller file NOT FOUND: $controllerPath\n\n";
}

// 7. Try to instantiate controller
echo "7. Testing controller instantiation...\n";
try {
    $controller = new PhieuKiemSoatVatTuController();
    echo "✓ Controller instantiated successfully\n\n";
    
    // 8. Try to call index method
    echo "8. Testing index method...\n";
    ob_start();
    $controller->index();
    $output = ob_get_clean();
    echo "✓ Index method called successfully\n";
    echo "Output length: " . strlen($output) . " bytes\n\n";
    
} catch (Exception $e) {
    echo "✗ Controller instantiation error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
}

echo "</pre>";

echo "<hr>";
echo "<h3>Recommendations:</h3>";
echo "<ul>";
echo "<li>If tables are missing, run: <code>php execute_create_phieu_kiem_soat_vattu.php</code></li>";
echo "<li>Or import SQL manually: <code>create_table_phieu_kiem_soat_vattu.sql</code></li>";
echo "<li>Check PHP error log for more details</li>";
echo "<li>Try accessing: <a href='phieukiemsoatvattu.php'>phieukiemsoatvattu.php</a></li>";
echo "</ul>";
?>
