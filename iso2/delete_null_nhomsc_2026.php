<?php
// Script xóa các bản ghi có nhomsc=NULL hoặc nhomsc != 'CNC'
// (Chỉ xóa serial trong danh sách 131 thiết bị CNC năm 2026)
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();

require_once __DIR__ . '/config/database.php';

// Danh sách serial numbers CNC 2026
$serialsToCheck = [
    '11440856', '11484088', '10002832', '10003697', '218711', '10002380',
    '11511544', '11550804', '10022372', '10022886', 'AJG00068', '218421',
    '215996', '10010121', '10010448', '11242365', '11258572', '16313311',
    '16313312', '10003198', '10003201', '16297101', '16292557', '219464',
    '219465', '219466', '211803', '211683', '10003682', '10003683',
    '16249248', '16259538', '219448', '10003705', '10003628', '111668024',
    '111668028', '10008946', '10009015', '10010856', '10002931', '10003381',
    '11710091', '16322649', '16321925', '11011121', '11011122', '001185',
    '001186', '002085', '002086', '001412', '001413', '002017', '002018',
    '001191', '001192', '001616', '001706', '001164', '001161', '001163',
    '001344', '001345', '001682', '001710', '001942', '002006', '001154',
    '001158', '001153', '001157', '001489', '001719', '002003', '002004',
    '001166', '001167', '001550', '001169', '001327', '001328', '001462',
    '001510', '001171', '001175', '002087', '002088', '001743', '001744',
    '001869', '001918', '003021', '003022', 'PO-K-42-175/120', 'K6-07',
    'K6-08', '73-01', '73-04', 'L6-03', 'L6-04', '86-02', '86-03',
    'A7-03', 'A7-04', 'IWO 40-02', 'IWO 40-03', 'J8JE6-02', 'JC1I7-01',
    '909101', '909102', '10011639', '10011661', '10011613', '10011660',
    '3301', '3102', 'CP 26591', 'CP 25349', 'CP 21782', 'CP 25314',
    'CP-26413', 'CP-20183357', 'CP-20183358', '001674', '001675', '001713',
    '001174', 'WM'
];

echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
    .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h2 { color: #333; border-bottom: 3px solid #dc3545; padding-bottom: 10px; }
    .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; color: #856404; }
    .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 10px 0; color: #155724; }
    .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; color: #721c24; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
    th { background-color: #dc3545; color: white; position: sticky; top: 0; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    tr:hover { background-color: #ffe0e0; }
    .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 14px; margin: 5px; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-danger:hover { background: #c82333; }
    .btn-cancel { background: #6c757d; color: white; }
</style>";

echo "<div class='container'>";
echo "<h2>🗑️ Xóa các bản ghi có Nhóm SC = NULL hoặc khác 'CNC'</h2>";
echo "<p style='color: #666;'>Chỉ xóa serial nằm trong danh sách 131 thiết bị CNC năm 2026</p>";
echo "<p>
<a href='check_data_kehoach_2026.php' style='color: #2196F3; font-weight: bold; text-decoration: none; margin-right: 15px;'>🔍 Kiểm tra dữ liệu</a>
<a href='insert_kehoach_baoduong_2026.php' style='color: #28a745; font-weight: bold; text-decoration: none; margin-right: 15px;'>➕ Import dữ liệu</a>
<a href='delete_kehoach_baoduong_2026.php' style='color: #dc3545; font-weight: bold; text-decoration: none;'>🗑️ Xóa dữ liệu khác</a>
</p>";

try {
    $db = getDBConnection(true);
    
    // Xử lý xóa
    if (isset($_POST['confirm_delete'])) {
        $placeholders = implode(',', array_fill(0, count($serialsToCheck), '?'));
        
        $deleteSql = "DELETE FROM ke_hoach_bao_duong_dinh_ky_iso 
                     WHERE nam = 2026 
                     AND so_serial IN ($placeholders)
                     AND (nhomsc IS NULL OR nhomsc != 'CNC')";
        
        $stmt = $db->prepare($deleteSql);
        $stmt->execute($serialsToCheck);
        $deletedCount = $stmt->rowCount();
        
        echo "<div class='success'>";
        echo "<h3>✅ Đã hoàn tất xóa!</h3>";
        echo "<p><strong>Số bản ghi đã xóa: $deletedCount</strong></p>";
        echo "<p>Các bản ghi này có:</p>";
        echo "<ul>";
        echo "<li>Năm = 2026</li>";
        echo "<li>Serial nằm trong danh sách 131 thiết bị CNC</li>";
        echo "<li>Nhóm SC = NULL hoặc Nhóm SC khác 'CNC'</li>";
        echo "</ul>";
        echo "<p><a href='check_data_kehoach_2026.php' style='color: #155724; font-weight: bold;'>→ Kiểm tra lại dữ liệu</a></p>";
        echo "<p><a href='insert_kehoach_baoduong_2026.php' style='color: #28a745; font-weight: bold;'>→ Import lại dữ liệu sạch</a></p>";
        echo "</div>";
    }
    
    // Hiển thị preview
    $placeholders = implode(',', array_fill(0, count($serialsToCheck), '?'));
    
    // Đếm tổng số
    $countSql = "SELECT COUNT(*) as total 
                 FROM ke_hoach_bao_duong_dinh_ky_iso 
                 WHERE nam = 2026 
                 AND so_serial IN ($placeholders)
                 AND (nhomsc IS NULL OR nhomsc != 'CNC')";
    $stmtCount = $db->prepare($countSql);
    $stmtCount->execute($serialsToCheck);
    $totalToDelete = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<div class='warning'>";
    echo "<h3>⚠️ Cảnh báo</h3>";
    echo "<p><strong>Tìm thấy $totalToDelete bản ghi</strong> có Nhóm SC = NULL hoặc khác 'CNC'</p>";
    echo "<p>Các bản ghi này sẽ bị XÓA:</p>";
    echo "<ul>";
    echo "<li>✓ Năm = 2026</li>";
    echo "<li>✓ Serial nằm trong danh sách 131 thiết bị CNC</li>";
    echo "<li>✓ Nhóm SC = NULL hoặc Nhóm SC ≠ 'CNC'</li>";
    echo "</ul>";
    echo "</div>";
    
    if ($totalToDelete > 0) {
        // Lấy danh sách chi tiết
        $previewSql = "SELECT id, ten_thietbi, so_serial, nhomsc, qui_1, qui_2, qui_3, qui_4, thietbi_id, created_at
                      FROM ke_hoach_bao_duong_dinh_ky_iso 
                      WHERE nam = 2026 
                      AND so_serial IN ($placeholders)
                      AND (nhomsc IS NULL OR nhomsc != 'CNC')
                      ORDER BY 
                          CASE 
                              WHEN nhomsc IS NULL THEN 1 
                              ELSE 2 
                          END,
                          nhomsc, id";
        $stmtPreview = $db->prepare($previewSql);
        $stmtPreview->execute($serialsToCheck);
        $recordsToDelete = $stmtPreview->fetchAll(PDO::FETCH_ASSOC);
        
        // Phân loại theo nhomsc
        $byNhomsc = [];
        foreach ($recordsToDelete as $rec) {
            $nhomsc = $rec['nhomsc'] ?: 'NULL';
            if (!isset($byNhomsc[$nhomsc])) {
                $byNhomsc[$nhomsc] = 0;
            }
            $byNhomsc[$nhomsc]++;
        }
        
        echo "<h3>📊 Phân loại bản ghi cần xóa:</h3>";
        echo "<table style='width: 400px;'>";
        echo "<tr><th>Nhóm SC</th><th>Số lượng</th></tr>";
        foreach ($byNhomsc as $nhomsc => $count) {
            $displayNhomsc = ($nhomsc === 'NULL') ? '<span style="color: #dc3545; font-weight: bold;">NULL</span>' : htmlspecialchars($nhomsc);
            echo "<tr>";
            echo "<td>$displayNhomsc</td>";
            echo "<td><strong>$count</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>📋 Chi tiết $totalToDelete bản ghi sẽ bị XÓA:</h3>";
        echo "<div style='max-height: 500px; overflow-y: auto;'>";
        echo "<table>";
        echo "<tr>
                <th>ID</th>
                <th>Tên thiết bị</th>
                <th>Serial</th>
                <th>Nhóm SC</th>
                <th>Q1</th>
                <th>Q2</th>
                <th>Q3</th>
                <th>Q4</th>
                <th>TB ID</th>
                <th>Ngày tạo</th>
              </tr>";
        
        foreach ($recordsToDelete as $rec) {
            $isNull = empty($rec['nhomsc']);
            $bgColor = $isNull ? '#ffe0e0' : '#fff3cd';
            $nhomscDisplay = $isNull ? '<span style="color: #dc3545; font-weight: bold;">NULL</span>' : htmlspecialchars($rec['nhomsc']);
            
            echo "<tr style='background-color: $bgColor;'>";
            echo "<td>" . htmlspecialchars($rec['id']) . "</td>";
            echo "<td>" . htmlspecialchars($rec['ten_thietbi']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($rec['so_serial']) . "</strong></td>";
            echo "<td>" . $nhomscDisplay . "</td>";
            echo "<td>" . ($rec['qui_1'] ? '✓' : '') . "</td>";
            echo "<td>" . ($rec['qui_2'] ? '✓' : '') . "</td>";
            echo "<td>" . ($rec['qui_3'] ? '✓' : '') . "</td>";
            echo "<td>" . ($rec['qui_4'] ? '✓' : '') . "</td>";
            echo "<td>" . ($rec['thietbi_id'] ?: '-') . "</td>";
            echo "<td style='font-size: 10px;'>" . htmlspecialchars($rec['created_at']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
        
        // Form xác nhận xóa
        echo "<div style='background: #dc3545; color: white; padding: 20px; margin: 20px 0; border-radius: 5px; text-align: center;'>";
        echo "<h3 style='margin-top: 0;'>⚠️ XÁC NHẬN XÓA</h3>";
        echo "<p style='font-size: 16px;'>Bạn có chắc chắn muốn xóa <strong>$totalToDelete bản ghi</strong> này?</p>";
        echo "<p>Hành động này <strong>KHÔNG THỂ HOÀN TÁC</strong>!</p>";
        echo "<form method='post' style='margin-top: 20px;' onsubmit='return confirm(\"Bạn có CHẮC CHẮN muốn xóa $totalToDelete bản ghi?\");'>";
        echo "<button type='submit' name='confirm_delete' class='btn btn-danger'>✓ XÁC NHẬN XÓA $totalToDelete BẢN GHI</button>";
        echo "<button type='button' class='btn btn-cancel' onclick='history.back()'>✗ Hủy bỏ</button>";
        echo "</form>";
        echo "</div>";
        
    } else {
        echo "<div class='success'>";
        echo "<h3>✅ Không có dữ liệu cần xóa</h3>";
        echo "<p>Tất cả các serial trong danh sách đều có nhomsc='CNC' hoặc chưa được import.</p>";
        echo "<p><a href='check_data_kehoach_2026.php' style='color: #155724; font-weight: bold;'>→ Kiểm tra dữ liệu</a></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Lỗi:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div>";
?>
