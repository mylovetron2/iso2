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
    private const MIGRATION_FILE_MANUAL_HOUR = __DIR__ . '/../migrations/20260811_add_dinh_muc_gio_thu_cong.sql';

    public function __construct()
    {
        parent::__construct('hososcbd_dinhmuc_iso');
        $this->primaryKey = 'id';
        $this->ensureTableExists();
        $this->ensureManualHourColumnExists();
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
     * Tu them cot dinh_muc_gio_thu_cong va cap nhat lai 2 view neu DB cu chua co
     */
    private function ensureManualHourColumnExists(): void
    {
        try {
            $check = $this->db->query("SHOW COLUMNS FROM hososcbd_dinhmuc_iso LIKE 'dinh_muc_gio_thu_cong'");
            if ($check->rowCount() === 0 && is_file(self::MIGRATION_FILE_MANUAL_HOUR)) {
                $this->db->exec((string)file_get_contents(self::MIGRATION_FILE_MANUAL_HOUR));
            }
        } catch (PDOException $e) {
            error_log('Error ensuring dinh_muc_gio_thu_cong exists: ' . $e->getMessage());
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
            if ($row) {
                return $row;
            }

            $rawStmt = $this->query(
                "SELECT
                    d.id AS dinhmuc_id,
                    d.hososcbd_stt,
                    h.hoso,
                    h.phieu,
                    h.mavt,
                    h.somay,
                    d.loai_congviec,
                    d.dinh_muc_gio_thu_cong,
                    d.kpi_baoduong_stt,
                    k.ten_thiet_bi,
                    CASE d.loai_congviec
                        WHEN 'kiem_tra'   THEN k.kiem_tra_nhan_cong
                        WHEN 'bd_cap_1'   THEN k.bd_cap_1_nhan_cong
                        WHEN 'bd_cap_2'   THEN k.bd_cap_2_nhan_cong
                        WHEN 'bd_cap_3'   THEN k.bd_cap_3_nhan_cong
                        WHEN 'hieu_chuan' THEN k.hieu_chuan_nhan_cong
                    END AS dinh_muc_nhan_cong,
                    COALESCE(
                        d.dinh_muc_gio_thu_cong,
                        CASE d.loai_congviec
                            WHEN 'kiem_tra'   THEN k.kiem_tra_so_gio
                            WHEN 'bd_cap_1'   THEN k.bd_cap_1_so_gio
                            WHEN 'bd_cap_2'   THEN k.bd_cap_2_so_gio
                            WHEN 'bd_cap_3'   THEN k.bd_cap_3_so_gio
                            WHEN 'hieu_chuan' THEN k.hieu_chuan_so_gio
                        END
                    ) AS dinh_muc_so_gio,
                    CASE d.loai_congviec
                        WHEN 'kiem_tra'   THEN k.kiem_tra_nguoi_thuc_hien
                        WHEN 'bd_cap_1'   THEN k.bd_cap_1_nguoi_thuc_hien
                        WHEN 'bd_cap_2'   THEN k.bd_cap_2_nguoi_thuc_hien
                        WHEN 'bd_cap_3'   THEN k.bd_cap_3_nguoi_thuc_hien
                        WHEN 'hieu_chuan' THEN k.hieu_chuan_nguoi_thuc_hien
                    END AS dinh_muc_nguoi_thuc_hien,
                    CASE d.loai_congviec
                        WHEN 'kiem_tra'   THEN k.kiem_tra_noi_dung
                        WHEN 'bd_cap_1'   THEN k.bd_cap_1_noi_dung
                        WHEN 'bd_cap_2'   THEN k.bd_cap_2_noi_dung
                        WHEN 'bd_cap_3'   THEN k.bd_cap_3_noi_dung
                        WHEN 'hieu_chuan' THEN k.hieu_chuan_noi_dung
                    END AS dinh_muc_noi_dung,
                    CASE d.loai_congviec
                        WHEN 'bd_cap_2'   THEN k.bd_cap_2_tan_suat_thang
                        WHEN 'bd_cap_3'   THEN k.bd_cap_3_tan_suat_thang
                        WHEN 'hieu_chuan' THEN k.hieu_chuan_tan_suat_thang
                        ELSE NULL
                    END AS dinh_muc_tan_suat_thang,
                    (SELECT MAX(n.giolv) FROM ngthuchien_iso n WHERE n.mahoso = h.hoso) AS gio_thuc_te,
                    CASE
                        WHEN (SELECT MAX(n.giolv) FROM ngthuchien_iso n WHERE n.mahoso = h.hoso) IS NULL OR COALESCE(d.dinh_muc_gio_thu_cong, CASE d.loai_congviec
                            WHEN 'kiem_tra'   THEN k.kiem_tra_so_gio
                            WHEN 'bd_cap_1'   THEN k.bd_cap_1_so_gio
                            WHEN 'bd_cap_2'   THEN k.bd_cap_2_so_gio
                            WHEN 'bd_cap_3'   THEN k.bd_cap_3_so_gio
                            WHEN 'hieu_chuan' THEN k.hieu_chuan_so_gio
                        END) IS NULL THEN 'chua_du_du_lieu'
                        WHEN (SELECT MAX(n.giolv) FROM ngthuchien_iso n WHERE n.mahoso = h.hoso) <= COALESCE(d.dinh_muc_gio_thu_cong, CASE d.loai_congviec
                            WHEN 'kiem_tra'   THEN k.kiem_tra_so_gio
                            WHEN 'bd_cap_1'   THEN k.bd_cap_1_so_gio
                            WHEN 'bd_cap_2'   THEN k.bd_cap_2_so_gio
                            WHEN 'bd_cap_3'   THEN k.bd_cap_3_so_gio
                            WHEN 'hieu_chuan' THEN k.hieu_chuan_so_gio
                        END) THEN 'dat'
                        ELSE 'khong_dat'
                    END AS ket_luan_kpi
                 FROM hososcbd_dinhmuc_iso d
                 INNER JOIN hososcbd_iso h ON h.stt = d.hososcbd_stt
                 LEFT JOIN kpi_baoduong_thietbi_iso k ON k.id = d.kpi_baoduong_stt
                 WHERE d.hososcbd_stt = ?
                 LIMIT 1",
                [$hososcbdStt]
            );
            $rawRow = $rawStmt->fetch();
            if ($rawRow) {
                return $rawRow;
            }

            $fallbackStmt = $this->query(
                "SELECT
                    NULL AS dinhmuc_id,
                    h.stt AS hososcbd_stt,
                    h.hoso,
                    h.phieu,
                    h.mavt,
                    h.somay,
                    'bd_cap_1' AS loai_congviec,
                    NULL AS dinh_muc_gio_thu_cong,
                    k.id AS kpi_baoduong_stt,
                    k.ten_thiet_bi,
                    k.bd_cap_1_nhan_cong AS dinh_muc_nhan_cong,
                    k.bd_cap_1_so_gio AS dinh_muc_so_gio,
                    k.bd_cap_1_nguoi_thuc_hien AS dinh_muc_nguoi_thuc_hien,
                    k.bd_cap_1_noi_dung AS dinh_muc_noi_dung,
                    NULL AS dinh_muc_tan_suat_thang,
                    (SELECT MAX(n.giolv) FROM ngthuchien_iso n WHERE n.mahoso = h.hoso) AS gio_thuc_te,
                    CASE
                        WHEN (SELECT MAX(n.giolv) FROM ngthuchien_iso n WHERE n.mahoso = h.hoso) IS NULL OR k.bd_cap_1_so_gio IS NULL THEN 'chua_du_du_lieu'
                        WHEN (SELECT MAX(n.giolv) FROM ngthuchien_iso n WHERE n.mahoso = h.hoso) <= k.bd_cap_1_so_gio THEN 'dat'
                        ELSE 'khong_dat'
                    END AS ket_luan_kpi
                 FROM hososcbd_iso h
                 LEFT JOIN (
                    SELECT MIN(stt) AS stt, mavt, somay
                    FROM thietbi_iso
                    GROUP BY mavt, somay
                 ) t ON t.mavt = h.mavt AND t.somay = h.somay
                 LEFT JOIN thietbi_kpi_baoduong_iso l ON l.thietbi_stt = t.stt
                 LEFT JOIN kpi_baoduong_thietbi_iso k ON k.id = l.kpi_baoduong_stt
                 WHERE h.stt = ? AND k.id IS NOT NULL
                 LIMIT 1",
                [$hososcbdStt]
            );
            $fallbackRow = $fallbackStmt->fetch();
            return $fallbackRow ?: false;
        } catch (PDOException $e) {
            error_log('Error querying view_hososcbd_kpi_ketluan: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Gan/cap nhat dinh muc cho 1 ho so (1 ho so chi co 1 dinh muc - UNIQUE KEY hososcbd_stt)
     * $dinhMucGioThuCong: dinh muc gio nhap tay (so thuc), null neu dung theo KPI thiet bi
     */
    public function luuDinhMuc(
        int $hososcbdStt,
        ?int $kpiBaoDuongStt,
        ?string $loaiCongViec,
        ?string $createdBy = null,
        ?float $dinhMucGioThuCong = null
    ): bool {
        $hopLe = ['kiem_tra', 'bd_cap_1', 'bd_cap_2', 'bd_cap_3', 'hieu_chuan'];
        if ($dinhMucGioThuCong === null) {
            if ($kpiBaoDuongStt === null || $loaiCongViec === null || !in_array($loaiCongViec, $hopLe, true)) {
                return false;
            }
        } elseif ($loaiCongViec !== null && !in_array($loaiCongViec, $hopLe, true)) {
            return false;
        }

        try {
            $sql = "INSERT INTO hososcbd_dinhmuc_iso (hososcbd_stt, kpi_baoduong_stt, loai_congviec, dinh_muc_gio_thu_cong, created_by)
                    VALUES (:hososcbd_stt, :kpi_baoduong_stt, :loai_congviec, :dinh_muc_gio_thu_cong, :created_by)
                    ON DUPLICATE KEY UPDATE
                        kpi_baoduong_stt = VALUES(kpi_baoduong_stt),
                        loai_congviec = VALUES(loai_congviec),
                        dinh_muc_gio_thu_cong = VALUES(dinh_muc_gio_thu_cong)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':hososcbd_stt' => $hososcbdStt,
                ':kpi_baoduong_stt' => $kpiBaoDuongStt,
                ':loai_congviec' => $loaiCongViec,
                ':dinh_muc_gio_thu_cong' => $dinhMucGioThuCong,
                ':created_by' => $createdBy,
            ]);
        } catch (PDOException $e) {
            error_log('Error saving hososcbd_dinhmuc_iso: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Gán định mức mặc định cho hồ sơ theo thiết bị được chọn.
     * Mặc định: loại công việc = bd_cap_1.
     */
    public function autoAssignDefaultByDevice(int $hososcbdStt, string $mavt, string $somay, ?string $createdBy = null): bool
    {
        $mavt = trim($mavt);
        $somay = trim($somay);
        if ($hososcbdStt <= 0 || $mavt === '' || $somay === '') {
            return false;
        }

        try {
            $stmt = $this->db->prepare(
                'SELECT l.kpi_baoduong_stt
                 FROM thietbi_iso t
                 LEFT JOIN thietbi_kpi_baoduong_iso l ON l.thietbi_stt = t.stt
                 WHERE t.mavt = :mavt AND t.somay = :somay
                 LIMIT 1'
            );
            $stmt->execute([
                ':mavt' => $mavt,
                ':somay' => $somay,
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || empty($row['kpi_baoduong_stt'])) {
                return false;
            }

            return $this->luuDinhMuc(
                $hososcbdStt,
                (int)$row['kpi_baoduong_stt'],
                'bd_cap_1',
                $createdBy,
                null
            );
        } catch (PDOException $e) {
            error_log('Error auto assigning default KPI by device: ' . $e->getMessage());
            return false;
        }
    }
}
