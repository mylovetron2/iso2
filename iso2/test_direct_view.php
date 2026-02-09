<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Loading config...<br>";
require_once __DIR__ . '/config/constants.php';

echo "Step 2: Loading auth...<br>";
require_once __DIR__ . '/includes/auth.php';

echo "Step 3: Checking session...<br>";
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['permissions'] = ['vattu.view', 'vattu.edit'];

echo "Step 4: Has vattu.view permission: " . (hasPermission('vattu.view') ? 'YES' : 'NO') . "<br>";

echo "Step 5: Loading controller...<br>";
require_once __DIR__ . '/controllers/VatTuThanhLyController.php';

$testId = $_GET['id'] ?? 834;
$_GET['id'] = $testId;
$_GET['action'] = 'view';

echo "Step 6: Creating controller...<br>";
$controller = new VatTuThanhLyController();

echo "Step 7: Calling view() with ID=$testId...<br>";
echo "<hr>";

// Prevent any previous output from interfering
ob_clean();

try {
    $controller->view();
    echo "<hr>Step 8: view() completed successfully<br>";
} catch (Exception $e) {
    echo "<hr><h3 style='color:red'>Exception:</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
