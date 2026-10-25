<?php
declare(strict_types=1);

session_start();
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/backfill_kehoach_hososcbd.php';

requireAuth();
if (!hasRole(ROLE_ADMIN)) {
    http_response_code(403);
    exit('Chỉ quản trị viên được chạy công cụ này.');
}

if (!isset($_SESSION['backfill_hoso_csrf'])) {
    $_SESSION['backfill_hoso_csrf'] = bin2hex(random_bytes(32));
}
$yearInput = (string)date('Y');
$records = [];
$summary = null;
$errorMessage = null;
$confirmation = null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method not allowed.');
}
if ($method === 'POST') {
    try {
        $csrf = $_POST['csrf'] ?? null;
        if (!is_string($csrf) || !hash_equals($_SESSION['backfill_hoso_csrf'], $csrf)) {
            http_response_code(403);
            throw new InvalidArgumentException('Phiên xác nhận không hợp lệ. Hãy tải lại trang.');
        }
        $input = $_POST['year'] ?? null;
        if (!is_string($input) || !preg_match('/^[1-9][0-9]{3}$/D', $input) || (int)$input > 9998) {
            throw new InvalidArgumentException('Năm phải nằm trong khoảng 1000–9998.');
        }
        $yearInput = $input;
        $action = $_POST['action'] ?? null;
        if (!in_array($action, ['preview', 'apply'], true)) {
            throw new InvalidArgumentException('Thao tác không hợp lệ.');
        }
        $preview = $_SESSION['backfill_hoso_preview'] ?? null;
        unset($_SESSION['backfill_hoso_preview']);
        if ($action === 'apply') {
            $token = $_POST['confirmation'] ?? null;
            if (!is_array($preview) || $preview['year'] !== (int)$input
                || $preview['expires'] < time() || !is_string($token)
                || !hash_equals($preview['token'], $token)
                || ($_POST['confirm_backup'] ?? null) !== '1') {
                throw new InvalidArgumentException('Cần xem trước lại và xác nhận đã sao lưu trước khi cập nhật.');
            }
        }
        require_once __DIR__ . '/config/database.php';
        $summary = backfillHoSoRun(getDBConnection(), $action === 'apply', (int)$input, static function (array $record) use (&$records): void {
            $records[] = $record;
        });
        if ($action === 'preview' && $summary['matched'] > 0) {
            $confirmation = bin2hex(random_bytes(32));
            $_SESSION['backfill_hoso_preview'] = [
                'year' => (int)$input, 'token' => $confirmation, 'expires' => time() + 600,
            ];
        }
        if ($action === 'apply') {
            error_log('Backfill hososcbd: ' . json_encode([
                'user_id' => $_SESSION['user_id'], 'year' => (int)$input, 'summary' => $summary,
            ], JSON_THROW_ON_ERROR));
        }
    } catch (InvalidArgumentException $error) {
        if (http_response_code() !== 403) {
            http_response_code(400);
        }
        $errorMessage = $error->getMessage();
        error_log('Backfill hososcbd rejected: ' . $error->getMessage());
    } catch (Throwable $error) {
        http_response_code(500);
        error_log('Backfill hososcbd failed: ' . $error->getMessage());
        $errorMessage = 'Không hoàn tất xử lý. Không có xác nhận commit thành công; xem PHP error log trước khi chạy lại.';
        $records = [];
    }
}
$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><title>Liên kết hồ sơ bảo dưỡng cũ</title></head>
<body>
<h1>Liên kết hồ sơ bảo dưỡng cũ</h1>
<p>Chỉ CNM/RDNGA; khớp mã vật tư + số máy, không lọc loại công việc cv, ngày bắt đầu thuộc quý đã hoàn tất.
Chọn hồ sơ mới nhất; không ghi đè liên kết hoặc tạo hồ sơ mới.</p>
<p><strong>Sao lưu DB trước khi cập nhật.</strong> Xem trước không thay đổi dữ liệu.
Khi xác nhận, dữ liệu được đối chiếu lại với DB hiện tại nên có thể khác bản xem trước.
Chỉ kết quả <code>committed</code> xác nhận đã lưu.</p>
<?php if ($errorMessage !== null): ?>
<p role="alert"><?= $escape($errorMessage) ?></p>
<?php endif; ?>
<form method="post">
    <input type="hidden" name="csrf" value="<?= $escape($_SESSION['backfill_hoso_csrf']) ?>">
    <label>Năm kế hoạch <input type="number" name="year" min="1000" max="9998" required value="<?= $escape($yearInput) ?>"></label>
    <button name="action" value="preview" type="submit">Xem trước — không cập nhật</button>
</form>
<?php if ($summary !== null): ?>
<h2>Kết quả</h2>
<pre><?= $escape(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)) ?></pre>
<table border="1">
<thead><tr><th>Kế hoạch</th><th>Năm</th><th>Số máy</th><th>Hồ sơ ID</th><th>Phiếu</th><th>Ngày bắt đầu</th><th>Trạng thái</th><th>Kế hoạch đang giữ hồ sơ</th></tr></thead>
<tbody>
<?php foreach ($records as $record): ?>
<tr>
<?php foreach (['plan_id', 'year', 'somay', 'dossier_id', 'phieu', 'start_date', 'status', 'owner_plan_id'] as $column): ?>
<td><?= $escape($record[$column] ?? '') ?></td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody></table>
<p>would_update: dự kiến gắn; pending_commit: thao tác trong transaction (chỉ đã lưu nếu tổng kết committed);
missing_device_identity: thiếu định danh; ambiguous_device_identity: trùng thiết bị;
no_matching_dossier: không có hồ sơ phù hợp; dossier_already_linked: hồ sơ đã được dùng.</p>
<?php endif; ?>
<?php if ($confirmation !== null): ?>
<h2>Xác nhận cập nhật năm <?= $escape($yearInput) ?></h2>
<form method="post">
    <input type="hidden" name="csrf" value="<?= $escape($_SESSION['backfill_hoso_csrf']) ?>">
    <input type="hidden" name="confirmation" value="<?= $escape($confirmation) ?>">
    <input type="hidden" name="year" value="<?= $escape($yearInput) ?>">
    <label><input type="checkbox" name="confirm_backup" value="1" required>Tôi đã sao lưu DB và kiểm tra kết quả xem trước.</label>
    <button name="action" value="apply" type="submit">Xác nhận cập nhật DB</button>
</form>
<p>Xác nhận có hiệu lực 10 phút và chỉ dùng một lần. Sau khi hoàn tất, nên gỡ trang công cụ khỏi hosting.</p>
<?php endif; ?>
</body></html>
