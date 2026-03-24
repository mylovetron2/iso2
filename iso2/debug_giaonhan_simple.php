<?php
/**
 * DEBUG SIMPLE - Kiểm tra query và data
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
table { border-collapse: collapse; margin: 20px 0; background: white; }
td, th { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #4CAF50; color: white; }
pre { background: #f0f0f0; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔍 DEBUG GIAO NHAN THIET BI</h1>";

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<p class='success'>✅ Database connected</p>";
    
    // Test controller query
    echo "<h2>📊 TEST CONTROLLER QUERY</h2>";
    
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
            GROUP BY gn.id 
            ORDER BY gn.created_at DESC";
    
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    
    $stmt = $db->query($sql);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='success'>✅ Query thành công! Tìm thấy <strong>" . count($records) . "</strong> records</p>";
    
    if (empty($records)) {
        echo "<p class='error'>❌ Không có dữ liệu! Table rỗng hoặc filter lỗi.</p>";
    } else {
        echo "<h3>Data preview:</h3>";
        echo "<table>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Trạng thái</th>";
        echo "<th>Người giao</th>";
        echo "<th>Người nhận</th>";
        echo "<th>Số TB</th>";
        echo "<th>Đơn vị giao</th>";
        echo "<th>Đơn vị nhận</th>";
        echo "<th>Created</th>";
        echo "</tr>";
        
        foreach ($records as $rec) {
            echo "<tr>";
            echo "<td>{$rec['id']}</td>";
            echo "<td><strong>{$rec['trangthai']}</strong></td>";
            echo "<td>{$rec['nguoi_giao']}</td>";
            echo "<td>{$rec['nguoi_nhan']}</td>";
            echo "<td>{$rec['so_thietbi']}</td>";
            echo "<td>{$rec['ten_donvi_giao']}</td>";
            echo "<td>{$rec['ten_donvi_nhan']}</td>";
            echo "<td>{$rec['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show detailed first record
        echo "<h3>Chi tiết record đầu tiên:</h3>";
        echo "<pre>" . print_r($records[0], true) . "</pre>";
    }
    
    // Check view file
    echo "<h2>📁 CHECK VIEW FILES</h2>";
    $viewPath = __DIR__ . '/views/giaonhanthietbi/index.php';
    echo "<p>Path: <code>{$viewPath}</code></p>";
    
    if (file_exists($viewPath)) {
        echo "<p class='success'>✅ View file exists</p>";
        echo "<p>Size: " . filesize($viewPath) . " bytes</p>";
        echo "<p>Modified: " . date('Y-m-d H:i:s', filemtime($viewPath)) . "</p>";
        
        // Check first 10 lines
        $lines = file($viewPath);
        echo "<h3>First 10 lines of view:</h3>";
        echo "<pre>";
        for ($i = 0; $i < min(10, count($lines)); $i++) {
            echo htmlspecialchars($lines[$i]);
        }
        echo "</pre>";
    } else {
        echo "<p class='error'>❌ View file NOT FOUND!</p>";
    }
    
    // Test controller instantiation
    echo "<h2>🎮 TEST CONTROLLER</h2>";
    require_once __DIR__ . '/controllers/GiaoNhanThietBiController.php';
    
    $controller = new GiaoNhanThietBiController();
    echo "<p class='success'>✅ Controller instantiated</p>";
    
    // Test donvi query
    echo "<h2>🏢 TEST DONVI QUERY</h2>";
    $stmtDonVi = $db->query("SELECT madv, tendv FROM donvi_iso ORDER BY tendv LIMIT 5");
    $donviList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);
    echo "<p class='success'>✅ Found " . count($donviList) . " donvi</p>";
    echo "<pre>" . print_r($donviList, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><a href='giaonhanthietbi.php'>→ Go to actual page</a></p>";
echo "</body></html>";
