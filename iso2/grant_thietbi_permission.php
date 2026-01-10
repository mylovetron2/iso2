<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$db = getDBConnection();

echo "<h2>Cấp quyền Kế hoạch Kiểm định cho tất cả users</h2>";

try {
    // Lấy danh sách tất cả users
    $stmt = $db->query("SELECT stt, username, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Tìm thấy " . count($users) . " users</p>";
    
    // Lấy hoặc tạo role có permission kehoach_kiemdinh
    $stmt = $db->query("SELECT * FROM roles WHERE name = 'user2'");
    $user2Role = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user2Role) {
        // Tạo role user2 với các permission cần thiết
        $permissions = json_encode([
            'thietbi.view',
            'donvi.view',
            'thietbihotro.view',
            'kehoach_kiemdinh.view',
            'kehoach_kiemdinh.edit',
            'kehoach_kiemdinh.export'
        ]);
        
        $stmt = $db->prepare("INSERT INTO roles (name, permissions) VALUES (?, ?)");
        $stmt->execute(['user2', $permissions]);
        $user2RoleId = $db->lastInsertId();
        echo "<p>✅ Đã tạo role 'user2' với ID: {$user2RoleId}</p>";
    } else {
        $user2RoleId = $user2Role['id'];
        echo "<p>✅ Sử dụng role 'user2' có sẵn (ID: {$user2RoleId})</p>";
        
        // Cập nhật permissions cho role
        $perms = json_decode($user2Role['permissions'], true);
        $newPerms = ['kehoach_kiemdinh.view', 'kehoach_kiemdinh.edit', 'kehoach_kiemdinh.export', 'thietbi.view'];
        $updated = false;
        foreach ($newPerms as $perm) {
            if (!in_array($perm, $perms)) {
                $perms[] = $perm;
                $updated = true;
            }
        }
        if ($updated) {
            $stmt = $db->prepare("UPDATE roles SET permissions = ? WHERE id = ?");
            $stmt->execute([json_encode($perms), $user2RoleId]);
            echo "<p>✅ Đã thêm permissions vào role: " . implode(', ', $newPerms) . "</p>";
        }
    }
    
    // Gán role cho tất cả users
    echo "<h3>Gán role cho users:</h3>";
    foreach ($users as $user) {
        // Kiểm tra xem user đã có role này chưa
        $stmt = $db->prepare("SELECT * FROM role_user WHERE user_id = ? AND role_id = ?");
        $stmt->execute([$user['stt'], $user2RoleId]);
        $exists = $stmt->fetch();
        
        if (!$exists) {
            $stmt = $db->prepare("INSERT INTO role_user (user_id, role_id) VALUES (?, ?)");
            $stmt->execute([$user['stt'], $user2RoleId]);
            echo "<p>✅ Đã gán role cho user: {$user['username']} (ID: {$user['stt']})</p>";
        } else {
            echo "<p>ℹ️ User {$user['username']} đã có role này</p>";
        }
    }
    
    echo "<hr><h3>Kiểm tra kết quả:</h3>";
    $stmt = $db->query("
        SELECT u.stt, u.username, r.name as role_name, r.permissions 
        FROM users u 
        LEFT JOIN role_user ru ON u.stt = ru.user_id 
        LEFT JOIN roles r ON ru.role_id = r.id
        ORDER BY u.stt
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>User ID</th><th>Username</th><th>Role</th><th>Permissions</th></tr>";
    foreach ($results as $row) {
        $perms = $row['permissions'] ? json_decode($row['permissions'], true) : [];
        $hasKehoach = in_array('kehoach_kiemdinh.view', $perms) ? '✅' : '❌';
        $hasExport = in_array('kehoach_kiemdinh.export', $perms) ? '✅' : '❌';
        echo "<tr>";
        echo "<td>{$row['stt']}</td>";
        echo "<td>{$row['username']}</td>";
        echo "<td>{$row['role_name']}</td>";
        echo "<td>{$hasKehoach} View | {$hasExport} Export (" . count($perms) . " total)</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr><p style='color: green; font-weight: bold;'>✅ HOÀN THÀNH! Tất cả users đã có quyền Kế hoạch Kiểm định</p>";
    echo "<p><a href='kehoach_thietbi_2026.php'>Vào trang Kế hoạch KĐ 2026</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
?>
