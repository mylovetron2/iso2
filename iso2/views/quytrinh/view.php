<?php
require_once __DIR__ . '/../layouts/header.php';
$ext = strtolower(pathinfo((string)($quyTrinh['ten_luu_tru'] ?? ''), PATHINFO_EXTENSION));
$isPdf = $ext === 'pdf';
$trangThaiMap = QuyTrinhGopY::TRANG_THAI;
$trangThaiClass = [
    'moi'      => 'bg-red-100 text-red-700 border-red-200',
    'da_xem'   => 'bg-blue-100 text-blue-700 border-blue-200',
    'da_xu_ly' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
    'tu_choi'  => 'bg-slate-200 text-slate-600 border-slate-300',
];
?>
<div class="max-w-6xl mx-auto space-y-4">
    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded"><i class="fas fa-check-circle mr-1"></i><?php echo htmlspecialchars((string)$_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded"><i class="fas fa-exclamation-triangle mr-1"></i><?php echo htmlspecialchars((string)$_GET['error']); ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <div class="flex flex-wrap items-start gap-4 justify-between">
            <div class="flex items-center gap-4 min-w-0">
                <a href="quytrinh.php" class="text-slate-500 hover:text-slate-700"><i class="fas fa-arrow-left"></i></a>
                <div class="bg-gradient-to-br from-blue-600 to-indigo-600 text-white rounded-xl px-4 py-2 text-center flex-shrink-0">
                    <div class="text-[10px] uppercase tracking-wider opacity-80">Quy trình</div>
                    <div class="text-2xl font-black leading-tight">QT <?php echo htmlspecialchars((string)$quyTrinh['so_qt']); ?></div>
                </div>
                <div class="min-w-0">
                    <h1 class="text-lg md:text-xl font-bold text-slate-800"><?php echo htmlspecialchars((string)$quyTrinh['ten_qt']); ?></h1>
                    <?php if (!empty($quyTrinh['mo_ta'])): ?>
                        <p class="text-sm text-slate-500 mt-1"><?php echo nl2br(htmlspecialchars((string)$quyTrinh['mo_ta'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($hasFile): ?>
                    <a href="download_quytrinh.php?id=<?php echo (int)$quyTrinh['id']; ?>" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm shadow-sm">
                        <i class="fas fa-download mr-1"></i> Tải xuống
                    </a>
                <?php endif; ?>
                <?php if ($isManager): ?>
                    <button type="button" onclick="document.getElementById('adminPanel').classList.toggle('hidden')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-cog mr-1"></i> Quản trị
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isManager): ?>
            <div id="adminPanel" class="hidden mt-4 grid md:grid-cols-2 gap-4 border-t pt-4">
                <form method="post" action="quytrinh.php?action=upload" enctype="multipart/form-data" class="border border-blue-100 bg-blue-50 rounded-lg p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="quy_trinh_id" value="<?php echo (int)$quyTrinh['id']; ?>">
                    <div class="font-semibold text-blue-800 mb-2"><i class="fas fa-upload mr-1"></i> Tải lên tài liệu (PDF/DOC/DOCX ≤ 20MB)</div>
                    <label class="block text-sm mb-2">Tên hiển thị
                        <input type="text" name="ten_hien_thi" maxlength="255" value="<?php echo htmlspecialchars((string)($quyTrinh['ten_hien_thi'] ?? '')); ?>" class="mt-1 w-full border rounded px-3 py-2 text-sm">
                    </label>
                    <label class="block text-sm mb-3">File
                        <input type="file" name="file" accept=".pdf,.doc,.docx" required class="mt-1 w-full border rounded px-3 py-1.5 bg-white text-sm">
                    </label>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm w-full"><i class="fas fa-cloud-upload-alt mr-1"></i> Lưu tài liệu</button>
                </form>
                <form method="post" action="quytrinh.php?action=update_meta" class="border border-indigo-100 bg-indigo-50 rounded-lg p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="quy_trinh_id" value="<?php echo (int)$quyTrinh['id']; ?>">
                    <div class="font-semibold text-indigo-800 mb-2"><i class="fas fa-pen mr-1"></i> Sửa thông tin quy trình</div>
                    <label class="block text-sm mb-2">Tên quy trình
                        <input type="text" name="ten_qt" maxlength="500" required value="<?php echo htmlspecialchars((string)$quyTrinh['ten_qt']); ?>" class="mt-1 w-full border rounded px-3 py-2 text-sm">
                    </label>
                    <label class="block text-sm mb-3">Mô tả
                        <textarea name="mo_ta" rows="2" maxlength="2000" class="mt-1 w-full border rounded px-3 py-2 text-sm"><?php echo htmlspecialchars((string)($quyTrinh['mo_ta'] ?? '')); ?></textarea>
                    </label>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm w-full"><i class="fas fa-save mr-1"></i> Lưu thông tin</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="grid lg:grid-cols-[2fr_1fr] gap-4">
        <!-- Tài liệu -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-4 py-3 border-b bg-slate-50 flex items-center justify-between">
                <div class="font-semibold text-slate-700"><i class="fas fa-file-alt mr-1 text-blue-600"></i> Nội dung tài liệu</div>
                <?php if ($hasFile): ?>
                    <div class="text-xs text-slate-500">
                        <?php echo htmlspecialchars((string)($quyTrinh['ten_hien_thi'] ?? '')); ?>
                        <?php if (!empty($quyTrinh['kich_thuoc'])): ?>
                            · <?php echo number_format(((int)$quyTrinh['kich_thuoc']) / 1024, 0, ',', '.'); ?> KB
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!$hasFile): ?>
                <div class="p-8 text-center text-slate-500">
                    <i class="fas fa-file-circle-question text-4xl mb-2"></i>
                    <div>Chưa có tài liệu được tải lên cho quy trình này.</div>
                    <?php if ($isManager): ?><div class="text-sm mt-2">Vui lòng dùng khu vực <strong>Quản trị</strong> để tải lên PDF/DOC/DOCX.</div><?php endif; ?>
                </div>
            <?php elseif ($isPdf): ?>
                <iframe src="download_quytrinh.php?id=<?php echo (int)$quyTrinh['id']; ?>&view=1" class="w-full" style="height:75vh; border:0;" title="Tài liệu quy trình"></iframe>
            <?php else: ?>
                <div class="p-6">
                    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-4 text-sm">
                        <i class="fas fa-info-circle mr-1"></i>
                        Tài liệu Word không xem trực tiếp trên trình duyệt. Vui lòng
                        <a class="underline font-semibold" href="download_quytrinh.php?id=<?php echo (int)$quyTrinh['id']; ?>">tải xuống</a>
                        để đọc.
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Góp ý -->
        <div class="bg-white rounded-lg shadow overflow-hidden flex flex-col">
            <div class="px-4 py-3 border-b bg-slate-50 flex items-center justify-between">
                <div class="font-semibold text-slate-700"><i class="fas fa-comments mr-1 text-amber-600"></i> Góp ý (<?php echo count($gopYs); ?>)</div>
            </div>
            <form method="post" action="quytrinh.php?action=gopy" class="p-4 border-b bg-amber-50/50">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['csrf_token']); ?>">
                <input type="hidden" name="quy_trinh_id" value="<?php echo (int)$quyTrinh['id']; ?>">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Nhận xét / đề xuất chỉnh sửa</label>
                <textarea name="noi_dung" rows="3" required minlength="3" maxlength="4000"
                          placeholder="Bạn cho ý kiến gì về quy trình này? Ví dụ: cần bổ sung mục X, chỉnh câu Y..."
                          class="w-full border rounded px-3 py-2 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500"></textarea>
                <div class="flex items-center justify-between mt-2">
                    <div class="text-xs text-slate-500">
                        Gửi với tên: <strong><?php echo htmlspecialchars((string)($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Ẩn danh')); ?></strong>
                    </div>
                    <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded text-sm">
                        <i class="fas fa-paper-plane mr-1"></i> Gửi góp ý
                    </button>
                </div>
            </form>

            <div class="p-4 space-y-3 max-h-[70vh] overflow-y-auto">
                <?php if (!$gopYs): ?>
                    <div class="text-center text-slate-400 text-sm py-8">
                        <i class="fas fa-comment-slash text-2xl mb-2"></i>
                        <div>Chưa có góp ý nào.</div>
                    </div>
                <?php endif; ?>
                <?php foreach ($gopYs as $g): ?>
                    <?php
                        $tt = (string)($g['trang_thai'] ?? 'moi');
                        $ttLabel = $trangThaiMap[$tt] ?? $tt;
                        $ttClass = $trangThaiClass[$tt] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                    ?>
                    <div class="border border-slate-200 rounded-lg p-3">
                        <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                            <div>
                                <i class="fas fa-user-circle mr-1"></i>
                                <strong class="text-slate-700"><?php echo htmlspecialchars((string)($g['nguoi_gui_ten'] ?? 'Ẩn danh')); ?></strong>
                                · <?php echo htmlspecialchars((string)$g['created_at']); ?>
                            </div>
                            <span class="inline-flex items-center border rounded-full px-2 py-0.5 text-[11px] font-semibold <?php echo $ttClass; ?>">
                                <?php echo htmlspecialchars($ttLabel); ?>
                            </span>
                        </div>
                        <div class="text-sm text-slate-800 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars((string)$g['noi_dung'])); ?></div>

                        <?php if (!empty($g['phan_hoi'])): ?>
                            <div class="mt-2 border-l-4 border-emerald-400 bg-emerald-50 text-sm text-emerald-900 p-2 rounded-r">
                                <div class="text-xs text-emerald-700 mb-0.5">
                                    <i class="fas fa-reply mr-1"></i>
                                    Phản hồi từ <strong><?php echo htmlspecialchars((string)($g['nguoi_xu_ly_ten'] ?? '')); ?></strong>
                                    <?php if (!empty($g['ngay_xu_ly'])): ?> · <?php echo htmlspecialchars((string)$g['ngay_xu_ly']); ?><?php endif; ?>
                                </div>
                                <div class="whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars((string)$g['phan_hoi'])); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($isManager): ?>
                            <details class="mt-2">
                                <summary class="cursor-pointer text-xs text-blue-600 font-semibold"><i class="fas fa-tools mr-1"></i> Xử lý</summary>
                                <form method="post" action="quytrinh.php?action=update_gopy" class="mt-2 space-y-2">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int)$g['id']; ?>">
                                    <input type="hidden" name="redirect" value="quytrinh.php?action=view&id=<?php echo (int)$quyTrinh['id']; ?>">
                                    <div class="flex gap-2">
                                        <select name="trang_thai" class="border rounded px-2 py-1 text-xs">
                                            <?php foreach ($trangThaiMap as $k => $v): ?>
                                                <option value="<?php echo htmlspecialchars($k); ?>" <?php echo $tt === $k ? 'selected' : ''; ?>><?php echo htmlspecialchars($v); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded text-xs"><i class="fas fa-check mr-1"></i>Lưu</button>
                                    </div>
                                    <textarea name="phan_hoi" rows="2" maxlength="4000" placeholder="Phản hồi (tùy chọn)" class="w-full border rounded px-2 py-1 text-xs"><?php echo htmlspecialchars((string)($g['phan_hoi'] ?? '')); ?></textarea>
                                </form>
                                <form method="post" action="quytrinh.php?action=delete_gopy" onsubmit="return confirm('Xóa góp ý này?');" class="mt-1">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int)$g['id']; ?>">
                                    <input type="hidden" name="redirect" value="quytrinh.php?action=view&id=<?php echo (int)$quyTrinh['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-700 text-xs"><i class="fas fa-trash mr-1"></i>Xóa</button>
                                </form>
                            </details>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
