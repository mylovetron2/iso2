<?php
$title = 'Nhận Hàng - ' . $phieu['ma_phieu'];
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-6">
            <i class="fas fa-box text-green-600 mr-2"></i> Nhận Hàng - <?php echo htmlspecialchars($phieu['ma_phieu']); ?>
        </h1>

        <form method="POST" action="phieudathang.php?action=receive">
            <input type="hidden" name="phieu_id" value="<?php echo $phieu['id']; ?>">

            <div class="overflow-x-auto mb-6">
                <table class="w-full border">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-3 py-2">STT</th>
                            <th class="border px-3 py-2">Tên vật tư</th>
                            <th class="border px-3 py-2 text-center">Đã đặt</th>
                            <th class="border px-3 py-2 text-center">Đã nhận trước</th>
                            <th class="border px-3 py-2 text-center">Còn lại</th>
                            <th class="border px-3 py-2 text-center">Nhận lần này</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($chi_tiet as $index => $item): ?>
                            <?php $con_lai = $item['so_luong_dat'] - $item['so_luong_nhan']; ?>
                            <?php if ($con_lai > 0): ?>
                                <tr>
                                    <td class="border px-3 py-2 text-center"><?php echo $index + 1; ?></td>
                                    <td class="border px-3 py-2">
                                        <?php echo htmlspecialchars($item['ten_tiengviet']); ?>
                                        <input type="hidden" name="chi_tiet_id[]" value="<?php echo $item['id']; ?>">
                                    </td>
                                    <td class="border px-3 py-2 text-center font-semibold"><?php echo $item['so_luong_dat']; ?></td>
                                    <td class="border px-3 py-2 text-center text-gray-600"><?php echo $item['so_luong_nhan']; ?></td>
                                    <td class="border px-3 py-2 text-center text-red-600 font-semibold"><?php echo $con_lai; ?></td>
                                    <td class="border px-3 py-2 text-center">
                                        <input type="number" name="so_luong_nhan[]" min="0" max="<?php echo $con_lai; ?>" 
                                               value="<?php echo $con_lai; ?>" 
                                               class="w-24 border rounded px-2 py-1 text-center">
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium mb-1">Vị trí kho</label>
                    <input type="text" name="vi_tri_kho" 
                           class="w-full border rounded px-3 py-2" 
                           placeholder="Nhập vị trí kho (tùy chọn)">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Ghi chú nhập kho</label>
                    <input type="text" name="ghi_chu" 
                           class="w-full border rounded px-3 py-2" 
                           placeholder="Nhập ghi chú (tùy chọn)">
                </div>
            </div>

            <div class="flex gap-3 justify-end">
                <a href="phieudathang.php?action=view&id=<?php echo $phieu['id']; ?>" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                    <i class="fas fa-times mr-1"></i> Hủy
                </a>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                    <i class="fas fa-check mr-1"></i> Xác Nhận Nhập Hàng
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
