<?php
require_once __DIR__ . '/../layouts/header.php';
$trangThaiMap = QuyTrinhGopY::TRANG_THAI;
?>
<div class="max-w-6xl mx-auto bg-white rounded-lg shadow p-4 md:p-6">
    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded mb-3"><?php echo htmlspecialchars((string)$_GET['success']); ?></div>
    <?php endif; ?>

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center">
            <i class="fas fa-comments text-amber-600 mr-2"></i> Góp ý quy trình cần xem xét
        </h1>
        <a href="quytrinh.php" class="text-blue-600 hover:underline text-sm"><i class="fas fa-arrow-left mr-1"></i> Danh sách quy trình</a>
    </div>

    <?php if (!$items): ?>
        <div class="text-center text-slate-500 py-12">
            <i class="fas fa-check-circle text-4xl mb-2 text-emerald-500"></i>
            <div>Không có góp ý mới cần xử lý.</div>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($items as $g): ?>
                <div class="border border-slate-200 rounded-lg p-4 hover:shadow">
                    <div class="flex items-start justify-between flex-wrap gap-2">
                        <div class="min-w-0">
                            <div class="text-xs text-slate-500 mb-1">
                                <span class="inline-flex items-center bg-blue-100 text-blue-700 rounded-full px-2 py-0.5 font-semibold mr-2">
                                    QT <?php echo htmlspecialchars((string)$g['so_qt']); ?>
                                </span>
                                <i class="fas fa-user-circle mr-1"></i>
                                <strong class="text-slate-700"><?php echo htmlspecialchars((string)($g['nguoi_gui_ten'] ?? 'Ẩn danh')); ?></strong>
                                · <?php echo htmlspecialchars((string)$g['created_at']); ?>
                            </div>
                            <div class="text-sm font-semibold text-slate-800"><?php echo htmlspecialchars((string)$g['ten_qt']); ?></div>
                            <div class="text-sm text-slate-700 whitespace-pre-wrap mt-1"><?php echo nl2br(htmlspecialchars((string)$g['noi_dung'])); ?></div>
                        </div>
                        <a href="quytrinh.php?action=view&id=<?php echo (int)$g['quy_trinh_id']; ?>" class="text-blue-600 hover:underline text-sm whitespace-nowrap">
                            Mở quy trình <i class="fas fa-external-link-alt ml-1"></i>
                        </a>
                    </div>
                    <form method="post" action="quytrinh.php?action=update_gopy" class="mt-3 flex flex-wrap items-end gap-2">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="id" value="<?php echo (int)$g['id']; ?>">
                        <input type="hidden" name="redirect" value="quytrinh.php?action=pending">
                        <label class="text-xs">
                            <div class="text-slate-500 mb-1">Trạng thái</div>
                            <select name="trang_thai" class="border rounded px-2 py-1 text-sm">
                                <?php foreach ($trangThaiMap as $k => $v): ?>
                                    <option value="<?php echo htmlspecialchars($k); ?>" <?php echo ((string)$g['trang_thai'] === $k) ? 'selected' : ''; ?>><?php echo htmlspecialchars($v); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="flex-1 min-w-[200px] text-xs">
                            <div class="text-slate-500 mb-1">Phản hồi</div>
                            <input type="text" name="phan_hoi" maxlength="4000" value="<?php echo htmlspecialchars((string)($g['phan_hoi'] ?? '')); ?>" class="w-full border rounded px-2 py-1 text-sm">
                        </label>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-1.5 rounded text-sm"><i class="fas fa-check mr-1"></i>Lưu</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
