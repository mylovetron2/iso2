<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/ThongKeHCKDController.php';

// Check authentication
requireAuth();

// Run controller
$controller = new ThongKeHCKDController();
$controller->index();
