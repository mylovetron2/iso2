<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/config/database.php';
requireAuth();
requirePermission(PERMISSION_GIAOVIEC_KPI_VIEW);

$db = getDBConnection();
$action = $_GET['action'] ?? 'index';

function ensureGiaoviecKpiColumns(PDO $db): void {
    $required = [
        'kpi_baoduong_stt' => "ALTER TABLE giaoviec_kpi ADD COLUMN `kpi_baoduong_stt` INT(11) NULL DEFAULT NULL AFTER `hoso`",
        'loai_congviec' => "ALTER TABLE giaoviec_kpi ADD COLUMN `loai_congviec` ENUM('kiem_tra','bd_cap_1','bd_cap_2','bd_cap_3','hieu_chuan') NULL DEFAULT NULL AFTER `kpi_baoduong_stt`",
        'dinh_muc_gio_thu_cong' => "ALTER TABLE giaoviec_kpi ADD COLUMN `dinh_muc_gio_thu_cong` DECIMAL(8,2) NULL DEFAULT NULL AFTER `loai_congviec`",
        'ghi_chu' => "ALTER TABLE giaoviec_kpi ADD COLUMN `ghi_chu` TEXT NULL DEFAULT NULL AFTER `mo_ta`",
        'ngay_thuc_hien_ket_thuc' => "ALTER TABLE giaoviec_kpi ADD COLUMN `ngay_thuc_hien_ket_thuc` DATETIME NULL DEFAULT NULL AFTER `gio_ket_thuc`",
    ];

    foreach ($required as $col => $sql) {
        try {
            $check = $db->prepare("SHOW COLUMNS FROM giaoviec_kpi LIKE :col");
            $check->execute([':col' => $col]);
            if ($check->rowCount() > 0) {
                continue;
            }
            $db->exec($sql);
        } catch (Throwable $e) {
            error_log('ensureGiaoviecKpiColumns failed for ' . $col . ': ' . $e->getMessage());
        }
    }

    $column = 'dinh_muc_gio_hien_tai';
    try {
        $check = $db->prepare("
            SELECT 1
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'giaoviec_kpi'
              AND COLUMN_NAME = :col
            LIMIT 1
        ");
        $check->execute([':col' => $column]);
        if ($check->fetchColumn() === false) {
            $db->exec("ALTER TABLE giaoviec_kpi ADD COLUMN `dinh_muc_gio_hien_tai` DECIMAL(8,2) NULL DEFAULT NULL");
            $check->execute([':col' => $column]);
            if ($check->fetchColumn() === false) {
                throw new RuntimeException('Column was not created');
            }
        }
    } catch (Throwable $e) {
        error_log('ensureGiaoviecKpiColumns failed for ' . $column . ': ' . $e->getMessage());
        throw new RuntimeException('Không thể tạo cột dinh_muc_gio_hien_tai trong bảng giaoviec_kpi.', 0, $e);
    }
}

function calculateBusinessScheduleHours(?string $startDate, ?string $startTime, ?string $endDate, ?string $endTime): ?float {
    if (!$startDate || !$startTime || !$endDate || !$endTime) {
        return null;
    }

    $startValue = $startDate . ' ' . $startTime;
    $endValue = $endDate . ' ' . $endTime;
    $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $startValue);
    $end = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $endValue);
    if (!$start || !$end
        || $start->format('Y-m-d H:i') !== $startValue
        || $end->format('Y-m-d H:i') !== $endValue
        || $end <= $start) {
        return null;
    }

    $hours = 0.0;
    $day = $start->setTime(0, 0);
    $lastDay = $end->setTime(0, 0);
    while ($day <= $lastDay) {
        if ((int)$day->format('N') < 6) {
            $dayStart = $day > $start ? $day : $start;
            $nextDay = $day->modify('+1 day');
            $dayEnd = $nextDay < $end ? $nextDay : $end;
            if ($dayEnd > $dayStart) {
                $hours += min(6.5, ($dayEnd->getTimestamp() - $dayStart->getTimestamp()) / 3600);
            }
        }
        $day = $day->modify('+1 day');
    }

    return $hours;
}

function ensureGiaoviecKpiNguoiColumns(PDO $db): void {
    $required = [
        'user_stt' => "ALTER TABLE giaoviec_kpi_nguoi ADD COLUMN `user_stt` INT(11) NULL DEFAULT NULL AFTER `giaoviec_stt`",
    ];

    foreach ($required as $col => $sql) {
        try {
            $check = $db->prepare("SHOW COLUMNS FROM giaoviec_kpi_nguoi LIKE :col");
            $check->execute([':col' => $col]);
            if ($check->rowCount() > 0) {
                continue;
            }
            $db->exec($sql);
        } catch (Throwable $e) {
            error_log('ensureGiaoviecKpiNguoiColumns failed for ' . $col . ': ' . $e->getMessage());
        }
    }

    try {
        $idx = $db->query("SHOW INDEX FROM giaoviec_kpi_nguoi WHERE Key_name = 'idx_user_stt'");
        if ($idx->rowCount() === 0) {
            $db->exec("ALTER TABLE giaoviec_kpi_nguoi ADD INDEX `idx_user_stt` (`user_stt`)");
        }
    } catch (Throwable $e) {
        error_log('ensureGiaoviecKpiNguoiIndex failed: ' . $e->getMessage());
    }
}

function resolveUserSttByDisplayName(PDO $db, string $name): ?int {
    $value = trim((string)$name);
    if ($value === '') {
        return null;
    }

    try {
        $st = $db->prepare("SELECT stt FROM users WHERE LOWER(TRIM(COALESCE(hoten, ''))) = LOWER(:v) OR LOWER(TRIM(COALESCE(username, ''))) = LOWER(:v) LIMIT 1");
        $st->execute([':v' => $value]);
        $id = $st->fetchColumn();
        return $id !== false && $id !== null ? (int)$id : null;
    } catch (Throwable $e) {
        error_log('resolveUserSttByDisplayName failed: ' . $e->getMessage());
        return null;
    }
}

try {
    ensureGiaoviecKpiColumns($db);
    ensureGiaoviecKpiNguoiColumns($db);
} catch (Throwable $e) {
    error_log('Giaoviec KPI migration check failed: ' . $e->getMessage());
    $message = 'Cơ sở dữ liệu chưa sẵn sàng cho Định mức giờ hiện tại. Hãy cấp quyền ALTER TABLE hoặc chạy: ALTER TABLE giaoviec_kpi ADD COLUMN dinh_muc_gio_hien_tai DECIMAL(8,2) NULL DEFAULT NULL;';
    if (str_starts_with((string)$action, 'api_')) {
        jsonOut(['ok' => false, 'error' => $message], 500);
    }
    http_response_code(500);
    echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    exit;
}

$currentUser = $_SESSION['username'] ?? 'unknown';
$canCreate = hasPermission(PERMISSION_GIAOVIEC_KPI_CREATE);
$canEdit = hasPermission(PERMISSION_GIAOVIEC_KPI_EDIT);
$canDelete = hasPermission(PERMISSION_GIAOVIEC_KPI_DELETE);
$loaiCongViecLabels = [
    'kiem_tra' => 'Kiểm tra',
    'bd_cap_1' => 'BD cấp 1',
    'bd_cap_2' => 'BD cấp 2',
    'bd_cap_3' => 'BD cấp 3',
    'hieu_chuan' => 'Hiệu chuẩn',
];

$kpiThietBiList = $db->query("SELECT id, ten_thiet_bi, kiem_tra_so_gio, bd_cap_1_so_gio, bd_cap_2_so_gio, bd_cap_3_so_gio, hieu_chuan_so_gio FROM kpi_baoduong_thietbi_iso ORDER BY ten_thiet_bi ASC")->fetchAll(PDO::FETCH_ASSOC);
$kpiHourPreviewMap = [];
foreach ($kpiThietBiList as $kpiRow) {
    $kpiHourPreviewMap[(int)$kpiRow['id']] = [
        'kiem_tra' => $kpiRow['kiem_tra_so_gio'] !== null ? (float)$kpiRow['kiem_tra_so_gio'] : null,
        'bd_cap_1' => $kpiRow['bd_cap_1_so_gio'] !== null ? (float)$kpiRow['bd_cap_1_so_gio'] : null,
        'bd_cap_2' => $kpiRow['bd_cap_2_so_gio'] !== null ? (float)$kpiRow['bd_cap_2_so_gio'] : null,
        'bd_cap_3' => $kpiRow['bd_cap_3_so_gio'] !== null ? (float)$kpiRow['bd_cap_3_so_gio'] : null,
        'hieu_chuan' => $kpiRow['hieu_chuan_so_gio'] !== null ? (float)$kpiRow['hieu_chuan_so_gio'] : null,
    ];
}

// ============================================================
// Helpers
// ============================================================
function jsonOut($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function computeStatus(array $row, int $nguoiCount): string {
    if ($nguoiCount === 0) return 'chua_giao';
    if ($row['trang_thai'] === 'hoan_thanh' || $row['trang_thai'] === 'huy') return $row['trang_thai'];
    if (empty($row['ngay_ket_thuc'])) return 'dang_lam';

    if (!empty($row['gio_ket_thuc'])) {
      $now = new DateTime();
      $end = new DateTime($row['ngay_ket_thuc'] . ' ' . $row['gio_ket_thuc']);
      $seconds = $end->getTimestamp() - $now->getTimestamp();
      if ($seconds < 0) return 'qua_han';
      if ($seconds < 86400) return 'den_han';
      if ($seconds <= 3 * 86400) return 'dang_lam';
      return 'dang_lam';
    }

    $today = new DateTime(date('Y-m-d'));
    $end = new DateTime($row['ngay_ket_thuc']);
    $diff = (int)$today->diff($end)->format('%r%a');
    if ($diff < 0) return 'qua_han';
    if ($diff === 0) return 'den_han';
    if ($diff <= 3) return 'dang_lam';
    return 'dang_lam';
}

// Kết quả KPI của công việc đã hoàn thành: ngày kết thúc thực tế so với hạn hoàn thành.
// Trả về true (đạt), false (không đạt) hoặc null (chưa đủ dữ liệu).
function computeKpiResult(array $row): ?bool {
    if (($row['trang_thai'] ?? '') !== 'hoan_thanh' || empty($row['ngay_ket_thuc'])) return null;

    $actual = !empty($row['ngay_thuc_hien_ket_thuc']) ? (string)$row['ngay_thuc_hien_ket_thuc'] : (string)($row['ngay_kt_thuc_te'] ?? '');
    if ($actual === '') return null;

    // Dữ liệu cũ chỉ có ngày thì so sánh theo ngày
    $actualHasTime = strlen($actual) > 10;
    if (!empty($row['gio_ket_thuc']) && $actualHasTime) {
        $deadline = new DateTime($row['ngay_ket_thuc'] . ' ' . $row['gio_ket_thuc']);
        return new DateTime($actual) <= $deadline;
    }
    return substr($actual, 0, 10) <= $row['ngay_ket_thuc'];
}

function statusLabel(string $s): array {
    // [label, css]
    return [
        'chua_giao'   => ['Cần giao',     'bg-gray-200 text-gray-800'],
        'den_han'     => ['Đến hạn',      'bg-orange-100 text-orange-800'],
        'qua_han'     => ['Quá hạn',      'bg-red-100 text-red-700'],
        'dang_lam'    => ['Đang thực hiện','bg-blue-100 text-blue-800'],
        'hoan_thanh'  => ['Hoàn thành',   'bg-green-100 text-green-800'],
        'huy'         => ['Đã hủy',       'bg-gray-100 text-gray-500'],
    ][$s] ?? [$s, 'bg-gray-100'];
}

// ============================================================
// AJAX / API actions
// ============================================================
try {
    switch ($action) {

        // --------- Danh sách người thực hiện (từ bảng users) ---------
        case 'api_users':
            $st = $db->prepare("
                SELECT DISTINCT stt, TRIM(hoten) AS display_name
                FROM users
                WHERE TRIM(COALESCE(hoten, '')) != ''
                ORDER BY display_name ASC
            ");
            $st->execute();
            $rows = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $name = trim((string)($row['display_name'] ?? ''));
                if ($name === '') continue;
                $rows[] = [
                    'stt' => (int)($row['stt'] ?? 0),
                    'display_name' => $name,
                    'label' => $name,
                ];
            }
            jsonOut(['ok' => true, 'data' => $rows]);

        // --------- Gợi ý phiếu/hồ sơ từ hososcbd_iso ---------
        case 'api_hososcbd':
            $q = trim($_GET['q'] ?? '');
            $sql = "SELECT stt, phieu, mavt, somay, hoso FROM hososcbd_iso WHERE 1=1";
            $params = [];
            if ($q !== '') {
                $sql .= " AND (phieu LIKE :q OR mavt LIKE :q OR somay LIKE :q OR hoso LIKE :q)";
                $params[':q'] = "%$q%";
            }
            $sql .= " ORDER BY stt DESC LIMIT 100";
            $st = $db->prepare($sql);
            $st->execute($params);
            jsonOut(['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)]);

        // --------- Tự chọn Thiết bị KPI theo máy / mã VT của hồ sơ ---------
        case 'api_kpi_by_hoso':
            $stt = isset($_GET['stt']) ? (int)$_GET['stt'] : 0;
            if ($stt <= 0) {
                jsonOut(['ok' => false, 'error' => 'Thiếu stt hồ sơ'], 400);
            }
            $sql = "
                SELECT h.stt, h.mavt, h.somay, h.hoso,
                       t.stt AS thietbi_stt,
                       l.kpi_baoduong_stt,
                       k.ten_thiet_bi
                FROM hososcbd_iso h
                LEFT JOIN thietbi_iso t ON t.mavt = h.mavt AND t.somay = h.somay
                LEFT JOIN thietbi_kpi_baoduong_iso l ON l.thietbi_stt = t.stt
                LEFT JOIN kpi_baoduong_thietbi_iso k ON k.id = l.kpi_baoduong_stt
                WHERE h.stt = :stt
                LIMIT 1
            ";
            $st = $db->prepare($sql);
            $st->execute([':stt' => $stt]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            jsonOut(['ok' => true, 'data' => $row ?: null]);

        // --------- Lấy danh sách công việc (root + con) ---------
        case 'api_list':
            $st = $db->query("
                SELECT g.*, h.mavt AS hoso_mavt, h.nhomsc AS nhomsc,
                  k.ten_thiet_bi AS ten_thiet_bi,
                  (SELECT COALESCE(SUM(tong_gio),0) FROM giaoviec_kpi_thuchien WHERE giaoviec_stt = g.stt) AS gio_da_lam,
                  (SELECT MIN(ngay_lam) FROM giaoviec_kpi_thuchien WHERE giaoviec_stt = g.stt) AS ngay_bd_thuc_te,
                  (SELECT MAX(ngay_lam) FROM giaoviec_kpi_thuchien WHERE giaoviec_stt = g.stt) AS ngay_kt_thuc_te,
                  (SELECT GROUP_CONCAT(CONCAT(n.user_stt,'|',COALESCE(NULLIF(TRIM(u.hoten), ''), u.username),'|',n.vai_tro) SEPARATOR ';;')
                   FROM giaoviec_kpi_nguoi n
                   INNER JOIN users u ON u.stt = n.user_stt
                   WHERE n.giaoviec_stt = g.stt) AS nguoi_raw,
                    (SELECT COUNT(*) FROM giaoviec_kpi_nguoi WHERE giaoviec_stt = g.stt) AS nguoi_count
                FROM giaoviec_kpi g
                LEFT JOIN hososcbd_iso h ON h.stt = g.hososcbd_stt
                LEFT JOIN kpi_baoduong_thietbi_iso k ON k.id = g.kpi_baoduong_stt
                ORDER BY COALESCE(g.parent_stt, g.stt) DESC, g.parent_stt IS NOT NULL, g.stt ASC
            ");
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $r['trang_thai_hien_thi'] = computeStatus($r, (int)$r['nguoi_count']);
                $r['kpi_dat'] = computeKpiResult($r);
                $r['ngay_ket_thuc_thuc_te'] = !empty($r['ngay_thuc_hien_ket_thuc']) ? $r['ngay_thuc_hien_ket_thuc'] : ($r['ngay_kt_thuc_te'] ?? null);
                // Tính tiến độ động theo giờ đã làm / định mức giờ hiệu lực
                $hoursDone = (float)($r['gio_da_lam'] ?? 0);
                $manual = $r['dinh_muc_gio_thu_cong'] ?? null;
                $target = ($manual !== null && $manual !== '' && (float)$manual > 0) ? (float)$manual : 0.0;
                if ($target <= 0 && !empty($r['kpi_baoduong_stt']) && !empty($r['loai_congviec'])) {
                    $kpiId = (int)$r['kpi_baoduong_stt'];
                    $loai = (string)$r['loai_congviec'];
                    if (isset($kpiHourPreviewMap[$kpiId][$loai]) && $kpiHourPreviewMap[$kpiId][$loai] !== null && (float)$kpiHourPreviewMap[$kpiId][$loai] > 0) {
                        $target = (float)$kpiHourPreviewMap[$kpiId][$loai];
                    }
                }
                if (($r['trang_thai'] ?? '') === 'hoan_thanh') {
                    $r['tien_do'] = 100;
                } elseif ($target > 0) {
                    $r['tien_do'] = max(0, min(100, (int)round(($hoursDone / $target) * 100)));
                } else {
                    $r['tien_do'] = (int)($r['tien_do'] ?? 0);
                }
                $r['dinh_muc_gio_hieu_luc'] = $target > 0 ? $target : null;
                $storedScheduleHours = $r['dinh_muc_gio_hien_tai'] ?? null;
                if ($storedScheduleHours === null || $storedScheduleHours === '') {
                    $storedScheduleHours = calculateBusinessScheduleHours(
                        $r['ngay_bat_dau'] ?? null,
                        isset($r['gio_bat_dau']) ? substr((string)$r['gio_bat_dau'], 0, 5) : null,
                        $r['ngay_ket_thuc'] ?? null,
                        isset($r['gio_ket_thuc']) ? substr((string)$r['gio_ket_thuc'], 0, 5) : null
                    );
                }
                $r['dinh_muc_gio_hien_tai'] = $storedScheduleHours;
                $r['nguoi_list'] = [];
                if (!empty($r['nguoi_raw'])) {
                    foreach (explode(';;', $r['nguoi_raw']) as $p) {
                        [$userStt, $hoten, $vt] = array_pad(explode('|', $p, 3), 3, '');
                        $r['nguoi_list'][] = ['user_stt' => (int)$userStt, 'hoten' => $hoten, 'vai_tro' => $vt];
                    }
                }
                unset($r['nguoi_raw']);
            }
            jsonOut(['ok' => true, 'data' => $rows]);

        // --------- Lưu công việc (create/update) ---------
        case 'api_save':
            $in = json_decode(file_get_contents('php://input'), true) ?: [];
            $stt          = isset($in['stt']) ? (int)$in['stt'] : 0;
          if ($stt > 0 && !$canEdit) jsonOut(['ok' => false, 'error' => 'Bạn không có quyền sửa công việc KPI'], 403);
          if ($stt === 0 && !$canCreate) jsonOut(['ok' => false, 'error' => 'Bạn không có quyền tạo công việc KPI'], 403);
            $parent_stt   = !empty($in['parent_stt']) ? (int)$in['parent_stt'] : null;
            $hososcbd_stt = !empty($in['hososcbd_stt']) ? (int)$in['hososcbd_stt'] : null;
            $phieu        = trim($in['phieu'] ?? '');
            $somay        = trim($in['somay'] ?? '');
            $hoso         = trim($in['hoso'] ?? '');
            $kpiStt       = !empty($in['kpi_baoduong_stt']) ? (int)$in['kpi_baoduong_stt'] : null;
            $loaiCongViec = trim((string)($in['loai_congviec'] ?? ''));
            $dinhMucGio   = $in['dinh_muc_gio_thu_cong'] ?? null;
            if ($dinhMucGio !== null && $dinhMucGio !== '') {
                if (!is_numeric((string)$dinhMucGio)) {
                    jsonOut(['ok' => false, 'error' => 'Định mức giờ phải là số hợp lệ'], 400);
                }
                $dinhMucGio = (float)str_replace(',', '.', (string)$dinhMucGio);
            } else {
                $dinhMucGio = null;
            }
            if ($loaiCongViec !== '' && !in_array($loaiCongViec, ['kiem_tra','bd_cap_1','bd_cap_2','bd_cap_3','hieu_chuan'], true)) {
                jsonOut(['ok' => false, 'error' => 'Loại công việc không hợp lệ'], 400);
            }
            $ten          = trim($in['ten_cong_viec'] ?? '');
            $mo_ta        = trim($in['mo_ta'] ?? '');
            $ghiChu       = trim((string)($in['ghi_chu'] ?? ''));
            $currentHourInput = $in['dinh_muc_gio_hien_tai'] ?? null;
            if ($currentHourInput !== null && $currentHourInput !== '') {
                if (!is_numeric((string)$currentHourInput)) {
                    jsonOut(['ok' => false, 'error' => 'Định mức giờ hiện tại phải là số hợp lệ'], 400);
                }
                $currentHourInput = (float)str_replace(',', '.', (string)$currentHourInput);
                if (!is_finite($currentHourInput) || $currentHourInput < 0 || $currentHourInput > 999999.99) {
                    jsonOut(['ok' => false, 'error' => 'Định mức giờ hiện tại phải từ 0 đến 999999.99'], 400);
                }
            } else {
                $currentHourInput = null;
            }
            $ngay_bd      = $in['ngay_bat_dau'] ?: null;
            $gio_bd       = $in['gio_bat_dau']  ?: null;
            $ngay_kt      = $in['ngay_ket_thuc']?: null;
            $gio_kt       = $in['gio_ket_thuc'] ?: null;
            $soNgayInput  = $in['so_ngay'] ?? 0;
            if (filter_var($soNgayInput, FILTER_VALIDATE_INT) === false || (int)$soNgayInput < 0) {
                jsonOut(['ok' => false, 'error' => 'Số ngày phải là số nguyên không âm'], 400);
            }
            $so_ngay = (int)$soNgayInput;
            if ($ngay_bd !== null && $ngay_kt !== null) {
                $startDate = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$ngay_bd);
                $endDate = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$ngay_kt);
                if (!$startDate || !$endDate
                    || $startDate->format('Y-m-d') !== $ngay_bd
                    || $endDate->format('Y-m-d') !== $ngay_kt) {
                    jsonOut(['ok' => false, 'error' => 'Ngày bắt đầu hoặc hạn hoàn thành không hợp lệ'], 400);
                }
                if ($endDate < $startDate) {
                    jsonOut(['ok' => false, 'error' => 'Hạn hoàn thành không được trước ngày bắt đầu'], 400);
                }
            }
            if ($ngay_bd !== null && $gio_bd !== null && $ngay_kt !== null && $gio_kt !== null) {
                $startDateTimeValue = $ngay_bd . ' ' . $gio_bd;
                $endDateTimeValue = $ngay_kt . ' ' . $gio_kt;
                $startDateTime = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $startDateTimeValue);
                $endDateTime = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $endDateTimeValue);
                if (!$startDateTime || !$endDateTime
                    || $startDateTime->format('Y-m-d H:i') !== $startDateTimeValue
                    || $endDateTime->format('Y-m-d H:i') !== $endDateTimeValue) {
                    jsonOut(['ok' => false, 'error' => 'Giờ bắt đầu hoặc giờ kết thúc không hợp lệ'], 400);
                }
                if ($endDateTime <= $startDateTime) {
                    jsonOut(['ok' => false, 'error' => 'Giờ kết thúc phải sau giờ bắt đầu'], 400);
                }
            }
            $tien_do      = (int)($in['tien_do'] ?? 0);
            $trang_thai   = $in['trang_thai'] ?? 'chua_giao';

            if ($ten === '') jsonOut(['ok' => false, 'error' => 'Thiếu tên công việc'], 400);

            if ($stt > 0) {
                $sql = "UPDATE giaoviec_kpi SET parent_stt=:p, hososcbd_stt=:hs, phieu=:ph, somay=:sm, hoso=:ho,
                        kpi_baoduong_stt=:kpi, loai_congviec=:lc, dinh_muc_gio_thu_cong=:dm, dinh_muc_gio_hien_tai=:dghh,
                        ten_cong_viec=:t, mo_ta=:mt, ghi_chu=:gc, ngay_bat_dau=:nbd, gio_bat_dau=:gbd, so_ngay=:sn,
                        ngay_ket_thuc=:nkt, gio_ket_thuc=:gkt, tien_do=:td, trang_thai=:tt WHERE stt=:s";
                $st = $db->prepare($sql);
                $st->execute([':p'=>$parent_stt, ':hs'=>$hososcbd_stt, ':ph'=>$phieu, ':sm'=>$somay, ':ho'=>$hoso,
                    ':kpi'=>$kpiStt, ':lc'=>$loaiCongViec !== '' ? $loaiCongViec : null, ':dm'=>$dinhMucGio, ':dghh'=>$currentHourInput,
                    ':t'=>$ten, ':mt'=>$mo_ta, ':gc'=>$ghiChu !== '' ? $ghiChu : null, ':nbd'=>$ngay_bd, ':gbd'=>$gio_bd, ':sn'=>$so_ngay,
                    ':nkt'=>$ngay_kt, ':gkt'=>$gio_kt, ':td'=>$tien_do, ':tt'=>$trang_thai, ':s'=>$stt]);
                jsonOut(['ok'=>true, 'stt'=>$stt]);
            } else {
                $sql = "INSERT INTO giaoviec_kpi (parent_stt, hososcbd_stt, phieu, somay, hoso, kpi_baoduong_stt, loai_congviec, dinh_muc_gio_thu_cong, dinh_muc_gio_hien_tai,
                        ten_cong_viec, mo_ta, ghi_chu, ngay_bat_dau, gio_bat_dau, so_ngay, ngay_ket_thuc, gio_ket_thuc, tien_do, trang_thai, nguoi_giao)
                        VALUES (:p,:hs,:ph,:sm,:ho,:kpi,:lc,:dm,:dghh,:t,:mt,:gc,:nbd,:gbd,:sn,:nkt,:gkt,:td,:tt,:ng)";
                $st = $db->prepare($sql);
                $st->execute([':p'=>$parent_stt, ':hs'=>$hososcbd_stt, ':ph'=>$phieu, ':sm'=>$somay, ':ho'=>$hoso,
                    ':kpi'=>$kpiStt, ':lc'=>$loaiCongViec !== '' ? $loaiCongViec : null, ':dm'=>$dinhMucGio, ':dghh'=>$currentHourInput,
                    ':t'=>$ten, ':mt'=>$mo_ta, ':gc'=>$ghiChu !== '' ? $ghiChu : null, ':nbd'=>$ngay_bd, ':gbd'=>$gio_bd, ':sn'=>$so_ngay,
                    ':nkt'=>$ngay_kt, ':gkt'=>$gio_kt, ':td'=>$tien_do, ':tt'=>$trang_thai, ':ng'=>$currentUser]);
                jsonOut(['ok'=>true, 'stt'=>(int)$db->lastInsertId()]);
            }

        // --------- Xoá công việc (kèm subtasks + người) ---------
        case 'api_delete':
          if (!$canDelete) jsonOut(['ok'=>false,'error'=>'Bạn không có quyền xóa công việc KPI'], 403);
            $stt = (int)($_POST['stt'] ?? $_GET['stt'] ?? 0);
            if (!$stt) jsonOut(['ok'=>false,'error'=>'Thiếu stt'], 400);
            $db->beginTransaction();
            $ids = [$stt];
            $childSt = $db->prepare("SELECT stt FROM giaoviec_kpi WHERE parent_stt = ?");
            $childSt->execute([$stt]);
            foreach ($childSt->fetchAll(PDO::FETCH_COLUMN) as $c) $ids[] = (int)$c;
            $in = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("DELETE FROM giaoviec_kpi_nguoi WHERE giaoviec_stt IN ($in)")->execute($ids);
            $db->prepare("DELETE FROM giaoviec_kpi WHERE stt IN ($in)")->execute($ids);
            $db->commit();
            jsonOut(['ok'=>true]);

        // --------- Lưu người thực hiện (thay thế toàn bộ) ---------
        case 'api_save_nguoi':
          if (!$canEdit) jsonOut(['ok'=>false,'error'=>'Bạn không có quyền gán người thực hiện'], 403);
            $in = json_decode(file_get_contents('php://input'), true) ?: [];
            $gvStt = (int)($in['giaoviec_stt'] ?? 0);
            $chinh = (int)($in['chinh'] ?? 0);
            $phu   = array_values(array_unique(array_filter(array_map('intval', (array)($in['phu'] ?? [])))));
            if (!$gvStt) jsonOut(['ok'=>false,'error'=>'Thiếu công việc'], 400);
            $userIds = array_values(array_unique(array_filter(array_merge($chinh > 0 ? [$chinh] : [], $phu))));
            if ($chinh <= 0) jsonOut(['ok'=>false,'error'=>'Vui lòng chọn người thực hiện chính'], 400);
            if ($userIds) {
              $userIn = implode(',', array_fill(0, count($userIds), '?'));
              $checkUsers = $db->prepare("SELECT stt FROM users WHERE stt IN ($userIn)");
              $checkUsers->execute($userIds);
              $validUsers = array_map('intval', $checkUsers->fetchAll(PDO::FETCH_COLUMN));
              if (count($validUsers) !== count($userIds)) {
                jsonOut(['ok'=>false,'error'=>'Danh sách người thực hiện không hợp lệ'], 400);
              }
            }
            $db->beginTransaction();
            $db->prepare("DELETE FROM giaoviec_kpi_nguoi WHERE giaoviec_stt = ?")->execute([$gvStt]);
            $ins = $db->prepare("INSERT INTO giaoviec_kpi_nguoi (giaoviec_stt, user_stt, vai_tro) VALUES (?,?,?)");
            $ins->execute([$gvStt, $chinh, 'chinh']);
            foreach ($phu as $userStt) {
              if ($userStt !== $chinh) {
                $ins->execute([$gvStt, $userStt, 'phu']);
              }
            }
            // Nếu có người thực hiện và đang là chua_giao thì chuyển thành dang_lam
            if ($chinh > 0) {
                $db->prepare("UPDATE giaoviec_kpi SET trang_thai='dang_lam' WHERE stt=? AND trang_thai='chua_giao'")->execute([$gvStt]);
            }
            $db->commit();
            jsonOut(['ok'=>true]);
    }
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    jsonOut(['ok'=>false, 'error'=>$e->getMessage()], 500);
}

// ============================================================
// Render trang HTML
// ============================================================
$title = 'Giao việc & KPI';
require_once __DIR__ . '/views/layouts/header.php';
?>
<style>
  .task-page {
    width: 100%;
    min-width: 0;
    overflow-x: hidden;
  }
  .task-table-wrap {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .task-table {
    min-width: 1300px;
  }
  .task-table tr.row-parent > td {
    background-color: #eff6ff;
    border-top: 2px solid #93c5fd;
  }
  .task-table tr.row-parent:hover > td { background-color: #dbeafe; }
  .task-table tr.row-child > td {
    background-color: #ffffff;
    border-top: 1px dashed #e5e7eb;
    font-size: 0.8125rem;
  }
  .task-table tr.row-child > td:first-child {
    padding-left: 2rem;
  }
  .task-table tr.row-child:hover > td { background-color: #f0fdf4; }
  .task-table tr.row-child:last-of-type > td { border-bottom: 0; }
  .task-toggle {
    display: inline-flex; align-items: center; justify-content: center;
    width: 1.25rem; height: 1.25rem; margin-right: 0.25rem;
    border-radius: 0.25rem; color: #1d4ed8; background: #dbeafe; cursor: pointer;
  }
  .task-toggle i { transition: transform .15s; font-size: 0.7rem; }
  .task-toggle.collapsed i { transform: rotate(-90deg); }
  .child-name { position: relative; padding-left: 1.5rem; display: inline-block; }
  .child-name::before {
    content: ""; position: absolute; left: 0.25rem; top: -0.5rem;
    width: 0.9rem; height: 1.1rem;
    border-left: 2px solid #16a34a; border-bottom: 2px solid #16a34a;
    border-bottom-left-radius: 4px;
  }
  @media (max-width: 640px) {
    .task-page {
      padding: 0.75rem;
    }
    .task-table {
      min-width: 1140px;
    }
  }
</style>
<div class="task-page w-full min-w-0">
  <div class="bg-white rounded-lg shadow p-4 mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="text-xl font-bold text-gray-800"><i class="fas fa-tasks text-blue-600 mr-2"></i>Giao việc &amp; KPI</h1>
      <p class="text-sm text-gray-500">Trưởng nhóm giao việc theo phiếu / máy — theo dõi tiến độ, người thực hiện.</p>
    </div>
    <div class="flex gap-2 flex-wrap">
      <select id="filterNhom" class="border rounded px-3 py-2 text-sm">
        <option value="">— Nhóm: Tất cả —</option>
      </select>
      <input type="text" id="filterThietBi" class="border rounded px-3 py-2 text-sm" placeholder="Tìm tên thiết bị / hồ sơ / số máy...">
      <select id="filterNguoiChinh" class="border rounded px-3 py-2 text-sm">
        <option value="">— Người thực hiện chính: Tất cả —</option>
      </select>
      <select id="filterStatus" class="border rounded px-3 py-2 text-sm">
        <option value="">— Tình trạng: Tất cả —</option>
        <option value="chua_giao">Cần giao</option>
        <option value="den_han">Đến hạn</option>
        <option value="qua_han">Quá hạn</option>
        <option value="dang_lam">Đang thực hiện</option>
        <option value="hoan_thanh">Hoàn thành</option>
      </select>
      <?php if ($canCreate): ?>
      <a href="giaoviec_kpi_detail.php" id="btnAdd" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
        <i class="fas fa-plus mr-1"></i> Thêm công việc
      </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="task-table-wrap bg-white rounded-lg shadow">
    <table class="task-table w-full text-sm">
      <thead class="bg-gray-50 text-gray-700">
        <tr>
          <th class="px-3 py-2 text-left min-w-[240px] w-[20.8%]">Tên công việc</th>
          <th class="px-3 py-2 text-left">Dự án (Hồ sơ)</th>
          <th class="px-3 py-2 text-left">Nhóm</th>
          <th class="px-3 py-2 text-left">Thời điểm bắt đầu</th>
          <th class="px-3 py-2 text-left">Hạn hoàn thành</th>
          <th class="px-3 py-2 text-left">Tình trạng</th>
          <th class="px-3 py-2 text-left">Tiến độ</th>
          <th class="px-3 py-2 text-left">Ghi chú</th>
          <th class="px-3 py-2 text-left">Người thực hiện</th>
          <th class="px-3 py-2 text-left">Người giao</th>
          <th class="px-3 py-2 text-center w-32">Thao tác</th>
        </tr>
      </thead>
      <tbody id="tblBody">
        <tr><td colspan="11" class="p-6 text-center text-gray-400">Đang tải...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
const API = 'giaoviec_kpi.php';
const CAN_CREATE = <?= $canCreate ? 'true' : 'false' ?>;
const CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;
const CAN_DELETE = <?= $canDelete ? 'true' : 'false' ?>;
let ALL_TASKS = [];

const STATUS_LABEL = <?= json_encode([
  'chua_giao'=>'Cần giao', 'den_han'=>'Đến hạn',
  'qua_han'=>'Quá hạn', 'dang_lam'=>'Đang thực hiện', 'hoan_thanh'=>'Hoàn thành', 'huy'=>'Đã hủy'
], JSON_UNESCAPED_UNICODE) ?>;
const STATUS_CSS = {
  chua_giao:'bg-gray-200 text-gray-800',
  den_han:'bg-orange-100 text-orange-800', qua_han:'bg-red-100 text-red-700',
  dang_lam:'bg-blue-100 text-blue-800', hoan_thanh:'bg-green-100 text-green-800', huy:'bg-gray-100 text-gray-500'
};
const STATUS_TEXT_CSS = {
  chua_giao:'text-gray-800', den_han:'text-orange-800',
  qua_han:'text-red-700', dang_lam:'text-blue-800', hoan_thanh:'text-green-800', huy:'text-gray-500'
};

function esc(s){ return (s??'').toString().replace(/[&<>"']/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function fmtDate(d){ if(!d) return ''; return d.split('-').reverse().join('/'); }

async function loadTasks(){
  const r = await fetch(`${API}?action=api_list`).then(r=>r.json());
  ALL_TASKS = r.data || [];
  populateGvFilters();
  renderTable();
}

function populateGvFilters(){
  const nhomSel = document.getElementById('filterNhom');
  const tbInput = document.getElementById('filterThietBi');
  const nguoiSel = document.getElementById('filterNguoiChinh');
  if (!nhomSel || !tbInput || !nguoiSel) return;

  const nhomSet = new Set();
  const tbSet = new Set();
  const nguoiMap = new Map();
  for (const t of ALL_TASKS) {
    if (t.nhomsc) nhomSet.add(String(t.nhomsc).trim());
    if (t.ten_thiet_bi) tbSet.add(String(t.ten_thiet_bi).trim());
    (t.nguoi_list || []).forEach(n => {
      if (n.vai_tro === 'chinh' && n.user_stt) {
        nguoiMap.set(String(n.user_stt), n.hoten || '');
      }
    });
  }

  const rebuild = (sel, items, placeholder) => {
    const cur = sel.value;
    sel.innerHTML = `<option value="">${placeholder}</option>` +
      items.map(([v, label]) => `<option value="${esc(v)}">${esc(label)}</option>`).join('');
    if (cur && items.some(([v]) => v === cur)) sel.value = cur;
  };

  const nhomItems = [...nhomSet].filter(Boolean).sort().map(v => [v, v]);
  const nguoiItems = [...nguoiMap.entries()].sort((a,b) => (a[1]||'').localeCompare(b[1]||''));

  rebuild(nhomSel, nhomItems, '— Nhóm: Tất cả —');
  rebuild(nguoiSel, nguoiItems, '— Người thực hiện chính: Tất cả —');

  let dl = document.getElementById('dl_filter_thietbi');
  if (!dl) {
    dl = document.createElement('datalist');
    dl.id = 'dl_filter_thietbi';
    document.body.appendChild(dl);
    tbInput.setAttribute('list', 'dl_filter_thietbi');
  }
  dl.innerHTML = [...tbSet].filter(Boolean).sort()
    .map(v => `<option value="${esc(v)}"></option>`).join('');
}

function taskMatchesFilters(t, fNhom, fTb, fNguoi){
  if (fNhom && String(t.nhomsc || '').trim() !== fNhom) return false;
  if (fTb) {
    const hay = [t.ten_thiet_bi, t.hoso, t.somay, t.phieu, t.hoso_mavt]
      .map(v => String(v || '').toLowerCase()).join(' | ');
    const keywords = fTb.toLowerCase().split(/\s+/).filter(Boolean);
    if (!keywords.every(k => hay.includes(k))) return false;
  }
  if (fNguoi) {
    const chinh = (t.nguoi_list || []).find(n => n.vai_tro === 'chinh');
    if (!chinh || String(chinh.user_stt) !== fNguoi) return false;
  }
  return true;
}

const COLLAPSED_PARENTS = new Set();
function toggleChildren(stt){
  stt = Number(stt);
  if (COLLAPSED_PARENTS.has(stt)) COLLAPSED_PARENTS.delete(stt); else COLLAPSED_PARENTS.add(stt);
  renderTable();
}

function renderTable(){
  const filter = document.getElementById('filterStatus').value;
  const fNhom = document.getElementById('filterNhom').value;
  const fTb = document.getElementById('filterThietBi').value.trim();
  const fNguoi = document.getElementById('filterNguoiChinh').value;
  const roots = ALL_TASKS.filter(t => !t.parent_stt);
  const rows = [];
  let idx = 0;
  for (const t of roots) {
    if (filter && t.trang_thai_hien_thi !== filter) continue;
    const children = ALL_TASKS.filter(c => Number(c.parent_stt) === Number(t.stt));
    const rootMatch = taskMatchesFilters(t, fNhom, fTb, fNguoi);
    const childMatches = children.filter(c => taskMatchesFilters(c, fNhom, fTb, fNguoi));
    if (!rootMatch && childMatches.length === 0) continue;
    idx++;
    const collapsed = COLLAPSED_PARENTS.has(Number(t.stt));
    rows.push(renderRow(t, idx, 0, {childCount: children.length, collapsed}));
    if (!collapsed) childMatches.forEach((c, i) => rows.push(renderRow(c, idx+'.'+(i+1), 1)));
  }
  document.getElementById('tblBody').innerHTML = rows.length
    ? rows.join('')
    : `<tr><td colspan="11" class="p-6 text-center text-gray-400">Chưa có công việc.</td></tr>`;
}

function renderThucTeInfo(t){
  const bd = t.ngay_bd_thuc_te ? fmtDate(t.ngay_bd_thuc_te) : '';
  const ktRaw = String(t.ngay_ket_thuc_thuc_te || '');
  const ktTime = ktRaw.length > 10 ? ktRaw.slice(11, 16) : '';
  const kt = ktRaw ? fmtDate(ktRaw.slice(0, 10)) + (ktTime && ktTime !== '00:00' ? ' ' + ktTime : '') : '';
  let html = '';
  if (bd || kt) {
    html += `<div class="text-[11px] mt-1 flex items-center gap-1 flex-wrap">`
      + `<span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-medium" title="Ngày bắt đầu thực tế"><i class="fas fa-play mr-0.5"></i>${esc(bd||'—')}</span>`
      + `<span class="text-gray-400">→</span>`
      + `<span class="px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-medium" title="Ngày kết thúc thực tế"><i class="fas fa-flag-checkered mr-0.5"></i>${esc(kt||'—')}</span>`
      + `</div>`;
  }
  const target = Number(t.dinh_muc_gio_hieu_luc || 0);
  const done = Number(t.gio_da_lam || 0);
  if (t.trang_thai === 'hoan_thanh' && t.kpi_dat !== null && t.kpi_dat !== undefined) {
    const dat = !!t.kpi_dat;
    html += `<div class="text-[11px] mt-1"><span class="px-1.5 py-0.5 rounded font-bold border ${dat?'bg-green-500 text-white border-green-600':'bg-red-500 text-white border-red-600'}" title="So sánh ngày kết thúc thực tế với hạn hoàn thành"><i class="fas ${dat?'fa-check-circle':'fa-times-circle'} mr-0.5"></i>${dat?'Đạt KPI':'Không đạt KPI'}</span>`
      + (target > 0 ? ` <span class="font-semibold ${dat?'text-green-700':'text-red-700'}">${done}h/${target}h</span>` : '')
      + `</div>`;
  } else if (target > 0 && done > 0) {
    html += `<div class="text-[11px] text-amber-700 mt-1">Đã làm: <span class="font-semibold">${done}h/${target}h</span></div>`;
  }
  return html;
}

function renderRow(t, idx, level, opts){
  opts = opts || {};
  const s = t.trang_thai_hien_thi;
  const tenHienThi = level
    ? (t.ten_cong_viec || [t.hoso_mavt, t.somay].filter(Boolean).join('-'))
    : ([t.hoso_mavt, t.somay].filter(Boolean).join('-') || t.ten_cong_viec);
  const detailUrl = `giaoviec_kpi_detail.php?stt=${t.stt}`;
  const badge = `<span class="px-2 py-0.5 rounded text-xs font-medium ${STATUS_CSS[s]||''}">${esc(STATUS_LABEL[s]||s)}</span>`;
  const deadlineColor = STATUS_TEXT_CSS[s] || 'text-gray-500';
  const nguoi = (t.nguoi_list||[]).map(n =>
    `<span class="inline-block ${n.vai_tro==='chinh'?'bg-blue-600 text-white':'bg-gray-200 text-gray-700'} rounded px-2 py-0.5 text-xs mr-1 mb-1" title="${n.vai_tro==='chinh'?'Chính':'Phụ'}">${esc(n.hoten)}</span>`
  ).join('') || '<span class="text-gray-400 text-xs italic">Chưa giao</span>';
  let nameHtml;
  if (level) {
    nameHtml = `<span class="child-name"><span class="font-medium text-gray-700">${esc(tenHienThi)}</span> <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] bg-green-100 text-green-800 align-middle">Việc con</span></span>`;
  } else {
    const n = opts.childCount || 0;
    const toggle = n ? `<span class="task-toggle ${opts.collapsed?'collapsed':''}" onclick="event.stopPropagation();toggleChildren(${t.stt})" title="${opts.collapsed?'Mở rộng':'Thu gọn'} việc con"><i class="fas fa-chevron-down"></i></span>` : `<span class="inline-block w-5 mr-1"></span>`;
    nameHtml = `${toggle}<i class="fas fa-folder-open text-blue-600 mr-1"></i><span class="font-bold text-blue-900">${esc(tenHienThi)}</span>${n ? ` <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-blue-600 text-white align-middle" title="Số việc con">${n} việc con</span>` : ''}`;
  }
  const ghiChuText = (t.ghi_chu || '').trim();

  const scheduleHours = t.dinh_muc_gio_hien_tai !== null && t.dinh_muc_gio_hien_tai !== undefined && t.dinh_muc_gio_hien_tai !== ''
    ? Number(t.dinh_muc_gio_hien_tai)
    : calculateScheduledHours(t.ngay_bat_dau, t.gio_bat_dau, t.ngay_ket_thuc, t.gio_ket_thuc);

  const admin = `${CAN_EDIT ? `
    <a href="${detailUrl}#nguoi" onclick="event.stopPropagation()" title="Người thực hiện" class="text-purple-600 hover:text-purple-800 px-1"><i class="fas fa-user-plus"></i></a>
    <a href="${detailUrl}" onclick="event.stopPropagation()" title="Sửa" class="text-blue-600 hover:text-blue-800 px-1"><i class="fas fa-edit"></i></a>` : ''}
    ${CAN_CREATE && level===0 ? `<a href="${detailUrl}#children" onclick="event.stopPropagation()" title="Thêm việc con" class="text-green-600 hover:text-green-800 px-1"><i class="fas fa-plus-circle"></i></a>` : ''}
    ${CAN_DELETE ? `<button onclick="event.stopPropagation();delTask(${t.stt})" title="Xóa" class="text-red-500 hover:text-red-700 px-1"><i class="fas fa-trash"></i></button>` : ''}`;
  return `<tr class="${level?'row-child':'row-parent'} cursor-pointer" onclick="location.href='${detailUrl}'" title="Nhấp để mở chi tiết">
    <td class="px-3 py-2">${nameHtml}
      ${t.mo_ta ? `<div class="text-xs text-gray-500 ${level?'pl-6':'pl-8'}">${esc(t.mo_ta)}</div>` : ''}</td>
    <td class="px-3 py-2">${esc(t.hoso||'')}</td>
    <td class="px-3 py-2">${esc(t.nhomsc||'')}</td>
    <td class="px-3 py-2">${fmtDate(t.ngay_bat_dau)} ${t.gio_bat_dau? '<span class="text-xs text-gray-500">'+t.gio_bat_dau.substring(0,5)+'</span>':''}</td>
    <td class="px-3 py-2 ${deadlineColor}">${fmtDate(t.ngay_ket_thuc)} ${t.gio_ket_thuc? '<span class="text-xs">'+t.gio_ket_thuc.substring(0,5)+'</span>':''}</td>
    <td class="px-3 py-2">${badge}</td>
    <td class="px-3 py-2">
      <div class="flex items-center gap-1"><div class="w-20 bg-gray-200 rounded h-2"><div class="h-2 rounded ${(t.tien_do||0)>=100?'bg-green-500':(t.tien_do||0)>=70?'bg-blue-500':(t.tien_do||0)>=40?'bg-yellow-500':'bg-red-500'}" style="width:${t.tien_do||0}%"></div></div><span class="text-xs font-semibold ${(t.tien_do||0)>=100?'text-green-700':(t.tien_do||0)>=70?'text-blue-700':(t.tien_do||0)>=40?'text-yellow-700':'text-red-600'}">${t.tien_do||0}%</span></div>
      ${scheduleHours !== null && Number.isFinite(scheduleHours) && scheduleHours > 0 ? `<div class="text-[11px] text-indigo-700 mt-1"><i class="fas fa-bullseye mr-0.5"></i>Định mức: <span class="font-semibold">${formatScheduleHours(scheduleHours)}</span></div>` : ''}
      ${renderThucTeInfo(t)}
    </td>
    <td class="px-3 py-2 text-xs text-gray-600" title="${esc(ghiChuText)}">${ghiChuText ? `<span class="line-clamp-2">${esc(ghiChuText)}</span>` : '<span class="text-gray-400 italic">-</span>'}</td>
    <td class="px-3 py-2">${nguoi}</td>
    <td class="px-3 py-2 text-xs text-gray-500">${esc(t.nguoi_giao||'')}</td>
    <td class="px-3 py-2 text-center whitespace-nowrap">${admin}</td>
  </tr>`;
}

function calculateScheduledHours(startDate, startTime, endDate, endTime){
  if (!startDate || !startTime || !endDate || !endTime) return null;
  const toUtcMilliseconds = (date, time) => {
    const [year, month, day] = date.split('-').map(Number);
    const [hour, minute] = time.split(':').map(Number);
    if (![year, month, day, hour, minute].every(Number.isFinite)) return NaN;
    return Date.UTC(year, month - 1, day, hour, minute);
  };
  const start = toUtcMilliseconds(startDate, startTime);
  const end = toUtcMilliseconds(endDate, endTime);
  if (!Number.isFinite(start) || !Number.isFinite(end) || end <= start) return NaN;

  let hours = 0;
  const getUtcDayStart = date => {
    const [year, month, day] = date.split('-').map(Number);
    return Date.UTC(year, month - 1, day);
  };
  const startDay = getUtcDayStart(startDate);
  const endDay = getUtcDayStart(endDate);
  for (let dayStart = startDay; dayStart <= endDay; dayStart += 86400000) {
    const weekday = new Date(dayStart).getUTCDay();
    if (weekday === 0 || weekday === 6) continue;
    const intervalStart = Math.max(start, dayStart);
    const intervalEnd = Math.min(end, dayStart + 86400000);
    if (intervalEnd > intervalStart) hours += Math.min(6.5, (intervalEnd - intervalStart) / 3600000);
  }
  return hours;
}

function formatScheduleHours(hours){
  return `${Number(hours.toFixed(2))}h`;
}

async function delTask(stt){
  if (!confirm('Xóa công việc này (kèm các công việc con và người thực hiện)?')) return;
  const r = await fetch(`${API}?action=api_delete&stt=${stt}`, { method:'POST' }).then(r=>r.json());
  if (!r.ok) { alert('Lỗi: '+r.error); return; }
  await loadTasks();
}

document.getElementById('filterStatus').addEventListener('change', renderTable);
document.getElementById('filterNhom').addEventListener('change', renderTable);
document.getElementById('filterThietBi').addEventListener('input', renderTable);
document.getElementById('filterNguoiChinh').addEventListener('change', renderTable);

loadTasks();
</script>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
