<?php
// Simple debug for hososcbd edit issue
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_debug.log');

echo "=== Debug HoSoScBd Edit (ID: 7679) ===<br><br>";

// Start session and load required files
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Check if logged in
echo "<h3>1. Session check:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✓ Logged in as user ID: " . $_SESSION['user_id'] . "<br>";
    echo "Username: " . ($_SESSION['username'] ?? 'N/A') . "<br>";
} else {
    echo "✗ NOT logged in<br>";
}

// Load models
echo "<h3>2. Load models:</h3>";
try {
    require_once __DIR__ . '/models/HoSoSCBD.php';
    require_once __DIR__ . '/models/DonVi.php';
    require_once __DIR__ . '/models/ThietBi.php';
    require_once __DIR__ . '/models/LichSuDN.php';
    echo "✓ Models loaded<br>";
} catch (Exception $e) {
    echo "✗ Error loading models: " . $e->getMessage() . "<br>";
    die();
}

// Check record
echo "<h3>3. Check record:</h3>";
try {
    $model = new HoSoSCBD();
    $item = $model->findById(7679);
    
    if ($item) {
        echo "✓ Record found<br>";
        echo "maql: " . $item['maql'] . "<br>";
        echo "mavt: " . $item['mavt'] . "<br>";
        echo "somay: " . $item['somay'] . "<br>";
        echo "madv: " . $item['madv'] . "<br>";
        echo "phieu: " . $item['phieu'] . "<br>";
    } else {
        echo "✗ Record NOT found<br>";
        die();
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
    die();
}

// Check permissions
echo "<h3>4. Check permissions:</h3>";
require_once __DIR__ . '/includes/permissions.php';
if (function_exists('hasPermission')) {
    echo "✓ hasPermission function exists<br>";
    if (isset($_SESSION['user_id'])) {
        $hasPerm = hasPermission('hososcbd.edit');
        echo "Permission 'hososcbd.edit': " . ($hasPerm ? 'YES' : 'NO') . "<br>";
    } else {
        echo "Cannot check permissions - not logged in<br>";
    }
} else {
    echo "✗ hasPermission function NOT found<br>";
}

// Test update method
echo "<h3>5. Test model update method:</h3>";
try {
    // Prepare test data (same as current record to avoid changes)
    $testData = [
        'mavt' => $item['mavt'],
        'somay' => $item['somay'],
        'ngayyc' => $item['ngayyc'],
        'madv' => $item['madv'],
        'phieu' => $item['phieu'],
        'solg' => (int)$item['solg'],
        'cv' => $item['cv'] ?? '',
        'ngyeucau' => $item['ngyeucau'] ?? '',
        'ngnhyeucau' => $item['ngnhyeucau'] ?? '',
        'maql' => $item['maql'],
        'hoso' => $item['hoso']
    ];
    
    echo "✓ Test data prepared<br>";
    echo "<pre>";
    print_r($testData);
    echo "</pre>";
    
    // Check if update method exists
    if (method_exists($model, 'update')) {
        echo "✓ update() method exists<br>";
    } else {
        echo "✗ update() method NOT found<br>";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>6. Check controller:</h3>";
try {
    require_once __DIR__ . '/controllers/HoSoScBdController.php';
    $controller = new HoSoScBdController();
    echo "✓ Controller instantiated<br>";
    
    if (method_exists($controller, 'edit')) {
        echo "✓ edit() method exists<br>";
    } else {
        echo "✗ edit() method NOT found<br>";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<br><h3>Check error_debug.log for any errors</h3>";
echo "Done!";
