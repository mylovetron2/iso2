<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

error_reporting(E_ALL); ini_set('display_errors', 1);
require_once '../../config/constants.php';
$title = 'Login';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        if (login($username, $password)) {
            header('Location: ../../tiendocongviec2.php');
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
        <div class="mb-6">
            <label class="block mb-2 text-gray-700">Mật khẩu</label>
            <input type="password" name="password" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring" required>
        </div>
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded">Đăng nhập</button>
    </form>
    <div class="mt-4 text-center">
        <a href="register.php" class="text-blue-600 hover:underline">Chưa có tài khoản? Đăng ký</a>
    </div>
</div>
<?php require_once '../layouts/footer.php'; ?>
<?php if ($_SERVER['REQUEST_METHOD'] !== 'POST') getDBConnection(true); ?>
