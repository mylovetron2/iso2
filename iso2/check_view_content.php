<?php
/**
 * CHECK VIEW FILE CONTENT - Kiểm tra nội dung view file trên server
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
pre { background: #f0f0f0; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
.line { border-bottom: 1px solid #ddd; padding: 2px 0; }
.line-num { display: inline-block; width: 40px; color: #999; }
</style></head><body>";

echo "<h1>🔍 CHECK VIEW FILE CONTENT</h1>";

$viewPath = __DIR__ . '/views/giaonhanthietbi/index.php';

echo "<p><strong>File:</strong> <code>{$viewPath}</code></p>";

if (file_exists($viewPath)) {
    echo "<p class='success'>✅ File exists</p>";
    echo "<p><strong>Size:</strong> " . filesize($viewPath) . " bytes</p>";
    echo "<p><strong>Modified:</strong> " . date('Y-m-d H:i:s', filemtime($viewPath)) . "</p>";
    
    $content = file_get_contents($viewPath);
    
    // Check for path issues
    echo "<h2>🔍 CHECK PATHS</h2>";
    
    // Check OLD path (BAD)
    if (strpos($content, "/../views/layouts/header.php") !== false) {
        echo "<p class='error'>❌ FOUND OLD PATH: <code>/../views/layouts/header.php</code></p>";
        echo "<p class='error'>⚠️ <strong>View file CHƯA ĐƯỢC CẬP NHẬT trên server!</strong></p>";
    } else {
        echo "<p class='success'>✅ OLD PATH not found (good)</p>";
    }
    
    // Check NEW path (GOOD)
    if (strpos($content, "/../layouts/header.php") !== false) {
        echo "<p class='success'>✅ FOUND NEW PATH: <code>/../layouts/header.php</code></p>";
    } else {
        echo "<p class='error'>❌ NEW PATH not found</p>";
    }
    
    // Check footer paths
    if (strpos($content, "/../views/layouts/footer.php") !== false) {
        echo "<p class='error'>❌ FOUND OLD FOOTER PATH: <code>/../views/layouts/footer.php</code></p>";
    } else {
        echo "<p class='success'>✅ OLD FOOTER PATH not found (good)</p>";
    }
    
    if (strpos($content, "/../layouts/footer.php") !== false) {
        echo "<p class='success'>✅ FOUND NEW FOOTER PATH: <code>/../layouts/footer.php</code></p>";
    } else {
        echo "<p class='error'>❌ NEW FOOTER PATH not found</p>";
    }
    
    // Show first 30 lines
    echo "<h2>📄 FIRST 30 LINES</h2>";
    $lines = explode("\n", $content);
    echo "<pre>";
    for ($i = 0; $i < min(30, count($lines)); $i++) {
        $lineNum = $i + 1;
        $line = htmlspecialchars($lines[$i]);
        
        // Highlight require lines
        if (stripos($line, 'require') !== false) {
            $line = "<span style='background: yellow;'>{$line}</span>";
        }
        
        echo "<div class='line'><span class='line-num'>{$lineNum}</span> {$line}</div>";
    }
    echo "</pre>";
    
    // Show last 20 lines
    echo "<h2>📄 LAST 20 LINES</h2>";
    echo "<pre>";
    $totalLines = count($lines);
    $start = max(0, $totalLines - 20);
    for ($i = $start; $i < $totalLines; $i++) {
        $lineNum = $i + 1;
        $line = htmlspecialchars($lines[$i]);
        
        // Highlight require lines
        if (stripos($line, 'require') !== false) {
            $line = "<span style='background: yellow;'>{$line}</span>";
        }
        
        echo "<div class='line'><span class='line-num'>{$lineNum}</span> {$line}</div>";
    }
    echo "</pre>";
    
    // Count lines
    echo "<h2>📊 STATISTICS</h2>";
    echo "<p><strong>Total lines:</strong> " . count($lines) . "</p>";
    echo "<p><strong>File ends with:</strong> <code>" . htmlspecialchars(substr($content, -50)) . "</code></p>";
    
    // Check for PHP errors
    echo "<h2>🔍 CHECK FOR COMMON ISSUES</h2>";
    
    $issues = [];
    
    // Check for short open tags
    if (strpos($content, "<?=") !== false) {
        $issues[] = "⚠️ Uses short echo tags &lt;?= (may cause issues if short_open_tag is off)";
    }
    
    // Check for unclosed PHP tags
    $phpStarts = substr_count($content, "<?php");
    $phpEnds = substr_count($content, "?>");
    if ($phpStarts != $phpEnds + 1) { // +1 because last file usually doesn't close
        $issues[] = "⚠️ Mismatched PHP tags (starts: {$phpStarts}, ends: {$phpEnds})";
    }
    
    // Check for UTF-8 BOM
    if (substr($content, 0, 3) == "\xEF\xBB\xBF") {
        $issues[] = "⚠️ File has UTF-8 BOM (may cause header issues)";
    }
    
    if (empty($issues)) {
        echo "<p class='success'>✅ No common issues found</p>";
    } else {
        foreach ($issues as $issue) {
            echo "<p class='warning'>{$issue}</p>";
        }
    }
    
} else {
    echo "<p class='error'>❌ File NOT FOUND!</p>";
}

echo "<hr>";
echo "<p><a href='test_view_render.php'>→ Test view render</a></p>";
echo "<p><a href='giaonhanthietbi.php'>→ Go to actual page</a></p>";
echo "</body></html>";
