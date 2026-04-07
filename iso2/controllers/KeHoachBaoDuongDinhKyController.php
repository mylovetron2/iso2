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
            $nhomsc = $_GET['nhomsc'] ?? ''; // 'RDNGA', 'CNC', ''
            $trangthai = $_GET['trangthai'] ?? ''; // 'hoantat', 'chuahoantat', ''
            $sapxep = $_GET['sapxep'] ?? ''; // '', 'qui_1', 'qui_2', 'qui_3', 'qui_4'
            $thietbi_id = isset($_GET['thietbi_id']) ? (int)$_GET['thietbi_id'] : 0;
            $thietbi_id_filter = $_GET['thietbi_id_filter'] ?? ''; // '', 'null', 'notnull'
            
            $items = $this->getAll($nam, $search, $qui, $nhomsc, $trangthai, $sapxep, $thietbi_id, $thietbi_id_filter);
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
                    (thietbi_id, nam, ten_thietbi, so_serial, nhomsc, qui_1, qui_2, qui_3, qui_4, donvi_lam_chinh, donvi_lam_phu, ghi_chu, created_by)
                    VALUES 
                    (:thietbi_id, :nam, :ten_thietbi, :so_serial, :nhomsc, :qui_1, :qui_2, :qui_3, :qui_4, :donvi_lam_chinh, :donvi_lam_phu, :ghi_chu, :created_by)";
            
            $stmt = $this->db->prepare($sql);
            $count = 0;

            foreach ($rows as $row) {
                // Bỏ qua dòng trống
                if (empty($row[1]) && empty($row[2])) continue;

                $stmt->execute([
                    ':thietbi_id' => !empty($row[0]) ? (int)$row[0] : null,
                    ':nam' => $nam,
                    ':ten_thietbi' => trim($row[1] ?? ''),
                    ':so_serial' => trim($row[2] ?? ''),
                    ':nhomsc' => trim($row[3] ?? ''),
                    ':qui_1' => trim($row[4] ?? ''),
                    ':qui_2' => trim($row[5] ?? ''),
                    ':qui_3' => trim($row[6] ?? ''),
                    ':qui_4' => trim($row[7] ?? ''),
                    ':donvi_lam_chinh' => trim($row[8] ?? ''),
                    ':donvi_lam_phu' => trim($row[9] ?? ''),
                    ':ghi_chu' => trim($row[10] ?? ''),
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
     * Cập nhật ID thiết bị
     */
    public function updateThietbiId(): void
    {
        try {
            header('Content-Type: application/json; charset=UTF-8');
            
            $input = json_decode(file_get_contents('php://input'), true);
            $kehoachId = isset($input['kehoach_id']) ? (int)$input['kehoach_id'] : 0;
            $thietbiId = isset($input['thietbi_id']) ? (int)$input['thietbi_id'] : 0;
            
            if ($kehoachId <= 0 || $thietbiId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Tham số không hợp lệ']);
                exit;
            }
            
            // Kiểm tra thiết bị có tồn tại không
            $checkSql = "SELECT stt FROM thietbi_iso WHERE stt = :thietbi_id LIMIT 1";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':thietbi_id' => $thietbiId]);
            
            if (!$checkStmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'ID thiết bị không tồn tại trong hệ thống']);
                exit;
            }
            
            // Cập nhật thietbi_id
            $sql = "UPDATE ke_hoach_bao_duong_dinh_ky_iso 
                    SET thietbi_id = :thietbi_id 
                    WHERE id = :kehoach_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':thietbi_id' => $thietbiId,
                ':kehoach_id' => $kehoachId
            ]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Đã cập nhật ID thiết bị'
            ]);
            
        } catch (Exception $e) {
            error_log("Error in updateThietbiId: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Cập nhật đơn vị làm chính và đơn vị làm phụ
     */
    public function updateDonViFields(): void
    {
        try {
            header('Content-Type: application/json; charset=UTF-8');
            
            $input = json_decode(file_get_contents('php://input'), true);
            $kehoachId = isset($input['kehoach_id']) ? (int)$input['kehoach_id'] : 0;
            $donviLamChinh = isset($input['donvi_lam_chinh']) ? trim($input['donvi_lam_chinh']) : '';
            $donviLamPhu = isset($input['donvi_lam_phu']) ? trim($input['donvi_lam_phu']) : '';
            
            if ($kehoachId <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                exit;
            }
            
            // Cập nhật đơn vị
            $sql = "UPDATE ke_hoach_bao_duong_dinh_ky_iso 
                    SET donvi_lam_chinh = :donvi_lam_chinh,
                        donvi_lam_phu = :donvi_lam_phu
                    WHERE id = :kehoach_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':donvi_lam_chinh' => $donviLamChinh,
                ':donvi_lam_phu' => $donviLamPhu,
                ':kehoach_id' => $kehoachId
            ]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Đã cập nhật đơn vị'
            ]);
            
        } catch (Exception $e) {
            error_log("Error in updateDonViFields: " . $e->getMessage());
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
            $nhomsc = $_GET['nhomsc'] ?? '';
            $trangthai = $_GET['trangthai'] ?? '';
            $sapxep = $_GET['sapxep'] ?? '';
            $thietbi_id_filter = $_GET['thietbi_id_filter'] ?? '';
            $items = $this->getAll($nam, $search, $qui, $nhomsc, $trangthai, $sapxep, 0, $thietbi_id_filter);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Title
            $sheet->setCellValue('A1', 'KẾ HOẠCH BẢO DƯỠNG THIẾT BỊ ĐỊNH KỲ NĂM ' . $nam);
            $sheet->mergeCells('A1:K1');
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
            $sheet->getStyle('A1:K1')->applyFromArray($titleStyle);
            $sheet->getRowDimension(1)->setRowHeight(25);

            // Note/Legend
            $sheet->setCellValue('A2', 'Ghi chú: Ô màu xanh nhạt = Theo kế hoạch;   ✓ = Đã thực hiện');
            $sheet->mergeCells('A2:K2');
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
            $sheet->getStyle('A2:K2')->applyFromArray($noteStyle);
            $sheet->getRowDimension(2)->setRowHeight(18);

            // Header
            $sheet->setCellValue('A3', 'STT');
            $sheet->setCellValue('B3', 'Tên thiết bị');
            $sheet->setCellValue('C3', 'Số S/N');
            $sheet->setCellValue('D3', 'Nhóm SC');
            $sheet->setCellValue('E3', 'Quí 1');
            $sheet->setCellValue('F3', 'Quí 2');
            $sheet->setCellValue('G3', 'Quí 3');
            $sheet->setCellValue('H3', 'Quí 4');
            $sheet->setCellValue('I3', 'Đơn vị làm chính');
            $sheet->setCellValue('J3', 'Đơn vị làm phụ');
            $sheet->setCellValue('K3', 'Ghi chú');

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
            $sheet->getStyle('A3:K3')->applyFromArray($headerStyle);

            // Data
            $row = 4;
            foreach ($items as $index => $item) {
                $sheet->setCellValue('A' . $row, !empty($item['thietbi_id']) ? $item['thietbi_id'] : ($index + 1));
                $sheet->setCellValue('B' . $row, $item['ten_thietbi']);
                $sheet->setCellValue('C' . $row, $item['so_serial']);
                $sheet->setCellValue('D' . $row, $item['nhomsc'] ?? '');
                
                // Quý 1: nếu hoàn thành thì in ✓, nếu không và không phải TO thì in giá trị, nếu là TO thì để trống
                if (!empty($item['qui_1_hoantat'])) {
                    $sheet->setCellValue('E' . $row, '✓');
                } elseif (strtoupper(trim($item['qui_1'] ?? '')) !== 'TO') {
                    $sheet->setCellValue('E' . $row, $item['qui_1']);
                }
                
                // Quý 2
                if (!empty($item['qui_2_hoantat'])) {
                    $sheet->setCellValue('F' . $row, '✓');
                } elseif (strtoupper(trim($item['qui_2'] ?? '')) !== 'TO') {
                    $sheet->setCellValue('F' . $row, $item['qui_2']);
                }
                
                // Quý 3
                if (!empty($item['qui_3_hoantat'])) {
                    $sheet->setCellValue('G' . $row, '✓');
                } elseif (strtoupper(trim($item['qui_3'] ?? '')) !== 'TO') {
                    $sheet->setCellValue('G' . $row, $item['qui_3']);
                }
                
                // Quý 4
                if (!empty($item['qui_4_hoantat'])) {
                    $sheet->setCellValue('H' . $row, '✓');
                } elseif (strtoupper(trim($item['qui_4'] ?? '')) !== 'TO') {
                    $sheet->setCellValue('H' . $row, $item['qui_4']);
                }
                
                $sheet->setCellValue('I' . $row, $item['donvi_lam_chinh'] ?? '');
                $sheet->setCellValue('J' . $row, $item['donvi_lam_phu'] ?? '');
                $sheet->setCellValue('K' . $row, $item['ghi_chu']);
                
                // Tô màu xanh cho các ô có giá trị "TO" trong database
                $quiColumns = ['E' => 'qui_1', 'F' => 'qui_2', 'G' => 'qui_3', 'H' => 'qui_4'];
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
            $sheet->getStyle('A3:K' . $lastRow)->applyFromArray($borderStyle);

            // Auto width
            foreach (range('A', 'K') as $col) {
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
    private function getAll(int $nam, string $search = '', int $qui = 0, string $nhomsc = '', string $trangthai = '', string $sapxep = '', int $thietbi_id = 0, string $thietbi_id_filter = ''): array
    {
        $sql = "SELECT * FROM ke_hoach_bao_duong_dinh_ky_iso WHERE nam = :nam";
        $params = [':nam' => $nam];

        // Lọc theo thiết bị ID cụ thể
        if ($thietbi_id > 0) {
            $sql .= " AND thietbi_id = :thietbi_id";
            $params[':thietbi_id'] = $thietbi_id;
        }
        
        // Lọc theo trạng thái thiết bị ID (null/not null)
        if ($thietbi_id_filter === 'null') {
            $sql .= " AND (thietbi_id IS NULL OR thietbi_id = 0)";
        } elseif ($thietbi_id_filter === 'notnull') {
            $sql .= " AND thietbi_id IS NOT NULL AND thietbi_id > 0";
        }

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

        // Lọc theo nhóm sửa chữa
        if (!empty($nhomsc)) {
            if ($nhomsc === 'CNC+RDNGA') {
                $sql .= " AND nhomsc IN ('CNC', 'RDNGA')";
            } elseif (in_array($nhomsc, ['RDNGA', 'CNC', 'KTKT'])) {
                $sql .= " AND nhomsc = :nhomsc";
                $params[':nhomsc'] = $nhomsc;
            }
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

    /**
     * Thống kê bảo dưỡng định kỳ
     */
    public function thongke(): void
    {
        try {
            $nam = isset($_GET['nam']) ? (int)$_GET['nam'] : (int)date('Y');
            $search = $_GET['search'] ?? '';
            $nhomsc = $_GET['nhomsc'] ?? '';
            $qui = $_GET['qui'] ?? '';
            
            $availableYears = $this->getAvailableYears();
            
            if (empty($availableYears)) {
                $availableYears = [(int)date('Y')];
            }
            
            $statistics = $this->getStatistics($nam, $search, $nhomsc, $qui);
            
            require_once __DIR__ . '/../views/kehoachbaoduongdinhky/thongke.php';
        } catch (Exception $e) {
            error_log("Error in KeHoachBaoDuongDinhKyController::thongke: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra khi tải thống kê';
            header('Location: /iso2/kehoachbaoduongdinhky.php');
            exit;
        }
    }

    /**
     * Lấy thống kê bảo dưỡng theo năm
     */
    private function getStatistics(int $nam, string $search = '', string $nhomsc = '', string $qui = ''): array
    {
        // Lấy tất cả kế hoạch theo năm với bộ lọc
        $plans = $this->getAll($nam, $search, 0, $nhomsc, '', '', 0, '');
        
        // Logic khác nhau khi có chọn quý cụ thể
        if (!empty($qui) && in_array($qui, ['1', '2', '3', '4'])) {
            // LOGIC MỚI: Thống kê theo quý được chọn
            $quarterField = 'qui_' . $qui;
            
            // Lọc các thiết bị có kế hoạch ở quý được chọn
            $plans = array_filter($plans, function($plan) use ($quarterField) {
                return !empty($plan[$quarterField]) && trim($plan[$quarterField]) !== '';
            });
            
            $statistics = [
                'da_hoan_thanh' => [],      // Hoàn thành đúng quý
                'truoc_han' => [],          // Hoàn thành trước hạn
                'sau_han' => [],            // Hoàn thành sau hạn
                'chua_hoan_thanh' => []     // Chưa hoàn thành
            ];
            
            $quiInt = (int)$qui;
            $completedCount = 0;
            
            // Đếm riêng cho tính % (chỉ CNM và RDNGA)
            $totalForPercent = 0;
            $completedForPercent = 0;
            
            foreach ($plans as $plan) {
                $completedField = 'qui_' . $qui . '_hoantat';
                $hasCompletedBefore = false;
                $hasCompletedAfter = false;
                
                // Kiểm tra hoàn thành trước hạn (quý < quý được chọn)
                for ($q = 1; $q < $quiInt; $q++) {
                    if (!empty($plan['qui_' . $q . '_hoantat'])) {
                        $hasCompletedBefore = true;
                        break;
                    }
                }
                
                // Kiểm tra hoàn thành sau hạn (quý > quý được chọn)
                for ($q = $quiInt + 1; $q <= 4; $q++) {
                    if (!empty($plan['qui_' . $q . '_hoantat'])) {
                        $hasCompletedAfter = true;
                        break;
                    }
                }
                
                // Kiểm tra đơn vị làm chính cho tính %
                $donviLamChinh = $plan['donvi_lam_chinh'] ?? '';
                $isValidForPercent = in_array($donviLamChinh, ['CNM', 'RDNGA']);
                
                // Phân loại
                $isCompleted = false;
                if (!empty($plan[$completedField])) {
                    // Hoàn thành đúng quý
                    $statistics['da_hoan_thanh'][] = $plan;
                    $completedCount++;
                    $isCompleted = true;
                } elseif ($hasCompletedBefore) {
                    // Hoàn thành trước hạn
                    $statistics['truoc_han'][] = $plan;
                    $completedCount++;
                    $isCompleted = true;
                } elseif ($hasCompletedAfter) {
                    // Hoàn thành sau hạn
                    $statistics['sau_han'][] = $plan;
                    $completedCount++;
                    $isCompleted = true;
                } else {
                    // Chưa hoàn thành
                    $statistics['chua_hoan_thanh'][] = $plan;
                }
                
                // Đếm cho tính % (chỉ CNM và RDNGA)
                if ($isValidForPercent) {
                    $totalForPercent++;
                    if ($isCompleted) {
                        $completedForPercent++;
                    }
                }
            }
            
            $total = count($plans);
            $summary = [
                'total_plans' => $total,
                'da_hoan_thanh' => count($statistics['da_hoan_thanh']),
                'truoc_han' => count($statistics['truoc_han']),
                'sau_han' => count($statistics['sau_han']),
                'chua_hoan_thanh' => count($statistics['chua_hoan_thanh']),
                'tyle_hoan_thanh' => $totalForPercent > 0 
                    ? round(($completedForPercent / $totalForPercent) * 100, 2)
                    : 0,
                'selected_qui' => $qui
            ];
            
        } else {
            // LOGIC CŨ: Thống kê tổng quát theo tất cả quý
            $statistics = [
                'da_hoan_thanh' => [],
                'chua_hoan_thanh' => [],
                'hoan_thanh_mot_phan' => []
            ];
            
            $totalQuarters = 0;
            $completedQuarters = 0;
            
            // Đếm riêng cho tính % (chỉ CNM và RDNGA)
            $totalQuartersForPercent = 0;
            $completedQuartersForPercent = 0;
            
            foreach ($plans as $plan) {
                $quarters = ['qui_1', 'qui_2', 'qui_3', 'qui_4'];
                $completedCount = 0;
                $plannedCount = 0;
                
                // Kiểm tra đơn vị làm chính cho tính %
                $donviLamChinh = $plan['donvi_lam_chinh'] ?? '';
                $isValidForPercent = in_array($donviLamChinh, ['CNM', 'RDNGA']);
                
                foreach ($quarters as $quarter) {
                    // Đếm các quý có kế hoạch (khác rỗng và khác null)
                    if (!empty($plan[$quarter]) && trim($plan[$quarter]) !== '') {
                        $plannedCount++;
                        $totalQuarters++;
                        
                        // Đếm cho tính % (chỉ CNM và RDNGA)
                        if ($isValidForPercent) {
                            $totalQuartersForPercent++;
                        }
                        
                        // Kiểm tra đã hoàn thành chưa
                        $completedField = $quarter . '_hoantat';
                        if (!empty($plan[$completedField])) {
                            $completedCount++;
                            $completedQuarters++;
                            
                            // Đếm cho tính % (chỉ CNM và RDNGA)
                            if ($isValidForPercent) {
                                $completedQuartersForPercent++;
                            }
                        }
                    }
                }
                
                // Phân loại
                if ($plannedCount === 0) {
                    // Không có kế hoạch nào -> bỏ qua
                    continue;
                } elseif ($completedCount === $plannedCount) {
                    // Hoàn thành tất cả
                    $statistics['da_hoan_thanh'][] = $plan;
                } elseif ($completedCount === 0) {
                    // Chưa hoàn thành gì
                    $statistics['chua_hoan_thanh'][] = $plan;
                } else {
                    // Hoàn thành một phần
                    $statistics['hoan_thanh_mot_phan'][] = $plan;
                }
            }
            
            $summary = [
                'total_plans' => count($plans),
                'da_hoan_thanh' => count($statistics['da_hoan_thanh']),
                'chua_hoan_thanh' => count($statistics['chua_hoan_thanh']),
                'hoan_thanh_mot_phan' => count($statistics['hoan_thanh_mot_phan']),
                'total_quarters' => $totalQuarters,
                'completed_quarters' => $completedQuarters,
                'tyle_hoan_thanh' => $totalQuartersForPercent > 0 
                    ? round(($completedQuartersForPercent / $totalQuartersForPercent) * 100, 2)
                    : 0,
                'selected_qui' => ''
            ];
        }
        
        return [
            'summary' => $summary,
            'details' => $statistics,
            'nam' => $nam
        ];
    }

    /**
     * Export statistics to PDF document
     */
    public function exportPdf(): void
    {
        $nam = isset($_GET['nam']) ? (int)$_GET['nam'] : (int)date('Y');
        $search = $_GET['search'] ?? '';
        $nhomsc = $_GET['nhomsc'] ?? '';
        $qui = $_GET['qui'] ?? '';
        
        $statistics = $this->getStatistics($nam, $search, $nhomsc, $qui);
        
        // Use TCPDF library
        require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';
        
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('ISO System');
        $pdf->SetAuthor('ISO System');
        $pdf->SetTitle('Báo cáo Thống kê Bảo dưỡng Định kỳ ' . $nam);
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('dejavusans', '', 10);
        
        // Output header and summary HTML
        ob_start();
        ?>
        <div style="text-align: center; margin-bottom: 20px;">
            <h2 style="font-size: 16pt; font-weight: bold; color: #1e40af; margin: 0;">BÁO CÁO THỐNG KÊ BẢO DƯỠNG ĐỊNH KỲ</h2>
            <p style="font-size: 12pt; font-style: italic; margin: 10px 0;">Năm <?php echo $nam; ?></p>
            <?php if (!empty($qui)): ?>
                <p style="font-size: 11pt; font-weight: bold; color: #2563eb;">Quý <?php echo $qui; ?></p>
            <?php endif; ?>
        </div>

        <div style="border: 2px solid #2563eb; padding: 10px; margin: 15px 0; background-color: #eff6ff;">
            <h3 style="margin-top: 0; color: #1e40af; font-size: 13pt;">TỔNG QUAN</h3>
            <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                <span style="font-weight: bold;">Tổng số kế hoạch:</span>
                <span style="font-weight: bold; color: #1e40af;"><?php echo $statistics['summary']['total_plans']; ?> thiết bị</span>
            </div>
            
            <?php if (!empty($statistics['summary']['selected_qui'])): ?>
                <!-- Khi chọn quý -->
                <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                    <span style="font-weight: bold;">Hoàn thành đúng hạn:</span>
                    <span style="font-weight: bold; color: #16a34a;"><?php echo $statistics['summary']['da_hoan_thanh']; ?> thiết bị</span>
                </div>
                <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                    <span style="font-weight: bold;">Hoàn thành trước hạn:</span>
                    <span style="font-weight: bold; color: #0d9488;"><?php echo $statistics['summary']['truoc_han']; ?> thiết bị</span>
                </div>
                <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                    <span style="font-weight: bold;">Hoàn thành sau hạn:</span>
                    <span style="font-weight: bold; color: #0891b2;"><?php echo $statistics['summary']['sau_han']; ?> thiết bị</span>
                </div>
                <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                    <span style="font-weight: bold;">Chưa hoàn thành:</span>
                    <span style="font-weight: bold; color: #dc2626;"><?php echo $statistics['summary']['chua_hoan_thanh']; ?> thiết bị</span>
                </div>
            <?php else: ?>
                <!-- Khi không chọn quý -->
                <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                    <span style="font-weight: bold;">Đã hoàn thành:</span>
                    <span style="font-weight: bold; color: #16a34a;"><?php echo $statistics['summary']['da_hoan_thanh']; ?> thiết bị</span>
                </div>
                <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                    <span style="font-weight: bold;">Chưa hoàn thành:</span>
                    <span style="font-weight: bold; color: #dc2626;"><?php echo $statistics['summary']['chua_hoan_thanh']; ?> thiết bị</span>
                </div>
                <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                    <span style="font-weight: bold;">Hoàn thành một phần:</span>
                    <span style="font-weight: bold; color: #f59e0b;"><?php echo $statistics['summary']['hoan_thanh_mot_phan'] ?? 0; ?> thiết bị</span>
                </div>
            <?php endif; ?>
            
            <div style="border-bottom: none; margin-top: 10px; background-color: white; padding: 8px;">
                <span style="font-weight: bold; font-size: 13pt;">TỶ LỆ HOÀN THÀNH:</span>
                <span style="font-size: 16pt; font-weight: bold; color: #16a34a;"><?php echo $statistics['summary']['tyle_hoan_thanh']; ?>%</span>
            </div>
        </div>
        <?php
        $html_summary = ob_get_clean();
        $pdf->writeHTML($html_summary, true, false, true, false, '');
        
        // Draw Pie Chart
        $total = $statistics['summary']['total_plans'];
        if ($total > 0) {
            $pdf->Ln(5);
            
            // Title
            $pdf->SetFont('dejavusans', 'B', 14);
            $pdf->SetTextColor(30, 64, 175);
            $pdf->Cell(0, 8, 'BIỂU ĐỒ PHÂN BỔ TRẠNG THÁI', 0, 1, 'C');
            $pdf->Ln(5);
            
            // Calculate data based on selected quarter or not
            if (!empty($qui)) {
                // Khi chọn quý: 4 trạng thái
                $data = [
                    $statistics['summary']['da_hoan_thanh'],
                    $statistics['summary']['truoc_han'],
                    $statistics['summary']['sau_han'],
                    $statistics['summary']['chua_hoan_thanh']
                ];
                $colors = [
                    [22, 163, 74],    // Green - Đúng hạn
                    [13, 148, 136],   // Teal - Trước hạn
                    [8, 145, 178],    // Cyan - Sau hạn
                    [220, 38, 38]     // Red - Chưa hoàn thành
                ];
                $labels = [
                    'Đúng hạn: ' . $data[0] . ' (' . round(($data[0]/$total)*100, 1) . '%)',
                    'Trước hạn: ' . $data[1] . ' (' . round(($data[1]/$total)*100, 1) . '%)',
                    'Sau hạn: ' . $data[2] . ' (' . round(($data[2]/$total)*100, 1) . '%)',
                    'Chưa hoàn thành: ' . $data[3] . ' (' . round(($data[3]/$total)*100, 1) . '%)'
                ];
            } else {
                // Khi không chọn quý: 3 trạng thái
                $data = [
                    $statistics['summary']['da_hoan_thanh'],
                    $statistics['summary']['chua_hoan_thanh'],
                    $statistics['summary']['hoan_thanh_mot_phan'] ?? 0
                ];
                $colors = [
                    [22, 163, 74],    // Green
                    [220, 38, 38],    // Red
                    [245, 158, 11]    // Amber
                ];
                $labels = [
                    'Đã hoàn thành: ' . $data[0] . ' (' . round(($data[0]/$total)*100, 1) . '%)',
                    'Chưa hoàn thành: ' . $data[1] . ' (' . round(($data[1]/$total)*100, 1) . '%)',
                    'Hoàn thành một phần: ' . $data[2] . ' (' . round(($data[2]/$total)*100, 1) . '%)'
                ];
            }
            
            // Draw pie chart
            $xc = 105;  // Center X
            $yc = $pdf->GetY() + 40;   // Center Y
            $r = 30;    // Radius
            
            $startAngle = 0;
            $labelPositions = [];
            
            for ($i = 0; $i < count($data); $i++) {
                if ($data[$i] <= 0) continue;
                
                $angle = ($data[$i] / $total) * 360;
                $endAngle = $startAngle + $angle;
                $percentage = round(($data[$i] / $total) * 100, 1);
                
                // Draw sector
                $pdf->SetFillColor($colors[$i][0], $colors[$i][1], $colors[$i][2]);
                $pdf->PieSector($xc, $yc, $r, $startAngle, $endAngle, 'F', false, 0, 2);
                
                // Store label info
                if ($percentage >= 5) {
                    $labelPositions[] = [
                        'start' => $startAngle,
                        'end' => $endAngle,
                        'percentage' => $percentage
                    ];
                }
                
                $startAngle = $endAngle;
            }
            
            // Draw labels
            $pdf->SetFont('dejavusans', 'B', 9);
            $pdf->SetTextColor(255, 255, 255);
            
            foreach ($labelPositions as $pos) {
                $midAngle = ($pos['start'] + $pos['end']) / 2;
                $angleRad = deg2rad(-$midAngle);
                
                $labelRadius = $r * 0.7;
                $labelX = $xc + $labelRadius * cos($angleRad);
                $labelY = $yc + $labelRadius * sin($angleRad);
                
                $text = $pos['percentage'] . '%';
                $textWidth = $pdf->GetStringWidth($text);
                $pdf->Text($labelX - ($textWidth / 2), $labelY + 1.5, $text);
            }
            
            // Draw legend
            $pdf->SetFont('dejavusans', '', 10);
            $legendY = $yc + $r + 10;
            
            foreach ($labels as $i => $label) {
                if ($data[$i] <= 0) continue;
                // Color box
                $pdf->SetFillColor($colors[$i][0], $colors[$i][1], $colors[$i][2]);
                $pdf->Rect(30, $legendY, 6, 6, 'F');
                
                // Label
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetXY(38, $legendY - 1);
                $pdf->Cell(0, 8, $label, 0, 1, 'L');
                $legendY += 10;
            }
        }
        
        // Add new page for device lists
        $pdf->AddPage();
        
        // Generate device lists HTML content
        ob_start();
        require __DIR__ . '/../views/kehoachbaoduongdinhky/export_pdf.php';
        $html = ob_get_clean();
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Close and output PDF document
        $pdf->Output('Bao_cao_bao_duong_dinh_ky_' . $nam . '.pdf', 'D');
        exit;
    }
}
