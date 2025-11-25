<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Lo extends BaseModel
{
    public function __construct()
    {
        parent::__construct('lo_iso');
        $this->primaryKey = 'stt';
    }
    
    /**
     * Lấy danh sách lô với tìm kiếm và phân trang
     */
    public function getList(string $search = '', int $offset = 0, int $limit = 20): array
    {
        $where = "1=1";
        $params = [];
        
        if ($search) {
            $where .= " AND (malo LIKE ? OR tenlo LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE $where 
                ORDER BY malo ASC 
                LIMIT $limit OFFSET $offset";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Đếm tổng số lô
     */
    public function countList(string $search = ''): int
    {
        $where = "1=1";
        $params = [];
        
        if ($search) {
            $where .= " AND (malo LIKE ? OR tenlo LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE $where";
        $stmt = $this->query($sql, $params);
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Tìm lô theo mã lô
     */
    public function findByMaLo(string $malo): array|false
    {
        $sql = "SELECT * FROM {$this->table} WHERE malo = ?";
        $stmt = $this->query($sql, [$malo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Kiểm tra mã lô đã tồn tại chưa (dùng khi tạo/sửa)
     */
    public function isCodeExists(string $malo, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE malo = ?";
        $params = [$malo];
        
        if ($excludeId) {
            $sql .= " AND stt != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->query($sql, $params);
        return (int)$stmt->fetchColumn() > 0;
    }
    
    /**
     * Lấy tất cả lô cho dropdown
     */
    public function getAllSimple(): array
    {
        $sql = "SELECT stt, malo, tenlo FROM {$this->table} ORDER BY malo ASC";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
