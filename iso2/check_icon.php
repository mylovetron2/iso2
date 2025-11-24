<?php
// Script kiểm tra icon hiện tại
$file = __DIR__ . '/views/layouts/header.php';
$content = file_get_contents($file);

if (strpos($content, 'fa-clipboard-check') !== false) {
    echo "✅ Icon MỚI: fa-clipboard-check (đã cập nhật)\n";
} elseif (strpos($content, 'fa-handshake') !== false) {
    echo "❌ Icon CŨ: fa-handshake (chưa cập nhật)\n";
    echo "👉 Cần chạy: git pull origin main\n";
} else {
    echo "⚠️ Không tìm thấy icon nào\n";
}

// Hiển thị commit hiện tại
$lastCommit = shell_exec('git log -1 --oneline');
echo "\nCommit hiện tại:\n" . $lastCommit;
?>
