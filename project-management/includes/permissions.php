<?php
function hasPermission($permission) {
    if (!isLoggedIn()) return false;
    $userModel = new User();
    return $userModel->hasPermission($_SESSION['user_id'], $permission);
}
function hasRole($role) {
    if (!isLoggedIn()) return false;
    $userModel = new User();
    return $userModel->hasRole($_SESSION['user_id'], $role);
}
function requirePermission($permission) {
    if (!hasPermission($permission)) {
        http_response_code(403);
        die('Access Denied. You do not have permission to access this page.');
    }
}
function requireRole($role) {
    if (!hasRole($role)) {
        http_response_code(403);
        die('Access Denied. Required role: ' . $role);
    }
}
