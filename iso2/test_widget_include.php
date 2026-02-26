<?php
/**
 * DEBUG: Test include widget trực tiếp
 * URL: http://localhost/iso2/test_widget_include.php?id=1
 */

// Start session first
session_start();

// Enable error reporting FIRST
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate environment
$_GET['id'] = isset($_GET['id']) ? $_GET['id'] : 1;
$stt = (int)$_GET['id'];

echo "<h1>DEBUG: Test Widget Include</h1>";
echo "<p><strong>STT:</strong> $stt</p>";

// Load item data
require_once __DIR__ . '/models/HoSoSCBD.php';
$model = new HoSoSCBD();
$item = $model->findById($stt);

if (!$item) {
    die("<div style='color: red; font-size: 20px;'>❌ Không tìm thấy hồ sơ với ID = $stt</div>");
}

echo "<p><strong>✅ Hồ sơ tìm thấy:</strong> " . htmlspecialchars($item['phieu']) . " - " . htmlspecialchars($item['mavt']) . "</p>";

echo "<h2>Thông tin item:</h2>";
echo "<pre>";
print_r($item);
echo "</pre>";

echo "<hr>";
echo "<h2>🔧 WIDGET BÊN DƯỚI:</h2>";

// Enable error reporting FIRST
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load required files
echo "<p>Loading dependencies...</p>";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/includes/permissions.php';
echo "<p>✓ Dependencies loaded</p>";

// Check if functions exist
echo "<p>Checking functions: ";
echo function_exists('getDBConnection') ? '✓ getDBConnection ' : '✗ getDBConnection ';
echo function_exists('hasPermission') ? '✓ hasPermission ' : '✗ hasPermission ';
echo "</p>";

// Include widget
echo "<p>Including widget...</p>";
ob_start();
try {
    include __DIR__ . '/views/hososcbd/components/congviec_widget.php';
    $widgetOutput = ob_get_clean();
    echo $widgetOutput;
    echo "<p style='color: green; font-weight: bold;'>✅ Widget included thành công!</p>";
} catch (Exception $e) {
    $widgetOutput = ob_get_clean();
    echo $widgetOutput;
    echo "<p style='color: red; font-weight: bold;'>❌ Exception: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    $widgetOutput = ob_get_clean();
    echo $widgetOutput;
    echo "<p style='color: red; font-weight: bold;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h3>End of Test</h3>";
