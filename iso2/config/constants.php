<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_URL', 'http://localhost/project-management');
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');
define('ROLE_VIEWER', 'viewer');
define('PERMISSION_PROJECT_VIEW', 'project.view');
define('PERMISSION_PROJECT_CREATE', 'project.create');
define('PERMISSION_PROJECT_EDIT', 'project.edit');
define('PERMISSION_PROJECT_DELETE', 'project.delete');
define('PERMISSION_PROJECT_MANAGE', 'project.manage');

// Hồ sơ SCBĐ Permissions
define('PERMISSION_HOSOSCBD_VIEW', 'hososcbd.view');
define('PERMISSION_HOSOSCBD_CREATE', 'hososcbd.create');
define('PERMISSION_HOSOSCBD_EDIT', 'hososcbd.edit');
define('PERMISSION_HOSOSCBD_DELETE', 'hososcbd.delete');

require_once __DIR__.'/database.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/permissions.php';
