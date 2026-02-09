<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection();
    
    echo "<h2>Thêm quyền quản lý phân loại vật tư thanh lý</h2>";
    
    // Đọc file SQL
    $sqlFile = __DIR__ . '/migrations/add_phanloai_vattu_permissions.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Không tìm thấy file SQL: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        throw new Exception("Không thể đọc file SQL");
    }
    
    // Tách các câu lệnh SQL
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    $db->beginTransaction();
    
    foreach ($statements as $statement) {
        if (trim($statement)) {
            echo "<p>Đang thực thi: <pre>" . htmlspecialchars(substr($statement, 0, 200)) . "...</pre></p>";
            $db->exec($statement);
            echo "<p style='color: green;'>✓ Thành công</p>";
        }
    }
    
    $db->commit();
    
    echo "<h3 style='color: green;'>✓ Hoàn thành! Đã thêm quyền quản lý phân loại vật tư</h3>";
    
    // Hiển thị danh sách quyền đã thêm
    echo "<h4>Các quyền đã được thêm:</h4>";
    echo "<ul>";
    $stmt = $db->query("SELECT name, description FROM permissions WHERE name LIKE 'phanloai_vattu.%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li><strong>" . htmlspecialchars($row['name']) . "</strong>: " . htmlspecialchars($row['description']) . "</li>";
    }
    echo "</ul>";
    
    // Hiển thị users đã được cấp quyền
    echo "<h4>Users đã được cấp quyền:</h4>";
    $stmt = $db->query("
        SELECT DISTINCT u.username, u.stt 
        FROM users u
        INNER JOIN user_permissions up ON u.stt = up.user_id
        INNER JOIN permissions p ON up.permission_id = p.id
        WHERE p.name LIKE 'phanloai_vattu.%'
    ");
    echo "<ul>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>User: <strong>" . htmlspecialchars($row['username']) . "</strong> (ID: {$row['stt']})</li>";
    }
    echo "</ul>";
    
    echo "<p><a href='phanloaivattu.php'>→ Vào trang quản lý phân loại vật tư</a></p>";
    echo "<p><a href='vattuthanhly.php'>← Quay lại trang quản lý vật tư</a></p>";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "<h3 style='color: red;'>✗ Lỗi:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
