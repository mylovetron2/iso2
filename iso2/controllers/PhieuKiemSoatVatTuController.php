<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ActivityLogger.php';

class PhieuKiemSoatVatTuController
{
    private PDO $db;
    private ActivityLogger $logger;

    public function __construct()
    {
        $this->db = getDBConnection();
        $this->logger = new ActivityLogger($this->db);
    }

    /**
     * Hiển thị danh sách phiếu
     */
    public function index(): void
    {
        try {
            $search = $_GET['search'] ?? '';
            $trangthai = $_GET['trangthai'] ?? '';
            
            $items = $this->getAll($search, $trangthai);
            
            // Convert status to display text
            foreach ($items as &$item) {
                $item['trang_thai'] = $this->getStatusText($item['trangthai']);
            }
            unset($item);
            
            $total = count($items);
            
            require_once __DIR__ . '/../views/phieukiemsoatvattu/index.php';
        } catch (Exception $e) {
            error_log("Error in PhieuKiemSoatVatTuController::index: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra khi tải danh sách phiếu';
            header('Location: /iso2/index.php');
            exit;
        }
    }

    /**
     * Hiển thị form tạo mới phiếu
     */
    public function create(): void
    {
        try {
            // Get vật tư list
            $vattus = $this->getAllVatTu();
            
            require_once __DIR__ . '/../views/phieukiemsoatvattu/create.php';
        } catch (Exception $e) {
            error_log("Error in PhieuKiemSoatVatTuController::create: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra';
            header('Location: /iso2/phieukiemsoatvattu.php');
            exit;
        }
    }

    /**
     * Lưu phiếu mới
     */
    public function store(): void
    {
        try {
            $this->db->beginTransaction();
            
            // Tạo số phiếu tự động
            $soPhieu = $this->generateSoPhieu();
            
            // Insert phiếu
            $sql = "INSERT INTO phieu_kiem_soat_vattu_iso (
                        so_phieu, loai_congviec, bophan_dathang, ten_thietbi, ky_mahieu,
                        nguoi_lap_phieu, bophan_nguoilap, phieu_xuat_kho_so, ngay_xuat_kho,
                        trangthai, ghi_chu, created_by
                    ) VALUES (
                        :so_phieu, :loai_congviec, :bophan_dathang, :ten_thietbi, :ky_mahieu,
                        :nguoi_lap_phieu, :bophan_nguoilap, :phieu_xuat_kho_so, :ngay_xuat_kho,
                        'dang_thuc_hien', :ghi_chu, :created_by
                    )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':so_phieu' => $soPhieu,
                ':loai_congviec' => $_POST['loai_congviec'] ?? null,
                ':bophan_dathang' => $_POST['bophan_dathang'] ?? null,
                ':ten_thietbi' => $_POST['ten_thietbi'] ?? null,
                ':ky_mahieu' => $_POST['ky_mahieu'] ?? null,
                ':nguoi_lap_phieu' => $_POST['nguoi_lap_phieu'] ?? null,
                ':bophan_nguoilap' => $_POST['bophan_nguoilap'] ?? null,
                ':phieu_xuat_kho_so' => $_POST['phieu_xuat_kho_so'] ?? null,
                ':ngay_xuat_kho' => $_POST['ngay_xuat_kho'] ?? date('Y-m-d'),
                ':ghi_chu' => $_POST['ghi_chu'] ?? null,
                ':created_by' => $_SESSION['user']['username'] ?? 'system'
            ]);
            
            $phieuId = (int)$this->db->lastInsertId();
            
            // Log phiếu creation
            $this->logger->log(
                'phieu_kiem_soat_vattu_iso',
                'INSERT',
                $phieuId,
                null,
                [
                    'so_phieu' => $soPhieu,
                    'loai_congviec' => $_POST['loai_congviec'] ?? null,
                    'ten_thietbi' => $_POST['ten_thietbi'] ?? null,
                    'nguoi_lap_phieu' => $_POST['nguoi_lap_phieu'] ?? null,
                    'trangthai' => 'dang_thuc_hien'
                ]
            );
            
            // Insert chi tiết vật tư
            if (!empty($_POST['vattu_items'])) {
                $vattuItems = json_decode($_POST['vattu_items'], true);
                
                $sql = "INSERT INTO phieu_kiem_soat_vattu_chitiet_iso (
                            phieu_id, vattu_stt, soluong_nhan, soluong_tieuhao, ghichu
                        ) VALUES (
                            :phieu_id, :vattu_stt, :soluong_nhan, :soluong_tieuhao, :ghichu
                        )";
                
                $stmt = $this->db->prepare($sql);
                
                // SQL để insert vào bảng sử dụng
                $sqlSuDung = "INSERT INTO vattu_thanh_ly_sudung_iso (
                                vattu_stt, nguoisudung, ngaysd_nhan, soluong, bophan, 
                                mucdich_sudung, trangthai, ghichu
                              ) VALUES (
                                :vattu_stt, :nguoisudung, :ngaysd_nhan, :soluong, :bophan,
                                :mucdich_sudung, :trangthai, :ghichu
                              )";
                $stmtSuDung = $this->db->prepare($sqlSuDung);
                
                foreach ($vattuItems as $item) {
                    // Insert vào chi tiết phiếu
                    $stmt->execute([
                        ':phieu_id' => $phieuId,
                        ':vattu_stt' => $item['vattu_stt'],
                        ':soluong_nhan' => $item['soluong_nhan'] ?? 0,
                        ':soluong_tieuhao' => $item['soluong_tieuhao'] ?? 0,
                        ':ghichu' => $item['ghichu'] ?? null
                    ]);
                    
                    $chitieuId = (int)$this->db->lastInsertId();
                    
                    // Log chi tiết insert
                    $this->logger->log(
                        'phieu_kiem_soat_vattu_chitiet_iso',
                        'INSERT',
                        $chitieuId,
                        null,
                        [
                            'phieu_id' => $phieuId,
                            'vattu_stt' => $item['vattu_stt'],
                            'soluong_nhan' => $item['soluong_nhan'] ?? 0,
                            'soluong_tieuhao' => $item['soluong_tieuhao'] ?? 0
                        ]
                    );
                    
                    // Nếu có tiêu hao, tạo bản ghi chi tiết sử dụng
                    $soluongTieuHao = floatval($item['soluong_tieuhao'] ?? 0);
                    if ($soluongTieuHao > 0) {
                        $mucdichSuDung = $_POST['loai_congviec'] ?? '';
                        if (!empty($_POST['ten_thietbi'])) {
                            $mucdichSuDung .= ' - ' . $_POST['ten_thietbi'];
                        }
                        if (!empty($_POST['ky_mahieu'])) {
                            $mucdichSuDung .= ' (' . $_POST['ky_mahieu'] . ')';
                        }
                        
                        $stmtSuDung->execute([
                            ':vattu_stt' => $item['vattu_stt'],
                            ':nguoisudung' => $_POST['nguoi_lap_phieu'] ?? null,
                            ':ngaysd_nhan' => $_POST['ngay_xuat_kho'] ?? date('Y-m-d'),
                            ':soluong' => $soluongTieuHao,
                            ':bophan' => $_POST['bophan_nguoilap'] ?? null,
                            ':mucdich_sudung' => $mucdichSuDung,
                            ':trangthai' => 'dangdung',
                            ':ghichu' => 'Phiếu KSV: ' . $soPhieu . ($item['ghichu'] ? ' - ' . $item['ghichu'] : '')
                        ]);
                        
                        $sudungId = (int)$this->db->lastInsertId();
                        
                        // Log sử dụng insert
                        $this->logger->log(
                            'vattu_thanh_ly_sudung_iso',
                            'INSERT',
                            $sudungId,
                            null,
                            [
                                'vattu_stt' => $item['vattu_stt'],
                                'soluong' => $soluongTieuHao,
                                'nguoisudung' => $_POST['nguoi_lap_phieu'] ?? null,
                                'bophan' => $_POST['bophan_nguoilap'] ?? null,
                                'mucdich_sudung' => $mucdichSuDung,
                                'so_phieu' => $soPhieu
                            ]
                        );
                        
                        // Trừ số lượng còn lại
                        $sqlUpdate = "UPDATE vattu_thanh_ly_iso 
                                     SET soluong_conlai = soluong_conlai - :soluong 
                                     WHERE stt = :vattu_stt";
                        $stmtUpdate = $this->db->prepare($sqlUpdate);
                        $stmtUpdate->execute([
                            ':soluong' => $soluongTieuHao,
                            ':vattu_stt' => $item['vattu_stt']
                        ]);
                        
                        // Log số lượng update
                        $this->logger->log(
                            'vattu_thanh_ly_iso',
                            'UPDATE',
                            $item['vattu_stt'],
                            null,
                            [
                                'action' => 'deduct_quantity',
                                'soluong_tru' => $soluongTieuHao,
                                'lien_ket' => 'Phiếu KSV: ' . $soPhieu
                            ]
                        );
                    }
                }
            }
            
            $this->db->commit();
            
            $_SESSION['success'] = 'Đã tạo phiếu kiểm soát vật tư thành công!';
            header('Location: /iso2/phieukiemsoatvattu.php?action=view&id=' . $phieuId);
            exit;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error in PhieuKiemSoatVatTuController::store: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra khi tạo phiếu: ' . $e->getMessage();
            header('Location: /iso2/phieukiemsoatvattu.php?action=create');
            exit;
        }
    }

    /**
     * Xem chi tiết phiếu
     */
    public function view(): void
    {
        try {
            $id = $_GET['id'] ?? 0;
            
            $phieu = $this->getById((int)$id);
            if (!$phieu) {
                $_SESSION['error'] = 'Không tìm thấy phiếu';
                header('Location: /iso2/phieukiemsoatvattu.php');
                exit;
            }
            
            // Convert trangthai to display text
            $phieu['trang_thai'] = $this->getStatusText($phieu['trangthai']);
            
            $chitiets = $this->getChiTiet((int)$id);
            
            require_once __DIR__ . '/../views/phieukiemsoatvattu/view.php';
        } catch (Exception $e) {
            error_log("Error in PhieuKiemSoatVatTuController::view: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra';
            header('Location: /iso2/phieukiemsoatvattu.php');
            exit;
        }
    }

    /**
     * Hủy phiếu
     */
    public function cancel(): void
    {
        try {
            $this->db->beginTransaction();
            
            $id = $_GET['id'] ?? 0;
            
            // Lấy thông tin phiếu
            $phieu = $this->getById((int)$id);
            if (!$phieu) {
                throw new Exception('Không tìm thấy phiếu');
            }
            
            // Nếu phiếu đã hủy rồi thì không cần xử lý
            if ($phieu['trangthai'] === 'huy') {
                $_SESSION['warning'] = 'Phiếu đã được hủy trước đó';
                header('Location: /iso2/phieukiemsoatvattu.php?action=view&id=' . $id);
                exit;
            }
            
            // Lấy chi tiết vật tư của phiếu
            $chitiets = $this->getChiTiet((int)$id);
            
            // Xóa các bản ghi sử dụng liên quan và hoàn trả số lượng
            foreach ($chitiets as $chitiet) {
                $soluongTieuHao = floatval($chitiet['soluong_tieuhao'] ?? 0);
                if ($soluongTieuHao > 0) {
                    // Lấy thông tin bản ghi sử dụng trước khi xóa (để log)
                    $sqlGetSuDung = "SELECT * FROM vattu_thanh_ly_sudung_iso 
                                    WHERE vattu_stt = :vattu_stt 
                                    AND ghichu LIKE :so_phieu_pattern";
                    $stmtGetSuDung = $this->db->prepare($sqlGetSuDung);
                    $stmtGetSuDung->execute([
                        ':vattu_stt' => $chitiet['vattu_stt'],
                        ':so_phieu_pattern' => 'Phiếu KSV: ' . $phieu['so_phieu'] . '%'
                    ]);
                    $sudungRecord = $stmtGetSuDung->fetch(PDO::FETCH_ASSOC);
                    
                    // Tìm và xóa bản ghi sử dụng (dùng ghichu chứa số phiếu)
                    $sqlDelete = "DELETE FROM vattu_thanh_ly_sudung_iso 
                                 WHERE vattu_stt = :vattu_stt 
                                 AND ghichu LIKE :so_phieu_pattern";
                    $stmtDelete = $this->db->prepare($sqlDelete);
                    $stmtDelete->execute([
                        ':vattu_stt' => $chitiet['vattu_stt'],
                        ':so_phieu_pattern' => 'Phiếu KSV: ' . $phieu['so_phieu'] . '%'
                    ]);
                    
                    // Log xóa sử dụng
                    if ($sudungRecord) {
                        $this->logger->log(
                            'vattu_thanh_ly_sudung_iso',
                            'DELETE',
                            (int)$sudungRecord['id'],
                            $sudungRecord,
                            null
                        );
                    }
                    
                    // Hoàn trả số lượng
                    $sqlUpdate = "UPDATE vattu_thanh_ly_iso 
                                 SET soluong_conlai = soluong_conlai + :soluong 
                                 WHERE stt = :vattu_stt";
                    $stmtUpdate = $this->db->prepare($sqlUpdate);
                    $stmtUpdate->execute([
                        ':soluong' => $soluongTieuHao,
                        ':vattu_stt' => $chitiet['vattu_stt']
                    ]);
                    
                    // Log hoàn trả số lượng
                    $this->logger->log(
                        'vattu_thanh_ly_iso',
                        'UPDATE',
                        (int)$chitiet['vattu_stt'],
                        null,
                        [
                            'action' => 'restore_quantity',
                            'soluong_hoan_tra' => $soluongTieuHao,
                            'lien_ket' => 'Hủy phiếu KSV: ' . $phieu['so_phieu']
                        ]
                    );
                }
            }
            
            // Cập nhật trạng thái phiếu
            $sql = "UPDATE phieu_kiem_soat_vattu_iso SET trangthai = 'huy' WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            // Log hủy phiếu
            $this->logger->log(
                'phieu_kiem_soat_vattu_iso',
                'UPDATE',
                $id,
                ['trangthai' => $phieu['trangthai']],
                [
                    'trangthai' => 'huy',
                    'so_phieu' => $phieu['so_phieu'],
                    'action' => 'cancel'
                ]
            );
            
            $this->db->commit();
            
            $_SESSION['success'] = 'Đã hủy phiếu thành công!';
            header('Location: /iso2/phieukiemsoatvattu.php?action=view&id=' . $id);
            exit;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error in PhieuKiemSoatVatTuController::cancel: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra khi hủy phiếu: ' . $e->getMessage();
            header('Location: /iso2/phieukiemsoatvattu.php');
            exit;
        }
    }

    /**
     * Lấy danh sách tất cả phiếu
     */
    private function getAll(string $search = '', string $trangthai = ''): array
    {
        $sql = "SELECT * FROM phieu_kiem_soat_vattu_iso WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (so_phieu LIKE :search OR ten_thietbi LIKE :search OR nguoi_lap_phieu LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($trangthai)) {
            $sql .= " AND trangthai = :trangthai";
            $params[':trangthai'] = $trangthai;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy thông tin phiếu theo ID
     */
    private function getById(int $id): ?array
    {
        $sql = "SELECT * FROM phieu_kiem_soat_vattu_iso WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Lấy chi tiết vật tư của phiếu
     */
    private function getChiTiet(int $phieuId): array
    {
        $sql = "SELECT 
                    ct.*,
                    v.mavattu,
                    v.ten_tiengviet as ten_vattu,
                    v.dvt_tiengviet as donvi
                FROM phieu_kiem_soat_vattu_chitiet_iso ct
                INNER JOIN vattu_thanh_ly_iso v ON ct.vattu_stt = v.stt
                WHERE ct.phieu_id = :phieu_id
                ORDER BY ct.id ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':phieu_id' => $phieuId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách vật tư
     */
    private function getAllVatTu(string $search = ''): array
    {
        $sql = "SELECT stt, mavattu, ten_tiengviet, dvt_tiengviet as donvi 
                FROM vattu_thanh_ly_iso 
                WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (mavattu LIKE :search OR ten_tiengviet LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        $sql .= " ORDER BY mavattu ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tạo số phiếu tự động
     */
    private function generateSoPhieu(): string
    {
        $year = date('Y');
        $month = date('m');
        
        // Lấy số phiếu cuối cùng trong tháng
        $sql = "SELECT so_phieu FROM phieu_kiem_soat_vattu_iso 
                WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month 
                ORDER BY id DESC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':year' => $year, ':month' => $month]);
        $lastPhieu = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lastPhieu && preg_match('/PKSV-(\d{4})(\d{2})-(\d+)/', $lastPhieu['so_phieu'], $matches)) {
            $nextNumber = (int)$matches[3] + 1;
        } else {
            $nextNumber = 1;
        }
        
        return sprintf('PKSV-%s%s-%03d', $year, $month, $nextNumber);
    }

    /**
     * Chuyển đổi status code sang text hiển thị
     */
    private function getStatusText(string $status): string
    {
        $statusMap = [
            'dang_thuc_hien' => 'Đang thực hiện',
            'hoan_thanh' => 'Đã hoàn thành',
            'huy' => 'Đã hủy'
        ];
        
        return $statusMap[$status] ?? $status;
    }

    /**
     * Export phiếu kiểm soát vật tư ra file Word
     */
    public function exportWord(): void
    {
        try {
            $id = $_GET['id'] ?? 0;
            
            $phieu = $this->getById((int)$id);
            if (!$phieu) {
                $_SESSION['error'] = 'Không tìm thấy phiếu';
                header('Location: /iso2/phieukiemsoatvattu.php');
                exit;
            }
            
            // Convert trangthai to display text
            $phieu['trang_thai'] = $this->getStatusText($phieu['trangthai']);
            
            $chitiets = $this->getChiTiet((int)$id);
            
            // Include export template
            require_once __DIR__ . '/../views/phieukiemsoatvattu/export_word.php';
        } catch (Exception $e) {
            error_log("Error in PhieuKiemSoatVatTuController::exportWord: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra khi xuất file Word';
            header('Location: /iso2/phieukiemsoatvattu.php');
            exit;
        }
    }
}
