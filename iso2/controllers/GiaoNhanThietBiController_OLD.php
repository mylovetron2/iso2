<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class GiaoNhanThietBiController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    /**
     * Hiển thị danh sách giao nhận
     */
    public function index(): void
    {
        try {
            // Filters
            $search = $_GET['search'] ?? '';
            $loai = $_GET['loai'] ?? ''; // 'giao_di_kd' or 'nhan_ve_kd'
            $trangthai = $_GET['trangthai'] ?? '';
            $donvi = $_GET['donvi'] ?? '';
            $tu_ngay = $_GET['tu_ngay'] ?? '';
            $den_ngay = $_GET['den_ngay'] ?? '';
            
            // Build query - Lấy thông tin phiếu với số lượng thiết bị
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
            
            if ($loai) {
                $sql .= " AND gn.loai_giao_nhan = :loai";
                $params[':loai'] = $loai;
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
            
            $sql .= " GROUP BY gn.id ORDER BY gn.ngay_giao DESC, gn.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get donvi list for filter
            $stmtDonVi = $this->db->query("SELECT madv as ma_don_vi, tendv as ten_don_vi FROM donvi_iso ORDER BY tendv");
            $donviList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);
            
            $total = count($records);
            
            require_once __DIR__ . '/../views/giaonhanthietbi/index.php';
            
        } catch (Exception $e) {
            error_log("Error in GiaoNhanThietBiController::index: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra khi tải danh sách: ' . $e->getMessage();
            
            // Set default values to prevent undefined variable errors in view
            $records = [];
            $total = 0;
            
            // Try to get donvi list for filter at least
            try {
                $stmtDonVi = $this->db->query("SELECT madv as ma_don_vi, tendv as ten_don_vi FROM donvi_iso ORDER BY tendv");
                $donviList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e2) {
                $donviList = [];
            }
            
            require_once __DIR__ . '/../views/giaonhanthietbi/index.php';
        }
    }

    /**
     * Hiển thị form giao thiết bị đi kiểm định (Đội -> Mình)
     */
    public function createGiaoDi(): void
    {
        try {
            // Get thiết bị list
            $stmtTB = $this->db->query("SELECT stt as id, tenvt as ten_thiet_bi, somay as ky_ma_hieu FROM thietbi_iso ORDER BY tenvt");
            $thietbiList = $stmtTB->fetchAll(PDO::FETCH_ASSOC);
            
            // Get donvi list
            $stmtDonVi = $this->db->query("SELECT madv as ma_don_vi, tendv as ten_don_vi FROM donvi_iso ORDER BY tendv");
            $donviList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);
            
            require_once __DIR__ . '/../views/giaonhanthietbi/giao_di_multiple.php';
            
        } catch (Exception $e) {
            error_log("Error in createGiaoDi: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra';
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
        }
    }

    /**
     * Lưu phiếu giao thiết bị đi kiểm định
     */
    public function storeGiaoDi(): void
    {
        try {
            $this->db->beginTransaction();
            
            // Thông tin chung
            $donvi_giao = $_POST['donvi_giao'];
            $nguoi_giao = $_POST['nguoi_giao'];
            $ngay_giao = $_POST['ngay_giao'];
            $nguoi_nhan = $_POST['nguoi_nhan']; // Bên mình nhận
            $ghichu = $_POST['ghichu'] ?? '';
            
            // Danh sách thiết bị (array)
            $thietbi_ids = $_POST['thietbi_id'] ?? [];
            $tinhtrang_list = $_POST['tinhtrang'] ?? [];
            $ghichu_tb_list = $_POST['ghichu_thietbi'] ?? [];
            
            if (empty($thietbi_ids)) {
                throw new Exception('Vui lòng chọn ít nhất 1 thiết bị');
            }
            
            // Insert phiếu chính
            $sql = "INSERT INTO giao_nhan_thietbi_iso (
                        loai_giao_nhan,
                        nguoi_giao, donvi_giao, ngay_giao,
                        nguoi_nhan, donvi_nhan, ghichu,
                        trangthai, tong_thietbi, created_by
                    ) VALUES (
                        'giao_di_kd',
                        :nguoi_giao, :donvi_giao, :ngay_giao,
                        :nguoi_nhan, 'SCTBDVL', :ghichu,
                        'da_nhan', :tong_thietbi, :created_by
                    )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nguoi_giao' => $nguoi_giao,
                ':donvi_giao' => $donvi_giao,
                ':ngay_giao' => $ngay_giao,
                ':nguoi_nhan' => $nguoi_nhan,
                ':ghichu' => $ghichu,
                ':tong_thietbi' => count($thietbi_ids),
                ':created_by' => $_SESSION['user_id'] ?? null
            ]);
            
            $phieu_id = $this->db->lastInsertId();
            
            // Insert chi tiết thiết bị
            $sqlChiTiet = "INSERT INTO giao_nhan_thietbi_chitiet (
                            phieu_id, thietbi_id, ten_thietbi, ky_ma_hieu, 
                            soluong, tinhtrang, ghichu
                        ) VALUES (
                            :phieu_id, :thietbi_id, :ten_thietbi, :ky_ma_hieu,
                            1, :tinhtrang, :ghichu
                        )";
            $stmtChiTiet = $this->db->prepare($sqlChiTiet);
            
            foreach ($thietbi_ids as $index => $thietbi_id) {
                // Get thông tin thiết bị
                $stmtTB = $this->db->prepare("SELECT tenvt as ten_thiet_bi, somay as ky_ma_hieu FROM thietbi_iso WHERE stt = ?");
                $stmtTB->execute([$thietbi_id]);
                $thietbi = $stmtTB->fetch(PDO::FETCH_ASSOC);
                
                if (!$thietbi) {
                    continue; // Skip invalid device
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
            
            $_SESSION['success'] = 'Tạo phiếu giao thiết bị thành công (' . count($thietbi_ids) . ' thiết bị)';
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error in storeGiaoDi: " . $e->getMessage());
            $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
            header('Location: /iso2/giaonhanthietbi.php?action=create_giao_di');
            exit;
        }
    }

    /**
     * Hiển thị form nhận thiết bị về sau kiểm định (Mình -> Đội)
     */
    public function createNhanVe(): void
    {
        try {
            // Get thietbi list (not needed for nhan_ve, only phieu_giao_id)
            $stmtThietBi = $this->db->query("SELECT stt as id, tenvt as ten_thiet_bi, somay as ky_ma_hieu FROM thietbi_iso ORDER BY tenvt");
            $thietbiList = $stmtThietBi->fetchAll(PDO::FETCH_ASSOC);
            
            // Get donvi list
            $stmtDonVi = $this->db->query("SELECT madv as ma_don_vi, tendv as ten_don_vi FROM donvi_iso ORDER BY tendv");
            $donviList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);
            
            // Get phiếu giao đi chưa có phiếu nhận về (với số thiết bị)
            $sqlGiaoDi = "SELECT gn.*, COUNT(ct.id) as so_thietbi
                          FROM giao_nhan_thietbi_iso gn
                          LEFT JOIN giao_nhan_thietbi_chitiet ct ON gn.id = ct.phieu_id
                          WHERE gn.loai_giao_nhan = 'giao_di_kd'
                          AND gn.id NOT IN (
                              SELECT phieu_giao_id 
                              FROM giao_nhan_thietbi_iso 
                              WHERE phieu_giao_id IS NOT NULL
                          )
                          GROUP BY gn.id
                          ORDER BY gn.ngay_giao DESC";
            $stmtGiaoDi = $this->db->query($sqlGiaoDi);
            $phieuGiaoList = $stmtGiaoDi->fetchAll(PDO::FETCH_ASSOC);
            
            require_once __DIR__ . '/../views/giaonhanthietbi/nhan_ve.php';
            
        } catch (Exception $e) {
            error_log("Error in createNhanVe: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra';
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
        }
    }

    /**
     * Lưu phiếu nhận thiết bị về sau kiểm định
     */
    public function storeNhanVe(): void
    {
        try {
            $this->db->beginTransaction();
            
            $phieu_giao_id = !empty($_POST['phieu_giao_id']) ? (int)$_POST['phieu_giao_id'] : null;
            $donvi_nhan = $_POST['donvi_nhan'];
            $nguoi_giao = $_POST['nguoi_giao']; // Bên mình giao
            $nguoi_nhan = $_POST['nguoi_nhan'];
            $ngay_giao = $_POST['ngay_giao'];
            $noidung_kiemdinh = $_POST['noidung_kiemdinh'] ?? '';
            $ghichu = $_POST['ghichu'] ?? '';
            
            if (!$phieu_giao_id) {
                throw new Exception('Vui lòng chọn phiếu giao đi');
            }
            
            // Get thông tin phiếu giao đi
            $stmtPhieuGiao = $this->db->prepare("SELECT * FROM giao_nhan_thietbi_iso WHERE id = ?");
            $stmtPhieuGiao->execute([$phieu_giao_id]);
            $phieuGiao = $stmtPhieuGiao->fetch(PDO::FETCH_ASSOC);
            
            if (!$phieuGiao) {
                throw new Exception('Không tìm thấy phiếu giao đi');
            }
            
            // Get danh sách thiết bị từ phiếu giao đi
            $stmtChiTietGiao = $this->db->prepare("SELECT * FROM giao_nhan_thietbi_chitiet WHERE phieu_id = ?");
            $stmtChiTietGiao->execute([$phieu_giao_id]);
            $thietbiListGiao = $stmtChiTietGiao->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($thietbiListGiao)) {
                throw new Exception('Phiếu giao đi không có thiết bị');
            }
            
            // Insert phiếu nhận về
            $sql = "INSERT INTO giao_nhan_thietbi_iso (
                        loai_giao_nhan,
                        nguoi_giao, donvi_giao, ngay_giao,
                        nguoi_nhan, donvi_nhan,
                        noidung_kiemdinh, ghichu,
                        phieu_giao_id, trangthai, tong_thietbi, created_by
                    ) VALUES (
                        'nhan_ve_kd',
                        :nguoi_giao, 'SCTBDVL', :ngay_giao,
                        :nguoi_nhan, :donvi_nhan,
                        :noidung_kiemdinh, :ghichu,
                        :phieu_giao_id, 'hoan_thanh', :tong_thietbi, :created_by
                    )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nguoi_giao' => $nguoi_giao,
                ':ngay_giao' => $ngay_giao,
                ':nguoi_nhan' => $nguoi_nhan,
                ':donvi_nhan' => $donvi_nhan,
                ':noidung_kiemdinh' => $noidung_kiemdinh,
                ':ghichu' => $ghichu,
                ':phieu_giao_id' => $phieu_giao_id,
                ':tong_thietbi' => count($thietbiListGiao),
                ':created_by' => $_SESSION['user_id'] ?? null
            ]);
            
            $phieu_nhan_id = $this->db->lastInsertId();
            
            // Copy danh sách thiết bị từ phiếu giao sang phiếu nhận
            $sqlChiTiet = "INSERT INTO giao_nhan_thietbi_chitiet (
                            phieu_id, thietbi_id, ten_thietbi, ky_ma_hieu,
                            soluong, tinhtrang, ghichu
                        ) VALUES (
                            :phieu_id, :thietbi_id, :ten_thietbi, :ky_ma_hieu,
                            :soluong, :tinhtrang, :ghichu
                        )";
            $stmtChiTiet = $this->db->prepare($sqlChiTiet);
            
            foreach ($thietbiListGiao as $tb) {
                $stmtChiTiet->execute([
                    ':phieu_id' => $phieu_nhan_id,
                    ':thietbi_id' => $tb['thietbi_id'],
                    ':ten_thietbi' => $tb['ten_thietbi'],
                    ':ky_ma_hieu' => $tb['ky_ma_hieu'],
                    ':soluong' => $tb['soluong'],
                    ':tinhtrang' => $tb['tinhtrang'], // Keep original condition
                    ':ghichu' => $tb['ghichu']
                ]);
            }
            
            $this->db->commit();
            
            $_SESSION['success'] = 'Tạo phiếu nhận thiết bị về thành công (' . count($thietbiListGiao) . ' thiết bị)';
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error in storeNhanVe: " . $e->getMessage());
            $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
            header('Location: /iso2/giaonhanthietbi.php?action=create_nhan_ve');
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
            
            // Get phiếu chính
            $sql = "SELECT 
                        gn.*,
                        dv_giao.tendv as ten_donvi_giao,
                        dv_nhan.tendv as ten_donvi_nhan
                    FROM giao_nhan_thietbi_iso gn
                    LEFT JOIN donvi_iso dv_giao ON gn.donvi_giao = dv_giao.madv
                    LEFT JOIN donvi_iso dv_nhan ON gn.donvi_nhan = dv_nhan.madv
                    WHERE gn.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                throw new Exception('Không tìm thấy phiếu');
            }
            
            // Get danh sách thiết bị
            $sqlChiTiet = "SELECT * FROM giao_nhan_thietbi_chitiet WHERE phieu_id = ? ORDER BY id";
            $stmtChiTiet = $this->db->prepare($sqlChiTiet);
            $stmtChiTiet->execute([$id]);
            $thietbiList = $stmtChiTiet->fetchAll(PDO::FETCH_ASSOC);
            
            // Nếu là phiếu nhận về, lấy thông tin phiếu giao đi
            $phieuGiaoDi = null;
            $thietbiListGiaoDi = [];
            if ($record['phieu_giao_id']) {
                $stmtGiao = $this->db->prepare("SELECT * FROM giao_nhan_thietbi_iso WHERE id = ?");
                $stmtGiao->execute([$record['phieu_giao_id']]);
                $phieuGiaoDi = $stmtGiao->fetch(PDO::FETCH_ASSOC);
                
                // Get thiết bị của phiếu giao đi
                $stmtChiTietGiao = $this->db->prepare("SELECT * FROM giao_nhan_thietbi_chitiet WHERE phieu_id = ?");
                $stmtChiTietGiao->execute([$record['phieu_giao_id']]);
                $thietbiListGiaoDi = $stmtChiTietGiao->fetchAll(PDO::FETCH_ASSOC);
            }
            
            require_once __DIR__ . '/../views/giaonhanthietbi/view.php';
            
        } catch (Exception $e) {
            error_log("Error in view: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
        }
    }

    /**
     * Xóa phiếu
     */
    public function delete(): void
    {
        try {
            $id = (int)($_POST['id'] ?? 0);
            
            if (!$id) {
                throw new Exception('ID không hợp lệ');
            }
            
            $this->db->beginTransaction();
            
            // Kiểm tra xem có phiếu nhận về liên kết không
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM giao_nhan_thietbi_iso WHERE phieu_giao_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                throw new Exception('Không thể xóa phiếu giao đi đã có phiếu nhận về');
            }
            
            // Xóa chi tiết thiết bị trước
            $stmtChiTiet = $this->db->prepare("DELETE FROM giao_nhan_thietbi_chitiet WHERE phieu_id = ?");
            $stmtChiTiet->execute([$id]);
            
            // Xóa phiếu chính
            $stmt = $this->db->prepare("DELETE FROM giao_nhan_thietbi_iso WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->db->commit();
            
            $_SESSION['success'] = 'Xóa phiếu thành công';
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error in delete: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: /iso2/giaonhanthietbi.php');
        exit;
    }
}
