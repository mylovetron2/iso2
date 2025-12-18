<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "Starting debug...\n<br>";

try {
    echo "Loading auth...\n<br>";
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/includes/auth.php';
    
    echo "Checking auth...\n<br>";
    if (!isset($_SESSION['user_id'])) {
        echo "Not logged in\n<br>";
        exit;
    }
    
    echo "Loading controller...\n<br>";
    require_once __DIR__ . '/controllers/ThietBiController.php';
    
    echo "Creating controller instance...\n<br>";
    $controller = new ThietBiController();
    
    echo "Calling index method...\n<br>";
    $controller->index();
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n<br>";
    echo "File: " . $e->getFile() . "\n<br>";
    echo "Line: " . $e->getLine() . "\n<br>";
    echo "Trace:\n<br><pre>";
    echo $e->getTraceAsString();
    echo "</pre>";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n<br>";
    echo "File: " . $e->getFile() . "\n<br>";
    echo "Line: " . $e->getLine() . "\n<br>";
    echo "Trace:\n<br><pre>";
    echo $e->getTraceAsString();
    echo "</pre>";
}
