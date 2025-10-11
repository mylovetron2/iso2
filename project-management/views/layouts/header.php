
<?php
require_once __DIR__ . '/../../config/constants.php';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Quản lý ISO 2.0'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-100">
<!-- Sidebar Toggle Button (mobile & desktop) -->
<button id="sidebarToggle" class="fixed top-4 left-4 z-50 bg-blue-700 text-white p-2 rounded-full shadow-lg focus:outline-none" aria-label="Toggle Sidebar">
    <i class="fas fa-bars"></i>
</button>
<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-blue-700 text-white flex flex-col py-6 px-4 min-h-screen transition-transform duration-300 ease-in-out translate-x-0 fixed top-0 left-0 h-full z-40">
        <div class="mb-8 flex items-center justify-between">
            <a href="index.php" class="text-2xl font-bold tracking-wide">\n  Quản lý ISO </a>
            <button id="sidebarClose" class="lg:hidden text-white text-xl focus:outline-none" aria-label="Close Sidebar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <nav class="flex-1">
            <ul class="space-y-2">
                <li>
                    <a href="/iso2/projects.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-folder mr-2"></i> Projects
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
    <!-- Main Content -->
    <main id="mainContent" class="flex-1 px-8 py-8 transition-all duration-300">
<script>
    // Sidebar toggle logic
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    function openSidebar() {
        sidebar.classList.remove('translate-x-[-100%]');
        sidebar.classList.add('translate-x-0');
    }
    function closeSidebar() {
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('translate-x-[-100%]');
    }
    // Sidebar toggle luôn hoạt động trên mọi màn hình
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            if (sidebar.classList.contains('translate-x-0')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }
    if (sidebarClose) {
        sidebarClose.addEventListener('click', closeSidebar);
    }
    // Sidebar luôn mở mặc định khi load trang
    openSidebar();
    // Đẩy main content sang phải khi sidebar mở, kéo về khi sidebar đóng trên mọi màn hình
    function updateMainMargin() {
        const main = document.getElementById('mainContent');
        if (sidebar.classList.contains('translate-x-0')) {
            main.style.marginLeft = '16rem';
        } else {
            main.style.marginLeft = '0';
        }
    }
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', updateMainMargin);
    }
    if (sidebarClose) {
        sidebarClose.addEventListener('click', updateMainMargin);
    }
    window.addEventListener('resize', updateMainMargin);
    updateMainMargin();
</script>
