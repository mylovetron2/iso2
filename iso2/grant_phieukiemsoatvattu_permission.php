<?php
declare(strict_types=1);

/**
 * Script để cấp quyền phiếu kiểm soát vật tư cho các role
 * Run once: php grant_phieukiemsoatvattu_permission.php
 */

require_once __DIR__ . '/config/database.php';

echo "=== Bắt đầu cấp quyền Phiếu Kiểm Soát Vật Tư ===\n\n";

try {
    echo "Đang kết nối database...\n";
    $db = getDBConnection();
    echo "✓ Kết nối thành công!\n\n";
    
    // Lấy tất cả các role
    echo "Đang lấy danh sách roles...\n";
    $stmt = $db->query("SELECT id, name, permissions FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✓ Tìm thấy " . count($roles) . " roles\n\n";
    
    $phieuperms = ['phieukiemsoatvattu.view', 'phieukiemsoatvattu.create', 'phieukiemsoatvattu.edit', 'phieukiemsoatvattu.delete'];
    
    foreach ($roles as $role) {
        $currentPerms = array_filter(array_map('trim', explode(',', $role['permissions'])));
        
        // Kiểm tra xem đã có quyền phieukiemsoatvattu chưa
        $hasPhieuPerm = false;
        foreach ($phieuperms as $pp) {
            if (in_array($pp, $currentPerms)) {
                $hasPhieuPerm = true;
                break;
            }
        }
        
        if (!$hasPhieuPerm) {
            // Thêm quyền phieukiemsoatvattu cho role
            if ($role['name'] === 'Admin' || $role['name'] === 'Manager') {
                // Admin và Manager có full quyền
                $newPerms = array_merge($currentPerms, $phieuperms);
            } else if ($role['name'] === 'Viewer') {
                // Viewer chỉ có quyền xem
                $newPerms = array_merge($currentPerms, ['phieukiemsoatvattu.view']);
            } else {
                // Role khác: thêm view, create và edit
                $newPerms = array_merge($currentPerms, ['phieukiemsoatvattu.view', 'phieukiemsoatvattu.create', 'phieukiemsoatvattu.edit']);
            }
            
            $newPermsStr = implode(',', array_unique($newPerms));
            
            $updateStmt = $db->prepare("UPDATE roles SET permissions = :permissions WHERE id = :id");
            $updateStmt->execute([
                ':permissions' => $newPermsStr,
                ':id' => $role['id']
            ]);
            
            echo "✓ Updated role: {$role['name']}\n";
            echo "  Old permissions: {$role['permissions']}\n";
            echo "  New permissions: $newPermsStr\n\n";
        } else {
            echo "- Role {$role['name']} already has phieukiemsoatvattu permissions\n\n";
        }
    }
    
    echo "\n✅ Permission grant completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
