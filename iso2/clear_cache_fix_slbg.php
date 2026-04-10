<?php
/**
 * Clear cache sau khi fix logic slbg
 */

// Clear opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ OPcache đã được reset<br>";
} else {
    echo "✗ OPcache không khả dụng<br>";
}

// Clear realpath cache
clearstatcache();
echo "✓ Realpath cache đã được clear<br>";

echo "<br><strong>Cache đã được xóa! Hãy thử lại tạo phiếu bàn giao.</strong>";
