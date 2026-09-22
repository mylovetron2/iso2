<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class QuyTrinh extends BaseModel
{
    public function __construct()
    {
        parent::__construct('quy_trinh_iso');
    }

    public function allOrdered(): array
    {
        $sql = "SELECT q.*,
                    (SELECT COUNT(*) FROM quy_trinh_gopy_iso g WHERE g.quy_trinh_id = q.id) AS so_gopy,
                    (SELECT COUNT(*) FROM quy_trinh_gopy_iso g WHERE g.quy_trinh_id = q.id AND g.trang_thai = 'moi') AS so_gopy_moi
                FROM quy_trinh_iso q
                WHERE q.trang_thai = 1
                ORDER BY q.thu_tu ASC, q.so_qt ASC, q.id ASC";
        return $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findBySoQt(string $soQt): array|false
    {
        $stmt = $this->query('SELECT * FROM quy_trinh_iso WHERE so_qt = ? LIMIT 1', [$soQt]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: false;
    }
}
