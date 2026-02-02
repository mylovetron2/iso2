<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

// Mock session for testing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mock hasPermission function if not exists
if (!function_exists('hasPermission')) {
    function hasPermission($perm) { return true; }
}

try {
    echo "<h3>Testing Database Connection...</h3>";
    $pdo = getDBConnection();
    echo "✅ Connected<br><br>";
    
    echo "<h3>Testing Table Structure...</h3>";
    $stmt = $pdo->query("DESCRIBE vattu_thanh_ly_iso");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns: " . implode(", ", $columns) . "<br><br>";
    
    echo "<h3>Testing Model...</h3>";
    require_once 'models/VatTuThanhLy.php';
    $model = new VatTuThanhLy();
    echo "✅ Model loaded<br><br>";
    
    echo "<h3>Testing getAllWithStats...</h3>";
    $items = $model->getAllWithStats();
    echo "✅ Found " . count($items) . " items<br><br>";
    
    if (count($items) > 0) {
        echo "<h3>First Item:</h3>";
        echo "<pre>";
        print_r($items[0]);
        echo "</pre>";
    }
    
    echo "<h3>Testing Controller...</h3>";
    require_once 'controllers/VatTuThanhLyController.php';
    echo "✅ Controller loaded<br><br>";
    
    echo "<h2 style='color: green;'>✅ All tests passed!</h2>";
    echo "<p><a href='vattuthanhly.php'>Go to Vật Tư Thanh Lý</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error Found:</h2>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}
