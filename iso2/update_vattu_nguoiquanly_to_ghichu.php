<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/vattu_nguoiquanly_to_ghichu.php';

requireAuth();
if (!hasPermission('vattu.edit')) {
    http_response_code(403);
    exit('Bạn không có quyền cập nhật vật tư.');
}

if (!isset($_SESSION['vattu_nql_ghichu_csrf'])) {
    $_SESSION['vattu_nql_ghichu_csrf'] = bin2hex(random_bytes(32));
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
        if (!is_string($csrf) || !hash_equals($_SESSION['vattu_nql_ghichu_csrf'], $csrf)) {
            http_response_code(403);
            throw new InvalidArgumentException('Phiên làm việc không hợp lệ. Hãy tải lại trang.');
        }

        $action = $_POST['action'] ?? null;
        if ($action === 'preview') {
            unset($_SESSION['vattu_nql_ghichu_preview']);
            $db = getDBConnection();
            $preview = vattuNguoiQuanLyGhiChuPreview($db);
            if ($preview['items'] !== []) {
                $confirmation = bin2hex(random_bytes(32));
                $_SESSION['vattu_nql_ghichu_preview'] = [
                    'token' => $confirmation,
                    'expires' => time() + 600,
                    'fingerprint' => vattuNguoiQuanLyGhiChuFingerprint($preview),
                ];
            } else {
                $message = 'Không có dòng nào cần copy sang ghi chú.';
            }
        } elseif ($action === 'apply') {
            $state = $_SESSION['vattu_nql_ghichu_preview'] ?? null;
            $token = $_POST['confirmation'] ?? null;
            if (!is_array($state) || $state['expires'] < time() || !is_string($token)
                || !hash_equals($state['token'], $token)
                || ($_POST['confirm_backup'] ?? null) !== '1') {
                throw new InvalidArgumentException('Cần xem trước lại và xác nhận đã sao lưu trước khi cập nhật.');
            }
            unset($_SESSION['vattu_nql_ghichu_preview']);

            $db = getDBConnection();
            $db->beginTransaction();
            $preview = vattuNguoiQuanLyGhiChuPreview($db, true);
            if (!hash_equals($state['fingerprint'], vattuNguoiQuanLyGhiChuFingerprint($preview))) {
                throw new InvalidArgumentException('Dữ liệu đã thay đổi từ lúc xem trước. Hãy xem trước lại.');
            }
            $updated = vattuNguoiQuanLyGhiChuApply($db, $preview);
            $db->commit();
            $message = "Đã copy người quản lý sang ghi chú cho {$updated} vật tư.";
            $preview = null;
            error_log('Vat tu nguoiquanly to ghichu: ' . json_encode([
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
            unset($_SESSION['vattu_nql_ghichu_preview']);
        }
    } catch (Throwable $error) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        error_log('Vat tu nguoiquanly to ghichu failed: ' . $error->getMessage());
        $errorMessage = 'Không hoàn tất cập nhật. Hãy kiểm tra PHP error log trước khi chạy lại.';
        $preview = null;
        $confirmation = null;
        unset($_SESSION['vattu_nql_ghichu_preview']);
    }
}

$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><title>Copy người quản lý sang ghi chú</title></head>
<body>
<h1>Copy người quản lý sang ghi chú</h1>
<p>Copy giá trị cột <strong>Người quản lý</strong> (nguoiquanly) sang cột <strong>Ghi chú</strong> (ghichu).
Ghi chú đang trống sẽ được gán bằng giá trị này; ghi chú đã có dữ liệu sẽ được nối thêm vào cuối
(<code>ghi chú; người quản lý</code>). Dòng đã có giá trị này ở cuối ghi chú sẽ bị bỏ qua nên chạy lại không bị nhân đôi.</p>
<p><strong>Chạy script này TRƯỚC khi chạy script chuẩn hóa tên người quản lý</strong>
nếu muốn giữ giá trị gốc. <strong>Sao lưu DB trước khi cập nhật.</strong> Xem trước không ghi dữ liệu.</p>
<?php if ($errorMessage !== null): ?><p role="alert"><?= $escape($errorMessage) ?></p><?php endif; ?>
<?php if ($message !== null): ?><p role="status"><?= $escape($message) ?></p><?php endif; ?>
<form method="post">
    <input type="hidden" name="csrf" value="<?= $escape($_SESSION['vattu_nql_ghichu_csrf']) ?>">
    <button type="submit" name="action" value="preview">Xem trước danh sách</button>
</form>
<?php if ($preview !== null): ?>
    <h2>Xem trước: <?= count($preview['items']) ?> vật tư sẽ cập nhật ghi chú</h2>
    <p>Bỏ qua (ghi chú đã có giá trị này): <?= (int)$preview['skipped'] ?></p>
    <?php if ($preview['too_long'] !== []): ?>
    <p role="alert">Các dòng sau bị bỏ qua vì ghi chú mới vượt quá <?= (int)$preview['max_length'] ?> ký tự:</p>
    <ul>
    <?php foreach ($preview['too_long'] as $item): ?>
        <li>STT <?= $escape($item['stt']) ?> (<?= $escape($item['mavattu']) ?>): <?= (int)$item['length'] ?> ký tự</li>
    <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <?php if ($preview['items'] !== []): ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead><tr><th>STT</th><th>Mã vật tư</th><th>Người quản lý</th><th>Ghi chú cũ</th><th>Ghi chú mới</th></tr></thead>
        <tbody>
        <?php foreach ($preview['items'] as $item): ?>
            <tr>
                <td><?= $escape($item['stt']) ?></td>
                <td><?= $escape($item['mavattu']) ?></td>
                <td><?= $escape($item['nguoiquanly']) ?></td>
                <td><?= $escape($item['old'] ?? '') ?></td>
                <td><?= $escape($item['new']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
<?php endif; ?>
<?php if ($confirmation !== null): ?>
    <h2>Xác nhận cập nhật</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $escape($_SESSION['vattu_nql_ghichu_csrf']) ?>">
        <input type="hidden" name="confirmation" value="<?= $escape($confirmation) ?>">
        <label><input type="checkbox" name="confirm_backup" value="1" required>
            Tôi đã sao lưu DB và kiểm tra danh sách trên.</label>
        <button type="submit" name="action" value="apply">Xác nhận cập nhật</button>
    </form>
<?php endif; ?>
<p><a href="vattuthanhly.php">Quay lại danh sách vật tư</a></p>
</body>
</html>
