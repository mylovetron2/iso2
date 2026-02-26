<?php
/**
 * Component: Danh sách công việc liên quan đến hồ sơ SC/BĐ
 * Usage: include trong hososcbd repair_details.php
 * Requires: $stt (hososcbd_iso.stt)
 */

// DEBUG: Echo để xem widget có được load không
echo "<!-- DEBUG: Widget loaded at " . date('Y-m-d H:i:s') . " -->\n";

if (!isset($stt)) {
    echo '<div class="bg-red-100 border border-red-400 p-4 rounded">';
    echo '<strong>❌ Lỗi: Thiếu tham số $stt</strong><br>';
    echo 'Current variables: ' . implode(', ', array_keys(get_defined_vars()));
    echo '</div>';
    return;
}

echo "<!-- DEBUG: STT = $stt -->\n";

// Load dependencies if not already loaded
if (!function_exists('getDBConnection')) {
    require_once __DIR__ . '/../../config/database.php';
}
if (!function_exists('hasPermission')) {
    require_once __DIR__ . '/../../includes/permissions.php';
}

echo "<!-- DEBUG: Requires loaded -->\n";

// Check view permission
// TODO: Uncomment sau khi chạy execute_add_congviec_permissions.php
/*
if (!hasPermission('congviec_suachua.view')) {
    echo '<div class="bg-yellow-100 border border-yellow-300 p-4 rounded text-sm">';
    echo '<i class="fas fa-lock mr-2"></i>Bạn không có quyền xem công việc sửa chữa';
    echo '</div>';
    return;
}
*/

$db = getDBConnection();
echo "<!-- DEBUG: DB connected -->\n";

// Lấy danh sách công việc liên quan
try {
    $stmtCongViec = $db->prepare("
        SELECT 
            cv.*,
            cd.ma_capdo,
            cd.ten_capdo,
            cd.mau_sac
        FROM congviec_suachua_iso cv
        LEFT JOIN capdo_baocuong_iso cd ON cv.capdo_stt = cd.stt
        WHERE cv.hososcbd_stt = :hososcbd_stt
        ORDER BY cv.ngay_lam DESC, cv.created_at DESC
    ");
    $stmtCongViec->execute([':hososcbd_stt' => $stt]);
    $congviecs = $stmtCongViec->fetchAll(PDO::FETCH_ASSOC);
    echo "<!-- DEBUG: Found " . count($congviecs) . " congviec records -->\n";
} catch (Exception $e) {
    echo '<div class="bg-red-100 border border-red-400 p-4 rounded">';
    echo '<strong>❌ Lỗi SQL:</strong> ' . htmlspecialchars($e->getMessage());
    echo '</div>';
    $congviecs = [];
}

// Tính tổng số giờ
try {
    $stmtTongGio = $db->prepare("
        SELECT 
            COUNT(*) AS so_congviec,
            COALESCE(SUM(so_gio_lam), 0) AS tong_gio,
            COALESCE(AVG(so_gio_lam), 0) AS trung_binh_gio
        FROM congviec_suachua_iso
        WHERE hososcbd_stt = :hososcbd_stt
    ");
    $stmtTongGio->execute([':hososcbd_stt' => $stt]);
    $thongke = $stmtTongGio->fetch(PDO::FETCH_ASSOC);
    echo "<!-- DEBUG: Thongke loaded -->\n";
} catch (Exception $e) {
    echo '<div class="bg-red-100 border border-red-400 p-4 rounded">';
    echo '<strong>❌ Lỗi Thống kê:</strong> ' . htmlspecialchars($e->getMessage());
    echo '</div>';
    $thongke = ['so_congviec' => 0, 'tong_gio' => 0, 'trung_binh_gio' => 0];
}

// Lấy danh sách nhân viên để tạo công việc mới
try {
    $stmtNV = $db->query("SELECT stt, hoten FROM resume ORDER BY hoten ASC LIMIT 100");
    $nhanviens = $stmtNV->fetchAll(PDO::FETCH_ASSOC);
    echo "<!-- DEBUG: Loaded " . count($nhanviens) . " nhanvien -->\n";
} catch (Exception $e) {
    echo '<div class="bg-red-100 border border-red-400 p-4 rounded">';
    echo '<strong>❌ Lỗi load nhân viên:</strong> ' . htmlspecialchars($e->getMessage());
    echo '</div>';
    $nhanviens = [];
}

// Lấy danh sách cấp độ
try {
    $stmtCD = $db->query("SELECT * FROM capdo_baocuong_iso WHERE trang_thai = 1 ORDER BY thu_tu ASC");
    $capdos = $stmtCD->fetchAll(PDO::FETCH_ASSOC);
    echo "<!-- DEBUG: Loaded " . count($capdos) . " capdo -->\n";
} catch (Exception $e) {
    echo '<div class="bg-red-100 border border-red-400 p-4 rounded">';
    echo '<strong>❌ Lỗi load cấp độ:</strong> ' . htmlspecialchars($e->getMessage());
    echo '</div>';
    $capdos = [];
}

echo "<!-- DEBUG: Starting HTML output -->\n";
?>

<div class="border-l-4 border-purple-500 pl-4 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-purple-700 flex items-center">
            <i class="fas fa-tasks mr-2"></i>Công việc sửa chữa liên quan
        </h2>
        <?php // TODO: Uncomment sau khi chạy migration
        if (true || hasPermission('congviec_suachua.create')): ?>
        <button type="button" onclick="openAddCongViecModal()" 
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded text-sm flex items-center">
            <i class="fas fa-plus-circle mr-2"></i>Thêm công việc
        </button>
        <?php endif; ?>
    </div>

    <!-- Thống kê tổng quan -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <?php /* Ẩn Số công việc
        <div class="bg-blue-50 border border-blue-200 rounded p-3">
            <div class="text-sm text-blue-600">Số công việc</div>
            <div class="text-2xl font-bold text-blue-700"><?= $thongke['so_congviec'] ?></div>
        </div>
        */ ?>
        <div class="bg-green-50 border border-green-200 rounded p-3">
            <div class="text-sm text-green-600">Tổng số giờ</div>
            <div class="text-2xl font-bold text-green-700"><?= number_format($thongke['tong_gio'], 2) ?>h</div>
        </div>
        <div class="bg-orange-50 border border-orange-200 rounded p-3">
            <div class="text-sm text-orange-600">Trung bình / công việc</div>
            <div class="text-2xl font-bold text-orange-700"><?= number_format($thongke['trung_binh_gio'], 2) ?>h</div>
        </div>
    </div>

    <!-- Danh sách công việc -->
    <?php if (empty($congviecs)): ?>
        <div class="bg-gray-50 border border-gray-200 rounded p-6 text-center text-gray-500">
            <i class="fas fa-inbox fa-3x mb-3"></i>
            <p>Chưa có công việc nào cho hồ sơ này</p>
            <p class="text-sm mt-2">Click nút "Thêm công việc" ở trên để bắt đầu ghi nhận công việc sửa chữa</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-300 rounded text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 border-b text-left">Ngày làm</th>
                        <th class="px-3 py-2 border-b text-left">Nhân viên</th>
                        <th class="px-3 py-2 border-b text-left">Cấp độ</th>
                        <th class="px-3 py-2 border-b text-left">Nội dung</th>
                        <th class="px-3 py-2 border-b text-center">Số giờ</th>
                        <?php /* Ẩn cột KPI chuẩn
                        <th class="px-3 py-2 border-b text-center">KPI chuẩn</th>
                        */ ?>
                        <?php /* Ẩn cột Đánh giá
                        <th class="px-3 py-2 border-b text-center">Đánh giá</th>
                        */ ?>
                        <?php /* Ẩn cột Trạng thái
                        <th class="px-3 py-2 border-b text-center">Trạng thái</th>
                        */ ?>
                        <th class="px-3 py-2 border-b text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($congviecs as $cv): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 border-b">
                                <?= date('d/m/Y', strtotime($cv['ngay_lam'])) ?>
                            </td>
                            <td class="px-3 py-2 border-b">
                                <strong><?= htmlspecialchars($cv['nhanvien_ten']) ?></strong>
                            </td>
                            <td class="px-3 py-2 border-b">
                                <span class="inline-block px-2 py-1 rounded text-xs text-white" 
                                      style="background-color: <?= htmlspecialchars($cv['mau_sac'] ?? '#666') ?>">
                                    <?= htmlspecialchars($cv['ten_capdo']) ?>
                                </span>
                            </td>
                            <td class="px-3 py-2 border-b">
                                <div class="whitespace-normal">
                                    <?= htmlspecialchars($cv['noi_dung']) ?>
                                </div>
                            </td>
                            <td class="px-3 py-2 border-b text-center">
                                <strong class="text-blue-600"><?= number_format($cv['so_gio_lam'], 2) ?>h</strong>
                            </td>
                            <?php /* Ẩn cột KPI chuẩn
                            <td class="px-3 py-2 border-b text-center text-gray-600">
                                <?= number_format($cv['kpi_gio_chuan'], 2) ?>h
                            </td>
                            */ ?>
                            <?php /* Ẩn cột Đánh giá
                            <td class="px-3 py-2 border-b text-center">
                                <?php if ($cv['so_gio_lam'] <= $cv['kpi_gio_chuan']): ?>
                                    <i class="fas fa-check-circle text-green-500 text-lg" title="Đạt KPI"></i>
                                <?php elseif ($cv['so_gio_lam'] <= $cv['kpi_gio_chuan'] * 1.2): ?>
                                    <i class="fas fa-exclamation-circle text-orange-500 text-lg" title="Gần đạt KPI"></i>
                                <?php else: ?>
                                    <i class="fas fa-times-circle text-red-500 text-lg" title="Chưa đạt KPI"></i>
                                <?php endif; ?>
                            </td>
                            */ ?>
                            <?php /* Ẩn cột Trạng thái
                            <td class="px-3 py-2 border-b text-center">
                                <?php
                                $badgeClass = 'gray';
                                if ($cv['trang_thai'] === 'Hoàn thành') $badgeClass = 'green';
                                elseif ($cv['trang_thai'] === 'Đang thực hiện') $badgeClass = 'blue';
                                elseif ($cv['trang_thai'] === 'Tạm dừng') $badgeClass = 'yellow';
                                ?>
                                <span class="inline-block bg-<?= $badgeClass ?>-100 text-<?= $badgeClass ?>-800 px-2 py-1 rounded text-xs">
                                    <?= htmlspecialchars($cv['trang_thai']) ?>
                                </span>
                            </td>
                            */ ?>
                            <td class="px-3 py-2 border-b text-center">
                                <?php // TODO: Uncomment sau khi chạy migration
                                if (true || hasPermission('congviec_suachua.view')): ?>
                                <button type="button" onclick="viewCongViecDetail(<?= $cv['stt'] ?>)" 
                                        class="text-blue-600 hover:text-blue-800 mr-2" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php endif; ?>
                                <?php if (true || hasPermission('congviec_suachua.edit')): ?>
                                <button type="button" onclick="openEditCongViecModal(<?= $cv['stt'] ?>)" 
                                        class="text-green-600 hover:text-green-800 mr-2" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>
                                <?php if (true || hasPermission('congviec_suachua.delete')): ?>
                                <button type="button" onclick="deleteCongViec(<?= $cv['stt'] ?>)" 
                                        class="text-red-600 hover:text-red-800" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-50 font-bold">
                    <tr>
                        <td colspan="4" class="px-3 py-2 border-t text-right">TỔNG CỘNG:</td>
                        <td class="px-3 py-2 border-t text-center text-blue-700">
                            <?= number_format($thongke['tong_gio'], 2) ?>h
                        </td>
                        <td colspan="1" class="px-3 py-2 border-t"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal thêm công việc -->
<div id="addCongViecModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="bg-purple-600 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
            <h3 class="text-xl font-bold">
                <i class="fas fa-plus-circle mr-2"></i>Thêm công việc cho hồ sơ #<?= $stt ?>
            </h3>
            <button type="button" onclick="closeAddCongViecModal()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        
        <form id="formAddCongViec" class="p-6 space-y-4">
            <input type="hidden" name="hososcbd_stt" value="<?= $stt ?>">
            <input type="hidden" name="mavt" value="<?= htmlspecialchars($item['mavt'] ?? '') ?>">
            <input type="hidden" name="somay" value="<?= htmlspecialchars($item['somay'] ?? '') ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        Nhân viên <span class="text-red-500">*</span>
                    </label>
                    <select name="nhanvien_stt" required class="w-full px-3 py-2 border rounded focus:ring focus:border-purple-500">
                        <option value="">-- Chọn nhân viên --</option>
                        <?php foreach ($nhanviens as $nv): ?>
                            <option value="<?= $nv['stt'] ?>"><?= htmlspecialchars($nv['hoten']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        Ngày làm <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="ngay_lam" value="<?= date('Y-m-d') ?>" required
                           class="w-full px-3 py-2 border rounded focus:ring focus:border-purple-500">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        Cấp độ bảo dưỡng <span class="text-red-500">*</span>
                    </label>
                    <select name="capdo_stt" required class="w-full px-3 py-2 border rounded focus:ring focus:border-purple-500" 
                            onchange="updateKpiDisplay(this)">
                        <option value="">-- Chọn cấp độ --</option>
                        <?php foreach ($capdos as $cd): ?>
                            <option value="<?= $cd['stt'] ?>" 
                                    data-kpi="<?= $cd['kpi_gio_chuan'] ?>"
                                    data-ten="<?= htmlspecialchars($cd['ten_capdo']) ?>">
                                <?= htmlspecialchars($cd['ten_capdo']) ?> (KPI: <?= $cd['kpi_gio_chuan'] ?>h)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="kpiDisplay" class="text-sm text-gray-600 mt-1"></div>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        Số giờ làm <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="so_gio_lam" step="0.5" min="0.5" max="8" required
                           class="w-full px-3 py-2 border rounded focus:ring focus:border-purple-500"
                           placeholder="Tối đa 8h">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Giờ bắt đầu</label>
                    <input type="time" name="gio_bat_dau" 
                           class="w-full px-3 py-2 border rounded focus:ring focus:border-purple-500">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Giờ kết thúc</label>
                    <input type="time" name="gio_ket_thuc"
                           class="w-full px-3 py-2 border rounded focus:ring focus:border-purple-500">
                </div>
            </div>
            
            <div>
                <label class="block text-gray-700 font-semibold mb-2">
                    Nội dung công việc <span class="text-red-500">*</span>
                </label>
                <textarea name="noi_dung" rows="4" required
                          class="w-full px-3 py-2 border rounded focus:ring focus:border-purple-500"
                          placeholder="Mô tả chi tiết công việc sửa chữa/bảo dưỡng..."></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Trạng thái</label>
                    <select name="trang_thai" class="w-full px-3 py-2 border rounded focus:ring focus:border-purple-500">
                        <option value="Đang thực hiện">Đang thực hiện</option>
                        <option value="Hoàn thành">Hoàn thành</option>
                        <option value="Tạm dừng">Tạm dừng</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Ghi chú</label>
                    <input type="text" name="ghi_chu" 
                           class="w-full px-3 py-2 border rounded focus:ring focus:border-purple-500"
                           placeholder="Ghi chú thêm...">
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button type="button" onclick="closeAddCongViecModal()" 
                        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                    Hủy
                </button>
                <button type="submit" 
                        class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded">
                    <i class="fas fa-save mr-2"></i>Lưu công việc
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal sửa công việc -->
<div id="editCongViecModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="bg-green-600 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
            <h3 class="text-xl font-bold">
                <i class="fas fa-edit mr-2"></i>Sửa công việc #<span id="editCvStt"></span>
            </h3>
            <button type="button" onclick="closeEditCongViecModal()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        
        <form id="formEditCongViec" class="p-6 space-y-4">
            <input type="hidden" name="stt" id="edit_stt">
            <input type="hidden" name="hososcbd_stt" id="edit_hososcbd_stt" value="<?= $stt ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        Nhân viên <span class="text-red-500">*</span>
                    </label>
                    <select name="nhanvien_stt" id="edit_nhanvien_stt" required class="w-full px-3 py-2 border rounded focus:ring focus:border-green-500">
                        <option value="">-- Chọn nhân viên --</option>
                        <?php foreach ($nhanviens as $nv): ?>
                            <option value="<?= $nv['stt'] ?>"><?= htmlspecialchars($nv['hoten']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        Ngày làm <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="ngay_lam" id="edit_ngay_lam" required
                           class="w-full px-3 py-2 border rounded focus:ring focus:border-green-500">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        Cấp độ bảo dưỡng <span class="text-red-500">*</span>
                    </label>
                    <select name="capdo_stt" id="edit_capdo_stt" required class="w-full px-3 py-2 border rounded focus:ring focus:border-green-500" 
                            onchange="updateEditKpiDisplay(this)">
                        <option value="">-- Chọn cấp độ --</option>
                        <?php foreach ($capdos as $cd): ?>
                            <option value="<?= $cd['stt'] ?>" 
                                    data-kpi="<?= $cd['kpi_gio_chuan'] ?>"
                                    data-ten="<?= htmlspecialchars($cd['ten_capdo']) ?>">
                                <?= htmlspecialchars($cd['ten_capdo']) ?> (KPI: <?= $cd['kpi_gio_chuan'] ?>h)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="editKpiDisplay" class="text-sm text-gray-600 mt-1"></div>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        Số giờ làm <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="so_gio_lam" id="edit_so_gio_lam" step="0.5" min="0.5" max="8" required
                           class="w-full px-3 py-2 border rounded focus:ring focus:border-green-500"
                           placeholder="Tối đa 8h">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Giờ bắt đầu</label>
                    <input type="time" name="gio_bat_dau" id="edit_gio_bat_dau"
                           class="w-full px-3 py-2 border rounded focus:ring focus:border-green-500">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Giờ kết thúc</label>
                    <input type="time" name="gio_ket_thuc" id="edit_gio_ket_thuc"
                           class="w-full px-3 py-2 border rounded focus:ring focus:border-green-500">
                </div>
            </div>
            
            <div>
                <label class="block text-gray-700 font-semibold mb-2">
                    Nội dung công việc <span class="text-red-500">*</span>
                </label>
                <textarea name="noi_dung" id="edit_noi_dung" rows="4" required
                          class="w-full px-3 py-2 border rounded focus:ring focus:border-green-500"
                          placeholder="Mô tả chi tiết công việc sửa chữa/bảo dưỡng..."></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Trạng thái</label>
                    <select name="trang_thai" id="edit_trang_thai" class="w-full px-3 py-2 border rounded focus:ring focus:border-green-500">
                        <option value="Đang thực hiện">Đang thực hiện</option>
                        <option value="Hoàn thành">Hoàn thành</option>
                        <option value="Tạm dừng">Tạm dừng</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Ghi chú</label>
                    <input type="text" name="ghi_chu" id="edit_ghi_chu"
                           class="w-full px-3 py-2 border rounded focus:ring focus:border-green-500"
                           placeholder="Ghi chú thêm...">
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button type="button" onclick="closeEditCongViecModal()" 
                        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                    Hủy
                </button>
                <button type="submit" 
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                    <i class="fas fa-save mr-2"></i>Cập nhật
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.bg-gray-100 { background-color: #f3f4f6; }
.bg-blue-100 { background-color: #dbeafe; }
.bg-green-100 { background-color: #d1fae5; }
.bg-yellow-100 { background-color: #fef3c7; }
.text-blue-800 { color: #1e40af; }
.text-green-800 { color: #065f46; }
.text-yellow-800 { color: #92400e; }
.text-gray-800 { color: #1f2937; }
</style>

<script>
function openAddCongViecModal() {
    document.getElementById('addCongViecModal').classList.remove('hidden');
}

function closeAddCongViecModal() {
    document.getElementById('addCongViecModal').classList.add('hidden');
    document.getElementById('formAddCongViec').reset();
}

function updateKpiDisplay(select) {
    const option = select.options[select.selectedIndex];
    const kpi = option.dataset.kpi;
    const ten = option.dataset.ten;
    const display = document.getElementById('kpiDisplay');
    
    if (kpi && ten) {
        display.innerHTML = `<i class="fas fa-info-circle"></i> ${ten}: KPI chuẩn là <strong>${kpi} giờ</strong>`;
    } else {
        display.innerHTML = '';
    }
}

document.getElementById('formAddCongViec').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'save');
    
    try {
        const response = await fetch('/iso2/congviec_suachua.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        // Check response status
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Get response text first
        const responseText = await response.text();
        
        // Try to parse as JSON
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('Response text:', responseText);
            throw new Error('Server trả về dữ liệu không phải JSON. Xem console để biết chi tiết.');
        }
        
        if (result.success) {
            alert('✓ ' + result.message);
            closeAddCongViecModal();
            location.reload();
        } else {
            alert('✗ ' + result.message + (result.debug ? '\n\nDebug:\n' + JSON.stringify(result.debug, null, 2) : ''));
        }
    } catch (error) {
        console.error('Full error:', error);
        alert('Lỗi kết nối: ' + error.message);
    }
});

async function deleteCongViec(stt) {
    if (!confirm('Bạn có chắc chắn muốn xóa công việc này?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('stt', stt);
    
    try {
        const response = await fetch('/iso2/congviec_suachua.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✓ ' + result.message);
            location.reload();
        } else {
            alert('✗ ' + result.message);
        }
    } catch (error) {
        alert('Lỗi kết nối: ' + error.message);
    }
}

function viewCongViecDetail(stt) {
    // Mở trong tab mới để xem chi tiết
    window.open('/iso2/congviec_suachua.php?stt=' + stt, '_blank');
}

function updateEditKpiDisplay(select) {
    const option = select.options[select.selectedIndex];
    const kpi = option.dataset.kpi;
    const ten = option.dataset.ten;
    const display = document.getElementById('editKpiDisplay');
    
    if (kpi && ten) {
        display.innerHTML = `<i class="fas fa-info-circle"></i> ${ten}: KPI chuẩn là <strong>${kpi} giờ</strong>`;
    } else {
        display.innerHTML = '';
    }
}

async function openEditCongViecModal(stt) {
    try {
        // Fetch data công việc
        const response = await fetch(`/iso2/congviec_suachua.php?action=get&stt=${stt}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const responseText = await response.text();
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('Response text:', responseText);
            throw new Error('Server trả về dữ liệu không phải JSON');
        }
        
        if (!result.success) {
            throw new Error(result.message || 'Không lấy được dữ liệu');
        }
        
        const cv = result.data;
        
        // Populate form
        document.getElementById('editCvStt').textContent = cv.stt;
        document.getElementById('edit_stt').value = cv.stt;
        document.getElementById('edit_hososcbd_stt').value = cv.hososcbd_stt || '<?= $stt ?>';
        document.getElementById('edit_nhanvien_stt').value = cv.nhanvien_stt;
        document.getElementById('edit_ngay_lam').value = cv.ngay_lam;
        document.getElementById('edit_capdo_stt').value = cv.capdo_stt;
        document.getElementById('edit_so_gio_lam').value = cv.so_gio_lam;
        document.getElementById('edit_gio_bat_dau').value = cv.gio_bat_dau || '';
        document.getElementById('edit_gio_ket_thuc').value = cv.gio_ket_thuc || '';
        document.getElementById('edit_noi_dung').value = cv.noi_dung;
        document.getElementById('edit_trang_thai').value = cv.trang_thai;
        document.getElementById('edit_ghi_chu').value = cv.ghi_chu || '';
        
        // Update KPI display
        const capdoSelect = document.getElementById('edit_capdo_stt');
        updateEditKpiDisplay(capdoSelect);
        
        // Show modal
        document.getElementById('editCongViecModal').classList.remove('hidden');
    } catch (error) {
        console.error('Error:', error);
        alert('Lỗi khi tải dữ liệu: ' + error.message);
    }
}

function closeEditCongViecModal() {
    document.getElementById('editCongViecModal').classList.add('hidden');
    document.getElementById('formEditCongViec').reset();
}

document.getElementById('formEditCongViec').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'update');
    
    try {
        const response = await fetch('/iso2/congviec_suachua.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const responseText = await response.text();
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('Response text:', responseText);
            throw new Error('Server trả về dữ liệu không phải JSON. Xem console để biết chi tiết.');
        }
        
        if (result.success) {
            alert('✓ ' + result.message);
            closeEditCongViecModal();
            location.reload();
        } else {
            alert('✗ ' + result.message + (result.debug ? '\n\nDebug:\n' + JSON.stringify(result.debug, null, 2) : ''));
        }
    } catch (error) {
        console.error('Full error:', error);
        alert('Lỗi kết nối: ' + error.message);
    }
});
</script>
