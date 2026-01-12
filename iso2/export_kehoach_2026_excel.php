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

// Tạo spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set page orientation to landscape
$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
$sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

// Add logo
$logoPath = __DIR__ . '/logo.jpg';
if (file_exists($logoPath)) {
    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
    $drawing->setName('Logo');
    $drawing->setDescription('Company Logo');
    $drawing->setPath($logoPath);
    $drawing->setCoordinates('A1');
    $drawing->setHeight(80); // Logo height in pixels
    $drawing->setOffsetX(10);
    $drawing->setOffsetY(10);
    $drawing->setWorksheet($sheet);
}

// Header
$sheet->mergeCells('A1:S1');
$sheet->setCellValue('A1', "DANH MỤC THIẾT BỊ, MẪU CHUẨN/VẬT CHUẨN\nYÊU CẦU HIỆU CHUẨN/KIỂM ĐỊNH, KIỂM TRA\n\n\n\nNăm 2026\n\nXí Nghiệp Địa Vật Lý Giếng Khoan");
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
$sheet->getRowDimension(1)->setRowHeight(120);

// Column headers - row 2
$sheet->setCellValue('A2', 'STT');
$sheet->setCellValue('B2', 'Tên thiết bị');
$sheet->setCellValue('C2', 'Ký/Mã hiệu');
$sheet->setCellValue('D2', 'Số S/N');
$sheet->setCellValue('E2', 'Nước/Hãng SX');
$sheet->setCellValue('F2', 'Nơi thực hiện');
$sheet->setCellValue('G2', '1');
$sheet->setCellValue('H2', '2');
$sheet->setCellValue('I2', '3');
$sheet->setCellValue('J2', '4');
$sheet->setCellValue('K2', '5');
$sheet->setCellValue('L2', '6');
$sheet->setCellValue('M2', '7');
$sheet->setCellValue('N2', '8');
$sheet->setCellValue('O2', '9');
$sheet->setCellValue('P2', '10');
$sheet->setCellValue('Q2', '11');
$sheet->setCellValue('R2', '12');
$sheet->setCellValue('S2', 'Ghi chú');

// Style header row
$headerStyle = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'CCCCCC']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A2:S2')->applyFromArray($headerStyle);

// Set column widths
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(40);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(15);
$sheet->getColumnDimension('F')->setWidth(20);
for ($col = 'G'; $col <= 'R'; $col++) {
    $sheet->getColumnDimension($col)->setWidth(4);
}
$sheet->getColumnDimension('S')->setWidth(20);

// Data rows
$row = 3;
$displaySTT = 1;

foreach ($allData as $item) {
    $sheet->setCellValue('A' . $row, $displaySTT++);
    $sheet->setCellValue('B' . $row, $item['tenthietbi']);
    $sheet->setCellValue('C' . $row, $item['tenviettat'] ?? '');
    $sheet->setCellValue('D' . $row, $item['somay'] ?? '');
    $sheet->setCellValue('E' . $row, $item['hangsx'] ?? '');
    $sheet->setCellValue('F' . $row, $item['donvi_thuchien'] ?? '');
    
    // Tô màu cho các tháng kế hoạch
    // Tháng đợt 1: màu xanh lá (green)
    if (!empty($item['thang_thuchien'])) {
        $selectedMonths = explode(',', $item['thang_thuchien']);
        foreach ($selectedMonths as $month) {
            $month = (int)trim($month);
            if ($month >= 1 && $month <= 12) {
                $colIndex = ord('F') + $month; // G=1, H=2, ... R=12
                $colLetter = chr($colIndex);
                
                // Tô nền xanh lá
                $sheet->getStyle($colLetter . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4CAF50'] // Green
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);
            }
        }
    }
    
    // Tháng đợt 2: màu cam (orange)
    if (!empty($item['thang_dot2'])) {
        $selectedMonths2 = explode(',', $item['thang_dot2']);
        foreach ($selectedMonths2 as $month) {
            $month = (int)trim($month);
            if ($month >= 1 && $month <= 12) {
                $colIndex = ord('F') + $month; // G=1, H=2, ... R=12
                $colLetter = chr($colIndex);
                
                // Tô nền cam
                $sheet->getStyle($colLetter . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FF9800'] // Orange
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);
            }
        }
    }
    
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
    
    $sheet->setCellValue('S' . $row, $chusohuuValue);
    
    // Border for data row
    $sheet->getStyle('A' . $row . ':S' . $row)->applyFromArray([
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
