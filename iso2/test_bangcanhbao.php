<?php
// Test debug file for Bang Cảnh Báo
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Bang Cảnh Báo</h1>";

// Test 1: Config
echo "<h2>1. Test Database Config</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $conn = getDBConnection(true);
    echo "<p style='color:green;'>✓ Database connected successfully</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    die();
}

// Test 2: Auth
echo "<h2>2. Test Auth</h2>";
session_start();
try {
    require_once __DIR__ . '/includes/auth.php';
    if (isLoggedIn()) {
        echo "<p style='color:green;'>✓ User is logged in</p>";
    } else {
        echo "<p style='color:orange;'>⚠ User is not logged in (will redirect)</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Auth failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: Models
echo "<h2>3. Test Models</h2>";
try {
    require_once __DIR__ . '/models/KeHoachISO.php';
    $keHoachModel = new KeHoachISO();
    echo "<p style='color:green;'>✓ KeHoachISO model loaded</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ KeHoachISO model failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    require_once __DIR__ . '/models/HoSoHCKD.php';
    $hoSoModel = new HoSoHCKD();
    echo "<p style='color:green;'>✓ HoSoHCKD model loaded</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ HoSoHCKD model failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    require_once __DIR__ . '/models/ThietBiHCKD.php';
    $thietBiModel = new ThietBiHCKD();
    echo "<p style='color:green;'>✓ ThietBiHCKD model loaded</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ ThietBiHCKD model failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    require_once __DIR__ . '/models/Resume.php';
    $resumeModel = new Resume();
    echo "<p style='color:green;'>✓ Resume model loaded</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Resume model failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: Controller
echo "<h2>4. Test Controller</h2>";
try {
    require_once __DIR__ . '/controllers/BangCanhBaoController.php';
    $controller = new BangCanhBaoController();
    echo "<p style='color:green;'>✓ BangCanhBaoController loaded</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Controller failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Test 5: Database queries
echo "<h2>5. Test Database Queries</h2>";
try {
    $years = $keHoachModel->getAvailableYears();
    echo "<p style='color:green;'>✓ Available years: " . implode(', ', $years) . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    $month = (int)date('m');
    $year = (int)date('Y');
    $count = $keHoachModel->countByMonthYear($month, $year);
    echo "<p style='color:green;'>✓ Count for {$month}/{$year}: {$count} records</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Count query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>✓ All tests completed!</h2>";
echo "<p><a href='bangcanhbao.php'>Go to Bang Cảnh Báo</a></p>";
