
<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';

logout();
header('Location: /iso2/views/auth/login.php');
exit;
