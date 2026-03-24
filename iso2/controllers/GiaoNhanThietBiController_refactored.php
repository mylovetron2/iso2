<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/**
 * Giao Nhận Thiết Bị Controller - REFACTORED
 * 
 * WORKFLOW: 1 phiếu duy nhất với 3 trạng thái
 * ============================================
 * 1. CREATE (da_nhan): Đội gửi cho mình 3 thiết bị → Tạo phiếu
 * 2. UPDATE_GUI_KIEM_DINH (dang_kiem_dinh): Mình gửi đi kiểm định → Cập nhật thông tin gửi
 * 3. UPDATE_GIAO_LAI (da_giao): Kiểm định xong, trả lại cho đội → Cập nhật thông tin giao + kết quả
 */
class GiaoNhanThietBiController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    /**
     * Hiển thị danh sách phiếu giao nhận
     */
    public function index(): void
    {
        try {
            // Filters
            $search = $_GET['search'] ?? '';
            $trangthai = $_GET['trangthai'] ?? ''; // 'da_nhan', 'dang_kiem_dinh', 'da_giao'
            $donvi = $_GET['donvi'] ?? '';
            $tu_ngay = $_GET['tu_ngay'] ?? '';
            $den_ngay = $_GET['den_ngay'] ?? '';
            
            // Build query
            $sql = "SELECT 
                        gn.*,
                        dv_giao.tendv as ten_donvi_giao,
                        dv_nhan.tendv as ten_donvi_nhan,
                        COUNT(ct.id) as so_thietbi
                    FROM giao_nhan_thietbi_iso gn
                    LEFT JOIN donvi_iso dv_giao ON gn.donvi_giao = dv_giao.madv
                    LEFT JOIN donvi_iso dv_nhan ON gn.donvi_nhan = dv_nhan.madv
                    LEFT JOIN giao_nhan_thietbi_chitiet ct ON gn.id = ct.phieu_id
                    WHERE 1=1";
            
            $params = [];
            
            if ($search) {
                $sql .= " AND (ct.ten_thietbi LIKE :search 
                          OR ct.ky_ma_hieu LIKE :search 
                          OR gn.nguoi_giao LIKE :search 
                          OR gn.nguoi_nhan LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }
            
            if ($trangthai) {
                $sql .= " AND gn.trangthai = :trangthai";
                $params[':trangthai'] = $trangthai;
            }
            
            if ($donvi) {
                $sql .= " AND (gn.donvi_giao = :donvi OR gn.donvi_nhan = :donvi)";
                $params[':donvi'] = $donvi;
            }
            
            if ($tu_ngay) {
                $sql .= " AND gn.ngay_giao >= :tu_ngay";
                $params[':tu_ngay'] = $tu_ngay;
            }
            
            if ($den_ngay) {
                $sql .= " AND gn.ngay_giao <= :den_ngay";
                $params[':den_ngay'] = $den_ngay;
            }
            
            $sql .= " GROUP BY gn.id ORDER BY gn.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Lấy danh sách đơn vị cho filter
            $stmtDonVi = $this->db->query("SELECT madv, tendv FROM donvi_iso ORDER BY tendv");
            $donviList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);
            
            require __DIR__ . '/../views/giaonhanthietbi/index.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Có lỗi xảy ra khi tải danh sách: ' . $e->getMessage();
            require __DIR__ . '/../views/giaonhanthietbi/index.php';
        }
    }

    /**
     * BƯỚC 1: Form tạo phiếu nhận từ đội
     */
    public function create(): void
    {
        try {
            // Lấy danh sách thiết bị
            $stmt = $this->db->query("
                SELECT 
                    stt as id, 
                    tenvt as ten_thiet_bi, 
                    somay as ky_ma_hieu,
                    tinhtrang
                FROM thietbi_iso 
                WHERE tinhtrang IS NOT NULL
                ORDER BY tenvt ASC
            ");
            $thietbiList = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Lấy danh sách đơn vị
            $stmtDonVi = $this->db->query("SELECT madv, tendv FROM donvi_iso ORDER BY tendv");
            $donviList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);
            
            require __DIR__ . '/../views/giaonhanthietbi/create.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
            header('Location: giaonhanthietbi.php');
            exit;
        }
    }

    /**
     * BƯỚC 1: Lưu phiếu nhận từ đội (trạng thái: da_nhan)
     */
    public function store(): void
    {
        try {
            // Validate
            $nguoi_giao = trim($_POST['nguoi_giao'] ?? '');
            $donvi_giao = trim($_POST['donvi_giao'] ?? '');
            $ngay_giao = trim($_POST['ngay_giao'] ?? '');
            $ghichu = trim($_POST['ghichu'] ?? '');
            
            $thietbi_ids = $_POST['thietbi_id'] ?? [];
            $tinhtrang_list = $_POST['tinhtrang'] ?? [];
            $ghichu_tb_list = $_POST['ghichu_thietbi'] ?? [];
            
            if (!$nguoi_giao || !$donvi_giao || !$ngay_giao) {
                throw new Exception('Vui lòng nhập đầy đủ thông tin người giao, đơn vị và ngày giao!');
            }
            
            if (empty($thietbi_ids) || count($thietbi_ids) === 0) {
                throw new Exception('Vui lòng chọn ít nhất 1 thiết bị!');
            }
            
            $this->db->beginTransaction();
            
            // INSERT master record (phiếu nhận từ đội)
            $stmtMaster = $this->db->prepare("
                INSERT INTO giao_nhan_thietbi_iso (
                    nguoi_giao, donvi_giao, ngay_giao,
                    ghichu,
                    trangthai, tong_thietbi,
                    created_by, created_at, updated_at
                ) VALUES (
                    :nguoi_giao, :donvi_giao, :ngay_giao,
                    :ghichu,
                    'da_nhan', :tong_thietbi,
                    :created_by, NOW(), NOW()
                )
            ");
            
            $stmtMaster->execute([
                ':nguoi_giao' => $nguoi_giao,
                ':donvi_giao' => $donvi_giao,
                ':ngay_giao' => $ngay_giao,
                ':ghichu' => $ghichu,
                ':tong_thietbi' => count($thietbi_ids),
                ':created_by' => $_SESSION['userid'] ?? null
            ]);
            
            $phieu_id = (int)$this->db->lastInsertId();
            
            // INSERT detail records (danh sách thiết bị)
            $stmtChiTiet = $this->db->prepare("
                INSERT INTO giao_nhan_thietbi_chitiet (
                    phieu_id, thietbi_id, ten_thietbi, ky_ma_hieu,
                    soluong, tinhtrang, ghichu,
                    created_at, updated_at
                ) VALUES (
                    :phieu_id, :thietbi_id, :ten_thietbi, :ky_ma_hieu,
                    1, :tinhtrang, :ghichu,
                    NOW(), NOW()
                )
            ");
            
            foreach ($thietbi_ids as $index => $thietbi_id) {
                // Lấy tên và ký mã hiệu từ bảng thietbi_iso
                $stmtTB = $this->db->prepare("
                    SELECT tenvt as ten_thiet_bi, somay as ky_ma_hieu 
                    FROM thietbi_iso 
                    WHERE stt = ?
                ");
                $stmtTB->execute([$thietbi_id]);
                $thietbi = $stmtTB->fetch(PDO::FETCH_ASSOC);
                
                if (!$thietbi) {
                    throw new Exception("Không tìm thấy thiết bị ID: $thietbi_id");
                }
                
                $stmtChiTiet->execute([
                    ':phieu_id' => $phieu_id,
                    ':thietbi_id' => $thietbi_id,
                    ':ten_thietbi' => $thietbi['ten_thiet_bi'],
                    ':ky_ma_hieu' => $thietbi['ky_ma_hieu'],
                    ':tinhtrang' => $tinhtrang_list[$index] ?? '',
                    ':ghichu' => $ghichu_tb_list[$index] ?? ''
                ]);
            }
            
            $this->db->commit();
            
            $_SESSION['success'] = "Tạo phiếu nhận thành công! (" . count($thietbi_ids) . " thiết bị)";
            header('Location: giaonhanthietbi.php?action=view&id=' . $phieu_id);
            exit;
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $_SESSION['error'] = 'Lỗi khi tạo phiếu: ' . $e->getMessage();
            header('Location: giaonhanthietbi.php?action=create');
            exit;
        }
    }

    /**
     * BƯỚC 2: Form gửi đi kiểm định
     */
    public function editGuiKiemDinh(): void
    {
        try {
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                throw new Exception('ID không hợp lệ');
            }
            
            // Lấy thông tin phiếu
            $stmt = $this->db->prepare("
                SELECT gn.*, dv_giao.tendv as ten_donvi_giao
                FROM giao_nhan_thietbi_iso gn
                LEFT JOIN donvi_iso dv_giao ON gn.donvi_giao = dv_giao.madv
                WHERE gn.id = ?
            ");
            $stmt->execute([$id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                throw new Exception('Không tìm thấy phiếu');
            }
            
            // Kiểm tra trạng thái (phải là da_nhan)
            if ($record['trangthai'] !== 'da_nhan') {
                throw new Exception('Chỉ có thể gửi kiểm định phiếu ở trạng thái "Đã nhận"');
            }
            
            // Lấy danh sách thiết bị
            $stmtChiTiet = $this->db->prepare("
                SELECT * FROM giao_nhan_thietbi_chitiet 
                WHERE phieu_id = ? 
                ORDER BY id
            ");
            $stmtChiTiet->execute([$id]);
            $thietbiList = $stmtChiTiet->fetchAll(PDO::FETCH_ASSOC);
            
            require __DIR__ . '/../views/giaonhanthietbi/edit_gui_kiemdinh.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: giaonhanthietbi.php');
            exit;
        }
    }

    /**
     * BƯỚC 2: Cập nhật gửi đi kiểm định (trạng thái: dang_kiem_dinh)
     */
    public function updateGuiKiemDinh(): void
    {
        try {
            $id = (int)($_POST['id'] ?? 0);
            $nguoi_gui = trim($_POST['nguoi_gui_kiemdinh'] ?? '');
            $donvi_gui = trim($_POST['donvi_gui_kiemdinh'] ?? '');
            $ngay_gui = trim($_POST['ngay_gui_kiemdinh'] ?? '');
            
            if (!$id || !$nguoi_gui || !$donvi_gui || !$ngay_gui) {
                throw new Exception('Vui lòng nhập đầy đủ thông tin!');
            }
            
            $this->db->beginTransaction();
            
            // Kiểm tra trạng thái hiện tại
            $stmt = $this->db->prepare("SELECT trangthai FROM giao_nhan_thietbi_iso WHERE id = ?");
            $stmt->execute([$id]);
            $currentStatus = $stmt->fetchColumn();
            
            if ($currentStatus !== 'da_nhan') {
                throw new Exception('Chỉ có thể cập nhật phiếu ở trạng thái "Đã nhận"');
            }
            
            // UPDATE phiếu: thêm thông tin gửi kiểm định + đổi trạng thái
            $stmtUpdate = $this->db->prepare("
                UPDATE giao_nhan_thietbi_iso 
                SET 
                    nguoi_gui_kiemdinh = :nguoi_gui,
                    donvi_gui_kiemdinh = :donvi_gui,
                    ngay_gui_kiemdinh = :ngay_gui,
                    trangthai = 'dang_kiem_dinh',
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            $stmtUpdate->execute([
                ':nguoi_gui' => $nguoi_gui,
                ':donvi_gui' => $donvi_gui,
                ':ngay_gui' => $ngay_gui,
                ':id' => $id
            ]);
            
            $this->db->commit();
            
            $_SESSION['success'] = 'Cập nhật thông tin gửi kiểm định thành công!';
            header('Location: giaonhanthietbi.php?action=view&id=' . $id);
            exit;
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
            header('Location: giaonhanthietbi.php?action=editGuiKiemDinh&id=' . ($id ?? 0));
            exit;
        }
    }

    /**
     * BƯỚC 3: Form giao lại cho đội
     */
    public function editGiaoLai(): void
    {
        try {
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                throw new Exception('ID không hợp lệ');
            }
            
            // Lấy thông tin phiếu
            $stmt = $this->db->prepare("
                SELECT gn.*, 
                       dv_giao.tendv as ten_donvi_giao,
                       dv_nhan.tendv as ten_donvi_nhan
                FROM giao_nhan_thietbi_iso gn
                LEFT JOIN donvi_iso dv_giao ON gn.donvi_giao = dv_giao.madv
                LEFT JOIN donvi_iso dv_nhan ON gn.donvi_nhan = dv_nhan.madv
                WHERE gn.id = ?
            ");
            $stmt->execute([$id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                throw new Exception('Không tìm thấy phiếu');
            }
            
            // Kiểm tra trạng thái (phải là dang_kiem_dinh)
            if ($record['trangthai'] !== 'dang_kiem_dinh') {
                throw new Exception('Chỉ có thể giao lại phiếu ở trạng thái "Đang kiểm định"');
            }
            
            // Lấy danh sách thiết bị
            $stmtChiTiet = $this->db->prepare("
                SELECT * FROM giao_nhan_thietbi_chitiet 
                WHERE phieu_id = ? 
                ORDER BY id
            ");
            $stmtChiTiet->execute([$id]);
            $thietbiList = $stmtChiTiet->fetchAll(PDO::FETCH_ASSOC);
            
            // Lấy danh sách đơn vị
            $stmtDonVi = $this->db->query("SELECT madv, tendv FROM donvi_iso ORDER BY tendv");
            $donviList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);
            
            require __DIR__ . '/../views/giaonhanthietbi/edit_giao_lai.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: giaonhanthietbi.php');
            exit;
        }
    }

    /**
     * BƯỚC 3: Cập nhật giao lại cho đội (trạng thái: da_giao)
     */
    public function updateGiaoLai(): void
    {
        try {
            $id = (int)($_POST['id'] ?? 0);
            $nguoi_nhan = trim($_POST['nguoi_nhan'] ?? '');
            $donvi_nhan = trim($_POST['donvi_nhan'] ?? '');
            $ngay_nhan = trim($_POST['ngay_nhan'] ?? '');
            $noidung_kiemdinh = trim($_POST['noidung_kiemdinh'] ?? '');
            
            if (!$id || !$nguoi_nhan || !$donvi_nhan || !$ngay_nhan) {
                throw new Exception('Vui lòng nhập đầy đủ thông tin!');
            }
            
            $this->db->beginTransaction();
            
            // Kiểm tra trạng thái hiện tại
            $stmt = $this->db->prepare("SELECT trangthai FROM giao_nhan_thietbi_iso WHERE id = ?");
            $stmt->execute([$id]);
            $currentStatus = $stmt->fetchColumn();
            
            if ($currentStatus !== 'dang_kiem_dinh') {
                throw new Exception('Chỉ có thể giao lại phiếu ở trạng thái "Đang kiểm định"');
            }
            
            // UPDATE phiếu: thêm thông tin giao lại + kết quả kiểm định + đổi trạng thái
            $stmtUpdate = $this->db->prepare("
                UPDATE giao_nhan_thietbi_iso 
                SET 
                    nguoi_nhan = :nguoi_nhan,
                    donvi_nhan = :donvi_nhan,
                    ngay_nhan = :ngay_nhan,
                    noidung_kiemdinh = :noidung_kiemdinh,
                    trangthai = 'da_giao',
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            $stmtUpdate->execute([
                ':nguoi_nhan' => $nguoi_nhan,
                ':donvi_nhan' => $donvi_nhan,
                ':ngay_nhan' => $ngay_nhan,
                ':noidung_kiemdinh' => $noidung_kiemdinh,
                ':id' => $id
            ]);
            
            $this->db->commit();
            
            $_SESSION['success'] = 'Hoàn tất giao lại thiết bị cho đội thành công!';
            header('Location: giaonhanthietbi.php?action=view&id=' . $id);
            exit;
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
            header('Location: giaonhanthietbi.php?action=editGiaoLai&id=' . ($id ?? 0));
            exit;
        }
    }

    /**
     * Xem chi tiết phiếu
     */
    public function view(): void
    {
        try {
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                throw new Exception('ID không hợp lệ');
            }
            
            // Lấy thông tin phiếu
            $stmt = $this->db->prepare("
                SELECT gn.*, 
                       dv_giao.tendv as ten_donvi_giao,
                       dv_nhan.tendv as ten_donvi_nhan
                FROM giao_nhan_thietbi_iso gn
                LEFT JOIN donvi_iso dv_giao ON gn.donvi_giao = dv_giao.madv
                LEFT JOIN donvi_iso dv_nhan ON gn.donvi_nhan = dv_nhan.madv
                WHERE gn.id = ?
            ");
            $stmt->execute([$id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                throw new Exception('Không tìm thấy phiếu');
            }
            
            // Lấy danh sách thiết bị
            $stmtChiTiet = $this->db->prepare("
                SELECT * FROM giao_nhan_thietbi_chitiet 
                WHERE phieu_id = ? 
                ORDER BY id
            ");
            $stmtChiTiet->execute([$id]);
            $thietbiList = $stmtChiTiet->fetchAll(PDO::FETCH_ASSOC);
            
            require __DIR__ . '/../views/giaonhanthietbi/view.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: giaonhanthietbi.php');
            exit;
        }
    }

    /**
     * Xóa phiếu (cascade delete)
     */
    public function delete(): void
    {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new Exception('ID không hợp lệ');
            }
            
            $this->db->beginTransaction();
            
            // DELETE detail records first (children)
            $stmtChiTiet = $this->db->prepare("DELETE FROM giao_nhan_thietbi_chitiet WHERE phieu_id = ?");
            $stmtChiTiet->execute([$id]);
            
            // DELETE master record (parent)
            $stmt = $this->db->prepare("DELETE FROM giao_nhan_thietbi_iso WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->db->commit();
            
            $_SESSION['success'] = 'Xóa phiếu thành công!';
            header('Location: giaonhanthietbi.php');
            exit;
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $_SESSION['error'] = 'Lỗi khi xóa: ' . $e->getMessage();
            header('Location: giaonhanthietbi.php');
            exit;
        }
    }
}
