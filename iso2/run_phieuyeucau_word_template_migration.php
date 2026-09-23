<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

if (PHP_SAPI !== 'cli') {
    session_start();
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/includes/auth.php';
    require_once __DIR__ . '/includes/permissions.php';

    requireAuth();
    if (!hasRole(ROLE_ADMIN)) {
        http_response_code(403);
        exit('Bạn không có quyền chạy migration này.');
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ?>
        <!doctype html>
        <html lang="vi"><head><meta charset="UTF-8"><title>Khởi tạo mẫu Word Phiếu YC</title></head>
        <body style="font-family:Arial,sans-serif;max-width:680px;margin:48px auto;line-height:1.5">
            <h1>Khởi tạo chức năng mẫu Word Phiếu YC</h1>
            <p>Thao tác này tạo bảng <code>phieuyeucau_word_template</code>. Có thể chạy lại an toàn.</p>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button type="submit">Chạy migration</button>
            </form>
        </body></html>
        <?php
        exit;
    }
    if (!hash_equals((string)$_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
        http_response_code(419);
        exit('Phiên thao tác không hợp lệ. Vui lòng tải lại trang.');
    }
}

$sqlFile = __DIR__ . '/migrations/create_phieuyeucau_word_template.sql';
if (!is_file($sqlFile)) {
    exit("Không tìm thấy file migration: $sqlFile\n");
}

try {
    $pdo = getDBConnection();
    $pdo->exec('SET NAMES latin1');
    $pdo->exec((string)file_get_contents($sqlFile));
    if (PHP_SAPI === 'cli') {
        echo "Đã tạo bảng phieuyeucau_word_template.\n";
    } else {
        echo '<!doctype html><html lang="vi"><head><meta charset="UTF-8"><title>Hoàn tất</title></head><body style="font-family:Arial,sans-serif;max-width:680px;margin:48px auto;line-height:1.5"><h1>Hoàn tất</h1><p>Đã tạo bảng phieuyeucau_word_template.</p><p><a href="phieuyeucau_template.php">Tải lên mẫu Word Phiếu YC</a></p></body></html>';
    }
} catch (Throwable $exception) {
    exit('ERROR: ' . $exception->getMessage() . "\n");
}