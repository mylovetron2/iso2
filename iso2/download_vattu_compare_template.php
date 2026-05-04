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
    $sheet->setTitle('Import Vật tư');

    // Headers
    $headers = [
        'A1' => 'STT',
        'B1' => 'Mã vật tư',
        'C1' => 'Tên vật tư',
        'D1' => 'Don gia(usd)',
        'E1' => 'Tồn',
        'F1' => 'Phân loại',
    ];

    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }

    // Header style
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12,
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

    $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

    // Column widths
    $widths = [
        'A' => 8,   // STT
        'B' => 20,  // Mã vật tư
        'C' => 50,  // Tên vật tư
        'D' => 15,  // Đơn giá USD
        'E' => 12,  // Tồn
        'F' => 15,  // Phân loại
    ];
    
    foreach ($widths as $col => $width) {
        $sheet->getColumnDimension($col)->setWidth($width);
    }

    $sheet->getRowDimension('1')->setRowHeight(25);

    // Sample data - theo hình ảnh
    $sampleData = [
        [2, '030.037.00001', 'Аэрозоль для чистки контактов - Bình xịt công tắc', 17.16, 14, 'Vật tư'],
        [3, '025.034.00004', 'Găng tay BHLD số 7 - Брезентовые рукавицы раз 7', 0.94, 80, 'Vật tư'],
        [4, '025.034.00005', 'Găng tay BHLD số 9 - Брезентовые рукавицы раз 9', 0.96, 50, 'Vật tư'],
        [6, '004.002.00078', 'ТРУБЫ НКТ Ф 89х6.5 Б/У-Ống NKT cũ', 103.74, 1, 'Vật tư'],
        [7, '004.002.00079', 'Трубы НКТ Ф73*5,5К Р Б/У (Ống cũ)', 31.29, 1, 'Vật tư'],
        [11, '045.013.00074', 'Аэрозоль от ржавчины - Bình xịt gỉ', 4.54, 31, 'Vật tư'],
        [19, '009.004.00094', 'Pin vuông 9V/ батареи', 7.90, 12, 'Vật tư'],
        [20, '009.004.00814', 'Pin Lithium AAA', 4.81, 7, 'Vật tư'],
        [29, '011.005.00465', 'IC PIC12F675-E/P - Микросхема PIC12F675-', 5.75, 5, 'Vật tư'],
        [31, '011.005.00788', 'IC FT2232HL', 10.00, 3, 'Vật tư'],
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

    $sheet->getStyle('A2:F' . ($row - 1))->applyFromArray($dataStyle);

    // Style cho cột B (Mã vật tư) - quan trọng
    $sheet->getStyle('B1')->applyFromArray([
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FF6B6B'], // Màu đỏ để highlight
        ],
    ]);

    // Style cho cột D (Đơn giá USD)
    $sheet->getStyle('D2:D' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('D1')->applyFromArray([
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FFD93D'], // Màu vàng
        ],
    ]);

    // Style cho cột E (Tồn)
    $sheet->getStyle('E2:E' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle('E1')->applyFromArray([
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '95E1D3'], // Màu xanh lá nhạt
        ],
    ]);

    // Style cho cột F (Phân loại)
    $sheet->getStyle('F1')->applyFromArray([
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FF8787'], // Màu đỏ nhạt
        ],
    ]);

    // Notes
    $noteRow = $row + 2;
    $notes = [
        'Lưu ý quan trọng:',
        '• Cột "Mã vật tư" (B) là BẮT BUỘC và dùng để so sánh với dữ liệu hiện có',
        '• Vật tư MỚI (mã chưa có) → Hệ thống sẽ THÊM MỚI vào database',
        '• Vật tư ĐÃ CÓ (mã đã tồn tại) → Hệ thống sẽ CẬP NHẬT SỐ LƯỢNG còn lại',
        '• Vật tư KHÔNG CÓ TRONG FILE (có trong DB nhưng không có trong Excel) → SET SỐ LƯỢNG = 0',
        '• ⚠️ File Excel được coi như SNAPSHOT HOÀN CHỈNH của tồn kho',
        '• Chỉ cập nhật số lượng, không cập nhật thông tin khác (tên, giá...)',
        '• Tên vật tư (C) có thể gồm cả tiếng Nga và tiếng Việt, ngăn cách bởi " - "',
        '• Phân loại (F): VT (Vật tư), CCDC (Công cụ dụng cụ), TS (Tài sản), PL (Phế liệu)',
        '• Đơn vị tính mặc định: шт. (tiếng Nga) / Cái (tiếng Việt)',
    ];

    foreach ($notes as $i => $note) {
        $sheet->setCellValue('A' . ($noteRow + $i), $note);
    }

    if (count($notes) > 1) {
        $sheet->getStyle('A' . $noteRow)->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('FF0000');
        $sheet->getStyle('A' . ($noteRow + 1) . ':A' . ($noteRow + count($notes) - 1))
            ->getFont()
            ->setSize(9)
            ->getColor()
            ->setRGB('333333');
    }

    // Merge cells cho notes để dễ đọc
    for ($i = 0; $i < count($notes); $i++) {
        $sheet->mergeCells('A' . ($noteRow + $i) . ':F' . ($noteRow + $i));
    }

    $sheet->freezePane('A2');

    // Output
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Mau_Import_Vattu_Compare.xlsx"');
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
