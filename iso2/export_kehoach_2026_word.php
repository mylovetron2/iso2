<?php
declare(strict_types=1);

// Start session manually
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Disable error display
error_reporting(0);
ini_set('display_errors', '0');

// Simple logged in check
if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

// Load dependencies
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';

requireAuth();

if (!hasPermission('kehoach_kiemdinh.export')) {
    die('Không có quyền xuất file');
}

// Database connection
require_once __DIR__ . '/config/database.php';
$db = getDBConnection();

// Lấy filters
$search = $_GET['search'] ?? '';
$loaitb = $_GET['loaitb'] ?? '';
$bophansh = $_GET['bophansh'] ?? '';
$kehoachFilter = $_GET['kehoach'] ?? '';

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(t.mavattu LIKE :search OR t.tenthietbi LIKE :search2 OR t.somay LIKE :search3)";
    $params[':search'] = "%$search%";
    $params[':search2'] = "%$search%";
    $params[':search3'] = "%$search%";
}

if ($loaitb) {
    $where[] = "t.loaitb = :loaitb";
    $params[':loaitb'] = $loaitb;
}

if ($bophansh === '__dvl_tonghop__') {
    $where[] = "t.bophansh IN ('CNC', 'TH', 'DVLTH')";
} elseif ($bophansh) {
    $where[] = "t.bophansh = :bophansh";
    $params[':bophansh'] = $bophansh;
}

$whereClause = implode(' AND ', $where);

// Filter theo tình trạng kế hoạch
$havingClause = '';
if ($kehoachFilter === 'co_chua_th') {
    $havingClause = 'HAVING inspection_count = 0';
}

// Lấy toàn bộ thiết bị với GROUP_CONCAT để gộp các tháng - chỉ lấy thiết bị có kế hoạch
$sql = "SELECT t.*, 
        GROUP_CONCAT(DISTINCT k.thang_thuchien ORDER BY k.thang_thuchien) as thang_thuchien,
        GROUP_CONCAT(DISTINCT k.thang_dot2 ORDER BY k.thang_dot2) as thang_dot2,
        MIN(CAST(k.thang_thuchien AS UNSIGNED)) as first_month,
        MAX(k.donvi_thuchien) as donvi_thuchien,
        GROUP_CONCAT(DISTINCT MONTH(h.ngayhc) ORDER BY h.ngayhc) as inspected_months,
        COUNT(DISTINCT h.stt) as inspection_count
        FROM thietbihckd_iso t
        INNER JOIN kehoach_kiemdinh_2026_iso k ON t.stt = k.stt AND k.nam_kehoach = 2026
        LEFT JOIN hosohckd_iso h ON (h.thietbi_stt = t.stt
            OR (h.thietbi_stt IS NULL AND h.tenmay = t.mavattu))
            AND YEAR(h.ngayhc) = 2026
        WHERE $whereClause
        GROUP BY t.stt
        $havingClause
        ORDER BY first_month ASC, t.loaitb, t.tenthietbi";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$allData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for Word document download
header('Content-Type: application/vnd.ms-word');
header('Content-Disposition: attachment; filename="Ke_Hoach_Kiem_Dinh_2026_' . date('YmdHis') . '.doc"');
header('Cache-Control: max-age=0');

// Output HTML formatted for Word
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="utf-8">
    <xml>
        <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>100</w:Zoom>
        </w:WordDocument>
    </xml>
    <style>
        @page {
            size: A4 landscape;
            margin: 1cm 0.5cm 1cm 0.5cm;
            mso-page-orientation: landscape;
        }
        @page Section1 {
            size: 297mm 210mm;
            mso-page-orientation: landscape;
        }
        div.Section1 { page: Section1; }
        body { 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 12pt;
        }
        table { 
            border-collapse: collapse; 
            width: 100%;
            table-layout: fixed;
        }
        th, td { 
            border: 1px solid black; 
            padding: 3px; 
            text-align: center;
            font-size: 12pt;
        }
        th { background-color: #CCCCCC; font-weight: bold; }
        .title { text-align: center; font-size: 13pt; font-weight: bold; margin-bottom: 15px; }
        .highlight-green { background-color: #2196F3; }
        .highlight-orange { background-color: #FF9800; }
    </style>
</head>
<body>
<div class="Section1">
    <div style="position: relative;">
        <img src="logo.jpg" alt="Logo" style="position: absolute; left: 20px; top: 10px; height: 80px;">
    </div>
    <div class="title">
        DANH MỤC THIẾT BỊ, MẪU CHUẨN/VẬT CHUẨN<br>
        YÊU CẦU HIỆU CHUẨN/KIỂM ĐỊNH, KIỂM TRA<br>
        <br><br><br>
        Năm 2026<br>
        <br>
        Xí Nghiệp Địa Vật Lý Giếng Khoan
    </div>
    
    <table>
        <colgroup>
            <col style="width: 30px;">  <!-- STT -->
            <col style="width: 200px;"> <!-- Tên thiết bị -->
            <col style="width: 80px;">  <!-- Ký hiệu -->
            <col style="width: 80px;">  <!-- Số máy -->
            <col style="width: 100px;"> <!-- Hãng SX -->
            <col style="width: 120px;"> <!-- Nơi thực hiện -->
            <?php for ($i = 1; $i <= 12; $i++): ?>
                <col style="width: 60px;"> <!-- Tháng <?= $i ?> -->
            <?php endfor; ?>
            <col style="width: 120px;"> <!-- Chủ sở hữu -->
            <col style="width: 100px;"> <!-- Đã KĐ -->
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">STT</th>
                <th rowspan="2">Tên thiết bị, mẫu chuẩn/Vật chuẩn</th>
                <th rowspan="2">Ký/Mã hiệu</th>
                <th rowspan="2">Số máy</th>
                <th rowspan="2">Nước/Hãng SX</th>
                <th rowspan="2">Nơi thực hiện</th>
                <th colspan="12">Tháng</th>
                <th rowspan="2">Chủ sở hữu</th>
                <th rowspan="2">Đã KĐ</th>
            </tr>
            <tr>
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <th><?= $i ?></th>
                <?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php 
            $displaySTT = 1;
            foreach ($allData as $item): 
                $selectedMonths = !empty($item['thang_thuchien']) ? explode(',', $item['thang_thuchien']) : [];
                $selectedMonths2 = !empty($item['thang_dot2']) ? explode(',', $item['thang_dot2']) : [];
                $inspectedMonths = !empty($item['inspected_months']) ? explode(',', $item['inspected_months']) : [];
                $hasInspection = (int)($item['inspection_count'] ?? 0) > 0;
            ?>
            <tr>
                <td><?= $displaySTT++ ?></td>
                <td style="text-align: left;"><?= htmlspecialchars($item['tenthietbi']) ?></td>
                <td><?= htmlspecialchars($item['tenviettat'] ?? '') ?></td>
                <td><?= htmlspecialchars($item['somay'] ?? '') ?></td>
                <td><?= htmlspecialchars($item['hangsx'] ?? '') ?></td>
                <td><?= htmlspecialchars(($item['donvi_thuchien'] ?? '') === 'Xí nghiệp CĐ' ? 'XNCĐ' : ($item['donvi_thuchien'] ?? '')) ?></td>
                
                <?php for ($month = 1; $month <= 12; $month++): 
                    // Kiểm tra xem tháng này có được tô màu không
                    // Logic: tháng kế hoạch là tháng cuối
                    // VD: thangkh=10 thì tô 8,9,10; thangkh=2 thì tô 1,2; thangkh=1 thì chỉ tô 1
                    $isGreen = false;
                    $isOrange = false;
                    
                    // Kiểm tra đợt 1 (green)
                    foreach ($selectedMonths as $selectedMonth) {
                        $selectedMonth = (int)trim($selectedMonth);
                        if ($selectedMonth >= 1 && $selectedMonth <= 12) {
                            // Tháng kế hoạch là tháng cuối
                            // Tính tháng bắt đầu: nếu tháng kế hoạch = 1 thì chỉ tô tháng 1
                            // nếu tháng kế hoạch = 2 thì tô tháng 1,2
                            // nếu tháng kế hoạch >= 3 thì tô 3 tháng liên tiếp
                            if ($selectedMonth == 1) {
                                $startMonth = 1;
                                $endMonth = 1;
                            } elseif ($selectedMonth == 2) {
                                $startMonth = 1;
                                $endMonth = 2;
                            } else {
                                $startMonth = $selectedMonth - 2;
                                $endMonth = $selectedMonth;
                            }
                            
                            // Kiểm tra nếu tháng hiện tại nằm trong khoảng
                            if ($month >= $startMonth && $month <= $endMonth) {
                                $isGreen = true;
                                break;
                            }
                        }
                    }
                    
                    // Kiểm tra đợt 2 (orange) - chỉ tô cam nếu chưa tô xanh
                    if (!$isGreen) {
                        foreach ($selectedMonths2 as $selectedMonth2) {
                            $selectedMonth2 = (int)trim($selectedMonth2);
                            if ($selectedMonth2 >= 1 && $selectedMonth2 <= 12) {
                                // Tháng kế hoạch là tháng cuối
                                if ($selectedMonth2 == 1) {
                                    $startMonth = 1;
                                    $endMonth = 1;
                                } elseif ($selectedMonth2 == 2) {
                                    $startMonth = 1;
                                    $endMonth = 2;
                                } else {
                                    $startMonth = $selectedMonth2 - 2;
                                    $endMonth = $selectedMonth2;
                                }
                                
                                // Kiểm tra nếu tháng hiện tại nằm trong khoảng
                                if ($month >= $startMonth && $month <= $endMonth) {
                                    $isOrange = true;
                                    break;
                                }
                            }
                        }
                    }
                    
                    // Kiểm tra tháng đã kiểm định
                    $isInspected = in_array((string)$month, $inspectedMonths);
                    
                    $highlightClass = '';
                    $styleAttr = ' style="width: 60px; min-width: 60px;"';
                    $cellContent = '&nbsp;';
                    
                    if ($isInspected) {
                        // Ưu tiên hiển thị tháng đã kiểm định (xanh nhạt + dấu ✓)
                        $styleAttr = ' style="width: 60px; min-width: 60px; background-color: #d4edda; color: #28a745; font-weight: bold; font-size: 14pt; border: 2px solid #28a745;"';
                        $cellContent = '✓';
                    } elseif ($isGreen) {
                        $highlightClass = ' class="highlight-green"';
                        $styleAttr = ' style="width: 60px; min-width: 60px; background-color: #2196F3;"';
                    } elseif ($isOrange) {
                        $highlightClass = ' class="highlight-orange"';
                        $styleAttr = ' style="width: 60px; min-width: 60px; background-color: #FF9800;"';
                    }
                ?>
                    <td<?= $styleAttr ?>><?= $cellContent ?></td>
                <?php endfor; ?>
                
                <?php 
                // Chuyển đổi chủ sở hữu
                $chusohuuValue = $item['chusohuu'] ?? '';
                if ($chusohuuValue === 'TH') {
                    $chusohuuValue = 'Đội TH';
                } elseif ($chusohuuValue === 'CNC') {
                    $chusohuuValue = 'Đội CNM';
                } elseif ($chusohuuValue === 'TV') {
                    $chusohuuValue = 'Đội TV';
                } elseif ($chusohuuValue === 'KTKT') {
                    $chusohuuValue = 'Đội KTKT';
                } elseif ($chusohuuValue === 'VT') {
                    $chusohuuValue = 'Phòng VT';
                }
                ?>
                <td><?= htmlspecialchars($chusohuuValue) ?></td>
                <td style="<?= $hasInspection ? 'background-color: #d4edda; color: #28a745; font-weight: bold;' : '' ?>">
                    <?php if ($hasInspection): ?>
                        ✓ (T: <?= implode(', ', $inspectedMonths) ?>)
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
