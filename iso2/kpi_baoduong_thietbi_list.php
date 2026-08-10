<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/config/database.php';

requireAuth();

$canViewKpi = hasPermission('kpi_baoduong.view')
    || hasPermission('kehoachbaoduong.view')
    || hasPermission('kehoachbaoduong.create');
$canImportKpi = hasPermission('kpi_baoduong.import')
    || hasPermission('kehoachbaoduong.create');
$canEditKpi = hasPermission('kpi_baoduong.edit')
    || hasPermission('kehoachbaoduong.edit');

if (!$canViewKpi) {
    http_response_code(403);
    die('Khong co quyen xem du lieu KPI bao duong');
}

$title = 'Danh sách KPI bảo dưỡng thiết bị';

$importUnlockError = '';
$editSuccessMessage = '';
$editErrorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_unlock_action'])) {
    if (!$canImportKpi) {
        http_response_code(403);
        die('Khong co quyen mo khoa import du lieu');
    }

    $unlockAction = (string)($_POST['import_unlock_action'] ?? '');
    if ($unlockAction === 'lock') {
        unset($_SESSION['kpi_baoduong_import_unlocked']);
        header('Location: kpi_baoduong_thietbi_list.php');
        exit;
    }

    $unlockPassword = (string)($_POST['import_unlock_password'] ?? '');
    if (hash_equals('iso2@lock', $unlockPassword)) {
        $_SESSION['kpi_baoduong_import_unlocked'] = true;
        header('Location: kpi_baoduong_thietbi_list.php');
        exit;
    }

    $importUnlockError = 'Sai mat khau mo khoa Import du lieu';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kpi_edit_action'])) {
    if (!$canEditKpi) {
        http_response_code(403);
        die('Khong co quyen sua du lieu KPI bao duong');
    }

    try {
        $db = getDBConnection();

        $id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;
        if ($id <= 0) {
            throw new RuntimeException('ID ban ghi khong hop le');
        }

        $tenThietBi = trim((string)($_POST['ten_thiet_bi'] ?? ''));
        if ($tenThietBi === '') {
            throw new RuntimeException('Ten thiet bi khong duoc de trong');
        }

        $parseNullableInt = static function (string $key): ?int {
            $raw = trim((string)($_POST[$key] ?? ''));
            if ($raw === '') {
                return null;
            }
            if (!preg_match('/^-?\d+$/', $raw)) {
                throw new RuntimeException('Gia tri so nguyen khong hop le cho truong: ' . $key);
            }
            return (int)$raw;
        };

        $parseNullableDecimal = static function (string $key): ?float {
            $raw = trim((string)($_POST[$key] ?? ''));
            if ($raw === '') {
                return null;
            }
            $normalized = str_replace(',', '.', $raw);
            if (!is_numeric($normalized)) {
                throw new RuntimeException('Gia tri so thuc khong hop le cho truong: ' . $key);
            }
            return (float)$normalized;
        };

        $cleanText = static function (string $key): ?string {
            $value = trim((string)($_POST[$key] ?? ''));
            return $value === '' ? null : $value;
        };

        $checkStmt = $db->prepare('SELECT id FROM kpi_baoduong_thietbi_iso WHERE id = :id LIMIT 1');
        $checkStmt->execute([':id' => $id]);
        if (!$checkStmt->fetch()) {
            throw new RuntimeException('Khong tim thay dong du lieu can sua');
        }

        $sql = 'UPDATE kpi_baoduong_thietbi_iso SET
                    stt_hien_thi = :stt_hien_thi,
                    ten_thiet_bi = :ten_thiet_bi,
                    kiem_tra_nhan_cong = :kiem_tra_nhan_cong,
                    kiem_tra_so_gio = :kiem_tra_so_gio,
                    kiem_tra_nguoi_thuc_hien = :kiem_tra_nguoi_thuc_hien,
                    kiem_tra_noi_dung = :kiem_tra_noi_dung,
                    bd_cap_1_nhan_cong = :bd_cap_1_nhan_cong,
                    bd_cap_1_so_gio = :bd_cap_1_so_gio,
                    bd_cap_1_nguoi_thuc_hien = :bd_cap_1_nguoi_thuc_hien,
                    bd_cap_1_noi_dung = :bd_cap_1_noi_dung,
                    bd_cap_2_tan_suat_thang = :bd_cap_2_tan_suat_thang,
                    bd_cap_2_nhan_cong = :bd_cap_2_nhan_cong,
                    bd_cap_2_so_gio = :bd_cap_2_so_gio,
                    bd_cap_2_nguoi_thuc_hien = :bd_cap_2_nguoi_thuc_hien,
                    bd_cap_2_noi_dung = :bd_cap_2_noi_dung,
                    bd_cap_3_tan_suat_thang = :bd_cap_3_tan_suat_thang,
                    bd_cap_3_nhan_cong = :bd_cap_3_nhan_cong,
                    bd_cap_3_so_gio = :bd_cap_3_so_gio,
                    bd_cap_3_nguoi_thuc_hien = :bd_cap_3_nguoi_thuc_hien,
                    bd_cap_3_noi_dung = :bd_cap_3_noi_dung,
                    hieu_chuan_tan_suat_thang = :hieu_chuan_tan_suat_thang,
                    hieu_chuan_nhan_cong = :hieu_chuan_nhan_cong,
                    hieu_chuan_so_gio = :hieu_chuan_so_gio,
                    hieu_chuan_nguoi_thuc_hien = :hieu_chuan_nguoi_thuc_hien,
                    hieu_chuan_noi_dung = :hieu_chuan_noi_dung,
                    ghi_chu = :ghi_chu
                WHERE id = :id';

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':stt_hien_thi' => $parseNullableInt('stt_hien_thi'),
            ':ten_thiet_bi' => $tenThietBi,
            ':kiem_tra_nhan_cong' => $parseNullableDecimal('kiem_tra_nhan_cong'),
            ':kiem_tra_so_gio' => $parseNullableDecimal('kiem_tra_so_gio'),
            ':kiem_tra_nguoi_thuc_hien' => $cleanText('kiem_tra_nguoi_thuc_hien'),
            ':kiem_tra_noi_dung' => $cleanText('kiem_tra_noi_dung'),
            ':bd_cap_1_nhan_cong' => $parseNullableDecimal('bd_cap_1_nhan_cong'),
            ':bd_cap_1_so_gio' => $parseNullableDecimal('bd_cap_1_so_gio'),
            ':bd_cap_1_nguoi_thuc_hien' => $cleanText('bd_cap_1_nguoi_thuc_hien'),
            ':bd_cap_1_noi_dung' => $cleanText('bd_cap_1_noi_dung'),
            ':bd_cap_2_tan_suat_thang' => $parseNullableInt('bd_cap_2_tan_suat_thang'),
            ':bd_cap_2_nhan_cong' => $parseNullableDecimal('bd_cap_2_nhan_cong'),
            ':bd_cap_2_so_gio' => $parseNullableDecimal('bd_cap_2_so_gio'),
            ':bd_cap_2_nguoi_thuc_hien' => $cleanText('bd_cap_2_nguoi_thuc_hien'),
            ':bd_cap_2_noi_dung' => $cleanText('bd_cap_2_noi_dung'),
            ':bd_cap_3_tan_suat_thang' => $parseNullableInt('bd_cap_3_tan_suat_thang'),
            ':bd_cap_3_nhan_cong' => $parseNullableDecimal('bd_cap_3_nhan_cong'),
            ':bd_cap_3_so_gio' => $parseNullableDecimal('bd_cap_3_so_gio'),
            ':bd_cap_3_nguoi_thuc_hien' => $cleanText('bd_cap_3_nguoi_thuc_hien'),
            ':bd_cap_3_noi_dung' => $cleanText('bd_cap_3_noi_dung'),
            ':hieu_chuan_tan_suat_thang' => $parseNullableInt('hieu_chuan_tan_suat_thang'),
            ':hieu_chuan_nhan_cong' => $parseNullableDecimal('hieu_chuan_nhan_cong'),
            ':hieu_chuan_so_gio' => $parseNullableDecimal('hieu_chuan_so_gio'),
            ':hieu_chuan_nguoi_thuc_hien' => $cleanText('hieu_chuan_nguoi_thuc_hien'),
            ':hieu_chuan_noi_dung' => $cleanText('hieu_chuan_noi_dung'),
            ':ghi_chu' => $cleanText('ghi_chu'),
        ]);

        $_SESSION['kpi_edit_success'] = 'Cap nhat du lieu thanh cong';
        header('Location: ' . ($_SERVER['REQUEST_URI'] ?? 'kpi_baoduong_thietbi_list.php'));
        exit;
    } catch (Throwable $e) {
        $editErrorMessage = 'Khong the cap nhat du lieu: ' . $e->getMessage();
    }
}

if (isset($_SESSION['kpi_edit_success'])) {
    $editSuccessMessage = (string)$_SESSION['kpi_edit_success'];
    unset($_SESSION['kpi_edit_success']);
}

$isImportUnlocked = !empty($_SESSION['kpi_baoduong_import_unlocked']);

// ten_thiet_bi khong co FK, chi khop ten voi thietbi_iso.tenvt qua text (LIKE)
$search = trim((string)($_GET['search'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

try {
    $db = getDBConnection();

    $check = $db->query("SHOW TABLES LIKE 'kpi_baoduong_thietbi_iso'");
    if ($check === false || !$check->fetch()) {
        $migrationPath = __DIR__ . '/migrations/20260714_create_kpi_baoduong_thietbi_iso.sql';
        if (is_file($migrationPath)) {
            $db->exec(trim((string)file_get_contents($migrationPath)));
        }
    }

    $whereSql = '';
    $params = [];
    if ($search !== '') {
        $whereSql = 'WHERE ten_thiet_bi LIKE :search';
        $params[':search'] = '%' . $search . '%';
    }

    $countStmt = $db->prepare("SELECT COUNT(*) FROM kpi_baoduong_thietbi_iso {$whereSql}");
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRows / $perPage));

    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    $dataSql = "
        SELECT
            id,
            stt_hien_thi,
            ten_thiet_bi,
            kiem_tra_nhan_cong,
            kiem_tra_so_gio,
            kiem_tra_nguoi_thuc_hien,
            kiem_tra_noi_dung,
            bd_cap_1_nhan_cong,
            bd_cap_1_so_gio,
            bd_cap_1_nguoi_thuc_hien,
            bd_cap_1_noi_dung,
            bd_cap_2_tan_suat_thang,
            bd_cap_2_nhan_cong,
            bd_cap_2_so_gio,
            bd_cap_2_nguoi_thuc_hien,
            bd_cap_2_noi_dung,
            bd_cap_3_tan_suat_thang,
            bd_cap_3_nhan_cong,
            bd_cap_3_so_gio,
            bd_cap_3_nguoi_thuc_hien,
            bd_cap_3_noi_dung,
            hieu_chuan_tan_suat_thang,
            hieu_chuan_nhan_cong,
            hieu_chuan_so_gio,
            hieu_chuan_nguoi_thuc_hien,
            hieu_chuan_noi_dung,
            ghi_chu,
            created_by,
            created_at,
            updated_at
        FROM kpi_baoduong_thietbi_iso
        {$whereSql}
        ORDER BY COALESCE(stt_hien_thi, id) ASC, id DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $db->prepare($dataSql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $items = [];
    $totalRows = 0;
    $totalPages = 1;
    $page = 1;
    $errorMessage = 'Khong the tai du lieu KPI bao duong: ' . $e->getMessage();
}

require_once __DIR__ . '/views/layouts/header.php';
?>

<style>
.excel-shell {
    background: transparent;
}

.excel-surface {
    background: transparent;
    border: 0;
    box-shadow: none;
}

.excel-table {
    --device-col-width: 160px;
    border-collapse: collapse;
    width: 100%;
    min-width: 2200px;
    table-layout: auto;
    font-size: 14px;
    font-family: 'Segoe UI', sans-serif;
}

.excel-table th,
.excel-table td {
    border: 1px solid #e5e7eb;
    padding: 6px 8px;
    vertical-align: middle;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.excel-table tbody td {
    white-space: normal;
    word-break: break-word;
}

.excel-table thead th {
    white-space: nowrap;
}

.excel-table thead th:first-child,
.excel-table tbody td.sticky-left {
    min-width: var(--device-col-width) !important;
    width: var(--device-col-width) !important;
    max-width: var(--device-col-width) !important;
}

.excel-table thead tr:first-child th {
    background: #e2e8f0;
    font-weight: 700;
    text-align: center;
}

.excel-table thead tr:nth-child(2) th {
    background: #f1f5f9;
    font-weight: 700;
    text-align: center;
}

.excel-table thead tr:nth-child(3) th {
    background: #f3f4f6;
    font-weight: 700;
    text-align: center;
}

.excel-table tbody tr:nth-child(even) td {
    background: #fafafa;
}

.excel-table tbody tr:hover td {
    background: #eff6ff;
}

.kpi-view-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.kpi-view-btn {
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #334155;
    border-radius: 999px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 700;
    transition: all 0.18s ease;
}

.kpi-view-btn:hover {
    border-color: #93c5fd;
    background: #eff6ff;
    color: #1d4ed8;
}

.kpi-view-btn.is-active {
    background: #1d4ed8;
    border-color: #1d4ed8;
    color: #fff;
}

.import-lock-note {
    font-size: 12px;
    color: #64748b;
}

.excel-table .is-hidden {
    display: none !important;
}

.heatmap-cell {
    font-weight: 700;
}

.kpi-legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    display: inline-block;
}

.kpi-scale-viewport {
    width: 100%;
    overflow-x: auto;
    overflow-y: visible;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 20;
}

.sticky-left {
    position: sticky;
    left: 0;
    z-index: 10;
    background: #ffffff;
}

.sticky-left-2 {
    position: sticky;
    left: 72px;
    z-index: 10;
    background: #ffffff;
}

.excel-table tbody tr:nth-child(even) .sticky-left,
.excel-table tbody tr:nth-child(even) .sticky-left-2 {
    background: #fafafa;
}

.excel-table tbody tr:hover .sticky-left,
.excel-table tbody tr:hover .sticky-left-2 {
    background: #eff6ff;
}

@media print {
    .no-print,
    header,
    nav,
    aside,
    .sidebar {
        display: none !important;
    }

    body {
        background: #fff !important;
    }

    .excel-shell,
    .excel-surface {
        background: #fff !important;
        box-shadow: none !important;
        border: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
    }
}
</style>

<div class="container mx-auto px-4 py-6 excel-shell">
    <div class="excel-surface rounded-lg p-4 mb-5">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6 no-print">
        <div>
            <h1 class="text-2xl font-bold flex items-center text-slate-800">
                <i class="fas fa-table mr-2 text-green-700"></i> Bảng KPI bảo dưỡng thiết bị
            </h1>
        </div>
        <div class="flex gap-2 flex-wrap">
            <?php if ($canImportKpi): ?>
                <a href="<?php echo $isImportUnlocked ? 'import_kpi_baoduong_thietbi.php' : '#'; ?>"
                   class="bg-green-600 text-white px-4 py-2 rounded text-sm font-semibold <?php echo $isImportUnlocked ? 'hover:bg-green-700' : 'opacity-50 cursor-not-allowed pointer-events-none'; ?>"
                   <?php echo $isImportUnlocked ? '' : 'aria-disabled="true"'; ?>>
                    <i class="fas fa-file-import mr-1"></i> Import dữ liệu
                </a>
                <?php if (!$isImportUnlocked): ?>
                    <button type="button"
                            id="unlockImportBtn"
                            data-unlocked="0"
                            class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded text-sm font-semibold">
                        <i class="fas fa-lock mr-1"></i>
                        Mở khóa Import
                    </button>
                <?php else: ?>
                    <button type="button"
                            id="lockImportBtn"
                            class="bg-amber-700 hover:bg-amber-800 text-white px-4 py-2 rounded text-sm font-semibold">
                        <i class="fas fa-lock mr-1"></i>
                        Khóa lại Import
                    </button>
                <?php endif; ?>
                <form method="POST" id="unlockImportForm" class="hidden">
                    <input type="hidden" name="import_unlock_action" id="unlockImportAction" value="unlock">
                    <input type="hidden" name="import_unlock_password" id="unlockImportPassword" value="">
                </form>
            <?php endif; ?>
            <a href="download_template_kpi_baoduong_thietbi.php" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm font-semibold">
                <i class="fas fa-download mr-1"></i> Tải mẫu Excel
            </a>
        </div>
    </div>

    <?php if ($importUnlockError !== ''): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 no-print">
            <?php echo htmlspecialchars($importUnlockError, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($editSuccessMessage !== ''): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4 no-print">
            <?php echo htmlspecialchars($editSuccessMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($editErrorMessage !== ''): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 no-print">
            <?php echo htmlspecialchars($editErrorMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6 no-print">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div class="md:col-span-3">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tìm theo tên thiết bị</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" class="w-full border rounded px-3 py-2" placeholder="Ví dụ: MBH, máy hút...">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-semibold">
                    <i class="fas fa-search mr-1"></i> Tìm
                </button>
                <a href="kpi_baoduong_thietbi_list.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded font-semibold">
                    <i class="fas fa-undo mr-1"></i> Bỏ lọc
                </a>
            </div>
        </div>
    </form>

    <?php if (!empty($errorMessage)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow overflow-visible">
        <div class="px-4 py-3 border-b flex items-center justify-between no-print">
            <div class="text-sm text-gray-600">
                Tổng số: <span class="font-semibold text-gray-900"><?php echo number_format($totalRows); ?></span> dòng
            </div>
            <div class="text-sm text-gray-600">
                Trang <span class="font-semibold text-gray-900"><?php echo $page; ?></span> / <span class="font-semibold text-gray-900"><?php echo $totalPages; ?></span>
            </div>
        </div>

        <div class="px-4 py-3 border-b bg-slate-50 no-print flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div class="kpi-view-toolbar">
                <button type="button" class="kpi-view-btn is-active" data-kpi-view="all">Toàn bộ</button>
                <button type="button" class="kpi-view-btn" data-kpi-view="kt">Kiểm tra</button>
                <button type="button" class="kpi-view-btn" data-kpi-view="bd1">BD cấp 1</button>
                <button type="button" class="kpi-view-btn" data-kpi-view="bd2">BD cấp 2</button>
                <button type="button" class="kpi-view-btn" data-kpi-view="bd3">BD cấp 3</button>
                <button type="button" class="kpi-view-btn" data-kpi-view="hc">Hiệu chuẩn</button>
            </div>
            <div class="text-xs text-slate-600 flex items-center gap-2">
                <span class="font-semibold">Heatmap:</span>
                <span class="kpi-legend-dot" style="background:#d9f99d;"></span><span>Thấp</span>
                <span class="kpi-legend-dot" style="background:#fde68a;"></span><span>Trung bình</span>
                <span class="kpi-legend-dot" style="background:#fca5a5;"></span><span>Cao</span>
            </div>
        </div>

        <div id="kpiTableViewport" class="kpi-scale-viewport">
            <table class="excel-table" id="kpiDataTable">
                <thead>
                    <tr>
                        <th rowspan="3" class="sticky-top" style="min-width:160px; width:160px;">Tên thiết bị</th>
                        <th colspan="4" data-group="kt">Kiểm tra thiết bị</th>
                        <th colspan="4" data-group="bd1">Bảo dưỡng thường xuyên (BD cấp 1)</th>
                        <th colspan="5" data-group="bd2">Bảo dưỡng định kỳ (BD cấp 2)</th>
                        <th colspan="5" data-group="bd3">Bảo dưỡng định kỳ (BD cấp 3)</th>
                        <th colspan="5" data-group="hc">Hiệu chuẩn thiết bị</th>
                        <?php if ($canEditKpi): ?>
                            <th rowspan="3" class="no-print sticky-top" style="min-width:84px; width:84px;">Thao tác</th>
                        <?php endif; ?>
                    </tr>
                    <tr>
                        <th data-group="kt" style="min-width:8ch; width:8ch; max-width:8ch;">SL nhân công</th><th data-group="kt" style="min-width:8ch; width:8ch; max-width:8ch;">Số giờ</th><th data-group="kt" style="min-width:20ch; width:20ch; max-width:20ch;">Người thực hiện</th><th data-group="kt">Nội dung chính</th>
                        <th rowspan="2" data-group="bd1" style="min-width:8ch; width:8ch; max-width:8ch;">SL nhân công</th><th rowspan="2" data-group="bd1" style="min-width:8ch; width:8ch; max-width:8ch;">Số giờ</th><th rowspan="2" data-group="bd1" style="min-width:20ch; width:20ch; max-width:20ch;">Người thực hiện</th><th rowspan="2" data-group="bd1">Nội dung chính</th>
                        <th data-group="bd2">Tần suất</th><th data-group="bd2" style="min-width:8ch; width:8ch; max-width:8ch;">SL nhân công</th><th data-group="bd2" style="min-width:8ch; width:8ch; max-width:8ch;">Số giờ</th><th data-group="bd2" style="min-width:20ch; width:20ch; max-width:20ch;">Người thực hiện</th><th data-group="bd2">Nội dung chính</th>
                        <th data-group="bd3">Tần suất</th><th data-group="bd3" style="min-width:8ch; width:8ch; max-width:8ch;">SL nhân công</th><th data-group="bd3" style="min-width:8ch; width:8ch; max-width:8ch;">Số giờ</th><th data-group="bd3" style="min-width:20ch; width:20ch; max-width:20ch;">Người thực hiện</th><th data-group="bd3">Nội dung chính</th>
                        <th data-group="hc">Tần suất</th><th data-group="hc" style="min-width:8ch; width:8ch; max-width:8ch;">SL nhân công</th><th data-group="hc" style="min-width:8ch; width:8ch; max-width:8ch;">Số giờ</th><th data-group="hc" style="min-width:20ch; width:20ch; max-width:20ch;">Người thực hiện</th><th data-group="hc">Nội dung chính</th>
                    </tr>
                    <tr>
                        <th data-group="kt">1</th><th data-group="kt">2</th><th data-group="kt">3</th><th data-group="kt">4</th>
                        <th data-group="bd2">9</th><th data-group="bd2">10</th><th data-group="bd2">11</th><th data-group="bd2">12</th><th data-group="bd2">13</th>
                        <th data-group="bd3">14</th><th data-group="bd3">15</th><th data-group="bd3">16</th><th data-group="bd3">17</th><th data-group="bd3">18</th>
                        <th data-group="hc">19</th><th data-group="hc">20</th><th data-group="hc">21</th><th data-group="hc">22</th><th data-group="hc">23</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="<?php echo $canEditKpi ? '25' : '24'; ?>" class="border px-4 py-8 text-center text-gray-500">Không có dữ liệu</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <?php $editPayload = htmlspecialchars((string)json_encode($item, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>
                            <tr>
                                <td class="font-medium sticky-left" style="min-width:160px; width:160px; white-space: normal; line-height: 1.3; word-break: break-word;"><?php echo htmlspecialchars((string)($item['ten_thiet_bi'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-right heatmap-cell" data-group="kt" data-heat-type="nhan-cong" data-heat="<?php echo $item['kiem_tra_nhan_cong'] !== null ? (float)$item['kiem_tra_nhan_cong'] : ''; ?>" style="min-width:8ch; width:8ch; max-width:8ch;"><?php echo $item['kiem_tra_nhan_cong'] !== null ? number_format((float)$item['kiem_tra_nhan_cong'], 2) : ''; ?></td>
                                <td class="text-right heatmap-cell" data-group="kt" data-heat-type="so-gio" data-heat="<?php echo $item['kiem_tra_so_gio'] !== null ? (float)$item['kiem_tra_so_gio'] : ''; ?>" style="min-width:8ch; width:8ch; max-width:8ch;"><?php echo $item['kiem_tra_so_gio'] !== null ? number_format((float)$item['kiem_tra_so_gio'], 2) : ''; ?></td>
                                <td data-group="kt" style="min-width:20ch; width:20ch; max-width:20ch;"><?php echo htmlspecialchars((string)($item['kiem_tra_nguoi_thuc_hien'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td data-group="kt" style="min-width:260px; width:260px; white-space: normal; line-height: 1.35;"><?php echo htmlspecialchars((string)($item['kiem_tra_noi_dung'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>

                                <td class="text-right heatmap-cell" data-group="bd1" data-heat-type="nhan-cong" data-heat="<?php echo $item['bd_cap_1_nhan_cong'] !== null ? (float)$item['bd_cap_1_nhan_cong'] : ''; ?>" style="min-width:8ch; width:8ch; max-width:8ch;"><?php echo $item['bd_cap_1_nhan_cong'] !== null ? number_format((float)$item['bd_cap_1_nhan_cong'], 2) : ''; ?></td>
                                <td class="text-right heatmap-cell" data-group="bd1" data-heat-type="so-gio" data-heat="<?php echo $item['bd_cap_1_so_gio'] !== null ? (float)$item['bd_cap_1_so_gio'] : ''; ?>" style="min-width:8ch; width:8ch; max-width:8ch;"><?php echo $item['bd_cap_1_so_gio'] !== null ? number_format((float)$item['bd_cap_1_so_gio'], 2) : ''; ?></td>
                                <td data-group="bd1" style="min-width:20ch; width:20ch; max-width:20ch;"><?php echo htmlspecialchars((string)($item['bd_cap_1_nguoi_thuc_hien'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td data-group="bd1" style="min-width:260px; width:260px; white-space: normal; line-height: 1.35;"><?php echo htmlspecialchars((string)($item['bd_cap_1_noi_dung'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>

                                <td class="text-center" data-group="bd2" style="min-width:86px; width:86px;"><?php echo htmlspecialchars((string)($item['bd_cap_2_tan_suat_thang'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-right heatmap-cell" data-group="bd2" data-heat-type="nhan-cong" data-heat="<?php echo $item['bd_cap_2_nhan_cong'] !== null ? (float)$item['bd_cap_2_nhan_cong'] : ''; ?>" style="min-width:8ch; width:8ch; max-width:8ch;"><?php echo $item['bd_cap_2_nhan_cong'] !== null ? number_format((float)$item['bd_cap_2_nhan_cong'], 2) : ''; ?></td>
                                <td class="text-right heatmap-cell" data-group="bd2" data-heat-type="so-gio" data-heat="<?php echo $item['bd_cap_2_so_gio'] !== null ? (float)$item['bd_cap_2_so_gio'] : ''; ?>" style="min-width:8ch; width:8ch; max-width:8ch;"><?php echo $item['bd_cap_2_so_gio'] !== null ? number_format((float)$item['bd_cap_2_so_gio'], 2) : ''; ?></td>
                                <td data-group="bd2" style="min-width:20ch; width:20ch; max-width:20ch;"><?php echo htmlspecialchars((string)($item['bd_cap_2_nguoi_thuc_hien'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td data-group="bd2" style="min-width:260px; width:260px; white-space: normal; line-height: 1.35;"><?php echo htmlspecialchars((string)($item['bd_cap_2_noi_dung'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>

                                <td class="text-center" data-group="bd3" style="min-width:86px; width:86px;"><?php echo htmlspecialchars((string)($item['bd_cap_3_tan_suat_thang'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-right heatmap-cell" data-group="bd3" data-heat-type="nhan-cong" data-heat="<?php echo $item['bd_cap_3_nhan_cong'] !== null ? (float)$item['bd_cap_3_nhan_cong'] : ''; ?>" style="min-width:8ch; width:8ch; max-width:8ch;"><?php echo $item['bd_cap_3_nhan_cong'] !== null ? number_format((float)$item['bd_cap_3_nhan_cong'], 2) : ''; ?></td>
                                <td class="text-right heatmap-cell" data-group="bd3" data-heat-type="so-gio" data-heat="<?php echo $item['bd_cap_3_so_gio'] !== null ? (float)$item['bd_cap_3_so_gio'] : ''; ?>" style="min-width:8ch; width:8ch; max-width:8ch;"><?php echo $item['bd_cap_3_so_gio'] !== null ? number_format((float)$item['bd_cap_3_so_gio'], 2) : ''; ?></td>
                                <td data-group="bd3" style="min-width:20ch; width:20ch; max-width:20ch;"><?php echo htmlspecialchars((string)($item['bd_cap_3_nguoi_thuc_hien'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td data-group="bd3" style="min-width:260px; width:260px; white-space: normal; line-height: 1.35;"><?php echo htmlspecialchars((string)($item['bd_cap_3_noi_dung'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>

                                <td class="text-center" data-group="hc" style="min-width:86px; width:86px;"><?php echo htmlspecialchars((string)($item['hieu_chuan_tan_suat_thang'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-right heatmap-cell" data-group="hc" data-heat-type="nhan-cong" data-heat="<?php echo $item['hieu_chuan_nhan_cong'] !== null ? (float)$item['hieu_chuan_nhan_cong'] : ''; ?>" style="min-width:8ch; width:8ch; max-width:8ch;"><?php echo $item['hieu_chuan_nhan_cong'] !== null ? number_format((float)$item['hieu_chuan_nhan_cong'], 2) : ''; ?></td>
                                <td class="text-right heatmap-cell" data-group="hc" data-heat-type="so-gio" data-heat="<?php echo $item['hieu_chuan_so_gio'] !== null ? (float)$item['hieu_chuan_so_gio'] : ''; ?>" style="min-width:8ch; width:8ch; max-width:8ch;"><?php echo $item['hieu_chuan_so_gio'] !== null ? number_format((float)$item['hieu_chuan_so_gio'], 2) : ''; ?></td>
                                <td data-group="hc" style="min-width:20ch; width:20ch; max-width:20ch;"><?php echo htmlspecialchars((string)($item['hieu_chuan_nguoi_thuc_hien'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td data-group="hc" style="min-width:260px; width:260px; white-space: normal; line-height: 1.35;"><?php echo htmlspecialchars((string)($item['hieu_chuan_noi_dung'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>

                                <?php if ($canEditKpi): ?>
                                    <td class="text-center no-print" style="min-width:84px; width:84px;">
                                        <button type="button" class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs font-semibold kpi-edit-btn" data-item="<?php echo $editPayload; ?>">
                                            <i class="fas fa-edit mr-1"></i> Sửa
                                        </button>
                                    </td>
                                <?php endif; ?>

                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <?php
                $baseQuery = [];
                if ($search !== '') {
                    $baseQuery['search'] = $search;
                }
            ?>
            <div class="px-4 py-3 border-t flex items-center justify-between gap-3 flex-wrap no-print">
                <div class="text-sm text-gray-600">Hiển thị <?php echo count($items); ?> / <?php echo number_format($totalRows); ?> dòng</div>
                <div class="flex gap-2 flex-wrap">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php $query = http_build_query($baseQuery + ['page' => $i]); ?>
                        <a href="?<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>"
                           class="px-3 py-1 rounded border <?php echo $i === $page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($canEditKpi): ?>
    <div id="kpiEditModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4 no-print">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl max-h-[92vh] overflow-y-auto">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-800">Sửa dữ liệu KPI bảo dưỡng</h2>
                <button type="button" id="kpiEditCloseTop" class="text-gray-500 hover:text-gray-700 text-xl leading-none">&times;</button>
            </div>

            <form method="POST" id="kpiEditForm" class="p-6 space-y-6">
                <input type="hidden" name="kpi_edit_action" value="1">
                <input type="hidden" name="edit_id" id="edit_id" value="">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">STT hiển thị</label>
                        <input type="number" name="stt_hien_thi" id="edit_stt_hien_thi" class="w-full border rounded px-3 py-2">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên thiết bị <span class="text-red-600">*</span></label>
                        <input type="text" name="ten_thiet_bi" id="edit_ten_thiet_bi" required class="w-full border rounded px-3 py-2">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border rounded p-4">
                        <h3 class="font-semibold text-slate-700 mb-3">Kiểm tra thiết bị</h3>
                        <div class="space-y-3">
                            <input type="text" name="kiem_tra_nhan_cong" id="edit_kiem_tra_nhan_cong" class="w-full border rounded px-3 py-2" placeholder="SL nhân công">
                            <input type="text" name="kiem_tra_so_gio" id="edit_kiem_tra_so_gio" class="w-full border rounded px-3 py-2" placeholder="Số giờ">
                            <input type="text" name="kiem_tra_nguoi_thuc_hien" id="edit_kiem_tra_nguoi_thuc_hien" class="w-full border rounded px-3 py-2" placeholder="Người thực hiện">
                            <textarea name="kiem_tra_noi_dung" id="edit_kiem_tra_noi_dung" rows="3" class="w-full border rounded px-3 py-2" placeholder="Nội dung"></textarea>
                        </div>
                    </div>

                    <div class="border rounded p-4">
                        <h3 class="font-semibold text-slate-700 mb-3">BD cấp 1</h3>
                        <div class="space-y-3">
                            <input type="text" name="bd_cap_1_nhan_cong" id="edit_bd_cap_1_nhan_cong" class="w-full border rounded px-3 py-2" placeholder="SL nhân công">
                            <input type="text" name="bd_cap_1_so_gio" id="edit_bd_cap_1_so_gio" class="w-full border rounded px-3 py-2" placeholder="Số giờ">
                            <input type="text" name="bd_cap_1_nguoi_thuc_hien" id="edit_bd_cap_1_nguoi_thuc_hien" class="w-full border rounded px-3 py-2" placeholder="Người thực hiện">
                            <textarea name="bd_cap_1_noi_dung" id="edit_bd_cap_1_noi_dung" rows="3" class="w-full border rounded px-3 py-2" placeholder="Nội dung"></textarea>
                        </div>
                    </div>

                    <div class="border rounded p-4">
                        <h3 class="font-semibold text-slate-700 mb-3">BD cấp 2</h3>
                        <div class="space-y-3">
                            <input type="number" name="bd_cap_2_tan_suat_thang" id="edit_bd_cap_2_tan_suat_thang" class="w-full border rounded px-3 py-2" placeholder="Tần suất (tháng)">
                            <input type="text" name="bd_cap_2_nhan_cong" id="edit_bd_cap_2_nhan_cong" class="w-full border rounded px-3 py-2" placeholder="SL nhân công">
                            <input type="text" name="bd_cap_2_so_gio" id="edit_bd_cap_2_so_gio" class="w-full border rounded px-3 py-2" placeholder="Số giờ">
                            <input type="text" name="bd_cap_2_nguoi_thuc_hien" id="edit_bd_cap_2_nguoi_thuc_hien" class="w-full border rounded px-3 py-2" placeholder="Người thực hiện">
                            <textarea name="bd_cap_2_noi_dung" id="edit_bd_cap_2_noi_dung" rows="3" class="w-full border rounded px-3 py-2" placeholder="Nội dung"></textarea>
                        </div>
                    </div>

                    <div class="border rounded p-4">
                        <h3 class="font-semibold text-slate-700 mb-3">BD cấp 3</h3>
                        <div class="space-y-3">
                            <input type="number" name="bd_cap_3_tan_suat_thang" id="edit_bd_cap_3_tan_suat_thang" class="w-full border rounded px-3 py-2" placeholder="Tần suất (tháng)">
                            <input type="text" name="bd_cap_3_nhan_cong" id="edit_bd_cap_3_nhan_cong" class="w-full border rounded px-3 py-2" placeholder="SL nhân công">
                            <input type="text" name="bd_cap_3_so_gio" id="edit_bd_cap_3_so_gio" class="w-full border rounded px-3 py-2" placeholder="Số giờ">
                            <input type="text" name="bd_cap_3_nguoi_thuc_hien" id="edit_bd_cap_3_nguoi_thuc_hien" class="w-full border rounded px-3 py-2" placeholder="Người thực hiện">
                            <textarea name="bd_cap_3_noi_dung" id="edit_bd_cap_3_noi_dung" rows="3" class="w-full border rounded px-3 py-2" placeholder="Nội dung"></textarea>
                        </div>
                    </div>

                    <div class="border rounded p-4 md:col-span-2">
                        <h3 class="font-semibold text-slate-700 mb-3">Hiệu chuẩn thiết bị</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <input type="number" name="hieu_chuan_tan_suat_thang" id="edit_hieu_chuan_tan_suat_thang" class="w-full border rounded px-3 py-2" placeholder="Tần suất (tháng)">
                            <input type="text" name="hieu_chuan_nhan_cong" id="edit_hieu_chuan_nhan_cong" class="w-full border rounded px-3 py-2" placeholder="SL nhân công">
                            <input type="text" name="hieu_chuan_so_gio" id="edit_hieu_chuan_so_gio" class="w-full border rounded px-3 py-2" placeholder="Số giờ">
                            <input type="text" name="hieu_chuan_nguoi_thuc_hien" id="edit_hieu_chuan_nguoi_thuc_hien" class="w-full border rounded px-3 py-2" placeholder="Người thực hiện">
                        </div>
                        <textarea name="hieu_chuan_noi_dung" id="edit_hieu_chuan_noi_dung" rows="3" class="w-full border rounded px-3 py-2 mt-3" placeholder="Nội dung"></textarea>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Ghi chú</label>
                    <textarea name="ghi_chu" id="edit_ghi_chu" rows="2" class="w-full border rounded px-3 py-2"></textarea>
                </div>

                <div class="border-t pt-4 flex items-center justify-end gap-2">
                    <button type="button" id="kpiEditCancel" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded font-semibold">Hủy</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-semibold">
                        <i class="fas fa-save mr-1"></i> Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
(function () {
    var table = document.querySelector('.excel-table');
    if (!table) {
        return;
    }

    var viewport = document.getElementById('kpiTableViewport');
    var groupedCells = table.querySelectorAll('[data-group]');
    var viewButtons = document.querySelectorAll('[data-kpi-view]');
    var baseTableWidth = 0;
    var currentView = 'all';

    function captureBaseTableWidth() {
        var previousWidth = table.style.width;
        var previousMinWidth = table.style.minWidth;

        table.style.width = 'max-content';
        table.style.minWidth = '2200px';
        baseTableWidth = Math.max(table.scrollWidth, 2200);

        table.style.width = previousWidth;
        table.style.minWidth = previousMinWidth;
    }

    function applyView(groupName) {
        currentView = groupName;

        groupedCells.forEach(function (el) {
            var currentGroup = el.getAttribute('data-group');
            var shouldHide = groupName !== 'all' && currentGroup !== groupName;
            el.classList.toggle('is-hidden', shouldHide);
        });

        viewButtons.forEach(function (button) {
            button.classList.toggle('is-active', button.getAttribute('data-kpi-view') === groupName);
        });

        fitTableToViewport();
    }

    viewButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            applyView(button.getAttribute('data-kpi-view') || 'all');
        });
    });

    function applyHeatmap() {
        var allHeatCells = Array.prototype.slice.call(table.querySelectorAll('.heatmap-cell'));
        if (!allHeatCells.length) {
            return;
        }

        var maxByType = {};
        allHeatCells.forEach(function (cell) {
            var type = cell.getAttribute('data-heat-type') || 'default';
            var raw = cell.getAttribute('data-heat');
            var value = raw === null || raw === '' ? NaN : Number(raw);
            if (!Number.isFinite(value) || value <= 0) {
                return;
            }
            if (!maxByType[type] || value > maxByType[type]) {
                maxByType[type] = value;
            }
        });

        allHeatCells.forEach(function (cell) {
            var type = cell.getAttribute('data-heat-type') || 'default';
            var raw = cell.getAttribute('data-heat');
            var value = raw === null || raw === '' ? NaN : Number(raw);
            if (!Number.isFinite(value) || value <= 0 || !maxByType[type]) {
                return;
            }

            var ratio = Math.min(value / maxByType[type], 1);
            var hue = 120 - (ratio * 120);
            var lightness = 92 - (ratio * 20);
            cell.style.backgroundColor = 'hsl(' + hue.toFixed(0) + ' 85% ' + lightness.toFixed(0) + '%)';
            cell.style.color = '#111827';
        });
    }

    function fitTableToViewport() {
        if (!viewport || !table) {
            return;
        }

        table.style.zoom = '1';

        if (currentView === 'all') {
            if (!baseTableWidth) {
                captureBaseTableWidth();
            }
            table.style.width = baseTableWidth + 'px';
            table.style.minWidth = baseTableWidth + 'px';
            return;
        }

        table.style.width = 'max-content';
        table.style.minWidth = '0';
    }

    var unlockButton = document.getElementById('unlockImportBtn');
    var lockButton = document.getElementById('lockImportBtn');
    var unlockForm = document.getElementById('unlockImportForm');
    var unlockActionInput = document.getElementById('unlockImportAction');
    var unlockPasswordInput = document.getElementById('unlockImportPassword');

    if (unlockButton && unlockForm && unlockPasswordInput && unlockActionInput) {
        unlockButton.addEventListener('click', function () {
            if (unlockButton.getAttribute('data-unlocked') === '1') {
                return;
            }

            var password = window.prompt('Nhap mat khau mo khoa Import du lieu:');
            if (password === null) {
                return;
            }

            unlockActionInput.value = 'unlock';
            unlockPasswordInput.value = password;
            unlockForm.submit();
        });
    }

    if (lockButton && unlockForm && unlockPasswordInput && unlockActionInput) {
        lockButton.addEventListener('click', function () {
            unlockActionInput.value = 'lock';
            unlockPasswordInput.value = '';
            unlockForm.submit();
        });
    }

    var editModal = document.getElementById('kpiEditModal');
    var editForm = document.getElementById('kpiEditForm');
    var editCloseTop = document.getElementById('kpiEditCloseTop');
    var editCancel = document.getElementById('kpiEditCancel');
    var editButtons = document.querySelectorAll('.kpi-edit-btn');

    function setInputValue(id, value) {
        var field = document.getElementById(id);
        if (!field) {
            return;
        }
        field.value = value === null || typeof value === 'undefined' ? '' : String(value);
    }

    function closeEditModal() {
        if (!editModal) {
            return;
        }
        editModal.classList.add('hidden');
        editModal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    function openEditModal(item) {
        if (!editModal || !editForm || !item) {
            return;
        }

        setInputValue('edit_id', item.id);
        setInputValue('edit_stt_hien_thi', item.stt_hien_thi);
        setInputValue('edit_ten_thiet_bi', item.ten_thiet_bi);

        setInputValue('edit_kiem_tra_nhan_cong', item.kiem_tra_nhan_cong);
        setInputValue('edit_kiem_tra_so_gio', item.kiem_tra_so_gio);
        setInputValue('edit_kiem_tra_nguoi_thuc_hien', item.kiem_tra_nguoi_thuc_hien);
        setInputValue('edit_kiem_tra_noi_dung', item.kiem_tra_noi_dung);

        setInputValue('edit_bd_cap_1_nhan_cong', item.bd_cap_1_nhan_cong);
        setInputValue('edit_bd_cap_1_so_gio', item.bd_cap_1_so_gio);
        setInputValue('edit_bd_cap_1_nguoi_thuc_hien', item.bd_cap_1_nguoi_thuc_hien);
        setInputValue('edit_bd_cap_1_noi_dung', item.bd_cap_1_noi_dung);

        setInputValue('edit_bd_cap_2_tan_suat_thang', item.bd_cap_2_tan_suat_thang);
        setInputValue('edit_bd_cap_2_nhan_cong', item.bd_cap_2_nhan_cong);
        setInputValue('edit_bd_cap_2_so_gio', item.bd_cap_2_so_gio);
        setInputValue('edit_bd_cap_2_nguoi_thuc_hien', item.bd_cap_2_nguoi_thuc_hien);
        setInputValue('edit_bd_cap_2_noi_dung', item.bd_cap_2_noi_dung);

        setInputValue('edit_bd_cap_3_tan_suat_thang', item.bd_cap_3_tan_suat_thang);
        setInputValue('edit_bd_cap_3_nhan_cong', item.bd_cap_3_nhan_cong);
        setInputValue('edit_bd_cap_3_so_gio', item.bd_cap_3_so_gio);
        setInputValue('edit_bd_cap_3_nguoi_thuc_hien', item.bd_cap_3_nguoi_thuc_hien);
        setInputValue('edit_bd_cap_3_noi_dung', item.bd_cap_3_noi_dung);

        setInputValue('edit_hieu_chuan_tan_suat_thang', item.hieu_chuan_tan_suat_thang);
        setInputValue('edit_hieu_chuan_nhan_cong', item.hieu_chuan_nhan_cong);
        setInputValue('edit_hieu_chuan_so_gio', item.hieu_chuan_so_gio);
        setInputValue('edit_hieu_chuan_nguoi_thuc_hien', item.hieu_chuan_nguoi_thuc_hien);
        setInputValue('edit_hieu_chuan_noi_dung', item.hieu_chuan_noi_dung);

        setInputValue('edit_ghi_chu', item.ghi_chu);

        editModal.classList.remove('hidden');
        editModal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    if (editButtons.length) {
        editButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var payload = button.getAttribute('data-item') || '{}';
                try {
                    openEditModal(JSON.parse(payload));
                } catch (error) {
                    window.alert('Khong the tai du lieu dong can sua');
                }
            });
        });
    }

    if (editCloseTop) {
        editCloseTop.addEventListener('click', closeEditModal);
    }

    if (editCancel) {
        editCancel.addEventListener('click', closeEditModal);
    }

    if (editModal) {
        editModal.addEventListener('click', function (event) {
            if (event.target === editModal) {
                closeEditModal();
            }
        });
    }

    captureBaseTableWidth();
    applyView('all');
    applyHeatmap();
    fitTableToViewport();

    window.addEventListener('resize', fitTableToViewport);
})();
</script>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>