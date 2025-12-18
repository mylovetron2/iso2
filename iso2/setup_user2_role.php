<?php
declare(strict_types=1);

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <title>Test User2 Role Creation</title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 800px; margin: 0 auto; }
        .success { background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .info { background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
<h2>Test User2 Role Creation</h2>
";

// Step 1: Test includes
echo "<div class='info'>Step 1: Testing file includes...</div>";
try {
    require_once __DIR__ . '/config/database.php';
    echo "<div class='success'>✅ Database config loaded</div>";
} catch (Exception $e) {
    die("<div class='error'>❌ Failed to load database config: " . $e->getMessage() . "</div>");
}

// Step 2: Test database connection
echo "<div class='info'>Step 2: Testing database connection...</div>";
try {
    $db = getDBConnection();
    echo "<div class='success'>✅ Database connected</div>";
} catch (Exception $e) {
    die("<div class='error'>❌ Failed to connect to database: " . $e->getMessage() . "</div>");
}

// Step 3: Check if roles table exists
echo "<div class='info'>Step 3: Checking roles table...</div>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'roles'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>✅ Roles table exists</div>";
    } else {
        die("<div class='error'>❌ Roles table does not exist</div>");
    }
} catch (Exception $e) {
    die("<div class='error'>❌ Error checking roles table: " . $e->getMessage() . "</div>");
}

// Step 4: Check existing roles
echo "<div class='info'>Step 4: Checking existing roles...</div>";
try {
    $stmt = $db->query("SELECT * FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<div class='success'>✅ Found " . count($roles) . " roles</div>";
    echo "<ul>";
    foreach ($roles as $role) {
        echo "<li>ID: {$role['id']}, Name: {$role['name']}</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    die("<div class='error'>❌ Error fetching roles: " . $e->getMessage() . "</div>");
}

// Step 5: Check if user2 role exists
echo "<div class='info'>Step 5: Checking if user2 role exists...</div>";
try {
    $stmt = $db->prepare("SELECT * FROM roles WHERE name = ?");
    $stmt->execute(['user2']);
    $user2Role = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user2Role) {
        echo "<div class='info'>✅ Role 'user2' already exists with ID: {$user2Role['id']}</div>";
    } else {
        echo "<div class='info'>⚠️ Role 'user2' does not exist. Will create it now...</div>";
        
        // Create user2 role
        $permissions = json_encode([
            'project.view',
            'tiendocongviec.view',
            'tiendocongviec.create',
            'tiendocongviec.edit',
            'phieubangiao.view',
            'phieubangiao.create',
            'thietbi.view',
            'donvi.view',
            'thietbihotro.view',
            'hososcbd.view',
            'hososcbd.create',
            'hososcbd.edit',
        ]);
        
        $stmt = $db->prepare("INSERT INTO roles (name, permissions) VALUES (?, ?)");
        if ($stmt->execute(['user2', $permissions])) {
            $newId = $db->lastInsertId();
            echo "<div class='success'>✅ Successfully created role 'user2' with ID: {$newId}</div>";
            echo "<p><strong>Permissions:</strong></p>";
            echo "<pre>" . print_r(json_decode($permissions, true), true) . "</pre>";
        } else {
            echo "<div class='error'>❌ Failed to create role 'user2'</div>";
        }
    }
} catch (Exception $e) {
    die("<div class='error'>❌ Error creating user2 role: " . $e->getMessage() . "</div>");
}

// Step 6: Show final status
echo "<div class='info'>Step 6: Final status - All roles in database:</div>";
try {
    $stmt = $db->query("SELECT * FROM roles ORDER BY id");
    $allRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>
            <tr style='background: #4CAF50; color: white;'>
                <th>ID</th>
                <th>Name</th>
                <th>Permissions Count</th>
            </tr>";
    
    foreach ($allRoles as $role) {
        $perms = json_decode($role['permissions'], true);
        $permCount = is_array($perms) ? count($perms) : 0;
        $highlight = ($role['name'] === 'user2') ? 'background: #ffffcc;' : '';
        
        echo "<tr style='$highlight'>
                <td>{$role['id']}</td>
                <td><strong>{$role['name']}</strong></td>
                <td>$permCount permissions</td>
              </tr>";
    }
    echo "</table>";
    
    echo "<div class='success'>✅ All done! Role 'user2' is ready to use.</div>";
    echo "<p><a href='admin_user_permissions.php' style='padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px;'>Go to User Permissions</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error showing final status: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
