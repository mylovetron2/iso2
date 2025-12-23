<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Step by Step</h2>";

try {
    echo "<p>1. Testing constants...</p>";
    require_once __DIR__ . '/config/constants.php';
    echo "<p>✓ Constants OK</p>";
    
    echo "<p>2. Testing database...</p>";
    require_once __DIR__ . '/config/database.php';
    $db = getDBConnection();
    echo "<p>✓ Database OK</p>";
    
    echo "<p>3. Testing HoSoHCKD model...</p>";
    require_once __DIR__ . '/models/HoSoHCKD.php';
    $model = new HoSoHCKD();
    echo "<p>✓ Model OK</p>";
    
    echo "<p>4. Testing getByDateRange method...</p>";
    $tungay = date('Y-m-01');
    $denngay = date('Y-m-d');
    $items = $model->getByDateRange($tungay, $denngay, '');
    echo "<p>✓ Method OK - Found " . count($items) . " records</p>";
    
    if (count($items) > 0) {
        echo "<h3>Sample record:</h3>";
        echo "<pre>" . print_r($items[0], true) . "</pre>";
    }
    
    echo "<hr>";
    echo "<p style='color:green; font-size:18px;'><strong>✓ All tests passed! thongke_hckd.php should work.</strong></p>";
    echo "<p><a href='thongke_hckd.php'>Try thongke_hckd.php →</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
