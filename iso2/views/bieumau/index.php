<?php
require_once __DIR__ . '/../layouts/header.php';
?>
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="p-4 md:p-5">
        <?php if ($isAdmin): ?>
        <div class="flex flex-wrap items-center justify-between gap-2 mb-5">
            <div class="text-sm font-semibold text-slate-700"><i class="fas fa-shield-alt text-emerald-600 mr-2"></i>Khu vực quản trị</div>
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="document.getElementById('uploadPanel').classList.toggle('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm shadow-sm"><i class="fas fa-upload mr-1"></i> Tải lên</button>
                <button type="button" onclick="document.getElementById('folderPanel').classList.toggle('hidden')" class="bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg text-sm"><i class="fas fa-folder-plus mr-1 text-amber-500"></i> Tạo thư mục</button>
            </div>
        </div>
        <form id="folderPanel" method="post" action="bieu_mau.php?action=create_folder" class="hidden border border-indigo-100 rounded-lg bg-indigo-50 p-4 mb-4">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="flex flex-col md:flex-row gap-2 items-end"><label class="block text-sm font-semibold flex-1">Tên thư mục
                <input type="text" name="ten_thu_muc" maxlength="150" placeholder="Ví dụ: Final test" required class="mt-1 w-full border rounded px-3 py-2 font-normal"></label>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm">Tạo thư mục</button>
            </div>
        </form>
        <form id="uploadPanel" method="post" action="bieu_mau.php?action=upload" enctype="multipart/form-data" class="hidden border border-blue-100 rounded-lg bg-blue-50 p-4 mb-4">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="grid md:grid-cols-[1fr_1fr_1fr_auto] gap-3 items-end"><label class="block text-sm font-semibold">Tên hiển thị
                <input type="text" name="ten_hien_thi" maxlength="255" placeholder="Tự lấy theo tên file nếu bỏ trống" class="mt-1 w-full border rounded px-3 py-2 font-normal"></label>
                <label class="block text-sm font-semibold">Thư mục<select name="thu_muc_id" class="mt-1 w-full border rounded px-3 py-2 font-normal"><option value="0">Không thuộc thư mục</option><?php foreach ($folders as $folder): ?><option value="<?php echo (int)$folder['id']; ?>"><?php echo htmlspecialchars($folder['ten_thu_muc']); ?></option><?php endforeach; ?></select></label>
                <label class="block text-sm font-semibold">File Word<input type="file" name="file" accept=".doc,.docx" required class="mt-1 w-full border rounded px-3 py-1.5 bg-white font-normal"></label>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">Upload</button>
            </div><div class="text-xs text-gray-500 mt-2">Chỉ nhận .doc và .docx, tối đa 10 MB.</div>
        </form>
        <?php endif; ?>

        <div class="grid lg:grid-cols-[220px_1fr] gap-5">
            <aside class="border border-slate-200 rounded-xl p-3 h-fit bg-slate-50">
                <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold px-2 mb-3">Thư mục</div>
                <a href="bieu_mau.php" class="flex items-center gap-2 px-3 py-2.5 rounded-lg <?php echo $selectedFolderId === null ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-700 hover:bg-white'; ?> font-semibold text-sm"><i class="fas fa-layer-group"></i> Tất cả biểu mẫu<span class="ml-auto text-xs opacity-75"><?php echo count($allBieuMaus); ?></span></a>
                <?php foreach ($folders as $folder): ?>
                    <a href="bieu_mau.php?folder_id=<?php echo (int)$folder['id']; ?>" class="group flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm <?php echo $selectedFolderId === (int)$folder['id'] ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-slate-700 hover:bg-white hover:shadow-sm'; ?>"><i class="fas fa-folder text-amber-500"></i><span class="truncate flex-1"><?php echo htmlspecialchars($folder['ten_thu_muc']); ?></span><span class="text-slate-400 text-xs"><?php echo count(array_filter($allBieuMaus, fn($item) => (int)$item['thu_muc_id'] === (int)$folder['id'])); ?></span></a>
                <?php endforeach; ?>
                <?php if ($isAdmin && $folders): ?><div class="border-t mt-2 pt-2 space-y-2"><?php foreach ($folders as $folder): ?><details><summary class="cursor-pointer text-xs text-gray-500">Quản lý: <?php echo htmlspecialchars($folder['ten_thu_muc']); ?></summary><div class="flex gap-1 mt-1"><form method="post" action="bieu_mau.php?action=rename_folder" class="flex gap-1"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"><input type="hidden" name="id" value="<?php echo (int)$folder['id']; ?>"><input type="text" name="ten_thu_muc" value="<?php echo htmlspecialchars($folder['ten_thu_muc']); ?>" maxlength="150" class="border rounded px-1 py-1 text-xs w-32"><button class="text-yellow-600" title="Đổi tên"><i class="fas fa-pen"></i></button></form><form method="post" action="bieu_mau.php?action=delete_folder" onsubmit="return confirm('Xóa thư mục này?');"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"><input type="hidden" name="id" value="<?php echo (int)$folder['id']; ?>"><button class="text-red-600" title="Xóa"><i class="fas fa-trash"></i></button></form></div></details><?php endforeach; ?></div><?php endif; ?>
            </aside>
            <section class="min-w-0 border border-slate-200 rounded-xl overflow-hidden">
        <form method="get" action="bieu_mau.php" class="p-3 border-b bg-slate-50 flex flex-col sm:flex-row gap-2">
            <?php if ($selectedFolderId !== null): ?><input type="hidden" name="folder_id" value="<?php echo (int)$selectedFolderId; ?>"><?php endif; ?>
            <?php if (isset($_GET['uncategorized'])): ?><input type="hidden" name="uncategorized" value="1"><?php endif; ?>
            <div class="relative flex-1"><i class="fas fa-search absolute left-3 top-3 text-slate-400 text-sm"></i><input type="search" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm theo tên biểu mẫu hoặc thư mục..." class="w-full border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></div>
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-search mr-1"></i> Tìm kiếm</button>
            <?php if ($search !== ''): ?><a href="<?php echo $selectedFolderId !== null ? 'bieu_mau.php?folder_id=' . (int)$selectedFolderId : (isset($_GET['uncategorized']) ? 'bieu_mau.php?uncategorized=1' : 'bieu_mau.php'); ?>" class="border border-slate-200 bg-white hover:bg-slate-100 text-slate-600 px-4 py-2 rounded-lg text-sm text-center">Xóa tìm kiếm</a><?php endif; ?>
        </form>
        <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-50">
                <tr>
                    <th class="border-b px-4 py-3 text-left text-xs uppercase tracking-wide text-slate-500">Tên biểu mẫu</th>
                    <th class="border-b px-4 py-3 text-left text-xs uppercase tracking-wide text-slate-500">Dung lượng</th>
                    <th class="border-b px-4 py-3 text-left text-xs uppercase tracking-wide text-slate-500">Cập nhật</th>
                    <th class="border-b px-4 py-3 text-center text-xs uppercase tracking-wide text-slate-500">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$bieuMaus): ?>
                <tr><td colspan="4" class="border px-3 py-8 text-center text-gray-500">Chưa có biểu mẫu.</td></tr>
            <?php endif; ?>
            <?php $currentFolder = null; ?>
            <?php foreach ($bieuMaus as $item): ?>
                <?php $itemFolder = $item['ten_thu_muc'] ?: 'Không thuộc thư mục'; ?>
                <?php if ($itemFolder !== $currentFolder): $currentFolder = $itemFolder; ?>
                <tr class="bg-blue-50"><td colspan="4" class="border px-3 py-2 font-semibold text-blue-800"><i class="fas fa-folder mr-2"></i><?php echo htmlspecialchars($currentFolder); ?></td></tr>
                <?php endif; ?>
                <tr class="hover:bg-slate-50">
                    <td class="border-b px-4 py-3">
                        <div class="flex items-center gap-3 min-w-[220px]"><span class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center shrink-0"><i class="fas fa-file-word text-blue-600"></i></span><span class="font-medium text-slate-800 truncate" title="<?php echo htmlspecialchars($item['ten_hien_thi']); ?>"><?php echo htmlspecialchars($item['ten_hien_thi']); ?></span></div>
                    </td>
                    <td class="border-b px-4 py-3 text-sm text-slate-500 whitespace-nowrap"><?php echo number_format(((int)$item['kich_thuoc']) / 1024, 0, ',', '.'); ?> KB</td>
                    <td class="border-b px-4 py-3 text-sm text-slate-500 whitespace-nowrap"><?php echo htmlspecialchars((string)$item['updated_at']); ?></td>
                    <td class="border-b px-4 py-3">
                        <div class="flex items-center justify-center gap-2">
                            <a href="download_bieu_mau.php?id=<?php echo (int)$item['id']; ?>" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm whitespace-nowrap" title="Tải xuống"><i class="fas fa-download"></i><span class="hidden sm:inline ml-1">Tải xuống</span></a>
                            <?php if ($isAdmin): ?>
                            <details class="relative">
                                <summary class="list-none cursor-pointer w-9 h-9 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-500 flex items-center justify-center" title="Thao tác"><i class="fas fa-ellipsis-v"></i></summary>
                                <div class="absolute right-0 top-11 z-20 w-72 bg-white border border-slate-200 rounded-lg shadow-xl p-3 text-left">
                                    <div class="text-xs uppercase tracking-wide text-slate-400 font-semibold mb-2">Thao tác file</div>
                                    <form method="post" action="bieu_mau.php?action=move" class="flex gap-2 mb-3">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"><input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                        <select name="thu_muc_id" class="border rounded px-2 py-1.5 text-sm flex-1"><option value="0" <?php echo empty($item['thu_muc_id']) ? 'selected' : ''; ?>>Không thuộc thư mục</option><?php foreach ($folders as $folder): ?><option value="<?php echo (int)$folder['id']; ?>" <?php echo (int)$item['thu_muc_id'] === (int)$folder['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($folder['ten_thu_muc']); ?></option><?php endforeach; ?></select>
                                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-2.5 py-1.5 rounded text-sm" title="Chuyển thư mục"><i class="fas fa-folder-tree"></i></button>
                                    </form>
                                    <form method="post" action="bieu_mau.php?action=rename" class="flex gap-2 mb-3">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"><input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                        <input type="text" name="ten_hien_thi" value="<?php echo htmlspecialchars($item['ten_hien_thi']); ?>" maxlength="255" class="border rounded px-2 py-1.5 text-sm flex-1"><button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-2.5 rounded text-sm" title="Đổi tên"><i class="fas fa-pen"></i></button>
                                    </form>
                                    <form method="post" action="bieu_mau.php?action=delete" onsubmit="return confirm('Xóa biểu mẫu này?');"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"><input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>"><button type="submit" class="w-full text-left text-red-600 hover:bg-red-50 rounded px-2 py-1.5 text-sm"><i class="fas fa-trash mr-2"></i>Xóa biểu mẫu</button></form>
                                </div>
                            </details>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
            </section>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
