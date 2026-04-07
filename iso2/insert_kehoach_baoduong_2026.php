<?php
// Script nhập dữ liệu vào bảng ke_hoach_bao_duong_dinh_ky_iso
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();

// Xử lý nút stop
if (isset($_POST['stop'])) {
    $_SESSION['stop_import'] = true;
    echo json_encode(['status' => 'stopped']);
    exit;
}

// Xử lý nút Re-Update Exact Match
if (isset($_POST['update_exact'])) {
    require_once __DIR__ . '/config/database.php';
    try {
        $db = getDBConnection(true);
        
        // Lấy tất cả bản ghi exact match (nam=2026, nhomsc=CNC, có thietbi_id)
        $stmt = $db->query("SELECT k.id, k.so_serial, k.ten_thietbi, k.thietbi_id as old_thietbi_id
                           FROM ke_hoach_bao_duong_dinh_ky_iso k
                           WHERE k.nam = 2026 AND k.nhomsc = 'CNC' AND k.thietbi_id IS NOT NULL");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $updated = 0;
        $failed = 0;
        
        foreach ($records as $rec) {
            // Tìm lại thiết bị ID từ thietbi_iso dựa trên serial
            $stmtFind = $db->prepare("SELECT stt FROM thietbi_iso WHERE somay = :serial LIMIT 1");
            $stmtFind->execute([':serial' => trim($rec['so_serial'])]);
            $foundDevice = $stmtFind->fetch(PDO::FETCH_ASSOC);
            
            if ($foundDevice) {
                $newThietbiId = $foundDevice['stt'];
                
                // Update lại thiết bị ID
                $stmtUpdate = $db->prepare("UPDATE ke_hoach_bao_duong_dinh_ky_iso 
                                            SET thietbi_id = :thietbi_id 
                                            WHERE id = :id");
                $stmtUpdate->execute([
                    ':thietbi_id' => $newThietbiId,
                    ':id' => $rec['id']
                ]);
                
                $updated++;
            } else {
                $failed++;
            }
        }
        
        echo json_encode([
            'success' => true,
            'updated' => $updated,
            'failed' => $failed,
            'total' => count($records)
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Reset stop flag
$_SESSION['stop_import'] = false;

require_once __DIR__ . '/config/database.php';

// Dữ liệu cần nhập (tên thiết bị, số serial, qui_1, qui_2, qui_3, qui_4)
$data = [
    ['MBH', '11440856', 'TO', '', '', ''],
    ['MBH', '11484088', 'TO', '', '', ''],
    ['MPL 024', '10002832', 'TO', '', '', ''],
    ['MPL 024', '10003697', '', '', 'TO', ''],
    ['MPL 030', '218711', 'TO', '', '', ''],
    ['MPL 030', '10002380', '', '', 'TO', ''],
    ['UMT 007', '11511544', '', '', 'TO', ''],
    ['UMT 007', '11550804', '', '', 'TO', ''],
    ['UMT 008', '10022372', '', '', 'TO', ''],
    ['UMT 008', '10022886', 'TO', '', '', ''],
    ['XTU 004', 'AJG00068', 'TO', '', '', ''],
    ['XTU 004', '218421', 'TO', '', '', ''],
    ['XTU 004', '215996', 'TO', '', '', ''],
    ['XTU 002', '10010121', '', '', 'TO', ''],
    ['XTU 002', '10010448', '', '', 'TO', ''],
    ['XTU 011', '11242365', 'TO', '', '', ''],
    ['XTU 011', '11258572', 'TO', '', '', ''],
    ['XTU 011', '16313311', '', '', 'TO', ''],
    ['XTU 011', '16313312', '', '', 'TO', ''],
    ['QPC 003', '10003198', '', 'TO', '', ''],
    ['QPC 003', '10003201', '', 'TO', '', ''],
    ['QPC 201', '16297101', '', '', 'TO', ''],
    ['QPC 201', '16292557', '', '', 'TO', ''],
    ['PGR 021', '219464', '', '', 'TO', ''],
    ['PGR 021', '219465', '', '', 'TO', ''],
    ['PGR 021', '219466', 'TO', '', '', ''],
    ['PGR 021', '211803', 'TO', '', '', ''],
    ['PGR 021', '211683', 'TO', '', '', ''],
    ['PGR 020', '10003682', '', 'TO', '', ''],
    ['PGR 020', '10003683', '', 'TO', '', ''],
    ['PGR 020', '16249248', 'TO', '', '', ''],
    ['PGR 020', '16259538', 'TO', '', '', ''],
    ['FDR 019', '219448', '', 'TO', '', ''],
    ['FDR 020', '10003705', '', '', 'TO', ''],
    ['FDR 020', '10003628', 'TO', '', '', ''],
    ['FDR 020', '111668024', 'TO', '', '', ''],
    ['FDR 020', '111668028', 'TO', '', '', ''],
    ['FDI 001', '10008946', '', 'TO', '', ''],
    ['FDI 001', '10009015', '', 'TO', '', ''],
    ['ILS', '10010856', 'TO', '', '', ''],
    ['CTF 004', '10002931', 'TO', '', '', ''],
    ['CTF 004', '10003381', 'TO', '', '', ''],
    ['CTF 004', '11710091', '', '', 'TO', ''],
    ['CTF 201', '16322649', '', '', '', 'TO'],
    ['CTF 201', '16321925', '', '', '', 'TO'],
    ['HTU', '11011121', 'TO', '', '', ''],
    ['HTU', '11011122', 'TO', '', '', ''],
    ['MLT 101', '001185', 'TO', '', '', ''],
    ['MLT 101', '001186', 'TO', '', '', ''],
    ['MLT 102', '002085', 'TO', '', '', ''],
    ['MLT 102', '002086', 'TO', '', '', ''],
    ['MLT 002', '001412', '', 'TO', '', ''],
    ['MLT 002', '001413', '', '', '', 'TO'],
    ['MLT 002', '002017', '', '', '', 'TO'],
    ['MLT 002', '002018', '', '', '', 'TO'],
    ['TCU 101', '001191', '', '', '', 'TO'],
    ['TCU 101', '001192', '', '', '', 'TO'],
    ['TCU 002', '001616', '', '', '', 'TO'],
    ['TCU 002', '001706', '', '', '', 'TO'],
    ['GCL 101', '001164', 'TO', '', '', ''],
    ['GCL 101', '001161', '', '', 'TO', ''],
    ['GCL 101', '001163', 'TO', '', '', ''],
    ['GCL 001', '001344', 'TO', '', '', ''],
    ['GCL 001', '001345', 'TO', '', '', ''],
    ['GCL 001', '001682', 'TO', '', '', ''],
    ['GCL 001', '001710', '', 'TO', '', ''],
    ['GCL 001', '001942', '', 'TO', '', ''],
    ['GCL 001', '002006', 'TO', '', '', ''],
    ['QPT 101', '001154', 'TO', '', '', ''],
    ['QPT 101', '001158', 'TO', '', '', ''],
    ['QPT 101', '001153', '', 'TO', '', ''],
    ['QPT 101', '001157', 'TO', '', '', ''],
    ['QPT 001', '001489', 'TO', '', '', ''],
    ['QPT 001', '001719', 'TO', '', '', ''],
    ['QPT 001', '002003', '', 'TO', '', ''],
    ['QPT 001', '002004', 'TO', '', '', ''],
    ['FDT 101', '001166', 'TO', '', '', ''],
    ['FDT 101', '001167', '', 'TO', '', ''],
    ['FDT 101', '001550', 'TO', '', '', ''],
    ['FDT 001', '001169', 'TO', '', '', ''],
    ['IFM 105', '001327', '', '', '', 'TO'],
    ['IFM 105', '001328', '', '', '', 'TO'],
    ['IFM 002', '001462', '', '', '', 'TO'],
    ['IFM 002', '001510', '', '', '', 'TO'],
    ['CFT 101', '001171', '', '', '', 'TO'],
    ['CFT 101', '001175', '', '', '', 'TO'],
    ['CFT 104', '002087', '', 'TO', '', ''],
    ['CFT 104', '002088', '', 'TO', '', ''],
    ['CFT 001', '001743', 'TO', '', '', ''],
    ['CFT 001', '001744', 'TO', '', '', ''],
    ['CFT 001', '001869', 'TO', '', '', ''],
    ['CFT 001', '001918', 'TO', '', '', ''],
    ['RAS 002', '003021', '', '', '', 'TO'],
    ['RAS 002', '003022', '', '', '', 'TO'],
    ['PO', 'PO-K-42-175/120', 'TO', '', '', ''],
    ['CCL 2-3/4"', 'K6-07', '', '', '', 'TO'],
    ['CCL 2-3/4"', 'K6-08', '', '', '', 'TO'],
    ['CCL 2-3/4"', '73-01', '', '', '', 'TO'],
    ['CCL 2-3/4"', '73-04', '', '', '', 'TO'],
    ['GR 2-3/4"', 'L6-03', '', 'TO', '', ''],
    ['GR 2-3/4"', 'L6-04', '', 'TO', '', ''],
    ['GR 2-3/4"', '86-02', '', 'TO', '', ''],
    ['GR 2-3/4"', '86-03', '', 'TO', '', ''],
    ['RIB 2-3/4"', 'A7-03', 'TO', '', '', ''],
    ['RIB 2-3/4"', 'A7-04', '', '', 'TO', ''],
    ['RIB 2-3/4"', 'IWO 40-02', 'TO', '', '', ''],
    ['RIB 2-3/4"', 'IWO 40-03', '', 'TO', '', ''],
    ['RIB 2-3/4"', 'J8JE6-02', 'TO', '', '', ''],
    ['RIB 2-3/4"', 'JC1I7-01', '', '', 'TO', ''],
    ['RIB 3-1/8"', '909101', '', '', '', 'TO'],
    ['RIB 3-1/8"', '909102', '', '', '', 'TO'],
    ['MIT- 40F', '10011639', 'TO', '', '', ''],
    ['MIT- 40F', '10011661', 'TO', '', '', ''],
    ['MIT- 60F', '10011613', '', 'TO', '', ''],
    ['MIT- 60F', '10011660', '', 'TO', '', ''],
    ['MAC- 40F', '3301', '', 'TO', '', ''],
    ['MAC- 40F', '3102', '', 'TO', '', ''],
    ['STIP_ CPFC', 'CP 26591', 'TO', '', '', ''],
    ['STIP_ CPFC', 'CP 25349', '', '', 'TO', ''],
    ['STIP_ CPFC', 'CP 21782', '', '', 'TO', ''],
    ['STIP_ CPFC', 'CP 25314', 'TO', '', '', ''],
    ['STIP_ CPFC', 'CP-26413', '', 'TO', '', ''],
    ['STIP_ CPFC', 'CP-20183357', 'TO', '', '', ''],
    ['STIP_ CPFC', 'CP-20183358', 'TO', '', '', ''],
    ['HIP 002', '001674', '', 'TO', '', ''],
    ['HIP 002', '001675', 'TO', '', '', ''],
    ['HIP 002', '001713', '', 'TO', '', ''],
    ['HIP 002', '001174', '', 'TO', '', ''],
    ['Wireline Mast 80ft', 'WM', 'TO', '', '', ''],
];

echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
    .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h2 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
    .success { color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; color: #856404; }
    .error { color: #721c24; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 10px 0; color: #0c5460; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; font-size: 12px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; position: sticky; top: 0; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    tr:hover { background-color: #e8f5e9; }
    .stats { display: flex; gap: 20px; margin: 20px 0; }
    .stat-box { flex: 1; padding: 15px; border-radius: 5px; text-align: center; }
    .stat-success { background: #d4edda; color: #155724; }
    .stat-warning { background: #fff3cd; color: #856404; }
    .stat-box h3 { margin: 0; font-size: 32px; }
    .stat-box p { margin: 5px 0 0 0; }
    .btn { padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 14px; margin: 5px; }
    .btn-success { background: #28a745; color: white; }
    .btn-success:hover { background: #218838; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-cancel { background: #6c757d; color: white; }
</style>";

echo "<div class='container'>";
echo "<h2>🔧 Nhập Kế Hoạch Bảo Dưỡng Định Kỳ 2026 (CNC)</h2>";
echo "<p style='color: #666;'>Tổng số bản ghi: <b>" . count($data) . "</b></p>";
echo "<p>
<a href='check_data_kehoach_2026.php' style='color: #2196F3; font-weight: bold; text-decoration: none; margin-right: 15px;'>🔍 Kiểm tra dữ liệu</a>
<a href='delete_kehoach_baoduong_2026.php' style='color: #dc3545; font-weight: bold; text-decoration: none; margin-right: 15px;'>🗑️ Xóa dữ liệu</a>
<a href='delete_null_nhomsc_2026.php' style='color: #ff6b6b; font-weight: bold; text-decoration: none;'>🗑️ Xóa nhomsc=NULL</a>
</p>";

try {
    $db = getDBConnection(true);
    set_time_limit(300);
    
    // ============================================================
    // XỬ LÝ INSERT (khi user đã confirm)
    // ============================================================
    if (isset($_POST['confirm_insert'])) {
        // Setup output buffering cho real-time progress
        if (ob_get_level()) ob_end_clean();
        ob_start();
        ob_implicit_flush(true);
        
        echo "<div class='info'>";
        echo "<h3>⏳ Đang thực hiện import...</h3>";
        echo "</div>";
        
        echo "<div id='progress' style='background: #e0e0e0; height: 30px; border-radius: 5px; overflow: hidden; margin: 20px 0;'>";
        echo "<div id='progressBar' style='background: #4CAF50; height: 100%; width: 0%; transition: width 0.3s; text-align: center; line-height: 30px; color: white; font-weight: bold;'></div>";
        echo "</div>";
        echo "<p id='status'>Đang chuẩn bị...</p>";
        
        // Lấy mapping từ POST
        $serialMapping = json_decode($_POST['serial_mapping'], true);
        
        $sql = "INSERT INTO ke_hoach_bao_duong_dinh_ky_iso 
                (ten_thietbi, so_serial, nhomsc, qui_1, qui_2, qui_3, qui_4, nam, thietbi_id, created_by, created_at) 
                VALUES (:ten_thietbi, :so_serial, :nhomsc, :qui_1, :qui_2, :qui_3, :qui_4, 2026, :thietbi_id, 'system', NOW())";
        $stmt = $db->prepare($sql);
        
        $success = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($data as $index => $row) {
            try {
                $serial = trim($row[1]);
                $thietbiId = isset($serialMapping[$serial]) ? $serialMapping[$serial]['thietbi_id'] : null;
                
                $stmt->execute([
                    ':ten_thietbi' => $row[0],
                    ':so_serial' => $row[1],
                    ':nhomsc' => 'CNC',
                    ':qui_1' => $row[2],
                    ':qui_2' => $row[3],
                    ':qui_3' => $row[4],
                    ':qui_4' => $row[5],
                    ':thietbi_id' => $thietbiId
                ]);
                $success++;
                
                $percent = round((($index + 1) / count($data)) * 100);
                echo "<script>
                    document.getElementById('progressBar').style.width = '{$percent}%';
                    document.getElementById('progressBar').innerText = '{$percent}%';
                    document.getElementById('status').innerText = 'Đã import: " . ($index + 1) . "/" . count($data) . " - {$row[0]} ({$serial})';
                </script>";
                if (ob_get_level()) ob_flush();
                flush();
                
            } catch (PDOException $e) {
                $failed++;
                $errors[] = ['row' => $index + 1, 'device' => $row[0], 'serial' => $row[1], 'error' => $e->getMessage()];
            }
        }
        
        echo "<div class='success'>";
        echo "<h3>✅ Hoàn tất import!</h3>";
        echo "<p>Thành công: <strong>$success</strong> | Thất bại: <strong>$failed</strong></p>";
        echo "<p><a href='check_data_kehoach_2026.php' style='color: #155724; font-weight: bold;'>→ Kiểm tra dữ liệu vừa import</a></p>";
        echo "<p><a href='kehoachbaoduongdinhky.php?nam=2026' style='color: #155724; font-weight: bold;'>→ Xem kế hoạch bảo dưỡng 2026</a></p>";
        echo "</div>";
        
        if (!empty($errors)) {
            echo "<div class='error'><h3>⚠ Chi tiết lỗi:</h3><table><tr><th>Dòng</th><th>Thiết bị</th><th>Serial</th><th>Lỗi</th></tr>";
            foreach ($errors as $err) {
                echo "<tr><td>{$err['row']}</td><td>" . htmlspecialchars($err['device']) . "</td><td>" . htmlspecialchars($err['serial']) . "</td><td>" . htmlspecialchars($err['error']) . "</td></tr>";
            }
            echo "</table></div>";
        }
        
        echo "</div>"; // container
        exit;
    }
    
    // ============================================================
    // BƯỚC 1: SCAN PHASE - Tìm TB ID cho tất cả serial
    // ============================================================
    echo "<div class='info'>";
    echo "<h3>🔍 BƯỚC 1: Quét Database để tìm Thiết Bị ID</h3>";
    echo "<p><i>Đang tìm kiếm TB ID cho " . count($data) . " serial numbers...</i></p>";
    echo "</div>";
    
    // Thu thập tất cả serial
    $allSerials = array_map(function($row) { return trim($row[1]); }, $data);
    $serialMapping = []; // serial => [thietbi_id, match_type, db_serial, mavt]
    
    // Query 1: Exact match - somay = serial
    $placeholders = implode(',', array_fill(0, count($allSerials), '?'));
    $exactSql = "SELECT stt, somay, mavt, tenvt FROM thietbi_iso WHERE somay IN ($placeholders)";
    $stmtExact = $db->prepare($exactSql);
    $stmtExact->execute($allSerials);
    $exactResults = $stmtExact->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($exactResults as $device) {
        $serialMapping[$device['somay']] = [
            'thietbi_id' => $device['stt'],
            'match_type' => 'exact',
            'db_serial' => $device['somay'],
            'mavt' => $device['mavt'],
            'tenvt' => $device['tenvt']
        ];
    }
    
    // Query 2: Normalized match - cho những serial chưa tìm thấy
    $notFoundSerials = [];
    foreach ($allSerials as $serial) {
        if (!isset($serialMapping[$serial])) {
            $notFoundSerials[] = $serial;
        }
    }
    
    if (!empty($notFoundSerials)) {
        foreach ($notFoundSerials as $serial) {
            $normalizedSql = "SELECT stt, somay, mavt, tenvt FROM thietbi_iso 
                             WHERE REPLACE(REPLACE(somay, ' ', ''), '-', '') = REPLACE(REPLACE(?, ' ', ''), '-', '') 
                             LIMIT 1";
            $stmtNorm = $db->prepare($normalizedSql);
            $stmtNorm->execute([$serial]);
            $normResult = $stmtNorm->fetch(PDO::FETCH_ASSOC);
            
            if ($normResult) {
                $serialMapping[$serial] = [
                    'thietbi_id' => $normResult['stt'],
                    'match_type' => 'normalized',
                    'db_serial' => $normResult['somay'],
                    'mavt' => $normResult['mavt'],
                    'tenvt' => $normResult['tenvt']
                ];
            }
        }
    }
    
    // Đếm kết quả
    $foundCount = count($serialMapping);
    $notFoundCount = count($data) - $foundCount;
    
    // ============================================================
    // BƯỚC 2: HIỂN THỊ PREVIEW
    // ============================================================
    echo "<div class='stats'>";
    echo "<div class='stat-box stat-success'>";
    echo "<h3>$foundCount</h3>";
    echo "<p>✓ Tìm được TB ID</p>";
    echo "</div>";
    echo "<div class='stat-box stat-warning'>";
    echo "<h3>$notFoundCount</h3>";
    echo "<p>⚠ Không tìm thấy TB ID</p>";
    echo "</div>";
    echo "</div>";
    
    if ($notFoundCount > 0) {
        echo "<div class='warning'>";
        echo "<h3>⚠ Cảnh báo</h3>";
        echo "<p>Có <strong>$notFoundCount serial</strong> không tìm thấy trong database thietbi_iso.</p>";
        echo "<p>Các bản ghi này sẽ được import KHÔNG CÓ TB ID (thietbi_id = NULL)</p>";
        echo "</div>";
    }
    
    echo "<h3>📋 PREVIEW: Dữ liệu sẽ được import</h3>";
    echo "<p style='color: #666; font-size: 13px;'><i>Kiểm tra kỹ trước khi nhấn 'Xác nhận Import'</i></p>";
    
    echo "<div style='max-height: 600px; overflow-y: auto; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<table>";
    echo "<tr>
            <th>#</th>
            <th>Tên TB (nhập)</th>
            <th>Serial (nhập)</th>
            <th>TB ID</th>
            <th>Match Type</th>
            <th>Serial (DB)</th>
            <th>Mã VT</th>
            <th>Q1</th>
            <th>Q2</th>
            <th>Q3</th>
            <th>Q4</th>
          </tr>";
    
    $exactCount = 0;
    $normalizedCount = 0;
    $noMatchCount = 0;
    
    foreach ($data as $index => $row) {
        $serial = trim($row[1]);
        $hasMatch = isset($serialMapping[$serial]);
        
        if ($hasMatch) {
            $map = $serialMapping[$serial];
            $matchIcon = $map['match_type'] == 'exact' 
                ? '<span style="color: #28a745; font-weight: bold;">✓✓ Exact</span>' 
                : '<span style="color: #ffc107; font-weight: bold;">≈ Normalized</span>';
            $bgColor = $map['match_type'] == 'exact' ? '#f1f9f1' : '#fffbf0';
            
            if ($map['match_type'] == 'exact') $exactCount++;
            else $normalizedCount++;
        } else {
            $matchIcon = '<span style="color: #dc3545;">✗ No Match</span>';
            $bgColor = '#fff3cd';
            $noMatchCount++;
        }
        
        echo "<tr style='background-color: $bgColor;'>";
        echo "<td>" . ($index + 1) . "</td>";
        echo "<td>" . htmlspecialchars($row[0]) . "</td>";
        echo "<td><strong>" . htmlspecialchars($serial) . "</strong></td>";
        echo "<td>" . ($hasMatch ? "<strong style='color: #0066cc;'>{$map['thietbi_id']}</strong>" : '<span style="color: #999;">-</span>') . "</td>";
        echo "<td>$matchIcon</td>";
        echo "<td>" . ($hasMatch ? htmlspecialchars($map['db_serial']) : '-') . "</td>";
        echo "<td>" . ($hasMatch ? htmlspecialchars($map['mavt']) : '-') . "</td>";
        echo "<td>" . ($row[2] ? '✓' : '') . "</td>";
        echo "<td>" . ($row[3] ? '✓' : '') . "</td>";
        echo "<td>" . ($row[4] ? '✓' : '') . "</td>";
        echo "<td>" . ($row[5] ? '✓' : '') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "</div>";
    
    echo "<div style='margin: 20px 0; padding: 15px; background: #e7f3ff; border-left: 4px solid #2196F3;'>";
    echo "<h4 style='margin-top: 0;'>📊 Thống kê Match:</h4>";
    echo "<ul style='margin: 5px 0;'>";
    echo "<li><strong style='color: #28a745;'>✓✓ Exact Match:</strong> $exactCount bản ghi (serial khớp chính xác)</li>";
    echo "<li><strong style='color: #ffc107;'>≈ Normalized Match:</strong> $normalizedCount bản ghi (serial khớp sau khi bỏ space/dash)</li>";
    echo "<li><strong style='color: #dc3545;'>✗ No Match:</strong> $noMatchCount bản ghi (không tìm thấy TB ID)</li>";
    echo "</ul>";
    echo "</div>";
    
    // ============================================================
    // BƯỚC 3: NÚT XÁC NHẬN IMPORT
    // ============================================================
    echo "<div style='background: #28a745; color: white; padding: 20px; margin: 20px 0; border-radius: 5px; text-align: center;'>";
    echo "<h3 style='margin-top: 0;'>✓ BƯỚC 2: Xác nhận Import</h3>";
    echo "<p style='font-size: 16px;'>Dữ liệu đã được quét và chuẩn bị sẵn sàng.</p>";
    echo "<p><strong>Sẽ import: " . count($data) . " bản ghi</strong></p>";
    echo "<p><strong>Có TB ID: $foundCount</strong> | <strong>Không có TB ID: $notFoundCount</strong></p>";
    
    echo "<form method='post' style='margin-top: 20px;' onsubmit='return confirm(\"Bạn có chắc chắn muốn import " . count($data) . " bản ghi?\");'>";
    echo "<input type='hidden' name='serial_mapping' value='" . htmlspecialchars(json_encode($serialMapping)) . "'>";
    echo "<button type='submit' name='confirm_insert' class='btn btn-success'>✓ XÁC NHẬN IMPORT " . count($data) . " BẢN GHI</button>";
    echo "<button type='button' class='btn btn-cancel' onclick='history.back()'>✗ Hủy bỏ</button>";
    echo "</form>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Lỗi:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div>";
?>
