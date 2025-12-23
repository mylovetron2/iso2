<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug thongke_hckd.php</h2>";

try {
    echo "<p>Step 1: Loading config...</p>";
    require_once __DIR__ . '/config/constants.php';
    echo "<p>✓ Config loaded</p>";
    
    echo "<p>Step 2: Loading auth...</p>";
    require_once __DIR__ . '/includes/auth.php';
    echo "<p>✓ Auth loaded</p>";
    
    echo "<p>Step 3: Loading controller...</p>";
    require_once __DIR__ . '/controllers/ThongKeHCKDController.php';
    echo "<p>✓ Controller loaded</p>";
    
    echo "<p>Step 4: Creating controller instance...</p>";
    $controller = new ThongKeHCKDController();
    echo "<p>✓ Controller instantiated</p>";
    
    echo "<p>Step 5: Calling index method...</p>";
    ob_start();
    $controller->index();
    $output = ob_get_clean();
    
    echo "<p>✓ Index method executed</p>";
    echo "<hr>";
    echo $output;
    
} catch (Exception $e) {
    echo "<div style='color:red; background:#ffe6e6; padding:10px; margin:10px 0;'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    echo "<pre style='background:#f4f4f4; padding:10px;'>";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
} catch (Error $e) {
    echo "<div style='color:red; background:#ffe6e6; padding:10px; margin:10px 0;'>";
    echo "<strong>Fatal Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    echo "<pre style='background:#f4f4f4; padding:10px;'>";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
}
