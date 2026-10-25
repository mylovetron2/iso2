<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Trả về trạng thái hiện tại
    $locked = true;
    if (file_exists($lockFile)) {
        $content = @file_get_contents($lockFile);
        if ($content === false) {
            http_response_code(500);
            error_log('edit_lock: lock file is not readable');
            echo json_encode(['success' => false, 'message' => 'Không đọc được file khóa. Kiểm tra quyền thư mục storage.']);
            exit;
        }
        $locked = (trim($content) !== 'unlocked');
    }
    echo json_encode(['success' => true, 'locked' => $locked]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dữ liệu JSON không hợp lệ']);
        exit;
    }
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
    }

    if ($action === 'lock' || $action === 'unlock') {
        $storageDir = dirname($lockFile);
        if (!is_dir($storageDir) && !@mkdir($storageDir, 0755, true) && !is_dir($storageDir)) {
            http_response_code(500);
            error_log('edit_lock: cannot create storage directory');
            echo json_encode(['success' => false, 'message' => 'Không tạo được thư mục storage. Kiểm tra quyền ghi trên hosting.']);
            exit;
        }
        $state = $action === 'unlock' ? 'unlocked' : 'locked';
        if ((!file_exists($lockFile) && !is_writable($storageDir))
            || (file_exists($lockFile) && !is_writable($lockFile))
            || @file_put_contents($lockFile, $state, LOCK_EX) !== strlen($state)) {
            http_response_code(500);
            error_log('edit_lock: cannot write lock state');
            echo json_encode(['success' => false, 'message' => 'Không lưu được trạng thái khóa. Kiểm tra quyền ghi storage/kehoachbd_edit_lock.txt.']);
            exit;
        }
        echo json_encode(['success' => true, 'locked' => $action === 'lock']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method không được hỗ trợ']);
