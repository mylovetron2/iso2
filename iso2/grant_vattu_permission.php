<?php
declare(strict_types=1);

/**
 * Script để cấp quyền vật tư thanh lý cho các role
 * Run once: php grant_vattu_permission.php
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection();
    
    // Lấy tất cả các role
    $stmt = $db->query("SELECT id, name, permissions FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $vattuperms = ['vattu.view', 'vattu.create', 'vattu.edit', 'vattu.delete'];
    
    foreach ($roles as $role) {
        $currentPerms = array_filter(array_map('trim', explode(',', $role['permissions'])));
        
        // Kiểm tra xem đã có quyền vattu chưa
        $hasVattuPerm = false;
        foreach ($vattuperms as $vp) {
            if (in_array($vp, $currentPerms)) {
                $hasVattuPerm = true;
                break;
            }
        }
        
        if (!$hasVattuPerm) {
            // Thêm quyền vattu cho role
            if ($role['name'] === 'Admin' || $role['name'] === 'Manager') {
                // Admin và Manager có full quyền
                $newPerms = array_merge($currentPerms, $vattuperms);
            } else if ($role['name'] === 'Viewer' || $role['name'] === 'User') {
                // Viewer chỉ có quyền xem
                $newPerms = array_merge($currentPerms, ['vattu.view']);
            } else {
                // Role khác: thêm view và edit
                $newPerms = array_merge($currentPerms, ['vattu.view', 'vattu.edit']);
            }
            
            $newPermsStr = implode(',', array_unique($newPerms));
            
            $updateStmt = $db->prepare("UPDATE roles SET permissions = :permissions WHERE id = :id");
            $updateStmt->execute([
                ':permissions' => $newPermsStr,
                ':id' => $role['id']
            ]);
            
            echo "✓ Đã cập nhật quyền vật tư cho role: {$role['name']}\n";
        } else {
            echo "→ Role {$role['name']} đã có quyền vật tư\n";
        }
    }
    
    echo "\n✅ Hoàn thành cập nhật quyền vật tư thanh lý!\n";
    
} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
    exit(1);
}
