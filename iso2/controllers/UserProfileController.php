<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/User.php';

class UserProfileController {
    private User $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Hiển thị trang profile của user hiện tại
     */
    public function view(): void {
        requireAuth();
        
        $userId = $_SESSION['user_id'];
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            $_SESSION['error'] = 'Không tìm thấy thông tin người dùng';
            header('Location: /iso2/index.php');
            exit;
        }
        
        $title = 'Thông tin cá nhân';
        require_once __DIR__ . '/../views/profile/view.php';
    }
    
    /**
     * Hiển thị form sửa profile
     */
    public function edit(): void {
        requireAuth();
        
        $userId = $_SESSION['user_id'];
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            $_SESSION['error'] = 'Không tìm thấy thông tin người dùng';
            header('Location: /iso2/index.php');
            exit;
        }
        
        $title = 'Chỉnh sửa thông tin cá nhân';
        require_once __DIR__ . '/../views/profile/edit.php';
    }
    
    /**
     * Cập nhật thông tin cơ bản (email, tên)
     */
    public function update(): void {
        requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /iso2/profile.php');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            $_SESSION['error'] = 'Không tìm thấy thông tin người dùng';
            header('Location: /iso2/index.php');
            exit;
        }
        
        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['hoten'] ?? '');
        
        $errors = [];
        
        // Validate email
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ';
        }
        
        // Validate name
        if (empty($name)) {
            $errors[] = 'Tên không được để trống';
        }
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: /iso2/profile.php?action=edit');
            exit;
        }
        
        // Cập nhật thông tin
        $updateData = [
            'email' => $email,
            'hoten' => $name
        ];
        
        if ($this->userModel->updateProfile($userId, $updateData)) {
            // Cập nhật session nếu cần
            $_SESSION['user_email'] = $email;
            $_SESSION['success'] = 'Cập nhật thông tin thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi khi cập nhật thông tin';
        }
        
        header('Location: /iso2/profile.php');
        exit;
    }
    
    /**
     * Thay đổi mật khẩu
     */
    public function changePassword(): void {
        requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /iso2/profile.php');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            $_SESSION['error'] = 'Không tìm thấy thông tin người dùng';
            header('Location: /iso2/index.php');
            exit;
        }
        
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        // Validate current password
        $passwordValid = false;
        if (password_verify($currentPassword, $user['password'])) {
            $passwordValid = true;
        } elseif ($user['password'] === $currentPassword) {
            // Hỗ trợ user cũ với password plaintext
            $passwordValid = true;
        }
        
        if (!$passwordValid) {
            $errors[] = 'Mật khẩu hiện tại không đúng';
        }
        
        // Validate new password
        if (empty($newPassword)) {
            $errors[] = 'Mật khẩu mới không được để trống';
        } elseif (strlen($newPassword) < 5) {
            $errors[] = 'Mật khẩu mới phải có ít nhất 5 ký tự';
        }
        
        // Validate confirm password
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Mật khẩu xác nhận không khớp';
        }
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: /iso2/profile.php?action=edit');
            exit;
        }
        
        // Cập nhật mật khẩu
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        if ($this->userModel->updatePassword($userId, $hashedPassword)) {
            $_SESSION['success'] = 'Thay đổi mật khẩu thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi khi thay đổi mật khẩu';
        }
        
        header('Location: /iso2/profile.php');
        exit;
    }
}
