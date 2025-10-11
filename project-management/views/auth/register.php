<?php
require_once '../../config/constants.php';
$title = 'Register';
require_once '../layouts/header.php';
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm = isset($_POST['confirm']) ? $_POST['confirm'] : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $hoten = isset($_POST['hoten']) ? trim($_POST['hoten']) : '';
    if (empty($username) || empty($password) || empty($confirm)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc.';
    } elseif ($password !== $confirm) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } else {
        $userModel = new User();
        $exists = $userModel->findByUsername($username);
        if ($exists) {
            $error = 'Tên đăng nhập đã tồn tại!';
        } else {
            $userModel->create([
                'username' => $username,
                'password' => $password,
                'email' => $email,
                'hoten' => $hoten,
                'madv' => '',
                'nhom' => '',
                'phanquyen' => 0
            ]);
            $success = 'Đăng ký thành công! Bạn có thể đăng nhập.';
        }
    }
}
?>
<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8 mt-16">
    <h2 class="text-2xl font-bold mb-6 text-center">Đăng ký tài khoản</h2>
    <?php if ($error): ?>
        <div class="mb-4 text-red-600 text-center"><?php echo $error; ?></div>
    <?php elseif ($success): ?>
        <div class="mb-4 text-green-600 text-center"><?php echo $success; ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="mb-4">
            <label class="block mb-2 text-gray-700">Tên đăng nhập *</label>
            <input type="text" name="username" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring" required>
        </div>
        <div class="mb-4">
            <label class="block mb-2 text-gray-700">Mật khẩu *</label>
            <input type="password" name="password" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring" required>
        </div>
        <div class="mb-4">
            <label class="block mb-2 text-gray-700">Xác nhận mật khẩu *</label>
            <input type="password" name="confirm" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring" required>
        </div>
        <div class="mb-4">
            <label class="block mb-2 text-gray-700">Email</label>
            <input type="email" name="email" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring">
        </div>
        <div class="mb-6">
            <label class="block mb-2 text-gray-700">Họ tên</label>
            <input type="text" name="hoten" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring">
        </div>
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded">Đăng ký</button>
    </form>
    <div class="mt-4 text-center">
        <a href="login.php" class="text-blue-600 hover:underline">Đã có tài khoản? Đăng nhập</a>
    </div>
</div>
<?php require_once '../layouts/footer.php'; ?>
