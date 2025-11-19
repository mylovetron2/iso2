<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class TiendocongviecPause extends BaseModel {
    protected string $table = 'hososcbd_iso_pauses';
    
    public function __construct() {
        parent::__construct($this->table);
    }
    
    public function getByWorkId(int $work_id): array {
        $stmt = $this->query("SELECT * FROM {$this->table} WHERE work_id = ? ORDER BY id DESC", [$work_id]);
        return $stmt->fetchAll();
    }
    
    public function createPause(array $data): string {
        return $this->create($data);
    }
    
    public function updatePause(int $id, array $data): int {
        return $this->update($id, $data);
    }
    
    public function deletePause(int $id): int {
        return $this->delete($id);
    }
}
