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
            
            // Get date range from query params
            $tungay = $_GET['tungay'] ?? date('Y-m-01'); // First day of current month
            $denngay = $_GET['denngay'] ?? date('Y-m-d'); // Today
            $search = $_GET['search'] ?? '';
            
            // Get statistics
            $items = $this->getThanhLyByDateRange($tungay, $denngay, $search);
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
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            
            require_once __DIR__ . '/../views/vattuthanhly/thongke.php';
        }
    }
    
    /**
     * Lấy danh sách vật tư thanh lý theo khoảng thời gian
     */
    private function getThanhLyByDateRange(string $tungay, string $denngay, string $search = ''): array
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
                    s.soluong as soluong_thaydoi,
                    s.soluong * v.dongia as thanhtien,
                    COALESCE(s.ngayhoanthanh, s.ngaysd_nhan, DATE(s.updated_at)) as ngay_thuchien,
                    s.ghichu as nguyennhan,
                    s.nguoisudung as nguoi_thuchien,
                    s.bophan,
                    s.trangthai,
                    '' as namsd
                FROM vattu_thanh_ly_sudung_iso s
                INNER JOIN vattu_thanh_ly_iso v ON s.vattu_stt = v.stt
                WHERE DATE(COALESCE(s.ngayhoanthanh, s.ngaysd_nhan, s.updated_at)) BETWEEN :tungay AND :denngay";
        
        $params = [
            ':tungay' => $tungay,
            ':denngay' => $denngay
        ];
        
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
            
            // Get data
            $items = $this->getThanhLyByDateRange($tungay, $denngay, $search);
            
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
            
            // Get data
            $items = $this->getThanhLyByDateRange($tungay, $denngay, $search);
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
            
            // Set headers for Word download
            header('Content-Type: application/vnd.ms-word; charset=UTF-8');
            header('Content-Disposition: attachment; filename="Thong_Ke_Vat_Tu_Thanh_Ly_' . date('Ymd') . '.doc"');
            header('Cache-Control: max-age=0');
            
            // Include the Word template
            require_once __DIR__ . '/../views/vattuthanhly/export_word.php';
            exit;
            
        } catch (Exception $e) {
            error_log("Error in exportWord: " . $e->getMessage());
            die("Error exporting to Word: " . $e->getMessage());
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
