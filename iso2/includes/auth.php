<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/User.php';

if (!function_exists('startUserSession')) {
    function startUserSession(array $user): void {
        $_SESSION['user_id'] = $user['stt'];
        $_SESSION['user_stt'] = $user['stt'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_name'] = $user['username'];
        $_SESSION['user_email'] = isset($user['email']) ? $user['email'] : '';
        $_SESSION['role'] = isset($user['role']) ? $user['role'] : 'user';
        $_SESSION['nhom'] = isset($user['nhom']) && !empty($user['nhom']) ? $user['nhom'] : 'RDNGA';
    }
}

if (!function_exists('setRememberCookie')) {
    function setRememberCookie(string $value, int $expires): void {
        setcookie('remember_me', $value, [
            'expires' => $expires,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

if (!function_exists('issueRememberToken')) {
    function issueRememberToken(array $user): void {
        $token = bin2hex(random_bytes(32));
        $userModel = new User();
        if ($userModel->saveRememberToken((int)$user['stt'], $token)) {
            setRememberCookie($token, time() + 30 * 24 * 60 * 60);
        }
    }
}

if (!function_exists('restoreRememberedLogin')) {
    // ÄÄƒng nháº­p láº¡i tá»« cookie "ghi nhá»› Ä‘Äƒng nháº­p" khi session Ä‘Ã£ háº¿t háº¡n
    function restoreRememberedLogin(): bool {
        $token = $_COOKIE['remember_me'] ?? '';
        if (!is_string($token) || $token === '' || session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        
        $userModel = new User();
        $user = $userModel->findByRememberToken($token);
        if (!$user) {
            setRememberCookie('', time() - 3600);
            return false;
        }
        
        if (!headers_sent()) { session_regenerate_id(true); }
        startUserSession($user);
        return true;
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool {
        if (isset($_SESSION['user_id'])) {
            return true;
        }
        return restoreRememberedLogin();
    }
}

if (!function_exists('getHomeUrl')) {
    // Trang chính sau đăng nhập: hososcbd.php nếu có quyền xem, ngược lại kehoachbaoduongdinhky.php
    function getHomeUrl(): string {
        if (function_exists('hasPermission') && hasPermission('hososcbd.view')) {
            return appUrl('hososcbd.php');
        }
        return appUrl('kehoachbaoduongdinhky.php');
    }
}

if (!function_exists('getCurrentUser')) {
    function getCurrentUser(): array|false|null {
        if (!isLoggedIn()) return null;
        $userModel = new User();
        return $userModel->find($_SESSION['user_id']);
    }
}

if (!function_exists('login')) {
    function login(string $username, string $password): bool {
        $userModel = new User();
        $user = $userModel->findByUsername($username);
        
        if ($user) {
            // Kiá»ƒm tra password - há»— trá»£ cáº£ plaintext (user cÅ©) vÃ  hashed (user má»›i)
            $passwordValid = password_verify($password, $user['password']) || $user['password'] === $password;
            
            if ($passwordValid) {
                if (!headers_sent()) { session_regenerate_id(true); }
                startUserSession($user);
                return true;
            }
        }
        
        return false;
    }
}

if (!function_exists('logout')) {
    function logout(): void {
        // XÃ³a token ghi nhá»› ká»ƒ cáº£ khi session Ä‘Ã£ háº¿t háº¡n
        $token = $_COOKIE['remember_me'] ?? '';
        if (is_string($token) && $token !== '') {
            $userModel = new User();
            $user = $userModel->findByRememberToken($token);
            if ($user) {
                $userModel->clearRememberToken((int)$user['stt']);
            }
            setRememberCookie('', time() - 3600);
        }
        session_destroy();
    }
}

if (!function_exists('requireAuth')) {
    function requireAuth(): void {
        if (!isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
}

if (!function_exists('getUserNhom')) {
    /**
     * Láº¥y nhÃ³m cá»§a user hiá»‡n táº¡i (RDNGA hoáº·c CNC)
     * @return string NhÃ³m cá»§a user, máº·c Ä‘á»‹nh lÃ  RDNGA
     */
    function getUserNhom(): string {
        return $_SESSION['nhom'] ?? 'RDNGA';
    }
}
