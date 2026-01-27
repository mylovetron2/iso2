<?php
// Check PHP error log
header('Content-Type: text/html; charset=UTF-8');

echo "<h2>PHP Error Log</h2>";
echo "<p>Checking common error log locations...</p>";

$locations = [
    __DIR__ . '/logs/php_errors.log',
    __DIR__ . '/error_log',
    __DIR__ . '/error_debug.log',
    '/tmp/error_log',
    ini_get('error_log')
];

foreach ($locations as $path) {
    if ($path && file_exists($path)) {
        echo "<h3>Found: $path</h3>";
        echo "<div style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 500px; overflow: auto;'>";
        
        // Get last 50 lines
        $lines = file($path);
        $recentLines = array_slice($lines, -50);
        
        foreach ($recentLines as $line) {
            if (stripos($line, 'hososcbd') !== false || stripos($line, 'edit error') !== false) {
                echo "<div style='color: red; font-weight: bold;'>" . htmlspecialchars($line) . "</div>";
            } else {
                echo "<div>" . htmlspecialchars($line) . "</div>";
            }
        }
        
        echo "</div>";
    }
}

echo "<h3>PHP Error Log Setting:</h3>";
echo "error_log: " . ini_get('error_log') . "<br>";
echo "log_errors: " . ini_get('log_errors') . "<br>";
echo "display_errors: " . ini_get('display_errors') . "<br>";
