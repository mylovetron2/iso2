<?php
/**
 * DEBUG CREATE ACTION
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
pre { background: #f0f0f0; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔍 DEBUG CREATE ACTION</h1>";

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<p class='success'>✅ Database connected</p>";
    
    // Test thiết bị query
    echo "<h2>📦 TEST THIẾT BỊ QUERY</h2>";
    
    $sql = "SELECT 
                stt as id, 
                tenvt as ten_thiet_bi, 
                somay as ky_ma_hieu,
                tinhtrang
            FROM thietbi_iso 
            WHERE tinhtrang IS NOT NULL
            ORDER BY tenvt ASC
            LIMIT 5";
    
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    
    $stmt = $db->query($sql);
    $thietbiList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='success'>✅ Found " . count($thietbiList) . " thiết bị</p>";
    echo "<pre>" . print_r($thietbiList, true) . "</pre>";
    
    // Test đơn vị query
    echo "<h2>🏢 TEST ĐƠN VỊ QUERY</h2>";
    
    $stmtDonVi = $db->query("SELECT madv, tendv FROM donvi_iso ORDER BY tendv");
    $donviList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='success'>✅ Found " . count($donviList) . " đơn vị</p>";
    echo "<pre>" . print_r($donviList, true) . "</pre>";
    
    // Test controller instantiation
    echo "<h2>🎮 TEST CONTROLLER</h2>";
    require_once __DIR__ . '/controllers/GiaoNhanThietBiController.php';
    
    $controller = new GiaoNhanThietBiController();
    echo "<p class='success'>✅ Controller instantiated</p>";
    
    // Test calling create() method
    echo "<h2>📝 TEST create() METHOD</h2>";
    
    try {
        ob_start();
        $controller->create();
        $output = ob_get_clean();
        
        echo "<p class='success'>✅ create() method executed</p>";
        echo "<p>Output length: " . strlen($output) . " bytes</p>";
        
        // Show first 500 chars
        echo "<h3>First 500 chars of output:</h3>";
        echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre>";
        
        // Show full output in iframe
        echo "<h3>Full output:</h3>";
        echo "<iframe srcdoc='" . htmlspecialchars($output) . "' style='width: 100%; height: 600px; border: 1px solid #ccc;'></iframe>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ ERROR calling create(): " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    
    // Check view file
    echo "<h2>📁 CHECK CREATE VIEW FILE</h2>";
    $viewPath = __DIR__ . '/views/giaonhanthietbi/create.php';
    echo "<p>Path: <code>{$viewPath}</code></p>";
    
    if (file_exists($viewPath)) {
        echo "<p class='success'>✅ View file exists</p>";
        echo "<p>Size: " . filesize($viewPath) . " bytes</p>";
        
        // Check first 20 lines
        $lines = file($viewPath);
        echo "<h3>First 20 lines:</h3>";
        echo "<pre>";
        for ($i = 0; $i < min(20, count($lines)); $i++) {
            $lineNum = $i + 1;
            $line = htmlspecialchars($lines[$i]);
            if (stripos($line, 'require') !== false || stripos($line, 'hasPermission') !== false) {
                $line = "<span style='background: yellow;'>{$line}</span>";
            }
            echo sprintf("%3d: %s", $lineNum, $line);
        }
        echo "</pre>";
    } else {
        echo "<p class='error'>❌ View file NOT FOUND!</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
