<?php
class BaseModel {
    protected $db;
    protected $table;
    public function __construct($table) {
        $this->db = getDBConnection();
        $this->table = $table;
    }
    protected function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    public function find($id) {
        $stmt = $this->query("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
        return $stmt->fetch();
    }
    public function all() {
        $stmt = $this->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }
    public function create($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->query($sql, $data);
        return $this->db->lastInsertId();
    }
    public function update($id, $data) {
        $setClause = '';
        foreach ($data as $key => $value) {
            $setClause .= "{$key} = :{$key}, ";
        }
        $setClause = rtrim($setClause, ', ');
        $data['id'] = $id;
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = :id";
        return $this->query($sql, $data)->rowCount();
    }
    public function delete($id) {
        return $this->query("DELETE FROM {$this->table} WHERE id = ?", [$id])->rowCount();
    }
}
