<?php
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    echo "Checking roles table...\n\n";
    
    // Get all roles
    $stmt = $conn->query("SELECT id, name, permissions FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($roles)) {
        echo "❌ No roles found in database.\n";
        exit(1);
    }
    
    echo "Found " . count($roles) . " roles:\n";
    foreach ($roles as $role) {
        echo "- {$role['name']} (ID: {$role['id']})\n";
    }
    
    echo "\nAdding mo.* permissions to admin/manager roles...\n\n";
    
    $moPermissions = ['mo.view', 'mo.create', 'mo.edit', 'mo.delete'];
    
    foreach ($roles as $role) {
        // Parse existing permissions
        $permissions = json_decode($role['permissions'], true);
        if (!is_array($permissions)) {
            $permissions = [];
        }
        
        // Check if this is admin or manager role
        if (stripos($role['name'], 'admin') !== false || stripos($role['name'], 'manager') !== false || stripos($role['name'], 'quản lý') !== false) {
            
            // Add mo permissions if not exists
            $added = false;
            foreach ($moPermissions as $perm) {
                if (!in_array($perm, $permissions)) {
                    $permissions[] = $perm;
                    $added = true;
                }
            }
            
            if ($added) {
                // Update role permissions
                $newPermissions = json_encode($permissions, JSON_UNESCAPED_UNICODE);
                $stmt = $conn->prepare("UPDATE roles SET permissions = ? WHERE id = ?");
                $stmt->execute([$newPermissions, $role['id']]);
                
                echo "✓ Updated role '{$role['name']}' with mo permissions\n";
                echo "  New permissions: " . implode(', ', $permissions) . "\n\n";
            } else {
                echo "✓ Role '{$role['name']}' already has mo permissions\n\n";
            }
        }
    }
    
    echo "✅ Done!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
