<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Thêm Vật Tư Thanh Lý';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">
        <i class="fas fa-plus-circle mr-2"></i> Thêm Vật Tư Thanh Lý
    </h1>

    <form method="POST" action="vattuthanhly.php?action=create">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-medium mb-2">Mã vật tư <span class="text-red-500">*</span></label>
                <input type="text" name="mavattu" required 
                       class="w-full border rounded px-3 py-2" 
                       placeholder="Ví dụ: 011.004.00521">
            </div>

            <div>
                <label class="block font-medium mb-2">Số serial/Số hiệu</label>
                <input type="text" name="so_serial" 
                       class="w-full border rounded px-3 py-2" 
                       placeholder="Ví dụ: SN123456">
            </div>

            <div>
                <label class="block font-medium mb-2">Phân loại <span class="text-red-500">*</span></label>
                <select name="phanloai_id" required class="w-full border rounded px-3 py-2">
                    <?php foreach ($phanLoaiList as $pl): ?>
                        <option value="<?php echo $pl['id']; ?>">
                            <?php echo htmlspecialchars($pl['ten_phanloai']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block font-medium mb-2">Vị trí sắp xếp</label>
                <input type="number" name="vi_tri_sap_xep" 
                       value="999"
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
                          placeholder="English name (e.g., Capacitor 10uF 25V X8L, RADIAL)"></textarea>
            </div>

            <div class="md:col-span-2">
                <label class="block font-medium mb-2 text-red-600">
                    <i class="fas fa-language"></i> Tên vật tư - Tiếng Nga
                </label>
                <textarea name="ten_tiengnga" rows="2"
                          class="w-full border-2 border-red-200 rounded px-3 py-2" 
                          placeholder="Название на русском (например, Конденсатор 10uF 25V X8L)"></textarea>
            </div>

            <div class="md:col-span-2">
                <label class="block font-medium mb-2 text-green-600">
                    <i class="fas fa-language"></i> Tên vật tư - Tiếng Việt
                </label>
                <textarea name="ten_tiengviet" rows="2"
                          class="w-full border-2 border-green-200 rounded px-3 py-2" 
                          placeholder="Tên tiếng Việt (ví dụ: Tụ điện 10uF 25V X8L, RADIAL)"></textarea>
            </div>

            <div>
                <label class="block font-medium mb-2">ĐVT Tiếng Nga</label>
                <input type="text" name="dvt_tiengnga" 
                       class="w-full border rounded px-3 py-2" 
                       placeholder="шт. / комплект / кг">
            </div>

            <div>
                <label class="block font-medium mb-2">ĐVT Tiếng Việt</label>
                <input type="text" name="dvt_tiengviet" 
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
                          placeholder="Технические характеристики"></textarea>
            </div>

            <div class="md:col-span-2">
                <label class="block font-medium mb-2 text-green-600">
                    <i class="fas fa-cog"></i> Đặc tính kỹ thuật - Tiếng Việt
                </label>
                <textarea name="dactinhkt_tiengviet" rows="2"
                          class="w-full border-2 border-green-200 rounded px-3 py-2" 
                          placeholder="Đặc tính kỹ thuật"></textarea>
            </div>

            <div>
                <label class="block font-medium mb-2">Số lượng còn lại</label>
                <input type="number" step="0.01" name="soluong_conlai" 
                       class="w-full border rounded px-3 py-2" 
                       placeholder="0.00">
            </div>

            <div>
                <label class="block font-medium mb-2">Đơn giá (VNĐ)</label>
                <input type="number" step="1" name="dongia" 
                       class="w-full border rounded px-3 py-2" 
                       placeholder="0">
            </div>

            <div>
                <label class="block font-medium mb-2">Đơn giá (USD)</label>
                <input type="number" step="0.01" name="dongia_usd" 
                       class="w-full border rounded px-3 py-2" 
                       placeholder="0.00">
            </div>

            <div>
                <label class="block font-medium mb-2">Ngày nhận</label>
                <input type="date" name="ngaynhan" 
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block font-medium mb-2">Số hợp đồng</label>
                <input type="text" name="sohd" 
                       class="w-full border rounded px-3 py-2" 
                       placeholder="Ví dụ: 0044/25/DVL-STE">
            </div>

            <div>
                <label class="block font-medium mb-2">Ngày ký hợp đồng</label>
                <input type="date" name="ngaykyhd" 
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block font-medium mb-2">Người quản lý</label>
                <input type="text" name="nguoiquanly" 
                       class="w-full border rounded px-3 py-2" 
                       placeholder="Họ tên người quản lý">
            </div>

            <div class="md:col-span-2">
                <label class="block font-medium mb-2">Vị trí bảo quản</label>
                <input type="text" name="vitribaoquan" 
                       class="w-full border rounded px-3 py-2" 
                       placeholder="Kho, tầng, vị trí...">
            </div>

            <div class="md:col-span-2">
                <label class="block font-medium mb-2">Ghi chú</label>
                <textarea name="ghichu" rows="2"
                          class="w-full border rounded px-3 py-2"></textarea>
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
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
