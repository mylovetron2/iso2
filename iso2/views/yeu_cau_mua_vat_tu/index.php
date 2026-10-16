<?php
require_once __DIR__ . '/../layouts/header.php';
$statusMap = YeuCauMuaVatTu::TRANG_THAI;
$statusClass = [
    'moi' => 'bg-amber-100 text-amber-800 border-amber-200',
    'da_xem' => 'bg-blue-100 text-blue-800 border-blue-200',
    'da_xu_ly' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
    'tu_choi' => 'bg-red-100 text-red-800 border-red-200',
];
$filterParams = [];
if ($year !== null) {
    $filterParams['year'] = $year;
}
if ($status !== null) {
    $filterParams['status'] = $status;
}
$allRequestsUrl = 'yeu_cau_mua_vat_tu.php' . ($filterParams ? '?' . http_build_query($filterParams) : '');
$pendingRequestsUrl = 'yeu_cau_mua_vat_tu.php?' . http_build_query(['action' => 'pending'] + $filterParams);
?>
<div class="max-w-7xl mx-auto bg-white rounded-lg shadow p-4 md:p-6">
    <?php if (isset($_GET['success'])): ?><div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars((string)$_GET['success']); ?></div><?php endif; ?>
    <?php if (isset($_GET['error'])): ?><div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars((string)$_GET['error']); ?></div><?php endif; ?>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div><h1 class="text-xl md:text-2xl font-bold text-slate-800"><i class="fas fa-cart-plus text-blue-600 mr-2"></i><?php echo $pendingOnly ? 'Yêu cầu mua vật tư cần xử lý' : 'Yêu cầu mua vật tư'; ?></h1><p class="text-sm text-slate-500 mt-1">Lưu vật tư cần mua và theo dõi kết quả xử lý.</p></div>
        <div class="flex gap-2">
            <?php if ($pendingOnly): ?><a href="<?php echo htmlspecialchars($allRequestsUrl); ?>" class="text-blue-600 hover:underline text-sm py-2"><i class="fas fa-list mr-1"></i>Tất cả yêu cầu</a><?php elseif ($isManager): ?><a href="<?php echo htmlspecialchars($pendingRequestsUrl); ?>" class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-2 rounded text-sm"><i class="fas fa-tasks mr-1"></i>Xử lý chờ duyệt <span class="ml-1 bg-white text-amber-700 rounded-full px-1.5"><?php echo (int)$pendingCount; ?></span></a><?php endif; ?>
        </div>
    </div>
    <?php if ($databaseError): ?><div class="bg-amber-50 border border-amber-200 text-amber-900 rounded p-4 mb-4">Chưa khởi tạo bảng. Vui lòng chạy file <code>migrations/create_yeu_cau_mua_vat_tu.sql</code>.</div><?php endif; ?>
    <form method="get" class="flex flex-wrap items-end gap-2 mb-5 bg-slate-50 border border-blue-200 rounded-lg p-3">
        <input type="hidden" name="action" value="<?php echo $pendingOnly ? 'pending' : 'index'; ?>">
        <label class="text-sm font-semibold text-slate-700">Lọc theo năm
            <select name="year" class="block mt-1 border rounded px-3 py-2 font-normal">
                <option value="">Tất cả các năm</option>
                <?php foreach ($availableYears as $availableYear): ?>
                    <option value="<?php echo (int)$availableYear; ?>" <?php echo $year === (int)$availableYear ? 'selected' : ''; ?>><?php echo (int)$availableYear; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="text-sm font-semibold text-slate-700">Lọc theo trạng thái
            <select name="status" class="block mt-1 border rounded px-3 py-2 font-normal">
                <option value="">Tất cả trạng thái</option>
                <?php foreach ($statusMap as $statusKey => $statusLabel): ?>
                    <option value="<?php echo htmlspecialchars($statusKey); ?>" <?php echo $status === $statusKey ? 'selected' : ''; ?>><?php echo htmlspecialchars($statusLabel); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm"><i class="fas fa-filter mr-1"></i>Lọc</button>
        <?php if ($year !== null || $status !== null): ?><a href="yeu_cau_mua_vat_tu.php<?php echo $pendingOnly ? '?action=pending' : ''; ?>" class="text-slate-600 hover:underline text-sm py-2">Xóa bộ lọc</a><?php endif; ?>
    </form>
    <?php if (!$pendingOnly && (hasRole(ROLE_ADMIN) || hasPermission('yeucaumuavattu.create'))): ?>
    <div class="mb-5">
        <button type="button" id="toggleYeuCauMuaForm" aria-expanded="false" aria-controls="yeuCauMuaForm" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
            <i class="fas fa-plus mr-1"></i> Gửi yêu cầu mới
        </button>
    </div>
    <form id="yeuCauMuaForm" method="post" action="yeu_cau_mua_vat_tu.php?action=create" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4 mb-5 grid md:grid-cols-2 gap-3">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['csrf_token']); ?>">
        <label class="text-sm font-semibold text-slate-700">Tên vật tư *<input required maxlength="255" name="ten_vat_tu" class="mt-1 w-full border rounded px-3 py-2 font-normal" placeholder="Ví dụ: vòng bi 6205"></label>
        <label class="text-sm font-semibold text-slate-700">Mã vật tư<input maxlength="100" name="ma_vat_tu" class="mt-1 w-full border rounded px-3 py-2 font-normal"></label>
        <label class="text-sm font-semibold text-slate-700">Product Number<input maxlength="100" name="product_number" class="mt-1 w-full border rounded px-3 py-2 font-normal"></label>
        <label class="text-sm font-semibold text-slate-700">Part Number<input maxlength="100" name="part_number" class="mt-1 w-full border rounded px-3 py-2 font-normal"></label>
        <label class="text-sm font-semibold text-slate-700 md:col-span-2">Đặc tính kỹ thuật<textarea maxlength="1000" name="dac_tinh_ky_thuat" rows="2" class="mt-1 w-full border rounded px-3 py-2 font-normal"></textarea></label>
        <div class="grid grid-cols-2 gap-2"><label class="text-sm font-semibold text-slate-700">Số lượng *<input required min="0.01" step="0.01" type="number" name="so_luong" value="1" class="mt-1 w-full border rounded px-3 py-2 font-normal"></label><label class="text-sm font-semibold text-slate-700">Đơn vị tính<input maxlength="50" name="don_vi_tinh" class="mt-1 w-full border rounded px-3 py-2 font-normal" placeholder="cái, bộ..."></label></div>
        <label class="text-sm font-semibold text-slate-700">Link mua<input type="url" maxlength="1000" name="link_mua" class="mt-1 w-full border rounded px-3 py-2 font-normal" placeholder="https://..."></label>
        <label class="text-sm font-semibold text-slate-700">Thời gian yêu cầu *<input required type="datetime-local" name="thoi_gian_yeu_cau" value="<?php echo date('Y-m-d\TH:i'); ?>" class="mt-1 w-full border rounded px-3 py-2 font-normal"></label>
        <label class="text-sm font-semibold text-slate-700 md:col-span-2">Ghi chú<textarea maxlength="4000" name="ghi_chu" rows="2" class="mt-1 w-full border rounded px-3 py-2 font-normal" placeholder="Thông số, lý do hoặc yêu cầu nhà cung cấp..."></textarea></label>
        <div class="md:col-span-2 text-right"><button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded"><i class="fas fa-paper-plane mr-1"></i>Gửi yêu cầu</button></div>
    </form>
    <script>
        document.getElementById('toggleYeuCauMuaForm').addEventListener('click', function () {
            const form = document.getElementById('yeuCauMuaForm');
            const expanded = form.classList.toggle('hidden') === false;
            this.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            this.innerHTML = expanded
                ? '<i class="fas fa-times mr-1"></i> Đóng form'
                : '<i class="fas fa-plus mr-1"></i> Gửi yêu cầu mới';
        });
    </script>
    <?php endif; ?>
    <?php if (!$items): ?><div class="text-center text-slate-500 py-10"><i class="fas fa-inbox text-4xl mb-2"></i><div>Chưa có yêu cầu mua vật tư.</div></div><?php endif; ?>
    <?php if ($items): ?>
    <style>.yc-mua-table th, .yc-mua-table td { border: 1px solid #93c5fd; }</style>
    <div class="overflow-x-auto border-2 border-blue-400 rounded-lg">
        <table class="min-w-full text-sm border-collapse yc-mua-table">
            <thead class="bg-blue-50 text-slate-700 text-left">
                <tr>
                    <th class="px-3 py-2 font-semibold w-10">#</th>
                    <th class="px-3 py-2 font-semibold">Vật tư</th>
                    <th class="px-3 py-2 font-semibold">Đặc tính kỹ thuật</th>
                    <th class="px-3 py-2 font-semibold">Người gửi</th>
                    <th class="px-3 py-2 font-semibold whitespace-nowrap">Số lượng</th>
                    <th class="px-3 py-2 font-semibold">Link mua</th>
                    <th class="px-3 py-2 font-semibold">Ghi chú</th>
                    <th class="px-3 py-2 font-semibold whitespace-nowrap">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-blue-100">
        <?php foreach ($items as $index => $item):
            $itemStatus = (string)$item['trang_thai'];
            $canEdit = !$pendingOnly && !in_array($itemStatus, ['da_xu_ly', 'tu_choi'], true) && (int)($item['nguoi_gui_id'] ?? 0) === (int)($_SESSION['user_id'] ?? 0) && (hasRole(ROLE_ADMIN) || hasPermission('yeucaumuavattu.create'));
        ?>
        <tr class="align-top hover:bg-slate-50">
            <td class="px-3 py-2 text-slate-500"><?php echo (int)$index + 1; ?></td>
            <td class="px-3 py-2 text-slate-800">
                <button type="button" class="open-modal text-left font-semibold text-blue-700 hover:underline" data-target="detail<?php echo (int)$item['id']; ?>" title="Xem chi tiết vật tư"><?php echo htmlspecialchars((string)$item['ten_vat_tu']); ?></button>
                <dialog id="detail<?php echo (int)$item['id']; ?>" class="rounded-lg shadow-xl p-0 w-full max-w-md backdrop:bg-black/50 text-left whitespace-normal">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-blue-200 bg-blue-50">
                        <h3 class="font-semibold text-slate-800"><?php echo htmlspecialchars((string)$item['ten_vat_tu']); ?></h3>
                        <button type="button" class="close-modal text-slate-500 hover:text-slate-800 text-xl leading-none" aria-label="Đóng">&times;</button>
                    </div>
                    <dl class="p-4 grid grid-cols-3 gap-x-3 gap-y-2 text-sm">
                        <dt class="text-slate-500">Mã vật tư</dt><dd class="col-span-2 font-semibold text-slate-800"><?php echo htmlspecialchars((string)($item['ma_vat_tu'] ?? '')) ?: '—'; ?></dd>
                        <dt class="text-slate-500">Product Number</dt><dd class="col-span-2 font-semibold text-slate-800"><?php echo htmlspecialchars((string)($item['product_number'] ?? '')) ?: '—'; ?></dd>
                        <dt class="text-slate-500">Part Number</dt><dd class="col-span-2 font-semibold text-slate-800"><?php echo htmlspecialchars((string)($item['part_number'] ?? '')) ?: '—'; ?></dd>
                    </dl>
                </dialog>
                <div class="text-xs text-slate-500 mt-0.5"><i class="far fa-clock mr-1"></i><?php echo htmlspecialchars(date('d/m/Y', strtotime((string)$item['thoi_gian_yeu_cau']))); ?></div>
                <div><span class="inline-flex border rounded-full px-2 py-0.5 mt-1 text-xs font-semibold whitespace-nowrap <?php echo $statusClass[$itemStatus] ?? 'bg-slate-100 text-slate-700'; ?>"><?php echo htmlspecialchars($statusMap[$itemStatus] ?? $itemStatus); ?></span></div>
                <?php if (!empty($item['phan_hoi'])): ?>
                <div class="mt-1 text-emerald-900 font-normal">
                    <div class="text-xs text-emerald-700"><?php echo htmlspecialchars((string)$item['nguoi_xu_ly_ten']); ?> · <?php echo htmlspecialchars((string)$item['ngay_xu_ly']); ?></div>
                    <?php echo nl2br(htmlspecialchars((string)$item['phan_hoi'])); ?>
                </div>
                <?php endif; ?>
            </td>
            <td class="px-3 py-2 text-slate-600 max-w-xs"><?php echo nl2br(htmlspecialchars((string)($item['dac_tinh_ky_thuat'] ?? ''))); ?></td>
            <td class="px-3 py-2 text-slate-600 whitespace-nowrap"><i class="fas fa-user mr-1 text-xs"></i><?php echo htmlspecialchars((string)$item['nguoi_gui_ten']); ?></td>
            <td class="px-3 py-2 whitespace-nowrap"><?php echo htmlspecialchars(rtrim(rtrim(number_format((float)$item['so_luong'], 2, '.', ''), '0'), '.')); ?> <?php echo htmlspecialchars((string)($item['don_vi_tinh'] ?? '')); ?></td>
            <td class="px-3 py-2"><?php if (!empty($item['link_mua'])): ?><a class="text-blue-600 hover:underline whitespace-nowrap" target="_blank" rel="noopener" href="<?php echo htmlspecialchars((string)$item['link_mua']); ?>"><i class="fas fa-external-link-alt mr-1"></i>Mở link</a><?php endif; ?></td>
            <td class="px-3 py-2 text-slate-600 max-w-xs"><?php echo nl2br(htmlspecialchars((string)($item['ghi_chu'] ?? ''))); ?></td>
            <td class="px-3 py-2 whitespace-nowrap">
            <?php if ($canEdit || $isManager): ?>
                <button type="button" class="open-modal bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm" data-target="modal<?php echo (int)$item['id']; ?>"><i class="fas fa-edit mr-1"></i><?php echo $isManager ? 'Xử lý' : 'Chỉnh sửa'; ?></button>
                <dialog id="modal<?php echo (int)$item['id']; ?>" class="rounded-lg shadow-xl p-0 w-full max-w-2xl backdrop:bg-black/50 text-left whitespace-normal">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-blue-200 bg-blue-50">
                        <h3 class="font-semibold text-slate-800"><?php echo htmlspecialchars((string)$item['ten_vat_tu']); ?></h3>
                        <button type="button" class="close-modal text-slate-500 hover:text-slate-800 text-xl leading-none" aria-label="Đóng">&times;</button>
                    </div>
                    <div class="p-4 space-y-5 max-h-[80vh] overflow-y-auto">
                        <?php if ($canEdit): ?>
                        <section>
                            <h4 class="text-sm font-bold text-slate-700 mb-2"><i class="fas fa-edit mr-1 text-blue-600"></i>Chỉnh sửa yêu cầu</h4>
                <form method="post" action="yeu_cau_mua_vat_tu.php?action=edit" class="bg-blue-50 border border-blue-200 rounded p-3 grid md:grid-cols-2 gap-2">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($allRequestsUrl); ?>">
                    <label class="text-xs font-semibold text-slate-700">Tên vật tư *<input required maxlength="255" name="ten_vat_tu" value="<?php echo htmlspecialchars((string)$item['ten_vat_tu']); ?>" class="mt-1 w-full border rounded px-2 py-1.5 text-sm font-normal"></label>
                    <label class="text-xs font-semibold text-slate-700">Mã vật tư<input maxlength="100" name="ma_vat_tu" value="<?php echo htmlspecialchars((string)($item['ma_vat_tu'] ?? '')); ?>" class="mt-1 w-full border rounded px-2 py-1.5 text-sm font-normal"></label>
                    <label class="text-xs font-semibold text-slate-700">Product Number<input maxlength="100" name="product_number" value="<?php echo htmlspecialchars((string)($item['product_number'] ?? '')); ?>" class="mt-1 w-full border rounded px-2 py-1.5 text-sm font-normal"></label>
                    <label class="text-xs font-semibold text-slate-700">Part Number<input maxlength="100" name="part_number" value="<?php echo htmlspecialchars((string)($item['part_number'] ?? '')); ?>" class="mt-1 w-full border rounded px-2 py-1.5 text-sm font-normal"></label>
                    <label class="text-xs font-semibold text-slate-700 md:col-span-2">Đặc tính kỹ thuật<textarea maxlength="1000" name="dac_tinh_ky_thuat" rows="2" class="mt-1 w-full border rounded px-2 py-1.5 text-sm font-normal"><?php echo htmlspecialchars((string)($item['dac_tinh_ky_thuat'] ?? '')); ?></textarea></label>
                    <div class="grid grid-cols-2 gap-2"><label class="text-xs font-semibold text-slate-700">Số lượng *<input required min="0.01" step="0.01" type="number" name="so_luong" value="<?php echo htmlspecialchars((string)$item['so_luong']); ?>" class="mt-1 w-full border rounded px-2 py-1.5 text-sm font-normal"></label><label class="text-xs font-semibold text-slate-700">Đơn vị tính<input maxlength="50" name="don_vi_tinh" value="<?php echo htmlspecialchars((string)($item['don_vi_tinh'] ?? '')); ?>" class="mt-1 w-full border rounded px-2 py-1.5 text-sm font-normal"></label></div>
                    <label class="text-xs font-semibold text-slate-700">Link mua<input type="url" maxlength="1000" name="link_mua" value="<?php echo htmlspecialchars((string)($item['link_mua'] ?? '')); ?>" class="mt-1 w-full border rounded px-2 py-1.5 text-sm font-normal"></label>
                    <label class="text-xs font-semibold text-slate-700">Thời gian yêu cầu<input required type="datetime-local" name="thoi_gian_yeu_cau" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime((string)$item['thoi_gian_yeu_cau']))); ?>" class="mt-1 w-full border rounded px-2 py-1.5 text-sm font-normal"></label>
                    <label class="text-xs font-semibold text-slate-700 md:col-span-2">Ghi chú<textarea maxlength="4000" name="ghi_chu" rows="2" class="mt-1 w-full border rounded px-2 py-1.5 text-sm font-normal"><?php echo htmlspecialchars((string)($item['ghi_chu'] ?? '')); ?></textarea></label>
                    <div class="md:col-span-2 text-right"><button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm"><i class="fas fa-save mr-1"></i>Lưu thay đổi</button></div>
                </form>
                        </section>
                        <?php endif; ?>
                        <?php if ($isManager): ?>
                        <section>
                            <h4 class="text-sm font-bold text-slate-700 mb-2"><i class="fas fa-check-circle mr-1 text-emerald-600"></i>Trạng thái &amp; phản hồi</h4>
                            <form method="post" action="yeu_cau_mua_vat_tu.php?action=update" class="flex flex-wrap items-end gap-2">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                <input type="hidden" name="redirect" value="yeu_cau_mua_vat_tu.php<?php echo $pendingOnly ? '?action=pending' : ''; ?>">
                                <label class="text-xs text-slate-500">Trạng thái<select name="trang_thai" class="block border rounded px-2 py-1.5 text-sm text-slate-800"><?php foreach ($statusMap as $key => $label): ?><option value="<?php echo htmlspecialchars($key); ?>" <?php echo $itemStatus === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option><?php endforeach; ?></select></label>
                                <label class="flex-1 min-w-[220px] text-xs text-slate-500">Phản hồi<input maxlength="4000" name="phan_hoi" value="<?php echo htmlspecialchars((string)($item['phan_hoi'] ?? '')); ?>" class="block w-full border rounded px-2 py-1.5 text-sm text-slate-800"></label>
                                <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded text-sm"><i class="fas fa-check mr-1"></i>Lưu xử lý</button>
                            </form>
                            <form method="post" action="yeu_cau_mua_vat_tu.php?action=delete" class="mt-3" onsubmit="return confirm('Xóa yêu cầu này?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                <button class="text-red-600 text-xs hover:underline"><i class="fas fa-trash mr-1"></i>Xóa yêu cầu</button>
                            </form>
                        </section>
                        <?php endif; ?>
                    </div>
                </dialog>
            <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
        document.querySelectorAll('.open-modal').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById(this.dataset.target).showModal();
            });
        });
        document.querySelectorAll('dialog .close-modal').forEach(function (btn) {
            btn.addEventListener('click', function () {
                this.closest('dialog').close();
            });
        });
        document.querySelectorAll('dialog').forEach(function (dlg) {
            dlg.addEventListener('click', function (e) {
                if (e.target === dlg) {
                    dlg.close();
                }
            });
        });
    </script>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>