<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/ActivityLogger.php';

requireAuth();

// Check permissions
if (!hasPermission('kehoach_kiemdinh.view')) {
    header('Location: /iso2/index.php?error=no_permission');
    exit;
}

$db = getDBConnection();
$logger = new ActivityLogger($db);
$success = '';
$error = '';

// Xử lý AJAX - Lưu từng thiết bị
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_save'])) {
    ob_clean();
    header('Content-Type: application/json');
    
    try {
        $stt = $_POST['stt'] ?? '';
        $thangThuchien = $_POST['thang_thuchien'] ?? '';
        $thangDot2 = $_POST['thang_dot2'] ?? '';
        $donviThuchien = $_POST['donvi_thuchien'] ?? '';
        $chusohuuInput = $_POST['chusohuu'] ?? '';
        
        if (empty($stt)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu STT thiết bị']);
            exit;
        }
        
        // Lấy thông tin thiết bị từ master table
        $stmtTB = $db->prepare("SELECT * FROM thietbihckd_iso WHERE stt = :stt LIMIT 1");
        $stmtTB->execute([':stt' => $stt]);
        $thietbi = $stmtTB->fetch(PDO::FETCH_ASSOC);
        
        if (!$thietbi) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thiết bị']);
            exit;
        }
        
        $db->beginTransaction();
        
        // Cập nhật chủ sở hữu vào master table nếu có thay đổi
        if ($chusohuuInput !== '') {
            $stmtUpdate = $db->prepare("UPDATE thietbihckd_iso SET chusohuu = :chusohuu WHERE stt = :stt");
            $stmtUpdate->execute([':chusohuu' => $chusohuuInput, ':stt' => $stt]);
        }
        
        // Xóa kế hoạch cũ của thiết bị này trong năm 2026 (join qua stt)
        $stmtDelete = $db->prepare("DELETE FROM kehoach_kiemdinh_2026_iso WHERE stt = :stt AND nam_kehoach = 2026");
        $stmtDelete->execute([':stt' => $stt]);
        
        // Nếu có chọn ít nhất 1 tháng, thêm kế hoạch mới
        if (!empty($thangThuchien)) {
            $stmt = $db->prepare("
                INSERT INTO kehoach_kiemdinh_2026_iso 
                (stt, ten_thietbi, ky_hieu, hang_sanxuat, so_may, thang_thuchien, thang_dot2, donvi_thuchien, ghichu, nam_kehoach)
                VALUES (:stt, :ten_thietbi, :ky_hieu, :hang_sanxuat, :so_may, :thang_thuchien, :thang_dot2, :donvi_thuchien, :ghichu, 2026)
            ");
            
            $stmt->execute([
                ':stt' => $stt,
                ':ten_thietbi' => $thietbi['tenthietbi'],
                ':ky_hieu' => $thietbi['tenviettat'] ?? '',
                ':hang_sanxuat' => $thietbi['hangsx'] ?? '',
                ':so_may' => $thietbi['somay'] ?? '',
                ':thang_thuchien' => $thangThuchien ? (int)$thangThuchien : null,
                ':thang_dot2' => $thangDot2 ? (int)$thangDot2 : null,
                ':donvi_thuchien' => $donviThuchien,
                ':ghichu' => 'Mã vật tư: ' . ($thietbi['mavattu'] ?? '') . ' - Chủ sở hữu: ' . ($thietbi['chusohuu'] ?? '')
            ]);
            
            // Log activity
            $logger->log(
                'kehoach_kiemdinh_2026_iso',
                'INSERT',
                null,
                null,
                [
                    'stt' => $stt,
                    'ten_thietbi' => $thietbi['tenthietbi'],
                    'mavattu' => $thietbi['mavattu'] ?? '',
                    'thang_thuchien' => $thangThuchien ? (int)$thangThuchien : null,
                    'thang_dot2' => $thangDot2 ? (int)$thangDot2 : null,
                    'nam_kehoach' => 2026
                ]
            );
        }
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Đã lưu thành công!']);
        
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
    exit;
}

// Xử lý AJAX - Xóa kế hoạch của thiết bị
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_delete'])) {
    ob_clean();
    header('Content-Type: application/json');
    
    try {
        $stt = $_POST['stt'] ?? '';
        
        if (empty($stt)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu STT thiết bị']);
            exit;
        }
        
        $db->beginTransaction();
        
        // Xóa tất cả kế hoạch của thiết bị này trong năm 2026
        $stmtDelete = $db->prepare("DELETE FROM kehoach_kiemdinh_2026_iso WHERE stt = :stt AND nam_kehoach = 2026");
        $stmtDelete->execute([':stt' => $stt]);
        
        $deletedCount = $stmtDelete->rowCount();
        
        // Log activity
        $logger->log(
            'kehoach_kiemdinh_2026_iso',
            'DELETE',
            null,
            null,
            [
                'stt' => $stt,
                'deleted_count' => $deletedCount,
                'nam_kehoach' => 2026
            ]
        );
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => "Đã xóa {$deletedCount} kế hoạch thành công!"]);
        
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
    exit;
}

// AJAX: Bulk delete multiple equipment plans
if (isset($_POST['ajax_bulk_delete'])) {
    ob_clean(); // Clear any output buffer
    header('Content-Type: application/json');
    
    $sttList = $_POST['stt_list'] ?? [];
    
    if (empty($sttList) || !is_array($sttList)) {
        echo json_encode(['success' => false, 'message' => 'Không có thiết bị nào được chọn']);
        exit;
    }
    
    try {
        $db->beginTransaction();
        
        $deletedCount = 0;
        foreach ($sttList as $stt) {
            // Get device info from master table
            $stmtInfo = $db->prepare("SELECT tenthietbi FROM thietbihckd_iso WHERE stt = :stt LIMIT 1");
            $stmtInfo->execute([':stt' => $stt]);
            $deviceInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);
            
            if ($deviceInfo) {
                // Delete all planning records for this device
                $stmtDelete = $db->prepare("DELETE FROM kehoach_kiemdinh_2026_iso WHERE stt = :stt AND nam_kehoach = 2026");
                $stmtDelete->execute([':stt' => $stt]);
                
                $rowsDeleted = $stmtDelete->rowCount();
                if ($rowsDeleted > 0) {
                    $deletedCount++;
                    
                    // Log activity
                    $logger->log(
                        'kehoach_kiemdinh_2026',
                        'delete_bulk',
                        (int)$stt,
                        null,
                        ['ten_thietbi' => $deviceInfo['tenthietbi'], 'rows_deleted' => $rowsDeleted]
                    );
                }
            }
        }
        
        $db->commit();
        
        if ($deletedCount > 0) {
            echo json_encode(['success' => true, 'message' => "Đã xóa kế hoạch của {$deletedCount} thiết bị"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không có kế hoạch nào để xóa. Các thiết bị đã chọn chưa có kế hoạch.']);
        }
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        exit;
    }
}

// Xử lý POST - Lưu kế hoạch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_plan'])) {
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("
            INSERT INTO kehoach_kiemdinh_2026_iso 
            (stt, ten_thietbi, ky_hieu, hang_sanxuat, so_may, thang_thuchien, thang_dot2, donvi_thuchien, ghichu, nam_kehoach)
            VALUES (:stt, :ten_thietbi, :ky_hieu, :hang_sanxuat, :so_may, :thang_thuchien, :thang_dot2, :donvi_thuchien, :ghichu, 2026)
        ");
        
        $count = 0;
        foreach ($_POST['thietbi'] ?? [] as $stt => $selectedMonths) {
            // Cập nhật chủ sở hữu vào master table nếu có
            if (isset($_POST['chusohuu'][$stt]) && $_POST['chusohuu'][$stt] !== '') {
                $stmtUpdateCSH = $db->prepare("UPDATE thietbihckd_iso SET chusohuu = :chusohuu WHERE stt = :stt");
                $stmtUpdateCSH->execute([':chusohuu' => $_POST['chusohuu'][$stt], ':stt' => $stt]);
            }
            
            // Lấy thông tin thiết bị từ master table (join qua stt)
            $stmtTB = $db->prepare("SELECT * FROM thietbihckd_iso WHERE stt = :stt LIMIT 1");
            $stmtTB->execute([':stt' => $stt]);
            $thietbi = $stmtTB->fetch(PDO::FETCH_ASSOC);
            
            if (!$thietbi || empty($selectedMonths) || !is_array($selectedMonths)) continue;
            
            // Xóa kế hoạch cũ của thiết bị này
            $stmtDelete = $db->prepare("DELETE FROM kehoach_kiemdinh_2026_iso WHERE stt = :stt AND nam_kehoach = 2026");
            $stmtDelete->execute([':stt' => $stt]);
            
            // Sắp xếp tháng đã chọn (nhỏ -> lớn)
            $sortedMonths = array_map('intval', $selectedMonths);
            sort($sortedMonths);
            
            // Tháng nhỏ hơn -> thang_thuchien, tháng lớn hơn -> thang_dot2
            $thang1 = isset($sortedMonths[0]) ? $sortedMonths[0] : null;
            $thang2 = isset($sortedMonths[1]) ? $sortedMonths[1] : null;
            
            // Lưu kế hoạch
            $stmt->execute([
                ':stt' => $stt,
                ':ten_thietbi' => $thietbi['tenthietbi'],
                ':ky_hieu' => $thietbi['tenviettat'] ?? '',
                ':hang_sanxuat' => $thietbi['hangsx'] ?? '',
                ':so_may' => $thietbi['somay'] ?? '',
                ':thang_thuchien' => $thang1,
                ':thang_dot2' => $thang2,
                ':donvi_thuchien' => $_POST['donvi_thuchien'][$stt] ?? '',
                ':ghichu' => 'Mã vật tư: ' . ($thietbi['mavattu'] ?? '') . ' - Chủ sở hữu: ' . ($thietbi['chusohuu'] ?? '')
            ]);
            
            // Log activity
            $logger->log(
                'kehoach_kiemdinh_2026_iso',
                'INSERT',
                null,
                null,
                [
                    'stt' => $stt,
                    'ten_thietbi' => $thietbi['tenthietbi'],
                    'mavattu' => $thietbi['mavattu'] ?? '',
                    'thang_thuchien' => (int)$selectedMonth,
                    'nam_kehoach' => 2026
                ]
            );
            
            $count++;
        }
        
        $db->commit();
        $success = "Đã lưu thành công {$count} kế hoạch kiểm định năm 2026!";
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Lỗi khi lưu dữ liệu: " . $e->getMessage();
        error_log("Error saving plan: " . $e->getMessage());
    }
}

// Lấy danh sách thiết bị
$search = $_GET['search'] ?? '';
$loaitb = $_GET['loaitb'] ?? '';
$bophansh = $_GET['bophansh'] ?? '';
$kehoachFilter = $_GET['kehoach'] ?? ''; // 'all', 'co', 'chua'
$sortOrder = $_GET['sort'] ?? 'default'; // 'default', 'doith'
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Nếu có search hoặc chọn sắp xếp Đội TH thì hiển thị tất cả (không phân trang)
$showAll = !empty($search) || $sortOrder === 'doith';

// Thứ tự số máy của Đội TH
$doiThOrder = [
    'EA15263', 'EA15268', 'EA15260', '4305', 'PJ1964', 'RG2817', 'RG2818', 'RG2819',
    '14939', '15262', '15269', '15195', '16907', '1520', '1687',
    '33710104', '33710108', '47510141', '60480178', '62340171', '260', '1',
    '02(01694)', '03(01695)', '5',
    'VSP-02 x 2 cái VSP-03 x 1 cái', 'VSP-03',
    'VSP-02 x 2 cái VSP-03 x 1 cái', 'Máy nén khí di động', 'Bể test áp suất',
    'WLU-16', 'WLU-18 x 2 cái', 'WLU-18', 'WLU-17', 'WLU-21',
    'WLU – 10.1', 'WLU – 10.2', 'WLU – 11.1', 'WLU – 11.2',
    'WLU – 12.1', 'WLU – 12.2', 'WLU – 22.1', 'WLU – 22.2', 'WLU – 22.4'
];

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(mavattu LIKE :search OR tenthietbi LIKE :search2 OR somay LIKE :search3 OR chusohuu LIKE :search4)";
    $params[':search'] = "%$search%";
    $params[':search2'] = "%$search%";
    $params[':search3'] = "%$search%";
    $params[':search4'] = "%$search%";
}

if ($loaitb) {
    $where[] = "loaitb = :loaitb";
    $params[':loaitb'] = $loaitb;
}

if ($bophansh) {
    $where[] = "bophansh = :bophansh";
    $params[':bophansh'] = $bophansh;
}

$whereClause = implode(' AND ', $where);

// Filter theo tình trạng kế hoạch
$havingClause = '';
if ($kehoachFilter === 'co') {
    $havingClause = 'HAVING planned_months IS NOT NULL';
} elseif ($kehoachFilter === 'chua') {
    $havingClause = 'HAVING planned_months IS NULL';
}

// Đếm tổng số thiết bị
$countSql = "SELECT COUNT(DISTINCT t.stt) as total
             FROM thietbihckd_iso t
             WHERE $whereClause";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRecords / $limit);

// Không phân trang nếu có search hoặc sắp xếp Đội TH
$limitClause = $showAll ? '' : "LIMIT $limit OFFSET $offset";

// Xác định ORDER BY clause
$orderByClause = ($sortOrder === 'doith') ? 'ORDER BY t.stt' : 'ORDER BY first_month ASC, t.loaitb, t.tenthietbi';

$sql = "SELECT t.*, 
        GROUP_CONCAT(DISTINCT k.thang_thuchien ORDER BY k.thang_thuchien) as planned_months,
        GROUP_CONCAT(DISTINCT k.thang_dot2 ORDER BY k.thang_dot2) as planned_months_dot2,
        MIN(CAST(k.thang_thuchien AS UNSIGNED)) as first_month,
        MAX(k.donvi_thuchien) as donvi_thuchien,
        GROUP_CONCAT(DISTINCT MONTH(h.ngayhc) ORDER BY h.ngayhc) as inspected_months,
        COUNT(DISTINCT h.stt) as inspection_count
        FROM thietbihckd_iso t
        LEFT JOIN kehoach_kiemdinh_2026_iso k ON t.stt = k.stt AND k.nam_kehoach = 2026
        LEFT JOIN hosohckd_iso h ON (t.mavattu = h.tenmay OR t.somay = h.tenmay) 
            AND YEAR(h.ngayhc) = 2026
        WHERE $whereClause
        GROUP BY t.stt
        $havingClause
        $orderByClause
        $limitClause";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$thietbiList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sắp xếp theo thứ tự Đội TH nếu được chọn
if ($sortOrder === 'doith') {
    // Tạo map index cho sorting
    $orderMap = array_flip($doiThOrder);
    
    usort($thietbiList, function($a, $b) use ($orderMap) {
        $somayA = $a['somay'] ?? '';
        $somayB = $b['somay'] ?? '';
        
        $indexA = isset($orderMap[$somayA]) ? $orderMap[$somayA] : 9999;
        $indexB = isset($orderMap[$somayB]) ? $orderMap[$somayB] : 9999;
        
        return $indexA <=> $indexB;
    });
}

// Lấy danh sách loại TB và bộ phận
$loaiTBList = $db->query("SELECT DISTINCT loaitb FROM thietbihckd_iso WHERE loaitb != '' ORDER BY loaitb")->fetchAll(PDO::FETCH_COLUMN);
$boPhanList = $db->query("SELECT DISTINCT bophansh FROM thietbihckd_iso WHERE bophansh != '' ORDER BY bophansh")->fetchAll(PDO::FETCH_COLUMN);

// Set title for header
$title = 'Danh mục thiết bị, mẫu chuẩn/vật chuẩn yêu cầu hiệu chuẩn/kiểm định, kiểm tra - Năm 2026';
require_once __DIR__ . '/views/layouts/header.php';
?>

<style>
    h1 { color: #333; margin-bottom: 20px; font-size: 24px; }
        .alert { padding: 12px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .filters { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .filters input, .filters select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .filters button { padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filters button:hover { background: #0056b3; }
        .table-wrapper { overflow-x: auto; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 13px; word-wrap: break-word; white-space: normal; }
        th { background: #4CAF50; color: white; position: sticky; top: 0; z-index: 10; }
        .month-cell { text-align: center; width: auto; cursor: pointer; user-select: none; position: relative; }
        .month-header { text-align: center; background: #2196F3; width: auto; }
        .month-selected { background: #2196F3 !important; }
        .month-selected-dot2 { background: #FF9800 !important; }
        .month-inspected { background: #d4edda !important; border: 2px solid #28a745 !important; }
        .month-cell .check-mark { 
            position: absolute; 
            top: 2px; 
            right: 2px; 
            color: #28a745; 
            font-size: 14px; 
            font-weight: bold; 
            pointer-events: none;
        }
        .month-cell input[type="checkbox"] { opacity: 0; position: absolute; pointer-events: none; }
        .row-checkbox { cursor: pointer; width: 18px; height: 18px; }
        #selectAllCheckbox { cursor: pointer; width: 18px; height: 18px; }
        input[type="text"].small { width: 150px; padding: 4px; font-size: 12px; white-space: nowrap; }
        .month-tooltip {
            position: fixed;
            background: #333;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 10000;
            pointer-events: none;
            white-space: nowrap;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .btn-save-single { 
            padding: 6px 12px; 
            background: #28a745; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 16px;
            transition: background 0.3s;
        }
        .btn-save-single:hover { background: #218838; }
        .btn-save-single:disabled { background: #6c757d; cursor: not-allowed; }
        input[type="text"].small { width: 100%; max-width: 180px; padding: 4px; font-size: 12px; box-sizing: border-box; }
        td:has(input.small) { white-space: nowrap; }
        .btn-save { padding: 8px 16px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold; position: sticky; bottom: 20px; z-index: 100; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .btn-save:hover { background: #218838; }
        .back-link { display: inline-block; margin-bottom: 15px; color: #007bff; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        tr:nth-child(even) { background: #f9f9f9; }
        tr:hover { background: #f0f0f0; }
        .selected { background: #e3f2fd !important; }
        .action-buttons { margin-top: 20px; display: flex; gap: 10px; position: sticky; bottom: 10px; z-index: 100; background: white; padding: 10px 15px; border-radius: 6px; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); }
        .checkbox-label { display: flex; align-items: center; gap: 5px; margin-bottom: 15px; }
</style>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <a href="/iso2/thietbihckd.php" class="back-link">← Quay lại danh sách thiết bị</a>
    
    <h1 class="text-2xl font-bold mb-4">📅 Kế hoạch Kiểm định Thiết bị Năm 2026</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">✗ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Bộ lọc -->
        <form method="GET" class="filters">
            <input type="text" name="search" placeholder="Tìm kiếm thiết bị, số máy, chủ sở hữu..." value="<?= htmlspecialchars($search) ?>">
            
            <select name="loaitb">
                <option value="">-- Tất cả loại TB --</option>
                <?php foreach ($loaiTBList as $loai): ?>
                    <option value="<?= htmlspecialchars($loai) ?>" <?= $loaitb === $loai ? 'selected' : '' ?>>
                        <?= htmlspecialchars($loai) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="bophansh">
                <option value="">-- Tất cả bộ phận --</option>
                <?php foreach ($boPhanList as $bp): ?>
                    <option value="<?= htmlspecialchars($bp) ?>" <?= $bophansh === $bp ? 'selected' : '' ?>>
                        <?= htmlspecialchars($bp) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="kehoach">
                <option value="">-- Tình trạng kế hoạch --</option>
                <option value="co" <?= $kehoachFilter === 'co' ? 'selected' : '' ?>>Đã có kế hoạch</option>
                <option value="chua" <?= $kehoachFilter === 'chua' ? 'selected' : '' ?>>Chưa có kế hoạch</option>
            </select>
            
            <select name="sort">
                <option value="default" <?= $sortOrder === 'default' ? 'selected' : '' ?>>Sắp xếp: Mặc định</option>
                <option value="doith" <?= $sortOrder === 'doith' ? 'selected' : '' ?>>Sắp xếp: Đội TH</option>
            </select>
            
            <button type="submit">Lọc</button>
            <a href="kehoach_thietbi_2026.php" style="padding: 8px 16px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">Reset</a>
            <a href="export_kehoach_2026_excel.php?<?= http_build_query(['search' => $search, 'loaitb' => $loaitb, 'bophansh' => $bophansh, 'kehoach' => $kehoachFilter, 'sort' => $sortOrder]) ?>" 
               style="padding: 8px 16px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; margin-left: auto;">
                📊 Xuất Excel
            </a>
            <a href="export_kehoach_2026_word.php?<?= http_build_query(['search' => $search, 'loaitb' => $loaitb, 'bophansh' => $bophansh, 'kehoach' => $kehoachFilter, 'sort' => $sortOrder]) ?>" 
               style="padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">
                📝 Xuất Word
            </a>
        </form>
        
        <!-- Chú thích màu sắc -->
        <div style="margin: 15px 0; padding: 12px; background: #f8f9fa; border-radius: 4px; border-left: 4px solid #007bff;">
            <strong style="display: block; margin-bottom: 8px;">📌 Chú thích:</strong>
            <div style="display: flex; gap: 20px; flex-wrap: wrap; font-size: 13px;">
                <div style="display: flex; align-items: center; gap: 5px;">
                    <span style="display: inline-block; width: 20px; height: 20px; background: #d4edda; border: 2px solid #28a745;"></span>
                    <span>Tháng đã kiểm định</span>
                </div>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <span style="color: #28a745; font-size: 20px; font-weight: bold;">✓</span>
                    <span>Thiết bị đã kiểm định trong năm 2026</span>
                </div>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <span style="display: inline-block; width: 20px; height: 20px; background: #2196F3;"></span>
                    <span>Tháng đã lập kế hoạch</span>
                </div>
            </div>
        </div>
        
        <!-- Bulk action button -->
        <div style="margin: 15px 0; display: flex; align-items: center; gap: 10px;">
            <button type="button" 
                    id="bulkDeleteBtn" 
                    onclick="bulkDeleteEquipmentPlans()" 
                    style="padding: 8px 16px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; display: none;">
                🗑️ Xóa đã chọn (<span id="selectedCount">0</span>)
            </button>
            <button type="button" 
                    onclick="selectAllCheckboxes()" 
                    style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
                ☑️ Chọn tất cả
            </button>
            <button type="button" 
                    onclick="deselectAllCheckboxes()" 
                    style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
                ☐ Bỏ chọn tất cả
            </button>
        </div>
        
        <!-- Form nhập kế hoạch -->
        <form method="POST">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th rowspan="2" style="width: 2%;">
                                <input type="checkbox" 
                                       id="selectAllCheckbox" 
                                       onchange="toggleSelectAll(this)"
                                       title="Chọn/Bỏ chọn tất cả">
                            </th>
                            <th rowspan="2" style="width: 3%;">STT</th>
                            <th rowspan="2" style="width: 18%;">Tên thiết bị, mẫu chuẩn/Vật chuẩn</th>
                            <th rowspan="2" style="width: 8%;">Ký/Mã hiệu</th>
                            <th rowspan="2" style="width: 8%;">Số máy</th>
                            <th rowspan="2" style="width: 10%;">Nước/Hãng SX</th>
                            <th rowspan="2" style="width: 12%;">Nơi thực hiện</th>
                            <th colspan="12" style="width: 30%;">Tháng</th>
                            <th rowspan="2" style="width: 6%;">Chủ sở hữu</th>
                            <th rowspan="2" style="width: 5%;">Đã KĐ</th>
                            <th rowspan="2" style="width: 5%; display: none;">Lưu</th>
                            <th rowspan="2" style="width: 4%;">Xóa</th>
                        </tr>
                        <tr>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <th class="month-header"><?= $i ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($thietbiList)): ?>
                            <tr>
                                <td colspan="22" style="text-align: center; padding: 20px;">
                                    Không có thiết bị nào. Vui lòng thêm thiết bị vào hệ thống trước.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $displaySTT = 1;
                            foreach ($thietbiList as $tb): 
                                $plannedMonths = $tb['planned_months'] ? explode(',', $tb['planned_months']) : [];
                                $plannedMonthsDot2 = $tb['planned_months_dot2'] ? explode(',', $tb['planned_months_dot2']) : [];
                                $inspectedMonths = $tb['inspected_months'] ? explode(',', $tb['inspected_months']) : [];
                                $hasInspection = (int)($tb['inspection_count'] ?? 0) > 0;
                            ?>
                            <tr>
                                <td style="text-align: center;">
                                    <input type="checkbox" 
                                           class="row-checkbox" 
                                           data-stt="<?= (int)$tb['stt'] ?>"
                                           data-ten="<?= htmlspecialchars($tb['tenthietbi']) ?>"
                                           onchange="updateBulkDeleteButton()">
                                </td>
                                <td><?= $displaySTT++ ?></td>
                                <td><?= htmlspecialchars($tb['tenthietbi']) ?></td>
                                <td><?= htmlspecialchars($tb['tenviettat'] ?? '') ?></td>
                                <td><?= htmlspecialchars($tb['somay'] ?? '') ?></td>
                                <td><?= htmlspecialchars($tb['hangsx'] ?? '') ?></td>
                                <td>
                                    <input type="text" 
                                           name="donvi_thuchien[<?= (int)$tb['stt'] ?>]" 
                                           class="small" 
                                           placeholder="Nơi thực hiện"
                                           value="<?= htmlspecialchars($tb['donvi_thuchien'] ?? '') ?>">
                                </td>
                                <?php for ($month = 1; $month <= 12; $month++): 
                                    $isInspected = in_array((string)$month, $inspectedMonths);
                                    $cellClass = $isInspected ? 'month-cell month-inspected' : 'month-cell';
                                ?>
                                    <td class="<?= $cellClass ?>" <?= $isInspected ? 'title="Đã kiểm định tháng ' . $month . '"' : '' ?>>
                                        <input type="checkbox" 
                                               name="thietbi[<?= (int)$tb['stt'] ?>][]" 
                                               value="<?= $month ?>"
                                               data-row="<?= (int)$tb['stt'] ?>"
                                               <?= in_array((string)$month, $plannedMonths) || in_array((string)$month, $plannedMonthsDot2) ? 'checked' : '' ?>>
                                        <?= $isInspected ? '<span class="check-mark">✓</span>' : '' ?>
                                    </td>
                                <?php endfor; ?>
                                <td>
                                    <input type="text" 
                                           name="chusohuu[<?= (int)$tb['stt'] ?>]" 
                                           class="small" 
                                           placeholder="Chủ sở hữu"
                                           value="<?= htmlspecialchars($tb['chusohuu'] ?? '') ?>">
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($hasInspection): ?>
                                        <span style="font-size: 20px; color: #28a745;" 
                                              title="Đã kiểm định <?= count($inspectedMonths) ?> lần trong năm 2026 (tháng: <?= implode(', ', $inspectedMonths) ?>)">
                                            ✓
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; display: none;">
                                    <button type="button" 
                                            class="btn-save-single" 
                                            data-stt="<?= (int)$tb['stt'] ?>"
                                            onclick="saveSingleEquipment(this)">
                                        💾
                                    </button>
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" 
                                            class="btn-delete" 
                                            data-stt="<?= (int)$tb['stt'] ?>"
                                            data-ten="<?= htmlspecialchars($tb['tenthietbi']) ?>"
                                            onclick="deleteEquipmentPlan(this)"
                                            title="Xóa kế hoạch của thiết bị này">
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="action-buttons">
                <button type="submit" name="save_plan" class="btn-save">💾 Lưu</button>
                <a href="/iso2/thietbihckd.php" style="padding: 8px 16px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;">Hủy</a>
            </div>
        </form>
        
        <div style="margin-top: 20px; padding: 15px; background: #e7f3fe; border-left: 4px solid #2196F3;">
            <strong>Tổng số thiết bị: <?= $totalRecords ?></strong>
            <?php if (!$search && $totalPages > 1): ?>
                (Hiển thị: <?= count($thietbiList) ?> - Trang <?= $page ?>/<?= $totalPages ?>)
            <?php else: ?>
                (Hiển thị: <?= count($thietbiList) ?>)
            <?php endif; ?>
        </div>
        
        <?php if (!$search && $totalPages > 1): ?>
        <div style="margin-top: 20px; padding: 15px; text-align: center;">
            <div style="display: inline-flex; gap: 5px; align-items: center;">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?= $loaitb ? '&loaitb='.urlencode($loaitb) : '' ?><?= $bophansh ? '&bophansh='.urlencode($bophansh) : '' ?>" 
                       style="padding: 8px 12px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">« Đầu</a>
                    <a href="?page=<?= $page - 1 ?><?= $loaitb ? '&loaitb='.urlencode($loaitb) : '' ?><?= $bophansh ? '&bophansh='.urlencode($bophansh) : '' ?>" 
                       style="padding: 8px 12px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">‹ Trước</a>
                <?php endif; ?>
                
                <span style="padding: 8px 16px; background: #e9ecef; border-radius: 4px; font-weight: bold;">
                    Trang <?= $page ?> / <?= $totalPages ?>
                </span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $loaitb ? '&loaitb='.urlencode($loaitb) : '' ?><?= $bophansh ? '&bophansh='.urlencode($bophansh) : '' ?>" 
                       style="padding: 8px 12px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">Sau ›</a>
                    <a href="?page=<?= $totalPages ?><?= $loaitb ? '&loaitb='.urlencode($loaitb) : '' ?><?= $bophansh ? '&bophansh='.urlencode($bophansh) : '' ?>" 
                       style="padding: 8px 12px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">Cuối »</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Save single equipment via AJAX
        function saveSingleEquipment(button) {
            const stt = button.getAttribute('data-stt');
            const row = button.closest('tr');
            const donviInput = row.querySelector('input[name="donvi_thuchien[' + stt + ']"]');
            const chusohuuInput = row.querySelector('input[name="chusohuu[' + stt + ']"]');
            
            // Get all checked months for this equipment
            const checkedMonths = Array.from(row.querySelectorAll('input[name="thietbi[' + stt + '][]"]:checked'))
                .map(cb => parseInt(cb.value))
                .sort((a, b) => a - b);
            
            const donviThuchien = donviInput ? donviInput.value : '';
            const chusohuu = chusohuuInput ? chusohuuInput.value : '';
            const thang1 = checkedMonths[0] || '';
            const thang2 = checkedMonths[1] || '';
            
            // Disable button
            button.disabled = true;
            const originalText = button.innerHTML;
            button.innerHTML = '⏳';
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax_save=1&stt=' + encodeURIComponent(stt) + 
                      '&thang_thuchien=' + encodeURIComponent(thang1) + 
                      '&thang_dot2=' + encodeURIComponent(thang2) +
                      '&donvi_thuchien=' + encodeURIComponent(donviThuchien) +
                      '&chusohuu=' + encodeURIComponent(chusohuu)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    button.innerHTML = '✓';
                    button.style.background = '#28a745';
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }, 1500);
                    
                    // Optional: Show toast notification
                    showToast(data.message, 'success');
                } else {
                    button.innerHTML = '✗';
                    button.style.background = '#dc3545';
                    alert('Lỗi: ' + data.message);
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.disabled = false;
                        button.style.background = '#28a745';
                    }, 2000);
                }
            })
            .catch(error => {
                button.innerHTML = '✗';
                button.style.background = '#dc3545';
                alert('Lỗi kết nối: ' + error);
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                    button.style.background = '#28a745';
                }, 2000);
            });
        }
        
        // Delete equipment plan via AJAX
        function deleteEquipmentPlan(button) {
            const stt = button.getAttribute('data-stt');
            const tenThietBi = button.getAttribute('data-ten');
            
            // Confirm before delete
            if (!confirm(`Bạn có chắc chắn muốn xóa kế hoạch của thiết bị "${tenThietBi}"?\n\nThao tác này không thể hoàn tác!`)) {
                return;
            }
            
            // Disable button
            button.disabled = true;
            const originalText = button.innerHTML;
            button.innerHTML = '⏳';
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax_delete=1&stt=' + encodeURIComponent(stt)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    if (!text) {
                        throw new Error('Empty response from server');
                    }
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Response text:', text);
                        throw new Error('Invalid JSON: ' + text.substring(0, 100));
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    // Show success message
                    showToast(data.message, 'success');
                    
                    // Clear all checkboxes in this row
                    const row = button.closest('tr');
                    const checkboxes = row.querySelectorAll('input[type="checkbox"]');
                    checkboxes.forEach(cb => {
                        cb.checked = false;
                        const cell = cb.closest('td');
                        cell.classList.remove('month-selected', 'month-selected-dot2');
                    });
                    
                    // Reset button
                    button.innerHTML = originalText;
                    button.disabled = false;
                    
                    // Optional: Reload page after 1 second to refresh data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    button.innerHTML = '✗';
                    button.style.background = '#dc3545';
                    alert('Lỗi: ' + data.message);
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.disabled = false;
                        button.style.background = '';
                    }, 2000);
                }
            })
            .catch(error => {
                button.innerHTML = '✗';
                button.style.background = '#dc3545';
                alert('Lỗi kết nối: ' + error);
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                    button.style.background = '';
                }, 2000);
            });
        }
        
        // Toggle select all from header checkbox
        function toggleSelectAll(headerCheckbox) {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(cb => cb.checked = headerCheckbox.checked);
            updateBulkDeleteButton();
        }
        
        // Select all checkboxes
        function selectAllCheckboxes() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const headerCheckbox = document.getElementById('selectAllCheckbox');
            checkboxes.forEach(cb => cb.checked = true);
            if (headerCheckbox) headerCheckbox.checked = true;
            updateBulkDeleteButton();
        }
        
        // Deselect all checkboxes
        function deselectAllCheckboxes() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const headerCheckbox = document.getElementById('selectAllCheckbox');
            checkboxes.forEach(cb => cb.checked = false);
            if (headerCheckbox) headerCheckbox.checked = false;
            updateBulkDeleteButton();
        }
        
        // Update bulk delete button visibility and count
        function updateBulkDeleteButton() {
            const allCheckboxes = document.querySelectorAll('.row-checkbox');
            const checkedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
            const count = checkedCheckboxes.length;
            const bulkBtn = document.getElementById('bulkDeleteBtn');
            const countSpan = document.getElementById('selectedCount');
            const headerCheckbox = document.getElementById('selectAllCheckbox');
            
            // Update header checkbox state
            if (headerCheckbox) {
                if (count === 0) {
                    headerCheckbox.checked = false;
                    headerCheckbox.indeterminate = false;
                } else if (count === allCheckboxes.length) {
                    headerCheckbox.checked = true;
                    headerCheckbox.indeterminate = false;
                } else {
                    headerCheckbox.checked = false;
                    headerCheckbox.indeterminate = true;
                }
            }
            
            // Update bulk delete button
            if (count > 0) {
                bulkBtn.style.display = 'block';
                countSpan.textContent = count;
            } else {
                bulkBtn.style.display = 'none';
            }
        }
        
        // Bulk delete selected equipment plans
        function bulkDeleteEquipmentPlans() {
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            
            if (checkboxes.length === 0) {
                alert('Vui lòng chọn ít nhất một thiết bị để xóa');
                return;
            }
            
            // Get device names for confirmation
            const deviceNames = Array.from(checkboxes).map(cb => cb.getAttribute('data-ten'));
            const confirmMsg = `Bạn có chắc chắn muốn xóa kế hoạch của ${checkboxes.length} thiết bị?\n\n` + 
                              deviceNames.slice(0, 5).join('\n') + 
                              (deviceNames.length > 5 ? `\n... và ${deviceNames.length - 5} thiết bị khác` : '') +
                              `\n\nThao tác này không thể hoàn tác!`;
            
            if (!confirm(confirmMsg)) {
                return;
            }
            
            // Get STT list
            const sttList = Array.from(checkboxes).map(cb => cb.getAttribute('data-stt'));
            
            // Disable bulk button
            const bulkBtn = document.getElementById('bulkDeleteBtn');
            const originalText = bulkBtn.innerHTML;
            bulkBtn.disabled = true;
            bulkBtn.innerHTML = '⏳ Đang xóa...';
            
            // Build POST data
            const formData = new URLSearchParams();
            formData.append('ajax_bulk_delete', '1');
            sttList.forEach(stt => formData.append('stt_list[]', stt));
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString()
            })
            .then(response => {
                // Check if response is OK
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                // Check if response has content
                return response.text().then(text => {
                    if (!text) {
                        throw new Error('Empty response from server');
                    }
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Response text:', text);
                        throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    
                    // Clear all checked rows
                    checkboxes.forEach(cb => {
                        const row = cb.closest('tr');
                        const monthCheckboxes = row.querySelectorAll('input[type="checkbox"][name^="thietbi"]');
                        monthCheckboxes.forEach(mcb => {
                            mcb.checked = false;
                            const cell = mcb.closest('td');
                            cell.classList.remove('month-selected', 'month-selected-dot2');
                        });
                        cb.checked = false;
                    });
                    
                    updateBulkDeleteButton();
                    
                    // Reload page after 1.5 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    bulkBtn.innerHTML = '✗ Lỗi';
                    bulkBtn.style.background = '#dc3545';
                    alert('Lỗi: ' + data.message);
                    setTimeout(() => {
                        bulkBtn.innerHTML = originalText;
                        bulkBtn.disabled = false;
                        bulkBtn.style.background = '';
                    }, 2000);
                }
            })
            .catch(error => {
                bulkBtn.innerHTML = '✗ Lỗi';
                bulkBtn.style.background = '#dc3545';
                alert('Lỗi kết nối: ' + error);
                setTimeout(() => {
                    bulkBtn.innerHTML = originalText;
                    bulkBtn.disabled = false;
                    bulkBtn.style.background = '';
                }, 2000);
            });
        }
        
        // Simple toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#28a745' : '#dc3545'};
                color: white;
                padding: 15px 20px;
                border-radius: 4px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                z-index: 9999;
                animation: slideIn 0.3s ease-out;
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // Allow clicking on cell to select/deselect month
        let lastChecked = {};
        
        // Initialize - highlight already selected cells
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            if (checkbox.checked) {
                updateCellColor(checkbox);
            }
        });
        
        // Limit to 2 checkboxes per row and color them
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const rowId = this.getAttribute('data-row');
                const rowCheckboxes = document.querySelectorAll(`input[type="checkbox"][data-row="${rowId}"]`);
                const checked = Array.from(rowCheckboxes).filter(cb => cb.checked);
                
                // Limit to 2 selections
                if (checked.length > 2) {
                    this.checked = false;
                    alert('Chỉ được chọn tối đa 2 tháng cho mỗi thiết bị!');
                    return;
                }
                
                // Update colors for all checkboxes in this row
                rowCheckboxes.forEach(cb => updateCellColor(cb));
            });
        });
        
        function updateCellColor(checkbox) {
            const cell = checkbox.closest('td');
            const rowId = checkbox.getAttribute('data-row');
            const rowCheckboxes = document.querySelectorAll(`input[type="checkbox"][data-row="${rowId}"]`);
            const checked = Array.from(rowCheckboxes).filter(cb => cb.checked).sort((a, b) => parseInt(a.value) - parseInt(b.value));
            
            // Remove all color classes first
            cell.classList.remove('month-selected', 'month-selected-dot2');
            
            if (checkbox.checked) {
                // First month (smallest) = green, second month = orange
                if (checked.length === 1) {
                    cell.classList.add('month-selected');
                } else if (checked.length === 2) {
                    if (checkbox === checked[0]) {
                        cell.classList.add('month-selected');
                    } else {
                        cell.classList.add('month-selected-dot2');
                    }
                }
            }
        }
        
        // Handle cell clicks
        document.querySelectorAll('.month-cell').forEach(cell => {
            // Add tooltip on hover/click
            cell.addEventListener('mouseenter', function(e) {
                const checkbox = this.querySelector('input[type="checkbox"]');
                if (!checkbox) return;
                
                const monthValue = checkbox.value;
                const monthNames = ['', 'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 
                                    'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
                
                const tooltip = document.createElement('div');
                tooltip.className = 'month-tooltip';
                tooltip.textContent = monthNames[monthValue] || 'Tháng ' + monthValue;
                tooltip.id = 'month-tooltip-' + monthValue;
                document.body.appendChild(tooltip);
                
                // Position tooltip near cursor
                const rect = this.getBoundingClientRect();
                tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
                tooltip.style.top = (rect.top - tooltip.offsetHeight - 8) + 'px';
            });
            
            cell.addEventListener('mouseleave', function() {
                const tooltips = document.querySelectorAll('.month-tooltip');
                tooltips.forEach(t => t.remove());
            });
            
            cell.addEventListener('click', function() {
                const checkbox = this.querySelector('input[type="checkbox"]');
                if (!checkbox) return;
                
                // Toggle checkbox
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));
            });
        });
    </script>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
