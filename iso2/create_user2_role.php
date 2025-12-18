<?php
declare(strict_types=1);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');

try {
    session_start();
    
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/includes/auth.php';
    
    requireAuth();
    
    require_once __DIR__ . '/models/User.php';
    
    $userModel = new User();
} catch (Exception $e) {
    die("Error during initialization: " . $e->getMessage() . "<br>File: " . $e->getFile() . "<br>Line: " . $e->getLine());
} catch (Error $e) {
    die("Fatal error during initialization: " . $e->getMessage() . "<br>File: " . $e->getFile() . "<br>Line: " . $e->getLine());
}

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Tạo Role User2</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border: 1px solid #bee5eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn-secondary {
            background: #2196F3;
        }
        .btn-secondary:hover {
            background: #0b7dda;
        }
    </style>
</head>
<body>
<div class='container'>";

echo "<h2>🔧 Tạo Role User2</h2>";

// Kiểm tra xem có role user2 chưa
$sql = "SELECT * FROM roles WHERE name = 'user2'";
$stmt = $userModel->query($sql);
$user2Role = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user2Role) {
    echo "<div class='info'>✅ Role 'user2' đã tồn tại với ID: {$user2Role['id']}</div>";
    echo "<p><strong>Permissions hiện tại:</strong> " . htmlspecialchars($user2Role['permissions']) . "</p>";
} else {
    echo "<div class='info'>📋 Role 'user2' chưa tồn tại. Tạo role mới...</div>";
    
    // Tạo permissions mặc định cho user2
    // User2 có quyền cơ bản, ít hơn admin nhưng có thể nhiều hơn user thường
    $permissions = json_encode([
        // View permissions
        'project.view',
        'tiendocongviec.view',
        'phieubangiao.view',
        'thietbi.view',
        'donvi.view',
        'thietbihotro.view',
        'hososcbd.view',
        
        // Create permissions
        'tiendocongviec.create',
        'phieubangiao.create',
        'hososcbd.create',
        
        // Edit permissions (limited)
        'tiendocongviec.edit',
        'hososcbd.edit',
    ]);
    
    $sql = "INSERT INTO roles (name, permissions) VALUES ('user2', ?)";
    $stmt = $userModel->db->prepare($sql);
    
    if ($stmt->execute([$permissions])) {
        $user2RoleId = $userModel->db->lastInsertId();
        echo "<div class='success'>✅ Đã tạo role 'user2' thành công với ID: {$user2RoleId}</div>";
        echo "<p><strong>Permissions:</strong></p>";
        echo "<ul>";
        $permList = json_decode($permissions, true);
        foreach ($permList as $perm) {
            echo "<li>$perm</li>";
        }
        echo "</ul>";
        
        // Lấy lại role vừa tạo
        $user2Role = ['id' => $user2RoleId, 'name' => 'user2', 'permissions' => $permissions];
    } else {
        echo "<div class='error'>❌ Không tạo được role 'user2'</div>";
    }
}

echo "<hr>";

// Hiển thị tất cả roles
echo "<h3>📊 Danh sách tất cả Roles</h3>";
$sql = "SELECT * FROM roles ORDER BY id";
$stmt = $userModel->query($sql);
$allRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($allRoles) > 0) {
    echo "<table>
            <tr>
                <th>ID</th>
                <th>Tên Role</th>
                <th>Permissions</th>
            </tr>";
    
    foreach ($allRoles as $role) {
        $perms = json_decode($role['permissions'], true);
        $permCount = is_array($perms) ? count($perms) : 0;
        $highlight = ($role['name'] === 'user2') ? 'background: #ffffcc;' : '';
        
        echo "<tr style='$highlight'>
                <td>{$role['id']}</td>
                <td><strong>{$role['name']}</strong></td>
                <td>$permCount quyền</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<div class='info'>Không có role nào trong hệ thống.</div>";
}

echo "<hr>";

// Hiển thị users có role user2
echo "<h3>👥 Users có role 'user2'</h3>";
if (isset($user2Role['id'])) {
    $sql = "SELECT u.stt, u.username, u.name, u.email 
            FROM users u 
            INNER JOIN role_user ru ON u.stt = ru.user_id 
            WHERE ru.role_id = ?";
    $stmt = $userModel->query($sql, [$user2Role['id']]);
    $user2Users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($user2Users) > 0) {
        echo "<table>
                <tr>
                    <th>STT</th>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Email</th>
                </tr>";
        
        foreach ($user2Users as $u) {
            echo "<tr>
                    <td>{$u['stt']}</td>
                    <td>{$u['username']}</td>
                    <td>" . htmlspecialchars($u['name'] ?? '') . "</td>
                    <td>" . htmlspecialchars($u['email'] ?? '') . "</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>Chưa có user nào được gán role 'user2'.</div>";
        echo "<p>💡 Bạn có thể gán role 'user2' cho users tại trang <strong>Phân quyền User</strong>.</p>";
    }
}

echo "<div style='margin-top: 30px;'>
        <a href='admin_user_permissions.php' class='btn'>👤 Phân quyền User</a>
        <a href='views/admin/permissions_manager.php' class='btn btn-secondary'>⚙️ Quản lý Permissions</a>
        <a href='index.php' class='btn btn-secondary'>🏠 Trang chủ</a>
      </div>";

echo "</div>
</body>
</html>";
?>
