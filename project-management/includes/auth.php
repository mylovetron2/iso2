<?php
require_once __DIR__ . '/../models/User.php';
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $userModel = new User();
    return $userModel->find($_SESSION['user_id']);
}
function login($username, $password) {
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
function logout() {
    session_destroy();
}
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
