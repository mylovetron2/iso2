<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Phiếu Yêu Cầu HC/KĐ';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-clipboard-list mr-2"></i> 
        Phiếu Yêu Cầu Hiệu Chuẩn/Kiểm Định
    </h1>

    <!-- Filter Month/Year -->
    <form method="get" class="mb-6">
        <input type="hidden" name="action" value="phieuyc">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
            <div>
                <label class="block text-sm font-medium mb-1">Tháng</label>
                <select name="month" class="border rounded px-3 py-2 w-full">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $month == $m ? 'selected' : ''; ?>>
                            Tháng <?php echo $m; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Năm</label>
                <select name="year" class="border rounded px-3 py-2 w-full">
                    <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full">
                    <i class="fas fa-search mr-1"></i> Xem
                </button>
            </div>
            
            <div>
                <a href="bangcanhbao.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>" 
                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded inline-block text-center w-full">
                    <i class="fas fa-arrow-left mr-1"></i> Về Bảng Cảnh Báo
                </a>
            </div>
        </div>
    </form>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full table-auto border-collapse border">
            <thead class="bg-gray-200">
                <tr>
                    <th class="border px-2 py-2 text-xs md:text-sm">STT</th>
                    <th class="border px-2 py-2 text-xs md:text-sm">Tên Máy</th>
                    <th class="border px-2 py-2 text-xs md:text-sm">Số Máy</th>
                    <th class="border px-2 py-2 text-xs md:text-sm">Hãng SX</th>
                    <th class="border px-2 py-2 text-xs md:text-sm">Nơi Thực Hiện</th>
                    <th class="border px-2 py-2 text-xs md:text-sm">Chủ Sở Hữu</th>
                    <th class="border px-2 py-2 text-xs md:text-sm">Loại TB</th>
                    <th class="border px-2 py-2 text-xs md:text-sm">Ghi Chú</th>
                    <th class="border px-2 py-2 text-xs md:text-sm">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr>
                        <td colspan="9" class="border px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Không có thiết bị nào cần hiệu chuẩn trong tháng <?php echo $month; ?>/<?php echo $year; ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $sttDisplay = isset($offset) ? $offset + 1 : 1;
                    foreach ($data as $row): 
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="border px-2 py-2 text-center text-xs md:text-sm">
                            <?php echo $sttDisplay++; ?>
                        </td>
                        <td class="border px-2 py-2 text-xs md:text-sm">
                            <strong><?php echo htmlspecialchars($row['tenviettat'] ?? $row['tenthietbi']); ?></strong>
                        </td>
                        <td class="border px-2 py-2 text-xs md:text-sm">
                            <?php echo htmlspecialchars($row['somay'] ?? ''); ?>
                        </td>
                        <td class="border px-2 py-2 text-xs md:text-sm">
                            <?php echo htmlspecialchars($row['hangsx'] ?? ''); ?>
                        </td>
                        <td class="border px-2 py-2 text-xs md:text-sm">
                            <?php echo htmlspecialchars($row['noithuchien'] ?? ''); ?>
                        </td>
                        <td class="border px-2 py-2 text-xs md:text-sm">
                            <?php echo htmlspecialchars($row['chusohuu'] ?? ''); ?>
                        </td>
                        <td class="border px-2 py-2 text-xs md:text-sm">
                            <?php echo htmlspecialchars($row['loaitb'] ?? ''); ?>
                        </td>
                        <td class="border px-2 py-2 text-xs md:text-sm">
                            <?php echo htmlspecialchars($row['ghichu'] ?? ''); ?>
                        </td>
                        <td class="border px-2 py-2 text-center text-xs md:text-sm">
                            <a href="bangcanhbao.php?action=formhoso&mavattu=<?php echo urlencode($row['mavattu'] ?? ''); ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>" 
                               class="bg-blue-500 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs"
                               title="Nhập hồ sơ">
                                <i class="fas fa-edit mr-1"></i> Nhập
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="mt-4 flex justify-center">
            <nav class="inline-flex rounded-md shadow">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="?action=phieuyc&month=<?php echo $month; ?>&year=<?php echo $year; ?>&page=<?php echo $p; ?>" 
                       class="px-3 py-2 border <?php echo $p == $page ? 'bg-blue-500 text-white' : 'bg-white text-blue-600 hover:bg-blue-50'; ?> 
                              <?php echo $p == 1 ? 'rounded-l-md' : ''; ?> 
                              <?php echo $p == $totalPages ? 'rounded-r-md' : ''; ?>">
                        <?php echo $p; ?>
                    </a>
                <?php endfor; ?>
            </nav>
        </div>
    <?php endif; ?>

    <!-- Summary -->
    <div class="mt-4 text-sm text-gray-600">
        <p>Hiển thị <?php echo count($data); ?> / <?php echo $total; ?> thiết bị cần hiệu chuẩn</p>
    </div>

    <!-- Actions -->
    <div class="mt-6 flex gap-2">
        <button onclick="window.print()" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-print mr-1"></i> In Phiếu
        </button>
    </div>
</div>

<style>
@media print {
    .no-print {
        display: none;
    }
    body {
        font-size: 12px;
    }
    table {
        font-size: 11px;
    }
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
