<?php
declare(strict_types=1);

/**
 * ActivityLogger Class
 * Handles logging of all database operations (INSERT, UPDATE, DELETE)
 * Tracks user actions, timestamps, and data changes
 */
class ActivityLogger
{
    private PDO $db;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    /**
     * Log a database operation
     * 
     * @param string $tableName Table being modified
     * @param string $action Action type: INSERT, UPDATE, DELETE
     * @param int|null $recordId ID of the affected record
     * @param array|null $oldData Data before change (for UPDATE/DELETE)
     * @param array|null $newData Data after change (for INSERT/UPDATE)
     * @return bool Success status
     */
    public function log(
        string $tableName,
        string $action,
        ?int $recordId = null,
        ?array $oldData = null,
        ?array $newData = null
    ): bool {
        // Check if user is logged in
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
            return false; // Skip logging if no user session
        }
        
        $userId = (int)$_SESSION['user_id'];
        $username = $_SESSION['username'];
        
        // Get client information
        $ipAddress = $this->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Prepare data for storage
        $oldDataJson = $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null;
        $newDataJson = $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null;
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs 
                (user_id, username, table_name, action, record_id, old_data, new_data, ip_address, user_agent)
                VALUES 
                (:user_id, :username, :table_name, :action, :record_id, :old_data, :new_data, :ip_address, :user_agent)
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':username' => $username,
                ':table_name' => $tableName,
                ':action' => strtoupper($action),
                ':record_id' => $recordId,
                ':old_data' => $oldDataJson,
                ':new_data' => $newDataJson,
                ':ip_address' => $ipAddress,
                ':user_agent' => substr($userAgent, 0, 255) // Limit length
            ]);
            
            return true;
        } catch (PDOException $e) {
            // Log error but don't break the main operation
            error_log("ActivityLogger Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get client IP address (handles proxies)
     * 
     * @return string Client IP address
     */
    private function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle multiple IPs (get first one)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return 'UNKNOWN';
    }
    
    /**
     * Get recent activity logs
     * 
     * @param int $limit Number of logs to retrieve
     * @param int $offset Offset for pagination
     * @param array $filters Optional filters (table_name, action, user_id, date_from, date_to)
     * @return array Array of log entries
     */
    public function getLogs(int $limit = 50, int $offset = 0, array $filters = []): array
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['table_name'])) {
            $where[] = "table_name = :table_name";
            $params[':table_name'] = $filters['table_name'];
        }
        
        if (!empty($filters['action'])) {
            $where[] = "action = :action";
            $params[':action'] = strtoupper($filters['action']);
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = "user_id = :user_id";
            $params[':user_id'] = (int)$filters['user_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "created_at >= :date_from";
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "created_at <= :date_to";
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $stmt = $this->db->prepare("
            SELECT * FROM activity_logs 
            {$whereClause}
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Count total logs with filters
     * 
     * @param array $filters Optional filters
     * @return int Total count
     */
    public function countLogs(array $filters = []): int
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['table_name'])) {
            $where[] = "table_name = :table_name";
            $params[':table_name'] = $filters['table_name'];
        }
        
        if (!empty($filters['action'])) {
            $where[] = "action = :action";
            $params[':action'] = strtoupper($filters['action']);
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = "user_id = :user_id";
            $params[':user_id'] = (int)$filters['user_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "created_at >= :date_from";
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "created_at <= :date_to";
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM activity_logs {$whereClause}");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}
