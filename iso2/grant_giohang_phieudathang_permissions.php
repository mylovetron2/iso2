<?php
/**
 * Grant Giỏ hàng & Phiếu đặt hàng permissions to roles
 * File: grant_giohang_phieudathang_permissions.php
 */

require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    echo "<h2>Thêm quyền Giỏ hàng & Phiếu đặt hàng vào Roles</h2>";
    echo "<hr>";
    
    // Danh sách permissions cần thêm
    $newPermissions = [
        // Giỏ hàng
        'giohang.view',
        'giohang.add',
        'giohang.edit',
        'giohang.delete',
        // Phiếu đặt hàng
        'phieudathang.view',
        'phieudathang.create',
        'phieudathang.edit',
        'phieudathang.delete',
        'phieudathang.approve',
        'phieudathang.receive',
        'phieudathang.stock',
        'phieudathang.cancel',
        'phieudathang.export'
    ];
    
    echo "<h3>Permissions cần thêm:</h3>";
    echo "<ul>";
    foreach ($newPermissions as $perm) {
        echo "<li><code>$perm</code></li>";
    }
    echo "</ul>";
    echo "<hr>";
    
    // Get all roles
    $stmt = $conn->query("SELECT id, name, permissions FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($roles)) {
        echo "<p style='color: red;'>❌ Không tìm thấy role nào trong database.</p>";
        exit(1);
    }
    
    echo "<h3>Roles hiện có:</h3>";
    echo "<ul>";
    foreach ($roles as $role) {
        echo "<li><strong>{$role['name']}</strong> (ID: {$role['id']})</li>";
    }
    echo "</ul>";
    echo "<hr>";
    
    // Update each role
    $updated = 0;
    foreach ($roles as $role) {
        // Parse existing permissions
        $permissions = json_decode($role['permissions'], true);
        if (!is_array($permissions)) {
            $permissions = [];
        }
        
        $originalCount = count($permissions);
        
        // Determine which permissions to add based on role name
        $toAdd = [];
        
        if (stripos($role['name'], 'admin') !== false) {
            // Admin gets all permissions
            $toAdd = $newPermissions;
        } elseif (stripos($role['name'], 'user') !== false || stripos($role['name'], 'người dùng') !== false) {
            // Regular users get basic permissions
            $toAdd = [
                'giohang.view',
                'giohang.add',
                'giohang.edit',
                'giohang.delete',
                'phieudathang.view',
                'phieudathang.create'
            ];
        } elseif (stripos($role['name'], 'manager') !== false || stripos($role['name'], 'quản lý') !== false) {
            // Managers get most permissions
            $toAdd = $newPermissions;
        }
        
        if (empty($toAdd)) {
            echo "<p>⏭️ Bỏ qua role '<strong>{$role['name']}</strong>' (không phù hợp)</p>";
            continue;
        }
        
        // Add new permissions
        $added = false;
        foreach ($toAdd as $perm) {
            if (!in_array($perm, $permissions)) {
                $permissions[] = $perm;
                $added = true;
            }
        }
        
        if ($added) {
            // Update role
            $newPermissionsJson = json_encode($permissions, JSON_UNESCAPED_UNICODE);
            $stmt = $conn->prepare("UPDATE roles SET permissions = ? WHERE id = ?");
            $stmt->execute([$newPermissionsJson, $role['id']]);
            
            $newCount = count($permissions);
            $addedCount = $newCount - $originalCount;
            
            echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-left: 4px solid #28a745;'>";
            echo "<p><strong>✅ Đã cập nhật role: {$role['name']}</strong></p>";
            echo "<p>Số quyền trước: <strong>$originalCount</strong> → Sau: <strong>$newCount</strong> (+$addedCount)</p>";
            echo "<p><small>Quyền mới thêm:</small></p>";
            echo "<ul style='margin: 5px 0;'>";
            foreach ($toAdd as $perm) {
                if (!in_array($perm, $permissions)) {
                    echo "<li><code>$perm</code></li>";
                }
            }
            echo "</ul>";
            echo "</div>";
            
            $updated++;
        } else {
            echo "<p>✓ Role '<strong>{$role['name']}</strong>' đã có đầy đủ permissions</p>";
        }
    }
    
    echo "<hr>";
    echo "<div style='background: #d1ecf1; padding: 15px; border-left: 4px solid #0c5460;'>";
    echo "<h3>✅ HOÀN TẤT!</h3>";
    echo "<p>Đã cập nhật <strong>$updated</strong> role(s)</p>";
    echo "<p><strong>13 permissions</strong> đã được thêm vào hệ thống phân quyền</p>";
    echo "</div>";
    
    echo "<hr>";
    echo "<h3>Kiểm tra kết quả:</h3>";
    echo "<p>Xem danh sách permissions của từng role:</p>";
    
    $stmt = $conn->query("SELECT id, name, permissions FROM roles ORDER BY id");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($roles as $role) {
        $perms = json_decode($role['permissions'], true);
        if (!is_array($perms)) $perms = [];
        
        // Filter giohang & phieudathang permissions
        $relevantPerms = array_filter($perms, function($p) {
            return strpos($p, 'giohang.') === 0 || strpos($p, 'phieudathang.') === 0;
        });
        
        if (!empty($relevantPerms)) {
            echo "<div style='background: #fff; border: 1px solid #ddd; padding: 10px; margin: 10px 0;'>";
            echo "<h4>{$role['name']} (ID: {$role['id']})</h4>";
            echo "<ul>";
            foreach ($relevantPerms as $p) {
                echo "<li><code>$p</code></li>";
            }
            echo "</ul>";
            echo "</div>";
        }
    }
    
    echo "<hr>";
    echo "<p><a href='/iso2/vattuthanhly.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;'>← Quay lại Vật tư thanh lý</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
    echo "<h3>❌ LỖI:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
