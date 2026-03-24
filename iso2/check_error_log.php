<?php
// Check PHP error log location
echo "<h2>PHP Error Log Configuration</h2>";

echo "<p><strong>error_log:</strong> " . ini_get('error_log') . "</p>";
echo "<p><strong>log_errors:</strong> " . ini_get('log_errors') . "</p>";
echo "<p><strong>display_errors:</strong> " . ini_get('display_errors') . "</p>";
echo "<p><strong>error_reporting:</strong> " . error_reporting() . "</p>";

// Test logging
error_log("Test log entry from check_error_log.php at " . date('Y-m-d H:i:s'));

echo "<h3>Recent Error Log (last 50 lines):</h3>";

$logFile = ini_get('error_log');
if ($logFile && file_exists($logFile)) {
    $lines = file($logFile);
    $recentLines = array_slice($lines, -50);
    echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto;'>";
    echo htmlspecialchars(implode('', $recentLines));
    echo "</pre>";
} else {
    echo "<p>Không tìm thấy error log file hoặc chưa được cấu hình.</p>";
    echo "<p>Thử kiểm tra: C:\\xampp\\apache\\logs\\error.log hoặc C:\\wamp\\logs\\php_error.log</p>";
    
    // Try common locations
    $commonPaths = [
        'C:\\xampp\\apache\\logs\\error.log',
        'C:\\xampp\\php\\logs\\php_error_log.txt',
        'C:\\wamp\\logs\\php_error.log',
        'C:\\wamp64\\logs\\php_error.log',
        __DIR__ . '\\error.log',
        __DIR__ . '\\..\\error.log'
    ];
    
    echo "<h3>Checking common log locations:</h3>";
    foreach ($commonPaths as $path) {
        if (file_exists($path)) {
            echo "<p style='color: green;'>✓ Found: $path</p>";
            echo "<p><a href='?show=" . urlencode($path) . "'>View this log</a></p>";
        } else {
            echo "<p style='color: gray;'>✗ Not found: $path</p>";
        }
    }
}

// Show specific log if requested
if (isset($_GET['show']) && file_exists($_GET['show'])) {
    $showPath = $_GET['show'];
    echo "<h3>Showing: " . htmlspecialchars($showPath) . "</h3>";
    $lines = file($showPath);
    $recentLines = array_slice($lines, -100);
    echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto; max-height: 500px;'>";
    echo htmlspecialchars(implode('', $recentLines));
    echo "</pre>";
}
?>
