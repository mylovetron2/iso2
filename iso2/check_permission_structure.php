<?php
require_once 'config/database.php';

try {
    $db = getDBConnection();
    
    echo "<h2>✅ KIỂM TRA CẤU TRÚC PHÂN QUYỀN</h2>";
    echo "<hr>";
    
    // 1. Kiểm tra các bảng tồn tại
    echo "<h3>1. Các bảng trong database:</h3>";
    $tables = ['users', 'roles', 'role_user', 'permissions', 'role_permissions', 'user_permissions'];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo $exists ? "✅ " : "❌ ";
        echo "<strong>$table</strong><br>";
        
        if ($exists) {
            // Show column count
            $stmt = $db->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'");
            $colCount = $stmt->fetch()['cnt'];
            echo "&nbsp;&nbsp;&nbsp;&nbsp;→ $colCount columns<br>";
        }
    }
    
    echo "<hr>";
    
    // 2. Kiểm tra cấu trúc bảng roles
    echo "<h3>2. Cấu trúc bảng ROLES:</h3>";
    $stmt = $db->query("DESCRIBE roles");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    
    // 3. Kiểm tra có bảng permissions không
    $stmt = $db->query("SHOW TABLES LIKE 'permissions'");
    if ($stmt->rowCount() > 0) {
        echo "<h3>3. ✅ Bảng PERMISSIONS tồn tại - Cấu trúc:</h3>";
        $stmt = $db->query("DESCRIBE permissions");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Đếm số permissions hiện có
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM permissions");
        $count = $stmt->fetch()['cnt'];
        echo "<p><strong>Tổng số permissions:</strong> $count</p>";
        
        // Hiển thị một vài permissions mẫu
        if ($count > 0) {
            echo "<h4>Ví dụ permissions:</h4>";
            $stmt = $db->query("SELECT * FROM permissions LIMIT 10");
            $perms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<ul>";
            foreach ($perms as $p) {
                echo "<li><code>{$p['name']}</code> - {$p['description']}</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<h3>3. ❌ Bảng PERMISSIONS KHÔNG TỒN TẠI</h3>";
        echo "<p>→ Hệ thống chỉ dùng roles.permissions (JSON string)</p>";
    }
    
    echo "<hr>";
    
    // 4. Kiểm tra cách lưu quyền trong roles
    echo "<h3>4. Cách lưu quyền trong ROLES:</h3>";
    $stmt = $db->query("SELECT id, name, permissions FROM roles LIMIT 3");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($roles)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Permissions (first 200 chars)</th></tr>";
        foreach ($roles as $role) {
            $perms = substr($role['permissions'], 0, 200);
            echo "<tr>";
            echo "<td>{$role['id']}</td>";
            echo "<td><strong>{$role['name']}</strong></td>";
            echo "<td><code>$perms...</code></td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><em>→ Quyền lưu dạng: JSON string hoặc comma-separated</em></p>";
    }
    
    echo "<hr>";
    
    // 5. KẾT LUẬN
    echo "<h3>5. 🎯 KẾT LUẬN:</h3>";
    
    $stmt = $db->query("SHOW TABLES LIKE 'permissions'");
    if ($stmt->rowCount() > 0) {
        echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
        echo "<h4>✅ HỆ THỐNG DÙNG 2 CÁCH PHÂN QUYỀN:</h4>";
        echo "<ol>";
        echo "<li><strong>Cách cũ (Legacy):</strong> roles.permissions (JSON string)</li>";
        echo "<li><strong>Cách mới:</strong> Bảng permissions + user_permissions + role_permissions</li>";
        echo "</ol>";
        echo "<p><strong>→ SQL scripts giỏ hàng CẦN DÙNG bảng permissions</strong></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
        echo "<h4>⚠️ HỆ THỐNG CHỈ DÙNG ROLES.PERMISSIONS (JSON)</h4>";
        echo "<p>→ Cần tạo bảng permissions trước!</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
    echo "<h3>❌ LỖI:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
