<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/config/database.php';

requireAuth();

$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'Unknown';

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<meta charset='UTF-8'>";
echo "<title>Debug Quyền User</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
    h1 { color: #2563eb; }
    h2 { color: #059669; margin-top: 20px; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f3f4f6; font-weight: bold; }
    .yes { color: green; font-weight: bold; }
    .no { color: red; }
    .info { background: #e5e7eb; padding: 10px; border-left: 4px solid #6b7280; margin: 10px 0; }
</style>";
echo "</head><body>";

echo "<h1>🔍 Debug Quyền User: " . htmlspecialchars($username) . " (ID: $userId)</h1>";

try {
    $db = getDBConnection();
    
    // Kiểm tra quyền phanloai_vattu
    echo "<h2>✅ Kiểm tra quyền phân loại vật tư</h2>";
    
    $permissions_to_check = [
        'vattu.view',
        'vattu.create', 
        'vattu.edit',
        'vattu.delete',
        'phanloai_vattu.view',
        'phanloai_vattu.create',
        'phanloai_vattu.edit',
        'phanloai_vattu.delete'
    ];
    
    echo "<table>";
    echo "<tr><th>Quyền</th><th>Có quyền?</th></tr>";
    
    foreach ($permissions_to_check as $perm) {
        $has = hasPermission($perm);
        $status = $has ? "<span class='yes'>✓ CÓ</span>" : "<span class='no'>✗ KHÔNG</span>";
        echo "<tr><td><code>$perm</code></td><td>$status</td></tr>";
    }
    echo "</table>";
    
    // Kiểm tra quyền từ role
    echo "<h2>📋 Quyền từ Role</h2>";
    $stmt = $db->prepare("
        SELECT r.name as role_name, r.permissions 
        FROM role_user ru 
        INNER JOIN roles r ON ru.role_id = r.id 
        WHERE ru.user_id = ?
    ");
    $stmt->execute([$userId]);
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($roles)) {
        echo "<div class='info'>❌ User chưa được gán vào role nào</div>";
    } else {
        foreach ($roles as $role) {
            echo "<h3>Role: " . htmlspecialchars($role['role_name']) . "</h3>";
            $perms = explode(',', $role['permissions']);
            echo "<ul>";
            foreach ($perms as $p) {
                echo "<li><code>" . htmlspecialchars(trim($p)) . "</code></li>";
            }
            echo "</ul>";
        }
    }
    
    // Kiểm tra quyền trực tiếp
    echo "<h2>👤 Quyền trực tiếp từ user_permissions</h2>";
    $stmt = $db->prepare("
        SELECT p.name, p.description 
        FROM user_permissions up 
        INNER JOIN permissions p ON up.permission_id = p.id 
        WHERE up.user_id = ?
        ORDER BY p.name
    ");
    $stmt->execute([$userId]);
    $userPerms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($userPerms)) {
        echo "<div class='info'>❌ User chưa được cấp quyền trực tiếp nào</div>";
    } else {
        echo "<table>";
        echo "<tr><th>Tên quyền</th><th>Mô tả</th></tr>";
        foreach ($userPerms as $p) {
            echo "<tr><td><code>" . htmlspecialchars($p['name']) . "</code></td>";
            echo "<td>" . htmlspecialchars($p['description']) . "</td></tr>";
        }
        echo "</table>";
    }
    
    // Kiểm tra quyền có trong database
    echo "<h2>🗄️ Tất cả quyền phanloai_vattu trong database</h2>";
    $stmt = $db->query("SELECT id, name, description FROM permissions WHERE name LIKE 'phanloai_vattu.%' ORDER BY name");
    $allPerms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allPerms)) {
        echo "<div class='info' style='background: #fee2e2; border-color: red; color: red;'>";
        echo "❌ <strong>KHÔNG CÓ quyền phanloai_vattu trong database!</strong><br>";
        echo "Cần chạy file: <a href='setup_phanloai_vattu.php'>setup_phanloai_vattu.php</a>";
        echo "</div>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Tên quyền</th><th>Mô tả</th></tr>";
        foreach ($allPerms as $p) {
            echo "<tr><td>{$p['id']}</td><td><code>" . htmlspecialchars($p['name']) . "</code></td>";
            echo "<td>" . htmlspecialchars($p['description']) . "</td></tr>";
        }
        echo "</table>";
    }
    
    // Gợi ý giải pháp
    echo "<h2>💡 Giải pháp</h2>";
    
    $hasVattuView = hasPermission('vattu.view');
    $hasPhanLoaiView = hasPermission('phanloai_vattu.view');
    
    if (!$hasVattuView && !$hasPhanLoaiView) {
        echo "<div class='info' style='background: #fef3c7; border-color: orange;'>";
        echo "<strong>⚠️ User chưa có quyền truy cập!</strong><br><br>";
        echo "Cần thực hiện một trong các cách sau:<br>";
        echo "<ol>";
        echo "<li>Chạy <a href='setup_phanloai_vattu.php'><strong>setup_phanloai_vattu.php</strong></a> để tự động cấp quyền cho admin</li>";
        echo "<li>Hoặc vào <a href='views/admin/permissions_manager.php'>Quản lý quyền Role</a> và tick chọn quyền 'Phân loại vật tư'</li>";
        echo "<li>Hoặc chạy SQL thủ công để cấp quyền trực tiếp</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<h3>SQL để cấp quyền thủ công:</h3>";
        echo "<pre style='background: #f3f4f6; padding: 10px; border-radius: 5px;'>";
        echo "-- Cấp quyền trực tiếp cho user ID $userId\n";
        echo "INSERT INTO user_permissions (user_id, permission_id)\n";
        echo "SELECT $userId, id FROM permissions WHERE name LIKE 'phanloai_vattu.%'\n";
        echo "ON DUPLICATE KEY UPDATE user_id = VALUES(user_id);";
        echo "</pre>";
    } else {
        echo "<div class='info' style='background: #d1fae5; border-color: green;'>";
        echo "<strong>✅ User có quyền truy cập!</strong><br>";
        echo "Bạn có thể truy cập <a href='phanloaivattu.php'>phanloaivattu.php</a>";
        echo "</div>";
    }
    
    echo "<p style='margin-top: 30px;'>";
    echo "<a href='phanloaivattu.php' style='padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px;'>→ Thử truy cập Phân loại vật tư</a> ";
    echo "<a href='vattuthanhly.php' style='padding: 10px 20px; background: #059669; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;'>→ Vật tư thanh lý</a>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #fee2e2; padding: 10px; border-left: 4px solid red;'>";
    echo "<strong>Lỗi:</strong> " . htmlspecialchars($e->getMessage());
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</body></html>";
