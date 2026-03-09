<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

try {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Kế hoạch bảo dưỡng');

    // Header row
    $headers = ['STT', 'Tên thiết bị', 'Số S/N', 'Quí 1', 'Quí 2', 'Quí 3', 'Quí 4', 'Ghi chú'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $col++;
    }

    // Style header
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4472C4']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

    // Sample data
    $samples = [
        [1, 'GTET', '11533904', 'TO', '', '', '', ''],
        [2, 'GTET', '11705762', 'TO', '', '', '', ''],
        [3, 'GTET', '11705765', 'TO', '', '', '', ''],
        [4, 'IDT', '11680456', 'TO', '', '', '', ''],
        [5, 'IDT', '11680458', 'TO', '', '', '', 'Máy đo nhiệt độ cao 180°C'],
        [6, 'DSNT', '11534471', 'TO', '', '', '', ''],
        [7, 'DSNT', '11534475', 'TO', '', '', '', ''],
        [8, 'DSNT', '11660710', 'TO', '', '', '', ''],
        [9, 'DSNT', '11660711', 'TO', '', '', '', 'Máy đo nhiệt độ cao 180°C'],
    ];

    $row = 2;
    foreach ($samples as $sample) {
        $col = 'A';
        foreach ($sample as $value) {
            $sheet->setCellValue($col . $row, $value);
            $col++;
        }
        $row++;
    }

    // Add borders to data
    $sheet->getStyle('A2:H' . ($row - 1))->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ]);

    // Center align STT and quarters
    $sheet->getStyle('A2:A' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('D2:G' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Tô màu xanh cho các ô có giá trị "TO" 
    for ($i = 2; $i < $row; $i++) {
        // Kiểm tra từng cột quý (D, E, F, G)
        foreach (['D', 'E', 'F', 'G'] as $col) {
            $cellValue = strtoupper(trim($sheet->getCell($col . $i)->getValue() ?? ''));
            if ($cellValue === 'TO') {
                $sheet->getStyle($col . $i)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('C6EFCE'); // Màu xanh lá nhạt
            }
        }
    }

    // Column widths
    $sheet->getColumnDimension('A')->setWidth(6);
    $sheet->getColumnDimension('B')->setWidth(20);
    $sheet->getColumnDimension('C')->setWidth(15);
    $sheet->getColumnDimension('D')->setWidth(10);
    $sheet->getColumnDimension('E')->setWidth(10);
    $sheet->getColumnDimension('F')->setWidth(10);
    $sheet->getColumnDimension('G')->setWidth(10);
    $sheet->getColumnDimension('H')->setWidth(35);

    // Add note
    $noteRow = $row + 2;
    $sheet->setCellValue('A' . $noteRow, 'Ghi chú:');
    $sheet->setCellValue('A' . ($noteRow + 1), '- TO = Có kế hoạch bảo dưỡng (Technical Overhaul)');
    $sheet->setCellValue('A' . ($noteRow + 2), '- Để trống nếu không có kế hoạch trong quý đó');
    $sheet->setCellValue('A' . ($noteRow + 3), '- Có thể thêm/xóa dòng thiết bị tùy ý');
    
    $sheet->getStyle('A' . $noteRow . ':A' . ($noteRow + 3))->getFont()->setItalic(true)->setSize(9);
    $sheet->getStyle('A' . $noteRow)->getFont()->setBold(true);

    // Freeze header row
    $sheet->freezePane('A2');

    // Output
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Mau_Ke_Hoach_Bao_Duong_' . date('Y') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    die('Lỗi tạo file Excel: ' . $e->getMessage());
}
