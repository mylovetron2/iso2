<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Vitri extends BaseModel
{
    public function __construct()
    {
        parent::__construct('vitri_iso');
        $this->primaryKey = 'stt';
    }

    public function getList(string $search = '', int $offset = 0, int $limit = 20): array
    {
        [$where, $params] = $this->buildWhere($search);

        $sql = "SELECT stt, mavitri, tenvitri FROM {$this->table}
                WHERE $where
                ORDER BY mavitri ASC
                LIMIT $limit OFFSET $offset";

        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countList(string $search = ''): int
    {
        [$where, $params] = $this->buildWhere($search);

        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE $where";
        return (int)$this->query($sql, $params)->fetchColumn();
    }

    public function isCodeExists(string $mavitri, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE mavitri = ?";
        $params = [$mavitri];

        if ($excludeId) {
            $sql .= ' AND stt != ?';
            $params[] = $excludeId;
        }

        return (int)$this->query($sql, $params)->fetchColumn() > 0;
    }

    private function buildWhere(string $search): array
    {
        $where = '1=1';
        $params = [];

        if ($search !== '') {
            $where .= ' AND (mavitri LIKE ? OR tenvitri LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        return [$where, $params];
    }
}
