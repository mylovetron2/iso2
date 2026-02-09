<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Sửa Vật Tư Thanh Lý';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">
        <i class="fas fa-edit mr-2"></i> Sửa Vật Tư Thanh Lý
    </h1>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($item)): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            Không tìm thấy vật tư này hoặc có lỗi xảy ra.
            <a href="/iso2/vattuthanhly.php" class="underline">Quay lại danh sách</a>
        </div>
    <?php else: ?>

    <form method="POST" action="vattuthanhly.php?action=edit&id=<?php echo $item['stt'] ?? ''; ?>">>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-medium mb-2">Mã vật tư <span class="text-red-500">*</span></label>
                <input type="text" name="mavattu" required 
                       value="<?php echo htmlspecialchars($item['mavattu'] ?? ''); ?>"
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block font-medium mb-2">Số serial/Số hiệu</label>
                <input type="text" name="so_serial" 
                       value="<?php echo htmlspecialchars($item['so_serial'] ?? ''); ?>"
                       class="w-full border rounded px-3 py-2" 
                       placeholder="Ví dụ: SN123456">
            </div>

            <div>
                <label class="block font-medium mb-2">Phân loại <span class="text-red-500">*</span></label>
                <select name="phanloai_id" required class="w-full border rounded px-3 py-2">
                    <?php foreach ($phanLoaiList as $pl): ?>
                        <option value="<?php echo $pl['id']; ?>" 
                                <?php echo ($item['phanloai_id'] ?? 1) == $pl['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($pl['ten_phanloai']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block font-medium mb-2">Vị trí sắp xếp</label>
                <input type="number" name="vi_tri_sap_xep" 
                       value="<?php echo htmlspecialchars($item['vi_tri_sap_xep'] ?? 999); ?>"
                       class="w-full border rounded px-3 py-2" 
                       placeholder="Số nhỏ hiển thị trước (mặc định: 999)">
                <p class="text-xs text-gray-500 mt-1">Số nhỏ sẽ hiển thị trước trong danh sách</p>
            </div>

            <!-- Tên vật tư - 3 ngôn ngữ -->
            <div class="md:col-span-2">
                <label class="block font-medium mb-2 text-blue-600">
                    <i class="fas fa-language"></i> Tên vật tư - Tiếng Anh
                </label>
                <textarea name="ten_tienganh" rows="2"
                          class="w-full border-2 border-blue-200 rounded px-3 py-2" 
                          placeholder="English name"><?php echo htmlspecialchars($item['ten_tienganh'] ?? ''); ?></textarea>
            </div>

            <div class="md:col-span-2">
                <label class="block font-medium mb-2 text-red-600">
                    <i class="fas fa-language"></i> Tên vật tư - Tiếng Nga
                </label>
                <textarea name="ten_tiengnga" rows="2"
                          class="w-full border-2 border-red-200 rounded px-3 py-2" 
                          placeholder="Название на русском"><?php echo htmlspecialchars($item['ten_tiengnga'] ?? ''); ?></textarea>
            </div>

            <div class="md:col-span-2">
                <label class="block font-medium mb-2 text-green-600">
                    <i class="fas fa-language"></i> Tên vật tư - Tiếng Việt
                </label>
                <textarea name="ten_tiengviet" rows="2"
                          class="w-full border-2 border-green-200 rounded px-3 py-2" 
                          placeholder="Tên tiếng Việt"><?php echo htmlspecialchars($item['ten_tiengviet'] ?? ''); ?></textarea>
            </div>

            <div>
                <label class="block font-medium mb-2">ĐVT Tiếng Nga</label>
                <input type="text" name="dvt_tiengnga" 
                       value="<?php echo htmlspecialchars($item['dvt_tiengnga'] ?? ''); ?>"
                       class="w-full border rounded px-3 py-2" 
                       placeholder="шт. / комплект / кг">
            </div>

            <div>
                <label class="block font-medium mb-2">ĐVT Tiếng Việt</label>
                <input type="text" name="dvt_tiengviet" 
                       value="<?php echo htmlspecialchars($item['dvt_tiengviet'] ?? ''); ?>"
                       class="w-full border rounded px-3 py-2" 
                       placeholder="cái / bộ / kg">
            </div>

            <!-- Đặc tính kỹ thuật - 2 ngôn ngữ -->
            <div class="md:col-span-2">
                <label class="block font-medium mb-2 text-red-600">
                    <i class="fas fa-cog"></i> Đặc tính kỹ thuật - Tiếng Nga
                </label>
                <textarea name="dactinhkt_tiengnga" rows="2"
                          class="w-full border-2 border-red-200 rounded px-3 py-2" 
                          placeholder="Технические характеристики"><?php echo htmlspecialchars($item['dactinhkt_tiengnga'] ?? ''); ?></textarea>
            </div>

            <div class="md:col-span-2">
                <label class="block font-medium mb-2 text-green-600">
                    <i class="fas fa-cog"></i> Đặc tính kỹ thuật - Tiếng Việt
                </label>
                <textarea name="dactinhkt_tiengviet" rows="2"
                          class="w-full border-2 border-green-200 rounded px-3 py-2" 
                          placeholder="Đặc tính kỹ thuật"><?php echo htmlspecialchars($item['dactinhkt_tiengviet'] ?? ''); ?></textarea>
            </div>

            <div>
                <label class="block font-medium mb-2">Số lượng còn lại</label>
                <input type="number" step="0.01" name="soluong_conlai" 
                       value="<?php echo $item['soluong_conlai'] ?? ''; ?>"
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block font-medium mb-2">Đơn giá (VNĐ)</label>
                <input type="number" step="1" name="dongia" 
                       value="<?php echo $item['dongia'] ?? ''; ?>"
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block font-medium mb-2">Ngày nhận</label>
                <input type="date" name="ngaynhan" 
                       value="<?php echo $item['ngaynhan'] ?? ''; ?>"
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block font-medium mb-2">Số hợp đồng</label>
                <input type="text" name="sohd" 
                       value="<?php echo htmlspecialchars($item['sohd'] ?? ''); ?>"
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block font-medium mb-2">Ngày ký hợp đồng</label>
                <input type="date" name="ngaykyhd" 
                       value="<?php echo $item['ngaykyhd'] ?? ''; ?>"
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block font-medium mb-2">Người quản lý</label>
                <input type="text" name="nguoiquanly" 
                       value="<?php echo htmlspecialchars($item['nguoiquanly'] ?? ''); ?>"
                       class="w-full border rounded px-3 py-2">
            </div>

            <div class="md:col-span-2">
                <label class="block font-medium mb-2">Vị trí bảo quản</label>
                <input type="text" name="vitribaoquan" 
                       value="<?php echo htmlspecialchars($item['vitribaoquan'] ?? ''); ?>"
                       class="w-full border rounded px-3 py-2">
            </div>

            <div class="md:col-span-2">
                <label class="block font-medium mb-2">Ghi chú</label>
                <textarea name="ghichu" rows="2"
                          class="w-full border rounded px-3 py-2"><?php echo htmlspecialchars($item['ghichu'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                <i class="fas fa-save mr-1"></i> Lưu
            </button>
            <a href="vattuthanhly.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
        </div>
    </form>
    
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
