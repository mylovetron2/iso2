<?php
// Script xóa dữ liệu từ bảng ke_hoach_bao_duong_dinh_ky_iso
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();

require_once __DIR__ . '/config/database.php';

// Danh sách serial numbers cần xóa (từ file insert)
$serialsToDelete = [
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

// Xử lý xóa
if (isset($_POST['delete_action'])) {
    try {
        $db = getDBConnection(true);
        $action = $_POST['delete_action'];
        $deleted = 0;
        
        if ($action === 'delete_cnc_2026') {
            // Xóa tất cả bản ghi nhóm CNC năm 2026
            $stmt = $db->prepare("DELETE FROM ke_hoach_bao_duong_dinh_ky_iso 
                                 WHERE nam = 2026 AND nhomsc = 'CNC'");
            $stmt->execute();
            $deleted = $stmt->rowCount();
            
        } elseif ($action === 'delete_duplicates') {
            // Xóa các bản ghi trùng lặp (giữ lại bản ghi có ID nhỏ nhất)
            $sql = "DELETE k1 FROM ke_hoach_bao_duong_dinh_ky_iso k1
                    INNER JOIN ke_hoach_bao_duong_dinh_ky_iso k2 
                    WHERE k1.id > k2.id 
                    AND k1.ten_thietbi = k2.ten_thietbi 
                    AND k1.so_serial = k2.so_serial 
                    AND k1.nam = k2.nam 
                    AND k1.nam = 2026 
                    AND k1.nhomsc = 'CNC'";
            $stmt = $db->query($sql);
            $deleted = $stmt->rowCount();
            
        } elseif ($action === 'delete_all_2026') {
            // Xóa TẤT CẢ bản ghi năm 2026 (không phân biệt nhóm SC)
            $stmt = $db->prepare("DELETE FROM ke_hoach_bao_duong_dinh_ky_iso WHERE nam = 2026");
            $stmt->execute();
            $deleted = $stmt->rowCount();
            
        } elseif ($action === 'delete_by_serial_list') {
            // Xóa các bản ghi theo danh sách serial
            global $serialsToDelete;
            
            if (!empty($serialsToDelete)) {
                $placeholders = implode(',', array_fill(0, count($serialsToDelete), '?'));
                $sql = "DELETE FROM ke_hoach_bao_duong_dinh_ky_iso 
                        WHERE nam = 2026 AND nhomsc = 'CNC' 
                        AND so_serial IN ($placeholders)";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($serialsToDelete);
                $deleted = $stmt->rowCount();
            }
            
        } elseif ($action === 'delete_null_or_non_cnc') {
            // Xóa các bản ghi có nhomsc = NULL hoặc nhomsc != 'CNC'
            $stmt = $db->prepare("DELETE FROM ke_hoach_bao_duong_dinh_ky_iso 
                                 WHERE nam = 2026 AND (nhomsc IS NULL OR nhomsc != 'CNC')");
            $stmt->execute();
            $deleted = $stmt->rowCount();
        }
        
        $_SESSION['delete_result'] = [
            'success' => true,
            'deleted' => $deleted,
            'action' => $action
        ];
        
    } catch (Exception $e) {
        $_SESSION['delete_result'] = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
    
    header("Location: delete_kehoach_baoduong_2026.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xóa Kế Hoạch Bảo Dưỡng 2026</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background-color: #f5f5f5; 
        }
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        h2 { 
            color: #d32f2f; 
            border-bottom: 3px solid #d32f2f; 
            padding-bottom: 10px; 
        }
        .warning { 
            background: #fff3cd; 
            border-left: 4px solid #ffc107; 
            padding: 15px; 
            margin: 20px 0; 
            color: #856404; 
        }
        .success { 
            background: #d4edda; 
            border-left: 4px solid #28a745; 
            padding: 15px; 
            margin: 20px 0; 
            color: #155724; 
        }
        .error { 
            background: #f8d7da; 
            border-left: 4px solid #dc3545; 
            padding: 15px; 
            margin: 20px 0; 
            color: #721c24; 
        }
        .stats { 
            display: flex; 
            gap: 20px; 
            margin: 20px 0; 
        }
        .stat-box { 
            flex: 1; 
            padding: 15px; 
            border-radius: 5px; 
            text-align: center; 
        }
        .stat-box h3 { 
            margin: 0; 
            font-size: 32px; 
        }
        .stat-box p { 
            margin: 5px 0 0 0; 
        }
        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin: 20px 0; 
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
            font-size: 12px; 
        }
        th { 
            background-color: #dc3545; 
            color: white; 
        }
        tr:nth-child(even) { 
            background-color: #f2f2f2; 
        }
        .delete-btn { 
            padding: 10px 20px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-weight: bold; 
            font-size: 14px; 
            margin: 5px; 
        }
        .btn-danger { 
            background: #dc3545; 
            color: white; 
        }
        .btn-warning { 
            background: #ffc107; 
            color: #000; 
        }
        .btn-info { 
            background: #17a2b8; 
            color: white; 
        }
        .delete-btn:hover { 
            opacity: 0.8; 
        }
        .confirm-box { 
            background: #fff3cd; 
            border: 2px solid #ffc107; 
            padding: 15px; 
            margin: 20px 0; 
            border-radius: 5px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🗑️ Xóa Kế Hoạch Bảo Dưỡng 2026</h2>
        <p><a href="check_data_kehoach_2026.php" style="color: #2196F3; font-weight: bold; text-decoration: none; margin-right: 15px;">🔍 Kiểm tra dữ liệu trước khi xóa</a></p>
        
        <?php
        // Hiển thị kết quả xóa
        if (isset($_SESSION['delete_result'])) {
            $result = $_SESSION['delete_result'];
            if ($result['success']) {
                echo "<div class='success'>";
                echo "<h3>✅ Xóa thành công!</h3>";
                echo "<p>Đã xóa <strong>{$result['deleted']}</strong> bản ghi</p>";
                if ($result['action'] === 'delete_cnc_2026') {
                    echo "<p>Đã xóa tất cả bản ghi nhóm CNC năm 2026</p>";
                } elseif ($result['action'] === 'delete_duplicates') {
                    echo "<p>Đã xóa các bản ghi trùng lặp (giữ lại bản ghi cũ nhất)</p>";
                } elseif ($result['action'] === 'delete_all_2026') {
                    echo "<p>Đã xóa TẤT CẢ bản ghi năm 2026</p>";
                } elseif ($result['action'] === 'delete_by_serial_list') {
                    echo "<p>Đã xóa các bản ghi theo danh sách serial (131 thiết bị)</p>";
                } elseif ($result['action'] === 'delete_null_or_non_cnc') {
                    echo "<p>Đã xóa các bản ghi có nhomsc = NULL hoặc nhomsc != 'CNC'</p>";
                }
                echo "</div>";
            } else {
                echo "<div class='error'>";
                echo "<h3>❌ Lỗi khi xóa:</h3>";
                echo "<p>{$result['error']}</p>";
                echo "</div>";
            }
            unset($_SESSION['delete_result']);
        }
        
        try {
            $db = getDBConnection(true);
            
            // Sử dụng biến global
            global $serialsToDelete;
            
            // Thống kê dữ liệu hiện tại
            echo "<h3>📊 Thống kê dữ liệu hiện tại:</h3>";
            
            // Tổng bản ghi năm 2026
            $stmt = $db->query("SELECT COUNT(*) as total FROM ke_hoach_bao_duong_dinh_ky_iso WHERE nam = 2026");
            $total2026 = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Bản ghi nhóm CNC năm 2026
            $stmt = $db->query("SELECT COUNT(*) as total FROM ke_hoach_bao_duong_dinh_ky_iso WHERE nam = 2026 AND nhomsc = 'CNC'");
            $totalCNC2026 = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Bản ghi trùng lặp
            $stmt = $db->query("SELECT COUNT(*) as duplicates FROM (
                                    SELECT ten_thietbi, so_serial, nam, COUNT(*) as cnt 
                                    FROM ke_hoach_bao_duong_dinh_ky_iso 
                                    WHERE nam = 2026 AND nhomsc = 'CNC'
                                    GROUP BY ten_thietbi, so_serial, nam 
                                    HAVING cnt > 1
                                ) as t");
            $duplicates = $stmt->fetch(PDO::FETCH_ASSOC)['duplicates'];
            
            // Tổng số bản ghi trùng lặp cần xóa
            $stmt = $db->query("SELECT COUNT(*) - COUNT(DISTINCT CONCAT(ten_thietbi, so_serial)) as dup_count 
                               FROM ke_hoach_bao_duong_dinh_ky_iso 
                               WHERE nam = 2026 AND nhomsc = 'CNC'");
            $dupToDelete = $stmt->fetch(PDO::FETCH_ASSOC)['dup_count'];
            
            // Số bản ghi trong danh sách serial
            $placeholders = implode(',', array_fill(0, count($serialsToDelete), '?'));
            $stmt = $db->prepare("SELECT COUNT(*) as count_in_list 
                                 FROM ke_hoach_bao_duong_dinh_ky_iso 
                                 WHERE nam = 2026 AND nhomsc = 'CNC' 
                                 AND so_serial IN ($placeholders)");
            $stmt->execute($serialsToDelete);
            $countInList = $stmt->fetch(PDO::FETCH_ASSOC)['count_in_list'];
            
            // Số bản ghi có nhomsc NULL hoặc != 'CNC'
            $stmt = $db->query("SELECT COUNT(*) as count_null_or_non_cnc 
                               FROM ke_hoach_bao_duong_dinh_ky_iso 
                               WHERE nam = 2026 AND (nhomsc IS NULL OR nhomsc != 'CNC')");
            $countNullOrNonCNC = $stmt->fetch(PDO::FETCH_ASSOC)['count_null_or_non_cnc'];
            
            echo "<div class='stats'>";
            echo "<div class='stat-box' style='background: #d1ecf1; color: #0c5460;'>";
            echo "<h3>$total2026</h3>";
            echo "<p>Tổng bản ghi năm 2026</p>";
            echo "</div>";
            echo "<div class='stat-box' style='background: #fff3cd; color: #856404;'>";
            echo "<h3>$totalCNC2026</h3>";
            echo "<p>Bản ghi CNC năm 2026</p>";
            echo "</div>";
            echo "<div class='stat-box' style='background: #cce5ff; color: #004085;'>";
            echo "<h3>$countInList</h3>";
            echo "<p>Bản ghi trong danh sách</p>";
            echo "</div>";
            echo "<div class='stat-box' style='background: #f8d7da; color: #721c24;'>";
            echo "<h3>$duplicates</h3>";
            echo "<p>Thiết bị bị trùng (unique)</p>";
            echo "</div>";
            echo "</div>";
            
            echo "<div class='stats'>";
            echo "<div class='stat-box' style='background: #ffe5e5; color: #d32f2f;'>";
            echo "<h3>$dupToDelete</h3>";
            echo "<p>Bản ghi trùng cần xóa</p>";
            echo "</div>";
            echo "<div class='stat-box' style='background: #e8f5e9; color: #2e7d32;'>";
            echo "<h3>" . count($serialsToDelete) . "</h3>";
            echo "<p>Serial trong danh sách</p>";
            echo "</div>";
            echo "<div class='stat-box' style='background: #ffebee; color: #c62828;'>";
            echo "<h3>$countNullOrNonCNC</h3>";
            echo "<p>Nhomsc NULL/Không phải CNC</p>";
            echo "</div>";
            echo "</div>";
            
            // Hiển thị các bản ghi trùng lặp
            if ($duplicates > 0) {
                echo "<h3>⚠ Danh sách thiết bị bị trùng lặp:</h3>";
                $stmt = $db->query("SELECT ten_thietbi, so_serial, COUNT(*) as cnt, 
                                          MIN(id) as first_id, MAX(id) as last_id,
                                          GROUP_CONCAT(id ORDER BY id) as all_ids
                                   FROM ke_hoach_bao_duong_dinh_ky_iso 
                                   WHERE nam = 2026 AND nhomsc = 'CNC'
                                   GROUP BY ten_thietbi, so_serial 
                                   HAVING cnt > 1
                                   ORDER BY cnt DESC
                                   LIMIT 20");
                $dupRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<table>";
                echo "<tr><th>Tên thiết bị</th><th>Số Serial</th><th>Số lần trùng</th><th>ID đầu tiên</th><th>ID cuối</th><th>Tất cả IDs</th></tr>";
                foreach ($dupRecords as $rec) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($rec['ten_thietbi']) . "</td>";
                    echo "<td><strong>" . htmlspecialchars($rec['so_serial']) . "</strong></td>";
                    echo "<td><span style='color: red; font-weight: bold;'>" . $rec['cnt'] . " lần</span></td>";
                    echo "<td>" . $rec['first_id'] . "</td>";
                    echo "<td>" . $rec['last_id'] . "</td>";
                    echo "<td style='font-size: 10px;'>" . htmlspecialchars($rec['all_ids']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Hiển thị bản ghi khớp với danh sách serial
            if ($countInList > 0) {
                echo "<h3>📋 Bản ghi khớp với danh sách serial (20 bản ghi đầu tiên):</h3>";
                echo "<p style='color: #0066cc;'><i>Đây là những bản ghi sẽ bị xóa nếu chọn 'Xóa theo danh sách serial'</i></p>";
                
                $placeholders = implode(',', array_fill(0, count($serialsToDelete), '?'));
                $stmt = $db->prepare("SELECT id, ten_thietbi, so_serial, qui_1, qui_2, qui_3, qui_4, thietbi_id, created_at 
                                     FROM ke_hoach_bao_duong_dinh_ky_iso 
                                     WHERE nam = 2026 AND nhomsc = 'CNC' 
                                     AND so_serial IN ($placeholders)
                                     ORDER BY id DESC 
                                     LIMIT 20");
                $stmt->execute($serialsToDelete);
                $matchingRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<table>";
                echo "<tr><th>ID</th><th>TB ID</th><th>Tên TB</th><th>Serial</th><th>Q1</th><th>Q2</th><th>Q3</th><th>Q4</th><th>Ngày tạo</th></tr>";
                foreach ($matchingRecords as $rec) {
                    echo "<tr style='background-color: #e3f2fd;'>";
                    echo "<td>" . $rec['id'] . "</td>";
                    echo "<td>" . ($rec['thietbi_id'] ?: '-') . "</td>";
                    echo "<td>" . htmlspecialchars($rec['ten_thietbi']) . "</td>";
                    echo "<td><strong>" . htmlspecialchars($rec['so_serial']) . "</strong></td>";
                    echo "<td>" . ($rec['qui_1'] ? '✓' : '') . "</td>";
                    echo "<td>" . ($rec['qui_2'] ? '✓' : '') . "</td>";
                    echo "<td>" . ($rec['qui_3'] ? '✓' : '') . "</td>";
                    echo "<td>" . ($rec['qui_4'] ? '✓' : '') . "</td>";
                    echo "<td style='font-size: 10px;'>" . $rec['created_at'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Các nút xóa
            echo "<div class='warning'>";
            echo "<h3>⚠️ CẢNH BÁO:</h3>";
            echo "<p><strong>Hành động xóa KHÔNG THỂ HOÀN TÁC!</strong></p>";
            echo "<p>Vui lòng kiểm tra kỹ trước khi thực hiện xóa.</p>";
            echo "</div>";
            
            echo "<h3>🗑️ Chọn hành động xóa:</h3>";
            
            // Nút -1: Xóa các bản ghi có nhomsc NULL hoặc != 'CNC' (Ưu tiên cao nhất khi phát hiện dữ liệu lỗi)
            if ($countNullOrNonCNC > 0) {
                echo "<div class='confirm-box' style='background: #ffebee; border-color: #c62828;'>";
                echo "<h4>⚠️ Xóa bản ghi có nhomsc sai (NULL hoặc != 'CNC')</h4>";
                echo "<p>Xóa các bản ghi có <strong>nhomsc = NULL</strong> hoặc <strong>nhomsc khác 'CNC'</strong> trong năm 2026.</p>";
                echo "<p><strong>Hiện có: $countNullOrNonCNC bản ghi</strong> có nhomsc sai</p>";
                echo "<p style='color: #c62828;'><i>⚠ Dữ liệu này có thể do import từ lần trước với nhomsc khác hoặc NULL</i></p>";
                
                // Hiển thị mẫu các bản ghi sẽ bị xóa
                $stmtPreview = $db->query("SELECT id, ten_thietbi, so_serial, nhomsc, created_at 
                                          FROM ke_hoach_bao_duong_dinh_ky_iso 
                                          WHERE nam = 2026 AND (nhomsc IS NULL OR nhomsc != 'CNC')
                                          ORDER BY id DESC 
                                          LIMIT 10");
                $previewRecords = $stmtPreview->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($previewRecords)) {
                    echo "<details style='margin: 10px 0;'>";
                    echo "<summary style='cursor: pointer; color: #c62828; font-weight: bold;'>👁️ Xem 10 bản ghi sẽ bị xóa</summary>";
                    echo "<table style='margin-top: 10px; font-size: 11px;'>";
                    echo "<tr><th>ID</th><th>Tên TB</th><th>Serial</th><th>Nhóm SC</th><th>Ngày tạo</th></tr>";
                    foreach ($previewRecords as $rec) {
                        $nhomscDisplay = $rec['nhomsc'] ? htmlspecialchars($rec['nhomsc']) : '<span style=\"color: red;\">NULL</span>';
                        echo "<tr style='background: #ffebee;'>";
                        echo "<td>" . $rec['id'] . "</td>";
                        echo "<td>" . htmlspecialchars($rec['ten_thietbi']) . "</td>";
                        echo "<td><strong>" . htmlspecialchars($rec['so_serial']) . "</strong></td>";
                        echo "<td><strong>" . $nhomscDisplay . "</strong></td>";
                        echo "<td style='font-size: 10px;'>" . $rec['created_at'] . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    echo "</details>";
                }
                
                echo "<form method='post' onsubmit='return confirm(\"Bạn có chắc muốn xóa $countNullOrNonCNC bản ghi có nhomsc sai?\");'>";
                echo "<input type='hidden' name='delete_action' value='delete_null_or_non_cnc'>";
                echo "<button type='submit' class='delete-btn btn-danger'>🗑️ Xóa $countNullOrNonCNC bản ghi nhomsc sai</button>";
                echo "</form>";
                echo "</div>";
            }
            
            // Nút 0: Xóa theo danh sách serial (MỚI - Ưu tiên cao nhất)
            echo "<div class='confirm-box' style='background: #e3f2fd; border-color: #2196F3;'>";
            echo "<h4>0️⃣ Xóa theo danh sách serial (Chính xác)</h4>";
            echo "<p>Xóa <strong>CHỈ</strong> các bản ghi có serial trong danh sách 131 thiết bị từ file insert.</p>";
            echo "<p><strong>Hiện có: $countInList bản ghi</strong> trong DB khớp với danh sách serial</p>";
            echo "<p style='color: #0066cc;'><i>✓ An toàn: Chỉ xóa đúng những gì đã import</i></p>";
            echo "<details style='margin: 10px 0;'>";
            echo "<summary style='cursor: pointer; color: #0066cc;'>👁️ Xem danh sách " . count($serialsToDelete) . " serial sẽ bị xóa</summary>";
            echo "<div style='max-height: 200px; overflow-y: auto; background: #f5f5f5; padding: 10px; margin-top: 5px; border-radius: 3px;'>";
            echo "<code style='font-size: 11px;'>" . implode(', ', array_slice($serialsToDelete, 0, 50));
            if (count($serialsToDelete) > 50) {
                echo "... (và " . (count($serialsToDelete) - 50) . " serial khác)";
            }
            echo "</code></div>";
            echo "</details>";
            echo "<form method='post' onsubmit='return confirm(\"Bạn có chắc muốn xóa $countInList bản ghi theo danh sách serial?\");'>";
            echo "<input type='hidden' name='delete_action' value='delete_by_serial_list'>";
            echo "<button type='submit' class='delete-btn btn-info'>🎯 Xóa theo danh sách (131 serial)</button>";
            echo "</form>";
            echo "</div>";
            
            // Nút 1: Xóa bản ghi trùng lặp
            echo "<div class='confirm-box'>";
            echo "<h4>1️⃣ Xóa bản ghi trùng lặp (Khuyến nghị)</h4>";
            echo "<p>Giữ lại bản ghi <strong>đầu tiên</strong> (ID nhỏ nhất), xóa các bản ghi trùng sau đó.</p>";
            echo "<p><strong>Sẽ xóa: $dupToDelete bản ghi</strong></p>";
            echo "<form method='post' onsubmit='return confirm(\"Bạn có chắc muốn xóa $dupToDelete bản ghi trùng lặp?\");'>";
            echo "<input type='hidden' name='delete_action' value='delete_duplicates'>";
            echo "<button type='submit' class='delete-btn btn-warning'>🧹 Xóa bản ghi trùng lặp</button>";
            echo "</form>";
            echo "</div>";
            
            // Nút 2: Xóa tất cả CNC 2026
            echo "<div class='confirm-box' style='background: #ffe5e5; border-color: #dc3545;'>";
            echo "<h4>2️⃣ Xóa tất cả bản ghi CNC năm 2026</h4>";
            echo "<p>Xóa <strong>TẤT CẢ</strong> bản ghi nhóm SC = 'CNC' năm 2026 (bao gồm cả bản ghi không trùng).</p>";
            echo "<p><strong>Sẽ xóa: $totalCNC2026 bản ghi</strong></p>";
            echo "<form method='post' onsubmit='return confirm(\"⚠️ CẢNH BÁO: Bạn sắp xóa $totalCNC2026 bản ghi CNC 2026. Hành động này KHÔNG THỂ HOÀN TÁC! Bạn có chắc chắn?\");'>";
            echo "<input type='hidden' name='delete_action' value='delete_cnc_2026'>";
            echo "<button type='submit' class='delete-btn btn-danger'>🗑️ Xóa tất cả CNC 2026</button>";
            echo "</form>";
            echo "</div>";
            
            // Nút 3: Xóa tất cả năm 2026
            echo "<div class='confirm-box' style='background: #ffcccc; border-color: #d32f2f;'>";
            echo "<h4>3️⃣ Xóa TẤT CẢ bản ghi năm 2026 (Nguy hiểm!)</h4>";
            echo "<p>Xóa <strong>TẤT CẢ</strong> bản ghi năm 2026 (không phân biệt nhóm SC).</p>";
            echo "<p><strong>Sẽ xóa: $total2026 bản ghi</strong></p>";
            echo "<form method='post' onsubmit='return confirm(\"🚨 NGUY HIỂM: Bạn sắp xóa TOÀN BỘ $total2026 bản ghi năm 2026. Bạn có THỰC SỰ chắc chắn?\") && confirm(\"Xác nhận lần 2: Xóa $total2026 bản ghi?\");'>";
            echo "<input type='hidden' name='delete_action' value='delete_all_2026'>";
            echo "<button type='submit' class='delete-btn btn-danger'>☠️ Xóa TẤT CẢ 2026</button>";
            echo "</form>";
            echo "</div>";
            
            // Hiển thị mẫu dữ liệu
            echo "<h3>📋 Mẫu dữ liệu (20 bản ghi gần nhất):</h3>";
            $stmt = $db->query("SELECT id, ten_thietbi, so_serial, nhomsc, qui_1, qui_2, qui_3, qui_4, thietbi_id, created_at 
                               FROM ke_hoach_bao_duong_dinh_ky_iso 
                               WHERE nam = 2026 
                               ORDER BY id DESC 
                               LIMIT 20");
            $sampleRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($sampleRecords)) {
                echo "<table>";
                echo "<tr><th>ID</th><th>TB ID</th><th>Tên TB</th><th>Serial</th><th>Nhóm SC</th><th>Q1</th><th>Q2</th><th>Q3</th><th>Q4</th><th>Ngày tạo</th></tr>";
                foreach ($sampleRecords as $rec) {
                    echo "<tr>";
                    echo "<td>" . $rec['id'] . "</td>";
                    echo "<td>" . ($rec['thietbi_id'] ?: '-') . "</td>";
                    echo "<td>" . htmlspecialchars($rec['ten_thietbi']) . "</td>";
                    echo "<td><strong>" . htmlspecialchars($rec['so_serial']) . "</strong></td>";
                    echo "<td>" . htmlspecialchars($rec['nhomsc']) . "</td>";
                    echo "<td>" . ($rec['qui_1'] ? '✓' : '') . "</td>";
                    echo "<td>" . ($rec['qui_2'] ? '✓' : '') . "</td>";
                    echo "<td>" . ($rec['qui_3'] ? '✓' : '') . "</td>";
                    echo "<td>" . ($rec['qui_4'] ? '✓' : '') . "</td>";
                    echo "<td style='font-size: 10px;'>" . $rec['created_at'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<h3>❌ Lỗi:</h3>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>
        
        <div style="margin-top: 30px; padding: 15px; background: #e7f3ff; border-left: 4px solid #2196F3;">
            <p><strong>💡 Gợi ý:</strong></p>
            <ul>
                <li><strong>Xóa theo danh sách serial:</strong> Xóa chính xác 131 thiết bị từ file insert (An toàn nhất, khuyến nghị)</li>
                <li><strong>Xóa bản ghi trùng lặp:</strong> Nếu bạn insert nhiều lần và muốn giữ lại dữ liệu (chỉ xóa phần trùng)</li>
                <li><strong>Xóa tất cả CNC 2026:</strong> Nếu muốn reset hoàn toàn và import lại từ đầu</li>
                <li><strong>Xóa tất cả 2026:</strong> Chỉ dùng khi cần xóa sạch toàn bộ để bắt đầu lại</li>
            </ul>
            <p><a href="insert_kehoach_baoduong_2026.php" style="color: #0066cc; font-weight: bold;">→ Quay lại trang Import</a></p>
        </div>
        
    </div>
</body>
</html>
