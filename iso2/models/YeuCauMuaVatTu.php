<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class YeuCauMuaVatTu extends BaseModel
{
    public const TRANG_THAI = [
        'moi' => 'Mới',
        'da_xem' => 'Đã xem',
        'da_xu_ly' => 'Đã xử lý',
        'tu_choi' => 'Không duyệt',
    ];

    public function __construct()
    {
        parent::__construct('yeu_cau_mua_vat_tu');
    }

    public function allOrdered(bool $pendingOnly = false, ?int $year = null, ?string $status = null): array
    {
        $conditions = [];
        $params = [];
        if ($pendingOnly) {
            $conditions[] = "trang_thai = 'moi'";
        }
        if ($status !== null && isset(self::TRANG_THAI[$status])) {
            $conditions[] = 'trang_thai = ?';
            $params[] = $status;
        }
        if ($year !== null) {
            $conditions[] = 'thoi_gian_yeu_cau >= ? AND thoi_gian_yeu_cau < ?';
            $params[] = sprintf('%04d-01-01 00:00:00', $year);
            $params[] = sprintf('%04d-01-01 00:00:00', $year + 1);
        }
        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        return $this->query("SELECT * FROM yeu_cau_mua_vat_tu{$where} ORDER BY thoi_gian_yeu_cau DESC, id DESC LIMIT 500", $params)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function availableYears(): array
    {
        return $this->query('SELECT DISTINCT YEAR(thoi_gian_yeu_cau) AS nam FROM yeu_cau_mua_vat_tu ORDER BY nam DESC')
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    public function pendingCount(): int
    {
        return (int)$this->query("SELECT COUNT(*) FROM yeu_cau_mua_vat_tu WHERE trang_thai = 'moi'")->fetchColumn();
    }
}