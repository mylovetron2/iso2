<?php
// Debug script - Check for errors in giaonhanthietbi
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Giao Nhận Thiết Bị</h1>";

// 1. Check database connection
echo "<h2>1. Database Connection</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $db = getDBConnection();
    echo "✅ Database connected successfully!<br>";
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "<br>";
    exit;
}

// 2. Check if table exists
echo "<h2>2. Table Structure</h2>";
try {
    $stmt = $db->query("DESCRIBE giao_nhan_thietbi_iso");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Table giao_nhan_thietbi_iso exists!<br>";
    echo "<pre>";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "❌ Table error: " . $e->getMessage() . "<br>";
}

// 3. Check trangthai column
echo "<h2>3. Trangthai Column</h2>";
try {
    $stmt = $db->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'giao_nhan_thietbi_iso' AND COLUMN_NAME = 'trangthai'");
    $type = $stmt->fetchColumn();
    echo "✅ Trangthai type: " . $type . "<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// 4. Check if loai_giao_nhan still exists (should not!)
echo "<h2>4. Check Old Columns</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'giao_nhan_thietbi_iso' AND COLUMN_NAME IN ('loai_giao_nhan', 'phieu_giao_id')");
    $count = $stmt->fetchColumn();
    if ($count == 0) {
        echo "✅ Old columns removed successfully!<br>";
    } else {
        echo "⚠️ Old columns still exist: " . $count . " columns found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// 5. Check new columns
echo "<h2>5. Check New Columns</h2>";
try {
    $stmt = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'giao_nhan_thietbi_iso' AND COLUMN_NAME IN ('nguoi_gui_kiemdinh', 'donvi_gui_kiemdinh', 'ngay_gui_kiemdinh')");
    $newCols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (count($newCols) == 3) {
        echo "✅ New columns exist: " . implode(', ', $newCols) . "<br>";
    } else {
        echo "⚠️ Missing new columns! Found: " . implode(', ', $newCols) . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// 6. Test query from controller
echo "<h2>6. Test Controller Query</h2>";
try {
    $sql = "SELECT 
                gn.*,
                dv_giao.tendv as ten_donvi_giao,
                dv_nhan.tendv as ten_donvi_nhan,
                COUNT(ct.id) as so_thietbi
            FROM giao_nhan_thietbi_iso gn
            LEFT JOIN donvi_iso dv_giao ON gn.donvi_giao = dv_giao.madv
            LEFT JOIN donvi_iso dv_nhan ON gn.donvi_nhan = dv_nhan.madv
            LEFT JOIN giao_nhan_thietbi_chitiet ct ON gn.id = ct.phieu_id
            WHERE 1=1
            GROUP BY gn.id ORDER BY gn.created_at DESC
            LIMIT 5";
    
    $stmt = $db->query($sql);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Query executed successfully! Found " . count($records) . " records<br>";
    
    if (count($records) > 0) {
        echo "<pre>";
        print_r($records[0]);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "❌ Query error: " . $e->getMessage() . "<br>";
    echo "SQL: " . $sql . "<br>";
}

// 7. Check controller file
echo "<h2>7. Check Controller File</h2>";
if (file_exists(__DIR__ . '/controllers/GiaoNhanThietBiController.php')) {
    echo "✅ Controller file exists<br>";
    
    try {
        require_once __DIR__ . '/controllers/GiaoNhanThietBiController.php';
        $controller = new GiaoNhanThietBiController();
        echo "✅ Controller instantiated successfully!<br>";
    } catch (Exception $e) {
        echo "❌ Controller error: " . $e->getMessage() . "<br>";
        echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "❌ Controller file not found!<br>";
}

// 8. Check view file
echo "<h2>8. Check View Files</h2>";
$viewFiles = [
    'index.php',
    'view.php',
    'create.php',
    'edit_gui_kiemdinh.php',
    'edit_giao_lai.php'
];

foreach ($viewFiles as $file) {
    if (file_exists(__DIR__ . '/views/giaonhanthietbi/' . $file)) {
        $size = filesize(__DIR__ . '/views/giaonhanthietbi/' . $file);
        echo "✅ $file exists ($size bytes)<br>";
    } else {
        echo "❌ $file not found!<br>";
    }
}

echo "<h2>9. Summary</h2>";
echo "If all checks pass above, try accessing: <a href='/iso2/giaonhanthietbi.php'>giaonhanthietbi.php</a><br>";
echo "<br>Check PHP error log at: /var/log/php_errors.log or similar<br>";
