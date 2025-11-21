<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Model: LichSuDN (Lịch Sử Dữ Liệu)
 * Quản lý lịch sử thao tác dữ liệu
 */
class LichSuDN extends BaseModel
{
    public function __construct()
    {
        parent::__construct('lichsudn_iso');
        $this->primaryKey = 'stt';
    }
    
    /**
     * Log an action
     * 
     * @param string $action CREATE/UPDATE/DELETE/HANDOVER
     * @param array $data Additional data to log
     * @return int|false
     */
    public function log(string $action, array $data = []): int|false
    {
        $logData = [
            'username' => $_SESSION['username'] ?? 'system',
            'action' => strtoupper($action),
            'table_name' => $data['table_name'] ?? 'hososcbd_iso',
            'record_id' => $data['record_id'] ?? null,
            'maql' => $data['maql'] ?? null,
            'phieu' => $data['phieu'] ?? null,
            'mavt' => $data['mavt'] ?? null,
            'somay' => $data['somay'] ?? null,
            'madv' => $data['madv'] ?? null,
            'description' => $data['description'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        return $this->create($logData);
    }
    
    /**
     * Get history for a specific record
     */
    public function getHistoryByRecordId(int $recordId, int $limit = 50): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE record_id = :record_id 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':record_id', $recordId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get history by maql
     */
    public function getHistoryByMaQL(string $maql, int $limit = 50): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE maql = :maql 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':maql', $maql, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get recent history
     */
    public function getRecentHistory(int $limit = 100): array
    {
        $sql = "SELECT * FROM {$this->table} 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get history by user
     */
    public function getHistoryByUser(string $username, int $limit = 100): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE username = :username 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get statistics
     */
    public function getStats(string $startDate = '', string $endDate = ''): array
    {
        $where = "1=1";
        $params = [];
        
        if ($startDate) {
            $where .= " AND created_at >= :start_date";
            $params[':start_date'] = $startDate;
        }
        
        if ($endDate) {
            $where .= " AND created_at <= :end_date";
            $params[':end_date'] = $endDate . ' 23:59:59';
        }
        
        $sql = "SELECT 
                    action,
                    COUNT(*) as count
                FROM {$this->table} 
                WHERE {$where}
                GROUP BY action
                ORDER BY count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
