<?php
/**
 * Script khắc phục quyền phieuyeucau
 * Chạy script này nếu gặp lỗi không đăng nhập hoặc không thấy menu sau khi thêm quyền mới
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';

// Kiểm tra đã đăng nhập chưa
if (!isLoggedIn()) {
    die('Vui lòng đăng nhập trước khi chạy script này!');
}

// Chỉ admin mới chạy được
if (!hasRole(ROLE_ADMIN)) {
    die('Chỉ admin mới có thể chạy script này!');
}

$roleModel = new BaseModel('roles');

echo "<h1>Khắc phục quyền phieuyeucau</h1>";
echo "<pre>";

// Lấy tất cả các role
$roles = $roleModel->all();

echo "=== DANH SÁCH ROLE HIỆN TẠI ===\n\n";
foreach ($roles as $role) {
    echo "Role: {$role['name']} (ID: {$role['id']})\n";
    echo "Permissions: {$role['permissions']}\n";
    
    // Kiểm tra có quyền phieuyeucau chưa
    $hasPhieuyeucau = strpos($role['permissions'], 'phieuyeucau.view') !== false;
    echo "Có quyền phieuyeucau: " . ($hasPhieuyeucau ? 'CÓ' : 'CHƯA') . "\n";
    echo str_repeat('-', 80) . "\n\n";
}

echo "\n=== BẮT ĐẦU CẬP NHẬT ===\n\n";

// Cập nhật quyền cho admin
$adminRoles = array_filter($roles, function($role) {
    return strtolower($role['name']) === 'admin';
});

foreach ($adminRoles as $adminRole) {
    $currentPerms = $adminRole['permissions'];
    
    // Kiểm tra đã có quyền chưa
    if (strpos($currentPerms, 'phieuyeucau.view') !== false) {
        echo "✓ Role '{$adminRole['name']}' đã có quyền phieuyeucau\n";
        continue;
    }
    
    // Thêm quyền mới
    $newPerms = [
        'phieuyeucau.view',
        'phieuyeucau.create',
        'phieuyeucau.edit',
        'phieuyeucau.delete'
    ];
    
    $updatedPerms = $currentPerms;
    if (!empty($updatedPerms)) {
        $updatedPerms .= ',';
    }
    $updatedPerms .= implode(',', $newPerms);
    
    $success = $roleModel->update($adminRole['id'], ['permissions' => $updatedPerms]);
    
    if ($success) {
        echo "✓ Đã cập nhật quyền cho role '{$adminRole['name']}'\n";
        echo "  Permissions mới: {$updatedPerms}\n";
    } else {
        echo "✗ Lỗi khi cập nhật role '{$adminRole['name']}'\n";
    }
}

echo "\n=== HOÀN THÀNH ===\n\n";
echo "Vui lòng:\n";
echo "1. Đăng xuất\n";
echo "2. Đăng nhập lại\n";
echo "3. Kiểm tra menu 'Quản lý số phiếu YC'\n";
echo "</pre>";

echo "<br><br>";
echo "<a href='logout.php' style='padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px;'>Đăng xuất ngay</a>";
echo " ";
echo "<a href='index.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Về trang chủ</a>";
