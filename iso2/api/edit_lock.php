<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/permissions.php';

// Chỉ cho phép người có quyền kehoachbaoduong.edit
if (!hasPermission('kehoachbaoduong.edit')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền']);
    exit;
}

$lockFile = __DIR__ . '/../storage/kehoachbd_edit_lock.txt';

// Đảm bảo thư mục tồn tại
if (!is_dir(__DIR__ . '/../storage')) {
    mkdir(__DIR__ . '/../storage', 0755, true);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Trả về trạng thái hiện tại
    $locked = true;
    if (file_exists($lockFile)) {
        $content = trim(file_get_contents($lockFile));
        $locked = ($content !== 'unlocked');
    }
    echo json_encode(['success' => true, 'locked' => $locked]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $password = $input['password'] ?? '';

    // Mật khẩu lưu trong file config hoặc hardcode ở đây
    // Admin có thể thay đổi tại đây:
    $correctPassword = 'iso2@lock';

    if ($action === 'unlock') {
        if ($password !== $correctPassword) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu không đúng']);
            exit;
        }
        file_put_contents($lockFile, 'unlocked');
        echo json_encode(['success' => true, 'locked' => false]);
        exit;
    }

    if ($action === 'lock') {
        file_put_contents($lockFile, 'locked');
        echo json_encode(['success' => true, 'locked' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method không được hỗ trợ']);
