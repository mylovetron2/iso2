<?php
$title = 'Giỏ Hàng Vật Tư';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-shopping-cart text-blue-600 mr-2"></i> Giỏ Hàng Vật Tư
                </h1>
                <p class="text-gray-600 mt-1">
                    <?php if ($total > 0): ?>
                        Có <span class="font-semibold text-blue-600"><?php echo $total; ?></span> vật tư trong giỏ hàng
                    <?php else: ?>
                        Giỏ hàng trống
                    <?php endif; ?>
                </p>
            </div>
            <div class="flex gap-3">
                <a href="vattuthanhly.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded transition">
                    <i class="fas fa-arrow-left mr-1"></i> Quay lại
                </a>
                <?php if ($total > 0): ?>
                <button onclick="clearCart()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded transition">
                    <i class="fas fa-trash mr-1"></i> Xóa tất cả
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['success']); ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if ($total > 0): ?>
        <!-- Giỏ hàng có items -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">STT</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Mã VT</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Tên vật tư</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Phân loại</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Đơn vị</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Tồn kho</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Số lượng đặt</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Ghi chú</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($items as $index => $item): ?>
                            <tr class="hover:bg-gray-50" id="cart-row-<?php echo $item['cart_id']; ?>">
                                <td class="px-4 py-3 text-sm"><?php echo $index + 1; ?></td>
                                <td class="px-4 py-3 text-sm font-medium"><?php echo htmlspecialchars($item['mavattu'] ?? ''); ?></td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['ten_tiengviet'] ?? ''); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($item['ten_tienganh'] ?? ''); ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($item['ten_phanloai']): ?>
                                        <span class="px-2 py-1 rounded text-xs font-semibold text-white" 
                                              style="background-color: <?php echo htmlspecialchars($item['mau_sac'] ?? '#6B7280'); ?>">
                                            <?php echo htmlspecialchars($item['ten_phanloai']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($item['dvt_tiengviet'] ?? ''); ?></td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <span class="font-semibold <?php echo $item['soluong_conlai'] <= 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                        <?php echo number_format($item['soluong_conlai'], 2); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <input type="number" 
                                           min="1" 
                                           value="<?php echo $item['so_luong']; ?>" 
                                           class="w-20 border border-gray-300 rounded px-2 py-1 text-center"
                                           onchange="updateQuantity(<?php echo $item['cart_id']; ?>, this.value)">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" 
                                           placeholder="Ghi chú..."
                                           value="<?php echo htmlspecialchars($item['ghi_chu'] ?? ''); ?>"
                                           class="w-full border border-gray-300 rounded px-2 py-1 text-sm"
                                           onchange="updateNote(<?php echo $item['cart_id']; ?>, this.value)">
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button onclick="deleteItem(<?php echo $item['cart_id']; ?>)" 
                                            class="text-red-600 hover:text-red-800 transition"
                                            title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Action buttons -->
            <div class="border-t bg-gray-50 px-6 py-4 flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    Tổng: <span class="font-semibold text-lg text-blue-600"><?php echo $total; ?></span> vật tư
                </div>
                <div>
                    <a href="phieudathang.php?action=create&step=2" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded font-semibold transition">
                        <i class="fas fa-file-invoice mr-2"></i> Tạo Phiếu Đặt Hàng
                    </a>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Giỏ hàng trống -->
        <div class="bg-white rounded-lg shadow-md p-12 text-center">
            <i class="fas fa-shopping-cart text-gray-300 text-6xl mb-4"></i>
            <h2 class="text-2xl font-semibold text-gray-700 mb-2">Giỏ hàng trống</h2>
            <p class="text-gray-500 mb-6">Bạn chưa thêm vật tư nào vào giỏ hàng</p>
            <a href="vattuthanhly.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded inline-block transition">
                <i class="fas fa-shopping-bag mr-2"></i> Chọn vật tư
            </a>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function updateQuantity(cartId, quantity) {
    quantity = parseInt(quantity);
    if (quantity <= 0) {
        alert('Số lượng phải lớn hơn 0');
        return;
    }

    $.ajax({
        url: 'giohang.php?action=update',
        method: 'POST',
        data: {
            cart_id: cartId,
            so_luong: quantity
        },
        success: function(response) {
            if (response.success) {
                showMessage('success', response.message);
            } else {
                showMessage('error', response.message);
            }
        },
        error: function() {
            showMessage('error', 'Có lỗi xảy ra khi cập nhật');
        }
    });
}

function updateNote(cartId, note) {
    $.ajax({
        url: 'giohang.php?action=update',
        method: 'POST',
        data: {
            cart_id: cartId,
            so_luong: $('#cart-row-' + cartId + ' input[type="number"]').val(),
            ghi_chu: note
        },
        success: function(response) {
            if (response.success) {
                showMessage('success', 'Đã cập nhật ghi chú');
            }
        }
    });
}

function deleteItem(cartId) {
    if (!confirm('Xóa vật tư này khỏi giỏ hàng?')) {
        return;
    }

    $.ajax({
        url: 'giohang.php?action=delete',
        method: 'POST',
        data: { cart_id: cartId },
        success: function(response) {
            if (response.success) {
                $('#cart-row-' + cartId).fadeOut(300, function() {
                    $(this).remove();
                    // Reload nếu giỏ hàng trống
                    if ($('tbody tr').length === 0) {
                        location.reload();
                    }
                });
                updateCartBadge(response.cart_count);
                showMessage('success', response.message);
            } else {
                showMessage('error', response.message);
            }
        },
        error: function() {
            showMessage('error', 'Có lỗi xảy ra');
        }
    });
}

function clearCart() {
    if (!confirm('Xóa tất cả vật tư trong giỏ hàng?')) {
        return;
    }

    $.ajax({
        url: 'giohang.php?action=clear',
        method: 'POST',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                showMessage('error', response.message);
            }
        }
    });
}

function updateCartBadge(count) {
    const badge = document.getElementById('cart-badge');
    if (badge) {
        badge.textContent = count;
        if (count === 0) {
            badge.style.display = 'none';
        }
    }
}

function showMessage(type, message) {
    const colorClass = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
    const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
    
    const alertDiv = $('<div>').addClass(`${colorClass} border px-4 py-3 rounded mb-4`).html(
        `<i class="fas fa-${icon} mr-2"></i>${message}`
    );
    
    $('.container').prepend(alertDiv);
    setTimeout(() => alertDiv.fadeOut(() => alertDiv.remove()), 3000);
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
