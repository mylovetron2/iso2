<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ActivityLogger.php';

class BaseModel {
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id'; // Default primary key
    protected ?ActivityLogger $logger = null;
    protected bool $enableLogging = true; // Enable/disable logging per model
    
    public function __construct(string $table, bool $enableLogging = true) {
        $this->db = getDBConnection();
        $this->table = $table;
        $this->enableLogging = $enableLogging;
        
        // Initialize logger if logging is enabled
        if ($this->enableLogging) {
            $this->logger = new ActivityLogger($this->db);
        }
    }
    
    protected function query(string $sql, array $params = []): PDOStatement {
        // For latin1 database: Use latin1 connection to properly handle UTF-8 bytes
        $this->db->exec("SET NAMES latin1");
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function find(int $id): array|false {
        $stmt = $this->query("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id]);
        $row = $stmt->fetch();
        return $row ? $row : false;
    }
    
    public function all(): array {
        $stmt = $this->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }
    
    /**
     * Get all records with optional WHERE clause, params, limit and offset
     */
    public function getAll(string $where = '', array $params = [], int $limit = 0, int $offset = 0): array {
        $sql = "SELECT * FROM {$this->table}";
        if ($where) {
            $sql .= " $where";
        }
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
            if ($offset > 0) {
                $sql .= " OFFSET $offset";
            }
        }
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Count records with optional WHERE clause
     */
    public function count(string $where = '', array $params = []): int {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        if ($where) {
            $sql .= " $where";
        }
        $stmt = $this->query($sql, $params);
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Find by primary key (alias for find)
     */
    public function findById(int $id): array|false {
        return $this->find($id);
    }
    
    public function create(array $data): string {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->query($sql, $data);
        $insertId = $this->db->lastInsertId();
        
        // Log the INSERT operation
        if ($this->enableLogging && $this->logger) {
            $this->logger->log(
                $this->table,
                'INSERT',
                (int)$insertId,
                null,
                $data
            );
        }
        
        return $insertId;
    }
    
    public function update(int $id, array $data): int {
        // Get old data before update for logging
        $oldData = null;
        if ($this->enableLogging && $this->logger) {
            $oldData = $this->find($id);
        }
        
        $setClause = '';
        foreach ($data as $key => $value) {
            $setClause .= "{$key} = :{$key}, ";
        }
        $setClause = rtrim($setClause, ', ');
        $data[$this->primaryKey] = $id;
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = :{$this->primaryKey}";
        $rowCount = $this->query($sql, $data)->rowCount();
        
        // Log the UPDATE operation
        if ($this->enableLogging && $this->logger && $rowCount > 0) {
            $this->logger->log(
                $this->table,
                'UPDATE',
                $id,
                $oldData ?: [],
                $data
            );
        }
        
        return $rowCount;
    }
    
    public function delete(int $id): int {
        // Get old data before delete for logging
        $oldData = null;
        if ($this->enableLogging && $this->logger) {
            $oldData = $this->find($id);
        }
        
        $rowCount = $this->query("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id])->rowCount();
        
        // Log the DELETE operation
        if ($this->enableLogging && $this->logger && $rowCount > 0 && $oldData) {
            $this->logger->log(
                $this->table,
                'DELETE',
                $id,
                $oldData,
                null
            );
        }
        
        return $rowCount;
    }
}
