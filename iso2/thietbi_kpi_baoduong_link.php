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

$canView = hasPermission('kpi_baoduong.view') || hasPermission('thietbi.view');
$canEdit = hasPermission('kpi_baoduong.edit');

if (!$canView) {
    http_response_code(403);
    die('Khong co quyen xem du lieu');
}

$title = 'Gán KPI bảo dưỡng cho thiết bị';

$successMessage = '';
$errorMessage = '';

$model = new ThietBiKpiBaoDuong();

try {
    $db = getDBConnection();
    $model->ensureTableExists();

    $checkKpiTable = $db->query("SHOW TABLES LIKE 'kpi_baoduong_thietbi_iso'");
    if ($checkKpiTable === false || !$checkKpiTable->fetch()) {
        throw new RuntimeException('Bang kpi_baoduong_thietbi_iso chua ton tai. Vui long tao du lieu KPI truoc.');
    }
} catch (Throwable $e) {
    $errorMessage = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_action'])) {
    if (!$canEdit) {
        http_response_code(403);
        die('Khong co quyen thuc hien thao tac nay');
    }

    $linkAction = (string)($_POST['link_action'] ?? '');

    try {
        if ($linkAction === 'save') {
            $thietbiStt = (int)($_POST['thietbi_stt'] ?? 0);
            $kpiBaoDuongStt = (int)($_POST['kpi_baoduong_stt'] ?? 0);

            if ($thietbiStt <= 0) {
                throw new RuntimeException('Vui long chon thiet bi');
            }
            if ($kpiBaoDuongStt <= 0) {
                throw new RuntimeException('Vui long chon dinh muc KPI');
            }

            $db = getDBConnection();

            // Doc lai thong tin thiet bi tu server de dam bao thiet bi ton tai va du lieu chinh xac
            $tbStmt = $db->prepare('SELECT stt, mavt, somay, tenvt FROM thietbi_iso WHERE stt = :stt LIMIT 1');
            $tbStmt->execute([':stt' => $thietbiStt]);
            $thietbiRow = $tbStmt->fetch(PDO::FETCH_ASSOC);
            if (!$thietbiRow) {
                throw new RuntimeException('Khong tim thay thiet bi da chon');
            }

            $kpiStmt = $db->prepare('SELECT id FROM kpi_baoduong_thietbi_iso WHERE id = :id LIMIT 1');
            $kpiStmt->execute([':id' => $kpiBaoDuongStt]);
            if (!$kpiStmt->fetch()) {
                throw new RuntimeException('Khong tim thay dinh muc KPI da chon');
            }

            $createdBy = (string)($_SESSION['username'] ?? $_SESSION['user_id'] ?? '');

            $model->upsertLink(
                $thietbiStt,
                (string)$thietbiRow['mavt'],
                (string)$thietbiRow['somay'],
                (string)$thietbiRow['tenvt'],
                $kpiBaoDuongStt,
                $createdBy
            );

            $_SESSION['link_success'] = 'Gán KPI cho thiết bị thành công';
            header('Location: thietbi_kpi_baoduong_link.php');
            exit;
        }

        if ($linkAction === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new RuntimeException('ID lien ket khong hop le');
            }
            $model->deleteById($id);
            $_SESSION['link_success'] = 'Đã xóa liên kết';
            header('Location: thietbi_kpi_baoduong_link.php');
            exit;
        }

        throw new RuntimeException('Hanh dong khong hop le');
    } catch (Throwable $e) {
        $errorMessage = 'Không thể lưu dữ liệu: ' . $e->getMessage();
    }
}

if (isset($_SESSION['link_success'])) {
    $successMessage = (string)$_SESSION['link_success'];
    unset($_SESSION['link_success']);
}

$search = trim((string)($_GET['search'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$items = [];
$totalRows = 0;
$totalPages = 1;
$kpiOptions = [];

try {
    $totalRows = $model->countSearch($search);
    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }
    $items = $model->searchWithDetails($search, $perPage, $offset);

    $db = getDBConnection();
    $kpiStmt = $db->query('SELECT id, ten_thiet_bi FROM kpi_baoduong_thietbi_iso ORDER BY COALESCE(stt_hien_thi, id) ASC');
    $kpiOptions = $kpiStmt !== false ? $kpiStmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable $e) {
    if ($errorMessage === '') {
        $errorMessage = 'Không thể tải dữ liệu: ' . $e->getMessage();
    }
}

require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center text-slate-800">
                <i class="fas fa-link mr-2 text-blue-700"></i> Gán KPI bảo dưỡng cho thiết bị
            </h1>
            <p class="text-sm text-gray-500 mt-1">Mỗi thiết bị (thietbi_iso) chỉ thuộc đúng 1 định mức KPI bảo dưỡng (kpi_baoduong_thietbi_iso).</p>
        </div>
        <?php if ($canEdit): ?>
        <div class="flex gap-2">
            <a href="thietbi_kpi_baoduong_auto_assign.php" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm font-semibold">
                <i class="fas fa-magic mr-1"></i> Gán tự động theo tên
            </a>
            <button type="button" id="btnOpenAddLink" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-semibold">
                <i class="fas fa-plus mr-1"></i> Thêm liên kết
            </button>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMessage !== ''): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div class="md:col-span-3">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tìm theo mã VT / số máy / tên thiết bị</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Nhập từ khóa...">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded text-sm font-semibold">
                    <i class="fas fa-search mr-1"></i> Tìm
                </button>
                <?php if ($search !== ''): ?>
                <a href="thietbi_kpi_baoduong_link.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded text-sm font-semibold">Xóa lọc</a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Mã VT</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Số máy</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Tên thiết bị</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Định mức KPI đã gán</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Cập nhật lúc</th>
                    <?php if ($canEdit): ?>
                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Thao tác</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-gray-400">Không có thiết bị nào trong thietbi_iso</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <tr class="hover:bg-blue-50">
                        <td class="px-3 py-2"><?php echo htmlspecialchars((string)$item['mavt'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-3 py-2"><?php echo htmlspecialchars((string)$item['somay'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-3 py-2"><?php echo htmlspecialchars((string)$item['ten_thiet_bi'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-3 py-2 font-semibold <?php echo !empty($item['link_id']) ? 'text-blue-700' : 'text-gray-500'; ?>">
                            <?php echo htmlspecialchars(!empty($item['link_id']) ? (string)$item['kpi_ten_thiet_bi'] : 'Chưa gán', ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td class="px-3 py-2 text-gray-500"><?php echo htmlspecialchars(!empty($item['updated_at']) ? (string)$item['updated_at'] : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                        <?php if ($canEdit): ?>
                        <td class="px-3 py-2">
                            <button type="button"
                                    class="btnEditLink text-blue-600 hover:text-blue-800 mr-3"
                                    data-thietbi-stt="<?php echo (int)$item['thietbi_stt']; ?>"
                                    data-thietbi-label="<?php echo htmlspecialchars((string)$item['ten_thiet_bi'] . ' — ' . $item['mavt'] . ' / SN:' . $item['somay'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-kpi-stt="<?php echo !empty($item['kpi_baoduong_stt']) ? (int)$item['kpi_baoduong_stt'] : ''; ?>"
                                    data-has-link="<?php echo !empty($item['link_id']) ? '1' : '0'; ?>">
                                <i class="fas fa-<?php echo !empty($item['link_id']) ? 'edit' : 'link'; ?> mr-1"></i>
                                <?php echo !empty($item['link_id']) ? 'Sửa' : 'Gán KPI'; ?>
                            </button>
                            <?php if (!empty($item['link_id'])): ?>
                            <form method="POST" class="inline" onsubmit="return confirm('Xóa liên kết này?');">
                                <input type="hidden" name="link_action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$item['link_id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="flex justify-center gap-2 mt-4">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $p; ?>"
               class="px-3 py-1 rounded text-sm <?php echo $p === $page ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-100'; ?>">
                <?php echo $p; ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($canEdit): ?>
<!-- Modal: Thêm / Sửa liên kết -->
<div id="linkModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg">
        <div class="flex items-center justify-between px-5 py-4 border-b">
            <h2 id="linkModalTitle" class="text-lg font-bold text-slate-800">Thêm liên kết thiết bị - KPI</h2>
            <button type="button" id="btnCloseLinkModal" class="text-gray-400 hover:text-gray-700">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <form method="POST" id="formLink" class="px-5 py-4 space-y-4">
            <input type="hidden" name="link_action" value="save">
            <input type="hidden" name="thietbi_stt" id="thietbi_stt" value="">

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Thiết bị <span class="text-red-500">*</span></label>
                <input type="text" id="thietbiSearchInput" autocomplete="off"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                       placeholder="Gõ mã VT / số máy / tên thiết bị để tìm...">
                <div id="thietbiSearchResults" class="relative">
                    <div class="absolute left-0 right-0 bg-white border border-gray-200 rounded shadow-lg mt-1 max-h-56 overflow-y-auto hidden z-10" id="thietbiSearchDropdown"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1" id="thietbiSelectedLabel"></p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Định mức KPI bảo dưỡng <span class="text-red-500">*</span></label>
                <select name="kpi_baoduong_stt" id="kpi_baoduong_stt" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required>
                    <option value="">-- Chọn định mức KPI --</option>
                    <?php foreach ($kpiOptions as $opt): ?>
                        <option value="<?php echo (int)$opt['id']; ?>"><?php echo htmlspecialchars((string)$opt['ten_thiet_bi'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" id="btnCancelLink" class="px-4 py-2 rounded text-sm font-semibold bg-gray-200 hover:bg-gray-300 text-gray-700">Hủy</button>
                <button type="submit" class="px-4 py-2 rounded text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white">
                    <i class="fas fa-save mr-1"></i> Lưu
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('linkModal');
    const modalTitle = document.getElementById('linkModalTitle');
    const formLink = document.getElementById('formLink');
    const thietbiSttInput = document.getElementById('thietbi_stt');
    const thietbiSearchInput = document.getElementById('thietbiSearchInput');
    const thietbiDropdown = document.getElementById('thietbiSearchDropdown');
    const thietbiSelectedLabel = document.getElementById('thietbiSelectedLabel');
    const kpiSelect = document.getElementById('kpi_baoduong_stt');

    let searchDebounce = null;

    function openModal(title) {
        modalTitle.textContent = title;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        formLink.reset();
        thietbiSttInput.value = '';
        thietbiSelectedLabel.textContent = '';
        thietbiDropdown.classList.add('hidden');
        thietbiDropdown.innerHTML = '';
    }

    document.getElementById('btnOpenAddLink').addEventListener('click', function () {
        openModal('Thêm liên kết thiết bị - KPI');
    });
    document.getElementById('btnCloseLinkModal').addEventListener('click', closeModal);
    document.getElementById('btnCancelLink').addEventListener('click', closeModal);

    document.querySelectorAll('.btnEditLink').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const hasLink = btn.dataset.hasLink === '1';
            openModal(hasLink ? 'Sửa liên kết thiết bị - KPI' : 'Gán KPI bảo dưỡng cho thiết bị');
            thietbiSttInput.value = btn.dataset.thietbiStt;
            thietbiSelectedLabel.textContent = 'Đang chọn: ' + btn.dataset.thietbiLabel;
            thietbiSearchInput.value = btn.dataset.thietbiLabel;
            kpiSelect.value = btn.dataset.kpiStt || '';
        });
    });

    thietbiSearchInput.addEventListener('input', function () {
        const q = thietbiSearchInput.value.trim();
        thietbiSttInput.value = '';
        thietbiSelectedLabel.textContent = '';
        if (searchDebounce) {
            clearTimeout(searchDebounce);
        }
        if (q.length < 2) {
            thietbiDropdown.classList.add('hidden');
            thietbiDropdown.innerHTML = '';
            return;
        }
        searchDebounce = setTimeout(function () {
            fetch('api/thietbi_search_for_kpi.php?q=' + encodeURIComponent(q))
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    thietbiDropdown.innerHTML = '';
                    if (!data.success || !data.items || data.items.length === 0) {
                        thietbiDropdown.innerHTML = '<div class="px-3 py-2 text-sm text-gray-400">Không tìm thấy thiết bị</div>';
                        thietbiDropdown.classList.remove('hidden');
                        return;
                    }
                    data.items.forEach(function (item) {
                        const row = document.createElement('div');
                        row.className = 'px-3 py-2 text-sm hover:bg-blue-50 cursor-pointer';
                        row.textContent = item.label;
                        row.addEventListener('click', function () {
                            thietbiSttInput.value = item.stt;
                            thietbiSearchInput.value = item.label;
                            thietbiSelectedLabel.textContent = 'Đang chọn: ' + item.label;
                            thietbiDropdown.classList.add('hidden');
                        });
                        thietbiDropdown.appendChild(row);
                    });
                    thietbiDropdown.classList.remove('hidden');
                })
                .catch(function () {
                    thietbiDropdown.innerHTML = '<div class="px-3 py-2 text-sm text-red-400">Lỗi tìm kiếm</div>';
                    thietbiDropdown.classList.remove('hidden');
                });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!thietbiDropdown.contains(e.target) && e.target !== thietbiSearchInput) {
            thietbiDropdown.classList.add('hidden');
        }
    });

    formLink.addEventListener('submit', function (e) {
        if (!thietbiSttInput.value) {
            e.preventDefault();
            alert('Vui lòng chọn thiết bị từ danh sách gợi ý');
        }
    });
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
