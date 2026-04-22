<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-6 text-white">
            <h1 class="text-2xl md:text-3xl font-bold">
                <i class="fas fa-user-edit mr-2"></i>Chỉnh sửa thông tin cá nhân
            </h1>
        </div>

        <!-- Error Messages -->
        <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
            <div class="mx-6 mt-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                <p class="font-semibold mb-2"><i class="fas fa-exclamation-triangle mr-2"></i>Có lỗi xảy ra:</p>
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>

        <div class="p-6 space-y-8">
            <!-- Form cập nhật thông tin cơ bản -->
            <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                <h2 class="text-xl font-bold mb-4 text-gray-800">
                    <i class="fas fa-info-circle mr-2 text-blue-600"></i>Thông tin cơ bản
                </h2>
                
                <form method="POST" action="/iso2/profile.php?action=update" class="space-y-4">
                    <!-- Username (Read-only) -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-user-tag mr-2"></i>Tên đăng nhập
                        </label>
                        <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed" 
                               readonly>
                        <p class="text-sm text-gray-500 mt-1">Tên đăng nhập không thể thay đổi</p>
                    </div>

                    <!-- Full Name -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-signature mr-2"></i>Họ và tên <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="hoten" required
                               value="<?php echo htmlspecialchars($user['hoten'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Nhập họ và tên đầy đủ">
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2"></i>Email
                        </label>
                        <input type="email" name="email"
                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="email@example.com">
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-4">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                            <i class="fas fa-save mr-2"></i>Lưu thay đổi
                        </button>
                        <a href="/iso2/profile.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-semibold text-center transition-colors">
                            <i class="fas fa-times mr-2"></i>Hủy
                        </a>
                    </div>
                </form>
            </div>

            <!-- Form đổi mật khẩu -->
            <div id="change-password" class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                <h2 class="text-xl font-bold mb-4 text-gray-800">
                    <i class="fas fa-key mr-2 text-blue-600"></i>Đổi mật khẩu
                </h2>
                
                <form method="POST" action="/iso2/profile.php?action=change_password" class="space-y-4">
                    <!-- Current Password -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2"></i>Mật khẩu hiện tại <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="current_password" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Nhập mật khẩu hiện tại">
                    </div>

                    <!-- New Password -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-key mr-2"></i>Mật khẩu mới <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="new_password" required minlength="5"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Nhập mật khẩu mới (tối thiểu 5 ký tự)">
                        <p class="text-sm text-gray-500 mt-1">Mật khẩu phải có ít nhất 5 ký tự</p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-check-circle mr-2"></i>Xác nhận mật khẩu mới <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="confirm_password" required minlength="5"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Nhập lại mật khẩu mới">
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm text-yellow-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Lưu ý:</strong> Sau khi đổi mật khẩu, bạn sẽ cần đăng nhập lại với mật khẩu mới.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-4">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                            <i class="fas fa-key mr-2"></i>Đổi mật khẩu
                        </button>
                        <a href="/iso2/profile.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-semibold text-center transition-colors">
                            <i class="fas fa-times mr-2"></i>Hủy
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="border-t border-gray-200 px-6 py-4 bg-gray-50">
            <a href="/iso2/profile.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 font-semibold">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại trang profile
            </a>
        </div>
    </div>
</div>

<script>
// Validate password match on client side
document.addEventListener('DOMContentLoaded', function() {
    const passwordForm = document.querySelector('form[action*="change_password"]');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const newPassword = document.querySelector('input[name="new_password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Mật khẩu xác nhận không khớp với mật khẩu mới!');
                return false;
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
