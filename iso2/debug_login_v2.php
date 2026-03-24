<?php
/**
 * DEBUG LOGIN ISSUE - Version 2 (No exec())
 * Fixed for hosting environment where exec() is disabled
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Debug Login - V2</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .test { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
        .check { font-weight: bold; color: #28a745; }
        .cross { font-weight: bold; color: #dc3545; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 KIỂM TRA LỖI LOGIN - V2</h1>
        <p><strong>Thời gian:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <?php
        $errors = [];
        $warnings = [];
        
        // ============================================
        // 1. Database Connection
        // ============================================
        echo '<h2>1. Kiểm tra Database</h2>';
        try {
            require_once __DIR__ . '/config/database.php';
            
            if (isset($conn) && $conn instanceof mysqli) {
                echo '<div class="test success"><span class="check">✅</span> Kết nối database OK</div>';
                echo '<div class="test info">Database: <code>' . $conn->db_name . '</code></div>';
            } else {
                echo '<div class="test error"><span class="cross">❌</span> Không tạo được kết nối</div>';
                $errors[] = 'Database connection failed';
            }
        } catch (Exception $e) {
            echo '<div class="test error"><span class="cross">❌</span> Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
            $errors[] = 'Database exception: ' . $e->getMessage();
        }
        
        // ============================================
        // 2. Check Required Tables
        // ============================================
        echo '<h2>2. Kiểm tra các bảng cần thiết</h2>';
        $required_tables = ['roles', 'users', 'role_user'];
        
        foreach ($required_tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                echo '<div class="test success"><span class="check">✅</span> Bảng <code>' . $table . '</code> tồn tại</div>';
            } else {
                echo '<div class="test error"><span class="cross">❌</span> Bảng <code>' . $table . '</code> KHÔNG tồn tại</div>';
                $errors[] = "Table $table not found";
            }
        }
        
        // ============================================
        // 3. Check permissions in roles table
        // ============================================
        echo '<h2>3. Kiểm tra permissions trong roles</h2>';
        $result = $conn->query("SELECT id, name, permissions FROM roles LIMIT 3");
        
        if ($result && $result->num_rows > 0) {
            echo '<div class="test success"><span class="check">✅</span> Đọc được dữ liệu roles</div>';
            
            while ($row = $result->fetch_assoc()) {
                echo '<div class="test info">';
                echo '<strong>Role:</strong> ' . htmlspecialchars($row['name']) . '<br>';
                
                // Decode permissions
                $perms = $row['permissions'];
                if (empty($perms)) {
                    echo '<strong>Permissions:</strong> <span class="warning">(trống)</span><br>';
                    $warnings[] = "Role '{$row['name']}' has no permissions";
                } else {
                    // Try to decode as JSON
                    $decoded = json_decode($perms, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        echo '<strong>Permissions:</strong> ' . count($decoded) . ' permissions<br>';
                        echo '<pre>' . htmlspecialchars(implode(', ', array_slice($decoded, 0, 5))) . 
                             (count($decoded) > 5 ? '...' : '') . '</pre>';
                        
                        // Check if giohang permissions exist
                        $hasGiohang = false;
                        foreach ($decoded as $perm) {
                            if (strpos($perm, 'giohang') !== false) {
                                $hasGiohang = true;
                                break;
                            }
                        }
                        
                        if ($hasGiohang) {
                            echo '<span style="color: green;">✓ Có permissions giỏ hàng</span><br>';
                        } else {
                            echo '<span style="color: orange;">⚠ Chưa có permissions giỏ hàng</span><br>';
                            $warnings[] = "Role '{$row['name']}' doesn't have giohang permissions yet";
                        }
                    } else {
                        echo '<strong>Permissions:</strong> (không phải JSON)<br>';
                        echo '<pre>' . htmlspecialchars(substr($perms, 0, 100)) . '...</pre>';
                    }
                }
                echo '</div>';
            }
        } else {
            echo '<div class="test error"><span class="cross">❌</span> Không đọc được roles</div>';
            $errors[] = 'Cannot read roles table';
        }
        
        // ============================================
        // 4. Check session and auth
        // ============================================
        echo '<h2>4. Kiểm tra Session & Auth</h2>';
        
        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (isset($_SESSION['user_id'])) {
            echo '<div class="test success"><span class="check">✅</span> Có phiên đăng nhập: User ID = ' . $_SESSION['user_id'] . '</div>';
        } else {
            echo '<div class="test warning"><span class="warning">⚠</span> Chưa đăng nhập (bình thường khi test)</div>';
        }
        
        // ============================================
        // 5. Test hasPermission() function
        // ============================================
        echo '<h2>5. Test hasPermission() Function</h2>';
        
        try {
            require_once __DIR__ . '/includes/permissions.php';
            echo '<div class="test success"><span class="check">✅</span> Load permissions.php OK</div>';
            
            if (function_exists('hasPermission')) {
                echo '<div class="test success"><span class="check">✅</span> Function hasPermission() tồn tại</div>';
                
                // Test với permission giohang.view
                try {
                    $result = hasPermission('giohang.view');
                    echo '<div class="test info">';
                    echo '<strong>Test:</strong> <code>hasPermission("giohang.view")</code><br>';
                    echo '<strong>Kết quả:</strong> ' . ($result ? 'TRUE' : 'FALSE') . '<br>';
                    if (!$result) {
                        echo '<em>(FALSE là bình thường vì chưa login hoặc chưa có permission)</em>';
                    }
                    echo '</div>';
                } catch (Exception $e) {
                    echo '<div class="test error"><span class="cross">❌</span> Lỗi khi gọi hasPermission(): ' . htmlspecialchars($e->getMessage()) . '</div>';
                    $errors[] = 'hasPermission() throws error: ' . $e->getMessage();
                }
            } else {
                echo '<div class="test error"><span class="cross">❌</span> Function hasPermission() KHÔNG tồn tại</div>';
                $errors[] = 'hasPermission() not found';
            }
        } catch (Exception $e) {
            echo '<div class="test error"><span class="cross">❌</span> Lỗi load permissions.php: ' . htmlspecialchars($e->getMessage()) . '</div>';
            $errors[] = 'permissions.php error: ' . $e->getMessage();
        }
        
        // ============================================
        // 6. Test loading header.php
        // ============================================
        echo '<h2>6. Test load Header.php</h2>';
        
        $headerPath = __DIR__ . '/views/layouts/header.php';
        if (file_exists($headerPath)) {
            echo '<div class="test success"><span class="check">✅</span> File header.php tồn tại</div>';
            
            $headerSize = filesize($headerPath);
            echo '<div class="test info">Kích thước: ' . $headerSize . ' bytes</div>';
            
            // Check if header contains giohang code
            $headerContent = file_get_contents($headerPath);
            if (strpos($headerContent, 'giohang.view') !== false) {
                echo '<div class="test warning"><span class="warning">⚠</span> Header có chứa code giỏ hàng (dòng ~157-172)</div>';
                
                // Try to find the specific line
                $lines = explode("\n", $headerContent);
                foreach ($lines as $num => $line) {
                    if (strpos($line, 'giohang.view') !== false) {
                        echo '<div class="test info">Dòng ' . ($num + 1) . ': <code>' . htmlspecialchars(trim($line)) . '</code></div>';
                        break;
                    }
                }
            } else {
                echo '<div class="test success"><span class="check">✅</span> Header KHÔNG có code giỏ hàng</div>';
            }
        } else {
            echo '<div class="test error"><span class="cross">❌</span> File header.php KHÔNG tồn tại</div>';
            $errors[] = 'header.php not found';
        }
        
        // ============================================
        // 7. Test loading login page
        // ============================================
        echo '<h2>7. Test trang Login</h2>';
        
        $loginPath = __DIR__ . '/views/auth/login.php';
        if (file_exists($loginPath)) {
            echo '<div class="test success"><span class="check">✅</span> File login.php tồn tại</div>';
            
            // Try to check if login.php uses header.php
            $loginContent = file_get_contents($loginPath);
            if (strpos($loginContent, 'header.php') !== false) {
                echo '<div class="test warning"><span class="warning">⚠</span> Login page có include header.php</div>';
                echo '<div class="test info"><em>→ Nếu header.php có lỗi sẽ ảnh hưởng login page</em></div>';
            } else {
                echo '<div class="test success"><span class="check">✅</span> Login page KHÔNG include header.php</div>';
            }
        } else {
            echo '<div class="test error"><span class="cross">❌</span> File login.php KHÔNG tồn tại</div>';
            $errors[] = 'login.php not found';
        }
        
        // ============================================
        // 8. Check PHP error log
        // ============================================
        echo '<h2>8. Kiểm tra PHP Error Log</h2>';
        
        $possibleLogs = [
            __DIR__ . '/php_error.log',
            __DIR__ . '/error.log',
            __DIR__ . '/logs/error.log',
            ini_get('error_log')
        ];
        
        $logFound = false;
        foreach ($possibleLogs as $logFile) {
            if ($logFile && file_exists($logFile)) {
                $logFound = true;
                echo '<div class="test success"><span class="check">✅</span> Tìm thấy log: <code>' . $logFile . '</code></div>';
                
                // Read last 20 lines
                $lines = file($logFile);
                if ($lines) {
                    $recent = array_slice($lines, -20);
                    echo '<div class="test info">';
                    echo '<strong>20 dòng cuối:</strong><br>';
                    echo '<pre>' . htmlspecialchars(implode('', $recent)) . '</pre>';
                    echo '</div>';
                }
                break;
            }
        }
        
        if (!$logFound) {
            echo '<div class="test warning"><span class="warning">⚠</span> Không tìm thấy PHP error log</div>';
            echo '<div class="test info">Thử kiểm tra tại: <code>php.ini</code> → <code>error_log</code> setting</div>';
        }
        
        // ============================================
        // SUMMARY
        // ============================================
        echo '<h2>📊 TÓM TẮT</h2>';
        
        if (empty($errors)) {
            echo '<div class="test success">';
            echo '<h3 style="margin-top:0;">✅ KHÔNG CÓ LỖI NGHIÊM TRỌNG</h3>';
            echo '<p>Tất cả các component cơ bản đều hoạt động bình thường.</p>';
            
            if (!empty($warnings)) {
                echo '<p><strong>Có ' . count($warnings) . ' cảnh báo:</strong></p>';
                echo '<ul>';
                foreach ($warnings as $warning) {
                    echo '<li>' . htmlspecialchars($warning) . '</li>';
                }
                echo '</ul>';
                
                // Check if main warning is missing giohang permissions
                $needsPermissions = false;
                foreach ($warnings as $warning) {
                    if (strpos($warning, "doesn't have giohang permissions") !== false) {
                        $needsPermissions = true;
                        break;
                    }
                }
                
                if ($needsPermissions) {
                    echo '<div style="background: #fff3cd; padding: 15px; margin-top: 15px; border-radius: 5px;">';
                    echo '<h4 style="margin-top: 0;">💡 GIẢI PHÁP:</h4>';
                    echo '<ol>';
                    echo '<li>Chạy SQL: <code>setup_giohang_phieudathang.sql</code></li>';
                    echo '<li>Chạy PHP: <code>grant_giohang_phieudathang_permissions.php</code></li>';
                    echo '<li>Thử login lại</li>';
                    echo '</ol>';
                    echo '<a href="grant_giohang_phieudathang_permissions.php" class="btn">▶ Grant Permissions Ngay</a>';
                    echo '</div>';
                }
            } else {
                echo '<p><strong>✓ Không có cảnh báo</strong></p>';
                echo '<p>Hệ thống sẵn sàng! Bạn có thể thử:</p>';
                echo '<a href="views/auth/login.php" class="btn">🔐 Thử Login</a>';
                echo '<a href="index.php" class="btn">🏠 Về trang chủ</a>';
            }
            echo '</div>';
        } else {
            echo '<div class="test error">';
            echo '<h3 style="margin-top:0;">❌ CÓ ' . count($errors) . ' LỖI</h3>';
            echo '<ul>';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul>';
            echo '<p><strong>Cần khắc phục các lỗi trên trước khi tiếp tục.</strong></p>';
            echo '</div>';
        }
        
        // ============================================
        // ACTIONS
        // ============================================
        echo '<h2>🔧 HÀNH ĐỘNG TIẾP THEO</h2>';
        echo '<div class="test info">';
        echo '<a href="views/auth/login.php" class="btn">🔐 Thử Login</a>';
        echo '<a href="setup_giohang_phieudathang.sql" class="btn" download>📥 Download SQL</a>';
        echo '<a href="grant_giohang_phieudathang_permissions.php" class="btn">⚙️ Grant Permissions</a>';
        echo '<a href="fix_login_issue.html" class="btn">📖 Xem hướng dẫn</a>';
        echo '</div>';
        
        ?>
        
        <hr>
        <p style="text-align: center; color: #666;">
            <small>Debug script: <code>debug_login_v2.php</code> | 
            Version 2.0 (No exec) | 
            <?php echo date('Y-m-d H:i:s'); ?></small>
        </p>
    </div>
</body>
</html>
