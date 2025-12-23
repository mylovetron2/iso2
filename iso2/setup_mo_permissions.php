<?php
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    echo "Adding Mo permissions...\n\n";
    
    // Check if permissions table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'permissions'");
    if ($stmt->rowCount() == 0) {
        echo "❌ Table 'permissions' not found. Creating basic permissions manually.\n";
        echo "Please run this on your database:\n\n";
        echo file_get_contents(__DIR__ . '/migrations/add_mo_permissions.sql');
        exit(1);
    }
    
    // Add permissions
    $permissions = [
        ['mo.view', 'Xem danh sách mỏ'],
        ['mo.create', 'Tạo mới mỏ'],
        ['mo.edit', 'Sửa thông tin mỏ'],
        ['mo.delete', 'Xóa mỏ']
    ];
    
    foreach ($permissions as list($name, $desc)) {
        $stmt = $conn->prepare("INSERT INTO permissions (name, description) VALUES (?, ?) ON DUPLICATE KEY UPDATE description = ?");
        $stmt->execute([$name, $desc, $desc]);
        echo "✓ Added permission: $name\n";
    }
    
    // Grant to admin users (user_id = 1 or users with admin role)
    echo "\n\nGranting permissions to admin users...\n";
    
    // Find admin users
    $stmt = $conn->query("SELECT id, username FROM users WHERE role = 'admin' OR id = 1 LIMIT 10");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($admins)) {
        echo "⚠ No admin users found.\n";
    } else {
        foreach ($admins as $admin) {
            foreach ($permissions as list($name, $desc)) {
                $stmt = $conn->prepare("
                    INSERT IGNORE INTO user_permissions (user_id, permission_id)
                    SELECT ?, id FROM permissions WHERE name = ?
                ");
                $stmt->execute([$admin['id'], $name]);
            }
            echo "✓ Granted permissions to user: {$admin['username']} (ID: {$admin['id']})\n";
        }
    }
    
    echo "\n✅ Mo permissions added successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
