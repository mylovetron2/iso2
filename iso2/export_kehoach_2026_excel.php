<?php
declare(strict_types=1);

// Start session manually
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Disable error display to avoid corrupting Excel file
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

// Check if PhpSpreadsheet exists
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die('PhpSpreadsheet library not found');
}

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
        MAX(k.donvi_thuchien) as donvi_thuchien
        FROM thietbihckd_iso t
        INNER JOIN kehoach_kiemdinh_2026_iso k ON t.stt = k.stt AND k.nam_kehoach = 2026
        WHERE $whereClause
        GROUP BY t.stt
        ORDER BY t.loaitb, t.tenthietbi";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$allData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tạo spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set page orientation to landscape
$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
$sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

// Header
$sheet->mergeCells('A1:P1');
$sheet->setCellValue('A1', 'KẾ HOẠCH CHUẨN/KIỂM ĐỊNH THIẾT BỊ NĂM 2026');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Column headers - row 2
$sheet->setCellValue('A2', 'STT');
$sheet->setCellValue('B2', 'Tên thiết bị');
$sheet->setCellValue('C2', 'Số S/N');
$sheet->setCellValue('D2', 'Nước/Hãng');
$sheet->setCellValue('E2', 'Thực hiện');
$sheet->setCellValue('F2', '1');
$sheet->setCellValue('G2', '2');
$sheet->setCellValue('H2', '3');
$sheet->setCellValue('I2', '4');
$sheet->setCellValue('J2', '5');
$sheet->setCellValue('K2', '6');
$sheet->setCellValue('L2', '7');
$sheet->setCellValue('M2', '8');
$sheet->setCellValue('N2', '9');
$sheet->setCellValue('O2', '10');
$sheet->setCellValue('P2', '11');
$sheet->setCellValue('Q2', '12');
$sheet->setCellValue('R2', 'Ghi chú');

// Style header row
$headerStyle = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'CCCCCC']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A2:R2')->applyFromArray($headerStyle);

// Set column widths
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(40);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(20);
for ($col = 'F'; $col <= 'Q'; $col++) {
    $sheet->getColumnDimension($col)->setWidth(4);
}
$sheet->getColumnDimension('R')->setWidth(20);

// Data rows
$row = 3;
$displaySTT = 1;

foreach ($allData as $item) {
    $sheet->setCellValue('A' . $row, $displaySTT++);
    $sheet->setCellValue('B' . $row, $item['tenthietbi']);
    $sheet->setCellValue('C' . $row, $item['somay'] ?? '');
    $sheet->setCellValue('D' . $row, $item['hangsx'] ?? '');
    $sheet->setCellValue('E' . $row, $item['donvi_thuchien'] ?? '');
    
    // Tô màu xanh dương cho 3 tháng liên tiếp (tháng dữ liệu là tháng đầu tiên hoặc điều chỉnh nếu gần cuối năm)
    if (!empty($item['thang_thuchien'])) {
        $selectedMonths = explode(',', $item['thang_thuchien']);
        foreach ($selectedMonths as $month) {
            $month = (int)trim($month);
            if ($month >= 1 && $month <= 12) {
                // Đảm bảo luôn tô 3 ô, điều chỉnh nếu tháng 11 hoặc 12
                // Tháng 1-10: tô từ tháng đó
                // Tháng 11, 12: tô 10, 11, 12
                $startMonth = min($month, 10);
                
                for ($i = 0; $i < 3; $i++) {
                    $currentMonth = $startMonth + $i;
                    $colIndex = ord('E') + $currentMonth; // F=1, G=2, ... Q=12
                    $colLetter = chr($colIndex);
                    
                    // Tô nền xanh dương
                    $sheet->getStyle($colLetter . $row)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '2196F3'] // Blue
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER
                        ]
                    ]);
                }
            }
        }
    }
    
    $sheet->setCellValue('R' . $row, $item['chusohuu'] ?? '');
    
    // Border for data row
    $sheet->getStyle('A' . $row . ':R' . $row)->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    
    $row++;
}

// Auto-fit row heights
for ($i = 2; $i < $row; $i++) {
    $sheet->getRowDimension($i)->setRowHeight(-1);
}

// Set filename
$filename = 'Ke_hoach_kiem_dinh_2026_' . date('Ymd_His') . '.xlsx';

// Output
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
