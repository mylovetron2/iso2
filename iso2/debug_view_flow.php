<?php
// Debug flow khi truy cập view page
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h2>Debug View Flow</h2>";

// 1. Kiểm tra URL parameters
echo "<h3>1. URL Parameters:</h3>";
echo "action = '" . ($_GET['action'] ?? 'NULL') . "'<br>";
echo "id = '" . ($_GET['id'] ?? 'NULL') . "'<br>";
echo "action length = " . strlen($_GET['action'] ?? '') . "<br>";
echo "action === 'view': " . (($_GET['action'] ?? '') === 'view' ? 'TRUE' : 'FALSE') . "<br>";

// 2. Kiểm tra auth
echo "<h3>2. Auth Check:</h3>";
require_once __DIR__ . '/includes/auth.php';
echo "isLoggedIn(): " . (isLoggedIn() ? 'TRUE' : 'FALSE') . "<br>";
echo "hasPermission('vattu.view'): " . (hasPermission('vattu.view') ? 'TRUE' : 'FALSE') . "<br>";

// 3. Test switch logic
echo "<h3>3. Switch Logic Test:</h3>";
$action = $_GET['action'] ?? 'index';
echo "Switch will match: ";
switch ($action) {
    case 'view':
        echo "CASE VIEW<br>";
        break;
    case 'create':
        echo "CASE CREATE<br>";
        break;
    case 'edit':
        echo "CASE EDIT<br>";
        break;
    default:
        echo "DEFAULT (index)<br>";
        break;
}

// 4. Test controller
echo "<h3>4. Controller Test:</h3>";
try {
    require_once __DIR__ . '/controllers/VatTuThanhLyController.php';
    $controller = new VatTuThanhLyController();
    echo "Controller created successfully<br>";
    
    // Check if view method exists
    echo "view() method exists: " . (method_exists($controller, 'view') ? 'TRUE' : 'FALSE') . "<br>";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
}

// 5. Show recent error log
echo "<h3>5. Recent Error Log (last 20 lines):</h3>";
$logFile = __DIR__ . '/error_log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recentLines = array_slice($lines, -20);
    echo "<pre style='background:#f1f5f9;padding:10px;'>" . htmlspecialchars(implode('', $recentLines)) . "</pre>";
} else {
    echo "No error_log file found<br>";
}

echo "<hr>";
echo "<p><a href='vattuthanhly.php?action=view&id=834'>Try: vattuthanhly.php?action=view&id=834</a></p>";
echo "<p><a href='test_direct_view.php?id=834'>Try: test_direct_view.php?id=834 (working)</a></p>";
