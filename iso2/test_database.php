<?php
/**
 * TEST DATABASE CONNECTION
 * Kiểm tra kết nối database chi tiết
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Database</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        .test { margin: 20px 0; padding: 20px; border-radius: 5px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
        .check { color: #28a745; font-weight: bold; font-size: 18px; }
        .cross { color: #dc3545; font-weight: bold; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 TEST DATABASE CONNECTION</h1>
        <p><strong>Thời gian:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <?php
        // ============================================
        // 1. Check config file
        // ============================================
        echo '<h2>1. Kiểm tra config file</h2>';
        
        $configPath = __DIR__ . '/config/database.php';
        if (file_exists($configPath)) {
            echo '<div class="test success">';
            echo '<span class="check">✅</span> File <code>config/database.php</code> tồn tại';
            echo '<br><small>Size: ' . filesize($configPath) . ' bytes</small>';
            echo '</div>';
        } else {
            echo '<div class="test error">';
            echo '<span class="cross">❌</span> File <code>config/database.php</code> KHÔNG tồn tại';
            echo '</div>';
            exit;
        }
        
        // ============================================
        // 2. Load config and show settings
        // ============================================
        echo '<h2>2. Load config và kiểm tra settings</h2>';
        
        try {
            require_once $configPath;
            
            echo '<div class="test info">';
            echo '<strong>Database Settings:</strong>';
            echo '<table>';
            echo '<tr><th>Setting</th><th>Value</th></tr>';
            echo '<tr><td>DB_HOST</td><td>' . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . '</td></tr>';
            echo '<tr><td>DB_USER</td><td>' . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . '</td></tr>';
            echo '<tr><td>DB_PASS</td><td>' . (defined('DB_PASS') ? '***' . substr(DB_PASS, -3) : 'NOT DEFINED') . '</td></tr>';
            echo '<tr><td>DB_NAME</td><td>' . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . '</td></tr>';
            echo '<tr><td>DB_PORT</td><td>' . (defined('DB_PORT') ? DB_PORT : 'NOT DEFINED') . '</td></tr>';
            echo '<tr><td>DB_CHARSET</td><td>' . (defined('DB_CHARSET') ? DB_CHARSET : 'NOT DEFINED') . '</td></tr>';
            echo '</table>';
            echo '</div>';
            
            // Check if all required constants are defined
            if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_NAME')) {
                echo '<div class="test error">';
                echo '<span class="cross">❌</span> Thiếu constants cần thiết';
                echo '</div>';
                exit;
            }
            
            echo '<div class="test success">';
            echo '<span class="check">✅</span> Config đã load, tất cả constants OK';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="test error">';
            echo '<span class="cross">❌</span> Lỗi load config: ' . htmlspecialchars($e->getMessage());
            echo '</div>';
            exit;
        }
        
        // ============================================
        // 3. Test PDO connection
        // ============================================
        echo '<h2>3. Test PDO Connection</h2>';
        
        try {
            // Check if function exists
            if (function_exists('getDBConnection')) {
                echo '<div class="test success">';
                echo '<span class="check">✅</span> Function <code>getDBConnection()</code> tồn tại';
                echo '</div>';
                
                // Try to connect
                echo '<div class="test info">Đang thử kết nối...</div>';
                
                $pdo = getDBConnection(true);
                
                if ($pdo instanceof PDO) {
                    echo '<div class="test success">';
                    echo '<span class="check">✅</span> <strong>KẾT NỐI PDO THÀNH CÔNG!</strong>';
                    echo '<br><small>Connection type: PDO</small>';
                    echo '</div>';
                    
                    // Get server info
                    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
                    echo '<div class="test info">';
                    echo '<strong>MySQL Version:</strong> ' . $version;
                    echo '</div>';
                    
                } else {
                    echo '<div class="test error">';
                    echo '<span class="cross">❌</span> getDBConnection() không trả về PDO object';
                    echo '</div>';
                }
                
            } else {
                echo '<div class="test error">';
                echo '<span class="cross">❌</span> Function <code>getDBConnection()</code> KHÔNG tồn tại';
                echo '</div>';
            }
            
        } catch (PDOException $e) {
            echo '<div class="test error">';
            echo '<span class="cross">❌</span> <strong>LỖI KẾT NỐI PDO</strong>';
            echo '<br><strong>Message:</strong> ' . htmlspecialchars($e->getMessage());
            echo '<br><strong>Code:</strong> ' . $e->getCode();
            echo '</div>';
            
            // Common error hints
            echo '<div class="test warning">';
            echo '<h4>💡 Gợi ý khắc phục:</h4>';
            echo '<ul>';
            
            if (strpos($e->getMessage(), 'Access denied') !== false) {
                echo '<li>❌ <strong>Sai username/password</strong> - Kiểm tra DB_USER và DB_PASS</li>';
                echo '<li>Kiểm tra user có tồn tại trong MySQL không</li>';
                echo '<li>Kiểm tra user có quyền truy cập database không</li>';
            } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
                echo '<li>❌ <strong>Database không tồn tại</strong> - Kiểm tra DB_NAME</li>';
                echo '<li>Tạo database: <code>CREATE DATABASE ' . DB_NAME . ';</code></li>';
            } elseif (strpos($e->getMessage(), 'Connection refused') !== false || strpos($e->getMessage(), "Can't connect") !== false) {
                echo '<li>❌ <strong>Không kết nối được MySQL server</strong></li>';
                echo '<li>Kiểm tra MySQL service đang chạy không</li>';
                echo '<li>Kiểm tra DB_HOST và DB_PORT đúng không</li>';
                echo '<li>Kiểm tra firewall có block không</li>';
            } else {
                echo '<li>Xem error message phía trên để biết chi tiết</li>';
            }
            
            echo '</ul>';
            echo '</div>';
            
            exit;
        } catch (Exception $e) {
            echo '<div class="test error">';
            echo '<span class="cross">❌</span> Lỗi: ' . htmlspecialchars($e->getMessage());
            echo '</div>';
            exit;
        }
        
        // ============================================
        // 4. Check required tables
        // ============================================
        echo '<h2>4. Kiểm tra các bảng cần thiết</h2>';
        
        $requiredTables = [
            'users' => 'Bảng người dùng',
            'roles' => 'Bảng roles',
            'role_user' => 'Bảng liên kết user-role',
            'vattu_thanh_ly_iso' => 'Bảng vật tư thanh lý',
            'cart_vattu_thanh_ly' => 'Bảng giỏ hàng (MỚI)',
            'phieu_dat_hang' => 'Bảng phiếu đặt hàng (MỚI)',
        ];
        
        $tableCount = 0;
        $missingTables = [];
        
        foreach ($requiredTables as $table => $desc) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo '<div class="test success">';
                echo '<span class="check">✅</span> Bảng <code>' . $table . '</code> tồn tại - ' . $desc;
                
                // Count records
                try {
                    $countStmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
                    $count = $countStmt->fetchColumn();
                    echo '<br><small>Số records: ' . $count . '</small>';
                } catch (Exception $e) {
                    // Can't count, skip
                }
                
                echo '</div>';
                $tableCount++;
            } else {
                echo '<div class="test warning">';
                echo '<span class="warning">⚠️</span> Bảng <code>' . $table . '</code> CHƯA TẠO - ' . $desc;
                echo '</div>';
                $missingTables[] = $table;
            }
        }
        
        if (count($missingTables) > 0) {
            echo '<div class="test warning">';
            echo '<h4>⚠️ Thiếu ' . count($missingTables) . ' bảng:</h4>';
            echo '<ul>';
            foreach ($missingTables as $table) {
                echo '<li><code>' . $table . '</code></li>';
            }
            echo '</ul>';
            
            // Check if missing cart tables
            if (in_array('cart_vattu_thanh_ly', $missingTables) || in_array('phieu_dat_hang', $missingTables)) {
                echo '<p><strong>→ Cần chạy SQL:</strong> <code>setup_giohang_phieudathang.sql</code></p>';
            }
            echo '</div>';
        }
        
        // ============================================
        // 5. Check roles.permissions structure
        // ============================================
        echo '<h2>5. Kiểm tra cấu trúc permissions</h2>';
        
        if (!in_array('roles', $missingTables)) {
            try {
                $stmt = $pdo->query("SELECT id, name, permissions FROM roles LIMIT 2");
                $roles = $stmt->fetchAll();
                
                if (count($roles) > 0) {
                    echo '<div class="test success">';
                    echo '<span class="check">✅</span> Đọc được bảng roles';
                    echo '<br><small>Số roles: ' . count($roles) . '</small>';
                    echo '</div>';
                    
                    foreach ($roles as $role) {
                        echo '<div class="test info">';
                        echo '<strong>Role:</strong> ' . htmlspecialchars($role['name']);
                        
                        $perms = json_decode($role['permissions'] ?? '[]', true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($perms)) {
                            echo '<br><strong>Permissions:</strong> ' . count($perms) . ' items';
                            
                            // Check for giohang permissions
                            $hasGiohang = false;
                            foreach ($perms as $perm) {
                                if (strpos($perm, 'giohang') !== false) {
                                    $hasGiohang = true;
                                    break;
                                }
                            }
                            
                            if ($hasGiohang) {
                                echo '<br><span style="color: green;">✓ Đã có permissions giỏ hàng</span>';
                            } else {
                                echo '<br><span style="color: orange;">⚠ Chưa có permissions giỏ hàng</span>';
                            }
                            
                            // Show first 5 permissions
                            if (count($perms) > 0) {
                                echo '<br><small>VD: ' . htmlspecialchars(implode(', ', array_slice($perms, 0, 5)));
                                if (count($perms) > 5) echo '...';
                                echo '</small>';
                            }
                        } else {
                            echo '<br><span style="color: red;">⚠ Permissions không phải JSON hợp lệ</span>';
                        }
                        
                        echo '</div>';
                    }
                } else {
                    echo '<div class="test warning">';
                    echo '<span class="warning">⚠️</span> Bảng roles không có dữ liệu';
                    echo '</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="test error">';
                echo '<span class="cross">❌</span> Lỗi đọc roles: ' . htmlspecialchars($e->getMessage());
                echo '</div>';
            }
        }
        
        // ============================================
        // SUMMARY
        // ============================================
        echo '<h2>📊 TÓM TẮT</h2>';
        
        echo '<div class="test success">';
        echo '<h3 style="margin-top:0;">✅ DATABASE KẾT NỐI THÀNH CÔNG!</h3>';
        echo '<p><strong>Loại kết nối:</strong> PDO</p>';
        echo '<p><strong>Database:</strong> ' . DB_NAME . '</p>';
        echo '<p><strong>Số bảng đã có:</strong> ' . $tableCount . ' / ' . count($requiredTables) . '</p>';
        echo '</div>';
        
        if (count($missingTables) > 0) {
            echo '<div class="test warning">';
            echo '<h3>⚠️ CẦN LÀM TIẾP:</h3>';
            echo '<ol>';
            echo '<li>Chạy SQL: <code>setup_giohang_phieudathang.sql</code> (phpMyAdmin)</li>';
            echo '<li>Chạy PHP: <code>grant_giohang_phieudathang_permissions.php</code></li>';
            echo '<li>Test lại: <code>test_giohang.php</code></li>';
            echo '</ol>';
            echo '</div>';
        } else {
            echo '<div class="test success">';
            echo '<h3>🎉 TẤT CẢ BẢNG ĐÃ CÓ!</h3>';
            echo '<p>Có thể test giỏ hàng ngay:</p>';
            echo '<p><a href="test_giohang.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">▶ Test Giỏ Hàng</a></p>';
            echo '</div>';
        }
        
        ?>
        
        <hr>
        <p style="text-align: center; color: #666;">
            <small>Test script: <code>test_database.php</code> | <?php echo date('Y-m-d H:i:s'); ?></small>
        </p>
    </div>
</body>
</html>
