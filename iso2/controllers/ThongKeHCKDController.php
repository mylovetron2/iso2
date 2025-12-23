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
                    <td style="width: 12%; border: 1px solid #000;">' . htmlspecialchars($item['tenthietbi'] ?? '') . '</td>
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
}
