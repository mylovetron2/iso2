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
     * Lấy công việc theo nhân viên và ngày
     */
    public function getByNhanVienNgay(int $nhanvienStt, string $ngayLam): array
    {
        $sql = "SELECT cv.*,
                       h.maql, h.phieu, h.mavt, h.somay
                FROM {$this->table} cv
                LEFT JOIN hososcbd_iso h ON cv.hososcbd_stt = h.stt
                WHERE cv.nhanvien_stt = :nhanvien_stt AND cv.ngay_lam = :ngay_lam
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
                WHERE nhanvien_stt = :nhanvien_stt AND ngay_lam = :ngay_lam";
        
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
     * Tạo công việc mới với validation
     */
    public function createWithValidation(array $data): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => null
        ];

        // Validate required fields
        $requiredFields = ['nhanvien_stt', 'nhanvien_ten', 'ngay_lam', 'hososcbd_stt',
                          'noi_dung', 'so_gio_lam'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
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
            $data['ngay_lam'], 
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

        // Insert using BaseModel::create()
        $newId = $this->create($data);
        
        if ($newId) {
            $result['success'] = true;
            $result['message'] = 'Tạo công việc thành công';
            $result['data'] = $this->find((int)$newId);
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

        // Update using BaseModel's update method
        $updated = $this->update($stt, $data);
        
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
     * Lấy danh sách công việc theo hồ sơ SC/BĐ
     */
    public function getByHoSo(int $hososcbdStt, int $limit = 50): array
    {
        $sql = "SELECT cv.*,
                       h.maql, h.phieu, h.mavt, h.somay
                FROM {$this->table} cv
                LEFT JOIN hososcbd_iso h ON cv.hososcbd_stt = h.stt
                WHERE cv.hososcbd_stt = :hososcbd_stt
                ORDER BY cv.ngay_lam DESC, cv.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':hososcbd_stt', $hososcbdStt, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * @deprecated Dùng getByHoSo() thay thế
     */
    public function getLichSuThietBi(string $mavt, string $somay, int $limit = 10): array
    {
        $sql = "SELECT cv.*,
                       h.maql, h.phieu, h.mavt AS h_mavt, h.somay AS h_somay
                FROM {$this->table} cv
                LEFT JOIN hososcbd_iso h ON cv.hososcbd_stt = h.stt
                WHERE h.mavt = :mavt AND h.somay = :somay
                ORDER BY cv.ngay_lam DESC, cv.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':mavt', $mavt, PDO::PARAM_STR);
        $stmt->bindValue(':somay', $somay, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
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
     * Thống kê KPI theo hồ sơ
     */
    public function getKPIHoSo(int $hososcbdStt): array
    {
        $sql = "SELECT cv.*, h.mavt, h.somay, h.maql, h.phieu
                FROM {$this->table} cv
                INNER JOIN hososcbd_iso h ON cv.hososcbd_stt = h.stt
                WHERE cv.hososcbd_stt = :hososcbd_stt";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':hososcbd_stt' => $hososcbdStt]);
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
                    COUNT(DISTINCT hososcbd_stt) as so_hoso_sua
                FROM {$this->table}
                WHERE ngay_lam BETWEEN :from AND :to
                GROUP BY nhanvien_stt, nhanvien_ten
                ORDER BY tong_gio DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':from' => $from, ':to' => $to]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy tất cả công việc của nhân viên trong tháng
     */
    public function getByNhanVienThang(int $nhanvienStt, int $thang, int $nam): array
    {
        $from = sprintf('%04d-%02d-01', $nam, $thang);
        $to   = date('Y-m-t', mktime(0, 0, 0, $thang, 1, $nam));

        $sql = "SELECT cv.*,
                       h.maql, h.phieu, h.mavt, h.somay
                FROM {$this->table} cv
                LEFT JOIN hososcbd_iso h ON cv.hososcbd_stt = h.stt
                WHERE cv.nhanvien_stt = :nhanvien_stt
                  AND cv.ngay_lam BETWEEN :from AND :to
                ORDER BY cv.ngay_lam ASC, cv.created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nhanvien_stt' => $nhanvienStt,
            ':from' => $from,
            ':to'   => $to,
        ]);
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
