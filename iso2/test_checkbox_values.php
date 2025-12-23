<?php
/**
 * Test script kiểm tra giá trị checkbox khi nhập hồ sơ HC
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

echo "<h1>🔍 Kiểm tra giá trị Checkbox Phương pháp chuẩn</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #4CAF50; color: white; font-weight: bold; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .success { background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
    code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
</style>";

try {
    $db = getDBConnection();
    
    // 1. Kiểm tra cấu trúc bảng
    echo "<h2>1. Cấu trúc các cột checkbox trong bảng hosohckd_iso</h2>";
    $sql = "DESCRIBE hosohckd_iso";
    $stmt = $db->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $checkboxColumns = array_filter($columns, function($col) {
        return in_array($col['Field'], ['danchuan', 'mauchuan', 'dinhky', 'dotxuat']);
    });
    
    if (!empty($checkboxColumns)) {
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($checkboxColumns as $col) {
            echo "<tr>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "<td>{$col['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>";
        echo "<p>⚠️ KHÔNG TÌM THẤY các cột: danchuan, mauchuan, dinhky, dotxuat</p>";
        echo "</div>";
    }
    
    // 2. Kiểm tra dữ liệu thực tế
    echo "<h2>2. Dữ liệu thực tế trong database (20 records gần nhất)</h2>";
    $sql = "SELECT stt, sohs, tenmay, ngayhc, danchuan, mauchuan, dinhky, dotxuat, ttkt 
            FROM hosohckd_iso 
            WHERE ngayhc IS NOT NULL 
            ORDER BY ngayhc DESC 
            LIMIT 20";
    $stmt = $db->query($sql);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($records)) {
        echo "<table>";
        echo "<tr>
                <th>STT</th>
                <th>Số HS</th>
                <th>Tên máy</th>
                <th>Ngày HC</th>
                <th>Dẫn chuẩn</th>
                <th>Mẫu chuẩn</th>
                <th>Định kỳ</th>
                <th>Đột xuất</th>
                <th>TTKT</th>
              </tr>";
        foreach ($records as $row) {
            echo "<tr>";
            echo "<td>{$row['stt']}</td>";
            echo "<td>{$row['sohs']}</td>";
            echo "<td>{$row['tenmay']}</td>";
            echo "<td>" . date('d/m/Y', strtotime($row['ngayhc'])) . "</td>";
            echo "<td style='text-align:center;'><strong>" . ($row['danchuan'] ?? 'NULL') . "</strong></td>";
            echo "<td style='text-align:center;'><strong>" . ($row['mauchuan'] ?? 'NULL') . "</strong></td>";
            echo "<td style='text-align:center;'><strong>" . ($row['dinhky'] ?? 'NULL') . "</strong></td>";
            echo "<td style='text-align:center;'><strong>" . ($row['dotxuat'] ?? 'NULL') . "</strong></td>";
            echo "<td>{$row['ttkt']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Thống kê giá trị
        $stats = [
            'danchuan' => ['0' => 0, '1' => 0, 'NULL' => 0],
            'mauchuan' => ['0' => 0, '1' => 0, 'NULL' => 0],
            'dinhky' => ['0' => 0, '1' => 0, 'NULL' => 0],
            'dotxuat' => ['0' => 0, '1' => 0, 'NULL' => 0],
        ];
        
        foreach ($records as $row) {
            foreach (['danchuan', 'mauchuan', 'dinhky', 'dotxuat'] as $field) {
                $value = $row[$field] ?? null;
                if ($value === null) {
                    $stats[$field]['NULL']++;
                } elseif ($value == 1) {
                    $stats[$field]['1']++;
                } else {
                    $stats[$field]['0']++;
                }
            }
        }
        
        echo "<div class='info'>";
        echo "<h3>📊 Thống kê giá trị (20 records):</h3>";
        echo "<table style='width:auto;'>";
        echo "<tr><th>Trường</th><th>Giá trị 1</th><th>Giá trị 0</th><th>NULL</th></tr>";
        foreach ($stats as $field => $counts) {
            echo "<tr>";
            echo "<td><strong>{$field}</strong></td>";
            echo "<td>{$counts['1']}</td>";
            echo "<td>{$counts['0']}</td>";
            echo "<td>{$counts['NULL']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<p>⚠️ Không có dữ liệu hồ sơ HC trong database</p>";
        echo "</div>";
    }
    
    // 3. Test case INSERT
    echo "<h2>3. Test Insert/Update Logic</h2>";
    echo "<div class='info'>";
    echo "<h3>Logic trong Controller (BangCanhBaoController.php):</h3>";
    echo "<pre style='background:#f5f5f5; padding:15px; border-radius:5px;'>";
    echo "\$danchuan = isset(\$_POST['danchuan']) ? 1 : 0;\n";
    echo "\$mauchuan = isset(\$_POST['mauchuan']) ? 1 : 0;\n";
    echo "\$dinhky = isset(\$_POST['dinhky']) ? 1 : 0;\n";
    echo "\$dotxuat = isset(\$_POST['dotxuat']) ? 1 : 0;";
    echo "</pre>";
    
    echo "<h3>Logic trong Form (form_hoso.php):</h3>";
    echo "<pre style='background:#f5f5f5; padding:15px; border-radius:5px;'>";
    echo htmlspecialchars('<input type="checkbox" name="danchuan" value="1" 
       <?php echo (!empty($hoSo[\'danchuan\'])) ? \'checked\' : \'\'; ?>>');
    echo "</pre>";
    echo "</div>";
    
    // 4. Kết luận
    echo "<h2>✅ Kết luận</h2>";
    echo "<div class='success'>";
    echo "<h3>Xác nhận cấu trúc:</h3>";
    echo "<ul>";
    echo "<li><strong>Name attribute:</strong> <code>danchuan</code>, <code>mauchuan</code>, <code>dinhky</code>, <code>dotxuat</code></li>";
    echo "<li><strong>Value trong HTML:</strong> <code>\"1\"</code> (string)</li>";
    echo "<li><strong>Giá trị lưu DB:</strong> <code>1</code> (checked) hoặc <code>0</code> (unchecked)</li>";
    echo "<li><strong>Kiểu dữ liệu DB:</strong> Cần kiểm tra (thường là TINYINT hoặc INT)</li>";
    echo "<li><strong>Logic kiểm tra:</strong> <code>isset(\$_POST['name']) ? 1 : 0</code></li>";
    echo "<li><strong>Logic hiển thị:</strong> <code>!empty(\$hoSo['name'])</code> → checked</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='warning'>";
    echo "<p><strong>❌ Lỗi:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
