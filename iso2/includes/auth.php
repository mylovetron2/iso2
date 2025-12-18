<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/User.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function getCurrentUser(): array|false|null {
    if (!isLoggedIn()) return null;
    $userModel = new User();
    return $userModel->find($_SESSION['user_id']);
}

function login(string $username, string $password): bool {
    $userModel = new User();
    $user = $userModel->findByUsername($username);
    
    if ($user) {
        // Kiểm tra password - hỗ trợ cả plaintext (user cũ) và hashed (user mới)
        $passwordValid = false;
        
        // Thử verify với password_hash trước
        if (password_verify($password, $user['password'])) {
            $passwordValid = true;
        }
        // Nếu không match, thử so sánh trực tiếp (cho user cũ)
        elseif ($user['password'] === $password) {
            $passwordValid = true;
        }
        
        if ($passwordValid) {
            $_SESSION['user_id'] = $user['stt'];
            $_SESSION['user_stt'] = $user['stt'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['username'];
            $_SESSION['user_email'] = isset($user['email']) ? $user['email'] : '';
            $_SESSION['role'] = isset($user['role']) ? $user['role'] : 'user';
            return true;
        }
    }
    
    return false;
}

function logout(): void {
    session_destroy();
}

function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
