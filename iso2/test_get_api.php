<?php
/**
 * Quick test - Direct API call
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing Get API...\n\n";

// Test 1: Find any STT
require_once __DIR__ . '/config/database.php';
$db = getDBConnection();
$stmt = $db->query("SELECT stt FROM congviec_suachua_iso LIMIT 1");
$row = $stmt->fetch();

if (!$row) {
    die("No records found in congviec_suachua_iso");
}

$testStt = $row['stt'];
echo "Test STT: $testStt\n\n";

// Test 2: Simulate AJAX request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'get';
$_GET['stt'] = $testStt;
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

// Capture output
ob_start();

try {
    include __DIR__ . '/congviec_suachua.php';
    $output = ob_get_clean();
    
    echo "Output:\n";
    echo $output . "\n\n";
    
    $json = json_decode($output, true);
    if ($json) {
        echo "Parsed JSON:\n";
        print_r($json);
    } else {
        echo "Not valid JSON\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
