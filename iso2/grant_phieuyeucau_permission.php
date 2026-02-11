<?php
/**
 * GRANT QUICK: Cấp nhanh quyền phieuyeucau cho admin
 * Truy cập: /iso2/grant_phieuyeucau_permission.php
 */

// Kết nối DB trực tiếp không cần auth
require_once __DIR__ . '/config/db.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Lấy tất cả admin roles
    $stmt = $db->query("SELECT * FROM roles WHERE name LIKE '%admin%' OR id = 1");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($roles)) {
        die('Không tìm thấy role admin trong hệ thống!');
    }
    
    echo "<h2>🔧 Cấp quyền phieuyeucau cho Admin</h2>";
    echo "<pre>";
    
    foreach ($roles as $role) {
        echo "\n--- Role: {$role['name']} (ID: {$role['id']}) ---\n";
        
        $currentPerms = $role['permissions'];
        echo "Quyền hiện tại: {$currentPerms}\n";
        
        // Kiểm tra đã có quyền chưa
        if (strpos($currentPerms, 'phieuyeucau.view') !== false) {
            echo "✓ Đã có quyền phieuyeucau!\n";
            continue;
        }
        
        // Thêm quyền mới
        $newPerms = 'phieuyeucau.view,phieuyeucau.create,phieuyeucau.edit,phieuyeucau.delete';
        $updatedPerms = empty($currentPerms) ? $newPerms : $currentPerms . ',' . $newPerms;
        
        // Update
        $updateStmt = $db->prepare("UPDATE roles SET permissions = ? WHERE id = ?");
        $success = $updateStmt->execute([$updatedPerms, $role['id']]);
        
        if ($success) {
            echo "✓ ĐÃ CẬP NHẬT!\n";
            echo "Quyền mới: {$updatedPerms}\n";
        } else {
            echo "✗ LỖI khi cập nhật!\n";
        }
    }
    
    echo "\n========================================\n";
    echo "✓ HOÀN THÀNH!\n";
    echo "========================================\n";
    echo "\nBước tiếp theo:\n";
    echo "1. Xóa file này (grant_phieuyeucau_permission.php) vì lý do bảo mật\n";
    echo "2. Đăng nhập lại vào hệ thống\n";
    echo "3. Kiểm tra menu 'Quản lý số phiếu YC'\n";
    echo "</pre>";
    
    echo "<br>";
    echo "<a href='views/auth/login.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Đi đến trang đăng nhập</a>";
    
} catch (Exception $e) {
    echo "<h2>❌ LỖI</h2>";
    echo "<pre>";
    echo "Lỗi: " . $e->getMessage();
    echo "\n\nKiểm tra:\n";
    echo "- Kết nối database\n";
    echo "- Bảng 'roles' có tồn tại không\n";
    echo "- File config/db.php đã đúng chưa\n";
    echo "</pre>";
}
