<?php
/**
 * File để xóa cache PHP trên server production
 * Upload file này lên server và truy cập qua trình duyệt
 */
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Clear Server Cache</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .success { color: green; font-size: 20px; font-weight: bold; }
        .error { color: red; font-size: 20px; font-weight: bold; }
        .info { color: blue; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔧 Clear Server PHP Cache</h1>
    
    <?php
    // Clear OPcache
    if (function_exists('opcache_reset')) {
        if (opcache_reset()) {
            echo '<p class="success">✓ OPcache đã được xóa thành công!</p>';
        } else {
            echo '<p class="error">✗ Không thể xóa OPcache</p>';
        }
        
        // Show OPcache status
        $status = opcache_get_status();
        echo '<div class="info">';
        echo '<p>OPcache enabled: ' . ($status['opcache_enabled'] ? 'YES' : 'NO') . '</p>';
        echo '<p>Cache full: ' . ($status['cache_full'] ? 'YES' : 'NO') . '</p>';
        echo '<p>Restart pending: ' . ($status['restart_pending'] ? 'YES' : 'NO') . '</p>';
        echo '</div>';
    } else {
        echo '<p class="info">OPcache không được bật trên server này</p>';
    }
    
    // Clear APC cache if available
    if (function_exists('apc_clear_cache')) {
        apc_clear_cache();
        echo '<p class="success">✓ APC cache đã được xóa!</p>';
    }
    
    // Show file modification times
    echo '<hr>';
    echo '<h2>Thông tin file print.php:</h2>';
    $printFile = __DIR__ . '/views/phieubangiao/print.php';
    if (file_exists($printFile)) {
        echo '<p>File path: ' . $printFile . '</p>';
        echo '<p>Last modified: <strong>' . date('Y-m-d H:i:s', filemtime($printFile)) . '</strong></p>';
        echo '<p>File size: ' . filesize($printFile) . ' bytes</p>';
        
        // Show first few lines to verify
        echo '<h3>5 dòng đầu của file:</h3>';
        echo '<pre>' . htmlspecialchars(implode('', array_slice(file($printFile), 0, 5))) . '</pre>';
    } else {
        echo '<p class="error">File không tồn tại!</p>';
    }
    ?>
    
    <hr>
    <p><a href="phieubangiao.php">← Quay lại danh sách phiếu bàn giao</a></p>
    <p><a href="test_print_version.php">→ Xem test version</a></p>
</body>
</html>
