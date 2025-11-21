<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Thêm Hồ sơ SCBĐ';
require_once __DIR__ . '/../layouts/header.php'; 
?>
<div class="max-w-6xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <h1 class="text-xl md:text-2xl font-bold mb-4 md:mb-6 flex items-center">
        <i class="fas fa-plus-circle mr-2"></i> Thêm Hồ sơ Sửa chữa Bảo dưỡng
    </h1>

    <?php if (isset($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <!-- Thông tin cơ bản -->
        <div class="border-l-4 border-blue-500 pl-4">
            <h2 class="text-lg font-bold mb-3 text-blue-700">
                <i class="fas fa-info-circle mr-2"></i>Thông tin cơ bản
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Số phiếu</label>
                    <input type="text" name="phieu" value="<?php echo isset($_POST['phieu']) ? htmlspecialchars($_POST['phieu']) : $nextPhieu; ?>"
                           placeholder="Tự động: <?php echo $nextPhieu; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle"></i> Để trống sẽ tự động sinh số tiếp theo</p>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Ngày yêu cầu <span class="text-red-500">*</span></label>
                    <input type="date" name="ngayyc" required value="<?php echo isset($_POST['ngayyc']) ? $_POST['ngayyc'] : date('Y-m-d'); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded p-3 mt-3">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-robot mr-1"></i> <strong>Tự động:</strong> 
                    Mã quản lý (maql) và Mã hồ sơ (hoso) sẽ được tạo tự động khi lưu phiếu.
                </p>
            </div>
        </div>

        <!-- Thông tin thiết bị -->
        <div class="border-l-4 border-green-500 pl-4">
            <h2 class="text-lg font-bold mb-3 text-green-700">
                <i class="fas fa-cogs mr-2"></i>Thông tin thiết bị
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Mã vật tư <span class="text-red-500">*</span></label>
                    <input type="text" name="mavt" required value="<?php echo isset($_POST['mavt']) ? htmlspecialchars($_POST['mavt']) : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Số máy <span class="text-red-500">*</span></label>
                    <input type="text" name="somay" required value="<?php echo isset($_POST['somay']) ? htmlspecialchars($_POST['somay']) : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Model <span class="text-red-500">*</span></label>
                    <input type="text" name="model" required value="<?php echo isset($_POST['model']) ? htmlspecialchars($_POST['model']) : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Vị trí máy BD <span class="text-red-500">*</span></label>
                    <input type="text" name="vitrimaybd" required value="<?php echo isset($_POST['vitrimaybd']) ? htmlspecialchars($_POST['vitrimaybd']) : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Lô <span class="text-red-500">*</span></label>
                    <input type="text" name="lo" required value="<?php echo isset($_POST['lo']) ? htmlspecialchars($_POST['lo']) : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Giếng <span class="text-red-500">*</span></label>
                    <input type="text" name="gieng" required value="<?php echo isset($_POST['gieng']) ? htmlspecialchars($_POST['gieng']) : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Mỏ <span class="text-red-500">*</span></label>
                    <input type="text" name="mo" required value="<?php echo isset($_POST['mo']) ? htmlspecialchars($_POST['mo']) : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
            </div>
        </div>

        <!-- Thông tin đơn vị & yêu cầu -->
        <div class="border-l-4 border-purple-500 pl-4">
            <h2 class="text-lg font-bold mb-3 text-purple-700">
                <i class="fas fa-building mr-2"></i>Thông tin đơn vị & Yêu cầu
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Đơn vị <span class="text-red-500">*</span></label>
                    <select name="madv" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                        <option value="">-- Chọn đơn vị --</option>
                        <?php foreach ($donViList as $dv): ?>
                            <option value="<?php echo htmlspecialchars($dv['madv']); ?>" 
                                    <?php echo (isset($_POST['madv']) && $_POST['madv'] === $dv['madv']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dv['tendv']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Điện thoại</label>
                    <input type="text" name="dienthoai" value="<?php echo isset($_POST['dienthoai']) ? htmlspecialchars($_POST['dienthoai']) : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Người yêu cầu</label>
                    <input type="text" name="ngyeucau" value="<?php echo isset($_POST['ngyeucau']) ? htmlspecialchars($_POST['ngyeucau']) : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Người nhận yêu cầu</label>
                    <input type="text" name="ngnhyeucau" value="<?php echo isset($_POST['ngnhyeucau']) ? htmlspecialchars($_POST['ngnhyeucau']) : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-semibold mb-2">Công việc <span class="text-red-500">*</span></label>
                    <textarea name="cv" required rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($_POST['cv']) ? htmlspecialchars($_POST['cv']) : ''; ?></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-semibold mb-2">Yêu cầu thêm của KH</label>
                    <textarea name="ycthemkh" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($_POST['ycthemkh']) ? htmlspecialchars($_POST['ycthemkh']) : ''; ?></textarea>
                </div>
            </div>
        </div>

        <!-- Thông tin sửa chữa -->
        <div class="border-l-4 border-orange-500 pl-4">
            <h2 class="text-lg font-bold mb-3 text-orange-700">
                <i class="fas fa-wrench mr-2"></i>Thông tin sửa chữa
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Nhóm SC <span class="text-red-500">*</span></label>
                    <input type="text" name="nhomsc" required value="<?php echo isset($_POST['nhomsc']) ? htmlspecialchars($_POST['nhomsc']) : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Ngày bắt đầu TT</label>
                    <input type="date" name="ngaybdtt" value="<?php echo isset($_POST['ngaybdtt']) ? $_POST['ngaybdtt'] : date('Y-m-d'); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Ngày thực hiện</label>
                    <input type="date" name="ngayth" value="<?php echo isset($_POST['ngayth']) ? $_POST['ngayth'] : date('Y-m-d'); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Ngày kiểm tra</label>
                    <input type="date" name="ngaykt" value="<?php echo isset($_POST['ngaykt']) ? $_POST['ngaykt'] : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Số lượng</label>
                    <input type="number" name="solg" min="0" value="<?php echo isset($_POST['solg']) ? $_POST['solg'] : '0'; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">TT KT trước</label>
                    <textarea name="ttktbefore" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($_POST['ttktbefore']) ? htmlspecialchars($_POST['ttktbefore']) : ''; ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Hỏng hóc</label>
                    <textarea name="honghoc" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($_POST['honghoc']) ? htmlspecialchars($_POST['honghoc']) : ''; ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Khắc phục</label>
                    <textarea name="khacphuc" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($_POST['khacphuc']) ? htmlspecialchars($_POST['khacphuc']) : ''; ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">TT KT sau</label>
                    <textarea name="ttktafter" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($_POST['ttktafter']) ? htmlspecialchars($_POST['ttktafter']) : ''; ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Nội dung</label>
                    <textarea name="noidung" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($_POST['noidung']) ? htmlspecialchars($_POST['noidung']) : ''; ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Kết luận</label>
                    <textarea name="ketluan" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($_POST['ketluan']) ? htmlspecialchars($_POST['ketluan']) : ''; ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Xem xét xưởng</label>
                    <textarea name="xemxetxuong" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($_POST['xemxetxuong']) ? htmlspecialchars($_POST['xemxetxuong']) : ''; ?></textarea>
                </div>
            </div>
        </div>

        <!-- Thiết bị đo SC -->
        <div class="border-l-4 border-teal-500 pl-4">
            <h2 class="text-lg font-bold mb-3 text-teal-700">
                <i class="fas fa-tools mr-2"></i>Thiết bị đo sửa chữa (5 slot)
            </h2>
            <?php for ($i = 0; $i <= 4; $i++): 
                $tbField = $i == 0 ? 'tbdosc' : "tbdosc$i";
                $serialField = $i == 0 ? 'serialtbdosc' : "serialtbdosc$i";
            ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">TB đo SC <?php echo $i + 1; ?></label>
                    <input type="text" name="<?php echo $tbField; ?>" value="<?php echo isset($_POST[$tbField]) ? htmlspecialchars($_POST[$tbField]) : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Serial <?php echo $i + 1; ?></label>
                    <input type="text" name="<?php echo $serialField; ?>" value="<?php echo isset($_POST[$serialField]) ? htmlspecialchars($_POST[$serialField]) : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Bàn giao -->
        <div class="border-l-4 border-red-500 pl-4">
            <h2 class="text-lg font-bold mb-3 text-red-700">
                <i class="fas fa-handshake mr-2"></i>Thông tin bàn giao
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
                <div>
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="bg" value="1" <?php echo (isset($_POST['bg']) && $_POST['bg'] == 1) ? 'checked' : ''; ?>
                               class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring focus:ring-blue-200">
                        <span class="ml-2 text-gray-700 font-semibold">Đã bàn giao</span>
                    </label>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Số lần BG</label>
                    <input type="number" name="slbg" min="0" value="<?php echo isset($_POST['slbg']) ? $_POST['slbg'] : '0'; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Dòng</label>
                    <input type="text" name="dong" value="<?php echo isset($_POST['dong']) ? htmlspecialchars($_POST['dong']) : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Ghi chú</label>
                    <textarea name="ghichu" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($_POST['ghichu']) ? htmlspecialchars($_POST['ghichu']) : ''; ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Ghi chú cuối</label>
                    <textarea name="ghichufinal" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($_POST['ghichufinal']) ? htmlspecialchars($_POST['ghichufinal']) : ''; ?></textarea>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex flex-col md:flex-row gap-2 pt-4 border-t">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded text-base font-semibold w-full md:w-auto">
                <i class="fas fa-save mr-2"></i> Lưu hồ sơ
            </button>
            <a href="hososcbd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded text-base font-semibold text-center w-full md:w-auto">
                <i class="fas fa-times mr-2"></i> Hủy
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
