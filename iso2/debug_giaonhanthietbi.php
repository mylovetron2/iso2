<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . '/config/constants.php';

echo "<h2>Debug Giao Nhận Thiết Bị Module</h2>";
echo "<hr>";

try {
    $db = getDBConnection(true);
    echo "✓ Database connection OK<br>";
    
    // Check table exists
    echo "<h3>1. Kiểm tra bảng giao_nhan_thietbi_iso</h3>";
    $stmt = $db->query("SHOW TABLES LIKE 'giao_nhan_thietbi_iso'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Bảng tồn tại<br>";
        
        // Show structure
        echo "<h4>Cấu trúc bảng:</h4>";
        $columns = $db->query("DESCRIBE giao_nhan_thietbi_iso")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
    } else {
        echo "✗ Bảng KHÔNG tồn tại<br>";
    }
    
    // Check thietbi_iso table
    echo "<h3>2. Kiểm tra bảng thietbi_iso</h3>";
    $stmt = $db->query("SHOW TABLES LIKE 'thietbi_iso'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Bảng tồn tại<br>";
        $columns = $db->query("DESCRIBE thietbi_iso")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h4>Columns:</h4><pre>";
        foreach ($columns as $col) {
            echo $col['Field'] . " (" . $col['Type'] . ")\n";
        }
        echo "</pre>";
        
        // Try to select with correct column names
        $count = $db->query("SELECT COUNT(*) FROM thietbi_iso")->fetchColumn();
        echo "Số lượng records: " . $count . "<br>";
        
        // Test SELECT with aliases
        echo "<h4>Test SELECT với aliases:</h4>";
        try {
            $testStmt = $db->query("SELECT stt as id, tenvt as ten_thiet_bi, somay as ky_ma_hieu FROM thietbi_iso LIMIT 3");
            $samples = $testStmt->fetchAll(PDO::FETCH_ASSOC);
            echo "✓ Query với aliases thành công<br>";
            echo "<pre>";
            print_r($samples);
            echo "</pre>";
        } catch (Exception $e) {
            echo "✗ Query với aliases LỖI: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "✗ Bảng thietbi_iso KHÔNG tồn tại<br>";
    }
    
    // Check donvi_iso table
    echo "<h3>3. Kiểm tra bảng donvi_iso</h3>";
    $stmt = $db->query("SHOW TABLES LIKE 'donvi_iso'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Bảng tồn tại<br>";
        $columns = $db->query("DESCRIBE donvi_iso")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h4>Columns:</h4><pre>";
        foreach ($columns as $col) {
            echo $col['Field'] . " (" . $col['Type'] . ")\n";
        }
        echo "</pre>";
        
        $count = $db->query("SELECT COUNT(*) FROM donvi_iso")->fetchColumn();
        echo "Số lượng records: " . $count . "<br>";
    } else {
        echo "✗ Bảng donvi_iso KHÔNG tồn tại<br>";
    }
    
    // Test main query from index()
    echo "<h3>4. Test query chính từ index()</h3>";
    $sql = "SELECT 
                gn.*,
                tb.tenvt as ten_thietbi,
                tb.somay as ky_ma_hieu,
                dv_giao.tendv as ten_donvi_giao,
                dv_nhan.tendv as ten_donvi_nhan
            FROM giao_nhan_thietbi_iso gn
            LEFT JOIN thietbi_iso tb ON gn.thietbi_id = tb.stt
            LEFT JOIN donvi_iso dv_giao ON gn.donvi_giao = dv_giao.madv
            LEFT JOIN donvi_iso dv_nhan ON gn.donvi_nhan = dv_nhan.madv
            WHERE 1=1
            ORDER BY gn.ngay_giao DESC, gn.created_at DESC";
    
    echo "Query:<br><pre>" . htmlspecialchars($sql) . "</pre>";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✓ Query thành công<br>";
        echo "Số records: " . count($records) . "<br>";
        
        if (count($records) > 0) {
            echo "<h4>Record đầu tiên:</h4><pre>";
            print_r($records[0]);
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "✗ Query LỖI:<br>";
        echo "<pre style='color:red'>" . $e->getMessage() . "</pre>";
    }
    
    // Test donvi query
    echo "<h3>5. Test query donvi cho filter</h3>";
    try {
        $stmtDonVi = $db->query("SELECT madv as ma_don_vi, tendv as ten_don_vi FROM donvi_iso ORDER BY tendv");
        $donviList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);
        echo "✓ Query donvi thành công<br>";
        echo "Số đơn vị: " . count($donviList) . "<br>";
        
        if (count($donviList) > 0) {
            echo "<h4>Đơn vị đầu tiên:</h4><pre>";
            print_r($donviList[0]);
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "✗ Query donvi LỖI:<br>";
        echo "<pre style='color:red'>" . $e->getMessage() . "</pre>";
    }
    
    // Check permissions
    echo "<h3>6. Kiểm tra permissions</h3>";
    if (isset($_SESSION['user_id'])) {
        echo "User ID: " . $_SESSION['user_id'] . "<br>";
        
        // Check roles
        $stmt = $db->prepare("SELECT r.* FROM roles r
                             INNER JOIN role_user ru ON r.id = ru.role_id
                             WHERE ru.user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>User roles:</h4><pre>";
        foreach ($roles as $role) {
            echo "Role: " . $role['name'] . "\n";
            echo "Permissions: " . $role['permissions'] . "\n\n";
            
            if (strpos($role['permissions'], 'giaonhanthietbi') !== false) {
                echo "✓ CÓ quyền giaonhanthietbi\n";
            } else {
                echo "✗ KHÔNG có quyền giaonhanthietbi\n";
            }
        }
        echo "</pre>";
    } else {
        echo "✗ Chưa đăng nhập<br>";
    }
    
    // Try to load controller
    echo "<h3>7. Test load Controller</h3>";
    try {
        require_once __DIR__ . '/controllers/GiaoNhanThietBiController.php';
        echo "✓ Controller file tồn tại<br>";
        
        $controller = new GiaoNhanThietBiController($db);
        echo "✓ Controller khởi tạo thành công<br>";
    } catch (Exception $e) {
        echo "✗ Lỗi load controller:<br>";
        echo "<pre style='color:red'>" . $e->getMessage() . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color:red'>LỖI CHÍNH:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
