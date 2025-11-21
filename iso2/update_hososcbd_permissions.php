<?php
// Update roles to add hososcbd permissions
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config/database.php';

echo "<h2>Update roles with hososcbd permissions</h2>";

try {
    $db = getDBConnection(true);
    
    // Get all roles
    $stmt = $db->query("SELECT id, name, permissions FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current roles:</h3>";
    foreach ($roles as $role) {
        echo "<div style='margin: 10px 0; padding: 10px; background: #f0f0f0;'>";
        echo "<b>Role #{$role['id']}: {$role['name']}</b><br>";
        echo "Current permissions: " . htmlspecialchars($role['permissions']) . "<br>";
        
        // Decode permissions
        $perms = json_decode($role['permissions'], true) ?: [];
        
        // Add hososcbd permissions if not exists
        $hososcbdPerms = ['hososcbd.view', 'hososcbd.create', 'hososcbd.edit', 'hososcbd.delete'];
        $added = [];
        
        foreach ($hososcbdPerms as $perm) {
            if (!in_array($perm, $perms)) {
                $perms[] = $perm;
                $added[] = $perm;
            }
        }
        
        if (count($added) > 0) {
            // Update role with new permissions
            $newPerms = json_encode($perms);
            $updateSql = "UPDATE roles SET permissions = ? WHERE id = ?";
            $db->prepare($updateSql)->execute([$newPerms, $role['id']]);
            
            echo "<span style='color: green;'>✓ Added: " . implode(', ', $added) . "</span><br>";
            echo "New permissions: " . htmlspecialchars($newPerms) . "<br>";
        } else {
            echo "<span style='color: blue;'>ℹ All hososcbd permissions already exist</span><br>";
        }
        
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<div style='color: green; font-weight: bold; padding: 10px; background: #d4edda;'>✓ Completed! All roles updated with hososcbd permissions.</div>";
    
} catch (Throwable $e) {
    echo "<div style='color: red; padding: 10px; background: #fee;'>";
    echo "<b>Error:</b> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
