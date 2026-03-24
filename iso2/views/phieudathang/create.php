<?php
$title = 'Tạo Phiếu Đặt Hàng';
require_once __DIR__ . '/../layouts/header.php';

// Xác định bước hiện tại
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
?>

<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <!-- Progress indicator -->
        <div class="mb-6">
            <div class="flex items-center justify-center">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full <?php echo $step === 1 ? 'bg-blue-600 text-white' : 'bg-green-600 text-white'; ?> font-bold">
                        <?php echo $step === 1 ? '1' : '✓'; ?>
                    </div>
                    <span class="ml-2 font-semibold <?php echo $step === 1 ? 'text-blue-600' : 'text-gray-600'; ?>">Chọn vật tư</span>
                </div>
                <div class="w-20 h-1 mx-4 <?php echo $step === 2 ? 'bg-blue-600' : 'bg-gray-300'; ?>"></div>
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full <?php echo $step === 2 ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600'; ?> font-bold">
                        2
                    </div>
                    <span class="ml-2 font-semibold <?php echo $step === 2 ? 'text-blue-600' : 'text-gray-600'; ?>">Thông tin NCC</span>
                </div>
            </div>
        </div>

        <?php if ($step === 1): ?>
            <!-- BƯỚC 1: CHỌN VẬT TƯ -->
            <h1 class="text-2xl font-bold mb-6">
                <i class="fas fa-list text-blue-600 mr-2"></i> Bước 1: Chọn Vật Tư Đặt Hàng
            </h1>

            <!-- Search & Filter -->
            <form method="GET" action="phieudathang.php" class="mb-4">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="step" value="1">
                <div class="flex gap-2">
                    <input type="text" 
                           name="search" 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                           placeholder="Tìm kiếm mã VT, tên..." 
                           class="flex-1 border rounded px-3 py-2">
                    
                    <select name="phanloai_id" class="border rounded px-3 py-2" style="min-width: 180px;">
                        <option value="">-- Tất cả phân loại --</option>
                        <?php foreach ($phanLoaiList ?? [] as $pl): ?>
                            <option value="<?php echo $pl['id']; ?>" 
                                    <?php echo (isset($_GET['phanloai_id']) && $_GET['phanloai_id'] == $pl['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pl['ten_phanloai']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                        <i class="fas fa-search mr-1"></i> Tìm
                    </button>
                    <a href="phieudathang.php?action=create&step=1" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-redo mr-1"></i> Reset
                    </a>
                </div>
            </form>

            <!-- Cart badge info -->
            <div class="bg-blue-50 border border-blue-200 rounded p-3 mb-4">
                <div class="flex items-center justify-between">
                    <span class="text-blue-800">
                        <i class="fas fa-shopping-cart mr-2"></i>
                        <strong id="cart-count-display"><?php echo $cartCount ?? 0; ?></strong> vật tư đã chọn
                    </span>
                    <?php if (($cartCount ?? 0) > 0): ?>
                    <button onclick="clearAllCart()" class="text-red-600 hover:text-red-800 text-sm">
                        <i class="fas fa-trash mr-1"></i> Xóa tất cả
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Danh sách vật tư với checkbox -->
            <div class="mb-6">
                <div class="overflow-x-auto">
                    <table class="w-full border">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-3 py-2 w-12">Chọn</th>
                                <th class="border px-3 py-2">Mã VT</th>
                                <th class="border px-3 py-2">Tên vật tư</th>
                                <th class="border px-3 py-2">Phân loại</th>
                                <th class="border px-3 py-2">ĐVT</th>
                                <th class="border px-3 py-2 text-center">Tồn kho</th>
                                <th class="border px-3 py-2 w-32">Số lượng đặt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($vattuList)): ?>
                                <tr>
                                    <td colspan="7" class="border px-3 py-4 text-center text-gray-500">
                                        Không có vật tư nào
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($vattuList as $vt): ?>
                                    <tr id="row-<?php echo $vt['stt']; ?>" class="hover:bg-gray-50">
                                        <td class="border px-3 py-2 text-center">
                                            <input type="checkbox" 
                                                   id="check-<?php echo $vt['stt']; ?>"
                                                   value="<?php echo $vt['stt']; ?>"
                                                   onchange="handleCheckboxChange(<?php echo $vt['stt']; ?>)"
                                                   class="w-5 h-5 cursor-pointer">
                                        </td>
                                        <td class="border px-3 py-2 font-medium"><?php echo htmlspecialchars($vt['mavattu'] ?? ''); ?></td>
                                        <td class="border px-3 py-2">
                                            <div class="font-medium"><?php echo htmlspecialchars($vt['ten_tiengviet'] ?? ''); ?></div>
                                            <?php if (!empty($vt['ten_tienganh'])): ?>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($vt['ten_tienganh']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="border px-3 py-2">
                                            <?php if (!empty($vt['ten_phanloai'])): ?>
                                                <span class="px-2 py-1 rounded text-xs font-semibold text-white" 
                                                      style="background-color: <?php echo htmlspecialchars($vt['mau_sac'] ?? '#6B7280'); ?>">
                                                    <?php echo htmlspecialchars($vt['ten_phanloai']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="border px-3 py-2 text-center"><?php echo htmlspecialchars($vt['dvt_tiengviet'] ?? ''); ?></td>
                                        <td class="border px-3 py-2 text-center">
                                            <span class="font-semibold <?php echo ($vt['soluong_conlai'] ?? 0) <= 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                                <?php echo number_format($vt['soluong_conlai'] ?? 0, 2); ?>
                                            </span>
                                        </td>
                                        <td class="border px-3 py-2">
                                            <input type="number" 
                                                   id="qty-<?php echo $vt['stt']; ?>"
                                                   min="1" 
                                                   value="1" 
                                                   class="w-full border rounded px-2 py-1 text-center"
                                                   onchange="updateQuantity(<?php echo $vt['stt']; ?>)">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (isset($totalPages) && $totalPages > 1): ?>
                <div class="mt-4 flex justify-center gap-2">
                    <?php 
                    $currentPage = $_GET['page'] ?? 1;
                    $search = $_GET['search'] ?? '';
                    $phanloai_id = $_GET['phanloai_id'] ?? '';
                    ?>
                    <?php if ($currentPage > 1): ?>
                        <a href="?action=create&step=1&page=<?php echo $currentPage - 1; ?>&search=<?php echo urlencode($search); ?>&phanloai_id=<?php echo $phanloai_id; ?>" 
                           class="px-3 py-1 border rounded hover:bg-gray-100">← Trước</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $currentPage): ?>
                            <span class="px-3 py-1 bg-blue-600 text-white rounded"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?action=create&step=1&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&phanloai_id=<?php echo $phanloai_id; ?>" 
                               class="px-3 py-1 border rounded hover:bg-gray-100"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?action=create&step=1&page=<?php echo $currentPage + 1; ?>&search=<?php echo urlencode($search); ?>&phanloai_id=<?php echo $phanloai_id; ?>" 
                           class="px-3 py-1 border rounded hover:bg-gray-100">Sau →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 justify-between">
                <a href="vattuthanhly.php" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                    <i class="fas fa-times mr-1"></i> Hủy
                </a>
                <a href="phieudathang.php?action=create&step=2" 
                   id="btn-next-step"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
                    <i class="fas fa-arrow-right mr-1"></i> Tiếp tục: Nhập thông tin NCC
                </a>
            </div>

        <?php else: ?>
            <!-- BƯỚC 2: NHẬP THÔNG TIN NCC -->
            <h1 class="text-2xl font-bold mb-6">
                <i class="fas fa-building text-blue-600 mr-2"></i> Bước 2: Thông Tin Nhà Cung Cấp
            </h1>

            <?php if (empty($cartItems)): ?>
                <!-- Giỏ hàng trống -->
                <div class="bg-yellow-50 border border-yellow-200 rounded p-4 mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl mr-3"></i>
                        <div>
                            <p class="font-semibold text-yellow-800">Chưa chọn vật tư nào!</p>
                            <p class="text-sm text-yellow-700">Vui lòng quay lại bước 1 để chọn vật tư.</p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-start">
                    <a href="phieudathang.php?action=create&step=1" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
                        <i class="fas fa-arrow-left mr-1"></i> Quay lại chọn vật tư
                    </a>
                </div>
            <?php else: ?>
                <!-- Hiển thị tóm tắt vật tư đã chọn -->
                <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-6">
                    <h3 class="font-semibold text-blue-900 mb-3">
                        <i class="fas fa-box-open mr-2"></i> Vật tư đã chọn (<?php echo count($cartItems); ?> items)
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-blue-100">
                                <tr>
                                    <th class="px-3 py-2 text-left">STT</th>
                                    <th class="px-3 py-2 text-left">Mã VT</th>
                                    <th class="px-3 py-2 text-left">Tên vật tư</th>
                                    <th class="px-3 py-2 text-center">ĐVT</th>
                                    <th class="px-3 py-2 text-center">Số lượng đặt</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <?php foreach ($cartItems as $index => $item): ?>
                                    <tr class="border-t border-blue-100">
                                        <td class="px-3 py-2"><?php echo $index + 1; ?></td>
                                        <td class="px-3 py-2 font-medium"><?php echo htmlspecialchars($item['mavattu'] ?? ''); ?></td>
                                        <td class="px-3 py-2"><?php echo htmlspecialchars($item['ten_tiengviet'] ?? ''); ?></td>
                                        <td class="px-3 py-2 text-center"><?php echo htmlspecialchars($item['dvt_tiengviet'] ?? ''); ?></td>
                                        <td class="px-3 py-2 text-center font-semibold"><?php echo $item['so_luong']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Form nhập thông tin NCC -->
                <form method="POST" action="phieudathang.php?action=store">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-semibold mb-2">
                                Nhà cung cấp <span class="text-red-600">*</span>
                            </label>
                            <input type="text" 
                                   name="nha_cung_cap" 
                                   required
                                   class="w-full border rounded px-3 py-2" 
                                   placeholder="Tên nhà cung cấp...">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">
                                Số hợp đồng NCC <span class="text-red-600">*</span>
                            </label>
                            <input type="text" 
                                   name="so_hd_ncc" 
                                   required
                                   class="w-full border rounded px-3 py-2" 
                                   placeholder="Số hợp đồng...">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">
                                Ngày giao dự kiến <span class="text-red-600">*</span>
                            </label>
                            <input type="date" 
                                   name="ngay_du_kien_nhan" 
                                   required
                                   class="w-full border rounded px-3 py-2"
                                   value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">Ghi chú</label>
                            <textarea name="ghi_chu" 
                                      rows="1"
                                      class="w-full border rounded px-3 py-2" 
                                      placeholder="Ghi chú (nếu có)..."></textarea>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-3 justify-between">
                        <a href="phieudathang.php?action=create&step=1" 
                           class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                            <i class="fas fa-arrow-left mr-1"></i> Quay lại chọn vật tư
                        </a>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded font-semibold">
                            <i class="fas fa-check-circle mr-1"></i> Tạo Phiếu Đặt Hàng
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Handle checkbox change - Auto add/remove from cart
function handleCheckboxChange(vattuStt) {
    const checkbox = document.getElementById('check-' + vattuStt);
    const qtyInput = document.getElementById('qty-' + vattuStt);
    const soLuong = parseInt(qtyInput.value) || 1;

    if (checkbox.checked) {
        // Add to cart
        addToCartAjax(vattuStt, soLuong);
    } else {
        // Remove from cart
        removeFromCartAjax(vattuStt);
    }
}

// Update quantity when input changes (if checkbox is checked)
function updateQuantity(vattuStt) {
    const checkbox = document.getElementById('check-' + vattuStt);
    const qtyInput = document.getElementById('qty-' + vattuStt);
    const soLuong = parseInt(qtyInput.value) || 1;

    if (checkbox.checked) {
        // Update cart quantity
        updateCartQuantityAjax(vattuStt, soLuong);
    }
}

// AJAX - Add to cart
function addToCartAjax(vattuStt, soLuong) {
    $.ajax({
        url: 'giohang.php?action=add',
        method: 'POST',
        data: {
            vattu_stt: vattuStt,
            so_luong: soLuong
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateCartCount(response.cart_count);
                showNotification('success', response.message);
            } else {
                showNotification('error', response.message);
                // Uncheck if failed
                document.getElementById('check-' + vattuStt).checked = false;
            }
        },
        error: function() {
            showNotification('error', 'Có lỗi xảy ra khi thêm vào giỏ');
            document.getElementById('check-' + vattuStt).checked = false;
        }
    });
}

// AJAX - Remove from cart
function removeFromCartAjax(vattuStt) {
    $.ajax({
        url: 'giohang.php?action=removeByVattu',
        method: 'POST',
        data: { vattu_stt: vattuStt },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateCartCount(response.cart_count);
                showNotification('success', 'Đã xóa khỏi giỏ hàng');
            }
        },
        error: function() {
            showNotification('error', 'Có lỗi xảy ra');
        }
    });
}

// AJAX - Update cart quantity
function updateCartQuantityAjax(vattuStt, soLuong) {
    $.ajax({
        url: 'giohang.php?action=updateByVattu',
        method: 'POST',
        data: {
            vattu_stt: vattuStt,
            so_luong: soLuong
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showNotification('success', 'Đã cập nhật số lượng');
            }
        }
    });
}

// Clear all cart
function clearAllCart() {
    if (!confirm('Xóa tất cả vật tư trong giỏ hàng?')) {
        return;
    }

    $.ajax({
        url: 'giohang.php?action=clear',
        method: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateCartCount(0);
                // Uncheck all checkboxes
                document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
                showNotification('success', 'Đã xóa tất cả');
            }
        }
    });
}

// Update cart count display
function updateCartCount(count) {
    const display = document.getElementById('cart-count-display');
    if (display) {
        display.textContent = count;
    }
    
    // Update badge in header if exists
    const badge = document.getElementById('cart-badge');
    if (badge) {
        badge.textContent = count;
        if (count === 0) {
            badge.classList.add('hidden');
        } else {
            badge.classList.remove('hidden');
        }
    }
}

// Show notification
function showNotification(type, message) {
    const colorClass = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${colorClass} text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-all`;
    notification.innerHTML = `
        <div class="flex items-center gap-2">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Load cart count on page load
$(document).ready(function() {
    fetch('giohang.php?action=getCount')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartCount(data.cart_count);
            }
        })
        .catch(error => console.error('Error loading cart count:', error));
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
