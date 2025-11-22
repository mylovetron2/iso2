
<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../../config/constants.php';
?><!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title><?php echo isset($title) ? $title : 'Quản lý ISO 2.0'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
<button id="sidebarToggle" class="fixed top-4 left-4 z-50 bg-blue-700 text-white p-3 rounded-full shadow-lg focus:outline-none hover:bg-blue-600 transition-all duration-300" aria-label="Toggle Sidebar">
    <i class="fas fa-bars text-lg"></i>
</button>
<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-blue-700 text-white flex flex-col py-6 px-4 min-h-screen transition-transform duration-300 ease-in-out fixed top-0 left-0 h-full z-40 overflow-y-auto">
        <div class="mb-8 flex items-center justify-between">
            <a href="index.php" class="text-2xl font-bold tracking-wide">\n  Quản lý ISO </a>
            <button id="sidebarClose" class="lg:hidden text-white text-xl focus:outline-none" aria-label="Close Sidebar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <nav class="flex-1">
            <ul class="space-y-2">
                <li>
                    <a href="/iso2/tiendocongviec2.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-business-time mr-2"></i> Tiến độ công việc
                    </a>
                </li>
                <!-- Menu Danh mục thiết bị -->
                <li>
                    <div id="thietbiMenuBtn" class="flex items-center px-3 py-2 rounded hover:bg-blue-600 cursor-pointer select-none">
                        <i class="fas fa-cogs mr-2"></i> Danh mục thiết bị
                        <i id="thietbiCaret" class="fas fa-caret-down ml-auto transition-transform"></i>
                    </div>
                    <ul id="thietbiMenu" class="ml-6 mt-1 space-y-1 text-sm hidden">
                        <li>
                            <a href="/iso2/thietbi.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-cogs mr-2"></i> Thiết bị
                            </a>
                        </li>
                        <li>
                            <a href="/iso2/thietbihotro.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-tools mr-2"></i> Thiết bị Hỗ trợ
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="/iso2/donvi.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-building mr-2"></i> Đơn vị KH
                    </a>
                </li>
                <li>
                    <a href="/iso2/hososcbd.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-folder-open mr-2"></i> Hồ sơ SCBĐ
                    </a>
                </li>
                <li>
                    <a href="/iso2/phieubangiao.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-handshake mr-2"></i> Phiếu bàn giao
                    </a>
                </li>
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
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="mt-8 border-t border-blue-600 pt-4">
            <?php if (isLoggedIn()): ?>
                <div class="mb-2">Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                <a href="/iso2/logout.php" class="block px-3 py-2 rounded hover:bg-blue-600"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
            <?php else: ?>
                <a href="login.php" class="block px-3 py-2 rounded hover:bg-blue-600">Login</a>
            <?php endif; ?>
        </div>
    </aside>
    <!-- Overlay for mobile -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden"></div>
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
</script>
