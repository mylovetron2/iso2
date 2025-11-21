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

        <!-- Thông tin thiết bị (5 slots) -->
        <div class="border-l-4 border-green-500 pl-4">
            <h2 class="text-lg font-bold mb-3 text-green-700">
                <i class="fas fa-cogs mr-2"></i>Thông tin thiết bị (nhập tối đa 5 thiết bị)
            </h2>
            <div class="bg-green-50 border border-green-200 rounded p-3 mb-4">
                <p class="text-sm text-green-800">
                    <i class="fas fa-info-circle mr-1"></i> 
                    Bạn có thể nhập từ 1 đến 5 thiết bị cùng lúc. Chỉ cần điền vào các thiết bị bạn muốn thêm.
                </p>
            </div>

            <?php for ($i = 1; $i <= 5; $i++): ?>
            <div class="border border-gray-300 rounded-lg p-4 mb-4 bg-gray-50">
                <h3 class="font-bold text-gray-700 mb-3 flex items-center">
                    <span class="bg-green-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2"><?php echo $i; ?></span>
                    Thiết bị <?php echo $i; ?><?php if ($i === 1): ?> <span class="text-red-500 ml-1">*</span><?php endif; ?>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm text-gray-700 font-semibold mb-1">
                            Mã vật tư<?php if ($i === 1): ?> <span class="text-red-500">*</span><?php endif; ?>
                        </label>
                        <input type="text" name="devices[<?php echo $i; ?>][mavt]" 
                               <?php if ($i === 1): ?>required<?php endif; ?>
                               value="<?php echo isset($_POST['devices'][$i]['mavt']) ? htmlspecialchars($_POST['devices'][$i]['mavt']) : ''; ?>"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 font-semibold mb-1">
                            Số máy<?php if ($i === 1): ?> <span class="text-red-500">*</span><?php endif; ?>
                        </label>
                        <input type="text" name="devices[<?php echo $i; ?>][somay]" 
                               <?php if ($i === 1): ?>required<?php endif; ?>
                               value="<?php echo isset($_POST['devices'][$i]['somay']) ? htmlspecialchars($_POST['devices'][$i]['somay']) : ''; ?>"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 font-semibold mb-1">
                            Model<?php if ($i === 1): ?> <span class="text-red-500">*</span><?php endif; ?>
                        </label>
                        <input type="text" name="devices[<?php echo $i; ?>][model]" 
                               <?php if ($i === 1): ?>required<?php endif; ?>
                               value="<?php echo isset($_POST['devices'][$i]['model']) ? htmlspecialchars($_POST['devices'][$i]['model']) : ''; ?>"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 font-semibold mb-1">
                            Vị trí máy BD<?php if ($i === 1): ?> <span class="text-red-500">*</span><?php endif; ?>
                        </label>
                        <input type="text" name="devices[<?php echo $i; ?>][vitrimaybd]" 
                               <?php if ($i === 1): ?>required<?php endif; ?>
                               value="<?php echo isset($_POST['devices'][$i]['vitrimaybd']) ? htmlspecialchars($_POST['devices'][$i]['vitrimaybd']) : ''; ?>"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 font-semibold mb-1">
                            Lô<?php if ($i === 1): ?> <span class="text-red-500">*</span><?php endif; ?>
                        </label>
                        <input type="text" name="devices[<?php echo $i; ?>][lo]" 
                               <?php if ($i === 1): ?>required<?php endif; ?>
                               value="<?php echo isset($_POST['devices'][$i]['lo']) ? htmlspecialchars($_POST['devices'][$i]['lo']) : ''; ?>"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 font-semibold mb-1">
                            Giếng<?php if ($i === 1): ?> <span class="text-red-500">*</span><?php endif; ?>
                        </label>
                        <input type="text" name="devices[<?php echo $i; ?>][gieng]" 
                               <?php if ($i === 1): ?>required<?php endif; ?>
                               value="<?php echo isset($_POST['devices'][$i]['gieng']) ? htmlspecialchars($_POST['devices'][$i]['gieng']) : ''; ?>"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-sm text-gray-700 font-semibold mb-1">
                            Mỏ<?php if ($i === 1): ?> <span class="text-red-500">*</span><?php endif; ?>
                        </label>
                        <input type="text" name="devices[<?php echo $i; ?>][mo]" 
                               <?php if ($i === 1): ?>required<?php endif; ?>
                               value="<?php echo isset($_POST['devices'][$i]['mo']) ? htmlspecialchars($_POST['devices'][$i]['mo']) : ''; ?>"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Thông tin đơn vị & yêu cầu -->
        <div class="border-l-4 border-purple-500 pl-4">
            <h2 class="text-lg font-bold mb-3 text-purple-700">
                <i class="fas fa-building mr-2"></i>Thông tin đơn vị & Yêu cầu
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Đơn vị <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <select name="madv" id="madvSelect" required class="flex-1 px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                            <option value="">-- Chọn đơn vị --</option>
                            <?php foreach ($donViList as $dv): ?>
                                <option value="<?php echo htmlspecialchars($dv['madv']); ?>" 
                                        <?php echo (isset($_POST['madv']) && $_POST['madv'] === $dv['madv']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dv['tendv']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" onclick="openAddUnitModal()" 
                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded font-bold text-lg"
                                title="Thêm đơn vị mới">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
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

<!-- Modal: Add Unit -->
<div id="addUnitModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="bg-green-600 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
            <h3 class="text-lg font-bold">
                <i class="fas fa-plus-circle mr-2"></i>Thêm đơn vị mới
            </h3>
            <button onclick="closeAddUnitModal()" class="text-white hover:text-gray-200 text-2xl font-bold">
                &times;
            </button>
        </div>
        
        <form id="addUnitForm" class="p-6 space-y-4">
            <div id="modalMessage" class="hidden"></div>
            
            <div>
                <label class="block text-gray-700 font-semibold mb-2">
                    Mã đơn vị <span class="text-red-500">*</span>
                </label>
                <input type="text" id="newMadv" required 
                       placeholder="Ví dụ: XDT, PCC, etc."
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Mã viết tắt, không dấu, chữ hoa</p>
            </div>
            
            <div>
                <label class="block text-gray-700 font-semibold mb-2">
                    Tên đơn vị <span class="text-red-500">*</span>
                </label>
                <input type="text" id="newTendv" required 
                       placeholder="Ví dụ: Xưởng Điện Tử"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
            
            <div class="flex gap-2 pt-4 border-t">
                <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-semibold">
                    <i class="fas fa-save mr-2"></i>Lưu
                </button>
                <button type="button" onclick="closeAddUnitModal()" 
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded font-semibold">
                    <i class="fas fa-times mr-2"></i>Hủy
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions for Add Unit
function openAddUnitModal() {
    document.getElementById('addUnitModal').classList.remove('hidden');
    document.getElementById('newMadv').focus();
}

function closeAddUnitModal() {
    document.getElementById('addUnitModal').classList.add('hidden');
    document.getElementById('addUnitForm').reset();
    document.getElementById('modalMessage').classList.add('hidden');
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddUnitModal();
    }
});

// Close modal on background click
document.getElementById('addUnitModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddUnitModal();
    }
});

// Handle Add Unit Form Submit
document.getElementById('addUnitForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const madv = document.getElementById('newMadv').value.trim().toUpperCase();
    const tendv = document.getElementById('newTendv').value.trim();
    
    if (!madv || !tendv) {
        showModalMessage('Vui lòng điền đầy đủ thông tin', 'error');
        return;
    }
    
    // Create FormData
    const formData = new FormData();
    formData.append('madv', madv);
    formData.append('tendv', tendv);
    
    // Submit to API
    fetch('/iso2/api/donvi.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showModalMessage(data.message, 'success');
            
            // Add new option to select
            const madvSelect = document.getElementById('madvSelect');
            const newOption = document.createElement('option');
            newOption.value = data.data.madv;
            newOption.textContent = data.data.tendv;
            newOption.selected = true;
            madvSelect.appendChild(newOption);
            
            // Trigger change event to reload devices
            madvSelect.dispatchEvent(new Event('change'));
            
            // Close modal after 1 second
            setTimeout(() => {
                closeAddUnitModal();
                showNotification('Đơn vị mới đã được thêm và chọn', 'success');
            }, 1000);
        } else {
            showModalMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showModalMessage('Có lỗi xảy ra khi thêm đơn vị', 'error');
    });
});

function showModalMessage(message, type) {
    const messageDiv = document.getElementById('modalMessage');
    const colors = {
        success: 'bg-green-100 border-green-400 text-green-700',
        error: 'bg-red-100 border-red-400 text-red-700'
    };
    
    messageDiv.className = `border px-4 py-3 rounded mb-4 ${colors[type]}`;
    messageDiv.textContent = message;
    messageDiv.classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    const madvSelect = document.querySelector('select[name="madv"]');
    
    if (!madvSelect) return;
    
    // Cascade handler: when unit changes, load devices for all 5 slots
    madvSelect.addEventListener('change', function() {
        const madv = this.value;
        
        if (!madv) {
            // Clear all device dropdowns if no unit selected
            for (let i = 1; i <= 5; i++) {
                resetDeviceInputs(i);
            }
            return;
        }
        
        // Load devices for this unit
        fetch(`/iso2/api/thietbi.php?madv=${encodeURIComponent(madv)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    // Store devices data globally for use in device inputs
                    window.availableDevices = data.data;
                    
                    // For simple implementation: keep text inputs but show info
                    console.log(`Loaded ${data.data.length} devices for unit ${madv}`);
                    
                    // Optional: Show available devices info
                    const deviceCount = data.data.length;
                    if (deviceCount > 0) {
                        showNotification(`Đã tải ${deviceCount} thiết bị cho đơn vị này`, 'success');
                    } else {
                        showNotification('Không có thiết bị nào cho đơn vị này', 'warning');
                    }
                }
            })
            .catch(error => {
                console.error('Error loading devices:', error);
                showNotification('Lỗi khi tải danh sách thiết bị', 'error');
            });
    });
    
    // Helper: Reset device inputs for a slot
    function resetDeviceInputs(index) {
        const mavtInput = document.querySelector(`input[name="devices[${index}][mavt]"]`);
        const somayInput = document.querySelector(`input[name="devices[${index}][somay]"]`);
        const modelInput = document.querySelector(`input[name="devices[${index}][model]"]`);
        
        if (mavtInput && index !== 1) mavtInput.value = '';
        if (somayInput && index !== 1) somayInput.value = '';
        if (modelInput && index !== 1) modelInput.value = '';
    }
    
    // Helper: Show notification
    function showNotification(message, type = 'info') {
        const colors = {
            success: 'bg-green-100 border-green-400 text-green-700',
            warning: 'bg-yellow-100 border-yellow-400 text-yellow-700',
            error: 'bg-red-100 border-red-400 text-red-700',
            info: 'bg-blue-100 border-blue-400 text-blue-700'
        };
        
        const notification = document.createElement('div');
        notification.className = `${colors[type]} border px-4 py-3 rounded mb-4 fixed top-4 right-4 z-50 shadow-lg`;
        notification.innerHTML = `
            <span class="block sm:inline">${message}</span>
            <button onclick="this.parentElement.remove()" class="ml-4 font-bold">&times;</button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => notification.remove(), 5000);
    }
    
    // Auto-fill functionality: When user types mavt, suggest from available devices
    for (let i = 1; i <= 5; i++) {
        const mavtInput = document.querySelector(`input[name="devices[${i}][mavt]"]`);
        const somayInput = document.querySelector(`input[name="devices[${i}][somay]"]`);
        const modelInput = document.querySelector(`input[name="devices[${i}][model]"]`);
        
        if (!mavtInput) continue;
        
        // Add autocomplete suggestions
        mavtInput.addEventListener('input', function() {
            const value = this.value.toLowerCase();
            
            if (!window.availableDevices || value.length < 2) return;
            
            // Find matching devices
            const matches = window.availableDevices.filter(d => 
                d.mavt.toLowerCase().includes(value) || 
                d.tenvt.toLowerCase().includes(value)
            );
            
            if (matches.length > 0) {
                // Show first match as placeholder or suggestion
                const firstMatch = matches[0];
                this.setAttribute('title', `${firstMatch.mavt} - ${firstMatch.tenvt}`);
            }
        });
        
        // When somay field is focused, load available serial numbers for the mavt
        somayInput.addEventListener('focus', function() {
            const madv = madvSelect.value;
            const mavt = mavtInput.value;
            
            if (!madv || !mavt) {
                showNotification('Vui lòng chọn đơn vị và nhập mã vật tư trước', 'warning');
                return;
            }
            
            // Load serial numbers
            fetch(`/iso2/api/somay.php?madv=${encodeURIComponent(madv)}&mavt=${encodeURIComponent(mavt)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.length > 0) {
                        // Store in input data attribute
                        somayInput.setAttribute('data-available', JSON.stringify(data.data));
                        
                        // Create datalist for autocomplete
                        let datalistId = `somay-list-${i}`;
                        let datalist = document.getElementById(datalistId);
                        
                        if (!datalist) {
                            datalist = document.createElement('datalist');
                            datalist.id = datalistId;
                            somayInput.setAttribute('list', datalistId);
                            somayInput.parentNode.appendChild(datalist);
                        }
                        
                        datalist.innerHTML = data.data.map(d => 
                            `<option value="${d.somay}">${d.somay} - ${d.model || ''}</option>`
                        ).join('');
                        
                        console.log(`Loaded ${data.data.length} serial numbers for ${mavt}`);
                    }
                })
                .catch(error => console.error('Error loading serial numbers:', error));
        });
        
        // When somay changes, auto-fill model if available
        somayInput.addEventListener('change', function() {
            const availableData = this.getAttribute('data-available');
            if (!availableData) return;
            
            try {
                const devices = JSON.parse(availableData);
                const selected = devices.find(d => d.somay === this.value);
                
                if (selected && selected.model && !modelInput.value) {
                    modelInput.value = selected.model;
                }
            } catch (e) {
                console.error('Error parsing available data:', e);
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
