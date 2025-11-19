<?php
// Script kiểm tra và hiển thị log lỗi PHP (error_log)
$logPaths = [
    __DIR__ . '/error_log',
    dirname(__DIR__) . '/error_log',
    ini_get('error_log'),
];
$logFile = null;
foreach ($logPaths as $path) {
    if ($path && file_exists($path)) {
        $logFile = $path;
        break;
    }
}
?><!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xem log lỗi PHP</title>
    <style>body{font-family:monospace;background:#f8fafc;color:#222}pre{background:#f1f5f9;padding:1em;border-radius:6px;max-width:900px;overflow:auto}</style>
</head>
<body>
    <h2>Log lỗi PHP (error_log)</h2>
    <?php if ($logFile): ?>
        <div><b>Đường dẫn log:</b> <?php echo htmlspecialchars($logFile); ?></div>
        <pre><?php echo htmlspecialchars(file_get_contents($logFile)); ?></pre>
    <?php else: ?>
        <div style="color:red">Không tìm thấy file error_log trong thư mục project hoặc cấu hình PHP.</div>
    <?php endif; ?>
</body>
</html>
