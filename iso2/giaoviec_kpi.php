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

function ensureGiaoviecKpiColumns(PDO $db): void {
    $required = [
        'kpi_baoduong_stt' => "ALTER TABLE giaoviec_kpi ADD COLUMN `kpi_baoduong_stt` INT(11) NULL DEFAULT NULL AFTER `hoso`",
        'loai_congviec' => "ALTER TABLE giaoviec_kpi ADD COLUMN `loai_congviec` ENUM('kiem_tra','bd_cap_1','bd_cap_2','bd_cap_3','hieu_chuan') NULL DEFAULT NULL AFTER `kpi_baoduong_stt`",
        'dinh_muc_gio_thu_cong' => "ALTER TABLE giaoviec_kpi ADD COLUMN `dinh_muc_gio_thu_cong` DECIMAL(8,2) NULL DEFAULT NULL AFTER `loai_congviec`",
        'ghi_chu' => "ALTER TABLE giaoviec_kpi ADD COLUMN `ghi_chu` TEXT NULL DEFAULT NULL AFTER `mo_ta`",
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
}

$currentUser = $_SESSION['username'] ?? 'unknown';
$canCreate = hasPermission(PERMISSION_GIAOVIEC_KPI_CREATE);
$canEdit = hasPermission(PERMISSION_GIAOVIEC_KPI_EDIT);
$canDelete = hasPermission(PERMISSION_GIAOVIEC_KPI_DELETE);
$action = $_GET['action'] ?? 'index';

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
                SELECT g.*, h.mavt AS hoso_mavt,
                  (SELECT GROUP_CONCAT(CONCAT(n.user_stt,'|',COALESCE(NULLIF(TRIM(u.hoten), ''), u.username),'|',n.vai_tro) SEPARATOR ';;')
                   FROM giaoviec_kpi_nguoi n
                   INNER JOIN users u ON u.stt = n.user_stt
                   WHERE n.giaoviec_stt = g.stt) AS nguoi_raw,
                    (SELECT COUNT(*) FROM giaoviec_kpi_nguoi WHERE giaoviec_stt = g.stt) AS nguoi_count
                FROM giaoviec_kpi g
                LEFT JOIN hososcbd_iso h ON h.stt = g.hososcbd_stt
                ORDER BY COALESCE(g.parent_stt, g.stt) DESC, g.parent_stt IS NOT NULL, g.stt ASC
            ");
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $r['trang_thai_hien_thi'] = computeStatus($r, (int)$r['nguoi_count']);
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
            $ngay_bd      = $in['ngay_bat_dau'] ?: null;
            $gio_bd       = $in['gio_bat_dau']  ?: null;
            $so_ngay      = (int)($in['so_ngay'] ?? 0);
            $ngay_kt      = $in['ngay_ket_thuc']?: null;
            $gio_kt       = $in['gio_ket_thuc'] ?: null;
            $tien_do      = (int)($in['tien_do'] ?? 0);
            $trang_thai   = $in['trang_thai'] ?? 'chua_giao';

            if ($ten === '') jsonOut(['ok' => false, 'error' => 'Thiếu tên công việc'], 400);

            if ($stt > 0) {
                $sql = "UPDATE giaoviec_kpi SET parent_stt=:p, hososcbd_stt=:hs, phieu=:ph, somay=:sm, hoso=:ho,
                        kpi_baoduong_stt=:kpi, loai_congviec=:lc, dinh_muc_gio_thu_cong=:dm,
                        ten_cong_viec=:t, mo_ta=:mt, ghi_chu=:gc, ngay_bat_dau=:nbd, gio_bat_dau=:gbd, so_ngay=:sn,
                        ngay_ket_thuc=:nkt, gio_ket_thuc=:gkt, tien_do=:td, trang_thai=:tt WHERE stt=:s";
                $st = $db->prepare($sql);
                $st->execute([':p'=>$parent_stt, ':hs'=>$hososcbd_stt, ':ph'=>$phieu, ':sm'=>$somay, ':ho'=>$hoso,
                    ':kpi'=>$kpiStt, ':lc'=>$loaiCongViec !== '' ? $loaiCongViec : null, ':dm'=>$dinhMucGio,
                    ':t'=>$ten, ':mt'=>$mo_ta, ':gc'=>$ghiChu !== '' ? $ghiChu : null, ':nbd'=>$ngay_bd, ':gbd'=>$gio_bd, ':sn'=>$so_ngay,
                    ':nkt'=>$ngay_kt, ':gkt'=>$gio_kt, ':td'=>$tien_do, ':tt'=>$trang_thai, ':s'=>$stt]);
                jsonOut(['ok'=>true, 'stt'=>$stt]);
            } else {
                $sql = "INSERT INTO giaoviec_kpi (parent_stt, hososcbd_stt, phieu, somay, hoso, kpi_baoduong_stt, loai_congviec, dinh_muc_gio_thu_cong,
                        ten_cong_viec, mo_ta, ghi_chu, ngay_bat_dau, gio_bat_dau, so_ngay, ngay_ket_thuc, gio_ket_thuc, tien_do, trang_thai, nguoi_giao)
                        VALUES (:p,:hs,:ph,:sm,:ho,:kpi,:lc,:dm,:t,:mt,:gc,:nbd,:gbd,:sn,:nkt,:gkt,:td,:tt,:ng)";
                $st = $db->prepare($sql);
                $st->execute([':p'=>$parent_stt, ':hs'=>$hososcbd_stt, ':ph'=>$phieu, ':sm'=>$somay, ':ho'=>$hoso,
                    ':kpi'=>$kpiStt, ':lc'=>$loaiCongViec !== '' ? $loaiCongViec : null, ':dm'=>$dinhMucGio,
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
    min-width: 1080px;
  }
  @media (max-width: 640px) {
    .task-page {
      padding: 0.75rem;
    }
    .task-table {
      min-width: 920px;
    }
  }
</style>
<div class="task-page w-full min-w-0">
  <div class="bg-white rounded-lg shadow p-4 mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="text-xl font-bold text-gray-800"><i class="fas fa-tasks text-blue-600 mr-2"></i>Giao việc &amp; KPI</h1>
      <p class="text-sm text-gray-500">Trưởng nhóm giao việc theo phiếu / máy — theo dõi tiến độ, người thực hiện.</p>
    </div>
    <div class="flex gap-2">
      <select id="filterStatus" class="border rounded px-3 py-2 text-sm">
        <option value="">— Tình trạng: Tất cả —</option>
        <option value="chua_giao">Cần giao</option>
        <option value="den_han">Đến hạn</option>
        <option value="qua_han">Quá hạn</option>
        <option value="dang_lam">Đang thực hiện</option>
        <option value="hoan_thanh">Hoàn thành</option>
      </select>
      <?php if ($canCreate): ?>
      <button id="btnAdd" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
        <i class="fas fa-plus mr-1"></i> Thêm công việc
      </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="task-table-wrap bg-white rounded-lg shadow">
    <table class="task-table w-full text-sm">
      <thead class="bg-gray-50 text-gray-700">
        <tr>
          <th class="px-3 py-2 text-left w-10">#</th>
          <th class="px-3 py-2 text-left">Tên công việc</th>
          <th class="px-3 py-2 text-left">Dự án (Hồ sơ)</th>
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

<!-- ============= Modal: Thêm/Sửa công việc ============= -->
<div id="modalTask" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-[100] p-4">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3 border-b">
      <h3 class="font-semibold text-gray-800" id="modalTaskTitle">Thêm công việc</h3>
      <button onclick="closeModal('modalTask')" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times"></i></button>
    </div>
    <div class="p-5 space-y-3 overflow-y-auto flex-1">
      <input type="hidden" id="f_stt">
      <input type="hidden" id="f_parent_stt">
      <input type="hidden" id="f_hososcbd_stt">
      <div>
        <label class="text-sm font-medium text-gray-700">Dự án (Phiếu) — chọn hồ sơ SCBD</label>
        <input list="dl_hoso" id="f_hoso_search" placeholder="Nhập phiếu / mã VT / số máy / hồ sơ để tìm..." class="w-full border rounded px-3 py-2 mt-1 text-sm" readonly>
        <datalist id="dl_hoso"></datalist>
        <div id="f_hoso_info" class="text-xs text-gray-500 mt-1"></div>
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">Tên công việc <span class="text-red-500">*</span></label>
        <input type="text" id="f_ten" class="w-full border rounded px-3 py-2 mt-1 text-sm" placeholder="VD: Sửa máy XYZ - Hồ sơ 123" readonly>
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">Mô tả</label>
        <textarea id="f_mota" rows="2" class="w-full border rounded px-3 py-2 mt-1 text-sm"></textarea>
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">Ghi chú</label>
        <textarea id="f_ghi_chu" rows="2" class="w-full border rounded px-3 py-2 mt-1 text-sm" placeholder="Thông tin bổ sung, lưu ý, điều kiện thực hiện..."></textarea>
      </div>

      <div class="border border-teal-200 bg-teal-50 rounded-lg p-3 space-y-3">
        <div class="flex items-center gap-2 text-teal-700 font-semibold text-sm">
          <i class="fas fa-bullseye"></i>
          <span>Định mức KPI</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div>
            <label class="text-xs font-medium text-gray-700">Thiết bị KPI</label>
            <select id="f_kpi_baoduong_stt" class="w-full border rounded px-2 py-2 mt-1 text-sm">
              <option value="">-- Chọn thiết bị --</option>
              <?php foreach ($kpiThietBiList as $kpiRow): ?>
                <option value="<?= (int)$kpiRow['id'] ?>"><?= htmlspecialchars((string)$kpiRow['ten_thiet_bi']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="text-xs font-medium text-gray-700">Loại công việc</label>
            <select id="f_loai_congviec" class="w-full border rounded px-2 py-2 mt-1 text-sm">
              <?php foreach ($loaiCongViecLabels as $key => $label): ?>
                <option value="<?= htmlspecialchars($key) ?>" <?= $key === 'kiem_tra' ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="text-xs font-medium text-gray-700">Định mức giờ (nhập tay)</label>
            <input type="number" id="f_dinh_muc_gio_thu_cong" step="0.01" min="0" value="" placeholder="Để trống nếu dùng KPI" class="w-full border rounded px-2 py-2 mt-1 text-sm">
          </div>
        </div>
        <div class="text-xs text-teal-700">
          <span class="font-medium">Định mức giờ hiện tại:</span>
          <span id="f_kpi_preview">—</span>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div>
          <label class="text-xs font-medium text-gray-600">Ngày bắt đầu</label>
          <input type="date" id="f_ngay_bd" class="w-full border rounded px-2 py-2 text-sm">
        </div>
        <div>
          <label class="text-xs font-medium text-gray-600">Giờ bắt đầu</label>
          <input type="time" id="f_gio_bd" class="w-full border rounded px-2 py-2 text-sm">
        </div>
        <div>
          <label class="text-xs font-medium text-gray-600">Số ngày</label>
          <input type="number" id="f_so_ngay" min="0" value="0" class="w-full border rounded px-2 py-2 text-sm">
        </div>
        <div>
          <label class="text-xs font-medium text-gray-600">Tiến độ (%)</label>
          <input type="number" id="f_tien_do" min="0" max="100" value="0" class="w-full border rounded px-2 py-2 text-sm">
        </div>
        <div>
          <label class="text-xs font-medium text-gray-600">Hạn hoàn thành</label>
          <input type="date" id="f_ngay_kt" class="w-full border rounded px-2 py-2 text-sm">
        </div>
        <div>
          <label class="text-xs font-medium text-gray-600">Giờ kết thúc</label>
          <input type="time" id="f_gio_kt" class="w-full border rounded px-2 py-2 text-sm">
        </div>
        <div class="col-span-2">
          <label class="text-xs font-medium text-gray-600">Trạng thái</label>
          <select id="f_trang_thai" class="w-full border rounded px-2 py-2 text-sm">
            <option value="chua_giao">Cần giao (chưa giao)</option>
            <option value="dang_lam">Đang thực hiện</option>
            <option value="hoan_thanh">Hoàn thành</option>
            <option value="huy">Đã hủy</option>
          </select>
        </div>
      </div>
    </div>
    <div class="px-5 py-3 border-t flex justify-end gap-2 bg-gray-50 rounded-b-lg">
      <button onclick="closeModal('modalTask')" class="px-4 py-2 text-sm rounded border">Hủy</button>
      <button onclick="saveTask()" class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">
        <i class="fas fa-save mr-1"></i>Lưu
      </button>
    </div>
  </div>
</div>

<!-- ============= Modal: Chọn người thực hiện ============= -->
<div id="modalNguoi" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-[100] p-4">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3 border-b">
      <h3 class="font-semibold text-gray-800">Chọn người thực hiện</h3>
      <button onclick="closeModal('modalNguoi')" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times"></i></button>
    </div>
    <div class="p-5 space-y-3 overflow-y-auto flex-1">
      <input type="hidden" id="n_giaoviec_stt">
      <div>
        <label class="text-sm font-medium text-gray-700">Người làm <b>chính</b></label>
        <select id="n_chinh" class="w-full border rounded px-3 py-2 mt-1 text-sm">
          <option value="">— Chọn —</option>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">Người làm <b>phụ</b> (Ctrl/Cmd để chọn nhiều)</label>
        <select id="n_phu" multiple size="8" class="w-full border rounded px-3 py-2 mt-1 text-sm"></select>
      </div>
    </div>
    <div class="px-5 py-3 border-t flex justify-end gap-2 bg-gray-50 rounded-b-lg">
      <button onclick="closeModal('modalNguoi')" class="px-4 py-2 text-sm rounded border">Hủy</button>
      <button onclick="saveNguoi()" class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">
        <i class="fas fa-save mr-1"></i>Lưu
      </button>
    </div>
  </div>
</div>

<script>
const API = 'giaoviec_kpi.php';
const CAN_CREATE = <?= $canCreate ? 'true' : 'false' ?>;
const CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;
const CAN_DELETE = <?= $canDelete ? 'true' : 'false' ?>;
let ALL_TASKS = [];
let RESUME_LIST = [];
let USERS_LIST = [];
let HOSO_LIST = [];

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

function openModal(id){ document.getElementById(id).classList.remove('hidden'); document.getElementById(id).classList.add('flex'); }
function closeModal(id){ document.getElementById(id).classList.add('hidden'); document.getElementById(id).classList.remove('flex'); }
function esc(s){ return (s??'').toString().replace(/[&<>"']/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function fmtDate(d){ if(!d) return ''; return d.split('-').reverse().join('/'); }

async function loadResume(){
  const r = await fetch(`${API}?action=api_users`).then(r=>r.json());
  USERS_LIST = (r.data || []).map(u => ({
    stt: Number(u.stt || 0),
    display_name: String(u.display_name || u.label || '').trim(),
    label: String(u.label || u.display_name || '').trim(),
  })).filter(u => u.stt > 0 && u.display_name !== '');
  RESUME_LIST = USERS_LIST.map(u => u.display_name);
}
async function loadHoso(){
  const r = await fetch(`${API}?action=api_hososcbd`).then(r=>r.json());
  HOSO_LIST = r.data || [];
  const dl = document.getElementById('dl_hoso');
  dl.innerHTML = HOSO_LIST.map(h => `<option data-stt="${h.stt}" value="${esc(h.phieu)} — ${esc(h.mavt)} — ${esc(h.somay)} — ${esc(h.hoso)}"></option>`).join('');
}
async function loadTasks(){
  const r = await fetch(`${API}?action=api_list`).then(r=>r.json());
  ALL_TASKS = r.data || [];
  renderTable();
}

function renderTable(){
  const filter = document.getElementById('filterStatus').value;
  const roots = ALL_TASKS.filter(t => !t.parent_stt);
  const rows = [];
  let idx = 0;
  for (const t of roots) {
    if (filter && t.trang_thai_hien_thi !== filter) continue;
    idx++;
    rows.push(renderRow(t, idx, 0));
    const children = ALL_TASKS.filter(c => Number(c.parent_stt) === Number(t.stt));
    for (const c of children) rows.push(renderRow(c, idx+'.'+(children.indexOf(c)+1), 1));
  }
  document.getElementById('tblBody').innerHTML = rows.length
    ? rows.join('')
    : `<tr><td colspan="10" class="p-6 text-center text-gray-400">Chưa có công việc.</td></tr>`;
}

function renderRow(t, idx, level){
  const s = t.trang_thai_hien_thi;
  const tenHienThi = [t.hoso_mavt, t.somay].filter(Boolean).join('-') || t.ten_cong_viec;
  const badge = `<span class="px-2 py-0.5 rounded text-xs font-medium ${STATUS_CSS[s]||''}">${esc(STATUS_LABEL[s]||s)}</span>`;
  const deadlineColor = STATUS_TEXT_CSS[s] || 'text-gray-500';
  const nguoi = (t.nguoi_list||[]).map(n =>
    `<span class="inline-block ${n.vai_tro==='chinh'?'bg-blue-600 text-white':'bg-gray-200 text-gray-700'} rounded px-2 py-0.5 text-xs mr-1 mb-1" title="${n.vai_tro==='chinh'?'Chính':'Phụ'}">${esc(n.hoten)}</span>`
  ).join('') || '<span class="text-gray-400 text-xs italic">Chưa giao</span>';
  const indent = level ? `<span class="text-gray-400 ml-4">↳</span> ` : '';
  const ghiChuText = (t.ghi_chu || '').trim();

  let dinhMucText = '';
  const selectedKpiId = Number(t.kpi_baoduong_stt || 0);
  const selectedLoai = t.loai_congviec || 'kiem_tra';
  const numericManual = t.dinh_muc_gio_thu_cong !== null && t.dinh_muc_gio_thu_cong !== undefined && t.dinh_muc_gio_thu_cong !== ''
    ? Number(t.dinh_muc_gio_thu_cong)
    : NaN;
  if (!Number.isNaN(numericManual)) {
    dinhMucText = `${numericManual}h`;
  } else if (selectedKpiId && KPI_HOUR_MAP[selectedKpiId] && KPI_HOUR_MAP[selectedKpiId][selectedLoai] !== null && KPI_HOUR_MAP[selectedKpiId][selectedLoai] !== undefined) {
    dinhMucText = `${KPI_HOUR_MAP[selectedKpiId][selectedLoai]}h`;
  }

  const admin = `${CAN_EDIT ? `
    <button onclick="openNguoi(${t.stt})" title="Người thực hiện" class="text-purple-600 hover:text-purple-800 px-1"><i class="fas fa-user-plus"></i></button>
    <button onclick="editTask(${t.stt})" title="Sửa" class="text-blue-600 hover:text-blue-800 px-1"><i class="fas fa-edit"></i></button>` : ''}
    ${CAN_CREATE && level===0 ? `<button onclick="addSubtask(${t.stt})" title="Thêm việc con" class="text-green-600 hover:text-green-800 px-1"><i class="fas fa-plus-circle"></i></button>` : ''}
    ${CAN_DELETE ? `<button onclick="delTask(${t.stt})" title="Xóa" class="text-red-500 hover:text-red-700 px-1"><i class="fas fa-trash"></i></button>` : ''}`;
  return `<tr class="border-t hover:bg-gray-50 ${level?'bg-gray-50/40':''}">
    <td class="px-3 py-2 text-gray-500">${idx}</td>
    <td class="px-3 py-2">${indent}<span class="font-medium">${esc(tenHienThi)}</span>
      ${t.mo_ta ? `<div class="text-xs text-gray-500">${esc(t.mo_ta)}</div>` : ''}</td>
    <td class="px-3 py-2">${esc(t.hoso||'')}</td>
    <td class="px-3 py-2">${fmtDate(t.ngay_bat_dau)} ${t.gio_bat_dau? '<span class="text-xs text-gray-500">'+t.gio_bat_dau.substring(0,5)+'</span>':''}</td>
    <td class="px-3 py-2 ${deadlineColor}">${fmtDate(t.ngay_ket_thuc)} ${t.gio_ket_thuc? '<span class="text-xs">'+t.gio_ket_thuc.substring(0,5)+'</span>':''}</td>
    <td class="px-3 py-2">${badge}</td>
    <td class="px-3 py-2">
      <div class="flex items-center gap-1"><div class="w-16 bg-gray-200 rounded h-1.5"><div class="bg-blue-500 h-1.5 rounded" style="width:${t.tien_do||0}%"></div></div><span class="text-xs">${t.tien_do||0}%</span></div>
      ${dinhMucText ? `<div class="text-[11px] text-gray-500 mt-1">Định mức: <span class="font-medium text-gray-700">${esc(dinhMucText)}</span></div>` : ''}
    </td>
    <td class="px-3 py-2 text-xs text-gray-600" title="${esc(ghiChuText)}">${ghiChuText ? `<span class="line-clamp-2">${esc(ghiChuText)}</span>` : '<span class="text-gray-400 italic">-</span>'}</td>
    <td class="px-3 py-2">${nguoi}</td>
    <td class="px-3 py-2 text-xs text-gray-500">${esc(t.nguoi_giao||'')}</td>
    <td class="px-3 py-2 text-center whitespace-nowrap">${admin}</td>
  </tr>`;
}

/* ========= CRUD công việc ========= */
const KPI_HOUR_MAP = <?= json_encode($kpiHourPreviewMap, JSON_UNESCAPED_UNICODE) ?>;

function updateKpiPreview(){
  const kpiId = document.getElementById('f_kpi_baoduong_stt').value;
  const loai = document.getElementById('f_loai_congviec').value;
  const manual = document.getElementById('f_dinh_muc_gio_thu_cong').value;
  const preview = document.getElementById('f_kpi_preview');
  if (manual !== '') {
    preview.textContent = `${manual}h`;
    return;
  }
  if (kpiId && KPI_HOUR_MAP[kpiId] && KPI_HOUR_MAP[kpiId][loai] !== null && KPI_HOUR_MAP[kpiId][loai] !== undefined) {
    preview.textContent = `${KPI_HOUR_MAP[kpiId][loai]}h`;
    return;
  }
  preview.textContent = '—';
}

function setTaskFormLocked(isLocked){
  const fields = ['f_hoso_search', 'f_ten'];
  fields.forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.readOnly = isLocked;
    el.classList.toggle('bg-gray-100', isLocked);
    el.classList.toggle('cursor-not-allowed', isLocked);
  });
}

function resetTaskForm(){
  ['f_stt','f_parent_stt','f_hososcbd_stt','f_hoso_search','f_ten','f_mota','f_ghi_chu','f_ngay_bd','f_gio_bd','f_ngay_kt','f_gio_kt','f_dinh_muc_gio_thu_cong']
    .forEach(id => document.getElementById(id).value='');
  document.getElementById('f_kpi_baoduong_stt').value = '';
  document.getElementById('f_loai_congviec').value = 'kiem_tra';
  document.getElementById('f_so_ngay').value = 0;
  document.getElementById('f_tien_do').value = 0;
  document.getElementById('f_trang_thai').value = 'chua_giao';
  document.getElementById('f_hoso_info').textContent = '';
  setTaskFormLocked(false);
  updateKpiPreview();
}

document.getElementById('btnAdd')?.addEventListener('click', () => {
  resetTaskForm();
  setTaskFormLocked(false);
  document.getElementById('modalTaskTitle').textContent = 'Thêm công việc';
  openModal('modalTask');
});

function editTask(stt){
  const t = ALL_TASKS.find(x => Number(x.stt) === stt);
  if (!t) return;
  resetTaskForm();
  document.getElementById('f_stt').value = t.stt;
  document.getElementById('f_parent_stt').value = t.parent_stt || '';
  document.getElementById('f_hososcbd_stt').value = t.hososcbd_stt || '';
  document.getElementById('f_hoso_search').value = t.phieu ? `${t.phieu} — ${t.hoso_mavt||''} — ${t.somay||''} — ${t.hoso||''}` : '';
  document.getElementById('f_ten').value = t.ten_cong_viec || '';
  document.getElementById('f_mota').value = t.mo_ta || '';
  document.getElementById('f_ghi_chu').value = t.ghi_chu || '';
  document.getElementById('f_ngay_bd').value = t.ngay_bat_dau || '';
  document.getElementById('f_gio_bd').value = (t.gio_bat_dau||'').substring(0,5);
  document.getElementById('f_so_ngay').value = t.so_ngay || 0;
  document.getElementById('f_ngay_kt').value = t.ngay_ket_thuc || '';
  document.getElementById('f_gio_kt').value = (t.gio_ket_thuc||'').substring(0,5);
  document.getElementById('f_tien_do').value = t.tien_do || 0;
  document.getElementById('f_trang_thai').value = t.trang_thai || 'chua_giao';

  const savedLoai = t.loai_congviec || 'kiem_tra';
  document.getElementById('f_loai_congviec').value = savedLoai;

  const savedManualHour = t.dinh_muc_gio_thu_cong !== null && t.dinh_muc_gio_thu_cong !== undefined && t.dinh_muc_gio_thu_cong !== ''
    ? String(t.dinh_muc_gio_thu_cong)
    : '';
  document.getElementById('f_dinh_muc_gio_thu_cong').value = savedManualHour;

  const savedKpi = t.kpi_baoduong_stt || '';
  const selectKpi = document.getElementById('f_kpi_baoduong_stt');
  if (savedKpi !== '') {
    selectKpi.value = String(savedKpi);
  } else if (t.hososcbd_stt) {
    loadKpiBySelectedHoso(Number(t.hososcbd_stt));
  } else {
    selectKpi.value = '';
  }

  setTaskFormLocked(true);
  document.getElementById('modalTaskTitle').textContent = 'Sửa công việc';
  updateKpiPreview();
  openModal('modalTask');
}

function addSubtask(parentStt){
  resetTaskForm();
  document.getElementById('f_parent_stt').value = parentStt;
  const p = ALL_TASKS.find(x => Number(x.stt) === parentStt);
  if (p) {
    document.getElementById('f_hososcbd_stt').value = p.hososcbd_stt || '';
    document.getElementById('f_hoso_search').value = p.phieu ? `${p.phieu} — ${p.mavt||''} — ${p.somay||''} — ${p.hoso||''}` : '';
  }
  document.getElementById('modalTaskTitle').textContent = 'Thêm công việc con';
  openModal('modalTask');
}

// Tự động tính hạn hoàn thành từ ngày bắt đầu + số ngày
function recalcEnd(){
  const bd = document.getElementById('f_ngay_bd').value;
  const sn = parseInt(document.getElementById('f_so_ngay').value || '0', 10);
  if (bd && sn > 0) {
    const d = new Date(bd);
    d.setDate(d.getDate() + sn);
    document.getElementById('f_ngay_kt').value = d.toISOString().substring(0,10);
  }
}
['f_ngay_bd','f_so_ngay'].forEach(id => document.getElementById(id).addEventListener('change', recalcEnd));

// Khi chọn hồ sơ từ datalist → tự điền tên công việc + hososcbd_stt
async function loadKpiBySelectedHoso(stt){
  if (!stt) return;
  const r = await fetch(`${API}?action=api_kpi_by_hoso&stt=${encodeURIComponent(stt)}`).then(r => r.json());
  const data = r && r.data ? r.data : null;
  if (!data) return;

  const select = document.getElementById('f_kpi_baoduong_stt');
  if (data.kpi_baoduong_stt && select) {
    select.value = String(data.kpi_baoduong_stt);
  } else if (select) {
    select.value = '';
  }
  document.getElementById('f_dinh_muc_gio_thu_cong').value = '';
  updateKpiPreview();
}

document.getElementById('f_hoso_search').addEventListener('input', function(){
  const v = this.value;
  const opt = [...document.getElementById('dl_hoso').options].find(o => o.value === v);
  if (opt) {
    const stt = opt.getAttribute('data-stt');
    const h = HOSO_LIST.find(x => String(x.stt) === String(stt));
    if (h) {
      document.getElementById('f_hososcbd_stt').value = h.stt;
      if (!document.getElementById('f_ten').value) {
        document.getElementById('f_ten').value = `${h.mavt||''}-${h.somay||''}`;
      }
      document.getElementById('f_hoso_info').textContent = `Phiếu ${h.phieu} · Máy ${h.somay} · HS ${h.hoso}`;
      loadKpiBySelectedHoso(h.stt);
    }
  }
});

async function saveTask(){
  const stt = document.getElementById('f_stt').value;
  // Lấy phiếu/somay/hoso từ hoso_stt
  let phieu='', somay='', hoso='';
  const hsStt = document.getElementById('f_hososcbd_stt').value;
  if (hsStt) {
    const h = HOSO_LIST.find(x => String(x.stt) === String(hsStt));
    if (h) { phieu = h.phieu||''; somay = h.somay||''; hoso = h.hoso||''; }
  }
  const payload = {
    stt: stt ? Number(stt) : 0,
    parent_stt: document.getElementById('f_parent_stt').value || null,
    hososcbd_stt: hsStt || null,
    phieu, somay, hoso,
    kpi_baoduong_stt: document.getElementById('f_kpi_baoduong_stt').value || null,
    loai_congviec: document.getElementById('f_loai_congviec').value || null,
    dinh_muc_gio_thu_cong: document.getElementById('f_dinh_muc_gio_thu_cong').value || null,
    ten_cong_viec: document.getElementById('f_ten').value.trim(),
    mo_ta: document.getElementById('f_mota').value.trim(),
    ghi_chu: document.getElementById('f_ghi_chu').value.trim(),
    ngay_bat_dau: document.getElementById('f_ngay_bd').value || null,
    gio_bat_dau: document.getElementById('f_gio_bd').value || null,
    so_ngay: parseInt(document.getElementById('f_so_ngay').value||'0',10),
    ngay_ket_thuc: document.getElementById('f_ngay_kt').value || null,
    gio_ket_thuc: document.getElementById('f_gio_kt').value || null,
    tien_do: parseInt(document.getElementById('f_tien_do').value||'0',10),
    trang_thai: document.getElementById('f_trang_thai').value,
  };
  if (!payload.ten_cong_viec) { alert('Vui lòng nhập tên công việc'); return; }
  const r = await fetch(`${API}?action=api_save`, {
    method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
  }).then(r=>r.json());
  if (!r.ok) { alert('Lỗi: '+(r.error||'')); return; }
  closeModal('modalTask');
  await loadTasks();
}

async function delTask(stt){
  if (!confirm('Xóa công việc này (kèm các công việc con và người thực hiện)?')) return;
  const r = await fetch(`${API}?action=api_delete&stt=${stt}`, { method:'POST' }).then(r=>r.json());
  if (!r.ok) { alert('Lỗi: '+r.error); return; }
  await loadTasks();
}

/* ========= Người thực hiện ========= */
function openNguoi(stt){
  const t = ALL_TASKS.find(x => Number(x.stt) === stt);
  if (!t) return;
  document.getElementById('n_giaoviec_stt').value = stt;
  const chinhSel = document.getElementById('n_chinh');
  const phuSel   = document.getElementById('n_phu');
  const userOptions = (USERS_LIST || []).map(u => ({
    stt: Number(u.stt || 0),
    label: String(u.display_name || u.label || '').trim(),
  })).filter(u => u.stt > 0 && u.label !== '');
  chinhSel.innerHTML = '<option value="">— Chọn —</option>' + userOptions.map(u => `<option value="${u.stt}">${esc(u.label)}</option>`).join('');
  phuSel.innerHTML   = userOptions.map(u => `<option value="${u.stt}">${esc(u.label)}</option>`).join('');
  const cur = t.nguoi_list||[];
  const chinh = cur.find(x=>x.vai_tro==='chinh');
  if (chinh && userOptions.some(u => u.stt === Number(chinh.user_stt))) {
    chinhSel.value = String(chinh.user_stt);
  } else {
    chinhSel.value = '';
  }
  const phuSet = new Set(cur.filter(x=>x.vai_tro==='phu').map(x=>String(x.user_stt)));
  [...phuSel.options].forEach(o => { if (phuSet.has(o.value)) o.selected = true; });
  openModal('modalNguoi');
}

async function saveNguoi(){
  const gvStt = Number(document.getElementById('n_giaoviec_stt').value);
  const chinh = Number(document.getElementById('n_chinh').value || 0);
  const phu = [...document.getElementById('n_phu').selectedOptions].map(o=>Number(o.value));
  const r = await fetch(`${API}?action=api_save_nguoi`, {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ giaoviec_stt: gvStt, chinh, phu })
  }).then(r=>r.json());
  if (!r.ok) { alert('Lỗi: '+r.error); return; }
  closeModal('modalNguoi');
  await loadTasks();
}

document.getElementById('filterStatus').addEventListener('change', renderTable);
document.getElementById('f_kpi_baoduong_stt')?.addEventListener('change', updateKpiPreview);
document.getElementById('f_loai_congviec')?.addEventListener('change', updateKpiPreview);
document.getElementById('f_dinh_muc_gio_thu_cong')?.addEventListener('input', updateKpiPreview);

(async function init(){
  await Promise.all([loadResume(), loadHoso()]);
  await loadTasks();
  updateKpiPreview();
})();
</script>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
