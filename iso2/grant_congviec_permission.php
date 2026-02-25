<?php
/**
 * Grant congviec_suachua permissions to specific users
 * File: grant_congviec_permission.php
 * 
 * Script tự động cấp quyền công việc sửa chữa cho users
 * URL: http://your-domain.com/iso2/grant_congviec_permission.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>";
echo "<html lang='vi'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Grant Công Việc Permissions</title>";
echo "<style>";
echo "body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 1200px; margin: 50px auto; padding: 20px; background: #f5f5f5; }";
echo ".container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }";
echo "h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }";
echo "h2 { color: #34495e; margin-top: 30px; }";
echo ".success { background: #d4edda; color: #155724; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0; border-radius: 4px; }";
echo ".error { background: #f8d7da; color: #721c24; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0; border-radius: 4px; }";
echo ".info { background: #d1ecf1; color: #0c5460; padding: 15px; border-left: 4px solid #17a2b8; margin: 10px 0; border-radius: 4px; }";
echo "table { width: 100%; border-collapse: collapse; margin: 15px 0; }";
echo "th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }";
echo "th { background: #3498db; color: white; font-weight: 600; }";
echo "tr:hover { background: #f5f5f5; }";
echo "code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }";
echo ".step { background: #e8f4f8; padding: 15px; margin: 15px 0; border-radius: 4px; border-left: 4px solid #3498db; }";
echo "form { margin: 20px 0; padding: 20px; background: #f9f9f9; border-radius: 4px; }";
echo "label { display: block; margin: 10px 0 5px; font-weight: 600; }";
echo "select, input[type='submit'] { padding: 8px; margin: 5px 0; }";
echo "input[type='submit'] { background: #3498db; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; }";
echo "input[type='submit']:hover { background: #2980b9; }";
echo ".checkbox-group { margin: 10px 0; }";
echo ".checkbox-group label { display: inline-block; margin-right: 20px; font-weight: normal; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";
echo "<h1>🔐 Cấp quyền Công Việc Sửa Chữa cho Users</h1>";

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $userId = (int)$_POST['user_id'];
        $permissions = $_POST['permissions'] ?? [];
        
        if (empty($userId)) {
            throw new Exception("Vui lòng chọn user");
        }
        
        if (empty($permissions)) {
            throw new Exception("Vui lòng chọn ít nhất 1 quyền");
        }
        
        echo "<div class='step'>";
        echo "<strong>🔧 Đang cấp quyền cho User ID: $userId</strong>";
        echo "</div>";
        
        $granted = 0;
        $skipped = 0;
        
        foreach ($permissions as $permName) {
            // Get permission ID
            $stmt = $pdo->prepare("SELECT id FROM permissions WHERE name = ?");
            $stmt->execute([$permName]);
            $permId = $stmt->fetchColumn();
            
            if (!$permId) {
                echo "<div class='error'>❌ Permission không tồn tại: $permName</div>";
                continue;
            }
            
            // Grant permission to user
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO user_permissions (user_id, permission_id) 
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE user_id = user_id
                ");
                $stmt->execute([$userId, $permId]);
                
                if ($stmt->rowCount() > 0) {
                    echo "<div class='success'>✅ Đã cấp quyền: <code>$permName</code></div>";
                    $granted++;
                } else {
                    echo "<div class='info'>ℹ️ User đã có quyền: <code>$permName</code></div>";
                    $skipped++;
                }
            } catch (PDOException $e) {
                echo "<div class='error'>❌ Lỗi khi cấp quyền $permName: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
        
        echo "<div class='step'>";
        echo "<strong>📊 Kết quả:</strong><br>";
        echo "- Đã cấp mới: $granted quyền<br>";
        echo "- Đã có sẵn: $skipped quyền";
        echo "</div>";
    }
    
    // Get all users
    $stmt = $pdo->query("
        SELECT stt, username, email, role 
        FROM users 
        ORDER BY username
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get available permissions
    $stmt = $pdo->query("
        SELECT id, name, description 
        FROM permissions 
        WHERE name LIKE 'congviec_suachua.%'
        ORDER BY name
    ");
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($permissions)) {
        echo "<div class='error'>❌ Chưa có permissions. Vui lòng chạy <code>execute_add_congviec_permissions.php</code> trước!</div>";
    } else {
        // Show form
        echo "<h2>📝 Cấp quyền cho User</h2>";
        echo "<form method='POST'>";
        
        echo "<label>Chọn User:</label>";
        echo "<select name='user_id' required style='width: 100%; max-width: 400px;'>";
        echo "<option value=''>-- Chọn user --</option>";
        foreach ($users as $user) {
            echo "<option value='" . htmlspecialchars($user['stt']) . "'>";
            echo htmlspecialchars($user['username']) . " (" . htmlspecialchars($user['email']) . ") - Role: " . htmlspecialchars($user['role']);
            echo "</option>";
        }
        echo "</select>";
        
        echo "<label style='margin-top: 20px;'>Chọn quyền:</label>";
        echo "<div class='checkbox-group'>";
        foreach ($permissions as $perm) {
            echo "<label>";
            echo "<input type='checkbox' name='permissions[]' value='" . htmlspecialchars($perm['name']) . "'> ";
            echo "<code>" . htmlspecialchars($perm['name']) . "</code> - " . htmlspecialchars($perm['description']);
            echo "</label><br>";
        }
        echo "</div>";
        
        echo "<input type='submit' value='🔐 Cấp quyền'>";
        echo "</form>";
    }
    
    // Show current user permissions
    echo "<h2>👤 Quyền hiện tại của Users</h2>";
    $stmt = $pdo->query("
        SELECT 
            u.stt,
            u.username,
            u.email,
            u.role,
            GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ') as permissions
        FROM users u
        LEFT JOIN user_permissions up ON u.stt = up.user_id
        LEFT JOIN permissions p ON up.permission_id = p.id AND p.name LIKE 'congviec_suachua.%'
        GROUP BY u.stt, u.username, u.email, u.role
        HAVING permissions IS NOT NULL
        ORDER BY u.username
    ");
    $userPerms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($userPerms)) {
        echo "<table>";
        echo "<tr><th>User ID</th><th>Username</th><th>Email</th><th>Role</th><th>Permissions</th></tr>";
        foreach ($userPerms as $up) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($up['stt']) . "</td>";
            echo "<td>" . htmlspecialchars($up['username']) . "</td>";
            echo "<td>" . htmlspecialchars($up['email']) . "</td>";
            echo "<td>" . htmlspecialchars($up['role']) . "</td>";
            echo "<td><small><code>" . htmlspecialchars($up['permissions']) . "</code></small></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ️ Chưa có user nào được cấp quyền trực tiếp</div>";
    }
    
    // Show role permissions
    echo "<h2>👥 Quyền từ Roles</h2>";
    $stmt = $pdo->query("
        SELECT 
            r.id,
            r.name as role_name,
            GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ') as permissions
        FROM roles r
        LEFT JOIN role_permissions rp ON r.id = rp.role_id
        LEFT JOIN permissions p ON rp.permission_id = p.id AND p.name LIKE 'congviec_suachua.%'
        GROUP BY r.id, r.name
        HAVING permissions IS NOT NULL
        ORDER BY r.name
    ");
    $rolePerms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($rolePerms)) {
        echo "<table>";
        echo "<tr><th>Role ID</th><th>Role Name</th><th>Permissions</th></tr>";
        foreach ($rolePerms as $rp) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($rp['id']) . "</td>";
            echo "<td>" . htmlspecialchars($rp['role_name']) . "</td>";
            echo "<td><small><code>" . htmlspecialchars($rp['permissions']) . "</code></small></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div class='info'>";
        echo "ℹ️ <strong>Lưu ý:</strong> Users được cấp quyền qua role sẽ tự động có các quyền tương ứng. ";
        echo "Không cần cấp quyền trực tiếp cho user nếu role đã có quyền.";
        echo "</div>";
    } else {
        echo "<div class='info'>ℹ️ Chưa có role nào được cấp quyền</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "</div>"; // container
echo "</body>";
echo "</html>";
