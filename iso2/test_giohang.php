<?php
/**
 * TEST GIOHANG.PHP - Simple Test
 * Không dùng exec() - an toàn cho hosting
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Giỏ Hàng</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        .test { margin: 20px 0; padding: 20px; border-radius: 5px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
        .check { color: #28a745; font-weight: bold; font-size: 18px; }
        .cross { color: #dc3545; font-weight: bold; font-size: 18px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
        .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; font-weight: bold; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-warning:hover { background: #e0a800; }
        .step { font-size: 20px; font-weight: bold; margin: 30px 0 15px 0; color: #555; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 TEST GIỎ HÀNG</h1>
        <p><strong>Thời gian:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <?php
        // ============================================
        // Step 1: Check required files
        // ============================================
        echo '<div class="step">Bước 1: Kiểm tra files cần thiết</div>';
        
        $requiredFiles = [
            'giohang.php' => 'Router giỏ hàng',
            'controllers/GioHangController.php' => 'Controller',
            'includes/auth_check.php' => 'Auth helper (mới tạo)',
            'includes/permission_check.php' => 'Permission helper (mới tạo)',
            'config/database.php' => 'Database config',
        ];
        
        $allFilesOK = true;
        foreach ($requiredFiles as $file => $desc) {
            $path = __DIR__ . '/' . $file;
            if (file_exists($path)) {
                echo '<div class="test success">';
                echo '<span class="check">✅</span> <strong>' . $file . '</strong> (' . $desc . ')';
                echo '<br><small>Size: ' . filesize($path) . ' bytes</small>';
                echo '</div>';
            } else {
                echo '<div class="test error">';
                echo '<span class="cross">❌</span> <strong>' . $file . '</strong> KHÔNG TỒN TẠI';
                echo '</div>';
                $allFilesOK = false;
            }
        }
        
        if (!$allFilesOK) {
            echo '<div class="test error"><h3>❌ THIẾU FILE</h3>';
            echo '<p>Không thể tiếp tục test. Vui lòng kiểm tra lại các file.</p>';
            echo '</div>';
            exit;
        }
        
        // ============================================
        // Step 2: Check database
        // ============================================
        echo '<div class="step">Bước 2: Kiểm tra Database</div>';
        
        try {
            require_once __DIR__ . '/config/database.php';
            
            // Project uses PDO, not mysqli
            if (function_exists('getDBConnection')) {
                $pdo = getDBConnection();
                
                if ($pdo instanceof PDO) {
                    echo '<div class="test success">';
                    echo '<span class="check">✅</span> Kết nối database OK (PDO)';
                    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
                    echo '<br><small>MySQL: ' . $version . '</small>';
                    echo '<br><small>Database: ' . DB_NAME . '</small>';
                    echo '</div>';
                    
                    // Check cart table
                    $stmt = $pdo->query("SHOW TABLES LIKE 'cart_vattu_thanh_ly'");
                    if ($stmt->rowCount() > 0) {
                        echo '<div class="test success">';
                        echo '<span class="check">✅</span> Bảng <code>cart_vattu_thanh_ly</code> đã tồn tại';
                        
                        // Count records
                        $countStmt = $pdo->query("SELECT COUNT(*) as total FROM cart_vattu_thanh_ly");
                        $count = $countStmt->fetchColumn();
                        echo '<br><small>Số records: ' . $count . '</small>';
                        echo '</div>';
                    } else {
                        echo '<div class="test warning">';
                        echo '<span class="warning">⚠️</span> Bảng <code>cart_vattu_thanh_ly</code> CHƯA TẠO';
                        echo '<br><strong>→ Cần chạy SQL:</strong> <code>setup_giohang_phieudathang.sql</code>';
                        echo '</div>';
                    }
                    
                    // Check permissions in roles
                    $stmt = $pdo->query("SELECT id, name, permissions FROM roles LIMIT 1");
                    $row = $stmt->fetch();
                    if ($row) {
                        echo '<div class="test success">';
                        echo '<span class="check">✅</span> Bảng <code>roles</code> OK';
                        
                        $perms = json_decode($row['permissions'] ?? '[]', true);
                        if ($perms && is_array($perms)) {
                            // Check if giohang permissions exist
                            $hasGiohang = false;
                            foreach ($perms as $perm) {
                                if (strpos($perm, 'giohang') !== false) {
                                    $hasGiohang = true;
                                    break;
                                }
                            }
                            
                            if ($hasGiohang) {
                                echo '<br><small style="color: green;">✓ Đã có permissions giỏ hàng</small>';
                            } else {
                                echo '<br><small style="color: orange;">⚠ Chưa có permissions giỏ hàng → Chạy grant script</small>';
                            }
                        }
                        echo '</div>';
                    }
                } else {
                    echo '<div class="test error">';
                    echo '<span class="cross">❌</span> getDBConnection() không trả về PDO';
                    echo '</div>';
                }
            } else {
                echo '<div class="test error">';
                echo '<span class="cross">❌</span> Function getDBConnection() không tồn tại';
                echo '<br><small>Kiểm tra file config/database.php</small>';
                echo '</div>';
            }
        } catch (PDOException $e) {
            echo '<div class="test error">';
            echo '<span class="cross">❌</span> Lỗi kết nối database: ' . htmlspecialchars($e->getMessage());
            echo '<br><small>Chạy <a href="test_database.php">test_database.php</a> để debug chi tiết</small>';
            echo '</div>';
        } catch (Exception $e) {
            echo '<div class="test error">';
            echo '<span class="cross">❌</span> Lỗi: ' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        
        // ============================================
        // Step 3: Test includes
        // ============================================
        echo '<div class="step">Bước 3: Test các include files</div>';
        
        try {
            require_once __DIR__ . '/includes/auth_check.php';
            echo '<div class="test success">';
            echo '<span class="check">✅</span> Load <code>auth_check.php</code> OK';
            
            // Check functions
            if (function_exists('requireLogin')) {
                echo '<br><small>✓ Function requireLogin() tồn tại</small>';
            }
            if (function_exists('getCurrentUserId')) {
                echo '<br><small>✓ Function getCurrentUserId() tồn tại</small>';
            }
            echo '</div>';
        } catch (Exception $e) {
            echo '<div class="test error">';
            echo '<span class="cross">❌</span> Lỗi load auth_check.php: ' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        
        try {
            require_once __DIR__ . '/includes/permission_check.php';
            echo '<div class="test success">';
            echo '<span class="check">✅</span> Load <code>permission_check.php</code> OK';
            
            // Check functions
            if (function_exists('checkPermission')) {
                echo '<br><small>✓ Function checkPermission() tồn tại</small>';
            }
            if (function_exists('hasAnyPermission')) {
                echo '<br><small>✓ Function hasAnyPermission() tồn tại</small>';
            }
            echo '</div>';
        } catch (Exception $e) {
            echo '<div class="test error">';
            echo '<span class="cross">❌</span> Lỗi load permission_check.php: ' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        
        // ============================================
        // Step 4: Test session
        // ============================================
        echo '<div class="step">Bước 4: Kiểm tra Session</div>';
        
        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (isset($_SESSION['user_id'])) {
            echo '<div class="test success">';
            echo '<span class="check">✅</span> Đã đăng nhập';
            echo '<br><small>User ID: ' . $_SESSION['user_id'] . '</small>';
            if (isset($_SESSION['username'])) {
                echo '<br><small>Username: ' . $_SESSION['username'] . '</small>';
            }
            echo '</div>';
        } else {
            echo '<div class="test warning">';
            echo '<span class="warning">⚠️</span> Chưa đăng nhập';
            echo '<br><small>Các chức năng giỏ hàng yêu cầu đăng nhập</small>';
            echo '</div>';
        }
        
        // ============================================
        // SUMMARY
        // ============================================
        echo '<div class="step">📊 KẾT LUẬN</div>';
        
        echo '<div class="test info">';
        echo '<h3 style="margin-top: 0;">✅ Setup cơ bản đã hoàn tất!</h3>';
        echo '<p>Các file cần thiết đã có. Giờ có thể test chức năng.</p>';
        echo '</div>';
        
        // ============================================
        // TEST LINKS
        // ============================================
        echo '<div class="step">🧪 Test các chức năng</div>';
        
        echo '<div class="test info">';
        echo '<h4>Chọn action muốn test:</h4>';
        
        if (isset($_SESSION['user_id'])) {
            // User is logged in
            echo '<p><a href="giohang.php?action=getCount" class="btn btn-success">1. Test getCount (đếm items)</a></p>';
            echo '<p><a href="giohang.php?action=index" class="btn btn-success">2. Xem Giỏ hàng</a></p>';
            echo '<p><a href="vattuthanhly.php" class="btn">3. Trang Vật tư thanh lý</a></p>';
            echo '<p><a href="phieudathang.php?action=index" class="btn">4. Danh sách Phiếu đặt hàng</a></p>';
        } else {
            // Not logged in
            echo '<p><strong>⚠️ Cần đăng nhập trước:</strong></p>';
            echo '<p><a href="views/auth/login.php" class="btn btn-warning">🔐 Đăng nhập</a></p>';
            echo '<hr>';
            echo '<p><small>Sau khi đăng nhập, quay lại đây và test lại:</small></p>';
            echo '<p><a href="test_giohang.php" class="btn">🔄 Refresh trang này</a></p>';
        }
        
        echo '</div>';
        
        // ============================================
        // NEXT STEPS
        // ============================================
        echo '<div class="step">📋 Các bước còn lại (nếu chưa làm)</div>';
        
        echo '<div class="test warning">';
        echo '<h4>1. Chạy SQL (nếu chưa có bảng cart_vattu_thanh_ly):</h4>';
        echo '<ul>';
        echo '<li>Mở phpMyAdmin</li>';
        echo '<li>Chọn database: <code>diavatly_db</code></li>';
        echo '<li>Tab SQL</li>';
        echo '<li>Copy file: <code>setup_giohang_phieudathang.sql</code></li>';
        echo '<li>Paste và click "Go"</li>';
        echo '</ul>';
        echo '<p><a href="setup_giohang_phieudathang.sql" class="btn" download>📥 Download SQL</a></p>';
        echo '</div>';
        
        echo '<div class="test warning">';
        echo '<h4>2. Grant permissions (nếu chưa có giohang permissions):</h4>';
        echo '<p><a href="grant_giohang_phieudathang_permissions.php" class="btn btn-warning">⚙️ Chạy Grant Script</a></p>';
        echo '</div>';
        
        ?>
        
        <hr>
        <p style="text-align: center; color: #666;">
            <small>Test script: <code>test_giohang.php</code> | <?php echo date('Y-m-d H:i:s'); ?></small>
        </p>
    </div>
</body>
</html>
