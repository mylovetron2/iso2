<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class QuyTrinhGopY extends BaseModel
{
    public const TRANG_THAI = [
        'moi'       => 'Mới',
        'da_xem'    => 'Đã xem',
        'da_xu_ly'  => 'Đã xử lý',
        'tu_choi'   => 'Không áp dụng',
    ];

    public function __construct()
    {
        parent::__construct('quy_trinh_gopy_iso');
    }

    public function byQuyTrinh(int $quyTrinhId): array
    {
        $sql = 'SELECT * FROM quy_trinh_gopy_iso WHERE quy_trinh_id = ? ORDER BY created_at DESC, id DESC';
        return $this->query($sql, [$quyTrinhId])->fetchAll(PDO::FETCH_ASSOC);
    }

    public function pendingCount(): int
    {
        $stmt = $this->query("SELECT COUNT(*) FROM quy_trinh_gopy_iso WHERE trang_thai = 'moi'");
        return (int)$stmt->fetchColumn();
    }

    public function allPending(int $limit = 200): array
    {
        $sql = "SELECT g.*, q.so_qt, q.ten_qt
                FROM quy_trinh_gopy_iso g
                JOIN quy_trinh_iso q ON q.id = g.quy_trinh_id
                WHERE g.trang_thai = 'moi'
                ORDER BY g.created_at DESC
                LIMIT " . max(1, $limit);
        return $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
