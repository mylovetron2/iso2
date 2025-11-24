<?php
session_start();
require_once __DIR__ . '/includes/auth.php';

echo "<h2>Kiểm tra quyền user hiện tại</h2>";

if (!isLoggedIn()) {
    die("❌ Chưa đăng nhập");
}

echo "✅ User: " . ($_SESSION['username'] ?? 'N/A') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'N/A') . "<br><br>";

$permissions = [
    'phieubangiao.view',
    'phieubangiao.create',
    'phieubangiao.edit',
    'phieubangiao.delete'
];

echo "<h3>Quyền phiếu bàn giao:</h3>";
foreach ($permissions as $perm) {
    $has = hasPermission($perm) ? '✅' : '❌';
    echo "$has $perm<br>";
}

echo "<br><a href='phieubangiao.php'>← Quay lại</a>";
?>
