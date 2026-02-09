<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug_view_errors.log');

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';

echo "<!DOCTYPE html><html><body>";
echo "<h1>Debug View Access</h1>";

// Simulate logged in user
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['permissions'] = ['vattu.view', 'vattu.edit', 'vattu.create', 'vattu.delete'];

echo "<p>✓ Session setup</p>";
echo "<p>✓ User ID: " . ($_SESSION['user_id'] ?? 'not set') . "</p>";
echo "<p>✓ Has vattu.view permission: " . (hasPermission('vattu.view') ? 'YES' : 'NO') . "</p>";

$testId = $_GET['id'] ?? 834;
$_GET['id'] = $testId;
$_GET['action'] = 'view';

echo "<h3>Simulating request: vattuthanhly.php?action=view&id=$testId</h3>";

echo "<hr><h3>Now loading actual vattuthanhly.php...</h3>";
ob_start();

try {
    // Prevent redirect headers
    header_remove();
    
    require_once __DIR__ . '/vattuthanhly.php';
    
    $output = ob_get_clean();
    
    if (empty($output)) {
        echo "<p style='color:red;'>✗ No output received (possible redirect)</p>";
        echo "<p>Check error log: debug_view_errors.log</p>";
        
        // Check if headers were sent
        if (headers_sent($file, $line)) {
            echo "<p style='color:orange;'>⚠ Headers already sent at $file:$line</p>";
        }
        
        // Check for redirect
        $headers = headers_list();
        if (!empty($headers)) {
            echo "<h4>Headers sent:</h4><ul>";
            foreach ($headers as $header) {
                echo "<li>" . htmlspecialchars($header) . "</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color:green;'>✓ Output received (" . strlen($output) . " bytes)</p>";
        echo "<hr>";
        echo $output;
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "<h3 style='color:red;'>✗ Exception caught:</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<h4>Stack Trace:</h4>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
