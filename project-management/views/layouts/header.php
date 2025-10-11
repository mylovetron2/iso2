
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
<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-blue-700 text-white flex flex-col py-6 px-4 min-h-screen">
        <div class="mb-8">
            <a href="index.php" class="text-2xl font-bold tracking-wide">Quản lý ISO v2.0</a>
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
    <main class="flex-1 px-8 py-8">
