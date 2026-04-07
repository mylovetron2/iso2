<?php
header('Content-Type: text/html; charset=UTF-8');

echo '<h1>Clear PHP Opcode Cache</h1>';

// Check if OPcache is enabled
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo '<p style="color: green; font-size: 20px;">✓ OPcache đã được xóa thành công!</p>';
    } else {
        echo '<p style="color: red;">✗ Không thể xóa OPcache</p>';
    }
    
    // Show OPcache status
    $status = opcache_get_status();
    echo '<h2>OPcache Status:</h2>';
    echo '<pre>';
    echo 'Enabled: ' . ($status['opcache_enabled'] ? 'Yes' : 'No') . "\n";
    echo 'Cache full: ' . ($status['cache_full'] ? 'Yes' : 'No') . "\n";
    echo 'Cached scripts: ' . $status['opcache_statistics']['num_cached_scripts'] . "\n";
    echo 'Cached keys: ' . $status['opcache_statistics']['num_cached_keys'] . "\n";
    echo 'Max cached keys: ' . $status['opcache_statistics']['max_cached_keys'] . "\n";
    echo 'Hits: ' . $status['opcache_statistics']['hits'] . "\n";
    echo 'Misses: ' . $status['opcache_statistics']['misses'] . "\n";
    echo '</pre>';
} else {
    echo '<p style="color: orange;">OPcache is not enabled or not available</p>';
}

// Check other caches
echo '<h2>Other Caches:</h2>';
echo '<ul>';
echo '<li>APC: ' . (function_exists('apc_clear_cache') ? 'Available' : 'Not available') . '</li>';
echo '<li>Realpath cache size: ' . realpath_cache_size() . ' bytes</li>';
echo '</ul>';

echo '<hr>';
echo '<p><strong>Bước tiếp theo:</strong></p>';
echo '<ol>';
echo '<li>Refresh trình duyệt: <strong>Ctrl + Shift + R</strong></li>';
echo '<li>Hoặc: <strong>Ctrl + F5</strong> (hard refresh)</li>';
echo '<li>Hoặc: Mở <strong>Chế độ ẩn danh</strong> (Incognito/Private mode)</li>';
echo '<li>Sau đó mở: <a href="kehoachbaoduongdinhky.php">kehoachbaoduongdinhky.php</a></li>';
echo '</ol>';

echo '<hr>';
echo '<p><a href="kehoachbaoduongdinhky.php">→ Mở trang chính ngay</a></p>';
