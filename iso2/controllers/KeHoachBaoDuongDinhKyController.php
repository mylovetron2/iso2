<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class KeHoachBaoDuongDinhKyController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    /**
     * Hiển thị danh sách kế hoạch
     */
    public function index(): void
    {
        try {
            $nam = isset($_GET['nam']) ? (int)$_GET['nam'] : (int)date('Y');
            $search = $_GET['search'] ?? '';
            $qui = isset($_GET['qui']) ? (int)$_GET['qui'] : 0;
            $trangthai = $_GET['trangthai'] ?? ''; // 'hoantat', 'chuahoantat', ''
            $sapxep = $_GET['sapxep'] ?? ''; // '', 'qui_1', 'qui_2', 'qui_3', 'qui_4'
            
            $items = $this->getAll($nam, $search, $qui, $trangthai, $sapxep);
            $total = count($items);
            
            // Lấy danh sách các năm có dữ liệu
            $years = $this->getAvailableYears();
            
            require_once __DIR__ . '/../views/kehoachbaoduongdinhky/index.php';
        } catch (Exception $e) {
            error_log("Error in KeHoachBaoDuongDinhKyController::index: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra khi tải danh sách';
            header('Location: /iso2/index.php');
            exit;
        }
    }

    /**
     * Hiển thị form import
     */
    public function import(): void
    {
        try {
            require_once __DIR__ . '/../views/kehoachbaoduongdinhky/import.php';
        } catch (Exception $e) {
            error_log("Error in KeHoachBaoDuongDinhKyController::import: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra';
            header('Location: /iso2/kehoachbaoduongdinhky.php');
            exit;
        }
    }

    /**
     * Xử lý import file Excel
     */
    public function processImport(): void
    {
        try {
            if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Không có file được upload hoặc file lỗi');
            }

            $nam = isset($_POST['nam']) ? (int)$_POST['nam'] : (int)date('Y');
            $clearExisting = isset($_POST['clear_existing']) && $_POST['clear_existing'] === '1';

            $file = $_FILES['excel_file']['tmp_name'];
            $spreadsheet = IOFactory::load($file);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Bỏ qua dòng header
            array_shift($rows);

            $this->db->beginTransaction();

            // Xóa dữ liệu cũ nếu được chọn
            if ($clearExisting) {
                $sql = "DELETE FROM ke_hoach_bao_duong_dinh_ky_iso WHERE nam = :nam";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':nam' => $nam]);
            }

            // Insert dữ liệu mới
            $sql = "INSERT INTO ke_hoach_bao_duong_dinh_ky_iso 
                    (nam, ten_thietbi, so_serial, qui_1, qui_2, qui_3, qui_4, ghi_chu, created_by)
                    VALUES 
                    (:nam, :ten_thietbi, :so_serial, :qui_1, :qui_2, :qui_3, :qui_4, :ghi_chu, :created_by)";
            
            $stmt = $this->db->prepare($sql);
            $count = 0;

            foreach ($rows as $row) {
                // Bỏ qua dòng trống
                if (empty($row[1]) && empty($row[2])) continue;

                $stmt->execute([
                    ':nam' => $nam,
                    ':ten_thietbi' => trim($row[1] ?? ''),
                    ':so_serial' => trim($row[2] ?? ''),
                    ':qui_1' => trim($row[3] ?? ''),
                    ':qui_2' => trim($row[4] ?? ''),
                    ':qui_3' => trim($row[5] ?? ''),
                    ':qui_4' => trim($row[6] ?? ''),
                    ':ghi_chu' => trim($row[7] ?? ''),
                    ':created_by' => $_SESSION['user']['username'] ?? 'system'
                ]);
                $count++;
            }

            $this->db->commit();

            $_SESSION['success'] = "Đã import thành công $count thiết bị!";
            header('Location: /iso2/kehoachbaoduongdinhky.php?nam=' . $nam);
            exit;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error in processImport: " . $e->getMessage());
            $_SESSION['error'] = 'Lỗi import: ' . $e->getMessage();
            header('Location: /iso2/kehoachbaoduongdinhky.php?action=import');
            exit;
        }
    }

    /**
     * Xóa kế hoạch theo năm
     */
    public function delete(): void
    {
        try {
            $nam = isset($_GET['nam']) ? (int)$_GET['nam'] : 0;
            
            if ($nam > 0) {
                $sql = "DELETE FROM ke_hoach_bao_duong_dinh_ky_iso WHERE nam = :nam";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':nam' => $nam]);
                
                $_SESSION['success'] = "Đã xóa kế hoạch năm $nam thành công!";
            }
            
            header('Location: /iso2/kehoachbaoduongdinhky.php');
            exit;
            
        } catch (Exception $e) {
            error_log("Error in delete: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra khi xóa';
            header('Location: /iso2/kehoachbaoduongdinhky.php');
            exit;
        }
    }

    /**
     * Cập nhật trạng thái hoàn thành bảo dưỡng
     */
    public function updateHoanTat(): void
    {
        try {
            header('Content-Type: application/json; charset=UTF-8');
            
            $input = json_decode(file_get_contents('php://input'), true);
            $id = isset($input['id']) ? (int)$input['id'] : 0;
            $qui = isset($input['qui']) ? (int)$input['qui'] : 0;
            $hoantat = isset($input['hoantat']) ? (int)$input['hoantat'] : 0;
            
            if ($id <= 0 || $qui < 1 || $qui > 4) {
                echo json_encode(['success' => false, 'message' => 'Tham số không hợp lệ']);
                exit;
            }
            
            $column = "qui_{$qui}_hoantat";
            $sql = "UPDATE ke_hoach_bao_duong_dinh_ky_iso SET $column = :hoantat WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':hoantat' => $hoantat, ':id' => $id]);
            
            echo json_encode([
                'success' => true, 
                'message' => $hoantat ? 'Đã đánh dấu hoàn thành' : 'Đã bỏ đánh dấu'
            ]);
            
        } catch (Exception $e) {
            error_log("Error in updateHoanTat: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Cập nhật trạng thái hoàn thành nhiều quý cùng lúc
     */
    public function updateMultipleHoanTat(): void
    {
        try {
            header('Content-Type: application/json; charset=UTF-8');
            
            $input = json_decode(file_get_contents('php://input'), true);
            $id = isset($input['id']) ? (int)$input['id'] : 0;
            $quarters = isset($input['quarters']) ? $input['quarters'] : [];
            
            if ($id <= 0 || !is_array($quarters)) {
                echo json_encode(['success' => false, 'message' => 'Tham số không hợp lệ']);
                exit;
            }
            
            // Cập nhật tất cả 4 quý
            $sql = "UPDATE ke_hoach_bao_duong_dinh_ky_iso 
                    SET qui_1_hoantat = :q1, 
                        qui_2_hoantat = :q2, 
                        qui_3_hoantat = :q3, 
                        qui_4_hoantat = :q4 
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':q1' => isset($quarters['1']) ? (int)$quarters['1'] : 0,
                ':q2' => isset($quarters['2']) ? (int)$quarters['2'] : 0,
                ':q3' => isset($quarters['3']) ? (int)$quarters['3'] : 0,
                ':q4' => isset($quarters['4']) ? (int)$quarters['4'] : 0,
                ':id' => $id
            ]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Đã cập nhật trạng thái hoàn thành'
            ]);
            
        } catch (Exception $e) {
            error_log("Error in updateMultipleHoanTat: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Xuất Excel
     */
    public function exportExcel(): void
    {
        try {
            $nam = isset($_GET['nam']) ? (int)$_GET['nam'] : (int)date('Y');
            $search = $_GET['search'] ?? '';
            $qui = isset($_GET['qui']) ? (int)$_GET['qui'] : 0;
            $trangthai = $_GET['trangthai'] ?? '';
            $sapxep = $_GET['sapxep'] ?? '';
            $items = $this->getAll($nam, $search, $qui, $trangthai, $sapxep);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Title
            $sheet->setCellValue('A1', 'KẾ HOẠCH BẢO DƯỠNG THIẾT BỊ ĐỊNH KỲ NĂM ' . $nam);
            $sheet->mergeCells('A1:H1');
            $titleStyle = [
                'font' => [
                    'name' => 'Times New Roman',
                    'bold' => true,
                    'size' => 14,
                    'color' => ['rgb' => '1F4E78']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ];
            $sheet->getStyle('A1:H1')->applyFromArray($titleStyle);
            $sheet->getRowDimension(1)->setRowHeight(25);

            // Note/Legend
            $sheet->setCellValue('A2', 'Ghi chú: Ô màu xanh nhạt = Theo kế hoạch;   ✓ = Đã thực hiện');
            $sheet->mergeCells('A2:H2');
            $noteStyle = [
                'font' => [
                    'italic' => true,
                    'size' => 10,
                    'color' => ['rgb' => '555555']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ];
            $sheet->getStyle('A2:H2')->applyFromArray($noteStyle);
            $sheet->getRowDimension(2)->setRowHeight(18);

            // Header
            $sheet->setCellValue('A3', 'STT');
            $sheet->setCellValue('B3', 'Tên thiết bị');
            $sheet->setCellValue('C3', 'Số S/N');
            $sheet->setCellValue('D3', 'Quí 1');
            $sheet->setCellValue('E3', 'Quí 2');
            $sheet->setCellValue('F3', 'Quí 3');
            $sheet->setCellValue('G3', 'Quí 4');
            $sheet->setCellValue('H3', 'Ghi chú');

            // Style header
            $headerStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ];
            $sheet->getStyle('A3:H3')->applyFromArray($headerStyle);

            // Data
            $row = 4;
            foreach ($items as $index => $item) {
                $sheet->setCellValue('A' . $row, $index + 1);
                $sheet->setCellValue('B' . $row, $item['ten_thietbi']);
                $sheet->setCellValue('C' . $row, $item['so_serial']);
                
                // Quý 1: nếu hoàn thành thì in ✓, nếu không và không phải TO thì in giá trị, nếu là TO thì để trống
                if (!empty($item['qui_1_hoantat'])) {
                    $sheet->setCellValue('D' . $row, '✓');
                } elseif (strtoupper(trim($item['qui_1'] ?? '')) !== 'TO') {
                    $sheet->setCellValue('D' . $row, $item['qui_1']);
                }
                
                // Quý 2
                if (!empty($item['qui_2_hoantat'])) {
                    $sheet->setCellValue('E' . $row, '✓');
                } elseif (strtoupper(trim($item['qui_2'] ?? '')) !== 'TO') {
                    $sheet->setCellValue('E' . $row, $item['qui_2']);
                }
                
                // Quý 3
                if (!empty($item['qui_3_hoantat'])) {
                    $sheet->setCellValue('F' . $row, '✓');
                } elseif (strtoupper(trim($item['qui_3'] ?? '')) !== 'TO') {
                    $sheet->setCellValue('F' . $row, $item['qui_3']);
                }
                
                // Quý 4
                if (!empty($item['qui_4_hoantat'])) {
                    $sheet->setCellValue('G' . $row, '✓');
                } elseif (strtoupper(trim($item['qui_4'] ?? '')) !== 'TO') {
                    $sheet->setCellValue('G' . $row, $item['qui_4']);
                }
                
                $sheet->setCellValue('H' . $row, $item['ghi_chu']);
                
                // Tô màu xanh cho các ô có giá trị "TO" trong database
                $quiColumns = ['D' => 'qui_1', 'E' => 'qui_2', 'F' => 'qui_3', 'G' => 'qui_4'];
                foreach ($quiColumns as $col => $field) {
                    if (strtoupper(trim($item[$field] ?? '')) === 'TO') {
                        $sheet->getStyle($col . $row)->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('C6EFCE'); // Màu xanh lá nhạt
                    }
                }
                
                $row++;
            }

            // Add borders to table
            $lastRow = $row - 1;
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ];
            $sheet->getStyle('A3:H' . $lastRow)->applyFromArray($borderStyle);

            // Auto width
            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Output
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="KeHoachBaoDuong_' . $nam . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (Exception $e) {
            error_log("Error in exportExcel: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra khi xuất Excel';
            header('Location: /iso2/kehoachbaoduongdinhky.php');
            exit;
        }
    }

    /**
     * Lấy danh sách kế hoạch
     */
    private function getAll(int $nam, string $search = '', int $qui = 0, string $trangthai = '', string $sapxep = ''): array
    {
        $sql = "SELECT * FROM ke_hoach_bao_duong_dinh_ky_iso WHERE nam = :nam";
        $params = [':nam' => $nam];

        if (!empty($search)) {
            $sql .= " AND (ten_thietbi LIKE :search1 OR so_serial LIKE :search2)";
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        // Lọc theo quý
        if ($qui > 0 && $qui <= 4) {
            $quiColumn = 'qui_' . $qui;
            $sql .= " AND $quiColumn IS NOT NULL AND $quiColumn != ''";
        }

        // Lọc theo trạng thái hoàn thành
        if (!empty($trangthai)) {
            if ($trangthai === 'hoantat') {
                // Đã hoàn thành: ít nhất 1 quý đã hoàn thành
                $sql .= " AND (qui_1_hoantat = 1 OR qui_2_hoantat = 1 OR qui_3_hoantat = 1 OR qui_4_hoantat = 1)";
            } elseif ($trangthai === 'chuahoantat') {
                // Chưa hoàn thành: tất cả quý chưa hoàn thành
                $sql .= " AND (qui_1_hoantat = 0 OR qui_1_hoantat IS NULL)";
                $sql .= " AND (qui_2_hoantat = 0 OR qui_2_hoantat IS NULL)";
                $sql .= " AND (qui_3_hoantat = 0 OR qui_3_hoantat IS NULL)";
                $sql .= " AND (qui_4_hoantat = 0 OR qui_4_hoantat IS NULL)";
            } elseif ($trangthai === 'qui_hoantat' && $qui > 0 && $qui <= 4) {
                // Quý cụ thể đã hoàn thành
                $sql .= " AND qui_{$qui}_hoantat = 1";
            }
        }

        // Sắp xếp theo quý tăng dần: ưu tiên thiết bị có quý sớm nhất, đã hoàn thành lên trên
        if (!empty($sapxep) && $sapxep === 'qui_tangdan') {
            // Tìm quý đầu tiên có dữ liệu và trạng thái hoàn thành của quý đó
            $sql .= " ORDER BY 
                CASE 
                    WHEN qui_1 IS NOT NULL AND qui_1 != '' THEN 1
                    WHEN qui_2 IS NOT NULL AND qui_2 != '' THEN 2
                    WHEN qui_3 IS NOT NULL AND qui_3 != '' THEN 3
                    WHEN qui_4 IS NOT NULL AND qui_4 != '' THEN 4
                    ELSE 5
                END ASC,
                CASE 
                    WHEN qui_1 IS NOT NULL AND qui_1 != '' THEN qui_1_hoantat
                    WHEN qui_2 IS NOT NULL AND qui_2 != '' THEN qui_2_hoantat
                    WHEN qui_3 IS NOT NULL AND qui_3 != '' THEN qui_3_hoantat
                    WHEN qui_4 IS NOT NULL AND qui_4 != '' THEN qui_4_hoantat
                    ELSE 0
                END DESC,
                id ASC";
        } else {
            $sql .= " ORDER BY id ASC";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách các năm có dữ liệu
     */
    private function getAvailableYears(): array
    {
        $sql = "SELECT DISTINCT nam FROM ke_hoach_bao_duong_dinh_ky_iso ORDER BY nam DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
