<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class PhanLoaiVatTu extends BaseModel
{
    public function __construct()
    {
        parent::__construct('phanloai_vattu_thanh_ly_iso');
        $this->primaryKey = 'id';
    }
    
    /**
     * Lấy tất cả phân loại sắp xếp theo thứ tự
     */
    public function getAllOrdered(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY thu_tu ASC, id ASC";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Kiểm tra mã phân loại đã tồn tại chưa
     */
    public function isCodeExists(string $maPhanLoai, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE ma_phanloai = :ma_phanloai";
        $params = [':ma_phanloai' => $maPhanLoai];
        
        if ($excludeId !== null) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }
        
        $stmt = $this->query($sql, $params);
        return (int)$stmt->fetchColumn() > 0;
    }
    
    /**
     * Kiểm tra phân loại có đang được sử dụng không
     */
    public function isUsedInVatTu(int $id): bool
    {
        $sql = "SELECT COUNT(*) FROM vattu_thanh_ly_iso WHERE phanloai_id = :id";
        $stmt = $this->query($sql, [':id' => $id]);
        return (int)$stmt->fetchColumn() > 0;
    }
    
    /**
     * Lấy số lượng vật tư đang sử dụng phân loại này
     */
    public function countUsedInVatTu(int $id): int
    {
        $sql = "SELECT COUNT(*) FROM vattu_thanh_ly_iso WHERE phanloai_id = :id";
        $stmt = $this->query($sql, [':id' => $id]);
        return (int)$stmt->fetchColumn();
    }
}
