<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Model: PhieuYeuCau
 * Quản lý số phiếu yêu cầu dịch vụ
 * Một phiếu gồm nhiều hồ sơ bảo dưỡng (hososcbd_iso)
 */
class PhieuYeuCau extends BaseModel
{
    public function __construct()
    {
        parent::__construct('hososcbd_iso');
        $this->primaryKey = 'phieu';
    }
    
    /**
     * Lấy danh sách phiếu (nhóm theo phieu)
     * Trả về thông tin tổng hợp của mỗi phiếu
     */
    public function getPhieuList(
        string $search = '',
        string $madv = '',
        string $nhomsc = '',
        string $trangthai = '',
        string $fromDate = '',
        string $toDate = '',
        int $offset = 0,
        int $limit = 20
    ): array {
        $where = ["1=1"];
        
        if ($search) {
            $searchEscaped = $this->db->quote("%$search%");
            $where[] = "(h.phieu LIKE $searchEscaped OR h.madv LIKE $searchEscaped OR d.tendv LIKE $searchEscaped OR h.ngyeucau LIKE $searchEscaped)";
        }
        
        if ($madv) {
            $madvEscaped = $this->db->quote($madv);
            $where[] = "h.madv = $madvEscaped";
        }
        
        if ($nhomsc) {
            $nhomscEscaped = $this->db->quote($nhomsc);
            $where[] = "h.nhomsc = $nhomscEscaped";
        }
        
        if ($fromDate) {
            $fromDateEscaped = $this->db->quote($fromDate);
            $where[] = "h.ngayyc >= $fromDateEscaped";
        }
        
        if ($toDate) {
            $toDateEscaped = $this->db->quote($toDate);
            $where[] = "h.ngayyc <= $toDateEscaped";
        }
        
        // Lọc theo trạng thái
        if ($trangthai === 'chuath') { // Chưa thực hiện
            $where[] = "(h.ngayth IS NULL OR h.ngayth = '0000-00-00')";
        } elseif ($trangthai === 'danglam') { // Đang làm
            $where[] = "h.ngayth IS NOT NULL AND h.ngayth != '0000-00-00' AND (h.ngaykt IS NULL OR h.ngaykt = '0000-00-00')";
        } elseif ($trangthai === 'hoanthanh') { // Hoàn thành
            $where[] = "h.ngaykt IS NOT NULL AND h.ngaykt != '0000-00-00'";
        } elseif ($trangthai === 'chuabg') { // Chưa bàn giao
            $where[] = "h.bg = 0 AND h.ngaykt IS NOT NULL AND h.ngaykt != '0000-00-00'";
        } elseif ($trangthai === 'dabg') { // Đã bàn giao
            $where[] = "h.bg = 1";
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT 
                    h.phieu,
                    MIN(h.ngayyc) as ngayyc,
                    h.madv,
                    d.tendv,
                    h.ngyeucau,
                    h.ngnhyeucau,
                    h.dienthoai,
                    h.nhomsc,
                    COUNT(*) as so_thietbi,
                    SUM(CASE WHEN h.ngayth IS NULL OR h.ngayth = '0000-00-00' THEN 1 ELSE 0 END) as tb_chuath,
                    SUM(CASE WHEN h.ngayth IS NOT NULL AND h.ngayth != '0000-00-00' AND (h.ngaykt IS NULL OR h.ngaykt = '0000-00-00') THEN 1 ELSE 0 END) as tb_danglam,
                    SUM(CASE WHEN h.ngaykt IS NOT NULL AND h.ngaykt != '0000-00-00' AND h.bg = 0 THEN 1 ELSE 0 END) as tb_hoanthanh,
                    SUM(CASE WHEN h.bg = 1 THEN 1 ELSE 0 END) as tb_dabg,
                    MAX(h.stt) as latest_stt
                FROM {$this->table} h
                LEFT JOIN donvi_iso d ON h.madv = d.madv
                WHERE $whereClause
                GROUP BY h.phieu, h.madv, d.tendv, h.ngyeucau, h.ngnhyeucau, h.dienthoai, h.nhomsc
                ORDER BY CAST(h.phieu AS UNSIGNED) DESC
                LIMIT $limit OFFSET $offset";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Đếm tổng số phiếu
     */
    public function countPhieuList(
        string $search = '',
        string $madv = '',
        string $nhomsc = '',
        string $trangthai = '',
        string $fromDate = '',
        string $toDate = ''
    ): int {
        $where = ["1=1"];
        
        if ($search) {
            $searchEscaped = $this->db->quote("%$search%");
            $where[] = "(h.phieu LIKE $searchEscaped OR h.madv LIKE $searchEscaped OR d.tendv LIKE $searchEscaped OR h.ngyeucau LIKE $searchEscaped)";
        }
        
        if ($madv) {
            $madvEscaped = $this->db->quote($madv);
            $where[] = "h.madv = $madvEscaped";
        }
        
        if ($nhomsc) {
            $nhomscEscaped = $this->db->quote($nhomsc);
            $where[] = "h.nhomsc = $nhomscEscaped";
        }
        
        if ($fromDate) {
            $fromDateEscaped = $this->db->quote($fromDate);
            $where[] = "h.ngayyc >= $fromDateEscaped";
        }
        
        if ($toDate) {
            $toDateEscaped = $this->db->quote($toDate);
            $where[] = "h.ngayyc <= $toDateEscaped";
        }
        
        // Lọc theo trạng thái
        if ($trangthai === 'chuath') {
            $where[] = "(h.ngayth IS NULL OR h.ngayth = '0000-00-00')";
        } elseif ($trangthai === 'danglam') {
            $where[] = "h.ngayth IS NOT NULL AND h.ngayth != '0000-00-00' AND (h.ngaykt IS NULL OR h.ngaykt = '0000-00-00')";
        } elseif ($trangthai === 'hoanthanh') {
            $where[] = "h.ngaykt IS NOT NULL AND h.ngaykt != '0000-00-00'";
        } elseif ($trangthai === 'chuabg') {
            $where[] = "h.bg = 0 AND h.ngaykt IS NOT NULL AND h.ngaykt != '0000-00-00'";
        } elseif ($trangthai === 'dabg') {
            $where[] = "h.bg = 1";
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT COUNT(DISTINCT h.phieu)
                FROM {$this->table} h
                LEFT JOIN donvi_iso d ON h.madv = d.madv
                WHERE $whereClause";
        
        $stmt = $this->query($sql);
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Lấy thông tin chi tiết phiếu gồm:
     * - Thông tin tổng hợp phiếu
     * - Danh sách thiết bị trong phiếu
     */
    public function getPhieuDetail(string $phieu): ?array
    {
        $phieuEscaped = $this->db->quote($phieu);
        
        // Lấy thông tin tổng hợp phiếu
        $sqlSummary = "SELECT 
                        h.phieu,
                        MIN(h.ngayyc) as ngayyc,
                        h.madv,
                        d.tendv,
                        h.ngyeucau,
                        h.ngnhyeucau,
                        h.dienthoai,
                        h.nhomsc,
                        h.cv,
                        h.ycthemkh,
                        COUNT(*) as so_thietbi,
                        SUM(CASE WHEN h.ngayth IS NULL OR h.ngayth = '0000-00-00' THEN 1 ELSE 0 END) as tb_chuath,
                        SUM(CASE WHEN h.ngayth IS NOT NULL AND h.ngayth != '0000-00-00' AND (h.ngaykt IS NULL OR h.ngaykt = '0000-00-00') THEN 1 ELSE 0 END) as tb_danglam,
                        SUM(CASE WHEN h.ngaykt IS NOT NULL AND h.ngaykt != '0000-00-00' AND h.bg = 0 THEN 1 ELSE 0 END) as tb_hoanthanh,
                        SUM(CASE WHEN h.bg = 1 THEN 1 ELSE 0 END) as tb_dabg
                    FROM {$this->table} h
                    LEFT JOIN donvi_iso d ON h.madv = d.madv
                    WHERE h.phieu = $phieuEscaped
                    GROUP BY h.phieu, h.madv, d.tendv, h.ngyeucau, h.ngnhyeucau, h.dienthoai, h.nhomsc, h.cv, h.ycthemkh";
        
        $stmt = $this->query($sqlSummary);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$summary) {
            return null;
        }
        
        // Lấy danh sách thiết bị
        $sqlDevices = "SELECT h.*, t.tenvt, t.stt as thietbi_stt,
                       GROUP_CONCAT(DISTINCT 
                           CONCAT(
                               IF((k.qui_1 IS NOT NULL AND k.qui_1 != '') OR k.qui_1_hoantat = 1, CONCAT('Q1:', COALESCE(k.qui_1_hoantat, 0), ','), ''),
                               IF((k.qui_2 IS NOT NULL AND k.qui_2 != '') OR k.qui_2_hoantat = 1, CONCAT('Q2:', COALESCE(k.qui_2_hoantat, 0), ','), ''),
                               IF((k.qui_3 IS NOT NULL AND k.qui_3 != '') OR k.qui_3_hoantat = 1, CONCAT('Q3:', COALESCE(k.qui_3_hoantat, 0), ','), ''),
                               IF((k.qui_4 IS NOT NULL AND k.qui_4 != '') OR k.qui_4_hoantat = 1, CONCAT('Q4:', COALESCE(k.qui_4_hoantat, 0), ','), '')
                           )
                       ) as bddk_quarters_raw
                       FROM {$this->table} h
                       LEFT JOIN thietbi_iso t ON h.mavt = t.mavt AND h.somay = t.somay
                       LEFT JOIN ke_hoach_bao_duong_dinh_ky_iso k ON t.stt = k.thietbi_id AND k.nam = YEAR(h.ngayyc)
                       WHERE h.phieu = $phieuEscaped
                       GROUP BY h.stt
                       ORDER BY h.stt ASC";
        
        $stmt = $this->query($sqlDevices);
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Xử lý quarters với trạng thái hoàn thành
        foreach ($devices as &$device) {
            if (!empty($device['bddk_quarters_raw'])) {
                $quarters = array_unique(explode(',', trim($device['bddk_quarters_raw'], ',')));
                $quarters = array_filter($quarters);
                sort($quarters);
                
                $quarterData = [];
                foreach ($quarters as $q) {
                    if (strpos($q, ':') !== false) {
                        list($quarter, $status) = explode(':', $q);
                        $quarterData[] = ['quarter' => $quarter, 'completed' => (int)$status === 1];
                    }
                }
                $device['bddk_quarters'] = $quarterData;
            } else {
                $device['bddk_quarters'] = [];
            }
        }
        unset($device);
        
        return [
            'summary' => $summary,
            'devices' => $devices
        ];
    }
    
    /**
     * Lấy thống kê tổng quan
     */
    public function getStats(string $nhomsc = ''): array
    {
        $where = $nhomsc ? "WHERE nhomsc = " . $this->db->quote($nhomsc) : "";
        
        $sql = "SELECT 
                    COUNT(DISTINCT phieu) as total_phieu,
                    COUNT(*) as total_thietbi,
                    SUM(CASE WHEN ngayth IS NULL OR ngayth = '0000-00-00' THEN 1 ELSE 0 END) as tb_chuath,
                    SUM(CASE WHEN ngayth IS NOT NULL AND ngayth != '0000-00-00' AND (ngaykt IS NULL OR ngaykt = '0000-00-00') THEN 1 ELSE 0 END) as tb_danglam,
                    SUM(CASE WHEN ngaykt IS NOT NULL AND ngaykt != '0000-00-00' AND bg = 0 THEN 1 ELSE 0 END) as tb_hoanthanh,
                    SUM(CASE WHEN bg = 1 THEN 1 ELSE 0 END) as tb_dabg
                FROM {$this->table}
                $where";
        
        $stmt = $this->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Kiểm tra phiếu có tồn tại không
     */
    public function phieuExists(string $phieu): bool
    {
        $phieuEscaped = $this->db->quote($phieu);
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE phieu = $phieuEscaped";
        $stmt = $this->query($sql);
        return (int)$stmt->fetchColumn() > 0;
    }
    
    /**
     * Cập nhật thông tin chung của phiếu
     * (Áp dụng cho tất cả thiết bị trong phiếu)
     */
    public function updatePhieuCommonInfo(string $phieu, array $data): bool
    {
        $allowedFields = ['ngayyc', 'madv', 'ngyeucau', 'ngnhyeucau', 'dienthoai', 'cv', 'ycthemkh', 'nhomsc'];
        $updates = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $value = $this->db->quote($data[$field]);
                $updates[] = "`$field` = $value";
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $phieuEscaped = $this->db->quote($phieu);
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE phieu = $phieuEscaped";
        
        try {
            $stmt = $this->query($sql);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("PhieuYeuCau update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Xóa phiếu (xóa tất cả thiết bị trong phiếu)
     * Chỉ cho phép xóa nếu tất cả thiết bị chưa thực hiện
     */
    public function deletePhieu(string $phieu): bool
    {
        $phieuEscaped = $this->db->quote($phieu);
        
        // Kiểm tra có thiết bị nào đã thực hiện chưa
        $sqlCheck = "SELECT COUNT(*) FROM {$this->table} 
                     WHERE phieu = $phieuEscaped 
                     AND (ngayth IS NOT NULL AND ngayth != '0000-00-00')";
        $stmt = $this->query($sqlCheck);
        
        if ((int)$stmt->fetchColumn() > 0) {
            return false; // Có thiết bị đã thực hiện, không cho xóa
        }
        
        // Xóa tất cả thiết bị trong phiếu
        $sql = "DELETE FROM {$this->table} WHERE phieu = $phieuEscaped";
        
        try {
            $stmt = $this->query($sql);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("PhieuYeuCau delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy số phiếu tiếp theo
     */
    public function getNextPhieuNumber(): string
    {
        $sql = "SELECT MAX(CAST(phieu AS UNSIGNED)) as max_phieu FROM {$this->table}";
        $stmt = $this->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $nextNumber = ($result['max_phieu'] ?? 0) + 1;
        
        return str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
