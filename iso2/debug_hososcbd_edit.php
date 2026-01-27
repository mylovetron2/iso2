<?php
// Debug script to check hososcbd edit issue with ID 7679
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Debugging HoSoScBd Edit Issue (ID: 7679) ===<br><br>";

// Database connection
require_once __DIR__ . '/config/database.php';
$conn = getDBConnection();

if (!$conn) {
    die("Database connection failed!");
}

echo "<h3>1. Check if record exists:</h3>";
$stmt = $conn->prepare("SELECT * FROM hososcbd_iso WHERE stt = ?");
$stmt->execute([7679]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if ($record) {
    echo "✓ Record found<br>";
    echo "<pre>";
    print_r($record);
    echo "</pre>";
} else {
    echo "✗ Record NOT found with stt = 7679<br>";
}

echo "<h3>2. Check model file exists:</h3>";
$modelPath = __DIR__ . '/models/HoSoSCBD.php';
if (file_exists($modelPath)) {
    echo "✓ Model file exists: $modelPath<br>";
} else {
    echo "✗ Model file NOT found<br>";
}

echo "<h3>3. Try loading controller:</h3>";
try {
    require_once __DIR__ . '/models/HoSoSCBD.php';
    require_once __DIR__ . '/models/DonVi.php';
    require_once __DIR__ . '/models/ThietBi.php';
    require_once __DIR__ . '/models/LichSuDN.php';
    require_once __DIR__ . '/controllers/HoSoScBdController.php';
    
    echo "✓ All required files loaded successfully<br>";
    
    $controller = new HoSoScBdController();
    echo "✓ Controller instantiated<br>";
    
} catch (Exception $e) {
    echo "✗ Error loading: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>4. Check permissions function:</h3>";
require_once __DIR__ . '/includes/permissions.php';
if (function_exists('hasPermission')) {
    echo "✓ hasPermission function exists<br>";
    echo "Permission check for 'hososcbd.edit': " . (hasPermission('hososcbd.edit') ? 'YES' : 'NO') . "<br>";
} else {
    echo "✗ hasPermission function NOT found<br>";
}

echo "<h3>5. Simulate edit action:</h3>";
try {
    require_once __DIR__ . '/includes/auth.php';
    
    $_GET['action'] = 'edit';
    $_GET['id'] = '7679';
    
    echo "Simulating: GET action=edit&id=7679<br>";
    echo "This would trigger the edit() method in controller<br>";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

echo "<br>Done!";
