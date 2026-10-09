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
$sttParam = (int)($_GET['stt'] ?? 0);
$canCreate = hasPermission(PERMISSION_GIAOVIEC_KPI_CREATE);
$canEdit = hasPermission(PERMISSION_GIAOVIEC_KPI_EDIT);
$canDelete = hasPermission(PERMISSION_GIAOVIEC_KPI_DELETE);

if ($sttParam === 0 && !$canCreate) {
    http_response_code(403);
    echo 'Bạn không có quyền tạo công việc KPI';
    exit;
}

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

$title = 'Chi tiết công việc';
require_once __DIR__ . '/views/layouts/header.php';
?>
<style>
  .detail-page { width: 100%; min-width: 0; }
  .child-table { min-width: 960px; }
  .child-table tr.child-row.is-new > td { background-color: #f0fdf4; }
  .child-table tr.child-row.is-dirty > td { background-color: #fffbeb; }
  .child-table tr.child-row.has-error > td { background-color: #fef2f2; }
  .child-table input, .child-table select { width: 100%; }
</style>

<div class="detail-page">
  <div class="bg-white rounded-lg shadow p-4 mb-4">
    <div class="text-sm mb-2 flex flex-wrap items-center gap-2 text-gray-500">
      <a href="giaoviec_kpi.php" class="text-blue-600 hover:underline"><i class="fas fa-arrow-left mr-1"></i>Danh sách công việc</a>
      <span id="parentCrumb"></span>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="min-w-0">
        <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2 flex-wrap">
          <i id="pageIcon" class="fas fa-folder-open text-blue-600"></i>
          <span id="pageTitle">Công việc</span>
          <span id="pageBadge"></span>
        </h1>
        <p id="pageSub" class="text-sm text-gray-500"></p>
      </div>
      <div class="flex gap-2">
        <button id="btnDeleteTask" type="button" onclick="deleteCurrent()" class="hidden px-3 py-2 text-sm rounded border border-red-300 text-red-600 hover:bg-red-50">
          <i class="fas fa-trash mr-1"></i>Xóa công việc
        </button>
      </div>
    </div>
  </div>

  <div id="notFound" class="hidden bg-white rounded-lg shadow p-6 text-center text-gray-500">Không tìm thấy công việc.</div>

  <div id="mainArea" class="space-y-4">
    <!-- ===== Thông tin công việc + Người thực hiện ===== -->
    <div class="bg-white rounded-lg shadow">
      <div class="px-5 py-3 border-b font-semibold text-gray-800"><i class="fas fa-edit text-blue-600 mr-2"></i>Thông tin công việc</div>
      <div id="mainForm" class="p-5 space-y-3">
        <input type="hidden" id="f_hososcbd_stt">
        <div>
          <label class="text-sm font-medium text-gray-700">Dự án (Phiếu) — chọn hồ sơ SCBD</label>
          <input list="dl_hoso" id="f_hoso_search" placeholder="Nhập phiếu / mã VT / số máy / hồ sơ để tìm..." class="w-full border rounded px-3 py-2 mt-1 text-sm">
          <datalist id="dl_hoso"></datalist>
          <div id="f_hoso_info" class="text-xs text-gray-500 mt-1"></div>
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700">Tên công việc <span class="text-red-500">*</span></label>
          <input type="text" id="f_ten" class="w-full border rounded px-3 py-2 mt-1 text-sm" placeholder="VD: Sửa máy XYZ - Hồ sơ 123">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="text-sm font-medium text-gray-700">Mô tả</label>
            <textarea id="f_mota" rows="2" class="w-full border rounded px-3 py-2 mt-1 text-sm"></textarea>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700">Ghi chú</label>
            <textarea id="f_ghi_chu" rows="2" class="w-full border rounded px-3 py-2 mt-1 text-sm" placeholder="Thông tin bổ sung, lưu ý, điều kiện thực hiện..."></textarea>
          </div>
        </div>

        <div class="border border-teal-200 bg-teal-50 rounded px-3 py-2 flex flex-wrap items-center gap-x-3 gap-y-2">
          <span class="flex items-center gap-1 text-teal-700 font-semibold text-xs whitespace-nowrap"><i class="fas fa-bullseye"></i>Định mức KPI</span>
          <input type="hidden" id="f_dinh_muc_gio_thu_cong" value="">
          <input list="dl_kpi_thietbi" id="f_kpi_baoduong_search" placeholder="Thiết bị..." autocomplete="off" title="Thiết bị" class="flex-1 min-w-[160px] border rounded px-2 py-1 text-xs">
          <input type="hidden" id="f_kpi_baoduong_stt">
          <datalist id="dl_kpi_thietbi">
            <?php foreach ($kpiThietBiList as $kpiRow): ?>
              <option data-id="<?= (int)$kpiRow['id'] ?>" value="<?= htmlspecialchars((string)$kpiRow['ten_thiet_bi']) ?>"></option>
            <?php endforeach; ?>
          </datalist>
          <select id="f_loai_congviec" title="Loại công việc" class="border rounded px-2 py-1 text-xs">
            <?php foreach ($loaiCongViecLabels as $key => $label): ?>
              <option value="<?= htmlspecialchars($key) ?>" <?= $key === 'kiem_tra' ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
          <span class="text-xs text-teal-700 whitespace-nowrap">Tham khảo: <b id="f_kpi_preview">—</b></span>
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
            <input type="number" id="f_so_ngay" min="0" step="1" value="0" class="w-full border rounded px-2 py-2 text-sm">
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
          <div class="col-span-2 md:col-span-4 text-sm text-teal-700">
            <label for="f_dinh_muc_gio_hien_tai" class="font-medium">Thời gian giao thực tế:</label>
            <input type="number" id="f_dinh_muc_gio_hien_tai" min="0" step="0.01" class="ml-2 w-32 border rounded px-2 py-1 text-sm text-gray-800">
          </div>
        </div>

        <div id="nguoiBox" class="border border-purple-200 bg-purple-50 rounded-lg p-3 space-y-2">
          <div class="flex items-center gap-2 text-purple-700 font-semibold text-sm">
            <i class="fas fa-user-plus"></i><span>Người thực hiện</span>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-medium text-gray-700">Người làm <b>chính</b></label>
              <select id="n_chinh" class="w-full border rounded px-2 py-2 mt-1 text-sm bg-white"></select>
            </div>
            <div>
              <label class="text-xs font-medium text-gray-700">Người làm <b>phụ</b></label>
              <div id="n_phu_tags" class="flex flex-wrap gap-1 mt-2"></div>
            </div>
          </div>
        </div>
      </div>
      <div class="need-edit px-5 py-3 border-t flex justify-end gap-2 bg-gray-50 rounded-b-lg">
        <a href="giaoviec_kpi.php" class="px-4 py-2 text-sm rounded border bg-white">Quay lại</a>
        <button type="button" onclick="saveMain()" class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">
          <i class="fas fa-save mr-1"></i>Lưu công việc
        </button>
      </div>
    </div>

    <!-- ===== Công việc con ===== -->
    <div id="childrenCard" class="bg-white rounded-lg shadow hidden">
      <div class="px-5 py-3 border-b flex flex-wrap items-center justify-between gap-2">
        <div class="font-semibold text-gray-800"><i class="fas fa-sitemap text-green-600 mr-2"></i>Công việc con <span id="childCount" class="ml-1 px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800"></span></div>
        <div class="need-create flex flex-wrap gap-2">
          <button type="button" onclick="addChildRows([''])" class="px-3 py-1.5 text-sm rounded border border-green-600 text-green-700 hover:bg-green-50"><i class="fas fa-plus mr-1"></i>Thêm dòng</button>
          <button type="button" onclick="saveChildren()" class="px-3 py-1.5 text-sm rounded bg-green-600 text-white hover:bg-green-700"><i class="fas fa-save mr-1"></i>Lưu việc con</button>
        </div>
      </div>
      <div class="need-create px-5 py-3 border-b bg-gray-50">
        <label class="text-xs font-medium text-gray-600">Thêm nhanh nhiều việc con — mỗi dòng một tên</label>
        <div class="flex flex-col md:flex-row gap-2 mt-1">
          <textarea id="quickNames" rows="3" class="flex-1 border rounded px-3 py-2 text-sm" placeholder="Kiểm tra ngoại quan&#10;Vệ sinh&#10;Hiệu chuẩn"></textarea>
          <button type="button" onclick="quickAddChildren()" class="md:self-start px-4 py-2 text-sm rounded bg-gray-700 text-white hover:bg-gray-800 whitespace-nowrap"><i class="fas fa-list mr-1"></i>Thêm vào bảng</button>
        </div>
        <p class="text-xs text-gray-500 mt-1">Dòng mới tự lấy ngày/giờ của công việc cha. Chọn người thực hiện từng dòng rồi bấm "Lưu việc con".</p>
      </div>
      <div class="overflow-x-auto">
        <table class="child-table w-full text-sm">
          <thead class="bg-gray-50 text-gray-700">
            <tr>
              <th class="px-3 py-2 text-left w-[28%]">Tên việc con</th>
              <th class="px-3 py-2 text-left w-[18%]">Người thực hiện chính</th>
              <th class="px-3 py-2 text-left w-[16%]">Bắt đầu</th>
              <th class="px-3 py-2 text-left w-[16%]">Hạn hoàn thành</th>
              <th class="px-3 py-2 text-left w-[12%]">Trạng thái</th>
              <th class="px-3 py-2 text-center w-24">Thao tác</th>
            </tr>
          </thead>
          <tbody id="childBody"></tbody>
        </table>
        <div id="childEmpty" class="p-6 text-center text-gray-400 text-sm hidden">Chưa có công việc con.</div>
      </div>
    </div>
  </div>
</div>

<script>
const API = 'giaoviec_kpi.php';
const TASK_STT = <?= (int)$sttParam ?>;
const CAN_CREATE = <?= $canCreate ? 'true' : 'false' ?>;
const CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;
const CAN_DELETE = <?= $canDelete ? 'true' : 'false' ?>;
const KPI_HOUR_MAP = <?= json_encode($kpiHourPreviewMap, JSON_UNESCAPED_UNICODE) ?>;
const STATUS_LABEL = <?= json_encode([
  'chua_giao'=>'Cần giao', 'den_han'=>'Đến hạn',
  'qua_han'=>'Quá hạn', 'dang_lam'=>'Đang thực hiện', 'hoan_thanh'=>'Hoàn thành', 'huy'=>'Đã hủy'
], JSON_UNESCAPED_UNICODE) ?>;
const STATUS_CSS = {
  chua_giao:'bg-gray-200 text-gray-800',
  den_han:'bg-orange-100 text-orange-800', qua_han:'bg-red-100 text-red-700',
  dang_lam:'bg-blue-100 text-blue-800', hoan_thanh:'bg-green-100 text-green-800', huy:'bg-gray-100 text-gray-500'
};
const CHILD_STATUS_OPTIONS = [
  ['chua_giao', 'Cần giao'], ['dang_lam', 'Đang thực hiện'], ['hoan_thanh', 'Hoàn thành'], ['huy', 'Đã hủy']
];

let ALL_TASKS = [];
let USERS_LIST = [];
let HOSO_LIST = [];
let TASK = null;

const $ = id => document.getElementById(id);
function esc(s){ return (s??'').toString().replace(/[&<>"']/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function fmtDate(d){ if(!d) return ''; return d.split('-').reverse().join('/'); }
function taskUrl(stt){ return `giaoviec_kpi_detail.php?stt=${stt}`; }

async function postJson(action, body){
  return fetch(`${API}?action=${action}`, {
    method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)
  }).then(r => r.json());
}

/* ========= Tải dữ liệu ========= */
async function loadUsers(){
  const r = await fetch(`${API}?action=api_users`).then(r=>r.json());
  USERS_LIST = (r.data || []).map(u => ({
    stt: Number(u.stt || 0),
    name: String(u.display_name || u.label || '').trim(),
  })).filter(u => u.stt > 0 && u.name !== '');
}
async function loadHoso(){
  const r = await fetch(`${API}?action=api_hososcbd`).then(r=>r.json());
  HOSO_LIST = r.data || [];
  $('dl_hoso').innerHTML = HOSO_LIST.map(h => `<option data-stt="${h.stt}" value="${esc(h.phieu)} — ${esc(h.mavt)} — ${esc(h.somay)} — ${esc(h.hoso)}"></option>`).join('');
}
async function loadTasks(){
  const r = await fetch(`${API}?action=api_list`).then(r=>r.json());
  ALL_TASKS = r.data || [];
  TASK = TASK_STT ? (ALL_TASKS.find(t => Number(t.stt) === TASK_STT) || null) : null;
}

/* ========= Tính giờ / ngày ========= */
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

function countBusinessDays(startDate, endDate){
  if (!startDate || !endDate) return 0;
  const startDay = new Date(`${startDate}T00:00:00Z`);
  const endDay = new Date(`${endDate}T00:00:00Z`);
  if (Number.isNaN(startDay.getTime()) || Number.isNaN(endDay.getTime()) || endDay < startDay) return 0;
  let n = 0;
  for (const day = new Date(startDay); day <= endDay; day.setUTCDate(day.getUTCDate() + 1)) {
    const wd = day.getUTCDay();
    if (wd !== 0 && wd !== 6) n++;
  }
  return n;
}

function scheduleFields(ngayBd, gioBd, ngayKt, gioKt){
  const hours = calculateScheduledHours(ngayBd, gioBd, ngayKt, gioKt);
  return {
    so_ngay: countBusinessDays(ngayBd, ngayKt),
    dinh_muc_gio_hien_tai: (hours !== null && Number.isFinite(hours) && hours > 0) ? Number(hours.toFixed(2)) : null,
  };
}

function updateScheduleDerivedDayCount(){
  $('f_so_ngay').value = String(countBusinessDays($('f_ngay_bd').value, $('f_ngay_kt').value));
}
function updateScheduleDerivedHours(){
  $('f_dinh_muc_gio_hien_tai').value =
    scheduleFields($('f_ngay_bd').value, $('f_gio_bd').value, $('f_ngay_kt').value, $('f_gio_kt').value).dinh_muc_gio_hien_tai ?? '';
}

/* ========= Thiết bị KPI ========= */
const KPI_TB_NAME_BY_ID = {};
const KPI_TB_ID_BY_NAME = {};
[...$('dl_kpi_thietbi').options].forEach(opt => {
  const id = opt.getAttribute('data-id');
  if (id) {
    KPI_TB_NAME_BY_ID[id] = opt.value;
    KPI_TB_ID_BY_NAME[opt.value.toLowerCase()] = id;
  }
});

function setKpiThietBiById(id){
  const sid = id ? String(id) : '';
  $('f_kpi_baoduong_stt').value = sid;
  $('f_kpi_baoduong_search').value = sid && KPI_TB_NAME_BY_ID[sid] ? KPI_TB_NAME_BY_ID[sid] : '';
}
function syncKpiSearchToHidden(){
  const v = ($('f_kpi_baoduong_search').value || '').trim().toLowerCase();
  $('f_kpi_baoduong_stt').value = v && KPI_TB_ID_BY_NAME[v] ? KPI_TB_ID_BY_NAME[v] : '';
  updateKpiPreview();
}
function updateKpiPreview(){
  const kpiId = $('f_kpi_baoduong_stt').value;
  const loai = $('f_loai_congviec').value;
  const manual = $('f_dinh_muc_gio_thu_cong').value;
  const preview = $('f_kpi_preview');
  if (manual !== '') { preview.textContent = `${manual}h`; return; }
  if (kpiId && KPI_HOUR_MAP[kpiId] && KPI_HOUR_MAP[kpiId][loai] !== null && KPI_HOUR_MAP[kpiId][loai] !== undefined) {
    preview.textContent = `${KPI_HOUR_MAP[kpiId][loai]}h`;
    return;
  }
  preview.textContent = '—';
}

async function loadKpiBySelectedHoso(stt){
  if (!stt) return;
  const r = await fetch(`${API}?action=api_kpi_by_hoso&stt=${encodeURIComponent(stt)}`).then(r => r.json());
  const data = r && r.data ? r.data : null;
  if (!data) return;
  setKpiThietBiById(data.kpi_baoduong_stt || '');
  $('f_dinh_muc_gio_thu_cong').value = '';
  updateKpiPreview();
}

/* ========= Form chính ========= */
function hosoLabel(t){
  return t.phieu ? `${t.phieu} — ${t.hoso_mavt||t.mavt||''} — ${t.somay||''} — ${t.hoso||''}` : '';
}

function userOptionsHtml(selected, allowBlank){
  const sel = selected ? String(selected) : '';
  return (allowBlank ? '<option value="">— Chọn người —</option>' : '')
    + USERS_LIST.map(u => `<option value="${u.stt}" ${String(u.stt) === sel ? 'selected' : ''}>${esc(u.name)}</option>`).join('');
}

function renderPhuTags(){
  const phu = TASK ? (TASK.nguoi_list || []).filter(n => n.vai_tro === 'phu') : [];
  $('n_phu_tags').innerHTML = phu.length
    ? phu.map(n => `<span class="inline-flex items-center bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">${esc(n.hoten)}</span>`).join('')
    : '<span class="text-xs text-gray-400 italic">Chưa có</span>';
}

function fillForm(){
  const t = TASK;
  if (!t) {
    $('f_loai_congviec').value = 'kiem_tra';
    $('n_chinh').innerHTML = userOptionsHtml('', true);
    renderPhuTags();
    updateKpiPreview();
    return;
  }
  $('f_hososcbd_stt').value = t.hososcbd_stt || '';
  $('f_hoso_search').value = hosoLabel(t);
  $('f_ten').value = t.ten_cong_viec || '';
  $('f_mota').value = t.mo_ta || '';
  $('f_ghi_chu').value = t.ghi_chu || '';
  $('f_ngay_bd').value = t.ngay_bat_dau || '';
  $('f_gio_bd').value = (t.gio_bat_dau||'').substring(0,5);
  $('f_so_ngay').value = t.so_ngay || 0;
  $('f_ngay_kt').value = t.ngay_ket_thuc || '';
  $('f_gio_kt').value = (t.gio_ket_thuc||'').substring(0,5);
  const hrs = t.dinh_muc_gio_hien_tai;
  $('f_dinh_muc_gio_hien_tai').value = hrs !== null && hrs !== undefined && hrs !== '' ? hrs : '';
  if ($('f_dinh_muc_gio_hien_tai').value === '') updateScheduleDerivedHours();
  $('f_tien_do').value = t.tien_do || 0;
  $('f_trang_thai').value = t.trang_thai || 'chua_giao';
  $('f_loai_congviec').value = t.loai_congviec || 'kiem_tra';
  $('f_dinh_muc_gio_thu_cong').value =
    t.dinh_muc_gio_thu_cong !== null && t.dinh_muc_gio_thu_cong !== undefined && t.dinh_muc_gio_thu_cong !== '' ? String(t.dinh_muc_gio_thu_cong) : '';
  setKpiThietBiById(t.kpi_baoduong_stt || '');
  const chinh = (t.nguoi_list || []).find(n => n.vai_tro === 'chinh');
  $('n_chinh').innerHTML = userOptionsHtml(chinh ? chinh.user_stt : '', !chinh);
  renderPhuTags();
  updateKpiPreview();

  // Hồ sơ gắn cố định sau khi tạo; tên công việc gốc cũng khóa như trước đây
  const lockHoso = $('f_hoso_search');
  lockHoso.readOnly = true;
  lockHoso.classList.add('bg-gray-100', 'cursor-not-allowed');
  if (!t.parent_stt) {
    $('f_ten').readOnly = true;
    $('f_ten').classList.add('bg-gray-100', 'cursor-not-allowed');
  }
  $('f_hoso_info').textContent = t.phieu ? `Phiếu ${t.phieu} · Máy ${t.somay||''} · HS ${t.hoso||''}` : '';
}

function resolveHoso(hsStt){
  const h = hsStt ? HOSO_LIST.find(x => String(x.stt) === String(hsStt)) : null;
  if (h) return { phieu: h.phieu || '', somay: h.somay || '', hoso: h.hoso || '' };
  return { phieu: TASK?.phieu || '', somay: TASK?.somay || '', hoso: TASK?.hoso || '' };
}

function collectMainPayload(){
  const hsStt = $('f_hososcbd_stt').value;
  return {
    stt: TASK_STT,
    parent_stt: TASK?.parent_stt || null,
    hososcbd_stt: hsStt || null,
    ...resolveHoso(hsStt),
    kpi_baoduong_stt: $('f_kpi_baoduong_stt').value || null,
    loai_congviec: $('f_loai_congviec').value || null,
    dinh_muc_gio_thu_cong: $('f_dinh_muc_gio_thu_cong').value || null,
    ten_cong_viec: $('f_ten').value.trim(),
    mo_ta: $('f_mota').value.trim(),
    ghi_chu: $('f_ghi_chu').value.trim(),
    ngay_bat_dau: $('f_ngay_bd').value || null,
    gio_bat_dau: $('f_gio_bd').value || null,
    so_ngay: Number($('f_so_ngay').value || 0),
    ngay_ket_thuc: $('f_ngay_kt').value || null,
    gio_ket_thuc: $('f_gio_kt').value || null,
    dinh_muc_gio_hien_tai: $('f_dinh_muc_gio_hien_tai').value || null,
    tien_do: parseInt($('f_tien_do').value || '0', 10),
    trang_thai: $('f_trang_thai').value,
  };
}

async function assignPeople(stt, chinh, phu){
  return postJson('api_save_nguoi', { giaoviec_stt: stt, chinh, phu });
}

async function saveMain(){
  const payload = collectMainPayload();
  if (!payload.ten_cong_viec) { alert('Vui lòng nhập tên công việc'); return; }
  const r = await postJson('api_save', payload);
  if (!r.ok) { alert('Lỗi: ' + (r.error || '')); return; }
  const stt = Number(r.stt);

  const chinh = Number($('n_chinh').value || 0);
  if (chinh > 0) {
    const phu = TASK ? (TASK.nguoi_list || []).filter(n => n.vai_tro === 'phu').map(n => Number(n.user_stt)) : [];
    const rn = await assignPeople(stt, chinh, phu);
    if (!rn.ok) { alert('Đã lưu công việc nhưng lỗi khi lưu người thực hiện: ' + (rn.error || '')); }
  }

  if (!TASK_STT) { location.replace(taskUrl(stt) + '#children'); return; }
  await loadTasks();
  renderAll();
  alert('Đã lưu.');
}

async function deleteCurrent(){
  if (!TASK) return;
  if (!confirm('Xóa công việc này (kèm các công việc con và người thực hiện)?')) return;
  const r = await fetch(`${API}?action=api_delete&stt=${TASK.stt}`, { method:'POST' }).then(r=>r.json());
  if (!r.ok) { alert('Lỗi: ' + r.error); return; }
  location.href = TASK.parent_stt ? taskUrl(TASK.parent_stt) : 'giaoviec_kpi.php';
}

/* ========= Công việc con ========= */
function childTasks(){
  return ALL_TASKS.filter(c => Number(c.parent_stt) === TASK_STT);
}

function timeVal(v){ return (v || '').substring(0,5); }

function childRowHtml(c){
  const chinh = c ? (c.nguoi_list || []).find(n => n.vai_tro === 'chinh') : null;
  const base = c || TASK;
  const ngayBd = c ? (c.ngay_bat_dau || '') : (base.ngay_bat_dau || '');
  const gioBd = c ? timeVal(c.gio_bat_dau) : timeVal(base.gio_bat_dau);
  const ngayKt = c ? (c.ngay_ket_thuc || '') : (base.ngay_ket_thuc || '');
  const gioKt = c ? timeVal(c.gio_ket_thuc) : timeVal(base.gio_ket_thuc);
  const status = c ? (c.trang_thai || 'chua_giao') : 'chua_giao';
  const statusOpts = CHILD_STATUS_OPTIONS.map(([v, l]) => `<option value="${v}" ${v === status ? 'selected' : ''}>${l}</option>`).join('');
  return `<td class="px-3 py-2"><input type="text" class="c-ten border rounded px-2 py-1 text-sm" value="${esc(c ? c.ten_cong_viec : '')}" placeholder="Tên việc con"></td>
    <td class="px-3 py-2"><select class="c-chinh border rounded px-2 py-1 text-sm bg-white">${userOptionsHtml(chinh ? chinh.user_stt : '', !chinh)}</select></td>
    <td class="px-3 py-2"><div class="flex gap-1"><input type="date" class="c-ngay-bd border rounded px-1 py-1 text-sm" value="${esc(ngayBd)}"><input type="time" class="c-gio-bd border rounded px-1 py-1 text-sm w-24" value="${esc(gioBd)}"></div></td>
    <td class="px-3 py-2"><div class="flex gap-1"><input type="date" class="c-ngay-kt border rounded px-1 py-1 text-sm" value="${esc(ngayKt)}"><input type="time" class="c-gio-kt border rounded px-1 py-1 text-sm w-24" value="${esc(gioKt)}"></div></td>
    <td class="px-3 py-2"><select class="c-trang-thai border rounded px-2 py-1 text-sm bg-white">${statusOpts}</select></td>
    <td class="px-3 py-2 text-center whitespace-nowrap c-actions"></td>`;
}

function renderRowActions(tr){
  const stt = Number(tr.dataset.stt);
  const err = tr.dataset.error ? `<div class="text-xs text-red-600 whitespace-normal text-left mb-1">${esc(tr.dataset.error)}</div>` : '';
  let btns = '';
  if (stt > 0) {
    btns += `<a href="${taskUrl(stt)}" title="Chi tiết / sửa đầy đủ" class="text-blue-600 hover:text-blue-800 px-1"><i class="fas fa-external-link-alt"></i></a>`;
    if (CAN_DELETE) btns += `<button type="button" onclick="deleteChildRow(this)" title="Xóa" class="text-red-500 hover:text-red-700 px-1"><i class="fas fa-trash"></i></button>`;
  } else {
    btns += `<button type="button" onclick="removeNewRow(this)" title="Bỏ dòng" class="text-gray-500 hover:text-red-600 px-1"><i class="fas fa-times"></i></button>`;
  }
  tr.querySelector('.c-actions').innerHTML = err + btns;
}

function markRowState(tr){
  const stt = Number(tr.dataset.stt);
  tr.classList.toggle('is-new', stt === 0);
  tr.classList.toggle('is-dirty', stt > 0 && tr.dataset.dirty === '1');
  tr.classList.toggle('has-error', !!tr.dataset.error);
}

function buildChildRow(c){
  const tr = document.createElement('tr');
  tr.className = 'child-row border-t';
  tr.dataset.stt = c ? String(c.stt) : '0';
  tr.dataset.dirty = '0';
  tr.innerHTML = childRowHtml(c);
  const touch = () => { tr.dataset.dirty = '1'; markRowState(tr); };
  tr.addEventListener('input', touch);
  tr.addEventListener('change', touch);
  renderRowActions(tr);
  markRowState(tr);
  return tr;
}

function updateChildMeta(){
  const n = $('childBody').querySelectorAll('tr.child-row').length;
  $('childCount').textContent = n;
  $('childEmpty').classList.toggle('hidden', n > 0);
}

function renderChildren(){
  const body = $('childBody');
  body.innerHTML = '';
  childTasks().forEach(c => body.appendChild(buildChildRow(c)));
  updateChildMeta();
}

function addChildRows(names){
  const body = $('childBody');
  let last = null;
  names.forEach(n => {
    last = buildChildRow(null);
    last.querySelector('.c-ten').value = n;
    body.appendChild(last);
  });
  updateChildMeta();
  if (last) last.querySelector('.c-ten').focus();
}

function quickAddChildren(){
  const names = $('quickNames').value.split(/\r?\n/).map(s => s.trim()).filter(Boolean);
  if (!names.length) { alert('Nhập ít nhất một tên việc con'); return; }
  addChildRows(names);
  $('quickNames').value = '';
}

function removeNewRow(btn){
  btn.closest('tr').remove();
  updateChildMeta();
}

async function deleteChildRow(btn){
  const tr = btn.closest('tr');
  if (!confirm('Xóa công việc con này?')) return;
  const r = await fetch(`${API}?action=api_delete&stt=${tr.dataset.stt}`, { method:'POST' }).then(r=>r.json());
  if (!r.ok) { alert('Lỗi: ' + r.error); return; }
  tr.remove();
  await loadTasks();
  updateChildMeta();
}

function taskToPayload(t){
  const hrs = t.dinh_muc_gio_hien_tai;
  return {
    stt: Number(t.stt),
    parent_stt: t.parent_stt || null,
    hososcbd_stt: t.hososcbd_stt || null,
    phieu: t.phieu || '', somay: t.somay || '', hoso: t.hoso || '',
    kpi_baoduong_stt: t.kpi_baoduong_stt || null,
    loai_congviec: t.loai_congviec || null,
    dinh_muc_gio_thu_cong: t.dinh_muc_gio_thu_cong ? String(t.dinh_muc_gio_thu_cong) : null,
    ten_cong_viec: t.ten_cong_viec || '',
    mo_ta: t.mo_ta || '',
    ghi_chu: t.ghi_chu || '',
    ngay_bat_dau: t.ngay_bat_dau || null,
    gio_bat_dau: timeVal(t.gio_bat_dau) || null,
    so_ngay: Number(t.so_ngay || 0),
    ngay_ket_thuc: t.ngay_ket_thuc || null,
    gio_ket_thuc: timeVal(t.gio_ket_thuc) || null,
    dinh_muc_gio_hien_tai: hrs !== null && hrs !== undefined && hrs !== '' ? hrs : null,
    tien_do: Number(t.tien_do || 0),
    trang_thai: t.trang_thai || 'chua_giao',
  };
}

function buildChildPayload(tr, existing){
  const ngayBd = tr.querySelector('.c-ngay-bd').value || null;
  const gioBd = tr.querySelector('.c-gio-bd').value || null;
  const ngayKt = tr.querySelector('.c-ngay-kt').value || null;
  const gioKt = tr.querySelector('.c-gio-kt').value || null;
  const base = existing ? taskToPayload(existing) : {
    stt: 0,
    parent_stt: TASK_STT,
    hososcbd_stt: TASK.hososcbd_stt || null,
    phieu: TASK.phieu || '', somay: TASK.somay || '', hoso: TASK.hoso || '',
    kpi_baoduong_stt: null,
    loai_congviec: 'kiem_tra',
    dinh_muc_gio_thu_cong: null,
    mo_ta: '', ghi_chu: '', tien_do: 0,
  };
  const scheduleChanged = !existing
    || (existing.ngay_bat_dau || null) !== ngayBd
    || (timeVal(existing.gio_bat_dau) || null) !== gioBd
    || (existing.ngay_ket_thuc || null) !== ngayKt
    || (timeVal(existing.gio_ket_thuc) || null) !== gioKt;
  const sched = scheduleChanged ? scheduleFields(ngayBd, gioBd, ngayKt, gioKt) : {};
  return {
    ...base,
    ten_cong_viec: tr.querySelector('.c-ten').value.trim(),
    ngay_bat_dau: ngayBd, gio_bat_dau: gioBd,
    ngay_ket_thuc: ngayKt, gio_ket_thuc: gioKt,
    trang_thai: tr.querySelector('.c-trang-thai').value,
    ...sched,
  };
}

async function saveChildren(){
  const rows = [...$('childBody').querySelectorAll('tr.child-row')];
  const done = [];
  let failed = 0;
  let skipped = 0;
  for (const tr of rows) {
    const stt = Number(tr.dataset.stt);
    const isNew = stt === 0;
    if (!isNew && tr.dataset.dirty !== '1') continue;
    if (isNew && !tr.querySelector('.c-ten').value.trim()) { skipped++; continue; }

    tr.dataset.error = '';
    const existing = isNew ? null : ALL_TASKS.find(t => Number(t.stt) === stt);
    if (!isNew && !existing) { tr.dataset.error = 'Không tìm thấy dữ liệu gốc'; failed++; markRowState(tr); renderRowActions(tr); continue; }
    if (!tr.querySelector('.c-ten').value.trim()) { tr.dataset.error = 'Thiếu tên việc con'; failed++; markRowState(tr); renderRowActions(tr); continue; }

    try {
      const r = await postJson('api_save', buildChildPayload(tr, existing));
      if (!r.ok) throw new Error(r.error || 'Lỗi lưu');
      const newStt = Number(r.stt);

      const chinh = Number(tr.querySelector('.c-chinh').value || 0);
      const oldChinh = existing ? (existing.nguoi_list || []).find(n => n.vai_tro === 'chinh') : null;
      if (chinh > 0 && (!oldChinh || Number(oldChinh.user_stt) !== chinh)) {
        const phu = existing ? (existing.nguoi_list || []).filter(n => n.vai_tro === 'phu').map(n => Number(n.user_stt)) : [];
        const rn = await assignPeople(newStt, chinh, phu);
        if (!rn.ok) {
          tr.dataset.stt = String(newStt);
          throw new Error('Đã lưu việc con nhưng lỗi gán người: ' + (rn.error || ''));
        }
      }
      tr.dataset.stt = String(newStt);
      tr.dataset.dirty = '0';
      tr.dataset.error = '';
      done.push(tr);
    } catch (e) {
      tr.dataset.error = e.message;
      failed++;
    }
    markRowState(tr);
    renderRowActions(tr);
  }

  await loadTasks();
  done.forEach(tr => {
    const t = ALL_TASKS.find(x => Number(x.stt) === Number(tr.dataset.stt));
    if (t) {
      tr.querySelector('.c-trang-thai').value = t.trang_thai || 'chua_giao';
      const chinh = (t.nguoi_list || []).find(n => n.vai_tro === 'chinh');
      tr.querySelector('.c-chinh').innerHTML = userOptionsHtml(chinh ? chinh.user_stt : '', !chinh);
    }
    markRowState(tr);
    renderRowActions(tr);
  });
  updateChildMeta();

  if (failed) alert(`Đã lưu ${done.length} việc con, ${failed} dòng bị lỗi (xem thông báo đỏ trong bảng).`);
  else if (done.length) alert(`Đã lưu ${done.length} việc con.`);
  else alert('Không có thay đổi nào để lưu.');
}

/* ========= Hiển thị ========= */
function renderHeader(){
  const t = TASK;
  if (!t) {
    $('pageTitle').textContent = 'Thêm công việc';
    $('pageSub').textContent = 'Nhập thông tin công việc. Sau khi lưu có thể thêm công việc con.';
    return;
  }
  const isChild = !!t.parent_stt;
  const name = isChild ? (t.ten_cong_viec || '') : ([t.hoso_mavt, t.somay].filter(Boolean).join('-') || t.ten_cong_viec);
  $('pageIcon').className = isChild ? 'fas fa-level-up-alt fa-rotate-90 text-green-600' : 'fas fa-folder-open text-blue-600';
  $('pageTitle').textContent = name;
  const s = t.trang_thai_hien_thi;
  $('pageBadge').innerHTML = `<span class="px-2 py-0.5 rounded text-xs font-medium ${STATUS_CSS[s]||''}">${esc(STATUS_LABEL[s]||s||'')}</span>`
    + (isChild ? ' <span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-800">Việc con</span>' : '');
  $('pageSub').textContent = [t.hoso ? `Hồ sơ ${t.hoso}` : '', t.nhomsc ? `Nhóm ${t.nhomsc}` : '', t.nguoi_giao ? `Người giao: ${t.nguoi_giao}` : '']
    .filter(Boolean).join(' · ');

  if (isChild) {
    const p = ALL_TASKS.find(x => Number(x.stt) === Number(t.parent_stt));
    const pName = p ? ([p.hoso_mavt, p.somay].filter(Boolean).join('-') || p.ten_cong_viec) : `#${t.parent_stt}`;
    $('parentCrumb').innerHTML = `<span class="text-gray-400">/</span> <a href="${taskUrl(t.parent_stt)}" class="text-blue-600 hover:underline"><i class="fas fa-folder-open mr-1"></i>${esc(pName)}</a>`;
  }
  if (CAN_DELETE) $('btnDeleteTask').classList.remove('hidden');
}

function applyPermissions(){
  const editable = TASK ? CAN_EDIT : CAN_CREATE;
  if (!editable) {
    $('mainForm').querySelectorAll('input, select, textarea').forEach(el => { el.disabled = true; });
    document.querySelectorAll('#mainArea > div:first-child .need-edit').forEach(el => el.classList.add('hidden'));
  }
  if (!CAN_CREATE || !CAN_EDIT) document.querySelectorAll('#childrenCard .need-create').forEach(el => el.classList.add('hidden'));
}

function renderAll(){
  renderHeader();
  fillForm();
  const showChildren = !!TASK && !TASK.parent_stt;
  $('childrenCard').classList.toggle('hidden', !showChildren);
  if (showChildren) renderChildren();
}

/* ========= Sự kiện ========= */
['f_ngay_bd','f_ngay_kt'].forEach(id => {
  $(id).addEventListener('input', updateScheduleDerivedDayCount);
  $(id).addEventListener('change', updateScheduleDerivedDayCount);
});
['f_ngay_bd','f_gio_bd','f_ngay_kt','f_gio_kt'].forEach(id => {
  $(id).addEventListener('input', updateScheduleDerivedHours);
  $(id).addEventListener('change', updateScheduleDerivedHours);
});
$('f_kpi_baoduong_search').addEventListener('input', syncKpiSearchToHidden);
$('f_kpi_baoduong_search').addEventListener('change', syncKpiSearchToHidden);
$('f_loai_congviec').addEventListener('change', updateKpiPreview);

// Chọn hồ sơ từ datalist → điền tên công việc + hososcbd_stt + thiết bị KPI
$('f_hoso_search').addEventListener('input', function(){
  const opt = [...$('dl_hoso').options].find(o => o.value === this.value);
  if (!opt) return;
  const h = HOSO_LIST.find(x => String(x.stt) === String(opt.getAttribute('data-stt')));
  if (!h) return;
  $('f_hososcbd_stt').value = h.stt;
  if (!$('f_ten').value) $('f_ten').value = `${h.mavt||''}-${h.somay||''}`;
  $('f_hoso_info').textContent = `Phiếu ${h.phieu} · Máy ${h.somay} · HS ${h.hoso}`;
  loadKpiBySelectedHoso(h.stt);
});

(async function init(){
  try {
    await Promise.all([loadUsers(), loadHoso(), loadTasks()]);
  } catch (e) {
    alert('Không tải được dữ liệu: ' + e.message);
    return;
  }
  if (TASK_STT && !TASK) {
    $('mainArea').classList.add('hidden');
    $('notFound').classList.remove('hidden');
    return;
  }
  renderAll();
  applyPermissions();
  if (location.hash === '#children' && !$('childrenCard').classList.contains('hidden')) {
    $('childrenCard').scrollIntoView({ behavior: 'smooth' });
  } else if (location.hash === '#nguoi') {
    $('nguoiBox').scrollIntoView({ behavior: 'smooth' });
  }
})();
</script>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
