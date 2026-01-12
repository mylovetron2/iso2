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

if ($bophansh) {
    $where[] = "t.bophansh = :bophansh";
    $params[':bophansh'] = $bophansh;
}

$whereClause = implode(' AND ', $where);

// Lấy toàn bộ thiết bị với GROUP_CONCAT để gộp các tháng - chỉ lấy thiết bị có kế hoạch
$sql = "SELECT t.*, 
        GROUP_CONCAT(DISTINCT k.thang_thuchien ORDER BY k.thang_thuchien) as thang_thuchien,
        GROUP_CONCAT(DISTINCT k.thang_dot2 ORDER BY k.thang_dot2) as thang_dot2,
        MIN(CAST(k.thang_thuchien AS UNSIGNED)) as first_month,
        MAX(k.donvi_thuchien) as donvi_thuchien
        FROM thietbihckd_iso t
        INNER JOIN kehoach_kiemdinh_2026_iso k ON t.stt = k.stt AND k.nam_kehoach = 2026
        WHERE $whereClause
        GROUP BY t.stt
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
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 5px; text-align: center; }
        th { background-color: #CCCCCC; font-weight: bold; }
        .title { text-align: center; font-size: 14pt; font-weight: bold; margin-bottom: 20px; }
        .highlight-green { background-color: #2196F3; }
        .highlight-orange { background-color: #FF9800; }
    </style>
</head>
<body>
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
        <thead>
            <tr>
                <th rowspan="2" style="width: 30px;">STT</th>
                <th rowspan="2" style="width: 200px;">Tên thiết bị, mẫu chuẩn/Vật chuẩn</th>
                <th rowspan="2" style="width: 80px;">Ký/Mã hiệu</th>
                <th rowspan="2" style="width: 80px;">Số máy</th>
                <th rowspan="2" style="width: 100px;">Nước/Hãng SX</th>
                <th rowspan="2" style="width: 120px;">Nơi thực hiện</th>
                <th colspan="12">Tháng</th>
                <th rowspan="2" style="width: 120px;">Chủ sở hữu</th>
            </tr>
            <tr>
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <th style="width: 30px;"><?= $i ?></th>
                <?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php 
            $displaySTT = 1;
            foreach ($allData as $item): 
                $selectedMonths = !empty($item['thang_thuchien']) ? explode(',', $item['thang_thuchien']) : [];
                $selectedMonths2 = !empty($item['thang_dot2']) ? explode(',', $item['thang_dot2']) : [];
            ?>
            <tr>
                <td><?= $displaySTT++ ?></td>
                <td style="text-align: left;"><?= htmlspecialchars($item['tenthietbi']) ?></td>
                <td><?= htmlspecialchars($item['tenviettat'] ?? '') ?></td>
                <td><?= htmlspecialchars($item['somay'] ?? '') ?></td>
                <td><?= htmlspecialchars($item['hangsx'] ?? '') ?></td>
                <td><?= htmlspecialchars($item['donvi_thuchien'] ?? '') ?></td>
                
                <?php for ($month = 1; $month <= 12; $month++): 
                    // Kiểm tra xem tháng này có được tô màu không (3 tháng liên tiếp)
                    $isGreen = false;
                    $isOrange = false;
                    
                    // Kiểm tra đợt 1 (green)
                    foreach ($selectedMonths as $selectedMonth) {
                        $selectedMonth = (int)trim($selectedMonth);
                        if ($selectedMonth >= 1 && $selectedMonth <= 12) {
                            $startMonth = ($selectedMonth >= 11) ? 10 : $selectedMonth;
                            // Kiểm tra nếu tháng hiện tại nằm trong 3 tháng liên tiếp
                            if ($month >= $startMonth && $month < $startMonth + 3) {
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
                                $startMonth = ($selectedMonth2 >= 11) ? 10 : $selectedMonth2;
                                // Kiểm tra nếu tháng hiện tại nằm trong 3 tháng liên tiếp
                                if ($month >= $startMonth && $month < $startMonth + 3) {
                                    $isOrange = true;
                                    break;
                                }
                            }
                        }
                    }
                    
                    $highlightClass = '';
                    if ($isGreen) {
                        $highlightClass = ' class="highlight-green"';
                    } elseif ($isOrange) {
                        $highlightClass = ' class="highlight-orange"';
                    }
                ?>
                    <td<?= $highlightClass ?>>&nbsp;</td>
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
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
