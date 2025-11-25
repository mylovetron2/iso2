<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Model: PhieuBanGiaoThietBi (Chi tiết thiết bị trong phiếu bàn giao)
 */
class PhieuBanGiaoThietBi extends BaseModel
{
    public function __construct()
    {
        parent::__construct('phieubangiao_thietbi_iso');
        $this->primaryKey = 'stt';
    }
    
    /**
     * Lấy danh sách thiết bị theo số phiếu
     */
    public function getBySoPhieu(string $sophieu): array
    {
        $sophieuEscaped = $this->db->quote($sophieu);
        $sql = "SELECT pt.*, 
                       h.mavt, 
                       COALESCE(t.tenvt, h.mavt) as tenvt,
                       h.somay, 
                       h.maql, 
                       h.phieu as phieu_yc, 
                       h.madv
                FROM {$this->table} pt
                INNER JOIN hososcbd_iso h ON pt.hososcbd_stt = h.stt
                LEFT JOIN thietbi_iso t ON h.mavt = t.mavt AND h.somay = t.somay
                WHERE pt.sophieu = $sophieuEscaped
                ORDER BY pt.stt";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Xóa tất cả thiết bị của 1 phiếu
     */
    public function deleteBySoPhieu(string $sophieu): int
    {
        $sophieuEscaped = $this->db->quote($sophieu);
        $sql = "DELETE FROM {$this->table} WHERE sophieu = $sophieuEscaped";
        return $this->query($sql)->rowCount();
    }
    
    /**
     * Thêm nhiều thiết bị cùng lúc
     */
    public function createMultiple(string $sophieu, array $devices): bool
    {
        $this->db->beginTransaction();
        
        try {
            foreach ($devices as $device) {
                $data = [
                    'sophieu' => $sophieu,
                    'hososcbd_stt' => $device['hososcbd_stt'],
                    'tinhtrang' => $device['tinhtrang'] ?? '',
                    'ghichu' => $device['ghichu'] ?? ''
                ];
                $this->create($data);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creating multiple devices: " . $e->getMessage());
            return false;
        }
    }
}
