
<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../../config/constants.php';

// Kiểm tra database đang sử dụng
$isLocalhost = false;
$dbSelectionFile = __DIR__ . '/../../config/db_selection.php';
if (file_exists($dbSelectionFile)) {
    $currentDb = require $dbSelectionFile;
    $isLocalhost = ($currentDb === 'localhost');
}
?><!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title><?php echo isset($title) ? $title : 'Quản lý ISO 2.0'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css?v=<?php echo time(); ?>">
    <style>
        body {
            -webkit-text-size-adjust: 100%;
            -webkit-tap-highlight-color: transparent;
        }
        /* Sidebar có thể toggle trên mọi màn hình */
        #sidebar {
            transform: translateX(0);
            transition: transform 0.3s ease-in-out;
        }
        #sidebar.hidden-sidebar {
            transform: translateX(-100%);
        }
        /* Main content tự động điều chỉnh khi sidebar ẩn/hiện */
        #mainContent {
            transition: margin-left 0.3s ease-in-out;
        }
        /* Trên mobile, sidebar ẩn mặc định */
        @media (max-width: 1023px) {
            #sidebar {
                transform: translateX(-100%);
            }
            #sidebar.show {
                transform: translateX(0);
            }
            /* Mobile không cần điều chỉnh margin */
            #mainContent {
                margin-left: 0 !important;
            }
        }
        @media (max-width: 768px) {
            .overflow-x-auto {
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
</head>

<body class="bg-gray-100">
<!-- Sidebar Toggle Button (hiện trên cả mobile và desktop) -->
<button id="sidebarToggle" class="fixed top-4 left-4 z-[70] bg-blue-700 text-white p-3 rounded-full shadow-lg focus:outline-none hover:bg-blue-600 transition-all duration-300" aria-label="Toggle Sidebar">
    <i class="fas fa-bars text-lg"></i>
</button>
<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-blue-700 text-white flex flex-col py-6 px-4 min-h-screen transition-transform duration-300 ease-in-out fixed top-0 left-0 h-full z-[60] overflow-y-auto">
        <div class="mb-3 flex items-center justify-between pl-14">
            <div class="flex flex-col flex-1">
                <a href="index.php" class="text-2xl font-bold tracking-wide">Quản lý ISO</a>
                <?php if ($isLocalhost): ?>
                    <span class="mt-1 text-xs bg-yellow-500 text-black px-2 py-1 rounded font-semibold w-fit">
                        <i class="fas fa-bug mr-1"></i>Bản DEBUG
                    </span>
                <?php endif; ?>
            </div>
            <button id="sidebarClose" class="lg:hidden text-white text-xl focus:outline-none" aria-label="Close Sidebar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <nav class="flex-1">
            <ul class="space-y-2">
                <!-- Dashboard -->
                <?php if (isLoggedIn()): ?>
                <li>
                    <a href="/iso2/dashboard.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600 bg-gradient-to-r from-blue-600/30 to-transparent border-l-4 border-yellow-400">
                        <i class="fas fa-chart-pie mr-2 text-yellow-300"></i> 
                        <span class="font-semibold">Dashboard</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- 1. Hồ sơ SCBD -->
                <?php if (isLoggedIn() && hasPermission('hososcbd.view')): ?>
                <li>
                    <a href="/iso2/hososcbd.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-folder-open mr-2"></i> Hồ sơ SCBD
                    </a>
                </li>
                <?php endif; ?>

                <!-- 1.5. Quản lý số phiếu YC -->
                <?php if (isLoggedIn() && hasPermission('phieuyeucau.view')): ?>
                <li>
                    <a href="/iso2/phieuyeucau.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-file-alt mr-2"></i> Quản lý số phiếu YC
                    </a>
                </li>
                <?php endif; ?>

                <!-- 1.6. Công việc sửa chữa -->
                <!-- IMPORTANT: Chạy execute_add_congviec_permissions.php trước khi uncomment -->
                <?php if (false): // Tạm thời tắt - Chạy migration trước: execute_add_congviec_permissions.php ?>
                <li>
                    <a href="/iso2/congviec_suachua.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-tasks mr-2"></i> Công việc sửa chữa
                    </a>
                </li>
                <?php endif; ?>

                <!-- 2. Bàn giao -->
                <?php if (isLoggedIn() && hasPermission('phieubangiao.view')): ?>
                <li>
                    <a href="/iso2/phieubangiao.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-clipboard-check mr-2"></i> Bàn giao
                    </a>
                </li>
                <?php endif; ?>

                <!-- 3. Quản lý Thiết bị -->
                <?php if (isLoggedIn() && hasPermission('thietbi.view')): ?>
                <li>
                    <div id="thietbiMenuBtn" class="flex items-center px-3 py-2 rounded hover:bg-blue-600 cursor-pointer select-none">
                        <i class="fas fa-cogs mr-2"></i> Quản lý Thiết bị
                        <i id="thietbiCaret" class="fas fa-caret-down ml-auto transition-transform"></i>
                    </div>
                    <ul id="thietbiMenu" class="ml-6 mt-1 space-y-1 text-sm hidden">
                        <li>
                            <a href="/iso2/thietbi.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-cogs mr-2"></i> Thiết bị máy giếng
                            </a>
                        </li>
                        <li>
                            <a href="/iso2/thietbihotro.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-tools mr-2"></i> Thiết bị Hỗ trợ
                            </a>
                        </li>
                        <li>
                            <a href="/iso2/thietbihckd.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-certificate mr-2"></i> Thiết bị HC/KĐ
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- 3.5. Vật tư thanh lý -->
                <?php if (isLoggedIn() && hasPermission('vattu.view')): ?>
                <li>
                    <div id="vattuMenuBtn" class="flex items-center px-3 py-2 rounded hover:bg-blue-600 cursor-pointer select-none">
                        <i class="fas fa-boxes mr-2"></i> Vật tư thanh lý
                        <i id="vattuCaret" class="fas fa-caret-down ml-auto transition-transform"></i>
                    </div>
                    <ul id="vattuMenu" class="ml-6 mt-1 space-y-1 text-sm hidden">
                        <li>
                            <a href="/iso2/vattuthanhly.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-boxes mr-2"></i> Vật tư thanh lý
                            </a>
                        </li>
                        <?php if (hasPermission('giohang.view')): ?>
                        <li>
                            <a href="/iso2/giohang.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80 relative">
                                <i class="fas fa-shopping-bag mr-2"></i> Giỏ hàng
                                <span id="sidebar-cart-badge" class="hidden ml-auto bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold"></span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (hasPermission('phieudathang.view')): ?>
                        <li>
                            <a href="/iso2/phieudathang.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-file-invoice mr-2"></i> Phiếu đặt hàng
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (hasPermission('phanloai_vattu.view')): ?>
                        <li>
                            <a href="/iso2/phanloaivattu.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-tags mr-2"></i> Phân loại vật tư
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (hasPermission('phieukiemsoatvattu.view')): ?>
                        <li>
                            <a href="/iso2/phieukiemsoatvattu.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-clipboard-check mr-2"></i> Phiếu kiểm soát vật tư
                            </a>
                        </li>
                        <?php endif; ?>
                        <li>
                            <a href="/iso2/thongke_vattu_thanh_ly.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-chart-bar mr-2"></i> Thống kê vật tư thanh lý
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- 3.6. Giao Nhận Thiết Bị -->
                <?php if (isLoggedIn() && hasPermission('giaonhanthietbi.view')): ?>
                <li>
                    <a href="/iso2/giaonhanthietbi.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-exchange-alt mr-2"></i> Giao Nhận Thiết Bị
                    </a>
                </li>
                <?php endif; ?>

                <!-- 4. Hiệu Chuẩn/Kiểm Định -->
                <?php if (isLoggedIn() && hasPermission('hieuchuan.view')): ?>
                <li>
                    <div id="bangcanhbaoMenuBtn" class="flex items-center px-3 py-2 rounded hover:bg-blue-600 cursor-pointer select-none">
                        <i class="fas fa-certificate mr-2"></i> Hiệu Chuẩn/Kiểm Định
                        <i id="bangcanhbaoCaret" class="fas fa-caret-down ml-auto transition-transform"></i>
                    </div>
                    <ul id="bangcanhbaoMenu" class="ml-6 mt-1 space-y-1 text-sm hidden">
                        <li>
                            <a href="/iso2/bangcanhbao.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-calendar-check mr-2"></i> Bảng Cảnh Báo
                            </a>
                        </li>
                        <?php /* Ẩn Phiếu Yêu Cầu
                        <li>
                            <a href="/iso2/bangcanhbao.php?action=phieuyc" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-file-alt mr-2"></i> Phiếu Yêu Cầu
                            </a>
                        </li>
                        */ ?>
                        <li>
                            <a href="/iso2/bangcanhbao.php?action=formhoso" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-edit mr-2"></i> Nhập Hồ Sơ HC
                            </a>
                        </li>
                        <?php /* Ẩn Phiếu Kiểm Tra
                        <li>
                            <a href="/iso2/bangcanhbao.php?action=phieukt" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-clipboard-check mr-2"></i> Phiếu Kiểm Tra
                            </a>
                        </li>
                        */ ?>
                        <li>
                            <a href="/iso2/thongke_hckd.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-chart-line mr-2"></i> Thống Kê HC/KĐ
                            </a>
                        </li>
                        <?php if (hasPermission('kehoach_kiemdinh.view')): ?>
                        <li>
                            <a href="/iso2/kehoach_thietbi_2026.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-calendar-alt mr-2"></i> Kế hoạch KĐ 2026
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- 4.5. Bảo dưỡng thiết bị -->
                <?php if (isLoggedIn() && hasPermission('kehoachbaoduong.view')): ?>
                <li>
                    <a href="/iso2/kehoachbaoduongdinhky.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-tools mr-2"></i> Bảo dưỡng định kỳ
                    </a>
                </li>
                <?php endif; ?>

                <!-- 5. Quản lý Lô & Mỏ -->
                <?php if (isLoggedIn() && (hasPermission('lo.view') || hasPermission('mo.view'))): ?>
                <li>
                    <div id="loMoMenuBtn" class="flex items-center px-3 py-2 rounded hover:bg-blue-600 cursor-pointer select-none">
                        <i class="fas fa-industry mr-2"></i> Quản lý Lô & Mỏ
                        <i id="loMoCaret" class="fas fa-caret-down ml-auto transition-transform"></i>
                    </div>
                    <ul id="loMoMenu" class="ml-6 mt-1 space-y-1 text-sm hidden">
                        <?php if (hasPermission('lo.view')): ?>
                        <li>
                            <a href="/iso2/lo.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-box mr-2"></i> Quản lý Lô
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (hasPermission('mo.view')): ?>
                        <li>
                            <a href="/iso2/mo.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-mountain mr-2"></i> Quản lý Mỏ
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- 6. Đơn vị -->
                <?php if (isLoggedIn() && hasPermission('donvi.view')): ?>
                <li>
                    <a href="/iso2/donvi.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-building mr-2"></i> Danh mục Bộ phận
                    </a>
                </li>
                <?php endif; ?>

                <!-- 7. Thống kê -->
                <?php if (isLoggedIn()): ?>
                <li>
                    <div id="thongkeMenuBtn" class="flex items-center px-3 py-2 rounded hover:bg-blue-600 cursor-pointer select-none">
                        <i class="fas fa-chart-bar mr-2"></i> Thống kê
                        <i id="thongkeCaret" class="fas fa-caret-down ml-auto transition-transform"></i>
                    </div>
                    <ul id="thongkeMenu" class="ml-6 mt-1 space-y-1 text-sm hidden">
                        <li>
                            <a href="/iso2/thongke_kiemdinh.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-clipboard-check mr-2"></i> Thống kê Kiểm định
                            </a>
                        </li>
                        <li>
                            <a href="/iso2/baocao_kiemdinh_thang.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-calendar-alt mr-2"></i> Báo cáo HC/KĐ theo kế hoạch
                            </a>
                        </li>
                        <li>
                            <a href="/iso2/thongke_hososcbd.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-exclamation-triangle mr-2"></i> Hồ sơ SCBD quá 30 ngày
                            </a>
                        </li>
                        <li>
                            <a href="/iso2/thongke_thietbi_chuakd.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-clipboard-list mr-2"></i> TB chưa Kiểm định
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- Quản lý file -->
                <?php if (isLoggedIn()): ?>
                <li>
                    <a href="https://diavatly.cloud/gdrive-manager" target="_blank" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-folder mr-2"></i> Quản lý file
                    </a>
                </li>
                <?php endif; ?>

                <?php if (isLoggedIn() && hasRole(ROLE_ADMIN)): ?>
                <!-- Menu Admin -->
                <li>
                    <div id="adminMenuBtn" class="flex items-center px-3 py-2 rounded hover:bg-blue-600 cursor-pointer select-none">
                        <i class="fas fa-user-shield mr-2"></i> Admin
                        <i id="adminCaret" class="fas fa-caret-down ml-auto transition-transform"></i>
                    </div>
                    <ul id="adminMenu" class="ml-6 mt-1 space-y-1 text-sm hidden">
                        <li>
                            <a href="/iso2/admin_user_permissions.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-users-cog mr-2"></i> Phân quyền User
                            </a>
                        </li>
                        <li>
                            <a href="/iso2/views/admin/permissions_manager.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-key mr-2"></i> Quản lý quyền
                            </a>
                        </li>
                        <li>
                            <a href="/iso2/views/admin/activity_logs.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-history mr-2"></i> Nhật ký hoạt động
                            </a>
                        </li>
                        <li>
                            <a href="/iso2/admin_database_switch.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-database mr-2"></i> Chuyển đổi Database
                            </a>
                        </li>
                        <?php /* Ẩn Cấu trúc Project
                        <li class="pt-2 border-t border-blue-600">
                            <div class="text-xs text-blue-300 px-3 py-1">Cấu trúc Project</div>
                        </li>
                        <li>
                            <a href="/iso2/project_structure.html" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-diagram-project mr-2"></i> Tổng quan cấu trúc
                            </a>
                        </li>
                        <li>
                            <a href="/iso2/project_model_view_structure.html" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-cubes mr-2"></i> Mô hình Model & View
                            </a>
                        </li>
                        */ ?>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="mt-8 border-t border-blue-600 pt-4">
            <?php if (isLoggedIn()): ?>
                <div class="mb-3 px-3">
                    <div class="text-sm text-blue-200 mb-1">Xin chào,</div>
                    <div class="font-semibold"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                </div>
                <a href="/iso2/profile.php" class="block px-3 py-2 rounded hover:bg-blue-600 mb-2">
                    <i class="fas fa-user-circle mr-2"></i>Thông tin cá nhân
                </a>
                <a href="/iso2/logout.php" class="block px-3 py-2 rounded hover:bg-blue-600"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
            <?php else: ?>
                <a href="login.php" class="block px-3 py-2 rounded hover:bg-blue-600">Login</a>
            <?php endif; ?>
        </div>
    </aside>
    <!-- Overlay for mobile -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden"></div>
    
    <?php if ($isLocalhost): ?>
    <!-- Debug mode indicator -->
    <div class="fixed top-4 right-4 z-50 bg-yellow-500 text-black px-3 py-2 rounded-lg shadow-lg font-bold text-sm flex items-center">
        <i class="fas fa-bug mr-2"></i>BẢN DEBUG (Localhost)
    </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main id="mainContent" class="flex-1 px-4 md:px-6 lg:px-8 py-4 md:py-6 lg:py-8 transition-all duration-300 lg:ml-64 mt-16 lg:mt-0">
<script>
    // Sidebar toggle logic - hoạt động trên cả desktop và mobile
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainContent = document.getElementById('mainContent');
    
    // Check if we're on mobile or desktop
    function isMobile() {
        return window.innerWidth < 1024;
    }
    
    function toggleSidebar() {
        if (isMobile()) {
            // Mobile: toggle với overlay
            if (sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.add('hidden');
                document.body.style.overflow = '';
            } else {
                sidebar.classList.add('show');
                sidebarOverlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        } else {
            // Desktop: toggle sidebar và điều chỉnh main content
            if (sidebar.classList.contains('hidden-sidebar')) {
                sidebar.classList.remove('hidden-sidebar');
                mainContent.style.marginLeft = '16rem'; // 256px = w-64
            } else {
                sidebar.classList.add('hidden-sidebar');
                mainContent.style.marginLeft = '0';
            }
        }
    }
    
    // Toggle button click
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    // Close button on sidebar (mobile only)
    if (sidebarClose) {
        sidebarClose.addEventListener('click', function() {
            if (isMobile()) {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.add('hidden');
                document.body.style.overflow = '';
            }
        });
    }
    
    // Click overlay to close sidebar on mobile
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.add('hidden');
            document.body.style.overflow = '';
        });
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (isMobile()) {
            // Mobile: reset state
            sidebar.classList.remove('hidden-sidebar');
            sidebar.classList.remove('show');
            sidebarOverlay.classList.add('hidden');
            document.body.style.overflow = '';
            mainContent.style.marginLeft = '';
        } else {
            // Desktop: remove mobile classes, keep desktop state
            sidebar.classList.remove('show');
            sidebarOverlay.classList.add('hidden');
            document.body.style.overflow = '';
            // Maintain desktop toggle state
            if (!sidebar.classList.contains('hidden-sidebar')) {
                mainContent.style.marginLeft = '16rem';
            }
        }
    });
    
    // Initialize: Set proper state on page load
    if (!isMobile()) {
        // Desktop: sidebar visible, main content with margin
        mainContent.style.marginLeft = '16rem';
    }
</script>
<script>
// Expand/collapse menu Admin
const adminBtn = document.getElementById('adminMenuBtn');
const adminMenu = document.getElementById('adminMenu');
const adminCaret = document.getElementById('adminCaret');
if (adminBtn && adminMenu && adminCaret) {
    adminBtn.addEventListener('click', function() {
        adminMenu.classList.toggle('hidden');
        adminCaret.classList.toggle('rotate-180');
    });
}

// Expand/collapse menu Danh mục thiết bị
const thietbiBtn = document.getElementById('thietbiMenuBtn');
const thietbiMenu = document.getElementById('thietbiMenu');
const thietbiCaret = document.getElementById('thietbiCaret');
if (thietbiBtn && thietbiMenu && thietbiCaret) {
    thietbiBtn.addEventListener('click', function() {
        thietbiMenu.classList.toggle('hidden');
        thietbiCaret.classList.toggle('rotate-180');
    });
}

// Expand/collapse menu Vật tư thanh lý
const vattuBtn = document.getElementById('vattuMenuBtn');
const vattuMenu = document.getElementById('vattuMenu');
const vattuCaret = document.getElementById('vattuCaret');
if (vattuBtn && vattuMenu && vattuCaret) {
    vattuBtn.addEventListener('click', function() {
        vattuMenu.classList.toggle('hidden');
        vattuCaret.classList.toggle('rotate-180');
    });
}

// Expand/collapse menu Bàn giao - DISABLED (menu is now a direct link)
/*
const bangiaoBtn = document.getElementById('bangiaoMenuBtn');
const bangiaoMenu = document.getElementById('bangiaoMenu');
const bangiaoCaret = document.getElementById('bangiaoCaret');
if (bangiaoBtn && bangiaoMenu && bangiaoCaret) {
    bangiaoBtn.addEventListener('click', function() {
        bangiaoMenu.classList.toggle('hidden');
        bangiaoCaret.classList.toggle('rotate-180');
    });
}
*/

// Expand/collapse menu Thống kê
const thongkeBtn = document.getElementById('thongkeMenuBtn');
const thongkeMenu = document.getElementById('thongkeMenu');
const thongkeCaret = document.getElementById('thongkeCaret');
if (thongkeBtn && thongkeMenu && thongkeCaret) {
    thongkeBtn.addEventListener('click', function() {
        thongkeMenu.classList.toggle('hidden');
        thongkeCaret.classList.toggle('rotate-180');
    });
}

// Toggle dropdown menus (for general use)
function toggleDropdown(dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    if (dropdown) {
        dropdown.classList.toggle('hidden');
    }
    // Close other dropdowns
    document.querySelectorAll('[id$="Dropdown"]').forEach(dd => {
        if (dd.id !== dropdownId) {
            dd.classList.add('hidden');
        }
    });
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('button') && !e.target.closest('[id$="Dropdown"]')) {
        document.querySelectorAll('[id$="Dropdown"]').forEach(dd => {
            dd.classList.add('hidden');
        });
    }
});

// Expand/collapse menu Bảng Cảnh Báo HC/KĐ
const bangcanhbaoBtn = document.getElementById('bangcanhbaoMenuBtn');
const bangcanhbaoMenu = document.getElementById('bangcanhbaoMenu');
const bangcanhbaoCaret = document.getElementById('bangcanhbaoCaret');
if (bangcanhbaoBtn && bangcanhbaoMenu && bangcanhbaoCaret) {
    bangcanhbaoBtn.addEventListener('click', function() {
        bangcanhbaoMenu.classList.toggle('hidden');
        bangcanhbaoCaret.classList.toggle('rotate-180');
    });
}

// Expand/collapse menu Lô & Mỏ
const loMoBtn = document.getElementById('loMoMenuBtn');
const loMoMenu = document.getElementById('loMoMenu');
const loMoCaret = document.getElementById('loMoCaret');
if (loMoBtn && loMoMenu && loMoCaret) {
    loMoBtn.addEventListener('click', function() {
        loMoMenu.classList.toggle('hidden');
        loMoCaret.classList.toggle('rotate-180');
    });
}
</script>
