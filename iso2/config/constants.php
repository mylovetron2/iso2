<?php
declare(strict_types=1);

session_start();

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

require_once __DIR__.'/database.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/permissions.php';
