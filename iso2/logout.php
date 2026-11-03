
<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';

// logout() cũng xóa token và cookie "ghi nhớ đăng nhập"
logout();
header('Location: ' . appUrl('views/auth/login.php'));
exit;
