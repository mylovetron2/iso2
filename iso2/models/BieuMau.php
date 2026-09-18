<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class BieuMau extends BaseModel
{
    public function __construct()
    {
        parent::__construct('bieu_mau');
    }

    public function allOrdered(?int $folderId = null, bool $uncategorized = false, string $search = ''): array
    {
        $sql = 'SELECT b.*, t.ten_thu_muc
                FROM bieu_mau b
                LEFT JOIN bieu_mau_thu_muc t ON t.id = b.thu_muc_id';
        $params = [];
        $conditions = [];
        if ($folderId !== null) {
            $conditions[] = 'b.thu_muc_id = ?';
            $params[] = $folderId;
        } elseif ($uncategorized) {
            $conditions[] = 'b.thu_muc_id IS NULL';
        }
        if ($search !== '') {
            $conditions[] = '(b.ten_hien_thi LIKE ? OR t.ten_thu_muc LIKE ?)';
            $searchValue = '%' . $search . '%';
            $params[] = $searchValue;
            $params[] = $searchValue;
        }
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY COALESCE(t.ten_thu_muc, \'\') ASC, b.ten_hien_thi ASC, b.id DESC';
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
