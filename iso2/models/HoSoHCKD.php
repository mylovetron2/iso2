<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class HoSoHCKD extends BaseModel {
    protected string $table = 'hosohckd_iso';
    protected string $primaryKey = 'stt';
    
    public function __construct() {
        parent::__construct('hosohckd_iso');
        $this->primaryKey = 'stt';
    }
    
    /**
     * Get all inspection records for a specific year
     */
    public function getByYear(int $namkh): array {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE namkh = :namkh ORDER BY tenmay, ngayhc";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['namkh' => $namkh]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in HoSoHCKD::getByYear: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get inspection records for a specific device in a year
     */
    public function getByDevice(string $tenmay, int $namkh): array {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE tenmay = :tenmay AND namkh = :namkh ORDER BY ngayhc";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['tenmay' => $tenmay, 'namkh' => $namkh]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in HoSoHCKD::getByDevice: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if device has inspection record
     */
    public function hasInspection(string $tenmay, int $namkh): bool {
        try {
            $sql = "SELECT COUNT(*) as cnt FROM {$this->table} 
                    WHERE tenmay = :tenmay AND namkh = :namkh AND ngayhc IS NOT NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['tenmay' => $tenmay, 'namkh' => $namkh]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['cnt'] ?? 0) > 0;
        } catch (PDOException $e) {
            error_log("Error in HoSoHCKD::hasInspection: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy hồ sơ theo thiết bị và ngày HC
     */
    public function getByDeviceAndDate(string $mavattu, string $ngayhc): ?array {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE tenmay = :tenmay AND ngayhc = :ngayhc 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['tenmay' => $mavattu, 'ngayhc' => $ngayhc]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error in HoSoHCKD::getByDeviceAndDate: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Tạo số hồ sơ tự động
     * Format: YY-TMM-XX
     */
    public function generateSoHS(int $month, int $year): string {
        try {
            $yearShort = substr((string)$year, -2);
            $monthStr = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
            $prefix = "{$yearShort}-T{$monthStr}-";
            
            // Tìm số thứ tự lớn nhất trong tháng
            $sql = "SELECT sohs FROM {$this->table} 
                    WHERE sohs LIKE :prefix 
                    ORDER BY sohs DESC 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['prefix' => $prefix . '%']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['sohs']) {
                // Lấy phần số thứ tự
                $parts = explode('-', $result['sohs']);
                $lastNum = isset($parts[2]) ? (int)$parts[2] : 0;
                $nextNum = $lastNum + 1;
            } else {
                $nextNum = 1;
            }
            
            $numStr = str_pad((string)$nextNum, 2, '0', STR_PAD_LEFT);
            return $prefix . $numStr;
        } catch (PDOException $e) {
            error_log("Error in HoSoHCKD::generateSoHS: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Lưu hoặc cập nhật hồ sơ HC
     */
    public function saveHoSo(array $data): bool {
        try {
            error_log("=== HoSoHCKD::saveHoSo ===");
            error_log("Input data: " . print_r($data, true));
            
            // Kiểm tra xem đã tồn tại chưa
            $existing = $this->getByDeviceAndDate($data['tenmay'], $data['ngayhc']);
            
            error_log("Existing record: " . ($existing ? "Found (stt=" . $existing['stt'] . ")" : "Not found"));
            
            if ($existing) {
                // UPDATE
                error_log("Attempting UPDATE...");
                $result = $this->update((int)$existing['stt'], $data);
                error_log("UPDATE result: " . $result);
                return $result >= 0;
            } else {
                // INSERT
                error_log("Attempting INSERT...");
                $id = $this->create($data);
                error_log("INSERT result (ID): " . $id);
                return (int)$id > 0;
            }
        } catch (PDOException $e) {
            error_log("Error in HoSoHCKD::saveHoSo: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        } catch (Exception $e) {
            error_log("General error in HoSoHCKD::saveHoSo: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Lấy hồ sơ HC mới nhất của thiết bị
     */
    public function getLatestByDevice(string $mavattu): ?array {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE tenmay = :tenmay 
                    ORDER BY ngayhc DESC 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['tenmay' => $mavattu]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error in HoSoHCKD::getLatestByDevice: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Lấy danh sách hồ sơ HC/KĐ theo khoảng thời gian
     */
    public function getByDateRange(string $tungay, string $denngay, string $search = ''): array {
        try {
            $sql = "SELECT h.*, 
                           t.tenthietbi, t.tenviettat, t.bophansh, t.thoihankd, t.chusohuu,
                           DATE_ADD(h.ngayhc, INTERVAL CAST(t.thoihankd AS SIGNED) MONTH) as ngayhc_tieptheo,
                           CASE 
                               WHEN h.dinhky = 'on' THEN 'DK'
                               WHEN h.dotxuat = 'on' THEN 'DX'
                               ELSE ''
                           END as loai_hc
                    FROM {$this->table} h
                    LEFT JOIN thietbihckd_iso t ON h.tenmay = t.mavattu
                    WHERE h.ngayhc >= :tungay AND h.ngayhc <= :denngay";
            
            $params = [
                'tungay' => $tungay,
                'denngay' => $denngay
            ];
            
            if ($search) {
                $sql .= " AND (h.tenmay LIKE :search OR h.sohs LIKE :search OR t.tenthietbi LIKE :search OR h.nhanvien LIKE :search)";
                $params['search'] = "%$search%";
            }
            
            $sql .= " ORDER BY h.ngayhc DESC, h.stt DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in HoSoHCKD::getByDateRange: " . $e->getMessage());
            return [];
        }
    }
}
