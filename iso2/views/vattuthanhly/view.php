<?php
$title = 'Chi tiết vật tư #' . ($vattu['stt'] ?? '');
require_once __DIR__ . '/../layouts/simple_header.php';
?>

<style>
.card {
    transition: transform 0.2s;
    border-radius: 0.5rem;
    overflow: hidden;
}
.card:hover {
    transform: translateY(-2px);
}
.info-table th {
    white-space: nowrap;
    background-color: #f3f4f6;
    font-weight: 600;
    padding: 0.75rem 1rem;
}
.info-table td {
    padding: 0.75rem 1rem;
}
</style>

<div class="container mx-auto px-4 py-3 max-w-7xl">
    <!-- Breadcrumb -->
    <nav class="mb-4">
        <ol class="flex items-center space-x-2 text-sm text-gray-600">
            <li><a href="/iso2/index.php" class="hover:text-blue-600">Trang chủ</a></li>
            <li><i class="fas fa-chevron-right text-xs"></i></li>
            <li><a href="/iso2/vattuthanhly.php" class="hover:text-blue-600">Vật tư thanh lý</a></li>
            <li><i class="fas fa-chevron-right text-xs"></i></li>
            <li class="text-gray-900 font-semibold">Chi tiết vật tư #<?php echo $vattu['stt']; ?></li>
        </ol>
    </nav>

    <!-- Header with Actions -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-info-circle text-blue-600 mr-3"></i>
            Chi tiết vật tư
        </h2>
        <div class="flex space-x-2">
            <a href="/iso2/vattuthanhly.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại
            </a>
            <?php if (hasPermission('vattu.edit')): ?>
            <a href="/iso2/vattuthanhly.php?action=edit&id=<?php echo $vattu['stt']; ?>" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition">
                <i class="fas fa-edit mr-2"></i> Sửa
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Thông tin cơ bản - Takes 2 columns -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Card 1: Thông tin cơ bản -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="bg-blue-600 text-white px-4 py-3">
                    <h5 class="text-lg font-semibold flex items-center">
                        <i class="fas fa-clipboard-list mr-2"></i> Thông tin cơ bản
                    </h5>
                </div>
                <div class="p-4">
                    <table class="info-table w-full border border-gray-200">
                        <tbody>
                            <tr class="border-b">
                                <th class="w-1/3 text-left">STT</th>
                                <td><strong class="text-blue-600">#<?php echo $vattu['stt']; ?></strong></td>
                            </tr>
                            <tr class="border-b">
                                <th class="text-left">Mã vật tư</th>
                                <td>
                                    <code class="<?php echo htmlspecialchars($vattu['phanloai_mau_sac'] ?? 'bg-blue-100 text-blue-800'); ?> rounded font-semibold"
                                          style="font-size: 16px; padding: 8px 16px; display: inline-block; min-width: 160px; text-align: center;">
                                        <?php echo htmlspecialchars($vattu['mavattu']); ?>
                                    </code>
                                </td>
                            </tr>
                            <?php if (!empty($vattu['so_serial'])): ?>
                            <tr class="border-b">
                                <th class="text-left">Số Serial</th>
                                <td>
                                    <span class="bg-gray-200 text-gray-800 px-3 py-1 rounded font-mono text-sm">
                                        <?php echo htmlspecialchars($vattu['so_serial']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <tr class="border-b">
                                <th class="text-left">Phân loại</th>
                                <td>
                                    <?php if (!empty($vattu['ten_phanloai'])): ?>
                                        <span class="<?php echo htmlspecialchars($vattu['phanloai_mau_sac'] ?? 'bg-gray-100 text-gray-800'); ?> px-3 py-1 rounded text-sm font-semibold">
                                            <?php echo htmlspecialchars($vattu['ten_phanloai']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-500">Chưa phân loại</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-left">Vị trí sắp xếp</th>
                                <td><?php echo $vattu['vi_tri_sap_xep'] ?? '-'; ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Tên vật tư 3 ngôn ngữ -->
                    <h6 class="mt-6 mb-3 text-lg font-semibold text-gray-700 flex items-center">
                        <i class="fas fa-language mr-2"></i> Tên vật tư
                    </h6>
                    <table class="info-table w-full border border-gray-200">
                        <tbody>
                            <tr class="border-b">
                                <th class="w-1/3 text-left">
                                    <i class="fas fa-flag mr-1"></i> Tiếng Anh
                                </th>
                                <td><?php echo htmlspecialchars($vattu['ten_tienganh'] ?? '-'); ?></td>
                            </tr>
                            <tr class="border-b">
                                <th class="text-left">
                                    <i class="fas fa-flag mr-1"></i> Tiếng Nga
                                </th>
                                <td class="text-blue-600"><?php echo htmlspecialchars($vattu['ten_tiengnga'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th class="text-left">
                                    <i class="fas fa-flag mr-1"></i> Tiếng Việt
                                </th>
                                <td class="text-green-600"><?php echo htmlspecialchars($vattu['ten_tiengviet'] ?? '-'); ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Đặc tính kỹ thuật -->
                    <h6 class="mt-6 mb-3 text-lg font-semibold text-gray-700 flex items-center">
                        <i class="fas fa-cogs mr-2"></i> Đặc tính kỹ thuật
                    </h6>
                    <table class="info-table w-full border border-gray-200">
                        <tbody>
                            <tr class="border-b">
                                <th class="w-1/3 text-left">Tiếng Anh</th>
                                <td class="whitespace-pre-wrap"><?php echo htmlspecialchars($vattu['dactinhkt_tiengnga'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th class="text-left">Tiếng Việt</th>
                                <td class="whitespace-pre-wrap"><?php echo htmlspecialchars($vattu['dactinhkt_tiengviet'] ?? '-'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Chi tiết sử dụng -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="bg-green-600 text-white px-4 py-3 flex justify-between items-center">
                    <h5 class="text-lg font-semibold flex items-center">
                        <i class="fas fa-history mr-2"></i> Lịch sử sử dụng
                    </h5>
                    <?php if (hasPermission('vattu.edit')): ?>
                    <button class="px-3 py-1 bg-white text-green-600 rounded hover:bg-gray-100 transition text-sm" onclick="showAddChiTietModal()">
                        <i class="fas fa-plus mr-1"></i> Thêm chi tiết
                    </button>
                    <?php endif; ?>
                </div>
                <div class="p-4">
                    <?php if (empty($chiTietList)): ?>
                        <p class="text-gray-500 text-center py-8">
                            <i class="fas fa-inbox text-4xl mb-2 block"></i>
                            Chưa có lịch sử sử dụng
                        </p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100 border-b-2 border-gray-300">
                                    <tr>
                                        <th class="px-4 py-2 text-left">ID</th>
                                        <th class="px-4 py-2 text-left">Người sử dụng</th>
                                        <th class="px-4 py-2 text-left">Ngày nhận</th>
                                        <th class="px-4 py-2 text-right">Số lượng</th>
                                        <th class="px-4 py-2 text-left">Bộ phận</th>
                                        <th class="px-4 py-2 text-left">Mục đích</th>
                                        <th class="px-4 py-2 text-center">Trạng thái</th>
                                        <th class="px-4 py-2 text-left">Ghi chú</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($chiTietList as $ct): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2"><?php echo $ct['id']; ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($ct['nguoisudung'] ?? '-'); ?></td>
                                        <td class="px-4 py-2"><?php echo $ct['ngaysd_nhan'] ? date('d/m/Y', strtotime($ct['ngaysd_nhan'])) : '-'; ?></td>
                                        <td class="px-4 py-2 text-right font-semibold"><?php echo number_format($ct['soluong'] ?? 0, 0); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($ct['bophan'] ?? '-'); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($ct['mucdich_sudung'] ?? '-'); ?></td>
                                        <td class="px-4 py-2 text-center">
                                            <?php if ($ct['trangthai'] === 'dangdung'): ?>
                                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold">Đang dùng</span>
                                            <?php elseif ($ct['trangthai'] === 'datra'): ?>
                                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">Đã trả</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-semibold"><?php echo htmlspecialchars($ct['trangthai']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($ct['ghichu'] ?? '-'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar: Thông tin tài chính và số lượng -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Số lượng & Giá -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="bg-cyan-600 text-white px-4 py-3">
                    <h5 class="text-lg font-semibold flex items-center">
                        <i class="fas fa-calculator mr-2"></i> Số lượng & Giá trị
                    </h5>
                </div>
                <div class="p-4">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-200">
                            <tr>
                                <th class="py-2 text-left text-gray-700 font-semibold">Đơn vị tính</th>
                                <td class="py-2 text-right">
                                    <strong class="text-gray-900">
                                        <?php echo htmlspecialchars($vattu['dvt_tiengviet'] ?? $vattu['dvt_tiengnga'] ?? $vattu['dvt_tienganh'] ?? '-'); ?>
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <th class="py-2 text-left text-gray-700 font-semibold">SL còn lại</th>
                                <td class="py-2 text-right">
                                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded font-bold text-base">
                                        <?php echo number_format($vattu['soluong_conlai'] ?? 0, 0); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="py-2 text-left text-gray-700 font-semibold">Đơn giá</th>
                                <td class="py-2 text-right text-gray-900">
                                    <?php echo $vattu['dongia'] ? number_format($vattu['dongia'], 0) . ' đ' : '-'; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="py-2 text-left text-gray-700 font-semibold">Tổng giá trị</th>
                                <td class="py-2 text-right">
                                    <strong class="text-blue-600 text-lg">
                                        <?php echo number_format($vattu['tong_tien'] ?? 0, 0); ?> đ
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <th class="py-2 text-left text-gray-700 font-semibold">SL đang dùng</th>
                                <td class="py-2 text-right text-gray-900">
                                    <?php echo number_format($vattu['soluong_dangdung'] ?? 0, 0); ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="py-2 text-left text-gray-700 font-semibold">Số lần thanh lý</th>
                                <td class="py-2 text-right">
                                    <?php 
                                    $badgeClass = ($vattu['so_lan_sudung'] ?? 0) > 0 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                                    ?>
                                    <span class="px-3 py-1 rounded font-semibold <?php echo $badgeClass; ?>">
                                        <?php echo $vattu['so_lan_sudung'] ?? 0; ?> lần
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Thông tin hợp đồng & quản lý -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="bg-yellow-500 text-white px-4 py-3">
                    <h5 class="text-lg font-semibold flex items-center">
                        <i class="fas fa-file-contract mr-2"></i> Hợp đồng & Quản lý
                    </h5>
                </div>
                <div class="p-4">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-200">
                            <tr>
                                <th class="py-2 text-left text-gray-700 font-semibold">Ngày nhận</th>
                                <td class="py-2 text-right text-gray-900"><?php echo $vattu['ngaynhan'] ? date('d/m/Y', strtotime($vattu['ngaynhan'])) : '-'; ?></td>
                            </tr>
                            <tr>
                                <th class="py-2 text-left text-gray-700 font-semibold">Số HĐ</th>
                                <td class="py-2 text-right text-gray-900"><?php echo htmlspecialchars($vattu['sohd'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th class="py-2 text-left text-gray-700 font-semibold">Ngày ký HĐ</th>
                                <td class="py-2 text-right text-gray-900"><?php echo !empty($vattu['ngaykyhd']) ? date('d/m/Y', strtotime($vattu['ngaykyhd'])) : '-'; ?></td>
                            </tr>
                            <tr>
                                <th class="py-2 text-left text-gray-700 font-semibold">Người quản lý</th>
                                <td class="py-2 text-right text-gray-900"><?php echo htmlspecialchars($vattu['nguoiquanly'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th class="py-2 text-left text-gray-700 font-semibold">Vị trí lưu kho</th>
                                <td class="py-2 text-right text-gray-900"><?php echo htmlspecialchars($vattu['vitri_luukho'] ?? '-'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ghi chú -->
            <?php if (!empty($vattu['ghichu'])): ?>
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="bg-gray-600 text-white px-4 py-3">
                    <h5 class="text-lg font-semibold flex items-center">
                        <i class="fas fa-sticky-note mr-2"></i> Ghi chú
                    </h5>
                </div>
                <div class="p-4">
                    <p class="text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($vattu['ghichu']); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Chi Tiet Modal -->
<div id="addChiTietModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="bg-blue-600 text-white px-6 py-4 flex justify-between items-center">
            <h5 class="text-xl font-semibold">Thêm chi tiết sử dụng</h5>
            <button type="button" class="text-white hover:text-gray-200" onclick="closeAddChiTietModal()">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="p-6">
            <form id="addChiTietForm" class="space-y-4">
                <input type="hidden" name="vattu_stt" value="<?php echo $vattu['stt']; ?>">
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Người sử dụng <span class="text-red-500">*</span></label>
                    <input type="text" class="w-full border border-gray-300 rounded px-3 py2 focus:outline-none focus:ring-2 focus:ring-blue-500" name="nguoisudung" required>
                </div>
                
                <div>
                    <label class="form-label">Ngày nhận</label>
                    <input type="date" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" name="ngaysd_nhan" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        Số lượng <span class="text-red-500">*</span>
                        <small class="text-gray-500 font-normal">(Còn lại: <?php echo number_format($vattu['soluong_conlai'] ?? 0, 0); ?>)</small>
                    </label>
                    <input type="number" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           name="soluong" step="0.01" required max="<?php echo $vattu['soluong_conlai'] ?? 0; ?>">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Bộ phận</label>
                    <select class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" name="bophan">
                        <option value="">-- Chọn bộ phận --</option>
                        <?php foreach ($donViList as $dv): ?>
                        <option value="<?php echo htmlspecialchars($dv['madv']); ?>">
                            <?php echo htmlspecialchars($dv['tendv']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Mục đích sử dụng</label>
                    <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" name="mucdich_sudung">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Ghi chú</label>
                    <textarea class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                              name="ghichu" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="bg-gray-100 px-6 py-4 flex justify-end space-x-3">
            <button type="button" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition" onclick="closeAddChiTietModal()">
                Đóng
            </button>
            <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition" onclick="submitAddChiTiet()">
                <i class="fas fa-save mr-2"></i>Lưu
            </button>
        </div>
    </div>
</div>

<script>
function showAddChiTietModal() {
    document.getElementById('addChiTietModal').classList.remove('hidden');
}

function closeAddChiTietModal() {
    document.getElementById('addChiTietModal').classList.add('hidden');
}

function submitAddChiTiet() {
    const form = document.getElementById('addChiTietForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    fetch('/iso2/vattuthanhly.php?action=addChiTiet', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            alert('Thêm chi tiết thành công!');
            location.reload();
        } else {
            alert('Lỗi: ' + (result.error || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Có lỗi xảy ra!');
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/simple_footer.php'; ?>
