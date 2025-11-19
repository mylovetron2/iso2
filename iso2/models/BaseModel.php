<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class BaseModel {
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id'; // Default primary key
    
    public function __construct(string $table) {
        $this->db = getDBConnection();
        $this->table = $table;
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
    
    public function create(array $data): string {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->query($sql, $data);
        return $this->db->lastInsertId();
    }
    
    public function update(int $id, array $data): int {
        $setClause = '';
        foreach ($data as $key => $value) {
            $setClause .= "{$key} = :{$key}, ";
        }
        $setClause = rtrim($setClause, ', ');
        $data[$this->primaryKey] = $id;
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = :{$this->primaryKey}";
        return $this->query($sql, $data)->rowCount();
    }
    
    public function delete(int $id): int {
        return $this->query("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id])->rowCount();
    }
}
