<?php
$title = 'Danh sách hồ sơ sửa chữa/bảo dưỡng';

$returnFilters = [];
foreach (['search', 'madv', 'nhomsc', 'trangthai', 'page'] as $key) {
    if (isset($_GET[$key]) && (string)$_GET[$key] !== '') {
        $returnFilters[$key] = (string)$_GET[$key];
    }
}

$returnQuery = $returnFilters ? '&' . http_build_query($returnFilters) : '';
$historyFormQuery = $returnFilters;
$backUrl = $item
    ? 'hososcbd_repair_details.php?id=' . (int)$stt . $returnQuery
    : 'hososcbd.php' . ($returnFilters ? '?' . http_build_query($returnFilters) : '');
$historyQuery = array_merge($returnFilters, [
    'ngayth_from' => $ngayThFrom,
    'ngayth_to' => $ngayThTo,
]);
unset($historyQuery['history_page']);

$formatDate = static function ($value): string {
    $value = trim((string)$value);
    if ($value === '' || $value === '0000-00-00' || strtotime($value) === false) {
        return '—';
    }
    return date('d/m/Y', strtotime($value));
};

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-7xl mx-auto p-4 md:p-6">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 p-4 md:p-6 border-b">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-blue-700">
                    <i class="fas fa-list mr-2"></i>Danh sách hồ sơ sửa chữa/bảo dưỡng
                </h1>
                <p class="text-sm text-gray-600 mt-1">
                    Tổng: <strong><?= $historyTotal ?></strong> hồ sơ
                </p>
            </div>
            <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>"
               class="inline-flex items-center self-start bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-arrow-left mr-2"></i><?= $item ? 'Quay lại sửa chữa' : 'Quay lại danh sách' ?>
            </a>
        </div>

        <form method="GET" action="hososcbd_machine_history.php"
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto_auto] items-end gap-3 p-4 md:px-6 bg-gray-50 border-b">
            <?php foreach ($historyFormQuery as $filterName => $filterValue): ?>
                <input type="hidden" name="<?= htmlspecialchars($filterName, ENT_QUOTES, 'UTF-8') ?>"
                       value="<?= htmlspecialchars((string)$filterValue, ENT_QUOTES, 'UTF-8') ?>">
            <?php endforeach; ?>
            <label class="block text-sm font-medium text-gray-700">
                Từ ngày thực hiện
                <input type="date" name="ngayth_from"
                       value="<?= htmlspecialchars($ngayThFrom, ENT_QUOTES, 'UTF-8') ?>"
                       class="mt-1 w-full border rounded px-3 py-2 bg-white">
            </label>
            <label class="block text-sm font-medium text-gray-700">
                Đến ngày thực hiện
                <input type="date" name="ngayth_to"
                       value="<?= htmlspecialchars($ngayThTo, ENT_QUOTES, 'UTF-8') ?>"
                       class="mt-1 w-full border rounded px-3 py-2 bg-white">
            </label>
            <button type="submit"
                    class="inline-flex justify-center items-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                <i class="fas fa-filter mr-2"></i>Lọc
            </button>
            <a href="hososcbd_machine_history.php?<?= htmlspecialchars(http_build_query($historyFormQuery), ENT_QUOTES, 'UTF-8') ?>"
               class="inline-flex justify-center items-center bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                <i class="fas fa-undo mr-2"></i>Xóa lọc
            </a>
        </form>

        <?php if (isset($_GET['history_saved'])): ?>
            <div class="mx-4 md:mx-6 mt-4 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">
                <i class="fas fa-check-circle mr-2"></i>
                Đã cập nhật <?= (int)$_GET['history_saved'] ?> nội dung kiểm tra sau sửa chữa.
            </div>
        <?php endif; ?>

        <?php if (empty($historyItems)): ?>
            <div class="p-10 text-center text-gray-500">
                <i class="fas fa-inbox text-5xl mb-3"></i>
                <p>Không có hồ sơ sửa chữa/bảo dưỡng phù hợp với bộ lọc.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <form method="POST" class="history-edit-form">
                    <input type="hidden" name="machine_history_token"
                           value="<?= htmlspecialchars($_SESSION['machine_history_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="scroll_top" value="">
                <table class="min-w-[1400px] w-full border-collapse text-sm">
                    <thead class="bg-blue-600 text-white">
                        <tr>
                            <th class="border border-blue-500 px-3 py-3 text-center w-36">Mã vật tư</th>
                            <th class="border border-blue-500 px-3 py-3 text-center w-28">Số máy</th>
                            <th class="border border-blue-500 px-3 py-3 text-center w-32">Số hồ sơ</th>
                            <th class="border border-blue-500 px-3 py-3 text-center w-44">Ngày thực hiện / kết thúc</th>
                            <th class="border border-blue-500 px-3 py-3 text-left min-w-[320px]">Hỏng hóc / Khắc phục</th>
                            <th class="border border-blue-500 px-3 py-3 text-left min-w-[360px]">Nội dung kiểm tra sau sửa chữa</th>
                            <th class="border border-blue-500 px-3 py-3 text-center w-24">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historyItems as $index => $historyItem): ?>
                            <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50 align-top">
                                <td class="border px-3 py-3 text-center font-semibold text-blue-700">
                                    <?= htmlspecialchars((string)($historyItem['mavt'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="border px-3 py-3 text-center">
                                    <?= htmlspecialchars((string)($historyItem['somay'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="border px-3 py-3 text-center">
                                    <?= htmlspecialchars((string)($historyItem['hoso'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="border px-3 py-3">
                                    <div class="rounded bg-blue-100 text-blue-900 px-2 py-1 text-center">
                                        <span class="font-semibold">TH:</span> <?= $formatDate($historyItem['ngayth'] ?? '') ?>
                                    </div>
                                    <div class="rounded bg-emerald-100 text-emerald-900 px-2 py-1 text-center mt-1">
                                        <span class="font-semibold">KT:</span> <?= $formatDate($historyItem['ngaykt'] ?? '') ?>
                                    </div>
                                </td>
                                <td class="border px-3 py-3">
                                    <div class="rounded bg-red-50 text-gray-800 px-2 py-2 whitespace-pre-wrap">
                                        <div class="font-semibold text-red-700 mb-1">Hỏng hóc</div>
                                        <?= displayText($historyItem['honghoc'] ?? '') ?: '—' ?>
                                    </div>
                                    <div class="rounded bg-green-50 text-gray-800 px-2 py-2 whitespace-pre-wrap mt-2">
                                        <div class="font-semibold text-green-700 mb-1">Khắc phục</div>
                                        <?= displayText($historyItem['khacphuc'] ?? '') ?: '—' ?>
                                    </div>
                                </td>
                                <td class="border px-3 py-3">
                                    <?php
                                    $canEditInspection = hasPermission('hososcbd.edit');
                                    ?>
                                    <?php if ($canEditInspection): ?>
                                            <textarea name="noidung[<?= (int)$historyItem['stt'] ?>]" rows="5"
                                                      class="w-full border rounded px-2 py-2 text-sm focus:outline-none focus:ring focus:border-blue-500"><?= displayText($historyItem['noidung'] ?? '') ?></textarea>
                                    <?php else: ?>
                                        <div class="whitespace-pre-wrap">
                                            <?= displayText($historyItem['noidung'] ?? '') ?: '—' ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="border px-3 py-3 text-center">
                                    <a href="hososcbd_repair_details.php?id=<?= (int)$historyItem['stt'] ?><?= $returnQuery ?>"
                                       class="inline-flex items-center bg-orange-500 hover:bg-orange-600 text-white px-2 py-1 rounded"
                                       title="Mở hồ sơ">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                    <?php if (hasPermission('hososcbd.edit')): ?>
                        <div class="flex items-center justify-end gap-3 bg-gray-50 border-t px-4 py-3">
                            <span class="text-sm text-gray-600">Chỉnh sửa nội dung kiểm tra tại các dòng cần cập nhật.</span>
                            <button type="submit"
                                    class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-semibold">
                                <i class="fas fa-save mr-2"></i>Lưu tất cả
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($historyTotalPages > 1): ?>
            <nav class="flex flex-wrap justify-center gap-2 p-4 border-t" aria-label="Phân trang hồ sơ máy">
                <?php
                $startPage = max(1, $historyPage - 2);
                $endPage = min($historyTotalPages, $historyPage + 2);
                ?>
                <?php if ($historyPage > 1): ?>
                    <?php $query = $historyQuery; $query['history_page'] = $historyPage - 1; ?>
                    <a class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300"
                       href="hososcbd_machine_history.php?<?= htmlspecialchars(http_build_query($query), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                <?php for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++): ?>
                    <?php $query = $historyQuery; $query['history_page'] = $pageNumber; ?>
                    <a class="px-3 py-2 rounded <?= $pageNumber === $historyPage ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>"
                       href="hososcbd_machine_history.php?<?= htmlspecialchars(http_build_query($query), ENT_QUOTES, 'UTF-8') ?>">
                        <?= $pageNumber ?>
                    </a>
                <?php endfor; ?>
                <?php if ($historyPage < $historyTotalPages): ?>
                    <?php $query = $historyQuery; $query['history_page'] = $historyPage + 1; ?>
                    <a class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300"
                       href="hososcbd_machine_history.php?<?= htmlspecialchars(http_build_query($query), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.history-edit-form').forEach(function (form) {
        form.addEventListener('submit', function () {
            const scrollInput = form.querySelector('input[name="scroll_top"]');
            if (scrollInput) {
                scrollInput.value = String(Math.max(0, Math.round(window.scrollY)));
            }
        });
    });

    const params = new URLSearchParams(window.location.search);
    const scrollTop = Number.parseInt(params.get('scroll_top') || '0', 10);
    if (Number.isFinite(scrollTop) && scrollTop > 0) {
        window.requestAnimationFrame(function () {
            window.scrollTo(0, scrollTop);
            params.delete('scroll_top');
            params.delete('history_saved');
            const query = params.toString();
            const cleanUrl = window.location.pathname + (query ? '?' + query : '') + window.location.hash;
            window.history.replaceState({}, document.title, cleanUrl);
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
