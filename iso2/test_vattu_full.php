<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Step 1: Load constants...</h3>";
require_once __DIR__ . '/config/constants.php';
echo "✅ Constants loaded<br>";

echo "<h3>Step 2: Load auth...</h3>";
require_once __DIR__ . '/includes/auth.php';
echo "✅ Auth loaded<br>";

echo "<h3>Step 3: Check session...</h3>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Session active<br>";
    echo "User: " . ($_SESSION['user_id'] ?? 'Not logged in') . "<br>";
} else {
    echo "⚠️ No session<br>";
}

echo "<h3>Step 4: Check permissions...</h3>";
if (function_exists('hasPermission')) {
    echo "✅ hasPermission function exists<br>";
    $canView = hasPermission('vattu.view');
    echo "Can view vattu: " . ($canView ? 'YES' : 'NO') . "<br>";
} else {
    echo "❌ hasPermission function not found<br>";
}

echo "<h3>Step 5: Load controller...</h3>";
require_once __DIR__ . '/controllers/VatTuThanhLyController.php';
echo "✅ Controller loaded<br>";

echo "<h3>Step 6: Try to instantiate controller...</h3>";
try {
    $controller = new VatTuThanhLyController();
    echo "✅ Controller instantiated<br>";
    
    echo "<h3>Step 7: Try index action...</h3>";
    ob_start();
    $controller->index();
    $output = ob_get_clean();
    
    if ($output) {
        echo "✅ Index action executed successfully<br>";
        echo "<hr>";
        echo $output;
    } else {
        echo "⚠️ Index action returned empty output<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
