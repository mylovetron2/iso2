<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/VatTuThanhLyController.php';

echo "Testing VatTuThanhLyController::view() method...<br>";

// Simulate a logged-in user
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';

$testId = $_GET['id'] ?? 834; // Use one of the STT from debug query

echo "Testing with STT: $testId<br>";

try {
    $_GET['id'] = $testId;
    $controller = new VatTuThanhLyController();
    echo "Controller created successfully<br>";
    
    $controller->view();
    echo "View method executed successfully<br>";
} catch (Exception $e) {
    echo "<h3 style='color:red;'>Error:</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<h3>Stack Trace:</h3>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
