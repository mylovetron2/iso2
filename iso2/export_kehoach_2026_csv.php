<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';

requireAuth();

if (!hasPermission('kehoach_kiemdinh.export')) {
    die('Không có quyền xuất file');
}

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

// Lấy toàn bộ thiết bị
$sql = "SELECT t.*, 
        k.thang_thuchien,
        k.donvi_thuchien
        FROM thietbihckd_iso t
        LEFT JOIN kehoach_kiemdinh_2026_iso k ON t.stt = k.stt AND k.nam_kehoach = 2026
        WHERE $whereClause
        ORDER BY t.loaitb, t.tenthietbi";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$allData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
$filename = 'Ke_hoach_kiem_dinh_2026_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Add BOM for UTF-8 Excel compatibility
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, [
    'STT',
    'Tên thiết bị',
    'Số S/N',
    'Nước/Hãng',
    'Thực hiện',
    '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12',
    'Ghi chú'
]);

// Data rows
$displaySTT = 1;
foreach ($allData as $item) {
    $row = [
        $displaySTT++,
        $item['tenthietbi'],
        $item['somay'] ?? '',
        $item['hangsx'] ?? '',
        $item['donvi_thuchien'] ?? ''
    ];
    
    // Add month columns (1-12)
    for ($month = 1; $month <= 12; $month++) {
        if ($item['thang_thuchien'] == $month) {
            $row[] = 'X'; // Mark selected month
        } else {
            $row[] = '';
        }
    }
    
    $row[] = $item['chusohuu'] ?? '';
    
    fputcsv($output, $row);
}

fclose($output);
exit;
