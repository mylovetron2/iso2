<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Model: ThietBiHCKD (Thiết Bị Hiệu Chuẩn/Kiểm Định)
 * Quản lý thiết bị cần hiệu chuẩn/kiểm định
 */
class ThietBiHCKD extends BaseModel
{
    public function __construct()
    {
        parent::__construct('thietbihckd_iso');
        $this->primaryKey = 'stt';
    }
    
    /**
     * Lấy danh sách thiết bị theo bộ phận sử dụng
     */
    public function getByBoPhanSH(string $bophansh): array
    {
        $bophanshEscaped = $this->db->quote($bophansh);
        $sql = "SELECT * FROM {$this->table} WHERE bophansh = $bophanshEscaped ORDER BY tenthietbi ASC";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy danh sách thiết bị theo chủ sở hữu
     */
    public function getByChuSoHuu(string $chusohuu): array
    {
        $chusohuuEscaped = $this->db->quote($chusohuu);
        $sql = "SELECT * FROM {$this->table} WHERE chusohuu = $chusohuuEscaped ORDER BY tenthietbi ASC";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy danh sách thiết bị theo loại
     */
    public function getByLoaiTB(string $loaitb): array
    {
        $loaitbEscaped = $this->db->quote($loaitb);
        $sql = "SELECT * FROM {$this->table} WHERE loaitb = $loaitbEscaped ORDER BY tenthietbi ASC";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Tìm thiết bị theo mã vật tư
     */
    public function findByMaVatTu(string $mavattu): array|false
    {
        $mavattuEscaped = $this->db->quote($mavattu);
        $sql = "SELECT * FROM {$this->table} WHERE mavattu = $mavattuEscaped";
        $stmt = $this->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy danh sách thiết bị sắp hết hạn kiểm định (trong vòng N ngày)
     */
    public function getSapHetHanKD(int $days = 30): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE ngayktnghiemthu IS NOT NULL 
                AND DATEDIFF(DATE_ADD(ngayktnghiemthu, INTERVAL CAST(thoihankd AS SIGNED) MONTH), CURDATE()) <= :days
                AND DATEDIFF(DATE_ADD(ngayktnghiemthu, INTERVAL CAST(thoihankd AS SIGNED) MONTH), CURDATE()) >= 0
                ORDER BY ngayktnghiemthu ASC";
        $stmt = $this->query($sql, [':days' => $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy danh sách thiết bị đã hết hạn kiểm định
     */
    public function getHetHanKD(): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE ngayktnghiemthu IS NOT NULL 
                AND DATEDIFF(DATE_ADD(ngayktnghiemthu, INTERVAL CAST(thoihankd AS SIGNED) MONTH), CURDATE()) < 0
                ORDER BY ngayktnghiemthu DESC";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy danh sách loại thiết bị
     */
    public function getAllLoaiTB(): array
    {
        $sql = "SELECT DISTINCT loaitb FROM {$this->table} WHERE loaitb != '' ORDER BY loaitb ASC";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Lấy danh sách bộ phận sử dụng
     */
    public function getAllBoPhanSH(): array
    {
        // Lấy danh sách đơn vị từ bảng donvi_iso (madv và tendv)
        $sql = "SELECT madv, tendv FROM donvi_iso WHERE madv != '' AND tendv != '' ORDER BY tendv ASC";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy thiết bị theo mã vật tư
     */
    public function getByMaVatTu(string $mavattu): ?array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE mavattu = :mavattu LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':mavattu' => $mavattu]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error in ThietBiHCKD::getByMaVatTu: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Lấy danh sách thiết bị dẫn chuẩn
     */
    public function getDanhChuan(): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE loaitb = 1 AND danchuan = 1 
                    ORDER BY tenviettat, somay";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in ThietBiHCKD::getDanhChuan: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Lấy danh sách thiết bị grouped theo tên
     */
    public function getAllGrouped(): array
    {
        try {
            $sql = "SELECT mavattu, tenthietbi, tenviettat, somay, bophansh 
                    FROM {$this->table} 
                    ORDER BY tenthietbi, somay";
            $stmt = $this->db->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group by tenthietbi
            $grouped = [];
            foreach ($data as $row) {
                $groupName = $row['tenthietbi'];
                if (!isset($grouped[$groupName])) {
                    $grouped[$groupName] = [];
                }
                $grouped[$groupName][] = $row;
            }
            
            return $grouped;
        } catch (PDOException $e) {
            error_log("Error in ThietBiHCKD::getAllGrouped: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Count records with filter based on expiry status
     */
    public function countWithFilter(string $where = '', array $params = [], string $filterType = ''): int
    {
        try {
            // Build same inner query as getAllWithLatestHC
            $innerSql = "SELECT t.*, 
                           CASE 
                               WHEN COALESCE(h.ngayhc, t.ngayktnghiemthu) IS NOT NULL AND t.thoihankd IS NOT NULL THEN
                                   DATEDIFF(DATE_ADD(COALESCE(h.ngayhc, t.ngayktnghiemthu), INTERVAL CAST(t.thoihankd AS SIGNED) MONTH), CURDATE())
                               ELSE NULL
                           END as days_to_expire
                    FROM {$this->table} t
                    LEFT JOIN (
                        SELECT tenmay, 
                               MAX(stt) as max_stt
                        FROM hosohckd_iso
                        WHERE ngayhc IS NOT NULL
                        GROUP BY tenmay
                    ) latest ON t.mavattu = latest.tenmay
                    LEFT JOIN hosohckd_iso h ON h.stt = latest.max_stt";
            
            if ($where) {
                // Replace table references in WHERE clause, but NOT parameter names (those with : prefix)
                $where = preg_replace('/(?<!:)\b(mavattu|tenviettat|tenthietbi|somay|hangsx|bophansh|loaitb)\b/', 't.$1', $where);
                $innerSql .= " $where";
            }
            
            // Wrap in COUNT query with filter
            $sql = "SELECT COUNT(*) FROM ($innerSql) subq";
            
            // Apply filter by expiry status
            if ($filterType === 'saphethan') {
                $sql .= " WHERE days_to_expire IS NOT NULL AND days_to_expire <= 30 AND days_to_expire >= 0";
            } elseif ($filterType === 'dahethan') {
                $sql .= " WHERE days_to_expire IS NOT NULL AND days_to_expire < 0";
            }
            
            $stmt = $this->query($sql, $params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error in ThietBiHCKD::countWithFilter: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Lấy danh sách thiết bị kèm ngày HC gần nhất từ hosohckd_iso
     */
    public function getAllWithLatestHC(string $where = '', array $params = [], int $limit = 0, int $offset = 0, string $filterType = ''): array
    {
        try {
            // Build inner query
            $innerSql = "SELECT t.*, 
                           dv.tendv as tendv_bophan,
                           h.ngayhc as ngayhc_latest,
                           h.ngayhctt as ngayhctt_latest,
                           h.ttkt as ttkt_latest,
                           COALESCE(h.ngayhc, t.ngayktnghiemthu) as ngayhc_calc,
                           CASE 
                               WHEN COALESCE(h.ngayhc, t.ngayktnghiemthu) IS NOT NULL AND t.thoihankd IS NOT NULL THEN
                                   DATEDIFF(DATE_ADD(COALESCE(h.ngayhc, t.ngayktnghiemthu), INTERVAL CAST(t.thoihankd AS SIGNED) MONTH), CURDATE())
                               ELSE NULL
                           END as days_to_expire
                    FROM {$this->table} t
                    LEFT JOIN donvi_iso dv ON t.bophansh = dv.madv
                    LEFT JOIN (
                        SELECT tenmay, 
                               MAX(stt) as max_stt
                        FROM hosohckd_iso
                        WHERE ngayhc IS NOT NULL
                        GROUP BY tenmay
                    ) latest ON t.mavattu = latest.tenmay
                    LEFT JOIN hosohckd_iso h ON h.stt = latest.max_stt";
            
            if ($where) {
                // Replace table references in WHERE clause, but NOT parameter names (those with : prefix)
                $where = preg_replace('/(?<!:)\b(mavattu|tenviettat|tenthietbi|somay|hangsx|bophansh|loaitb)\b/', 't.$1', $where);
                $innerSql .= " $where";
            }
            
            // Wrap in subquery and apply filter in outer WHERE clause
            $sql = "SELECT * FROM ($innerSql) subq";
            
            // Apply filter by expiry status using WHERE clause on subquery
            if ($filterType === 'saphethan') {
                $sql .= " WHERE days_to_expire IS NOT NULL AND days_to_expire <= 30 AND days_to_expire >= 0";
            } elseif ($filterType === 'dahethan') {
                $sql .= " WHERE days_to_expire IS NOT NULL AND days_to_expire < 0";
            }
            
            // Add ORDER BY after filter
            $sql .= " ORDER BY stt DESC";
            
            if ($limit > 0) {
                $sql .= " LIMIT $limit";
                if ($offset > 0) {
                    $sql .= " OFFSET $offset";
                }
            }
            
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in ThietBiHCKD::getAllWithLatestHC: " . $e->getMessage());
            return [];
        }
    }
}

