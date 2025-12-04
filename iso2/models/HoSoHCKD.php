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
}
