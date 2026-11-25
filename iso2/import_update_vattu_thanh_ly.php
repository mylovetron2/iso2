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
require_once __DIR__ . '/config/database.php';

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

const VATTU_UPDATE_FIELDS = [
    'soluong_conlai' => 'Số lượng tồn',
    'dongia_usd' => 'Đơn giá USD',
    'nguoiquanly' => 'Người quản lý',
    'vitribaoquan' => 'Vị trí bảo quản',
];

function normalizeUpdateImportText(string $value): string
{
    $trimmed = preg_replace('/\A[\p{Z}\s]+|[\p{Z}\s]+\z/u', '', $value);
    return $trimmed ?? trim($value);
}

function readUpdateTextCell($worksheet, string $coordinate): array
{
    $cell = $worksheet->getCell($coordinate);
    if ($cell->getDataType() === DataType::TYPE_FORMULA) {
        return ['valid' => false, 'value' => null];
    }

    $value = $cell->getFormattedValue();
    if ($value === null) {
        return ['valid' => true, 'value' => null];
    }
    if (!is_scalar($value)) {
        return ['valid' => false, 'value' => null];
    }

    $value = normalizeUpdateImportText((string)$value);
    return ['valid' => true, 'value' => $value === '' ? null : $value];
}

function readUpdateNumberCell($worksheet, string $coordinate): array
{
    $cell = $worksheet->getCell($coordinate);
    if ($cell->getDataType() === DataType::TYPE_FORMULA) {
        return ['valid' => false, 'value' => null];
    }

    $value = $cell->getValue();
    if ($value === null || $value === '') {
        return ['valid' => true, 'value' => null];
    }
    if (is_int($value) || is_float($value)) {
        return is_finite((float)$value)
            ? ['valid' => true, 'value' => (float)$value]
            : ['valid' => false, 'value' => null];
    }
    if (!is_string($value)) {
        return ['valid' => false, 'value' => null];
    }

    $number = normalizeUpdateImportText($value);
    if ($number === '') {
        return ['valid' => true, 'value' => null];
    }
    $number = preg_replace('/[\s\p{Z}]+/u', '', $number) ?? $number;
    $comma = strrpos($number, ',');
    $dot = strrpos($number, '.');
    if ($comma !== false && $dot !== false) {
        if ($comma > $dot) {
            $number = str_replace('.', '', $number);
            $number = str_replace(',', '.', $number);
        } else {
            $number = str_replace(',', '', $number);
        }
    } elseif ($comma !== false) {
        $number = str_replace(',', '.', $number);
    }

    if (!is_numeric($number) || !is_finite((float)$number)) {
        return ['valid' => false, 'value' => null];
    }

    return ['valid' => true, 'value' => (float)$number];
}

function validateUpdateImportHeaders($worksheet): void
{
    $headers = [
        'B1' => 'Mã vật tư',
        'E2' => 'Số lượng',
        'F2' => 'Đơn giá',
        'G2' => 'Người quản lý',
        'H2' => 'Vị trí bảo quản',
    ];

    foreach ($headers as $coordinate => $expected) {
        $value = normalizeUpdateImportText((string)$worksheet->getCell($coordinate)->getValue());
        if ($value === '' || mb_stripos($value, $expected, 0, 'UTF-8') === false) {
            throw new InvalidArgumentException("Không đúng mẫu Excel: ô {$coordinate} cần có tiêu đề “{$expected}”.");
        }
    }
}

function updateImportValueChanged(string $field, $oldValue, $newValue): bool
{
    if (in_array($field, ['soluong_conlai', 'dongia_usd'], true)) {
        if ($oldValue === null || $oldValue === '') {
            return $newValue !== null;
        }
        return $newValue !== null
            && abs(round((float)$oldValue, 2) - round((float)$newValue, 2)) > 0.000001;
    }

    return normalizeUpdateImportText((string)($oldValue ?? ''))
        !== normalizeUpdateImportText((string)($newValue ?? ''));
}

function buildUpdateImportPreview(PDO $db, string $filePath, bool $lockRows = false): array
{
    $spreadsheet = IOFactory::load($filePath);
    try {
        $worksheet = $spreadsheet->getActiveSheet();
        validateUpdateImportHeaders($worksheet);

        $rawRows = [];
        $codeCounts = [];
        $highestRow = $worksheet->getHighestDataRow();
        for ($rowNumber = 4; $rowNumber <= $highestRow; $rowNumber++) {
            $codeCell = readUpdateTextCell($worksheet, "B{$rowNumber}");
            $quantityCell = readUpdateNumberCell($worksheet, "E{$rowNumber}");
            $priceCell = readUpdateNumberCell($worksheet, "F{$rowNumber}");
            $managerCell = readUpdateTextCell($worksheet, "G{$rowNumber}");
            $locationCell = readUpdateTextCell($worksheet, "H{$rowNumber}");
            $values = [
                'soluong_conlai' => $quantityCell['value'],
                'dongia_usd' => $priceCell['value'],
                'nguoiquanly' => $managerCell['value'],
                'vitribaoquan' => $locationCell['value'],
            ];

            if ($codeCell['value'] === null
                && $quantityCell['value'] === null
                && $priceCell['value'] === null
                && $managerCell['value'] === null
                && $locationCell['value'] === null
                && $codeCell['valid']
                && $quantityCell['valid']
                && $priceCell['valid']
                && $managerCell['valid']
                && $locationCell['valid']) {
                continue;
            }

            $code = $codeCell['value'];
            if ($code !== null) {
                $key = mb_strtolower($code, 'UTF-8');
                $codeCounts[$key] = ($codeCounts[$key] ?? 0) + 1;
            }
            $rawRows[] = [
                'excel_row' => $rowNumber,
                'code' => $code,
                'code_valid' => $codeCell['valid'],
                'values' => $values,
                'valid' => $quantityCell['valid'] && $priceCell['valid']
                    && $managerCell['valid'] && $locationCell['valid'],
            ];
        }

        $suffix = $lockRows ? ' FOR UPDATE' : '';
        $find = $db->prepare("
            SELECT stt, mavattu, soluong_conlai, dongia_usd, nguoiquanly, vitribaoquan
            FROM vattu_thanh_ly_iso
            WHERE mavattu = :mavattu{$suffix}
        ");
        $previewRows = [];

        foreach ($rawRows as $raw) {
            $preview = [
                'excel_row' => $raw['excel_row'],
                'code' => $raw['code'] ?? '',
                'status' => '',
                'values' => $raw['values'],
                'old' => [],
                'changes' => [],
            ];

            if (!$raw['code_valid'] || !$raw['valid']) {
                $preview['status'] = 'invalid';
            } elseif ($raw['code'] === null) {
                $preview['status'] = 'missing_code';
            } elseif ($codeCounts[mb_strtolower($raw['code'], 'UTF-8')] > 1) {
                $preview['status'] = 'duplicate_file';
            } else {
                $find->execute([':mavattu' => $raw['code']]);
                $matches = $find->fetchAll(PDO::FETCH_ASSOC);
                if (count($matches) === 0) {
                    $preview['status'] = 'not_found';
                } elseif (count($matches) > 1) {
                    $preview['status'] = 'duplicate_db';
                } else {
                    $existing = $matches[0];
                    $preview['stt'] = $existing['stt'];
                    $preview['old'] = array_intersect_key($existing, $raw['values']);
                    foreach ($raw['values'] as $field => $newValue) {
                        if ($newValue === null) {
                            continue;
                        }
                        if (updateImportValueChanged($field, $existing[$field] ?? null, $newValue)) {
                            $preview['changes'][$field] = [
                                'old' => $existing[$field] ?? null,
                                'new' => $newValue,
                            ];
                        }
                    }
                    $preview['status'] = $preview['changes'] ? 'update' : 'no_change';
                }
            }

            $previewRows[] = $preview;
        }

        return $previewRows;
    } finally {
        $spreadsheet->disconnectWorksheets();
    }
}

function updateImportPreviewFingerprint(array $rows): string
{
    return hash(
        'sha256',
        json_encode($rows, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)
    );
}

function cleanupUpdateImportUpload(): void
{
    $path = $_SESSION['vattu_update_import_path'] ?? null;
    $prefix = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'vattu_update_';
    if (is_string($path) && strpos($path, $prefix) === 0 && is_file($path) && !unlink($path)) {
        error_log('Unable to remove temporary vattu update import file.');
    }
    unset(
        $_SESSION['vattu_update_import_path'],
        $_SESSION['vattu_update_import_name'],
        $_SESSION['vattu_update_import_hash'],
        $_SESSION['vattu_update_import_preview']
    );
}

if (!isset($_SESSION['vattu_update_import_csrf'])) {
    $_SESSION['vattu_update_import_csrf'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['vattu_update_import_preview']['expires'])
    && $_SESSION['vattu_update_import_preview']['expires'] < time()) {
    cleanupUpdateImportUpload();
}

$errorMessage = null;
$successMessage = null;
$previewRows = [];
$confirmation = null;
$filename = '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method not allowed.');
}

if ($method === 'POST') {
    try {
        $csrf = $_POST['csrf'] ?? null;
        if (!is_string($csrf)
            || !hash_equals($_SESSION['vattu_update_import_csrf'], $csrf)) {
            http_response_code(403);
            throw new InvalidArgumentException('Phiên làm việc không hợp lệ. Hãy tải lại trang.');
        }

        $action = $_POST['action'] ?? null;
        if ($action === 'preview') {
            cleanupUpdateImportUpload();
            $upload = $_FILES['excel_file'] ?? null;
            if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new InvalidArgumentException('Vui lòng chọn file Excel hợp lệ.');
            }
            if (($upload['size'] ?? 0) > 10 * 1024 * 1024) {
                throw new InvalidArgumentException('File Excel không được vượt quá 10 MB.');
            }

            $extension = strtolower(pathinfo((string)$upload['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ['xlsx', 'xls'], true)) {
                throw new InvalidArgumentException('Chỉ chấp nhận file .xlsx hoặc .xls.');
            }

            $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR
                . 'vattu_update_' . bin2hex(random_bytes(16)) . '.' . $extension;
            if (!move_uploaded_file((string)$upload['tmp_name'], $path)) {
                throw new RuntimeException('Không lưu được file tạm để đọc.');
            }

            $db = getDBConnection();
            $previewRows = buildUpdateImportPreview($db, $path);
            $fileHash = hash_file('sha256', $path);
            if ($fileHash === false) {
                throw new RuntimeException('Không thể kiểm tra file đã tải lên.');
            }

            $filename = basename((string)$upload['name']);
            $hasUpdates = count(array_filter(
                $previewRows,
                static fn(array $row): bool => $row['status'] === 'update'
            )) > 0;
            if ($hasUpdates) {
                $confirmation = bin2hex(random_bytes(32));
                $_SESSION['vattu_update_import_path'] = $path;
                $_SESSION['vattu_update_import_name'] = $filename;
                $_SESSION['vattu_update_import_hash'] = $fileHash;
                $_SESSION['vattu_update_import_preview'] = [
                    'token' => $confirmation,
                    'fingerprint' => updateImportPreviewFingerprint($previewRows),
                    'expires' => time() + 600,
                ];
            } else {
                if (is_file($path) && !unlink($path)) {
                    error_log('Unable to remove vattu update import file with no updates.');
                }
                $successMessage = $previewRows === []
                    ? 'File không có dòng dữ liệu để xử lý.'
                    : 'Không có dòng nào cần cập nhật.';
            }
        } elseif ($action === 'apply') {
            $state = $_SESSION['vattu_update_import_preview'] ?? null;
            $token = $_POST['confirmation'] ?? null;
            $path = $_SESSION['vattu_update_import_path'] ?? null;
            $filename = (string)($_SESSION['vattu_update_import_name'] ?? '');
            if (!is_array($state)
                || $state['expires'] < time()
                || !is_string($token)
                || !hash_equals($state['token'], $token)
                || ($_POST['confirm_backup'] ?? null) !== '1'
                || !is_string($path)
                || !is_file($path)
                || !isset($_SESSION['vattu_update_import_hash'])) {
                throw new InvalidArgumentException('Phiên xem trước đã hết hạn. Hãy tải file lên và xem trước lại.');
            }

            unset($_SESSION['vattu_update_import_preview']);
            $currentFileHash = hash_file('sha256', $path);
            if ($currentFileHash === false
                || !hash_equals((string)$_SESSION['vattu_update_import_hash'], $currentFileHash)) {
                throw new InvalidArgumentException('File đã thay đổi. Hãy tải file lên và xem trước lại.');
            }

            $db = getDBConnection();
            $db->beginTransaction();
            $previewRows = buildUpdateImportPreview($db, $path, true);
            if (!hash_equals($state['fingerprint'], updateImportPreviewFingerprint($previewRows))) {
                throw new InvalidArgumentException('Dữ liệu trong DB đã thay đổi từ lúc xem trước. Hãy xem trước lại.');
            }

            $updated = 0;
            foreach ($previewRows as $row) {
                if ($row['status'] !== 'update') {
                    continue;
                }

                $set = [];
                $params = [':stt' => $row['stt'], ':mavattu' => $row['code']];
                foreach ($row['changes'] as $field => $change) {
                    if (!array_key_exists($field, VATTU_UPDATE_FIELDS)) {
                        throw new RuntimeException('Có trường dữ liệu ngoài danh sách cập nhật.');
                    }
                    $set[] = "`{$field}` = :{$field}";
                    $params[":{$field}"] = $change['new'];
                }

                $stmtUpdate = $db->prepare(
                    'UPDATE vattu_thanh_ly_iso SET ' . implode(', ', $set)
                    . ' WHERE stt = :stt AND mavattu = :mavattu'
                );
                $stmtUpdate->execute($params);
                $updated += $stmtUpdate->rowCount();
            }
            $db->commit();

            $summary = array_count_values(array_column($previewRows, 'status'));
            $successMessage = "Hoàn tất cập nhật {$updated} vật tư. "
                . 'Không tìm thấy: ' . ($summary['not_found'] ?? 0)
                . '; dòng không hợp lệ/trùng mã: '
                . (($summary['invalid'] ?? 0) + ($summary['missing_code'] ?? 0)
                    + ($summary['duplicate_file'] ?? 0) + ($summary['duplicate_db'] ?? 0))
                . '; không đổi: ' . ($summary['no_change'] ?? 0) . '.';
            error_log('Vat tu Excel update: ' . json_encode([
                'user_id' => $_SESSION['user_id'],
                'updated' => $updated,
                'statuses' => $summary,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            cleanupUpdateImportUpload();
            $confirmation = null;
        } else {
            throw new InvalidArgumentException('Thao tác không hợp lệ.');
        }
    } catch (InvalidArgumentException $error) {
        if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
            $db->rollBack();
        }
        if (http_response_code() !== 403) {
            http_response_code(400);
        }
        $errorMessage = $error->getMessage();
        if (isset($path) && is_string($path) && is_file($path)
            && !isset($_SESSION['vattu_update_import_path'])) {
            if (!unlink($path)) {
                error_log('Unable to remove rejected vattu update import file.');
            }
        }
        if (($_POST['action'] ?? null) === 'apply') {
            cleanupUpdateImportUpload();
        }
    } catch (Throwable $error) {
        if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        error_log('Vat tu Excel update failed: ' . $error->getMessage());
        $errorMessage = 'Không hoàn tất import. Dữ liệu chưa được xác nhận cập nhật; kiểm tra PHP error log trước khi chạy lại.';
        cleanupUpdateImportUpload();
        if (isset($path) && is_string($path) && is_file($path)) {
            $prefix = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'vattu_update_';
            if (strpos($path, $prefix) === 0 && !unlink($path)) {
                error_log('Unable to remove failed vattu update import file.');
            }
        }
        $previewRows = [];
        $confirmation = null;
    }
}

$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$statusLabels = [
    'update' => 'Sẽ cập nhật',
    'no_change' => 'Không thay đổi',
    'not_found' => 'Không tìm thấy mã',
    'duplicate_db' => 'Mã bị trùng trong DB',
    'duplicate_file' => 'Mã bị trùng trong file',
    'missing_code' => 'Thiếu mã vật tư',
    'invalid' => 'Dữ liệu không hợp lệ / ô có công thức',
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Import cập nhật vật tư thanh lý</title>
</head>
<body>
<h1>Import cập nhật vật tư thanh lý</h1>
<p>Đọc dòng dữ liệu từ dòng 4: cột B dùng để khớp mã vật tư; chỉ cập nhật cột E số lượng tồn,
cột F đơn giá USD, cột G người quản lý và cột H vị trí bảo quản. Cột STT, tên vật tư và ngày trên
tiêu đề được bỏ qua. Ô trống giữ nguyên dữ liệu hiện có; mã không có trong DB không được thêm mới.</p>
<p><strong>Sao lưu cơ sở dữ liệu trước khi cập nhật.</strong> Xem trước không ghi dữ liệu.
Khi xác nhận, chương trình kiểm tra lại file và dữ liệu hiện tại trước khi cập nhật trong transaction.</p>

<?php if ($errorMessage !== null): ?>
    <p role="alert"><?= $escape($errorMessage) ?></p>
<?php endif; ?>
<?php if ($successMessage !== null): ?>
    <p role="status"><?= $escape($successMessage) ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= $escape($_SESSION['vattu_update_import_csrf']) ?>">
    <input type="file" name="excel_file" accept=".xlsx,.xls" required>
    <button type="submit" name="action" value="preview">Tải file và xem trước</button>
</form>

<?php if ($previewRows !== []): ?>
    <?php $statusCounts = array_count_values(array_column($previewRows, 'status')); ?>
    <h2>Xem trước: <?= $escape($_SESSION['vattu_update_import_name'] ?? $filename) ?></h2>
    <p>Số dòng đọc được: <?= $escape(count($previewRows)) ?>;
    có thay đổi: <?= $escape($statusCounts['update'] ?? 0) ?>;
    không tìm thấy mã: <?= $escape($statusCounts['not_found'] ?? 0) ?>.</p>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>Dòng Excel</th>
                <th>Mã vật tư</th>
                <th>Trạng thái</th>
                <?php foreach (VATTU_UPDATE_FIELDS as $label): ?>
                    <th><?= $escape($label) ?> (cũ → mới)</th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($previewRows as $row): ?>
                <tr>
                    <td><?= $escape($row['excel_row']) ?></td>
                    <td><?= $escape($row['code']) ?></td>
                    <td><?= $escape($statusLabels[$row['status']] ?? $row['status']) ?></td>
                    <?php foreach (VATTU_UPDATE_FIELDS as $field => $label): ?>
                        <?php $change = $row['changes'][$field] ?? null; ?>
                        <td>
                            <?php if ($change !== null): ?>
                                <?= $escape($change['old'] === null ? 'NULL' : $change['old']) ?> → <?= $escape($change['new']) ?>
                            <?php elseif (($row['values'][$field] ?? null) !== null): ?>
                                Không đổi: <?= $escape($row['values'][$field]) ?>
                            <?php elseif (array_key_exists($field, $row['old'])): ?>
                                Giữ nguyên: <?= $escape($row['old'][$field] === null ? 'NULL' : $row['old'][$field]) ?>
                            <?php elseif ($row['status'] === 'invalid'): ?>
                                Không hợp lệ
                            <?php else: ?>
                                Ô trống
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if ($confirmation !== null): ?>
    <h2>Xác nhận cập nhật</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $escape($_SESSION['vattu_update_import_csrf']) ?>">
        <input type="hidden" name="confirmation" value="<?= $escape($confirmation) ?>">
        <label>
            <input type="checkbox" name="confirm_backup" value="1" required>
            Tôi đã sao lưu DB và kiểm tra danh sách xem trước.
        </label>
        <button type="submit" name="action" value="apply">Xác nhận cập nhật DB</button>
    </form>
<?php endif; ?>

<p><a href="vattuthanhly.php">Quay lại danh sách vật tư</a></p>
</body>
</html>
