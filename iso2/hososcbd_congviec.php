<?php
declare(strict_types=1);

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load dependencies
require_once __DIR__ . '/config/database.php';

// Load auth and permissions - optional for now
if (file_exists(__DIR__ . '/includes/auth.php')) {
    require_once __DIR__ . '/includes/auth.php';
}
if (file_exists(__DIR__ . '/models/User.php')) {
    require_once __DIR__ . '/models/User.php';
}
if (file_exists(__DIR__ . '/includes/permissions.php')) {
    require_once __DIR__ . '/includes/permissions.php';
}

// TODO: Uncomment after running permission migration
// Check if user is logged in
/*
if (!isLoggedIn()) {
    header('Location: /iso2/index.php?error=login_required');
    exit;
}

// Check view permission
if (!hasPermission('congviec_suachua.view')) {
    header('Location: /iso2/index.php?error=no_permission');
    exit;
}
*/

// Include the congviec list view
require_once __DIR__ . '/views/hososcbd/congviec_list.php';
