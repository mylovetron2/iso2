<?php
/**
 * CHECK USER PERMISSIONS - Kiểm tra quyền người dùng
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Kiểm tra Permissions</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        .test { margin: 20px 0; padding: 20px; border-radius: 5px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; max-height: 400px; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
        .check { color: #28a745; font-weight: bold; font-size: 18px; }
        .cross { color: #dc3545; font-weight: bold; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; font-weight: bold; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #333; }
        .perm-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
        .perm-item { padding: 8px; background: #f8f9fa; border-radius: 4px; font-size: 13px; }
        .perm-item.has { background: #d4edda; border-left: 3px solid #28a745; }
        .perm-item.missing { background: #fff3cd; border-left: 3px solid #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 KIỂM TRA PERMISSIONS</h1>
        <p><strong>Thời gian:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <?php
        // ============================================
        // 1. Check login status
        // ============================================
        echo '<h2>1. Trạng thái đăng nhập</h2>';
        
        if (!isset($_SESSION['user_id'])) {
            echo '<div class="test error">';
            echo '<span class="cross">❌</span> <strong>CHƯA ĐĂNG NHẬP</strong>';
            echo '<br><br><p>Bạn cần đăng nhập để kiểm tra permissions.</p>';
            echo '<a href="views/auth/login.php" class="btn">🔐 Đăng nhập ngay</a>';
            echo '</div>';
            exit;
        }
        
        echo '<div class="test success">';
        echo '<span class="check">✅</span> Đã đăng nhập';
        echo '<br><strong>User ID:</strong> ' . $_SESSION['user_id'];
        if (isset($_SESSION['username'])) {
            echo '<br><strong>Username:</strong> ' . $_SESSION['username'];
        }
        echo '</div>';
        
        $userId = $_SESSION['user_id'];
        
        // ============================================
        // 2. Connect to database
        // ============================================
        echo '<h2>2. Kết nối Database</h2>';
        
        try {
            require_once __DIR__ . '/config/database.php';
            $pdo = getDBConnection();
            
            echo '<div class="test success">';
            echo '<span class="check">✅</span> Database kết nối OK';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="test error">';
            echo '<span class="cross">❌</span> Lỗi database: ' . htmlspecialchars($e->getMessage());
            echo '</div>';
            exit;
        }
        
        // ============================================
        // 3. Get user's roles
        // ============================================
        echo '<h2>3. Roles của người dùng</h2>';
        
        try {
            $stmt = $pdo->prepare("
                SELECT r.id, r.name, r.permissions 
                FROM roles r
                INNER JOIN role_user ru ON r.id = ru.role_id
                WHERE ru.user_id = ?
            ");
            $stmt->execute([$userId]);
            $userRoles = $stmt->fetchAll();
            
            if (empty($userRoles)) {
                echo '<div class="test error">';
                echo '<span class="cross">❌</span> User không có role nào!';
                echo '<br><small>User cần được gán ít nhất 1 role</small>';
                echo '</div>';
            } else {
                echo '<div class="test success">';
                echo '<span class="check">✅</span> User có ' . count($userRoles) . ' role(s)';
                echo '</div>';
                
                foreach ($userRoles as $role) {
                    echo '<div class="test info">';
                    echo '<strong>Role:</strong> ' . htmlspecialchars($role['name']) . ' (ID: ' . $role['id'] . ')';
                    
                    $permissions = json_decode($role['permissions'] ?? '[]', true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($permissions)) {
                        echo '<br><strong>Số permissions:</strong> ' . count($permissions);
                    } else {
                        echo '<br><span style="color: red;">⚠ Permissions không phải JSON hợp lệ</span>';
                    }
                    echo '</div>';
                }
            }
            
        } catch (Exception $e) {
            echo '<div class="test error">';
            echo '<span class="cross">❌</span> Lỗi đọc roles: ' . htmlspecialchars($e->getMessage());
            echo '</div>';
            exit;
        }
        
        // ============================================
        // 4. Get all permissions
        // ============================================
        echo '<h2>4. Tất cả permissions của user</h2>';
        
        $allPermissions = [];
        foreach ($userRoles as $role) {
            $permissions = json_decode($role['permissions'] ?? '[]', true);
            if (is_array($permissions)) {
                $allPermissions = array_merge($allPermissions, $permissions);
            }
        }
        $allPermissions = array_unique($allPermissions);
        
        if (empty($allPermissions)) {
            echo '<div class="test warning">';
            echo '<span class="warning">⚠️</span> User không có permission nào!';
            echo '</div>';
        } else {
            echo '<div class="test success">';
            echo '<span class="check">✅</span> Tổng cộng: <strong>' . count($allPermissions) . ' permissions</strong>';
            echo '</div>';
            
            echo '<div class="test info">';
            echo '<strong>Danh sách permissions:</strong>';
            echo '<div class="perm-list" style="margin-top: 15px;">';
            sort($allPermissions);
            foreach ($allPermissions as $perm) {
                echo '<div class="perm-item has">' . htmlspecialchars($perm) . '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
        
        // ============================================
        // 5. Check specific giohang permissions
        // ============================================
        echo '<h2>5. Kiểm tra Giỏ hàng permissions</h2>';
        
        $requiredGiohangPerms = [
            'giohang.view' => 'Xem giỏ hàng',
            'giohang.add' => 'Thêm vào giỏ',
            'giohang.edit' => 'Sửa giỏ hàng',
            'giohang.delete' => 'Xóa khỏi giỏ',
        ];
        
        $hasGiohangPerms = [];
        $missingGiohangPerms = [];
        
        foreach ($requiredGiohangPerms as $perm => $desc) {
            if (in_array($perm, $allPermissions)) {
                $hasGiohangPerms[] = $perm;
            } else {
                $missingGiohangPerms[] = $perm;
            }
        }
        
        if (empty($missingGiohangPerms)) {
            echo '<div class="test success">';
            echo '<span class="check">✅</span> <strong>ĐÃ CÓ TẤT CẢ PERMISSIONS GIỎ HÀNG!</strong>';
            echo '<br><br><p>Bạn có thể sử dụng chức năng giỏ hàng.</p>';
            echo '<br><a href="giohang.php?action=index" class="btn btn-success">🛒 Vào Giỏ hàng</a>';
            echo '<a href="vattuthanhly.php" class="btn">📦 Vật tư thanh lý</a>';
            echo '</div>';
        } else {
            echo '<div class="test error">';
            echo '<span class="cross">❌</span> <strong>THIẾU ' . count($missingGiohangPerms) . ' PERMISSIONS!</strong>';
            echo '<br><br><p>User chưa có quyền truy cập Giỏ hàng.</p>';
            echo '</div>';
            
            echo '<div class="test info">';
            echo '<h4>Permissions đã có (' . count($hasGiohangPerms) . '):</h4>';
            if (!empty($hasGiohangPerms)) {
                echo '<div class="perm-list">';
                foreach ($hasGiohangPerms as $perm) {
                    echo '<div class="perm-item has">' . $perm . ' - ' . $requiredGiohangPerms[$perm] . '</div>';
                }
                echo '</div>';
            } else {
                echo '<p><em>Không có</em></p>';
            }
            
            echo '<h4 style="margin-top: 20px;">Permissions còn thiếu (' . count($missingGiohangPerms) . '):</h4>';
            echo '<div class="perm-list">';
            foreach ($missingGiohangPerms as $perm) {
                echo '<div class="perm-item missing">' . $perm . ' - ' . $requiredGiohangPerms[$perm] . '</div>';
            }
            echo '</div>';
            echo '</div>';
            
            // Show solution
            echo '<div class="test warning">';
            echo '<h3 style="margin-top: 0;">💡 GIẢI PHÁP</h3>';
            echo '<p><strong>Bước 1:</strong> Đảm bảo đã chạy SQL tạo bảng</p>';
            echo '<pre>File: setup_giohang_phieudathang.sql (phpMyAdmin)</pre>';
            
            echo '<p><strong>Bước 2:</strong> Chạy script grant permissions:</p>';
            echo '<a href="grant_giohang_phieudathang_permissions.php" class="btn btn-warning">⚙️ Grant Permissions Ngay</a>';
            
            echo '<p style="margin-top: 15px;"><strong>Bước 3:</strong> Sau khi grant xong, reload trang này để kiểm tra lại</p>';
            echo '<a href="check_user_permissions.php" class="btn">🔄 Reload trang này</a>';
            echo '</div>';
        }
        
        // ============================================
        // 6. Check phieudathang permissions
        // ============================================
        echo '<h2>6. Kiểm tra Phiếu đặt hàng permissions</h2>';
        
        $requiredPDHPerms = [
            'phieudathang.view' => 'Xem danh sách',
            'phieudathang.create' => 'Tạo phiếu mới',
            'phieudathang.edit' => 'Sửa phiếu',
            'phieudathang.delete' => 'Xóa phiếu',
            'phieudathang.approve' => 'Duyệt phiếu',
            'phieudathang.receive' => 'Nhận hàng',
            'phieudathang.stock' => 'Nhập kho',
            'phieudathang.cancel' => 'Hủy phiếu',
            'phieudathang.export' => 'Xuất Excel',
        ];
        
        $hasPDHPerms = [];
        $missingPDHPerms = [];
        
        foreach ($requiredPDHPerms as $perm => $desc) {
            if (in_array($perm, $allPermissions)) {
                $hasPDHPerms[] = $perm;
            } else {
                $missingPDHPerms[] = $perm;
            }
        }
        
        echo '<div class="test info">';
        echo '<strong>Có:</strong> ' . count($hasPDHPerms) . ' / ' . count($requiredPDHPerms);
        if (count($hasPDHPerms) > 0) {
            echo '<div class="perm-list" style="margin-top: 10px;">';
            foreach ($hasPDHPerms as $perm) {
                echo '<div class="perm-item has" style="font-size: 12px;">' . $perm . '</div>';
            }
            echo '</div>';
        }
        if (count($missingPDHPerms) > 0) {
            echo '<br><strong>Thiếu:</strong> ' . count($missingPDHPerms);
            echo '<div class="perm-list" style="margin-top: 10px;">';
            foreach ($missingPDHPerms as $perm) {
                echo '<div class="perm-item missing" style="font-size: 12px;">' . $perm . '</div>';
            }
            echo '</div>';
        }
        echo '</div>';
        
        // ============================================
        // SUMMARY
        // ============================================
        echo '<h2>📊 TÓM TẮT</h2>';
        
        $canUseGiohang = empty($missingGiohangPerms);
        $canUsePDH = empty($missingPDHPerms);
        
        if ($canUseGiohang && $canUsePDH) {
            echo '<div class="test success">';
            echo '<h3 style="margin-top:0;">🎉 HOÀN HẢO!</h3>';
            echo '<p>User đã có đầy đủ permissions cho cả Giỏ hàng và Phiếu đặt hàng.</p>';
            echo '<br>';
            echo '<a href="vattuthanhly.php" class="btn btn-success">📦 Trang Vật tư</a>';
            echo '<a href="giohang.php?action=index" class="btn">🛒 Giỏ hàng</a>';
            echo '<a href="phieudathang.php?action=index" class="btn">📋 Phiếu đặt hàng</a>';
            echo '</div>';
        } elseif (!$canUseGiohang || !$canUsePDH) {
            echo '<div class="test error">';
            echo '<h3 style="margin-top:0;">❌ CHƯA ĐỦ PERMISSIONS</h3>';
            echo '<p>User thiếu permissions:</p>';
            echo '<ul>';
            if (!$canUseGiohang) {
                echo '<li>Giỏ hàng: Thiếu ' . count($missingGiohangPerms) . ' permissions</li>';
            }
            if (!$canUsePDH) {
                echo '<li>Phiếu đặt hàng: Thiếu ' . count($missingPDHPerms) . ' permissions</li>';
            }
            echo '</ul>';
            echo '<br>';
            echo '<a href="grant_giohang_phieudathang_permissions.php" class="btn btn-warning">⚙️ Grant Permissions</a>';
            echo '</div>';
        }
        
        ?>
        
        <hr>
        <p style="text-align: center; color: #666;">
            <small>Check script: <code>check_user_permissions.php</code> | <?php echo date('Y-m-d H:i:s'); ?></small>
        </p>
    </div>
</body>
</html>
