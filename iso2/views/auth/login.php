<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

error_reporting(E_ALL); ini_set('display_errors', 1);
require_once '../../config/constants.php';
$title = 'Login';
$error = '';

// Kiểm tra remember me cookie khi load trang
if (!isLoggedIn() && isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    require_once '../../models/User.php';
    $userModel = new User();
    $user = $userModel->findByRememberToken($token);
    if ($user) {
        $_SESSION['user_id'] = $user['stt'];
        $_SESSION['user_stt'] = $user['stt'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_name'] = $user['username'];
        $_SESSION['user_email'] = $user['email'] ?? '';
        $_SESSION['role'] = $user['role'] ?? 'user';
        header('Location: ../../hososcbd.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember_me']);
    
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        if (login($username, $password)) {
            // Xử lý remember me
            if ($remember) {
                require_once '../../models/User.php';
                $userModel = new User();
                $user = $userModel->findByUsername($username);
                if ($user) {
                    // Tạo token ngẫu nhiên
                    $token = bin2hex(random_bytes(32));
                    // Lưu token vào database
                    $userModel->saveRememberToken($user['stt'], $token);
                    // Lưu cookie 30 ngày
                    setcookie('remember_me', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                }
            }
            header('Location: ../../hososcbd.php');
            exit;
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng!';
        }
    }
}
require_once '../layouts/header.php';
?>
<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8 mt-16">
    <h2 class="text-2xl font-bold mb-6 text-center">Đăng nhập</h2>
    <?php if ($error): ?>
        <div class="mb-4 text-red-600 text-center"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="mb-4">
            <label class="block mb-2 text-gray-700">Tên đăng nhập</label>
            <input type="text" name="username" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring" required>
        </div>
        <div class="mb-4">
            <label class="block mb-2 text-gray-700">Mật khẩu</label>
            <input type="password" name="password" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring" required>
        </div>
        <div class="mb-6">
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" name="remember_me" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-700">Ghi nhớ đăng nhập</span>
            </label>
        </div>
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded">Đăng nhập</button>
    </form>
</div>
<?php require_once '../layouts/footer.php'; ?>
<?php if ($_SERVER['REQUEST_METHOD'] !== 'POST') getDBConnection(true); ?>
