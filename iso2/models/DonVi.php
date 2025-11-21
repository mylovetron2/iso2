<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Model: DonVi (Đơn Vị Khách Hàng)
 * Quản lý thông tin đơn vị/khách hàng
 */
class DonVi extends BaseModel
{
    public function __construct()
    {
        parent::__construct('donvi_iso');
        $this->primaryKey = 'stt';
    }
    
    /**
     * Lấy tất cả đơn vị sắp xếp theo tên
     */
    public function getAllOrdered(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY tendv ASC";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy danh sách đơn vị đơn giản (madv, tendv)
     */
    public function getAllSimple(): array
    {
        $sql = "SELECT madv, tendv FROM {$this->table} ORDER BY tendv ASC";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy đơn vị theo mã
     */
    public function findByMaDV(string $madv): array|false
    {
        $madvEscaped = $this->db->quote($madv);
        $sql = "SELECT * FROM {$this->table} WHERE madv = $madvEscaped";
        $stmt = $this->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Kiểm tra mã đơn vị tồn tại
     */
    public function existsMaDV(string $madv): bool
    {
        $madvEscaped = $this->db->quote($madv);
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE madv = $madvEscaped";
        $stmt = $this->query($sql);
        return (int)$stmt->fetchColumn() > 0;
    }
}
