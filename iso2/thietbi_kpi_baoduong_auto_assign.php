<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/ThietBiKpiBaoDuong.php';

requireAuth();

$canEdit = hasPermission('kpi_baoduong.edit');
if (!$canEdit) {
    http_response_code(403);
    die('Khong co quyen thuc hien gan tu dong');
}

$title = 'Gán KPI tự động theo tên thiết bị';

$successMessage = '';
$errorMessage = '';

$model = new ThietBiKpiBaoDuong();

try {
    $db = getDBConnection();
    $model->ensureTableExists();
} catch (Throwable $e) {
    $errorMessage = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_assign_action']) && $errorMessage === '') {
    try {
        $selectedThietBi = $_POST['selected_thietbi_stt'] ?? [];
        if (!is_array($selectedThietBi) || empty($selectedThietBi)) {
            throw new RuntimeException('Vui lòng chọn ít nhất 1 thiết bị để gán');
        }

        $mavtMap = $_POST['mavt'] ?? [];
        $somayMap = $_POST['somay'] ?? [];
        $tenMap = $_POST['ten_thiet_bi'] ?? [];
        $kpiMap = $_POST['kpi_baoduong_stt'] ?? [];

        $pairs = [];
        foreach ($selectedThietBi as $thietbiStt) {
            $thietbiStt = (int)$thietbiStt;
            if ($thietbiStt <= 0 || !isset($kpiMap[$thietbiStt])) {
                continue;
            }
            $pairs[] = [
                'thietbi_stt' => $thietbiStt,
                'mavt' => (string)($mavtMap[$thietbiStt] ?? ''),
                'somay' => (string)($somayMap[$thietbiStt] ?? ''),
                'ten_thiet_bi' => (string)($tenMap[$thietbiStt] ?? ''),
                'kpi_baoduong_stt' => (int)$kpiMap[$thietbiStt],
            ];
        }

        if (empty($pairs)) {
            throw new RuntimeException('Không có dòng hợp lệ nào được chọn');
        }

        $createdBy = (string)($_SESSION['username'] ?? $_SESSION['user_id'] ?? '');
        $count = $model->bulkAssign($pairs, $createdBy);

        $_SESSION['auto_assign_success'] = "Đã gán tự động thành công cho {$count} thiết bị";
        header('Location: thietbi_kpi_baoduong_auto_assign.php');
        exit;
    } catch (Throwable $e) {
        $errorMessage = 'Không thể gán tự động: ' . $e->getMessage();
    }
}

if (isset($_SESSION['auto_assign_success'])) {
    $successMessage = (string)$_SESSION['auto_assign_success'];
    unset($_SESSION['auto_assign_success']);
}

$onlyUnlinked = !isset($_GET['show_all']);
$search = trim((string)($_GET['search'] ?? ''));
$candidates = [];

if ($errorMessage === '') {
    try {
        $candidates = $model->getAutoMatchCandidates($onlyUnlinked, $search);
    } catch (Throwable $e) {
        $errorMessage = 'Không thể tải danh sách khớp tên: ' . $e->getMessage();
    }
}

require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center text-slate-800">
                <i class="fas fa-magic mr-2 text-purple-700"></i> Gán KPI tự động theo tên thiết bị
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                So khớp <code>thietbi_iso.tenvt</code> với <code>kpi_baoduong_thietbi_iso.ten_thiet_bi</code> (không phân biệt hoa/thường, bỏ khoảng trắng thừa, hỗ trợ dạng mã như AK ↔ AK-73 / AK-76).
            </p>
        </div>
        <a href="thietbi_kpi_baoduong_link.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded text-sm font-semibold">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại danh sách
        </a>
    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMessage !== ''): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tìm theo mã VT / số máy / tên thiết bị</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Nhập từ khóa...">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="show_all" name="show_all" value="1" <?php echo $onlyUnlinked ? '' : 'checked'; ?>
                       class="rounded border-gray-300">
                <label for="show_all" class="text-sm text-gray-700">Hiện cả thiết bị đã gán đúng</label>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded text-sm font-semibold">
                    <i class="fas fa-search mr-1"></i> Lọc
                </button>
            </div>
        </div>
    </form>

    <?php if (empty($candidates)): ?>
        <div class="bg-white rounded-lg shadow p-6 text-center text-gray-400">
            Không tìm thấy thiết bị nào khớp tên với định mức KPI để gán tự động
        </div>
    <?php else: ?>
    <form method="POST" id="formAutoAssign">
        <input type="hidden" name="auto_assign_action" value="assign">
        <div class="bg-white rounded-lg shadow overflow-x-auto mb-4">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 text-left">
                            <input type="checkbox" id="checkAll" checked>
                        </th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Mã VT</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Số máy</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Tên thiết bị</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">KPI khớp tự động</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Trạng thái</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($candidates as $c): ?>
                    <?php
                        $thietbiStt = (int)$c['thietbi_stt'];
                        $isAlreadyCorrect = !empty($c['link_id']) && (int)$c['current_kpi_stt'] === (int)$c['kpi_baoduong_stt'];
                        $isRelink = !empty($c['link_id']) && !$isAlreadyCorrect;
                    ?>
                    <tr class="hover:bg-blue-50">
                        <td class="px-3 py-2">
                            <input type="checkbox" class="rowCheck" name="selected_thietbi_stt[]" value="<?php echo $thietbiStt; ?>" <?php echo $isAlreadyCorrect ? '' : 'checked'; ?>>
                            <input type="hidden" name="mavt[<?php echo $thietbiStt; ?>]" value="<?php echo htmlspecialchars((string)$c['mavt'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="somay[<?php echo $thietbiStt; ?>]" value="<?php echo htmlspecialchars((string)$c['somay'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="ten_thiet_bi[<?php echo $thietbiStt; ?>]" value="<?php echo htmlspecialchars((string)$c['ten_thiet_bi'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="kpi_baoduong_stt[<?php echo $thietbiStt; ?>]" value="<?php echo (int)$c['kpi_baoduong_stt']; ?>">
                        </td>
                        <td class="px-3 py-2"><?php echo htmlspecialchars((string)$c['mavt'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-3 py-2"><?php echo htmlspecialchars((string)$c['somay'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-3 py-2"><?php echo htmlspecialchars((string)$c['ten_thiet_bi'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-3 py-2 font-semibold text-purple-700"><?php echo htmlspecialchars((string)$c['kpi_ten_thiet_bi'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-3 py-2">
                            <?php if ($isAlreadyCorrect): ?>
                                <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>Đã khớp</span>
                            <?php elseif ($isRelink): ?>
                                <span class="text-amber-600"><i class="fas fa-exclamation-triangle mr-1"></i>Sẽ ghi đè</span>
                            <?php else: ?>
                                <span class="text-blue-600"><i class="fas fa-plus-circle mr-1"></i>Gán mới</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 rounded text-sm font-semibold">
                <i class="fas fa-magic mr-1"></i> Gán tự động các dòng đã chọn
            </button>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
(function () {
    const checkAll = document.getElementById('checkAll');
    if (!checkAll) {
        return;
    }
    const rowChecks = document.querySelectorAll('.rowCheck');
    checkAll.addEventListener('change', function () {
        rowChecks.forEach(function (cb) { cb.checked = checkAll.checked; });
    });
    document.getElementById('formAutoAssign').addEventListener('submit', function (e) {
        const anyChecked = Array.from(rowChecks).some(function (cb) { return cb.checked; });
        if (!anyChecked) {
            e.preventDefault();
            alert('Vui lòng chọn ít nhất 1 thiết bị');
        } else if (!confirm('Xác nhận gán KPI tự động cho các thiết bị đã chọn?')) {
            e.preventDefault();
        }
    });
})();
</script>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
