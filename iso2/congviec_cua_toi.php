<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/config/database.php';
requireAuth();

$db = getDBConnection();

function ensureGiaoviecKpiNguoiColumns(PDO $db): bool {
    try {
    $columns = $db->query("SHOW COLUMNS FROM `giaoviec_kpi_nguoi`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('user_stt', $columns, true)) {
      $db->exec("ALTER TABLE `giaoviec_kpi_nguoi` ADD COLUMN `user_stt` INT(11) NULL DEFAULT NULL");
      $columns = $db->query("SHOW COLUMNS FROM `giaoviec_kpi_nguoi`")->fetchAll(PDO::FETCH_COLUMN);
      if (!in_array('user_stt', $columns, true)) {
        throw new RuntimeException('Không thể tạo cột user_stt trong bảng giaoviec_kpi_nguoi');
      }
        }
        return true;
    } catch (Throwable $e) {
        error_log('ensureGiaoviecKpiNguoiColumns: ' . $e->getMessage());
      return false;
  }
}

function getGiaoviecKpiNguoiColumns(PDO $db): array {
  try {
    return $db->query("SHOW COLUMNS FROM `giaoviec_kpi_nguoi`")->fetchAll(PDO::FETCH_COLUMN);
  } catch (Throwable $e) {
    return [];
  }
}

// Đảm bảo bảng log tồn tại (fallback cho DB cũ)
function ensureThucHienTables(PDO $db): void {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `giaoviec_kpi_thuchien` (
            `stt` INT(11) NOT NULL AUTO_INCREMENT,
            `giaoviec_stt` INT(11) NOT NULL,
            `ngay_lam` DATE NOT NULL,
            `noi_dung` TEXT DEFAULT NULL,
            `tong_gio` DECIMAL(8,2) DEFAULT 0,
            `nguoi_nhap` VARCHAR(100) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`stt`),
            KEY `idx_giaoviec` (`giaoviec_stt`),
            KEY `idx_ngay` (`ngay_lam`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1");
        $db->exec("CREATE TABLE IF NOT EXISTS `giaoviec_kpi_thuchien_gio` (
            `stt` INT(11) NOT NULL AUTO_INCREMENT,
            `thuchien_stt` INT(11) NOT NULL,
            `hoten` VARCHAR(100) NOT NULL,
            `so_gio` DECIMAL(6,2) NOT NULL DEFAULT 0,
            PRIMARY KEY (`stt`),
            KEY `idx_thuchien` (`thuchien_stt`),
            KEY `idx_hoten` (`hoten`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1");
    } catch (Throwable $e) {
        error_log('ensureThucHienTables: ' . $e->getMessage());
    }
}
ensureThucHienTables($db);
$userSttSupported = ensureGiaoviecKpiNguoiColumns($db);

$username = $_SESSION['username'] ?? '';
$currentUserStt = (int)($_SESSION['user_stt'] ?? $_SESSION['user_id'] ?? 0);
$isAdmin = hasRole(ROLE_ADMIN);

// Tên hiển thị chỉ lấy từ users; phân quyền và ghép công việc dùng user_stt.
$currentHoten = '';
try {
  $st = $db->prepare("SELECT COALESCE(NULLIF(TRIM(hoten), ''), username) FROM users WHERE stt = :s LIMIT 1");
  $st->execute([':s' => $currentUserStt]);
    $currentHoten = (string)($st->fetchColumn() ?: '');
} catch (Throwable $e) { /* fallback bên dưới */ }
if ($currentHoten === '') $currentHoten = $username;

function buildUserMatchCondition(int $currentUserStt): array {
  return [
    $currentUserStt > 0 ? 'n.user_stt = ?' : '(0=1)',
    $currentUserStt > 0 ? [$currentUserStt] : [],
  ];
}

function jsonOut($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getTaskProgress(PDO $db, array $task): array {
  $hoursStmt = $db->prepare("SELECT COALESCE(SUM(tong_gio), 0) FROM giaoviec_kpi_thuchien WHERE giaoviec_stt = ?");
  $hoursStmt->execute([(int)$task['stt']]);
  $hoursDone = (float)$hoursStmt->fetchColumn();

  $manualHours = $task['dinh_muc_gio_thu_cong'] ?? null;
  $targetHours = ($manualHours !== null && $manualHours !== '' && (float)$manualHours > 0)
    ? (float)$manualHours
    : 0.0;

  if ($targetHours <= 0 && !empty($task['kpi_baoduong_stt']) && !empty($task['loai_congviec'])) {
    $columnMap = [
      'kiem_tra' => 'kiem_tra_so_gio',
      'bd_cap_1' => 'bd_cap_1_so_gio',
      'bd_cap_2' => 'bd_cap_2_so_gio',
      'bd_cap_3' => 'bd_cap_3_so_gio',
      'hieu_chuan' => 'hieu_chuan_so_gio',
    ];
    $column = $columnMap[(string)$task['loai_congviec']] ?? null;
    if ($column !== null) {
      $stmt = $db->prepare("SELECT {$column} FROM kpi_baoduong_thietbi_iso WHERE id = ? LIMIT 1");
      $stmt->execute([(int)$task['kpi_baoduong_stt']]);
      $value = $stmt->fetchColumn();
      if ($value !== false && $value !== null && (float)$value > 0) {
        $targetHours = (float)$value;
      }
    }
  }

  $progress = $targetHours > 0
    ? min(100, (int)round(($hoursDone / $targetHours) * 100))
    : (int)($task['tien_do'] ?? 0);

  if (($task['trang_thai'] ?? '') === 'hoan_thanh') {
    $progress = 100;
  }

  return [
    'gio_da_lam' => $hoursDone,
    'dinh_muc_gio_hieu_luc' => $targetHours > 0 ? $targetHours : null,
    'tien_do_tinh' => max(0, min(100, $progress)),
  ];
}

function refreshTaskProgress(PDO $db, int $taskStt): void {
  $stmt = $db->prepare("SELECT stt, tien_do, trang_thai, kpi_baoduong_stt, loai_congviec, dinh_muc_gio_thu_cong
              FROM giaoviec_kpi WHERE stt = ? LIMIT 1");
  $stmt->execute([$taskStt]);
  $task = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$task || ($task['trang_thai'] ?? '') === 'hoan_thanh') {
    return;
  }

  $progress = getTaskProgress($db, $task);
  $db->prepare("UPDATE giaoviec_kpi SET tien_do = ? WHERE stt = ?")
    ->execute([$progress['tien_do_tinh'], $taskStt]);
}

$action = $_GET['action'] ?? 'index';

try {
  if (!$userSttSupported) {
    jsonOut([
      'ok' => false,
      'error' => 'Database hiện tại chưa có cột user_stt trong bảng giaoviec_kpi_nguoi.',
      'database' => defined('DB_NAME') ? DB_NAME : null,
      'columns' => getGiaoviecKpiNguoiColumns($db),
      'sql' => 'ALTER TABLE giaoviec_kpi_nguoi ADD COLUMN user_stt INT(11) NULL DEFAULT NULL;',
    ]);
  }

    switch ($action) {

        case 'api_my_tasks':
            [$matchSql, $matchParams] = buildUserMatchCondition($currentUserStt);
            $sql = "SELECT g.stt, g.ten_cong_viec, g.mo_ta, g.ghi_chu, g.phieu, g.somay, g.hoso,
                           g.ngay_bat_dau, g.ngay_ket_thuc, g.tien_do, g.trang_thai,
                           g.kpi_baoduong_stt, g.loai_congviec, g.dinh_muc_gio_thu_cong,
                           h.mavt AS hoso_mavt,
                           k.ten_thiet_bi,
                           (SELECT COALESCE(SUM(tong_gio),0) FROM giaoviec_kpi_thuchien WHERE giaoviec_stt = g.stt) AS gio_da_lam,
                           (SELECT COUNT(*) FROM giaoviec_kpi_thuchien WHERE giaoviec_stt = g.stt) AS so_log
                    FROM giaoviec_kpi g
                    INNER JOIN giaoviec_kpi_nguoi n ON n.giaoviec_stt = g.stt
                    LEFT JOIN hososcbd_iso h ON h.stt = g.hososcbd_stt
                    LEFT JOIN kpi_baoduong_thietbi_iso k ON k.id = g.kpi_baoduong_stt
                    WHERE $matchSql AND n.vai_tro = 'chinh'
                    ORDER BY FIELD(g.trang_thai,'dang_lam','chua_giao','hoan_thanh','huy'), g.ngay_ket_thuc ASC, g.stt DESC";
            $st = $db->prepare($sql);
            $st->execute($matchParams);
            $tasks = $st->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tasks as &$task) {
              $task = array_merge($task, getTaskProgress($db, $task));
            }
            unset($task);
            jsonOut(['ok' => true, 'hoten' => $currentHoten, 'data' => $tasks]);

        case 'api_detail':
            $stt = (int)($_GET['stt'] ?? 0);
            if ($stt <= 0) jsonOut(['ok'=>false,'error'=>'Thiếu stt'], 400);

            // Kiểm tra quyền: phải là người chính của việc này
            [$matchSql, $matchParams] = buildUserMatchCondition($currentUserStt);
            $chk = $db->prepare("SELECT COUNT(*) FROM giaoviec_kpi_nguoi n WHERE n.giaoviec_stt=? AND $matchSql AND n.vai_tro='chinh'");
            $chk->execute(array_merge([$stt], $matchParams));
            if ((int)$chk->fetchColumn() === 0) jsonOut(['ok'=>false,'error'=>'Bạn không phải người thực hiện chính của công việc này'], 403);

            $st = $db->prepare("SELECT g.*, h.mavt AS hoso_mavt, k.ten_thiet_bi
                                FROM giaoviec_kpi g
                                LEFT JOIN hososcbd_iso h ON h.stt = g.hososcbd_stt
                                LEFT JOIN kpi_baoduong_thietbi_iso k ON k.id = g.kpi_baoduong_stt
                                WHERE g.stt = :s");
            $st->execute([':s'=>$stt]);
            $task = $st->fetch(PDO::FETCH_ASSOC);
            if (!$task) jsonOut(['ok'=>false,'error'=>'Không tìm thấy công việc'], 404);
            $task = array_merge($task, getTaskProgress($db, $task));

            $st = $db->prepare("SELECT COALESCE(NULLIF(TRIM(u.hoten), ''), u.username) AS hoten, n.vai_tro
                                FROM giaoviec_kpi_nguoi n
                                INNER JOIN users u ON u.stt = n.user_stt
                                WHERE n.giaoviec_stt=:s
                                ORDER BY n.vai_tro='chinh' DESC, hoten ASC");
            $st->execute([':s'=>$stt]);
            $members = $st->fetchAll(PDO::FETCH_ASSOC);

            $st = $db->prepare("SELECT * FROM giaoviec_kpi_thuchien WHERE giaoviec_stt=:s ORDER BY ngay_lam DESC, stt DESC");
            $st->execute([':s'=>$stt]);
            $logs = $st->fetchAll(PDO::FETCH_ASSOC);

            if ($logs) {
                $ids = array_column($logs, 'stt');
                $in = implode(',', array_fill(0, count($ids), '?'));
                $st = $db->prepare("SELECT thuchien_stt, hoten, so_gio FROM giaoviec_kpi_thuchien_gio WHERE thuchien_stt IN ($in)");
                $st->execute($ids);
                $gioMap = [];
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $gioMap[(int)$r['thuchien_stt']][] = ['hoten'=>$r['hoten'], 'so_gio'=>$r['so_gio']];
                }
                foreach ($logs as &$l) {
                    $l['chi_tiet_gio'] = $gioMap[(int)$l['stt']] ?? [];
                }
                unset($l);
            }

            jsonOut(['ok'=>true, 'is_admin'=>$isAdmin, 'task'=>$task, 'members'=>$members, 'logs'=>$logs]);

        case 'api_add_log':
            $in = json_decode(file_get_contents('php://input'), true) ?: [];
            $stt = (int)($in['giaoviec_stt'] ?? 0);
            $ngay = trim($in['ngay_lam'] ?? '');
            $noiDung = trim((string)($in['noi_dung'] ?? ''));
            $gioList = is_array($in['gio'] ?? null) ? $in['gio'] : [];
            if ($stt <= 0 || $ngay === '') jsonOut(['ok'=>false,'error'=>'Thiếu dữ liệu bắt buộc'], 400);

            $chk = $db->prepare("SELECT trang_thai FROM giaoviec_kpi WHERE stt=:s");
            $chk->execute([':s'=>$stt]);
            $trangThai = $chk->fetchColumn();
            if (!$trangThai) jsonOut(['ok'=>false,'error'=>'Công việc không tồn tại'], 404);
            if (in_array($trangThai, ['hoan_thanh','huy'], true) && !$isAdmin) {
              jsonOut(['ok'=>false,'error'=>'Công việc đã hoàn thành, chỉ admin mới được sửa'], 403);
            }

            [$matchSql, $matchParams] = buildUserMatchCondition($currentUserStt);
            $chk = $db->prepare("SELECT COUNT(*) FROM giaoviec_kpi_nguoi n WHERE n.giaoviec_stt=? AND $matchSql AND n.vai_tro='chinh'");
            if (!$isAdmin) {
              $chk->execute(array_merge([$stt], $matchParams));
              if ((int)$chk->fetchColumn() === 0) jsonOut(['ok'=>false,'error'=>'Bạn không phải người thực hiện chính'], 403);
            }

            $tong = 0.0;
            foreach ($gioList as $item) {
                $so = (float)($item['so_gio'] ?? 0);
                if ($so > 0) $tong += $so;
            }

            $db->beginTransaction();
            try {
                $st = $db->prepare("INSERT INTO giaoviec_kpi_thuchien (giaoviec_stt, ngay_lam, noi_dung, tong_gio, nguoi_nhap)
                                    VALUES (:s,:n,:nd,:tg,:np)");
                $st->execute([':s'=>$stt, ':n'=>$ngay, ':nd'=>$noiDung !== '' ? $noiDung : null, ':tg'=>$tong, ':np'=>$currentHoten]);
                $tcId = (int)$db->lastInsertId();

                $ins = $db->prepare("INSERT INTO giaoviec_kpi_thuchien_gio (thuchien_stt, hoten, so_gio) VALUES (?,?,?)");
                foreach ($gioList as $item) {
                    $ht = trim((string)($item['hoten'] ?? ''));
                    $so = (float)($item['so_gio'] ?? 0);
                    if ($ht === '' || $so <= 0) continue;
                    $ins->execute([$tcId, $ht, $so]);
                }

                if ($trangThai === 'chua_giao') {
                    $db->prepare("UPDATE giaoviec_kpi SET trang_thai='dang_lam' WHERE stt=?")->execute([$stt]);
                }
                refreshTaskProgress($db, $stt);
                $db->commit();
                jsonOut(['ok'=>true, 'stt'=>$tcId]);
            } catch (Throwable $e) {
                $db->rollBack();
                jsonOut(['ok'=>false, 'error'=>$e->getMessage()], 500);
            }

        case 'api_delete_log':
            $tcId = (int)($_POST['stt'] ?? $_GET['stt'] ?? 0);
            if ($tcId <= 0) jsonOut(['ok'=>false,'error'=>'Thiếu stt'], 400);
            $st = $db->prepare("SELECT t.giaoviec_stt, t.nguoi_nhap, g.trang_thai
                      FROM giaoviec_kpi_thuchien t
                      INNER JOIN giaoviec_kpi g ON g.stt = t.giaoviec_stt
                      WHERE t.stt=:s");
            $st->execute([':s'=>$tcId]);
            $log = $st->fetch(PDO::FETCH_ASSOC);
            if (!$log) jsonOut(['ok'=>false,'error'=>'Không tìm thấy log'], 404);
            if (in_array($log['trang_thai'], ['hoan_thanh','huy'], true) && !$isAdmin) {
              jsonOut(['ok'=>false,'error'=>'Công việc đã hoàn thành, chỉ admin mới được sửa'], 403);
            }
            [$matchSql, $matchParams] = buildUserMatchCondition($currentUserStt);
            $chk = $db->prepare("SELECT COUNT(*) FROM giaoviec_kpi_nguoi n WHERE n.giaoviec_stt=? AND $matchSql AND n.vai_tro='chinh'");
            if (!$isAdmin) {
              $chk->execute(array_merge([(int)$log['giaoviec_stt']], $matchParams));
              if ((int)$chk->fetchColumn() === 0) jsonOut(['ok'=>false,'error'=>'Không có quyền xoá log này'], 403);
            }
            $db->beginTransaction();
            $db->prepare("DELETE FROM giaoviec_kpi_thuchien_gio WHERE thuchien_stt=?")->execute([$tcId]);
            $db->prepare("DELETE FROM giaoviec_kpi_thuchien WHERE stt=?")->execute([$tcId]);
            refreshTaskProgress($db, (int)$log['giaoviec_stt']);
            $db->commit();
            jsonOut(['ok'=>true]);

        case 'api_finish':
            $in = json_decode(file_get_contents('php://input'), true) ?: [];
            $stt = (int)($in['giaoviec_stt'] ?? 0);
            if ($stt <= 0) jsonOut(['ok'=>false,'error'=>'Thiếu stt'], 400);
            [$matchSql, $matchParams] = buildUserMatchCondition($currentUserStt);
            $chk = $db->prepare("SELECT COUNT(*) FROM giaoviec_kpi_nguoi n WHERE n.giaoviec_stt=? AND $matchSql AND n.vai_tro='chinh'");
            $chk->execute(array_merge([$stt], $matchParams));
            if ((int)$chk->fetchColumn() === 0) jsonOut(['ok'=>false,'error'=>'Bạn không phải người thực hiện chính'], 403);
            $db->prepare("UPDATE giaoviec_kpi SET trang_thai='hoan_thanh', tien_do=100 WHERE stt=:s")->execute([':s'=>$stt]);
            jsonOut(['ok'=>true]);
    }
} catch (Throwable $e) {
    jsonOut(['ok'=>false, 'error'=>$e->getMessage()], 500);
}

require_once __DIR__ . '/views/layouts/header.php';
?>
<style>
  .cvpage { padding: 1rem; }
  .cv-card { transition: box-shadow .15s; }
  .cv-card:hover { box-shadow: 0 4px 14px rgba(0,0,0,.08); }
  .thuchien-log { border-left: 4px solid #60a5fa; background: #eff6ff; }
  .thuchien-log:hover { border-left-color: #2563eb; }
</style>
<div class="cvpage w-full min-w-0">
  <div class="bg-white rounded-lg shadow p-4 mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="text-xl font-bold text-gray-800"><i class="fas fa-user-clock text-blue-600 mr-2"></i>Công việc của tôi</h1>
      <p class="text-sm text-gray-500">Bạn (<b><?= htmlspecialchars($currentHoten) ?></b>) đang là người thực hiện chính của các công việc dưới đây.</p>
    </div>
    <div class="flex gap-2">
      <select id="filterStatus" class="border rounded px-3 py-2 text-sm">
        <option value="">— Tất cả trạng thái —</option>
        <option value="chua_giao">Chưa bắt đầu</option>
        <option value="dang_lam" selected>Đang thực hiện</option>
        <option value="hoan_thanh">Hoàn thành</option>
      </select>
    </div>
  </div>

  <div id="taskList" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
    <div class="p-6 text-center text-gray-400 col-span-full">Đang tải...</div>
  </div>
</div>

<!-- Modal: Chi tiết + nhật ký thực hiện -->
<div id="modalDetail" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-[100] p-4">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[92vh] flex flex-col overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3 border-b">
      <div>
        <h3 class="font-semibold text-gray-800" id="dTitle">Chi tiết công việc</h3>
        <div class="text-xs text-gray-500" id="dSub"></div>
      </div>
      <button onclick="closeModal('modalDetail')" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times"></i></button>
    </div>
    <div class="p-4 space-y-4 overflow-y-auto flex-1">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
        <div><div class="text-xs text-gray-500">Dự án</div><div id="dPhieu" class="font-medium">-</div></div>
        <div><div class="text-xs text-gray-500">Hồ sơ</div><div id="dHoso" class="font-medium">-</div></div>
        <div><div class="text-xs text-gray-500">Bắt đầu</div><div id="dNgayBd" class="font-medium">-</div></div>
        <div><div class="text-xs text-gray-500">Hạn</div><div id="dNgayKt" class="font-medium">-</div></div>
        <div><div class="text-xs text-gray-500">Định mức</div><div id="dDinhMuc" class="font-medium">-</div></div>
        <div><div class="text-xs text-gray-500">Đã làm</div><div id="dGioLam" class="font-medium">0h</div></div>
        <div><div class="text-xs text-gray-500">Trạng thái</div><div id="dStatus" class="font-medium">-</div></div>
        <div><div class="text-xs text-gray-500">Tiến độ</div><div id="dProgress" class="font-medium">0%</div></div>
      </div>
      <div class="text-xs text-gray-600" id="dGhiChu"></div>

      <div class="border rounded-lg p-3 bg-blue-50/30">
        <div class="font-semibold text-gray-800 mb-2 text-sm"><i class="fas fa-pen text-blue-600 mr-1"></i>Nhập nhật ký thực hiện</div>
        <div class="mb-2">
          <div>
            <label class="text-xs text-gray-600">Ngày làm</label>
            <input type="date" id="l_ngay" class="w-full border rounded px-2 py-1.5 text-sm">
          </div>
        </div>
        <div>
          <div class="text-xs text-gray-600 mb-1">Số giờ làm của từng thành viên</div>
          <div id="l_gio_list" class="grid grid-cols-1 md:grid-cols-2 gap-2"></div>
        </div>
        <div class="mt-2">
          <label class="text-xs text-gray-600">Nội dung thực hiện</label>
          <textarea id="l_noidung" rows="5" placeholder="Mô tả nội dung công việc trong ngày" class="w-full border rounded px-2 py-1.5 text-sm"></textarea>
        </div>
        <div class="text-right mt-2">
          <button id="btnAddLog" onclick="submitLog()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded text-sm">
            <i class="fas fa-plus mr-1"></i>Thêm thực hiện
          </button>
        </div>
      </div>

      <div>
        <div class="flex items-center justify-between mb-2">
          <div class="font-semibold text-gray-800 text-sm"><i class="fas fa-history text-gray-500 mr-1"></i>Nhật ký đã ghi</div>
        </div>
        <div id="logList" class="space-y-2"></div>
      </div>
    </div>
    <div class="px-5 py-3 border-t flex justify-between items-center gap-2 bg-gray-50 rounded-b-lg">
      <div class="text-xs text-gray-500" id="dTotal"></div>
      <div class="flex gap-2">
        <button onclick="closeModal('modalDetail')" class="px-4 py-2 text-sm rounded border">Đóng</button>
        <button id="btnFinish" onclick="finishTask()" class="px-4 py-2 text-sm rounded bg-green-600 text-white hover:bg-green-700">
          <i class="fas fa-flag-checkered mr-1"></i>Kết thúc công việc
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const API = 'congviec_cua_toi.php';
const CURRENT_HOTEN = <?= json_encode($currentHoten, JSON_UNESCAPED_UNICODE) ?>;
let ALL = [];
let CURRENT = null;

const STATUS_LABEL = {
  chua_giao:'Chưa bắt đầu', dang_lam:'Đang thực hiện', hoan_thanh:'Hoàn thành', huy:'Đã huỷ'
};
const STATUS_CSS = {
  chua_giao:'bg-gray-200 text-gray-800', dang_lam:'bg-blue-100 text-blue-800',
  hoan_thanh:'bg-green-100 text-green-800', huy:'bg-gray-100 text-gray-500'
};

function esc(s){ return (s??'').toString().replace(/[&<>"']/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function fmtDate(d){ if(!d) return ''; return d.split('-').reverse().join('/'); }
function openModal(id){ document.getElementById(id).classList.remove('hidden'); document.getElementById(id).classList.add('flex'); }
function closeModal(id){ document.getElementById(id).classList.add('hidden'); document.getElementById(id).classList.remove('flex'); }

async function loadMyTasks(){
  const r = await fetch(`${API}?action=api_my_tasks`).then(r=>r.json());
  if (!r.ok) { document.getElementById('taskList').innerHTML = `<div class="col-span-full p-4 text-red-600 text-sm">${esc(r.error||'Lỗi')}</div>`; return; }
  ALL = r.data || [];
  render();
}

function render(){
  const f = document.getElementById('filterStatus').value;
  const list = ALL.filter(t => !f || t.trang_thai === f);
  const box = document.getElementById('taskList');
  if (!list.length) { box.innerHTML = `<div class="col-span-full p-4 text-center text-gray-400">Không có công việc nào.</div>`; return; }
  box.innerHTML = list.map(t => {
    const ten = [t.hoso_mavt, t.somay].filter(Boolean).join('-') || t.ten_cong_viec;
    const dm = t.dinh_muc_gio_hieu_luc ?? t.dinh_muc_gio_thu_cong ?? '-';
    const dl = Number(t.gio_da_lam||0);
    const st = t.trang_thai;
    return `
    <div class="cv-card bg-white rounded-lg shadow border p-3 flex flex-col gap-2 cursor-pointer" onclick="openDetail(${t.stt})">
      <div class="flex items-start justify-between gap-2">
        <div class="font-semibold text-gray-800">${esc(ten)}</div>
        <span class="px-2 py-0.5 rounded text-xs font-medium ${STATUS_CSS[st]||''}">${esc(STATUS_LABEL[st]||st)}</span>
      </div>
      <div class="text-xs text-gray-500">Phiếu: <b>${esc(t.phieu||'')}</b> · HS: ${esc(t.hoso||'')}</div>
      ${t.ghi_chu ? `<div class="text-xs text-gray-500 italic line-clamp-2">${esc(t.ghi_chu)}</div>` : ''}
      <div class="flex items-center justify-between text-xs mt-1">
        <div>Hạn: <b>${fmtDate(t.ngay_ket_thuc)||'—'}</b></div>
        <div>Đã làm: <b>${dl}h</b>${dm!=='-'?` / ${dm}h`:''}</div>
      </div>
      <div class="w-full bg-gray-200 rounded h-1.5"><div class="bg-blue-500 h-1.5 rounded" style="width:${t.tien_do||0}%"></div></div>
    </div>`;
  }).join('');
}

async function openDetail(stt){
  const r = await fetch(`${API}?action=api_detail&stt=${stt}`).then(r=>r.json());
  if (!r.ok) { alert('Lỗi: '+(r.error||'')); return; }
  CURRENT = r;
  const t = r.task;
  const ten = [t.hoso_mavt, t.somay].filter(Boolean).join('-') || t.ten_cong_viec;
  document.getElementById('dTitle').textContent = ten;
  document.getElementById('dSub').textContent = t.mo_ta || '';
  document.getElementById('dPhieu').textContent = t.phieu || '-';
  document.getElementById('dHoso').textContent = t.hoso || '-';
  document.getElementById('dNgayBd').textContent = fmtDate(t.ngay_bat_dau) || '-';
  document.getElementById('dNgayKt').textContent = fmtDate(t.ngay_ket_thuc) || '-';
  const effectiveHours = t.dinh_muc_gio_hieu_luc ?? t.dinh_muc_gio_thu_cong;
  document.getElementById('dDinhMuc').textContent = (effectiveHours ?? '-') + (effectiveHours ? 'h' : '');
  document.getElementById('dStatus').innerHTML = `<span class="px-2 py-0.5 rounded text-xs ${STATUS_CSS[t.trang_thai]||''}">${esc(STATUS_LABEL[t.trang_thai]||t.trang_thai)}</span>`;
  document.getElementById('dProgress').textContent = (t.tien_do||0)+'%';
  document.getElementById('dGhiChu').textContent = t.ghi_chu ? 'Ghi chú: ' + t.ghi_chu : '';

  const totalGio = (r.logs||[]).reduce((s,l)=>s+Number(l.tong_gio||0),0);
  document.getElementById('dGioLam').textContent = totalGio + 'h';
  document.getElementById('dTotal').textContent = `Tổng giờ đã ghi: ${totalGio}h · ${r.logs.length} log`;

  // ngày hôm nay mặc định
  const today = new Date().toISOString().substring(0,10);
  document.getElementById('l_ngay').value = today;
  document.getElementById('l_noidung').value = '';

  // Render input giờ theo từng thành viên
  const gioBox = document.getElementById('l_gio_list');
  gioBox.innerHTML = (r.members||[]).map(m => `
    <div class="flex items-center gap-2 border rounded px-2 py-1 bg-white">
      <div class="text-sm flex-1 truncate">
        <span class="${m.vai_tro==='chinh'?'font-semibold text-blue-700':''}">${esc(m.hoten)}</span>
        <span class="text-xs text-gray-500">(${m.vai_tro==='chinh'?'Chính':'Phụ'})</span>
      </div>
      <input type="number" step="0.25" min="0" value="0"
             class="l_gio_input w-24 border rounded px-2 py-1 text-sm text-right" data-hoten="${esc(m.hoten)}">
      <span class="text-xs text-gray-500">giờ</span>
    </div>`).join('') || '<div class="text-xs text-gray-400 italic">Chưa có thành viên</div>';

  renderLogs(r.logs || []);

  const finished = ['hoan_thanh','huy'].includes(t.trang_thai);
  const canEdit = !finished || r.is_admin;
  document.getElementById('btnAddLog').classList.toggle('hidden', !canEdit);
  document.getElementById('btnFinish').classList.toggle('hidden', finished || !canEdit);
  openModal('modalDetail');
}

function renderLogs(logs){
  const box = document.getElementById('logList');
  if (!logs.length) { box.innerHTML = '<div class="text-xs text-gray-400 italic">Chưa có log nào.</div>'; return; }
  const canEdit = !['hoan_thanh','huy'].includes(CURRENT?.task?.trang_thai) || CURRENT?.is_admin;
  box.innerHTML = logs.map(l => {
    const gio = (l.chi_tiet_gio||[]).map(g => `<span class="inline-block bg-white border border-blue-100 text-gray-700 rounded px-2 py-0.5 text-xs mr-1 mb-1">${esc(g.hoten)}: <b class="text-blue-700">${g.so_gio}h</b></span>`).join('');
    return `
    <div class="thuchien-log border rounded p-2">
      <div class="flex items-center justify-between text-blue-900">
        <div class="text-sm"><i class="fas fa-calendar-day text-blue-600 mr-1"></i><b>${fmtDate(l.ngay_lam)}</b> · <span class="text-gray-600">${esc(l.nguoi_nhap||'')}</span> · <span class="bg-blue-100 rounded px-1.5 py-0.5">Tổng <b>${l.tong_gio}h</b></span></div>
        ${canEdit ? `<button onclick="delLog(${l.stt})" class="text-red-500 bg-red-50 hover:bg-red-100 rounded px-2 py-1 text-xs" title="Xóa nhật ký"><i class="fas fa-trash"></i></button>` : ''}
      </div>
      ${l.noi_dung ? `<div class="text-sm text-gray-700 bg-white/70 rounded px-2 py-1 mt-2">${esc(l.noi_dung)}</div>` : ''}
      <div class="mt-2">${gio}</div>
    </div>`;
  }).join('');
}

async function submitLog(){
  if (!CURRENT) return;
  if (['hoan_thanh','huy'].includes(CURRENT.task.trang_thai) && !CURRENT.is_admin) {
    alert('Công việc đã hoàn thành, chỉ admin mới được sửa');
    return;
  }
  const stt = CURRENT.task.stt;
  const ngay = document.getElementById('l_ngay').value;
  const noiDung = document.getElementById('l_noidung').value.trim();
  if (!ngay) { alert('Vui lòng chọn ngày làm'); return; }
  const gio = [...document.querySelectorAll('.l_gio_input')].map(inp => ({
    hoten: inp.dataset.hoten,
    so_gio: parseFloat(inp.value||'0') || 0
  })).filter(g => g.so_gio > 0);
  if (!gio.length) { if (!confirm('Chưa có thành viên nào có giờ làm > 0. Vẫn lưu log?')) return; }
  const r = await fetch(`${API}?action=api_add_log`, {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ giaoviec_stt: stt, ngay_lam: ngay, noi_dung: noiDung, gio })
  }).then(r=>r.json());
  if (!r.ok) { alert('Lỗi: '+(r.error||'')); return; }
  await openDetail(stt);
  await loadMyTasks();
}

async function delLog(id){
  if (!confirm('Xoá log này?')) return;
  const r = await fetch(`${API}?action=api_delete_log&stt=${id}`, { method:'POST' }).then(r=>r.json());
  if (!r.ok) { alert('Lỗi: '+r.error); return; }
  if (CURRENT) await openDetail(CURRENT.task.stt);
  await loadMyTasks();
}

async function finishTask(){
  if (!CURRENT) return;
  const stt = CURRENT.task.stt;
  if (!confirm('Xác nhận KẾT THÚC công việc? Trạng thái sẽ chuyển sang Hoàn thành.')) return;
  const r = await fetch(`${API}?action=api_finish`, {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ giaoviec_stt: stt })
  }).then(r=>r.json());
  if (!r.ok) { alert('Lỗi: '+(r.error||'')); return; }
  closeModal('modalDetail');
  await loadMyTasks();
}

document.getElementById('filterStatus').addEventListener('change', render);

loadMyTasks();
</script>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
