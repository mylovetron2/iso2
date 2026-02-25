<?php
/**
 * Execute migration: Add congviec_suachua permissions
 * File: execute_add_congviec_permissions.php
 * 
 * Chạy file này để thêm quyền quản lý công việc sửa chữa
 * URL: http://your-domain.com/iso2/execute_add_congviec_permissions.php
 */

declare(strict_types=1);

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load database connection
require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>";
echo "<html lang='vi'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Execute Migration: Add Công Việc Permissions</title>";
echo "<style>";
echo "body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; background: #f5f5f5; }";
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
echo "</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";
echo "<h1>🔐 Migration: Add Công Việc Sửa Chữa Permissions</h1>";

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Read SQL file
    $sqlFile = __DIR__ . '/migrations/20260225_add_congviec_permissions.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    echo "<div class='info'><strong>📄 Đang thực thi migration:</strong> $sqlFile</div>";
    
    // Split SQL into statements (simple split by semicolon)
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            // Remove comments and empty statements
            $stmt = preg_replace('/--.*$/m', '', $stmt);
            $stmt = trim($stmt);
            return !empty($stmt) && stripos($stmt, 'SELECT') !== 0; // Skip SELECT statements for now
        }
    );
    
    echo "<div class='step'>";
    echo "<strong>🔧 Thực thi các lệnh SQL...</strong>";
    echo "</div>";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $index => $statement) {
        try {
            $pdo->exec($statement);
            $successCount++;
            
            // Determine what was executed
            if (stripos($statement, 'INSERT INTO permissions') !== false) {
                echo "<div class='success'>✅ Đã thêm permissions vào bảng</div>";
            } elseif (stripos($statement, 'INSERT INTO role_permissions') !== false) {
                echo "<div class='success'>✅ Đã cấp quyền cho roles</div>";
            } elseif (stripos($statement, 'INSERT INTO user_permissions') !== false) {
                echo "<div class='success'>✅ Đã cấp quyền cho users</div>";
            }
        } catch (PDOException $e) {
            $errorCount++;
            // Check if it's a duplicate key error (which is OK)
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "<div class='info'>ℹ️ Quyền đã tồn tại (bỏ qua)</div>";
            } else {
                echo "<div class='error'>❌ Lỗi: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
    
    echo "<h2>✅ Kết quả Migration</h2>";
    echo "<div class='step'>";
    echo "<strong>Thành công:</strong> $successCount lệnh<br>";
    echo "<strong>Lỗi:</strong> $errorCount lệnh";
    echo "</div>";
    
    // Show added permissions
    echo "<h2>📋 Permissions đã thêm</h2>";
    $stmt = $pdo->query("
        SELECT id, name, description, created_at 
        FROM permissions 
        WHERE name LIKE 'congviec_suachua.%'
        ORDER BY name
    ");
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($permissions)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Tên Permission</th><th>Mô tả</th><th>Ngày tạo</th></tr>";
        foreach ($permissions as $perm) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($perm['id']) . "</td>";
            echo "<td><code>" . htmlspecialchars($perm['name']) . "</code></td>";
            echo "<td>" . htmlspecialchars($perm['description']) . "</td>";
            echo "<td>" . htmlspecialchars($perm['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ Không tìm thấy permissions nào</div>";
    }
    
    // Show role permissions
    echo "<h2>👥 Role Permissions</h2>";
    $stmt = $pdo->query("
        SELECT rp.role_id, r.name as role_name, p.name as permission_name, p.description
        FROM role_permissions rp
        JOIN permissions p ON rp.permission_id = p.id
        LEFT JOIN roles r ON rp.role_id = r.id
        WHERE p.name LIKE 'congviec_suachua.%'
        ORDER BY rp.role_id, p.name
    ");
    $rolePerms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($rolePerms)) {
        echo "<table>";
        echo "<tr><th>Role ID</th><th>Role Name</th><th>Permission</th><th>Mô tả</th></tr>";
        foreach ($rolePerms as $rp) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($rp['role_id']) . "</td>";
            echo "<td>" . htmlspecialchars($rp['role_name'] ?? 'N/A') . "</td>";
            echo "<td><code>" . htmlspecialchars($rp['permission_name']) . "</code></td>";
            echo "<td>" . htmlspecialchars($rp['description']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ️ Chưa cấp quyền cho role nào</div>";
    }
    
    // Show user permissions
    echo "<h2>👤 User Permissions</h2>";
    $stmt = $pdo->query("
        SELECT up.user_id, u.username, p.name as permission_name, p.description
        FROM user_permissions up
        JOIN permissions p ON up.permission_id = p.id
        LEFT JOIN users u ON up.user_id = u.stt
        WHERE p.name LIKE 'congviec_suachua.%'
        ORDER BY up.user_id, p.name
    ");
    $userPerms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($userPerms)) {
        echo "<table>";
        echo "<tr><th>User ID</th><th>Username</th><th>Permission</th><th>Mô tả</th></tr>";
        foreach ($userPerms as $up) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($up['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($up['username'] ?? 'N/A') . "</td>";
            echo "<td><code>" . htmlspecialchars($up['permission_name']) . "</code></td>";
            echo "<td>" . htmlspecialchars($up['description']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ️ Chưa cấp quyền trực tiếp cho user nào</div>";
    }
    
    echo "<h2>🎉 Hoàn thành!</h2>";
    echo "<div class='success'>";
    echo "<strong>Migration đã được thực thi thành công!</strong><br><br>";
    echo "Các quyền sau đã được thêm:<br>";
    echo "<ul>";
    echo "<li><code>congviec_suachua.view</code> - Xem công việc sửa chữa</li>";
    echo "<li><code>congviec_suachua.create</code> - Tạo công việc sửa chữa</li>";
    echo "<li><code>congviec_suachua.edit</code> - Sửa công việc sửa chữa</li>";
    echo "<li><code>congviec_suachua.delete</code> - Xóa công việc sửa chữa</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<strong>📌 Bước tiếp theo:</strong><br>";
    echo "1. Kiểm tra quyền trong Admin → User Permissions<br>";
    echo "2. Cấp quyền cho users cụ thể nếu cần bằng script <code>grant_congviec_permission.php</code><br>";
    echo "3. Test các chức năng trong <code>/iso2/congviec_suachua.php</code><br>";
    echo "4. Đăng xuất và đăng nhập lại để load permissions mới";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<strong>Stack trace:</strong><br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</div>"; // container
echo "</body>";
echo "</html>";
