<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Model: HoSoSCBDDinhMuc
 * Quan ly dinh muc KPI (kiem_tra/BD cap 1-2-3/hieu chuan) gan cho 1 ho so SCBD
 *
 * LUU Y: Model nay KHONG sua bang hososcbd_iso, kpi_baoduong_thietbi_iso, ngthuchien_iso
 * Bang/view duoc tao boi migrations/20260810_create_hososcbd_dinhmuc_iso.sql
 */
class HoSoSCBDDinhMuc extends BaseModel
{
    private const MIGRATION_FILE = __DIR__ . '/../migrations/20260810_create_hososcbd_dinhmuc_iso.sql';

    public function __construct()
    {
        parent::__construct('hososcbd_dinhmuc_iso');
        $this->primaryKey = 'id';
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void
    {
        try {
            $missing = false;
            foreach (['hososcbd_dinhmuc_iso', 'view_hososcbd_kpi_dinhmuc', 'view_hososcbd_kpi_ketluan'] as $obj) {
                $check = $this->db->query("SHOW TABLES LIKE " . $this->db->quote($obj));
                if ($check->rowCount() === 0) {
                    $missing = true;
                    break;
                }
            }
            if ($missing && is_file(self::MIGRATION_FILE)) {
                $this->db->exec((string)file_get_contents(self::MIGRATION_FILE));
            }
        } catch (PDOException $e) {
            error_log('Error ensuring hososcbd_dinhmuc_iso exists: ' . $e->getMessage());
        }
    }

    /**
     * Lay dinh muc + ket luan KPI (Dat/Khong dat) cua 1 ho so, neu da duoc gan
     * Tra ve false neu chua gan hoac view chua san sang, khong bao gio lam sap trang goi no
     */
    public function layTheoHoSo(int $hososcbdStt): array|false
    {
        try {
            $stmt = $this->query(
                'SELECT * FROM view_hososcbd_kpi_ketluan WHERE hososcbd_stt = ? LIMIT 1',
                [$hososcbdStt]
            );
            $row = $stmt->fetch();
            return $row ?: false;
        } catch (PDOException $e) {
            error_log('Error querying view_hososcbd_kpi_ketluan: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Gan/cap nhat dinh muc cho 1 ho so (1 ho so chi co 1 dinh muc - UNIQUE KEY hososcbd_stt)
     */
    public function luuDinhMuc(int $hososcbdStt, int $kpiBaoDuongStt, string $loaiCongViec, ?string $createdBy = null): bool
    {
        $hopLe = ['kiem_tra', 'bd_cap_1', 'bd_cap_2', 'bd_cap_3', 'hieu_chuan'];
        if (!in_array($loaiCongViec, $hopLe, true)) {
            return false;
        }

        try {
            $sql = "INSERT INTO hososcbd_dinhmuc_iso (hososcbd_stt, kpi_baoduong_stt, loai_congviec, created_by)
                    VALUES (:hososcbd_stt, :kpi_baoduong_stt, :loai_congviec, :created_by)
                    ON DUPLICATE KEY UPDATE
                        kpi_baoduong_stt = VALUES(kpi_baoduong_stt),
                        loai_congviec = VALUES(loai_congviec)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':hososcbd_stt' => $hososcbdStt,
                ':kpi_baoduong_stt' => $kpiBaoDuongStt,
                ':loai_congviec' => $loaiCongViec,
                ':created_by' => $createdBy,
            ]);
        } catch (PDOException $e) {
            error_log('Error saving hososcbd_dinhmuc_iso: ' . $e->getMessage());
            return false;
        }
    }
}
