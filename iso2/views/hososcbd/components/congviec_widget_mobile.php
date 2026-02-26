<?php
/**
 * Component: Danh sách công việc MOBILE cho hồ sơ SC/BĐ
 * Usage: include trong congviec_mobile_view.php
 * Requires: $stt (hososcbd_iso.stt), $item (hososcbd record)
 */

if (!isset($stt)) {
    echo '<div class="bg-red-100 border border-red-400 p-4 rounded text-sm">';
    echo '<strong>❌ Lỗi: Thiếu tham số $stt</strong>';
    echo '</div>';
    return;
}

// Load dependencies if not already loaded
if (!function_exists('getDBConnection')) {
    require_once __DIR__ . '/../../../config/database.php';
}
if (!function_exists('hasPermission')) {
    require_once __DIR__ . '/../../../includes/permissions.php';
}

$db = getDBConnection();

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
} catch (Exception $e) {
    echo '<div class="bg-red-100 border border-red-400 p-3 rounded text-sm">';
    echo '<strong>❌ Lỗi:</strong> ' . htmlspecialchars($e->getMessage());
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
} catch (Exception $e) {
    $thongke = ['so_congviec' => 0, 'tong_gio' => 0, 'trung_binh_gio' => 0];
}

// Lấy danh sách nhân viên
try {
    $stmtNV = $db->query("SELECT stt, hoten FROM resume ORDER BY hoten ASC LIMIT 100");
    $nhanviens = $stmtNV->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $nhanviens = [];
}

// Lấy danh sách cấp độ
try {
    $stmtCD = $db->query("SELECT * FROM capdo_baocuong_iso WHERE trang_thai = 1 ORDER BY thu_tu ASC");
    $capdos = $stmtCD->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $capdos = [];
}
?>

<!-- Mobile Widget Styles -->
<style>
    .mobile-stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 0.75rem;
        border-radius: 0.5rem;
        color: white;
        text-align: center;
    }
    
    .mobile-stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        margin: 0.25rem 0;
    }
    
    .mobile-stat-label {
        font-size: 0.75rem;
        opacity: 0.9;
    }
    
    .mobile-work-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 0.75rem;
        margin-bottom: 0.75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .mobile-work-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .mobile-work-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
    }
    
    .mobile-work-detail {
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }
    
    .mobile-work-actions {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
        margin-top: 0.75rem;
        padding-top: 0.5rem;
        border-top: 1px solid #f3f4f6;
    }
    
    .mobile-action-btn {
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 500;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .mobile-fab {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 50%;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        cursor: pointer;
        z-index: 50;
        transition: transform 0.2s;
    }
    
    .mobile-fab:active {
        transform: scale(0.95);
    }
    
    .mobile-modal {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 100;
        display: flex;
        align-items: flex-end;
        transition: opacity 0.3s;
    }
    
    .mobile-modal.hidden {
        display: none;
    }
    
    .mobile-modal-content {
        background: white;
        border-radius: 1rem 1rem 0 0;
        width: 100%;
        max-height: 85vh;
        overflow-y: auto;
        animation: slideUp 0.3s ease-out;
    }
    
    @keyframes slideUp {
        from {
            transform: translateY(100%);
        }
        to {
            transform: translateY(0);
        }
    }
    
    .mobile-modal-header {
        position: sticky;
        top: 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 10;
    }
    
    .mobile-modal-body {
        padding: 1rem;
    }
    
    .mobile-form-group {
        margin-bottom: 1rem;
    }
    
    .mobile-form-label {
        display: block;
        font-weight: 600;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
        color: #374151;
    }
    
    .mobile-form-input,
    .mobile-form-select,
    .mobile-form-textarea {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 1rem;
    }
    
    .mobile-form-input:focus,
    .mobile-form-select:focus,
    .mobile-form-textarea:focus {
        outline: none;
        border-color: #9333ea;
    }
    
    .mobile-form-actions {
        position: sticky;
        bottom: 0;
        background: white;
        padding: 1rem;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 0.75rem;
    }
    
    .mobile-btn-primary {
        flex: 1;
        background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
        color: white;
        padding: 0.875rem;
        border-radius: 0.5rem;
        font-weight: 600;
        border: none;
    }
    
    .mobile-btn-secondary {
        flex: 1;
        background: #e5e7eb;
        color: #374151;
        padding: 0.875rem;
        border-radius: 0.5rem;
        font-weight: 600;
        border: none;
    }
</style>

<div class="mb-4">
    <!-- Header -->
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-bold text-purple-700 flex items-center">
            <i class="fas fa-tasks mr-2"></i>
            Công việc sửa chữa
        </h2>
        <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-sm font-semibold">
            <?= $thongke['so_congviec'] ?> công việc
        </span>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="mobile-stat-card">
            <div class="mobile-stat-label">Tổng số giờ</div>
            <div class="mobile-stat-value"><?= number_format($thongke['tong_gio'], 1) ?>h</div>
        </div>
        <div class="mobile-stat-card">
            <div class="mobile-stat-label">Trung bình</div>
            <div class="mobile-stat-value"><?= number_format($thongke['trung_binh_gio'], 1) ?>h</div>
        </div>
    </div>

    <!-- Work List -->
    <?php if (empty($congviecs)): ?>
        <div class="text-center py-8 text-gray-500">
            <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
            <p class="text-sm">Chưa có công việc nào</p>
            <p class="text-xs mt-1">Nhấn nút + để thêm công việc</p>
        </div>
    <?php else: ?>
        <?php foreach ($congviecs as $cv): ?>
            <div class="mobile-work-card">
                <div class="mobile-work-header">
                    <div>
                        <div class="text-xs text-gray-500 mb-1">
                            <i class="fas fa-calendar mr-1"></i>
                            <?= date('d/m/Y', strtotime($cv['ngay_lam'])) ?>
                        </div>
                        <div class="font-semibold text-gray-800">
                            <i class="fas fa-user mr-1"></i>
                            <?= htmlspecialchars($cv['nhanvien_ten']) ?>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="mobile-work-badge" style="background-color: <?= htmlspecialchars($cv['mau_sac'] ?? '#666') ?>">
                            <?= htmlspecialchars($cv['ten_capdo']) ?>
                        </span>
                        <div class="text-lg font-bold text-blue-600 mt-1">
                            <?= number_format($cv['so_gio_lam'], 1) ?>h
                        </div>
                    </div>
                </div>
                <div class="mobile-work-detail">
                    <div class="text-gray-700 leading-relaxed">
                        <?= htmlspecialchars($cv['noi_dung']) ?>
                    </div>
                    <?php if (!empty($cv['ghi_chu'])): ?>
                        <div class="text-xs text-gray-500 mt-2 italic">
                            <i class="fas fa-sticky-note mr-1"></i>
                            <?= htmlspecialchars($cv['ghi_chu']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mobile-work-actions">
                    <button onclick="openEditCongViecMobileModal(<?= $cv['stt'] ?>)" 
                            class="mobile-action-btn" style="background: #10b981; color: white;">
                        <i class="fas fa-edit"></i>
                        Sửa
                    </button>
                    <button onclick="deleteCongViecMobile(<?= $cv['stt'] ?>)" 
                            class="mobile-action-btn" style="background: #ef4444; color: white;">
                        <i class="fas fa-trash"></i>
                        Xóa
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Floating Action Button -->
<button class="mobile-fab" onclick="openAddCongViecMobileModal()" title="Thêm công việc">
    <i class="fas fa-plus"></i>
</button>

<!-- Modal Thêm Công Việc -->
<div id="addCongViecMobileModal" class="mobile-modal hidden">
    <div class="mobile-modal-content">
        <div class="mobile-modal-header">
            <h3 class="text-lg font-bold">
                <i class="fas fa-plus-circle mr-2"></i>
                Thêm công việc
            </h3>
            <button onclick="closeAddCongViecMobileModal()" class="text-white text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="formAddCongViecMobile" class="mobile-modal-body">
            <input type="hidden" name="hososcbd_stt" value="<?= $stt ?>">
            <input type="hidden" name="mavt" value="<?= htmlspecialchars($item['mavt'] ?? '') ?>">
            <input type="hidden" name="somay" value="<?= htmlspecialchars($item['somay'] ?? '') ?>">
            
            <div class="mobile-form-group">
                <label class="mobile-form-label">
                    Nhân viên <span class="text-red-500">*</span>
                </label>
                <select name="nhanvien_stt" required class="mobile-form-select">
                    <option value="">-- Chọn nhân viên --</option>
                    <?php foreach ($nhanviens as $nv): ?>
                        <option value="<?= $nv['stt'] ?>"><?= htmlspecialchars($nv['hoten']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mobile-form-group">
                <label class="mobile-form-label">
                    Ngày làm <span class="text-red-500">*</span>
                </label>
                <input type="date" name="ngay_lam" value="<?= date('Y-m-d') ?>" required class="mobile-form-input">
            </div>
            
            <div class="mobile-form-group">
                <label class="mobile-form-label">
                    Cấp độ bảo dưỡng <span class="text-red-500">*</span>
                </label>
                <select name="capdo_stt" required class="mobile-form-select" onchange="updateKpiDisplayMobile(this)">
                    <option value="">-- Chọn cấp độ --</option>
                    <?php foreach ($capdos as $cd): ?>
                        <option value="<?= $cd['stt'] ?>" 
                                data-kpi="<?= $cd['kpi_gio_chuan'] ?>"
                                data-ten="<?= htmlspecialchars($cd['ten_capdo']) ?>">
                            <?= htmlspecialchars($cd['ten_capdo']) ?> (KPI: <?= $cd['kpi_gio_chuan'] ?>h)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="kpiDisplayMobile" class="text-sm text-gray-600 mt-1"></div>
            </div>
            
            <div class="mobile-form-group">
                <label class="mobile-form-label">
                    Số giờ làm <span class="text-red-500">*</span>
                </label>
                <input type="number" name="so_gio_lam" step="0.5" min="0.5" max="8" required 
                       class="mobile-form-input" placeholder="Tối đa 8h">
            </div>
            
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="mobile-form-label">Giờ bắt đầu</label>
                    <input type="time" name="gio_bat_dau" class="mobile-form-input">
                </div>
                <div>
                    <label class="mobile-form-label">Giờ kết thúc</label>
                    <input type="time" name="gio_ket_thuc" class="mobile-form-input">
                </div>
            </div>
            
            <div class="mobile-form-group">
                <label class="mobile-form-label">
                    Nội dung công việc <span class="text-red-500">*</span>
                </label>
                <textarea name="noi_dung" rows="4" required class="mobile-form-textarea" 
                          placeholder="Mô tả chi tiết công việc..."></textarea>
            </div>
            
            <div class="mobile-form-group">
                <label class="mobile-form-label">Trạng thái</label>
                <select name="trang_thai" class="mobile-form-select">
                    <option value="Đang thực hiện">Đang thực hiện</option>
                    <option value="Hoàn thành">Hoàn thành</option>
                    <option value="Tạm dừng">Tạm dừng</option>
                </select>
            </div>
            
            <div class="mobile-form-group">
                <label class="mobile-form-label">Ghi chú</label>
                <input type="text" name="ghi_chu" class="mobile-form-input" placeholder="Ghi chú thêm...">
            </div>
            
            <div class="mobile-form-actions">
                <button type="button" onclick="closeAddCongViecMobileModal()" class="mobile-btn-secondary">
                    Hủy
                </button>
                <button type="submit" class="mobile-btn-primary">
                    <i class="fas fa-save mr-2"></i>Lưu
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sửa Công Việc -->
<div id="editCongViecMobileModal" class="mobile-modal hidden">
    <div class="mobile-modal-content">
        <div class="mobile-modal-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
            <h3 class="text-lg font-bold">
                <i class="fas fa-edit mr-2"></i>
                Sửa công việc #<span id="editCvSttMobile"></span>
            </h3>
            <button onclick="closeEditCongViecMobileModal()" class="text-white text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="formEditCongViecMobile" class="mobile-modal-body">
            <input type="hidden" id="edit_stt_mobile" name="stt">
            <input type="hidden" id="edit_hososcbd_stt_mobile" name="hososcbd_stt">
            
            <div class="mobile-form-group">
                <label class="mobile-form-label">Nhân viên <span class="text-red-500">*</span></label>
                <select id="edit_nhanvien_stt_mobile" name="nhanvien_stt" required class="mobile-form-select">
                    <?php foreach ($nhanviens as $nv): ?>
                        <option value="<?= $nv['stt'] ?>"><?= htmlspecialchars($nv['hoten']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mobile-form-group">
                <label class="mobile-form-label">Ngày làm <span class="text-red-500">*</span></label>
                <input type="date" id="edit_ngay_lam_mobile" name="ngay_lam" required class="mobile-form-input">
            </div>
            
            <div class="mobile-form-group">
                <label class="mobile-form-label">Cấp độ <span class="text-red-500">*</span></label>
                <select id="edit_capdo_stt_mobile" name="capdo_stt" required class="mobile-form-select" 
                        onchange="updateEditKpiDisplayMobile(this)">
                    <?php foreach ($capdos as $cd): ?>
                        <option value="<?= $cd['stt'] ?>" 
                                data-kpi="<?= $cd['kpi_gio_chuan'] ?>"
                                data-ten="<?= htmlspecialchars($cd['ten_capdo']) ?>">
                            <?= htmlspecialchars($cd['ten_capdo']) ?> (KPI: <?= $cd['kpi_gio_chuan'] ?>h)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="editKpiDisplayMobile" class="text-sm text-gray-600 mt-1"></div>
            </div>
            
            <div class="mobile-form-group">
                <label class="mobile-form-label">Số giờ làm <span class="text-red-500">*</span></label>
                <input type="number" id="edit_so_gio_lam_mobile" name="so_gio_lam" step="0.5" min="0.5" max="8" 
                       required class="mobile-form-input">
            </div>
            
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="mobile-form-label">Giờ bắt đầu</label>
                    <input type="time" id="edit_gio_bat_dau_mobile" name="gio_bat_dau" class="mobile-form-input">
                </div>
                <div>
                    <label class="mobile-form-label">Giờ kết thúc</label>
                    <input type="time" id="edit_gio_ket_thuc_mobile" name="gio_ket_thuc" class="mobile-form-input">
                </div>
            </div>
            
            <div class="mobile-form-group">
                <label class="mobile-form-label">Nội dung <span class="text-red-500">*</span></label>
                <textarea id="edit_noi_dung_mobile" name="noi_dung" rows="4" required 
                          class="mobile-form-textarea"></textarea>
            </div>
            
            <div class="mobile-form-group">
                <label class="mobile-form-label">Trạng thái</label>
                <select id="edit_trang_thai_mobile" name="trang_thai" class="mobile-form-select">
                    <option value="Đang thực hiện">Đang thực hiện</option>
                    <option value="Hoàn thành">Hoàn thành</option>
                    <option value="Tạm dừng">Tạm dừng</option>
                </select>
            </div>
            
            <div class="mobile-form-group">
                <label class="mobile-form-label">Ghi chú</label>
                <input type="text" id="edit_ghi_chu_mobile" name="ghi_chu" class="mobile-form-input">
            </div>
            
            <div class="mobile-form-actions">
                <button type="button" onclick="closeEditCongViecMobileModal()" class="mobile-btn-secondary">
                    Hủy
                </button>
                <button type="submit" class="mobile-btn-primary">
                    <i class="fas fa-save mr-2"></i>Cập nhật
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript -->
<script>
// Choices.js instances for employee selects
let addNhanVienChoices = null;
let editNhanVienChoices = null;

// Add Modal Functions
function openAddCongViecMobileModal() {
    document.getElementById('addCongViecMobileModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Initialize Choices.js for employee select if not already initialized
    if (!addNhanVienChoices) {
        const nhanVienSelect = document.getElementById('nhanvien_stt_mobile');
        if (nhanVienSelect) {
            addNhanVienChoices = new Choices(nhanVienSelect, {
                searchEnabled: true,
                searchPlaceholderValue: 'Tìm kiếm nhân viên...',
                itemSelectText: 'Nhấn để chọn',
                noResultsText: 'Không tìm thấy nhân viên',
                noChoicesText: 'Không có nhân viên nào',
                position: 'bottom',
                shouldSort: true,
                searchResultLimit: 50,
                fuseOptions: {
                    threshold: 0.3,
                    distance: 100
                }
            });
        }
    }
}

function closeAddCongViecMobileModal() {
    document.getElementById('addCongViecMobileModal').classList.add('hidden');
    document.getElementById('formAddCongViecMobile').reset();
    
    // Reset Choices.js selection
    if (addNhanVienChoices) {
        addNhanVienChoices.setChoiceByValue('');
    }
    
    document.body.style.overflow = '';
}

function updateKpiDisplayMobile(select) {
    const option = select.options[select.selectedIndex];
    const kpi = option.dataset.kpi;
    const ten = option.dataset.ten;
    const display = document.getElementById('kpiDisplayMobile');
    
    if (kpi && ten) {
        display.innerHTML = `<i class="fas fa-info-circle"></i> ${ten}: KPI chuẩn là <strong>${kpi} giờ</strong>`;
    } else {
        display.innerHTML = '';
    }
}

// Add Form Submit
document.getElementById('formAddCongViecMobile').addEventListener('submit', async function(e) {
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
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const responseText = await response.text();
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('Response:', responseText);
            throw new Error('Server trả về dữ liệu không hợp lệ');
        }
        
        if (result.success) {
            alert('✓ ' + result.message);
            location.reload();
        } else {
            alert('✗ ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Lỗi kết nối: ' + error.message);
    }
});

// Edit Modal Functions
async function openEditCongViecMobileModal(stt) {
    try {
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
            console.error('Response:', responseText);
            throw new Error('Server trả về dữ liệu không hợp lệ');
        }
        
        if (!result.success) {
            throw new Error(result.message || 'Không lấy được dữ liệu');
        }
        
        const cv = result.data;
        
        // Populate form
        document.getElementById('editCvSttMobile').textContent = cv.stt;
        document.getElementById('edit_stt_mobile').value = cv.stt;
        document.getElementById('edit_hososcbd_stt_mobile').value = cv.hososcbd_stt || '<?= $stt ?>';
        document.getElementById('edit_nhanvien_stt_mobile').value = cv.nhanvien_stt;
        document.getElementById('edit_ngay_lam_mobile').value = cv.ngay_lam;
        document.getElementById('edit_capdo_stt_mobile').value = cv.capdo_stt;
        document.getElementById('edit_so_gio_lam_mobile').value = cv.so_gio_lam;
        document.getElementById('edit_gio_bat_dau_mobile').value = cv.gio_bat_dau || '';
        document.getElementById('edit_gio_ket_thuc_mobile').value = cv.gio_ket_thuc || '';
        document.getElementById('edit_noi_dung_mobile').value = cv.noi_dung;
        document.getElementById('edit_trang_thai_mobile').value = cv.trang_thai;
        document.getElementById('edit_ghi_chu_mobile').value = cv.ghi_chu || '';
        
        updateEditKpiDisplayMobile(document.getElementById('edit_capdo_stt_mobile'));
        
        // Initialize Choices.js for edit employee select
        if (!editNhanVienChoices) {
            const editNhanVienSelect = document.getElementById('edit_nhanvien_stt_mobile');
            if (editNhanVienSelect) {
                editNhanVienChoices = new Choices(editNhanVienSelect, {
                    searchEnabled: true,
                    searchPlaceholderValue: 'Tìm kiếm nhân viên...',
                    itemSelectText: 'Nhấn để chọn',
                    noResultsText: 'Không tìm thấy nhân viên',
                    noChoicesText: 'Không có nhân viên nào',
                    position: 'bottom',
                    shouldSort: true,
                    searchResultLimit: 50,
                    fuseOptions: {
                        threshold: 0.3,
                        distance: 100
                    }
                });
            }
        }
        
        // Set the value after Choices.js is initialized
        if (editNhanVienChoices && cv.nhanvien_stt) {
            editNhanVienChoices.setChoiceByValue(cv.nhanvien_stt);
        }
        
        document.getElementById('editCongViecMobileModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    } catch (error) {
        console.error('Error:', error);
        alert('Lỗi khi tải dữ liệu: ' + error.message);
    }
}

function closeEditCongViecMobileModal() {
    document.getElementById('editCongViecMobileModal').classList.add('hidden');
    document.getElementById('formEditCongViecMobile').reset();
    document.body.style.overflow = '';
}

function updateEditKpiDisplayMobile(select) {
    const option = select.options[select.selectedIndex];
    const kpi = option.dataset.kpi;
    const ten = option.dataset.ten;
    const display = document.getElementById('editKpiDisplayMobile');
    
    if (kpi && ten) {
        display.innerHTML = `<i class="fas fa-info-circle"></i> ${ten}: KPI chuẩn là <strong>${kpi} giờ</strong>`;
    } else {
        display.innerHTML = '';
    }
}

// Edit Form Submit
document.getElementById('formEditCongViecMobile').addEventListener('submit', async function(e) {
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
            console.error('Response:', responseText);
            throw new Error('Server trả về dữ liệu không hợp lệ');
        }
        
        if (result.success) {
            alert('✓ ' + result.message);
            location.reload();
        } else {
            alert('✗ ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Lỗi kết nối: ' + error.message);
    }
});

// Delete Function
async function deleteCongViecMobile(stt) {
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

// Close modal when clicking outside
document.querySelectorAll('.mobile-modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
            document.body.style.overflow = '';
        }
    });
});
</script>
