
<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';

// Xóa remember token nếu có
if (isLoggedIn() && isset($_COOKIE['remember_me'])) {
    require_once __DIR__ . '/models/User.php';
    $userModel = new User();
    $userModel->clearRememberToken($_SESSION['user_id']);
    // Xóa cookie
    setcookie('remember_me', '', time() - 3600, '/', '', false, true);
}

logout();
header('Location: /iso2/views/auth/login.php');
exit;
