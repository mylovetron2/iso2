<?php
// Disable error display
error_reporting(0);
ini_set('display_errors', '0');

// Start output buffering
ob_start();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth check
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    die('Not logged in');
}

// Load autoloader
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    ob_end_clean();
    die('PhpSpreadsheet library not found');
}

require_once __DIR__ . '/vendor/autoload.php';

// Clear any output
ob_end_clean();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

try {
    // Create spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Vật tư thanh lý');

    // Headers
    $headers = [
        'A1' => 'STT',
        'B1' => 'Mã vật tư *',
        'C1' => 'Tên tiếng Anh',
        'D1' => 'Tên tiếng Nga',
        'E1' => 'Tên tiếng Việt',
        'F1' => 'Đặc tính kỹ thuật tiếng Nga',
        'G1' => 'Đặc tính kỹ thuật tiếng Việt',
        'H1' => 'ĐVT tiếng Nga',
        'I1' => 'ĐVT tiếng Việt',
        'J1' => 'Số lượng tồn',
        'K1' => 'Đơn giá (VNĐ)',
        'L1' => 'Đơn giá (USD)',
        'M1' => 'Ngày nhận',
        'N1' => 'Số HĐ',
        'O1' => 'Ngày ký HĐ',
        'P1' => 'Người quản lý',
        'Q1' => 'Vị trí bảo quản',
        'R1' => 'Phân loại',
        'S1' => 'Số Serial',
    ];

    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }

    // Header style
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 11,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '2196F3'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
    ];

    $sheet->getStyle('A1:S1')->applyFromArray($headerStyle);

    // Column widths
    $widths = ['A' => 8, 'B' => 20, 'C' => 30, 'D' => 30, 'E' => 30, 'F' => 35, 'G' => 35,
               'H' => 15, 'I' => 15, 'J' => 12, 'K' => 15, 'L' => 15, 'M' => 15, 'N' => 18,
               'O' => 15, 'P' => 20, 'Q' => 20, 'R' => 15, 'S' => 15];
    
    foreach ($widths as $col => $width) {
        $sheet->getColumnDimension($col)->setWidth($width);
    }

    $sheet->getRowDimension('1')->setRowHeight(25);

    // Sample data
    $sampleData = [
        [1, '011.004.00575', 'CAP ALUM 22UF 20% 450V RADIAL TH', 'Конденсатор ALUM 22мкФ 450В 20% RADIAL TH', 
         'Tụ điện ALUM 22UF 20% 450V RADIAL TH', 
         'Конденсатор ALUM 22мкФ 20% 450V RADIAL Размер: max Φ 20.00mm Kích thước: max Φ 20.00mm',
         'Tụ điện ALUM 22UF 20% 450V RADIAL Kích thước: max Φ 20.00mm',
         'Cái', 'Cái', 50, 50500, 2.15, '20/11/2025', '0044/25/DV-LSTE', 
         '20/07/2025', 'T.N Sang', 'P1. Nga', 'Vật tư', ''],
        [2, '011.002.00859', 'RES 1K OHM 1W 1% AXIAL', 'Резистор 1K OHM 1W 1% AXIAL', 
         'Điện trở 1K OHM 1W 1% AXIAL',
         'Резистор 1K OHM, 1W, ±0.1%, ≥50ppm/°C, AXIAL, термостойкий ≥ 175°C',
         'Điện trở 1K OHM, 1W, ±0.1%, ≥50ppm/°C, AXIAL',
         'Cái', 'Cái', 8, 295000, 12.55, '20/11/2025', '0044/25/DV-LSTE', 
         '20/07/2025', 'T.N Sang', 'P1. Nga', 'Vật tư', ''],
    ];

    $row = 2;
    foreach ($sampleData as $data) {
        $col = 'A';
        foreach ($data as $value) {
            $sheet->setCellValue($col . $row, $value);
            $col++;
        }
        $row++;
    }

    // Data style
    $dataStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'CCCCCC'],
            ],
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_TOP,
        ],
    ];

    $sheet->getStyle('A2:S' . ($row - 1))->applyFromArray($dataStyle);

    // Notes
    $noteRow = $row + 2;
    $notes = [
        'Lưu ý:',
        '• Cột "Mã vật tư" là bắt buộc (đánh dấu *)',
        '• Cột STT tự động tăng, có thể để trống hoặc điền số thứ tự',
        '• Phân loại có thể điền mã (VT, CCDC, TS, PL) hoặc tên đầy đủ (Vật tư, Công cụ dụng cụ, Tài sản, Phế liệu)',
        '• Ngày tháng theo format: dd/mm/yyyy (ví dụ: 20/11/2025)',
        '• Các cột còn lại có thể bỏ trống',
    ];

    foreach ($notes as $i => $note) {
        $sheet->setCellValue('A' . ($noteRow + $i), $note);
    }

    if (count($notes) > 1) {
        $sheet->getStyle('A' . $noteRow)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A' . ($noteRow + 1) . ':A' . ($noteRow + count($notes) - 1))
            ->getFont()
            ->setSize(9)
            ->getColor()
            ->setRGB('666666');
    }

    $sheet->freezePane('A2');

    // Output
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Mau_Import_Vattu_Thanh_Ly.xlsx"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    header('Content-Type: text/plain');
    echo 'Error: ' . $e->getMessage();
    exit;
}
