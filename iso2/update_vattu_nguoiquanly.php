<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/vattu_nguoiquanly_update.php';

requireAuth();
if (!hasPermission('vattu.edit')) {
    http_response_code(403);
    exit('Bạn không có quyền cập nhật vật tư.');
}

if (!isset($_SESSION['vattu_nguoiquanly_csrf'])) {
    $_SESSION['vattu_nguoiquanly_csrf'] = bin2hex(random_bytes(32));
}

$preview = null;
$confirmation = null;
$message = null;
$errorMessage = null;
$action = null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method not allowed.');
}

if ($method === 'POST') {
    try {
        $csrf = $_POST['csrf'] ?? null;
        if (!is_string($csrf) || !hash_equals($_SESSION['vattu_nguoiquanly_csrf'], $csrf)) {
            http_response_code(403);
            throw new InvalidArgumentException('Phiên làm việc không hợp lệ. Hãy tải lại trang.');
        }

        $action = $_POST['action'] ?? null;
        if ($action === 'preview') {
            unset($_SESSION['vattu_nguoiquanly_preview']);
            $db = getDBConnection();
            $preview = vattuNguoiQuanLyPreview($db);
            if ($preview['items'] !== []) {
                $confirmation = bin2hex(random_bytes(32));
                $_SESSION['vattu_nguoiquanly_preview'] = [
                    'token' => $confirmation,
                    'expires' => time() + 600,
                    'fingerprint' => vattuNguoiQuanLyFingerprint($preview),
                ];
            } else {
                $message = 'Không có dòng nào cần cập nhật người quản lý.';
            }
        } elseif ($action === 'apply') {
            $state = $_SESSION['vattu_nguoiquanly_preview'] ?? null;
            $token = $_POST['confirmation'] ?? null;
            if (!is_array($state) || $state['expires'] < time() || !is_string($token)
                || !hash_equals($state['token'], $token)
                || ($_POST['confirm_backup'] ?? null) !== '1') {
                throw new InvalidArgumentException('Cần xem trước lại và xác nhận đã sao lưu trước khi cập nhật.');
            }
            unset($_SESSION['vattu_nguoiquanly_preview']);

            $db = getDBConnection();
            $db->beginTransaction();
            $preview = vattuNguoiQuanLyPreview($db, true);
            if (!hash_equals($state['fingerprint'], vattuNguoiQuanLyFingerprint($preview))) {
                throw new InvalidArgumentException('Dữ liệu đã thay đổi từ lúc xem trước. Hãy xem trước lại.');
            }
            $updated = vattuNguoiQuanLyApply($db, $preview);
            $db->commit();
            $message = "Đã cập nhật người quản lý cho {$updated} vật tư.";
            $preview = null;
            error_log('Vat tu nguoiquanly update: ' . json_encode([
                'user_id' => $_SESSION['user_id'] ?? null,
                'updated' => $updated,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        } else {
            throw new InvalidArgumentException('Thao tác không hợp lệ.');
        }
    } catch (InvalidArgumentException $error) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        if (http_response_code() !== 403) {
            http_response_code(400);
        }
        $errorMessage = $error->getMessage();
        $preview = null;
        $confirmation = null;
        if ($action === 'apply') {
            unset($_SESSION['vattu_nguoiquanly_preview']);
        }
    } catch (Throwable $error) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        error_log('Vat tu nguoiquanly update failed: ' . $error->getMessage());
        $errorMessage = 'Không hoàn tất cập nhật. Hãy kiểm tra PHP error log trước khi chạy lại.';
        $preview = null;
        $confirmation = null;
        unset($_SESSION['vattu_nguoiquanly_preview']);
    }
}

$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><title>Cập nhật người quản lý vật tư</title></head>
<body>
<h1>Cập nhật người quản lý vật tư</h1>
<p>Chuẩn hóa cột <strong>Người quản lý</strong> theo bảng quy đổi: mọi tên có trong giá trị được đổi thành tên đầy đủ
và <strong>mọi thông tin khác (mã thiết bị, năm, số...) bị bỏ</strong>, chỉ còn tên
(ví dụ <em>Sang SGH521SPHW Đỗ Thành Luân SGH543SRW6</em> &rarr; <em>Trương Ngọc Sang, Đỗ Thành Luân</em>).
Nên chạy script copy sang ghi chú trước để không mất thông tin:</p>
<ul>
<?php foreach (vattuNguoiQuanLyMap() as $target => $aliases): ?>
    <li><?= $escape(implode(', ', $aliases)) ?> &rarr; <strong><?= $escape($target) ?></strong></li>
<?php endforeach; ?>
</ul>
<p><strong>Sao lưu DB trước khi cập nhật.</strong> Xem trước không ghi dữ liệu.
Sau khi xác nhận, chương trình sẽ kiểm tra lại trong transaction.</p>
<?php if ($errorMessage !== null): ?><p role="alert"><?= $escape($errorMessage) ?></p><?php endif; ?>
<?php if ($message !== null): ?><p role="status"><?= $escape($message) ?></p><?php endif; ?>
<form method="post">
    <input type="hidden" name="csrf" value="<?= $escape($_SESSION['vattu_nguoiquanly_csrf']) ?>">
    <button type="submit" name="action" value="preview">Xem trước danh sách</button>
</form>
<?php if ($preview !== null): ?>
    <h2>Xem trước: <?= count($preview['items']) ?> vật tư sẽ đổi người quản lý</h2>
    <?php if ($preview['items'] !== []): ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead><tr><th>STT</th><th>Mã vật tư</th><th>Người quản lý cũ</th><th>Người quản lý mới</th></tr></thead>
        <tbody>
        <?php foreach ($preview['items'] as $item): ?>
            <tr>
                <td><?= $escape($item['stt']) ?></td>
                <td><?= $escape($item['mavattu']) ?></td>
                <td><?= $escape($item['old']) ?></td>
                <td><?= $escape($item['new']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <?php if ($preview['unmapped'] !== []): ?>
    <h3>Giá trị không nằm trong bảng quy đổi (giữ nguyên)</h3>
    <ul>
    <?php foreach ($preview['unmapped'] as $value => $count): ?>
        <li><?= $escape($value) ?> (<?= (int)$count ?>)</li>
    <?php endforeach; ?>
    </ul>
    <?php endif; ?>
<?php endif; ?>
<?php if ($confirmation !== null): ?>
    <h2>Xác nhận cập nhật</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $escape($_SESSION['vattu_nguoiquanly_csrf']) ?>">
        <input type="hidden" name="confirmation" value="<?= $escape($confirmation) ?>">
        <label><input type="checkbox" name="confirm_backup" value="1" required>
            Tôi đã sao lưu DB và kiểm tra danh sách trên.</label>
        <button type="submit" name="action" value="apply">Xác nhận cập nhật</button>
    </form>
<?php endif; ?>
<p><a href="vattuthanhly.php">Quay lại danh sách vật tư</a></p>
</body>
</html>
