<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Thống Kê Vật Tư Thanh Lý';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-chart-bar mr-2 text-red-600"></i> Thống Kê Vật Tư Thanh Lý
        </h1>
        <div class="flex gap-2">
            <?php if (!empty($items)): ?>
            <a href="thongke_vattu_thanh_ly.php?action=exportWord&tungay=<?php echo urlencode($tungay); ?>&denngay=<?php echo urlencode($denngay); ?>&search=<?php echo urlencode($search); ?>&phanloai_id=<?php echo urlencode($phanloai_id ?? ''); ?>&bophan=<?php echo urlencode($bophan ?? ''); ?>" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-file-word mr-1"></i> Xuất Word
            </a>
            <a href="thongke_vattu_thanh_ly.php?action=exportPhieuKSVT&tungay=<?php echo urlencode($tungay); ?>&denngay=<?php echo urlencode($denngay); ?>&search=<?php echo urlencode($search); ?>&phanloai_id=<?php echo urlencode($phanloai_id ?? ''); ?>&bophan=<?php echo urlencode($bophan ?? ''); ?>" 
               target="_blank"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-file-alt mr-1"></i> In phiếu KSVT
            </a>
            <?php endif; ?>
            <a href="vattuthanhly.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </div>
    </div>

    <!-- Messages -->
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Filter Form -->
    <form method="get" class="mb-6 bg-gray-50 p-4 rounded-lg">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-calendar-alt text-blue-600"></i> Từ ngày
                </label>
                <input type="date" 
                       name="tungay" 
                       value="<?php echo htmlspecialchars($tungay); ?>" 
                       class="border rounded px-3 py-2 w-full">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-calendar-check text-blue-600"></i> Đến ngày
                </label>
                <input type="date" 
                       name="denngay" 
                       value="<?php echo htmlspecialchars($denngay); ?>" 
                       class="border rounded px-3 py-2 w-full">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-tags text-blue-600"></i> Phân loại
                </label>
                <select name="phanloai_id" class="border rounded px-3 py-2 w-full">
                    <option value="">Tất cả phân loại</option>
                    <?php foreach ($phanloaiList as $pl): ?>
                    <option value="<?php echo $pl['id']; ?>" 
                            <?php echo (isset($phanloai_id) && $phanloai_id == $pl['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($pl['ten_phanloai']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-building text-blue-600"></i> Bộ phận
                </label>
                <select name="bophan" class="border rounded px-3 py-2 w-full">
                    <option value="">Tất cả bộ phận</option>
                    <?php foreach ($donViList as $dv): ?>
                        <?php 
                        // Bỏ qua TH và CNC (đã gộp vào DVLTH - Đội ĐVL Tổng hợp)
                        if ($dv['madv'] === 'TH' || $dv['madv'] === 'CNC') continue;
                        ?>
                    <option value="<?php echo htmlspecialchars($dv['madv']); ?>"
                            <?php echo (isset($bophan) && $bophan === $dv['madv']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dv['tendv']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-search text-blue-600"></i> Tìm kiếm
                </label>
                <input type="text" 
                       name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Mã VT, Tên VT, Nguyên nhân..."
                       class="border rounded px-3 py-2 w-full">
            </div>
            
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex-1">
                    <i class="fas fa-filter mr-1"></i> Lọc
                </button>
                <a href="thongke_vattu_thanh_ly.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </div>
    </form>

    <!-- Summary Cards -->
    <?php if (!empty($items)): ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-600 font-medium">Tổng số vật tư</p>
                    <p class="text-2xl font-bold text-blue-800"><?php echo number_format($total); ?></p>
                </div>
                <i class="fas fa-list-ol text-3xl text-blue-300"></i>
            </div>
        </div>
        
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-600 font-medium">Tổng số lượng</p>
                    <p class="text-2xl font-bold text-green-800"><?php echo number_format($totalQuantity, 2); ?></p>
                </div>
                <i class="fas fa-boxes text-3xl text-green-300"></i>
            </div>
        </div>
        
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-red-600 font-medium">Tổng giá trị</p>
                    <p class="text-2xl font-bold text-red-800"><?php echo number_format($totalAmount, 0, ',', '.'); ?> đ</p>
                </div>
                <i class="fas fa-dong-sign text-3xl text-red-300"></i>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Data Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-3 border text-center font-semibold" style="width: 4%;">
                        TT<br>
                        <span class="text-xs font-normal text-gray-600">ПП</span>
                    </th>
                    <th class="px-3 py-3 border text-left font-semibold" style="width: 12%;">
                        Mã vật tư<br>
                        <span class="text-xs font-normal text-gray-600">Номенкла-турный код</span>
                    </th>
                    <th class="px-3 py-3 border text-left font-semibold" style="width: 25%;">
                        Tên vật tư<br>
                        <span class="text-xs font-normal text-gray-600">Наименование материалов</span>
                    </th>
                    <th class="px-3 py-3 border text-center font-semibold" style="width: 8%;">
                        Đơn vị<br>
                        <span class="text-xs font-normal text-gray-600">Ед. изм</span>
                    </th>
                    <th class="px-3 py-3 border text-center font-semibold" style="width: 7%;">
                        Năm SD<br>
                        <span class="text-xs font-normal text-gray-600">Срок эксплуа-тации (лет)</span>
                    </th>
                    <th class="px-3 py-3 border text-center font-semibold" style="width: 8%;">
                        Số lượng<br>
                        <span class="text-xs font-normal text-gray-600">Кол-во</span>
                    </th>
                    <th class="px-3 py-3 border text-center font-semibold" style="width: 10%;">
                        Đơn giá<br>
                        <span class="text-xs font-normal text-gray-600">Цена</span>
                    </th>
                    <th class="px-3 py-3 border text-center font-semibold" style="width: 10%;">
                        Thành tiền<br>
                        <span class="text-xs font-normal text-gray-600">Сумма</span>
                    </th>
                    <th class="px-3 py-3 border text-left font-semibold" style="width: 16%;">
                        Nguyên nhân<br>
                        <span class="text-xs font-normal text-gray-600">Причина списания</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Không có dữ liệu thanh lý trong khoảng thời gian này</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php 
                $stt = 1; 
                foreach ($items as $item): 
                    $tenvattu = $item['ten_tiengviet'] ?: $item['ten_tienganh'] ?: $item['ten_tiengnga'];
                    $soluong = $item['soluong_thaydoi'];
                    $dongia = $item['dongia'];
                    $thanhtien = $item['thanhtien'];
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 border text-center"><?php echo $stt; ?></td>
                    <td class="px-3 py-2 border">
                        <span class="font-mono text-blue-700"><?php echo htmlspecialchars($item['mavattu']); ?></span>
                    </td>
                    <td class="px-3 py-2 border">
                        <?php echo htmlspecialchars($tenvattu); ?>
                    </td>
                    <td class="px-3 py-2 border text-center">
                        <?php echo htmlspecialchars($item['donvi'] ?? ''); ?>
                    </td>
                    <td class="px-3 py-2 border text-center">
                        <?php echo htmlspecialchars($item['namsd'] ?? ''); ?>
                    </td>
                    <td class="px-3 py-2 border text-right">
                        <?php echo number_format($soluong, 2, ',', '.'); ?>
                    </td>
                    <td class="px-3 py-2 border text-right">
                        <?php echo number_format($dongia, 2, ',', '.'); ?>
                    </td>
                    <td class="px-3 py-2 border text-right font-semibold">
                        <?php echo number_format($thanhtien, 2, ',', '.'); ?>
                    </td>
                    <td class="px-3 py-2 border">
                        <?php 
                        $nguyennhan = $item['nguyennhan'] ?: 'Không rõ';
                        echo htmlspecialchars($nguyennhan); 
                        ?>
                    </td>
                </tr>
                <?php 
                $stt++; 
                endforeach; 
                ?>
                <!-- Total Row -->
                <tr class="bg-yellow-50 font-bold border-t-2 border-gray-800">
                    <td colspan="5" class="px-3 py-3 border text-center text-lg">
                        TỔNG CỘNG / ИТОГО
                    </td>
                    <td class="px-3 py-3 border text-right text-lg">
                        <?php echo number_format($totalQuantity, 2, ',', '.'); ?>
                    </td>
                    <td class="px-3 py-3 border"></td>
                    <td class="px-3 py-3 border text-right text-lg text-red-700">
                        <?php echo number_format($totalAmount, 2, ',', '.'); ?>
                    </td>
                    <td class="px-3 py-3 border"></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($items)): ?>
    <div class="mt-4 text-sm text-gray-600">
        <p><i class="fas fa-info-circle mr-1"></i> Tổng cộng: <strong><?php echo number_format($total); ?></strong> vật tư thanh lý</p>
        <p><i class="fas fa-calendar mr-1"></i> Thời gian: <strong><?php echo date('d/m/Y', strtotime($tungay)); ?></strong> đến <strong><?php echo date('d/m/Y', strtotime($denngay)); ?></strong></p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
