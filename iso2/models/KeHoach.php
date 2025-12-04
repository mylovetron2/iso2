<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class KeHoach extends BaseModel {
    protected string $table = 'kehoach_iso';
    protected string $primaryKey = 'stt';
    
    public function __construct() {
        parent::__construct('kehoach_iso');
        $this->primaryKey = 'stt';
    }
    
    /**
     * Get all kehoach records for a specific year
     */
    public function getByYear(int $namkh): array {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE namkh = :namkh ORDER BY somay, thang";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['namkh' => $namkh]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in KeHoach::getByYear: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Count how many plans a device has in a year
     */
    public function countPlansByDevice(string $somay, int $namkh): int {
        try {
            $sql = "SELECT COUNT(*) as cnt FROM {$this->table} WHERE somay = :somay AND namkh = :namkh";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['somay' => $somay, 'namkh' => $namkh]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['cnt'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error in KeHoach::countPlansByDevice: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get all plans for a specific device in a year
     */
    public function getPlansByDevice(string $somay, int $namkh): array {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE somay = :somay AND namkh = :namkh ORDER BY thang";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['somay' => $somay, 'namkh' => $namkh]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in KeHoach::getPlansByDevice: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get distinct years
     */
    public function getDistinctYears(): array {
        try {
            $sql = "SELECT DISTINCT namkh FROM {$this->table} ORDER BY namkh DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error in KeHoach::getDistinctYears: " . $e->getMessage());
            return [];
        }
    }
}
