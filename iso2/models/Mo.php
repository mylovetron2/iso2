<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Mo extends BaseModel
{
    public function __construct()
    {
        parent::__construct('mo_iso');
        $this->primaryKey = 'stt';
    }
    
    /**
     * Lấy danh sách mỏ với tìm kiếm và phân trang
     */
    public function getList(string $search = '', int $offset = 0, int $limit = 20): array
    {
        $where = "1=1";
        $params = [];
        
        if ($search) {
            $where .= " AND (mamo LIKE ? OR tenmo LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql = "SELECT stt, mamo, tenmo FROM {$this->table} 
                WHERE $where 
                ORDER BY mamo ASC 
                LIMIT $limit OFFSET $offset";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Đếm tổng số mỏ
     */
    public function countList(string $search = ''): int
    {
        $where = "1=1";
        $params = [];
        
        if ($search) {
            $where .= " AND (mamo LIKE ? OR tenmo LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE $where";
        $stmt = $this->query($sql, $params);
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Tìm mỏ theo mã mỏ
     */
    public function findByMaMo(string $mamo): array|false
    {
        $sql = "SELECT * FROM {$this->table} WHERE mamo = ?";
        $stmt = $this->query($sql, [$mamo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Kiểm tra mã mỏ đã tồn tại chưa (dùng khi tạo/sửa)
     */
    public function isCodeExists(string $mamo, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE mamo = ?";
        $params = [$mamo];
        
        if ($excludeId) {
            $sql .= " AND stt != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->query($sql, $params);
        return (int)$stmt->fetchColumn() > 0;
    }
    
    /**
     * Lấy tất cả mỏ (dùng cho dropdown)
     */
    public function getAllMo(): array
    {
        $sql = "SELECT stt, mamo, tenmo FROM {$this->table} ORDER BY mamo ASC";
        $stmt = $this->query($sql, []);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
