-- ========================================
-- Migration: Chuyển thietbi_capdo_kpi_iso sang dùng FK
-- Tác giả: AI Assistant
-- Ngày: 2026-02-24
-- Mục đích: Chuẩn hóa database, sử dụng FK thay vì duplicate mavt/somay
-- ========================================

USE diavatly_db;

-- ========================================
-- Phần 1: Kiểm tra và xử lý bảng hiện tại
-- ========================================

-- Kiểm tra bảng có tồn tại không
SET @table_exists = (
    SELECT COUNT(*) 
    FROM information_schema.tables 
    WHERE table_schema = 'diavatly_db' 
    AND table_name = 'thietbi_capdo_kpi_iso'
);

SELECT CASE 
    WHEN @table_exists > 0 THEN 'Bảng thietbi_capdo_kpi_iso đã tồn tại - Sẽ migrate dữ liệu'
    ELSE 'Bảng thietbi_capdo_kpi_iso chưa tồn tại - Sẽ tạo mới'
END AS migration_status;

-- ========================================
-- Phần 2: Tạo bảng mới với cấu trúc FK
-- ========================================

-- Kiểm tra bảng tham chiếu có tồn tại không
SET @thietbi_exists = (
    SELECT COUNT(*) 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE() 
    AND table_name = 'thietbi_iso'
);

SET @capdo_exists = (
    SELECT COUNT(*) 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE() 
    AND table_name = 'capdo_baocuong_iso'
);

SELECT 
    CASE WHEN @thietbi_exists > 0 THEN '✓ thietbi_iso tồn tại' 
         ELSE '✗ CẢNH BÁO: thietbi_iso KHÔNG tồn tại!' 
    END AS check_thietbi,
    CASE WHEN @capdo_exists > 0 THEN '✓ capdo_baocuong_iso tồn tại' 
         ELSE '✗ CẢNH BÁO: capdo_baocuong_iso KHÔNG tồn tại!' 
    END AS check_capdo;

-- Nếu bảng capdo_baocuong_iso chưa tồn tại, tạo trước
CREATE TABLE IF NOT EXISTS capdo_baocuong_iso (
    stt INT(11) AUTO_INCREMENT PRIMARY KEY,
    ma_capdo VARCHAR(20) NOT NULL UNIQUE,
    ten_capdo VARCHAR(100) NOT NULL,
    mo_ta TEXT,
    kpi_gio_chuan DECIMAL(5,2) NOT NULL,
    mau_sac VARCHAR(20) DEFAULT '#4CAF50',
    thu_tu INT(3) DEFAULT 1,
    trang_thai TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_ma_capdo (ma_capdo),
    INDEX idx_trang_thai (trang_thai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert dữ liệu mẫu nếu bảng trống
INSERT IGNORE INTO capdo_baocuong_iso (ma_capdo, ten_capdo, kpi_gio_chuan, mau_sac, thu_tu, mo_ta)
VALUES 
    ('CAP1', 'Bảo dưỡng Cấp 1', 2.00, '#4CAF50', 1, 'Bảo dưỡng nhẹ, kiểm tra định kỳ'),
    ('CAP2', 'Bảo dưỡng Cấp 2', 4.00, '#FF9800', 2, 'Bảo dưỡng trung bình, thay linh kiện nhỏ'),
    ('CAP3', 'Bảo dưỡng Cấp 3', 8.00, '#F44336', 3, 'Bảo dưỡng nặng, đại tu toàn bộ');

-- Drop bảng cũ nếu tồn tại
DROP TABLE IF EXISTS thietbi_capdo_kpi_iso_new;

-- Tạo bảng KHÔNG có FK trước (để tránh lỗi)
CREATE TABLE thietbi_capdo_kpi_iso_new (
    stt INT(11) AUTO_INCREMENT PRIMARY KEY,
    thietbi_stt INT(11) NOT NULL COMMENT 'FK → thietbi_iso.stt',
    capdo_stt INT(11) NOT NULL COMMENT 'FK → capdo_baocuong_iso.stt',
    kpi_gio_du_kien DECIMAL(5,2) NOT NULL COMMENT 'KPI giờ dự kiến riêng cho thiết bị này',
    ghi_chu TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(50),
    updated_by VARCHAR(50),
    
    -- Indexes để tăng tốc truy vấn (tạo trước FK)
    INDEX idx_thietbi (thietbi_stt),
    INDEX idx_capdo (capdo_stt),
    
    -- Unique constraint: mỗi thiết bị chỉ có 1 KPI cho mỗi cấp độ
    UNIQUE KEY uk_thietbi_capdo (thietbi_stt, capdo_stt)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='KPI riêng cho thiết bị theo cấp độ - Normalized version with FK';

-- Thêm Foreign Keys SAU khi tạo bảng (an toàn hơn)
-- Chỉ thêm FK nếu bảng tham chiếu tồn tại
SET @add_fk_thietbi = CONCAT(
    'ALTER TABLE thietbi_capdo_kpi_iso_new ',
    'ADD CONSTRAINT fk_kpi_thietbi ',
    'FOREIGN KEY (thietbi_stt) REFERENCES thietbi_iso(stt) ',
    'ON DELETE CASCADE ON UPDATE CASCADE'
);

SET @add_fk_capdo = CONCAT(
    'ALTER TABLE thietbi_capdo_kpi_iso_new ',
    'ADD CONSTRAINT fk_kpi_capdo ',
    'FOREIGN KEY (capdo_stt) REFERENCES capdo_baocuong_iso(stt) ',
    'ON DELETE RESTRICT ON UPDATE CASCADE'
);

-- Thực thi ADD CONSTRAINT nếu bảng tồn tại
-- Uncomment 2 dòng dưới để thêm FK constraints
-- PREPARE stmt_thietbi FROM @add_fk_thietbi; EXECUTE stmt_thietbi; DEALLOCATE PREPARE stmt_thietbi;
-- PREPARE stmt_capdo FROM @add_fk_capdo; EXECUTE stmt_capdo; DEALLOCATE PREPARE stmt_capdo;

-- ========================================
-- Phần 3: Migrate dữ liệu từ bảng cũ (nếu có)
-- ========================================

-- Chuyển đổi từ (mavt, somay) sang thietbi_stt
INSERT IGNORE INTO thietbi_capdo_kpi_iso_new 
    (thietbi_stt, capdo_stt, kpi_gio_du_kien, ghi_chu, created_at, updated_at, created_by, updated_by)
SELECT 
    tb.stt AS thietbi_stt,
    old.capdo_stt,
    old.kpi_gio_du_kien,
    CASE 
        WHEN old.ghi_chu IS NOT NULL AND old.ghi_chu != '' 
        THEN CONCAT(old.ghi_chu, ' | Migrated from mavt=', old.mavt, ', somay=', old.somay)
        ELSE CONCAT('Migrated from mavt=', old.mavt, ', somay=', old.somay)
    END AS ghi_chu,
    COALESCE(old.created_at, NOW()) AS created_at,
    COALESCE(old.updated_at, NOW()) AS updated_at,
    old.created_by,
    old.updated_by
FROM thietbi_capdo_kpi_iso old
INNER JOIN thietbi_iso tb 
    ON old.mavt = tb.MAVT 
    AND old.somay = tb.SOMAY
WHERE EXISTS (SELECT 1 FROM information_schema.tables 
              WHERE table_schema = 'diavatly_db' 
              AND table_name = 'thietbi_capdo_kpi_iso');

-- ========================================
-- Phần 4: Kiểm tra dữ liệu sau migration
-- ========================================

-- So sánh số lượng record
SELECT 
    'Old Table (if exists)' AS source,
    COALESCE(COUNT(*), 0) AS total_records
FROM information_schema.tables t
LEFT JOIN thietbi_capdo_kpi_iso old ON 1=1
WHERE t.table_schema = 'diavatly_db' 
AND t.table_name = 'thietbi_capdo_kpi_iso'

UNION ALL

SELECT 
    'New Table (migrated)' AS source,
    COUNT(*) AS total_records
FROM thietbi_capdo_kpi_iso_new;

-- Hiển thị các record KHÔNG migrate được (thiết bị không tồn tại)
SELECT 
    old.stt,
    old.mavt,
    old.somay,
    old.capdo_stt,
    old.kpi_gio_du_kien,
    'Thiết bị không tồn tại trong thietbi_iso' AS warning,
    'Cần thêm thiết bị vào thietbi_iso trước' AS action_required
FROM thietbi_capdo_kpi_iso old
LEFT JOIN thietbi_iso tb 
    ON old.mavt = tb.MAVT 
    AND old.somay = tb.SOMAY
WHERE tb.stt IS NULL
AND EXISTS (SELECT 1 FROM information_schema.tables 
            WHERE table_schema = 'diavatly_db' 
            AND table_name = 'thietbi_capdo_kpi_iso');

-- Xem preview dữ liệu đã migrate
SELECT 
    new.*,
    tb.MAVT,
    tb.SOMAY,
    tb.TENVT,
    cd.ma_capdo,
    cd.ten_capdo,
    cd.kpi_gio_chuan AS kpi_chuan
FROM thietbi_capdo_kpi_iso_new new
LEFT JOIN thietbi_iso tb ON new.thietbi_stt = tb.stt
LEFT JOIN capdo_baocuong_iso cd ON new.capdo_stt = cd.stt
ORDER BY new.stt
LIMIT 10;

-- ========================================
-- Phần 5: Thực hiện chuyển đổi (UNCOMMENT để thực thi)
-- ========================================

-- Bước 5.1: Backup bảng cũ (nếu tồn tại)
-- DROP TABLE IF EXISTS thietbi_capdo_kpi_iso_backup_20260224;
-- CREATE TABLE thietbi_capdo_kpi_iso_backup_20260224 
-- SELECT * FROM thietbi_capdo_kpi_iso 
-- WHERE EXISTS (SELECT 1 FROM information_schema.tables 
--               WHERE table_schema = 'diavatly_db' 
--               AND table_name = 'thietbi_capdo_kpi_iso');

-- Bước 5.2: Drop bảng cũ
-- DROP TABLE IF EXISTS thietbi_capdo_kpi_iso;

-- Bước 5.3: Rename bảng mới thành tên chính thức
-- RENAME TABLE thietbi_capdo_kpi_iso_new TO thietbi_capdo_kpi_iso;

-- Bước 5.4: Verify
-- SELECT COUNT(*) AS total_records FROM thietbi_capdo_kpi_iso;

-- ========================================
-- HƯỚNG DẪN CẬP NHẬT CODE PHP
-- ========================================

/*
==================================================
File: models/ThietBiCapDoKPI.php
==================================================

<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class ThietBiCapDoKPI extends BaseModel
{
    protected $table = 'thietbi_capdo_kpi_iso';
    protected $primaryKey = 'stt';
    
    // ✅ Thay đổi fillable: thietbi_stt thay vì mavt, somay
    protected $fillable = [
        'thietbi_stt',        
        'capdo_stt',
        'kpi_gio_du_kien',
        'ghi_chu'
    ];

    /**
     * Lấy tất cả KPI của 1 thiết bị (tất cả cấp độ)
     * @param int $thietbiStt
     * @return array
     */
    public function getByThietBi(int $thietbiStt): array
    {
        $sql = "SELECT 
                    tk.*,
                    cd.ma_capdo,
                    cd.ten_capdo,
                    cd.kpi_gio_chuan AS kpi_chuan,
                    tb.MAVT,
                    tb.SOMAY,
                    tb.TENVT
                FROM {$this->table} tk
                INNER JOIN capdo_baocuong_iso cd ON tk.capdo_stt = cd.stt
                INNER JOIN thietbi_iso tb ON tk.thietbi_stt = tb.stt
                WHERE tk.thietbi_stt = ?
                ORDER BY cd.thu_tu";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$thietbiStt]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy KPI của thiết bị khi biết mavt và somay
     * @param string $mavt
     * @param string $somay
     * @return array
     */
    public function getByMaVtSoMay(string $mavt, string $somay): array
    {
        $sql = "SELECT 
                    tk.*,
                    cd.ma_capdo,
                    cd.ten_capdo,
                    cd.kpi_gio_chuan AS kpi_chuan
                FROM {$this->table} tk
                INNER JOIN capdo_baocuong_iso cd ON tk.capdo_stt = cd.stt
                INNER JOIN thietbi_iso tb ON tk.thietbi_stt = tb.stt
                WHERE tb.MAVT = ? AND tb.SOMAY = ?
                ORDER BY cd.thu_tu";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$mavt, $somay]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy tất cả KPI của 1 cấp độ
     * @param int $capdoStt
     * @return array
     */
    public function getByCapDo(int $capdoStt): array
    {
        $sql = "SELECT 
                    tk.*,
                    tb.MAVT,
                    tb.SOMAY,
                    tb.TENVT
                FROM {$this->table} tk
                INNER JOIN thietbi_iso tb ON tk.thietbi_stt = tb.stt
                WHERE tk.capdo_stt = ?
                ORDER BY tb.MAVT, tb.SOMAY";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$capdoStt]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tạo hoặc cập nhật KPI (UPSERT pattern)
     * @param array $data
     * @return array
     */
    public function createOrUpdate(array $data): array
    {
        $exists = $this->exists($data['thietbi_stt'], $data['capdo_stt']);
        
        if ($exists) {
            $affected = $this->update($exists['stt'], $data);
            return [
                'success' => true, 
                'action' => 'updated', 
                'stt' => $exists['stt'],
                'affected_rows' => $affected
            ];
        } else {
            $stt = $this->create($data);
            return [
                'success' => true, 
                'action' => 'created', 
                'stt' => $stt
            ];
        }
    }

    /**
     * Kiểm tra KPI đã tồn tại chưa
     * @param int $thietbiStt
     * @param int $capdoStt
     * @return array|null
     */
    public function exists(int $thietbiStt, int $capdoStt): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE thietbi_stt = ? AND capdo_stt = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$thietbiStt, $capdoStt]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Lấy KPI giờ dự kiến cho thiết bị và cấp độ cụ thể
     * @param int $thietbiStt
     * @param int $capdoStt
     * @return float|null
     */
    public function getKPIDuKien(int $thietbiStt, int $capdoStt): ?float
    {
        $sql = "SELECT kpi_gio_du_kien FROM {$this->table} 
                WHERE thietbi_stt = ? AND capdo_stt = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$thietbiStt, $capdoStt]);
        $result = $stmt->fetchColumn();
        return $result !== false ? (float)$result : null;
    }

    /**
     * Override delete để return int
     */
    public function delete(int $id): int
    {
        return parent::delete($id);
    }
}

==================================================
File: models/ThietBi.php (Cần method mới)
==================================================

// Thêm method này vào class ThietBi nếu chưa có:

public function findByMaVtAndSoMay(string $mavt, string $somay): ?array
{
    $sql = "SELECT * FROM {$this->table} 
            WHERE MAVT = ? AND SOMAY = ?";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$mavt, $somay]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

public function getEquipmentInfo(string $mavt, string $somay): ?array
{
    $sql = "SELECT 
                tb.*,
                dv.TEN as ten_donvi,
                dv.DIACHI as diachi_donvi
            FROM {$this->table} tb
            LEFT JOIN donvi_iso dv ON tb.DONVI_STT = dv.stt
            WHERE tb.MAVT = ? AND tb.SOMAY = ?";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$mavt, $somay]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

*/

/*
==================================================
File: controllers/CongViecSuaChuaController.php
==================================================

// ✅ CẬP NHẬT phương thức create():

public function create(): void
{
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        // Lấy dữ liệu POST
        $nhanvienStt = (int)($_POST['nhanvien_stt'] ?? 0);
        $ngayLamViec = $_POST['ngay_lam_viec'] ?? '';
        $mavt = trim($_POST['mavt_thietbi'] ?? '');
        $somay = trim($_POST['somay_thietbi'] ?? '');
        $capdoStt = (int)($_POST['capdo_stt'] ?? 0);
        $soGioLam = (float)($_POST['so_gio_lam'] ?? 0);
        $ghiChu = trim($_POST['ghi_chu'] ?? '');
        
        // Validate required fields
        if (empty($nhanvienStt) || empty($ngayLamViec) || empty($capdoStt) || $soGioLam <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc'
            ]);
            return;
        }
        
        // ✅ Tìm thiết bị để lấy thietbi_stt (FK)
        $thietbi = null;
        $thietbiStt = null;
        if (!empty($mavt) && !empty($somay)) {
            if ($this->thietbiModel) {
                $thietbi = $this->thietbiModel->findByMaVtAndSoMay($mavt, $somay);
                if ($thietbi) {
                    $thietbiStt = (int)$thietbi['stt'];
                }
            }
        }
        
        // ✅ Lấy KPI - Ưu tiên KPI riêng của thiết bị
        $kpiChuan = null;
        if ($thietbiStt !== null && $this->kpiModel) {
            // Tìm KPI riêng cho thiết bị này
            $kpiChuan = $this->kpiModel->getKPIDuKien($thietbiStt, $capdoStt);
        }
        
        // Nếu không có KPI riêng, lấy KPI chuẩn từ cấp độ
        if ($kpiChuan === null) {
            $kpiChuan = $this->capdoModel->getKPIChuan($capdoStt);
        }
        
        // Kiểm tra có thể thêm giờ không (giới hạn 8h/ngày)
        $checkGio = $this->congviecModel->canAddGio($nhanvienStt, $ngayLamViec, $soGioLam);
        
        if (!$checkGio['can_add']) {
            echo json_encode([
                'success' => false,
                'message' => sprintf(
                    'Vượt quá giới hạn 8 giờ/ngày! Hiện tại: %.1f giờ, Thêm %.1f giờ = %.1f giờ (vượt %.1f giờ)',
                    $checkGio['tong_gio_hien_tai'],
                    $soGioLam,
                    $checkGio['tong_gio_hien_tai'] + $soGioLam,
                    $checkGio['vuot_gio']
                )
            ]);
            return;
        }
        
        // ✅ Chuẩn bị data để lưu (bao gồm cả FK thietbi_stt)
        $data = [
            'nhanvien_stt' => $nhanvienStt,
            'ngay_lam_viec' => $ngayLamViec,
            'mavt_thietbi' => $mavt,
            'somay_thietbi' => $somay,
            'thietbi_stt' => $thietbiStt,  // ✅ FK → thietbi_iso
            'capdo_stt' => $capdoStt,
            'so_gio_lam' => $soGioLam,
            'ghi_chu' => $ghiChu
        ];
        
        // Lưu vào database
        $result = $this->congviecModel->createWithValidation($data);
        
        if ($result['success']) {
            // ✅ Tính hiệu suất so với KPI
            $hieuSuat = $kpiChuan > 0 ? ($kpiChuan / $soGioLam) * 100 : 0;
            
            // Lấy thông tin đầy đủ để trả về
            $congviec = $this->congviecModel->find((int)$result['stt']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Thêm công việc thành công',
                'data' => [
                    'stt' => $result['stt'],
                    'congviec' => $congviec,
                    'kpi_chuan' => $kpiChuan,
                    'hieu_suat' => round($hieuSuat, 1),
                    'gio_con_lai' => $checkGio['gio_con_lai'] - $soGioLam,
                    'tong_gio_moi' => $checkGio['tong_gio_hien_tai'] + $soGioLam
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['message'] ?? 'Có lỗi xảy ra khi thêm công việc'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ]);
    }
}

// ✅ Thêm phương thức để quản lý KPI thiết bị

public function manageThietBiKPI(): void
{
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $action = $_POST['kpi_action'] ?? $_GET['kpi_action'] ?? '';
        
        switch ($action) {
            case 'get_by_thietbi':
                // Lấy KPI của thiết bị
                $mavt = $_GET['mavt'] ?? '';
                $somay = $_GET['somay'] ?? '';
                
                $thietbi = $this->thietbiModel->findByMaVtAndSoMay($mavt, $somay);
                if (!$thietbi) {
                    echo json_encode(['success' => false, 'message' => 'Thiết bị không tồn tại']);
                    return;
                }
                
                $kpiList = $this->kpiModel->getByThietBi((int)$thietbi['stt']);
                echo json_encode(['success' => true, 'data' => $kpiList]);
                break;
                
            case 'set_kpi':
                // Thiết lập KPI riêng cho thiết bị
                $mavt = $_POST['mavt'] ?? '';
                $somay = $_POST['somay'] ?? '';
                $capdoStt = (int)($_POST['capdo_stt'] ?? 0);
                $kpiGioDuKien = (float)($_POST['kpi_gio_du_kien'] ?? 0);
                $ghiChu = $_POST['ghi_chu'] ?? '';
                
                // Tìm thiết bị
                $thietbi = $this->thietbiModel->findByMaVtAndSoMay($mavt, $somay);
                if (!$thietbi) {
                    echo json_encode(['success' => false, 'message' => 'Thiết bị không tồn tại']);
                    return;
                }
                
                // Tạo hoặc update KPI
                $result = $this->kpiModel->createOrUpdate([
                    'thietbi_stt' => (int)$thietbi['stt'],
                    'capdo_stt' => $capdoStt,
                    'kpi_gio_du_kien' => $kpiGioDuKien,
                    'ghi_chu' => $ghiChu
                ]);
                
                echo json_encode($result);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
*/

-- ========================================
-- ROLLBACK (nếu cần)
-- ========================================

/*
-- Khôi phục bảng cũ từ backup
DROP TABLE IF EXISTS thietbi_capdo_kpi_iso;
RENAME TABLE thietbi_capdo_kpi_iso_backup_20260224 TO thietbi_capdo_kpi_iso;

-- Verify
SELECT COUNT(*) AS restored_records FROM thietbi_capdo_kpi_iso;
*/

-- ========================================
-- HƯỚNG DẪN THỰC THI
-- ========================================

/*
BƯỚC 1: Kiểm tra trước khi migrate
--------------------------------------
- Chạy các SELECT ở Phần 1-4 để xem trạng thái hiện tại
- Kiểm tra các thiết bị không migrate được (nếu có)
- Đảm bảo bảng thietbi_iso đã có đầy đủ thiết bị

BƯỚC 2: Thêm Foreign Key Constraints (OPTIONAL)
--------------------------------------
Nếu muốn có FK để đảm bảo referential integrity:
- Uncomment 2 dòng PREPARE/EXECUTE trong Phần 2
- Điều này sẽ thêm FK constraints sau khi tạo bảng
- Nếu không cần FK, có thể bỏ qua bước này

BƯỚC 3: Thực thi migration
--------------------------------------
- Uncomment các dòng trong Phần 5
- Chạy từng bước một:
  + Bước 5.1: Backup
  + Bước 5.2: Drop old
  + Bước 5.3: Rename
  + Bước 5.4: Verify

BƯỚC 3: Cập nhật code PHP
--------------------------------------
- Cập nhật models/ThietBiCapDoKPI.php theo hướng dẫn trên
- Cập nhật models/ThietBi.php thêm findByMaVtAndSoMay()
- Cập nhật controllers/CongViecSuaChuaController.php

BƯỚC 4: Test chức năng
--------------------------------------
- Test tạo KPI mới cho thiết bị
- Test nhập công việc với KPI riêng
- Test báo cáo hiệu suất

BƯỚC 5: Nếu có vấn đề
--------------------------------------
- Chạy ROLLBACK script ở trên
- Kiểm tra lại logic
- Debug và thử lại

LỢI ÍCH SAU KHI MIGRATE:
--------------------------------------
✅ Database chuẩn hóa (3NF)
✅ Referential Integrity với FK
✅ Dễ bảo trì khi thiết bị thay đổi
✅ Tiết kiệm 96 bytes/record
✅ Query JOIN mạnh mẽ hơn
✅ Đảm bảo thiết bị phải tồn tại trước khi set KPI

VÍ DỤ SỬ DỤNG SAU KHI MIGRATE:
--------------------------------------

-- Thiết lập KPI riêng cho thiết bị
INSERT INTO thietbi_capdo_kpi_iso (thietbi_stt, capdo_stt, kpi_gio_du_kien, ghi_chu)
VALUES (
    (SELECT stt FROM thietbi_iso WHERE MAVT = 'TB001' AND SOMAY = 'M001'),
    1,  -- CAP1
    3.0,  -- 3 giờ thay vì 2 giờ chuẩn
    'Thiết bị phức tạp, cần thêm thời gian'
);

-- Lấy KPI của thiết bị
SELECT 
    tk.kpi_gio_du_kien,
    cd.ten_capdo,
    cd.kpi_gio_chuan,
    tb.MAVT,
    tb.SOMAY
FROM thietbi_capdo_kpi_iso tk
JOIN thietbi_iso tb ON tk.thietbi_stt = tb.stt
JOIN capdo_baocuong_iso cd ON tk.capdo_stt = cd.stt
WHERE tb.MAVT = 'TB001' AND tb.SOMAY = 'M001';

-- Xóa thiết bị (KPI tự động xóa theo CASCADE)
DELETE FROM thietbi_iso WHERE MAVT = 'TB001' AND SOMAY = 'M001';
-- => thietbi_capdo_kpi_iso của TB này cũng bị xóa

*/
