<?php
require_once __DIR__ . '/../layouts/header.php';
?>
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3"><i class="fas fa-check-circle mr-1"></i><?php echo htmlspecialchars((string)$_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3"><i class="fas fa-exclamation-triangle mr-1"></i><?php echo htmlspecialchars((string)$_GET['error']); ?></div>
    <?php endif; ?>

    <div class="p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center">
                    <i class="fas fa-book-open text-blue-600 mr-2"></i> Quy trình ISO
                </h1>
                <p class="text-sm text-slate-500 mt-1">Đọc quy trình và gửi góp ý để xem xét chỉnh sửa.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
            <?php if (!empty($isAdmin)): ?>
            <button type="button" onclick="document.getElementById('addQtForm').classList.toggle('hidden')"
                    class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm shadow-sm">
                <i class="fas fa-plus mr-2"></i> Thêm quy trình
            </button>
            <?php endif; ?>
            <?php if ($isManager): ?>
            <a href="quytrinh.php?action=pending" class="inline-flex items-center bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm shadow-sm">
                <i class="fas fa-comments mr-2"></i> Duyệt góp ý
                <?php if ($pendingCount > 0): ?>
                    <span class="ml-2 bg-white text-amber-700 rounded-full text-xs px-2 py-0.5 font-bold"><?php echo (int)$pendingCount; ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($isAdmin)): ?>
        <form id="addQtForm" method="post" action="quytrinh.php?action=create"
              class="hidden mb-6 bg-slate-50 border border-slate-200 rounded-lg p-4 space-y-3">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($_SESSION['csrf_token'] ?? '')); ?>">
            <h2 class="font-semibold text-slate-800"><i class="fas fa-plus-circle text-blue-600 mr-1"></i> Thêm quy trình mới</h2>
            <div class="grid gap-3 md:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1" for="so_qt">Số quy trình *</label>
                    <input type="text" id="so_qt" name="so_qt" required maxlength="20" pattern="[A-Za-z0-9._\-]+"
                           placeholder="VD: 26" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-slate-700 mb-1" for="ten_qt">Tên quy trình *</label>
                    <input type="text" id="ten_qt" name="ten_qt" required maxlength="500"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1" for="mo_ta">Mô tả</label>
                <textarea id="mo_ta" name="mo_ta" rows="3" maxlength="4000"
                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-save mr-1"></i> Lưu</button>
                <button type="button" onclick="document.getElementById('addQtForm').classList.add('hidden')"
                        class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-lg text-sm">Hủy</button>
            </div>
            <p class="text-xs text-slate-500">Sau khi tạo, bạn sẽ được chuyển đến trang chi tiết để tải lên tài liệu.</p>
        </form>
        <?php endif; ?>

        <?php if (!empty($databaseError)): ?>
            <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-lg p-5">
                <div class="font-semibold"><i class="fas fa-database mr-1"></i> Chưa khởi tạo dữ liệu quy trình</div>
                <p class="text-sm mt-2">Admin cần mở và chạy file <code>quy_trinh.sql</code> trong phpMyAdmin hoặc MySQL để tạo bảng và các quy trình 14, 18, 20, 25.</p>
            </div>
        <?php elseif (!$quyTrinhs): ?>
            <div class="text-center text-slate-500 py-12">
                <i class="fas fa-inbox text-4xl mb-2"></i>
                <div>Chưa có quy trình nào. Vui lòng chạy migration: <code>php run_quytrinh_migration.php</code></div>
            </div>
        <?php else: ?>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($quyTrinhs as $qt): ?>
                    <?php
                        $hasFile = !empty($qt['ten_luu_tru']);
                        $soQt = (string)$qt['so_qt'];
                        $soGopY = (int)($qt['so_gopy'] ?? 0);
                        $soGopYMoi = (int)($qt['so_gopy_moi'] ?? 0);
                    ?>
                    <a href="quytrinh.php?action=view&amp;id=<?php echo (int)$qt['id']; ?>"
                       class="block bg-white border border-slate-200 rounded-xl hover:shadow-lg hover:border-blue-400 transition-all overflow-hidden group">
                        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-5 py-4 flex items-center justify-between">
                            <div>
                                <div class="text-xs uppercase tracking-widest opacity-80">Quy trình</div>
                                <div class="text-3xl font-black leading-tight">QT <?php echo htmlspecialchars($soQt); ?></div>
                            </div>
                            <i class="fas fa-book-open text-3xl opacity-60 group-hover:opacity-100"></i>
                        </div>
                        <div class="p-5">
                            <h3 class="font-semibold text-slate-800 line-clamp-3 min-h-[3.75rem]"><?php echo htmlspecialchars((string)$qt['ten_qt']); ?></h3>
                            <?php if (!empty($qt['mo_ta'])): ?>
                                <p class="text-sm text-slate-500 mt-2 line-clamp-2"><?php echo htmlspecialchars((string)$qt['mo_ta']); ?></p>
                            <?php endif; ?>
                            <div class="flex items-center flex-wrap gap-2 mt-4 text-xs">
                                <?php if ($hasFile): ?>
                                    <span class="inline-flex items-center bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full font-semibold">
                                        <i class="fas fa-file-alt mr-1"></i> Có tài liệu
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center bg-slate-100 text-slate-500 px-2 py-1 rounded-full">
                                        <i class="fas fa-file mr-1"></i> Chưa có tài liệu
                                    </span>
                                <?php endif; ?>
                                <span class="inline-flex items-center bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-semibold">
                                    <i class="fas fa-comment-dots mr-1"></i> <?php echo $soGopY; ?> góp ý
                                </span>
                                <?php if ($soGopYMoi > 0): ?>
                                    <span class="inline-flex items-center bg-red-100 text-red-700 px-2 py-1 rounded-full font-semibold">
                                        <i class="fas fa-circle text-[6px] mr-1"></i> <?php echo $soGopYMoi; ?> mới
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="mt-4 text-sm text-blue-600 font-semibold flex items-center">
                                Xem chi tiết <i class="fas fa-arrow-right ml-2 transition-transform group-hover:translate-x-1"></i>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
