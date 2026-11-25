<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/vattu_position_category_update.php';

requireAuth();
if (!hasPermission('vattu.edit')) {
    http_response_code(403);
    exit('Bạn không có quyền cập nhật vật tư.');
}

if (!isset($_SESSION['vattu_category_138_csrf'])) {
    $_SESSION['vattu_category_138_csrf'] = bin2hex(random_bytes(32));
}

$preview = null;
$confirmation = null;
$message = null;
$errorMessage = null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method not allowed.');
}

if ($method === 'POST') {
    try {
        $csrf = $_POST['csrf'] ?? null;
        if (!is_string($csrf) || !hash_equals($_SESSION['vattu_category_138_csrf'], $csrf)) {
            http_response_code(403);
            throw new InvalidArgumentException('Phiên làm việc không hợp lệ. Hãy tải lại trang.');
        }

        $action = $_POST['action'] ?? null;
        if ($action === 'preview') {
            unset($_SESSION['vattu_category_138_preview']);
            $db = getDBConnection();
            $preview = vattuPositionCategoryPreview($db);
            $willUpdate = count(array_filter(
                $preview['items'],
                static fn(array $item): bool => $item['status'] === 'update'
            ));
            if ($willUpdate > 0) {
                $confirmation = bin2hex(random_bytes(32));
                $_SESSION['vattu_category_138_preview'] = [
                    'token' => $confirmation,
                    'expires' => time() + 600,
                    'data' => $preview,
                    'fingerprint' => vattuPositionCategoryFingerprint($preview),
                ];
            } else {
                $message = 'Không có vật tư nào cần đổi phân loại.';
            }
        } elseif ($action === 'apply') {
            $state = $_SESSION['vattu_category_138_preview'] ?? null;
            $token = $_POST['confirmation'] ?? null;
            if (!is_array($state) || $state['expires'] < time() || !is_string($token)
                || !hash_equals($state['token'], $token)
                || ($_POST['confirm_backup'] ?? null) !== '1') {
                throw new InvalidArgumentException('Cần xem trước lại và xác nhận đã sao lưu trước khi cập nhật.');
            }
            unset($_SESSION['vattu_category_138_preview']);

            $db = getDBConnection();
            $db->beginTransaction();
            $preview = vattuPositionCategoryPreview($db, true);
            if (!hash_equals($state['fingerprint'], vattuPositionCategoryFingerprint($preview))) {
                throw new InvalidArgumentException('Dữ liệu đã thay đổi từ lúc xem trước. Hãy xem trước lại.');
            }
            $updated = vattuPositionCategoryApply($db, $preview);
            $db->commit();
            $message = "Đã cập nhật phân loại cho {$updated} vật tư.";
            error_log('Vat tu category position update: ' . json_encode([
                'user_id' => $_SESSION['user_id'],
                'updated' => $updated,
                'position_min' => 138,
                'category_id' => $preview['category_id'],
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
        if ($action === 'apply') {
            unset($_SESSION['vattu_category_138_preview']);
        }
    } catch (Throwable $error) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        error_log('Vat tu category position update failed: ' . $error->getMessage());
        $errorMessage = 'Không hoàn tất cập nhật. Hãy kiểm tra PHP error log trước khi chạy lại.';
        $preview = null;
        $confirmation = null;
        unset($_SESSION['vattu_category_138_preview']);
    }
}

$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><title>Cập nhật phân loại vật tư</title></head>
<body>
<h1>Cập nhật phân loại vật tư</h1>
<p>Đổi phân loại thành <strong>Công cụ dụng cụ</strong> cho vật tư có vị trí sắp xếp
<strong>từ 138 trở lên</strong>. Các vật tư đã có phân loại này sẽ giữ nguyên.</p>
<p><strong>Sao lưu DB trước khi cập nhật.</strong> Xem trước không ghi dữ liệu.
Sau khi xác nhận, chương trình sẽ kiểm tra lại trong transaction.</p>
<?php if ($errorMessage !== null): ?><p role="alert"><?= $escape($errorMessage) ?></p><?php endif; ?>
<?php if ($message !== null): ?><p role="status"><?= $escape($message) ?></p><?php endif; ?>
<form method="post">
    <input type="hidden" name="csrf" value="<?= $escape($_SESSION['vattu_category_138_csrf']) ?>">
    <button type="submit" name="action" value="preview">Xem trước danh sách</button>
</form>
<?php if ($preview !== null): ?>
    <?php
    $updates = array_filter($preview['items'], static fn(array $item): bool => $item['status'] === 'update');
    $unchanged = count($preview['items']) - count($updates);
    ?>
    <h2>Xem trước: <?= count($updates) ?> vật tư sẽ đổi phân loại</h2>
    <p>Đã đúng phân loại: <?= $unchanged ?>; tổng vị trí sắp xếp từ 138: <?= count($preview['items']) ?>.</p>
    <?php if ($updates !== []): ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead><tr><th>STT</th><th>Mã vật tư</th><th>Vị trí sắp xếp</th><th>Phân loại cũ</th><th>Phân loại mới</th></tr></thead>
        <tbody>
        <?php foreach ($updates as $item): ?>
            <tr>
                <td><?= $escape($item['stt']) ?></td>
                <td><?= $escape($item['mavattu']) ?></td>
                <td><?= $escape($item['vi_tri_sap_xep']) ?></td>
                <td><?= $escape($item['phanloai_id'] ?? 'NULL') ?></td>
                <td>Công cụ dụng cụ</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
<?php endif; ?>
<?php if ($confirmation !== null): ?>
    <h2>Xác nhận cập nhật</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $escape($_SESSION['vattu_category_138_csrf']) ?>">
        <input type="hidden" name="confirmation" value="<?= $escape($confirmation) ?>">
        <label><input type="checkbox" name="confirm_backup" value="1" required>
            Tôi đã sao lưu DB và kiểm tra danh sách trên.</label>
        <button type="submit" name="action" value="apply">Xác nhận cập nhật</button>
    </form>
<?php endif; ?>
<p><a href="vattuthanhly.php">Quay lại danh sách vật tư</a></p>
</body>
</html>
