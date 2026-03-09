<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/config/database.php';

echo "<h2>Debug Kế hoạch bảo dưỡng - Tìm kiếm</h2>";

// Test connection
try {
    $db = getDBConnection();
    echo "<p style='color: green;'>✓ Database connected</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check table structure
echo "<h3>1. Cấu trúc bảng</h3>";
try {
    $stmt = $db->query("DESCRIBE ke_hoach_bao_duong_dinh_ky_iso");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

// Check data count
echo "<h3>2. Số lượng dữ liệu</h3>";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM ke_hoach_bao_duong_dinh_ky_iso");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>Tổng số bản ghi: <strong>$total</strong></p>";
    
    // Count by year
    $stmt = $db->query("SELECT nam, COUNT(*) as count FROM ke_hoach_bao_duong_dinh_ky_iso GROUP BY nam ORDER BY nam DESC");
    $byYear = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Theo năm:</p><ul>";
    foreach ($byYear as $row) {
        echo "<li>Năm {$row['nam']}: {$row['count']} bản ghi</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

// Test search query
echo "<h3>3. Test tìm kiếm</h3>";
$testSearch = 'GTET'; // Replace with actual search term
$testYear = 2026;

echo "<p>Tìm kiếm: '<strong>$testSearch</strong>' trong năm <strong>$testYear</strong></p>";

try {
    $sql = "SELECT * FROM ke_hoach_bao_duong_dinh_ky_iso WHERE nam = :nam";
    $params = [':nam' => $testYear];
    
    if (!empty($testSearch)) {
        $sql .= " AND (ten_thietbi LIKE :search1 OR so_serial LIKE :search2)";
        $params[':search1'] = '%' . $testSearch . '%';
        $params[':search2'] = '%' . $testSearch . '%';
    }
    
    echo "<p>SQL: <code>$sql</code></p>";
    echo "<p>Params: <code>" . json_encode($params) . "</code></p>";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Kết quả: <strong>" . count($results) . "</strong> bản ghi</p>";
    
    if (count($results) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Tên thiết bị</th><th>Số S/N</th><th>Năm</th><th>Q1</th><th>Q2</th><th>Q3</th><th>Q4</th></tr>";
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['ten_thietbi']}</td>";
            echo "<td>{$row['so_serial']}</td>";
            echo "<td>{$row['nam']}</td>";
            echo "<td>{$row['qui_1']}</td>";
            echo "<td>{$row['qui_2']}</td>";
            echo "<td>{$row['qui_3']}</td>";
            echo "<td>{$row['qui_4']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Query Error: " . $e->getMessage() . "</p>";
}

// Test with completion columns if they exist
echo "<h3>4. Test cột hoàn thành</h3>";
try {
    $sql = "SELECT id, ten_thietbi, qui_1_hoantat, qui_2_hoantat, qui_3_hoantat, qui_4_hoantat 
            FROM ke_hoach_bao_duong_dinh_ky_iso 
            WHERE nam = :nam 
            LIMIT 5";
    $stmt = $db->prepare($sql);
    $stmt->execute([':nam' => $testYear]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color: green;'>✓ Các cột hoàn thành tồn tại</p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Tên</th><th>Q1 HT</th><th>Q2 HT</th><th>Q3 HT</th><th>Q4 HT</th></tr>";
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['ten_thietbi']}</td>";
        echo "<td>" . ($row['qui_1_hoantat'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['qui_2_hoantat'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['qui_3_hoantat'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['qui_4_hoantat'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Các cột hoàn thành CHƯA TỒN TẠI: " . $e->getMessage() . "</p>";
    echo "<p style='color: orange;'>⚠ Cần chạy migration: <code>php execute_add_hoantat_columns.php</code></p>";
}
