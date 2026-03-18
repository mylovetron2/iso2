<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ThongKeVatTuThanhLyController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    /**
     * Hiển thị trang thống kê vật tư thanh lý theo khoảng thời gian
     */
    public function index(): void
    {
        try {
            // Check if export Excel is requested
            if (isset($_GET['action']) && $_GET['action'] === 'exportExcel') {
                $this->exportExcel();
                return;
            }
            
            // Check if export Word is requested
            if (isset($_GET['action']) && $_GET['action'] === 'exportWord') {
                $this->exportWord();
                return;
            }
            
            // Check if export Phieu KSVT is requested
            if (isset($_GET['action']) && $_GET['action'] === 'exportPhieuKSVT') {
                $this->exportPhieuKSVT();
                return;
            }
            
            // Check if export Excel Procurement is requested
            if (isset($_GET['action']) && $_GET['action'] === 'exportExcelProcurement') {
                $this->exportExcelProcurement();
                return;
            }
            
            // Get date range from query params
            $tungay = $_GET['tungay'] ?? date('Y-m-01'); // First day of current month
            $denngay = $_GET['denngay'] ?? date('Y-m-d'); // Today
            $search = $_GET['search'] ?? '';
            $phanloai_id = !empty($_GET['phanloai_id']) ? (int)$_GET['phanloai_id'] : null;
            $bophan = $_GET['bophan'] ?? '';
            
            // Get list of phanloai for dropdown
            $stmtPhanLoai = $this->db->query("SELECT id, ma_phanloai, ten_phanloai, mau_sac FROM phanloai_vattu_thanh_ly_iso ORDER BY thu_tu ASC");
            $phanloaiList = $stmtPhanLoai->fetchAll(PDO::FETCH_ASSOC);
            
            // Get list of donvi for dropdown
            $stmtDonVi = $this->db->query("SELECT madv, tendv FROM donvi_iso ORDER BY tendv ASC");
            $donViList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);
            
            // Get statistics
            $items = $this->getThanhLyByDateRange($tungay, $denngay, $search, $phanloai_id, $bophan);
            $total = count($items);
            
            // Calculate totals
            $totalQuantity = 0;
            $totalAmount = 0;
            
            foreach ($items as $item) {
                $totalQuantity += $item['soluong_thaydoi'];
                $totalAmount += $item['thanhtien'];
            }
            
            // Set error to null for view
            $error = null;
            
            require_once __DIR__ . '/../views/vattuthanhly/thongke.php';
        } catch (Exception $e) {
            error_log("Error in ThongKeVatTuThanhLyController::index: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            $items = [];
            $total = 0;
            $totalQuantity = 0;
            $totalAmount = 0;
            $tungay = $_GET['tungay'] ?? date('Y-m-01');
            $denngay = $_GET['denngay'] ?? date('Y-m-d');
            $search = $_GET['search'] ?? '';
            $phanloai_id = !empty($_GET['phanloai_id']) ? (int)$_GET['phanloai_id'] : null;
            $bophan = $_GET['bophan'] ?? '';
            $phanloaiList = [];
            $donViList = [];
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            
            require_once __DIR__ . '/../views/vattuthanhly/thongke.php';
        }
    }
    
    /**
     * Lấy danh sách vật tư thanh lý theo khoảng thời gian
     */
    private function getThanhLyByDateRange(string $tungay, string $denngay, string $search = '', ?int $phanloai_id = null, string $bophan = ''): array
    {
        $sql = "SELECT 
                    s.id,
                    v.stt as vattu_stt,
                    v.mavattu,
                    v.ten_tiengviet,
                    v.ten_tienganh,
                    v.ten_tiengnga,
                    v.dactinhkt_tiengviet,
                    v.dvt_tiengviet as donvi,
                    v.dvt_tiengnga,
                    v.dongia,
                    v.phanloai_id,
                    pl.ten_phanloai,
                    pl.ma_phanloai,
                    s.soluong as soluong_thaydoi,
                    s.soluong * v.dongia as thanhtien,
                    COALESCE(s.ngayhoanthanh, s.ngaysd_nhan, DATE(s.updated_at)) as ngay_thuchien,
                    s.mucdich_sudung as nguyennhan,
                    s.nguoisudung as nguoi_thuchien,
                    s.bophan,
                    s.trangthai,
                    '' as namsd
                FROM vattu_thanh_ly_sudung_iso s
                INNER JOIN vattu_thanh_ly_iso v ON s.vattu_stt = v.stt
                LEFT JOIN phanloai_vattu_thanh_ly_iso pl ON v.phanloai_id = pl.id
                WHERE DATE(COALESCE(s.ngayhoanthanh, s.ngaysd_nhan, s.updated_at)) BETWEEN :tungay AND :denngay";
        
        $params = [
            ':tungay' => $tungay,
            ':denngay' => $denngay
        ];
        
        // Add phanloai filter if provided
        if ($phanloai_id) {
            $sql .= " AND v.phanloai_id = :phanloai_id";
            $params[':phanloai_id'] = $phanloai_id;
        }
        
        // Add bophan filter if provided
        if (!empty($bophan)) {
            // Nếu chọn "Đội ĐVL Tổng hợp" (madv = DVLTH) thì lọc cả TH và CNC
            if ($bophan === 'DVLTH') {
                $sql .= " AND s.bophan IN ('TH', 'CNC')";
            } else {
                $sql .= " AND s.bophan = :bophan";
                $params[':bophan'] = $bophan;
            }
        }
        
        // Add search filter if provided
        if (!empty($search)) {
            $sql .= " AND (
                v.mavattu LIKE :search 
                OR v.ten_tiengviet LIKE :search 
                OR v.ten_tienganh LIKE :search
                OR v.ten_tiengnga LIKE :search
                OR s.ghichu LIKE :search
                OR s.bophan LIKE :search
                OR s.nguoisudung LIKE :search
            )";
            $params[':search'] = '%' . $search . '%';
        }
        
        $sql .= " ORDER BY ngay_thuchien ASC, v.mavattu ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            $phanloai_id = !empty($_GET['phanloai_id']) ? (int)$_GET['phanloai_id'] : null;
            $bophan = $_GET['bophan'] ?? '';
            
            // Get data
            $items = $this->getThanhLyByDateRange($tungay, $denngay, $search, $phanloai_id, $bophan);
            
            // Load PhpSpreadsheet
            require_once(__DIR__ . '/../vendor/autoload.php');
            
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set title
            $sheet->setCellValue('A1', 'THỐNG KÊ VẬT TƯ THANH LÝ');
            $sheet->mergeCells('A1:I1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Set date range
            $sheet->setCellValue('A2', 'Từ ngày ' . date('d/m/Y', strtotime($tungay)) . ' đến ngày ' . date('d/m/Y', strtotime($denngay)));
            $sheet->mergeCells('A2:I2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Set headers
            $headerRow = 4;
            $headers = [
                'A' => 'TT',
                'B' => "Mã vật tư\nНоменкла-турный код",
                'C' => "Tên vật tư\nНаименование материалов",
                'D' => "Đơn vị\nЕд. изм",
                'E' => "Năm SD\nСрок эксплуа-тации (лет)",
                'F' => "Số lượng\nКол-во",
                'G' => "Đơn giá\nЦена",
                'H' => "Thành tiền\nСумма",
                'I' => "Nguyên nhân\nПричина списания"
            ];
            
            foreach ($headers as $col => $header) {
                $sheet->setCellValue($col . $headerRow, $header);
                $sheet->getStyle($col . $headerRow)->getFont()->setBold(true);
                $sheet->getStyle($col . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($col . $headerRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle($col . $headerRow)->getAlignment()->setWrapText(true);
                $sheet->getStyle($col . $headerRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9D9D9');
            }
            
            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(6);
            $sheet->getColumnDimension('B')->setWidth(18);
            $sheet->getColumnDimension('C')->setWidth(35);
            $sheet->getColumnDimension('D')->setWidth(10);
            $sheet->getColumnDimension('E')->setWidth(8);
            $sheet->getColumnDimension('F')->setWidth(10);
            $sheet->getColumnDimension('G')->setWidth(12);
            $sheet->getColumnDimension('H')->setWidth(15);
            $sheet->getColumnDimension('I')->setWidth(25);
            
            $sheet->getRowDimension($headerRow)->setRowHeight(40);
            
            // Fill data
            $row = $headerRow + 1;
            $stt = 1;
            $totalQuantity = 0;
            $totalAmount = 0;
            
            foreach ($items as $item) {
                $tenvattu = $item['ten_tiengviet'] ?: $item['ten_tienganh'] ?: $item['ten_tiengnga'];
                
                $soluong = $item['soluong_thaydoi'];
                $thanhtien = $item['thanhtien'];
                
                $totalQuantity += $soluong;
                $totalAmount += $thanhtien;
                
                $sheet->setCellValue('A' . $row, $stt);
                $sheet->setCellValue('B' . $row, $item['mavattu']);
                $sheet->setCellValue('C' . $row, $tenvattu);
                $sheet->setCellValue('D' . $row, $item['donvi'] ?: $item['dvt_tiengnga']);
                $sheet->setCellValue('E' . $row, $item['namsd']);
                $sheet->setCellValue('F' . $row, number_format($soluong, 2, ',', '.'));
                $sheet->setCellValue('G' . $row, number_format($item['dongia'], 2, ',', '.'));
                $sheet->setCellValue('H' . $row, number_format($thanhtien, 2, ',', '.'));
                $sheet->setCellValue('I' . $row, $item['nguyennhan']);
                
                // Center alignment for certain columns
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                // Wrap text for long descriptions
                $sheet->getStyle('C' . $row)->getAlignment()->setWrapText(true);
                $sheet->getStyle('I' . $row)->getAlignment()->setWrapText(true);
                
                $row++;
                $stt++;
            }
            
            // Add total row
            if (!empty($items)) {
                $sheet->setCellValue('A' . $row, 'TỔNG CỘNG');
                $sheet->mergeCells('A' . $row . ':E' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('F' . $row, number_format($totalQuantity, 2, ',', '.'));
                $sheet->setCellValue('G' . $row, '');
                $sheet->setCellValue('H' . $row, number_format($totalAmount, 2, ',', '.'));
                $sheet->setCellValue('I' . $row, '');
                
                $sheet->getStyle('F' . $row)->getFont()->setBold(true);
                $sheet->getStyle('H' . $row)->getFont()->setBold(true);
                $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                // Apply borders to total row
                $sheet->getStyle('A' . $row . ':I' . $row)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            }
            
            // Apply borders to all data
            $lastRow = $row;
            $sheet->getStyle('A' . $headerRow . ':I' . $lastRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            
            // Clean output buffer before download
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Set header
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Thong_Ke_Vat_Tu_Thanh_Ly_' . date('Ymd') . '.xlsx"');
            header('Cache-Control: max-age=0');
            
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
            
        } catch (Exception $e) {
            error_log("Error in exportExcel: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            die('Lỗi khi xuất Excel: ' . $e->getMessage());
        }
    }
    
    /**
     * Export statistics to Word document
     */
    public function exportWord(): void {
        try {
            // Get date range
            $tungay = $_GET['tungay'] ?? date('Y-m-01');
            $denngay = $_GET['denngay'] ?? date('Y-m-d');
            $search = $_GET['search'] ?? '';
            $phanloai_id = !empty($_GET['phanloai_id']) ? (int)$_GET['phanloai_id'] : null;
            $bophan = $_GET['bophan'] ?? '';
            
            // Get data
            $items = $this->getThanhLyByDateRange($tungay, $denngay, $search, $phanloai_id, $bophan);
            $total = count($items);
            
            // Calculate totals
            $totalQuantity = 0;
            $totalAmount = 0;
            
            foreach ($items as $item) {
                $totalQuantity += $item['soluong_thaydoi'];
                $totalAmount += $item['thanhtien'];
            }
            
            // Convert total to Vietnamese words
            $totalInWords = $this->numberToVietnameseWords($total);
            
            // Determine which template to use based on phanloai
            $templateFile = 'export_word.php'; // Default template
            
            if ($phanloai_id) {
                $stmt = $this->db->prepare("SELECT ten_phanloai FROM phanloai_vattu_thanh_ly_iso WHERE id = :id");
                $stmt->execute([':id' => $phanloai_id]);
                $phanloai = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($phanloai && stripos($phanloai['ten_phanloai'], 'Công cụ dụng cụ') !== false) {
                    $templateFile = 'export_word_congcu.php';
                }
            }
            
            // Set headers for Word download
            header('Content-Type: application/vnd.ms-word; charset=UTF-8');
            header('Content-Disposition: attachment; filename="Thong_Ke_Vat_Tu_Thanh_Ly_' . date('Ymd') . '.doc"');
            header('Cache-Control: max-age=0');
            
            // Include the appropriate Word template
            require_once __DIR__ . '/../views/vattuthanhly/' . $templateFile;
            exit;
            
        } catch (Exception $e) {
            error_log("Error in exportWord: " . $e->getMessage());
            die("Error exporting to Word: " . $e->getMessage());
        }
    }
    
    /**
     * Export Phieu Kiem Soat Vat Tu (Equipment Control Voucher) to Word document
     */
    public function exportPhieuKSVT(): void {
        try {
            // Get date range
            $tungay = $_GET['tungay'] ?? date('Y-m-01');
            $denngay = $_GET['denngay'] ?? date('Y-m-d');
            $search = $_GET['search'] ?? '';
            $phanloai_id = !empty($_GET['phanloai_id']) ? (int)$_GET['phanloai_id'] : null;
            $bophan = $_GET['bophan'] ?? '';
            
            // Get data
            $items = $this->getThanhLyByDateRange($tungay, $denngay, $search, $phanloai_id, $bophan);
            
            // Set headers for Word download
            header('Content-Type: application/vnd.ms-word; charset=UTF-8');
            header('Content-Disposition: attachment; filename="Phieu_Kiem_Soat_Vat_Tu_' . date('Ymd') . '.doc"');
            header('Cache-Control: max-age=0');
            
            // Include the template
            require_once __DIR__ . '/../views/vattuthanhly/export_phieu_ksvt.php';
            exit;
            
        } catch (Exception $e) {
            error_log("Error in exportPhieuKSVT: " . $e->getMessage());
            die("Error exporting Phieu KSVT: " . $e->getMessage());
        }
    }
    
    /**
     * Export procurement cost calculation to Excel
     */
    public function exportExcelProcurement(): void {
        try {
            // Get date range
            $tungay = $_GET['tungay'] ?? date('Y-m-01');
            $denngay = $_GET['denngay'] ?? date('Y-m-d');
            $search = $_GET['search'] ?? '';
            $phanloai_id = !empty($_GET['phanloai_id']) ? (int)$_GET['phanloai_id'] : null;
            $bophan = $_GET['bophan'] ?? '';
            
            // Get data
            $items = $this->getThanhLyByDateRange($tungay, $denngay, $search, $phanloai_id, $bophan);
            
            // Load PhpSpreadsheet
            require_once(__DIR__ . '/../vendor/autoload.php');
            
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Exchange rate
            $exchangeRate = 25380;
            $year = date('Y', strtotime($denngay));
            
            // Title
            $sheet->setCellValue('A1', 'РАСЧЁТ СТОИМОСТИ');
            $sheet->mergeCells('A1:K1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Subtitle
            $sheet->setCellValue('A2', "Hа закупку измерительных приборов в {$year} г");
            $sheet->mergeCells('A2:K2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Exchange rate
            $sheet->setCellValue('G3', 'Tỷ giá:');
            $sheet->setCellValue('H3', $exchangeRate);
            $sheet->getStyle('G3')->getFont()->setBold(true);
            
            // Headers row 1
            $row = 5;
            $sheet->setCellValue('A' . $row, "П/п\n(Stt)");
            $sheet->mergeCells('A' . $row . ':A' . ($row + 1));
            
            $sheet->setCellValue('B' . $row, "Наименование\n(Tên hàng hóa/Dịch vụ)");
            $sheet->mergeCells('B' . $row . ':C' . $row);
            
            $sheet->setCellValue('D' . $row, "Ед.изм\n(Đơn vị tính)");
            $sheet->mergeCells('D' . $row . ':D' . ($row + 1));
            
            $sheet->setCellValue('E' . $row, "Кол-во закупки\n(SL cần mua)");
            $sheet->mergeCells('E' . $row . ':E' . ($row + 1));
            
            $sheet->setCellValue('F' . $row, "Цена\nĐơn giá\n(VND)");
            $sheet->mergeCells('F' . $row . ':F' . ($row + 1));
            
            $sheet->setCellValue('G' . $row, "Коэ-т повы-ия цен\n(Hệ số trượt giá)");
            $sheet->mergeCells('G' . $row . ':G' . ($row + 1));
            
            $sheet->setCellValue('H' . $row, "Стоимость\nTổng giá trị\n(VND)");
            $sheet->mergeCells('H' . $row . ':H' . ($row + 1));
            
            $sheet->setCellValue('I' . $row, "Примечание\n(Ghi chú)");
            $sheet->mergeCells('I' . $row . ':I' . ($row + 1));
            
            $sheet->setCellValue('J' . $row, 'Mã vật tư');
            $sheet->mergeCells('J' . $row . ':J' . ($row + 1));
            
            $sheet->setCellValue('K' . $row, 'Vật tư/\nCông cụ');
            $sheet->mergeCells('K' . $row . ':K' . ($row + 1));
            
            // Headers row 2
            $row++;
            $sheet->setCellValue('B' . $row, "На Русс. языке\n(Tiếng Nga)");
            $sheet->setCellValue('C' . $row, "На Вьетнам. языке\n(Tiếng Việt)");
            
            // Number row
            $row++;
            $sheet->setCellValue('A' . $row, '1');
            $sheet->setCellValue('B' . $row, '2');
            $sheet->setCellValue('C' . $row, '3');
            $sheet->setCellValue('D' . $row, '4');
            $sheet->setCellValue('E' . $row, '5');
            $sheet->setCellValue('F' . $row, '6');
            $sheet->setCellValue('G' . $row, '7');
            $sheet->setCellValue('H' . $row, '8=5x6x7');
            $sheet->setCellValue('I' . $row, '9');
            
            // Style headers
            for ($i = 5; $i <= $row; $i++) {
                $sheet->getStyle('A' . $i . ':K' . $i)->getFont()->setBold(true);
                $sheet->getStyle('A' . $i . ':K' . $i)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A' . $i . ':K' . $i)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A' . $i . ':K' . $i)->getAlignment()->setWrapText(true);
                $sheet->getStyle('A' . $i . ':K' . $i)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9D9D9');
            }
            
            // Column widths
            $sheet->getColumnDimension('A')->setWidth(6);
            $sheet->getColumnDimension('B')->setWidth(35);
            $sheet->getColumnDimension('C')->setWidth(35);
            $sheet->getColumnDimension('D')->setWidth(8);
            $sheet->getColumnDimension('E')->setWidth(10);
            $sheet->getColumnDimension('F')->setWidth(12);
            $sheet->getColumnDimension('G')->setWidth(10);
            $sheet->getColumnDimension('H')->setWidth(15);
            $sheet->getColumnDimension('I')->setWidth(25);
            $sheet->getColumnDimension('J')->setWidth(15);
            $sheet->getColumnDimension('K')->setWidth(12);
            
            // Data rows
            $row++;
            $stt = 1;
            $totalVND = 0;
            
            foreach ($items as $item) {
                $tenNga = $item['ten_tiengnga'] ?: '';
                $tenViet = $item['ten_tiengviet'] ?: $item['ten_tienganh'];
                $donvi = $item['donvi'] ?: $item['dvt_tiengnga'];
                $soluong = $item['soluong_thaydoi'];
                $dongia = $item['dongia'];
                $hesotruot = 1.00;
                $thanhtien = $soluong * $dongia * $hesotruot;
                $ghichu = $item['nguyennhan'] ?? '';
                $mavattu = $item['mavattu'];
                
                // Determine type based on phanloai
                $loai = 'Vật tư';
                if (stripos($item['ten_phanloai'] ?? '', 'công cụ') !== false) {
                    $loai = 'Công cụ';
                }
                
                $totalVND += $thanhtien;
                
                $sheet->setCellValue('A' . $row, $stt);
                $sheet->setCellValue('B' . $row, $tenNga);
                $sheet->setCellValue('C' . $row, $tenViet);
                $sheet->setCellValue('D' . $row, 'Cái');
                $sheet->setCellValue('E' . $row, $soluong);
                $sheet->setCellValue('F' . $row, $dongia);
                $sheet->setCellValue('G' . $row, $hesotruot);
                $sheet->setCellValue('H' . $row, $thanhtien);
                $sheet->setCellValue('I' . $row, $ghichu);
                $sheet->setCellValue('J' . $row, $mavattu);
                $sheet->setCellValue('K' . $row, $loai);
                
                // Format numbers
                $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('0.00');
                $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0');
                
                // Alignment
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                $row++;
                $stt++;
            }
            
            // Totals
            $totalUSD = $totalVND / $exchangeRate;
            $vatVND = $totalVND * 0.10;
            $vatUSD = $vatVND / $exchangeRate;
            $grandTotalVND = $totalVND + $vatVND;
            $grandTotalUSD = $grandTotalVND / $exchangeRate;
            
            // Total row
            $sheet->setCellValue('A' . $row, 'Cộng/ Итого');
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $sheet->setCellValue('H' . $row, $totalVND);
            $sheet->setCellValue('I' . $row, 'VND');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('H' . $row)->getFont()->setBold(true);
            $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $row++;
            
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $sheet->setCellValue('H' . $row, $totalUSD);
            $sheet->setCellValue('I' . $row, 'USD');
            $sheet->getStyle('H' . $row)->getFont()->setBold(true);
            $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $row++;
            
            // VAT row
            $sheet->setCellValue('A' . $row, 'Thuế VAT/ НДС');
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $sheet->setCellValue('H' . $row, $vatVND);
            $sheet->setCellValue('I' . $row, 'VND');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('H' . $row)->getFont()->setBold(true);
            $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $row++;
            
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $sheet->setCellValue('H' . $row, $vatUSD);
            $sheet->setCellValue('I' . $row, 'USD');
            $sheet->getStyle('H' . $row)->getFont()->setBold(true);
            $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $row++;
            
            // Grand total row
            $sheet->setCellValue('A' . $row, 'Tổng Giá trị hàng hóa/ Общая стоимость');
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $sheet->setCellValue('H' . $row, $grandTotalVND);
            $sheet->setCellValue('I' . $row, 'VND');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('H' . $row)->getFont()->setBold(true);
            $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $row++;
            
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $sheet->setCellValue('H' . $row, $grandTotalUSD);
            $sheet->setCellValue('I' . $row, 'USD');
            $sheet->getStyle('H' . $row)->getFont()->setBold(true);
            $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $row += 2;
            
            // Signatures
            $sheet->setCellValue('B' . $row, 'Зам. директора КПГ');
            $sheet->setCellValue('H' . $row, 'Нгуен Зуи Нгок');
            $row += 2;
            
            $sheet->setCellValue('A' . $row, 'Ký tắt - Визы:');
            $row++;
            $sheet->setCellValue('B' . $row, 'Ban VTHC - CМТОиЛ');
            $sheet->setCellValue('H' . $row, 'Фан Ван Хоа');
            $row += 2;
            
            $sheet->setCellValue('B' . $row, 'Xưởng trưởng XSCTBĐVL /Начальник ЦРГО');
            $sheet->setCellValue('H' . $row, 'Данг Ван Туэ');
            
            // Borders for data area
            $lastDataRow = $row - 8; // Before totals
            $sheet->getStyle('A5:K' . $lastDataRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            
            // Clean output buffer before download
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Procurement_Cost_' . date('Ymd') . '.xlsx"');
            header('Cache-Control: max-age=0');
            
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
            
        } catch (Exception $e) {
            error_log("Error in exportExcelProcurement: " . $e->getMessage());
            die("Error exporting Excel Procurement: " . $e->getMessage());
        }
    }
    
    /**
     * Convert number to Vietnamese words
     */
    private function numberToVietnameseWords(int $number): string {
        if ($number == 0) return 'không';
        
        $units = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
        $teens = ['mười', 'mười một', 'mười hai', 'mười ba', 'mười bốn', 'mười lăm', 
                  'mười sáu', 'mười bảy', 'mười tám', 'mười chín'];
        
        if ($number < 10) {
            return $units[$number];
        } elseif ($number < 20) {
            return $teens[$number - 10];
        } elseif ($number < 100) {
            $tens = intdiv($number, 10);
            $ones = $number % 10;
            $result = $units[$tens] . ' mươi';
            if ($ones == 1) {
                $result .= ' mốt';
            } elseif ($ones == 5 && $tens > 1) {
                $result .= ' lăm';
            } elseif ($ones > 0) {
                $result .= ' ' . $units[$ones];
            }
            return $result;
        } elseif ($number < 1000) {
            $hundreds = intdiv($number, 100);
            $remainder = $number % 100;
            $result = $units[$hundreds] . ' trăm';
            if ($remainder > 0) {
                if ($remainder < 10) {
                    $result .= ' lẻ ' . $units[$remainder];
                } else {
                    $result .= ' ' . $this->numberToVietnameseWords($remainder);
                }
            }
            return $result;
        }
        
        // For numbers >= 1000
        return number_format($number);
    }
}
