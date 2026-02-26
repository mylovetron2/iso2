<?php
/**
 * Debug hososcbd_congviec.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug hososcbd_congviec.php</h1>";

echo "<p>Step 1: Check session...</p>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p>✓ Session OK</p>";

echo "<p>Step 2: Load database...</p>";
require_once __DIR__ . '/config/database.php';
echo "<p>✓ Database loaded</p>";

echo "<p>Step 3: Check ID parameter...</p>";
$stt = isset($_GET['id']) ? (int)$_GET['id'] : 0;
echo "<p>ID = $stt</p>";

if (!$stt) {
    echo "<p style='color: red'>❌ No ID provided! Add ?id=1 to URL</p>";
} else {
    echo "<p>Step 4: Load model...</p>";
    require_once __DIR__ . '/models/HoSoSCBD.php';
    $model = new HoSoSCBD();
    echo "<p>✓ Model loaded</p>";
    
    echo "<p>Step 5: Find record...</p>";
    $item = $model->findById($stt);
    
    if ($item) {
        echo "<p>✓ Record found:</p>";
        echo "<pre>";
        print_r($item);
        echo "</pre>";
        
        echo "<p>Step 6: Try to include view...</p>";
        try {
            include __DIR__ . '/views/hososcbd/congviec_list.php';
            echo "<p>✓ View included</p>";
        } catch (Exception $e) {
            echo "<p style='color: red'>❌ Error including view: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red'>❌ No record found with ID = $stt</p>";
    }
}
