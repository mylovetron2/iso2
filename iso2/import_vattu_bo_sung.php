<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';

requireAuth();
if (!hasPermission('vattu.edit')) {
    http_response_code(403);
    exit('Bạn không có quyền cập nhật vật tư.');
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/vattu_import_missing.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_SESSION['vattu_missing_csrf'])) {
    $_SESSION['vattu_missing_csrf'] = bin2hex(random_bytes(32));
}

$preview = [];
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
        if (!is_string($csrf) || !hash_equals($_SESSION['vattu_missing_csrf'], $csrf)) {
            http_response_code(403);
            throw new InvalidArgumentException('Phiên làm việc không hợp lệ. Hãy tải lại trang.');
        }

        $action = $_POST['action'] ?? null;
        if ($action === 'preview') {
            unset($_SESSION['vattu_missing_preview']);
            $upload = $_FILES['excel_file'] ?? null;
            if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new InvalidArgumentException('Không tải được file. Hãy chọn lại file Excel.');
            }
            if (($upload['size'] ?? 0) > 10 * 1024 * 1024) {
                throw new InvalidArgumentException('File không được vượt quá 10 MB.');
            }
            $extension = strtolower(pathinfo((string)$upload['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ['xlsx', 'xls'], true)) {
                throw new InvalidArgumentException('Chỉ chấp nhận file .xlsx hoặc .xls.');
            }
            $type = IOFactory::identify((string)$upload['tmp_name'], ['Xlsx', 'Xls']);
            $reader = IOFactory::createReader($type);
            $spreadsheet = $reader->load((string)$upload['tmp_name']);
            try {
                $rows = vattuMissingReadRows($spreadsheet->getActiveSheet());
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
            $db = getDBConnection();
            $preview = vattuMissingPreview($db, $rows);
            $changes = count(array_filter($preview, static fn(array $row): bool => $row['status'] === 'update'));
            if ($changes > 0) {
                $confirmation = bin2hex(random_bytes(32));
                $_SESSION['vattu_missing_preview'] = [
                    'token' => $confirmation,
                    'expires' => time() + 600,
                    'version' => 4,
                    'rows' => $rows,
                    'fingerprint' => vattuMissingFingerprint($preview),
                ];
            } else {
                $message = 'Không có vật tư nào cần bổ sung.';
            }
        } elseif ($action === 'apply') {
            $state = $_SESSION['vattu_missing_preview'] ?? null;
            $token = $_POST['confirmation'] ?? null;
            if (!is_array($state) || ($state['version'] ?? null) !== 4
                || $state['expires'] < time() || !is_string($token)
                || !hash_equals($state['token'], $token) || ($_POST['confirm_backup'] ?? null) !== '1') {
                throw new InvalidArgumentException('Cần xem trước lại và xác nhận đã sao lưu trước khi cập nhật.');
            }
            unset($_SESSION['vattu_missing_preview']);
            $db = getDBConnection();
            $db->beginTransaction();
            $preview = vattuMissingPreview($db, $state['rows'], true);
            if (!hash_equals($state['fingerprint'], vattuMissingFingerprint($preview))) {
                throw new InvalidArgumentException('Dữ liệu đã thay đổi từ lúc xem trước. Hãy tải file và xem trước lại.');
            }
            $updated = vattuMissingApply($db, $preview);
            $db->commit();
            $message = "Đã cập nhật {$updated} vật tư.";
            error_log('Vat tu missing fields import: ' . json_encode([
                'user_id' => $_SESSION['user_id'], 'updated' => $updated,
            ], JSON_THROW_ON_ERROR));
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
        $preview = [];
    } catch (Throwable $error) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        error_log('Vat tu missing fields import failed: ' . $error->getMessage());
        $errorMessage = 'Không hoàn tất xử lý. Hãy kiểm tra PHP error log trước khi chạy lại.';
        $preview = [];
    }
}

$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$statusLabels = [
    'update' => 'Sẽ bổ sung',
    'no_change' => 'Không có trường cần bổ sung',
    'not_found' => 'Không tìm thấy mã',
    'duplicate_db' => 'Mã trùng trong DB',
    'duplicate_file' => 'Mã trùng trong file',
    'missing_code' => 'Thiếu mã',
    'invalid' => 'Dòng không hợp lệ',
];
?>
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><title>Import bổ sung thông tin vật tư</title></head>
<body>
<h1>Import bổ sung thông tin vật tư</h1>
<p>File theo mẫu: mã vật tư ở cột A, tên tiếng Anh ở C, đặc tính kỹ thuật Nga/Việt ở D/E,
đơn vị tính Nga/Việt ở F/G và phân loại ở H. Dữ liệu bắt đầu từ dòng 2;
cột B không được cập nhật.</p>
<p>Chỉ bổ sung các trường khác khi đang NULL hoặc trống; ô Excel trống không thay đổi dữ liệu.
Phân loại được khớp theo mã hoặc tên và cập nhật cả khi đã có giá trị,
chỉ đối với vật tư có vị trí sắp xếp hiện tại từ 138 trở lên.
Mã không có hoặc bị trùng sẽ được bỏ qua; phân loại không hợp lệ được báo và bỏ qua riêng.</p>
<p><strong>Sao lưu DB trước khi cập nhật.</strong> Xem trước không ghi dữ liệu;
chương trình sẽ kiểm tra lại dữ liệu trước khi cập nhật.</p>
<?php if ($errorMessage !== null): ?><p role="alert"><?= $escape($errorMessage) ?></p><?php endif; ?>
<?php if ($message !== null): ?><p role="status"><?= $escape($message) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= $escape($_SESSION['vattu_missing_csrf']) ?>">
    <input type="file" name="excel_file" accept=".xlsx,.xls" required>
    <button type="submit" name="action" value="preview">Tải file và xem trước</button>
</form>
<?php if ($preview !== []): ?>
    <?php
    $updates = array_filter($preview, static fn(array $entry): bool => $entry['status'] === 'update');
    $counts = array_count_values(array_column($preview, 'status'));
    $issues = array_filter($preview, static fn(array $entry): bool =>
        !in_array($entry['status'], ['update', 'no_change'], true) || $entry['message'] !== null
    );
    ?>
    <h2>Danh sách xem trước</h2>
    <p>Sẽ bổ sung: <?= count($updates) ?>; không thay đổi: <?= $counts['no_change'] ?? 0 ?>;
    các dòng còn lại: <?= count($preview) - count($updates) - ($counts['no_change'] ?? 0) ?>.</p>
    <?php if ($updates !== []): ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead><tr><th>Dòng</th><th>Mã vật tư</th><th>Trạng thái</th>
        <?php foreach (VATTU_MISSING_COLUMNS as [, $label]): ?>
            <th><?= $escape($label) ?></th>
        <?php endforeach; ?>
        </tr></thead>
        <tbody>
        <?php foreach ($updates as $entry): ?>
            <tr>
                <td><?= $escape($entry['row']) ?></td>
                <td><?= $escape($entry['code'] ?? '') ?></td>
                <td><?= $escape($statusLabels[$entry['status']]) ?>
                    <?php if ($entry['message'] !== null): ?><br><?= $escape($entry['message']) ?><?php endif; ?>
                </td>
                <?php foreach (VATTU_MISSING_COLUMNS as $field => $definition): ?>
                    <td>
                        <?php if (isset($entry['changes'][$field])): ?>
                            <?php if ($field === 'phanloai_id' && $entry['old'][$field] !== null
                                && vattuMissingTrim((string)$entry['old'][$field]) !== ''): ?>
                                ID cũ: <?= $escape($entry['old'][$field]) ?> →
                            <?php else: ?>Bổ sung: <?php endif; ?>
                            <?= nl2br($escape($entry['changes'][$field]['display'])) ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <?php if ($issues !== []): ?>
    <details>
        <summary>Cảnh báo và dòng bị bỏ qua (<?= count($issues) ?>)</summary>
        <ul>
        <?php foreach ($issues as $entry): ?>
            <li>Dòng <?= $escape($entry['row']) ?>
                <?php if ($entry['code'] !== null): ?>(<?= $escape($entry['code']) ?>)<?php endif; ?>:
                <?= $escape($entry['message'] ?? $statusLabels[$entry['status']]) ?>
            </li>
        <?php endforeach; ?>
        </ul>
    </details>
    <?php endif; ?>
<?php endif; ?>
<?php if ($confirmation !== null): ?>
    <h2>Xác nhận cập nhật</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $escape($_SESSION['vattu_missing_csrf']) ?>">
        <input type="hidden" name="confirmation" value="<?= $escape($confirmation) ?>">
        <label><input type="checkbox" name="confirm_backup" value="1" required> Tôi đã sao lưu DB và kiểm tra xem trước.</label>
        <button type="submit" name="action" value="apply">Xác nhận cập nhật DB</button>
    </form>
<?php endif; ?>
<p><a href="vattuthanhly.php">Quay lại danh sách vật tư</a></p>
</body>
</html>
