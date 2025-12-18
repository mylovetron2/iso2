<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Resume extends BaseModel
{
    protected string $primaryKey = 'stt';
    
    public function __construct()
    {
        parent::__construct('resume');
        $this->primaryKey = 'stt';
    }

    /**
     * Lấy danh sách nhân viên còn làm việc
     */
    public function getActiveEmployees(): array
    {
        $sql = "SELECT stt, hoten, chucdanh, donvi 
                FROM {$this->table} 
                WHERE nghiviec IS NULL OR nghiviec = ''
                ORDER BY hoten";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Lấy thông tin nhân viên theo tên
     */
    public function getByName(string $name): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE hoten = :name LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':name' => $name]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Tìm kiếm nhân viên
     */
    public function search(string $keyword): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE hoten LIKE :keyword OR chucdanh LIKE :keyword
                ORDER BY hoten";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':keyword' => "%$keyword%"]);
        
        return $stmt->fetchAll();
    }
}
