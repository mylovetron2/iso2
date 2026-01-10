<?php
declare(strict_types=1);

// Ensure auth functions are available
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/auth.php';
}

if (!function_exists('hasPermission')) {
    function hasPermission(string $permission): bool {
        if (!isLoggedIn()) return false;
        $userModel = new User();
        return $userModel->hasPermission($_SESSION['user_id'], $permission);
    }
}

if (!function_exists('hasRole')) {
    function hasRole(string $role): bool {
        if (!isLoggedIn()) return false;
        $userModel = new User();
        return $userModel->hasRole($_SESSION['user_id'], $role);
    }
}

if (!function_exists('requirePermission')) {
    function requirePermission(string $permission): void {
        if (!hasPermission($permission)) {
            http_response_code(403);
            die('Access Denied. You do not have permission to access this page.');
        }
    }
}

if (!function_exists('requireRole')) {
    function requireRole(string $role): void {
        if (!hasRole($role)) {
            http_response_code(403);
            die('Access Denied. Required role: ' . $role);
        }
    }
}
