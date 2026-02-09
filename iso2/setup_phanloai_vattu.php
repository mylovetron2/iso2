<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection();
    
    echo "<!DOCTYPE html>";
    echo "<html lang='vi'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<title>Setup Phân loại Vật tư Thanh lý</title>";
    echo "<style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        h1 { color: #2563eb; border-bottom: 3px solid #2563eb; padding-bottom: 10px; }
        h2 { color: #059669; margin-top: 30px; }
        .success { color: green; background: #d1fae5; padding: 10px; border-left: 4px solid green; margin: 10px 0; }
        .error { color: red; background: #fee2e2; padding: 10px; border-left: 4px solid red; margin: 10px 0; }
        .info { color: #1f2937; background: #e5e7eb; padding: 10px; border-left: 4px solid #6b7280; margin: 10px 0; }
        pre { background: #f3f4f6; padding: 10px; border-radius: 5px; overflow-x: auto; }
        ul { line-height: 1.8; }
        .nav { margin: 20px 0; }
        .nav a { display: inline-block; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px; }
        .nav a:hover { background: #1d4ed8; }
    </style>";
    echo "</head>";
    echo "<body>";
    
    echo "<h1>🚀 Setup Phân loại Vật tư Thanh lý</h1>";
    
    // BƯỚC 1: Tạo bảng và cấu trúc database
    echo "<h2>📦 BƯỚC 1: Tạo bảng phân loại vật tư</h2>";
    
    $sqlFile1 = __DIR__ . '/add_phanloai_vattu_thanh_ly.sql';
    
    if (!file_exists($sqlFile1)) {
        throw new Exception("Không tìm thấy file SQL: $sqlFile1");
    }
    
    $sql1 = file_get_contents($sqlFile1);
    
    if ($sql1 === false) {
        throw new Exception("Không thể đọc file SQL");
    }
    
    $statements1 = array_filter(
        array_map('trim', explode(';', $sql1)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    $db->beginTransaction();
    
    foreach ($statements1 as $statement) {
        if (trim($statement)) {
            echo "<div class='info'>Đang thực thi: <pre>" . htmlspecialchars(substr($statement, 0, 150)) . "...</pre></div>";
            try {
                $db->exec($statement);
                echo "<div class='success'>✓ Thành công</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>⚠ " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
    
    $db->commit();
    
    echo "<div class='success'><strong>✓ Hoàn thành BƯỚC 1!</strong> Đã tạo bảng phân loại và cập nhật cấu trúc.</div>";
    
    // BƯỚC 2: Thêm quyền
    echo "<h2>🔐 BƯỚC 2: Thêm quyền quản lý phân loại</h2>";
    
    $sqlFile2 = __DIR__ . '/migrations/add_phanloai_vattu_permissions.sql';
    
    if (!file_exists($sqlFile2)) {
        throw new Exception("Không tìm thấy file SQL: $sqlFile2");
    }
    
    $sql2 = file_get_contents($sqlFile2);
    
    if ($sql2 === false) {
        throw new Exception("Không thể đọc file SQL");
    }
    
    $statements2 = array_filter(
        array_map('trim', explode(';', $sql2)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    $db->beginTransaction();
    
    foreach ($statements2 as $statement) {
        if (trim($statement)) {
            echo "<div class='info'>Đang thực thi: <pre>" . htmlspecialchars(substr($statement, 0, 150)) . "...</pre></div>";
            try {
                $db->exec($statement);
                echo "<div class='success'>✓ Thành công</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>⚠ " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
    
    $db->commit();
    
    echo "<div class='success'><strong>✓ Hoàn thành BƯỚC 2!</strong> Đã thêm quyền quản lý phân loại.</div>";
    
    // Hiển thị kết quả
    echo "<h2>📊 Kết quả</h2>";
    
    // Kiểm tra bảng phân loại
    echo "<h3>Bảng phân loại vật tư:</h3>";
    $stmt = $db->query("SELECT * FROM phanloai_vattu_thanh_ly_iso ORDER BY thu_tu ASC");
    $phanloais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<ul>";
    foreach ($phanloais as $pl) {
        echo "<li><strong>" . htmlspecialchars($pl['ten_phanloai']) . "</strong> (" . htmlspecialchars($pl['ma_phanloai']) . ")";
        if (!empty($pl['mau_sac'])) {
            echo " - <span style='padding: 4px 8px; border-radius: 4px;' class='" . htmlspecialchars($pl['mau_sac']) . "'>Mẫu màu</span>";
        }
        echo "</li>";
    }
    echo "</ul>";
    
    // Hiển thị danh sách quyền đã thêm
    echo "<h3>Các quyền đã được thêm:</h3>";
    echo "<ul>";
    $stmt = $db->query("SELECT name, description FROM permissions WHERE name LIKE 'phanloai_vattu.%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li><code>" . htmlspecialchars($row['name']) . "</code>: " . htmlspecialchars($row['description']) . "</li>";
    }
    echo "</ul>";
    
    // Hiển thị users đã được cấp quyền
    echo "<h3>Users đã được cấp quyền:</h3>";
    $stmt = $db->query("
        SELECT DISTINCT u.username, u.stt, COUNT(p.id) as so_quyen
        FROM users u
        INNER JOIN user_permissions up ON u.stt = up.user_id
        INNER JOIN permissions p ON up.permission_id = p.id
        WHERE p.name LIKE 'phanloai_vattu.%'
        GROUP BY u.stt, u.username
    ");
    echo "<ul>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>User: <strong>" . htmlspecialchars($row['username']) . "</strong> (ID: {$row['stt']}) - {$row['so_quyen']} quyền</li>";
    }
    echo "</ul>";
    
    echo "<h2>🎉 Hoàn thành toàn bộ setup!</h2>";
    echo "<div class='success'>";
    echo "<p><strong>✓ Đã tạo bảng phân loại vật tư thanh lý</strong></p>";
    echo "<p><strong>✓ Đã thêm " . count($phanloais) . " loại phân loại mặc định</strong></p>";
    echo "<p><strong>✓ Đã thêm 4 quyền quản lý phân loại</strong></p>";
    echo "<p><strong>✓ Đã cấp quyền cho admin users</strong></p>";
    echo "</div>";
    
    echo "<div class='nav'>";
    echo "<a href='phanloaivattu.php'>→ Quản lý Phân loại Vật tư</a>";
    echo "<a href='vattuthanhly.php'>→ Quản lý Vật tư Thanh lý</a>";
    echo "<a href='index.php'>→ Trang chủ</a>";
    echo "</div>";
    
    echo "</body></html>";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "<div class='error'>";
    echo "<h3>✗ Lỗi:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
    echo "</body></html>";
}
