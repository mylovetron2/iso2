<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Model: ThietBiKpiBaoDuong
 * Quan ly lien ket 1 thietbi_iso <-> 1 kpi_baoduong_thietbi_iso
 */
class ThietBiKpiBaoDuong extends BaseModel
{
    public function __construct()
    {
        parent::__construct('thietbi_kpi_baoduong_iso');
        $this->primaryKey = 'id';
    }

    public function ensureTableExists(): void
    {
        $check = $this->db->query("SHOW TABLES LIKE 'thietbi_kpi_baoduong_iso'");
        if ($check !== false && $check->fetch()) {
            return;
        }
        $migrationPath = __DIR__ . '/../migrations/20260810_create_thietbi_kpi_baoduong_iso.sql';
        if (is_file($migrationPath)) {
            $this->db->exec(trim((string)file_get_contents($migrationPath)));
        }
    }

    /**
     * Danh sach tat ca thiet bi trong thietbi_iso, kem thong tin lien ket KPI neu co
     */
    public function searchWithDetails(string $search, int $limit, int $offset): array
    {
        $whereSql = '';
        $params = [];
        if ($search !== '') {
            $whereSql = 'WHERE t.mavt LIKE ? OR t.somay LIKE ? OR t.tenvt LIKE ? OR k.ten_thiet_bi LIKE ?';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $sql = "SELECT t.stt AS thietbi_stt, t.mavt, t.somay, t.tenvt AS ten_thiet_bi,
                       l.id AS link_id, l.kpi_baoduong_stt, k.ten_thiet_bi AS kpi_ten_thiet_bi,
                       l.created_at, l.updated_at
                FROM thietbi_iso t
                LEFT JOIN thietbi_kpi_baoduong_iso l ON l.thietbi_stt = t.stt
                LEFT JOIN kpi_baoduong_thietbi_iso k ON k.id = l.kpi_baoduong_stt
                {$whereSql}
                ORDER BY t.madv ASC, t.mavt ASC, t.somay ASC, t.stt ASC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($params, [$limit, $offset]));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countSearch(string $search): int
    {
        $whereSql = '';
        $params = [];
        if ($search !== '') {
            $whereSql = 'WHERE t.mavt LIKE ? OR t.somay LIKE ? OR t.tenvt LIKE ? OR k.ten_thiet_bi LIKE ?';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $sql = "SELECT COUNT(*)
                FROM thietbi_iso t
                LEFT JOIN thietbi_kpi_baoduong_iso l ON l.thietbi_stt = t.stt
                LEFT JOIN kpi_baoduong_thietbi_iso k ON k.id = l.kpi_baoduong_stt
                {$whereSql}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function findByThietBiStt(int $thietbiStt): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM thietbi_kpi_baoduong_iso WHERE thietbi_stt = :stt LIMIT 1');
        $stmt->execute([':stt' => $thietbiStt]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: false;
    }

    /**
     * Tao moi hoac cap nhat lien ket cho 1 thiet bi (upsert theo thietbi_stt)
     */
    public function upsertLink(int $thietbiStt, string $mavt, string $somay, string $tenThietBi, int $kpiBaoDuongStt, string $createdBy): void
    {
        $existing = $this->findByThietBiStt($thietbiStt);
        if ($existing) {
            $sql = 'UPDATE thietbi_kpi_baoduong_iso SET
                        mavt = :mavt, somay = :somay, ten_thiet_bi = :ten_thiet_bi,
                        kpi_baoduong_stt = :kpi_baoduong_stt
                    WHERE thietbi_stt = :thietbi_stt';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':mavt' => $mavt,
                ':somay' => $somay,
                ':ten_thiet_bi' => $tenThietBi,
                ':kpi_baoduong_stt' => $kpiBaoDuongStt,
                ':thietbi_stt' => $thietbiStt,
            ]);
            return;
        }

        $sql = 'INSERT INTO thietbi_kpi_baoduong_iso
                    (thietbi_stt, mavt, somay, ten_thiet_bi, kpi_baoduong_stt, created_by)
                VALUES
                    (:thietbi_stt, :mavt, :somay, :ten_thiet_bi, :kpi_baoduong_stt, :created_by)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':thietbi_stt' => $thietbiStt,
            ':mavt' => $mavt,
            ':somay' => $somay,
            ':ten_thiet_bi' => $tenThietBi,
            ':kpi_baoduong_stt' => $kpiBaoDuongStt,
            ':created_by' => $createdBy,
        ]);
    }

    public function deleteById(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM thietbi_kpi_baoduong_iso WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    private function normalizeNameForMatching(string $value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        $value = mb_strtoupper($value, 'UTF-8');
        $value = preg_replace('/[^A-Z0-9]+/', '', $value) ?? '';
        return $value;
    }

    private function collectVariantTokens(string $value): array
    {
        $text = trim((string)$value);
        if ($text === '') {
            return [];
        }

        $parts = preg_split('/[;,|\/]+/', $text) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            $token = trim($part);
            if ($token !== '') {
                $tokens[] = $this->normalizeNameForMatching($token);
            }
        }

        return array_values(array_unique(array_filter($tokens)));
    }

    private function namesMatch(string $deviceCode, string $kpiValue): bool
    {
        $deviceNormalized = $this->normalizeNameForMatching($deviceCode);
        if ($deviceNormalized === '') {
            return false;
        }

        $kpiTokens = $this->collectVariantTokens($kpiValue);
        if ($kpiTokens === []) {
            return false;
        }

        foreach ($kpiTokens as $token) {
            if ($deviceNormalized === $token) {
                return true;
            }

            if (str_starts_with($token, $deviceNormalized) || str_starts_with($deviceNormalized, $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tim cac thiet bi co tenvt khop (khong phan biet hoa/thuong, khong tinh khoang trang thua)
     * voi ten_thiet_bi trong kpi_baoduong_thietbi_iso, kem trang thai lien ket hien tai.
     * Ngoai ra co the khop theo ma co so (vd: AK <-> AK-73, AK-76).
     * $onlyUnlinked = true: chi lay thiet bi chua gan hoac dang gan sai KPI so voi ket qua khop ten.
     */
    public function getAutoMatchCandidates(bool $onlyUnlinked, string $search): array
    {
        $whereParts = [];
        $params = [];
        if ($search !== '') {
            $whereParts[] = '(t.mavt LIKE ? OR t.somay LIKE ? OR t.tenvt LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $whereSql = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

        $sql = "SELECT t.stt AS thietbi_stt, t.mavt, t.somay, t.tenvt AS ten_thiet_bi,
                       l.id AS link_id, l.kpi_baoduong_stt AS current_kpi_stt
                FROM thietbi_iso t
                LEFT JOIN thietbi_kpi_baoduong_iso l ON l.thietbi_stt = t.stt
                {$whereSql}
                ORDER BY t.tenvt ASC, t.mavt ASC, t.somay ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $kpis = $this->db->query('SELECT id, ten_thiet_bi FROM kpi_baoduong_thietbi_iso ORDER BY ten_thiet_bi ASC')
            ->fetchAll(PDO::FETCH_ASSOC);

        $candidates = [];
        foreach ($devices as $device) {
            foreach ($kpis as $kpi) {
                if (!$this->namesMatch((string)$device['mavt'], (string)$kpi['ten_thiet_bi'])) {
                    continue;
                }

                $currentKpiStt = isset($device['current_kpi_stt']) ? (int)$device['current_kpi_stt'] : 0;
                $matchedKpiStt = (int)$kpi['id'];
                if ($onlyUnlinked && $device['link_id'] !== null && $currentKpiStt === $matchedKpiStt) {
                    continue;
                }

                $candidates[] = [
                    'thietbi_stt' => (int)$device['thietbi_stt'],
                    'mavt' => (string)$device['mavt'],
                    'somay' => (string)$device['somay'],
                    'ten_thiet_bi' => (string)$device['ten_thiet_bi'],
                    'kpi_baoduong_stt' => $matchedKpiStt,
                    'kpi_ten_thiet_bi' => (string)$kpi['ten_thiet_bi'],
                    'link_id' => $device['link_id'] ?? null,
                    'current_kpi_stt' => $currentKpiStt,
                ];
                break;
            }
        }

        return $candidates;
    }

    /**
     * Gan hang loat theo danh sach cap (thietbi_stt, mavt, somay, ten_thiet_bi, kpi_baoduong_stt)
     * Tra ve so ban ghi da gan/cap nhat thanh cong.
     */
    public function bulkAssign(array $pairs, string $createdBy): int
    {
        $count = 0;
        foreach ($pairs as $pair) {
            $this->upsertLink(
                (int)$pair['thietbi_stt'],
                (string)$pair['mavt'],
                (string)$pair['somay'],
                (string)$pair['ten_thiet_bi'],
                (int)$pair['kpi_baoduong_stt'],
                $createdBy
            );
            $count++;
        }
        return $count;
    }
}
