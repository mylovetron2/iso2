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
    if ($user && $user['password'] === $password) {
        $_SESSION['user_id'] = $user['stt'];
        $_SESSION['user_name'] = $user['username'];
        $_SESSION['user_email'] = isset($user['email']) ? $user['email'] : '';
        return true;
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
