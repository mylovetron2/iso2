<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/vattu_import_quantity_price.php';

requireAuth();
if (!hasPermission('vattu.edit')) {
    http_response_code(403);
    exit('Bạn không có quyền cập nhật vật tư.');
}

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

function cleanupQuantityPriceUpload(): void
{
    $path = $_SESSION['vattu_quantity_price_path'] ?? null;
    $prefix = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'vattu_quantity_price_';
    if (is_string($path) && strpos($path, $prefix) === 0 && is_file($path) && !unlink($path)) {
        error_log('Unable to remove temporary quantity/price import file.');
    }
    unset(
        $_SESSION['vattu_quantity_price_path'],
        $_SESSION['vattu_quantity_price_hash'],
        $_SESSION['vattu_quantity_price_preview']
    );
}

if (!isset($_SESSION['vattu_quantity_price_csrf'])) {
    $_SESSION['vattu_quantity_price_csrf'] = bin2hex(random_bytes(32));
}
if (isset($_SESSION['vattu_quantity_price_preview']['expires'])
    && $_SESSION['vattu_quantity_price_preview']['expires'] < time()) {
    cleanupQuantityPriceUpload();
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
        if (!is_string($csrf) || !hash_equals($_SESSION['vattu_quantity_price_csrf'], $csrf)) {
            http_response_code(403);
            throw new InvalidArgumentException('Phiên làm việc không hợp lệ. Hãy tải lại trang.');
        }

        $action = $_POST['action'] ?? null;
        if ($action === 'preview') {
            cleanupQuantityPriceUpload();
            $upload = $_FILES['excel_file'] ?? null;
            if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new InvalidArgumentException('Vui lòng chọn file Excel hợp lệ.');
            }
            if (($upload['size'] ?? 0) <= 0 || $upload['size'] > 10 * 1024 * 1024) {
                throw new InvalidArgumentException('File phải có dữ liệu và không được vượt quá 10 MB.');
            }
            $extension = strtolower(pathinfo((string)$upload['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ['xlsx', 'xls'], true)) {
                throw new InvalidArgumentException('Chỉ chấp nhận file .xlsx hoặc .xls.');
            }

            $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR
                . 'vattu_quantity_price_' . bin2hex(random_bytes(16)) . '.' . $extension;
            if (!move_uploaded_file((string)$upload['tmp_name'], $path)) {
                throw new RuntimeException('Không lưu được file tạm để đọc.');
            }

            $type = IOFactory::identify($path, ['Xlsx', 'Xls']);
            $spreadsheet = IOFactory::createReader($type)->load($path);
            try {
                $rows = vattuQuantityPriceReadRows($spreadsheet->getActiveSheet());
            } finally {
                $spreadsheet->disconnectWorksheets();
            }

            $db = getDBConnection();
            $preview = vattuQuantityPricePreview($db, $rows);
            $hasUpdates = count(array_filter(
                $preview,
                static fn(array $row): bool => $row['status'] === 'update'
            )) > 0;
            if ($hasUpdates) {
                $hash = hash_file('sha256', $path);
                if ($hash === false) {
                    throw new RuntimeException('Không thể kiểm tra file đã tải lên.');
                }
                $confirmation = bin2hex(random_bytes(32));
                $_SESSION['vattu_quantity_price_path'] = $path;
                $_SESSION['vattu_quantity_price_hash'] = $hash;
                $_SESSION['vattu_quantity_price_preview'] = [
                    'token' => $confirmation,
                    'expires' => time() + 600,
                    'rows' => $rows,
                    'fingerprint' => vattuQuantityPriceFingerprint($preview),
                ];
            } else {
                if (is_file($path) && !unlink($path)) {
                    error_log('Unable to remove quantity/price import file with no updates.');
                }
                $message = $preview === [] ? 'File không có dòng dữ liệu.' : 'Không có dòng nào cần cập nhật.';
            }
        } elseif ($action === 'apply') {
            $state = $_SESSION['vattu_quantity_price_preview'] ?? null;
            $token = $_POST['confirmation'] ?? null;
            $path = $_SESSION['vattu_quantity_price_path'] ?? null;
            if (!is_array($state) || $state['expires'] < time() || !is_string($token)
                || !hash_equals($state['token'], $token)
                || ($_POST['confirm_backup'] ?? null) !== '1'
                || !is_string($path) || !is_file($path)
                || !isset($_SESSION['vattu_quantity_price_hash'])) {
                throw new InvalidArgumentException('Phiên xem trước hết hạn. Hãy tải file lên và xem trước lại.');
            }

            unset($_SESSION['vattu_quantity_price_preview']);
            $actualHash = hash_file('sha256', $path);
            if ($actualHash === false || !hash_equals($_SESSION['vattu_quantity_price_hash'], $actualHash)) {
                throw new InvalidArgumentException('File đã thay đổi. Hãy tải file lên và xem trước lại.');
            }

            $db = getDBConnection();
            $db->beginTransaction();
            $preview = vattuQuantityPricePreview($db, $state['rows'], true);
            if (!hash_equals($state['fingerprint'], vattuQuantityPriceFingerprint($preview))) {
                throw new InvalidArgumentException('Dữ liệu DB đã thay đổi từ lúc xem trước. Hãy xem trước lại.');
            }
            $updated = vattuQuantityPriceApply($db, $preview);
            $db->commit();
            $message = "Đã cập nhật {$updated} vật tư.";
            error_log('Vat tu quantity/price import: ' . json_encode([
                'user_id' => $_SESSION['user_id'],
                'updated' => $updated,
            ], JSON_THROW_ON_ERROR));
            cleanupQuantityPriceUpload();
            $preview = [];
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
        if ($action === 'apply') {
            cleanupQuantityPriceUpload();
        } elseif (isset($path) && is_string($path) && is_file($path)
            && !isset($_SESSION['vattu_quantity_price_path'])) {
            if (!unlink($path)) {
                error_log('Unable to remove rejected quantity/price import file.');
            }
        }
        $preview = [];
    } catch (Throwable $error) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        error_log('Vat tu quantity/price import failed: ' . $error->getMessage());
        $errorMessage = 'Import chưa hoàn tất. Hãy kiểm tra PHP error log trước khi chạy lại.';
        cleanupQuantityPriceUpload();
        if (isset($path) && is_string($path) && is_file($path)) {
            $prefix = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'vattu_quantity_price_';
            if (strpos($path, $prefix) === 0 && !unlink($path)) {
                error_log('Unable to remove failed quantity/price import file.');
            }
        }
        $preview = [];
    }
}

$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$statusLabels = [
    'update' => 'Sẽ cập nhật',
    'no_change' => 'Không thay đổi',
    'not_found' => 'Không tìm thấy mã',
    'duplicate_db' => 'Mã bị trùng trong DB',
    'duplicate_file' => 'Mã bị trùng trong file',
    'missing_code' => 'Thiếu mã vật tư',
    'invalid' => 'Dữ liệu không hợp lệ hoặc có công thức',
];
?>
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><title>Cập nhật số lượng và đơn giá USD</title></head>
<body>
<h1>Cập nhật số lượng và đơn giá USD</h1>
<p>Chọn file Excel theo mẫu mới. Tiêu đề ở dòng 1, dữ liệu từ dòng 2:
cột A dùng để khớp mã vật tư, cột B là số lượng và cột C là đơn giá USD.
Giá trị có trong Excel sẽ ghi đè DB;
ô trống giữ nguyên. Mã không tồn tại hoặc bị trùng sẽ bỏ qua.</p>
<p><strong>Sao lưu DB trước khi cập nhật.</strong> Xem trước chưa ghi dữ liệu.
Khi xác nhận, chương trình sẽ đối chiếu lại dữ liệu và cập nhật trong transaction.</p>
<?php if ($errorMessage !== null): ?><p role="alert"><?= $escape($errorMessage) ?></p><?php endif; ?>
<?php if ($message !== null): ?><p role="status"><?= $escape($message) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= $escape($_SESSION['vattu_quantity_price_csrf']) ?>">
    <input type="file" name="excel_file" accept=".xlsx,.xls" required>
    <button type="submit" name="action" value="preview">Tải file và xem trước</button>
</form>
<?php if ($preview !== []): ?>
    <?php
    $updates = array_filter($preview, static fn(array $row): bool => $row['status'] === 'update');
    $issues = array_filter($preview, static fn(array $row): bool =>
        !in_array($row['status'], ['update', 'no_change'], true)
    );
    ?>
    <h2>Xem trước: <?= count($updates) ?> dòng sẽ cập nhật</h2>
    <?php if ($updates !== []): ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead><tr><th>Dòng</th><th>Mã vật tư</th><th>Trạng thái</th>
        <?php foreach (VATTU_QUANTITY_PRICE_FIELDS as $label): ?>
            <th><?= $escape($label) ?> (cũ → mới)</th>
        <?php endforeach; ?>
        </tr></thead>
        <tbody>
        <?php foreach ($updates as $row): ?>
            <tr>
                <td><?= $escape($row['row']) ?></td>
                <td><?= $escape($row['code']) ?></td>
                <td><?= $escape($statusLabels[$row['status']]) ?></td>
                <?php foreach (VATTU_QUANTITY_PRICE_FIELDS as $field => $label): ?>
                    <td>
                        <?php if (isset($row['changes'][$field])): ?>
                            <?= $escape($row['changes'][$field]['old'] ?? 'NULL') ?>
                            → <?= $escape($row['changes'][$field]['new']) ?>
                        <?php else: ?>Giữ nguyên<?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <?php if ($issues !== []): ?>
    <details><summary>Dòng bị bỏ qua (<?= count($issues) ?>)</summary>
        <ul><?php foreach ($issues as $row): ?>
            <li>Dòng <?= $escape($row['row']) ?> (<?= $escape($row['code']) ?>):
                <?= $escape($statusLabels[$row['status']]) ?></li>
        <?php endforeach; ?></ul>
    </details>
    <?php endif; ?>
<?php endif; ?>
<?php if ($confirmation !== null): ?>
    <h2>Xác nhận cập nhật</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $escape($_SESSION['vattu_quantity_price_csrf']) ?>">
        <input type="hidden" name="confirmation" value="<?= $escape($confirmation) ?>">
        <label><input type="checkbox" name="confirm_backup" value="1" required>
            Tôi đã sao lưu DB và kiểm tra xem trước.</label>
        <button type="submit" name="action" value="apply">Xác nhận cập nhật DB</button>
    </form>
<?php endif; ?>
<p><a href="vattuthanhly.php">Quay lại danh sách vật tư</a></p>
</body>
</html>
