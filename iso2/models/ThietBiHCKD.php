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
        $sql = "SELECT DISTINCT bophansh FROM {$this->table} WHERE bophansh != '' ORDER BY bophansh ASC";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
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
}

