<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Model: ThietBiCapDoKPI
 * Quản lý liên kết thiết bị với cấp độ KPI
 */
class ThietBiCapDoKPI extends BaseModel
{
    protected string $primaryKey = 'stt';
    
    public function __construct()
    {
        parent::__construct('thietbi_capdo_kpi_iso');
        $this->primaryKey = 'stt';
    }

    /**
     * Lấy các cấp độ KPI của 1 thiết bị
     */
    public function getByThietBi(string $mavt, string $somay): array
    {
        $sql = "SELECT tk.*, c.ten_capdo, c.kpi_gio_chuan as kpi_chuan_capdo
                FROM {$this->table} tk
                INNER JOIN capdo_baocuong_iso c ON tk.capdo_stt = c.stt
                WHERE tk.mavt = :mavt AND tk.somay = :somay
                ORDER BY c.thu_tu";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':mavt' => $mavt, ':somay' => $somay]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy KPI dự kiến cho thiết bị + cấp độ cụ thể
     */
    public function getKPIDuKien(string $mavt, string $somay, int $capdoStt): ?float
    {
        $sql = "SELECT kpi_gio_du_kien FROM {$this->table} 
                WHERE mavt = :mavt AND somay = :somay AND capdo_stt = :capdo_stt
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':mavt' => $mavt,
            ':somay' => $somay,
            ':capdo_stt' => $capdoStt
        ]);
        
        $result = $stmt->fetch();
        return $result ? (float)$result['kpi_gio_du_kien'] : null;
    }

    /**
     * Kiểm tra thiết bị đã có cấp độ chưa
     */
    public function exists(string $mavt, string $somay, int $capdoStt): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE mavt = :mavt AND somay = :somay AND capdo_stt = :capdo_stt";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':mavt' => $mavt,
            ':somay' => $somay,
            ':capdo_stt' => $capdoStt
        ]);
        
        $result = $stmt->fetch();
        return $result && $result['count'] > 0;
    }

    /**
     * Tạo hoặc cập nhật KPI cho thiết bị
     */
    public function createOrUpdate(array $data): int|bool
    {
        $requiredFields = ['mavt', 'somay', 'capdo_stt', 'kpi_gio_du_kien'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }

        // Kiểm tra đã tồn tại chưa
        if ($this->exists($data['mavt'], $data['somay'], $data['capdo_stt'])) {
            // Update
            $where = [
                'mavt' => $data['mavt'],
                'somay' => $data['somay'],
                'capdo_stt' => $data['capdo_stt']
            ];
            unset($data['mavt'], $data['somay'], $data['capdo_stt']);
            return $this->updateWhere($data, $where);
        } else {
            // Insert
            return $this->insert($data);
        }
    }

    /**
     * Xóa KPI của thiết bị
     * Override BaseModel::delete() để sử dụng đúng primary key
     */
    public function delete(int $id): int
    {
        return parent::delete($id);
    }

    /**
     * Lấy danh sách thiết bị theo cấp độ
     */
    public function getByCapDo(int $capdoStt): array
    {
        $sql = "SELECT tk.*, c.ten_capdo
                FROM {$this->table} tk
                INNER JOIN capdo_baocuong_iso c ON tk.capdo_stt = c.stt
                WHERE tk.capdo_stt = :capdo_stt
                ORDER BY tk.mavt, tk.somay";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':capdo_stt' => $capdoStt]);
        return $stmt->fetchAll();
    }
}
