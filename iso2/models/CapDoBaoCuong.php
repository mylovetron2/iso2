<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Model: CapDoBaoCuong
 * Quản lý 3 cấp độ bảo dưỡng với KPI chuẩn
 */
class CapDoBaoCuong extends BaseModel
{
    protected string $primaryKey = 'stt';
    
    public function __construct()
    {
        parent::__construct('capdo_baocuong_iso');
        $this->primaryKey = 'stt';
    }

    /**
     * Lấy tất cả cấp độ đang kích hoạt
     */
    public function getActiveLevels(): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE trang_thai = 1 
                ORDER BY thu_tu ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Lấy cấp độ theo mã
     */
    public function getByCode(string $maCapdo): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE ma_capdo = :ma_capdo LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ma_capdo' => $maCapdo]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Lấy KPI chuẩn của 1 cấp độ
     */
    public function getKPIChuan(int $capdoStt): ?float
    {
        $sql = "SELECT kpi_gio_chuan FROM {$this->table} WHERE stt = :stt LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':stt' => $capdoStt]);
        
        $result = $stmt->fetch();
        return $result ? (float)$result['kpi_gio_chuan'] : null;
    }

    /**
     * Thống kê tổng quan các cấp độ
     */
    public function getStatistics(): array
    {
        $sql = "SELECT * FROM view_thongke_theo_capdo ORDER BY stt";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
