<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Nhập Hồ Sơ HC/KĐ';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-file-alt mr-2"></i> 
        <?php echo $mode === 'edit' ? 'Sửa' : 'Nhập'; ?> Hồ Sơ Hiệu Chuẩn/Kiểm Định
    </h1>

    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="bangcanhbao.php?action=savehoso" class="space-y-4">
        <!-- Số Hồ Sơ -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-base font-bold text-gray-800 mb-2">
                    <i class="fas fa-file-alt text-blue-600"></i> Số Hồ Sơ <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-2">
                    <input type="text" name="sohs" id="sohs" 
                           value="<?php echo htmlspecialchars($hoSo['sohs'] ?? ''); ?>" 
                           class="border rounded px-3 py-2 w-full" required>
                    <button type="button" id="btnGenerateSoHS" 
                            class="bg-blue-500 hover:bg-blue-700 text-white px-4 py-2 rounded">
                        <i class="fas fa-magic"></i> Tự động
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-base font-bold text-gray-800 mb-2">
                    <i class="fas fa-tools text-blue-600"></i> Tên Thiết Bị <span class="text-red-500">*</span>
                </label>
                <select name="tenmay" id="tenmay" class="border rounded px-3 py-2 w-full select2-device" required style="width: 100%;">
                    <option value="">-- Click hoặc gõ để tìm thiết bị --</option>
                    <?php if (!empty($thietBiList)): ?>
                        <?php foreach ($thietBiList as $group => $items): ?>
                            <optgroup label="<?php echo htmlspecialchars($group); ?>">
                                <?php foreach ($items as $item): ?>
                                    <option value="<?php echo htmlspecialchars($item['mavattu']); ?>"
                                            data-somay="<?php echo htmlspecialchars($item['somay']); ?>"
                                            data-bophansh="<?php echo htmlspecialchars($item['bophansh']); ?>"
                                            data-chusohuu="<?php echo htmlspecialchars($item['chusohuu'] ?? ''); ?>"
                                            <?php echo (isset($thietBi) && $thietBi['mavattu'] === $item['mavattu']) ? 'selected' : ''; ?>>
                                        [<?php echo htmlspecialchars($item['somay']); ?>] <?php echo htmlspecialchars($item['tenviettat']); ?> - <?php echo htmlspecialchars($item['bophansh'] ?? ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <small class="text-gray-500 text-xs mt-1 block">
                    <i class="fas fa-keyboard"></i> Click vào ô này và gõ luôn để tìm nhanh (tên, số máy, chủ phương tiện)
                </small>
            </div>
        </div>

        <!-- Ngày HC & Ngày HC Tiếp Theo -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-base font-bold text-gray-800 mb-2">
                    <i class="fas fa-calendar-check text-green-600"></i> Ngày Hiệu Chuẩn <span class="text-red-500">*</span>
                </label>
                <input type="date" name="ngayhc" id="ngayhc" 
                       value="<?php echo htmlspecialchars($hoSo['ngayhc'] ?? ''); ?>" 
                       class="border rounded px-3 py-2 w-full" required>
            </div>

            <div>
                <label class="block text-base font-bold text-gray-800 mb-2">
                    <i class="fas fa-calendar-plus text-green-600"></i> Ngày HC Tiếp Theo <span class="text-red-500">*</span>
                </label>
                <input type="date" name="ngayhctt" 
                       value="<?php echo htmlspecialchars($hoSo['ngayhctt'] ?? ''); ?>" 
                       class="border rounded px-3 py-2 w-full" required>
            </div>
        </div>

        <!-- Số Máy & Chủ Phương Tiện -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-base font-bold text-gray-800 mb-2">
                    <i class="fas fa-barcode text-purple-600"></i> Số Máy
                </label>
                <input type="text" name="somay" id="somay" 
                       value="<?php echo htmlspecialchars($thietBi['somay'] ?? ''); ?>" 
                       class="border rounded px-3 py-2 w-full bg-gray-100" readonly>
            </div>

            <div>
                <label class="block text-base font-bold text-gray-800 mb-2">
                    <i class="fas fa-user-tie text-purple-600"></i> Chủ Phương Tiện
                </label>
                <input type="text" name="chuphuongtien" id="chuphuongtien" 
                       value="<?php echo htmlspecialchars($thietBi['bophansh'] ?? ''); ?>" 
                       class="border rounded px-3 py-2 w-full bg-gray-100" readonly>
            </div>
        </div>

        <!-- Phương Pháp Chuẩn -->
        <div>
            <label class="block text-base font-bold text-gray-800 mb-2">
                <i class="fas fa-clipboard-list text-orange-600"></i> Phương Pháp Chuẩn
            </label>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                <label class="flex items-center">
                    <input type="checkbox" name="danchuan" value="1" 
                           <?php echo (!empty($hoSo['danchuan'])) ? 'checked' : ''; ?>
                           class="mr-2">
                    Dẫn chuẩn
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="mauchuan" value="1" 
                           <?php echo (!empty($hoSo['mauchuan'])) ? 'checked' : ''; ?>
                           class="mr-2">
                    Chuẩn qua mẫu chuẩn
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="dinhky" value="1" 
                           <?php echo (!empty($hoSo['dinhky'])) ? 'checked' : ''; ?>
                           class="mr-2">
                    Định kỳ
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="dotxuat" value="1" 
                           <?php echo (!empty($hoSo['dotxuat'])) ? 'checked' : ''; ?>
                           class="mr-2">
                    Đột xuất
                </label>
            </div>
        </div>

        <!-- Thiết Bị Dẫn Chuẩn (5 thiết bị) -->
        <div>
            <label class="block text-base font-bold text-gray-800 mb-2">
                <i class="fas fa-toolbox text-indigo-600"></i> Thiết Bị Dẫn Chuẩn
            </label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <select name="thietbidc<?php echo $i; ?>" class="border rounded px-3 py-2 text-sm">
                        <option value="">-- Chọn thiết bị dẫn chuẩn <?php echo $i; ?> --</option>
                        <?php if (!empty($danhChuanList)): ?>
                            <?php foreach ($danhChuanList as $dc): ?>
                                <option value="<?php echo htmlspecialchars($dc['mavattu']); ?>"
                                        <?php echo (isset($hoSo["thietbidc$i"]) && $hoSo["thietbidc$i"] === $dc['mavattu']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dc['tenviettat'] . ' - ' . $dc['somay']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Nơi Thực Hiện & Người HC -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-base font-bold text-gray-800 mb-2">
                    <i class="fas fa-map-marker-alt text-red-600"></i> Nơi Thực Hiện <span class="text-red-500">*</span>
                </label>
                <select name="noithuchien" class="border rounded px-3 py-2 w-full" required>
                    <option value="">-- Chọn --</option>
                    <option value="XSCCMDVL" <?php echo (isset($hoSo['noithuchien']) && $hoSo['noithuchien'] === 'XSCCMDVL') ? 'selected' : ''; ?>>
                        XSCCMDVL
                    </option>
                    <option value="MN" <?php echo (isset($hoSo['noithuchien']) && $hoSo['noithuchien'] === 'MN') ? 'selected' : ''; ?>>
                        MN
                    </option>
                    <option value="XNKT" <?php echo (isset($hoSo['noithuchien']) && $hoSo['noithuchien'] === 'XNKT') ? 'selected' : ''; ?>>
                        XNKT
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-base font-bold text-gray-800 mb-2">
                    <i class="fas fa-user-check text-teal-600"></i> Người Hiệu Chuẩn <span class="text-red-500">*</span>
                </label>
                <select name="nhanvien" class="border rounded px-3 py-2 w-full" required>
                    <option value="">-- Chọn nhân viên --</option>
                    <?php if (!empty($nhanVienList)): ?>
                        <?php foreach ($nhanVienList as $nv): ?>
                            <option value="<?php echo htmlspecialchars($nv['hoten']); ?>"
                                    <?php echo (isset($hoSo['nhanvien']) && $hoSo['nhanvien'] === $nv['hoten']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($nv['hoten'] . ' - ' . $nv['chucdanh']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <!-- Tình Trạng Kiểm Tra -->
        <div>
            <label class="block text-base font-bold text-gray-800 mb-2">
                <i class="fas fa-check-circle text-yellow-600"></i> Tình Trạng Kiểm Tra <span class="text-red-500">*</span>
            </label>
            <select name="ttkt" class="border rounded px-3 py-2 w-full" required>
                <option value="">-- Chọn --</option>
                <option value="Tốt" <?php echo (isset($hoSo['ttkt']) && $hoSo['ttkt'] === 'Tốt') ? 'selected' : ''; ?>>
                    Tốt
                </option>
                <option value="Hỏng" <?php echo (isset($hoSo['ttkt']) && $hoSo['ttkt'] === 'Hỏng') ? 'selected' : ''; ?>>
                    Hỏng
                </option>
            </select>
        </div>

        <!-- Buttons -->
        <div class="flex gap-2 pt-4">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                <i class="fas fa-save mr-1"></i> Lưu
            </button>
            <a href="bangcanhbao.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded inline-block">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
        </div>
    </form>
</div>

<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 CSS (Stable Version) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

<!-- Select2 JS (Stable Version) -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

<!-- Custom Select2 Styling -->
<style>
    /* Tùy chỉnh Select2 cho giao diện đẹp hơn */
    .select2-container--default .select2-selection--single {
        height: 42px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
        padding: 0.5rem 0.75rem !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 26px !important;
        padding-left: 0 !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
        right: 8px !important;
    }
    
    .select2-dropdown {
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
        z-index: 9999 !important;
    }
    
    .select2-search--dropdown .select2-search__field {
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
        padding: 0.5rem !important;
    }
    
    .select2-results__option {
        padding: 8px 12px !important;
    }
    
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #3b82f6 !important;
    }
    
    .select2-results__option[aria-selected=true] {
        background-color: #dbeafe !important;
        color: #1e40af !important;
    }
    
    .select2-container {
        z-index: 9999 !important;
    }
</style>

<script>
// Check if libraries loaded
if (typeof jQuery === 'undefined') {
    alert('jQuery không load được! Vui lòng kiểm tra kết nối internet.');
} else if (typeof $.fn.select2 === 'undefined') {
    alert('Select2 không load được! Vui lòng kiểm tra kết nối internet.');
} else {
    console.log('✓ jQuery ' + $.fn.jquery + ' loaded');
    console.log('✓ Select2 loaded');
}

$(document).ready(function() {
    // Kiểm tra lại trong ready
    if (typeof $.fn.select2 === 'undefined') {
        console.error('Select2 not available');
        return;
    }
    
    console.log('Initializing Select2 for #tenmay...');
    
    // Khởi tạo Select2 cho combobox thiết bị
    $('#tenmay').select2({
        placeholder: '-- Gõ để tìm kiếm thiết bị --',
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return "Không tìm thấy kết quả";
            },
            searching: function() {
                return "Đang tìm kiếm...";
            },
            inputTooShort: function() {
                return "Vui lòng nhập ít nhất 1 ký tự";
            }
        },
        matcher: function(params, data) {
            // Nếu không có từ khóa tìm kiếm, hiển thị tất cả
            if ($.trim(params.term) === '') {
                return data;
            }
            
            // Không tìm kiếm trong optgroup
            if (typeof data.text === 'undefined') {
                return null;
            }
            
            // Chuyển về lowercase để tìm không phân biệt hoa thường
            var term = params.term.toLowerCase();
            var text = data.text.toLowerCase();
            
            // Tìm kiếm trong text của option
            if (text.indexOf(term) > -1) {
                return data;
            }
            
            // Tìm kiếm trong các thuộc tính data
            var $option = $(data.element);
            var somay = ($option.data('somay') || '').toString().toLowerCase();
            var bophansh = ($option.data('bophansh') || '').toString().toLowerCase();
            var chusohuu = ($option.data('chusohuu') || '').toString().toLowerCase();
            
            if (somay.indexOf(term) > -1 || bophansh.indexOf(term) > -1 || chusohuu.indexOf(term) > -1) {
                return data;
            }
            
            return null;
        }
    });
    
    console.log('✓ Select2 initialized successfully');
    
    // Khi select2 mở, focus vào search input
    $('#tenmay').on('select2:open', function(e) {
        setTimeout(function() {
            var searchField = $('.select2-search__field');
            if (searchField.length) {
                searchField[0].focus();
            }
        }, 50);
    });
    
    // Khi thay đổi thiết bị, tự động điền thông tin
    $('#tenmay').on('select2:select', function(e) {
        var data = e.params.data;
        var $option = $(data.element);
        
        var somay = $option.data('somay') || '';
        var bophansh = $option.data('bophansh') || '';
        
        $('#somay').val(somay);
        $('#chuphuongtien').val(bophansh);
        
        console.log('✓ Device selected:', data.text);
        
        // Trigger change event for bangcanhbao.js
        $('#tenmay').trigger('change');
    });
});
</script>

<!-- bangcanhbao.js for auto-generate số hồ sơ functionality -->
<script src="assets/js/bangcanhbao.js?v=<?php echo time(); ?>"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
