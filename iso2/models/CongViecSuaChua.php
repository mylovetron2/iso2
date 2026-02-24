<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Model: CongViecSuaChua
 * Quản lý công việc sửa chữa hàng ngày của nhân viên
 */
class CongViecSuaChua extends BaseModel
{
    protected string $primaryKey = 'stt';
    
    public function __construct()
    {
        parent::__construct('congviec_suachua_iso');
        $this->primaryKey = 'stt';
    }

    /**
     * Lấy công việc theo nhân viên và ngày (với thông tin từ hososcbd_iso)
     */
    public function getByNhanVienNgay(int $nhanvienStt, string $ngayLam): array
    {
        $sql = "SELECT cv.*, 
                       cd.ma_capdo, cd.ten_capdo,
                       hs.mavt, hs.somay, hs.hoso, hs.phieu,
                       tb.TENVT as ten_thietbi
                FROM {$this->table} cv
                LEFT JOIN capdo_baocuong_iso cd ON cv.capdo_stt = cd.stt
                LEFT JOIN hososcbd_iso hs ON cv.hososcbd_stt = hs.stt
                LEFT JOIN thietbi_iso tb ON hs.mavt = tb.MAVT AND hs.somay = tb.SOMAY
                WHERE cv.nhanvien_stt = :nhanvien_stt AND cv.ngay_lam_viec = :ngay_lam
                ORDER BY cv.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nhanvien_stt' => $nhanvienStt,
            ':ngay_lam' => $ngayLam
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Tính tổng số giờ đã làm trong ngày của nhân viên
     */
    public function getTongGioTrongNgay(int $nhanvienStt, string $ngayLam, ?int $excludeStt = null): float
    {
        $sql = "SELECT COALESCE(SUM(so_gio_lam), 0) as tong_gio
                FROM {$this->table}
                WHERE nhanvien_stt = :nhanvien_stt AND ngay_lam_viec = :ngay_lam";
        
        $params = [
            ':nhanvien_stt' => $nhanvienStt,
            ':ngay_lam' => $ngayLam
        ];
        
        if ($excludeStt !== null) {
            $sql .= " AND stt != :exclude_stt";
            $params[':exclude_stt'] = $excludeStt;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return (float)($result['tong_gio'] ?? 0);
    }

    /**
     * Kiểm tra có thể thêm giờ làm không (max 8h/ngày)
     */
    public function canAddGio(int $nhanvienStt, string $ngayLam, float $soGio, ?int $excludeStt = null): array
    {
        $tongGioHienTai = $this->getTongGioTrongNgay($nhanvienStt, $ngayLam, $excludeStt);
        $tongGioSauKhiThem = $tongGioHienTai + $soGio;
        
        return [
            'can_add' => $tongGioSauKhiThem <= 8,
            'tong_gio_hien_tai' => $tongGioHienTai,
            'tong_gio_sau_them' => $tongGioSauKhiThem,
            'gio_con_lai' => max(0, 8 - $tongGioHienTai),
            'vuot_gio' => max(0, $tongGioSauKhiThem - 8)
        ];
    }

    /**
     * Tạo công việc mới với validation (dựa trên hososcbd_stt)
     */
    public function createWithValidation(array $data): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => null
        ];

        // Validate required fields
        $requiredFields = ['nhanvien_stt', 'ngay_lam_viec', 'hososcbd_stt', 
                          'capdo_stt', 'so_gio_lam'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field]) && $data[$field] !== 0) {
                $result['message'] = "Thiếu trường bắt buộc: $field";
                return $result;
            }
        }

        // Validate số giờ > 0
        if ($data['so_gio_lam'] <= 0) {
            $result['message'] = 'Số giờ làm phải lớn hơn 0';
            return $result;
        }

        // Kiểm tra tổng giờ trong ngày
        $checkGio = $this->canAddGio(
            $data['nhanvien_stt'], 
            $data['ngay_lam_viec'], 
            $data['so_gio_lam']
        );

        if (!$checkGio['can_add']) {
            $result['message'] = sprintf(
                'Không thể thêm %.2f giờ. Tổng giờ hiện tại: %.2f/8h. Giờ còn lại: %.2f',
                $data['so_gio_lam'],
                $checkGio['tong_gio_hien_tai'],
                $checkGio['gio_con_lai']
            );
            return $result;
        }

        // Thêm thông tin người tạo
        $data['created_by'] = $_SESSION['username'] ?? 'system';

        // Insert
        $newId = $this->insert($data);
        
        if ($newId) {
            $result['success'] = true;
            $result['message'] = 'Tạo công việc thành công';
            $result['data'] = $this->find($newId);
        } else {
            $result['message'] = 'Lỗi khi tạo công việc';
        }

        return $result;
    }

    /**
     * Cập nhật công việc với validation
     */
    public function updateWithValidation(int $stt, array $data): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => null
        ];

        // Lấy thông tin công việc hiện tại
        $currentCongViec = $this->find($stt);
        if (!$currentCongViec) {
            $result['message'] = 'Không tìm thấy công việc';
            return $result;
        }

        // Nếu thay đổi số giờ, kiểm tra lại tổng giờ
        if (isset($data['so_gio_lam']) && $data['so_gio_lam'] != $currentCongViec['so_gio_lam']) {
            $checkGio = $this->canAddGio(
                $currentCongViec['nhanvien_stt'],
                $currentCongViec['ngay_lam'],
                $data['so_gio_lam'],
                $stt // Exclude bản ghi hiện tại
            );

            if (!$checkGio['can_add']) {
                $result['message'] = sprintf(
                    'Không thể cập nhật thành %.2f giờ. Giờ còn lại: %.2f',
                    $data['so_gio_lam'],
                    $checkGio['gio_con_lai']
                );
                return $result;
            }
        }

        // Update
        $updated = $this->updateWhere($data, [$this->primaryKey => $stt]);
        
        if ($updated) {
            $result['success'] = true;
            $result['message'] = 'Cập nhật công việc thành công';
            $result['data'] = $this->find($stt);
        } else {
            $result['message'] = 'Lỗi khi cập nhật công việc';
        }

        return $result;
    }

    /**
     * Lấy lịch sử sửa chữa của thiết bị
     */
    /**
     * Lấy lịch sử công việc của thiết bị (theo mavt, somay từ hososcbd_iso)
     */
    public function getLichSuThietBi(string $mavt, string $somay, int $limit = 10): array
    {
        $sql = "SELECT cv.*, 
                       cd.ma_capdo, cd.ten_capdo,
                       hs.hoso, hs.phieu,
                       nv.HOTEN as ten_nhanvien
                FROM {$this->table} cv
                LEFT JOIN capdo_baocuong_iso cd ON cv.capdo_stt = cd.stt
                LEFT JOIN hososcbd_iso hs ON cv.hososcbd_stt = hs.stt
                LEFT JOIN resume nv ON cv.nhanvien_stt = nv.stt
                WHERE hs.mavt = :mavt AND hs.somay = :somay
                ORDER BY cv.ngay_lam_viec DESC, cv.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':mavt', $mavt, PDO::PARAM_STR);
        $stmt->bindValue(':somay', $somay, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Lấy lịch sử công việc của 1 hồ sơ SCBD
     */
    public function getByHoSoScBd(int $hososcbdStt): array
    {
        $sql = "SELECT cv.*, 
                       cd.ma_capdo, cd.ten_capdo,
                       nv.HOTEN as ten_nhanvien
                FROM {$this->table} cv
                LEFT JOIN capdo_baocuong_iso cd ON cv.capdo_stt = cd.stt
                LEFT JOIN resume nv ON cv.nhanvien_stt = nv.stt
                WHERE cv.hososcbd_stt = :hososcbd_stt
                ORDER BY cv.ngay_lam_viec DESC, cv.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':hososcbd_stt' => $hososcbdStt]);
        
        return $stmt->fetchAll();
    }

    /**
     * Thống kê công việc theo nhân viên trong khoảng thời gian
     */
    public function getThongKeNhanVien(int $nhanvienStt, string $from, string $to): array
    {
        $sql = "SELECT 
                    COUNT(*) as so_cong_viec,
                    SUM(so_gio_lam) as tong_gio,
                    AVG(so_gio_lam) as gio_trung_binh,
                    MIN(ngay_lam) as ngay_dau,
                    MAX(ngay_lam) as ngay_cuoi
                FROM {$this->table}
                WHERE nhanvien_stt = :nhanvien_stt
                AND ngay_lam BETWEEN :from AND :to";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nhanvien_stt' => $nhanvienStt,
            ':from' => $from,
            ':to' => $to
        ]);
        
        return $stmt->fetch() ?: [];
    }

    /**
     * Thống kê KPI theo thiết bị
     */
    public function getKPIThietBi(string $mavt, string $somay): array
    {
        $sql = "SELECT * FROM view_kpi_thietbi_thongke 
                WHERE mavt = :mavt AND somay = :somay";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':mavt' => $mavt, ':somay' => $somay]);
        return $stmt->fetchAll();
    }

    /**
     * Báo cáo tổng quan công việc
     */
    public function getBaoCaoTongQuan(string $from, string $to): array
    {
        $sql = "SELECT 
                    nhanvien_stt,
                    nhanvien_ten,
                    COUNT(*) as so_cong_viec,
                    SUM(so_gio_lam) as tong_gio,
                    ROUND(AVG(so_gio_lam), 2) as gio_trung_binh,
                    COUNT(DISTINCT ngay_lam) as so_ngay_lam,
                    COUNT(DISTINCT CONCAT(mavt, '-', somay)) as so_thietbi_sua
                FROM {$this->table}
                WHERE ngay_lam BETWEEN :from AND :to
                GROUP BY nhanvien_stt, nhanvien_ten
                ORDER BY tong_gio DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':from' => $from, ':to' => $to]);
        return $stmt->fetchAll();
    }

    /**
     * Xóa công việc
     * Override BaseModel::delete() để sử dụng đúng primary key
     */
    public function delete(int $id): int
    {
        return parent::delete($id);
    }
}
