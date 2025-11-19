
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
        /* Hiển thị sidebar mặc định trên desktop */
        #sidebar {
            transform: translateX(0);
        }
        /* Ẩn sidebar mặc định trên mobile */
        @media (max-width: 1023px) {
            #sidebar {
                transform: translateX(-100%);
            }
            #sidebar.show {
                transform: translateX(0);
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
<!-- Sidebar Toggle Button (chỉ hiện trên mobile) -->
<button id="sidebarToggle" class="lg:hidden fixed top-4 left-4 z-50 bg-blue-700 text-white p-3 rounded-full shadow-lg focus:outline-none hover:bg-blue-600" aria-label="Toggle Sidebar">
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
                <li>
                    <a href="/iso2/projects.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-folder mr-2"></i> Projects
                    </a>
                </li>
                <li>
                    <a href="/iso2/thietbihotro.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-tools mr-2"></i> Thiết bị Hỗ trợ
                    </a>
                </li>
                <?php if (isLoggedIn() && hasRole(ROLE_ADMIN)): ?>
                <li>
                    <a href="/iso2/admin_user_permissions.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-user-shield mr-2"></i> Phân quyền User
                    </a>
                </li>
                <li>
                    <a href="/iso2/views/admin/permissions_manager.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-key mr-2"></i> Quản lý quyền
                    </a>
                </li>
                <!-- Menu Cấu trúc project -->
                <li>
                    <div id="projectStructMenuBtn" class="flex items-center px-3 py-2 rounded hover:bg-blue-600 cursor-pointer select-none">
                        <i class="fas fa-sitemap mr-2"></i> Cấu trúc project
                        <i id="projectStructCaret" class="fas fa-caret-down ml-auto transition-transform"></i>
                    </div>
                    <ul id="projectStructMenu" class="ml-6 mt-1 space-y-1 text-sm hidden">
                        <li>
                            <a href="/iso2/project_structure.html"  class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
                                <i class="fas fa-diagram-project mr-2"></i> Tổng quan cấu trúc
                            </a>
                        </li>
                        <li>
                            <a href="/iso2/project_model_view_structure.html"  class="flex items-center px-3 py-2 rounded hover:bg-blue-500 bg-blue-800/80">
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
                <a href="register.php" class="block px-3 py-2 rounded hover:bg-blue-600">Register</a>
            <?php endif; ?>
        </div>
    </aside>
    <!-- Overlay for mobile -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden"></div>
    <!-- Main Content -->
    <main id="mainContent" class="flex-1 px-4 md:px-6 lg:px-8 py-4 md:py-6 lg:py-8 transition-all duration-300 lg:ml-64 mt-16 lg:mt-0">
<script>
    // Sidebar toggle logic
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    function openSidebar() {
        sidebar.classList.add('show');
        if (window.innerWidth < 1024) {
            sidebarOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }
    function closeSidebar() {
        sidebar.classList.remove('show');
        sidebarOverlay.classList.add('hidden');
        document.body.style.overflow = '';
    }
    // Sidebar toggle chỉ hoạt động trên mobile
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            if (sidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }
    if (sidebarClose) {
        sidebarClose.addEventListener('click', closeSidebar);
    }
    // Click overlay to close sidebar on mobile
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
    // Auto show/hide sidebar when window resizes
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
            // Desktop: luôn hiển thị sidebar, ẩn overlay
            sidebar.classList.remove('show');
            sidebarOverlay.classList.add('hidden');
            document.body.style.overflow = '';
        } else {
            // Mobile: ẩn sidebar mặc định
            sidebar.classList.remove('show');
            sidebarOverlay.classList.add('hidden');
            document.body.style.overflow = '';
        }
    });
    // Initialize: không cần thêm class 'show' trên desktop vì CSS đã xử lý
</script>
<script>
// Expand/collapse menu Cấu trúc project
const structBtn = document.getElementById('projectStructMenuBtn');
const structMenu = document.getElementById('projectStructMenu');
const structCaret = document.getElementById('projectStructCaret');
if (structBtn && structMenu && structCaret) {
    structBtn.addEventListener('click', function() {
        structMenu.classList.toggle('hidden');
        structCaret.classList.toggle('rotate-180');
    });
}
</script>
