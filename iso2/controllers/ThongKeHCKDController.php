<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/HoSoHCKD.php';
require_once __DIR__ . '/../models/ThietBiHCKD.php';

class ThongKeHCKDController
{
    private HoSoHCKD $hoSoModel;
    private ThietBiHCKD $thietBiModel;

    public function __construct()
    {
        $this->hoSoModel = new HoSoHCKD();
        $this->thietBiModel = new ThietBiHCKD();
    }

    /**
     * Hiển thị trang thống kê HC/KĐ theo khoảng thời gian
     */
    public function index(): void
    {
        try {
            // Check if export PDF is requested
            if (isset($_GET['action']) && $_GET['action'] === 'exportPDF') {
                $this->exportPDF();
                return;
            }
            
            // Check if export Excel is requested
            if (isset($_GET['action']) && $_GET['action'] === 'exportExcel') {
                $this->exportExcel();
                return;
            }
            
            // Get date range from query params
            $tungay = $_GET['tungay'] ?? date('Y-m-01'); // First day of current month
            $denngay = $_GET['denngay'] ?? date('Y-m-d'); // Today
            $search = $_GET['search'] ?? '';
            
            // Get statistics
            $items = $this->hoSoModel->getByDateRange($tungay, $denngay, $search);
            $total = count($items);
            
            // Group by công việc (HC/CM)
            $countByType = [
                'HC' => 0,
                'CM' => 0
            ];
            
            foreach ($items as $item) {
                $congviec = $item['congviec'] ?? 'HC';
                if (isset($countByType[$congviec])) {
                    $countByType[$congviec]++;
                }
            }
            
            // Set error to null for view
            $error = null;
            
            require_once __DIR__ . '/../views/thongke_hckd/index.php';
        } catch (Exception $e) {
            error_log("Error in ThongKeHCKDController::index: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            $items = [];
            $total = 0;
            $countByType = ['HC' => 0, 'CM' => 0];
            $tungay = $_GET['tungay'] ?? date('Y-m-01');
            $denngay = $_GET['denngay'] ?? date('Y-m-d');
            $search = $_GET['search'] ?? '';
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            
            require_once __DIR__ . '/../views/thongke_hckd/index.php';
        }
    }
    
    /**
     * Xuất báo cáo PDF theo mẫu Word
     */
    public function exportPDF(): void
    {
        try {
            // Get date range from query params
            $tungay = $_GET['tungay'] ?? date('Y-m-01');
            $denngay = $_GET['denngay'] ?? date('Y-m-d');
            $search = $_GET['search'] ?? '';
            
            // Get data
            $items = $this->hoSoModel->getByDateRange($tungay, $denngay, $search);
            
            // Load TCPDF library
            require_once(__DIR__ . '/../libs/tcpdf/tcpdf.php');
            
            // Create PDF instance - Landscape orientation
            $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('ISO2 System');
            $pdf->SetAuthor('XN Địa Vật Lý GK');
            $pdf->SetTitle('Báo Cáo Hiệu Chuẩn Thiết Bị');
            $pdf->SetSubject('Thống kê HC/KĐ');
            
            // Set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            
            // Set margins
            $pdf->SetMargins(10, 15, 10);
            $pdf->SetHeaderMargin(5);
            $pdf->SetFooterMargin(10);
            
            // Set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, 15);
            
            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Set font
            $pdf->SetFont('dejavusans', '', 10);
            
            // Add a page
            $pdf->AddPage();
            
            // Title section
            $html = '<table style="width: 100%; margin-bottom: 5px;" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width: 30%; font-weight: bold; font-size: 11pt; vertical-align: top;">XN Địa Vật Lý GK</td>
                    <td style="width: 40%; text-align: center; font-size: 14pt; font-weight: bold; vertical-align: top;">Liệt Kê Công Tác Hiệu Chuẩn Thiết Bị</td>
                    <td style="width: 30%; vertical-align: top;"></td>
                </tr>
                <tr>
                    <td style="font-weight: bold; font-size: 11pt;">Xưởng SC&CCMĐVL</td>
                    <td style="text-align: center; font-weight: bold; font-size: 11pt;">Từ ' . date('d-m-y', strtotime($tungay)) . ' đến ' . date('d-m-y', strtotime($denngay)) . '</td>
                    <td style="text-align: right;"></td>
                </tr>
            </table>';
            
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Ln(3);
            
            // Data table - Use consistent widths
            $tableHtml = '<table border="1" cellpadding="3" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 9pt;">
                <thead>
                    <tr style="background-color: #f0f0f0; font-weight: bold;">
                        <th style="width: 5%; text-align: center; border: 1px solid #000;">STT</th>
                        <th style="width: 10%; text-align: center; border: 1px solid #000;">SỐ HỒ SƠ</th>
                        <th style="width: 18%; text-align: center; border: 1px solid #000;">TÊN MÁY</th>
                        <th style="width: 12%; text-align: center; border: 1px solid #000;">SỐ MÁY</th>
                        <th style="width: 8%; text-align: center; border: 1px solid #000;">C.VIỆC</th>
                        <th style="width: 12%; text-align: center; border: 1px solid #000;">NTH</th>
                        <th style="width: 13%; text-align: center; border: 1px solid #000;">NH.VIÊN</th>
                        <th style="width: 10%; text-align: center; border: 1px solid #000;">NƠI.TH</th>
                        <th style="width: 12%; text-align: center; border: 1px solid #000;">Bộ phận</th>
                    </tr>
                </thead>
                <tbody>';
            
            $stt = 1;
            foreach ($items as $item) {
                $congviec = htmlspecialchars($item['congviec'] ?? 'HC');
                $ngayhc = date('d/m/Y', strtotime($item['ngayhc']));
                
                $tableHtml .= '<tr>
                    <td style="width: 5%; text-align: center; border: 1px solid #000;">' . $stt++ . '</td>
                    <td style="width: 10%; border: 1px solid #000;">' . htmlspecialchars($item['sohs'] ?? '') . '</td>
                    <td style="width: 18%; border: 1px solid #000;">' . htmlspecialchars($item['tenthietbi'] ?? '') . '</td>
                    <td style="width: 12%; border: 1px solid #000;">' . htmlspecialchars($item['tenmay'] ?? '') . '</td>
                    <td style="width: 8%; text-align: center; border: 1px solid #000;">' . $congviec . '</td>
                    <td style="width: 12%; text-align: center; border: 1px solid #000;">' . $ngayhc . '</td>
                    <td style="width: 13%; border: 1px solid #000;">' . htmlspecialchars($item['nhanvien'] ?? '') . '</td>
                    <td style="width: 10%; text-align: center; border: 1px solid #000;">' . htmlspecialchars($item['bophansh'] ?? '') . '</td>
                    <td style="width: 12%; border: 1px solid #000;">' . htmlspecialchars($item['chusohuu'] ?? '') . '</td>
                </tr>';
            }
            
            $tableHtml .= '</tbody></table>';
            
            $pdf->writeHTML($tableHtml, true, false, true, false, '');
            
            // Output PDF
            $filename = 'baocaothang-' . $tungay . '-' . $denngay . '.pdf';
            $pdf->Output($filename, 'I');
            
        } catch (Exception $e) {
            error_log("Error in ThongKeHCKDController::exportPDF: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            die('Có lỗi xảy ra khi xuất PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Xuất báo cáo Excel
     */
    public function exportExcel(): void
    {
        try {
            // Get date range from query params
            $tungay = $_GET['tungay'] ?? date('Y-m-01');
            $denngay = $_GET['denngay'] ?? date('Y-m-d');
            $search = $_GET['search'] ?? '';
            
            // Get data
            $items = $this->hoSoModel->getByDateRange($tungay, $denngay, $search);
            
            // Load PhpSpreadsheet
            require_once(__DIR__ . '/../vendor/autoload.php');
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Thong ke HC-KD');
            
            // Title section
            $sheet->mergeCells('A1:I1');
            $sheet->setCellValue('A1', 'XN Địa Vật Lý GK - Xưởng SC&CCMĐVL');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            $sheet->mergeCells('A2:I2');
            $sheet->setCellValue('A2', 'LIỆT KÊ CÔNG TÁC HIỆU CHUẨN THIẾT BỊ');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            $sheet->mergeCells('A3:I3');
            $sheet->setCellValue('A3', 'Từ ' . date('d-m-Y', strtotime($tungay)) . ' đến ' . date('d-m-Y', strtotime($denngay)));
            $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            // Headers
            $headers = ['STT', 'SỐ HỒ SƠ', 'TÊN MÁY', 'SỐ MÁY', 'C.VIỆC', 'NTH', 'NH.VIÊN', 'NƠI.TH', 'Bộ phận'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '5', $header);
                $col++;
            }
            
            // Header style
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ];
            $sheet->getStyle('A5:I5')->applyFromArray($headerStyle);
            
            // Data rows
            $row = 6;
            $stt = 1;
            foreach ($items as $item) {
                $congviec = $item['congviec'] ?? 'HC';
                $ngayhc = date('d/m/Y', strtotime($item['ngayhc']));
                
                $sheet->setCellValue('A' . $row, $stt++);
                $sheet->setCellValue('B' . $row, $item['sohs'] ?? '');
                $sheet->setCellValue('C' . $row, $item['tenthietbi'] ?? '');
                $sheet->setCellValue('D' . $row, $item['tenmay'] ?? '');  // Sửa: dùng tenmay thay vì tenthietbi
                $sheet->setCellValue('E' . $row, $congviec);
                $sheet->setCellValue('F' . $row, $ngayhc);
                $sheet->setCellValue('G' . $row, $item['nhanvien'] ?? '');
                $sheet->setCellValue('H' . $row, $item['bophansh'] ?? '');
                $sheet->setCellValue('I' . $row, $item['chusohuu'] ?? '');
                
                $row++;
            }
            
            // Apply borders to data
            $lastRow = $row - 1;
            if ($lastRow >= 6) {
                $sheet->getStyle('A5:I' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);
                
                // Center align columns - only if there's data
                $sheet->getStyle('A6:A' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('E6:F' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('H6:H' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }
            
            // Column widths
            $sheet->getColumnDimension('A')->setWidth(6);
            $sheet->getColumnDimension('B')->setWidth(15);
            $sheet->getColumnDimension('C')->setWidth(25);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(10);
            $sheet->getColumnDimension('F')->setWidth(13);
            $sheet->getColumnDimension('G')->setWidth(20);
            $sheet->getColumnDimension('H')->setWidth(12);
            $sheet->getColumnDimension('I')->setWidth(20);
            
            // Output Excel file
            $filename = 'baocao-hckd-' . $tungay . '-' . $denngay . '.xlsx';
            
            // Clean any previous output
            if (ob_get_length()) {
                ob_end_clean();
            }
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
            
        } catch (Exception $e) {
            error_log("Error in ThongKeHCKDController::exportExcel: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            die('Có lỗi xảy ra khi xuất Excel: ' . $e->getMessage());
        }
    }
}
