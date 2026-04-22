<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-8 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="bg-white/20 rounded-full p-4">
                        <i class="fas fa-user text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold"><?php echo htmlspecialchars($user['hoten'] ?? $user['username']); ?></h1>
                        <p class="text-blue-100 mt-1">
                            <i class="fas fa-at mr-1"></i><?php echo htmlspecialchars($user['username']); ?>
                        </p>
                    </div>
                </div>
                <a href="/iso2/profile.php?action=edit" class="bg-white text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-edit mr-2"></i>Chỉnh sửa
                </a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mx-6 mt-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="mx-6 mt-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Profile Information -->
        <div class="p-6">
            <h2 class="text-xl font-bold mb-4 text-gray-800">
                <i class="fas fa-info-circle mr-2 text-blue-600"></i>Thông tin cá nhân
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Username -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <label class="block text-sm font-semibold text-gray-600 mb-2">
                        <i class="fas fa-user-tag mr-2"></i>Tên đăng nhập
                    </label>
                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($user['username']); ?></p>
                </div>

                <!-- Full Name -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <label class="block text-sm font-semibold text-gray-600 mb-2">
                        <i class="fas fa-signature mr-2"></i>Họ và tên
                    </label>
                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($user['hoten'] ?? 'Chưa cập nhật'); ?></p>
                </div>

                <!-- Email -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <label class="block text-sm font-semibold text-gray-600 mb-2">
                        <i class="fas fa-envelope mr-2"></i>Email
                    </label>
                    <p class="text-gray-900 font-medium">
                        <?php echo !empty($user['email']) ? htmlspecialchars($user['email']) : '<span class="text-gray-400 italic">Chưa cập nhật</span>'; ?>
                    </p>
                </div>

                <!-- Role -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <label class="block text-sm font-semibold text-gray-600 mb-2">
                        <i class="fas fa-user-shield mr-2"></i>Vai trò
                    </label>
                    <p class="text-gray-900 font-medium">
                        <?php 
                        $role = $user['role'] ?? 'user';
                        $roleDisplay = [
                            'admin' => 'Quản trị viên',
                            'supervisor' => 'Giám sát',
                            'user' => 'Người dùng'
                        ];
                        echo htmlspecialchars($roleDisplay[$role] ?? ucfirst($role));
                        ?>
                    </p>
                </div>

                <!-- Department -->
                <?php if (!empty($user['madv'])): ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <label class="block text-sm font-semibold text-gray-600 mb-2">
                        <i class="fas fa-building mr-2"></i>Đơn vị
                    </label>
                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($user['madv']); ?></p>
                </div>
                <?php endif; ?>

                <!-- Group -->
                <?php if (!empty($user['nhom'])): ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <label class="block text-sm font-semibold text-gray-600 mb-2">
                        <i class="fas fa-users mr-2"></i>Nhóm
                    </label>
                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($user['nhom']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Security Section -->
        <div class="border-t border-gray-200 p-6 bg-gray-50">
            <h2 class="text-xl font-bold mb-4 text-gray-800">
                <i class="fas fa-lock mr-2 text-blue-600"></i>Bảo mật
            </h2>
            <div class="flex items-center justify-between bg-white rounded-lg p-4 border border-gray-200">
                <div>
                    <p class="font-semibold text-gray-900">Mật khẩu</p>
                    <p class="text-sm text-gray-500 mt-1">••••••••</p>
                </div>
                <a href="/iso2/profile.php?action=edit#change-password" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-key mr-2"></i>Đổi mật khẩu
                </a>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="border-t border-gray-200 px-6 py-4 bg-gray-50">
            <a href="/iso2/index.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 font-semibold">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại trang chủ
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
