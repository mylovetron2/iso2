<?php
/**
 * Permission Check Helper
 * Kiểm tra permissions và cung cấp helper functions
 */

// Load permission functions
require_once __DIR__ . '/permissions.php';

/**
 * Check if user has permission, throw exception if not
 * @param string $permission Permission to check (e.g., 'giohang.view')
 * @param string $message Custom error message
 * @throws Exception
 * @return void
 */
function checkPermission(string $permission, string $message = null): void
{
    if (!hasPermission($permission)) {
        // If AJAX request, return JSON error
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'permission_denied',
                'message' => $message ?? 'Bạn không có quyền thực hiện hành động này'
            ]);
            exit;
        }
        
        // If normal request, redirect to index with error message
        $_SESSION['error_message'] = $message ?? 'Bạn không có quyền truy cập chức năng này. Vui lòng liên hệ quản trị viên.';
        $_SESSION['missing_permission'] = $permission;
        header('Location: /iso2/index.php?error=permission_denied');
        exit;
    }
}

/**
 * Check if user has any of the given permissions
 * @param array $permissions Array of permissions to check
 * @return bool
 */
function hasAnyPermission(array $permissions): bool
{
    foreach ($permissions as $permission) {
        if (hasPermission($permission)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if user has all of the given permissions
 * @param array $permissions Array of permissions to check
 * @return bool
 */
function hasAllPermissions(array $permissions): bool
{
    foreach ($permissions as $permission) {
        if (!hasPermission($permission)) {
            return false;
        }
    }
    return true;
}

/**
 * Require any of the given permissions, throw exception if none match
 * @param array $permissions Array of permissions
 * @param string $message Custom error message
 * @throws Exception
 * @return void
 */
function requireAnyPermission(array $permissions, string $message = null): void
{
    if (!hasAnyPermission($permissions)) {
        checkPermission($permissions[0], $message); // Will throw/redirect
    }
}

/**
 * Require all of the given permissions, throw exception if any missing
 * @param array $permissions Array of permissions
 * @param string $message Custom error message
 * @throws Exception
 * @return void
 */
function requireAllPermissions(array $permissions, string $message = null): void
{
    foreach ($permissions as $permission) {
        checkPermission($permission, $message);
    }
}
