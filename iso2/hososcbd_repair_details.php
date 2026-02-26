<?php
declare(strict_types=1);

session_start();

// Load dependencies for widget
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/includes/permissions.php';

// Include the repair details view
require_once __DIR__ . '/views/hososcbd/repair_details.php';
