<?php
/**
 * Auth Check Helper
 * Kiểm tra authentication và cung cấp helper functions
 */

// Load auth functions
require_once __DIR__ . '/auth.php';

/**
 * Check if user is logged in, redirect to login if not
 * @param string $redirect_url URL to redirect after login
 * @return void
 */
function requireLogin(string $redirect_url = null): void
{
    if (!isLoggedIn()) {
        if ($redirect_url) {
            $_SESSION['redirect_after_login'] = $redirect_url;
        } else {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        }
        header('Location: /iso2/views/auth/login.php');
        exit;
    }
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current username
 * @return string|null
 */
function getCurrentUsername(): ?string
{
    return $_SESSION['username'] ?? null;
}

/**
 * Check if current user has a specific role
 * @param string $role Role name to check
 * @return bool
 */
function hasRole(string $role): bool
{
    if (!isLoggedIn()) {
        return false;
    }
    
    // Load User model if not loaded
    if (!class_exists('User')) {
        require_once __DIR__ . '/../models/User.php';
    }
    
    $userModel = new User();
    return $userModel->hasRole($_SESSION['user_id'], $role);
}
