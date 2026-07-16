<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

try {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('KPI Bao duong');

    $sheet->mergeCells('A1:A2');
    $sheet->mergeCells('B1:B2');
    $sheet->mergeCells('C1:F1');
    $sheet->mergeCells('G1:J1');
    $sheet->mergeCells('K1:O1');
    $sheet->mergeCells('P1:T1');
    $sheet->mergeCells('U1:Y1');
    $sheet->mergeCells('Z1:Z2');

    $sheet->setCellValue('A1', 'STT');
    $sheet->setCellValue('B1', 'Ten thiet bi');
    $sheet->setCellValue('C1', 'Kiem tra thiet bi');
    $sheet->setCellValue('G1', 'Bao duong thuong xuyen (BD cap 1)');
    $sheet->setCellValue('K1', 'Bao duong dinh ky (BD cap 2)');
    $sheet->setCellValue('P1', 'Bao duong dinh ky (BD cap 3)');
    $sheet->setCellValue('U1', 'Hieu chuan thiet bi');
    $sheet->setCellValue('Z1', 'Ghi chu');

    $sheet->fromArray([
        'So luong nhan cong (nguoi)', 'So gio thuc hien', 'Nguoi thuc hien', 'Noi dung cong viec chinh',
        'So luong nhan cong (nguoi)', 'So gio thuc hien', 'Nguoi thuc hien', 'Noi dung cong viec chinh',
        'Tan suat (thang)', 'So luong nhan cong (nguoi)', 'So gio thuc hien', 'Nguoi thuc hien', 'Noi dung cong viec chinh',
        'Tan suat (thang)', 'So luong nhan cong (nguoi)', 'So gio thuc hien', 'Nguoi thuc hien', 'Noi dung cong viec chinh',
        'Tan suat (thang)', 'So luong nhan cong (nguoi)', 'So gio thuc hien', 'Nguoi thuc hien', 'Noi dung cong viec'
    ], null, 'C2');

    $sample = [
        1,
        'MBH',
        1,
        0.5,
        'KS, KTV',
        'Do kiem tra thong mach va cach dien',
        2,
        0.5,
        'KS, KTV + CN',
        'Mo may, ve sinh, kiem tra va thay Oring (neu can), kiem tra oc vit, kiem tra bang mat day dien, do thong mach',
        12,
        2,
        0.5,
        'KS, KTV + CN',
        'Mo may, ve sinh, kiem tra va thay Oring (neu can), kiem tra oc vit, kiem tra bang mat day dien, do thong mach',
        '-',
        '-',
        '-',
        '-',
        '-',
        '-',
        '-',
        '-',
        '-',
        '-',
        ''
    ];
    $sheet->fromArray($sample, null, 'A3');

    $sheet->getStyle('A1:Z3')->getAlignment()->setWrapText(true);
    $sheet->getStyle('A1:Z3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A1:Z3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->getStyle('A1:Z2')->applyFromArray([
        'font' => ['bold' => true],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'D9EAD3']
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ]);
    $sheet->getStyle('A3:Z3')->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ]);

    foreach (range('A', 'Z') as $column) {
        $sheet->getColumnDimension($column)->setWidth(16);
    }
    $sheet->getColumnDimension('B')->setWidth(22);
    $sheet->getColumnDimension('F')->setWidth(34);
    $sheet->getColumnDimension('J')->setWidth(42);
    $sheet->getColumnDimension('O')->setWidth(42);
    $sheet->getColumnDimension('T')->setWidth(42);
    $sheet->getColumnDimension('Y')->setWidth(28);
    $sheet->getColumnDimension('Z')->setWidth(20);
    $sheet->getRowDimension(1)->setRowHeight(24);
    $sheet->getRowDimension(2)->setRowHeight(42);
    $sheet->freezePane('A3');

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Mau_Import_KPI_Bao_Duong_Thiet_Bi.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Loi tao file mau: ' . $e->getMessage();
}